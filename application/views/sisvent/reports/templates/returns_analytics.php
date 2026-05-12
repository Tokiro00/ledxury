<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Template returns_analytics — Análisis de Devoluciones.
 *
 * Estructura:
 *   1. KPI strip (4 tiles)
 *   2. Tasa por transportadora
 *   3. Tasa por vendedor
 *   4. Tasa por ciudad
 *   5. Clientes problemáticos (>3 devoluciones)
 *   6. Productos más devueltos
 *   7. Evolución mensual (bar chart simple)
 */

$kpis           = $data['kpis']            ?? [];
$byCarrier      = $data['by_carrier']      ?? [];
$byVendor       = $data['by_vendor']       ?? [];
$byCity         = $data['by_city']         ?? [];
$problemClients = $data['problem_clients'] ?? [];
$topProducts    = $data['top_products']    ?? [];
$monthly        = $data['monthly']         ?? [];
$isPdf          = !empty($pdf_mode);

$fmt = function ($val, $decimals = 0) {
    if (!is_numeric($val)) return $val;
    return number_format((float)$val, $decimals, ',', '.');
};
$fmtCurrency = function ($val) use ($fmt) {
    if (!is_numeric($val)) return $val;
    $abs = abs((float)$val);
    $sign = $val < 0 ? '-' : '';
    if ($abs >= 1000000000) return $sign . '$' . $fmt($abs / 1000000000, 1) . 'B';
    if ($abs >= 1000000)    return $sign . '$' . $fmt($abs / 1000000, 1) . 'M';
    if ($abs >= 1000)       return $sign . '$' . $fmt($abs / 1000, 0) . 'K';
    return $sign . '$' . $fmt($abs);
};
$fmtFull = function ($val) use ($fmt) {
    if (!is_numeric($val)) return $val;
    $sign = $val < 0 ? '-' : '';
    return $sign . '$' . $fmt(abs($val));
};
$tasaColor = function ($tasa) {
    if ($tasa >= 10) return '#C0392B'; // crítico
    if ($tasa >= 5)  return '#F39C12'; // alerta
    if ($tasa >= 2)  return '#4487A0'; // normal
    return '#5EBA47';                  // bueno
};

$tasaGlobal = (float)($kpis['tasa_global'] ?? 0);
$tasaTone   = $tasaGlobal >= 10 ? 'dn' : ($tasaGlobal >= 5 ? 'mid' : 'up');
$tasaCol    = $tasaColor($tasaGlobal);
?>

<!-- ============== KPI STRIP ============== -->
<?php
$kpiTiles = [
    [
        'eyebrow' => 'Devoluciones',
        'value'   => $fmt($kpis['total_devs'] ?? 0),
        'delta'   => $fmtCurrency($kpis['valor_devs'] ?? 0) . ' en valor',
        'delta_tone' => 'fg4',
        'accent'  => 'var(--mam-blue-petroleo, #4487A0)',
        'pdf_color' => '#4487A0',
    ],
    [
        'eyebrow' => 'Tasa de devolución',
        'value'   => $fmt($tasaGlobal, 2) . '%',
        'delta'   => $fmt($kpis['total_devs'] ?? 0) . ' de ' . $fmt($kpis['total_despachado'] ?? 0) . ' despachadas',
        'delta_tone' => $tasaTone,
        'accent'  => $tasaCol,
        'pdf_color' => $tasaCol,
    ],
    [
        'eyebrow' => 'Costo asumido',
        'value'   => $fmtCurrency($kpis['costo_total'] ?? 0),
        'delta'   => 'fletes ida + vuelta perdidos',
        'delta_tone' => 'dn',
        'accent'  => '#C0392B',
        'pdf_color' => '#C0392B',
    ],
    [
        'eyebrow' => 'Tiempo prom. recibir',
        'value'   => ($kpis['dias_prom_recibir'] ?? null) !== null ? $fmt($kpis['dias_prom_recibir'], 1) . ' d' : '—',
        'delta'   => 'detección → llegada física',
        'delta_tone' => 'fg4',
        'accent'  => 'var(--mam-gray-medium, #AEAAA6)',
        'pdf_color' => '#AEAAA6',
    ],
];
?>
<?php if ($isPdf): ?>
    <table cellspacing="0" cellpadding="0" style="width:100%;margin-bottom:10px;border-collapse:separate;border-spacing:5px 0;">
        <tr>
            <?php foreach ($kpiTiles as $t): ?>
                <td style="width:25%;border:1px solid #DDDFE8;border-left:3px solid <?= $t['pdf_color'] ?>;padding:7px 10px;background:#FAFBFC;">
                    <div style="font-size:8px;font-weight:700;text-transform:uppercase;color:#7F8392;letter-spacing:0.5px;"><?= htmlspecialchars($t['eyebrow']) ?></div>
                    <div style="font-size:15px;font-weight:800;color:#2B3164;margin-top:2px;"><?= $t['value'] ?></div>
                    <?php if ($t['delta']): ?><div style="font-size:8px;color:#7F8392;margin-top:1px;"><?= htmlspecialchars($t['delta']) ?></div><?php endif; ?>
                </td>
            <?php endforeach; ?>
        </tr>
    </table>
