<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Envios extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->backend_lib->controlModule('envios');
        $this->load->model('shipping_model');
        $this->load->model('stores_model');
        $this->load->model('vendors_model');
        $this->load->library('interrapidisimo_lib');
    }

    /**
     * Dashboard de envíos
     */
    public function index() {
        $store = $this->input->get('store') ?: -1;
        $status = $this->input->get('status') ?: 'all';
        $vendor = $this->input->get('vendor') ?: 'all';
        $from = $this->input->get('from') ?: date('Y-m-01');
        $to = $this->input->get('to') ?: date('Y-m-d');
        $search = $this->input->get('q') ?: '';
        $page = $this->input->get('page') ?: 1;

        $data = array(
            'shipments' => $this->shipping_model->getShipments((int)$store, $status, $from, $to, $search, (int)$page, 25, $vendor),
            'total' => $this->shipping_model->countShipments((int)$store, $status, $from, $to, $search, $vendor),
            'stats' => $this->shipping_model->getStats((int)$store),
            'stores' => $this->stores_model->getStores(),
            'vendors' => $this->vendors_model->getVendors(),
            'selectedStore' => $store,
            'selectedStatus' => $status,
            'selectedVendor' => $vendor,
            'from' => $from,
            'to' => $to,
            'search' => $search,
            'page' => (int)$page,
            'thisFile' => 'sisvent/admin/envios/index',
            'role' => $this->session->userdata('user_data')['role']
        );

        $this->load->view('sisvent/admin/envios/index', $data);
    }

    /**
     * Exportar envíos a Excel (con los mismos filtros que el dashboard)
     */
    public function exportExcel() {
        $store = $this->input->get('store') ?: -1;
        $status = $this->input->get('status') ?: 'all';
        $vendor = $this->input->get('vendor') ?: 'all';
        $from = $this->input->get('from') ?: date('Y-m-01');
        $to = $this->input->get('to') ?: date('Y-m-d');
        $search = $this->input->get('q') ?: '';

        // Obtener TODOS los registros (sin paginar)
        $shipments = $this->shipping_model->getShipments((int)$store, $status, $from, $to, $search, 1, 0, $vendor);

        // Crear archivo Excel con PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Envios');

        // Headers
        $headers = array(
            'A1' => '#', 'B1' => 'Guia', 'C1' => 'Factura', 'D1' => 'Presupuesto',
            'E1' => 'Cliente', 'F1' => 'Documento', 'G1' => 'Telefono',
            'H1' => 'Vendedor', 'I1' => 'Bodega', 'J1' => 'Destino',
            'K1' => 'Cajas', 'L1' => 'Tipo', 'M1' => 'Estado', 'N1' => 'Estado Inter',
            'O1' => 'Ultima Act.', 'P1' => 'Costo', 'Q1' => 'Recaudo', 'R1' => 'Fecha'
        );
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        $sheet->getStyle('A1:R1')->getFont()->setBold(true);
        $sheet->getStyle('A1:R1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1B365D');
        $sheet->getStyle('A1:R1')->getFont()->getColor()->setRGB('FFFFFF');

        // Data rows
        $row = 2;
        $i = 0;
        foreach ($shipments as $s) {
            $i++;
            $esCp = isset($s->isContrapago) && $s->isContrapago;
            $sheet->setCellValue('A' . $row, $i);
            $sheet->setCellValueExplicit('B' . $row, $s->numeroPreenvio, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, $s->invoiceId);
            $sheet->setCellValue('D' . $row, isset($s->budgetId) ? $s->budgetId : '');
            $sheet->setCellValue('E' . $row, isset($s->client_name) ? $s->client_name : '');
            $sheet->setCellValueExplicit('F' . $row, isset($s->client_doc) ? $s->client_doc : '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('G' . $row, isset($s->client_phone) ? $s->client_phone : '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('H' . $row, isset($s->vendor_name) ? $s->vendor_name : '');
            $sheet->setCellValue('I' . $row, isset($s->store_name) ? $s->store_name : '');
            $sheet->setCellValue('J' . $row, isset($s->ciudadDestinoNombre) ? $s->ciudadDestinoNombre : '');
            $sheet->setCellValue('K' . $row, isset($s->numeroPiezas) ? $s->numeroPiezas : 1);
            $sheet->setCellValue('L' . $row, $esCp ? 'Contrapago' : 'MAM paga');
            $sheet->setCellValue('M' . $row, ucfirst(str_replace('_', ' ', $s->status)));
            $sheet->setCellValue('N' . $row, isset($s->estadoNombre) ? $s->estadoNombre : '');
            $sheet->setCellValue('O' . $row, !empty($s->lastTrackingCheck) ? $s->lastTrackingCheck : '');
            $sheet->setCellValue('P' . $row, (float) $s->valorTotal);
            $sheet->setCellValue('Q' . $row, $esCp ? (float)(isset($s->contrapagoCost) ? $s->contrapagoCost : 0) : 0);
            $sheet->setCellValue('R' . $row, $s->created_at);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Currency format
        $sheet->getStyle('P2:Q' . ($row - 1))->getNumberFormat()->setFormatCode('$#,##0');

        // Output
        $filename = 'envios_' . date('Y-m-d_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Estado de cuenta con Interrapidísimo
     */
    public function estadoCuenta() {
        $store = $this->input->get('store') ?: -1;
        $from = $this->input->get('from') ?: date('Y-m-01');
        $to = $this->input->get('to') ?: date('Y-m-d');
        $tipo = $this->input->get('tipo') ?: 'all';

        $data = array(
            'stats' => $this->shipping_model->getFinancialStats($from, $to, (int)$store),
            'guias' => $this->shipping_model->getFinancialDetail($from, $to, (int)$store, $tipo),
            'stores' => $this->stores_model->getStores(),
            'selectedStore' => $store,
            'selectedTipo' => $tipo,
            'from' => $from,
            'to' => $to,
            'thisFile' => 'sisvent/admin/envios/estado_cuenta',
            'role' => $this->session->userdata('user_data')['role']
        );

        $this->load->view('sisvent/admin/envios/estado_cuenta', $data);
    }

    /**
     * AJAX: Sincronizar estados de todas las guías activas con la API de Inter
     */
    public function syncEstados() {
        header('Content-Type: application/json');

        // Obtener guías activas (no entregadas ni anuladas) con número válido
        $guias = $this->db->select('id, numeroPreenvio, guiasHijas')
            ->from('shipping_guides')
            ->where('estadoGuia !=', 11)
            ->where('estadoGuia !=', 15)
            ->where('numeroPreenvio IS NOT NULL')
            ->where('numeroPreenvio !=', 0)
            ->get()->result();

        if (empty($guias)) {
            echo json_encode(array('success' => true, 'updated' => 0, 'message' => 'No hay guías activas'));
            return;
        }

        // Recopilar todos los números (principales + hijas)
        $allNums = array();
        $numToId = array(); // Mapeo número → id del registro padre
        foreach ($guias as $g) {
            $allNums[] = (int) $g->numeroPreenvio;
            $numToId[(int) $g->numeroPreenvio] = $g->id;
            if (!empty($g->guiasHijas)) {
                $hijas = json_decode($g->guiasHijas, true);
                if (is_array($hijas)) {
                    foreach ($hijas as $h) {
                        $allNums[] = (int) $h;
                        $numToId[(int) $h] = $g->id;
                    }
                }
            }
        }
        $allNums = array_unique($allNums);

        // Consultar en lotes de 20
        $updated = 0;
        $chunks = array_chunk($allNums, 20);
        foreach ($chunks as $chunk) {
            $resultado = $this->interrapidisimo_lib->consultarEstados($chunk);

            // Log para debug
            log_message('debug', 'Sync Inter response: ' . json_encode($resultado));

            if (!$resultado || is_string($resultado)) continue;

            // La API puede devolver listadoGuias o directamente ser un array
            $listaGuias = array();
            if (isset($resultado->listadoGuias)) {
                $listaGuias = $resultado->listadoGuias;
            } elseif (is_array($resultado)) {
                $listaGuias = $resultado;
            }

            foreach ($listaGuias as $guia) {
                $numGuia = isset($guia->numeroGuia) ? (int) $guia->numeroGuia : 0;
                $parentId = isset($numToId[$numGuia]) ? $numToId[$numGuia] : null;
                if (!$parentId) continue;

                // Prioridad: estadosGuia (tracking real) > estadosPreenvio (pre-despacho)
                if (!empty($guia->estadosGuia)) {
                    // Guía ya en transporte — usar estados de logística
                    $ultimo = $guia->estadosGuia[0];
                    $this->shipping_model->updateStatus(
                        $parentId,
                        $ultimo->idEstadoGuia,
                        $ultimo->nombreEstado
                    );
                    $updated++;
                } elseif (!empty($guia->estadosPreenvio)) {
                    // Guía en etapa preenvío — actualizar con info descriptiva
                    $ultimo = $guia->estadosPreenvio[0];
                    $ciudad = isset($ultimo->nombreCiudadDestino) ? str_replace('\\', ' / ', $ultimo->nombreCiudadDestino) : '';
                    // Mapear estados de preenvío a nuestro sistema
                    // En preenvío: 11=Creado, 12=Anulado, etc. — NO confundir con estadosGuia
                    $preStatus = $ultimo->nombreEstado;
                    date_default_timezone_set("America/Bogota");
                    $this->db->where('id', $parentId);
                    $this->db->update('shipping_guides', array(
                        'estadoNombre' => 'Pre: ' . $preStatus,
                        'lastTrackingCheck' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ));
                    $updated++;
                }

                // Guardar info de devolución si hay
                if (!empty($guia->detalleMotivoDevolucion)) {
                    $this->db->where('id', $parentId);
                    $this->db->update('shipping_guides', array(
                        'observations' => 'Devolución: ' . $guia->detalleMotivoDevolucion
                    ));
                }
            }
            usleep(500000);
        }

        echo json_encode(array('success' => true, 'updated' => $updated, 'total' => count($allNums)));
    }

    /**
     * AJAX: Agregar guías históricas por número para trackear
     * POST con: guias (texto con números separados por coma o salto de línea)
     */
    public function agregarGuias() {
        header('Content-Type: application/json');
        $raw = $this->input->post('guias');
        if (!$raw) {
            echo json_encode(array('error' => 'Ingrese números de guía'));
            return;
        }

        // Parsear números (separados por coma, espacio, salto de línea)
        $numeros = preg_split('/[\s,;]+/', trim($raw));
        $numeros = array_filter(array_map('intval', $numeros));
        $numeros = array_unique($numeros);

        if (empty($numeros)) {
            echo json_encode(array('error' => 'No se encontraron números válidos'));
            return;
        }

        date_default_timezone_set("America/Bogota");
        $added = 0;
        $skipped = 0;
        $errors = 0;
        $user = $this->session->userdata('user_data')['uname'];

        // Procesar en lotes de 10 para no saturar la API
        $chunks = array_chunk(array_values($numeros), 10);

        foreach ($chunks as $chunk) {
            $resultado = $this->interrapidisimo_lib->consultarEstados($chunk);

            if (!$resultado || is_string($resultado)) {
                $errors += count($chunk);
                continue;
            }

            $listaGuias = array();
            if (isset($resultado->listadoGuias)) $listaGuias = $resultado->listadoGuias;
            elseif (is_array($resultado)) $listaGuias = $resultado;

            if (empty($listaGuias)) { $errors += count($chunk); continue; }

        foreach ($listaGuias as $guia) {
            $numGuia = (int) $guia->numeroGuia;

            // Verificar si ya existe
            $exists = $this->db->where('numeroPreenvio', $numGuia)->count_all_results('shipping_guides');
            if ($exists) { $skipped++; continue; }

            // Determinar estado
            $estadoCode = 0;
            $estadoName = 'Importado';
            $status = 'creado';
            $ciudadDestino = '';
            $ciudadOrigen = '';
            $fechaEstado = null;

            if (!empty($guia->estadosGuia)) {
                $ultimo = $guia->estadosGuia[0];
                $estadoCode = $ultimo->idEstadoGuia;
                $estadoName = $ultimo->nombreEstado;
                $ciudadDestino = isset($ultimo->nombreCiudadDestino) ? str_replace('\\', ' / ', $ultimo->nombreCiudadDestino) : '';
                $ciudadOrigen = isset($ultimo->nombreCiudadOrigen) ? str_replace('\\', ' / ', $ultimo->nombreCiudadOrigen) : '';
                $fechaEstado = isset($ultimo->fechaEstado) ? $ultimo->fechaEstado : null;

                // Mapear estado
                if ($estadoCode == 11) $status = 'entregado';
                elseif ($estadoCode == 15) $status = 'anulado';
                elseif (in_array($estadoCode, array(2,3,4,18))) $status = 'en_transito';
                elseif (in_array($estadoCode, array(6,31))) $status = 'en_reparto';
                elseif (in_array($estadoCode, array(7,8,10))) $status = 'novedad';
            } elseif (!empty($guia->estadosPreenvio)) {
                $ultimo = $guia->estadosPreenvio[0];
                $estadoName = 'Pre: ' . $ultimo->nombreEstado;
                $ciudadDestino = isset($ultimo->nombreCiudadDestino) ? str_replace('\\', ' / ', $ultimo->nombreCiudadDestino) : '';
                $fechaEstado = isset($ultimo->fechaEstado) ? $ultimo->fechaEstado : null;
            }

            $devolucion = !empty($guia->detalleMotivoDevolucion) ? $guia->detalleMotivoDevolucion : null;

            $this->db->insert('shipping_guides', array(
                'invoiceId' => 0,
                'numeroPreenvio' => $numGuia,
                'status' => $status,
                'estadoGuia' => $estadoCode,
                'estadoNombre' => $estadoName,
                'fechaEstado' => $fechaEstado,
                'ciudadDestinoNombre' => $ciudadDestino,
                'observations' => $devolucion ? 'Devolución: ' . $devolucion : 'Guía importada',
                'created_by' => $user,
                'created_at' => $fechaEstado ?: date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'lastTrackingCheck' => date('Y-m-d H:i:s')
            ));
            $added++;
        }

            usleep(500000); // Pausa entre lotes
        } // Fin chunks

        echo json_encode(array(
            'success' => true,
            'added' => $added,
            'skipped' => $skipped,
            'errors' => $errors,
            'total' => count($numeros)
        ));
    }

    /**
     * Detalle de un envío con timeline de tracking
     */
    public function view($id) {
        $shipment = $this->shipping_model->getShipment($id);
        if (!$shipment) show_404();

        $data = array(
            'shipment' => $shipment,
            'events' => $this->shipping_model->getTrackingEvents($id),
            'thisFile' => 'sisvent/admin/envios/view',
            'role' => $this->session->userdata('user_data')['role']
        );

        $this->load->view('sisvent/admin/envios/view', $data);
    }

    /**
     * AJAX: Actualizar tracking de una guía específica
     */
    public function refreshTracking($id) {
        $shipment = $this->shipping_model->getShipment($id);
        if (!$shipment || !$shipment->numeroPreenvio) {
            echo json_encode(array('error' => 'Guía no encontrada'));
            return;
        }

        $result = $this->_updateTrackingForGuide($shipment);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * AJAX: Enviar WhatsApp al cliente con el estado de su envío
     * POST /sisvent/admin/envios/notifyClient/{id}
     */
    public function notifyClient($id) {
        header('Content-Type: application/json');

        $shipment = $this->shipping_model->getShipment($id);
        if (!$shipment) {
            echo json_encode(array('success' => false, 'message' => 'Envío no encontrado'));
            return;
        }

        $phone = isset($shipment->client_phone) ? preg_replace('/[^0-9]/', '', $shipment->client_phone) : '';
        if (strlen($phone) === 10) $phone = '57' . $phone;
        if (strlen($phone) < 12) {
            echo json_encode(array('success' => false, 'message' => 'El cliente no tiene celular válido'));
            return;
        }

        // Buscar bot según vendedor, fallback por tienda
        $this->load->model('builderbot_model');
        $this->load->library('builderbot_lib');
        $bots = $this->builderbot_model->getConfigs(true);
        $bot = null;
        foreach ($bots as $b) {
            if ($b->default_vendor_id == $shipment->vendorId) { $bot = $b; break; }
        }
        if (!$bot && isset($shipment->storeId)) {
            foreach ($bots as $b) {
                if ($b->default_store_id == $shipment->storeId) { $bot = $b; break; }
            }
        }
        if (!$bot) {
            echo json_encode(array('success' => false, 'message' => 'No hay bot configurado para este vendedor'));
            return;
        }

        $clientName = isset($shipment->client_name) ? $shipment->client_name : 'Cliente';
        $guia = $shipment->numeroPreenvio;
        $trackUrl = 'https://interrapidisimo.com/sigue-tu-envio/?guia=' . $guia;
        $destino = isset($shipment->ciudadDestinoNombre) ? $shipment->ciudadDestinoNombre : '';
        $esCp = isset($shipment->isContrapago) && $shipment->isContrapago;
        $valorCp = isset($shipment->contrapagoCost) ? $shipment->contrapagoCost : 0;

        // Mensaje personalizado según estado
        switch ($shipment->status) {
            case 'creado':
            case 'cotizado':
            case 'recogida_solicitada':
                $message = "Hola {$clientName} 👋\n\n"
                    . "Tu pedido ya fue creado y pronto será recogido por *Interrapidísimo*.\n\n"
                    . "📦 *Guía:* {$guia}\n"
                    . "📍 *Destino:* {$destino}\n\n"
                    . "Te avisaremos cuando esté en camino. 🚚";
                break;

            case 'en_transito':
                $message = "Hola {$clientName} 👋\n\n"
                    . "¡Tu pedido ya está en camino! 🚚\n\n"
                    . "📦 *Guía:* {$guia}\n"
                    . "📍 *Estado:* En tránsito hacia *{$destino}*\n\n"
                    . "🔗 Rastrea tu envío aquí:\n{$trackUrl}\n\n"
                    . "Te avisaremos cuando esté cerca. 😊";
                break;

            case 'en_reparto':
                $estadoPago = '';
                if ($esCp && $valorCp > 0) {
                    $estadoPago = "\n\n💰 *Importante:* Debes pagar *$" . number_format($valorCp, 0, ',', '.') . "* al momento de recibir tu pedido. Ten el dinero listo.";
                }
                $message = "Hola {$clientName} 👋\n\n"
                    . "🎉 ¡Tu pedido está en reparto! Prepárate para recibirlo hoy.\n\n"
                    . "📦 *Guía:* {$guia}\n"
                    . "📍 *Estado:* En reparto en *{$destino}*\n"
                    . "{$estadoPago}\n\n"
                    . "Asegúrate de estar disponible en la dirección de entrega. 🏠";
                break;

            case 'entregado':
                $message = "Hola {$clientName} 👋\n\n"
                    . "✅ ¡Tu pedido ha sido *entregado* exitosamente!\n\n"
                    . "📦 *Guía:* {$guia}\n\n"
                    . "Esperamos que disfrutes tu compra. Si tienes alguna pregunta, no dudes en escribirnos. ¡Gracias por confiar en nosotros! 🙏";
                break;

            case 'novedad':
                $obs = isset($shipment->observations) ? $shipment->observations : '';
                $message = "Hola {$clientName} 👋\n\n"
                    . "⚠️ Tu envío presenta una novedad y no pudo ser entregado.\n\n"
                    . "📦 *Guía:* {$guia}\n"
                    . "📍 *Estado:* " . ($shipment->estadoNombre ?: 'Novedad') . "\n"
                    . ($obs ? "📝 *Detalle:* {$obs}\n" : '')
                    . "\nPor favor comunícate con nosotros para coordinar la entrega. 📞";
                break;

            default:
                $estado = $shipment->estadoNombre ?: $shipment->status;
                $message = "Hola {$clientName} 👋\n\n"
                    . "Te informamos sobre tu envío:\n\n"
                    . "📦 *Guía:* {$guia}\n"
                    . "📍 *Estado:* {$estado}\n\n"
                    . "🔗 Rastrea tu pedido aquí:\n{$trackUrl}\n\n"
                    . "Si tienes alguna duda, responde este mensaje. 🙏";
        }

        $result = $this->builderbot_lib->sendMessage($bot, $phone, $message);

        if ($result['success']) {
            echo json_encode(array('success' => true, 'message' => 'Mensaje enviado a ' . $clientName));
        } else {
            $httpCode = isset($result['http_code']) ? $result['http_code'] : 'N/A';
            echo json_encode(array('success' => false, 'message' => 'Error al enviar (HTTP ' . $httpCode . ')'));
        }
    }

    /**
     * Cron: Actualizar tracking de todas las guías activas
     * URL: sisvent/admin/envios/cronTracking (llamar cada 30 min)
     */
    public function cronTracking() {
        $guides = $this->shipping_model->getActiveForTracking(15);
        $updated = 0;
        $errors = 0;

        foreach ($guides as $guide) {
            $shipment = $this->shipping_model->getShipment($guide->id);
            $result = $this->_updateTrackingForGuide($shipment);
            if (isset($result['updated']) && $result['updated']) $updated++;
            else $errors++;

            // Pausa entre llamadas para no saturar la API
            usleep(500000); // 0.5 segundos
        }

        header('Content-Type: application/json');
        echo json_encode(array(
            'processed' => count($guides),
            'updated' => $updated,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Poblar tabla de municipios DANE desde la API
     */
    public function seedDane() {
        header('Content-Type: application/json');

        $localidades = $this->interrapidisimo_lib->obtenerLocalidades();
        if (!$localidades) {
            echo json_encode(array('error' => 'No se pudieron obtener localidades'));
            return;
        }

        // Truncar y re-poblar con datos completos
        $this->db->truncate('dane_municipalities');

        $batch = array();
        foreach ($localidades as $loc) {
            $batch[] = array(
                'daneCode' => $loc->IdLocalidad,
                'name' => $loc->Nombre,
                'shortName' => isset($loc->NombreCorto) ? $loc->NombreCorto : '',
                'department' => isset($loc->NombreAncestroPGrado) ? $loc->NombreAncestroPGrado : '',
                'postalCode' => isset($loc->CodigoPostal) ? $loc->CodigoPostal : '',
                'hasPickup' => isset($loc->PermiteRecogida) ? ($loc->PermiteRecogida ? 1 : 0) : 0,
                'idCentroServicio' => isset($loc->IdCentroServicio) ? (int)$loc->IdCentroServicio : 0,
                'abbreviation' => isset($loc->AbreviacionCiudad) ? $loc->AbreviacionCiudad : '',
                'permitePreEnviosPunto' => isset($loc->PermitePreEnviosPunto) ? ($loc->PermitePreEnviosPunto ? 1 : 0) : 0,
            );

            if (count($batch) >= 200) {
                $this->shipping_model->seedMunicipalities($batch);
                $batch = array();
            }
        }
        if (!empty($batch)) {
            $this->shipping_model->seedMunicipalities($batch);
        }

        echo json_encode(array('success' => true, 'count' => count($localidades)));
    }

    /**
     * Helper: actualizar tracking de una guía
     */
    private function _updateTrackingForGuide($shipment) {
        if (!$shipment || !$shipment->numeroPreenvio) {
            $this->shipping_model->markChecked($shipment->id);
            return array('error' => 'Sin número de guía');
        }

        $resultado = $this->interrapidisimo_lib->consultarEstados(array((int)$shipment->numeroPreenvio));

        if (!$resultado || !isset($resultado->listadoGuias) || empty($resultado->listadoGuias)) {
            $this->shipping_model->markChecked($shipment->id);
            return array('error' => 'Sin respuesta de la API');
        }

        $guia = $resultado->listadoGuias[0];
        $updated = false;

        // Procesar estados logísticos
        if (!empty($guia->estadosGuia)) {
            foreach ($guia->estadosGuia as $estado) {
                // Verificar si este evento ya existe
                $exists = $this->db->where('guideId', $shipment->id)
                    ->where('statusCode', $estado->idEstadoGuia)
                    ->where('eventDate', $estado->fechaEstado)
                    ->get('shipping_tracking_events')->num_rows();

                if (!$exists) {
                    $this->shipping_model->addTrackingEvent(array(
                        'guideId' => $shipment->id,
                        'statusCode' => $estado->idEstadoGuia,
                        'statusName' => $estado->nombreEstado,
                        'description' => $estado->nombreEstado,
                        'location' => isset($estado->nombreCiudadDestino) ? $estado->nombreCiudadDestino : '',
                        'eventDate' => $estado->fechaEstado,
                        'source' => 'api'
                    ));
                    $updated = true;
                }
            }

            // Actualizar estado principal con el más reciente
            $ultimo = $guia->estadosGuia[0];
            $this->shipping_model->updateStatus($shipment->id, $ultimo->idEstadoGuia, $ultimo->nombreEstado);
        }

        // Procesar estados de preenvío
        if (!empty($guia->estadosPreenvio)) {
            foreach ($guia->estadosPreenvio as $estado) {
                $exists = $this->db->where('guideId', $shipment->id)
                    ->where('statusCode', $estado->idEstadoGuia)
                    ->where('source', 'api')
                    ->where('description LIKE', '%preenvío%')
                    ->get('shipping_tracking_events')->num_rows();

                if (!$exists) {
                    $this->shipping_model->addTrackingEvent(array(
                        'guideId' => $shipment->id,
                        'statusCode' => $estado->idEstadoGuia,
                        'statusName' => $estado->nombreEstado,
                        'description' => 'Preenvío: ' . $estado->nombreEstado,
                        'location' => isset($estado->nombreCiudadOrigen) ? $estado->nombreCiudadOrigen : '',
                        'eventDate' => $estado->fechaEstado,
                        'source' => 'api'
                    ));
                }
            }
        }

        $this->shipping_model->markChecked($shipment->id);

        return array('updated' => $updated, 'guia' => $shipment->numeroPreenvio);
    }
}
