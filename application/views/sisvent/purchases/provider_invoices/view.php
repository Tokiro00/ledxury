<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Detalle de factura de proveedor.
 * Línea gráfica de Ledxury (Tailwind + azul petróleo), igual que el detalle de
 * factura de venta: encabezado, tarjetas de resumen y tablas shadow-xs.
 */
$money = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
$moneyC = function ($n, $cur) {
    $c = strtoupper((string)$cur);
    if ($c === 'COP' || $c === '') return '$' . number_format((float)$n, 0, ',', '.');
    return ($c === 'CNY' ? 'RMB' : $c) . ' ' . number_format((float)$n, 2, ',', '.');
};
$dateEs = function ($d) {
    if (!$d) return '—';
    $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    $ts = strtotime($d); if (!$ts) return $d;
    return date('d', $ts) . ' ' . $meses[(int)date('n', $ts)-1] . ' ' . date('Y', $ts);
};
$estados = [
    'en_transito'  => ['Por recibir',  'text-orange-700 bg-orange-100'],
    'open'         => ['Abierta',      'text-blue-700 bg-blue-100'],
    'paid_partial' => ['Pago parcial', 'text-yellow-700 bg-yellow-100'],
    'paid'         => ['Pagada',       'text-green-700 bg-green-100'],
    'cancelled'    => ['Anulada',      'text-gray-600 bg-gray-100'],
];
$est = $estados[$invoice->status] ?? [ucfirst($invoice->status), 'text-gray-600 bg-gray-100'];
$vencida = !in_array($invoice->status, ['paid','cancelled']) && $invoice->due_date && strtotime($invoice->due_date) < strtotime('today');
$diasVenc = $vencida ? (int)((time() - strtotime($invoice->due_date)) / 86400) : 0;

