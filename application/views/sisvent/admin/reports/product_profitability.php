<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Rentabilidad por Producto</title>
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
                            <h2 class="text-xl font-bold text-gray-800">Rentabilidad por Producto</h2>
                            <p class="text-sm text-gray-500">Ingresos, costos y margen por producto</p>
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
                            <select name="family" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="">Todas las familias</option>
                                <?php foreach($families as $f): ?>
                                <option value="<?= $f->idFamily ?>" <?= $f->idFamily == $family ? 'selected' : '' ?>><?= $f->name ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="sort" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="revenue" <?= $sort == 'revenue' ? 'selected' : '' ?>>Ordenar por Ingreso</option>
                                <option value="margin" <?= $sort == 'margin' ? 'selected' : '' ?>>Ordenar por Margen</option>
                            </select>
                            <button type="submit" class="px-4 py-2 text-sm text-white rounded-lg" style="background:#2E7D91;">Filtrar</button>
                        </form>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Ingresos Totales</p>
                            <p class="text-lg font-bold text-gray-800 mt-1">$<?= number_format($totalRevenue, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Costo Total</p>
                            <p class="text-lg font-bold text-red-600 mt-1">$<?= number_format($totalCost, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Margen Total</p>
                            <p class="text-lg font-bold text-green-600 mt-1">$<?= number_format($totalMargin, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Margen Promedio</p>
                            <p class="text-lg font-bold <?= $avgMarginPct >= 20 ? 'text-green-600' : 'text-yellow-600' ?> mt-1"><?= number_format($avgMarginPct, 1) ?>%</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Productos / Uds</p>
                            <p class="text-lg font-bold text-blue-600 mt-1"><?= count($products) ?> / <?= number_format($totalQty, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold">#</th>
                                        <th class="px-3 py-2.5 font-semibold">Codigo</th>
                                        <th class="px-3 py-2.5 font-semibold">Descripcion</th>
                                        <th class="px-3 py-2.5 font-semibold">Familia</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Cant. Vendida</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Ingresos</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Costo</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Margen</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Margen %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach($products as $p): $i++;
                                        $mPct = (float)$p->margin_pct;
                                        $mColor = $mPct >= 30 ? 'text-green-700 bg-green-50' : ($mPct >= 15 ? 'text-yellow-700 bg-yellow-50' : 'text-red-700 bg-red-50');
                                    ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-1.5 text-gray-400"><?= $i ?></td>
                                        <td class="px-3 py-1.5 font-mono font-medium"><?= $p->idProduct ?></td>
                                        <td class="px-3 py-1.5"><?= $p->description ?></td>
                                        <td class="px-3 py-1.5 text-gray-500"><?= $p->family_name ?></td>
                                        <td class="px-3 py-1.5 text-right"><?= number_format($p->qty_sold, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right font-medium">$<?= number_format($p->revenue, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right text-red-600">$<?= number_format($p->total_cost, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right font-medium text-green-700">$<?= number_format($p->margin, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold <?= $mColor ?>"><?= number_format($mPct, 1) ?>%</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1B365D; color:white;" class="font-bold text-xs">
                                        <td class="px-3 py-2.5" colspan="4">TOTALES (<?= count($products) ?> productos)</td>
                                        <td class="px-3 py-2.5 text-right"><?= number_format($totalQty, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($totalRevenue, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($totalCost, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($totalMargin, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right"><?= number_format($avgMarginPct, 1) ?>%</td>
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
