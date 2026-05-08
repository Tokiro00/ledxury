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

        if ($details === null) {
            $details = $this->CI->invoices_model->getDetails($invoice->idInvoice);
        }
        if ($flete === null) {
            $flete = $this->getInvoiceFreight($invoice->idInvoice, (float)$invoice->total);
        }

        // Sumar líneas con not_settle activo (productos excluidos)
        $not_settle = 0;
        foreach ($details as $d) {
            if (!empty($d->not_settle)) $not_settle += (float)$d->subtotal;
        }
        $invTotal = (float)$invoice->total;
        // Flete capado al total (defensa contra bases negativas)
        $flete = min((float)$flete, $invTotal);

        // === RULE 1: legal_collection (cobro jurídico) → 2% ===
        if (!empty($invoice->legal_collection)) {
            $base = $invTotal - $not_settle - $flete;
            return $this->_buildResult('legal_collection', $base, 2.00, $base * 0.02, $not_settle, $flete, 0);
        }

        // === RULE 2: by_commission (vendedor con % fijo) ===
        if (!empty($vendor->by_commission)) {
            $pct = (int)$vendor->commission_perc / 100;
            $is_underpriced = 0;
            // Override: si el vendedor tiene "castigar venta subprecio" activo
            // y vendió algún ítem por debajo del precio del producto, la
            // comisión cae al 5% para esta factura.
            if (!empty($vendor->new_settlement_method)) {
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

        // === RULE 3: list_price → 5% sobre 70% del total ===
        if (!empty($invoice->list_price)) {
            $base = ($invTotal * 0.7) - $not_settle - $flete;
            return $this->_buildResult('list_price', $base, 5.00, $base * 0.05, $not_settle, $flete, 0);
        }

        // === RULE 4: invoice_discount (factura con descuento) ===
        if ((float)$invoice->discount > 0) {
            $pct = (float)$invoice->discount_perc / 100;
            $base = $invTotal - $not_settle - (float)$invoice->discount - $flete;
            return $this->_buildResult('invoice_discount', $base, (float)$invoice->discount_perc, $base * $pct, $not_settle, $flete, 0);
        }

        // === RULE 5: e_commerce → 15% ===
        if (!empty($invoice->e_commerce)) {
            $base = $invTotal - $not_settle - $flete;
            return $this->_buildResult('e_commerce', $base, 15.00, $base * 0.15, $not_settle, $flete, 0);
        }

        // === RULE 6: iva → invoice.iva% ===
        if (!empty($invoice->hasIva)) {
            $pct = (float)$invoice->iva / 100;
            $base = $invTotal - $not_settle - $flete;
            return $this->_buildResult('iva', $base, (float)$invoice->iva, $base * $pct, $not_settle, $flete, 0);
        }

        // === RULE 7: default → margen por línea menos flete ===
        $margin = 0;
        $alert = false;
        foreach ($details as $d) {
            if (!empty($d->not_settle)) continue;
            if (empty($d->reviewed) && (float)$d->base >= (float)$d->unit) {
                $alert = true;
            }
            $margin += (float)$d->subtotal - ((float)$d->quantity * (float)$d->base);
        }
        $margin = max(0, $margin - $flete);
        $base = $invTotal - $not_settle - $flete;
        $r = $this->_buildResult('default', $base, 0, $margin, $not_settle, $flete, 0);
        $r['alert'] = $alert;
        return $r;
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
