<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Commissions_lib — fuente única de verdad para el cálculo de comisiones.
 *
 * Antes de v2.0.0 las 7 reglas estaban duplicadas en 3 lugares:
 *   - mam_helper::calculateSettlementValues (con bloques A/B y vouchers acoplados)
 *   - Settlements::_computeInvoiceCommission (per-invoice formal)
 *   - settlement_helper::_computeSingleInvoiceCommission (per-invoice statement)
 *
 * Cualquier cambio (ej. "restar el flete") obligaba a tocar 3 archivos y ya
 * generó bugs (v1.10.4 olvidé Block B → comisiones infladas; v1.11.2 vouchers
 * se restaban per-invoice → comisiones de 391%). Ahora todos los callers
 * usan compute() desde aquí.
 *
 * Uso típico:
 *
 *     $this->load->library('commissions_lib');
 *     $r = $this->commissions_lib->compute($invoice, $vendor);
 *     echo $r['amount'];     // monto de comisión (siempre positivo)
 *     echo $r['rule'];       // legal_collection|by_commission|list_price|...
 *     echo $r['percentage']; // % aplicado
 *     echo $r['base'];       // base sobre la que se aplicó (incluye descuentos)
 *     echo $r['flete'];      // flete restado
 *
 * Las 7 reglas en orden de precedencia (idénticas a mam_helper original):
 *   1. legal_collection → 2%
 *   2. by_commission    → vendor.commission_perc% (5% override si underpriced)
 *   3. list_price       → 5% sobre 70% del total
 *   4. invoice_discount → invoice.discount_perc%
 *   5. e_commerce       → 15%
 *   6. iva              → invoice.iva%
 *   7. default          → margen por línea (subtotal − cantidad×base)
 *
 * Base de cálculo: invoice.total − not_settle − flete (con variantes según regla).
 *
 * IMPORTANTE: esta función es PURA. No resta vouchers, no acumula totales,
 * no toca BD más allá de lo necesario para fetch de details/flete. El caller
 * orquesta el resto.
 */
class Commissions_lib
{
    /** @var \CI_Controller */
    private $CI;

    public function __construct()
    {
        $this->CI = get_instance();
        $this->CI->load->database();
        $this->CI->load->model('invoices_model');
        $this->CI->load->model('vendors_model');
        $this->CI->load->model('products_model');
    }

