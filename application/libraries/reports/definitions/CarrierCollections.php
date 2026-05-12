<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * CarrierCollections — Recaudo Transportadora.
 *
 * Equivalente a "Mis Cobros" de Mercado Libre, "Cash on Delivery" de Odoo,
 * y "Bills of Exchange" de SAP B1: trata al carrier como una cuenta por
 * cobrar — cada guia entregada en contrapago genera "carrier nos debe X",
 * cada remesa del carrier al banco la cancela.
 *
 * Combina dos fuentes:
 *   - shipping_guides     → guias enviadas (lo que el carrier cobra al cliente)
 *   - contrapago_payments → pagos del carrier a nuestro banco (lo que
 *                            el carrier ya nos remitió, conciliado)
 *
 * Una guia en isContrapago=1 que está delivered pero no tiene un pago
 * conciliado = "el carrier nos debe ese dinero". Aging cuenta los días
 * desde actualDelivery hasta hoy.
 */
class CarrierCollections extends AbstractReport
{
    public function id(): string { return 'carrier_collections'; }
    public function title(): string { return 'Recaudo Transportadora'; }
    public function description(): string
    {
        return 'Cobros pendientes y recibidos de la transportadora. Guías entregadas vs. pagadas, aging de pendientes y discrepancias entre lo cobrado al cliente y lo remesado al banco.';
    }

    public function requiredRoles(): array
    {
        // 2=admin, 5=tesorería, 9=logística
        return [2, 5, 9];
    }

    public function filterDefinitions(): array
    {
        return [
            ['name' => 'date_range', 'type' => 'date_range'],
            [
                'name'    => 'desde',
                'label'   => 'Desde',
                'type'    => 'date',
                'default' => date('Y-m-01'),
            ],
            [
                'name'    => 'hasta',
                'label'   => 'Hasta',
                'type'    => 'date',
                'default' => date('Y-m-d'),
            ],
            [
                'name'    => 'date_field',
                'label'   => 'Filtrar por',
                'type'    => 'select',
                'options' => [
                    'created_at'      => 'Fecha despacho (created_at)',
                    'actualDelivery'  => 'Fecha entrega (actualDelivery)',
                ],
                'default' => 'created_at',
            ],
            [
                'name'    => 'carrier',
                'label'   => 'Transportadora',
                'type'    => 'select',
                'options' => [
                    'all'             => 'Todas',
                    'Interrapidisimo' => 'Interrapidísimo',
                    'Servientrega'    => 'Servientrega',
                    'Coordinadora'    => 'Coordinadora',
                    'Envia'           => 'Envia',
                    'TCC'             => 'TCC',
                ],
                'default' => 'all',
            ],
            $this->storeFilterDefinition(),
            [
                'name'    => 'estado_recaudo',
                'label'   => 'Estado recaudo',
                'type'    => 'select',
                'options' => [
                    'all'                => 'Todos',
                    'pagada'             => 'Pagadas (carrier remesó)',
                    'pendiente_carrier'  => 'Pendientes (carrier nos debe)',
                    'en_transito'        => 'En tránsito (no entregada)',
                    'devuelta'           => 'Devueltas',
                    'archivada'          => 'Archivadas',
                ],
                'default' => 'all',
            ],
        ];
    }

    public function availableChannels(): array { return ['email', 'schedule']; }

