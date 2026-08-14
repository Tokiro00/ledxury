<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * BotResponse — Endpoint estilo "JSON API" de Chatfuel para que BuilderBot
 * delegue la decisión de respuesta al backend MAM.
 *
 * Inspirado en el patrón de Chatfuel: el bot manda atributos del cliente,
 * el backend calcula TODO (producto, precio, agotado, envío), y devuelve
 * un JSON con `messages` (textos a renderizar) + `set_attributes` (datos
 * a guardar en el perfil del usuario en el bot) + opcional `redirect_to`.
 *
 * FASE 1: Solo agrega rutas nuevas. NO toca BotImport.php ni webhooks
 * existentes. BuilderBot puede seguir usando `/webhook/builderbot` como
 * hasta hoy en paralelo. Cuando se reconfigure el flow para usar este
 * endpoint, recién entonces empieza a tener efecto en producción real.
 *
 * Endpoints:
 *   POST /api/v1/bot/quote    → preview_quote (NO crea budget)
 *   POST /api/v1/bot/confirm  → confirm_quote (crea cliente + budget)
 *
 * Auth: header `X-Bot-Key: <bot_response_key>` (configurado en secrets.php).
 */
class BotResponse extends CI_Controller {

    private $apiKey = '';

    public function __construct() {
        parent::__construct();
        $this->load->model('products_model');
        $this->load->model('clients_model');
        $this->load->model('budgets_model');

        // Cargar la API key desde secrets.php. Patrón consistente con Builderbot_lib:
        // secrets.php define $config['...'] como variables locales (NO se registra
        // en el config loader de CI), por eso hay que include explícito.
        $secretsFile = APPPATH . 'config/secrets.php';
        if (file_exists($secretsFile)) {
            include($secretsFile);
            $this->apiKey = isset($config['bot_response_key']) ? (string)$config['bot_response_key'] : '';
        }

        // Todos los endpoints devuelven JSON
        header('Content-Type: application/json; charset=utf-8');

        // CORS básico — útil si BuilderBot llama desde su SaaS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, X-Bot-Key');
        header('Access-Control-Allow-Methods: POST, OPTIONS');

        if ($this->input->method() === 'options') {
            http_response_code(200);
            exit;
        }
    }

    // =========================================================
    // ENDPOINTS PÚBLICOS
    // =========================================================

    /**
     * POST /api/v1/bot/quote
     *
     * Recibe atributos del bot, resuelve producto + precio + envío y
     * devuelve los mensajes a renderizar. NO crea budget — es preview puro.
     *
     * Body esperado:
     * {
     *   "action": "preview_quote",
     *   "phone": "573114567890",
     *   "attributes": {
     *     "producto_solicitado": "5 modulos verdes 12v",
     *     "cantidad": 5,
     *     "ciudad": "medellin"   // opcional, para calcular envío
     *   }
     * }
     */
    public function quote() {
        if (!$this->_authenticate()) return;
        if ($this->input->method() !== 'post') {
            return $this->_error('Method not allowed', 405);
        }
        $input = $this->_readJson();
        if (!$input) return $this->_error('Body JSON inválido o vacío', 400);

        return $this->_previewQuote($input);
    }

    /**
     * POST /api/v1/bot/confirm
     *
     * Crea cliente (si no existe) y budget en estado pendiente. Reutiliza los
     * atributos calculados por /quote más los datos del cliente.
     *
     * Body esperado:
     * {
     *   "action": "confirm_quote",
     *   "phone": "573114567890",
     *   "vendor_id": "1234567",    // opcional, fallback default
     *   "store_id": 1,             // opcional, fallback default
     *   "attributes": {
     *     "nombre": "Juan Perez",
     *     "cedula": "71234567",
     *     "direccion": "Cra 50 # 30-20",
     *     "ciudad": "Medellín",
     *     "departamento": "Antioquia",
     *     "sku": "3LED-12V-F",
     *     "cantidad": 5,
     *     "precio_unit": 8000,
     *     "subtotal": 40000,
     *     "envio": 0,
     *     "total": 40000
     *   }
     * }
     */
    public function confirm() {
        if (!$this->_authenticate()) return;
        if ($this->input->method() !== 'post') {
            return $this->_error('Method not allowed', 405);
        }
        $input = $this->_readJson();
        if (!$input) return $this->_error('Body JSON inválido o vacío', 400);

        return $this->_confirmQuote($input);
    }

