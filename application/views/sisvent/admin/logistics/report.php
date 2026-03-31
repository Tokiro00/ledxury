<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];

$transportadoras = [
    'sin_despacho'    => ['label' => 'Sin despacho',    'color' => 'gray'],
    'interrapidisimo' => ['label' => 'Interrapidisimo', 'color' => 'blue'],
    'estelar'         => ['label' => 'Estelar',         'color' => 'purple'],
    'coordinadora'    => ['label' => 'Coordinadora',    'color' => 'orange'],
    'carro_mam'       => ['label' => 'Carro MAM',       'color' => 'green'],
    'moto_mam'        => ['label' => 'Moto MAM',        'color' => 'teal'],
    'particular'      => ['label' => 'Particular',      'color' => 'yellow'],
    'recoge_cliente'  => ['label' => 'Recoge cliente',   'color' => 'pink'],
];

$hoy = date('Y-m-d');
$ayer = date('Y-m-d', strtotime('-1 day'));
$lunes = date('Y-m-d', strtotime('monday this week'));

function formatMoney($v) {
    if ($v >= 1000000000) return '$' . number_format($v/1000000000, 1, '.', '') . 'B';
    if ($v >= 1000000) return '$' . number_format($v/1000000, 1, '.', '') . 'M';
    return '$' . number_format($v, 0, ',', '.');
}

