<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Inventario Valorizado</title>
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
                            <h2 class="text-xl font-bold text-gray-800">Inventario Valorizado por Bodega</h2>
                            <p class="text-sm text-gray-500">Valor del inventario actual (stock x costo)</p>
                        </div>
                        <form method="get" class="flex flex-wrap items-center gap-2 mt-3 lg:mt-0">
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
                            <button type="submit" class="px-4 py-2 text-sm text-white rounded-lg" style="background:#2E7D91;">Filtrar</button>
                        </form>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Productos</p>
                            <p class="text-lg font-bold text-gray-800 mt-1"><?= number_format($grandProducts, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Unidades</p>
                            <p class="text-lg font-bold text-blue-600 mt-1"><?= number_format($grandUnits, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Valor Total del Inventario</p>
                            <p class="text-xl font-bold text-green-600 mt-1">$<?= number_format($grandValue, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Summary by Store -->
                    <?php if(count($summary) > 1): ?>
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-4">
                        <div class="px-4 py-2 border-b" style="background:#f8fafc;"><span class="text-sm font-semibold text-gray-700">Resumen por Bodega</span></div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold">Bodega</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Productos</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Unidades</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Valor</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">% del Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach($summary as $s): $i++;
                                        $pct = $grandValue > 0 ? ((float)$s->total_value / $grandValue) * 100 : 0;
                                    ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?>">
                                        <td class="px-3 py-2 font-semibold"><?= $s->store_name ?></td>
                                        <td class="px-3 py-2 text-right"><?= number_format($s->product_count, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right"><?= number_format($s->total_units, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right font-bold">$<?= number_format($s->total_value, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <div class="w-16 bg-gray-200 rounded-full h-2"><div class="h-2 rounded-full" style="width:<?= min($pct, 100) ?>%; background:#2E7D91;"></div></div>
                                                <span class="font-medium"><?= number_format($pct, 1) ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Detail Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="px-4 py-2 border-b" style="background:#f8fafc;"><span class="text-sm font-semibold text-gray-700">Detalle de Productos (<?= count($details) ?> items)</span></div>
                        <div class="overflow-x-auto" style="max-height:600px; overflow-y:auto;">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white; position:sticky; top:0; z-index:10;">
                                        <th class="px-3 py-2.5 font-semibold">#</th>
                                        <th class="px-3 py-2.5 font-semibold">Bodega</th>
                                        <th class="px-3 py-2.5 font-semibold">Codigo</th>
                                        <th class="px-3 py-2.5 font-semibold">Descripcion</th>
                                        <th class="px-3 py-2.5 font-semibold">Familia</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Stock</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Costo Und</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Valor Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach($details as $d): $i++; ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-1.5 text-gray-400"><?= $i ?></td>
                                        <td class="px-3 py-1.5 text-gray-500"><?= $d->store_name ?></td>
                                        <td class="px-3 py-1.5 font-mono font-medium"><?= $d->idProduct ?></td>
                                        <td class="px-3 py-1.5"><?= $d->description ?></td>
                                        <td class="px-3 py-1.5 text-gray-500"><?= $d->family_name ?></td>
                                        <td class="px-3 py-1.5 text-right"><?= number_format($d->stock, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right">$<?= number_format($d->cost_cop, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right font-bold">$<?= number_format($d->value, 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1B365D; color:white;" class="font-bold text-xs">
                                        <td class="px-3 py-2.5" colspan="5">TOTAL</td>
                                        <td class="px-3 py-2.5 text-right"><?= number_format($grandUnits, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">-</td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($grandValue, 0, ',', '.') ?></td>
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