    // =========================================================
    // LÓGICA PREVIEW
    // =========================================================

    private function _previewQuote($input) {
        $attrs = isset($input['attributes']) ? (array)$input['attributes'] : [];
        $phone = isset($input['phone']) ? preg_replace('/[^0-9]/', '', (string)$input['phone']) : '';

        $productText = trim((string)($attrs['producto_solicitado'] ?? ''));
        $cantidad    = max(1, (int)($attrs['cantidad'] ?? 1));

        if ($productText === '') {
            return $this->_respond(
                ['Necesito que me digas qué producto quieres pedir.'],
                ['estado' => 'falta_producto']
            );
        }

        // 1. Resolver producto a SKU
        $sku = $this->_resolveProduct($productText);
        if (!$sku) {
            return $this->_respond(
                [
                    'No reconocí el producto "' . $productText . '" 🤔',
                    '¿Me confirmas voltaje (12V o 24V), cantidad de LEDs (3, 6 o 12) y color?'
                ],
                ['estado' => 'aclarar_producto']
            );
        }

        // 2. Verificar agotado (consulta blocked_products)
        $blocked = $this->_loadBlockedProducts();
        if (in_array($sku, $blocked, true)) {
            $alternatives = $this->_findAlternatives($sku);
            if (empty($alternatives)) {
                return $this->_respond(
                    [
                        'Lo siento, ese producto está agotado y no tengo alternativas similares 😕',
                        'Un asesor te va a contactar para ayudarte.'
                    ],
                    ['estado' => 'agotado_sin_alternativa', 'sku_agotado' => $sku]
                );
            }
            $altColors = [];
            foreach ($alternatives as $alt) {
                $color = $this->_colorNameFromSku($alt['idProduct']);
                if ($color) $altColors[] = $color;
            }
            return $this->_respond(
                [
                    '😕 Ese color está agotado por ahora.',
                    'Pero tengo disponible al mismo precio: ' . implode(', ', $altColors),
                    '¿Quieres cambiar a alguno? Respóndeme con el color o escribe NO para esperar al original.'
                ],
                [
                    'estado'         => 'agotado_con_alternativas',
                    'sku_agotado'    => $sku,
                    'alternativas'   => array_column($alternatives, 'idProduct'),
                    'alternativas_colores' => $altColors,
                ]
            );
        }

        // 3. Producto disponible — calcular precio + envío
        $product = $this->products_model->getProduct($sku);
        if (!$product) {
            return $this->_respond(
                ['Hubo un problema buscando el precio del producto. Te conecto con un asesor.'],
                ['estado' => 'error_precio', 'sku' => $sku]
            );
        }
        $precioUnit = (float) $product->price;
        $subtotal   = $precioUnit * $cantidad;

        // Política Ledxury simplificada: envío gratis a Medellín, $15k al resto
        $ciudad = strtolower((string)($attrs['ciudad'] ?? ''));
        $envio  = (strpos($ciudad, 'medel') !== false) ? 0 : 15000;
        $total  = $subtotal + $envio;

        $envioTxt = $envio === 0 ? 'Envío *gratis* 🚚' : 'Envío: $' . number_format($envio, 0, ',', '.');

        return $this->_respond(
            [
                '✅ Listo, este es tu pedido:',
                "{$cantidad}x " . $product->description,
                "Subtotal: $" . number_format($subtotal, 0, ',', '.'),
                $envioTxt,
                "*Total: $" . number_format($total, 0, ',', '.') . "*",
                '¿Confirmas el pedido? Responde *SI* para confirmar.'
            ],
            [
                'sku'         => $sku,
                'cantidad'    => $cantidad,
                'precio_unit' => $precioUnit,
                'subtotal'    => $subtotal,
                'envio'       => $envio,
                'total'       => $total,
                'estado'      => 'esperando_confirmacion',
            ]
        );
    }

