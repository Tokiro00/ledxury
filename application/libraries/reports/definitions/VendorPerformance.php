<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * VendorPerformance — ranking de vendedores en un período.
 *
 * Inspirado en Odoo "Sales Analysis by Salesperson" + SAP B1 "Sales Analysis".
 * Muestra para cada vendedor: # facturas, ventas totales, recaudo (cobrado),
 * cartera generada (vendido pero pendiente de cobro), ticket promedio.
 *
 * Filtros: rango de fechas + bodega (opcional).
 */
class VendorPerformance extends AbstractReport
{
    public function id(): string { return 'vendor_performance'; }
    public function title(): string { return 'Rendimiento de Vendedores'; }
    public function description(): string
    {
        return 'Ranking de vendedores: facturas, ventas, recaudo y ticket promedio en un período.';
    }

    public function requiredRoles(): array { return [2, 5, 8]; }

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
        ];
    }

    public function availableChannels(): array { return ['email', 'schedule']; }

    public function data(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $desde = $params['desde'];
        $hasta = $params['hasta'];
        $storeId = (int) ($params['store_id'] ?? 0);

        $CI =& get_instance();

        $where = "i.deleted = 0 AND DATE(i.date) BETWEEN ? AND ?";
        $args = [$desde, $hasta];
        if ($storeId) {
            $where .= " AND i.storeId = ?";
            $args[] = $storeId;
        }

        $rows = $CI->db->query("
            SELECT
                u.idUser AS vendor_id,
                u.name AS vendor_name,
                COUNT(DISTINCT i.idInvoice) AS num_invoices,
                COUNT(DISTINCT i.clientId) AS num_clients,
                SUM(i.total) AS total_ventas,
                SUM(i.payment) AS total_recaudo,
                SUM(i.total - i.payment - i.discount) AS cartera_generada,
                AVG(i.total) AS avg_ticket
            FROM invoices i
            INNER JOIN users u ON u.idUser = i.vendorId
            WHERE $where
            GROUP BY u.idUser
            ORDER BY total_ventas DESC
        ", $args)->result_array();

        $totals = [
            'vendor_name' => 'TOTAL',
            'num_invoices' => array_sum(array_column($rows, 'num_invoices')),
            'num_clients' => '',
            'total_ventas' => array_sum(array_column($rows, 'total_ventas')),
            'total_recaudo' => array_sum(array_column($rows, 'total_recaudo')),
            'cartera_generada' => array_sum(array_column($rows, 'cartera_generada')),
            'avg_ticket' => '',
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
            'filename' => 'rendimiento_vendedores_' . ($params['hasta'] ?? date('Y-m-d')),
            'email_subject' => 'Rendimiento de vendedores — ' . date('d/m/Y', strtotime($params['hasta'] ?? 'now')),
            'pdf_orientation' => 'L',
        ];
    }

    private function columns(): array
    {
        return [
            ['key' => 'vendor_name',      'label' => 'Vendedor',          'type' => 'text'],
            ['key' => 'num_invoices',     'label' => 'Facturas',          'type' => 'number'],
            ['key' => 'num_clients',      'label' => 'Clientes',          'type' => 'number'],
            ['key' => 'total_ventas',     'label' => 'Ventas',            'type' => 'currency'],
            ['key' => 'total_recaudo',    'label' => 'Recaudo',           'type' => 'currency'],
            ['key' => 'cartera_generada', 'label' => 'Cartera Pendiente', 'type' => 'currency'],
            ['key' => 'avg_ticket',       'label' => 'Ticket Promedio',   'type' => 'currency'],
        ];
    }
}
