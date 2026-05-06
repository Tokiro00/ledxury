<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Anticipos a vendedores. Cuando se le entrega plata a un vendedor antes
 * de la liquidación, queda como cuenta por cobrar al empleado. En la
 * liquidación, se cruza FIFO contra el neto de comisiones.
 *
 * Estados: pendiente → aprobado → desembolsado → pagado / anulado
 */
class Employeeadvances_model extends CI_Model
{
    /**
     * Genera siguiente código formato ANT#### (compatible con Lumen).
     * Si en la base hay códigos con prefijo legacy (AC-XXXXXX), los considera
     * para no chocar — toma el max numérico entre ambos formatos y le suma 1.
     */
    public function getNextCode()
    {
        $maxAnt = 0; $maxAc = 0;
        $row = $this->db->select_max('code')->like('code', 'ANT', 'after')->get('employee_advances')->row();
        if ($row && $row->code) $maxAnt = (int)substr($row->code, 3);
        $row = $this->db->select_max('code')->like('code', 'AC-', 'after')->get('employee_advances')->row();
        if ($row && $row->code) $maxAc  = (int)substr($row->code, 3);
        $next = max($maxAnt, $maxAc) + 1;
        return 'ANT' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function save($data)
    {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('employee_advances', $data);
    }

    public function update($id, $data)
    {
        date_default_timezone_set("America/Bogota");
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update('employee_advances', $data);
        return true;
    }

    public function lastID() { return $this->db->insert_id(); }

    public function get($id)
    {
        return $this->db->select('a.*, u.name AS employee_name')
            ->from('employee_advances a')
            ->join('users u', 'u.idUser = a.employee_id', 'left')
            ->where('a.id', $id)->where('a.deleted', 0)
            ->get()->row();
    }

    public function getList($filters = array(), $page = 1, $limit = 20)
    {
        $this->db->select('a.*, u.name AS employee_name')
            ->from('employee_advances a')
            ->join('users u', 'u.idUser = a.employee_id', 'left')
            ->where('a.deleted', 0)
            ->order_by('a.created_at', 'DESC');
        if (!empty($filters['employee_id'])) $this->db->where('a.employee_id', $filters['employee_id']);
        if (!empty($filters['status'])) $this->db->where('a.status', $filters['status']);
        if (!empty($filters['from'])) $this->db->where('a.created_at >=', $filters['from'] . ' 00:00:00');
        if (!empty($filters['to']))   $this->db->where('a.created_at <=', $filters['to'] . ' 23:59:59');
        if ($page != -1) $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getTotal($filters = array())
    {
        $this->db->from('employee_advances')->where('deleted', 0);
        if (!empty($filters['employee_id'])) $this->db->where('employee_id', $filters['employee_id']);
        if (!empty($filters['status'])) $this->db->where('status', $filters['status']);
        if (!empty($filters['from'])) $this->db->where('created_at >=', $filters['from'] . ' 00:00:00');
        if (!empty($filters['to']))   $this->db->where('created_at <=', $filters['to'] . ' 23:59:59');
        return $this->db->count_all_results();
    }

    /**
     * Anticipos activos de un empleado (desembolsados, con saldo > 0),
     * en orden FIFO (más antiguo primero).
     */
    public function getActiveAdvancesForEmployee($employeeId)
    {
        return $this->db->where('employee_id', $employeeId)
            ->where('status', 'desembolsado')
            ->where('outstanding_balance >', 0.001)
            ->where('deleted', 0)
            ->order_by('disbursed_at', 'ASC')
            ->order_by('id', 'ASC')
            ->get('employee_advances')->result();
    }

    /**
     * Saldo total de anticipos pendientes para un empleado.
     */
    public function getEmployeeBalance($employeeId)
    {
        $row = $this->db->select_sum('outstanding_balance', 'total')
            ->from('employee_advances')
            ->where('employee_id', $employeeId)
            ->where('status', 'desembolsado')
            ->where('deleted', 0)
            ->get()->row();
        return $row ? (float)$row->total : 0;
    }

    /**
     * Resumen agrupado por empleado (vendedor): cuántos anticipos
     * activos, saldo total. Útil para dashboard.
     */
    public function getBalanceByEmployee()
    {
        return $this->db->select('a.employee_id, u.name AS employee_name, COUNT(*) AS active_count, SUM(a.outstanding_balance) AS total_balance')
            ->from('employee_advances a')
            ->join('users u', 'u.idUser = a.employee_id', 'left')
            ->where('a.status', 'desembolsado')
            ->where('a.outstanding_balance >', 0.001)
            ->where('a.deleted', 0)
            ->group_by('a.employee_id, u.name')
            ->order_by('total_balance', 'DESC')
            ->get()->result();
    }

    /**
     * Reduce el outstanding_balance al cruzar con una liquidación.
     * Si queda en 0, marca como pagado.
     */
    public function applyToBalance($advanceId, $amountApplied)
    {
        $adv = $this->get($advanceId);
        if (!$adv) return false;
        $newBalance = max(0, (float)$adv->outstanding_balance - (float)$amountApplied);
        $update = array('outstanding_balance' => $newBalance);
        if ($newBalance <= 0.001) $update['status'] = 'pagado';
        return $this->update($advanceId, $update);
    }

    /**
     * Audit: registrar el cruce de un anticipo con una liquidación.
     */
    public function logSettlementCross($settlementId, $advanceId, $amountApplied, $userId)
    {
        date_default_timezone_set("America/Bogota");
        return $this->db->insert('settlement_advance_payments', array(
            'settlement_id' => $settlementId,
            'advance_id' => $advanceId,
            'amount_applied' => $amountApplied,
            'applied_by' => $userId,
            'applied_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function getCrossesForSettlement($settlementId)
    {
        return $this->db->select('sap.*, a.code AS advance_code, a.amount AS advance_amount, a.purpose')
            ->from('settlement_advance_payments sap')
            ->join('employee_advances a', 'a.id = sap.advance_id')
            ->where('sap.settlement_id', $settlementId)
            ->order_by('sap.applied_at', 'ASC')
            ->get()->result();
    }
}
