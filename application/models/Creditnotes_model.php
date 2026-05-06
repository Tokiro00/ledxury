<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Creditnotes_model extends CI_Model {

    public function getAll($status = 'all', $vendorId = null, $page = 1, $limit = 50, $storeId = null) {
        $this->db->select('cn.*, c.name as client_name, u.name as vendor_name, s.name as store_name, ua.name as approver_name');
        $this->db->from('credit_notes cn');
        $this->db->join('clients c', 'c.idClient = cn.clientId', 'left');
        $this->db->join('users u', 'u.idUser = cn.vendorId', 'left');
        $this->db->join('stores s', 's.idStore = cn.storeId', 'left');
        $this->db->join('users ua', 'ua.idUser = cn.approved_by', 'left');
        $this->db->where('cn.deleted', 0);
        if ($status !== 'all') $this->db->where('cn.status', $status);
        if ($vendorId) $this->db->where('cn.vendorId', $vendorId);
        if ($storeId) $this->db->where('cn.storeId', (int)$storeId);
        $this->db->order_by('cn.created_at', 'DESC');
        $this->db->limit($limit, ($page - 1) * $limit);
        return $this->db->get()->result();
    }

    public function get($id) {
        $this->db->select('cn.*, c.name as client_name, c.idNum as client_idNum, u.name as vendor_name, s.name as store_name, ua.name as approver_name');
        $this->db->from('credit_notes cn');
        $this->db->join('clients c', 'c.idClient = cn.clientId', 'left');
        $this->db->join('users u', 'u.idUser = cn.vendorId', 'left');
        $this->db->join('stores s', 's.idStore = cn.storeId', 'left');
        $this->db->join('users ua', 'ua.idUser = cn.approved_by', 'left');
        $this->db->where('cn.id', $id);
        return $this->db->get()->row();
    }

    public function getDetails($id) {
        $this->db->select('d.*, p.description as product_name, p.picture_url');
        $this->db->from('credit_note_details d');
        $this->db->join('products p', 'p.idProduct = d.productId', 'left');
        $this->db->where('d.creditNoteId', $id);
        return $this->db->get()->result();
    }

    public function save($data) {
        $this->db->insert('credit_notes', $data);
        return $this->db->insert_id();
    }

    public function saveDetail($data) {
        return $this->db->insert('credit_note_details', $data);
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('credit_notes', $data);
    }

    public function countByStatus($status = 'pendiente') {
        $this->db->where('status', $status)->where('deleted', 0);
        return $this->db->count_all_results('credit_notes');
    }
}
