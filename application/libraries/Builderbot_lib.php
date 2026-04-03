<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Builderbot_lib — Integración con BuilderBot Cloud API
 *
 * Envío de mensajes WhatsApp, validación de webhooks,
 * y replicación de ventas a Google Sheets.
 */
class Builderbot_lib {

    private $CI;
    private $baseUrl;
    private $webhookSecret;

    public function __construct()
    {
        $this->CI =& get_instance();
        $secretsFile = APPPATH . 'config/secrets.php';
        if (file_exists($secretsFile)) {
            include($secretsFile);
            $secrets = isset($config['builderbot']) ? $config['builderbot'] : array();
        } else {
            $secrets = array();
        }
        $this->baseUrl = isset($secrets['base_url']) ? $secrets['base_url'] : 'https://app.builderbot.cloud';
        $this->webhookSecret = isset($secrets['webhook_secret']) ? $secrets['webhook_secret'] : '';
    }

    /**
     * Enviar mensaje a un número de WhatsApp
     * @param object $botConfig  Row from builderbot_configs
     * @param string $number     Teléfono (con código país)
     * @param string $content    Texto del mensaje
     * @param string|null $mediaUrl URL de archivo multimedia (opcional)
     * @return array ['success' => bool, 'response' => mixed, 'http_code' => int]
     */
    public function sendMessage($botConfig, $number, $content, $mediaUrl = null)
    {
        $url = rtrim($botConfig->base_url ?: $this->baseUrl, '/')
             . '/api/v2/' . $botConfig->bot_id . '/messages';

        $body = array(
            'messages' => array(
                'content' => $content,
            ),
            'number' => $number,
            'checkIfExists' => true,
        );

        if ($mediaUrl) {
            $body['messages']['mediaUrl'] = $mediaUrl;
        }

        $result = $this->_post($url, $body, $botConfig->api_key);

        return array(
            'success'   => ($result['http_code'] >= 200 && $result['http_code'] < 300),
            'response'  => $result['body'],
            'http_code' => $result['http_code'],
        );
    }

    /**
     * Obtener instrucciones actuales del asistente IA
     */
    public function getAssistantInstructions($botConfig)
    {
        if (empty($botConfig->answer_id)) return null;

        $url = rtrim($botConfig->base_url ?: $this->baseUrl, '/')
             . '/api/v2/' . $botConfig->bot_id . '/answer/' . $botConfig->answer_id . '/plugin/assistant';

        $result = $this->_get($url, $botConfig->api_key);

        if ($result['http_code'] === 200 && isset($result['body']->data->instructions)) {
            return $result['body']->data->instructions;
        }
        return null;
    }

    /**
     * Actualizar instrucciones del asistente IA
     */
    public function updateAssistantInstructions($botConfig, $instructions)
    {
        if (empty($botConfig->answer_id)) {
            return array('success' => false, 'error' => 'No hay answer_id configurado');
        }

        $url = rtrim($botConfig->base_url ?: $this->baseUrl, '/')
             . '/api/v2/' . $botConfig->bot_id . '/answer/' . $botConfig->answer_id . '/plugin/assistant';

        $result = $this->_post($url, array('instructions' => $instructions), $botConfig->api_key);

        return array(
            'success'   => ($result['http_code'] >= 200 && $result['http_code'] < 300),
            'response'  => $result['body'],
            'http_code' => $result['http_code'],
        );
    }

    /**
     * Validar webhook entrante
     * @param string $providedSecret Header X-Webhook-Secret del request
     * @param object|null $botConfig Config del bot (usa su webhook_secret si existe)
     * @return bool
     */
    public function validateWebhook($providedSecret, $botConfig = null)
    {
        if (empty($providedSecret)) return false;

        // Primero intentar con el secret específico del bot
        if ($botConfig && !empty($botConfig->webhook_secret)) {
            return hash_equals($botConfig->webhook_secret, $providedSecret);
        }

        // Fallback al secret global
        return hash_equals($this->webhookSecret, $providedSecret);
    }

