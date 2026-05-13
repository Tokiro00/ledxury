<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * BalanceSheet — Balance General (Estado de Situación Financiera).
 *
 * Ecuación fundamental: Activo = Pasivo + Patrimonio.
 *
 * Saldos ACUMULADOS hasta la fecha de corte (no de un período).
 *   - Clase 1 (Activos)    — natural DÉBITO
 *   - Clase 2 (Pasivos)    — natural CRÉDITO
 *   - Clase 3 (Patrimonio) — natural CRÉDITO
 *   - + Utilidad del ejercicio (Ingresos − Costos − Gastos hasta fecha corte)
 *     que se suma al Patrimonio.
 *
 * Si Activo ≠ Pasivo+Patrimonio → ecuación rota (alerta).
 */
class BalanceSheet extends AbstractReport
{
    public function id(): string { return 'balance_sheet'; }
    public function title(): string { return 'Balance General'; }
    public function description(): string
    {
        return 'Estado de Situación Financiera con Activo, Pasivo y Patrimonio a una fecha de corte. Incluye utilidad del ejercicio acumulada en Patrimonio.';
    }

    public function requiredRoles(): array { return [2, 4]; }

    public function filterDefinitions(): array
    {
        return [
            [
                'name'    => 'fecha_corte',
                'label'   => 'Fecha de corte',
                'type'    => 'date',
                'default' => date('Y-m-d'),
                'required'=> true,
            ],
            $this->storeFilterDefinition(),
            [
                'name'    => 'incluir_utilidad',
                'label'   => 'Utilidad del ejercicio',
                'type'    => 'select',
                'options' => ['1' => 'Incluir en patrimonio', '0' => 'No incluir'],
                'default' => '1',
            ],
        ];
    }

    public function availableChannels(): array { return ['email', 'schedule']; }

    public function data(array $params): array
    {
        $params         = $this->applyFilterDefaults($params);
        $fecha          = $params['fecha_corte'];
        $storeId        = (int) ($params['store_id'] ?? 0);
        $incluirUtilidad= !empty($params['incluir_utilidad']);

        $CI =& get_instance();

        // Saldos acumulados hasta fecha de corte (todas las clases 1-6)
        $movements = $this->_getAccumulated($CI, $fecha, $storeId);

        // Separar por clase
        $tree = ['1' => null, '2' => null, '3' => null];
        $resultadoEjercicio = 0;  // ingresos - costos - gastos

        foreach ($movements as $r) {
            $first = substr($r['pucCode'], 0, 1);
            if (in_array($first, ['1','2','3'], true)) {
                $cls = $first;
                $grp = substr($r['pucCode'], 0, 2);
                if (!isset($tree[$cls])) $tree[$cls] = ['saldo' => 0, 'groups' => []];
                if (!isset($tree[$cls]['groups'][$grp])) {
                    $tree[$cls]['groups'][$grp] = ['name' => $this->_groupName($grp), 'saldo' => 0, 'subaccounts' => []];
                }
                $tree[$cls]['saldo']                       += $r['saldo'];
                $tree[$cls]['groups'][$grp]['saldo']       += $r['saldo'];
                $tree[$cls]['groups'][$grp]['subaccounts'][] = [
                    'pucCode' => $r['pucCode'],
                    'name'    => $r['accountName'],
                    'saldo'   => $r['saldo'],
                ];
            }
            // Calcular resultado del ejercicio para sumarlo al patrimonio
            if ($first === '4') $resultadoEjercicio += $r['saldo'];  // ingresos suman
            if ($first === '5') $resultadoEjercicio -= $r['saldo'];  // gastos restan
            if ($first === '6') $resultadoEjercicio -= $r['saldo'];  // costos restan
        }

        // Sort groups
        foreach ($tree as $cls => &$cd) {
            if ($cd === null) continue;
            ksort($cd['groups']);
            foreach ($cd['groups'] as &$g) {
                usort($g['subaccounts'], fn($a, $b) => strcmp($a['pucCode'], $b['pucCode']));
            }
        }
        unset($cd, $g);

        // Inicializar clases vacías para evitar nulls en el template
        foreach (['1','2','3'] as $c) {
            if ($tree[$c] === null) $tree[$c] = ['saldo' => 0, 'groups' => []];
        }

        $totalActivo = $tree['1']['saldo'];
        $totalPasivo = $tree['2']['saldo'];
        $totalPatrimonio = $tree['3']['saldo'];
        if ($incluirUtilidad) {
            $totalPatrimonio += $resultadoEjercicio;
        }
        $totalPasivoPat = $totalPasivo + $totalPatrimonio;
        $diferencia = $totalActivo - $totalPasivoPat;
        $cuadrado = abs($diferencia) < 1.0;

        $kpis = [
            'total_activo'        => $totalActivo,
            'total_pasivo'        => $totalPasivo,
            'total_patrimonio'    => $totalPatrimonio,
            'total_pasivo_pat'    => $totalPasivoPat,
            'resultado_ejercicio' => $resultadoEjercicio,
            'diferencia'          => $diferencia,
            'cuadrado'            => $cuadrado,
            'incluir_utilidad'    => $incluirUtilidad,
            'fecha_corte'         => $fecha,
        ];

        // Filas planas para export
        $rows = [];
        foreach ($movements as $r) {
            $first = substr($r['pucCode'], 0, 1);
            if (!in_array($first, ['1','2','3'], true)) continue;
            $rows[] = [
                'pucCode'  => $r['pucCode'],
                'cuenta'   => $r['accountName'],
                'clase'    => $this->_className($first),
                'grupo'    => $this->_groupName(substr($r['pucCode'], 0, 2)),
                'debit'    => $r['debit'],
                'credit'   => $r['credit'],
                'saldo'    => $r['saldo'],
            ];
        }

        return [
            'kpis'    => $kpis,
            'tree'    => $tree,
            'columns' => [
                ['key' => 'pucCode', 'label' => 'Código', 'type' => 'text'],
                ['key' => 'cuenta',  'label' => 'Cuenta', 'type' => 'text'],
                ['key' => 'clase',   'label' => 'Clase',  'type' => 'text'],
                ['key' => 'grupo',   'label' => 'Grupo',  'type' => 'text'],
                ['key' => 'saldo',   'label' => 'Saldo',  'type' => 'currency'],
            ],
            'rows'    => $rows,
            'totals'  => null,
        ];
    }

