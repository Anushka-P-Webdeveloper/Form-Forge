<?php

namespace App\Http\Livewire;

use App\Jobs\EditFormWithAiJob;
use App\Models\Form;
use App\Services\FormSchemaService;
use Livewire\Component;

class FormBuilder extends Component
{
    public Form $form;
    public array $fields = [];
    public string $title = '';
    public string $jsonEditor = '';
    public string $aiInstruction = '';
    public array $errorsList = [];
    public string $successMessage = '';
    public bool $aiEditPending = false;

    public function mount(Form $form)
    {
        $this->form = $form;
        $this->title = $form->title;
        $this->fields = $form->schema['fields'] ?? [];
        $this->syncJsonFromFields();
    }

    protected function currentSchema(): array
    {
        return ['title' => $this->title, 'fields' => $this->fields];
    }

    protected function syncJsonFromFields(): void
    {
        $this->jsonEditor = json_encode($this->currentSchema(), JSON_PRETTY_PRINT);
    }

    // --- Canvas actions -----------------------------------------------

    public function addField(string $type = 'text')
    {
        $this->fields[] = [
            'key' => 'field_' . (count($this->fields) + 1) . '_' . substr(md5((string) microtime()), 0, 4),
            'type' => $type,
            'label' => 'New Field',
            'placeholder' => null,
            'help_text' => null,
            'default' => null,
            'required' => false,
            'options' => in_array($type, ['dropdown', 'radio', 'checkbox'], true) ? ['Option 1', 'Option 2'] : [],
            'validation' => [],
            'section' => 'General',
        ];
        $this->syncJsonFromFields();
    }

    public function removeField(int $index)
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
        $this->syncJsonFromFields();
    }

    public function duplicateField(int $index)
    {
        $copy = $this->fields[$index];
        $copy['key'] .= '_copy';
        array_splice($this->fields, $index + 1, 0, [$copy]);
        $this->syncJsonFromFields();
    }

    // Simplified reordering (up/down) in place of full drag & drop for today's scope.
    public function moveUp(int $index)
    {
        if ($index <= 0) return;
        [$this->fields[$index - 1], $this->fields[$index]] = [$this->fields[$index], $this->fields[$index - 1]];
        $this->syncJsonFromFields();
    }

    public function moveDown(int $index)
    {
        if ($index >= count($this->fields) - 1) return;
        [$this->fields[$index + 1], $this->fields[$index]] = [$this->fields[$index], $this->fields[$index + 1]];
        $this->syncJsonFromFields();
    }

    public function updated($name)
    {
        // Any field-level edit on the canvas re-syncs the raw JSON view.
        if (str_starts_with($name, 'fields.') || $name === 'title') {
            $this->syncJsonFromFields();
        }
    }

    // --- Raw JSON editor -> canvas -------------------------------------

    public function applyJsonEditor(FormSchemaService $schemaService)
    {
        $decoded = json_decode($this->jsonEditor, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errorsList = ['Invalid JSON: ' . json_last_error_msg()];
            return;
        }

        [$valid, $errors] = $schemaService->validate($decoded);
        if (!$valid) {
            $this->errorsList = $errors;
            return;
        }

        $this->errorsList = [];
        $this->title = $decoded['title'] ?? $this->title;
        $this->fields = $decoded['fields'];
    }

    // --- Save ------------------------------------------------------------

    public function save(FormSchemaService $schemaService)
    {
        $schema = $this->currentSchema();
        [$valid, $errors] = $schemaService->validate($schema);

        if (!$valid) {
            $this->errorsList = $errors;
            return;
        }

        $this->errorsList = [];
        $this->form->title = $this->title;
        $this->form->updateSchema($schema);
        $this->successMessage = 'Saved at ' . now()->format('H:i:s');
    }

    public function publish(FormSchemaService $schemaService)
    {
        $this->save($schemaService);
        if (empty($this->errorsList)) {
            $this->form->update(['status' => 'published']);
            $this->successMessage = 'Published! Public URL: ' . route('forms.fill', $this->form->slug);
        }
    }

    public function rollback()
    {
        if ($this->form->rollbackSchema()) {
            $this->fields = $this->form->schema['fields'] ?? [];
            $this->title = $this->form->schema['title'] ?? $this->title;
            $this->syncJsonFromFields();
            $this->successMessage = 'Rolled back to previous version.';
        }
    }

    // --- AI edit of existing form ----------------------------------------

    public function aiEdit()
    {
        $this->validate(['aiInstruction' => 'required|string|min:5|max:500']);

        // Save current manual edits first so the AI edits the latest version.
        app(FormSchemaService::class);
        $this->form->updateSchema($this->currentSchema());

        EditFormWithAiJob::dispatch($this->form->id, $this->aiInstruction);
        $this->aiInstruction = '';
        $this->successMessage = '';
        $this->aiEditPending = true;
    }

    // Called on wire:poll while an AI edit job is in flight (same pattern as
    // AiFormGenerator::checkStatus). Once the job flips the form's status back
    // off "generating", pull the fresh schema straight into the canvas —
    // no manual page reload, so nothing the user typed elsewhere is lost.
    public function checkAiEditStatus()
    {
        if (!$this->aiEditPending) {
            return;
        }

        $fresh = Form::find($this->form->id);
        if (!$fresh || $fresh->status === 'generating') {
            return; // still working
        }

        $this->form = $fresh;
        $this->fields = $fresh->schema['fields'] ?? [];
        $this->title = $fresh->schema['title'] ?? $fresh->title;
        $this->syncJsonFromFields();
        $this->aiEditPending = false;

        $log = $fresh->aiLogs()->where('type', 'edit')->latest()->first();
        if ($log && $log->status === 'failed') {
            $this->errorsList = [$log->error ?? 'AI edit failed — the form was left unchanged.'];
        } else {
            $this->successMessage = 'AI edit applied.';
        }
    }

    public function render()
    {
        return view('livewire.form-builder', [
            'fieldTypes' => FormSchemaService::FIELD_TYPES,
        ]);
    }
}