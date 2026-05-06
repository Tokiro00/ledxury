<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Template custom para Posición de Caja (v1.30.28 — vista limpia).
 *
 * Estructura simplificada:
 *   1. Saldos de apertura por cuenta
 *   2. KPI strip 6 tiles
 *   3. Timeline SVG
 *   4. Clasificación IAS 7
 *   5. Saldos de cierre por cuenta
 *   6. Tabla detalle por categoría con drill-down expandible
 *      (cada fila se despliega y muestra los conceptos individuales)
 *
 * Removidos en v1.30.28: bar divergente, resumen agrupado y top movimientos
 * individuales. Su info ahora vive como drill-down expandible dentro de la
 * tabla por categoría — un solo lugar a mirar.
 */

$kpis              = $data['kpis']               ?? [];
$accounts          = $data['accounts']           ?? [];
$timeline          = $data['timeline']           ?? [];
$byCategory        = $data['by_category']        ?? [];
$classified        = $data['classified']         ?? [];
$categoryDrilldown = $data['category_drilldown'] ?? [];
$groupBy           = $data['group_by']           ?? 'day';
$columns           = $data['columns']            ?? [];
$rows              = $data['rows']               ?? [];
$totals            = $data['totals']             ?? null;
$period            = $data['period']             ?? ['desde' => '', 'hasta' => ''];
$isPdf             = !empty($pdf_mode);

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

$growth = $kpis['growth_pct'] ?? null;
$growthTone = 'fg4'; $growthSymbol = '·';
if ($growth !== null) {
    if ($growth > 0.5)      { $growthTone = 'up'; $growthSymbol = '▲'; }
    elseif ($growth < -0.5) { $growthTone = 'dn'; $growthSymbol = '▼'; }
}
$growthLabel = $growth === null
    ? 'sin datos previos'
    : $growthSymbol . ' ' . $fmt(abs($growth), 1) . '% vs ' . ($kpis['prev_label'] ?? '');

$netoColor = ($kpis['neto'] ?? 0) >= 0 ? 'var(--mam-green-program, #5EBA47)' : '#C0392B';
$netoColorPdf = ($kpis['neto'] ?? 0) >= 0 ? '#5EBA47' : '#C0392B';

// Splitter: caja vs banco para los bloques de saldo
$accountsCaja  = array_filter($accounts, fn($a) => $a['source_type'] === 'caja');
$accountsBanco = array_filter($accounts, fn($a) => $a['source_type'] === 'banco');

// ====================================================================
// HELPERS
// ====================================================================