    // =========================================================
    // LÓGICA CONFIRM
    // =========================================================

    private function _confirmQuote($input) {
        $attrs    = isset($input['attributes']) ? (array)$input['attributes'] : [];
        $phone    = isset($input['phone']) ? preg_replace('/[^0-9]/', '', (string)$input['phone']) : '';
        $vendorId = isset($input['vendor_id']) ? (string)$input['vendor_id'] : '1234567';  // default GerMam Medellín
        $storeId  = isset($input['store_id'])  ? (int)$input['store_id']  : 1;

        // Validar mínimos
        $required = ['nombre', 'sku', 'cantidad', 'subtotal', 'total'];
        foreach ($required as $k) {
            if (!isset($attrs[$k]) || $attrs[$k] === '') {
                return $this->_error("Falta atributo requerido: {$k}", 400);
            }
        }
        if (empty($phone) || strlen($phone) < 10) {
            return $this->_error('Phone inválido', 400);
        }

        date_default_timezone_set('America/Bogota');
        $this->db->trans_start();

        // 1. Cliente: buscar por celular, crear si no existe
        $client = method_exists($this->clients_model, 'getClientByPhone')
            ? $this->clients_model->getClientByPhone($phone)
            : null;

        if (!$client) {
            $fid = 1;
            if (method_exists($this->clients_model, 'getHighestClientFid')) {
                $row = $this->clients_model->getHighestClientFid();
                $fid = isset($row->next_fid) ? (int)$row->next_fid : 1;
            }
            $clientData = [
                'name'      => trim((string)$attrs['nombre']),
                'idNum'     => !empty($attrs['cedula']) ? trim((string)$attrs['cedula']) : null,
                'cellphone' => $phone,
                'phone'     => $phone,
                'address'   => trim((string)($attrs['direccion'] ?? '')),
                'city'      => trim((string)($attrs['ciudad'] ?? '')),
                'state'     => trim((string)($attrs['departamento'] ?? '')),
                'vendor'    => $vendorId,
                'retail'    => 1,
                'rate'      => 0,
                'f_id'      => $fid,
                'type'      => '-',
                'is_new'    => 1,
                'created_by'=> 'bot-response',
                'created_at'=> date('Y-m-d H:i:s'),
                'updated_at'=> date('Y-m-d H:i:s'),
            ];
            $this->db->insert('clients', $clientData);
            $clientId = (int) $this->db->insert_id();
        } else {
            $clientId = (int) $client->idClient;
        }

        // 2. Budget — solo columnas que existen en producción (verificadas en mamdb)
        $budgetData = [
            'clientId'     => $clientId,
            'storeId'      => $storeId,
            'vendorId'     => $vendorId,
            'total'        => (int) round($attrs['total']),
            'state'        => 0,
            'e_commerce'   => 1,
            'hasIva'       => 0,
            'iva'          => 0,
            'list_price'   => 0,
            'date'         => date('Y-m-d H:i:s'),
            'created_by'   => $vendorId,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
            'comments'     => 'Pedido confirmado vía bot (BotResponse) ' . date('Y-m-d H:i'),
            'delivery_type'=> 5,  // Interrapidísimo
            'budget_type'  => 'venta',
        ];
        $this->db->insert('budgets', $budgetData);
        $budgetId = (int) $this->db->insert_id();

        // 3. Detalle
        $this->db->insert('budget_detail', [
            'budgetId'  => $budgetId,
            'productId' => (string) $attrs['sku'],
            'quantity'  => (int) $attrs['cantidad'],
            'unit'      => (int) round($attrs['precio_unit']),
            'base'      => (int) round($attrs['precio_unit']),
            'total'     => (int) round($attrs['subtotal']),
        ]);

        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            return $this->_error('Error guardando el pedido en BD', 500);
        }