<?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:12px;">
        <?php foreach ($kpiTiles as $t): $this->load->view('sisvent/design-system/_kpi_tile', $t); endforeach; ?>
    </div>
<?php endif; ?>

<?php
// Helper para render de tablas de tasa por dimensión
$renderTasaTable = function ($title, $rows, $keyName, $keyLabel) use ($fmt, $fmtCurrency, $fmtFull, $tasaColor) {
    if (empty($rows)) return;
    ?>
    <div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;margin-bottom:12px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
        <div style="padding:7px 12px;background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);font-size:11px;font-weight:700;text-transform:uppercase;color:#7F8392;letter-spacing:0.5px;">
            <?= htmlspecialchars($title) ?>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);">
                    <th style="padding:5px 8px;text-align:left;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.3px;"><?= htmlspecialchars($keyLabel) ?></th>
                    <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.3px;">Despachadas</th>
                    <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.3px;">Devueltas</th>
                    <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.3px;">Tasa %</th>
                    <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.3px;">Valor devs</th>
                    <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.3px;">Costo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r):
                    $tasa = (float)($r['tasa_pct'] ?? 0);
                    $color = $tasaColor($tasa);
                ?>
                <tr style="border-bottom:1px solid var(--border-subtle,#F1F3F5);">
                    <td style="padding:5px 8px;font-weight:600;"><?= htmlspecialchars((string)$r[$keyName]) ?></td>
                    <td style="padding:5px 8px;text-align:right;font-family:monospace;"><?= $fmt($r['despachadas']) ?></td>
                    <td style="padding:5px 8px;text-align:right;font-family:monospace;color:#C0392B;"><?= $fmt($r['devueltas']) ?></td>
                    <td style="padding:5px 8px;text-align:right;font-weight:700;color:<?= $color ?>;"><?= $fmt($tasa, 2) ?>%</td>
                    <td style="padding:5px 8px;text-align:right;font-family:monospace;"><?= $fmtFull($r['valor_devs']) ?></td>
                    <td style="padding:5px 8px;text-align:right;font-family:monospace;color:#C0392B;"><?= $fmtFull($r['costo']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
};
?>

<!-- Tasa por dimensiones -->
<?php $renderTasaTable('Tasa por transportadora', $byCarrier, 'carrier', 'Transportadora'); ?>
<?php $renderTasaTable('Tasa por vendedor',       $byVendor,  'vendor',  'Vendedor'); ?>
<?php $renderTasaTable('Tasa por ciudad (top 15)', $byCity,   'ciudad',  'Ciudad'); ?>

<!-- Clientes problemáticos -->
<?php if (!empty($problemClients)): ?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;margin-bottom:12px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <div style="padding:7px 12px;background:#FEF2F2;border-bottom:1px solid var(--border-default,#DDDFE8);font-size:11px;font-weight:700;text-transform:uppercase;color:#C0392B;letter-spacing:0.5px;">
        ⚠ Clientes problemáticos (3+ devoluciones)
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:11px;">
        <thead>
            <tr style="background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);">
                <th style="padding:5px 8px;text-align:left;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;">Cliente</th>
                <th style="padding:5px 8px;text-align:left;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;">Ciudad</th>
                <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;">Devoluciones</th>
                <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;">Valor</th>
                <th style="padding:5px 8px;text-align:left;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;">Última</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($problemClients as $pc): ?>
            <tr style="border-bottom:1px solid var(--border-subtle,#F1F3F5);">
                <td style="padding:5px 8px;font-weight:600;"><?= htmlspecialchars($pc['client_name'] ?? '—') ?></td>
                <td style="padding:5px 8px;color:#575964;"><?= htmlspecialchars($pc['client_city'] ?? '—') ?></td>
                <td style="padding:5px 8px;text-align:right;font-weight:700;color:#C0392B;"><?= (int)$pc['num_devs'] ?></td>
                <td style="padding:5px 8px;text-align:right;font-family:monospace;"><?= $fmtFull($pc['valor_devs']) ?></td>
                <td style="padding:5px 8px;font-size:10px;color:#7F8392;"><?= !empty($pc['ultima_dev']) ? date('d/m/Y', strtotime($pc['ultima_dev'])) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Top productos devueltos -->
