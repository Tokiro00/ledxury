<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Template compartido para ClientStatement + ProviderStatement (v1.30.31).
 *
 * Estructura:
 *   1. KPI strip 4 tiles (apertura / facturado o comprado / pagado / saldo final)
 *   2. Saldo global (banner) si difiere del saldo del periodo
 *   3. Timeline SVG: evolucion del saldo en el periodo
 *   4. Tabla cronologica con saldo corrido
 *
 * El parametro $data['side'] (client|provider) ajusta etiquetas y semantica:
 *   client   -> facturamos/cobramos · saldo a cobrar
 *   provider -> nos facturan/pagamos · saldo a pagar
 */

$kpis     = $data['kpis']     ?? [];
$timeline = $data['timeline'] ?? [];
$side     = $data['side']     ?? 'client';
$columns  = $data['columns']  ?? [];
$rows     = $data['rows']     ?? [];
$totals   = $data['totals']   ?? null;
$isPdf    = !empty($pdf_mode);

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

$labels = $side === 'provider' ? [
    'in' => 'Nos facturó',
    'out' => 'Pagamos',
    'closing' => 'Saldo final (le debemos)',
    'global' => 'Saldo global con proveedor',
    'in_color' => '#C0392B', // les debemos -> rojo
    'out_color' => '#5EBA47',// les pagamos -> verde
] : [
    'in' => 'Facturado',
    'out' => 'Cobrado',
    'closing' => 'Saldo final (nos debe)',
    'global' => 'Saldo global cliente',
    'in_color' => '#5EBA47', // facturamos = ingreso futuro -> verde (positivo para nosotros)
    'out_color' => '#4487A0',// cobramos -> azul
];

$closing = $kpis['closing'] ?? 0;
$closingColor = $closing > 0 ? ($side === 'provider' ? '#C0392B' : '#4487A0') : '#5EBA47';
$closingColorPdf = $closingColor;

$global = $kpis['global'] ?? 0;
$globalDiff = abs($global - $closing) > 1; // muestra alerta si saldo global != saldo periodo
?>

