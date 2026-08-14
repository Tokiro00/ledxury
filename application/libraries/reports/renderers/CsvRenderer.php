<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/RendererInterface.php';

/**
 * CsvRenderer — emite el reporte como CSV.
 *
 * Escribe data['columns'] como header row + data['rows']. UTF-8 con BOM
 * para que Excel en Windows abra los acentos correctamente sin pedir
 * conversión.
 *
 * No incluye totales (CSV no tiene formato; quien lo abra puede agregar
 * la fórmula). Si se necesita totales en CSV, exportar como XLSX.
 */
class CsvRenderer implements RendererInterface
{
    public function format(): string { return 'csv'; }
    public function mimeType(): string { return 'text/csv; charset=utf-8'; }
    public function fileExtension(): string { return 'csv'; }

    public function render(ReportInterface $report, array $params, array $data, array $meta): void
    {
        $filename = ($meta['filename'] ?? $report->id()) . '.csv';

        header('Content-Type: ' . $this->mimeType());
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // BOM utf-8 para Excel Windows
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        $this->writeCsv($out, $data);
        fclose($out);
    }

    public function renderToString(ReportInterface $report, array $params, array $data, array $meta): string
    {
        $fp = fopen('php://temp', 'w+');
        fwrite($fp, "\xEF\xBB\xBF");
        $this->writeCsv($fp, $data);
        rewind($fp);
        $contents = stream_get_contents($fp);
        fclose($fp);
        return $contents;
    }

    /**
     * @param resource $out
     * @param array<string, mixed> $data
     */
    private function writeCsv($out, array $data): void
    {
        $columns = $data['columns'] ?? [];
        $rows = $data['rows'] ?? [];

        // Header
        $header = array_map(fn($c) => $c['label'] ?? $c['key'], $columns);
        fputcsv($out, $header, ';');

        // Rows
        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $col) {
                $line[] = (string) ($row[$col['key']] ?? '');
            }
            fputcsv($out, $line, ';');
        }
    }
}
