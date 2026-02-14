<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Dashboard Financiero</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-700">Dashboard Financiero</h2>
                            <p class="text-xs text-gray-400"><?php echo date('d/m/Y H:i'); ?></p>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?php echo base_url(); ?>sisvent/accounting/reports" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-lg hover:bg-mam-blue-dark-hover">
                                Ver Reportes
                            </a>
                        </div>
                    </div>

                    <!-- LIQUIDEZ TOTAL -->
                    <div class="grid gap-6 mb-8 md:grid-cols-4">
                        <div class="flex items-center p-4 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg">
                            <div class="p-3 mr-4 text-white bg-white bg-opacity-20 rounded-full">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="text-white">
                                <p class="mb-1 text-sm font-medium opacity-80">Liquidez Total</p>
                                <p class="text-2xl font-bold">$<?php echo number_format($totalLiquidity, 2); ?></p>
                            </div>
                        </div>

                        <div class="flex items-center p-4 bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg">
                            <div class="p-3 mr-4 text-white bg-white bg-opacity-20 rounded-full">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="text-white">
                                <p class="mb-1 text-sm font-medium opacity-80">Por Cobrar</p>
                                <p class="text-2xl font-bold">$<?php echo number_format($receivableAging['total'], 2); ?></p>
                            </div>
                        </div>

                        <div class="flex items-center p-4 bg-gradient-to-r from-red-500 to-red-600 rounded-lg shadow-lg">
                            <div class="p-3 mr-4 text-white bg-white bg-opacity-20 rounded-full">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="text-white">
                                <p class="mb-1 text-sm font-medium opacity-80">Por Pagar</p>
                                <p class="text-2xl font-bold">$<?php echo number_format($payableTotal, 2); ?></p>
                            </div>
                        </div>

                        <div class="flex items-center p-4 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-lg">
                            <div class="p-3 mr-4 text-white bg-white bg-opacity-20 rounded-full">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                            </div>
                            <div class="text-white">
                                <p class="mb-1 text-sm font-medium opacity-80">Ingresos del Mes</p>
                                <p class="text-2xl font-bold">$<?php echo number_format($monthlyIngress, 2); ?></p>
                            </div>
                        </div>

                        <div class="flex items-center p-4 bg-gradient-to-r from-red-500 to-red-600 rounded-lg shadow-lg">
                            <div class="p-3 mr-4 text-white bg-white bg-opacity-20 rounded-full">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <div class="text-white">
                                <p class="mb-1 text-sm font-medium opacity-80">Gastos del Mes</p>
                                <p class="text-2xl font-bold">$<?php echo number_format($monthlyExpenses, 2); ?></p>
                            </div>
                        </div>

                        <div class="flex items-center p-4 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg shadow-lg">
                            <div class="p-3 mr-4 text-white bg-white bg-opacity-20 rounded-full">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="text-white">
                                <p class="mb-1 text-sm font-medium opacity-80">Gastos Pendientes</p>
                                <p class="text-2xl font-bold">$<?php echo number_format($pendingExpenses, 2); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- SALDOS CAJA Y BANCOS -->
                    <div class="grid gap-6 mb-8 md:grid-cols-2">
                        <!-- Cajas -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="px-4 py-3 bg-blue-50 border-b flex justify-between items-center">
                                <h3 class="font-semibold text-blue-700">Cajas</h3>
                                <span class="text-lg font-bold text-blue-700">$<?php echo number_format($totalCashboxBalance, 2); ?></span>
                            </div>
                            <div class="p-4">
                                <?php if(!empty($cashboxes)): ?>
                                    <?php foreach($cashboxes as $cb): ?>
                                    <div class="flex justify-between items-center py-2 border-b last:border-0">
                                        <div>
                                            <p class="font-medium text-gray-700"><?php echo $cb->name; ?></p>
                                            <p class="text-xs text-gray-500">
                                                Hoy: <span class="text-green-600">+$<?php echo number_format($cb->todayIngress, 2); ?></span>
                                                <span class="text-red-600">-$<?php echo number_format($cb->todayEgress, 2); ?></span>
                                            </p>
                                        </div>
                                        <span class="text-lg font-semibold text-gray-800">$<?php echo number_format($cb->currentBalance, 2); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-gray-500 text-center py-4">No hay cajas activas</p>
                                <?php endif; ?>
                            </div>
                            <div class="px-4 py-2 bg-gray-50 border-t">
                                <a href="<?php echo base_url(); ?>sisvent/admin/cashboxes" class="text-sm text-blue-600 hover:underline">Ver todas las cajas</a>
                            </div>
                        </div>

                        <!-- Bancos -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="px-4 py-3 bg-green-50 border-b flex justify-between items-center">
                                <h3 class="font-semibold text-green-700">Cuentas Bancarias</h3>
                                <span class="text-lg font-bold text-green-700">$<?php echo number_format($totalBankBalance, 2); ?></span>
                            </div>
                            <div class="p-4">
                                <?php if(!empty($bankAccounts)): ?>
                                    <?php foreach($bankAccounts as $bank): ?>
                                    <div class="flex justify-between items-center py-2 border-b last:border-0">
                                        <div>
                                            <p class="font-medium text-gray-700"><?php echo $bank->bankName; ?></p>
                                            <p class="text-xs text-gray-500">
                                                <?php echo $bank->accountNumber; ?>
                                                <?php if($bank->todayIngress > 0 || $bank->todayEgress > 0): ?>
                                                - Hoy: <span class="text-green-600">+$<?php echo number_format($bank->todayIngress, 2); ?></span>
                                                <span class="text-red-600">-$<?php echo number_format($bank->todayEgress, 2); ?></span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <span class="text-lg font-semibold text-gray-800">$<?php echo number_format($bank->currentBalance, 2); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-gray-500 text-center py-4">No hay cuentas bancarias activas</p>
                                <?php endif; ?>
                            </div>
                            <div class="px-4 py-2 bg-gray-50 border-t">
                                <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts" class="text-sm text-green-600 hover:underline">Ver todas las cuentas</a>
                            </div>
                        </div>
                    </div>

                    <!-- CUENTAS POR COBRAR -->
                    <div class="grid gap-6 mb-8 md:grid-cols-2">
                        <!-- Antiguedad -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="px-4 py-3 bg-purple-50 border-b">
                                <h3 class="font-semibold text-purple-700">Antiguedad de Cartera</h3>
                            </div>
                            <div class="p-4">
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                                            <span class="text-sm text-gray-600">Al dia (0-30 dias)</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-semibold text-gray-800">$<?php echo number_format($receivableAging['current'], 2); ?></span>
                                            <span class="text-xs text-gray-500 ml-2">(<?php echo $receivableAging['count_current']; ?>)</span>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></span>
                                            <span class="text-sm text-gray-600">31-60 dias</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-semibold text-gray-800">$<?php echo number_format($receivableAging['days_31_60'], 2); ?></span>
                                            <span class="text-xs text-gray-500 ml-2">(<?php echo $receivableAging['count_31_60']; ?>)</span>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 bg-orange-500 rounded-full mr-2"></span>
                                            <span class="text-sm text-gray-600">61-90 dias</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-semibold text-gray-800">$<?php echo number_format($receivableAging['days_61_90'], 2); ?></span>
                                            <span class="text-xs text-gray-500 ml-2">(<?php echo $receivableAging['count_61_90']; ?>)</span>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                                            <span class="text-sm text-gray-600">+90 dias</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-semibold text-gray-800">$<?php echo number_format($receivableAging['days_91_plus'], 2); ?></span>
                                            <span class="text-xs text-gray-500 ml-2">(<?php echo $receivableAging['count_91_plus']; ?>)</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 pt-3 border-t flex justify-between items-center">
                                    <span class="font-semibold text-gray-700">Total por Cobrar</span>
                                    <span class="text-lg font-bold text-purple-700">$<?php echo number_format($receivableAging['total'], 2); ?></span>
                                </div>
                            </div>
                            <div class="px-4 py-2 bg-gray-50 border-t">
                                <a href="<?php echo base_url(); ?>sisvent/admin/accountsreceivable" class="text-sm text-purple-600 hover:underline">Ver detalle de cartera</a>
                            </div>
                        </div>

                        <!-- Top Deudores -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="px-4 py-3 bg-red-50 border-b">
                                <h3 class="font-semibold text-red-700">Principales Deudores</h3>
                            </div>
                            <div class="p-4">
                                <?php if(!empty($topDebtors)): ?>
                                    <?php foreach($topDebtors as $debtor): ?>
                                    <div class="flex justify-between items-center py-2 border-b last:border-0">
                                        <div>
                                            <p class="font-medium text-gray-700 text-sm"><?php echo $debtor->client_name; ?></p>
                                            <p class="text-xs text-gray-500">
                                                <?php echo $debtor->invoice_count; ?> facturas
                                                <?php if($debtor->max_days_overdue > 30): ?>
                                                    - <span class="text-red-500"><?php echo $debtor->max_days_overdue; ?> dias</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <span class="font-semibold text-red-600">$<?php echo number_format($debtor->total_balance, 2); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-gray-500 text-center py-4">No hay deudores pendientes</p>
                                <?php endif; ?>
                            </div>
                            <div class="px-4 py-2 bg-gray-50 border-t">
                                <a href="<?php echo base_url(); ?>sisvent/admin/accountsreceivable/byClient" class="text-sm text-red-600 hover:underline">Ver todos los clientes</a>
                            </div>
                        </div>
                    </div>

                    <!-- CUENTAS POR PAGAR -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                        <div class="px-4 py-3 bg-orange-50 border-b flex justify-between items-center">
                            <h3 class="font-semibold text-orange-700">Cuentas por Pagar a Proveedores</h3>
                            <span class="text-lg font-bold text-orange-700">$<?php echo number_format($payableTotal, 2); ?></span>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-5 gap-4 text-center">
                                <div class="p-3 bg-green-50 rounded-lg">
                                    <p class="text-xs text-gray-500 mb-1">Al dia</p>
                                    <p class="text-lg font-bold text-green-600">$<?php echo number_format($payableAging['current'], 2); ?></p>
                                </div>
                                <div class="p-3 bg-yellow-50 rounded-lg">
                                    <p class="text-xs text-gray-500 mb-1">1-30 dias</p>
                                    <p class="text-lg font-bold text-yellow-600">$<?php echo number_format($payableAging['days_1_30'], 2); ?></p>
                                </div>
                                <div class="p-3 bg-orange-50 rounded-lg">
                                    <p class="text-xs text-gray-500 mb-1">31-60 dias</p>
                                    <p class="text-lg font-bold text-orange-600">$<?php echo number_format($payableAging['days_31_60'], 2); ?></p>
                                </div>
                                <div class="p-3 bg-red-50 rounded-lg">
                                    <p class="text-xs text-gray-500 mb-1">61-90 dias</p>
                                    <p class="text-lg font-bold text-red-500">$<?php echo number_format($payableAging['days_61_90'], 2); ?></p>
                                </div>
                                <div class="p-3 bg-red-100 rounded-lg">
                                    <p class="text-xs text-gray-500 mb-1">+90 dias</p>
                                    <p class="text-lg font-bold text-red-700">$<?php echo number_format($payableAging['days_90_plus'], 2); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-2 bg-gray-50 border-t">
                            <a href="<?php echo base_url(); ?>sisvent/admin/accountspayable" class="text-sm text-orange-600 hover:underline">Ver todas las cuentas por pagar</a>
                        </div>
                    </div>

                    <!-- MOVIMIENTOS RECIENTES -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                        <div class="px-4 py-3 bg-gray-100 border-b">
                            <h3 class="font-semibold text-gray-700">Movimientos Recientes</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-xs font-semibold text-left text-gray-500 uppercase bg-gray-50">
                                        <th class="px-4 py-3">Fecha</th>
                                        <th class="px-4 py-3">Descripcion</th>
                                        <th class="px-4 py-3">Origen</th>
                                        <th class="px-4 py-3">Tipo</th>
                                        <th class="px-4 py-3 text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php if(!empty($recentMovements)): ?>
                                        <?php foreach($recentMovements as $mov): ?>
                                        <tr class="text-sm text-gray-700">
                                            <td class="px-4 py-3"><?php echo date('d/m H:i', strtotime($mov->movementDate)); ?></td>
                                            <td class="px-4 py-3"><?php echo $mov->concept ?: '-'; ?></td>
                                            <td class="px-4 py-3">
                                                <?php if($mov->sourceType == 'caja'): ?>
                                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded">Caja</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">Banco</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <?php if(in_array($mov->movementType, ['ingreso', 'apertura'])): ?>
                                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded"><?php echo ucfirst($mov->movementType); ?></span>
                                                <?php elseif($mov->movementType == 'transferencia'): ?>
                                                    <span class="px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded">Transferencia</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded"><?php echo ucfirst($mov->movementType); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-right font-medium <?php echo in_array($mov->movementType, ['ingreso', 'apertura']) ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo in_array($mov->movementType, ['ingreso', 'apertura']) ? '+' : '-'; ?>$<?php echo number_format($mov->amount, 2); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay movimientos recientes</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 py-2 bg-gray-50 border-t">
                            <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements" class="text-sm text-gray-600 hover:underline">Ver todos los movimientos</a>
                        </div>
                    </div>

                    <!-- ACCESOS RAPIDOS -->
                    <div class="grid gap-4 mb-8 md:grid-cols-4">
                        <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements/add" class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
                            <div class="p-2 mr-3 text-green-500 bg-green-100 rounded-full">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700">Nuevo Movimiento</span>
                        </a>
                        <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements/transfer" class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
                            <div class="p-2 mr-3 text-purple-500 bg-purple-100 rounded-full">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700">Transferencia</span>
                        </a>
                        <a href="<?php echo base_url(); ?>sisvent/accounting/reports/balance" class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
                            <div class="p-2 mr-3 text-blue-500 bg-blue-100 rounded-full">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700">Balance General</span>
                        </a>
                        <a href="<?php echo base_url(); ?>sisvent/accounting/reports/resultados" class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
                            <div class="p-2 mr-3 text-orange-500 bg-orange-100 rounded-full">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700">Estado de Resultados</span>
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
