<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tenants_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function all($includeInactive = false)
    {
        if (!$includeInactive) $this->db->where('active', 1);
        return $this->db->order_by('name', 'ASC')->get('tenants')->result();
    }

    public function find($id)
    {
        return $this->db->where('id', (int)$id)->get('tenants')->row();
    }

    public function findBySlug($slug)
    {
        return $this->db->where('slug', $slug)->get('tenants')->row();
    }

    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('tenants', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', (int)$id)->update('tenants', $data);
    }

    public function setActive($id, $active)
    {
        return $this->db->where('id', (int)$id)
            ->update('tenants', array('active' => $active ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')));
    }

    public function counts($id)
    {
        $id = (int)$id;
        $r = array();
        $r['invoices'] = (int)$this->db->where('tenant_id', $id)->where('deleted', 0)->count_all_results('invoices');
        $r['budgets']  = (int)$this->db->where('tenant_id', $id)->where('deleted', 0)->count_all_results('budgets');
        $r['clients']  = (int)$this->db->where('tenant_id', $id)->where('deleted', 0)->count_all_results('clients');
        $r['products'] = (int)$this->db->where('tenant_id', $id)->count_all_results('products');
        $r['users']    = (int)$this->db->where('tenant_id', $id)->where('deleted', 0)->count_all_results('users');
        $r['stores']   = (int)$this->db->where('tenant_id', $id)->where('deleted', 0)->count_all_results('stores');
        return $r;
    }
}
