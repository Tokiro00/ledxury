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
     * Trae el extracto cronológico de comisión bot de un vendedor entre dos fechas.
     *
     * v2.3.0 — extracto bot-only (ver nota dentro de la función). Fuentes:
     *   1. Comisión ganada → asientos `bot_commission_accrual` (CRÉDITO)
     *   2. Anticipos desembolsados (employee_advances) → DÉBITO
     *   3. Pagos de comisión bot (cash_movements bot_commission_payment) → DÉBITO
     *
     * El saldo corrido cierra en el saldo del aux 233525 de la persona
     * (= tarjeta "Comisión liquidable"). Vales/liquidaciones del sistema
     * viejo NO se incluyen en esta vista.
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

        // v2.3.0 — El extracto es el LIBRO DE COMISIÓN BOT (decisión de negocio:
        // "el saldo a favor/en contra del vendedor = solo comisión bot"). Tres
        // componentes, todos sobre la misma aux 233525 de la persona:
        //   • Comisión ganada (accrual)  → CRÉDITO  (a favor)   — _getBotCommissionRows
        //   • Anticipos desembolsados    → DÉBITO   (en contra) — empresa entregó plata
        //   • Pago de comisión bot       → DÉBITO   (en contra) — egreso caja/banco
        // El cruce de anticipo↔comisión NO se lista: netea entre el crédito del
        // accrual y el débito del anticipo, no mueve el saldo. Los vales y
        // liquidaciones del sistema viejo no se muestran en esta vista.
        // Resultado: saldo corrido = Σaccrual − anticipos − pagos = saldo del
        // aux 233525 = tarjeta "Comisión liquidable".
        $botCommissions = _getBotCommissionRows($vendorId, $since, $until);

        $vid = $CI->db->escape($vendorId);

        $sql = "
            SELECT * FROM (
                -- Anticipos desembolsados → DÉBITO
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

                -- Pago de comisión bot (egreso de caja/banco a la persona) → DÉBITO
                SELECT
                    cm.movementDate AS fecha,
                    'pago_comision_bot' AS tipo,
                    cm.idMovement AS ref_id,
                    CONCAT('PGB-', LPAD(cm.idMovement, 6, '0')) AS code,
                    COALESCE(cm.concept, 'Pago comisión bot') AS concepto,
                    cm.amount AS debito,
                    0 AS credito
                FROM cash_movements cm
                WHERE cm.referenceType = 'bot_commission_payment'
                  AND cm.referenceId = $vid
                  AND cm.deleted = 0
                  AND cm.status IN ('activo', 'ejecutado')
            ) AS stmt
            WHERE 1=1 $dateFilter
            ORDER BY fecha ASC, ref_id ASC
        ";

        $rows = $CI->db->query($sql)->result();

        // Merge comisiones bot (asientos accrual, calculados aparte) y re-sort.
        if (!empty($botCommissions)) {
            $rows = array_merge($rows, $botCommissions);
            usort($rows, function ($a, $b) {
                $cmp = strcmp((string)$a->fecha, (string)$b->fecha);
                if ($cmp !== 0) return $cmp;
                return strcmp((string)$a->code, (string)$b->code);
            });
        }

        return $rows;
    }
}

