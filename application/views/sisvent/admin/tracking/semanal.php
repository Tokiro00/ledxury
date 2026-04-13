<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$months = array(1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre');
?>
<!DOCTYPE html>
<html lang="en">
    <title>Desempeno - <?= $monthName ?> <?= $year ?></title>
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
                            <h2 class="text-xl font-bold text-gray-800">Desempeno Comercial — <?= $monthName ?> <?= $year ?></h2>
                            <p class="text-sm text-gray-500">Ventas vs Meta | Dia <?= $daysPassed ?> de <?= $daysInMonth ?> del mes</p>
                        </div>
                        <!-- Filtros -->
                        <div class="flex flex-wrap items-center gap-2 mt-3 lg:mt-0">
                            <select id="sel-year" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
                                <?php for($y = 2024; $y <= 2027; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <select id="sel-month" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
                                <?php foreach($months as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $k == $month ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="sel-store" class="text-sm border border-gray-300 rounded-lg px-3 py-2">
                                <option value="-1" <?= $storeFilter == '-1' ? 'selected' : '' ?>>Todas las bodegas</option>
                                <?php foreach($stores as $s): ?>
                                <option value="<?= $s->idStore ?>" <?= $storeFilter == $s->idStore ? 'selected' : '' ?>><?= $s->name ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button id="btn-filter" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#2E7D91;">Filtrar</button>
                        </div>
                    </div>

                    <!-- KPI Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">Ventas Mes</p>
                            <p class="text-lg font-bold mt-1" style="color:#1B365D;">$<?= number_format($totalVentas, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">Cobros Mes</p>
                            <p class="text-lg font-bold text-green-600 mt-1">$<?= number_format($totalCobros, 0, ',', '.') ?></p>
                            <p class="text-xs <?= $pctCobro >= 80 ? 'text-green-600' : ($pctCobro >= 50 ? 'text-yellow-600' : 'text-red-600') ?>"><?= $pctCobro ?>% cobrado</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">Meta Mes</p>
                            <p class="text-lg font-bold text-gray-600 mt-1">$<?= number_format($totalMeta, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">% Cumplimiento</p>
                            <p class="text-lg font-bold mt-1 <?= $pctMeta >= 100 ? 'text-green-600' : ($pctMeta >= 60 ? 'text-yellow-600' : 'text-red-600') ?>"><?= $pctMeta ?>%</p>
                            <!-- Progress bar -->
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                <div class="h-2 rounded-full <?= $pctMeta >= 100 ? 'bg-green-500' : ($pctMeta >= 60 ? 'bg-yellow-500' : 'bg-red-500') ?>" style="width:<?= min($pctMeta, 100) ?>%"></div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">Proyeccion</p>
                            <p class="text-lg font-bold text-blue-600 mt-1">$<?= number_format($proyeccion, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">Cartera</p>
                            <p class="text-lg font-bold text-red-600 mt-1">$<?= number_format($cartera, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Vendor Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="px-4 py-3 border-b flex items-center justify-between">
                            <h3 class="font-semibold text-gray-700">Ranking de Vendedores — <?= $monthName ?> <?= $year ?></h3>
                            <span class="text-xs text-gray-400"><?= count($vendorRows) ?> vendedores activos</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full" style="font-size:13px;">
                                <thead>
                                    <tr style="background:#1B365D; color:white;">
                                        <th class="px-3 py-3 text-left font-semibold">#</th>
                                        <th class="px-3 py-3 text-left font-semibold" style="min-width:180px;">Vendedor</th>
                                        <th class="px-3 py-3 text-right font-semibold">Ventas</th>
                                        <th class="px-3 py-3 text-right font-semibold">Cobros</th>
                                        <th class="px-3 py-3 text-right font-semibold">% Cobro</th>
                                        <th class="px-3 py-3 text-right font-semibold">Meta</th>
                                        <th class="px-3 py-3 text-right font-semibold">% Meta</th>
                                        <th class="px-3 py-3 text-center font-semibold" style="min-width:120px;">Progreso</th>
                                        <th class="px-3 py-3 text-right font-semibold">Proyeccion</th>
                                        <th class="px-3 py-3 text-center font-semibold">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $n = 0; $tVentas = 0; $tCobros = 0; $tMeta = 0; $tProy = 0; ?>
                                    <?php foreach($vendorRows as $vr): $n++; $tVentas += $vr['ventas']; $tCobros += $vr['cobros']; $tMeta += $vr['meta']; $tProy += $vr['proyeccion']; ?>
                                    <tr class="border-t hover:bg-blue-50 <?= $n % 2 == 0 ? 'bg-gray-50' : '' ?>">
                                        <td class="px-3 py-2 text-gray-400 font-bold"><?= $n ?></td>
                                        <td class="px-3 py-2 font-medium text-gray-800"><?= $vr['name'] ?></td>
                                        <td class="px-3 py-2 text-right font-semibold" style="color:#1B365D;">$<?= number_format($vr['ventas'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right text-green-700">$<?= number_format($vr['cobros'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right">
                                            <span class="px-1.5 py-0.5 rounded text-xs font-semibold <?= $vr['pctCobro'] >= 80 ? 'bg-green-100 text-green-700' : ($vr['pctCobro'] >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?>"><?= $vr['pctCobro'] ?>%</span>
                                        </td>
                                        <td class="px-3 py-2 text-right text-gray-600">$<?= number_format($vr['meta'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right font-bold <?= $vr['pctMeta'] >= 100 ? 'text-green-600' : ($vr['pctMeta'] >= 60 ? 'text-yellow-600' : 'text-red-600') ?>"><?= $vr['pctMeta'] ?>%</td>
                                        <td class="px-3 py-2">
                                            <div class="w-full bg-gray-200 rounded-full h-3">
                                                <div class="h-3 rounded-full transition-all <?= $vr['semaforo'] == 'green' ? 'bg-green-500' : ($vr['semaforo'] == 'yellow' ? 'bg-yellow-400' : 'bg-red-500') ?>" style="width:<?= min($vr['pctMeta'], 100) ?>%"></div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-right text-blue-600">$<?= number_format($vr['proyeccion'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-center">
                                            <?php if($vr['semaforo'] == 'green'): ?>
                                            <span class="inline-block w-4 h-4 rounded-full bg-green-500"></span>
                                            <?php elseif($vr['semaforo'] == 'yellow'): ?>
                                            <span class="inline-block w-4 h-4 rounded-full bg-yellow-400"></span>
                                            <?php else: ?>
                                            <span class="inline-block w-4 h-4 rounded-full bg-red-500"></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <?php $tPctCobro = ($tVentas > 0) ? round(($tCobros / $tVentas) * 100, 1) : 0; ?>
                                    <?php $tPctMeta = ($tMeta > 0) ? round(($tVentas / $tMeta) * 100, 1) : 0; ?>
                                    <tr style="background:#1B365D; color:white;" class="font-bold">
                                        <td class="px-3 py-3" colspan="2">TOTALES (<?= count($vendorRows) ?> vendedores)</td>
                                        <td class="px-3 py-3 text-right">$<?= number_format($tVentas, 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right">$<?= number_format($tCobros, 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right"><?= $tPctCobro ?>%</td>
                                        <td class="px-3 py-3 text-right">$<?= number_format($tMeta, 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right"><?= $tPctMeta ?>%</td>
                                        <td class="px-3 py-3">
                                            <div class="w-full bg-gray-600 rounded-full h-3">
                                                <div class="h-3 rounded-full <?= $tPctMeta >= 100 ? 'bg-green-400' : ($tPctMeta >= 60 ? 'bg-yellow-400' : 'bg-red-400') ?>" style="width:<?= min($tPctMeta, 100) ?>%"></div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-right">$<?= number_format($tProy, 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-center">
                                            <span class="inline-block w-4 h-4 rounded-full <?= $tPctMeta >= 100 ? 'bg-green-400' : ($tPctMeta >= 60 ? 'bg-yellow-400' : 'bg-red-400') ?>"></span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <p class="text-xs text-gray-400 mt-2">Datos en tiempo real. Proyeccion = (ventas acumuladas / dias transcurridos) x dias del mes. Solo vendedores con actividad o meta asignada.</p>

                </div>
            </main>
            <?php $this->load->view('sisvent/layouts/footer'); ?>
        </div>
    </div>

<script>
$(document).on('click', '#btn-filter', function(){
    var y = $('#sel-year').val();
    var m = $('#sel-month').val();
    var s = $('#sel-store').val();
    window.location.href = '<?= base_url() ?>sisvent/admin/tracking/semanal?year=' + y + '&month=' + m + '&store=' + s;
});
</script>

</body>
</html>
