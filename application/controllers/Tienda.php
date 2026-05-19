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

    // Bot local que define el vendedor + bodega del e-commerce.
    // El vendor_id se lee desde builderbot_configs.default_vendor_id (no hardcodear).
    const BOT_ID_LOCAL = 1;        // Medellín
    const MIN_ORDER    = 60000;    // Pedido mínimo COP

    public function __construct() {
        parent::__construct();
        $this->load->model('Tienda_model');
        $this->load->model('clients_model');
        $this->load->model('budgets_model');
        $this->load->library('session');
    }

    /**
     * Rate limit por (ip, endpoint) en ventana móvil. Devuelve true si está dentro del
     * límite, false si lo excedió. Limpia entradas viejas oportunísticamente.
     *
     * @param string $endpoint  Nombre del endpoint (ej: 'placeOrder', 'otpSend')
     * @param int    $maxHits   Máximo de hits permitidos en la ventana
     * @param int    $windowSec Tamaño de la ventana en segundos
     */
    private function _rateLimit($endpoint, $maxHits, $windowSec) {
        $ip = (string) $this->input->ip_address();
        if ($ip === '') return true; // Sin IP no limito (caso extraño)

        $cutoff = date('Y-m-d H:i:s', time() - $windowSec);
        $count = $this->db->where('ip', $ip)
            ->where('endpoint', $endpoint)
            ->where('created_at >=', $cutoff)
            ->count_all_results('rate_limit');

        if ($count >= $maxHits) return false;

        $this->db->insert('rate_limit', array(
            'ip'         => $ip,
            'endpoint'   => $endpoint,
            'created_at' => date('Y-m-d H:i:s'),
        ));

        // Limpieza oportunista: 1 de cada 50 inserts borra registros >24h
        if (random_int(1, 50) === 1) {
            $this->db->where('created_at <', date('Y-m-d H:i:s', strtotime('-1 day')))
                ->delete('rate_limit');
        }
        return true;
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
        // Endpoint público (sin sesión). Suprimir errores en pantalla para que cualquier
        // warning no corrompa la respuesta JSON. Capturamos cualquier salida previa.
        @ini_set('display_errors', '0');
        @ini_set('html_errors', '0');
        if (ob_get_level() === 0) ob_start();

        header('Content-Type: application/json');

        // Rate limit: máx 5 pedidos por IP cada 10 minutos.
        // Evita SPAM/abuse del endpoint público (creación masiva de clientes/budgets).
        if (!$this->_rateLimit('placeOrder', 5, 600)) {
            if (ob_get_level() > 0) ob_clean();
            http_response_code(429);
            echo json_encode(array('ok' => false, 'error' => 'Demasiados intentos. Espera unos minutos antes de volver a intentar.'));
            return;
        }

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            if (ob_get_level() > 0) ob_clean();
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

        // === Cargar config del bot (vendor + bodega) ===
        $bot = $this->db->where('id', self::BOT_ID_LOCAL)->get('builderbot_configs')->row();
        if (!$bot || empty($bot->default_vendor_id)) {
            echo json_encode(array('ok' => false, 'error' => 'Bot no configurado'));
            return;
        }
        $vendorId = (int) $bot->default_vendor_id;
        $storeId  = (int) ($bot->default_store_id ?: 1);

        // Normalizar celular (Colombia: sin prefijo 57)
        $celular_norm = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($celular_norm) > 10 && strpos($celular_norm, '57') === 0) {
            $celular_norm = substr($celular_norm, 2);
        }

        // Documento → celular si no se proporcionó (fallback acordado)
        if (empty($doc) || strlen(preg_replace('/[^0-9]/', '', $doc)) < 6) {
            $doc = $celular_norm;
        }

        date_default_timezone_set('America/Bogota');

        // === DUPLICATE GUARD: lock por (vendor + cellphone + total), 30 min ===
        $lockKey = 'led_pdo_' . md5($vendorId . '|' . $celular_norm . '|' . $total);
        $lockRow = $this->db->query("SELECT GET_LOCK(?, 8) AS got", array($lockKey))->row();
        $gotLock = $lockRow && (int)$lockRow->got === 1;

        $existing = $this->db->select('budgets.idBudget')
            ->from('budgets')
            ->join('clients', 'clients.idClient = budgets.clientId', 'left')
            ->where('budgets.vendorId', $vendorId)
            ->where('budgets.total', $total)
            ->where('budgets.date >=', date('Y-m-d H:i:s', strtotime('-30 minutes')))
            ->like('clients.cellphone', $celular_norm, 'both')
            ->where('budgets.deleted', 0)
            ->order_by('budgets.idBudget', 'DESC')
            ->limit(1)
            ->get()->row();

        if ($existing) {
            if ($gotLock) $this->db->query("SELECT RELEASE_LOCK(?)", array($lockKey));
            echo json_encode(array(
                'ok' => true,
                'budget_id' => (int)$existing->idBudget,
                'redirect' => base_url() . 'tienda/exito/' . (int)$existing->idBudget,
            ));
            return;
        }

        // === Encontrar/crear cliente (mismo patrón que BotImport) ===
        $client = $this->clients_model->getClientByPhone($celular_norm);
        if (!$client && !empty($doc)) {
            $client = $this->db->where('idNum', $doc)->where('deleted', 0)->get('clients')->row();
        }

        if (!$client) {
            $next_fid = (int)$this->clients_model->getHighestClientFid()->next_fid + 1;
            $client_data = array(
                'idNum'     => $doc,
                'name'      => $name,
                'email'     => $email ?: '',
                'phone'     => $celular_norm,
                'cellphone' => $celular_norm,
                'address'   => $address,
                'city'      => $city,
                'state'     => $dept,
                'vendor'    => $vendorId,
                'retail'    => 1,
                'rate'      => 0,
                'f_id'      => $next_fid,
            );
            $this->clients_model->save($client_data);
            $clientId = (int)$this->db->insert_id();
        } else {
            $clientId = (int)$client->idClient;
            $update = array('cellphone' => $celular_norm ?: $client->cellphone);
            if (!empty($address)) $update['address'] = $address;
            if (!empty($city))    $update['city']    = $city;
            if (!empty($dept))    $update['state']   = $dept;
            if (empty($client->idNum) && !empty($doc)) $update['idNum'] = $doc;
            $this->clients_model->update($clientId, $update);
        }

        // === Calcular envío gratis server-side (NO confiar en el cliente) ===
        // Regla: subtotal en módulos > $60.000.
        $modulesTotal = 0;
        foreach ($validItems as $it) {
            $code = strtoupper((string)$it['id']);
            $isModule = (bool) preg_match('/^(3LED|6LED|12LED|2835|JS-COB)-/', $code)
                     || (bool) preg_match('/^M[A-Z]{1,2}-(12|24)V$/', $code);
            if ($isModule) $modulesTotal += (int) $it['subtotal'];
        }
        $freeShipping = $modulesTotal > 60000;
        $shipNote = $freeShipping ? 'ENVÍO GRATIS (módulos > $60.000)' : 'Envío con costo';

        // === Crear presupuesto (mismo patrón que BotImport) ===
        $budget_data = array(
            'clientId'   => $clientId,
            'vendorId'   => $vendorId,
            'storeId'    => $storeId,
            'total'      => $total,
            'date'       => date('Y-m-d H:i:s'),
            'state'      => 0,
            'e_commerce' => 1,
            'list_price' => 0,
            'hasIva'     => 0,
            'iva'        => 8,
            'comments'   => $shipNote . ' | Pedido web (tienda pública). Tel: ' . $celular_norm . '. Dir: ' . $address . ($city ? ", $city" : '') . ($dept ? ", $dept" : '') . ($email ? ". Email: $email" : '') . '. Pago: contra entrega. Envío: Interrapidísimo. Vía: web.',
        );
        $this->budgets_model->save($budget_data);
        $budgetId = (int) $this->budgets_model->lastID();

        if (!$budgetId) {
            if ($gotLock) $this->db->query("SELECT RELEASE_LOCK(?)", array($lockKey));
            echo json_encode(array('ok' => false, 'error' => 'Error guardando el pedido'));
            return;
        }

        // === Detalle ===
        try {
            $sum = 0;
            $num = count($validItems);
            foreach ($validItems as $i => $it) {
                if ($i === $num - 1) {
                    $line_total = $total - $sum;
                    $line_unit  = ($it['qty'] > 0) ? round($line_total / $it['qty']) : $line_total;
                } else {
                    $line_unit  = (int) $it['price'];
                    $line_total = (int) $it['subtotal'];
                }
                $sum += $line_total;
                $this->budgets_model->save_detail(array(
                    'budgetId'  => $budgetId,
                    'productId' => $it['id'],
                    'quantity'  => $it['qty'],
                    'unit'      => $line_unit,
                    'base'      => $line_unit,
                    'total'     => $line_total,
                ));
            }
        } catch (Exception $e) {
            $this->db->where('budgetId', $budgetId)->delete('budget_detail');
            $this->budgets_model->save_detail(array(
                'budgetId'  => $budgetId,
                'productId' => 'PENDIENTE',
                'quantity'  => 1,
                'unit'      => $total,
                'base'      => $total,
                'total'     => $total,
            ));
        }

        if ($gotLock) $this->db->query("SELECT RELEASE_LOCK(?)", array($lockKey));

        // === Notificar al CLIENTE por WhatsApp (best-effort) ===
        $this->_notifyClient($budgetId, $name, $celular_norm, $address, $city, $dept, $validItems, $total, $freeShipping);

        if (ob_get_level() > 0) ob_clean();
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
     * Enviar mensaje de confirmación al CLIENTE vía BuilderBot API (best-effort).
     * El cliente recibe en su WhatsApp un resumen del pedido recién creado.
     */
    private function _notifyClient($budgetId, $name, $phone, $address, $city, $dept, $items, $total, $freeShipping = false) {
        try {
            $bot = $this->db->where('id', self::BOT_ID_LOCAL)->where('is_active', 1)->get('builderbot_configs')->row();
            if (!$bot) return;

            $this->load->library('builderbot_lib');

            // Formato WhatsApp Colombia: 57 + 10 dígitos
            $waPhone = preg_replace('/[^0-9]/', '', $phone);
            if (mb_strlen($waPhone) === 10) $waPhone = '57' . $waPhone;
            if (mb_strlen($waPhone) < 11) return;

            $firstName = explode(' ', trim($name))[0] ?: 'cliente';

            $lines = array();
            $lines[] = "¡Hola $firstName! 👋";
            $lines[] = "";
            $lines[] = "Recibimos tu pedido en *Ledxury*. ¡Gracias por tu compra! 🎉";
            $lines[] = "";
            $lines[] = "*Pedido #" . str_pad($budgetId, 6, '0', STR_PAD_LEFT) . "*";
            $lines[] = "📍 " . trim($address . ($city ? ', ' . $city : '') . ($dept ? ', ' . $dept : ''), ' ,');
            $lines[] = "";
            $lines[] = "*Productos:*";
            foreach ($items as $it) {
                $lines[] = "  • " . $it['qty'] . "x " . $it['name'];
            }
            $lines[] = "";
            $lines[] = "*Total: $" . number_format($total, 0, ',', '.') . "* (Pago contra entrega)";
            $lines[] = $freeShipping
                ? "🚚 *¡Envío GRATIS!* Tu pedido califica para envío sin costo a toda Colombia."
                : "🚚 Envío con Interrapidísimo a toda Colombia";
            $lines[] = "";
            $lines[] = "Te contactaremos en las próximas horas para confirmar la dirección y enviar el número de guía.";
            $lines[] = "";
            $lines[] = "Puedes consultar el estado en cualquier momento en " . rtrim(base_url(), '/') . "/tienda/mis-pedidos";

            $body = implode("\n", $lines);
            $result = $this->builderbot_lib->sendMessage($bot, $waPhone, $body);

            $this->load->model('logs_model');
            $http = isset($result['http_code']) ? $result['http_code'] : 0;
            $this->logs_model->logMessage('info', "Tienda → cliente notify budget #$budgetId tel=$waPhone HTTP $http");
        } catch (\Exception $e) {
            // Silencioso: no afectar el pedido si falla la notificación
        }
    }

    const OTP_TTL_MIN       = 10;   // Minutos de vida del código
    const OTP_SESSION_HOURS = 2;    // Sesión válida tras verificar
    const OTP_THROTTLE_MIN  = 15;   // Minutos para throttle (max 3 códigos / ventana)
    const OTP_THROTTLE_MAX  = 3;

    /**
     * Página de búsqueda de pedidos protegida por OTP (WhatsApp).
     * 3 estados:
     *   1) sin sesión y sin código pendiente → form ingresar celular
     *   2) sin sesión pero con código enviado → form ingresar OTP
     *   3) sesión válida (verified_phone en sesión) → muestra pedidos
     *
     * GET  /tienda/mis-pedidos                  → vista
     * POST /tienda/mis-pedidos (action=send)    → envía código por WhatsApp
     * POST /tienda/mis-pedidos (action=verify)  → valida código y crea sesión
     * GET  /tienda/mis-pedidos?logout=1         → cerrar sesión
     */
    public function misPedidos() {
        $action = (string) $this->input->post('action', true);

        // Logout
        if ($this->input->get('logout') === '1') {
            $this->session->unset_userdata('tienda_verified_phone');
            $this->session->unset_userdata('tienda_verified_at');
            $this->session->unset_userdata('tienda_otp_phone');
            redirect(base_url('tienda/mis-pedidos'));
            return;
        }

        // Estado 3: ya verificado y vigente
        $verifiedPhone = (string) $this->session->userdata('tienda_verified_phone');
        $verifiedAt    = (int) $this->session->userdata('tienda_verified_at');
        if ($verifiedPhone && (time() - $verifiedAt) < self::OTP_SESSION_HOURS * 3600) {
            $this->_renderPedidos($verifiedPhone);
            return;
        }

        // POST: enviar código
        if ($action === 'send') {
            // Rate limit por IP además del throttle por celular: máx 10 envíos / hora.
            // Evita atacante que enumere celulares ajenos para spamear OTPs.
            if (!$this->_rateLimit('otpSend', 10, 3600)) {
                $this->_renderOtpView(array('error' => 'Demasiados intentos desde este dispositivo. Intenta de nuevo en una hora.', 'phone' => $this->input->post('phone', true)));
                return;
            }
            $phone = $this->_normPhone($this->input->post('phone', true));
            if (strlen($phone) < 10) {
                $this->_renderOtpView(array('error' => 'Número inválido. Debe tener 10 dígitos.', 'phone' => $phone));
                return;
            }
            // Throttle por celular
            $recent = $this->db->where('phone', $phone)
                ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-' . self::OTP_THROTTLE_MIN . ' minutes')))
                ->count_all_results('tienda_otp_codes');
            if ($recent >= self::OTP_THROTTLE_MAX) {
                $this->_renderOtpView(array('error' => 'Has solicitado demasiados códigos. Espera ' . self::OTP_THROTTLE_MIN . ' minutos.', 'phone' => $phone));
                return;
            }
            // Generar y guardar
            $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $this->db->insert('tienda_otp_codes', array(
                'phone'      => $phone,
                'code'       => $code,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+' . self::OTP_TTL_MIN . ' minutes')),
                'ip'         => $this->input->ip_address(),
            ));
            // Enviar por WhatsApp
            $sent = $this->_sendOtpWhatsApp($phone, $code);
            // Guardar fase actual en sesión para mostrar form de OTP en GET
            $this->session->set_userdata('tienda_otp_phone', $phone);
            $this->_renderOtpView(array(
                'phase' => 'verify',
                'phone' => $phone,
                'info'  => $sent
                    ? 'Te enviamos un código de 6 dígitos a tu WhatsApp +57 ' . $phone . '. Escríbelo abajo.'
                    : 'Código generado. Si no recibes el WhatsApp en 1 minuto, vuelve a solicitarlo.',
            ));
            return;
        }

        // POST: verificar código
        if ($action === 'verify') {
            $phone = $this->_normPhone($this->input->post('phone', true));
            $code  = preg_replace('/[^0-9]/', '', (string) $this->input->post('code', true));
            if (strlen($phone) < 10 || strlen($code) !== 6) {
                $this->_renderOtpView(array('phase' => 'verify', 'phone' => $phone, 'error' => 'Código inválido.'));
                return;
            }
            $row = $this->db->where('phone', $phone)
                ->where('code', $code)
                ->where('used', 0)
                ->where('expires_at >=', date('Y-m-d H:i:s'))
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get('tienda_otp_codes')->row();
            if (!$row) {
                $this->_renderOtpView(array('phase' => 'verify', 'phone' => $phone, 'error' => 'Código incorrecto o vencido. Solicita uno nuevo.'));
                return;
            }
            // Marcar usado
            $this->db->where('id', $row->id)->update('tienda_otp_codes', array('used' => 1));
            // Crear sesión verificada
            $this->session->set_userdata('tienda_verified_phone', $phone);
            $this->session->set_userdata('tienda_verified_at', time());
            $this->session->unset_userdata('tienda_otp_phone');
            redirect(base_url('tienda/mis-pedidos'));
            return;
        }

        // GET sin sesión: estado 1 (form celular) o 2 (form OTP si hay phase pendiente)
        $pending = (string) $this->session->userdata('tienda_otp_phone');
        if ($pending !== '') {
            // Si llega ?phone=X y es distinto al pendiente, reset
            $maybe = $this->_normPhone($this->input->get('phone'));
            if ($maybe && $maybe !== $pending) {
                $this->session->unset_userdata('tienda_otp_phone');
                $this->_renderOtpView(array('phase' => 'phone', 'phone' => $maybe));
            } else {
                $this->_renderOtpView(array('phase' => 'verify', 'phone' => $pending));
            }
            return;
        }
        // Pre-poblar el celular si viene por GET (link desde página de éxito)
        $prefill = $this->_normPhone($this->input->get('phone'));
        $this->_renderOtpView(array('phase' => 'phone', 'phone' => $prefill));
    }

    private function _normPhone($raw) {
        $n = preg_replace('/[^0-9]/', '', (string) $raw);
        if (strlen($n) > 10 && strpos($n, '57') === 0) $n = substr($n, 2);
        return $n;
    }

    private function _sendOtpWhatsApp($phone, $code) {
        try {
            $bot = $this->db->where('id', self::BOT_ID_LOCAL)->where('is_active', 1)->get('builderbot_configs')->row();
            if (!$bot) return false;
            $this->load->library('builderbot_lib');
            $waPhone = strlen($phone) === 10 ? '57' . $phone : $phone;
            $msg = "🔐 *Tu código Ledxury*\n\n*$code*\n\nVence en " . self::OTP_TTL_MIN . " minutos. Si no fuiste tú, ignora este mensaje.";
            $r = $this->builderbot_lib->sendMessage($bot, $waPhone, $msg);
            return !empty($r['success']);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function _renderOtpView($vars) {
        $defaults = array('phase' => 'phone', 'phone' => '', 'error' => null, 'info' => null);
        $this->load->view('tienda/mis_pedidos', array_merge($defaults, $vars, array('results' => null)));
    }

    private function _renderPedidos($phone) {
        $this->load->model('clients_model');
        $client = $this->clients_model->getClientByPhone($phone);

        $orders = array();
        if ($client) {
            // Tracking real (preferir shipping_guides.estadoNombre que es lo que actualiza
            // el cron update_shipping_guides cada 30min; invoices.tracking_status es legacy).
            $orders = $this->db->select('b.idBudget, b.date, b.state, b.total, b.comments,
                    (SELECT i.idInvoice FROM invoices i WHERE i.budgetId = b.idBudget AND i.deleted = 0 LIMIT 1) AS invoice_id,
                    (SELECT sg.numeroPreenvio FROM shipping_guides sg
                       JOIN invoices i ON i.idInvoice = sg.invoiceId
                       WHERE i.budgetId = b.idBudget AND i.deleted = 0
                       ORDER BY sg.id DESC LIMIT 1) AS tracking_number,
                    (SELECT sg.estadoNombre FROM shipping_guides sg
                       JOIN invoices i ON i.idInvoice = sg.invoiceId
                       WHERE i.budgetId = b.idBudget AND i.deleted = 0
                       ORDER BY sg.id DESC LIMIT 1) AS tracking_status,
                    (SELECT sg.fechaEstado FROM shipping_guides sg
                       JOIN invoices i ON i.idInvoice = sg.invoiceId
                       WHERE i.budgetId = b.idBudget AND i.deleted = 0
                       ORDER BY sg.id DESC LIMIT 1) AS tracking_updated_at', false)
                ->from('budgets b')
                ->where('b.clientId', $client->idClient)
                ->where('b.deleted', 0)
                ->order_by('b.idBudget', 'DESC')
                ->limit(20)
                ->get()->result();

            // Detalle por presupuesto: cantidad + código + descripción + precio
            if (!empty($orders)) {
                $ids = array_map(function($o) { return (int) $o->idBudget; }, $orders);
                $details = $this->db->select('bd.budgetId, bd.quantity, bd.productId, bd.unit, bd.total AS line_total, p.description')
                    ->from('budget_detail bd')
                    ->join('products p', 'p.idProduct = bd.productId', 'left')
                    ->where_in('bd.budgetId', $ids)
                    ->get()->result();
                $byBudget = array();
                foreach ($details as $d) {
                    $bid = (int) $d->budgetId;
                    if (!isset($byBudget[$bid])) $byBudget[$bid] = array();
                    $byBudget[$bid][] = $d;
                }
                foreach ($orders as $o) {
                    $o->lines = isset($byBudget[(int)$o->idBudget]) ? $byBudget[(int)$o->idBudget] : array();
                }
            }
        }

        $this->load->view('tienda/mis_pedidos', array(
            'phase'   => 'orders',
            'phone'   => $phone,
            'results' => array('client' => $client, 'orders' => $orders),
            'error'   => null,
            'info'    => null,
        ));
    }
}
