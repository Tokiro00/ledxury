<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$pctTotal = $totalMeta > 0 ? round(($totalVentas / $totalMeta) * 100, 1) : 0;

function fmtMoney($v) {
    if ($v >= 1000000000) return '$' . number_format($v/1000000000, 1, '.', '') . 'B';
    if ($v >= 1000000) return '$' . number_format($v/1000000, 1, '.', '') . 'M';
    return '$' . number_format($v, 0, ',', '.');
}

$deltaVentas = $prevTotalVentas > 0 ? round((($totalVentas - $prevTotalVentas) / $prevTotalVentas) * 100) : ($totalVentas > 0 ? 100 : 0);
$deltaCobros = $prevTotalCobros > 0 ? round((($totalCobros - $prevTotalCobros) / $prevTotalCobros) * 100) : ($totalCobros > 0 ? 100 : 0);
if ($isYtd) {
    $pctDia = $isCurrentMonth ? ((($month - 1) + ($dayOfMonth / max($daysInMonth, 1))) / max($month, 1)) : 1;
} else {
    $pctDia = $daysInMonth > 0 ? ($dayOfMonth / $daysInMonth) : 0;
}
$paceExpected = $metaMensualVentas * $pctDia;
$paceDelta = $paceExpected > 0 ? round((($totalVentas - $paceExpected) / $paceExpected) * 100) : 0;
$projStatus = $metaMensualVentas > 0 ? ($proyeccionGlobal >= $metaMensualVentas ? 'alcanzable' : ($proyeccionGlobal >= $metaMensualVentas * 0.8 ? 'ajustado' : 'dificil')) : 'sin_meta';
$projColors = ['alcanzable' => 'var(--mam-green-program)', 'ajustado' => 'var(--mam-yellow)', 'dificil' => 'var(--mam-red)', 'sin_meta' => 'var(--fg-3)'];
$projLabels = ['alcanzable' => 'Alcanzable', 'ajustado' => 'Ajustado', 'dificil' => 'Rezagado', 'sin_meta' => 'Sin meta'];

