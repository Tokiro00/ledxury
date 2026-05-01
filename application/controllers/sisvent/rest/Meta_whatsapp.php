<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Meta WhatsApp Cloud API direct controller (sin BuilderBot).
 *
 * Atiende un único número WhatsApp Business (Ledxury Garantías) registrado
 * directamente en Meta Cloud API. Sirve para canales de baja frecuencia y
 * alta sensibilidad (garantías, devoluciones, reclamos) donde queremos
 * agente humano en vez de flow automatizado.
 *
 * Configuración esperada en application/config/secrets.php:
 *
 *   $config['meta_whatsapp_garantias'] = array(
 *       'phone_number_id' => '...',         // ID interno del número en Meta
 *       'waba_id'         => '...',         // WhatsApp Business Account ID
 *       'access_token'    => '...',         // System User token permanente
 *       'verify_token'    => '...',         // String secreto para handshake del webhook
 *       'graph_version'   => 'v19.0',       // versión de Graph API
 *       'bot_config_id'   => 99,            // id en builderbot_configs del bot virtual
 *   );
 *
 * Endpoints:
 *   GET  /sisvent/rest/meta_whatsapp/webhook  → handshake (devuelve hub.challenge)
 *   POST /sisvent/rest/meta_whatsapp/webhook  → recibe mensajes / status updates
 *
 * El payload entrante de Meta tiene esta forma:
 *   {
 *     object: "whatsapp_business_account",
 *     entry: [{
 *       id: "<waba_id>",
 *       changes: [{
 *         field: "messages",
 *         value: {
 *           messaging_product: "whatsapp",
 *           metadata: { display_phone_number, phone_number_id },
 *           contacts: [{ profile: { name }, wa_id }],
 *           messages: [{ from, id, timestamp, type, text:{body}, ... }]
 *         }
 *       }]
 *     }]
 *   }
 */
