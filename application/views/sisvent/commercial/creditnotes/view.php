<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$statusLabels = ['pendiente'=>['Pendiente','bg-yellow-100 text-yellow-700'],'aprobada'=>['Aprobada','bg-green-100 text-green-700'],'rechazada'=>['Rechazada','bg-red-100 text-red-700']];
$reasonLabels = ['defecto'=>'Producto defectuoso','dano'=>'Producto dañado','inconformidad'=>'Inconformidad','garantia'=>'Garantía fabricante','error_facturacion'=>'Error facturación','otro'=>'Otro'];
$condLabels = ['bueno'=>'Bueno','danado'=>'Dañado','defectuoso'=>'Defectuoso'];
$st = isset($statusLabels[$note->status]) ? $statusLabels[$note->status] : ['?','bg-gray-100'];
?>
<!DOCTYPE html>
<html lang="es">
    <title>Nota Credito #<?= $note->id ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full max-w-4xl mx-auto">

                    <?php if($this->session->flashdata('success_cn')): ?>
                    <div class="p-3 mb-4 text-sm font-semibold text-white bg-green-600 rounded-lg"><?= $this->session->flashdata('success_cn') ?></div>
                    <?php endif; ?>

                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Nota Credito #<?= $note->id ?></h2>
                            <p class="text-sm text-gray-500"><?= $note->type == 'garantia' ? 'Garantía' : 'Devolución' ?></p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/commercial/creditnotes" class="text-sm hover:underline" style="color:#1B365D;">← Volver</a>
                    </div>

                    <!-- Header -->
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Cliente</p>
                                <p class="text-sm font-bold"><?= $note->client_name ?></p>
                                <p class="text-xs text-gray-400"><?= $note->client_idNum ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Factura origen</p>
                                <p class="text-sm font-bold"><?= $note->invoiceId ? '#'.$note->invoiceId : 'N/A' ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Estado</p>
                                <span class="inline-block mt-1 px-3 py-1 rounded-full text-xs font-bold <?= $st[1] ?>"><?= $st[0] ?></span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Total</p>
                                <p class="text-xl font-black" style="color:#1B365D;">$<?= number_format($note->total, 0, ',', '.') ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Motivo</p>
                                <p class="text-sm"><?= isset($reasonLabels[$note->reason]) ? $reasonLabels[$note->reason] : $note->reason ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Bodega</p>
                                <p class="text-sm"><?= $note->store_name ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Creado por</p>
                                <p class="text-sm"><?= $note->vendor_name ?></p>
                                <p class="text-xs text-gray-400"><?= date('d/m/Y H:i', strtotime($note->created_at)) ?></p>
                            </div>
                            <?php if($note->approved_by): ?>
                            <div>
                                <p class="text-xs text-gray-500 uppercase"><?= $note->status == 'aprobada' ? 'Aprobado por' : 'Revisado por' ?></p>
                                <p class="text-sm"><?= $note->approver_name ?></p>
                                <p class="text-xs text-gray-400"><?= date('d/m/Y H:i', strtotime($note->approved_at)) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if($note->observations): ?>
                        <div class="mt-4 pt-4 border-t">
                            <p class="text-xs text-gray-500 uppercase">Observaciones</p>
                            <p class="text-sm mt-1"><?= nl2br(htmlspecialchars($note->observations)) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if($note->rejection_reason): ?>
                        <div class="mt-4 pt-4 border-t bg-red-50 p-3 rounded">
                            <p class="text-xs text-red-500 uppercase font-bold">Motivo de rechazo</p>
                            <p class="text-sm mt-1 text-red-700"><?= htmlspecialchars($note->rejection_reason) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Productos -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-4">
                        <table class="w-full text-xs">
                            <thead>
                                <tr style="background:#1B365D; color:white;">
                                    <th class="px-3 py-2.5 font-semibold">Codigo</th>
                                    <th class="px-3 py-2.5 font-semibold">Producto</th>
                                    <th class="px-3 py-2.5 font-semibold text-center">Cantidad</th>
                                    <th class="px-3 py-2.5 font-semibold text-right">Precio</th>
                                    <th class="px-3 py-2.5 font-semibold text-right">Subtotal</th>
                                    <th class="px-3 py-2.5 font-semibold text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($details as $d): ?>
                                <tr class="border-t hover:bg-blue-50">
                                    <td class="px-3 py-1.5 font-mono"><?= $d->productId ?></td>
                                    <td class="px-3 py-1.5"><?= $d->product_name ?: $d->productId ?></td>
                                    <td class="px-3 py-1.5 text-center font-bold"><?= $d->quantity ?></td>
                                    <td class="px-3 py-1.5 text-right">$<?= number_format($d->price, 0, ',', '.') ?></td>
                                    <td class="px-3 py-1.5 text-right font-bold">$<?= number_format($d->subtotal, 0, ',', '.') ?></td>
                                    <td class="px-3 py-1.5 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $d->condition=='bueno'?'bg-green-100 text-green-700':($d->condition=='danado'?'bg-orange-100 text-orange-700':'bg-red-100 text-red-700') ?>"><?= isset($condLabels[$d->condition]) ? $condLabels[$d->condition] : $d->condition ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background:#1B365D; color:white;" class="font-bold">
                                    <td colspan="4" class="px-3 py-2.5">TOTAL</td>
                                    <td class="px-3 py-2.5 text-right">$<?= number_format($note->total, 0, ',', '.') ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Acciones -->
                    <?php if($note->status == 'pendiente' && has_permission('aprobar_notas_credito')): ?>
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <div class="flex flex-wrap gap-3">
                            <a href="<?= base_url() ?>sisvent/commercial/creditnotes/approve/<?= $note->id ?>"
                               class="px-6 py-2 text-sm font-bold text-white rounded-lg bg-green-600 hover:bg-green-700"
                               onclick="return confirm('¿Aprobar esta nota credito?\n\nEsto reduce la deuda del cliente y devuelve productos al inventario.')">
                                Aprobar
                            </a>
                            <button type="button" onclick="$('#reject-form').toggle()" class="px-6 py-2 text-sm font-bold text-white rounded-lg bg-red-500 hover:bg-red-600">Rechazar</button>
                        </div>
                        <form id="reject-form" style="display:none;" method="POST" action="<?= base_url() ?>sisvent/commercial/creditnotes/reject/<?= $note->id ?>" class="mt-3">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <textarea name="rejection_reason" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" rows="2" placeholder="Motivo del rechazo..." required></textarea>
                            <button type="submit" class="mt-2 px-4 py-2 text-sm font-bold text-white rounded-lg bg-red-600">Confirmar Rechazo</button>
                        </form>
                    </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
