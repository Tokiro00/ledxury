<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helpers para el extracto unificado del vendedor (estado de cuenta).
 *
 * Convención del libro mayor del vendedor:
 *   - CRÉDITO = a favor del vendedor (gana plata) → liquidaciones de comisión.
 *   - DÉBITO  = en contra del vendedor (le entregamos plata o se le descuenta)
 *               → vales positivos, anticipos desembolsados, cruces de anticipo.
 *
 * Saldo corrido = saldo_anterior + sum(crédito) - sum(débito).
 *   - Saldo positivo → la empresa le DEBE al vendedor.
 *   - Saldo negativo → el vendedor le DEBE a la empresa (anticipos no cruzados).
 */

if (!function_exists('getVendorStatement')) {
    /**
     * Trae el extracto cronológico de un vendedor entre dos fechas.
     *
     * Hace UNION de 5 fuentes:
     *   1. Liquidaciones (expenses ligados al vendedor)
     *   2. Vales (vouchers del vendedor)
     *   3. Anticipos desembolsados (employee_advances)
     *   4. Cruces de anticipo en liquidaciones (settlement_advance_payments)
     *   5. Abonos directos del empleado (cash_movements con referenceType='employee_payment')
     *
     * @param string $vendorId  idUser del vendedor
     * @param string $since     'Y-m-d' o null
     * @param string $until     'Y-m-d' o null
     * @return array  Filas con: fecha, tipo, ref_id, code, concepto, debito, credito, saldo
     */
    function getVendorStatement($vendorId, $since = null, $until = null) {
        $CI =& get_instance();

        $dateFilter = '';
        if (!empty($since)) $dateFilter .= " AND fecha >= " . $CI->db->escape($since . ' 00:00:00');
        if (!empty($until)) $dateFilter .= " AND fecha <= " . $CI->db->escape($until . ' 23:59:59');

        // Comisiones ganadas pendientes de liquidar (facturas pagadas que NO
        // han entrado todavía en una liquidación formal). Se calculan en PHP
        // porque el monto depende de 7 reglas que viven en mam_helper. Se
        // mergean al resultado del UNION ALL como filas tipo 'comision_pendiente'.
        $pendientes = _getPendingCommissionRows($vendorId, $since, $until);

        // Comisiones de bot (admin/coordinador con bot_commission_config):
        // mergea períodos liquidados + estimado del período en curso.
        $botCommissions = _getBotCommissionRows($vendorId, $since, $until);

        $vid = $CI->db->escape($vendorId);

        $sql = "
            SELECT * FROM (
                -- 1) Liquidaciones (comisión ganada → CRÉDITO a favor)
                SELECT
                    e.created_at AS fecha,
                    'liquidacion' AS tipo,
                    e.idExpense AS ref_id,
                    CONCAT('LIQ-', LPAD(e.idExpense, 6, '0')) AS code,
                    CONCAT('Liquidación #', e.idExpense) AS concepto,
                    0 AS debito,
                    ABS(e.value) AS credito
                FROM expenses e
                WHERE e.vendorId = $vid AND e.deleted = 0

                UNION ALL

                -- 2) Vales: value>=0 → DÉBITO (entregado al vendedor); value<0 → CRÉDITO (descuento)
                SELECT
                    v.date AS fecha,
                    'vale' AS tipo,
                    v.idVoucher AS ref_id,
                    CONCAT('VAL-', LPAD(v.idVoucher, 6, '0')) AS code,
                    COALESCE(LEFT(v.description, 80), CONCAT('Vale #', v.idVoucher)) AS concepto,
                    CASE WHEN v.value >= 0 THEN v.value ELSE 0 END AS debito,
                    CASE WHEN v.value < 0 THEN ABS(v.value) ELSE 0 END AS credito
                FROM vouchers v
                WHERE v.userId = $vid AND v.deleted = 0 AND v.state IN (1, 2)

                UNION ALL

                -- 3) Anticipos desembolsados → DÉBITO
                SELECT
                    COALESCE(ea.disbursed_at, ea.created_at) AS fecha,
                    'anticipo' AS tipo,
                    ea.id AS ref_id,
                    ea.code AS code,
                    CONCAT('Anticipo ', ea.code,
                           CASE WHEN ea.purpose IS NOT NULL AND ea.purpose <> ''
                                THEN CONCAT(' — ', ea.purpose) ELSE '' END) AS concepto,
                    ea.amount AS debito,
                    0 AS credito
                FROM employee_advances ea
                WHERE ea.employee_id = $vid AND ea.deleted = 0
                  AND ea.status IN ('desembolsado', 'pagado')

                UNION ALL

                -- 4) Cruces de anticipo en liquidaciones → CRÉDITO
                --    (cancela parte del anticipo contra la comisión liquidada)
                SELECT
                    sap.applied_at AS fecha,
                    'cruce_anticipo' AS tipo,
                    sap.id AS ref_id,
                    CONCAT('CRZ-', LPAD(sap.id, 6, '0')) AS code,
                    CONCAT('Cruce anticipo en liquidación #', sap.settlement_id) AS concepto,
                    0 AS debito,
                    sap.amount_applied AS credito
                FROM settlement_advance_payments sap
                JOIN employee_advances ea2 ON ea2.id = sap.advance_id
                WHERE ea2.employee_id = $vid AND sap.amount_applied > 0

                UNION ALL

                -- 5) Abonos directos del empleado vía cash_movements → CRÉDITO
                --    (vendedor devuelve plata sin pasar por liquidación)
                SELECT
                    cm.movementDate AS fecha,
                    'abono_empleado' AS tipo,
                    cm.idMovement AS ref_id,
                    CONCAT('ABN-', LPAD(cm.idMovement, 6, '0')) AS code,
                    COALESCE(cm.concept, 'Abono empleado') AS concepto,
                    0 AS debito,
                    cm.amount AS credito
                FROM cash_movements cm
                WHERE cm.referenceType = 'employee_payment'
                  AND cm.referenceId = $vid
                  AND cm.deleted = 0
                  AND cm.status IN ('activo', 'ejecutado')
            ) AS stmt
            WHERE 1=1 $dateFilter
            ORDER BY fecha ASC, ref_id ASC
        ";

        $rows = $CI->db->query($sql)->result();

        // Merge comisiones pendientes (calculadas en PHP) y re-sort por fecha.
        if (!empty($pendientes)) {
            $rows = array_merge($rows, $pendientes);
        }
        if (!empty($botCommissions)) {
            $rows = array_merge($rows, $botCommissions);
        }
        if (!empty($pendientes) || !empty($botCommissions)) {
            usort($rows, function ($a, $b) {
                $cmp = strcmp((string)$a->fecha, (string)$b->fecha);
                if ($cmp !== 0) return $cmp;
                return strcmp((string)$a->code, (string)$b->code);
            });
        }

        return $rows;
    }
}

