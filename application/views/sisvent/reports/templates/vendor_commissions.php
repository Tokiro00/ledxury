<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Template VendorCommissions (v1.30.43).
 *
 * 1. KPI strip
 * 2. Bar horizontal por vendedor (neto + comisión)
 * 3. Tabla con drill expandible (top 10 facturas por vendedor)
 */

$kpis      = $data['kpis']      ?? [];
$byVendor  = $data['by_vendor'] ?? [];
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
?>

<!-- KPI strip -->
<?php
$top = $kpis['top_vendor'] ?? null;
$kpiTiles = [
    ['eyebrow' => 'Recaudado (neto)',   'value' => $fmtCurrency($kpis['total_collected'] ?? 0),   'delta' => 'facturas pagadas en el período',                                    'delta_tone' => 'fg4', 'accent' => 'var(--mam-blue-petroleo, #4487A0)', 'pdf_color' => '#4487A0'],
    ['eyebrow' => 'Total comisión',      'value' => $fmtCurrency($kpis['total_commission'] ?? 0), 'delta' => $fmt($kpis['avg_commission_pct'] ?? 0, 2) . '% promedio',           'delta_tone' => 'fg4', 'accent' => 'var(--mam-green-program, #5EBA47)', 'pdf_color' => '#5EBA47'],
    ['eyebrow' => '# Vendedores',        'value' => $fmt($kpis['num_vendors'] ?? 0),              'delta' => 'con cobros en el período',                                          'delta_tone' => 'fg4', 'accent' => 'var(--mam-blue-dark, #2B3164)',     'pdf_color' => '#2B3164'],
    ['eyebrow' => 'Top vendedor',        'value' => $top ? htmlspecialchars($top['vendor_name']) : '—', 'delta' => $top ? $fmtCurrency($top['commission']) . ' comisión' : '',  'delta_tone' => 'fg4', 'accent' => 'var(--mam-gray-medium, #AEAAA6)',   'pdf_color' => '#AEAAA6'],
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

<!-- Bar horizontal: comisión por vendedor (top 15) -->
<?php if (!empty($byVendor)):
    $top15 = array_slice($byVendor, 0, 15);
    $maxCom = max(array_column($top15, 'commission')) ?: 1;
?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;padding:10px 14px;margin-bottom:12px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <h3 style="margin:0 0 8px;font-size:12px;font-weight:700;color:var(--mam-blue-dark,#2B3164);text-transform:uppercase;letter-spacing:0.5px;">Comisión por recaudo — top 15</h3>
    <div style="display:flex;flex-direction:column;gap:5px;">
        <?php foreach ($top15 as $v):
            $widthPct = ($v['commission'] / $maxCom) * 100;
        ?>
            <div style="display:grid;grid-template-columns:160px 1fr 100px;gap:8px;align-items:center;font-size:12px;">
                <div style="color:var(--fg-1,#2C2721);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($v['vendor_name']) ?>">
                    <?= htmlspecialchars($v['vendor_name']) ?>
                    <?php if (empty($v['by_commission'])): ?><span style="font-size:9px;color:#AEAAA6;">(sin %)</span><?php endif; ?>
                </div>
                <div style="background:var(--bg-subtle,#F1F3F5);height:16px;border-radius:3px;position:relative;overflow:hidden;">
                    <div style="background:linear-gradient(90deg,#4487A0 0%, #5EBA47 100%);height:100%;width:<?= round($widthPct, 1) ?>%;border-radius:3px;"></div>
                </div>
                <div style="text-align:right;">
                    <div style="color:#5EBA47;font-family:monospace;font-weight:700;font-size:12px;"><?= htmlspecialchars($fmtCurrency($v['commission'])) ?></div>
                    <div style="color:#7F8392;font-size:9px;font-family:monospace;"><?= htmlspecialchars($fmt($v['commission_pct'], 1)) ?>% sobre <?= htmlspecialchars($fmtCurrency($v['net_collected'])) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Tabla detalle con drill -->
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <div style="padding:7px 12px;background:var(--mam-blue-dark,#2B3164);color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;display:flex;justify-content:space-between;align-items:center;">
        <span>Cálculo por vendedor (sobre facturas pagadas)</span>
        <span style="font-size:9px;opacity:0.85;font-weight:500;">Click en una fila para ver las facturas cobradas</span>
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead>
                <tr style="background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);">
                    <?php foreach ($columns as $col): ?>
                        <th style="padding:5px 8px;text-align:<?= ($col['type'] ?? '') === 'currency' || ($col['type'] ?? '') === 'number' ? 'right' : 'left' ?>;font-size:9px;font-weight:600;color:#7F8392;text-transform:uppercase;letter-spacing:0.4px;white-space:nowrap;"><?= htmlspecialchars($col['label']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= count($columns) ?>" style="padding:20px;text-align:center;color:#AEAAA6;">Sin ventas en el periodo.</td></tr>
                <?php else: foreach ($rows as $row):
                    $vid = $row['vendor_id'];
                    $invoices = $drilldown[$vid] ?? [];
                    $hasDrill = !empty($invoices);
                ?>
                    <tr class="vc-row" data-vid="<?= htmlspecialchars($vid) ?>" style="border-bottom:1px solid var(--border-subtle,#F1F3F5);<?= $hasDrill ? 'cursor:pointer;' : '' ?>">
                        <?php foreach ($columns as $col):
                            $key = $col['key']; $val = $row[$key] ?? '';
                            $type = $col['type'] ?? 'text'; $decimals = $col['decimals'] ?? 0;
                            $align = ($type === 'currency' || $type === 'number') ? 'right' : 'left';
                            $color = 'var(--fg-1,#2C2721)'; $weight = '';
                            if ($key === 'commission') { $color = '#5EBA47'; $weight = 'font-weight:700;'; }
                            elseif ($key === 'net_collected') { $weight = 'font-weight:600;'; }
                            elseif ($key === 'discount_total' && (float)$val > 0) $color = '#E67E22';

                            $formatted = $val;
                            if ($type === 'currency' && is_numeric($val)) $formatted = (float)$val == 0 ? '—' : '$' . $fmt($val, $decimals);
                            elseif ($type === 'number' && is_numeric($val)) $formatted = $fmt($val, $decimals);

                            $isFirst = $col === $columns[0];
                        ?>
                            <td style="padding:5px 8px;text-align:<?= $align ?>;color:<?= $color ?>;<?= $weight ?>white-space:nowrap;font-family:<?= ($type === 'currency' || $type === 'number') ? 'monospace' : 'inherit' ?>;">
                                <?php if ($isFirst && $hasDrill): ?><span class="vc-chev" style="display:inline-block;width:10px;color:#7F8392;font-size:9px;transition:transform .15s;">▶</span><?php elseif ($isFirst): ?><span style="display:inline-block;width:10px;"></span><?php endif; ?>
                                <?= htmlspecialchars((string) $formatted) ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php if ($hasDrill): ?>
                    <tr class="vc-detail" data-vid="<?= htmlspecialchars($vid) ?>" style="display:<?= $isPdf ? 'table-row' : 'none' ?>;background:#FAFBFC;">
                        <td colspan="<?= count($columns) ?>" style="padding:0;">
                            <div style="padding:6px 18px 12px 30px;">
                                <div style="font-size:9px;text-transform:uppercase;letter-spacing:0.4px;color:#7F8392;font-weight:700;margin-bottom:5px;">Facturas pagadas (genera <?= $fmt($row['commission_pct'], 1) ?>% comisión)</div>
                                <table style="width:100%;border-collapse:collapse;font-size:11px;">
                                    <thead>
                                        <tr style="border-bottom:1px solid var(--border-default,#DDDFE8);">
                                            <th style="padding:4px 8px;text-align:left;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;">Factura</th>
                                            <th style="padding:4px 8px;text-align:left;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;">Pagada</th>
                                            <th style="padding:4px 8px;text-align:left;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;">Cliente</th>
                                            <th style="padding:4px 8px;text-align:right;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;">Bruto</th>
                                            <th style="padding:4px 8px;text-align:right;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;">Descuento</th>
                                            <th style="padding:4px 8px;text-align:right;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;">Neto</th>
                                            <th style="padding:4px 8px;text-align:right;font-size:9px;font-weight:600;color:#AEAAA6;text-transform:uppercase;">Comisión</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($invoices as $inv):
                                            $invComm = round($inv['net'] * $row['commission_pct'] / 100, 2);
                                        ?>
                                            <tr style="border-bottom:1px solid #F1F3F5;">
                                                <td style="padding:4px 8px;color:var(--mam-blue-dark,#2B3164);font-family:monospace;font-weight:600;"><?= htmlspecialchars($inv['idInvoice']) ?></td>
                                                <td style="padding:4px 8px;color:#575964;font-family:monospace;font-size:10px;"><?= htmlspecialchars(date('d/m/y', strtotime($inv['date']))) ?></td>
                                                <td style="padding:4px 8px;color:var(--fg-1,#2C2721);max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($inv['client_name']) ?>"><?= htmlspecialchars($inv['client_name']) ?></td>
                                                <td style="padding:4px 8px;text-align:right;color:#575964;font-family:monospace;"><?= htmlspecialchars($fmtFull($inv['total'])) ?></td>
                                                <td style="padding:4px 8px;text-align:right;color:#E67E22;font-family:monospace;"><?= $inv['discount'] > 0 ? '-' . htmlspecialchars($fmtFull($inv['discount'])) : '—' ?></td>
                                                <td style="padding:4px 8px;text-align:right;color:var(--mam-blue-dark,#2B3164);font-family:monospace;font-weight:600;"><?= htmlspecialchars($fmtFull($inv['net'])) ?></td>
                                                <td style="padding:4px 8px;text-align:right;color:#5EBA47;font-family:monospace;font-weight:700;"><?= htmlspecialchars($fmtFull($invComm)) ?></td>
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
                            if ($key === 'commission') $color = '#5EBA47';
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

<?php if (!$isPdf) $GLOBALS['__vc_drilldown_script'] = true; ?>
