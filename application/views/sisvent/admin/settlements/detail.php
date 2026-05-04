<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return number_format((float)$n, 0, ',', '.'); };
$ruleLabel = array(
    'default'             => array('Margen por línea',   'bg-gray-100 text-gray-700'),
    'by_commission'       => array('Comisión vendedor',  'bg-indigo-100 text-indigo-700'),
    'list_price'          => array('Precio lista',       'bg-purple-100 text-purple-700'),
    'invoice_discount'    => array('Descuento factura',  'bg-pink-100 text-pink-700'),
    'e_commerce'          => array('e-commerce',         'bg-blue-100 text-blue-700'),
    'iva'                 => array('IVA',                'bg-yellow-100 text-yellow-700'),
    'legal_collection'    => array('Cobro jurídico',     'bg-orange-100 text-orange-700'),
    'national_skipped'    => array('Nacional (omitida)', 'bg-gray-100 text-gray-500'),
    'blacklisted_skipped' => array('Blacklisted (omitida)', 'bg-red-100 text-red-700'),
);
$statusBadge = function ($s) {
    switch ($s) {
        case 'pagado':     return ['bg-green-100 text-green-700',  'Pagado'];
        case 'aprobado':   return ['bg-blue-100 text-blue-700',    'Aprobado'];
        case 'calculado':  return ['bg-yellow-100 text-yellow-700','Calculado'];
        case 'reversado':  return ['bg-red-100 text-red-700',      'Reversado'];
    }
    return ['bg-gray-100 text-gray-500', ucfirst($s)];
};
list($stCls, $stLbl) = $statusBadge($settlement->status);
?>
<!DOCTYPE html>
<html lang="es">
<title>Liquidación #<?= $settlement->id ?> - Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/settlements/list', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 w-full max-w-screen-xl mx-auto">

                <!-- Header -->
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-700">Liquidación #<?= $settlement->id ?></h2>
                            <p class="text-xs text-gray-400 mt-0.5">
                                <?= htmlspecialchars($settlement->vendor_name ?: $settlement->vendor_id) ?>
                                · <?= date('d/m/Y H:i', strtotime($settlement->created_at)) ?>
                                <?php if ($settlement->expense_id): ?>
                                    · gasto #<?= $settlement->expense_id ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full <?= $stCls ?>"><?= $stLbl ?></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="<?= base_url() ?>sisvent/admin/settlements/history?vendor=<?= urlencode($settlement->vendor_id) ?>" class="text-xs text-gray-500 hover:text-gray-700">Ver otras del vendedor</a>
                        <a href="<?= base_url() ?>sisvent/admin/settlements/history" class="px-4 py-2 text-xs text-gray-500 hover:text-gray-700">&larr; Volver</a>
                    </div>
                </div>

                <?php if (in_array($settlement->status, array('calculado','aprobado'))): ?>
                <!-- Acciones del workflow Fase 3 -->
                <div class="mb-4 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-yellow-900 font-semibold">Esta liquidación todavía no se ha pagado.</p>
                        <p class="text-xs text-yellow-700">Revisá el detalle abajo. Si está OK, "Pagar" aplica los efectos contables. "Descartar" elimina el snapshot sin tocar nada.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if ($settlement->status === 'calculado'): ?>
                        <form method="POST" action="<?= base_url() ?>sisvent/admin/settlements/approveSettlement/<?= $settlement->id ?>" style="display:inline">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <button type="submit" class="px-4 py-2 text-xs font-bold text-blue-700 bg-blue-100 hover:bg-blue-200 rounded">Aprobar</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" action="<?= base_url() ?>sisvent/admin/settlements/pay/<?= $settlement->id ?>" style="display:inline" onsubmit="return confirm('Confirmar el pago de esta liquidación. Se creará el gasto, asiento contable y se marcarán las facturas como liquidadas.')">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded">Pagar</button>
                        </form>
                        <form method="POST" action="<?= base_url() ?>sisvent/admin/settlements/discardSettlement/<?= $settlement->id ?>" style="display:inline" onsubmit="return confirm('Descartar esta liquidación calculada. No afecta facturas ni vales. Acción irreversible.')">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <button type="submit" class="px-4 py-2 text-xs font-medium text-red-600 hover:text-white hover:bg-red-500 border border-red-300 rounded">Descartar</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($settlement->notes)): ?>
                <div class="mb-4 p-3 bg-orange-50 border-l-4 border-orange-400 rounded">
                    <p class="text-sm text-orange-800"><strong>Aviso:</strong> <?= htmlspecialchars($settlement->notes) ?></p>
                </div>
                <?php endif; ?>

                <!-- KPI cards -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
                    <div class="p-3 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Recaudado</p>
                        <p class="text-xl font-semibold text-gray-700">$<?= $fmt($settlement->total_recaudado) ?></p>
                        <p class="text-xs text-gray-400 mt-0.5"><?= (int)$settlement->invoice_count ?> facturas</p>
                    </div>
                    <div class="p-3 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Comisión positiva</p>
                        <p class="text-xl font-semibold text-green-700">$<?= $fmt($settlement->total_comision) ?></p>
                    </div>
                    <div class="p-3 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Descuentos</p>
                        <p class="text-xl font-semibold text-red-600">$<?= $fmt($settlement->total_descuentos) ?></p>
                    </div>
                    <div class="p-3 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Vales</p>
                        <p class="text-xl font-semibold text-gray-600">$<?= $fmt($settlement->total_vouchers) ?></p>
                        <p class="text-xs text-gray-400 mt-0.5"><?= (int)$settlement->voucher_count ?> vales</p>
                    </div>
                    <div class="p-3 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Neto</p>
                        <p class="text-xl font-semibold <?= $settlement->total_neto >= 0 ? 'text-gray-800' : 'text-red-600' ?>">$<?= $fmt($settlement->total_neto) ?></p>
                    </div>
                </div>

                <!-- Resumen por regla -->
                <div class="mb-5 bg-white rounded-lg shadow-xs">
                    <div class="px-4 py-3 border-b">
                        <h3 class="text-sm font-semibold text-gray-700">Resumen por regla</h3>
                        <p class="text-xs text-gray-400">Cómo se distribuye la comisión según la regla aplicada a cada factura.</p>
                    </div>
                    <div class="p-4">
                        <?php if (empty($summary)): ?>
                            <p class="text-sm text-gray-400">Sin items.</p>
                        <?php else: ?>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs text-gray-400 uppercase border-b">
                                        <th class="text-left py-2">Regla</th>
                                        <th class="text-right py-2">Facturas</th>
                                        <th class="text-right py-2">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php foreach ($summary as $s):
                                        list($lbl, $cls) = $ruleLabel[$s->rule_applied] ?? array($s->rule_applied, 'bg-gray-100 text-gray-700');
                                    ?>
                                    <tr>
                                        <td class="py-2"><span class="px-2 py-0.5 text-xxs font-bold rounded-full <?= $cls ?>"><?= $lbl ?></span></td>
                                        <td class="text-right py-2 text-gray-600"><?= (int)$s->n ?></td>
                                        <td class="text-right py-2 font-semibold <?= $s->total >= 0 ? 'text-green-700' : 'text-red-600' ?>">$<?= $fmt($s->total) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Detalle por factura -->
                <div class="mb-5 bg-white rounded-lg shadow-xs">
                    <div class="px-4 py-3 border-b flex justify-between items-center">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700">Detalle por factura</h3>
                            <p class="text-xs text-gray-400">Una fila por factura procesada en esta liquidación.</p>
                        </div>
                        <input id="filterFactura" type="text" placeholder="filtrar..." class="px-2 py-1 border rounded text-xs">
                    </div>
                    <div class="overflow-x-auto">
                        <table id="itemsTable" class="w-full text-sm whitespace-no-wrap">
                            <thead>
                                <tr class="text-xs text-gray-400 uppercase border-b bg-gray-50">
                                    <th class="px-3 py-2 text-left">Factura</th>
                                    <th class="px-3 py-2 text-left">Cliente</th>
                                    <th class="px-3 py-2 text-left">Regla</th>
                                    <th class="px-3 py-2 text-right">Total fact.</th>
                                    <th class="px-3 py-2 text-right">No liquidable</th>
                                    <th class="px-3 py-2 text-right">Base</th>
                                    <th class="px-3 py-2 text-right">%</th>
                                    <th class="px-3 py-2 text-right">Comisión</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php if (empty($items)): ?>
                                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">Sin facturas en esta liquidación.</td></tr>
                                <?php else: foreach ($items as $it):
                                    list($lbl, $cls) = $ruleLabel[$it->rule_applied] ?? array($it->rule_applied, 'bg-gray-100 text-gray-700');
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2"><a href="<?= base_url() ?>sisvent/commercial/invoices/view/<?= $it->invoice_id ?>" class="text-mam-blue-petroleo hover:underline">#<?= $it->invoice_id ?></a></td>
                                    <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($it->client_name ?: $it->client_id) ?></td>
                                    <td class="px-3 py-2"><span class="px-2 py-0.5 text-xxs font-bold rounded-full <?= $cls ?>"><?= $lbl ?></span></td>
                                    <td class="px-3 py-2 text-right text-gray-600">$<?= $fmt($it->invoice_total) ?></td>
                                    <td class="px-3 py-2 text-right text-gray-400"><?= $it->not_settle_amount > 0 ? '$' . $fmt($it->not_settle_amount) : '—' ?></td>
                                    <td class="px-3 py-2 text-right text-gray-700">$<?= $fmt($it->base_amount) ?></td>
                                    <td class="px-3 py-2 text-right text-gray-600"><?= $it->percentage > 0 ? rtrim(rtrim(number_format((float)$it->percentage, 2, ',', '.'), '0'), ',') . '%' : '—' ?></td>
                                    <td class="px-3 py-2 text-right font-semibold <?= $it->commission_amount > 0 ? 'text-green-700' : ($it->commission_amount < 0 ? 'text-red-600' : 'text-gray-400') ?>">
                                        <?= $it->commission_amount != 0 ? '$' . $fmt($it->commission_amount) : '—' ?>
                                    </td>
                                    <td class="px-3 py-2 text-xs">
                                        <?php if ($it->is_self_invoice): ?><span class="text-orange-500" title="vendedor==cliente">SELF</span><?php endif; ?>
                                        <?php if ($it->is_underpriced): ?><span class="text-red-500 ml-1" title="ítem por debajo del precio base">UND</span><?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (!empty($vouchers)): ?>
                <div class="mb-5 bg-white rounded-lg shadow-xs">
                    <div class="px-4 py-3 border-b">
                        <h3 class="text-sm font-semibold text-gray-700">Vales consumidos</h3>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase border-b">
                                <th class="px-3 py-2 text-left">Vale</th>
                                <th class="px-3 py-2 text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach ($vouchers as $v): ?>
                            <tr>
                                <td class="px-3 py-2">#<?= $v->voucher_id ?></td>
                                <td class="px-3 py-2 text-right text-red-600">$<?= $fmt($v->voucher_value) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- L.2 — Anticipos cruzados con esta liquidación -->
                <?php if (!empty($advance_crosses)): ?>
                <div class="mb-5 bg-white rounded-lg shadow-xs">
                    <div class="px-4 py-3 border-b">
                        <h3 class="text-sm font-semibold text-gray-700">Anticipos cruzados (FIFO)</h3>
                        <p class="text-xs text-gray-400">Saldos pendientes de anticipos descontados del neto pagado.</p>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase border-b">
                                <th class="px-3 py-2 text-left">Anticipo</th>
                                <th class="px-3 py-2 text-left">Concepto</th>
                                <th class="px-3 py-2 text-right">Monto original</th>
                                <th class="px-3 py-2 text-right">Cruzado aquí</th>
                                <th class="px-3 py-2 text-left">Fecha cruce</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php $crossTotal = 0; foreach ($advance_crosses as $cx): $crossTotal += (float)$cx->amount_applied; ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 font-mono">
                                    <a href="<?= base_url() ?>sisvent/admin/advances/view/<?= $cx->advance_id ?>" class="text-mam-blue-petroleo hover:underline"><?= htmlspecialchars($cx->advance_code) ?></a>
                                </td>
                                <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($cx->purpose) ?></td>
                                <td class="px-3 py-2 text-right text-gray-500">$<?= $fmt($cx->advance_amount) ?></td>
                                <td class="px-3 py-2 text-right font-semibold text-yellow-700">−$<?= $fmt($cx->amount_applied) ?></td>
                                <td class="px-3 py-2 text-gray-500"><?= date('d/m/Y H:i', strtotime($cx->applied_at)) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="bg-yellow-50 font-bold">
                                <td colspan="3" class="px-3 py-2 text-right text-gray-700">Total cruzado:</td>
                                <td class="px-3 py-2 text-right text-yellow-700">−$<?= $fmt($crossTotal) ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php elseif (!empty($pending_advance_balance) && $pending_advance_balance > 0 && in_array($settlement->status, array('calculado','aprobado'))): ?>
                <div class="mb-5 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                    <p class="text-sm font-semibold text-yellow-900">Anticipos pendientes del vendedor: $<?= $fmt($pending_advance_balance) ?></p>
                    <p class="text-xs text-yellow-700">Al pagar esta liquidación se cruzarán FIFO contra el neto.</p>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</div>
<script>
// Filtro client-side simple sobre la tabla de items
document.getElementById('filterFactura')?.addEventListener('input', function (e) {
    var q = e.target.value.toLowerCase();
    var rows = document.querySelectorAll('#itemsTable tbody tr');
    rows.forEach(function (r) {
        r.style.display = r.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
    });
});
</script>
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
