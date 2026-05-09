<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$stClass = 'bg-gray-100 text-gray-500';
$stLabel = ucfirst($invoice->status);
if ($invoice->status === 'pendiente') { $stClass = 'bg-yellow-100 text-yellow-700'; $stLabel = 'Pendiente'; }
elseif ($invoice->status === 'descontada') { $stClass = 'bg-green-100 text-green-700'; $stLabel = 'Descontada'; }
elseif ($invoice->status === 'pagada') { $stClass = 'bg-blue-100 text-blue-700'; $stLabel = 'Pagada'; }

$companyMeta = [
    'ledxury'    => ['label' => 'Match Ledxury', 'badge' => 'bg-green-100 text-green-700',  'dot' => '#22C55E'],
    'mam'        => ['label' => 'MAM',           'badge' => 'bg-purple-100 text-purple-700', 'dot' => '#A855F7'],
    'no_invoice' => ['label' => 'Sin factura',   'badge' => 'bg-amber-100 text-amber-700',   'dot' => '#F59E0B'],
    'disputa'    => ['label' => 'Disputa',       'badge' => 'bg-red-100 text-red-700',       'dot' => '#DC2626'],
    'sin_revisar'=> ['label' => 'Sin revisar',   'badge' => 'bg-gray-200 text-gray-600',     'dot' => '#9CA3AF'],
];

