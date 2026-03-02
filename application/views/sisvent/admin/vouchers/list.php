<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Vales</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <h2 class="text-lg font-semibold text-gray-600">Vales</h2>
                        <?php if(in_array($role, [1])): ?>
                        <a href="<?php echo base_url(); ?>sisvent/admin/vouchers/add" class="flex items-center px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-lg hover:bg-blue-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Agregar Vale
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- Filters -->
                    <form method="GET" action="<?php echo base_url(); ?>sisvent/admin/vouchers" class="bg-white rounded-lg shadow-sm p-4 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Vendedor</label>
                                <select name="vendor" class="form-input form-select text-sm">
                                    <option value="">Todos</option>
                                    <?php foreach($vendors as $vendor): ?>
                                        <option value="<?php echo $vendor->idUser; ?>" <?php echo (isset($filters['vendor']) && $filters['vendor'] == $vendor->idUser) ? 'selected' : ''; ?>><?php echo $vendor->name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Estado</label>
                                <select name="state" class="form-input form-select text-sm">
                                    <option value="">Todos</option>
                                    <option value="1" <?php echo (isset($filters['state']) && $filters['state'] == '1') ? 'selected' : ''; ?>>Pagada</option>
                                    <option value="2" <?php echo (isset($filters['state']) && $filters['state'] == '2') ? 'selected' : ''; ?>>Liquidada</option>
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
                                <a href="<?php echo base_url(); ?>sisvent/admin/vouchers" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Summary by Vendor -->
                    <?php if(!empty($summary)): ?>
                    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase mb-3">Resumen por Vendedor</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-3">
                            <?php foreach($summary as $s): ?>
                            <div class="border rounded-lg p-3 <?php echo (isset($filters['vendor']) && $filters['vendor'] == $s->userId) ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?>">
                                <p class="text-sm font-semibold text-gray-700"><?php echo $s->vendor_name; ?></p>
                                <p class="text-xl font-bold text-gray-800">$<?php echo number_format($s->total_value, 0, ',', '.'); ?></p>
                                <p class="text-xs text-gray-500"><?php echo $s->total_vouchers; ?> vales</p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-200 flex justify-end">
                            <div class="text-right">
                                <span class="text-sm text-gray-500">Total General:</span>
                                <span class="text-xl font-bold text-gray-800 ml-2">$<?php echo number_format($grandTotal, 0, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Table -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs bg-white">
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Id</th>
                                        <th class="px-4 py-3">Vendedor</th>
                                        <th class="px-4 py-3 text-right">Valor</th>
                                        <th class="px-4 py-3">Metodo</th>
                                        <th class="px-4 py-3">Estado</th>
                                        <th class="px-4 py-3">Fecha</th>
                                        <th class="px-4 py-3">Observaciones</th>
                                        <th class="px-4 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php if(!empty($vouchers)):
                                        $currentVendor = null;
                                        $vendorSubtotal = 0;
                                        $vendorCount = 0;
                                        foreach($vouchers as $key => $voucher):
                                            // Vendor group header
                                            if ($currentVendor !== null && $currentVendor !== $voucher->userId):
                                    ?>
                                    <tr class="bg-gray-100 font-semibold">
                                        <td colspan="2" class="px-4 py-2 text-sm text-right text-gray-600">Subtotal (<?php echo $vendorCount; ?> vales):</td>
                                        <td class="px-4 py-2 text-sm text-right font-bold text-gray-800">$<?php echo number_format($vendorSubtotal, 0, ',', '.'); ?></td>
                                        <td colspan="5"></td>
                                    </tr>
                                    <?php
                                                $vendorSubtotal = 0;
                                                $vendorCount = 0;
                                            endif;

                                            if ($currentVendor !== $voucher->userId):
                                                $currentVendor = $voucher->userId;
                                    ?>
                                    <tr class="bg-blue-50">
                                        <td colspan="8" class="px-4 py-2">
                                            <span class="text-sm font-bold text-blue-800"><?php echo $voucher->vendor_name; ?></span>
                                            <span class="text-xs text-blue-600 ml-2"><?php echo $voucher->userId; ?></span>
                                        </td>
                                    </tr>
                                    <?php endif;
                                            $val = (float)preg_replace("/[^0-9.]/", "", $voucher->value);
                                            $vendorSubtotal += $val;
                                            $vendorCount++;
                                    ?>
                                            <tr class="text-gray-700 <?php echo $key % 2 ? 'bg-gray-50' : 'bg-white'; ?>">
                                                <td class="px-4 py-3 text-sm"><?php echo $voucher->idVoucher; ?></td>
                                                <td class="px-4 py-3">
                                                    <p class="font-semibold text-sm"><?php echo $voucher->vendor_name; ?></p>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right font-medium">
                                                    $<?php echo number_format($val, 2); ?>
                                                </td>
                                                <td class="px-4 py-3 text-xs"><?php echo $voucher->method_name; ?></td>
                                                <td class="px-4 py-3">
                                                    <?php if($voucher->state == 1): ?>
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-700">Pagada</span>
                                                    <?php elseif($voucher->state == 2): ?>
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">Liquidada</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">Pendiente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm"><?php echo date("d/m/Y", strtotime($voucher->date)); ?></td>
                                                <td class="px-4 py-3 text-xs max-w-xs whitespace-normal"><?php echo $voucher->description; ?></td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center space-x-2">
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/vouchers/edit/<?php echo $voucher->idVoucher; ?>" class="p-2 text-blue-600 hover:bg-blue-100 rounded" title="Editar">
                                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path></svg>
                                                        </a>
                                                        <?php if(in_array($role, [1])): ?>
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/vouchers/delete/<?php echo $voucher->idVoucher; ?>" class="p-2 text-red-600 hover:bg-red-100 rounded" onclick="showSureModal(event,this)" title="Eliminar">
                                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                    <?php endforeach;
                                        // Last vendor subtotal
                                        if ($currentVendor !== null):
                                    ?>
                                    <tr class="bg-gray-100 font-semibold">
                                        <td colspan="2" class="px-4 py-2 text-sm text-right text-gray-600">Subtotal (<?php echo $vendorCount; ?> vales):</td>
                                        <td class="px-4 py-2 text-sm text-right font-bold text-gray-800">$<?php echo number_format($vendorSubtotal, 0, ',', '.'); ?></td>
                                        <td colspan="5"></td>
                                    </tr>
                                    <?php endif; ?>
                                    <!-- Grand Total -->
                                    <tr class="bg-gray-200 font-bold">
                                        <td colspan="2" class="px-4 py-3 text-sm text-right text-gray-700">TOTAL GENERAL:</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900">$<?php echo number_format($grandTotal, 0, ',', '.'); ?></td>
                                        <td colspan="5"></td>
                                    </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                                No hay vales registrados
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
