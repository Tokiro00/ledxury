<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$pctOver90 = $grandDebt > 0 ? ($grandOver90 / $grandDebt) * 100 : 0;
$pctCobro = $grandInvoiced > 0 ? ($grandPaid / $grandInvoiced) * 100 : 0;
$groupLabels = array('city' => 'Ciudad', 'store' => 'Tienda', 'vendor' => 'Vendedor');
$groupLabel = isset($groupLabels[$groupBy]) ? $groupLabels[$groupBy] : 'Ciudad';
?>
<!DOCTYPE html>
<html lang="en">
    <title>Reporte de Cartera</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <!-- Header -->
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Reporte de Cartera</h2>
                            <p class="text-sm text-gray-500">Antigüedad de cuentas por cobrar — Agrupado por <?= $groupLabel ?></p>
                        </div>
                    </div>

                    <!-- Filters Bar -->
                    <div class="bg-white rounded-lg shadow-sm border p-3 mb-4">
                        <form method="get" id="filters-form">
                            <div class="grid grid-cols-2 lg:grid-cols-7 gap-2 items-end">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Agrupar por</label>
                                    <select name="group" class="w-full text-sm border border-gray-300 rounded px-2 py-1.5" onchange="this.form.submit()">
                                        <option value="city" <?= $groupBy == 'city' ? 'selected' : '' ?>>Ciudad</option>
                                        <option value="store" <?= $groupBy == 'store' ? 'selected' : '' ?>>Tienda</option>
                                        <option value="vendor" <?= $groupBy == 'vendor' ? 'selected' : '' ?>>Vendedor</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Tienda</label>
                                    <select name="store" class="w-full text-sm border border-gray-300 rounded px-2 py-1.5">
                                        <option value="-1">Todas</option>
                                        <?php foreach($stores as $s): ?>
                                        <option value="<?= $s->idStore ?>" <?= $s->idStore == $store ? 'selected' : '' ?>><?= $s->name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Vendedor</label>
                                    <select name="vendor" class="w-full text-sm border border-gray-300 rounded px-2 py-1.5">
                                        <option value="">Todos</option>
                                        <?php foreach($vendors as $v): ?>
                                        <option value="<?= $v->idUser ?>" <?= $v->idUser == $vendorFilter ? 'selected' : '' ?>><?= $v->name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Cliente</label>
                                    <select name="client" class="w-full text-sm border border-gray-300 rounded px-2 py-1.5">
                                        <option value="">Todos</option>
                                        <?php foreach($clients as $cl): ?>
                                        <option value="<?= $cl->idClient ?>" <?= $cl->idClient == $clientFilter ? 'selected' : '' ?>><?= $cl->name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Monto minimo</label>
                                    <input type="number" name="min" value="<?= $minAmount ?>" placeholder="0" class="w-full text-sm border border-gray-300 rounded px-2 py-1.5">
                                </div>
                                <div>
                                    <label class="flex items-center text-xs text-gray-600 mt-4">
                                        <input type="checkbox" name="overdue" value="1" <?= $onlyOverdue ? 'checked' : '' ?> class="mr-1.5">
                                        Solo +90 dias
                                    </label>
                                </div>
                                <div class="flex gap-1">
                                    <button type="submit" class="flex-1 px-3 py-1.5 text-sm text-white rounded" style="background:#2E7D91;">Filtrar</button>
                                    <a href="<?= base_url() ?>sisvent/admin/reports/debtByCity" class="px-3 py-1.5 text-sm text-gray-600 bg-gray-100 rounded hover:bg-gray-200">X</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- KPI Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-600 p-4">
                            <p class="text-xs text-gray-500 uppercase">Cartera Total</p>
                            <p class="text-xl font-bold text-blue-700">$<?= number_format($grandDebt, 0, ',', '.') ?></p>
                            <p class="text-xs text-gray-400"><?= $grandClients ?> clientes / <?= $grandInvoices ?> facturas</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 <?= $pct90 = $pctOver90; $pctOver90 > 40 ? 'border-red-600' : 'border-orange-500' ?> p-4">
                            <p class="text-xs text-gray-500 uppercase">Cartera +90 dias</p>
                            <p class="text-xl font-bold text-red-700">$<?= number_format($grandOver90, 0, ',', '.') ?></p>
                            <p class="text-xs <?= $pctOver90 > 40 ? 'text-red-500 font-bold' : 'text-gray-400' ?>"><?= number_format($pctOver90, 1) ?>% del total</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-4">
                            <p class="text-xs text-gray-500 uppercase">% Cobro General</p>
                            <p class="text-xl font-bold <?= $pctCobro >= 50 ? 'text-green-600' : 'text-orange-600' ?>"><?= number_format($pctCobro, 1) ?>%</p>
                            <p class="text-xs text-gray-400">$<?= number_format($grandPaid, 0, ',', '.') ?> cobrado</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">DSO</p>
                            <p class="text-xl font-bold text-gray-700"><?= $dso ?> dias</p>
                            <p class="text-xs text-gray-400">Dias promedio de cobro</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">Distribucion</p>
                            <div class="flex rounded overflow-hidden h-4 mt-2">
                                <?php if($grand030 > 0): ?><div class="bg-green-500" style="width:<?= $grandDebt > 0 ? ($grand030/$grandDebt)*100 : 0 ?>%" title="0-30d"></div><?php endif; ?>
                                <?php if($grand3160 > 0): ?><div class="bg-yellow-500" style="width:<?= $grandDebt > 0 ? ($grand3160/$grandDebt)*100 : 0 ?>%" title="31-60d"></div><?php endif; ?>
                                <?php if($grand6190 > 0): ?><div class="bg-orange-500" style="width:<?= $grandDebt > 0 ? ($grand6190/$grandDebt)*100 : 0 ?>%" title="61-90d"></div><?php endif; ?>
                                <?php if($grandOver90 > 0): ?><div class="bg-red-600" style="width:<?= $grandDebt > 0 ? ($grandOver90/$grandDebt)*100 : 0 ?>%" title="+90d"></div><?php endif; ?>
                            </div>
                            <div class="flex justify-between text-xs text-gray-400 mt-1">
                                <span class="text-green-600"><?= $grandDebt > 0 ? number_format(($grand030/$grandDebt)*100, 0) : 0 ?>%</span>
                                <span class="text-yellow-600"><?= $grandDebt > 0 ? number_format(($grand3160/$grandDebt)*100, 0) : 0 ?>%</span>
                                <span class="text-orange-600"><?= $grandDebt > 0 ? number_format(($grand6190/$grandDebt)*100, 0) : 0 ?>%</span>
                                <span class="text-red-600"><?= number_format($pctOver90, 0) ?>%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="px-4 py-2.5 border-b flex items-center justify-between">
                            <h3 class="font-semibold text-gray-700 text-sm">Detalle por <?= $groupLabel ?> / Vendedor</h3>
                            <span class="text-xs text-gray-400"><?= count($groups) ?> <?= strtolower($groupLabel) ?>s con cartera</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 text-left font-semibold" style="min-width:200px;"><?= $groupLabel ?> / Detalle</th>
                                        <th class="px-3 py-2.5 text-center font-semibold">Clientes</th>
                                        <th class="px-3 py-2.5 text-center font-semibold">Fact.</th>
                                        <th class="px-3 py-2.5 text-right font-semibold">Facturado</th>
                                        <th class="px-3 py-2.5 text-right font-semibold">Cobrado</th>
                                        <th class="px-3 py-2.5 text-right font-semibold">% Cobro</th>
                                        <th class="px-3 py-2.5 text-right font-semibold">Cartera</th>
                                        <th class="px-3 py-2.5 text-right font-semibold">% Total</th>
                                        <th class="px-3 py-2.5 text-right font-semibold" style="background:#1a4080;">0-30d</th>
                                        <th class="px-3 py-2.5 text-right font-semibold" style="background:#1a4080;">31-60d</th>
                                        <th class="px-3 py-2.5 text-right font-semibold" style="background:#1a4080;">61-90d</th>
                                        <th class="px-3 py-2.5 text-right font-semibold" style="background:#8b0000;">+90d</th>
                                        <th class="px-3 py-2.5 text-right font-semibold" style="background:#8b0000;">% +90d</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($groups as $g): ?>
                                    <?php $gPct90 = $g['total_debt'] > 0 ? ($g['debt_over_90'] / $g['total_debt']) * 100 : 0; ?>
                                    <tr class="border-t-2 border-gray-300 font-bold cursor-pointer group-row" style="background:#e8edf3;" onclick="$(this).nextUntil('.group-row').toggle();">
                                        <td class="px-3 py-2.5 text-gray-800 text-sm">
                                            <span class="mr-1 text-gray-400">&#9660;</span>
                                            <?= strtoupper($g['name']) ?>
                                        </td>
                                        <td class="px-3 py-2.5 text-center"><?= $g['client_count'] ?></td>
                                        <td class="px-3 py-2.5 text-center"><?= $g['invoice_count'] ?></td>
                                        <td class="px-3 py-2.5 text-right">$<?= number_format($g['total_invoiced'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right text-green-700">$<?= number_format($g['total_paid'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right">
                                            <?php $pc = $g['total_invoiced'] > 0 ? ($g['total_paid']/$g['total_invoiced'])*100 : 0; ?>
                                            <span class="<?= $pc >= 50 ? 'text-green-600' : 'text-red-600' ?>"><?= number_format($pc, 1) ?>%</span>
                                        </td>
                                        <td class="px-3 py-2.5 text-right text-red-600 text-sm">$<?= number_format($g['total_debt'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right"><?= $grandDebt > 0 ? number_format(($g['total_debt']/$grandDebt)*100, 1) : 0 ?>%</td>
                                        <td class="px-3 py-2.5 text-right text-blue-600">$<?= number_format($g['debt_0_30'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right text-yellow-600">$<?= number_format($g['debt_31_60'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right text-orange-600">$<?= number_format($g['debt_61_90'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right text-red-700" style="<?= $g['debt_over_90'] > 0 ? 'background:rgba(220,38,38,0.08)' : '' ?>">$<?= number_format($g['debt_over_90'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right font-bold" style="<?= $gPct90 > 40 ? 'background:rgba(220,38,38,0.12)' : '' ?>">
                                            <span class="<?= $gPct90 > 50 ? 'text-red-700' : ($gPct90 > 25 ? 'text-orange-600' : 'text-gray-600') ?>"><?= number_format($gPct90, 1) ?>%</span>
                                        </td>
                                    </tr>
                                    <?php foreach($g['details'] as $d):
                                        $dPct90 = $d->total_debt > 0 ? ($d->debt_over_90 / $d->total_debt) * 100 : 0;
                                        $dPcCobro = $d->total_invoiced > 0 ? ($d->total_paid / $d->total_invoiced) * 100 : 0;
                                    ?>
                                    <tr class="border-t hover:bg-blue-50 detail-row">
                                        <td class="px-3 py-1.5 pl-8 text-gray-600">
                                            <span class="text-gray-300 mr-1">&#8627;</span>
                                            <?php if($groupBy == 'vendor'): ?>
                                                <?= $d->city ?: 'Sin ciudad' ?>
                                                <span class="text-xs text-gray-400 ml-1">(<?= $d->store_name ?>)</span>
                                            <?php elseif($groupBy == 'store'): ?>
                                                <?= $d->vendor_name ?>
                                                <span class="text-xs text-gray-400 ml-1">(<?= $d->city ?: 'Sin ciudad' ?>)</span>
                                            <?php else: ?>
                                                <?= $d->vendor_name ?>
                                                <span class="text-xs text-gray-400 ml-1">(<?= $d->store_name ?>)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-1.5 text-center text-gray-500"><?= $d->client_count ?></td>
                                        <td class="px-3 py-1.5 text-center text-gray-500"><?= $d->invoice_count ?></td>
                                        <td class="px-3 py-1.5 text-right text-gray-600">$<?= number_format($d->total_invoiced, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right text-green-600">$<?= number_format($d->total_paid, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right">
                                            <span class="<?= $dPcCobro >= 50 ? 'text-green-600' : 'text-red-600' ?>"><?= number_format($dPcCobro, 1) ?>%</span>
                                        </td>
                                        <td class="px-3 py-1.5 text-right text-red-600 font-medium">$<?= number_format($d->total_debt, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right text-gray-500"><?= $grandDebt > 0 ? number_format(($d->total_debt/$grandDebt)*100, 1) : 0 ?>%</td>
                                        <td class="px-3 py-1.5 text-right <?= $d->debt_0_30 > 0 ? 'text-blue-600' : 'text-gray-300' ?>">$<?= number_format($d->debt_0_30, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right <?= $d->debt_31_60 > 0 ? 'text-yellow-600' : 'text-gray-300' ?>">$<?= number_format($d->debt_31_60, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right <?= $d->debt_61_90 > 0 ? 'text-orange-600' : 'text-gray-300' ?>">$<?= number_format($d->debt_61_90, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right <?= $d->debt_over_90 > 0 ? 'text-red-700 font-semibold' : 'text-gray-300' ?>" style="<?= $d->debt_over_90 > 0 ? 'background:rgba(220,38,38,0.05)' : '' ?>">$<?= number_format($d->debt_over_90, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-right" style="<?= $dPct90 > 40 ? 'background:rgba(220,38,38,0.08)' : '' ?>">
                                            <span class="<?= $dPct90 > 50 ? 'text-red-700 font-bold' : 'text-gray-500' ?>"><?= number_format($dPct90, 1) ?>%</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1B365D; color:white;" class="font-bold text-xs">
                                        <td class="px-3 py-3">TOTAL (<?= count($groups) ?> <?= strtolower($groupLabel) ?>s)</td>
                                        <td class="px-3 py-3 text-center"><?= number_format($grandClients) ?></td>
                                        <td class="px-3 py-3 text-center"><?= number_format($grandInvoices) ?></td>
                                        <td class="px-3 py-3 text-right">$<?= number_format($grandInvoiced, 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right">$<?= number_format($grandPaid, 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right"><?= number_format($pctCobro, 1) ?>%</td>
                                        <td class="px-3 py-3 text-right text-sm">$<?= number_format($grandDebt, 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right">100%</td>
                                        <td class="px-3 py-3 text-right">$<?= number_format($grand030, 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right">$<?= number_format($grand3160, 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right">$<?= number_format($grand6190, 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right" style="background:#8b0000;">$<?= number_format($grandOver90, 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right" style="background:#8b0000;"><?= number_format($pctOver90, 1) ?>%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <p class="text-xs text-gray-400 mt-2">DSO = Dias promedio de cobro. Clic en grupo para expandir/colapsar. Cartera = facturas con saldo pendiente.</p>

                </div>
            </main>
        </div>
    </div>
</body>
</html>
