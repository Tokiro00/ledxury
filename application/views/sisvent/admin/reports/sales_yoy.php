<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Comparativo Ventas Ano vs Ano</title>
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
                            <h2 class="text-xl font-bold text-gray-800">Comparativo Ventas <?= $year ?> vs <?= $yearPrev ?></h2>
                            <p class="text-sm text-gray-500">Evolucion mensual y crecimiento interanual</p>
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
                            <select name="vendor" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="">Todos los vendedores</option>
                                <?php foreach($vendorsList as $v): ?>
                                <option value="<?= $v->idUser ?>" <?= $v->idUser == $vendor ? 'selected' : '' ?>><?= $v->name ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="px-4 py-2 text-sm text-white rounded-lg" style="background:#2E7D91;">Filtrar</button>
                        </form>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">YTD <?= $year ?></p>
                            <p class="text-lg font-bold text-gray-800 mt-1">$<?= number_format($ytdCurrent, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">YTD <?= $yearPrev ?></p>
                            <p class="text-lg font-bold text-gray-500 mt-1">$<?= number_format($ytdPrevious, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Diferencia</p>
                            <?php $diff = $ytdCurrent - $ytdPrevious; ?>
                            <p class="text-lg font-bold <?= $diff >= 0 ? 'text-green-600' : 'text-red-600' ?> mt-1"><?= $diff >= 0 ? '+' : '' ?>$<?= number_format($diff, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Crecimiento %</p>
                            <p class="text-lg font-bold <?= $ytdGrowth >= 0 ? 'text-green-600' : 'text-red-600' ?> mt-1"><?= $ytdGrowth >= 0 ? '+' : '' ?><?= number_format($ytdGrowth, 1) ?>%</p>
                        </div>
                    </div>

                    <!-- Monthly Comparison Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold" style="min-width:100px;">Mes</th>
                                        <th class="px-3 py-2.5 font-semibold text-right"><?= $year ?></th>
                                        <th class="px-3 py-2.5 font-semibold text-right"># Fact. <?= $year ?></th>
                                        <th class="px-3 py-2.5 font-semibold text-right"><?= $yearPrev ?></th>
                                        <th class="px-3 py-2.5 font-semibold text-right"># Fact. <?= $yearPrev ?></th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Diferencia</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">% Cambio</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Acum. <?= $year ?></th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Acum. <?= $yearPrev ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $cumCurrent = 0; $cumPrevious = 0;
                                    foreach($monthly as $m):
                                        $curr = (float)$m->current_total;
                                        $prev = (float)$m->previous_total;
                                        $diff = $curr - $prev;
                                        $pctChange = $prev > 0 ? (($curr - $prev) / $prev) * 100 : ($curr > 0 ? 100 : 0);
                                        $cumCurrent += $curr;
                                        $cumPrevious += $prev;
                                        $mi = (int)$m->month_num;
                                        $isCurrentMonth = ($mi == (int)date('m') && $year == date('Y'));
                                    ?>
                                    <tr class="border-t <?= $isCurrentMonth ? 'bg-blue-50' : ($mi % 2 == 0 ? 'bg-gray-50' : 'bg-white') ?> hover:bg-blue-50">
                                        <td class="px-3 py-2 font-semibold"><?= $month_names[$mi] ?> <?= $isCurrentMonth ? '*' : '' ?></td>
                                        <td class="px-3 py-2 text-right font-bold">$<?= number_format($curr, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right text-gray-500"><?= number_format($m->current_count, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right text-gray-600">$<?= number_format($prev, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right text-gray-500"><?= number_format($m->previous_count, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right font-medium <?= $diff >= 0 ? 'text-green-700' : 'text-red-700' ?>"><?= $diff >= 0 ? '+' : '' ?>$<?= number_format($diff, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right">
                                            <?php if($curr > 0 || $prev > 0): ?>
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold <?= $pctChange >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>"><?= $pctChange >= 0 ? '+' : '' ?><?= number_format($pctChange, 1) ?>%</span>
                                            <?php else: ?>
                                            <span class="text-gray-300">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-right font-medium">$<?= number_format($cumCurrent, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right text-gray-500">$<?= number_format($cumPrevious, 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1B365D; color:white;" class="font-bold text-xs">
                                        <td class="px-3 py-2.5">TOTAL</td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($ytdCurrent, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right"><?php $tc = 0; foreach($monthly as $m) $tc += (int)$m->current_count; echo number_format($tc, 0, ',', '.'); ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($ytdPrevious, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right"><?php $tp = 0; foreach($monthly as $m) $tp += (int)$m->previous_count; echo number_format($tp, 0, ',', '.'); ?></td>
                                        <td class="px-3 py-2.5 text-right"><?= ($ytdCurrent - $ytdPrevious) >= 0 ? '+' : '' ?>$<?= number_format($ytdCurrent - $ytdPrevious, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right"><?= $ytdGrowth >= 0 ? '+' : '' ?><?= number_format($ytdGrowth, 1) ?>%</td>
                                        <td class="px-3 py-2.5 text-right">-</td>
                                        <td class="px-3 py-2.5 text-right">-</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">* Mes actual (datos parciales).</p>

                </div>
            </main>
        </div>
    </div>
<?php $this->load->view("sisvent/layouts/footer"); ?>
</body>
</html>
