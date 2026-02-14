<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Editar Caja</title>
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

                    <!-- ENCABEZADO -->
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <h2 class="text-lg font-semibold text-gray-600">Editar Caja</h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/cashboxes"
                           class="text-sm text-mam-blue-dark hover:underline">
                            ← Volver al listado
                        </a>
                    </div>

                    <!-- MENSAJE FLASH -->
                    <?php if($this->session->flashdata('error')): ?>
                        <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- FORMULARIO -->
                    <form action="<?php echo base_url(); ?>sisvent/admin/cashboxes/update" method="POST">
                        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">

                            <!-- ID oculto -->
                            <input type="hidden" name="idCashbox" value="<?php echo $cashbox->idCashbox; ?>"/>

                            <!-- Nombre -->
                            <label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600' : ''; ?>">
                                <span class="text-gray-700">Nombre <span class="text-red-500">*</span></span>
                                <input class="form-input" type="text" name="name"
                                       value="<?php echo !empty(form_error('name')) ? set_value('name') : $cashbox->name; ?>"
                                       required/>
                                <?php echo form_error('name', "<span class='text-xs text-red-600'>", "</span>"); ?>
                            </label>

                            <!-- Código -->
                            <label class="block text-sm mt-4 <?php echo !empty(form_error('code')) ? 'border-red-600' : ''; ?>">
                                <span class="text-gray-700">Código <span class="text-red-500">*</span></span>
                                <input class="form-input" type="text" name="code"
                                       value="<?php echo !empty(form_error('code')) ? set_value('code') : $cashbox->code; ?>"
                                       required/>
                                <?php echo form_error('code', "<span class='text-xs text-red-600'>", "</span>"); ?>
                            </label>

                            <!-- Tipo -->
                            <label class="block text-sm mt-4 <?php echo !empty(form_error('type')) ? 'border-red-600' : ''; ?>">
                                <span class="text-gray-700">Tipo <span class="text-red-500">*</span></span>
                                <select class="form-input form-select" name="type" required>
                                    <option value="principal" <?php echo ($cashbox->type == 'principal') ? 'selected' : ''; ?>>Principal</option>
                                    <option value="secundaria" <?php echo ($cashbox->type == 'secundaria') ? 'selected' : ''; ?>>Secundaria</option>
                                    <option value="chica" <?php echo ($cashbox->type == 'chica') ? 'selected' : ''; ?>>Chica</option>
                                </select>
                                <?php echo form_error('type', "<span class='text-xs text-red-600'>", "</span>"); ?>
                            </label>

                            <!-- Tienda -->
                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Tienda <span class="text-red-500">*</span></span>
                                <select class="form-input form-select" name="storeId" required>
                                    <option value="0" <?php echo ($cashbox->storeId == 0) ? 'selected' : ''; ?>>Todas las tiendas</option>
                                    <?php foreach($stores as $store): ?>
                                        <option value="<?php echo $store->idStore; ?>" <?php echo ($cashbox->storeId == $store->idStore) ? 'selected' : ''; ?>><?php echo $store->name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <!-- Info de solo lectura -->
                            <div class="mt-4 pt-4 border-t">
                                <p class="text-xs text-gray-500">Estado: <span class="font-semibold capitalize"><?php echo $cashbox->status; ?></span></p>
                                <p class="text-xs text-gray-500">Saldo actual: $<?php echo number_format($cashbox->currentBalance, 2); ?></p>
                            </div>

                            <!-- Botones -->
                            <div class="flex items-center space-x-3 mt-6">
                                <input type="submit"
                                       class="px-4 py-2 text-sm font-medium leading-5 text-white
                                              bg-mam-blue-dark border border-transparent rounded-lg
                                              hover:bg-mam-blue-dark focus:outline-none"
                                       value="Guardar"/>
                                <a href="<?php echo base_url(); ?>sisvent/admin/cashboxes"
                                   class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">
                                    Cancelar
                                </a>
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
