<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html lang="es">
<title>Nuevo anticipo - Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/advances/list', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 w-full max-w-2xl mx-auto">

                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-700">Nuevo anticipo</h2>
                    <a href="<?= base_url() ?>sisvent/admin/advances" class="text-xs text-gray-500 hover:text-gray-700">← Volver</a>
                </div>

                <?php if($this->session->flashdata('error')): ?>
                <div class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 rounded">
                    <p class="text-sm text-red-700"><?= $this->session->flashdata('error') ?></p>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= base_url() ?>sisvent/admin/advances/store" class="bg-white p-5 rounded-lg shadow-xs space-y-4">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Vendedor *</label>
                        <select name="employee_id" required class="w-full px-3 py-2 border rounded text-sm">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($vendors as $v): ?>
                                <option value="<?= htmlspecialchars($v->idUser) ?>" <?= (!empty($preselect_employee) && $preselect_employee == $v->idUser) ? 'selected' : '' ?>><?= htmlspecialchars($v->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Monto (COP) *</label>
                        <input type="number" name="amount" required min="1" step="any" class="w-full px-3 py-2 border rounded text-sm" placeholder="500000">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Concepto *</label>
                        <input type="text" name="purpose" required maxlength="255" class="w-full px-3 py-2 border rounded text-sm" placeholder="Ej. Adelanto comisión semana 18">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                        <select name="type" class="w-full px-3 py-2 border rounded text-sm">
                            <option value="cash">Efectivo (cruza con próxima liquidación)</option>
                            <option value="credit">Crédito (cruza con futuras)</option>
                            <option value="scheduled">Cuotas programadas</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bodega</label>
                        <select name="store_id" class="w-full px-3 py-2 border rounded text-sm">
                            <?php foreach ($stores as $s): ?>
                                <option value="<?= $s->idStore ?>" <?= $s->idStore == 1 ? 'selected' : '' ?>><?= htmlspecialchars($s->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="border-t pt-4">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="disburse_now" id="disburse_now" value="1" class="rounded">
                            Desembolsar ahora (la plata sale ya)
                        </label>
                        <p class="text-xs text-gray-400 ml-6 mt-1">Si dejas sin marcar, queda en estado "pendiente" y se aprueba/desembolsa luego.</p>
                    </div>

                    <div id="disbursement-fields" class="hidden bg-yellow-50 -mx-5 px-5 py-3 border-t border-b border-yellow-200 space-y-3">
                        <p class="text-xs text-yellow-800 font-semibold">¿De dónde sale el dinero?</p>
                        <div class="flex flex-wrap gap-2">
                            <select name="source_type" id="source-type" class="px-2 py-1.5 border rounded text-sm">
                                <option value="caja">Caja</option>
                                <option value="banco">Banco</option>
                            </select>
                            <select name="source_id" id="source-id" class="px-2 py-1.5 border rounded text-sm flex-1 min-w-[200px]">
                                <?php foreach ($cashboxes as $cb): ?>
                                    <option value="<?= $cb->idCashbox ?>" data-type="caja"><?= htmlspecialchars($cb->name) ?></option>
                                <?php endforeach; ?>
                                <?php foreach ($bankaccounts as $ba): ?>
                                    <option value="<?= $ba->idBankAccount ?>" data-type="banco" class="hidden"><?= htmlspecialchars($ba->bankName . ' ' . $ba->accountNumber) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-mam-blue-petroleo rounded hover:bg-blue-900">Guardar</button>
                        <a href="<?= base_url() ?>sisvent/admin/advances" class="text-sm text-gray-500 hover:text-gray-700">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
<script>
$(document).on('change', '#disburse_now', function(){
    $('#disbursement-fields').toggleClass('hidden', !this.checked);
});
$(document).on('change', '#source-type', function(){
    var type = this.value;
    var $sel = $('#source-id');
    $sel.find('option').each(function(){
        $(this).toggleClass('hidden', $(this).data('type') !== type);
    });
    var $first = $sel.find('option:not(.hidden)').first();
    if ($first.length) $sel.val($first.val());
}).trigger('change');
</script>
</body>
</html>
