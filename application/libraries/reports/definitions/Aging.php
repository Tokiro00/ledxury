<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * Aging — antigüedad de cartera con KPIs + chart de buckets + drill-down.
 *
 * Reescrito v1.30.30: pasa de tabla simple a vista ejecutiva con:
 *   - KPI strip: total cartera, # clientes, % crítico (+90), promedio días
 *   - Bar chart 4 buckets (0-30 / 31-60 / 61-90 / +90)
 *   - Tabla por cliente con drill-down a sus facturas vencidas
 *
 * Output: cada row es un cliente con sus 4 buckets + total + último pago.
 * Drilldown: array indexado por idClient con top 10 facturas más vencidas.
 */
class Aging extends AbstractReport
{
    public function id(): string { return 'aging'; }
    public function title(): string { return 'Antigüedad de Cartera'; }
    public function description(): string
    {
        return 'Cartera por cliente desglosada en buckets 0-30 / 31-60 / 61-90 / +90 días con drill-down a facturas vencidas.';
    }

    public function requiredRoles(): array
    {
        return [2, 5, 8];
    }

    public function filterDefinitions(): array
    {
        return [
            ['name' => 'vendor_id', 'label' => 'Vendedor', 'type' => 'vendor'],
            $this->storeFilterDefinition(),
            [
                'name' => 'min_saldo',
                'label' => 'Saldo mínimo',
                'type' => 'number',
                'default' => 0,
                'placeholder' => '0',
            ],
            [
                'name' => 'order_by',
                'label' => 'Ordenar por',
                'type' => 'select',
                'options' => [
                    'days_91_plus' => 'Más vencido (+90)',
                    'total' => 'Mayor saldo total',
                    'name' => 'Nombre A-Z',
                ],
                'default' => 'days_91_plus',
            ],
        ];
    }

    public function availableChannels(): array { return ['email', 'schedule']; }