    /**
     * Transformar payload de BuilderBot al formato de bot_sales_queue
     * @param array $bbPayload Payload raw de BuilderBot
     * @param object $botConfig Config del bot
     * @return array Formato compatible con BotImport::process_webhook_sale()
     */
    public function transformSalePayload($bbPayload, $botConfig)
    {
        // El payload de BuilderBot puede variar según la configuración del flujo.
        // Este mapeo cubre la estructura más común; se ajusta según el flujo real.
        $data = is_array($bbPayload) ? $bbPayload : (array) $bbPayload;

        $transformed = array(
            'nombre'    => isset($data['nombre']) ? $data['nombre'] : (isset($data['name']) ? $data['name'] : ''),
            'documento' => isset($data['documento']) ? $data['documento'] : (isset($data['doc']) ? $data['doc'] : ''),
            'celular'   => isset($data['celular']) ? $data['celular'] : (isset($data['phone']) ? $data['phone'] : ''),
            'email'     => isset($data['email']) ? $data['email'] : '',
            'direccion' => isset($data['direccion']) ? $data['direccion'] : (isset($data['address']) ? $data['address'] : ''),
            'tipoenvio' => isset($data['tipoenvio']) ? $data['tipoenvio'] : 'envio gratis',
            'vendedor'  => $botConfig->default_vendor_id,
            'productos' => array(),
        );

        // Mapear productos
        $productos = isset($data['productos']) ? $data['productos'] : (isset($data['products']) ? $data['products'] : array());
        if (is_array($productos)) {
            foreach ($productos as $p) {
                $p = (array) $p;
                $transformed['productos'][] = array(
                    'codigo'   => isset($p['codigo']) ? $p['codigo'] : (isset($p['code']) ? $p['code'] : ''),
                    'cantidad' => isset($p['cantidad']) ? (int)$p['cantidad'] : (isset($p['qty']) ? (int)$p['qty'] : 1),
                    'precio'   => isset($p['precio']) ? (float)$p['precio'] : (isset($p['price']) ? (float)$p['price'] : 0),
                );
            }
        }

        return $transformed;
    }

    /**
     * Escribir venta en Google Sheet vía Apps Script
     * @param object $botConfig Config del bot (tiene script_url, sheet_id)
     * @param array $saleData Datos de la venta
     * @param int $budgetId ID del presupuesto creado
     * @return bool
     */
    public function writeToGoogleSheet($botConfig, $saleData, $budgetId)
    {
        if (empty($botConfig->script_url)) {
            log_message('debug', 'BuilderBot: No script_url configurado para bot ' . $botConfig->name);
            return false;
        }

        $productos_texto = '';
        if (isset($saleData['productos']) && is_array($saleData['productos'])) {
            $items = array();
            foreach ($saleData['productos'] as $p) {
                $items[] = $p['codigo'] . ' x' . $p['cantidad'];
            }
            $productos_texto = implode(', ', $items);
        }

        $total = 0;
        if (isset($saleData['productos'])) {
            foreach ($saleData['productos'] as $p) {
                $total += $p['cantidad'] * $p['precio'];
            }
        }

        $body = json_encode(array(
            'action' => 'appendRow',
            'sheetId' => $botConfig->sheet_id,
            'gid' => $botConfig->sheet_gid,
            'row' => array(
                date('Y-m-d H:i:s'),
                isset($saleData['nombre']) ? $saleData['nombre'] : '',
                isset($saleData['documento']) ? $saleData['documento'] : '',
                isset($saleData['celular']) ? $saleData['celular'] : '',
                isset($saleData['direccion']) ? $saleData['direccion'] : '',
                $productos_texto,
                $total,
                $budgetId,
                'procesado',
            ),
        ));

        $ch = curl_init($botConfig->script_url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            log_message('error', "BuilderBot writeToGoogleSheet HTTP {$httpCode}: {$response}");
        }

        return $httpCode === 200;
    }

    /**
     * GET request a BuilderBot API
     */
    private function _get($url, $apiKey)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'x-api-builderbot: ' . $apiKey,
            ),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ));

        $response = curl_exec($ch);

        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return array('http_code' => 0, 'body' => $err);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array(
            'http_code' => $httpCode,
            'body'      => json_decode($response),
        );
    }

    /**
     * POST request a BuilderBot API
     */
    private function _post($url, $body, $apiKey)
    {
        $ch = curl_init($url);
        $json = json_encode($body);

        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'x-api-builderbot: ' . $apiKey,
            ),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ));

        $response = curl_exec($ch);

        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            log_message('error', "BuilderBot POST cURL error: {$err} - {$url}");
            return array('http_code' => 0, 'body' => $err);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response);

        return array(
            'http_code' => $httpCode,
            'body'      => $decoded !== null ? $decoded : $response,
        );
    }
}
