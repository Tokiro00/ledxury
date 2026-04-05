<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$pctA = $totalRevenue > 0 ? ($sumA / $totalRevenue) * 100 : 0;
$pctB = $totalRevenue > 0 ? ($sumB / $totalRevenue) * 100 : 0;
$pctC = $totalRevenue > 0 ? ($sumC / $totalRevenue) * 100 : 0;
$pctCobro = $totalRevenue > 0 ? ($totalPaid / $totalRevenue) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
    <title>Analisis Clientes ABC</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <!-- Header -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Analisis de Clientes ABC</h2>
                            <p class="text-sm text-gray-500">Clasificacion por volumen de compra + Antigüedad de cartera</p>
                        </div>
                        <form method="get" class="flex flex-wrap items-center gap-2 mt-3 lg:mt-0">
                            <select name="year" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="">Todo el historico</option>
                                <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <select name="store" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="-1">Todas las bodegas</option>
                                <?php foreach($stores as $s): ?>
                                <option value="<?= $s->idStore ?>" <?= $s->idStore == $store ? 'selected' : '' ?>><?= $s->name ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="px-4 py-2 text-sm text-white rounded-lg" style="background:#2E7D91;">Filtrar</button>
                        </form>
                    </div>

                    <!-- Row 1: ABC Classification -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Clientes</p>
                            <p class="text-xl font-bold text-gray-800 mt-1"><?= number_format($totalClients) ?></p>
                            <p class="text-xs text-gray-400 mt-1">$<?= number_format($totalRevenue, 0, ',', '.') ?> facturado</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-4">
                            <div class="flex items-center justify-between">
                                <p class="text-xs text-gray-500 uppercase">Clase A</p>
                                <span class="px-1.5 py-0.5 text-xs font-bold rounded bg-green-100 text-green-800">80%</span>
                            </div>
                            <p class="text-xl font-bold text-green-700 mt-1"><?= $countA ?></p>
                            <p class="text-xs text-gray-400">$<?= number_format($sumA, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-yellow-500 p-4">
                            <div class="flex items-center justify-between">
                                <p class="text-xs text-gray-500 uppercase">Clase B</p>
                                <span class="px-1.5 py-0.5 text-xs font-bold rounded bg-yellow-100 text-yellow-800">15%</span>
                            </div>
                            <p class="text-xl font-bold text-yellow-700 mt-1"><?= $countB ?></p>
                            <p class="text-xs text-gray-400">$<?= number_format($sumB, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-4">
                            <div class="flex items-center justify-between">
                                <p class="text-xs text-gray-500 uppercase">Clase C</p>
                                <span class="px-1.5 py-0.5 text-xs font-bold rounded bg-red-100 text-red-800">5%</span>
                            </div>
                            <p class="text-xl font-bold text-red-700 mt-1"><?= $countC ?></p>
                            <p class="text-xs text-gray-400">$<?= number_format($sumC, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Row 2: Cartera -->
                    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">Cartera Total</p>
                            <p class="text-lg font-bold text-red-600 mt-1">$<?= number_format($totalDebt, 0, ',', '.') ?></p>
                            <p class="text-xs text-gray-400"><?= number_format(100 - $pctCobro, 1) ?>% sin cobrar</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase">Cobrado</p>
                            <p class="text-lg font-bold text-green-600 mt-1">$<?= number_format($totalPaid, 0, ',', '.') ?></p>
                            <p class="text-xs text-gray-400"><?= number_format($pctCobro, 1) ?>%</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-400 p-4">
                            <p class="text-xs text-gray-500 uppercase">0-30 dias</p>
                            <p class="text-lg font-bold text-blue-600 mt-1">$<?= number_format($totalDebt030, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-yellow-400 p-4">
                            <p class="text-xs text-gray-500 uppercase">31-60 dias</p>
                            <p class="text-lg font-bold text-yellow-600 mt-1">$<?= number_format($totalDebt3160, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-orange-500 p-4">
                            <p class="text-xs text-gray-500 uppercase">61-90 dias</p>
                            <p class="text-lg font-bold text-orange-600 mt-1">$<?= number_format($totalDebt6190, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-600 p-4">
                            <p class="text-xs text-gray-500 uppercase">+90 dias</p>
                            <p class="text-lg font-bold text-red-700 mt-1">$<?= number_format($totalDebtOver90, 0, ',', '.') ?></p>
                            <?php $pct90 = $totalDebt > 0 ? ($totalDebtOver90 / $totalDebt) * 100 : 0; ?>
                            <p class="text-xs text-red-500 font-semibold"><?= number_format($pct90, 1) ?>% de cartera</p>
                        </div>
                    </div>

                    <!-- Distribution Bar -->
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs text-gray-500 uppercase">Distribucion de Ingresos (Pareto)</p>
                            <p class="text-xs text-gray-400"><?= $countA ?> clientes = <?= number_format($pctA, 0) ?>% del ingreso</p>
                        </div>
                        <div class="flex rounded-lg overflow-hidden h-6">
                            <?php if($pctA > 0): ?>
                            <div class="flex items-center justify-center text-xs font-bold text-white" style="width:<?= $pctA ?>%; background:#38a169;">A: <?= number_format($pctA, 0) ?>%</div>
                            <?php endif; ?>
                            <?php if($pctB > 0): ?>
                            <div class="flex items-center justify-center text-xs font-bold text-gray-800" style="width:<?= $pctB ?>%; background:#ecc94b;">B: <?= number_format($pctB, 0) ?>%</div>
                            <?php endif; ?>
                            <?php if($pctC > 0): ?>
                            <div class="flex items-center justify-center text-xs font-bold text-white" style="width:<?= max($pctC, 5) ?>%; background:#e53e3e;">C</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="px-4 py-2.5 border-b flex items-center justify-between">
                            <h3 class="font-semibold text-gray-700 text-sm">Detalle por Cliente</h3>
                            <input type="text" id="search-abc" placeholder="Buscar cliente..." class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:border-blue-500 w-64">
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs" id="abc-table">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-2 py-2.5 font-semibold">#</th>
                                        <th class="px-2 py-2.5 font-semibold">Clase</th>
                                        <th class="px-2 py-2.5 font-semibold" style="min-width:180px;">Cliente</th>
                                        <th class="px-2 py-2.5 font-semibold">Documento</th>
                                        <th class="px-2 py-2.5 font-semibold">Ciudad</th>
                                        <th class="px-2 py-2.5 font-semibold">Vendedor</th>
                                        <th class="px-2 py-2.5 font-semibold text-center">Fact.</th>
                                        <th class="px-2 py-2.5 font-semibold text-right">Total Compras</th>
                                        <th class="px-2 py-2.5 font-semibold text-right">Pagado</th>
                                        <th class="px-2 py-2.5 font-semibold text-right">Saldo Total</th>
                                        <th class="px-2 py-2.5 font-semibold text-right" style="background:#1a4080;">0-30d</th>
                                        <th class="px-2 py-2.5 font-semibold text-right" style="background:#1a4080;">31-60d</th>
                                        <th class="px-2 py-2.5 font-semibold text-right" style="background:#1a4080;">61-90d</th>
                                        <th class="px-2 py-2.5 font-semibold text-right" style="background:#8b0000;">+90d</th>
                                        <th class="px-2 py-2.5 font-semibold text-right">% Ing.</th>
                                        <th class="px-2 py-2.5 font-semibold text-right">% Acum.</th>
                                        <th class="px-2 py-2.5 font-semibold text-right">Ticket</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $n = 0;
                                    $tCompras = 0; $tPagado = 0; $tSaldo = 0; $t030 = 0; $t3160 = 0; $t6190 = 0; $t90 = 0; $tFact = 0;
                                    foreach($clients as $c):
                                        $n++;
                                        $tCompras += $c->total_purchases;
                                        $tPagado += $c->total_paid;
                                        $tSaldo += $c->total_debt;
                                        $t030 += $c->debt_0_30;
                                        $t3160 += $c->debt_31_60;
                                        $t6190 += $c->debt_61_90;
                                        $t90 += $c->debt_over_90;
                                        $tFact += $c->invoice_count;
                                    ?>
                                    <tr class="border-t hover:bg-blue-50 transition-colors abc-row <?= $n % 2 == 0 ? 'bg-gray-50' : '' ?>">
                                        <td class="px-2 py-1.5 text-gray-400"><?= $n ?></td>
                                        <td class="px-2 py-1.5">
                                            <?php if($c->classification == 'A'): ?>
                                            <span class="inline-block w-5 h-5 text-center leading-5 text-xs font-bold rounded-full bg-green-100 text-green-800">A</span>
                                            <?php elseif($c->classification == 'B'): ?>
                                            <span class="inline-block w-5 h-5 text-center leading-5 text-xs font-bold rounded-full bg-yellow-100 text-yellow-800">B</span>
                                            <?php else: ?>
                                            <span class="inline-block w-5 h-5 text-center leading-5 text-xs font-bold rounded-full bg-red-100 text-red-800">C</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-2 py-1.5 font-medium text-gray-800"><?= $c->client_name ?></td>
                                        <td class="px-2 py-1.5 text-gray-500"><?= $c->idNum ?></td>
                                        <td class="px-2 py-1.5 text-gray-500"><?= $c->city ?></td>
                                        <td class="px-2 py-1.5 text-gray-500"><?= $c->vendor_name ?></td>
                                        <td class="px-2 py-1.5 text-center"><?= $c->invoice_count ?></td>
                                        <td class="px-2 py-1.5 text-right font-semibold text-gray-800">$<?= number_format($c->total_purchases, 0, ',', '.') ?></td>
                                        <td class="px-2 py-1.5 text-right text-green-700">$<?= number_format($c->total_paid, 0, ',', '.') ?></td>
                                        <td class="px-2 py-1.5 text-right <?= $c->total_debt > 0 ? 'text-red-600 font-semibold' : 'text-gray-400' ?>">$<?= number_format($c->total_debt, 0, ',', '.') ?></td>
                                        <td class="px-2 py-1.5 text-right <?= $c->debt_0_30 > 0 ? 'text-blue-600' : 'text-gray-300' ?>">$<?= number_format($c->debt_0_30, 0, ',', '.') ?></td>
                                        <td class="px-2 py-1.5 text-right <?= $c->debt_31_60 > 0 ? 'text-yellow-600' : 'text-gray-300' ?>">$<?= number_format($c->debt_31_60, 0, ',', '.') ?></td>
                                        <td class="px-2 py-1.5 text-right <?= $c->debt_61_90 > 0 ? 'text-orange-600 font-semibold' : 'text-gray-300' ?>">$<?= number_format($c->debt_61_90, 0, ',', '.') ?></td>
                                        <td class="px-2 py-1.5 text-right <?= $c->debt_over_90 > 0 ? 'text-red-700 font-bold' : 'text-gray-300' ?>" style="<?= $c->debt_over_90 > 0 ? 'background:rgba(220,38,38,0.05)' : '' ?>">$<?= number_format($c->debt_over_90, 0, ',', '.') ?></td>
                                        <td class="px-2 py-1.5 text-right text-gray-600"><?= number_format($c->individual_pct, 2) ?>%</td>
                                        <td class="px-2 py-1.5 text-right text-gray-600"><?= number_format($c->accumulated_pct, 1) ?>%</td>
                                        <td class="px-2 py-1.5 text-right text-gray-600">$<?= number_format($c->avg_ticket, 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1B365D; color:white;" class="font-bold text-xs">
                                        <td class="px-2 py-2.5" colspan="6">TOTALES (<?= $totalClients ?> clientes)</td>
                                        <td class="px-2 py-2.5 text-center"><?= number_format($tFact) ?></td>
                                        <td class="px-2 py-2.5 text-right">$<?= number_format($tCompras, 0, ',', '.') ?></td>
                                        <td class="px-2 py-2.5 text-right">$<?= number_format($tPagado, 0, ',', '.') ?></td>
                                        <td class="px-2 py-2.5 text-right">$<?= number_format($tSaldo, 0, ',', '.') ?></td>
                                        <td class="px-2 py-2.5 text-right">$<?= number_format($t030, 0, ',', '.') ?></td>
                                        <td class="px-2 py-2.5 text-right">$<?= number_format($t3160, 0, ',', '.') ?></td>
                                        <td class="px-2 py-2.5 text-right">$<?= number_format($t6190, 0, ',', '.') ?></td>
                                        <td class="px-2 py-2.5 text-right" style="background:#8b0000;">$<?= number_format($t90, 0, ',', '.') ?></td>
                                        <td class="px-2 py-2.5 text-right">100%</td>
                                        <td class="px-2 py-2.5 text-right">-</td>
                                        <td class="px-2 py-2.5 text-right">$<?= $tFact > 0 ? number_format($tCompras / $tFact, 0, ',', '.') : 0 ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <p class="text-xs text-gray-400 mt-2">Clase A: 80% del ingreso. B: siguiente 15%. C: ultimo 5%. Cartera por antigüedad desde fecha de factura.</p>

                </div>
            </main>
        </div>
    </div>

<script>
$(document).on('keyup', '#search-abc', function() {
    var val = $(this).val().toLowerCase();
    $('.abc-row').each(function() {
        var text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(val) > -1);
    });
});
</script>

<?php $this->load->view("sisvent/layouts/footer"); ?>
</body>
</html>
