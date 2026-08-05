<div class="card p-4">
    <h4>Generate a form with AI</h4>
    <p class="text-muted">e.g. "internship application with education history, skills and resume upload"</p>

    <textarea wire:model="prompt" class="form-control mb-2" rows="3"
        placeholder="Describe the form you want..."></textarea>
    <?php $__errorArgs = ['prompt'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger small mb-2"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

    <button wire:click="generate" wire:loading.attr="disabled" class="btn btn-primary">
        <span wire:loading.remove wire:target="generate">Generate Form</span>
        <span wire:loading wire:target="generate">Queuing job...</span>
    </button>

    <?php if($pendingFormId): ?>
        <div class="mt-3" wire:poll.2s="checkStatus">
            <?php if($pendingStatus === 'generating'): ?>
                <div class="alert alert-info mb-0">⏳ Generating your form... this page updates automatically.</div>
            <?php elseif($pendingStatus === 'draft'): ?>
                <div class="alert alert-success mb-0">
                    ✅ Done!
                    <a href="<?php echo e(route('forms.edit', $pendingFormId)); ?>">Open the form to review and edit it</a>.
                </div>
            <?php elseif($pendingStatus === 'failed'): ?>
                <div class="alert alert-danger mb-0">
                    ⚠️ Generation failed after retries. A blank editable form was created instead —
                    <a href="<?php echo e(route('forms.edit', $pendingFormId)); ?>">open it to build manually</a>.
                    <?php if($pendingError): ?>
                        <div class="small mt-1 font-monospace"><?php echo e($pendingError); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH /Users/anushka/Desktop/Form-Forge/resources/views/livewire/ai-form-generator.blade.php ENDPATH**/ ?>