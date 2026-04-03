<?php
$isProduction         = 'production' === ENVIRONMENT;
$prefix = $isProduction ? '.min' : '';
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Ledxury</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="<?php echo get_public_path('main'.$prefix.'.css') ?>">
    <script src="<?php echo get_public_path('main'.$prefix.'.js') ?>"></script>
    <style>
      .login-bg { background: #1a1a2e; }
      .login-card { background: #16213e; }
      .led-glow { text-shadow: 0 0 20px rgba(230,57,70,0.4), 0 0 40px rgba(230,57,70,0.2); }
      .accent-line { background: linear-gradient(90deg, #E63946, #c1121f); }
      .btn-ledxury {
        background: linear-gradient(135deg, #E63946 0%, #c1121f 100%);
        transition: all 0.3s ease;
      }
      .btn-ledxury:hover {
        background: linear-gradient(135deg, #c1121f 0%, #a00d1a 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(230,57,70,0.4);
      }
      .input-dark {
        background: #0f3460;
        border: 1px solid #1a4a7a;
        color: #e0e0e0;
        transition: border-color 0.2s;
      }
      .input-dark:focus {
        border-color: #E63946;
        outline: none;
        box-shadow: 0 0 0 3px rgba(230,57,70,0.2);
      }
      .input-dark::placeholder { color: #6b7fa8; }
    </style>
  </head>
<body class="login-bg font-sans antialiased">
  <div class="flex items-center justify-center min-h-screen px-4">
    <div class="w-full max-w-md">

      <!-- Logo Area -->
      <div class="text-center mb-8">
        <h1 class="text-5xl font-black text-white tracking-tight led-glow" style="font-family: 'Inter', sans-serif;">
          LEDXURY
        </h1>
        <div class="accent-line h-1 w-16 mx-auto mt-3 rounded-full"></div>
        <p class="text-gray-400 text-sm mt-3 tracking-widest uppercase">Luxury</p>
      </div>

      <!-- Login Card -->
      <div class="login-card rounded-2xl shadow-2xl p-8" style="border: 1px solid rgba(230,57,70,0.15);">

        <?php if($this->session->flashdata("login_error")):?>
          <div class="text-white px-4 py-3 rounded-lg mb-6 text-sm" style="background: rgba(230,57,70,0.2); border: 1px solid rgba(230,57,70,0.3);">
            <?php echo $this->session->flashdata("login_error")?>
          </div>
        <?php endif; ?>

        <form action="<?= base_url() ?>sisvent/login/validate" method="post">
          <div class="mb-5">
            <label class="block text-gray-400 text-xs font-semibold uppercase tracking-wider mb-2">Identificacion</label>
            <input type="text" name="uid" class="w-full px-4 py-3 rounded-lg input-dark text-sm" placeholder="Numero de documento" />
          </div>

          <div class="mb-6">
            <label class="block text-gray-400 text-xs font-semibold uppercase tracking-wider mb-2">Contrasena</label>
            <input type="password" name="ups" class="w-full px-4 py-3 rounded-lg input-dark text-sm" placeholder="Tu contrasena" />
          </div>

          <input type="submit" class="w-full py-3 rounded-lg btn-ledxury text-white font-bold text-sm uppercase tracking-wider cursor-pointer" value="Ingresar" />
        </form>
      </div>

      <!-- Footer -->
      <p class="text-center text-gray-600 text-xs mt-6">&copy; Ledxury <?php echo date('Y'); ?></p>
    </div>
  </div>
</body>
</html>
