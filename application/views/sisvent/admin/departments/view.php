<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Departamento: <?php echo $department->name; ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-700"><?php echo $department->name; ?></h2>
                            <p class="text-xs text-gray-400">Detalle del departamento y KPIs</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?php echo base_url(); ?>sisvent/admin/departments"
                               class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Volver</a>
                            <a href="<?php echo base_url(); ?>sisvent/admin/departments/edit/<?php echo $department->id; ?>"
                               class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Editar</a>
                        </div>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                    <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg"><?php echo $this->session->flashdata('error'); ?></div>
                    <?php endif; ?>
                    <?php if($this->session->flashdata('success')): ?>
                    <div class="p-3 mb-4 text-sm text-green-700 bg-green-100 rounded-lg"><?php echo $this->session->flashdata('success'); ?></div>
                    <?php endif; ?>

                    <!-- Info del Departamento -->
                    <div class="bg-white rounded-lg shadow-md p-4 mb-6 border border-gray-200">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-xs text-gray-400">Lider</p>
                                <p class="text-sm font-semibold text-gray-700"><?php echo isset($department->manager_name) && $department->manager_name ? $department->manager_name : '-'; ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Bodega</p>
                                <p class="text-sm font-semibold text-gray-700"><?php echo isset($department->store_name) && $department->store_name ? $department->store_name : 'Todas'; ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Presupuesto Bonificacion</p>
                                <p class="text-sm font-semibold text-gray-700">$<?php echo number_format($department->budget, 0, ',', '.'); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Descripcion</p>
                                <p class="text-sm text-gray-600"><?php echo $department->description ? $department->description : '-'; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones KPIs -->
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-700">Indicadores (KPIs)</h3>
                        <div class="flex gap-2">
                            <a href="<?php echo base_url(); ?>sisvent/admin/departments/calculateKpis/<?php echo $department->id; ?>"
                               style="background:#2E7D91"
                               class="px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90">
                                Calcular KPIs
                            </a>
                            <a href="<?php echo base_url(); ?>sisvent/admin/departments/addKpi/<?php echo $department->id; ?>"
                               style="background:#1B365D"
                               class="px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90">
                                + Agregar KPI
                            </a>
                            <a href="<?php echo base_url(); ?>sisvent/admin/departments/calculateBonus/<?php echo $department->id; ?>"
                               class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700"
                               onclick="return confirm('Calcular bonificacion del trimestre actual?')">
                                Calcular Bonificacion
                            </a>
                        </div>
                    </div>

                    <!-- Tabla de KPIs -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs mb-8">
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left uppercase" style="background:#1B365D; color:white;">
                                        <th class="px-4 py-3">KPI</th>
                                        <th class="px-4 py-3">Meta</th>
                                        <th class="px-4 py-3">Actual</th>
                                        <th class="px-4 py-3">Peso</th>
                                        <th class="px-4 py-3" style="min-width:200px;">Cumplimiento</th>
                                        <th class="px-4 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php if(isset($kpis) && count($kpis) > 0): ?>
                                    <?php foreach($kpis as $kpi): ?>
                                    <?php
                                        $compliance = isset($kpi->compliance) ? $kpi->compliance : 0;
                                        $barColor = 'bg-red-500';
                                        if ($compliance >= 80) {
                                            $barColor = 'bg-green-500';
                                        } elseif ($compliance >= 50) {
                                            $barColor = 'bg-yellow-500';
                                        }

                                        // Formato segun unidad
                                        $unit = isset($kpi->unit) ? $kpi->unit : '';
                                        $targetDisplay = '';
                                        $currentDisplay = '';
                                        if ($unit == '$') {
                                            $targetDisplay = '$' . number_format($kpi->target_value, 0, ',', '.');
                                            $currentDisplay = '$' . number_format($kpi->current_value, 0, ',', '.');
                                        } elseif ($unit == '%') {
                                            $targetDisplay = number_format($kpi->target_value, 1) . '%';
                                            $currentDisplay = number_format($kpi->current_value, 1) . '%';
                                        } else {
                                            $targetDisplay = number_format($kpi->target_value, 0, ',', '.');
                                            $currentDisplay = number_format($kpi->current_value, 0, ',', '.');
                                        }

                                        $directionLabel = ($kpi->direction == 'lower_better') ? '↓' : '↑';
                                    ?>
                                    <tr class="text-gray-700">
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-semibold"><?php echo $kpi->name; ?></div>
                                            <?php if($kpi->description): ?>
                                            <div class="text-xs text-gray-400"><?php echo $kpi->description; ?></div>
                                            <?php endif; ?>
                                            <span class="text-xs text-gray-400"><?php echo $directionLabel; ?> <?php echo ($kpi->direction == 'lower_better') ? 'Menor es mejor' : 'Mayor es mejor'; ?></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm font-medium"><?php echo $targetDisplay; ?></td>
                                        <td class="px-4 py-3 text-sm font-medium"><?php echo $currentDisplay; ?></td>
                                        <td class="px-4 py-3 text-sm"><?php echo $kpi->weight; ?>%</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-full bg-gray-200 rounded-full h-3">
                                                    <div class="<?php echo $barColor; ?> h-3 rounded-full transition-all" style="width: <?php echo min($compliance, 100); ?>%"></div>
                                                </div>
                                                <span class="text-sm font-semibold whitespace-no-wrap" style="min-width:50px;">
                                                    <?php echo number_format($compliance, 1); ?>%
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex gap-2">
                                                <a href="<?php echo base_url(); ?>sisvent/admin/departments/editKpi/<?php echo $kpi->id; ?>"
                                                   class="text-blue-600 hover:underline text-xs">Editar</a>
                                                <a href="<?php echo base_url(); ?>sisvent/admin/departments/removeKpi/<?php echo $kpi->id; ?>"
                                                   class="text-red-600 hover:underline text-xs"
                                                   onclick="return confirm('Eliminar este KPI?')">Eliminar</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-sm text-gray-500 text-center">
                                            No hay KPIs configurados para este departamento.
                                            <a href="<?php echo base_url(); ?>sisvent/admin/departments/addKpi/<?php echo $department->id; ?>"
                                               class="text-blue-600 hover:underline">Agregar primer KPI</a>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Escala de Bonos -->
                    <?php
                        // Calcular puntaje actual para resaltar nivel
                        $currentScore = 0;
                        $totalW = 0;
                        $kpisOver70 = 0;
                        if (isset($kpis) && count($kpis) > 0) {
                            foreach ($kpis as $k) {
                                $comp = isset($k->compliance) ? $k->compliance : 0;
                                $currentScore += $comp * $k->weight;
                                $totalW += $k->weight;
                                if ($comp >= 70) $kpisOver70++;
                            }
                            $currentScore = $totalW > 0 ? round($currentScore / $totalW, 1) : 0;
                        }

                        $bonusBase  = isset($department->bonus_base)  ? (float)$department->bonus_base  : 0;
                        $bonusCumpl = isset($department->bonus_cumpl) ? (float)$department->bonus_cumpl : 0;
                        $bonusElite = isset($department->bonus_elite) ? (float)$department->bonus_elite : 0;
                        $minScoreReq = isset($department->min_score) ? (float)$department->min_score : 60;
                        $extraCond  = isset($department->extra_condition) ? $department->extra_condition : '';

                        // Determinar nivel actual
                        $currentTier = 'none';
                        if ($currentScore >= $minScoreReq && $kpisOver70 >= 3) {
                            if ($currentScore >= 100) $currentTier = 'elite';
                            elseif ($currentScore >= 80) $currentTier = 'cumpl';
                            else $currentTier = 'base';
                        }
                    ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Escala de Bonos</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                            <!-- Nivel Base -->
                            <div class="rounded-lg border-2 p-4 <?php echo $currentTier == 'base' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200 bg-white'; ?> shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold uppercase <?php echo $currentTier == 'base' ? 'text-yellow-700' : 'text-gray-500'; ?>">Base</span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $currentTier == 'base' ? 'bg-yellow-200 text-yellow-800' : 'bg-gray-100 text-gray-500'; ?>">
                                        &ge; <?php echo number_format($minScoreReq, 0); ?>%
                                    </span>
                                </div>
                                <p class="text-2xl font-bold <?php echo $currentTier == 'base' ? 'text-yellow-700' : 'text-gray-700'; ?>">
                                    $<?php echo number_format($bonusBase, 0, ',', '.'); ?>
                                </p>
                                <p class="text-xs text-gray-400 mt-1">Bonificacion mensual</p>
                                <?php if($currentTier == 'base'): ?>
                                <div class="mt-2 text-xs font-semibold text-yellow-600">&#10003; Nivel actual (<?php echo number_format($currentScore, 1); ?>%)</div>
                                <?php endif; ?>
                            </div>

                            <!-- Nivel Cumplimiento -->
                            <div class="rounded-lg border-2 p-4 <?php echo $currentTier == 'cumpl' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white'; ?> shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold uppercase <?php echo $currentTier == 'cumpl' ? 'text-blue-700' : 'text-gray-500'; ?>">Cumplimiento</span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $currentTier == 'cumpl' ? 'bg-blue-200 text-blue-800' : 'bg-gray-100 text-gray-500'; ?>">
                                        &ge; 80%
                                    </span>
                                </div>
                                <p class="text-2xl font-bold <?php echo $currentTier == 'cumpl' ? 'text-blue-700' : 'text-gray-700'; ?>">
                                    $<?php echo number_format($bonusCumpl, 0, ',', '.'); ?>
                                </p>
                                <p class="text-xs text-gray-400 mt-1">Bonificacion mensual</p>
                                <?php if($currentTier == 'cumpl'): ?>
                                <div class="mt-2 text-xs font-semibold text-blue-600">&#10003; Nivel actual (<?php echo number_format($currentScore, 1); ?>%)</div>
                                <?php endif; ?>
                            </div>

                            <!-- Nivel Elite -->
                            <div class="rounded-lg border-2 p-4 <?php echo $currentTier == 'elite' ? 'border-green-500 bg-green-50' : 'border-gray-200 bg-white'; ?> shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold uppercase <?php echo $currentTier == 'elite' ? 'text-green-700' : 'text-gray-500'; ?>">Elite</span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $currentTier == 'elite' ? 'bg-green-200 text-green-800' : 'bg-gray-100 text-gray-500'; ?>">
                                        &ge; 100%
                                    </span>
                                </div>
                                <p class="text-2xl font-bold <?php echo $currentTier == 'elite' ? 'text-green-700' : 'text-gray-700'; ?>">
                                    $<?php echo number_format($bonusElite, 0, ',', '.'); ?>
                                </p>
                                <p class="text-xs text-gray-400 mt-1">Bonificacion mensual</p>
                                <?php if($currentTier == 'elite'): ?>
                                <div class="mt-2 text-xs font-semibold text-green-600">&#10003; Nivel actual (<?php echo number_format($currentScore, 1); ?>%)</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Reglas y condiciones -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 flex flex-wrap items-center gap-4 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="inline-block w-2 h-2 rounded-full bg-red-500"></span>
                                <span class="text-gray-600">Puntaje < <?php echo number_format($minScoreReq, 0); ?>% = <strong>$0</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-block w-2 h-2 rounded-full bg-yellow-500"></span>
                                <span class="text-gray-600">Minimo 3 KPIs &ge; 70% requeridos</span>
                            </div>
                            <?php if($extraCond): ?>
                            <div class="flex items-center gap-2">
                                <span class="inline-block w-2 h-2 rounded-full" style="background:#2E7D91"></span>
                                <span class="text-gray-600"><?php echo $extraCond; ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex items-center gap-2 ml-auto">
                                <span class="text-gray-500">Puntaje actual:</span>
                                <span class="px-2 py-1 text-xs font-bold rounded-full <?php
                                    echo $currentScore >= 100 ? 'bg-green-100 text-green-700' :
                                        ($currentScore >= 80 ? 'bg-blue-100 text-blue-700' :
                                        ($currentScore >= $minScoreReq ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'));
                                ?>">
                                    <?php echo number_format($currentScore, 1); ?>%
                                </span>
                                <span class="text-gray-500">| KPIs &ge; 70%:</span>
                                <span class="px-2 py-1 text-xs font-bold rounded-full <?php echo $kpisOver70 >= 3 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                    <?php echo $kpisOver70; ?> de <?php echo count($kpis); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de Bonificaciones -->
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-700">Historial de Bonificaciones</h3>
                        <a href="<?php echo base_url(); ?>sisvent/admin/departments/bonuses/<?php echo $department->id; ?>"
                           class="text-sm text-blue-600 hover:underline">Ver todo</a>
                    </div>

                    <div class="w-full overflow-hidden rounded-lg shadow-xs mb-8">
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left uppercase" style="background:#1B365D; color:white;">
                                        <th class="px-4 py-3">Periodo</th>
                                        <th class="px-4 py-3">Cumplimiento</th>
                                        <th class="px-4 py-3">Bonificacion</th>
                                        <th class="px-4 py-3">Calculado por</th>
                                        <th class="px-4 py-3">Notas</th>
                                        <th class="px-4 py-3">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php if(isset($bonuses) && count($bonuses) > 0): ?>
                                    <?php
                                        $quarterNames = array(1 => 'Q1 (Ene-Mar)', 2 => 'Q2 (Abr-Jun)', 3 => 'Q3 (Jul-Sep)', 4 => 'Q4 (Oct-Dic)');
                                        $count = 0;
                                    ?>
                                    <?php foreach($bonuses as $bonus): ?>
                                    <?php if($count >= 5) break; $count++; ?>
                                    <?php
                                        $compColor = 'text-red-700 bg-red-100';
                                        if ($bonus->compliance_score >= 80) $compColor = 'text-green-700 bg-green-100';
                                        elseif ($bonus->compliance_score >= 50) $compColor = 'text-yellow-700 bg-yellow-100';
                                    ?>
                                    <tr class="text-gray-700">
                                        <td class="px-4 py-3 text-sm font-medium"><?php echo $bonus->year; ?> - <?php echo isset($quarterNames[$bonus->quarter]) ? $quarterNames[$bonus->quarter] : 'Q' . $bonus->quarter; ?></td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $compColor; ?>">
                                                <?php echo number_format($bonus->compliance_score, 1); ?>%
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold">
                                            $<?php echo number_format($bonus->bonus_amount, 0, ',', '.'); ?>
                                            <?php if(isset($bonus->bonus_tier) && $bonus->bonus_tier): ?>
                                            <?php
                                                $tierBadge = 'bg-gray-100 text-gray-600';
                                                $tierText = $bonus->bonus_tier;
                                                if ($bonus->bonus_tier == 'elite') { $tierBadge = 'bg-green-100 text-green-700'; $tierText = 'Elite'; }
                                                elseif ($bonus->bonus_tier == 'cumplimiento') { $tierBadge = 'bg-blue-100 text-blue-700'; $tierText = 'Cumplimiento'; }
                                                elseif ($bonus->bonus_tier == 'base') { $tierBadge = 'bg-yellow-100 text-yellow-700'; $tierText = 'Base'; }
                                                elseif ($bonus->bonus_tier == 'sin_bono') { $tierBadge = 'bg-red-100 text-red-700'; $tierText = 'Sin bono'; }
                                            ?>
                                            <span class="ml-1 px-2 py-0.5 text-xs font-semibold rounded-full <?php echo $tierBadge; ?>"><?php echo $tierText; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm"><?php echo isset($bonus->calculated_by_name) && $bonus->calculated_by_name ? $bonus->calculated_by_name : $bonus->calculated_by; ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-500"><?php echo $bonus->notes; ?></td>
                                        <td class="px-4 py-3 text-xs text-gray-400"><?php echo $bonus->created_at; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-sm text-gray-500 text-center">No hay bonificaciones registradas.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Vendedores y Metas -->
                    <?php if(isset($vendors) && count($vendors) > 0): ?>
                    <?php
                        // Filtrar solo vendedores con ventas o meta en el año
                        $activeVendors = array();
                        foreach ($vendors as $v) {
                            $hasSales = false;
                            $hasGoal = false;
                            for ($m = 1; $m <= 12; $m++) {
                                if ($v->sales['m'.$m] > 0) $hasSales = true;
                                if ($v->goals['m'.$m] > 0) $hasGoal = true;
                            }
                            if ($hasSales || $hasGoal) $activeVendors[] = $v;
                        }
                    ?>
                    <?php if(count($activeVendors) > 0): ?>
                    <div class="bg-white rounded-lg shadow-sm border mb-8">
                        <div class="px-5 py-3 border-b flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-800">Vendedores y Metas <?= $goalYear ?></h3>
                                <p class="text-xs text-gray-400"><?= count($activeVendors) ?> vendedores activos</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <select id="goalYearSelector" class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                                    <?php for($y = (int)date('Y') - 2; $y <= (int)date('Y') + 1; $y++): ?>
                                    <option value="<?= $y ?>" <?= ($goalYear == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Toast -->
                        <div id="goalToast" class="fixed top-4 right-4 z-50 hidden">
                            <div id="goalToastContent" class="px-4 py-3 rounded-lg shadow-lg text-sm font-medium"></div>
                        </div>

                        <div class="overflow-x-auto">
                            <?php
                                $monthNames = array('m1'=>'Ene','m2'=>'Feb','m3'=>'Mar','m4'=>'Abr','m5'=>'May','m6'=>'Jun','m7'=>'Jul','m8'=>'Ago','m9'=>'Sep','m10'=>'Oct','m11'=>'Nov','m12'=>'Dic');
                                $currentMonth = (int)date('n');
                                $totalGoals = array(); $totalSales = array();
                                for ($m = 1; $m <= 12; $m++) { $totalGoals['m'.$m] = 0; $totalSales['m'.$m] = 0; }
                                $grandTotalGoal = 0; $grandTotalSales = 0;
                                foreach ($activeVendors as $v) {
                                    for ($m = 1; $m <= 12; $m++) {
                                        $k = 'm'.$m;
                                        $totalGoals[$k] += $v->goals[$k];
                                        $totalSales[$k] += $v->sales[$k];
                                        $grandTotalGoal += $v->goals[$k];
                                        $grandTotalSales += $v->sales[$k];
                                    }
                                }
                            ?>
                            <table class="w-full" style="font-size:14px;">
                                <thead>
                                    <tr style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 text-left font-semibold" style="min-width:160px;">Vendedor</th>
                                        <?php foreach($monthNames as $key => $name): ?>
                                        <?php $mNum = (int)substr($key, 1); ?>
                                        <th class="px-1 py-2.5 text-center font-semibold <?= ($goalYear == (int)date('Y') && $mNum == $currentMonth) ? 'bg-blue-800' : '' ?>" style="min-width:85px;"><?= $name ?><?= ($goalYear == (int)date('Y') && $mNum == $currentMonth) ? ' *' : '' ?></th>
                                        <?php endforeach; ?>
                                        <th class="px-2 py-2.5 text-right font-semibold" style="min-width:90px;">Meta Anual</th>
                                        <th class="px-2 py-2.5 text-right font-semibold" style="min-width:90px;">Ventas</th>
                                        <th class="px-2 py-2.5 text-center font-semibold" style="min-width:55px;">%</th>
                                        <th class="px-2 py-2.5 text-center font-semibold" style="min-width:60px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $n = 0; foreach($activeVendors as $vendor): $n++; ?>
                                    <?php
                                        $vTotalGoal = 0; $vTotalSales = 0;
                                        for ($m = 1; $m <= 12; $m++) { $vTotalGoal += $vendor->goals['m'.$m]; $vTotalSales += $vendor->sales['m'.$m]; }
                                        $vCompliance = $vTotalGoal > 0 ? round(($vTotalSales / $vTotalGoal) * 100, 1) : 0;
                                    ?>
                                    <tr class="border-t <?= $n % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50" data-vendor-id="<?= $vendor->idUser ?>">
                                        <td class="px-3 py-2">
                                            <div class="font-semibold text-gray-800" style="font-size:13px;"><?= $vendor->name ?></div>
                                            <div class="text-gray-400" style="font-size:12px;"><?= $vendor->store ?></div>
                                        </td>
                                        <?php for($m = 1; $m <= 12; $m++): ?>
                                        <?php
                                            $key = 'm'.$m;
                                            $goalVal = $vendor->goals[$key];
                                            $salesVal = $vendor->sales[$key];
                                            $isPast = ($goalYear < (int)date('Y')) || ($goalYear == (int)date('Y') && $m <= $currentMonth);
                                            $cellBg = '';
                                            if ($isPast && $goalVal > 0) {
                                                $cellBg = $salesVal >= $goalVal ? 'background:#d4edda;' : 'background:#f8d7da;';
                                            }
                                        ?>
                                        <td class="px-1 py-1.5 text-center" style="<?= $cellBg ?>">
                                            <input type="text" name="<?= $key ?>" value="<?= $goalVal > 0 ? number_format($goalVal, 0, '', '.') : '' ?>"
                                                   class="w-full text-right px-1 py-1 border border-gray-200 rounded goal-input"
                                                   style="font-size:13px; max-width:90px;"
                                                   placeholder="0" data-vendor="<?= $vendor->idUser ?>" data-month="<?= $key ?>">
                                            <?php if($isPast || $salesVal > 0): ?>
                                            <div class="mt-0.5 font-medium <?= ($goalVal > 0 && $salesVal >= $goalVal) ? 'text-green-600' : 'text-red-500' ?>" style="font-size:12px;">
                                                $<?= number_format($salesVal, 0, ',', '.') ?>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <?php endfor; ?>
                                        <td class="px-2 py-2 text-right font-bold text-gray-700">$<?= number_format($vTotalGoal, 0, ',', '.') ?></td>
                                        <td class="px-2 py-2 text-right font-bold <?= $vTotalSales > 0 ? 'text-green-700' : 'text-gray-400' ?>">$<?= number_format($vTotalSales, 0, ',', '.') ?></td>
                                        <td class="px-2 py-2 text-center">
                                            <?php if($vTotalGoal > 0): ?>
                                            <span class="px-1.5 py-0.5 font-bold rounded-full <?= $vCompliance >= 100 ? 'text-green-700 bg-green-100' : ($vCompliance >= 70 ? 'text-yellow-700 bg-yellow-100' : 'text-red-700 bg-red-100') ?>" style="font-size:13px;">
                                                <?= $vCompliance ?>%
                                            </span>
                                            <?php else: ?>
                                            <span class="text-gray-300" style="font-size:12px;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <button class="btn-save-goals px-2 py-1 text-white rounded hover:opacity-90" style="background:#2E7D91; font-size:10px;" data-vendor="<?= $vendor->idUser ?>">Guardar</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#1B365D; color:white; font-size:11px;" class="font-bold">
                                        <td class="px-3 py-2.5">TOTALES (<?= count($activeVendors) ?>)</td>
                                        <?php for($m = 1; $m <= 12; $m++): $key = 'm'.$m; ?>
                                        <td class="px-1 py-2 text-center">
                                            <div>$<?= number_format($totalGoals[$key], 0, ',', '.') ?></div>
                                            <div style="font-size:11px; opacity:0.8;">$<?= number_format($totalSales[$key], 0, ',', '.') ?></div>
                                        </td>
                                        <?php endfor; ?>
                                        <td class="px-2 py-2 text-right">$<?= number_format($grandTotalGoal, 0, ',', '.') ?></td>
                                        <td class="px-2 py-2 text-right">$<?= number_format($grandTotalSales, 0, ',', '.') ?></td>
                                        <td class="px-2 py-2 text-center">
                                            <?php $gc = $grandTotalGoal > 0 ? round(($grandTotalSales / $grandTotalGoal) * 100, 1) : 0; ?>
                                            <span class="px-1.5 py-0.5 rounded-full <?= $gc >= 100 ? 'bg-green-200 text-green-800' : ($gc >= 70 ? 'bg-yellow-200 text-yellow-800' : 'bg-red-200 text-red-100') ?>" style="font-size:13px;"><?= $gc ?>%</span>
                                        </td>
                                        <td class="px-2 py-2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="px-5 py-2 border-t text-xs text-gray-400">* Mes actual. Meta = fila superior, Ventas reales = fila inferior. Verde = cumple meta, Rojo = no cumple.</div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                </div>
            </main>
            <?php $this->load->view('sisvent/layouts/footer'); ?>
        </div>
    </div>

<script>
// Cambiar año de metas
$(document).on('change', '#goalYearSelector', function() {
    var year = $(this).val();
    var currentUrl = window.location.href.split('?')[0];
    window.location.href = currentUrl + '?goal_year=' + year;
});

// Mostrar toast
function showGoalToast(message, isSuccess) {
    var toast = $('#goalToast');
    var content = $('#goalToastContent');
    content.text(message);
    if (isSuccess) {
        content.removeClass('bg-red-100 text-red-700').addClass('bg-green-100 text-green-700');
    } else {
        content.removeClass('bg-green-100 text-green-700').addClass('bg-red-100 text-red-700');
    }
    toast.removeClass('hidden');
    setTimeout(function() {
        toast.addClass('hidden');
    }, 3000);
}

// Guardar metas de un vendedor
$(document).on('click', '.btn-save-goals', function() {
    var btn = $(this);
    var vendorId = btn.data('vendor');
    var year = $('#goalYearSelector').val();
    var row = $('tr[data-vendor-id="' + vendorId + '"]');

    var postData = {
        vendor_id: vendorId,
        year: year
    };

    row.find('.goal-input').each(function() {
        var month = $(this).data('month');
        var val = $(this).val().replace(/\./g, '').replace(/,/g, '');
        postData[month] = val || 0;
    });

    btn.prop('disabled', true).text('...');

    $.ajax({
        url: '<?php echo base_url(); ?>sisvent/admin/departments/vendorGoals/<?php echo $department->id; ?>',
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showGoalToast(response.message, true);
            } else {
                showGoalToast(response.message, false);
            }
            btn.prop('disabled', false).text('Guardar');
        },
        error: function() {
            showGoalToast('Error de conexion al guardar', false);
            btn.prop('disabled', false).text('Guardar');
        }
    });
});
</script>
</body>
</html>
