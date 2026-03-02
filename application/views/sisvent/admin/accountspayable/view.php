<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];

$statusColors = array(
    'pendiente' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
    'parcial' => 'bg-blue-100 text-blue-800 border-blue-300',
    'pagada' => 'bg-green-100 text-green-800 border-green-300',
    'vencida' => 'bg-red-100 text-red-800 border-red-300',
    'anulada' => 'bg-gray-100 text-gray-800 border-gray-300'
);
$statusClass = isset($statusColors[$bill->status]) ? $statusColors[$bill->status] : 'bg-gray-100 text-gray-800 border-gray-300';
$isOverdue = ($bill->status != 'pagada' && $bill->status != 'anulada' && strtotime($bill->dueDate) < strtotime(date('Y-m-d')));
$isReceived = isset($bill->received) && $bill->received == 1;
?>
<!DOCTYPE html>
<html lang="en">
    <title>Factura Proveedor #<?php echo $bill->invoiceNumber; ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid max-w-4xl">
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <div class="flex items-center">
                            <a href="<?php echo base_url(); ?>sisvent/admin/accountspayable" class="mr-4 text-gray-500 hover:text-gray-700">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            </a>
                            <h2 class="text-lg font-semibold text-gray-600">Factura #<?php echo $bill->invoiceNumber; ?></h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if(!$isReceived && $bill->status != 'anulada'): ?>
                            <form action="<?php echo base_url(); ?>sisvent/admin/accountspayable/receive/<?php echo $bill->idSupplierInvoice; ?>" method="POST"
                                onsubmit="return confirm('Confirma que recibio la mercancia? Se actualizara el inventario.');" class="inline">
                                <button type="submit" class="flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    Recibir Mercancia
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if($bill->status != 'pagada' && $bill->status != 'anulada'): ?>
                            <a href="<?php echo base_url(); ?>sisvent/admin/accountspayable/pay/<?php echo $bill->idSupplierInvoice; ?>" class="flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                Registrar Pago
                            </a>
                            <?php endif; ?>
                        </div>
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

                    <!-- Invoice Details -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-700">Detalles de la Factura</h3>
                            <div class="flex gap-2">
                                <span class="px-3 py-1 text-sm font-semibold rounded-full border <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($bill->status); ?>
                                </span>
                                <span class="px-3 py-1 text-sm font-semibold rounded-full border <?php echo $isReceived ? 'bg-green-100 text-green-800 border-green-300' : 'bg-yellow-100 text-yellow-800 border-yellow-300'; ?>">
                                    <?php echo $isReceived ? 'Recibida' : 'En Transito'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Left Column -->
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Proveedor</p>
                                        <p class="text-lg font-semibold text-gray-800"><?php echo $bill->providerName; ?></p>
                                        <p class="text-sm text-gray-600"><?php echo $bill->providerIdNum; ?></p>
                                        <?php if($bill->providerPhone): ?>
                                        <p class="text-sm text-gray-600">Tel: <?php echo $bill->providerPhone; ?></p>
                                        <?php endif; ?>
                                        <?php if($bill->providerEmail): ?>
                                        <p class="text-sm text-gray-600"><?php echo $bill->providerEmail; ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Concepto</p>
                                        <p class="text-sm text-gray-700"><?php echo $bill->concept ?: 'Sin descripcion'; ?></p>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Fecha Factura</p>
                                            <p class="text-sm font-medium text-gray-700"><?php echo date('d/m/Y', strtotime($bill->invoiceDate)); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Fecha Vencimiento</p>
                                            <p class="text-sm font-medium <?php echo $isOverdue ? 'text-red-600' : 'text-gray-700'; ?>">
                                                <?php echo date('d/m/Y', strtotime($bill->dueDate)); ?>
                                                <?php if($isOverdue): ?>
                                                <span class="block text-xs text-red-500">Vencida</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php if(isset($destinationStore) && $destinationStore): ?>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Bodega Destino</p>
                                        <p class="text-sm font-medium text-gray-700"><?php echo $destinationStore->name; ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Registrada por</p>
                                        <p class="text-sm text-gray-700"><?php echo $bill->created_by; ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($bill->created_at)); ?></p>
                                    </div>
                                    <?php if($isReceived): ?>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Recibida por</p>
                                        <p class="text-sm text-gray-700"><?php echo $bill->received_by; ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($bill->received_at)); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Amounts Summary -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <div class="grid grid-cols-3 gap-4 text-center">
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="text-xs text-gray-500 uppercase">Total Factura</p>
                                        <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($bill->total, 0, ',', '.'); ?></p>
                                    </div>
                                    <div class="bg-green-50 rounded-lg p-4">
                                        <p class="text-xs text-green-600 uppercase">Total Pagado</p>
                                        <p class="text-2xl font-bold text-green-700">$<?php echo number_format($bill->paidAmount, 0, ',', '.'); ?></p>
                                    </div>
                                    <div class="bg-red-50 rounded-lg p-4">
                                        <p class="text-xs text-red-600 uppercase">Saldo Pendiente</p>
                                        <p class="text-2xl font-bold text-red-700">$<?php echo number_format($bill->balance, 0, ',', '.'); ?></p>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <?php if($bill->total > 0): ?>
                                <div class="mt-4">
                                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                                        <span>Progreso de Pago</span>
                                        <span><?php echo number_format(($bill->paidAmount / $bill->total) * 100, 1); ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo min(100, ($bill->paidAmount / $bill->total) * 100); ?>%"></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Product Line Items -->
                    <?php if(!empty($details)): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-700">Productos (<?php echo count($details); ?>)</h3>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="text-xs font-semibold text-left text-gray-500 uppercase bg-gray-50">
                                    <th class="px-6 py-3">#</th>
                                    <th class="px-6 py-3">Codigo</th>
                                    <th class="px-6 py-3">Descripcion</th>
                                    <th class="px-6 py-3 text-right">Cantidad</th>
                                    <th class="px-6 py-3 text-right">Costo Unit.</th>
                                    <th class="px-6 py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php foreach($details as $key => $detail): ?>
                                <tr class="text-gray-700">
                                    <td class="px-6 py-4 text-sm"><?php echo $key + 1; ?></td>
                                    <td class="px-6 py-4 text-sm font-mono"><?php echo $detail->productId; ?></td>
                                    <td class="px-6 py-4 text-sm"><?php echo $detail->description; ?></td>
                                    <td class="px-6 py-4 text-sm text-right"><?php echo $detail->quantity; ?></td>
                                    <td class="px-6 py-4 text-sm text-right">$<?php echo number_format($detail->unitCost, 0, ',', '.'); ?></td>
                                    <td class="px-6 py-4 text-sm text-right font-semibold">$<?php echo number_format($detail->total, 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 font-semibold">
                                    <td colspan="5" class="px-6 py-3 text-right text-gray-700">Total:</td>
                                    <td class="px-6 py-3 text-right text-gray-800">$<?php echo number_format($bill->total, 0, ',', '.'); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>

                    <!-- Payment History -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-700">Historial de Pagos</h3>
                        </div>
                        <?php if(!empty($payments)): ?>
                        <table class="w-full">
                            <thead>
                                <tr class="text-xs font-semibold text-left text-gray-500 uppercase bg-gray-50">
                                    <th class="px-6 py-3"># Pago</th>
                                    <th class="px-6 py-3">Fecha</th>
                                    <th class="px-6 py-3">Metodo</th>
                                    <th class="px-6 py-3">Referencia</th>
                                    <th class="px-6 py-3 text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php foreach($payments as $payment): ?>
                                <tr class="text-gray-700">
                                    <td class="px-6 py-4 text-sm font-mono">#<?php echo str_pad($payment->idSupplierPayment, 6, '0', STR_PAD_LEFT); ?></td>
                                    <td class="px-6 py-4 text-sm"><?php echo date('d/m/Y', strtotime($payment->paymentDate)); ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php echo $payment->paymentMethod == 'caja' ? 'Efectivo (Caja)' : 'Transferencia (Banco)'; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm"><?php echo $payment->reference ?: '-'; ?></td>
                                    <td class="px-6 py-4 text-sm text-right font-semibold text-green-600">$<?php echo number_format($payment->amount, 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 font-semibold">
                                    <td colspan="4" class="px-6 py-3 text-right text-gray-700">Total Pagado:</td>
                                    <td class="px-6 py-3 text-right text-green-700">$<?php echo number_format($bill->paidAmount, 0, ',', '.'); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                        <?php else: ?>
                        <div class="p-8 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <p>No hay pagos registrados para esta factura</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <?php if($bill->status != 'anulada' && $bill->paidAmount == 0 && !$isReceived): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-4">Acciones</h4>
                        <form action="<?php echo base_url(); ?>sisvent/admin/accountspayable/cancel/<?php echo $bill->idSupplierInvoice; ?>" method="POST" onsubmit="return confirm('Esta seguro que desea anular esta factura? Esta accion no se puede deshacer.');">
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                Anular Factura
                            </button>
                            <p class="text-xs text-gray-500 mt-2">Solo se pueden anular facturas sin pagos registrados y sin mercancia recibida.</p>
                        </form>
                    </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
