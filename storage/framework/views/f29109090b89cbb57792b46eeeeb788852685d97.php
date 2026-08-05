<?php $__env->startSection('title', $form->title); ?>

<?php $__env->startSection('content'); ?>
    <?php
if (! isset($_instance)) {
    $html = \Livewire\Livewire::mount('public-form-fill', ['form' => $form])->html();
} elseif ($_instance->childHasBeenRendered('B4tGGzZ')) {
    $componentId = $_instance->getRenderedChildComponentId('B4tGGzZ');
    $componentTag = $_instance->getRenderedChildComponentTagName('B4tGGzZ');
    $html = \Livewire\Livewire::dummyMount($componentId, $componentTag);
    $_instance->preserveRenderedChild('B4tGGzZ');
} else {
    $response = \Livewire\Livewire::mount('public-form-fill', ['form' => $form]);
    $html = $response->html();
    $_instance->logRenderedChild('B4tGGzZ', $response->id(), \Livewire\Livewire::getRootElementTagName($html));
}
echo $html;
?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.builder', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/anushka/Desktop/Form-Forge/resources/views/forms/fill.blade.php ENDPATH**/ ?>