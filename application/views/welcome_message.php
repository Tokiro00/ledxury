<?php 
$isProduction         = 'production' === ENVIRONMENT;
$prefix = $isProduction ? '.min' : '';
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Accesosorios M.A.M.</title>
	<meta name="description" content="Pure Technology Heavy Duty">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
  	<link rel="stylesheet" href="<?php echo get_public_path('main'.$prefix.'.css') ?>"> 
	<link rel="shortcut icon" type="image/png" href="/favicon.ico"/>
</head>
<body class="font-sans antialiased text-gray-900 leading-normal tracking-wider bg-cover" style="background-image:url('<?php echo get_images_path("back.jpg") ?>');">
	<div class="back-overlay">
    
	<div class="w-full container mx-auto p-6">
			
		<div class="w-full flex flex-col sm:flex-row items-center justify-between">
			<a class="flex items-center text-center sm:text-left text-mam-blue-dark no-underline hover:no-underline font-bold text-2xl lg:text-4xl"  href="#"> 
				Multi Accesorios Medellín
			</a>
			
			<div class="flex flex-col sm:flex-row justify-center sm:justify-end w-1/2 content-center mt-8 sm:mt-0 text-center">		
				<div >
					<a href="https://www.instagram.com/accesoriosmam/" class="inline-block text-mam-blue-dark no-underline hover:text-indigo-800 hover:text-underline text-center h-10 p-2 md:h-auto md:p-4">
						<svg class="fill-current h-6" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 551.034 551.034" xml:space="preserve"><path class="fill-current h-6" d="M386.878,0H164.156C73.64,0,0,73.64,0,164.156v222.722 c0,90.516,73.64,164.156,164.156,164.156h222.722c90.516,0,164.156-73.64,164.156-164.156V164.156 C551.033,73.64,477.393,0,386.878,0z M495.6,386.878c0,60.045-48.677,108.722-108.722,108.722H164.156 c-60.045,0-108.722-48.677-108.722-108.722V164.156c0-60.046,48.677-108.722,108.722-108.722h222.722 c60.045,0,108.722,48.676,108.722,108.722L495.6,386.878L495.6,386.878z"/><path class="fill-current h-6" d="M275.517,133C196.933,133,133,196.933,133,275.516 s63.933,142.517,142.517,142.517S418.034,354.1,418.034,275.516S354.101,133,275.517,133z M275.517,362.6 c-48.095,0-87.083-38.988-87.083-87.083s38.989-87.083,87.083-87.083c48.095,0,87.083,38.988,87.083,87.083 C362.6,323.611,323.611,362.6,275.517,362.6z"/><circle class="fill-current h-6" cx="418.306" cy="134.072" r="34.149"/></svg>
					</a>
					<a href="https://www.instagram.com/accesoriosmam/" class="inline-block text-mam-blue-dark no-underline hover:text-indigo-800 hover:text-underline text-center h-10 p-2 md:h-auto md:p-4 " data-tippy-content="#facebook_id" href="https://www.facebook.com/Accesorios-MAM-106438461132832">
						<svg class="fill-current h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path d="M19 6h5V0h-5c-3.86 0-7 3.14-7 7v3H8v6h4v16h6V16h5l1-6h-6V7c0-.542.458-1 1-1z"></path></svg>
					</a>
				</div>
				<a href="<?= base_url() ?>sisvent/login" class="button-main ml-0 sm:ml-4">INGRESAR</a>
			</div>
			
		</div>

	</div>

	<!--Main-->
	<div class="container pt-8 sm:pt-24 px-6 mx-auto flex flex-wrap flex-col md:flex-row items-center">
		
			<!--Right Col-->
		<div class="w-full xl:w-3/5 py-0 sm:py-6 overflow-y-hidden mx-auto">
			<img class="w-5/6 mx-auto slide-in-bottom" src="<?php echo get_images_path('svg/logo-mam-1.png') ?>">
		</div>
		
		<!--Footer-->
		<div class="w-full pt-4 sm:pt-16 pb-6 text-sm text-center md:text-left fade-in">
			&copy; M.A.M. <?php echo date('Y'); ?> Todos los derechos reservados
		</div>
		
	</div>
	</div>

	<script src="<?php echo get_public_path('main'.$prefix.'.js') ?>"></script>
	
</body>
</html>