    public function data(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $vendorId = (int) ($params['vendor_id'] ?? 0);
        $storeId = (int) ($params['store_id'] ?? 0);
        $minSaldo = (float) ($params['min_saldo'] ?? 0);
        $orderBy = $params['order_by'] ?? 'days_91_plus';

        $CI =& get_instance();

        $where = "i.deleted = 0 AND i.state IN (0,1)";
        $args = [];
        if ($vendorId) { $where .= " AND c.vendor = ?"; $args[] = $vendorId; }
        if ($storeId)  { $where .= " AND i.storeId = ?"; $args[] = $storeId; }

        $sql = "
            SELECT
                c.idClient,
                c.name,
                c.idNum,
                c.city,
                u.name AS vendor_name,
                SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) <= 30 THEN (i.total - i.payment - i.discount) ELSE 0 END) AS bucket_current,
                SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) BETWEEN 31 AND 60 THEN (i.total - i.payment - i.discount) ELSE 0 END) AS bucket_31_60,
                SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) BETWEEN 61 AND 90 THEN (i.total - i.payment - i.discount) ELSE 0 END) AS bucket_61_90,
                SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) > 90 THEN (i.total - i.payment - i.discount) ELSE 0 END) AS bucket_91_plus,
                SUM(i.total - i.payment - i.discount) AS total,
                COUNT(*) AS num_invoices,
                AVG(DATEDIFF(CURDATE(), i.date)) AS avg_days,
                (SELECT MAX(p.date) FROM payments p WHERE p.clientId = c.idClient AND p.deleted = 0) AS last_payment
            FROM invoices i
            INNER JOIN clients c ON c.idClient = i.clientId
            LEFT JOIN users u ON u.idUser = c.vendor
            WHERE $where
            GROUP BY c.idClient
            HAVING total > ?
            ORDER BY " . $this->resolveOrderBy($orderBy) . "
            LIMIT 500
        ";
        $args[] = $minSaldo;

        $rows = $CI->db->query($sql, $args)->result_array();

        // KPIs agregados sobre todos los clientes con saldo
        $totalCart = array_sum(array_column($rows, 'total'));
        $bucketCurrent = array_sum(array_column($rows, 'bucket_current'));
        $bucket3160   = array_sum(array_column($rows, 'bucket_31_60'));
        $bucket6190   = array_sum(array_column($rows, 'bucket_61_90'));
        $bucket91     = array_sum(array_column($rows, 'bucket_91_plus'));
        $numClients   = count($rows);

        // Promedio ponderado de días vencidos
        $weightedDays = 0;
        $totalAbs = 0;
        foreach ($rows as $r) {
            $weightedDays += (float)$r['avg_days'] * (float)$r['total'];
            $totalAbs += (float)$r['total'];
        }
        $avgDaysOverdue = $totalAbs > 0 ? $weightedDays / $totalAbs : 0;

        $kpis = [
            'total_cart'        => $totalCart,
            'num_clients'       => $numClients,
            'critical_amount'   => $bucket91,
            'critical_pct'      => $totalCart > 0 ? ($bucket91 / $totalCart) * 100 : 0,
            'avg_days_overdue'  => $avgDaysOverdue,
            'num_invoices'      => array_sum(array_column($rows, 'num_invoices')),
        ];

        $buckets = [
            ['key' => 'current', 'label' => '0–30 días',  'short' => '0-30',  'total' => $bucketCurrent, 'color' => '#5EBA47', 'severity' => 'ok'],
            ['key' => '31_60',   'label' => '31–60 días', 'short' => '31-60', 'total' => $bucket3160,    'color' => '#F39C12', 'severity' => 'warn'],
            ['key' => '61_90',   'label' => '61–90 días', 'short' => '61-90', 'total' => $bucket6190,    'color' => '#E67E22', 'severity' => 'high'],
            ['key' => '91_plus', 'label' => '+90 días',   'short' => '+90',   'total' => $bucket91,      'color' => '#C0392B', 'severity' => 'crit'],
        ];
        foreach ($buckets as &$b) {
            $b['pct'] = $totalCart > 0 ? ($b['total'] / $totalCart) * 100 : 0;
        }
        unset($b);

        // Drilldown: top facturas más vencidas por cliente (top 10 cada uno)
        $drilldown = $this->fetchClientDrilldown(array_column($rows, 'idClient'));

        $totals = [
            'name' => 'TOTAL',
            'idNum' => '',
            'city' => '',
            'vendor_name' => $numClients . ' cliente' . ($numClients === 1 ? '' : 's'),
            'bucket_current' => $bucketCurrent,
            'bucket_31_60'   => $bucket3160,
            'bucket_61_90'   => $bucket6190,
            'bucket_91_plus' => $bucket91,
            'total'          => $totalCart,
            'last_payment'   => '',
        ];

        return [
            'kpis'      => $kpis,
            'buckets'   => $buckets,
            'drilldown' => $drilldown,
            'columns'   => $this->columns(),
            'rows'      => $rows,
            'totals'    => $totals,
        ];
    }

    public function meta(array $params): array
    {
        return [
            'filename' => 'cartera_aging_' . date('Y-m-d'),
            'email_subject' => 'Antigüedad de cartera — ' . date('d/m/Y'),
            'pdf_orientation' => 'L',
        ];
    }

    /**
     * Top 10 facturas más vencidas por cliente. Devuelve indexado por idClient.
     * Una sola query con ORDER BY clientId, days_overdue DESC; PHP corta a 10
     * por cliente.
     *
     * @param int[] $clientIds
     * @return array<int, array>
     */
    private function fetchClientDrilldown(array $clientIds): array
    {
        if (empty($clientIds)) return [];
        $CI =& get_instance();

        $placeholders = implode(',', array_fill(0, count($clientIds), '?'));
        $rows = $CI->db->query("
            SELECT
                i.clientId,
                i.idInvoice,
                i.date,
                DATEDIFF(CURDATE(), i.date) AS days_overdue,
                i.total,
                i.payment,
                i.discount,
                (i.total - i.payment - i.discount) AS balance,
                i.state
            FROM invoices i
            WHERE i.deleted = 0
              AND i.state IN (0, 1)
              AND i.clientId IN ($placeholders)
              AND (i.total - i.payment - i.discount) > 0
            ORDER BY i.clientId, days_overdue DESC
        ", $clientIds)->result_array();

        $byClient = [];
        foreach ($rows as $r) {
            $cid = (int) $r['clientId'];
            if (!isset($byClient[$cid])) $byClient[$cid] = [];
            if (count($byClient[$cid]) < 10) {
                $byClient[$cid][] = [
                    'idInvoice'    => '#' . str_pad($r['idInvoice'], 6, '0', STR_PAD_LEFT),
                    'date'         => $r['date'],
                    'days_overdue' => (int) $r['days_overdue'],
                    'total'        => (float) $r['total'],
                    'payment'      => (float) $r['payment'],
                    'balance'      => (float) $r['balance'],
                    'state'        => (int) $r['state'],
                ];
            }
        }
        return $byClient;
    }

    private function resolveOrderBy(string $orderBy): string
    {
        $map = [
            'days_91_plus' => 'bucket_91_plus DESC',
            'total'        => 'total DESC',
            'name'         => 'c.name ASC',
        ];
        return $map[$orderBy] ?? 'bucket_91_plus DESC';
    }

    private function columns(): array
    {
        return [
            ['key' => 'name',          'label' => 'Cliente',     'type' => 'text'],
            ['key' => 'idNum',         'label' => 'NIT',         'type' => 'text'],
            ['key' => 'city',          'label' => 'Ciudad',      'type' => 'text'],
            ['key' => 'vendor_name',   'label' => 'Vendedor',    'type' => 'text'],
            ['key' => 'bucket_current','label' => '0-30',        'type' => 'currency'],
            ['key' => 'bucket_31_60',  'label' => '31-60',       'type' => 'currency'],
            ['key' => 'bucket_61_90',  'label' => '61-90',       'type' => 'currency'],
            ['key' => 'bucket_91_plus','label' => '+90',         'type' => 'currency'],
            ['key' => 'total',         'label' => 'Total',       'type' => 'currency'],
            ['key' => 'last_payment',  'label' => 'Último pago', 'type' => 'date'],
        ];
    }
}
