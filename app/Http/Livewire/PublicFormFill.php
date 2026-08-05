<?php

namespace App\Http\Livewire;

use App\Models\Form;
use App\Models\Submission;
use App\Services\FormSchemaService;
use Livewire\Component;
use Livewire\WithFileUploads;

class PublicFormFill extends Component
{
    use WithFileUploads;

    public Form $form;
    public array $data = [];
    public bool $submitted = false;

    // Part D: basic spam protection — honeypot field, invisible to real users,
    // plus the throttle:10,1 middleware applied on the route itself.
    public string $website = '';

    public function mount(Form $form)
    {
        $this->form = $form;
        foreach ($form->schema['fields'] ?? [] as $field) {
            $this->data[$field['key']] = $field['type'] === 'checkbox' ? [] : ($field['default'] ?? '');
        }
    }

    public function submit(FormSchemaService $schemaService)
    {
        if (!empty($this->website)) {
            // Honeypot tripped — silently "succeed" without persisting anything.
            $this->submitted = true;
            return;
        }

        // Server-side validation derived from the same schema as the canvas.
        // The browser's HTML5 validation (required, type=email, etc.) is UX
        // only — this is the check that actually matters.
        $validator = $schemaService->makeValidator($this->form->schema, $this->data);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $messages) {
                $this->addError($key, $messages[0]);
            }
            return;
        }

        $stored = $this->data;
        foreach ($stored as $key => $value) {
            if ($value instanceof \Livewire\TemporaryUploadedFile) {
                $stored[$key] = $value->store('submissions/' . $this->form->id, 'public');
            }
        }

        Submission::create([
            'form_id' => $this->form->id,
            'data' => $stored,
            'ip_address' => request()->ip(),
        ]);

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.public-form-fill');
    }
}
