<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Nuevo Asiento Contable</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => 'sisvent/accounting/entries/list.php','role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto pb-8">
    	 		<div class="px-6 mx-auto grid max-w-4xl">
            <!-- Header -->
            <div class="mt-4 mb-6">
                <a href="<?php echo base_url(); ?>sisvent/accounting/entries" class="text-blue-600 hover:text-blue-800 text-sm flex items-center mb-2">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Volver al Libro Diario
                </a>
                <h2 class="text-2xl font-bold text-gray-800">Nuevo Asiento Contable</h2>
                <p class="text-gray-600 mt-1">Crear un asiento contable manual</p>
            </div>

            <!-- Flash Messages -->
            <?php if($this->session->flashdata('success')): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $this->session->flashdata('success'); ?>
            </div>
            <?php endif; ?>
            <?php if($this->session->flashdata('error')): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $this->session->flashdata('error'); ?>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="post" action="<?php echo base_url(); ?>sisvent/accounting/entries/save" id="entryForm">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Información General</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="entryDate" class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                            <input type="date" name="entryDate" id="entryDate" required
                                   value="<?php echo date('Y-m-d'); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="storeId" class="block text-sm font-medium text-gray-700 mb-1">Bodega</label>
                            <select name="storeId" id="storeId" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Sin bodega específica</option>
                                <?php foreach($stores as $store): ?>
                                <option value="<?php echo $store->idStore; ?>"><?php echo $store->name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                            <input type="text" name="amount" id="amount" required
                                   placeholder="0.00"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   onkeyup="formatCurrency(this)">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
                        <textarea name="description" id="description" rows="2" required
                                  placeholder="Descripción del asiento contable..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>

                <!-- Debit Account -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                        <h3 class="text-lg font-semibold text-gray-800">Cuenta Débito</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="debitAccount" class="block text-sm font-medium text-gray-700 mb-1">Subcuenta *</label>
                            <select name="debitAccount" id="debitAccount" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    onchange="loadAuxAccounts('debit', this.value)">
                                <option value="">Seleccione una cuenta</option>
                                <?php
                                $currentClass = '';
                                foreach($subaccounts as $acc):
                                    if($currentClass != $acc->className):
                                        if($currentClass != '') echo '</optgroup>';
                                        $currentClass = $acc->className;
                                        echo '<optgroup label="' . $acc->className . '">';
                                    endif;
                                ?>
                                <option value="<?php echo $acc->id; ?>" data-code="<?php echo $acc->accountID; ?>">
                                    <?php echo $acc->accountID . ' - ' . $acc->accountName; ?>
                                </option>
                                <?php endforeach; ?>
                                <?php if($currentClass != '') echo '</optgroup>'; ?>
                            </select>
                        </div>
                        <div>
                            <label for="debitAuxAccount" class="block text-sm font-medium text-gray-700 mb-1">Auxiliar (opcional)</label>
                            <select name="debitAuxAccount" id="debitAuxAccount"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Sin auxiliar</option>
                                <?php foreach($auxaccounts as $aux): ?>
                                <option value="<?php echo $aux->id; ?>" data-subaccount="<?php echo $aux->accountSubaccount; ?>">
                                    <?php echo $aux->accountName; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Credit Account -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                        <h3 class="text-lg font-semibold text-gray-800">Cuenta Crédito</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="creditAccount" class="block text-sm font-medium text-gray-700 mb-1">Subcuenta *</label>
                            <select name="creditAccount" id="creditAccount" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    onchange="loadAuxAccounts('credit', this.value)">
                                <option value="">Seleccione una cuenta</option>
                                <?php
                                $currentClass = '';
                                foreach($subaccounts as $acc):
                                    if($currentClass != $acc->className):
                                        if($currentClass != '') echo '</optgroup>';
                                        $currentClass = $acc->className;
                                        echo '<optgroup label="' . $acc->className . '">';
                                    endif;
                                ?>
                                <option value="<?php echo $acc->id; ?>" data-code="<?php echo $acc->accountID; ?>">
                                    <?php echo $acc->accountID . ' - ' . $acc->accountName; ?>
                                </option>
                                <?php endforeach; ?>
                                <?php if($currentClass != '') echo '</optgroup>'; ?>
                            </select>
                        </div>
                        <div>
                            <label for="creditAuxAccount" class="block text-sm font-medium text-gray-700 mb-1">Auxiliar (opcional)</label>
                            <select name="creditAuxAccount" id="creditAuxAccount"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Sin auxiliar</option>
                                <?php foreach($auxaccounts as $aux): ?>
                                <option value="<?php echo $aux->id; ?>" data-subaccount="<?php echo $aux->accountSubaccount; ?>">
                                    <?php echo $aux->accountName; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6" id="previewSection" style="display: none;">
                    <h3 class="text-lg font-semibold text-blue-800 mb-4">Vista Previa del Asiento</h3>
                    <table class="w-full">
                        <thead>
                            <tr class="text-xs font-semibold text-gray-500 uppercase border-b">
                                <th class="px-4 py-2 text-left">Cuenta</th>
                                <th class="px-4 py-2 text-right">Débito</th>
                                <th class="px-4 py-2 text-right">Crédito</th>
                            </tr>
                        </thead>
                        <tbody id="previewBody">
                        </tbody>
                        <tfoot class="bg-blue-100">
                            <tr class="font-bold">
                                <td class="px-4 py-2 text-right">TOTALES:</td>
                                <td class="px-4 py-2 text-right text-green-600" id="totalDebit">$ 0.00</td>
                                <td class="px-4 py-2 text-right text-red-600" id="totalCredit">$ 0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Warning -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-yellow-800">Importante</h4>
                            <p class="text-sm text-yellow-700 mt-1">
                                Los asientos manuales afectan directamente los saldos de las cuentas.
                                Verifique la información antes de guardar.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <a href="<?php echo base_url(); ?>sisvent/accounting/entries"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onclick="return validateForm()">
                        Guardar Asiento
                    </button>
                </div>
            </form>
    	 		</div>
        </main>
      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
        function formatCurrency(input) {
            let value = input.value.replace(/[^\d.]/g, '');
            let parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            if (parts[1] && parts[1].length > 2) {
                value = parts[0] + '.' + parts[1].substring(0, 2);
            }
            input.value = value;
            updatePreview();
        }

        function updatePreview() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const debitSelect = document.getElementById('debitAccount');
            const creditSelect = document.getElementById('creditAccount');
            const debitText = debitSelect.options[debitSelect.selectedIndex]?.text || '';
            const creditText = creditSelect.options[creditSelect.selectedIndex]?.text || '';

            if (amount > 0 && debitText && creditText && debitSelect.value && creditSelect.value) {
                document.getElementById('previewSection').style.display = 'block';
                document.getElementById('previewBody').innerHTML = `
                    <tr>
                        <td class="px-4 py-2">${debitText}</td>
                        <td class="px-4 py-2 text-right text-green-600">$ ${amount.toFixed(2)}</td>
                        <td class="px-4 py-2 text-right text-gray-400">-</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2">${creditText}</td>
                        <td class="px-4 py-2 text-right text-gray-400">-</td>
                        <td class="px-4 py-2 text-right text-red-600">$ ${amount.toFixed(2)}</td>
                    </tr>
                `;
                document.getElementById('totalDebit').textContent = '$ ' + amount.toFixed(2);
                document.getElementById('totalCredit').textContent = '$ ' + amount.toFixed(2);
            } else {
                document.getElementById('previewSection').style.display = 'none';
            }
        }

        function loadAuxAccounts(type, subaccountId) {
            updatePreview();
            // Filter auxiliaries based on selected subaccount
            const auxSelect = document.getElementById(type + 'AuxAccount');
            const options = auxSelect.querySelectorAll('option');
            options.forEach(opt => {
                if (opt.value === '' || opt.dataset.subaccount == subaccountId) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                }
            });
            auxSelect.value = '';
        }

        function validateForm() {
            const debitAccount = document.getElementById('debitAccount').value;
            const creditAccount = document.getElementById('creditAccount').value;
            const amount = parseFloat(document.getElementById('amount').value) || 0;

            if (debitAccount === creditAccount) {
                alert('Las cuentas de débito y crédito no pueden ser iguales.');
                return false;
            }

            if (amount <= 0) {
                alert('El monto debe ser mayor a cero.');
                return false;
            }

            return confirm('¿Está seguro de crear este asiento contable?');
        }

        // Initialize preview on page load
        document.getElementById('debitAccount').addEventListener('change', updatePreview);
        document.getElementById('creditAccount').addEventListener('change', updatePreview);
        document.getElementById('amount').addEventListener('input', updatePreview);
    </script>
  </body>
</html>
