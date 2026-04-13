<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];

// Summary counts
$countA = 0; $countB = 0; $countC = 0; $countN = 0;
$revenueA = 0; $revenueB = 0; $revenueC = 0; $revenueN = 0;
if (!empty($products)) {
    foreach ($products as $p) {
        switch ($p->abc_type) {
            case 'A': $countA++; $revenueA += $p->revenue_12m; break;
            case 'B': $countB++; $revenueB += $p->revenue_12m; break;
            case 'C': $countC++; $revenueC += $p->revenue_12m; break;
            default:  $countN++; $revenueN += $p->revenue_12m; break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <title>Clasificacion ABC de Productos</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <!-- Title -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Clasificacion ABC de Productos</h2>
                            <p class="text-sm text-gray-500">Analisis de productos por contribucion a ingresos (ultimos 12 meses)</p>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Tipo A</p>
                            <p class="text-lg font-bold text-green-600 mt-1"><?= $countA ?> productos</p>
                            <p class="text-sm text-gray-600">$<?= number_format($revenueA, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-yellow-500 p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Tipo B</p>
                            <p class="text-lg font-bold text-yellow-600 mt-1"><?= $countB ?> productos</p>
                            <p class="text-sm text-gray-600">$<?= number_format($revenueB, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Tipo C</p>
                            <p class="text-lg font-bold text-red-600 mt-1"><?= $countC ?> productos</p>
                            <p class="text-sm text-gray-600">$<?= number_format($revenueC, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-gray-400 p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Sin Ventas</p>
                            <p class="text-lg font-bold text-gray-600 mt-1"><?= $countN ?> productos</p>
                            <p class="text-sm text-gray-600">$<?= number_format($revenueN, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Filter Bar -->
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <select id="abcStoreSelect" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="-1">Todas las bodegas</option>
                                <?php if (!empty($stores)): foreach ($stores as $s): ?>
                                <option value="<?= $s->idStore ?>" <?= $selectedStore == $s->idStore ? 'selected' : '' ?>><?= $s->name ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                            <select id="abcTypeSelect" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="all">Todos</option>
                                <option value="A" <?= $filterAbc == 'A' ? 'selected' : '' ?>>Tipo A</option>
                                <option value="B" <?= $filterAbc == 'B' ? 'selected' : '' ?>>Tipo B</option>
                                <option value="C" <?= $filterAbc == 'C' ? 'selected' : '' ?>>Tipo C</option>
                                <option value="N" <?= $filterAbc == 'N' ? 'selected' : '' ?>>Sin Ventas</option>
                            </select>
                            <button type="button" onclick="var st=document.getElementById('abcStoreSelect').value; var ab=document.getElementById('abcTypeSelect').value; window.location='<?= base_url() ?>sisvent/store/reorder/index/'+st+'/'+ab;" class="px-4 py-2 text-sm text-white rounded-lg" style="background:#2E7D91;">Filtrar</button>
                            <button type="button" id="btnRecalculate" class="px-4 py-2 text-sm text-white bg-orange-500 rounded-lg hover:bg-orange-600">Recalcular ABC</button>
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
                                        <th class="px-3 py-2.5 font-semibold">Proveedor</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">ABC</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Ingresos 12m</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Uds Vendidas</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Demanda/Mes</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Stock Actual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($products)): $i = 0; foreach ($products as $p): $i++;
                                        $badgeClass = '';
                                        switch ($p->abc_type) {
                                            case 'A': $badgeClass = 'bg-green-500 text-white'; break;
                                            case 'B': $badgeClass = 'bg-yellow-500 text-white'; break;
                                            case 'C': $badgeClass = 'bg-red-500 text-white'; break;
                                            default:  $badgeClass = 'bg-gray-400 text-white'; break;
                                        }
                                    ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-1.5 text-gray-400 font-bold"><?= $i ?></td>
                                        <td class="px-3 py-1.5 font-mono font-medium"><?= $p->idProduct ?></td>
                                        <td class="px-3 py-1.5"><?= $p->description ?></td>
                                        <td class="px-3 py-1.5 text-gray-500"><?= isset($p->family_name) ? $p->family_name : '' ?></td>
                                        <td class="px-3 py-1.5 text-gray-500"><?= isset($p->provider_name) ? $p->provider_name : '' ?></td>
                                        <td class="px-3 py-1.5 text-center">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold <?= $badgeClass ?>"><?= $p->abc_type ?></span>
                                        </td>
                                        <td class="px-3 py-1.5 text-right font-medium">$<?= number_format($p->revenue_12m, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right"><?= number_format($p->units_12m, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right"><?= number_format($p->demand_monthly, 1, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right"><?= number_format($p->stock_actual, 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr>
                                        <td colspan="10" class="px-3 py-6 text-center text-gray-400">No hay productos para mostrar</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    $(document).on('click', '#btnRecalculate', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Recalculando...');
        $.ajax({
            url: '<?= base_url() ?>sisvent/store/reorder/recalculate',
            type: 'POST',
            data: {
                '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>',
                'store': $('#abcStoreSelect').val()
            },
            success: function(resp) {
                location.reload();
            },
            error: function() {
                alert('Error al recalcular. Intente de nuevo.');
                btn.prop('disabled', false).text('Recalcular ABC');
            }
        });
    });

    $(document).on('click', '#btnFilter', function() {
        var store = $('#abcStoreSelect').val();
        var abc = $('#abcTypeSelect').val();
        window.location.href = '<?= base_url() ?>sisvent/store/reorder/index/' + store + '/' + abc;
    });
    </script>
</body>
</html>