$impCosts = $import_costs ?? [];
$impTotal = $import_costs_total ?? 0;
$impLabels = ['aduana'=>'Aduana','flete'=>'Flete','descargue'=>'Descargue','nacionalizacion'=>'Nacionalización','otro'=>'Otro'];
$puedeRecibir  = empty($invoice->received_at) && !empty($items);
$puedeEditar   = (int)($invoice->cash_payments ?? 0) === 0 && empty($invoice->received_at);
$puedeEliminar = (float)$invoice->paid < 0.01 && empty($invoice->received_at);
$puedePagar    = (float)$invoice->balance > 0.01 && $invoice->status !== 'en_transito';
?>
<!DOCTYPE html>
<html lang="es">
    <title>Factura <?= htmlspecialchars($invoice->inv_code) ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head></head>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>

            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">

                    <!-- ENCABEZADO -->
                    <div class="flex items-center justify-between mb-4 mt-2 flex-wrap gap-2">
                        <h2 class="text-lg font-semibold text-gray-600">
                            Factura de Proveedor — <?= htmlspecialchars($invoice->inv_code) ?>
                            <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full <?= $est[1] ?>"><?= $est[0] ?></span>
                            <?php if ($vencida): ?><span class="ml-1 px-2 py-1 text-xs font-semibold rounded-full text-red-700 bg-red-100"><?= $diasVenc ?> días vencida</span><?php endif; ?>
                        </h2>
                        <a href="<?= base_url() ?>sisvent/purchases/provider_invoices" class="text-sm text-mam-blue-petroleo hover:underline">← Volver a facturas</a>
                    </div>

                    <?php if ($this->session->flashdata('success')): ?>
                        <div class="px-4 py-3 mb-4 text-sm text-green-800 bg-green-100 border border-green-200 rounded-lg"><?= $this->session->flashdata('success') ?></div>
                    <?php endif; ?>
                    <?php if ($this->session->flashdata('error')): ?>
                        <div class="px-4 py-3 mb-4 text-sm text-red-800 bg-red-100 border border-red-200 rounded-lg"><?= $this->session->flashdata('error') ?></div>
                    <?php endif; ?>
                    <?php if ($this->session->flashdata('warning')): ?>
                        <div class="px-4 py-3 mb-4 text-sm text-yellow-800 bg-yellow-100 border border-yellow-200 rounded-lg"><?= $this->session->flashdata('warning') ?></div>
                    <?php endif; ?>

                    <!-- BOTONERA -->
                    <div class="flex flex-wrap gap-3 mb-6">
                        <?php if ($puedePagar): ?>
                            <button type="button" id="btn-pagar" class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg hover:bg-mam-blue focus:outline-none">Registrar Pago</button>
                        <?php endif; ?>
                        <?php if ((float)$invoice->balance > 0.01): ?>
                            <a href="<?= base_url() ?>sisvent/purchases/provider_advances/add?provider_id=<?= (int)$invoice->provider_id ?>" class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100">Registrar Anticipo</a>
                        <?php endif; ?>
                        <?php if ((float)$invoice->balance > 0.01 && !empty($advance_balance) && $advance_balance > 0.01): ?>
                            <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_invoices/apply_advances/<?= (int)$invoice->id ?>" onsubmit="return confirm('¿Aplicar los anticipos disponibles (<?= $money($advance_balance) ?>) a esta factura?');">
                                <button type="submit" class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-green-800 bg-green-100 border border-green-300 rounded-lg hover:bg-green-200">Aplicar Anticipos (<?= $money($advance_balance) ?>)</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($puedeEditar): ?>
                            <a href="<?= base_url() ?>sisvent/purchases/provider_invoices/edit/<?= (int)$invoice->id ?>" class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100">Editar</a>
                        <?php endif; ?>
                        <a href="<?= base_url() ?>sisvent/purchases/provider_invoices/statement/<?= (int)$invoice->provider_id ?>" class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100">Estado de Cuenta</a>
                        <?php if (!empty($invoice->received_at)): ?>
                            <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_invoices/unreceive/<?= (int)$invoice->id ?>" onsubmit="return confirm('¿Revertir la recepción? Sale el stock que entró y se restaura el costo anterior de cada producto. La factura vuelve a Por recibir.');">
                                <button type="submit" class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-orange-700 bg-orange-100 border border-orange-300 rounded-lg hover:bg-orange-200">Revertir Recepción</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($puedeEliminar): ?>
                            <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_invoices/delete/<?= (int)$invoice->id ?>" onsubmit="return confirm('¿Eliminar esta factura? Se reversan sus asientos contables.');">
                                <button type="submit" class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-red-700 bg-white border border-red-300 rounded-lg hover:bg-red-50">Eliminar</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- RESUMEN -->
                    <div class="grid gap-4 mb-6 md:grid-cols-4">
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Total factura</p>
                            <p class="text-lg font-bold text-gray-800 mt-1"><?= $moneyC($invoice->total, $invoice->currency) ?></p>
                            <?php if ($invoice->currency !== 'COP'): ?><p class="text-xs text-gray-500 mt-1">= <?= $money((float)$invoice->total * (float)$invoice->exchange_rate) ?></p><?php endif; ?>
                        </div>
                        <div class="bg-green-50 rounded-lg shadow-sm p-4">
                            <p class="text-xs text-green-600 uppercase">Pagado</p>
                            <p class="text-lg font-bold text-green-700 mt-1"><?= $moneyC($invoice->paid, $invoice->currency) ?></p>
                            <p class="text-xs text-green-600 mt-1"><?= count($payments) ?> pago(s)</p>
                        </div>
                        <div class="<?= $vencida ? 'bg-red-50' : 'bg-white' ?> rounded-lg shadow-sm p-4">
                            <p class="text-xs <?= $vencida ? 'text-red-600' : 'text-gray-500' ?> uppercase">Saldo</p>
                            <p class="text-lg font-bold <?= $vencida ? 'text-red-700' : 'text-gray-800' ?> mt-1"><?= $moneyC($invoice->balance, $invoice->currency) ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Proveedor</p>
                            <p class="text-sm font-bold text-gray-800 mt-1"><?= htmlspecialchars($invoice->provider_name) ?></p>
                            <p class="text-xs text-gray-500 mt-1">Emitida <?= $dateEs($invoice->issue_date) ?> · vence <?= $dateEs($invoice->due_date) ?></p>
                        </div>
                    </div>

                    <!-- DATOS + NOTAS -->
                    <div class="grid gap-4 mb-6 md:grid-cols-2">
                        <div class="w-full overflow-hidden rounded-lg shadow-xs">
                            <div class="px-4 py-3 bg-gray-50 border-b"><p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Datos de la factura</p></div>
                            <div class="bg-white p-4 text-sm text-gray-700">
                                <div class="flex justify-between py-1 border-b border-dashed"><span class="text-gray-500">Moneda</span><span class="font-medium"><?= htmlspecialchars($invoice->currency) ?><?= $invoice->currency !== 'COP' ? ' · tasa ' . number_format((float)$invoice->exchange_rate, 2, ',', '.') . ' COP' : '' ?></span></div>
                                <div class="flex justify-between py-1 border-b border-dashed"><span class="text-gray-500">Subtotal</span><span class="font-medium"><?= $moneyC($invoice->subtotal, $invoice->currency) ?></span></div>
                                <?php if ((float)$invoice->tax != 0): ?><div class="flex justify-between py-1 border-b border-dashed"><span class="text-gray-500">IVA</span><span class="font-medium"><?= $moneyC($invoice->tax, $invoice->currency) ?></span></div><?php endif; ?>
                                <?php if ((float)$invoice->withholding != 0): ?><div class="flex justify-between py-1 border-b border-dashed"><span class="text-gray-500">Retención</span><span class="font-medium">-<?= $moneyC($invoice->withholding, $invoice->currency) ?></span></div><?php endif; ?>
                                <?php if (!empty($invoice->financing_pct)): ?><div class="flex justify-between py-1 border-b border-dashed"><span class="text-gray-500">Financiación</span><span class="font-medium"><?= number_format((float)$invoice->financing_pct, 2, ',', '.') ?>%</span></div><?php endif; ?>
                                <?php if (!empty($invoice->received_at)): ?>
                                <div class="flex justify-between py-1 border-b border-dashed"><span class="text-gray-500">Recibida</span><span class="font-medium"><?= $dateEs($invoice->received_at) ?> · bodega <?= (int)$invoice->received_store_id ?></span></div>
                                <?php endif; ?>
                                <?php if (!empty($invoice->origin_ref)): ?>
                                <div class="flex justify-between py-1"><span class="text-gray-500">Origen</span><span class="font-medium"><?= htmlspecialchars($invoice->origin_ref) ?></span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($invoice->notes)): ?>
                        <div class="w-full overflow-hidden rounded-lg shadow-xs">
                            <div class="px-4 py-3 bg-gray-50 border-b"><p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Notas</p></div>
                            <div class="bg-white p-4 text-sm text-gray-700" style="white-space: pre-line;"><?= htmlspecialchars($invoice->notes) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- ARTÍCULOS + RECEPCIÓN -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs mb-6">
                        <div class="px-4 py-3 bg-gray-50 border-b">
                            <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Artículos (<?= count($items) ?>)<?= $impTotal > 0 ? ' · gastos de importación ' . $money($impTotal) : '' ?></p>
                        </div>
                        <?php if ($puedeRecibir): ?>
                        <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_invoices/receive/<?= (int)$invoice->id ?>" onsubmit="return confirm('¿Recibir esta factura? Los artículos entran al inventario y se actualiza el costo de cada producto.');">
                        <?php endif; ?>
                        <div class="w-full overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Código</th>
                                        <th class="px-4 py-3">Descripción</th>
                                        <th class="px-4 py-3 text-right">Cantidad</th>
                                        <th class="px-4 py-3 text-right whitespace-no-wrap">Costo unit.</th>
                                        <th class="px-4 py-3 text-right whitespace-no-wrap">Subtotal</th>
                                        <?php if ($puedeRecibir): ?>
                                        <th class="px-4 py-3 text-right whitespace-no-wrap">Nuevo costo</th>
                                        <th class="px-4 py-3 text-right whitespace-no-wrap">Precio venta</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php if (empty($items)): ?>
                                        <tr><td colspan="7" class="px-4 py-6 text-sm text-center text-gray-500">Esta factura no tiene artículos (es un saldo global)</td></tr>
                                    <?php else: foreach ($items as $it): ?>
                                    <tr class="text-gray-700">
                                        <td class="px-4 py-3 text-sm font-mono whitespace-no-wrap"><?= htmlspecialchars($it->product_id) ?></td>
                                        <td class="px-4 py-3 text-sm"><?= htmlspecialchars($it->product_description ?: $it->description) ?></td>
                                        <td class="px-4 py-3 text-sm text-right"><?= number_format((float)$it->quantity, 0, ',', '.') ?></td>
                                        <td class="px-4 py-3 text-sm text-right whitespace-no-wrap"><?= $moneyC($it->unit_cost, $invoice->currency) ?></td>
                                        <td class="px-4 py-3 text-sm text-right whitespace-no-wrap"><?= $moneyC($it->total, $invoice->currency) ?></td>
                                        <?php if ($puedeRecibir): ?>
                                        <td class="px-4 py-3 text-sm text-right whitespace-no-wrap">
                                            <span class="font-semibold text-gray-800"><?= $money($it->new_cost_base ?? 0) ?></span>
                                            <?php if (!empty($it->current_cost)): ?><div class="text-xs text-gray-400">antes <?= $money($it->current_cost) ?></div><?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <input type="number" step="1" min="0" name="price[<?= (int)$it->id ?>]" class="form-input text-sm text-right" style="max-width:8rem"
                                                   placeholder="<?= (int)round((float)($it->new_cost_base ?? 0) * (float)$price_factor) ?>"
                                                   value="<?= !empty($it->current_price) ? (int)$it->current_price : '' ?>">
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($puedeRecibir): ?>
                        <div class="px-4 py-3 bg-gray-50 border-t flex flex-wrap items-end gap-4">
                            <label class="flex flex-col text-sm">
                                <span class="text-gray-600 mb-1">Bodega de destino</span>
                                <select name="store_id" required class="form-input">
                                    <option value="">— Seleccionar —</option>
                                    <?php foreach ($stores as $s): ?>
                                        <option value="<?= (int)$s->idStore ?>"><?= htmlspecialchars($s->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue">Recibir Mercancía</button>
                            <p class="text-xs text-gray-500 flex-1">El stock entra a la bodega elegida y el costo de cada producto se actualiza con el costo nacionalizado. Si te equivocas, puedes revertir la recepción.</p>
                        </div>
                        </form>
                        <?php endif; ?>
                    </div>

                    <!-- GASTOS DE IMPORTACIÓN -->
                    <?php if (!empty($impCosts)): ?>
                    <div class="w-full overflow-hidden rounded-lg shadow-xs mb-6">
                        <div class="px-4 py-3 bg-gray-50 border-b"><p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Gastos de importación · <?= $money($impTotal) ?></p></div>
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Concepto</th>
                                        <th class="px-4 py-3">Prorrateo</th>
                                        <th class="px-4 py-3 text-right">Monto</th>
                                        <th class="px-4 py-3 text-right">Pagado</th>
                                        <th class="px-4 py-3 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php foreach ($impCosts as $c): ?>
                                    <tr class="text-gray-700">
                                        <td class="px-4 py-3 text-sm"><?= htmlspecialchars($impLabels[$c->concept] ?? ucfirst($c->concept)) ?><?= $c->description ? ' · ' . htmlspecialchars($c->description) : '' ?></td>
                                        <td class="px-4 py-3 text-sm"><?= $c->alloc_basis === 'cbm' ? 'por volumen (CBM)' : 'por valor' ?></td>
                                        <td class="px-4 py-3 text-sm text-right"><?= $money($c->amount_base) ?></td>
                                        <td class="px-4 py-3 text-sm text-right text-green-600"><?= $money($c->paid_amount) ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold"><?= $money($c->outstanding) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- PAGOS -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs mb-8">
                        <div class="px-4 py-3 bg-gray-50 border-b"><p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Pagos registrados (<?= count($payments) ?>)</p></div>
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Comprobante</th>
                                        <th class="px-4 py-3">Fecha</th>
                                        <th class="px-4 py-3">Origen</th>
                                        <th class="px-4 py-3">Referencia</th>
                                        <th class="px-4 py-3 text-right">Monto</th>
                                        <?php if (in_array((int)$role, [1,2,4], true)): ?><th class="px-4 py-3"></th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php if (empty($payments)): ?>
                                        <tr><td colspan="6" class="px-4 py-6 text-sm text-center text-gray-500">Sin pagos registrados todavía</td></tr>
                                    <?php else: foreach ($payments as $pay): ?>
                                    <tr class="text-gray-700">
                                        <td class="px-4 py-3 text-sm font-mono font-medium"><?= htmlspecialchars($pay->pay_code) ?></td>
                                        <td class="px-4 py-3 text-sm"><?= $dateEs($pay->pay_date) ?></td>
                                        <td class="px-4 py-3 text-sm">
                                            <?php if (!empty($pay->cash_account_name)): ?>
                                                <?= htmlspecialchars($pay->cash_account_name) ?>
                                                <div class="text-xs text-gray-500"><?= $pay->source_type === 'banco' ? 'Banco' : 'Caja' ?></div>
                                            <?php else: ?>
                                                <span class="text-gray-400"><?= htmlspecialchars($pay->payment_method ?: '—') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500"><?= htmlspecialchars($pay->reference ?: '—') ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold text-green-700"><?= $money($pay->amount) ?></td>
                                        <?php if (in_array((int)$role, [1,2,4], true)): ?>
                                        <td class="px-4 py-3 text-right">
                                            <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_payments/delete/<?= (int)$pay->id ?>" onsubmit="return confirm('¿Eliminar el pago <?= htmlspecialchars($pay->pay_code) ?>? El saldo de la caja/banco se ajusta automáticamente.');">
                                                <button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 border border-red-200 rounded hover:bg-red-50">Anular</button>
                                            </form>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- MODAL REGISTRAR PAGO -->
    <?php if ($puedePagar): ?>
    <div id="modal-pago" class="fixed inset-0 z-50 items-center justify-center hidden p-6" style="background: rgba(0,0,0,.5);">
        <div class="w-full max-w-lg bg-white rounded-lg shadow-lg">
            <form method="POST" action="<?= base_url() ?>sisvent/purchases/provider_payments/save">
                <input type="hidden" name="invoice_id" value="<?= (int)$invoice->id ?>">
                <input type="hidden" name="currency" value="COP">
                <input type="hidden" name="exchange_rate" value="1">
                <div class="px-6 py-4 border-b bg-mam-blue-petroleo rounded-t-lg">
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wide">Registrar Pago</h3>
                    <p class="text-xs text-blue-200 mt-0.5"><?= htmlspecialchars($invoice->inv_code) ?> · <?= htmlspecialchars($invoice->provider_name) ?></p>
                </div>
                <div class="px-6 py-5">
                    <div class="p-3 mb-4 rounded-lg bg-blue-50 border border-blue-200 flex items-center justify-between">
                        <span class="text-xs text-blue-600 uppercase font-medium">Saldo de la factura</span>
                        <span class="text-lg font-bold text-blue-700"><?= $money($invoice->balance) ?></span>
                    </div>
                    <div class="space-y-3">
                        <label class="block">
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Fecha del pago</span>
                            <input type="date" name="pay_date" value="<?= date('Y-m-d') ?>" required class="form-input w-full mt-1">
                        </label>
                        <label class="block">
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Monto</span>
                            <input type="number" step="0.01" min="0.01" max="<?= (float)$invoice->balance ?>" name="amount" value="<?= (float)$invoice->balance ?>" required class="form-input w-full mt-1">
                        </label>
                        <label class="block">
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Pagar desde</span>
                            <select name="fuente" required class="form-input w-full mt-1">
                                <option value="">— Seleccionar caja o banco —</option>
                                <?php if (!empty($bancos)): ?><optgroup label="Bancos">
                                    <?php foreach ($bancos as $b): ?><option value="banco:<?= (int)$b->id ?>"><?= htmlspecialchars($b->name) ?></option><?php endforeach; ?>
                                </optgroup><?php endif; ?>
                                <?php if (!empty($cajas)): ?><optgroup label="Cajas">
                                    <?php foreach ($cajas as $cb): ?><option value="caja:<?= (int)$cb->id ?>"><?= htmlspecialchars($cb->name) ?></option><?php endforeach; ?>
                                </optgroup><?php endif; ?>
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Referencia del banco</span>
                            <input type="text" name="reference" class="form-input w-full mt-1" placeholder="Nº de transferencia / consignación">
                        </label>
                        <label class="block">
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Notas (opcional)</span>
                            <input type="text" name="notes" class="form-input w-full mt-1">
                        </label>
                    </div>
                </div>
                <div class="px-6 py-3 bg-gray-50 border-t flex gap-2 justify-end rounded-b-lg">
                    <button type="button" id="btn-cancelar-pago" class="px-4 py-2 text-xs font-medium text-gray-500 hover:text-gray-700">Cancelar</button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue">Registrar Pago</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php $this->load->view('sisvent/layouts/footer'); ?>
    <script>
    // Delegado: el modal se monta después de Vue (convención del proyecto).
    $(document).on('click', '#btn-pagar', function () { $('#modal-pago').removeClass('hidden').addClass('flex'); });
    $(document).on('click', '#btn-cancelar-pago', function () { $('#modal-pago').addClass('hidden').removeClass('flex'); });
    $(document).on('click', '#modal-pago', function (e) { if (e.target === this) { $(this).addClass('hidden').removeClass('flex'); } });
    </script>
</body>
</html>
