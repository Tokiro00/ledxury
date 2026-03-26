<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Gastos por Categoria</title>
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
                            <h2 class="text-xl font-bold text-gray-800">Gastos por Categoria</h2>
                            <p class="text-sm text-gray-500">Distribucion y evolucion mensual de gastos operacionales</p>
                        </div>
                        <form method="get" class="flex flex-wrap items-center gap-2 mt-3 lg:mt-0">
                            <select name="year" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <select name="store" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="">Todas las tiendas</option>
                                <?php foreach($stores as $s): ?>
                                <option value="<?= $s->idStore ?>" <?= $s->idStore == $store ? 'selected' : '' ?>><?= $s->name ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="px-4 py-2 text-sm text-white rounded-lg" style="background:#2E7D91;">Filtrar</button>
                        </form>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Gastos <?= $year ?></p>
                            <p class="text-xl font-bold text-red-600 mt-1">$<?= number_format($totalExpenses, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Categorias Activas</p>
                            <p class="text-xl font-bold text-gray-800 mt-1"><?= count($byCategory) ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Promedio Mensual</p>
                            <p class="text-xl font-bold text-orange-600 mt-1">$<?= number_format($totalExpenses / 12, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Distribution Bar -->
                    <?php if($totalExpenses > 0 && count($byCategory) > 0): ?>
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Distribucion por Categoria</p>
                        <?php
                        $colors = ['bg-blue-500','bg-green-500','bg-yellow-500','bg-red-500','bg-purple-500','bg-indigo-500','bg-pink-500','bg-teal-500','bg-orange-500','bg-gray-500'];
                        $ci = 0;
                        ?>
                        <div class="flex h-8 rounded-lg overflow-hidden">
                            <?php foreach($byCategory as $cat):
                                $pct = $totalExpenses > 0 ? ((float)$cat->total / $totalExpenses) * 100 : 0;
                                $color = $colors[$ci % count($colors)]; $ci++;
                            ?>
                            <div class="<?= $color ?> flex items-center justify-center text-xs text-white font-bold" style="width:<?= max($pct, 0.5) ?>%" title="<?= $cat->category_name ?>: $<?= number_format($cat->total, 0, ',', '.') ?> (<?= number_format($pct, 1) ?>%)">
                                <?php if($pct > 5): ?><?= number_format($pct, 0) ?>%<?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="flex flex-wrap gap-3 mt-2 text-xs">
                            <?php $ci = 0; foreach($byCategory as $cat):
                                $pct = $totalExpenses > 0 ? ((float)$cat->total / $totalExpenses) * 100 : 0;
                                $color = $colors[$ci % count($colors)]; $ci++;
                                $dotColor = str_replace('bg-', 'bg-', $color);
                            ?>
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded <?= $color ?> inline-block"></span> <?= $cat->category_name ?>: $<?= number_format($cat->total, 0, ',', '.') ?> (<?= number_format($pct, 1) ?>%)</span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Monthly Grid Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="px-4 py-2 border-b" style="background:#f8fafc;"><span class="text-sm font-semibold text-gray-700">Desglose Mensual por Categoria</span></div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold sticky left-0 z-10" style="background:#1B365D; min-width:180px;">Categoria</th>
                                        <?php foreach($month_names as $mi => $mn): ?>
                                        <th class="px-2 py-2.5 font-semibold text-right" style="min-width:90px;"><?= $mn ?></th>
                                        <?php endforeach; ?>
                                        <th class="px-3 py-2.5 font-semibold text-right" style="background:#0d2340; min-width:100px;">Total</th>
                                        <th class="px-3 py-2.5 font-semibold text-right" style="background:#0d2340; min-width:60px;">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach($monthlyGrid as $catId => $cat): $i++;
                                        $pct = $totalExpenses > 0 ? ($cat['total'] / $totalExpenses) * 100 : 0;
                                    ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-2 font-semibold sticky left-0 z-10 <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?>">
                                            <?php if($cat['code']): ?><span class="text-gray-400 font-mono"><?= $cat['code'] ?></span> <?php endif; ?>
                                            <?= $cat['name'] ?>
                                        </td>
                                        <?php foreach($month_names as $mi => $mn):
                                            $val = $cat['months'][$mi];
                                        ?>
                                        <td class="px-2 py-2 text-right <?= $val > 0 ? 'text-gray-800' : 'text-gray-300' ?>"><?= $val > 0 ? '$'.number_format($val, 0, ',', '.') : '-' ?></td>
                                        <?php endforeach; ?>
                                        <td class="px-3 py-2 text-right font-bold" style="background:rgba(27,54,93,0.05);">$<?= number_format($cat['total'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right" style="background:rgba(27,54,93,0.05);"><?= number_format($pct, 1) ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1B365D; color:white;" class="font-bold text-xs">
                                        <td class="px-3 py-2.5 sticky left-0 z-10" style="background:#1B365D;">TOTAL</td>
                                        <?php foreach($month_names as $mi => $mn):
                                            $monthTotal = 0;
                                            foreach($monthlyGrid as $cat) { $monthTotal += $cat['months'][$mi]; }
                                        ?>
                                        <td class="px-2 py-2.5 text-right">$<?= number_format($monthTotal, 0, ',', '.') ?></td>
                                        <?php endforeach; ?>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($totalExpenses, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">100%</td>
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
