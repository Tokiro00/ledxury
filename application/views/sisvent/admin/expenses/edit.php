<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Editar Gasto</title>
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
                        <h2 class="text-lg font-semibold text-gray-600">Editar Gasto - <?php echo $expense->code; ?></h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/expenses"
                           class="text-sm text-mam-blue-dark hover:underline">← Volver</a>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                        <div class="p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if($expense->status == 'pagado'): ?>
                        <div class="p-4 mb-4 text-sm font-semibold text-yellow-800 bg-yellow-100 rounded-lg">
                            Este gasto ya fue pagado y no puede ser editado.
                        </div>
                    <?php else: ?>

                    <form action="<?php echo base_url(); ?>sisvent/admin/expenses/update" method="POST">
                        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">

                            <input type="hidden" name="id" value="<?php echo $expense->id; ?>"/>

                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Codigo</span>
                                    <input class="form-input bg-gray-100" type="text" value="<?php echo $expense->code; ?>" readonly/>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Fecha <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="date" name="expense_date"
                                           value="<?php echo $expense->expense_date; ?>" required/>
                                </label>
                            </div>

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Descripcion <span class="text-red-500">*</span></span>
                                <textarea class="form-input" name="description" rows="2" required><?php echo $expense->description; ?></textarea>
                            </label>

                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Proveedor <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="provider_id" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach($providers as $prov): ?>
                                            <option value="<?php echo $prov->idProvider; ?>" <?php echo ($expense->provider_id == $prov->idProvider) ? 'selected' : ''; ?>>
                                                <?php echo $prov->name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Categoria <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="expense_category_id" id="expense_category_id" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?php echo $cat->id; ?>" <?php echo ($expense->expense_category_id == $cat->id) ? 'selected' : ''; ?>
                                                    data-subaccount-name="<?php echo $cat->subaccount_puc . ' - ' . $cat->subaccount_name; ?>">
                                                <?php echo $cat->name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1" id="category-account-info">
                                        <?php if($expense->accounting_subaccount_id): ?>
                                            Cuenta contable asignada
                                        <?php endif; ?>
                                    </p>
                                </label>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Monto <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="number" name="amount" step="0.01" min="0.01"
                                           value="<?php echo $expense->amount; ?>" required/>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Tienda <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="store_id" required>
                                        <?php foreach($stores as $store): ?>
                                            <option value="<?php echo $store->idStore; ?>" <?php echo ($expense->store_id == $store->idStore) ? 'selected' : ''; ?>>
                                                <?php echo $store->name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="block text-sm mt-4">
                                    <span class="text-gray-700">Estado</span>
                                    <select class="form-input form-select" name="status" id="expense_status">
                                        <option value="pendiente" <?php echo ($expense->status == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="pagado" <?php echo ($expense->status == 'pagado') ? 'selected' : ''; ?>>Pagado</option>
                                    </select>
                                </label>
                            </div>

                            <!-- Seccion de pago -->
                            <div id="payment-section" class="mt-4 pt-4 border-t" style="display:none;">
                                <h3 class="text-sm font-semibold text-gray-600 mb-2">Datos de Pago</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <label class="block text-sm">
                                        <span class="text-gray-700">Pagar desde</span>
                                        <select class="form-input form-select" name="source_type" id="source_type">
                                            <option value="caja">Caja</option>
                                            <option value="banco">Banco</option>
                                        </select>
                                    </label>
                                    <div>
                                        <label class="block text-sm" id="caja-select-wrapper">
                                            <span class="text-gray-700">Caja</span>
                                            <select class="form-input form-select" name="source_id_caja" id="source_id_caja">
                                                <option value="">Seleccione...</option>
                                                <?php foreach($cashboxes as $cb): ?>
                                                    <option value="<?php echo $cb->idCashbox; ?>">
                                                        <?php echo $cb->name; ?> ($<?php echo number_format($cb->currentBalance, 2); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="block text-sm" id="banco-select-wrapper" style="display:none;">
                                            <span class="text-gray-700">Banco</span>
                                            <select class="form-input form-select" name="source_id_banco" id="source_id_banco">
                                                <option value="">Seleccione...</option>
                                                <?php foreach($bankaccounts as $ba): ?>
                                                    <option value="<?php echo $ba->idBankAccount; ?>">
                                                        <?php echo $ba->bankName . ' - ' . $ba->accountNumber; ?> ($<?php echo number_format($ba->currentBalance, 2); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mt-2">
                                    <label class="block text-sm">
                                        <span class="text-gray-700">Metodo de Pago</span>
                                        <select class="form-input form-select" name="payment_method">
                                            <option value="efectivo" <?php echo ($expense->payment_method == 'efectivo') ? 'selected' : ''; ?>>Efectivo</option>
                                            <option value="transferencia" <?php echo ($expense->payment_method == 'transferencia') ? 'selected' : ''; ?>>Transferencia</option>
                                            <option value="cheque" <?php echo ($expense->payment_method == 'cheque') ? 'selected' : ''; ?>>Cheque</option>
                                            <option value="otro" <?php echo ($expense->payment_method == 'otro') ? 'selected' : ''; ?>>Otro</option>
                                        </select>
                                    </label>
                                    <label class="block text-sm">
                                        <span class="text-gray-700">Referencia / Comprobante</span>
                                        <input class="form-input" type="text" name="voucher_reference"
                                               value="<?php echo $expense->voucher_reference; ?>"/>
                                    </label>
                                </div>
                            </div>

                            <input type="hidden" name="source_id" id="source_id" value=""/>

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Observaciones</span>
                                <textarea class="form-input" name="observations" rows="2"><?php echo $expense->observations; ?></textarea>
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

                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
        // Toggle payment section
        $(document).on('change', '#expense_status', function() {
            if ($(this).val() === 'pagado') {
                $('#payment-section').show();
            } else {
                $('#payment-section').hide();
            }
        });

        // Toggle caja/banco
        $(document).on('change', '#source_type', function() {
            if ($(this).val() === 'caja') {
                $('#caja-select-wrapper').show();
                $('#banco-select-wrapper').hide();
            } else {
                $('#caja-select-wrapper').hide();
                $('#banco-select-wrapper').show();
            }
        });

        // Category info
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
            if (sourceType === 'caja') {
                $('#source_id').val($('#source_id_caja').val());
            } else {
                $('#source_id').val($('#source_id_banco').val());
            }
        });

        // Show payment section if already pagado
        if ($('#expense_status').val() === 'pagado') {
            $('#payment-section').show();
        }
    </script>
</body>
</html>
