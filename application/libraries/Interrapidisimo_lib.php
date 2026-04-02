<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Interrapidisimo_lib — Integración con API REST de Inter Rapidísimo
 *
 * Maneja: cotización, admisión de preenvíos, impresión de guías,
 * recogidas esporádicas y consulta de estados.
 */
class Interrapidisimo_lib {

    private $CI;
    private $baseUrl;
    private $signature;
    private $token;
    private $idCliente;
    private $idSucursal;
    private $ciudadOrigenId;

    public function __construct() {
        $this->CI =& get_instance();
        // Cargar secrets.php directamente
        $secretsFile = APPPATH . 'config/secrets.php';
        if (file_exists($secretsFile)) {
            include($secretsFile);
            $secrets = isset($config['interrapidisimo']) ? $config['interrapidisimo'] : array();
        } else {
            $secrets = array();
        }
        $this->baseUrl = isset($secrets['base_url']) ? $secrets['base_url'] : 'https://www3.interrapidisimo.com';
        $this->signature = isset($secrets['signature']) ? $secrets['signature'] : '';
        $this->token = isset($secrets['token']) ? $secrets['token'] : '';
        $this->idCliente = isset($secrets['id_cliente']) ? $secrets['id_cliente'] : 0;
        $this->idSucursal = isset($secrets['id_sucursal']) ? $secrets['id_sucursal'] : 0;
        $this->ciudadOrigenId = isset($secrets['ciudad_origen']) ? $secrets['ciudad_origen'] : '05088000';
    }

    /**
     * Cotizar envío
     * @return array|false Lista de servicios con precios
     */
    public function cotizar($ciudadDestinoId, $peso, $valorDeclarado, $idTipoEntrega = 1, $aplicaContrapago = false) {
        $fecha = date('d-m-Y');
        $contrapago = $aplicaContrapago ? 'TRUE' : 'FALSE';

        $url = "{$this->baseUrl}/ApiServInter/api/CotizadorCliente/ResultadoListaCotizarValidaContrapago"
             . "/{$this->idCliente}/{$this->ciudadOrigenId}/{$ciudadDestinoId}/{$peso}/{$valorDeclarado}/{$idTipoEntrega}/{$fecha}/{$contrapago}";

        return $this->_get($url);
    }

    /**
     * Crear preenvío (admisión) — genera número de guía
     * @return object|false {idPreenvio, numeroPreenvio, valorFlete, valorSobreFlete}
     */
    public function crearPreenvio($data) {
        $body = array(
            'IdClienteCredito' => $this->idCliente,
            'CodigoConvenioRemitente' => $this->idSucursal,
            'IdTipoEntrega' => isset($data['idTipoEntrega']) ? $data['idTipoEntrega'] : 1,
            'AplicaContrapago' => isset($data['contrapago']) ? $data['contrapago'] : false,
            'ValorRecaudar' => (isset($data['contrapago']) && $data['contrapago']) ? $data['valorDeclarado'] : 0,
            'IdServicio' => isset($data['idServicio']) ? $data['idServicio'] : 3,
            'Peso' => $data['peso'],
            'Largo' => isset($data['largo']) ? $data['largo'] : 10,
            'Ancho' => isset($data['ancho']) ? $data['ancho'] : 10,
            'Alto' => isset($data['alto']) ? $data['alto'] : 10,
            'DiceContener' => $data['diceContener'],
            'ValorDeclarado' => $data['valorDeclarado'],
            'IdTipoEnvio' => isset($data['idTipoEnvio']) ? $data['idTipoEnvio'] : 3,
            'IdFormaPago' => 2, // Crédito
            'NumeroPieza' => isset($data['numeroPiezas']) ? $data['numeroPiezas'] : 1,
            'Destinatario' => array(
                'tipoDocumento' => isset($data['tipoDoc']) ? $data['tipoDoc'] : 'CC',
                'numeroDocumento' => $data['documento'],
                'nombre' => $data['nombre'],
                'primerApellido' => isset($data['apellido']) ? $data['apellido'] : 'N/A',
                'segundoApellido' => isset($data['apellido2']) ? $data['apellido2'] : '',
                'telefono' => $data['telefono'],
                'direccion' => $data['direccion'],
                'idRemitente' => 0,
                'idDestinatario' => 0,
                'idLocalidad' => $data['ciudadDestinoId'],
                'CodigoConvenio' => 0,
                'ConvenioDestinatario' => 0,
                'correo' => isset($data['correo']) ? $data['correo'] : ''
            ),
            'DescripcionTipoEntrega' => '',
            'NombreTipoEnvio' => '',
            'CodigoConvenio' => 0,
            'IdSucursal' => 0,
            'IdCliente' => 0,
            'RapiRadicado' => array('numerodeFolios' => 0, 'CodigoRapiRadicado' => 0),
            'Observaciones' => isset($data['observaciones']) ? $data['observaciones'] : ''
        );

        return $this->_post("{$this->baseUrl}/ApiVentaCredito/api/Admision/InsertarAdmision", $body);
    }

