<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Nuevo Gasto</title>
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
                        <h2 class="text-lg font-semibold text-gray-600">Nuevo Gasto</h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/expenses"
                           class="text-sm text-mam-blue-dark hover:underline">← Volver</a>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                        <div class="p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo base_url(); ?>sisvent/admin/expenses/store" method="POST">
                        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">

                            <!-- Datos principales -->
                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Codigo</span>
                                    <input class="form-input bg-gray-100" type="text" name="code"
                                           value="<?php echo $nextCode; ?>" readonly/>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Fecha <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="date" name="expense_date"
                                           value="<?php echo set_value('expense_date', date('Y-m-d')); ?>" required/>
                                    <?php echo form_error('expense_date', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                </label>
                            </div>

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Descripcion <span class="text-red-500">*</span></span>
                                <textarea class="form-input" name="description" rows="2" required
                                          placeholder="Descripcion del gasto"><?php echo set_value('description'); ?></textarea>
                                <?php echo form_error('description', "<span class='text-xs text-red-600'>", "</span>"); ?>
                            </label>

                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Proveedor <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="provider_id" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach($providers as $prov): ?>
                                            <option value="<?php echo $prov->idProvider; ?>" <?php echo set_select('provider_id', $prov->idProvider); ?>>
                                                <?php echo $prov->name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php echo form_error('provider_id', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Categoria <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="expense_category_id" id="expense_category_id" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?php echo $cat->id; ?>"
                                                    data-subaccount="<?php echo $cat->accounting_subaccount_id; ?>"
                                                    data-subaccount-name="<?php echo $cat->subaccount_puc . ' - ' . $cat->subaccount_name; ?>"
                                                    <?php echo set_select('expense_category_id', $cat->id); ?>>
                                                <?php echo $cat->name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php echo form_error('expense_category_id', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                    <p class="text-xs text-gray-500 mt-1" id="category-account-info"></p>
                                </label>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Monto <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="number" name="amount" step="0.01" min="0.01"
                                           value="<?php echo set_value('amount'); ?>" required/>
                                    <?php echo form_error('amount', "<span class='text-xs text-red-600'>", "</span>"); ?>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Tienda <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="store_id" required>
                                        <?php foreach($stores as $store): ?>
                                            <option value="<?php echo $store->idStore; ?>"
                                                    <?php echo ($store->idStore == $this->session->userdata('user_data')['store']) ? 'selected' : ''; ?>>
                                                <?php echo $store->name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Estado</span>
                                    <select class="form-input form-select" name="status" id="expense_status">
                                        <option value="pendiente" <?php echo set_select('status', 'pendiente'); ?>>Pendiente</option>
                                        <option value="pagado" <?php echo set_select('status', 'pagado'); ?>>Pagado</option>
                                    </select>
                                </label>
                            </div>

                            <!-- Método de Pago y Caja/Banco -->
                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <label class="block text-sm">
                                    <span class="text-gray-700">Metodo de Pago <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="payment_method" id="payment_method">
                                        <option value="efectivo">Efectivo</option>
                                        <option value="transferencia">Transferencia</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </label>
                                <div>
                                    <span class="block text-sm text-gray-700 mb-1">Caja / Banco <span class="text-red-500">*</span></span>
                                    <div id="caja-select-wrapper">
                                        <select class="form-input form-select" name="source_id_caja" id="source_id_caja">
                                            <option value="">Seleccione...</option>
                                            <?php foreach($cashboxes as $cb): ?>
                                                <option value="<?php echo $cb->idCashbox; ?>">
                                                    <?php echo $cb->name; ?> ($<?php echo number_format($cb->currentBalance, 2); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div id="banco-select-wrapper" style="display:none;">
                                        <select class="form-input form-select" name="source_id_banco" id="source_id_banco">
                                            <option value="">Seleccione...</option>
                                            <?php foreach($bankaccounts as $ba): ?>
                                                <option value="<?php echo $ba->idBankAccount; ?>">
                                                    <?php echo $ba->bankName . ' - ' . $ba->accountNumber; ?> ($<?php echo number_format($ba->currentBalance, 2); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Referencia (solo visible si estado = pagado) -->
                            <div id="payment-section" class="mt-4" style="display:none;">
                                <label class="block text-sm">
                                    <span class="text-gray-700">Referencia / Comprobante</span>
                                    <input class="form-input" type="text" name="voucher_reference"
                                           placeholder="No. de comprobante o referencia"/>
                                </label>
                            </div>

                            <!-- Campos ocultos -->
                            <input type="hidden" name="source_type" id="source_type" value="caja"/>
                            <input type="hidden" name="source_id" id="source_id" value=""/>

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Observaciones</span>
                                <textarea class="form-input" name="observations" rows="2"
                                          placeholder="Observaciones opcionales"><?php echo set_value('observations'); ?></textarea>
                            </label>

                            <div class="flex items-center space-x-3 mt-6">
                                <input type="submit"
                                       class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-lg hover:bg-mam-blue-dark"
                                       value="Guardar"/>
                                <a href="<?php echo base_url(); ?>sisvent/admin/expenses"
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
        // Toggle referencia section
        $(document).on('change', '#expense_status', function() {
            if ($(this).val() === 'pagado') {
                $('#payment-section').show();
            } else {
                $('#payment-section').hide();
            }
        });

        // Toggle caja/banco based on payment method
        function toggleSourceByMethod() {
            var method = $('#payment_method').val();
            if (method === 'transferencia') {
                $('#caja-select-wrapper').hide();
                $('#banco-select-wrapper').show();
                $('#source_type').val('banco');
            } else {
                $('#caja-select-wrapper').show();
                $('#banco-select-wrapper').hide();
                $('#source_type').val('caja');
            }
        }
        $(document).on('change', '#payment_method', toggleSourceByMethod);

        // Show category accounting info
        $(document).on('change', '#expense_category_id', function() {
            var selected = $(this).find(':selected');
            var subName = selected.data('subaccount-name');
            if (subName && subName !== 'null - null' && subName !== ' - ') {
                $('#category-account-info').text('Cuenta contable: ' + subName);
            } else {
                $('#category-account-info').text('Sin cuenta contable asignada');
            }
        });

        // Set source_id before submit
        $(document).on('submit', 'form', function() {
            var sourceType = $('#source_type').val();
            if (sourceType === 'banco') {
                $('#source_id').val($('#source_id_banco').val());
            } else {
                $('#source_id').val($('#source_id_caja').val());
            }
        });
    </script>
</body>
</html>
