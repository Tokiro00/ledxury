<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$thisFile = 'sisvent/commercial/smartcatalog';
$status_colors = ['activo' => 'bg-green-100 text-green-800', 'alerta' => 'bg-yellow-100 text-yellow-800', 'dormido' => 'bg-red-100 text-red-800', 'inactivo' => 'bg-gray-100 text-gray-500'];
?>
<!DOCTYPE html>
<html lang="es">
<title>MAM — Fidelización</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
.fid-card { background: white; border-radius: 12px; border: 1px solid #E5E7EB; padding: 20px; }
.vendor-row:hover { background: #F0F9FF; }
.orphan-row:hover { background: #FFF7ED; }
</style>
<head></head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $thisFile, 'role' => $role]); ?>

    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>

        <main class="h-full overflow-y-auto pb-16">
            <div class="px-6 mx-auto">

                <div class="flex items-center justify-between mt-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">🎯 Panel de Fidelización</h2>
                        <p class="text-sm text-gray-500">Clientes dormidos, huérfanos y ranking de vendedores</p>
                    </div>
                    <a href="<?= base_url('sisvent/commercial/smartcatalog') ?>" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">← Catálogo</a>
                </div>

                <!-- Ranking vendedores -->
                <div class="fid-card mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">🏆 Ranking vendedores — <?= date('F Y') ?></h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-xs text-gray-500 uppercase border-b">
                                <tr>
                                    <th class="pb-3 text-left">#</th>
                                    <th class="pb-3 text-left">Vendedor</th>
                                    <th class="pb-3 text-left">Tienda</th>
                                    <th class="pb-3 text-right">Venta mes</th>
                                    <th class="pb-3 text-right">Meta</th>
                                    <th class="pb-3 text-center">Cumplimiento</th>
                                    <th class="pb-3 text-right">Facturas</th>
                                    <th class="pb-3 text-right">Clientes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php foreach($ranking as $i => $v): ?>
                                <tr class="vendor-row">
                                    <td class="py-3 font-bold text-gray-400"><?= $i + 1 ?></td>
                                    <td class="py-3 font-medium text-gray-800"><?= htmlspecialchars($v->name) ?></td>
                                    <td class="py-3 text-gray-500 text-xs"><?= htmlspecialchars($v->store_name ?? '-') ?></td>
                                    <td class="py-3 text-right font-bold text-gray-800">$<?= number_format($v->venta_mes, 0, ',', '.') ?></td>
                                    <td class="py-3 text-right text-gray-500">$<?= number_format($v->meta_mes, 0, ',', '.') ?></td>
                                    <td class="py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full <?= $v->cumplimiento >= 100 ? 'bg-green-500' : ($v->cumplimiento >= 70 ? 'bg-yellow-500' : 'bg-red-500') ?>" style="width: <?= min($v->cumplimiento, 100) ?>%"></div>
                                            </div>
                                            <span class="text-xs font-bold <?= $v->cumplimiento >= 100 ? 'text-green-600' : ($v->cumplimiento >= 70 ? 'text-yellow-600' : 'text-red-600') ?>">
                                                <?= $v->cumplimiento ?>%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 text-right text-gray-500"><?= $v->facturas_mes ?></td>
                                    <td class="py-3 text-right text-gray-500"><?= $v->clientes_atendidos ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Clientes dormidos -->
                    <div class="fid-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-800">😴 Clientes dormidos</h3>
                            <span class="px-2 py-1 text-xs font-bold bg-red-100 text-red-800 rounded-full"><?= count($dormidos) ?></span>
                        </div>
                        <p class="text-xs text-gray-400 mb-3">Más de 30 días sin comprar · Facturación histórica > $1M</p>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            <?php foreach(array_slice($dormidos, 0, 30) as $c):
                                $nombre = $c->commercial_name ?: $c->name;
                            ?>
                            <div class="flex items-center justify-between p-2 rounded-lg hover:bg-red-50 transition">
                                <div class="min-w-0">
                                    <a href="<?= base_url('sisvent/commercial/smartcatalog/clientview/' . $c->idClient) ?>" class="text-sm font-medium text-gray-800 hover:text-blue-600 truncate block">
                                        <?= htmlspecialchars($nombre) ?>
                                    </a>
                                    <p class="text-xs text-gray-400">
                                        📍 <?= htmlspecialchars($c->city ?? '') ?> ·
                                        $<?= number_format($c->total_comprado, 0, ',', '.') ?> total ·
                                        <span class="text-red-500 font-medium"><?= $c->dias_sin_compra ?> días</span>
                                    </p>
                                </div>
                                <a href="<?= base_url('sisvent/commercial/smartcatalog/clientcatalog/' . $c->idClient) ?>" class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded hover:bg-green-100 shrink-0" title="Enviar catálogo">📋</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Clientes huérfanos -->
                    <div class="fid-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-800">👻 Clientes huérfanos</h3>
                            <span class="px-2 py-1 text-xs font-bold bg-yellow-100 text-yellow-800 rounded-full"><?= count($huerfanos) ?></span>
                        </div>
                        <p class="text-xs text-gray-400 mb-3">Vendedor inactivo o vetado · Necesitan reasignación</p>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            <?php foreach(array_slice($huerfanos, 0, 30) as $c):
                                $nombre = $c->commercial_name ?: $c->name;
                            ?>
                            <div class="flex items-center justify-between p-2 rounded-lg hover:bg-yellow-50 transition orphan-row">
                                <div class="min-w-0">
                                    <a href="<?= base_url('sisvent/commercial/smartcatalog/clientview/' . $c->idClient) ?>" class="text-sm font-medium text-gray-800 hover:text-blue-600 truncate block">
                                        <?= htmlspecialchars($nombre) ?>
                                    </a>
                                    <p class="text-xs text-gray-400">
                                        📍 <?= htmlspecialchars($c->city ?? '') ?> ·
                                        $<?= number_format($c->total_comprado, 0, ',', '.') ?> ·
                                        <span class="text-yellow-600">Vendedor: <?= htmlspecialchars($c->vendor_name ?? $c->vendor) ?></span>
                                    </p>
                                </div>
                                <a href="<?= base_url('sisvent/commercial/smartcatalog/clientcatalog/' . $c->idClient) ?>" class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded hover:bg-green-100 shrink-0">📋</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
