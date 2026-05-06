<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];

/**
 * Reports v2 — index landing.
 *
 * Reorganizacion v1.30.44: cards agrupadas por job-to-be-done (no por
 * fuente de datos). Inspirado en Odoo Reporting + SAP B1 Reports tree.
 *
 * Categorias:
 *   - Comercial    -> ventas, vendedores, comisiones, top productos
 *   - Cartera      -> aging, pending invoices, estados de cuenta
 *   - Analisis ABC -> Pareto clientes y productos (analitica estrategica)
 *   - Tesoreria    -> cash flow + inventario + provider statement
 *
 * @var ReportInterface[] $reports
 * @var int $role
 */

// Mapping report_id -> category + icon (key) + accent color
$categoryMap = [
    // Comercial
    'daily_sales'        => ['cat' => 'comercial', 'icon' => 'chart-bar',    'accent' => '#5EBA47'],
    'vendor_performance' => ['cat' => 'comercial', 'icon' => 'users',        'accent' => '#5EBA47'],
    'top_products'       => ['cat' => 'comercial', 'icon' => 'shopping',     'accent' => '#5EBA47'],
    'vendor_commissions' => ['cat' => 'comercial', 'icon' => 'cash',         'accent' => '#5EBA47'],

    // Cartera
    'aging'              => ['cat' => 'cartera',   'icon' => 'clock',        'accent' => '#C0392B'],
    'pending_invoices'   => ['cat' => 'cartera',   'icon' => 'document',     'accent' => '#C0392B'],
    'client_statement'   => ['cat' => 'cartera',   'icon' => 'user-circle',  'accent' => '#C0392B'],

    // ABC
    'clients_abc'        => ['cat' => 'abc',       'icon' => 'pie',          'accent' => '#4487A0'],
    'products_abc'       => ['cat' => 'abc',       'icon' => 'cube',         'accent' => '#4487A0'],

    // Tesoreria & Operativo
    'cash_flow'          => ['cat' => 'tesoreria', 'icon' => 'currency',     'accent' => '#8E44AD'],
    'provider_statement' => ['cat' => 'tesoreria', 'icon' => 'truck',        'accent' => '#8E44AD'],
    'inventory_valuation'=> ['cat' => 'tesoreria', 'icon' => 'archive',      'accent' => '#8E44AD'],
];

$categoryMeta = [
    'comercial' => [
        'label'    => 'Comercial y vendedores',
        'subtitle' => 'Ventas, rendimiento, comisiones, top productos',
        'color'    => '#5EBA47',
        'icon'     => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    ],
    'cartera' => [
        'label'    => 'Cartera y cobranza',
        'subtitle' => 'Antigüedad de saldos, facturas pendientes, estado de cuenta',
        'color'    => '#C0392B',
        'icon'     => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    ],
    'abc' => [
        'label'    => 'Análisis estratégico (ABC)',
        'subtitle' => 'Pareto 80/15/5 — qué pocos clientes/productos generan la mayoría',
        'color'    => '#4487A0',
        'icon'     => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z',
    ],
    'tesoreria' => [
        'label'    => 'Tesorería e inventario',
        'subtitle' => 'Posición de caja, estado de cuenta proveedor, inventario valorizado',
        'color'    => '#8E44AD',
        'icon'     => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ],
];

// Iconos heroicons-like por report
$iconPaths = [
    'chart-bar'   => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'users'       => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
    'shopping'    => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
    'cash'        => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    'clock'       => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    'document'    => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'user-circle' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    'pie'         => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z',
    'cube'        => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    'currency'    => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    'truck'       => 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1',
    'archive'     => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
];

// Agrupa los reports por categoria
$grouped = [];
foreach ($reports as $r) {
    $id = $r->id();
    $info = $categoryMap[$id] ?? ['cat' => 'tesoreria', 'icon' => 'document', 'accent' => '#7F8392'];
    $cat = $info['cat'];
    if (!isset($grouped[$cat])) $grouped[$cat] = [];
    $grouped[$cat][] = ['report' => $r, 'info' => $info];
}