class Meta_whatsapp extends CI_Controller
{
    private $cfg = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('builderbot_model');
        $this->config->load('secrets', true);
        $secrets = $this->config->item('secrets');
        $this->cfg = isset($secrets['meta_whatsapp_garantias']) ? $secrets['meta_whatsapp_garantias'] : null;
        // Fallback: leer secrets.php directo si la lib no lo cargó.
        if (empty($this->cfg) && file_exists(APPPATH . 'config/secrets.php')) {
            include(APPPATH . 'config/secrets.php');
            $this->cfg = isset($config['meta_whatsapp_garantias']) ? $config['meta_whatsapp_garantias'] : null;
        }
    }

    // ========================================================================
    // WEBHOOK
    // ========================================================================

    /**
     * Endpoint único webhook(). Despacha por método HTTP:
     *   GET  → handshake (Meta envía hub.mode=subscribe + hub.challenge)
     *   POST → mensaje entrante o status update
     */
    public function webhook()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->_handshake();
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->_receivePost();
            return;
        }
        http_response_code(405);
        echo 'Method Not Allowed';
    }

    /**
     * Handshake del webhook: Meta llama con
     *   ?hub.mode=subscribe&hub.verify_token=XXX&hub.challenge=YYY
     * Si el token coincide con cfg.verify_token, devolvemos el challenge en plain.
     */
    private function _handshake()
    {
        $mode      = $this->input->get('hub_mode');      // CI normaliza '.' a '_'
        $challenge = $this->input->get('hub_challenge');
        $token     = $this->input->get('hub_verify_token');

        // Variantes con punto por si CI no las normalizó
        if (!$mode)      $mode      = $this->input->get('hub.mode');
        if (!$challenge) $challenge = $this->input->get('hub.challenge');
        if (!$token)     $token     = $this->input->get('hub.verify_token');

        $expected = $this->cfg['verify_token'] ?? '';
        if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, (string)$token)) {
            header('Content-Type: text/plain');
            echo (string)$challenge;
            return;
        }
        http_response_code(403);
        echo 'Forbidden';
    }

    /**
     * Recibe el POST de Meta con events/messages. Siempre responde 200 OK
     * rápido (Meta marca como falla si tarda > 5s o devuelve != 2xx).
     */
    private function _receivePost()
    {
        // Responder 200 inmediatamente para que Meta no marque el webhook como caído.
        // Cualquier procesamiento pesado se hace después.
        header('Content-Type: application/json');
        ignore_user_abort(true);

        $raw = file_get_contents('php://input');
        $this->_log('RAW: ' . $raw);

        echo json_encode(['received' => true]);
        // Flush al cliente para que Meta no espere
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

        $payload = json_decode($raw, true);
        if (empty($payload) || empty($payload['entry'])) return;

        foreach ($payload['entry'] as $entry) {
            if (empty($entry['changes'])) continue;
            foreach ($entry['changes'] as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];
                if ($field !== 'messages') continue;

                // Procesar status updates (sent/delivered/read/failed)
                if (!empty($value['statuses'])) {
                    foreach ($value['statuses'] as $st) $this->_processStatus($st);
                }
                // Procesar mensajes entrantes
                if (!empty($value['messages'])) {
                    $contactName = $value['contacts'][0]['profile']['name'] ?? null;
                    foreach ($value['messages'] as $msg) {
                        try {
                            $this->_processIncomingMessage($msg, $contactName);
                        } catch (Exception $e) {
                            $this->_log('ERROR procesando mensaje: ' . $e->getMessage());
                        }
                    }
                }
            }
        }
    }

    /**
     * Guarda un mensaje entrante en bot_conversations + builderbot_messages,
     * usando el bot_config_id que está en cfg (es el "bot virtual" Garantías).
     */
    private function _processIncomingMessage($msg, $contactName)
    {
        $bot_config_id = (int)($this->cfg['bot_config_id'] ?? 0);
        if ($bot_config_id <= 0) {
            $this->_log('SKIP: bot_config_id no configurado en secrets.meta_whatsapp_garantias');
            return;
        }
        $botConfig = $this->builderbot_model->getConfig($bot_config_id);
        if (!$botConfig) {
            $this->_log("SKIP: bot_config_id={$bot_config_id} no encontrado en builderbot_configs");
            return;
        }

        $from = preg_replace('/[^0-9]/', '', (string)($msg['from'] ?? ''));
        if ($from === '') return;

        $type = $msg['type'] ?? 'text';
        $content = '';
        $media_url = null;

        switch ($type) {
            case 'text':
                $content = $msg['text']['body'] ?? '';
                break;
            case 'image':
                $content = $msg['image']['caption'] ?? '[Imagen]';
                $media_url = $msg['image']['id'] ?? null; // luego se descarga
                break;
            case 'audio':
            case 'voice':
                $content = '[Audio]';
                $media_url = $msg['audio']['id'] ?? ($msg['voice']['id'] ?? null);
                break;
            case 'video':
                $content = $msg['video']['caption'] ?? '[Video]';
                $media_url = $msg['video']['id'] ?? null;
                break;
            case 'document':
                $content = $msg['document']['caption'] ?? ('[Documento] ' . ($msg['document']['filename'] ?? ''));
                $media_url = $msg['document']['id'] ?? null;
                break;
            case 'sticker':
                $content = '[Sticker]';
                $media_url = $msg['sticker']['id'] ?? null;
                break;
            case 'button':
                $content = $msg['button']['text'] ?? '[Botón]';
                break;
            case 'interactive':
                $reply = $msg['interactive']['button_reply']['title']
                    ?? $msg['interactive']['list_reply']['title']
                    ?? '[Interactivo]';
                $content = $reply;
                break;
            default:
                $content = "[{$type}]";
        }

        $this->builderbot_model->saveConversationMessage(
            $bot_config_id,
            $from,
            'incoming',
            $content,
            $media_url,
            null
        );

        // Actualizar nombre del contacto si vino en el payload
        if ($contactName) {
            $this->builderbot_model->getOrCreateConversation($bot_config_id, $from, $contactName);
        }

        $this->_log("INCOMING type={$type} from={$from} content_len=" . strlen($content));
    }

    /**
     * Status update de un mensaje saliente. Por ahora solo logeamos;
     * se puede usar después para mostrar ✓/✓✓ en la UI.
     */
    private function _processStatus($status)
    {
        $this->_log('STATUS msg=' . ($status['id'] ?? '?') . ' status=' . ($status['status'] ?? '?'));
    }

    // ========================================================================
    // SENDER (Graph API)
    // ========================================================================

    /**
     * Envía un mensaje de texto en la ventana de 24h posterior al último
     * mensaje del cliente. Fuera de esa ventana, Meta solo permite plantillas.
     *
     * @return array con response decoded de Meta (incluye 'messages'[0]['id'] si OK)
     */
    public function _sendText($to, $body)
    {
        return $this->_callGraph('messages', [
            'messaging_product' => 'whatsapp',
            'to'                => $this->_normalizePhone($to),
            'type'              => 'text',
            'text'              => ['body' => $body, 'preview_url' => false],
        ]);
    }

    /**
     * Envía una plantilla aprobada por Meta. Las plantillas se aprueban por
     * separado en Meta Business Manager.
     *
     * @param string $to      Número destino
     * @param string $template Nombre de la plantilla (ej. "pedido_entregado")
     * @param string $lang     Código de idioma (ej. "es_CO" o "en")
     * @param array  $bodyParams Variables {{1}}, {{2}}, ... de la plantilla
     */
    public function _sendTemplate($to, $template, $lang = 'es_CO', $bodyParams = [])
    {
        $components = [];
        if (!empty($bodyParams)) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(function($v){ return ['type' => 'text', 'text' => (string)$v]; }, $bodyParams),
            ];
        }
        return $this->_callGraph('messages', [
            'messaging_product' => 'whatsapp',
            'to'                => $this->_normalizePhone($to),
            'type'              => 'template',
            'template'          => [
                'name'       => $template,
                'language'   => ['code' => $lang],
                'components' => $components,
            ],
        ]);
    }

    /**
     * POST a Graph API contra el phone_number_id configurado.
     */
    private function _callGraph($path, $body)
    {
        $version = $this->cfg['graph_version'] ?? 'v19.0';
        $pni     = $this->cfg['phone_number_id'] ?? '';
        $token   = $this->cfg['access_token'] ?? '';
        if ($pni === '' || $token === '') {
            $this->_log('SEND ERROR: cfg incompleta (phone_number_id o access_token vacío)');
            return ['error' => 'config_missing'];
        }

        $url = "https://graph.facebook.com/{$version}/{$pni}/{$path}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($resp, true) ?: ['raw' => $resp];
        $this->_log("SEND http={$code} resp=" . substr((string)$resp, 0, 300));
        return $decoded;
    }

    /**
     * Meta espera el número en formato E.164 sin signo + (ej "573001234567").
     */
    private function _normalizePhone($phone)
    {
        $p = preg_replace('/[^0-9]/', '', (string)$phone);
        // Si vino sin prefijo de país y tiene 10 dígitos, asumir Colombia
        if (strlen($p) === 10) $p = '57' . $p;
        return $p;
    }

    private function _log($msg)
    {
        @file_put_contents(APPPATH . 'logs/meta_whatsapp.log',
            date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
    }
}
