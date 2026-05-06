<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * PendingInvoices — facturas con saldo pendiente, una fila por factura.
 *
 * Equivalente Odoo: "Customer Invoices" filtrado por "Not Fully Paid".
 * Equivalente SAP B1: "Customer Receivables - Open Items List".
 *
 * Diferencia con Aging:
 * - Aging agrupa por cliente con buckets de antigüedad (vista ejecutiva).
 * - PendingInvoices muestra factura por factura con Total / Pagado / Saldo
 *   y status pendiente|parcial|vencida (vista operativa para cobranza).
 *
 * Schema de invoices:
 *   state = 0 → sin pagos (debe completa)
 *   state = 1 → parcialmente pagada
 *   state = 2 → pagada (excluida de este reporte)
 *   state = 3 → liquidada (excluida)
 */
class PendingInvoices extends AbstractReport
{
    public function id(): string { return 'pending_invoices'; }
    public function title(): string { return 'Facturas Pendientes'; }
    public function description(): string
    {
        return 'Facturas con saldo pendiente: completa (sin pago) o parcial. Equivalente Open Items en SAP B1.';
    }

    public function requiredRoles(): array
    {
        // 2=admin, 5=tesoreria, 8=cartera, 3=vendedor (ve solo las suyas, filtrado en query si rol)
        return [2, 3, 5, 8];
    }

    public function filterDefinitions(): array
    {
        return [
            ['name' => 'date_range', 'type' => 'date_range'],
            [
                'name' => 'desde',
                'label' => 'Desde',
                'type' => 'date',
                'default' => date('Y-01-01'),
            ],
            [
                'name' => 'hasta',
                'label' => 'Hasta',
                'type' => 'date',
                'default' => date('Y-m-d'),
            ],
            [
                'name' => 'client_id',
                'label' => 'Cliente',
                'type' => 'client',
            ],
            [
                'name' => 'vendor_id',
                'label' => 'Vendedor',
                'type' => 'vendor',
            ],
            $this->storeFilterDefinition(),
            [
                'name' => 'estado',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    '' => 'Todas las pendientes',
                    'pendiente' => 'Solo pendientes (debe completa)',
                    'parcial' => 'Solo parciales (pagó algo)',
                    'vencida' => 'Solo vencidas (>30 días)',
                ],
                'default' => '',
            ],
            [
                'name' => 'min_balance',
                'label' => 'Saldo mínimo',
                'type' => 'number',
                'default' => 0,
            ],
        ];
    }

    public function availableChannels(): array { return ['email', 'whatsapp', 'schedule']; }

    public function data(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $desde = $params['desde'];
        $hasta = $params['hasta'];
        $clientId = (int) ($params['client_id'] ?? 0);
        $vendorId = (string) ($params['vendor_id'] ?? '');
        $storeId = (int) ($params['store_id'] ?? 0);
        $estado = $params['estado'] ?? '';
        $minBalance = (float) ($params['min_balance'] ?? 0);

        $CI =& get_instance();

        $where = "i.deleted = 0 AND i.state IN (0,1) AND DATE(i.date) BETWEEN ? AND ?";
        $args = [$desde, $hasta];

        if ($clientId) {
            $where .= " AND i.clientId = ?";
            $args[] = $clientId;
        }
        if ($vendorId) {
            $where .= " AND i.vendorId = ?";
            $args[] = $vendorId;
        }
        if ($storeId) {
            $where .= " AND i.storeId = ?";
            $args[] = $storeId;
        }

        // Filtro por estado (pendiente/parcial/vencida)
        if ($estado === 'pendiente') {
            $where .= " AND i.state = 0";
        } elseif ($estado === 'parcial') {
            $where .= " AND i.state = 1";
        } elseif ($estado === 'vencida') {
            $where .= " AND DATEDIFF(CURDATE(), i.date) > 30";
        }

        $rows = $CI->db->query("
            SELECT
                i.idInvoice,
                i.date,
                DATEDIFF(CURDATE(), i.date) AS days_old,
                c.name AS client_name,
                c.idNum AS client_idnum,
                COALESCE(u.name, i.vendorId) AS vendor_name,
                s.name AS store_name,
                i.total,
                i.payment AS paid,
                i.discount,
                (i.total - i.payment - i.discount) AS balance,
                i.state
            FROM invoices i
            INNER JOIN clients c ON c.idClient = i.clientId
            LEFT JOIN users u ON u.idUser = i.vendorId
            LEFT JOIN stores s ON s.idStore = i.storeId
            WHERE $where
            HAVING balance > ?
            ORDER BY days_old DESC
            LIMIT 2000
        ", array_merge($args, [$minBalance]))->result_array();

        // Map state + days_old a status legible
        foreach ($rows as &$r) {
            $isOverdue = (int) $r['days_old'] > 30;
            if ((int) $r['state'] === 0) {
                $r['status'] = $isOverdue ? 'vencida (sin pago)' : 'pendiente';
            } else {
                $r['status'] = $isOverdue ? 'vencida (parcial)' : 'parcial';
            }
            $r['idInvoice'] = '#' . str_pad($r['idInvoice'], 6, '0', STR_PAD_LEFT);
        }
        unset($r);

        // Totals
        $totalAmount = array_sum(array_column($rows, 'total'));
        $totalPaid = array_sum(array_column($rows, 'paid'));
        $totalBalance = array_sum(array_column($rows, 'balance'));

        $totals = [
            'idInvoice' => 'TOTAL',
            'date' => '',
            'days_old' => '',
            'client_name' => count($rows) . ' factura' . (count($rows) === 1 ? '' : 's'),
            'client_idnum' => '',
            'vendor_name' => '',
            'store_name' => '',
            'total' => $totalAmount,
            'paid' => $totalPaid,
            'discount' => array_sum(array_column($rows, 'discount')),
            'balance' => $totalBalance,
            'status' => '',
        ];

        return [
            'columns' => $this->columns(),
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    public function meta(array $params): array
    {
        $estado = $params['estado'] ?? '';
        $estadoLabel = $estado ?: 'todas';

        return [
            'filename' => 'facturas_pendientes_' . $estadoLabel . '_' . date('Y-m-d'),
            'email_subject' => 'Reporte de facturas pendientes — ' . date('d/m/Y'),
            'whatsapp_report_label' => 'facturas pendientes',
            'pdf_orientation' => 'L', // 11 columnas → landscape
        ];
    }

    private function columns(): array
    {
        return [
            ['key' => 'idInvoice',     'label' => 'Factura',  'type' => 'text'],
            ['key' => 'date',          'label' => 'Fecha',    'type' => 'date'],
            ['key' => 'days_old',      'label' => 'Días',     'type' => 'number'],
            ['key' => 'client_name',   'label' => 'Cliente',  'type' => 'text'],
            ['key' => 'client_idnum',  'label' => 'NIT',      'type' => 'text'],
            ['key' => 'vendor_name',   'label' => 'Vendedor', 'type' => 'text'],
            ['key' => 'store_name',    'label' => 'Bodega',   'type' => 'text'],
            ['key' => 'total',         'label' => 'Total',    'type' => 'currency'],
            ['key' => 'paid',          'label' => 'Pagado',   'type' => 'currency'],
            ['key' => 'balance',       'label' => 'Saldo',    'type' => 'currency'],
            ['key' => 'status',        'label' => 'Estado',   'type' => 'text'],
        ];
    }
}
