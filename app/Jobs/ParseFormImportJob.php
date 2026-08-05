<?php

namespace App\Jobs;

use App\Models\FormImport;
use App\Services\FormSchemaService;
use App\Services\ImportParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Part C — large uploads are queued rather than parsed on the request thread,
 * same pattern as GenerateFormJob for Part B. Never leaves a broken/partial
 * schema behind: on any failure the import is marked 'failed' with a message
 * instead of a half-populated 'needs_review' row.
 */
class ParseFormImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $formImportId)
    {
    }

    public function handle(ImportParserService $parser, FormSchemaService $schemaService): void
    {
        $import = FormImport::find($this->formImportId);
        if (!$import) {
            return;
        }

        $import->update(['status' => 'processing']);

        $absolutePath = Storage::disk('local')->path($import->stored_path);

        try {
            $result = $import->source_type === 'docx'
                ? $parser->parseDocx($absolutePath)
                : $parser->parseXlsx($absolutePath);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error' => 'Could not read this file: ' . $e->getMessage(),
            ]);
            return;
        }

        $schema = $result['schema'];

        // Deterministic keys can collide across sections in a docx (e.g. two
        // "Comments?" lines) — make sure the schema that reaches the mapping
        // screen is always structurally valid before we ever show it.
        [$valid, $errors] = $schemaService->validate($schema);
        if (!$valid) {
            $schema = $schemaService->repair($schema);
        }

        if (empty($schema['fields'])) {
            $import->update([
                'status' => 'failed',
                'error' => 'No fields could be detected in this file. See warnings for details.',
                'warnings' => $result['warnings'],
            ]);
            return;
        }

        $import->update([
            'status' => 'needs_review',
            'detected_schema' => $schema,
            'field_meta' => $result['field_meta'],
            'warnings' => $result['warnings'],
        ]);
    }
}
