<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * TrialBalance — Balance de Comprobación (Trial Balance).
 *
 * Lista TODAS las subcuentas con movimiento en el período mostrando:
 *   Saldo inicial | Débitos del período | Créditos del período | Saldo final
 *
 * Validador: Σ Débitos del período = Σ Créditos del período (siempre, por
 * partida doble). Si no cuadra, hay un asiento descuadrado en la BD.
 *
 * Equivalente a "Trial Balance" en SAP B1 / Odoo. Es el reporte de auditoría
 * por excelencia — útil para detectar errores antes del cierre.
 */
class TrialBalance extends AbstractReport
{
    public function id(): string { return 'trial_balance'; }
    public function title(): string { return 'Balance de Comprobación'; }
    public function description(): string
    {
        return 'Listado completo de subcuentas con saldo inicial, débitos, créditos y saldo final del período. Validador de partida doble. Exportable a Excel para el contador.';
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
                'name'    => 'clase',
                'label'   => 'Clase',
                'type'    => 'select',
                'options' => [
                    'all' => 'Todas',
                    '1'   => '1 - Activo',
                    '2'   => '2 - Pasivo',
                    '3'   => '3 - Patrimonio',
                    '4'   => '4 - Ingresos',
                    '5'   => '5 - Gastos',
                    '6'   => '6 - Costos',
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
        $clase   = $params['clase'] ?? 'all';

        $CI =& get_instance();

        $args = [$desde, $desde, $hasta];   // saldo inicial < desde / movimientos del período
        $storeSqlInit = ''; $storeSqlPer = '';
        if ($storeId > 0) {
            $storeSqlInit = ' AND (e1.entryStoreId = ? OR e1.entryStoreId IS NULL)';
            $storeSqlPer  = ' AND (e2.entryStoreId = ? OR e2.entryStoreId IS NULL)';
            // Reordenar args: desde, store, desde, hasta, store
            $args = [$desde, $storeId, $desde, $hasta, $storeId];
        }

        $claseFilter = '';
        if ($clase !== 'all' && in_array($clase, ['1','2','3','4','5','6'], true)) {
            $claseFilter = " AND LEFT(s.pucCode, 1) = '" . $clase . "'";
        }

        // Una sola query que calcula saldo inicial + débitos período + créditos período por subcuenta.
        // Las dos subqueries correlacionadas son por simplicidad (vs UNION + GROUP); con índice en
        // entryDebitAccount/entryCreditAccount + entryDate son rápidas.
        // FIX v2: entryDate viene NULL en prod → usar COALESCE con
        // entryCreateDate. pucCode NULL en muchas subaccounts → fallback a
        // accountID que sí tiene el código PUC.
        $effPuc = "COALESCE(NULLIF(s.pucCode, ''), CAST(s.accountID AS CHAR))";
        $sql = "
            SELECT
                s.id,
                $effPuc AS pucCode,
                s.accountName,
                s.accountSide,
                COALESCE((SELECT SUM(CAST(e1.entryDebitBalance AS DECIMAL(15,2)))
                          FROM entries e1
                         WHERE e1.entryDebitAccount = s.id AND e1.deleted = 0
                           AND COALESCE(e1.entryDate, DATE(e1.entryCreateDate)) < ?
                         $storeSqlInit), 0) AS debit_initial,
                COALESCE((SELECT SUM(CAST(e1.entryCreditBalance AS DECIMAL(15,2)))
                          FROM entries e1
                         WHERE e1.entryCreditAccount = s.id AND e1.deleted = 0
                           AND COALESCE(e1.entryDate, DATE(e1.entryCreateDate)) < ?
                         " . ($storeId > 0 ? ' AND (e1.entryStoreId = ? OR e1.entryStoreId IS NULL)' : '') . "), 0) AS credit_initial,
                COALESCE((SELECT SUM(CAST(e2.entryDebitBalance AS DECIMAL(15,2)))
                          FROM entries e2
                         WHERE e2.entryDebitAccount = s.id AND e2.deleted = 0
                           AND COALESCE(e2.entryDate, DATE(e2.entryCreateDate)) BETWEEN ? AND ?
                         $storeSqlPer), 0) AS debit_period,
                COALESCE((SELECT SUM(CAST(e2.entryCreditBalance AS DECIMAL(15,2)))
                          FROM entries e2
                         WHERE e2.entryCreditAccount = s.id AND e2.deleted = 0
                           AND COALESCE(e2.entryDate, DATE(e2.entryCreateDate)) BETWEEN ? AND ?
                         " . ($storeId > 0 ? ' AND (e2.entryStoreId = ? OR e2.entryStoreId IS NULL)' : '') . "), 0) AS credit_period
            FROM subaccounts s
            WHERE s.deleted = 0
              AND $effPuc IS NOT NULL
              $claseFilter
            HAVING debit_initial + credit_initial + debit_period + credit_period > 0
            ORDER BY pucCode ASC
        ";

        // Re-armar args correctamente
        if ($storeId > 0) {
            $args = [
                $desde, $storeId,         // debit_initial
                $desde, $storeId,         // credit_initial
                $desde, $hasta, $storeId, // debit_period
                $desde, $hasta, $storeId, // credit_period
            ];
        } else {
            $args = [
                $desde,                   // debit_initial
                $desde,                   // credit_initial
                $desde, $hasta,           // debit_period
                $desde, $hasta,           // credit_period
            ];
        }

        $rows = $CI->db->query($sql, $args)->result_array();

        // Procesar cada fila: calcular saldos
        $totalDebInit = 0; $totalCredInit = 0;
        $totalDebPer  = 0; $totalCredPer  = 0;
        $totalDebFin  = 0; $totalCredFin  = 0;

        foreach ($rows as &$r) {
            $r['debit_initial']  = (float) $r['debit_initial'];
            $r['credit_initial'] = (float) $r['credit_initial'];
            $r['debit_period']   = (float) $r['debit_period'];
            $r['credit_period']  = (float) $r['credit_period'];

            $first = substr($r['pucCode'], 0, 1);
            $isDebitNature = in_array($first, ['1','5','6'], true);

            // Saldo inicial firmado
            if ($isDebitNature) {
                $r['saldo_inicial'] = $r['debit_initial'] - $r['credit_initial'];
            } else {
                $r['saldo_inicial'] = $r['credit_initial'] - $r['debit_initial'];
            }

            // Saldo final: inicial + movimiento neto del período
            $movNeto = $isDebitNature
                ? ($r['debit_period'] - $r['credit_period'])
                : ($r['credit_period'] - $r['debit_period']);
            $r['saldo_final'] = $r['saldo_inicial'] + $movNeto;

            // Separar saldo final en débito/crédito (formato típico contador)
            if ($r['saldo_final'] >= 0) {
                $r['saldo_debito_final']  = $isDebitNature ? $r['saldo_final'] : 0;
                $r['saldo_credito_final'] = $isDebitNature ? 0 : $r['saldo_final'];
            } else {
                $r['saldo_debito_final']  = $isDebitNature ? 0 : abs($r['saldo_final']);
                $r['saldo_credito_final'] = $isDebitNature ? abs($r['saldo_final']) : 0;
            }

            $r['clase'] = $first;
            $r['nombre_clase'] = $this->_className($first);

            $totalDebInit  += $r['debit_initial'];
            $totalCredInit += $r['credit_initial'];
            $totalDebPer   += $r['debit_period'];
            $totalCredPer  += $r['credit_period'];
            $totalDebFin   += $r['saldo_debito_final'];
            $totalCredFin  += $r['saldo_credito_final'];
        }
        unset($r);

        // Partida doble: Σ Débitos período debería = Σ Créditos período
        $partidaDoble = abs($totalDebPer - $totalCredPer) < 1.0;
        $diferenciaPD = $totalDebPer - $totalCredPer;

        $kpis = [
            'total_subaccounts' => count($rows),
            'total_debit_period' => $totalDebPer,
            'total_credit_period' => $totalCredPer,
            'partida_doble_ok' => $partidaDoble,
            'diferencia_partida_doble' => $diferenciaPD,
            'total_debit_final' => $totalDebFin,
            'total_credit_final' => $totalCredFin,
            'cuadre_final' => abs($totalDebFin - $totalCredFin) < 1.0,
        ];

        return [
            'kpis'    => $kpis,
            'columns' => [
                ['key' => 'pucCode',             'label' => 'Código',          'type' => 'text'],
                ['key' => 'accountName',         'label' => 'Cuenta',          'type' => 'text'],
                ['key' => 'nombre_clase',        'label' => 'Clase',           'type' => 'text'],
                ['key' => 'saldo_inicial',       'label' => 'Saldo inicial',   'type' => 'currency'],
                ['key' => 'debit_period',        'label' => 'Débitos',         'type' => 'currency'],
                ['key' => 'credit_period',       'label' => 'Créditos',        'type' => 'currency'],
                ['key' => 'saldo_debito_final',  'label' => 'Saldo D final',   'type' => 'currency'],
                ['key' => 'saldo_credito_final', 'label' => 'Saldo C final',   'type' => 'currency'],
            ],
            'rows'    => $rows,
            'totals'  => [
                'pucCode' => 'TOTALES',
                'accountName' => count($rows) . ' subcuentas',
                'nombre_clase' => '',
                'saldo_inicial' => $totalDebInit - $totalCredInit,
                'debit_period' => $totalDebPer,
                'credit_period' => $totalCredPer,
                'saldo_debito_final' => $totalDebFin,
                'saldo_credito_final' => $totalCredFin,
            ],
        ];
    }

    private function _className(string $c): string
    {
        $map = ['1'=>'Activo','2'=>'Pasivo','3'=>'Patrimonio','4'=>'Ingresos','5'=>'Gastos','6'=>'Costos'];
        return $map[$c] ?? 'Clase ' . $c;
    }

    public function meta(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        return [
            'filename'        => 'balance_comprobacion_' . $params['desde'] . '_' . $params['hasta'],
            'email_subject'   => 'Balance de Comprobación · ' . $params['desde'] . ' al ' . $params['hasta'],
            'pdf_orientation' => 'L',
        ];
    }
}
