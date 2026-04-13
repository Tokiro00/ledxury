<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Comisiones de Vendedores</title>
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
                            <h2 class="text-xl font-bold text-gray-800">Comisiones de Vendedores</h2>
                            <p class="text-sm text-gray-500">Ventas, comisiones generadas, liquidaciones y saldo pendiente</p>
                        </div>
                        <form method="get" class="flex flex-wrap items-center gap-2 mt-3 lg:mt-0">
                            <select name="year" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <select name="month" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="">Todo el ano</option>
                                <?php foreach($month_names as $mi => $mn): ?>
                                <option value="<?= $mi ?>" <?= $mi == $month ? 'selected' : '' ?>><?= $mn ?></option>
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
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Ventas</p>
                            <p class="text-lg font-bold text-gray-800 mt-1">$<?= number_format($grandSales, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Comisiones Generadas</p>
                            <p class="text-lg font-bold text-blue-600 mt-1">$<?= number_format($grandCommission, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Liquidaciones Pagadas</p>
                            <p class="text-lg font-bold text-green-600 mt-1">$<?= number_format($grandSettled, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Saldo Pendiente</p>
                            <p class="text-lg font-bold <?= $grandPending > 0 ? 'text-red-600' : 'text-green-600' ?> mt-1">$<?= number_format($grandPending, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Summary Table per Vendor -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-4">
                        <div class="px-4 py-2 border-b" style="background:#f8fafc;"><span class="text-sm font-semibold text-gray-700">Resumen por Vendedor</span></div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold">#</th>
                                        <th class="px-3 py-2.5 font-semibold">Vendedor</th>
                                        <th class="px-3 py-2.5 font-semibold">Tienda</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">% Comision</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Total Ventas</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Comision Generada</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Liquidado</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Saldo Pendiente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach($vendorSummary as $vid => $v): $i++;
                                        $pending = $v['total_commission'] - $v['total_settled'];
                                    ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-2 text-gray-400"><?= $i ?></td>
                                        <td class="px-3 py-2 font-semibold"><?= $v['name'] ?></td>
                                        <td class="px-3 py-2 text-gray-500"><?= $v['store'] ?></td>
                                        <td class="px-3 py-2 text-right"><?= number_format($v['commission_perc'], 1) ?>%</td>
                                        <td class="px-3 py-2 text-right font-medium">$<?= number_format($v['total_sales'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right font-medium text-blue-700">$<?= number_format($v['total_commission'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right text-green-700">$<?= number_format($v['total_settled'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right font-bold <?= $pending > 0 ? 'text-red-700' : 'text-green-700' ?>">$<?= number_format($pending, 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1B365D; color:white;" class="font-bold text-xs">
                                        <td class="px-3 py-2.5" colspan="4">TOTALES</td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($grandSales, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($grandCommission, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($grandSettled, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($grandPending, 0, ',', '.') ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Monthly Detail Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="px-4 py-2 border-b" style="background:#f8fafc;"><span class="text-sm font-semibold text-gray-700">Comisiones Mensuales por Vendedor</span></div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold sticky left-0 z-10" style="background:#1B365D; min-width:160px;">Vendedor</th>
                                        <?php foreach($month_names as $mi => $mn): ?>
                                        <th class="px-2 py-2.5 font-semibold text-center" style="min-width:80px;"><?= $mn ?></th>
                                        <?php endforeach; ?>
                                        <th class="px-3 py-2.5 font-semibold text-right" style="background:#0d2340; min-width:100px;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach($vendorSummary as $vid => $v): $i++; ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-2 sticky left-0 z-10 <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?>">
                                            <div class="font-semibold"><?= $v['name'] ?></div>
                                            <div class="text-gray-400" style="font-size:10px;"><?= $v['commission_perc'] ?>% com.</div>
                                        </td>
                                        <?php foreach($month_names as $mi => $mn):
                                            $mSales = $v['months'][$mi]['sales'];
                                            $mComm = $v['months'][$mi]['commission'];
                                        ?>
                                        <td class="px-2 py-1.5 text-center">
                                            <div class="<?= $mSales > 0 ? 'text-gray-800' : 'text-gray-300' ?>"><?= $mSales > 0 ? '$'.number_format($mSales, 0, ',', '.') : '-' ?></div>
                                            <?php if($mComm > 0): ?>
                                            <div class="text-blue-600" style="font-size:10px;">Com: $<?= number_format($mComm, 0, ',', '.') ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <?php endforeach; ?>
                                        <td class="px-3 py-2 text-right font-bold" style="background:rgba(27,54,93,0.05);">
                                            <div>$<?= number_format($v['total_sales'], 0, ',', '.') ?></div>
                                            <div class="text-blue-600" style="font-size:10px;">Com: $<?= number_format($v['total_commission'], 0, ',', '.') ?></div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1B365D; color:white;" class="font-bold text-xs">
                                        <td class="px-3 py-2.5 sticky left-0 z-10" style="background:#1B365D;">TOTAL</td>
                                        <?php foreach($month_names as $mi => $mn):
                                            $mTotalS = 0; $mTotalC = 0;
                                            foreach($vendorSummary as $v) { $mTotalS += $v['months'][$mi]['sales']; $mTotalC += $v['months'][$mi]['commission']; }
                                        ?>
                                        <td class="px-2 py-2.5 text-center">
                                            <div>$<?= number_format($mTotalS, 0, ',', '.') ?></div>
                                            <div style="font-size:10px;">$<?= number_format($mTotalC, 0, ',', '.') ?></div>
                                        </td>
                                        <?php endforeach; ?>
                                        <td class="px-3 py-2.5 text-right">
                                            <div>$<?= number_format($grandSales, 0, ',', '.') ?></div>
                                            <div style="font-size:10px;">$<?= number_format($grandCommission, 0, ',', '.') ?></div>
                                        </td>
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
