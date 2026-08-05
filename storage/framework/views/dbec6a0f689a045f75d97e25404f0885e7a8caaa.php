<?php $__env->startSection('title', 'Submissions: ' . $form->title); ?>

<?php $__env->startSection('content'); ?>
    <h4><?php echo e($form->title); ?> — Submissions</h4>
    <?php
if (! isset($_instance)) {
    $html = \Livewire\Livewire::mount('submissions-list', ['form' => $form])->html();
} elseif ($_instance->childHasBeenRendered('20tFPQB')) {
    $componentId = $_instance->getRenderedChildComponentId('20tFPQB');
    $componentTag = $_instance->getRenderedChildComponentTagName('20tFPQB');
    $html = \Livewire\Livewire::dummyMount($componentId, $componentTag);
    $_instance->preserveRenderedChild('20tFPQB');
} else {
    $response = \Livewire\Livewire::mount('submissions-list', ['form' => $form]);
    $html = $response->html();
    $_instance->logRenderedChild('20tFPQB', $response->id(), \Livewire\Livewire::getRootElementTagName($html));
}
echo $html;
?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.builder', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/anushka/Desktop/Form-Forge/resources/views/forms/submissions.blade.php ENDPATH**/ ?>