<?php
$role = $this->session->userdata('user_data')['role'];
$fmt = function($n){ return number_format((float)$n, 0, ',', '.'); };
$statusBadge = function($s){
    switch($s){
        case 'pendiente':    return ['bg-yellow-100 text-yellow-800', 'Pendiente'];
        case 'aprobado':     return ['bg-blue-100 text-blue-800', 'Aprobado'];
        case 'desembolsado': return ['bg-green-100 text-green-800', 'Desembolsado'];
        case 'pagado':       return ['bg-gray-200 text-gray-700', 'Pagado'];
        case 'anulado':      return ['bg-red-100 text-red-800', 'Anulado'];
    }
    return ['bg-gray-100 text-gray-600', ucfirst($s)];
};
list($cls, $lbl) = $statusBadge($advance->status);
?>
<!DOCTYPE html>
<html lang="es">
<title>Anticipo <?= $advance->code ?> - Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/advances/list', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 w-full max-w-3xl mx-auto">

                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-gray-700">Anticipo <?= $advance->code ?></h2>
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full <?= $cls ?>"><?= $lbl ?></span>
                    </div>
                    <a href="<?= base_url() ?>sisvent/admin/advances" class="text-xs text-gray-500 hover:text-gray-700">← Volver</a>
                </div>

                <div class="bg-white rounded-lg shadow-xs p-5 mb-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xxs text-gray-400 uppercase">Vendedor</p>
                            <p class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($advance->employee_name ?: $advance->employee_id) ?></p>
                        </div>
                        <div>
                            <p class="text-xxs text-gray-400 uppercase">Fecha de creación</p>
                            <p class="text-sm text-gray-700"><?= date('d/m/Y H:i', strtotime($advance->created_at)) ?></p>
                            <p class="text-xs text-gray-400">por <?= htmlspecialchars($advance->created_by) ?></p>
                        </div>
                        <div>
                            <p class="text-xxs text-gray-400 uppercase">Concepto</p>
                            <p class="text-sm text-gray-700"><?= htmlspecialchars($advance->purpose) ?></p>
                        </div>
                        <div>
                            <p class="text-xxs text-gray-400 uppercase">Tipo</p>
                            <p class="text-sm text-gray-700 capitalize"><?= $advance->type ?></p>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xxs text-gray-400 uppercase">Monto entregado</p>
                            <p class="text-2xl font-bold text-gray-800">$<?= $fmt($advance->amount) ?></p>
                        </div>
                        <div>
                            <p class="text-xxs text-gray-400 uppercase">Saldo pendiente</p>
                            <p class="text-2xl font-bold <?= $advance->outstanding_balance > 0 ? 'text-yellow-700' : 'text-gray-400' ?>">$<?= $fmt($advance->outstanding_balance) ?></p>
                        </div>
                    </div>

                    <?php if ($advance->approved_by): ?>
                    <div class="mt-4 pt-4 border-t">
                        <p class="text-xxs text-gray-400 uppercase">Aprobación</p>
                        <p class="text-sm text-gray-700">por <?= htmlspecialchars($advance->approved_by) ?> · <?= date('d/m/Y H:i', strtotime($advance->approved_at)) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($advance->disbursed_at): ?>
                    <div class="mt-4 pt-4 border-t">
                        <p class="text-xxs text-gray-400 uppercase">Desembolso</p>
                        <p class="text-sm text-gray-700"><?= date('d/m/Y H:i', strtotime($advance->disbursed_at)) ?> desde <?= ucfirst($advance->source_type) ?> #<?= $advance->source_id ?></p>
                        <?php if ($advance->entry_id): ?>
                            <a href="<?= base_url() ?>sisvent/accounting/entries/view/<?= $advance->entry_id ?>" class="text-xs text-mam-blue-petroleo hover:underline">Ver asiento #<?= $advance->entry_id ?></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($advance->cancelled_at): ?>
                    <div class="mt-4 pt-4 border-t bg-red-50 -mx-5 px-5 py-3">
                        <p class="text-xxs text-red-700 uppercase font-semibold">Anulado</p>
                        <p class="text-sm text-red-700"><?= date('d/m/Y H:i', strtotime($advance->cancelled_at)) ?></p>
                        <?php if ($advance->cancellation_reason): ?>
                            <p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($advance->cancellation_reason) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Acciones según estado y rol -->
                <?php if ($canApprove && in_array($advance->status, array('pendiente','aprobado'))): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded p-4 mb-4 space-y-3">
                    <div class="flex flex-wrap gap-2">
                        <?php if ($advance->status === 'pendiente'): ?>
                            <button type="button" id="btn-approve" data-id="<?= $advance->id ?>"
                                    class="px-3 py-1.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded">Aprobar</button>
                        <?php endif; ?>
                        <button type="button" id="btn-disburse-toggle"
                                class="px-3 py-1.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded">Desembolsar →</button>
                        <button type="button" id="btn-cancel" data-id="<?= $advance->id ?>"
                                class="px-3 py-1.5 text-xs font-medium text-red-600 hover:text-white hover:bg-red-500 border border-red-300 rounded">Anular</button>
                    </div>

                    <form id="form-disburse" method="POST" action="<?= base_url() ?>sisvent/admin/advances/disburse/<?= $advance->id ?>"
                          class="hidden border-t border-yellow-200 pt-3 space-y-2">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                        <p class="text-xs text-yellow-800 font-semibold">¿De dónde sale el dinero?</p>
                        <div class="flex flex-wrap gap-2">
                            <select name="source_type" id="source-type" class="px-2 py-1.5 text-xs border rounded">
                                <option value="caja">Caja</option>
                                <option value="banco">Banco</option>
                            </select>
                            <select name="source_id" id="source-id" class="px-2 py-1.5 text-xs border rounded flex-1 min-w-[200px]">
                                <?php foreach ($cashboxes as $cb): ?>
                                    <option value="<?= $cb->idCashbox ?>" data-type="caja"><?= htmlspecialchars($cb->name) ?></option>
                                <?php endforeach; ?>
                                <?php foreach ($bankaccounts as $ba): ?>
                                    <option value="<?= $ba->idBankAccount ?>" data-type="banco" class="hidden"><?= htmlspecialchars($ba->bankName . ' ' . $ba->accountNumber) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="px-3 py-1.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded">Confirmar desembolso</button>
                            <button type="button" id="btn-disburse-cancel" class="px-3 py-1.5 text-xs text-gray-500">Cancelar</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