        // Log de auditoría — en logs/webhook_debug.log para no chocar con
        // logs_model que requiere sesión web activa.
        @file_put_contents(
            APPPATH . 'logs/webhook_debug.log',
            date('Y-m-d H:i:s') . " BotResponse CONFIRM: budget={$budgetId} client={$clientId} phone={$phone} total={$attrs['total']} sku={$attrs['sku']}\n",
            FILE_APPEND
        );

        return $this->_respond(
            [
                "✅ Pedido confirmado! Tu número de pedido es *#" . str_pad($budgetId, 6, '0', STR_PAD_LEFT) . "*",
                "Te llega entre 2 y 4 días hábiles 📦",
                "Si necesitas consultar tu guía, escribe *guía* en cualquier momento."
            ],
            [
                'budget_id' => $budgetId,
                'client_id' => $clientId,
                'estado'    => 'confirmado',
            ]
        );
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Auth por header X-Bot-Key. Si la key no coincide, devuelve 401 y exit.
     * Retorna true si OK, false (después de error) si falla.
     */
    private function _authenticate() {
        if (empty($this->apiKey)) {
            $this->_error('Endpoint no configurado: falta bot_response_key en secrets.php', 503);
            return false;
        }
        $provided = $this->input->get_request_header('X-Bot-Key', true);
        if (empty($provided) || !hash_equals($this->apiKey, (string)$provided)) {
            $this->_error('Unauthorized', 401);
            return false;
        }
        return true;
    }

