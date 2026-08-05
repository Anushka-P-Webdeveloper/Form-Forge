<?php $__env->startSection('title', 'Review Import'); ?>

<?php $__env->startSection('content'); ?>
    <?php
if (! isset($_instance)) {
    $html = \Livewire\Livewire::mount('import-mapper', ['formImport' => $formImport])->html();
} elseif ($_instance->childHasBeenRendered('tU8xNnX')) {
    $componentId = $_instance->getRenderedChildComponentId('tU8xNnX');
    $componentTag = $_instance->getRenderedChildComponentTagName('tU8xNnX');
    $html = \Livewire\Livewire::dummyMount($componentId, $componentTag);
    $_instance->preserveRenderedChild('tU8xNnX');
} else {
    $response = \Livewire\Livewire::mount('import-mapper', ['formImport' => $formImport]);
    $html = $response->html();
    $_instance->logRenderedChild('tU8xNnX', $response->id(), \Livewire\Livewire::getRootElementTagName($html));
}
echo $html;
?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.builder', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/anushka/Desktop/Form-Forge/resources/views/forms/import-review.blade.php ENDPATH**/ ?>