    /**
     * Calcula la comisión de UNA factura aplicando las 7 reglas.
     *
     * @param object $invoice  Factura (necesita: idInvoice, total, discount,
     *                         discount_perc, hasIva, iva, e_commerce,
     *                         legal_collection, list_price, blacklisted)
     * @param object $vendor   Vendedor (necesita: by_commission, commission_perc,
     *                         new_settlement_method)
     * @param array|null $details  Si null, los carga de invoices_model->getDetails
     * @param float|null $flete    Si null, lo calcula desde shipping_guides
     * @return array {amount, rule, percentage, base, not_settle, flete, is_underpriced, skipped}
     */
    public function compute($invoice, $vendor, $details = null, $flete = null)
    {
        $empty = $this->_emptyResult();

        if (!$vendor || !empty($invoice->blacklisted)) {
            $empty['skipped'] = true;
            return $empty;
        }

        // v2.1.0: bot operators NO reciben comisión directa. Solo cobran su
        // % via "Liquidar Período" en /admin/comisiones (escenario 2 elegido
        // por el usuario). Antes había doble pago: comisión directa + bots
        // sobre las mismas facturas.
        if ($this->isBotOperator($vendor->idUser)) {
            $empty['skipped'] = true;
            $empty['rule']    = 'bot_operator_skipped';
            return $empty;
        }

        if ($details === null) {
            $details = $this->CI->invoices_model->getDetails($invoice->idInvoice);
        }
        if ($flete === null) {
            $flete = $this->getInvoiceFreight($invoice->idInvoice, (float)$invoice->total);
        }

        // Reglas activas del vendedor a la fecha de la factura (histórico correcto).
        // Fallback a users.* si no hay reglas en vendor_commission_rules.
        $invoiceDate = !empty($invoice->date) ? substr($invoice->date, 0, 10) : null;
        $vendorRules = $this->getActiveRules($vendor->idUser, $invoiceDate);

        $byCommissionRule = isset($vendorRules['by_commission']) ? $vendorRules['by_commission'] : null;
        $penaltyRule      = isset($vendorRules['underprice_penalty_5pct']) ? $vendorRules['underprice_penalty_5pct'] : null;

        $isByCommission = $byCommissionRule ? true : !empty($vendor->by_commission);
        $commissionPct  = $byCommissionRule ? ((float)$byCommissionRule->percentage / 100) : ((int)$vendor->commission_perc / 100);
        $applyPenalty   = $penaltyRule ? true
                       : (!empty($vendor->apply_underprice_penalty_5pct) || !empty($vendor->new_settlement_method));

        // Sumar líneas con not_settle activo (productos excluidos)
        $not_settle = 0;
        foreach ($details as $d) {
            if (!empty($d->not_settle)) $not_settle += (float)$d->subtotal;
        }
        $invTotal = (float)$invoice->total;
        // Flete capado al total (defensa contra bases negativas)
        $flete = min((float)$flete, $invTotal);

        // ====================================================================
        // v2.1.0: SIMPLIFICADO de 7 reglas → 2 reglas + 1 modificador.
        // Eliminadas: list_price (0 facturas en prod), invoice_discount,
        // e_commerce, iva, default — eran reglas legacy de MAM con fórmulas
        // poco prácticas (commission = iva%, commission = discount_perc, etc.)
        // ====================================================================

        // === RULE 1: legal_collection (cobro jurídico) → 2% fijo ===
        // Especial porque el caso es real (recuperación vía abogado) y el 2%
        // refleja el menor margen disponible cuando se cobra con dificultad.
        if (!empty($invoice->legal_collection)) {
            $base = $invTotal - $not_settle - $flete;
            return $this->_buildResult('legal_collection', $base, 2.00, $base * 0.02, $not_settle, $flete, 0);
        }

        // === RULE 2: by_commission (regla universal — % del vendedor) ===
        // Aplica a TODAS las demás facturas (e-commerce incluido). El % viene
        // de vendor_commission_rules (con histórico) o users.commission_perc
        // (fallback). Si el vendedor no tiene ninguna regla configurada,
        // commissionPct = 0 → comisión = 0.
        $pct = $commissionPct;
        $is_underpriced = 0;
        // Modificador: penalización 5% si vendió subprecio (si está activo)
        if ($applyPenalty && $pct > 0) {
            foreach ($details as $d) {
                $product = $this->CI->products_model->getProduct($d->productId);
                if ($product && (float)$d->unit < (float)$product->price) {
                    $pct = 0.05;
                    $is_underpriced = 1;
                    break;
                }
            }
        }
        $base = $invTotal - $not_settle - $flete;
        return $this->_buildResult('by_commission', $base, $pct * 100, $base * $pct, $not_settle, $flete, $is_underpriced);
    }

    /**
     * Indica si un usuario es operador de bot (commission_type='operator' en
     * bot_commission_config con is_active=1). Los operadores SOLO cobran
     * comisión vía bots (1% admin / 7% operator), NO directa por factura,
     * para evitar doble pago sobre la misma venta.
     *
     * Cache por request — la consulta se ejecuta una vez por usuario.
     */
    public function isBotOperator($userId)
    {
        static $cache = array();
        if (isset($cache[$userId])) return $cache[$userId];

        $row = $this->CI->db->select('1 AS exists_flag')
            ->from('bot_commission_config')
            ->where('user_id', $userId)
            ->where('commission_type', 'operator')
            ->where('is_active', 1)
            ->limit(1)
            ->get()->row();
        $cache[$userId] = !empty($row);
        return $cache[$userId];
    }

