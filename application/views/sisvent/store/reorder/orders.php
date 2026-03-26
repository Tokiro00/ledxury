<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$selectedStore = isset($storeFilter) ? $storeFilter : '';
$selectedStatus = isset($statusFilter) ? $statusFilter : '';

$statusLabels = [
    'borrador'  => ['label' => 'Borrador',  'class' => 'bg-gray-400 text-white'],
    'pendiente' => ['label' => 'Pendiente', 'class' => 'bg-yellow-500 text-white'],
    'enviada'   => ['label' => 'Enviada',   'class' => 'bg-blue-500 text-white'],
    'parcial'   => ['label' => 'Parcial',   'class' => 'bg-orange-500 text-white'],
    'recibida'  => ['label' => 'Recibida',  'class' => 'bg-green-500 text-white'],
    'cancelada' => ['label' => 'Cancelada', 'class' => 'bg-red-500 text-white'],
];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Ordenes de Compra a Proveedores</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <!-- Title -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Ordenes de Compra a Proveedores</h2>
                            <p class="text-sm text-gray-500">Gestion de ordenes de compra generadas por el agente de reorden</p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/store/reorder/agent" class="mt-2 lg:mt-0 text-sm text-mam-blue-petroleo hover:underline">← Agente de Reorden</a>
                    </div>

                    <!-- Flash Messages -->
                    <?php if ($this->session->flashdata('success_reorder')): ?>
                        <div class="p-4 mb-4 text-sm font-semibold text-white bg-green-600 rounded-lg shadow-md">
                            <p><?= $this->session->flashdata('success_reorder') ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($this->session->flashdata('error_reorder')): ?>
                        <div class="p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?= $this->session->flashdata('error_reorder') ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Filters -->
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <form method="get" class="flex flex-wrap items-center gap-2">
                            <select name="store" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="">Todas las bodegas</option>
                                <?php if (!empty($stores)): foreach ($stores as $s): ?>
                                <option value="<?= $s->idStore ?>" <?= $selectedStore == $s->idStore ? 'selected' : '' ?>><?= $s->name ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                            <select name="status" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="">Todos los estados</option>
                                <?php foreach ($statusLabels as $key => $val): ?>
                                <option value="<?= $key ?>" <?= $selectedStatus == $key ? 'selected' : '' ?>><?= $val['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="px-4 py-2 text-sm text-white rounded-lg" style="background:#2E7D91;">Filtrar</button>
                        </form>
                    </div>

                    <!-- Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold">#</th>
                                        <th class="px-3 py-2.5 font-semibold">No. Orden</th>
                                        <th class="px-3 py-2.5 font-semibold">Proveedor</th>
                                        <th class="px-3 py-2.5 font-semibold">Tienda</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Estado</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Total</th>
                                        <th class="px-3 py-2.5 font-semibold">Fecha</th>
                                        <th class="px-3 py-2.5 font-semibold">Fecha Esperada</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($orders)): $i = 0; foreach ($orders as $o): $i++;
                                        $st = isset($statusLabels[$o->status]) ? $statusLabels[$o->status] : ['label' => $o->status, 'class' => 'bg-gray-400 text-white'];
                                    ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-1.5 text-gray-400 font-bold"><?= $i ?></td>
                                        <td class="px-3 py-1.5 font-mono font-bold"><?= $o->order_number ?></td>
                                        <td class="px-3 py-1.5"><?= $o->provider_name ?></td>
                                        <td class="px-3 py-1.5 text-gray-500"><?= $o->store_name ?></td>
                                        <td class="px-3 py-1.5 text-center">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold <?= $st['class'] ?>"><?= $st['label'] ?></span>
                                        </td>
                                        <td class="px-3 py-1.5 text-right font-medium">$<?= number_format($o->total, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5"><?= date('d/m/Y', strtotime($o->created_at)) ?></td>
                                        <td class="px-3 py-1.5"><?= !empty($o->expected_date) ? date('d/m/Y', strtotime($o->expected_date)) : '-' ?></td>
                                        <td class="px-3 py-1.5 text-center">
                                            <a href="<?= base_url() ?>sisvent/store/reorder/view/<?= $o->id ?>" class="inline-flex items-center px-2 py-1 text-xs font-medium text-mam-blue-petroleo hover:underline">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                Ver
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr>
                                        <td colspan="9" class="px-3 py-6 text-center text-gray-400">No hay ordenes de compra para mostrar</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
