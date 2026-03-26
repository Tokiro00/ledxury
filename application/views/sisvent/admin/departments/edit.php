<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Editar Departamento: <?php echo $department->name; ?></title>
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
                            <h2 class="text-lg font-semibold text-gray-600">Editar Departamento</h2>
                            <p class="text-xs text-gray-400"><?php echo $department->name; ?></p>
                        </div>
                        <a href="<?php echo base_url(); ?>sisvent/admin/departments"
                           class="text-sm text-gray-600 hover:underline">← Volver</a>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                    <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg"><?php echo $this->session->flashdata('error'); ?></div>
                    <?php endif; ?>

                    <form action="<?php echo base_url(); ?>sisvent/admin/departments/edit/<?php echo $department->id; ?>" method="POST">
                        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Nombre <span class="text-red-500">*</span></span>
                                <input class="form-input" type="text" name="name"
                                       value="<?php echo set_value('name', $department->name); ?>" required
                                       placeholder="Nombre del departamento"/>
                                <?php echo form_error('name', "<span class='text-xs text-red-600'>", "</span>"); ?>
                            </label>

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Descripcion</span>
                                <textarea class="form-input" name="description" rows="2"
                                          placeholder="Descripcion del departamento"><?php echo set_value('description', $department->description); ?></textarea>
                            </label>

                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Lider / Responsable</span>
                                    <select class="form-input form-select" name="leader_user_id">
                                        <option value="">Seleccione...</option>
                                        <?php foreach($users as $user): ?>
                                        <option value="<?php echo $user->idUser; ?>"
                                                <?php echo ($department->leader_user_id == $user->idUser) ? 'selected' : ''; ?>>
                                            <?php echo $user->name; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Bodega / Tienda</span>
                                    <select class="form-input form-select" name="store_id">
                                        <option value="">Todas las bodegas</option>
                                        <?php foreach($stores as $store): ?>
                                        <option value="<?php echo $store->idStore; ?>"
                                                <?php echo ($department->store_id == $store->idStore) ? 'selected' : ''; ?>>
                                            <?php echo $store->name; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Presupuesto de Bonificacion <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="number" name="budget" step="1" min="0"
                                           value="<?php echo set_value('budget', $department->budget); ?>" required/>
                                    <?php echo form_error('budget', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Orden</span>
                                    <input class="form-input" type="number" name="sort_order" step="1" min="0"
                                           value="<?php echo set_value('sort_order', $department->sort_order); ?>"/>
                                </label>
                            </div>

                            <div class="flex items-center space-x-3 mt-6">
                                <input type="submit"
                                       style="background:#2E7D91"
                                       class="px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90 cursor-pointer"
                                       value="Guardar"/>
                                <a href="<?php echo base_url(); ?>sisvent/admin/departments"
                                   class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
                                <a href="<?php echo base_url(); ?>sisvent/admin/departments/remove/<?php echo $department->id; ?>"
                                   class="px-4 py-2 text-sm text-red-600 hover:text-red-800 ml-auto"
                                   onclick="return confirm('Esta seguro de eliminar este departamento?')">Eliminar</a>
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
