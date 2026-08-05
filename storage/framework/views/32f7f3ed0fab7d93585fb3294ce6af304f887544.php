<div class="container py-4" style="max-width:700px;">
    <?php if($submitted): ?>
        <div class="alert alert-success">✅ Thanks! Your response has been recorded.</div>
    <?php else: ?>
        <h3><?php echo e($form->title); ?></h3>
        <?php if($form->description): ?><p class="text-muted"><?php echo e($form->description); ?></p><?php endif; ?>

        <form wire:submit.prevent="submit">
            
            <input type="text" wire:model="website" class="d-none" tabindex="-1" autocomplete="off">

            <?php $__currentLoopData = $form->schema['fields'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="mb-3">
                    <?php if($field['type'] === 'heading'): ?>
                        <h5 class="mt-4"><?php echo e($field['label']); ?></h5>
                        <?php continue; ?>
                    <?php endif; ?>

                    <label class="form-label">
                        <?php echo e($field['label']); ?>

                        <?php if($field['required'] ?? false): ?><span class="text-danger">*</span><?php endif; ?>
                    </label>

                    <?php switch($field['type']):
                        case ('textarea'): ?>
                            <textarea wire:model="data.<?php echo e($field['key']); ?>" class="form-control" placeholder="<?php echo e($field['placeholder']); ?>"></textarea>
                            <?php break; ?>
                        <?php case ('dropdown'): ?>
                            <select wire:model="data.<?php echo e($field['key']); ?>" class="form-select">
                                <option value="">Select...</option>
                                <?php $__currentLoopData = $field['options'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($opt); ?>"><?php echo e($opt); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php break; ?>
                        <?php case ('radio'): ?>
                            <?php $__currentLoopData = $field['options'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="form-check">
                                    <input type="radio" wire:model="data.<?php echo e($field['key']); ?>" value="<?php echo e($opt); ?>" class="form-check-input">
                                    <label class="form-check-label"><?php echo e($opt); ?></label>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php break; ?>
                        <?php case ('checkbox'): ?>
                            <?php $__currentLoopData = $field['options'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="form-check">
                                    <input type="checkbox" wire:model="data.<?php echo e($field['key']); ?>" value="<?php echo e($opt); ?>" class="form-check-input">
                                    <label class="form-check-label"><?php echo e($opt); ?></label>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php break; ?>
                        <?php case ('file'): ?>
                            <input type="file" wire:model="data.<?php echo e($field['key']); ?>" class="form-control">
                            <?php break; ?>
                        <?php case ('date'): ?>
                            <input type="date" wire:model="data.<?php echo e($field['key']); ?>" class="form-control">
                            <?php break; ?>
                        <?php default: ?>
                            <input type="<?php echo e($field['type'] === 'phone' ? 'tel' : $field['type']); ?>"
                                wire:model="data.<?php echo e($field['key']); ?>" class="form-control"
                                placeholder="<?php echo e($field['placeholder']); ?>">
                    <?php endswitch; ?>

                    <?php if($field['help_text'] ?? false): ?><div class="form-text"><?php echo e($field['help_text']); ?></div><?php endif; ?>
                    <?php $__errorArgs = ['data.' . $field['key']];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    <?php endif; ?>
</div>
<?php /**PATH /Users/anushka/Desktop/Form-Forge/resources/views/livewire/public-form-fill.blade.php ENDPATH**/ ?>