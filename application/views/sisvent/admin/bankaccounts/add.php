<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Nueva Cuenta Bancaria</title>
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
                        <h2 class="text-lg font-semibold text-gray-600">Nueva Cuenta Bancaria</h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts"
                           class="text-sm text-mam-blue-dark hover:underline">← Volver</a>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                        <div class="p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo base_url(); ?>sisvent/admin/bankaccounts/store" method="POST">
                        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">

                            <!-- Datos principales -->
                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Nombre del Banco <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="text" name="bankName"
                                           value="<?php echo set_value('bankName'); ?>"
                                           placeholder="Ej: Banco Davivienda" required/>
                                    <?php echo form_error('bankName', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Número de Cuenta <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="text" name="accountNumber"
                                           value="<?php echo set_value('accountNumber'); ?>"
                                           placeholder="Ej: 1234567890" required/>
                                    <?php echo form_error('accountNumber', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Tipo de Cuenta <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="accountType" required>
                                        <option value="corriente" <?php echo set_select('accountType','corriente'); ?>>Corriente</option>
                                        <option value="ahorros" <?php echo set_select('accountType','ahorros'); ?>>Ahorros</option>
                                        <option value="credito" <?php echo set_select('accountType','credito'); ?>>Crédito</option>
                                        <option value="otro" <?php echo set_select('accountType','otro'); ?>>Otro</option>
                                    </select>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Tienda <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="storeId" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach($stores as $store): ?>
                                            <option value="<?php echo $store->idStore; ?>" <?php echo set_select('storeId', $store->idStore); ?>><?php echo $store->name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Saldo Inicial</span>
                                    <div class="flex items-center">
                                        <span class="px-3 py-2 bg-gray-100 border border-r-0 border-gray-300 rounded-l-lg text-gray-600">$</span>
                                        <input class="form-input rounded-l-none" type="number" name="initialBalance"
                                               step="0.01" min="0" value="<?php echo set_value('initialBalance', '0'); ?>"/>
                                    </div>
                                </label>
                            </div>

                            <!-- Datos del titular -->
                            <h3 class="text-sm font-semibold text-gray-600 mt-6 mb-2 border-t pt-4">Datos del Titular</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm">
                                    <span class="text-gray-700">Nombre del Titular</span>
                                    <input class="form-input" type="text" name="ownerName"
                                           value="<?php echo set_value('ownerName'); ?>"/>
                                </label>
                                <label class="block text-sm">
                                    <span class="text-gray-700">NIT / CC</span>
                                    <input class="form-input" type="text" name="ownerIdNumber"
                                           value="<?php echo set_value('ownerIdNumber'); ?>"/>
                                </label>
                                <label class="block text-sm">
                                    <span class="text-gray-700">Sucursal</span>
                                    <input class="form-input" type="text" name="branchOffice"
                                           value="<?php echo set_value('branchOffice'); ?>"/>
                                </label>
                                <label class="block text-sm">
                                    <span class="text-gray-700">Teléfono de Contacto</span>
                                    <input class="form-input" type="text" name="contactPhone"
                                           value="<?php echo set_value('contactPhone'); ?>"/>
                                </label>
                                <label class="block text-sm">
                                    <span class="text-gray-700">Email de Contacto</span>
                                    <input class="form-input" type="email" name="contactEmail"
                                           value="<?php echo set_value('contactEmail'); ?>"/>
                                </label>
                            </div>

                            <!-- Botones -->
                            <div class="flex items-center space-x-3 mt-6">
                                <input type="submit"
                                       class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-lg hover:bg-mam-blue-dark"
                                       value="Guardar"/>
                                <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts"
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
