<?php $__env->startSection('title', 'My Forms'); ?>

<?php $__env->startSection('content'); ?>
    <?php if(session('info')): ?>
        <div class="alert alert-info"><?php echo e(session('info')); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <?php
if (! isset($_instance)) {
    $html = \Livewire\Livewire::mount('ai-form-generator')->html();
} elseif ($_instance->childHasBeenRendered('qklOOab')) {
    $componentId = $_instance->getRenderedChildComponentId('qklOOab');
    $componentTag = $_instance->getRenderedChildComponentTagName('qklOOab');
    $html = \Livewire\Livewire::dummyMount($componentId, $componentTag);
    $_instance->preserveRenderedChild('qklOOab');
} else {
    $response = \Livewire\Livewire::mount('ai-form-generator');
    $html = $response->html();
    $_instance->logRenderedChild('qklOOab', $response->id(), \Livewire\Livewire::getRootElementTagName($html));
}
echo $html;
?>
        </div>
        <div class="col-md-7">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">My Forms</h4>
                <form method="POST" action="<?php echo e(route('forms.create')); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="btn btn-outline-primary">+ New blank form</button>
                </form>
            </div>

            <table class="table bg-white">
                <thead><tr><th>Title</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php $__currentLoopData = $forms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $form): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($form->title); ?> <?php if($form->ai_generated): ?><span class="badge bg-info">AI</span><?php endif; ?></td>
                        <td><span class="badge bg-secondary"><?php echo e($form->status); ?></span></td>
                        <td>
                            <a href="<?php echo e(route('forms.edit', $form)); ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <a href="<?php echo e(route('forms.submissions', $form)); ?>" class="btn btn-sm btn-outline-secondary">Submissions</a>
                            <?php if($form->status === 'published'): ?>
                                <a href="<?php echo e(route('forms.fill', $form->slug)); ?>" target="_blank" class="btn btn-sm btn-outline-success">Public link</a>
                            <?php endif; ?>
                            <form method="POST" action="<?php echo e(route('forms.destroy', $form)); ?>" class="d-inline"
                                onsubmit="return confirm('Delete &quot;<?php echo e($form->title); ?>&quot;? This also deletes its submissions. This can\'t be undone.');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
            <?php echo e($forms->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.builder', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/anushka/Desktop/Form-Forge/resources/views/forms/index.blade.php ENDPATH**/ ?>