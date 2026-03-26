<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];

$statusLabels = [
    'borrador'  => ['label' => 'Borrador',  'class' => 'bg-gray-400 text-white'],
    'pendiente' => ['label' => 'Pendiente', 'class' => 'bg-yellow-500 text-white'],
    'enviada'   => ['label' => 'Enviada',   'class' => 'bg-blue-500 text-white'],
    'parcial'   => ['label' => 'Parcial',   'class' => 'bg-orange-500 text-white'],
    'recibida'  => ['label' => 'Recibida',  'class' => 'bg-green-500 text-white'],
    'cancelada' => ['label' => 'Cancelada', 'class' => 'bg-red-500 text-white'],
];

$lineStatusLabels = [
    'pendiente' => ['label' => 'Pendiente', 'class' => 'bg-yellow-100 text-yellow-800'],
    'parcial'   => ['label' => 'Parcial',   'class' => 'bg-orange-100 text-orange-800'],
    'recibido'  => ['label' => 'Recibido',  'class' => 'bg-green-100 text-green-800'],
    'cancelado' => ['label' => 'Cancelado', 'class' => 'bg-red-100 text-red-800'],
];

$st = isset($statusLabels[$order->status]) ? $statusLabels[$order->status] : ['label' => $order->status, 'class' => 'bg-gray-400 text-white'];
$sourceLabel = ($order->source == 'agente') ? 'Agente de Reorden' : 'Manual';
?>
<!DOCTYPE html>
<html lang="en">
    <title>Orden <?= $order->order_number ?></title>
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
                            <h2 class="text-xl font-bold text-gray-800">Orden <?= $order->order_number ?></h2>
                            <p class="text-sm text-gray-500">Detalle de la orden de compra</p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/store/reorder/orders" class="mt-2 lg:mt-0 text-sm text-mam-blue-petroleo hover:underline">← Volver a Ordenes</a>
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

                    <!-- Order Header Card -->
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Proveedor</p>
                                <p class="text-sm font-bold text-gray-800 mt-1"><?= $order->provider_name ?></p>
                                <?php if (!empty($order->provider_nit)): ?>
                                <p class="text-xs text-gray-500">NIT: <?= $order->provider_nit ?></p>
                                <?php endif; ?>
                                <?php if (!empty($order->provider_phone)): ?>
                                <p class="text-xs text-gray-500">Tel: <?= $order->provider_phone ?></p>
                                <?php endif; ?>
                                <?php if (!empty($order->provider_email)): ?>
                                <p class="text-xs text-gray-500"><?= $order->provider_email ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Tienda</p>
                                <p class="text-sm font-bold text-gray-800 mt-1"><?= $order->store_name ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Estado</p>
                                <p class="mt-1">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold <?= $st['class'] ?>"><?= $st['label'] ?></span>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">Generado: <?= $sourceLabel ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Fechas</p>
                                <p class="text-sm text-gray-800 mt-1">Creacion: <?= date('d/m/Y', strtotime($order->created_at)) ?></p>
                                <?php if (!empty($order->expected_date)): ?>
                                <p class="text-sm text-gray-800">Esperada: <?= date('d/m/Y', strtotime($order->expected_date)) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($order->received_date)): ?>
                                <p class="text-sm text-gray-800">Recibida: <?= date('d/m/Y', strtotime($order->received_date)) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Details Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-4">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold">Codigo</th>
                                        <th class="px-3 py-2.5 font-semibold">Descripcion</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">ABC</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Cantidad Pedida</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Cantidad Recibida</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Costo Unit</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Subtotal</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Estado Linea</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $total = 0; if (!empty($details)): $i = 0; foreach ($details as $d): $i++;
                                        $lineTotal = $d->quantityOrdered * $d->unitCost;
                                        $total += $lineTotal;
                                        $badgeClass = '';
                                        switch ($d->abc_type) {
                                            case 'A': $badgeClass = 'bg-green-500 text-white'; break;
                                            case 'B': $badgeClass = 'bg-yellow-500 text-white'; break;
                                            case 'C': $badgeClass = 'bg-red-500 text-white'; break;
                                            default:  $badgeClass = 'bg-gray-400 text-white'; break;
                                        }
                                        $ls = isset($lineStatusLabels[$d->status]) ? $lineStatusLabels[$d->status] : ['label' => $d->status, 'class' => 'bg-gray-100 text-gray-800'];
                                    ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-1.5 font-mono font-medium"><?= $d->idProduct ?></td>
                                        <td class="px-3 py-1.5"><?= $d->description ?></td>
                                        <td class="px-3 py-1.5 text-center">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold <?= $badgeClass ?>"><?= $d->abc_type ?></span>
                                        </td>
                                        <td class="px-3 py-1.5 text-right"><?= number_format($d->quantityOrdered, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right"><?= number_format($d->quantityReceived, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right">$<?= number_format($d->unitCost, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right font-medium">$<?= number_format($lineTotal, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-center">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold <?= $ls['class'] ?>"><?= $ls['label'] ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1B365D; color:white;" class="font-bold text-xs">
                                        <td class="px-3 py-2.5" colspan="6">TOTAL</td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($total, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <?php if ($order->status == 'borrador'): ?>
                                <a href="<?= base_url() ?>sisvent/store/reorder/approve/<?= $order->id ?>"
                                   class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#2E7D91;"
                                   onclick="return confirm('¿Aprobar esta orden?')">
                                    Aprobar
                                </a>
                                <a href="<?= base_url() ?>sisvent/store/reorder/cancel/<?= $order->id ?>"
                                   class="px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600"
                                   onclick="return confirm('¿Cancelar esta orden?')">
                                    Cancelar
                                </a>

                            <?php elseif ($order->status == 'pendiente'): ?>
                                <form action="<?= base_url() ?>sisvent/store/reorder/markSent/<?= $order->id ?>" method="POST" class="flex items-center gap-2">
                                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                    <label class="text-sm text-gray-600">Fecha esperada de entrega:</label>
                                    <input type="date" name="expectedDate" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500" required>
                                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#2E7D91;">
                                        Marcar Enviada
                                    </button>
                                </form>
                                <a href="<?= base_url() ?>sisvent/store/reorder/cancel/<?= $order->id ?>"
                                   class="px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600"
                                   onclick="return confirm('¿Cancelar esta orden?')">
                                    Cancelar
                                </a>

                            <?php elseif ($order->status == 'enviada' || $order->status == 'parcial'): ?>
                                <a href="<?= base_url() ?>sisvent/store/reorder/receive/<?= $order->id ?>"
                                   class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#2E7D91;">
                                    Recibir Mercancia
                                </a>

                            <?php elseif ($order->status == 'recibida'): ?>
                                <p class="text-sm text-green-600 font-medium">Esta orden ha sido recibida completamente.</p>

                            <?php elseif ($order->status == 'cancelada'): ?>
                                <p class="text-sm text-red-600 font-medium">Esta orden fue cancelada.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
