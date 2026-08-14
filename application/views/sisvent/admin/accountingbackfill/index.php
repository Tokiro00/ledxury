<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$csrfName = $this->security->get_csrf_token_name();
$csrfHash = $this->security->get_csrf_hash();
?>
<!DOCTYPE html>
<html lang="es">
<title>Back-fill Contable — Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-50">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 max-w-screen-xl mx-auto">
                <div class="mb-5">
                    <p class="text-xs text-gray-400 uppercase">Contabilidad</p>
                    <h2 class="text-2xl font-bold text-gray-800">Back-fill de Asientos Contables</h2>
                    <p class="text-sm text-gray-500 mt-1">Genera asientos retroactivos para facturas y notas crédito anteriores a la Fase 3.1. Idempotente — se puede correr varias veces sin duplicar.</p>
                </div>

                <!-- KPIs -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
                    <div class="bg-white border rounded-lg p-4">
                        <p class="text-xxs text-gray-400 uppercase">Facturas en BD</p>
                        <p class="text-2xl font-bold text-gray-700 mt-1"><?= number_format($stats['invoices_total']) ?></p>
                    </div>
                    <div class="bg-white border rounded-lg p-4">
                        <p class="text-xxs text-gray-400 uppercase">Asientos venta</p>
                        <p class="text-2xl font-bold text-green-700 mt-1" id="kpi-inv-ok"><?= number_format($stats['invoices_with_entry']) ?></p>
                    </div>
                    <div class="bg-white border rounded-lg p-4 border-2 <?= $stats['invoices_pending'] > 0 ? 'border-amber-400' : 'border-green-400' ?>">
                        <p class="text-xxs text-gray-400 uppercase">Pendientes venta</p>
                        <p class="text-2xl font-bold <?= $stats['invoices_pending'] > 0 ? 'text-amber-700' : 'text-green-700' ?> mt-1" id="kpi-inv-pending"><?= number_format($stats['invoices_pending']) ?></p>
                    </div>
                    <div class="bg-white border rounded-lg p-4 border-2 <?= $stats['cost_pending'] > 0 ? 'border-amber-400' : 'border-green-400' ?>">
                        <p class="text-xxs text-gray-400 uppercase">Pendientes costo</p>
                        <p class="text-2xl font-bold <?= $stats['cost_pending'] > 0 ? 'text-amber-700' : 'text-green-700' ?> mt-1" id="kpi-cost-pending"><?= number_format($stats['cost_pending']) ?></p>
                    </div>
                    <div class="bg-white border rounded-lg p-4 border-2 <?= $stats['refunds_pending'] > 0 ? 'border-amber-400' : 'border-green-400' ?>">
                        <p class="text-xxs text-gray-400 uppercase">Pendientes refunds</p>
                        <p class="text-2xl font-bold <?= $stats['refunds_pending'] > 0 ? 'text-amber-700' : 'text-green-700' ?> mt-1" id="kpi-ref-pending"><?= number_format($stats['refunds_pending']) ?></p>
                    </div>
                </div>

                <!-- Panel facturas -->
                <div class="bg-white rounded-lg border p-5 mb-4">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Facturas de Venta</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Asiento: DR Clientes (130505) + aux / CR Ventas (413506). Idempotente.</p>
                        </div>
                        <button id="btn-run-invoices" class="px-4 py-2 text-sm font-bold text-white bg-mam-blue-petroleo hover:opacity-90 rounded-lg disabled:opacity-50" <?= $stats['invoices_pending'] == 0 ? 'disabled' : '' ?>>
                            ▶ Procesar 200 facturas
                        </button>
                    </div>
                    <div id="progress-invoices" class="hidden">
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden mb-2">
                            <div id="bar-invoices" class="h-full bg-mam-blue-petroleo transition-all" style="width:0%;"></div>
                        </div>
                        <p id="log-invoices" class="text-xs text-gray-600 font-mono"></p>
                    </div>
                </div>

                <!-- Panel Costo de Ventas -->
                <div class="bg-white rounded-lg border p-5 mb-4">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Costo de Ventas</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Asiento: DR Costo Mercancía Vendida (613501) / CR Inventario (143501). Calcula desde invoice_details × products.cost_cop.</p>
                        </div>
                        <button id="btn-run-cost" class="px-4 py-2 text-sm font-bold text-white bg-emerald-700 hover:opacity-90 rounded-lg disabled:opacity-50" <?= $stats['cost_pending'] == 0 ? 'disabled' : '' ?>>
                            ▶ Procesar 200 facturas
                        </button>
                    </div>
                    <div id="progress-cost" class="hidden">
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden mb-2">
                            <div id="bar-cost" class="h-full bg-emerald-700 transition-all" style="width:0%;"></div>
                        </div>
                        <p id="log-cost" class="text-xs text-gray-600 font-mono"></p>
                    </div>
                </div>

                <!-- Panel refunds -->
                <div class="bg-white rounded-lg border p-5 mb-4">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Devoluciones (refunds legacy)</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Asiento: DR Devoluciones en Ventas (417505) / CR Clientes + aux. Idempotente.</p>
                        </div>
                        <button id="btn-run-refunds" class="px-4 py-2 text-sm font-bold text-white bg-red-600 hover:opacity-90 rounded-lg disabled:opacity-50" <?= $stats['refunds_pending'] == 0 ? 'disabled' : '' ?>>
                            ▶ Procesar 200 refunds
                        </button>
                    </div>
                    <div id="progress-refunds" class="hidden">
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden mb-2">
                            <div id="bar-refunds" class="h-full bg-red-600 transition-all" style="width:0%;"></div>
                        </div>
                        <p id="log-refunds" class="text-xs text-gray-600 font-mono"></p>
                    </div>
                </div>

                <!-- Auto-run option -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <h4 class="text-sm font-bold text-amber-800 mb-2">⚡ Modo automático</h4>
                    <p class="text-xs text-amber-700 mb-3">Procesa todo en bucle hasta que el contador llegue a 0. Puede tardar varios minutos según volumen. No cerrá la pestaña.</p>
                    <div class="flex flex-wrap gap-2">
                        <button id="btn-auto-invoices" class="px-3 py-1.5 text-xs font-medium bg-amber-600 text-white rounded hover:opacity-90">⚡ Auto-procesar facturas</button>
                        <button id="btn-auto-cost"     class="px-3 py-1.5 text-xs font-medium bg-amber-600 text-white rounded hover:opacity-90">⚡ Auto-procesar costo de ventas</button>
                        <button id="btn-auto-refunds"  class="px-3 py-1.5 text-xs font-medium bg-amber-600 text-white rounded hover:opacity-90">⚡ Auto-procesar refunds</button>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
