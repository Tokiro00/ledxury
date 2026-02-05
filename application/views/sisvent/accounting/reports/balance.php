<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Balance General</title>
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
                        <div>
                            <h2 class="text-lg font-semibold text-gray-600">Balance General</h2>
                            <p class="text-xs text-gray-400">Al <?php echo date('d/m/Y', strtotime($reportDate)); ?></p>
                        </div>
                        <div class="flex gap-2 print:hidden">
                            <a href="<?php echo base_url(); ?>sisvent/accounting/reports" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border rounded-lg hover:bg-gray-50">
                                Volver
                            </a>
                            <button onclick="window.print()" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border rounded-lg hover:bg-gray-50">
                                Imprimir
                            </button>
                            <button id="exportBalance" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                Excel
                            </button>
                        </div>
                    </div>

                    <!-- FILTROS -->
                    <div class="bg-white rounded-lg shadow-md p-4 mb-4 print:hidden">
                        <form method="GET" action="<?php echo base_url(); ?>sisvent/accounting/reports/balance" class="flex flex-wrap gap-4 items-end">
                            <div class="flex-1 min-w-48">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Fecha de corte</label>
                                <input type="date" name="to" value="<?php echo $filter_to; ?>" class="form-input w-full">
                            </div>
                            <div class="flex-1 min-w-48">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Bodega</label>
                                <select name="store" class="form-input form-select w-full">
                                    <option value="">Todas las bodegas</option>
                                    <?php if(isset($stores)): foreach($stores as $store): ?>
                                        <option value="<?php echo $store->idStore; ?>" <?php echo (isset($filter_store) && $filter_store == $store->idStore) ? 'selected' : ''; ?>><?php echo $store->name; ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-lg hover:bg-mam-blue-dark-hover">
                                    Generar
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- RESUMEN GENERAL -->
                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 rounded-lg shadow-sm p-4 border-t-4 border-blue-500">
                            <p class="text-xs text-blue-600 uppercase font-semibold">Total Activos</p>
                            <p class="text-xl font-bold text-blue-700 mt-1">$<?php echo number_format($totalActivos, 2); ?></p>
                        </div>
                        <div class="bg-red-50 rounded-lg shadow-sm p-4 border-t-4 border-red-500">
                            <p class="text-xs text-red-600 uppercase font-semibold">Total Pasivos</p>
                            <p class="text-xl font-bold text-red-700 mt-1">$<?php echo number_format($totalPasivos, 2); ?></p>
                        </div>
                        <div class="bg-green-50 rounded-lg shadow-sm p-4 border-t-4 border-green-500">
                            <p class="text-xs text-green-600 uppercase font-semibold">Total Patrimonio</p>
                            <p class="text-xl font-bold text-green-700 mt-1">$<?php echo number_format($totalPatrimonio, 2); ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4 border-t-4 <?php echo abs($balanceCheck) < 0.01 ? 'border-green-500' : 'border-yellow-500'; ?>">
                            <p class="text-xs text-gray-500 uppercase font-semibold">Verificación</p>
                            <?php if (abs($balanceCheck) < 0.01): ?>
                                <p class="text-sm font-semibold text-green-600 mt-1">Cuadrado</p>
                            <?php else: ?>
                                <p class="text-sm font-semibold text-yellow-600 mt-1">Diferencia: $<?php echo number_format($balanceCheck, 2); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ECUACIÓN CONTABLE -->
                    <div class="bg-gray-100 rounded-lg p-3 mb-6 text-center text-sm">
                        <span class="text-blue-700 font-semibold">Activos ($<?php echo number_format($totalActivos, 2); ?>)</span>
                        <span class="text-gray-500 mx-2">=</span>
                        <span class="text-red-700 font-semibold">Pasivos ($<?php echo number_format($totalPasivos, 2); ?>)</span>
                        <span class="text-gray-500 mx-2">+</span>
                        <span class="text-green-700 font-semibold">Patrimonio ($<?php echo number_format($totalPatrimonio, 2); ?>)</span>
                    </div>

                    <!-- DETALLE POR CLASE -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

                        <!-- ACTIVOS -->
                        <?php if (isset($groupedAccounts[1])): ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <div class="px-4 py-3 bg-blue-500 text-white">
                                <h3 class="font-semibold">1. ACTIVOS</h3>
                            </div>
                            <table id="tableActivos" class="w-full table2excel" data-tableName="Activos">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-2">Código</th>
                                        <th class="px-4 py-2">Cuenta</th>
                                        <th class="px-4 py-2 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php foreach ($groupedAccounts[1]['accounts'] as $acc): ?>
                                    <tr class="text-gray-700 text-sm">
                                        <td class="px-4 py-2 font-mono text-xs"><?php echo $acc->accountID; ?></td>
                                        <td class="px-4 py-2"><?php echo $acc->accountName; ?></td>
                                        <td class="px-4 py-2 text-right font-medium">$<?php echo number_format($acc->calculatedBalance, 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-blue-50 font-semibold text-blue-800">
                                        <td colspan="2" class="px-4 py-2">Total Activos</td>
                                        <td class="px-4 py-2 text-right">$<?php echo number_format($groupedAccounts[1]['total'], 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php endif; ?>

                        <!-- PASIVOS Y PATRIMONIO -->
                        <div class="space-y-6">
                            <!-- PASIVOS -->
                            <?php if (isset($groupedAccounts[2])): ?>
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                                <div class="px-4 py-3 bg-red-500 text-white">
                                    <h3 class="font-semibold">2. PASIVOS</h3>
                                </div>
                                <table id="tablePasivos" class="w-full table2excel" data-tableName="Pasivos">
                                    <thead>
                                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                            <th class="px-4 py-2">Código</th>
                                            <th class="px-4 py-2">Cuenta</th>
                                            <th class="px-4 py-2 text-right">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <?php foreach ($groupedAccounts[2]['accounts'] as $acc): ?>
                                        <tr class="text-gray-700 text-sm">
                                            <td class="px-4 py-2 font-mono text-xs"><?php echo $acc->accountID; ?></td>
                                            <td class="px-4 py-2"><?php echo $acc->accountName; ?></td>
                                            <td class="px-4 py-2 text-right font-medium">$<?php echo number_format($acc->calculatedBalance, 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-red-50 font-semibold text-red-800">
                                            <td colspan="2" class="px-4 py-2">Total Pasivos</td>
                                            <td class="px-4 py-2 text-right">$<?php echo number_format($groupedAccounts[2]['total'], 2); ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <?php endif; ?>

                            <!-- PATRIMONIO -->
                            <?php if (isset($groupedAccounts[3])): ?>
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                                <div class="px-4 py-3 bg-green-500 text-white">
                                    <h3 class="font-semibold">3. PATRIMONIO</h3>
                                </div>
                                <table id="tablePatrimonio" class="w-full table2excel" data-tableName="Patrimonio">
                                    <thead>
                                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                            <th class="px-4 py-2">Código</th>
                                            <th class="px-4 py-2">Cuenta</th>
                                            <th class="px-4 py-2 text-right">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <?php foreach ($groupedAccounts[3]['accounts'] as $acc): ?>
                                        <tr class="text-gray-700 text-sm">
                                            <td class="px-4 py-2 font-mono text-xs"><?php echo $acc->accountID; ?></td>
                                            <td class="px-4 py-2"><?php echo $acc->accountName; ?></td>
                                            <td class="px-4 py-2 text-right font-medium">$<?php echo number_format($acc->calculatedBalance, 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-green-50 font-semibold text-green-800">
                                            <td colspan="2" class="px-4 py-2">Total Patrimonio</td>
                                            <td class="px-4 py-2 text-right">$<?php echo number_format($groupedAccounts[3]['total'], 2); ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <?php endif; ?>

                            <!-- TOTAL PASIVO + PATRIMONIO -->
                            <div class="bg-gray-800 text-white rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold">TOTAL PASIVO + PATRIMONIO</span>
                                    <span class="text-xl font-bold">$<?php echo number_format($totalPasivos + $totalPatrimonio, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MENSAJE SI NO HAY DATOS -->
                    <?php if (empty($groupedAccounts)): ?>
                    <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                        <p class="text-gray-500">No hay cuentas registradas para el Balance General</p>
                    </div>
                    <?php endif; ?>

                    <!-- TABLA OCULTA PARA EXPORTAR -->
                    <table id="tableBalanceExport" class="hidden table2excel" data-tableName="BalanceGeneral">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cuenta</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($groupedAccounts[1])): ?>
                            <tr><td colspan="3"><strong>1. ACTIVOS</strong></td></tr>
                            <?php foreach ($groupedAccounts[1]['accounts'] as $acc): ?>
                            <tr>
                                <td><?php echo $acc->accountID; ?></td>
                                <td><?php echo $acc->accountName; ?></td>
                                <td><?php echo number_format($acc->calculatedBalance, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr><td></td><td><strong>Total Activos</strong></td><td><strong><?php echo number_format($groupedAccounts[1]['total'], 2); ?></strong></td></tr>
                            <?php endif; ?>

                            <?php if (isset($groupedAccounts[2])): ?>
                            <tr><td colspan="3"></td></tr>
                            <tr><td colspan="3"><strong>2. PASIVOS</strong></td></tr>
                            <?php foreach ($groupedAccounts[2]['accounts'] as $acc): ?>
                            <tr>
                                <td><?php echo $acc->accountID; ?></td>
                                <td><?php echo $acc->accountName; ?></td>
                                <td><?php echo number_format($acc->calculatedBalance, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr><td></td><td><strong>Total Pasivos</strong></td><td><strong><?php echo number_format($groupedAccounts[2]['total'], 2); ?></strong></td></tr>
                            <?php endif; ?>

                            <?php if (isset($groupedAccounts[3])): ?>
                            <tr><td colspan="3"></td></tr>
                            <tr><td colspan="3"><strong>3. PATRIMONIO</strong></td></tr>
                            <?php foreach ($groupedAccounts[3]['accounts'] as $acc): ?>
                            <tr>
                                <td><?php echo $acc->accountID; ?></td>
                                <td><?php echo $acc->accountName; ?></td>
                                <td><?php echo number_format($acc->calculatedBalance, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr><td></td><td><strong>Total Patrimonio</strong></td><td><strong><?php echo number_format($groupedAccounts[3]['total'], 2); ?></strong></td></tr>
                            <?php endif; ?>

                            <tr><td colspan="3"></td></tr>
                            <tr><td></td><td><strong>TOTAL PASIVO + PATRIMONIO</strong></td><td><strong><?php echo number_format($totalPasivos + $totalPatrimonio, 2); ?></strong></td></tr>
                        </tbody>
                    </table>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>
    <script>
        $(document).ready(function(){
            $(document).on("click","#exportBalance",function(){
                var table = document.getElementById('tableBalanceExport');
                var wb = XLSX.utils.table_to_book(table, {sheet: "BalanceGeneral"});
                var fileName = 'BalanceGeneral_<?php echo $filter_to; ?>.xlsx';
                XLSX.writeFile(wb, fileName);
            });
        });
    </script>
</body>
</html>
