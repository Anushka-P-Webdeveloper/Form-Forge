<?php

namespace App\Jobs;

use App\Models\AiGenerationLog;
use App\Models\Form;
use App\Services\FormSchemaService;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EditFormWithAiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public int $formId,
        public string $instruction
    ) {}

    public function handle(GeminiService $gemini, FormSchemaService $schemaService): void
    {
        $form = Form::find($this->formId);
        if (!$form) {
            return;
        }

        $previousStatus = $form->status === 'generating' ? 'draft' : $form->status;
        $form->update(['status' => 'generating']);

        $result = $gemini->editForm($form->schema, $this->instruction);
        $candidate = $result['schema'];
        $status = 'failed';
        $error = null;

        if ($candidate) {
            [$valid, $errors] = $schemaService->validate($candidate);
            if (!$valid) {
                $candidate = $schemaService->repair($candidate);
                [$valid, $errors] = $schemaService->validate($candidate);
            }

            if ($valid) {
                $form->updateSchema($candidate); // keeps previous_schema for rollback
                $status = 'success';
            } else {
                $error = 'Edited schema failed validation: ' . implode('; ', $errors);
            }
        } else {
            $error = $result['api_error'] ?? 'Could not parse JSON from model output';
        }

        $form->update(['status' => $status === 'success' ? 'draft' : $previousStatus]);

        AiGenerationLog::create([
            'form_id' => $form->id,
            'type' => 'edit',
            'prompt' => $this->instruction,
            'model' => $result['model'] ?? 'unknown',
            'prompt_tokens' => $result['prompt_tokens'] ?? null,
            'completion_tokens' => $result['completion_tokens'] ?? null,
            'latency_ms' => $result['latency_ms'] ?? null,
            'attempt' => 1,
            'status' => $status,
            'error' => $error,
        ]);
    }
}
