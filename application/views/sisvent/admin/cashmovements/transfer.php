<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Transferencia</title>
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
                        <h2 class="text-lg font-semibold text-gray-600">Transferencia entre Cajas/Bancos</h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements"
                           class="text-sm text-mam-blue-petroleo hover:underline">← Volver</a>
                    </div>

                    <?php if($this->session->flashdata('error')): ?>
                        <div class="p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo base_url(); ?>sisvent/admin/cashmovements/processTransfer" method="POST">
                        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">

                            <!-- ORIGEN -->
                            <h3 class="text-sm font-semibold text-gray-600 mt-4 mb-2">Origen</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm">
                                    <span class="text-gray-700">Tipo <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="sourceType" id="source-type">
                                        <option value="caja">Caja</option>
                                        <option value="banco">Banco</option>
                                    </select>
                                </label>
                                <label class="block text-sm">
                                    <span class="text-gray-700">Cuenta <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="sourceId" id="source-id">
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
                                </label>
                            </div>

                            <!-- FLECHA -->
                            <div class="text-center my-4">
                                <svg class="w-6 h-6 mx-auto text-mam-blue-petroleo" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>

                            <!-- DESTINO -->
                            <h3 class="text-sm font-semibold text-gray-600 mb-2">Destino</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="block text-sm">
                                    <span class="text-gray-700">Tipo <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="destinationType" id="dest-type">
                                        <option value="caja">Caja</option>
                                        <option value="banco">Banco</option>
                                    </select>
                                </label>
                                <label class="block text-sm">
                                    <span class="text-gray-700">Cuenta <span class="text-red-500">*</span></span>
                                    <select class="form-input form-select" name="destinationId" id="dest-id">
                                        <?php foreach($cashboxes as $cb): ?>
                                            <option value="<?php echo $cb->idCashbox; ?>" data-type="caja">
                                                <?php echo $cb->name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php foreach($bankAccounts as $ba): ?>
                                            <option value="<?php echo $ba->idBankAccount; ?>" data-type="banco">
                                                <?php echo $ba->bankName . ' - ' . substr($ba->accountNumber, -4); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>

                            <!-- MONTO Y CONCEPTO -->
                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Monto <span class="text-red-500">*</span></span>
                                <div class="flex items-center">
                                    <span class="px-3 py-2 bg-gray-100 border border-r-0 border-gray-300 rounded-l-lg text-gray-600">$</span>
                                    <input class="form-input rounded-l-none" type="number" name="amount"
                                           step="0.01" min="0.01" placeholder="0.00" required/>
                                </div>
                            </label>

                            <label class="block text-sm mt-4">
                                <span class="text-gray-700">Concepto</span>
                                <input class="form-input" type="text" name="concept"
                                       placeholder="Ej: Depósito diario"/>
                            </label>

                            <!-- Botones -->
                            <div class="flex items-center space-x-3 mt-6">
                                <input type="submit"
                                       class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue-petroleo"
                                       value="Ejecutar Transferencia"/>
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
        function filterOptions(selectType, selectId) {
            var selectedType = $(selectType).val();
            $(selectId + ' option').hide();
            $(selectId + ' option[data-type="' + selectedType + '"]').show();
            $(selectId).val($(selectId + ' option[data-type="' + selectedType + '"]:first').val());
        }

        $('#source-type').on('change', function() { filterOptions('#source-type', '#source-id'); });
        $('#dest-type').on('change', function() { filterOptions('#dest-type', '#dest-id'); });

        // Inicializar destino como banco por defecto para evitar origen=destino
        $('#dest-type').val('banco');
        filterOptions('#source-type', '#source-id');
        filterOptions('#dest-type', '#dest-id');
    </script>
</body>
</html>
