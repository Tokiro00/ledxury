<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tienda — e-commerce público de Ledxury.
 * - Catálogo público (productos con stock+foto)
 * - Carrito (localStorage en cliente; aquí solo procesamos checkout)
 * - Pedido genera presupuesto a vendedor GerMAM Medellín (1234567)
 * - Notifica al bot por su API (BuilderBot) con los datos del cliente
 */
class Tienda extends CI_Controller {

    const VENDOR_ID = '1234567';   // GerMAM Medellín (default_vendor_id del bot 1)
    const STORE_ID  = 1;
    const BOT_ID_LOCAL = 1;
    const MIN_ORDER = 60000;       // Pedido mínimo COP

    public function __construct() {
        parent::__construct();
        $this->load->model('Tienda_model');
        $this->load->library('session');
    }

    /**
     * Catálogo principal.
     * GET /tienda
     */
    public function index() {
        $catalog = $this->Tienda_model->get_catalog();
        $this->load->view('tienda/index', array('catalog' => $catalog));
    }

    /**
     * Detalle de producto.
     * GET /tienda/producto/{id}
     */
    public function producto($id = null) {
        $id = urldecode((string)$id);
        $product = $this->Tienda_model->get_product($id);
        if (!$product) show_404();
        $this->load->view('tienda/producto', array('product' => $product));
    }

    /**
     * Carrito (renderizado client-side desde localStorage).
     * GET /tienda/carrito
     */
    public function carrito() {
        $this->load->view('tienda/carrito');
    }

    /**
     * Checkout (form de datos del cliente).
     * GET /tienda/checkout
     */
    public function checkout() {
        $this->load->view('tienda/checkout');
    }

    /**
     * Procesa el pedido: crea presupuesto y notifica al bot.
     * POST /tienda/placeOrder
     * body JSON: { client: {name, doc, phone, address, city, dept, email}, items: [{id, qty}] }
     */
    public function placeOrder() {
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            echo json_encode(array('ok' => false, 'error' => 'Datos inválidos'));
            return;
        }

        $client = $payload['client'] ?? array();
        $items  = $payload['items'] ?? array();
        $name    = trim((string)($client['name']    ?? ''));
        $doc     = trim((string)($client['doc']     ?? ''));
        $phone   = trim((string)($client['phone']   ?? ''));
        $address = trim((string)($client['address'] ?? ''));
        $city    = trim((string)($client['city']    ?? ''));
        $dept    = trim((string)($client['dept']    ?? ''));
        $email   = trim((string)($client['email']   ?? ''));

        // Validaciones mínimas
        $errors = array();
        if (mb_strlen($name) < 3)  $errors[] = 'Nombre demasiado corto';
        if (mb_strlen($phone) < 7) $errors[] = 'Teléfono inválido';
        if (mb_strlen($address) < 5) $errors[] = 'Dirección requerida';
        if (mb_strlen($city) < 2)  $errors[] = 'Ciudad requerida';
        if (empty($items)) $errors[] = 'Carrito vacío';
        if (!empty($errors)) {
            echo json_encode(array('ok' => false, 'error' => implode('. ', $errors)));
            return;
        }

        // Validar y enriquecer carrito contra DB
        $validItems = $this->Tienda_model->validate_cart($items);
        if (empty($validItems)) {
            echo json_encode(array('ok' => false, 'error' => 'Productos no disponibles'));
            return;
        }
        $total = 0;
        foreach ($validItems as $it) $total += $it['subtotal'];

        // Pedido mínimo
        if ($total < self::MIN_ORDER) {
            echo json_encode(array(
                'ok' => false,
                'error' => 'El pedido mínimo es $' . number_format(self::MIN_ORDER, 0, ',', '.') . '. Te faltan $' . number_format(self::MIN_ORDER - $total, 0, ',', '.') . '.'
            ));
            return;
        }

        // === Encontrar/crear cliente ===
        $this->load->model('clients_model');
        $clientId = null;
        // Buscar por documento
        if ($doc !== '') {
            $existing = $this->db->where('idNum', $doc)->where('deleted', 0)->get('clients')->row();
            if ($existing) $clientId = (int)$existing->idClient;
        }
        if (!$clientId) {
            // Buscar por celular
            $existing = $this->db->where('cellphone', $phone)->where('deleted', 0)->get('clients')->row();
            if ($existing) $clientId = (int)$existing->idClient;
        }

        date_default_timezone_set('America/Bogota');
        if (!$clientId) {
            $clientData = array(
                'idNum'      => $doc ?: null,
                'name'       => $name,
                'cellphone'  => $phone,
                'phone'      => $phone,
                'email'      => $email ?: null,
                'address'    => $address,
                'city'       => $city,
                'state'      => $dept,
                'is_new'     => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            );
            $this->db->insert('clients', $clientData);
            $clientId = (int)$this->db->insert_id();
        }

