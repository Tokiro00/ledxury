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
                <div class="px-6 mx-auto grid max-w-3xl">
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

                    <form action="<?php echo base_url(); ?>sisvent/admin/accountspayable/store" method="POST">
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
                                    <span class="text-gray-700 font-medium">Número de Factura <span class="text-red-500">*</span></span>
                                    <input type="text" name="invoice_number" class="form-input mt-1" placeholder="Ej: FAC-001234" required>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Fecha de Factura</span>
                                    <input type="date" name="invoice_date" class="form-input mt-1" value="<?php echo date('Y-m-d'); ?>">
                                </label>

                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Fecha de Vencimiento</span>
                                    <input type="date" name="due_date" class="form-input mt-1" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                                </label>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Concepto / Descripción</span>
                                    <textarea name="concept" class="form-input mt-1" rows="2" placeholder="Descripción de la compra o servicio"></textarea>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Total de la Factura <span class="text-red-500">*</span></span>
                                    <div class="relative mt-1">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
                                        <input type="number" name="total" class="form-input pl-8" step="0.01" min="0.01" placeholder="0.00" required>
                                    </div>
                                </label>

                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Código de Gasto (PUC)</span>
                                    <select name="expense_code" class="form-input form-select mt-1">
                                        <option value="519595">519595 - Otros Gastos Diversos</option>
                                        <option value="519505">519505 - Comisiones</option>
                                        <option value="519510">519510 - Libros, Suscripciones, Periódicos</option>
                                        <option value="519515">519515 - Música Ambiental</option>
                                        <option value="519520">519520 - Gastos de Representación</option>
                                        <option value="519525">519525 - Elementos de Aseo</option>
                                        <option value="519530">519530 - Útiles, Papelería</option>
                                        <option value="519535">519535 - Combustibles y Lubricantes</option>
                                        <option value="519540">519540 - Envases y Empaques</option>
                                        <option value="519545">519545 - Taxis y Buses</option>
                                        <option value="519550">519550 - Estampillas</option>
                                        <option value="519555">519555 - Microfilmación</option>
                                        <option value="519560">519560 - Casino y Restaurante</option>
                                        <option value="519565">519565 - Parqueaderos</option>
                                        <option value="519570">519570 - Indemnización Daños</option>
                                        <option value="519575">519575 - Pólvora y Material</option>
                                    </select>
                                </label>
                            </div>

                            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">Asiento Contable Generado</h4>
                                <p class="text-xs text-gray-500">Al guardar se generará automáticamente:</p>
                                <div class="mt-2 text-xs">
                                    <div class="flex justify-between py-1 border-b border-gray-200">
                                        <span class="text-blue-600">Débito: Cuenta de Gasto (según código PUC)</span>
                                        <span class="font-medium">$ Total</span>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <span class="text-red-600">Crédito: 220505 - Proveedores Nacionales</span>
                                        <span class="font-medium">$ Total</span>
                                    </div>
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
