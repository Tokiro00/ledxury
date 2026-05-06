<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * CashFlow — Posicion de Caja con saldos inicial/final por cuenta + flujos
 * clasificados (Operativo / Inversion / Financiamiento).
 *
 * Reescrito v1.30.26: el reporte arma una secuencia conciliable
 *   saldo_inicial + ingresos - egresos = saldo_final
 *
 * por cada caja y banco, mas la clasificacion contable estilo Odoo
 * (IAS 7) y el desglose visual por categoria.
 *
 * Filtros:
 *   - date_range con presets (default mes en curso)
 *   - store_id · source_type (caja/banco/ambos)
 *   - group_by (day/week/month) -> drives buckets temporales
 *
 * Output completo en data():
 *   kpis           ingresos, egresos, neto, num_movs, opening, closing,
 *                  prev_neto, growth_pct
 *   timeline       barras divergentes con neto acumulado
 *   by_category    bar divergente por categoria
 *   classified     {operativo, inversion, financiamiento, total}
 *   accounts       lista de cuentas con opening/in/out/closing
 *   top_movements  {ingresos, egresos} top 20 cada uno con concepto
 *   columns/rows/totals  tabla detalle por categoria
 */
class CashFlow extends AbstractReport
{
    public function id(): string { return 'cash_flow'; }
    public function title(): string { return 'Posición de Caja'; }
    public function description(): string
    {
        return 'Saldo inicial + flujos del período por categoría = Saldo final, con clasificación operativo/inversión/financiamiento estilo IAS 7.';
    }

    public function requiredRoles(): array { return [2, 5]; }

