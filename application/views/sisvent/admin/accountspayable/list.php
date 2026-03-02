<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Cuentas por Pagar</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <h2 class="text-lg font-semibold text-gray-600">Cuentas por Pagar</h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/accountspayable/add" class="flex items-center px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-lg hover:bg-blue-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Nueva Factura Proveedor
                        </a>
                    </div>

                    <?php if($this->session->flashdata("success")): ?>
                    <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-green-600 rounded-lg shadow-md">
                        <p><?php echo $this->session->flashdata("success"); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if($this->session->flashdata("error")): ?>
                    <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                        <p><?php echo $this->session->flashdata("error"); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Aging Summary Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
                            <p class="text-xs text-gray-500 uppercase">Al Día</p>
                            <p class="text-xl font-bold text-green-600">$<?php echo number_format($aging['current'], 2); ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-400">
                            <p class="text-xs text-gray-500 uppercase">1-30 días</p>
                            <p class="text-xl font-bold text-yellow-600">$<?php echo number_format($aging['days_1_30'], 2); ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-orange-500">
                            <p class="text-xs text-gray-500 uppercase">31-60 días</p>
                            <p class="text-xl font-bold text-orange-600">$<?php echo number_format($aging['days_31_60'], 2); ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-red-500">
                            <p class="text-xs text-gray-500 uppercase">61-90 días</p>
                            <p class="text-xl font-bold text-red-600">$<?php echo number_format($aging['days_61_90'], 2); ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-red-800">
                            <p class="text-xs text-gray-500 uppercase">+90 días</p>
                            <p class="text-xl font-bold text-red-800">$<?php echo number_format($aging['days_90_plus'], 2); ?></p>
                        </div>
                    </div>

                    <!-- Total Payable -->
                    <div class="bg-red-50 rounded-lg shadow-sm p-4 mb-6 border-t-4 border-red-600">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-red-600 font-semibold uppercase">Total Cuentas por Pagar</p>
                                <p class="text-2xl font-bold text-red-700">$<?php echo number_format($aging['total'], 2); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <form method="GET" action="<?php echo base_url(); ?>sisvent/admin/accountspayable" class="bg-white rounded-lg shadow-sm p-4 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Proveedor</label>
                                <select name="provider" class="form-input form-select text-sm">
                                    <option value="">Todos</option>
                                    <?php foreach($providers as $provider): ?>
                                        <option value="<?php echo $provider->idProvider; ?>" <?php echo (isset($filters['providerId']) && $filters['providerId'] == $provider->idProvider) ? 'selected' : ''; ?>><?php echo $provider->name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Estado</label>
                                <select name="status" class="form-input form-select text-sm">
                                    <option value="">Todos</option>
                                    <option value="pendiente" <?php echo (isset($filters['status']) && $filters['status'] == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="parcial" <?php echo (isset($filters['status']) && $filters['status'] == 'parcial') ? 'selected' : ''; ?>>Parcial</option>
                                    <option value="pagada" <?php echo (isset($filters['status']) && $filters['status'] == 'pagada') ? 'selected' : ''; ?>>Pagada</option>
                                    <option value="vencida" <?php echo (isset($filters['status']) && $filters['status'] == 'vencida') ? 'selected' : ''; ?>>Vencida</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Mercancía</label>
                                <select name="received" class="form-input form-select text-sm">
                                    <option value="">Todos</option>
                                    <option value="0" <?php echo (isset($filters['received']) && $filters['received'] === '0') ? 'selected' : ''; ?>>En Tránsito</option>
                                    <option value="1" <?php echo (isset($filters['received']) && $filters['received'] === '1') ? 'selected' : ''; ?>>Recibida</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Desde</label>
                                <input type="date" name="from" class="form-input text-sm" value="<?php echo isset($filters['from']) ? $filters['from'] : ''; ?>">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Hasta</label>
                                <input type="date" name="to" class="form-input text-sm" value="<?php echo isset($filters['to']) ? $filters['to'] : ''; ?>">
                            </div>
                            <div class="flex items-end gap-2">
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-lg hover:bg-blue-700">
                                    Filtrar
                                </button>
                                <a href="<?php echo base_url(); ?>sisvent/admin/accountspayable" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Table -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs bg-white">
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3"># Factura</th>
                                        <th class="px-4 py-3">Proveedor</th>
                                        <th class="px-4 py-3">Fecha</th>
                                        <th class="px-4 py-3">Vence</th>
                                        <th class="px-4 py-3 text-right">Total</th>
                                        <th class="px-4 py-3 text-right">Pagado</th>
                                        <th class="px-4 py-3 text-right">Saldo</th>
                                        <th class="px-4 py-3">Estado</th>
                                        <th class="px-4 py-3">Mercancía</th>
                                        <th class="px-4 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php if(!empty($bills)): ?>
                                        <?php foreach($bills as $key => $bill): ?>
                                            <?php
                                            $statusColors = array(
                                                'pendiente' => 'bg-yellow-100 text-yellow-800',
                                                'parcial' => 'bg-blue-100 text-blue-800',
                                                'pagada' => 'bg-green-100 text-green-800',
                                                'vencida' => 'bg-red-100 text-red-800',
                                                'anulada' => 'bg-gray-100 text-gray-800'
                                            );
                                            $statusClass = isset($statusColors[$bill->status]) ? $statusColors[$bill->status] : 'bg-gray-100 text-gray-800';
                                            $isOverdue = ($bill->status != 'pagada' && $bill->status != 'anulada' && strtotime($bill->dueDate) < strtotime(date('Y-m-d')));
                                            ?>
                                            <tr class="text-gray-700 <?php echo $key % 2 ? 'bg-gray-50' : 'bg-white'; ?> <?php echo $isOverdue ? 'border-l-4 border-red-500' : ''; ?>">
                                                <td class="px-4 py-3 text-sm font-mono">
                                                    <?php echo $bill->invoiceNumber; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <p class="font-semibold text-sm"><?php echo $bill->providerName; ?></p>
                                                    <p class="text-xs text-gray-500"><?php echo $bill->providerIdNum; ?></p>
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    <?php echo date('d/m/Y', strtotime($bill->invoiceDate)); ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm <?php echo $isOverdue ? 'text-red-600 font-semibold' : ''; ?>">
                                                    <?php echo date('d/m/Y', strtotime($bill->dueDate)); ?>
                                                    <?php if($isOverdue): ?>
                                                        <span class="block text-xs">Vencida</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right font-medium">
                                                    $<?php echo number_format($bill->total, 2); ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right text-green-600">
                                                    $<?php echo number_format($bill->paidAmount, 2); ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right font-bold text-red-600">
                                                    $<?php echo number_format($bill->balance, 2); ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($bill->status); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <?php if(isset($bill->received) && $bill->received == 1): ?>
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Recibida</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">En Tránsito</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center space-x-2">
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/accountspayable/view/<?php echo $bill->idSupplierInvoice; ?>" class="p-2 text-blue-600 hover:bg-blue-100 rounded" title="Ver">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                        </a>
                                                        <?php if($bill->status != 'pagada' && $bill->status != 'anulada'): ?>
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/accountspayable/pay/<?php echo $bill->idSupplierInvoice; ?>" class="p-2 text-green-600 hover:bg-green-100 rounded" title="Pagar">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                                No hay facturas de proveedor registradas
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if($total > 0): ?>
                        <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t bg-gray-50 sm:grid-cols-9">
                            <span class="flex items-center col-span-3">
                                <?php $last = ceil($total / $limit); ?>
                                Mostrando <?php echo ((($page-1) * $limit)+1).'-'.(($last == $page) ? ($total) : ((($page-1) * $limit)+$limit)).' de '.($total); ?>
                            </span>
                            <span class="col-span-2"></span>
                            <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
                                <nav aria-label="Table navigation">
                                    <?php echo createLinks($page, $total, "", $limit); ?>
                                </nav>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
