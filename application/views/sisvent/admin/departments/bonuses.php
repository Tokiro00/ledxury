<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Bonificaciones: <?php echo $department->name; ?></title>
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
                            <h2 class="text-xl font-semibold text-gray-700">Bonificaciones: <?php echo $department->name; ?></h2>
                            <p class="text-xs text-gray-400">Historial de bonificaciones del departamento</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?php echo base_url(); ?>sisvent/admin/departments/view/<?php echo $department->id; ?>"
                               class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Volver</a>
                            <a href="<?php echo base_url(); ?>sisvent/admin/departments/calculateBonus/<?php echo $department->id; ?>"
                               class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700"
                               onclick="return confirm('Calcular bonificacion del trimestre actual?')">
                                Calcular Bonificacion
                            </a>
                        </div>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                    <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg"><?php echo $this->session->flashdata('error'); ?></div>
                    <?php endif; ?>
                    <?php if($this->session->flashdata('success')): ?>
                    <div class="p-3 mb-4 text-sm text-green-700 bg-green-100 rounded-lg"><?php echo $this->session->flashdata('success'); ?></div>
                    <?php endif; ?>

                    <!-- Bonificaciones -->
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
                                    ?>
                                    <?php foreach($bonuses as $bonus): ?>
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
                                        <td class="px-4 py-3 text-sm font-semibold">$<?php echo number_format($bonus->bonus_amount, 0, ',', '.'); ?></td>
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
                </div>
            </main>
            <?php $this->load->view('sisvent/layouts/footer'); ?>
        </div>
    </div>
</body>
</html>
