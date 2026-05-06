<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];

/**
 * @var ReportInterface $report
 * @var array $history
 */
?>
<!DOCTYPE html>
<html lang="es">
    <title>Historial — <?= htmlspecialchars($report->title()) ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <?php
                    $this->load->view('sisvent/design-system/_page_header', [
                        'eyebrow'  => 'Reportes · ' . strtoupper($report->id()),
                        'title'    => 'Historial — ' . $report->title(),
                        'subtitle' => count($history) . ' despacho' . (count($history) === 1 ? '' : 's') . ' registrado' . (count($history) === 1 ? '' : 's') . ' (últimos 100)',
                    ]);
                    ?>

                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
                        <a href="<?= base_url() ?>sisvent/admin/reports/v2/<?= $report->id() ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;font-size:13px;font-weight:500;color:var(--fg-2,#575964);background:transparent;border-radius:6px;text-decoration:none;">
                            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            <span>Volver al reporte</span>
                        </a>
                    </div>

                    <div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">
                        <table style="width:100%;border-collapse:collapse;font-size:13px;">
                            <thead>
                                <tr style="background:var(--mam-blue-dark,#2B3164);color:#fff;">
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Fecha</th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Canal</th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Formato</th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Destinatario</th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Por</th>
                                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($history)): ?>
                                    <tr><td colspan="6" style="padding:32px;text-align:center;color:var(--fg-3,#AEAAA6);">Aún no hay despachos registrados.</td></tr>
                                <?php else: foreach ($history as $h): ?>
                                <tr style="border-bottom:1px solid var(--border-subtle,#F1F3F5);">
                                    <td style="padding:10px 12px;">
                                        <?= date('d/m/Y', strtotime($h['dispatched_at'])) ?>
                                        <span style="color:var(--fg-3,#AEAAA6);"><?= date('H:i', strtotime($h['dispatched_at'])) ?></span>
                                    </td>
                                    <td style="padding:10px 12px;text-transform:capitalize;"><?= htmlspecialchars($h['channel']) ?></td>
                                    <td style="padding:10px 12px;text-transform:uppercase;font-size:11px;font-weight:600;color:var(--fg-2,#575964);"><?= htmlspecialchars($h['format']) ?></td>
                                    <td style="padding:10px 12px;font-family:monospace;font-size:12px;"><?= htmlspecialchars($h['recipient'] ?? '—') ?></td>
                                    <td style="padding:10px 12px;"><?= htmlspecialchars($h['dispatched_by']) ?></td>
                                    <td style="padding:10px 12px;">
                                        <?php if ($h['status'] === 'sent'): ?>
                                            <span style="font-size:11px;font-weight:600;padding:2px 8px;background:var(--mam-green-light,#f0f8e4);color:var(--mam-green-program,#31AB20);border-radius:9999px;">enviado</span>
                                        <?php else: ?>
                                            <span style="font-size:11px;font-weight:600;padding:2px 8px;background:var(--mam-red-light,#FFD2D2);color:var(--mam-red,#ef0d0d);border-radius:9999px;" title="<?= htmlspecialchars($h['error_message'] ?? '') ?>">fallido</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
