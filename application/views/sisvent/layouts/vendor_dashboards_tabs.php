<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Barra de tabs compartida por los 7 dashboards de vendedores.
 * Resuelve la fricción de UX que señalo la auditoría: los usuarios tenían
 * que volver al sidemenu para saltar entre Panel / Semanal / Acumulado /
 * Cierre / Mi Desempeño / Inactivos / Metas.
 *
 * Uso:
 *   $this->load->view('sisvent/layouts/vendor_dashboards_tabs', ['active' => 'panel']);
 *
 * Valores de $active: panel | semanal | acumulado | cierre | mi_desempeno | inactivos | metas
 */

$tabs = [
    'panel'        => ['label' => 'Panel',        'url' => 'sisvent/admin/salesboard'],
    'semanal'      => ['label' => 'Semanal',      'url' => 'sisvent/admin/tracking/semanal'],
    'acumulado'    => ['label' => 'Acumulado',    'url' => 'sisvent/admin/tracking/acumulado'],
    'cierre'       => ['label' => 'Cierre',       'url' => 'sisvent/admin/tracking/cierre'],
    'mi_desempeno' => ['label' => 'Mi Desempeño', 'url' => 'sisvent/admin/tracking/miDesempeno'],
    'inactivos'    => ['label' => 'Inactivos',    'url' => 'sisvent/admin/salesboard/inactivos'],
    'metas'        => ['label' => 'Metas',        'url' => 'sisvent/admin/salesboard/metas'],
];

$active = isset($active) ? $active : '';
?>
<div class="border-b border-gray-200 mb-4 bg-white -mx-4 px-4 pt-1">
    <nav class="flex gap-1 overflow-x-auto" aria-label="Dashboards de vendedores">
        <?php foreach ($tabs as $key => $tab): ?>
            <?php $isActive = ($key === $active); ?>
            <a href="<?= base_url($tab['url']) ?>"
               class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition <?= $isActive
                 ? 'border-mam-blue-petroleo text-mam-blue-petroleo'
                 : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                <?= $tab['label'] ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>
