<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * VendorCommissions — comisiones por vendedor pagadas POR RECAUDO.
 *
 * Inspirado en Odoo "Sales Commissions" + SAP B1 "Commission Calculation",
 * pero adaptado a la regla de MAM: la comisión se genera cuando la
 * factura queda **totalmente pagada** (state=2), no al facturarla.
 *
 * Filtra por `i.updated_at` (fecha del último pago que cerró la factura),
 * no por `i.date` (fecha de emisión). Una factura emitida en enero pero
 * pagada en abril aparece en el reporte de abril.
 *
 * NOTA: el cálculo aquí es la APROXIMACIÓN simple
 *   commission = (total - discount) × commission_perc
 * El módulo formal de Liquidaciones (settlement_helper.php) maneja reglas
 * adicionales: not_settle por línea, tesorería_discount, % escalonado por
 * tipo (legal=2%, list_price=5%, e-commerce=15%, IVA, margen), vales
 * restados, signo invertido si cliente=vendedor. Para liquidación formal
 * usar Cartera > Liquidaciones; este reporte es vista comparativa rápida
 * para gerencia comercial.
 *
 * Output:
 *   - kpis: total_collected, total_commission, num_vendors, top_vendor
 *   - by_vendor: lista con cobrado/comisión/pct
 *   - rows: tabla detalle con drill (top facturas pagadas)
 */
class VendorCommissions extends AbstractReport
{
    public function id(): string { return 'vendor_commissions'; }
    public function title(): string { return 'Comisiones por Recaudo'; }
    public function description(): string
    {
        return 'Comisión sobre facturas TOTALMENTE PAGADAS (state=2) en el período. La fecha es la del cierre del pago, no la de emisión. Cálculo aproximado: (total − descuento) × commission_perc. Para liquidación formal con reglas completas usar Cartera > Liquidaciones.';
    }

    public function requiredRoles(): array { return [2, 5]; } // admin, tesoreria

    public function filterDefinitions(): array
    {
        return [
            ['name' => 'date_range', 'type' => 'date_range'],
            ['name' => 'desde', 'label' => 'Desde', 'type' => 'date', 'default' => date('Y-m-01')],
            ['name' => 'hasta', 'label' => 'Hasta', 'type' => 'date', 'default' => date('Y-m-d')],
            ['name' => 'vendor_id', 'label' => 'Vendedor', 'type' => 'vendor'],
            $this->storeFilterDefinition(),
        ];
    }

    public function availableChannels(): array { return ['email', 'schedule']; }

