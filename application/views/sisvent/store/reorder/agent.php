<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Agente de Reorden</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
    .coverage-btn { transition: all 0.15s; }
    .coverage-btn.active { background: #2E7D91; color: white; }
    .coverage-btn:not(.active) { background: white; color: #374151; border: 1px solid #D1D5DB; }
    .coverage-btn:not(.active):hover { background: #F3F4F6; }
    .sticky-thead th { position: sticky; top: 0; z-index: 5; }
    @media print {
        .no-print { display: none !important; }
        #bars > div:first-child { display: none !important; }
        main { overflow: visible !important; }
        .sticky-thead th { position: static !important; }
    }
</style>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <!-- Header -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-4 no-print">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Agente de Reorden</h2>
                            <p class="text-sm text-gray-500">Genera ordenes de compra basadas en demanda y stock</p>
                        </div>
                        <div class="flex items-center gap-3 mt-2 lg:mt-0">
                            <a href="<?= base_url() ?>sisvent/store/reorder" class="text-sm text-gray-500 hover:underline">ABC</a>
                            <a href="<?= base_url() ?>sisvent/store/reorder/orders" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#1B365D;">
                                Ordenes de Compra
                            </a>
                        </div>
                    </div>

                    <!-- Control Bar -->
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4 no-print">
                        <div class="flex flex-wrap items-center gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Tienda</label>
                                <select id="agentStore" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                    <option value="">Seleccione...</option>
                                    <?php if (!empty($stores)): foreach ($stores as $s): ?>
                                    <option value="<?= $s->idStore ?>"><?= $s->name ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div>
                                <button type="button" id="toggleCoverage" class="text-xs text-gray-500 hover:text-gray-700 underline mt-5">Ajustar cobertura</button>
                                <div id="coveragePanel" class="hidden mt-2 flex items-center gap-2">
                                    <div class="flex items-center gap-1">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-green-500 text-white">A</span>
                                        <select id="monthsA" class="text-sm border border-gray-300 rounded px-1 py-1 w-14">
                                            <option value="1">1m</option><option value="2">2m</option><option value="3" selected>3m</option><option value="4">4m</option><option value="6">6m</option><option value="9">9m</option><option value="12">12m</option>
                                        </select>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-yellow-500 text-white">B</span>
                                        <select id="monthsB" class="text-sm border border-gray-300 rounded px-1 py-1 w-14">
                                            <option value="1">1m</option><option value="2">2m</option><option value="3">3m</option><option value="4">4m</option><option value="6" selected>6m</option><option value="9">9m</option><option value="12">12m</option>
                                        </select>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-red-500 text-white">C</span>
                                        <select id="monthsC" class="text-sm border border-gray-300 rounded px-1 py-1 w-14">
                                            <option value="1">1m</option><option value="2">2m</option><option value="3" selected>3m</option><option value="4">4m</option><option value="6">6m</option><option value="9">9m</option><option value="12">12m</option>
                                        </select>
                                    </div>
                                    <button type="button" onclick="$('#btnGenerate').click()" class="text-xs px-2 py-1 text-white rounded" style="background:#2E7D91;">Recalcular</button>
                                </div>
                            </div>
                            <div class="flex-grow"></div>
                            <button type="button" id="btnGenerate" class="px-5 py-2 text-sm font-bold text-white rounded-lg" style="background:#2E7D91;">
                                Calcular Sugerencias
                            </button>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div id="loading-msg" style="display:none" class="bg-white rounded-lg shadow-sm border p-12 text-center">
                        <svg class="w-10 h-10 mx-auto text-blue-500 animate-spin mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <p class="text-gray-500">Calculando sugerencias de reorden...</p>
                    </div>

                    <!-- Empty State -->
                    <div id="empty-msg" class="bg-white rounded-lg shadow-sm border p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <p class="text-gray-400 text-lg">Seleccione una tienda y cobertura para generar sugerencias</p>
                    </div>

                    <!-- Results Container (hidden until data loads) -->
                    <div id="results-container" style="display:none">

                        <!-- Summary Cards -->
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                            <div class="bg-white rounded-lg shadow-sm border-l-4 p-4" style="border-color:#2E7D91;">
                                <p class="text-xs text-gray-500 uppercase">Proveedores</p>
                                <p class="text-xl font-bold text-gray-800 mt-1" id="statProviders">0</p>
                            </div>
                            <div class="bg-white rounded-lg shadow-sm border-l-4 p-4" style="border-color:#A8C63A;">
                                <p class="text-xs text-gray-500 uppercase">Productos a Pedir</p>
                                <p class="text-xl font-bold text-gray-800 mt-1" id="statProducts">0</p>
                            </div>
                            <div class="bg-white rounded-lg shadow-sm border-l-4 border-yellow-500 p-4">
                                <p class="text-xs text-gray-500 uppercase">Inversion Total</p>
                                <p class="text-xl font-bold text-gray-800 mt-1">$<span id="statTotal">0</span></p>
                            </div>
                            <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500 p-4">
                                <p class="text-xs text-gray-500 uppercase">Cobertura</p>
                                <p class="text-sm font-bold text-gray-800 mt-1" id="statMonths">A:3m  B:2m  C:1m</p>
                            </div>
                        </div>

                        <!-- Provider Filter + Actions -->
                        <div class="bg-white rounded-lg shadow-sm border p-4 mb-4 no-print">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex-grow">
                                    <label class="block text-xs text-gray-500 uppercase mb-1">Proveedor</label>
                                    <select id="providerFilter" class="text-sm border border-gray-300 rounded-lg px-3 py-2 w-full lg:w-auto min-w-64 focus:outline-none focus:border-blue-500">
                                        <option value="all">Todos los proveedores</option>
                                    </select>
                                </div>
                                <div class="flex gap-2 items-end">
                                    <button onclick="doExportExcel()" class="px-3 py-2 text-sm font-medium text-white rounded-lg bg-green-600" title="Exportar Excel">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        Excel
                                    </button>
                                    <button onclick="window.print()" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" title="Imprimir">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        Imprimir
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Provider Info Card (shown when single provider selected) -->
                        <div id="providerInfo" class="bg-white rounded-lg shadow-sm border p-4 mb-4" style="display:none">
                            <div class="flex flex-wrap items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800" id="provInfoName"></h3>
                                    <p class="text-sm text-gray-500"><span id="provInfoNit"></span> <span id="provInfoPhone" class="ml-3"></span> <span id="provInfoEmail" class="ml-3"></span></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500 uppercase">Subtotal Proveedor</p>
                                    <p class="text-xl font-bold" style="color:#2E7D91;">$<span id="provInfoTotal">0</span></p>
                                    <p class="text-xs text-gray-400"><span id="provInfoItems">0</span> productos</p>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <form action="<?= base_url() ?>sisvent/store/reorder/generateOrders" method="POST" id="formReorder">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <input type="hidden" name="storeId" id="formStoreId" value="">

                            <div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-4">
                                <div class="overflow-x-auto" style="max-height:60vh; overflow-y:auto;">
                                    <table class="w-full text-xs" id="itemsTable">
                                        <thead class="sticky-thead">
                                            <tr class="text-left" style="background:#1B365D; color:white;">
                                                <th class="px-2 py-2.5 font-semibold w-8"><input type="checkbox" id="checkAll" checked></th>
                                                <th class="px-2 py-2.5 font-semibold w-10"></th>
                                                <th class="px-2 py-2.5 font-semibold">Codigo</th>
                                                <th class="px-2 py-2.5 font-semibold">Descripcion</th>
                                                <th class="px-2 py-2.5 font-semibold text-center">ABC</th>
                                                <th class="px-2 py-2.5 font-semibold text-right">Dem/Mes</th>
                                                <th class="px-2 py-2.5 font-semibold text-right">Objetivo</th>
                                                <th class="px-2 py-2.5 font-semibold text-right">Stock</th>
                                                <th class="px-2 py-2.5 font-semibold text-right">Transito</th>
                                                <th class="px-2 py-2.5 font-semibold text-right">Pedir</th>
                                                <th class="px-2 py-2.5 font-semibold text-right">COP</th>
                                                <th class="px-2 py-2.5 font-semibold text-right">RMB</th>
                                                <th class="px-2 py-2.5 font-semibold text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsBody"></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Bottom Bar -->
                            <div class="bg-white rounded-lg shadow-sm border p-4 flex flex-wrap items-center justify-between no-print">
                                <div class="text-sm text-gray-600">
                                    Seleccionados: <strong id="selectedCount">0</strong> de <span id="totalCount">0</span> items
                                    &nbsp;&mdash;&nbsp;
                                    Total: <strong class="text-lg" style="color:#1B365D;">$<span id="selectedTotal">0</span></strong>
                                </div>
                                <button type="submit" class="px-6 py-3 text-sm font-bold text-white rounded-lg mt-2 lg:mt-0" style="background:#2E7D91;">
                                    Generar Ordenes de Compra
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    var RD = null; // Reorder Data (full JSON response)
    var abcColors = {'A':'bg-green-500 text-white','B':'bg-yellow-500 text-white','C':'bg-red-500 text-white','N':'bg-gray-400 text-white'};

    function fmt(n) { return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
    function getMonthsParams() { return 'mA=' + $('#monthsA').val() + '&mB=' + $('#monthsB').val() + '&mC=' + $('#monthsC').val(); }
    function getMonthsLabel() { return 'A:' + $('#monthsA').val() + 'm / B:' + $('#monthsB').val() + 'm / C:' + $('#monthsC').val() + 'm'; }

    // Toggle coverage panel
    $(document).on('click', '#toggleCoverage', function() {
        $('#coveragePanel').slideToggle(200);
    });

    // Generate suggestions
    $(document).on('click', '#btnGenerate', function() {
        var storeId = $('#agentStore').val();
        if (!storeId) { alert('Seleccione una tienda'); return; }

        $('#empty-msg').hide();
        $('#results-container').hide();
        $('#loading-msg').show();

        $.getJSON('<?= base_url() ?>sisvent/store/reorder/debug/' + storeId + '?' + getMonthsParams(), function(data) {
            $('#loading-msg').hide();
            RD = data;

            if (!data || data.providers == 0) {
                $('#empty-msg').show().find('p').text('No hay productos que requieran reorden para esta tienda');
                return;
            }

            // Update summary cards
            $('#statProviders').text(data.providers);
            $('#statProducts').text(data.totalItems);
            $('#statTotal').text(fmt(data.totalCost));
            $('#statMonths').text(getMonthsLabel());
            $('#formStoreId').val(storeId);

            // Populate provider dropdown
            var sel = '<option value="all">Todos los proveedores (' + data.providers + ')</option>';
            $.each(data.providerSummary, function(i, p) {
                sel += '<option value="' + p.id + '">' + p.name + ' (' + p.items_count + ') — $' + fmt(p.total) + '</option>';
            });
            $('#providerFilter').html(sel);

            // Render table for "all"
            renderTable('all');
            $('#results-container').show();
        }).fail(function() {
            $('#loading-msg').hide();
            $('#empty-msg').show().find('p').text('Error al cargar sugerencias. Intente de nuevo.');
        });
    });

    // Provider filter change
    $(document).on('change', '#providerFilter', function() {
        renderTable($(this).val());
    });

    function renderTable(filterId) {
        if (!RD) return;
        var html = '';
        var idx = 0;
        var providerData = filterId === 'all' ? RD.data : {};

        if (filterId !== 'all') {
            if (RD.data[filterId]) providerData[filterId] = RD.data[filterId];
        }

        // Show/hide provider info card
        if (filterId !== 'all') {
            var ps = null;
            $.each(RD.providerSummary, function(i, p) { if (p.id == filterId) ps = p; });
            if (ps) {
                $('#provInfoName').text(ps.name);
                $('#provInfoNit').text(ps.nit ? 'NIT: ' + ps.nit : '');
                $('#provInfoPhone').text(ps.phone ? 'Tel: ' + ps.phone : '');
                $('#provInfoEmail').text(ps.email ? ps.email : '');
                $('#provInfoTotal').text(fmt(ps.total));
                $('#provInfoItems').text(ps.items_count);
                $('#providerInfo').show();
            }
        } else {
            $('#providerInfo').hide();
        }

        var showGroupHeaders = (filterId === 'all' && RD.providers > 1);

        $.each(providerData, function(provId, prov) {
            if (showGroupHeaders) {
                html += '<tr style="background:#F0F4F8;"><td colspan="13" class="px-3 py-2 font-bold text-gray-700 text-sm border-t-2 border-gray-300">';
                html += prov.provider_name + ' <span class="font-normal text-gray-400">(' + prov.items.length + ' items — $' + fmt(prov.total) + ')</span></td></tr>';
            }
            $.each(prov.items, function(i, item) {
                var lt = item.need * item.unit_cost;
                var bc = abcColors[item.abc_type] || abcColors['N'];
                var rowBg = idx % 2 == 0 ? 'bg-white' : 'bg-gray-50';
                var imgUrl = '<?= base_url() ?>public/dist/images/' + (item.picture_url || 'products/no_image.png');
                var rmb = item.cost_rmb || 0;
                html += '<tr class="border-t hover:bg-blue-50 ' + rowBg + ' item-row">';
                html += '<td class="px-2 py-1"><input type="checkbox" class="item-check" name="items[' + idx + '][selected]" value="1" checked data-cost="' + item.unit_cost + '" data-qty="' + item.need + '"></td>';
                html += '<td class="px-2 py-1"><img src="' + imgUrl + '" class="w-8 h-8 object-cover rounded" onerror="this.src=\'<?= base_url() ?>public/dist/images/products/no_image.png\'"></td>';
                html += '<td class="px-2 py-1 font-mono font-medium text-gray-700 text-xs">' + item.productId + '</td>';
                html += '<td class="px-2 py-1 text-gray-600 text-xs">' + item.description + '</td>';
                html += '<td class="px-2 py-1 text-center"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold ' + bc + '">' + item.abc_type + '</span></td>';
                html += '<td class="px-2 py-1 text-right">' + item.demand_monthly + '</td>';
                html += '<td class="px-2 py-1 text-right">' + fmt(item.stock_target) + '</td>';
                html += '<td class="px-2 py-1 text-right ' + (item.stock_actual <= 0 ? 'text-red-600 font-bold' : '') + '">' + fmt(item.stock_actual) + '</td>';
                html += '<td class="px-2 py-1 text-right text-blue-600">' + item.in_transit + '</td>';
                html += '<td class="px-2 py-1 text-right"><input type="number" name="items[' + idx + '][quantity]" value="' + item.need + '" min="0" class="w-16 text-xs text-right border border-gray-300 rounded px-1 py-1 qty-input" data-cost="' + item.unit_cost + '"></td>';
                html += '<td class="px-2 py-1 text-right text-gray-500 text-xs">$' + fmt(item.unit_cost) + '</td>';
                html += '<td class="px-2 py-1 text-right text-red-400 text-xs">\u00A5' + rmb.toFixed(1) + '</td>';
                html += '<td class="px-2 py-1 text-right font-medium line-subtotal text-xs">$' + fmt(lt) + '</td>';
                html += '<input type="hidden" name="items[' + idx + '][productId]" value="' + item.productId + '">';
                html += '<input type="hidden" name="items[' + idx + '][unitCost]" value="' + item.unit_cost + '">';
                html += '<input type="hidden" name="items[' + idx + '][providerId]" value="' + provId + '">';
                html += '</tr>';
                idx++;
            });
        });

        $('#itemsBody').html(html);
        $('#totalCount').text(idx);
        updateTotals();
    }

    function updateTotals() {
        var count = 0, total = 0;
        $('#itemsBody .item-row').each(function() {
            var $row = $(this);
            var checked = $row.find('.item-check').is(':checked');
            if (checked) {
                count++;
                var qty = parseInt($row.find('.qty-input').val()) || 0;
                var cost = parseFloat($row.find('.qty-input').data('cost')) || 0;
                total += qty * cost;
            }
        });
        $('#selectedCount').text(count);
        $('#selectedTotal').text(fmt(total));
    }

    // Recalculate on quantity change
    $(document).on('input', '.qty-input', function() {
        var qty = parseInt($(this).val()) || 0;
        var cost = parseFloat($(this).data('cost')) || 0;
        $(this).closest('tr').find('.line-subtotal').text('$' + fmt(qty * cost));
        updateTotals();
    });

    // Check/uncheck all
    $(document).on('change', '#checkAll', function() {
        var checked = $(this).is(':checked');
        $('.item-check').prop('checked', checked);
        updateTotals();
    });

    // Single check change
    $(document).on('change', '.item-check', function() { updateTotals(); });

    // Export Excel
    function doExportExcel() {
        var storeId = $('#agentStore').val();
        if (!storeId || !RD) return;
        var provId = $('#providerFilter').val();
        var mp = getMonthsParams();
        if (provId === 'all') {
            window.location = '<?= base_url() ?>sisvent/store/reorder/exportExcel/' + storeId + '?' + mp;
        } else {
            window.location = '<?= base_url() ?>sisvent/store/reorder/exportExcelProvider/' + storeId + '/' + provId + '?' + mp;
        }
    }
    </script>
</body>
</html>
