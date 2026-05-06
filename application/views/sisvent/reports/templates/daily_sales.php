<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Template custom para Análisis de Ventas (v1.30.20).
 *
 * Estructura:
 *   1. KPI strip (4 tiles, lenguaje DS)
 *   2. SVG line chart — timeline current + prev (dashed)
 *   3. SVG horizontal bar — top 10 vendedores
 *   4. Tabla detalle (genérica)
 *
 * @var array $data    kpis + timeline + by_vendor + group_by + columns + rows + totals
 * @var array $params
 * @var bool  $pdf_mode (opcional)
 */

$kpis      = $data['kpis']      ?? [];
$timeline  = $data['timeline']  ?? [];
$byVendor  = $data['by_vendor'] ?? [];
$groupBy   = $data['group_by']  ?? 'day';
$columns   = $data['columns']   ?? [];
$rows      = $data['rows']      ?? [];
$totals    = $data['totals']    ?? null;
$isPdf     = !empty($pdf_mode);

// Helpers de formato
$fmt = function ($val, $decimals = 0) {
    if (!is_numeric($val)) return $val;
    return number_format((float)$val, $decimals, ',', '.');
};
$fmtCurrency = function ($val) use ($fmt) {
    if (!is_numeric($val)) return $val;
    $abs = abs((float)$val);
    if ($abs >= 1000000000) return '$' . $fmt((float)$val / 1000000000, 1) . 'B';
    if ($abs >= 1000000)    return '$' . $fmt((float)$val / 1000000, 1) . 'M';
    if ($abs >= 1000)       return '$' . $fmt((float)$val / 1000, 0) . 'K';
    return '$' . $fmt($val);
};

// Tone para el delta vs prev
$growth = $kpis['growth_pct'] ?? null;
$growthTone = 'fg4';
$growthSymbol = '·';
if ($growth !== null) {
    if ($growth > 0.5)       { $growthTone = 'up'; $growthSymbol = '▲'; }
    elseif ($growth < -0.5)  { $growthTone = 'dn'; $growthSymbol = '▼'; }
}
$growthLabel = $growth === null
    ? 'sin datos previos'
    : $growthSymbol . ' ' . $fmt(abs($growth), 1) . '% vs ' . ($kpis['prev_label'] ?? '');
?>

<!-- ============== KPI STRIP ============== -->
<?php
$kpiTiles = [
    ['eyebrow' => 'Total ventas',     'value' => $fmtCurrency($kpis['total'] ?? 0),      'delta' => $growthLabel,                                                  'delta_tone' => $growthTone, 'accent' => 'var(--mam-green-program, #5EBA47)', 'pdf_color' => '#5EBA47'],
    ['eyebrow' => '# Facturas',       'value' => $fmt($kpis['count'] ?? 0),              'delta' => 'Periodo previo: ' . $fmt($kpis['prev_count'] ?? 0),           'delta_tone' => 'fg4',       'accent' => 'var(--mam-blue-petroleo, #4487A0)', 'pdf_color' => '#4487A0'],
    ['eyebrow' => 'Ticket promedio',  'value' => $fmtCurrency($kpis['avg_ticket'] ?? 0), 'delta' => '',                                                            'delta_tone' => 'fg4',       'accent' => 'var(--mam-blue-dark, #2B3164)',     'pdf_color' => '#2B3164'],
    ['eyebrow' => 'Periodo anterior', 'value' => $fmtCurrency($kpis['prev_total'] ?? 0), 'delta' => $kpis['prev_label'] ?? '',                                     'delta_tone' => 'fg4',       'accent' => 'var(--mam-gray-medium, #AEAAA6)',   'pdf_color' => '#AEAAA6'],
];
?>
<?php if ($isPdf): ?>
    <table cellspacing="0" cellpadding="0" style="width:100%;margin-bottom:14px;border-collapse:separate;border-spacing:6px 0;">
        <tr>
            <?php foreach ($kpiTiles as $t): ?>
                <td style="width:25%;border:1px solid #DDDFE8;border-left:3px solid <?= $t['pdf_color'] ?>;padding:10px 12px;background:#FAFBFC;">
                    <div style="font-size:9px;font-weight:700;text-transform:uppercase;color:#7F8392;letter-spacing:0.5px;"><?= htmlspecialchars($t['eyebrow']) ?></div>
                    <div style="font-size:18px;font-weight:800;color:#2B3164;margin-top:3px;"><?= $t['value'] ?></div>
                    <?php if ($t['delta']): ?><div style="font-size:9px;color:#7F8392;margin-top:2px;"><?= htmlspecialchars($t['delta']) ?></div><?php endif; ?>
                </td>
            <?php endforeach; ?>
        </tr>
    </table>
