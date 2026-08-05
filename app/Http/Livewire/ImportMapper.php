<?php

namespace App\Http\Livewire;

use App\Models\Form;
use App\Models\FormImport;
use App\Services\FormSchemaService;
use Livewire\Component;

/**
 * Part C — mapping/preview screen. Renders the deterministic + AI-assisted
 * schema detected from a .docx/.xlsx upload and lets the user fix a wrongly
 * detected field type (or required flag, or options) before anything is
 * committed as a real Form.
 */
class ImportMapper extends Component
{
    public int $formImportId;
    public string $status;
    public ?string $error = null;
    public array $fields = [];
    public array $fieldMeta = [];
    public array $warnings = [];
    public string $title = '';

    protected FormSchemaService $schemaService;

    public function boot(FormSchemaService $schemaService)
    {
        $this->schemaService = $schemaService;
    }

    public function mount(FormImport $formImport)
    {
        $this->formImportId = $formImport->id;
        $this->hydrateFrom($formImport);
    }

    protected function hydrateFrom(FormImport $formImport): void
    {
        $this->status = $formImport->status;
        $this->error = $formImport->error;
        $this->warnings = $formImport->warnings ?? [];
        $this->fieldMeta = $formImport->field_meta ?? [];
        $this->fields = $formImport->detected_schema['fields'] ?? [];
        $this->title = $formImport->detected_schema['title']
            ?? $formImport->original_filename;
    }

    // wire:poll while the queued job is still running.
    public function checkStatus()
    {
        $formImport = FormImport::find($this->formImportId);
        if ($formImport) {
            $this->hydrateFrom($formImport);
        }
    }

    public function addOption(int $index)
    {
        $this->fields[$index]['options'][] = 'New option';
    }

    public function removeOption(int $index, int $optionIndex)
    {
        unset($this->fields[$index]['options'][$optionIndex]);
        $this->fields[$index]['options'] = array_values($this->fields[$index]['options']);
    }

    public function removeField(int $index)
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    public function updatedFieldsIndexType($value, $index)
    {
        // Ensure options[] exists the moment a field is switched to a choice type.
        if (in_array($value, ['dropdown', 'radio', 'checkbox'], true) && empty($this->fields[$index]['options'])) {
            $this->fields[$index]['options'] = ['Option 1', 'Option 2'];
        }
    }

    public function commit()
    {
        $schema = [
            'title' => $this->title ?: 'Imported Form',
            'fields' => array_values($this->fields),
        ];

        [$valid, $errors] = $this->schemaService->validate($schema);
        if (!$valid) {
            $this->addError('schema', implode(' ', $errors));
            return;
        }

        $form = Form::create([
            'title' => $schema['title'],
            'schema' => $schema,
            'status' => 'draft',
            'ai_generated' => false,
        ]);

        FormImport::where('id', $this->formImportId)->update([
            'status' => 'committed',
            'form_id' => $form->id,
        ]);

        return redirect()->route('forms.edit', $form)
            ->with('info', "Imported \"{$form->title}\". Review the fields before publishing.");
    }

    public function render()
    {
        return view('livewire.import-mapper', [
            'fieldTypes' => FormSchemaService::FIELD_TYPES,
        ]);
    }
}
