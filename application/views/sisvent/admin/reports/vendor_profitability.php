<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Rentabilidad por Vendedor</title>
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
                            <h2 class="text-xl font-bold text-gray-800">Rentabilidad por Vendedor</h2>
                            <p class="text-sm text-gray-500">Margen bruto y comisiones por vendedor</p>
                        </div>
                        <form method="get" class="flex flex-wrap items-center gap-2 mt-3 lg:mt-0">
                            <select name="year" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
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
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Ingresos Totales</p>
                            <p class="text-lg font-bold text-gray-800 mt-1">$<?= number_format($totalRevenue, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Costo Mercancia</p>
                            <p class="text-lg font-bold text-red-600 mt-1">$<?= number_format($totalCogs, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Margen Bruto (<?= number_format($avgMarginPct, 1) ?>%)</p>
                            <p class="text-lg font-bold text-green-600 mt-1">$<?= number_format($totalMargin, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Comisiones</p>
                            <p class="text-lg font-bold text-blue-600 mt-1">$<?= number_format($totalCommission, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold">#</th>
                                        <th class="px-3 py-2.5 font-semibold">Vendedor</th>
                                        <th class="px-3 py-2.5 font-semibold">Tienda</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Facturas</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Ingresos</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Costo Mercancia</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Margen Bruto</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Margen %</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Comision %</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Comision $</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Contribucion Neta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach($vendors as $v): $i++;
                                        $mPct = (float)$v->margin_pct;
                                        $mColor = $mPct >= 25 ? 'text-green-700 bg-green-50' : ($mPct >= 15 ? 'text-yellow-700 bg-yellow-50' : 'text-red-700 bg-red-50');
                                        $netContribution = (float)$v->gross_margin - (float)$v->commission_earned;
                                    ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-2 text-gray-400"><?= $i ?></td>
                                        <td class="px-3 py-2 font-semibold"><?= $v->vendor_name ?></td>
                                        <td class="px-3 py-2 text-gray-500"><?= $v->store_name ?></td>
                                        <td class="px-3 py-2 text-right"><?= number_format($v->invoice_count, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right font-medium">$<?= number_format($v->revenue, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right text-red-600">$<?= number_format($v->cogs, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right font-medium text-green-700">$<?= number_format($v->gross_margin, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold <?= $mColor ?>"><?= number_format($mPct, 1) ?>%</span></td>
                                        <td class="px-3 py-2 text-right"><?= number_format($v->commission_perc, 1) ?>%</td>
                                        <td class="px-3 py-2 text-right text-blue-700 font-medium">$<?= number_format($v->commission_earned, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right font-bold <?= $netContribution >= 0 ? 'text-green-700' : 'text-red-700' ?>">$<?= number_format($netContribution, 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1B365D; color:white;" class="font-bold text-xs">
                                        <td class="px-3 py-2.5" colspan="4">TOTALES</td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($totalRevenue, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($totalCogs, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($totalMargin, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right"><?= number_format($avgMarginPct, 1) ?>%</td>
                                        <td class="px-3 py-2.5 text-right">-</td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($totalCommission, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($totalMargin - $totalCommission, 0, ',', '.') ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
<?php $this->load->view("sisvent/layouts/footer"); ?>
</body>
</html>