$renderAccountTable = function (array $accounts, string $title, string $whichBalance) use ($fmt, $fmtFull) {
    if (empty($accounts)) return;
    $sumOpening = array_sum(array_column($accounts, 'opening_balance'));
    $sumClosing = array_sum(array_column($accounts, 'closing_balance'));
    $sumIn      = array_sum(array_column($accounts, 'period_in'));
    $sumOut     = array_sum(array_column($accounts, 'period_out'));
?>
    <div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);margin-bottom:14px;">
        <div style="padding:10px 14px;background:var(--mam-blue-dark,#2B3164);color:#fff;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">
            <?= htmlspecialchars($title) ?>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);">
                    <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.4px;">Cuenta</th>
                    <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.4px;">Saldo Inicial</th>
                    <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:600;text-transform:uppercase;color:#5EBA47;letter-spacing:0.4px;">Ingresos</th>
                    <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:600;text-transform:uppercase;color:#C0392B;letter-spacing:0.4px;">Egresos</th>
                    <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.4px;">Saldo Final</th>
                    <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.4px;">Δ %</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $a):
                    $variation = $a['variation_pct'];
                    $varColor = '#7F8392'; $varSymbol = '·';
                    if ($variation !== null) {
                        if ($variation > 0.5)      { $varColor = '#5EBA47'; $varSymbol = '▲'; }
                        elseif ($variation < -0.5) { $varColor = '#C0392B'; $varSymbol = '▼'; }
                    }
                ?>
                    <tr style="border-bottom:1px solid var(--border-subtle,#F1F3F5);">
                        <td style="padding:8px 12px;color:var(--fg-1,#2C2721);">
                            <?= htmlspecialchars($a['name']) ?>
                            <span style="font-size:9px;text-transform:uppercase;color:#AEAAA6;margin-left:6px;letter-spacing:0.4px;"><?= htmlspecialchars($a['source_type']) ?></span>
                        </td>
                        <td style="padding:8px 12px;text-align:right;color:#575964;font-family:monospace;"><?= htmlspecialchars($fmtFull($a['opening_balance'])) ?></td>
                        <td style="padding:8px 12px;text-align:right;color:#5EBA47;font-family:monospace;"><?= $a['period_in'] > 0 ? '+' . htmlspecialchars($fmtFull($a['period_in'])) : '—' ?></td>
                        <td style="padding:8px 12px;text-align:right;color:#C0392B;font-family:monospace;"><?= $a['period_out'] > 0 ? '-' . htmlspecialchars($fmtFull($a['period_out'])) : '—' ?></td>
                        <td style="padding:8px 12px;text-align:right;color:var(--mam-blue-dark,#2B3164);font-family:monospace;font-weight:700;"><?= htmlspecialchars($fmtFull($a['closing_balance'])) ?></td>
                        <td style="padding:8px 12px;text-align:right;color:<?= $varColor ?>;font-family:monospace;font-size:11px;">
                            <?= $variation !== null ? $varSymbol . ' ' . htmlspecialchars($fmt(abs($variation), 1)) . '%' : '—' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr style="background:var(--bg-subtle,#F1F3F5);font-weight:700;border-top:2px solid var(--mam-blue-dark,#2B3164);">
                    <td style="padding:10px 12px;color:var(--mam-blue-dark,#2B3164);">TOTAL</td>
                    <td style="padding:10px 12px;text-align:right;color:var(--mam-blue-dark,#2B3164);font-family:monospace;"><?= htmlspecialchars($fmtFull($sumOpening)) ?></td>
                    <td style="padding:10px 12px;text-align:right;color:#5EBA47;font-family:monospace;">+<?= htmlspecialchars($fmtFull($sumIn)) ?></td>
                    <td style="padding:10px 12px;text-align:right;color:#C0392B;font-family:monospace;">-<?= htmlspecialchars($fmtFull($sumOut)) ?></td>
                    <td style="padding:10px 12px;text-align:right;color:var(--mam-blue-dark,#2B3164);font-family:monospace;"><?= htmlspecialchars($fmtFull($sumClosing)) ?></td>
                    <td style="padding:10px 12px;"></td>
                </tr>
            </tbody>
        </table>
    </div>
<?php
};
?>

<!-- ============== BLOQUE 1: SALDOS DE APERTURA POR CUENTA ============== -->
<?php if (!empty($accounts)): ?>
    <div style="margin-bottom:6px;font-size:11px;text-transform:uppercase;font-weight:700;letter-spacing:0.5px;color:#7F8392;">
        Saldos al inicio del período <?= htmlspecialchars($period['desde']) ?>
    </div>
    <?php $renderAccountTable($accounts, 'Posición de apertura', 'opening'); ?>
<?php endif; ?>

