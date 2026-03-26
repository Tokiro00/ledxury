<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Conciliación — <?php echo $bankAccount->bankName; ?></title>
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
                        <h2 class="text-lg font-semibold text-gray-600">
                            Conciliación Bancaria — <?php echo $bankAccount->bankName; ?> (***<?php echo substr($bankAccount->accountNumber, -4); ?>)
                        </h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/view/<?php echo $bankAccount->idBankAccount; ?>"
                           class="text-sm text-mam-blue-petroleo hover:underline">← Volver</a>
                    </div>

                    <!-- FLASH MESSAGES -->
                    <?php if($this->session->flashdata('error')): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-red-700"><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- INFO DEL BANCO -->
                    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                        <div class="grid grid-cols-4 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Banco</p>
                                <p class="font-semibold text-gray-700"><?php echo $bankAccount->bankName; ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Cuenta</p>
                                <p class="font-semibold text-gray-700">***<?php echo substr($bankAccount->accountNumber, -4); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Tipo</p>
                                <p class="font-semibold text-gray-700 capitalize"><?php echo $bankAccount->accountType; ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Estado</p>
                                <p class="font-semibold text-gray-700 capitalize"><?php echo $bankAccount->status; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- FORMULARIO DE CONCILIACIÓN -->
                    <form method="post" action="<?php echo base_url(); ?>sisvent/admin/bankaccounts/saveReconciliation">
                        <?php echo $this->security->form_open_tag(''); ?>
                        <input type="hidden" name="bankAccountId" value="<?php echo $bankAccount->idBankAccount; ?>"/>

                        <!-- COMPARACIÓN DE SALDOS -->
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <!-- Saldo en libros (nuestro registro) -->
                            <div class="bg-white rounded-lg shadow-sm p-4 border-t-4 border-mam-blue-petroleo">
                                <p class="text-xs text-gray-500 uppercase mb-1">Saldo en Libros</p>
                                <p class="text-xl font-bold text-gray-800">$<?php echo number_format($bookBalance, 2); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Según nuestros registros</p>
                            </div>

                            <!-- Saldo según banco (entrada manual) -->
                            <div class="bg-white rounded-lg shadow-sm p-4 border-t-4 border-green-500">
                                <p class="text-xs text-gray-500 uppercase mb-1">Saldo según Banco</p>
                                <div class="flex items-center mt-1">
                                    <span class="px-2 py-1 bg-gray-100 border border-r-0 border-gray-300 rounded-l text-gray-600 text-sm">$</span>
                                    <input type="number" name="bankBalance" id="input-bank-balance"
                                           step="0.01" min="0" value="0" placeholder="0.00"
                                           class="form-input rounded-l-none text-lg font-bold"/>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Del estado de cuenta</p>
                            </div>

                            <!-- Diferencia (calculada en JS) -->
                            <div class="bg-white rounded-lg shadow-sm p-4 border-t-4 border-gray-300">
                                <p class="text-xs text-gray-500 uppercase mb-1">Diferencia</p>
                                <p class="text-xl font-bold" id="difference-display">-</p>
                                <p class="text-xs text-gray-400 mt-1" id="difference-label">Saldo banco - Saldo libros</p>
                            </div>
                        </div>

                        <!-- CAMPOS DEL FORMULARIO -->
                        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                            <div class="grid grid-cols-2 gap-6">
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Fecha del Estado de Cuenta</span>
                                    <input type="date" name="statementDate"
                                           value="<?php echo date('Y-m-d'); ?>"
                                           class="form-input mt-1" required/>
                                </label>
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Observaciones</span>
                                    <textarea name="notes" class="form-input mt-1" rows="2"
                                              placeholder="Notas sobre la conciliación..."></textarea>
                                </label>
                            </div>
                        </div>

                        <!-- BOTONES -->
                        <div class="flex items-center justify-between mb-6">
                            <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/view/<?php echo $bankAccount->idBankAccount; ?>"
                               class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
                            <button type="submit"
                                    class="px-6 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue">
                                Guardar Conciliación
                            </button>
                        </div>
                    </form>

                    <!-- ÚLTIMA CONCILIACIÓN -->
                    <?php if($lastReconciliation): ?>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-3">Última Conciliación</p>
                            <div class="grid grid-cols-5 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Fecha</p>
                                    <p class="font-semibold text-gray-700"><?php echo date('d/m/Y', strtotime($lastReconciliation->reconciliationDate)); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Estado Cuenta</p>
                                    <p class="font-semibold text-gray-700"><?php echo date('d/m/Y', strtotime($lastReconciliation->statementDate)); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Saldo Libros</p>
                                    <p class="font-semibold text-gray-700">$<?php echo number_format($lastReconciliation->bookBalance, 2); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Saldo Banco</p>
                                    <p class="font-semibold text-gray-700">$<?php echo number_format($lastReconciliation->bankBalance, 2); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Diferencia</p>
                                    <p class="font-semibold <?php echo ($lastReconciliation->difference == 0) ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo ($lastReconciliation->difference >= 0) ? '+' : ''; ?>$<?php echo number_format($lastReconciliation->difference, 2); ?>
                                    </p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                Estado: <span class="capitalize font-semibold"><?php echo $lastReconciliation->status; ?></span>
                                — Realizada por: <?php echo $lastReconciliation->reconciledBy; ?>
                            </p>
                        </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    (function() {
        var bookBalance = <?php echo (float)$bookBalance; ?>;

        function formatMoney(val) {
            return '$' + Math.abs(val).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        $('#input-bank-balance').on('input', function() {
            var bankBal = parseFloat($(this).val()) || 0;
            var diff = bankBal - bookBalance;
            var $el = $('#difference-display');

            $el.text((diff >= 0 ? '+' : '-') + formatMoney(diff));
            $el.removeClass('text-green-600 text-red-600 text-gray-800');
            $el.addClass(diff === 0 ? 'text-green-600' : (diff > 0 ? 'text-red-600' : 'text-red-600'));

            $('#difference-label').text(diff === 0 ? 'Conciliado' : 'Hay diferencia');
        });
    })();
    </script>
</body>
</html>