    private function _readJson() {
        $raw = file_get_contents('php://input');
        if (empty($raw)) return null;
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private function _respond($messages, $attributes = [], $redirectBlocks = []) {
        $out = [
            'success'         => true,
            'messages'        => array_values($messages),
            'set_attributes'  => (object) $attributes,
        ];
        if (!empty($redirectBlocks)) {
            $out['redirect_to_blocks'] = $redirectBlocks;
        }
        echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function _error($msg, $status = 400) {
        http_response_code($status);
        echo json_encode([
            'success' => false,
            'error'   => $msg,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Mapeo color → letra de SKU. Consistente con BotImport::$color_map.
     */
    private function _colorMap() {
        return [
            'azul hielo' => 'I', 'azul ice' => 'I', 'ice' => 'I', 'hielo' => 'I',
            'azul oscuro' => 'E', 'azul' => 'E', 'blue' => 'E',
            'rojo' => 'C', 'red' => 'C',
            'verde' => 'F', 'green' => 'F',
            'amarillo' => 'D', 'yellow' => 'D',
            'blanco calido' => 'B', 'blanco cálido' => 'B', 'warm white' => 'B',
            'blanco' => 'A', 'white' => 'A',
            'rosado' => 'G', 'fucsia' => 'G', 'pink' => 'G',
            'morado' => 'H', 'purple' => 'H',
            'verde limon' => 'J', 'verde limón' => 'J', 'limon' => 'J', 'limón' => 'J',
            'verde turquesa' => 'K', 'turquesa' => 'K',
        ];
    }

    /**
     * Resuelve un texto libre del cliente a un SKU concreto.
     * Soporta:
     *   - "3LED-12V-F" → match exacto del SKU
     *   - "5 modulos verdes 12v" → parsea LEDs + voltaje + color
     *   - alias en bot_product_aliases (si existe)
     */
    private function _resolveProduct($text) {
        $text = trim((string)$text);
        if ($text === '') return null;

        $upper = strtoupper($text);

        // 1. ¿Contiene un SKU directo?
        if (preg_match('/(\d+LED-\d+V-[A-Z])/', $upper, $m)) {
            $sku = $m[1];
            $prod = $this->products_model->getProduct($sku);
            if ($prod) return $sku;
        }

        // 2. Parsear LEDs + voltaje + color
        $leds = null; $volt = null; $color = null;
        if (preg_match('/(\d+)\s*led/i', $text, $m)) {
            $leds = (int)$m[1];
        } elseif (preg_match('/(\d+)\s*m[óo]dulo/i', $text, $m)) {
            // "3 modulos" no es el LED count si viene "5 modulos 6LED" — pero
            // como simplificación inicial asumimos que un solo número refiere
            // al modelo. Si dice "modulos 6 LED" preferimos el "6 LED" via
            // primer regex. Si solo dice "3 modulos" sin LED count, no resuelve.
        }
        if (preg_match('/(\d+)\s*v(\b|olt)/i', $text, $m)) {
            $volt = (int)$m[1];
        }
        $colorMap = $this->_colorMap();
        $textLower = mb_strtolower($text);
        foreach ($colorMap as $name => $letter) {
            if (strpos($textLower, $name) !== false) {
                $color = $letter;
                break;
            }
        }

        if ($leds && $volt && $color) {
            $sku = "{$leds}LED-{$volt}V-{$color}";
            $prod = $this->products_model->getProduct($sku);
            if ($prod) return $sku;
        }

        // 3. Fallback: bot_product_aliases (busca match en alias_norm/alias_raw)
        if ($this->db->table_exists('bot_product_aliases')) {
            $row = $this->db->select('product_code')
                ->from('bot_product_aliases')
                ->group_start()
                    ->like('alias_norm', $textLower, 'both')
                    ->or_like('alias_raw', $textLower, 'both')
                ->group_end()
                ->limit(1)->get()->row();
            if ($row && !empty($row->product_code)) {
                $prod = $this->products_model->getProduct($row->product_code);
                if ($prod) return $row->product_code;
            }
        }

        return null;
    }

    /**
     * Lee blocked_products (productos agotados) — mismos códigos que usa BotImport.
     */
    private function _loadBlockedProducts() {
        $codes = [];
        try {
            $rows = $this->db->select('product_code')->get('blocked_products')->result();
            foreach ($rows as $r) $codes[] = strtoupper(trim((string)$r->product_code));
        } catch (\Throwable $e) { /* tabla aún no creada */ }
        return array_values(array_unique($codes));
    }

    /**
     * Busca SKUs hermanos (mismo modelo+voltaje, distinto color, no bloqueados).
     * Patrón consistente con BotImport::_findSkuAlternatives.
     */
    private function _findAlternatives($sku) {
        if (!preg_match('/^(\d+)LED-(\d+V)-([A-Z])$/', $sku, $m)) return [];
        $prefix = $m[1] . 'LED-' . $m[2] . '-';
        $blocked = $this->_loadBlockedProducts();

        $this->db->select('idProduct, description, price')
                 ->from('products')
                 ->like('idProduct', $prefix, 'after')
                 ->where('idProduct !=', $sku)
                 ->order_by('idProduct', 'ASC');
        if (!empty($blocked)) {
            $this->db->where_not_in('idProduct', $blocked);
        }
        $rows = $this->db->get()->result_array();
        return is_array($rows) ? $rows : [];
    }

    /**
     * Dado un SKU 6LED-12V-F devuelve "Verde" (legible para el cliente).
     */
    private function _colorNameFromSku($sku) {
        if (!preg_match('/-([A-Z])$/', $sku, $m)) return $sku;
        $letter = $m[1];
        $reverse = [
            'A' => 'Blanco', 'B' => 'Blanco cálido', 'C' => 'Rojo',
            'D' => 'Amarillo', 'E' => 'Azul', 'F' => 'Verde',
            'G' => 'Rosado', 'H' => 'Morado', 'I' => 'Azul hielo',
            'J' => 'Verde limón', 'K' => 'Verde turquesa',
        ];
        return $reverse[$letter] ?? $sku;
    }
}
