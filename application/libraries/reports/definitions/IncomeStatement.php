<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * IncomeStatement — Estado de Resultados (P&L).
 *
 * Estructura PUC Colombia:
 *   - Clase 4 (Ingresos) — saldo natural CRÉDITO
 *   - Clase 5 (Gastos)   — saldo natural DÉBITO
 *   - Clase 6 (Costos)   — saldo natural DÉBITO
 *
 * Cálculos:
 *   Utilidad Bruta       = Ingresos (4) − Costos (6)
 *   Utilidad Operacional = U.Bruta − Gastos op. (51, 52)
 *   Utilidad Neta        = U.Operacional − Gastos no op. (53) − Impuestos (54)
 *
 * Cohort: período de movimientos (no acumulado histórico). Compara vs período
 * anterior de igual duración cuando `compare_prior = true`.
 */
class IncomeStatement extends AbstractReport
{
    public function id(): string { return 'income_statement'; }
    public function title(): string { return 'Estado de Resultados'; }
    public function description(): string
    {
        return 'P&L con ingresos, costos, gastos y utilidades por período. Drill-down jerárquico (clase → grupo → subcuenta) con comparativo vs período anterior.';
    }

    public function requiredRoles(): array { return [2, 4]; }

    public function filterDefinitions(): array
    {
        return [
            ['name' => 'date_range', 'type' => 'date_range'],
            ['name' => 'desde', 'label' => 'Desde', 'type' => 'date', 'default' => date('Y-m-01')],
            ['name' => 'hasta', 'label' => 'Hasta', 'type' => 'date', 'default' => date('Y-m-d')],
            $this->storeFilterDefinition(),
            [
                'name'    => 'compare_prior',
                'label'   => 'Comparativo',
                'type'    => 'select',
                'options' => ['0' => 'Sin comparativo', '1' => 'vs Período anterior'],
                'default' => '1',
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
        $compare = !empty($params['compare_prior']);

        $CI =& get_instance();

        // Período anterior: igual cantidad de días terminando antes del desde
        $days = max(1, (strtotime($hasta) - strtotime($desde)) / 86400 + 1);
        $priorDesde = date('Y-m-d', strtotime($desde) - $days * 86400);
        $priorHasta = date('Y-m-d', strtotime($desde) - 86400);

        // Movimientos del período actual + anterior por subcuenta (solo clases 4,5,6)
        $current = $this->_getMovements($CI, $desde, $hasta, $storeId);
        $prior   = $compare ? $this->_getMovements($CI, $priorDesde, $priorHasta, $storeId) : [];

        // Agrupar por clase y grupo
        $groupsByClass = $this->_groupTree($current, $prior);

        // KPIs principales
        $ingresos = $groupsByClass['4']['saldo'] ?? 0;
        $costos   = $groupsByClass['6']['saldo'] ?? 0;
        $gastos51 = $groupsByClass['5']['groups']['51']['saldo'] ?? 0;
        $gastos52 = $groupsByClass['5']['groups']['52']['saldo'] ?? 0;
        $gastos53 = $groupsByClass['5']['groups']['53']['saldo'] ?? 0;
        $impuestos = $groupsByClass['5']['groups']['54']['saldo'] ?? 0;
        $gastosOp = $gastos51 + $gastos52;
        $gastosNoOp = $gastos53;

        $utilidadBruta = $ingresos - $costos;
        $utilidadOp    = $utilidadBruta - $gastosOp;
        $utilidadNeta  = $utilidadOp - $gastosNoOp - $impuestos;

        // Prior period KPIs para comparativo
        $ingresosP = 0; $costosP = 0; $utNetaP = 0;
        if ($compare) {
            $groupsP = $this->_groupTree($prior, []);
            $ingresosP = $groupsP['4']['saldo'] ?? 0;
            $costosP   = $groupsP['6']['saldo'] ?? 0;
            $g51 = $groupsP['5']['groups']['51']['saldo'] ?? 0;
            $g52 = $groupsP['5']['groups']['52']['saldo'] ?? 0;
            $g53 = $groupsP['5']['groups']['53']['saldo'] ?? 0;
            $imp = $groupsP['5']['groups']['54']['saldo'] ?? 0;
            $utNetaP = ($ingresosP - $costosP) - ($g51 + $g52) - $g53 - $imp;
        }

        $kpis = [
            'ingresos'         => $ingresos,
            'ingresos_prior'   => $ingresosP,
            'utilidad_bruta'   => $utilidadBruta,
            'margen_bruto_pct' => $ingresos > 0 ? round(($utilidadBruta / $ingresos) * 100, 1) : 0,
            'utilidad_op'      => $utilidadOp,
            'margen_op_pct'    => $ingresos > 0 ? round(($utilidadOp / $ingresos) * 100, 1) : 0,
            'utilidad_neta'    => $utilidadNeta,
            'utilidad_neta_prior' => $utNetaP,
            'margen_neto_pct'  => $ingresos > 0 ? round(($utilidadNeta / $ingresos) * 100, 1) : 0,
            'compare'          => $compare,
            'prior_desde'      => $priorDesde,
            'prior_hasta'      => $priorHasta,
        ];

        // Estructura jerárquica para template
        $sections = [
            [
                'title'   => 'INGRESOS',
                'classes' => ['4'],
                'sign'    => 1,   // suma
            ],
            [
                'title'   => 'COSTOS DE VENTAS',
                'classes' => ['6'],
                'sign'    => -1,  // resta
            ],
            [
                'title'   => 'GASTOS OPERACIONALES',
                'classes' => ['5'],
                'groups_filter' => ['51', '52'],
                'sign'    => -1,
            ],
            [
                'title'   => 'GASTOS NO OPERACIONALES',
                'classes' => ['5'],
                'groups_filter' => ['53'],
                'sign'    => -1,
            ],
            [
                'title'   => 'IMPUESTOS',
                'classes' => ['5'],
                'groups_filter' => ['54', '59'],
                'sign'    => -1,
            ],
        ];

        // Build flat rows array para export (cada subcuenta una fila)
        $rows = [];
        foreach ($current as $r) {
            $r['name_clase'] = $this->_className(substr($r['pucCode'], 0, 1));
            $r['name_grupo'] = $this->_groupName(substr($r['pucCode'], 0, 2));
            $rows[] = $r;
        }

        return [
            'kpis'           => $kpis,
            'groups_by_class' => $groupsByClass,
            'sections'       => $sections,
            'utilidad_bruta' => $utilidadBruta,
            'utilidad_op'    => $utilidadOp,
            'utilidad_neta'  => $utilidadNeta,
            'columns'        => $this->_columns($compare),
            'rows'           => $rows,
            'totals'         => null,
        ];
    }

    /**
     * Query: movimientos por subcuenta en un rango. Solo clases 4, 5, 6
     * (Estado de Resultados). Cohort = entries cuyo entryDate cae en [desde, hasta].
     */
    private function _getMovements($CI, $desde, $hasta, $storeId)
    {
        $args = [$desde, $hasta];
        $storeSql = '';
        if ($storeId > 0) {
            $storeSql = ' AND (e.entryStoreId = ? OR e.entryStoreId IS NULL)';
            $args[] = $storeId;
        }

        // FIX v2: dos parches críticos basados en estructura real de prod:
        //   1. entryDate es NULL en TODOS los entries — la fecha real está en
        //      entryCreateDate. Usamos COALESCE para tolerar ambos casos.
        //   2. Muchas subaccounts tienen pucCode=NULL pero accountID contiene
        //      el PUC real (ej. id=12: pucCode=NULL, accountID=110505). Usamos
        //      effective_puc = COALESCE(pucCode, accountID).
        $sql = "
            SELECT
                s.id AS subaccount_id,
                COALESCE(NULLIF(s.pucCode, ''), CAST(s.accountID AS CHAR)) AS pucCode,
                s.accountName,
                s.accountSide,
                COALESCE(SUM(CASE WHEN e.entryDebitAccount  = s.id THEN CAST(e.entryDebitBalance AS DECIMAL(15,2))  ELSE 0 END), 0) AS debit,
                COALESCE(SUM(CASE WHEN e.entryCreditAccount = s.id THEN CAST(e.entryCreditBalance AS DECIMAL(15,2)) ELSE 0 END), 0) AS credit
            FROM subaccounts s
            LEFT JOIN entries e ON (e.entryDebitAccount = s.id OR e.entryCreditAccount = s.id)
                                AND e.deleted = 0
                                AND COALESCE(e.entryDate, DATE(e.entryCreateDate)) BETWEEN ? AND ?
                                $storeSql
            WHERE s.deleted = 0
              AND COALESCE(NULLIF(s.pucCode, ''), CAST(s.accountID AS CHAR)) IS NOT NULL
              AND LEFT(COALESCE(NULLIF(s.pucCode, ''), CAST(s.accountID AS CHAR)), 1) IN ('4', '5', '6')
            GROUP BY s.id
            HAVING debit > 0 OR credit > 0
        ";
        $rows = $CI->db->query($sql, $args)->result_array();

        // Calcular saldo según naturaleza de la cuenta (primer dígito PUC)
        foreach ($rows as &$r) {
            $r['debit']  = (float) $r['debit'];
            $r['credit'] = (float) $r['credit'];
            $first = substr($r['pucCode'], 0, 1);
            if ($first === '4') {
                $r['saldo'] = $r['credit'] - $r['debit'];
            } else {
                $r['saldo'] = $r['debit'] - $r['credit'];
            }
        }
        unset($r);
        return $rows;
    }

    /**
     * Agrupa filas planas en estructura: class → group → subcuentas[].
     * Suma saldos de período actual + anterior.
     */
    private function _groupTree(array $rows, array $priorRows)
    {
        $priorByPuc = [];
        foreach ($priorRows as $p) $priorByPuc[$p['pucCode']] = (float)$p['saldo'];

        $tree = [];
        foreach ($rows as $r) {
            $cls = substr($r['pucCode'], 0, 1);
            $grp = substr($r['pucCode'], 0, 2);
            $saldoPrior = isset($priorByPuc[$r['pucCode']]) ? $priorByPuc[$r['pucCode']] : 0;

            if (!isset($tree[$cls])) {
                $tree[$cls] = ['saldo' => 0, 'saldo_prior' => 0, 'groups' => []];
            }
            if (!isset($tree[$cls]['groups'][$grp])) {
                $tree[$cls]['groups'][$grp] = [
                    'name'        => $this->_groupName($grp),
                    'saldo'       => 0,
                    'saldo_prior' => 0,
                    'subaccounts' => [],
                ];
            }

            $tree[$cls]['saldo']                              += $r['saldo'];
            $tree[$cls]['saldo_prior']                        += $saldoPrior;
            $tree[$cls]['groups'][$grp]['saldo']              += $r['saldo'];
            $tree[$cls]['groups'][$grp]['saldo_prior']        += $saldoPrior;
            $tree[$cls]['groups'][$grp]['subaccounts'][] = [
                'pucCode'     => $r['pucCode'],
                'name'        => $r['accountName'],
                'saldo'       => $r['saldo'],
                'saldo_prior' => $saldoPrior,
                'debit'       => $r['debit'],
                'credit'      => $r['credit'],
            ];
        }

        // Ordenar grupos dentro de cada clase
        foreach ($tree as &$cls) {
            ksort($cls['groups']);
            foreach ($cls['groups'] as &$g) {
                usort($g['subaccounts'], fn($a, $b) => strcmp($a['pucCode'], $b['pucCode']));
            }
        }
        return $tree;
    }

    private function _className(string $code): string
    {
        $map = ['1'=>'ACTIVO','2'=>'PASIVO','3'=>'PATRIMONIO','4'=>'INGRESOS','5'=>'GASTOS','6'=>'COSTOS DE VENTAS','7'=>'COSTOS DE PRODUCCIÓN'];
        return $map[$code] ?? 'Clase ' . $code;
    }

    private function _groupName(string $code): string
    {
        // Catálogo PUC Colombia (estándar). Si falta uno, retorna el código.
        $map = [
            '11'=>'Disponible','12'=>'Inversiones','13'=>'Deudores','14'=>'Inventarios','15'=>'Propiedades, planta y equipo','16'=>'Intangibles','17'=>'Diferidos','18'=>'Otros activos','19'=>'Valorizaciones',
            '21'=>'Obligaciones financieras','22'=>'Proveedores','23'=>'Cuentas por pagar','24'=>'Impuestos, gravámenes y tasas','25'=>'Obligaciones laborales','26'=>'Pasivos estimados','27'=>'Diferidos','28'=>'Otros pasivos','29'=>'Bonos y papeles comerciales',
            '31'=>'Capital social','32'=>'Superávit de capital','33'=>'Reservas','34'=>'Revalorización del patrimonio','36'=>'Resultados del ejercicio','37'=>'Resultados de ejercicios anteriores','38'=>'Superávit por valorizaciones',
            '41'=>'Ingresos operacionales','42'=>'Ingresos no operacionales',
            '51'=>'Gastos op. de administración','52'=>'Gastos op. de ventas','53'=>'Gastos no operacionales','54'=>'Impuesto de renta','59'=>'Ganancias y pérdidas',
            '61'=>'Costo de ventas','62'=>'Compras','71'=>'Materia prima','72'=>'Mano de obra','73'=>'Costos indirectos','74'=>'Contratos de servicios',
        ];
        return $map[$code] ?? 'Grupo ' . $code;
    }

    private function _columns(bool $compare): array
    {
        $cols = [
            ['key' => 'pucCode', 'label' => 'Código', 'type' => 'text'],
            ['key' => 'accountName', 'label' => 'Cuenta', 'type' => 'text'],
            ['key' => 'name_grupo', 'label' => 'Grupo', 'type' => 'text'],
            ['key' => 'name_clase', 'label' => 'Clase', 'type' => 'text'],
            ['key' => 'debit', 'label' => 'Débitos', 'type' => 'currency'],
            ['key' => 'credit', 'label' => 'Créditos', 'type' => 'currency'],
            ['key' => 'saldo', 'label' => 'Saldo', 'type' => 'currency'],
        ];
        return $cols;
    }

    public function meta(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        return [
            'filename'        => 'estado_resultados_' . $params['desde'] . '_' . $params['hasta'],
            'email_subject'   => 'Estado de Resultados · ' . $params['desde'] . ' al ' . $params['hasta'],
            'pdf_orientation' => 'P',
        ];
    }
}
