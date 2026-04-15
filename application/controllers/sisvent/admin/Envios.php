<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Envios extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->backend_lib->controlModule('envios');
        $this->load->model('shipping_model');
        $this->load->model('stores_model');
        $this->load->library('interrapidisimo_lib');
    }

    /**
     * Dashboard de envíos
     */
    public function index() {
        $store = $this->input->get('store') ?: -1;
        $status = $this->input->get('status') ?: 'all';
        $from = $this->input->get('from') ?: date('Y-m-01');
        $to = $this->input->get('to') ?: date('Y-m-d');
        $search = $this->input->get('q') ?: '';
        $page = $this->input->get('page') ?: 1;

        $data = array(
            'shipments' => $this->shipping_model->getShipments((int)$store, $status, $from, $to, $search, (int)$page, 25),
            'total' => $this->shipping_model->countShipments((int)$store, $status, $from, $to, $search),
            'stats' => $this->shipping_model->getStats((int)$store),
            'stores' => $this->stores_model->getStores(),
            'selectedStore' => $store,
            'selectedStatus' => $status,
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
     * AJAX: Importar Excel "Detallado de Envíos por Cliente" de Interrapidísimo
     * Actualiza guías existentes y crea las que no existen
     */
    public function importExcel() {
        header('Content-Type: application/json');

        if (empty($_FILES['excel']['tmp_name'])) {
            echo json_encode(array('error' => 'No se recibió el archivo'));
            return;
        }

        $ext = strtolower(pathinfo($_FILES['excel']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, array('xlsx', 'xls'))) {
            echo json_encode(array('error' => 'Solo se aceptan archivos .xlsx o .xls'));
            return;
        }

        require_once FCPATH . 'vendor/autoload.php';

        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($_FILES['excel']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
        } catch (Exception $e) {
            echo json_encode(array('error' => 'Error al leer el Excel: ' . $e->getMessage()));
            return;
        }

        // Detectar fila de encabezados (buscar "Numero de Guia" en columna A)
        $headerRow = 0;
        for ($r = 1; $r <= 5; $r++) {
            $val = trim($sheet->getCell('A' . $r)->getValue());
            if (stripos($val, 'Numero de Guia') !== false || stripos($val, 'Numero') !== false) {
                $headerRow = $r;
                break;
            }
        }
        if (!$headerRow) {
            // Si no encuentra header, asumir fila 2 (como en el archivo de ejemplo)
            $headerRow = 2;
        }

        $totalRows = $sheet->getHighestRow();
        $updated = 0;
        $created = 0;
        $skipped = 0;
        $user = $this->session->userdata('user_data')['uname'];
        date_default_timezone_set("America/Bogota");

        for ($row = $headerRow + 1; $row <= $totalRows; $row++) {
            $numGuia = trim($sheet->getCell('A' . $row)->getValue());
            if (empty($numGuia) || !is_numeric($numGuia)) continue;

            $numGuia = (int) $numGuia;
            $ciudadDestino = trim($sheet->getCell('F' . $row)->getValue());
            $destinatario = trim($sheet->getCell('V' . $row)->getValue());
            $piezas = (int) $sheet->getCell('AC' . $row)->getValue();
            $valorComercial = (float) $sheet->getCell('AF' . $row)->getValue();
            $valorTransporte = (float) $sheet->getCell('AG' . $row)->getValue();
            $valorSeguro = (float) $sheet->getCell('AH' . $row)->getValue();
            $valorTotal = (float) $sheet->getCell('AJ' . $row)->getValue();
            $ultimoEstado = trim($sheet->getCell('AM' . $row)->getValue());
            $estadoGestion = trim($sheet->getCell('AO' . $row)->getValue());
            $esContrapago = (strtolower(trim($sheet->getCell('BE' . $row)->getValue())) === 'si') ? 1 : 0;

            // Buscar guía existente
            $existing = $this->db->where('numeroPreenvio', $numGuia)->get('shipping_guides')->row();

            if ($existing) {
                // Actualizar valores financieros
                $changes = array();
                if (abs((float)$existing->valorTotal - $valorTotal) > 0.01) $changes['valorTotal'] = $valorTotal;
                if (abs((float)$existing->valorFlete - $valorTransporte) > 0.01) $changes['valorFlete'] = $valorTransporte;
                if (abs((float)$existing->valorSeguro - $valorSeguro) > 0.01) $changes['valorSeguro'] = $valorSeguro;
                if ((int)$existing->isContrapago !== $esContrapago) $changes['isContrapago'] = $esContrapago;
                if ($esContrapago && abs((float)$existing->contrapagoCost - $valorComercial) > 0.01) {
                    $changes['contrapagoCost'] = $valorComercial;
                }
                if ($esContrapago && (float)$existing->valorDeclarado < 1 && $valorComercial > 0) {
                    $changes['valorDeclarado'] = $valorComercial;
                }
                if (empty($existing->recipientName) && !empty($destinatario)) $changes['recipientName'] = $destinatario;
                if (empty($existing->ciudadDestinoNombre) && !empty($ciudadDestino)) $changes['ciudadDestinoNombre'] = $ciudadDestino;
                if ($piezas > 0 && (!isset($existing->numeroPiezas) || (int)$existing->numeroPiezas !== $piezas)) {
                    $changes['numeroPiezas'] = $piezas;
                }

                if (!empty($changes)) {
                    $changes['updated_at'] = date('Y-m-d H:i:s');
                    $this->db->where('id', $existing->id)->update('shipping_guides', $changes);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                // Crear nueva guía
                $status = 'creado';
                $estadoCode = 0;
                if (stripos($estadoGestion, 'Entregada') !== false) { $status = 'entregado'; $estadoCode = 11; }
                elseif (stripos($ultimoEstado, 'Anulada') !== false) { $status = 'anulado'; $estadoCode = 15; }

                $this->db->insert('shipping_guides', array(
                    'invoiceId' => 0,
                    'numeroPreenvio' => $numGuia,
                    'status' => $status,
                    'estadoGuia' => $estadoCode,
                    'estadoNombre' => $ultimoEstado ?: 'Importado Excel',
                    'valorFlete' => $valorTransporte,
                    'valorSeguro' => $valorSeguro,
                    'valorTotal' => $valorTotal,
                    'valorDeclarado' => $valorComercial,
                    'isContrapago' => $esContrapago,
                    'contrapagoCost' => $esContrapago ? $valorComercial : 0,
                    'ciudadDestinoNombre' => $ciudadDestino,
                    'recipientName' => $destinatario,
                    'numeroPiezas' => $piezas ?: 1,
                    'observations' => 'Importado desde Excel Inter',
                    'created_by' => $user,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ));
                $created++;
            }
        }

        echo json_encode(array(
            'success' => true,
            'updated' => $updated,
            'created' => $created,
            'skipped' => $skipped,
            'total' => $updated + $created + $skipped
        ));
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
     * AJAX: Actualizar datos financieros de una guía
     */
    public function updateFinancial() {
        header('Content-Type: application/json');

        $id = (int) $this->input->post('id');
        if (!$id) {
            echo json_encode(array('error' => 'ID requerido'));
            return;
        }

        $guide = $this->shipping_model->getShipment($id);
        if (!$guide) {
            echo json_encode(array('error' => 'Guía no encontrada'));
            return;
        }

        date_default_timezone_set("America/Bogota");

        $data = array(
            'valorTotal'     => (float) $this->input->post('valorTotal'),
            'isContrapago'   => (int) $this->input->post('isContrapago'),
            'contrapagoCost' => (float) $this->input->post('contrapagoCost'),
            'observations'   => trim($this->input->post('observations')),
            'updated_at'     => date('Y-m-d H:i:s')
        );

        // Si no es contrapago, limpiar el valor
        if (!$data['isContrapago']) {
            $data['contrapagoCost'] = 0;
        }

        $this->db->where('id', $id);
        $ok = $this->db->update('shipping_guides', $data);

        echo json_encode(array(
            'success' => $ok,
            'message' => $ok ? 'Guía actualizada' : 'Error al actualizar'
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