    public function data(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $desde = $params['desde'];
        $hasta = $params['hasta'];
        $vendorId = (string) ($params['vendor_id'] ?? '');
        $storeId = (int) ($params['store_id'] ?? 0);

        $CI =& get_instance();

        // Filtros — comisión por RECAUDO: factura pagada (state=2) en el rango.
        // Fecha del recaudo = updated_at (cuando state pasa a 2 al recibir el ultimo pago).
        $where = "i.deleted = 0 AND i.state = 2 AND DATE(i.updated_at) BETWEEN ? AND ?";
        $args = [$desde, $hasta];
        if ($vendorId) { $where .= " AND i.vendorId = ?"; $args[] = $vendorId; }
        if ($storeId)  { $where .= " AND i.storeId = ?"; $args[] = $storeId; }

        // Aggregate por vendedor (solo facturas pagadas)
        $rows = $CI->db->query("
            SELECT
                i.vendorId AS vendor_id,
                COALESCE(u.name, i.vendorId) AS vendor_name,
                COALESCE(u.commission_perc, 0) AS commission_pct,
                COALESCE(u.by_commission, 0) AS by_commission,
                COUNT(*) AS num_invoices,
                SUM(i.total) AS gross_sales,
                SUM(i.discount) AS discount_total,
                SUM(i.total - i.discount) AS net_collected
            FROM invoices i
            LEFT JOIN users u ON u.idUser = i.vendorId
            WHERE $where
            GROUP BY i.vendorId
            ORDER BY net_collected DESC
        ", $args)->result_array();

        // Calcular commission_amount sobre lo RECAUDADO (factura state=2 es 100% pagada,
        // asi que net = total - discount es el monto efectivo cobrado).
        foreach ($rows as &$r) {
            $r['gross_sales']   = (float) $r['gross_sales'];
            $r['discount_total']= (float) $r['discount_total'];
            $r['net_collected'] = (float) $r['net_collected'];
            $r['commission_pct']= (float) $r['commission_pct'];
            $r['by_commission'] = (int) $r['by_commission'];
            $r['num_invoices']  = (int) $r['num_invoices'];
            // Solo aplica comisión si by_commission=1 (vendedor opt-in al pago x recaudo)
            $r['commission']    = $r['by_commission']
                ? round($r['net_collected'] * $r['commission_pct'] / 100, 2)
                : 0.0;
        }
        unset($r);

        // Drill-down: top facturas pagadas en el periodo por vendedor
        $vendorIds = array_column($rows, 'vendor_id');
        $drilldown = $this->fetchVendorDrilldown($vendorIds, $desde, $hasta, $storeId);

        // KPIs
        $totalCollected = array_sum(array_column($rows, 'net_collected'));
        $totalCommission = array_sum(array_column($rows, 'commission'));
        $numVendors = count($rows);
        $avgPct = $totalCollected > 0 ? ($totalCommission / $totalCollected) * 100 : 0;

        $top = !empty($rows) ? $rows[0] : null;

        $kpis = [
            'total_collected'   => $totalCollected,
            'total_commission'  => $totalCommission,
            'num_vendors'       => $numVendors,
            'avg_commission_pct'=> $avgPct,
            'top_vendor'        => $top,
        ];

        // Tabla detalle
        $totals = [
            'vendor_name'    => 'TOTAL',
            'num_invoices'   => array_sum(array_column($rows, 'num_invoices')),
            'gross_sales'    => array_sum(array_column($rows, 'gross_sales')),
            'discount_total' => array_sum(array_column($rows, 'discount_total')),
            'net_collected'  => $totalCollected,
            'commission_pct' => '',
            'commission'     => $totalCommission,
        ];

        return [
            'kpis'      => $kpis,
            'by_vendor' => $rows,
            'drilldown' => $drilldown,
            'columns'   => $this->columns(),
            'rows'      => $rows,
            'totals'    => $totals,
            'period'    => ['desde' => $desde, 'hasta' => $hasta],
        ];
    }

    public function meta(array $params): array
    {
        return [
            'filename' => 'comisiones_vendedores_' . ($params['desde'] ?? date('Y-m-01')) . '_' . ($params['hasta'] ?? date('Y-m-d')),
            'email_subject' => 'Comisiones vendedores — ' . date('d/m/Y'),
            'pdf_orientation' => 'L',
        ];
    }

    /**
     * Top facturas PAGADAS por vendedor en el rango (drill-down).
     * Filtra mismo criterio del aggregate: state=2 + updated_at en rango.
     * `pagada_en` muestra la fecha real del recaudo (updated_at).
     */
    private function fetchVendorDrilldown(array $vendorIds, string $desde, string $hasta, int $storeId): array
    {
        if (empty($vendorIds)) return [];
        $CI =& get_instance();

        $placeholders = implode(',', array_fill(0, count($vendorIds), '?'));
        $where = "i.deleted = 0 AND i.state = 2 AND DATE(i.updated_at) BETWEEN ? AND ? AND i.vendorId IN ($placeholders)";
        $args = array_merge([$desde, $hasta], $vendorIds);
        if ($storeId) { $where .= " AND i.storeId = ?"; $args[] = $storeId; }

        $rows = $CI->db->query("
            SELECT i.vendorId, i.idInvoice, i.date AS issued_at, i.updated_at AS paid_at,
                   i.total, i.discount,
                   (i.total - i.discount) AS net, c.name AS client_name
            FROM invoices i
            LEFT JOIN clients c ON c.idClient = i.clientId
            WHERE $where
            ORDER BY i.vendorId, net DESC
        ", $args)->result_array();

        $byVendor = [];
        foreach ($rows as $r) {
            $vid = $r['vendorId'];
            if (!isset($byVendor[$vid])) $byVendor[$vid] = [];
            if (count($byVendor[$vid]) < 10) {
                $byVendor[$vid][] = [
                    'idInvoice'   => '#' . str_pad($r['idInvoice'], 6, '0', STR_PAD_LEFT),
                    'date'        => $r['paid_at'],     // mostramos la fecha de PAGO, no de emision
                    'issued_at'   => $r['issued_at'],
                    'client_name' => $r['client_name'] ?? '',
                    'total'       => (float) $r['total'],
                    'discount'    => (float) $r['discount'],
                    'net'         => (float) $r['net'],
                ];
            }
        }
        return $byVendor;
    }

    private function columns(): array
    {
        return [
            ['key' => 'vendor_name',   'label' => 'Vendedor',         'type' => 'text'],
            ['key' => 'num_invoices',  'label' => '# Pagadas',        'type' => 'number'],
            ['key' => 'gross_sales',   'label' => 'Bruto',            'type' => 'currency'],
            ['key' => 'discount_total','label' => 'Descuento',        'type' => 'currency'],
            ['key' => 'net_collected', 'label' => 'Recaudado neto',   'type' => 'currency'],
            ['key' => 'commission_pct','label' => '% Comis.',         'type' => 'number', 'decimals' => 1],
            ['key' => 'commission',    'label' => 'Comisión',         'type' => 'currency'],
        ];
    }
}