<?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px;">
        <?php foreach ($kpiTiles as $t): $this->load->view('sisvent/design-system/_kpi_tile', $t); endforeach; ?>
    </div>
<?php endif; ?>

<!-- ============== LÍNEA TEMPORAL ============== -->
<?php
$bucketTitle = ['day' => 'Día', 'week' => 'Semana', 'month' => 'Mes', 'vendor' => 'Día'][$groupBy] ?? 'Día';

if (!empty($timeline)):
    // Calcular escala
    $maxVal = 0;
    foreach ($timeline as $b) {
        $maxVal = max($maxVal, $b['total'], $b['prev_total']);
    }
    $maxVal = $maxVal ?: 1;

    $w = 1100; $h = 280; $padL = 60; $padR = 20; $padT = 20; $padB = 50;
    $innerW = $w - $padL - $padR;
    $innerH = $h - $padT - $padB;
    $n = count($timeline);
    $stepX = $n > 1 ? $innerW / ($n - 1) : 0;

    // Path strings
    $currPts = [];
    $prevPts = [];
    foreach ($timeline as $i => $b) {
        $x = $padL + ($i * $stepX);
        $yC = $padT + $innerH - (($b['total']      / $maxVal) * $innerH);
        $yP = $padT + $innerH - (($b['prev_total'] / $maxVal) * $innerH);
        $currPts[] = round($x, 1) . ',' . round($yC, 1);
        $prevPts[] = round($x, 1) . ',' . round($yP, 1);
    }
    $currPath = 'M ' . implode(' L ', $currPts);
    $prevPath = 'M ' . implode(' L ', $prevPts);

    // Gridlines (5 horizontales)
    $gridLines = [];
    for ($g = 0; $g <= 4; $g++) {
        $y = $padT + ($innerH * $g / 4);
        $val = $maxVal * (1 - $g / 4);
        $gridLines[] = ['y' => $y, 'label' => $fmtCurrency($val)];
    }