<!-- ============== BLOQUE 2: KPI STRIP (6 tiles) ============== -->
<?php
$kpiTiles = [
    ['eyebrow' => 'Saldo inicial', 'value' => $fmtCurrency($kpis['opening'] ?? 0),  'delta' => '',                                                              'delta_tone' => 'fg4',       'accent' => 'var(--mam-blue-dark, #2B3164)',    'pdf_color' => '#2B3164'],
    ['eyebrow' => 'Ingresos',      'value' => $fmtCurrency($kpis['ingresos'] ?? 0), 'delta' => '',                                                              'delta_tone' => 'fg4',       'accent' => 'var(--mam-green-program, #5EBA47)','pdf_color' => '#5EBA47'],
    ['eyebrow' => 'Egresos',       'value' => $fmtCurrency($kpis['egresos'] ?? 0),  'delta' => '',                                                              'delta_tone' => 'fg4',       'accent' => '#C0392B',                          'pdf_color' => '#C0392B'],
    ['eyebrow' => 'Neto periodo',  'value' => $fmtCurrency($kpis['neto'] ?? 0),     'delta' => $growthLabel,                                                    'delta_tone' => $growthTone, 'accent' => $netoColor,                         'pdf_color' => $netoColorPdf],
    ['eyebrow' => 'Saldo final',   'value' => $fmtCurrency($kpis['closing'] ?? 0),  'delta' => '',                                                              'delta_tone' => 'fg4',       'accent' => 'var(--mam-blue-petroleo, #4487A0)','pdf_color' => '#4487A0'],
    ['eyebrow' => '# Movs',        'value' => $fmt($kpis['num_movs'] ?? 0),         'delta' => 'Periodo prev: ' . $fmtCurrency($kpis['prev_neto'] ?? 0),       'delta_tone' => 'fg4',       'accent' => 'var(--mam-gray-medium, #AEAAA6)',  'pdf_color' => '#AEAAA6'],
];
?>
<?php if ($isPdf): ?>
    <table cellspacing="0" cellpadding="0" style="width:100%;margin-bottom:14px;border-collapse:separate;border-spacing:5px 0;">
        <tr>
            <?php foreach ($kpiTiles as $t): ?>
                <td style="border:1px solid #DDDFE8;border-left:3px solid <?= $t['pdf_color'] ?>;padding:8px 10px;background:#FAFBFC;">
                    <div style="font-size:8px;font-weight:700;text-transform:uppercase;color:#7F8392;letter-spacing:0.5px;"><?= htmlspecialchars($t['eyebrow']) ?></div>
                    <div style="font-size:14px;font-weight:800;color:#2B3164;margin-top:2px;"><?= $t['value'] ?></div>
                    <?php if ($t['delta']): ?><div style="font-size:8px;color:#7F8392;margin-top:1px;"><?= htmlspecialchars($t['delta']) ?></div><?php endif; ?>
                </td>
            <?php endforeach; ?>
        </tr>
    </table>
<?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:10px;margin-bottom:18px;">
        <?php foreach ($kpiTiles as $t): $this->load->view('sisvent/design-system/_kpi_tile', $t); endforeach; ?>
    </div>
<?php endif; ?>

<!-- ============== BLOQUE 3: TIMELINE ============== -->
<?php
$bucketTitle = ['day' => 'día', 'week' => 'semana', 'month' => 'mes'][$groupBy] ?? 'día';

if (!empty($timeline)):
    $maxBar = 0;
    foreach ($timeline as $b) { $maxBar = max($maxBar, $b['ingreso'], $b['egreso']); }
    $maxBar = $maxBar ?: 1;

    $cumMax = 0; $cumMin = 0;
    foreach ($timeline as $b) { $cumMax = max($cumMax, $b['cumulative']); $cumMin = min($cumMin, $b['cumulative']); }
    $cumRange = max(abs($cumMax), abs($cumMin)) ?: 1;

    $w = 1100; $h = 320; $padL = 70; $padR = 60; $padT = 20; $padB = 50;
    $innerW = $w - $padL - $padR;
    $innerH = $h - $padT - $padB;
    $zeroY = $padT + $innerH / 2;
    $halfH = $innerH / 2;
    $n = count($timeline);
    $barWidth = $n > 0 ? max(4, ($innerW / $n) * 0.7) : 0;
    $stepX = $n > 1 ? $innerW / ($n - 1) : 0;

    $cumPts = [];
    foreach ($timeline as $i => $b) {
        $x = $padL + ($i * $stepX);
        $y = $zeroY - (($b['cumulative'] / $cumRange) * $halfH);
        $cumPts[] = round($x, 1) . ',' . round($y, 1);
    }
    $cumPath = 'M ' . implode(' L ', $cumPts);
