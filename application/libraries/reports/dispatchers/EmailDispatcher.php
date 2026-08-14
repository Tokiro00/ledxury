<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/DispatcherInterface.php';
require_once __DIR__ . '/../ReportAuditLogger.php';

/**
 * EmailDispatcher — manda el reporte adjunto por email usando la library
 * `email` de CodeIgniter (autoloaded en application/config/autoload.php).
 *
 * Subject default: meta['email_subject'] ?? "{reporte} — {fecha}".
 * Body default: meta['email_body'] ?? plantilla genérica.
 *
 * Si el dominio MAM no tiene SPF/DKIM configurado, los correos pueden caer
 * en spam. Esto NO bloquea el dispatcher (sigue funcionando), pero conviene
 * resolverlo antes del rollout masivo (pre-requisito 3 del INDEX v1.30.x).
 */
class EmailDispatcher implements DispatcherInterface
{
    /** @var ReportAuditLogger */
    private $auditLogger;

    /** @var bool */
    private $enabled;

    public function __construct(ReportAuditLogger $auditLogger, bool $enabled = true)
    {
        $this->auditLogger = $auditLogger;
        $this->enabled = $enabled;
    }

    public function channel(): string { return 'email'; }
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
                'EmailDispatcher deshabilitado por feature flag',
                'DISPATCHER_DISABLED'
            );
        }

        // Validación básica de email
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(
                "Email inválido: $recipient",
                'INVALID_EMAIL',
                ['recipient' => 'Formato de email no válido']
            );
        }

        if (!in_array($format, ['pdf', 'xlsx', 'csv'], true)) {
            throw new ValidationException(
                "Formato '$format' no enviable por email (solo pdf/xlsx/csv)",
                'INVALID_FORMAT_FOR_CHANNEL'
            );
        }

        $CI =& get_instance();
        $CI->load->library('email');

        $subject = $meta['email_subject'] ?? sprintf('%s — %s', $report->title(), date('d/m/Y'));
        $body = $meta['email_body'] ?? $this->defaultBody($report, $meta);
        $filename = ($meta['filename'] ?? $report->id()) . '.' . $format;

        // Adjuntar binario sin escribir a disco. CI3 email->attach() requiere path,
        // así que escribimos a tmp y limpiamos al final.
        $tmpPath = tempnam(sys_get_temp_dir(), 'rep_') . '.' . $format;
        file_put_contents($tmpPath, $binary);

        try {
            $CI->email->clear();
            $CI->email->from($CI->config->item('smtp_user', 'email') ?: 'noreply@accesoriosmam.com', 'MAM ERP');
            $CI->email->to($recipient);
            $CI->email->subject($subject);
            $CI->email->message($body);
            $CI->email->attach($tmpPath, 'attachment', $filename);

            $sent = $CI->email->send(false);
            $error = $sent ? null : $CI->email->print_debugger(['headers']);

            $logId = $this->auditLogger->logDispatch([
                'report_id' => $report->id(),
                'format' => $format,
                'channel' => 'email',
                'recipient' => $recipient,
                'recipient_client_id' => $meta['client_id'] ?? null,
                'params' => $params,
                'status' => $sent ? 'sent' : 'failed',
                'error' => $error,
            ]);

            if (!$sent) {
                throw new ExternalApiException(
                    "Email a $recipient falló: " . ($error ?: 'unknown'),
                    'EMAIL_SEND_FAILED'
                );
            }

            return $logId;

        } finally {
            if (file_exists($tmpPath)) @unlink($tmpPath);
        }
    }

    private function defaultBody(ReportInterface $report, array $meta): string
    {
        $clientName = $meta['client_name'] ?? '';
        $hello = $clientName ? "Hola $clientName," : 'Hola,';

        return <<<TEXT
$hello

Te enviamos adjunto el reporte de {$report->title()} que solicitaste.

Si tenés alguna duda, respondé este correo.

— MAM ERP
Multi Accesorios Medellín
TEXT;
    }
}
