<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Interrapidisimo_tracker Library
 *
 * Servicio para rastrear guías de Interrapidísimo
 * Usa múltiples métodos para obtener el estado de las guías
 *
 * @author Sistema SisVent
 */
class Interrapidisimo_tracker {

    protected $CI;

    // URLs de APIs de Interrapidísimo (probadas en orden)
    private $api_urls = [
        // API principal del SiguetuEnvio
        'https://www3.interrapidisimo.com/SiguetuEnvio/api/shipments/',
        'https://www3.interrapidisimo.com/SiguetuEnvio/api/shipment/',
        'https://www3.interrapidisimo.com/SiguetuEnvio/api/tracking/',
        'https://www3.interrapidisimo.com/api/shipments/',
        'https://www3.interrapidisimo.com/api/tracking/',
        // APIs alternativas
        'https://interrapidisimo.com/api/tracking/',
        'https://www.interrapidisimo.com/api/rastreo/'
    ];

    // URL de la página de seguimiento
    private $tracking_page_url = 'https://interrapidisimo.com/sigue-tu-envio/';

    // Mapeo de estados de Interrapidísimo a estados internos
    private $status_map = [
        // Estados de entrega
        'ENTREGADO' => 'delivered',
        'ENTREGA' => 'delivered',
        'FUE ENTREGADO' => 'delivered',
        'ENVÍO FUE ENTREGADO' => 'delivered',
        'TU ENVÍO FUE ENTREGADO' => 'delivered',
        'TÚ ENVÍO FUE ENTREGADO' => 'delivered',

        // Estados en tránsito
        'ADMITIDO' => 'in_transit',
        'ADMISION' => 'in_transit',
        'EN PROCESO' => 'in_transit',
        'EN TRANSITO' => 'in_transit',
        'EN TRÁNSITO' => 'in_transit',
        'EN CAMINO' => 'in_transit',
        'TRANSITO' => 'in_transit',
        'RECOGIDO' => 'in_transit',
        'RECOLECTADO' => 'in_transit',
        'EN BODEGA' => 'in_transit',
        'RECIBIDO EN BODEGA' => 'in_transit',

        // En reparto
        'EN REPARTO' => 'out_for_delivery',
        'REPARTO' => 'out_for_delivery',
        'SALIDA A REPARTO' => 'out_for_delivery',
        'YA PUEDES RECOGER TU ENVÍO' => 'out_for_delivery',
        'YA PUEDES RECOGER' => 'out_for_delivery',
        'DISPONIBLE PARA RECOGER' => 'out_for_delivery',

        // Devolución
        'DEVOLUCION' => 'returned',
        'DEVUELTO' => 'returned',
        'DEVOLUCION AL REMITENTE' => 'returned',
        'DEVOLUCIÓN' => 'returned',

        // Novedad
        'NOVEDAD' => 'exception',
        'RETENIDO' => 'exception',
        'SIN NOVEDAD' => 'in_transit', // "Sin novedad" significa que va bien

        // Pendiente
        'PENDIENTE' => 'pending',
    ];