?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;padding:16px 20px;margin-bottom:18px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h3 style="margin:0;font-size:14px;font-weight:700;color:var(--mam-blue-dark,#2B3164);text-transform:uppercase;letter-spacing:0.5px;">Flujo por <?= htmlspecialchars($bucketTitle) ?></h3>
        <div style="display:flex;gap:14px;font-size:11px;color:var(--fg-3,#AEAAA6);">
            <span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:14px;height:10px;background:#5EBA47;border-radius:2px;"></span>Ingreso</span>
            <span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:14px;height:10px;background:#C0392B;border-radius:2px;"></span>Egreso</span>
            <span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:14px;height:2px;background:#2B3164;"></span>Neto acumulado</span>
        </div>
    </div>
    <svg viewBox="0 0 <?= $w ?> <?= $h ?>" preserveAspectRatio="xMidYMid meet" style="width:100%;height:320px;display:block;">
        <line x1="<?= $padL ?>" y1="<?= $zeroY ?>" x2="<?= $w - $padR ?>" y2="<?= $zeroY ?>" stroke="#7F8392" stroke-width="1"/>
        <text x="<?= $padL - 8 ?>" y="<?= $zeroY + 4 ?>" text-anchor="end" font-size="10" fill="#AEAAA6" font-family="monospace">0</text>
        <text x="<?= $padL - 8 ?>" y="<?= $padT + 12 ?>" text-anchor="end" font-size="10" fill="#5EBA47" font-family="monospace"><?= htmlspecialchars($fmtCurrency($maxBar)) ?></text>
        <text x="<?= $padL - 8 ?>" y="<?= $h - $padB ?>" text-anchor="end" font-size="10" fill="#C0392B" font-family="monospace">-<?= htmlspecialchars($fmtCurrency($maxBar)) ?></text>
        <text x="<?= $w - $padR + 6 ?>" y="<?= $padT + 12 ?>" text-anchor="start" font-size="10" fill="#2B3164" font-family="monospace"><?= htmlspecialchars($fmtCurrency($cumRange)) ?></text>
        <text x="<?= $w - $padR + 6 ?>" y="<?= $h - $padB ?>" text-anchor="start" font-size="10" fill="#2B3164" font-family="monospace">-<?= htmlspecialchars($fmtCurrency($cumRange)) ?></text>

        <?php
        $skipEvery = max(1, (int) ceil($n / 14));
        foreach ($timeline as $i => $b):
            if ($i % $skipEvery !== 0 && $i !== $n - 1) continue;
            $x = $padL + ($i * $stepX);
        ?>
            <text x="<?= round($x, 1) ?>" y="<?= $h - $padB + 18 ?>" text-anchor="middle" font-size="10" fill="#575964"><?= htmlspecialchars($b['label']) ?></text>
        <?php endforeach; ?>

        <?php foreach ($timeline as $i => $b):
            $x = $padL + ($i * $stepX) - $barWidth / 2;
            $hUp = ($b['ingreso'] / $maxBar) * $halfH;
            $hDn = ($b['egreso'] / $maxBar) * $halfH;
        ?>
            <?php if ($hUp > 0): ?>
                <rect x="<?= round($x, 1) ?>" y="<?= round($zeroY - $hUp, 1) ?>" width="<?= round($barWidth, 1) ?>" height="<?= round($hUp, 1) ?>" fill="#5EBA47" opacity="0.85" rx="1.5">
                    <title><?= htmlspecialchars($b['label']) ?>: ingreso <?= htmlspecialchars($fmtCurrency($b['ingreso'])) ?></title>
                </rect>
            <?php endif; ?>
            <?php if ($hDn > 0): ?>
                <rect x="<?= round($x, 1) ?>" y="<?= round($zeroY, 1) ?>" width="<?= round($barWidth, 1) ?>" height="<?= round($hDn, 1) ?>" fill="#C0392B" opacity="0.85" rx="1.5">
                    <title><?= htmlspecialchars($b['label']) ?>: egreso <?= htmlspecialchars($fmtCurrency($b['egreso'])) ?></title>
                </rect>
            <?php endif; ?>
        <?php endforeach; ?>

        <path d="<?= $cumPath ?>" fill="none" stroke="#2B3164" stroke-width="2.5"/>
        <?php foreach ($timeline as $i => $b):
            $x = $padL + ($i * $stepX);
            $y = $zeroY - (($b['cumulative'] / $cumRange) * $halfH);
        ?>
            <circle cx="<?= round($x, 1) ?>" cy="<?= round($y, 1) ?>" r="3" fill="#2B3164"/>
            <title><?= htmlspecialchars($b['label']) ?>: acumulado <?= htmlspecialchars($fmtCurrency($b['cumulative'])) ?></title>
        <?php endforeach; ?>
    </svg>
