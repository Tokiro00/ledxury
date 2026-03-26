<?php
    $role = $this->session->userdata('user_data')['role'];
    $semaforoColor = $pctMeta >= 100 ? '#22c55e' : ($pctMeta >= 60 ? '#eab308' : '#ef4444');
    $semaforoBg    = $pctMeta >= 100 ? 'bg-green-500' : ($pctMeta >= 60 ? 'bg-yellow-400' : 'bg-red-500');
    $semaforoText  = $pctMeta >= 100 ? 'text-green-600' : ($pctMeta >= 60 ? 'text-yellow-600' : 'text-red-600');
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
                                <p class="text-sm text-gray-500"><?= $monthName ?> <?= $year ?> - Mi Desempeno</p>
                            </div>
                        </div>
                    </div>

                    <!-- PROGRESO PRINCIPAL -->
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

                    <!-- CARDS: PROYECCION Y RANKING -->
                    <div class="grid gap-6 md:grid-cols-2 mb-6">
                        <!-- Proyeccion -->
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
                            <p class="text-xs text-gray-400">
                                Basado en el ritmo actual de ventas, se proyecta alcanzar este valor al cierre del mes.
                            </p>
                        </div>

                        <!-- Ranking -->
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
                            <p class="text-xs text-gray-400">
                                Posicion basada en ventas acumuladas del mes en curso.
                            </p>
                        </div>
                    </div>

                    <!-- HISTORICO -->
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

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
