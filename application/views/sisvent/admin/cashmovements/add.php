<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Nuevo Movimiento</title>
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
                        <h2 class="text-lg font-semibold text-gray-600">Nuevo Movimiento</h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements"
                           class="text-sm text-mam-blue-petroleo hover:underline">← Volver</a>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                        <div class="p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo base_url(); ?>sisvent/admin/cashmovements/store" method="POST">
                        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">

                            <!-- Tipo de movimiento -->
                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Tipo de Movimiento <span class="text-red-500">*</span></span>
                                <select class="form-input form-select" name="movementType" required>
                                    <option value="">Seleccione...</option>
                                    <option value="ingreso" <?php echo set_select('movementType','ingreso'); ?>>Ingreso</option>
                                    <option value="egreso" <?php echo set_select('movementType','egreso'); ?>>Egreso</option>
                                </select>
                                <?php echo form_error('movementType', "<span class='text-xs text-red-600'>", "</span>"); ?>
                            </label>

                            <!-- Origen (Tipo + Cuenta) -->
                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Tipo de Origen <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="sourceType" id="source-type" required>
                                        <option value="caja" <?php echo set_select('sourceType','caja'); ?>>Caja</option>
                                        <option value="banco" <?php echo set_select('sourceType','banco'); ?>>Banco</option>
                                    </select>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Cuenta <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="sourceId" id="source-id" required>
                                        <?php foreach($cashboxes as $cb): ?>
                                            <option value="<?php echo $cb->idCashbox; ?>" data-type="caja">
                                                <?php echo $cb->name; ?> ($<?php echo number_format($cb->currentBalance, 2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                        <?php foreach($bankAccounts as $ba): ?>
                                            <option value="<?php echo $ba->idBankAccount; ?>" data-type="banco">
                                                <?php echo $ba->bankName . ' - ' . substr($ba->accountNumber, -4); ?> ($<?php echo number_format($ba->currentBalance, 2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php echo form_error('sourceId', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                </label>
                            </div>

                            <!-- Monto -->
                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Monto <span class="text-red-500">*</span></span>
                                <div class="flex items-center">
                                    <span class="px-3 py-2 bg-gray-100 border border-r-0 border-gray-300 rounded-l-lg text-gray-600">$</span>
                                    <input class="form-input rounded-l-none" type="number" name="amount"
                                           step="0.01" min="0.01" value="<?php echo set_value('amount'); ?>"
                                           placeholder="0.00" required/>
                                </div>
                                <?php echo form_error('amount', "<span class='text-xs text-red-600'>", "</span>"); ?>
                            </label>

                            <!-- Concepto -->
                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Concepto <span class="text-red-500">*</span></span>
                                <input class="form-input" type="text" name="concept"
                                       value="<?php echo set_value('concept'); ?>"
                                       placeholder="Descripción del movimiento" required/>
                                <?php echo form_error('concept', "<span class='text-xs text-red-600'>", "</span>"); ?>
                            </label>

                            <!-- Categoría -->
                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Categoría</span>
                                <select class="form-input form-select" name="category">
                                    <option value="otro" <?php echo set_select('category','otro'); ?>>Otro</option>
                                    <option value="venta" <?php echo set_select('category','venta'); ?>>Venta</option>
                                    <option value="pago_proveedor" <?php echo set_select('category','pago_proveedor'); ?>>Pago a Proveedor</option>
                                    <option value="gasto" <?php echo set_select('category','gasto'); ?>>Gasto</option>
                                    <option value="pago_cliente" <?php echo set_select('category','pago_cliente'); ?>>Pago de Cliente</option>
                                    <option value="nomina" <?php echo set_select('category','nomina'); ?>>Nómina</option>
                                    <option value="impuestos" <?php echo set_select('category','impuestos'); ?>>Impuestos</option>
                                    <option value="prestamo" <?php echo set_select('category','prestamo'); ?>>Préstamo</option>
                                </select>
                            </label>

                            <!-- Notas -->
                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Notas</span>
                                <textarea class="form-input" name="notes" rows="2"
                                          placeholder="Observaciones opcionales..."><?php echo set_value('notes'); ?></textarea>
                            </label>

                            <!-- Botones -->
                            <div class="flex items-center space-x-3 mt-6">
                                <input type="submit"
                                       class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue-petroleo"
                                       value="Registrar Movimiento"/>
                                <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements"
                                   class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
        // Filtrar cuentas según tipo de origen
        $('#source-type').on('change', function() {
            var selectedType = $(this).val();
            $('#source-id option').hide();
            $('#source-id option[data-type="' + selectedType + '"]').show();
            $('#source-id').val($('#source-id option[data-type="' + selectedType + '"]:first').val());
        });
        // Inicializar
        $('#source-type').trigger('change');
    </script>
</body>
</html>
