<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Balance de Comprobacion</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">
                    <!-- ENCABEZADO -->
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-600">Balance de Comprobacion</h2>
                            <p class="text-xs text-gray-400">Del <?php echo date('d/m/Y', strtotime($filter_from)); ?> al <?php echo date('d/m/Y', strtotime($filter_to)); ?></p>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?php echo base_url(); ?>sisvent/accounting/reports" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border rounded-lg hover:bg-gray-50">
                                Volver
                            </a>
                            <button onclick="window.print()" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border rounded-lg hover:bg-gray-50">
                                Imprimir
                            </button>
                        </div>
                    </div>

                    <!-- FILTROS -->
                    <div class="bg-white rounded-lg shadow-md p-4 mb-4 print:hidden">
                        <form method="GET" action="<?php echo base_url(); ?>sisvent/accounting/reports/comprobacion" class="flex flex-wrap gap-4 items-end">
                            <div class="flex-1 min-w-40">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Desde</label>
                                <input type="date" name="from" value="<?php echo $filter_from; ?>" class="form-input w-full">
                            </div>
                            <div class="flex-1 min-w-40">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Hasta</label>
                                <input type="date" name="to" value="<?php echo $filter_to; ?>" class="form-input w-full">
                            </div>
                            <div class="flex-1 min-w-48">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Bodega</label>
                                <select name="store" class="form-input form-select w-full">
                                    <option value="">Todas las bodegas</option>
                                    <?php foreach($stores as $store): ?>
                                        <option value="<?php echo $store->idStore; ?>" <?php echo $filter_store == $store->idStore ? 'selected' : ''; ?>><?php echo $store->name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-lg hover:bg-mam-blue-dark-hover">
                                    Generar
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- RESUMEN -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 rounded-lg shadow-sm p-4 border-t-4 border-blue-500">
                            <p class="text-xs text-blue-600 uppercase font-semibold">Total Debitos</p>
                            <p class="text-xl font-bold text-blue-700 mt-1">$<?php echo number_format($totalDebits, 2); ?></p>
                        </div>
                        <div class="bg-green-50 rounded-lg shadow-sm p-4 border-t-4 border-green-500">
                            <p class="text-xs text-green-600 uppercase font-semibold">Total Creditos</p>
                            <p class="text-xl font-bold text-green-700 mt-1">$<?php echo number_format($totalCredits, 2); ?></p>
                        </div>
                        <div class="bg-purple-50 rounded-lg shadow-sm p-4 border-t-4 border-purple-500">
                            <p class="text-xs text-purple-600 uppercase font-semibold">Saldo Deudor</p>
                            <p class="text-xl font-bold text-purple-700 mt-1">$<?php echo number_format($totalDebitBalance, 2); ?></p>
                        </div>
                        <div class="bg-orange-50 rounded-lg shadow-sm p-4 border-t-4 border-orange-500">
                            <p class="text-xs text-orange-600 uppercase font-semibold">Saldo Acreedor</p>
                            <p class="text-xl font-bold text-orange-700 mt-1">$<?php echo number_format($totalCreditBalance, 2); ?></p>
                        </div>
                    </div>

                    <!-- VERIFICACIÓN -->
                    <?php
                        $diffMovs = abs($totalDebits - $totalCredits);
                        $diffBalance = abs($totalDebitBalance - $totalCreditBalance);
                        $isBalanced = $diffMovs < 0.01 && $diffBalance < 0.01;
                    ?>
                    <div class="mb-4 p-3 rounded-lg <?php echo $isBalanced ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                        <?php if($isBalanced): ?>
                            <span class="font-semibold">Balance Cuadrado</span> - Los debitos y creditos coinciden.
                        <?php else: ?>
                            <span class="font-semibold">Diferencia detectada</span> -
                            Movimientos: $<?php echo number_format($diffMovs, 2); ?> |
                            Saldos: $<?php echo number_format($diffBalance, 2); ?>
                        <?php endif; ?>
                    </div>

                    <!-- TABLA -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs bg-white mb-6">
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Codigo</th>
                                        <th class="px-4 py-3">Cuenta</th>
                                        <th class="px-4 py-3 text-right">Debitos</th>
                                        <th class="px-4 py-3 text-right">Creditos</th>
                                        <th class="px-4 py-3 text-right">Saldo Deudor</th>
                                        <th class="px-4 py-3 text-right">Saldo Acreedor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php if(!empty($accounts)): ?>
                                        <?php foreach($accounts as $acc): ?>
                                            <tr class="text-gray-700 text-sm hover:bg-gray-50">
                                                <td class="px-4 py-3 font-mono text-xs"><?php echo $acc->accountID; ?></td>
                                                <td class="px-4 py-3"><?php echo $acc->accountName; ?></td>
                                                <td class="px-4 py-3 text-right text-blue-600">$<?php echo number_format($acc->totalDebit, 2); ?></td>
                                                <td class="px-4 py-3 text-right text-green-600">$<?php echo number_format($acc->totalCredit, 2); ?></td>
                                                <td class="px-4 py-3 text-right font-medium <?php echo $acc->debitBalance > 0 ? 'text-purple-600' : ''; ?>">
                                                    <?php echo $acc->debitBalance > 0 ? '$' . number_format($acc->debitBalance, 2) : '-'; ?>
                                                </td>
                                                <td class="px-4 py-3 text-right font-medium <?php echo $acc->creditBalance > 0 ? 'text-orange-600' : ''; ?>">
                                                    <?php echo $acc->creditBalance > 0 ? '$' . number_format($acc->creditBalance, 2) : '-'; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                                No hay movimientos en el periodo seleccionado
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-100 font-semibold text-gray-800">
                                        <td colspan="2" class="px-4 py-3">TOTALES</td>
                                        <td class="px-4 py-3 text-right text-blue-700">$<?php echo number_format($totalDebits, 2); ?></td>
                                        <td class="px-4 py-3 text-right text-green-700">$<?php echo number_format($totalCredits, 2); ?></td>
                                        <td class="px-4 py-3 text-right text-purple-700">$<?php echo number_format($totalDebitBalance, 2); ?></td>
                                        <td class="px-4 py-3 text-right text-orange-700">$<?php echo number_format($totalCreditBalance, 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