if (!function_exists('_getBotCommissionRows')) {
    /**
     * Comisiones de bot que recibe un usuario via bot_commission_details.
     * Cubre dos casos:
     *   1. Períodos ya liquidados (status='liquidado') → fila por cada uno
     *      con monto y descripción listos.
     *   2. Período vigente (no liquidado todavía) → calcula en vivo desde
     *      bot_commission_config × cobros del período actual y muestra
     *      como "estimado".
     */
    function _getBotCommissionRows($vendorId, $since = null, $until = null) {
        $CI =& get_instance();

        // 1) Liquidados: leer directo de bot_commission_details
        $CI->db->select('bcd.*, bcp.period_start, bcp.period_end, bcp.period_label, bcp.status AS period_status, bcp.liquidated_at')
            ->from('bot_commission_details bcd')
            ->join('bot_commission_periods bcp', 'bcp.id = bcd.period_id')
            ->where('bcd.user_id', $vendorId)
            ->where('bcp.status', 'liquidado');
        if ($since) $CI->db->where('bcp.period_end >=', $since);
        if ($until) $CI->db->where('bcp.period_start <=', $until);
        $rows = array();
        $detalles = $CI->db->get()->result();
        foreach ($detalles as $d) {
            $row = new stdClass();
            $row->fecha    = $d->liquidated_at ?: ($d->period_end . ' 23:59:59');
            $row->tipo     = 'comision_bot';
            $row->ref_id   = $d->id;
            $row->code     = 'BOT-' . str_pad($d->period_id, 6, '0', STR_PAD_LEFT);
            $row->concepto = ($d->bot_name ? $d->bot_name . ' — ' : '')
                           . 'Período ' . $d->period_label
                           . ' (' . ucfirst($d->commission_type ?: '') . ')';
            $row->debito        = 0;
            $row->credito       = (float)$d->commission_amount;
            $row->invoice_total = (float)$d->base_amount;
            $row->flete         = 0;
            $row->percentage    = (float)$d->percentage;
            $row->rule          = 'bot_' . $d->commission_type;
            $row->is_underpriced = 0;
            $rows[] = $row;
        }

        // 2) Período vigente sin liquidar (estimado en vivo)
        // Solo si la persona tiene config activa
        $configs = $CI->db->where('user_id', $vendorId)->where('is_active', 1)->get('bot_commission_config')->result();
        if (!empty($configs)) {
            $today = date('Y-m-d');
            // Ciclo en curso: del 21 mes anterior al 20 mes actual (o si pasó el 20, del 21 actual al 20 próximo)
            $day = (int)date('d');
            if ($day <= 20) {
                $ps = date('Y-m-21', strtotime('-1 month'));
                $pe = date('Y-m-20');
            } else {
                $ps = date('Y-m-21');
                $pe = date('Y-m-20', strtotime('+1 month'));
            }
            // Si el período actual está dentro del rango filtrado del statement
            if ((!$since || $pe >= $since) && (!$until || $ps <= $until)) {
                // ¿Ya se liquidó este período? Si sí, no agregamos estimado
                $alreadyLiq = $CI->db->where('period_start', $ps)->where('period_end', $pe)->where('status', 'liquidado')->count_all_results('bot_commission_periods');
                if ($alreadyLiq == 0) {
                    // Calcular cobros del período por bot
                    $sql = "SELECT bc.id AS bot_id, bc.name AS bot_name, bc.default_vendor_id,
                                   COALESCE(SUM(i.total),0) AS total
                            FROM builderbot_configs bc
                            LEFT JOIN invoices i ON i.vendorId = bc.default_vendor_id
                                AND i.state = 2 AND i.total > 0
                                AND i.date >= ? AND i.date <= ?
                                AND (i.deleted IS NULL OR i.deleted = 0)
                            WHERE bc.is_active = 1
                            GROUP BY bc.id";
                    $cobrosRaw = $CI->db->query($sql, array($ps . ' 00:00:00', $pe . ' 23:59:59'))->result();
                    $totalAll = 0; $cobrosByBot = array();
                    foreach ($cobrosRaw as $cr) {
                        $cobrosByBot[$cr->bot_id] = (float)$cr->total;
                        $totalAll += (float)$cr->total;
                    }
                    foreach ($configs as $cfg) {
                        $base = ($cfg->applies_to === 'all') ? $totalAll
                              : (isset($cobrosByBot[(int)$cfg->applies_to]) ? $cobrosByBot[(int)$cfg->applies_to] : 0);
                        $amount = round($base * ((float)$cfg->percentage / 100));
                        if ($amount <= 0) continue;
                        $row = new stdClass();
                        $row->fecha = $today;
                        $row->tipo = 'comision_bot_estimado';
                        $row->ref_id = $cfg->id;
                        $row->code = 'EST-' . str_pad($cfg->id, 6, '0', STR_PAD_LEFT);
                        $row->concepto = ($cfg->description ?: $cfg->commission_type)
                                       . ' (estimado · período en curso ' . date('d/m', strtotime($ps)) . '–' . date('d/m', strtotime($pe)) . ')';
                        $row->debito = 0;
                        $row->credito = $amount;
                        $row->invoice_total = $base;
                        $row->flete = 0;
                        $row->percentage = (float)$cfg->percentage;
                        $row->rule = 'bot_' . $cfg->commission_type;
                        $row->is_underpriced = 0;
                        $rows[] = $row;
                    }
                }
            }
        }

        return $rows;
    }
}

