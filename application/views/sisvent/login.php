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
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="<?php echo get_public_path('main'.$prefix.'.css') ?>">
    <link rel="stylesheet" href="<?php echo base_url() ?>public/assets/styles/tablero.css">
    <script src="<?php echo get_public_path('main'.$prefix.'.js') ?>"></script>
    <style>
      /* Sistema Tablero: la entrada conserva su estética oscura, pero con la
         paleta de la marca — navy de fondo y naranja como único acento.
         Antes usaba el rojo #E63946 del rebrand a medias. */
      .login-bg { background: #1B1F3B; }
      .login-card { background: #2B3164; }
      .led-glow { text-shadow: 0 0 20px rgba(247,148,29,0.40), 0 0 40px rgba(247,148,29,0.20); }
      .accent-line { background: linear-gradient(90deg, #F7941D, #C97810); }
      .btn-ledxury {
        background: linear-gradient(135deg, #F7941D 0%, #E07E0B 100%);
        transition: all 0.3s ease;
      }
      .btn-ledxury:hover {
        background: linear-gradient(135deg, #E07E0B 0%, #C97810 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(247,148,29,0.40);
      }
      .input-dark {
        background: rgba(255,255,255,0.07);
        border: 1px solid rgba(255,255,255,0.16);
        color: #ECEDF3;
        transition: border-color 0.2s;
      }
      .input-dark:focus {
        border-color: #F7941D;
        outline: none;
        box-shadow: 0 0 0 3px rgba(247,148,29,0.22);
      }
      .input-dark::placeholder { color: #A2A7BA; }
    </style>
  </head>
<body class="login-bg font-sans antialiased" style="font-family: Manrope, Inter, -apple-system, sans-serif;">
  <div class="flex items-center justify-center min-h-screen px-4">
    <div class="w-full max-w-md">

      <!-- Logo Area -->
      <div class="text-center mb-8">
        <h1 class="text-5xl font-black text-white tracking-tight led-glow" style="font-family: Manrope, Inter, sans-serif;">
          LEDXURY
        </h1>
        <div class="accent-line h-1 w-16 mx-auto mt-3 rounded-full"></div>
        <p class="text-gray-400 text-sm mt-3 tracking-widest uppercase">Luxury</p>
      </div>

      <!-- Login Card -->
      <div class="login-card rounded-2xl shadow-2xl p-8" style="border: 1px solid rgba(255,255,255,0.10);">

        <?php if($this->session->flashdata("login_error")):?>
          <div class="text-white px-4 py-3 rounded-lg mb-6 text-sm" style="background: rgba(208,58,46,0.22); border: 1px solid rgba(208,58,46,0.38);">
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
