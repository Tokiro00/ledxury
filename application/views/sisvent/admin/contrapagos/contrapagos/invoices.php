<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$k = $kpi;
?>
<!DOCTYPE html>
<html lang="en">
    <title>Facturas Interrapidisimo - Ledxury</title>
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
                            <h2 class="text-lg font-semibold text-gray-700">Facturas Interrapidisimo</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Facturas de fletes que Inter nos cobra (archivos tipo CORTE)</p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/admin/contrapagos" class="mt-2 lg:mt-0 text-xs text-mam-blue-petroleo hover:underline">&larr; Pagos Contrapago</a>
                    </div>

                    <?php if ($this->session->flashdata('contrapago_error')): ?>
                    <div class="flex items-center p-3 mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg">
                        <?= $this->session->flashdata('contrapago_error') ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($this->session->flashdata('contrapago_success')): ?>
                    <div class="flex items-center p-3 mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg">
                        <?= $this->session->flashdata('contrapago_success') ?>
                    </div>
                    <?php endif; ?>

                    <!-- KPIs -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Facturas Pendientes</p>
                            <p class="text-xl font-bold mt-1 <?= $k['count_pendiente'] > 0 ? 'text-yellow-600' : 'text-gray-300' ?>"><?= $k['count_pendiente'] ?></p>
                        </div>
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Total Pendiente (a pagar Inter)</p>
                            <p class="text-xl font-bold text-red-600 mt-1">$<?= number_format($k['total_pendiente'], 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Facturas Descontadas</p>
                            <p class="text-xl font-bold text-green-600 mt-1"><?= $k['count_descontado'] ?></p>
                        </div>
                        <div class="bg-white rounded-lg border p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Total Descontado</p>
                            <p class="text-xl font-bold text-green-600 mt-1">$<?= number_format($k['total_descontado'], 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Upload -->
                    <div class="bg-white rounded-lg border p-5 mb-5">
                        <form action="<?= base_url() ?>sisvent/admin/contrapagos/uploadInvoice" method="POST" enctype="multipart/form-data" class="flex flex-col lg:flex-row items-start lg:items-end gap-4">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <div class="flex-1 w-full">
                                <label class="block text-xs text-gray-400 uppercase tracking-wide mb-1.5">Archivo CORTE de Interrapidisimo (.xlsx)</label>
                                <input type="file" name="excel_file" accept=".xlsx,.xls" required
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:outline-none focus:bg-white transition-colors">
                            </div>
                            <button type="submit" style="background:#4487A0;" class="inline-flex items-center flex-shrink-0 px-5 py-2.5 text-sm font-medium text-white rounded-lg hover:opacity-90 transition-opacity whitespace-nowrap">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                Importar Factura Inter
                            </button>
                        </form>
                        <p class="text-xs text-gray-300 mt-2">El numero de factura debe estar en la celda J1. Actualiza los fletes reales en las guias del sistema.</p>
                    </div>

                    <!-- Table -->
                    <div class="bg-white rounded-lg border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D;">
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wide">Factura</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wide">Fecha Corte</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-white uppercase tracking-wide">Guias</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-white uppercase tracking-wide">Transporte</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-white uppercase tracking-wide">Total</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wide">Estado</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wide">Pago Vinculado</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wide">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($invoices)): $i=0; foreach ($invoices as $inv): $i++;
                                        $stClass = 'bg-gray-100 text-gray-500';
                                        $stLabel = ucfirst($inv->status);
                                        if ($inv->status === 'pendiente') { $stClass = 'bg-yellow-100 text-yellow-700'; $stLabel = 'Pendiente'; }
                                        elseif ($inv->status === 'descontada') { $stClass = 'bg-green-100 text-green-700'; $stLabel = 'Descontada'; }
                                        elseif ($inv->status === 'pagada') { $stClass = 'bg-blue-100 text-blue-700'; $stLabel = 'Pagada'; }
                                    ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50 transition-colors">
                                        <td class="px-4 py-2.5 font-mono font-bold text-gray-700">#<?= $inv->numero_factura ?></td>
                                        <td class="px-4 py-2.5 text-gray-500"><?= $inv->fecha_corte ? date('d/m/Y', strtotime($inv->fecha_corte)) : '-' ?></td>
                                        <td class="px-4 py-2.5 text-right font-bold text-gray-700"><?= $inv->total_guias ?></td>
                                        <td class="px-4 py-2.5 text-right text-gray-600">$<?= number_format($inv->valor_transporte, 0, ',', '.') ?></td>
                                        <td class="px-4 py-2.5 text-right font-bold text-red-600">$<?= number_format($inv->valor_total, 0, ',', '.') ?></td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span class="px-2 py-0.5 text-xs font-bold rounded-full <?= $stClass ?>"><?= $stLabel ?></span>
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <?php if ($inv->descontada_en_batch_id): ?>
                                                <a href="<?= base_url() ?>sisvent/admin/contrapagos/view/<?= $inv->descontada_en_batch_id ?>" class="text-mam-blue-petroleo hover:underline font-medium">Pago #<?= $inv->descontada_en_batch_id ?></a>
                                            <?php else: ?>
                                                <span class="text-gray-300">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <div class="inline-flex items-center gap-1">
                                                <a href="<?= base_url() ?>sisvent/admin/contrapagos/invoiceDetail/<?= $inv->id ?>"
                                                   class="px-2.5 py-1 text-xs font-medium text-mam-blue-petroleo hover:text-white hover:bg-mam-blue-petroleo border border-mam-blue-petroleo rounded transition-colors">Ver</a>
                                                <?php if ($inv->status !== 'descontada'): ?>
                                                <button onclick="eliminarFactura(<?= $inv->id ?>)"
                                                    class="px-2.5 py-1 text-xs font-medium text-red-500 hover:text-white hover:bg-red-500 border border-red-300 rounded transition-colors">Eliminar</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr><td colspan="8" class="px-4 py-12 text-center text-gray-300">No hay facturas importadas</td></tr>
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
    function eliminarFactura(id) {
        if (!confirm('Eliminar esta factura y sus items?')) return;
        $.ajax({
            url: '<?= base_url() ?>sisvent/admin/contrapagos/deleteInvoice/' + id,
            type: 'POST',
            data: { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' },
            dataType: 'json',
            success: function(r) {
                if (r.success) location.reload();
                else alert(r.message);
            },
            error: function() { alert('Error de conexion'); }
        });
    }
    </script>
</body>
</html>
