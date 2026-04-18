<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
    <title>Entre Compañías - Ledxury</title>
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
                            <h2 class="text-lg font-semibold text-gray-700">Entre Compañías</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Saldo entre Ledxury y MAM por operaciones de Interrapidisimo</p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/admin/contrapagos" class="mt-2 lg:mt-0 text-xs text-mam-blue-petroleo hover:underline">&larr; Pagos Contrapago</a>
                    </div>

                    <!-- Balance Principal -->
                    <div class="bg-white rounded-lg border p-6 mb-5">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Ledxury recibió de MAM -->
                            <div class="text-center border-r-0 lg:border-r border-gray-200">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Ledxury cobró para MAM</p>
                                <p class="text-xs text-gray-400">Contrapagos de clientes MAM</p>
                                <p class="text-2xl font-bold text-green-600 mt-2">$<?= number_format($mam_cobrado_total, 0, ',', '.') ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?= $mam_cobrado_count ?> guías</p>
                            </div>
                            <!-- Ledxury pagó por MAM -->
                            <div class="text-center border-r-0 lg:border-r border-gray-200">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Ledxury pagó por MAM</p>
                                <p class="text-xs text-gray-400">Fletes de guías MAM</p>
                                <p class="text-2xl font-bold text-red-600 mt-2">$<?= number_format($mam_fletes_total, 0, ',', '.') ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?= $mam_fletes_count ?> guías</p>
                            </div>
                            <!-- Balance -->
                            <div class="text-center">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Balance</p>
                                <p class="text-xs text-gray-400"><?= $balance_neto >= 0 ? 'Ledxury debe a MAM' : 'MAM debe a Ledxury' ?></p>
                                <p class="text-2xl font-bold mt-2 <?= $balance_neto >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                    $<?= number_format(abs($balance_neto), 0, ',', '.') ?>
                                </p>
                                <p class="text-xs mt-1 text-gray-400">
                                    <?= $balance_neto >= 0 ? 'A transferir a MAM' : 'MAM debe reembolsar' ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Pagos sin match -->
                    <?php if (!empty($pendientes_payments)): ?>
                    <div class="bg-white rounded-lg border overflow-hidden mb-5">
                        <div class="px-4 py-3 border-b bg-yellow-50">
                            <h3 class="text-sm font-bold text-yellow-800">Guías en pagos sin match (<?= count($pendientes_payments) ?>)</h3>
                            <p class="text-xs text-yellow-600 mt-0.5">Posiblemente guías de MAM. Marca cuáles son para incluir en el cálculo.</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D;">
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-white uppercase">Guía</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-white uppercase">Destinatario</th>
                                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-white uppercase">Valor</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-white uppercase">Pago</th>
                                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-white uppercase">Empresa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($pendientes_payments as $p): $i++; ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?>">
                                        <td class="px-4 py-2 font-mono"><?= $p->numeroGuia ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($p->nombreDestinatario) ?></td>
                                        <td class="px-4 py-2 text-right font-bold text-green-600">$<?= number_format($p->valorTotal, 0, ',', '.') ?></td>
                                        <td class="px-4 py-2"><a href="<?= base_url() ?>sisvent/admin/contrapagos/view/<?= $p->batch_id ?>" class="text-mam-blue-petroleo hover:underline">Pago #<?= $p->batch_id ?></a></td>
                                        <td class="px-4 py-2 text-center">
                                            <select onchange="markCompany('payment', <?= $p->id ?>, this.value, this)" class="text-xs border border-gray-200 rounded px-2 py-1">
                                                <option value="">—</option>
                                                <option value="mam" <?= $p->company === 'mam' ? 'selected' : '' ?>>Es de MAM</option>
                                                <option value="ledxury" <?= $p->company === 'ledxury' ? 'selected' : '' ?>>Es de Ledxury</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Invoice items sin match -->
                    <?php if (!empty($pendientes_invoices)): ?>
                    <div class="bg-white rounded-lg border overflow-hidden mb-5">
                        <div class="px-4 py-3 border-b bg-yellow-50">
                            <h3 class="text-sm font-bold text-yellow-800">Guías en facturas Inter sin match (<?= count($pendientes_invoices) ?>)</h3>
                            <p class="text-xs text-yellow-600 mt-0.5">Fletes que Ledxury pagó pero la guía no está en el sistema. Posiblemente de MAM.</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr style="background:#1B365D;">
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-white uppercase">Guía</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-white uppercase">Destino</th>
                                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-white uppercase">Flete</th>
                                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-white uppercase">Total</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-white uppercase">Factura</th>
                                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-white uppercase">Empresa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($pendientes_invoices as $it): $i++; ?>
                                    <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?>">
                                        <td class="px-4 py-2 font-mono"><?= $it->numero_guia ?></td>
                                        <td class="px-4 py-2 text-gray-600"><?= htmlspecialchars($it->ciudad_destino) ?></td>
                                        <td class="px-4 py-2 text-right text-red-600">$<?= number_format($it->valor_transporte, 0, ',', '.') ?></td>
                                        <td class="px-4 py-2 text-right font-bold">$<?= number_format($it->valor_total, 0, ',', '.') ?></td>
                                        <td class="px-4 py-2"><a href="<?= base_url() ?>sisvent/admin/contrapagos/invoiceDetail/<?= $it->invoice_id ?>" class="text-mam-blue-petroleo hover:underline">#<?= $it->numero_factura ?></a></td>
                                        <td class="px-4 py-2 text-center">
                                            <select onchange="markCompany('invoice_item', <?= $it->id ?>, this.value, this)" class="text-xs border border-gray-200 rounded px-2 py-1">
                                                <option value="">—</option>
                                                <option value="mam" <?= $it->company === 'mam' ? 'selected' : '' ?>>Es de MAM</option>
                                                <option value="ledxury" <?= $it->company === 'ledxury' ? 'selected' : '' ?>>Es de Ledxury</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($pendientes_payments) && empty($pendientes_invoices)): ?>
                    <div class="bg-white rounded-lg border p-8 text-center text-gray-400">
                        <svg class="w-10 h-10 mx-auto text-green-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Todas las guías están asignadas
                    </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    function markCompany(table, id, company, el) {
        if (!company) return;
        $(el).prop('disabled', true);
        $.ajax({
            url: '<?= base_url() ?>sisvent/admin/contrapagos/markCompany',
            type: 'POST',
            data: {
                '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>',
                table: table, id: id, company: company
            },
            dataType: 'json',
            success: function(r) {
                $(el).prop('disabled', false);
                if (r.success) {
                    $(el).css('border-color', '#10B981').css('background', '#D1FAE5');
                    setTimeout(function() { location.reload(); }, 600);
                } else {
                    alert(r.message || 'Error');
                }
            },
            error: function() {
                $(el).prop('disabled', false);
                alert('Error de conexion');
            }
        });
    }
    </script>
</body>
</html>
