<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/RendererInterface.php';

/**
 * HtmlRenderer — emite el reporte como vista HTML interactiva (browser).
 *
 * Usa el view partial estándar del engine: 'sisvent/reports/_layout' que
 * agrega chrome (sidebar + navbar + page_header + action_bar) alrededor
 * del template específico del reporte.
 *
 * Cada reporte declara su template HTML en `meta()['html_template']`
 * (default: 'sisvent/reports/templates/{id}'). Si el template no existe,
 * cae a una tabla genérica que itera columns + rows.
 */
class HtmlRenderer implements RendererInterface
{
    public function format(): string { return 'html'; }
    public function mimeType(): string { return 'text/html; charset=utf-8'; }
    public function fileExtension(): string { return 'html'; }

    public function render(ReportInterface $report, array $params, array $data, array $meta): void
    {
        $CI =& get_instance();

        $viewData = [
            'report' => $report,
            'params' => $params,
            'data'   => $data,
            'meta'   => $meta,
        ];

        // Cada reporte puede tener su template específico. Si no existe, usa el genérico.
        $template = $meta['html_template'] ?? ('sisvent/reports/templates/' . $report->id());
        $viewPath = APPPATH . 'views/' . $template . '.php';
        if (!file_exists($viewPath)) {
            $template = 'sisvent/reports/templates/_generic';
        }
        $viewData['template'] = $template;

        // Layout wrappea con chrome (sidebar/navbar/page_header/action_bar).
        $CI->load->view('sisvent/reports/_layout', $viewData);
    }

    public function renderToString(ReportInterface $report, array $params, array $data, array $meta): string
    {
        ob_start();
        $this->render($report, $params, $data, $meta);
        return ob_get_clean();
    }
}
