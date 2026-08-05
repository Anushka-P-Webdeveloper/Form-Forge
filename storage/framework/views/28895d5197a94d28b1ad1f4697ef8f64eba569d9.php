<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $__env->yieldContent('title', 'Form Builder'); ?></title>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <?php echo \Livewire\Livewire::styles(); ?>

</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="<?php echo e(route('forms.index')); ?>">AI Form Builder</a>
        </div>
    </nav>

    <div class="container pb-5">
        <?php echo $__env->yieldContent('content'); ?>
    </div>

    <?php echo \Livewire\Livewire::scripts(); ?>

</body>
</html>
<?php /**PATH /Users/anushka/Desktop/Form-Forge/resources/views/layouts/builder.blade.php ENDPATH**/ ?>