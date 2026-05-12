<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * ReturnsAnalytics — Análisis de Devoluciones.
 *
 * Vista ejecutiva sobre el módulo /admin/devoluciones. Responde:
 *   - ¿Cuál es nuestra tasa de devolución? (global + por carrier/vendedor/ciudad)
 *   - ¿Cuáles clientes son problemáticos? (>3 devoluciones en el período)
 *   - ¿Qué productos se devuelven más?
 *   - ¿Cuánto nos cuestan las devoluciones? (flete asumido por mes)
 *
 * Fuente: shipping_returns + shipping_guides (denominador para tasas) +
 * invoices/invoice_details (productos devueltos) + clients + users.
 *
 * Tasa de devolución = devoluciones / total_despachado (en el período y scope)
 *
 * Equivalente a "Returns Analytics" de Mercado Libre / Shopify Admin.
 */
class ReturnsAnalytics extends AbstractReport
{
    public function id(): string { return 'returns_analytics'; }
    public function title(): string { return 'Análisis de Devoluciones'; }
    public function description(): string
    {
        return 'Tasa de devolución por carrier/vendedor/ciudad, clientes problemáticos, productos más devueltos y costo mensual de devoluciones.';
    }

    public function requiredRoles(): array { return [2, 5, 9]; }

    public function filterDefinitions(): array
    {
        return [
            ['name' => 'date_range', 'type' => 'date_range'],
            [
                'name'    => 'desde',
                'label'   => 'Desde',
                'type'    => 'date',
                'default' => date('Y-01-01'),
            ],
            [
                'name'    => 'hasta',
                'label'   => 'Hasta',
                'type'    => 'date',
                'default' => date('Y-m-d'),
            ],
            $this->storeFilterDefinition(),
            [
                'name'    => 'carrier',
                'label'   => 'Transportadora',
                'type'    => 'select',
                'options' => [
                    'all'             => 'Todas',
                    'Interrapidisimo' => 'Interrapidísimo',
                    'Servientrega'    => 'Servientrega',
                    'Coordinadora'    => 'Coordinadora',
                ],
                'default' => 'all',
            ],
        ];
    }

    public function availableChannels(): array { return ['email', 'schedule']; }

