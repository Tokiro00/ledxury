<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$statusMeta = [
    'detectada'             => ['label' => 'Detectada',          'class' => 'bg-amber-100 text-amber-700'],
    'en_camino'             => ['label' => 'En camino',          'class' => 'bg-blue-100 text-blue-700'],
    'recibida'              => ['label' => 'Recibida',           'class' => 'bg-indigo-100 text-indigo-700'],
    'nota_credito_emitida'  => ['label' => 'NC emitida',         'class' => 'bg-green-100 text-green-700'],
    'reembarcada'           => ['label' => 'Reembarcada',        'class' => 'bg-purple-100 text-purple-700'],
    'perdida'               => ['label' => 'Perdida (write-off)','class' => 'bg-red-100 text-red-700'],
];
$sm = $statusMeta[$return->status] ?? ['label' => $return->status, 'class' => 'bg-gray-100 text-gray-600'];
$fmt = function ($n) { return number_format((float)$n, 0, ',', '.'); };
$csrfName = $this->security->get_csrf_token_name();
$csrfHash = $this->security->get_csrf_hash();

$reasonLabels = [
    'no_estaba'     => 'No estaba al llegar',
    'sin_dinero'    => 'No tenía el dinero',
    'se_arrepintio' => 'Se arrepintió / ya no lo quería',
    'demora'        => 'Entrega demorada',
    'direccion'     => 'Dirección/teléfono errado',
    'error_pedido'  => 'Pedido equivocado',
    'carrier'       => 'Falla transportadora',
    'otro'          => 'Otro',
];

