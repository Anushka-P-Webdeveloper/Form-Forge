<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class FormController extends Controller
{
    public function index()
    {
        $forms = Form::latest()->paginate(10);
        return view('forms.index', compact('forms'));
    }

    public function create()
    {
        $form = Form::create([
            'title' => 'Untitled Form',
            'schema' => ['title' => 'Untitled Form', 'fields' => []],
            'status' => 'draft',
        ]);

        return redirect()->route('forms.edit', $form);
    }

    public function edit(Form $form)
    {
        return view('forms.edit', compact('form'));
    }

    public function fill(string $slug)
    {
        $form = Form::where('slug', $slug)->where('status', 'published')->firstOrFail();
        return view('forms.fill', compact('form'));
    }

    public function importPage()
    {
        return view('forms.import');
    }

    public function submissions(Form $form)
    {
        return view('forms.submissions', compact('form'));
    }

    public function destroy(Form $form)
    {
        // submissions and ai_generation_logs both cascade/null-out via FK
        // constraints on forms.id, so no manual cleanup needed here.
        $title = $form->title;
        $form->delete();

        return redirect()->route('forms.index')->with('info', "\"{$title}\" was deleted.");
    }

    /**
     * CSV export of a form's submissions. Streamed so large submission sets
     * don't blow up memory.
     */
    public function exportSubmissions(Form $form)
    {
        // Nothing to export yet — send the user back with a plain message
        // instead of streaming an (effectively broken) CSV or letting a
        // downstream edge case throw a hard error.
        if ($form->submissions()->doesntExist()) {
            return redirect()
                ->route('forms.submissions', $form)
                ->with('info', 'This form has no submissions yet, so there\'s nothing to export.');
        }

        $fields = collect($form->schema['fields'] ?? [])
            ->filter(fn ($field) => is_array($field) && ($field['type'] ?? null) !== 'heading')
            ->values();

        $filename = Str::slug($form->title ?: 'form') . '-submissions.csv';

        $callback = function () use ($form, $fields) {
            $handle = fopen('php://output', 'w');
            $headerLabels = $fields->map(fn ($field) => $field['label'] ?? $field['key'] ?? 'Field')->toArray();
            fputcsv($handle, array_merge(['ID'], $headerLabels, ['Submitted At']));

            $form->submissions()->orderBy('id')->chunk(200, function ($chunk) use ($handle, $fields) {
                foreach ($chunk as $submission) {
                    $row = [$submission->id];
                    foreach ($fields as $field) {
                        $val = $submission->data[$field['key'] ?? ''] ?? '';
                        $row[] = is_array($val) ? implode(', ', $val) : $val;
                    }
                    $row[] = $submission->created_at?->toDateTimeString();
                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
