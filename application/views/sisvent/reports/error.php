<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];

/**
 * @var string $message
 * @var string $code
 */
?>
<!DOCTYPE html>
<html lang="es">
    <title>Reporte — Error</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">
                    <div style="max-width:480px;margin:80px auto;text-align:center;">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--mam-red,#ef0d0d);margin-bottom:8px;">
                            Error · <?= htmlspecialchars($code) ?>
                        </div>
                        <h1 style="font-size:22px;font-weight:800;color:var(--mam-blue-dark,#2B3164);margin:0 0 12px;">
                            No se pudo generar el reporte
                        </h1>
                        <p style="font-size:14px;color:var(--fg-2,#575964);margin-bottom:24px;line-height:1.5;">
                            <?= htmlspecialchars($message) ?>
                        </p>
                        <a href="<?= base_url() ?>sisvent/admin/reports/v2" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;color:#fff;background:var(--mam-blue-petroleo,#4487A0);border-radius:6px;text-decoration:none;">
                            ← Volver a reportes
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
