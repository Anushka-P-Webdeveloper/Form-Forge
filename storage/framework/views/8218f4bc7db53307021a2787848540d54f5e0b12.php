<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
	
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>


	<!-- Meta data -->
	<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>" />

	<meta charset="UTF-8">
	<meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<!-- Favicon -->

	<!-- Title -->
	<title><?php echo e($title); ?></title>

	<?php echo $__env->make('includes.css', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<style>
.app-content .side-app {
    padding: 20px 30px 0 30px !important;
}	
</style>
</head>


<?php echo $__env->make('includes.navigation', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->yieldContent('content'); ?>

<!--Footer-->
<footer class="footer">
	<div class="container">
		<div class="row align-items-center flex-row-reverse">
					<div class="col-lg-12 col-sm-12 mt-3 mt-lg-0 text-center">
				Copyright © <?php echo e(date('Y')); ?> <a href="javascript:void(0)" class="fs-14 text-primary">LMS</a>.
				All rights reserved. 
			</div>
		</div>
	</div>
</footer>
<!--/Footer-->
</div>

<!-- Back to top -->
<a href="#top" id="back-to-top"><i class="fa fa-long-arrow-up"></i></a>

<?php echo $__env->make('includes.js', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

</body>

</html><?php /**PATH /Users/anushka/Desktop/Form-Forge/resources/views/layouts/admin.blade.php ENDPATH**/ ?>