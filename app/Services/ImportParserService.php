<?php

namespace App\Services;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

/**
 * Part C — turns an uploaded .docx or .xlsx into a draft FormSchemaService-shaped
 * schema.
 *
 * Strategy (the "hybrid approach" the brief asks for):
 *  1. Parse deterministically first. Headings/paragraph structure and bullet
 *     lists give us sections, field boundaries and options for free — no LLM
 *     call needed for the common case, so imports stay fast and cheap.
 *  2. Fall back to AI only for the type of a field whose label doesn't match
 *     any of our keyword heuristics ($this->inferType() returns null). We
 *     batch all ambiguous labels into a single classification call instead
 *     of one call per field.
 *  3. Anything we can't turn into a field at all (e.g. a free-form table) is
 *     recorded in $warnings and surfaced on the mapping screen — never
 *     silently dropped.
 */
class ImportParserService
{
    public function __construct(protected GeminiService $gemini)
    {
    }

    /**
     * @return array{schema: array, field_meta: array, warnings: array}
     */
    public function parseDocx(string $path): array
    {
        $phpWord = WordIOFactory::load($path, 'Word2007');

        $fields = [];
        $warnings = [];
        $currentSection = 'General';
        $lastField = null; // reference to the field a following bullet list attaches to

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof Title) {
                    $currentSection = trim($element->getText()) ?: $currentSection;
                    $lastField = null;
                    continue;
                }

                if ($element instanceof Table) {
                    // Tables are too free-form to trust deterministically here —
                    // report the block instead of guessing wrong.
                    $warnings[] = "A table in section \"{$currentSection}\" was skipped — review it manually and add any fields it contains by hand.";
                    continue;
                }

                if ($element instanceof ListItem) {
                    $text = trim($this->elementText($element));
                    if ($text === '') {
                        continue;
                    }
                    if ($lastField && in_array($lastField['type'], ['dropdown', 'radio', 'checkbox'], true)) {
                        $fields[$lastField['key']]['options'][] = $text;
                    } elseif ($lastField) {
                        // We had a plain field but it turned out to have options
                        // after it — upgrade it to a dropdown.
                        $fields[$lastField['key']]['type'] = 'dropdown';
                        $fields[$lastField['key']]['options'] = [$text];
                    } else {
                        $warnings[] = "Bulleted line \"{$text}\" appeared with no preceding question — skipped.";
                    }
                    continue;
                }

