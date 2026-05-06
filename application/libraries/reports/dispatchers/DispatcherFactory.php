<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/DispatcherInterface.php';
require_once __DIR__ . '/EmailDispatcher.php';
require_once __DIR__ . '/WhatsappDispatcher.php';
require_once __DIR__ . '/../ReportAuditLogger.php';

/**
 * DispatcherFactory — resuelve un canal a su dispatcher con feature flags
 * leídos desde Settings_lib (company_settings) o secrets.php.
 *
 * Defaults:
 *   - email: enabled (siempre — el SMTP puede fallar pero el dispatcher
 *     se construye)
 *   - whatsapp: disabled (hasta aprobación del template Meta)
 *
 * Uso desde el controller:
 *   $dispatcher = DispatcherFactory::for('email', $auditLogger, $settingsLib);
 *   $dispatcher->send($report, $params, $meta, 'pdf', $binary, 'cliente@x.com');
 */
class DispatcherFactory
{
    public static function for(string $channel, ReportAuditLogger $auditLogger, $settingsLib = null): DispatcherInterface
    {
        switch ($channel) {
            case 'email':
                $enabled = $settingsLib
                    ? (bool) $settingsLib->get('email_reports_enabled', true)
                    : true;
                return new EmailDispatcher($auditLogger, $enabled);

            case 'whatsapp':
                $enabled = $settingsLib
                    ? (bool) $settingsLib->get('whatsapp_reports_enabled', false)
                    : false;
                $templateName = $settingsLib
                    ? (string) $settingsLib->get('whatsapp_report_template', 'report_dispatch_es')
                    : 'report_dispatch_es';
                return new WhatsappDispatcher($auditLogger, $enabled, $templateName);

            default:
                throw new ValidationException(
                    "Canal '$channel' no soportado",
                    'INVALID_CHANNEL',
                    ['channel' => 'Debe ser uno de: email, whatsapp']
                );
        }
    }

    /**
     * Lista canales soportados (sin filtrar por habilitado — eso lo decide
     * cada dispatcher en isEnabled()).
     *
     * @return string[]
     */
    public static function available(): array
    {
        return ['email', 'whatsapp'];
    }
}
