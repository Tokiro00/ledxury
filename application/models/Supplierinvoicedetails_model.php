<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Supplierinvoicedetails_model extends CI_Model {

    public function getByInvoice($supplierInvoiceId) {
        $this->db->select('supplier_invoice_details.*, products.description as product_name');
        $this->db->join('products', 'products.idProduct = supplier_invoice_details.productId', 'left');
        $this->db->where('supplierInvoiceId', $supplierInvoiceId);
        return $this->db->get('supplier_invoice_details')->result();
    }

    public function save($data) {
        return $this->db->insert('supplier_invoice_details', $data);
    }

    public function saveBatch($details) {
        if (!empty($details)) {
            return $this->db->insert_batch('supplier_invoice_details', $details);
        }
        return false;
    }

    public function deleteByInvoice($supplierInvoiceId) {
        $this->db->where('supplierInvoiceId', $supplierInvoiceId);
        return $this->db->delete('supplier_invoice_details');
    }

    public function getTotal($supplierInvoiceId) {
        $this->db->select('COALESCE(SUM(total), 0) as total');
        $this->db->where('supplierInvoiceId', $supplierInvoiceId);
        return $this->db->get('supplier_invoice_details')->row()->total;
    }
}