                if ($element instanceof Text || $element instanceof TextRun) {
                    $text = trim($this->elementText($element));
                    if ($text === '' || strtolower($text) === strtolower($currentSection)) {
                        continue;
                    }

                    if ($this->isHeadingLike($element, $text)) {
                        $currentSection = $text;
                        $lastField = null;
                        continue;
                    }

                    $field = $this->lineToField($text, $currentSection);
                    if ($field) {
                        $fields[$field['key']] = $field;
                        $lastField = $field;
                    } else {
                        $warnings[] = "Line \"{$text}\" didn't look like a question or field — skipped.";
                    }
                }
            }
        }

        return $this->finalize(array_values($fields), $warnings);
    }

    /**
     * Supports two layouts, auto-detected from the header row:
     *  - "structured": Label | Type | Required | Options | Help Text columns
     *  - "header-row": a plain sheet where every column header is a field label
     *    (e.g. an export/template someone filled with one sample row)
     *
     * @return array{schema: array, field_meta: array, warnings: array}
     */
    public function parseXlsx(string $path): array
    {
        $spreadsheet = SpreadsheetIOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows) || empty(array_filter($rows[0] ?? []))) {
            return $this->finalize([], ['The sheet appears to be empty — nothing to import.']);
        }

        $header = array_map(fn ($h) => is_string($h) ? trim(strtolower($h)) : $h, $rows[0]);

        $structuredColumns = ['label', 'type', 'required', 'options', 'help text', 'help_text'];
        $isStructured = count(array_intersect($header, ['label', 'type'])) === 2;

        if ($isStructured) {
            return $this->parseStructuredSheet($header, array_slice($rows, 1));
        }

        return $this->parseHeaderRowSheet($rows[0]);
    }

    protected function parseStructuredSheet(array $header, array $dataRows): array
    {
        $colIndex = array_flip($header);
        $fields = [];
        $warnings = [];

        foreach ($dataRows as $i => $row) {
            $label = trim((string) ($row[$colIndex['label'] ?? -1] ?? ''));
            if ($label === '') {
                continue;
            }

            $rawType = strtolower(trim((string) ($row[$colIndex['type'] ?? -1] ?? '')));
            $required = $this->parseBoolish($row[$colIndex['required'] ?? -1] ?? '');
            $optionsRaw = trim((string) ($row[$colIndex['options'] ?? -1] ?? ''));
            $help = trim((string) ($row[$colIndex['help text'] ?? $colIndex['help_text'] ?? -1] ?? ''));

            $type = in_array($rawType, \App\Services\FormSchemaService::FIELD_TYPES, true) ? $rawType : null;
            $confidence = $type ? 'deterministic' : null;

            if (!$type) {
                $guess = $this->inferType($label);
                $type = $guess['type'];
                $confidence = $guess['confidence'];
            }

            $key = $this->uniqueKey($label, $fields);
            $field = [
                'key' => $key,
                'type' => $type,
                'label' => $label,
                'placeholder' => null,
                'help_text' => $help ?: null,
                'default' => null,
                'required' => $required,
                'section' => 'Imported Fields',
            ];

            if (in_array($type, ['dropdown', 'radio', 'checkbox'], true)) {
                $field['options'] = $optionsRaw !== ''
                    ? array_values(array_filter(array_map('trim', explode(',', $optionsRaw))))
                    : ['Option 1', 'Option 2'];
            }

            $fields[$key] = ['field' => $field, 'confidence' => $confidence];
        }

        if (empty($fields)) {
            $warnings[] = 'No usable rows found under the Label/Type header row.';
        }

        return $this->finalize(array_column($fields, 'field'), $warnings, $fields);
    }

    protected function parseHeaderRowSheet(array $headerRow): array
    {
        $fields = [];
        $warnings = [];

        foreach ($headerRow as $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }

            $guess = $this->inferType($label);
            $key = $this->uniqueKey($label, $fields);

            $field = [
                'key' => $key,
                'type' => $guess['type'],
                'label' => $label,
                'placeholder' => null,
                'help_text' => null,
                'default' => null,
                'required' => false,
                'section' => 'Imported Fields',
            ];

            if (in_array($guess['type'], ['dropdown', 'radio', 'checkbox'], true)) {
                $field['options'] = ['Option 1', 'Option 2'];
            }

            $fields[$key] = ['field' => $field, 'confidence' => $guess['confidence']];
        }

        if (empty($fields)) {
            $warnings[] = 'No column headers found on the first row.';
        }

        return $this->finalize(array_column($fields, 'field'), $warnings, $fields);
    }

    /**
     * Turn one line of document text into a field, or null if it isn't one.
     */
    protected function lineToField(string $text, string $section): ?array
    {
        // Strip trailing "(required)" / "(optional)" markers.
        $required = (bool) preg_match('/\(\s*required\s*\)/i', $text);
        $clean = trim(preg_replace('/\(\s*(required|optional)[^)]*\)/i', '', $text));

        $looksLikeQuestion = (bool) preg_match('/\?\s*$/', $clean)
            || (bool) preg_match('/_{3,}/', $clean)
            || (bool) preg_match('/:\s*$/', $clean)
            || Str::endsWith(strtolower($clean), ['upload', 'attach resume', 'attach', 'signature']);

        if (!$looksLikeQuestion) {
            return null;
        }

        $label = trim(preg_replace(['/_{3,}/', '/\?\s*$/', '/:\s*$/'], '', $clean));
        $label = trim($label);
        if ($label === '') {
            return null;
        }

        $guess = $this->inferType($label);

        $field = [
            'key' => Str::slug($label, '_'),
            'type' => $guess['type'],
            'label' => $label,
            'placeholder' => null,
            'help_text' => null,
            'default' => null,
            'required' => $required,
            'section' => $section,
            '_confidence' => $guess['confidence'],
        ];

        if (in_array($guess['type'], ['dropdown', 'radio', 'checkbox'], true)) {
            $field['options'] = [];
        }

        if ($guess['type'] === 'file') {
            $field['validation'] = ['file_types' => ['pdf', 'doc', 'docx'], 'max_file_size_kb' => 5120];
        }

        return $field;
    }

    /**
     * Deterministic keyword heuristics. Returns null type when nothing matches
     * confidently, so the caller can escalate to AI classification.
     */
    protected function inferType(string $label): array
    {
        $l = strtolower($label);

        $rules = [
            'email' => 'email',
            'phone' => 'phone',
            'mobile' => 'phone',
            'contact number' => 'phone',
            'date of birth' => 'date',
            'dob' => 'date',
            'start date' => 'date',
            'date' => 'date',
            'upload' => 'file',
            'resume' => 'file',
            'attach' => 'file',
            'cv' => 'file',
            'signature' => 'file',
            'comment' => 'textarea',
            'description' => 'textarea',
            'address' => 'textarea',
            'summary' => 'textarea',
            'age' => 'number',
            'experience' => 'number',
            'years' => 'number',
            'salary' => 'number',
            'rate' => 'rating',
            'rating' => 'rating',
            'confidence' => 'rating',
            'select all that apply' => 'checkbox',
            'which of the following' => 'checkbox',
        ];

        foreach ($rules as $needle => $type) {
            if (Str::contains($l, $needle)) {
                return ['type' => $type, 'confidence' => 'deterministic'];
            }
        }

        // Ambiguous — mark for AI classification (batched, see classifyAmbiguous()).
        return ['type' => 'text', 'confidence' => 'ambiguous'];
    }

    /**
     * Batch AI classification for every field the deterministic pass marked
     * "ambiguous". One call for the whole document/sheet, not one per field.
     * On any failure we simply keep the deterministic fallback (plain text) —
     * an import must never fail because the AI layer is unavailable.
     */
    public function classifyAmbiguous(array &$fields, array &$fieldMeta): void
    {
        $ambiguous = [];
        foreach ($fields as $i => $field) {
            if (($fieldMeta[$field['key']] ?? null) === 'ambiguous') {
                $ambiguous[$i] = $field['label'];
            }
        }

        if (empty($ambiguous)) {
            return;
        }

        try {
            $types = $this->gemini->classifyFieldTypes($ambiguous);
        } catch (\Throwable $e) {
            return; // keep deterministic 'text' fallback
        }

        foreach ($ambiguous as $i => $label) {
            $type = $types[$i] ?? null;
            if ($type && in_array($type, \App\Services\FormSchemaService::FIELD_TYPES, true)) {
                $fields[$i]['type'] = $type;
                $fieldMeta[$fields[$i]['key']] = 'ai';
                if (in_array($type, ['dropdown', 'radio', 'checkbox'], true) && empty($fields[$i]['options'])) {
                    $fields[$i]['options'] = ['Option 1', 'Option 2'];
                }
            }
        }
    }

    protected function finalize(array $fields, array $warnings, array $withConfidence = []): array
    {
        $fieldMeta = [];

        foreach ($fields as $i => $field) {
            if (isset($withConfidence[$field['key']]['confidence'])) {
                $fieldMeta[$field['key']] = $withConfidence[$field['key']]['confidence'];
            } else {
                $fieldMeta[$field['key']] = $field['_confidence'] ?? 'deterministic';
            }
            unset($fields[$i]['_confidence']);
        }

        $this->classifyAmbiguous($fields, $fieldMeta);

        return [
            'schema' => ['title' => 'Imported Form', 'fields' => array_values($fields)],
            'field_meta' => $fieldMeta,
            'warnings' => $warnings,
        ];
    }

    protected function isHeadingLike($element, string $text): bool
    {
        if (method_exists($element, 'getFontStyle')) {
            $font = $element->getFontStyle();
            if (is_object($font) && method_exists($font, 'isBold') && $font->isBold()) {
                return strlen($text) < 60 && !Str::endsWith($text, '?');
            }
        }
        return false;
    }

    protected function elementText($element): string
    {
        if (method_exists($element, 'getText')) {
            $text = $element->getText();
            if (is_string($text)) {
                return $text;
            }
        }

        if ($element instanceof AbstractContainer) {
            $out = '';
            foreach ($element->getElements() as $child) {
                if ($child instanceof Text) {
                    $out .= $child->getText();
                }
            }
            return $out;
        }

        return '';
    }

    protected function uniqueKey(string $label, array $existing): string
    {
        $base = Str::slug($label, '_') ?: 'field';
        $key = $base;
        $n = 2;
        while (isset($existing[$key])) {
            $key = $base . '_' . $n++;
        }
        return $key;
    }

    protected function parseBoolish($value): bool
    {
        $v = strtolower(trim((string) $value));
        return in_array($v, ['yes', 'y', 'true', '1', 'required'], true);
    }
}
