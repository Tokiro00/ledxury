<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
$csrfName = $this->security->get_csrf_token_name();
$csrfHash = $this->security->get_csrf_hash();
$totalUnidades = 0;
$totalCostoEstimado = 0;
foreach ($rows as $r) {
    $totalUnidades += (int)$r->stock;
    $totalCostoEstimado += (int)$r->stock * (float)($r->unit_cost ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<title>Devolución a MAM</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/accountspayable/list', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-4 mx-auto max-w-screen-xl">

                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-xxs text-gray-400 uppercase tracking-wider">Cuentas por Pagar</p>
                        <h2 class="text-2xl font-bold text-gray-800">Devolución física a MAM</h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Productos físicamente en bodega Ledxury (devoluciones de clientes) que se entregan a MAM.
                        </p>
                    </div>
                    <a href="<?= base_url('sisvent/admin/accountspayable') ?>" class="px-3 py-1.5 text-xs text-gray-600 border rounded hover:bg-gray-50">← Volver</a>
                </div>

                <!-- Últimas devoluciones (historial) -->
                <?php if (!empty($prevReturns)): ?>
                <div class="bg-white border rounded-lg p-3 mb-3">
                    <div class="flex items-center mb-2">
                        <div class="text-xs font-bold text-gray-500 uppercase">Últimas devoluciones a MAM</div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($prevReturns as $p): ?>
                        <a href="<?= base_url('sisvent/admin/accountspayable/returnPdf/' . $p->id) ?>"
                           class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-50 border rounded hover:bg-gray-100">
                            <span class="font-mono font-bold text-xxs text-gray-700"><?= htmlspecialchars($p->return_code) ?></span>
                            <span class="text-xxs text-gray-500"><?= date('d/m/Y', strtotime($p->return_date)) ?></span>
                            <span class="px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded text-xxs"><?= (int)$p->total_units ?> u</span>
                            <?php if (!empty($p->total_cost) && (float)$p->total_cost > 0): ?>
                            <span class="text-xxs font-bold text-green-700">$<?= number_format($p->total_cost, 0, ',', '.') ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Filtros de fecha -->
                <form method="GET" action="<?= base_url('sisvent/admin/accountspayable/returnToMam') ?>" class="bg-white border rounded-lg p-3 mb-3 flex items-center gap-3 flex-wrap">
                    <span class="text-xxs font-bold text-gray-500 uppercase">Rango:</span>
                    <label class="flex items-center gap-1 text-xs text-gray-600">Desde
                        <input type="date" name="from" value="<?= htmlspecialchars($fromIn ?: date('Y-m-d', strtotime($sinceDt))) ?>" class="px-2 py-1 border rounded text-xs">
                    </label>
                    <label class="flex items-center gap-1 text-xs text-gray-600">Hasta
                        <input type="date" name="to" value="<?= htmlspecialchars($toIn ?: date('Y-m-d', strtotime($untilDt))) ?>" class="px-2 py-1 border rounded text-xs">
                    </label>
                    <button type="submit" class="px-3 py-1.5 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 rounded">Aplicar</button>
                    <?php
                        $base = base_url('sisvent/admin/accountspayable/returnToMam');
                        $today = date('Y-m-d');
                    ?>
                    <a href="<?= $base ?>?from=<?= date('Y-m-01') ?>&to=<?= $today ?>" class="text-xxs text-gray-500 hover:text-gray-700">Este mes</a>
                    <a href="<?= $base ?>?from=<?= date('Y-m-01', strtotime('first day of last month')) ?>&to=<?= date('Y-m-t', strtotime('last day of last month')) ?>" class="text-xxs text-gray-500 hover:text-gray-700">Mes pasado</a>
                    <a href="<?= $base ?>?from=<?= date('Y-01-01') ?>&to=<?= $today ?>" class="text-xxs text-gray-500 hover:text-gray-700">Este año</a>
                    <?php if ($fromIn || $toIn): ?>
                    <a href="<?= $base ?>" class="text-xxs text-purple-700 font-bold hover:underline">Reset (última devolución)</a>
                    <?php endif; ?>
                </form>

                <!-- Info ciclo / Resumen -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                    <div class="bg-white border rounded-lg p-3">
                        <p class="text-xxs text-gray-400 uppercase">Desde</p>
                        <p class="text-sm font-bold text-gray-700 mt-1"><?= htmlspecialchars($sinceDt) ?></p>
                        <p class="text-xxs text-gray-400 mt-1">
                            <?php if ($lastReturn): ?>
                                última devolución: <?= htmlspecialchars($lastReturn->return_code) ?>
                            <?php elseif ($fromIn): ?>
                                filtro manual
                            <?php else: ?>
                                reset 01/05/2026 (primer ciclo)
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="bg-white border rounded-lg p-3">
                        <p class="text-xxs text-gray-400 uppercase">Hasta</p>
                        <p class="text-sm font-bold text-gray-700 mt-1"><?= htmlspecialchars($untilDt) ?></p>
                        <p class="text-xxs text-gray-400 mt-1"><?= $toIn ? 'filtro manual' : 'ahora' ?></p>
                    </div>
                    <div class="bg-white border rounded-lg p-3">
                        <p class="text-xxs text-gray-400 uppercase">SKUs en bodega</p>
                        <p class="text-2xl font-bold text-purple-700 mt-1"><?= count($rows) ?></p>
                        <p class="text-xxs text-gray-500 mt-1">
                            <?= number_format($totalUnidades, 0, ',', '.') ?> unidades disponibles
                        </p>
                    </div>
                    <div class="bg-white border-2 border-blue-400 rounded-lg p-3">
                        <p class="text-xxs text-gray-400 uppercase">A entregar (nota crédito)</p>
                        <p class="text-2xl font-bold text-blue-700 mt-1" id="total-display">0</p>
                        <p class="text-xxs text-gray-400 mt-1">$<span id="total-cost-display">0</span> · se actualiza al editar</p>
                    </div>
                </div>

                <?php if (empty($rows)): ?>
                <div class="bg-white border rounded-lg p-8 text-center text-gray-400">
                    No hay productos con stock positivo en bodega. Nada para devolver a MAM.
                </div>
                <?php else: ?>

                <form id="dev-form" method="POST" action="<?= base_url('sisvent/admin/accountspayable/returnToMam') ?>" onsubmit="return false;">
                    <input type="hidden" name="<?= $csrfName ?>" value="<?= $csrfHash ?>">

                    <div class="bg-white border rounded-lg p-3 mb-3 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        <label class="text-sm">
                            <span class="block font-bold text-gray-700 text-xs">Persona MAM que recibe</span>
                            <input type="text" name="delivered_to" placeholder="Nombre y cédula" class="mt-1 w-full px-2 py-1.5 border rounded text-sm">
                        </label>
                        <label class="text-sm">
                            <span class="block font-bold text-gray-700 text-xs">Notas</span>
                            <input type="text" name="notes" placeholder="Observaciones (opcional)" class="mt-1 w-full px-2 py-1.5 border rounded text-sm">
                        </label>
                        <div class="flex gap-2">
                            <button type="button" id="btn-all" class="px-3 py-2 text-xs text-gray-600 border rounded hover:bg-gray-50">Marcar todo</button>
                            <button type="button" id="btn-none" class="px-3 py-2 text-xs text-gray-600 border rounded hover:bg-gray-50">Limpiar</button>
                            <button type="button" id="btn-confirm" class="flex-1 px-4 py-2 text-sm font-bold text-white bg-purple-600 hover:bg-purple-700 rounded">
                                📦 Registrar entrega a MAM
                            </button>
                        </div>
                    </div>

                    <div class="bg-white border rounded-lg overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50">
                                <tr class="text-xxs uppercase text-gray-500 font-bold">
                                    <th class="px-3 py-2 text-left">SKU</th>
                                    <th class="px-3 py-2 text-left">Descripción</th>
                                    <th class="px-3 py-2 text-right">Bodega</th>
                                    <th class="px-3 py-2 text-right">Stock disponible</th>
                                    <th class="px-3 py-2 text-right" style="width:120px;">Cant. a devolver</th>
                                    <th class="px-3 py-2 text-right">Costo unit.</th>
                                    <th class="px-3 py-2 text-right">Subtotal</th>
                                    <th class="px-3 py-2 text-left text-gray-400">Última act.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r):
                                    $unitCost = (float)($r->unit_cost ?? 0);
                                ?>
                                <tr class="border-t hover:bg-gray-50 row-item"
                                    data-pid="<?= htmlspecialchars($r->idProduct) ?>"
                                    data-store="<?= (int)$r->idStore ?>"
                                    data-max="<?= (int)$r->stock ?>"
                                    data-cost="<?= $unitCost ?>">
                                    <td class="px-3 py-2 font-mono font-bold text-gray-700"><?= htmlspecialchars($r->idProduct) ?></td>
                                    <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($r->description) ?></td>
                                    <td class="px-3 py-2 text-right text-gray-500"><?= (int)$r->idStore ?></td>
                                    <td class="px-3 py-2 text-right font-bold text-purple-700"><?= number_format($r->stock, 0, ',', '.') ?></td>
                                    <td class="px-3 py-2 text-right">
                                        <input type="number" class="qty-input border rounded px-2 py-1 text-right text-xs w-20"
                                               value="0" min="0" max="<?= (int)$r->stock ?>" step="1">
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-600 <?= $unitCost <= 0 ? 'text-red-500 font-bold' : '' ?>">
                                        <?= $unitCost > 0 ? '$' . number_format($unitCost, 0, ',', '.') : '⚠ sin costo' ?>
                                    </td>
                                    <td class="px-3 py-2 text-right font-bold text-gray-700 row-sub">$0</td>
                                    <td class="px-3 py-2 text-gray-400 text-xxs">
                                        <?= !empty($r->last_change) ? date('d/m/Y', strtotime($r->last_change)) : '—' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-100 font-bold">
                                <tr class="border-t-2">
                                    <td colspan="3" class="px-3 py-2 text-right uppercase text-gray-500">Total</td>
                                    <td class="px-3 py-2 text-right text-purple-700"><?= number_format($totalUnidades, 0, ',', '.') ?></td>
                                    <td class="px-3 py-2 text-right text-base text-blue-700" id="total-row">0</td>
                                    <td></td>
                                    <td class="px-3 py-2 text-right text-lg text-green-700" id="total-cost-row">$0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </form>

                <?php endif; ?>

            </div>
        </main>
    </div>
</div>

<script>
$(function() {
    var fmtMoney = function(n) { return '$' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };

    function recalc() {
        var totalUnits = 0, totalCost = 0;
        $('.row-item').each(function() {
            var qty = parseInt($(this).find('.qty-input').val()) || 0;
            var max = parseInt($(this).data('max')) || 0;
            var cost = parseFloat($(this).data('cost')) || 0;
            if (qty > max) { qty = max; $(this).find('.qty-input').val(max); }
            var sub = qty * cost;
            $(this).find('.row-sub').text(fmtMoney(sub));
            totalUnits += qty;
            totalCost += sub;
        });
        $('#total-display, #total-row').text(totalUnits.toLocaleString('es-CO'));
        $('#total-cost-display').text(Math.round(totalCost).toLocaleString('es-CO'));
        $('#total-cost-row').text(fmtMoney(totalCost));
    }
    $(document).on('input', '.qty-input', recalc);
    $(document).on('click', '#btn-all', function(e) {
        e.preventDefault();
        console.log('[return-mam] btn-all clicked, rows:', $('.row-item').length);
        $('.row-item').each(function() {
            var max = parseInt($(this).data('max')) || 0;
            $(this).find('.qty-input').val(max);
        });
        recalc();
    });
    $(document).on('click', '#btn-none', function(e) {
        e.preventDefault();
        $('.qty-input').val(0);
        recalc();
    });

    $(document).on('click', '#btn-confirm', function(e) {
        e.preventDefault();
        var items = [];
        $('.row-item').each(function() {
            var pid = $(this).data('pid');
            var storeId = $(this).data('store');
            var qty = parseInt($(this).find('.qty-input').val()) || 0;
            if (qty > 0) items.push({ productId: pid, qty: qty, storeId: storeId });
        });
        if (items.length === 0) { alert('Marca al menos 1 producto a devolver'); return; }
        if (!confirm('Registrar entrega de ' + items.length + ' SKUs a MAM?\n\nEl stock se descuenta y se genera nota crédito al proveedor (asiento contable inverso).')) return;

        var data = {
            delivered_to: $('input[name="delivered_to"]').val(),
            notes: $('input[name="notes"]').val(),
            items: items
        };
        data['<?= $csrfName ?>'] = '<?= $csrfHash ?>';

        $('#btn-confirm').prop('disabled', true).text('Procesando…');
        $.post('<?= base_url('sisvent/admin/accountspayable/returnToMam') ?>', data, function(r) {
            if (r && r.success) {
                alert('✓ ' + r.return_code + '\n\n' + (r.message || ''));
                window.open(r.pdf_url, '_blank');
                window.location.reload();
            } else {
                alert('Error: ' + (r && r.message ? r.message : 'desconocido'));
                $('#btn-confirm').prop('disabled', false).text('📦 Registrar entrega a MAM');
            }
        }, 'json').fail(function(xhr) {
            alert('Error de red. HTTP ' + xhr.status + ': ' + (xhr.responseText || '').substring(0, 200));
            $('#btn-confirm').prop('disabled', false).text('📦 Registrar entrega a MAM');
        });
    });
});
</script>

<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
