<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <input type="text" wire:model.debounce.400ms="search" class="form-control w-25" placeholder="Search submissions...">
        @if($form->submissions()->exists())
            <a href="{{ route('forms.submissions.export', $form->id) }}" class="btn btn-outline-secondary">Export CSV</a>
        @else
            <span class="text-muted small">No submissions yet — nothing to export.</span>
        @endif
    </div>

    <table class="table table-sm">
        <thead>
            <tr>
                <th>#</th>
                @foreach($form->schema['fields'] ?? [] as $field)
                    @if($field['type'] !== 'heading')<th>{{ $field['label'] }}</th>@endif
                @endforeach
                <th>Submitted</th>
            </tr>
        </thead>
        <tbody>
            @foreach($submissions as $submission)
                <tr>
                    <td>{{ $submission->id }}</td>
                    @foreach($form->schema['fields'] ?? [] as $field)
                        @if($field['type'] !== 'heading')
                            <td>
                                @php $val = $submission->data[$field['key']] ?? ''; @endphp
                                {{ is_array($val) ? implode(', ', $val) : $val }}
                            </td>
                        @endif
                    @endforeach
                    <td>{{ $submission->created_at->diffForHumans() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $submissions->links() }}
</div>
