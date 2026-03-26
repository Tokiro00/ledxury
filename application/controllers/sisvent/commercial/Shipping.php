<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shipping extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->backend_lib->controlModule('facturas');
        $this->load->library('interrapidisimo_lib');
        $this->load->model('invoices_model');
    }

    /**
     * AJAX: Cotizar envío para una factura
     * POST con: invoiceId, peso, ciudadDestinoId
     */
    public function cotizar() {
        $ciudadId = $this->input->post('ciudadDestinoId');
        $peso = (float) $this->input->post('peso') ?: 1;
        $valorDeclarado = (float) $this->input->post('valorDeclarado') ?: 100000;

        $resultado = $this->interrapidisimo_lib->cotizar($ciudadId, $peso, $valorDeclarado);

        header('Content-Type: application/json');
        if (!$resultado) {
            echo json_encode(array('error' => 'No se pudo cotizar. Verifique la ciudad destino.'));
            return;
        }

        echo json_encode(array('success' => true, 'servicios' => $resultado));
    }

    /**
     * AJAX: Crear guía (preenvío) y agregar FLETE a la factura
     * POST con todos los datos del envío
     */
    public function crearGuia() {
        $invoiceId = (int) $this->input->post('invoiceId');
        $invoice = $this->invoices_model->getInvoice($invoiceId);

        if (!$invoice) {
            header('Content-Type: application/json');
            echo json_encode(array('error' => 'Factura no encontrada'));
            return;
        }

        // Datos del destinatario — usa POST (editables), fallback a factura
        $data = array(
            'peso' => (float) $this->input->post('peso'),
            'largo' => (float) $this->input->post('largo') ?: 30,
            'ancho' => (float) $this->input->post('ancho') ?: 20,
            'alto' => (float) $this->input->post('alto') ?: 15,
            'valorDeclarado' => (float) $this->input->post('valorDeclarado'),
            'idServicio' => (int) $this->input->post('idServicio') ?: 3,
            'idTipoEnvio' => (int) $this->input->post('idTipoEnvio') ?: 3,
            'idTipoEntrega' => (int) $this->input->post('idTipoEntrega') ?: 1,
            'numeroPiezas' => (int) $this->input->post('numeroPiezas') ?: 1,
            'diceContener' => 'Factura #' . $invoiceId . ' - ' . ($this->input->post('diceContener') ?: 'Mercancía'),
            'documento' => $this->input->post('documento') ?: $invoice->client_idNum,
            'nombre' => $this->input->post('nombre') ?: $invoice->client_name,
            'apellido' => '',
            'telefono' => $this->input->post('telefono') ?: ($invoice->client_cellphone ?: $invoice->client_phone),
            'direccion' => $this->input->post('direccion') ?: ($invoice->client_address ?: 'Sin dirección'),
            'ciudadDestinoId' => $this->input->post('ciudadDestinoId'),
            'correo' => isset($invoice->client_email) ? $invoice->client_email : '',
            'observaciones' => $this->input->post('observaciones') ?: ''
        );

        $resultado = $this->interrapidisimo_lib->crearPreenvio($data);

        header('Content-Type: application/json');

        if (!$resultado || isset($resultado->Message) || isset($resultado->UuidException)) {
            $msg = 'Error al crear el preenvío';
            if (isset($resultado->Message)) $msg = $resultado->Message;
            if (isset($resultado->ErrorMessage)) $msg = $resultado->ErrorMessage;
            if (is_string($resultado)) $msg = $resultado;
            echo json_encode(array('error' => $msg, 'debug' => $resultado));
            return;
        }

        // Guardar guía en BD
        date_default_timezone_set("America/Bogota");
        $valorTotal = (float) $resultado->valorFlete + (float) $resultado->valorSobreFlete;

        $this->db->insert('shipping_guides', array(
            'invoiceId' => $invoiceId,
            'numeroPreenvio' => $resultado->numeroPreenvio,
            'idPreenvio' => $resultado->idPreenvio,
            'status' => 'creado',
            'peso' => $data['peso'],
            'largo' => $data['largo'],
            'ancho' => $data['ancho'],
            'alto' => $data['alto'],
            'valorDeclarado' => $data['valorDeclarado'],
            'diceContener' => $data['diceContener'],
            'idServicio' => $data['idServicio'],
            'idTipoEnvio' => $data['idTipoEnvio'],
            'idTipoEntrega' => $data['idTipoEntrega'],
            'valorFlete' => $resultado->valorFlete,
            'valorSeguro' => $resultado->valorSobreFlete,
            'valorTotal' => $valorTotal,
            'ciudadDestinoId' => $data['ciudadDestinoId'],
            'ciudadDestinoNombre' => $this->input->post('ciudadDestinoNombre') ?: '',
            'estadoGuia' => 11,
            'estadoNombre' => 'Creado',
            'observations' => $data['observaciones'],
            'created_by' => $this->session->userdata('user_data')['uname'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ));

        // Agregar producto FLETE a la factura
        $fleteProduct = $this->db->where('idProduct', 'FLETE')->get('products')->row();
        if ($fleteProduct) {
            $this->db->insert('invoice_details', array(
                'invoiceId' => $invoiceId,
                'productId' => 'FLETE',
                'quantity' => 1,
                'unit' => $valorTotal,
                'base' => $valorTotal,
                'total' => $valorTotal
            ));

            // Actualizar total de la factura
            $this->db->set('total', 'total + ' . $valorTotal, FALSE);
            $this->db->where('idInvoice', $invoiceId);
            $this->db->update('invoices');
        }

        echo json_encode(array(
            'success' => true,
            'guia' => $resultado->numeroPreenvio,
            'valorFlete' => $valorTotal,
            'mensaje' => "Guía {$resultado->numeroPreenvio} creada. Flete \$" . number_format($valorTotal, 0, ',', '.') . " agregado a la factura."
        ));
    }

    /**
     * Descargar PDF de la guía
     */
    public function descargarGuia($numeroGuia) {
        $pdf = $this->interrapidisimo_lib->obtenerGuiaPdf($numeroGuia);

        if (!$pdf) {
            show_error('No se pudo obtener la guía');
            return;
        }

        // El API devuelve un arreglo de bytes en Base64
        $pdfBytes = is_string($pdf) ? $pdf : (isset($pdf->Bytes) ? $pdf->Bytes : json_encode($pdf));

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="guia_' . $numeroGuia . '.pdf"');
        echo base64_decode($pdfBytes);
    }

    /**
     * AJAX: Consultar estado de tracking de una guía
     */
    public function tracking() {
        $numeroGuia = (int) $this->input->post('numeroGuia');

        $resultado = $this->interrapidisimo_lib->consultarEstados(array($numeroGuia));

        header('Content-Type: application/json');
        if (!$resultado) {
            echo json_encode(array('error' => 'No se pudo consultar el estado'));
            return;
        }

        // Actualizar estado en BD local
        if (isset($resultado->listadoGuias) && !empty($resultado->listadoGuias)) {
            $guia = $resultado->listadoGuias[0];
            if (!empty($guia->estadosGuia)) {
                $ultimoEstado = $guia->estadosGuia[0];
                $this->db->where('numeroPreenvio', $numeroGuia);
                $this->db->update('shipping_guides', array(
                    'estadoGuia' => $ultimoEstado->idEstadoGuia,
                    'estadoNombre' => $ultimoEstado->nombreEstado,
                    'fechaEstado' => $ultimoEstado->fechaEstado,
                    'updated_at' => date('Y-m-d H:i:s')
                ));
            }
        }

        echo json_encode(array('success' => true, 'data' => $resultado));
    }

    /**
     * AJAX: Solicitar recogida
     */
    public function solicitarRecogida() {
        $guias = $this->input->post('guias');

        if (!is_array($guias)) {
            $guias = array((int) $guias);
        }

        $resultado = $this->interrapidisimo_lib->solicitarRecogida($guias);

        header('Content-Type: application/json');
        if (!$resultado) {
            echo json_encode(array('error' => 'No se pudo solicitar la recogida'));
            return;
        }

        // Actualizar estado de las guías
        foreach ($guias as $g) {
            $this->db->where('numeroPreenvio', $g);
            $this->db->update('shipping_guides', array(
                'status' => 'recogida_solicitada',
                'idRecogida' => isset($resultado->idRecogida) ? $resultado->idRecogida : null,
                'updated_at' => date('Y-m-d H:i:s')
            ));
        }

        echo json_encode(array('success' => true, 'data' => $resultado));
    }

    /**
     * AJAX: Buscar ciudades para el autocompletado
     * Usa cache local en application/cache/ir_localidades.json
     */
    public function buscarCiudad() {
        $term = $this->input->get('q');
        header('Content-Type: application/json');

        if (strlen($term) < 2) {
            echo json_encode(array());
            return;
        }

        $cacheFile = APPPATH . 'cache/ir_localidades.json';

        if (!file_exists($cacheFile)) {
            // Descargar y cachear desde la API
            $localidades = $this->interrapidisimo_lib->obtenerLocalidades();
            if ($localidades) {
                $cities = array();
                foreach ($localidades as $d) {
                    $cities[] = array('id' => $d->IdLocalidad, 'nombre' => $d->NombreCorto, 'depto' => $d->NombreAncestroPGrado, 'label' => $d->NombreCorto . ' - ' . $d->NombreAncestroPGrado);
                }
                file_put_contents($cacheFile, json_encode($cities));
            }
        }

        $cities = json_decode(file_get_contents($cacheFile), true);
        $results = array();
        $term = strtolower($term);

        foreach ($cities as $c) {
            if (stripos($c['nombre'], $term) !== false || stripos($c['label'], $term) !== false) {
                $results[] = $c;
                if (count($results) >= 10) break;
            }
        }

        echo json_encode($results);
    }

    /**
     * Ver guías de una factura (AJAX)
     */
    public function guiasFactura($invoiceId) {
        $guias = $this->db->where('invoiceId', $invoiceId)->order_by('created_at', 'DESC')->get('shipping_guides')->result();

        header('Content-Type: application/json');
        echo json_encode($guias);
    }
}
