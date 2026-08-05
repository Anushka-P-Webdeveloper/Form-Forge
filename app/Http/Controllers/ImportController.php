<?php

namespace App\Http\Controllers;

use App\Jobs\ParseFormImportJob;
use App\Models\Form;
use App\Models\FormImport;
use App\Services\FormSchemaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    /**
     * Accept a .docx/.xlsx upload, store it, and queue parsing.
     * Returns JSON so the upload UI can poll status without a full reload.
     */
    public function upload(Request $request)
    {
        // Validate by extension rather than Laravel's `mimes` rule: docx/xlsx
        // are zip containers and are frequently sniffed as application/zip or
        // application/octet-stream depending on OS/browser, which makes the
        // MIME-based rule reject perfectly valid files.
        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['docx', 'xlsx'], true)) {
            return response()->json(['message' => 'Only .docx and .xlsx files are supported.'], 422);
        }
        $storedPath = $file->store('imports');

        $import = FormImport::create([
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'source_type' => $extension,
            'status' => 'pending',
        ]);

        ParseFormImportJob::dispatch($import->id);

        return response()->json([
            'id' => $import->id,
            'status_url' => route('imports.status', $import),
            'review_url' => route('imports.review', $import),
        ]);
    }

    /**
     * Lightweight polling endpoint the upload page hits every couple of
     * seconds while the queued job runs.
     */
    public function status(FormImport $formImport)
    {
        return response()->json([
            'status' => $formImport->status,
            'error' => $formImport->error,
            'review_url' => $formImport->status === 'needs_review' ? route('imports.review', $formImport) : null,
        ]);
    }

    /**
     * Preview/mapping screen — required by the brief before anything is
     * committed, so the user can fix a wrongly detected field type.
     */
    public function review(FormImport $formImport)
    {
        if ($formImport->status === 'committed' && $formImport->form_id) {
            return redirect()->route('forms.edit', $formImport->form_id);
        }

        return view('forms.import-review', compact('formImport'));
    }

    /**
     * Commit the (possibly user-edited) schema from the mapping screen into
     * a real, editable Form — same validation path as manual save and AI
     * generation, so nothing broken ever gets persisted.
     */
    public function commit(Request $request, FormImport $formImport, FormSchemaService $schemaService)
    {
        $data = $request->validate([
            'schema' => ['required', 'array'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $schema = $data['schema'];
        $schema['title'] = $data['title'] ?? $schema['title'] ?? $formImport->original_filename;

        [$valid, $errors] = $schemaService->validate($schema);
        if (!$valid) {
            return back()->withErrors(['schema' => $errors])->withInput();
        }

        $form = Form::create([
            'title' => $schema['title'],
            'schema' => $schema,
            'status' => 'draft',
            'ai_generated' => false,
        ]);

        $formImport->update(['status' => 'committed', 'form_id' => $form->id]);

        return redirect()->route('forms.edit', $form)
            ->with('info', "Imported \"{$form->title}\" from {$formImport->original_filename}. Review the fields before publishing.");
    }
}
