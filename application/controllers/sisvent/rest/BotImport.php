<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * BotImport - Google Sheets Bot Webhook Controller
 *
 * Receives sales data from a Google Sheets bot via webhook,
 * queues it in bot_sales_queue, and processes it into budgets.
 *
 * This controller does NOT use session-based authentication.
 * Authentication is done via API key (matches users.bot_api_key).
 *
 * Endpoints:
 *   POST sisvent/rest/botimport/receive  - Receive bot payload
 *   GET  sisvent/rest/botimport/process   - Process pending queue items
 *   GET  sisvent/rest/botimport/status    - Check queue status for a vendor
 */
class BotImport extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        // Do NOT call backend_lib->control() - this is an API endpoint without sessions
        $this->load->library('api_response');
        $this->api_response->set_cors_headers();

        // Handle OPTIONS preflight
        if ($this->input->method() === 'options') {
            $this->api_response->success(null, 'OK', 200);
        }

        $this->load->model('budgets_model');
        $this->load->model('users_model');
    }

    /**
     * POST sisvent/rest/botimport/receive
     *
     * Receive a sales payload from the Google Sheets bot.
     * Validates API key, identifies the vendor, and queues the data.
     *
     * Expected body (JSON):
     * {
     *   "api_key": "...",
     *   "data": {
     *     "clientId": "...",
     *     "storeId": "...",
     *     "items": [ { "productId": "...", "quantity": N, "price": N } ],
     *     "hasIva": 0|1,
     *     "notes": "..."
     *   }
     * }
     */
    public function receive()
    {
        if ($this->input->method() !== 'post') {
            $this->api_response->error('Method not allowed', 405);
        }

        // Parse JSON body
        $json = json_decode(file_get_contents('php://input'), true);

        if (!$json) {
            // Fall back to POST data
            $json = array(
                'api_key' => $this->input->post('api_key'),
                'data'    => $this->input->post('data')
            );
        }

        $api_key = isset($json['api_key']) ? $json['api_key'] : null;

        if (empty($api_key)) {
            $this->api_response->error('API key requerida', 401);
        }

        // Validate API key and get vendor
        $vendor = $this->_get_vendor_by_api_key($api_key);

        if (!$vendor) {
            $this->api_response->error('API key invalida', 401);
        }

        $data = isset($json['data']) ? $json['data'] : null;

        if (empty($data)) {
            $this->api_response->error('Se requiere el campo data con la informacion de la venta', 400);
        }

        // Ensure data is a JSON string for storage
        $data_json = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);

        // Queue the payload in bot_sales_queue
        date_default_timezone_set('America/Bogota');
        $queue_data = array(
            'vendor_id'  => $vendor->idUser,
            'store_id'   => isset($data['storeId']) ? $data['storeId'] : $vendor->store,
            'payload'    => $data_json,
            'status'     => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        );

        $inserted = $this->db->insert('bot_sales_queue', $queue_data);

        if (!$inserted) {
            $this->api_response->error('Error al encolar la venta', 500);
        }

        $queue_id = $this->db->insert_id();

        $this->api_response->success(array(
            'queue_id' => $queue_id,
            'status'   => 'pending'
        ), 'Venta encolada exitosamente', 201);
    }

    /**
     * GET sisvent/rest/botimport/process
     *
     * Process pending items from bot_sales_queue.
     * Creates budgets from the queued data.
     * Can be called via cron or manually.
     *
     * Optional query param: api_key (for authentication)
     */
    public function process()
    {
        $api_key = $this->input->get('api_key');

        // Allow processing without API key only from CLI
        if (!$this->input->is_cli_request() && empty($api_key)) {
            $this->api_response->error('API key requerida', 401);
        }

        if (!empty($api_key)) {
            $vendor = $this->_get_vendor_by_api_key($api_key);
            if (!$vendor) {
                $this->api_response->error('API key invalida', 401);
            }
        }

        // Get pending items
        $this->db->where('status', 'pending');
        $this->db->order_by('created_at', 'ASC');
        $this->db->limit(50); // Process in batches
        $pending = $this->db->get('bot_sales_queue')->result();

        $processed = 0;
        $failed    = 0;
        $results   = array();

        foreach ($pending as $item) {
            $payload = json_decode($item->payload, true);

            if (!$payload || empty($payload['clientId'])) {
                $this->_update_queue_status($item->id, 'failed', 'Payload invalido o falta clientId');
                $failed++;
                $results[] = array('queue_id' => $item->id, 'status' => 'failed', 'error' => 'Payload invalido');
                continue;
            }

            // Build budget data
            date_default_timezone_set('America/Bogota');
            $items = isset($payload['items']) ? $payload['items'] : array();
            $total = 0;

            foreach ($items as $detail) {
                $total += (float) $detail['quantity'] * (float) $detail['price'];
            }

            $budgetData = array(
                'clientId'   => $payload['clientId'],
                'storeId'    => isset($payload['storeId']) ? $payload['storeId'] : $item->store_id,
                'vendorId'   => $item->vendor_id,
                'hasIva'     => isset($payload['hasIva']) ? $payload['hasIva'] : 0,
                'notes'      => isset($payload['notes']) ? $payload['notes'] : 'Importado via Bot',
                'state'      => 1,
                'date'       => date('Y-m-d H:i:s'),
                'total'      => $total,
                'created_by' => $item->vendor_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            );

            // Insert budget directly (no session available)
            $this->db->insert('budgets', $budgetData);
            $budgetId = $this->db->insert_id();

            if (!$budgetId) {
                $this->_update_queue_status($item->id, 'failed', 'Error al insertar cotizacion');
                $failed++;
                $results[] = array('queue_id' => $item->id, 'status' => 'failed', 'error' => 'Error DB');
                continue;
            }

            // Insert budget details
            foreach ($items as $detail) {
                $detailData = array(
                    'budgetId'  => $budgetId,
                    'productId' => $detail['productId'],
                    'quantity'  => $detail['quantity'],
                    'price'     => $detail['price'],
                    'total'     => (float) $detail['quantity'] * (float) $detail['price']
                );
                $this->db->insert('budget_detail', $detailData);
            }

            $this->_update_queue_status($item->id, 'completed', null, $budgetId);
            $processed++;
            $results[] = array('queue_id' => $item->id, 'status' => 'completed', 'budget_id' => $budgetId);
        }

        $this->api_response->success(array(
            'processed' => $processed,
            'failed'    => $failed,
            'total'     => count($pending),
            'results'   => $results
        ), 'Procesamiento completado');
    }

    /**
     * GET sisvent/rest/botimport/status
     *
     * Return queue status for a vendor.
     *
     * Query params: api_key (string), status (string: pending|completed|failed, optional)
     */
    public function status()
    {
        $api_key = $this->input->get('api_key');

        if (empty($api_key)) {
            $this->api_response->error('API key requerida', 401);
        }

        $vendor = $this->_get_vendor_by_api_key($api_key);

        if (!$vendor) {
            $this->api_response->error('API key invalida', 401);
        }

        $status_filter = $this->input->get('status');

        $this->db->where('vendor_id', $vendor->idUser);

        if (!empty($status_filter)) {
            $this->db->where('status', $status_filter);
        }

        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(100);
        $queue_items = $this->db->get('bot_sales_queue')->result();

        // Get counts by status
        $this->db->select('status, COUNT(*) as count');
        $this->db->where('vendor_id', $vendor->idUser);
        $this->db->group_by('status');
        $counts_raw = $this->db->get('bot_sales_queue')->result();

        $counts = array('pending' => 0, 'completed' => 0, 'failed' => 0);
        foreach ($counts_raw as $row) {
            $counts[$row->status] = (int) $row->count;
        }

        $this->api_response->success(array(
            'vendor_id' => $vendor->idUser,
            'counts'    => $counts,
            'items'     => $queue_items
        ));
    }

    // ---------------------------------------------------------------
    // Helper Methods
    // ---------------------------------------------------------------

    /**
     * Look up a vendor by their bot API key
     *
     * @param string $api_key
     * @return object|null User row or null
     */
    private function _get_vendor_by_api_key($api_key)
    {
        $this->db->where('bot_api_key', $api_key);
        $this->db->where('deleted', 0);
        $query = $this->db->get('users');

        if ($query->num_rows() === 1) {
            return $query->row();
        }

        return null;
    }

    /**
     * GET sisvent/rest/botimport/stock
     *
     * Consulta de stock para el bot.
     * Query params: api_key, product (idProduct o busqueda)
     */
    public function stock()
    {
        $apiKey = $this->input->get('api_key');
        if (empty($apiKey)) {
            $this->api_response->error('API key requerida', 401);
        }

        $vendor = $this->_get_vendor_by_api_key($apiKey);
        if (!$vendor) {
            $this->api_response->error('API key invalida', 401);
        }

        $search = $this->input->get('product');
        if (empty($search)) {
            $this->api_response->error('Se requiere el parametro product', 400);
        }

        $products = $this->db->query("
            SELECT p.idProduct, p.description, p.price, p.min as min_stock,
                   COALESCE(SUM(inv.stock), 0) as total_stock
            FROM products p
            LEFT JOIN inventory inv ON inv.idProduct = p.idProduct
            WHERE p.deleted = 0
              AND (p.idProduct = ? OR p.description LIKE ?)
            GROUP BY p.idProduct
            ORDER BY p.description ASC
            LIMIT 10
        ", [$search, '%' . $search . '%'])->result();

        foreach ($products as &$prod) {
            $stores = $this->db->query("
                SELECT s.name as store, inv.stock
                FROM inventory inv
                INNER JOIN stores s ON s.idStore = inv.idStore
                WHERE inv.idProduct = ? AND inv.stock > 0
                ORDER BY inv.stock DESC
            ", [$prod->idProduct])->result();
            $prod->stock_by_store = $stores;
            $prod->low_stock = (int)$prod->total_stock <= (int)$prod->min_stock && (int)$prod->total_stock > 0;
            $prod->out_of_stock = (int)$prod->total_stock <= 0;
        }
        unset($prod);

        $this->api_response->success([
            'products' => $products,
            'query'    => $search,
        ]);
    }

    /**
     * Update the status of a queue item
     *
     * @param int    $id         Queue item ID
     * @param string $status     New status (pending|completed|failed)
     * @param string $error_msg  Error message (for failed items)
     * @param int    $budget_id  Created budget ID (for completed items)
     */
    private function _update_queue_status($id, $status, $error_msg = null, $budget_id = null)
    {
        date_default_timezone_set('America/Bogota');

        $data = array(
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s')
        );

        if ($error_msg !== null) {
            $data['error_message'] = $error_msg;
        }

        if ($budget_id !== null) {
            $data['budget_id'] = $budget_id;
        }

        if ($status === 'completed' || $status === 'failed') {
            $data['processed_at'] = date('Y-m-d H:i:s');
        }

        $this->db->where('id', $id);
        $this->db->update('bot_sales_queue', $data);
    }
}