if (!function_exists('_getBotOperatorInvoiceRows')) {
    /**
     * Filas per-factura para un operador de bot, estilo Lumen view.php.
     *
     * Para cada factura pagada del bot en el período, emite una fila con:
     *   - fecha = updated_at (cuando pasó a pagada)
     *   - invoice_total, flete, base = total - flete
     *   - comisión = base × percentage / 100
     *
     * Si $tipo === 'comision_bot', son filas oficiales (período liquidado o
     * facturas cobradas firmes); si 'comision_bot_estimado', son del período
     * en curso aún sin liquidar.
     */
    function _getBotOperatorInvoiceRows($config, $botVendorId, $botName, $ps, $pe, $tipo) {
        $CI =& get_instance();
        $pct = (float)$config->percentage;
        if ($pct <= 0 || empty($botVendorId)) return array();

        // 1 fila por factura cobrada del bot en el período.
        // Misma regla de base que Comisiones y Liquidaciones: desde la fecha
        // de corte se restan devoluciones y descuentos (Commissions_lib).
        $CI->load->library('commissions_lib');
        $deduc  = $CI->commissions_lib->baseDeductionsSql('i', 'nc');
        $ncJoin = $CI->commissions_lib->creditNotesJoinSql('nc', 'i.idInvoice');

        $sql = "
            SELECT i.idInvoice, i.total, i.date, i.updated_at, i.clientId,
                   c.name AS client_name,
                   COALESCE(sg.flete, 0) AS flete,
                   COALESCE(sg.flete_puro, 0) AS flete_puro,
                   COALESCE(sg.seguro, 0) AS seguro,
                   COALESCE(i.discount, 0) AS descuento,
                   COALESCE(nc.devuelto, 0) AS devuelto,
                   $deduc AS ajustes
            FROM invoices i
            LEFT JOIN clients c ON c.idClient = i.clientId
            $ncJoin
            LEFT JOIN (
                SELECT invoiceId,
                       SUM(valorTotal)  AS flete,
                       SUM(valorFlete)  AS flete_puro,
                       SUM(valorSeguro) AS seguro
                FROM shipping_guides
                GROUP BY invoiceId
            ) sg ON sg.invoiceId = i.idInvoice
            WHERE i.vendorId = ?
              AND i.state = 2
              AND i.total > 0
              AND (i.deleted IS NULL OR i.deleted = 0)
              AND i.updated_at >= ?
              AND i.updated_at <= ?
            ORDER BY i.updated_at ASC, i.idInvoice ASC
        ";
        $invoices = $CI->db->query($sql, array($botVendorId, $ps . ' 00:00:00', $pe . ' 23:59:59'))->result();

        $rows = array();
        $estLabel = ($tipo === 'comision_bot_estimado') ? ' (estimado)' : '';
        foreach ($invoices as $inv) {
            $invTotal = (float)$inv->total;
            $ajustes  = (float)($inv->ajustes ?? 0); // devoluciones + descuentos
            $flete    = min((float)$inv->flete, $invTotal); // cap al total
            $base     = max(0, $invTotal - $ajustes - $flete);
            $amount   = round($base * $pct / 100);
            if ($amount <= 0) continue;

            $row = new stdClass();
            $row->fecha    = $inv->updated_at ?: $inv->date;
            $row->tipo     = $tipo;
            $row->ref_id   = $inv->idInvoice;
            $row->code     = 'FAC-' . str_pad($inv->idInvoice, 6, '0', STR_PAD_LEFT);
            $row->concepto = ($botName ? $botName . ' — ' : '')
                           . 'Factura #' . $inv->idInvoice
                           . (!empty($inv->client_name) ? ' — ' . htmlspecialchars_decode($inv->client_name, ENT_QUOTES) : '')
                           . $estLabel;
            $row->debito        = 0;
            $row->credito       = $amount;
            $row->invoice_total = $invTotal;
            $row->flete         = $flete;
            // Desglose de lo que cobró la transportadora, para que el vendedor
            // vea de dónde sale el descuento y no solo el total.
            $row->flete_puro    = (float)($inv->flete_puro ?? 0);
            $row->seguro        = (float)($inv->seguro ?? 0);
            $row->contraentrega = max(0, $flete - (float)($inv->flete_puro ?? 0) - (float)($inv->seguro ?? 0));
            $row->ajustes       = $ajustes;
            $row->devuelto      = (float)($inv->devuelto ?? 0);
            $row->descuento     = (float)($inv->descuento ?? 0);
            $row->percentage    = $pct;
            $row->rule          = 'bot_' . $config->commission_type;
            $row->is_underpriced = 0;
            $rows[] = $row;
        }
        return $rows;
    }
}

