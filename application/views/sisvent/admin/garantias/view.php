<?php
$role = $this->session->userdata('user_data')['role'];
$st_color = ['abierto'=>'amber','en_revision'=>'blue','resuelto'=>'emerald','cerrado'=>'slate','cancelado'=>'red'][$ticket->status] ?? 'gray';
$prio_color = ['urgente'=>'red','alta'=>'orange','media'=>'amber','baja'=>'slate'][$ticket->priority] ?? 'slate';
$opened = $ticket->opened_at ? (new DateTime($ticket->opened_at, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Bogota'))->format('d M Y H:i') : '-';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title><?= htmlspecialchars($ticket->ticket_number) ?> · Garantías</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

    <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>

    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>

        <main class="h-full overflow-y-auto">
            <div class="px-6 mx-auto grid max-w-5xl">

                <div class="flex items-center justify-between mb-4 mt-2 flex-wrap gap-3">
                    <div class="flex items-center">
                        <a href="<?= base_url() ?>sisvent/admin/garantias" class="mr-3 text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                        <h2 class="text-lg font-semibold text-gray-700">
                            <?= htmlspecialchars($ticket->ticket_number) ?>
                            <span class="ml-2 px-2 py-0.5 text-[10px] font-bold rounded-full bg-<?= $st_color ?>-100 text-<?= $st_color ?>-800 uppercase"><?= str_replace('_',' ',$ticket->status) ?></span>
                            <span class="ml-1 px-2 py-0.5 text-[10px] font-bold rounded-full bg-<?= $prio_color ?>-100 text-<?= $prio_color ?>-800 uppercase"><?= $ticket->priority ?></span>
                        </h2>
                    </div>
                    <div class="flex gap-2">
                        <button class="btn-status px-3 py-1.5 text-xs font-bold text-white bg-blue-600 rounded hover:bg-blue-700"     data-status="en_revision">⟳ Tomar (en revisión)</button>
                        <button class="btn-status px-3 py-1.5 text-xs font-bold text-white bg-emerald-600 rounded hover:bg-emerald-700" data-status="resuelto">✓ Marcar resuelto</button>
                        <button class="btn-status px-3 py-1.5 text-xs font-bold text-white bg-slate-600 rounded hover:bg-slate-700"   data-status="cerrado">▣ Cerrar</button>
                        <button class="btn-status px-3 py-1.5 text-xs font-bold text-white bg-red-600 rounded hover:bg-red-700"       data-status="cancelado">✕ Cancelar</button>
                    </div>
                </div>

                <?php if($this->session->flashdata('success')): ?>
                    <div class="p-4 mb-4 text-sm font-semibold text-white bg-emerald-600 rounded-lg"><?= htmlspecialchars($this->session->flashdata('success')) ?></div>
                <?php endif; ?>

                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <!-- Detalles del cliente / caso -->
                    <div class="bg-white rounded-lg shadow p-4">
                        <h3 class="font-semibold text-gray-700 mb-3 border-b pb-2">Caso</h3>
                        <dl class="text-sm space-y-2">
                            <div class="flex justify-between"><dt class="text-gray-500">Cliente</dt><dd class="font-medium"><?= htmlspecialchars($ticket->client_name ?: ($ticket->client_name_full ?: '?')) ?></dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Teléfono</dt><dd class="font-mono"><?= htmlspecialchars($ticket->client_phone) ?></dd></div>
                            <?php if($ticket->client_idnum): ?><div class="flex justify-between"><dt class="text-gray-500">Cédula/NIT</dt><dd><?= htmlspecialchars($ticket->client_idnum) ?></dd></div><?php endif; ?>
                            <div class="flex justify-between"><dt class="text-gray-500">Tipo</dt><dd><?= htmlspecialchars(ucfirst($ticket->case_type)) ?></dd></div>
                            <?php if($ticket->product_id): ?><div class="flex justify-between"><dt class="text-gray-500">Producto</dt><dd class="font-mono"><?= htmlspecialchars($ticket->product_id) ?></dd></div><?php endif; ?>
                            <?php if($ticket->idInvoice): ?>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Factura</dt>
                                <dd>
                                    <a href="<?= base_url() ?>sisvent/commercial/invoices/edit/<?= (int)$ticket->idInvoice ?>" class="text-mam-blue hover:underline">
                                        #<?= (int)$ticket->idInvoice ?>
                                    </a>
                                    <span class="text-gray-400 text-xs">(<?= date('d M Y', strtotime($ticket->invoice_date)) ?>)</span>
                                </dd>
                            </div>
                            <?php endif; ?>
                            <div class="flex justify-between"><dt class="text-gray-500">Asignado a</dt><dd><?= htmlspecialchars($ticket->assigned_to ?: '—') ?></dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Abierto</dt><dd class="text-xs"><?= $opened ?></dd></div>
                        </dl>

                        <?php if($ticket->description): ?>
                        <div class="mt-3 pt-3 border-t">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Descripción</p>
                            <p class="text-sm whitespace-pre-line"><?= htmlspecialchars($ticket->description) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if($ticket->resolution_notes): ?>
                        <div class="mt-3 pt-3 border-t">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Notas de resolución</p>
                            <p class="text-sm whitespace-pre-line text-gray-700"><?= htmlspecialchars($ticket->resolution_notes) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Mensajes WhatsApp (últimos) -->
                    <div class="bg-white rounded-lg shadow p-4">
                        <h3 class="font-semibold text-gray-700 mb-3 border-b pb-2 flex items-center justify-between">
                            <span>Conversación WhatsApp</span>
                            <?php if($ticket->conversation_id): ?>
                                <a href="<?= base_url() ?>sisvent/admin/bots/messages/<?= (int)($this->config->item('meta_whatsapp_garantias')['bot_config_id'] ?? 0) ?>"
                                   class="text-xs text-mam-blue hover:underline">Abrir chat completo →</a>
                            <?php endif; ?>
                        </h3>
                        <?php if(empty($messages)): ?>
                            <p class="text-sm text-gray-400 italic">Sin mensajes vinculados a este ticket.</p>
                        <?php else: ?>
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                <?php foreach(array_reverse($messages) as $m):
                                    $isIn = $m->direction === 'incoming';
                                    $when = (new DateTime($m->created_at, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Bogota'))->format('d M H:i');
                                ?>
                                <div class="flex <?= $isIn ? '' : 'justify-end' ?>">
                                    <div class="max-w-[80%] px-3 py-2 rounded-lg text-sm <?= $isIn ? 'bg-gray-100 text-gray-700' : 'bg-emerald-100 text-emerald-900' ?>">
                                        <p class="whitespace-pre-line"><?= htmlspecialchars($m->content) ?></p>
                                        <p class="text-[10px] text-gray-500 mt-1 text-right"><?= $when ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
$(document).on('click', '.btn-status', function(){
    var status = $(this).data('status');
    var labels = { en_revision:'tomar (en revisión)', resuelto:'marcar como resuelto', cerrado:'cerrar', cancelado:'cancelar' };
    var notes = prompt('Notas para "' + labels[status] + '" (opcional):');
    if (notes === null) return;
    $.post('<?= base_url() ?>sisvent/admin/garantias/changeStatus/<?= (int)$ticket->id ?>', {
        status: status,
        notes: notes,
        '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
    }, function(res){
        if (res && res.ok) location.reload();
        else alert('Error: ' + (res && res.error ? res.error : 'desconocido'));
    }, 'json');
});
</script>
</body>
</html>
