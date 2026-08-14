<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Template custom Aging (v1.30.30).
 *
 * Estructura:
 *   1. KPI strip (4 tiles)
 *   2. Bar chart 4 buckets con severity colors
 *   3. Tabla por cliente con drill-down expandible (top facturas vencidas)
 */

$kpis      = $data['kpis']      ?? [];
$buckets   = $data['buckets']   ?? [];
$drilldown = $data['drilldown'] ?? [];
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

$criticalPct = $kpis['critical_pct'] ?? 0;
$criticalTone = $criticalPct > 30 ? 'dn' : ($criticalPct > 15 ? 'mid' : 'up');
$criticalColor = $criticalPct > 30 ? '#C0392B' : ($criticalPct > 15 ? '#F39C12' : '#5EBA47');
?>

<!-- ============== KPI STRIP ============== -->
<?php
$kpiTiles = [
    ['eyebrow' => 'Cartera total',      'value' => $fmtCurrency($kpis['total_cart'] ?? 0),       'delta' => ($kpis['num_invoices'] ?? 0) . ' facturas',                                          'delta_tone' => 'fg4', 'accent' => 'var(--mam-blue-petroleo, #4487A0)', 'pdf_color' => '#4487A0'],
    ['eyebrow' => '# Clientes',         'value' => $fmt($kpis['num_clients'] ?? 0),              'delta' => 'con saldo pendiente',                                                                'delta_tone' => 'fg4', 'accent' => 'var(--mam-blue-dark, #2B3164)',     'pdf_color' => '#2B3164'],
    ['eyebrow' => 'Saldo crítico (+90)','value' => $fmtCurrency($kpis['critical_amount'] ?? 0), 'delta' => $fmt($criticalPct, 1) . '% del total',                                                'delta_tone' => $criticalTone, 'accent' => $criticalColor,             'pdf_color' => $criticalColor],
    ['eyebrow' => 'Días promedio',      'value' => $fmt($kpis['avg_days_overdue'] ?? 0, 0) . ' d', 'delta' => 'ponderado por saldo',                                                              'delta_tone' => 'fg4', 'accent' => 'var(--mam-gray-medium, #AEAAA6)',   'pdf_color' => '#AEAAA6'],
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

<!-- ============== BUCKETS BAR CHART ============== -->
<?php if (!empty($buckets) && ($kpis['total_cart'] ?? 0) > 0):
    $maxBucket = max(array_column($buckets, 'total')) ?: 1;
?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;padding:10px 14px;margin-bottom:12px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <h3 style="margin:0 0 8px;font-size:12px;font-weight:700;color:var(--mam-blue-dark,#2B3164);text-transform:uppercase;letter-spacing:0.5px;">Distribución por antigüedad</h3>
    <div style="display:flex;flex-direction:column;gap:5px;">
        <?php foreach ($buckets as $b):
            $widthPct = ($b['total'] / $maxBucket) * 100;
        ?>
            <div style="display:grid;grid-template-columns:90px 1fr 70px 60px;gap:8px;align-items:center;font-size:12px;">
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
                <div style="text-align:right;">
                    <span style="display:inline-block;padding:1px 6px;border-radius:3px;font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;background:<?= $b['color'] ?>;color:#fff;">
                        <?= htmlspecialchars(strtoupper($b['severity'])) ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============== TABLA POR CLIENTE CON DRILL-DOWN ============== -->
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <div style="padding:7px 12px;background:var(--mam-blue-dark,#2B3164);color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;display:flex;justify-content:space-between;align-items:center;">
        <span>Cartera por cliente</span>
        <span style="font-size:9px;opacity:0.85;font-weight:500;">Click en una fila para ver las facturas vencidas</span>
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
                    <tr><td colspan="<?= count($columns) ?>" style="padding:18px;text-align:center;color:var(--fg-3,#AEAAA6);">No hay clientes con saldo pendiente.</td></tr>
                <?php else: foreach ($rows as $row):
                    $cid = (int) $row['idClient'];
                    $invoices = $drilldown[$cid] ?? [];
                    $hasDrill = !empty($invoices);
                ?>
                    <tr class="ag-row" data-cid="<?= $cid ?>" style="border-bottom:1px solid var(--border-subtle,#F1F3F5);<?= $hasDrill ? 'cursor:pointer;' : '' ?>">
                        <?php foreach ($columns as $col):
                            $key = $col['key']; $val = $row[$key] ?? '';
                            $type = $col['type'] ?? 'text'; $decimals = $col['decimals'] ?? 0;
                            $align = ($type === 'currency' || $type === 'number') ? 'right' : 'left';

                            $color = 'var(--fg-1,#2C2721)';
                            $weight = '';
                            if ($key === 'bucket_91_plus' && (float)$val > 0) $color = '#C0392B';
                            elseif ($key === 'bucket_61_90' && (float)$val > 0) $color = '#E67E22';
                            elseif ($key === 'bucket_31_60' && (float)$val > 0) $color = '#F39C12';
                            elseif ($key === 'bucket_current' && (float)$val > 0) $color = '#5EBA47';
                            elseif ($key === 'total') { $weight = 'font-weight:700;'; }

                            $formatted = $val;
                            if ($type === 'currency' && is_numeric($val)) $formatted = ((float)$val > 0 ? '$' . $fmt($val, $decimals) : '—');
                            elseif ($type === 'number' && is_numeric($val)) $formatted = $fmt($val, $decimals);
                            elseif ($type === 'date' && $val) $formatted = date('d/m/y', strtotime($val));
                            elseif ($val === null || $val === '') $formatted = '—';

                            // Chevron solo en la primera columna (Cliente)
                            $isFirst = $col === $columns[0];

                            // Cliente y vendedor: wrap si nombres largos
                            $cellStyle = ($key === 'name' || $key === 'vendor_name') ? 'white-space:normal;word-wrap:break-word;max-width:180px;' : 'white-space:nowrap;';
                            $cellFont = ($type === 'currency' || $type === 'number') ? 'monospace' : 'inherit';
                        ?>
                            <td style="padding:5px 6px;text-align:<?= $align ?>;color:<?= $color ?>;<?= $weight ?><?= $cellStyle ?>font-family:<?= $cellFont ?>;">
                                <?php if ($isFirst && $hasDrill): ?><span class="ag-chev" style="display:inline-block;width:10px;color:#7F8392;font-size:9px;transition:transform .15s;">▶</span><?php elseif ($isFirst): ?><span style="display:inline-block;width:10px;"></span><?php endif; ?>
                                <?= htmlspecialchars((string) $formatted) ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php if ($hasDrill): ?>
                    <tr class="ag-detail" data-cid="<?= $cid ?>" style="display:<?= $isPdf ? 'table-row' : 'none' ?>;background:#FAFBFC;">
                        <td colspan="<?= count($columns) ?>" style="padding:0;">
                            <div style="padding:8px 24px 14px 36px;">
                                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#7F8392;font-weight:700;margin-bottom:6px;">Top facturas más vencidas</div>
                                <table style="width:100%;border-collapse:collapse;font-size:12px;">
                                    <thead>
                                        <tr style="border-bottom:1px solid var(--border-default,#DDDFE8);">
                                            <th style="padding:5px 8px;text-align:left;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;letter-spacing:0.3px;">Factura</th>
                                            <th style="padding:5px 8px;text-align:left;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;letter-spacing:0.3px;">Fecha</th>
                                            <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;letter-spacing:0.3px;">Días</th>
                                            <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;letter-spacing:0.3px;">Total</th>
                                            <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;letter-spacing:0.3px;">Pagado</th>
                                            <th style="padding:5px 8px;text-align:right;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;letter-spacing:0.3px;">Saldo</th>
                                            <th style="padding:5px 8px;text-align:left;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;letter-spacing:0.3px;">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($invoices as $inv):
                                            $days = $inv['days_overdue'];
                                            $daysColor = $days > 90 ? '#C0392B' : ($days > 60 ? '#E67E22' : ($days > 30 ? '#F39C12' : '#5EBA47'));
                                            $stateLabel = $inv['state'] === 0 ? 'pendiente' : 'parcial';
                                        ?>
                                            <tr style="border-bottom:1px solid #F1F3F5;">
                                                <td style="padding:6px 8px;color:var(--mam-blue-dark,#2B3164);font-family:monospace;font-weight:600;"><?= htmlspecialchars($inv['idInvoice']) ?></td>
                                                <td style="padding:6px 8px;color:#575964;font-family:monospace;font-size:11px;"><?= htmlspecialchars(date('d/m/y', strtotime($inv['date']))) ?></td>
                                                <td style="padding:6px 8px;text-align:right;color:<?= $daysColor ?>;font-family:monospace;font-weight:700;"><?= (int)$days ?></td>
                                                <td style="padding:6px 8px;text-align:right;color:#575964;font-family:monospace;"><?= htmlspecialchars($fmtFull($inv['total'])) ?></td>
                                                <td style="padding:6px 8px;text-align:right;color:#5EBA47;font-family:monospace;"><?= $inv['payment'] > 0 ? htmlspecialchars($fmtFull($inv['payment'])) : '—' ?></td>
                                                <td style="padding:6px 8px;text-align:right;color:#C0392B;font-family:monospace;font-weight:700;"><?= htmlspecialchars($fmtFull($inv['balance'])) ?></td>
                                                <td style="padding:6px 8px;color:#7F8392;font-size:10px;">
                                                    <span style="display:inline-block;padding:1px 6px;border-radius:8px;background:<?= $inv['state'] === 0 ? '#FCEAE7' : '#FFF4E6' ?>;color:<?= $inv['state'] === 0 ? '#C0392B' : '#E67E22' ?>;font-weight:600;"><?= $stateLabel ?></span>
                                                </td>
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
                        <?php foreach ($columns as $col):
                            $key = $col['key']; $val = $totals[$key] ?? '';
                            $type = $col['type'] ?? 'text'; $decimals = $col['decimals'] ?? 0;
                            $align = ($type === 'currency' || $type === 'number') ? 'right' : 'left';
                            $color = 'var(--mam-blue-dark,#2B3164)';
                            $formatted = $val;
                            if ($val !== '' && $type === 'currency' && is_numeric($val)) $formatted = '$' . $fmt($val, $decimals);
                            elseif ($val !== '' && $type === 'number' && is_numeric($val)) $formatted = $fmt($val, $decimals);
                        ?>
                            <td style="padding:10px 10px;text-align:<?= $align ?>;color:<?= $color ?>;white-space:nowrap;"><?= htmlspecialchars((string) $formatted) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Flag para que _layout.php emita el script de toggle FUERA del Vue mount.
if (!$isPdf) $GLOBALS['__ag_drilldown_script'] = true;
?>