if (!function_exists('_computeSingleInvoiceCommission')) {
    /**
     * Comisión de UNA factura aplicando las 7 reglas en orden, restando flete.
     * No suma a un acumulador global, no resta vouchers, no toca otros estados.
     * Devuelve el monto positivo de comisión que el vendedor gana por esta factura.
     *
     * Reglas en orden de precedencia:
     *   1. legal_collection → 2%
     *   2. by_commission    → vendor.commission_perc% (o 5% si underpriced)
     *   3. list_price       → 5% sobre 70% del total
     *   4. invoice_discount → invoice.discount_perc%
     *   5. e_commerce       → 15%
     *   6. iva              → invoice.iva%
     *   7. default          → margen por línea (subtotal - cantidad×base) menos flete
     *
     * Base de cálculo en todas: invoice.total - not_settle - flete (con variantes).
     */
    function _computeSingleInvoiceCommission($invoice, $vendorId, $flete = 0) {
        $CI =& get_instance();

        $vend = $CI->vendors_model->getVendor($vendorId);
        if (!$vend) return array('amount' => 0, 'rule' => 'no_vendor', 'is_underpriced' => 0);

        $details = $CI->invoices_model->getDetails($invoice->idInvoice);
        $not_settle_total = 0;
        foreach ($details as $d) {
            if ($d->not_settle) $not_settle_total += (float)$d->subtotal;
        }
        $invTotal = (float)$invoice->total;
        $flete = min((float)$flete, $invTotal);

        if ($invoice->legal_collection) {
            return array('amount' => max(0, ($invTotal - $not_settle_total - $flete) * 0.02), 'rule' => 'legal_collection', 'is_underpriced' => 0);
        }

        if ($vend->by_commission) {
            $pct = (int)$vend->commission_perc / 100;
            $isUnderpriced = 0;
            if ($vend->new_settlement_method) {
                foreach ($details as $d) {
                    $product = $CI->products_model->getProduct($d->productId);
                    if ($product && $d->unit < $product->price) {
                        $pct = 0.05;
                        $isUnderpriced = 1;
                        break;
                    }
                }
            }
            return array('amount' => max(0, ($invTotal - $not_settle_total - $flete) * $pct), 'rule' => 'by_commission', 'is_underpriced' => $isUnderpriced);
        }

        if ($invoice->list_price) {
            return array('amount' => max(0, (($invTotal * 0.7) - $not_settle_total - $flete) * 0.05), 'rule' => 'list_price', 'is_underpriced' => 0);
        }

        if ($invoice->discount > 0) {
            $pct = (float)$invoice->discount_perc / 100;
            return array('amount' => max(0, ($invTotal - $not_settle_total - (float)$invoice->discount - $flete) * $pct), 'rule' => 'invoice_discount', 'is_underpriced' => 0);
        }

        if ($invoice->e_commerce) {
            return array('amount' => max(0, ($invTotal - $not_settle_total - $flete) * 0.15), 'rule' => 'e_commerce', 'is_underpriced' => 0);
        }

        if ($invoice->hasIva) {
            $pct = (float)$invoice->iva / 100;
            return array('amount' => max(0, ($invTotal - $not_settle_total - $flete) * $pct), 'rule' => 'iva', 'is_underpriced' => 0);
        }

        $margin = 0;
        foreach ($details as $d) {
            if ($d->not_settle) continue;
            $margin += (float)$d->subtotal - ((float)$d->quantity * (float)$d->base);
        }
        return array('amount' => max(0, $margin - $flete), 'rule' => 'default', 'is_underpriced' => 0);
    }
}

