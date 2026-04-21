<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$grandDebt = $aging['total'];
$grandOver90 = $aging['days_91_plus'];
$pct90 = $grandDebt > 0 ? ($grandOver90 / $grandDebt) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
    <title>Cartera por Tienda</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Cartera por Tienda y Vendedor</h2>
                            <p class="text-sm text-gray-500">Vista completa de cartera pendiente agrupada por bodega</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?= base_url() ?>sisvent/admin/accountsreceivable" class="px-3 py-2 text-sm bg-white border rounded-lg hover:bg-gray-50">Por Factura</a>
                            <a href="<?= base_url() ?>sisvent/admin/accountsreceivable/byClient" class="px-3 py-2 text-sm bg-white border rounded-lg hover:bg-gray-50">Por Cliente</a>
                            <a href="<?= base_url() ?>sisvent/admin/accountsreceivable/byStore" class="px-3 py-2 text-sm text-white rounded-lg" style="background:#2E7D91;">Por Tienda</a>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-600 p-5">
                            <p class="text-xs text-gray-500 uppercase">Cartera Total</p>
                            <p class="text-2xl font-bold text-blue-700">$<?= number_format($grandDebt, 0, ',', '.') ?></p>
                            <p class="text-xs text-gray-400"><?= $aging['count_total'] ?> facturas pendientes</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 <?= $pct90 > 40 ? 'border-red-600' : 'border-orange-500' ?> p-5">
                            <p class="text-xs text-gray-500 uppercase">Cartera +90 dias</p>
                            <p class="text-2xl font-bold text-red-700">$<?= number_format($grandOver90, 0, ',', '.') ?></p>
                            <p class="text-xs <?= $pct90 > 40 ? 'text-red-500 font-bold' : 'text-gray-400' ?>"><?= number_format($pct90, 1) ?>% del total</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-5">
                            <p class="text-xs text-gray-500 uppercase">0-30 dias</p>
                            <p class="text-xl font-bold text-green-700">$<?= number_format($aging['current'], 0, ',', '.') ?></p>
                            <p class="text-xs text-gray-400"><?= $aging['count_current'] ?> facturas</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-5">
                            <p class="text-xs text-gray-500 uppercase">31-90 dias</p>
                            <p class="text-xl font-bold text-orange-600">$<?= number_format($aging['days_31_60'] + $aging['days_61_90'], 0, ',', '.') ?></p>
                            <p class="text-xs text-gray-400"><?= $aging['count_31_60'] + $aging['count_61_90'] ?> facturas</p>
                        </div>
                    </div>

                    <!-- Table by Store -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D; color:white;">
                                        <th class="px-3 py-3 text-left font-semibold" style="min-width:220px;">Tienda / Vendedor</th>
                                        <th class="px-3 py-3 text-center font-semibold">Clientes</th>
                                        <th class="px-3 py-3 text-center font-semibold">Facturas</th>
                                        <th class="px-3 py-3 text-right font-semibold">Cartera Total</th>
                                        <th class="px-3 py-3 text-right font-semibold">% del Total</th>
                                        <th class="px-3 py-3 text-right font-semibold" style="background:#1a4080;">0-30d</th>
                                        <th class="px-3 py-3 text-right font-semibold" style="background:#1a4080;">31-60d</th>
                                        <th class="px-3 py-3 text-right font-semibold" style="background:#1a4080;">61-90d</th>
                                        <th class="px-3 py-3 text-right font-semibold" style="background:#8b0000;">+90 dias</th>
                                        <th class="px-3 py-3 text-right font-semibold" style="background:#8b0000;">% +90d</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($storesData as $st): ?>
                                    <!-- Store Row -->
                                    <?php $storePct90 = $st['total_debt'] > 0 ? ($st['debt_over_90'] / $st['total_debt']) * 100 : 0; ?>
                                    <tr class="border-t-2 border-gray-300 font-bold cursor-pointer store-row" style="background:#e8edf3;" onclick="$(this).nextUntil('.store-row').toggle();">
                                        <td class="px-3 py-3 text-gray-800 text-sm">
                                            <span class="mr-1 text-gray-400 toggle-icon">&#9660;</span>
                                            <?= strtoupper($st['store_name']) ?>
                                        </td>
                                        <td class="px-3 py-3 text-center"><?= $st['client_count'] ?></td>
                                        <td class="px-3 py-3 text-center"><?= $st['invoice_count'] ?></td>
                                        <td class="px-3 py-3 text-right text-red-600 text-sm">$<?= number_format($st['total_debt'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right"><?= $grandDebt > 0 ? number_format(($st['total_debt']/$grandDebt)*100, 1) : 0 ?>%</td>
                                        <td class="px-3 py-3 text-right text-blue-600">$<?= number_format($st['debt_0_30'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right text-yellow-600">$<?= number_format($st['debt_31_60'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right text-orange-600">$<?= number_format($st['debt_61_90'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right text-red-700" style="<?= $st['debt_over_90'] > 0 ? 'background:rgba(220,38,38,0.08)' : '' ?>">$<?= number_format($st['debt_over_90'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right font-bold" style="<?= $storePct90 > 40 ? 'background:rgba(220,38,38,0.12)' : ($st['debt_over_90'] > 0 ? 'background:rgba(220,38,38,0.08)' : '') ?>">
                                            <span class="<?= $storePct90 > 50 ? 'text-red-700' : ($storePct90 > 25 ? 'text-orange-600' : 'text-gray-600') ?>"><?= number_format($storePct90, 1) ?>%</span>
                                        </td>
                                    </tr>
                                    <!-- Vendor Rows -->
                                    <?php foreach($st['vendors'] as $v):
                                        $vPct90 = $v->total_debt > 0 ? ($v->debt_over_90 / $v->total_debt) * 100 : 0;
                                    ?>
                                    <tr class="border-t hover:bg-blue-50 vendor-row">
                                        <td class="px-3 py-2 pl-8 text-gray-600">
                                            <span class="text-gray-300 mr-1">&#8627;</span>
                                            <?= $v->vendor_name ?>
                                        </td>
                                        <td class="px-3 py-2 text-center text-gray-500"><?= $v->client_count ?></td>
                                        <td class="px-3 py-2 text-center text-gray-500"><?= $v->invoice_count ?></td>
                                        <td class="px-3 py-2 text-right text-red-600 font-medium">$<?= number_format($v->total_debt, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right text-gray-500"><?= $grandDebt > 0 ? number_format(($v->total_debt/$grandDebt)*100, 1) : 0 ?>%</td>
                                        <td class="px-3 py-2 text-right <?= $v->debt_0_30 > 0 ? 'text-blue-600' : 'text-gray-300' ?>">$<?= number_format($v->debt_0_30, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right <?= $v->debt_31_60 > 0 ? 'text-yellow-600' : 'text-gray-300' ?>">$<?= number_format($v->debt_31_60, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right <?= $v->debt_61_90 > 0 ? 'text-orange-600' : 'text-gray-300' ?>">$<?= number_format($v->debt_61_90, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right <?= $v->debt_over_90 > 0 ? 'text-red-700 font-semibold' : 'text-gray-300' ?>" style="<?= $v->debt_over_90 > 0 ? 'background:rgba(220,38,38,0.05)' : '' ?>">$<?= number_format($v->debt_over_90, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right" style="<?= $vPct90 > 40 ? 'background:rgba(220,38,38,0.08)' : ($v->debt_over_90 > 0 ? 'background:rgba(220,38,38,0.05)' : '') ?>">
                                            <span class="<?= $vPct90 > 50 ? 'text-red-700 font-bold' : ($vPct90 > 25 ? 'text-orange-600' : 'text-gray-500') ?>"><?= number_format($vPct90, 1) ?>%</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1B365D; color:white;" class="font-bold">
                                        <td class="px-3 py-3">TOTAL GENERAL</td>
                                        <td class="px-3 py-3 text-center"><?= $aging['count_total'] ?></td>
                                        <td class="px-3 py-3 text-center">-</td>
                                        <td class="px-3 py-3 text-right text-sm">$<?= number_format($grandDebt, 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right">100%</td>
                                        <td class="px-3 py-3 text-right">$<?= number_format($aging['current'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right">$<?= number_format($aging['days_31_60'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right">$<?= number_format($aging['days_61_90'], 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right" style="background:#8b0000;">$<?= number_format($grandOver90, 0, ',', '.') ?></td>
                                        <td class="px-3 py-3 text-right" style="background:#8b0000;"><?= number_format($pct90, 1) ?>%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <p class="text-xs text-gray-400 mt-2">Clic en una tienda para expandir/colapsar vendedores. Cartera = facturas pendientes de pago (estado 0 o 1).</p>

                </div>
            </main>
        </div>
    </div>
<?php $this->load->view("sisvent/layouts/footer"); ?>
</body>
</html>
