<div wire:poll.2s="checkStatus">
    <?php if(in_array($status, ['pending', 'processing'])): ?>
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <p class="text-muted">Parsing your file… this page updates automatically.</p>
        </div>

    <?php elseif($status === 'failed'): ?>
        <div class="alert alert-danger">
            <strong>Import failed.</strong> <?php echo e($error); ?>

        </div>
        <a href="<?php echo e(route('forms.import')); ?>" class="btn btn-outline-secondary">Try another file</a>

    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Review &amp; map fields</h4>
                <p class="text-muted mb-0">Fix any wrongly detected type, required flag, or options before saving. Nothing is created until you confirm.</p>
            </div>
            <a href="<?php echo e(route('forms.import')); ?>" class="btn btn-outline-secondary btn-sm">Start over</a>
        </div>

        <?php if($errors->has('schema')): ?>
            <div class="alert alert-danger"><?php echo e($errors->first('schema')); ?></div>
        <?php endif; ?>

        <?php if(!empty($warnings)): ?>
            <div class="alert alert-warning">
                <strong><?php echo e(count($warnings)); ?> block(s) couldn't be parsed automatically:</strong>
                <ul class="mb-0 mt-1">
                    <?php $__currentLoopData = $warnings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warning): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($warning); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

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
                    <?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td>
                                <input type="text" class="form-control form-control-sm" wire:model="fields.<?php echo e($index); ?>.label">
                            </td>
                            <td>
                                <select class="form-select form-select-sm" wire:model="fields.<?php echo e($index); ?>.type">
                                    <?php $__currentLoopData = $fieldTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($type); ?>"><?php echo e($type); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </td>
                            <td>
                                <?php if(in_array($field['type'], ['dropdown', 'radio', 'checkbox'])): ?>
                                    <?php $__currentLoopData = ($field['options'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optIndex => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="input-group input-group-sm mb-1">
                                            <input type="text" class="form-control" wire:model="fields.<?php echo e($index); ?>.options.<?php echo e($optIndex); ?>">
                                            <button class="btn btn-outline-danger" type="button" wire:click="removeOption(<?php echo e($index); ?>, <?php echo e($optIndex); ?>)">&times;</button>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" wire:click="addOption(<?php echo e($index); ?>)">+ Option</button>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" wire:model="fields.<?php echo e($index); ?>.required">
                            </td>
                            <td>
                                <?php $meta = $fieldMeta[$field['key']] ?? 'deterministic'; ?>
                                <?php if($meta === 'ai'): ?>
                                    <span class="badge bg-info">AI-inferred</span>
                                <?php elseif($meta === 'ambiguous'): ?>
                                    <span class="badge bg-warning text-dark">Guessed — check me</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">Detected</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" type="button" wire:click="removeField(<?php echo e($index); ?>)">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>

        <button class="btn btn-primary" wire:click="commit" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="commit">Create Form from Import</span>
            <span wire:loading wire:target="commit">Saving…</span>
        </button>
    <?php endif; ?>
</div>
<?php /**PATH /Users/anushka/Desktop/Form-Forge/resources/views/livewire/import-mapper.blade.php ENDPATH**/ ?>