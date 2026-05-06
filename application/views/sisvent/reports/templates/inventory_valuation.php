<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Template custom Inventory Valuation (v1.30.32).
 *
 * Estructura:
 *   1. KPI strip 4 tiles
 *   2. Bar chart por bodega (con # productos y % del total)
 *   3. Bar chart top 10 productos por valor
 *   4. Tabla detalle (top N producto-bodega)
 */

$kpis        = $data['kpis']         ?? [];
$byStore     = $data['by_store']     ?? [];
$topProducts = $data['top_products'] ?? [];
$columns     = $data['columns']      ?? [];
$rows        = $data['rows']         ?? [];
$totals      = $data['totals']       ?? null;
$isPdf       = !empty($pdf_mode);

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

<!-- ============== KPI STRIP ============== -->
<?php
$kpiTiles = [
    ['eyebrow' => 'Valor total inventario',  'value' => $fmtCurrency($kpis['total_value'] ?? 0), 'delta' => 'inmovilizado en bodegas',                  'delta_tone' => 'fg4', 'accent' => 'var(--mam-green-program, #5EBA47)', 'pdf_color' => '#5EBA47'],
    ['eyebrow' => '# Productos',              'value' => $fmt($kpis['num_products'] ?? 0),       'delta' => 'SKUs distintos con stock',                  'delta_tone' => 'fg4', 'accent' => 'var(--mam-blue-petroleo, #4487A0)', 'pdf_color' => '#4487A0'],
    ['eyebrow' => '# Bodegas',                'value' => $fmt($kpis['num_stores'] ?? 0),         'delta' => $fmt($kpis['total_qty'] ?? 0) . ' und totales', 'delta_tone' => 'fg4', 'accent' => 'var(--mam-blue-dark, #2B3164)',     'pdf_color' => '#2B3164'],
    ['eyebrow' => 'Valor promedio por SKU',   'value' => $fmtCurrency($kpis['avg_per_sku'] ?? 0),'delta' => 'valor / # productos',                       'delta_tone' => 'fg4', 'accent' => 'var(--mam-gray-medium, #AEAAA6)',   'pdf_color' => '#AEAAA6'],
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

<!-- ============== POR BODEGA + TOP PRODUCTOS (lado a lado en HTML) ============== -->
<?php
$renderByStore = function (array $byStore, float $totalValue) use ($fmt, $fmtFull, $fmtCurrency) {
    if (empty($byStore)) return;
    $maxVal = max(array_column($byStore, 'total_value')) ?: 1;
?>
    <div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">
        <div style="padding:10px 14px;background:var(--mam-blue-dark,#2B3164);color:#fff;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;display:flex;justify-content:space-between;align-items:center;">
            <span>Valor por bodega</span>
            <span style="font-size:11px;font-weight:700;font-family:monospace;"><?= htmlspecialchars($fmtFull($totalValue)) ?></span>
        </div>
        <div style="padding:14px 16px;">
            <?php foreach ($byStore as $bs):
                $widthPct = ($bs['total_value'] / $maxVal) * 100;
            ?>
                <div style="display:grid;grid-template-columns:1fr 110px;gap:10px;align-items:center;font-size:13px;margin-bottom:8px;">
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:3px;">
                            <span style="color:var(--fg-1,#2C2721);font-weight:600;"><?= htmlspecialchars($bs['store_name']) ?></span>
                            <span style="color:#7F8392;font-size:10px;font-family:monospace;"><?= (int)$bs['num_products'] ?> SKUs · <?= htmlspecialchars($fmt($bs['total_qty'])) ?> und</span>
                        </div>
                        <div style="background:var(--bg-subtle,#F1F3F5);height:14px;border-radius:3px;position:relative;overflow:hidden;">
                            <div style="background:linear-gradient(90deg,#4487A0 0%, #5EBA47 100%);height:100%;width:<?= round($widthPct, 1) ?>%;border-radius:3px;"></div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="color:var(--mam-blue-dark,#2B3164);font-family:monospace;font-weight:700;font-size:13px;"><?= htmlspecialchars($fmtFull($bs['total_value'])) ?></div>
                        <div style="color:#7F8392;font-size:10px;font-family:monospace;"><?= htmlspecialchars($fmt($bs['pct_of_total'], 1)) ?>%</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php
};

$renderTopProducts = function (array $top) use ($fmt, $fmtFull, $fmtCurrency) {
    if (empty($top)) return;
    $maxVal = $top[0]['valor_total'] ?: 1;
?>
    <div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">
        <div style="padding:10px 14px;background:#16A085;color:#fff;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;display:flex;justify-content:space-between;align-items:center;">
            <span>Top 10 productos por valor</span>
            <span style="font-size:10px;opacity:0.85;font-weight:500;">Suma de todas las bodegas</span>
        </div>
        <div style="padding:14px 16px;">
            <?php foreach ($top as $p):
                $widthPct = ($p['valor_total'] / $maxVal) * 100;
                $name = mb_strlen($p['product_name']) > 40 ? mb_substr($p['product_name'], 0, 38) . '…' : $p['product_name'];
            ?>
                <div style="display:grid;grid-template-columns:1fr 90px;gap:8px;align-items:center;font-size:12px;margin-bottom:6px;">
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:2px;">
                            <span style="color:var(--fg-1,#2C2721);font-weight:500;" title="<?= htmlspecialchars($p['product_name']) ?>"><?= htmlspecialchars($name) ?></span>
                            <span style="color:#AEAAA6;font-size:10px;font-family:monospace;">#<?= htmlspecialchars($p['code']) ?> · <?= htmlspecialchars($fmt($p['qty'])) ?> und</span>
                        </div>
                        <div style="background:var(--bg-subtle,#F1F3F5);height:10px;border-radius:2px;position:relative;overflow:hidden;">
                            <div style="background:#16A085;height:100%;width:<?= round($widthPct, 1) ?>%;border-radius:2px;"></div>
                        </div>
                    </div>
                    <div style="text-align:right;color:#16A085;font-family:monospace;font-weight:700;font-size:11px;"><?= htmlspecialchars($fmtCurrency($p['valor_total'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php
};
?>

<?php if (!$isPdf): ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
    <?php $renderByStore($byStore, $kpis['total_value'] ?? 0); ?>
    <?php $renderTopProducts($topProducts); ?>
</div>
<?php else: ?>
<div style="margin-bottom:14px;"><?php $renderByStore($byStore, $kpis['total_value'] ?? 0); ?></div>
<div style="margin-bottom:14px;"><?php $renderTopProducts($topProducts); ?></div>
<?php endif; ?>

<!-- ============== TABLA DETALLE ============== -->
<div style="background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;box-shadow:0 1px 2px rgba(27,54,93,.04);">
    <div style="padding:10px 14px;background:var(--mam-blue-dark,#2B3164);color:#fff;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">
        Detalle producto · bodega
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#F8F8FA;border-bottom:1px solid var(--border-default,#DDDFE8);">
                    <?php foreach ($columns as $col): ?>
                        <th style="padding:8px 10px;text-align:<?= ($col['type'] ?? '') === 'currency' || ($col['type'] ?? '') === 'number' ? 'right' : 'left' ?>;font-size:10px;font-weight:600;text-transform:uppercase;color:#7F8392;letter-spacing:0.4px;white-space:nowrap;"><?= htmlspecialchars($col['label']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= count($columns) ?>" style="padding:24px;text-align:center;color:var(--fg-3,#AEAAA6);">No hay productos con stock que cumplan el filtro.</td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <tr style="border-bottom:1px solid var(--border-subtle,#F1F3F5);">
                        <?php foreach ($columns as $col):
                            $key = $col['key']; $val = $row[$key] ?? '';
                            $type = $col['type'] ?? 'text'; $decimals = $col['decimals'] ?? 0;
                            $align = ($type === 'currency' || $type === 'number') ? 'right' : 'left';
                            $color = $key === 'valor_total' ? 'var(--mam-blue-dark,#2B3164)' : 'var(--fg-1,#2C2721)';
                            $weight = $key === 'valor_total' ? 'font-weight:700;' : '';

                            $formatted = $val;
                            if ($type === 'currency' && is_numeric($val)) $formatted = '$' . $fmt($val, $decimals);
                            elseif ($type === 'number' && is_numeric($val)) $formatted = $fmt($val, $decimals);
                        ?>
                            <td style="padding:8px 10px;text-align:<?= $align ?>;color:<?= $color ?>;<?= $weight ?>white-space:nowrap;font-family:<?= ($type === 'currency' || $type === 'number') ? 'monospace' : 'inherit' ?>;"><?= htmlspecialchars((string) $formatted) ?></td>
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
                            <td style="padding:10px 10px;text-align:<?= $align ?>;color:var(--mam-blue-dark,#2B3164);white-space:nowrap;font-family:<?= ($type === 'currency' || $type === 'number') ? 'monospace' : 'inherit' ?>;"><?= htmlspecialchars((string) $formatted) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
