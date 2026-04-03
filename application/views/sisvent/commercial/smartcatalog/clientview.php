<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$thisFile = 'sisvent/commercial/smartcatalog';
$c = $d->client;
$s = $d->stats;
$nombre = $c->commercial_name ?: $c->name;
$saldoPendiente = $s->saldo_pendiente ?: 0;
$saldoVencido = $s->saldo_vencido ?: 0;
$invoiceStates = [0 => ['label' => 'Pendiente', 'class' => 'bg-yellow-100 text-yellow-800'],
                  1 => ['label' => 'Parcial', 'class' => 'bg-blue-100 text-blue-800'],
                  2 => ['label' => 'Pagada', 'class' => 'bg-green-100 text-green-800'],
                  3 => ['label' => 'Completa', 'class' => 'bg-green-100 text-green-800']];
?>
<!DOCTYPE html>
<html lang="es">
<title>Ledxury — <?= htmlspecialchars($nombre) ?></title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<style>
.dash-card { background: white; border-radius: 12px; border: 1px solid #E5E7EB; padding: 20px; }
.product-mini { display: flex; align-items: center; gap: 12px; padding: 8px; border-radius: 8px; transition: background 0.15s; }
.product-mini:hover { background: #F9FAFB; }
.product-mini img { width: 48px; height: 48px; border-radius: 8px; object-fit: contain; background: #F3F4F6; }
</style>
<head></head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $thisFile, 'role' => $role]); ?>

    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>

        <main class="h-full overflow-y-auto pb-16">
            <div class="px-6 mx-auto">

                <!-- Header del cliente -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-4 mb-6">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($nombre) ?></h2>
                            <span class="px-2 py-0.5 rounded text-xs font-bold <?= $c->type === 'A' ? 'bg-yellow-100 text-yellow-800' : ($c->type === 'B' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600') ?>">
                                Tipo <?= $c->type ?: '-' ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">
                            📍 <?= htmlspecialchars($c->city ?? '') ?> · 👤 Vendedor: <?= htmlspecialchars($c->vendor_name ?? '-') ?>
                            <?php if($c->vendor_phone): ?> · 📞 <?= $c->vendor_phone ?><?php endif; ?>
                        </p>
                    </div>
                    <div class="flex gap-2 mt-2 md:mt-0">
                        <a href="<?= base_url('sisvent/commercial/smartcatalog/clientcatalog/' . $c->idClient) ?>" class="px-4 py-2 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600">
                            📋 Catálogo personalizado
                        </a>
                        <a href="<?= base_url('sisvent/commercial/smartcatalog/clients') ?>" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
                            ← Clientes
                        </a>
                    </div>
                </div>

                <!-- Stats del cliente -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-6">
                    <div class="dash-card">
                        <p class="text-xs text-gray-500">Total comprado</p>
                        <p class="text-lg font-bold text-gray-800">$<?= number_format($s->total_comprado, 0, ',', '.') ?></p>
                    </div>
                    <div class="dash-card">
                        <p class="text-xs text-gray-500">Facturas</p>
                        <p class="text-lg font-bold text-gray-800"><?= number_format($s->total_facturas) ?></p>
                    </div>
                    <div class="dash-card">
                        <p class="text-xs text-gray-500">Pagado</p>
                        <p class="text-lg font-bold text-green-600">$<?= number_format($s->total_pagado, 0, ',', '.') ?></p>
                    </div>
                    <div class="dash-card">
                        <p class="text-xs text-gray-500">Saldo pendiente</p>
                        <p class="text-lg font-bold <?= $saldoPendiente > 0 ? 'text-yellow-600' : 'text-green-600' ?>">$<?= number_format($saldoPendiente, 0, ',', '.') ?></p>
                    </div>
                    <div class="dash-card">
                        <p class="text-xs text-gray-500">Saldo vencido</p>
                        <p class="text-lg font-bold <?= $saldoVencido > 0 ? 'text-red-600' : 'text-green-600' ?>">$<?= number_format($saldoVencido, 0, ',', '.') ?></p>
                    </div>
                    <div class="dash-card">
                        <p class="text-xs text-gray-500">Primera / Última compra</p>
                        <p class="text-xs text-gray-600 mt-1">
                            <?= $s->primera_compra ? date('d/m/Y', strtotime($s->primera_compra)) : '-' ?><br>
                            <?= $s->ultima_compra ? date('d/m/Y', strtotime($s->ultima_compra)) : '-' ?>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- TOP Productos del cliente -->
                    <div class="lg:col-span-1 dash-card">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">🔥 Sus productos favoritos</h3>
                        <div class="space-y-1 max-h-96 overflow-y-auto">
                            <?php foreach($d->topProducts as $tp):
                                $imgurl = $tp->picture_url;
                                if ($tp->picture_url == 'products/no_image.png' && file_exists('public/dist/images/products/' . $tp->idProduct . '.jpg')) {
                                    $imgurl = 'products/' . $tp->idProduct . '.jpg';
                                }
                            ?>
                            <div class="product-mini">
                                <?php if($tp->hasImage): ?>
                                    <img src="<?= get_images_path($imgurl) ?>" alt="" loading="lazy" onerror="this.src='<?= get_images_path('products/no_image.png') ?>'">
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-xs font-mono text-gray-400"><?= $tp->idProduct ?></div>
                                <?php endif; ?>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-mono font-semibold text-blue-600"><?= htmlspecialchars($tp->idProduct) ?></p>
                                    <p class="text-xs text-gray-600 truncate"><?= htmlspecialchars($tp->description) ?></p>
                                    <p class="text-xs text-gray-400"><?= number_format($tp->total_comprado) ?> uds · <?= $tp->veces ?> veces</p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Facturas recientes -->
                    <div class="lg:col-span-2 dash-card">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">📄 Facturas recientes</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-gray-500 border-b">
                                    <tr>
                                        <th class="pb-2 text-left">#</th>
                                        <th class="pb-2 text-right">Total</th>
                                        <th class="pb-2 text-right">Pagado</th>
                                        <th class="pb-2 text-right">Saldo</th>
                                        <th class="pb-2 text-center">Estado</th>
                                        <th class="pb-2 text-right">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <?php foreach($d->invoices as $inv):
                                        $saldo = $inv->total - $inv->payment;
                                        $st = $invoiceStates[$inv->state] ?? $invoiceStates[0];
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 font-mono text-xs text-blue-600"><?= $inv->if_id ?: $inv->idInvoice ?></td>
                                        <td class="py-2 text-right font-medium">$<?= number_format($inv->total, 0, ',', '.') ?></td>
                                        <td class="py-2 text-right text-green-600">$<?= number_format($inv->payment, 0, ',', '.') ?></td>
                                        <td class="py-2 text-right <?= $saldo > 0 ? 'text-red-500 font-medium' : 'text-gray-400' ?>">
                                            $<?= number_format($saldo, 0, ',', '.') ?>
                                        </td>
                                        <td class="py-2 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $st['class'] ?>">
                                                <?= $st['label'] ?>
                                            </span>
                                            <?php if($inv->is_expired): ?>
                                            <span class="text-red-500 text-xs ml-1">⏰</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2 text-right text-xs text-gray-400"><?= date('d/m/Y', strtotime($inv->date)) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recomendaciones -->
                <?php if(!empty($d->recommendations)): ?>
                <div class="dash-card mt-6">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">💡 Productos recomendados</h3>
                    <p class="text-xs text-gray-400 mb-3">Clientes similares compran estos productos que <?= htmlspecialchars($nombre) ?> aún no compra</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <?php foreach($d->recommendations as $rec):
                            $imgurl = $rec->picture_url ?: 'products/no_image.png';
                            if ($rec->picture_url == 'products/no_image.png' && file_exists('public/dist/images/products/' . $rec->productId . '.jpg')) {
                                $imgurl = 'products/' . $rec->productId . '.jpg';
                            }
                        ?>
                        <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-blue-200 hover:bg-blue-50 transition">
                            <?php if($rec->hasImage): ?>
                                <img src="<?= get_images_path($imgurl) ?>" class="w-10 h-10 rounded object-contain bg-gray-50" loading="lazy">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center text-xs text-gray-400">📦</div>
                            <?php endif; ?>
                            <div class="min-w-0">
                                <p class="text-xs font-mono font-semibold text-blue-600"><?= htmlspecialchars($rec->productId) ?></p>
                                <p class="text-xs text-gray-600 truncate"><?= htmlspecialchars($rec->description) ?></p>
                                <p class="text-xs text-gray-400"><?= $rec->clientes_que_compran ?> clientes similares lo compran</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Presupuestos recientes -->
                <?php if(!empty($d->budgets)): ?>
                <div class="dash-card mt-6 mb-8">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">📝 Presupuestos recientes</h3>
                    <div class="space-y-2">
                        <?php foreach($d->budgets as $b): ?>
                        <div class="flex items-center justify-between py-2 border-b border-gray-50">
                            <div>
                                <span class="text-xs font-mono text-gray-500">#<?= $b->idBudget ?></span>
                                <span class="text-sm text-gray-700 ml-2">$<?= number_format($b->total, 0, ',', '.') ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs <?= $b->state == 0 ? 'text-yellow-600' : 'text-green-600' ?>">
                                    <?= $b->state == 0 ? 'Pendiente' : 'Procesado' ?>
                                </span>
                                <span class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($b->date)) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
