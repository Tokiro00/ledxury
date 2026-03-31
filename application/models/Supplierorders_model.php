<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Supplierorders_model extends CI_Model {

    public function getOrders($store = -1, $provider = -1, $status = 'all', $page = 1, $limit = 20) {
        $this->db->select('supplier_orders.*, providers.name as provider_name, stores.name as store_name');
        $this->db->from('supplier_orders');
        $this->db->join('providers', 'providers.idProvider = supplier_orders.providerId', 'left');
        $this->db->join('stores', 'stores.idStore = supplier_orders.storeId', 'left');
        $this->db->where('supplier_orders.deleted', 0);
        if ($store != -1) $this->db->where('supplier_orders.storeId', $store);
        if ($provider != -1) $this->db->where('supplier_orders.providerId', $provider);
        if ($status != 'all') $this->db->where('supplier_orders.status', $status);
        $this->db->order_by('supplier_orders.created_at', 'DESC');
        $this->db->limit($limit, ($page - 1) * $limit);
        return $this->db->get()->result();
    }

    public function getOrder($id) {
        $this->db->select('supplier_orders.*, providers.name as provider_name, providers.idNum as provider_nit,
            providers.phone as provider_phone, providers.email as provider_email,
            stores.name as store_name');
        $this->db->from('supplier_orders');
        $this->db->join('providers', 'providers.idProvider = supplier_orders.providerId', 'left');
        $this->db->join('stores', 'stores.idStore = supplier_orders.storeId', 'left');
        $this->db->where('supplier_orders.id', $id);
        $this->db->where('supplier_orders.deleted', 0);
        return $this->db->get()->row();
    }

    public function getDetails($orderId) {
        $this->db->select('supplier_order_details.*, products.description, products.abc_type, products.idProduct');
        $this->db->from('supplier_order_details');
        $this->db->join('products', 'products.idProduct = supplier_order_details.productId', 'left');
        $this->db->where('supplier_order_details.orderId', $orderId);
        $this->db->order_by('products.description', 'ASC');
        return $this->db->get()->result();
    }

    public function save($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('supplier_orders', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        date_default_timezone_set("America/Bogota");
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('supplier_orders', $data);
    }

    public function remove($id) {
        date_default_timezone_set("America/Bogota");
        return $this->update($id, array(
            'deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s')
        ));
    }

    public function saveDetail($data) {
        return $this->db->insert('supplier_order_details', $data);
    }

    public function saveBatch($details) {
        return $this->db->insert_batch('supplier_order_details', $details);
    }

    public function updateDetail($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('supplier_order_details', $data);
    }

    public function removeDetails($orderId) {
        $this->db->where('orderId', $orderId);
        return $this->db->delete('supplier_order_details');
    }

    public function getNextOrderNumber() {
        date_default_timezone_set("America/Bogota");
        $year = date('Y');
        $row = $this->db->query(
            "SELECT MAX(CAST(SUBSTRING(orderNumber, 9) AS UNSIGNED)) as last_num
             FROM supplier_orders WHERE orderNumber LIKE ?",
            array("OC-{$year}-%")
        )->row();
        $next = ($row && $row->last_num) ? $row->last_num + 1 : 1;
        return sprintf('OC-%s-%05d', $year, $next);
    }

    /**
     * Cantidad en tránsito para un producto en una tienda específica
     * (pedido pero no recibido completamente)
     */
    public function getInTransitByProduct($productId, $storeId) {
        $row = $this->db->query(
            "SELECT COALESCE(SUM(d.quantityOrdered - d.quantityReceived), 0) as in_transit
             FROM supplier_order_details d
             JOIN supplier_orders o ON o.id = d.orderId
             WHERE d.productId = ?
               AND o.storeId = ?
               AND o.deleted = 0
               AND o.status IN ('pendiente','enviada','parcial')
               AND d.status != 'recibido'",
            array($productId, $storeId)
        )->row();
        return (int) $row->in_transit;
    }

    /**
     * Todas las cantidades en tránsito para una tienda, agrupadas por producto
     */
    public function getInTransitByStore($storeId) {
        return $this->db->query(
            "SELECT d.productId, COALESCE(SUM(d.quantityOrdered - d.quantityReceived), 0) as in_transit
             FROM supplier_order_details d
             JOIN supplier_orders o ON o.id = d.orderId
             WHERE o.storeId = ?
               AND o.deleted = 0
               AND o.status IN ('pendiente','enviada','parcial')
               AND d.status != 'recibido'
             GROUP BY d.productId",
            array($storeId)
        )->result();
    }

    /**
     * Registrar recepción de mercancía para una línea
     */
    public function receiveDetail($detailId, $qty) {
        $detail = $this->db->where('id', $detailId)->get('supplier_order_details')->row();
        if (!$detail) return false;

        $newReceived = $detail->quantityReceived + $qty;
        $detailStatus = ($newReceived >= $detail->quantityOrdered) ? 'recibido' : 'parcial';

        $this->updateDetail($detailId, array(
            'quantityReceived' => $newReceived,
            'status' => $detailStatus
        ));

        // Verificar si toda la orden está recibida
        $this->_updateOrderStatus($detail->orderId);

        return $detail;
    }

    private function _updateOrderStatus($orderId) {
        $counts = $this->db->query(
            "SELECT COUNT(*) as total,
                    SUM(CASE WHEN status = 'recibido' THEN 1 ELSE 0 END) as received,
                    SUM(CASE WHEN status = 'parcial' THEN 1 ELSE 0 END) as partial
             FROM supplier_order_details WHERE orderId = ?",
            array($orderId)
        )->row();

        if ($counts->received == $counts->total) {
            $this->update($orderId, array('status' => 'recibida', 'receivedDate' => date('Y-m-d')));
        } elseif ($counts->received > 0 || $counts->partial > 0) {
            $this->update($orderId, array('status' => 'parcial'));
        }
    }
}