        // === Crear presupuesto ===
        $this->db->trans_start();
        $budgetData = array(
            'clientId'   => $clientId,
            'vendorId'   => self::VENDOR_ID,
            'storeId'    => self::STORE_ID,
            'total'      => $total,
            'date'       => date('Y-m-d H:i:s'),
            'state'      => 0, // pendiente
            'hasIva'     => 0,
            'iva'        => 8,
            'e_commerce' => 1,
            'comments'   => "Pedido web (tienda pública). Cliente: $name. Tel: $phone. Dir: $address, $city" . ($dept ? ", $dept" : '') . ($email ? ". Email: $email" : '') . '. Pago: contra entrega. Envío: Interrapidísimo. Vía: web.',
            'created_by' => self::VENDOR_ID,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );
        $this->db->insert('budgets', $budgetData);
        $budgetId = (int)$this->db->insert_id();

        // Detalle
        foreach ($validItems as $it) {
            $this->db->insert('budget_detail', array(
                'budgetId'    => $budgetId,
                'productId'   => $it['id'],
                'description' => $it['name'],
                'quantity'    => $it['qty'],
                'unit'        => $it['price'],
                'total'       => $it['subtotal'],
            ));
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            echo json_encode(array('ok' => false, 'error' => 'Error guardando el pedido'));
            return;
        }

        // === Notificar al bot ===
        $this->_notifyBot($budgetId, $name, $phone, $address, $city, $dept, $validItems, $total);

        echo json_encode(array(
            'ok' => true,
            'budget_id' => $budgetId,
            'redirect' => base_url() . 'tienda/exito/' . $budgetId,
        ));
    }

    /**
     * Página de éxito tras el pedido.
     * GET /tienda/exito/{id}
     */
    public function exito($id = null) {
        $id = (int)$id;
        if ($id <= 0) show_404();
        $budget = $this->db->where('idBudget', $id)->where('deleted', 0)->get('budgets')->row();
        if (!$budget) show_404();
        $client = $budget->clientId ? $this->db->where('idClient', $budget->clientId)->get('clients')->row() : null;
        $this->load->view('tienda/exito', array('budget' => $budget, 'client' => $client));
    }

    /**
     * Enviar notificación al bot vía BuilderBot API (best-effort).
     * No bloqueamos el pedido si esto falla.
     */
    private function _notifyBot($budgetId, $name, $phone, $address, $city, $dept, $items, $total) {
        try {
            $bot = $this->db->where('id', self::BOT_ID_LOCAL)->where('is_active', 1)->get('builderbot_configs')->row();
            if (!$bot) return;

            // Resumen del pedido
            $lines = array();
            $lines[] = "🛒 Nuevo pedido web #$budgetId";
            $lines[] = "Cliente: $name";
            $lines[] = "Tel: $phone";
            $lines[] = "Dir: $address, $city" . ($dept ? ", $dept" : '');
            $lines[] = "";
            $lines[] = "Productos:";
            foreach ($items as $it) {
                $lines[] = "  • {$it['qty']}x {$it['id']} — \${$it['price']} = \${$it['subtotal']}";
            }
            $lines[] = "";
            $lines[] = "TOTAL: $" . number_format($total, 0, ',', '.');
            $lines[] = "Origen: web (ledxury.com/tienda)";
            $body = implode("\n", $lines);

            // Limpiar teléfono para WhatsApp (formato Colombia: 57XXXXXXXXXX)
            $waPhone = preg_replace('/[^0-9]/', '', $phone);
            if (mb_strlen($waPhone) === 10) $waPhone = '57' . $waPhone;

            // BuilderBot API: enviar mensaje "como si fuera del cliente" para que el bot procese y responda al cliente.
            // Endpoint: POST {base_url}/v2/blueprints/{bot_id}/intents/{answer_id}
            $url = rtrim($bot->base_url, '/') . '/v2/blueprints/' . $bot->bot_id . '/intents/' . $bot->answer_id;
            $headers = array(
                'Content-Type: application/json',
                'x-api-builderbot: ' . $bot->api_key,
            );
            $data = array(
                'phone'   => $waPhone,
                'message' => $body,
                'meta'    => array('source' => 'web', 'budget_id' => $budgetId),
            );

            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_SSL_VERIFYPEER => false,
            ));
            $resp = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Log best-effort
            $this->load->model('logs_model');
            $this->logs_model->logMessage('info', "Tienda web → bot notify budget #$budgetId — HTTP $http");
        } catch (\Exception $e) {
            // Silencioso: no afectar el pedido si falla la notificación
        }
    }
}
