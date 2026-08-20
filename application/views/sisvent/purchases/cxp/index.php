<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Panel de Cuentas por Pagar (aging por proveedor).
 * Línea gráfica de Ledxury (Tailwind + azul petróleo), igual que el resto
 * de los listados: título h2, tarjetas de resumen y tabla shadow-xs.
 */
$money = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
$t = $totals ?? array();
$totalBase   = (float)($t['total_base']    ?? 0);
$overdue     = (float)($t['overdue_base']  ?? 0);
$over90      = (float)($t['over_90d_base'] ?? 0);
$numFacturas = (int)  ($t['num_invoices']  ?? 0);
$numProv     = (int)  ($t['num_providers'] ?? 0);

$b = array('b1'=>0,'b2'=>0,'b3'=>0,'b4'=>0);
foreach ($aging as $row) { $b['b1'] += (float)$row->b1; $b['b2'] += (float)$row->b2; $b['b3'] += (float)$row->b3; $b['b4'] += (float)$row->b4; }
$sumBuckets = $b['b1'] + $b['b2'] + $b['b3'] + $b['b4'];
$pct = function ($v) use ($sumBuckets) { return $sumBuckets > 0 ? round($v / $sumBuckets * 100, 1) : 0; };
$transitTotal = 0; foreach (($transit ?? array()) as $v) { $transitTotal += (float)$v; }
$advTotal = 0; foreach (($advances ?? array()) as $a) { $advTotal += (float)$a->available; }
?>
<!DOCTYPE html>
<html lang="es">
    <title>Cuentas por Pagar</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head></head>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>

            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">

                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">Cuentas por Pagar a Proveedores</h2>

                    <!-- BOTONERA -->
                    <div class="flex flex-col flex-wrap mb-6 space-y-4 md:flex-row md:items-end md:space-x-4 md:space-y-0">
                        <a href="<?= base_url() ?>sisvent/purchases/provider_invoices"
                           class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg hover:bg-mam-blue focus:outline-none">Facturas de Proveedor</a>
                        <a href="<?= base_url() ?>sisvent/purchases/provider_invoices/add"
                           class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg hover:bg-mam-blue focus:outline-none">Cargar Factura</a>
                        <a href="<?= base_url() ?>sisvent/purchases/provider_payments"
                           class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100">Pagos Realizados</a>
                        <a href="<?= base_url() ?>sisvent/purchases/provider_advances"
                           class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100">Anticipos</a>
                    </div>

                    <!-- KPIs -->
                    <div class="grid gap-4 mb-6 md:grid-cols-4">
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Deuda con proveedores</p>
                            <p class="text-lg font-bold text-gray-800 mt-1"><?= $money($totalBase) ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?= $numFacturas ?> factura(s) · <?= $numProv ?> proveedor(es)</p>
                        </div>
                        <div class="<?= $overdue > 0 ? 'bg-red-50' : 'bg-white' ?> rounded-lg shadow-sm p-4">
                            <p class="text-xs <?= $overdue > 0 ? 'text-red-600' : 'text-gray-500' ?> uppercase">Vencido</p>
                            <p class="text-lg font-bold <?= $overdue > 0 ? 'text-red-700' : 'text-gray-800' ?> mt-1"><?= $money($overdue) ?></p>
                            <p class="text-xs <?= $overdue > 0 ? 'text-red-600' : 'text-gray-500' ?> mt-1"><?= $overdue > 0 ? 'pasado de la fecha de pago' : 'nada vencido' ?></p>
                        </div>
                        <div class="bg-orange-50 rounded-lg shadow-sm p-4">
                            <p class="text-xs text-orange-600 uppercase">Mercancía por recibir</p>
                            <p class="text-lg font-bold text-orange-700 mt-1"><?= $money($transitTotal) ?></p>
                            <p class="text-xs text-orange-600 mt-1">en tránsito, ya es deuda</p>
                        </div>
                        <div class="bg-green-50 rounded-lg shadow-sm p-4">
                            <p class="text-xs text-green-600 uppercase">Pagado este mes</p>
                            <p class="text-lg font-bold text-green-700 mt-1"><?= $money($month_payments ?? 0) ?></p>
                            <?php if ($advTotal > 0): ?><p class="text-xs text-green-600 mt-1">anticipos disponibles <?= $money($advTotal) ?></p><?php endif; ?>
                        </div>
                    </div>

                    <!-- BARRA DE ANTIGÜEDAD -->
                    <?php if ($sumBuckets > 0): ?>
                    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                        <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase mb-3">Antigüedad de la deuda</p>
                        <div class="flex w-full h-6 rounded overflow-hidden bg-gray-100 mb-2">
                            <?php if ($b['b1'] > 0): ?><div class="bg-green-500"  style="width: <?= $pct($b['b1']) ?>%"></div><?php endif; ?>
                            <?php if ($b['b2'] > 0): ?><div class="bg-yellow-400" style="width: <?= $pct($b['b2']) ?>%"></div><?php endif; ?>
                            <?php if ($b['b3'] > 0): ?><div class="bg-orange-500" style="width: <?= $pct($b['b3']) ?>%"></div><?php endif; ?>
                            <?php if ($b['b4'] > 0): ?><div class="bg-red-600"    style="width: <?= $pct($b['b4']) ?>%"></div><?php endif; ?>
                        </div>
                        <div class="flex flex-wrap gap-4 text-xs text-gray-600">
                            <span><span class="inline-block w-3 h-3 rounded-sm bg-green-500 mr-1"></span>0–30 días · <b><?= $money($b['b1']) ?></b> (<?= $pct($b['b1']) ?>%)</span>
                            <span><span class="inline-block w-3 h-3 rounded-sm bg-yellow-400 mr-1"></span>31–60 · <b><?= $money($b['b2']) ?></b> (<?= $pct($b['b2']) ?>%)</span>
                            <span><span class="inline-block w-3 h-3 rounded-sm bg-orange-500 mr-1"></span>61–90 · <b><?= $money($b['b3']) ?></b> (<?= $pct($b['b3']) ?>%)</span>
                            <span><span class="inline-block w-3 h-3 rounded-sm bg-red-600 mr-1"></span>+90 · <b><?= $money($b['b4']) ?></b> (<?= $pct($b['b4']) ?>%)</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- AGING POR PROVEEDOR -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs mb-8">
                        <div class="px-4 py-3 bg-gray-50 border-b">
                            <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Saldo por proveedor (<?= count($aging) ?>)</p>
                        </div>
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Proveedor</th>
                                        <th class="px-4 py-3 text-center">Facturas</th>
                                        <th class="px-4 py-3 text-right">0–30</th>
                                        <th class="px-4 py-3 text-right">31–60</th>
                                        <th class="px-4 py-3 text-right">61–90</th>
                                        <th class="px-4 py-3 text-right">+90</th>
                                        <th class="px-4 py-3 text-right">Por recibir</th>
                                        <th class="px-4 py-3 text-right">Anticipos</th>
                                        <th class="px-4 py-3 text-right">Saldo total</th>
                                        <th class="px-4 py-3 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php if (empty($aging)): ?>
                                        <tr><td colspan="10" class="px-4 py-6 text-sm text-center text-gray-500">No hay deuda abierta con proveedores</td></tr>
                                    <?php else: foreach ($aging as $row):
                                        $pid = (int)$row->provider_id;
                                        $tr  = (float)($transit[$pid] ?? 0);
                                        $adv = isset($advances[$pid]) ? (float)$advances[$pid]->available : 0;
                                    ?>
                                    <tr class="text-gray-700 hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium"><?= htmlspecialchars($row->provider_name) ?></td>
                                        <td class="px-4 py-3 text-sm text-center"><?= (int)$row->num_invoices ?></td>
                                        <td class="px-4 py-3 text-sm text-right <?= (float)$row->b1 > 0 ? 'text-green-700' : 'text-gray-300' ?>"><?= (float)$row->b1 > 0 ? $money($row->b1) : '—' ?></td>
                                        <td class="px-4 py-3 text-sm text-right <?= (float)$row->b2 > 0 ? 'text-yellow-700' : 'text-gray-300' ?>"><?= (float)$row->b2 > 0 ? $money($row->b2) : '—' ?></td>
                                        <td class="px-4 py-3 text-sm text-right <?= (float)$row->b3 > 0 ? 'text-orange-700' : 'text-gray-300' ?>"><?= (float)$row->b3 > 0 ? $money($row->b3) : '—' ?></td>
                                        <td class="px-4 py-3 text-sm text-right <?= (float)$row->b4 > 0 ? 'text-red-700 font-semibold' : 'text-gray-300' ?>"><?= (float)$row->b4 > 0 ? $money($row->b4) : '—' ?></td>
                                        <td class="px-4 py-3 text-sm text-right <?= $tr > 0 ? 'text-orange-600' : 'text-gray-300' ?>"><?= $tr > 0 ? $money($tr) : '—' ?></td>
                                        <td class="px-4 py-3 text-sm text-right <?= $adv > 0 ? 'text-green-600' : 'text-gray-300' ?>"><?= $adv > 0 ? '-' . $money($adv) : '—' ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-bold text-gray-800"><?= $money((float)$row->balance_base + $tr) ?></td>
                                        <td class="px-4 py-3 text-sm text-right whitespace-no-wrap">
                                            <a class="px-2 py-1 text-xs font-medium text-white bg-mam-blue-petroleo rounded hover:bg-mam-blue" href="<?= base_url() ?>sisvent/purchases/provider_invoices?provider_id=<?= $pid ?>">Facturas</a>
                                            <a class="px-2 py-1 text-xs font-medium text-gray-700 border border-gray-300 rounded hover:bg-gray-100" href="<?= base_url() ?>sisvent/purchases/provider_invoices/statement/<?= $pid ?>">Estado</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                                <?php if (!empty($aging)): ?>
                                <tfoot>
                                    <tr class="bg-gray-100 border-t-2 border-gray-300 text-gray-700">
                                        <td class="px-4 py-3 text-sm font-bold uppercase" colspan="2">Totales</td>
                                        <td class="px-4 py-3 text-sm text-right font-bold"><?= $money($b['b1']) ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-bold"><?= $money($b['b2']) ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-bold"><?= $money($b['b3']) ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-bold"><?= $money($b['b4']) ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-bold"><?= $money($transitTotal) ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-bold"><?= $advTotal > 0 ? '-' . $money($advTotal) : '—' ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-bold"><?= $money($totalBase + $transitTotal) ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
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