</div>
<?php endif; ?>

<!-- ============== BLOQUE 4: CLASIFICACIÓN IAS 7 ============== -->
<?php if (!empty($classified)): ?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);margin-bottom:18px;">
    <div style="padding:10px 14px;background:#16A085;color:#fff;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;display:flex;justify-content:space-between;align-items:center;">
        <span>Clasificación contable (IAS 7)</span>
        <span style="font-size:10px;opacity:0.85;font-weight:500;">Estilo Odoo / SAP B1</span>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);">
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.4px;">Sección</th>
                <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:600;text-transform:uppercase;color:#5EBA47;letter-spacing:0.4px;">Ingresos</th>
                <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:600;text-transform:uppercase;color:#C0392B;letter-spacing:0.4px;">Egresos</th>
                <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.4px;">Neto</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sumNeto = 0;
            foreach ($classified as $key => $sec):
                $sumNeto += $sec['neto'];
                $netoColorRow = $sec['neto'] >= 0 ? '#5EBA47' : '#C0392B';
                $isEmpty = ($sec['ingreso'] == 0 && $sec['egreso'] == 0);
            ?>
                <tr style="border-bottom:1px solid var(--border-subtle,#F1F3F5);<?= $isEmpty ? 'opacity:0.5;' : '' ?>">
                    <td style="padding:10px 12px;color:var(--fg-1,#2C2721);font-weight:600;"><?= htmlspecialchars($sec['label']) ?></td>
                    <td style="padding:10px 12px;text-align:right;color:#5EBA47;font-family:monospace;"><?= $sec['ingreso'] > 0 ? '+' . htmlspecialchars($fmtFull($sec['ingreso'])) : '—' ?></td>
                    <td style="padding:10px 12px;text-align:right;color:#C0392B;font-family:monospace;"><?= $sec['egreso'] > 0 ? '-' . htmlspecialchars($fmtFull($sec['egreso'])) : '—' ?></td>
                    <td style="padding:10px 12px;text-align:right;color:<?= $netoColorRow ?>;font-family:monospace;font-weight:700;"><?= ($sec['neto'] >= 0 ? '+' : '') . htmlspecialchars($fmtFull($sec['neto'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr style="background:var(--bg-subtle,#F1F3F5);font-weight:700;border-top:2px solid var(--mam-blue-dark,#2B3164);">
                <td style="padding:10px 12px;color:var(--mam-blue-dark,#2B3164);">FLUJO NETO TOTAL</td>
                <td colspan="2"></td>
                <td style="padding:10px 12px;text-align:right;color:<?= $sumNeto >= 0 ? '#5EBA47' : '#C0392B' ?>;font-family:monospace;"><?= ($sumNeto >= 0 ? '+' : '') . htmlspecialchars($fmtFull($sumNeto)) ?></td>
            </tr>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ============== BLOQUE 5: SALDOS CIERRE POR CUENTA ============== -->
<?php if (!empty($accounts)): ?>
    <div style="margin-bottom:6px;font-size:11px;text-transform:uppercase;font-weight:700;letter-spacing:0.5px;color:#7F8392;">
        Saldos al final del período <?= htmlspecialchars($period['hasta']) ?>
    </div>
    <?php $renderAccountTable($accounts, 'Posición de cierre', 'closing'); ?>
<?php endif; ?>

<!-- ============== BLOQUE 6: DETALLE POR CATEGORÍA CON DRILL-DOWN ============== -->
<?php
// Mapping nombre legible -> category_key (para encontrar el drill-down)
$categoryKeyMap = [
    'Venta'             => 'venta',
    'Pago de Cliente'   => 'pago_cliente',
    'Pago a Proveedor'  => 'pago_proveedor',
    'Gasto'             => 'gasto',
    'Nómina'            => 'nomina',
    'Impuestos'         => 'impuestos',
    'Préstamo'          => 'prestamo',
    'Otro'              => 'otro',
];
?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <div style="padding:10px 14px;background:var(--mam-blue-dark,#2B3164);color:#fff;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;display:flex;justify-content:space-between;align-items:center;">
        <span>Detalle por categoría</span>
        <span style="font-size:10px;opacity:0.85;font-weight:500;">Click en una fila para ver los conceptos</span>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);">
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.4px;">Categoría</th>
                <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.4px;">#</th>
                <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:600;text-transform:uppercase;color:#5EBA47;letter-spacing:0.4px;">Ingreso</th>
                <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:600;text-transform:uppercase;color:#C0392B;letter-spacing:0.4px;">Egreso</th>
                <th style="padding:8px 12px;text-align:right;font-size:10px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.4px;">Neto</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="5" style="padding:24px;text-align:center;color:var(--fg-3,#AEAAA6);">No hay movimientos en el periodo.</td></tr>
            <?php else: foreach ($rows as $row):
                $catLabel = $row['category'];
                $catKey = $categoryKeyMap[$catLabel] ?? strtolower($catLabel);
                $drilldown = $categoryDrilldown[$catKey]['top_movements'] ?? [];
                $netoColorRow = $row['neto'] >= 0 ? '#5EBA47' : '#C0392B';
                $hasDrilldown = !empty($drilldown);
            ?>
                <tr class="cf-cat-row" data-cat-key="<?= htmlspecialchars($catKey) ?>" style="border-bottom:1px solid var(--border-subtle,#F1F3F5);<?= $hasDrilldown ? 'cursor:pointer;' : '' ?>">
                    <td style="padding:10px 12px;color:var(--fg-1,#2C2721);font-weight:500;">
                        <?php if ($hasDrilldown): ?><span class="cf-chev" style="display:inline-block;width:12px;color:#7F8392;font-size:10px;transition:transform .15s;">▶</span><?php else: ?><span style="display:inline-block;width:12px;"></span><?php endif; ?>
                        <?= htmlspecialchars($catLabel) ?>
                    </td>
                    <td style="padding:10px 12px;text-align:right;color:#575964;font-family:monospace;"><?= htmlspecialchars($fmt($row['num_movements'])) ?></td>
                    <td style="padding:10px 12px;text-align:right;color:#5EBA47;font-family:monospace;"><?= $row['ingreso'] > 0 ? '+' . htmlspecialchars($fmtFull($row['ingreso'])) : '—' ?></td>
                    <td style="padding:10px 12px;text-align:right;color:#C0392B;font-family:monospace;"><?= $row['egreso'] > 0 ? '-' . htmlspecialchars($fmtFull($row['egreso'])) : '—' ?></td>
                    <td style="padding:10px 12px;text-align:right;color:<?= $netoColorRow ?>;font-family:monospace;font-weight:700;"><?= ($row['neto'] >= 0 ? '+' : '') . htmlspecialchars($fmtFull($row['neto'])) ?></td>
                </tr>
                <?php if ($hasDrilldown): ?>
                <tr class="cf-cat-detail" data-cat-key="<?= htmlspecialchars($catKey) ?>" style="display:<?= $isPdf ? 'table-row' : 'none' ?>;background:#FAFBFC;">
                    <td colspan="5" style="padding:0;">
                        <div style="padding:8px 24px 14px 36px;">
                            <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#7F8392;font-weight:700;margin-bottom:6px;">Top conceptos en <?= htmlspecialchars($catLabel) ?></div>
                            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                                <thead>
                                    <tr style="border-bottom:1px solid var(--border-default,#DDDFE8);">
                                        <th style="padding:5px 8px;text-align:left;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;letter-spacing:0.3px;width:80px;">Fecha</th>
                                        <th style="padding:5px 8px;text-align:left;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;letter-spacing:0.3px;">Concepto</th>
                                        <th style="padding:5px 8px;text-align:left;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;letter-spacing:0.3px;width:80px;">Tipo</th>
                                        <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;letter-spacing:0.3px;width:120px;">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($drilldown as $m):
                                        $movColor = $m['is_ingreso'] ? '#5EBA47' : '#C0392B';
                                        $movSign = $m['is_ingreso'] ? '+' : '-';
                                    ?>
                                        <tr style="border-bottom:1px solid #F1F3F5;">
                                            <td style="padding:6px 8px;color:#575964;font-family:monospace;font-size:11px;white-space:nowrap;"><?= htmlspecialchars(date('d/m/y', strtotime($m['fecha']))) ?></td>
                                            <td style="padding:6px 8px;color:var(--fg-1,#2C2721);">
                                                <?= htmlspecialchars($m['concepto']) ?>
                                                <?php if (!empty($m['document_number'])): ?><span style="color:#AEAAA6;font-family:monospace;font-size:10px;margin-left:4px;">#<?= htmlspecialchars($m['document_number']) ?></span><?php endif; ?>
                                            </td>
                                            <td style="padding:6px 8px;color:#7F8392;font-size:10px;">
                                                <span style="display:inline-block;padding:1px 5px;border-radius:8px;background:<?= $m['is_ingreso'] ? '#E5F5DD' : '#FCEAE7' ?>;color:<?= $movColor ?>;font-weight:600;">
                                                    <?= htmlspecialchars($m['movement_type']) ?>
                                                </span>
                                                <span style="color:#AEAAA6;margin-left:3px;"><?= htmlspecialchars($m['source_type']) ?></span>
                                            </td>
                                            <td style="padding:6px 8px;text-align:right;color:<?= $movColor ?>;font-family:monospace;font-weight:700;"><?= $movSign ?><?= htmlspecialchars($fmtFull($m['amount'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            <?php endforeach; endif; ?>
            <?php if ($totals && !empty($rows)): ?>
                <tr style="background:var(--bg-subtle,#F1F3F5);font-weight:700;border-top:2px solid var(--mam-blue-dark,#2B3164);">
                    <td style="padding:10px 12px;color:var(--mam-blue-dark,#2B3164);">TOTAL</td>
                    <td style="padding:10px 12px;text-align:right;color:var(--mam-blue-dark,#2B3164);font-family:monospace;"><?= htmlspecialchars($fmt($totals['num_movements'])) ?></td>
                    <td style="padding:10px 12px;text-align:right;color:#5EBA47;font-family:monospace;">+<?= htmlspecialchars($fmtFull($totals['ingreso'])) ?></td>
                    <td style="padding:10px 12px;text-align:right;color:#C0392B;font-family:monospace;">-<?= htmlspecialchars($fmtFull($totals['egreso'])) ?></td>
                    <td style="padding:10px 12px;text-align:right;color:<?= $totals['neto'] >= 0 ? '#5EBA47' : '#C0392B' ?>;font-family:monospace;"><?= ($totals['neto'] >= 0 ? '+' : '') . htmlspecialchars($fmtFull($totals['neto'])) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// Flag para que _layout.php emita el script de toggle FUERA del mount Vue.
// Vue strippea <script> dentro del template (mismo issue que el picker en v1.30.14).
if (!$isPdf) $GLOBALS['__cf_drilldown_script'] = true;
?>
