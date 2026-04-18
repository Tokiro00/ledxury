<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$k = $kpi;
?>
<!DOCTYPE html>
<html lang="en">
    <title>Pagos Interrapidisimo - Ledxury</title>
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
                            <h2 class="text-lg font-semibold text-gray-700">Pagos Interrapidisimo</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Conciliacion y registro de contrapagos</p>
                        </div>
                        <div class="flex items-center gap-3 mt-2 lg:mt-0">
                            <a href="<?= base_url() ?>sisvent/admin/contrapagos/entreCompanias"
                               class="inline-flex items-center px-4 py-2 text-xs font-bold text-white rounded-lg transition-colors" style="background:#7C3AED;">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                Entre Compañías
                            </a>
                            <a href="<?= base_url() ?>sisvent/admin/contrapagos/invoices"
                               class="inline-flex items-center px-4 py-2 text-xs font-bold text-white rounded-lg transition-colors" style="background:#1B365D;">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Facturas Inter
                            </a>
                            <a href="<?= base_url() ?>sisvent/admin/envios" class="text-xs text-mam-blue-petroleo hover:underline">&larr; Dashboard Envios</a>
                        </div>
                    </div>

                    <?php if ($this->session->flashdata('contrapago_error')): ?>
                    <div class="flex items-center p-3 mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <?= $this->session->flashdata('contrapago_error') ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($this->session->flashdata('contrapago_success')): ?>
                    <div class="flex items-center p-3 mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <?= $this->session->flashdata('contrapago_success') ?>
                    </div>
                    <?php endif; ?>

                    <!-- KPI Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Lotes Importados</p>
                            <p class="text-xl font-bold text-gray-700 mt-1"><?= $k['total_lotes'] ?></p>
                        </div>
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Pendientes</p>
                            <p class="text-xl font-bold mt-1 <?= $k['pendientes'] > 0 ? 'text-yellow-600' : 'text-gray-300' ?>"><?= $k['pendientes'] ?></p>
                        </div>
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Registrados</p>
                            <p class="text-xl font-bold text-green-600 mt-1"><?= $k['registrados'] ?></p>
                        </div>
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Total Registrado</p>
                            <p class="text-xl font-bold text-green-600 mt-1">$<?= number_format($k['total_registrado'], 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Upload -->
                    <div class="bg-white rounded-lg border p-5 mb-5">
                        <form action="<?= base_url() ?>sisvent/admin/contrapagos/upload" method="POST" enctype="multipart/form-data" class="flex flex-col lg:flex-row items-start lg:items-end gap-4">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <div class="flex-1 w-full">
                                <label class="block text-xs text-gray-400 uppercase tracking-wide mb-1.5">Archivo de compensacion (.xlsx)</label>
                                <input type="file" name="excel_file" accept=".xlsx,.xls" required
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:outline-none focus:border-mam-blue-petroleo focus:bg-white transition-colors">
                            </div>
                            <button type="submit" style="background:#4487A0;" class="inline-flex items-center flex-shrink-0 px-5 py-2.5 text-sm font-medium text-white rounded-lg hover:opacity-90 transition-opacity whitespace-nowrap">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                Importar y Conciliar
                            </button>
                        </form>
                        <p class="text-xs text-gray-300 mt-2">Cruza automaticamente las guias con las facturas del sistema.</p>
                    </div>

                    <!-- Table -->
                    <div class="bg-white rounded-lg border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D;">
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wide">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wide">Archivo</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-white uppercase tracking-wide">Guias</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-white uppercase tracking-wide">Neto</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wide">Fecha</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wide">Cruce</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wide">Estado</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wide">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($batches)): $i=0; foreach ($batches as $b): $i++;
                                        $stClass = 'bg-gray-100 text-gray-500';
                                        $stLabel = ucfirst($b->status);
                                        if ($b->status === 'importado') { $stClass = 'bg-yellow-100 text-yellow-700'; $stLabel = 'Importado'; }
                                        elseif ($b->status === 'conciliado') { $stClass = 'bg-blue-100 text-blue-700'; $stLabel = 'Conciliado'; }
                                        elseif ($b->status === 'registrado') { $stClass = 'bg-green-100 text-green-700'; $stLabel = 'Registrado'; }
                                        $neto = $b->total_valor - round($b->total_valor * 0.004);
                                    ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50 transition-colors">
                                        <td class="px-4 py-2.5 font-medium text-gray-500"><?= $b->id ?></td>
                                        <td class="px-4 py-2.5">
                                            <p class="font-medium text-gray-700"><?= htmlspecialchars($b->filename) ?></p>
                                            <p class="text-gray-400"><?= htmlspecialchars($b->sheet_name) ?><?= $b->banco ? ' &middot; ' . $b->banco : '' ?></p>
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-bold text-gray-700"><?= $b->total_guias ?></td>
                                        <td class="px-4 py-2.5 text-right">
                                            <span class="font-bold text-gray-800">$<?= number_format($neto, 0, ',', '.') ?></span>
                                            <p class="text-gray-400">Bruto $<?= number_format($b->total_valor, 0, ',', '.') ?></p>
                                        </td>
                                        <td class="px-4 py-2.5 text-center text-gray-500"><?= $b->fecha_pago ? date('d/m/Y', strtotime($b->fecha_pago)) : '-' ?></td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span class="font-bold text-green-600"><?= $b->matched ?></span>
                                            <?php if ($b->unmatched > 0): ?>
                                            <span class="text-gray-300">/</span>
                                            <span class="font-bold text-red-500"><?= $b->unmatched ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span class="px-2 py-0.5 text-xs font-bold rounded-full <?= $stClass ?>"><?= $stLabel ?></span>
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <div class="inline-flex items-center gap-1">
                                                <a href="<?= base_url() ?>sisvent/admin/contrapagos/view/<?= $b->id ?>"
                                                   class="px-2.5 py-1 text-xs font-medium text-mam-blue-petroleo hover:text-white hover:bg-mam-blue-petroleo border border-mam-blue-petroleo rounded transition-colors">Ver</a>
                                                <?php if ($b->status === 'conciliado'): ?>
                                                <button onclick="abrirRegistro(<?= $b->id ?>, <?= $b->total_valor ?>, '<?= $b->fecha_pago ? date('d/m/Y', strtotime($b->fecha_pago)) : '' ?>', <?= $b->total_guias ?>, '<?= $b->fecha_pago ?: '' ?>')"
                                                    class="px-2.5 py-1 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 transition-colors">Registrar</button>
                                                <?php endif; ?>
                                                <?php if ($b->status === 'registrado'): ?>
                                                <button onclick="reversarLote(<?= $b->id ?>)"
                                                    class="px-2.5 py-1 text-xs font-medium text-orange-600 hover:text-white hover:bg-orange-500 border border-orange-400 rounded transition-colors">Reversar</button>
                                                <?php else: ?>
                                                <button onclick="eliminarLote(<?= $b->id ?>)"
                                                    class="px-2.5 py-1 text-xs font-medium text-red-500 hover:text-white hover:bg-red-500 border border-red-300 rounded transition-colors">Eliminar</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr><td colspan="8" class="px-4 py-12 text-center text-gray-300">
                                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        No hay importaciones
                                    </td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- Modal Registrar Pago -->
    <div id="registroModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-40" onclick="$('#registroModal').addClass('hidden')"></div>
            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md z-10 overflow-hidden">
                <!-- Modal header -->
                <div class="px-6 py-4 border-b" style="background:#1B365D;">
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wide">Registrar Pago</h3>
                    <p class="text-xs text-blue-200 mt-0.5">Lote <span id="regLoteId"></span> &middot; <span id="regGuias"></span> guias &middot; <span id="regFecha"></span></p>
                </div>
                <!-- Modal body -->
                <div class="px-6 py-5">
                    <input type="hidden" id="regBatchId">

                    <!-- Resumen -->
                    <div class="flex items-center justify-between p-3 mb-4 rounded-lg bg-green-50 border border-green-200">
                        <span class="text-xs text-green-600 uppercase font-medium">Valor bruto</span>
                        <span id="regBruto" class="text-lg font-bold text-green-700"></span>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs text-gray-400 uppercase tracking-wide mb-1">Fecha del Deposito</label>
                            <input type="date" id="regFechaDeposito" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:outline-none focus:border-mam-blue-petroleo focus:bg-white">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 uppercase tracking-wide mb-1">Cuenta Bancaria</label>
                            <select id="regBanco" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:outline-none focus:border-mam-blue-petroleo focus:bg-white">
                                <?php foreach ($bank_accounts as $ba): ?>
                                <option value="<?= $ba->idBankAccount ?>"><?= htmlspecialchars($ba->bankName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 uppercase tracking-wide mb-1">No. Movimiento Bancario</label>
                            <input type="text" id="regNumMov" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:outline-none focus:border-mam-blue-petroleo focus:bg-white" placeholder="Referencia o comprobante del banco">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 uppercase tracking-wide mb-1">Concepto</label>
                            <select id="regConcepto" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:outline-none focus:border-mam-blue-petroleo focus:bg-white">
                                <option value="contrapago">Contrapago (pago de guias)</option>
                                <option value="flete">Pago de fletes</option>
                                <option value="impuesto">Impuestos / Retenciones</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div id="regOtroWrap" class="hidden">
                            <label class="block text-xs text-gray-400 uppercase tracking-wide mb-1">Especifique</label>
                            <input type="text" id="regOtroConcepto" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:outline-none focus:border-mam-blue-petroleo focus:bg-white" placeholder="Descripcion del concepto">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 uppercase tracking-wide mb-1">Observaciones</label>
                            <input type="text" id="regObs" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:outline-none focus:border-mam-blue-petroleo focus:bg-white" placeholder="Opcional">
                        </div>
                    </div>

                    <div id="regResult" class="hidden mt-4 p-3 rounded-lg text-sm"></div>
                </div>
                <!-- Modal footer -->
                <div class="px-6 py-3 bg-gray-50 border-t flex gap-2 justify-end">
                    <button onclick="$('#registroModal').addClass('hidden')" class="px-4 py-2 text-xs font-medium text-gray-500 hover:text-gray-700">Cancelar</button>
                    <button onclick="confirmarRegistro()" id="btnRegistrar" class="px-5 py-2 text-xs font-bold text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">Registrar Pago</button>
                </div>
            </div>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    $(document).on('change', '#regConcepto', function() {
        $('#regOtroWrap').toggleClass('hidden', this.value !== 'otro');
    });

    function abrirRegistro(batchId, totalBruto, fecha, guias, fechaISO) {
        $('#regBatchId').val(batchId);
        $('#regLoteId').text('#' + batchId);
        $('#regBruto').text('$' + Number(totalBruto).toLocaleString('es-CO'));
        $('#regGuias').text(guias);
        $('#regFecha').text(fecha || 'Sin fecha');
        $('#regFechaDeposito').val(fechaISO || new Date().toISOString().split('T')[0]);
        $('#regNumMov').val('');
        $('#regConcepto').val('contrapago');
        $('#regOtroConcepto').val('');
        $('#regObs').val('');
        $('#regOtroWrap').addClass('hidden');
        $('#regResult').addClass('hidden');
        $('#registroModal').removeClass('hidden');
    }

    function confirmarRegistro() {
        var batchId = $('#regBatchId').val();
        var bankId = $('#regBanco').val();
        var concepto = $('#regConcepto').val();
        var otroConcepto = $('#regOtroConcepto').val().trim();

        if (!$('#regFechaDeposito').val()) { alert('Ingrese la fecha del deposito'); return; }
        if (!$('#regNumMov').val().trim()) { alert('Ingrese el numero de movimiento bancario'); return; }
        if (!bankId) { alert('Seleccione una cuenta bancaria'); return; }

        var btn = $('#btnRegistrar');
        btn.prop('disabled', true).text('Registrando...');
        $('#regResult').addClass('hidden');

        $.ajax({
            url: '<?= base_url() ?>sisvent/admin/contrapagos/registrarIngreso/' + batchId,
            type: 'POST',
            data: {
                '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>',
                bank_account_id: bankId,
                fecha_deposito: $('#regFechaDeposito').val(),
                numero_movimiento: $('#regNumMov').val().trim(),
                concepto: concepto === 'otro' ? otroConcepto : concepto,
                observaciones: $('#regObs').val().trim()
            },
            dataType: 'json',
            success: function(r) {
                btn.prop('disabled', false).text('Registrar Pago');
                if (r.success) {
                    $('#regResult').removeClass('hidden bg-red-50 text-red-700 border-red-200').addClass('bg-green-50 text-green-700 border border-green-200').html(r.message);
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    $('#regResult').removeClass('hidden bg-green-50 text-green-700 border-green-200').addClass('bg-red-50 text-red-700 border border-red-200').text(r.message);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).text('Registrar Pago');
                var msg = 'Error ' + xhr.status + ': ';
                try { var j = JSON.parse(xhr.responseText); msg += j.message || xhr.responseText.substring(0, 200); }
                catch(e) { msg += xhr.responseText ? xhr.responseText.substring(0, 300) : 'Sin respuesta del servidor'; }
                $('#regResult').removeClass('hidden').addClass('bg-red-50 text-red-700 border border-red-200').html(msg);
            }
        });
    }

    function reversarLote(batchId) {
        if (!confirm('Reversar este lote?\n\nSe eliminara el movimiento bancario y las facturas vuelven a pendiente.')) return;
        $.ajax({
            url: '<?= base_url() ?>sisvent/admin/contrapagos/reversar/' + batchId,
            type: 'POST',
            data: { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' },
            dataType: 'json',
            success: function(r) {
                if (r.success) { location.reload(); }
                else { alert(r.message); }
            },
            error: function() { alert('Error de conexion'); }
        });
    }

    function eliminarLote(batchId) {
        if (!confirm('Eliminar este lote y todas sus guias?')) return;
        $.ajax({
            url: '<?= base_url() ?>sisvent/admin/contrapagos/delete/' + batchId,
            type: 'POST',
            data: { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' },
            dataType: 'json',
            success: function(r) {
                if (r.success) { location.reload(); }
                else { alert(r.message); }
            },
            error: function() { alert('Error de conexion'); }
        });
    }
    </script>
</body>
</html>
