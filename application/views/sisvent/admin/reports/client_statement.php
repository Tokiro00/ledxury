<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="es">
    <title>Estado de Cuenta Clientes</title>
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
                            <h2 class="text-xl font-bold text-gray-800">Estado de Cuenta</h2>
                            <p class="text-sm text-gray-500"><?= $client ? $client->name : 'Seleccione un cliente' ?></p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/admin/reports/clientStatement" class="text-sm hover:underline" style="color:#1B365D;">← Buscar otro cliente</a>
                    </div>

                    <!-- Buscador -->
                    <?php if(!$client): ?>
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <label class="text-xs text-gray-500 uppercase">Buscar cliente</label>
                        <form method="GET" class="flex gap-2 mt-1">
                            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Nombre, NIT o telefono..." class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#1B365D;">Buscar</button>
                        </form>
                        <?php if(!empty($results)): ?>
                        <div class="mt-3 border rounded-lg overflow-hidden">
                            <?php foreach($results as $r): ?>
                            <a href="?id=<?= $r->idClient ?>" class="block px-4 py-3 hover:bg-blue-50 border-b text-sm">
                                <span class="font-bold"><?= $r->name ?></span>
                                <span class="text-gray-400 ml-2"><?= $r->idNum ?></span>
                                <span class="text-gray-400 ml-2"><?= $r->city ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php elseif($q): ?>
                        <p class="mt-3 text-sm text-gray-400">Sin resultados para "<?= htmlspecialchars($q) ?>"</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if($client): ?>
                    <!-- Info del cliente -->
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Cliente</p>
                                <p class="text-sm font-bold"><?= $client->name ?></p>
                                <p class="text-xs text-gray-400"><?= $client->idNum ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Vendedor</p>
                                <p class="text-sm"><?= $vendorName ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Ciudad</p>
                                <p class="text-sm"><?= $client->city ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Telefono</p>
                                <p class="text-sm"><?= $client->cellphone ?: $client->phone ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- KPIs -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Total Compras</p>
                            <p class="text-xl font-black" style="color:#1B365D">$<?= number_format($totals->total_compras, 0, ',', '.') ?></p>
                            <p class="text-xs text-gray-400"><?= $totals->num_facturas ?> facturas</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Total Pagado</p>
                            <p class="text-xl font-black text-green-600">$<?= number_format($totals->total_pagado, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Saldo Pendiente</p>
                            <p class="text-xl font-black text-red-600">$<?= number_format($totals->saldo_pendiente, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-orange-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Ultimo Pago</p>
                            <p class="text-sm font-bold"><?= $totals->ultimo_pago ? date('d/m/Y', strtotime($totals->ultimo_pago)) : 'Sin pagos' ?></p>
                        </div>
                    </div>

                    <!-- Facturas -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-4">
                        <div class="px-4 py-2 border-b" style="background:#1B365D; color:white;">
                            <h3 class="text-sm font-bold">Facturas</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-gray-50 text-left">
                                        <th class="px-3 py-2">#</th>
                                        <th class="px-3 py-2">Fecha</th>
                                        <th class="px-3 py-2 text-right">Total</th>
                                        <th class="px-3 py-2 text-right">Pagado</th>
                                        <th class="px-3 py-2 text-right">Descuento</th>
                                        <th class="px-3 py-2 text-right">Saldo</th>
                                        <th class="px-3 py-2 text-center">Estado</th>
                                        <th class="px-3 py-2 text-center">Dias</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($facturas as $f):
                                        $saldo = (float)$f->total - (float)$f->payment - (float)$f->discount;
                                        $dias = (int)((time() - strtotime($f->date)) / 86400);
                                        $stLabel = $f->state == 2 ? 'Pagada' : ($f->state == 1 ? 'Parcial' : 'Pendiente');
                                        $stColor = $f->state == 2 ? 'text-green-600' : ($f->state == 1 ? 'text-orange-600' : 'text-red-600');
                                    ?>
                                    <tr class="border-t hover:bg-blue-50">
                                        <td class="px-3 py-1.5 font-mono font-bold">#<?= $f->idInvoice ?></td>
                                        <td class="px-3 py-1.5"><?= date('d/m/Y', strtotime($f->date)) ?></td>
                                        <td class="px-3 py-1.5 text-right">$<?= number_format($f->total, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right text-green-600">$<?= number_format($f->payment, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right text-gray-400">$<?= number_format($f->discount, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right font-bold <?= $saldo > 0 ? 'text-red-600' : 'text-green-600' ?>">$<?= number_format($saldo, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-center"><span class="font-bold <?= $stColor ?>"><?= $stLabel ?></span></td>
                                        <td class="px-3 py-1.5 text-center <?= $dias > 30 ? 'text-red-600 font-bold' : 'text-gray-400' ?>"><?= $dias ?>d</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagos -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="px-4 py-2 border-b bg-green-600 text-white">
                            <h3 class="text-sm font-bold">Historial de Pagos</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-gray-50 text-left">
                                        <th class="px-3 py-2">Fecha</th>
                                        <th class="px-3 py-2">Factura</th>
                                        <th class="px-3 py-2 text-right">Monto</th>
                                        <th class="px-3 py-2">Metodo</th>
                                        <th class="px-3 py-2">Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($pagos)): ?>
                                    <tr><td colspan="5" class="px-3 py-4 text-center text-gray-400">Sin pagos registrados</td></tr>
                                    <?php else: foreach($pagos as $p): ?>
                                    <tr class="border-t hover:bg-green-50">
                                        <td class="px-3 py-1.5"><?= date('d/m/Y H:i', strtotime($p->date)) ?></td>
                                        <td class="px-3 py-1.5 font-mono">#<?= $p->invoiceId ?></td>
                                        <td class="px-3 py-1.5 text-right font-bold text-green-600">$<?= number_format($p->payment, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5"><?= $p->method_name ?: '-' ?></td>
                                        <td class="px-3 py-1.5 text-gray-500"><?= $p->comments ?: '' ?></td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
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