    /**
     * Devuelve las reglas activas de un vendedor a una fecha específica.
     * Si la factura es del 2024-08-15 y el vendedor cambió de 7% → 10% el
     * 2025-01-01, esta función devuelve la regla del 7% (la vigente en
     * 2024-08-15). Eso da histórico correcto en re-cálculos.
     *
     * @param string $vendorId
     * @param string|null $date  Fecha (YYYY-MM-DD). null = hoy.
     * @return array  [rule_kind => stdClass]  La 1ra regla activa por kind.
     */
    public function getActiveRules($vendorId, $date = null)
    {
        $date = $date ?: date('Y-m-d');
        $rows = $this->CI->db->where('vendor_id', $vendorId)
            ->where('is_active', 1)
            ->where('valid_from <=', $date)
            ->group_start()
                ->where('valid_to IS NULL', null, false)
                ->or_where('valid_to >=', $date)
            ->group_end()
            ->order_by('valid_from', 'DESC')
            ->get('vendor_commission_rules')->result();

        $byKind = array();
        foreach ($rows as $r) {
            // Si hay 2 reglas del mismo kind activas (no debería pasar pero
            // por defensa), nos quedamos con la más reciente (ya ordenadas DESC).
            if (!isset($byKind[$r->rule_kind])) $byKind[$r->rule_kind] = $r;
        }
        return $byKind;
    }

    /**
     * Reemplaza las reglas activas de un vendedor por un set nuevo. Lo
     * antiguo queda con valid_to=ayer + is_active=0 (preserva histórico).
     * Lo nuevo se inserta con valid_from=hoy + is_active=1.
     *
     * @param string $vendorId
     * @param array  $newRules  array de ['rule_kind' => 'by_commission', 'percentage' => 7.00], etc.
     * @param string $createdBy uname para auditoría
     */
    public function syncRules($vendorId, array $newRules, $createdBy = null)
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // Cerrar reglas activas (no eliminar, preservar histórico)
        $this->CI->db->where('vendor_id', $vendorId)
            ->where('is_active', 1)
            ->update('vendor_commission_rules', array(
                'valid_to'  => $yesterday,
                'is_active' => 0,
            ));

        // Insertar las nuevas
        foreach ($newRules as $r) {
            if (empty($r['rule_kind'])) continue;
            $this->CI->db->insert('vendor_commission_rules', array(
                'vendor_id'  => $vendorId,
                'rule_kind'  => $r['rule_kind'],
                'percentage' => isset($r['percentage']) ? (float)$r['percentage'] : 0,
                'valid_from' => $today,
                'valid_to'   => null,
                'is_active'  => 1,
                'created_by' => $createdBy,
                'notes'      => isset($r['notes']) ? $r['notes'] : null,
            ));
        }
    }

    /**
     * Suma del flete asociado a una factura (shipping_guides.valorTotal),
     * capado al total para evitar bases negativas.
     */
    public function getInvoiceFreight($invoiceId, $invoiceTotal = null)
    {
        $row = $this->CI->db->select('COALESCE(SUM(valorTotal), 0) AS flete')
            ->from('shipping_guides')
            ->where('invoiceId', (int)$invoiceId)
            ->get()->row();
        $flete = $row ? (float)$row->flete : 0;
        if ($invoiceTotal !== null) $flete = min($flete, (float)$invoiceTotal);
        return $flete;
    }

    /**
     * Indica si la factura debe excluirse de comisión por tener líneas
     * marcadas como "national" (clientes mayoristas que no pagan comisión).
     * Solo aplica cuando clientId != vendorId (factura normal a cliente).
     */
    public function isNationalSkipped($invoice, $vendorId)
    {
        if ($invoice->clientId == $vendorId) return false;
        $rows = $this->CI->invoices_model->getIfDetailsHasNational($invoice->idInvoice);
        return !empty($rows);
    }

    /**
     * Indica si una factura es "self-invoice" (vendedor le compra a la empresa).
     * En ese caso la comisión se DEDUCE en vez de sumarse al saldo.
     */
    public function isSelfInvoice($invoice, $vendorId)
    {
        return $invoice->clientId == $vendorId;
    }

    private function _emptyResult()
    {
        return array(
            'amount'         => 0,
            'rule'           => 'none',
            'percentage'     => 0,
            'base'           => 0,
            'not_settle'     => 0,
            'flete'          => 0,
            'is_underpriced' => 0,
            'skipped'        => false,
            'alert'          => false,
        );
    }

    private function _buildResult($rule, $base, $percentage, $amount, $not_settle, $flete, $is_underpriced)
    {
        return array(
            'amount'         => max(0, (float)$amount),
            'rule'           => $rule,
            'percentage'     => (float)$percentage,
            'base'           => (float)$base,
            'not_settle'     => (float)$not_settle,
            'flete'          => (float)$flete,
            'is_underpriced' => (int)$is_underpriced,
            'skipped'        => false,
            'alert'          => false,
        );
    }
}
