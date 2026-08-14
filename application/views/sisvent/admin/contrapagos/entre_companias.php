<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
    <title>Entre Compañías - Ledxury</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/contrapagos/index', 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 py-5 w-full max-w-screen-xl mx-auto">

                    <!-- Header -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-700">Entre Compañías</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Saldo entre Ledxury y MAM por operaciones de Interrapidisimo</p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/admin/contrapagos" class="mt-2 lg:mt-0 text-xs text-mam-blue-petroleo hover:underline">&larr; Pagos Contrapago</a>
                    </div>

                    <!-- Totales globales (suma de todas las empresas partner) -->
                    <div class="bg-white rounded-lg border p-6 mb-5">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="text-center border-r-0 lg:border-r border-gray-200">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Ledxury cobró por terceros</p>
                                <p class="text-xs text-gray-400">Contrapagos de clientes ajenos</p>
                                <p class="text-2xl font-bold text-green-600 mt-2">$<?= number_format($totales['cobrado_total'], 0, ',', '.') ?></p>
                            </div>
                            <div class="text-center border-r-0 lg:border-r border-gray-200">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Ledxury pagó por terceros</p>
                                <p class="text-xs text-gray-400">Fletes de guías ajenas</p>
                                <p class="text-2xl font-bold text-red-600 mt-2">$<?= number_format($totales['fletes_total'], 0, ',', '.') ?></p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Saldo neto</p>
                                <p class="text-xs text-gray-400"><?= $totales['saldo_neto'] >= 0 ? 'A favor de Ledxury' : 'A pagar' ?></p>
                                <p class="text-2xl font-bold mt-2 <?= $totales['saldo_neto'] >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                    $<?= number_format(abs($totales['saldo_neto']), 0, ',', '.') ?>
                                </p>
                                <?php if (!empty($totales['pagos_recibidos'])): ?>
                                <p class="text-xs mt-1 text-gray-400">
                                    Pagos recibidos: $<?= number_format($totales['pagos_recibidos'], 0, ',', '.') ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Desglose por empresa partner -->
                    <?php if (!empty($resumen)): ?>
                    <div class="bg-white rounded-lg border overflow-hidden mb-5">
                        <div class="px-4 py-3 border-b bg-gray-50">
                            <h3 class="text-sm font-bold text-gray-700">Saldos por empresa partner</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Cada empresa tiene su saldo independiente. Positivo = nos deben. Negativo = ya cobramos por ellos.</p>
                        </div>
                        <table class="w-full text-xs">
                            <thead>
                                <tr style="background:#1B365D;">
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-white uppercase">Empresa</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-white uppercase">Fletes pagados a Interrapidísimo</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-white uppercase">Contrapagos cobrados</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-white uppercase">Pagos recibidos</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-white uppercase">Saldo neto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i=0; foreach ($resumen as $r): $i++; ?>
                                <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?>">
                                    <td class="px-4 py-2.5 font-bold text-gray-800"><?= htmlspecialchars($r->label) ?></td>
                                    <td class="px-4 py-2.5 text-right">
                                        <span class="text-red-600 font-semibold">$<?= number_format($r->fletes_total, 0, ',', '.') ?></span>
                                        <?php if ($r->fletes_count): ?>
                                        <span class="block text-xxs text-gray-400"><?= $r->fletes_count ?> guías</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <span class="text-green-600 font-semibold">$<?= number_format($r->cobrado_total, 0, ',', '.') ?></span>
                                        <?php if ($r->cobrado_count): ?>
                                        <span class="block text-xxs text-gray-400"><?= $r->cobrado_count ?> guías</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-blue-600 font-semibold">
                                        $<?= number_format($r->pagos_recibidos, 0, ',', '.') ?>
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <span class="text-lg font-bold <?= $r->saldo_neto >= 0 ? 'text-green-700' : 'text-red-700' ?>">
                                            $<?= number_format(abs($r->saldo_neto), 0, ',', '.') ?>
                                        </span>
                                        <span class="block text-xxs text-gray-500">
                                            <?= $r->saldo_neto >= 0 ? 'A favor Ledxury' : 'A pagar' ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <!-- Pagos sin match -->
                    <?php if (!empty($pendientes_payments)): ?>
                    <div class="bg-white rounded-lg border overflow-hidden mb-5">
                        <div class="px-4 py-3 border-b bg-yellow-50">
                            <h3 class="text-sm font-bold text-yellow-800">Guías en pagos sin match (<?= count($pendientes_payments) ?>)</h3>
                            <p class="text-xs text-yellow-600 mt-0.5">Posiblemente guías de MAM. Marca cuáles son para incluir en el cálculo.</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D;">
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-white uppercase">Guía</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-white uppercase">Destinatario</th>
                                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-white uppercase">Valor</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-white uppercase">Pago</th>
                                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-white uppercase">Empresa</th>
                                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-white uppercase">Factura</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($pendientes_payments as $p): $i++; ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?>">
                                        <td class="px-4 py-2 font-mono"><?= $p->numeroGuia ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($p->nombreDestinatario) ?></td>
                                        <td class="px-4 py-2 text-right font-bold text-green-600">$<?= number_format($p->valorTotal, 0, ',', '.') ?></td>
                                        <td class="px-4 py-2"><a href="<?= base_url() ?>sisvent/admin/contrapagos/view/<?= $p->batch_id ?>" class="text-mam-blue-petroleo hover:underline">Pago #<?= $p->batch_id ?></a></td>
                                        <td class="px-4 py-2 text-center">
                                            <select onchange="markCompany('payment', <?= $p->id ?>, this.value, this)" class="text-xs border border-gray-200 rounded px-2 py-1">
                                                <option value="">—</option>
                                                <option value="mam" <?= $p->company === 'mam' ? 'selected' : '' ?>>Es de MAM</option>
                                                <option value="mam_online" <?= $p->company === 'mam_online' ? 'selected' : '' ?>>Es de MAM Online</option>
                                                <option value="ledxury" <?= $p->company === 'ledxury' ? 'selected' : '' ?>>Es de Ledxury</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <?php if (!empty($p->invoice_id)): ?>
                                            <a href="<?= base_url() ?>sisvent/commercial/invoices/view/<?= (int)$p->invoice_id ?>" class="text-mam-blue-petroleo hover:underline font-bold">#<?= (int)$p->invoice_id ?></a>
                                            <?php else: ?>
                                            <button type="button" class="link-invoice-btn text-xs px-2 py-1 rounded border border-blue-200 text-blue-700 bg-blue-50 hover:bg-blue-100"
                                                    data-payment-id="<?= $p->id ?>" data-guia="<?= htmlspecialchars($p->numeroGuia) ?>"
                                                    data-dest="<?= htmlspecialchars($p->nombreDestinatario) ?>">
                                                Vincular factura
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Invoice items sin match -->
                    <?php if (!empty($pendientes_invoices)): ?>
                    <div class="bg-white rounded-lg border overflow-hidden mb-5">
                        <div class="px-4 py-3 border-b bg-yellow-50">
                            <h3 class="text-sm font-bold text-yellow-800">Guías en facturas Interrapidísimo sin match (<?= count($pendientes_invoices) ?>)</h3>
                            <p class="text-xs text-yellow-600 mt-0.5">Fletes que Ledxury pagó pero la guía no está en el sistema. Posiblemente de MAM.</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D;">
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-white uppercase">Guía</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-white uppercase">Destino</th>
                                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-white uppercase">Flete</th>
                                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-white uppercase">Total</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-white uppercase">Factura</th>
                                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-white uppercase">Empresa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($pendientes_invoices as $it): $i++; ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?>">
                                        <td class="px-4 py-2 font-mono"><?= $it->numero_guia ?></td>
                                        <td class="px-4 py-2 text-gray-600"><?= htmlspecialchars($it->ciudad_destino) ?></td>
                                        <td class="px-4 py-2 text-right text-red-600">$<?= number_format($it->valor_transporte, 0, ',', '.') ?></td>
                                        <td class="px-4 py-2 text-right font-bold">$<?= number_format($it->valor_total, 0, ',', '.') ?></td>
                                        <td class="px-4 py-2"><a href="<?= base_url() ?>sisvent/admin/contrapagos/invoiceDetail/<?= $it->invoice_id ?>" class="text-mam-blue-petroleo hover:underline">#<?= $it->numero_factura ?></a></td>
                                        <td class="px-4 py-2 text-center">
                                            <select onchange="markCompany('invoice_item', <?= $it->id ?>, this.value, this)" class="text-xs border border-gray-200 rounded px-2 py-1">
                                                <option value="">—</option>
                                                <option value="mam" <?= $it->company === 'mam' ? 'selected' : '' ?>>Es de MAM</option>
                                                <option value="mam_online" <?= $it->company === 'mam_online' ? 'selected' : '' ?>>Es de MAM Online</option>
                                                <option value="ledxury" <?= $it->company === 'ledxury' ? 'selected' : '' ?>>Es de Ledxury</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($pendientes_payments) && empty($pendientes_invoices)): ?>
                    <div class="bg-white rounded-lg border p-8 text-center text-gray-400">
                        <svg class="w-10 h-10 mx-auto text-green-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Todas las guías están asignadas
                    </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <!-- Modal: vincular factura a pago sin match -->
    <div id="link-modal" class="fixed inset-0 z-50" style="display:none; background:rgba(0,0,0,0.4);">
        <div class="bg-white rounded-lg shadow-xl mx-auto mt-24" style="max-width:480px;">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-700">Vincular factura de Ledxury</h3>
                <button type="button" id="link-close" class="text-gray-400 hover:text-gray-600 text-lg leading-none">&times;</button>
            </div>
            <div class="p-4">
                <p class="text-xs text-gray-500 mb-2">
                    Guía <span id="link-guia" class="font-mono font-bold"></span> ·
                    <span id="link-dest"></span>
                </p>
                <input type="text" id="link-search" placeholder="Buscar por # factura, cliente o valor..."
                       class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-400" autocomplete="off">
                <div id="link-results" class="border border-gray-200 rounded mt-1 max-h-56 overflow-y-auto" style="display:none;"></div>
            </div>
            <div class="px-4 py-3 border-t flex justify-end gap-2">
                <button type="button" id="link-cancel" class="text-xs px-3 py-1.5 rounded border border-gray-300 text-gray-600">Cancelar</button>
                <button type="button" id="link-confirm" disabled class="text-xs px-3 py-1.5 rounded bg-mam-blue-petroleo text-white disabled:opacity-40">Vincular</button>
            </div>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    function markCompany(table, id, company, el) {
        if (!company) return;
        $(el).prop('disabled', true);
        $.ajax({
            url: '<?= base_url() ?>sisvent/admin/contrapagos/markCompany',
            type: 'POST',
            data: {
                '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>',
                table: table, id: id, company: company
            },
            dataType: 'json',
            success: function(r) {
                $(el).prop('disabled', false);
                if (r.success) {
                    $(el).css('border-color', '#10B981').css('background', '#D1FAE5');
                    setTimeout(function() { location.reload(); }, 600);
                } else {
                    alert(r.message || 'Error');
                }
            },
            error: function() {
                $(el).prop('disabled', false);
                alert('Error de conexion');
            }
        });
    }

    // ===== Vincular factura a pago sin match =====
    $(document).on('click', '.link-invoice-btn', function() {
        var $b = $(this);
        $('#link-modal').data('payment-id', $b.data('payment-id')).show();
        $('#link-guia').text($b.data('guia'));
        $('#link-dest').text($b.data('dest'));
        $('#link-search').val('').focus();
        $('#link-results').hide().empty();
        $('#link-confirm').prop('disabled', true).removeData('invoice-id');
    });

    $(document).on('click', '#link-close, #link-cancel', function() { $('#link-modal').hide(); });
    $(document).on('click', '#link-modal', function(e) { if (e.target === this) $(this).hide(); });

    var linkTimer = null;
    $(document).on('input', '#link-search', function() {
        var q = $(this).val().trim();
        if (linkTimer) clearTimeout(linkTimer);
        if (q.length < 2) { $('#link-results').hide(); return; }
        linkTimer = setTimeout(function() {
            $.getJSON('<?= base_url() ?>sisvent/admin/contrapagos/searchInvoiceForMatch?q=' + encodeURIComponent(q), function(rows) {
                var $r = $('#link-results').empty();
                if (!rows.length) { $r.html('<div class="px-3 py-2 text-xs text-gray-400">Sin resultados</div>').show(); return; }
                rows.forEach(function(row) {
                    $r.append($('<div>')
                        .addClass('link-result-row px-3 py-2 cursor-pointer hover:bg-blue-50 text-xs')
                        .attr('data-invoice-id', row.id)
                        .html('<div class="font-medium text-gray-700">' + row.label + '</div><div class="text-xxs text-gray-400 mt-0.5">' + row.meta + '</div>'));
                });
                $r.show();
            });
        }, 250);
    });

    $(document).on('click', '.link-result-row', function() {
        $('.link-result-row').removeClass('bg-blue-100');
        $(this).addClass('bg-blue-100');
        $('#link-confirm').prop('disabled', false).data('invoice-id', $(this).data('invoice-id'));
    });

    $(document).on('click', '#link-confirm', function() {
        var invoiceId = $(this).data('invoice-id');
        var paymentId = $('#link-modal').data('payment-id');
        if (!invoiceId) { alert('Selecciona primero una factura de la lista.'); return; }
        if (!paymentId) { alert('No se identificó la guía. Cierra y vuelve a abrir.'); return; }
        var $btn = $(this).prop('disabled', true);
        var data = { payment_id: paymentId, invoice_id: invoiceId };
        data['<?= $this->security->get_csrf_token_name() ?>'] = '<?= $this->security->get_csrf_hash() ?>';
        $.post('<?= base_url() ?>sisvent/admin/contrapagos/linkPaymentInvoice', data, function(r) {
            if (r.success) {
                $('#link-modal').hide();
                location.reload();
            } else {
                alert(r.message || 'Error al vincular');
                $btn.prop('disabled', false);
            }
        }, 'json').fail(function() {
            alert('Error de conexion');
            $btn.prop('disabled', false);
        });
    });
    </script>
</body>
</html>
