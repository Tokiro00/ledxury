<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * ProductsABC — Análisis Pareto 80/15/5 de productos por revenue o cantidad.
 *
 * Inspirado en Odoo "Product Analysis" + SAP B1 "Inventory ABC".
 * Detecta los SKUs A (80% del revenue, "moneda corriente") vs los C
 * (cola larga, candidatos a discontinuar o reordenar menos).
 *
 * Filtro `metric`: revenue (default) o quantity. Cambia el ranking — un
 * producto puede ser A por unidades vendidas pero C por revenue (commodity
 * vs premium).
 *
 * NOTA: ABC por margen requiere cost histórico por línea. MAM tiene
 * products.cost actual pero no histórico — el cálculo seria aproximado.
 * Por eso esta version arranca solo con revenue/quantity. El margen queda
 * para una iteracion futura.
 */
class ProductsABC extends AbstractReport
{
    public function id(): string { return 'products_abc'; }
    public function title(): string { return 'Análisis ABC de Productos'; }
    public function description(): string
    {
        return 'Pareto 80/15/5 sobre productos. Métrica configurable: revenue o cantidad vendida. Detecta los SKUs A (críticos) vs C (cola larga).';
    }

    public function requiredRoles(): array { return [2, 4, 5]; }

    public function filterDefinitions(): array
    {
        return [
            ['name' => 'date_range', 'type' => 'date_range'],
            ['name' => 'desde', 'label' => 'Desde', 'type' => 'date', 'default' => date('Y-01-01')],
            ['name' => 'hasta', 'label' => 'Hasta', 'type' => 'date', 'default' => date('Y-m-d')],
            $this->storeFilterDefinition(),
            [
                'name' => 'metric',
                'label' => 'Métrica',
                'type' => 'select',
                'options' => [
                    'revenue'  => 'Revenue (monto facturado)',
                    'quantity' => 'Cantidad (unidades vendidas)',
                ],
                'default' => 'revenue',
            ],
            [
                'name' => 'limit',
                'label' => 'Top',
                'type' => 'select',
                'options' => ['100' => 'Top 100', '500' => 'Top 500', '2000' => 'Top 2000', '10000' => 'Todos'],
                'default' => '500',
            ],
        ];
    }

    public function availableChannels(): array { return ['email', 'schedule']; }

