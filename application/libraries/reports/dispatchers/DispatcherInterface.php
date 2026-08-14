<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../ReportInterface.php';
require_once __DIR__ . '/../renderers/RendererInterface.php';

/**
 * DispatcherInterface — toma un reporte ya renderizado en algún formato y
 * lo envía por su canal (email / WhatsApp / cron schedule).
 *
 * Cada dispatcher es responsable de:
 *   - Validar entrada (recipient válido, archivo no excede limits del canal).
 *   - Hacer el envío.
 *   - Loggear via ReportAuditLogger (status sent | failed).
 *   - Lanzar ExternalApiException si el canal falla.
 */
interface DispatcherInterface
{
    /**
     * Canal: 'email' | 'whatsapp' | 'schedule'.
     */
    public function channel(): string;

    /**
     * Indica si el dispatcher está habilitado en este entorno. Permite
     * feature-flag dispatchers cuya integración externa todavía no está
     * lista (ej. WhatsApp template no aprobado).
     */
    public function isEnabled(): bool;

    /**
     * Envía el reporte adjunto al recipient.
     *
     * @param ReportInterface $report
     * @param array<string, mixed> $params Filtros aplicados (para audit log)
     * @param array<string, mixed> $meta Metadata del reporte (filename, subject, body, client_id)
     * @param string $format Formato del adjunto: 'pdf' | 'xlsx' | 'csv' (no 'html')
     * @param string $binary Contenido binario del adjunto (de RendererInterface::renderToString)
     * @param string $recipient Email o phone (con código país, +57...)
     * @return int Id del registro en report_dispatches
     *
     * @throws ValidationException Si recipient inválido.
     * @throws ExternalApiException Si el canal externo falla.
     * @throws BusinessRuleException Si el dispatcher está deshabilitado por feature flag.
     */
    public function send(
        ReportInterface $report,
        array $params,
        array $meta,
        string $format,
        string $binary,
        string $recipient
    ): int;
}