    // Nombres amigables para mostrar
    private $status_labels = [
        'pending' => 'Pendiente',
        'in_transit' => 'En tránsito',
        'out_for_delivery' => 'En reparto / Listo para recoger',
        'delivered' => 'Entregado',
        'returned' => 'Devuelto',
        'exception' => 'Novedad',
        'unknown' => 'Desconocido'
    ];

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Obtener estado de tracking de una guía
     *
     * @param string $guia Número de guía (10-15 dígitos)
     * @return array|false Array con info de tracking o false si falla
     */
    public function getStatus($guia)
    {
        // Validar formato de guía
        $guia = trim($guia);
        if (!preg_match('/^\d{10,15}$/', $guia)) {
            log_message('error', "Interrapidisimo_tracker: Formato de guía inválido: {$guia}");
            return false;
        }

        try {
            // Método 1: Intentar POST a la API de búsqueda
            $result = $this->querySearchAPI($guia);
            if ($result && $result['status'] !== 'unknown') {
                log_message('info', "Interrapidisimo_tracker: Guía {$guia} - Estado obtenido via Search API: {$result['status']}");
                return $result;
            }

            // Método 2: Intentar APIs GET conocidas
            foreach ($this->api_urls as $apiUrl) {
                $result = $this->queryAPI($apiUrl, $guia);
                if ($result && $result['status'] !== 'unknown') {
                    log_message('info', "Interrapidisimo_tracker: Guía {$guia} - Estado obtenido via API: {$result['status']}");
                    return $result;
                }
            }

            // Método 3: Intentar scraping de la página web (menos confiable con SPAs)
            $result = $this->scrapeTrackingPage($guia);
            if ($result && $result['status'] !== 'unknown') {
                log_message('info', "Interrapidisimo_tracker: Guía {$guia} - Estado obtenido via scraping: {$result['status']}");
                return $result;
            }

            log_message('error', "Interrapidisimo_tracker: No se pudo obtener estado para guía {$guia}");
            return false;

        } catch (Exception $e) {
            log_message('error', "Interrapidisimo_tracker: Error consultando guía {$guia}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Scraping de la página de seguimiento
     * Este es el método principal ya que es más confiable
     *
     * @param string $guia Número de guía
     * @return array|false
     */
    private function scrapeTrackingPage($guia)
    {
        $url = $this->tracking_page_url . '?guia=' . urlencode($guia);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false, // Deshabilitar para evitar problemas de certificado
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
                'Cache-Control: no-cache',
                'Pragma: no-cache'
            ],
            CURLOPT_ENCODING => 'gzip, deflate', // Aceptar compresión
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || empty($html)) {
            log_message('debug', "Interrapidisimo scrape failed: HTTP {$httpCode}, Error: {$error}");
            return false;
        }

        // Guardar HTML para debug (habilitar temporalmente para diagnosticar)
        $debugFile = APPPATH . 'logs/interrapidisimo_debug_' . $guia . '.html';
        file_put_contents($debugFile, $html);
        log_message('info', "Interrapidisimo: HTML guardado en {$debugFile} - Tamaño: " . strlen($html) . " bytes");

        return $this->parseHTML($html, $guia);
    }

    /**
     * Parsear HTML de la página de seguimiento
     *
     * @param string $html Contenido HTML
     * @param string $guia Número de guía
     * @return array
     */
    private function parseHTML($html, $guia)
    {
        $result = [
            'guia' => $guia,
            'status' => 'unknown',
            'status_raw' => '',
            'status_label' => 'Desconocido',
            'location' => '',
            'destination' => '',
            'origin' => '',
            'last_update' => date('Y-m-d H:i:s'),
            'events' => [],
            'source' => 'scrape'
        ];

        // ═══════════════════════════════════════════════════════════════
        // MÉTODO 1: Buscar datos en JSON embebido (React/Next.js)
        // ═══════════════════════════════════════════════════════════════

        // Buscar __NEXT_DATA__ (Next.js)
        if (preg_match('/<script[^>]*id="__NEXT_DATA__"[^>]*>(.+?)<\/script>/s', $html, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if ($jsonData && isset($jsonData['props']['pageProps'])) {
                $pageProps = $jsonData['props']['pageProps'];
                if (isset($pageProps['tracking']) || isset($pageProps['shipment'])) {
                    $tracking = $pageProps['tracking'] ?? $pageProps['shipment'] ?? [];
                    return $this->parseJSONTracking($tracking, $guia);
                }
            }
        }

        // Buscar cualquier JSON con datos de tracking
        if (preg_match_all('/\{[^{}]*"(status|estado|tracking)"[^{}]*\}/i', $html, $jsonMatches)) {
            foreach ($jsonMatches[0] as $jsonStr) {
                $data = json_decode($jsonStr, true);
                if ($data) {
                    $parsed = $this->parseJSONTracking($data, $guia);
                    if ($parsed['status'] !== 'unknown') {
                        return $parsed;
                    }
                }
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // MÉTODO 2: Buscar textos específicos en el HTML
        // ═══════════════════════════════════════════════════════════════

        // Patrones de estado conocidos de Interrapidísimo
        $statusPatterns = [
            // Estado de entrega
            '/(?:estado\s*actual|tu\s*env[ií]o)[^<]*?(?:fue\s*)?entregado/i' => 'delivered',
            '/entregado\s*(?:exitosamente)?/i' => 'delivered',

            // En reparto / listo para recoger
            '/ya\s*puedes\s*recoger\s*tu\s*env[ií]o/i' => 'out_for_delivery',
            '/disponible\s*para\s*recoger/i' => 'out_for_delivery',
            '/en\s*reparto/i' => 'out_for_delivery',
            '/salida\s*a\s*reparto/i' => 'out_for_delivery',

            // En tránsito
            '/en\s*tr[aá]nsito/i' => 'in_transit',
            '/en\s*proceso/i' => 'in_transit',
            '/en\s*camino/i' => 'in_transit',
            '/admitido/i' => 'in_transit',
            '/recibido\s*en\s*bodega/i' => 'in_transit',
            '/sin\s*novedad/i' => 'in_transit', // "Sin novedad" = todo va bien

            // Devuelto
            '/devuelto/i' => 'returned',
            '/devoluci[oó]n/i' => 'returned',

            // Novedad
            '/novedad/i' => 'exception',
        ];

        foreach ($statusPatterns as $pattern => $status) {
            if (preg_match($pattern, $html)) {
                $result['status'] = $status;
                // Extraer el texto que coincidió para el status_raw
                if (preg_match($pattern, $html, $statusMatch)) {
                    $result['status_raw'] = trim(strip_tags($statusMatch[0]));
                }
                break;
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // MÉTODO 3: Extraer origen y destino
        // ═══════════════════════════════════════════════════════════════

        // Buscar origen
        if (preg_match('/origen[^<]*<[^>]*>([^<]+)/i', $html, $match)) {
            $result['origin'] = trim(strip_tags($match[1]));
        }

        // Buscar destino
        if (preg_match('/destino[^<]*<[^>]*>([^<]+)/i', $html, $match)) {
            $result['destination'] = trim(strip_tags($match[1]));
            $result['location'] = $result['destination'];
        }

        // Buscar ciudad en el HTML
        if (empty($result['location'])) {
            if (preg_match('/ciudad[:\s]*([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s\/]+)/i', $html, $match)) {
                $result['location'] = trim($match[1]);
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // MÉTODO 4: Buscar última actualización
        // ═══════════════════════════════════════════════════════════════

        // Buscar fecha de actualización
        if (preg_match('/(?:[úu]ltima\s*)?actualizaci[oó]n[:\s]*(\d{1,2}\/\d{1,2}\/\d{4})/i', $html, $match)) {
            $result['last_update'] = $this->parseDate($match[1]);
        } elseif (preg_match('/fecha[:\s]*(\d{1,2}\/\d{1,2}\/\d{4})/i', $html, $match)) {
            $result['last_update'] = $this->parseDate($match[1]);
        }

        // Asignar etiqueta de estado
        $result['status_label'] = $this->status_labels[$result['status']] ?? 'Desconocido';

        // Log para debug
        log_message('debug', "Interrapidisimo parseHTML - Guía: {$guia}, Status: {$result['status']}, Location: {$result['location']}");

        return $result;
    }

    /**
     * Parsear datos de tracking desde JSON
     *
     * @param array $data Datos JSON
     * @param string $guia Número de guía
     * @return array
     */
    private function parseJSONTracking($data, $guia)
    {
        $result = [
            'guia' => $guia,
            'status' => 'unknown',
            'status_raw' => '',
            'status_label' => 'Desconocido',
            'location' => '',
            'last_update' => date('Y-m-d H:i:s'),
            'events' => [],
            'source' => 'json'
        ];

        // Buscar estado en diferentes campos posibles
        $statusFields = ['status', 'estado', 'state', 'tracking_status', 'estadoActual'];
        foreach ($statusFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $result['status_raw'] = $data[$field];
                $result['status'] = $this->mapStatus($data[$field]);
                break;
            }
        }

        // Buscar ubicación
        $locationFields = ['location', 'ubicacion', 'city', 'ciudad', 'destino'];
        foreach ($locationFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $result['location'] = $data[$field];
                break;
            }
        }

        // Buscar eventos
        $eventsFields = ['events', 'eventos', 'history', 'historial', 'tracking'];
        foreach ($eventsFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $result['events'] = array_map(function($event) {
                    return [
                        'date' => $event['date'] ?? $event['fecha'] ?? '',
                        'status' => $event['status'] ?? $event['estado'] ?? '',
                        'location' => $event['location'] ?? $event['ciudad'] ?? '',
                        'description' => $event['description'] ?? $event['descripcion'] ?? ''
                    ];
                }, $data[$field]);
                break;
            }
        }

        $result['status_label'] = $this->status_labels[$result['status']] ?? 'Desconocido';

        return $result;
    }

    /**
     * Consultar API de búsqueda de Interrapidísimo via POST
     * Este método simula lo que hace el frontend cuando buscas una guía
     *
     * @param string $guia Número de guía
     * @return array|false
     */
    private function querySearchAPI($guia)
    {
        // Primero intentar obtener la URL codificada del endpoint de búsqueda
        $encodedUrl = $this->getEncodedTrackingUrl($guia);
        if ($encodedUrl) {
            // Ahora hacer scraping de la página con la URL codificada
            $result = $this->scrapeEncodedPage($encodedUrl, $guia);
            if ($result && $result['status'] !== 'unknown') {
                return $result;
            }
        }

        // Endpoints de API backend de Angular/SPA
        $searchEndpoints = [
            // APIs probables del backend
            [
                'url' => "https://www3.interrapidisimo.com/SiguetuEnvio/api/envio/{$guia}",
                'method' => 'GET',
                'data' => null
            ],
            [
                'url' => "https://www3.interrapidisimo.com/SiguetuEnvio/api/guia/{$guia}",
                'method' => 'GET',
                'data' => null
            ],
            [
                'url' => "https://www3.interrapidisimo.com/SiguetuEnvio/api/consulta/{$guia}",
                'method' => 'GET',
                'data' => null
            ],
            [
                'url' => "https://www3.interrapidisimo.com/SiguetuEnvio/api/tracking/{$guia}",
                'method' => 'GET',
                'data' => null
            ],
            // WordPress/Backend alternativo
            [
                'url' => "https://www.interrapidisimo.com/wp-json/inter/v1/tracking/{$guia}",
                'method' => 'GET',
                'data' => null
            ],
            [
                'url' => "https://api.interrapidisimo.com/v1/tracking/{$guia}",
                'method' => 'GET',
                'data' => null
            ],
            // POST endpoints
            [
                'url' => 'https://www3.interrapidisimo.com/SiguetuEnvio/api/consultar',
                'method' => 'POST',
                'data' => json_encode(['guia' => $guia, 'numero' => $guia])
            ],
        ];

        foreach ($searchEndpoints as $endpoint) {
            $ch = curl_init();

            $curlOpts = [
                CURLOPT_URL => $endpoint['url'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json, text/plain, */*',
                    'Content-Type: application/json',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Origin: https://www3.interrapidisimo.com',
                    'Referer: https://www3.interrapidisimo.com/SiguetuEnvio/'
                ]
            ];

            if ($endpoint['method'] === 'POST' && $endpoint['data']) {
                $curlOpts[CURLOPT_POST] = true;
                $curlOpts[CURLOPT_POSTFIELDS] = $endpoint['data'];
            }

            curl_setopt_array($ch, $curlOpts);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            log_message('debug', "Interrapidisimo Search API: {$endpoint['url']} - HTTP {$httpCode}");

            if ($httpCode === 200 && !empty($response)) {
                // Guardar respuesta para debug
                $debugFile = APPPATH . 'logs/interrapidisimo_api_debug_' . $guia . '.json';
                file_put_contents($debugFile, $response);
                log_message('info', "Interrapidisimo API Response guardado en {$debugFile}");

                $data = json_decode($response, true);
                if ($data) {
                    $result = $this->parseJSONTracking($data, $guia);
                    if ($result['status'] !== 'unknown') {
                        return $result;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Obtener la URL codificada de tracking desde Interrapidísimo
     * Intenta simular lo que hace el frontend cuando buscas una guía
     *
     * @param string $guia Número de guía
     * @return string|false URL codificada o false si falla
     */
    private function getEncodedTrackingUrl($guia)
    {
        // Método 1: Intentar hacer POST al formulario de búsqueda y capturar redirect
        $searchUrl = 'https://www3.interrapidisimo.com/SiguetuEnvio/';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $searchUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false, // No seguir redirects automáticamente
            CURLOPT_HEADER => true, // Incluir headers en la respuesta
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['guia' => $guia, 'numero' => $guia]),
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Origin: https://www3.interrapidisimo.com',
                'Referer: https://www3.interrapidisimo.com/SiguetuEnvio/'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Buscar redirect en headers (Location header)
        if (preg_match('/Location:\s*(.+)/i', $response, $matches)) {
            $redirectUrl = trim($matches[1]);
            if (strpos($redirectUrl, '/SiguetuEnvio/shipment/') !== false) {
                log_message('info', "Interrapidisimo: URL codificada obtenida via redirect: {$redirectUrl}");
                // Si es URL relativa, agregar el dominio
                if (strpos($redirectUrl, 'http') !== 0) {
                    $redirectUrl = 'https://www3.interrapidisimo.com' . $redirectUrl;
                }
                return $redirectUrl;
            }
        }

        // Método 2: Buscar en el HTML si hay algún enlace con la URL codificada
        if (preg_match('/\/SiguetuEnvio\/shipment\/([A-Za-z0-9_\-]+)/', $response, $matches)) {
            $encodedUrl = 'https://www3.interrapidisimo.com/SiguetuEnvio/shipment/' . $matches[1];
            log_message('info', "Interrapidisimo: URL codificada encontrada en HTML: {$encodedUrl}");
            return $encodedUrl;
        }

        // Método 3: Intentar endpoint de API que podría devolver la URL
        $apiEndpoints = [
            'https://www3.interrapidisimo.com/SiguetuEnvio/api/encode/' . $guia,
            'https://www3.interrapidisimo.com/SiguetuEnvio/api/search?guia=' . $guia,
        ];

        foreach ($apiEndpoints as $apiUrl) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);

            $apiResponse = curl_exec($ch);
            $apiCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($apiCode === 200 && !empty($apiResponse)) {
                $data = json_decode($apiResponse, true);
                if ($data) {
                    // Buscar URL en la respuesta
                    $urlFields = ['url', 'redirect', 'trackingUrl', 'shipmentUrl'];
                    foreach ($urlFields as $field) {
                        if (!empty($data[$field])) {
                            log_message('info', "Interrapidisimo: URL obtenida de API: {$data[$field]}");
                            return $data[$field];
                        }
                    }
                }
            }
        }

        log_message('debug', "Interrapidisimo: No se pudo obtener URL codificada para guía {$guia}");
        return false;
    }

    /**
     * Hacer scraping de la página con URL codificada
     *
     * @param string $url URL codificada completa
     * @param string $guia Número de guía original
     * @return array|false
     */
    private function scrapeEncodedPage($url, $guia)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: es-ES,es;q=0.9'
            ],
            CURLOPT_ENCODING => 'gzip, deflate'
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($html)) {
            log_message('debug', "Interrapidisimo: Fallo al cargar página codificada - HTTP {$httpCode}");
            return false;
        }

        // Guardar para debug
        $debugFile = APPPATH . "logs/interrapidisimo_encoded_{$guia}.html";
        file_put_contents($debugFile, $html);
        log_message('info', "Interrapidisimo: HTML de página codificada guardado - Tamaño: " . strlen($html));

        // Parsear el HTML
        return $this->parseHTML($html, $guia);
    }

    /**
     * Consultar API de Interrapidísimo
     *
     * @param string $baseUrl URL base de la API
     * @param string $guia Número de guía
     * @return array|false
     */
    private function queryAPI($baseUrl, $guia)
    {
        $url = rtrim($baseUrl, '/') . '/' . urlencode($guia);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            return false;
        }

        $data = json_decode($response, true);
        if (!$data) {
            return false;
        }

        return $this->parseJSONTracking($data, $guia);
    }

    /**
     * Mapear estado de Interrapidísimo a estado interno
     *
     * @param string $rawStatus Estado original
     * @return string Estado interno
     */
    private function mapStatus($rawStatus)
    {
        if (empty($rawStatus)) {
            return 'unknown';
        }

        $rawStatus = mb_strtoupper(trim($rawStatus));

        // Buscar coincidencia exacta
        if (isset($this->status_map[$rawStatus])) {
            return $this->status_map[$rawStatus];
        }

        // Buscar coincidencia parcial
        foreach ($this->status_map as $pattern => $status) {
            if (mb_strpos($rawStatus, $pattern) !== false) {
                return $status;
            }
        }

        // Buscar palabras clave
        if (mb_strpos($rawStatus, 'ENTREG') !== false) return 'delivered';
        if (mb_strpos($rawStatus, 'TRANSIT') !== false) return 'in_transit';
        if (mb_strpos($rawStatus, 'REPART') !== false) return 'out_for_delivery';
        if (mb_strpos($rawStatus, 'RECOG') !== false) return 'out_for_delivery';
        if (mb_strpos($rawStatus, 'DEVOL') !== false) return 'returned';
        if (mb_strpos($rawStatus, 'NOVED') !== false && mb_strpos($rawStatus, 'SIN') === false) return 'exception';

        return 'unknown';
    }

    /**
     * Parsear fecha en diferentes formatos
     *
     * @param string $dateStr Fecha como string
     * @return string Fecha en formato Y-m-d H:i:s
     */
    private function parseDate($dateStr)
    {
        $dateStr = trim($dateStr);

        // Formato dd/mm/yyyy
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]} 00:00:00";
        }

