<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="es">
    <title>Clientes Inactivos</title>
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
                            <h2 class="text-2xl font-black text-gray-800">Clientes Inactivos</h2>
                            <p class="text-xs text-gray-400 uppercase tracking-widest">Clientes sin comprar hace <?= $days ?> dias o mas</p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/admin/salesboard" class="mt-2 lg:mt-0 text-sm hover:underline" style="color:#1B365D;">← Volver al Panel</a>
                    </div>

                    <!-- Filtros -->
                    <div class="bg-white rounded-lg shadow-sm border p-3 mb-4">
                        <form method="GET" class="flex flex-wrap items-end gap-3">
                            <div>
                                <label class="text-xs text-gray-500">Vendedor</label>
                                <select name="vendor" class="block text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                                    <option value="all">Todos</option>
                                    <?php foreach($vendedores as $v): ?>
                                    <option value="<?= $v->idUser ?>" <?= $vendorId == $v->idUser ? 'selected' : '' ?>><?= $v->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Inactivos hace</label>
                                <select name="days" class="block text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                                    <option value="30" <?= $days == 30 ? 'selected' : '' ?>>30 dias</option>
                                    <option value="60" <?= $days == 60 ? 'selected' : '' ?>>60 dias</option>
                                    <option value="90" <?= $days == 90 ? 'selected' : '' ?>>90 dias</option>
                                    <option value="180" <?= $days == 180 ? 'selected' : '' ?>>180 dias</option>
                                </select>
                            </div>
                            <button type="submit" class="px-4 py-1.5 text-sm font-medium text-white rounded-lg" style="background:#1B365D;">Filtrar</button>
                        </form>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-3 mb-4">
                        <span class="text-sm font-bold text-red-600"><?= count($inactivos) ?></span>
                        <span class="text-sm text-gray-600">clientes sin comprar hace <?= $days ?>+ dias</span>
                    </div>

                    <!-- Tabla -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold">Cliente</th>
                                        <th class="px-3 py-2.5 font-semibold">Vendedor</th>
                                        <th class="px-3 py-2.5 font-semibold">Ciudad</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Ultima Compra</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Dias Inactivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($inactivos)): ?>
                                    <tr><td colspan="5" class="px-3 py-8 text-center text-gray-400">No hay clientes inactivos con estos filtros</td></tr>
                                    <?php else: $i=0; foreach($inactivos as $c): $i++;
                                        $lastDate = isset($c->max_date) ? $c->max_date : (isset($c->last_purchase) ? $c->last_purchase : null);
                                        $diasInactivo = $lastDate ? (int)((time() - strtotime($lastDate)) / 86400) : 999;
                                    ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-1.5 font-medium"><?= $c->name ?></td>
                                        <td class="px-3 py-1.5"><?= isset($c->vendor_name) ? $c->vendor_name : (isset($c->vendorId) ? $c->vendorId : '-') ?></td>
                                        <td class="px-3 py-1.5"><?= isset($c->city) ? $c->city : '-' ?></td>
                                        <td class="px-3 py-1.5 text-center"><?= $lastDate ? date('d/m/Y', strtotime($lastDate)) : 'Nunca' ?></td>
                                        <td class="px-3 py-1.5 text-center">
                                            <span class="font-bold <?= $diasInactivo > 90 ? 'text-red-600' : ($diasInactivo > 60 ? 'text-orange-600' : 'text-yellow-600') ?>"><?= $diasInactivo > 900 ? 'Nunca' : $diasInactivo . 'd' ?></span>
                                        </td>
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
