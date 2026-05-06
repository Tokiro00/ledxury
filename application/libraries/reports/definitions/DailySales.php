<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * DailySales — Análisis de Ventas (estilo Odoo Sales Analysis / SAP B1).
 *
 * Reescrito v1.30.20: de "Ventas del Día" (single-date snapshot) a
 * "Análisis de Ventas" (rango con comparativa, gráficos y agrupación).
 *
 * Filtros:
 *   - date_range (desde / hasta) con presets — default mes en curso
 *   - vendor_id (opcional) · store_id (opcional)
 *   - group_by (day/week/month/vendor) — drives buckets del chart
 *
 * Output:
 *   - data['kpis']      → total, count, avg, prev_total, growth_pct
 *   - data['timeline']  → buckets por fecha (current + prev) para línea
 *   - data['by_vendor'] → top 10 vendedores por monto, para bar chart
 *   - data['columns']/['rows']/['totals'] → tabla detalle (genérica)
 *
 * Equivalente Odoo: "Sales > Reporting > Sales".
 * Equivalente SAP B1: "Sales Analysis Report".
 */
class DailySales extends AbstractReport
{
    public function id(): string { return 'daily_sales'; }
    public function title(): string { return 'Análisis de Ventas'; }
    public function description(): string
    {
        return 'Ventas por día, semana, mes o vendedor con comparativa de período anterior y gráficos. Equivalente Sales Analysis (Odoo) / SAP B1.';
    }

    public function requiredRoles(): array
    {
        return [2, 3, 5, 8]; // admin, vendedor, tesoreria, cartera
    }

    public function filterDefinitions(): array
    {
        return [
            ['name' => 'date_range', 'type' => 'date_range'],
            [
                'name' => 'desde',
                'label' => 'Desde',
                'type' => 'date',
                'default' => date('Y-m-01'),
            ],
            [
                'name' => 'hasta',
                'label' => 'Hasta',
                'type' => 'date',
                'default' => date('Y-m-d'),
            ],
            [
                'name' => 'vendor_id',
                'label' => 'Vendedor',
                'type' => 'vendor',
            ],
            $this->storeFilterDefinition(),
            [
                'name' => 'group_by',
                'label' => 'Agrupar por',
                'type' => 'select',
                'options' => [
                    'day'    => 'Día',
                    'week'   => 'Semana',
                    'month'  => 'Mes',
                    'vendor' => 'Vendedor',
                ],
                'default' => 'day',
            ],
        ];
    }

    public function availableChannels(): array { return ['email', 'whatsapp', 'schedule']; }

