<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$currentMonth = (int)date('m');
?>
<!DOCTYPE html>
<html lang="en">
    <title>Rendimiento Vendedores</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <!-- Header -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Rendimiento de Vendedores</h2>
                            <p class="text-sm text-gray-500">Ventas vs Meta por vendedor — Analisis anual</p>
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
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Ventas <?= $year ?></p>
                            <p class="text-xl font-bold text-gray-800 mt-1">$<?= number_format($grandTotalSales, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Cobros</p>
                            <p class="text-xl font-bold text-green-600 mt-1">$<?= number_format($grandTotalCollected, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Meta Anual</p>
                            <p class="text-xl font-bold text-blue-600 mt-1">$<?= number_format($grandTotalGoal, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">% Cumplimiento</p>
                            <?php $pctGlobal = $grandTotalGoal > 0 ? ($grandTotalSales / $grandTotalGoal) * 100 : 0; ?>
                            <p class="text-xl font-bold mt-1 <?= $pctGlobal >= 100 ? 'text-green-600' : ($pctGlobal >= 70 ? 'text-yellow-600' : 'text-red-600') ?>"><?= number_format($pctGlobal, 1) ?>%</p>
                        </div>
                    </div>

                    <!-- View Mode Tabs -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="flex items-center border-b px-4 py-2 gap-1">
                            <span class="text-xs text-gray-500 mr-2">Vista:</span>
                            <button class="view-btn px-3 py-1.5 text-xs font-semibold rounded-md transition-colors bg-blue-100 text-blue-800" data-view="ventas">Ventas</button>
                            <button class="view-btn px-3 py-1.5 text-xs font-semibold rounded-md transition-colors text-gray-500 hover:bg-gray-100" data-view="cobros">Cobros</button>
                            <button class="view-btn px-3 py-1.5 text-xs font-semibold rounded-md transition-colors text-gray-500 hover:bg-gray-100" data-view="ventas_cobros">Ventas + Cobros</button>
                            <button class="view-btn px-3 py-1.5 text-xs font-semibold rounded-md transition-colors text-gray-500 hover:bg-gray-100" data-view="meta">Ventas vs Meta</button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-xs" id="perf-table">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold sticky left-0 z-10" style="background:#1B365D; min-width:160px;">Vendedor</th>
                                        <?php foreach($month_names as $mi => $mn): ?>
                                        <th class="px-2 py-2.5 font-semibold text-center <?= $mi == $currentMonth ? 'bg-blue-800' : '' ?>" style="min-width:95px;">
                                            <?= $mn ?><?= $mi == $currentMonth ? ' *' : '' ?>
                                        </th>
                                        <?php endforeach; ?>
                                        <th class="px-3 py-2.5 font-semibold text-center" style="background:#0d2340; min-width:100px;">Total</th>
                                        <th class="px-3 py-2.5 font-semibold text-center col-meta" style="background:#0d2340; min-width:100px;">Meta</th>
                                        <th class="px-3 py-2.5 font-semibold text-center col-meta" style="background:#0d2340; min-width:65px;">%</th>
                                        <th class="px-3 py-2.5 font-semibold text-center col-cobro" style="background:#0d2340; min-width:100px;">Cobros</th>
                                        <th class="px-3 py-2.5 font-semibold text-center col-cobro" style="background:#0d2340; min-width:65px;">% Cobro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach($vendorData as $vid => $vd): $i++; ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-2 font-medium text-gray-800 sticky left-0 z-10 <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover-inherit">
                                            <div class="font-semibold text-xs"><?= $vd['name'] ?></div>
                                            <div class="text-gray-400" style="font-size:10px;"><?= $vd['store'] ?> | <?= $vd['total_invoices'] ?> fact.</div>
                                        </td>
                                        <?php foreach($month_names as $mi => $mn):
                                            $mSales = $vd['months'][$mi]['sales'];
                                            $mCollected = $vd['months'][$mi]['collected'];
                                            $mGoal = $vd['months'][$mi]['goal'];
                                            $mPct = $mGoal > 0 ? ($mSales / $mGoal) * 100 : ($mSales > 0 ? 100 : 0);
                                            $mColor = $mPct >= 100 ? 'text-green-700 bg-green-50' : ($mPct >= 70 ? 'text-yellow-700 bg-yellow-50' : ($mSales > 0 ? 'text-red-700 bg-red-50' : 'text-gray-400'));
                                        ?>
                                        <td class="px-2 py-1.5 text-center <?= $mi == $currentMonth ? 'bg-blue-50' : '' ?>">
                                            <!-- Ventas -->
                                            <div class="cell-ventas <?= $mSales > 0 ? 'font-medium text-gray-800' : 'text-gray-300' ?>">$<?= number_format($mSales, 0, ',', '.') ?></div>
                                            <!-- Cobros -->
                                            <div class="cell-cobros hidden <?= $mCollected > 0 ? 'font-medium text-green-700' : 'text-gray-300' ?>">$<?= number_format($mCollected, 0, ',', '.') ?></div>
                                            <!-- % Cobro vs Venta -->
                                            <?php $mCobroPct = $mSales > 0 ? ($mCollected / $mSales) * 100 : 0; ?>
                                            <div class="cell-cobros hidden text-xs mt-0.5">
                                                <?php if($mSales > 0): ?>
                                                <span class="inline-block px-1 rounded <?= $mCobroPct >= 80 ? 'text-green-700 bg-green-50' : ($mCobroPct >= 50 ? 'text-yellow-700 bg-yellow-50' : 'text-red-700 bg-red-50') ?>"><?= number_format($mCobroPct, 0) ?>%</span>
                                                <?php endif; ?>
                                            </div>
                                            <!-- Meta % -->
                                            <?php if($mGoal > 0): ?>
                                            <div class="cell-meta text-xs mt-0.5">
                                                <span class="inline-block px-1 rounded <?= $mColor ?>"><?= number_format($mPct, 0) ?>%</span>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <?php endforeach; ?>

                                        <?php $totalPct = $vd['total_goal'] > 0 ? ($vd['total_sales'] / $vd['total_goal']) * 100 : 0; ?>
                                        <?php $cobroPct = $vd['total_sales'] > 0 ? ($vd['total_collected'] / $vd['total_sales']) * 100 : 0; ?>
                                        <td class="px-3 py-2 text-center font-bold text-gray-800" style="background:rgba(27,54,93,0.05);">
                                            <span class="cell-ventas">$<?= number_format($vd['total_sales'], 0, ',', '.') ?></span>
                                            <span class="cell-cobros hidden text-green-700">$<?= number_format($vd['total_collected'], 0, ',', '.') ?></span>
                                        </td>
                                        <td class="px-3 py-2 text-center font-medium text-blue-700 col-meta" style="background:rgba(27,54,93,0.05);">$<?= number_format($vd['total_goal'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-center col-meta" style="background:rgba(27,54,93,0.05);">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold
                                                <?= $totalPct >= 100 ? 'bg-green-100 text-green-800' : ($totalPct >= 70 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                                <?= number_format($totalPct, 1) ?>%
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-center font-bold text-green-700 col-cobro" style="background:rgba(27,54,93,0.05);">$<?= number_format($vd['total_collected'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-center col-cobro" style="background:rgba(27,54,93,0.05);">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold
                                                <?= $cobroPct >= 80 ? 'bg-green-100 text-green-800' : ($cobroPct >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                                <?= number_format($cobroPct, 1) ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1B365D; color:white;" class="font-bold text-xs">
                                        <td class="px-3 py-2.5 sticky left-0 z-10" style="background:#1B365D;">TOTALES</td>
                                        <?php foreach($month_names as $mi => $mn):
                                            $mTotalS = 0; $mTotalC = 0;
                                            foreach($vendorData as $vd) { $mTotalS += $vd['months'][$mi]['sales']; $mTotalC += $vd['months'][$mi]['collected']; }
                                        ?>
                                        <td class="px-2 py-2.5 text-center">
                                            <span class="cell-ventas">$<?= number_format($mTotalS, 0, ',', '.') ?></span>
                                            <span class="cell-cobros hidden">$<?= number_format($mTotalC, 0, ',', '.') ?></span>
                                        </td>
                                        <?php endforeach; ?>
                                        <td class="px-3 py-2.5 text-center">
                                            <span class="cell-ventas">$<?= number_format($grandTotalSales, 0, ',', '.') ?></span>
                                            <span class="cell-cobros hidden">$<?= number_format($grandTotalCollected, 0, ',', '.') ?></span>
                                        </td>
                                        <td class="px-3 py-2.5 text-center col-meta">$<?= number_format($grandTotalGoal, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-center col-meta"><?= number_format($pctGlobal, 1) ?>%</td>
                                        <td class="px-3 py-2.5 text-center col-cobro">$<?= number_format($grandTotalCollected, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-center col-cobro"><?= $grandTotalSales > 0 ? number_format(($grandTotalCollected/$grandTotalSales)*100, 1) : 0 ?>%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <p class="text-xs text-gray-400 mt-2">* Mes actual. Ventas/Meta = % cumplimiento. Cobro = Pagado/Facturado.</p>

                </div>
            </main>
        </div>
    </div>

<script>
$(document).on('click', '.view-btn', function() {
    var view = $(this).data('view');

    // Active tab style
    $('.view-btn').removeClass('bg-blue-100 text-blue-800').addClass('text-gray-500');
    $(this).addClass('bg-blue-100 text-blue-800').removeClass('text-gray-500');

    // Reset visibility
    $('.cell-ventas, .cell-cobros, .cell-meta, .col-meta, .col-cobro').hide();

    if (view === 'ventas') {
        $('.cell-ventas').show();
        $('.col-meta').hide();
        $('.col-cobro').hide();
    } else if (view === 'cobros') {
        $('.cell-cobros').show().css('display', 'block');
        $('.col-cobro').show();
        $('.col-meta').hide();
    } else if (view === 'ventas_cobros') {
        $('.cell-ventas').show();
        $('.cell-cobros').show().css('display', 'block');
        $('.col-cobro').show();
        $('.col-meta').hide();
    } else if (view === 'meta') {
        $('.cell-ventas').show();
        $('.cell-meta').show();
        $('.col-meta').show();
        $('.col-cobro').hide();
    }
});

// Default view
$(document).ready(function() {
    $('.view-btn[data-view="ventas"]').click();
});
</script>

<style>
.hover-inherit:hover { background: inherit !important; }
</style>

</body>
</html>
