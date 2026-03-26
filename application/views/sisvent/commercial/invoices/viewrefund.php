<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Devolución #<?php echo str_pad($refund->idRefund, 6, '0', STR_PAD_LEFT); ?></title>
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
                            <a href="<?php echo base_url(); ?>sisvent/commercial/invoices/refunds" class="mr-4 text-gray-500 hover:text-gray-700">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            </a>
                            <h2 class="text-lg font-semibold text-gray-600">Devolución #<?php echo str_pad($refund->idRefund, 6, '0', STR_PAD_LEFT); ?></h2>
                        </div>
                        <?php if(in_array($role, [1])): ?>
                        <form action="<?php echo base_url(); ?>sisvent/commercial/invoices/undoRefund/<?php echo $refund->idRefund; ?>" method="POST" onsubmit="return confirm('¿Está seguro que desea deshacer esta devolución?\n\nEsto restaurará:\n- El total de la factura\n- Las cantidades de los productos\n- Reducirá el inventario\n\nEsta acción no se puede deshacer.');">
                            <button type="submit" class="flex items-center px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                Deshacer Devolución
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>

                    <?php if($this->session->flashdata("error")): ?>
                    <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                        <p><?php echo $this->session->flashdata("error"); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Refund Details -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
                            <h3 class="text-lg font-semibold text-red-700">Información de la Devolución</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Left Column -->
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Factura Afectada</p>
                                        <p class="text-lg font-mono font-semibold text-blue-600">#<?php echo str_pad($refund->invoiceId, 6, '0', STR_PAD_LEFT); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Cliente</p>
                                        <p class="text-lg font-semibold text-gray-800"><?php echo $refund->client_name; ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Bodega</p>
                                        <p class="text-sm text-gray-700"><?php echo $refund->store_name; ?></p>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Fecha de Devolución</p>
                                        <p class="text-sm font-medium text-gray-700"><?php echo date('d/m/Y H:i', strtotime($refund->date)); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Comentarios</p>
                                        <p class="text-sm text-gray-700"><?php echo $refund->comments ?: 'Sin comentarios'; ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Registrada</p>
                                        <p class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($refund->created_at)); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Amount -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <div class="bg-red-100 rounded-lg p-4 text-center">
                                    <p class="text-sm text-red-600 uppercase font-semibold">Valor Total de la Devolución</p>
                                    <p class="text-3xl font-bold text-red-700">$<?php echo number_format($refund->total, 2); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Returned -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-700">Productos Devueltos</h3>
                        </div>
                        <?php if(!empty($details)): ?>
                        <table class="w-full">
                            <thead>
                                <tr class="text-xs font-semibold text-left text-gray-500 uppercase bg-gray-50">
                                    <th class="px-6 py-3">Producto</th>
                                    <th class="px-6 py-3 text-center">Cantidad</th>
                                    <th class="px-6 py-3 text-right">Precio Unit.</th>
                                    <th class="px-6 py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php foreach($details as $detail): ?>
                                <tr class="text-gray-700">
                                    <td class="px-6 py-4">
                                        <p class="font-semibold text-sm"><?php echo $detail->product_name; ?></p>
                                        <p class="text-xs text-gray-500 font-mono"><?php echo $detail->productId; ?></p>
                                    </td>
                                    <td class="px-6 py-4 text-center font-medium"><?php echo $detail->quantity; ?></td>
                                    <td class="px-6 py-4 text-right text-sm">$<?php echo number_format($detail->unit, 2); ?></td>
                                    <td class="px-6 py-4 text-right font-semibold text-red-600">$<?php echo number_format($detail->total, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="bg-red-50 font-semibold">
                                    <td colspan="3" class="px-6 py-3 text-right text-red-700">Total Devuelto:</td>
                                    <td class="px-6 py-3 text-right text-red-700">$<?php echo number_format($refund->total, 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                        <?php else: ?>
                        <div class="p-8 text-center text-gray-500">
                            <p>No hay detalle de productos para esta devolución</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Info Box -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <svg class="w-5 h-5 text-yellow-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <div>
                                <h4 class="text-sm font-semibold text-yellow-800">Información sobre deshacer devolución</h4>
                                <p class="text-xs text-yellow-700 mt-1">
                                    Al deshacer esta devolución:
                                </p>
                                <ul class="text-xs text-yellow-700 mt-1 list-disc list-inside">
                                    <li>El total de la factura #<?php echo str_pad($refund->invoiceId, 6, '0', STR_PAD_LEFT); ?> se restaurará</li>
                                    <li>Las cantidades de productos volverán a la factura</li>
                                    <li>El inventario se reducirá (productos salen del stock)</li>
                                    <li>Se generará un asiento contable de reversión</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