    public function data(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $desde   = $params['desde'];
        $hasta   = $params['hasta'];
        $vendor  = (string) ($params['vendor_id'] ?? '');
        $storeId = (int) ($params['store_id'] ?? 0);
        $groupBy = $params['group_by'] ?? 'day';

        // Período comparativo: mismo número de días inmediatamente anterior
        $days = (int) ((strtotime($hasta) - strtotime($desde)) / 86400) + 1;
        $prevHasta = date('Y-m-d', strtotime($desde . ' -1 day'));
        $prevDesde = date('Y-m-d', strtotime($prevHasta . ' -' . ($days - 1) . ' days'));

        $current = $this->fetchAggregate($desde, $hasta, $vendor, $storeId);
        $previous = $this->fetchAggregate($prevDesde, $prevHasta, $vendor, $storeId);

        $kpis = [
            'total'        => (float) $current['total'],
            'count'        => (int) $current['count'],
            'avg_ticket'   => $current['count'] > 0 ? $current['total'] / $current['count'] : 0,
            'prev_total'   => (float) $previous['total'],
            'prev_count'   => (int) $previous['count'],
            'growth_pct'   => $previous['total'] > 0
                ? (($current['total'] - $previous['total']) / $previous['total']) * 100
                : null,
            'prev_label'   => date('d/m', strtotime($prevDesde)) . ' – ' . date('d/m', strtotime($prevHasta)),
        ];

        $timeline = $this->fetchTimeline($desde, $hasta, $prevDesde, $prevHasta, $vendor, $storeId, $groupBy);
        $byVendor = $this->fetchByVendor($desde, $hasta, $storeId);

        // Tabla detalle según group_by
        if ($groupBy === 'vendor') {
            $rows = $this->fetchRowsByVendor($desde, $hasta, $vendor, $storeId);
            $columns = [
                ['key' => 'vendor_name',  'label' => 'Vendedor',        'type' => 'text'],
                ['key' => 'num_invoices', 'label' => '# Facturas',      'type' => 'number'],
                ['key' => 'total',        'label' => 'Total',           'type' => 'currency'],
                ['key' => 'avg_ticket',   'label' => 'Ticket promedio', 'type' => 'currency'],
                ['key' => 'pct',          'label' => '% del total',     'type' => 'number', 'decimals' => 1],
            ];
        } else {
            $rows = $this->bucketRowsFromTimeline($timeline);
            $columns = [
                ['key' => 'label',        'label' => $this->bucketLabel($groupBy), 'type' => 'text'],
                ['key' => 'num_invoices', 'label' => '# Facturas',                 'type' => 'number'],
                ['key' => 'total',        'label' => 'Total',                      'type' => 'currency'],
                ['key' => 'avg_ticket',   'label' => 'Ticket promedio',            'type' => 'currency'],
                ['key' => 'pct',          'label' => '% del total',                'type' => 'number', 'decimals' => 1],
            ];
        }

        // Calcular % del total en cada row
        $totalSum = array_sum(array_column($rows, 'total')) ?: 1;
        foreach ($rows as &$r) {
            $r['pct'] = ($r['total'] / $totalSum) * 100;
        }
        unset($r);

        $totals = [
            'vendor_name' => 'TOTAL',
            'label'       => 'TOTAL',
            'num_invoices'=> array_sum(array_column($rows, 'num_invoices')),
            'total'       => array_sum(array_column($rows, 'total')),
            'avg_ticket'  => $kpis['avg_ticket'],
            'pct'         => 100.0,
        ];

        return [
            'kpis'      => $kpis,
            'timeline'  => $timeline,
            'by_vendor' => $byVendor,
            'group_by'  => $groupBy,
            'columns'   => $columns,
            'rows'      => $rows,
            'totals'    => $totals,
        ];
    }

    public function meta(array $params): array
    {
        $desde = $params['desde'] ?? date('Y-m-01');
        $hasta = $params['hasta'] ?? date('Y-m-d');
        return [
            'filename'             => 'analisis_ventas_' . $desde . '_' . $hasta,
            'email_subject'        => 'Análisis de ventas — ' . date('d/m/Y', strtotime($desde)) . ' a ' . date('d/m/Y', strtotime($hasta)),
            'whatsapp_report_label'=> 'análisis de ventas',
            'pdf_orientation'      => 'L',
        ];
    }

    // ---------- Queries ---------- //