    /**
     * Saldos acumulados de TODAS las subcuentas hasta la fecha de corte.
     */
    private function _getAccumulated($CI, $fecha, $storeId)
    {
        $args = [$fecha];
        $storeSql = '';
        if ($storeId > 0) {
            $storeSql = ' AND (e.entryStoreId = ? OR e.entryStoreId IS NULL)';
            $args[] = $storeId;
        }

        // FIX v2: entryDate viene NULL en prod (la fecha real está en
        // entryCreateDate). pucCode también NULL en muchas subaccounts pero
        // accountID contiene el código PUC. Patches via COALESCE.
        $sql = "
            SELECT
                s.id,
                COALESCE(NULLIF(s.pucCode, ''), CAST(s.accountID AS CHAR)) AS pucCode,
                s.accountName,
                s.accountSide,
                COALESCE(SUM(CASE WHEN e.entryDebitAccount  = s.id THEN CAST(e.entryDebitBalance AS DECIMAL(15,2))  ELSE 0 END), 0) AS debit,
                COALESCE(SUM(CASE WHEN e.entryCreditAccount = s.id THEN CAST(e.entryCreditBalance AS DECIMAL(15,2)) ELSE 0 END), 0) AS credit
            FROM subaccounts s
            LEFT JOIN entries e ON (e.entryDebitAccount = s.id OR e.entryCreditAccount = s.id)
                                AND e.deleted = 0
                                AND COALESCE(e.entryDate, DATE(e.entryCreateDate)) <= ?
                                $storeSql
            WHERE s.deleted = 0
              AND COALESCE(NULLIF(s.pucCode, ''), CAST(s.accountID AS CHAR)) IS NOT NULL
            GROUP BY s.id
            HAVING debit > 0 OR credit > 0
        ";
        $rows = $CI->db->query($sql, $args)->result_array();

        foreach ($rows as &$r) {
            $r['debit']  = (float) $r['debit'];
            $r['credit'] = (float) $r['credit'];
            $first = substr($r['pucCode'], 0, 1);
            // Activos (1), Gastos (5), Costos (6) — natural débito → debit - credit
            // Pasivos (2), Patrimonio (3), Ingresos (4) — natural crédito → credit - debit
            if (in_array($first, ['1','5','6'], true)) {
                $r['saldo'] = $r['debit'] - $r['credit'];
            } else {
                $r['saldo'] = $r['credit'] - $r['debit'];
            }
        }
        unset($r);
        return $rows;
    }

    private function _className(string $c): string
    {
        $map = ['1'=>'ACTIVO','2'=>'PASIVO','3'=>'PATRIMONIO','4'=>'INGRESOS','5'=>'GASTOS','6'=>'COSTOS'];
        return $map[$c] ?? 'Clase ' . $c;
    }

    private function _groupName(string $c): string
    {
        $map = [
            '11'=>'Disponible','12'=>'Inversiones','13'=>'Deudores','14'=>'Inventarios','15'=>'Propiedades, planta y equipo','16'=>'Intangibles','17'=>'Diferidos','18'=>'Otros activos','19'=>'Valorizaciones',
            '21'=>'Obligaciones financieras','22'=>'Proveedores','23'=>'Cuentas por pagar','24'=>'Impuestos','25'=>'Obligaciones laborales','26'=>'Pasivos estimados','27'=>'Diferidos','28'=>'Otros pasivos','29'=>'Bonos',
            '31'=>'Capital social','32'=>'Superávit de capital','33'=>'Reservas','34'=>'Revalorización del patrimonio','36'=>'Resultados del ejercicio','37'=>'Resultados ejercicios anteriores','38'=>'Superávit por valorizaciones',
        ];
        return $map[$c] ?? 'Grupo ' . $c;
    }

    public function meta(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        return [
            'filename'        => 'balance_general_' . $params['fecha_corte'],
            'email_subject'   => 'Balance General al ' . $params['fecha_corte'],
            'pdf_orientation' => 'P',
        ];
    }
}
