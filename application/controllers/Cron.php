<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cron Controller
 *
 * Controlador para tareas programadas (ejecutar via CLI o Task Scheduler)
 *
 * Uso desde CLI:
 *   php index.php cron update_tracking
 *   php index.php cron send_tracking_notifications
 *
 * Uso desde web (solo si está habilitado):
 *   /cron/update_tracking?key=YOUR_CRON_KEY
 */
class Cron extends CI_Controller {

    // Clave secreta para ejecutar CRON vía web (opcional)
    private $cron_key = 'sisvent_cron_2024_tracking';

    public function __construct()
    {
        parent::__construct();

        // Permitir ejecución CLI o con clave válida
        if (!is_cli()) {
            $key = $this->input->get('key');
            if ($key !== $this->cron_key) {
                log_message('error', 'Intento de acceso no autorizado al CRON');
                show_error('Acceso no autorizado', 403);
            }
        }

        $this->load->model('invoices_model');
        $this->load->model('message_model');
        $this->load->model('users_model');
        $this->load->library('interrapidisimo_tracker');

        // Aumentar tiempo de ejecución para tareas largas
        set_time_limit(300); // 5 minutos
    }

    /**
     * Tarea principal: Actualizar estado de todas las guías activas
     *
     * Ejecutar: php index.php cron update_tracking
     */
    public function update_tracking()
    {
        $this->log_cron('=== Iniciando actualización de tracking ===');

        // Obtener facturas con tracking activo (no entregadas ni devueltas)
        $invoices = $this->invoices_model->getInvoicesWithActiveTracking();

        if (empty($invoices)) {
            $this->log_cron('No hay facturas con tracking activo para actualizar');
            return;
        }

        $this->log_cron('Facturas a procesar: ' . count($invoices));

        $updated = 0;
        $errors = 0;
        $notified = 0;

        foreach ($invoices as $invoice) {
            try {
                $this->log_cron("Procesando factura #{$invoice->idInvoice} - Guía: {$invoice->tracking_number}");

                // Consultar estado actual
                $trackingInfo = $this->interrapidisimo_tracker->getStatus($invoice->tracking_number);

                if ($trackingInfo === false) {
                    $this->log_cron("  ERROR: No se pudo obtener info de la guía");
                    $errors++;
                    continue;
                }

                $previousStatus = $invoice->tracking_status;
                $newStatus = $trackingInfo['status'];

                // Actualizar factura
                $updateData = [
                    'tracking_status' => $newStatus,
                    'tracking_location' => $trackingInfo['location'],
                    'tracking_last_update' => date('Y-m-d H:i:s')
                ];

                // Si está entregado, marcar fecha de entrega
                if ($this->interrapidisimo_tracker->isDelivered($newStatus) && empty($invoice->delivered_at)) {
                    $updateData['delivered_at'] = date('Y-m-d H:i:s');
                }

                $this->invoices_model->updateTracking($invoice->idInvoice, $updateData);
                $updated++;

                $this->log_cron("  Actualizado: {$previousStatus} -> {$newStatus} | {$trackingInfo['location']}");

                // Si el estado cambió, notificar al vendedor
                if ($previousStatus !== $newStatus) {
                    $this->notify_vendor($invoice, $trackingInfo);
                    $notified++;
                }

                // Rate limiting: esperar 500ms entre consultas
                usleep(500000);

            } catch (Exception $e) {
                $this->log_cron("  ERROR: " . $e->getMessage());
                $errors++;
            }
        }

        $this->log_cron("=== Actualización completada ===");
        $this->log_cron("Actualizadas: {$updated} | Errores: {$errors} | Notificaciones: {$notified}");

        // Mostrar resumen en CLI
        if (is_cli()) {
            echo "\n=== Tracking Update Complete ===\n";
            echo "Updated: {$updated}\n";
            echo "Errors: {$errors}\n";
            echo "Notifications: {$notified}\n";
        } else {
            echo json_encode([
                'success' => true,
                'updated' => $updated,
                'errors' => $errors,
                'notifications' => $notified
            ]);
        }
    }

    /**
     * Enviar notificación al vendedor sobre cambio de estado
     */
    private function notify_vendor($invoice, $trackingInfo)
    {
        $statusLabels = [
            'pending' => 'Pendiente',
            'in_transit' => 'En tránsito',
            'out_for_delivery' => 'En reparto',
            'delivered' => '✅ ENTREGADO',
            'returned' => '⚠️ DEVUELTO',
            'exception' => '⚠️ Novedad',
        ];

        $statusLabel = $statusLabels[$trackingInfo['status']] ?? $trackingInfo['status'];

        // Mensaje interno
        $message = "📦 Factura #{$invoice->idInvoice} - Guía {$invoice->tracking_number}: {$statusLabel}";
        if (!empty($trackingInfo['location'])) {
            $message .= " | Ubicación: {$trackingInfo['location']}";
        }
        $message .= " | Cliente: {$invoice->client_name}";

        try {
            // Enviar mensaje interno al vendedor
            $data = [
                'time' => date('Y-m-d H:i:s'),
                'sender_message_id' => '00000', // Sistema
                'receiver_message_id' => $invoice->vendorId,
                'message' => $message,
                'readed' => 0
            ];

            $this->db->insert('user_messages', $data);

            $this->log_cron("  Notificación enviada a vendedor: {$invoice->vendor_name}");

        } catch (Exception $e) {
            $this->log_cron("  ERROR al enviar notificación: " . $e->getMessage());
        }
    }

    /**
     * Enviar resumen diario de tracking a Google Sheets
     */
    public function update_tracking_sheets()
    {
        $this->log_cron('=== Actualizando Google Sheets con tracking ===');

        // Obtener todas las facturas con tracking
        $invoices = $this->invoices_model->getInvoicesWithActiveTracking();

        if (empty($invoices)) {
            $this->log_cron('No hay facturas con tracking para reportar');
            return;
        }

        // Agrupar por vendedor
        $byVendor = [];
        foreach ($invoices as $invoice) {
            $vendorId = $invoice->vendorId;
            if (!isset($byVendor[$vendorId])) {
                $byVendor[$vendorId] = [
                    'vendor_name' => $invoice->vendor_name,
                    'invoices' => []
                ];
            }
            $byVendor[$vendorId]['invoices'][] = $invoice;
        }

        // Para cada vendedor con bot configurado, enviar a su sheet
        foreach ($byVendor as $vendorId => $data) {
            $vendor = $this->users_model->getAnyUser($vendorId);

            if (empty($vendor->bot_script_url)) {
                continue; // No tiene script configurado
            }

            $this->send_tracking_to_sheet($vendor, $data['invoices']);
        }

        $this->log_cron('=== Sheets actualizados ===');
    }

    /**
     * Enviar tracking a Google Sheet de un vendedor
     */
    private function send_tracking_to_sheet($vendor, $invoices)
    {
        $payload = [
            'action' => 'tracking_update',
            'vendor_id' => $vendor->idUser,
            'vendor_name' => $vendor->name,
            'updated_at' => date('Y-m-d H:i:s'),
            'tracking_data' => []
        ];

        foreach ($invoices as $invoice) {
            $payload['tracking_data'][] = [
                'invoice_id' => $invoice->idInvoice,
                'tracking_number' => $invoice->tracking_number,
                'status' => $invoice->tracking_status,
                'location' => $invoice->tracking_location,
                'client_name' => $invoice->client_name,
                'shipped_at' => $invoice->shipped_at,
                'last_update' => $invoice->tracking_last_update
            ];
        }

        try {
            $ch = curl_init($vendor->bot_script_url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $this->log_cron("Sheet actualizado para vendedor: {$vendor->name}");
            } else {
                $this->log_cron("ERROR al actualizar sheet para {$vendor->name}: HTTP {$httpCode}");
            }

        } catch (Exception $e) {
            $this->log_cron("ERROR al enviar a sheet: " . $e->getMessage());
        }
    }