    /**
     * Obtener PDF de guía (media carta) en Base64
     */
    public function obtenerGuiaPdf($numeroGuia) {
        return $this->_get("{$this->baseUrl}/ApiVentaCredito/api/Admision/ObtenerBase64PdfPreGuia/{$numeroGuia}");
    }

    /**
     * Obtener PDF de guía pequeña (cuarto de página)
     */
    public function obtenerGuiaPdfPeq($numeroGuia) {
        return $this->_get("{$this->baseUrl}/ApiVentaCredito/api/Admision/ObtenerBase64PdfPreGuiaFormatoPeq/{$numeroGuia}");
    }

    /**
     * Obtener PDF de múltiples guías (lote) en Base64
     */
    public function obtenerGuiasPdfLote($guias) {
        return $this->_post("{$this->baseUrl}/ApiVentaCredito/api/Admision/ObtenerBase64PdfPreGuias", $guias);
    }

    /**
     * Solicitar recogida esporádica
     */
    public function solicitarRecogida($guias, $fechaRecogida = null) {
        if (!$fechaRecogida) $fechaRecogida = date('Y-m-d H:i');

        $body = array(
            'IdClienteCredito' => $this->idCliente,
            'IdSucursalCliente' => $this->idSucursal,
            'listaNumPreenvios' => $guias,
            'fechaRecogida' => $fechaRecogida
        );

        return $this->_post("{$this->baseUrl}/ApiVentaCredito/api/Recogida/InsertarRecogidaCliente/", $body);
    }

    /**
     * Consultar estados de guías
     */
    public function consultarEstados($guias) {
        $body = array(
            'idCliente' => $this->idCliente,
            'numeroGuias' => $guias
        );

        return $this->_post("{$this->baseUrl}/ApiVentaCredito/api/ClientesCredito/ConsultarEstadosGuiasCliente", $body);
    }

    /**
     * Obtener centros de servicio (oficinas) por ciudad
     * @param string $idCiudad Código DANE de la ciudad
     * @param int $idZona Zona (0 = todas)
     * @param int $idDia Día de la semana (1=Lun..7=Dom, 0=todos)
     */
    public function obtenerCentrosServicio($idCiudad, $idZona = 0, $idDia = 0) {
        return $this->_get("{$this->baseUrl}/Apicontroller/api/CentrosServicio/ObtenerCentrosServicioNacional/{$idCiudad}/{$idZona}/{$idDia}");
    }

    /**
     * Obtener sucursales activas del cliente
     */
    public function obtenerSucursales() {
        return $this->_get("{$this->baseUrl}/ApiVentaCredito/api/ClientesCredito/ObtenerSucursalesActivasPorCliente?idCliente={$this->idCliente}");
    }

    /**
     * Intentar obtener guías por rango de fechas (endpoint exploratorio)
     */
    public function consultarGuiasPorFecha($fechaInicio, $fechaFin) {
        $body = array(
            'idCliente' => $this->idCliente,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin
        );
        return $this->_post("{$this->baseUrl}/ApiVentaCredito/api/ClientesCredito/ConsultarGuiasCliente", $body);
    }

    /**
     * Consultar movimientos/extracto del cliente (endpoint exploratorio)
     */
    public function consultarMovimientos($fechaInicio, $fechaFin) {
        $body = array(
            'idCliente' => $this->idCliente,
            'idSucursal' => $this->idSucursal,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin
        );
        return $this->_post("{$this->baseUrl}/ApiVentaCredito/api/ClientesCredito/ConsultarMovimientosCliente", $body);
    }

    /**
     * Obtener localidades (ciudades)
     */
    public function obtenerLocalidades() {
        return $this->_get("{$this->baseUrl}/Apicontroller/api/ParametrosFramework/ObtenerLocalidadesNoPaisNoDepartamentoColombia");
    }

    // ---------------------------------------------------------------
    // HTTP Helpers
    // ---------------------------------------------------------------

    private function _get($url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->_headers(),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ));
        $response = curl_exec($ch);

        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            log_message('error', "Interrapidisimo GET cURL error: {$err} - {$url}");
            return 'Error de conexión: ' . $err;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            log_message('error', "Interrapidisimo GET {$url} - HTTP {$httpCode}: {$response}");
            return false;
        }

        return json_decode($response);
    }

    private function _post($url, $body) {
        $ch = curl_init($url);
        $json = json_encode($body);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => array_merge($this->_headers(), array('Content-Type: application/json')),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ));
        $response = curl_exec($ch);

        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            log_message('error', "Interrapidisimo POST cURL error: {$err} - {$url}");
            return 'Error de conexión: ' . $err;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        log_message('debug', "Interrapidisimo POST {$url} - HTTP {$httpCode} - Body: {$json} - Response: {$response}");

        if ($httpCode >= 400) {
            log_message('error', "Interrapidisimo POST {$url} - HTTP {$httpCode}: {$response}");
            // Intentar decodificar error del API
            $decoded = json_decode($response);
            return $decoded ? $decoded : "HTTP {$httpCode}: {$response}";
        }

        return json_decode($response);
    }

    private function _headers() {
        return array(
            'x-app-signature: ' . $this->signature,
            'x-app-security_token: ' . $this->token
        );
    }
}
