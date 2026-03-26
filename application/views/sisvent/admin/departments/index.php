<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Departamentos</title>
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
                            <h2 class="text-xl font-semibold text-gray-700">Departamentos</h2>
                            <p class="text-xs text-gray-400">Gestion de departamentos y KPIs</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <select id="storeFilter" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="">Todas las tiendas</option>
                                <?php if(isset($stores)): ?>
                                <?php foreach($stores as $store): ?>
                                <option value="<?php echo $store->idStore; ?>" <?php echo (isset($storeFilter) && $storeFilter == $store->idStore) ? 'selected' : ''; ?>>
                                    <?php echo $store->name; ?>
                                </option>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <a href="<?php echo base_url(); ?>sisvent/admin/departments/add"
                               style="background:#2E7D91"
                               class="px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90">
                                + Nuevo Departamento
                            </a>
                        </div>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                    <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg"><?php echo $this->session->flashdata('error'); ?></div>
                    <?php endif; ?>
                    <?php if($this->session->flashdata('success')): ?>
                    <div class="p-3 mb-4 text-sm text-green-700 bg-green-100 rounded-lg"><?php echo $this->session->flashdata('success'); ?></div>
                    <?php endif; ?>

                    <!-- Cards de Departamentos -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                        <?php if(isset($departments) && count($departments) > 0): ?>
                        <?php foreach($departments as $dept): ?>
                        <?php
                            $complianceColor = 'bg-red-500';
                            $complianceBg = 'bg-red-100 text-red-700';
                            if ($dept->avg_compliance >= 80) {
                                $complianceColor = 'bg-green-500';
                                $complianceBg = 'bg-green-100 text-green-700';
                            } elseif ($dept->avg_compliance >= 50) {
                                $complianceColor = 'bg-yellow-500';
                                $complianceBg = 'bg-yellow-100 text-yellow-700';
                            }
                        ?>
                        <div class="bg-white rounded-lg shadow-md p-4 border border-gray-200">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800"><?php echo $dept->name; ?></h3>
                                    <?php if(isset($dept->store_name) && $dept->store_name): ?>
                                    <p class="text-xs text-gray-400"><?php echo $dept->store_name; ?></p>
                                    <?php endif; ?>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $complianceBg; ?>">
                                    <?php echo number_format($dept->avg_compliance, 1); ?>%
                                </span>
                            </div>

                            <div class="mb-3">
                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>Cumplimiento promedio</span>
                                    <span><?php echo number_format($dept->avg_compliance, 1); ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="<?php echo $complianceColor; ?> h-2 rounded-full" style="width: <?php echo min($dept->avg_compliance, 100); ?>%"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-center mb-3">
                                <div>
                                    <p class="text-xs text-gray-400">Lider</p>
                                    <p class="text-xs font-semibold text-gray-600"><?php echo isset($dept->manager_name) && $dept->manager_name ? $dept->manager_name : '-'; ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">KPIs</p>
                                    <p class="text-xs font-semibold text-gray-600"><?php echo $dept->kpi_count; ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Presupuesto</p>
                                    <p class="text-xs font-semibold text-gray-600">$<?php echo number_format($dept->budget, 0, ',', '.'); ?></p>
                                </div>
                            </div>

                            <div class="flex gap-2 pt-2 border-t border-gray-100">
                                <a href="<?php echo base_url(); ?>sisvent/admin/departments/view/<?php echo $dept->id; ?>"
                                   style="background:#2E7D91"
                                   class="flex-1 text-center px-3 py-1 text-xs font-medium text-white rounded hover:opacity-90">
                                    Ver
                                </a>
                                <a href="<?php echo base_url(); ?>sisvent/admin/departments/edit/<?php echo $dept->id; ?>"
                                   class="flex-1 text-center px-3 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200">
                                    Editar
                                </a>
                                <a href="<?php echo base_url(); ?>sisvent/admin/departments/calculateKpis/<?php echo $dept->id; ?>"
                                   class="flex-1 text-center px-3 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200">
                                    Calcular KPIs
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="col-span-3 text-center py-8 text-gray-500 bg-white rounded-lg shadow-md">
                            <p class="text-sm">No hay departamentos registrados.</p>
                            <a href="<?php echo base_url(); ?>sisvent/admin/departments/add"
                               class="text-sm text-blue-600 hover:underline mt-2 inline-block">Crear primer departamento</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
            <?php $this->load->view('sisvent/layouts/footer'); ?>
        </div>
    </div>
<script>
$(document).on('change', '#storeFilter', function() {
    var storeId = $(this).val();
    var url = '<?php echo base_url(); ?>sisvent/admin/departments';
    if (storeId) {
        url += '?store=' + storeId;
    }
    window.location.href = url;
});
</script>
</body>
</html>
