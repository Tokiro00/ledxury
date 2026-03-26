<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$selectedStore = isset($storeId) ? $storeId : '';
?>
<!DOCTYPE html>
<html lang="en">
    <title>Generar Ordenes por Proveedor</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <!-- Title -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Generar Ordenes por Proveedor</h2>
                            <p class="text-sm text-gray-500">Genera ordenes de compra automaticas basadas en demanda y stock por tienda</p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/store/reorder/orders" class="mt-2 lg:mt-0 text-sm text-mam-blue-petroleo hover:underline">Ver Ordenes de Compra →</a>
                    </div>

                    <!-- Store Selector -->
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <label class="text-sm font-medium text-gray-700">Tienda:</label>
                            <select id="agentStore" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                <option value="">-- Seleccione una tienda --</option>
                                <?php if (!empty($stores)): foreach ($stores as $s): ?>
                                <option value="<?= $s->idStore ?>"><?= $s->name ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                            <button type="button" id="btnGenerate" class="px-4 py-2 text-sm text-white rounded-lg" style="background:#2E7D91;">Generar Sugerencias</button>
                            <span id="resultCount" class="text-sm text-green-600 font-medium"></span>
                        </div>
                    </div>

                    <div id="loading-msg" style="display:none" class="bg-white rounded-lg shadow-sm border p-8 text-center">
                        <p class="text-blue-500 text-lg">Calculando sugerencias de reorden...</p>
                    </div>

                    <?php if (empty($selectedStore)): ?>
                        <!-- No store selected -->
                        <div id="no-store-msg" class="bg-white rounded-lg shadow-sm border p-8 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <p class="text-gray-500 text-lg">Seleccione una tienda y haga clic en Generar Sugerencias</p>
                        </div>

                        <div id="ajax-results"></div>

                    <?php elseif (!empty($suggestions)): ?>
                        <!-- Suggestions by provider -->
                        <form action="<?= base_url() ?>sisvent/store/reorder/generateOrders" method="POST" id="formReorder">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <input type="hidden" name="storeId" value="<?= $selectedStore ?>">

                            <?php $itemIdx = 0; $grandTotal = 0; foreach ($suggestions as $providerId => $provData):
                                $provName = $provData['name'];
                                $provItems = $provData['items'];
                                $provTotal = 0;
                                foreach ($provItems as $item) {
                                    $provTotal += $item->need * $item->unit_cost;
                                }
                                $grandTotal += $provTotal;
                            ?>
                            <div class="bg-white rounded-lg shadow-sm border mb-4 provider-card">
                                <div class="flex items-center justify-between px-4 py-3 cursor-pointer border-b bg-gray-50 rounded-t-lg toggle-provider" data-provider="<?= $providerId ?>">
                                    <h3 class="text-sm font-bold text-gray-800">
                                        <svg class="w-4 h-4 inline mr-1 transform transition-transform provider-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        <?= $provName ?>
                                    </h3>
                                    <span class="text-sm font-bold" style="color:#2E7D91;">Subtotal: $<span class="provider-subtotal"><?= number_format($provTotal, 0, ',', '.') ?></span></span>
                                </div>
                                <div class="provider-body overflow-x-auto">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="text-left bg-gray-100 text-gray-600">
                                                <th class="px-3 py-2 font-semibold w-8"><input type="checkbox" class="check-all-provider" data-provider="<?= $providerId ?>" checked></th>
                                                <th class="px-3 py-2 font-semibold">Codigo</th>
                                                <th class="px-3 py-2 font-semibold">Descripcion</th>
                                                <th class="px-3 py-2 font-semibold text-center">ABC</th>
                                                <th class="px-3 py-2 font-semibold text-right">Demanda/Mes</th>
                                                <th class="px-3 py-2 font-semibold text-right">Stock Objetivo</th>
                                                <th class="px-3 py-2 font-semibold text-right">Stock Actual</th>
                                                <th class="px-3 py-2 font-semibold text-right">En Transito</th>
                                                <th class="px-3 py-2 font-semibold text-right">Pedir</th>
                                                <th class="px-3 py-2 font-semibold text-right">Costo Unit</th>
                                                <th class="px-3 py-2 font-semibold text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($provItems as $item):
                                                $badgeClass = '';
                                                switch ($item->abc_type) {
                                                    case 'A': $badgeClass = 'bg-green-500 text-white'; break;
                                                    case 'B': $badgeClass = 'bg-yellow-500 text-white'; break;
                                                    case 'C': $badgeClass = 'bg-red-500 text-white'; break;
                                                    default:  $badgeClass = 'bg-gray-400 text-white'; break;
                                                }
                                                $lineTotal = $item->need * $item->unit_cost;
                                            ?>
                                            <tr class="border-t hover:bg-blue-50 item-row" data-provider="<?= $providerId ?>">
                                                <td class="px-3 py-1.5">
                                                    <input type="checkbox" class="item-check" name="items[<?= $itemIdx ?>][selected]" value="1" checked data-provider="<?= $providerId ?>">
                                                </td>
                                                <td class="px-3 py-1.5 font-mono font-medium"><?= $item->productId ?></td>
                                                <td class="px-3 py-1.5"><?= $item->description ?></td>
                                                <td class="px-3 py-1.5 text-center">
                                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold <?= $badgeClass ?>"><?= $item->abc_type ?></span>
                                                </td>
                                                <td class="px-3 py-1.5 text-right"><?= number_format($item->demand_monthly, 0, ',', '.') ?></td>
                                                <td class="px-3 py-1.5 text-right"><?= number_format($item->stock_target, 0, ',', '.') ?></td>
                                                <td class="px-3 py-1.5 text-right"><?= number_format($item->stock_actual, 0, ',', '.') ?></td>
                                                <td class="px-3 py-1.5 text-right"><?= number_format($item->in_transit, 0, ',', '.') ?></td>
                                                <td class="px-3 py-1.5 text-right">
                                                    <input type="number" name="items[<?= $itemIdx ?>][quantity]" value="<?= $item->need ?>" min="0" class="w-16 text-xs text-right border border-gray-300 rounded px-1 py-1 qty-input" data-cost="<?= $item->unit_cost ?>">
                                                </td>
                                                <td class="px-3 py-1.5 text-right">$<?= number_format($item->unit_cost, 0, ',', '.') ?></td>
                                                <td class="px-3 py-1.5 text-right font-medium line-subtotal">$<?= number_format($lineTotal, 0, ',', '.') ?></td>
                                                <input type="hidden" name="items[<?= $itemIdx ?>][productId]" value="<?= $item->productId ?>">
                                                <input type="hidden" name="items[<?= $itemIdx ?>][unitCost]" value="<?= $item->unit_cost ?>">
                                                <input type="hidden" name="items[<?= $itemIdx ?>][providerId]" value="<?= $providerId ?>">
                                            </tr>
                                            <?php $itemIdx++; endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <!-- Grand Total & Submit -->
                            <div class="bg-white rounded-lg shadow-sm border p-4 flex items-center justify-between">
                                <div>
                                    <span class="text-sm text-gray-600">Total General:</span>
                                    <span class="text-lg font-bold ml-2" style="color:#1B365D;">$<span id="grandTotal"><?= number_format($grandTotal, 0, ',', '.') ?></span></span>
                                </div>
                                <button type="submit" class="px-6 py-3 text-sm font-bold text-white rounded-lg" style="background:#2E7D91;">
                                    Generar Ordenes
                                </button>
                            </div>
                        </form>

                    <?php else: ?>
                        <!-- No suggestions -->
                        <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
                            <svg class="w-16 h-16 mx-auto text-green-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-gray-500 text-lg">No hay productos que requieran reorden para esta tienda</p>
                        </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    // Toggle provider sections
    $(document).on('click', '.toggle-provider', function() {
        var body = $(this).closest('.provider-card').find('.provider-body');
        var arrow = $(this).find('.provider-arrow');
        body.slideToggle(200);
        arrow.toggleClass('rotate-180');
    });

    // Check/uncheck all items for a provider
    $(document).on('change', '.check-all-provider', function() {
        var providerId = $(this).data('provider');
        var checked = $(this).is(':checked');
        $('.item-check[data-provider="' + providerId + '"]').prop('checked', checked);
    });

    // Recalculate line subtotal when quantity changes
    $(document).on('input', '.qty-input', function() {
        var qty = parseInt($(this).val()) || 0;
        var cost = parseFloat($(this).data('cost')) || 0;
        var subtotal = qty * cost;
        $(this).closest('tr').find('.line-subtotal').text('$' + formatNumber(subtotal));
    });

    function formatNumber(n) {
        return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    var abcColors = {'A':'bg-green-500 text-white','B':'bg-yellow-500 text-white','C':'bg-red-500 text-white'};

    $(document).on('click', '#btnGenerate', function() {
        var storeId = $('#agentStore').val();
        if (!storeId) { alert('Seleccione una tienda'); return; }

        $('#no-store-msg').hide();
        $('#ajax-results').html('');
        $('#loading-msg').show();
        $('#resultCount').text('');

        $.getJSON('<?= base_url() ?>sisvent/store/reorder/debug/' + storeId, function(data) {
            $('#loading-msg').hide();

            if (!data || data.providers == 0) {
                $('#ajax-results').html('<div class="bg-white rounded-lg shadow-sm border p-8 text-center"><p class="text-green-500 text-lg">No hay productos que requieran reorden</p></div>');
                return;
            }

            $('#resultCount').text(data.providers + ' proveedores con sugerencias');

            var html = '<form action="<?= base_url() ?>sisvent/store/reorder/generateOrders" method="POST">';
            html += '<input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">';
            html += '<input type="hidden" name="storeId" value="' + storeId + '">';

            var idx = 0;
            var grandTotal = 0;

            $.each(data.sample, function(provId, prov) {
                var provTotal = 0;
                $.each(prov.items, function(i, item) { provTotal += item.need * item.unit_cost; });
                grandTotal += provTotal;

                html += '<div class="bg-white rounded-lg shadow-sm border mb-4">';
                html += '<div class="flex items-center justify-between px-4 py-3 border-b bg-gray-50 rounded-t-lg cursor-pointer toggle-provider">';
                html += '<h3 class="text-sm font-bold text-gray-800">' + prov.provider_name + '</h3>';
                html += '<span class="text-sm font-bold" style="color:#2E7D91;">Subtotal: $' + formatNumber(provTotal) + '</span></div>';
                html += '<div class="overflow-x-auto"><table class="w-full text-xs">';
                html += '<thead style="background:#1B365D;color:white"><tr>';
                html += '<th class="px-2 py-2 w-8"><input type="checkbox" checked class="check-all-provider" data-provider="' + provId + '"></th>';
                html += '<th class="px-2 py-2">Codigo</th><th class="px-2 py-2">Descripcion</th><th class="px-2 py-2">ABC</th>';
                html += '<th class="px-2 py-2 text-right">Demanda/Mes</th><th class="px-2 py-2 text-right">Objetivo</th>';
                html += '<th class="px-2 py-2 text-right">Stock</th><th class="px-2 py-2 text-right">Transito</th>';
                html += '<th class="px-2 py-2 text-right">Pedir</th><th class="px-2 py-2 text-right">Costo</th>';
                html += '<th class="px-2 py-2 text-right">Subtotal</th></tr></thead><tbody>';

                $.each(prov.items, function(i, item) {
                    var lt = item.need * item.unit_cost;
                    var bc = abcColors[item.abc_type] || 'bg-gray-400 text-white';
                    html += '<tr class="border-t hover:bg-blue-50">';
                    html += '<td class="px-2 py-1"><input type="checkbox" class="item-check" checked data-provider="' + provId + '"></td>';
                    html += '<td class="px-2 py-1 font-mono">' + item.productId + '</td>';
                    html += '<td class="px-2 py-1">' + item.description + '</td>';
                    html += '<td class="px-2 py-1 text-center"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold ' + bc + '">' + item.abc_type + '</span></td>';
                    html += '<td class="px-2 py-1 text-right">' + item.demand_monthly + '</td>';
                    html += '<td class="px-2 py-1 text-right">' + formatNumber(item.stock_target) + '</td>';
                    html += '<td class="px-2 py-1 text-right">' + formatNumber(item.stock_actual) + '</td>';
                    html += '<td class="px-2 py-1 text-right">' + item.in_transit + '</td>';
                    html += '<td class="px-2 py-1 text-right"><input type="number" name="items[' + idx + '][quantity]" value="' + item.need + '" min="0" class="w-16 text-xs text-right border rounded px-1 py-1 qty-input" data-cost="' + item.unit_cost + '"></td>';
                    html += '<td class="px-2 py-1 text-right">$' + formatNumber(item.unit_cost) + '</td>';
                    html += '<td class="px-2 py-1 text-right font-medium line-subtotal">$' + formatNumber(lt) + '</td>';
                    html += '<input type="hidden" name="items[' + idx + '][productId]" value="' + item.productId + '">';
                    html += '<input type="hidden" name="items[' + idx + '][unitCost]" value="' + item.unit_cost + '">';
                    html += '<input type="hidden" name="items[' + idx + '][providerId]" value="' + provId + '">';
                    html += '</tr>';
                    idx++;
                });

                html += '</tbody></table></div></div>';
            });

            html += '<div class="bg-white rounded-lg shadow-sm border p-4 flex items-center justify-between">';
            html += '<div><span class="text-sm text-gray-600">Total General:</span>';
            html += '<span class="text-lg font-bold ml-2" style="color:#1B365D;">$' + formatNumber(grandTotal) + '</span></div>';
            html += '<a href="<?= base_url() ?>sisvent/store/reorder/exportExcel/' + storeId + '" class="px-4 py-3 text-sm font-bold text-white rounded-lg bg-green-600 mr-2">Exportar Excel</a>';
            html += '<button type="submit" class="px-6 py-3 text-sm font-bold text-white rounded-lg" style="background:#2E7D91;">Generar Ordenes de Compra</button>';
            html += '</div></form>';

            $('#ajax-results').html(html);
        }).fail(function() {
            $('#loading-msg').hide();
            $('#ajax-results').html('<div class="bg-red-100 border border-red-400 text-red-700 p-4 rounded">Error al cargar sugerencias. Intente de nuevo.</div>');
        });
    });
    </script>
</body>
</html>