    /**
     * Mostrar resumen de tracking (para debug)
     */
    public function tracking_summary()
    {
        $invoices = $this->invoices_model->getInvoicesWithActiveTracking();

        $summary = [
            'total' => count($invoices),
            'by_status' => [],
            'invoices' => []
        ];

        foreach ($invoices as $invoice) {
            $status = $invoice->tracking_status ?: 'unknown';
            if (!isset($summary['by_status'][$status])) {
                $summary['by_status'][$status] = 0;
            }
            $summary['by_status'][$status]++;

            $summary['invoices'][] = [
                'id' => $invoice->idInvoice,
                'tracking' => $invoice->tracking_number,
                'status' => $status,
                'location' => $invoice->tracking_location,
                'vendor' => $invoice->vendor_name,
                'client' => $invoice->client_name,
                'last_update' => $invoice->tracking_last_update
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($summary, JSON_PRETTY_PRINT);
    }

    /**
     * Actualizar tracking de shipping_guides usando Interrapidisimo_lib (sistema nuevo)
     *
     * Procesa guías activas (no entregadas/anuladas) con lastTrackingCheck > 30min
     * y consulta sus estados a la API de Inter.
     *
     * Ejecutar: php index.php cron update_shipping_guides
     * O via web: /cron/update_shipping_guides?key=YOUR_CRON_KEY
     */
    public function update_shipping_guides()
    {
        $this->log_cron('=== Iniciando actualización de shipping_guides ===');

        // Cargar dependencias
        $this->load->model('shipping_model');
        $this->load->model('builderbot_model');
        $this->load->library('interrapidisimo_lib');
        $this->load->library('builderbot_lib');

        // Obtener guías que necesitan actualización (max 50 por corrida)
        $guides = $this->shipping_model->getActiveForTracking(50);
        $total = count($guides);
        $this->log_cron("Guías a procesar: {$total}");

        if ($total == 0) {
            $this->log_cron('No hay guías activas para actualizar');
            if (!is_cli()) {
                header('Content-Type: application/json');
                echo json_encode(array('processed' => 0, 'updated' => 0, 'notified' => 0, 'message' => 'No hay guías activas'));
            }
            return;
        }

        // Guardar estadoNombre anterior de cada guía para detectar cambios
        $previousEstado = array();
        foreach ($guides as $g) {
            $full = $this->shipping_model->getShipment($g->id);
            $previousEstado[$g->id] = $full ? $full->estadoNombre : '';
        }

        // Recopilar números de guía (incluyendo guías hijas si tiene piezas múltiples)
        $allNums = array();
        $numToId = array();
        foreach ($guides as $g) {
            $allNums[] = (int) $g->numeroPreenvio;
            $numToId[(int) $g->numeroPreenvio] = $g->id;

            $full = $this->shipping_model->getShipment($g->id);
            if (!empty($full->guiasHijas)) {
                $hijas = json_decode($full->guiasHijas, true);
                if (is_array($hijas)) {
                    foreach ($hijas as $h) {
                        $allNums[] = (int) $h;
                        $numToId[(int) $h] = $g->id;
                    }
                }
            }
        }
        $allNums = array_values(array_unique($allNums));

        $updated = 0;
        $checked = 0;
        $errors = 0;
        $changedIds = array(); // IDs de guías cuyo estado cambió

        // Consultar en lotes de 15 (Inter API limit)
        $chunks = array_chunk($allNums, 15);
        foreach ($chunks as $chunk) {
            $resultado = $this->interrapidisimo_lib->consultarEstados($chunk);

            if (!$resultado || is_string($resultado)) {
                $errors += count($chunk);
                $this->log_cron('Error en lote: ' . (is_string($resultado) ? $resultado : 'sin respuesta'));
                continue;
            }

            // Si Inter responde con error "guías no existen/no admitidas", extraer las inválidas,
            // marcarlas como anuladas y reintentar el lote con las válidas.
            if (is_object($resultado) && isset($resultado->Message) && !isset($resultado->listadoGuias)) {
                $msg = (string) $resultado->Message;
                $this->log_cron('API error en lote: ' . $msg);

                if (preg_match('/\(([^)]+)\)/', $msg, $m) && stripos($msg, 'no existen') !== false) {
                    $badNums = array_filter(array_map('trim', explode(',', $m[1])));
                    foreach ($badNums as $bn) {
                        $bnInt = (int) $bn;
                        if (!$bnInt || !isset($numToId[$bnInt])) continue;
                        $this->db->where('id', $numToId[$bnInt]);
                        $this->db->update('shipping_guides', array(
                            'status' => 'anulado',
                            'estadoNombre' => 'No encontrada en transportadora',
                            'lastTrackingCheck' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ));
                    }
                    $validChunk = array_values(array_diff($chunk, array_map('intval', $badNums)));
                    if (empty($validChunk)) {
                        $errors += count($chunk);
                        continue;
                    }
                    $resultado = $this->interrapidisimo_lib->consultarEstados($validChunk);
                    if (!$resultado || is_string($resultado) || (is_object($resultado) && isset($resultado->Message) && !isset($resultado->listadoGuias))) {
                        $errors += count($validChunk);
                        $this->log_cron('Reintento falló: ' . (is_object($resultado) && isset($resultado->Message) ? $resultado->Message : 'sin respuesta'));
                        continue;
                    }
                } else {
                    $errors += count($chunk);
                    continue;
                }
            }

            $listaGuias = array();
            if (isset($resultado->listadoGuias)) {
                $listaGuias = $resultado->listadoGuias;
            } elseif (is_array($resultado)) {
                $listaGuias = $resultado;
            }

            foreach ($listaGuias as $guia) {
                $numGuia = isset($guia->numeroGuia) ? (int) $guia->numeroGuia : 0;
                $parentId = isset($numToId[$numGuia]) ? $numToId[$numGuia] : null;
                if (!$parentId) continue;

                $checked++;

                if (!empty($guia->estadosGuia)) {
                    $ultimo = $guia->estadosGuia[0];
                    $this->shipping_model->updateStatus($parentId, $ultimo->idEstadoGuia, $ultimo->nombreEstado);
                    $updated++;
                } elseif (!empty($guia->estadosPreenvio)) {
                    $ultimo = $guia->estadosPreenvio[0];
                    date_default_timezone_set("America/Bogota");
                    $this->db->where('id', $parentId);
                    $this->db->update('shipping_guides', array(
                        'estadoNombre' => 'Pre: ' . $ultimo->nombreEstado,
                        'lastTrackingCheck' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ));
                    $updated++;
                } else {
                    $this->shipping_model->markChecked($parentId);
                }

                if (!empty($guia->detalleMotivoDevolucion)) {
                    $this->db->where('id', $parentId);
                    $this->db->update('shipping_guides', array(
                        'observations' => 'Devolución: ' . $guia->detalleMotivoDevolucion
                    ));
                }

                // Detectar si el estadoNombre cambió (detecta cambios dentro del mismo status, ej: novedad)
                $newGuide = $this->db->where('id', $parentId)->get('shipping_guides')->row();
                if ($newGuide && isset($previousEstado[$parentId]) && $newGuide->estadoNombre !== $previousEstado[$parentId]) {
                    if ($newGuide->lastNotifiedStatus !== $newGuide->estadoNombre) {
                        $changedIds[] = $parentId;
                    }
                }
            }

            usleep(500000);
        }

        // === AUTO-NOTIFICAR clientes cuyo estado cambió ===
        $notified = 0;
        if (!empty($changedIds)) {
            $bots = $this->builderbot_model->getConfigs(true);
            // Filtrar bots aptos para tracking: excluir el de garantías. Si
            // mañana se agregan más bots no-vendedores, este filtro evita
            // mandar mensajes de tracking desde un número que no corresponde.
            $trackingBots = array();
            foreach ($bots as $b) {
                if (!preg_match('/garant/i', (string)$b->name)) {
                    $trackingBots[] = $b;
                }
            }

            $botByVendor = array();
            $botByStore = array();
            foreach ($trackingBots as $b) {
                $botByVendor[$b->default_vendor_id] = $b;
                $botByStore[$b->default_store_id] = $b;
            }

            foreach ($changedIds as $gId) {
                $shipment = $this->shipping_model->getShipment($gId);
                if (!$shipment) continue;

                $phone = isset($shipment->client_phone) ? preg_replace('/[^0-9]/', '', $shipment->client_phone) : '';
                if (strlen($phone) === 10) $phone = '57' . $phone;
                if (strlen($phone) < 12) continue;

                $vendorId = isset($shipment->vendorId) ? $shipment->vendorId : null;
                $storeId  = isset($shipment->storeId)  ? $shipment->storeId  : null;

                // Lookup en cascada: vendor → store → fallback al primer bot
                // de tracking activo. Antes, si la guía tenía un vendor que no
                // matcheaba ninguno de los 3 bots, simplemente se saltaba y
                // el cliente nunca recibía aviso.
                $bot = ($vendorId !== null && isset($botByVendor[$vendorId])) ? $botByVendor[$vendorId] : null;
                if (!$bot && $storeId !== null) {
                    $bot = isset($botByStore[$storeId]) ? $botByStore[$storeId] : null;
                }
                if (!$bot && !empty($trackingBots)) {
                    $bot = reset($trackingBots);
                }
                if (!$bot) continue;

                $message = $this->_buildTrackingMessage($shipment);

                // Reintentar hasta 2 veces ante fallos transitorios (BuilderBot
                // cae intermitentemente). Si tras 2 intentos sigue fallando,
                // dejamos lastNotifiedStatus sin tocar para que el próximo
                // ciclo del cron lo reintente.
                $success = false;
                $lastHttp = null;
                for ($attempt = 1; $attempt <= 2; $attempt++) {
                    $result = $this->builderbot_lib->sendMessage($bot, $phone, $message);
                    $lastHttp = $result['http_code'] ?? null;
                    if (!empty($result['success'])) { $success = true; break; }
                    if ($attempt < 2) sleep(2);
                }

                if ($success) {
                    $notified++;
                    $this->db->where('id', $gId)->update('shipping_guides', array('lastNotifiedStatus' => $shipment->estadoNombre));
                    $this->log_cron("  ✓ WhatsApp enviado a {$shipment->client_name} ({$phone}) — Estado: {$shipment->estadoNombre}");
                } else {
                    $this->log_cron("  ✗ Error WhatsApp a {$shipment->client_name} (http={$lastHttp}, 2 intentos)");
                }

                usleep(1000000);
            }
        }

        $this->log_cron("Procesadas: {$total} | Actualizadas: {$updated} | Notificadas: {$notified} | Errores: {$errors}");

        if (!is_cli()) {
            header('Content-Type: application/json');
            echo json_encode(array(
                'processed' => $total,
                'checked' => $checked,
                'updated' => $updated,
                'notified' => $notified,
                'errors' => $errors,
                'timestamp' => date('Y-m-d H:i:s')
            ));
        }
    }

    /**
     * Construir mensaje WhatsApp personalizado según estado del envío
     */
    private function _buildTrackingMessage($shipment)
    {
        $clientName = isset($shipment->client_name) ? $shipment->client_name : 'Cliente';
        $guia = $shipment->numeroPreenvio;
        $trackUrl = 'https://interrapidisimo.com/sigue-tu-envio/?guia=' . $guia;
        $destino = isset($shipment->ciudadDestinoNombre) ? $shipment->ciudadDestinoNombre : '';
        $esCp = isset($shipment->isContrapago) && $shipment->isContrapago;
        $valorCp = isset($shipment->contrapagoCost) ? $shipment->contrapagoCost : 0;

        switch ($shipment->status) {
            case 'creado':
            case 'cotizado':
            case 'recogida_solicitada':
                return "Hola {$clientName} 👋\n\nTu pedido ya fue creado y pronto será recogido por *Interrapidísimo*.\n\n📦 *Guía:* {$guia}\n📍 *Destino:* {$destino}\n\nTe avisaremos cuando esté en camino. 🚚";

            case 'en_transito':
                return "Hola {$clientName} 👋\n\n¡Tu pedido ya está en camino! 🚚\n\n📦 *Guía:* {$guia}\n📍 *Estado:* En tránsito hacia *{$destino}*\n\n🔗 Rastrea tu envío aquí:\n{$trackUrl}\n\nTe avisaremos cuando esté cerca. 😊";

            case 'en_reparto':
                $pago = ($esCp && $valorCp > 0) ? "\n\n💰 *Importante:* Debes pagar *$" . number_format($valorCp, 0, ',', '.') . "* al momento de recibir tu pedido." : '';
                return "Hola {$clientName} 👋\n\n🎉 ¡Tu pedido está en reparto! Prepárate para recibirlo hoy.\n\n📦 *Guía:* {$guia}\n📍 *Estado:* En reparto en *{$destino}*{$pago}\n\nAsegúrate de estar disponible en la dirección de entrega. 🏠";

            case 'entregado':
                return "Hola {$clientName} 👋\n\n✅ ¡Tu pedido ha sido *entregado* exitosamente!\n\n📦 *Guía:* {$guia}\n\nEsperamos que disfrutes tu compra. ¡Gracias por confiar en nosotros! 🙏";

            case 'novedad':
                $obs = isset($shipment->observations) ? $shipment->observations : '';
                $estadoInter = isset($shipment->estadoNombre) ? $shipment->estadoNombre : '';
                $estadoGuiaCode = isset($shipment->estadoGuia) ? (int)$shipment->estadoGuia : 0;
                $pago = ($esCp && $valorCp > 0) ? "\n\n💰 *Recuerda:* Debes tener listos *$" . number_format($valorCp, 0, ',', '.') . "* para pagar al momento de recibir." : '';

                // Reenvío o segundo intento
                if ($estadoGuiaCode == 12) {
                    return "Hola {$clientName} 👋\n\nInterrapidísimo intentará *nuevamente* entregar tu pedido. 🚚\n\n📦 *Guía:* {$guia}\n📍 *Destino:* {$destino}\n📝 *Estado:* Reenvío{$pago}\n\nPor favor asegúrate de estar disponible en la dirección de entrega. 🙏\n\n🔗 Rastrea tu envío:\n{$trackUrl}";
                }
                // Intento de entrega fallido
                if ($estadoGuiaCode == 7) {
                    return "Hola {$clientName} 👋\n\n⚠️ Interrapidísimo intentó entregar tu pedido pero no fue posible.\n\n📦 *Guía:* {$guia}\n📍 *Destino:* {$destino}\n📝 *Estado:* Intento de entrega{$pago}\n\nSe realizará un nuevo intento. Asegúrate de estar disponible en la dirección de entrega. 🏠\n\n🔗 Rastrea tu envío:\n{$trackUrl}";
                }
                // Telemercadeo - Inter contactando al cliente
                if ($estadoGuiaCode == 8) {
                    return "Hola {$clientName} 👋\n\n📞 Interrapidísimo te estará contactando para coordinar la entrega de tu pedido.\n\n📦 *Guía:* {$guia}\n📍 *Destino:* {$destino}{$pago}\n\nPor favor atiende la llamada de Interrapidísimo para coordinar. 🙏\n\n🔗 Rastrea tu envío:\n{$trackUrl}";
                }
                // Novedad genérica
                return "Hola {$clientName} 👋\n\n⚠️ Tu envío presenta una novedad y no pudo ser entregado.\n\n📦 *Guía:* {$guia}\n📍 *Estado:* " . ($estadoInter ?: 'Novedad') . "\n" . ($obs ? "📝 *Detalle:* {$obs}\n" : '') . "\nPor favor comunícate con nosotros para coordinar la entrega. 📞";

            case 'anulado':
                return "Hola {$clientName} 👋\n\n📦 Tu envío con guía *{$guia}* ha sido anulado.\n\nSi tienes alguna duda, por favor comunícate con nosotros. 📞";

            default:
                $estado = $shipment->estadoNombre ?: $shipment->status;
                return "Hola {$clientName} 👋\n\nTe informamos sobre tu envío:\n\n📦 *Guía:* {$guia}\n📍 *Estado:* {$estado}\n\n🔗 Rastrea tu pedido aquí:\n{$trackUrl}\n\nSi tienes alguna duda, responde este mensaje. 🙏";
        }
    }

    /**
     * Enviar notificación WhatsApp a clientes con guías activas.
     * Informa el estado actual y un link de rastreo.
     *
     * Ejecutar: php index.php cron notify_clients_tracking
     * O via web: /cron/notify_clients_tracking?key=YOUR_CRON_KEY
     */
    public function notify_clients_tracking()
    {
        $this->log_cron('=== Enviando notificaciones de tracking a clientes ===');

        $this->load->model('shipping_model');
        $this->load->model('builderbot_model');
        $this->load->library('builderbot_lib');

        // Obtener guías activas con datos de cliente y vendedor
        $guides = $this->db->select('sg.id, sg.numeroPreenvio, sg.status, sg.estadoNombre, sg.ciudadDestinoNombre,
                                     c.name as client_name, c.cellphone as client_phone,
                                     i.vendorId')
            ->from('shipping_guides sg')
            ->join('invoices i', 'i.idInvoice = sg.invoiceId', 'left')
            ->join('clients c', 'c.idClient = i.clientId', 'left')
            ->where('sg.status !=', 'entregado')
            ->where('sg.status !=', 'anulado')
            ->where('c.cellphone IS NOT NULL')
            ->where('c.cellphone !=', '')
            ->get()->result();

        if (empty($guides)) {
            $this->log_cron('No hay guías activas con cliente para notificar');
            if (!is_cli()) {
                header('Content-Type: application/json');
                echo json_encode(array('sent' => 0, 'message' => 'No hay guías para notificar'));
            }
            return;
        }

        // Cargar bots activos e indexar por vendor_id y store_id
        $bots = $this->builderbot_model->getConfigs(true);
        $botByVendor = array();
        $botByStore = array();
        foreach ($bots as $b) {
            $botByVendor[$b->default_vendor_id] = $b;
            $botByStore[$b->default_store_id] = $b;
        }

        $sent = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($guides as $g) {
            // Buscar el bot por vendedor, fallback por tienda
            $bot = isset($botByVendor[$g->vendorId]) ? $botByVendor[$g->vendorId] : null;
            if (!$bot) {
                // Buscar store de la guía
                $guideStore = $this->db->select('storeId')->where('id', $g->id)->get('shipping_guides')->row();
                if ($guideStore) {
                    $bot = isset($botByStore[$guideStore->storeId]) ? $botByStore[$guideStore->storeId] : null;
                }
            }
            if (!$bot) {
                $this->log_cron("  Sin bot para vendedor {$g->vendorId}, saltando guía {$g->numeroPreenvio}");
                $skipped++;
                continue;
            }

            // Normalizar celular
            $phone = preg_replace('/[^0-9]/', '', $g->client_phone);
            if (strlen($phone) === 10) $phone = '57' . $phone;
            if (strlen($phone) < 12) {
                $this->log_cron("  Celular inválido para {$g->client_name}: {$g->client_phone}");
                $skipped++;
                continue;
            }

            // Construir mensaje
            $trackUrl = 'https://interrapidisimo.com/sigue-tu-envio/?guia=' . $g->numeroPreenvio;
            $estado = $g->estadoNombre ?: $g->status;
            $destino = $g->ciudadDestinoNombre ? " con destino *{$g->ciudadDestinoNombre}*" : '';

            $message = "Hola {$g->client_name} 👋\n\n"
                     . "Te informamos sobre tu envío:\n\n"
                     . "📦 *Guía:* {$g->numeroPreenvio}\n"
                     . "📍 *Estado:* {$estado}{$destino}\n\n"
                     . "🔗 Rastrea tu pedido aquí:\n{$trackUrl}\n\n"
                     . "Si tienes alguna duda, responde este mensaje. ¡Gracias por tu compra! 🙏";

            // Enviar WhatsApp via BuilderBot
            $result = $this->builderbot_lib->sendMessage($bot, $phone, $message);

            if ($result['success']) {
                $sent++;
                $this->db->where('id', $g->id)->update('shipping_guides', array('lastNotifiedStatus' => $g->estadoNombre));
                $this->log_cron("  ✓ Enviado a {$g->client_name} ({$phone}) - Guía {$g->numeroPreenvio} - Estado: {$g->estadoNombre}");
            } else {
                $errors++;
                $httpCode = isset($result['http_code']) ? $result['http_code'] : 'N/A';
                $this->log_cron("  ✗ Error enviando a {$g->client_name}: HTTP {$httpCode}");
            }

            // Pausa entre mensajes
            usleep(1000000); // 1 segundo
        }

        $this->log_cron("=== Notificaciones completadas: Enviadas={$sent} | Errores={$errors} | Saltadas={$skipped} ===");

        if (!is_cli()) {
            header('Content-Type: application/json');
            echo json_encode(array(
                'sent' => $sent,
                'errors' => $errors,
                'skipped' => $skipped,
                'timestamp' => date('Y-m-d H:i:s')
            ));
        }
    }

    /**
     * Poblar columna permiteContrapago en dane_municipalities
     * Cotiza a cada destino con contrapago=TRUE. Si la API responde con servicios, sí permite.
     *
     * Ejecutar: /cron/seed_contrapago?key=YOUR_CRON_KEY&offset=0&limit=50
     * (ejecutar varias veces incrementando offset hasta cubrir los 1472 destinos)
     */
    /**
     * Recuperación de carritos abandonados de la tienda pública.
     *
     * Lógica:
     *   - Carritos con status='pending' y created_at entre 24h y 72h atrás
     *   - Mandar UN solo WhatsApp por cliente, marcar status='reminded'
     *   - Si después de 72h no completó, marcar 'expired' (no más mensajes)
     *
     * Ejecutar: /cron/recover_abandoned_carts?key=YOUR_CRON_KEY
     * Crontab sugerido: cada 2 horas en horarios comerciales
     *   0 9-21/2 * * *  curl -s "https://ledxury.com/cron/recover_abandoned_carts?key=..."
     */
    /**
     * Alerta de stock bajo: identifica productos que se han vendido en el último
     * mes Y cuyo stock total (todas las tiendas) está por debajo del umbral.
     * Manda WhatsApp al admin (default vendor del bot 1).
     *
     * Solo alerta una vez por producto cada 7 días para evitar spam.
     *
     * Ejecutar:
     *   /cron/low_stock_alert?key=YOUR_CRON_KEY
     * Crontab sugerido (1 vez al día, 8 AM):
     *   0 8 * * *  curl -s "https://ledxury.com/cron/low_stock_alert?key=..."
     */
    /**
     * Health check de bots BuilderBot Cloud.
     * Verifica que cada bot activo pueda hacer una llamada a la API
     * (sin enviar mensaje real) — usa GET de instructions del Asistente IA
     * como ping. Si un bot está caído (HTTP error o timeout), avisa al admin.
     *
     * Solo alerta cuando un bot que ANTES funcionaba pasa a fallar (transición),
     * o cuando lleva 24h fallando consecutivas. No spamea.
     *
     * Ejecutar: /cron/bot_health_check?key=YOUR_CRON_KEY
     * Crontab sugerido (cada hora):
     *   0 * * * *  curl -s "https://ledxury.com/cron/bot_health_check?key=..."
     */
    /**
     * Auto-mensaje "estamos contigo" cuando el bot/vendedor tarda > 90 segundos
     * en responder a un cliente. Reduce bounce rate de clientes impacientes.
     *
     * Lógica:
     *   - Conversaciones con last_direction='in' y last_message_at entre 90s y 5min atrás
     *   - pending_holder_sent_at IS NULL (no le mandamos placeholder en este turno)
     *   - Excluir spam (tag 9) y ventas confirmadas (tag 2)
     *
     * Cuando el bot/vendedor finalmente responde (outgoing message), saveConversationMessage
     * resetea pending_holder_sent_at automáticamente (vía actualización en Builderbot_model).
     *
     * Crontab sugerido: cada minuto (tunable):
     *   * * * * *  curl -s "https://ledxury.com/cron/check_pending_responses?key=..."
     */
    public function check_pending_responses()
    {
        $this->load->library('builderbot_lib');
        $this->load->model('builderbot_model');

        $threshold_sec = (int)($this->input->get('seconds') ?: 90);
        $max_age_sec   = 300; // 5 min: si lleva más, asumimos que ya el cliente se desinteresó

        $cutoff_min = date('Y-m-d H:i:s', time() - $max_age_sec);
        $cutoff_max = date('Y-m-d H:i:s', time() - $threshold_sec);

        $candidates = $this->db
            ->select('bc.*, bcfg.name AS bot_name, bcfg.api_key, bcfg.bot_id AS bot_external_id, bcfg.base_url', false)
            ->from('bot_conversations bc')
            ->join('builderbot_configs bcfg', 'bcfg.id = bc.bot_config_id', 'left')
            ->where('bc.last_direction', 'in')
            ->where('bc.last_message_at >=', $cutoff_min)
            ->where('bc.last_message_at <=', $cutoff_max)
            ->where('bc.pending_holder_sent_at IS NULL', null, false)
            ->where('(bc.tag_id IS NULL OR bc.tag_id NOT IN (2, 9))', null, false)
            ->where('bcfg.is_active', 1)
            ->order_by('bc.last_message_at', 'ASC')
            ->limit(50)
            ->get()->result();

        if (empty($candidates)) {
            if (!is_cli()) { header('Content-Type: application/json'); echo json_encode(array('count' => 0)); }
            return;
        }

        // Mensajes rotativos (variación natural — no siempre el mismo texto)
        $messages = array(
            "👋 Estamos contigo en un momento, ya te respondemos.",
            "Hola, gracias por escribir 🙏 Estamos atendiendo, en breve te respondemos.",
            "👋 Recibido tu mensaje, te respondemos en un momento.",
        );

        $sent = 0;
        foreach ($candidates as $c) {
            // Reconstruir bot config (no es objeto pero los campos están)
            $bot = (object) array(
                'id'         => $c->bot_config_id,
                'bot_id'     => $c->bot_external_id,
                'api_key'    => $c->api_key,
                'base_url'   => $c->base_url ?: 'https://app.builderbot.cloud',
            );
            $phone = preg_replace('/[^0-9]/', '', $c->phone);
            if (strlen($phone) === 10) $phone = '57' . $phone;
            if (strlen($phone) < 11) continue;

            $message = $messages[array_rand($messages)];
            try {
                $r = $this->builderbot_lib->sendMessage($bot, $phone, $message);
                if (!empty($r['success'])) {
                    $this->db->where('id', $c->id)->update('bot_conversations', array(
                        'pending_holder_sent_at' => date('Y-m-d H:i:s'),
                    ));
                    $sent++;
                }
            } catch (\Throwable $e) {
                $this->log_cron("  pending response sendError conv={$c->id}: " . $e->getMessage());
            }
            usleep(400000);
        }

        if ($sent > 0) {
            $this->log_cron("=== Pending responses: enviados {$sent}/" . count($candidates) . " ===");
        }
        if (!is_cli()) {
            header('Content-Type: application/json');
            echo json_encode(array('candidates' => count($candidates), 'sent' => $sent));
        }
    }

    public function bot_health_check()
    {
        $this->load->library('builderbot_lib');
        $this->load->model('builderbot_model');
        $this->log_cron("=== Bot health check ===");

        $bots = $this->builderbot_model->getConfigs(true);
        if (empty($bots)) { $this->log_cron('No hay bots activos'); return; }

        $cache_file = APPPATH . 'cache/bot_health_state.json';
        $state = is_file($cache_file) ? (json_decode(file_get_contents($cache_file), true) ?: array()) : array();
        $now = time();
        $alerts = array();

        foreach ($bots as $bot) {
            $key = 'bot_' . $bot->id;
            $prev = isset($state[$key]) ? $state[$key] : array('healthy' => true, 'failing_since' => 0, 'last_alerted' => 0);

            $result = $this->builderbot_lib->getAssistantInstructions($bot);
            $healthy = ($result !== null);

            $this->log_cron("  Bot {$bot->id} ({$bot->name}): " . ($healthy ? 'OK' : 'FAIL'));

            // Decidir si alertar
            if (!$healthy) {
                // Si pasa de healthy → unhealthy, alertar. Si lleva > 24h fallando, re-alertar.
                $failing_since = $prev['failing_since'] ?: $now;
                $last_alerted = (int)$prev['last_alerted'];
                $just_failed = $prev['healthy'];
                $long_outage = ($now - $failing_since) > 86400 && ($now - $last_alerted) > 86400;

                if ($just_failed || $long_outage) {
                    $alerts[] = array(
                        'bot' => $bot,
                        'duration_hours' => round(($now - $failing_since) / 3600, 1),
                        'is_new' => $just_failed,
                    );
                    $prev['last_alerted'] = $now;
                }
                $prev['failing_since'] = $failing_since;
                $prev['healthy'] = false;
            } else {
                if (!$prev['healthy']) {
                    // Recuperación — opcionalmente avisar
                    $alerts[] = array(
                        'bot' => $bot,
                        'duration_hours' => round(($now - $prev['failing_since']) / 3600, 1),
                        'is_recovery' => true,
                    );
                }
                $prev['healthy'] = true;
                $prev['failing_since'] = 0;
            }

            $state[$key] = $prev;
        }

        @file_put_contents($cache_file, json_encode($state));

        // Enviar alertas (si hay) al admin del bot 1
        if (!empty($alerts)) {
            $bot1 = $this->db->where('id', 1)->where('is_active', 1)->get('builderbot_configs')->row();
            if ($bot1) {
                $admin = $this->db->where('idUser', $bot1->default_vendor_id)->get('users')->row();
                if ($admin && !empty($admin->cellphone)) {
                    $admin_phone = preg_replace('/[^0-9]/', '', $admin->cellphone);
                    if (strlen($admin_phone) === 10) $admin_phone = '57' . $admin_phone;

                    $lines = array("🤖 *Health check bots*", "");
                    foreach ($alerts as $a) {
                        if (!empty($a['is_recovery'])) {
                            $lines[] = "✅ Bot *{$a['bot']->name}* RECUPERADO tras {$a['duration_hours']}h caído.";
                        } elseif (!empty($a['is_new'])) {
                            $lines[] = "⚠️ Bot *{$a['bot']->name}* dejó de responder. Acabamos de detectar la falla.";
                        } else {
                            $lines[] = "❗ Bot *{$a['bot']->name}* lleva {$a['duration_hours']}h caído sin recuperar.";
                        }
                    }
                    $lines[] = "";
                    $lines[] = "Acción: revisar panel BuilderBot o llamar al soporte.";
                    $body = implode("\n", $lines);

                    $r = $this->builderbot_lib->sendMessage($bot1, $admin_phone, $body);
                    $this->log_cron("Alertas enviadas: " . count($alerts) . " — HTTP " . ($r['http_code'] ?? '?'));
                }
            }
        }

        if (!is_cli()) {
            header('Content-Type: application/json');
            echo json_encode(array(
                'bots'   => count($bots),
                'alerts' => count($alerts),
                'state'  => $state,
            ));
        }
    }

    public function low_stock_alert()
    {
        $this->load->library('builderbot_lib');
        $this->load->model('builderbot_model');
        $threshold = (int)($this->input->get('threshold') ?: 10); // configurable via ?threshold=20

        $this->log_cron("=== Alerta stock bajo (umbral: {$threshold}) ===");

        // Productos vendidos en último mes (top 200) con stock total bajo el umbral.
        // Excluir agotados manualmente (ya sabemos), excluir códigos especiales.
        $rows = $this->db->query("
            SELECT
                p.idProduct,
                p.description,
                COALESCE(SUM(inv.stock), 0) AS stock_total,
                ventas.qty_30d AS vendidos_30d,
                f.name AS familia
            FROM products p
            INNER JOIN (
                SELECT bd.productId, SUM(bd.quantity) AS qty_30d
                FROM budget_detail bd
                JOIN budgets b ON b.idBudget = bd.budgetId
                WHERE b.deleted = 0
                  AND b.date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  AND bd.productId NOT LIKE 'PENDIENTE%'
                GROUP BY bd.productId
                HAVING qty_30d > 0
            ) ventas ON ventas.productId = p.idProduct
            LEFT JOIN inventory inv ON inv.idProduct = p.idProduct
            LEFT JOIN product_families f ON f.idFamily = p.family
            LEFT JOIN blocked_products bp ON bp.product_code = p.idProduct
            WHERE p.deleted = 0
              AND bp.id IS NULL
            GROUP BY p.idProduct
            HAVING stock_total < ?
            ORDER BY ventas.qty_30d DESC
            LIMIT 50
        ", array($threshold))->result();

        if (empty($rows)) {
            $this->log_cron("No hay productos con stock bajo el umbral");
            if (!is_cli()) { header('Content-Type: application/json'); echo json_encode(array('count' => 0)); }
            return;
        }

        // Filtrar los ya alertados en últimos 7 días (cache file)
        $cache_file = APPPATH . 'cache/low_stock_last_alert.json';
        $last_alerts = is_file($cache_file) ? (json_decode(file_get_contents($cache_file), true) ?: array()) : array();
        $cutoff = time() - 7 * 86400;
        $new_alerts = array();
        foreach ($rows as $r) {
            $last = isset($last_alerts[$r->idProduct]) ? (int)$last_alerts[$r->idProduct] : 0;
            if ($last < $cutoff) $new_alerts[] = $r;
        }

        if (empty($new_alerts)) {
            $this->log_cron("Todos los productos con stock bajo ya alertados en últimos 7d");
            if (!is_cli()) { header('Content-Type: application/json'); echo json_encode(array('count' => 0, 'reason' => 'cooldown')); }
            return;
        }

        // Construir mensaje
        $lines = array();
        $lines[] = "⚠️ *Alerta de stock bajo* (umbral: {$threshold} unidades)";
        $lines[] = "";
        $lines[] = "Productos vendidos en últimos 30 días con stock crítico:";
        $lines[] = "";
        foreach (array_slice($new_alerts, 0, 30) as $r) {
            $lines[] = sprintf("• *%s* — stock: %d, vendidos 30d: %d",
                $r->idProduct, (int)$r->stock_total, (int)$r->vendidos_30d);
        }
        if (count($new_alerts) > 30) $lines[] = "  ... y " . (count($new_alerts) - 30) . " más";
        $lines[] = "";
        $lines[] = "📦 Considerar reorden urgente.";
        $lines[] = "Esta alerta se envía 1 vez por producto cada 7 días.";
        $body = implode("\n", $lines);

        // Mandar al admin (default_vendor_id del bot 1 — Jorge)
        $bot = $this->db->where('id', 1)->where('is_active', 1)->get('builderbot_configs')->row();
        if (!$bot) { $this->log_cron('ERROR: bot 1 no activo'); return; }

        $admin_user = $this->db->where('idUser', $bot->default_vendor_id)->get('users')->row();
        if (!$admin_user || empty($admin_user->cellphone)) {
            $this->log_cron('ERROR: admin sin cellphone configurado en users');
            return;
        }

        $admin_phone = preg_replace('/[^0-9]/', '', $admin_user->cellphone);
        if (strlen($admin_phone) === 10) $admin_phone = '57' . $admin_phone;

        $r = $this->builderbot_lib->sendMessage($bot, $admin_phone, $body);
        if (!empty($r['success'])) {
            // Actualizar cache con timestamp
            $now = time();
            foreach ($new_alerts as $alert) $last_alerts[$alert->idProduct] = $now;
            @file_put_contents($cache_file, json_encode($last_alerts));
            $this->log_cron("✓ Alerta enviada a {$admin_phone} con " . count($new_alerts) . " productos");
        } else {
            $this->log_cron("✗ Error enviando alerta: HTTP " . ($r['http_code'] ?? '?'));
        }

        if (!is_cli()) {
            header('Content-Type: application/json');
            echo json_encode(array(
                'count'    => count($new_alerts),
                'sent'     => !empty($r['success']),
                'products' => array_slice(array_map(function($r){ return $r->idProduct; }, $new_alerts), 0, 30),
            ));
        }
    }

    public function recover_abandoned_carts()
    {
        $this->load->library('builderbot_lib');
        $this->log_cron('=== Recuperación de carritos abandonados ===');

        // 1) Marcar como expired los carritos pending/reminded de hace > 72h
        //    (ya no vale la pena molestar al cliente)
        $expired_cutoff = date('Y-m-d H:i:s', time() - 72 * 3600);
        $this->db->where('created_at <', $expired_cutoff)
            ->where_in('status', array('pending', 'reminded'))
            ->update('tienda_abandoned_carts', array('status' => 'expired'));
        $expired = $this->db->affected_rows();
        if ($expired > 0) $this->log_cron("Expirados: {$expired}");

        // 2) Enviar reminder a carritos pending de entre 24h y 72h
        $remind_after  = date('Y-m-d H:i:s', time() - 24 * 3600);
        $remind_before = $expired_cutoff;
        $candidates = $this->db
            ->where('status', 'pending')
            ->where('created_at <=', $remind_after)
            ->where('created_at >=', $remind_before)
            ->order_by('id', 'ASC')
            ->limit(50)
            ->get('tienda_abandoned_carts')
            ->result();

        $this->log_cron("Candidatos para WhatsApp: " . count($candidates));
        if (empty($candidates)) return;

        // Bot 1 (Medellín) por defecto para mandar mensajes
        $bot = $this->db->where('id', 1)->where('is_active', 1)->get('builderbot_configs')->row();
        if (!$bot) {
            $this->log_cron('ERROR: bot 1 no activo, no se puede mandar reminders');
            return;
        }

        $sent = 0; $skipped = 0;
        foreach ($candidates as $cart) {
            // No molestar dos veces a un cliente: si ya tiene un budget reciente, marcar recovered
            $recent_budget = $this->db->select('idBudget')
                ->from('budgets')
                ->join('clients', 'clients.idClient = budgets.clientId', 'left')
                ->like('clients.cellphone', $cart->phone, 'both')
                ->where('budgets.created_at >=', $cart->created_at)
                ->where('budgets.deleted', 0)
                ->order_by('budgets.idBudget', 'DESC')
                ->limit(1)
                ->get()->row();
            if ($recent_budget) {
                $this->db->where('id', $cart->id)->update('tienda_abandoned_carts', array(
                    'status' => 'recovered',
                    'recovered_budget_id' => (int)$recent_budget->idBudget,
                ));
                $skipped++;
                continue;
            }

            // Construir mensaje
            $items = json_decode($cart->cart_json, true);
            if (!is_array($items) || empty($items)) {
                $this->db->where('id', $cart->id)->update('tienda_abandoned_carts', array('status' => 'expired'));
                continue;
            }
            $first_name = $cart->name ? explode(' ', trim($cart->name))[0] : 'cliente';
            $lines = array();
            $lines[] = "Hola " . $first_name . " 👋";
            $lines[] = "";
            $lines[] = "Vi que dejaste algunos productos en tu carrito de Ledxury y quería ayudarte a finalizar:";
            $lines[] = "";
            foreach (array_slice($items, 0, 5) as $it) {
                $lines[] = "  • " . (int)$it['qty'] . "x " . mb_substr((string)($it['name'] ?? $it['id']), 0, 40);
            }
            if (count($items) > 5) $lines[] = "  ... y " . (count($items) - 5) . " más";
            $lines[] = "";
            $lines[] = "*Total: $" . number_format((int)$cart->cart_total, 0, ',', '.') . "* (pago contra entrega, envío con Interrapidísimo)";
            $lines[] = "";
            $lines[] = "Termina tu pedido aquí 👇";
            $lines[] = rtrim(base_url(), '/') . "/tienda/checkout";
            $lines[] = "";
            $lines[] = "Si querés cambiar algo o tenés dudas, respóndeme por aquí mismo.";
            $body = implode("\n", $lines);

            // Mandar
            $waPhone = strlen($cart->phone) === 10 ? '57' . $cart->phone : $cart->phone;
            try {
                $r = $this->builderbot_lib->sendMessage($bot, $waPhone, $body);
                if (!empty($r['success'])) {
                    $this->db->where('id', $cart->id)->update('tienda_abandoned_carts', array(
                        'status'      => 'reminded',
                        'reminded_at' => date('Y-m-d H:i:s'),
                    ));
                    $sent++;
                    $this->log_cron("  Reminder OK cart_id={$cart->id} phone={$cart->phone} total={$cart->cart_total}");
                } else {
                    $this->log_cron("  Reminder FAIL cart_id={$cart->id} HTTP=" . ($r['http_code'] ?? '?'));
                }
            } catch (\Throwable $e) {
                $this->log_cron("  Reminder EXCEPTION cart_id={$cart->id}: " . $e->getMessage());
            }
            // Throttle: 1 segundo entre mensajes para no saturar la API
            usleep(800000);
        }

        $this->log_cron("=== Done. Enviados: {$sent}, ya recuperados: {$skipped}, expirados: {$expired} ===");
        if (!is_cli()) {
            header('Content-Type: application/json');
            echo json_encode(array(
                'sent'      => $sent,
                'skipped'   => $skipped,
                'expired'   => $expired,
                'candidates' => count($candidates),
            ));
        }
    }

    public function seed_contrapago()
    {
        $this->load->library('interrapidisimo_lib');
        set_time_limit(600);

        $offset = (int)($this->input->get('offset') ?: 0);
        $limit = (int)($this->input->get('limit') ?: 50);

        $localidades = $this->db->select('id, daneCode, shortName')
            ->from('dane_municipalities')
            ->where('permiteContrapago IS NULL')
            ->order_by('id', 'ASC')
            ->limit($limit, $offset)
            ->get()->result();

        $total = count($localidades);
        $updated = 0;
        $fecha = date('d-m-Y');

        foreach ($localidades as $loc) {
            $result = $this->interrapidisimo_lib->cotizar($loc->daneCode, 1, 50000, 1, true);

            $permite = 0;
            if ($result && is_array($result) && !empty($result)) {
                $permite = 1;
            }

            $this->db->where('id', $loc->id)->update('dane_municipalities', array('permiteContrapago' => $permite));
            $updated++;

            usleep(300000); // 0.3s entre consultas
        }

        $remaining = $this->db->where('permiteContrapago IS NULL')->count_all_results('dane_municipalities');

        header('Content-Type: application/json');
        echo json_encode(array(
            'processed' => $updated,
            'remaining' => $remaining,
            'next_offset' => $offset + $limit,
            'timestamp' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Procesar sheets de todos los bots: crear presupuestos desde filas nuevas.
     * Lee los 3 sheets (Medellín, Barranquilla, Bogotá) y procesa filas sin ID.
     *
     * Ejecutar: /cron/process_bot_sheets?key=YOUR_CRON_KEY
     */
    public function process_bot_sheets()
    {
        $this->log_cron('=== Procesando sheets de bots ===');

        $this->load->model('builderbot_model');
        $bots = $this->builderbot_model->getConfigs(true);
        $base_url = rtrim(config_item('base_url'), '/');

        $results = array();
        $total_created = 0;
        $total_errors = 0;

        foreach ($bots as $bot) {
            if (empty($bot->sheet_id)) continue;

            // Pasar sheet/script del bot config directamente — evita depender de users.bot_sheet_id
            $params = array(
                'cron_key'   => 'sisvent_cron_2024_tracking',
                'vendor'     => $bot->default_vendor_id,
                'sheet_id'   => $bot->sheet_id,
                'gid'        => $bot->sheet_gid ?: '0',
                'limit'      => 100,
            );
            if (!empty($bot->script_url)) $params['script_url'] = $bot->script_url;

            $url = $base_url . '/sisvent/rest/BotImport/processSheet?' . http_build_query($params);

            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 180,
                CURLOPT_SSL_VERIFYPEER => false,
            ));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($response, true);
            $created = 0;
            $errors = 0;

            if ($data && isset($data['summary'])) {
                $created = (int)($data['summary']['created'] ?? 0);
                $errors = (int)($data['summary']['errors'] ?? 0);
            }

            $results[] = array(
                'bot' => $bot->name,
                'created' => $created,
                'errors' => $errors,
                'http_code' => $httpCode,
            );
            $total_created += $created;
            $total_errors += $errors;

            $this->log_cron("  {$bot->name}: {$created} creados, {$errors} errores");
        }

        $this->log_cron("=== Total creados: {$total_created} | Errores: {$total_errors} ===");

        if (!is_cli()) {
            header('Content-Type: application/json');
            echo json_encode(array(
                'created' => $total_created,
                'errors' => $total_errors,
                'by_bot' => $results,
                'timestamp' => date('Y-m-d H:i:s')
            ));
        }
    }

    /**
     * Ejecuta las purchase_rules cuyo next_run_at ya venció.
     * Genera por cada una una `purchase` en estado borrador (state=0) con sus
     * `purchase_detail`, calculando los productos según el filtro de la rule:
     *   - all_sold      → SUM(invoices_detail.quantity) en los últimos N días
     *   - specific_list → SKUs explícitos del JSON product_list
     *   - all_provider  → todos los productos atados al proveedor (product_providers)
     *
     * Si la rule tiene exclude_blocked=1, se filtran los SKUs presentes en
     * blocked_products (agotados en el proveedor).
     *
     * Endpoint: GET /cron/run_purchase_rules?key=...
     * Crontab sugerido: cada hora a la hora redonda. Las rules calculan su
     * propio next_run_at; el cron solo decide si "tocó". Esto permite que el
     * usuario cambie la frecuencia sin tocar crontab.
     */
    public function run_purchase_rules()
    {
        $this->log_cron('=== Ejecutando purchase_rules pendientes ===');
        date_default_timezone_set('America/Bogota');
        $now = date('Y-m-d H:i:s');

        // Comparamos contra NOW() de MySQL para evitar desfases entre el TZ
        // de PHP (Bogotá) y el de MySQL (UTC en este servidor).
        $rules = $this->db
            ->where('active', 1)
            ->where('deleted', 0)
            ->where('next_run_at <= NOW()', null, false)
            ->order_by('next_run_at', 'ASC')
            ->get('purchase_rules')->result();

        if (empty($rules)) {
            $this->log_cron('No hay rules pendientes en este ciclo.');
            if (!is_cli()) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => true, 'rules_run' => 0, 'timestamp' => $now]);
            }
            return;
        }

        $generated = 0;
        $details = [];

        foreach ($rules as $rule) {
            try {
                $po_id = $this->_run_purchase_rule($rule, $now);
                $details[] = [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'purchase_id' => $po_id,
                    'status' => $po_id ? 'ok' : 'sin_productos',
                ];
                if ($po_id) $generated++;
            } catch (Exception $e) {
                $this->log_cron("ERROR en rule {$rule->id} ({$rule->name}): " . $e->getMessage());
                $details[] = [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'purchase_id' => null,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->log_cron("Rules procesadas: " . count($rules) . " | POs generadas: {$generated}");

        if (!is_cli()) {
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => true,
                'rules_run' => count($rules),
                'pos_generated' => $generated,
                'details' => $details,
                'timestamp' => $now,
            ]);
        }
    }

    /**
     * Procesa una sola rule: arma la lista de productos+cantidades, crea la
     * `purchase` borrador y actualiza el next_run_at de la rule. Devuelve
     * el id de la purchase creada o null si la rule no produjo líneas.
     */
    private function _run_purchase_rule($rule, $now)
    {
        // 1. Resolver productos según product_filter
        $items = []; // [['idProduct'=>X, 'quantity'=>N], ...]

        if ($rule->product_filter === 'specific_list') {
            $skus = json_decode((string)$rule->product_list, true);
            if (!is_array($skus) || empty($skus)) {
                $this->log_cron("Rule {$rule->id}: product_list vacío.");
                $this->_update_rule_next_run($rule, $now);
                return null;
            }
            foreach ($skus as $sku) {
                $items[] = ['idProduct' => $sku, 'quantity' => 0]; // qty 0 = "incluir aunque no se haya vendido"
            }

        } elseif ($rule->product_filter === 'all_provider') {
            // Todos los productos vinculados al proveedor en product_providers
            $rows = $this->db->select('pp.productId')
                ->from('product_providers pp')
                ->where('pp.providerId', $rule->providerId)
                ->get()->result();
            foreach ($rows as $r) {
                $items[] = ['idProduct' => $r->productId, 'quantity' => 0];
            }

        } else {
            // Default: all_sold → suma invoice_details por SKU desde el cutoff calculado.
            // Si la rule tiene since_date seteado, ese override one-shot manda
            // (caso típico: arranque del módulo, queremos un cutoff fijo en lugar
            // de la ventana móvil). En el resto de casos, cutoff = NOW - lookback_days.
            if (!empty($rule->since_date)) {
                $cutoff = $rule->since_date;
                $this->log_cron("Rule {$rule->id}: usando since_date override → cutoff={$cutoff}");
            } else {
                $cutoff = date('Y-m-d 00:00:00', strtotime("-{$rule->lookback_days} days", strtotime($now)));
            }
            $rows = $this->db->select('idt.productId AS idProduct, SUM(idt.quantity) AS qty', false)
                ->from('invoice_details idt')
                ->join('invoices i', 'i.idInvoice = idt.invoiceId', 'inner')
                ->where('i.date >=', $cutoff)
                ->where('i.deleted', 0)
                ->where('idt.productId NOT IN ("PENDIENTE","AGOTADO")', null, false)
                ->group_by('idt.productId')
                ->having('qty > 0')
                ->get()->result();
            foreach ($rows as $r) {
                $items[] = ['idProduct' => $r->idProduct, 'quantity' => (int)$r->qty];
            }
        }

        if (empty($items)) {
            $this->log_cron("Rule {$rule->id}: 0 productos para incluir.");
            $this->_update_rule_next_run($rule, $now);
            return null;
        }

        // 2. Filtrar agotados si exclude_blocked. La tabla `blocked_products`
        // tiene la columna `product_code` (no idProduct).
        if ((int)$rule->exclude_blocked === 1) {
            $blocked_codes = $this->db->select('product_code')->get('blocked_products')->result();
            $blocked_set = [];
            foreach ($blocked_codes as $b) $blocked_set[strtoupper($b->product_code)] = true;
            $items = array_values(array_filter($items, function($it) use ($blocked_set) {
                return !isset($blocked_set[strtoupper($it['idProduct'])]);
            }));
        }

        // 3. Filtrar SKUs que no existen en products (defensa contra datos sucios)
        $skus = array_map(function($it){ return $it['idProduct']; }, $items);
        $existing = $this->db->select('idProduct')
            ->from('products')
            ->where_in('idProduct', $skus)
            ->where('deleted', 0)
            ->get()->result();
        $existing_set = [];
        foreach ($existing as $e) $existing_set[$e->idProduct] = true;
        $items = array_values(array_filter($items, function($it) use ($existing_set) {
            return isset($existing_set[$it['idProduct']]);
        }));

        if (empty($items)) {
            $this->log_cron("Rule {$rule->id}: 0 productos tras filtros.");
            $this->_update_rule_next_run($rule, $now);
            return null;
        }

        // 4. Crear supplier_order en estado borrador. Total/precios quedan en 0
        //    hasta que el user los complete al revisar antes de enviar.
        //    Reusamos el módulo de Reorder existente (supplier_orders + UI completa
        //    en sisvent/store/reorder) en vez de duplicar tablas y vistas.
        $this->load->model('supplierorders_model');
        $orderNumber = $this->supplierorders_model->getNextOrderNumber();

        $order_id = $this->supplierorders_model->save([
            'orderNumber' => $orderNumber,
            'providerId'  => (string)$rule->providerId, // supplier_orders.providerId es varchar
            'storeId'     => (int)$rule->storeId,
            'status'      => 'borrador',
            'orderDate'   => date('Y-m-d', strtotime($now)),
            'subtotal'    => 0,
            'tax'         => 0,
            'total'       => 0,
            'notes'       => "Generado automáticamente por rule #{$rule->id} '{$rule->name}'.",
        ]);

        // 5. Pre-cargar costos del proveedor desde product_providers para los SKUs.
        //    El user puede editar línea por línea antes de aprobar. Si no hay costo
        //    registrado, se queda en 0 y el user lo completa manual.
        $skus_for_cost = array_map(function($it){ return $it['idProduct']; }, $items);
        $costs_map = [];
        if (!empty($skus_for_cost)) {
            $cost_rows = $this->db->select('productId, providerPrice')
                ->from('product_providers')
                ->where('providerId', (string)$rule->providerId)
                ->where_in('productId', $skus_for_cost)
                ->where('is_active', 1)
                ->get()->result();
            foreach ($cost_rows as $cr) $costs_map[$cr->productId] = (float)$cr->providerPrice;
        }

        // 6. Crear supplier_order_details por cada item, ya con costo pre-llenado
        $rows = [];
        $order_subtotal = 0.0;
        foreach ($items as $it) {
            $qty  = (int)$it['quantity'];
            $cost = isset($costs_map[$it['idProduct']]) ? (float)$costs_map[$it['idProduct']] : 0.0;
            $line_total = round($qty * $cost, 2);
            $order_subtotal += $line_total;
            $rows[] = [
                'orderId'          => $order_id,
                'productId'        => $it['idProduct'],
                'quantityOrdered'  => $qty,
                'quantityReceived' => 0,
                'unitCost'         => $cost,
                'total'            => $line_total,
                'status'           => 'pendiente',
            ];
        }
        if (!empty($rows)) $this->supplierorders_model->saveBatch($rows);

        // 7. Recalcular subtotal/total de la orden con los costos cargados
        $this->supplierorders_model->update($order_id, [
            'subtotal' => $order_subtotal,
            'total'    => $order_subtotal,
        ]);

        // 6. Registrar la PO en purchases también (legacy/contabilidad), enlazada
        //    a la rule para auditoría. State=0 borrador. Si el flujo legacy no se
        //    usa, simplemente queda en BD sin afectar nada.
        // (Comentado: solo creamos en supplier_orders por ahora; descomentar si
        // se quiere doble-tracking. Para evitar duplicación contable lo dejo off.)

        // 8. Actualizar rule
        $this->_update_rule_next_run($rule, $now);

        $this->log_cron("Rule {$rule->id}: orden {$orderNumber} (id={$order_id}) creada con " . count($items) . " líneas en supplier_orders.");
        return $order_id;
    }

    /**
     * Calcula y persiste el próximo next_run_at según frequency_type.
     *
     * frequency_config.hour se interpreta como hora local de Bogotá (UTC-5).
     * Almacenamos `next_run_at` en UTC para que coincida con NOW() de MySQL,
     * que en este servidor está en UTC.
     */
    private function _update_rule_next_run($rule, $now)
    {
        $cfg = json_decode((string)$rule->frequency_config, true) ?: [];
        $hour_bogota = isset($cfg['hour']) ? max(0, min(23, (int)$cfg['hour'])) : 6;

        $tz_local = new DateTimeZone('America/Bogota');
        $tz_utc   = new DateTimeZone('UTC');
        $today_local = (new DateTime('now', $tz_local))->format('Y-m-d');

        switch ($rule->frequency_type) {
            case 'monthly':
                $dom = isset($cfg['day_of_month']) ? max(1, min(28, (int)$cfg['day_of_month'])) : 1;
                $next_local = (new DateTime("first day of next month $today_local", $tz_local))
                    ->setDate(
                        (int)(new DateTime("first day of next month", $tz_local))->format('Y'),
                        (int)(new DateTime("first day of next month", $tz_local))->format('n'),
                        $dom
                    )
                    ->setTime($hour_bogota, 0);
                break;

            case 'custom':
                // TODO: parser real de cron expression. Por ahora, fallback a +7 días.
                $next_local = (new DateTime("$today_local +7 days", $tz_local))->setTime($hour_bogota, 0);
                break;

            case 'weekly':
            default:
                $dow = isset($cfg['day_of_week']) ? max(1, min(7, (int)$cfg['day_of_week'])) : 1;
                // PHP date('N'): 1=lunes ... 7=domingo
                $today_dow = (int)(new DateTime('now', $tz_local))->format('N');
                $delta = ($dow - $today_dow + 7) % 7;
                if ($delta === 0) $delta = 7; // siempre la próxima ocurrencia, no hoy
                $next_local = (new DateTime("$today_local +{$delta} days", $tz_local))->setTime($hour_bogota, 0);
                break;
        }

        // Almacenamos hora local (Bogotá) — desde el cambio de TZ del 1-may
        // el server completo (Linux + MariaDB) está en America/Bogota, así
        // que NOW() también es Bogotá. Nada de gmdate() ni setTimezone(UTC).
        $next_local_str = $next_local->format('Y-m-d H:i:s');
        $now_local = date('Y-m-d H:i:s');

        $update = [
            'last_run_at' => $now_local,
            'next_run_at' => $next_local_str,
            'updated_at'  => $now_local,
        ];

        // Si la rule tenía since_date (override one-shot), nulearlo después del
        // run para que el siguiente ciclo use lookback_days normal.
        if (!empty($rule->since_date)) {
            $update['since_date'] = null;
        }

        $this->db->where('id', $rule->id)->update('purchase_rules', $update);
    }

    /**
     * Registrar mensaje en log
     */
    private function log_cron($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] CRON: {$message}";

        log_message('info', $logMessage);

        if (is_cli()) {
            echo $logMessage . "\n";
        }
    }

    /**
     * Página de índice del CRON (mostrar tareas disponibles)
     */
    public function index()
    {
        if (is_cli()) {
            echo "\n=== SisVent CRON Tasks ===\n\n";
            echo "Available commands:\n";
            echo "  php index.php cron update_tracking         - Update legacy tracking (invoices.tracking_*)\n";
            echo "  php index.php cron update_shipping_guides  - Update shipping_guides via Interrapidisimo API\n";
            echo "  php index.php cron update_tracking_sheets  - Update Google Sheets with tracking\n";
            echo "  php index.php cron tracking_summary        - Show tracking summary\n";
            echo "\n";
        } else {
            echo json_encode([
                'status' => 'ok',
                'available_tasks' => [
                    'update_tracking' => 'Update all active shipping tracking',
                    'update_tracking_sheets' => 'Send tracking updates to Google Sheets',
                    'tracking_summary' => 'Get summary of all active tracking'
                ]
            ]);
        }
    }
}
