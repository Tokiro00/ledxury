<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];

/**
 * @var ReportInterface $report
 * @var array $params
 * @var array $data
 * @var array $meta
 * @var string $template
 */
?>
<!DOCTYPE html>
<html lang="es">
    <title><?= htmlspecialchars($report->title()) ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <?php
                    $this->load->view('sisvent/design-system/_page_header', [
                        'eyebrow'  => 'Reportes',
                        'title'    => $report->title(),
                        'subtitle' => $report->description() ?: '',
                    ]);
                    ?>

                    <!-- Action bar: formatos + envío + filtros -->
                    <?php $this->load->view('sisvent/reports/_action_bar', [
                        'report' => $report,
                        'params' => $params,
                        'meta'   => $meta,
                    ]); ?>

                    <!-- Filtros -->
                    <?php if (!empty($report->filterDefinitions())): ?>
                    <?php $this->load->view('sisvent/reports/_filters', [
                        'report' => $report,
                        'params' => $params,
                    ]); ?>
                    <?php endif; ?>

                    <!-- Detectar filtros requeridos sin valor para mostrar mensaje claro
                         en vez de la tabla 'no hay datos' (UX). -->
                    <?php
                    $missingRequired = [];
                    foreach ($report->filterDefinitions() as $f) {
                        if (!empty($f['required']) && empty($params[$f['name']])) {
                            $missingRequired[] = $f['label'] ?? $f['name'];
                        }
                    }
                    ?>

                    <?php if (!empty($missingRequired)): ?>
                        <div style="padding:48px 24px;text-align:center;background:var(--bg-surface,#fff);border:1px dashed var(--border-default,#DDDFE8);border-radius:8px;">
                            <svg style="width:36px;height:36px;color:var(--mam-blue-petroleo,#4487A0);margin-bottom:8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            <h3 style="font-size:16px;font-weight:700;color:var(--mam-blue-dark,#2B3164);margin:0 0 6px;">
                                Selecciona <?= htmlspecialchars(strtolower(implode(' y ', $missingRequired))) ?> para ver el reporte
                            </h3>
                            <p style="font-size:13px;color:var(--fg-2,#575964);margin:0;">
                                Usa el buscador en los filtros de arriba.
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Template específico del reporte -->
                        <?php $this->load->view($template, [
                            'report' => $report,
                            'params' => $params,
                            'data'   => $data,
                            'meta'   => $meta,
                        ]); ?>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <?php
    // Picker assets (CSS + JS) inyectados FUERA del Vue mount (#bars).
    // Vue rechaza <script> y <style> con side-effects dentro del template
    // y aborta el rendering — por eso se mueven al final del body.
    if (!empty($GLOBALS['__rep_picker_assets_needed'])):
    ?>
    <style>
    .rep-picker__row.rep-picker__row--focused {
        border-color: var(--mam-blue-petroleo,#4487A0) !important;
        box-shadow: 0 0 0 3px rgba(68,135,160,.15);
    }
    .rep-picker__row.rep-picker__row--error {
        border-color: var(--mam-red,#ef0d0d) !important;
        box-shadow: 0 0 0 3px rgba(239,13,13,.15);
    }
    .rep-picker__clear:hover {
        color: var(--mam-red,#ef0d0d) !important;
        background: var(--bg-subtle,#F1F3F5) !important;
    }
    @keyframes rep-spin { to { transform: rotate(360deg); } }
    .rep-picker__dropdown {
        position:absolute; top:100%; left:0; margin-top:4px;
        width: 480px; max-width: calc(100vw - 80px);
        background:#fff; border:1px solid var(--border-default,#DDDFE8);
        border-radius:8px;
        box-shadow:0 12px 28px rgba(36,47,81,.18);
        max-height:480px; overflow-y:auto; z-index:100;
    }
    .rep-picker__item {
        padding:12px 16px; cursor:pointer;
        border-bottom:1px solid var(--border-subtle,#F1F3F5);
    }
    .rep-picker__item:last-child { border-bottom:0; }
    .rep-picker__item--active, .rep-picker__item:hover {
        background: var(--bg-tinted-blue, #e8f4f8);
    }
    .rep-picker__label {
        font-size:14px; font-weight:700;
        color: var(--mam-blue-dark,#2B3164);
    }
    .rep-picker__meta {
        font-size:12px; color: var(--fg-3,#AEAAA6);
        margin-top:2px; font-family:monospace;
    }
    .rep-picker__phonetic {
        font-size:11px; color: var(--mam-blue-petroleo,#4487A0);
        margin-left:8px; font-style:italic;
    }
    .rep-picker__empty {
        padding:18px; text-align:center;
        font-size:13px; color: var(--fg-3,#AEAAA6);
    }
    .rep-picker__hint {
        padding:8px 12px; font-size:11px; color: var(--fg-3,#AEAAA6);
        background: var(--bg-subtle,#F1F3F5);
        border-top:1px solid var(--border-subtle,#F1F3F5);
        text-align:center;
    }
    </style>
    <script src="<?= base_url() ?>public/assets/js/reports/picker.js?v=1.30.29" defer></script>
    <?php if (!empty($GLOBALS['__cf_drilldown_script'])): ?>
    <script>
    // cash_flow drill-down: click en fila de categoría toggle el detalle
    document.addEventListener('click', function(e) {
        var row = e.target.closest('.cf-cat-row');
        if (!row) return;
        var key = row.dataset.catKey;
        var detail = document.querySelector('.cf-cat-detail[data-cat-key="' + key + '"]');
        if (!detail) return;
        var visible = detail.style.display !== 'none';
        detail.style.display = visible ? 'none' : 'table-row';
        var chev = row.querySelector('.cf-chev');
        if (chev) chev.style.transform = visible ? 'none' : 'rotate(90deg)';
    });
    </script>
    <?php endif; ?>
    <?php if (!empty($GLOBALS['__ag_drilldown_script'])): ?>
    <script>
    // aging drill-down: click en fila de cliente toggle facturas vencidas
    document.addEventListener('click', function(e) {
        var row = e.target.closest('.ag-row');
        if (!row) return;
        var cid = row.dataset.cid;
        var detail = document.querySelector('.ag-detail[data-cid="' + cid + '"]');
        if (!detail) return;
        var visible = detail.style.display !== 'none';
        detail.style.display = visible ? 'none' : 'table-row';
        var chev = row.querySelector('.ag-chev');
        if (chev) chev.style.transform = visible ? 'none' : 'rotate(90deg)';
    });
    </script>
    <?php endif; ?>
    <?php if (!empty($GLOBALS['__vc_drilldown_script'])): ?>
    <script>
    // vendor_commissions drill-down
    document.addEventListener('click', function(e) {
        var row = e.target.closest('.vc-row');
        if (!row) return;
        var vid = row.dataset.vid;
        var detail = document.querySelector('.vc-detail[data-vid="' + vid + '"]');
        if (!detail) return;
        var visible = detail.style.display !== 'none';
        detail.style.display = visible ? 'none' : 'table-row';
        var chev = row.querySelector('.vc-chev');
        if (chev) chev.style.transform = visible ? 'none' : 'rotate(90deg)';
    });
    </script>
    <?php endif; ?>
    <?php if (!empty($GLOBALS['__abc_drilldown_script'])): ?>
    <script>
    // ABC reports (clientes y productos) drill-down
    document.addEventListener('click', function(e) {
        var row = e.target.closest('.abc-row');
        if (!row) return;
        var rid = row.dataset.rid;
        var detail = document.querySelector('.abc-detail[data-rid="' + rid + '"]');
        if (!detail) return;
        var visible = detail.style.display !== 'none';
        detail.style.display = visible ? 'none' : 'table-row';
        var chev = row.querySelector('.abc-chev');
        if (chev) chev.style.transform = visible ? 'none' : 'rotate(90deg)';
    });
    </script>
    <?php endif; ?>
    <?php if (!empty($GLOBALS['__rep_filters_has_date_range'])): ?>
    <script>
    // Date presets: click llena Desde/Hasta y submit del form.
    // Event delegation a nivel document — Vue puede re-renderizar #bars
    // (donde vive el form) y los listeners directos se pierden.
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('button[data-preset-d]');
        if (!btn) return;
        var form = document.getElementById('rep-filters-form');
        if (!form) return;
        var dInput = document.getElementById('rep-filter-desde');
        var hInput = document.getElementById('rep-filter-hasta');
        if (!dInput || !hInput) return;
        e.preventDefault();
        dInput.value = btn.dataset.presetD;
        hInput.value = btn.dataset.presetH;
        form.submit();
    });
    </script>
    <?php endif; ?>
    <?php endif; ?>
</body>
</html>
