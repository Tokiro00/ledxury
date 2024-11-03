<?php 
$isProduction         = 'production' === ENVIRONMENT;
$prefix = $isProduction ? '.min' : '';
?>
<!DOCTYPE html>
<html :class="{ 'theme-dark': dark }" x-data="data()" lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - M.A.M.</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="<?php echo get_public_path('main'.$prefix.'.css') ?>"> 
    <!--script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script-->
    <script src="<?php echo get_public_path('main'.$prefix.'.js') ?>"></script>
  </head>
<body class="font-sans antialiased text-gray-900 leading-normal tracking-wider bg-cover" style="background-image:url('<?php echo get_images_path("back.jpg") ?>');">
  <div class="back-overlay-soft">
    <div class="flex items-center min-h-screen p-6 bg-gray-50">
      <div
        class="flex-1 h-full max-w-4xl mx-auto overflow-hidden bg-white rounded-lg shadow-xl"
      >
        <div class="flex flex-col overflow-y-auto md:flex-row">
          <a href="<?= base_url() ?>" class="h-32 md:h-auto md:w-1/2">
            <img
              aria-hidden="true"
              class="object-contain w-full h-full"
              src="<?php echo get_images_path('svg/logo-mam-1.png') ?>"
              alt="Office"
            />
          </a>
          <form class="flex items-center justify-center p-6 sm:p-12 md:w-1/2"  action="<?= base_url() ?>sisvent/login/validate" method="post">
            <div class="w-full">
              <h1 class="mb-4 text-xl font-semibold text-gray-700">
                Login <b>Dropshipping</b>
              </h1>
              <?php if($this->session->flashdata("error")):?>
                <div class="text-white px-6 py-4 border-0 rounded relative mb-4 bg-red-500">
                  <span class="inline-block align-middle mr-8">
                    <?php echo $this->session->flashdata("error")?>
                  </span>
                </div>
              <?php endif; ?>
              <label class="block text-sm">
                <span class="text-gray-700">Identificación</span>
                <input class="form-input" name="uid"/>
              </label>
              <label class="block mt-4 text-sm">
                <span class="text-gray-700">Contraseña</span>
                <input class="form-input" type="password" name="ups"/>
              </label>

              <!-- You should use a button here, as the anchor is only used for the example  -->
              <input type="submit" class="block w-full px-4 py-2 mt-4 text-sm font-medium leading-5 text-center text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-600 hover:bg-mam-blue focus:outline-none focus:shadow-outline-gray"
                value="Ingresar" />
             
            </div>
          </form>
        </div>
      </div>
    </div>
    </div>
  </body>
</html>
