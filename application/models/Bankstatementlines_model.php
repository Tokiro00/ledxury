<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bankstatementlines_model extends CI_Model {

    // ========================================================================
    // CRUD BASICO
    // ========================================================================

    public function getLines($reconciliationId, $page = 1, $limit = 200) {
        $this->db->select('bank_statement_lines.*');
        $this->db->from('bank_statement_lines');
        $this->db->where('bank_statement_lines.reconciliationId', $reconciliationId);
        $this->db->where('bank_statement_lines.deleted', 0);
        $this->db->order_by('bank_statement_lines.rowNumber', 'asc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getLine($id) {
        $this->db->select('bank_statement_lines.*');
        $this->db->from('bank_statement_lines');
        $this->db->where('bank_statement_lines.idLine', $id);
        $this->db->where('bank_statement_lines.deleted', 0);
        return $this->db->get()->row();
    }

    public function getUnmatchedLines($reconciliationId) {
        $this->db->select('bank_statement_lines.*');
        $this->db->from('bank_statement_lines');
        $this->db->where('bank_statement_lines.reconciliationId', $reconciliationId);
        $this->db->where('bank_statement_lines.matchStatus', 'pendiente');
        $this->db->where('bank_statement_lines.deleted', 0);
        $this->db->order_by('bank_statement_lines.rowNumber', 'asc');
        return $this->db->get()->result();
    }

    public function getMatchedLines($reconciliationId) {
        $this->db->select('bank_statement_lines.*');
        $this->db->from('bank_statement_lines');
        $this->db->where('bank_statement_lines.reconciliationId', $reconciliationId);
        $this->db->where('bank_statement_lines.matchStatus', 'matched');
        $this->db->where('bank_statement_lines.deleted', 0);
        $this->db->order_by('bank_statement_lines.rowNumber', 'asc');
        return $this->db->get()->result();
    }

    public function save($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('bank_statement_lines', $data);
    }

    public function saveBatch($dataArray) {
        date_default_timezone_set("America/Bogota");
        $now = date('Y-m-d H:i:s');
        foreach ($dataArray as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }
        return $this->db->insert_batch('bank_statement_lines', $dataArray);
    }

    public function update($id, $data) {
        date_default_timezone_set("America/Bogota");
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('idLine', $id);
        return $this->db->update('bank_statement_lines', $data);
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
    // ESTADISTICAS
    // ========================================================================

    public function getStats($reconciliationId) {
        $this->db->select("
            COUNT(*) as total_lines,
            SUM(CASE WHEN matchStatus = 'matched' THEN 1 ELSE 0 END) as matched,
            SUM(CASE WHEN matchStatus = 'pendiente' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN matchStatus = 'unmatched_bank' THEN 1 ELSE 0 END) as unmatched_bank,
            SUM(CASE WHEN matchStatus = 'manual' THEN 1 ELSE 0 END) as manual,
            SUM(debit) as total_debits,
            SUM(credit) as total_credits
        ");
        $this->db->from('bank_statement_lines');
        $this->db->where('reconciliationId', $reconciliationId);
        $this->db->where('deleted', 0);
        return $this->db->get()->row();
    }

    public function getTotal($reconciliationId) {
        $this->db->from('bank_statement_lines');
        $this->db->where('reconciliationId', $reconciliationId);
        $this->db->where('deleted', 0);
        return $this->db->count_all_results();
    }

    // ========================================================================
    // UTILITARIOS
    // ========================================================================

    public function lastID() {
        return $this->db->insert_id();
    }
}
