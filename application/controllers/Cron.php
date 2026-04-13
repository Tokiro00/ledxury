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
            echo "  php index.php cron update_tracking        - Update all active tracking\n";
            echo "  php index.php cron update_tracking_sheets - Update Google Sheets with tracking\n";
            echo "  php index.php cron tracking_summary       - Show tracking summary\n";
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
