<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Costcenters_model extends CI_Model {

    public function getCostCenters() {
        $this->db->select('cost_centers.*, stores.name as store_name, parent.name as parent_name');
        $this->db->from('cost_centers');
        $this->db->join('stores', 'stores.idStore = cost_centers.store_id', 'left');
        $this->db->join('cost_centers as parent', 'parent.id = cost_centers.parent_id', 'left');
        $this->db->where('cost_centers.deleted', 0);
        $this->db->order_by('cost_centers.code', 'ASC');
        return $this->db->get()->result();
    }

    public function getActiveCostCenters() {
        $this->db->select('cost_centers.*, stores.name as store_name');
        $this->db->from('cost_centers');
        $this->db->join('stores', 'stores.idStore = cost_centers.store_id', 'left');
        $this->db->where('cost_centers.is_active', 1);
        $this->db->where('cost_centers.deleted', 0);
        $this->db->order_by('cost_centers.code', 'ASC');
        return $this->db->get()->result();
    }

    public function getCostCenter($id) {
        $this->db->select('cost_centers.*, stores.name as store_name, parent.name as parent_name');
        $this->db->from('cost_centers');
        $this->db->join('stores', 'stores.idStore = cost_centers.store_id', 'left');
        $this->db->join('cost_centers as parent', 'parent.id = cost_centers.parent_id', 'left');
        $this->db->where('cost_centers.id', $id);
        return $this->db->get()->row();
    }

    public function save($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('cost_centers', $data);
    }

    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('cost_centers', $data);
    }

    public function remove($id) {
        return $this->update($id, array('deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')));
    }
}
