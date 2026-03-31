<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Agents extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1]);
    }

    // ================================================================
    // AGENT 1: Cobros Automaticos
    // ================================================================

    public function collections()
    {
        $this->load->view('sisvent/admin/agents/collections');
    }

    /**
     * AJAX: Analiza facturas vencidas y genera mensajes de cobro con IA
     */
    public function runCollections()
    {
        header('Content-Type: application/json');

        $storeId = $this->input->get_post('storeId');

        // 1. Consultar facturas pendientes
        $storeFilter = '';
        if (!empty($storeId) && $storeId != '-1') {
            $storeFilter = ' AND i.storeId = ' . (int)$storeId;
        }

        $results = $this->db->query(
            "SELECT
                c.idClient,
                c.name AS client_name,
                c.phone,
                c.cellphone,
                c.city,
                COUNT(i.idInvoice) AS invoice_count,
                SUM(i.total - i.payment - i.discount) AS total_debt,
                MIN(i.date) AS oldest_invoice_date,
                DATEDIFF(CURDATE(), MIN(i.date)) AS oldest_days,
                GROUP_CONCAT(CONCAT(i.idInvoice, ':', DATE_FORMAT(i.date, '%d/%m/%Y'), ':', FORMAT(i.total - i.payment - i.discount, 0)) ORDER BY i.date SEPARATOR '|') AS invoice_details
             FROM invoices i
             INNER JOIN clients c ON c.idClient = i.clientId
             WHERE i.deleted = 0
               AND (i.state = 0 OR i.state = 1)
               AND (i.total - i.payment - i.discount) > 0
               {$storeFilter}
             GROUP BY c.idClient
             HAVING oldest_days >= 30
             ORDER BY total_debt DESC
             LIMIT 50"
        )->result();

        if (empty($results)) {
            echo json_encode(['success' => true, 'clients' => [], 'stats' => ['total' => 0, 'critica' => 0, 'alta' => 0, 'media' => 0, 'baja' => 0]]);
            return;
        }

        $clients = [];
        $stats = ['total' => 0, 'critica' => 0, 'alta' => 0, 'media' => 0, 'baja' => 0];

        foreach ($results as $row) {
            $urgency = 'BAJA';
            if ($row->total_debt > 10000000 || $row->oldest_days > 180) {
                $urgency = 'CRITICA';
            } elseif ($row->total_debt > 5000000 || $row->oldest_days > 90) {
                $urgency = 'ALTA';
            } elseif ($row->total_debt > 1000000 || $row->oldest_days > 60) {
                $urgency = 'MEDIA';
            }

            $stats['total']++;
            $stats[strtolower($urgency)]++;

            // Preparar datos del cliente
            $phone = !empty($row->cellphone) ? $row->cellphone : $row->phone;
            $phone = preg_replace('/[^0-9]/', '', $phone);

            $clients[] = [
                'id'             => $row->idClient,
                'name'           => $row->client_name,
                'phone'          => $phone,
                'city'           => $row->city,
                'total_debt'     => (float)$row->total_debt,
                'total_debt_fmt' => '$' . number_format($row->total_debt, 0, ',', '.'),
                'invoice_count'  => (int)$row->invoice_count,
                'oldest_days'    => (int)$row->oldest_days,
                'urgency'        => $urgency,
                'invoice_details'=> $row->invoice_details,
                'message'        => '',
                'whatsapp_link'  => ''
            ];
        }

        // 2. Generar mensajes con Claude en UNA sola llamada (lote)
        $api_key = $this->config->item('anthropic_api_key');
        if (!empty($api_key) && count($clients) > 0) {
            // Construir prompt con todos los clientes (max 20)
            $batch = array_slice($clients, 0, 20);
            $clientList = "";
            foreach ($batch as $i => $c) {
                $clientList .= ($i+1) . ". {$c['name']} | Deuda: {$c['total_debt_fmt']} COP | Facturas: {$c['invoice_count']} | Dias: {$c['oldest_days']} | Urgencia: {$c['urgency']}\n";
            }

            $prompt = "Genera mensajes de cobro para WhatsApp para estos clientes de MAM (Multi Accesorios Medellin - autopiezas). "
                . "Cada mensaje debe ser breve (max 250 caracteres), profesional, cordial, indicar monto y solicitar pago. "
                . "Maximo 1-2 emojis por mensaje. Responde SOLO con el formato:\n"
                . "1. [mensaje]\n2. [mensaje]\n...\n\n"
                . "CLIENTES:\n" . $clientList;

            $response = $this->_call_claude_simple($prompt, 2048);
            if ($response['success']) {
                // Parsear respuestas numeradas
                $lines = explode("\n", $response['response']);
                $msgIndex = 0;
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (preg_match('/^\d+[\.\)]\s*(.+)/', $line, $m)) {
                        if (isset($clients[$msgIndex])) {
                            $clients[$msgIndex]['message'] = trim($m[1]);
                            $encoded = urlencode($clients[$msgIndex]['message']);
                            $clients[$msgIndex]['whatsapp_link'] = "https://wa.me/57{$clients[$msgIndex]['phone']}?text={$encoded}";
                        }
                        $msgIndex++;
                    }
                }
            }

            // Rellenar los que no recibieron mensaje
            foreach ($clients as &$client) {
                if (empty($client['message'])) {
                    $client['message'] = "Estimado {$client['name']}, le recordamos su deuda pendiente de {$client['total_debt_fmt']} COP con MAM. Agradecemos su pronto pago.";
                    $encoded = urlencode($client['message']);
                    $client['whatsapp_link'] = "https://wa.me/57{$client['phone']}?text={$encoded}";
                }
            }
            unset($client);
        } else {
            // Sin API key, mensajes genericos
            foreach ($clients as &$client) {
                $client['message'] = "Estimado {$client['name']}, le recordamos su deuda pendiente de {$client['total_debt_fmt']} COP con MAM. Agradecemos su pronto pago.";
                $encoded = urlencode($client['message']);
                $client['whatsapp_link'] = "https://wa.me/57{$client['phone']}?text={$encoded}";
            }
            unset($client);
        }

        echo json_encode(['success' => true, 'clients' => $clients, 'stats' => $stats]);
    }

    /**
     * Genera enlace de WhatsApp con mensaje para un cliente
     */
    public function sendWhatsapp($clientId)
    {
        header('Content-Type: application/json');

        $client = $this->db->where('idClient', $clientId)->get('clients')->row();
        if (!$client) {
            echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
            return;
        }

        $debt = $this->db->select('SUM(total - payment - discount) as total_debt')
            ->where('deleted', 0)
            ->where("(state = 0 OR state = 1)")
            ->where('clientId', $clientId)
            ->where('(total - payment - discount) >', 0)
            ->get('invoices')->row();

        $phone = !empty($client->cellphone) ? $client->cellphone : $client->phone;
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $debt_fmt = '$' . number_format($debt->total_debt, 0, ',', '.');

        $message = "Estimado {$client->name}, le recordamos que tiene una deuda pendiente de {$debt_fmt} COP con MAM. Agradecemos su pronto pago.";
        $link = "https://wa.me/57{$phone}?text=" . urlencode($message);

        echo json_encode(['success' => true, 'link' => $link, 'message' => $message]);
    }

    // ================================================================
    // AGENT 2: Resumen Ejecutivo Diario
    // ================================================================

    public function dailySummary()
    {
        $this->load->view('sisvent/admin/agents/summary');
    }

    /**
     * AJAX: Genera resumen ejecutivo del dia con IA
     */
    public function generateSummary()
    {
        header('Content-Type: application/json');

        $date = $this->input->post('date');
        if (empty($date)) $date = date('Y-m-d');

        $storeId = $this->input->post('storeId');

        // 1. Ventas del dia
        $sales_query = $this->db->select('COALESCE(SUM(total),0) as total, COUNT(*) as count')
            ->where('deleted', 0)->where('DATE(date)', $date);
        if (!empty($storeId)) $sales_query->where('storeId', $storeId);
        $sales = $sales_query->get('invoices')->row();

        // 2. Cobros del dia
        $payments_query = $this->db->select('COALESCE(SUM(payment),0) as total, COUNT(*) as count')
            ->where('deleted', 0)->where('DATE(date)', $date);
        $payments = $payments_query->get('payments')->row();

        // 3. Presupuestos creados hoy
        $budgets_query = $this->db->select('COUNT(*) as count, COALESCE(SUM(total),0) as total')
            ->where('deleted', 0)->where('DATE(date)', $date);
        if (!empty($storeId)) $budgets_query->where('storeId', $storeId);
        $budgets = $budgets_query->get('budgets')->row();

        // 4. Top 5 vendedores del dia
        $top_vendors_q = "SELECT u.name, COUNT(i.idInvoice) as facturas, SUM(i.total) as total
             FROM invoices i INNER JOIN users u ON u.idUser = i.vendorId
             WHERE i.deleted = 0 AND i.date = ?";
        if (!empty($storeId)) $top_vendors_q .= " AND i.storeId = " . (int)$storeId;
        $top_vendors_q .= " GROUP BY i.vendorId ORDER BY total DESC LIMIT 5";
        $top_vendors = $this->db->query($top_vendors_q, [$date])->result();

        // 5. Alertas de inventario (stock <= 0)
        $stock_alerts_q = "SELECT COUNT(DISTINCT i.idProduct) as count
             FROM inventory i WHERE i.stock <= 0";
        if (!empty($storeId)) $stock_alerts_q .= " AND i.storeId = " . (int)$storeId;
        $stock_alerts = $this->db->query($stock_alerts_q)->row();

        // 6. Facturas vencidas
        $overdue = $this->db->query(
            "SELECT COUNT(*) as count, COALESCE(SUM(total - payment - discount),0) as total
             FROM invoices
             WHERE deleted = 0 AND (state = 0 OR state = 1) AND (total - payment - discount) > 0
               AND DATEDIFF(CURDATE(), date) > 30"
        )->row();

        // 7. Saldos cajas y bancos
        $cashboxes = $this->db->select('name, currentBalance as balance')
            ->where('deleted', 0)->get('cashboxes')->result();
        $bank_accounts = $this->db->select('bankName as name, currentBalance as balance')
            ->where('deleted', 0)->get('bank_accounts')->result();

        // 8. Gastos del dia
        $expenses_query = $this->db->select('COALESCE(SUM(amount),0) as total, COUNT(*) as count')
            ->where('deleted', 0)->where('expense_date', $date);
        if (!empty($storeId)) $expenses_query->where('store_id', $storeId);
        $expenses = $expenses_query->get('expense_records')->row();

        // Construir datos para Claude
        $data_text = "DATOS DEL DIA: {$date}\n";
        $data_text .= "- Ventas: {$sales->count} facturas por $" . number_format($sales->total, 0, ',', '.') . " COP\n";
        $data_text .= "- Cobros: {$payments->count} pagos por $" . number_format($payments->total, 0, ',', '.') . " COP\n";
        $data_text .= "- Presupuestos: {$budgets->count} nuevos por $" . number_format($budgets->total, 0, ',', '.') . " COP\n";
        $data_text .= "- Gastos operativos: {$expenses->count} registros por $" . number_format($expenses->total, 0, ',', '.') . " COP\n";

        if (!empty($top_vendors)) {
            $data_text .= "\nTOP VENDEDORES DEL DIA:\n";
            foreach ($top_vendors as $v) {
                $data_text .= "- {$v->name}: {$v->facturas} facturas, $" . number_format($v->total, 0, ',', '.') . " COP\n";
            }
        }

        $data_text .= "\nALERTAS:\n";
        $data_text .= "- Productos agotados (stock<=0): {$stock_alerts->count}\n";
        $data_text .= "- Facturas vencidas (>30 dias): {$overdue->count} por $" . number_format($overdue->total, 0, ',', '.') . " COP\n";

        $data_text .= "\nSALDOS CAJAS:\n";
        foreach ($cashboxes as $c) {
            $data_text .= "- {$c->name}: $" . number_format($c->balance, 0, ',', '.') . " COP\n";
        }
        $data_text .= "\nSALDOS BANCOS:\n";
        foreach ($bank_accounts as $b) {
            $data_text .= "- {$b->name}: $" . number_format($b->balance, 0, ',', '.') . " COP\n";
        }

        $system = "Eres el analista financiero de MAM (Multi Accesorios Medellin), empresa de autopartes en Colombia. "
            . "Genera un resumen ejecutivo conciso para el gerente. "
            . "Usa formato con secciones claras, bullets y resalta lo mas importante. "
            . "Incluye: Metricas clave, Preocupaciones, Acciones recomendadas. "
            . "Moneda: COP (pesos colombianos). Responde en espanol.";

        $prompt = "Con base en los siguientes datos, genera el resumen ejecutivo del dia:\n\n" . $data_text;

        $response = $this->_call_claude_simple($prompt, $system, 1500);

        echo json_encode($response);
    }

    // ================================================================
    // AGENT 4: WhatsApp Integration
    // ================================================================

    public function whatsapp()
    {
        $this->load->view('sisvent/admin/agents/whatsapp');
    }

    /**
     * AJAX: Genera mensaje contextual de WhatsApp para un cliente
     */
    public function generateClientMessage()
    {
        header('Content-Type: application/json');

        $clientId = $this->input->post('clientId');
        $type = $this->input->post('type'); // COBRO, SEGUIMIENTO, AGRADECIMIENTO, PROMOCION

        $client = $this->db->where('idClient', $clientId)->where('deleted', 0)->get('clients')->row();
        if (!$client) {
            echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
            return;
        }

        $phone = !empty($client->cellphone) ? $client->cellphone : $client->phone;
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $prompt = '';
        $context_data = '';

        switch ($type) {
            case 'COBRO':
                $invoices = $this->db->query(
                    "SELECT idInvoice, date, total, payment, discount,
                            (total - payment - discount) as saldo,
                            DATEDIFF(CURDATE(), date) as dias
                     FROM invoices
                     WHERE deleted = 0 AND clientId = ? AND (state = 0 OR state = 1) AND (total - payment - discount) > 0
                     ORDER BY date ASC LIMIT 10", [$clientId]
                )->result();

                $total_debt = 0;
                $invoice_list = '';
                foreach ($invoices as $inv) {
                    $total_debt += $inv->saldo;
                    $invoice_list .= "Factura #{$inv->idInvoice} ({$inv->date}): $" . number_format($inv->saldo, 0, ',', '.') . " - {$inv->dias} dias\n";
                }

                $prompt = "Genera un mensaje de cobro para WhatsApp. Cliente: {$client->name}. "
                    . "Deuda total: $" . number_format($total_debt, 0, ',', '.') . " COP. "
                    . "Detalle facturas:\n{$invoice_list}"
                    . "Mensaje breve, profesional, cordial. Max 400 caracteres. Empresa: MAM.";
                break;

            case 'SEGUIMIENTO':
                $budget = $this->db->where('clientId', $clientId)->where('deleted', 0)
                    ->order_by('date', 'DESC')->limit(1)->get('budgets')->row();

                if ($budget) {
                    $prompt = "Genera un mensaje de seguimiento post-cotizacion para WhatsApp. "
                        . "Cliente: {$client->name}. "
                        . "Ultimo presupuesto #{$budget->idBudget} del {$budget->date} por $" . number_format($budget->total, 0, ',', '.') . " COP. "
                        . "Preguntar si tiene dudas o desea proceder con el pedido. "
                        . "Mensaje breve, amigable. Max 300 caracteres. Empresa: MAM.";
                } else {
                    $prompt = "Genera un mensaje de seguimiento comercial para WhatsApp. "
                        . "Cliente: {$client->name}. No tiene cotizaciones recientes. "
                        . "Preguntar si necesita algo, ofrecer asistencia. "
                        . "Mensaje breve, amigable. Max 300 caracteres. Empresa: MAM.";
                }
                break;

            case 'AGRADECIMIENTO':
                $last_payment = $this->db->where('clientId', $clientId)->where('deleted', 0)
                    ->order_by('date', 'DESC')->limit(1)->get('payments')->row();

                $amount = $last_payment ? '$' . number_format($last_payment->payment, 0, ',', '.') : '';
                $prompt = "Genera un mensaje de agradecimiento por pago recibido para WhatsApp. "
                    . "Cliente: {$client->name}. "
                    . ($amount ? "Monto pagado: {$amount} COP. " : "")
                    . "Agradecer el pago, reforzar la relacion comercial. "
                    . "Mensaje breve, calido. Max 300 caracteres. Empresa: MAM.";
                break;

            case 'PROMOCION':
                // Buscar historial de compras del cliente
                $history = $this->db->query(
                    "SELECT p.description, SUM(bd.quantity) as qty
                     FROM budget_detail bd
                     INNER JOIN budgets b ON b.idBudget = bd.budgetId
                     INNER JOIN products p ON p.idProduct = bd.productId
                     WHERE b.clientId = ? AND b.deleted = 0
                     GROUP BY bd.productId ORDER BY qty DESC LIMIT 5", [$clientId]
                )->result();

                $products_text = '';
                foreach ($history as $h) {
                    $products_text .= "- {$h->description} (ha comprado {$h->qty} unidades)\n";
                }

                $prompt = "Genera un mensaje promocional para WhatsApp. "
                    . "Cliente: {$client->name}. "
                    . ($products_text ? "Productos que suele comprar:\n{$products_text}" : "Cliente sin historial especifico. ")
                    . "Crear un mensaje que ofrezca productos relacionados o novedades. "
                    . "Mensaje breve, atractivo. Max 350 caracteres. Empresa: MAM (autopartes).";
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Tipo de mensaje invalido']);
                return;
        }

        $response = $this->_call_claude_simple($prompt);

        if ($response['success']) {
            $message = $response['response'];
            $link = "https://wa.me/57{$phone}?text=" . urlencode($message);
            echo json_encode([
                'success' => true,
                'message' => $message,
                'whatsapp_link' => $link,
                'client_name' => $client->name,
                'phone' => $phone
            ]);
        } else {
            echo json_encode($response);
        }
    }

    /**
     * AJAX: Genera mensajes masivos para multiples clientes
     */
    public function bulkMessages()
    {
        header('Content-Type: application/json');

        $clientIds = $this->input->post('clientIds');
        $type = $this->input->post('type');

        if (empty($clientIds) || !is_array($clientIds)) {
            echo json_encode(['success' => false, 'error' => 'Seleccione al menos un cliente']);
            return;
        }

        $results = [];
        foreach ($clientIds as $cid) {
            // Reutilizar la logica de generateClientMessage via POST interno
            $_POST['clientId'] = $cid;
            $_POST['type'] = $type;

            ob_start();
            $this->generateClientMessage();
            $output = ob_get_clean();

            $data = json_decode($output, true);
            if ($data && $data['success']) {
                $results[] = $data;
            }
        }

        echo json_encode(['success' => true, 'messages' => $results]);
    }

    /**
     * AJAX: Buscar clientes para WhatsApp
     */
    public function searchClients()
    {
        header('Content-Type: application/json');

        $term = $this->input->post('term');
        if (empty($term) || strlen($term) < 2) {
            echo json_encode([]);
            return;
        }

        $clients = $this->db->select('idClient, name, phone, cellphone, city')
            ->like('name', $term)
            ->or_like('phone', $term)
            ->or_like('cellphone', $term)
            ->where('deleted', 0)
            ->limit(15)
            ->get('clients')->result();

        echo json_encode($clients);
    }

    // ================================================================
    // UTILIDADES PRIVADAS
    // ================================================================

    /**
     * Llamada simple a Claude Haiku
     */
    private function _call_claude_simple($prompt, $system = '', $max_tokens = 512)
    {
        $api_key = $this->config->item('anthropic_api_key');
        if (empty($api_key)) {
            return ['success' => false, 'error' => 'API key de Anthropic no configurada'];
        }

        if (empty($system)) {
            $system = 'Eres un asistente de negocios profesional para MAM (Multi Accesorios Medellin), empresa de autopartes en Colombia. Respondes siempre en espanol. Eres conciso y profesional.';
        }

        $data = [
            'model' => 'claude-haiku-4-5-20251001',
            'max_tokens' => $max_tokens,
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $api_key,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return ['success' => false, 'error' => 'Error de conexion: ' . $curl_error];
        }

        $result = json_decode($response, true);

        if ($http_code !== 200) {
            $error_msg = isset($result['error']['message']) ? $result['error']['message'] : 'Error HTTP ' . $http_code;
            return ['success' => false, 'error' => $error_msg];
        }

        $text = isset($result['content'][0]['text']) ? $result['content'][0]['text'] : '';
        return ['success' => true, 'response' => $text];
    }
}
