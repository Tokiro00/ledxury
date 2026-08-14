<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/RendererInterface.php';
require_once __DIR__ . '/HtmlRenderer.php';
require_once __DIR__ . '/PdfRenderer.php';
require_once __DIR__ . '/XlsxRenderer.php';
require_once __DIR__ . '/CsvRenderer.php';

/**
 * RendererFactory — resuelve un format string a la instancia correspondiente.
 *
 *   $renderer = RendererFactory::for('pdf');
 *   $renderer->render($report, $params, $data, $meta);
 *
 * Si el formato no existe, lanza ValidationException.
 */
class RendererFactory
{
    public static function for(string $format): RendererInterface
    {
        switch ($format) {
            case 'html': return new HtmlRenderer();
            case 'pdf':  return new PdfRenderer();
            case 'xlsx': return new XlsxRenderer();
            case 'csv':  return new CsvRenderer();
            default:
                throw new ValidationException(
                    "Formato '$format' no soportado",
                    'INVALID_FORMAT',
                    ['format' => 'Debe ser uno de: html, pdf, xlsx, csv']
                );
        }
    }

    /**
     * Lista todos los formatos disponibles. Para validación + UI.
     *
     * @return string[]
     */
    public static function available(): array
    {
        return ['html', 'pdf', 'xlsx', 'csv'];
    }
}