    public function data(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $desde         = $params['desde'];
        $hasta         = $params['hasta'];
        $dateField     = $params['date_field'] ?? 'created_at';
        $carrier       = $params['carrier'] ?? 'all';
        $storeId       = (int) ($params['store_id'] ?? 0);
        $estadoRecaudo = $params['estado_recaudo'] ?? 'all';

        // Whitelist defensivo del campo de fecha
        $dateField = in_array($dateField, ['created_at', 'actualDelivery'], true) ? $dateField : 'created_at';

        $CI =& get_instance();

        $where = ["sg.isContrapago = 1"];
        $args  = [];
        $where[] = "DATE(sg.$dateField) BETWEEN ? AND ?";
        $args[] = $desde; $args[] = $hasta;
        if ($carrier !== 'all') { $where[] = "sg.carrierName = ?"; $args[] = $carrier; }
        if ($storeId)           { $where[] = "sg.storeId = ?"; $args[] = $storeId; }

        $whereSql = implode(' AND ', $where);

        // Pre-agregamos contrapago_payments para evitar multiplicación de filas
        // cuando una guía tiene >1 payment conciliado. v2: ANTES usábamos
        // valorPago directo, pero ese campo es el TOTAL DEL BATCH (todas las
        // guías del mismo lote comparten ese valor) — inflaba el remesado
        // ~13x. Ahora sumamos valorTotal que SÍ es per-guía.
        //
        // Status mapping fix v2: los status en BD están en ESPAÑOL
        // (entregado/anulado/en_transito/...) no en inglés. Antes caían
        // casi todos en "Otro" porque buscaba 'delivered', 'in_transit', etc.
        $sql = "
            SELECT
                sg.id                    AS guide_id,
                sg.numeroPreenvio        AS guia_numero,
                sg.invoiceId,
                sg.carrierName,
                sg.status                AS guide_status,
                sg.estadoNombre,
                sg.estadoGuia,
                sg.actualDelivery,
                sg.created_at            AS fecha_creacion,
                sg.valorDeclarado,
                sg.recipientName,
                sg.ciudadDestinoNombre,
                sg.storeId,
                cp.payment_id,
                cp.fechaPago,
                cp.valor_remesado        AS valorPago,
                cp.batch_id,
                cp.banco,
                cp.num_payments,
                inv.idInvoice            AS factura_id,
                inv.total                AS factura_total,
                cli.name                 AS cliente_name,
                CASE
                    WHEN cp.payment_id IS NOT NULL AND sg.status = 'entregado' THEN 'pagada'
                    WHEN sg.status = 'entregado'                                 THEN 'pendiente_carrier'
                    WHEN sg.status = 'anulado' OR sg.estadoGuia = 15              THEN 'devuelta'
                    WHEN sg.estadoGuia = 16                                       THEN 'archivada'
                    WHEN sg.status IN ('creado','en_transito','en_reparto','novedad','cotizado','pending','in_transit','out_for_delivery','delivered','returned') THEN 'en_transito'
                    ELSE 'otro'
                END AS estado_recaudo,
                CASE
                    WHEN sg.actualDelivery IS NOT NULL
                    THEN DATEDIFF(IFNULL(cp.fechaPago, CURDATE()), sg.actualDelivery)
                    ELSE NULL
                END AS dias_aging
            FROM shipping_guides sg
            LEFT JOIN (
                SELECT
                    MIN(id)             AS payment_id,
                    numeroGuia,
                    MAX(shipping_guide_id) AS shipping_guide_id,
                    SUM(valorTotal)     AS valor_remesado,
                    MIN(fechaPago)      AS fechaPago,
                    MAX(batch_id)       AS batch_id,
                    MAX(banco)          AS banco,
                    COUNT(*)            AS num_payments
                FROM contrapago_payments
                WHERE status = 'conciliado'
                GROUP BY numeroGuia
            ) cp
              ON cp.shipping_guide_id = sg.id
              OR cp.numeroGuia = CAST(sg.numeroPreenvio AS CHAR)
            LEFT JOIN invoices inv ON inv.idInvoice = sg.invoiceId
            LEFT JOIN clients cli ON cli.idClient = inv.clientId
            WHERE $whereSql
            ORDER BY sg.created_at DESC
            LIMIT 5000
        ";

        $rows = $CI->db->query($sql, $args)->result_array();

        // Filtro post-query por estado_recaudo (más simple que armarlo en SQL)
        if ($estadoRecaudo !== 'all') {
            $rows = array_values(array_filter($rows, fn($r) => $r['estado_recaudo'] === $estadoRecaudo));
        }

        // KPIs sobre TODAS las guías del rango (sin filtro de estado_recaudo
        // post-query, así los % son comparables)
        $allRows = $rows;
        // Si filtraron por estado, recalcular KPIs sobre el set sin filtro:
        if ($estadoRecaudo !== 'all') {
            $allRows = $CI->db->query($sql, $args)->result_array();
        }

        $entregadasCount = 0; $entregadasValor = 0;
        $pagadasCount    = 0; $pagadasValor    = 0;
        $pendCount       = 0; $pendValor       = 0;
        $transitoCount   = 0; $transitoValor   = 0;
        $devCount        = 0; $devValor        = 0;
        $sumDiasPagadas  = 0; $countDiasPagadas = 0;
        $sumDiscrepancia = 0;

        foreach ($allRows as $r) {
            $valor = (float)($r['valorDeclarado'] ?: 0);
            switch ($r['estado_recaudo']) {
                case 'pagada':
                    $pagadasCount++; $pagadasValor += (float)$r['valorPago'];
                    $entregadasCount++; $entregadasValor += $valor;
                    if ($r['dias_aging'] !== null) {
                        $sumDiasPagadas += (int)$r['dias_aging'];
                        $countDiasPagadas++;
                    }
                    // Discrepancia entre lo cobrado al cliente y lo remesado
                    $disc = $valor - (float)$r['valorPago'];
                    if (abs($disc) > 100) $sumDiscrepancia += $disc;
                    break;
                case 'pendiente_carrier':
                    $pendCount++; $pendValor += $valor;
                    $entregadasCount++; $entregadasValor += $valor;
                    break;
                case 'en_transito':
                    $transitoCount++; $transitoValor += $valor;
                    break;
                case 'devuelta':
                    $devCount++; $devValor += $valor;
                    break;
            }
        }

        $diasPromCobro = $countDiasPagadas > 0 ? round($sumDiasPagadas / $countDiasPagadas, 1) : 0;
        $pctRecaudo    = $entregadasValor > 0 ? round(($pagadasValor / $entregadasValor) * 100, 1) : 0;

        $kpis = [
            'entregadas_count'  => $entregadasCount,
            'entregadas_valor'  => $entregadasValor,
            'pagadas_count'     => $pagadasCount,
            'pagadas_valor'     => $pagadasValor,
            'pendientes_count'  => $pendCount,
            'pendientes_valor'  => $pendValor,
            'transito_count'    => $transitoCount,
            'transito_valor'    => $transitoValor,
            'devueltas_count'   => $devCount,
            'devueltas_valor'   => $devValor,
            'dias_prom_cobro'   => $diasPromCobro,
            'pct_recaudo'       => $pctRecaudo,
            'discrepancia_total' => $sumDiscrepancia,
        ];

        // Aging buckets: solo sobre PENDIENTES (las que carrier nos debe)
        $bucket07 = ['count' => 0, 'valor' => 0];
        $bucket815 = ['count' => 0, 'valor' => 0];
        $bucket1630 = ['count' => 0, 'valor' => 0];
        $bucket31 = ['count' => 0, 'valor' => 0];
        foreach ($allRows as $r) {
            if ($r['estado_recaudo'] !== 'pendiente_carrier') continue;
            $d = (int)($r['dias_aging'] ?? 0);
            $v = (float)$r['valorDeclarado'];
            if ($d <= 7)         { $bucket07['count']++;    $bucket07['valor'] += $v; }
            elseif ($d <= 15)    { $bucket815['count']++;   $bucket815['valor'] += $v; }
            elseif ($d <= 30)    { $bucket1630['count']++;  $bucket1630['valor'] += $v; }
            else                 { $bucket31['count']++;    $bucket31['valor'] += $v; }
        }
        $totalPend = $pendValor ?: 1;
        $buckets = [
            ['label' => '0–7 días',   'count' => $bucket07['count'],   'total' => $bucket07['valor'],   'pct' => round(($bucket07['valor']/$totalPend)*100, 1),   'severity' => 'OK',       'color' => '#5EBA47'],
            ['label' => '8–15 días',  'count' => $bucket815['count'],  'total' => $bucket815['valor'],  'pct' => round(($bucket815['valor']/$totalPend)*100, 1),  'severity' => 'NORMAL',   'color' => '#4487A0'],
            ['label' => '16–30 días', 'count' => $bucket1630['count'], 'total' => $bucket1630['valor'], 'pct' => round(($bucket1630['valor']/$totalPend)*100, 1), 'severity' => 'ALERTA',   'color' => '#F39C12'],
            ['label' => '+30 días',   'count' => $bucket31['count'],   'total' => $bucket31['valor'],   'pct' => round(($bucket31['valor']/$totalPend)*100, 1),   'severity' => 'CRÍTICO',  'color' => '#C0392B'],
        ];

        // Resumen por transportadora
        $byCarrier = [];
        foreach ($allRows as $r) {
            $c = $r['carrierName'] ?: '—';
            if (!isset($byCarrier[$c])) {
                $byCarrier[$c] = ['carrier' => $c, 'count' => 0, 'cobrado_cliente' => 0, 'remesado' => 0, 'pendiente' => 0];
            }
            $byCarrier[$c]['count']++;
            $valor = (float)$r['valorDeclarado'];
            if ($r['estado_recaudo'] === 'pagada') {
                $byCarrier[$c]['cobrado_cliente'] += $valor;
                $byCarrier[$c]['remesado']        += (float)$r['valorPago'];
            } elseif ($r['estado_recaudo'] === 'pendiente_carrier') {
                $byCarrier[$c]['cobrado_cliente'] += $valor;
                $byCarrier[$c]['pendiente']       += $valor;
            }
        }
        $byCarrier = array_values($byCarrier);

        // Format rows for display
        foreach ($rows as &$r) {
            $r['guia_numero']      = (string)$r['guia_numero'];
            $r['valorDeclarado']   = (float)$r['valorDeclarado'];
            $r['valorPago']        = $r['valorPago'] !== null ? (float)$r['valorPago'] : null;
            $r['dias_aging']       = $r['dias_aging'] !== null ? (int)$r['dias_aging'] : null;
            $r['estado_label']     = $this->estadoLabel($r['estado_recaudo']);
            $r['discrepancia']     = ($r['estado_recaudo'] === 'pagada' && $r['valorPago'] !== null)
                ? ((float)$r['valorDeclarado'] - (float)$r['valorPago'])
                : null;
        }
        unset($r);

        $totals = [
            'guia_numero'    => 'TOTAL',
            'cliente_name'   => count($rows) . ' guía' . (count($rows) === 1 ? '' : 's'),
            'carrierName'    => '',
            'fecha_creacion' => '',
            'actualDelivery' => '',
            'estado_label'   => '',
            'dias_aging'     => '',
            'valorDeclarado' => array_sum(array_column($rows, 'valorDeclarado')),
            'valorPago'      => array_sum(array_filter(array_column($rows, 'valorPago'), 'is_numeric')),
            'discrepancia'   => array_sum(array_filter(array_column($rows, 'discrepancia'), fn($x) => $x !== null)),
            'fechaPago'      => '',
        ];

        return [
            'kpis'       => $kpis,
            'buckets'    => $buckets,
            'by_carrier' => $byCarrier,
            'columns'    => $this->columns(),
            'rows'       => $rows,
            'totals'     => $totals,
        ];
    }

