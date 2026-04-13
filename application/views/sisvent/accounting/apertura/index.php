<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Apertura de Balance</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto pb-8">
    	 		<div class="px-6 mx-auto grid">
            <!-- Header -->
            <div class="mt-2 mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Apertura de Balance</h2>
                <p class="text-gray-600 mt-1">Registrar saldos iniciales para cada subcuenta</p>
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

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-xs p-4 mb-4">
                <form method="get" action="<?php echo base_url(); ?>sisvent/accounting/apertura" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bodega</label>
                        <select name="store" onchange="this.form.submit()" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todas las bodegas</option>
                            <?php foreach($stores as $store): ?>
                            <option value="<?php echo $store->idStore; ?>" <?php echo ($filter_store == $store->idStore) ? 'selected' : ''; ?>>
                                <?php echo $store->name; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Existing Apertura Entries -->
            <?php if(!empty($aperturaEntries)): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <h3 class="text-sm font-semibold text-blue-800 mb-2">Asientos de apertura existentes: <?php echo count($aperturaEntries); ?></h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs font-semibold text-gray-500 uppercase border-b">
                                <th class="px-3 py-2 text-left">Fecha</th>
                                <th class="px-3 py-2 text-left">Cuenta Debito</th>
                                <th class="px-3 py-2 text-left">Cuenta Credito</th>
                                <th class="px-3 py-2 text-right">Debito</th>
                                <th class="px-3 py-2 text-right">Credito</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-blue-100">
                            <?php foreach($aperturaEntries as $entry): ?>
                            <tr>
                                <td class="px-3 py-1"><?php echo $entry->entryDate; ?></td>
                                <td class="px-3 py-1"><?php echo $entry->debitaccCode . ' - ' . $entry->debitaccName; ?></td>
                                <td class="px-3 py-1"><?php echo $entry->creditaccCode . ' - ' . $entry->creditaccName; ?></td>
                                <td class="px-3 py-1 text-right">$ <?php echo number_format($entry->entryDebitBalance, 2); ?></td>
                                <td class="px-3 py-1 text-right">$ <?php echo number_format($entry->entryCreditBalance, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Opening Balance Form -->
            <form method="post" action="<?php echo base_url(); ?>sisvent/accounting/apertura/save" id="aperturaForm">
                <div class="bg-white rounded-lg shadow-md p-6 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Apertura *</label>
                            <input type="date" name="entryDate" required value="<?php echo date('Y-m-d'); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bodega *</label>
                            <select name="storeId" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccione bodega</option>
                                <?php foreach($stores as $store): ?>
                                <option value="<?php echo $store->idStore; ?>" <?php echo ($filter_store == $store->idStore) ? 'selected' : ''; ?>>
                                    <?php echo $store->name; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <?php foreach($grouped as $className => $group): ?>
                <div class="bg-white rounded-lg shadow-xs mb-4">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <?php echo $group['classID'] . ' - ' . $className; ?>
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-no-wrap">
                            <thead>
                                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                    <th class="px-4 py-3">Codigo</th>
                                    <th class="px-4 py-3">Subcuenta</th>
                                    <th class="px-4 py-3">Naturaleza</th>
                                    <th class="px-4 py-3 text-center">Tipo</th>
                                    <th class="px-4 py-3 text-right">Monto Inicial</th>
                                    <th class="px-4 py-3">Saldo Actual</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y">
                                <?php foreach($group['subaccounts'] as $sub): ?>
                                <tr class="text-gray-700 hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm font-mono">
                                        <?php echo $sub->accountID; ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        <?php echo $sub->accountName; ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo ($sub->accountSide == 1) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo isset($sub->sideName) ? $sub->sideName : (($sub->accountSide == 1) ? 'Debito' : 'Credito'); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <select name="types[<?php echo $sub->id; ?>]" class="text-sm border border-gray-300 rounded px-2 py-1">
                                            <option value="debit" <?php echo ($sub->accountSide == 1) ? 'selected' : ''; ?>>Debito</option>
                                            <option value="credit" <?php echo ($sub->accountSide == 2) ? 'selected' : ''; ?>>Credito</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <input type="text" name="accounts[<?php echo $sub->id; ?>]"
                                               placeholder="0.00"
                                               class="w-32 px-2 py-1 text-sm text-right border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                                               onkeyup="formatAmount(this)">
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        <?php
                                        $currentBal = isset($sub->accountBalance) ? $sub->accountBalance : 0;
                                        $balClass = $currentBal >= 0 ? 'text-green-600' : 'text-red-600';
                                        ?>
                                        <span class="font-semibold <?php echo $balClass; ?>">
                                            $ <?php echo number_format($currentBal, 2); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Warning -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-yellow-800">Importante</h4>
                            <p class="text-sm text-yellow-700 mt-1">
                                Solo ingrese montos en las cuentas que tengan saldo inicial.
                                Los asientos de apertura se crearan automaticamente con la contra-cuenta de patrimonio.
                                Deje en blanco las cuentas sin saldo inicial.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3 mb-8">
                    <a href="<?php echo base_url(); ?>sisvent/accounting/entries"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-6 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onclick="return confirm('Se crearan los asientos de apertura. Esta seguro?')">
                        Guardar Saldos de Apertura
                    </button>
                </div>
            </form>

    	 		</div>
        </main>
      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
        function formatAmount(input) {
            var value = input.value.replace(/[^\d.]/g, '');
            var parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            if (parts[1] && parts[1].length > 2) {
                value = parts[0] + '.' + parts[1].substring(0, 2);
            }
            input.value = value;
        }
    </script>
  </body>
</html>