<!-- ============== KPI STRIP ============== -->
<?php
$kpiTiles = [
    ['eyebrow' => 'Saldo inicial',     'value' => $fmtCurrency($kpis['opening'] ?? 0),    'delta' => '',                                                              'delta_tone' => 'fg4', 'accent' => 'var(--mam-blue-dark, #2B3164)',     'pdf_color' => '#2B3164'],
    ['eyebrow' => $labels['in'],        'value' => $fmtCurrency($kpis['in_period'] ?? 0),  'delta' => ($kpis['num_invoices'] ?? 0) . ' factura' . (($kpis['num_invoices'] ?? 0) === 1 ? '' : 's'), 'delta_tone' => 'fg4', 'accent' => $labels['in_color'], 'pdf_color' => $labels['in_color']],
    ['eyebrow' => $labels['out'],       'value' => $fmtCurrency($kpis['out_period'] ?? 0), 'delta' => ($kpis['num_payments'] ?? 0) . ' pago' . (($kpis['num_payments'] ?? 0) === 1 ? '' : 's'),  'delta_tone' => 'fg4', 'accent' => $labels['out_color'], 'pdf_color' => $labels['out_color']],
    ['eyebrow' => $labels['closing'],   'value' => $fmtCurrency($closing),                  'delta' => '',                                                              'delta_tone' => 'fg4', 'accent' => $closingColor,                       'pdf_color' => $closingColorPdf],
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

<!-- ============== SALDO GLOBAL (banner si difiere) ============== -->
<?php if ($globalDiff): ?>
<div style="background:#FFF4E6;border:1px solid #FFC78A;border-left:3px solid #E67E22;border-radius:5px;padding:7px 12px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;">
    <div>
        <div style="font-size:9px;font-weight:700;text-transform:uppercase;color:#9A6028;letter-spacing:0.5px;"><?= htmlspecialchars($labels['global']) ?></div>
        <div style="font-size:10px;color:#7F8392;">Total de facturas no canceladas (fuera del rango también)</div>
    </div>
    <div style="font-size:15px;font-weight:800;color:#9A6028;font-family:monospace;"><?= htmlspecialchars($fmtFull($global)) ?></div>
</div>
<?php endif; ?>

<!-- ============== TIMELINE: EVOLUCIÓN DEL SALDO ============== -->
<?php if (!empty($timeline) && count($timeline) > 1):
    $maxAbs = 0;
    foreach ($timeline as $p) { $maxAbs = max($maxAbs, abs($p['saldo'])); }
    $maxAbs = $maxAbs ?: 1;

    $hasNeg = false;
    foreach ($timeline as $p) { if ($p['saldo'] < 0) { $hasNeg = true; break; } }

    $w = 1100; $h = 170; $padL = 65; $padR = 18; $padT = 14; $padB = 30;
    $innerW = $w - $padL - $padR;
    $innerH = $h - $padT - $padB;
    $zeroY = $hasNeg ? $padT + ($innerH / 2) : $padT + $innerH;
    $rangeH = $hasNeg ? $innerH / 2 : $innerH;
    $n = count($timeline);
    $stepX = $n > 1 ? $innerW / ($n - 1) : 0;

    $points = [];
    foreach ($timeline as $i => $p) {
        $x = $padL + ($i * $stepX);
        $y = $zeroY - (($p['saldo'] / $maxAbs) * $rangeH);
        $points[] = round($x, 1) . ',' . round($y, 1);
    }
    $path = 'M ' . implode(' L ', $points);
    $areaPath = $path . ' L ' . round($padL + (($n - 1) * $stepX), 1) . ',' . $zeroY . ' L ' . $padL . ',' . $zeroY . ' Z';

    $accentColor = $closing > 0 ? '#4487A0' : '#5EBA47';
?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;padding:10px 14px;margin-bottom:12px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <h3 style="margin:0 0 8px;font-size:12px;font-weight:700;color:var(--mam-blue-dark,#2B3164);text-transform:uppercase;letter-spacing:0.5px;">Evolución del saldo</h3>
    <svg viewBox="0 0 <?= $w ?> <?= $h ?>" preserveAspectRatio="xMidYMid meet" style="width:100%;height:170px;display:block;">
        <line x1="<?= $padL ?>" y1="<?= $zeroY ?>" x2="<?= $w - $padR ?>" y2="<?= $zeroY ?>" stroke="#7F8392" stroke-width="1"/>
        <text x="<?= $padL - 8 ?>" y="<?= $zeroY + 4 ?>" text-anchor="end" font-size="10" fill="#AEAAA6" font-family="monospace">0</text>
        <text x="<?= $padL - 8 ?>" y="<?= $padT + 12 ?>" text-anchor="end" font-size="10" fill="#575964" font-family="monospace"><?= htmlspecialchars($fmtCurrency($maxAbs)) ?></text>
        <?php if ($hasNeg): ?>
            <text x="<?= $padL - 8 ?>" y="<?= $h - $padB ?>" text-anchor="end" font-size="10" fill="#575964" font-family="monospace">-<?= htmlspecialchars($fmtCurrency($maxAbs)) ?></text>
        <?php endif; ?>

        <?php
        $skipEvery = max(1, (int) ceil($n / 14));
        foreach ($timeline as $i => $p):
            if ($i % $skipEvery !== 0 && $i !== $n - 1) continue;
            $x = $padL + ($i * $stepX);
        ?>
            <text x="<?= round($x, 1) ?>" y="<?= $h - $padB + 18 ?>" text-anchor="middle" font-size="10" fill="#575964"><?= htmlspecialchars($p['label']) ?></text>
        <?php endforeach; ?>

        <path d="<?= $areaPath ?>" fill="<?= $accentColor ?>" opacity="0.12"/>
        <path d="<?= $path ?>" fill="none" stroke="<?= $accentColor ?>" stroke-width="2.5"/>

        <?php
        // Solo render points cada N para no saturar
        $pointEvery = max(1, (int) ceil($n / 30));
        foreach ($timeline as $i => $p):
            if ($i % $pointEvery !== 0 && $i !== $n - 1) continue;
            $x = $padL + ($i * $stepX);
            $y = $zeroY - (($p['saldo'] / $maxAbs) * $rangeH);
        ?>
            <circle cx="<?= round($x, 1) ?>" cy="<?= round($y, 1) ?>" r="2.5" fill="<?= $accentColor ?>"/>
            <title><?= htmlspecialchars(date('d/m/Y', strtotime($p['date']))) ?>: <?= htmlspecialchars($fmtCurrency($p['saldo'])) ?></title>
        <?php endforeach; ?>
    </svg>
</div>
<?php endif; ?>

<!-- ============== TABLA CRONOLOGICA ============== -->
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <div style="padding:7px 12px;background:var(--mam-blue-dark,#2B3164);color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">
        Movimientos del periodo
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;table-layout:auto;">
            <thead>
                <tr style="background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);">
                    <?php foreach ($columns as $col): ?>
                        <th style="padding:5px 8px;text-align:<?= ($col['type'] ?? '') === 'currency' || ($col['type'] ?? '') === 'number' ? 'right' : 'left' ?>;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.4px;white-space:nowrap;"><?= htmlspecialchars($col['label']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fila inicial: saldo apertura
                if (($kpis['opening'] ?? 0) != 0):
                ?>
                    <tr style="background:#F8F8FA;border-bottom:1px solid var(--border-subtle,#F1F3F5);font-style:italic;color:#7F8392;">
                        <td style="padding:5px 8px;font-size:11px;" colspan="<?= count($columns) - 1 ?>">Saldo de apertura al <?= htmlspecialchars(!empty($params['desde']) ? date('d/m/Y', strtotime($params['desde'])) : '—') ?></td>
                        <td style="padding:5px 8px;text-align:right;font-family:monospace;font-weight:700;color:#2B3164;font-size:11px;"><?= htmlspecialchars($fmtFull($kpis['opening'])) ?></td>
                    </tr>
                <?php endif; ?>

                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= count($columns) ?>" style="padding:18px;text-align:center;color:var(--fg-3,#AEAAA6);">No hay movimientos en el periodo.</td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <tr style="border-bottom:1px solid var(--border-subtle,#F1F3F5);">
                        <?php foreach ($columns as $col):
                            $key = $col['key']; $val = $row[$key] ?? '';
                            $type = $col['type'] ?? 'text'; $decimals = $col['decimals'] ?? 0;
                            $align = ($type === 'currency' || $type === 'number') ? 'right' : 'left';

                            $color = 'var(--fg-1,#2C2721)';
                            $weight = '';
                            if ($key === 'debito'  && (float)$val > 0) $color = $labels['out_color'];
                            elseif ($key === 'credito' && (float)$val > 0) $color = $labels['in_color'];
                            elseif ($key === 'saldo') {
                                $color = $val > 0 ? '#2B3164' : ($val < 0 ? '#5EBA47' : '#7F8392');
                                $weight = 'font-weight:700;';
                            }

                            $formatted = $val;
                            if ($type === 'currency' && is_numeric($val)) $formatted = (float)$val == 0 ? '—' : '$' . $fmt($val, $decimals);
                            elseif ($type === 'number' && is_numeric($val)) $formatted = $fmt($val, $decimals);
                            elseif ($type === 'date' && $val) $formatted = date('d/m/Y', strtotime($val));

                            // Descripcion: permite wrap para evitar overflow horizontal
                            $cellStyle = ($key === 'descripcion') ? 'white-space:normal;word-wrap:break-word;max-width:320px;' : 'white-space:nowrap;';
                        ?>
                            <td style="padding:5px 8px;text-align:<?= $align ?>;color:<?= $color ?>;<?= $weight ?><?= $cellStyle ?>font-family:<?= ($type === 'currency' || $type === 'number') ? 'monospace' : 'inherit' ?>;">
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
                            $color = 'var(--mam-blue-dark,#2B3164)';
                            if ($key === 'saldo' && is_numeric($val) && $val < 0) $color = '#5EBA47';
                            $formatted = $val;
                            if ($val !== '' && $type === 'currency' && is_numeric($val)) $formatted = '$' . $fmt($val, $decimals);
                            elseif ($val !== '' && $type === 'number' && is_numeric($val)) $formatted = $fmt($val, $decimals);
                        ?>
                            <td style="padding:7px 8px;text-align:<?= $align ?>;color:<?= $color ?>;white-space:nowrap;font-family:<?= ($type === 'currency' || $type === 'number') ? 'monospace' : 'inherit' ?>;"><?= htmlspecialchars((string) $formatted) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
