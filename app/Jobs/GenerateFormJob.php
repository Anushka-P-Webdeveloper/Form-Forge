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

class GenerateFormJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // we do our own retry loop below against the LLM, not the queue

    public function __construct(
        public int $formId,
        public string $prompt
    ) {}

    public function handle(GeminiService $gemini, FormSchemaService $schemaService): void
    {
        $form = Form::find($this->formId);
        if (!$form) {
            return;
        }

        $maxAttempts = 3;
        $schema = null;
        $lastResult = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $lastResult = $gemini->generateForm($this->buildPromptForAttempt($attempt));

            $candidate = $lastResult['schema'];

            if ($candidate) {
                [$valid, $errors] = $schemaService->validate($candidate);

                if (!$valid) {
                    // Try a deterministic repair before burning another AI call.
                    $repaired = $schemaService->repair($candidate);
                    [$valid, $errors] = $schemaService->validate($repaired);
                    if ($valid) {
                        $schema = $repaired;
                        $this->log($form, $attempt, $lastResult, 'repaired');
                        break;
                    }
                } else {
                    $schema = $candidate;
                    $this->log($form, $attempt, $lastResult, 'success');
                    break;
                }
            }

            $this->log($form, $attempt, $lastResult, 'failed',
                $candidate ? 'Schema failed validation' : ($lastResult['api_error'] ?? 'Could not parse JSON from model output'));
        }

        if ($schema) {
            $form->update([
                'title' => $schema['title'] ?? $form->title,
                'schema' => $schema,
                'status' => 'draft',
                'ai_generated' => true,
            ]);
        } else {
            // Never persist a broken schema. Fall back to a minimal, valid,
            // empty form so the user has something editable rather than nothing.
            $form->update([
                'schema' => ['title' => $form->title, 'fields' => []],
                'status' => 'failed',
            ]);
        }
    }

    protected function buildPromptForAttempt(int $attempt): string
    {
        if ($attempt === 1) {
            return $this->prompt;
        }

        return $this->prompt . "\n\n(Previous attempt returned invalid/unparseable JSON. Return ONLY valid JSON, strictly matching the required shape, no markdown fences.)";
    }

    protected function log(Form $form, int $attempt, array $result, string $status, ?string $error = null): void
    {
        AiGenerationLog::create([
            'form_id' => $form->id,
            'type' => 'generate',
            'prompt' => $this->prompt,
            'model' => $result['model'] ?? 'unknown',
            'prompt_tokens' => $result['prompt_tokens'] ?? null,
            'completion_tokens' => $result['completion_tokens'] ?? null,
            'latency_ms' => $result['latency_ms'] ?? null,
            'attempt' => $attempt,
            'status' => $status,
            'error' => $error,
        ]);
    }
}
