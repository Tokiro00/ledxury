<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Nueva Factura Proveedor</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">
                    <div class="flex items-center mb-4 mt-2">
                        <a href="<?php echo base_url(); ?>sisvent/admin/accountspayable" class="mr-4 text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </a>
                        <h2 class="text-lg font-semibold text-gray-600">Nueva Factura de Proveedor</h2>
                    </div>

                    <?php if($this->session->flashdata("error")): ?>
                    <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                        <p><?php echo $this->session->flashdata("error"); ?></p>
                    </div>
                    <?php endif; ?>

                    <form id="supplier-invoice-form" action="<?php echo base_url(); ?>sisvent/admin/accountspayable/store" method="POST">
                        <div class="px-4 py-6 bg-white rounded-lg shadow-md">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Proveedor <span class="text-red-500">*</span></span>
                                    <select name="provider_id" class="form-input form-select mt-1" required>
                                        <option value="" disabled selected>Selecciona un proveedor</option>
                                        <?php foreach($providers as $provider): ?>
                                            <option value="<?php echo $provider->idProvider; ?>"><?php echo $provider->name; ?> - <?php echo $provider->idNum; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>

                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Numero de Factura <span class="text-red-500">*</span></span>
                                    <input type="text" name="invoice_number" class="form-input mt-1" placeholder="Ej: FAC-001234" required>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Fecha de Factura</span>
                                    <input type="date" name="invoice_date" class="form-input mt-1" value="<?php echo date('Y-m-d'); ?>">
                                </label>

                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Fecha de Vencimiento</span>
                                    <input type="date" name="due_date" class="form-input mt-1" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                                </label>

                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Bodega Destino <span class="text-red-500">*</span></span>
                                    <select name="destination_store" class="form-input form-select mt-1" required>
                                        <option value="" disabled selected>Selecciona bodega</option>
                                        <?php foreach($stores as $store): ?>
                                            <option value="<?php echo $store->idStore; ?>"><?php echo $store->name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Concepto / Descripcion</span>
                                    <textarea name="concept" class="form-input mt-1" rows="2" placeholder="Descripcion de la compra"></textarea>
                                </label>
                            </div>

                            <!-- Product Search -->
                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Productos <span class="text-red-500">*</span></h4>
                                <div class="flex flex-row gap-2 items-end">
                                    <div class="flex-1">
                                        <span class="text-xs text-gray-500">Producto</span>
                                        <input class="form-input" type="text" id="supplier-product" placeholder="Buscar por codigo o nombre..." autocomplete="off">
                                    </div>
                                    <div class="w-24">
                                        <span class="text-xs text-gray-500">Cantidad</span>
                                        <input id="supplier-quantity" class="form-input" type="number" min="1" value="1">
                                    </div>
                                    <div class="w-32">
                                        <span class="text-xs text-gray-500">Costo Unit.</span>
                                        <input id="supplier-cost" class="form-input" type="number" min="0.01" step="0.01" placeholder="0.00">
                                    </div>
                                    <button id="btn-agregar-supplier" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-lg hover:bg-blue-700 flex items-center" type="button">
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                        Agregar
                                    </button>
                                </div>
                            </div>

                            <!-- Products Table -->
                            <div class="w-full overflow-hidden rounded-lg shadow-xs mt-4">
                                <div class="w-full overflow-x-auto">
                                    <table class="w-full whitespace-no-wrap">
                                        <thead>
                                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                                <th class="px-4 py-3">#</th>
                                                <th class="px-4 py-3">Codigo</th>
                                                <th class="px-4 py-3">Descripcion</th>
                                                <th class="px-4 py-3">Cantidad</th>
                                                <th class="px-4 py-3">Costo Unit.</th>
                                                <th class="px-4 py-3">Subtotal</th>
                                                <th class="px-4 py-3">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tborders-supplier" class="bg-white divide-y">
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Totals -->
                            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200">
                                <div class="text-sm text-gray-600">
                                    Total Productos: <span id="supplier-total-products" class="font-semibold">0</span>
                                </div>
                                <div class="text-xl font-bold text-gray-800">
                                    Total:
                                    <input id="supplier-total-val" type="hidden" name="total" value="0" readonly>
                                    <input id="supplier-total" class="form-input font-bold text-right w-48 inline" type="text" value="$0" disabled>
                                </div>
                            </div>

                            <div class="mt-6 flex gap-4">
                                <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-mam-blue-dark rounded-lg hover:bg-blue-700">
                                    Guardar Factura
                                </button>
                                <a href="<?php echo base_url(); ?>sisvent/admin/accountspayable" class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                                    Cancelar
                                </a>
                            </div>

                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

</body>
</html>
