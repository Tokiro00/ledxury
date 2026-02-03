<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bankreconciliations_model extends CI_Model {

    // ========================================================================
    // CRUD BÁSICO
    // ========================================================================

    public function getReconciliations($bankAccountId, $page = 1, $limit = 20) {
        $this->db->select('bank_reconciliations.*');
        $this->db->from('bank_reconciliations');
        $this->db->where('bank_reconciliations.bankAccountId', $bankAccountId);
        $this->db->where('bank_reconciliations.deleted', 0);
        $this->db->order_by('bank_reconciliations.reconciliationDate', 'desc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getReconciliation($id) {
        $this->db->select('bank_reconciliations.*');
        $this->db->from('bank_reconciliations');
        $this->db->where('bank_reconciliations.idReconciliation', $id);
        $this->db->where('bank_reconciliations.deleted', 0);
        return $this->db->get()->row();
    }

    public function getLastReconciliation($bankAccountId) {
        $this->db->select('bank_reconciliations.*');
        $this->db->from('bank_reconciliations');
        $this->db->where('bank_reconciliations.bankAccountId', $bankAccountId);
        $this->db->where('bank_reconciliations.deleted', 0);
        $this->db->order_by('bank_reconciliations.reconciliationDate', 'desc');
        $this->db->limit(1);
        return $this->db->get()->row();
    }

    public function save($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('bank_reconciliations', $data);
    }

    public function update($id, $data) {
        date_default_timezone_set("America/Bogota");
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('idReconciliation', $id);
        return $this->db->update('bank_reconciliations', $data);
    }

    public function remove($id) {
        date_default_timezone_set("America/Bogota");
        $data = array(
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $this->session->userdata('user_data')['uname'],
            'deleted' => 1
        );
        return $this->update($id, $data);
    }

    // ========================================================================
    // LÓGICA DE CONCILIACIÓN
    // ========================================================================

    public function authorize($id, $userId) {
        date_default_timezone_set("America/Bogota");
        $data = array(
            'status' => 'autorizada',
            'authorizedBy' => $userId,
            'updated_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('idReconciliation', $id);
        return $this->db->update('bank_reconciliations', $data);
    }

    // ========================================================================
    // UTILITARIOS
    // ========================================================================

    public function lastID() {
        return $this->db->insert_id();
    }
}
