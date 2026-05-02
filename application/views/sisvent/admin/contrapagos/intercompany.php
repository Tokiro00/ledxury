<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$bal = (float)$balance;
$balLabel = $bal >= 0 ? 'MAM debe a Ledxury' : 'Ledxury debe a MAM';
$balColor = $bal >= 0 ? 'text-green-600' : 'text-red-600';
?>
<!DOCTYPE html>
<html lang="en">
    <title>Cuentas Intercompañías - Ledxury</title>
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
                        <div>
                            <h2 class="text-lg font-semibold text-gray-700">Cuentas Intercompañías (Ledxury / MAM)</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Cobros y pagos pendientes entre las dos empresas</p>
                        </div>
                        <div class="flex items-center gap-2 mt-2 lg:mt-0">
                            <button onclick="openModal()" class="px-4 py-2 text-xs font-bold text-white bg-green-600 rounded-lg hover:bg-green-700">+ Nuevo Movimiento</button>
                            <a href="<?= base_url() ?>sisvent/admin/contrapagos" class="text-xs text-mam-blue-petroleo hover:underline">&larr; Pagos Contrapago</a>
                        </div>
                    </div>

                    <!-- KPI Cards -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
                        <div class="bg-white rounded-lg border p-5">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Cobros pendientes</p>
                            <p class="text-2xl font-bold text-gray-700 mt-1">$<?= number_format($stats->total_cobros_pendientes, 0, ',', '.') ?></p>
                            <p class="text-xs text-gray-400 mt-0.5"><?= $stats->count_cobros ?> movimientos</p>
                        </div>
                        <div class="bg-white rounded-lg border p-5">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Pagos recibidos</p>
                            <p class="text-2xl font-bold text-blue-600 mt-1">$<?= number_format($stats->total_pagos_recibidos, 0, ',', '.') ?></p>
                            <p class="text-xs text-gray-400 mt-0.5"><?= $stats->count_pagos ?> movimientos</p>
                        </div>
                        <div class="bg-white rounded-lg border-2 p-5 <?= $bal >= 0 ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50' ?>">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-bold">Saldo Neto</p>
                            <p class="text-2xl font-bold mt-1 <?= $balColor ?>">$<?= number_format(abs($bal), 0, ',', '.') ?></p>
                            <p class="text-xs <?= $balColor ?> mt-0.5 font-semibold"><?= $balLabel ?></p>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <form method="get" class="bg-white rounded-lg border p-4 mb-4">
                        <div class="flex flex-wrap items-end gap-3">
                            <div>
                                <label class="block text-xs text-gray-400 uppercase mb-1">Tipo</label>
                                <select name="tipo" class="text-sm border border-gray-200 rounded px-3 py-1.5">
                                    <option value="">Todos</option>
                                    <option value="cobro_pendiente" <?= $filter_tipo == 'cobro_pendiente' ? 'selected' : '' ?>>Cobro pendiente</option>
                                    <option value="pago_recibido" <?= $filter_tipo == 'pago_recibido' ? 'selected' : '' ?>>Pago recibido</option>
                                    <option value="ajuste" <?= $filter_tipo == 'ajuste' ? 'selected' : '' ?>>Ajuste</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 uppercase mb-1">Estado</label>
                                <select name="status" class="text-sm border border-gray-200 rounded px-3 py-1.5">
                                    <option value="activo" <?= $filter_status == 'activo' ? 'selected' : '' ?>>Activos</option>
                                    <option value="anulado" <?= $filter_status == 'anulado' ? 'selected' : '' ?>>Anulados</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 uppercase mb-1">Desde</label>
                                <input type="date" name="from" value="<?= htmlspecialchars($filter_from) ?>" class="text-sm border border-gray-200 rounded px-3 py-1.5">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 uppercase mb-1">Hasta</label>
                                <input type="date" name="to" value="<?= htmlspecialchars($filter_to) ?>" class="text-sm border border-gray-200 rounded px-3 py-1.5">
                            </div>
                            <button type="submit" class="px-4 py-1.5 text-sm text-white rounded" style="background:#4487A0;">Filtrar</button>
                            <a href="<?= base_url() ?>sisvent/admin/contrapagos/intercompany" class="text-xs text-gray-500 hover:underline">Limpiar</a>
                        </div>
                    </form>

                    <!-- Tabla -->
                    <div class="bg-white rounded-lg border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D;">
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase">Fecha</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase">Tipo</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase">Concepto</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase">Dirección</th>
                                        <th class="px-3 py-2.5 text-right text-xs font-semibold text-white uppercase">Monto</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase">Descripción</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-white uppercase">Mov #</th>
                                        <th class="px-3 py-2.5 text-center text-xs font-semibold text-white uppercase">Estado</th>
                                        <th class="px-3 py-2.5 text-center text-xs font-semibold text-white uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach ($movements as $m): $i++;
                                        $tipoLabel = array('cobro_pendiente'=>'Cobro pendiente','pago_recibido'=>'Pago recibido','ajuste'=>'Ajuste');
                                        $tipoClass = array(
                                            'cobro_pendiente'=>'bg-yellow-100 text-yellow-700',
                                            'pago_recibido'=>'bg-blue-100 text-blue-700',
                                            'ajuste'=>'bg-gray-100 text-gray-600'
                                        );
                                        $conceptoLabel = array(
                                            'flete_mam'=>'Flete MAM','contrapago_mam'=>'Contrapago MAM',
                                            'transferencia'=>'Transferencia','ajuste_manual'=>'Ajuste manual'
                                        );
                                        $direccionLabel = $m->direccion === 'mam_debe_ledxury' ? 'MAM → Ledxury' : 'Ledxury → MAM';
                                        $direccionClass = $m->direccion === 'mam_debe_ledxury' ? 'text-green-700' : 'text-red-700';
                                        $rowBg = $i % 2 == 0 ? 'bg-gray-50' : 'bg-white';
                                        if ($m->status === 'anulado') $rowBg = 'bg-gray-100 opacity-60';
                                    ?>
                                    <tr class="border-t <?= $rowBg ?> hover:bg-blue-50">
                                        <td class="px-3 py-2 text-gray-600"><?= date('d/m/Y', strtotime($m->fecha)) ?></td>
                                        <td class="px-3 py-2"><span class="px-2 py-0.5 rounded-full text-xs font-bold <?= $tipoClass[$m->tipo] ?>"><?= $tipoLabel[$m->tipo] ?></span></td>
                                        <td class="px-3 py-2 text-gray-700"><?= $conceptoLabel[$m->concepto] ?? $m->concepto ?></td>
                                        <td class="px-3 py-2 font-bold <?= $direccionClass ?>"><?= $direccionLabel ?></td>
                                        <td class="px-3 py-2 text-right font-bold text-gray-800">$<?= number_format($m->monto, 0, ',', '.') ?></td>
                                        <td class="px-3 py-2 text-gray-500 text-xs"><?= htmlspecialchars($m->descripcion) ?></td>
                                        <td class="px-3 py-2 font-mono text-gray-500"><?= htmlspecialchars($m->numero_movimiento) ?: '-' ?></td>
                                        <td class="px-3 py-2 text-center">
                                            <?php if ($m->status === 'anulado'): ?>
                                                <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-700">Anulado</span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-green-100 text-green-700">Activo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <?php if ($m->status === 'activo'): ?>
                                                <button onclick='openModal(<?= json_encode($m) ?>)' class="px-2 py-0.5 text-xs text-blue-600 hover:underline">Editar</button>
                                                <button onclick="anular(<?= $m->id ?>)" class="px-2 py-0.5 text-xs text-red-500 hover:underline">Anular</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($movements)): ?>
                                    <tr><td colspan="9" class="px-3 py-12 text-center text-gray-300">Sin movimientos</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- Modal Crear/Editar -->
    <div id="movModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-40" onclick="closeModal()"></div>
            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-lg z-10 overflow-hidden">
                <div class="px-6 py-4 border-b" style="background:#1B365D;">
                    <h3 class="text-sm font-semibold text-white uppercase" id="modalTitle">Nuevo Movimiento Intercompañías</h3>
                </div>
                <div class="px-6 py-5 space-y-3">
                    <input type="hidden" id="movId" value="0">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-400 uppercase mb-1">Tipo *</label>
                            <select id="movTipo" class="w-full text-sm border border-gray-200 rounded px-3 py-2">
                                <option value="pago_recibido">Pago recibido (transferencia)</option>
                                <option value="cobro_pendiente">Cobro pendiente (manual)</option>
                                <option value="ajuste">Ajuste</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 uppercase mb-1">Concepto *</label>
                            <select id="movConcepto" class="w-full text-sm border border-gray-200 rounded px-3 py-2">
                                <option value="transferencia">Transferencia</option>
                                <option value="flete_mam">Flete MAM</option>
                                <option value="contrapago_mam">Contrapago MAM</option>
                                <option value="ajuste_manual">Ajuste manual</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 uppercase mb-1">Dirección *</label>
                        <select id="movDireccion" class="w-full text-sm border border-gray-200 rounded px-3 py-2">
                            <option value="mam_debe_ledxury">MAM debe a Ledxury (entrada de dinero)</option>
                            <option value="ledxury_debe_mam">Ledxury debe a MAM (salida de dinero)</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-400 uppercase mb-1">Monto *</label>
                            <input type="number" id="movMonto" step="1" min="0" class="w-full text-sm border border-gray-200 rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 uppercase mb-1">Fecha *</label>
                            <input type="date" id="movFecha" class="w-full text-sm border border-gray-200 rounded px-3 py-2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 uppercase mb-1">Cuenta bancaria (solo para pago recibido)</label>
                        <select id="movBanco" class="w-full text-sm border border-gray-200 rounded px-3 py-2">
                            <option value="">— Sin banco —</option>
                            <?php foreach ($bank_accounts as $ba): ?>
                            <option value="<?= $ba->idBankAccount ?>"><?= htmlspecialchars($ba->bankName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 uppercase mb-1">No. movimiento bancario</label>
                        <input type="text" id="movNumero" class="w-full text-sm border border-gray-200 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 uppercase mb-1">Descripción</label>
                        <textarea id="movDesc" rows="2" class="w-full text-sm border border-gray-200 rounded px-3 py-2"></textarea>
                    </div>
                    <div id="movResult" class="hidden p-2 rounded text-sm"></div>
                </div>
                <div class="px-6 py-3 bg-gray-50 border-t flex gap-2 justify-end">
                    <button onclick="closeModal()" class="px-4 py-2 text-xs text-gray-500">Cancelar</button>
                    <button onclick="saveMov()" id="btnSave" class="px-5 py-2 text-xs font-bold text-white bg-green-600 rounded hover:bg-green-700">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    function openModal(data) {
        if (data) {
            $('#modalTitle').text('Editar movimiento #' + data.id);
            $('#movId').val(data.id);
            $('#movTipo').val(data.tipo);
            $('#movConcepto').val(data.concepto);
            $('#movDireccion').val(data.direccion);
            $('#movMonto').val(data.monto);
            $('#movFecha').val(data.fecha);
            $('#movBanco').val(data.bank_account_id || '');
            $('#movNumero').val(data.numero_movimiento || '');
            $('#movDesc').val(data.descripcion || '');
        } else {
            $('#modalTitle').text('Nuevo Movimiento Intercompañías');
            $('#movId').val(0);
            $('#movTipo').val('pago_recibido');
            $('#movConcepto').val('transferencia');
            $('#movDireccion').val('mam_debe_ledxury');
            $('#movMonto').val('');
            $('#movFecha').val(new Date().toISOString().split('T')[0]);
            $('#movBanco').val('');
            $('#movNumero').val('');
            $('#movDesc').val('');
        }
        $('#movResult').addClass('hidden');
        $('#movModal').removeClass('hidden');
    }
    function closeModal() { $('#movModal').addClass('hidden'); }

    function saveMov() {
        var btn = $('#btnSave');
        btn.prop('disabled', true).text('Guardando...');
        $('#movResult').addClass('hidden');
        $.post('<?= base_url() ?>sisvent/admin/contrapagos/intercompanySave', {
            '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>',
            id: $('#movId').val(),
            tipo: $('#movTipo').val(),
            concepto: $('#movConcepto').val(),
            direccion: $('#movDireccion').val(),
            monto: $('#movMonto').val(),
            fecha: $('#movFecha').val(),
            bank_account_id: $('#movBanco').val(),
            numero_movimiento: $('#movNumero').val().trim(),
            descripcion: $('#movDesc').val().trim()
        }, function(r) {
            btn.prop('disabled', false).text('Guardar');
            if (r.success) location.reload();
            else $('#movResult').removeClass('hidden').addClass('bg-red-50 text-red-700').text(r.message);
        }, 'json').fail(function(){
            btn.prop('disabled', false).text('Guardar');
            $('#movResult').removeClass('hidden').addClass('bg-red-50 text-red-700').text('Error de conexión');
        });
    }

    function anular(id) {
        if (!confirm('¿Anular este movimiento?\nSi tiene movimiento bancario asociado, también se revertirá.')) return;
        $.post('<?= base_url() ?>sisvent/admin/contrapagos/intercompanyDelete/' + id, {
            '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
        }, function(r) {
            if (r.success) location.reload();
            else alert(r.message);
        }, 'json').fail(function(){ alert('Error de conexión'); });
    }
    </script>
</body>
</html>
