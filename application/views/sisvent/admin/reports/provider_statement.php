<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Estado de Cuenta por Proveedor</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Estado de Cuenta por Proveedor</h2>
                            <p class="text-sm text-gray-500">Facturas, pagos y saldos pendientes con aging</p>
                        </div>
                        <form method="get" class="flex flex-wrap items-center gap-2 mt-3 lg:mt-0">
                            <select name="provider" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="">Todos los proveedores</option>
                                <?php foreach($providersList as $prov): ?>
                                <option value="<?= $prov->idProvider ?>" <?= $prov->idProvider == $providerFilter ? 'selected' : '' ?>><?= $prov->name ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="store" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="-1">Todas las bodegas</option>
                                <?php foreach($stores as $s): ?>
                                <option value="<?= $s->idStore ?>" <?= $s->idStore == $store ? 'selected' : '' ?>><?= $s->name ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="px-4 py-2 text-sm text-white rounded-lg" style="background:#2E7D91;">Filtrar</button>
                        </form>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Facturado</p>
                            <p class="text-lg font-bold text-gray-800 mt-1">$<?= number_format($totalInvoiced, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Pagado</p>
                            <p class="text-lg font-bold text-green-600 mt-1">$<?= number_format($totalPaid, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Saldo Pendiente</p>
                            <p class="text-lg font-bold text-red-600 mt-1">$<?= number_format($totalPending, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Vencido +90 dias</p>
                            <p class="text-lg font-bold text-red-800 mt-1">$<?= number_format($total90, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Aging Summary Bar -->
                    <?php if($totalPending > 0): ?>
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Distribucion por Antiguedad</p>
                        <div class="flex h-6 rounded-lg overflow-hidden">
                            <?php
                            $pct030 = $totalPending > 0 ? ($total030 / $totalPending) * 100 : 0;
                            $pct3160 = $totalPending > 0 ? ($total3160 / $totalPending) * 100 : 0;
                            $pct6190 = $totalPending > 0 ? ($total6190 / $totalPending) * 100 : 0;
                            $pct90p = $totalPending > 0 ? ($total90 / $totalPending) * 100 : 0;
                            ?>
                            <div class="bg-green-400 flex items-center justify-center text-xs text-white font-bold" style="width:<?= max($pct030, 1) ?>%">
                                <?php if($pct030 > 5): ?><?= number_format($pct030, 0) ?>%<?php endif; ?>
                            </div>
                            <div class="bg-yellow-400 flex items-center justify-center text-xs text-white font-bold" style="width:<?= max($pct3160, 1) ?>%">
                                <?php if($pct3160 > 5): ?><?= number_format($pct3160, 0) ?>%<?php endif; ?>
                            </div>
                            <div class="bg-orange-500 flex items-center justify-center text-xs text-white font-bold" style="width:<?= max($pct6190, 1) ?>%">
                                <?php if($pct6190 > 5): ?><?= number_format($pct6190, 0) ?>%<?php endif; ?>
                            </div>
                            <div class="bg-red-600 flex items-center justify-center text-xs text-white font-bold" style="width:<?= max($pct90p, 1) ?>%">
                                <?php if($pct90p > 5): ?><?= number_format($pct90p, 0) ?>%<?php endif; ?>
                            </div>
                        </div>
                        <div class="flex gap-4 mt-2 text-xs">
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-400 inline-block"></span> 0-30d: $<?= number_format($total030, 0, ',', '.') ?></span>
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-yellow-400 inline-block"></span> 31-60d: $<?= number_format($total3160, 0, ',', '.') ?></span>
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-orange-500 inline-block"></span> 61-90d: $<?= number_format($total6190, 0, ',', '.') ?></span>
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-600 inline-block"></span> +90d: $<?= number_format($total90, 0, ',', '.') ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold">#</th>
                                        <th class="px-3 py-2.5 font-semibold">Proveedor</th>
                                        <th class="px-3 py-2.5 font-semibold">NIT</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Facturas</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Facturado</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Pagado</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Pendiente</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">0-30d</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">31-60d</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">61-90d</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">+90d</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach($providers as $p): $i++; ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-2 text-gray-400"><?= $i ?></td>
                                        <td class="px-3 py-2 font-semibold"><?= $p->provider_name ?></td>
                                        <td class="px-3 py-2 text-gray-500 font-mono"><?= $p->provider_idnum ?></td>
                                        <td class="px-3 py-2 text-right"><?= $p->invoice_count ?></td>
                                        <td class="px-3 py-2 text-right">$<?= number_format($p->total_invoiced, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right text-green-700">$<?= number_format($p->total_paid, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right font-bold text-red-700">$<?= number_format($p->total_pending, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right <?= (float)$p->aging_0_30 > 0 ? 'text-green-700' : 'text-gray-300' ?>">$<?= number_format($p->aging_0_30, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right <?= (float)$p->aging_31_60 > 0 ? 'text-yellow-700' : 'text-gray-300' ?>">$<?= number_format($p->aging_31_60, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right <?= (float)$p->aging_61_90 > 0 ? 'text-orange-600' : 'text-gray-300' ?>">$<?= number_format($p->aging_61_90, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right <?= (float)$p->aging_90_plus > 0 ? 'text-red-700 font-bold' : 'text-gray-300' ?>">$<?= number_format($p->aging_90_plus, 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1B365D; color:white;" class="font-bold text-xs">
                                        <td class="px-3 py-2.5" colspan="3">TOTALES (<?= count($providers) ?> proveedores)</td>
                                        <td class="px-3 py-2.5 text-right"><?= array_sum(array_column($providers, 'invoice_count')) ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($totalInvoiced, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($totalPaid, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($totalPending, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($total030, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($total3160, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($total6190, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($total90, 0, ',', '.') ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
</body>
</html>
