<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Template carrier_collections — Recaudo Transportadora.
 *
 * Estructura:
 *   1. KPI strip (4 tiles): Entregadas, Recibido carrier, Pendiente, Días prom.
 *   2. Bar chart aging (4 buckets) sobre las pendientes
 *   3. Resumen por transportadora
 *   4. Tabla detalle de guías
 */

$kpis      = $data['kpis']      ?? [];
$buckets   = $data['buckets']   ?? [];
$byCarrier = $data['by_carrier'] ?? [];
$columns   = $data['columns']   ?? [];
$rows      = $data['rows']      ?? [];
$totals    = $data['totals']    ?? null;
$isPdf     = !empty($pdf_mode);

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
$fmtDate = function ($val) {
    if (empty($val) || $val === '0000-00-00' || $val === '0000-00-00 00:00:00') return '—';
    $ts = strtotime($val);
    return $ts ? date('d/m/Y', $ts) : $val;
};

$pctRecaudo = $kpis['pct_recaudo'] ?? 0;
$pctTone   = $pctRecaudo >= 90 ? 'up' : ($pctRecaudo >= 70 ? 'mid' : 'dn');
$pctColor  = $pctRecaudo >= 90 ? '#5EBA47' : ($pctRecaudo >= 70 ? '#F39C12' : '#C0392B');
?>

