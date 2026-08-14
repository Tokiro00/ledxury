<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * TopProducts — productos más vendidos en un período.
 *
 * Inspirado en Odoo "Sales Analysis by Product" + SAP B1.
 * Muestra para cada producto: cantidad vendida, monto bruto, # facturas,
 * # clientes únicos.
 *
 * Filtros: rango de fechas + bodega + límite (top 50/100/500).
 */
class TopProducts extends AbstractReport
{
    public function id(): string { return 'top_products'; }
    public function title(): string { return 'Productos Más Vendidos'; }
    public function description(): string
    {
        return 'Top productos por cantidad y monto en un período. Útil para reorder + análisis ABC.';
    }

    public function requiredRoles(): array { return [2, 5, 4]; }

    public function filterDefinitions(): array
    {
        return [
            ['name' => 'date_range', 'type' => 'date_range'],
            [
                'name' => 'desde',
                'label' => 'Desde',
                'type' => 'date',
                'default' => date('Y-m-01'),
                'required' => true,
            ],
            [
                'name' => 'hasta',
                'label' => 'Hasta',
                'type' => 'date',
                'default' => date('Y-m-d'),
                'required' => true,
            ],
            $this->storeFilterDefinition(),
            [
                'name' => 'limit',
                'label' => 'Top',
                'type' => 'select',
                'options' => ['25' => 'Top 25', '50' => 'Top 50', '100' => 'Top 100', '500' => 'Top 500'],
                'default' => '50',
            ],
            [
                'name' => 'order_by',
                'label' => 'Ordenar por',
                'type' => 'select',
                'options' => [
                    'monto' => 'Monto vendido',
                    'cantidad' => 'Cantidad de unidades',
                ],
                'default' => 'monto',
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
        $limit = max(1, min(1000, (int) ($params['limit'] ?? 50)));
        $orderBy = ($params['order_by'] ?? 'monto') === 'cantidad' ? 'total_qty DESC' : 'total_monto DESC';

        $CI =& get_instance();

        $where = "i.deleted = 0 AND DATE(i.date) BETWEEN ? AND ?";
        $args = [$desde, $hasta];
        if ($storeId) {
            $where .= " AND i.storeId = ?";
            $args[] = $storeId;
        }

        $rows = $CI->db->query("
            SELECT
                p.idProduct AS code,
                p.description AS product_name,
                SUM(id.quantity) AS total_qty,
                SUM(id.total) AS total_monto,
                COUNT(DISTINCT i.idInvoice) AS num_invoices,
                COUNT(DISTINCT i.clientId) AS num_clients,
                AVG(id.unit) AS avg_price
            FROM invoice_details id
            INNER JOIN invoices i ON i.idInvoice = id.invoiceId
            INNER JOIN products p ON p.idProduct = id.productId
            WHERE $where
            GROUP BY p.idProduct
            ORDER BY $orderBy
            LIMIT $limit
        ", $args)->result_array();

        $totals = [
            'code' => 'TOTAL',
            'product_name' => count($rows) . ' producto' . (count($rows) === 1 ? '' : 's'),
            'total_qty' => array_sum(array_column($rows, 'total_qty')),
            'total_monto' => array_sum(array_column($rows, 'total_monto')),
            'num_invoices' => '',
            'num_clients' => '',
            'avg_price' => '',
        ];

        return [
            'columns' => $this->columns(),
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    public function meta(array $params): array
    {
        return [
            'filename' => 'top_productos_' . ($params['hasta'] ?? date('Y-m-d')),
            'email_subject' => 'Top productos vendidos',
            'pdf_orientation' => 'L',
        ];
    }

    private function columns(): array
    {
        return [
            ['key' => 'code',         'label' => 'Código',     'type' => 'text'],
            ['key' => 'product_name', 'label' => 'Producto',   'type' => 'text'],
            ['key' => 'total_qty',    'label' => 'Cantidad',   'type' => 'number'],
            ['key' => 'total_monto',  'label' => 'Monto',      'type' => 'currency'],
            ['key' => 'num_invoices', 'label' => 'Facturas',   'type' => 'number'],
            ['key' => 'num_clients',  'label' => 'Clientes',   'type' => 'number'],
            ['key' => 'avg_price',    'label' => 'Precio Avg', 'type' => 'currency'],
        ];
    }
}
