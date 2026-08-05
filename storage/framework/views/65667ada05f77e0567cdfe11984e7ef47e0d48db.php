<div class="row">
    <div class="col-md-7">
        <input type="text" wire:model.lazy="title" class="form-control form-control-lg mb-3" placeholder="Form title">

        <?php if($errorsList): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php $__currentLoopData = $errorsList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> <li><?php echo e($e); ?></li> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if($successMessage): ?>
            <div class="alert alert-success"><?php echo e($successMessage); ?></div>
        <?php endif; ?>

        <div class="mb-3">
            <?php $__currentLoopData = $fieldTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <button wire:click="addField('<?php echo e($type); ?>')" class="btn btn-sm btn-outline-secondary mb-1">
                    + <?php echo e(ucfirst($type)); ?>

                </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="card mb-2 p-3" wire:key="field-<?php echo e($index); ?>">
                <div class="d-flex justify-content-between">
                    <strong><?php echo e($field['type']); ?></strong>
                    <div>
                        <button wire:click="moveUp(<?php echo e($index); ?>)" class="btn btn-sm btn-light">↑</button>
                        <button wire:click="moveDown(<?php echo e($index); ?>)" class="btn btn-sm btn-light">↓</button>
                        <button wire:click="duplicateField(<?php echo e($index); ?>)" class="btn btn-sm btn-light">⧉</button>
                        <button wire:click="removeField(<?php echo e($index); ?>)" class="btn btn-sm btn-danger">✕</button>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-6">
                        <label class="small">Label</label>
                        <input type="text" wire:model="fields.<?php echo e($index); ?>.label" class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label class="small">Key</label>
                        <input type="text" wire:model="fields.<?php echo e($index); ?>.key" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 mt-2">
                        <label class="small">Placeholder</label>
                        <input type="text" wire:model="fields.<?php echo e($index); ?>.placeholder" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 mt-2">
                        <label class="small">Section</label>
                        <input type="text" wire:model="fields.<?php echo e($index); ?>.section" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 mt-2">
                        <label class="small">Help text</label>
                        <input type="text" wire:model="fields.<?php echo e($index); ?>.help_text" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 mt-2 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" wire:model="fields.<?php echo e($index); ?>.required" class="form-check-input">
                            <label class="form-check-label small">Required</label>
                        </div>
                    </div>

                    <?php if(in_array($field['type'], ['dropdown', 'radio', 'checkbox'])): ?>
                        <div class="col-12 mt-2">
                            <label class="small">Options (comma separated)</label>
                            <input type="text" class="form-control form-control-sm"
                                value="<?php echo e(implode(', ', $field['options'] ?? [])); ?>"
                                wire:change="$set('fields.<?php echo e($index); ?>.options', $event.target.value.split(',').map(s => s.trim()))">
                        </div>
                    <?php endif; ?>

                    <div class="col-4 mt-2">
                        <label class="small">Min length</label>
                        <input type="number" wire:model="fields.<?php echo e($index); ?>.validation.min_length" class="form-control form-control-sm">
                    </div>
                    <div class="col-4 mt-2">
                        <label class="small">Max length</label>
                        <input type="number" wire:model="fields.<?php echo e($index); ?>.validation.max_length" class="form-control form-control-sm">
                    </div>
                    <div class="col-4 mt-2">
                        <label class="small">Regex</label>
                        <input type="text" wire:model="fields.<?php echo e($index); ?>.validation.regex" class="form-control form-control-sm">
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <div class="d-flex gap-2 mt-3">
            <button wire:click="save" class="btn btn-primary">Save Draft</button>
            <button wire:click="publish" class="btn btn-success">Publish</button>
            <button wire:click="rollback" class="btn btn-outline-secondary">Rollback to previous version</button>
        </div>

        <hr>
        <h5>AI edit</h5>
        <p class="text-muted small">e.g. "add an emergency contact section", "make phone required", "translate labels to Hindi"</p>
        <div class="input-group">
            <input type="text" wire:model="aiInstruction" class="form-control" placeholder="Describe the change..." <?php if($aiEditPending): ?> disabled <?php endif; ?>>
            <button wire:click="aiEdit" wire:loading.attr="disabled" class="btn btn-outline-primary" <?php if($aiEditPending): ?> disabled <?php endif; ?>>
                Apply with AI
            </button>
        </div>
        <?php $__errorArgs = ['aiInstruction'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger small mt-1"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

        <?php if($aiEditPending): ?>
            <div class="mt-2" wire:poll.2s="checkAiEditStatus">
                <div class="alert alert-info mb-0 py-2 d-flex align-items-center gap-2">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Applying your AI edit... this updates automatically, no need to refresh.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-5">
        <label class="small text-muted">Raw JSON schema (two-way synced with the canvas)</label>
        <textarea wire:model="jsonEditor" rows="28" class="form-control font-monospace small"></textarea>
        <button wire:click="applyJsonEditor" class="btn btn-sm btn-outline-primary mt-2">Apply JSON → Canvas</button>
    </div>
</div><?php /**PATH /Users/anushka/Desktop/Form-Forge/resources/views/livewire/form-builder.blade.php ENDPATH**/ ?>