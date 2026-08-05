<div class="card p-4">
    <h4>Generate a form with AI</h4>
    <p class="text-muted">e.g. "internship application with education history, skills and resume upload"</p>

    <textarea wire:model="prompt" class="form-control mb-2" rows="3"
        placeholder="Describe the form you want..."></textarea>
    @error('prompt') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

    <button wire:click="generate" wire:loading.attr="disabled" class="btn btn-primary">
        <span wire:loading.remove wire:target="generate">Generate Form</span>
        <span wire:loading wire:target="generate">Queuing job...</span>
    </button>

    @if($pendingFormId)
        <div class="mt-3" wire:poll.2s="checkStatus">
            @if($pendingStatus === 'generating')
                <div class="alert alert-info mb-0">⏳ Generating your form... this page updates automatically.</div>
            @elseif($pendingStatus === 'draft')
                <div class="alert alert-success mb-0">
                    ✅ Done!
                    <a href="{{ route('forms.edit', $pendingFormId) }}">Open the form to review and edit it</a>.
                </div>
            @elseif($pendingStatus === 'failed')
                <div class="alert alert-danger mb-0">
                    ⚠️ Generation failed after retries. A blank editable form was created instead —
                    <a href="{{ route('forms.edit', $pendingFormId) }}">open it to build manually</a>.
                    @if($pendingError)
                        <div class="small mt-1 font-monospace">{{ $pendingError }}</div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
