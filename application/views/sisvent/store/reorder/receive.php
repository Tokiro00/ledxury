<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Recepcion - Orden <?= $order->order_number ?></title>
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
                            <h2 class="text-xl font-bold text-gray-800">Recepcion - Orden <?= $order->order_number ?></h2>
                            <p class="text-sm text-gray-500">Registrar la mercancia recibida del proveedor</p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/store/reorder/view/<?= $order->id ?>" class="mt-2 lg:mt-0 text-sm text-mam-blue-petroleo hover:underline">← Volver a Orden</a>
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

                    <!-- Order Header Info -->
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Proveedor</p>
                                <p class="text-sm font-bold text-gray-800 mt-1"><?= $order->provider_name ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Tienda</p>
                                <p class="text-sm font-bold text-gray-800 mt-1"><?= $order->store_name ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Estado</p>
                                <p class="text-sm font-bold text-gray-800 mt-1"><?= ucfirst($order->status) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Receive Form -->
                    <form action="<?= base_url() ?>sisvent/store/reorder/processReceive/<?= $order->id ?>" method="POST">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

                        <div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-4">
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left" style="background:#1B365D; color:white;">
                                            <th class="px-3 py-2.5 font-semibold">Codigo</th>
                                            <th class="px-3 py-2.5 font-semibold">Descripcion</th>
                                            <th class="px-3 py-2.5 font-semibold text-right">Pedido</th>
                                            <th class="px-3 py-2.5 font-semibold text-right">Ya Recibido</th>
                                            <th class="px-3 py-2.5 font-semibold text-right">Pendiente</th>
                                            <th class="px-3 py-2.5 font-semibold text-right">Recibir Ahora</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $hasLines = false; if (!empty($details)): $i = 0; foreach ($details as $d):
                                            // Only show lines that are not fully received
                                            if ($d->status == 'recibido') continue;
                                            $hasLines = true;
                                            $i++;
                                            $pending = $d->quantityOrdered - $d->quantityReceived;
                                        ?>
                                        <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                            <td class="px-3 py-1.5 font-mono font-medium"><?= $d->idProduct ?></td>
                                            <td class="px-3 py-1.5"><?= $d->description ?></td>
                                            <td class="px-3 py-1.5 text-right"><?= number_format($d->quantityOrdered, 0, ',', '.') ?></td>
                                            <td class="px-3 py-1.5 text-right"><?= number_format($d->quantityReceived, 0, ',', '.') ?></td>
                                            <td class="px-3 py-1.5 text-right font-bold text-orange-600"><?= number_format($pending, 0, ',', '.') ?></td>
                                            <td class="px-3 py-1.5 text-right">
                                                <input type="number"
                                                       name="qty[<?= $d->id ?>]"
                                                       value="0"
                                                       min="0"
                                                       max="<?= $pending ?>"
                                                       class="w-20 text-xs text-right border border-gray-300 rounded px-2 py-1 focus:outline-none focus:border-blue-500">
                                            </td>
                                        </tr>
                                        <?php endforeach; endif; ?>

                                        <?php if (!$hasLines): ?>
                                        <tr>
                                            <td colspan="6" class="px-3 py-6 text-center text-gray-400">Todas las lineas han sido recibidas</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if ($hasLines): ?>
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-3 text-sm font-bold text-white rounded-lg" style="background:#2E7D91;"
                                    onclick="return confirm('¿Confirmar la recepcion de mercancia?')">
                                Confirmar Recepcion
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