?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;padding:16px 20px;margin-bottom:18px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h3 style="margin:0;font-size:14px;font-weight:700;color:var(--mam-blue-dark,#2B3164);text-transform:uppercase;letter-spacing:0.5px;">Ventas por <?= htmlspecialchars(strtolower($bucketTitle)) ?></h3>
        <div style="display:flex;gap:14px;font-size:11px;color:var(--fg-3,#AEAAA6);">
            <span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:14px;height:2px;background:var(--mam-blue-petroleo,#4487A0);"></span>Periodo actual</span>
            <span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:14px;height:2px;background:var(--mam-gray-medium,#AEAAA6);border-bottom:1px dashed var(--mam-gray-medium,#AEAAA6);"></span>Periodo anterior</span>
        </div>
    </div>
    <svg viewBox="0 0 <?= $w ?> <?= $h ?>" preserveAspectRatio="xMidYMid meet" style="width:100%;height:280px;display:block;">
        <!-- gridlines + Y labels -->
        <?php foreach ($gridLines as $gl): ?>
            <line x1="<?= $padL ?>" y1="<?= $gl['y'] ?>" x2="<?= $w - $padR ?>" y2="<?= $gl['y'] ?>" stroke="#F1F3F5" stroke-width="1"/>
            <text x="<?= $padL - 8 ?>" y="<?= $gl['y'] + 4 ?>" text-anchor="end" font-size="10" fill="#AEAAA6" font-family="monospace"><?= htmlspecialchars($gl['label']) ?></text>
        <?php endforeach; ?>

        <!-- Eje X labels (cada N para no saturar) -->
        <?php
        $skipEvery = max(1, (int) ceil($n / 14));
        foreach ($timeline as $i => $b):
            if ($i % $skipEvery !== 0 && $i !== $n - 1) continue;
            $x = $padL + ($i * $stepX);
        ?>
            <text x="<?= round($x, 1) ?>" y="<?= $h - $padB + 18 ?>" text-anchor="middle" font-size="10" fill="#575964"><?= htmlspecialchars($b['label']) ?></text>
        <?php endforeach; ?>

        <!-- Línea periodo previo (dashed gris) -->
        <path d="<?= $prevPath ?>" fill="none" stroke="#AEAAA6" stroke-width="1.5" stroke-dasharray="4 3" opacity="0.7"/>

        <!-- Línea periodo actual (sólida petróleo) -->
        <path d="<?= $currPath ?>" fill="none" stroke="#4487A0" stroke-width="2.5"/>

        <!-- Puntos en periodo actual -->
        <?php foreach ($timeline as $i => $b):
            $x = $padL + ($i * $stepX);
            $y = $padT + $innerH - (($b['total'] / $maxVal) * $innerH);
        ?>
            <circle cx="<?= round($x, 1) ?>" cy="<?= round($y, 1) ?>" r="3" fill="#4487A0"/>
            <title><?= htmlspecialchars($b['label']) ?>: <?= htmlspecialchars($fmtCurrency($b['total'])) ?> · <?= (int)$b['num_invoices'] ?> facturas</title>
        <?php endforeach; ?>
    </svg>
</div>
<?php endif; ?>

<!-- ============== TOP VENDEDORES (BAR CHART HORIZONTAL) ============== -->
<?php if (!empty($byVendor)):
    $maxVendor = $byVendor[0]['total'] ?: 1;
?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;padding:16px 20px;margin-bottom:18px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <h3 style="margin:0 0 14px;font-size:14px;font-weight:700;color:var(--mam-blue-dark,#2B3164);text-transform:uppercase;letter-spacing:0.5px;">Top vendedores</h3>
    <div style="display:flex;flex-direction:column;gap:8px;">
        <?php foreach ($byVendor as $v):
            $pct = ($v['total'] / $maxVendor) * 100;
        ?>
            <div style="display:grid;grid-template-columns:180px 1fr 100px;gap:12px;align-items:center;font-size:13px;">
                <div style="color:var(--fg-1,#2C2721);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($v['vendor_name']) ?>">
                    <?= htmlspecialchars($v['vendor_name']) ?>
                </div>
                <div style="background:var(--bg-subtle,#F1F3F5);border-radius:4px;height:20px;position:relative;overflow:hidden;">
                    <div style="background:linear-gradient(90deg, #4487A0 0%, #5EBA47 100%);height:100%;width:<?= round($pct, 1) ?>%;border-radius:4px;"></div>
                </div>
                <div style="text-align:right;font-weight:700;color:var(--mam-blue-dark,#2B3164);font-family:monospace;">
                    <?= htmlspecialchars($fmtCurrency($v['total'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============== TABLA DETALLE ============== -->
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:var(--mam-blue-dark,#2B3164);color:#fff;">
                    <?php foreach ($columns as $col): ?>
                        <th style="padding:10px 12px;text-align:<?= ($col['type'] ?? '') === 'currency' || ($col['type'] ?? '') === 'number' ? 'right' : 'left' ?>;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">
                            <?= htmlspecialchars($col['label']) ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= count($columns) ?>" style="padding:24px;text-align:center;color:var(--fg-3,#AEAAA6);">No hay ventas en el periodo.</td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <tr style="border-bottom:1px solid var(--border-subtle,#F1F3F5);">
                        <?php foreach ($columns as $col):
                            $key = $col['key']; $val = $row[$key] ?? '';
                            $type = $col['type'] ?? 'text'; $decimals = $col['decimals'] ?? 0;
                            $align = ($type === 'currency' || $type === 'number') ? 'right' : 'left';
                            $formatted = $val;
                            if ($type === 'currency' && is_numeric($val)) $formatted = '$' . $fmt($val, $decimals);
                            elseif ($type === 'number' && is_numeric($val)) $formatted = $fmt($val, $decimals);
                        ?>
                            <td style="padding:10px 12px;text-align:<?= $align ?>;color:var(--fg-1,#2C2721);">
                                <?= htmlspecialchars((string) $formatted) ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; endif; ?>
                <?php if ($totals && !empty($rows)): ?>
                    <tr style="background:var(--bg-subtle,#F1F3F5);font-weight:700;border-top:2px solid var(--mam-blue-dark,#2B3164);">
                        <?php foreach ($columns as $col):
                            $key = $col['key']; $val = $totals[$key] ?? '';
                            $type = $col['type'] ?? 'text'; $decimals = $col['decimals'] ?? 0;
                            $align = ($type === 'currency' || $type === 'number') ? 'right' : 'left';
                            $formatted = $val;
                            if ($val !== '' && $type === 'currency' && is_numeric($val)) $formatted = '$' . $fmt($val, $decimals);
                            elseif ($val !== '' && $type === 'number' && is_numeric($val)) $formatted = $fmt($val, $decimals);
                        ?>
                            <td style="padding:10px 12px;text-align:<?= $align ?>;color:var(--mam-blue-dark,#2B3164);">
                                <?= htmlspecialchars((string) $formatted) ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
