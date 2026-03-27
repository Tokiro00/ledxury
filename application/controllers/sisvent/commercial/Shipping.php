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
        $pesoUnitario = (float) $this->input->post('peso') ?: 1;
        $piezas = (int) $this->input->post('numeroPiezas') ?: 1;
        $valorDeclarado = (float) $this->input->post('valorDeclarado') ?: 100000;
        $idTipoEntrega = (int) $this->input->post('idTipoEntrega') ?: 1;
        $contrapago = (int) $this->input->post('contrapago') ? true : false;

        // Cotizar UNA caja (cada guía es individual)
        $resultado = $this->interrapidisimo_lib->cotizar($ciudadId, $pesoUnitario, $valorDeclarado, $idTipoEntrega, $contrapago);

        header('Content-Type: application/json');
        if (!$resultado || is_string($resultado)) {
            $msg = is_string($resultado) ? $resultado : 'No se pudo cotizar. Verifique la ciudad destino.';
            echo json_encode(array('error' => $msg, 'debug' => array('ciudadId' => $ciudadId, 'peso' => $pesoUnitario)));
            return;
        }

        echo json_encode(array('success' => true, 'servicios' => $resultado, 'piezas' => $piezas));
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

        // Separar nombre completo en nombre + apellidos (API requiere primerApellido)
        $fullName = $this->input->post('nombre') ?: $invoice->client_name;
        $parts = preg_split('/\s+/', trim($fullName));
        $numParts = count($parts);
        if ($numParts >= 4) {
            // JUAN CARLOS GARCIA LOPEZ → nombre: JUAN CARLOS, apellido1: GARCIA, apellido2: LOPEZ
            $nombre = implode(' ', array_slice($parts, 0, $numParts - 2));
            $apellido1 = $parts[$numParts - 2];
            $apellido2 = $parts[$numParts - 1];
        } elseif ($numParts == 3) {
            $nombre = $parts[0];
            $apellido1 = $parts[1];
            $apellido2 = $parts[2];
        } elseif ($numParts == 2) {
            $nombre = $parts[0];
            $apellido1 = $parts[1];
            $apellido2 = '';
        } else {
            $nombre = $fullName;
            $apellido1 = $fullName;
            $apellido2 = '';
        }

        $piezas = (int) $this->input->post('numeroPiezas') ?: 1;
        $pesoUnitario = (float) $this->input->post('peso') ?: 1;

        // Datos base del destinatario
        $data = array(
            'peso' => $pesoUnitario,
            'largo' => (float) $this->input->post('largo') ?: 30,
            'ancho' => (float) $this->input->post('ancho') ?: 20,
            'alto' => (float) $this->input->post('alto') ?: 15,
            'valorDeclarado' => (float) $this->input->post('valorDeclarado'),
            'idServicio' => (int) $this->input->post('idServicio') ?: 3,
            'idTipoEnvio' => (int) $this->input->post('idTipoEnvio') ?: 3,
            'idTipoEntrega' => (int) $this->input->post('idTipoEntrega') ?: 1,
            'numeroPiezas' => 1,
            'diceContener' => 'Factura #' . $invoiceId . ' - ' . ($this->input->post('diceContener') ?: 'Mercancía'),
            'documento' => $this->input->post('documento') ?: $invoice->client_idNum,
            'nombre' => $nombre,
            'apellido' => $apellido1,
            'apellido2' => $apellido2,
            'telefono' => $this->input->post('telefono') ?: ($invoice->client_cellphone ?: $invoice->client_phone),
            'direccion' => $this->input->post('direccion') ?: ($invoice->client_address ?: 'Sin dirección'),
            'ciudadDestinoId' => $this->input->post('ciudadDestinoId'),
            'correo' => isset($invoice->client_email) ? $invoice->client_email : '',
            'observaciones' => $this->input->post('observaciones') ?: '',
            'contrapago' => (int) $this->input->post('contrapago') ? true : false
        );

        header('Content-Type: application/json');
        date_default_timezone_set("America/Bogota");

        $esContrapago = $data['contrapago'];
        $guiasNums = array();
        $fleteTotal = 0;
        $primerResultado = null;
        $storeId = isset($invoice->storeId) ? $invoice->storeId : 1;
        $user = $this->session->userdata('user_data')['uname'];
        $ciudadNombre = $this->input->post('ciudadDestinoNombre') ?: '';

        // Crear una guía en Inter por cada pieza
        for ($i = 1; $i <= $piezas; $i++) {
            $data['diceContener'] = "Factura #{$invoiceId} - Caja {$i}/{$piezas}";

            $resultado = $this->interrapidisimo_lib->crearPreenvio($data);

            if (!$resultado || is_string($resultado) || isset($resultado->Message) || isset($resultado->UuidException)) {
                $msg = 'Error al crear guía ' . $i . '/' . $piezas;
                if (is_string($resultado)) $msg .= ': ' . $resultado;
                elseif (isset($resultado->Message)) $msg .= ': ' . $resultado->Message;
                elseif (isset($resultado->ErrorMessage)) $msg .= ': ' . $resultado->ErrorMessage;
                elseif (isset($resultado->ExceptionMessage)) $msg .= ': ' . $resultado->ExceptionMessage;
                if (!empty($guiasNums)) $msg .= '. Guías ya creadas: ' . implode(', ', $guiasNums);
                echo json_encode(array('error' => $msg, 'debug' => $resultado));
                return;
            }

            $valorGuia = (float) $resultado->valorFlete + (float) $resultado->valorSobreFlete;
            $fleteTotal += $valorGuia;
            $guiasNums[] = $resultado->numeroPreenvio;
            if (!$primerResultado) $primerResultado = $resultado;

            if ($i < $piezas) usleep(300000);
        }

        // Guardar UN solo registro en BD con todas las guías
        $this->db->insert('shipping_guides', array(
            'invoiceId' => $invoiceId,
            'numeroPreenvio' => $primerResultado->numeroPreenvio,
            'guiasHijas' => json_encode($guiasNums),
            'idPreenvio' => $primerResultado->idPreenvio,
            'status' => 'creado',
            'peso' => $pesoUnitario,
            'numeroPiezas' => $piezas,
            'largo' => $data['largo'],
            'ancho' => $data['ancho'],
            'alto' => $data['alto'],
            'valorDeclarado' => $data['valorDeclarado'],
            'diceContener' => "Factura #{$invoiceId} - {$piezas} caja(s)",
            'idServicio' => $data['idServicio'],
            'idTipoEnvio' => $data['idTipoEnvio'],
            'idTipoEntrega' => $data['idTipoEntrega'],
            'isContrapago' => $esContrapago ? 1 : 0,
            'contrapagoCost' => $esContrapago ? $data['valorDeclarado'] : 0,
            'valorFlete' => $fleteTotal,
            'valorSeguro' => 0,
            'valorTotal' => $fleteTotal,
            'ciudadDestinoId' => $data['ciudadDestinoId'],
            'ciudadDestinoNombre' => $ciudadNombre,
            'recipientName' => $data['nombre'] . ' ' . $data['apellido'],
            'recipientPhone' => $data['telefono'],
            'recipientAddress' => $data['direccion'],
            'recipientDoc' => $data['documento'],
            'estadoGuia' => 0,
            'estadoNombre' => 'Creado',
            'storeId' => $storeId,
            'observations' => $data['observaciones'],
            'created_by' => $user,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ));

        // Agregar FLETE a la factura SOLO si MAM paga
        $fleteMsg = '';
        if (!$esContrapago) {
            $fleteProduct = $this->db->where('idProduct', 'FLETE')->get('products')->row();
            if ($fleteProduct) {
                $this->db->insert('invoice_details', array(
                    'invoiceId' => $invoiceId,
                    'productId' => 'FLETE',
                    'quantity' => $piezas,
                    'unit' => round($fleteTotal / $piezas),
                    'base' => $fleteTotal,
                    'total' => $fleteTotal
                ));

                $this->db->set('total', 'total + ' . $fleteTotal, FALSE);
                $this->db->where('idInvoice', $invoiceId);
                $this->db->update('invoices');
            }
            $fleteMsg = "Flete \$" . number_format($fleteTotal, 0, ',', '.') . " agregado a la factura.";
        } else {
            $fleteMsg = "Pago en casa: el cliente pagará \$" . number_format($fleteTotal, 0, ',', '.') . " al recibir.";
        }

        echo json_encode(array(
            'success' => true,
            'guias' => $guiasNums,
            'piezas' => $piezas,
            'valorFlete' => $fleteTotal,
            'contrapago' => $esContrapago,
            'mensaje' => $piezas . " caja(s), " . count($guiasNums) . " guía(s) creada(s). " . $fleteMsg
        ));
    }

    /**
     * AJAX: Eliminar una guía y revertir FLETE de la factura
     */
    public function eliminarGuia() {
        $guideId = (int) $this->input->post('guideId');
        $guide = $this->db->where('id', $guideId)->get('shipping_guides')->row();

        header('Content-Type: application/json');

        if (!$guide) {
            echo json_encode(array('error' => 'Guía no encontrada'));
            return;
        }

        $invoiceId = $guide->invoiceId;
        $valorGuia = (float) $guide->valorTotal;
        $esContrapago = (int) $guide->isContrapago;

        // Eliminar guía de BD
        $this->db->where('id', $guideId)->delete('shipping_guides');

        // Eliminar eventos de tracking asociados
        $this->db->where('guideId', $guideId)->delete('shipping_tracking_events');

        // Si NO era contrapago, revertir el FLETE de la factura
        if (!$esContrapago && $valorGuia > 0) {
            // Contar cuántas guías quedan para esta factura (no contrapago)
            $remaining = $this->db->where('invoiceId', $invoiceId)
                ->where('isContrapago', 0)
                ->count_all_results('shipping_guides');

            if ($remaining == 0) {
                // No quedan guías: eliminar línea FLETE completa
                $this->db->where('invoiceId', $invoiceId)
                    ->where('productId', 'FLETE')
                    ->delete('invoice_details');
            } else {
                // Restar valor de esta guía del FLETE existente
                $this->db->set('total', 'total - ' . $valorGuia, FALSE);
                $this->db->set('base', 'base - ' . $valorGuia, FALSE);
                $this->db->set('quantity', $remaining);
                $this->db->where('invoiceId', $invoiceId);
                $this->db->where('productId', 'FLETE');
                $this->db->update('invoice_details');
            }

            // Actualizar total de la factura
            $this->db->set('total', 'total - ' . $valorGuia, FALSE);
            $this->db->where('idInvoice', $invoiceId);
            $this->db->update('invoices');
        }

        echo json_encode(array(
            'success' => true,
            'mensaje' => 'Guía ' . $guide->numeroPreenvio . ' eliminada.'
        ));
    }

    /**
     * Descargar PDF de una guía individual
     */
    public function descargarGuia($numeroGuia, $debug = '') {
        $pdf = $this->interrapidisimo_lib->obtenerGuiaPdf($numeroGuia);

        // Debug mode: ver qué devuelve la API
        if ($debug === 'debug') {
            header('Content-Type: application/json');
            echo json_encode(array(
                'type' => gettype($pdf),
                'is_null' => is_null($pdf),
                'is_false' => ($pdf === false),
                'is_string' => is_string($pdf),
                'is_object' => is_object($pdf),
                'is_array' => is_array($pdf),
                'preview' => is_string($pdf) ? substr($pdf, 0, 200) : (is_object($pdf) || is_array($pdf) ? json_encode($pdf) : var_export($pdf, true))
            ));
            return;
        }

        if (!$pdf) {
            show_error('No se pudo obtener la guía. Intente con: ' . base_url() . 'sisvent/commercial/shipping/descargarGuia/' . $numeroGuia . '/debug');
            return;
        }

        $bytes = $this->_extractPdfBytes($pdf);
        if (!$bytes) {
            show_error('Formato de respuesta no reconocido: ' . gettype($pdf));
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="guia_' . $numeroGuia . '.pdf"');
        echo $bytes;
    }

    /**
     * Imprimir todas las guías de una factura
     * Descarga cada PDF y los une con FPDI
     */
    public function imprimirGuias($invoiceId) {
        $registros = $this->db->where('invoiceId', (int)$invoiceId)
            ->where('numeroPreenvio IS NOT NULL')
            ->where('numeroPreenvio !=', 0)
            ->order_by('created_at', 'ASC')
            ->get('shipping_guides')->result();

        if (empty($registros)) {
            show_error('No hay guías para esta factura');
            return;
        }

        // Recopilar todos los números de guía (incluyendo hijas)
        $numeros = array();
        foreach ($registros as $r) {
            if (!empty($r->guiasHijas)) {
                $hijas = json_decode($r->guiasHijas, true);
                if (is_array($hijas)) {
                    $numeros = array_merge($numeros, $hijas);
                    continue;
                }
            }
            $numeros[] = $r->numeroPreenvio;
        }
        $numeros = array_unique($numeros);

        // Descargar cada PDF
        $allPages = array();
        foreach ($numeros as $num) {
            $pdf = $this->interrapidisimo_lib->obtenerGuiaPdf($num);
            if ($pdf) {
                $bytes = $this->_extractPdfBytes($pdf);
                if ($bytes) $allPages[] = $bytes;
            }
            usleep(200000);
        }

        if (empty($allPages)) {
            show_error('No se pudieron obtener las guías desde Interrapidísimo');
            return;
        }

        // Generar página HTML con cada guía como PDF embebido
        // Cada una en su propio visor con scroll independiente
        $total = count($allPages);
        $titulo = $total . ' guía' . ($total > 1 ? 's' : '') . ' - Factura #' . $invoiceId;
        ?>
<!DOCTYPE html>
<html><head>
<title><?= $titulo ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#333; font-family:Arial,sans-serif; }
.toolbar { position:fixed; top:0; left:0; right:0; z-index:10; background:#1B365D; color:white; padding:8px 16px; display:flex; align-items:center; justify-content:space-between; }
.toolbar strong { font-size:14px; }
.toolbar button, .toolbar a { padding:6px 16px; border-radius:4px; font-size:13px; font-weight:bold; cursor:pointer; text-decoration:none; border:none; }
.btn-print { background:#2E7D91; color:white; }
.btn-dl { background:#FF6B00; color:white; }
.guides-grid { padding:56px 16px 16px; display:flex; flex-wrap:wrap; gap:12px; justify-content:center; }
.guide-card { background:white; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.3); }
.guide-card .label { background:#1B365D; color:white; padding:6px 12px; font-size:12px; font-weight:bold; text-align:center; }
.guide-card embed { display:block; }
@media print {
  .toolbar { display:none; }
  body { background:white; }
  .guides-grid { padding:0; gap:0; }
  .guide-card { box-shadow:none; border-radius:0; page-break-after:always; }
  .guide-card .label { background:#000; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
</head><body>
<div class="toolbar">
    <strong><?= $titulo ?></strong>
    <div style="display:flex; gap:8px;">
        <?php foreach ($numeros as $idx => $num): ?>
        <a class="btn-dl" href="<?= base_url() ?>sisvent/commercial/shipping/descargarGuia/<?= $num ?>" target="_blank" title="Descargar guía <?= $idx+1 ?>">Guía <?= $idx+1 ?></a>
        <?php endforeach; ?>
    </div>
</div>
<div class="guides-grid">
<?php foreach ($allPages as $idx => $pdfContent):
    $b64 = base64_encode($pdfContent);
    $num = isset($numeros[$idx]) ? $numeros[$idx] : '';
?>
    <div class="guide-card">
        <div class="label">Caja <?= $idx + 1 ?> de <?= $total ?> &mdash; Guía <?= $num ?></div>
        <embed src="data:application/pdf;base64,<?= $b64 ?>" type="application/pdf" width="600" height="420">
    </div>
<?php endforeach; ?>
</div>
</body></html>
        <?php
    }

    /**
     * Extraer bytes de PDF de la respuesta de la API
     * La API puede devolver: string base64, objeto con Bytes, o array
     */
    private function _extractPdfBytes($response) {
        if (is_string($response)) {
            return base64_decode($response);
        }
        if (is_object($response)) {
            if (isset($response->Bytes)) return base64_decode($response->Bytes);
            if (isset($response->bytes)) return base64_decode($response->bytes);
        }
        if (is_array($response)) {
            // A veces devuelve un array de bytes directamente
            $str = implode('', array_map('chr', $response));
            return $str;
        }
        return null;
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
     * Usa tabla dane_municipalities (más rápido que API o archivo)
     */
    public function buscarCiudad() {
        $term = $this->input->get('q');
        header('Content-Type: application/json');

        if (strlen($term) < 2) {
            echo json_encode(array());
            return;
        }

        // Buscar solo municipios principales (daneCode termina en 000)
        // Las sub-localidades no tienen Centro de Servicios en Interrapidísimo
        $results = $this->db->select('daneCode as id, shortName as nombre, department as departamento')
            ->from('dane_municipalities')
            ->like('daneCode', '000', 'before')
            ->group_start()
                ->like('shortName', $term, 'both')
                ->or_like('name', $term, 'both')
            ->group_end()
            ->limit(10)
            ->get()->result();

        $output = array();
        foreach ($results as $r) {
            $r->label = $r->nombre . ' - ' . $r->departamento;
            $output[] = $r;
        }

        echo json_encode($output);
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
