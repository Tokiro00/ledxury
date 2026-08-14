<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/RendererInterface.php';

/**
 * XlsxRenderer — emite el reporte como Excel con PhpSpreadsheet.
 *
 * No usa template HTML — toma `data['columns']` y `data['rows']` directos
 * y arma la hoja programáticamente. Esto es lo que esperan los contadores
 * y vendedores: una tabla limpia para auditar/filtrar/sumar.
 *
 * Header navy MAM en row 1, columnas en row 2, datos desde row 3.
 * Si data['totals'] existe, agrega una fila final con bold + bg gris.
 *
 * Para reportes con catálogo grande (>5000 rows) implementar chunks vía
 * iterador en una versión futura. Por ahora carga todo en memoria.
 */
class XlsxRenderer implements RendererInterface
{
    public function format(): string { return 'xlsx'; }
    public function mimeType(): string { return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; }
    public function fileExtension(): string { return 'xlsx'; }

    public function render(ReportInterface $report, array $params, array $data, array $meta): void
    {
        $binary = $this->renderToString($report, $params, $data, $meta);
        $filename = ($meta['filename'] ?? $report->id()) . '.xlsx';

        header('Content-Type: ' . $this->mimeType());
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        echo $binary;
    }

    public function renderToString(ReportInterface $report, array $params, array $data, array $meta): string
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // PhpSpreadsheet rechaza : / \ ? * [ ] en sheet titles. Los reemplazamos
        // por '-' antes de cortar a 31 chars.
        $sheetTitle = preg_replace('#[:\\\\/?*\\[\\]]#', '-', $report->title());
        $sheet->setTitle(mb_substr($sheetTitle, 0, 31));

        $columns = $data['columns'] ?? [];
        $rows = $data['rows'] ?? [];
        $totals = $data['totals'] ?? null;

        // Row 1: título (merged a través de todas las columnas).
        $colCount = count($columns);
        if ($colCount > 0) {
            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
            $sheet->setCellValue('A1', $report->title());
            $sheet->mergeCells("A1:{$lastCol}1");
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2B3164']], // mam-blue-dark
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(28);
        }

        // Row 2: column headers.
        $colIdx = 1;
        foreach ($columns as $col) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx) . '2';
            $sheet->setCellValue($cell, $col['label']);
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '2B3164']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F1F3F5']],
                'borders' => ['bottom' => ['borderStyle' => 'medium', 'color' => ['rgb' => '4487A0']]],
                'alignment' => ['vertical' => 'center'],
            ]);
            $colIdx++;
        }
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Datos desde row 3.
        $rowIdx = 3;
        foreach ($rows as $row) {
            $colIdx = 1;
            foreach ($columns as $col) {
                $key = $col['key'];
                $value = $row[$key] ?? '';
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx) . $rowIdx;

                // Format por tipo (con decimals opcional por columna)
                $type = $col['type'] ?? 'text';
                $decimals = $col['decimals'] ?? 0;
                if ($type === 'currency' && is_numeric($value)) {
                    $sheet->setCellValue($cell, (float) $value);
                    $fmt = $decimals > 0
                        ? '"$"#,##0.' . str_repeat('0', $decimals)
                        : '"$"#,##0';
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($fmt);
                } elseif ($type === 'number' && is_numeric($value)) {
                    $sheet->setCellValue($cell, (float) $value);
                    if ($decimals > 0) {
                        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0.' . str_repeat('0', $decimals));
                    }
                } elseif ($type === 'date' && $value) {
                    $sheet->setCellValue($cell, $value);
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                } else {
                    $sheet->setCellValueExplicit($cell, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                $colIdx++;
            }
            $rowIdx++;
        }

        // Totals como última fila si existen.
        if ($totals && is_array($totals)) {
            $colIdx = 1;
            foreach ($columns as $col) {
                $key = $col['key'];
                if (isset($totals[$key])) {
                    $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx) . $rowIdx;
                    $type = $col['type'] ?? 'text';
                    if ($type === 'currency') {
                        $sheet->setCellValue($cell, (float) $totals[$key]);
                        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                    } else {
                        $sheet->setCellValue($cell, $totals[$key]);
                    }
                    $sheet->getStyle($cell)->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F8F8F8']],
                        'borders' => ['top' => ['borderStyle' => 'medium', 'color' => ['rgb' => '2B3164']]],
                    ]);
                }
                $colIdx++;
            }
        }

        // Auto-width columnas.
        for ($i = 1; $i <= $colCount; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Freeze: row 2 (column headers) queda fija.
        $sheet->freezePane('A3');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }
}
