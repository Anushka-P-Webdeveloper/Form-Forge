<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
    }

    protected function systemPrompt(): string
    {
        return <<<PROMPT
You are a form-schema generator for a form builder product. You must respond with
ONLY raw JSON, no markdown fences, no prose, matching exactly this shape:

{
  "title": "string",
  "fields": [
    {
      "key": "snake_case_unique_key",
      "type": "one of: text, textarea, number, email, phone, date, dropdown, radio, checkbox, file, heading, rating",
      "label": "Human readable label",
      "placeholder": "string or null",
      "help_text": "string or null",
      "default": null,
      "required": true,
      "options": ["only for dropdown/radio/checkbox"],
      "validation": { "min": null, "max": null, "min_length": null, "max_length": null, "regex": null, "file_types": null, "max_file_size_kb": null },
      "section": "string grouping related fields"
    }
  ]
}

Rules:
- Use only the listed field types. Never invent a type.
- Every field key must be unique, snake_case, no spaces.
- Add sensible validation (e.g. email fields get an email-like key, resume uploads get file_types).
- Group related fields under the same "section" name.
- Output ONLY the JSON object. No commentary, no code fences.
PROMPT;
    }

    /**
     * Generate a brand-new schema from a free-text prompt.
     * Returns ['schema' => array|null, 'raw' => string, 'usage' => array, 'latency_ms' => int]
     */
    public function generateForm(string $prompt): array
    {
        return $this->call(
            $this->systemPrompt() . "\n\nUser request: " . $prompt
        );
    }

    /**
     * Apply a natural-language edit instruction to an existing schema.
     */
    public function editForm(array $existingSchema, string $instruction): array
    {
        $context = "Existing form schema (JSON):\n" . json_encode($existingSchema)
            . "\n\nApply this edit instruction and return the FULL updated schema in the same JSON shape: "
            . $instruction;

        return $this->call($this->systemPrompt() . "\n\n" . $context);
    }

    protected function call(string $fullPrompt): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('GEMINI_API_KEY is not set in .env');
        }

        $start = microtime(true);

        $response = Http::timeout(60)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
            [
                'contents' => [
                    ['parts' => [['text' => $fullPrompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'responseMimeType' => 'application/json',
                ],
            ]
        );

        $latencyMs = (int) round((microtime(true) - $start) * 1000);
        $body = $response->json();

        // Surface the real reason for a failure instead of silently returning an
        // empty string and letting the caller guess "unparseable JSON" three times
        // in a row. The most common cause here is a retired/typo'd model string
        // (Google returns a 404 with a message like "model is not found").
        if ($response->failed()) {
            $apiError = $body['error']['message'] ?? $response->body();
            Log::warning('Gemini API call failed', [
                'model' => $this->model,
                'status' => $response->status(),
                'error' => $apiError,
            ]);

            return [
                'schema' => null,
                'raw' => '',
                'prompt_tokens' => null,
                'completion_tokens' => null,
                'latency_ms' => $latencyMs,
                'model' => $this->model,
                'http_ok' => false,
                'api_error' => "Gemini API error ({$response->status()}): {$apiError}",
            ];
        }

        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $usage = $body['usageMetadata'] ?? [];

        $schema = $this->extractJson($text);

        return [
            'schema' => $schema,
            'raw' => $text,
            'prompt_tokens' => $usage['promptTokenCount'] ?? null,
            'completion_tokens' => $usage['candidatesTokenCount'] ?? null,
            'latency_ms' => $latencyMs,
            'model' => $this->model,
            'http_ok' => true,
            'api_error' => $schema === null ? 'Model returned a response but no valid JSON could be extracted from it.' : null,
        ];
    }

    /**
     * Defensive JSON extraction: strips markdown fences if the model adds them
     * anyway, and returns null (not an exception) on unparseable output so the
     * caller can decide to retry/repair rather than crash the job.
     */
    protected function extractJson(string $text): ?array
    {
        $text = trim($text);
        $text = preg_replace('/^```json|```$/m', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to salvage the largest {...} block in the text.
            if (preg_match('/\{.*\}/s', $text, $matches)) {
                $decoded = json_decode($matches[0], true);
            }
        }

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
