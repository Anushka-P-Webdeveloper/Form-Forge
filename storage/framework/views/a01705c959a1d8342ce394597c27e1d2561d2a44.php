<?php $__env->startSection('title', 'Edit: ' . $form->title); ?>

<?php $__env->startSection('content'); ?>
    <?php
if (! isset($_instance)) {
    $html = \Livewire\Livewire::mount('form-builder', ['form' => $form])->html();
} elseif ($_instance->childHasBeenRendered('PGAitQa')) {
    $componentId = $_instance->getRenderedChildComponentId('PGAitQa');
    $componentTag = $_instance->getRenderedChildComponentTagName('PGAitQa');
    $html = \Livewire\Livewire::dummyMount($componentId, $componentTag);
    $_instance->preserveRenderedChild('PGAitQa');
} else {
    $response = \Livewire\Livewire::mount('form-builder', ['form' => $form]);
    $html = $response->html();
    $_instance->logRenderedChild('PGAitQa', $response->id(), \Livewire\Livewire::getRootElementTagName($html));
}
echo $html;
?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.builder', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/anushka/Desktop/Form-Forge/resources/views/forms/edit.blade.php ENDPATH**/ ?>