function timeDiff($from, $to) {
    if (!$from || !$to) return '-';
    $diff = strtotime($to) - strtotime($from);
    if ($diff < 3600) return round($diff/60) . 'min';
    if ($diff < 86400) return round($diff/3600, 1) . 'h';
    return round($diff/86400, 1) . 'd';
}
?>
<!DOCTYPE html>
<html lang="es">
    <title>Bitacora Logistica</title>
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
                            <h2 class="text-2xl font-black text-gray-800 tracking-tight">BITACORA LOGISTICA</h2>
                            <p class="text-xs text-gray-400 uppercase tracking-widest">Control de Pedidos - Facturacion - Seguimiento - <?= $year ?></p>
                        </div>
                        <div class="flex gap-2 mt-2 lg:mt-0">
                            <a href="<?= base_url() ?>sisvent/admin/logistics/export?desde=<?= $fecha_desde ?>&hasta=<?= $fecha_hasta ?>" class="px-3 py-2 text-xs font-medium border border-gray-300 rounded-lg hover:bg-gray-100">Exportar</a>
                        </div>
                    </div>

                    <!-- KPIs Acumulado Año -->
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Total Pedidos</p>
                            <p class="text-2xl font-black text-gray-800"><?= number_format($kpis->total_pedidos, 0, ',', '.') ?></p>
                            <p class="text-xs text-gray-400">registrados en <?= $year ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Total Facturado</p>
                            <p class="text-2xl font-black text-green-600"><?= formatMoney($kpis->total_facturado) ?></p>
                            <p class="text-xs text-gray-400">acumulado ano</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-teal-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Entregados</p>
                            <p class="text-2xl font-black text-teal-600"><?= number_format($kpis->entregados, 0, ',', '.') ?> <span class="text-sm">(<?= $kpis->pct_entregados ?>%)</span></p>
                            <p class="text-xs text-gray-400">pedidos completados</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-yellow-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Pendientes</p>
                            <p class="text-2xl font-black text-yellow-600"><?= number_format($kpis->pendientes, 0, ',', '.') ?></p>
                            <p class="text-xs text-gray-400">en transito / sin entregar</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-purple-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Ticket Promedio</p>
                            <p class="text-2xl font-black text-purple-600"><?= formatMoney($kpis->ticket_promedio) ?></p>
                            <p class="text-xs text-gray-400">por factura</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-3">
                            <p class="text-xs text-gray-400 uppercase font-bold">Facturas Hoy</p>
                            <p class="text-2xl font-black text-gray-800"><?= $totals->facturas_count ?></p>
                            <p class="text-xs text-gray-400"><?= $totals->embalados ?> embalados / <?= $totals->despachados ?> despachados</p>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="bg-white rounded-lg shadow-sm border p-3 mb-4">
                        <form method="GET" class="flex flex-wrap items-end gap-3">
                            <div>
                                <label class="text-xs text-gray-500">Desde</label>
                                <input type="date" name="desde" value="<?= $fecha_desde ?>" class="block text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Hasta</label>
                                <input type="date" name="hasta" value="<?= $fecha_hasta ?>" class="block text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                            </div>
                            <!-- Botones rapidos -->
                            <a href="?desde=<?= $hoy ?>&hasta=<?= $hoy ?>" class="px-3 py-1.5 text-xs font-bold rounded-lg <?= ($fecha_desde==$hoy && $fecha_hasta==$hoy) ? 'text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>" <?= ($fecha_desde==$hoy && $fecha_hasta==$hoy) ? 'style="background:#1B365D"' : '' ?>>HOY</a>
                            <a href="?desde=<?= $ayer ?>&hasta=<?= $ayer ?>" class="px-3 py-1.5 text-xs font-bold rounded-lg <?= ($fecha_desde==$ayer && $fecha_hasta==$ayer) ? 'text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>" <?= ($fecha_desde==$ayer && $fecha_hasta==$ayer) ? 'style="background:#1B365D"' : '' ?>>Ayer</a>
                            <a href="?desde=<?= $lunes ?>&hasta=<?= $hoy ?>" class="px-3 py-1.5 text-xs font-bold rounded-lg <?= ($fecha_desde==$lunes && $fecha_hasta==$hoy) ? 'text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>" <?= ($fecha_desde==$lunes && $fecha_hasta==$hoy) ? 'style="background:#1B365D"' : '' ?>>Esta semana</a>
                            <div>
                                <label class="text-xs text-gray-500">Transportadora</label>
                                <select name="transportadora" class="block text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                                    <option value="all">Todas</option>
                                    <?php foreach($transportadoras as $k => $t): if($k=='sin_despacho') continue; ?>
                                    <option value="<?= $k ?>" <?= $transportadora==$k?'selected':'' ?>><?= $t['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Vendedor</label>
                                <select name="vendedor" class="block text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                                    <option value="all">Todos</option>
                                    <?php foreach($vendedores as $v): ?>
                                    <option value="<?= $v->idUser ?>" <?= $vendedor==$v->idUser?'selected':'' ?>><?= $v->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Bodega</label>
                                <select name="store" class="block text-sm border border-gray-300 rounded-lg px-2 py-1.5">
                                    <option value="all">Todas</option>
                                    <?php foreach($tiendas as $t): ?>
                                    <option value="<?= $t->idStore ?>" <?= $store==$t->idStore?'selected':'' ?>><?= $t->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="px-4 py-1.5 text-sm font-medium text-white rounded-lg" style="background:#1B365D;">Filtrar</button>
                            <a href="<?= base_url() ?>sisvent/admin/logistics" class="px-3 py-1.5 text-xs text-gray-500 hover:text-red-500">Limpiar</a>
                        </form>
                    </div>

                    <!-- Tabs -->
                    <div class="flex gap-1 mb-3 border-b">
                        <a href="?desde=<?=$fecha_desde?>&hasta=<?=$fecha_hasta?>&transportadora=<?=$transportadora?>&vendedor=<?=$vendedor?>&store=<?=$store?>&tab=pedidos"
                           class="px-4 py-2 text-sm font-bold <?= $tab=='pedidos' ? 'border-b-2 text-gray-800' : 'text-gray-400 hover:text-gray-600' ?>" style="<?= $tab=='pedidos'?'border-color:#1B365D':'' ?>">
                            Pedidos <span class="text-xs bg-gray-200 px-1.5 py-0.5 rounded-full"><?= $totals->pendientes_count ?></span>
                        </a>
                        <a href="?desde=<?=$fecha_desde?>&hasta=<?=$fecha_hasta?>&transportadora=<?=$transportadora?>&vendedor=<?=$vendedor?>&store=<?=$store?>&tab=facturas"
                           class="px-4 py-2 text-sm font-bold <?= $tab=='facturas' ? 'border-b-2 text-gray-800' : 'text-gray-400 hover:text-gray-600' ?>" style="<?= $tab=='facturas'?'border-color:#1B365D':'' ?>">
                            Facturas <span class="text-xs bg-gray-200 px-1.5 py-0.5 rounded-full"><?= $totals->facturas_count ?></span>
                        </a>
                    </div>

                    <!-- Tab: Pedidos Pendientes -->
                    <?php if($tab == 'pedidos'): ?>
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold">#</th>
                                        <th class="px-3 py-2.5 font-semibold">Cliente</th>
                                        <th class="px-3 py-2.5 font-semibold">Vendedor</th>
                                        <th class="px-3 py-2.5 font-semibold">Bodega</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Total</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Creado</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Estado</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Embalaje</th>
                                        <th class="px-3 py-2.5 font-semibold">Embalo</th>
                                        <th class="px-3 py-2.5 font-semibold">Destino</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($pendientes)): ?>
                                    <tr><td colspan="10" class="px-3 py-8 text-center text-gray-400">No hay pedidos pendientes</td></tr>
                                    <?php else: $i=0; foreach($pendientes as $p): $i++; ?>
                                    <tr class="border-t <?= $i%2==0?'bg-gray-50':'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-3 py-1.5 font-mono font-medium"><?= $p->idBudget ?></td>
                                        <td class="px-3 py-1.5"><?= $p->client_name ?></td>
                                        <td class="px-3 py-1.5"><?= $p->vendor_name ?></td>
                                        <td class="px-3 py-1.5"><?= $p->store_name ?></td>
                                        <td class="px-3 py-1.5 text-right font-medium">$<?= number_format($p->total, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-center">
                                            <div><?= date('d/m', strtotime($p->created_at)) ?></div>
                                            <div class="text-gray-400"><?= date('H:i', strtotime($p->created_at)) ?></div>
                                        </td>
                                        <td class="px-3 py-1.5 text-center">
                                            <?php if($p->embalado): ?>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Embalado</span>
                                            <?php else: ?>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-1.5 text-center">
                                            <?php if($p->embalado_at): ?>
                                            <div><?= date('d/m', strtotime($p->embalado_at)) ?></div>
                                            <div class="text-gray-400"><?= date('H:i', strtotime($p->embalado_at)) ?></div>
                                            <?php else: ?>-<?php endif; ?>
                                        </td>
                                        <td class="px-3 py-1.5"><?= $p->embalador_name ?: '-' ?></td>
                                        <td class="px-3 py-1.5"><?= $p->client_city ?: '-' ?></td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Tab: Facturas -->
                    <?php if($tab == 'facturas'): ?>

                    <!-- Resumen por día -->
                    <?php
                    $byDay = array();
                    foreach ($facturas as $f) {
                        $day = date('Y-m-d', strtotime($f->invoice_created));
                        if (!isset($byDay[$day])) $byDay[$day] = array('count' => 0, 'total' => 0);
                        $byDay[$day]['count']++;
                        $byDay[$day]['total'] += (float)$f->invoice_total;
                    }
                    krsort($byDay);
                    ?>
                    <?php if(count($byDay) > 1): ?>
                    <div class="bg-white rounded-lg shadow-sm border p-3 mb-4">
                        <p class="text-xs font-bold text-gray-500 uppercase mb-2">Resumen por dia</p>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left border-b">
                                        <th class="px-2 py-1.5 font-semibold text-gray-600">Dia</th>
                                        <th class="px-2 py-1.5 font-semibold text-gray-600 text-center">Facturas</th>
                                        <th class="px-2 py-1.5 font-semibold text-gray-600 text-right">Total Facturado</th>
                                        <th class="px-2 py-1.5 font-semibold text-gray-600 text-right">Promedio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($byDay as $day => $d): ?>
                                    <tr class="border-t hover:bg-blue-50">
                                        <td class="px-2 py-1.5 font-medium"><?= date('d/m/Y', strtotime($day)) ?> <span class="text-gray-400"><?= ucfirst(strftime('%A', strtotime($day))) ?></span></td>
                                        <td class="px-2 py-1.5 text-center font-bold"><?= $d['count'] ?></td>
                                        <td class="px-2 py-1.5 text-right font-bold text-green-600">$<?= number_format($d['total'], 0, ',', '.') ?></td>
                                        <td class="px-2 py-1.5 text-right text-gray-500">$<?= number_format($d['count'] > 0 ? $d['total'] / $d['count'] : 0, 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="border-t-2 border-gray-800 font-bold">
                                        <td class="px-2 py-1.5">TOTAL</td>
                                        <td class="px-2 py-1.5 text-center"><?= $totals->facturas_count ?></td>
                                        <td class="px-2 py-1.5 text-right text-green-700">$<?= number_format($totals->facturas_total, 0, ',', '.') ?></td>
                                        <td class="px-2 py-1.5 text-right text-gray-500">$<?= number_format($totals->facturas_count > 0 ? $totals->facturas_total / $totals->facturas_count : 0, 0, ',', '.') ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="px-3 py-2 bg-gray-50 border-b flex justify-between items-center">
                            <span class="text-xs text-gray-500"><?= $totals->facturas_count ?> facturas | Total: $<?= number_format($totals->facturas_total, 0, ',', '.') ?> | Fletes: $<?= number_format($totals->envios_total, 0, ',', '.') ?></span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-2 py-2.5 font-semibold">Presup.</th>
                                        <th class="px-2 py-2.5 font-semibold">Factura</th>
                                        <th class="px-2 py-2.5 font-semibold">Cliente</th>
                                        <th class="px-2 py-2.5 font-semibold">Vendedor</th>
                                        <th class="px-2 py-2.5 font-semibold text-right">Total</th>
                                        <th class="px-2 py-2.5 font-semibold text-center">Creacion</th>
                                        <th class="px-2 py-2.5 font-semibold text-center">Embalaje</th>
                                        <th class="px-2 py-2.5 font-semibold text-center">Facturacion</th>
                                        <th class="px-2 py-2.5 font-semibold text-center">T. Proceso</th>
                                        <th class="px-2 py-2.5 font-semibold">Embalo</th>
                                        <th class="px-2 py-2.5 font-semibold">Destino</th>
                                        <th class="px-2 py-2.5 font-semibold">Transportadora</th>
                                        <th class="px-2 py-2.5 font-semibold text-center">Guia</th>
                                        <th class="px-2 py-2.5 font-semibold text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($facturas)): ?>
                                    <tr><td colspan="14" class="px-3 py-8 text-center text-gray-400">No hay facturas en este rango</td></tr>
                                    <?php else: $i=0; foreach($facturas as $f): $i++;
                                        $tc = isset($transportadoras[$f->transportadora]) ? $transportadoras[$f->transportadora] : $transportadoras['sin_despacho'];
                                        $destino = !empty($f->shipping_destination) ? $f->shipping_destination : (!empty($f->despacho_destino) ? $f->despacho_destino : $f->client_city);
                                        $tProceso = timeDiff($f->budget_created, $f->invoice_created);
                                    ?>
                                    <tr class="border-t <?= $i%2==0?'bg-gray-50':'bg-white' ?> hover:bg-blue-50">
                                        <td class="px-2 py-1.5 font-mono font-medium"><?= $f->budgetId ?: '-' ?></td>
                                        <td class="px-2 py-1.5 font-mono font-medium">#<?= $f->idInvoice ?></td>
                                        <td class="px-2 py-1.5">
                                            <div class="font-medium truncate max-w-xs"><?= $f->client_name ?></div>
                                            <div class="text-gray-400 text-xxs"><?= $f->store_name ?></div>
                                        </td>
                                        <td class="px-2 py-1.5"><?= $f->vendor_name ?></td>
                                        <td class="px-2 py-1.5 text-right font-medium">$<?= number_format($f->invoice_total, 0, ',', '.') ?></td>
                                        <td class="px-2 py-1.5 text-center">
                                            <?php if($f->budget_created): ?>
                                            <div><?= date('d/m', strtotime($f->budget_created)) ?></div>
                                            <div class="text-gray-400"><?= date('H:i', strtotime($f->budget_created)) ?></div>
                                            <?php else: ?>-<?php endif; ?>
                                        </td>
                                        <td class="px-2 py-1.5 text-center">
                                            <?php if($f->embalado_at): ?>
                                            <div><?= date('d/m', strtotime($f->embalado_at)) ?></div>
                                            <div class="text-gray-400"><?= date('H:i', strtotime($f->embalado_at)) ?></div>
                                            <?php else: ?><span class="text-gray-300">-</span><?php endif; ?>
                                        </td>
                                        <td class="px-2 py-1.5 text-center">
                                            <div><?= date('d/m', strtotime($f->invoice_created)) ?></div>
                                            <div class="text-gray-400"><?= date('H:i', strtotime($f->invoice_created)) ?></div>
                                        </td>
                                        <td class="px-2 py-1.5 text-center font-mono text-xs <?= $tProceso != '-' ? 'text-blue-600 font-bold' : '' ?>"><?= $tProceso ?></td>
                                        <td class="px-2 py-1.5"><?= $f->embalador_name ?: '-' ?></td>
                                        <td class="px-2 py-1.5"><?= $destino ?: '-' ?></td>
                                        <td class="px-2 py-1.5">
                                            <?php if($f->transportadora && $f->transportadora !== 'sin_despacho'): ?>
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-<?= $tc['color'] ?>-100 text-<?= $tc['color'] ?>-700"><?= $tc['label'] ?></span>
                                            <?php else: ?>
                                            <span class="text-xs text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-2 py-1.5 text-center font-mono text-xs"><?= $f->numeroPreenvio ?: '-' ?></td>
                                        <td class="px-2 py-1.5 text-center">
                                            <?php if($f->estadoNombre): ?>
                                            <span class="text-xs"><?= $f->estadoNombre ?></span>
                                            <?php else: ?>-<?php endif; ?>
                                        </td>
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
