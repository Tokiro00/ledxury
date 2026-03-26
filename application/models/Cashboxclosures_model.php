<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cashboxclosures_model extends CI_Model {

    // ========================================================================
    // CRUD BÁSICO
    // ========================================================================

    public function getClosures($cashboxId, $page = 1, $limit = 20) {
        $this->db->select('cashbox_closures.*');
        $this->db->from('cashbox_closures');
        $this->db->where('cashbox_closures.cashboxId', $cashboxId);
        $this->db->where('cashbox_closures.deleted', 0);
        $this->db->order_by('cashbox_closures.closureDate', 'desc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getClosure($id) {
        $this->db->select('cashbox_closures.*');
        $this->db->from('cashbox_closures');
        $this->db->where('cashbox_closures.idClosure', $id);
        $this->db->where('cashbox_closures.deleted', 0);
        return $this->db->get()->row();
    }

    public function getLastClosure($cashboxId) {
        $this->db->select('cashbox_closures.*');
        $this->db->from('cashbox_closures');
        $this->db->where('cashbox_closures.cashboxId', $cashboxId);
        $this->db->where('cashbox_closures.deleted', 0);
        $this->db->order_by('cashbox_closures.closureDate', 'desc');
        $this->db->limit(1);
        return $this->db->get()->row();
    }

    public function save($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('cashbox_closures', $data);
    }

    public function update($id, $data) {
        date_default_timezone_set("America/Bogota");
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('idClosure', $id);
        return $this->db->update('cashbox_closures', $data);
    }

    public function remove($id) {
        date_default_timezone_set("America/Bogota");
        $data = array(
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted' => 1
        );
        return $this->update($id, $data);
    }

    // ========================================================================
    // LÓGICA DE CIERRE
    // ========================================================================

    public function calculateExpectedBalance($cashboxId, $openingBalance, $from, $to) {
        $this->db->select('
            COALESCE(SUM(CASE WHEN movementType IN ("ingreso", "apertura") THEN amount ELSE 0 END), 0) as totalIngress,
            COALESCE(SUM(CASE WHEN movementType IN ("egreso", "cierre") THEN amount ELSE 0 END), 0) as totalEgress
        ');
        $this->db->from('cash_movements');
        $this->db->where('sourceType', 'caja');
        $this->db->where('sourceId', $cashboxId);
        if ($from) $this->db->where('movementDate >=', $from);
        $this->db->where('movementDate <=', $to);
        $this->db->where('status !=', 'anulado');
        $this->db->where('deleted', 0);
        $row = $this->db->get()->row();

        $totalIngress = $row ? (float)$row->totalIngress : 0;
        $totalEgress = $row ? (float)$row->totalEgress : 0;
        $expectedBalance = $openingBalance + $totalIngress - $totalEgress;

        return array(
            'totalIngress' => $totalIngress,
            'totalEgress' => $totalEgress,
            'expectedBalance' => $expectedBalance
        );
    }

    public function authorizeClosure($id, $userId) {
        date_default_timezone_set("America/Bogota");
        $data = array(
            'status' => 'autorizada',
            'authorizedBy' => $userId,
            'updated_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('idClosure', $id);
        return $this->db->update('cashbox_closures', $data);
    }

    // ========================================================================
    // UTILITARIOS
    // ========================================================================

    public function lastID() {
        return $this->db->insert_id();
    }
}
