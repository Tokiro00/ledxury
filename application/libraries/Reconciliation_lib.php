<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reconciliation Library
 *
 * Biblioteca para conciliacion bancaria automatica y sugerencias de match.
 * Compara lineas de extracto bancario con movimientos del sistema.
 *
 * @package    MAM ERP
 * @subpackage Libraries
 * @category   Banking
 */
class Reconciliation_lib {

    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Bankstatementlines_model', 'bankstatementlines_model');
        $this->CI->load->model('Cashmovements_model', 'cashmovements_model');
        $this->CI->load->model('Bankreconciliations_model', 'bankreconciliations_model');
        date_default_timezone_set("America/Bogota");
    }

    /**
     * Auto-match bank statement lines with system cash movements
     *
     * @param int $reconciliationId
     * @param int $bankAccountId
     * @param int $dateRange Days tolerance for date matching
     * @return int Number of matches made
     */
    public function autoMatch($reconciliationId, $bankAccountId, $dateRange = 3) {
        $matchCount = 0;
        $userId = $this->CI->session->userdata('user_data')['uname'];

        // Get all unmatched bank statement lines for this reconciliation
        $lines = $this->CI->bankstatementlines_model->getUnmatchedLines($reconciliationId);

        foreach ($lines as $line) {
            // Determine movement type and amount to match
            // Bank debit = money out of bank = system egreso
            // Bank credit = money into bank = system ingreso
            $isDebit = ((float)$line->debit > 0);
            $amount = $isDebit ? (float)$line->debit : (float)$line->credit;
            $movementType = $isDebit ? 'egreso' : 'ingreso';

            if ($amount <= 0) continue;

            // Calculate date range
            $dateFrom = date('Y-m-d 00:00:00', strtotime($line->transactionDate . " -$dateRange days"));
            $dateTo = date('Y-m-d 23:59:59', strtotime($line->transactionDate . " +$dateRange days"));

            // Search for matching cash movements
            $this->CI->db->select('cash_movements.*');
            $this->CI->db->from('cash_movements');
            $this->CI->db->where('cash_movements.sourceType', 'banco');
            $this->CI->db->where('cash_movements.sourceId', $bankAccountId);
            $this->CI->db->where('cash_movements.reconciled', 0);
            $this->CI->db->where('cash_movements.movementType', $movementType);
            $this->CI->db->where('cash_movements.amount', $amount);
            $this->CI->db->where('cash_movements.movementDate >=', $dateFrom);
            $this->CI->db->where('cash_movements.movementDate <=', $dateTo);
            $this->CI->db->where('cash_movements.deleted', 0);
            $this->CI->db->where('cash_movements.status !=', 'anulado');
            $this->CI->db->limit(1);
            $movement = $this->CI->db->get()->row();

            if ($movement) {
                // Match found - update bank statement line
                $this->CI->bankstatementlines_model->update($line->idLine, array(
                    'matchedMovementId' => $movement->idMovement,
                    'matchStatus' => 'matched',
                    'matchedAt' => date('Y-m-d H:i:s'),
                    'matchedBy' => $userId
                ));

                // Mark cash movement as reconciled
                $this->CI->cashmovements_model->update($movement->idMovement, array(
                    'reconciled' => 1,
                    'reconciledLineId' => $line->idLine
                ));

                $matchCount++;
            }
        }

        // Update reconciliation stats
        $stats = $this->CI->bankstatementlines_model->getStats($reconciliationId);
        $this->CI->bankreconciliations_model->update($reconciliationId, array(
            'totalMatched' => (int)$stats->matched,
            'totalUnmatchedBank' => (int)$stats->pending + (int)$stats->unmatched_bank,
            'totalUnmatchedSystem' => 0 // Will be calculated separately if needed
        ));

        return $matchCount;
    }

    /**
     * Suggest possible matches for a bank statement line
     *
     * @param int $lineId
     * @param int $bankAccountId
     * @param int $limit
     * @return array Candidates with match score
     */
    public function suggestMatches($lineId, $bankAccountId, $limit = 5) {
        $line = $this->CI->bankstatementlines_model->getLine($lineId);
        if (!$line) return array();

        $isDebit = ((float)$line->debit > 0);
        $amount = $isDebit ? (float)$line->debit : (float)$line->credit;
        $movementType = $isDebit ? 'egreso' : 'ingreso';

        if ($amount <= 0) return array();

        // Search with relaxed criteria: +/- 10% amount, +/- 7 days
        $amountMin = $amount * 0.90;
        $amountMax = $amount * 1.10;
        $dateFrom = date('Y-m-d 00:00:00', strtotime($line->transactionDate . ' -7 days'));
        $dateTo = date('Y-m-d 23:59:59', strtotime($line->transactionDate . ' +7 days'));

        $this->CI->db->select('cash_movements.*');
        $this->CI->db->from('cash_movements');
        $this->CI->db->where('cash_movements.sourceType', 'banco');
        $this->CI->db->where('cash_movements.sourceId', $bankAccountId);
        $this->CI->db->where('cash_movements.reconciled', 0);
        $this->CI->db->where('cash_movements.movementType', $movementType);
        $this->CI->db->where('cash_movements.amount >=', $amountMin);
        $this->CI->db->where('cash_movements.amount <=', $amountMax);
        $this->CI->db->where('cash_movements.movementDate >=', $dateFrom);
        $this->CI->db->where('cash_movements.movementDate <=', $dateTo);
        $this->CI->db->where('cash_movements.deleted', 0);
        $this->CI->db->where('cash_movements.status !=', 'anulado');
        $this->CI->db->limit($limit);
        $candidates = $this->CI->db->get()->result();

        // Score each candidate
        $scored = array();
        foreach ($candidates as $mov) {
            $score = 0;

            // Amount match score (max 50)
            $amountDiff = abs((float)$mov->amount - $amount);
            $amountPct = ($amount > 0) ? ($amountDiff / $amount) * 100 : 100;
            $score += max(0, 50 - ($amountPct * 5));

            // Date match score (max 50)
            $daysDiff = abs((strtotime(date('Y-m-d', strtotime($mov->movementDate))) - strtotime($line->transactionDate)) / 86400);
            $score += max(0, 50 - ($daysDiff * 7));

            $mov->matchScore = round($score);
            $mov->amountDiff = $amountDiff;
            $mov->daysDiff = $daysDiff;
            $scored[] = $mov;
        }

        // Sort by score descending
        usort($scored, function($a, $b) {
            return $b->matchScore <=> $a->matchScore;
        });

        return array_slice($scored, 0, $limit);
    }
}
