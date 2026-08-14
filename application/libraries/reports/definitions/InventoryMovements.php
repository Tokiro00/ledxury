<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * InventoryMovements — kardex / movimientos de inventario por articulo.
 *
 * Equivalente a "Stock Card" de SAP B1 e "Inventory Report - Stock Moves"
 * de Odoo. Consolida en un solo timeline las salidas (ventas,
 * remisiones origen) y entradas (compras, remisiones recibidas,
 * devoluciones de cliente, ajustes positivos) por producto y bodega.
 *
 * Origenes:
 *   - Ventas (invoices/invoice_details)               → salida
 *   - Compras (purchases/purchase_detail)              → entrada
 *   - Devoluciones cliente (refunds/refund_details)    → entrada
 *   - Traspasos enviados (transfers/transfer_details)  → salida (origin)
 *   - Traspasos recibidos                              → entrada (destination)
 *   - Ajustes de inventario (inventory_adjustments)    → entrada/salida segun signo
 *
 * Notas:
 *   - Para `remision_sucursal`, la entrada en destino solo cuenta cuando
 *     status='recibido' (tipica logica enviado→recibido). Para
 *     `movimiento_interno`, entra inmediatamente al destino.
 *   - Costos: se valoriza usando products.cost_cop (con fallback a cost).
 */
class InventoryMovements extends AbstractReport
{
    public function id(): string { return 'inventory_movements'; }
    public function title(): string { return 'Movimientos de Inventario'; }
    public function description(): string
    {
        return 'Kardex unificado: entradas y salidas por producto y bodega (ventas, compras, traspasos, devoluciones, ajustes). Equivalente a Stock Card en SAP B1 / Stock Moves en Odoo.';
    }

