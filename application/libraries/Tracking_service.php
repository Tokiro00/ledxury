<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Servicio de Rastreo de Envíos usando 17TRACK API
 *
 * Documentación: https://api.17track.net/track/v2.2
 *
 * @author Sistema SisVent
 */
class Tracking_service {

    protected $CI;

    private $api_key;
    private $api_url;
    private $carrier_codes;
    private $status_mapping;
    private $status_labels;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('tracking', TRUE);

        $this->api_key = $this->CI->config->item('17track_api_key', 'tracking');
        $this->api_url = $this->CI->config->item('17track_api_url', 'tracking');
        $this->carrier_codes = $this->CI->config->item('carrier_codes', 'tracking');
        $this->status_mapping = $this->CI->config->item('status_mapping', 'tracking');
        $this->status_labels = $this->CI->config->item('status_labels', 'tracking');

        log_message('debug', 'Tracking_service: Initialized with API URL: ' . $this->api_url);
    }

    /**
     * Consultar estado de una guía
     *
     * @param string $trackingNumber Número de guía
     * @param string $carrier Transportadora (interrapidisimo, servientrega, etc.)
     * @return array|false Array con estado o false si falla
     */
    public function getStatus($trackingNumber, $carrier = 'interrapidisimo')
    {
        $trackingNumber = trim($trackingNumber);

        if (empty($trackingNumber)) {
            log_message('error', 'Tracking_service: Número de guía vacío');
            return false;
        }

        log_message('info', "Tracking_service: Consultando guía {$trackingNumber} - Carrier: {$carrier}");

        // Paso 1: Registrar la guía en 17TRACK (si no está registrada)
        $registered = $this->registerTracking($trackingNumber, $carrier);

        if (!$registered) {
            log_message('warning', "Tracking_service: No se pudo registrar guía {$trackingNumber}, intentando consultar directamente");
        }

        // Paso 2: Consultar el estado
        $result = $this->queryTracking($trackingNumber, $carrier);

        if ($result) {
            log_message('info', "Tracking_service: Guía {$trackingNumber} - Estado: {$result['status']}");
            return $result;
        }

        log_message('error', "Tracking_service: No se pudo obtener estado para guía {$trackingNumber}");
        return false;
    }

    /**
     * Registrar una guía en 17TRACK para seguimiento
     *
     * @param string $trackingNumber Número de guía
     * @param string $carrier Transportadora
     * @return bool
     */
    public function registerTracking($trackingNumber, $carrier = 'interrapidisimo')
    {
        $carrierCode = $this->carrier_codes[$carrier] ?? 0;

        $endpoint = $this->api_url . '/register';

        $payload = [
            [
                'number' => $trackingNumber,
                'carrier' => $carrierCode > 0 ? $carrierCode : null,
                'auto_detection' => $carrierCode === 0,
            ]
        ];

        $response = $this->makeRequest($endpoint, $payload);

        if ($response === false) {
            return false;
        }

        // Verificar respuesta
        if (isset($response['code']) && $response['code'] === 0) {
            log_message('info', "Tracking_service: Guía {$trackingNumber} registrada exitosamente");
            return true;
        }

        // Si ya está registrada, también es éxito
        if (isset($response['data']['accepted']) && count($response['data']['accepted']) > 0) {
            return true;
        }

        // Si fue rechazada por duplicada, aún podemos consultar
        if (isset($response['data']['rejected'])) {
            foreach ($response['data']['rejected'] as $rejected) {
                if (isset($rejected['error']['code']) && $rejected['error']['code'] === -18019901) {
                    // -18019901 = Already registered
                    log_message('debug', "Tracking_service: Guía {$trackingNumber} ya estaba registrada");
                    return true;
                }
            }
        }

        log_message('warning', "Tracking_service: Error registrando guía {$trackingNumber}: " . json_encode($response));
        return false;
    }

    /**
     * Consultar estado de una guía registrada
     *
     * @param string $trackingNumber Número de guía
     * @param string $carrier Transportadora
     * @return array|false
     */
    private function queryTracking($trackingNumber, $carrier = 'interrapidisimo')
    {
        $carrierCode = $this->carrier_codes[$carrier] ?? 0;

        $endpoint = $this->api_url . '/gettrackinfo';

        $payload = [
            [
                'number' => $trackingNumber,
                'carrier' => $carrierCode > 0 ? $carrierCode : null,
            ]
        ];

        $response = $this->makeRequest($endpoint, $payload);

        if ($response === false) {
            return false;
        }

        // Parsear respuesta
        return $this->parseTrackingResponse($response, $trackingNumber);
    }

    /**
     * Parsear respuesta de 17TRACK
     *
     * @param array $response Respuesta de la API
     * @param string $trackingNumber Número de guía
     * @return array|false
     */
    private function parseTrackingResponse($response, $trackingNumber)
    {
        if (!isset($response['code']) || $response['code'] !== 0) {
            log_message('error', "Tracking_service: Error en respuesta: " . json_encode($response));
            return false;
        }

        if (!isset($response['data']['accepted']) || empty($response['data']['accepted'])) {
            // Verificar si fue rechazado
            if (isset($response['data']['rejected']) && !empty($response['data']['rejected'])) {
                log_message('warning', "Tracking_service: Guía rechazada: " . json_encode($response['data']['rejected']));
            }
            return false;
        }

        $trackInfo = $response['data']['accepted'][0];

        // Obtener estado principal
        $track = $trackInfo['track'] ?? null;

        if (!$track) {
            log_message('warning', "Tracking_service: Sin información de tracking para {$trackingNumber}");
            return [
                'tracking_number' => $trackingNumber,
                'status' => 'pending',
                'status_label' => 'Pendiente',
                'location' => '',
                'last_event' => 'Sin información de rastreo disponible',
                'events' => [],
                'raw_response' => $trackInfo,
            ];
        }

        // Estado según 17TRACK
        $apiStatus = $track['e'] ?? 'Pending'; // e = estado principal
        $status = $this->mapStatus($apiStatus);
        $statusLabel = $this->status_labels[$status] ?? 'Desconocido';

        // Último evento
        $lastEvent = '';
        $location = '';
        $events = [];

        if (isset($track['z1']) && !empty($track['z1'])) {
            // z1 = lista de eventos del último carrier
            $latestEvent = $track['z1'][0] ?? null;

            if ($latestEvent) {
                $lastEvent = $latestEvent['z'] ?? ''; // z = descripción del evento
                $location = $latestEvent['c'] ?? '';  // c = ubicación

                // Formatear todos los eventos
                foreach ($track['z1'] as $event) {
                    $events[] = [
                        'date' => $event['a'] ?? '',     // a = fecha
                        'time' => $event['b'] ?? '',     // b = hora
                        'description' => $event['z'] ?? '', // z = descripción
                        'location' => $event['c'] ?? '',    // c = ubicación
                    ];
                }
            }
        }

        // Si hay ubicación en el tracking general
        if (empty($location) && isset($track['b'])) {
            $location = $track['b']; // b = ubicación actual
        }

        $result = [
            'tracking_number' => $trackingNumber,
            'status' => $status,
            'status_label' => $statusLabel,
            'location' => $location,
            'last_event' => $lastEvent,
            'events' => $events,
            'carrier_name' => $track['w1'] ?? '', // w1 = nombre del carrier
            'raw_status' => $apiStatus,
        ];

        // Guardar respuesta para debug
        $debugFile = APPPATH . 'logs/17track_response_' . $trackingNumber . '.json';
        file_put_contents($debugFile, json_encode($response, JSON_PRETTY_PRINT));
        log_message('debug', "Tracking_service: Respuesta guardada en {$debugFile}");

        return $result;
    }

    /**
     * Mapear estado de 17TRACK a estado interno
     *
     * @param string|int $apiStatus Estado de 17TRACK
     * @return string Estado interno
     */
    private function mapStatus($apiStatus)
    {
        // 17TRACK usa códigos numéricos para estados principales:
        // 0 = Not Found, 10 = In Transit, 20 = Expired, 30 = Pick Up,
        // 35 = Undelivered, 40 = Delivered, 50 = Alert

        $numericMapping = [
            0  => 'pending',           // Not Found
            10 => 'in_transit',        // In Transit
            20 => 'exception',         // Expired
            30 => 'out_for_delivery',  // Pick Up / Available
            35 => 'exception',         // Undelivered
            40 => 'delivered',         // Delivered
            50 => 'exception',         // Alert/Exception
        ];

        // Si es numérico
        if (is_numeric($apiStatus)) {
            return $numericMapping[(int)$apiStatus] ?? 'pending';
        }

        // Si es string, usar mapeo de config
        return $this->status_mapping[$apiStatus] ?? 'pending';
    }

    /**
     * Hacer request a la API de 17TRACK
     *
     * @param string $endpoint URL del endpoint
     * @param array $payload Datos a enviar
     * @return array|false
     */
    private function makeRequest($endpoint, $payload)
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                '17token: ' . $this->api_key,
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            log_message('error', "Tracking_service: CURL error: {$error}");
            return false;
        }

        if ($httpCode !== 200) {
            log_message('error', "Tracking_service: HTTP {$httpCode} - {$response}");
            return false;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', "Tracking_service: JSON decode error: " . json_last_error_msg());
            return false;
        }

        return $data;
    }

    /**
     * Eliminar una guía del seguimiento
     *
     * @param string $trackingNumber Número de guía
     * @param string $carrier Transportadora
     * @return bool
     */
    public function deleteTracking($trackingNumber, $carrier = 'interrapidisimo')
    {
        $carrierCode = $this->carrier_codes[$carrier] ?? 0;

        $endpoint = $this->api_url . '/deletetrack';

        $payload = [
            [
                'number' => $trackingNumber,
                'carrier' => $carrierCode > 0 ? $carrierCode : null,
            ]
        ];

        $response = $this->makeRequest($endpoint, $payload);

        return $response !== false && isset($response['code']) && $response['code'] === 0;
    }

    /**
     * Obtener lista de transportadoras soportadas
     *
     * @return array
     */
    public function getCarriers()
    {
        return $this->carrier_codes;
    }

    /**
     * Verificar si el API está disponible
     *
     * @return bool
     */
    public function isAvailable()
    {
        return !empty($this->api_key);
    }
}
