<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Template compartido para ClientsABC + ProductsABC (v1.30.43).
 *
 * Estructura:
 *   1. KPI strip 4 tiles
 *   2. Banner Pareto: 3 cards A/B/C con counts + % del revenue
 *   3. Bar chart top 20 entidades
 *   4. Tabla detalle con drill-down expandible (top facturas)
 *
 * Esperado en $data:
 *   - kpis (con count_a/b/c, pct_a/b/c, total_revenue, total_clients/products,
 *     top10_concentration)
 *   - top20: array de filas para el bar
 *   - rows: tabla detalle con tag ABC en cada fila
 *   - drilldown: por id de entidad
 *   - entity_label: 'cliente' o 'producto' (singular)
 *   - entity_label_plural: 'clientes' o 'productos'
 *   - id_field: 'idClient' o 'code' — para data-rid del drill
 *   - name_field: 'client_name' o 'product_name' — para el label en el bar
 */

$kpis              = $data['kpis']              ?? [];
$top20             = $data['top20']             ?? [];
$drilldown         = $data['drilldown']         ?? [];
$columns           = $data['columns']           ?? [];
$rows              = $data['rows']              ?? [];
$totals            = $data['totals']            ?? null;
$entityLabel       = $data['entity_label']      ?? 'item';
$entityLabelPlural = $data['entity_label_plural']?? 'items';
$idField           = $data['id_field']          ?? 'id';
$nameField         = $data['name_field']        ?? 'name';
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

$totalCount = $kpis['total_' . $entityLabelPlural] ?? ($kpis['total_clients'] ?? $kpis['total_products'] ?? 0);
$countA = $kpis['count_a'] ?? 0;
$countB = $kpis['count_b'] ?? 0;
$countC = $kpis['count_c'] ?? 0;
?>

