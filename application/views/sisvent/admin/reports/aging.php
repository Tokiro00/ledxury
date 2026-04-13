<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="es">
    <title>Antigüedad de Saldos</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Antiguedad de Saldos</h2>
                            <p class="text-sm text-gray-500">Cartera clasificada por dias de vencimiento</p>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="bg-white rounded-lg shadow-sm border p-3 mb-4">
                        <form method="GET" class="flex flex-wrap items-end gap-3">
                            <div>
                                <label class="text-xs text-gray-500">Vendedor</label>
                                <select name="vendor" class="block text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                                    <option value="all">Todos</option>
                                    <?php foreach($vendedores as $v): ?>
                                    <option value="<?= $v->idUser ?>" <?= $vendor == $v->idUser ? 'selected' : '' ?>><?= $v->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Bodega</label>
                                <select name="store" class="block text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                                    <option value="all">Todas</option>
                                    <?php foreach($tiendas as $t): ?>
                                    <option value="<?= $t->idStore ?>" <?= $store == $t->idStore ? 'selected' : '' ?>><?= $t->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="px-4 py-1.5 text-sm font-medium text-white rounded-lg" style="background:#1B365D;">Filtrar</button>
                        </form>
                    </div>

                    <!-- KPIs -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Corriente</p>
                            <p class="text-xl font-black text-green-600">$<?= number_format($totals->corriente, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-yellow-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">1-30 dias</p>
                            <p class="text-xl font-black text-yellow-600">$<?= number_format($totals->d1_30, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-orange-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">31-60 dias</p>
                            <p class="text-xl font-black text-orange-600">$<?= number_format($totals->d31_60, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">61-90 dias</p>
                            <p class="text-xl font-black text-red-600">$<?= number_format($totals->d61_90, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-800 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">90+ dias</p>
                            <p class="text-xl font-black text-red-800">$<?= number_format($totals->d90, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="bg-white rounded-lg shadow-sm border p-3 mb-4 flex justify-between items-center">
                        <span class="text-sm font-bold text-gray-600">Total cartera: $<?= number_format($totals->total, 0, ',', '.') ?></span>
                        <span class="text-sm text-gray-400"><?= count($clientes) ?> clientes con saldo</span>
                    </div>

                    <!-- Tabla -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold text-left">Cliente</th>
                                        <th class="px-3 py-2.5 font-semibold">Vendedor</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Corriente</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">1-30d</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">31-60d</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">61-90d</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">90+d</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($clientes)): ?>
                                    <tr><td colspan="8" class="px-3 py-8 text-center text-gray-400">Sin datos</td></tr>
                                    <?php else: $i=0; foreach($clientes as $c): $i++;
                                        $total = $c->corriente + $c->d1_30 + $c->d31_60 + $c->d61_90 + $c->d90;
                                        if ($total <= 0) continue;
                                    ?>
                                    <tr class="border-t <?= $i%2==0?'bg-gray-50':'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-1.5 font-medium"><?= $c->client_name ?></td>
                                        <td class="px-3 py-1.5 text-center"><?= $c->vendor_name ?></td>
                                        <td class="px-3 py-1.5 text-right <?= $c->corriente > 0 ? 'text-green-600' : '' ?>">$<?= number_format($c->corriente, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right <?= $c->d1_30 > 0 ? 'text-yellow-600 font-bold' : '' ?>">$<?= number_format($c->d1_30, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right <?= $c->d31_60 > 0 ? 'text-orange-600 font-bold' : '' ?>">$<?= number_format($c->d31_60, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right <?= $c->d61_90 > 0 ? 'text-red-600 font-bold' : '' ?>">$<?= number_format($c->d61_90, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right <?= $c->d90 > 0 ? 'text-red-800 font-bold' : '' ?>">$<?= number_format($c->d90, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right font-bold">$<?= number_format($total, 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; endif; ?>
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
