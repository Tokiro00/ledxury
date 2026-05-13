<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Accountingbackfill — back-fill de asientos contables retroactivos.
 *
 * Para data histórica anterior a Fase 3.1 (commits 38a56ee). Genera asientos
 * de venta/refund que faltan iterando invoices/refunds existentes.
 *
 * Idempotente: verifica entries con (entryTransactionType, entryTransactionId)
 * antes de crear — si ya existe, skip.
 *
 * Usa entryDate = invoice.date (fecha original), no NOW().
 *
 * UI con preview + ejecución por batch (limita a 200 por request para
 * evitar timeouts en prod). El usuario puede correrlo varias veces hasta
 * que el contador de pendientes llegue a 0.
 *
 * Solo para superadmin (role=1).
 */
class Accountingbackfill extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1]);
        $this->load->library('accounting_lib');
        $this->load->model('invoices_model');
    }

    /**
     * Index: muestra stats + botones para ejecutar batches.
     */
    public function index()
    {
        $stats = $this->_getStats();
        $this->load->view('sisvent/admin/accountingbackfill/index', [
            'stats' => $stats,
            'role'  => $this->session->userdata('user_data')['role'],
        ]);
    }

    /**
     * Ejecuta un batch de back-fill de FACTURAS. AJAX endpoint.
     * Procesa hasta $limit por llamada para evitar timeout.
     */
    public function runInvoices()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false]); return; }
        $this->outh_model->CSRFVerify();

        $limit = (int)($this->input->post('limit') ?: 200);
        $userId = $this->session->userdata('user_data')['uname'];

        // Facturas sin asiento de tipo 'invoice', NO eliminadas, con cliente + total + bodega válidos
        $sql = "
            SELECT i.idInvoice, i.clientId, i.storeId, i.total, i.date
            FROM invoices i
            WHERE i.deleted = 0
              AND COALESCE(i.total, 0) > 0
              AND i.clientId IS NOT NULL
              AND i.storeId IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1 FROM entries e
                  WHERE e.entryTransactionType = 'invoice'
                    AND e.entryTransactionId  = i.idInvoice
                    AND e.deleted = 0
              )
            ORDER BY i.idInvoice ASC
            LIMIT ?
        ";
        $invoices = $this->db->query($sql, [$limit])->result();

        $ok = 0; $fail = 0; $errors = [];
        foreach ($invoices as $inv) {
            $entryDate = $inv->date ? substr($inv->date, 0, 10) : null;
            try {
                $result = $this->accounting_lib->recordInvoice(
                    (int)$inv->idInvoice,
                    (int)$inv->clientId,
                    (int)$inv->storeId,
                    (float)$inv->total,
                    $userId,
                    null,           // costCenterId
                    $entryDate
                );
                if ($result) {
                    $ok++;
                } else {
                    $fail++;
                    if (count($errors) < 5) $errors[] = "Factura #{$inv->idInvoice}: recordInvoice retornó false";
                }
            } catch (Exception $e) {
                $fail++;
                if (count($errors) < 5) $errors[] = "Factura #{$inv->idInvoice}: " . $e->getMessage();
            }
        }

        $stats = $this->_getStats();
        echo json_encode([
            'ok'         => true,
            'processed'  => count($invoices),
            'success'    => $ok,
            'fail'       => $fail,
            'errors'     => $errors,
            'remaining'  => $stats['invoices_pending'],
        ]);
    }

    /**
     * Back-fill de REFUNDS (tabla legacy refunds, generada al anular facturas).
     */
    public function runRefunds()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false]); return; }
        $this->outh_model->CSRFVerify();

        $limit = (int)($this->input->post('limit') ?: 200);
        $userId = $this->session->userdata('user_data')['uname'];

        $sql = "
            SELECT r.idRefund, r.invoiceId, r.total, r.date, i.clientId, i.storeId
            FROM refunds r
            JOIN invoices i ON i.idInvoice = r.invoiceId
            WHERE COALESCE(r.deleted, 0) = 0
              AND COALESCE(r.total, 0) > 0
              AND i.clientId IS NOT NULL
              AND i.storeId IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1 FROM entries e
                  WHERE e.entryTransactionType = 'refund'
                    AND e.entryTransactionId  = r.idRefund
                    AND e.deleted = 0
              )
            ORDER BY r.idRefund ASC
            LIMIT ?
        ";
        $refunds = $this->db->query($sql, [$limit])->result();

        $ok = 0; $fail = 0; $errors = [];
        foreach ($refunds as $r) {
            $entryDate = $r->date ? substr($r->date, 0, 10) : null;
            try {
                $result = $this->accounting_lib->recordRefund(
                    (int)$r->idRefund,
                    (int)$r->invoiceId,
                    (int)$r->clientId,
                    (float)$r->total,
                    (int)$r->storeId,
                    $userId,
                    null,
                    $entryDate
                );
                if ($result) $ok++;
                else {
                    $fail++;
                    if (count($errors) < 5) $errors[] = "Refund #{$r->idRefund}: recordRefund retornó false";
                }
            } catch (Exception $e) {
                $fail++;
                if (count($errors) < 5) $errors[] = "Refund #{$r->idRefund}: " . $e->getMessage();
            }
        }

        $stats = $this->_getStats();
        echo json_encode([
            'ok'        => true,
            'processed' => count($refunds),
            'success'   => $ok,
            'fail'      => $fail,
            'errors'    => $errors,
            'remaining' => $stats['refunds_pending'],
        ]);
    }

    /**
     * Stats agregados para mostrar al usuario qué falta.
     */
    private function _getStats()
    {
        // Facturas pendientes
        $invTotal   = (int)$this->db->where('deleted', 0)->where('total >', 0)
                              ->where('clientId IS NOT NULL', null, false)
                              ->where('storeId IS NOT NULL', null, false)
                              ->count_all_results('invoices');
        $invWithEntry = (int)$this->db->where('entryTransactionType', 'invoice')
                                ->where('deleted', 0)
                                ->count_all_results('entries');
        $invPending = max(0, $invTotal - $invWithEntry);

        // Refunds pendientes
        $refTotal    = (int)$this->db->where('COALESCE(deleted,0)=0', null, false)
                              ->where('total >', 0)
                              ->count_all_results('refunds');
        $refWithEntry= (int)$this->db->where('entryTransactionType', 'refund')
                                ->where('deleted', 0)
                                ->count_all_results('entries');
        $refPending = max(0, $refTotal - $refWithEntry);

        // Totales en BD vs en entries
        $totalEntries = (int)$this->db->where('deleted', 0)->count_all_results('entries');

        return [
            'invoices_total'    => $invTotal,
            'invoices_with_entry' => $invWithEntry,
            'invoices_pending'  => $invPending,
            'refunds_total'     => $refTotal,
            'refunds_with_entry'=> $refWithEntry,
            'refunds_pending'   => $refPending,
            'total_entries'     => $totalEntries,
        ];
    }
}
