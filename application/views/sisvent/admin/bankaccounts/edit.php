<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Editar Cuenta Bancaria</title>
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
                        <h2 class="text-lg font-semibold text-gray-600">Editar Cuenta Bancaria</h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts"
                           class="text-sm text-mam-blue-dark hover:underline">← Volver</a>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                        <div class="p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo base_url(); ?>sisvent/admin/bankaccounts/update" method="POST">
                        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">

                            <input type="hidden" name="idBankAccount" value="<?php echo $bankAccount->idBankAccount; ?>"/>

                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Nombre del Banco <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="text" name="bankName"
                                           value="<?php echo !empty(form_error('bankName')) ? set_value('bankName') : $bankAccount->bankName; ?>" required/>
                                    <?php echo form_error('bankName', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Número de Cuenta <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="text" name="accountNumber"
                                           value="<?php echo !empty(form_error('accountNumber')) ? set_value('accountNumber') : $bankAccount->accountNumber; ?>" required/>
                                    <?php echo form_error('accountNumber', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Tipo de Cuenta <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="accountType" required>
                                        <option value="corriente" <?php echo ($bankAccount->accountType=='corriente') ? 'selected' : ''; ?>>Corriente</option>
                                        <option value="ahorros" <?php echo ($bankAccount->accountType=='ahorros') ? 'selected' : ''; ?>>Ahorros</option>
                                        <option value="credito" <?php echo ($bankAccount->accountType=='credito') ? 'selected' : ''; ?>>Crédito</option>
                                        <option value="otro" <?php echo ($bankAccount->accountType=='otro') ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Estado</span>
                                    <select class="form-input form-select" name="status">
                                        <option value="activa" <?php echo ($bankAccount->status=='activa') ? 'selected' : ''; ?>>Activa</option>
                                        <option value="inactiva" <?php echo ($bankAccount->status=='inactiva') ? 'selected' : ''; ?>>Inactiva</option>
                                        <option value="bloqueada" <?php echo ($bankAccount->status=='bloqueada') ? 'selected' : ''; ?>>Bloqueada</option>
                                    </select>
                                </label>
                            </div>

                            <h3 class="text-sm font-semibold text-gray-600 mt-6 mb-2 border-t pt-4">Datos del Titular</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm">
                                    <span class="text-gray-700">Nombre del Titular</span>
                                    <input class="form-input" type="text" name="ownerName"
                                           value="<?php echo $bankAccount->ownerName; ?>"/>
                                </label>
                                <label class="block text-sm">
                                    <span class="text-gray-700">NIT / CC</span>
                                    <input class="form-input" type="text" name="ownerIdNumber"
                                           value="<?php echo $bankAccount->ownerIdNumber; ?>"/>
                                </label>
                                <label class="block text-sm">
                                    <span class="text-gray-700">Sucursal</span>
                                    <input class="form-input" type="text" name="branchOffice"
                                           value="<?php echo $bankAccount->branchOffice; ?>"/>
                                </label>
                                <label class="block text-sm">
                                    <span class="text-gray-700">Teléfono</span>
                                    <input class="form-input" type="text" name="contactPhone"
                                           value="<?php echo $bankAccount->contactPhone; ?>"/>
                                </label>
                                <label class="block text-sm">
                                    <span class="text-gray-700">Email</span>
                                    <input class="form-input" type="email" name="contactEmail"
                                           value="<?php echo $bankAccount->contactEmail; ?>"/>
                                </label>
                            </div>

                            <!-- Info solo lectura -->
                            <div class="mt-4 pt-4 border-t">
                                <p class="text-xs text-gray-500">Saldo actual: $<?php echo number_format($bankAccount->currentBalance, 2); ?></p>
                                <p class="text-xs text-gray-500">Saldo inicial: $<?php echo number_format($bankAccount->initialBalance, 2); ?></p>
                            </div>

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
