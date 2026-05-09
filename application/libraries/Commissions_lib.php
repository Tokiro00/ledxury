<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Commissions_lib — fuente única de verdad para el cálculo de comisiones.
 *
 * v2.2.0: ELIMINADA la comisión directa por factura. En este negocio las
 * comisiones se pagan EXCLUSIVAMENTE vía el sistema de bots (operador 7%,
 * admin 3%, coordinador 1%) — ver bot_commission_config + /admin/comisiones.
 * No hay vendedores que cobren "por cada factura suya" sin pasar por bots.
 *
 * Por eso compute() ahora siempre retorna skipped=true. Se conserva el
 * método (en lugar de borrarlo) para que los callers existentes (mam_helper,
 * settlement_helper) sigan funcionando devolviendo 0 en vez de romperse.
 *
 * Histórico de simplificación:
 *   v2.0.0 — 3 implementaciones duplicadas → 1 (este lib) con 7 reglas
 *   v2.1.0 — 7 reglas → 2 (legal_collection 2%, by_commission %) + 1 modificador
 *   v2.2.0 — 2 reglas → 0 (todo via bots)
 *
 * Métodos auxiliares aún en uso:
 *   - isBotOperator($userId)        — usado por Settlements para filtrar
 *   - isNationalSkipped/isSelfInvoice — usados en agregaciones
 *   - getInvoiceFreight             — usado en estados de cuenta
 *   - getActiveRules / syncRules    — usados por Vendors form (CRUD reglas)
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
     * v2.2.0: stub — siempre retorna skipped=true. Las comisiones se pagan
     * exclusivamente vía bots (ver /admin/comisiones), no por factura. Se
     * conserva el método para que callers legacy (mam_helper::calculateSettlementValues,
     * settlement_helper::_computeSingleInvoiceCommission) sigan compilando.
     */
    public function compute($invoice, $vendor, $details = null, $flete = null)
    {
        $empty = $this->_emptyResult();
        $empty['skipped'] = true;
        $empty['rule']    = 'no_direct_commission';
        return $empty;
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
