<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Pagar Factura #<?php echo $bill->invoiceNumber; ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid max-w-3xl">
                    <div class="flex items-center mb-4 mt-2">
                        <a href="<?php echo base_url(); ?>sisvent/admin/accountspayable/view/<?php echo $bill->idSupplierInvoice; ?>" class="mr-4 text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </a>
                        <h2 class="text-lg font-semibold text-gray-600">Pagar Factura #<?php echo $bill->invoiceNumber; ?></h2>
                    </div>

                    <?php if($this->session->flashdata("error")): ?>
                    <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                        <p><?php echo $this->session->flashdata("error"); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Invoice Summary -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Proveedor</p>
                                <p class="text-lg font-semibold text-gray-800"><?php echo $bill->providerName; ?></p>
                                <p class="text-sm text-gray-600"><?php echo $bill->providerIdNum; ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500 uppercase">Factura</p>
                                <p class="text-lg font-mono font-semibold text-gray-800"><?php echo $bill->invoiceNumber; ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-200">
                            <div class="text-center">
                                <p class="text-xs text-gray-500 uppercase">Total</p>
                                <p class="text-xl font-bold text-gray-800">$<?php echo number_format($bill->total, 2); ?></p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500 uppercase">Pagado</p>
                                <p class="text-xl font-bold text-green-600">$<?php echo number_format($bill->paidAmount, 2); ?></p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500 uppercase">Saldo</p>
                                <p class="text-xl font-bold text-red-600">$<?php echo number_format($bill->balance, 2); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form action="<?php echo base_url(); ?>sisvent/admin/accountspayable/processPayment" method="POST">
                        <input type="hidden" name="bill_id" value="<?php echo $bill->idSupplierInvoice; ?>">

                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">Datos del Pago</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Monto a Pagar <span class="text-red-500">*</span></span>
                                    <div class="relative mt-1">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
                                        <input type="number" name="amount" id="payment-amount" class="form-input pl-8"
                                               step="0.01" min="0.01" max="<?php echo $bill->balance; ?>"
                                               value="<?php echo $bill->balance; ?>" required>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Máximo: $<?php echo number_format($bill->balance, 2); ?></p>
                                </label>

                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Fecha de Pago</span>
                                    <input type="date" name="payment_date" class="form-input mt-1" value="<?php echo date('Y-m-d'); ?>">
                                </label>
                            </div>

                            <div class="mt-4">
                                <span class="block text-sm text-gray-700 font-medium mb-1">Origen del Pago <span class="text-red-500">*</span></span>
                                <div class="flex gap-2">
                                    <select id="cash-source-type" name="cash_source_type" class="form-input form-select" style="max-width:140px" required>
                                        <option value="cashbox">Caja</option>
                                        <option value="bank">Banco</option>
                                    </select>
                                    <div id="cash-source-cashbox-wrapper" class="flex-1">
                                        <select id="cash-source-cashbox" name="cash_source_cashbox" class="form-input form-select w-full" required>
                                            <option value="" disabled selected>Selecciona una caja</option>
                                            <?php foreach($cashboxes as $box): ?>
                                                <option value="<?php echo $box->idCashbox; ?>" data-balance="<?php echo $box->currentBalance; ?>">
                                                    <?php echo $box->name; ?> - Saldo: $<?php echo number_format($box->currentBalance, 2); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div id="cash-source-bank-wrapper" class="flex-1 hidden">
                                        <select id="cash-source-bank" name="cash_source_bank" class="form-input form-select w-full">
                                            <option value="" disabled selected>Selecciona un banco</option>
                                            <?php foreach($bankaccounts as $bank): ?>
                                                <option value="<?php echo $bank->idBankAccount; ?>" data-balance="<?php echo $bank->currentBalance; ?>">
                                                    <?php echo $bank->bankName; ?> - <?php echo $bank->accountNumber; ?> - Saldo: $<?php echo number_format($bank->currentBalance, 2); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <p id="balance-warning" class="text-xs text-red-500 mt-1 hidden">El saldo disponible es menor al monto a pagar.</p>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Referencia / Número de Comprobante</span>
                                    <input type="text" name="reference" class="form-input mt-1" placeholder="Ej: TRANS-001234, CHQ-5678">
                                </label>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Notas</span>
                                    <textarea name="notes" class="form-input mt-1" rows="2" placeholder="Observaciones adicionales"></textarea>
                                </label>
                            </div>

                            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                                <h4 class="text-sm font-semibold text-blue-700 mb-2">Asiento Contable Generado</h4>
                                <div class="text-xs">
                                    <div class="flex justify-between py-1 border-b border-blue-200">
                                        <span class="text-blue-600">Débito: 220505 - Proveedores Nacionales</span>
                                        <span class="font-medium" id="debit-amount">$<?php echo number_format($bill->balance, 2); ?></span>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <span class="text-red-600">Crédito: Caja/Banco (según selección)</span>
                                        <span class="font-medium" id="credit-amount">$<?php echo number_format($bill->balance, 2); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex gap-4">
                                <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                    Confirmar Pago
                                </button>
                                <a href="<?php echo base_url(); ?>sisvent/admin/accountspayable/view/<?php echo $bill->idSupplierInvoice; ?>" class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sourceType = document.getElementById('cash-source-type');
        const cashboxWrapper = document.getElementById('cash-source-cashbox-wrapper');
        const bankWrapper = document.getElementById('cash-source-bank-wrapper');
        const cashboxSelect = document.getElementById('cash-source-cashbox');
        const bankSelect = document.getElementById('cash-source-bank');
        const amountInput = document.getElementById('payment-amount');
        const balanceWarning = document.getElementById('balance-warning');
        const debitAmount = document.getElementById('debit-amount');
        const creditAmount = document.getElementById('credit-amount');

        function toggleSource() {
            if (sourceType.value === 'cashbox') {
                cashboxWrapper.classList.remove('hidden');
                bankWrapper.classList.add('hidden');
                cashboxSelect.setAttribute('required', 'required');
                bankSelect.removeAttribute('required');
            } else {
                cashboxWrapper.classList.add('hidden');
                bankWrapper.classList.remove('hidden');
                bankSelect.setAttribute('required', 'required');
                cashboxSelect.removeAttribute('required');
            }
            checkBalance();
        }

        function checkBalance() {
            const amount = parseFloat(amountInput.value) || 0;
            let availableBalance = 0;

            if (sourceType.value === 'cashbox') {
                const selected = cashboxSelect.options[cashboxSelect.selectedIndex];
                if (selected && selected.dataset.balance) {
                    availableBalance = parseFloat(selected.dataset.balance);
                }
            } else {
                const selected = bankSelect.options[bankSelect.selectedIndex];
                if (selected && selected.dataset.balance) {
                    availableBalance = parseFloat(selected.dataset.balance);
                }
            }

            if (amount > availableBalance && availableBalance > 0) {
                balanceWarning.classList.remove('hidden');
            } else {
                balanceWarning.classList.add('hidden');
            }
        }

        function updateAmounts() {
            const amount = parseFloat(amountInput.value) || 0;
            const formatted = '$' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            debitAmount.textContent = formatted;
            creditAmount.textContent = formatted;
            checkBalance();
        }

        sourceType.addEventListener('change', toggleSource);
        cashboxSelect.addEventListener('change', checkBalance);
        bankSelect.addEventListener('change', checkBalance);
        amountInput.addEventListener('input', updateAmounts);

        toggleSource();
    });
    </script>
</body>
</html>
