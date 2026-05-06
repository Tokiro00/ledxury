<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return ($n >= 0 ? '' : '-') . '$' . number_format(abs((float)$n), 0, ',', '.'); };
$typeLabels = array(
    'liquidacion'        => array('label' => 'Liquidación',       'icon' => '💰', 'cls' => 'bg-green-100 text-green-700'),
    'vale'               => array('label' => 'Vale',              'icon' => '📄', 'cls' => 'bg-gray-100 text-gray-700'),
    'anticipo'           => array('label' => 'Anticipo',          'icon' => '💸', 'cls' => 'bg-yellow-100 text-yellow-800'),
    'cruce_anticipo'     => array('label' => 'Cruce anticipo',    'icon' => '✂️', 'cls' => 'bg-orange-100 text-orange-700'),
    'abono_empleado'     => array('label' => 'Abono',             'icon' => '💵', 'cls' => 'bg-blue-100 text-blue-700'),
    'comision_pendiente' => array('label' => 'Comisión ganada',   'icon' => '🎯', 'cls' => 'bg-emerald-100 text-emerald-700'),
);
?>
<!DOCTYPE html>
<html lang="es">
<title>Estado de cuenta — <?= htmlspecialchars($vendor->name) ?></title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/settlements/list', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-4 w-full max-w-screen-xl mx-auto">

                <!-- Header compacto -->
                <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                    <div>
                        <p class="text-xxs text-gray-400 uppercase tracking-wider">Estado de cuenta</p>
                        <h2 class="text-lg font-semibold text-gray-700"><?= htmlspecialchars($vendor->name) ?></h2>
                        <p class="text-xs text-gray-400">id: <?= htmlspecialchars($vendor->idUser) ?></p>
                    </div>
                    <a href="<?= base_url() ?>sisvent/admin/settlements" class="text-sm text-mam-blue-petroleo hover:underline">&larr; Volver a Liquidaciones</a>
                </div>

                <!-- Filtro + acciones inline -->
                <form method="GET" class="bg-white rounded-lg shadow-xs p-3 mb-3 flex items-center gap-3 flex-wrap">
                    <span class="text-xxs font-bold text-gray-500 uppercase">Rango:</span>
                    <label class="flex items-center gap-1 text-xs text-gray-600">Desde
                        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="px-2 py-1 border rounded text-xs">
                    </label>
                    <label class="flex items-center gap-1 text-xs text-gray-600">Hasta
                        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="px-2 py-1 border rounded text-xs">
                    </label>
                    <button type="submit" class="px-3 py-1 text-xs font-bold text-white bg-mam-blue-petroleo rounded">Filtrar</button>
                    <?php
                        $today = date('Y-m-d');
                        $base = base_url() . 'sisvent/admin/settlements/statement/' . urlencode($vendor->idUser);
                    ?>
                    <a href="<?= $base ?>?from=<?= date('Y-m-21', strtotime('-1 month')) ?>&to=<?= date('Y-m-20') ?>" class="text-xs text-gray-500 hover:text-gray-700">Ciclo 21–20</a>
                    <a href="<?= $base ?>?from=<?= date('Y-m-01') ?>&to=<?= $today ?>" class="text-xs text-gray-500 hover:text-gray-700">Este mes</a>
                    <a href="<?= $base ?>?from=<?= date('Y-01-01') ?>&to=<?= $today ?>" class="text-xs text-gray-500 hover:text-gray-700">Este año</a>
                    <div class="ml-auto flex gap-2">
                        <button type="button" onclick="window.print()" class="px-3 py-1 text-xs text-gray-600 border border-gray-300 hover:bg-gray-100 rounded">Imprimir</button>
                        <a href="<?= base_url() ?>sisvent/admin/advances/add?employee_id=<?= urlencode($vendor->idUser) ?>"
                           class="px-3 py-1 text-xs font-bold text-white bg-yellow-600 hover:bg-yellow-700 rounded">+ Anticipo</a>
                        <?php if ($current_commission > 0): ?>
                        <a href="<?= base_url() ?>sisvent/admin/settlements/calculate/<?= urlencode($vendor->idUser) ?>"
                           class="px-3 py-1 text-xs font-bold text-white bg-mam-blue-petroleo hover:bg-blue-900 rounded"
                           onclick="showSureModal(event,this,'Calcular liquidación. Después podrás revisarla y pagar o descartar.')">+ Liquidar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- 3 KPIs con border-left estilo Lumen -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                    <div class="bg-white rounded-lg shadow-xs p-3 border-l-4 border-green-500">
                        <p class="text-xxs text-gray-500 uppercase">Comisión liquidable</p>
                        <p class="text-xl font-bold text-green-700 mt-0.5">$<?= number_format($current_commission, 0, ',', '.') ?></p>
                    </div>
                    <div class="bg-white rounded-lg shadow-xs p-3 border-l-4 border-yellow-500">
                        <p class="text-xxs text-gray-500 uppercase">Anticipos pendientes</p>
                        <p class="text-xl font-bold text-yellow-700 mt-0.5">$<?= number_format($current_advances, 0, ',', '.') ?></p>
                    </div>
                    <div class="bg-white rounded-lg shadow-xs p-3 border-l-4 <?= $current_balance >= 0 ? 'border-green-600' : 'border-red-600' ?>">
                        <p class="text-xxs text-gray-500 uppercase">Saldo neto</p>
                        <p class="text-2xl font-bold <?= $current_balance >= 0 ? 'text-green-700' : 'text-red-600' ?> mt-0.5"><?= $fmt($current_balance) ?></p>
                        <p class="text-xxs text-gray-400 mt-0.5"><?= $current_balance >= 0 ? 'Empresa debe al vendedor' : 'Vendedor debe a empresa' ?></p>
                    </div>
                </div>

                <!-- Tabla de movimientos -->
                <div class="bg-white rounded-lg shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs whitespace-no-wrap">
                            <thead>
                                <tr class="text-xxs font-semibold text-left text-gray-500 uppercase border-b bg-gray-50">
                                    <th class="px-3 py-2">Fecha</th>
                                    <th class="px-3 py-2">Tipo</th>
                                    <th class="px-3 py-2">Ref</th>
                                    <th class="px-3 py-2">Concepto</th>
                                    <th class="px-3 py-2 text-right">Factura</th>
                                    <th class="px-3 py-2 text-right">Flete</th>
                                    <th class="px-3 py-2 text-center">%</th>
                                    <th class="px-3 py-2 text-right">Entregado</th>
                                    <th class="px-3 py-2 text-right">Ganado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">Sin movimientos en el período.</td></tr>
                                <?php else:
                                    $totEntregado = 0; $totGanado = 0; $totFactura = 0; $totFlete = 0;
                                    foreach ($rows as $r):
                                        $tl = $typeLabels[$r->tipo] ?? array('label' => $r->tipo, 'icon' => '•', 'cls' => 'bg-gray-100 text-gray-700');
                                        $totEntregado += (float)$r->debito;
                                        $totGanado    += (float)$r->credito;
                                        $invoiceTotal = isset($r->invoice_total) ? (float)$r->invoice_total : 0;
                                        $fleteVal     = isset($r->flete) ? (float)$r->flete : 0;
                                        $pctVal       = isset($r->percentage) ? (float)$r->percentage : 0;
                                        $totFactura  += $invoiceTotal;
                                        $totFlete    += $fleteVal;
                                ?>
                                <tr class="text-gray-700 hover:bg-gray-50">
                                    <td class="px-3 py-1.5 text-gray-500"><?= date('d/m/Y', strtotime($r->fecha)) ?></td>
                                    <td class="px-3 py-1.5"><span class="inline-flex items-center gap-1 px-2 py-0.5 text-xxs font-semibold rounded-full <?= $tl['cls'] ?>"><?= $tl['icon'] ?> <?= $tl['label'] ?></span></td>
                                    <td class="px-3 py-1.5 font-mono text-mam-blue-petroleo"><?= htmlspecialchars($r->code) ?></td>
                                    <td class="px-3 py-1.5 text-gray-600" style="max-width:320px; word-break:break-word;"><?= htmlspecialchars($r->concepto) ?></td>
                                    <td class="px-3 py-1.5 text-right <?= $invoiceTotal > 0 ? 'text-gray-700' : 'text-gray-300' ?>">
                                        <?= $invoiceTotal > 0 ? '$' . number_format($invoiceTotal, 0, ',', '.') : '—' ?>
                                    </td>
                                    <td class="px-3 py-1.5 text-right <?= $fleteVal > 0 ? 'text-yellow-700' : 'text-gray-300' ?>">
                                        <?= $fleteVal > 0 ? '$' . number_format($fleteVal, 0, ',', '.') : '—' ?>
                                    </td>
                                    <td class="px-3 py-1.5 text-center <?= $pctVal > 0 ? 'font-semibold text-mam-blue-petroleo' : 'text-gray-300' ?>">
                                        <?= $pctVal > 0 ? number_format($pctVal, 1, '.', '') . '%' : '—' ?>
                                    </td>
                                    <td class="px-3 py-1.5 text-right <?= $r->debito > 0 ? 'font-bold text-red-600' : 'text-gray-300' ?>">
                                        <?= $r->debito > 0 ? '$' . number_format($r->debito, 0, ',', '.') : '—' ?>
                                    </td>
                                    <td class="px-3 py-1.5 text-right <?= $r->credito > 0 ? 'font-bold text-green-600' : 'text-gray-300' ?>">
                                        <?= $r->credito > 0 ? '$' . number_format($r->credito, 0, ',', '.') : '—' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="bg-gray-50 font-bold border-t-2">
                                    <td colspan="4" class="px-3 py-2 text-right text-gray-700">Totales del período:</td>
                                    <td class="px-3 py-2 text-right text-gray-700"><?= $totFactura > 0 ? '$' . number_format($totFactura, 0, ',', '.') : '—' ?></td>
                                    <td class="px-3 py-2 text-right text-yellow-700"><?= $totFlete > 0 ? '$' . number_format($totFlete, 0, ',', '.') : '—' ?></td>
                                    <td class="px-3 py-2 text-center text-gray-300">—</td>
                                    <td class="px-3 py-2 text-right text-red-600">$<?= number_format($totEntregado, 0, ',', '.') ?></td>
                                    <td class="px-3 py-2 text-right text-green-600">$<?= number_format($totGanado, 0, ',', '.') ?></td>
                                </tr>
                                <tr class="bg-white border-t-2 <?= $current_balance >= 0 ? 'border-green-500' : 'border-red-500' ?>">
                                    <td colspan="8" class="px-3 py-2 text-right font-bold text-gray-700 uppercase text-xs">Saldo neto del vendedor:</td>
                                    <td class="px-3 py-2 text-right font-bold text-base <?= $current_balance >= 0 ? 'text-green-700' : 'text-red-600' ?>"><?= $fmt($current_balance) ?></td>
                                </tr>
                                <?php endif; ?>
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
