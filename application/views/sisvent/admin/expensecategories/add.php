<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Nueva Categoria de Gasto</title>
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
                        <h2 class="text-lg font-semibold text-gray-600">Nueva Categoria de Gasto</h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/expensecategories"
                           class="text-sm text-mam-blue-petroleo hover:underline">← Volver</a>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                        <div class="p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo base_url(); ?>sisvent/admin/expensecategories/store" method="POST">
                        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">

                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Codigo <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="text" name="code"
                                           value="<?php echo set_value('code'); ?>"
                                           placeholder="Ej: CAT001" required/>
                                    <?php echo form_error('code', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Nombre <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="text" name="name"
                                           value="<?php echo set_value('name'); ?>"
                                           placeholder="Ej: Servicios Publicos" required/>
                                    <?php echo form_error('name', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                </label>
                            </div>

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Descripcion</span>
                                <textarea class="form-input" name="description" rows="2"
                                          placeholder="Descripcion opcional"><?php echo set_value('description'); ?></textarea>
                            </label>

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Subcuenta Contable (PUC)</span>
                                <select class="form-input form-select" name="accounting_subaccount_id">
                                    <option value="">Sin asignar</option>
                                    <?php foreach($subaccounts as $sub): ?>
                                        <option value="<?php echo $sub->id; ?>" <?php echo set_select('accounting_subaccount_id', $sub->id); ?>>
                                            <?php echo $sub->pucCode . ' - ' . $sub->accountName; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Subcuenta del PUC donde se debita el gasto al pagar</p>
                            </label>

                            <div class="flex items-center space-x-3 mt-6">
                                <input type="submit"
                                       class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue-petroleo"
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