<script>
$(document).on('click', '#btn-approve', function(e){
    e.preventDefault();
    if (!confirm('¿Aprobar este anticipo? Pasará a estado APROBADO.')) return;
    var id = $(this).data('id');
    $.post('<?= base_url() ?>sisvent/admin/advances/approve/' + id, { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' },
        function(r){ if (r && r.indexOf('error:') === 0) alert(r.substring(6)); else location.reload(); });
});
$(document).on('click', '#btn-disburse-toggle', function(){ $('#form-disburse').toggleClass('hidden'); });
$(document).on('click', '#btn-disburse-cancel', function(){ $('#form-disburse').addClass('hidden'); });
$(document).on('click', '#btn-cancel', function(e){
    e.preventDefault();
    var reason = prompt('Motivo de la anulación (opcional):');
    if (reason === null) return;
    var id = $(this).data('id');
    $.post('<?= base_url() ?>sisvent/admin/advances/cancel/' + id,
        { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>', reason: reason },
        function(r){ if (r && r.indexOf('error:') === 0) alert(r.substring(6)); else window.location = r; });
});
$(document).on('change', '#source-type', function(){
    var type = this.value;
    var $sel = $('#source-id');
    $sel.find('option').each(function(){ $(this).toggleClass('hidden', $(this).data('type') !== type); });
    var $first = $sel.find('option:not(.hidden)').first();
    if ($first.length) $sel.val($first.val());
}).trigger('change');
</script>
</body>
</html>