    public function requiredRoles(): array { return [2, 4, 5]; }

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
                'name' => 'product_id',
                'label' => 'Producto',
                'type' => 'product',
                'default' => '',
            ],
            [
                'name' => 'movement_type',
                'label' => 'Tipo movimiento',
                'type' => 'select',
                'options' => [
                    'all' => 'Todos',
                    'in'  => 'Solo entradas',
                    'out' => 'Solo salidas',
                ],
                'default' => 'all',
            ],
            [
                'name' => 'doc_type',
                'label' => 'Origen',
                'type' => 'select',
                'options' => [
                    'all'         => 'Todos',
                    'sale'        => 'Ventas (factura)',
                    'purchase'    => 'Compras (proveedor)',
                    'transfer'    => 'Traspasos / Remisiones',
                    'refund'      => 'Devoluciones cliente',
                    'adjustment'  => 'Ajustes de inventario',
                ],
                'default' => 'all',
            ],
            [
                'name' => 'limit',
                'label' => 'Filas detalle',
                'type' => 'select',
                'options' => ['200' => 'Hasta 200', '1000' => 'Hasta 1.000', '5000' => 'Hasta 5.000', '20000' => 'Hasta 20.000'],
                'default' => '1000',
            ],
        ];
    }

    public function availableChannels(): array { return ['email', 'schedule']; }

    public function data(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $desde   = $params['desde'];
        $hasta   = $params['hasta'];
        $storeId = (int) ($params['store_id'] ?? 0);
        $product = trim((string) ($params['product_id'] ?? ''));
        $mvType  = $params['movement_type'] ?? 'all';
        $docType = $params['doc_type'] ?? 'all';
        $limit   = max(1, min(50000, (int) ($params['limit'] ?? 1000)));

        $CI =& get_instance();

        $unionParts = [];
        $unionArgs  = [];

        // 1) VENTAS — salida desde invoice.storeId
        if (($docType === 'all' || $docType === 'sale') && $mvType !== 'in') {
            $w = []; $a = [];
            $w[] = 'i.deleted = 0';
            $w[] = 'DATE(i.date) BETWEEN ? AND ?';
            $a[] = $desde; $a[] = $hasta;
            if ($storeId) { $w[] = 'i.storeId = ?'; $a[] = $storeId; }
            if ($product !== '') { $w[] = 'd.productId LIKE ?'; $a[] = '%' . $product . '%'; }
            $whereStr = ' AND ' . implode(' AND ', $w);
            $unionParts[] = "
                SELECT
                    i.date AS movement_date,
                    'sale' AS doc_type,
                    CONCAT('FV-', LPAD(i.idInvoice, 6, '0')) AS doc_ref,
                    i.idInvoice AS doc_id,
                    d.productId AS product_code,
                    p.description AS product_name,
                    i.storeId AS store_id,
                    s.name AS store_name,
                    i.storeId AS store_id_dst,
                    NULL AS store_name_dst,
                    0 AS qty_in,
                    d.quantity AS qty_out,
                    -d.quantity AS qty_signed,
                    COALESCE(NULLIF(p.cost_cop, 0), p.cost, 0) AS unit_cost,
                    c.name AS counterparty_name
                FROM invoice_details d
                JOIN invoices i ON i.idInvoice = d.invoiceId
                JOIN products p ON p.idProduct = d.productId
                LEFT JOIN stores s ON s.idStore = i.storeId
                LEFT JOIN clients c ON c.idClient = i.clientId
                WHERE 1=1 $whereStr
            ";
            foreach ($a as $arg) $unionArgs[] = $arg;
        }

        // 2) COMPRAS — entrada a purchase.storeId
        if ($docType === 'all' || $docType === 'purchase') {
            if ($mvType !== 'out') {
                $w = []; $a = [];
                $w[] = 'pu.deleted = 0';
                $w[] = 'DATE(pu.date) BETWEEN ? AND ?';
                $a[] = $desde; $a[] = $hasta;
                if ($storeId) { $w[] = 'pu.storeId = ?'; $a[] = $storeId; }
                if ($product !== '') { $w[] = 'pd.productId LIKE ?'; $a[] = '%' . $product . '%'; }
                $whereStr = ' AND ' . implode(' AND ', $w);
                $unionParts[] = "
                    SELECT
                        pu.date AS movement_date,
                        'purchase' AS doc_type,
                        CONCAT('FC-', LPAD(pu.idPurchase, 6, '0')) AS doc_ref,
                        pu.idPurchase AS doc_id,
                        pd.productId AS product_code,
                        p.description AS product_name,
                        pu.storeId AS store_id,
                        s.name AS store_name,
                        pu.storeId AS store_id_dst,
                        NULL AS store_name_dst,
                        pd.quantity AS qty_in,
                        0 AS qty_out,
                        pd.quantity AS qty_signed,
                        COALESCE(pd.unit, NULLIF(p.cost_cop, 0), p.cost, 0) AS unit_cost,
                        prov.name AS counterparty_name
                    FROM purchase_detail pd
                    JOIN purchases pu ON pu.idPurchase = pd.purchaseId
                    JOIN products p ON p.idProduct = pd.productId
                    LEFT JOIN stores s ON s.idStore = pu.storeId
                    LEFT JOIN providers prov ON prov.idProvider = pu.providerId
                    WHERE 1=1 $whereStr
                ";
                foreach ($a as $arg) $unionArgs[] = $arg;
            }

            // 2b) FACTURAS DE PROVEEDOR (supplier_invoices) — entrada al
            // destination_store cuando la factura fue marcada received=1.
            // El "FC-XXXXXX" del legacy convive con "FCP-N" del modulo nuevo.
            // v1.31.76: agregado tras detectar que el modulo legacy purchases
            // estaba vacio en prod y todas las compras estan acá.
            if ($mvType !== 'out') {
                $w = []; $a = [];
                $w[] = 'si.deleted = 0';
                $w[] = 'si.received = 1';
                $w[] = 'DATE(COALESCE(si.received_at, si.invoiceDate)) BETWEEN ? AND ?';
                $a[] = $desde; $a[] = $hasta;
                if ($storeId) {
                    // Match contra destination_store si esta seteado, sino contra storeId del header
                    $w[] = 'COALESCE(si.destination_store, si.storeId) = ?';
                    $a[] = $storeId;
                }
                if ($product !== '') { $w[] = 'sid.productId LIKE ?'; $a[] = '%' . $product . '%'; }
                $whereStr = ' AND ' . implode(' AND ', $w);
                $unionParts[] = "
                    SELECT
                        COALESCE(si.received_at, si.invoiceDate) AS movement_date,
                        'supplier_invoice' AS doc_type,
                        CONCAT('FCP-', si.invoiceNumber) AS doc_ref,
                        si.idSupplierInvoice AS doc_id,
                        sid.productId AS product_code,
                        COALESCE(sid.description, p.description) AS product_name,
                        COALESCE(si.destination_store, si.storeId) AS store_id,
                        s.name AS store_name,
                        COALESCE(si.destination_store, si.storeId) AS store_id_dst,
                        NULL AS store_name_dst,
                        sid.quantity AS qty_in,
                        0 AS qty_out,
                        sid.quantity AS qty_signed,
                        COALESCE(sid.unitCost, NULLIF(p.cost_cop, 0), p.cost, 0) AS unit_cost,
                        prov.name AS counterparty_name
                    FROM supplier_invoice_details sid
                    JOIN supplier_invoices si ON si.idSupplierInvoice = sid.supplierInvoiceId
                    LEFT JOIN products p ON p.idProduct = sid.productId
                    LEFT JOIN stores s ON s.idStore = COALESCE(si.destination_store, si.storeId)
                    LEFT JOIN providers prov ON prov.idProvider = si.providerId
                    WHERE 1=1 $whereStr
                ";
                foreach ($a as $arg) $unionArgs[] = $arg;
            }
        }

        // 3) TRASPASOS — salida desde origin (toda)
        if ($docType === 'all' || $docType === 'transfer') {
            if ($mvType !== 'in') {
                $w = []; $a = [];
                $w[] = 't.deleted = 0';
                $w[] = "t.status <> 'cancelado'";
                $w[] = 'DATE(t.date) BETWEEN ? AND ?';
                $a[] = $desde; $a[] = $hasta;
                if ($storeId) { $w[] = 't.originId = ?'; $a[] = $storeId; }
                if ($product !== '') { $w[] = 'td.idProduct LIKE ?'; $a[] = '%' . $product . '%'; }
                $whereStr = ' AND ' . implode(' AND ', $w);
                $unionParts[] = "
                    SELECT
                        t.date AS movement_date,
                        CASE WHEN t.transfer_type='remision_sucursal' THEN 'transfer_remision_out' ELSE 'transfer_out' END AS doc_type,
                        CONCAT('TR-', LPAD(t.idTransfer, 6, '0')) AS doc_ref,
                        t.idTransfer AS doc_id,
                        td.idProduct AS product_code,
                        p.description AS product_name,
                        t.originId AS store_id,
                        so.name AS store_name,
                        t.destinationId AS store_id_dst,
                        sd.name AS store_name_dst,
                        0 AS qty_in,
                        td.quantity AS qty_out,
                        -td.quantity AS qty_signed,
                        COALESCE(td.unit_cost, NULLIF(p.cost_cop, 0), p.cost, 0) AS unit_cost,
                        sd.name AS counterparty_name
                    FROM transfer_details td
                    JOIN transfers t ON t.idTransfer = td.idTransfer
                    JOIN products p ON p.idProduct = td.idProduct
                    LEFT JOIN stores so ON so.idStore = t.originId
                    LEFT JOIN stores sd ON sd.idStore = t.destinationId
                    WHERE 1=1 $whereStr
                ";
                foreach ($a as $arg) $unionArgs[] = $arg;
            }

            // Entrada al destino: movimiento_interno (siempre) o remision_sucursal (solo si recibido)
            if ($mvType !== 'out') {
                $w = []; $a = [];
                $w[] = 't.deleted = 0';
                $w[] = "(t.transfer_type='movimiento_interno' OR (t.transfer_type='remision_sucursal' AND t.status='recibido'))";
                // Para movimiento_interno usamos t.date, para remision usamos received_at
                $w[] = "DATE(COALESCE(t.received_at, t.date)) BETWEEN ? AND ?";
                $a[] = $desde; $a[] = $hasta;
                if ($storeId) { $w[] = 't.destinationId = ?'; $a[] = $storeId; }
                if ($product !== '') { $w[] = 'td.idProduct LIKE ?'; $a[] = '%' . $product . '%'; }
                $whereStr = ' AND ' . implode(' AND ', $w);
                $unionParts[] = "
                    SELECT
                        COALESCE(t.received_at, t.date) AS movement_date,
                        CASE WHEN t.transfer_type='remision_sucursal' THEN 'transfer_remision_in' ELSE 'transfer_in' END AS doc_type,
                        CONCAT('TR-', LPAD(t.idTransfer, 6, '0')) AS doc_ref,
                        t.idTransfer AS doc_id,
                        td.idProduct AS product_code,
                        p.description AS product_name,
                        t.destinationId AS store_id,
                        sd.name AS store_name,
                        t.originId AS store_id_dst,
                        so.name AS store_name_dst,
                        td.quantity AS qty_in,
                        0 AS qty_out,
                        td.quantity AS qty_signed,
                        COALESCE(td.transfer_price, td.unit_cost, NULLIF(p.cost_cop, 0), p.cost, 0) AS unit_cost,
                        so.name AS counterparty_name
                    FROM transfer_details td
                    JOIN transfers t ON t.idTransfer = td.idTransfer
                    JOIN products p ON p.idProduct = td.idProduct
                    LEFT JOIN stores so ON so.idStore = t.originId
                    LEFT JOIN stores sd ON sd.idStore = t.destinationId
                    WHERE 1=1 $whereStr
                ";
                foreach ($a as $arg) $unionArgs[] = $arg;
            }
        }

        // 4) DEVOLUCIONES — entrada al store de la factura original
        if ($docType === 'all' || $docType === 'refund') {
            if ($mvType !== 'out') {
                $w = []; $a = [];
                $w[] = 'r.deleted = 0';
                $w[] = 'DATE(r.date) BETWEEN ? AND ?';
                $a[] = $desde; $a[] = $hasta;
                if ($storeId) { $w[] = 'i.storeId = ?'; $a[] = $storeId; }
                if ($product !== '') { $w[] = 'rd.productId LIKE ?'; $a[] = '%' . $product . '%'; }
                $whereStr = ' AND ' . implode(' AND ', $w);
                $unionParts[] = "
                    SELECT
                        r.date AS movement_date,
                        'refund' AS doc_type,
                        CONCAT('NC-', LPAD(r.idRefund, 6, '0')) AS doc_ref,
                        r.idRefund AS doc_id,
                        rd.productId AS product_code,
                        p.description AS product_name,
                        i.storeId AS store_id,
                        s.name AS store_name,
                        i.storeId AS store_id_dst,
                        NULL AS store_name_dst,
                        rd.quantity AS qty_in,
                        0 AS qty_out,
                        rd.quantity AS qty_signed,
                        COALESCE(NULLIF(p.cost_cop, 0), p.cost, 0) AS unit_cost,
                        c.name AS counterparty_name
                    FROM refund_details rd
                    JOIN refunds r ON r.idRefund = rd.refundId
                    JOIN invoices i ON i.idInvoice = r.invoiceId
                    JOIN products p ON p.idProduct = rd.productId
                    LEFT JOIN stores s ON s.idStore = i.storeId
                    LEFT JOIN clients c ON c.idClient = i.clientId
                    WHERE 1=1 $whereStr
                ";
                foreach ($a as $arg) $unionArgs[] = $arg;
            }
        }

        // 5) AJUSTES — entrada si difference>0 (sobrante), salida si <0 (faltante)
        // Ledxury: tabla inventory_adjustments aún no existe. Guard la deja
        // opcional para que el kardex funcione sin la sección de ajustes.
        if (($docType === 'all' || $docType === 'adjustment') && $CI->db->table_exists('inventory_adjustments')) {
            $w = []; $a = [];
            $w[] = 'DATE(ia.applied_at) BETWEEN ? AND ?';
            $a[] = $desde; $a[] = $hasta;
            if ($mvType === 'in') $w[] = 'ia.difference > 0';
            elseif ($mvType === 'out') $w[] = 'ia.difference < 0';
            if ($storeId) { $w[] = 'ia.store_id = ?'; $a[] = $storeId; }
            if ($product !== '') { $w[] = 'ia.product_id LIKE ?'; $a[] = '%' . $product . '%'; }
            $whereStr = ' AND ' . implode(' AND ', $w);
            $unionParts[] = "
                SELECT
                    ia.applied_at AS movement_date,
                    'adjustment' AS doc_type,
                    CONCAT('AJ-', LPAD(ia.id, 6, '0')) AS doc_ref,
                    ia.id AS doc_id,
                    ia.product_id AS product_code,
                    p.description AS product_name,
                    ia.store_id AS store_id,
                    s.name AS store_name,
                    ia.store_id AS store_id_dst,
                    NULL AS store_name_dst,
                    GREATEST(ia.difference, 0) AS qty_in,
                    GREATEST(-ia.difference, 0) AS qty_out,
                    ia.difference AS qty_signed,
                    COALESCE(NULLIF(p.cost_cop, 0), p.cost, 0) AS unit_cost,
                    ia.reason AS counterparty_name
                FROM inventory_adjustments ia
                JOIN products p ON p.idProduct = ia.product_id
                LEFT JOIN stores s ON s.idStore = ia.store_id
                WHERE 1=1 $whereStr
            ";
            foreach ($a as $arg) $unionArgs[] = $arg;
        }

        if (empty($unionParts)) {
            return [
                'kpis'         => $this->emptyKpis(),
                'by_doc_type'  => [],
                'by_store'     => [],
                'top_products' => [],
                'columns'      => $this->columns(),
                'rows'         => [],
                'totals'       => $this->emptyTotals(),
            ];
        }

        // v1.31.73 — necesitamos doble paren: una alrededor de cada SELECT
        // (para que MariaDB valide la subquery del UNION) y otra alrededor
        // del UNION completo para que el alias `AS m` aplique al timeline
        // entero, no solo a la ultima parte. Sin este wrap MariaDB tira
        // error 1248 "Every derived table must have its own alias".
        $unionSql = '((' . implode(') UNION ALL (', $unionParts) . '))';

        // Detalle (limit aplicado)
        $rows = $CI->db->query("
            SELECT m.*, (m.qty_signed * m.unit_cost) AS value_signed
            FROM $unionSql AS m
            ORDER BY m.movement_date DESC, m.doc_id DESC
            LIMIT $limit
        ", $unionArgs)->result_array();

        // KPIs (sobre TODO el resultset, sin limit)
        $kpisRow = $CI->db->query("
            SELECT
                COUNT(*) AS total_movements,
                SUM(qty_in) AS total_in_qty,
                SUM(qty_out) AS total_out_qty,
                SUM(qty_signed * unit_cost) AS net_value,
                SUM(qty_in * unit_cost) AS in_value,
                SUM(qty_out * unit_cost) AS out_value,
                COUNT(DISTINCT product_code) AS distinct_products,
                COUNT(DISTINCT store_id) AS distinct_stores
            FROM $unionSql AS m
        ", $unionArgs)->row_array();

        $kpis = [
            'total_movements'   => (int)($kpisRow['total_movements'] ?? 0),
            'total_in_qty'      => (float)($kpisRow['total_in_qty'] ?? 0),
            'total_out_qty'     => (float)($kpisRow['total_out_qty'] ?? 0),
            'net_qty'           => (float)($kpisRow['total_in_qty'] ?? 0) - (float)($kpisRow['total_out_qty'] ?? 0),
            'in_value'          => (float)($kpisRow['in_value'] ?? 0),
            'out_value'         => (float)($kpisRow['out_value'] ?? 0),
            'net_value'         => (float)($kpisRow['net_value'] ?? 0),
            'distinct_products' => (int)($kpisRow['distinct_products'] ?? 0),
            'distinct_stores'   => (int)($kpisRow['distinct_stores'] ?? 0),
        ];

        // Resumen por tipo de documento
        $byDoc = $CI->db->query("
            SELECT doc_type,
                   COUNT(*) AS num_movements,
                   SUM(qty_in) AS qty_in,
                   SUM(qty_out) AS qty_out,
                   SUM(qty_in * unit_cost) AS in_value,
                   SUM(qty_out * unit_cost) AS out_value
            FROM $unionSql AS m
            GROUP BY doc_type
            ORDER BY num_movements DESC
        ", $unionArgs)->result_array();
        foreach ($byDoc as &$d) {
            $d['qty_in'] = (float)$d['qty_in'];
            $d['qty_out'] = (float)$d['qty_out'];
            $d['in_value'] = (float)$d['in_value'];
            $d['out_value'] = (float)$d['out_value'];
            $d['doc_type_label'] = $this->docTypeLabel($d['doc_type']);
        }
        unset($d);

        // Resumen por bodega
        $byStore = $CI->db->query("
            SELECT store_id, store_name,
                   COUNT(*) AS num_movements,
                   SUM(qty_in) AS qty_in,
                   SUM(qty_out) AS qty_out,
                   SUM(qty_in - qty_out) AS net_qty,
                   SUM(qty_in * unit_cost - qty_out * unit_cost) AS net_value
            FROM $unionSql AS m
            GROUP BY store_id, store_name
            ORDER BY num_movements DESC
        ", $unionArgs)->result_array();
        foreach ($byStore as &$s) {
            $s['qty_in'] = (float)$s['qty_in'];
            $s['qty_out'] = (float)$s['qty_out'];
            $s['net_qty'] = (float)$s['net_qty'];
            $s['net_value'] = (float)$s['net_value'];
        }
        unset($s);

        // Top productos por movimiento absoluto (qty_in + qty_out)
        $topProducts = $CI->db->query("
            SELECT product_code, product_name,
                   SUM(qty_in) AS qty_in,
                   SUM(qty_out) AS qty_out,
                   SUM(qty_in + qty_out) AS total_movement,
                   SUM(qty_in - qty_out) AS net_qty
            FROM $unionSql AS m
            GROUP BY product_code, product_name
            ORDER BY total_movement DESC
            LIMIT 15
        ", $unionArgs)->result_array();
        foreach ($topProducts as &$tp) {
            $tp['qty_in'] = (float)$tp['qty_in'];
            $tp['qty_out'] = (float)$tp['qty_out'];
            $tp['total_movement'] = (float)$tp['total_movement'];
            $tp['net_qty'] = (float)$tp['net_qty'];
        }
        unset($tp);

        // Acumular valores por fila + label legible de doc_type
        foreach ($rows as &$r) {
            $r['qty_in'] = (float)$r['qty_in'];
            $r['qty_out'] = (float)$r['qty_out'];
            $r['qty_signed'] = (float)$r['qty_signed'];
            $r['unit_cost'] = (float)$r['unit_cost'];
            $r['value_signed'] = (float)$r['value_signed'];
            $r['doc_type_label'] = $this->docTypeLabel($r['doc_type']);
            // Texto bodegas
            if ($r['doc_type'] === 'transfer_out' || $r['doc_type'] === 'transfer_remision_out') {
                $r['store_label'] = ($r['store_name'] ?: '?') . ' → ' . ($r['store_name_dst'] ?: '?');
            } elseif ($r['doc_type'] === 'transfer_in' || $r['doc_type'] === 'transfer_remision_in') {
                $r['store_label'] = ($r['store_name_dst'] ?: '?') . ' → ' . ($r['store_name'] ?: '?');
            } else {
                $r['store_label'] = $r['store_name'] ?: '—';
            }
        }
        unset($r);

        $totals = [
            'movement_date'   => 'TOTAL',
            'doc_type_label'  => count($rows) . ' fila' . (count($rows) === 1 ? '' : 's'),
            'doc_ref'         => '',
            'product_code'    => '',
            'product_name'    => '',
            'store_label'     => '',
            'qty_in'          => array_sum(array_column($rows, 'qty_in')),
            'qty_out'         => array_sum(array_column($rows, 'qty_out')),
            'qty_signed'      => array_sum(array_column($rows, 'qty_signed')),
            'unit_cost'       => '',
            'value_signed'    => array_sum(array_column($rows, 'value_signed')),
            'counterparty_name' => '',
        ];

        return [
            'kpis'         => $kpis,
            'by_doc_type'  => $byDoc,
            'by_store'     => $byStore,
            'top_products' => $topProducts,
            'columns'      => $this->columns(),
            'rows'         => $rows,
            'totals'       => $totals,
        ];
    }

    public function meta(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $tag = '';
        if (!empty($params['product_id'])) $tag = '_' . preg_replace('/[^A-Za-z0-9_-]/', '', $params['product_id']);
        return [
            'filename'        => 'movimientos_inventario' . $tag . '_' . $params['desde'] . '_' . $params['hasta'],
            'email_subject'   => 'Movimientos de Inventario · ' . $params['desde'] . ' al ' . $params['hasta'],
            'pdf_orientation' => 'L',
        ];
    }

    private function columns(): array
    {
        return [
            ['key' => 'movement_date',     'label' => 'Fecha',       'type' => 'datetime'],
            ['key' => 'doc_type_label',    'label' => 'Tipo',        'type' => 'text'],
            ['key' => 'doc_ref',           'label' => 'Documento',   'type' => 'text'],
            ['key' => 'product_code',      'label' => 'Código',      'type' => 'text'],
            ['key' => 'product_name',      'label' => 'Producto',    'type' => 'text'],
            ['key' => 'store_label',       'label' => 'Bodega',      'type' => 'text'],
            ['key' => 'qty_in',            'label' => 'Entrada',     'type' => 'number'],
            ['key' => 'qty_out',           'label' => 'Salida',      'type' => 'number'],
            ['key' => 'qty_signed',        'label' => 'Neto',        'type' => 'number'],
            ['key' => 'unit_cost',         'label' => 'Costo Unit.', 'type' => 'currency', 'decimals' => 2],
            ['key' => 'value_signed',      'label' => 'Valor Neto',  'type' => 'currency'],
            ['key' => 'counterparty_name', 'label' => 'Contraparte', 'type' => 'text'],
        ];
    }

    private function docTypeLabel(string $code): string
    {
        $map = [
            'sale'                  => 'Venta',
            'purchase'              => 'Compra (legacy)',
            'supplier_invoice'      => 'Compra (factura proveedor)',
            'transfer_out'          => 'Traspaso (sale)',
            'transfer_in'           => 'Traspaso (entra)',
            'transfer_remision_out' => 'Remisión (envío)',
            'transfer_remision_in'  => 'Remisión (recepción)',
            'refund'                => 'Devolución',
            'adjustment'            => 'Ajuste',
        ];
        return $map[$code] ?? $code;
    }

    private function emptyKpis(): array
    {
        return [
            'total_movements'   => 0,
            'total_in_qty'      => 0,
            'total_out_qty'     => 0,
            'net_qty'           => 0,
            'in_value'          => 0,
            'out_value'         => 0,
            'net_value'         => 0,
            'distinct_products' => 0,
            'distinct_stores'   => 0,
        ];
    }

    private function emptyTotals(): array
    {
        return [
            'movement_date' => 'TOTAL', 'doc_type_label' => '0 filas', 'doc_ref' => '',
            'product_code' => '', 'product_name' => '', 'store_label' => '',
            'qty_in' => 0, 'qty_out' => 0, 'qty_signed' => 0,
            'unit_cost' => '', 'value_signed' => 0, 'counterparty_name' => '',
        ];
    }
}