if (!function_exists('_getPendingCommissionRows')) {
    /**
     * Comisiones ganadas pendientes de liquidar. Retorna filas tipo
     * 'comision_pendiente' (CRÉDITO) por cada factura del vendedor que:
     *   - state = 2 (pagada) y no eliminada
     *   - todavía no fue incluida en una vendor_settlement_items (es decir,
     *     no ha sido formalmente liquidada)
     *   - su updated_at cae en el rango [since, until] (proxy de "fecha de pago")
     *
     * El monto se calcula con calculateSettlementValues() — las mismas 7
     * reglas que usa el resto del sistema.
     */
    function _getPendingCommissionRows($vendorId, $since = null, $until = null) {
        $CI =& get_instance();

        $invoices = $CI->invoices_model->getVendorPaidInvoices($vendorId);
        if (empty($invoices)) return array();

        // Filtrar por rango de fecha (proxy: invoices.updated_at = cuando pasó a pagada)
        $sinceTs = $since ? strtotime($since . ' 00:00:00') : null;
        $untilTs = $until ? strtotime($until . ' 23:59:59') : null;

        // Set de invoice_ids ya liquidados (en alguna vendor_settlement_items
        // o, en sistema legacy, con un expense vinculado por vendorId+invoice_id).
        $liquidatedIds = array();
        $r1 = $CI->db->select('invoice_id')->from('vendor_settlement_items')->get()->result();
        foreach ($r1 as $row) $liquidatedIds[(int)$row->invoice_id] = true;

        // Pre-fetch fletes por factura (sum valorTotal de shipping_guides)
        // en una sola consulta — más eficiente que N queries.
        $invoiceIds = array_map(function ($i) { return (int)$i->idInvoice; }, $invoices);
        $fletes = array();
        if (!empty($invoiceIds)) {
            $sgRows = $CI->db->select('invoiceId, COALESCE(SUM(valorTotal),0) AS flete')
                ->from('shipping_guides')
                ->where_in('invoiceId', $invoiceIds)
                ->group_by('invoiceId')
                ->get()->result();
            foreach ($sgRows as $sg) $fletes[(int)$sg->invoiceId] = (float)$sg->flete;
        }

        $rows = array();
        foreach ($invoices as $inv) {
            if (isset($liquidatedIds[(int)$inv->idInvoice])) continue;

            $fecha = $inv->updated_at ?: $inv->date;
            $ts = strtotime($fecha);
            if ($sinceTs && $ts < $sinceTs) continue;
            if ($untilTs && $ts > $untilTs) continue;

            // Calcular comisión de ESTA factura sola.
            // NO usar calculateSettlementValues per-invoice: esa función resta
            // el TOTAL de vouchers del vendedor al final, lo que distorsiona el
            // valor cuando se llama 1 vez por factura (cada llamada restaba el
            // total entero, dando resultados absurdos como 391%, 860%).
            $invTotal = (float)$inv->total;
            $flete    = isset($fletes[(int)$inv->idInvoice]) ? $fletes[(int)$inv->idInvoice] : 0;
            $comInfo  = _computeSingleInvoiceCommission($inv, $vendorId, $flete);
            $comision = (float)$comInfo['amount'];
            if ($comision <= 0) continue;

            $base     = max(0, $invTotal - $flete);
            // Porcentaje efectivo: comisión / base (base ya excluye flete).
            $pct      = $base > 0 ? round(($comision / $base) * 100, 2) : 0;
            $rule     = $comInfo['rule'];
            $isUnderpriced = (int)$comInfo['is_underpriced'];

            $row = new stdClass();
            $row->fecha    = $fecha;
            $row->tipo     = 'comision_pendiente';
            $row->ref_id   = $inv->idInvoice;
            $row->code     = 'FAC-' . str_pad($inv->idInvoice, 6, '0', STR_PAD_LEFT);
            $row->concepto = 'Comisión factura #' . $inv->idInvoice
                           . (isset($inv->client_name) ? ' — ' . $inv->client_name : '');
            $row->debito       = 0;
            $row->credito      = $comision;
            $row->invoice_total = $invTotal;
            $row->flete         = $flete;
            $row->percentage    = $pct;
            $row->rule          = $rule;
            $row->is_underpriced = $isUnderpriced;
            $rows[] = $row;
        }

        return $rows;
    }
}

