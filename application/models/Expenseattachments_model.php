<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Adjuntos de gastos: comprobantes (factura PDF / foto del recibo) que
 * acompañan cada expense_record. Permite auditoría y soporte fiscal.
 */
class Expenseattachments_model extends CI_Model
{
    public function save($data)
    {
        date_default_timezone_set("America/Bogota");
        $data['uploaded_at'] = isset($data['uploaded_at']) ? $data['uploaded_at'] : date('Y-m-d H:i:s');
        $this->db->insert('expense_attachments', $data);
        return $this->db->insert_id();
    }

    public function getByExpense($expenseId)
    {
        return $this->db->where('expense_id', $expenseId)
            ->where('deleted', 0)
            ->order_by('uploaded_at', 'ASC')
            ->get('expense_attachments')->result();
    }

    public function getById($id)
    {
        return $this->db->where('id', $id)
            ->where('deleted', 0)
            ->get('expense_attachments')->row();
    }

    public function softDelete($id)
    {
        date_default_timezone_set("America/Bogota");
        $this->db->where('id', $id)->update('expense_attachments', array(
            'deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function countByExpense($expenseId)
    {
        return $this->db->where('expense_id', $expenseId)
            ->where('deleted', 0)
            ->count_all_results('expense_attachments');
    }
}
