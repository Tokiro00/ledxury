<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
$csrfName = $this->security->get_csrf_token_name();
$csrfHash = $this->security->get_csrf_hash();
$totalRows = count($rows ?? []);
$sinCostoCount = 0;
$totalEstimado = 0;
$totalVendidos = 0;
$totalDevoluciones = 0;
$totalAFacturar = 0;
foreach ($rows as $r) {
    if ((float)$r->costo_actual <= 0) $sinCostoCount++;
    $totalEstimado += (int)$r->unidades * (float)$r->costo_actual;
    $totalVendidos     += (int)$r->vendidos;
    $totalDevoluciones += (int)$r->devoluciones;
    $totalAFacturar    += (int)$r->unidades;
}
?>
<!DOCTYPE html>
<html lang="es">
<title>Cierre Compra MAM</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/accountspayable/list', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-4 mx-auto max-w-screen-xl">

                <!-- Header -->
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-xxs text-gray-400 uppercase tracking-wider">Cuentas por Pagar</p>
                        <h2 class="text-2xl font-bold text-gray-800">Cierre Compra MAM</h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Consolidar productos vendidos en Ledxury · MAM nos factura el costo.
                        </p>
                    </div>
                    <a href="<?= base_url('sisvent/admin/accountspayable') ?>" class="px-3 py-1.5 text-xs text-gray-600 border rounded hover:bg-gray-50">← Volver</a>
                </div>

                <!-- Últimas compras MAM (historial) -->
                <?php if (!empty($prevPurchases)): ?>
                <div class="bg-white border rounded-lg p-3 mb-3">
                    <div class="flex items-center mb-2">
                        <div class="text-xs font-bold text-gray-500 uppercase">Últimas compras a MAM</div>
                        <a href="<?= base_url('sisvent/admin/accountspayable') ?>?provider=12" class="ml-auto text-xxs text-mam-blue-petroleo hover:underline">Ver todas →</a>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($prevPurchases as $p):
                            $statusCls = $p->status === 'pagado' ? 'bg-green-100 text-green-700' : ($p->status === 'parcial' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700');
                        ?>
                        <a href="<?= base_url('sisvent/admin/accountspayable/view/' . $p->idSupplierInvoice) ?>"
                           class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-50 border rounded hover:bg-gray-100">
                            <span class="font-mono font-bold text-xxs text-gray-700"><?= htmlspecialchars($p->invoiceNumber) ?></span>
                            <span class="text-xxs text-gray-500"><?= date('d/m/Y', strtotime($p->invoiceDate)) ?></span>
                            <span class="text-xxs font-bold text-green-700">$<?= number_format($p->total, 0, ',', '.') ?></span>
                            <span class="px-1.5 py-0.5 <?= $statusCls ?> rounded text-xxs"><?= htmlspecialchars($p->status) ?></span>
                            <?php if ($p->balance > 0): ?>
                            <span class="text-xxs text-red-600">saldo $<?= number_format($p->balance, 0, ',', '.') ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Filtros de fecha -->
                <form method="GET" action="<?= base_url('sisvent/admin/accountspayable/closeCycleMam') ?>" class="bg-white border rounded-lg p-3 mb-3 flex items-center gap-3 flex-wrap">
                    <span class="text-xxs font-bold text-gray-500 uppercase">Rango:</span>
                    <label class="flex items-center gap-1 text-xs text-gray-600">Desde
                        <input type="date" name="from" value="<?= htmlspecialchars($fromIn ?: date('Y-m-d', strtotime($sinceDt))) ?>" class="px-2 py-1 border rounded text-xs">
                    </label>
                    <label class="flex items-center gap-1 text-xs text-gray-600">Hasta
                        <input type="date" name="to" value="<?= htmlspecialchars($toIn ?: date('Y-m-d', strtotime($untilDt))) ?>" class="px-2 py-1 border rounded text-xs">
                    </label>
                    <button type="submit" class="px-3 py-1.5 text-xs font-bold text-white bg-mam-blue-petroleo hover:bg-blue-900 rounded">Aplicar</button>
                    <?php
                        $base = base_url('sisvent/admin/accountspayable/closeCycleMam');
                        $today = date('Y-m-d');
                    ?>
                    <a href="<?= $base ?>?from=<?= date('Y-m-01') ?>&to=<?= $today ?>" class="text-xxs text-gray-500 hover:text-gray-700">Este mes</a>
                    <a href="<?= $base ?>?from=<?= date('Y-m-01', strtotime('first day of last month')) ?>&to=<?= date('Y-m-t', strtotime('last day of last month')) ?>" class="text-xxs text-gray-500 hover:text-gray-700">Mes pasado</a>
                    <a href="<?= $base ?>?from=<?= date('Y-01-01') ?>&to=<?= $today ?>" class="text-xxs text-gray-500 hover:text-gray-700">Este año</a>
                    <?php if ($fromIn || $toIn): ?>
                    <a href="<?= $base ?>" class="text-xxs text-mam-blue-petroleo font-bold hover:underline">Reset (último cierre)</a>
                    <?php endif; ?>
                </form>

                <!-- Info ciclo -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                    <div class="bg-white border rounded-lg p-3">
                        <p class="text-xxs text-gray-400 uppercase">Desde</p>
                        <p class="text-sm font-bold text-gray-700 mt-1"><?= htmlspecialchars($sinceDt) ?></p>
                        <p class="text-xxs text-gray-400 mt-1">
                            <?php if ($lastClosure): ?>
                                último cierre: #<?= $lastClosure->idSupplierInvoice ?>
                            <?php elseif ($fromIn): ?>
                                filtro manual
                            <?php else: ?>
                                reset 01/05/2026 (primer cierre)
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="bg-white border rounded-lg p-3">
                        <p class="text-xxs text-gray-400 uppercase">Hasta</p>
                        <p class="text-sm font-bold text-gray-700 mt-1"><?= htmlspecialchars($untilDt) ?></p>
                        <p class="text-xxs text-gray-400 mt-1"><?= $toIn ? 'filtro manual' : 'ahora' ?></p>
                    </div>
                    <div class="bg-white border rounded-lg p-3">
                        <p class="text-xxs text-gray-400 uppercase">SKUs a facturar</p>
                        <p class="text-2xl font-bold text-blue-700 mt-1"><?= number_format($totalRows, 0, ',', '.') ?></p>
                        <p class="text-xxs text-gray-500 mt-1">
                            <?= number_format($totalVendidos, 0, ',', '.') ?> vend ·
                            <span class="text-purple-600"><?= number_format($totalDevoluciones, 0, ',', '.') ?> dev</span> ·
                            <strong class="text-blue-700"><?= number_format($totalAFacturar, 0, ',', '.') ?> a MAM</strong>
                        </p>
                        <?php if ($sinCostoCount > 0): ?>
                        <p class="text-xxs text-red-600 font-bold mt-1">⚠ <?= $sinCostoCount ?> sin costo</p>
                        <?php endif; ?>
                    </div>
                    <div class="bg-white border-2 rounded-lg p-3 <?= $sinCostoCount > 0 ? 'border-yellow-400' : 'border-green-400' ?>">
                        <p class="text-xxs text-gray-400 uppercase">Total compra MAM</p>
                        <p class="text-2xl font-bold text-green-700 mt-1" id="total-display">
                            <?= $fmt($totalEstimado) ?>
                        </p>
                        <p class="text-xxs text-gray-400 mt-1">se actualiza al editar costos</p>
                    </div>
                </div>

                <?php if ($sinCostoCount > 0): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-4 text-sm text-yellow-800">
                    <strong>⚠ Atención:</strong> <?= $sinCostoCount ?> producto(s) sin costo registrado (`products.cost = 0`).
                    Edita el costo en la columna <strong>Costo unitario</strong> de la tabla antes de generar la factura.
                    Los productos con costo 0 NO se incluirán en la factura final.
                </div>
                <?php endif; ?>

                <?php if (empty($rows)): ?>
                <div class="bg-white border rounded-lg p-8 text-center text-gray-400">
                    No hay productos vendidos desde el último cierre. Nada para facturar a MAM.
                </div>
                <?php else: ?>

                <form id="cierre-form" method="POST" action="<?= base_url('sisvent/admin/accountspayable/closeCycleMam') ?>" onsubmit="return false;">
                    <input type="hidden" name="<?= $csrfName ?>" value="<?= $csrfHash ?>">

                    <div class="bg-white border rounded-lg p-3 mb-3 flex items-center gap-3">
                        <label class="text-sm">
                            <span class="font-bold text-gray-700">Bodega destino:</span>
                            <select name="store_id" class="ml-2 px-2 py-1 border rounded text-sm">
                                <?php foreach ($stores as $s): ?>
                                <option value="<?= (int)$s->idStore ?>" <?= (int)$s->idStore === 1 ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s->name) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="text-sm flex-1">
                            <span class="font-bold text-gray-700">Notas:</span>
                            <input type="text" name="notes" placeholder="Notas internas (opcional)" class="ml-2 px-2 py-1 border rounded text-sm w-96">
                        </label>
                        <button type="button" id="btn-confirm" class="px-4 py-2 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded">
                            💾 Generar factura compra MAM
                        </button>
                    </div>

                    <div class="bg-white border rounded-lg overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50">
                                <tr class="text-xxs uppercase text-gray-500 font-bold">
                                    <th class="px-3 py-2 text-left" style="width:32px;"></th>
                                    <th class="px-3 py-2 text-left">SKU</th>
                                    <th class="px-3 py-2 text-left">Descripción</th>
                                    <th class="px-3 py-2 text-right">Vendidos</th>
                                    <th class="px-3 py-2 text-right">− Devol.</th>
                                    <th class="px-3 py-2 text-right" style="width:110px;">= A MAM</th>
                                    <th class="px-3 py-2 text-right" style="width:140px;">Costo unit.</th>
                                    <th class="px-3 py-2 text-right">Subtotal</th>
                                    <th class="px-3 py-2 text-left text-gray-400"># Facts</th>
                                </tr>
                            </thead>
                            <tbody id="items-tbody">
                                <?php foreach ($rows as $i => $r):
                                    $costo = (float)$r->costo_actual;
                                    $unidades = (int)$r->unidades;     // a MAM (neto)
                                    $vendidos = (int)$r->vendidos;
                                    $devoluciones = (int)$r->devoluciones;
                                    $sub = $costo * $unidades;
                                ?>
                                <tr class="border-t hover:bg-gray-50 row-item" data-pid="<?= htmlspecialchars($r->productId) ?>">
                                    <td class="px-2 py-2 text-center">
                                        <button type="button" class="btn-remove-row text-red-400 hover:text-red-600" title="Quitar de la factura">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </td>
                                    <td class="px-3 py-2 font-mono font-bold text-gray-700"><?= htmlspecialchars($r->productId) ?></td>
                                    <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($r->description) ?></td>
                                    <td class="px-3 py-2 text-right text-gray-600"><?= number_format($vendidos, 0, ',', '.') ?></td>
                                    <td class="px-3 py-2 text-right text-purple-600 <?= $devoluciones > 0 ? 'font-bold' : '' ?>">
                                        <?= $devoluciones > 0 ? '−' . number_format($devoluciones, 0, ',', '.') : '0' ?>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <input type="number" class="qty-input border rounded px-2 py-1 text-right text-xs w-20 font-bold text-blue-700"
                                               value="<?= $unidades ?>" min="0" step="1">
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <input type="number" class="cost-input border rounded px-2 py-1 text-right text-xs w-28 <?= $costo <= 0 ? 'border-red-400 bg-red-50' : '' ?>"
                                               value="<?= (int)round($costo) ?>" min="0" step="1">
                                    </td>
                                    <td class="px-3 py-2 text-right font-bold text-gray-700 row-sub">
                                        <?= $fmt($sub) ?>
                                    </td>
                                    <td class="px-3 py-2 text-gray-400 text-xxs">
                                        <?= (int)$r->n_facturas ?> · <?= date('d/m', strtotime($r->primera_venta)) ?>-<?= date('d/m', strtotime($r->ultima_venta)) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-100 font-bold">
                                <tr class="border-t-2">
                                    <td></td>
                                    <td colspan="2" class="px-3 py-2 text-right uppercase text-gray-500">Total</td>
                                    <td class="px-3 py-2 text-right text-gray-700" id="tot-vendidos"><?= number_format($totalVendidos, 0, ',', '.') ?></td>
                                    <td class="px-3 py-2 text-right text-purple-700" id="tot-devol">−<?= number_format($totalDevoluciones, 0, ',', '.') ?></td>
                                    <td class="px-3 py-2 text-right text-blue-700 text-base" id="tot-amam"><?= number_format($totalAFacturar, 0, ',', '.') ?></td>
                                    <td></td>
                                    <td class="px-3 py-2 text-right text-lg text-green-700" id="total-row"><?= $fmt($totalEstimado) ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <p class="text-xxs text-gray-400 mt-2">
                        💡 Puedes <strong>quitar filas</strong> (×) y <strong>ajustar cantidad/costo</strong> antes de generar la factura.
                        Productos no físicos como FLETE deben quitarse.
                    </p>
                </form>

                <?php endif; ?>

            </div>
        </main>
    </div>
