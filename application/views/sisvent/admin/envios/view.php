<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Envio #<?= $shipment->numeroPreenvio ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
    .timeline { position: relative; padding-left: 30px; }
    .timeline::before { content: ''; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #E5E7EB; }
    .timeline-item { position: relative; padding-bottom: 20px; }
    .timeline-dot { position: absolute; left: -25px; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px currentColor; }
    .timeline-dot.dot-green { color: #10B981; background: #10B981; }
    .timeline-dot.dot-blue { color: #3B82F6; background: #3B82F6; }
    .timeline-dot.dot-yellow { color: #F59E0B; background: #F59E0B; }
    .timeline-dot.dot-red { color: #EF4444; background: #EF4444; }
    .timeline-dot.dot-purple { color: #8B5CF6; background: #8B5CF6; }
    .timeline-dot.dot-gray { color: #9CA3AF; background: #9CA3AF; }
</style>
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
                            <h2 class="text-xl font-bold text-gray-800">Envio #<?= $shipment->numeroPreenvio ?></h2>
                        </div>
                        <a href="<?= base_url() ?>sisvent/admin/envios"
                           class="text-sm text-mam-blue-petroleo hover:underline">&larr; Dashboard de Envios</a>
                    </div>

                    <!-- Shipment Header Card -->
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Left: Client Info -->
                            <div>
                                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-3">Datos del Destinatario</h3>
                                <div class="space-y-2">
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Nombre</p>
                                        <p class="text-sm font-medium text-gray-700"><?= isset($shipment->client_name) ? $shipment->client_name : '-' ?></p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Documento</p>
                                            <p class="text-sm font-medium text-gray-700"><?= isset($shipment->client_doc) ? $shipment->client_doc : '-' ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Telefono</p>
                                            <p class="text-sm font-medium text-gray-700"><?= isset($shipment->client_phone) ? $shipment->client_phone : '-' ?></p>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Direccion</p>
                                        <p class="text-sm font-medium text-gray-700"><?= isset($shipment->recipientAddress) && $shipment->recipientAddress ? $shipment->recipientAddress : (isset($shipment->client_address) ? $shipment->client_address : '-') ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Ciudad</p>
                                        <p class="text-sm font-medium text-gray-700"><?= isset($shipment->ciudadDestinoNombre) ? $shipment->ciudadDestinoNombre : '-' ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Shipment Info -->
                            <div>
                                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-3">Datos del Envio</h3>
                                <div class="space-y-2">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Factura</p>
                                            <?php if(isset($shipment->invoiceId) && $shipment->invoiceId): ?>
                                                <a href="<?= base_url() ?>sisvent/commercial/invoices/view/<?= $shipment->invoiceId ?>"
                                                   class="text-sm text-mam-blue-petroleo hover:underline font-medium">
                                                    #<?= $shipment->invoiceId ?>
                                                </a>
                                            <?php else: ?>
                                                <p class="text-sm text-gray-400">-</p>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Bodega</p>
                                            <p class="text-sm font-medium text-gray-700"><?= isset($shipment->store_name) ? $shipment->store_name : '-' ?></p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Estado</p>
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
                                            ?>
                                            <span class="inline-block px-3 py-1 text-xs font-bold rounded-full <?= $badgeClass ?>"><?= $statusLabel ?></span>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase">Transportadora</p>
                                            <p class="text-sm font-medium text-gray-700"><?= isset($shipment->carrierName) && $shipment->carrierName ? $shipment->carrierName : 'Interrapidisimo' ?></p>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Fecha de Creacion</p>
                                        <p class="text-sm font-medium text-gray-700"><?= date('d/m/Y H:i', strtotime($shipment->created_at)) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Peso</p>
                            <p class="text-lg font-bold text-gray-800 mt-1"><?= isset($shipment->peso) ? number_format($shipment->peso, 1, ',', '.') : '0' ?> kg</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Valor Declarado</p>
                            <p class="text-lg font-bold text-gray-800 mt-1">$<?= number_format(isset($shipment->valorDeclarado) ? $shipment->valorDeclarado : 0, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Costo Flete</p>
                            <p class="text-lg font-bold text-blue-600 mt-1">$<?= number_format(isset($shipment->valorFlete) ? $shipment->valorFlete : 0, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Costo Seguro</p>
                            <p class="text-lg font-bold text-blue-600 mt-1">$<?= number_format(isset($shipment->valorSeguro) ? $shipment->valorSeguro : 0, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Flete</p>
                            <p class="text-lg font-bold text-green-600 mt-1">$<?= number_format(isset($shipment->valorTotal) ? $shipment->valorTotal : 0, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center gap-3 mb-6">
                        <button id="btn-refresh-tracking" data-id="<?= $shipment->id ?>"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#2E7D91;">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Actualizar Tracking
                        </button>
                        <?php if(isset($shipment->numeroPreenvio) && $shipment->numeroPreenvio): ?>
                        <a href="<?= base_url() ?>sisvent/commercial/shipping/descargarGuia/<?= $shipment->numeroPreenvio ?>"
                           class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#1B365D;">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Descargar Guia
                        </a>
                        <?php endif; ?>
                        <?php if(isset($shipment->invoiceId) && $shipment->invoiceId): ?>
                        <a href="<?= base_url() ?>sisvent/commercial/invoices/view/<?= $shipment->invoiceId ?>"
                           class="inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Ver Factura
                        </a>
                        <?php endif; ?>
                        <?php if(isset($shipment->client_phone) && $shipment->client_phone): ?>
                        <button id="btn-notify-client" data-id="<?= $shipment->id ?>"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#25D366;">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.468l4.571-1.46A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818c-2.168 0-4.207-.614-5.932-1.677l-.425-.254-2.71.866.877-2.622-.278-.442A9.776 9.776 0 012.182 12c0-5.418 4.4-9.818 9.818-9.818S21.818 6.582 21.818 12 17.418 21.818 12 21.818z"/>
                            </svg>
                            Notificar Cliente
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Tracking Timeline -->
                    <div class="bg-white rounded-lg shadow-sm border p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Tracking del Envio</h3>

                        <?php if(!empty($events)): ?>
                        <div class="timeline">
                            <?php foreach($events as $event): ?>
                            <?php
                                // Determine dot color based on statusCode
                                $dotClass = 'dot-gray';
                                $code = isset($event->statusCode) ? (int)$event->statusCode : 0;
                                if ($code == 11) $dotClass = 'dot-green';           // Entregado
                                elseif (in_array($code, [2,3,4,18])) $dotClass = 'dot-blue';   // En tránsito
                                elseif (in_array($code, [6,31])) $dotClass = 'dot-purple';      // En reparto
                                elseif (in_array($code, [7,8,10])) $dotClass = 'dot-red';       // Novedad
                                elseif ($code == 15) $dotClass = 'dot-gray';        // Anulado
                            ?>
                            <div class="timeline-item">
                                <div class="timeline-dot <?= $dotClass ?>"></div>
                                <div class="ml-2">
                                    <p class="text-sm font-bold text-gray-800">
                                        <?= isset($event->statusName) ? $event->statusName : 'Evento' ?>
                                    </p>
                                    <?php if(isset($event->description) && $event->description): ?>
                                        <p class="text-sm text-gray-600"><?= $event->description ?></p>
                                    <?php endif; ?>
                                    <?php if(isset($event->location) && $event->location): ?>
                                        <p class="text-xs text-gray-400 mt-1">
                                            <svg class="w-3 h-3 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            <?= $event->location ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if(isset($event->eventDate) && $event->eventDate): ?>
                                        <p class="text-xs text-gray-400 mt-1"><?= date('d/m/Y H:i', strtotime($event->eventDate)) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p class="text-gray-400">Sin eventos de tracking registrados</p>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    $(function() {
        // Notify client via WhatsApp
        $(document).on('click', '#btn-notify-client', function() {
            var $btn = $(this);
            var shipmentId = $btn.data('id');
            if (!confirm('¿Enviar mensaje WhatsApp al cliente con el estado de este envío?')) return;

            var originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<svg class="w-4 h-4 mr-2 animate-spin inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Enviando...');

            $.ajax({
                url: '<?= base_url() ?>sisvent/admin/envios/notifyClient/' + shipmentId,
                type: 'POST',
                data: { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' },
                dataType: 'json',
                success: function(r) {
                    $btn.prop('disabled', false).html(originalHtml);
                    if (r.success) {
                        alert('✅ ' + r.message);
                    } else {
                        alert('❌ ' + r.message);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).html(originalHtml);
                    alert('Error al enviar la notificación');
                }
            });
        });

        // Refresh tracking - delegated event
        $(document).on('click', '#btn-refresh-tracking', function() {
            var $btn = $(this);
            var shipmentId = $btn.data('id');
            $btn.prop('disabled', true).text('Actualizando...');

            $.ajax({
                url: '<?= base_url() ?>sisvent/admin/envios/refreshTracking/' + shipmentId,
                type: 'POST',
                data: {
                    '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
                },
                success: function(response) {
                    location.reload();
                },
                error: function() {
                    alert('Error al actualizar el tracking. Intente nuevamente.');
                    $btn.prop('disabled', false).html(
                        '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>' +
                        '</svg> Actualizar Tracking'
                    );
                }
            });
        });
    });
    </script>
</body>
</html>
