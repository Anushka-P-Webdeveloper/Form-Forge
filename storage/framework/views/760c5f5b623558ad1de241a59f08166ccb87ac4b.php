<div>
    <div class="d-flex justify-content-between mb-3">
        <input type="text" wire:model.debounce.400ms="search" class="form-control w-25" placeholder="Search submissions...">
        <a href="<?php echo e(route('forms.submissions.export', $form->id)); ?>" class="btn btn-outline-secondary">Export CSV</a>
    </div>

    <table class="table table-sm">
        <thead>
            <tr>
                <th>#</th>
                <?php $__currentLoopData = $form->schema['fields'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($field['type'] !== 'heading'): ?><th><?php echo e($field['label']); ?></th><?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <th>Submitted</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $submissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $submission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($submission->id); ?></td>
                    <?php $__currentLoopData = $form->schema['fields'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($field['type'] !== 'heading'): ?>
                            <td>
                                <?php $val = $submission->data[$field['key']] ?? ''; ?>
                                <?php echo e(is_array($val) ? implode(', ', $val) : $val); ?>

                            </td>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <td><?php echo e($submission->created_at->diffForHumans()); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <?php echo e($submissions->links()); ?>

</div>
<?php /**PATH /Users/anushka/Desktop/Form-Forge/resources/views/livewire/submissions-list.blade.php ENDPATH**/ ?>