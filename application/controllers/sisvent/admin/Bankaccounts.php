<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bankaccounts extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('caja_bancos');
        $this->load->model('bankaccounts_model');
        $this->load->model('cashmovements_model');
        $this->load->model('bankreconciliations_model');
        $this->load->model('bankstatementlines_model');
        $this->load->model('stores_model');
        $this->load->library('reconciliation_lib');
    }

    // ========================================================================
    // LISTADO
    // ========================================================================

    public function index()
    {
        $storeId = $this->session->userdata('user_data')['store'];
        $page = $this->input->get('p');
        if (!$page) $page = 1;

        $limit = 20;
        $total = $this->bankaccounts_model->getTotal($storeId);
        $last = ceil($total / $limit);

        if ($page > $last) $page = $last;
        if ($page <= 0) $page = 1;

        $data = array(
            'bankAccounts' => $this->bankaccounts_model->getBankAccounts($storeId, $page, $limit),
            'page' => $page,
            'total' => $total,
            'limit' => $limit
        );

        $this->load->view('sisvent/admin/bankaccounts/list', $data);
    }

    public function search($term)
    {
        $term = str_replace("%20", " ", $term);
        $storeId = $this->session->userdata('user_data')['store'];
        $page = $this->input->get('p');
        if (!$page) $page = 1;

        $limit = 20;
        $total = $this->bankaccounts_model->getTotalSearch($term, $storeId);
        $last = ceil($total / $limit);

        if ($page > $last) $page = $last;
        if ($page <= 0) $page = 1;

        $data = array(
            'bankAccounts' => $this->bankaccounts_model->searchByWord($term, $storeId, $page, $limit),
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'search_term' => $term
        );

        $this->load->view('sisvent/admin/bankaccounts/list', $data);
    }

    // ========================================================================
    // CREAR
    // ========================================================================

    public function add()
    {
        $data = array(
            'stores' => $this->stores_model->getStores()
        );
        $this->load->view('sisvent/admin/bankaccounts/add', $data);
    }

    public function store()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $bankName = $this->input->post('bankName');
        $accountNumber = $this->input->post('accountNumber');
        $accountType = $this->input->post('accountType');
        $ownerName = $this->input->post('ownerName');
        $ownerIdNumber = $this->input->post('ownerIdNumber');
        $branchOffice = $this->input->post('branchOffice');
        $contactEmail = $this->input->post('contactEmail');
        $contactPhone = $this->input->post('contactPhone');
        $initialBalance = (float)$this->input->post('initialBalance');
        $storeId = $this->input->post('storeId');
        if ($storeId === null || $storeId === '' || $storeId === false) {
            $storeId = $this->session->userdata('user_data')['store'];
        }

        $this->form_validation->set_rules('bankName', 'Banco', 'required|max_length[100]');
        $this->form_validation->set_rules('accountNumber', 'Número de cuenta', 'required|max_length[50]');
        $this->form_validation->set_rules('accountType', 'Tipo de cuenta', 'required');

        if (!$this->form_validation->run()) {
            $this->add();
            return;
        }

        // Verificar número de cuenta único
        if ($this->bankaccounts_model->accountNumberExists($accountNumber)) {
            $this->session->set_flashdata('error', 'El número de cuenta ya existe');
            redirect(base_url() . 'sisvent/admin/bankaccounts/add');
        }

        $data = array(
            'bankName' => $bankName,
            'accountNumber' => $accountNumber,
            'accountType' => $accountType,
            'storeId' => $storeId,
            'initialBalance' => $initialBalance,
            'currentBalance' => $initialBalance,
            'ownerName' => $ownerName,
            'ownerIdNumber' => $ownerIdNumber,
            'branchOffice' => $branchOffice,
            'contactEmail' => $contactEmail,
            'contactPhone' => $contactPhone,
            'status' => 'activa'
        );

        if ($this->bankaccounts_model->save($data)) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        } else {
            $this->session->set_flashdata('error', 'No se pudo crear la cuenta bancaria');
            redirect(base_url() . 'sisvent/admin/bankaccounts/add');
        }
    }

    // ========================================================================
    // EDITAR
    // ========================================================================

    public function edit($id)
    {
        $bankAccount = $this->bankaccounts_model->getBankAccount($id);
        if (!$bankAccount) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        }

        $data = array(
            'bankAccount' => $bankAccount,
            'stores' => $this->stores_model->getStores()
        );

        $this->load->view('sisvent/admin/bankaccounts/edit', $data);
    }

    public function update()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $id = $this->input->post('idBankAccount');
        $bankName = $this->input->post('bankName');
        $accountNumber = $this->input->post('accountNumber');
        $accountType = $this->input->post('accountType');
        $ownerName = $this->input->post('ownerName');
        $ownerIdNumber = $this->input->post('ownerIdNumber');
        $branchOffice = $this->input->post('branchOffice');
        $contactEmail = $this->input->post('contactEmail');
        $contactPhone = $this->input->post('contactPhone');
        $status = $this->input->post('status');

        $this->form_validation->set_rules('bankName', 'Banco', 'required|max_length[100]');
        $this->form_validation->set_rules('accountNumber', 'Número de cuenta', 'required|max_length[50]');
        $this->form_validation->set_rules('accountType', 'Tipo de cuenta', 'required');

        if (!$this->form_validation->run()) {
            $this->edit($id);
            return;
        }

        // Verificar número de cuenta único excluyendo actual
        if ($this->bankaccounts_model->accountNumberExists($accountNumber, $id)) {
            $this->session->set_flashdata('error', 'El número de cuenta ya existe');
            redirect(base_url() . 'sisvent/admin/bankaccounts/edit/' . $id);
        }

        $data = array(
            'bankName' => $bankName,
            'accountNumber' => $accountNumber,
            'accountType' => $accountType,
            'ownerName' => $ownerName,
            'ownerIdNumber' => $ownerIdNumber,
            'branchOffice' => $branchOffice,
            'contactEmail' => $contactEmail,
            'contactPhone' => $contactPhone,
            'status' => $status,
            'storeId' => $this->input->post('storeId')
        );

        if ($this->bankaccounts_model->update($id, $data)) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        } else {
            $this->session->set_flashdata('error', 'No se pudo actualizar la cuenta bancaria');
            redirect(base_url() . 'sisvent/admin/bankaccounts/edit/' . $id);
        }
    }

    // ========================================================================
    // ELIMINAR
    // ========================================================================

    public function delete($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $this->bankaccounts_model->remove($id);
        echo base_url() . 'sisvent/admin/bankaccounts';
    }

    // ========================================================================
    // DETALLE + MOVIMIENTOS
    // ========================================================================

    public function view($id)
    {
        $bankAccount = $this->bankaccounts_model->getBankAccount($id);
        if (!$bankAccount) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        }

        // Movimientos de este banco
        $movements = $this->cashmovements_model->getMovementsBySource('banco', $id);

        // Última conciliación
        $lastReconciliation = $this->bankreconciliations_model->getLastReconciliation($id);

        $data = array(
            'bankAccount' => $bankAccount,
            'movements' => $movements,
            'lastReconciliation' => $lastReconciliation
        );

        $this->load->view('sisvent/admin/bankaccounts/view', $data);
    }

    // ========================================================================
    // LIBRO DE BANCOS
    // ========================================================================

    public function libro($id)
    {
        $bankAccount = $this->bankaccounts_model->getBankAccount($id);
        if (!$bankAccount) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        }

        $from = $this->input->get('from');
        $to   = $this->input->get('to');

        if (!$from) $from = date('Y-m-d', strtotime('-30 days'));
        if (!$to)   $to   = date('Y-m-d');

        $fromDt = $from . ' 00:00:00';
        $toDt   = $to   . ' 23:59:59';

        // Traer todos los movimientos para calcular saldo corrido
        $allMovements = $this->cashmovements_model->getMovementsBySource('banco', $id);

        $runningBalance = (float)$bankAccount->initialBalance;
        $openingBalance = $runningBalance;
        $filteredMovements = array();

        foreach ($allMovements as $mov) {
            $sign = in_array($mov->movementType, ['egreso', 'cierre']) ? -1 : 1;

            if ($mov->movementDate < $fromDt) {
                $runningBalance += $sign * (float)$mov->amount;
                $openingBalance = $runningBalance;
            } elseif ($mov->movementDate <= $toDt) {
                $runningBalance += $sign * (float)$mov->amount;
                $mov->runningBalance = $runningBalance;
                $mov->sign = $sign;
                $filteredMovements[] = $mov;
            }
        }

        $data = array(
            'bankAccount'    => $bankAccount,
            'movements'      => $filteredMovements,
            'openingBalance' => $openingBalance,
            'closingBalance' => $runningBalance,
            'from'           => $from,
            'to'             => $to
        );

        $this->load->view('sisvent/admin/bankaccounts/libro', $data);
    }

    // ========================================================================
    // CONCILIACIÓN BANCARIA
    // ========================================================================

    public function reconciliation($id)
    {
        $bankAccount = $this->bankaccounts_model->getBankAccount($id);
        if (!$bankAccount) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        }

        $lastReconciliation = $this->bankreconciliations_model->getLastReconciliation($id);

        // Saldo libro = saldo actual de la cuenta bancaria en nuestros registros
        $bookBalance = $bankAccount->currentBalance;

        $data = array(
            'bankAccount'        => $bankAccount,
            'lastReconciliation' => $lastReconciliation,
            'bookBalance'        => $bookBalance
        );

        $this->load->view('sisvent/admin/bankaccounts/reconciliation', $data);
    }

    public function saveReconciliation()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $bankAccountId   = $this->input->post('bankAccountId');
        $bankBalance     = (float)$this->input->post('bankBalance');
        $statementDate   = $this->input->post('statementDate');
        $notes           = $this->input->post('notes');
        $userId         = $this->session->userdata('user_data')['uname'];

        $bankAccount = $this->bankaccounts_model->getBankAccount($bankAccountId);
        if (!$bankAccount) {
            $this->session->set_flashdata('error', 'Cuenta bancaria no encontrada');
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        }

        $bookBalance     = $bankAccount->currentBalance;
        $difference      = $bankBalance - $bookBalance;
        $reconciledBalance = min($bookBalance, $bankBalance);

        date_default_timezone_set("America/Bogota");

        $data = array(
            'bankAccountId'    => $bankAccountId,
            'reconciliationDate' => date('Y-m-d H:i:s'),
            'statementDate'    => date('Y-m-d', strtotime($statementDate)),
            'bookBalance'      => $bookBalance,
            'bankBalance'      => $bankBalance,
            'reconciledBalance'=> $reconciledBalance,
            'difference'       => $difference,
            'notes'            => $notes,
            'reconciledBy'     => $userId,
            'status'           => ($difference == 0) ? 'conciliada' : 'borrador'
        );

        if ($this->bankreconciliations_model->save($data)) {
            $this->logs_model->logMessage("info", "Usuario " . $userId . " realizó conciliación del banco " . $bankAccountId);
            redirect(base_url() . 'sisvent/admin/bankaccounts/view/' . $bankAccountId);
        } else {
            $this->session->set_flashdata('error', 'No se pudo guardar la conciliación');
            redirect(base_url() . 'sisvent/admin/bankaccounts/reconciliation/' . $bankAccountId);
        }
    }

    // ========================================================================
    // CARGA DE EXTRACTO BANCARIO
    // ========================================================================

    public function uploadStatement($bankAccountId)
    {
        $bankAccount = $this->bankaccounts_model->getBankAccount($bankAccountId);
        if (!$bankAccount) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        }

        $data = array(
            'bankAccount' => $bankAccount
        );

        $this->load->view('sisvent/admin/bankaccounts/upload_statement', $data);
    }

    public function processStatement()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $bankAccountId = $this->input->post('bankAccountId');
        $statementDate = $this->input->post('statementDate');
        $periodMonth = $this->input->post('periodMonth');
        $periodYear = $this->input->post('periodYear');
        $notes = $this->input->post('notes');
        $userId = $this->session->userdata('user_data')['uname'];

        $bankAccount = $this->bankaccounts_model->getBankAccount($bankAccountId);
        if (!$bankAccount) {
            $this->session->set_flashdata('error', 'Cuenta bancaria no encontrada');
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        }

        // Upload file
        $config = array(
            'upload_path' => './uploads/statements/',
            'allowed_types' => 'xlsx|xls|csv',
            'max_size' => 5120, // 5MB
            'file_name' => 'statement_' . $bankAccountId . '_' . date('YmdHis')
        );

        // Create upload directory if it does not exist
        if (!is_dir('./uploads/statements/')) {
            mkdir('./uploads/statements/', 0755, true);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('statement_file')) {
            $this->session->set_flashdata('error', 'Error al cargar archivo: ' . $this->upload->display_errors('', ''));
            redirect(base_url() . 'sisvent/admin/bankaccounts/uploadStatement/' . $bankAccountId);
            return;
        }

        $uploadData = $this->upload->data();
        $filePath = 'uploads/statements/' . $uploadData['file_name'];

        // Create reconciliation record
        date_default_timezone_set("America/Bogota");
        $reconciliationData = array(
            'bankAccountId' => $bankAccountId,
            'reconciliationDate' => date('Y-m-d H:i:s'),
            'statementDate' => date('Y-m-d', strtotime($statementDate)),
            'bookBalance' => $bankAccount->currentBalance,
            'bankBalance' => 0,
            'reconciledBalance' => 0,
            'difference' => 0,
            'notes' => $notes,
            'reconciledBy' => $userId,
            'status' => 'borrador',
            'statementFilePath' => $filePath,
            'periodMonth' => (int)$periodMonth,
            'periodYear' => (int)$periodYear
        );

        $this->bankreconciliations_model->save($reconciliationData);
        $reconciliationId = $this->bankreconciliations_model->lastID();

        // Parse file with PhpSpreadsheet
        try {
            $fullPath = FCPATH . $filePath;
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row
            $statementLines = array();
            $rowNumber = 0;
            $totalDebits = 0;
            $totalCredits = 0;
            $lastBalance = 0;

            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header

                // Expected format: Date | Description | Reference | Debit | Credit | Balance
                $transactionDate = !empty($row[0]) ? date('Y-m-d', strtotime($row[0])) : null;
                if (!$transactionDate || $transactionDate === '1970-01-01') continue;

                $rowNumber++;
                $debit = abs((float)str_replace(array(',', '$', ' '), '', $row[3] ?? 0));
                $credit = abs((float)str_replace(array(',', '$', ' '), '', $row[4] ?? 0));
                $balance = (float)str_replace(array(',', '$', ' '), '', $row[5] ?? 0);

                $totalDebits += $debit;
                $totalCredits += $credit;
                $lastBalance = $balance;

                $statementLines[] = array(
                    'reconciliationId' => $reconciliationId,
                    'bankAccountId' => $bankAccountId,
                    'transactionDate' => $transactionDate,
                    'description' => trim($row[1] ?? ''),
                    'reference' => trim($row[2] ?? ''),
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $balance,
                    'matchStatus' => 'pendiente',
                    'rowNumber' => $rowNumber
                );
            }

            // Insert all lines
            if (!empty($statementLines)) {
                $this->bankstatementlines_model->saveBatch($statementLines);
            }

            // Update reconciliation with bank balance
            $this->bankreconciliations_model->update($reconciliationId, array(
                'bankBalance' => $lastBalance,
                'difference' => $lastBalance - (float)$bankAccount->currentBalance,
                'reconciledBalance' => min((float)$bankAccount->currentBalance, $lastBalance)
            ));

            // Run auto-match
            $matchCount = $this->reconciliation_lib->autoMatch($reconciliationId, $bankAccountId);

            $this->logs_model->logMessage("info", "Usuario $userId cargó extracto bancario para banco $bankAccountId. $rowNumber lineas, $matchCount matches automaticos.");
            $this->session->set_flashdata('success', "Extracto cargado: $rowNumber lineas procesadas, $matchCount conciliadas automaticamente.");
            redirect(base_url() . 'sisvent/admin/bankaccounts/reconciliationDetail/' . $reconciliationId);

        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Error al procesar archivo: ' . $e->getMessage());
            redirect(base_url() . 'sisvent/admin/bankaccounts/uploadStatement/' . $bankAccountId);
        }
    }

    // ========================================================================
    // DETALLE DE CONCILIACION
    // ========================================================================

    public function reconciliationDetail($reconciliationId)
    {
        $reconciliation = $this->bankreconciliations_model->getReconciliation($reconciliationId);
        if (!$reconciliation) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        }

        $bankAccount = $this->bankaccounts_model->getBankAccount($reconciliation->bankAccountId);
        $lines = $this->bankstatementlines_model->getLines($reconciliationId, -1);
        $stats = $this->bankstatementlines_model->getStats($reconciliationId);

        // Get unreconciled system movements for this bank account in the period
        $from = null;
        $to = null;
        if ($reconciliation->periodMonth && $reconciliation->periodYear) {
            $from = sprintf('%04d-%02d-01 00:00:00', $reconciliation->periodYear, $reconciliation->periodMonth);
            $to = date('Y-m-d 23:59:59', strtotime('last day of ' . sprintf('%04d-%02d-01', $reconciliation->periodYear, $reconciliation->periodMonth)));
        }

        $systemMovements = $this->cashmovements_model->getMovementsBySource('banco', $reconciliation->bankAccountId, $from, $to);

        $data = array(
            'reconciliation' => $reconciliation,
            'bankAccount' => $bankAccount,
            'lines' => $lines,
            'stats' => $stats,
            'systemMovements' => $systemMovements
        );

        $this->load->view('sisvent/admin/bankaccounts/reconciliation_detail', $data);
    }

    // ========================================================================
    // MATCH / UNMATCH AJAX
    // ========================================================================

    public function matchItems()
    {
        if (!$this->input->is_ajax_request()) {
            show_error('Acceso directo no permitido', 403);
        }

        $lineId = $this->input->post('lineId');
        $movementId = $this->input->post('movementId');
        $userId = $this->session->userdata('user_data')['uname'];

        if (empty($lineId) || empty($movementId)) {
            echo json_encode(array('success' => false, 'message' => 'Datos incompletos'));
            return;
        }

        // Update bank statement line
        $this->bankstatementlines_model->update($lineId, array(
            'matchedMovementId' => $movementId,
            'matchStatus' => 'manual',
            'matchedAt' => date('Y-m-d H:i:s'),
            'matchedBy' => $userId
        ));

        // Mark cash movement as reconciled
        $this->cashmovements_model->update($movementId, array(
            'reconciled' => 1,
            'reconciledLineId' => $lineId
        ));

        // Get line to find reconciliation ID and update stats
        $line = $this->bankstatementlines_model->getLine($lineId);
        if ($line) {
            $stats = $this->bankstatementlines_model->getStats($line->reconciliationId);
            $this->bankreconciliations_model->update($line->reconciliationId, array(
                'totalMatched' => (int)$stats->matched + (int)$stats->manual,
                'totalUnmatchedBank' => (int)$stats->pending + (int)$stats->unmatched_bank
            ));
        }

        echo json_encode(array('success' => true, 'message' => 'Movimiento conciliado correctamente'));
    }

    public function unmatchItem()
    {
        if (!$this->input->is_ajax_request()) {
            show_error('Acceso directo no permitido', 403);
        }

        $lineId = $this->input->post('lineId');
        $userId = $this->session->userdata('user_data')['uname'];

        if (empty($lineId)) {
            echo json_encode(array('success' => false, 'message' => 'ID de linea requerido'));
            return;
        }

        $line = $this->bankstatementlines_model->getLine($lineId);
        if (!$line) {
            echo json_encode(array('success' => false, 'message' => 'Linea no encontrada'));
            return;
        }

        // Unmatch the cash movement
        if ($line->matchedMovementId) {
            $this->cashmovements_model->update($line->matchedMovementId, array(
                'reconciled' => 0,
                'reconciledLineId' => null
            ));
        }

        // Reset bank statement line
        $this->bankstatementlines_model->update($lineId, array(
            'matchedMovementId' => null,
            'matchStatus' => 'pendiente',
            'matchedAt' => null,
            'matchedBy' => null
        ));

        // Update stats
        $stats = $this->bankstatementlines_model->getStats($line->reconciliationId);
        $this->bankreconciliations_model->update($line->reconciliationId, array(
            'totalMatched' => (int)$stats->matched + (int)$stats->manual,
            'totalUnmatchedBank' => (int)$stats->pending + (int)$stats->unmatched_bank
        ));

        echo json_encode(array('success' => true, 'message' => 'Conciliacion deshecha'));
    }

    // ========================================================================
    // SUGERENCIAS AJAX
    // ========================================================================

    public function suggestMatches()
    {
        if (!$this->input->is_ajax_request()) {
            show_error('Acceso directo no permitido', 403);
        }

        $lineId = $this->input->post('lineId');
        $bankAccountId = $this->input->post('bankAccountId');

        if (empty($lineId) || empty($bankAccountId)) {
            echo json_encode(array('success' => false, 'message' => 'Datos incompletos'));
            return;
        }

        $suggestions = $this->reconciliation_lib->suggestMatches($lineId, $bankAccountId);

        echo json_encode(array('success' => true, 'suggestions' => $suggestions));
    }

    // ========================================================================
    // REPORTE DE CONCILIACION
    // ========================================================================

    public function reconciliationReport($reconciliationId)
    {
        $reconciliation = $this->bankreconciliations_model->getReconciliation($reconciliationId);
        if (!$reconciliation) {
            redirect(base_url() . 'sisvent/admin/bankaccounts');
        }

        $bankAccount = $this->bankaccounts_model->getBankAccount($reconciliation->bankAccountId);
        $lines = $this->bankstatementlines_model->getLines($reconciliationId, -1);
        $stats = $this->bankstatementlines_model->getStats($reconciliationId);

        // Separate matched and unmatched lines
        $matchedLines = array();
        $unmatchedLines = array();
        foreach ($lines as $line) {
            if ($line->matchStatus === 'matched' || $line->matchStatus === 'manual') {
                $matchedLines[] = $line;
            } else {
                $unmatchedLines[] = $line;
            }
        }

        // Get unreconciled system movements
        $from = null;
        $to = null;
        if ($reconciliation->periodMonth && $reconciliation->periodYear) {
            $from = sprintf('%04d-%02d-01 00:00:00', $reconciliation->periodYear, $reconciliation->periodMonth);
            $to = date('Y-m-d 23:59:59', strtotime('last day of ' . sprintf('%04d-%02d-01', $reconciliation->periodYear, $reconciliation->periodMonth)));
        }
        $allMovements = $this->cashmovements_model->getMovementsBySource('banco', $reconciliation->bankAccountId, $from, $to);
        $unreconciledMovements = array();
        foreach ($allMovements as $mov) {
            if (empty($mov->reconciled) || $mov->reconciled == 0) {
                $unreconciledMovements[] = $mov;
            }
        }

        $data = array(
            'reconciliation' => $reconciliation,
            'bankAccount' => $bankAccount,
            'stats' => $stats,
            'matchedLines' => $matchedLines,
            'unmatchedLines' => $unmatchedLines,
            'unreconciledMovements' => $unreconciledMovements
        );

        $this->load->view('sisvent/admin/bankaccounts/reconciliation_report', $data);
    }
}
