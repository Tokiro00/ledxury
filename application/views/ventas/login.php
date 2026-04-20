<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ledxury Ventas</title>
    <link rel="icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <link rel="shortcut icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#0a0a14; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .login-card { background:#16213e; border-radius:16px; padding:40px 28px; width:90%; max-width:380px; }
        .logo { text-align:center; margin-bottom:32px; }
        .logo h1 { color:#fff; font-size:28px; font-weight:800; letter-spacing:2px; }
        .logo p { color:#8896a4; font-size:12px; margin-top:4px; }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; color:#8896a4; font-size:12px; margin-bottom:6px; text-transform:uppercase; letter-spacing:1px; }
        .form-input { width:100%; padding:14px 16px; border:none; border-radius:10px; background:#1a1a2e; color:#fff; font-size:16px; outline:none; }
        .form-input:focus { background:#222240; box-shadow:0 0 0 2px #E63946; }
        .form-input::placeholder { color:#6b7fa8; }
        .btn-login { width:100%; padding:14px; border:none; border-radius:10px; background:linear-gradient(135deg,#E63946,#c0392b); color:#fff; font-size:16px; font-weight:700; cursor:pointer; margin-top:8px; text-transform:uppercase; letter-spacing:1px; }
        .btn-login:active { transform:scale(0.98); }
        .error-msg { background:#E6394620; color:#E63946; padding:10px; border-radius:8px; font-size:13px; text-align:center; margin-bottom:16px; }
        .back-link { text-align:center; margin-top:20px; }
        .back-link a { color:#6b7fa8; font-size:12px; text-decoration:none; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <h1>LEDXURY</h1>
            <p>Panel de Ventas</p>
        </div>

        <?php if ($this->session->flashdata('login_error')): ?>
        <div class="error-msg"><?= $this->session->flashdata('login_error') ?></div>
        <?php endif; ?>

        <form action="<?= base_url() ?>ventas/validate" method="POST">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
            <div class="form-group">
                <label>Documento</label>
                <input type="text" name="uid" class="form-input" placeholder="Numero de documento" required autocomplete="username">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="ups" class="form-input" placeholder="Tu contraseña" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login">Ingresar</button>
        </form>

        <div class="back-link">
            <a href="<?= base_url() ?>">← Ir al sitio principal</a>
        </div>
    </div>
</body>
</html>
