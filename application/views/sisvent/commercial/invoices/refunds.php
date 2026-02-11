<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Devoluciones</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <h2 class="text-lg font-semibold text-gray-600">
                            Devoluciones <span class="text-sm font-normal text-gray-400">(<?php echo number_format($total); ?> registros)</span>
                        </h2>
                        <a href="<?php echo base_url(); ?>sisvent/commercial/invoices" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border rounded-lg hover:bg-gray-50">
                            Volver a Facturas
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

                    <div class="w-full overflow-hidden rounded-lg shadow-xs bg-white">
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3"># Devolución</th>
                                        <th class="px-4 py-3"># Factura</th>
                                        <th class="px-4 py-3">Cliente</th>
                                        <th class="px-4 py-3">Bodega</th>
                                        <th class="px-4 py-3 text-right">Valor</th>
                                        <th class="px-4 py-3">Fecha</th>
                                        <th class="px-4 py-3">Comentarios</th>
                                        <th class="px-4 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php if(!empty($refunds)): ?>
                                        <?php foreach($refunds as $key => $refund): ?>
                                            <tr class="text-gray-700 <?php echo $key % 2 ? 'bg-gray-50' : 'bg-white'; ?>">
                                                <td class="px-4 py-3 text-sm font-mono">
                                                    #<?php echo str_pad($refund->idRefund, 6, '0', STR_PAD_LEFT); ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    <a href="<?php echo base_url(); ?>sisvent/commercial/invoices/search/<?php echo $refund->invoiceId; ?>?p=1" target="_blank" class="text-blue-600 hover:underline font-mono">
                                                        #<?php echo str_pad($refund->invoiceId, 6, '0', STR_PAD_LEFT); ?>
                                                    </a>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <p class="font-semibold text-sm"><?php echo $refund->client_name; ?></p>
                                                    <p class="text-xs text-gray-500"><?php echo $refund->client_idNum; ?></p>
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    <?php echo $refund->store_name; ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right font-bold text-red-600">
                                                    $<?php echo number_format($refund->total, 2); ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    <?php echo date('d/m/Y H:i', strtotime($refund->date)); ?>
                                                </td>
                                                <td class="px-4 py-3 text-xs max-w-xs whitespace-normal">
                                                    <?php echo $refund->comments ?: '-'; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center space-x-2">
                                                        <a href="<?php echo base_url(); ?>sisvent/commercial/invoices/viewRefund/<?php echo $refund->idRefund; ?>" class="p-2 text-blue-600 hover:bg-blue-100 rounded" title="Ver Detalle">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                        </a>
                                                        <?php if(in_array($role, [1])): ?>
                                                        <form action="<?php echo base_url(); ?>sisvent/commercial/invoices/undoRefund/<?php echo $refund->idRefund; ?>" method="POST" onsubmit="return confirm('¿Está seguro que desea deshacer esta devolución?\n\nEsto restaurará:\n- El total de la factura\n- Las cantidades de los productos\n- Reducirá el inventario\n\nEsta acción no se puede deshacer.');">
                                                            <button type="submit" class="p-2 text-orange-600 hover:bg-orange-100 rounded" title="Deshacer Devolución">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                                No hay devoluciones registradas
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
