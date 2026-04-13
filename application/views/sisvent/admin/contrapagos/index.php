<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
    <title>Conciliacion Contrapagos - Ledxury</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Conciliacion Contrapagos Interrapidisimo</h2>
                            <p class="text-sm text-gray-500">Importe el archivo de compensacion que envia Interrapidisimo</p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/admin/envios" class="text-sm text-blue-600 hover:underline">&larr; Dashboard Envios</a>
                    </div>

                    <?php if ($this->session->flashdata('contrapago_error')): ?>
                    <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg"><?= $this->session->flashdata('contrapago_error') ?></div>
                    <?php endif; ?>
                    <?php if ($this->session->flashdata('contrapago_success')): ?>
                    <div class="p-3 mb-4 text-sm text-green-700 bg-green-100 rounded-lg"><?= $this->session->flashdata('contrapago_success') ?></div>
                    <?php endif; ?>

                    <!-- Upload Card -->
                    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
                        <h3 class="text-sm font-bold text-gray-600 uppercase tracking-wide mb-3">Importar Archivo de Compensacion</h3>
                        <form action="<?= base_url() ?>sisvent/admin/contrapagos/upload" method="POST" enctype="multipart/form-data" class="flex items-end gap-4">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">Archivo Excel (.xlsx)</label>
                                <input type="file" name="excel_file" accept=".xlsx,.xls" required
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            </div>
                            <button type="submit" class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-white rounded-lg" style="background:#2E7D91;">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                Importar y Conciliar
                            </button>
                        </form>
                        <p class="text-xs text-gray-400 mt-2">El sistema lee todas las hojas del archivo, cruza automaticamente las guias con el sistema y calcula los totales.</p>
                    </div>

                    <!-- Batches Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="px-4 py-3 border-b">
                            <h3 class="text-sm font-bold text-gray-600">Historial de Importaciones</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs font-medium text-gray-500 uppercase bg-gray-50">
                                        <th class="px-4 py-3 text-left">#</th>
                                        <th class="px-4 py-3 text-left">Archivo</th>
                                        <th class="px-4 py-3 text-left">Hoja</th>
                                        <th class="px-4 py-3 text-right">Guias</th>
                                        <th class="px-4 py-3 text-right">Total</th>
                                        <th class="px-4 py-3 text-center">Fecha Pago</th>
                                        <th class="px-4 py-3 text-left">Banco</th>
                                        <th class="px-4 py-3 text-center">Cruzadas</th>
                                        <th class="px-4 py-3 text-center">Sin Match</th>
                                        <th class="px-4 py-3 text-center">Estado</th>
                                        <th class="px-4 py-3 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if (!empty($batches)): foreach ($batches as $b): ?>
                                    <?php
                                        $stClass = 'bg-gray-100 text-gray-600';
                                        $stLabel = $b->status;
                                        if ($b->status === 'importado') { $stClass = 'bg-yellow-100 text-yellow-700'; $stLabel = 'Importado'; }
                                        elseif ($b->status === 'conciliado') { $stClass = 'bg-blue-100 text-blue-700'; $stLabel = 'Conciliado'; }
                                        elseif ($b->status === 'registrado') { $stClass = 'bg-green-100 text-green-700'; $stLabel = 'Registrado'; }
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium"><?= $b->id ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($b->filename) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($b->sheet_name) ?></td>
                                        <td class="px-4 py-3 text-right font-bold"><?= $b->total_guias ?></td>
                                        <?php $neto4x1000 = $b->total_valor - round($b->total_valor * 0.004); ?>
                                        <td class="px-4 py-3 text-right">
                                            <span class="font-bold text-green-600">$<?= number_format($neto4x1000, 0, ',', '.') ?></span>
                                            <div class="text-xs text-gray-400">Bruto: $<?= number_format($b->total_valor, 0, ',', '.') ?></div>
                                        </td>
                                        <td class="px-4 py-3 text-center"><?= $b->fecha_pago ? date('d/m/Y', strtotime($b->fecha_pago)) : '-' ?></td>
                                        <td class="px-4 py-3"><?= $b->banco ?: '-' ?></td>
                                        <td class="px-4 py-3 text-center font-bold text-green-600"><?= $b->matched ?></td>
                                        <td class="px-4 py-3 text-center font-bold <?= $b->unmatched > 0 ? 'text-red-600' : 'text-gray-400' ?>"><?= $b->unmatched ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $stClass ?>"><?= $stLabel ?></span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex gap-1 justify-center">
                                                <a href="<?= base_url() ?>sisvent/admin/contrapagos/view/<?= $b->id ?>"
                                                   class="px-3 py-1 text-xs font-medium text-white rounded" style="background:#1B365D;">Ver</a>
                                                <?php if ($b->status === 'conciliado'): ?>
                                                <select id="bankSelect_<?= $b->id ?>" class="px-2 py-1 text-xs border rounded">
                                                    <?php foreach ($bank_accounts as $ba): ?>
                                                    <option value="<?= $ba->idBankAccount ?>"><?= htmlspecialchars($ba->bankName) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button onclick="registrarIngreso(<?= $b->id ?>)"
                                                    class="px-3 py-1 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700">Registrar</button>
                                                <?php endif; ?>
                                                <?php if ($b->status === 'registrado'): ?>
                                                <button onclick="reversarLote(<?= $b->id ?>)"
                                                    class="px-3 py-1 text-xs font-medium text-white bg-orange-500 rounded hover:bg-orange-600">Reversar</button>
                                                <?php else: ?>
                                                <button onclick="eliminarLote(<?= $b->id ?>)"
                                                    class="px-3 py-1 text-xs font-medium text-white bg-red-500 rounded hover:bg-red-600">Eliminar</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr><td colspan="11" class="px-4 py-8 text-center text-gray-400">No hay importaciones. Sube un archivo de compensacion para comenzar.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    function reversarLote(batchId) {
        if (!confirm('¿Reversar este lote?\n\nEsto eliminará el movimiento bancario y devolverá las facturas a estado Pendiente.')) return;

        $.ajax({
            url: '<?= base_url() ?>sisvent/admin/contrapagos/reversar/' + batchId,
            type: 'POST',
            data: { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' },
            dataType: 'json',
            success: function(r) {
                if (r.success) { alert(r.message); location.reload(); }
                else { alert('Error: ' + r.message); }
            },
            error: function() { alert('Error de conexión'); }
        });
    }

    function eliminarLote(batchId) {
        if (!confirm('¿Eliminar este lote y todas sus guías importadas?')) return;

        $.ajax({
            url: '<?= base_url() ?>sisvent/admin/contrapagos/delete/' + batchId,
            type: 'POST',
            data: { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' },
            dataType: 'json',
            success: function(r) {
                if (r.success) { alert(r.message); location.reload(); }
                else { alert('Error: ' + r.message); }
            },
            error: function() { alert('Error de conexión'); }
        });
    }

    function registrarIngreso(batchId) {
        var bankId = $('#bankSelect_' + batchId).val();
        var bankName = $('#bankSelect_' + batchId).find('option:selected').text();
        if (!confirm('¿Registrar este pago como ingreso en ' + bankName + '?')) return;

        var btn = event.target;
        btn.disabled = true;
        btn.textContent = 'Registrando...';

        $.ajax({
            url: '<?= base_url() ?>sisvent/admin/contrapagos/registrarIngreso/' + batchId,
            type: 'POST',
            data: { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>', bank_account_id: bankId },
            dataType: 'json',
            success: function(r) {
                if (r.success) {
                    alert(r.message);
                    location.reload();
                } else {
                    alert('Error: ' + r.message);
                    btn.disabled = false;
                    btn.textContent = 'Registrar en Banco';
                }
            },
            error: function() {
                alert('Error de conexión');
                btn.disabled = false;
                btn.textContent = 'Registrar en Banco';
            }
        });
    }
    </script>
</body>
</html>
