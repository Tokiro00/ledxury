<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/DispatcherInterface.php';
require_once __DIR__ . '/../ReportAuditLogger.php';

/**
 * WhatsappDispatcher — manda el reporte adjunto via Meta Cloud API.
 *
 * IMPORTANTE: Meta Cloud API NO permite mandar mensajes outbound libres
 * fuera de la ventana de 24h post-mensaje del cliente. Para enviar a
 * cualquier hora hace falta un **utility template aprobado** por Meta
 * Business Suite.
 *
 * Hasta que el template esté aprobado, este dispatcher queda detrás del
 * feature flag `whatsapp_reports_enabled` (en application/config/secrets.php
 * o en company_settings) y devuelve BusinessRuleException si se invoca.
 *
 * Plantilla esperada:
 *   - Nombre: report_dispatch_es
 *   - Categoría: utility
 *   - Idioma: es
 *   - Header: document (PDF/XLSX adjunto)
 *   - Body: "Hola {{1}}, te enviamos tu {{2}} actualizada al {{3}}: el archivo está adjunto."
 *
 * Reusa el flujo de upload de media que `Bot_api` ya implementa para los
 * mensajes outbound del bot existente.
 */
class WhatsappDispatcher implements DispatcherInterface
{
    /** @var ReportAuditLogger */
    private $auditLogger;

    /** @var bool */
    private $enabled;

    /** @var string Nombre del template aprobado por Meta */
    private $templateName;

    /** @var string Idioma del template */
    private $templateLang;

    public function __construct(
        ReportAuditLogger $auditLogger,
        bool $enabled = false,
        string $templateName = 'report_dispatch_es',
        string $templateLang = 'es'
    ) {
        $this->auditLogger = $auditLogger;
        $this->enabled = $enabled;
        $this->templateName = $templateName;
        $this->templateLang = $templateLang;
    }

    public function channel(): string { return 'whatsapp'; }
    public function isEnabled(): bool { return $this->enabled; }

    public function send(
        ReportInterface $report,
        array $params,
        array $meta,
        string $format,
        string $binary,
        string $recipient
    ): int {
        if (!$this->enabled) {
            throw new BusinessRuleException(
                'WhatsappDispatcher deshabilitado: template de Meta no aprobado o feature flag off',
                'DISPATCHER_DISABLED'
            );
        }

        // Validar phone E.164 (con código país, ej. +573001234567)
        if (!preg_match('/^\+?\d{10,15}$/', $recipient)) {
            throw new ValidationException(
                "Phone inválido: $recipient",
                'INVALID_PHONE',
                ['recipient' => 'Debe estar en formato E.164 (ej. +573001234567)']
            );
        }

        if (!in_array($format, ['pdf', 'xlsx'], true)) {
            throw new ValidationException(
                "Formato '$format' no enviable por WhatsApp (solo pdf/xlsx)",
                'INVALID_FORMAT_FOR_CHANNEL'
            );
        }

        // Meta limita media a 100MB. Validar antes de subir.
        $sizeBytes = strlen($binary);
        if ($sizeBytes > 100 * 1024 * 1024) {
            throw new ValidationException(
                'Archivo excede 100MB (límite Meta Cloud API)',
                'FILE_TOO_LARGE'
            );
        }

        try {
            // Step 1: upload media a Meta, devuelve media_id.
            $mediaId = $this->uploadMedia($binary, $format, ($meta['filename'] ?? $report->id()) . '.' . $format);

            // Step 2: enviar template message con header.document.id = mediaId
            $clientName = $meta['client_name'] ?? 'cliente';
            $reportTitle = $meta['whatsapp_report_label'] ?? $report->title();
            $dateStr = $params['hasta'] ?? date('Y-m-d');

            $messageId = $this->sendTemplateMessage($recipient, $mediaId, [
                $clientName,
                $reportTitle,
                $dateStr,
            ]);

            return $this->auditLogger->logDispatch([
                'report_id' => $report->id(),
                'format' => $format,
                'channel' => 'whatsapp',
                'recipient' => $recipient,
                'recipient_client_id' => $meta['client_id'] ?? null,
                'params' => array_merge($params, ['wa_message_id' => $messageId]),
                'status' => 'sent',
            ]);

        } catch (Throwable $e) {
            $this->auditLogger->logDispatch([
                'report_id' => $report->id(),
                'format' => $format,
                'channel' => 'whatsapp',
                'recipient' => $recipient,
                'recipient_client_id' => $meta['client_id'] ?? null,
                'params' => $params,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
            throw new ExternalApiException(
                "WhatsApp a $recipient falló: " . $e->getMessage(),
                'WHATSAPP_SEND_FAILED'
            );
        }
    }

    /**
     * Sube el binario a Meta Cloud API y devuelve media_id.
     *
     * Implementación pendiente: reusar el código de upload de Bot_api.
     * Mientras tanto, lanza excepción para que tests sepan que el flow no
     * está activo todavía.
     *
     * @throws RuntimeException
     */
    private function uploadMedia(string $binary, string $format, string $filename): string
    {
        throw new RuntimeException(
            'WhatsappDispatcher::uploadMedia() pendiente de implementar. ' .
            'Reusar pattern de Bot_api::uploadMedia. Activar cuando template Meta esté aprobado.'
        );
    }

    /**
     * Envía un template message a recipient con header.document.id = $mediaId
     * y body parameters $params.
     *
     * @param string[] $bodyParams Variables {{1}}, {{2}}, {{3}} del template.
     * @return string message_id retornado por Meta.
     * @throws RuntimeException
     */
    private function sendTemplateMessage(string $recipient, string $mediaId, array $bodyParams): string
    {
        throw new RuntimeException(
            'WhatsappDispatcher::sendTemplateMessage() pendiente de implementar. ' .
            'Activar cuando template Meta esté aprobado.'
        );
    }
}