<!-- ============== KPI STRIP ============== -->
<?php
$kpiTiles = [
    [
        'eyebrow' => 'Entregadas',
        'value' => $fmtCurrency($kpis['entregadas_valor'] ?? 0),
        'delta' => $fmt($kpis['entregadas_count'] ?? 0) . ' guías cobradas al cliente',
        'delta_tone' => 'fg4',
        'accent' => 'var(--mam-blue-petroleo, #4487A0)',
        'pdf_color' => '#4487A0',
    ],
    [
        'eyebrow' => 'Recibido del carrier',
        'value' => $fmtCurrency($kpis['pagadas_valor'] ?? 0),
        'delta' => $fmt($pctRecaudo, 1) . '% del entregado',
        'delta_tone' => $pctTone,
        'accent' => $pctColor,
        'pdf_color' => $pctColor,
    ],
    [
        'eyebrow' => 'Pendiente carrier',
        'value' => $fmtCurrency($kpis['pendientes_valor'] ?? 0),
        'delta' => $fmt($kpis['pendientes_count'] ?? 0) . ' guías sin pagar',
        'delta_tone' => ($kpis['pendientes_valor'] ?? 0) > 0 ? 'dn' : 'up',
        'accent' => '#C0392B',
        'pdf_color' => '#C0392B',
    ],
    [
        'eyebrow' => 'Días prom. cobro',
        'value' => $fmt($kpis['dias_prom_cobro'] ?? 0, 1) . ' d',
        'delta' => 'entrega → remesa carrier',
        'delta_tone' => 'fg4',
        'accent' => 'var(--mam-gray-medium, #AEAAA6)',
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

<!-- ============== AGING BUCKETS (solo si hay pendientes) ============== -->
<?php if (!empty($buckets) && ($kpis['pendientes_valor'] ?? 0) > 0):
    $maxBucket = max(array_column($buckets, 'total')) ?: 1;
?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;padding:10px 14px;margin-bottom:12px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <h3 style="margin:0 0 8px;font-size:12px;font-weight:700;color:var(--mam-blue-dark,#2B3164);text-transform:uppercase;letter-spacing:0.5px;">Aging — días desde la entrega (sobre $<?= $fmt($kpis['pendientes_valor'] ?? 0) ?> pendientes)</h3>
    <div style="display:flex;flex-direction:column;gap:5px;">
        <?php foreach ($buckets as $b):
            $widthPct = ($b['total'] / $maxBucket) * 100;
        ?>
            <div style="display:grid;grid-template-columns:90px 1fr 70px 60px 70px;gap:8px;align-items:center;font-size:12px;">
                <div style="color:<?= $b['color'] ?>;font-weight:700;"><?= htmlspecialchars($b['label']) ?></div>
                <div style="background:var(--bg-subtle,#F1F3F5);height:18px;border-radius:3px;position:relative;overflow:hidden;">
                    <div style="background:<?= $b['color'] ?>;height:100%;width:<?= round($widthPct, 1) ?>%;border-radius:3px;"></div>
                    <?php if ($b['total'] > 0): ?>
                        <span style="position:absolute;left:7px;top:0;line-height:18px;font-size:10px;font-weight:700;color:#fff;font-family:monospace;"><?= htmlspecialchars($fmtFull($b['total'])) ?></span>
                    <?php endif; ?>
                </div>
                <div style="text-align:right;font-family:monospace;color:#575964;font-size:10px;">
                    <?= htmlspecialchars($fmt($b['pct'], 1)) ?>%
                </div>
                <div style="text-align:right;font-family:monospace;color:#575964;font-size:10px;">
                    <?= (int)$b['count'] ?> guías
                </div>
                <div style="text-align:right;">
                    <span style="display:inline-block;padding:1px 6px;border-radius:3px;font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;background:<?= $b['color'] ?>;color:#fff;">
                        <?= htmlspecialchars($b['severity']) ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============== RESUMEN POR TRANSPORTADORA ============== -->
<?php if (!empty($byCarrier)): ?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;margin-bottom:12px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <div style="padding:7px 12px;background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);font-size:11px;font-weight:700;text-transform:uppercase;color:#7F8392;letter-spacing:0.5px;">
        Resumen por transportadora
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:11px;">
        <thead>
            <tr style="background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);">
                <th style="padding:5px 8px;text-align:left;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.3px;">Carrier</th>
                <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.3px;">Guías</th>
                <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.3px;">Cobrado al cliente</th>
                <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.3px;">Remesado al banco</th>
                <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.3px;">Pendiente carrier</th>
                <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.3px;">% Recaudo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($byCarrier as $bc):
                $totalEntreg = (float)$bc['cobrado_cliente'];
                $pctC = $totalEntreg > 0 ? round(((float)$bc['remesado'] / $totalEntreg) * 100, 1) : 0;
            ?>
            <tr style="border-bottom:1px solid var(--border-subtle,#F1F3F5);">
                <td style="padding:5px 8px;font-weight:600;"><?= htmlspecialchars($bc['carrier']) ?></td>
                <td style="padding:5px 8px;text-align:right;"><?= (int)$bc['count'] ?></td>
                <td style="padding:5px 8px;text-align:right;font-family:monospace;"><?= $fmtFull($bc['cobrado_cliente']) ?></td>
                <td style="padding:5px 8px;text-align:right;font-family:monospace;color:#5EBA47;"><?= $fmtFull($bc['remesado']) ?></td>
                <td style="padding:5px 8px;text-align:right;font-family:monospace;color:<?= $bc['pendiente'] > 0 ? '#C0392B' : '#7F8392' ?>;"><?= $fmtFull($bc['pendiente']) ?></td>
                <td style="padding:5px 8px;text-align:right;font-weight:700;color:<?= $pctC >= 90 ? '#5EBA47' : ($pctC >= 70 ? '#F39C12' : '#C0392B') ?>;"><?= $fmt($pctC, 1) ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ============== TABLA DETALLE ============== -->
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <div style="padding:7px 12px;background:var(--mam-blue-dark,#2B3164);color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">
        Detalle de guías
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);">
                    <?php foreach ($columns as $col): ?>
                        <th style="padding:5px 6px;text-align:<?= ($col['type'] ?? '') === 'currency' || ($col['type'] ?? '') === 'number' ? 'right' : 'left' ?>;font-size:9px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.3px;white-space:nowrap;"><?= htmlspecialchars($col['label']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= count($columns) ?>" style="padding:18px;text-align:center;color:var(--fg-3,#AEAAA6);">No hay guías que coincidan con los filtros.</td></tr>
                <?php else: foreach ($rows as $row):
                    $estado = $row['estado_recaudo'] ?? '';
                    $rowTone = '';
                    if ($estado === 'pendiente_carrier') {
                        $dias = (int)($row['dias_aging'] ?? 0);
                        if ($dias > 30)      $rowTone = 'background:rgba(192,57,43,.06);';
                        elseif ($dias > 15)  $rowTone = 'background:rgba(243,156,18,.06);';
                    }
                ?>
                    <tr style="border-bottom:1px solid var(--border-subtle,#F1F3F5);<?= $rowTone ?>">
                        <?php foreach ($columns as $col):
                            $key = $col['key']; $val = $row[$key] ?? '';
                            $type = $col['type'] ?? 'text';
                            $align = ($type === 'currency' || $type === 'number') ? 'right' : 'left';
                            $cellStyle = "padding:5px 6px;text-align:$align;white-space:nowrap;";
                            if ($type === 'currency' || $type === 'number') $cellStyle .= 'font-family:monospace;';
                            // Color especial por estado en la celda Estado
                            if ($key === 'estado_label') {
                                $colorMap = [
                                    'Pagada' => '#5EBA47',
                                    'Pendiente carrier' => '#C0392B',
                                    'En tránsito' => '#4487A0',
                                    'Devuelta' => '#7F8392',
                                ];
                                $clr = $colorMap[$val] ?? '#575964';
                                echo '<td style="' . $cellStyle . '"><span style="display:inline-block;padding:1px 6px;border-radius:3px;font-size:9px;font-weight:700;background:' . $clr . ';color:#fff;">' . htmlspecialchars($val) . '</span></td>';
                                continue;
                            }
                            if ($val === null || $val === '') {
                                echo '<td style="' . $cellStyle . 'color:#AEAAA6;">—</td>';
                                continue;
                            }
                            switch ($type) {
                                case 'currency':
                                    $color = $key === 'discrepancia' && abs((float)$val) > 100 ? '#C0392B' : '#2B3164';
                                    echo '<td style="' . $cellStyle . 'color:' . $color . ';">' . $fmtFull($val) . '</td>';
                                    break;
                                case 'number':
                                    $color = $key === 'dias_aging' && (int)$val > 30 ? '#C0392B' : ((int)$val > 15 ? '#F39C12' : '#575964');
                                    echo '<td style="' . $cellStyle . 'color:' . $color . ';font-weight:600;">' . $fmt($val) . '</td>';
                                    break;
                                case 'date':
                                    echo '<td style="' . $cellStyle . '">' . htmlspecialchars($fmtDate($val)) . '</td>';
                                    break;
                                default:
                                    echo '<td style="' . $cellStyle . '">' . htmlspecialchars($val) . '</td>';
                            }
                        endforeach; ?>
                    </tr>
                <?php endforeach; endif; ?>
                <?php if ($totals && !empty($rows)): ?>
                    <tr style="background:#F8F8FA;border-top:2px solid var(--mam-blue-dark,#2B3164);font-weight:700;">
                        <?php foreach ($columns as $col):
                            $key = $col['key']; $val = $totals[$key] ?? '';
                            $type = $col['type'] ?? 'text';
                            $align = ($type === 'currency' || $type === 'number') ? 'right' : 'left';
                            $cellStyle = "padding:6px;text-align:$align;font-weight:700;color:#2B3164;";
                            if ($type === 'currency' || $type === 'number') $cellStyle .= 'font-family:monospace;';
                            if (is_numeric($val) && ($type === 'currency' || $type === 'number')) {
                                $val = $type === 'currency' ? $fmtFull($val) : $fmt($val);
                            }
                            echo '<td style="' . $cellStyle . '">' . htmlspecialchars($val) . '</td>';
                        endforeach; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