$totalItems = count($items);
$pendientesRev = $kpi_counts['sin_revisar'] ?? 0;
$pctMatched = $totalItems > 0 ? round((($totalItems - $pendientesRev) / $totalItems) * 100, 1) : 0;
$csrfName = $this->security->get_csrf_token_name();
$csrfHash = $this->security->get_csrf_hash();
?>
<!DOCTYPE html>
<html lang="es">
    <title>Factura Inter #<?= $invoice->numero_factura ?> - Ledxury</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/contrapagos/index', 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 py-5 w-full max-w-screen-xl mx-auto">

                    <!-- Header -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-5">
                        <div class="flex items-center gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-700">Factura Inter #<?= $invoice->numero_factura ?></h2>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Fecha: <?= $invoice->fecha_corte ? date('d/m/Y', strtotime($invoice->fecha_corte)) : '-' ?>
                                    &middot; <?= htmlspecialchars($invoice->razon_social) ?>
                                    &middot; NIT <?= $invoice->nit ?>
                                </p>
                            </div>
                            <span class="px-2.5 py-0.5 text-xs font-bold rounded-full <?= $stClass ?>"><?= $stLabel ?></span>
                        </div>
                        <div class="flex items-center gap-2 mt-3 lg:mt-0">
                            <?php if ($batch): ?>
                            <a href="<?= base_url() ?>sisvent/admin/contrapagos/view/<?= $batch->id ?>"
                               class="px-4 py-2 text-xs font-medium text-mam-blue-petroleo hover:text-white hover:bg-mam-blue-petroleo border border-mam-blue-petroleo rounded-lg transition-colors">Ver Pago #<?= $batch->id ?></a>
                            <?php endif; ?>
                            <a href="<?= base_url() ?>sisvent/admin/contrapagos/invoices" class="px-4 py-2 text-xs text-gray-500 hover:text-gray-700">&larr; Volver</a>
                        </div>
                    </div>

                    <?php if (!empty($invoice_payments)): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-5">
                        <h4 class="text-sm font-bold text-blue-800 uppercase tracking-wide mb-2">Pagos / Compensaciones de esta factura</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-blue-100 text-blue-900">
                                        <th class="px-3 py-1.5 text-left">Lote</th>
                                        <th class="px-3 py-1.5 text-left">Hoja</th>
                                        <th class="px-3 py-1.5 text-left">Fecha pago</th>
                                        <th class="px-3 py-1.5 text-right">Monto compensado</th>
                                        <th class="px-3 py-1.5 text-left">Observación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoice_payments as $ip): ?>
                                    <tr class="border-t border-blue-100 bg-white">
                                        <td class="px-3 py-1.5"><a href="<?= base_url() ?>sisvent/admin/contrapagos/view/<?= $ip->batch_id ?>" class="text-blue-700 font-bold hover:underline">#<?= $ip->batch_id ?></a></td>
                                        <td class="px-3 py-1.5"><?= htmlspecialchars($ip->sheet_name) ?></td>
                                        <td class="px-3 py-1.5"><?= $ip->fecha_pago ? date('d/m/Y', strtotime($ip->fecha_pago)) : '-' ?></td>
                                        <td class="px-3 py-1.5 text-right font-bold text-green-700">$<?= number_format($ip->monto_cobrado, 0, ',', '.') ?></td>
                                        <td class="px-3 py-1.5 text-gray-600 text-xs"><?= htmlspecialchars($ip->texto_observacion) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-blue-100 font-bold text-blue-900 border-t-2"><td colspan="3" class="px-3 py-1.5 text-right uppercase">Total cobrado:</td><td class="px-3 py-1.5 text-right">$<?= number_format($total_cobrado, 0, ',', '.') ?></td><td></td></tr>
                                    <?php if ($saldo_pendiente > 0): ?>
                                    <tr class="bg-yellow-50 font-bold text-yellow-900 border-t"><td colspan="3" class="px-3 py-1.5 text-right uppercase">Saldo pendiente por compensar:</td><td class="px-3 py-1.5 text-right">$<?= number_format($saldo_pendiente, 0, ',', '.') ?></td><td></td></tr>
                                    <?php endif; ?>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <?php elseif ($invoice->descuento_observacion): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-5">
                        <p class="text-sm text-green-800"><span class="font-bold">Descontada en pago:</span> <?= htmlspecialchars($invoice->descuento_observacion) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- KPIs valores -->
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                        <div class="bg-white rounded-lg border p-4"><p class="text-xs text-gray-400 uppercase tracking-wide">Guias</p><p class="text-xl font-bold text-gray-700 mt-1"><?= $invoice->total_guias ?></p></div>
                        <div class="bg-white rounded-lg border p-4"><p class="text-xs text-gray-400 uppercase tracking-wide">Transporte</p><p class="text-xl font-bold text-gray-700 mt-1">$<?= number_format($invoice->valor_transporte, 0, ',', '.') ?></p></div>
                        <div class="bg-white rounded-lg border p-4"><p class="text-xs text-gray-400 uppercase tracking-wide">Seguro</p><p class="text-xl font-bold text-gray-700 mt-1">$<?= number_format($invoice->valor_seguro, 0, ',', '.') ?></p></div>
                        <div class="bg-white rounded-lg border p-4"><p class="text-xs text-gray-400 uppercase tracking-wide">Adicionales</p><p class="text-xl font-bold text-gray-700 mt-1">$<?= number_format($invoice->valor_adicionales, 0, ',', '.') ?></p></div>
                        <div class="bg-white rounded-lg border p-4"><p class="text-xs text-gray-400 uppercase tracking-wide">TOTAL</p><p class="text-xl font-bold text-red-600 mt-1">$<?= number_format($invoice->valor_total, 0, ',', '.') ?></p></div>
                    </div>

                    <!-- Panel de revisión: KPIs por estado + filtros -->
                    <div class="bg-white rounded-lg border p-4 mb-5">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Estado de revisión</h4>
                                <p class="text-xs text-gray-500 mt-0.5"><?= ($totalItems - $pendientesRev) ?> / <?= $totalItems ?> items revisados (<?= $pctMatched ?>%)</p>
                            </div>
                            <?php if ($pendientesRev > 0): ?>
                            <span class="px-3 py-1 text-xs font-bold bg-amber-100 text-amber-700 rounded-full">⚠ <?= $pendientesRev ?> sin revisar</span>
                            <?php else: ?>
                            <span class="px-3 py-1 text-xs font-bold bg-green-100 text-green-700 rounded-full">✓ Todo revisado</span>
                            <?php endif; ?>
                        </div>

                        <!-- Bar visual de progreso -->
                        <div class="flex h-2 w-full rounded-full overflow-hidden mb-3 bg-gray-100">
                            <?php foreach (['ledxury','mam','no_invoice','disputa','sin_revisar'] as $bk):
                                $cnt = $kpi_counts[$bk] ?? 0;
                                if ($cnt === 0) continue;
                                $pct = $totalItems > 0 ? ($cnt / $totalItems) * 100 : 0;
                            ?>
                            <div title="<?= $companyMeta[$bk]['label'] ?>: <?= $cnt ?>" style="width:<?= $pct ?>%;background:<?= $companyMeta[$bk]['dot'] ?>;"></div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Filtros / chips de estado -->
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" class="filter-chip px-3 py-1.5 text-xs font-medium rounded-full border bg-mam-blue-petroleo text-white border-mam-blue-petroleo" data-filter="all">
                                Todas (<?= $totalItems ?>)
                            </button>
                            <?php foreach (['sin_revisar','ledxury','mam','no_invoice','disputa'] as $bk):
                                $cnt = $kpi_counts[$bk] ?? 0;
                                if ($cnt === 0 && $bk !== 'sin_revisar') continue;
                                $valor = $kpi_valor[$bk] ?? 0;
                            ?>
                            <button type="button" class="filter-chip px-3 py-1.5 text-xs font-medium rounded-full border <?= $companyMeta[$bk]['badge'] ?> border-transparent hover:border-gray-400" data-filter="<?= $bk ?>">
                                <span class="inline-block w-2 h-2 rounded-full mr-1" style="background:<?= $companyMeta[$bk]['dot'] ?>;"></span>
                                <?= $companyMeta[$bk]['label'] ?> (<?= $cnt ?>)
                                <?php if ($valor > 0): ?>
                                <span class="text-xxs opacity-70 ml-1">· $<?= number_format($valor, 0, ',', '.') ?></span>
                                <?php endif; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="bg-white rounded-lg border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs" id="items-table">
                                <thead>
                                    <tr style="background:#1B365D;">
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase tracking-wide">#</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase tracking-wide">Guía</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase tracking-wide">Destino</th>
                                        <th class="px-3 py-2.5 text-right text-xs font-semibold text-white uppercase tracking-wide">V.Comercial</th>
                                        <th class="px-3 py-2.5 text-right text-xs font-semibold text-white uppercase tracking-wide">Flete</th>
                                        <th class="px-3 py-2.5 text-right text-xs font-semibold text-white uppercase tracking-wide">Seguro</th>
                                        <th class="px-3 py-2.5 text-right text-xs font-semibold text-white uppercase tracking-wide">Total</th>
                                        <th class="px-3 py-2.5 text-center text-xs font-semibold text-white uppercase tracking-wide">Estado</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase tracking-wide">Cliente / Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($items as $it): $i++;
                                        // El controller anotó $it->_bucket con la clasificación correcta
                                        // (toma en cuenta el caso company='ledxury' default sin match real).
                                        $bucket = $it->_bucket ?? (!empty($it->company) ? $it->company : 'sin_revisar');
                                        $meta = $companyMeta[$bucket] ?? $companyMeta['sin_revisar'];
                                    ?>
                                    <tr class="item-row border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50" data-item-id="<?= $it->id ?>" data-bucket="<?= $bucket ?>">
                                        <td class="px-3 py-2 text-gray-400"><?= $i ?></td>
                                        <td class="px-3 py-2 font-mono font-medium text-gray-700"><?= $it->numero_guia ?></td>
                                        <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($it->ciudad_destino) ?></td>
                                        <td class="px-3 py-2 text-right text-gray-600">$<?= number_format($it->valor_comercial, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right font-medium text-red-600">$<?= number_format($it->valor_transporte, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right text-gray-500">$<?= number_format($it->valor_prima, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-right font-bold text-gray-800">$<?= number_format($it->valor_total, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-center status-cell">
                                            <span class="px-2 py-0.5 text-xxs font-bold rounded-full <?= $meta['badge'] ?>"><?= $meta['label'] ?></span>
                                            <?php if (!empty($it->invoice_system_id) || !empty($it->sys_invoice_id)):
                                                $sysId = $it->invoice_system_id ?: $it->sys_invoice_id;
                                            ?>
                                                <a href="<?= base_url() ?>sisvent/commercial/invoices/view/<?= $sysId ?>" class="block text-xxs text-mam-blue-petroleo hover:underline mt-1">→ #<?= $sysId ?></a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-gray-500">
                                            <?php if (!empty($it->client_name)): ?>
                                                <div class="text-gray-700"><?= htmlspecialchars($it->client_name) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($it->notes)): ?>
                                                <div class="text-xxs text-gray-400 italic mt-0.5"><?= htmlspecialchars($it->notes) ?></div>
                                            <?php endif; ?>
                                            <div class="action-buttons mt-1 flex flex-wrap gap-1">
                                                <button type="button" class="btn-action px-2 py-0.5 text-xxs bg-green-50 text-green-700 hover:bg-green-100 border border-green-200 rounded"
                                                    data-action="match"
                                                    data-item-id="<?= $it->id ?>"
                                                    data-guia="<?= htmlspecialchars($it->numero_guia) ?>"
                                                    data-valor="<?= (float)$it->valor_comercial ?>">🔍 Match manual</button>
                                                <button type="button" class="btn-action px-2 py-0.5 text-xxs bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 rounded" data-action="mark" data-company="mam" data-item-id="<?= $it->id ?>">🏢 MAM</button>
                                                <button type="button" class="btn-action px-2 py-0.5 text-xxs bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 rounded" data-action="mark" data-company="no_invoice" data-item-id="<?= $it->id ?>">📦 Sin factura</button>
                                                <button type="button" class="btn-action px-2 py-0.5 text-xxs bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 rounded" data-action="mark" data-company="disputa" data-item-id="<?= $it->id ?>">⚠ Disputa</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-100 border-t-2 border-gray-300">
                                        <td colspan="4" class="px-3 py-2.5 text-right font-bold text-gray-500 uppercase">Total</td>
                                        <td class="px-3 py-2.5 text-right font-bold text-red-700">$<?= number_format($invoice->valor_transporte, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right font-bold text-gray-700">$<?= number_format($invoice->valor_seguro, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2.5 text-right font-bold text-red-700">$<?= number_format($invoice->valor_total, 0, ',', '.') ?></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- ==================== Modal Match Manual ==================== -->
    <div id="match-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center" style="display:none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
            <div class="px-5 py-3 border-b flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-700">🔍 Match manual a factura del sistema</h3>
                <button type="button" id="match-close" class="text-gray-400 hover:text-gray-700">✕</button>
            </div>
            <div class="px-5 py-4">
                <p class="text-xs text-gray-500 mb-3">Guía Inter: <span id="match-guia-label" class="font-mono font-bold text-gray-800"></span></p>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Buscar factura</label>
                <input type="text" id="match-search" placeholder="# factura, nombre cliente, ciudad o monto..." class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-400" autofocus>
                <div id="match-results" class="mt-2 max-h-72 overflow-y-auto border rounded divide-y" style="display:none;"></div>
                <label class="block text-xs font-bold text-gray-600 uppercase mt-3 mb-1">Notas (opcional)</label>
                <input type="text" id="match-notes" placeholder="ej: cliente cambió de razón social" class="w-full px-3 py-2 text-sm border rounded-lg">
            </div>
            <div class="px-5 py-3 border-t bg-gray-50 flex items-center justify-end gap-2">
                <button type="button" id="match-cancel" class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="button" id="match-confirm" class="px-4 py-1.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg disabled:opacity-50" disabled>Confirmar match</button>
            </div>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
$(document).ready(function() {
    var CSRF_NAME = '<?= $csrfName ?>';
    var CSRF_HASH = '<?= $csrfHash ?>';
    var BASE = '<?= base_url() ?>';

    // ===== Filtros por chip =====
    $(document).on('click', '.filter-chip', function() {
        var filter = $(this).data('filter');
        $('.filter-chip').removeClass('bg-mam-blue-petroleo text-white border-mam-blue-petroleo').addClass('border-transparent hover:border-gray-400');
        $(this).removeClass('border-transparent hover:border-gray-400').addClass('bg-mam-blue-petroleo text-white border-mam-blue-petroleo');
        $('.item-row').each(function() {
            if (filter === 'all' || $(this).data('bucket') === filter) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // ===== Acciones por fila =====
    $(document).on('click', '.btn-action', function() {
        var $btn = $(this);
        var action = $btn.data('action');
        var itemId = $btn.data('item-id');

        if (action === 'match') {
            // Abrir modal
            $('#match-modal').data('item-id', itemId);
            $('#match-guia-label').text($btn.data('guia'));
            $('#match-search').val('');
            $('#match-notes').val('');
            $('#match-results').empty().hide();
            $('#match-confirm').prop('disabled', true).removeData('invoice-id');
            $('#match-modal').css('display','flex');
            setTimeout(function() { $('#match-search').focus(); }, 100);
            return;
        }

        if (action === 'mark') {
            var company = $btn.data('company');
            var label = $btn.text().trim();
            if (!confirm('¿Marcar esta guía como "' + label + '"?')) return;
            postMark(itemId, company, '', null, $btn.closest('tr'));
        }
    });

    // ===== Modal: search facturas (debounced) =====
    var searchTimer = null;
    $(document).on('input', '#match-search', function() {
        var q = $(this).val().trim();
        if (searchTimer) clearTimeout(searchTimer);
        if (q.length < 2) { $('#match-results').hide(); return; }
        searchTimer = setTimeout(function() {
            $.getJSON(BASE + 'sisvent/admin/contrapagos/searchInvoiceForMatch?q=' + encodeURIComponent(q), function(rows) {
                var $r = $('#match-results').empty();
                if (!rows.length) { $r.html('<div class="px-3 py-2 text-xs text-gray-400">Sin resultados</div>').show(); return; }
                rows.forEach(function(row) {
                    var $item = $('<div>')
                        .addClass('match-result-row px-3 py-2 cursor-pointer hover:bg-blue-50 text-xs')
                        .attr('data-invoice-id', row.id)
                        .html('<div class="font-medium text-gray-700">' + row.label + '</div><div class="text-xxs text-gray-400 mt-0.5">' + row.meta + '</div>');
                    $r.append($item);
                });
                $r.show();
            });
        }, 250);
    });

    $(document).on('click', '.match-result-row', function() {
        $('.match-result-row').removeClass('bg-blue-100');
        $(this).addClass('bg-blue-100');
        $('#match-confirm').prop('disabled', false).data('invoice-id', $(this).data('invoice-id'));
    });

    $(document).on('click', '#match-close, #match-cancel', function() { $('#match-modal').hide(); });
    $(document).on('click', '#match-modal', function(e) { if (e.target === this) $(this).hide(); });

    $(document).on('click', '#match-confirm', function() {
        var invoiceId = $(this).data('invoice-id');
        var itemId = $('#match-modal').data('item-id');
        var notes = $('#match-notes').val();
        if (!invoiceId || !itemId) return;
        var $row = $('tr[data-item-id="' + itemId + '"]');
        postMark(itemId, 'ledxury', notes, invoiceId, $row);
        $('#match-modal').hide();
    });

    // ===== POST a markInvoiceItem =====
    function postMark(itemId, company, notes, invoiceSysId, $row) {
        var data = { item_id: itemId, company: company, notes: notes || '' };
        if (invoiceSysId) data.invoice_system_id = invoiceSysId;
        data[CSRF_NAME] = CSRF_HASH;
        $.post(BASE + 'sisvent/admin/contrapagos/markInvoiceItem', data, function(r) {
            if (!r.success) { alert(r.message || 'Error al guardar'); return; }
            // Reload page para reflejar cambios en KPIs (más simple que update parcial)
            location.reload();
        }, 'json').fail(function() { alert('Error de red'); });
    }
});
</script>
</body>
</html>