    public function data(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $desde = $params['desde'];
        $hasta = $params['hasta'];
        $storeId = (int) ($params['store_id'] ?? 0);
        $metric = $params['metric'] ?? 'revenue';
        $limit = max(1, min(100000, (int) ($params['limit'] ?? 500)));

        $CI =& get_instance();

        $where = "i.deleted = 0 AND i.state IN (0,1,2) AND DATE(i.date) BETWEEN ? AND ?";
        $args = [$desde, $hasta];
        if ($storeId) { $where .= " AND i.storeId = ?"; $args[] = $storeId; }

        // Aggregate por producto
        // - revenue = SUM(invoice_details.total)
        // - quantity = SUM(invoice_details.quantity)
        $rows = $CI->db->query("
            SELECT
                d.productId AS code,
                p.description AS product_name,
                p.section,
                p.cost AS unit_cost,
                COUNT(DISTINCT d.invoiceId) AS num_invoices,
                SUM(d.quantity) AS quantity,
                SUM(d.total) AS revenue
            FROM invoice_details d
            INNER JOIN invoices i ON i.idInvoice = d.invoiceId
            LEFT JOIN products p ON p.idProduct = d.productId
            WHERE $where AND d.productId IS NOT NULL
            GROUP BY d.productId
            ORDER BY " . ($metric === 'quantity' ? 'quantity' : 'revenue') . " DESC
            LIMIT $limit
        ", $args)->result_array();

        // Cast + revenue es lo que usamos como "valor" para Pareto
        foreach ($rows as &$r) {
            $r['revenue']      = (float) $r['revenue'];
            $r['quantity']     = (int) $r['quantity'];
            $r['num_invoices'] = (int) $r['num_invoices'];
            $r['unit_cost']    = (float) $r['unit_cost'];
        }
        unset($r);

        // Total y Pareto basado en la métrica elegida
        $valueKey = $metric === 'quantity' ? 'quantity' : 'revenue';
        $totalValue = array_sum(array_column($rows, $valueKey));
        $totalProducts = count($rows);

        $cumulative = 0;
        $countA = 0; $countB = 0; $countC = 0;
        $sumA = 0; $sumB = 0; $sumC = 0;
        $top10Sum = 0;
        foreach ($rows as $i => &$r) {
            $value = (float) $r[$valueKey];
            $cumulative += $value;
            $r['cumulative']      = $cumulative;
            $r['cumulative_pct']  = $totalValue > 0 ? ($cumulative / $totalValue) * 100 : 0;
            $r['revenue_pct']     = $totalValue > 0 ? ($value / $totalValue) * 100 : 0;
            if ($r['cumulative_pct'] <= 80)      { $r['abc'] = 'A'; $countA++; $sumA += $value; }
            elseif ($r['cumulative_pct'] <= 95)  { $r['abc'] = 'B'; $countB++; $sumB += $value; }
            else                                  { $r['abc'] = 'C'; $countC++; $sumC += $value; }
            if ($i < 10) $top10Sum += $value;
        }
        unset($r);

        $kpis = [
            'total_revenue'        => $totalValue, // generic name (works for both metrics)
            'total_products'       => $totalProducts,
            'count_a'              => $countA,
            'count_b'              => $countB,
            'count_c'              => $countC,
            'pct_a'                => $totalValue > 0 ? ($sumA / $totalValue) * 100 : 0,
            'pct_b'                => $totalValue > 0 ? ($sumB / $totalValue) * 100 : 0,
            'pct_c'                => $totalValue > 0 ? ($sumC / $totalValue) * 100 : 0,
            'top10_concentration'  => $totalValue > 0 ? ($top10Sum / $totalValue) * 100 : 0,
            'metric_label'         => $metric === 'quantity' ? 'unidades' : 'COP',
        ];

        // Drill-down: top 5 facturas por producto
        $codes = array_column($rows, 'code');
        $drilldown = $this->fetchProductDrilldown($codes, $desde, $hasta, $storeId);

        $top20 = array_slice($rows, 0, 20);

        $totals = [
            'product_name'   => 'TOTAL',
            'code'           => '',
            'section'        => '',
            'num_invoices'   => array_sum(array_column($rows, 'num_invoices')),
            'quantity'       => array_sum(array_column($rows, 'quantity')),
            'revenue'        => array_sum(array_column($rows, 'revenue')),
            'revenue_pct'    => 100.0,
            'cumulative_pct' => 100.0,
            'abc'            => '',
        ];

        return [
            'kpis'                => $kpis,
            'top20'               => $top20,
            'drilldown'           => $drilldown,
            'entity_label'        => 'producto',
            'entity_label_plural' => 'productos',
            'id_field'            => 'code',
            'name_field'          => 'product_name',
            'columns'             => $this->columns($metric),
            'rows'                => $rows,
            'totals'              => $totals,
            'period'              => ['desde' => $desde, 'hasta' => $hasta],
            'metric'              => $metric,
        ];
    }

    public function meta(array $params): array
    {
        return [
            'filename' => 'productos_abc_' . ($params['desde'] ?? date('Y-01-01')) . '_' . ($params['hasta'] ?? date('Y-m-d')),
            'email_subject' => 'Análisis ABC de productos',
            'pdf_orientation' => 'L',
            'html_template' => 'sisvent/reports/templates/_abc',
        ];
    }

    /**
     * Top 5 facturas por producto (mayor revenue). Drill compacto.
     *
     * @param string[] $codes
     */
    private function fetchProductDrilldown(array $codes, string $desde, string $hasta, int $storeId): array
    {
        if (empty($codes)) return [];
        $CI =& get_instance();

        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $where = "i.deleted = 0 AND i.state IN (0,1,2) AND DATE(i.date) BETWEEN ? AND ? AND d.productId IN ($placeholders)";
        $args = array_merge([$desde, $hasta], $codes);
        if ($storeId) { $where .= " AND i.storeId = ?"; $args[] = $storeId; }

        $rows = $CI->db->query("
            SELECT d.productId, d.invoiceId AS idInvoice_raw, i.date,
                   d.quantity, d.unit, d.total AS net,
                   c.name AS client_name, COALESCE(u.name, i.vendorId) AS vendor_name
            FROM invoice_details d
            INNER JOIN invoices i ON i.idInvoice = d.invoiceId
            LEFT JOIN clients c ON c.idClient = i.clientId
            LEFT JOIN users u ON u.idUser = i.vendorId
            WHERE $where
            ORDER BY d.productId, d.total DESC
        ", $args)->result_array();

        $byProduct = [];
        foreach ($rows as $r) {
            $pid = $r['productId'];
            if (!isset($byProduct[$pid])) $byProduct[$pid] = [];
            if (count($byProduct[$pid]) < 5) {
                $byProduct[$pid][] = [
                    'idInvoice'   => '#' . str_pad($r['idInvoice_raw'], 6, '0', STR_PAD_LEFT),
                    'date'        => $r['date'],
                    'client_name' => $r['client_name'] ?? '',
                    'vendor_name' => $r['vendor_name'] ?? '',
                    'quantity'    => (int) $r['quantity'],
                    'unit'        => (float) $r['unit'],
                    'total'       => (float) $r['net'],
                    'net'         => (float) $r['net'],
                ];
            }
        }
        return $byProduct;
    }

    private function columns(string $metric): array
    {
        $cols = [
            ['key' => 'product_name',   'label' => 'Producto',    'type' => 'text'],
            ['key' => 'code',           'label' => 'Código',      'type' => 'text'],
            ['key' => 'num_invoices',   'label' => '# Facturas',  'type' => 'number'],
            ['key' => 'quantity',       'label' => 'Cantidad',    'type' => 'number'],
            ['key' => 'revenue',        'label' => 'Revenue',     'type' => 'currency'],
            ['key' => 'revenue_pct',    'label' => '% del total', 'type' => 'number', 'decimals' => 2],
            ['key' => 'cumulative_pct', 'label' => '% Acum.',     'type' => 'number', 'decimals' => 1],
            ['key' => 'abc',            'label' => 'ABC',         'type' => 'text'],
        ];
        return $cols;
    }
}
