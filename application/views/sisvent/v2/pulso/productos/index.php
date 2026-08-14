<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$fmt = function($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
$fmtShort = function($n) {
    $n = (float)$n;
    if (abs($n) >= 1000000) return '$' . rtrim(rtrim(number_format($n / 1000000, 1, ',', '.'), '0'), ',') . 'M';
    if (abs($n) >= 1000)    return '$' . round($n / 1000) . 'k';
    return '$' . number_format($n, 0, ',', '.');
};
$baseUrl = strpos($_SERVER['REQUEST_URI'] ?? '', '/v2/productos') !== false
    ? base_url('sisvent/v2/productos')
    : base_url('sisvent/business/products');
?>
<!DOCTYPE html>
<html lang="es" class="pulso">
<head>
    <title>Productos · Ledxury</title>
    <?php $this->load->view('sisvent/v2/pulso/layouts/meta_header'); ?>
</head>
<body>

<div class="pulso-shell">
    <?php $this->load->view('sisvent/v2/pulso/layouts/sidebar'); ?>

    <main class="pulso-main">

        <?php
        $topbarActions = '
            <a href="' . base_url('sisvent/business/products/add') . '" class="pulso-btn pulso-btn--primary pulso-btn--pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14 M12 5v14"/>
                </svg>
                Nuevo producto
            </a>
        ';
        $this->load->view('sisvent/v2/pulso/layouts/topbar', array(
            'pageTitle'     => $pageTitle,
            'breadcrumbs'   => $breadcrumbs,
            'topbarActions' => $topbarActions,
        ));
        ?>

        <div class="pulso-content">

            <!-- KPI strip -->
            <div class="pulso-kpi-grid" style="grid-template-columns: 1.4fr 1fr 1fr 1fr;">

                <div class="pulso-kpi pulso-kpi--big">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Valor inventario</div>
                            <div class="pulso-kpi-value"><?= $fmtShort($kpiValorInv) ?></div>
                            <div class="pulso-kpi-sub"><?= number_format($kpiUnidades, 0, ',', '.') ?> unidades en stock</div>
                        </div>
                    </div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">SKUs totales</div>
                            <div class="pulso-kpi-value"><?= number_format($kpiTotal, 0, ',', '.') ?></div>
                            <div class="pulso-kpi-sub">productos en catálogo</div>
                        </div>
                    </div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Con stock</div>
                            <div class="pulso-kpi-value"><?= number_format($kpiConStock, 0, ',', '.') ?></div>
                            <div class="pulso-kpi-sub">
                                <?= $kpiTotal > 0 ? round(($kpiConStock / $kpiTotal) * 100, 0) . '% del catálogo' : '—' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pulso-kpi">
                    <div class="pulso-kpi-head">
                        <div>
                            <div class="pulso-eyebrow">Sin stock</div>
                            <div class="pulso-kpi-value" style="color:<?= $kpiSinStock > 0 ? 'var(--pulso-danger)' : 'var(--pulso-ink)' ?>;">
                                <?= number_format($kpiSinStock, 0, ',', '.') ?>
                            </div>
                            <div class="pulso-kpi-sub">requieren reposición</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Search -->
            <form method="GET" action="<?= $baseUrl ?>" style="margin-bottom: 16px; display:flex; gap:12px; align-items:center;">
                <div class="pulso-search-input" style="flex:1; max-width:420px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input type="text" name="q" value="<?= htmlspecialchars($term) ?>" placeholder="Buscar por SKU, descripción…" style="width:100%;">
                </div>
                <button type="submit" class="pulso-btn pulso-btn--primary pulso-btn--sm">Buscar</button>
                <?php if ($term !== ''): ?>
                <a href="<?= $baseUrl ?>" class="pulso-btn pulso-btn--ghost pulso-btn--sm">Limpiar ✕</a>
                <?php endif; ?>
            </form>

            <!-- Tabla -->
            <div class="pulso-table-wrap">
                <table class="pulso-table">
                    <thead>
                        <tr>
                            <th style="width:54px;"></th>
                            <th>SKU</th>
                            <th>Descripción</th>
                            <th class="pulso-text-right">Precio</th>
                            <th class="pulso-text-right">Costo</th>
                            <th class="pulso-text-right">Margen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" style="padding:48px;text-align:center;color:var(--pulso-ink3);">
                                <?= $term !== '' ? 'Sin coincidencias.' : 'Sin productos registrados.' ?>
                            </td>
                        </tr>
                        <?php else: foreach ($products as $p):
                            $editUrl = base_url('sisvent/business/products/edit/' . urlencode($p->idProduct));
                            $price = (float)($p->price ?? 0);
                            $cost  = (float)($p->cost ?? 0);
                            $margin = $price > 0 ? round((($price - $cost) / $price) * 100, 1) : 0;
                            $marginTone = $margin >= 40 ? 'var(--pulso-mint)' : ($margin >= 20 ? 'var(--pulso-ink)' : 'var(--pulso-danger)');
                            $img = !empty($p->picture_url) && $p->picture_url !== 'products/no_image.png'
                                ? base_url('public/' . $p->picture_url)
                                : '';
                        ?>
                        <tr style="cursor:pointer;" onclick="window.location='<?= $editUrl ?>'">
                            <td>
                                <?php if ($img): ?>
                                <div style="width:42px;height:42px;border-radius:8px;background:#f5f0e8 url('<?= $img ?>') center/cover no-repeat;"></div>
                                <?php else: ?>
                                <div style="width:42px;height:42px;border-radius:8px;background:var(--pulso-bg);display:grid;place-items:center;color:var(--pulso-ink3);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z M3.27 6.96 12 12.01l8.73-5.05 M12 22.08V12"/>
                                    </svg>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="pulso-mono" style="color:var(--pulso-ink);font-weight:600;">
                                <?= htmlspecialchars($p->idProduct) ?>
                            </td>
                            <td style="color:var(--pulso-ink);">
                                <div style="font-weight:500;"><?= htmlspecialchars($p->description ?: '(sin descripción)') ?></div>
                            </td>
                            <td class="pulso-text-right pulso-num" style="font-weight:500;color:var(--pulso-ink);">
                                <?= $fmt($price) ?>
                            </td>
                            <td class="pulso-text-right pulso-num" style="color:var(--pulso-ink3);">
                                <?= $cost > 0 ? $fmt($cost) : '—' ?>
                            </td>
                            <td class="pulso-text-right" style="font-weight:600;color:<?= $marginTone ?>;">
                                <?= $cost > 0 ? $margin . '%' : '—' ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($lastPage > 1): ?>
            <div style="display:flex; align-items:center; justify-content:space-between; margin-top:20px; padding:0 4px;">
                <div style="font-size:12px; color:var(--pulso-ink3);">
                    Página <?= $page ?> de <?= $lastPage ?> · <?= number_format($total, 0, ',', '.') ?> productos
                </div>
                <div style="display:flex; gap:6px;">
                    <?php $qStr = $term !== '' ? '&q=' . urlencode($term) : ''; ?>
                    <?php if ($page > 1): ?>
                    <a href="<?= $baseUrl . '?p=' . ($page - 1) . $qStr ?>" class="pulso-btn pulso-btn--secondary pulso-btn--sm">← Anterior</a>
                    <?php endif; ?>
                    <?php if ($page < $lastPage): ?>
                    <a href="<?= $baseUrl . '?p=' . ($page + 1) . $qStr ?>" class="pulso-btn pulso-btn--secondary pulso-btn--sm">Siguiente →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php $this->load->view('sisvent/v2/pulso/layouts/footer'); ?>
</body>
</html>
