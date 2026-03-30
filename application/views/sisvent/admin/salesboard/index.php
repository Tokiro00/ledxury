<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$pctTotal = $totalMeta > 0 ? round(($totalVentas / $totalMeta) * 100, 1) : 0;

function fmtMoney($v) {
    if ($v >= 1000000000) return '$' . number_format($v/1000000000, 1, '.', '') . 'B';
    if ($v >= 1000000) return '$' . number_format($v/1000000, 1, '.', '') . 'M';
    return '$' . number_format($v, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="es">
    <title>Panel de Vendedores</title>
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
                            <h2 class="text-2xl font-black text-gray-800 tracking-tight">Panel de Vendedores</h2>
                            <p class="text-xs text-gray-400 uppercase tracking-widest"><?= $monthName ?> <?= $year ?> - Dia <?= $dayOfMonth ?> de <?= $daysInMonth ?> (<?= $workDaysLeft ?> dias habiles restantes)</p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/admin/salesboard/inactivos" class="mt-2 lg:mt-0 px-4 py-2 text-xs font-bold text-white rounded-lg bg-red-500 hover:bg-red-600">Clientes Inactivos</a>
                    </div>

                    <!-- KPIs -->
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Ventas del mes</p>
                            <p class="text-2xl font-black" style="color:#1B365D"><?= fmtMoney($totalVentas) ?></p>
                            <p class="text-xs text-gray-400">de <?= fmtMoney($totalMeta) ?> meta</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 p-3 <?= $pctTotal >= 80 ? 'border-green-500' : ($pctTotal >= 50 ? 'border-yellow-500' : 'border-red-500') ?>">
                            <p class="text-xs text-gray-400 uppercase font-bold">Cumplimiento</p>
                            <p class="text-2xl font-black <?= $pctTotal >= 80 ? 'text-green-600' : ($pctTotal >= 50 ? 'text-yellow-600' : 'text-red-600') ?>"><?= $pctTotal ?>%</p>
                            <p class="text-xs text-gray-400">equipo completo</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-teal-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Ventas hoy</p>
                            <p class="text-2xl font-black text-teal-600"><?= fmtMoney($totalHoy) ?></p>
                            <p class="text-xs text-gray-400"><?= date('d/m/Y') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Cumplieron meta</p>
                            <p class="text-2xl font-black text-green-600"><?= $cumplieron ?> <span class="text-sm text-gray-400">/ <?= $totalVendors ?></span></p>
                            <p class="text-xs text-gray-400">vendedores</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-purple-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Ticket promedio</p>
                            <p class="text-2xl font-black text-purple-600"><?php $totalInv = array_sum(array_column(array_map(function($v){return (array)$v;}, $vendors), 'invoices')); echo $totalInv > 0 ? fmtMoney($totalVentas / $totalInv) : '$0'; ?></p>
                            <p class="text-xs text-gray-400">por factura</p>
                        </div>
                    </div>

                    <!-- Tabla de vendedores -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold">#</th>
                                        <th class="px-3 py-2.5 font-semibold">Vendedor</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Ventas Mes</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Meta</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Cumplimiento</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Hoy</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Meta Diaria</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Presup.</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Facturas</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Conversion</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Actividad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach($vendors as $v): $i++;
                                        $semaforo = $v->pctMeta >= 100 ? 'bg-green-500' : ($v->pctMeta >= 60 ? 'bg-yellow-400' : 'bg-red-500');
                                        $semaforoText = $v->pctMeta >= 100 ? 'text-green-600' : ($v->pctMeta >= 60 ? 'text-yellow-600' : 'text-red-600');
                                        $actColor = $v->diasSinActividad == 0 ? 'text-green-600' : ($v->diasSinActividad <= 1 ? 'text-yellow-600' : 'text-red-600');
                                    ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-2 font-bold text-gray-400"><?= $i ?></td>
                                        <td class="px-3 py-2 font-medium text-gray-800"><?= $v->name ?></td>
                                        <td class="px-3 py-2 text-right font-bold" style="color:#1B365D">$<?= number_format($v->ventasMes, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right text-gray-500">$<?= number_format($v->meta, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2">
                                            <div class="flex items-center gap-2">
                                                <div class="w-full bg-gray-200 rounded-full h-3">
                                                    <div class="h-3 rounded-full <?= $semaforo ?>" style="width:<?= min($v->pctMeta, 100) ?>%"></div>
                                                </div>
                                                <span class="font-bold <?= $semaforoText ?> whitespace-nowrap"><?= $v->pctMeta ?>%</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-right font-medium <?= $v->ventasHoy > 0 ? 'text-green-600' : 'text-gray-300' ?>">$<?= number_format($v->ventasHoy, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right text-gray-500">$<?= number_format($v->metaDiaria, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-center"><?= $v->budgets ?></td>
                                        <td class="px-3 py-2 text-center"><?= $v->invoices ?></td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="font-bold <?= $v->conversion >= 70 ? 'text-green-600' : ($v->conversion >= 40 ? 'text-yellow-600' : 'text-red-600') ?>"><?= $v->conversion ?>%</span>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <?php if($v->diasSinActividad == 0): ?>
                                                <span class="text-green-600 font-bold">Hoy</span>
                                            <?php elseif($v->diasSinActividad == 1): ?>
                                                <span class="text-yellow-600">Ayer</span>
                                            <?php else: ?>
                                                <span class="text-red-600 font-bold"><?= $v->diasSinActividad ?>d</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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
