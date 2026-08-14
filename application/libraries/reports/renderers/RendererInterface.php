<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../ReportInterface.php';

/**
 * RendererInterface — toma una definición de reporte + sus datos y emite
 * la respuesta en su formato.
 *
 * El controller llama:
 *   $renderer = RendererFactory::for($format);
 *   $renderer->render($report, $params, $data);
 *
 * Cada renderer decide si el método imprime al output buffer (download) o
 * devuelve el binario para que el dispatcher lo adjunte (envío por email).
 *
 * Para el flow de envío:
 *   $binary = $renderer->renderToString($report, $params, $data);
 *   $dispatcher->send($binary, $meta);
 */
interface RendererInterface
{
    /**
     * Formato que este renderer produce. Subset de ['html','pdf','xlsx','csv'].
     */
    public function format(): string;

    /**
     * MIME type apropiado para Content-Type header.
     */
    public function mimeType(): string;

    /**
     * Extensión de archivo (sin punto).
     */
    public function fileExtension(): string;

    /**
     * Genera el output y lo emite directamente (echo + headers).
     * Para servir un PDF/XLSX como descarga, o un HTML interactivo.
     *
     * @param ReportInterface $report
     * @param array<string, mixed> $params Filtros validados.
     * @param array<string, mixed> $data Resultado de $report->data($params).
     * @param array<string, mixed> $meta Resultado de $report->meta($params).
     */
    public function render(ReportInterface $report, array $params, array $data, array $meta): void;

    /**
     * Genera el output como string binario. Para que un dispatcher lo
     * adjunte a un email o lo upload a Meta Cloud API.
     */
    public function renderToString(ReportInterface $report, array $params, array $data, array $meta): string;
}
