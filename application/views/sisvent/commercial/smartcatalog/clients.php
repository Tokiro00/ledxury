<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$thisFile = 'sisvent/commercial/smartcatalog';
$abc_colors = ['A' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'border' => 'border-yellow-300', 'label' => '⭐ Estrella'],
               'B' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'border' => 'border-blue-300', 'label' => '💎 Importante'],
               'C' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'border' => 'border-gray-300', 'label' => '📦 Pequeño']];
$status_colors = ['activo' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'dot' => 'bg-green-500'],
                  'alerta' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'dot' => 'bg-yellow-500'],
                  'dormido' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'dot' => 'bg-red-500'],
                  'inactivo' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-500', 'dot' => 'bg-gray-400']];
?>
<!DOCTYPE html>
<html lang="es">
<title>MAM — Clientes ABC</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
.client-row { transition: all 0.15s; }
.client-row:hover { background: #F0F9FF; }
.abc-pill { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; font-weight: 800; font-size: 16px; }
.summary-card { border-radius: 12px; padding: 20px; border: 1px solid #E5E7EB; background: white; }
</style>
<head></head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $thisFile, 'role' => $role]); ?>

    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>

        <main class="h-full overflow-y-auto pb-16">
            <div class="px-6 mx-auto">

                <!-- Header -->
                <div class="flex items-center justify-between mt-4 mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Clientes — Clasificación ABC</h2>
                        <p class="text-sm text-gray-500"><?= $data->total ?> clientes clasificados automáticamente por facturación</p>
                    </div>
                    <a href="<?= base_url('sisvent/commercial/smartcatalog') ?>" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
                        ← Catálogo
                    </a>
                </div>

                <!-- Resumen ABC -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-3 mb-6">
                    <?php foreach($data->abc as $class => $info): $ac = $abc_colors[$class]; ?>
                    <a href="<?= base_url('sisvent/commercial/smartcatalog/clients?abc=' . $class) ?>" class="summary-card hover:shadow-md <?= $filter === $class ? 'ring-2 ring-blue-500' : '' ?>">
                        <span class="abc-pill <?= $ac['bg'] ?> <?= $ac['text'] ?>"><?= $class ?></span>
                        <p class="text-2xl font-bold text-gray-800 mt-2"><?= $info['count'] ?></p>
                        <p class="text-xs text-gray-500"><?= $ac['label'] ?></p>
                        <p class="text-xs text-gray-400 mt-1">$<?= number_format($info['revenue'] / 1000000, 0) ?>M</p>
                    </a>
                    <?php endforeach; ?>

                    <!-- Status cards -->
                    <?php foreach($data->status as $st => $count): $sc = $status_colors[$st]; ?>
                    <a href="<?= base_url('sisvent/commercial/smartcatalog/clients?status=' . $st) ?>" class="summary-card hover:shadow-md <?= $statusFilter === $st ? 'ring-2 ring-blue-500' : '' ?>">
                        <span class="inline-block w-3 h-3 rounded-full <?= $sc['dot'] ?>"></span>
                        <p class="text-2xl font-bold text-gray-800 mt-2"><?= $count ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?= $st ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Búsqueda -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
                    <form method="get" class="flex gap-3">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar cliente por nombre o ciudad..." class="flex-1 py-2 px-4 text-sm border border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none">
                        <?php if($filter): ?><input type="hidden" name="abc" value="<?= $filter ?>"><?php endif; ?>
                        <?php if($statusFilter): ?><input type="hidden" name="status" value="<?= $statusFilter ?>"><?php endif; ?>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg">Buscar</button>
                        <?php if($search || $filter || $statusFilter): ?>
                        <a href="<?= base_url('sisvent/commercial/smartcatalog/clients') ?>" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-lg">Limpiar</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Tabla de clientes -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left">ABC</th>
                                    <th class="px-4 py-3 text-left">Cliente</th>
                                    <th class="px-4 py-3 text-left">Ciudad</th>
                                    <th class="px-4 py-3 text-left">Vendedor</th>
                                    <th class="px-4 py-3 text-right">Facturado</th>
                                    <th class="px-4 py-3 text-right">Facturas</th>
                                    <th class="px-4 py-3 text-center">Estado</th>
                                    <th class="px-4 py-3 text-right">Última compra</th>
                                    <th class="px-4 py-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach($data->clients as $c):
                                    $ac = $abc_colors[$c->abc_class] ?? $abc_colors['C'];
                                    $sc = $status_colors[$c->status] ?? $status_colors['inactivo'];
                                    $nombre = $c->commercial_name ?: $c->name;
                                ?>
                                <tr class="client-row">
                                    <td class="px-4 py-3">
                                        <span class="abc-pill <?= $ac['bg'] ?> <?= $ac['text'] ?> text-sm"><?= $c->abc_class ?></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="<?= base_url('sisvent/commercial/smartcatalog/clientview/' . $c->idClient) ?>" class="font-medium text-gray-800 hover:text-blue-600">
                                            <?= htmlspecialchars($nombre) ?>
                                        </a>
                                        <?php if($c->pct_revenue >= 1): ?>
                                        <span class="text-xs text-gray-400 ml-1">(<?= $c->pct_revenue ?>%)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars($c->city ?? '-') ?></td>
                                    <td class="px-4 py-3 text-gray-500 text-xs"><?= htmlspecialchars($c->vendor_name ?? '-') ?></td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-800">$<?= number_format($c->total_comprado, 0, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-right text-gray-500"><?= number_format($c->total_facturas) ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium <?= $sc['bg'] ?> <?= $sc['text'] ?>">
                                            <span class="w-1.5 h-1.5 rounded-full <?= $sc['dot'] ?>"></span>
                                            <?= ucfirst($c->status) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-xs text-gray-400">
                                        <?= $c->ultima_compra ? date('d/m/Y', strtotime($c->ultima_compra)) : 'Nunca' ?>
                                        <?php if($c->dias_sin_compra > 30): ?>
                                        <br><span class="text-red-400">(<?= $c->dias_sin_compra ?>d)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center gap-1">
                                            <a href="<?= base_url('sisvent/commercial/smartcatalog/clientview/' . $c->idClient) ?>" class="px-2 py-1 text-xs bg-blue-50 text-blue-600 rounded hover:bg-blue-100" title="Dashboard">📊</a>
                                            <a href="<?= base_url('sisvent/commercial/smartcatalog/clientcatalog/' . $c->idClient) ?>" class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded hover:bg-green-100" title="Catálogo personalizado">📋</a>
                                        </div>
                                    </td>
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
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
