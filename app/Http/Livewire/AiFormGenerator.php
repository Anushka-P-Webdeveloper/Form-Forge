<?php

namespace App\Http\Livewire;

use App\Jobs\GenerateFormJob;
use App\Models\Form;
use Livewire\Component;

class AiFormGenerator extends Component
{
    public string $prompt = '';
    public ?int $pendingFormId = null;
    public ?string $pendingStatus = null;
    public ?string $pendingError = null;

    protected $rules = [
        'prompt' => 'required|string|min:10|max:2000',
    ];

    public function generate()
    {
        $this->validate();

        // Create a placeholder form immediately so the UI has something to
        // poll against; the actual LLM call happens in the queued job so
        // this request returns instantly instead of blocking on the API call.
        $form = Form::create([
            'title' => 'Generating…',
            'schema' => ['title' => 'Generating…', 'fields' => []],
            'status' => 'generating',
        ]);

        GenerateFormJob::dispatch($form->id, $this->prompt);

        $this->pendingFormId = $form->id;
        $this->pendingStatus = 'generating';
    }

    // Called on wire:poll while a generation job is in flight.
    public function checkStatus()
    {
        if (!$this->pendingFormId) {
            return;
        }

        $form = Form::find($this->pendingFormId);
        $this->pendingStatus = $form?->status;

        if ($this->pendingStatus === 'failed') {
            $this->pendingError = $form->aiLogs()->where('type', 'generate')->latest()->value('error');
        }
    }

    public function render()
    {
        return view('livewire.ai-form-generator');
    }
}
