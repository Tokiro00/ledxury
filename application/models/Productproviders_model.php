<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Productproviders_model extends CI_Model {

    public function getByProduct($productId) {
        $this->db->select('product_providers.*, providers.name as provider_name, providers.idNum, providers.phone, providers.email');
        $this->db->from('product_providers');
        $this->db->join('providers', 'providers.idProvider = product_providers.providerId', 'left');
        $this->db->where('product_providers.productId', $productId);
        $this->db->where('product_providers.is_active', 1);
        $this->db->order_by('product_providers.priority', 'ASC');
        return $this->db->get()->result();
    }

    public function getByProvider($providerId) {
        $this->db->select('product_providers.*, products.description, products.abc_type');
        $this->db->from('product_providers');
        $this->db->join('products', 'products.idProduct = product_providers.productId');
        $this->db->where('product_providers.providerId', $providerId);
        $this->db->where('product_providers.is_active', 1);
        $this->db->where('products.deleted', 0);
        $this->db->order_by('products.description', 'ASC');
        return $this->db->get()->result();
    }

    public function getDefaultProvider($productId) {
        $this->db->select('product_providers.*, providers.name as provider_name');
        $this->db->from('product_providers');
        $this->db->join('providers', 'providers.idProvider = product_providers.providerId', 'left');
        $this->db->where('product_providers.productId', $productId);
        $this->db->where('product_providers.isDefault', 1);
        $this->db->where('product_providers.is_active', 1);
        return $this->db->get()->row();
    }

    public function save($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('product_providers', $data);
    }

    public function update($id, $data) {
        date_default_timezone_set("America/Bogota");
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('product_providers', $data);
    }

    public function remove($id) {
        return $this->update($id, array('is_active' => 0));
    }

    public function setDefault($productId, $providerId) {
        // Quitar default de todos
        $this->db->where('productId', $productId);
        $this->db->update('product_providers', array('isDefault' => 0));
        // Poner default al seleccionado
        $this->db->where('productId', $productId);
        $this->db->where('providerId', $providerId);
        return $this->db->update('product_providers', array('isDefault' => 1));
    }
}