    public function filterDefinitions(): array
    {
        return [
            ['name' => 'date_range', 'type' => 'date_range'],
            ['name' => 'desde', 'label' => 'Desde', 'type' => 'date', 'default' => date('Y-m-01'), 'required' => true],
            ['name' => 'hasta', 'label' => 'Hasta', 'type' => 'date', 'default' => date('Y-m-d'), 'required' => true],
            $this->storeFilterDefinition(),
            [
                'name' => 'source_type',
                'label' => 'Origen',
                'type' => 'select',
                'options' => ['' => 'Todos (caja + banco)', 'caja' => 'Solo Caja', 'banco' => 'Solo Banco'],
                'default' => '',
            ],
            [
                'name' => 'group_by',
                'label' => 'Agrupar por',
                'type' => 'select',
                'options' => ['day' => 'Día', 'week' => 'Semana', 'month' => 'Mes'],
                'default' => 'day',
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
        $sourceType = $params['source_type'] ?? '';
        $groupBy = $params['group_by'] ?? 'day';

        // Comparativa: mismos N días inmediatos antes
        $days = (int) ((strtotime($hasta) - strtotime($desde)) / 86400) + 1;
        $prevHasta = date('Y-m-d', strtotime($desde . ' -1 day'));
        $prevDesde = date('Y-m-d', strtotime($prevHasta . ' -' . ($days - 1) . ' days'));

        // Categorías (período actual)
        $byCategory = $this->fetchByCategory($desde, $hasta, $storeId, $sourceType);
        $totalIngreso = array_sum(array_column($byCategory, 'ingreso'));
        $totalEgreso  = array_sum(array_column($byCategory, 'egreso'));
        $totalMovs    = array_sum(array_column($byCategory, 'num_movements'));
        $totalNeto    = $totalIngreso - $totalEgreso;

        // Período anterior (para delta del KPI)
        $prevAgg = $this->fetchAggregate($prevDesde, $prevHasta, $storeId, $sourceType);
        $prevNeto = $prevAgg['ingreso'] - $prevAgg['egreso'];

        // Saldos por cuenta: apertura + flujos del período + cierre
        $accounts = $this->fetchAccountBalances($desde, $hasta, $storeId, $sourceType);
        $totalOpening = array_sum(array_column($accounts, 'opening_balance'));
        $totalClosing = array_sum(array_column($accounts, 'closing_balance'));

        $kpis = [
            'opening'    => $totalOpening,
            'ingresos'   => $totalIngreso,
            'egresos'    => $totalEgreso,
            'neto'       => $totalNeto,
            'closing'    => $totalClosing,
            'num_movs'   => $totalMovs,
            'prev_neto'  => $prevNeto,
            'prev_label' => date('d/m', strtotime($prevDesde)) . ' – ' . date('d/m', strtotime($prevHasta)),
            'growth_pct' => $prevNeto != 0 ? (($totalNeto - $prevNeto) / abs($prevNeto)) * 100 : null,
        ];

        // Timeline + neto acumulado
        $timeline = $this->fetchTimeline($desde, $hasta, $storeId, $sourceType, $groupBy);
        $running = 0;
        foreach ($timeline as &$b) {
            $b['neto'] = $b['ingreso'] - $b['egreso'];
            $running += $b['neto'];
            $b['cumulative'] = $running;
        }
        unset($b);

        // Clasificacion contable (Odoo IAS 7 style)
        $classified = $this->classifyByCategory($byCategory);

        // Drill-down por categoría: conceptos individuales dentro de cada
        // categoría agregada (aparecen al expandir la fila correspondiente)
        $categoryDrilldown = $this->fetchCategoryDrilldown($desde, $hasta, $storeId, $sourceType, 10);

        $totals = [
            'category'      => 'TOTAL',
            'num_movements' => $totalMovs,
            'ingreso'       => $totalIngreso,
            'egreso'        => $totalEgreso,
            'neto'          => $totalNeto,
        ];

        return [
            'kpis'          => $kpis,
            'accounts'      => $accounts,
            'timeline'      => $timeline,
            'by_category'   => $byCategory,
            'classified'    => $classified,
            'category_drilldown' => $categoryDrilldown,
            'group_by'      => $groupBy,
            'columns'       => $this->columns(),
            'rows'          => $byCategory,
            'totals'        => $totals,
            'period'        => ['desde' => $desde, 'hasta' => $hasta],
        ];
    }

    public function meta(array $params): array
    {
        return [
            'filename' => 'posicion_caja_' . ($params['desde'] ?? date('Y-m-01')) . '_' . ($params['hasta'] ?? date('Y-m-d')),
            'email_subject' => 'Posición de caja del período',
            'pdf_orientation' => 'L',
        ];
    }

    // ---------- Queries ---------- //

    /**
     * Lista de cuentas (cajas + bancos) con saldo inicial + flujos del período + saldo final.
     * Aplica filtro de bodega y source_type.
     *
     * Saldo inicial = initialBalance + sum(movs antes de $desde)
     * Saldo final = saldo_inicial + ingresos_periodo - egresos_periodo
     */
    private function fetchAccountBalances(string $desde, string $hasta, int $storeId, string $sourceType): array
    {
        $CI =& get_instance();
        $accounts = [];

        $needCajas  = ($sourceType === '' || $sourceType === 'caja');
        $needBancos = ($sourceType === '' || $sourceType === 'banco');

        if ($needCajas) {
            $where = "cb.deleted = 0";
            $args = [];
            if ($storeId) { $where .= " AND cb.storeId = ?"; $args[] = $storeId; }

            $rows = $CI->db->query("
                SELECT
                    cb.idCashbox AS source_id,
                    cb.name AS name,
                    cb.initialBalance AS initial_balance,
                    COALESCE(pre.net_pre, 0) AS net_pre,
                    COALESCE(per.ingreso_periodo, 0) AS ingreso_periodo,
                    COALESCE(per.egreso_periodo, 0)  AS egreso_periodo
                FROM cashboxes cb
                LEFT JOIN (
                    SELECT
                        sourceId,
                        SUM(CASE WHEN movementType IN ('ingreso','apertura') THEN amount ELSE 0 END)
                      - SUM(CASE WHEN movementType IN ('egreso','cierre','transferencia') THEN amount ELSE 0 END) AS net_pre
                    FROM cash_movements
                    WHERE deleted = 0 AND sourceType = 'caja' AND DATE(movementDate) < ?
                    GROUP BY sourceId
                ) pre ON pre.sourceId = cb.idCashbox
                LEFT JOIN (
                    SELECT
                        sourceId,
                        SUM(CASE WHEN movementType IN ('ingreso','apertura') THEN amount ELSE 0 END) AS ingreso_periodo,
                        SUM(CASE WHEN movementType IN ('egreso','cierre','transferencia') THEN amount ELSE 0 END) AS egreso_periodo
                    FROM cash_movements
                    WHERE deleted = 0 AND sourceType = 'caja' AND DATE(movementDate) BETWEEN ? AND ?
                    GROUP BY sourceId
                ) per ON per.sourceId = cb.idCashbox
                WHERE $where
                ORDER BY cb.name
            ", array_merge([$desde, $desde, $hasta], $args))->result_array();

            foreach ($rows as $r) {
                $opening = (float) $r['initial_balance'] + (float) $r['net_pre'];
                $in  = (float) $r['ingreso_periodo'];
                $out = (float) $r['egreso_periodo'];
                $closing = $opening + $in - $out;
                $accounts[] = [
                    'source_type'     => 'caja',
                    'source_id'       => (int) $r['source_id'],
                    'name'            => $r['name'],
                    'opening_balance' => $opening,
                    'period_in'       => $in,
                    'period_out'      => $out,
                    'closing_balance' => $closing,
                    'variation_pct'   => $opening != 0 ? (($closing - $opening) / abs($opening)) * 100 : null,
                ];
            }
        }

        if ($needBancos) {
            $where = "ba.deleted = 0";
            $args = [];
            if ($storeId) { $where .= " AND ba.storeId = ?"; $args[] = $storeId; }

            $rows = $CI->db->query("
                SELECT
                    ba.idBankAccount AS source_id,
                    CONCAT(ba.bankName, ' · ', RIGHT(ba.accountNumber, 4)) AS name,
                    ba.initialBalance AS initial_balance,
                    COALESCE(pre.net_pre, 0) AS net_pre,
                    COALESCE(per.ingreso_periodo, 0) AS ingreso_periodo,
                    COALESCE(per.egreso_periodo, 0)  AS egreso_periodo
                FROM bank_accounts ba
                LEFT JOIN (
                    SELECT
                        sourceId,
                        SUM(CASE WHEN movementType IN ('ingreso','apertura') THEN amount ELSE 0 END)
                      - SUM(CASE WHEN movementType IN ('egreso','cierre','transferencia') THEN amount ELSE 0 END) AS net_pre
                    FROM cash_movements
                    WHERE deleted = 0 AND sourceType = 'banco' AND DATE(movementDate) < ?
                    GROUP BY sourceId
                ) pre ON pre.sourceId = ba.idBankAccount
                LEFT JOIN (
                    SELECT
                        sourceId,
                        SUM(CASE WHEN movementType IN ('ingreso','apertura') THEN amount ELSE 0 END) AS ingreso_periodo,
                        SUM(CASE WHEN movementType IN ('egreso','cierre','transferencia') THEN amount ELSE 0 END) AS egreso_periodo
                    FROM cash_movements
                    WHERE deleted = 0 AND sourceType = 'banco' AND DATE(movementDate) BETWEEN ? AND ?
                    GROUP BY sourceId
                ) per ON per.sourceId = ba.idBankAccount
                WHERE $where
                ORDER BY ba.bankName
            ", array_merge([$desde, $desde, $hasta], $args))->result_array();

            foreach ($rows as $r) {
                $opening = (float) $r['initial_balance'] + (float) $r['net_pre'];
                $in  = (float) $r['ingreso_periodo'];
                $out = (float) $r['egreso_periodo'];
                $closing = $opening + $in - $out;
                $accounts[] = [
                    'source_type'     => 'banco',
                    'source_id'       => (int) $r['source_id'],
                    'name'            => $r['name'],
                    'opening_balance' => $opening,
                    'period_in'       => $in,
                    'period_out'      => $out,
                    'closing_balance' => $closing,
                    'variation_pct'   => $opening != 0 ? (($closing - $opening) / abs($opening)) * 100 : null,
                ];
            }
        }

        return $accounts;
    }

    /**
     * Clasifica las categorías en Operativo / Inversión / Financiamiento (IAS 7).
     */
    private function classifyByCategory(array $byCategory): array
    {
        // Mapping enum -> sección IAS 7
        // venta/pago_cliente/pago_proveedor/gasto/nomina/impuestos = Operativo
        // prestamo = Financiamiento
        // (no hay categoría de inversión en MAM hoy)
        $mapping = [
            'Venta'             => 'operativo',
            'Pago de Cliente'   => 'operativo',
            'Pago a Proveedor'  => 'operativo',
            'Gasto'             => 'operativo',
            'Nómina'            => 'operativo',
            'Impuestos'         => 'operativo',
            'Préstamo'          => 'financiamiento',
            'Otro'              => 'operativo', // por defecto operativo
        ];

        $sections = [
            'operativo'      => ['label' => 'Actividades Operativas',      'ingreso' => 0, 'egreso' => 0, 'neto' => 0],
            'inversion'      => ['label' => 'Actividades de Inversión',    'ingreso' => 0, 'egreso' => 0, 'neto' => 0],
            'financiamiento' => ['label' => 'Actividades de Financiamiento','ingreso' => 0, 'egreso' => 0, 'neto' => 0],
        ];

        foreach ($byCategory as $c) {
            $section = $mapping[$c['category']] ?? 'operativo';
            $sections[$section]['ingreso'] += $c['ingreso'];
            $sections[$section]['egreso']  += $c['egreso'];
            $sections[$section]['neto']    += $c['neto'];
        }

        return $sections;
    }

    /**
     * Suma agregada (ingreso/egreso/count) en un rango. Período comparativo.
     */
    private function fetchAggregate(string $desde, string $hasta, int $storeId, string $sourceType): array
    {
        $CI =& get_instance();
        [$where, $args] = $this->buildWhere($desde, $hasta, $storeId, $sourceType);

        $row = $CI->db->query("
            SELECT
                COALESCE(SUM(CASE WHEN cm.movementType IN ('ingreso','apertura') THEN cm.amount ELSE 0 END), 0) AS ingreso,
                COALESCE(SUM(CASE WHEN cm.movementType IN ('egreso','cierre','transferencia') THEN cm.amount ELSE 0 END), 0) AS egreso,
                COUNT(*) AS count
            FROM cash_movements cm
            WHERE $where
        ", $args)->row_array();

        return [
            'ingreso' => (float) ($row['ingreso'] ?? 0),
            'egreso'  => (float) ($row['egreso'] ?? 0),
            'count'   => (int) ($row['count'] ?? 0),
        ];
    }

    private function fetchByCategory(string $desde, string $hasta, int $storeId, string $sourceType): array
    {
        $CI =& get_instance();
        [$where, $args] = $this->buildWhere($desde, $hasta, $storeId, $sourceType);

        $rows = $CI->db->query("
            SELECT
                COALESCE(NULLIF(cm.category, ''), 'otro') AS category_key,
                COUNT(*) AS num_movements,
                SUM(CASE WHEN cm.movementType IN ('ingreso','apertura') THEN cm.amount ELSE 0 END) AS ingreso,
                SUM(CASE WHEN cm.movementType IN ('egreso','cierre','transferencia') THEN cm.amount ELSE 0 END) AS egreso
            FROM cash_movements cm
            WHERE $where
            GROUP BY category_key
        ", $args)->result_array();

        $result = [];
        foreach ($rows as $r) {
            $ingreso = (float) $r['ingreso'];
            $egreso  = (float) $r['egreso'];
            $result[] = [
                'category'      => $this->categoryLabel($r['category_key']),
                'num_movements' => (int) $r['num_movements'],
                'ingreso'       => $ingreso,
                'egreso'        => $egreso,
                'neto'          => $ingreso - $egreso,
            ];
        }
        usort($result, fn($a, $b) => abs($b['neto']) <=> abs($a['neto']));
        return $result;
    }

    /**
     * Drill-down por categoría: agrega cada categoría con sus top N conceptos.
     * El template los renderiza como filas expandibles (<details>).
     *
     * Output: array indexado por category_key con label + agregados + top N
     * movimientos individuales (fecha, concepto, monto, tipo).
     *
     * @return array<string, array>
     */
    private function fetchCategoryDrilldown(string $desde, string $hasta, int $storeId, string $sourceType, int $topPerCat): array
    {
        $CI =& get_instance();
        [$where, $args] = $this->buildWhere($desde, $hasta, $storeId, $sourceType);

        // Top movimientos ordenados por categoría + monto (todos en una query;
        // PHP corta a $topPerCat por categoría).
        $rows = $CI->db->query("
            SELECT
                COALESCE(NULLIF(cm.category, ''), 'otro') AS category_key,
                cm.movementType,
                DATE(cm.movementDate) AS fecha,
                COALESCE(NULLIF(cm.concept, ''), '(sin concepto)') AS concepto,
                cm.amount,
                cm.sourceType,
                cm.documentNumber
            FROM cash_movements cm
            WHERE $where
            ORDER BY category_key, cm.amount DESC
        ", $args)->result_array();

        $byCat = [];
        foreach ($rows as $r) {
            $key = $r['category_key'];
            if (!isset($byCat[$key])) {
                $byCat[$key] = ['top_movements' => []];
            }
            if (count($byCat[$key]['top_movements']) < $topPerCat) {
                $isIngreso = in_array($r['movementType'], ['ingreso', 'apertura'], true);
                $byCat[$key]['top_movements'][] = [
                    'fecha'           => $r['fecha'],
                    'concepto'        => $r['concepto'],
                    'amount'          => (float) $r['amount'],
                    'movement_type'   => $r['movementType'],
                    'source_type'     => $r['sourceType'],
                    'document_number' => $r['documentNumber'],
                    'is_ingreso'      => $isIngreso,
                ];
            }
        }
        return $byCat;
    }

    private function fetchTimeline(string $desde, string $hasta, int $storeId, string $sourceType, string $groupBy): array
    {
        $CI =& get_instance();
        [$where, $args] = $this->buildWhere($desde, $hasta, $storeId, $sourceType);
        $bucketExpr = $this->bucketExpr($groupBy);

        $rows = $CI->db->query("
            SELECT $bucketExpr AS bucket,
                   SUM(CASE WHEN cm.movementType IN ('ingreso','apertura') THEN cm.amount ELSE 0 END) AS ingreso,
                   SUM(CASE WHEN cm.movementType IN ('egreso','cierre','transferencia') THEN cm.amount ELSE 0 END) AS egreso,
                   COUNT(*) AS num_movements
            FROM cash_movements cm
            WHERE $where
            GROUP BY bucket ORDER BY bucket
        ", $args)->result_array();

        $idx = [];
        foreach ($rows as $r) { $idx[$r['bucket']] = $r; }

        $buckets = $this->generateBuckets($desde, $hasta, $groupBy);
        $timeline = [];
        foreach ($buckets as $b) {
            $r = $idx[$b['key']] ?? ['ingreso' => 0, 'egreso' => 0, 'num_movements' => 0];
            $timeline[] = [
                'key'           => $b['key'],
                'label'         => $b['label'],
                'ingreso'       => (float) $r['ingreso'],
                'egreso'        => (float) $r['egreso'],
                'num_movements' => (int) $r['num_movements'],
            ];
        }
        return $timeline;
    }

    /**
     * @return array{0: string, 1: array}
     */
    private function buildWhere(string $desde, string $hasta, int $storeId, string $sourceType): array
    {
        $where = "cm.deleted = 0 AND DATE(cm.movementDate) BETWEEN ? AND ?";
        $args = [$desde, $hasta];
        if ($storeId) {
            $where .= " AND ( (cm.sourceType = 'caja' AND cm.sourceId IN (SELECT idCashbox FROM cashboxes WHERE storeId = ?))"
                   .  "   OR (cm.sourceType = 'banco' AND cm.sourceId IN (SELECT idBankAccount FROM bank_accounts WHERE storeId = ?)) )";
            $args[] = $storeId;
            $args[] = $storeId;
        }
        if (in_array($sourceType, ['caja', 'banco'], true)) {
            $where .= " AND cm.sourceType = ?";
            $args[] = $sourceType;
        }
        return [$where, $args];
    }

    private function bucketExpr(string $groupBy): string
    {
        switch ($groupBy) {
            case 'week':  return "DATE_FORMAT(DATE_SUB(cm.movementDate, INTERVAL WEEKDAY(cm.movementDate) DAY), '%Y-%m-%d')";
            case 'month': return "DATE_FORMAT(cm.movementDate, '%Y-%m-01')";
            case 'day':
            default:      return "DATE(cm.movementDate)";
        }
    }

    private function generateBuckets(string $desde, string $hasta, string $groupBy): array
    {
        $buckets = [];
        $cursor = strtotime($desde);
        $end = strtotime($hasta);

        if ($groupBy === 'month') {
            $cursor = strtotime(date('Y-m-01', $cursor));
            while ($cursor <= $end) {
                $buckets[] = ['key' => date('Y-m-01', $cursor), 'label' => date('M Y', $cursor)];
                $cursor = strtotime('+1 month', $cursor);
            }
        } elseif ($groupBy === 'week') {
            $cursor = strtotime('monday this week', $cursor);
            while ($cursor <= $end) {
                $buckets[] = ['key' => date('Y-m-d', $cursor), 'label' => 'Sem ' . date('W', $cursor)];
                $cursor = strtotime('+1 week', $cursor);
            }
        } else {
            while ($cursor <= $end) {
                $buckets[] = ['key' => date('Y-m-d', $cursor), 'label' => date('d/m', $cursor)];
                $cursor = strtotime('+1 day', $cursor);
            }
        }
        return $buckets;
    }

    private function categoryLabel(string $key): string
    {
        $map = [
            'venta'           => 'Venta',
            'pago_cliente'    => 'Pago de Cliente',
            'pago_proveedor'  => 'Pago a Proveedor',
            'gasto'            => 'Gasto',
            'nomina'           => 'Nómina',
            'impuestos'        => 'Impuestos',
            'prestamo'         => 'Préstamo',
            'otro'             => 'Otro',
        ];
        return $map[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    private function columns(): array
    {
        return [
            ['key' => 'category',      'label' => 'Categoría',     'type' => 'text'],
            ['key' => 'num_movements', 'label' => '# Movimientos', 'type' => 'number'],
            ['key' => 'ingreso',       'label' => 'Ingreso',       'type' => 'currency'],
            ['key' => 'egreso',        'label' => 'Egreso',        'type' => 'currency'],
            ['key' => 'neto',          'label' => 'Neto',          'type' => 'currency'],
        ];
    }
}
