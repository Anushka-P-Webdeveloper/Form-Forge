<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

/**
 * The JSON schema shape (per field) that the canvas, the raw JSON editor,
 * the AI generator, and the import pipeline all agree on:
 *
 * {
 *   "key": "full_name",            // unique within the form, used as the input name
 *   "type": "text",                // one of self::FIELD_TYPES
 *   "label": "Full Name",
 *   "placeholder": "Jane Doe",
 *   "help_text": "As it appears on your ID",
 *   "default": null,
 *   "required": true,
 *   "options": ["A", "B"],         // dropdown / radio / checkbox only
 *   "validation": {
 *       "min": null, "max": null, "min_length": null, "max_length": null,
 *       "numeric": false, "email": false, "url": false, "regex": null,
 *       "file_types": ["pdf","docx"], "max_file_size_kb": 5120
 *   },
 *   "section": "Personal Details"  // groups fields into sections/steps
 * }
 *
 * Top level: { "title": "...", "sections": ["..."], "fields": [ {...}, ... ] }
 */
class FormSchemaService
{
    public const FIELD_TYPES = [
        'text', 'textarea', 'number', 'email', 'phone', 'date',
        'dropdown', 'radio', 'checkbox', 'file', 'heading', 'rating',
    ];

    /**
     * Validate a raw decoded schema array. Returns [bool valid, array errors].
     * Used before every save (manual edit, AI generate, AI edit, import).
     */
    public function validate($schema): array
    {
        $errors = [];

        if (!is_array($schema) || !isset($schema['fields']) || !is_array($schema['fields'])) {
            return [false, ['Schema must be an object with a "fields" array.']];
        }

        $seenKeys = [];

        foreach ($schema['fields'] as $i => $field) {
            $prefix = "fields[$i]";

            if (empty($field['key'])) {
                $errors[] = "$prefix: missing \"key\".";
                continue;
            }

            if (in_array($field['key'], $seenKeys, true)) {
                $errors[] = "$prefix: duplicate key \"{$field['key']}\".";
            }
            $seenKeys[] = $field['key'];

            if (empty($field['type']) || !in_array($field['type'], self::FIELD_TYPES, true)) {
                $errors[] = "$prefix: invalid or missing type. Allowed: " . implode(', ', self::FIELD_TYPES);
            }

            if (empty($field['label'])) {
                $errors[] = "$prefix: missing \"label\".";
            }

            if (in_array($field['type'] ?? null, ['dropdown', 'radio', 'checkbox'], true)
                && empty($field['options'])) {
                $errors[] = "$prefix: type \"{$field['type']}\" requires a non-empty \"options\" array.";
            }
        }

        return [empty($errors), $errors];
    }

    /**
     * Attempt to repair a near-valid schema coming back from the LLM:
     * - map hallucinated/unknown types to the closest known type
     * - drop fields that are unsalvageable (no key at all)
     * - fill missing labels from the key
     */
    public function repair(array $schema): array
    {
        $typeMap = [
            'string' => 'text', 'longtext' => 'textarea', 'paragraph' => 'textarea',
            'int' => 'number', 'integer' => 'number', 'float' => 'number',
            'select' => 'dropdown', 'single_choice' => 'radio', 'multi_choice' => 'checkbox',
            'multiselect' => 'checkbox', 'upload' => 'file', 'attachment' => 'file',
            'section' => 'heading', 'title' => 'heading', 'stars' => 'rating',
            'tel' => 'phone', 'mobile' => 'phone', 'datetime' => 'date',
        ];

        $schema['fields'] = array_values(array_filter(array_map(function ($field) use ($typeMap) {
            if (empty($field['key'])) {
                return null; // unsalvageable, drop it
            }

            $type = strtolower($field['type'] ?? 'text');
            if (!in_array($type, self::FIELD_TYPES, true)) {
                $type = $typeMap[$type] ?? 'text';
            }
            $field['type'] = $type;

            if (empty($field['label'])) {
                $field['label'] = ucwords(str_replace(['_', '-'], ' ', $field['key']));
            }

            if (in_array($type, ['dropdown', 'radio', 'checkbox'], true) && empty($field['options'])) {
                $field['options'] = ['Option 1', 'Option 2'];
            }

            $field['required'] = (bool) ($field['required'] ?? false);

            return $field;
        }, $schema['fields'] ?? [])));

        return $schema;
    }

    /**
     * Build Laravel validation rules from the schema. This is what the public
     * fill endpoint runs server-side — the browser's validation is UX only.
     */
    public function buildValidationRules(array $schema): array
    {
        $rules = [];

        foreach ($schema['fields'] ?? [] as $field) {
            if ($field['type'] === 'heading') {
                continue;
            }

            $key = $field['key'];
            $fieldRules = [];

            $fieldRules[] = ($field['required'] ?? false) ? 'required' : 'nullable';

            switch ($field['type']) {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'number':
                case 'rating':
                    $fieldRules[] = 'numeric';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'file':
                    $fieldRules[] = 'file';
                    break;
                case 'dropdown':
                case 'radio':
                    $fieldRules[] = 'in:' . implode(',', $field['options'] ?? []);
                    break;
                case 'checkbox':
                    $fieldRules[] = 'array';
                    break;
            }

            $v = $field['validation'] ?? [];
            if (!empty($v['min'])) $fieldRules[] = 'min:' . $v['min'];
            if (!empty($v['max'])) $fieldRules[] = 'max:' . $v['max'];
            if (!empty($v['min_length'])) $fieldRules[] = 'min:' . $v['min_length'];
            if (!empty($v['max_length'])) $fieldRules[] = 'max:' . $v['max_length'];
            if (!empty($v['regex'])) $fieldRules[] = 'regex:' . $v['regex'];
            if (!empty($v['file_types'])) $fieldRules[] = 'mimes:' . implode(',', $v['file_types']);
            if (!empty($v['max_file_size_kb'])) $fieldRules[] = 'max:' . $v['max_file_size_kb'];

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    public function makeValidator(array $schema, array $data)
    {
        return Validator::make($data, $this->buildValidationRules($schema));
    }
}
