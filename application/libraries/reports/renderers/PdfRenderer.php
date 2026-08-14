<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/RendererInterface.php';

/**
 * PdfRenderer — toma el mismo template HTML del reporte y lo convierte a
 * PDF con mPDF. Esto es fundamental: garantiza que el PDF siempre coincide
 * con lo que el usuario ve en pantalla, sin mantener templates duplicados.
 *
 * Usa el view 'sisvent/reports/_pdf_layout' (versión simplificada del HTML
 * layout, sin sidebar/navbar — solo el contenido del reporte + header MAM).
 *
 * Requiere mPDF (ya en composer.json: mpdf/mpdf).
 */
class PdfRenderer implements RendererInterface
{
    public function format(): string { return 'pdf'; }
    public function mimeType(): string { return 'application/pdf'; }
    public function fileExtension(): string { return 'pdf'; }

    public function render(ReportInterface $report, array $params, array $data, array $meta): void
    {
        $pdf = $this->renderToString($report, $params, $data, $meta);
        $filename = ($meta['filename'] ?? $report->id()) . '.pdf';

        header('Content-Type: ' . $this->mimeType());
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }

    public function renderToString(ReportInterface $report, array $params, array $data, array $meta): string
    {
        $CI =& get_instance();

        // Renderiza HTML usando el layout PDF (más limpio, sin chrome interactivo).
        $template = $meta['pdf_template'] ?? $meta['html_template'] ?? ('sisvent/reports/templates/' . $report->id());
        $viewPath = APPPATH . 'views/' . $template . '.php';
        if (!file_exists($viewPath)) {
            $template = 'sisvent/reports/templates/_generic';
        }

        $viewData = [
            'report' => $report,
            'params' => $params,
            'data'   => $data,
            'meta'   => $meta,
            'template' => $template,
            'pdf_mode' => true, // El template puede usar esto para condicional rendering.
        ];

        $html = $CI->load->view('sisvent/reports/_pdf_layout', $viewData, true);

        // mPDF setup. Encoding utf-8, márgenes razonables para reportes con tablas densas.
        $orientation = $meta['pdf_orientation'] ?? 'P'; // P = portrait, L = landscape
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'Letter',
            'orientation' => $orientation,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 18,
            'margin_bottom' => 16,
            'margin_header' => 6,
            'margin_footer' => 6,
            'shrink_tables_to_fit' => 1.4,
            'use_kwt' => true,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => sys_get_temp_dir(),
        ]);
        $mpdf->SetTitle($report->title());
        $mpdf->SetCreator('MAM ERP');
        $mpdf->SetAuthor('Multi Accesorios Medellin');

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }
}
