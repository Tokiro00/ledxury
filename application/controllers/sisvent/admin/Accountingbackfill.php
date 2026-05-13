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
        // CLI: bypass auth (no session disponible al correr via php index.php).
        // Endpoint cli() solo se puede invocar desde shell.
        if (!is_cli()) {
            $this->backend_lib->control([1]);
        }
        $this->load->library('accounting_lib');
        $this->load->model('invoices_model');
    }

    /**
     * CLI back-fill: procesa todo en bucle hasta agotar pendientes.
     * Solo invocable desde shell:
     *   php /var/www/html/index.php sisvent/admin/accountingbackfill/cli
     *
     * Tres fases en orden:
     *   1. Facturas → entries 'invoice' (DR Cliente / CR Ventas)
     *   2. Costo de ventas → entries 'cost_of_sales' (DR Costo / CR Inventario)
     *   3. Refunds → entries 'refund' (DR Devoluciones / CR Cliente)
     */
    public function cli()
    {
        if (!is_cli()) { show_404(); return; }

        $batch = 500;  // batch grande en CLI, no hay timeout HTTP
        $userId = 'cli-backfill';

        echo "=== ACCOUNTING BACK-FILL CLI ===\n";
        echo "Inicio: " . date('Y-m-d H:i:s') . "\n\n";

        $this->_cliPhase('invoices',      'recordInvoice',       $batch, $userId);
        $this->_cliPhase('cost_of_sales', 'recordCostOfSales',   $batch, $userId);
        $this->_cliPhase('refunds',       'recordRefund',        $batch, $userId);

        $stats = $this->_getStats();
        echo "\n=== FINAL ===\n";
        echo sprintf("Facturas pendientes:    %s\n", number_format($stats['invoices_pending']));
        echo sprintf("Costo pendientes:       %s\n", number_format($stats['cost_pending']));
        echo sprintf("Refunds pendientes:     %s\n", number_format($stats['refunds_pending']));
        echo sprintf("Total entries en BD:    %s\n", number_format($stats['total_entries']));
        echo "Fin: " . date('Y-m-d H:i:s') . "\n";
    }

    private function _cliPhase($phase, $methodHint, $batch, $userId)
    {
        echo "--- Fase: $phase ---\n";
        $iteration = 0; $cumOk = 0; $cumFail = 0;
        while (true) {
            $iteration++;
            $rows = $this->_cliFetchPending($phase, $batch);
            if (empty($rows)) {
                echo "  ✓ Sin pendientes.\n";
                break;
            }
            $ok = 0; $fail = 0;
            foreach ($rows as $r) {
                $result = $this->_cliProcessRow($phase, $r, $userId);
                if ($result) $ok++;
                else $fail++;
            }
            $cumOk += $ok; $cumFail += $fail;
            echo sprintf("  Batch %d: %d ok / %d fail (acum: %d ok / %d fail)\n", $iteration, $ok, $fail, $cumOk, $cumFail);
            if ($ok === 0 && $fail > 0) {
                echo "  ⚠ 0 éxitos en este batch — abortando para no loop infinito.\n";
                break;
            }
        }
    }

    private function _cliFetchPending($phase, $limit)
    {
        if ($phase === 'invoices') {
            $sql = "SELECT i.idInvoice, i.clientId, i.storeId, i.total, i.date
                    FROM invoices i
                    WHERE i.deleted = 0 AND COALESCE(i.total, 0) > 0
                      AND i.clientId IS NOT NULL AND i.storeId IS NOT NULL
                      AND NOT EXISTS (SELECT 1 FROM entries e
                                       WHERE e.entryTransactionType='invoice'
                                         AND e.entryTransactionId=i.idInvoice
                                         AND e.deleted=0)
                    ORDER BY i.idInvoice ASC LIMIT ?";
            return $this->db->query($sql, [$limit])->result();
        }
        if ($phase === 'cost_of_sales') {
            $sql = "SELECT i.idInvoice, i.storeId, i.date,
                           COALESCE(SUM(id.quantity * COALESCE(NULLIF(p.cost_cop,0), p.cost, 0)), 0) AS total_cost
                    FROM invoices i
                    JOIN invoice_details id ON id.invoiceId=i.idInvoice
                    JOIN products p ON p.idProduct=id.productId
                    WHERE i.deleted=0 AND i.storeId IS NOT NULL
                      AND NOT EXISTS (SELECT 1 FROM entries e
                                       WHERE e.entryTransactionType='cost_of_sales'
                                         AND e.entryTransactionId=i.idInvoice
                                         AND e.deleted=0)
                    GROUP BY i.idInvoice
                    HAVING total_cost > 0
                    ORDER BY i.idInvoice ASC LIMIT ?";
            return $this->db->query($sql, [$limit])->result();
        }
        if ($phase === 'refunds') {
            $sql = "SELECT r.idRefund, r.invoiceId, r.total, r.date, i.clientId, i.storeId
                    FROM refunds r
                    JOIN invoices i ON i.idInvoice=r.invoiceId
                    WHERE COALESCE(r.deleted,0)=0 AND COALESCE(r.total,0)>0
                      AND i.clientId IS NOT NULL AND i.storeId IS NOT NULL
                      AND NOT EXISTS (SELECT 1 FROM entries e
                                       WHERE e.entryTransactionType='refund'
                                         AND e.entryTransactionId=r.idRefund
                                         AND e.deleted=0)
                    ORDER BY r.idRefund ASC LIMIT ?";
            return $this->db->query($sql, [$limit])->result();
        }
        return [];
    }

    private function _cliProcessRow($phase, $r, $userId)
    {
        $entryDate = !empty($r->date) ? substr($r->date, 0, 10) : null;
        try {
            if ($phase === 'invoices') {
                return $this->accounting_lib->recordInvoice(
                    (int)$r->idInvoice, (int)$r->clientId, (int)$r->storeId,
                    (float)$r->total, $userId, null, $entryDate
                );
            }
            if ($phase === 'cost_of_sales') {
                return $this->accounting_lib->recordCostOfSales(
                    (int)$r->idInvoice, (int)$r->storeId, $userId,
                    (float)$r->total_cost, $entryDate
                );
            }
            if ($phase === 'refunds') {
                return $this->accounting_lib->recordRefund(
                    (int)$r->idRefund, (int)$r->invoiceId, (int)$r->clientId,
                    (float)$r->total, (int)$r->storeId, $userId, null, $entryDate
                );
            }
        } catch (Exception $e) {
            // log silently
        }
        return false;
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
     * Back-fill de COSTO DE VENTAS. Para cada factura sin entry tipo
     * 'cost_of_sales', calcula el costo desde invoice_details × products.cost_cop
     * y genera el asiento DR 613501 / CR 143501.
     */
    public function runCostOfSales()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false]); return; }
        $this->outh_model->CSRFVerify();

        $limit = (int)($this->input->post('limit') ?: 200);
        $userId = $this->session->userdata('user_data')['uname'];

        // Facturas sin asiento de costo + tienen al menos 1 producto con costo > 0
        $sql = "
            SELECT i.idInvoice, i.storeId, i.date,
                   COALESCE(SUM(id.quantity * COALESCE(NULLIF(p.cost_cop, 0), p.cost, 0)), 0) AS total_cost
            FROM invoices i
            JOIN invoice_details id ON id.invoiceId = i.idInvoice
            JOIN products p ON p.idProduct = id.productId
            WHERE i.deleted = 0
              AND i.storeId IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1 FROM entries e
                  WHERE e.entryTransactionType = 'cost_of_sales'
                    AND e.entryTransactionId  = i.idInvoice
                    AND e.deleted = 0
              )
            GROUP BY i.idInvoice
            HAVING total_cost > 0
            ORDER BY i.idInvoice ASC
            LIMIT ?
        ";
        $invoices = $this->db->query($sql, [$limit])->result();

        $ok = 0; $fail = 0; $errors = [];
        foreach ($invoices as $inv) {
            $entryDate = $inv->date ? substr($inv->date, 0, 10) : null;
            try {
                $result = $this->accounting_lib->recordCostOfSales(
                    (int)$inv->idInvoice,
                    (int)$inv->storeId,
                    $userId,
                    (float)$inv->total_cost,
                    $entryDate
                );
                if ($result) $ok++;
                else {
                    $fail++;
                    if (count($errors) < 5) $errors[] = "Factura #{$inv->idInvoice} (costo \$" . number_format($inv->total_cost, 0) . "): recordCostOfSales retornó false";
                }
            } catch (Exception $e) {
                $fail++;
                if (count($errors) < 5) $errors[] = "Factura #{$inv->idInvoice}: " . $e->getMessage();
            }
        }

        $stats = $this->_getStats();
        echo json_encode([
            'ok'        => true,
            'processed' => count($invoices),
            'success'   => $ok,
            'fail'      => $fail,
            'errors'    => $errors,
            'remaining' => $stats['cost_pending'],
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

        // Costo de ventas pendiente: facturas sin entry tipo 'cost_of_sales' y
        // con productos que tienen cost_cop > 0
        $costPending = (int)$this->db->query("
            SELECT COUNT(*) AS n FROM (
                SELECT i.idInvoice
                FROM invoices i
                JOIN invoice_details id ON id.invoiceId = i.idInvoice
                JOIN products p ON p.idProduct = id.productId
                WHERE i.deleted = 0 AND i.storeId IS NOT NULL
                  AND COALESCE(NULLIF(p.cost_cop, 0), p.cost, 0) > 0
                  AND NOT EXISTS (
                      SELECT 1 FROM entries e
                      WHERE e.entryTransactionType = 'cost_of_sales'
                        AND e.entryTransactionId  = i.idInvoice
                        AND e.deleted = 0
                  )
                GROUP BY i.idInvoice
            ) x
        ")->row()->n;

        $costWithEntry = (int)$this->db->where('entryTransactionType', 'cost_of_sales')
                                 ->where('deleted', 0)
                                 ->count_all_results('entries');

        // Totales en BD vs en entries
        $totalEntries = (int)$this->db->where('deleted', 0)->count_all_results('entries');

        return [
            'invoices_total'    => $invTotal,
            'invoices_with_entry' => $invWithEntry,
            'invoices_pending'  => $invPending,
            'refunds_total'     => $refTotal,
            'refunds_with_entry'=> $refWithEntry,
            'refunds_pending'   => $refPending,
            'cost_with_entry'   => $costWithEntry,
            'cost_pending'      => $costPending,
            'total_entries'     => $totalEntries,
        ];
    }
}
