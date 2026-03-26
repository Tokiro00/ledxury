<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Cuentas por Cobrar</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <h2 class="text-lg font-semibold text-gray-600">
                            Cuentas por Cobrar <span class="text-sm font-normal text-gray-400">(<?php echo number_format($total); ?> facturas pendientes)</span>
                        </h2>
                        <div class="flex gap-2">
                            <a href="<?php echo base_url(); ?>sisvent/admin/accountsreceivable/byClient<?php echo ($filter_store || $filter_vendor) ? '?' . http_build_query(array_filter(['store' => $filter_store, 'vendor' => $filter_vendor])) : ''; ?>" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue-petroleo-hover">
                                Ver por Cliente
                            </a>
                        </div>
                    </div>

                    <?php if($this->session->flashdata("success")): ?>
                    <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-green-600 rounded-lg shadow-md">
                        <p><?php echo $this->session->flashdata("success"); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if($this->session->flashdata("error")): ?>
                    <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                        <p><?php echo $this->session->flashdata("error"); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Aging Summary Cards -->
                    <?php
                        $pct90 = $aging['total'] > 0 ? ($aging['days_91_plus'] / $aging['total']) * 100 : 0;
                        $pct6190 = $aging['total'] > 0 ? ($aging['days_61_90'] / $aging['total']) * 100 : 0;
                        $pct3160 = $aging['total'] > 0 ? ($aging['days_31_60'] / $aging['total']) * 100 : 0;
                        $pct030 = $aging['total'] > 0 ? ($aging['current'] / $aging['total']) * 100 : 0;
                    ?>
                    <div class="grid gap-3 mb-4 grid-cols-2 lg:grid-cols-6">
                        <!-- Total -->
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-600 p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase">Cartera Total</p>
                            <p class="text-xl font-bold text-blue-700">$<?php echo number_format($aging['total'], 0, ',', '.'); ?></p>
                            <p class="text-xs text-gray-400"><?php echo $aging['count_total']; ?> facturas</p>
                        </div>

                        <!-- % +90 días -->
                        <div class="bg-white rounded-lg shadow-sm border-l-4 <?= $pct90 > 40 ? 'border-red-600' : 'border-orange-500' ?> p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase">% Cartera +90d</p>
                            <p class="text-xl font-bold <?= $pct90 > 40 ? 'text-red-700' : 'text-orange-600' ?>"><?= number_format($pct90, 1) ?>%</p>
                            <p class="text-xs text-gray-400">$<?= number_format($aging['days_91_plus'], 0, ',', '.') ?></p>
                        </div>

                        <!-- Al día (0-30) -->
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase">0-30 dias</p>
                            <p class="text-lg font-bold text-green-700">$<?php echo number_format($aging['current'], 0, ',', '.'); ?></p>
                            <p class="text-xs text-gray-400"><?= number_format($pct030, 1) ?>% - <?= $aging['count_current'] ?> fact.</p>
                        </div>

                        <!-- 31-60 días -->
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-yellow-500 p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase">31-60 dias</p>
                            <p class="text-lg font-bold text-yellow-700">$<?php echo number_format($aging['days_31_60'], 0, ',', '.'); ?></p>
                            <p class="text-xs text-gray-400"><?= number_format($pct3160, 1) ?>% - <?= $aging['count_31_60'] ?> fact.</p>
                        </div>

                        <!-- 61-90 días -->
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-orange-500 p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase">61-90 dias</p>
                            <p class="text-lg font-bold text-orange-700">$<?php echo number_format($aging['days_61_90'], 0, ',', '.'); ?></p>
                            <p class="text-xs text-gray-400"><?= number_format($pct6190, 1) ?>% - <?= $aging['count_61_90'] ?> fact.</p>
                        </div>

                        <!-- +90 días -->
                        <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-600 p-4" style="<?= $pct90 > 40 ? 'background:rgba(220,38,38,0.05)' : '' ?>">
                            <p class="text-xs font-medium text-gray-500 uppercase">+90 dias</p>
                            <p class="text-lg font-bold text-red-700">$<?php echo number_format($aging['days_91_plus'], 0, ',', '.'); ?></p>
                            <p class="text-xs text-red-500 font-semibold"><?= $aging['count_91_plus'] ?> facturas criticas</p>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
                        <form method="GET" action="<?php echo base_url(); ?>sisvent/admin/accountsreceivable" class="flex flex-wrap gap-4 items-end">
                            <div class="flex-1 min-w-48">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Cliente</label>
                                <select name="client" class="form-input form-select w-full">
                                    <option value="">Todos los clientes</option>
                                    <?php foreach($clients as $client): ?>
                                        <option value="<?php echo $client->idClient; ?>" <?php echo $filter_client == $client->idClient ? 'selected' : ''; ?>><?php echo $client->name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex-1 min-w-48">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Bodega</label>
                                <select name="store" class="form-input form-select w-full">
                                    <option value="">Todas las bodegas</option>
                                    <?php foreach($stores as $store): ?>
                                        <option value="<?php echo $store->idStore; ?>" <?php echo $filter_store == $store->idStore ? 'selected' : ''; ?>><?php echo $store->name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex-1 min-w-48">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Vendedor</label>
                                <select name="vendor" class="form-input form-select w-full">
                                    <option value="">Todos los vendedores</option>
                                    <?php foreach($vendors as $vendor): ?>
                                        <option value="<?php echo $vendor->idUser; ?>" <?php echo $filter_vendor == $vendor->idUser ? 'selected' : ''; ?>><?php echo $vendor->name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue-petroleo-hover">
                                    Filtrar
                                </button>
                                <a href="<?php echo base_url(); ?>sisvent/admin/accountsreceivable" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                                    Limpiar
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Receivables Table -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs bg-white">
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3"># Factura</th>
                                        <th class="px-4 py-3">Cliente</th>
                                        <th class="px-4 py-3">Vendedor</th>
                                        <th class="px-4 py-3">Bodega</th>
                                        <th class="px-4 py-3 text-right">Total</th>
                                        <th class="px-4 py-3 text-right">Abonado</th>
                                        <th class="px-4 py-3 text-right">Saldo</th>
                                        <th class="px-4 py-3">Fecha</th>
                                        <th class="px-4 py-3 text-center">Días</th>
                                        <th class="px-4 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php if(!empty($receivables)): ?>
                                        <?php foreach($receivables as $key => $inv): ?>
                                            <?php
                                                $days = $inv->days_overdue;
                                                if ($days <= 30) {
                                                    $rowClass = 'bg-green-50';
                                                    $badgeClass = 'bg-green-100 text-green-800';
                                                } else if ($days <= 60) {
                                                    $rowClass = 'bg-yellow-50';
                                                    $badgeClass = 'bg-yellow-100 text-yellow-800';
                                                } else if ($days <= 90) {
                                                    $rowClass = 'bg-orange-50';
                                                    $badgeClass = 'bg-orange-100 text-orange-800';
                                                } else {
                                                    $rowClass = 'bg-red-50';
                                                    $badgeClass = 'bg-red-100 text-red-800';
                                                }
                                            ?>
                                            <tr class="text-gray-700 <?php echo $rowClass; ?>">
                                                <td class="px-4 py-3 text-sm font-mono">
                                                    <a href="<?php echo base_url(); ?>sisvent/commercial/invoices/view/<?php echo $inv->idInvoice; ?>" class="text-blue-600 hover:underline">
                                                        #<?php echo str_pad($inv->idInvoice, 6, '0', STR_PAD_LEFT); ?>
                                                    </a>
                                                    <?php if(!empty($inv->comments) && $inv->comments === 'BALANCE INICIAL'): ?>
                                                        <span class="ml-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">Saldo Inicial</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <p class="font-semibold text-sm"><?php echo $inv->client_name; ?></p>
                                                    <p class="text-xs text-gray-500"><?php echo $inv->client_idNum; ?></p>
                                                </td>
                                                <td class="px-4 py-3 text-sm"><?php echo $inv->vendor_name; ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo $inv->store_name; ?></td>
                                                <td class="px-4 py-3 text-sm text-right">$<?php echo number_format($inv->total, 2); ?></td>
                                                <td class="px-4 py-3 text-sm text-right text-green-600">$<?php echo number_format($inv->payment, 2); ?></td>
                                                <td class="px-4 py-3 text-sm text-right font-bold text-blue-600">$<?php echo number_format($inv->balance, 2); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo date('d/m/Y', strtotime($inv->date)); ?></td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $badgeClass; ?>">
                                                        <?php echo $days; ?> días
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center space-x-2">
                                                        <button value="<?php echo $inv->idInvoice; ?>" class="btn-view-invoice p-2 text-blue-600 hover:bg-blue-100 rounded" title="Ver Factura">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                        </button>
                                                        <button value="<?php echo $inv->idInvoice; ?>" class="btn-quick-pay px-3 py-1 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700" title="Abonar">
                                                            Abonar
                                                        </button>
                                                        <?php if(!empty($inv->client_cellphone)): ?>
                                                        <a href="https://wa.me/57<?php echo preg_replace('/[^0-9]/', '', $inv->client_cellphone); ?>" target="_blank" class="p-2 text-green-600 hover:bg-green-100 rounded" title="WhatsApp">
                                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                                No hay cuentas por cobrar pendientes
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if($total > 0): ?>
                        <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t bg-gray-50 sm:grid-cols-9">
                            <span class="flex items-center col-span-3">
                                <?php $last = ceil($total / $limit); ?>
                                Mostrando <?php echo ((($page-1) * $limit)+1).'-'.(($last == $page) ? ($total) : ((($page-1) * $limit)+$limit)).' de '.($total); ?>
                            </span>
                            <span class="col-span-2"></span>
                            <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
                                <nav aria-label="Table navigation">
                                    <?php
                                        $queryParams = array_filter([
                                            'client' => $filter_client,
                                            'store' => $filter_store,
                                            'vendor' => $filter_vendor
                                        ]);
                                        $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
                                        echo createLinks($page, $total, $queryString, $limit);
                                    ?>
                                </nav>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <!-- Payment Modal Overlay -->
    <div id="ar-payment-modal" class="fixed inset-0 z-50 hidden" style="background:rgba(0,0,0,0.5);">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg relative">
                <div class="flex items-center justify-between px-4 py-3 border-b" style="background:#1B365D;">
                    <h3 class="text-sm font-semibold text-white">Registrar Abono</h3>
                    <button id="ar-modal-close" class="text-white hover:text-gray-300 text-xl">&times;</button>
                </div>
                <div id="ar-payment-form-container" class="p-4">
                    <p class="text-center text-gray-500 py-8">Cargando...</p>
                </div>
            </div>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    (function() {
        var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';

        // Open quick payment modal
        $(document).on('click', '.btn-quick-pay', function() {
            var invoiceId = $(this).val();
            $('#ar-payment-form-container').html('<p class="text-center text-gray-500 py-8">Cargando...</p>');
            $('#ar-payment-modal').removeClass('hidden');

            $.ajax({
                url: '<?php echo base_url(); ?>sisvent/admin/accountsreceivable/quickPayment',
                type: 'POST',
                data: { id: invoiceId },
                headers: { 'Authkey': csrfHash },
                success: function(html) {
                    $('#ar-payment-form-container').html(html);
                },
                error: function() {
                    $('#ar-payment-form-container').html('<p class="text-center text-red-500 py-4">Error al cargar formulario</p>');
                }
            });
        });

        // Close modal
        $(document).on('click', '#ar-modal-close', function() {
            $('#ar-payment-modal').addClass('hidden');
        });
        $(document).on('click', '#ar-payment-modal', function(e) {
            if (e.target === this) $('#ar-payment-modal').addClass('hidden');
        });

        // Toggle cash source type
        $(document).on('change', '#ar-pay-source-type', function() {
            if ($(this).val() === 'cashbox') {
                $('#ar-pay-cashbox-wrapper').removeClass('hidden');
                $('#ar-pay-bank-wrapper').addClass('hidden');
            } else {
                $('#ar-pay-cashbox-wrapper').addClass('hidden');
                $('#ar-pay-bank-wrapper').removeClass('hidden');
            }
        });

        // Submit payment
        $(document).on('click', '#ar-pay-submit', function() {
            var btn = $(this);
            btn.prop('disabled', true).text('Procesando...');

            var sourceType = $('#ar-pay-source-type').val();
            var data = {
                id: $('#ar-pay-invoice-id').val(),
                method: $('#ar-pay-method').val(),
                payment: $('#ar-pay-amount').val(),
                comment: $('#ar-pay-comment').val(),
                date: $('#ar-pay-date').val(),
                cash_source_type: sourceType,
                cash_source_cashbox: $('#ar-pay-cashbox').val(),
                cash_source_bank: $('#ar-pay-bank').val(),
                return_to: 'list'
            };

            if (!data.payment || parseFloat(data.payment) <= 0) {
                alert('Ingrese un valor de abono valido');
                btn.prop('disabled', false).text('Registrar Abono');
                return;
            }

            if (sourceType === 'cashbox' && !data.cash_source_cashbox) {
                alert('Seleccione una caja');
                btn.prop('disabled', false).text('Registrar Abono');
                return;
            }
            if (sourceType === 'bank' && !data.cash_source_bank) {
                alert('Seleccione un banco');
                btn.prop('disabled', false).text('Registrar Abono');
                return;
            }

            $.ajax({
                url: '<?php echo base_url(); ?>sisvent/admin/accountsreceivable/makePayment',
                type: 'POST',
                data: data,
                dataType: 'json',
                headers: { 'Authkey': csrfHash },
                success: function(resp) {
                    if (resp.success) {
                        $('#ar-payment-modal').addClass('hidden');
                        location.reload();
                    } else {
                        alert(resp.message || 'Error al registrar pago');
                        btn.prop('disabled', false).text('Registrar Abono');
                    }
                },
                error: function() {
                    alert('Error de conexion');
                    btn.prop('disabled', false).text('Registrar Abono');
                }
            });
        });
    })();
    </script>
</body>
</html>