(function() {
    var BASE = '<?= base_url() ?>';
    var CSRF_NAME = '<?= $csrfName ?>', CSRF_HASH = '<?= $csrfHash ?>';

    function runBatch(kind, callback) {
        var endpoint = kind === 'invoices' ? 'runInvoices'
                     : kind === 'cost'     ? 'runCostOfSales'
                     :                       'runRefunds';
        var data = {};
        data[CSRF_NAME] = CSRF_HASH;
        data['limit'] = 200;
        $.post(BASE + 'sisvent/admin/accountingbackfill/' + endpoint, data, function(r) {
            callback(r);
        }, 'json').fail(function(xhr) {
            callback({ok: false, error: 'HTTP ' + xhr.status});
        });
    }

    function updateProgress(kind, r) {
        var $log = $('#log-' + kind);
        var $bar = $('#bar-' + kind);
        var $progress = $('#progress-' + kind);
        $progress.removeClass('hidden');

        var msg = '✓ ' + r.success + ' procesadas';
        if (r.fail) msg += ' · ⚠ ' + r.fail + ' fallaron';
        msg += ' · Faltan: ' + r.remaining;
        if (r.errors && r.errors.length) msg += ' · Último error: ' + r.errors[0];
        $log.text(msg);

        // Update KPI
        if (kind === 'invoices') {
            $('#kpi-inv-pending').text(r.remaining.toLocaleString());
            $('#kpi-inv-ok').text((parseInt($('#kpi-inv-ok').text().replace(/,/g, '')) + r.success).toLocaleString());
        } else if (kind === 'cost') {
            $('#kpi-cost-pending').text(r.remaining.toLocaleString());
        } else {
            $('#kpi-ref-pending').text(r.remaining.toLocaleString());
        }

        // Disable button when done
        if (r.remaining == 0) {
            $('#btn-run-' + kind).prop('disabled', true).text('✓ Completo');
            $bar.css('width', '100%');
        } else {
            // Estimar progreso (asume que arrancamos en el max original)
            var maxAttr = $bar.attr('data-max');
            if (!maxAttr) {
                maxAttr = r.remaining + r.success;
                $bar.attr('data-max', maxAttr);
            }
            var pct = ((maxAttr - r.remaining) / maxAttr) * 100;
            $bar.css('width', pct + '%');
        }
    }

    $(document).on('click', '#btn-run-invoices', function() {
        var $btn = $(this).prop('disabled', true).text('Procesando...');
        runBatch('invoices', function(r) {
            $btn.prop('disabled', false).text('▶ Procesar 200 más');
            if (r.ok) updateProgress('invoices', r);
            else $('#log-invoices').text('Error: ' + (r.error || 'desconocido'));
        });
    });

    $(document).on('click', '#btn-run-refunds', function() {
        var $btn = $(this).prop('disabled', true).text('Procesando...');
        runBatch('refunds', function(r) {
            $btn.prop('disabled', false).text('▶ Procesar 200 más');
            if (r.ok) updateProgress('refunds', r);
            else $('#log-refunds').text('Error: ' + (r.error || 'desconocido'));
        });
    });

    $(document).on('click', '#btn-run-cost', function() {
        var $btn = $(this).prop('disabled', true).text('Procesando...');
        runBatch('cost', function(r) {
            $btn.prop('disabled', false).text('▶ Procesar 200 más');
            if (r.ok) updateProgress('cost', r);
            else $('#log-cost').text('Error: ' + (r.error || 'desconocido'));
        });
    });

    function autoLoop(kind, $btn) {
        $btn.prop('disabled', true).text('Procesando...');
        function step() {
            runBatch(kind, function(r) {
                if (!r.ok) {
                    $btn.prop('disabled', false).text('⚡ Reintentar');
                    return;
                }
                updateProgress(kind, r);
                if (r.remaining > 0 && r.processed > 0) {
                    setTimeout(step, 500);
                } else {
                    $btn.text('✓ Completo');
                }
            });
        }
        step();
    }

    $(document).on('click', '#btn-auto-invoices', function() {
        if (!confirm('Procesar TODAS las facturas pendientes en bucle. ¿Continuar?')) return;
        autoLoop('invoices', $(this));
    });
    $(document).on('click', '#btn-auto-cost', function() {
        if (!confirm('Procesar TODOS los asientos de costo de ventas pendientes en bucle. ¿Continuar?')) return;
        autoLoop('cost', $(this));
    });
    $(document).on('click', '#btn-auto-refunds', function() {
        if (!confirm('Procesar TODAS las refunds pendientes en bucle. ¿Continuar?')) return;
        autoLoop('refunds', $(this));
    });
})();
</script>
</body>
</html>
