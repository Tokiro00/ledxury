<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Cuentas por Cobrar - Por Cliente</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <div class="flex items-center">
                            <a href="<?php echo base_url(); ?>sisvent/admin/accountsreceivable" class="mr-4 text-gray-500 hover:text-gray-700">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            </a>
                            <h2 class="text-lg font-semibold text-gray-600">
                                Cuentas por Cobrar - Por Cliente <span class="text-sm font-normal text-gray-400">(<?php echo count($clients_receivables); ?> clientes)</span>
                            </h2>
                        </div>
                    </div>

                    <!-- Aging Summary Cards -->
                    <div class="grid gap-4 mb-6 md:grid-cols-5">
                        <!-- Al día (0-30) -->
                        <div class="flex items-center p-4 bg-green-100 rounded-lg shadow-xs">
                            <div class="p-3 mr-4 text-green-500 bg-green-200 rounded-full">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-green-600">Al día (0-30 días)</p>
                                <p class="text-lg font-bold text-green-700">$<?php echo number_format($aging['current'], 2); ?></p>
                                <p class="text-xs text-green-600"><?php echo $aging['count_current']; ?> facturas</p>
                            </div>
                        </div>

                        <!-- 31-60 días -->
                        <div class="flex items-center p-4 bg-yellow-100 rounded-lg shadow-xs">
                            <div class="p-3 mr-4 text-yellow-500 bg-yellow-200 rounded-full">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-yellow-600">31-60 días</p>
                                <p class="text-lg font-bold text-yellow-700">$<?php echo number_format($aging['days_31_60'], 2); ?></p>
                                <p class="text-xs text-yellow-600"><?php echo $aging['count_31_60']; ?> facturas</p>
                            </div>
                        </div>

                        <!-- 61-90 días -->
                        <div class="flex items-center p-4 bg-orange-100 rounded-lg shadow-xs">
                            <div class="p-3 mr-4 text-orange-500 bg-orange-200 rounded-full">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-orange-600">61-90 días</p>
                                <p class="text-lg font-bold text-orange-700">$<?php echo number_format($aging['days_61_90'], 2); ?></p>
                                <p class="text-xs text-orange-600"><?php echo $aging['count_61_90']; ?> facturas</p>
                            </div>
                        </div>

                        <!-- +90 días -->
                        <div class="flex items-center p-4 bg-red-100 rounded-lg shadow-xs">
                            <div class="p-3 mr-4 text-red-500 bg-red-200 rounded-full">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-red-600">+90 días</p>
                                <p class="text-lg font-bold text-red-700">$<?php echo number_format($aging['days_91_plus'], 2); ?></p>
                                <p class="text-xs text-red-600"><?php echo $aging['count_91_plus']; ?> facturas</p>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="flex items-center p-4 bg-blue-100 rounded-lg shadow-xs">
                            <div class="p-3 mr-4 text-blue-500 bg-blue-200 rounded-full">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-blue-600">Total por Cobrar</p>
                                <p class="text-lg font-bold text-blue-700">$<?php echo number_format($aging['total'], 2); ?></p>
                                <p class="text-xs text-blue-600"><?php echo $aging['count_total']; ?> facturas</p>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
                        <form method="GET" action="<?php echo base_url(); ?>sisvent/admin/accountsreceivable/byClient" class="flex flex-wrap gap-4 items-end">
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
                                <a href="<?php echo base_url(); ?>sisvent/admin/accountsreceivable/byClient" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                                    Limpiar
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Clients Table -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs bg-white">
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Cliente</th>
                                        <th class="px-4 py-3">Contacto</th>
                                        <th class="px-4 py-3 text-center">Facturas</th>
                                        <th class="px-4 py-3 text-right">Total Deuda</th>
                                        <th class="px-4 py-3">Factura Más Antigua</th>
                                        <th class="px-4 py-3 text-center">Días Máx.</th>
                                        <th class="px-4 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php if(!empty($clients_receivables)): ?>
                                        <?php foreach($clients_receivables as $client): ?>
                                            <?php
                                                $days = $client->max_days_overdue;
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
                                                <td class="px-4 py-3">
                                                    <p class="font-semibold text-sm"><?php echo $client->client_name; ?></p>
                                                    <p class="text-xs text-gray-500"><?php echo $client->client_idNum; ?></p>
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    <?php if($client->cellphone): ?>
                                                        <p class="text-xs"><?php echo $client->cellphone; ?></p>
                                                    <?php endif; ?>
                                                    <?php if($client->phone): ?>
                                                        <p class="text-xs text-gray-500"><?php echo $client->phone; ?></p>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full">
                                                        <?php echo $client->invoice_count; ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <span class="text-lg font-bold text-blue-600">$<?php echo number_format($client->total_balance, 2); ?></span>
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    <?php echo date('d/m/Y', strtotime($client->oldest_invoice)); ?>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $badgeClass; ?>">
                                                        <?php echo $days; ?> días
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center space-x-2">
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/accountsreceivable/clientDetail/<?php echo $client->idClient; ?>" class="p-2 text-blue-600 hover:bg-blue-100 rounded" title="Ver Detalle">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                        </a>
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/accountsreceivable?client=<?php echo $client->idClient; ?>" class="p-2 text-gray-600 hover:bg-gray-100 rounded" title="Ver Facturas">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                        </a>
                                                        <?php if(!empty($client->cellphone)): ?>
                                                        <a href="https://wa.me/57<?php echo preg_replace('/[^0-9]/', '', $client->cellphone); ?>" target="_blank" class="p-2 text-green-600 hover:bg-green-100 rounded" title="WhatsApp">
                                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                                No hay clientes con cuentas por cobrar pendientes
                                            </td>
                                        </tr>
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
</body>
</html>