<?php if (!empty($topProducts)): ?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;margin-bottom:12px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <div style="padding:7px 12px;background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);font-size:11px;font-weight:700;text-transform:uppercase;color:#7F8392;letter-spacing:0.5px;">
        Productos más devueltos (top 15)
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:11px;">
        <thead>
            <tr style="background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);">
                <th style="padding:5px 8px;text-align:left;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;">Código</th>
                <th style="padding:5px 8px;text-align:left;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;">Producto</th>
                <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;">Unidades</th>
                <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;"># Devoluciones</th>
                <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;">Valor devuelto</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topProducts as $tp): ?>
            <tr style="border-bottom:1px solid var(--border-subtle,#F1F3F5);">
                <td style="padding:5px 8px;font-family:monospace;font-size:10px;"><?= htmlspecialchars($tp['code']) ?></td>
                <td style="padding:5px 8px;color:#575964;"><?= htmlspecialchars($tp['name']) ?></td>
                <td style="padding:5px 8px;text-align:right;font-family:monospace;font-weight:600;"><?= $fmt($tp['qty_devuelta']) ?></td>
                <td style="padding:5px 8px;text-align:right;font-family:monospace;"><?= $fmt($tp['num_devs']) ?></td>
                <td style="padding:5px 8px;text-align:right;font-family:monospace;"><?= $fmtFull($tp['valor_devuelto']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Evolución mensual -->
<?php if (!empty($monthly)):
    $maxMonth = max(array_column($monthly, 'num_devs')) ?: 1;
?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;margin-bottom:12px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <div style="padding:7px 12px;background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);font-size:11px;font-weight:700;text-transform:uppercase;color:#7F8392;letter-spacing:0.5px;">
        Evolución mensual
    </div>
    <div style="padding:10px 14px;">
        <?php foreach ($monthly as $m):
            $widthPct = ($m['num_devs'] / $maxMonth) * 100;
        ?>
        <div style="display:grid;grid-template-columns:75px 1fr 80px 100px;gap:8px;align-items:center;font-size:11px;margin-bottom:4px;">
            <div style="font-family:monospace;color:#575964;font-weight:600;"><?= htmlspecialchars($m['mes']) ?></div>
            <div style="background:var(--bg-subtle,#F1F3F5);height:18px;border-radius:3px;position:relative;overflow:hidden;">
                <div style="background:#C0392B;height:100%;width:<?= round($widthPct, 1) ?>%;border-radius:3px;"></div>
                <?php if ($m['num_devs'] > 0): ?>
                    <span style="position:absolute;left:7px;top:0;line-height:18px;font-size:10px;font-weight:700;color:#fff;font-family:monospace;"><?= $fmt($m['num_devs']) ?> dev</span>
                <?php endif; ?>
            </div>
            <div style="text-align:right;font-family:monospace;color:#575964;"><?= $fmtFull($m['valor_devs']) ?></div>
            <div style="text-align:right;font-family:monospace;color:#C0392B;"><?= $fmtFull($m['costo']) ?> costo</div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (empty($byCarrier) && empty($byVendor) && empty($byCity) && empty($problemClients) && empty($topProducts) && empty($monthly)): ?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;padding:30px;text-align:center;color:var(--fg-3,#AEAAA6);">
    No hay devoluciones registradas en este rango.<br>
    <span style="font-size:10px;">Probá entrar a <a href="<?= base_url('sisvent/admin/devoluciones') ?>" style="color:#4487A0;">/admin/devoluciones</a> para activar el detector automático.</span>
</div>
<?php endif; ?>