if (!function_exists('getVendorPreviousBalance')) {
    /**
     * Saldo del vendedor ANTES de una fecha dada.
     * Positivo: empresa debe al vendedor. Negativo: vendedor debe a empresa.
     */
    function getVendorPreviousBalance($vendorId, $beforeDate) {
        if (empty($beforeDate)) return 0;
        $rows = getVendorStatement($vendorId, null, date('Y-m-d', strtotime($beforeDate . ' -1 day')));
        $balance = 0;
        foreach ($rows as $r) $balance += (float)$r->credito - (float)$r->debito;
        return $balance;
    }
}

if (!function_exists('getVendorCurrentBalance')) {
    /**
     * Saldo del vendedor a HOY = sum(créditos) - sum(débitos) all-time.
     * Es el "saldo neto" verdadero según los libros del vendedor: lo que
     * la empresa le debe (positivo) o lo que el vendedor le debe a la
     * empresa (negativo), considerando TODO el histórico de movimientos.
     *
     * Coincide con el running balance al final cuando la tabla del
     * statement filtra hasta hoy.
     */
    function getVendorCurrentBalance($vendorId) {
        $rows = getVendorStatement($vendorId, null, date('Y-m-d'));
        $balance = 0;
        foreach ($rows as $r) $balance += (float)$r->credito - (float)$r->debito;
        return $balance;
    }
}