    /**
     * Suma total + count en un rango. Filtra por vendor/store opcionalmente.
     * @return array{total: float, count: int}
     */
    private function fetchAggregate(string $desde, string $hasta, string $vendor, int $storeId): array
    {
        $CI =& get_instance();
        $where = "i.deleted = 0 AND DATE(i.date) BETWEEN ? AND ?";
        $args = [$desde, $hasta];
        if ($vendor) { $where .= " AND i.vendorId = ?"; $args[] = $vendor; }
        if ($storeId) { $where .= " AND i.storeId = ?"; $args[] = $storeId; }

        $row = $CI->db->query("
            SELECT COALESCE(SUM(i.total), 0) AS total, COUNT(*) AS count
            FROM invoices i
            WHERE $where
        ", $args)->row_array();

        return ['total' => (float) ($row['total'] ?? 0), 'count' => (int) ($row['count'] ?? 0)];
    }

    /**
     * Buckets temporales (current + previous) según group_by. Rellena ceros
     * en buckets vacíos para que el chart no tenga gaps.
     *
     * group_by=vendor: la línea temporal usa día como bucket (siempre).
     */
    private function fetchTimeline(string $desde, string $hasta, string $prevDesde, string $prevHasta, string $vendor, int $storeId, string $groupBy): array
    {
        $bucketExpr = $this->bucketExpr($groupBy);

        $CI =& get_instance();
        $whereCurr = "i.deleted = 0 AND DATE(i.date) BETWEEN ? AND ?";
        $argsCurr = [$desde, $hasta];
        $wherePrev = "i.deleted = 0 AND DATE(i.date) BETWEEN ? AND ?";
        $argsPrev = [$prevDesde, $prevHasta];
        if ($vendor) {
            $whereCurr .= " AND i.vendorId = ?"; $argsCurr[] = $vendor;
            $wherePrev .= " AND i.vendorId = ?"; $argsPrev[] = $vendor;
        }
        if ($storeId) {
            $whereCurr .= " AND i.storeId = ?"; $argsCurr[] = $storeId;
            $wherePrev .= " AND i.storeId = ?"; $argsPrev[] = $storeId;
        }

        $currRows = $CI->db->query("
            SELECT $bucketExpr AS bucket, SUM(i.total) AS total, COUNT(*) AS num_invoices
            FROM invoices i
            WHERE $whereCurr
            GROUP BY bucket
            ORDER BY bucket
        ", $argsCurr)->result_array();

        $prevRows = $CI->db->query("
            SELECT $bucketExpr AS bucket, SUM(i.total) AS total
            FROM invoices i
            WHERE $wherePrev
            GROUP BY bucket
            ORDER BY bucket
        ", $argsPrev)->result_array();

        // Indexar por offset desde el inicio del periodo para alinear curr/prev visualmente
        $buckets = $this->generateBuckets($desde, $hasta, $groupBy);
        $currIdx = [];
        foreach ($currRows as $r) { $currIdx[$r['bucket']] = $r; }

        // prev: alineamos por posición (offset del inicio), no por bucket literal
        $prevIdx = array_values($prevRows);

        $timeline = [];
        $i = 0;
        foreach ($buckets as $b) {
            $cr = $currIdx[$b['key']] ?? ['total' => 0, 'num_invoices' => 0];
            $pr = $prevIdx[$i] ?? ['total' => 0];
            $timeline[] = [
                'key'          => $b['key'],
                'label'        => $b['label'],
                'total'        => (float) $cr['total'],
                'num_invoices' => (int) ($cr['num_invoices'] ?? 0),
                'prev_total'   => (float) $pr['total'],
            ];
            $i++;
        }
        return $timeline;
    }

    /**
     * Top 10 vendedores por monto para el bar chart horizontal.
     */
    private function fetchByVendor(string $desde, string $hasta, int $storeId): array
    {
        $CI =& get_instance();
        $where = "i.deleted = 0 AND DATE(i.date) BETWEEN ? AND ?";
        $args = [$desde, $hasta];
        if ($storeId) { $where .= " AND i.storeId = ?"; $args[] = $storeId; }

        $rows = $CI->db->query("
            SELECT
                u.idUser AS vendor_id,
                COALESCE(u.name, i.vendorId) AS vendor_name,
                SUM(i.total) AS total,
                COUNT(*) AS num_invoices
            FROM invoices i
            LEFT JOIN users u ON u.idUser = i.vendorId
            WHERE $where
            GROUP BY u.idUser, vendor_name
            ORDER BY total DESC
            LIMIT 10
        ", $args)->result_array();

        foreach ($rows as &$r) {
            $r['total'] = (float) $r['total'];
            $r['num_invoices'] = (int) $r['num_invoices'];
        }
        return $rows;
    }

    /**
     * Filas detalle cuando group_by=vendor (no derivable de timeline).
     */
    private function fetchRowsByVendor(string $desde, string $hasta, string $vendor, int $storeId): array
    {
        $CI =& get_instance();
        $where = "i.deleted = 0 AND DATE(i.date) BETWEEN ? AND ?";
        $args = [$desde, $hasta];
        if ($vendor) { $where .= " AND i.vendorId = ?"; $args[] = $vendor; }
        if ($storeId) { $where .= " AND i.storeId = ?"; $args[] = $storeId; }

        $rows = $CI->db->query("
            SELECT
                COALESCE(u.name, i.vendorId) AS vendor_name,
                SUM(i.total) AS total,
                COUNT(*) AS num_invoices,
                AVG(i.total) AS avg_ticket
            FROM invoices i
            LEFT JOIN users u ON u.idUser = i.vendorId
            WHERE $where
            GROUP BY u.idUser, vendor_name
            ORDER BY total DESC
        ", $args)->result_array();

        foreach ($rows as &$r) {
            $r['total'] = (float) $r['total'];
            $r['num_invoices'] = (int) $r['num_invoices'];
            $r['avg_ticket'] = (float) $r['avg_ticket'];
        }
        return $rows;
    }

    /**
     * Convierte timeline en filas tabulares cuando group_by != vendor.
     */
    private function bucketRowsFromTimeline(array $timeline): array
    {
        $rows = [];
        foreach ($timeline as $b) {
            $rows[] = [
                'label'        => $b['label'],
                'num_invoices' => $b['num_invoices'],
                'total'        => $b['total'],
                'avg_ticket'   => $b['num_invoices'] > 0 ? $b['total'] / $b['num_invoices'] : 0,
            ];
        }
        return $rows;
    }

    // ---------- Bucket helpers ---------- //

    /**
     * SQL expression que convierte i.date al bucket según group_by.
     */
    private function bucketExpr(string $groupBy): string
    {
        switch ($groupBy) {
            case 'week':   return "DATE_FORMAT(DATE_SUB(i.date, INTERVAL WEEKDAY(i.date) DAY), '%Y-%m-%d')";
            case 'month':  return "DATE_FORMAT(i.date, '%Y-%m-01')";
            case 'vendor': return "DATE(i.date)"; // vendor mode usa día en timeline
            case 'day':
            default:       return "DATE(i.date)";
        }
    }

    /**
     * Pre-genera todos los buckets del rango para garantizar continuidad
     * en el chart aún si no hubo ventas ese día/semana/mes.
     *
     * @return array<int, array{key: string, label: string}>
     */
    private function generateBuckets(string $desde, string $hasta, string $groupBy): array
    {
        $buckets = [];
        $cursor = strtotime($desde);
        $end = strtotime($hasta);

        if ($groupBy === 'month') {
            $cursor = strtotime(date('Y-m-01', $cursor));
            while ($cursor <= $end) {
                $buckets[] = [
                    'key'   => date('Y-m-01', $cursor),
                    'label' => date('M Y', $cursor),
                ];
                $cursor = strtotime('+1 month', $cursor);
            }
        } elseif ($groupBy === 'week') {
            // Lunes de la semana del desde
            $cursor = strtotime('monday this week', $cursor);
            while ($cursor <= $end) {
                $buckets[] = [
                    'key'   => date('Y-m-d', $cursor),
                    'label' => 'Sem ' . date('W', $cursor),
                ];
                $cursor = strtotime('+1 week', $cursor);
            }
        } else { // day or vendor (vendor uses day)
            while ($cursor <= $end) {
                $buckets[] = [
                    'key'   => date('Y-m-d', $cursor),
                    'label' => date('d/m', $cursor),
                ];
                $cursor = strtotime('+1 day', $cursor);
            }
        }
        return $buckets;
    }

    private function bucketLabel(string $groupBy): string
    {
        switch ($groupBy) {
            case 'week':  return 'Semana';
            case 'month': return 'Mes';
            case 'day':
            default:      return 'Día';
        }
    }
}
