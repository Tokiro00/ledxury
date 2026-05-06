<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * InventoryValuation — valor del inventario con KPIs + por bodega + tabla.
 *
 * Reescrito v1.30.32: vista ejecutiva inspirada en SAP B1 "Inventory
 * Valuation Report" + Odoo "Stock Valuation".
 *
 * Estructura output:
 *   - kpis:       valor total, # productos, # bodegas, valor promedio por SKU
 *   - by_store:   bar chart con valor por bodega
 *   - top_products: top 10 productos por valor (bar horizontal)
 *   - rows:       tabla detalle (igual que antes)
 */
class InventoryValuation extends AbstractReport
{
    public function id(): string { return 'inventory_valuation'; }
    public function title(): string { return 'Valoración de Inventario'; }
    public function description(): string
    {
        return 'Stock actual valorizado por bodega + KPIs + top productos. Equivalente Inventory Valuation (SAP B1) / Stock Valuation (Odoo).';
    }

    public function requiredRoles(): array { return [2, 4, 5]; }

    public function filterDefinitions(): array
    {
        return [
            $this->storeFilterDefinition(),
            [
                'name' => 'min_stock',
                'label' => 'Stock mínimo',
                'type' => 'number',
                'default' => 1,
                'placeholder' => '1',
            ],
            [
                'name' => 'order_by',
                'label' => 'Ordenar por',
                'type' => 'select',
                'options' => [
                    'valor' => 'Valor (mayor primero)',
                    'cantidad' => 'Cantidad',
                    'code' => 'Código A-Z',
                ],
                'default' => 'valor',
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
        $storeId = (int) ($params['store_id'] ?? 0);
        $minStock = (float) ($params['min_stock'] ?? 1);
        $orderBy = $params['order_by'] ?? 'valor';
        $limit = max(1, min(100000, (int) ($params['limit'] ?? 500)));

        $CI =& get_instance();

        $where = "(p.deleted_at IS NULL) AND inv.stock >= ?";
        $args = [$minStock];
        if ($storeId) { $where .= " AND inv.idStore = ?"; $args[] = $storeId; }

        $orderMap = [
            'valor' => 'valor_total DESC',
            'cantidad' => 'inv.stock DESC',
            'code' => 'p.idProduct ASC',
        ];
        $orderClause = $orderMap[$orderBy] ?? 'valor_total DESC';

        $rows = $CI->db->query("
            SELECT
                p.idProduct AS code,
                p.description AS product_name,
                s.name AS store_name,
                inv.stock AS qty,
                p.cost AS unit_cost,
                (inv.stock * p.cost) AS valor_total
            FROM inventory inv
            INNER JOIN products p ON p.idProduct = inv.idProduct
            INNER JOIN stores s ON s.idStore = inv.idStore
            WHERE $where
            ORDER BY $orderClause
            LIMIT $limit
        ", $args)->result_array();

        // KPIs globales (sobre todo el resultset filtrado, no solo top-N)
        $kpisRow = $CI->db->query("
            SELECT
                COALESCE(SUM(inv.stock * p.cost), 0) AS total_value,
                COUNT(DISTINCT p.idProduct) AS num_products,
                COUNT(DISTINCT inv.idStore) AS num_stores,
                COALESCE(SUM(inv.stock), 0) AS total_qty
            FROM inventory inv
            INNER JOIN products p ON p.idProduct = inv.idProduct
            WHERE $where
        ", $args)->row_array();

        $totalValue = (float) ($kpisRow['total_value'] ?? 0);
        $numProducts = (int) ($kpisRow['num_products'] ?? 0);
        $numStores = (int) ($kpisRow['num_stores'] ?? 0);
        $totalQty = (float) ($kpisRow['total_qty'] ?? 0);

        $kpis = [
            'total_value'  => $totalValue,
            'num_products' => $numProducts,
            'num_stores'   => $numStores,
            'total_qty'    => $totalQty,
            'avg_per_sku'  => $numProducts > 0 ? $totalValue / $numProducts : 0,
        ];

        // Valor por bodega
        $byStore = $CI->db->query("
            SELECT s.idStore, s.name AS store_name,
                   COUNT(DISTINCT p.idProduct) AS num_products,
                   SUM(inv.stock) AS total_qty,
                   SUM(inv.stock * p.cost) AS total_value
            FROM inventory inv
            INNER JOIN products p ON p.idProduct = inv.idProduct
            INNER JOIN stores s ON s.idStore = inv.idStore
            WHERE $where
            GROUP BY s.idStore, s.name
            ORDER BY total_value DESC
        ", $args)->result_array();

        foreach ($byStore as &$bs) {
            $bs['total_value'] = (float) $bs['total_value'];
            $bs['total_qty']   = (float) $bs['total_qty'];
            $bs['num_products']= (int) $bs['num_products'];
            $bs['pct_of_total']= $totalValue > 0 ? ($bs['total_value'] / $totalValue) * 100 : 0;
        }
        unset($bs);

        // Top 10 productos por valor (independiente del orden_by elegido)
        $topProducts = $CI->db->query("
            SELECT
                p.idProduct AS code,
                p.description AS product_name,
                SUM(inv.stock) AS qty,
                p.cost AS unit_cost,
                SUM(inv.stock * p.cost) AS valor_total
            FROM inventory inv
            INNER JOIN products p ON p.idProduct = inv.idProduct
            WHERE $where
            GROUP BY p.idProduct, p.description, p.cost
            ORDER BY valor_total DESC
            LIMIT 10
        ", $args)->result_array();

        foreach ($topProducts as &$tp) {
            $tp['valor_total'] = (float) $tp['valor_total'];
            $tp['qty'] = (float) $tp['qty'];
        }
        unset($tp);

        $totals = [
            'code' => 'TOTAL',
            'product_name' => count($rows) . ' fila' . (count($rows) === 1 ? '' : 's') . ' (top mostrado)',
            'store_name' => '',
            'qty' => array_sum(array_column($rows, 'qty')),
            'unit_cost' => '',
            'valor_total' => array_sum(array_column($rows, 'valor_total')),
        ];

        return [
            'kpis'         => $kpis,
            'by_store'     => $byStore,
            'top_products' => $topProducts,
            'columns'      => $this->columns(),
            'rows'         => $rows,
            'totals'       => $totals,
        ];
    }

    public function meta(array $params): array
    {
        return [
            'filename' => 'inventario_valoracion_' . date('Y-m-d'),
            'email_subject' => 'Valoración de inventario — ' . date('d/m/Y'),
            'pdf_orientation' => 'L',
        ];
    }

    private function columns(): array
    {
        return [
            ['key' => 'code',         'label' => 'Código',     'type' => 'text'],
            ['key' => 'product_name', 'label' => 'Producto',   'type' => 'text'],
            ['key' => 'store_name',   'label' => 'Bodega',     'type' => 'text'],
            ['key' => 'qty',          'label' => 'Cantidad',   'type' => 'number'],
            ['key' => 'unit_cost',    'label' => 'Costo Unit.', 'type' => 'currency', 'decimals' => 2],
            ['key' => 'valor_total',  'label' => 'Valor',      'type' => 'currency'],
        ];
    }
}
