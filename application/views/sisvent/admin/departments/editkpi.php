<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Editar KPI - <?php echo $kpi->name; ?></title>
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
                            <h2 class="text-lg font-semibold text-gray-600">Editar KPI</h2>
                            <p class="text-xs text-gray-400">Departamento: <?php echo $department->name; ?></p>
                        </div>
                        <a href="<?php echo base_url(); ?>sisvent/admin/departments/view/<?php echo $department->id; ?>"
                           class="text-sm text-gray-600 hover:underline">← Volver</a>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                    <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg"><?php echo $this->session->flashdata('error'); ?></div>
                    <?php endif; ?>

                    <form action="<?php echo base_url(); ?>sisvent/admin/departments/editKpi/<?php echo $kpi->id; ?>" method="POST">
                        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Nombre del KPI <span class="text-red-500">*</span></span>
                                <input class="form-input" type="text" name="name"
                                       value="<?php echo set_value('name', $kpi->name); ?>" required
                                       placeholder="Ej: Ventas del mes, Recaudo de cartera"/>
                                <?php echo form_error('name', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                <p class="text-xs text-gray-400 mt-1">Tip: Use palabras clave como "venta", "cobro", "cartera 90", "inventario", "gasto", "cliente" para calculo automatico.</p>
                            </label>

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Descripcion</span>
                                <textarea class="form-input" name="description" rows="2"
                                          placeholder="Descripcion detallada del indicador"><?php echo set_value('description', $kpi->description); ?></textarea>
                            </label>

                            <div class="grid grid-cols-3 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Meta <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="number" name="target_value" step="0.01" min="0"
                                           value="<?php echo set_value('target_value', $kpi->target_value); ?>" required
                                           placeholder="Valor objetivo"/>
                                    <?php echo form_error('target_value', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Unidad</span>
                                    <select class="form-input form-select" name="unit">
                                        <option value="#" <?php echo ($kpi->unit == '#') ? 'selected' : ''; ?>>Numero (#)</option>
                                        <option value="$" <?php echo ($kpi->unit == '$') ? 'selected' : ''; ?>>Pesos ($)</option>
                                        <option value="%" <?php echo ($kpi->unit == '%') ? 'selected' : ''; ?>>Porcentaje (%)</option>
                                    </select>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Direccion</span>
                                    <select class="form-input form-select" name="direction">
                                        <option value="higher_better" <?php echo ($kpi->direction == 'higher_better') ? 'selected' : ''; ?>>Mayor es mejor</option>
                                        <option value="lower_better" <?php echo ($kpi->direction == 'lower_better') ? 'selected' : ''; ?>>Menor es mejor</option>
                                    </select>
                                </label>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Peso (%) <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="number" name="weight" step="0.1" min="0" max="100"
                                           value="<?php echo set_value('weight', $kpi->weight); ?>" required
                                           placeholder="Peso porcentual"/>
                                    <?php echo form_error('weight', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                    <p class="text-xs text-gray-400 mt-1">Peso relativo para el calculo de bonificacion</p>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Orden</span>
                                    <input class="form-input" type="number" name="sort_order" step="1" min="0"
                                           value="<?php echo set_value('sort_order', $kpi->sort_order); ?>"
                                           placeholder="0"/>
                                </label>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-3 mt-4">
                                <p class="text-xs text-gray-500">Valor actual: <strong><?php echo number_format($kpi->current_value, 2); ?></strong> (se actualiza con "Calcular KPIs")</p>
                            </div>

                            <div class="flex items-center space-x-3 mt-6">
                                <input type="submit"
                                       style="background:#2E7D91"
                                       class="px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90 cursor-pointer"
                                       value="Guardar Cambios"/>
                                <a href="<?php echo base_url(); ?>sisvent/admin/departments/view/<?php echo $department->id; ?>"
                                   class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
            <?php $this->load->view('sisvent/layouts/footer'); ?>
        </div>
    </div>
</body>
</html>
