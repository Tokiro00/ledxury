<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Modelo de liquidaciones estructuradas de vendedor.
 *
 * Convive con la tabla `expenses` (que sigue siendo la fuente para contabilidad
 * y estados de vendedor) y agrega trazabilidad por factura y por regla.
 *
 * La comisión se causa al RECAUDO: las facturas referenciadas en
 * vendor_settlement_items siempre son state=2 (pagadas) al momento de liquidar.
 */
class Vendor_settlement_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function createSettlement($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('vendor_settlements', $data);
        return $this->db->insert_id();
    }

    public function updateSettlement($id, $data)
    {
        $this->db->where('id', $id)->update('vendor_settlements', $data);
    }

    public function getSettlement($id)
    {
        return $this->db->where('id', $id)->get('vendor_settlements')->row();
    }

    public function getByVendor($vendorId, $limit = 50)
    {
        return $this->db->where('vendor_id', $vendorId)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get('vendor_settlements')->result();
    }

    public function getByExpense($expenseId)
    {
        return $this->db->where('expense_id', $expenseId)
            ->get('vendor_settlements')->row();
    }

    public function saveItem($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('vendor_settlement_items', $data);
        return $this->db->insert_id();
    }

    public function saveItemsBatch(array $items)
    {
        if (empty($items)) return 0;
        foreach ($items as &$it) {
            if (empty($it['created_at'])) $it['created_at'] = date('Y-m-d H:i:s');
        }
        unset($it);
        $this->db->insert_batch('vendor_settlement_items', $items);
        return count($items);
    }

    public function getItems($settlementId)
    {
        return $this->db->where('settlement_id', $settlementId)
            ->order_by('id', 'ASC')
            ->get('vendor_settlement_items')->result();
    }

    public function saveVoucher($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('vendor_settlement_vouchers', $data);
        return $this->db->insert_id();
    }

    public function saveVouchersBatch(array $vouchers)
    {
        if (empty($vouchers)) return 0;
        foreach ($vouchers as &$v) {
            if (empty($v['created_at'])) $v['created_at'] = date('Y-m-d H:i:s');
        }
        unset($v);
        $this->db->insert_batch('vendor_settlement_vouchers', $vouchers);
        return count($vouchers);
    }

    public function getVouchers($settlementId)
    {
        return $this->db->where('settlement_id', $settlementId)
            ->get('vendor_settlement_vouchers')->result();
    }

    /**
     * Resumen agrupado por regla aplicada (útil para vista detalle).
     */
    public function getItemsSummaryByRule($settlementId)
    {
        return $this->db->select('rule_applied, COUNT(*) AS n, SUM(commission_amount) AS total')
            ->where('settlement_id', $settlementId)
            ->group_by('rule_applied')
            ->order_by('total', 'DESC')
            ->get('vendor_settlement_items')->result();
    }
}