if (!function_exists('attachRunningBalance')) {
    /**
     * Recibe el array de filas del statement + saldo inicial; agrega
     * propiedad ->saldo a cada fila (saldo después de aplicar esa fila).
     * Modifica el array por referencia.
     */
    function attachRunningBalance(array &$rows, $startBalance = 0) {
        $balance = (float)$startBalance;
        foreach ($rows as $r) {
            $balance += (float)$r->credito - (float)$r->debito;
            $r->saldo = $balance;
        }
        return $balance;
    }
}

if (!function_exists('getVendorStatementKpis')) {
    /**
     * KPIs del extracto en un período: saldo_anterior, ganado, pagado,
     * neto_periodo, anticipos_activos, saldo_final.
     */
    function getVendorStatementKpis($vendorId, $since, $until, array $rows = null) {
        $CI =& get_instance();

        if ($rows === null) $rows = getVendorStatement($vendorId, $since, $until);

        $previous = $since ? getVendorPreviousBalance($vendorId, $since) : 0;
        $earned = 0; $paid = 0;
        foreach ($rows as $r) {
            $earned += (float)$r->credito;   // a favor del vendedor (liquidaciones, cruces, abonos)
            $paid   += (float)$r->debito;    // entregado al vendedor (vales, anticipos)
        }
        $netPeriod = $earned - $paid;
        $finalBalance = $previous + $netPeriod;

        // Anticipos pendientes (saldo activo a hoy)
        $row = $CI->db->select_sum('outstanding_balance', 'total')
            ->from('employee_advances')
            ->where('employee_id', $vendorId)
            ->where('status', 'desembolsado')
            ->where('deleted', 0)
            ->get()->row();
        $pendingAdvances = $row ? (float)$row->total : 0;

        return array(
            'previous_balance' => $previous,
            'earned' => $earned,
            'paid' => $paid,
            'net_period' => $netPeriod,
            'final_balance' => $finalBalance,
            'pending_advances' => $pendingAdvances,
        );
    }
}