    public function meta(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $tag = '';
        if (!empty($params['carrier']) && $params['carrier'] !== 'all') $tag = '_' . $params['carrier'];
        return [
            'filename'        => 'recaudo_transportadora' . $tag . '_' . $params['desde'] . '_' . $params['hasta'],
            'email_subject'   => 'Recaudo Transportadora · ' . $params['desde'] . ' al ' . $params['hasta'],
            'pdf_orientation' => 'L',
        ];
    }

    private function columns(): array
    {
        return [
            ['key' => 'guia_numero',     'label' => 'Guía',       'type' => 'text'],
            ['key' => 'carrierName',     'label' => 'Carrier',    'type' => 'text'],
            ['key' => 'cliente_name',    'label' => 'Cliente',    'type' => 'text'],
            ['key' => 'fecha_creacion',  'label' => 'Despacho',   'type' => 'date'],
            ['key' => 'actualDelivery',  'label' => 'Entrega',    'type' => 'date'],
            ['key' => 'estado_label',    'label' => 'Estado',     'type' => 'text'],
            ['key' => 'dias_aging',      'label' => 'Días',       'type' => 'number'],
            ['key' => 'valorDeclarado',  'label' => 'Cobrado cliente', 'type' => 'currency'],
            ['key' => 'valorPago',       'label' => 'Remesado',   'type' => 'currency'],
            ['key' => 'discrepancia',    'label' => 'Diff',       'type' => 'currency'],
            ['key' => 'fechaPago',       'label' => 'F. pago',    'type' => 'date'],
        ];
    }

    private function estadoLabel(string $code): string
    {
        $map = [
            'pagada'             => 'Pagada',
            'pendiente_carrier'  => 'Pendiente carrier',
            'en_transito'        => 'En tránsito',
            'devuelta'           => 'Devuelta',
            'archivada'          => 'Archivada',
            'otro'               => 'Otro',
        ];
        return $map[$code] ?? $code;
    }
}
