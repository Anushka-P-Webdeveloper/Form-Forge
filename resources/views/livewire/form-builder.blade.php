<div class="row">
    <div class="col-md-7">
        <input type="text" wire:model.lazy="title" class="form-control form-control-lg mb-3" placeholder="Form title">

        @if($errorsList)
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errorsList as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif
        @if($successMessage)
            <div class="alert alert-success">{{ $successMessage }}</div>
        @endif

        <div class="mb-3">
            @foreach($fieldTypes as $type)
                <button wire:click="addField('{{ $type }}')" class="btn btn-sm btn-outline-secondary mb-1">
                    + {{ ucfirst($type) }}
                </button>
            @endforeach
        </div>

        @foreach($fields as $index => $field)
            <div class="card mb-2 p-3" wire:key="field-{{ $index }}">
                <div class="d-flex justify-content-between">
                    <strong>{{ $field['type'] }}</strong>
                    <div>
                        <button wire:click="moveUp({{ $index }})" class="btn btn-sm btn-light">↑</button>
                        <button wire:click="moveDown({{ $index }})" class="btn btn-sm btn-light">↓</button>
                        <button wire:click="duplicateField({{ $index }})" class="btn btn-sm btn-light">⧉</button>
                        <button wire:click="removeField({{ $index }})" class="btn btn-sm btn-danger">✕</button>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-6">
                        <label class="small">Label</label>
                        <input type="text" wire:model="fields.{{ $index }}.label" class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label class="small">Key</label>
                        <input type="text" wire:model="fields.{{ $index }}.key" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 mt-2">
                        <label class="small">Placeholder</label>
                        <input type="text" wire:model="fields.{{ $index }}.placeholder" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 mt-2">
                        <label class="small">Section</label>
                        <input type="text" wire:model="fields.{{ $index }}.section" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 mt-2">
                        <label class="small">Help text</label>
                        <input type="text" wire:model="fields.{{ $index }}.help_text" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 mt-2 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" wire:model="fields.{{ $index }}.required" class="form-check-input">
                            <label class="form-check-label small">Required</label>
                        </div>
                    </div>

                    @if(in_array($field['type'], ['dropdown', 'radio', 'checkbox']))
                        <div class="col-12 mt-2">
                            <label class="small">Options (comma separated)</label>
                            <input type="text" class="form-control form-control-sm"
                                value="{{ implode(', ', $field['options'] ?? []) }}"
                                wire:change="$set('fields.{{ $index }}.options', $event.target.value.split(',').map(s => s.trim()))">
                        </div>
                    @endif

                    <div class="col-4 mt-2">
                        <label class="small">Min length</label>
                        <input type="number" wire:model="fields.{{ $index }}.validation.min_length" class="form-control form-control-sm">
                    </div>
                    <div class="col-4 mt-2">
                        <label class="small">Max length</label>
                        <input type="number" wire:model="fields.{{ $index }}.validation.max_length" class="form-control form-control-sm">
                    </div>
                    <div class="col-4 mt-2">
                        <label class="small">Regex</label>
                        <input type="text" wire:model="fields.{{ $index }}.validation.regex" class="form-control form-control-sm">
                    </div>
                </div>
            </div>
        @endforeach

        <div class="d-flex gap-2 mt-3">
            <button wire:click="save" class="btn btn-primary">Save Draft</button>
            <button wire:click="publish" class="btn btn-success">Publish</button>
            <button wire:click="rollback" class="btn btn-outline-secondary">Rollback to previous version</button>
        </div>

        <hr>
        <h5>AI edit</h5>
        <p class="text-muted small">e.g. "add an emergency contact section", "make phone required", "translate labels to Hindi"</p>
        <div class="input-group">
            <input type="text" wire:model="aiInstruction" class="form-control" placeholder="Describe the change..." @if($aiEditPending) disabled @endif>
            <button wire:click="aiEdit" wire:loading.attr="disabled" class="btn btn-outline-primary" @if($aiEditPending) disabled @endif>
                Apply with AI
            </button>
        </div>
        @error('aiInstruction') <div class="text-danger small mt-1">{{ $message }}</div> @enderror

        @if($aiEditPending)
            <div class="mt-2" wire:poll.2s="checkAiEditStatus">
                <div class="alert alert-info mb-0 py-2 d-flex align-items-center gap-2">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Applying your AI edit... this updates automatically, no need to refresh.
                </div>
            </div>
        @endif
    </div>

    <div class="col-md-5">
        <label class="small text-muted">Raw JSON schema (two-way synced with the canvas)</label>
        <textarea wire:model="jsonEditor" rows="28" class="form-control font-monospace small"></textarea>
        <button wire:click="applyJsonEditor" class="btn btn-sm btn-outline-primary mt-2">Apply JSON → Canvas</button>
    </div>
</div>