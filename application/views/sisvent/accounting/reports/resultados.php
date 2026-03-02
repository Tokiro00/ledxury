<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Estado de Resultados</title>
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
                            <h2 class="text-lg font-semibold text-gray-600">Estado de Resultados</h2>
                            <p class="text-xs text-gray-400">Del <?php echo date('d/m/Y', strtotime($filter_from)); ?> al <?php echo date('d/m/Y', strtotime($filter_to)); ?></p>
                        </div>
                        <div class="flex gap-2 print:hidden">
                            <a href="<?php echo base_url(); ?>sisvent/accounting/reports" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border rounded-lg hover:bg-gray-50">
                                Volver
                            </a>
                            <button onclick="window.print()" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border rounded-lg hover:bg-gray-50">
                                Imprimir
                            </button>
                            <button id="exportResultados" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                Excel
                            </button>
                        </div>
                    </div>

                    <!-- FILTROS -->
                    <div class="bg-white rounded-lg shadow-md p-4 mb-4 print:hidden">
                        <form method="GET" action="<?php echo base_url(); ?>sisvent/accounting/reports/resultados" class="flex flex-wrap gap-4 items-end">
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
                                    <?php if(isset($stores)): foreach($stores as $store): ?>
                                        <option value="<?php echo $store->idStore; ?>" <?php echo (isset($filter_store) && $filter_store == $store->idStore) ? 'selected' : ''; ?>><?php echo $store->name; ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="flex-1 min-w-48">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Centro de Costo</label>
                                <select name="cost_center" class="form-input form-select w-full">
                                    <option value="">Todos</option>
                                    <?php if(isset($costcenters)): foreach($costcenters as $cc): ?>
                                        <option value="<?php echo $cc->id; ?>" <?php echo (isset($filter_cost_center) && $filter_cost_center == $cc->id) ? 'selected' : ''; ?>><?php echo $cc->code . ' - ' . $cc->name; ?></option>
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
                        <div class="bg-green-50 rounded-lg shadow-sm p-4 border-t-4 border-green-500">
                            <p class="text-xs text-green-600 uppercase font-semibold">Total Ingresos</p>
                            <p class="text-xl font-bold text-green-700 mt-1">$<?php echo number_format($totalIngresos, 2); ?></p>
                        </div>
                        <div class="bg-red-50 rounded-lg shadow-sm p-4 border-t-4 border-red-500">
                            <p class="text-xs text-red-600 uppercase font-semibold">Total Gastos</p>
                            <p class="text-xl font-bold text-red-700 mt-1">$<?php echo number_format($totalGastos, 2); ?></p>
                        </div>
                        <div class="bg-orange-50 rounded-lg shadow-sm p-4 border-t-4 border-orange-500">
                            <p class="text-xs text-orange-600 uppercase font-semibold">Total Costos</p>
                            <p class="text-xl font-bold text-orange-700 mt-1">$<?php echo number_format($totalCostos, 2); ?></p>
                        </div>
                        <div class="rounded-lg shadow-sm p-4 border-t-4 <?php echo $utilidadNeta >= 0 ? 'bg-blue-50 border-blue-500' : 'bg-red-100 border-red-600'; ?>">
                            <p class="text-xs <?php echo $utilidadNeta >= 0 ? 'text-blue-600' : 'text-red-600'; ?> uppercase font-semibold">
                                <?php echo $utilidadNeta >= 0 ? 'Utilidad Neta' : 'Pérdida Neta'; ?>
                            </p>
                            <p class="text-xl font-bold <?php echo $utilidadNeta >= 0 ? 'text-blue-700' : 'text-red-700'; ?> mt-1">
                                $<?php echo number_format(abs($utilidadNeta), 2); ?>
                            </p>
                        </div>
                    </div>

                    <!-- FÓRMULA -->
                    <div class="bg-gray-100 rounded-lg p-3 mb-6 text-center text-sm">
                        <span class="text-green-700 font-semibold">Ingresos ($<?php echo number_format($totalIngresos, 2); ?>)</span>
                        <span class="text-gray-500 mx-2">-</span>
                        <span class="text-red-700 font-semibold">Gastos ($<?php echo number_format($totalGastos, 2); ?>)</span>
                        <span class="text-gray-500 mx-2">-</span>
                        <span class="text-orange-700 font-semibold">Costos ($<?php echo number_format($totalCostos, 2); ?>)</span>
                        <span class="text-gray-500 mx-2">=</span>
                        <span class="<?php echo $utilidadNeta >= 0 ? 'text-blue-700' : 'text-red-700'; ?> font-semibold">
                            <?php echo $utilidadNeta >= 0 ? 'Utilidad' : 'Pérdida'; ?> ($<?php echo number_format(abs($utilidadNeta), 2); ?>)
                        </span>
                    </div>

                    <!-- DETALLE -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">

                        <!-- INGRESOS -->
                        <?php if (isset($groupedAccounts[4])): ?>
                        <div class="border-b">
                            <div class="px-4 py-3 bg-green-500 text-white">
                                <h3 class="font-semibold">4. INGRESOS</h3>
                            </div>
                            <table id="tableIngresos" class="w-full table2excel" data-tableName="Ingresos">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-2">Código</th>
                                        <th class="px-4 py-2">Cuenta</th>
                                        <th class="px-4 py-2 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php foreach ($groupedAccounts[4]['accounts'] as $acc): ?>
                                    <tr class="text-gray-700 text-sm">
                                        <td class="px-4 py-2 font-mono text-xs"><?php echo $acc->accountID; ?></td>
                                        <td class="px-4 py-2"><?php echo $acc->accountName; ?></td>
                                        <td class="px-4 py-2 text-right font-medium text-green-700">$<?php echo number_format($acc->calculatedBalance, 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-green-50 font-semibold text-green-800">
                                        <td colspan="2" class="px-4 py-2">Total Ingresos</td>
                                        <td class="px-4 py-2 text-right">$<?php echo number_format($groupedAccounts[4]['total'], 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php endif; ?>

                        <!-- GASTOS -->
                        <?php if (isset($groupedAccounts[5])): ?>
                        <div class="border-b">
                            <div class="px-4 py-3 bg-red-500 text-white">
                                <h3 class="font-semibold">5. GASTOS</h3>
                            </div>
                            <table id="tableGastos" class="w-full table2excel" data-tableName="Gastos">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-2">Código</th>
                                        <th class="px-4 py-2">Cuenta</th>
                                        <th class="px-4 py-2 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php foreach ($groupedAccounts[5]['accounts'] as $acc): ?>
                                    <tr class="text-gray-700 text-sm">
                                        <td class="px-4 py-2 font-mono text-xs"><?php echo $acc->accountID; ?></td>
                                        <td class="px-4 py-2"><?php echo $acc->accountName; ?></td>
                                        <td class="px-4 py-2 text-right font-medium text-red-700">$<?php echo number_format($acc->calculatedBalance, 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-red-50 font-semibold text-red-800">
                                        <td colspan="2" class="px-4 py-2">Total Gastos</td>
                                        <td class="px-4 py-2 text-right">$<?php echo number_format($groupedAccounts[5]['total'], 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php endif; ?>

                        <!-- COSTOS -->
                        <?php if (isset($groupedAccounts[6])): ?>
                        <div class="border-b">
                            <div class="px-4 py-3 bg-orange-500 text-white">
                                <h3 class="font-semibold">6. COSTOS DE VENTAS</h3>
                            </div>
                            <table id="tableCostos" class="w-full table2excel" data-tableName="Costos">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-2">Código</th>
                                        <th class="px-4 py-2">Cuenta</th>
                                        <th class="px-4 py-2 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php foreach ($groupedAccounts[6]['accounts'] as $acc): ?>
                                    <tr class="text-gray-700 text-sm">
                                        <td class="px-4 py-2 font-mono text-xs"><?php echo $acc->accountID; ?></td>
                                        <td class="px-4 py-2"><?php echo $acc->accountName; ?></td>
                                        <td class="px-4 py-2 text-right font-medium text-orange-700">$<?php echo number_format($acc->calculatedBalance, 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-orange-50 font-semibold text-orange-800">
                                        <td colspan="2" class="px-4 py-2">Total Costos</td>
                                        <td class="px-4 py-2 text-right">$<?php echo number_format($groupedAccounts[6]['total'], 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php endif; ?>

                        <!-- RESULTADO FINAL -->
                        <div class="<?php echo $utilidadNeta >= 0 ? 'bg-blue-600' : 'bg-red-600'; ?> text-white p-4">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-lg"><?php echo $utilidadNeta >= 0 ? 'UTILIDAD NETA' : 'PÉRDIDA NETA'; ?></span>
                                <span class="text-2xl font-bold">$<?php echo number_format(abs($utilidadNeta), 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- MENSAJE SI NO HAY DATOS -->
                    <?php if (empty($groupedAccounts)): ?>
                    <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                        <p class="text-gray-500">No hay cuentas registradas para el Estado de Resultados</p>
                    </div>
                    <?php endif; ?>

                    <!-- TABLA OCULTA PARA EXPORTAR -->
                    <table id="tableResultadosExport" class="hidden table2excel" data-tableName="EstadoResultados">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cuenta</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($groupedAccounts[4])): ?>
                            <tr><td colspan="3"><strong>4. INGRESOS</strong></td></tr>
                            <?php foreach ($groupedAccounts[4]['accounts'] as $acc): ?>
                            <tr>
                                <td><?php echo $acc->accountID; ?></td>
                                <td><?php echo $acc->accountName; ?></td>
                                <td><?php echo number_format($acc->calculatedBalance, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr><td></td><td><strong>Total Ingresos</strong></td><td><strong><?php echo number_format($groupedAccounts[4]['total'], 2); ?></strong></td></tr>
                            <?php endif; ?>

                            <?php if (isset($groupedAccounts[5])): ?>
                            <tr><td colspan="3"></td></tr>
                            <tr><td colspan="3"><strong>5. GASTOS</strong></td></tr>
                            <?php foreach ($groupedAccounts[5]['accounts'] as $acc): ?>
                            <tr>
                                <td><?php echo $acc->accountID; ?></td>
                                <td><?php echo $acc->accountName; ?></td>
                                <td><?php echo number_format($acc->calculatedBalance, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr><td></td><td><strong>Total Gastos</strong></td><td><strong><?php echo number_format($groupedAccounts[5]['total'], 2); ?></strong></td></tr>
                            <?php endif; ?>

                            <?php if (isset($groupedAccounts[6])): ?>
                            <tr><td colspan="3"></td></tr>
                            <tr><td colspan="3"><strong>6. COSTOS DE VENTAS</strong></td></tr>
                            <?php foreach ($groupedAccounts[6]['accounts'] as $acc): ?>
                            <tr>
                                <td><?php echo $acc->accountID; ?></td>
                                <td><?php echo $acc->accountName; ?></td>
                                <td><?php echo number_format($acc->calculatedBalance, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr><td></td><td><strong>Total Costos</strong></td><td><strong><?php echo number_format($groupedAccounts[6]['total'], 2); ?></strong></td></tr>
                            <?php endif; ?>

                            <tr><td colspan="3"></td></tr>
                            <tr><td></td><td><strong><?php echo $utilidadNeta >= 0 ? 'UTILIDAD NETA' : 'PÉRDIDA NETA'; ?></strong></td><td><strong><?php echo number_format(abs($utilidadNeta), 2); ?></strong></td></tr>
                        </tbody>
                    </table>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>
    <script>
        $(document).ready(function(){
            $(document).on("click","#exportResultados",function(){
                var table = document.getElementById('tableResultadosExport');
                var wb = XLSX.utils.table_to_book(table, {sheet: "EstadoResultados"});
                var fileName = 'EstadoResultados_<?php echo $filter_from; ?>_<?php echo $filter_to; ?>.xlsx';
                XLSX.writeFile(wb, fileName);
            });
        });
    </script>
</body>
</html>
