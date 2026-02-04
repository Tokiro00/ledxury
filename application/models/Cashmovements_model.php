<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cashmovements_model extends CI_Model {

    // ========================================================================
    // CRUD BÁSICO
    // ========================================================================

    public function getMovements($filters = array(), $page = 1, $limit = 50) {
        $this->db->select('cash_movements.*');
        $this->db->from('cash_movements');

        if (!empty($filters['sourceType']) && !empty($filters['sourceId'])) {
            $this->db->where('cash_movements.sourceType', $filters['sourceType']);
            $this->db->where('cash_movements.sourceId', $filters['sourceId']);
        }
        if (!empty($filters['movementType'])) {
            $this->db->where('cash_movements.movementType', $filters['movementType']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('cash_movements.status', $filters['status']);
        }
        if (!empty($filters['category'])) {
            $this->db->where('cash_movements.category', $filters['category']);
        }
        if (!empty($filters['from'])) {
            $this->db->where('cash_movements.movementDate >=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $this->db->where('cash_movements.movementDate <=', $filters['to']);
        }

        $this->db->where('cash_movements.deleted', 0);
        $this->db->order_by('cash_movements.movementDate', 'desc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getMovement($id) {
        $this->db->select('cash_movements.*');
        $this->db->from('cash_movements');
        $this->db->where('cash_movements.idMovement', $id);
        $this->db->where('cash_movements.deleted', 0);
        return $this->db->get()->row();
    }

    public function getMovementsBySource($sourceType, $sourceId, $from = null, $to = null) {
        $this->db->select('cash_movements.*');
        $this->db->from('cash_movements');
        $this->db->where('cash_movements.sourceType', $sourceType);
        $this->db->where('cash_movements.sourceId', $sourceId);
        if ($from) $this->db->where('cash_movements.movementDate >=', $from);
        if ($to) $this->db->where('cash_movements.movementDate <=', $to);
        $this->db->where('cash_movements.deleted', 0);
        $this->db->where('cash_movements.status !=', 'anulado');
        $this->db->order_by('cash_movements.movementDate', 'asc');
        return $this->db->get()->result();
    }

    public function save($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('cash_movements', $data);
    }

    public function update($id, $data) {
        date_default_timezone_set("America/Bogota");
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('idMovement', $id);
        return $this->db->update('cash_movements', $data);
    }

    public function remove($id) {
        date_default_timezone_set("America/Bogota");
        $data = array(
            'status' => 'anulado',
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $this->session->userdata('user_data')['uname'],
            'deleted' => 1
        );
        return $this->update($id, $data);
    }

    // ========================================================================
    // BÚSQUEDA
    // ========================================================================

    public function searchByWord($term, $filters = array(), $page = 1, $limit = 50) {
        $this->db->select('cash_movements.*');
        $this->db->from('cash_movements');
        $this->db->group_start();
        $this->db->like('cash_movements.concept', $term);
        $this->db->or_like('cash_movements.documentNumber', $term);
        $this->db->or_like('cash_movements.idMovement', $term);
        $this->db->group_end();

        if (!empty($filters['sourceType']) && !empty($filters['sourceId'])) {
            $this->db->where('cash_movements.sourceType', $filters['sourceType']);
            $this->db->where('cash_movements.sourceId', $filters['sourceId']);
        }

        $this->db->where('cash_movements.deleted', 0);
        $this->db->order_by('cash_movements.movementDate', 'desc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getTotal($filters = array()) {
        $this->db->from('cash_movements');
        if (!empty($filters['sourceType']) && !empty($filters['sourceId'])) {
            $this->db->where('cash_movements.sourceType', $filters['sourceType']);
            $this->db->where('cash_movements.sourceId', $filters['sourceId']);
        }
        $this->db->where('cash_movements.deleted', 0);
        return $this->db->count_all_results();
    }

    public function getTotalSearch($term, $filters = array()) {
        $this->db->from('cash_movements');
        $this->db->group_start();
        $this->db->like('cash_movements.concept', $term);
        $this->db->or_like('cash_movements.documentNumber', $term);
        $this->db->or_like('cash_movements.idMovement', $term);
        $this->db->group_end();
        if (!empty($filters['sourceType']) && !empty($filters['sourceId'])) {
            $this->db->where('cash_movements.sourceType', $filters['sourceType']);
            $this->db->where('cash_movements.sourceId', $filters['sourceId']);
        }
        $this->db->where('cash_movements.deleted', 0);
        return $this->db->count_all_results();
    }

    // ========================================================================
    // AGREGACIONES Y REPORTES
    // ========================================================================

    public function getTotalsBySource($sourceType, $sourceId, $from = null, $to = null) {
        $this->db->select('
            SUM(CASE WHEN movementType IN ("ingreso", "apertura") THEN amount ELSE 0 END) as totalIngress,
            SUM(CASE WHEN movementType IN ("egreso", "cierre") THEN amount ELSE 0 END) as totalEgress,
            COUNT(*) as totalMovements
        ');
        $this->db->from('cash_movements');
        $this->db->where('sourceType', $sourceType);
        $this->db->where('sourceId', $sourceId);
        $this->db->where('status !=', 'anulado');
        if ($from) $this->db->where('movementDate >=', $from);
        if ($to) $this->db->where('movementDate <=', $to);
        $this->db->where('deleted', 0);
        return $this->db->get()->row();
    }

    public function getDailyMovements($sourceType, $sourceId, $date) {
        $dayStart = date('Y-m-d', strtotime($date)) . ' 00:00:00';
        $dayEnd = date('Y-m-d', strtotime($date)) . ' 23:59:59';
        return $this->getMovementsBySource($sourceType, $sourceId, $dayStart, $dayEnd);
    }

    public function getMonthlyMovements($sourceType, $sourceId, $year, $month) {
        $from = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $to = date('Y-m-d 23:59:59', strtotime('last day of ' . sprintf('%04d-%02d-01', $year, $month)));
        return $this->getMovementsBySource($sourceType, $sourceId, $from, $to);
    }

    // ========================================================================
    // UTILITARIOS
    // ========================================================================

    public function lastID() {
        return $this->db->insert_id();
    }

    public function linkEntry($movementId, $entryId) {
        $data = array('entryId' => $entryId);
        $this->db->where('idMovement', $movementId);
        return $this->db->update('cash_movements', $data);
    }

    public function getByReference($referenceType, $referenceId) {
        $this->db->select('cash_movements.*');
        $this->db->from('cash_movements');
        $this->db->where('cash_movements.referenceType', $referenceType);
        $this->db->where('cash_movements.referenceId', $referenceId);
        $this->db->where('cash_movements.deleted', 0);
        return $this->db->get()->result();
    }

    /**
     * Get total amount by movement type for a date range
     */
    public function getTotalsByDateRange($from, $to, $movementType = null) {
        $this->db->select_sum('amount', 'total');
        $this->db->from('cash_movements');
        $this->db->where('movementDate >=', $from . ' 00:00:00');
        $this->db->where('movementDate <=', $to . ' 23:59:59');
        if ($movementType) {
            $this->db->where('movementType', $movementType);
        }
        $this->db->where('status !=', 'anulado');
        $this->db->where('deleted', 0);
        $result = $this->db->get()->row();
        return $result ? (float)$result->total : 0;
    }

    /**
     * Get summary of all movements by type for a date range
     */
    public function getSummaryByDateRange($from, $to) {
        $this->db->select('
            SUM(CASE WHEN movementType = "ingreso" THEN amount ELSE 0 END) as totalIngress,
            SUM(CASE WHEN movementType = "egreso" THEN amount ELSE 0 END) as totalEgress,
            SUM(CASE WHEN movementType = "transferencia" THEN amount ELSE 0 END) as totalTransfers,
            COUNT(*) as totalCount
        ');
        $this->db->from('cash_movements');
        $this->db->where('movementDate >=', $from . ' 00:00:00');
        $this->db->where('movementDate <=', $to . ' 23:59:59');
        $this->db->where('status !=', 'anulado');
        $this->db->where('deleted', 0);
        return $this->db->get()->row();
    }
}
