<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html lang="es">
<title>Nuevo Anticipo / Préstamo - Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/advances/list', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 w-full max-w-4xl mx-auto">

                <!-- Header con breadcrumb -->
                <div class="mb-4">
                    <p class="text-xxs font-bold text-gray-400 uppercase tracking-wider">RRHH · Anticipos y Préstamos</p>
                    <h2 class="text-xl font-bold text-gray-800 mt-1">Nuevo Anticipo / Préstamo</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Crear un nuevo anticipo de empleado</p>
                </div>

                <a href="<?= base_url() ?>sisvent/admin/advances" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 mb-4">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Volver al listado
                </a>

                <?php if($this->session->flashdata('error')): ?>
                <div class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 rounded">
                    <p class="text-sm text-red-700"><?= $this->session->flashdata('error') ?></p>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= base_url() ?>sisvent/admin/advances/store" class="bg-white p-5 rounded-lg shadow-xs space-y-4">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

                    <!-- Fila 1: Código + Tipo + Bodega -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Código</label>
                            <input type="text" value="<?= htmlspecialchars($nextCode) ?>" class="w-full px-3 py-2 border rounded text-sm bg-gray-50 text-gray-700 font-mono" disabled>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Tipo <span class="text-red-500">*</span></label>
                            <select name="type" id="type-select" class="w-full px-3 py-2 border rounded text-sm">
                                <option value="anticipo">Anticipo (vale)</option>
                                <option value="prestamo">Préstamo (cuotas)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Bodega <span class="text-red-500">*</span></label>
                            <select name="store_id" class="w-full px-3 py-2 border rounded text-sm">
                                <?php foreach ($stores as $s): ?>
                                    <option value="<?= $s->idStore ?>" <?= $s->idStore == 1 ? 'selected' : '' ?>><?= htmlspecialchars($s->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Fila 2: Empleado -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Empleado <span class="text-red-500">*</span></label>
                        <select name="employee_id" required class="w-full px-3 py-2 border rounded text-sm">
                            <option value="">Seleccione un empleado...</option>
                            <?php foreach ($vendors as $v): ?>
                                <option value="<?= htmlspecialchars($v->idUser) ?>" <?= (!empty($preselect_employee) && $preselect_employee == $v->idUser) ? 'selected' : '' ?>><?= htmlspecialchars($v->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Fila 3: Fecha + Monto + Cuotas + Valor cuota -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Fecha <span class="text-red-500">*</span></label>
                            <input type="date" name="advance_date" id="advance_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border rounded text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Monto <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" id="amount-input" required min="1" step="any" class="w-full px-3 py-2 border rounded text-sm" placeholder="0">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">No. Cuotas</label>
                            <input type="number" name="num_installments" id="num-installments" value="1" min="1" max="60" class="w-full px-3 py-2 border rounded text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Valor Cuota</label>
                            <input type="text" id="installment-display" value="$0" class="w-full px-3 py-2 border rounded text-sm bg-gray-50 text-gray-700" readonly>
                        </div>
                    </div>

                    <!-- Fila 4: Propósito -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Propósito / Motivo <span class="text-red-500">*</span></label>
                        <input type="text" name="purpose" required maxlength="255" class="w-full px-3 py-2 border rounded text-sm" placeholder="Ej: Anticipo de nómina, Préstamo educación, Vale almuerzo...">
                    </div>

                    <!-- Fila 5: Observaciones -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Observaciones</label>
                        <textarea name="observations" rows="2" class="w-full px-3 py-2 border rounded text-sm" placeholder="Notas adicionales..."></textarea>
                    </div>

                    <!-- Toggle desembolsar ahora -->
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
                            <select name="source_id" id="source-id" class="px-2 py-1.5 border rounded text-sm flex-1" style="min-width:200px;">
                                <?php foreach ($cashboxes as $cb): ?>
                                    <option value="<?= $cb->idCashbox ?>" data-type="caja"><?= htmlspecialchars($cb->name) ?></option>
                                <?php endforeach; ?>
                                <?php foreach ($bankaccounts as $ba): ?>
                                    <option value="<?= $ba->idBankAccount ?>" data-type="banco" class="hidden"><?= htmlspecialchars($ba->bankName . ' ' . $ba->accountNumber) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-3 border-t">
                        <button type="submit" class="px-5 py-2 text-sm font-bold text-white rounded" style="background:#4487A0;">Crear Borrador</button>
                        <a href="<?= base_url() ?>sisvent/admin/advances" class="text-sm text-gray-500 hover:text-gray-700">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
<script>
// Desembolsar-ahora toggle (mantiene comportamiento existente).
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

// Auto-calc Valor Cuota = monto / cuotas. Display formato COP.
function updateInstallment() {
    var amount = parseFloat($('#amount-input').val()) || 0;
    var n = parseInt($('#num-installments').val(), 10) || 1;
    if (n < 1) n = 1;
    var per = Math.round(amount / n);
    $('#installment-display').val('$' + per.toLocaleString('es-CO'));
}
$(document).on('input change', '#amount-input, #num-installments', updateInstallment);

// Tipo='anticipo' → forzar 1 cuota y deshabilitar input.
$(document).on('change', '#type-select', function(){
    var $cuotas = $('#num-installments');
    if (this.value === 'anticipo') {
        $cuotas.val(1).prop('readonly', true).addClass('bg-gray-50');
    } else {
        $cuotas.prop('readonly', false).removeClass('bg-gray-50');
    }
    updateInstallment();
}).trigger('change');
</script>
</body>
</html>
