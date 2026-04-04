<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Aiassistant extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('asistente_ia');
    }

    public function index()
    {
        $this->load->view('sisvent/admin/aiassistant/index');
    }

    // ─── AJAX: Conversation Management ───────────────────────────

    /**
     * Returns user's conversations (last 30)
     */
    public function getConversations()
    {
        header('Content-Type: application/json');
        $userId = $this->session->userdata('user_data')['uname'];

        $conversations = $this->db
            ->where('userId', $userId)
            ->order_by('updated_at', 'DESC')
            ->limit(30)
            ->get('ai_conversations')
            ->result_array();

        echo json_encode(['success' => true, 'conversations' => $conversations]);
    }

    /**
     * Returns messages for a conversation
     */
    public function getMessages($conversationId = null)
    {
        header('Content-Type: application/json');

        if (!$conversationId) {
            $conversationId = $this->input->get('conversationId');
        }

        if (empty($conversationId)) {
            echo json_encode(['success' => false, 'error' => 'conversationId requerido']);
            return;
        }

        $userId = $this->session->userdata('user_data')['uname'];

        // Verify ownership
        $conv = $this->db->where('id', (int)$conversationId)
                         ->where('userId', $userId)
                         ->get('ai_conversations')
                         ->row_array();

        if (!$conv) {
            echo json_encode(['success' => false, 'error' => 'Conversacion no encontrada']);
            return;
        }

        $messages = $this->db
            ->where('conversationId', (int)$conversationId)
            ->order_by('created_at', 'ASC')
            ->get('ai_messages')
            ->result_array();

        echo json_encode(['success' => true, 'conversation' => $conv, 'messages' => $messages]);
    }

    /**
     * Creates a new conversation and returns its ID
     */
    public function newConversation()
    {
        header('Content-Type: application/json');
        $userId = $this->session->userdata('user_data')['uname'];

        $this->db->insert('ai_conversations', [
            'userId' => $userId,
            'title'  => 'Nueva conversacion'
        ]);

        $id = $this->db->insert_id();
        echo json_encode(['success' => true, 'conversationId' => $id]);
    }

    /**
     * Deletes a conversation and its messages
     */
    public function deleteConversation($id = null)
    {
        header('Content-Type: application/json');

        if (!$id) {
            $id = $this->input->post('id');
        }

        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'ID requerido']);
            return;
        }

        $userId = $this->session->userdata('user_data')['uname'];

        // Verify ownership
        $conv = $this->db->where('id', (int)$id)
                         ->where('userId', $userId)
                         ->get('ai_conversations')
                         ->row_array();

        if (!$conv) {
            echo json_encode(['success' => false, 'error' => 'Conversacion no encontrada']);
            return;
        }

        $this->db->where('conversationId', (int)$id)->delete('ai_messages');
        $this->db->where('id', (int)$id)->delete('ai_conversations');

        echo json_encode(['success' => true]);
    }

    // ─── AJAX: Ask with conversation persistence ─────────────────

    /**
     * Temporal test - borrar despues
     */
    public function test()
    {
        header('Content-Type: application/json');
        try {
            $api_key = $this->config->item('anthropic_api_key');
            $context = $this->_build_system_context('hola');
            echo json_encode(['success' => true, 'key_len' => strlen($api_key), 'context_len' => strlen($context), 'msg' => 'OK']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }

    /**
     * AJAX endpoint: recibe pregunta, consulta datos del sistema,
     * envia contexto + pregunta a Claude API con tool_use, retorna respuesta.
     * Ahora persiste mensajes en ai_conversations / ai_messages.
     */
    public function ask()
    {
        header('Content-Type: application/json');

        try {
            $question       = $this->input->post('question');
            $conversationId = $this->input->post('conversationId');
            $userId         = $this->session->userdata('user_data')['uname'];

            if (empty($question)) {
                echo json_encode(['success' => false, 'error' => 'Pregunta vacia']);
                return;
            }

            // If no conversationId, create a new conversation
            if (empty($conversationId)) {
                $title = mb_substr(trim($question), 0, 50);
                $this->db->insert('ai_conversations', [
                    'userId' => $userId,
                    'title'  => $title
                ]);
                $conversationId = $this->db->insert_id();
            }

            // Verify ownership
            $conv = $this->db->where('id', (int)$conversationId)
                             ->where('userId', $userId)
                             ->get('ai_conversations')
                             ->row_array();

            if (!$conv) {
                echo json_encode(['success' => false, 'error' => 'Conversacion no encontrada']);
                return;
            }

            // Save user message
            $this->db->insert('ai_messages', [
                'conversationId' => (int)$conversationId,
                'role'           => 'user',
                'content'        => $question
            ]);

            // Auto-generate title from first message if still default
            if ($conv['title'] === 'Nueva conversacion') {
                $title = mb_substr(trim($question), 0, 50);
                $this->db->where('id', (int)$conversationId)
                         ->update('ai_conversations', ['title' => $title]);
            }

            // Touch updated_at
            $this->db->where('id', (int)$conversationId)
                     ->update('ai_conversations', ['updated_at' => date('Y-m-d H:i:s')]);

            // Recopilar contexto del sistema
            $context = $this->_build_system_context($question);

            // Intentar Claude primero, si no hay key o falla, usar Gemini
            $claude_key = $this->config->item('anthropic_api_key');
            if (!empty($claude_key)) {
                $response = $this->_call_claude($context, $question);
            } else {
                $response = $this->_call_gemini($context, $question);
            }

            // Save assistant response
            if ($response['success'] && !empty($response['response'])) {
                $this->db->insert('ai_messages', [
                    'conversationId' => (int)$conversationId,
                    'role'           => 'assistant',
                    'content'        => $response['response']
                ]);
            }

            // Add conversationId to response
            $response['conversationId'] = (int)$conversationId;

            echo json_encode($response);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => 'Error PHP: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine()]);
        }
    }

    /**
     * AJAX endpoint: ejecuta accion sugerida por la IA
     */
    public function executeAction()
    {
        header('Content-Type: application/json');

        $action = $this->input->post('action');
        $params = $this->input->post('params');

        if (empty($action)) {
            echo json_encode(['success' => false, 'error' => 'Accion no especificada']);
            return;
        }

        // Solo permitir acciones de lectura
        switch ($action) {
            case 'query_database':
                $result = $this->_execute_safe_query($params['sql'] ?? '');
                echo json_encode($result);
                break;
            case 'get_client_info':
                $result = $this->_get_client_info($params['identifier'] ?? '');
                echo json_encode($result);
                break;
            case 'get_vendor_sales':
                $result = $this->_get_vendor_sales($params['period'] ?? 'month', $params['vendorId'] ?? null);
                echo json_encode($result);
                break;
            case 'get_inventory_status':
                $result = $this->_get_inventory_status($params['productId'] ?? null, $params['storeId'] ?? null);
                echo json_encode($result);
                break;
            case 'get_financial_summary':
                $result = $this->_get_financial_summary($params['period'] ?? 'month');
                echo json_encode($result);
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Accion no reconocida']);
        }
    }

    /**
     * Construye contexto con datos relevantes del sistema
     */
    private function _build_system_context($question)
    {
        $context = "Eres el asistente de IA del ERP MAM (Multi Accesorios Medellin). ";
        $context .= "Respondes en espanol. Eres conciso y util. ";
        $context .= "El sistema maneja ventas, inventario, contabilidad, clientes y proveedores.\n\n";
        $context .= "Tienes acceso a herramientas para consultar la base de datos en tiempo real. ";
        $context .= "Usa las herramientas cuando necesites datos especificos para responder la pregunta del usuario.\n\n";
        $context .= "=== ESQUEMA DE TABLAS ===\n";
        $context .= "invoices: idInvoice, clientId, vendorId, storeId, date, total, payment, discount, state(0=pendiente,1=parcial,2=pagada), deleted\n";
        $context .= "clients: idClient, name, phone, cellphone, email, city, nit, deleted\n";
        $context .= "products: idProduct, description, price, price_base, price_scale, price_dist, deleted\n";
        $context .= "inventory: idInventory, idProduct, storeId, stock\n";
        $context .= "payments: idPayment, invoiceId, clientId, storeId, amount, date, deleted\n";
        $context .= "users: idUser, name, role, user_status, deleted\n";
        $context .= "stores: idStore, name, deleted\n";
        $context .= "budgets: idBudget, clientId, vendorId, storeId, date, total, state, deleted\n";
        $context .= "expense_records: idExpenseRecord, categoryId, storeId, description, amount, date, status, deleted\n";
        $context .= "expense_categories: idExpenseCategory, name, deleted\n";
        $context .= "cashboxes: idCashbox, name, storeId, balance, deleted\n";
        $context .= "bank_accounts: idBankAccount, name, balance, deleted\n";
        $context .= "providers: idProvider, name, deleted\n";
        $context .= "supplier_invoices: idSupplierInvoice, providerId, storeId, date, total, deleted\n";
        $context .= "budget_details: idBudgetDetail, budgetId, productId, quantity, rate, subtotal\n\n";

        $context .= "=== DATOS GENERALES ===\n";
        $clients = $this->db->where('deleted', 0)->count_all_results('clients');
        $products = $this->db->where('deleted', 0)->count_all_results('products');
        $context .= "Clientes: $clients | Productos: $products\n";
        $context .= "Fecha actual: " . date('Y-m-d') . "\n";
        $context .= "Moneda: COP (pesos colombianos). Formato: \$XXX.XXX.XXX\n";

        return $context;
    }

    /**
     * Definicion de herramientas para Claude tool_use
     */
    private function _get_tools()
    {
        return [
            [
                'name' => 'query_database',
                'description' => 'Execute a read-only SQL query against the ERP database. Only SELECT queries are allowed. Use this to answer questions about sales, clients, inventory, etc. Always use table/column names from the schema provided in the system prompt.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'sql' => [
                            'type' => 'string',
                            'description' => 'SQL SELECT query to execute. Tables: invoices, clients, products, inventory, payments, users, stores, expense_records, expense_categories, supplier_invoices, budgets, budget_details, cashboxes, bank_accounts, providers. NEVER use DELETE/UPDATE/INSERT/DROP/ALTER.'
                        ]
                    ],
                    'required' => ['sql']
                ]
            ],
            [
                'name' => 'get_client_info',
                'description' => 'Get detailed client info by name or ID, including their debt, recent invoices, and payment history.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'identifier' => [
                            'type' => 'string',
                            'description' => 'Client name (partial match) or numeric ID'
                        ]
                    ],
                    'required' => ['identifier']
                ]
            ],
            [
                'name' => 'get_vendor_sales',
                'description' => 'Get vendor/salesperson sales data for a given period.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => [
                            'type' => 'string',
                            'description' => 'Period: "today", "week", "month", "year" or "YYYY-MM-DD:YYYY-MM-DD" range'
                        ],
                        'vendorId' => [
                            'type' => 'string',
                            'description' => 'Optional vendor ID to filter. Omit for all vendors.'
                        ]
                    ],
                    'required' => ['period']
                ]
            ],
            [
                'name' => 'get_inventory_status',
                'description' => 'Check stock levels for products. Can filter by product ID or store, or show critical stock items.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'productId' => [
                            'type' => 'string',
                            'description' => 'Optional product ID to check specific product'
                        ],
                        'storeId' => [
                            'type' => 'string',
                            'description' => 'Optional store ID to filter by location'
                        ]
                    ],
                    'required' => []
                ]
            ],
            [
                'name' => 'get_financial_summary',
                'description' => 'Get cash flow and financial summary data: cash balances, bank balances, revenue, expenses.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => [
                            'type' => 'string',
                            'description' => 'Period: "today", "week", "month", "year"'
                        ]
                    ],
                    'required' => ['period']
                ]
            ]
        ];
    }

    /**
     * Llama a la API de Claude con soporte tool_use
     */
    private function _call_claude($system_context, $question)
    {
        $api_key = $this->config->item('anthropic_api_key');

        if (empty($api_key)) {
            return [
                'success' => false,
                'error' => 'API key de Anthropic no configurada. Configurala en application/config/config.php (anthropic_api_key)'
            ];
        }

        $messages = [
            ['role' => 'user', 'content' => $question]
        ];

        $tools = $this->_get_tools();

        // Loop for multi-turn tool_use (max 5 rounds)
        for ($round = 0; $round < 5; $round++) {
            $data = [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 2048,
                'system' => $system_context,
                'messages' => $messages,
                'tools' => $tools
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
                CURLOPT_TIMEOUT => 60
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

            // Check if Claude wants to use tools
            $stop_reason = $result['stop_reason'] ?? '';

            if ($stop_reason === 'tool_use') {
                // Add assistant message to conversation
                $messages[] = ['role' => 'assistant', 'content' => $result['content']];

                // Process each tool use block
                $tool_results = [];
                foreach ($result['content'] as $block) {
                    if ($block['type'] === 'tool_use') {
                        $tool_name = $block['name'];
                        $tool_input = $block['input'];
                        $tool_id = $block['id'];

                        // Execute the tool
                        $tool_result = $this->_execute_tool($tool_name, $tool_input);

                        $tool_results[] = [
                            'type' => 'tool_result',
                            'tool_use_id' => $tool_id,
                            'content' => json_encode($tool_result, JSON_UNESCAPED_UNICODE)
                        ];
                    }
                }

                // Add tool results to conversation
                $messages[] = ['role' => 'user', 'content' => $tool_results];

            } else {
                // Claude gave a final text response
                $text = '';
                foreach ($result['content'] as $block) {
                    if ($block['type'] === 'text') {
                        $text .= $block['text'];
                    }
                }
                return ['success' => true, 'response' => $text];
            }
        }

        return ['success' => false, 'error' => 'Se excedio el limite de iteraciones de herramientas'];
    }

    /**
     * Llama a Gemini API con function calling
     */
    private function _call_gemini($system_context, $question)
    {
        $secretsFile = APPPATH . 'config/secrets.php';
        if (file_exists($secretsFile)) {
            include($secretsFile);
        }
        $api_key = isset($config['gemini_api_key']) ? $config['gemini_api_key'] : '';

        if (empty($api_key)) {
            return ['success' => false, 'error' => 'API key de Gemini no configurada en secrets.php'];
        }

        // Convertir tools al formato de Gemini (function declarations)
        $claude_tools = $this->_get_tools();
        $gemini_tools = [];
        foreach ($claude_tools as $t) {
            $gemini_tools[] = [
                'name' => $t['name'],
                'description' => $t['description'],
                'parameters' => $t['input_schema'],
            ];
        }

        $contents = [
            ['role' => 'user', 'parts' => [['text' => $question]]]
        ];

        // Loop para function calling (max 5 rounds)
        for ($round = 0; $round < 5; $round++) {
            $data = [
                'system_instruction' => ['parts' => [['text' => $system_context]]],
                'contents' => $contents,
                'tools' => [['function_declarations' => $gemini_tools]],
                'generationConfig' => ['maxOutputTokens' => 2048, 'temperature' => 0.3],
            ];

            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $api_key;

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => false,
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

            if (empty($result['candidates'][0]['content']['parts'])) {
                return ['success' => false, 'error' => 'Gemini no devolvio respuesta'];
            }

            $parts = $result['candidates'][0]['content']['parts'];

            // Verificar si Gemini quiere llamar funciones
            $has_function_call = false;
            $function_responses = [];

            foreach ($parts as $part) {
                if (isset($part['functionCall'])) {
                    $has_function_call = true;
                    $fn_name = $part['functionCall']['name'];
                    $fn_args = $part['functionCall']['args'] ?? [];

                    // Ejecutar la tool
                    $tool_result = $this->_execute_tool($fn_name, $fn_args);

                    $function_responses[] = [
                        'functionResponse' => [
                            'name' => $fn_name,
                            'response' => ['result' => $tool_result],
                        ]
                    ];
                }
            }

            if ($has_function_call) {
                // Agregar respuesta del modelo y resultados de funciones
                $contents[] = ['role' => 'model', 'parts' => $parts];
                $contents[] = ['role' => 'function', 'parts' => $function_responses];
            } else {
                // Respuesta final de texto
                $text = '';
                foreach ($parts as $part) {
                    if (isset($part['text'])) {
                        $text .= $part['text'];
                    }
                }
                return ['success' => true, 'response' => $text];
            }
        }

        return ['success' => false, 'error' => 'Se excedio el limite de iteraciones de funciones'];
    }

    /**
     * Ejecuta una herramienta y retorna el resultado
     */
    private function _execute_tool($tool_name, $input)
    {
        switch ($tool_name) {
            case 'query_database':
                return $this->_execute_safe_query($input['sql'] ?? '');
            case 'get_client_info':
                return $this->_get_client_info($input['identifier'] ?? '');
            case 'get_vendor_sales':
                return $this->_get_vendor_sales($input['period'] ?? 'month', $input['vendorId'] ?? null);
            case 'get_inventory_status':
                return $this->_get_inventory_status($input['productId'] ?? null, $input['storeId'] ?? null);
            case 'get_financial_summary':
                return $this->_get_financial_summary($input['period'] ?? 'month');
            default:
                return ['error' => 'Herramienta no reconocida: ' . $tool_name];
        }
    }

    /**
     * Ejecuta una consulta SQL segura (solo SELECT)
     */
    private function _execute_safe_query($sql)
    {
        $sql = trim($sql);

        // Validar que sea SELECT
        if (!preg_match('/^SELECT\s/i', $sql)) {
            return ['error' => 'Solo se permiten consultas SELECT'];
        }

        // Rechazar palabras peligrosas
        $forbidden = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE', 'CREATE', 'GRANT', 'REVOKE', 'EXEC', 'EXECUTE', 'CALL', 'INTO OUTFILE', 'INTO DUMPFILE', 'LOAD_FILE'];
        foreach ($forbidden as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $sql)) {
                return ['error' => "Operacion no permitida: {$word}"];
            }
        }

        // Agregar LIMIT si no existe
        if (!preg_match('/\bLIMIT\s+\d/i', $sql)) {
            $sql = rtrim($sql, '; ') . ' LIMIT 50';
        }

        try {
            $result = $this->db->query($sql);
            if ($result === false) {
                return ['error' => 'Error ejecutando la consulta: ' . $this->db->error()['message']];
            }
            $rows = $result->result_array();
            return ['success' => true, 'data' => $rows, 'count' => count($rows)];
        } catch (Exception $e) {
            return ['error' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene informacion detallada de un cliente
     */
    private function _get_client_info($identifier)
    {
        if (is_numeric($identifier)) {
            $client = $this->db->where('idClient', (int)$identifier)->where('deleted', 0)->get('clients')->row_array();
        } else {
            $client = $this->db->like('name', $identifier)->where('deleted', 0)->get('clients')->row_array();
        }

        if (!$client) {
            return ['error' => 'Cliente no encontrado: ' . $identifier];
        }

        // Deuda
        $debt = $this->db->query(
            "SELECT COUNT(*) as invoice_count, COALESCE(SUM(total - payment - discount),0) as total_debt
             FROM invoices WHERE deleted = 0 AND clientId = ? AND (state = 0 OR state = 1) AND (total - payment - discount) > 0",
            [$client['idClient']]
        )->row_array();

        // Ultimas facturas
        $recent_invoices = $this->db->query(
            "SELECT idInvoice, date, total, payment, discount, state
             FROM invoices WHERE deleted = 0 AND clientId = ?
             ORDER BY date DESC LIMIT 5",
            [$client['idClient']]
        )->result_array();

        // Ultimos pagos
        $recent_payments = $this->db->query(
            "SELECT idPayment, amount, date
             FROM payments WHERE deleted = 0 AND clientId = ?
             ORDER BY date DESC LIMIT 5",
            [$client['idClient']]
        )->result_array();

        return [
            'success' => true,
            'client' => $client,
            'debt' => $debt,
            'recent_invoices' => $recent_invoices,
            'recent_payments' => $recent_payments
        ];
    }

    /**
     * Obtiene ventas por vendedor en un periodo
     */
    private function _get_vendor_sales($period, $vendorId = null)
    {
        list($date_from, $date_to) = $this->_parse_period($period);

        $sql = "SELECT u.idUser, u.name,
                       COUNT(i.idInvoice) as invoice_count,
                       COALESCE(SUM(i.total),0) as total_sales,
                       COALESCE(SUM(i.payment),0) as total_collected
                FROM invoices i
                INNER JOIN users u ON u.idUser = i.vendorId
                WHERE i.deleted = 0 AND i.date BETWEEN ? AND ?";

        $params = [$date_from, $date_to];

        if ($vendorId) {
            $sql .= " AND i.vendorId = ?";
            $params[] = (int)$vendorId;
        }

        $sql .= " GROUP BY i.vendorId ORDER BY total_sales DESC LIMIT 20";

        return [
            'success' => true,
            'period' => ['from' => $date_from, 'to' => $date_to],
            'vendors' => $this->db->query($sql, $params)->result_array()
        ];
    }

    /**
     * Obtiene estado de inventario
     */
    private function _get_inventory_status($productId = null, $storeId = null)
    {
        if ($productId) {
            $sql = "SELECT i.idProduct, p.description, s.name as store_name, i.stock
                    FROM inventory i
                    INNER JOIN products p ON p.idProduct = i.idProduct
                    INNER JOIN stores s ON s.idStore = i.storeId
                    WHERE i.idProduct = ?";
            $params = [(int)$productId];
            if ($storeId) {
                $sql .= " AND i.storeId = ?";
                $params[] = (int)$storeId;
            }
            $rows = $this->db->query($sql, $params)->result_array();
        } else {
            // Mostrar productos criticos (stock <= 0)
            $sql = "SELECT i.idProduct, p.description, s.name as store_name, i.stock
                    FROM inventory i
                    INNER JOIN products p ON p.idProduct = i.idProduct
                    INNER JOIN stores s ON s.idStore = i.storeId
                    WHERE i.stock <= 0";
            if ($storeId) {
                $sql .= " AND i.storeId = " . (int)$storeId;
            }
            $sql .= " ORDER BY i.stock ASC LIMIT 30";
            $rows = $this->db->query($sql)->result_array();
        }

        $total_critical = $this->db->where('stock <=', 0)->count_all_results('inventory');

        return [
            'success' => true,
            'items' => $rows,
            'total_critical' => $total_critical
        ];
    }

    /**
     * Obtiene resumen financiero
     */
    private function _get_financial_summary($period)
    {
        list($date_from, $date_to) = $this->_parse_period($period);

        // Ventas
        $sales = $this->db->query(
            "SELECT COUNT(*) as count, COALESCE(SUM(total),0) as total
             FROM invoices WHERE deleted = 0 AND date BETWEEN ? AND ?",
            [$date_from, $date_to]
        )->row_array();

        // Cobros
        $payments = $this->db->query(
            "SELECT COUNT(*) as count, COALESCE(SUM(amount),0) as total
             FROM payments WHERE deleted = 0 AND date BETWEEN ? AND ?",
            [$date_from, $date_to]
        )->row_array();

        // Gastos
        $expenses = $this->db->query(
            "SELECT COUNT(*) as count, COALESCE(SUM(amount),0) as total
             FROM expense_records WHERE deleted = 0 AND date BETWEEN ? AND ?",
            [$date_from, $date_to]
        )->row_array();

        // Cajas
        $cashboxes = $this->db->select('name, balance')
            ->where('deleted', 0)->get('cashboxes')->result_array();

        // Bancos
        $banks = $this->db->select('name, balance')
            ->where('deleted', 0)->get('bank_accounts')->result_array();

        // Cartera
        $receivables = $this->db->query(
            "SELECT COALESCE(SUM(total - payment - discount),0) as total
             FROM invoices WHERE deleted = 0 AND (state = 0 OR state = 1) AND (total - payment - discount) > 0"
        )->row_array();

        return [
            'success' => true,
            'period' => ['from' => $date_from, 'to' => $date_to],
            'sales' => $sales,
            'payments' => $payments,
            'expenses' => $expenses,
            'cashboxes' => $cashboxes,
            'bank_accounts' => $banks,
            'accounts_receivable' => $receivables['total']
        ];
    }

    /**
     * Parsea un periodo en fechas from/to
     */
    private function _parse_period($period)
    {
        switch ($period) {
            case 'today':
                return [date('Y-m-d'), date('Y-m-d')];
            case 'week':
                return [date('Y-m-d', strtotime('monday this week')), date('Y-m-d')];
            case 'year':
                return [date('Y-01-01'), date('Y-m-d')];
            case 'month':
            default:
                if (preg_match('/^\d{4}-\d{2}-\d{2}:\d{4}-\d{2}-\d{2}$/', $period)) {
                    $parts = explode(':', $period);
                    return [$parts[0], $parts[1]];
                }
                return [date('Y-m-01'), date('Y-m-d')];
        }
    }
}
