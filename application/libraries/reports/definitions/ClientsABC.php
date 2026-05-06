<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * ClientsABC — Análisis Pareto 80/15/5 de clientes por revenue.
 *
 * Inspirado en Odoo "Customer Analysis" + SAP B1 "Customer ABC".
 * Detecta los pocos clientes críticos para el negocio (los que generan
 * 80% del revenue son los "A" — perdurables; los "C" son los que aportan
 * poco y son los más numerosos).
 *
 * Algoritmo:
 *   1. SUM(net_sales) por cliente en periodo
 *   2. Sort desc
 *   3. Cumulative %
 *   4. A: hasta 80%, B: 80-95%, C: 95-100%
 *
 * Output:
 *   - kpis: total_revenue, total_clients, count_a/b/c, top10_concentration
 *   - rows: clientes ordenados con cumulative% y tag ABC
 *   - drilldown: top facturas por cliente
 */
class ClientsABC extends AbstractReport
{
    public function id(): string { return 'clients_abc'; }
    public function title(): string { return 'Análisis ABC de Clientes'; }
    public function description(): string
    {
        return 'Pareto 80/15/5 sobre revenue. Detecta los clientes A (80% del revenue), B (siguiente 15%), C (último 5%).';
    }

    public function requiredRoles(): array { return [2, 3, 5, 8]; }

