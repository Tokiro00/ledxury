<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Flujo de Efectivo</title>
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
                            <h2 class="text-xl font-bold text-gray-800">Flujo de Efectivo</h2>
                            <p class="text-sm text-gray-500">Ingresos y egresos operacionales mensuales</p>
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
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Ingresos</p>
                            <p class="text-lg font-bold text-green-600 mt-1">$<?= number_format($totalIn, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Egresos</p>
                            <p class="text-lg font-bold text-red-600 mt-1">$<?= number_format($totalOut, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Flujo Neto</p>
                            <p class="text-lg font-bold <?= $netFlow >= 0 ? 'text-green-600' : 'text-red-600' ?> mt-1">$<?= number_format($netFlow, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Ratio Ingr/Egr</p>
                            <?php $ratio = $totalOut > 0 ? $totalIn / $totalOut : 0; ?>
                            <p class="text-lg font-bold <?= $ratio >= 1 ? 'text-green-600' : 'text-red-600' ?> mt-1"><?= number_format($ratio, 2) ?>x</p>
                        </div>
                    </div>

                    <!-- Monthly Grid Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold" style="min-width:180px;">Concepto</th>
                                        <?php foreach($month_names as $mi => $mn): ?>
                                        <th class="px-2 py-2.5 font-semibold text-right" style="min-width:100px;"><?= $mn ?></th>
                                        <?php endforeach; ?>
                                        <th class="px-3 py-2.5 font-semibold text-right" style="background:#0d2340; min-width:110px;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Ingresos -->
                                    <tr class="border-t bg-green-50">
                                        <td class="px-3 py-2 font-semibold text-green-800">Ingresos Operacionales</td>
                                        <?php $rowTotalIn = 0; foreach($monthly as $m): $rowTotalIn += (float)$m->ingresos; ?>
                                        <td class="px-2 py-2 text-right text-green-700 font-medium">$<?= number_format($m->ingresos, 0, ',', '.') ?></td>
                                        <?php endforeach; ?>
                                        <td class="px-3 py-2 text-right font-bold text-green-800" style="background:rgba(16,185,129,0.1);">$<?= number_format($rowTotalIn, 0, ',', '.') ?></td>
                                    </tr>
                                    <!-- Egresos -->
                                    <tr class="border-t bg-red-50">
                                        <td class="px-3 py-2 font-semibold text-red-800">Egresos Operacionales</td>
                                        <?php $rowTotalOut = 0; foreach($monthly as $m): $rowTotalOut += (float)$m->egresos; ?>
                                        <td class="px-2 py-2 text-right text-red-700 font-medium">$<?= number_format($m->egresos, 0, ',', '.') ?></td>
                                        <?php endforeach; ?>
                                        <td class="px-3 py-2 text-right font-bold text-red-800" style="background:rgba(239,68,68,0.1);">$<?= number_format($rowTotalOut, 0, ',', '.') ?></td>
                                    </tr>
                                    <!-- Flujo Neto -->
                                    <tr class="border-t-2 border-gray-400">
                                        <td class="px-3 py-2 font-bold text-gray-800">Flujo Neto Mensual</td>
                                        <?php $rowTotalNet = 0; foreach($monthly as $m):
                                            $net = (float)$m->ingresos - (float)$m->egresos;
                                            $rowTotalNet += $net;
                                        ?>
                                        <td class="px-2 py-2 text-right font-bold <?= $net >= 0 ? 'text-green-700' : 'text-red-700' ?>">$<?= number_format($net, 0, ',', '.') ?></td>
                                        <?php endforeach; ?>
                                        <td class="px-3 py-2 text-right font-bold <?= $rowTotalNet >= 0 ? 'text-green-800' : 'text-red-800' ?>" style="background:rgba(27,54,93,0.05);">$<?= number_format($rowTotalNet, 0, ',', '.') ?></td>
                                    </tr>
                                    <!-- Acumulado -->
                                    <tr class="border-t bg-gray-100">
                                        <td class="px-3 py-2 font-semibold text-gray-600">Acumulado</td>
                                        <?php $cumulative = 0; foreach($monthly as $m):
                                            $net = (float)$m->ingresos - (float)$m->egresos;
                                            $cumulative += $net;
                                        ?>
                                        <td class="px-2 py-2 text-right font-medium <?= $cumulative >= 0 ? 'text-green-700' : 'text-red-700' ?>">$<?= number_format($cumulative, 0, ',', '.') ?></td>
                                        <?php endforeach; ?>
                                        <td class="px-3 py-2 text-right font-bold <?= $cumulative >= 0 ? 'text-green-800' : 'text-red-800' ?>" style="background:rgba(27,54,93,0.05);">$<?= number_format($cumulative, 0, ',', '.') ?></td>
                                    </tr>
                                </tbody>
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
