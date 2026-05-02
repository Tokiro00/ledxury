<?php
$role = $this->session->userdata('user_data')['role'];
$f_phone = $prefill ? $prefill->client_phone : '';
$f_name  = $prefill ? ($prefill->client_name ?? '') : '';
$f_conv  = $prefill ? $prefill->id : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Nuevo ticket de garantía</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

    <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>

    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>

        <main class="h-full overflow-y-auto">
            <div class="px-6 mx-auto grid max-w-3xl">

                <div class="flex items-center mb-4 mt-2">
                    <a href="<?= base_url() ?>sisvent/admin/garantias" class="mr-3 text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <h2 class="text-lg font-semibold text-gray-700">Nuevo ticket de garantía</h2>
                </div>

                <?php if($this->session->flashdata('error')): ?>
                    <div class="p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg">
                        <?= htmlspecialchars($this->session->flashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= base_url() ?>sisvent/admin/garantias/store" class="bg-white rounded-lg shadow p-6 space-y-4">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    <?php if($f_conv): ?><input type="hidden" name="conversation_id" value="<?= (int)$f_conv ?>"><?php endif; ?>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Teléfono cliente *</label>
                            <input type="text" name="client_phone" required value="<?= htmlspecialchars($f_phone) ?>" class="form-input-lg w-full" placeholder="3001234567"/>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre cliente</label>
                            <input type="text" name="client_name" value="<?= htmlspecialchars($f_name) ?>" class="form-input-lg w-full"/>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo</label>
                            <select name="case_type" class="form-input-lg w-full">
                                <option value="garantia">Garantía</option>
                                <option value="devolucion">Devolución</option>
                                <option value="reclamo">Reclamo</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Prioridad</label>
                            <select name="priority" class="form-input-lg w-full">
                                <option value="baja">Baja</option>
                                <option value="media" selected>Media</option>
                                <option value="alta">Alta</option>
                                <option value="urgente">Urgente</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Asignar a</label>
                            <select name="assigned_to" class="form-input-lg w-full">
                                <option value="">— Sin asignar —</option>
                                <?php foreach($agents as $a): ?>
                                    <option value="<?= htmlspecialchars($a->idUser) ?>"><?= htmlspecialchars($a->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Descripción del caso</label>
                        <textarea name="description" rows="4" class="form-input-lg w-full" placeholder="Qué reporta el cliente, qué producto, fecha de compra estimada, etc."></textarea>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Factura (opcional)</label>
                            <input type="number" name="invoice_id" class="form-input-lg w-full" placeholder="idInvoice"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Presupuesto (opcional)</label>
                            <input type="number" name="budget_id" class="form-input-lg w-full" placeholder="idBudget"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Producto SKU</label>
                            <input type="text" name="product_id" class="form-input-lg w-full" placeholder="ej. 6LED-12V-A"/>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t pt-4">
                        <a href="<?= base_url() ?>sisvent/admin/garantias" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200">Cancelar</a>
                        <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded hover:opacity-90">Crear ticket</button>
                    </div>
                </form>

            </div>
        </main>
    </div>
</div>

<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
