<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;

class Contrapagos extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Acepta cualquiera de los 3 permisos del módulo
        if (!has_permission('contrapagos') && !has_permission('facturas_inter') && !has_permission('entre_companias') && !has_permission('envios')) {
            redirect(base_url() . 'sisvent/dashboard');
        }
        $this->load->model('contrapago_model');
        $this->load->model('contrapago_invoice_model');
        $this->load->model('intercompany_model');
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
            $totalDuplicates = 0;
            $batchIds = array();
            $hojasSaltadas = array();
            $vinculosFacturas = array();

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

                // Anti-duplicado: hash basado en el conjunto de guías de la hoja.
                // Independiente del nombre de archivo/hoja: si las mismas guías ya fueron
                // importadas en otro lote, no se vuelve a crear.
                $importHash = $this->contrapago_invoice_model->calcSheetHash(
                    array_column($rows, 'numeroGuia')
                );
                $existingBatch = $this->db->where('import_hash', $importHash)
                    ->get('contrapago_batches')->row();
                if ($existingBatch) {
                    $hojasSaltadas[] = "$sheetName (mismas guías que lote #{$existingBatch->id})";
                    continue;
                }

                // Crear lote
                $batchId = $this->contrapago_model->saveBatch(array(
                    'filename' => $filename,
                    'sheet_name' => $sheetName,
                    'total_guias' => count($rows),
                    'total_valor' => $totalValor,
                    'fecha_pago' => $fechaPago,
                    'banco' => $banco,
                    'created_by' => $uid,
                    'import_hash' => $importHash,
                ));

                // Asignar batch_id a cada fila
                foreach ($rows as &$row) {
                    $row['batch_id'] = $batchId;
                }
                unset($row);

                $this->contrapago_model->savePaymentsBatch($rows);

                // Auto-match con shipping_guides
                $matchResult = $this->contrapago_model->matchGuides($batchId);
                $totalDuplicates += isset($matchResult['duplicates']) ? (int)$matchResult['duplicates'] : 0;

                // Vincular con facturas Inter mencionadas en observaciones
                $vinculos = $this->contrapago_invoice_model->linkBatchToInterInvoices($batchId, $uid);
                if (!empty($vinculos)) $vinculosFacturas = array_merge($vinculosFacturas, $vinculos);

                $totalImported += count($rows);
                $batchIds[] = $batchId;
            }

            if ($totalImported > 0 || !empty($hojasSaltadas)) {
                $msgParts = array();
                if ($totalImported > 0) {
                    $msgParts[] = "✅ Se importaron {$totalImported} guías de " . count($batchIds) . " hoja(s) nueva(s).";
                }
                if (!empty($hojasSaltadas)) {
                    $msgParts[] = "⏭️ " . count($hojasSaltadas) . " hoja(s) saltadas (ya estaban importadas): " . implode(', ', $hojasSaltadas);
                }
                if ($totalDuplicates > 0) {
                    $msgParts[] = "⚠️ {$totalDuplicates} guía(s) ya cobradas previamente (marcadas como duplicadas).";
                }
                if (!empty($vinculosFacturas)) {
                    $vinculadas = 0; $sinImportar = 0;
                    foreach ($vinculosFacturas as $v) {
                        if (isset($v['invoice_id'])) $vinculadas++;
                        else $sinImportar++;
                    }
                    if ($vinculadas > 0) $msgParts[] = "🔗 {$vinculadas} vínculo(s) con facturas Inter creados.";
                    if ($sinImportar > 0) {
                        $facsPendientes = array();
                        foreach ($vinculosFacturas as $v) {
                            if (empty($v['invoice_id'])) $facsPendientes[$v['factura']] = true;
                        }
                        $msgParts[] = "📌 Facturas Inter mencionadas pero NO importadas aún: #" . implode(', #', array_keys($facsPendientes)) . ". Súbelas para vincular.";
                    }
                }
                $this->session->set_flashdata('contrapago_success', implode(' ', $msgParts));
            } else {
                $this->session->set_flashdata('contrapago_error', 'No se encontraron datos válidos en el archivo');
            }

        } catch (Exception $e) {
            $this->session->set_flashdata('contrapago_error', 'Error al procesar el archivo: ' . $e->getMessage());
        }

        redirect(base_url() . 'sisvent/admin/contrapagos');
    }

    /**
     * Listado de facturas de Inter (fletes que MAM debe pagar)
     */
    public function invoices() {
        $invoices = $this->contrapago_invoice_model->getInvoices();

        // KPIs
        $totalPendiente = 0; $totalDescontado = 0; $countPendiente = 0; $countDescontado = 0;
        foreach ($invoices as $inv) {
            if ($inv->status === 'pendiente') { $totalPendiente += (float)$inv->valor_total; $countPendiente++; }
            elseif ($inv->status === 'descontada') { $totalDescontado += (float)$inv->valor_total; $countDescontado++; }
        }

        $data = array(
            'invoices' => $invoices,
            'role' => $this->session->userdata('user_data')['role'],
            'kpi' => array(
                'count_pendiente' => $countPendiente,
                'count_descontado' => $countDescontado,
                'total_pendiente' => $totalPendiente,
                'total_descontado' => $totalDescontado,
            )
        );
        $this->load->view('sisvent/admin/contrapagos/invoices', $data);
    }

    /**
     * Detalle de una factura Inter con items y guias cruzadas
     */
    public function invoiceDetail($id) {
        $invoice = $this->contrapago_invoice_model->getInvoice($id);
        if (!$invoice) show_404();

        // Items con datos enriquecidos del sistema
        $items = $this->db->select('ii.*, sg.invoiceId as sys_invoice_id, i.total as sys_invoice_total, c.name as client_name, u.name as vendor_name')
            ->from('contrapago_invoice_items ii')
            ->join('shipping_guides sg', 'sg.id = ii.shipping_guide_id', 'left')
            ->join('invoices i', 'i.idInvoice = sg.invoiceId', 'left')
            ->join('clients c', 'c.idClient = i.clientId', 'left')
            ->join('users u', 'u.idUser = i.vendorId', 'left')
            ->where('ii.invoice_id', $id)
            ->order_by('ii.id', 'ASC')
            ->get()->result();

        $batch = null;
        if ($invoice->descontada_en_batch_id) {
            $batch = $this->contrapago_model->getBatch($invoice->descontada_en_batch_id);
        }

        // Pagos parciales: lista de batches que han compensado esta factura
        $invoicePayments = $this->db->select('cip.*, b.sheet_name, b.fecha_pago, b.filename, b.status as batch_status, b.id as batch_id')
            ->from('contrapago_invoice_payments cip')
            ->join('contrapago_batches b', 'b.id = cip.batch_id')
            ->where('cip.invoice_id', $id)
            ->order_by('b.fecha_pago', 'ASC')
            ->get()->result();

        $totalCobrado = 0;
        foreach ($invoicePayments as $ip) $totalCobrado += (float)$ip->monto_cobrado;
        $saldoPendiente = max(0, (float)$invoice->valor_total - $totalCobrado);

        $data = array(
            'invoice' => $invoice,
            'items' => $items,
            'batch' => $batch,
            'invoice_payments' => $invoicePayments,
            'total_cobrado' => $totalCobrado,
            'saldo_pendiente' => $saldoPendiente,
            'role' => $this->session->userdata('user_data')['role']
        );
        $this->load->view('sisvent/admin/contrapagos/invoice_detail', $data);
    }

    /**
     * Importar factura Inter (archivo CORTE)
     */
    public function uploadInvoice() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url() . 'sisvent/admin/contrapagos/invoices');
            return;
        }
        $this->outh_model->CSRFVerify();

        if (empty($_FILES['excel_file']['name'])) {
            $this->session->set_flashdata('contrapago_error', 'No se seleccionó ningún archivo');
            redirect(base_url() . 'sisvent/admin/contrapagos/invoices');
            return;
        }

        $file = $_FILES['excel_file']['tmp_name'];
        $filename = $_FILES['excel_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, array('xlsx', 'xls'))) {
            $this->session->set_flashdata('contrapago_error', 'Solo se permiten archivos Excel (.xlsx, .xls)');
            redirect(base_url() . 'sisvent/admin/contrapagos/invoices');
            return;
        }

        try {
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();

            // Inter envía el detalle en DOS formatos distintos. Detectamos
            // cuál es y parseamos en consecuencia.
            $parsed = $this->_parseInterInvoiceFile($sheet);
            if (!is_array($parsed) || !empty($parsed['error'])) {
                $errMsg = is_array($parsed) ? $parsed['error'] : 'Formato de factura Inter no reconocido. Esperaba formato CORTE (J1=#fact, headers fila 2) o SOPORTE DETALLADO (sheet name = #fact, headers fila 11).';
                $this->session->set_flashdata('contrapago_error', $errMsg);
                redirect(base_url() . 'sisvent/admin/contrapagos/invoices');
                return;
            }

            $numeroFactura  = $parsed['numero_factura'];
            $items          = $parsed['items'];
            $totalTransporte = $parsed['total_transporte'];
            $totalSeguro    = $parsed['total_seguro'];
            $totalAdicionales = $parsed['total_adicionales'];
            $totalValor     = $parsed['total_valor'];
            $nit            = $parsed['nit'];
            $razonSocial    = $parsed['razon_social'];
            $fechaCorte     = $parsed['fecha_corte'];

            // Verificar si ya existe
            if ($this->contrapago_invoice_model->getInvoiceByNumber($numeroFactura)) {
                $this->session->set_flashdata('contrapago_error', 'La factura #' . $numeroFactura . ' ya fue importada.');
                redirect(base_url() . 'sisvent/admin/contrapagos/invoices');
                return;
            }

            if (empty($items)) {
                $this->session->set_flashdata('contrapago_error', 'No se encontraron guías válidas en el archivo');
                redirect(base_url() . 'sisvent/admin/contrapagos/invoices');
                return;
            }

            $uid = $this->session->userdata('user_data')['uname'];
            $invoiceId = $this->contrapago_invoice_model->saveInvoice(array(
                'numero_factura' => $numeroFactura,
                'fecha_corte' => $fechaCorte,
                'nit' => $nit,
                'razon_social' => $razonSocial,
                'total_guias' => count($items),
                'valor_transporte' => $totalTransporte,
                'valor_seguro' => $totalSeguro,
                'valor_adicionales' => $totalAdicionales,
                'valor_total' => $totalValor,
                'status' => 'pendiente',
                'filename' => $filename,
                'created_by' => $uid,
            ));

            // Vincular items con invoice_id
            foreach ($items as &$it) $it['invoice_id'] = $invoiceId;
            unset($it);
            $this->contrapago_invoice_model->saveItems($items);

            // Cruzar con shipping_guides y actualizar fletes reales
            $match = $this->contrapago_invoice_model->matchItems($invoiceId);

            // Vincular retroactivamente con batches que mencionan esta factura
            // (busca observaciones que la nombren y crea/actualiza pagos parciales)
            $linkedBatches = 0;
            $batchesQuery = $this->db->select('id')
                ->from('contrapago_batches')
                ->where_in('status', array('conciliado','registrado'))
                ->get()->result();
            foreach ($batchesQuery as $b) {
                $vinc = $this->contrapago_invoice_model->linkBatchToInterInvoices($b->id, $uid);
                foreach ($vinc as $v) {
                    if (!empty($v['invoice_id']) && $v['invoice_id'] == $invoiceId) {
                        $linkedBatches++;
                    }
                }
            }

            // Generar/actualizar cuenta por cobrar a MAM por la parte proporcional
            $cobroMam = $this->intercompany_model->generateFromInterInvoice($invoiceId, $uid);

            $msg = "Factura #{$numeroFactura} importada: " . count($items) . " guías, ";
            $msg .= "{$match['matched']} cruzadas con sistema, {$match['flete_updated']} fletes actualizados.";
            if ($linkedBatches > 0) {
                $msg .= " 🔗 Vinculada con {$linkedBatches} pago(s) que la mencionaban.";
            }
            if ($cobroMam > 0) {
                $msg .= " | Cuenta por cobrar a MAM: $" . number_format($cobroMam, 0, ',', '.');
            }
            $this->session->set_flashdata('contrapago_success', $msg);

        } catch (Exception $e) {
            $this->session->set_flashdata('contrapago_error', 'Error al procesar el archivo: ' . $e->getMessage());
        }

        redirect(base_url() . 'sisvent/admin/contrapagos/invoices');
    }

    /**
     * AJAX: Marcar guía (item de factura o pago) como de otra empresa (MAM)
     */
    public function markCompany() {
        header('Content-Type: application/json');
        $table = $this->input->post('table'); // 'payment' | 'invoice_item'
        $id = (int)$this->input->post('id');
        $company = $this->input->post('company'); // 'ledxury' | 'mam'
        $uid = $this->session->userdata('user_data')['uname'];

        if (!in_array($table, array('payment', 'invoice_item')) || !$id || !in_array($company, array('ledxury', 'mam'))) {
            echo json_encode(array('success' => false, 'message' => 'Parámetros inválidos'));
            return;
        }

        $targetTable = $table === 'payment' ? 'contrapago_payments' : 'contrapago_invoice_items';
        $row = $this->db->where('id', $id)->get($targetTable)->row();
        if (!$row) {
            echo json_encode(array('success' => false, 'message' => 'Registro no encontrado'));
            return;
        }
        $this->db->where('id', $id)->update($targetTable, array('company' => $company));

        // Regenerar cuenta por cobrar/pagar a MAM del batch o factura afectado
        $regenerated = 0;
        if ($table === 'payment' && !empty($row->batch_id)) {
            $batch = $this->db->where('id', $row->batch_id)->get('contrapago_batches')->row();
            if ($batch && $batch->status === 'registrado') {
                $regenerated = $this->intercompany_model->generateFromContrapagoBatch(
                    $row->batch_id, null, $uid
                );
            }
        } elseif ($table === 'invoice_item' && !empty($row->invoice_id)) {
            $regenerated = $this->intercompany_model->generateFromInterInvoice($row->invoice_id, $uid);
        }

        echo json_encode(array(
            'success' => true,
            'company' => $company,
            'intercompany_regenerated' => $regenerated,
        ));
    }

    /**
     * Dashboard Entre Compañías (Ledxury vs MAM)
     */
    public function entreCompanias() {
        // Total contrapagos de MAM cobrados (Ledxury recibió pero es de MAM)
        $mamCobrado = $this->db->query("
            SELECT COALESCE(SUM(cp.valorTotal), 0) as total, COUNT(*) as count
            FROM contrapago_payments cp
            INNER JOIN contrapago_batches b ON b.id = cp.batch_id AND b.status = 'registrado'
            WHERE cp.company = 'mam'
        ")->row();

        // Total fletes de MAM pagados (Ledxury pagó a Inter pero son guías de MAM)
        $mamFletes = $this->db->query("
            SELECT COALESCE(SUM(ii.valor_total), 0) as total, COUNT(*) as count
            FROM contrapago_invoice_items ii
            WHERE ii.company = 'mam'
        ")->row();

        $balanceNeto = (float)$mamCobrado->total - (float)$mamFletes->total;

        // Guías pendientes de asignar empresa (sin_match sin company)
        $pendientesPayments = $this->db->query("
            SELECT cp.*, b.id as batch_id, b.filename, b.fecha_pago
            FROM contrapago_payments cp
            INNER JOIN contrapago_batches b ON b.id = cp.batch_id
            WHERE cp.status = 'sin_match'
            ORDER BY cp.id DESC
        ")->result();

        $pendientesInvoices = $this->db->query("
            SELECT ii.*, i.numero_factura, i.fecha_corte
            FROM contrapago_invoice_items ii
            INNER JOIN contrapago_invoices i ON i.id = ii.invoice_id
            WHERE ii.shipping_guide_id IS NULL
            ORDER BY ii.id DESC
        ")->result();

        $data = array(
            'mam_cobrado_total' => (float)$mamCobrado->total,
            'mam_cobrado_count' => (int)$mamCobrado->count,
            'mam_fletes_total' => (float)$mamFletes->total,
            'mam_fletes_count' => (int)$mamFletes->count,
            'balance_neto' => $balanceNeto,
            'pendientes_payments' => $pendientesPayments,
            'pendientes_invoices' => $pendientesInvoices,
            'role' => $this->session->userdata('user_data')['role']
        );
        $this->load->view('sisvent/admin/contrapagos/entre_companias', $data);
    }

    /**
     * Vista de cuentas por cobrar/pagar entre Ledxury y MAM
     */
    public function intercompany() {
        $from = $this->input->get('from');
        $to = $this->input->get('to');
        $tipo = $this->input->get('tipo');
        $status = $this->input->get('status') ?: 'activo';

        $filters = array('status' => $status);
        if ($from) $filters['from'] = $from;
        if ($to) $filters['to'] = $to;
        if ($tipo) $filters['tipo'] = $tipo;

        $movements = $this->intercompany_model->getMovements($filters);
        $balance = $this->intercompany_model->getBalance();
        $stats = $this->intercompany_model->getStats();

        $this->load->model('bankaccounts_model');

        $data = array(
            'movements' => $movements,
            'balance' => $balance,
            'stats' => $stats,
            'bank_accounts' => $this->bankaccounts_model->getBankAccounts(),
            'filter_from' => $from,
            'filter_to' => $to,
            'filter_tipo' => $tipo,
            'filter_status' => $status,
            'role' => $this->session->userdata('user_data')['role'],
        );
        $this->load->view('sisvent/admin/contrapagos/intercompany', $data);
    }

    /**
     * AJAX: Crear movimiento intercompañías manual (pago recibido o ajuste)
     */
    public function intercompanySave() {
        header('Content-Type: application/json');
        $uid = $this->session->userdata('user_data')['uname'];

        $id = (int)$this->input->post('id');
        $tipo = $this->input->post('tipo');
        $concepto = $this->input->post('concepto');
        $direccion = $this->input->post('direccion');
        $monto = (float)$this->input->post('monto');
        $fecha = $this->input->post('fecha');
        $descripcion = trim($this->input->post('descripcion'));
        $numMov = trim($this->input->post('numero_movimiento'));
        $bankId = $this->input->post('bank_account_id') ?: null;

        if (!in_array($tipo, array('cobro_pendiente','pago_recibido','ajuste'))
            || !in_array($concepto, array('flete_mam','contrapago_mam','transferencia','ajuste_manual'))
            || !in_array($direccion, array('mam_debe_ledxury','ledxury_debe_mam'))
            || $monto <= 0
            || !$fecha) {
            echo json_encode(array('success' => false, 'message' => 'Parámetros inválidos'));
            return;
        }

        $data = array(
            'tipo' => $tipo,
            'concepto' => $concepto,
            'direccion' => $direccion,
            'monto' => $monto,
            'fecha' => $fecha,
            'descripcion' => $descripcion,
            'numero_movimiento' => $numMov,
            'bank_account_id' => $bankId,
        );

        if ($id > 0) {
            $existing = $this->intercompany_model->get($id);
            if (!$existing) {
                echo json_encode(array('success' => false, 'message' => 'Movimiento no encontrado'));
                return;
            }
            $this->intercompany_model->update($id, $data);
            echo json_encode(array('success' => true, 'id' => $id, 'action' => 'updated'));
        } else {
            $data['created_by'] = $uid;
            $newId = $this->intercompany_model->save($data);

            // Si es pago_recibido y tiene banco asociado, crear movimiento bancario
            if ($tipo === 'pago_recibido' && $bankId) {
                $this->load->model('bankaccounts_model');
                $bank = $this->bankaccounts_model->getBankAccount($bankId);
                if ($bank) {
                    date_default_timezone_set("America/Bogota");
                    $isIngreso = ($direccion === 'mam_debe_ledxury'); // MAM nos pagó = ingreso
                    $this->db->insert('cash_movements', array(
                        'sourceType' => 'banco',
                        'sourceId' => $bankId,
                        'movementType' => $isIngreso ? 'ingreso' : 'egreso',
                        'amount' => $monto,
                        'concept' => 'Intercompañías ' . $concepto . ' - ' . $descripcion . ($numMov ? ' | Mov: ' . $numMov : ''),
                        'category' => 'intercompanias',
                        'documentNumber' => $numMov ?: ('Intercomp #' . $newId),
                        'movementDate' => $fecha . ' ' . date('H:i:s'),
                        'status' => 'activo',
                        'referenceType' => 'intercompany',
                        'referenceId' => $newId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ));
                    $movId = $this->db->insert_id();
                    $this->db->set('currentBalance',
                        'currentBalance ' . ($isIngreso ? '+' : '-') . ' ' . $monto, false);
                    $this->db->where('idBankAccount', $bankId);
                    $this->db->update('bank_accounts');
                    $this->intercompany_model->update($newId, array('cash_movement_id' => $movId));
                }
            }
            echo json_encode(array('success' => true, 'id' => $newId, 'action' => 'created'));
        }
    }

    /**
     * AJAX: Anular movimiento intercompañías (soft delete + revertir banco si tiene)
     */
    public function intercompanyDelete($id) {
        header('Content-Type: application/json');
        $uid = $this->session->userdata('user_data')['uname'];
        $mov = $this->intercompany_model->get($id);
        if (!$mov) {
            echo json_encode(array('success' => false, 'message' => 'No encontrado'));
            return;
        }
        if ($mov->status === 'anulado') {
            echo json_encode(array('success' => false, 'message' => 'Ya está anulado'));
            return;
        }

        // Si tiene movimiento bancario, revertirlo
        if ($mov->cash_movement_id) {
            $cm = $this->db->where('idMovement', $mov->cash_movement_id)->get('cash_movements')->row();
            if ($cm) {
                // Revertir saldo
                $sign = $cm->movementType === 'ingreso' ? '-' : '+';
                $this->db->set('currentBalance', 'currentBalance ' . $sign . ' ' . $cm->amount, false);
                $this->db->where('idBankAccount', $cm->sourceId);
                $this->db->update('bank_accounts');
                $this->db->where('idMovement', $mov->cash_movement_id)->delete('cash_movements');
            }
        }

        $this->intercompany_model->softDelete($id, $uid);
        echo json_encode(array('success' => true));
    }

    /**
     * Eliminar una factura Inter
     */
    public function deleteInvoice($id) {
        header('Content-Type: application/json');
        $invoice = $this->contrapago_invoice_model->getInvoice($id);
        if (!$invoice) {
            echo json_encode(array('success' => false, 'message' => 'Factura no encontrada'));
            return;
        }
        if ($invoice->status === 'descontada') {
            echo json_encode(array('success' => false, 'message' => 'No se puede eliminar una factura ya descontada'));
            return;
        }
        $this->contrapago_invoice_model->deleteInvoice($id);
        echo json_encode(array('success' => true, 'message' => 'Factura eliminada'));
    }

    /**
     * Ver detalle de un lote importado
     */
    public function view($id) {
        $batch = $this->contrapago_model->getBatch($id);
        if (!$batch) show_404();

        $this->load->model('bankaccounts_model');
        $payments = $this->contrapago_model->getPayments($id);

        // Detectar descuentos de Inter (ej: "Dcto Factura #X Por valor de $Y")
        $descuentos = array();
        $totalDescuentos = 0;
        $seenDesc = array();
        foreach ($payments as $p) {
            if (!empty($p->observacion) && stripos($p->observacion, 'Dcto') !== false) {
                $obs = trim($p->observacion);
                if (isset($seenDesc[$obs])) continue;
                $seenDesc[$obs] = true;

                // Parse "Dcto Factura #XXXXX Por valor de $ Y.YYY"
                $numFact = null;
                $valor = 0;
                if (preg_match('/Factura\s*#?\s*(\d+)/i', $obs, $m)) $numFact = $m[1];
                if (preg_match('/[\$]\s*([\d\.,]+)/', $obs, $m)) {
                    $valor = (float) str_replace(array('.', ','), array('', '.'), $m[1]);
                }
                $descuentos[] = array(
                    'texto' => $obs,
                    'factura' => $numFact,
                    'valor' => $valor
                );
                $totalDescuentos += $valor;
            }
        }

        $totalBruto = (float)$batch->total_valor + $totalDescuentos;

        // Contar duplicadas
        $duplicadas = 0;
        $totalDuplicado = 0;
        foreach ($payments as $p) {
            if ($p->status === 'duplicada') {
                $duplicadas++;
                $totalDuplicado += (float)$p->valorTotal;
            }
        }

        $data = array(
            'batch' => $batch,
            'payments' => $payments,
            'bank_accounts' => $this->bankaccounts_model->getBankAccounts(),
            'role' => $this->session->userdata('user_data')['role'],
            'descuentos' => $descuentos,
            'total_descuentos' => $totalDescuentos,
            'total_bruto_real' => $totalBruto,
            'duplicadas' => $duplicadas,
            'total_duplicado' => $totalDuplicado,
        );
        $this->load->view('sisvent/admin/contrapagos/view', $data);
    }

    /**
     * Exportar Excel enriquecido del lote: datos originales + factura, cliente, vendedor del sistema
     */
    public function exportBatch($id) {
        $batch = $this->contrapago_model->getBatch($id);
        if (!$batch) show_404();

        require_once FCPATH . 'vendor/autoload.php';
        $payments = $this->contrapago_model->getPayments($id);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Lote ' . $id);

        // Headers
        $headers = array(
            'A1' => '#', 'B1' => 'Guia', 'C1' => 'Fecha Venta', 'D1' => 'Valor Guia',
            'E1' => 'Destinatario', 'F1' => 'Conciliacion Inter', 'G1' => 'Fecha Pago',
            'H1' => 'Banco', 'I1' => 'Observacion Inter',
            'J1' => 'Factura #', 'K1' => 'Fecha Factura', 'L1' => 'Cliente Sistema',
            'M1' => 'Vendedor', 'N1' => 'Total Factura', 'O1' => 'Estado Factura',
            'P1' => 'Diferencia'
        );
        foreach ($headers as $c => $v) $sheet->setCellValue($c, $v);
        $sheet->getStyle('A1:P1')->getFont()->setBold(true);
        $sheet->getStyle('A1:P1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1B365D');
        $sheet->getStyle('A1:P1')->getFont()->getColor()->setRGB('FFFFFF');

        $row = 2; $i = 0;
        foreach ($payments as $p) {
            $i++;
            $inv = null; $vendor = null; $client = null;
            if ($p->invoice_id) {
                $inv = $this->db->select('i.idInvoice, i.total, i.state, i.date, i.vendorId, c.name as client_name, u.name as vendor_name')
                    ->from('invoices i')
                    ->join('clients c', 'c.idClient = i.clientId', 'left')
                    ->join('users u', 'u.idUser = i.vendorId', 'left')
                    ->where('i.idInvoice', $p->invoice_id)
                    ->get()->row();
            }

            $sheet->setCellValue('A' . $row, $i);
            $sheet->setCellValueExplicit('B' . $row, $p->numeroGuia, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, $p->fechaVenta ? date('d/m/Y', strtotime($p->fechaVenta)) : '');
            $sheet->setCellValue('D' . $row, (float)$p->valorTotal);
            $sheet->setCellValue('E' . $row, $p->nombreDestinatario);
            $sheet->setCellValue('F' . $row, $p->conciliacion);
            $sheet->setCellValue('G' . $row, $p->fechaPago ? date('d/m/Y', strtotime($p->fechaPago)) : '');
            $sheet->setCellValue('H' . $row, $p->banco);
            $sheet->setCellValue('I' . $row, $p->observacion);

            if ($inv) {
                $sheet->setCellValue('J' . $row, '#' . $inv->idInvoice);
                $sheet->setCellValue('K' . $row, date('d/m/Y', strtotime($inv->date)));
                $sheet->setCellValue('L' . $row, $inv->client_name);
                $sheet->setCellValue('M' . $row, $inv->vendor_name);
                $sheet->setCellValue('N' . $row, (float)$inv->total);
                $stateMap = array(0 => 'Pendiente', 1 => 'Parcial', 2 => 'Pagada', 3 => 'Anulada');
                $sheet->setCellValue('O' . $row, isset($stateMap[$inv->state]) ? $stateMap[$inv->state] : $inv->state);
                $diff = (float)$p->valorTotal - (float)$inv->total;
                $sheet->setCellValue('P' . $row, $diff);
                if (abs($diff) > 0.01) {
                    $sheet->getStyle('P' . $row)->getFont()->getColor()->setRGB('CC0000');
                    $sheet->getStyle('P' . $row)->getFont()->setBold(true);
                }
            } else {
                $sheet->setCellValue('J' . $row, 'SIN MATCH');
                $sheet->getStyle('J' . $row)->getFont()->getColor()->setRGB('CC0000');
            }
            $row++;
        }

        // Formato moneda
        $sheet->getStyle('D2:D' . ($row - 1))->getNumberFormat()->setFormatCode('$#,##0');
        $sheet->getStyle('N2:N' . ($row - 1))->getNumberFormat()->setFormatCode('$#,##0');
        $sheet->getStyle('P2:P' . ($row - 1))->getNumberFormat()->setFormatCode('$#,##0');

        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'contrapago_lote_' . $id . '_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
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

        // Pre-validación: determinar qué pagos son realmente aplicables.
        // Una guía no aplica si: no cruzó factura, está marcada como duplicada,
        // la factura ya está pagada/anulada, o ya tiene un contrapago registrado.
        // Ese filtro evita inflar el saldo del banco con dinero que no se aplica
        // a ninguna factura cuando se registra un lote duplicado.
        $applicable = array();
        $skippedDuplicada = 0; $skippedFacturaPaga = 0; $skippedYaCobrada = 0;
        $skippedSinFactura = 0; $brutoSkipped = 0;
        foreach ($cpPayments as $p) {
            if (!$p->invoice_id || $p->status !== 'conciliado') {
                if ($p->status === 'duplicada') { $skippedDuplicada++; $brutoSkipped += (float)$p->valorTotal; }
                else $skippedSinFactura++;
                continue;
            }
            $invoice = $this->invoices_model->getInvoice($p->invoice_id);
            if (!$invoice) { $skippedSinFactura++; continue; }
            if ($invoice->state == 2 || $invoice->state == 3) {
                $skippedFacturaPaga++; $brutoSkipped += (float)$p->valorTotal; continue;
            }
            $existingPay = $this->db->where('invoiceId', $p->invoice_id)
                ->where('paymentMethod', 5)->where('deleted', 0)
                ->get('payments')->num_rows();
            if ($existingPay > 0) {
                $skippedYaCobrada++; $brutoSkipped += (float)$p->valorTotal; continue;
            }
            $applicable[] = array('p' => $p, 'invoice' => $invoice);
        }

        $totalBruto = 0;
        foreach ($applicable as $a) $totalBruto += (float)$a['p']->valorTotal;

        if ($totalBruto <= 0) {
            $razones = array();
            if ($skippedDuplicada) $razones[] = "$skippedDuplicada guía(s) ya cobrada(s) en otro lote";
            if ($skippedFacturaPaga) $razones[] = "$skippedFacturaPaga factura(s) ya pagada(s)";
            if ($skippedYaCobrada) $razones[] = "$skippedYaCobrada factura(s) con contrapago previo";
            if ($skippedSinFactura) $razones[] = "$skippedSinFactura guía(s) sin cruce con factura";
            echo json_encode(array(
                'success' => false,
                'message' => 'No hay nada por registrar en este lote: ' . (empty($razones) ? 'sin guías aplicables.' : implode(', ', $razones) . '.')
                    . ' Es probable que sea un lote duplicado — elimínalo en lugar de registrarlo.'
            ));
            return;
        }

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

        // 2. Crear pagos individuales por cada factura y calcular comisiones.
        // La pre-validación ya descartó pagos no aplicables, así que iteramos
        // solo sobre $applicable.
        $facturasPagadas = 0;
        $vendorCommissions = array();

        foreach ($applicable as $a) {
            $p = $a['p'];
            $invoice = $a['invoice'];

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

        // 4b. Generar cuenta por pagar a MAM por contrapagos cobrados de clientes MAM
        $intercompanyMonto = $this->intercompany_model->generateFromContrapagoBatch(
            $batchId, $bankAccount->idBankAccount, $uid
        );

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

        $intercompanyMsg = '';
        if ($intercompanyMonto > 0) {
            $intercompanyMsg = ' | Cuenta por pagar a MAM: $' . number_format($intercompanyMonto, 0, ',', '.');
        }

        $skippedMsg = '';
        $totalSkipped = $skippedDuplicada + $skippedFacturaPaga + $skippedYaCobrada + $skippedSinFactura;
        if ($totalSkipped > 0) {
            $detalles = array();
            if ($skippedDuplicada) $detalles[] = "$skippedDuplicada duplicada(s)";
            if ($skippedFacturaPaga) $detalles[] = "$skippedFacturaPaga ya pagada(s)";
            if ($skippedYaCobrada) $detalles[] = "$skippedYaCobrada con contrapago previo";
            if ($skippedSinFactura) $detalles[] = "$skippedSinFactura sin cruce";
            $skippedMsg = ' | ⏭️ ' . $totalSkipped . " guía(s) omitidas (" . implode(', ', $detalles)
                . ') por $' . number_format($brutoSkipped, 0, ',', '.') . ' (no se acreditan al banco)';
        }

        echo json_encode(array(
            'success' => true,
            'message' => 'Ingreso neto de $' . number_format($netoConsignado, 0, ',', '.')
                . ' registrado en ' . $bankAccount->bankName
                . ($numeroMovimiento ? ' (Mov: ' . $numeroMovimiento . ')' : '')
                . ' — Bruto: $' . number_format($totalBruto, 0, ',', '.')
                . ' - 4x1000: $' . number_format($impuesto4x1000, 0, ',', '.') . '. '
                . $facturasPagadas . ' pagos creados.' . $comisionMsg . $intercompanyMsg . $skippedMsg,
            'movement_id' => $movId,
            'payments_created' => $facturasPagadas,
            'vendor_commissions' => $vendorCommissions,
            'intercompany_monto' => $intercompanyMonto,
            'skipped' => array(
                'duplicada' => $skippedDuplicada,
                'factura_paga' => $skippedFacturaPaga,
                'ya_cobrada' => $skippedYaCobrada,
                'sin_factura' => $skippedSinFactura,
                'bruto_omitido' => $brutoSkipped,
            ),
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

    /**
     * Detecta y parsea una factura Inter en cualquiera de sus 2 formatos.
     *
     * Formato A (CORTE):
     *   - J1 contiene el número de factura
     *   - Headers en fila 2: A=ADM_NumeroGuia, B=ADM_FechaGrabacion,
     *     C=NombreCiudadOrigen, D=NombreCiudadDestino, E=Peso,
     *     F=ValorComercial, G=ValorAdicionales, H=ValorTransporte,
     *     I=ValorPrima, J=ValorTotal, K=Nit, L=RazonSocial
     *   - Datos desde fila 3
     *
     * Formato B (SOPORTE DETALLADO):
     *   - Sheet name = número de factura (numérico)
     *   - C1 = "Inter Rapidisimo S.A."
     *   - M10 también tiene el número de factura
     *   - Headers en fila 11: B=Guía, D=Fecha, F=Origen, H=Destino,
     *     I=Peso, J=Valor Flete, K=Vr Otros Conceptos, M=Vr Sobre Flete, P=Total
     *   - Datos desde fila 12
     *   - No hay valor comercial; valor_total = J + K + M (Flete + Otros + Sobre Flete)
     *
     * @return array|false  Array con: numero_factura, items[], totales y meta
     */
    private function _parseInterInvoiceFile($sheet)
    {
        $highRow = $sheet->getHighestRow();

        // ── Detectar formato ─────────────────────────────────────────────
        $j1 = trim((string)$sheet->getCell('J1')->getValue());
        $i1 = trim((string)$sheet->getCell('I1')->getValue());
        $isFormatA = (is_numeric($j1) && $j1 > 1000) || (is_numeric($i1) && $i1 > 1000);

        $sheetTitle = $sheet->getTitle();
        $c1 = trim((string)$sheet->getCell('C1')->getValue());
        $isFormatB = is_numeric($sheetTitle) || stripos($c1, 'Inter Rapidisimo') !== false;

        if ($isFormatA) {
            return $this->_parseInterFormatA($sheet, $highRow);
        }
        if ($isFormatB) {
            return $this->_parseInterFormatB($sheet, $highRow, $sheetTitle);
        }
        return array('error' => 'Formato de factura Inter no reconocido. Verifique que sea un archivo válido (CORTE o SOPORTE DETALLADO).');
    }

    /**
     * Parser del formato A (CORTE) — el original.
     */
    private function _parseInterFormatA($sheet, $highRow)
    {
        $numeroFactura = trim((string)$sheet->getCell('J1')->getValue());
        if (empty($numeroFactura)) $numeroFactura = trim((string)$sheet->getCell('I1')->getValue());
        if (empty($numeroFactura) || !is_numeric($numeroFactura)) {
            return array('error' => 'No se pudo detectar el número de factura en celda J1 (formato CORTE).');
        }

        $headerRow = 0;
        for ($r = 1; $r <= min(10, $highRow); $r++) {
            $a = trim((string)$sheet->getCell('A' . $r)->getValue());
            if (stripos($a, 'NumeroGuia') !== false || stripos($a, 'Numero de Guia') !== false) {
                $headerRow = $r;
                break;
            }
        }
        if (!$headerRow) {
            return array('error' => 'No se encontró fila de encabezados en columna A (formato CORTE espera "NumeroGuia").');
        }

        $items = array();
        $totT = 0; $totS = 0; $totA = 0; $totV = 0;
        $nit = null; $razonSocial = null; $fechaCorte = null;

        for ($r = $headerRow + 1; $r <= $highRow; $r++) {
            $guia = trim((string)$sheet->getCell('A' . $r)->getValue());
            if (empty($guia) || !is_numeric($guia)) continue;

            $fechaGrab = $sheet->getCell('B' . $r)->getValue();
            if ($fechaGrab && is_numeric($fechaGrab)) {
                $fechaGrab = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fechaGrab)->format('Y-m-d H:i:s');
            }
            $cOrigen  = trim((string)$sheet->getCell('C' . $r)->getValue());
            $cDestino = trim((string)$sheet->getCell('D' . $r)->getValue());
            $peso     = (float)$sheet->getCell('E' . $r)->getValue();
            $vComercial = (float)$sheet->getCell('F' . $r)->getValue();
            $vAdic    = (float)$sheet->getCell('G' . $r)->getValue();
            $vTransp  = (float)$sheet->getCell('H' . $r)->getValue();
            $vPrima   = (float)$sheet->getCell('I' . $r)->getValue();
            $vTotal   = (float)$sheet->getCell('J' . $r)->getValue();
            $nitRow   = trim((string)$sheet->getCell('K' . $r)->getValue());
            $razon    = trim((string)$sheet->getCell('L' . $r)->getValue());

            if (!$nit && $nitRow) $nit = $nitRow;
            if (!$razonSocial && $razon) $razonSocial = $razon;
            if (!$fechaCorte && $fechaGrab) $fechaCorte = substr($fechaGrab, 0, 10);

            $totT += $vTransp; $totS += $vPrima; $totA += $vAdic; $totV += $vTotal;

            $items[] = array(
                'numero_guia' => $guia,
                'fecha_grabacion' => $fechaGrab,
                'ciudad_origen' => $cOrigen,
                'ciudad_destino' => $cDestino,
                'peso' => $peso,
                'valor_comercial' => $vComercial,
                'valor_adicionales' => $vAdic,
                'valor_transporte' => $vTransp,
                'valor_prima' => $vPrima,
                'valor_total' => $vTotal,
            );
        }

        return array(
            'format' => 'CORTE',
            'numero_factura' => $numeroFactura,
            'items' => $items,
            'total_transporte' => $totT,
            'total_seguro' => $totS,
            'total_adicionales' => $totA,
            'total_valor' => $totV,
            'nit' => $nit,
            'razon_social' => $razonSocial,
            'fecha_corte' => $fechaCorte,
        );
    }

    /**
     * Parser del formato B (SOPORTE DETALLADO).
     * Headers en fila 11, columnas B/D/F/H/I/J/K/M/P, sheet name = #factura.
     */
    private function _parseInterFormatB($sheet, $highRow, $sheetTitle)
    {
        // Número de factura: priorizar M10, fallback al sheet name
        $numeroFactura = trim((string)$sheet->getCell('M10')->getValue());
        if (!is_numeric($numeroFactura) && is_numeric($sheetTitle)) {
            $numeroFactura = $sheetTitle;
        }
        if (empty($numeroFactura) || !is_numeric($numeroFactura)) {
            return array('error' => 'No se pudo detectar el número de factura en celda M10 ni en sheet name (formato SOPORTE DETALLADO).');
        }

        // NIT cliente y razón social en fila 10
        $nit         = trim((string)$sheet->getCell('D10')->getValue());
        $razonSocial = trim((string)$sheet->getCell('F10')->getValue());

        // Buscar fila de headers (esperada en 11; tolerante hasta 15)
        $headerRow = 0;
        for ($r = 9; $r <= min(15, $highRow); $r++) {
            $b = trim((string)$sheet->getCell('B' . $r)->getValue());
            if (stripos($b, 'Gu') !== false && stripos($b, 'a') !== false) { // 'Guía' o 'Guia'
                $headerRow = $r;
                break;
            }
        }
        if (!$headerRow) {
            return array('error' => 'No se encontró fila de encabezados (formato SOPORTE DETALLADO espera "Guía" en columna B fila 11).');
        }

        $items = array();
        $totT = 0; $totS = 0; $totA = 0; $totV = 0;
        $fechaCorte = null;

        for ($r = $headerRow + 1; $r <= $highRow; $r++) {
            $guia = trim((string)$sheet->getCell('B' . $r)->getValue());
            if (empty($guia) || !is_numeric($guia)) continue;
            // La última fila puede tener totales (sin guía válida) — la excluye is_numeric

            // Fecha: puede ser string "3/10/2026" o Excel serial
            $fechaGrab = $sheet->getCell('D' . $r)->getValue();
            if ($fechaGrab && is_numeric($fechaGrab)) {
                $fechaGrab = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fechaGrab)->format('Y-m-d H:i:s');
            } elseif ($fechaGrab) {
                $ts = strtotime((string)$fechaGrab);
                $fechaGrab = $ts ? date('Y-m-d H:i:s', $ts) : null;
            }

            $cOrigen  = trim((string)$sheet->getCell('F' . $r)->getValue());
            $cDestino = trim((string)$sheet->getCell('H' . $r)->getValue());
            $peso     = (float)$sheet->getCell('I' . $r)->getValue();
            $vTransp  = (float)$sheet->getCell('J' . $r)->getValue();   // Valor Flete
            $vAdic    = (float)$sheet->getCell('K' . $r)->getValue();   // Vr Otros Conceptos
            $vPrima   = (float)$sheet->getCell('M' . $r)->getValue();   // Vr Sobre Flete
            $vTotal   = (float)$sheet->getCell('P' . $r)->getValue();   // Total
            // Si Total no viene calculado, lo armamos
            if ($vTotal <= 0) $vTotal = $vTransp + $vAdic + $vPrima;

            if (!$fechaCorte && $fechaGrab) $fechaCorte = substr($fechaGrab, 0, 10);

            $totT += $vTransp; $totS += $vPrima; $totA += $vAdic; $totV += $vTotal;

            $items[] = array(
                'numero_guia' => $guia,
                'fecha_grabacion' => $fechaGrab,
                'ciudad_origen' => $cOrigen,
                'ciudad_destino' => $cDestino,
                'peso' => $peso,
                'valor_comercial' => 0,  // formato B no incluye valor comercial
                'valor_adicionales' => $vAdic,
                'valor_transporte' => $vTransp,
                'valor_prima' => $vPrima,
                'valor_total' => $vTotal,
            );
        }

        return array(
            'format' => 'SOPORTE',
            'numero_factura' => $numeroFactura,
            'items' => $items,
            'total_transporte' => $totT,
            'total_seguro' => $totS,
            'total_adicionales' => $totA,
            'total_valor' => $totV,
            'nit' => $nit,
            'razon_social' => $razonSocial,
            'fecha_corte' => $fechaCorte,
        );
    }
}