<!-- KPI strip -->
<?php
$kpiTiles = [
    ['eyebrow' => 'Revenue total',      'value' => $fmtCurrency($kpis['total_revenue'] ?? 0),     'delta' => $fmt($totalCount) . ' ' . $entityLabelPlural,                          'delta_tone' => 'fg4', 'accent' => 'var(--mam-blue-petroleo, #4487A0)', 'pdf_color' => '#4487A0'],
    ['eyebrow' => 'Clase A (top)',       'value' => $fmt($countA) . ' ' . $entityLabelPlural,     'delta' => $fmt($kpis['pct_a'] ?? 0, 1) . '% del revenue',                        'delta_tone' => 'up',  'accent' => '#5EBA47',                            'pdf_color' => '#5EBA47'],
    ['eyebrow' => 'Clase B',             'value' => $fmt($countB) . ' ' . $entityLabelPlural,     'delta' => $fmt($kpis['pct_b'] ?? 0, 1) . '% del revenue',                        'delta_tone' => 'fg4', 'accent' => '#F39C12',                            'pdf_color' => '#F39C12'],
    ['eyebrow' => 'Clase C (cola)',      'value' => $fmt($countC) . ' ' . $entityLabelPlural,     'delta' => $fmt($kpis['pct_c'] ?? 0, 1) . '% del revenue',                        'delta_tone' => 'fg4', 'accent' => '#AEAAA6',                            'pdf_color' => '#AEAAA6'],
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

<!-- Concentración top-10 banner -->
<?php $top10pct = $kpis['top10_concentration'] ?? 0; ?>
<div style="background:#F0F8FF;border:1px solid #B8D4E3;border-left:3px solid #4487A0;border-radius:5px;padding:7px 12px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;">
    <div>
        <div style="font-size:9px;font-weight:700;text-transform:uppercase;color:#2B3164;letter-spacing:0.5px;">Concentración top 10</div>
        <div style="font-size:10px;color:#7F8392;">Los 10 principales <?= htmlspecialchars($entityLabelPlural) ?> aportan <?= htmlspecialchars($fmt($top10pct, 1)) ?>% del revenue</div>
    </div>
    <div style="font-size:18px;font-weight:800;color:#4487A0;font-family:monospace;"><?= htmlspecialchars($fmt($top10pct, 1)) ?>%</div>
</div>

<!-- Bar top 20 -->
<?php if (!empty($top20)):
    $maxRev = max(array_column($top20, 'revenue')) ?: 1;
?>
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;padding:10px 14px;margin-bottom:12px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <h3 style="margin:0 0 8px;font-size:12px;font-weight:700;color:var(--mam-blue-dark,#2B3164);text-transform:uppercase;letter-spacing:0.5px;">Top 20 <?= htmlspecialchars($entityLabelPlural) ?> por revenue</h3>
    <div style="display:flex;flex-direction:column;gap:4px;">
        <?php foreach ($top20 as $i => $row):
            $widthPct = ($row['revenue'] / $maxRev) * 100;
            $abc = $row['abc'] ?? '';
            $abcColor = $abc === 'A' ? '#5EBA47' : ($abc === 'B' ? '#F39C12' : '#AEAAA6');
            $name = $row[$nameField] ?? '—';
            $nameTrunc = mb_strlen($name) > 38 ? mb_substr($name, 0, 36) . '…' : $name;
        ?>
            <div style="display:grid;grid-template-columns:30px 220px 1fr 100px 50px;gap:6px;align-items:center;font-size:11px;">
                <div style="color:#7F8392;font-family:monospace;text-align:right;font-size:10px;"><?= $i + 1 ?>.</div>
                <div style="color:var(--fg-1,#2C2721);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($name) ?>">
                    <?= htmlspecialchars($nameTrunc) ?>
                </div>
                <div style="background:var(--bg-subtle,#F1F3F5);height:12px;border-radius:2px;overflow:hidden;">
                    <div style="background:<?= $abcColor ?>;height:100%;width:<?= round($widthPct, 1) ?>%;"></div>
                </div>
                <div style="text-align:right;color:var(--fg-1,#2C2721);font-family:monospace;font-weight:600;"><?= htmlspecialchars($fmtCurrency($row['revenue'])) ?></div>
                <div style="text-align:center;">
                    <span style="display:inline-block;padding:1px 6px;border-radius:3px;font-size:9px;font-weight:700;background:<?= $abcColor ?>;color:#fff;"><?= htmlspecialchars($abc) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Tabla detalle con drill -->
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <div style="padding:7px 12px;background:var(--mam-blue-dark,#2B3164);color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;display:flex;justify-content:space-between;align-items:center;">
        <span>Listado completo (<?= count($rows) ?> <?= htmlspecialchars($entityLabelPlural) ?>)</span>
        <span style="font-size:9px;opacity:0.85;font-weight:500;">Click en una fila para ver sus top facturas</span>
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);">
                    <?php foreach ($columns as $col): ?>
                        <th style="padding:5px 6px;text-align:<?= ($col['type'] ?? '') === 'currency' || ($col['type'] ?? '') === 'number' ? 'right' : 'left' ?>;font-size:9px;font-weight:600;color:#7F8392;text-transform:uppercase;letter-spacing:0.3px;white-space:nowrap;"><?= htmlspecialchars($col['label']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= count($columns) ?>" style="padding:20px;text-align:center;color:#AEAAA6;">Sin datos para los filtros aplicados.</td></tr>
                <?php else: foreach ($rows as $row):
                    $rid = $row[$idField] ?? null;
                    $invoices = $rid !== null ? ($drilldown[$rid] ?? []) : [];
                    $hasDrill = !empty($invoices);
                    $abc = $row['abc'] ?? '';
                    $abcColor = $abc === 'A' ? '#5EBA47' : ($abc === 'B' ? '#F39C12' : '#AEAAA6');
                ?>
                    <tr class="abc-row" data-rid="<?= htmlspecialchars((string)$rid) ?>" style="border-bottom:1px solid var(--border-subtle,#F1F3F5);<?= $hasDrill ? 'cursor:pointer;' : '' ?>">
                        <?php foreach ($columns as $col):
                            $key = $col['key']; $val = $row[$key] ?? '';
                            $type = $col['type'] ?? 'text'; $decimals = $col['decimals'] ?? 0;
                            $align = ($type === 'currency' || $type === 'number') ? 'right' : 'left';

                            $color = 'var(--fg-1,#2C2721)';
                            $weight = '';
                            if ($key === 'revenue') $weight = 'font-weight:700;';
                            elseif ($key === 'cumulative_pct') $color = '#7F8392';

                            $formatted = $val;
                            if ($key === 'abc') {
                                $formatted = '<span style="display:inline-block;padding:1px 6px;border-radius:3px;font-size:9px;font-weight:700;background:' . $abcColor . ';color:#fff;">' . htmlspecialchars($val) . '</span>';
                            } elseif ($type === 'currency' && is_numeric($val)) {
                                $formatted = (float)$val == 0 ? '—' : '$' . $fmt($val, $decimals);
                            } elseif ($type === 'number' && is_numeric($val)) {
                                $formatted = $fmt($val, $decimals) . ($key === 'cumulative_pct' || $key === 'revenue_pct' ? '%' : '');
                            } elseif ($type === 'date' && $val) {
                                $formatted = date('d/m/y', strtotime($val));
                            } elseif ($val === null || $val === '') {
                                $formatted = '—';
                            } else {
                                $formatted = htmlspecialchars((string)$val);
                            }

                            $isFirst = $col === $columns[0];
                            $cellStyle = ($key === 'client_name' || $key === 'product_name' || $key === 'vendor_name') ? 'white-space:normal;word-wrap:break-word;max-width:200px;' : 'white-space:nowrap;';
                        ?>
                            <td style="padding:5px 6px;text-align:<?= $align ?>;color:<?= $color ?>;<?= $weight ?><?= $cellStyle ?>font-family:<?= ($type === 'currency' || $type === 'number') ? 'monospace' : 'inherit' ?>;">
                                <?php if ($isFirst && $hasDrill): ?><span class="abc-chev" style="display:inline-block;width:10px;color:#7F8392;font-size:9px;transition:transform .15s;">▶</span><?php elseif ($isFirst): ?><span style="display:inline-block;width:10px;"></span><?php endif; ?>
                                <?= $key === 'abc' ? $formatted : $formatted ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php if ($hasDrill): ?>
                    <tr class="abc-detail" data-rid="<?= htmlspecialchars((string)$rid) ?>" style="display:<?= $isPdf ? 'table-row' : 'none' ?>;background:#FAFBFC;">
                        <td colspan="<?= count($columns) ?>" style="padding:0;">
                            <div style="padding:6px 18px 12px 30px;">
                                <div style="font-size:9px;text-transform:uppercase;letter-spacing:0.4px;color:#7F8392;font-weight:700;margin-bottom:5px;">Top facturas</div>
                                <table style="width:100%;border-collapse:collapse;font-size:10px;">
                                    <thead>
                                        <tr style="border-bottom:1px solid var(--border-default,#DDDFE8);">
                                            <?php foreach (['Factura','Fecha','Vendedor','Bruto','Descuento','Neto'] as $h): ?>
                                                <th style="padding:3px 8px;text-align:<?= in_array($h, ['Bruto','Descuento','Neto']) ? 'right' : 'left' ?>;font-size:8px;font-weight:600;color:#AEAAA6;text-transform:uppercase;"><?= htmlspecialchars($h) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($invoices as $inv): ?>
                                            <tr style="border-bottom:1px solid #F1F3F5;">
                                                <td style="padding:3px 8px;color:var(--mam-blue-dark,#2B3164);font-family:monospace;font-weight:600;"><?= htmlspecialchars($inv['idInvoice']) ?></td>
                                                <td style="padding:3px 8px;color:#575964;font-family:monospace;"><?= htmlspecialchars(date('d/m/y', strtotime($inv['date']))) ?></td>
                                                <td style="padding:3px 8px;color:var(--fg-1,#2C2721);max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($inv['vendor_name'] ?? $inv['client_name'] ?? '') ?></td>
                                                <td style="padding:3px 8px;text-align:right;color:#575964;font-family:monospace;"><?= htmlspecialchars($fmtFull($inv['total'] ?? 0)) ?></td>
                                                <td style="padding:3px 8px;text-align:right;color:#E67E22;font-family:monospace;"><?= !empty($inv['discount']) && $inv['discount'] > 0 ? '-' . htmlspecialchars($fmtFull($inv['discount'])) : '—' ?></td>
                                                <td style="padding:3px 8px;text-align:right;color:var(--mam-blue-dark,#2B3164);font-family:monospace;font-weight:700;"><?= htmlspecialchars($fmtFull($inv['net'] ?? 0)) ?></td>
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
                            $formatted = $val;
                            if ($val !== '' && $type === 'currency' && is_numeric($val)) $formatted = '$' . $fmt($val, $decimals);
                            elseif ($val !== '' && $type === 'number' && is_numeric($val)) $formatted = $fmt($val, $decimals);
                        ?>
                            <td style="padding:7px 6px;text-align:<?= $align ?>;color:var(--mam-blue-dark,#2B3164);white-space:nowrap;font-family:<?= ($type === 'currency' || $type === 'number') ? 'monospace' : 'inherit' ?>;"><?= htmlspecialchars((string) $formatted) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$isPdf) $GLOBALS['__abc_drilldown_script'] = true; ?>
