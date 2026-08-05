<div wire:poll.2s="checkStatus">
    @if(in_array($status, ['pending', 'processing']))
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <p class="text-muted">Parsing your file… this page updates automatically.</p>
        </div>

    @elseif($status === 'failed')
        <div class="alert alert-danger">
            <strong>Import failed.</strong> {{ $error }}
        </div>
        <a href="{{ route('forms.import') }}" class="btn btn-outline-secondary">Try another file</a>

    @else
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Review &amp; map fields</h4>
                <p class="text-muted mb-0">Fix any wrongly detected type, required flag, or options before saving. Nothing is created until you confirm.</p>
            </div>
            <a href="{{ route('forms.import') }}" class="btn btn-outline-secondary btn-sm">Start over</a>
        </div>

        @if($errors->has('schema'))
            <div class="alert alert-danger">{{ $errors->first('schema') }}</div>
        @endif

        @if(!empty($warnings))
            <div class="alert alert-warning">
                <strong>{{ count($warnings) }} block(s) couldn't be parsed automatically:</strong>
                <ul class="mb-0 mt-1">
                    @foreach($warnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-3">
            <label class="form-label">Form Title</label>
            <input type="text" class="form-control" wire:model="title">
        </div>

        <div class="card mb-3">
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 22%">Label</th>
                            <th style="width: 15%">Type</th>
                            <th style="width: 30%">Options (dropdown/radio/checkbox)</th>
                            <th style="width: 8%">Required</th>
                            <th style="width: 15%">Source</th>
                            <th style="width: 10%"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($fields as $index => $field)
                        <tr>
                            <td>
                                <input type="text" class="form-control form-control-sm" wire:model="fields.{{ $index }}.label">
                            </td>
                            <td>
                                <select class="form-select form-select-sm" wire:model="fields.{{ $index }}.type">
                                    @foreach($fieldTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                @if(in_array($field['type'], ['dropdown', 'radio', 'checkbox']))
                                    @foreach(($field['options'] ?? []) as $optIndex => $option)
                                        <div class="input-group input-group-sm mb-1">
                                            <input type="text" class="form-control" wire:model="fields.{{ $index }}.options.{{ $optIndex }}">
                                            <button class="btn btn-outline-danger" type="button" wire:click="removeOption({{ $index }}, {{ $optIndex }})">&times;</button>
                                        </div>
                                    @endforeach
                                    <button class="btn btn-sm btn-outline-secondary" type="button" wire:click="addOption({{ $index }})">+ Option</button>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" wire:model="fields.{{ $index }}.required">
                            </td>
                            <td>
                                @php $meta = $fieldMeta[$field['key']] ?? 'deterministic'; @endphp
                                @if($meta === 'ai')
                                    <span class="badge bg-info">AI-inferred</span>
                                @elseif($meta === 'ambiguous')
                                    <span class="badge bg-warning text-dark">Guessed — check me</span>
                                @else
                                    <span class="badge bg-light text-dark border">Detected</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" type="button" wire:click="removeField({{ $index }})">Remove</button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <button class="btn btn-primary" wire:click="commit" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="commit">Create Form from Import</span>
            <span wire:loading wire:target="commit">Saving…</span>
        </button>
    @endif
</div>