$topVendors = array_slice($vendors, 0, 3);
$mNamesGlobal = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
?>
<!DOCTYPE html>
<html lang="es">
    <title>Panel de Vendedores</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <?php $this->load->view('sisvent/layouts/vendor_dashboards_tabs', ['active' => 'panel']); ?>

                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-4">
                        <div>
                            <h2 class="text-2xl font-black text-gray-800 tracking-tight">Panel de Vendedores</h2>
                            <p class="text-xs text-gray-400 uppercase tracking-widest">
                                <?php if($isYtd): ?>
                                Acumulado Ene–<?= $mNamesGlobal[$month-1] ?> <?= $year ?>
                                <?php else: ?>
                                <?= $monthName ?> <?= $year ?> <?= $isCurrentMonth ? '- Dia ' . $dayOfMonth . ' de ' . $daysInMonth . ' (' . $workDaysLeft . ' dias habiles restantes)' : '' ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="flex gap-2 mt-2 lg:mt-0">
                            <a href="<?= base_url() ?>sisvent/admin/salesboard/metas" class="px-4 py-2 text-xs font-bold text-white rounded-lg" style="background:var(--mam-blue-dark);">Configurar Metas</a>
                            <a href="<?= base_url() ?>sisvent/admin/salesboard/inactivos" class="px-4 py-2 text-xs font-bold text-white rounded-lg bg-red-500 hover:bg-red-600">Clientes Inactivos</a>
                        </div>
                    </div>

                    <!-- Toggle: Mensual / Acumulado YTD -->
                    <div class="inline-flex rounded-lg p-1 mb-3" style="background:var(--mam-gray-300);">
                        <a href="?year=<?= $year ?>&month=<?= $month ?>&store=<?= $storeFilter ?>&view=month"
                           class="px-4 py-1.5 text-xs font-bold rounded-md transition-all <?= !$isYtd ? 'text-white shadow-sm' : 'text-gray-500' ?>"
                           <?= !$isYtd ? 'style="background:var(--mam-blue-dark);"' : '' ?>>Mensual</a>
                        <a href="?year=<?= $year ?>&month=<?= $month ?>&store=<?= $storeFilter ?>&view=ytd"
                           class="px-4 py-1.5 text-xs font-bold rounded-md transition-all <?= $isYtd ? 'text-white shadow-sm' : 'text-gray-500' ?>"
                           <?= $isYtd ? 'style="background:var(--mam-blue-dark);"' : '' ?>>Acumulado YTD</a>
                    </div>

                    <!-- Filtros: mes + bodega -->
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <?php
                        $mNames = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                        $cm = (int)date('n');
                        $cy = (int)date('Y');
                        $lastMonth = ($year < $cy) ? 12 : $cm;
                        for ($m = 1; $m <= $lastMonth; $m++):
                            $isActive = ($m == $month);
                        ?>
                        <a href="?year=<?= $year ?>&month=<?= $m ?>&store=<?= $storeFilter ?>&view=<?= $view ?>"
                           class="px-3 py-1 text-xs font-bold rounded-lg <?= $isActive ? 'text-white' : 'text-gray-500 bg-gray-100 hover:bg-gray-200' ?>"
                           <?= $isActive ? 'style="background:var(--mam-blue-dark);"' : '' ?>>
                            <?= $isYtd ? '→' : '' ?><?= $mNames[$m-1] ?>
                        </a>
                        <?php endfor; ?>

                        <span class="text-gray-300 mx-1">|</span>
                        <select onchange="window.location='?year=<?= $year ?>&month=<?= $month ?>&view=<?= $view ?>&store='+this.value" class="text-xs border border-gray-300 rounded-lg px-2 py-1">
                            <option value="all" <?= $storeFilter == 'all' ? 'selected' : '' ?>>Todas (MDE)</option>
                            <?php foreach($tiendas as $t): ?>
                            <option value="<?= $t->idStore ?>" <?= $storeFilter == $t->idStore ? 'selected' : '' ?>><?= $t->name ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if($isYtd): ?>
                        <span class="text-xs text-gray-500 ml-2">· Acumulado desde Enero hasta <strong><?= $mNames[$month-1] ?></strong></span>
                        <?php endif; ?>
                    </div>

                    <!-- KPI strip — estilo Fiori/Odoo: 5 tiles compactos con delta -->
                    <?php
                    $totalInv = array_sum(array_column(array_map(function($v){return (array)$v;}, $vendors), 'invoices'));
                    $ticketProm = $totalInv > 0 ? ($totalVentas / $totalInv) : 0;
                    $bulletColV = $pctColectivoVentas >= 80 ? 'var(--mam-green)' : ($pctColectivoVentas >= 50 ? 'var(--mam-yellow)' : 'var(--mam-red)');
                    $bulletColC = $pctColectivoCobros >= 80 ? 'var(--mam-green)' : ($pctColectivoCobros >= 50 ? 'var(--mam-yellow)' : 'var(--mam-red)');
                    ?>
                    <?php
                    /* KPI strip — migrado a _kpi_tile.php (DS v1.28.0). */
                    $convAccent = $globalConv >= 70
                        ? 'var(--mam-green-program)'
                        : ($globalConv >= 40 ? 'var(--mam-yellow)' : 'var(--mam-red)');
                    ?>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-3">
                        <?php $this->load->view('sisvent/design-system/_kpi_tile', [
                            'eyebrow'    => 'Ventas',
                            'value'      => fmtMoney($totalVentas),
                            'delta'      => ($deltaVentas >= 0 ? '▲ ' : '▼ ') . abs($deltaVentas) . '% vs periodo ant.',
                            'delta_tone' => $deltaVentas >= 0 ? 'up' : 'dn',
                            'accent'     => 'var(--mam-blue-petroleo)',
                        ]); ?>
                        <?php $this->load->view('sisvent/design-system/_kpi_tile', [
                            'eyebrow'    => 'Cobros',
                            'value'      => fmtMoney($totalCobros),
                            'delta'      => ($deltaCobros >= 0 ? '▲ ' : '▼ ') . abs($deltaCobros) . '% vs periodo ant.',
                            'delta_tone' => $deltaCobros >= 0 ? 'up' : 'dn',
                            'accent'     => 'var(--mam-yellow)',
                        ]); ?>
                        <?php $this->load->view('sisvent/design-system/_kpi_tile', [
                            'eyebrow' => 'Cumplen meta',
                            'value'   => $cumplieron . '<span class="text-base font-normal" style="color: var(--fg-3);">/' . $totalVendors . '</span>',
                            'delta'   => 'vendedores',
                            'accent'  => 'var(--mam-green)',
                        ]); ?>
                        <?php $this->load->view('sisvent/design-system/_kpi_tile', [
                            'eyebrow' => 'Ticket prom.',
                            'value'   => fmtMoney($ticketProm),
                            'delta'   => $totalInv . ' facturas',
                            'accent'  => 'var(--mam-blue-dark)',
                        ]); ?>
                        <?php $this->load->view('sisvent/design-system/_kpi_tile', [
                            'eyebrow'    => 'Conversión',
                            'value'      => $globalConv . '%',
                            'delta'      => 'presup → factura',
                            'delta_tone' => $globalConv >= 70 ? 'up' : ($globalConv >= 40 ? 'mid' : 'dn'),
                            'accent'     => $convAccent,
                        ]); ?>
                    </div>

                    <!-- Bullet charts: Meta Ventas + Meta Cobros — estilo SAP Fiori -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-3">
                        <!-- Bullet Meta Ventas -->
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-gray-400 font-bold uppercase tracking-wide">Meta Ventas <?= $monthName ?></p>
                                <span class="text-xs font-black px-2 py-0.5 rounded" style="background:<?= $bulletColV ?>1A;color:<?= $bulletColV ?>;"><?= $pctColectivoVentas ?>%</span>
                            </div>
                            <?php $wV = min($pctColectivoVentas, 100); ?>
                            <div class="relative h-6 rounded overflow-hidden" style="background:var(--mam-gray-200);">
                                <!-- zonas semáforo de fondo -->
                                <div class="absolute top-0 left-0 h-full" style="width:50%;background:var(--pill-danger-bg);"></div>
                                <div class="absolute top-0 h-full" style="left:50%;width:30%;background:var(--pill-warning-bg);"></div>
                                <div class="absolute top-0 h-full" style="left:80%;width:20%;background:var(--pill-success-bg);"></div>
                                <!-- barra actual -->
                                <div class="absolute top-1 bottom-1 left-0 rounded" style="width:<?= $wV ?>%;background:<?= $bulletColV ?>;"></div>
                                <!-- marcador de meta (100%) -->
                                <div class="absolute top-0 bottom-0" style="left:100%;width:2px;background:var(--mam-blue-dark);transform:translateX(-2px);"></div>
                            </div>
                            <div class="flex items-center justify-between mt-2 text-xs">
                                <span class="font-bold" style="color:var(--mam-blue-dark);"><?= fmtMoney($totalVentas) ?></span>
                                <?php if($metaMensualVentas > 0 && $totalVentas < $metaMensualVentas): ?>
                                <span class="text-gray-500">Faltan <span class="font-bold text-red-600"><?= fmtMoney($metaMensualVentas - $totalVentas) ?></span></span>
                                <?php elseif($metaMensualVentas > 0): ?>
                                <span class="text-green-600 font-bold">Meta superada +<?= fmtMoney($totalVentas - $metaMensualVentas) ?></span>
                                <?php endif; ?>
                                <span class="text-gray-400">Meta: <?= fmtMoney($metaMensualVentas) ?></span>
                            </div>
                            <?php if($metaMensualVentas > 0 && $isCurrentMonth): ?>
                            <p class="text-3xxs text-gray-500 mt-2 border-t pt-2">
                                Proyección: <span class="font-bold" style="color:<?= $projColors[$projStatus] ?>;"><?= fmtMoney($proyeccionGlobal) ?></span>
                                · <span class="font-semibold"><?= $projLabels[$projStatus] ?></span>
                                · Pacing <?= $paceDelta >= 0 ? '+' : '' ?><?= $paceDelta ?>%
                            </p>
                            <?php endif; ?>
                        </div>

                        <!-- Bullet Meta Cobros -->
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-gray-400 font-bold uppercase tracking-wide">Meta Cobros <?= $monthName ?></p>
                                <span class="text-xs font-black px-2 py-0.5 rounded" style="background:<?= $bulletColC ?>1A;color:<?= $bulletColC ?>;"><?= $pctColectivoCobros ?>%</span>
                            </div>
                            <?php $wC = min($pctColectivoCobros, 100); ?>
                            <div class="relative h-6 rounded overflow-hidden" style="background:var(--mam-gray-200);">
                                <div class="absolute top-0 left-0 h-full" style="width:50%;background:var(--pill-danger-bg);"></div>
                                <div class="absolute top-0 h-full" style="left:50%;width:30%;background:var(--pill-warning-bg);"></div>
                                <div class="absolute top-0 h-full" style="left:80%;width:20%;background:var(--pill-success-bg);"></div>
                                <div class="absolute top-1 bottom-1 left-0 rounded" style="width:<?= $wC ?>%;background:<?= $bulletColC ?>;"></div>
                                <div class="absolute top-0 bottom-0" style="left:100%;width:2px;background:var(--mam-blue-dark);transform:translateX(-2px);"></div>
                            </div>
                            <div class="flex items-center justify-between mt-2 text-xs">
                                <span class="font-bold" style="color:var(--mam-yellow);"><?= fmtMoney($totalCobros) ?></span>
                                <?php if($metaMensualCobros > 0 && $totalCobros < $metaMensualCobros): ?>
                                <span class="text-gray-500">Faltan <span class="font-bold text-red-600"><?= fmtMoney($metaMensualCobros - $totalCobros) ?></span></span>
                                <?php elseif($metaMensualCobros > 0): ?>
                                <span class="text-green-600 font-bold">Meta superada +<?= fmtMoney($totalCobros - $metaMensualCobros) ?></span>
                                <?php endif; ?>
                                <span class="text-gray-400">Meta: <?= fmtMoney($metaMensualCobros) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Charts row: Evolucion diaria + Pipeline -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 mb-3">
                        <!-- Evolucion diaria / mensual -->
                        <div class="lg:col-span-8 bg-white rounded-xl shadow-sm p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wide"><?= $isYtd ? 'Evolucion mensual' : 'Evolucion diaria' ?></p>
                                    <p class="text-sm font-semibold text-gray-700"><?= $isYtd ? ('Año ' . $year) : ($monthName . ' ' . $year) ?></p>
                                </div>
                                <div class="flex items-center gap-3 text-xs">
                                    <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm" style="background:var(--mam-blue-dark);"></span>Ventas</span>
                                    <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm" style="background:var(--mam-yellow);"></span>Cobros</span>
                                    <?php if($metaMensualVentas > 0): ?>
                                    <span class="flex items-center gap-1"><span class="inline-block w-3 h-3" style="background:repeating-linear-gradient(90deg,var(--mam-red) 0 4px,transparent 4px 8px);"></span>Pace meta</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div id="dailyChart" style="height:<?= $isYtd ? '240' : '200' ?>px;"></div>
                        </div>

                        <!-- Funnel SVG: Presupuestos → Facturas → Cobros -->
                        <div class="lg:col-span-4 bg-white rounded-xl shadow-sm p-4">
                            <p class="text-xs text-gray-400 font-bold uppercase tracking-wide mb-3">Pipeline comercial</p>
                            <?php
                            $invPct = $totalBudgets > 0 ? round(($totalInvoices / $totalBudgets) * 100) : 0;
                            $cobPct = $totalVentas > 0 ? round(($totalCobros / $totalVentas) * 100) : 0;
                            // Anchos proporcionales del funnel (min 25% para que conserve forma de embudo)
                            $w1 = 100;
                            $w2 = max($invPct, 25);
                            $w3 = max(round($w2 * ($cobPct / 100)), 15);
                            // Offsets laterales (para centrar cada capa)
                            $off1 = (100 - $w1) / 2;
                            $off2 = (100 - $w2) / 2;
                            $off3 = (100 - $w3) / 2;
                            ?>
                            <svg viewBox="0 0 100 140" preserveAspectRatio="none" style="width:100%;height:180px;">
                                <!-- Capa 1: Presupuestos -->
                                <polygon points="<?= $off1 ?>,5 <?= 100 - $off1 ?>,5 <?= 100 - $off2 ?>,42 <?= $off2 ?>,42"
                                         fill="var(--mam-blue-dark)"/>
                                <!-- Capa 2: Facturas -->
                                <polygon points="<?= $off2 ?>,47 <?= 100 - $off2 ?>,47 <?= 100 - $off3 ?>,84 <?= $off3 ?>,84"
                                         fill="var(--mam-blue-petroleo)"/>
                                <!-- Capa 3: Cobros -->
                                <polygon points="<?= $off3 ?>,89 <?= 100 - $off3 ?>,89 <?= 100 - $off3 - 3 ?>,126 <?= $off3 + 3 ?>,126"
                                         fill="var(--mam-green)"/>
                            </svg>
                            <!-- Labels al costado del funnel -->
                            <div class="space-y-2 mt-1 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        <span class="inline-block w-2 h-2 rounded" style="background:var(--mam-blue-dark);"></span>
                                        <span class="font-semibold text-gray-600">Presupuestos</span>
                                    </span>
                                    <span class="font-black text-gray-800"><?= $totalBudgets ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        <span class="inline-block w-2 h-2 rounded" style="background:var(--mam-blue-petroleo);"></span>
                                        <span class="font-semibold text-gray-600">Facturas</span>
                                        <span class="text-xxs text-gray-400">(<?= $invPct ?>% conv.)</span>
                                    </span>
                                    <span class="font-black text-gray-800"><?= $totalInvoices ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        <span class="inline-block w-2 h-2 rounded" style="background:var(--mam-green);"></span>
                                        <span class="font-semibold text-gray-600">Cobros</span>
                                        <span class="text-xxs text-gray-400">(<?= $cobPct ?>% recup.)</span>
                                    </span>
                                    <span class="font-black text-gray-800"><?= fmtMoney($totalCobros) ?></span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 mt-3 pt-3 border-t">
                                <div class="text-center">
                                    <p class="text-lg font-black" style="color:<?= $globalConv>=70?'var(--mam-green)':($globalConv>=40?'var(--mam-yellow)':'var(--mam-red)') ?>;"><?= $globalConv ?>%</p>
                                    <p class="text-xxs text-gray-400 uppercase font-bold">Conversión</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-black" style="color:<?= $cobPct>=80?'var(--mam-green)':($cobPct>=50?'var(--mam-yellow)':'var(--mam-red)') ?>;"><?= $cobPct ?>%</p>
                                    <p class="text-xxs text-gray-400 uppercase font-bold">Recuperación</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Podio top 3 + Distribucion por tienda -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 mb-3">
                        <!-- Podio -->
                        <div class="lg:col-span-7 bg-white rounded-xl shadow-sm p-4">
                            <p class="text-xs text-gray-400 font-bold uppercase tracking-wide mb-3">Top 3 del periodo</p>
                            <?php if(count($topVendors) >= 3): ?>
                            <div class="grid grid-cols-3 gap-2 items-end" style="min-height:160px;">
                                <?php
                                $order = [1, 0, 2]; // 2do, 1ero, 3ro
                                $podiumH = [80, 120, 60];
                                $podiumColor = ['var(--fg-3)', 'var(--mam-yellow)', '#CD7F32'];
                                $medalSize = [48, 60, 44];
                                foreach ($order as $idx => $ti):
                                    if (!isset($topVendors[$ti])) continue;
                                    $tv = $topVendors[$ti];
                                    $rank = $ti + 1;
                                ?>
                                <div class="flex flex-col items-center">
                                    <span class="inline-flex items-center justify-center rounded-full text-white font-black shadow-lg" style="width:<?= $medalSize[$idx] ?>px;height:<?= $medalSize[$idx] ?>px;background:<?= $podiumColor[$idx] ?>;font-size:<?= $medalSize[$idx] > 50 ? '22' : '16' ?>px;"><?= $rank ?></span>
                                    <p class="text-xs font-bold text-gray-700 mt-2 text-center truncate w-full" title="<?= htmlspecialchars($tv->name) ?>"><?= $tv->name ?></p>
                                    <p class="text-sm font-black" style="color:var(--mam-blue-dark);"><?= fmtMoney($tv->ventasMes) ?></p>
                                    <p class="text-xs <?= $tv->pctMeta >= 100 ? 'text-green-600' : ($tv->pctMeta >= 60 ? 'text-yellow-600' : 'text-red-600') ?> font-bold"><?= $tv->pctMeta ?>% meta</p>
                                    <div class="w-full rounded-t-lg mt-2" style="height:<?= $podiumH[$idx] ?>px;background:linear-gradient(180deg,<?= $podiumColor[$idx] ?>,var(--mam-blue-dark));"></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p class="text-sm text-gray-400 text-center py-6">Sin suficientes vendedores.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Distribucion por tienda -->
                        <div class="lg:col-span-5 bg-white rounded-xl shadow-sm p-4">
                            <p class="text-xs text-gray-400 font-bold uppercase tracking-wide mb-3">Distribucion por bodega</p>
                            <?php if(!empty($storeBreakdown)):
                                $mxS = 0; foreach($storeBreakdown as $sb) if($sb->v > $mxS) $mxS = $sb->v;
                                $palette = ['var(--mam-blue-dark)','var(--mam-blue-petroleo)','var(--mam-green-program)','var(--mam-yellow)','var(--mam-purple)','var(--mam-red)','#06B6D4'];
                            ?>
                            <div class="space-y-2">
                                <?php foreach($storeBreakdown as $i => $sb):
                                    $w = $mxS > 0 ? ($sb->v / $mxS) * 100 : 0;
                                    $pctSb = $totalVentas > 0 ? round(($sb->v / $totalVentas) * 100) : 0;
                                ?>
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="font-semibold text-gray-700 truncate"><?= $sb->store_name ?: 'Sin bodega' ?></span>
                                        <span class="text-gray-400"><?= $sb->cnt ?> fact.</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 rounded-full h-4" style="background:var(--mam-gray-200);">
                                            <div class="h-4 rounded-full" style="width:<?= max($w,3) ?>%;background:<?= $palette[$i % count($palette)] ?>;"></div>
                                        </div>
                                        <span class="text-xs font-black whitespace-nowrap" style="color:var(--mam-blue-dark);min-width:70px;text-align:right;"><?= fmtMoney($sb->v) ?></span>
                                        <span class="text-xs text-gray-400 font-semibold" style="min-width:32px;text-align:right;"><?= $pctSb ?>%</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p class="text-sm text-gray-400 text-center py-6">Sin ventas en el periodo.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tabla de vendedores — 6 cols con microcharts inline (Fiori pattern) -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:var(--mam-blue-dark); color:white;">
                                        <th class="px-3 py-2.5 font-semibold">Vendedor</th>
                                        <th class="px-3 py-2.5 font-semibold" style="min-width:180px;">Ventas vs Meta</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">% Meta</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Conversión</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Cobros</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Estado</th>
                                        <th class="px-2 py-2.5 font-semibold text-center w-6"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach($vendors as $v): $i++;
                                        $pctCap = min($v->pctMeta, 100);
                                        $bulletCol = $v->pctMeta >= 100 ? 'var(--mam-green)' : ($v->pctMeta >= 60 ? 'var(--mam-yellow)' : 'var(--mam-red)');
                                        $pctText = $v->pctMeta >= 100 ? 'text-green-600' : ($v->pctMeta >= 60 ? 'text-yellow-600' : 'text-red-600');
                                        $tendColor = $v->tendencia > 0 ? 'text-green-600' : ($v->tendencia < 0 ? 'text-red-600' : 'text-gray-400');
                                        $tendArrow = $v->tendencia > 0 ? '▲' : ($v->tendencia < 0 ? '▼' : '→');
                                        $convText = $v->conversion >= 70 ? 'text-green-600' : ($v->conversion >= 40 ? 'text-yellow-600' : 'text-red-600');
                                        $pctCobro = $v->meta > 0 ? round(($v->cobros / $v->meta) * 100) : 0;
                                        $cobroText = $pctCobro >= 80 ? 'text-green-600' : ($pctCobro >= 50 ? 'text-yellow-600' : ($v->cobros > 0 ? 'text-orange-600' : 'text-gray-400'));
                                        // Harvey ball: cuarto por 25% de health
                                        $hb = $v->avgHealth;
                                        $hbColor = $hb >= 75 ? 'var(--mam-green)' : ($hb >= 50 ? 'var(--mam-yellow)' : ($hb > 0 ? 'var(--mam-red)' : 'var(--mam-gray-300)'));
                                        // actividad
                                        if ($v->diasSinActividad == 0) { $actLabel = 'Hoy'; $actColor = 'text-green-600'; }
                                        elseif ($v->diasSinActividad == 1) { $actLabel = 'Ayer'; $actColor = 'text-yellow-600'; }
                                        elseif ($v->diasSinActividad <= 3) { $actLabel = $v->diasSinActividad . 'd'; $actColor = 'text-yellow-600'; }
                                        else { $actLabel = $v->diasSinActividad . 'd'; $actColor = 'text-red-600 font-bold'; }
                                    ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50 btn-vendor-detail" style="cursor:pointer;" data-vendor="<?= $v->vendorId ?>" data-name="<?= htmlspecialchars($v->name) ?>"
                                        data-ventas="<?= $v->ventasMes ?>" data-meta="<?= $v->meta ?>" data-pct="<?= $v->pctMeta ?>"
                                        data-cobros="<?= $v->cobros ?>" data-conv="<?= $v->conversion ?>" data-budgets="<?= $v->budgets ?>" data-invoices="<?= $v->invoices ?>"
                                        data-active="<?= $v->clientsActive ?>" data-assigned="<?= $v->clientsAssigned ?>" data-health="<?= $v->avgHealth ?>"
                                        data-tend="<?= $v->tendencia ?>" data-proy="<?= $v->proyeccion ?>" data-proyest="<?= $v->proyEstado ?>"
                                    >
                                        <!-- 1) Vendedor + rank -->
                                        <td class="px-3 py-2.5 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <?php if($i <= 3): ?>
                                                <span class="inline-flex items-center justify-center w-5 h-5 text-xxs font-black text-white rounded-full flex-shrink-0" style="background:<?= $i == 1 ? 'var(--mam-yellow)' : ($i == 2 ? 'var(--fg-3)' : '#CD7F32') ?>;"><?= $i ?></span>
                                                <?php else: ?>
                                                <span class="inline-flex items-center justify-center w-5 h-5 text-xxs font-bold text-gray-400 flex-shrink-0"><?= $i ?></span>
                                                <?php endif; ?>
                                                <span class="font-medium text-gray-800"><?= $v->name ?></span>
                                            </div>
                                        </td>

                                        <!-- 2) Ventas vs Meta: bullet inline + montos -->
                                        <td class="px-3 py-2.5">
                                            <div class="relative h-2 rounded overflow-hidden" style="background:var(--mam-gray-200);">
                                                <?php if($v->meta > 0): ?>
                                                <!-- zonas semáforo de fondo -->
                                                <div class="absolute top-0 left-0 h-full" style="width:50%;background:var(--pill-danger-bg);"></div>
                                                <div class="absolute top-0 h-full" style="left:50%;width:30%;background:var(--pill-warning-bg);"></div>
                                                <div class="absolute top-0 h-full" style="left:80%;width:20%;background:var(--pill-success-bg);"></div>
                                                <!-- barra actual -->
                                                <div class="absolute inset-y-0 left-0 rounded" style="width:<?= $pctCap ?>%;background:<?= $bulletCol ?>;"></div>
                                                <!-- marcador meta -->
                                                <div class="absolute inset-y-0" style="left:100%;width:2px;background:var(--mam-blue-dark);transform:translateX(-2px);"></div>
                                                <?php else: ?>
                                                <div class="absolute inset-y-0 left-0 rounded" style="width:<?= $v->ventasMes > 0 ? 100 : 0 ?>%;background:var(--fg-3);"></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex items-center justify-between mt-1 text-3xxs">
                                                <span class="font-bold" style="color:var(--mam-blue-dark);">$<?= number_format($v->ventasMes, 0, ',', '.') ?></span>
                                                <?php if($v->meta > 0): ?>
                                                <span class="text-gray-400">de <?= fmtMoney($v->meta) ?></span>
                                                <?php else: ?>
                                                <span class="text-gray-300">sin meta</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- 3) % Meta + tendencia -->
                                        <td class="px-3 py-2.5 text-center">
                                            <p class="text-base font-black <?= $pctText ?>"><?= $v->pctMeta ?>%</p>
                                            <?php if($v->tendencia != 0): ?>
                                            <p class="text-xxs <?= $tendColor ?> font-semibold"><?= $tendArrow ?> <?= abs($v->tendencia) ?>%</p>
                                            <?php else: ?>
                                            <p class="text-xxs text-gray-300">—</p>
                                            <?php endif; ?>
                                        </td>

                                        <!-- 4) Conversión -->
                                        <td class="px-3 py-2.5 text-center">
                                            <p class="text-base font-black <?= $convText ?>"><?= $v->conversion ?>%</p>
                                            <p class="text-xxs text-gray-400"><?= $v->budgets ?> → <?= $v->invoices ?></p>
                                        </td>

                                        <!-- 5) Cobros -->
                                        <td class="px-3 py-2.5 text-right">
                                            <p class="font-bold <?= $cobroText ?>">$<?= number_format($v->cobros, 0, ',', '.') ?></p>
                                            <?php if($v->meta > 0): ?>
                                            <p class="text-xxs <?= $cobroText ?>"><?= $pctCobro ?>% vs meta</p>
                                            <?php else: ?>
                                            <p class="text-xxs text-gray-300">—</p>
                                            <?php endif; ?>
                                        </td>

                                        <!-- 6) Estado: harvey ball + actividad + clientes -->
                                        <td class="px-3 py-2.5">
                                            <div class="flex items-center justify-center gap-2">
                                                <?php if($v->avgHealth > 0): ?>
                                                <!-- Harvey ball SVG (4 cuartos) -->
                                                <?php
                                                $quarters = round($hb / 25); // 0-4
                                                $hbPaths = [
                                                    0 => '',
                                                    1 => 'M10,10 L10,0 A10,10 0 0,1 20,10 Z',
                                                    2 => 'M10,10 L10,0 A10,10 0 0,1 10,20 Z',
                                                    3 => 'M10,10 L10,0 A10,10 0 0,1 20,10 A10,10 0 0,1 10,20 Z',
                                                    4 => 'M10,10 L10,0 A10,10 0 1,1 9.99,0 Z',
                                                ];
                                                ?>
                                                <span class="inline-block flex-shrink-0" title="Salud: <?= $hb ?>">
                                                    <svg viewBox="0 0 20 20" width="16" height="16">
                                                        <circle cx="10" cy="10" r="9" fill="none" stroke="<?= $hbColor ?>" stroke-width="1.5"/>
                                                        <?php if($quarters > 0): ?>
                                                        <path d="<?= $hbPaths[$quarters] ?>" fill="<?= $hbColor ?>"/>
                                                        <?php endif; ?>
                                                    </svg>
                                                </span>
                                                <?php else: ?>
                                                <span class="inline-block w-4 h-4 rounded-full border border-gray-200 flex-shrink-0"></span>
                                                <?php endif; ?>
                                                <div class="text-left">
                                                    <p class="<?= $actColor ?> text-3xxs"><?= $actLabel ?></p>
                                                    <p class="text-xxs text-gray-400"><?= $v->clientsActive ?>/<?= $v->clientsAssigned ?> clientes</p>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Arrow drill-through -->
                                        <td class="px-1 py-2.5 text-center text-gray-300">›</td>
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

    <!-- Modal: Vendor Detail -->
    <div id="vendorModal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,0.45);">
        <div class="bg-white rounded-xl shadow-2xl" style="width:940px; max-width:96vw; max-height:92vh; display:flex; flex-direction:column;">
            <!-- Header with integrated KPIs -->
            <div class="flex-shrink-0 rounded-t-xl" style="background:linear-gradient(135deg, var(--mam-blue-dark) 0%, var(--mam-blue-petroleo) 100%);">
                <div class="flex items-center justify-between px-6 pt-4 pb-2">
                    <div>
                        <h3 class="font-black text-lg text-white" id="vmTitle"></h3>
                        <p class="text-xs text-blue-200" id="vmSubtitle"></p>
                    </div>
                    <button onclick="$('#vendorModal').addClass('hidden')" class="w-8 h-8 flex items-center justify-center rounded-full text-white hover:bg-white hover:bg-opacity-20 transition-colors text-xl leading-none">&times;</button>
                </div>
                <!-- KPI strip inside header -->
                <div class="grid grid-cols-6 gap-px px-6 pb-4 pt-2" id="vmHeaderKpis"></div>
            </div>
            <!-- Body -->
            <div class="px-6 py-5 overflow-y-auto" style="flex:1; background:var(--mam-gray-150);" id="vmBody"></div>
        </div>
    </div>

    <script>
    var vendorClients = <?= $vendorClientsJson ?>;
    var vendorMonthly = <?= $vendorMonthlyJson ?>;
    var vendorPeriodClients = <?= $vendorPeriodClientsJson ?>;
    var periodLabel = '<?= $monthName ?> <?= $year ?>';
    var dailySales = <?= $dailySalesJson ?>;
    var dailyCobros = <?= $dailyCobrosJson ?>;
    var metaVentasMes = <?= $metaMensualVentas ?>;
    var daysInMonth = <?= $daysInMonth ?>;
    var currentDayOfMonth = <?= $dayOfMonth ?>;
    var isCurrentMonth = <?= $isCurrentMonth ? 'true' : 'false' ?>;
    var chartMode = '<?= $isYtd ? 'ytd' : 'month' ?>';
    var selectedMonth = <?= $month ?>;
    var currentMonthNum = <?= (int)date('n') ?>;
    var currentYearNum = <?= (int)date('Y') ?>;
    var chartYear = <?= $year ?>;
    var mNames = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    function renderDailyChart(){
        if(chartMode === 'ytd') return renderYtdChart();
        return renderMonthChart();
    }

    function renderMonthChart(){
        var container = document.getElementById('dailyChart');
        if(!container) return;
        var w = container.clientWidth || 700;
        var h = 200;
        var padL = 40, padR = 10, padT = 10, padB = 22;
        var innerW = w - padL - padR, innerH = h - padT - padB;
        var n = dailySales.length;

        var maxDaily = 0;
        for(var i=0;i<n;i++){
            if(dailySales[i] > maxDaily) maxDaily = dailySales[i];
            if(dailyCobros[i] > maxDaily) maxDaily = dailyCobros[i];
        }
        if(maxDaily === 0) maxDaily = 1;
        var niceMax = Math.ceil(maxDaily / 1000000) * 1000000;
        if(niceMax === 0) niceMax = maxDaily;

        function x(i){ return padL + (n > 1 ? (i/(n-1))*innerW : innerW/2); }
        function y(v){ return padT + innerH - (v/niceMax)*innerH; }

        var svg = '<svg viewBox="0 0 '+w+' '+h+'" style="width:100%;height:'+h+'px;" preserveAspectRatio="none">';

        var ticks = 4;
        for(var t=0;t<=ticks;t++){
            var yy = padT + (t/ticks)*innerH;
            var val = niceMax * (1 - t/ticks);
            svg += '<line x1="'+padL+'" y1="'+yy+'" x2="'+(w-padR)+'" y2="'+yy+'" stroke="var(--mam-gray-200)" stroke-width="1"/>';
            var lbl = val >= 1000000 ? (val/1000000).toFixed(val>=10000000?0:1)+'M' : (val/1000).toFixed(0)+'k';
            svg += '<text x="'+(padL-4)+'" y="'+(yy+3)+'" text-anchor="end" font-size="9" fill="var(--fg-3)">$'+lbl+'</text>';
        }

        if(metaVentasMes > 0){
            var paceMax = metaVentasMes / daysInMonth;
            var yp = y(paceMax);
            if(yp >= padT && yp <= padT+innerH){
                svg += '<line x1="'+padL+'" y1="'+yp+'" x2="'+(w-padR)+'" y2="'+yp+'" stroke="var(--mam-red)" stroke-width="1.5" stroke-dasharray="5,4"/>';
                svg += '<text x="'+(w-padR-4)+'" y="'+(yp-4)+'" text-anchor="end" font-size="9" font-weight="700" fill="var(--mam-red)">Pace: $'+(paceMax/1000000).toFixed(1)+'M/dia</text>';
            }
        }

        if(isCurrentMonth && currentDayOfMonth >= 1 && currentDayOfMonth <= n){
            var xt = x(currentDayOfMonth - 1);
            svg += '<line x1="'+xt+'" y1="'+padT+'" x2="'+xt+'" y2="'+(padT+innerH)+'" stroke="var(--fg-3)" stroke-width="1" stroke-dasharray="2,3"/>';
        }

        var areaPath = 'M '+padL+' '+(padT+innerH);
        for(var i=0;i<n;i++){ areaPath += ' L '+x(i)+' '+y(dailySales[i]); }
        areaPath += ' L '+(w-padR)+' '+(padT+innerH)+' Z';
        svg += '<defs><linearGradient id="salesGrad" x1="0" y1="0" x2="0" y2="1">';
        svg += '<stop offset="0%" stop-color="var(--mam-blue-petroleo)" stop-opacity="0.35"/>';
        svg += '<stop offset="100%" stop-color="var(--mam-blue-dark)" stop-opacity="0.05"/>';
        svg += '</linearGradient></defs>';
        svg += '<path d="'+areaPath+'" fill="url(#salesGrad)"/>';

        var linePath = '';
        for(var i=0;i<n;i++){ linePath += (i===0?'M ':'L ')+x(i)+' '+y(dailySales[i])+' '; }
        svg += '<path d="'+linePath+'" fill="none" stroke="var(--mam-blue-dark)" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>';

        var cobPath = '';
        for(var i=0;i<n;i++){ cobPath += (i===0?'M ':'L ')+x(i)+' '+y(dailyCobros[i])+' '; }
        svg += '<path d="'+cobPath+'" fill="none" stroke="var(--mam-yellow)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" stroke-dasharray="0"/>';

        if(isCurrentMonth && currentDayOfMonth >= 1 && currentDayOfMonth <= n){
            var di = currentDayOfMonth - 1;
            svg += '<circle cx="'+x(di)+'" cy="'+y(dailySales[di])+'" r="4" fill="var(--mam-blue-dark)" stroke="white" stroke-width="2"/>';
        }

        for(var i=0;i<n;i++){
            if(i === 0 || (i+1) % 5 === 0 || i === n-1){
                svg += '<text x="'+x(i)+'" y="'+(padT+innerH+14)+'" text-anchor="middle" font-size="9" fill="var(--fg-3)">'+(i+1)+'</text>';
            }
        }

        svg += '</svg>';
        container.innerHTML = svg;
    }

    function renderYtdChart(){
        var container = document.getElementById('dailyChart');
        if(!container) return;
        var w = container.clientWidth || 700;
        var h = 240;
        var padL = 46, padR = 14, padT = 14, padB = 42;
        var innerW = w - padL - padR, innerH = h - padT - padB;
        var n = 12;

        // Only show up to selectedMonth data; future months faded
        var maxVal = 0;
        for(var i=0;i<n;i++){
            if((i+1) <= selectedMonth){
                if(dailySales[i] > maxVal) maxVal = dailySales[i];
                if(dailyCobros[i] > maxVal) maxVal = dailyCobros[i];
            }
        }
        if(maxVal === 0) maxVal = 1;
        var niceMax = Math.ceil(maxVal / 10000000) * 10000000;
        if(niceMax === 0) niceMax = maxVal;

        // Monthly meta (pace line): metaVentasMes is accumulated in YTD mode, so per-month avg = metaVentasMes/selectedMonth
        var monthlyMeta = selectedMonth > 0 ? metaVentasMes / selectedMonth : 0;

        var barW = innerW / n;
        function xStart(i){ return padL + i*barW + 4; }
        function y(v){ return padT + innerH - Math.min(v/niceMax, 1)*innerH; }

        var svg = '<svg viewBox="0 0 '+w+' '+h+'" style="width:100%;height:'+h+'px;" preserveAspectRatio="none">';

        // Gridlines + y-labels
        var ticks = 4;
        for(var t=0;t<=ticks;t++){
            var yy = padT + (t/ticks)*innerH;
            var val = niceMax * (1 - t/ticks);
            svg += '<line x1="'+padL+'" y1="'+yy+'" x2="'+(w-padR)+'" y2="'+yy+'" stroke="var(--mam-gray-200)" stroke-width="1"/>';
            var lbl = val >= 1000000 ? (val/1000000).toFixed(val>=10000000?0:1)+'M' : (val/1000).toFixed(0)+'k';
            svg += '<text x="'+(padL-4)+'" y="'+(yy+3)+'" text-anchor="end" font-size="9" fill="var(--fg-3)">$'+lbl+'</text>';
        }

        // Pace line (meta mensual)
        if(monthlyMeta > 0){
            var yp = y(monthlyMeta);
            if(yp >= padT && yp <= padT+innerH){
                svg += '<line x1="'+padL+'" y1="'+yp+'" x2="'+(w-padR)+'" y2="'+yp+'" stroke="var(--mam-red)" stroke-width="1.5" stroke-dasharray="5,4"/>';
                svg += '<text x="'+(w-padR-4)+'" y="'+(yp-4)+'" text-anchor="end" font-size="9" font-weight="700" fill="var(--mam-red)">Meta: $'+(monthlyMeta/1000000).toFixed(1)+'M/mes</text>';
            }
        }

        svg += '<defs><linearGradient id="salesBar" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="var(--mam-blue-petroleo)"/><stop offset="100%" stop-color="var(--mam-blue-dark)"/></linearGradient></defs>';

        // Bars: ventas + cobros side by side per month
        var groupW = barW - 8;
        var bw = groupW * 0.48;
        var gap = groupW * 0.04;
        for(var i=0;i<n;i++){
            var past = (i+1) <= selectedMonth;
            var future = !past;
            var isCurrent = (chartYear === currentYearNum && (i+1) === currentMonthNum);
            var vs = dailySales[i];
            var cb = dailyCobros[i];
            var vBarH = Math.max(0, padT + innerH - y(vs));
            var cBarH = Math.max(0, padT + innerH - y(cb));
            var xs = xStart(i);

            // Ventas bar
            svg += '<rect x="'+xs+'" y="'+y(vs)+'" width="'+bw+'" height="'+vBarH+'" rx="3" fill="'+(future?'var(--mam-gray-300)':'url(#salesBar)')+'" opacity="'+(future?0.5:1)+'"/>';
            // Cobros bar
            svg += '<rect x="'+(xs+bw+gap)+'" y="'+y(cb)+'" width="'+bw+'" height="'+cBarH+'" rx="3" fill="'+(future?'var(--pill-warning-bg)':'var(--mam-yellow)')+'" opacity="'+(future?0.5:1)+'"/>';

            // Month label
            svg += '<text x="'+(xs+groupW/2)+'" y="'+(padT+innerH+14)+'" text-anchor="middle" font-size="10" font-weight="'+(past?'700':'400')+'" fill="'+(isCurrent?'var(--mam-blue-dark)':(past?'var(--mam-gray-dark)':'var(--mam-gray-300)'))+'">'+mNames[i]+'</text>';
            // Value label above bar
            if(past && vs > 0){
                svg += '<text x="'+(xs+groupW/2)+'" y="'+(padT+innerH+28)+'" text-anchor="middle" font-size="9" font-weight="700" fill="var(--mam-blue-dark)">$'+(vs/1000000).toFixed(vs>=10000000?0:1)+'M</text>';
            }
        }

        svg += '</svg>';
        container.innerHTML = svg;
    }
    $(function(){ renderDailyChart(); $(window).on('resize', renderDailyChart); });

    function fmt(n){ return Number(n||0).toLocaleString('es-CO',{maximumFractionDigits:0}); }
    function esc(s){ var d=document.createElement('div'); d.appendChild(document.createTextNode(s||'')); return d.innerHTML; }

    $(document).on('click', '.btn-vendor-detail', function(){
        var row = $(this);
        var vid = row.data('vendor');
        var name = row.data('name');
        var ventas = parseFloat(row.data('ventas'));
        var meta = parseFloat(row.data('meta'));
        var pct = parseFloat(row.data('pct'));
        var cobros = parseFloat(row.data('cobros'));
        var conv = parseFloat(row.data('conv'));
        var budgets = parseInt(row.data('budgets'));
        var invoices = parseInt(row.data('invoices'));
        var active = parseInt(row.data('active'));
        var assigned = parseInt(row.data('assigned'));
        var health = parseInt(row.data('health'));
        var tend = parseInt(row.data('tend'));
        var proy = parseFloat(row.data('proy'));
        var proyEst = row.data('proyest');
        var clients = vendorClients[vid] || [];
        var monthly = vendorMonthly[vid] || [];
        var periodClients = vendorPeriodClients[vid] || [];
        window._vmVendorId = vid;

        $('#vmTitle').text(name);
        $('#vmSubtitle').text(clients.length + ' clientes · <?= $monthName ?> <?= $year ?>');
        $('#vendorModal').removeClass('hidden');

        // Header KPIs — glass cards inside the gradient header
        var metaC = pct >= 100 ? 'var(--mam-green-program)' : (pct >= 60 ? 'var(--mam-yellow)' : 'var(--mam-red)');
        var healthC = health >= 75 ? 'var(--mam-green-program)' : (health >= 50 ? 'var(--mam-yellow)' : 'var(--mam-red)');
        var tendC = tend > 0 ? 'var(--mam-green)' : (tend < 0 ? 'var(--mam-red-light)' : 'var(--fg-3)');
        var activPct = assigned > 0 ? Math.round((active/assigned)*100) : 0;
        var pctCobro = meta > 0 ? Math.round((cobros/meta)*100) : 0;

        // Store data globally for tab switching
        window._vmData = {ventas:ventas, meta:meta, pct:pct, cobros:cobros, conv:conv, budgets:budgets,
            invoices:invoices, active:active, assigned:assigned, health:health, tend:tend,
            proy:proy, proyEst:proyEst, clients:clients, monthly:monthly, periodClients:periodClients,
            metaC:metaC, healthC:healthC, tendC:tendC, activPct:activPct, pctCobro:pctCobro, name:name};

        var kh = '';
        var tabStyle = 'cursor:pointer; transition:all 0.15s;';
        var activeTab = 'background:rgba(255,255,255,0.22); box-shadow:0 0 0 1px rgba(255,255,255,0.3);';
        var inactiveTab = 'background:rgba(255,255,255,0.08);';

        // Ventas tab
        kh += '<div class="rounded-lg p-3 vm-tab" data-tab="ventas" style="'+tabStyle+activeTab+'">';
        kh += '<p class="text-xs text-blue-200 font-semibold uppercase">Ventas</p>';
        kh += '<p class="text-xl font-black text-white mt-1">$'+fmt(ventas)+'</p>';
        kh += '<div class="w-full rounded-full h-1 mt-2" style="background:rgba(255,255,255,0.2);"><div class="h-1 rounded-full" style="width:'+Math.min(pct,100)+'%;background:'+metaC+';"></div></div>';
        kh += '<p class="text-xs text-blue-300 mt-1">'+pct+'% de meta</p></div>';
        // Cobros tab
        kh += '<div class="rounded-lg p-3 vm-tab" data-tab="cobros" style="'+tabStyle+inactiveTab+'">';
        kh += '<p class="text-xs text-blue-200 font-semibold uppercase">Cobros</p>';
        kh += '<p class="text-xl font-black text-white mt-1">$'+fmt(cobros)+'</p>';
        kh += '<p class="text-xs text-blue-300 mt-1">'+pctCobro+'% de meta</p></div>';
        // Tendencia tab
        kh += '<div class="rounded-lg p-3 vm-tab" data-tab="tendencia" style="'+tabStyle+inactiveTab+'">';
        kh += '<p class="text-xs text-blue-200 font-semibold uppercase">Tendencia</p>';
        kh += '<p class="text-xl font-black mt-1" style="color:'+tendC+';">'+(tend>=0?'↑':'↓')+' '+Math.abs(tend)+'%</p>';
        kh += '<p class="text-xs text-blue-300 mt-1">Conv. '+conv+'%</p></div>';
        // Clientes tab
        kh += '<div class="rounded-lg p-3 vm-tab" data-tab="clientes" style="'+tabStyle+inactiveTab+'">';
        kh += '<p class="text-xs text-blue-200 font-semibold uppercase">Clientes</p>';
        kh += '<p class="text-xl font-black text-white mt-1">'+active+' <span class="text-sm font-normal text-blue-300">/ '+assigned+'</span></p>';
        kh += '<p class="text-xs text-blue-300 mt-1">'+activPct+'% activos</p></div>';
        // Salud tab
        kh += '<div class="rounded-lg p-3 vm-tab flex flex-col items-center justify-center" data-tab="salud" style="'+tabStyle+inactiveTab+'">';
        kh += '<p class="text-xs text-blue-200 font-semibold uppercase">Salud</p>';
        kh += '<div class="mt-1" style="width:48px;height:48px;border-radius:50%;background:conic-gradient('+healthC+' 0% '+health+'%, rgba(255,255,255,0.15) '+health+'% 100%);display:flex;align-items:center;justify-content:center;">';
        kh += '<div style="width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;">';
        kh += '<span class="text-sm font-black text-white">'+health+'</span></div></div></div>';
        // Brecha tab — cierre de meta
        var faltante = Math.max(0, meta - ventas);
        var brechaC = faltante === 0 ? 'var(--mam-green-program)' : 'var(--mam-orange)';
        kh += '<div class="rounded-lg p-3 vm-tab" data-tab="brecha" style="'+tabStyle+inactiveTab+'">';
        kh += '<p class="text-xs text-blue-200 font-semibold uppercase">Brecha</p>';
        kh += '<p class="text-xl font-black text-white mt-1">$'+fmt(faltante)+'</p>';
        kh += '<p class="text-xs mt-1" style="color:'+brechaC+';font-weight:700;">'+(faltante===0?'Meta cubierta':'Por cerrar')+'</p></div>';

        $('#vmHeaderKpis').html(kh);

        // Render default tab
        renderVmTab('ventas');
    });

    // Tab click handler
    $(document).on('click', '.vm-tab', function(){
        var tab = $(this).data('tab');
        $('.vm-tab').css({'background':'rgba(255,255,255,0.08)','box-shadow':'none'});
        $(this).css({'background':'rgba(255,255,255,0.22)','box-shadow':'0 0 0 1px rgba(255,255,255,0.3)'});
        renderVmTab(tab);
    });

    function renderVmTab(tab){
        var d = window._vmData;
        var clients = d.clients;
        var monthly = d.monthly;
        var html = '';

        if(tab === 'ventas'){
            html += buildMonthlyChart(monthly);
            var periodClients = d.periodClients || [];
            var totalPeriod = 0;
            for(var pi=0; pi<periodClients.length; pi++) totalPeriod += periodClients[pi].revenue;
            var ticketProm = periodClients.length > 0 ? (d.ventas / Math.max(d.invoices,1)) : 0;
            html += '<div class="bg-white rounded-lg p-4 shadow-sm mb-4">';
            html += '<div class="grid grid-cols-3 gap-4 text-center">';
            html += '<div><p class="text-2xl font-black" style="color:var(--mam-blue-dark);">'+periodClients.length+'</p><p class="text-xs text-gray-400">Clientes que compraron</p></div>';
            html += '<div><p class="text-2xl font-black" style="color:var(--mam-blue-petroleo);">'+d.invoices+'</p><p class="text-xs text-gray-400">Facturas</p></div>';
            html += '<div><p class="text-2xl font-black text-purple-600">$'+fmt(ticketProm)+'</p><p class="text-xs text-gray-400">Ticket promedio</p></div>';
            html += '</div></div>';
            window._vmClients = periodClients; window._vmSortDir = {}; window._vmMode = 'period';
            html += buildClientTable(periodClients.length, 'revenue', 'period');
            $('#vmBody').html(html); renderVmClients(periodClients, 'period'); return;
        }
        window._vmMode = 'rfm';
        if(tab === 'cobros'){
            html += '<div class="bg-white rounded-lg p-4 shadow-sm mb-4">';
            html += '<div class="grid grid-cols-3 gap-4 text-center">';
            html += '<div><p class="text-2xl font-black" style="color:var(--mam-blue-dark);">$'+fmt(d.ventas)+'</p><p class="text-xs text-gray-400">Facturado</p></div>';
            html += '<div><p class="text-2xl font-black text-orange-500">$'+fmt(d.cobros)+'</p><p class="text-xs text-gray-400">Cobrado</p></div>';
            html += '<div><p class="text-2xl font-black text-red-500">$'+fmt(d.ventas - d.cobros)+'</p><p class="text-xs text-gray-400">Pendiente</p></div>';
            html += '</div>';
            if(d.meta > 0){
                html += '<div class="w-full bg-gray-100 rounded-full h-3 mt-3"><div class="h-3 rounded-full" style="width:'+Math.min(d.pctCobro,100)+'%;background:'+(d.pctCobro>=80?'var(--mam-green-program)':(d.pctCobro>=50?'var(--mam-yellow)':'var(--mam-red)'))+';"></div></div>';
                html += '<p class="text-xs text-gray-400 mt-1 text-center">'+d.pctCobro+'% de meta</p>';
            }
            html += '</div>';
            var sorted = clients.slice().sort(function(a,b){ return b.days - a.days; });
            window._vmClients = sorted; window._vmSortDir = {};
            html += buildClientTable(sorted.length, 'days');
            $('#vmBody').html(html); renderVmClients(sorted); return;
        }
        if(tab === 'tendencia'){
            html += buildMonthlyChart(monthly);
            html += '<div class="bg-white rounded-lg p-4 shadow-sm mb-4">';
            html += '<div class="grid grid-cols-3 gap-4 text-center">';
            html += '<div><p class="text-2xl font-black" style="color:var(--mam-blue-dark);">'+d.budgets+'</p><p class="text-xs text-gray-400">Presupuestos</p></div>';
            html += '<div><p class="text-2xl font-black text-green-600">'+d.invoices+'</p><p class="text-xs text-gray-400">Facturas</p></div>';
            html += '<div><p class="text-2xl font-black" style="color:'+(d.conv>=70?'var(--mam-green-program)':(d.conv>=40?'var(--mam-yellow)':'var(--mam-red)'))+';">'+d.conv+'%</p><p class="text-xs text-gray-400">Conversion</p></div>';
            html += '</div></div>';
            $('#vmBody').html(html); return;
        }
        if(tab === 'clientes'){
            var activos = clients.filter(function(c){ return c.days <= 90; });
            var inactivos = clients.filter(function(c){ return c.days > 90; });
            var sinCompra = Math.max(0, d.assigned - clients.length);

            html += '<div class="grid grid-cols-3 gap-3 mb-4">';
            html += '<div class="bg-white rounded-lg p-4 shadow-sm text-center"><p class="text-3xl font-black text-green-600">'+activos.length+'</p><p class="text-xs text-gray-400">Activos (90d)</p></div>';
            html += '<div class="bg-white rounded-lg p-4 shadow-sm text-center"><p class="text-3xl font-black text-red-500">'+inactivos.length+'</p><p class="text-xs text-gray-400">Inactivos (>90d)</p></div>';
            html += '<div class="bg-white rounded-lg p-4 shadow-sm text-center"><p class="text-3xl font-black text-gray-400">'+sinCompra+'</p><p class="text-xs text-gray-400">Sin compras</p></div>';
            html += '</div>';

            if(inactivos.length > 0){
                html += '<p class="text-xs font-bold uppercase text-red-500 mb-2">Inactivos (>90 dias) — requieren atencion</p>';
                html += buildSimpleList(inactivos);
            }
            html += '<p class="text-xs font-bold uppercase text-green-600 mb-2 mt-3">Activos</p>';
            html += buildSimpleList(activos);
            $('#vmBody').html(html); return;
        }
        if(tab === 'brecha'){
            $('#vmBody').html('<div class="bg-white rounded-lg p-8 shadow-sm text-center"><p class="text-sm text-gray-400">Calculando oportunidades...</p></div>');
            var vid = window._vmVendorId;
            $.get('<?= base_url() ?>sisvent/admin/salesboard/gap', {vendor:vid, year:<?= $year ?>, month:<?= $month ?>, store:'<?= $storeFilter ?>'}, function(resp){
                renderBrechaTab(resp);
            }, 'json').fail(function(){
                $('#vmBody').html('<div class="bg-white rounded-lg p-8 shadow-sm text-center"><p class="text-sm text-red-500">Error al cargar brecha.</p></div>');
            });
            return;
        }
        if(tab === 'salud'){
            var segments = {}, types = {A:0,B:0,C:0,D:0};
            var hr = {excellent:0,good:0,fair:0,critical:0};
            for(var i=0;i<clients.length;i++){
                var c = clients[i];
                if(!segments[c.segment]) segments[c.segment] = {count:0, color:c.seg_color};
                segments[c.segment].count++;
                if(types[c.type] !== undefined) types[c.type]++;
                if(c.health >= 75) hr.excellent++; else if(c.health >= 50) hr.good++; else if(c.health >= 25) hr.fair++; else hr.critical++;
            }
            // Health bars
            html += '<div class="bg-white rounded-lg p-4 shadow-sm mb-4">';
            html += '<p class="text-xs text-gray-400 font-semibold uppercase mb-3">Distribucion de Salud</p>';
            var hBars = [{l:'Excelente (75-100)',c:hr.excellent,co:'var(--mam-green-program)'},{l:'Buena (50-74)',c:hr.good,co:'var(--mam-blue-petroleo)'},{l:'Regular (25-49)',c:hr.fair,co:'var(--mam-yellow)'},{l:'Critica (0-24)',c:hr.critical,co:'var(--mam-red)'}];
            var mxH = Math.max.apply(null,hBars.map(function(b){return b.c;}))||1;
            for(var i=0;i<hBars.length;i++){
                var b = hBars[i]; var w = (b.c/mxH)*100;
                html += '<div class="mb-2"><div class="flex justify-between text-xs mb-1"><span class="text-gray-600">'+b.l+'</span><span class="font-bold" style="color:'+b.co+';">'+b.c+'</span></div>';
                html += '<div class="w-full bg-gray-100 rounded-full h-4"><div class="h-4 rounded-full" style="width:'+Math.max(w,3)+'%;background:'+b.co+';"></div></div></div>';
            }
            var tc = {A:'var(--mam-green-program)',B:'var(--mam-yellow)',C:'var(--mam-red)',D:'var(--mam-gray-dark)'};
            html += '<div class="flex items-center gap-3 mt-3 pt-3" style="border-top:1px solid var(--mam-gray-300);">';
            for(var t in types) html += '<div class="flex items-center gap-1"><span class="inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white rounded-full" style="background:'+tc[t]+';">'+t+'</span><span class="text-sm font-bold text-gray-700">'+types[t]+'</span></div>';
            html += '</div></div>';
            // Segments
            html += '<div class="bg-white rounded-lg p-4 shadow-sm mb-4">';
            html += '<p class="text-xs text-gray-400 font-semibold uppercase mb-3">Segmentos RFM</p>';
            var segArr = Object.keys(segments).map(function(k){return {name:k,count:segments[k].count,color:segments[k].color};}).sort(function(a,b){return b.count-a.count;});
            for(var i=0;i<segArr.length;i++){
                var s = segArr[i]; var sw = clients.length > 0 ? (s.count/clients.length)*100 : 0;
                html += '<div class="flex items-center gap-2 mb-1.5"><span class="inline-block w-3 h-3 rounded-full flex-shrink-0" style="background:'+s.color+';"></span>';
                html += '<span class="text-xs text-gray-600 flex-1">'+s.name+'</span><span class="text-xs font-bold text-gray-800">'+s.count+'</span>';
                html += '<div class="w-20 bg-gray-100 rounded-full h-2"><div class="h-2 rounded-full" style="width:'+sw+'%;background:'+s.color+';"></div></div></div>';
            }
            html += '</div>';
            var sorted = clients.slice().sort(function(a,b){ return a.health - b.health; });
            window._vmClients = sorted; window._vmSortDir = {};
            html += buildClientTable(sorted.length, 'health');
            $('#vmBody').html(html); renderVmClients(sorted); return;
        }
    }

    function buildMonthlyChart(monthly){
        if(!monthly || monthly.length === 0) return '';
        var h = '<div class="bg-white rounded-lg p-4 shadow-sm mb-4"><p class="text-xs text-gray-400 font-semibold uppercase mb-3">Evolucion Mensual</p>';
        h += '<div class="flex items-end gap-2" style="height:80px;">';
        var mx = 0; for(var i=0;i<monthly.length;i++) if(monthly[i].v > mx) mx = monthly[i].v;
        for(var i=0;i<monthly.length;i++){
            var m = monthly[i]; var p = mx > 0 ? (m.v/mx)*100 : 0; var last = (i === monthly.length-1);
            h += '<div class="flex-1 flex flex-col items-center gap-1"><span class="text-xs font-bold '+(last?'':'text-gray-400')+'" style="'+(last?'color:var(--mam-blue-dark);':'')+'">$'+fmt(m.v/1000000)+'M</span>';
            h += '<div class="w-full rounded-md" style="height:'+Math.max(p,6)+'%;background:'+(last?'linear-gradient(180deg,var(--mam-blue-petroleo),var(--mam-blue-dark))':'var(--mam-gray-300)')+';"></div>';
            h += '<span class="text-xs '+(last?'font-bold':'text-gray-400')+'" style="'+(last?'color:var(--mam-blue-dark);':'')+'">'+m.m.substring(0,2)+'</span></div>';
        }
        h += '</div></div>'; return h;
    }

    function buildClientTable(count, activeSort, mode){
        mode = mode || 'rfm';
        var h = '<div class="bg-white rounded-lg shadow-sm overflow-hidden">';
        h += '<div class="flex items-center justify-between px-4 py-3" style="border-bottom:1px solid var(--mam-gray-300);">';
        var label = mode === 'period' ? (count+' Clientes · '+periodLabel) : (count+' Clientes');
        h += '<p class="text-xs text-gray-400 font-semibold uppercase">'+label+'</p>';
        h += '<div class="flex gap-1">';
        var sorts = mode === 'period'
            ? [{key:'revenue',label:'Ventas'},{key:'invoices',label:'Facturas'},{key:'delta',label:'Δ vs prev'},{key:'name',label:'A-Z'}]
            : [{key:'revenue',label:'Revenue'},{key:'health',label:'Salud'},{key:'days',label:'Dias'},{key:'type',label:'Tipo'},{key:'name',label:'A-Z'}];
        for(var i=0;i<sorts.length;i++){
            var a = sorts[i].key === activeSort;
            h += '<button class="btn-sort-clients px-2.5 py-1 rounded-full text-xs font-medium transition-colors" style="background:'+(a?'var(--mam-blue-dark)':'var(--mam-gray-200)')+';color:'+(a?'white':'var(--mam-gray-dark)')+';" data-sort="'+sorts[i].key+'">'+sorts[i].label+'</button>';
        }
        h += '</div></div><div id="vmClientTable"></div></div>'; return h;
    }

    function buildSimpleList(clients){
        if(!clients || clients.length === 0) return '<p class="text-xs text-gray-400 py-2">Ninguno.</p>';
        var tc = {A:'var(--mam-green-program)',B:'var(--mam-yellow)',C:'var(--mam-red)',D:'var(--mam-gray-dark)'};
        var h = '<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-2">';
        for(var i=0;i<Math.min(clients.length,50);i++){
            var c = clients[i];
            var hc = c.health >= 75 ? 'var(--mam-green-program)' : (c.health >= 50 ? 'var(--mam-yellow)' : 'var(--mam-red)');
            var dBg = c.days > 90 ? 'var(--pill-danger-bg)' : (c.days > 45 ? 'var(--pill-warning-bg)' : 'var(--pill-success-bg)');
            var dTx = c.days > 90 ? 'var(--pill-danger-fg)' : (c.days > 45 ? 'var(--pill-warning-fg)' : 'var(--pill-success-fg)');
            h += '<div class="flex items-center px-4 py-2 hover:bg-blue-50 transition-colors" style="border-bottom:1px solid var(--mam-gray-200);">';
            h += '<div class="flex-1 min-w-0"><p class="text-sm font-semibold text-gray-800 truncate">'+esc(c.name)+'</p>';
            h += '<span class="inline-block px-2 py-0.5 rounded-full text-white font-semibold" style="font-size:8px;background:'+c.seg_color+';">'+c.segment+'</span></div>';
            h += '<span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold text-white mx-1" style="background:'+hc+';">'+c.health+'</span>';
            h += '<span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-bold text-white mx-1" style="background:'+(tc[c.type]||'var(--mam-gray-dark)')+';">'+c.type+'</span>';
            h += '<span class="text-sm font-bold mx-2" style="color:var(--mam-blue-dark);min-width:80px;text-align:right;">$'+fmt(c.revenue)+'</span>';
            h += '<span class="inline-block px-2 py-1 rounded-lg text-xs font-bold" style="background:'+dBg+';color:'+dTx+';min-width:40px;text-align:center;">'+c.days+'d</span>';
            h += '</div>';
        }
        h += '</div>'; return h;
    }

    function renderVmClients(clients, mode){
        mode = mode || 'rfm';
        if(!clients || clients.length === 0){
            var msg = mode === 'period' ? 'Sin ventas en el periodo.' : 'Sin datos RFM.';
            $('#vmClientTable').html('<p class="text-sm text-gray-400 py-6 text-center">'+msg+'</p>');
            return;
        }
        if(mode === 'period') return renderVmPeriodClients(clients);

        var tc = {A:'var(--mam-green-program)',B:'var(--mam-yellow)',C:'var(--mam-red)',D:'var(--mam-gray-dark)'};
        var h = '<table class="w-full" style="border-collapse:collapse;">';
        h += '<tbody>';
        for(var i=0; i<Math.min(clients.length, 100); i++){
            var c = clients[i];
            var hc = c.health >= 75 ? 'var(--mam-green-program)' : (c.health >= 50 ? 'var(--mam-yellow)' : 'var(--mam-red)');
            h += '<tr class="hover:bg-blue-50 transition-colors" style="border-bottom:1px solid var(--mam-gray-200);">';
            // Name + segment
            h += '<td class="py-2.5 px-4" style="min-width:200px;">';
            h += '<p class="text-sm font-semibold text-gray-800">'+esc(c.name)+'</p>';
            h += '<span class="inline-block px-2 py-0.5 rounded-full text-white font-semibold mt-0.5" style="font-size:9px; background:'+c.seg_color+';">'+c.segment+'</span>';
            h += '</td>';
            // Health + Type
            h += '<td class="py-2.5 px-2 text-center" style="width:70px;">';
            h += '<span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold text-white" style="background:'+hc+';">'+c.health+'</span>';
            h += '</td>';
            h += '<td class="py-2.5 px-2 text-center" style="width:40px;">';
            h += '<span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold text-white" style="background:'+(tc[c.type]||'var(--mam-gray-dark)')+';">'+c.type+'</span>';
            h += '</td>';
            // RFM scores as mini bar
            h += '<td class="py-2.5 px-2" style="width:90px;">';
            h += '<div class="flex gap-1 items-end" style="height:24px;">';
            var rfmColors = ['var(--mam-blue-petroleo)','var(--mam-green-program)','var(--mam-purple)'];
            var rfmLabels = ['R','F','M'];
            var rfmVals = [c.r, c.f, c.m];
            for(var ri=0;ri<3;ri++){
                var barH = (rfmVals[ri]/5)*100;
                h += '<div class="flex flex-col items-center" style="width:22px;">';
                h += '<div class="w-full rounded-sm" style="height:'+Math.max(barH,15)+'%;background:'+rfmColors[ri]+';"></div>';
                h += '<span class="text-xs text-gray-400 mt-0.5" style="font-size:8px;">'+rfmLabels[ri]+':'+rfmVals[ri]+'</span>';
                h += '</div>';
            }
            h += '</div></td>';
            // Revenue
            h += '<td class="py-2.5 px-3 text-right" style="width:120px;">';
            h += '<p class="text-sm font-bold" style="color:var(--mam-blue-dark);">$'+fmt(c.revenue)+'</p>';
            h += '<p class="text-xs text-gray-400">12 meses</p></td>';
            // Days
            h += '<td class="py-2.5 px-3 text-right" style="width:70px;">';
            var daysBg = c.days > 90 ? 'var(--pill-danger-bg)' : (c.days > 45 ? 'var(--pill-warning-bg)' : 'var(--pill-success-bg)');
            var daysTx = c.days > 90 ? 'var(--pill-danger-fg)' : (c.days > 45 ? 'var(--pill-warning-fg)' : 'var(--pill-success-fg)');
            h += '<span class="inline-block px-2 py-1 rounded-lg text-xs font-bold" style="background:'+daysBg+';color:'+daysTx+';">'+c.days+'d</span>';
            h += '</td>';
            h += '</tr>';
        }
        h += '</tbody></table>';
        if(clients.length > 100) h += '<p class="text-xs text-gray-400 text-center py-2">Mostrando 100 de '+clients.length+'</p>';
        $('#vmClientTable').html(h);
    }

    function renderVmPeriodClients(clients){
        var total = 0;
        for(var i=0;i<clients.length;i++) total += clients[i].revenue;
        var h = '<table class="w-full" style="border-collapse:collapse;">';
        h += '<thead><tr class="text-gray-400 uppercase" style="font-size:10px;background:var(--mam-gray-150);border-bottom:1px solid var(--mam-gray-300);">';
        h += '<th class="text-left px-4 py-2">Cliente</th>';
        h += '<th class="text-right px-2 py-2">Ventas</th>';
        h += '<th class="text-center px-2 py-2">%</th>';
        h += '<th class="text-center px-2 py-2">Facturas</th>';
        h += '<th class="text-right px-2 py-2">Cobros</th>';
        h += '<th class="text-center px-2 py-2">vs. prev</th>';
        h += '<th class="text-right px-3 py-2">Última</th>';
        h += '</tr></thead><tbody>';
        var max = Math.min(clients.length, 100);
        for(var i=0; i<max; i++){
            var c = clients[i];
            var pctTot = total > 0 ? (c.revenue / total) * 100 : 0;
            var deltaC = c.delta > 0 ? 'var(--mam-green-program)' : (c.delta < 0 ? 'var(--mam-red)' : 'var(--fg-3)');
            var deltaIcon = c.delta > 0 ? '↑' : (c.delta < 0 ? '↓' : '→');
            var isNew = (c.prev_revenue === 0 && c.revenue > 0);
            h += '<tr class="hover:bg-blue-50 transition-colors" style="border-bottom:1px solid var(--mam-gray-200);">';
            h += '<td class="py-2.5 px-4" style="min-width:220px;">';
            h += '<p class="text-sm font-semibold text-gray-800">'+esc(c.name)+'</p>';
            if(isNew) h += '<span class="inline-block px-2 py-0.5 rounded-full text-white font-semibold mt-0.5" style="font-size:9px;background:var(--mam-purple);">Nuevo en el periodo</span>';
            h += '</td>';
            // Ventas
            h += '<td class="py-2.5 px-2 text-right" style="width:120px;">';
            h += '<p class="text-sm font-bold" style="color:var(--mam-blue-dark);">$'+fmt(c.revenue)+'</p></td>';
            // % del total
            h += '<td class="py-2.5 px-2" style="width:80px;">';
            h += '<div class="flex items-center gap-1 justify-center">';
            h += '<div class="bg-gray-100 rounded-full h-1.5" style="width:40px;"><div class="h-1.5 rounded-full" style="width:'+Math.min(pctTot,100)+'%;background:var(--mam-blue-petroleo);"></div></div>';
            h += '<span class="text-xs text-gray-500 font-semibold" style="min-width:28px;text-align:right;">'+pctTot.toFixed(1)+'%</span>';
            h += '</div></td>';
            // Facturas
            h += '<td class="py-2.5 px-2 text-center" style="width:60px;"><span class="text-sm font-semibold text-gray-700">'+c.invoices+'</span></td>';
            // Cobros
            h += '<td class="py-2.5 px-2 text-right" style="width:110px;">';
            h += '<p class="text-sm font-medium '+(c.cobros>0?'text-orange-600':'text-gray-300')+'">$'+fmt(c.cobros)+'</p></td>';
            // Delta
            h += '<td class="py-2.5 px-2 text-center" style="width:80px;">';
            if(isNew){
                h += '<span class="text-xs font-bold" style="color:var(--mam-purple);">nuevo</span>';
            } else {
                h += '<span class="text-xs font-bold" style="color:'+deltaC+';">'+deltaIcon+' '+Math.abs(c.delta)+'%</span>';
            }
            h += '</td>';
            // Last date
            h += '<td class="py-2.5 px-3 text-right text-xs text-gray-500" style="width:80px;">'+(c.last_date ? c.last_date.substring(0,10) : '—')+'</td>';
            h += '</tr>';
        }
        h += '</tbody>';
        // Footer with total
        h += '<tfoot><tr style="background:var(--mam-gray-150);border-top:2px solid var(--mam-gray-300);">';
        h += '<td class="py-2 px-4 text-sm font-bold text-gray-700">Total</td>';
        h += '<td class="py-2 px-2 text-right text-sm font-black" style="color:var(--mam-blue-dark);">$'+fmt(total)+'</td>';
        h += '<td colspan="5"></td></tr></tfoot>';
        h += '</table>';
        if(clients.length > 100) h += '<p class="text-xs text-gray-400 text-center py-2">Mostrando 100 de '+clients.length+'</p>';
        $('#vmClientTable').html(h);
    }

    function renderBrechaTab(d){
        var html = '';
        var faltante = d.faltante || 0;
        var coverage = d.coverage || 0;
        var oppTotal = d.oppTotal || 0;
        var covC = coverage >= 100 ? 'var(--mam-green-program)' : (coverage >= 60 ? 'var(--mam-yellow)' : 'var(--mam-red)');

        // Summary banner
        html += '<div class="bg-white rounded-lg shadow-sm p-4 mb-4">';
        html += '<div class="flex items-center justify-between gap-4">';
        html += '<div class="flex-1">';
        html += '<p class="text-xs text-gray-400 font-bold uppercase">Faltante para meta</p>';
        html += '<p class="text-3xl font-black" style="color:var(--mam-blue-dark);">$'+fmt(faltante)+'</p>';
        html += '<p class="text-xs text-gray-500 mt-1">Vendido: $'+fmt(d.ventasMes)+' · Meta: $'+fmt(d.meta)+'</p>';
        html += '</div><div class="flex-1 text-center">';
        html += '<p class="text-xs text-gray-400 font-bold uppercase">Oportunidad identificada</p>';
        html += '<p class="text-3xl font-black" style="color:'+covC+';">$'+fmt(oppTotal)+'</p>';
        html += '<p class="text-xs font-bold mt-1" style="color:'+covC+';">'+coverage+'% de la brecha cubierta</p>';
        html += '</div></div>';
        // Coverage bar
        html += '<div class="mt-3 rounded-full overflow-hidden" style="background:var(--mam-gray-200);height:10px;">';
        html += '<div style="height:10px;width:'+Math.min(coverage,100)+'%;background:linear-gradient(90deg,'+covC+',var(--mam-blue-petroleo));"></div>';
        html += '</div>';
        html += '</div>';

        // Buckets summary cards
        var b = d.buckets;
        var bucketDefs = [
            {key:'repetidores', label:'Repetidores pendientes', color:'var(--mam-blue-petroleo)', icon:'↻', desc:'Compraron los ultimos meses, aun no este mes'},
            {key:'enRiesgo', label:'En riesgo recuperables', color:'var(--mam-red)', icon:'!', desc:'Segmento RFM de riesgo, rescatables con contacto'},
            {key:'hibernando', label:'Dormidos (90-365d)', color:'var(--mam-purple)', icon:'z', desc:'Sin compra hace mas de 90 dias, reactivables'},
            {key:'pipeline', label:'Pipeline abierto', color:'var(--mam-yellow)', icon:'$', desc:'Presupuestos sin facturar, cierre esperado ~40%'},
        ];
        html += '<div class="grid grid-cols-2 gap-3 mb-4">';
        for(var i=0;i<bucketDefs.length;i++){
            var bd = bucketDefs[i];
            var bk = b[bd.key];
            var cnt = bd.key === 'pipeline' ? bk.count : bk.count;
            var opp = bk.opportunity;
            var label2 = bd.key === 'pipeline' ? (bk.count + ' presupuestos · $' + fmt(bk.value)) : (cnt + ' clientes');
            html += '<button class="btn-brecha-bucket text-left bg-white rounded-lg shadow-sm p-3 hover:shadow-md transition-all" data-bucket="'+bd.key+'" style="border-left:4px solid '+bd.color+';">';
            html += '<div class="flex items-start justify-between">';
            html += '<div class="flex-1"><p class="text-xs text-gray-400 font-bold uppercase">'+bd.label+'</p>';
            html += '<p class="text-2xl font-black mt-1" style="color:'+bd.color+';">$'+fmt(opp)+'</p>';
            html += '<p class="text-xs text-gray-500 mt-1">'+label2+'</p></div>';
            html += '<span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-white font-black" style="background:'+bd.color+';">'+bd.icon+'</span>';
            html += '</div>';
            html += '<p class="text-xs text-gray-400 mt-2">'+bd.desc+'</p>';
            html += '</button>';
        }
        html += '</div>';

        // Stacked bar: distribution of opportunity
        if(oppTotal > 0){
            html += '<div class="bg-white rounded-lg shadow-sm p-4 mb-4">';
            html += '<p class="text-xs text-gray-400 font-bold uppercase mb-2">Composicion de la oportunidad</p>';
            html += '<div class="flex rounded-full overflow-hidden" style="height:18px;background:var(--mam-gray-200);">';
            for(var i=0;i<bucketDefs.length;i++){
                var bd = bucketDefs[i];
                var opp = b[bd.key].opportunity;
                var pct = (opp/oppTotal)*100;
                if(pct < 0.5) continue;
                html += '<div title="'+bd.label+': $'+fmt(opp)+'" style="width:'+pct+'%;background:'+bd.color+';"></div>';
            }
            html += '</div>';
            html += '<div class="flex flex-wrap gap-3 mt-2 text-xs">';
            for(var i=0;i<bucketDefs.length;i++){
                var bd = bucketDefs[i];
                var opp = b[bd.key].opportunity;
                if(opp <= 0) continue;
                var pct = Math.round((opp/oppTotal)*100);
                html += '<span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm" style="background:'+bd.color+';"></span><span class="text-gray-600">'+bd.label+' <strong>'+pct+'%</strong></span></span>';
            }
            html += '</div></div>';
        }

        // Container for drill-down
        html += '<div id="brechaDetail"></div>';

        $('#vmBody').html(html);

        // Default: show repetidores
        renderBrechaBucket('repetidores', d);
        window._vmBrecha = d;
    }

    function renderBrechaBucket(key, d){
        var b = d.buckets[key];
        var labels = {repetidores:'Repetidores pendientes',enRiesgo:'En riesgo recuperables',hibernando:'Dormidos (90-365d)',pipeline:'Pipeline abierto'};
        var color = {repetidores:'var(--mam-blue-petroleo)',enRiesgo:'var(--mam-red)',hibernando:'var(--mam-purple)',pipeline:'var(--mam-yellow)'}[key];
        var html = '<div class="bg-white rounded-lg shadow-sm overflow-hidden">';
        html += '<div class="flex items-center justify-between px-4 py-3" style="border-bottom:1px solid var(--mam-gray-300);">';
        html += '<div><p class="text-xs font-bold uppercase" style="color:'+color+';">'+labels[key]+'</p>';
        html += '<p class="text-xs text-gray-400 mt-0.5">Accion sugerida: '+actionForBucket(key)+'</p></div>';
        if(key !== 'pipeline') html += '<p class="text-xs text-gray-400">Top 20 por oportunidad</p>';
        html += '</div>';

        if(key === 'pipeline'){
            html += '<div class="p-6 text-center">';
            html += '<p class="text-4xl font-black" style="color:'+color+';">$'+fmt(b.value)+'</p>';
            html += '<p class="text-xs text-gray-400 mt-1">'+b.count+' presupuestos abiertos (state=0, no facturados)</p>';
            html += '<p class="text-sm text-gray-600 mt-3">Cierre esperado al 40% de conversion: <strong style="color:var(--mam-blue-dark);">$'+fmt(b.opportunity)+'</strong></p>';
            html += '<a href="<?= base_url() ?>sisvent/commercial/budgets?vendor='+window._vmVendorId+'&approval=pending" class="inline-block mt-3 px-4 py-2 rounded-lg text-white font-bold text-xs" style="background:'+color+';">Ver presupuestos</a>';
            html += '</div></div>';
            $('#brechaDetail').html(html);
            return;
        }

        if(!b.clients || b.clients.length === 0){
            html += '<p class="text-sm text-gray-400 text-center py-6">Sin clientes en este bucket.</p></div>';
            $('#brechaDetail').html(html);
            return;
        }

        html += '<table class="w-full" style="border-collapse:collapse;">';
        html += '<thead><tr class="text-gray-400 uppercase" style="font-size:10px;background:var(--mam-gray-150);border-bottom:1px solid var(--mam-gray-300);">';
        html += '<th class="text-left px-4 py-2">Cliente</th>';
        if(key === 'enRiesgo') html += '<th class="text-left px-2 py-2">Segmento</th>';
        html += '<th class="text-center px-2 py-2">Meses activo</th>';
        html += '<th class="text-right px-2 py-2">Oportunidad</th>';
        html += '<th class="text-center px-2 py-2">Dias sin</th>';
        html += '<th class="text-right px-3 py-2">Accion</th>';
        html += '</tr></thead><tbody>';
        for(var i=0;i<b.clients.length;i++){
            var c = b.clients[i];
            var dBg = c.days_since > 90 ? 'var(--pill-danger-bg)' : (c.days_since > 45 ? 'var(--pill-warning-bg)' : 'var(--pill-success-bg)');
            var dTx = c.days_since > 90 ? 'var(--pill-danger-fg)' : (c.days_since > 45 ? 'var(--pill-warning-fg)' : 'var(--pill-success-fg)');
            html += '<tr style="border-bottom:1px solid var(--mam-gray-200);" class="hover:bg-blue-50 transition-colors">';
            html += '<td class="py-2.5 px-4"><p class="text-sm font-semibold text-gray-800">'+esc(c.name)+'</p></td>';
            if(key === 'enRiesgo'){
                html += '<td class="py-2.5 px-2"><span class="inline-block px-2 py-0.5 rounded-full text-white font-semibold" style="font-size:9px;background:'+c.seg_color+';">'+c.segment+'</span></td>';
            }
            html += '<td class="py-2.5 px-2 text-center"><span class="text-xs font-bold text-gray-700">'+(c.months_bought || '—')+'/6</span></td>';
            html += '<td class="py-2.5 px-2 text-right"><p class="text-sm font-bold" style="color:'+color+';">$'+fmt(c.opportunity)+'</p>';
            html += '<p class="text-xs text-gray-400">~prom. '+fmt(c.avg_monthly)+'</p></td>';
            html += '<td class="py-2.5 px-2 text-center"><span class="inline-block px-2 py-1 rounded-lg text-xs font-bold" style="background:'+dBg+';color:'+dTx+';">'+c.days_since+'d</span></td>';
            html += '<td class="py-2.5 px-3 text-right">';
            html += '<a href="<?= base_url() ?>sisvent/business/clients/edit/'+c.id+'" target="_blank" class="inline-block px-2.5 py-1 rounded-full text-xs font-bold text-white hover:opacity-80" style="background:'+color+';">Ver ficha</a>';
            html += '</td></tr>';
        }
        html += '</tbody></table></div>';
        $('#brechaDetail').html(html);
    }

    function actionForBucket(key){
        return {
            repetidores: 'Llamar y ofrecer reorden antes de cierre de mes',
            enRiesgo: 'Visita personalizada + oferta especial urgente',
            hibernando: 'Campaña de reactivacion (bot WhatsApp o llamada)',
            pipeline: 'Cerrar pagos pendientes y facturar presupuestos abiertos'
        }[key] || '';
    }

    $(document).on('click', '.btn-brecha-bucket', function(){
        $('.btn-brecha-bucket').css('box-shadow','');
        $(this).css('box-shadow','0 0 0 2px var(--mam-blue-dark)');
        renderBrechaBucket($(this).data('bucket'), window._vmBrecha);
    });

    $(document).on('click', '.btn-sort-clients', function(){
        var key = $(this).data('sort');
        // Toggle direction
        if(!window._vmSortDir[key]) window._vmSortDir[key] = 'desc';
        else window._vmSortDir[key] = window._vmSortDir[key] === 'desc' ? 'asc' : 'desc';
        var dir = window._vmSortDir[key];

        // Highlight active button
        $('.btn-sort-clients').css({'background':'var(--mam-gray-200)','color':'var(--mam-gray-dark)'});
        $(this).css({'background':'var(--mam-blue-dark)','color':'white'});

        var sorted = window._vmClients.slice();
        sorted.sort(function(a, b){
            var va = a[key], vb = b[key];
            if(typeof va === 'string') { va = va.toLowerCase(); vb = (vb||'').toLowerCase(); }
            if(va < vb) return dir === 'asc' ? -1 : 1;
            if(va > vb) return dir === 'asc' ? 1 : -1;
            return 0;
        });
        renderVmClients(sorted, window._vmMode || 'rfm');
    });
    </script>
</body>
</html>
