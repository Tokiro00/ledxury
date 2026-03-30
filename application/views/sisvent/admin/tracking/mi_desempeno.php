<?php
    $role = $this->session->userdata('user_data')['role'];
    $semaforoColor = $pctMeta >= 100 ? '#22c55e' : ($pctMeta >= 60 ? '#eab308' : '#ef4444');
    $semaforoBg    = $pctMeta >= 100 ? 'bg-green-500' : ($pctMeta >= 60 ? 'bg-yellow-400' : 'bg-red-500');
    $semaforoText  = $pctMeta >= 100 ? 'text-green-600' : ($pctMeta >= 60 ? 'text-yellow-600' : 'text-red-600');

    $rolNames = [1=>'Super Admin',2=>'Administrador',3=>'Vendedor',4=>'Almacenista',8=>'Cartera',9=>'Jefe de Logistica'];
    $rolName = isset($rolNames[$role]) ? $rolNames[$role] : 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
    <title>Mi Desempeno - <?= $vendorName ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50"
         v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <?php $this->load->view('sisvent/layouts/sidebar',
            array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>

            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 mx-auto w-full max-w-4xl">

                    <!-- BIENVENIDA -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <div class="flex items-center gap-4">
                            <div class="p-4 rounded-full" style="background:#1B365D">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold" style="color:#1B365D"><?= $vendorName ?></h2>
                                <p class="text-sm text-gray-500"><?= $rolName ?> - <?= $monthName ?> <?= $year ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- VENDEDOR (3) / ADMIN (1,2): Ventas vs Meta -->
                    <!-- ============================================================ -->
                    <?php if(in_array($role, [1, 2, 3])): ?>

                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-bold text-gray-600">Ventas del Mes vs Meta Personal</h3>
                            <span class="text-2xl font-bold <?= $semaforoText ?>"><?= $pctMeta ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-6 mb-3">
                            <div class="h-6 rounded-full <?= $semaforoBg ?> flex items-center justify-end pr-2 transition-all duration-500"
                                 style="width:<?= min($pctMeta, 100) ?>%">
                                <?php if($pctMeta >= 15): ?>
                                <span class="text-xs font-bold text-white"><?= $pctMeta ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <div>
                                <span class="text-gray-500">Ventas: </span>
                                <span class="font-bold" style="color:#1B365D">$<?= number_format($ventasAcum, 0, ',', '.') ?></span>
                            </div>
                            <div>
                                <span class="text-gray-500">Meta: </span>
                                <span class="font-bold text-gray-700">$<?= number_format($metaIndiv, 0, ',', '.') ?></span>
                            </div>
                            <div>
                                <span class="text-gray-500">Falta: </span>
                                <span class="font-bold text-red-600">$<?= number_format(max(0, $metaIndiv - $ventasAcum), 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 mb-6">
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-3 rounded-full bg-blue-500">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Proyeccion Mensual</p>
                                    <p class="text-xl font-bold text-blue-600">$<?= number_format($proyeccion, 0, ',', '.') ?></p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">Basado en el ritmo actual de ventas.</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-3 rounded-full" style="background:#7AB929">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Tu Posicion en el Ranking</p>
                                    <p class="text-xl font-bold" style="color:#1B365D">
                                        #<?= $position ?> <span class="text-sm font-normal text-gray-500">de <?= $totalVendors ?> vendedores</span>
                                    </p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">Posicion basada en ventas acumuladas del mes.</p>
                        </div>
                    </div>

                    <!-- Historico 3 meses -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <h3 class="text-sm font-bold mb-4" style="color:#1B365D">Ultimos 3 Meses</h3>
                        <div class="space-y-4">
                            <?php foreach($history as $h):
                                $hPct = ($h['meta'] > 0) ? round(($h['ventas'] / $h['meta']) * 100, 1) : 0;
                                $hColor = $hPct >= 100 ? 'bg-green-500' : ($hPct >= 60 ? 'bg-yellow-400' : 'bg-red-500');
                            ?>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-600"><?= $h['monthName'] ?> <?= $h['year'] ?></span>
                                    <span class="text-sm font-bold">$<?= number_format($h['ventas'], 0, ',', '.') ?> / $<?= number_format($h['meta'], 0, ',', '.') ?> (<?= $hPct ?>%)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-4">
                                    <div class="h-4 rounded-full <?= $hColor ?> transition-all duration-500" style="width:<?= min($hPct, 100) ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ============================================================ -->
                    <!-- ALMACENISTA (4): Productividad de embalaje -->
                    <!-- ============================================================ -->
                    <?php if($role == 4 && isset($embaladosMes)): ?>

                    <div class="grid gap-4 md:grid-cols-3 mb-6">
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-5">
                            <p class="text-xs text-gray-400 uppercase font-bold">Embalados este mes</p>
                            <p class="text-3xl font-black text-green-600"><?= $embaladosMes ?></p>
                            <p class="text-xs text-gray-400"><?= $monthName ?> <?= $year ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500 p-5">
                            <p class="text-xs text-gray-400 uppercase font-bold">Embalados hoy</p>
                            <p class="text-3xl font-black text-blue-600"><?= $embaladosHoy ?></p>
                            <p class="text-xs text-gray-400"><?= date('d/m/Y') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-orange-500 p-5">
                            <p class="text-xs text-gray-400 uppercase font-bold">Pendientes</p>
                            <p class="text-3xl font-black text-orange-600"><?= $pendientesEmbalar ?></p>
                            <p class="text-xs text-gray-400">asignados a ti</p>
                        </div>
                    </div>

                    <!-- Promedio y gráfica semanal -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold" style="color:#1B365D">Embalajes ultimos 7 dias</h3>
                            <span class="text-sm text-gray-500">Promedio: <span class="font-bold text-blue-600"><?= $promedioDiario ?>/dia</span></span>
                        </div>
                        <div class="flex items-end gap-2 h-32">
                            <?php $maxCount = max(1, max(array_column($embaladosPorDia, 'count')));
                            foreach($embaladosPorDia as $d):
                                $pct = ($d['count'] / $maxCount) * 100;
                                $isToday = $d['fecha'] == date('Y-m-d');
                            ?>
                            <div class="flex-1 flex flex-col items-center">
                                <span class="text-xs font-bold mb-1 <?= $d['count'] > 0 ? 'text-gray-700' : 'text-gray-300' ?>"><?= $d['count'] ?></span>
                                <div class="w-full rounded-t <?= $isToday ? 'bg-blue-500' : 'bg-gray-300' ?>" style="height:<?= max($pct, 4) ?>%"></div>
                                <span class="text-xs text-gray-400 mt-1"><?= $d['dia'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ============================================================ -->
                    <!-- JEFE LOGISTICA (9): Operación del mes -->
                    <!-- ============================================================ -->
                    <?php if($role == 9 && isset($facturasMes)): ?>

                    <div class="grid gap-4 md:grid-cols-3 mb-6">
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500 p-5">
                            <p class="text-xs text-gray-400 uppercase font-bold">Facturas del mes</p>
                            <p class="text-3xl font-black text-blue-600"><?= $facturasMes ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-5">
                            <p class="text-xs text-gray-400 uppercase font-bold">Despachos del mes</p>
                            <p class="text-3xl font-black text-green-600"><?= $despachosMes ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-teal-500 p-5">
                            <p class="text-xs text-gray-400 uppercase font-bold">Ventas del mes</p>
                            <p class="text-2xl font-black text-teal-600">$<?= number_format($ventasMes, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 mb-6">
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="text-sm font-bold mb-3" style="color:#1B365D">Pipeline Hoy</h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Presupuestos pendientes</span>
                                    <span class="text-lg font-bold text-gray-800"><?= $presupuestosPendientes ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Por facturar (embalados)</span>
                                    <span class="text-lg font-bold text-blue-600"><?= $porFacturar ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Facturas hoy</span>
                                    <span class="text-lg font-bold text-green-600"><?= $facturasHoy ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="text-sm font-bold mb-3" style="color:#1B365D">Eficiencia</h3>
                            <?php $pctDespacho = $facturasMes > 0 ? round(($despachosMes / $facturasMes) * 100) : 0; ?>
                            <div class="mb-3">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600">Tasa de despacho</span>
                                    <span class="font-bold"><?= $pctDespacho ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-4">
                                    <div class="h-4 rounded-full bg-green-500" style="width:<?= min($pctDespacho, 100) ?>%"></div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400"><?= $despachosMes ?> de <?= $facturasMes ?> facturas despachadas este mes.</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ============================================================ -->
                    <!-- CARTERA (8): Indicadores de cobranza -->
                    <!-- ============================================================ -->
                    <?php if($role == 8 && isset($carteraTotal)): ?>

                    <div class="grid gap-4 md:grid-cols-3 mb-6">
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-5">
                            <p class="text-xs text-gray-400 uppercase font-bold">Cartera total pendiente</p>
                            <p class="text-2xl font-black text-red-600">$<?= number_format($carteraTotal, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-5">
                            <p class="text-xs text-gray-400 uppercase font-bold">Recaudo del mes</p>
                            <p class="text-2xl font-black text-green-600">$<?= number_format($recaudoMes, 0, ',', '.') ?></p>
                            <p class="text-xs text-gray-400"><?= $monthName ?> <?= $year ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-orange-500 p-5">
                            <p class="text-xs text-gray-400 uppercase font-bold">Facturas morosas (+30 dias)</p>
                            <p class="text-3xl font-black text-orange-600"><?= $clientesMorosos ?></p>
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