</div>

<script>
$(function() {
    var fmtMoney = function(n) { return '$' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
    var fmtInt   = function(n) { return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };

    function recalc() {
        var totalAMam = 0, totalCop = 0, totalVendidos = 0, totalDev = 0, skuCount = 0;
        $('.row-item').each(function() {
            var qty  = parseInt($(this).find('.qty-input').val()) || 0;
            var cost = parseFloat($(this).find('.cost-input').val()) || 0;
            var sub  = qty * cost;
            $(this).find('.row-sub').text(fmtMoney(sub));
            totalAMam += qty;
            totalCop  += sub;
            skuCount++;
            // Las cifras Vendidos/Devol vienen del backend, no se recalculan localmente
            // (son referencia histórica, no entrarán a la factura como tales).
        });
        $('#total-display, #total-row').text(fmtMoney(totalCop));
        $('#tot-amam').text(fmtInt(totalAMam));
    }

    $(document).on('input', '.cost-input, .qty-input', recalc);

    $(document).on('click', '.btn-remove-row', function() {
        var $row = $(this).closest('.row-item');
        var pid = $row.data('pid');
        if (!confirm('Quitar ' + pid + ' de la factura? No afecta los datos de ventas, solo lo excluye de esta factura compra MAM.')) return;
        $row.remove();
        recalc();
    });

    // Click directo en el botón (no submit del form) — más robusto que el submit handler
    $(document).on('click', '#btn-confirm', function(e) {
        e.preventDefault();
        console.log('[cierre-mam] btn-confirm clicked');
        var items = [];
        $('.row-item').each(function() {
            var pid = $(this).data('pid');
            var qty = parseInt($(this).find('.qty-input').val()) || 0;
            var cost = parseFloat($(this).find('.cost-input').val()) || 0;
            if (cost > 0 && qty > 0) {
                items.push({ productId: pid, qty: qty, cost: cost });
            }
        });
        if (items.length === 0) {
            alert('No hay productos con cantidad y costo > 0 para facturar');
            return;
        }
        if (!confirm('Generar factura de compra a MAM por ' + items.length + ' productos? Esto creará un asiento contable.')) return;

        var data = {
            store_id: $('select[name="store_id"]').val(),
            notes: $('input[name="notes"]').val(),
            items: items
        };
        data['<?= $csrfName ?>'] = '<?= $csrfHash ?>';

        $('#btn-confirm').prop('disabled', true).text('Procesando…');
        $.post('<?= base_url('sisvent/admin/accountspayable/closeCycleMam') ?>', data, function(r) {
            console.log('[cierre-mam] response:', r);
            if (r && r.success) {
                alert('✓ Factura ' + r.invoice_number + ' creada por ' + fmtMoney(r.total));
                window.location = r.redirect_url;
            } else {
                alert('Error: ' + (r && r.message ? r.message : 'sin respuesta del servidor'));
                $('#btn-confirm').prop('disabled', false).text('💾 Generar factura compra MAM');
            }
        }, 'json').fail(function(xhr, status, err) {
            console.error('[cierre-mam] AJAX fail', status, err, xhr.responseText);
            alert('Error de red (' + status + '). HTTP ' + xhr.status + ': ' + (xhr.responseText || err).substring(0, 200));
            $('#btn-confirm').prop('disabled', false).text('💾 Generar factura compra MAM');
        });
    });
});
</script>

<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
