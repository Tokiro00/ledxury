<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CRUD para purchase_rules — reglas recurrentes que el cron
 * /cron/run_purchase_rules ejecuta para generar supplier_orders en
 * estado borrador.
 */
class Purchaserules_model extends CI_Model {

    public function getRules($onlyActive = false)
    {
        $this->db->select('pr.*, p.name AS provider_name, s.name AS store_name');
        $this->db->from('purchase_rules pr');
        $this->db->join('providers p', 'p.idProvider = pr.providerId', 'left');
        $this->db->join('stores s', 's.idStore = pr.storeId', 'left');
        $this->db->where('pr.deleted', 0);
        if ($onlyActive) $this->db->where('pr.active', 1);
        $this->db->order_by('pr.active', 'DESC');
        $this->db->order_by('pr.next_run_at', 'ASC');
        return $this->db->get()->result();
    }

    public function getRule($id)
    {
        $this->db->select('pr.*, p.name AS provider_name, s.name AS store_name');
        $this->db->from('purchase_rules pr');
        $this->db->join('providers p', 'p.idProvider = pr.providerId', 'left');
        $this->db->join('stores s', 's.idStore = pr.storeId', 'left');
        $this->db->where('pr.id', $id);
        $this->db->where('pr.deleted', 0);
        return $this->db->get()->row();
    }

    public function save($data)
    {
        // Server en America/Bogota desde 1-may; date() devuelve Bogotá local.
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $data['created_by'] = $this->session->userdata('user_data')['uname'] ?? null;
        $this->db->insert('purchase_rules', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('purchase_rules', $data);
    }

    public function remove($id)
    {
        return $this->update($id, ['deleted' => 1, 'active' => 0]);
    }

    public function toggleActive($id)
    {
        $rule = $this->getRule($id);
        if (!$rule) return false;
        return $this->update($id, ['active' => $rule->active ? 0 : 1]);
    }

    public function nameExists($name, $excludeId = null)
    {
        $this->db->where('name', $name);
        $this->db->where('deleted', 0);
        if ($excludeId) $this->db->where('id !=', $excludeId);
        return $this->db->count_all_results('purchase_rules') > 0;
    }
}
