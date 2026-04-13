<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];

$months = array(
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
    4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
    10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
);
$periodLabel = $periodType === 'yearly' ? "Año $year" : $months[$month] . ' ' . $year;
?>
<!DOCTYPE html>
<html lang="en">
    <title>Vista Previa - Cierre Contable <?php echo $periodLabel; ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => 'sisvent/accounting/cierre/list.php','role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto pb-8">
    	 		<div class="px-6 mx-auto grid max-w-4xl">
            <!-- Header -->
            <div class="mt-4 mb-6">
                <a href="<?php echo base_url(); ?>sisvent/accounting/cierre/nuevo" class="text-blue-600 hover:text-blue-800 text-sm flex items-center mb-2">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Volver
                </a>
                <h2 class="text-2xl font-bold text-gray-800">Vista Previa de Cierre: <?php echo $periodLabel; ?></h2>
                <p class="text-gray-600 mt-1">
                    Período: <?php echo date('d/m/Y', strtotime($startDate)); ?> - <?php echo date('d/m/Y', strtotime($endDate)); ?>
                    <?php if($storeId): ?>
                    | Bodega: <?php
                        foreach($stores as $s) {
                            if($s->idStore == $storeId) echo $s->name;
                        }
                    ?>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-4">
                    <p class="text-sm font-medium text-green-100">Ingresos</p>
                    <p class="text-xl font-bold text-white">$ <?php echo number_format($income, 2); ?></p>
                </div>
                <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-4">
                    <p class="text-sm font-medium text-yellow-100">Costos</p>
                    <p class="text-xl font-bold text-white">$ <?php echo number_format($costs, 2); ?></p>
                </div>
                <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-lg shadow-lg p-4">
                    <p class="text-sm font-medium text-red-100">Gastos</p>
                    <p class="text-xl font-bold text-white">$ <?php echo number_format($expenses, 2); ?></p>
                </div>
                <div class="<?php echo $netIncome >= 0 ? 'bg-gradient-to-r from-blue-500 to-blue-600' : 'bg-gradient-to-r from-orange-500 to-orange-600'; ?> rounded-lg shadow-lg p-4">
                    <p class="text-sm font-medium text-white text-opacity-80"><?php echo $netIncome >= 0 ? 'Utilidad Neta' : 'Pérdida Neta'; ?></p>
                    <p class="text-xl font-bold text-white">$ <?php echo number_format(abs($netIncome), 2); ?></p>
                </div>
            </div>

            <!-- Income Details -->
            <?php if(!empty($incomeDetails)): ?>
            <div class="bg-white rounded-lg shadow-md mb-6">
                <div class="px-4 py-3 bg-green-50 border-b">
                    <h3 class="font-semibold text-green-700">Detalle de Ingresos (Clase 4)</h3>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="text-xs font-semibold text-gray-500 uppercase border-b bg-gray-50">
                            <th class="px-4 py-2 text-left">Código</th>
                            <th class="px-4 py-2 text-left">Cuenta</th>
                            <th class="px-4 py-2 text-right">Débitos</th>
                            <th class="px-4 py-2 text-right">Créditos</th>
                            <th class="px-4 py-2 text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach($incomeDetails as $acc): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-600"><?php echo $acc->accountID; ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo $acc->accountName; ?></td>
                            <td class="px-4 py-2 text-sm text-right">$ <?php echo number_format($acc->totalDebit, 2); ?></td>
                            <td class="px-4 py-2 text-sm text-right">$ <?php echo number_format($acc->totalCredit, 2); ?></td>
                            <td class="px-4 py-2 text-sm text-right font-medium text-green-600">$ <?php echo number_format($acc->balance, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-green-50">
                        <tr class="font-semibold">
                            <td colspan="4" class="px-4 py-2 text-right">Total Ingresos:</td>
                            <td class="px-4 py-2 text-right text-green-600">$ <?php echo number_format($income, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>

            <!-- Cost Details -->
            <?php if(!empty($costDetails)): ?>
            <div class="bg-white rounded-lg shadow-md mb-6">
                <div class="px-4 py-3 bg-yellow-50 border-b">
                    <h3 class="font-semibold text-yellow-700">Detalle de Costos (Clase 6)</h3>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="text-xs font-semibold text-gray-500 uppercase border-b bg-gray-50">
                            <th class="px-4 py-2 text-left">Código</th>
                            <th class="px-4 py-2 text-left">Cuenta</th>
                            <th class="px-4 py-2 text-right">Débitos</th>
                            <th class="px-4 py-2 text-right">Créditos</th>
                            <th class="px-4 py-2 text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach($costDetails as $acc): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-600"><?php echo $acc->accountID; ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo $acc->accountName; ?></td>
                            <td class="px-4 py-2 text-sm text-right">$ <?php echo number_format($acc->totalDebit, 2); ?></td>
                            <td class="px-4 py-2 text-sm text-right">$ <?php echo number_format($acc->totalCredit, 2); ?></td>
                            <td class="px-4 py-2 text-sm text-right font-medium text-yellow-600">$ <?php echo number_format($acc->balance, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-yellow-50">
                        <tr class="font-semibold">
                            <td colspan="4" class="px-4 py-2 text-right">Total Costos:</td>
                            <td class="px-4 py-2 text-right text-yellow-600">$ <?php echo number_format($costs, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>

            <!-- Expense Details -->
            <?php if(!empty($expenseDetails)): ?>
            <div class="bg-white rounded-lg shadow-md mb-6">
                <div class="px-4 py-3 bg-red-50 border-b">
                    <h3 class="font-semibold text-red-700">Detalle de Gastos (Clase 5)</h3>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="text-xs font-semibold text-gray-500 uppercase border-b bg-gray-50">
                            <th class="px-4 py-2 text-left">Código</th>
                            <th class="px-4 py-2 text-left">Cuenta</th>
                            <th class="px-4 py-2 text-right">Débitos</th>
                            <th class="px-4 py-2 text-right">Créditos</th>
                            <th class="px-4 py-2 text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach($expenseDetails as $acc): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-600"><?php echo $acc->accountID; ?></td>
                            <td class="px-4 py-2 text-sm"><?php echo $acc->accountName; ?></td>
                            <td class="px-4 py-2 text-sm text-right">$ <?php echo number_format($acc->totalDebit, 2); ?></td>
                            <td class="px-4 py-2 text-sm text-right">$ <?php echo number_format($acc->totalCredit, 2); ?></td>
                            <td class="px-4 py-2 text-sm text-right font-medium text-red-600">$ <?php echo number_format($acc->balance, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-red-50">
                        <tr class="font-semibold">
                            <td colspan="4" class="px-4 py-2 text-right">Total Gastos:</td>
                            <td class="px-4 py-2 text-right text-red-600">$ <?php echo number_format($expenses, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>

            <!-- Closing Entry Preview -->
            <div class="bg-white rounded-lg shadow-md mb-6">
                <div class="px-4 py-3 bg-blue-50 border-b">
                    <h3 class="font-semibold text-blue-700">Asiento de Cierre a Generar</h3>
                </div>
                <div class="p-4">
                    <?php if(abs($netIncome) < 0.01): ?>
                    <p class="text-gray-600 text-center py-4">
                        No se generará asiento de cierre porque el resultado neto es cero.
                    </p>
                    <?php else: ?>
                    <table class="w-full">
                        <thead>
                            <tr class="text-xs font-semibold text-gray-500 uppercase border-b">
                                <th class="px-4 py-2 text-left">Cuenta</th>
                                <th class="px-4 py-2 text-right">Débito</th>
                                <th class="px-4 py-2 text-right">Crédito</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php if($netIncome >= 0): ?>
                            <!-- Utilidad -->
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-xs text-gray-500">360505</p>
                                    <p class="font-medium"><?php echo $utilityAccount ? $utilityAccount->accountName : 'Utilidad del Ejercicio'; ?></p>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-green-600">$ <?php echo number_format($netIncome, 2); ?></td>
                                <td class="px-4 py-3 text-right text-gray-400">-</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-xs text-gray-500">360505</p>
                                    <p class="font-medium"><?php echo $utilityAccount ? $utilityAccount->accountName : 'Utilidad del Ejercicio'; ?></p>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-400">-</td>
                                <td class="px-4 py-3 text-right font-semibold text-red-600">$ <?php echo number_format($netIncome, 2); ?></td>
                            </tr>
                            <?php else: ?>
                            <!-- Pérdida -->
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-xs text-gray-500">360510</p>
                                    <p class="font-medium"><?php echo $lossAccount ? $lossAccount->accountName : 'Pérdida del Ejercicio'; ?></p>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-green-600">$ <?php echo number_format(abs($netIncome), 2); ?></td>
                                <td class="px-4 py-3 text-right text-gray-400">-</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-xs text-gray-500">360510</p>
                                    <p class="font-medium"><?php echo $lossAccount ? $lossAccount->accountName : 'Pérdida del Ejercicio'; ?></p>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-400">-</td>
                                <td class="px-4 py-3 text-right font-semibold text-red-600">$ <?php echo number_format(abs($netIncome), 2); ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr class="font-bold">
                                <td class="px-4 py-3 text-right">TOTALES:</td>
                                <td class="px-4 py-3 text-right text-green-600">$ <?php echo number_format(abs($netIncome), 2); ?></td>
                                <td class="px-4 py-3 text-right text-red-600">$ <?php echo number_format(abs($netIncome), 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Confirmation Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <form method="post" action="<?php echo base_url(); ?>sisvent/accounting/cierre/ejecutar">
                    <input type="hidden" name="year" value="<?php echo $year; ?>">
                    <input type="hidden" name="month" value="<?php echo $month; ?>">
                    <input type="hidden" name="store" value="<?php echo $storeId; ?>">
                    <input type="hidden" name="periodType" value="<?php echo $periodType; ?>">

                    <div class="mb-4">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notas del Cierre (opcional)</label>
                        <textarea name="notes" id="notes" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Observaciones adicionales sobre este cierre..."></textarea>
                    </div>

                    <!-- Warning -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <div class="flex">
                            <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div>
                                <h4 class="text-sm font-medium text-yellow-800">Confirmar Cierre</h4>
                                <p class="text-sm text-yellow-700 mt-1">
                                    Al ejecutar el cierre, se generará el asiento contable y el período quedará marcado como cerrado.
                                    Solo un administrador podrá reabrir el período si es necesario.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="<?php echo base_url(); ?>sisvent/accounting/cierre/nuevo"
                           class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                onclick="return confirm('¿Está seguro de ejecutar el cierre contable para <?php echo $periodLabel; ?>?');">
                            Ejecutar Cierre
                        </button>
                    </div>
                </form>
            </div>
    	 		</div>
        </main>
      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>
