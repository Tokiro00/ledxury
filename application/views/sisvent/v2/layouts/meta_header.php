<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ledxury v2 — meta_header
 *
 * Cabecera HTML solo para vistas /sisvent/v2/*. v1 no se ve afectado.
 * Carga: Inter + Tailwind (mismo bundle compilado de v1) + v2-tokens.css +
 *        Alpine 3 (CDN, defer) + jQuery (mismo CDN de v1) + CSRF meta.
 *
 * data-palette por defecto = "petroleo". Se puede cambiar con JS o desde
 * un switcher futuro: document.documentElement.dataset.palette = 'ember'.
 */
$pageTitle = isset($pageTitle) ? $pageTitle . ' · Ledxury' : 'Ledxury';
?>
<!DOCTYPE html>
<html lang="es" data-palette="petroleo">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?></title>

<meta name="csrf-token-name" content="<?= $this->security->get_csrf_token_name() ?>">
<meta name="csrf-token-hash" content="<?= $this->security->get_csrf_hash() ?>">

<!-- Tailwind bundle compilado de v1 — reusamos las utility classes -->
<link rel="stylesheet" href="<?= base_url('public/dist/main.min.css') ?>?v=<?= filemtime(FCPATH . 'public/dist/main.min.css') ?>">

<!-- Tokens v2 — paletas + tipo + spacing -->
<link rel="stylesheet" href="<?= base_url('public/assets/styles/v2-tokens.css') ?>?v=<?= filemtime(FCPATH . 'public/assets/styles/v2-tokens.css') ?>">

<!-- jQuery (misma versión que v1) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>

<!-- Alpine 3 para interactividad ligera (menús, dropdowns). Defer para que
     evalúe después de que el DOM esté listo. NO se carga en v1. -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>

<!-- Boot config para v2-app.js (URL base + CSRF) -->
<script>
window.LX_V2 = {
  baseUrl: '<?= base_url() ?>',
  csrfName: '<?= $this->security->get_csrf_token_name() ?>',
  csrfHash: '<?= $this->security->get_csrf_hash() ?>',
  user: <?= json_encode([
      'uname' => $this->session->userdata('user_data')['uname'] ?? '',
      'name'  => $this->session->userdata('user_data')['name']  ?? '',
      'role'  => (int)($this->session->userdata('user_data')['role'] ?? 0),
  ]) ?>,
};
</script>
</head>
<body class="lx-app" style="min-height: 100vh;">