        // Formato yyyy-mm-dd
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateStr)) {
            return date('Y-m-d H:i:s', strtotime($dateStr));
        }

        return date('Y-m-d H:i:s');
    }

    /**
     * Verificar si un estado indica entrega completada
     *
     * @param string $status Estado interno
     * @return bool
     */
    public function isDelivered($status)
    {
        return $status === 'delivered';
    }

    /**
     * Verificar si un estado es terminal (no necesita más actualizaciones)
     *
     * @param string $status Estado interno
     * @return bool
     */
    public function isTerminal($status)
    {
        return in_array($status, ['delivered', 'returned']);
    }

    /**
     * Obtener etiqueta de estado para mostrar
     *
     * @param string $status Estado interno
     * @return string Etiqueta amigable
     */
    public function getStatusLabel($status)
    {
        return $this->status_labels[$status] ?? 'Desconocido';
    }

    /**
     * Obtener color CSS para un estado
     *
     * @param string $status Estado interno
     * @return string Clase CSS de color
     */
    public function getStatusColor($status)
    {
        $colors = [
            'pending' => 'gray',
            'in_transit' => 'blue',
            'out_for_delivery' => 'yellow',
            'delivered' => 'green',
            'returned' => 'red',
            'exception' => 'orange',
            'unknown' => 'gray'
        ];

        return $colors[$status] ?? 'gray';
    }

    /**
     * Obtener URL pública de seguimiento
     *
     * @param string $guia Número de guía
     * @return string URL de seguimiento
     */
    public function getTrackingUrl($guia)
    {
        return $this->tracking_page_url . '?guia=' . urlencode($guia);
    }
}
