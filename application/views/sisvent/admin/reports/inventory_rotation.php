<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Rotacion de Inventario</title>
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
                            <h2 class="text-xl font-bold text-gray-800">Rotacion de Inventario</h2>
                            <p class="text-sm text-gray-500">Analisis de movimiento de productos (ultimos 12 meses)</p>
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
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Productos</p>
                            <p class="text-lg font-bold text-gray-800 mt-1"><?= number_format($totalProducts, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4 border-l-4 border-green-500">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Alta Rotacion</p>
                            <p class="text-lg font-bold text-green-600 mt-1"><?= $alta ?></p>
                            <p class="text-xs text-gray-400">Indice >= 4</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4 border-l-4 border-yellow-500">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Media Rotacion</p>
                            <p class="text-lg font-bold text-yellow-600 mt-1"><?= $media ?></p>
                            <p class="text-xs text-gray-400">Indice 1-4</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4 border-l-4 border-orange-500">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Baja Rotacion</p>
                            <p class="text-lg font-bold text-orange-600 mt-1"><?= $baja ?></p>
                            <p class="text-xs text-gray-400">Indice < 1</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4 border-l-4 border-red-500">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Sin Movimiento</p>
                            <p class="text-lg font-bold text-red-600 mt-1"><?= $sinMov ?></p>
                            <p class="text-xs text-gray-400">0 ventas en 12m</p>
                        </div>
                    </div>

                    <!-- Filter tabs -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="flex items-center border-b px-4 py-2 gap-1">
                            <span class="text-xs text-gray-500 mr-2">Filtrar:</span>
                            <button class="rot-filter px-3 py-1.5 text-xs font-semibold rounded-md bg-blue-100 text-blue-800" data-filter="all">Todos</button>
                            <button class="rot-filter px-3 py-1.5 text-xs font-semibold rounded-md text-gray-500 hover:bg-gray-100" data-filter="alta">Alta</button>
                            <button class="rot-filter px-3 py-1.5 text-xs font-semibold rounded-md text-gray-500 hover:bg-gray-100" data-filter="media">Media</button>
                            <button class="rot-filter px-3 py-1.5 text-xs font-semibold rounded-md text-gray-500 hover:bg-gray-100" data-filter="baja">Baja</button>
                            <button class="rot-filter px-3 py-1.5 text-xs font-semibold rounded-md text-gray-500 hover:bg-gray-100" data-filter="sinmov">Sin Mov.</button>
                        </div>
                        <div class="overflow-x-auto" style="max-height:600px; overflow-y:auto;">
                            <table class="w-full text-xs" id="rotation-table">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white; position:sticky; top:0; z-index:10;">
                                        <th class="px-3 py-2.5 font-semibold">#</th>
                                        <th class="px-3 py-2.5 font-semibold">Codigo</th>
                                        <th class="px-3 py-2.5 font-semibold">Descripcion</th>
                                        <th class="px-3 py-2.5 font-semibold">Familia</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Stock</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Vendido 12m</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Indice Rot.</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Dias Stock</th>
                                        <th class="px-3 py-2.5 font-semibold">Clasificacion</th>
                                        <th class="px-3 py-2.5 font-semibold">Ult. Venta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach($products as $p): $i++;
                                        $idx = (float)$p->rotation_index;
                                        $qtySold = (int)$p->qty_sold;
                                        $daysStock = (int)$p->days_of_stock;
                                        if ($qtySold == 0) {
                                            $cls = 'sinmov'; $clsLabel = 'Sin Movimiento'; $clsColor = 'bg-red-100 text-red-800';
                                        } elseif ($idx >= 4) {
                                            $cls = 'alta'; $clsLabel = 'Alta'; $clsColor = 'bg-green-100 text-green-800';
                                        } elseif ($idx >= 1) {
                                            $cls = 'media'; $clsLabel = 'Media'; $clsColor = 'bg-yellow-100 text-yellow-800';
                                        } else {
                                            $cls = 'baja'; $clsLabel = 'Baja'; $clsColor = 'bg-orange-100 text-orange-800';
                                        }
                                        // Dead stock flag: 0 sales in 6+ months
                                        $deadStock = false;
                                        if ($p->last_sale && strtotime($p->last_sale) < strtotime('-6 months')) {
                                            $deadStock = true;
                                        } elseif (!$p->last_sale && $qtySold == 0) {
                                            $deadStock = true;
                                        }
                                    ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50 rot-row" data-cls="<?= $cls ?>">
                                        <td class="px-3 py-1.5 text-gray-400"><?= $i ?></td>
                                        <td class="px-3 py-1.5 font-mono font-medium"><?= $p->idProduct ?></td>
                                        <td class="px-3 py-1.5">
                                            <?= $p->description ?>
                                            <?php if($deadStock): ?><span class="ml-1 px-1 py-0.5 text-xs bg-red-600 text-white rounded">DEAD STOCK</span><?php endif; ?>
                                        </td>
                                        <td class="px-3 py-1.5 text-gray-500"><?= $p->family_name ?></td>
                                        <td class="px-3 py-1.5 text-right"><?= number_format($p->stock, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right"><?= number_format($p->qty_sold, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right font-bold"><?= number_format($idx, 2) ?></td>
                                        <td class="px-3 py-1.5 text-right <?= $daysStock > 365 ? 'text-red-700 font-bold' : '' ?>"><?= $daysStock >= 9999 ? '---' : number_format($daysStock, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5"><span class="px-2 py-0.5 rounded-full text-xs font-bold <?= $clsColor ?>"><?= $clsLabel ?></span></td>
                                        <td class="px-3 py-1.5 text-gray-500"><?= $p->last_sale ? date('d/m/Y', strtotime($p->last_sale)) : 'Nunca' ?></td>
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

<script>
$(document).on('click', '.rot-filter', function() {
    var filter = $(this).data('filter');
    $('.rot-filter').removeClass('bg-blue-100 text-blue-800').addClass('text-gray-500');
    $(this).addClass('bg-blue-100 text-blue-800').removeClass('text-gray-500');
    if (filter === 'all') {
        $('.rot-row').show();
    } else {
        $('.rot-row').hide();
        $('.rot-row[data-cls="' + filter + '"]').show();
    }
});
</script>
</body>
</html>