// Orden de presentacion: Comercial -> Cartera -> ABC -> Tesoreria
$catOrder = ['comercial', 'cartera', 'abc', 'tesoreria'];
?>
<!DOCTYPE html>
<html lang="es">
<title>Reportes — Engine v2</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-4 py-4 w-full">

                <?php $this->load->view('sisvent/design-system/_page_header', [
                    'eyebrow'  => 'Reportes',
                    'title'    => 'Reportes ejecutivos',
                    'subtitle' => count($reports) . ' reporte' . (count($reports) === 1 ? '' : 's') . ' organizado' . (count($reports) === 1 ? '' : 's') . ' por área de uso · estilo Odoo / SAP',
                ]); ?>

                <!-- Search bar -->
                <div style="margin-bottom:18px;">
                    <div style="display:inline-flex;align-items:center;gap:8px;background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:8px;padding:6px 12px;width:100%;max-width:480px;box-shadow:0 1px 2px rgba(27,54,93,.04);">
                        <svg style="width:14px;height:14px;color:#7F8392;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" id="rep-index-search" placeholder="Buscar reporte por nombre o descripción..." style="border:0;outline:0;flex:1;padding:6px 0;font-size:13px;color:var(--fg-1,#2C2721);background:transparent;">
                    </div>
                </div>

                <?php if (empty($reports)): ?>
                    <div style="padding:40px;text-align:center;color:#AEAAA6;background:#fff;border-radius:8px;border:1px dashed #DDDFE8;">
                        No hay reportes disponibles para tu rol.
                    </div>
                <?php else: ?>

                    <?php foreach ($catOrder as $cat):
                        if (empty($grouped[$cat])) continue;
                        $meta = $categoryMeta[$cat];
                        $items = $grouped[$cat];
                    ?>
                    <!-- Categoria: <?= $cat ?> -->
                    <section style="margin-bottom:22px;" data-category="<?= $cat ?>">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                            <div style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:<?= $meta['color'] ?>15;border:1px solid <?= $meta['color'] ?>30;">
                                <svg style="width:16px;height:16px;color:<?= $meta['color'] ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $meta['icon'] ?>"/></svg>
                            </div>
                            <div style="flex:1;">
                                <h2 style="margin:0;font-size:15px;font-weight:700;color:var(--mam-blue-dark,#2B3164);"><?= htmlspecialchars($meta['label']) ?></h2>
                                <p style="margin:0;font-size:11px;color:#7F8392;"><?= htmlspecialchars($meta['subtitle']) ?></p>
                            </div>
                            <span style="font-size:10px;color:#AEAAA6;font-family:monospace;"><?= count($items) ?> reporte<?= count($items) === 1 ? '' : 's' ?></span>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;">
                            <?php foreach ($items as $entry):
                                $r = $entry['report'];
                                $info = $entry['info'];
                                $iconPath = $iconPaths[$info['icon']] ?? $iconPaths['document'];
                            ?>
                            <a href="<?= base_url() ?>sisvent/admin/reports/v2/<?= $r->id() ?>"
                               class="rep-index-card"
                               data-search="<?= htmlspecialchars(strtolower($r->title() . ' ' . $r->description() . ' ' . $r->id())) ?>"
                               style="display:block;background:#fff;padding:14px 16px;border:1px solid #DDDFE8;border-left:3px solid <?= $info['accent'] ?>;border-radius:8px;text-decoration:none;box-shadow:0 1px 2px rgba(27,54,93,.04);transition:all .15s;">
                                <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:6px;">
                                    <div style="width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;background:<?= $info['accent'] ?>12;flex-shrink:0;">
                                        <svg style="width:14px;height:14px;color:<?= $info['accent'] ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $iconPath ?>"/></svg>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <h3 style="font-size:14px;font-weight:700;color:var(--mam-blue-dark,#2B3164);margin:0 0 3px;line-height:1.3;">
                                            <?= htmlspecialchars($r->title()) ?>
                                        </h3>
                                        <div style="font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:<?= $info['accent'] ?>;font-family:monospace;">
                                            <?= htmlspecialchars($r->id()) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($r->description()): ?>
                                    <p style="font-size:12px;color:#575964;margin:0 0 10px;line-height:1.4;">
                                        <?= htmlspecialchars(mb_strimwidth($r->description(), 0, 110, '…')) ?>
                                    </p>
                                <?php endif; ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <div style="display:flex;gap:3px;flex-wrap:wrap;">
                                        <?php foreach ($r->availableFormats() as $f): ?>
                                            <span style="font-size:9px;font-weight:600;padding:1px 5px;background:#F1F3F5;color:#575964;border-radius:3px;text-transform:uppercase;letter-spacing:0.4px;font-family:monospace;"><?= $f ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <span style="font-size:11px;color:<?= $info['accent'] ?>;font-weight:600;">Abrir →</span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endforeach; ?>

                    <!-- Empty search state -->
                    <div id="rep-index-empty" style="display:none;padding:40px;text-align:center;color:#AEAAA6;background:#fff;border-radius:8px;border:1px dashed #DDDFE8;">
                        Ningún reporte coincide con tu búsqueda.
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>

<style>
.rep-index-card:hover { box-shadow: 0 4px 12px rgba(27,54,93,.10) !important; transform: translateY(-1px); }
</style>
<script>
// Search delegation a nivel document (sobrevive Vue re-render)
document.addEventListener('input', function(e) {
    if (e.target.id !== 'rep-index-search') return;
    var q = e.target.value.trim().toLowerCase();
    var cards = document.querySelectorAll('.rep-index-card');
    var visibleCount = 0;
    cards.forEach(function(card) {
        var match = q === '' || (card.dataset.search || '').indexOf(q) !== -1;
        card.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });
    // Hide categorias completamente vacias
    document.querySelectorAll('section[data-category]').forEach(function(sec) {
        var any = sec.querySelectorAll('.rep-index-card:not([style*="display: none"])').length;
        sec.style.display = any > 0 ? '' : 'none';
    });
    var empty = document.getElementById('rep-index-empty');
    if (empty) empty.style.display = visibleCount === 0 ? 'block' : 'none';
});
</script>
</body>
</html>
