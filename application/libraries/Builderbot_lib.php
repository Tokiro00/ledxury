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
    private $managerApi;

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
        $this->managerApi = isset($secrets['manager_api']) ? $secrets['manager_api'] : '';
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
            'checkIfExists' => false,
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
     * Escribir guía en Sheet col M, enviar WhatsApp y marcar OK en col Q.
     *
     * @param string $sheetId    ID del Google Sheet
     * @param string $documento  Documento/cédula del cliente
     * @param string $guiaNum    Número de guía
     * @param object|null $botConfig Config del bot para enviar WhatsApp
     * @param array $extraData   Datos extra: ciudad_destino, valor_cobrar, es_contrapago
     * @param string $sheetName  Nombre de la hoja
     * @return array
     */
    public function writeGuideToSheet($sheetId, $documento, $guiaNum, $botConfig = null, $extraData = array(), $sheetName = 'Registros')
    {
        try {
            $credPath = APPPATH . 'config/google_sheets_credentials.json';
            if (!file_exists($credPath)) {
                return array('success' => false, 'row' => null, 'message' => 'Credenciales Google no encontradas');
            }

            $client = new Google\Client();
            $client->setAuthConfig($credPath);
            $client->addScope(Google\Service\Sheets::SPREADSHEETS);
            $service = new Google\Service\Sheets($client);

            // Leer columnas B a Q (nombre, doc, ..., celular, ..., guía, ..., mensajeGuia)
            $range = $sheetName . '!B2:Q1000';
            $response = $service->spreadsheets_values->get($sheetId, $range);
            $rows = $response->getValues();

            if (empty($rows)) {
                return array('success' => false, 'row' => null, 'message' => 'Sheet vacío');
            }

            $documento = trim($documento);
            $matchRow = null;
            $matchData = null;

            // B=0, C=1, D=2, E=3, F=4, G=5, H=6, I=7(celular), ..., M=11(guía), ..., Q=15(mensajeGuia)
            foreach ($rows as $i => $row) {
                $docSheet = isset($row[1]) ? trim($row[1]) : '';
                $docClean = preg_replace('/^(cc|CC|Ce|ce|Cc)\s*/i', '', $docSheet);
                $docClean = trim($docClean);

                $guiaExisting = isset($row[11]) ? trim($row[11]) : '';

                if ($docClean === $documento && empty($guiaExisting)) {
                    $matchRow = $i + 2;
                    $matchData = array(
                        'nombre'  => isset($row[0]) ? trim($row[0]) : '',
                        'celular' => isset($row[7]) ? trim($row[7]) : '',
                        'msgGuia' => isset($row[15]) ? strtoupper(trim($row[15])) : '',
                    );
                    break;
                }
            }

            if (!$matchRow) {
                return array('success' => false, 'row' => null, 'message' => 'Cliente doc ' . $documento . ' no encontrado en Sheet o ya tiene guía');
            }

            // 1. Escribir guía en columna M
            $cellM = $sheetName . '!M' . $matchRow;
            $bodyM = new Google\Service\Sheets\ValueRange(['values' => [[$guiaNum]]]);
            $service->spreadsheets_values->update($sheetId, $cellM, $bodyM, ['valueInputOption' => 'RAW']);

            log_message('info', "BuilderBot: Guía {$guiaNum} escrita en Sheet row {$matchRow} para doc {$documento}");

            // 2. Enviar WhatsApp si hay bot, celular, y no se ha enviado ya
            $messageSent = false;
            $messageError = '';

            if ($botConfig && !empty($matchData['celular']) && $matchData['msgGuia'] !== 'OK') {
                $celular = $matchData['celular'];
                // Formatear número Colombia
                $celular = preg_replace('/\D/', '', $celular);
                if (substr($celular, 0, 2) !== '57' && substr($celular, 0, 1) === '3') {
                    $celular = '57' . $celular;
                }

                $ciudad = isset($extraData['ciudad_destino']) ? $extraData['ciudad_destino'] : '';
                $esContrapago = !empty($extraData['es_contrapago']);
                $valorCobrar = isset($extraData['valor_cobrar']) ? $extraData['valor_cobrar'] : '';

                $mensaje = "Hola " . $matchData['nombre'] . "!\n\n"
                    . "Tu pedido de *Ledxury* ya fue enviado por *Interrapidisimo*.\n\n"
                    . "Numero de guia: " . $guiaNum . "\n";

                if ($esContrapago && $valorCobrar) {
                    $mensaje .= "Valor a pagar: $" . number_format((float)$valorCobrar, 0, ',', '.') . "\n";
                }
                if ($ciudad) {
                    $mensaje .= "Ciudad destino: " . $ciudad . "\n";
                }

                $mensaje .= "\nPuedes rastrear tu envio en: https://www.interrapidisimo.com/rastreo/\n\n"
                    . "Gracias por tu compra!";

                $sendResult = $this->sendMessage($botConfig, $celular, $mensaje);

                if ($sendResult['success']) {
                    $messageSent = true;
                    // 3. Marcar OK en columna Q
                    $cellQ = $sheetName . '!Q' . $matchRow;
                    $bodyQ = new Google\Service\Sheets\ValueRange(['values' => [['OK']]]);
                    $service->spreadsheets_values->update($sheetId, $cellQ, $bodyQ, ['valueInputOption' => 'RAW']);

                    log_message('info', "BuilderBot: WhatsApp enviado a {$celular} con guía {$guiaNum}");
                } else {
                    $messageError = is_string($sendResult['response']) ? $sendResult['response'] : 'HTTP ' . $sendResult['http_code'];
                    log_message('error', "BuilderBot: Error enviando WhatsApp guía {$guiaNum}: {$messageError}");
                }
            }

            return array(
                'success' => true,
                'row' => $matchRow,
                'message_sent' => $messageSent,
                'message_error' => $messageError,
                'message' => 'Guía escrita en fila ' . $matchRow . ($messageSent ? ' + WhatsApp enviado' : ''),
            );

        } catch (Exception $e) {
            log_message('error', 'BuilderBot writeGuideToSheet error: ' . $e->getMessage());
            return array('success' => false, 'row' => null, 'message' => $e->getMessage());
        }
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

    /**
     * GET que acepta respuestas binarias (e.g. PNG). Si el content-type es imagen,
     * devuelve el body base64-encoded para que la vista lo consuma como data URI.
     */
    private function _getBinary($url, $apiKey)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('x-api-builderbot: ' . $apiKey),
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
        $ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($ctype && stripos($ctype, 'image/') === 0) {
            $mime = trim(explode(';', $ctype)[0]);
            return array(
                'http_code' => $httpCode,
                'body'      => array(
                    'qr'        => 'data:' . $mime . ';base64,' . base64_encode($response),
                    'mime'      => $mime,
                ),
            );
        }

        $decoded = json_decode($response);
        return array(
            'http_code' => $httpCode,
            'body'      => $decoded !== null ? $decoded : $response,
        );
    }

    private function _delete($url, $apiKey)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('x-api-builderbot: ' . $apiKey),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array('http_code' => $httpCode, 'body' => json_decode($response));
    }

    // =========================================================
    // BOT CONTROL (Hub Central)
    // Deploys (status/start/stop/qr) usan MANAGER_API (bbc-...) en /api/v1/manager/deploys
    // Reboot usa la CLIENT API (bb-...) del bot en /api/v2/{bot_id}/reboot
    // =========================================================

    public function getBotStatus($botConfig)
    {
        $url = rtrim($botConfig->base_url ?: $this->baseUrl, '/')
             . '/api/v1/manager/deploys/' . $botConfig->bot_id;
        return $this->_get($url, $this->managerApi);
    }

    public function startBot($botConfig)
    {
        $url = rtrim($botConfig->base_url ?: $this->baseUrl, '/')
             . '/api/v1/manager/deploys';
        return $this->_post($url, array('projectId' => $botConfig->bot_id), $this->managerApi);
    }

    public function stopBot($botConfig)
    {
        $url = rtrim($botConfig->base_url ?: $this->baseUrl, '/')
             . '/api/v1/manager/deploys/' . $botConfig->bot_id;
        return $this->_delete($url, $this->managerApi);
    }

    /**
     * QR devuelve PNG binario (no JSON). Lo envolvemos como base64 para
     * que la vista pueda renderizarlo con <img src="data:image/png;base64,...">.
     */
    public function getBotQR($botConfig)
    {
        $url = rtrim($botConfig->base_url ?: $this->baseUrl, '/')
             . '/api/v1/manager/deploys/' . $botConfig->bot_id . '/qr';
        return $this->_getBinary($url, $this->managerApi);
    }

    public function restartBot($botConfig)
    {
        $url = rtrim($botConfig->base_url ?: $this->baseUrl, '/')
             . '/api/v2/' . $botConfig->bot_id . '/reboot';
        return $this->_get($url, $botConfig->api_key);
    }

    public function getBlacklist($botConfig)
    {
        $url = rtrim($botConfig->base_url ?: $this->baseUrl, '/') . '/api/v2/' . $botConfig->bot_id . '/blacklist';
        return $this->_get($url, $botConfig->api_key);
    }

    public function addToBlacklist($botConfig, $numbers)
    {
        return $this->_blacklistBatch($botConfig, $numbers, 'add');
    }

    public function removeFromBlacklist($botConfig, $numbers)
    {
        return $this->_blacklistBatch($botConfig, $numbers, 'remove');
    }

    /**
     * La API acepta un unico 'number' (string) por request. Procesamos multiples
     * numeros separados por coma/espacio/salto iterando llamadas.
     */
    private function _blacklistBatch($botConfig, $numbers, $intent)
    {
        $url = rtrim($botConfig->base_url ?: $this->baseUrl, '/') . '/api/v2/' . $botConfig->bot_id . '/blacklist';
        $list = $this->_splitNumbers($numbers);
        if (empty($list)) {
            return array('http_code' => 400, 'body' => array('error' => 'No numbers'));
        }

        $results = array();
        $lastCode = 200;
        foreach ($list as $n) {
            $res = $this->_post($url, array('number' => $n, 'intent' => $intent), $botConfig->api_key);
            $results[] = array('number' => $n, 'http_code' => $res['http_code'], 'body' => $res['body']);
            if ($res['http_code'] < 200 || $res['http_code'] >= 300) $lastCode = $res['http_code'];
        }
        return array('http_code' => $lastCode, 'body' => array('results' => $results));
    }

    private function _splitNumbers($numbers)
    {
        if (is_array($numbers)) return array_values(array_filter(array_map('trim', $numbers)));
        $parts = preg_split('/[\s,;]+/', (string)$numbers);
        return array_values(array_filter(array_map('trim', $parts)));
    }

    public function getAssistantFiles($botConfig)
    {
        if (empty($botConfig->answer_id)) return array('http_code' => 400, 'body' => 'No answer_id');
        $url = rtrim($botConfig->base_url ?: $this->baseUrl, '/') . '/api/v2/' . $botConfig->bot_id . '/answer/' . $botConfig->answer_id . '/plugin/assistant/files';
        return $this->_get($url, $botConfig->api_key);
    }

    public function deleteAssistantFile($botConfig, $fileId)
    {
        if (empty($botConfig->answer_id)) return array('http_code' => 400, 'body' => 'No answer_id');
        $url = rtrim($botConfig->base_url ?: $this->baseUrl, '/') . '/api/v2/' . $botConfig->bot_id . '/answer/' . $botConfig->answer_id . '/plugin/assistant/files/' . $fileId;
        return $this->_delete($url, $botConfig->api_key);
    }
}