$canReceive = in_array($return->status, ['detectada', 'en_camino'], true);
$canIssueNc = in_array($return->status, ['recibida'], true) && !$return->credit_note_id && $return->invoice_id;
$canReship  = in_array($return->status, ['recibida'], true);
$canMarkLost = in_array($return->status, ['detectada', 'en_camino', 'recibida'], true);
?>
<!DOCTYPE html>
<html lang="es">
<title>Devolución #<?= $return->id ?> — Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-50">
    <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => 'sisvent/admin/devoluciones/list', 'role' => $role]); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 max-w-5xl mx-auto">

                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-gray-700">Devolución #<?= $return->id ?></h2>
                        <span class="px-3 py-1 text-xs font-bold rounded-full <?= $sm['class'] ?>"><?= $sm['label'] ?></span>
                    </div>
                    <a href="<?= base_url() ?>sisvent/admin/devoluciones" class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700">&larr; Volver al listado</a>
                </div>

                <?php if ($this->session->flashdata('devoluciones_msg')): ?>
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded mb-4 text-sm"><?= htmlspecialchars($this->session->flashdata('devoluciones_msg')) ?></div>
                <?php endif; ?>
                <?php if ($this->session->flashdata('devoluciones_error')): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded mb-4 text-sm"><?= htmlspecialchars($this->session->flashdata('devoluciones_error')) ?></div>
                <?php endif; ?>

                <!-- Resumen 2 columnas -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">
                    <!-- Guía + Cliente -->
                    <div class="bg-white rounded-lg border p-4">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Guía y cliente</h4>
                        <dl class="text-sm space-y-1.5">
                            <div class="flex justify-between"><dt class="text-gray-500">Carrier</dt><dd class="font-medium"><?= htmlspecialchars($return->carrierName ?? '—') ?></dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Guía</dt><dd class="font-mono"><?= htmlspecialchars($return->numeroPreenvio ?? '—') ?></dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Estado guía</dt><dd><?= htmlspecialchars($return->estadoNombre ?: $return->guide_status) ?></dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Destino</dt><dd><?= htmlspecialchars($return->ciudadDestinoNombre ?? $return->cliente_city ?? '—') ?></dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Cliente</dt><dd class="font-medium"><?= htmlspecialchars($return->cliente_name ?? '—') ?></dd></div>
                            <?php if (!empty($return->cliente_phone)): ?>
                            <div class="flex justify-between"><dt class="text-gray-500">Teléfono</dt><dd><?= htmlspecialchars($return->cliente_phone) ?></dd></div>
                            <?php endif; ?>
                            <div class="flex justify-between"><dt class="text-gray-500">Vendedor</dt><dd><?= htmlspecialchars($return->vendor_name ?? '—') ?></dd></div>
                        </dl>
                    </div>

                    <!-- Factura + Costos -->
                    <div class="bg-white rounded-lg border p-4">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Factura y costos</h4>
                        <dl class="text-sm space-y-1.5">
                            <div class="flex justify-between"><dt class="text-gray-500">Factura</dt>
                                <dd>
                                    <?php if ($return->factura_id): ?>
                                        <a href="<?= base_url() ?>sisvent/commercial/invoices/view/<?= $return->factura_id ?>" class="text-mam-blue-petroleo hover:underline font-medium">#<?= $return->factura_id ?></a>
                                    <?php else: ?>—<?php endif; ?>
                                </dd>
                            </div>
                            <?php if ($return->factura_id): ?>
                            <div class="flex justify-between"><dt class="text-gray-500">Total factura</dt><dd class="font-bold">$<?= $fmt($return->factura_total) ?></dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Pagado</dt><dd>$<?= $fmt($return->factura_payment) ?></dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Fecha factura</dt><dd><?= $return->factura_date ? date('d/m/Y', strtotime($return->factura_date)) : '—' ?></dd></div>
                            <?php endif; ?>
                            <div class="flex justify-between border-t pt-2 mt-2"><dt class="text-gray-500">Flete devolución</dt><dd class="text-red-600">$<?= $fmt($return->flete_devolucion) ?></dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 font-bold">Flete perdido total (ida+vuelta)</dt><dd class="font-bold text-red-700">$<?= $fmt($return->flete_perdido) ?></dd></div>
                            <?php if ($return->credit_note_id): ?>
                            <div class="flex justify-between border-t pt-2 mt-2"><dt class="text-gray-500">Nota crédito</dt><dd>
                                <a href="<?= base_url() ?>sisvent/commercial/creditnotes" class="text-green-600 font-bold">#<?= $return->credit_note_id ?></a>
                                <?php if ($return->cn_status): ?><span class="text-xxs text-gray-400 ml-1">(<?= $return->cn_status ?>)</span><?php endif; ?>
                            </dd></div>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>

                <!-- Encuesta de motivo (bot WhatsApp) -->
                <div class="bg-white rounded-lg border p-4 mb-5">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">💬 Motivo de la devolución (encuesta bot)</h4>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="text-sm space-y-1.5">
                            <?php if (empty($return->survey_sent_at)): ?>
                                <p class="text-gray-500">
                                    <?php if (($return->survey_status ?? '') === 'sin_telefono'): ?>
                                        ⚠ No se pudo enviar la encuesta: el cliente no tiene teléfono registrado.
                                    <?php elseif (($return->survey_status ?? '') === 'error'): ?>
                                        ⚠ Error enviando la encuesta<?= !empty($return->survey_error) ? ': ' . htmlspecialchars($return->survey_error) : '' ?>. Se reintentará en el próximo ciclo.
                                    <?php else: ?>
                                        Encuesta aún no enviada — el bot la envía automáticamente al detectar la devolución.
                                    <?php endif; ?>
                                </p>
                            <?php else: ?>
                                <div class="flex justify-between"><dt class="text-gray-500">Enviada</dt><dd><?= date('d/m/Y H:i', strtotime($return->survey_sent_at)) ?></dd></div>
                                <?php if (!empty($return->survey_responded_at)): ?>
                                <div class="flex justify-between"><dt class="text-gray-500">Respondida</dt><dd class="text-green-700 font-medium"><?= date('d/m/Y H:i', strtotime($return->survey_responded_at)) ?></dd></div>
                                <?php else: ?>
                                <p class="text-amber-600 text-xs">Sin respuesta del cliente todavía.</p>
                                <?php endif; ?>
                                <?php if (!empty($return->survey_reason)): ?>
                                <div class="flex justify-between"><dt class="text-gray-500">Motivo</dt><dd><span class="px-2 py-0.5 text-xxs font-bold rounded-full bg-teal-100 text-teal-700"><?= htmlspecialchars($reasonLabels[$return->survey_reason] ?? $return->survey_reason) ?></span></dd></div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($return->survey_response)): ?>
                            <div class="mt-2 bg-gray-50 border rounded p-2 text-xs text-gray-700 whitespace-pre-line italic">"<?= htmlspecialchars($return->survey_response) ?>"</div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <form method="POST" action="<?= base_url() ?>sisvent/admin/devoluciones/update/<?= $return->id ?>">
                                <input type="hidden" name="<?= $csrfName ?>" value="<?= $csrfHash ?>">
                                <label class="block text-xxs text-gray-500 uppercase font-bold mb-1">Categorizar motivo</label>
                                <select name="survey_reason" class="w-full px-2 py-1.5 text-sm border rounded mb-2">
                                    <option value="">— Sin categorizar —</option>
                                    <?php foreach ($reasonLabels as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= ($return->survey_reason ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($return->survey_response)): ?>
                                <textarea name="survey_response" rows="2" placeholder="Respuesta del cliente (si te la dio por otro canal)" class="w-full px-2 py-1.5 text-sm border rounded mb-2"></textarea>
                                <?php endif; ?>
                                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-white rounded hover:opacity-80" style="background:#2E7D91;">Guardar motivo</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Productos de la factura -->
                <?php if (!empty($invoice_items)): ?>
                <div class="bg-white rounded-lg border overflow-hidden mb-5">
                    <div class="px-4 py-2 bg-gray-50 border-b">
                        <h4 class="text-xs font-bold text-gray-600 uppercase tracking-wide">Productos en la factura (volverían al stock si recibís en buen estado)</h4>
                    </div>
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xxs">
                            <tr>
                                <th class="px-3 py-1.5 text-left">Código</th>
                                <th class="px-3 py-1.5 text-left">Producto</th>
                                <th class="px-3 py-1.5 text-right">Cant.</th>
                                <th class="px-3 py-1.5 text-right">Precio</th>
                                <th class="px-3 py-1.5 text-right">Costo</th>
                                <th class="px-3 py-1.5 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach ($invoice_items as $it): ?>
                            <tr>
                                <td class="px-3 py-1.5 font-mono text-gray-600"><?= htmlspecialchars($it->productId) ?></td>
                                <td class="px-3 py-1.5"><?= htmlspecialchars($it->product_description ?? '—') ?></td>
                                <td class="px-3 py-1.5 text-right"><?= (int)$it->quantity ?></td>
                                <td class="px-3 py-1.5 text-right">$<?= $fmt($it->unit) ?></td>
                                <td class="px-3 py-1.5 text-right text-gray-400">$<?= $fmt($it->cost_cop ?: 0) ?></td>
                                <td class="px-3 py-1.5 text-right font-bold">$<?= $fmt($it->total ?? ((int)$it->quantity * (int)$it->unit)) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Acciones -->
                <div class="bg-white rounded-lg border p-4 mb-5">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Acciones</h4>

                    <?php if ($return->status === 'nota_credito_emitida'): ?>
                    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded text-sm">
                        ✅ Devolución cerrada. Nota crédito #<?= $return->credit_note_id ?> emitida.
                    </div>
                    <?php elseif ($return->status === 'perdida'): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded text-sm">
                        ⚠ Devolución cerrada como pérdida total (write-off).
                    </div>
                    <?php elseif ($return->status === 'reembarcada'): ?>
                    <div class="bg-purple-50 border border-purple-200 text-purple-800 px-4 py-3 rounded text-sm">
                        🚚 Devolución cerrada por reembarque.
                        <?php if ($return->new_guide_id): ?>Nueva guía ID: <?= $return->new_guide_id ?><?php endif; ?>
                    </div>
                    <?php else: ?>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                        <?php if ($canReceive): ?>
                        <!-- Recibir -->
                        <div class="border rounded-lg p-3 bg-indigo-50/40">
                            <h5 class="text-sm font-bold text-indigo-800 mb-2">📦 Recibir paquete en bodega</h5>
                            <p class="text-xs text-gray-600 mb-3">Marcá el paquete como recibido y registrá el estado físico del producto.</p>
                            <form method="POST" action="<?= base_url() ?>sisvent/admin/devoluciones/receive/<?= $return->id ?>" onsubmit="return confirm('Confirmar recibo?')">
                                <input type="hidden" name="<?= $csrfName ?>" value="<?= $csrfHash ?>">
                                <label class="block text-xxs text-gray-500 uppercase font-bold mb-1">Estado del producto</label>
                                <select name="product_condition" class="w-full px-2 py-1.5 text-sm border rounded mb-2">
                                    <option value="bueno">Bueno (apto para revender)</option>
                                    <option value="danado">Dañado (write-off parcial)</option>
                                    <option value="incompleto">Incompleto (faltan piezas)</option>
                                    <option value="no_recibido">No recibido (paquete vacío)</option>
                                </select>
                                <label class="flex items-center gap-2 text-xs mb-2">
                                    <input type="checkbox" name="restock_inventory" value="1" checked>
                                    <span>Devolver al stock (auto-restock al aprobar nota crédito)</span>
                                </label>
                                <textarea name="notes" rows="2" placeholder="Notas (opcional)" class="w-full px-2 py-1.5 text-sm border rounded mb-2"></textarea>
                                <button type="submit" class="w-full px-3 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded">Confirmar recibo</button>
                            </form>
                        </div>
                        <?php endif; ?>

                        <?php if ($canIssueNc): ?>
                        <!-- Generar nota crédito -->
                        <div class="border rounded-lg p-3 bg-green-50/40">
                            <h5 class="text-sm font-bold text-green-800 mb-2">📝 Generar nota crédito</h5>
                            <p class="text-xs text-gray-600 mb-3">Crea una nota crédito linkeada a la factura #<?= $return->factura_id ?>. Al aprobarla, restockea inventario y genera asientos contables automáticamente.</p>
                            <a href="<?= base_url() ?>sisvent/admin/devoluciones/generateCreditNote/<?= $return->id ?>" class="block text-center px-3 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded">→ Crear nota crédito</a>
                        </div>
                        <?php endif; ?>

                        <?php if ($canReship): ?>
                        <!-- Reembarcar -->
                        <div class="border rounded-lg p-3 bg-purple-50/40">
                            <h5 class="text-sm font-bold text-purple-800 mb-2">🚚 Reembarcar</h5>
                            <p class="text-xs text-gray-600 mb-3">El cliente acepta segundo intento. La factura se mantiene; generá una nueva guía desde el módulo de envíos y pegá el ID acá.</p>
                            <form method="POST" action="<?= base_url() ?>sisvent/admin/devoluciones/reship/<?= $return->id ?>" onsubmit="return confirm('Marcar como reembarcada?')">
                                <input type="hidden" name="<?= $csrfName ?>" value="<?= $csrfHash ?>">
                                <input type="number" name="new_guide_id" placeholder="ID nueva guía (opcional)" class="w-full px-2 py-1.5 text-sm border rounded mb-2">
                                <textarea name="notes" rows="2" placeholder="Notas" class="w-full px-2 py-1.5 text-sm border rounded mb-2"></textarea>
                                <button type="submit" class="w-full px-3 py-2 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 rounded">Marcar reembarcada</button>
                            </form>
                        </div>
                        <?php endif; ?>

                        <?php if ($canMarkLost): ?>
                        <!-- Perdida -->
                        <div class="border rounded-lg p-3 bg-red-50/40">
                            <h5 class="text-sm font-bold text-red-800 mb-2">⚠ Marcar perdida (write-off)</h5>
                            <p class="text-xs text-gray-600 mb-3">El paquete nunca llegó o llegó destruido. Asumimos el costo total.</p>
                            <form method="POST" action="<?= base_url() ?>sisvent/admin/devoluciones/markLost/<?= $return->id ?>" onsubmit="return confirm('Marcar como PERDIDA? Esta acción registra un write-off.')">
                                <input type="hidden" name="<?= $csrfName ?>" value="<?= $csrfHash ?>">
                                <textarea name="notes" rows="2" placeholder="Razón (ej: paquete extraviado por carrier)" class="w-full px-2 py-1.5 text-sm border rounded mb-2" required></textarea>
                                <button type="submit" class="w-full px-3 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded">Marcar perdida</button>
                            </form>
                        </div>
                        <?php endif; ?>

                    </div>
                    <?php endif; ?>
                </div>

                <!-- Auditoría -->
                <div class="bg-gray-50 rounded-lg border p-4 text-xs text-gray-500">
                    <strong>Auditoría:</strong>
                    Detectada el <?= date('d/m/Y H:i', strtotime($return->detected_at)) ?>
                    <?php if ($return->received_back_at): ?>
                    · Recibida el <?= date('d/m/Y H:i', strtotime($return->received_back_at)) ?> por <?= htmlspecialchars($return->received_back_by ?? '—') ?>
                    <?php endif; ?>
                    <?php if ($return->notes): ?>
                    <div class="mt-2 italic">"<?= htmlspecialchars($return->notes) ?>"</div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