    public function filterDefinitions(): array
    {
        return [
            ['name' => 'date_range', 'type' => 'date_range'],
            ['name' => 'desde', 'label' => 'Desde', 'type' => 'date', 'default' => date('Y-01-01')],
            ['name' => 'hasta', 'label' => 'Hasta', 'type' => 'date', 'default' => date('Y-m-d')],
            $this->storeFilterDefinition(),
            [
                'name' => 'min_revenue',
                'label' => 'Revenue mínimo',
                'type' => 'number',
                'default' => 0,
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
        $minRevenue = (float) ($params['min_revenue'] ?? 0);

        $CI =& get_instance();

        // Filtrar clientes borrados (c.deleted=0): si un cliente fue eliminado pero
        // tiene facturas, NO debe aparecer en el ABC. Antes salia duplicado cuando
        // el mismo cliente tenia un registro borrado y otro activo (ej: OSWALDO PICO
        // con idClient 2369 deleted + idClient 861 activo).
        $where = "i.deleted = 0 AND c.deleted = 0 AND i.state IN (0,1,2) AND DATE(i.date) BETWEEN ? AND ?";
        $args = [$desde, $hasta];
        if ($storeId) { $where .= " AND i.storeId = ?"; $args[] = $storeId; }

        // Aggregate por cliente
        $rows = $CI->db->query("
            SELECT
                c.idClient,
                c.name AS client_name,
                c.idNum,
                c.city,
                COALESCE(u.name, i.vendorId) AS vendor_name,
                COUNT(*) AS num_invoices,
                SUM(i.total - i.discount) AS revenue,
                MAX(i.date) AS last_purchase
            FROM invoices i
            INNER JOIN clients c ON c.idClient = i.clientId
            LEFT JOIN users u ON u.idUser = i.vendorId
            WHERE $where
            GROUP BY c.idClient
            HAVING revenue >= ?
            ORDER BY revenue DESC
        ", array_merge($args, [$minRevenue]))->result_array();

        $totalRevenue = array_sum(array_column($rows, 'revenue'));
        $totalClients = count($rows);

        // Calcular cumulative% y tag ABC en una pasada
        $cumulative = 0;
        $countA = 0; $countB = 0; $countC = 0;
        $sumA = 0; $sumB = 0; $sumC = 0;
        $top10Sum = 0;
        foreach ($rows as $i => &$r) {
            $r['revenue']         = (float) $r['revenue'];
            $r['num_invoices']    = (int) $r['num_invoices'];
            $cumulative          += $r['revenue'];
            $r['cumulative']      = $cumulative;
            $r['cumulative_pct']  = $totalRevenue > 0 ? ($cumulative / $totalRevenue) * 100 : 0;
            $r['revenue_pct']     = $totalRevenue > 0 ? ($r['revenue'] / $totalRevenue) * 100 : 0;
            // Tag
            if ($r['cumulative_pct'] <= 80)      { $r['abc'] = 'A'; $countA++; $sumA += $r['revenue']; }
            elseif ($r['cumulative_pct'] <= 95)  { $r['abc'] = 'B'; $countB++; $sumB += $r['revenue']; }
            else                                  { $r['abc'] = 'C'; $countC++; $sumC += $r['revenue']; }
            if ($i < 10) $top10Sum += $r['revenue'];
        }
        unset($r);

        $kpis = [
            'total_revenue'        => $totalRevenue,
            'total_clients'        => $totalClients,
            'count_a'              => $countA,
            'count_b'              => $countB,
            'count_c'              => $countC,
            'sum_a'                => $sumA,
            'sum_b'                => $sumB,
            'sum_c'                => $sumC,
            'pct_a'                => $totalRevenue > 0 ? ($sumA / $totalRevenue) * 100 : 0,
            'pct_b'                => $totalRevenue > 0 ? ($sumB / $totalRevenue) * 100 : 0,
            'pct_c'                => $totalRevenue > 0 ? ($sumC / $totalRevenue) * 100 : 0,
            'top10_concentration'  => $totalRevenue > 0 ? ($top10Sum / $totalRevenue) * 100 : 0,
        ];

        // Drill-down: top facturas por cliente
        $clientIds = array_column($rows, 'idClient');
        $drilldown = $this->fetchClientDrilldown($clientIds, $desde, $hasta, $storeId);

        // Top 20 para bar chart
        $top20 = array_slice($rows, 0, 20);

        $totals = [
            'client_name'    => 'TOTAL',
            'idNum'          => '',
            'city'           => '',
            'vendor_name'    => $totalClients . ' clientes',
            'num_invoices'   => array_sum(array_column($rows, 'num_invoices')),
            'revenue'        => $totalRevenue,
            'revenue_pct'    => 100.0,
            'cumulative_pct' => 100.0,
            'abc'            => '',
            'last_purchase'  => '',
        ];

        return [
            'kpis'                => $kpis,
            'top20'               => $top20,
            'drilldown'           => $drilldown,
            'entity_label'        => 'cliente',
            'entity_label_plural' => 'clientes',
            'id_field'            => 'idClient',
            'name_field'          => 'client_name',
            'columns'             => $this->columns(),
            'rows'                => $rows,
            'totals'              => $totals,
            'period'              => ['desde' => $desde, 'hasta' => $hasta],
        ];
    }

    public function meta(array $params): array
    {
        return [
            'filename' => 'clientes_abc_' . ($params['desde'] ?? date('Y-01-01')) . '_' . ($params['hasta'] ?? date('Y-m-d')),
            'email_subject' => 'Análisis ABC de clientes',
            'pdf_orientation' => 'L',
            'html_template' => 'sisvent/reports/templates/_abc',
        ];
    }

    private function fetchClientDrilldown(array $clientIds, string $desde, string $hasta, int $storeId): array
    {
        if (empty($clientIds)) return [];
        $CI =& get_instance();

        // Solo top-5 facturas por cliente — drill compacto
        // (los clientIds vienen del query principal que ya filtra c.deleted=0,
        // entonces aqui no necesitamos rejoin a clients)
        $placeholders = implode(',', array_fill(0, count($clientIds), '?'));
        $where = "i.deleted = 0 AND i.state IN (0,1,2) AND DATE(i.date) BETWEEN ? AND ? AND i.clientId IN ($placeholders)";
        $args = array_merge([$desde, $hasta], $clientIds);
        if ($storeId) { $where .= " AND i.storeId = ?"; $args[] = $storeId; }

        $rows = $CI->db->query("
            SELECT i.clientId, i.idInvoice, i.date, i.total, i.discount,
                   (i.total - i.discount) AS net,
                   COALESCE(u.name, i.vendorId) AS vendor_name
            FROM invoices i
            LEFT JOIN users u ON u.idUser = i.vendorId
            WHERE $where
            ORDER BY i.clientId, net DESC
        ", $args)->result_array();

        $byClient = [];
        foreach ($rows as $r) {
            $cid = (int) $r['clientId'];
            if (!isset($byClient[$cid])) $byClient[$cid] = [];
            if (count($byClient[$cid]) < 5) {
                $byClient[$cid][] = [
                    'idInvoice'   => '#' . str_pad($r['idInvoice'], 6, '0', STR_PAD_LEFT),
                    'date'        => $r['date'],
                    'vendor_name' => $r['vendor_name'] ?? '',
                    'total'       => (float) $r['total'],
                    'net'         => (float) $r['net'],
                ];
            }
        }
        return $byClient;
    }

    private function columns(): array
    {
        return [
            ['key' => 'client_name',    'label' => 'Cliente',     'type' => 'text'],
            ['key' => 'idNum',          'label' => 'NIT',         'type' => 'text'],
            ['key' => 'city',           'label' => 'Ciudad',      'type' => 'text'],
            ['key' => 'vendor_name',    'label' => 'Vendedor',    'type' => 'text'],
            ['key' => 'num_invoices',   'label' => 'Facturas',    'type' => 'number'],
            ['key' => 'revenue',        'label' => 'Revenue',     'type' => 'currency'],
            ['key' => 'revenue_pct',    'label' => '% del total', 'type' => 'number', 'decimals' => 2],
            ['key' => 'cumulative_pct', 'label' => '% Acum.',     'type' => 'number', 'decimals' => 1],
            ['key' => 'abc',            'label' => 'ABC',         'type' => 'text'],
            ['key' => 'last_purchase',  'label' => 'Última compra','type' => 'date'],
        ];
    }
}
