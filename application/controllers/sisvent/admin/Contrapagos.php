<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;

class Contrapagos extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->backend_lib->controlModule('envios');
        $this->load->model('contrapago_model');
        $this->load->model('invoices_model');
        $this->load->model('payments_model');
        $this->load->model('products_model');
        $this->load->model('vendors_model');
    }

    /**
     * Dashboard: lista de lotes importados
     */
    public function index() {
        $this->load->model('bankaccounts_model');
        $batches = $this->contrapago_model->getBatches();

        // Calcular KPIs
        $totalLotes = count($batches);
        $lotesPendientes = 0;
        $lotesRegistrados = 0;
        $totalBruto = 0;
        $totalRegistrado = 0;

        foreach ($batches as $b) {
            if ($b->status === 'conciliado' || $b->status === 'importado') $lotesPendientes++;
            if ($b->status === 'registrado') {
                $lotesRegistrados++;
                $totalRegistrado += (float)$b->total_valor;
            }
            $totalBruto += (float)$b->total_valor;
        }

        $data = array(
            'batches' => $batches,
            'bank_accounts' => $this->bankaccounts_model->getBankAccounts(),
            'role' => $this->session->userdata('user_data')['role'],
            'kpi' => array(
                'total_lotes' => $totalLotes,
                'pendientes' => $lotesPendientes,
                'registrados' => $lotesRegistrados,
                'total_bruto' => $totalBruto,
                'total_registrado' => $totalRegistrado,
            )
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

        $this->load->model('bankaccounts_model');
        $data = array(
            'batch' => $batch,
            'payments' => $this->contrapago_model->getPayments($id),
            'bank_accounts' => $this->bankaccounts_model->getBankAccounts(),
            'role' => $this->session->userdata('user_data')['role']
        );
        $this->load->view('sisvent/admin/contrapagos/view', $data);
    }

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
     * Recibe: bank_account_id, numero_movimiento, concepto, observaciones
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

        $this->load->model('bankaccounts_model');
        $bankId = $this->input->post('bank_account_id');
        $numeroMovimiento = trim($this->input->post('numero_movimiento'));
        $concepto = trim($this->input->post('concepto')) ?: 'contrapago';
        $observaciones = trim($this->input->post('observaciones'));

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
        $fechaDeposito = trim($this->input->post('fecha_deposito'));
        $fechaPago = ($fechaDeposito ?: ($batch->fecha_pago ?: date('Y-m-d'))) . ' ' . date('H:i:s');

        // Obtener observación con descuentos si hay
        $cpPayments = $this->contrapago_model->getPayments($batchId);
        $descuentos = '';
        foreach ($cpPayments as $p) {
            if (!empty($p->observacion) && stripos($p->observacion, 'Dcto') !== false) {
                $descuentos = $p->observacion;
                break;
            }
        }

        // Calcular 4x1000 y neto
        $totalBruto = $batch->total_valor;
        $impuesto4x1000 = round($totalBruto * 0.004);
        $netoConsignado = $totalBruto - $impuesto4x1000;

        // Construir descripción según concepto
        $conceptLabels = array(
            'contrapago' => 'Pago contrapago Interrapidísimo',
            'flete' => 'Pago fletes Interrapidísimo',
            'impuesto' => 'Impuestos/Retenciones Interrapidísimo',
        );
        $conceptLabel = isset($conceptLabels[$concepto]) ? $conceptLabels[$concepto] : $concepto;

        $description = "{$conceptLabel} - {$batch->sheet_name} ({$batch->total_guias} guías)";
        if ($numeroMovimiento) $description .= " | Mov: {$numeroMovimiento}";
        if ($descuentos) $description .= " | {$descuentos}";
        if ($observaciones) $description .= " | {$observaciones}";
        $description .= " | Bruto: $" . number_format($totalBruto, 0, ',', '.') . " - 4x1000: $" . number_format($impuesto4x1000, 0, ',', '.');

        // Número de documento: usar el del banco si lo dieron, sino generar
        $docNumber = $numeroMovimiento ?: ('Contrapago #' . $batchId);

        // 1. Registrar movimiento de caja (ingreso neto al banco)
        $this->db->insert('cash_movements', array(
            'sourceType' => 'banco',
            'sourceId' => $bankAccount->idBankAccount,
            'movementType' => 'ingreso',
            'amount' => $netoConsignado,
            'concept' => $description,
            'category' => 'contrapago_inter',
            'documentNumber' => $docNumber,
            'movementDate' => $fechaPago,
            'status' => 'activo',
            'referenceType' => 'contrapago',
            'referenceId' => $batchId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        $movId = $this->db->insert_id();

        // Actualizar saldo del banco
        $this->db->set('currentBalance', 'currentBalance + ' . $netoConsignado, false);
        $this->db->where('idBankAccount', $bankAccount->idBankAccount);
        $this->db->update('bank_accounts');

        // 2. Crear pagos individuales por cada factura y calcular comisiones
        $facturasPagadas = 0;
        $vendorCommissions = array();

        foreach ($cpPayments as $p) {
            if (!$p->invoice_id || $p->status !== 'conciliado') continue;

            $invoice = $this->invoices_model->getInvoice($p->invoice_id);
            if (!$invoice) continue;
            // Saltear facturas ya pagadas (state 2) o anuladas (state 3)
            if ($invoice->state == 2 || $invoice->state == 3) continue;
            // Evitar doble pago: verificar si ya existe un pago contrapago para esta factura
            $existingPay = $this->db->where('invoiceId', $p->invoice_id)
                ->where('paymentMethod', 5)->where('deleted', 0)
                ->get('payments')->num_rows();
            if ($existingPay > 0) continue;

            // Crear registro de pago en tabla payments
            $paymentData = array(
                'invoiceId' => $p->invoice_id,
                'clientId' => $invoice->clientId,
                'vendorId' => $invoice->vendorId,
                'paymentMethod' => 5,
                'payment' => $p->valorTotal,
                'date' => $fechaPago,
                'comments' => $conceptLabel . ' - Guía ' . $p->numeroGuia . ' - Lote #' . $batchId . ($numeroMovimiento ? ' - Mov: ' . $numeroMovimiento : ''),
                'originType' => 'banco',
                'originId' => $bankAccount->idBankAccount,
                'created_at' => date('Y-m-d H:i:s'),
            );
            $this->payments_model->save($paymentData);
            $paymentId = $this->db->insert_id();

            // Actualizar factura
            $acum = $this->payments_model->getInvoicePayment($p->invoice_id);
            $totalPaid = $acum ? $acum->payment : $p->valorTotal;
            $newState = ($totalPaid + $invoice->discount >= round($invoice->total, 2)) ? 2 : 1;

            $this->invoices_model->update($p->invoice_id, array(
                'payment' => $totalPaid,
                'state' => $newState,
            ));

            $this->payments_model->update($paymentId, array('cashMovementId' => $movId));

            $facturasPagadas++;

            // 3. Acumular comisión del vendedor
            $vendorId = $invoice->vendorId;
            if (!isset($vendorCommissions[$vendorId])) {
                $vend = $this->vendors_model->getVendor($vendorId);
                $vendorCommissions[$vendorId] = array(
                    'name' => $vend ? $vend->name : $vendorId,
                    'by_commission' => $vend ? $vend->by_commission : 0,
                    'commission_perc' => $vend ? $vend->commission_perc : 10,
                    'new_settlement_method' => $vend ? (isset($vend->new_settlement_method) ? $vend->new_settlement_method : 0) : 0,
                    'total_ventas' => 0,
                    'comision' => 0,
                    'facturas' => array(),
                );
            }

            $vendorCommissions[$vendorId]['total_ventas'] += $invoice->total;
            $vendorCommissions[$vendorId]['facturas'][] = $invoice->idInvoice;

            $comision = $this->_calcularComisionFactura($invoice, $vendorCommissions[$vendorId]);
            $vendorCommissions[$vendorId]['comision'] += $comision;
        }

        // 4. Marcar lote como registrado
        $this->contrapago_model->updateBatch($batchId, array(
            'cash_movement_id' => $movId,
            'status' => 'registrado',
        ));

        // 5. Construir resumen
        $comisionMsg = '';
        if (!empty($vendorCommissions)) {
            $comisionMsg = ' | Comisiones: ';
            $parts = array();
            foreach ($vendorCommissions as $vid => $vc) {
                $parts[] = $vc['name'] . ': $' . number_format($vc['comision'], 0, ',', '.') . ' (' . count($vc['facturas']) . ' fact.)';
            }
            $comisionMsg .= implode(', ', $parts);
        }

        echo json_encode(array(
            'success' => true,
            'message' => 'Ingreso neto de $' . number_format($netoConsignado, 0, ',', '.')
                . ' registrado en ' . $bankAccount->bankName
                . ($numeroMovimiento ? ' (Mov: ' . $numeroMovimiento . ')' : '')
                . ' — Bruto: $' . number_format($totalBruto, 0, ',', '.')
                . ' - 4x1000: $' . number_format($impuesto4x1000, 0, ',', '.') . '. '
                . $facturasPagadas . ' pagos creados.' . $comisionMsg,
            'movement_id' => $movId,
            'payments_created' => $facturasPagadas,
            'vendor_commissions' => $vendorCommissions,
        ));
    }

    /**
     * Calcular comisión de una factura para un vendedor
     */
    private function _calcularComisionFactura($invoice, $vendorConfig) {
        $details = $this->invoices_model->getDetails($invoice->idInvoice);
        $not_settle_total = 0;
        foreach ($details as $detail) {
            if (isset($detail->not_settle) && $detail->not_settle) {
                $not_settle_total += $detail->subtotal;
            }
        }

        $base = $invoice->total - $not_settle_total;

        if ($vendorConfig['by_commission']) {
            $perc = $vendorConfig['commission_perc'] / 100;

            if ($vendorConfig['new_settlement_method']) {
                foreach ($details as $detail) {
                    $product = $this->products_model->getProduct($detail->productId);
                    if ($product && $detail->unit < $product->price) {
                        $perc = 0.05;
                        break;
                    }
                }
            }

            return $base * $perc;
        }

        if (isset($invoice->list_price) && $invoice->list_price) {
            return ($base * 0.7) * 0.05;
        }

        if ($invoice->discount > 0) {
            return ($base - $invoice->discount) * ($invoice->discount_perc / 100);
        }

        if (isset($invoice->e_commerce) && $invoice->e_commerce) {
            return $base * 0.15;
        }

        $comision = 0;
        foreach ($details as $detail) {
            if (isset($detail->not_settle) && $detail->not_settle) continue;
            $comision += ($detail->subtotal - ($detail->quantity * $detail->base));
        }
        return $comision;
    }
}
