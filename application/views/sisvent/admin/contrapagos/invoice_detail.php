<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$stClass = 'bg-gray-100 text-gray-500';
$stLabel = ucfirst($invoice->status);
if ($invoice->status === 'pendiente') { $stClass = 'bg-yellow-100 text-yellow-700'; $stLabel = 'Pendiente'; }
elseif ($invoice->status === 'descontada') { $stClass = 'bg-green-100 text-green-700'; $stLabel = 'Descontada'; }
elseif ($invoice->status === 'pagada') { $stClass = 'bg-blue-100 text-blue-700'; $stLabel = 'Pagada'; }
?>
<!DOCTYPE html>
<html lang="en">
    <title>Factura Inter #<?= $invoice->numero_factura ?> - Ledxury</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/contrapagos/index', 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 py-5 w-full max-w-screen-xl mx-auto">

                    <!-- Header -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-5">
                        <div class="flex items-center gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-700">Factura Inter #<?= $invoice->numero_factura ?></h2>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Fecha: <?= $invoice->fecha_corte ? date('d/m/Y', strtotime($invoice->fecha_corte)) : '-' ?>
                                    &middot; <?= htmlspecialchars($invoice->razon_social) ?>
                                    &middot; NIT <?= $invoice->nit ?>
                                </p>
                            </div>
                            <span class="px-2.5 py-0.5 text-xs font-bold rounded-full <?= $stClass ?>"><?= $stLabel ?></span>
                        </div>
                        <div class="flex items-center gap-2 mt-3 lg:mt-0">
                            <?php if ($batch): ?>
                            <a href="<?= base_url() ?>sisvent/admin/contrapagos/view/<?= $batch->id ?>"
                               class="px-4 py-2 text-xs font-medium text-mam-blue-petroleo hover:text-white hover:bg-mam-blue-petroleo border border-mam-blue-petroleo rounded-lg transition-colors">Ver Pago #<?= $batch->id ?></a>
                            <?php endif; ?>
                            <a href="<?= base_url() ?>sisvent/admin/contrapagos/invoices" class="px-4 py-2 text-xs text-gray-500 hover:text-gray-700">&larr; Volver</a>
                        </div>
                    </div>

                    <?php if ($invoice->descuento_observacion): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-5">
                        <p class="text-sm text-green-800"><span class="font-bold">Descontada en pago:</span> <?= htmlspecialchars($invoice->descuento_observacion) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- KPIs -->
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Guias</p>
                            <p class="text-xl font-bold text-gray-700 mt-1"><?= $invoice->total_guias ?></p>
                        </div>
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Transporte</p>
                            <p class="text-xl font-bold text-gray-700 mt-1">$<?= number_format($invoice->valor_transporte, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Seguro</p>
                            <p class="text-xl font-bold text-gray-700 mt-1">$<?= number_format($invoice->valor_seguro, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Adicionales</p>
                            <p class="text-xl font-bold text-gray-700 mt-1">$<?= number_format($invoice->valor_adicionales, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">TOTAL</p>
                            <p class="text-xl font-bold text-red-600 mt-1">$<?= number_format($invoice->valor_total, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="bg-white rounded-lg border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D;">
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase tracking-wide">#</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase tracking-wide">Guia</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase tracking-wide">Destino</th>
                                        <th class="px-3 py-2.5 text-right text-xs font-semibold text-white uppercase tracking-wide">V.Comercial</th>
                                        <th class="px-3 py-2.5 text-right text-xs font-semibold text-white uppercase tracking-wide">Flete</th>
                                        <th class="px-3 py-2.5 text-right text-xs font-semibold text-white uppercase tracking-wide">Seguro</th>
                                        <th class="px-3 py-2.5 text-right text-xs font-semibold text-white uppercase tracking-wide">Total</th>
                                        <th class="px-3 py-2.5 text-center text-xs font-semibold text-white uppercase tracking-wide">Factura Sistema</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase tracking-wide">Cliente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($items as $it): $i++; ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-2 text-gray-400"><?= $i ?></td>
                                        <td class="px-3 py-2 font-mono font-medium text-gray-700"><?= $it->numero_guia ?></td>
                                        <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($it->ciudad_destino) ?></td>
                                        <td class="px-3 py-2 text-right text-gray-600">$<?= number_format($it->valor_comercial, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right font-medium text-red-600">$<?= number_format($it->valor_transporte, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right text-gray-500">$<?= number_format($it->valor_prima, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right font-bold text-gray-800">$<?= number_format($it->valor_total, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-center">
                                            <?php if (isset($it->sys_invoice_id) && $it->sys_invoice_id): ?>
                                                <a href="<?= base_url() ?>sisvent/commercial/invoices/view/<?= $it->sys_invoice_id ?>" class="text-mam-blue-petroleo hover:underline font-medium">#<?= $it->sys_invoice_id ?></a>
                                            <?php else: ?>
                                                <span class="text-gray-300">sin match</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-gray-500"><?= isset($it->client_name) ? htmlspecialchars($it->client_name) : '-' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-100 border-t-2 border-gray-300">
                                        <td colspan="4" class="px-3 py-2.5 text-right font-bold text-gray-500 uppercase">Total</td>
                                        <td class="px-3 py-2.5 text-right font-bold text-red-700">$<?= number_format($invoice->valor_transporte, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right font-bold text-gray-700">$<?= number_format($invoice->valor_seguro, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right font-bold text-red-700">$<?= number_format($invoice->valor_total, 0, ',', '.') ?></td>
                                        <td colspan="2"></td>
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
