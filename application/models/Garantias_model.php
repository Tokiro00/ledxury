<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CRUD de tickets de garantía/devolución (tabla garantias_tickets).
 * Los tickets se vinculan opcionalmente a una conversación de WhatsApp
 * (canal Meta directo, bot_config_id con channel_type='meta_direct'),
 * a una factura/presupuesto y a un cliente/producto.
 */
class Garantias_model extends CI_Model {

    public function getTickets($filters = array(), $page = 1, $limit = 50)
    {
        $this->db->select('t.*, c.name AS client_name_full, c.cellphone AS client_cellphone, conv.last_message AS conv_last_message');
        $this->db->from('garantias_tickets t');
        $this->db->join('clients c', 'c.idClient = t.client_id', 'left');
        $this->db->join('bot_conversations conv', 'conv.id = t.conversation_id', 'left');
        $this->db->where('t.deleted', 0);

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $this->db->where('t.status', $filters['status']);
        }
        if (!empty($filters['case_type']) && $filters['case_type'] !== 'all') {
            $this->db->where('t.case_type', $filters['case_type']);
        }
        if (!empty($filters['priority']) && $filters['priority'] !== 'all') {
            $this->db->where('t.priority', $filters['priority']);
        }
        if (!empty($filters['assigned_to'])) {
            $this->db->where('t.assigned_to', $filters['assigned_to']);
        }
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $this->db->group_start()
                ->like('t.ticket_number', $term)
                ->or_like('t.client_phone', $term)
                ->or_like('t.client_name', $term)
                ->or_like('t.description', $term)
                ->group_end();
        }
        $this->db->order_by("CASE t.priority WHEN 'urgente' THEN 0 WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END");
        $this->db->order_by('t.opened_at', 'DESC');
        $this->db->limit($limit, ($page - 1) * $limit);
        return $this->db->get()->result();
    }

    public function getTicket($id)
    {
        $this->db->select('t.*, c.name AS client_name_full, c.cellphone AS client_cellphone, c.idNum AS client_idnum,
                           inv.idInvoice, inv.date AS invoice_date, inv.total AS invoice_total,
                           bg.idBudget, bg.date AS budget_date');
        $this->db->from('garantias_tickets t');
        $this->db->join('clients c', 'c.idClient = t.client_id', 'left');
        $this->db->join('invoices inv', 'inv.idInvoice = t.invoice_id', 'left');
        $this->db->join('budgets bg', 'bg.idBudget = t.budget_id', 'left');
        $this->db->where('t.id', $id);
        $this->db->where('t.deleted', 0);
        return $this->db->get()->row();
    }

    public function getCounts()
    {
        $this->db->select('status, COUNT(*) AS cnt');
        $this->db->where('deleted', 0);
        $this->db->group_by('status');
        $rows = $this->db->get('garantias_tickets')->result();
        $counts = array('abierto' => 0, 'en_revision' => 0, 'resuelto' => 0, 'cerrado' => 0, 'cancelado' => 0);
        foreach ($rows as $r) $counts[$r->status] = (int)$r->cnt;
        $counts['total_activos'] = $counts['abierto'] + $counts['en_revision'];
        return $counts;
    }

    public function save($data)
    {
        $now = gmdate('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        if (empty($data['opened_at'])) $data['opened_at'] = $now;
        if (empty($data['ticket_number'])) {
            $data['ticket_number'] = $this->_nextTicketNumber();
        }
        $data['created_by'] = $this->session->userdata('user_data')['uname'] ?? null;
        $this->db->insert('garantias_tickets', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['updated_at'] = gmdate('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('garantias_tickets', $data);
    }

    public function changeStatus($id, $newStatus, $notes = null)
    {
        $now = gmdate('Y-m-d H:i:s');
        $update = array('status' => $newStatus, 'updated_at' => $now);
        if ($newStatus === 'resuelto')   $update['resolved_at'] = $now;
        if ($newStatus === 'cerrado')    $update['closed_at']   = $now;
        if ($newStatus === 'cancelado')  $update['closed_at']   = $now;
        if ($notes !== null && $notes !== '') {
            $current = $this->db->select('resolution_notes')->where('id', $id)->get('garantias_tickets')->row();
            $existing = $current ? (string)$current->resolution_notes : '';
            $stamp = '[' . date('Y-m-d H:i') . ' ' . ($this->session->userdata('user_data')['uname'] ?? 'sys') . '] ';
            $update['resolution_notes'] = trim($existing . "\n" . $stamp . $notes);
        }
        $this->db->where('id', $id);
        return $this->db->update('garantias_tickets', $update);
    }

    public function remove($id)
    {
        return $this->update($id, array('deleted' => 1));
    }

    /**
     * Busca tickets abiertos/en revisión por teléfono — útil cuando llega un
     * mensaje del cliente y queremos saber si ya tiene caso abierto.
     */
    public function getOpenByPhone($phone)
    {
        return $this->db->where('client_phone', $phone)
            ->where_in('status', array('abierto', 'en_revision'))
            ->where('deleted', 0)
            ->order_by('opened_at', 'DESC')
            ->get('garantias_tickets')->result();
    }

    /**
     * Genera un número de ticket consecutivo formato GAR-YYYY-NNNNN.
     */
    private function _nextTicketNumber()
    {
        $year = date('Y');
        $row = $this->db->query(
            "SELECT MAX(CAST(SUBSTRING(ticket_number, 10) AS UNSIGNED)) AS last_num
             FROM garantias_tickets WHERE ticket_number LIKE ?",
            array("GAR-{$year}-%")
        )->row();
        $next = ($row && $row->last_num) ? $row->last_num + 1 : 1;
        return sprintf('GAR-%s-%05d', $year, $next);
    }
}