if (!function_exists('_getBotCommissionRows')) {
    /**
     * Comisiones de bot del extracto del vendedor — LIBRO AUTORITATIVO.
     *
     * v2.3.0: lee los asientos contables reales `bot_commission_accrual`
     * (CR a la aux 233525 de la persona) en lugar de reconstruir desde
     * períodos liquidados + estimado en vivo. Cada accrual nace cuando se
     * registra el ingreso del contrapago de su factura
     * (Contrapagos::registrarIngreso) o por backfill contable.
     *
     * Por qué: la tarjeta "Comisión liquidable" lee el saldo de esa misma aux
     * (generada − pagada). Al leer el extracto los mismos asientos, el saldo
     * corrido cierra EXACTAMENTE en el saldo del aux — antes reconstruía con
     * estimados/liquidados y nunca cuadraba con la tarjeta.
     *
     * Una fila por factura (CRÉDITO = comisión ganada), con desglose
     * Cobros/Flete/Base/% reconstruido desde la factura para el detalle visual.
     * La comisión guardada en el asiento ya es post-flete (base = total − flete).
     */
    function _getBotCommissionRows($vendorId, $since = null, $until = null) {
        $CI =& get_instance();

        // Aux 233525 (bot_commission) de la persona. Sin aux → sin comisión bot.
        $aux = $CI->db->select('id')
            ->from('auxiliary_subaccounts')
            ->where('accountType', 'bot_commission')
            ->where('accountAccount', $vendorId)
            ->where('deleted', 0)
            ->get()->row();
        if (!$aux) return array();

        $dateFilter = '';
        if (!empty($since)) $dateFilter .= ' AND e.entryDate >= ' . $CI->db->escape($since);
        if (!empty($until)) $dateFilter .= ' AND e.entryDate <= ' . $CI->db->escape($until);

        $sql = "
            SELECT e.entryID, e.entryDate AS fecha, e.entryTransactionId AS invoice_id,
                   CAST(e.entryCreditBalance AS DECIMAL(15,2)) AS credito,
                   COALESCE(i.total, 0) AS invoice_total,
                   i.clientId,
                   c.name AS client_name,
                   COALESCE(sg.flete, 0) AS flete
            FROM entries e
            LEFT JOIN invoices i ON i.idInvoice = e.entryTransactionId
            LEFT JOIN clients c ON c.idClient = i.clientId
            LEFT JOIN (
                SELECT invoiceId, SUM(valorTotal) AS flete
                FROM shipping_guides GROUP BY invoiceId
            ) sg ON sg.invoiceId = e.entryTransactionId
            WHERE e.entryTransactionType = 'bot_commission_accrual'
              AND e.entryCreditAuxaccount = " . (int)$aux->id . "
              AND e.deleted = 0
              $dateFilter
            ORDER BY e.entryDate ASC, e.entryID ASC
        ";
        $res = $CI->db->query($sql)->result();

        $rows = array();
        foreach ($res as $r) {
            $invTotal = (float)$r->invoice_total;
            $flete    = min((float)$r->flete, $invTotal);
            $base     = max(0, $invTotal - $flete);
            $com      = (float)$r->credito;
            // % efectivo reconstruido desde el monto del asiento y la base.
            $pct      = $base > 0 ? round($com / $base * 100, 2) : 0;

            $row = new stdClass();
            $row->fecha          = $r->fecha;
            $row->tipo           = 'comision_bot';
            $row->ref_id         = (int)$r->invoice_id;
            $row->code           = 'FAC-' . str_pad((string)(int)$r->invoice_id, 6, '0', STR_PAD_LEFT);
            $row->concepto       = ($r->client_name ?: 'Cliente') . ' — Factura #' . (int)$r->invoice_id;
            $row->debito         = 0;
            $row->credito        = $com;
            $row->invoice_total  = $invTotal;
            $row->flete          = $flete;
            $row->percentage     = $pct;
            $row->rule           = 'bot_commission_accrual';
            $row->is_underpriced = 0;
            $rows[] = $row;
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
        $CI->load->library('commissions_lib');

        $vend = $CI->vendors_model->getVendor($vendorId);
        if (!$vend) return array('amount' => 0, 'rule' => 'no_vendor', 'is_underpriced' => 0);

        // v2.0.0: thin wrapper sobre Commissions_lib::compute(). Antes vivía
        // inlineado aquí en ~60 líneas con las 7 reglas duplicadas.
        $r = $CI->commissions_lib->compute($invoice, $vend, null, $flete);
        return array(
            'amount'         => $r['amount'],
            'rule'           => $r['rule'],
            'is_underpriced' => $r['is_underpriced'],
        );
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
