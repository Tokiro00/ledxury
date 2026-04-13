<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Dashboard Envios Interrapidisimo</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 w-full">

                    <!-- Header -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Dashboard Envios Interrapidisimo</h2>
                            <p class="text-sm text-gray-500">Seguimiento y gestion de envios con Interrapidisimo</p>
                        </div>
                        <div class="flex items-center gap-2 mt-2 lg:mt-0">
                            <button onclick="syncTrackingNow()" id="btnSyncTracking"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#2E7D91;">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Sincronizar ahora
                            </button>
                            <button onclick="notifyClientsTracking()" id="btnNotifyClients"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#25D366;">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.468l4.571-1.46A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818c-2.168 0-4.207-.614-5.932-1.677l-.425-.254-2.71.866.877-2.622-.278-.442A9.776 9.776 0 012.182 12c0-5.418 4.4-9.818 9.818-9.818S21.818 6.582 21.818 12 17.418 21.818 12 21.818z"/>
                                </svg>
                                Notificar Clientes
                            </button>
                            <a href="<?= base_url() ?>sisvent/admin/envios/estadoCuenta"
                               class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#1B365D;">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                Estado de Cuenta
                            </a>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total</p>
                            <p class="text-lg font-bold text-blue-600 mt-1"><?= isset($stats->total) ? number_format($stats->total, 0, ',', '.') : 0 ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Creados</p>
                            <p class="text-lg font-bold text-gray-600 mt-1"><?= isset($stats->creados) ? number_format($stats->creados, 0, ',', '.') : 0 ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">En Transito</p>
                            <p class="text-lg font-bold text-blue-600 mt-1"><?= isset($stats->en_transito) ? number_format($stats->en_transito, 0, ',', '.') : 0 ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">En Reparto</p>
                            <p class="text-lg font-bold text-purple-600 mt-1"><?= isset($stats->en_reparto) ? number_format($stats->en_reparto, 0, ',', '.') : 0 ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Entregados</p>
                            <p class="text-lg font-bold text-green-600 mt-1"><?= isset($stats->entregados) ? number_format($stats->entregados, 0, ',', '.') : 0 ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Novedades</p>
                            <p class="text-lg font-bold text-red-600 mt-1"><?= isset($stats->novedades) ? number_format($stats->novedades, 0, ',', '.') : 0 ?></p>
                        </div>
                    </div>

                    <!-- Contrapago Summary -->
                    <?php
                        $cpTotal = isset($stats->contrapago_total) ? (float)$stats->contrapago_total : 0;
                        $cpEntregado = isset($stats->contrapago_entregado) ? (float)$stats->contrapago_entregado : 0;
                        $cpPendiente = isset($stats->contrapago_pendiente) ? (float)$stats->contrapago_pendiente : 0;
                    ?>
                    <?php if($cpTotal > 0): ?>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-4">
                        <div class="bg-yellow-50 rounded-lg shadow-sm border border-yellow-200 p-4">
                            <p class="text-xs text-yellow-700 uppercase tracking-wide font-semibold">Pago en Casa - Total</p>
                            <p class="text-lg font-bold text-yellow-800 mt-1">$<?= number_format($cpTotal, 0, ',', '.') ?></p>
                            <p class="text-xs text-yellow-600 mt-1">Total a recaudar por Inter</p>
                        </div>
                        <div class="bg-green-50 rounded-lg shadow-sm border border-green-200 p-4">
                            <p class="text-xs text-green-700 uppercase tracking-wide font-semibold">Pago en Casa - Entregado</p>
                            <p class="text-lg font-bold text-green-800 mt-1">$<?= number_format($cpEntregado, 0, ',', '.') ?></p>
                            <p class="text-xs text-green-600 mt-1">Inter debe transferir a Ledxury</p>
                        </div>
                        <div class="bg-blue-50 rounded-lg shadow-sm border border-blue-200 p-4">
                            <p class="text-xs text-blue-700 uppercase tracking-wide font-semibold">Pago en Casa - Pendiente</p>
                            <p class="text-lg font-bold text-blue-800 mt-1">$<?= number_format($cpPendiente, 0, ',', '.') ?></p>
                            <p class="text-xs text-blue-600 mt-1">En camino, aun no entregado</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Filter Bar -->
                    <form method="get" action="<?= base_url() ?>sisvent/admin/envios" class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <div class="flex flex-wrap items-end gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Bodega</label>
                                <select name="store" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                    <option value="">Todas</option>
                                    <?php if(!empty($stores)): foreach($stores as $s): ?>
                                    <option value="<?= $s->idStore ?>" <?= (isset($_GET['store']) && $_GET['store'] == $s->idStore) ? 'selected' : '' ?>><?= $s->name ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Estado</label>
                                <select name="status" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                    <option value="">Todos</option>
                                    <?php
                                    $statuses = [
                                        'creado' => 'Creado',
                                        'en_transito' => 'En Transito',
                                        'en_reparto' => 'En Reparto',
                                        'entregado' => 'Entregado',
                                        'novedad' => 'Novedad',
                                        'anulado' => 'Anulado'
                                    ];
                                    foreach($statuses as $sKey => $sLabel): ?>
                                    <option value="<?= $sKey ?>" <?= (isset($_GET['status']) && $_GET['status'] == $sKey) ? 'selected' : '' ?>><?= $sLabel ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Vendedor</label>
                                <select name="vendor" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                                    <option value="">Todos</option>
                                    <?php if(!empty($vendors)): foreach($vendors as $v): ?>
                                    <option value="<?= $v->idUser ?>" <?= (isset($_GET['vendor']) && $_GET['vendor'] == $v->idUser) ? 'selected' : '' ?>><?= htmlspecialchars($v->name) ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Desde</label>
                                <input type="date" name="from" value="<?= isset($_GET['from']) ? $_GET['from'] : '' ?>" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Hasta</label>
                                <input type="date" name="to" value="<?= isset($_GET['to']) ? $_GET['to'] : '' ?>" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Buscar</label>
                                <input type="text" name="q" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>" placeholder="Guia, factura, cliente..." class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <button type="submit" class="px-4 py-2 text-sm text-white rounded-lg" style="background:#2E7D91;">Filtrar</button>
                            </div>
                            <div>
                                <a href="<?= base_url() ?>sisvent/admin/envios/exportExcel?<?= http_build_query($_GET) ?>"
                                   class="inline-flex items-center px-4 py-2 text-sm text-white rounded-lg" style="background:#1F8B4C;">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Excel
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Table -->
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left" style="background:#1B365D; color:white;">
                                        <th class="px-3 py-2.5 font-semibold">#</th>
                                        <th class="px-3 py-2.5 font-semibold">Guia</th>
                                        <th class="px-3 py-2.5 font-semibold">Factura</th>
                                        <th class="px-3 py-2.5 font-semibold">Presup.</th>
                                        <th class="px-3 py-2.5 font-semibold">Cliente</th>
                                        <th class="px-3 py-2.5 font-semibold">Documento</th>
                                        <th class="px-3 py-2.5 font-semibold">Vendedor</th>
                                        <th class="px-3 py-2.5 font-semibold">Destino</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Cajas</th>
                                        <th class="px-3 py-2.5 font-semibold">Tipo</th>
                                        <th class="px-3 py-2.5 font-semibold">Estado</th>
                                        <th class="px-3 py-2.5 font-semibold">Última act.</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Costo</th>
                                        <th class="px-3 py-2.5 font-semibold text-right">Recaudo</th>
                                        <th class="px-3 py-2.5 font-semibold">Fecha</th>
                                        <th class="px-3 py-2.5 font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($shipments)): ?>
                                        <?php $i = isset($page) ? ($page - 1) * 25 : 0; foreach($shipments as $shipment): $i++; ?>
                                        <?php
                                            switch($shipment->status) {
                                                case 'creado':               $badgeClass = 'bg-gray-100 text-gray-700'; break;
                                                case 'recogida_solicitada':  $badgeClass = 'bg-yellow-100 text-yellow-800'; break;
                                                case 'en_transito':          $badgeClass = 'bg-blue-100 text-blue-800'; break;
                                                case 'en_reparto':           $badgeClass = 'bg-purple-100 text-purple-800'; break;
                                                case 'entregado':            $badgeClass = 'bg-green-100 text-green-800'; break;
                                                case 'novedad':              $badgeClass = 'bg-red-100 text-red-800'; break;
                                                case 'anulado':              $badgeClass = 'bg-gray-200 text-gray-600'; break;
                                                default:                     $badgeClass = 'bg-gray-100 text-gray-600';
                                            }
                                            $statusLabel = str_replace('_', ' ', ucfirst($shipment->status));
                                            $piezas = isset($shipment->numeroPiezas) ? (int)$shipment->numeroPiezas : 1;
                                            $esCp = isset($shipment->isContrapago) && $shipment->isContrapago;
                                            $canDelete = in_array($shipment->status, ['creado','cotizado','recogida_solicitada']);
                                        ?>
                                        <tr class="border-t <?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50">
                                            <td class="px-3 py-1.5 text-gray-400 font-bold"><?= $i ?></td>
                                            <td class="px-3 py-1.5 font-mono font-medium">
                                                <?= $shipment->numeroPreenvio ?>
                                                <?php if($piezas > 1): ?>
                                                    <span class="text-gray-400 text-xs">(+<?= $piezas - 1 ?>)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 py-1.5">
                                                <a href="<?= base_url() ?>sisvent/commercial/invoices/view/<?= $shipment->invoiceId ?>" class="text-blue-700 font-medium hover:underline" onclick="event.stopPropagation();">#<?= $shipment->invoiceId ?></a>
                                            </td>
                                            <td class="px-3 py-1.5">
                                                <?php if(!empty($shipment->budgetId)): ?>
                                                    <a href="<?= base_url() ?>sisvent/commercial/budgets?p=1&q=<?= $shipment->budgetId ?>" class="text-blue-700 font-medium hover:underline" onclick="event.stopPropagation();">#<?= $shipment->budgetId ?></a>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 py-1.5"><?= isset($shipment->client_name) ? $shipment->client_name : '-' ?></td>
                                            <td class="px-3 py-1.5 font-mono"><?= isset($shipment->client_doc) ? $shipment->client_doc : '-' ?></td>
                                            <td class="px-3 py-1.5"><?= isset($shipment->vendor_name) ? $shipment->vendor_name : '-' ?></td>
                                            <td class="px-3 py-1.5"><?= isset($shipment->ciudadDestinoNombre) ? $shipment->ciudadDestinoNombre : '-' ?></td>
                                            <td class="px-3 py-1.5 text-center font-bold"><?= $piezas ?></td>
                                            <td class="px-3 py-1.5">
                                                <?php if($esCp): ?>
                                                    <span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800 text-xs font-bold">Contrapago</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs font-bold">Ledxury</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 py-1.5">
                                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold <?= $badgeClass ?>"><?= $statusLabel ?></span>
                                                <?php if(!empty($shipment->estadoNombre) && $shipment->estadoNombre != 'Creado'): ?>
                                                    <div class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($shipment->estadoNombre) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 py-1.5 text-xs text-gray-500">
                                                <?php if(!empty($shipment->lastTrackingCheck)): ?>
                                                    <?php
                                                        $diff = time() - strtotime($shipment->lastTrackingCheck);
                                                        if ($diff < 60) $ago = 'Hace segundos';
                                                        elseif ($diff < 3600) $ago = 'Hace ' . floor($diff/60) . ' min';
                                                        elseif ($diff < 86400) $ago = 'Hace ' . floor($diff/3600) . ' h';
                                                        else $ago = 'Hace ' . floor($diff/86400) . ' d';
                                                    ?>
                                                    <span title="<?= $shipment->lastTrackingCheck ?>"><?= $ago ?></span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 py-1.5 text-right font-medium">$<?= number_format($shipment->valorTotal, 0, ',', '.') ?></td>
                                            <td class="px-3 py-1.5 text-right font-medium">
                                                <?php if($esCp): ?>
                                                    <span class="text-yellow-700 font-bold">$<?= number_format(isset($shipment->contrapagoCost) ? $shipment->contrapagoCost : 0, 0, ',', '.') ?></span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 py-1.5"><?= date('d/m/Y', strtotime($shipment->created_at)) ?></td>
                                            <td class="px-3 py-1.5">
                                                <div class="flex gap-1" onclick="event.stopPropagation();">
                                                    <a href="<?= base_url() ?>sisvent/admin/envios/view/<?= $shipment->id ?>"
                                                       class="px-2 py-1 text-xs font-medium text-white rounded" style="background:#2E7D91;" title="Ver detalle">Ver</a>
                                                    <a href="<?= base_url() ?>sisvent/commercial/shipping/imprimirGuias/<?= $shipment->invoiceId ?>"
                                                       target="_blank" class="px-2 py-1 text-xs font-medium text-white rounded" style="background:#1B365D;" title="Imprimir guias">PDF</a>
                                                    <?php if($canDelete): ?>
                                                    <button onclick="eliminarEnvio(<?= $shipment->id ?>, '<?= $shipment->numeroPreenvio ?>')"
                                                            class="px-2 py-1 text-xs font-medium text-white bg-red-500 rounded hover:bg-red-600" title="Eliminar">X</button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="16" class="px-3 py-8 text-center text-gray-400">No hay envios para mostrar</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <?php if(isset($total) && $total > 0): ?>
                    <?php
                        $perPage = 25;
                        $currentPage = isset($page) ? (int)$page : 1;
                        $totalPages = ceil($total / $perPage);
                        $queryParams = $_GET;
                    ?>
                    <div class="flex items-center justify-between mt-4">
                        <p class="text-xs text-gray-500">
                            Mostrando <?= (($currentPage - 1) * $perPage) + 1 ?> - <?= min($currentPage * $perPage, $total) ?> de <?= number_format($total, 0, ',', '.') ?> envios
                        </p>
                        <div class="flex items-center space-x-2">
                            <?php if($currentPage > 1):
                                $queryParams['page'] = $currentPage - 1;
                            ?>
                                <a href="<?= base_url() ?>sisvent/admin/envios?<?= http_build_query($queryParams) ?>"
                                   class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                    &laquo; Anterior
                                </a>
                            <?php endif; ?>

                            <span class="px-3 py-1.5 text-xs font-bold text-white rounded-lg" style="background:#1B365D;">
                                <?= $currentPage ?>
                            </span>

                            <?php if($currentPage < $totalPages):
                                $queryParams['page'] = $currentPage + 1;
                            ?>
                                <a href="<?= base_url() ?>sisvent/admin/envios?<?= http_build_query($queryParams) ?>"
                                   class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                    Siguiente &raquo;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
    <script>
    function eliminarEnvio(id, numero) {
        if (!confirm('¿Eliminar envío ' + numero + '? Se revertirá el flete de la factura.')) return;
        var csrf = $('input[name="<?= $this->security->get_csrf_token_name() ?>"]').first().val() || '<?= $this->security->get_csrf_hash() ?>';
        var d = {};
        d['<?= $this->security->get_csrf_token_name() ?>'] = csrf;
        d.guideId = id;
        $.post('<?= base_url() ?>sisvent/commercial/shipping/eliminarGuia', d, function(r) {
            if (r.error) { alert(r.error); return; }
            alert(r.mensaje);
            location.reload();
        }, 'json');
    }

    function syncTrackingNow() {
        var btn = $('#btnSyncTracking');
        var originalHtml = btn.html();
        btn.prop('disabled', true).html('<svg class="w-4 h-4 mr-2 animate-spin inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Sincronizando...');

        $.get('<?= base_url() ?>cron/update_shipping_guides?key=sisvent_cron_2024_tracking', function(r) {
            btn.prop('disabled', false).html(originalHtml);
            if (r && typeof r === 'object') {
                alert('Sincronización completa\n\nProcesadas: ' + (r.processed || 0) + '\nVerificadas: ' + (r.checked || 0) + '\nActualizadas: ' + (r.updated || 0) + '\nErrores: ' + (r.errors || 0));
                location.reload();
            } else {
                alert('Sincronización completa');
                location.reload();
            }
        }, 'json').fail(function(xhr) {
            btn.prop('disabled', false).html(originalHtml);
            alert('Error al sincronizar: ' + (xhr.responseText || xhr.statusText));
        });
    }

    function notifyClientsTracking() {
        if (!confirm('¿Enviar notificación WhatsApp a todos los clientes con guías activas?')) return;

        var btn = $('#btnNotifyClients');
        var originalHtml = btn.html();
        btn.prop('disabled', true).html('<svg class="w-4 h-4 mr-2 animate-spin inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Enviando...');

        $.get('<?= base_url() ?>cron/notify_clients_tracking?key=sisvent_cron_2024_tracking', function(r) {
            btn.prop('disabled', false).html(originalHtml);
            if (r && typeof r === 'object') {
                alert('Notificaciones enviadas\n\nEnviadas: ' + (r.sent || 0) + '\nErrores: ' + (r.errors || 0) + '\nSaltadas: ' + (r.skipped || 0));
            } else {
                alert('Proceso completado');
            }
        }, 'json').fail(function(xhr) {
            btn.prop('disabled', false).html(originalHtml);
            alert('Error: ' + (xhr.responseText || xhr.statusText));
        });
    }
    </script>
</body>
</html>
