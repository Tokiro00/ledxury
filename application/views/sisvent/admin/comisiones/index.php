<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Comisiones Bots - Ledxury</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/comisiones/index', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 w-full max-w-screen-xl mx-auto">

                <!-- Header -->
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Comisiones de Bots</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Período: <?= date('d/m/Y', strtotime($period_start)) ?> al <?= date('d/m/Y', strtotime($period_end)) ?></p>
                    </div>
                    <div class="flex items-center gap-3 mt-2 lg:mt-0">
                        <form method="GET" class="flex items-center gap-2">
                            <select name="year" class="px-3 py-2 text-sm border rounded-lg">
                                <?php $cy = (int)date('Y'); for ($y = $cy + 1; $y >= $cy - 4; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <select name="month_num" class="px-3 py-2 text-sm border rounded-lg">
                                <option value="0">Todo el año</option>
                                <?php
                                $meses_sel = array('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
                                for ($i = 1; $i <= 12; $i++):
                                ?>
                                <option value="<?= $i ?>" <?= $i == $month_num ? 'selected' : '' ?>><?= $meses_sel[$i-1] ?></option>
                                <?php endfor; ?>
                            </select>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#2E7D91;">Consultar</button>
                        </form>
                    </div>
                </div>

                <!-- KPIs -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
                    <div class="bg-white rounded-lg border p-4">
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Cobrado</p>
                        <p class="text-xl font-bold text-green-600 mt-1">$<?= number_format($total_cobrado, 0, ',', '.') ?></p>
                    </div>
                    <div class="bg-white rounded-lg border p-4">
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Comisiones</p>
                        <p class="text-xl font-bold text-blue-600 mt-1">$<?= number_format($total_comisiones, 0, ',', '.') ?></p>
                    </div>
                    <div class="bg-white rounded-lg border p-4">
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Período</p>
                        <p class="text-lg font-bold text-gray-700 mt-1"><?= $period_label ?></p>
                    </div>
                    <div class="bg-white rounded-lg border p-4">
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Estado</p>
                        <?php if ($is_year_scope): ?>
                        <p class="mt-1"><span class="px-3 py-1 text-xs font-bold text-blue-700 bg-blue-100 rounded-full"><?= $liquidated_months_count ?>/12 meses liquidados</span></p>
                        <?php elseif ($period && $period->status === 'liquidado'): ?>
                        <p class="mt-1"><span class="px-3 py-1 text-xs font-bold text-green-700 bg-green-100 rounded-full">Liquidado</span></p>
                        <?php else: ?>
                        <p class="mt-1"><span class="px-3 py-1 text-xs font-bold text-yellow-700 bg-yellow-100 rounded-full">Pendiente</span></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cobros por Bot -->
                <div class="bg-white rounded-lg border overflow-hidden mb-5">
                    <div class="px-4 py-3 border-b bg-gray-50">
                        <h3 class="text-sm font-bold text-gray-600">Cobros por Bot en el Período</h3>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs font-medium text-gray-500 uppercase bg-gray-50">
                                <th class="px-4 py-3 text-left">Bot</th>
                                <th class="px-4 py-3 text-right">Guías</th>
                                <th class="px-4 py-3 text-right">Bruto</th>
                                <th class="px-4 py-3 text-right text-yellow-700">− Flete</th>
                                <th class="px-4 py-3 text-right">Base comisión</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php
                                $sumBruto = 0; $sumFlete = 0;
                                foreach ($cobros as $bot_id => $info):
                                    $bruto = isset($info['bruto']) ? (float)$info['bruto'] : (float)$info['total'];
                                    $flete = isset($info['flete']) ? (float)$info['flete'] : 0;
                                    $sumBruto += $bruto; $sumFlete += $flete;
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium"><?= htmlspecialchars($info['bot_name']) ?></td>
                                <td class="px-4 py-3 text-right"><?= $info['guias'] ?></td>
                                <td class="px-4 py-3 text-right text-gray-600">$<?= number_format($bruto, 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-right text-yellow-700">−$<?= number_format($flete, 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-right font-bold text-green-600">$<?= number_format($info['total'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-4 py-3">TOTAL</td>
                                <td class="px-4 py-3 text-right"><?= array_sum(array_column($cobros, 'guias')) ?></td>
                                <td class="px-4 py-3 text-right text-gray-700">$<?= number_format($sumBruto, 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-right text-yellow-700">−$<?= number_format($sumFlete, 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-right text-green-600">$<?= number_format($total_cobrado, 0, ',', '.') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Comisiones -->
                <div class="bg-white rounded-lg border overflow-hidden mb-5">
                    <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-gray-600">Detalle de Comisiones</h3>
                        <?php if (!$is_year_scope && (!$period || $period->status !== 'liquidado')): ?>
                        <button onclick="liquidar()" class="px-4 py-2 text-xs font-bold text-white bg-green-600 rounded-lg hover:bg-green-700">Liquidar Período</button>
                        <?php elseif ($is_year_scope): ?>
                        <span class="text-xxs text-gray-400 italic">Para liquidar, selecciona un mes específico</span>
                        <?php endif; ?>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs font-medium text-gray-500 uppercase bg-gray-50">
                                <th class="px-4 py-3 text-left">Persona</th>
                                <th class="px-4 py-3 text-left">Tipo</th>
                                <th class="px-4 py-3 text-center">%</th>
                                <th class="px-4 py-3 text-left">Aplica sobre</th>
                                <th class="px-4 py-3 text-right">Base</th>
                                <th class="px-4 py-3 text-right">Comisión periodo</th>
                                <th class="px-4 py-3 text-right border-l">Acumulado año</th>
                                <th class="px-4 py-3 text-right">Acumulado total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php
                            $totalHistYear = 0; $totalHistAll = 0;
                            foreach ($comisiones as $c):
                                $totalHistYear += (float)($c['hist_year'] ?? 0);
                                $totalHistAll  += (float)($c['hist_total'] ?? 0);
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium"><?= htmlspecialchars($c['user_name']) ?></td>
                                <td class="px-4 py-3">
                                    <?php
                                        $typeColors = array('admin_bots' => 'bg-purple-100 text-purple-700', 'operator' => 'bg-blue-100 text-blue-700', 'ads_manager' => 'bg-orange-100 text-orange-700');
                                        $tc = isset($typeColors[$c['type']]) ? $typeColors[$c['type']] : 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="px-2 py-0.5 text-xs font-bold rounded-full <?= $tc ?>"><?= $c['type_label'] ?></span>
                                </td>
                                <td class="px-4 py-3 text-center font-bold"><?= $c['percentage'] ?>%</td>
                                <td class="px-4 py-3 text-sm text-gray-500"><?= $c['bot_name'] ?></td>
                                <td class="px-4 py-3 text-right">$<?= number_format($c['base'], 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-right font-bold text-blue-600">$<?= number_format($c['amount'], 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-right text-gray-700 border-l">
                                    <?= !empty($c['hist_year']) ? '$' . number_format($c['hist_year'], 0, ',', '.') : '<span class="text-gray-300">—</span>' ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <?php if (!empty($c['hist_total'])): ?>
                                        <span class="font-semibold text-emerald-700">$<?= number_format($c['hist_total'], 0, ',', '.') ?></span>
                                        <div class="text-xxs text-gray-400"><?= (int)$c['hist_periods'] ?> período<?= $c['hist_periods'] == 1 ? '' : 's' ?></div>
                                    <?php else: ?>
                                        <span class="text-gray-300">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-4 py-3" colspan="5">TOTAL COMISIONES</td>
                                <td class="px-4 py-3 text-right text-blue-600">$<?= number_format($total_comisiones, 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-right text-gray-700 border-l">$<?= number_format($totalHistYear, 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-right text-emerald-700">$<?= number_format($totalHistAll, 0, ',', '.') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
function liquidar() {
    if (!confirm('¿Liquidar comisiones del período <?= date("d/m/Y", strtotime($period_start)) ?> al <?= date("d/m/Y", strtotime($period_end)) ?>?')) return;
    $.ajax({
        url: '<?= base_url() ?>sisvent/admin/comisiones/liquidar',
        type: 'POST',
        data: { period_start: '<?= $period_start ?>', period_end: '<?= $period_end ?>', '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' },
        dataType: 'json',
        success: function(r) { alert(r.message); if (r.success) location.reload(); },
        error: function() { alert('Error de conexión'); }
    });
}
</script>
</body>
</html>
