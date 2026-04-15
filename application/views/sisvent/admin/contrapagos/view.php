<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$stClass = 'bg-gray-100 text-gray-500';
$stLabel = ucfirst($batch->status);
if ($batch->status === 'importado') { $stClass = 'bg-yellow-100 text-yellow-700'; $stLabel = 'Importado'; }
elseif ($batch->status === 'conciliado') { $stClass = 'bg-blue-100 text-blue-700'; $stLabel = 'Conciliado'; }
elseif ($batch->status === 'registrado') { $stClass = 'bg-green-100 text-green-700'; $stLabel = 'Registrado'; }
$neto = $batch->total_valor - round($batch->total_valor * 0.004);
?>
<!DOCTYPE html>
<html lang="en">
    <title>Pago #<?= $batch->id ?> - Ledxury</title>
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
                                <h2 class="text-lg font-semibold text-gray-700">Pago #<?= $batch->id ?></h2>
                                <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($batch->filename) ?> &middot; <?= $batch->sheet_name ?></p>
                            </div>
                            <span class="px-2.5 py-0.5 text-xs font-bold rounded-full <?= $stClass ?>"><?= $stLabel ?></span>
                        </div>
                        <div class="flex items-center gap-2 mt-3 lg:mt-0">
                            <?php if ($batch->status === 'conciliado'): ?>
                            <button onclick="abrirRegistro()"
                                class="px-4 py-2 text-xs font-bold text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">Registrar Pago</button>
                            <?php endif; ?>
                            <?php if ($batch->status !== 'registrado'): ?>
                            <button onclick="eliminarLote()"
                                class="px-4 py-2 text-xs font-medium text-red-500 hover:text-white hover:bg-red-500 border border-red-300 rounded-lg transition-colors">Eliminar Lote</button>
                            <?php endif; ?>
                            <?php if ($batch->status === 'registrado'): ?>
                            <button onclick="reversarLote()"
                                class="px-4 py-2 text-xs font-medium text-orange-600 hover:text-white hover:bg-orange-500 border border-orange-400 rounded-lg transition-colors">Reversar</button>
                            <?php endif; ?>
                            <a href="<?= base_url() ?>sisvent/admin/contrapagos" class="px-4 py-2 text-xs text-gray-500 hover:text-gray-700">&larr; Volver</a>
                        </div>
                    </div>

                    <!-- KPI Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Recaudado</p>
                            <p class="text-xl font-bold text-gray-700 mt-1">$<?= number_format($batch->total_valor, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Neto (-4x1000)</p>
                            <p class="text-xl font-bold text-green-600 mt-1">$<?= number_format($neto, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Guias</p>
                            <p class="text-xl font-bold text-gray-700 mt-1"><?= $batch->total_guias ?></p>
                        </div>
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Cruzadas</p>
                            <p class="text-xl font-bold text-green-600 mt-1"><?= $batch->matched ?></p>
                        </div>
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Sin Match</p>
                            <p class="text-xl font-bold mt-1 <?= $batch->unmatched > 0 ? 'text-red-500' : 'text-gray-300' ?>"><?= $batch->unmatched ?></p>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="bg-white rounded-lg border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D;">
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase tracking-wide">Guia</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase tracking-wide">Destinatario</th>
                                        <th class="px-3 py-2.5 text-center text-xs font-semibold text-white uppercase tracking-wide">Fecha</th>
                                        <th class="px-3 py-2.5 text-right text-xs font-semibold text-white uppercase tracking-wide">Valor</th>
                                        <th class="px-3 py-2.5 text-center text-xs font-semibold text-white uppercase tracking-wide">Conciliacion</th>
                                        <th class="px-3 py-2.5 text-center text-xs font-semibold text-white uppercase tracking-wide">Factura</th>
                                        <th class="px-3 py-2.5 text-center text-xs font-semibold text-white uppercase tracking-wide">Estado</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase tracking-wide">Obs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach ($payments as $p): $i++;
                                        $rowBg = $i % 2 == 0 ? 'bg-gray-50' : 'bg-white';
                                        $stBadge = 'bg-gray-100 text-gray-500';
                                        if ($p->status === 'conciliado') { $stBadge = 'bg-green-100 text-green-700'; }
                                        elseif ($p->status === 'sin_match') { $stBadge = 'bg-red-100 text-red-600'; $rowBg = 'bg-red-50'; }
                                    ?>
                                    <tr class="border-t <?= $rowBg ?> hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-2 font-mono font-medium text-gray-700"><?= $p->numeroGuia ?></td>
                                        <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($p->nombreDestinatario) ?></td>
                                        <td class="px-3 py-2 text-center text-gray-500"><?= $p->fechaVenta ? date('d/m/Y', strtotime($p->fechaVenta)) : '-' ?></td>
                                        <td class="px-3 py-2 text-right font-bold text-gray-800">$<?= number_format($p->valorTotal, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-center text-gray-500"><?= $p->conciliacion ?: '-' ?></td>
                                        <td class="px-3 py-2 text-center">
                                            <?php if ($p->invoice_id): ?>
                                                <a href="<?= base_url() ?>sisvent/commercial/invoices/view/<?= $p->invoice_id ?>" class="text-mam-blue-petroleo hover:underline font-medium">#<?= $p->invoice_id ?></a>
                                            <?php else: ?>
                                                <span class="text-gray-300">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="px-2 py-0.5 text-xs font-bold rounded-full <?= $stBadge ?>"><?= ucfirst(str_replace('_', ' ', $p->status)) ?></span>
                                        </td>
                                        <td class="px-3 py-2 text-gray-400"><?= $p->observacion ? htmlspecialchars($p->observacion) : '' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-100 border-t-2 border-gray-300">
                                        <td class="px-3 py-2.5 font-bold text-gray-500 uppercase text-right" colspan="3">Total</td>
                                        <td class="px-3 py-2.5 text-right font-bold text-green-700">$<?= number_format($batch->total_valor, 0, ',', '.') ?></td>
                                        <td colspan="4"></td>
                                    </tr>
                                </tfoot>
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
                <div class="px-6 py-4 border-b" style="background:#1B365D;">
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wide">Registrar Pago</h3>
                    <p class="text-xs text-blue-200 mt-0.5">Lote #<?= $batch->id ?> &middot; <?= $batch->total_guias ?> guias &middot; <?= $batch->fecha_pago ? date('d/m/Y', strtotime($batch->fecha_pago)) : '' ?></p>
                </div>
                <div class="px-6 py-5">
                    <div class="flex items-center justify-between p-3 mb-4 rounded-lg bg-green-50 border border-green-200">
                        <span class="text-xs text-green-600 uppercase font-medium">Valor bruto</span>
                        <span class="text-lg font-bold text-green-700">$<?= number_format($batch->total_valor, 0, ',', '.') ?></span>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs text-gray-400 uppercase tracking-wide mb-1">Fecha del Deposito</label>
                            <input type="date" id="regFechaDeposito" value="<?= $batch->fecha_pago ?: date('Y-m-d') ?>" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:outline-none focus:border-mam-blue-petroleo focus:bg-white">
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
                            <input type="text" id="regNumMov" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:outline-none focus:border-mam-blue-petroleo focus:bg-white" placeholder="Referencia o comprobante">
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
                            <input type="text" id="regOtroConcepto" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:outline-none focus:border-mam-blue-petroleo focus:bg-white">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 uppercase tracking-wide mb-1">Observaciones</label>
                            <input type="text" id="regObs" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 focus:outline-none focus:border-mam-blue-petroleo focus:bg-white" placeholder="Opcional">
                        </div>
                    </div>
                    <div id="regResult" class="hidden mt-4 p-3 rounded-lg text-sm"></div>
                </div>
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

    function abrirRegistro() {
        $('#regNumMov').val('');
        $('#regConcepto').val('contrapago');
        $('#regOtroConcepto').val('');
        $('#regObs').val('');
        $('#regOtroWrap').addClass('hidden');
        $('#regResult').addClass('hidden');
        $('#registroModal').removeClass('hidden');
    }

    function confirmarRegistro() {
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
            url: '<?= base_url() ?>sisvent/admin/contrapagos/registrarIngreso/<?= $batch->id ?>',
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
                    $('#regResult').removeClass('hidden bg-red-50 text-red-700').addClass('bg-green-50 text-green-700 border border-green-200').html(r.message);
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    $('#regResult').removeClass('hidden bg-green-50 text-green-700').addClass('bg-red-50 text-red-700 border border-red-200').text(r.message);
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

    function eliminarLote() {
        if (!confirm('Eliminar este lote y todas sus guias?')) return;
        $.ajax({
            url: '<?= base_url() ?>sisvent/admin/contrapagos/delete/<?= $batch->id ?>',
            type: 'POST',
            data: { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' },
            dataType: 'json',
            success: function(r) {
                if (r.success) { window.location.href = '<?= base_url() ?>sisvent/admin/contrapagos'; }
                else { alert(r.message); }
            },
            error: function() { alert('Error de conexion'); }
        });
    }

    function reversarLote() {
        if (!confirm('Reversar este lote?\n\nSe eliminara el movimiento bancario y las facturas vuelven a pendiente.')) return;
        $.ajax({
            url: '<?= base_url() ?>sisvent/admin/contrapagos/reversar/<?= $batch->id ?>',
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
