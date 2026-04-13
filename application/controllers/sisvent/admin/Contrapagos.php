<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;

class Contrapagos extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->backend_lib->controlModule('envios');
        $this->load->model('contrapago_model');
    }

    /**
     * Dashboard: lista de lotes importados
     */
    public function index() {
        $this->load->model('bankaccounts_model');
        $data = array(
            'batches' => $this->contrapago_model->getBatches(),
            'bank_accounts' => $this->bankaccounts_model->getBankAccounts(),
            'role' => $this->session->userdata('user_data')['role']
        );
        $this->load->view('sisvent/admin/contrapagos/index', $data);
    }

    /**
     * Formulario de subida + procesamiento del Excel
     */
    public function upload() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url() . 'sisvent/admin/contrapagos');
            return;
        }

        $this->outh_model->CSRFVerify();

        // Validar archivo
        if (empty($_FILES['excel_file']['name'])) {
            $this->session->set_flashdata('contrapago_error', 'No se seleccionó ningún archivo');
            redirect(base_url() . 'sisvent/admin/contrapagos');
            return;
        }

        $file = $_FILES['excel_file']['tmp_name'];
        $filename = $_FILES['excel_file']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if (!in_array(strtolower($ext), array('xlsx', 'xls'))) {
            $this->session->set_flashdata('contrapago_error', 'Solo se permiten archivos Excel (.xlsx, .xls)');
            redirect(base_url() . 'sisvent/admin/contrapagos');
            return;
        }

        try {
            $spreadsheet = IOFactory::load($file);
            $uid = $this->session->userdata('user_data')['uname'];
            $totalImported = 0;
            $batchIds = array();

            // Procesar cada hoja del Excel
            foreach ($spreadsheet->getSheetNames() as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                $highRow = $sheet->getHighestRow();

                // Buscar fila de encabezados (contiene "Numero de Guia")
                $headerRow = 0;
                for ($r = 1; $r <= min(10, $highRow); $r++) {
                    $cellA = trim((string)$sheet->getCell('A' . $r)->getValue());
                    if (stripos($cellA, 'Numero de Guia') !== false || stripos($cellA, 'Numero') !== false) {
                        $headerRow = $r;
                        break;
                    }
                }

                if ($headerRow == 0) continue;

                // Leer datos
                $rows = array();
                $totalValor = 0;
                $fechaPago = null;
                $banco = null;

                for ($r = $headerRow + 1; $r <= $highRow; $r++) {
                    $guia = trim((string)$sheet->getCell('A' . $r)->getValue());
                    if (empty($guia) || !is_numeric($guia)) continue;

                    $fechaVenta = $sheet->getCell('B' . $r)->getValue();
                    if ($fechaVenta && is_numeric($fechaVenta)) {
                        $fechaVenta = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fechaVenta)->format('Y-m-d H:i:s');
                    }

                    $valor = (float)$sheet->getCell('C' . $r)->getValue();
                    $nombre = trim((string)$sheet->getCell('D' . $r)->getValue());
                    $conciliacion = trim((string)$sheet->getCell('E' . $r)->getValue());

                    $fPago = $sheet->getCell('F' . $r)->getValue();
                    if ($fPago && is_numeric($fPago)) {
                        $fPago = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fPago)->format('Y-m-d');
                    } elseif ($fPago) {
                        $fPago = date('Y-m-d', strtotime($fPago));
                    }

                    $valorPago = (float)$sheet->getCell('G' . $r)->getValue();
                    $bancoCell = trim((string)$sheet->getCell('H' . $r)->getValue());
                    $obs = trim((string)$sheet->getCell('I' . $r)->getValue());

                    if ($fPago) $fechaPago = $fPago;
                    if ($bancoCell) $banco = $bancoCell;
                    $totalValor += $valor;

                    $rows[] = array(
                        'numeroGuia' => $guia,
                        'fechaVenta' => $fechaVenta,
                        'valorTotal' => $valor,
                        'nombreDestinatario' => $nombre,
                        'conciliacion' => $conciliacion,
                        'fechaPago' => $fPago,
                        'valorPago' => $valorPago,
                        'banco' => $bancoCell,
                        'observacion' => $obs,
                    );
                }

                if (empty($rows)) continue;

                // Crear lote
                $batchId = $this->contrapago_model->saveBatch(array(
                    'filename' => $filename,
                    'sheet_name' => $sheetName,
                    'total_guias' => count($rows),
                    'total_valor' => $totalValor,
                    'fecha_pago' => $fechaPago,
                    'banco' => $banco,
                    'created_by' => $uid,
                ));

                // Asignar batch_id a cada fila
                foreach ($rows as &$row) {
                    $row['batch_id'] = $batchId;
                }
                unset($row);

                $this->contrapago_model->savePaymentsBatch($rows);

                // Auto-match con shipping_guides
                $matchResult = $this->contrapago_model->matchGuides($batchId);

                $totalImported += count($rows);
                $batchIds[] = $batchId;
            }

            if ($totalImported > 0) {
                $this->session->set_flashdata('contrapago_success', "Se importaron {$totalImported} guías de " . count($batchIds) . " hoja(s). Cruces automáticos realizados.");
            } else {
                $this->session->set_flashdata('contrapago_error', 'No se encontraron datos válidos en el archivo');
            }

        } catch (Exception $e) {
            $this->session->set_flashdata('contrapago_error', 'Error al procesar el archivo: ' . $e->getMessage());
        }

        redirect(base_url() . 'sisvent/admin/contrapagos');
    }

    /**
     * Ver detalle de un lote importado
     */
    public function view($id) {
        $batch = $this->contrapago_model->getBatch($id);
        if (!$batch) show_404();

        $data = array(
            'batch' => $batch,
            'payments' => $this->contrapago_model->getPayments($id),
            'role' => $this->session->userdata('user_data')['role']
        );
        $this->load->view('sisvent/admin/contrapagos/view', $data);
    }

    /**
     * AJAX: Registrar ingreso en banco (crear cash_movement)
     */
    /**
     * AJAX: Eliminar un lote y sus pagos
     */
    public function delete($batchId) {
        header('Content-Type: application/json');

        $batch = $this->contrapago_model->getBatch($batchId);
        if (!$batch) {
            echo json_encode(array('success' => false, 'message' => 'Lote no encontrado'));
            return;
        }
        if ($batch->status === 'registrado') {
            echo json_encode(array('success' => false, 'message' => 'No se puede eliminar un lote ya registrado en banco'));
            return;
        }

        $this->db->where('batch_id', $batchId)->delete('contrapago_payments');
        $this->db->where('id', $batchId)->delete('contrapago_batches');

        echo json_encode(array('success' => true, 'message' => 'Lote eliminado correctamente'));
    }

    /**
     * AJAX: Reversar un lote registrado (deshacer movimiento banco + facturas)
     */
    public function reversar($batchId) {
        header('Content-Type: application/json');

        $batch = $this->contrapago_model->getBatch($batchId);
        if (!$batch) {
            echo json_encode(array('success' => false, 'message' => 'Lote no encontrado'));
            return;
        }
        if ($batch->status !== 'registrado') {
            echo json_encode(array('success' => false, 'message' => 'Este lote no está registrado'));
            return;
        }

        $this->load->model('bankaccounts_model');

        // Reversar movimiento de caja
        if ($batch->cash_movement_id) {
            $mov = $this->db->where('idMovement', $batch->cash_movement_id)->get('cash_movements')->row();
            if ($mov) {
                // Restar saldo del banco
                $this->db->set('currentBalance', 'currentBalance - ' . $mov->amount, false);
                $this->db->where('idBankAccount', $mov->sourceId);
                $this->db->update('bank_accounts');

                // Eliminar movimiento
                $this->db->where('idMovement', $batch->cash_movement_id)->delete('cash_movements');
            }
        }

        // Reversar facturas a pendiente
        $payments = $this->contrapago_model->getPayments($batchId);
        $facturasReversadas = 0;
        foreach ($payments as $p) {
            if ($p->invoice_id && $p->status === 'conciliado') {
                $this->db->where('idInvoice', $p->invoice_id)->update('invoices', array(
                    'state' => 0,
                    'payment' => 0,
                ));
                $facturasReversadas++;
            }
        }

        // Volver lote a conciliado
        $this->contrapago_model->updateBatch($batchId, array(
            'cash_movement_id' => null,
            'status' => 'conciliado',
        ));

        echo json_encode(array(
            'success' => true,
            'message' => 'Lote reversado. Movimiento bancario eliminado y ' . $facturasReversadas . ' facturas devueltas a pendiente.'
        ));
    }

    /**
     * AJAX: Registrar ingreso en banco (crear cash_movement)
     */
    public function registrarIngreso($batchId) {
        header('Content-Type: application/json');

        $batch = $this->contrapago_model->getBatch($batchId);
        if (!$batch) {
            echo json_encode(array('success' => false, 'message' => 'Lote no encontrado'));
            return;
        }
        if ($batch->status === 'registrado') {
            echo json_encode(array('success' => false, 'message' => 'Este lote ya fue registrado en el banco'));
            return;
        }

        // Buscar cuenta bancaria (por POST o auto-detect Bancolombia)
        $this->load->model('bankaccounts_model');
        $bankId = $this->input->post('bank_account_id');

        if ($bankId) {
            $bankAccount = $this->bankaccounts_model->getBankAccount($bankId);
        } else {
            $bankAccount = $this->db->like('bankName', 'bancolombia', 'both')
                ->where('deleted', 0)
                ->get('bank_accounts')->row();
        }

        if (!$bankAccount) {
            echo json_encode(array('success' => false, 'message' => 'No se encontró la cuenta bancaria. Créala primero en Bancos.'));
            return;
        }

        date_default_timezone_set("America/Bogota");
        $uid = $this->session->userdata('user_data')['uname'];

        // Obtener observación con descuentos si hay
        $payments = $this->contrapago_model->getPayments($batchId);
        $descuentos = '';
        foreach ($payments as $p) {
            if (!empty($p->observacion) && stripos($p->observacion, 'Dcto') !== false) {
                $descuentos = $p->observacion;
                break;
            }
        }

        // Calcular 4x1000 y neto
        $totalBruto = $batch->total_valor;
        $impuesto4x1000 = round($totalBruto * 0.004);
        $netoConsignado = $totalBruto - $impuesto4x1000;

        $description = "Pago contrapago Interrapidísimo - {$batch->sheet_name} ({$batch->total_guias} guías)";
        if ($descuentos) $description .= " | {$descuentos}";
        $description .= " | Bruto: $" . number_format($totalBruto, 0, ',', '.') . " - 4x1000: $" . number_format($impuesto4x1000, 0, ',', '.');

        // Registrar movimiento de caja (ingreso neto al banco)
        $movData = array(
            'sourceType' => 'banco',
            'sourceId' => $bankAccount->idBankAccount,
            'movementType' => 'ingreso',
            'amount' => $netoConsignado,
            'concept' => $description,
            'category' => 'contrapago_inter',
            'documentNumber' => 'Contrapago #' . $batchId,
            'movementDate' => ($batch->fecha_pago ?: date('Y-m-d')) . ' ' . date('H:i:s'),
            'status' => 'activo',
            'referenceType' => 'contrapago',
            'referenceId' => $batchId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );

        $this->db->insert('cash_movements', $movData);
        $movId = $this->db->insert_id();

        // Actualizar saldo del banco con el neto
        $this->db->set('currentBalance', 'currentBalance + ' . $netoConsignado, false);
        $this->db->where('idBankAccount', $bankAccount->idBankAccount);
        $this->db->update('bank_accounts');

        // Marcar facturas como pagadas
        $facturasPagadas = 0;
        $payments = $this->contrapago_model->getPayments($batchId);
        foreach ($payments as $p) {
            if ($p->invoice_id && $p->status === 'conciliado') {
                $invoice = $this->db->where('idInvoice', $p->invoice_id)->get('invoices')->row();
                if ($invoice && $invoice->state != 2 && $invoice->state != 3) {
                    $this->db->where('idInvoice', $p->invoice_id)->update('invoices', array(
                        'state' => 2,
                        'payment' => $p->valorTotal,
                    ));
                    $facturasPagadas++;
                }
            }
        }

        // Marcar lote como registrado
        $this->contrapago_model->updateBatch($batchId, array(
            'cash_movement_id' => $movId,
            'status' => 'registrado',
        ));

        echo json_encode(array(
            'success' => true,
            'message' => 'Ingreso neto de $' . number_format($netoConsignado, 0, ',', '.') . ' registrado en ' . $bankAccount->bankName . ' (Bruto: $' . number_format($totalBruto, 0, ',', '.') . ' - 4x1000: $' . number_format($impuesto4x1000, 0, ',', '.') . '). ' . $facturasPagadas . ' facturas marcadas como pagadas.',
            'movement_id' => $movId
        ));
    }
}
