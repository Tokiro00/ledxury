<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Editar Categoria de Gasto</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50"
         v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <?php $this->load->view('sisvent/layouts/sidebar',
            array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>

            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">

                    <div class="flex items-center justify-between mb-4 mt-2">
                        <h2 class="text-lg font-semibold text-gray-600">Editar Categoria de Gasto</h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/expensecategories"
                           class="text-sm text-mam-blue-dark hover:underline">← Volver</a>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                        <div class="p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo base_url(); ?>sisvent/admin/expensecategories/update" method="POST">
                        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">

                            <input type="hidden" name="id" value="<?php echo $category->id; ?>"/>

                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Codigo <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="text" name="code"
                                           value="<?php echo !empty(form_error('code')) ? set_value('code') : $category->code; ?>" required/>
                                    <?php echo form_error('code', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Nombre <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="text" name="name"
                                           value="<?php echo !empty(form_error('name')) ? set_value('name') : $category->name; ?>" required/>
                                    <?php echo form_error('name', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                </label>
                            </div>

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Descripcion</span>
                                <textarea class="form-input" name="description" rows="2"><?php echo $category->description; ?></textarea>
                            </label>

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Subcuenta Contable (PUC)</span>
                                <select class="form-input form-select" name="accounting_subaccount_id">
                                    <option value="">Sin asignar</option>
                                    <?php foreach($subaccounts as $sub): ?>
                                        <option value="<?php echo $sub->id; ?>" <?php echo ($category->accounting_subaccount_id == $sub->id) ? 'selected' : ''; ?>>
                                            <?php echo $sub->pucCode . ' - ' . $sub->accountName; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Subcuenta del PUC donde se debita el gasto al pagar</p>
                            </label>

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Estado</span>
                                <select class="form-input form-select" name="is_active">
                                    <option value="1" <?php echo ($category->is_active == 1) ? 'selected' : ''; ?>>Activa</option>
                                    <option value="0" <?php echo ($category->is_active == 0) ? 'selected' : ''; ?>>Inactiva</option>
                                </select>
                            </label>

                            <div class="flex items-center space-x-3 mt-6">
                                <input type="submit"
                                       class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-lg hover:bg-mam-blue-dark"
                                       value="Guardar"/>
                                <a href="<?php echo base_url(); ?>sisvent/admin/expensecategories"
                                   class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
