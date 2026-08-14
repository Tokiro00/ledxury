<?php
/**
 * Design System preview — specimens de los 5 partials canonicos.
 *
 * Acceso: /sisvent/admin/designsystem (controller en
 * application/controllers/sisvent/admin/Designsystem.php).
 *
 * Restringido a roles 1 (superadmin) y 2 (admin) por el controller.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
    <title>Design System Preview — v1.28.0</title>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full overflow-x-hidden">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto" style="background: var(--bg-page);">
            <div class="container mx-auto px-6 py-6">

                <header class="mb-8">
                    <h1 style="color: var(--mam-blue-dark);" class="text-3xl font-bold">Design System Preview</h1>
                    <p class="mt-1 text-sm" style="color: var(--fg-2);">
                        v1.28.0 — specimens de los 5 partials canonicos. Esta vista vive en
                        <code>application/views/sisvent/design-system/preview.php</code> como referencia
                        para devs nuevos. Cualquier ajuste a los partials debe verse aca primero.
                    </p>
                </header>

                <!-- ============ BUTTONS ============ -->
                <section class="mb-10">
                    <h2 class="mb-3 text-lg font-semibold" style="color: var(--mam-blue-dark);">Button</h2>
                    <p class="text-xs mb-4" style="color: var(--fg-3);">
                        <code>design-system/_button.php</code> — variants: primary, secondary, ghost, danger, success.
                    </p>
                    <div class="bg-white rounded-lg p-5" style="box-shadow: var(--shadow-soft); display: flex; gap: 12px; flex-wrap: wrap;">
                        <?php $this->load->view('sisvent/design-system/_button', ['variant' => 'primary',   'label' => 'Guardar']); ?>
                        <?php $this->load->view('sisvent/design-system/_button', ['variant' => 'secondary', 'label' => 'Cancelar']); ?>
                        <?php $this->load->view('sisvent/design-system/_button', ['variant' => 'ghost',     'label' => 'Ver detalle']); ?>
                        <?php $this->load->view('sisvent/design-system/_button', ['variant' => 'danger',    'label' => 'Anular']); ?>
                        <?php $this->load->view('sisvent/design-system/_button', ['variant' => 'success',   'label' => 'Aprobar']); ?>
                        <?php $this->load->view('sisvent/design-system/_button', ['variant' => 'primary',   'label' => 'Disabled', 'attrs' => ['disabled' => 'disabled']]); ?>
                    </div>
                </section>

                <!-- ============ PILLS ============ -->
                <section class="mb-10">
                    <h2 class="mb-3 text-lg font-semibold" style="color: var(--mam-blue-dark);">Pill</h2>
                    <p class="text-xs mb-4" style="color: var(--fg-3);">
                        <code>design-system/_pill.php</code> — vocabulario cerrado (lowercase). Tones: success, warning, danger, gray, info.
                    </p>
                    <div class="bg-white rounded-lg p-5" style="box-shadow: var(--shadow-soft);">
                        <div class="mb-3" style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <span class="text-xs font-bold uppercase tracking-wider mr-2" style="color: var(--fg-4);">Factura:</span>
                            <?php $this->load->view('sisvent/design-system/_pill', ['tone' => 'success', 'label' => 'pagada']); ?>
                            <?php $this->load->view('sisvent/design-system/_pill', ['tone' => 'warning', 'label' => 'pendiente']); ?>
                            <?php $this->load->view('sisvent/design-system/_pill', ['tone' => 'info',    'label' => 'parcial']); ?>
                            <?php $this->load->view('sisvent/design-system/_pill', ['tone' => 'danger',  'label' => 'vencida']); ?>
                            <?php $this->load->view('sisvent/design-system/_pill', ['tone' => 'gray',    'label' => 'anulada']); ?>
                        </div>
                        <div class="mb-3" style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <span class="text-xs font-bold uppercase tracking-wider mr-2" style="color: var(--fg-4);">Vendedor:</span>
                            <?php $this->load->view('sisvent/design-system/_pill', ['tone' => 'success', 'label' => 'activo']); ?>
                            <?php $this->load->view('sisvent/design-system/_pill', ['tone' => 'warning', 'label' => 'en riesgo']); ?>
                            <?php $this->load->view('sisvent/design-system/_pill', ['tone' => 'danger',  'label' => 'crítico']); ?>
                            <?php $this->load->view('sisvent/design-system/_pill', ['tone' => 'gray',    'label' => 'inactivo']); ?>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <span class="text-xs font-bold uppercase tracking-wider mr-2" style="color: var(--fg-4);">Producto:</span>
                            <?php $this->load->view('sisvent/design-system/_pill', ['tone' => 'success', 'label' => 'en stock']); ?>
                            <?php $this->load->view('sisvent/design-system/_pill', ['tone' => 'warning', 'label' => 'bajo stock']); ?>
                            <?php $this->load->view('sisvent/design-system/_pill', ['tone' => 'danger',  'label' => 'agotado']); ?>
                        </div>
                    </div>
                </section>

                <!-- ============ FILTER CHIPS ============ -->
                <section class="mb-10">
                    <h2 class="mb-3 text-lg font-semibold" style="color: var(--mam-blue-dark);">FilterChip</h2>
                    <p class="text-xs mb-4" style="color: var(--fg-3);">
                        <code>design-system/_filter_chip.php</code> — toolbar sticky de filtros. Default activo + dashed para "+ filtro".
                    </p>
                    <div class="bg-white rounded-lg p-5" style="box-shadow: var(--shadow-soft); display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                        <?php $this->load->view('sisvent/design-system/_filter_chip', ['label' => 'Vendedor: Carlos Jaimes', 'onclick_remove' => 'alert(\'remove vendedor\')']); ?>
                        <?php $this->load->view('sisvent/design-system/_filter_chip', ['label' => 'Estado: pendiente']); ?>
                        <?php $this->load->view('sisvent/design-system/_filter_chip', ['label' => 'Fecha: este mes']); ?>
                        <?php $this->load->view('sisvent/design-system/_filter_chip', ['label' => '+ filtro', 'dashed' => true, 'href' => '#']); ?>
                    </div>
                </section>

                <!-- ============ AVATARS ============ -->
                <section class="mb-10">
                    <h2 class="mb-3 text-lg font-semibold" style="color: var(--mam-blue-dark);">Avatar</h2>
                    <p class="text-xs mb-4" style="color: var(--fg-3);">
                        <code>design-system/_avatar.php</code> — tones: brand, danger, purple, inactive. Sizes 24-64px.
                    </p>
                    <div class="bg-white rounded-lg p-5" style="box-shadow: var(--shadow-soft); display: flex; gap: 14px; align-items: center;">
                        <?php $this->load->view('sisvent/design-system/_avatar', ['initials' => 'CJ', 'size' => 24, 'tone' => 'brand']); ?>
                        <?php $this->load->view('sisvent/design-system/_avatar', ['initials' => 'AM', 'size' => 32, 'tone' => 'brand']); ?>
                        <?php $this->load->view('sisvent/design-system/_avatar', ['initials' => 'EG', 'size' => 40, 'tone' => 'brand']); ?>
                        <?php $this->load->view('sisvent/design-system/_avatar', ['initials' => 'VM', 'size' => 48, 'tone' => 'danger']); ?>
                        <?php $this->load->view('sisvent/design-system/_avatar', ['initials' => 'PR', 'size' => 48, 'tone' => 'purple']); ?>
                        <?php $this->load->view('sisvent/design-system/_avatar', ['initials' => 'IN', 'size' => 48, 'tone' => 'inactive']); ?>
                        <?php $this->load->view('sisvent/design-system/_avatar', ['initials' => 'XL', 'size' => 64, 'tone' => 'brand']); ?>
                    </div>
                </section>

                <!-- ============ KPI TILES ============ -->
                <section class="mb-10">
                    <h2 class="mb-3 text-lg font-semibold" style="color: var(--mam-blue-dark);">KpiTile</h2>
                    <p class="text-xs mb-4" style="color: var(--fg-3);">
                        <code>design-system/_kpi_tile.php</code> — accent customizable, wash gradient automatico, hover lift.
                        Combinable en grid segun el layout de la vista. Reemplaza progresivamente a <code>_kpi_strip.php</code>.
                    </p>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
                        <?php $this->load->view('sisvent/design-system/_kpi_tile', [
                            'eyebrow'    => 'Ventas hoy',
                            'value'      => '$487.2M',
                            'delta'      => '▲ $12.4M vs ayer',
                            'delta_tone' => 'up',
                            'accent'     => 'var(--mam-green-program)',
                        ]); ?>
                        <?php $this->load->view('sisvent/design-system/_kpi_tile', [
                            'eyebrow'    => 'Cobros mes',
                            'value'      => '$312.4M',
                            'delta'      => '67% de meta',
                            'delta_tone' => 'mid',
                            'accent'     => 'var(--mam-yellow)',
                        ]); ?>
                        <?php $this->load->view('sisvent/design-system/_kpi_tile', [
                            'eyebrow'    => 'Cartera vencida',
                            'value'      => '$94.2M',
                            'delta'      => '▼ $8.1M vs mes anterior',
                            'delta_tone' => 'dn',
                            'accent'     => 'var(--mam-red)',
                        ]); ?>
                        <?php $this->load->view('sisvent/design-system/_kpi_tile', [
                            'eyebrow'    => 'Pedidos pendientes',
                            'value'      => '142',
                            'delta'      => '12 sin asignar',
                            'delta_tone' => 'fg4',
                            'accent'     => 'var(--mam-blue-petroleo)',
                        ]); ?>
                    </div>
                </section>

                <!-- ============ TOKENS reference ============ -->
                <section class="mb-10">
                    <h2 class="mb-3 text-lg font-semibold" style="color: var(--mam-blue-dark);">Tokens</h2>
                    <p class="text-xs mb-4" style="color: var(--fg-3);">
                        Source of truth: <code>tailwind.config.js</code>. Espejo SCSS:
                        <code>public/assets/css/_tokens.scss</code>. Ambos viven sincronizados; el linter
                        (<code>composer lint:views</code>) valida que ninguna view tenga hex hardcoded.
                    </p>
                    <div class="bg-white rounded-lg p-5" style="box-shadow: var(--shadow-soft);">
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px;">
                            <?php
                            $swatches = [
                                ['mam-blue-petroleo',  '#4487A0'],
                                ['mam-blue-dark',      '#2B3164'],
                                ['mam-orange',         '#F7941D'],
                                ['mam-green-program',  '#31AB20'],
                                ['mam-yellow',         '#FEAB2F'],
                                ['mam-purple',         '#5D41CC'],
                                ['mam-red',            '#ef0d0d'],
                                ['mam-gray-default',   '#7F8392'],
                            ];
                            foreach ($swatches as $sw): ?>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="width:24px;height:24px;border-radius:4px;background:<?= $sw[1] ?>;border:1px solid var(--mam-gray-300);"></span>
                                    <div>
                                        <div class="text-xs font-semibold" style="color: var(--fg-1);"><?= $sw[0] ?></div>
                                        <div class="text-xs" style="color: var(--fg-4);font-family: var(--font-mono);"><?= $sw[1] ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
