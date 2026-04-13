<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
    <title>Detalle Contrapago #<?= $batch->id ?> - Ledxury</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Detalle Pago Contrapago #<?= $batch->id ?></h2>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($batch->filename) ?> — <?= $batch->sheet_name ?></p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/admin/contrapagos" class="text-sm text-blue-600 hover:underline">&larr; Volver</a>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">Total Recaudado</p>
                            <p class="text-lg font-bold text-green-600 mt-1">$<?= number_format($batch->total_valor, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">Guias</p>
                            <p class="text-lg font-bold text-gray-800 mt-1"><?= $batch->total_guias ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">Cruzadas</p>
                            <p class="text-lg font-bold text-green-600 mt-1"><?= $batch->matched ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">Sin Match</p>
                            <p class="text-lg font-bold <?= $batch->unmatched > 0 ? 'text-red-600' : 'text-gray-400' ?> mt-1"><?= $batch->unmatched ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">Estado</p>
                            <?php
                                $stClass = 'bg-gray-100 text-gray-600';
                                if ($batch->status === 'importado') $stClass = 'bg-yellow-100 text-yellow-700';
                                elseif ($batch->status === 'conciliado') $stClass = 'bg-blue-100 text-blue-700';
                                elseif ($batch->status === 'registrado') $stClass = 'bg-green-100 text-green-700';
                            ?>
                            <p class="mt-1"><span class="px-3 py-1 text-xs font-bold rounded-full <?= $stClass ?>"><?= ucfirst($batch->status) ?></span></p>
                        </div>
                    </div>

                    <!-- Payments Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="px-4 py-3 border-b">
                            <h3 class="text-sm font-bold text-gray-600">Detalle de Guias</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs font-medium text-gray-500 uppercase bg-gray-50">
                                        <th class="px-3 py-3 text-left">#</th>
                                        <th class="px-3 py-3 text-left">Guia</th>
                                        <th class="px-3 py-3 text-left">Destinatario</th>
                                        <th class="px-3 py-3 text-center">Fecha Venta</th>
                                        <th class="px-3 py-3 text-right">Valor</th>
                                        <th class="px-3 py-3 text-center">Conciliacion</th>
                                        <th class="px-3 py-3 text-center">Factura</th>
                                        <th class="px-3 py-3 text-center">Estado</th>
                                        <th class="px-3 py-3 text-left">Observacion</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php $i = 0; foreach ($payments as $p): $i++; ?>
                                    <?php
                                        $rowClass = '';
                                        $stBadge = 'bg-gray-100 text-gray-600';
                                        if ($p->status === 'conciliado') { $stBadge = 'bg-green-100 text-green-700'; }
                                        elseif ($p->status === 'sin_match') { $stBadge = 'bg-red-100 text-red-700'; $rowClass = 'bg-red-50'; }
                                    ?>
                                    <tr class="hover:bg-gray-50 <?= $rowClass ?>">
                                        <td class="px-3 py-2 text-gray-400"><?= $i ?></td>
                                        <td class="px-3 py-2 font-mono font-medium"><?= $p->numeroGuia ?></td>
                                        <td class="px-3 py-2"><?= htmlspecialchars($p->nombreDestinatario) ?></td>
                                        <td class="px-3 py-2 text-center"><?= $p->fechaVenta ? date('d/m/Y', strtotime($p->fechaVenta)) : '-' ?></td>
                                        <td class="px-3 py-2 text-right font-bold">$<?= number_format($p->valorTotal, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-center"><?= $p->conciliacion ?: '-' ?></td>
                                        <td class="px-3 py-2 text-center">
                                            <?php if ($p->invoice_id): ?>
                                                <a href="<?= base_url() ?>sisvent/commercial/invoices/view/<?= $p->invoice_id ?>" class="text-blue-600 hover:underline font-medium">#<?= $p->invoice_id ?></a>
                                            <?php else: ?>
                                                <span class="text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $stBadge ?>"><?= ucfirst(str_replace('_', ' ', $p->status)) ?></span>
                                        </td>
                                        <td class="px-3 py-2 text-xs text-gray-500"><?= $p->observacion ? htmlspecialchars($p->observacion) : '' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-50 font-bold">
                                        <td class="px-3 py-3" colspan="4">TOTAL</td>
                                        <td class="px-3 py-3 text-right text-green-600">$<?= number_format($batch->total_valor, 0, ',', '.') ?></td>
                                        <td colspan="4"></td>
                                    </tr>
                                </tfoot>
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