    public function data(array $params): array
    {
        $params  = $this->applyFilterDefaults($params);
        $desde   = $params['desde'];
        $hasta   = $params['hasta'];
        $storeId = (int) ($params['store_id'] ?? 0);
        $carrier = $params['carrier'] ?? 'all';

        $CI =& get_instance();

        // -------------------------------------------------------------
        // Filtros comunes — cohort por fecha de DESPACHO (sg.created_at).
        //
        // Antes filtraba el numerador por sr.detected_at, pero como el
        // auto-detector lazy crea filas con detected_at=NOW al cargar el
        // listado por primera vez, casi todas tenían fechas recientes
        // independiente de cuándo se despachó. Resultado: tasa > 100%
        // porque numerador (detectadas en mayo) y denominador (despachadas
        // en mayo) eran cohortes distintas.
        //
        // Ahora ambas queries filtran por sg.created_at = fecha de despacho.
        // Significado: "de las guías despachadas en este rango, cuántas
        // fueron devueltas y cuántas se entregaron OK".
        // -------------------------------------------------------------
        $rWhere = ["DATE(sg.created_at) BETWEEN ? AND ?"];
        $rArgs  = [$desde, $hasta];
        $gWhere = ["DATE(sg.created_at) BETWEEN ? AND ?"];
        $gArgs  = [$desde, $hasta];

        if ($storeId) {
            $rWhere[] = 'sg.storeId = ?'; $rArgs[] = $storeId;
            $gWhere[] = 'sg.storeId = ?'; $gArgs[] = $storeId;
        }
        if ($carrier !== 'all') {
            $rWhere[] = 'sg.carrierName = ?'; $rArgs[] = $carrier;
            $gWhere[] = 'sg.carrierName = ?'; $gArgs[] = $carrier;
        }

        $rWhereSql = implode(' AND ', $rWhere);
        $gWhereSql = implode(' AND ', $gWhere);

        // -------------------------------------------------------------
        // KPIs globales
        // -------------------------------------------------------------
        $sqlKpis = "
            SELECT
                COUNT(DISTINCT sr.id) AS total_devs,
                COALESCE(SUM(sg.valorDeclarado), 0) AS valor_devs,
                COALESCE(SUM(sr.flete_perdido), 0) AS costo_total,
                COALESCE(SUM(CASE WHEN sr.status='perdida' THEN 1 ELSE 0 END), 0) AS perdidas,
                AVG(CASE WHEN sr.received_back_at IS NOT NULL
                         THEN TIMESTAMPDIFF(DAY, sr.detected_at, sr.received_back_at)
                         ELSE NULL END) AS dias_prom_recibir
            FROM shipping_returns sr
            INNER JOIN shipping_guides sg ON sg.id = sr.shipping_guide_id
            WHERE $rWhereSql
        ";
        $kpis = (array) $CI->db->query($sqlKpis, $rArgs)->row();

        $totalDespachado = (int) $CI->db->query("
            SELECT COUNT(*) AS n FROM shipping_guides sg WHERE $gWhereSql
        ", $gArgs)->row()->n;

        $tasaGlobal = $totalDespachado > 0 ? round(((int)$kpis['total_devs'] / $totalDespachado) * 100, 2) : 0;

        $kpiData = [
            'total_devs'        => (int) $kpis['total_devs'],
            'valor_devs'        => (float) $kpis['valor_devs'],
            'costo_total'       => (float) $kpis['costo_total'],
            'perdidas'          => (int) $kpis['perdidas'],
            'dias_prom_recibir' => $kpis['dias_prom_recibir'] !== null ? round((float)$kpis['dias_prom_recibir'], 1) : null,
            'total_despachado'  => $totalDespachado,
            'tasa_global'       => $tasaGlobal,
        ];

        // -------------------------------------------------------------
        // Por transportadora
        // -------------------------------------------------------------
        $byCarrier = $this->_tasaPorDimension($CI, 'sg.carrierName', 'carrier', $rWhereSql, $rArgs, $gWhereSql, $gArgs);

        // -------------------------------------------------------------
        // Por vendedor
        // -------------------------------------------------------------
        $byVendor = $this->_tasaPorDimension(
            $CI,
            'u.name', 'vendor',
            $rWhereSql, $rArgs, $gWhereSql, $gArgs,
            'LEFT JOIN users u ON u.idUser = sr.vendor_id',
            'LEFT JOIN invoices i2 ON i2.idInvoice = sg.invoiceId LEFT JOIN users u ON u.idUser = i2.vendorId'
        );

        // -------------------------------------------------------------
        // Por ciudad (top 15)
        // -------------------------------------------------------------
        $byCity = $this->_tasaPorDimension(
            $CI,
            'sg.ciudadDestinoNombre', 'ciudad',
            $rWhereSql, $rArgs, $gWhereSql, $gArgs,
            '', '', 15
        );

        // -------------------------------------------------------------
        // Clientes problemáticos (más de 3 devoluciones en el período)
        // -------------------------------------------------------------
        $sqlProblemClients = "
            SELECT
                c.idClient,
                c.name AS client_name,
                c.city AS client_city,
                COUNT(sr.id) AS num_devs,
                COALESCE(SUM(sg.valorDeclarado), 0) AS valor_devs,
                MAX(sr.detected_at) AS ultima_dev
            FROM shipping_returns sr
            INNER JOIN shipping_guides sg ON sg.id = sr.shipping_guide_id
            LEFT JOIN clients c ON c.idClient = sr.client_id
            WHERE $rWhereSql AND c.idClient IS NOT NULL
            GROUP BY c.idClient
            HAVING num_devs >= 3
            ORDER BY num_devs DESC, valor_devs DESC
            LIMIT 50
        ";
        $problemClients = $CI->db->query($sqlProblemClients, $rArgs)->result_array();
        foreach ($problemClients as &$pc) {
            $pc['num_devs']   = (int)$pc['num_devs'];
            $pc['valor_devs'] = (float)$pc['valor_devs'];
        } unset($pc);

        // -------------------------------------------------------------
        // Productos más devueltos (top 15)
        // -------------------------------------------------------------
        // Join con invoice_details vía invoice_id de la devolución
        // invoice_details usa columna 'total' (no 'subtotal'). Fallback a
        // quantity*unit por defensa si total está null en filas viejas.
        $sqlTopProducts = "
            SELECT
                d.productId AS code,
                p.description AS name,
                SUM(d.quantity) AS qty_devuelta,
                COUNT(DISTINCT sr.id) AS num_devs,
                COALESCE(SUM(IFNULL(d.total, d.quantity * d.unit)), 0) AS valor_devuelto
            FROM shipping_returns sr
            INNER JOIN shipping_guides sg ON sg.id = sr.shipping_guide_id
            JOIN invoice_details d ON d.invoiceId = sr.invoice_id
            JOIN products p ON p.idProduct = d.productId
            WHERE $rWhereSql
            GROUP BY d.productId, p.description
            ORDER BY qty_devuelta DESC
            LIMIT 15
        ";
        $topProducts = $CI->db->query($sqlTopProducts, $rArgs)->result_array();
        foreach ($topProducts as &$tp) {
            $tp['qty_devuelta']   = (float)$tp['qty_devuelta'];
            $tp['num_devs']       = (int)$tp['num_devs'];
            $tp['valor_devuelto'] = (float)$tp['valor_devuelto'];
        } unset($tp);

        // -------------------------------------------------------------
        // Evolución mensual
        // -------------------------------------------------------------
        // Mensual: agrupa por mes de DESPACHO (no de detección) — alinea con
        // los KPIs cohort. "En marzo despachamos X y N de esas se devolvieron".
        $sqlMonthly = "
            SELECT
                DATE_FORMAT(sg.created_at, '%Y-%m') AS mes,
                COUNT(sr.id) AS num_devs,
                COALESCE(SUM(sg.valorDeclarado), 0) AS valor_devs,
                COALESCE(SUM(sr.flete_perdido), 0) AS costo
            FROM shipping_returns sr
            INNER JOIN shipping_guides sg ON sg.id = sr.shipping_guide_id
            WHERE $rWhereSql
            GROUP BY mes
            ORDER BY mes ASC
        ";
        $monthly = $CI->db->query($sqlMonthly, $rArgs)->result_array();
        foreach ($monthly as &$m) {
            $m['num_devs']   = (int)$m['num_devs'];
            $m['valor_devs'] = (float)$m['valor_devs'];
            $m['costo']      = (float)$m['costo'];
        } unset($m);

        // -------------------------------------------------------------
        // Rows / columns / totals (para fallback en _generic y export Excel)
        // -------------------------------------------------------------
        $columns = [
            ['key' => 'mes',        'label' => 'Mes',           'type' => 'text'],
            ['key' => 'num_devs',   'label' => '# Devoluciones','type' => 'number'],
            ['key' => 'valor_devs', 'label' => 'Valor',         'type' => 'currency'],
            ['key' => 'costo',      'label' => 'Costo asumido', 'type' => 'currency'],
        ];
        $totals = [
            'mes'        => 'TOTAL',
            'num_devs'   => array_sum(array_column($monthly, 'num_devs')),
            'valor_devs' => array_sum(array_column($monthly, 'valor_devs')),
            'costo'      => array_sum(array_column($monthly, 'costo')),
        ];

        return [
            'kpis'             => $kpiData,
            'by_carrier'       => $byCarrier,
            'by_vendor'        => $byVendor,
            'by_city'          => $byCity,
            'problem_clients'  => $problemClients,
            'top_products'     => $topProducts,
            'monthly'          => $monthly,
            'columns'          => $columns,
            'rows'             => $monthly,
            'totals'           => $totals,
        ];
    }

    /**
     * Calcula tasa de devolución por una dimensión (carrier/vendor/city).
     * Hace dos queries: una para devoluciones y otra para denominador (despachado).
     * Luego empareja en PHP.
     */
    private function _tasaPorDimension($CI, $dimSql, $key, $rWhere, $rArgs, $gWhere, $gArgs, $extraJoinR = '', $extraJoinG = '', $limit = 50)
    {
        // Numerador: devoluciones por dimensión. INNER JOIN con sg porque
        // filtramos por sg.created_at (cohort de despacho).
        $sqlN = "
            SELECT $dimSql AS dim, COUNT(*) AS n, COALESCE(SUM(sg.valorDeclarado), 0) AS valor, COALESCE(SUM(sr.flete_perdido), 0) AS costo
            FROM shipping_returns sr
            INNER JOIN shipping_guides sg ON sg.id = sr.shipping_guide_id
            $extraJoinR
            WHERE $rWhere
            GROUP BY dim
        ";
        $rowsN = $CI->db->query($sqlN, $rArgs)->result();
        $devs = [];
        foreach ($rowsN as $r) {
            $dim = $r->dim ?: '—';
            $devs[$dim] = ['n' => (int)$r->n, 'valor' => (float)$r->valor, 'costo' => (float)$r->costo];
        }

        // Denominador: total despachado por dimensión
        $sqlD = "
            SELECT $dimSql AS dim, COUNT(*) AS n
            FROM shipping_guides sg
            $extraJoinG
            WHERE $gWhere
            GROUP BY dim
        ";
        $rowsD = $CI->db->query($sqlD, $gArgs)->result();
        $total = [];
        foreach ($rowsD as $r) {
            $dim = $r->dim ?: '—';
            $total[$dim] = (int)$r->n;
        }

        // Merge + tasa
        $out = [];
        foreach ($devs as $dim => $info) {
            $den = isset($total[$dim]) ? $total[$dim] : 0;
            $tasa = $den > 0 ? round(($info['n'] / $den) * 100, 2) : 0;
            $out[] = [
                $key            => $dim,
                'despachadas'   => $den,
                'devueltas'     => $info['n'],
                'tasa_pct'      => $tasa,
                'valor_devs'    => $info['valor'],
                'costo'         => $info['costo'],
            ];
        }
        // Ordenar por # devueltas desc
        usort($out, fn($a, $b) => $b['devueltas'] <=> $a['devueltas']);
        return array_slice($out, 0, $limit);
    }

    public function meta(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        return [
            'filename'        => 'devoluciones_analytics_' . $params['desde'] . '_' . $params['hasta'],
            'email_subject'   => 'Análisis de Devoluciones · ' . $params['desde'] . ' al ' . $params['hasta'],
            'pdf_orientation' => 'L',
        ];
    }
}
