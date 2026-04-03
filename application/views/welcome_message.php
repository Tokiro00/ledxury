<?php
$isProduction = 'production' === ENVIRONMENT;
$prefix = $isProduction ? '' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Ledxury - Luxury</title>
  <meta name="description" content="Ledxury - Luxury LED Solutions">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="shortcut icon" type="image/png" href="/favicon.ico"/>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Inter', sans-serif;
      background: #0a0a14;
      color: #fff;
      min-height: 100vh;
      overflow-x: hidden;
    }
    .hero-bg {
      background: radial-gradient(ellipse at 50% 0%, rgba(230,57,70,0.15) 0%, transparent 60%),
                  radial-gradient(ellipse at 80% 50%, rgba(26,26,46,0.8) 0%, transparent 50%),
                  #0a0a14;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    nav {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem 3rem;
    }
    .nav-logo {
      font-size: 1.5rem;
      font-weight: 900;
      color: #fff;
      text-decoration: none;
      letter-spacing: -0.5px;
    }
    .nav-logo span { color: #E63946; }
    .nav-links { display: flex; align-items: center; gap: 1.5rem; }
    .nav-links a {
      color: #888;
      text-decoration: none;
      font-size: 0.85rem;
      transition: color 0.2s;
    }
    .nav-links a:hover { color: #E63946; }
    .btn-login {
      background: linear-gradient(135deg, #E63946, #c1121f);
      color: #fff;
      padding: 0.6rem 1.8rem;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
      transition: all 0.3s;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(230,57,70,0.4);
    }
    .hero {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 2rem;
    }
    .hero-title {
      font-size: clamp(3rem, 8vw, 7rem);
      font-weight: 900;
      letter-spacing: -2px;
      line-height: 1;
      margin-bottom: 0.5rem;
      text-shadow: 0 0 40px rgba(230,57,70,0.3);
    }
    .hero-title span { color: #E63946; }
    .hero-sub {
      font-size: 1.1rem;
      color: #666;
      letter-spacing: 0.6em;
      text-transform: uppercase;
      margin-bottom: 2.5rem;
    }
    .hero-line {
      width: 60px;
      height: 3px;
      background: linear-gradient(90deg, #E63946, #c1121f);
      border-radius: 2px;
      margin: 0 auto 2rem;
    }
    .hero-cta {
      display: inline-block;
      background: linear-gradient(135deg, #E63946, #c1121f);
      color: #fff;
      padding: 1rem 3rem;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 700;
      font-size: 1rem;
      letter-spacing: 1px;
      transition: all 0.3s;
    }
    .hero-cta:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(230,57,70,0.4);
    }
    footer {
      text-align: center;
      padding: 2rem;
      color: #444;
      font-size: 0.75rem;
    }
    @media (max-width: 640px) {
      nav { padding: 1rem 1.5rem; }
      .nav-links a:not(.btn-login) { display: none; }
    }
  </style>
</head>
<body>
  <div class="hero-bg">

    <nav>
      <a href="#" class="nav-logo">LED<span>X</span>URY</a>
      <div class="nav-links"></div>
    </nav>

    <div class="hero">
      <h1 class="hero-title">LED<span>X</span>URY</h1>
      <div class="hero-line"></div>
      <p class="hero-sub" style="letter-spacing: 0.2em; font-size: 0.95rem; color: #999;">Ilumina tu camino con estilo</p>
      <a href="<?= base_url() ?>sisvent/login" class="hero-cta">INICIAR SESION</a>
    </div>

    <footer>
      &copy; Ledxury <?php echo date('Y'); ?> — Todos los derechos reservados
    </footer>

  </div>
</body>
</html>
