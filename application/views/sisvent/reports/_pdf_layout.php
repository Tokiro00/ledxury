<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Layout simplificado para PDF: solo el contenido del reporte + header MAM.
 * No incluye sidebar/navbar/action_bar (son chrome interactivo).
 *
 * @var ReportInterface $report
 * @var array $params
 * @var array $data
 * @var array $meta
 * @var string $template
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($report->title()) ?></title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #2C2721; }
        h1, h2, h3 { color: #2B3164; margin: 0; }
        h1 { font-size: 22px; font-weight: 800; }
        h2 { font-size: 16px; font-weight: 700; margin-top: 12px; }
        .eyebrow { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #4487A0; margin-bottom: 4px; }
        .subtitle { font-size: 12px; color: #7F8392; margin-top: 4px; }
        .hairline { border-bottom: 1px solid #DDDFE8; margin: 14px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 10.5px; margin-top: 8px; }
        th { background: #2B3164; color: white; padding: 6px 8px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 6px 8px; border-bottom: 1px solid #F1F3F5; }
        tr.totals td { font-weight: 700; background: #F8F8F8; border-top: 2px solid #2B3164; }
        .meta-line { font-size: 10px; color: #7F8392; margin-bottom: 12px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="eyebrow">Reportes · MAM</div>
    <h1><?= htmlspecialchars($report->title()) ?></h1>
    <?php if ($report->description()): ?>
        <div class="subtitle"><?= htmlspecialchars($report->description()) ?></div>
    <?php endif; ?>
    <div class="meta-line">
        Generado el <?= date('d/m/Y H:i') ?>
        <?php if (!empty($params['desde']) || !empty($params['hasta'])): ?>
            · Período <?= !empty($params['desde']) ? date('d/m/Y', strtotime($params['desde'])) : '—' ?>
            a <?= !empty($params['hasta']) ? date('d/m/Y', strtotime($params['hasta'])) : '—' ?>
        <?php endif; ?>
    </div>
    <div class="hairline"></div>

    <?php $this->load->view($template, [
        'report' => $report,
        'params' => $params,
        'data'   => $data,
        'meta'   => $meta,
        'pdf_mode' => true,
    ]); ?>
</body>
</html>
