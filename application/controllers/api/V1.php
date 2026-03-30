<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * V1 - REST API Controller v1
 *
 * RESTful API endpoints with JWT authentication.
 * Routes are defined in application/config/routes.php
 *
 * Public endpoints:
 *   POST api/v1/login   - Authenticate and get JWT token
 *   POST api/v1/refresh  - Refresh JWT token
 *
 * Protected endpoints (require Bearer token):
 *   GET api/v1/clients          - List clients
 *   GET api/v1/clients/search   - Search clients by term
 *   GET api/v1/clients/detail   - Get client detail by id
 *   GET api/v1/products/search  - Search products by term
 *   GET api/v1/products/detail  - Get product detail by id
 *   GET api/v1/products/catalog - Get product catalog
 *   GET api/v1/stores           - List stores
 *   GET api/v1/budgets          - List budgets
 *   GET api/v1/budgets/store    - List budgets by store
 *   GET api/v1/budgets/detail   - Get budget detail by id
 *   POST api/v1/budgets/sync    - Sync/create budget
 */
class V1 extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        // Set CORS headers for all API requests
        $this->load->library('api_response');
        $this->load->library('jwt_lib');
        $this->api_response->set_cors_headers();

        // Handle OPTIONS preflight requests
        if ($this->input->method() === 'options') {
            $this->api_response->success(null, 'OK', 200);
        }

        // Load models used by API endpoints
        $this->load->model('login_model');
        $this->load->model('users_model');
        $this->load->model('clients_model');
        $this->load->model('products_model');
        $this->load->model('stores_model');
        $this->load->model('budgets_model');
    }

    // ---------------------------------------------------------------
    // Authentication Endpoints
    // ---------------------------------------------------------------

    /**
     * POST api/v1/login
     *
     * Authenticate user with idUser + password, return JWT token.
     *
     * Body params: uid (string), password (string)
     */
    public function login()
    {
        if ($this->input->method() !== 'post') {
            $this->api_response->error('Method not allowed', 405);
        }

        $uid      = $this->input->post('uid');
        $password = $this->input->post('password');

        if (empty($uid) || empty($password)) {
            // Also try JSON body
            $json = json_decode(file_get_contents('php://input'), true);
            if ($json) {
                $uid      = isset($json['uid']) ? $json['uid'] : $uid;
                $password = isset($json['password']) ? $json['password'] : $password;
            }
        }

        if (empty($uid) || empty($password)) {
            $this->api_response->error('Se requiere usuario y contrasena', 400);
        }

        // Verify credentials
        $query = $this->db->get_where('users', array('idUser' => $uid, 'deleted' => 0));

        if ($query->num_rows() !== 1) {
            $this->api_response->error('Credenciales invalidas', 401);
        }

        $user = $query->row();

        if (!password_verify($password, $user->password)) {
            $this->api_response->error('Credenciales invalidas', 401);
        }

        // Generate JWT token
        $token = $this->jwt_lib->generateToken(array(
            'idUser' => $user->idUser,
            'name'   => $user->name,
            'role'   => $user->role,
            'store'  => $user->store
        ));

        $this->api_response->success(array(
            'token' => $token,
            'user'  => array(
                'idUser' => $user->idUser,
                'name'   => $user->name,
                'role'   => $user->role,
                'store'  => $user->store
            )
        ), 'Login exitoso');
    }

    /**
     * POST api/v1/refresh
     *
     * Refresh a valid (non-expired) JWT token.
     * Requires Authorization: Bearer <token>
     */
    public function refresh()
    {
        if ($this->input->method() !== 'post') {
            $this->api_response->error('Method not allowed', 405);
        }

        $payload = $this->_authenticate();

        // Re-fetch user to get latest data
        $user = $this->users_model->getAnyUser($payload->sub);

        if (!$user) {
            $this->api_response->error('Usuario no encontrado', 404);
        }

        // Generate new token
        $token = $this->jwt_lib->generateToken(array(
            'idUser' => $user->idUser,
            'name'   => $user->name,
            'role'   => $user->role,
            'store'  => $user->store
        ));

        $this->api_response->success(array(
            'token' => $token,
            'user'  => array(
                'idUser' => $user->idUser,
                'name'   => $user->name,
                'role'   => $user->role,
                'store'  => $user->store
            )
        ), 'Token renovado');
    }

    // ---------------------------------------------------------------
    // Protected Endpoints - Clients
    // ---------------------------------------------------------------

    /**
     * GET api/v1/clients
     *
     * List all clients (paginated).
     * Query params: page (int, default 1), limit (int, default 20)
     */
    public function clients_list()
    {
        $payload = $this->_authenticate();

        $page  = (int) $this->input->get('page') ?: 1;
        $limit = (int) $this->input->get('limit') ?: 20;

        // Vendedores solo ven sus clientes
        if (in_array($payload->role, ['3', 3])) {
            $this->db->where('vendor', $payload->sub);
        }
        $this->db->where('deleted', 0);
        $this->db->order_by('name', 'ASC');
        $this->db->limit($limit, ($page - 1) * $limit);
        $clients = $this->db->get('clients')->result();

        $this->api_response->success(array(
            'clients' => $clients,
            'page'    => $page,
            'limit'   => $limit
        ));
    }

    /**
     * GET api/v1/clients/search
     *
     * Search clients by term.
     * Query params: q (string), page (int), limit (int)
     */
    public function clients_search()
    {
        $payload = $this->_authenticate();

        $term  = $this->input->get('q');
        $page  = (int) $this->input->get('page') ?: 1;
        $limit = (int) $this->input->get('limit') ?: 20;

        if (empty($term)) {
            $this->api_response->error('Se requiere el parametro de busqueda (q)', 400);
        }

        // Vendedores solo buscan sus clientes
        $this->db->select('*');
        $this->db->from('clients');
        $this->db->group_start();
        $this->db->like('name', $term);
        $this->db->or_like('idNum', $term);
        $this->db->or_like('phone', $term);
        $this->db->or_like('cellphone', $term);
        $this->db->group_end();
        $this->db->where('deleted', 0);
        if (in_array($payload->role, ['3', 3])) {
            $this->db->where('vendor', $payload->sub);
        }
        $this->db->order_by('name', 'ASC');
        $this->db->limit($limit, ($page - 1) * $limit);
        $clients = $this->db->get()->result();

        $this->api_response->success(array(
            'clients' => $clients,
            'page'    => $page,
            'limit'   => $limit
        ));
    }

    /**
     * GET api/v1/clients/detail
     *
     * Get client detail by ID.
     * Query params: id (int)
     */
    public function clients_detail()
    {
        $this->_authenticate();

        $id = $this->input->get('id');

        if (empty($id)) {
            $this->api_response->error('Se requiere el parametro id', 400);
        }

        $client = $this->clients_model->getClient($id);

        if (!$client) {
            $this->api_response->error('Cliente no encontrado', 404);
        }

        $this->api_response->success(array('client' => $client));
    }

    // ---------------------------------------------------------------
    // Protected Endpoints - Products
    // ---------------------------------------------------------------

    /**
     * GET api/v1/products/search
     *
     * Search products by term.
     * Query params: q (string), page (int), limit (int)
     */
    public function products_search()
    {
        $this->_authenticate();

        $term  = $this->input->get('q');
        $page  = (int) $this->input->get('page') ?: 1;
        $limit = (int) $this->input->get('limit') ?: 20;

        if (empty($term)) {
            $this->api_response->error('Se requiere el parametro de busqueda (q)', 400);
        }

        $products = $this->products_model->getProductsByWord($term, $page, $limit);

        // Agregar stock total (suma de todas las bodegas) a cada producto
        foreach ($products as &$product) {
            $stockRow = $this->db->query(
                "SELECT COALESCE(SUM(stock), 0) as total_stock FROM inventory WHERE idProduct = ?",
                array($product->idProduct)
            )->row();
            $product->total_stock = (int) $stockRow->total_stock;
        }
        unset($product);

        $this->api_response->success(array(
            'products' => $products,
            'page'     => $page,
            'limit'    => $limit
        ));
    }

    /**
     * GET api/v1/products/detail
     *
     * Get product detail by ID.
     * Query params: id (string)
     */
    public function products_detail()
    {
        $this->_authenticate();

        $id = $this->input->get('id');

        if (empty($id)) {
            $this->api_response->error('Se requiere el parametro id', 400);
        }

        $product = $this->products_model->getProduct($id);

        if (!$product) {
            $this->api_response->error('Producto no encontrado', 404);
        }

        // Add total stock across all stores
        $stockRow = $this->db->query(
            "SELECT COALESCE(SUM(stock), 0) as total_stock FROM inventory WHERE idProduct = ?",
            array($product->idProduct)
        )->row();
        $product->total_stock = (int) $stockRow->total_stock;

        $this->api_response->success(array('product' => $product));
    }

    /**
     * GET api/v1/products/catalog
     *
     * Get product catalog (all products, paginated).
     * Query params: page (int), limit (int)
     */
    public function products_catalog()
    {
        $this->_authenticate();

        $page  = (int) $this->input->get('page') ?: 1;
        $limit = (int) $this->input->get('limit') ?: 20;

        $products = $this->products_model->getProductsPag($page, $limit);
        $total    = $this->products_model->getTotal();

        $this->api_response->success(array(
            'products' => $products,
            'total'    => $total,
            'page'     => $page,
            'limit'    => $limit
        ));
    }

    /**
     * GET api/v1/products/lastunits
     *
     * Productos con stock bajo (entre 1 y su mínimo) — "Últimas Unidades".
     * Devuelve productos ordenados por stock ascendente.
     * Query params: page (int), limit (int), family (int)
     */
    public function products_lastunits()
    {
        $this->_authenticate();

        $page   = (int) $this->input->get('page') ?: 1;
        $limit  = (int) $this->input->get('limit') ?: 20;
        $family = $this->input->get('family');
        $offset = ($page - 1) * $limit;

        // Productos con stock total > 0 y <= su mínimo
        $this->db->select('
            products.idProduct, products.description, products.price,
            products.price_base, products.cost, products.min,
            products.picture_url, products.family,
            pf.name as familyName,
            COALESCE(SUM(inventory.stock), 0) as total_stock
        ');
        $this->db->from('products');
        $this->db->join('inventory', 'inventory.idProduct = products.idProduct', 'left');
        $this->db->join('product_families pf', 'pf.idFamily = products.family', 'left');
        $this->db->where('products.deleted', 0);
        if (!empty($family)) {
            $this->db->where('products.family', (int)$family);
        }
        $this->db->group_by('products.idProduct');
        $this->db->having('total_stock > 0');
        $this->db->having('total_stock <= products.min', NULL, FALSE);
        $this->db->order_by('total_stock', 'ASC');

        // Count total before pagination
        $countQuery = clone $this->db;

        $this->db->limit($limit, $offset);
        $products = $this->db->get()->result();

        // Get total count with a separate query
        $totalQuery = $this->db->query("
            SELECT COUNT(*) as total FROM (
                SELECT products.idProduct, products.min as min_stock
                FROM products
                LEFT JOIN inventory ON inventory.idProduct = products.idProduct
                WHERE products.deleted = 0
                " . (!empty($family) ? "AND products.family = " . (int)$family : "") . "
                GROUP BY products.idProduct
                HAVING COALESCE(SUM(inventory.stock), 0) > 0
                   AND COALESCE(SUM(inventory.stock), 0) <= min_stock
            ) as sub
        ");
        $total = (int) $totalQuery->row()->total;

        $this->api_response->success(array(
            'products' => $products,
            'total'    => $total,
            'page'     => $page,
            'limit'    => $limit
        ));
    }

    /**
     * GET api/v1/products/hot
     * Bestsellers — productos más vendidos últimos 3 meses con stock.
     */
    public function products_hot()
    {
        $this->_authenticate();
        $page   = (int) $this->input->get('page') ?: 1;
        $limit  = (int) $this->input->get('limit') ?: 20;
        $offset = ($page - 1) * $limit;

        $products = $this->db->query("
            SELECT p.idProduct, p.description, p.price, p.picture_url,
                   p.family, pf.name as familyName,
                   COALESCE(inv_t.total_stock, 0) as total_stock,
                   COALESCE(sales.qty_sold, 0) as qty_sold
            FROM products p
            LEFT JOIN product_families pf ON pf.idFamily = p.family
            LEFT JOIN (SELECT idProduct, SUM(stock) as total_stock FROM inventory GROUP BY idProduct) inv_t ON inv_t.idProduct = p.idProduct
            LEFT JOIN (
                SELECT d.productId, SUM(d.quantity) as qty_sold
                FROM invoice_details d
                INNER JOIN invoices i ON i.idInvoice = d.invoiceId
                WHERE i.date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                GROUP BY d.productId
            ) sales ON sales.productId = p.idProduct
            WHERE p.deleted = 0 AND COALESCE(inv_t.total_stock, 0) > 0
            ORDER BY qty_sold DESC
            LIMIT ? OFFSET ?
        ", [$limit, $offset])->result();

        $this->api_response->success(['products' => $products, 'page' => $page, 'limit' => $limit]);
    }

    /**
     * GET api/v1/products/remate
     * Productos con < 5 unidades de stock.
     */
    public function products_remate()
    {
        $this->_authenticate();
        $page   = (int) $this->input->get('page') ?: 1;
        $limit  = (int) $this->input->get('limit') ?: 20;
        $offset = ($page - 1) * $limit;

        $products = $this->db->query("
            SELECT p.idProduct, p.description, p.price, p.picture_url,
                   p.family, pf.name as familyName,
                   COALESCE(inv_t.total_stock, 0) as total_stock
            FROM products p
            LEFT JOIN product_families pf ON pf.idFamily = p.family
            LEFT JOIN (SELECT idProduct, SUM(stock) as total_stock FROM inventory GROUP BY idProduct) inv_t ON inv_t.idProduct = p.idProduct
            WHERE p.deleted = 0
              AND COALESCE(inv_t.total_stock, 0) > 0
              AND COALESCE(inv_t.total_stock, 0) < 5
            ORDER BY total_stock ASC
            LIMIT ? OFFSET ?
        ", [$limit, $offset])->result();

        $this->api_response->success(['products' => $products, 'page' => $page, 'limit' => $limit]);
    }

    // ---------------------------------------------------------------
    // Protected Endpoints - Stores
    // ---------------------------------------------------------------

    /**
     * GET api/v1/stores
     *
     * List all stores.
     */
    public function stores_list()
    {
        $this->_authenticate();

        $stores = $this->stores_model->getStores();

        $this->api_response->success(array('stores' => $stores));
    }

    // ---------------------------------------------------------------
    // Protected Endpoints - Budgets
    // ---------------------------------------------------------------

    /**
     * GET api/v1/budgets
     *
     * List budgets (paginated). Admin/managers see all, vendors see own.
     * Query params: page (int), limit (int), store (int|'all'), state (int|'all')
     */
    public function budgets_list()
    {
        $payload = $this->_authenticate();

        $page  = (int) $this->input->get('page') ?: 1;
        $limit = (int) $this->input->get('limit') ?: 20;
        $store = $this->input->get('store') ?: 'all';
        $state = $this->input->get('state') ?: 'all';

        // Admins (role 1, 2, 6) see all budgets, vendors see only their own
        $getOthers = in_array($payload->role, array('1', '2', '6', 1, 2, 6));

        // Set session data for model compatibility (model reads session for vendor filtering)
        if (!$getOthers) {
            $this->session->set_userdata('user_data', array('uname' => $payload->sub, 'role' => $payload->role));
        }

        $budgets = $this->budgets_model->getBudgets(
            $getOthers, $store, 'all', $state, 'all', 'all', array(), $page, $limit
        );

        $this->api_response->success(array(
            'budgets' => $budgets,
            'page'    => $page,
            'limit'   => $limit
        ));
    }

    /**
     * GET api/v1/budgets/store
     *
     * List budgets filtered by store.
     * Query params: store_id (int), page (int), limit (int)
     */
    public function budgets_store()
    {
        $payload = $this->_authenticate();

        $store_id = $this->input->get('store_id');
        $page     = (int) $this->input->get('page') ?: 1;
        $limit    = (int) $this->input->get('limit') ?: 20;

        if (empty($store_id)) {
            $this->api_response->error('Se requiere el parametro store_id', 400);
        }

        $getOthers = in_array($payload->role, array('1', '2', '6', 1, 2, 6));

        $budgets = $this->budgets_model->getBudgets(
            $getOthers, $store_id, 'all', 'all', 'all', 'all', array(), $page, $limit
        );

        $this->api_response->success(array(
            'budgets'  => $budgets,
            'store_id' => $store_id,
            'page'     => $page,
            'limit'    => $limit
        ));
    }

    /**
     * GET api/v1/budgets/detail
     *
     * Get budget detail by ID.
     * Query params: id (int)
     */
    public function budgets_detail()
    {
        $this->_authenticate();

        $id = $this->input->get('id');

        if (empty($id)) {
            $this->api_response->error('Se requiere el parametro id', 400);
        }

        $budget  = $this->budgets_model->getBudget($id);
        $details = $this->budgets_model->getDetails($id);

        if (!$budget) {
            $this->api_response->error('Cotizacion no encontrada', 404);
        }

        $this->api_response->success(array(
            'budget'  => $budget,
            'details' => $details
        ));
    }

    /**
     * POST api/v1/budgets/sync
     *
     * Create/sync a budget from external source (e.g., mobile app).
     * Body: clientId, storeId, vendorId, items[], hasIva, notes
     */
    public function budgets_sync()
    {
        $payload = $this->_authenticate();

        if ($this->input->method() !== 'post') {
            $this->api_response->error('Method not allowed', 405);
        }

        $json = json_decode(file_get_contents('php://input'), true);
        if (!$json) {
            $json = $this->input->post();
        }

        if (empty($json['clientId']) || empty($json['storeId'])) {
            $this->api_response->error('Se requiere clientId y storeId', 400);
        }

        // Build budget data
        $budgetData = array(
            'clientId'  => $json['clientId'],
            'storeId'   => $json['storeId'],
            'vendorId'  => isset($json['vendorId']) ? $json['vendorId'] : $payload->sub,
            'hasIva'    => isset($json['hasIva']) ? $json['hasIva'] : 0,
            'iva'       => 8,
            'budget_type' => isset($json['budget_type']) ? $json['budget_type'] : 'venta',
            'comments'  => isset($json['notes']) ? $json['notes'] : '',
            'state'     => 0,
            'e_commerce' => 0,
            'list_price' => 0,
            'printed'   => 0,
            'archived'  => 0,
            'deleted'   => 0,
            'date'      => date('Y-m-d H:i:s'),
            'total'     => 0
        );

        // Calculate total from items
        $items = isset($json['items']) ? $json['items'] : array();
        $total = 0;

        // We need to temporarily set session data for the model's save method
        // since it references session for created_by
        $budgetData['created_by'] = $payload->sub;
        $budgetData['created_at'] = date('Y-m-d H:i:s');
        $budgetData['updated_at'] = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            $subtotal = (float) $item['quantity'] * (float) $item['price'];
            $total += $subtotal;
        }

        $budgetData['total'] = $total;

        // Insert budget directly (bypass model save to avoid session dependency)
        $this->db->insert('budgets', $budgetData);
        $budgetId = $this->db->insert_id();

        if (!$budgetId) {
            $this->api_response->error('Error al crear la cotizacion', 500);
        }

        // Insert budget details
        foreach ($items as $item) {
            $detailData = array(
                'budgetId'  => $budgetId,
                'productId' => $item['productId'],
                'quantity'  => $item['quantity'],
                'unit'      => $item['price'],
                'base'      => $item['price'],
                'total'     => (float) $item['quantity'] * (float) $item['price']
            );
            $this->db->insert('budget_detail', $detailData);
        }

        $this->api_response->success(array(
            'budgetId' => $budgetId,
            'total'    => $total
        ), 'Cotizacion creada exitosamente', 201);
    }

    /**
     * POST api/v1/budgets/update
     * Update existing budget (replace items)
     */
    public function budgets_update()
    {
        $payload = $this->_authenticate();
        if ($this->input->method() !== 'post') $this->api_response->error('Method not allowed', 405);

        $json = json_decode(file_get_contents('php://input'), true);
        if (!$json) $json = $this->input->post();

        $budgetId = isset($json['budgetId']) ? (int)$json['budgetId'] : 0;
        if (!$budgetId) $this->api_response->error('Se requiere budgetId', 400);

        $budget = $this->budgets_model->getBudget($budgetId);
        if (!$budget || $budget->state != 0) $this->api_response->error('Presupuesto no editable', 400);

        // Solo el vendedor dueño o admin puede editar
        if ($budget->vendorId !== $payload->sub && !in_array($payload->role, [1, 2, 9])) {
            $this->api_response->error('No autorizado', 403);
        }

        $items = isset($json['items']) ? $json['items'] : array();
        if (empty($items)) $this->api_response->error('Se requiere items', 400);

        // Borrar detalles anteriores
        $this->db->where('budgetId', $budgetId)->delete('budget_detail');

        // Insertar nuevos
        $total = 0;
        foreach ($items as $item) {
            $subtotal = (float)$item['quantity'] * (float)$item['price'];
            $total += $subtotal;
            $this->db->insert('budget_detail', array(
                'budgetId' => $budgetId,
                'productId' => $item['productId'],
                'quantity' => (int)$item['quantity'],
                'unit' => (float)$item['price'],
                'base' => (float)$item['price'],
                'subtotal' => $subtotal
            ));
        }

        // Actualizar total y notas
        $updateData = array('total' => $total, 'updated_at' => date('Y-m-d H:i:s'));
        if (isset($json['notes'])) $updateData['comments'] = $json['notes'];
        $this->budgets_model->update($budgetId, $updateData);

        $this->api_response->success(array(
            'budgetId' => $budgetId,
            'total' => $total
        ), 'Presupuesto actualizado');
    }

    // ---------------------------------------------------------------
    // Refunds (Devoluciones)
    // ---------------------------------------------------------------

    /**
     * GET api/v1/refunds
     * List refunds for the authenticated vendor
     */
    public function refunds_list()
    {
        $payload = $this->_authenticate();
        $this->load->model('invoices_model');

        $page  = (int) $this->input->get('page') ?: 1;
        $limit = (int) $this->input->get('limit') ?: 20;

        $refunds = $this->invoices_model->getRefunds($page, $limit);

        $this->api_response->success(array('refunds' => $refunds));
    }

    /**
     * GET api/v1/refunds/invoice?id=X
     * Get invoice products available for refund
     */
    public function refunds_invoice_products()
    {
        $payload = $this->_authenticate();
        $this->load->model('invoices_model');

        $invoiceId = $this->input->get('id');
        if (empty($invoiceId)) {
            $this->api_response->error('Se requiere id de factura', 400);
        }

        $invoice = $this->invoices_model->getInvoice($invoiceId);
        if (!$invoice) {
            $this->api_response->error('Factura no encontrada', 404);
        }

        $details = $this->invoices_model->getDetails($invoiceId);

        // Get existing refunds for this invoice to calculate remaining qty
        $existingRefunds = $this->invoices_model->getRefundsByInvoice($invoiceId);
        $refundedQty = array();
        foreach ($existingRefunds as $ref) {
            $refDetails = $this->invoices_model->getRefundDetails($ref->idRefund);
            foreach ($refDetails as $rd) {
                if (!isset($refundedQty[$rd->productId])) $refundedQty[$rd->productId] = 0;
                $refundedQty[$rd->productId] += $rd->quantity;
            }
        }

        $products = array();
        foreach ($details as $d) {
            $alreadyRefunded = isset($refundedQty[$d->idProduct]) ? $refundedQty[$d->idProduct] : 0;
            $available = $d->quantity - $alreadyRefunded;
            if ($available > 0) {
                $products[] = array(
                    'productId' => $d->idProduct,
                    'description' => $d->description,
                    'quantity' => $d->quantity,
                    'refunded' => $alreadyRefunded,
                    'available' => $available,
                    'unit' => $d->unit,
                    'total' => $d->subtotal
                );
            }
        }

        $this->api_response->success(array(
            'invoice' => array(
                'idInvoice' => $invoice->idInvoice,
                'client_name' => $invoice->client_name,
                'total' => $invoice->total,
                'date' => $invoice->date,
                'storeId' => $invoice->storeId
            ),
            'products' => $products
        ));
    }

    /**
     * POST api/v1/refunds/create
     * Create a refund for an invoice
     * Body: { invoiceId, comments, items: [{ productId, quantity, unit, total }] }
     */
    public function refunds_create()
    {
        $payload = $this->_authenticate();
        $this->load->model('invoices_model');
        $this->load->model('inventory_model');

        if ($this->input->method() !== 'post') {
            $this->api_response->error('Method not allowed', 405);
        }

        $json = json_decode(file_get_contents('php://input'), true);
        if (!$json) $json = $this->input->post();

        if (empty($json['invoiceId']) || empty($json['items'])) {
            $this->api_response->error('Se requiere invoiceId e items', 400);
        }

        $invoice = $this->invoices_model->getInvoice($json['invoiceId']);
        if (!$invoice) {
            $this->api_response->error('Factura no encontrada', 404);
        }

        // Calculate refund total
        $total = 0;
        foreach ($json['items'] as $item) {
            $total += (float) $item['total'];
        }

        // Get next f_id
        $lastRefund = $this->db->select('MAX(f_id) as max_fid')->get('refunds')->row();
        $nextFid = ($lastRefund->max_fid ?? 0) + 1;

        // Save refund
        $refundData = array(
            'f_id' => $nextFid,
            'invoiceId' => $json['invoiceId'],
            'total' => $total,
            'comments' => isset($json['comments']) ? $json['comments'] : '',
            'date' => date('Y-m-d H:i:s'),
            'state' => 1
        );
        $this->invoices_model->saveRefund($refundData);
        $refundId = $this->db->insert_id();

        // Save refund details and return stock
        foreach ($json['items'] as $item) {
            $this->invoices_model->save_refund_detail(array(
                'refundId' => $refundId,
                'productId' => $item['productId'],
                'quantity' => $item['quantity'],
                'unit' => isset($item['unit']) ? $item['unit'] : 0,
                'total' => $item['total']
            ));

            // Return stock to inventory
            $this->db->set('stock', 'stock + ' . (int)$item['quantity'], FALSE);
            $this->db->where('idProduct', $item['productId']);
            $this->db->where('idStore', $invoice->storeId);
            $this->db->update('inventory');
        }

        $this->api_response->success(array(
            'refundId' => $refundId,
            'total' => $total
        ), 'Devolucion creada exitosamente', 201);
    }

    // ---------------------------------------------------------------
    // Protected Endpoints - Clients by Phone (WhatsApp Bot)
    // ---------------------------------------------------------------

    /**
     * GET api/v1/clients/by-phone
     *
     * Identify a client by phone or cellphone number.
     * Used by WhatsApp bot to recognize the sender.
     * Query params: phone (string)
     */
    public function clients_by_phone()
    {
        $this->_authenticate();

        $phone = $this->input->get('phone');

        if (empty($phone)) {
            $this->api_response->error('Se requiere el parametro phone', 400);
        }

        // Normalize: strip country code prefixes (57, +57) for Colombian numbers
        $normalized = preg_replace('/^\+?57/', '', $phone);

        $this->db->select('clients.*, users.name as vendor_name, users.store as vendor_store');
        $this->db->from('clients');
        $this->db->join('users', 'users.idUser = clients.vendor', 'left');
        $this->db->where('clients.deleted', 0);
        $this->db->group_start();
        $this->db->like('clients.phone', $normalized, 'both');
        $this->db->or_like('clients.cellphone', $normalized, 'both');
        $this->db->group_end();
        $this->db->limit(1);
        $client = $this->db->get()->row();

        if (!$client) {
            $this->api_response->error('Cliente no encontrado para este numero', 404);
        }

        // Get outstanding balance
        $balanceRow = $this->db->query(
            "SELECT COALESCE(SUM(total - payment - discount), 0) as saldo
             FROM invoices
             WHERE deleted = 0 AND clientId = ? AND (state = 0 OR state = 1)
               AND (total - payment - discount) > 0",
            array($client->idClient)
        )->row();

        $this->api_response->success(array(
            'client'  => $client,
            'balance' => (float) $balanceRow->saldo
        ));
    }

    // ---------------------------------------------------------------
    // Protected Endpoints - Promotions (WhatsApp Bot)
    // ---------------------------------------------------------------

    /**
     * GET api/v1/promotions
     *
     * List active promotional packs (promopacks).
     */
    public function promotions_list()
    {
        $this->_authenticate();

        $this->db->select('idPromopack, name, comments, picture_url, created_at');
        $this->db->from('promopacks');
        $this->db->where('deleted', 0);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(10);
        $promos = $this->db->get()->result();

        // Resolver URL pública de cada imagen
        foreach ($promos as &$promo) {
            $pic = $promo->picture_url;
            if (file_exists(FCPATH . 'uploads/' . $pic)) {
                $promo->image_url = base_url('uploads/' . $pic);
            } else {
                $promo->image_url = base_url('public/dist/images/' . $pic);
            }
        }
        unset($promo);

        $this->api_response->success(array('promotions' => $promos));
    }

    // ---------------------------------------------------------------
    // Protected Endpoints - Cartera (Accounts Receivable)
    // ---------------------------------------------------------------

    /**
     * GET api/v1/cartera
     * Cartera del vendedor logueado (o toda si es admin)
     */
    public function cartera()
    {
        $payload = $this->_authenticate();

        $vendorFilter = null;
        if (in_array($payload->role, ['3', 3])) {
            $vendorFilter = $payload->sub;
        }

        $sql = "SELECT c.idClient, c.name as client_name, c.idNum, c.city, c.cellphone,
                    COUNT(i.idInvoice) as invoice_count,
                    SUM(i.total - i.payment - i.discount) as total_debt,
                    DATEDIFF(CURDATE(), MIN(i.date)) as oldest_days,
                    SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) > 90 THEN (i.total - i.payment - i.discount) ELSE 0 END) as debt_over_90
                FROM invoices i
                INNER JOIN clients c ON c.idClient = i.clientId
                WHERE i.deleted = 0 AND (i.state = 0 OR i.state = 1)
                  AND (i.total - i.payment - i.discount) > 0";
        if ($vendorFilter) {
            $sql .= " AND i.vendorId = " . $this->db->escape($vendorFilter);
        }
        $sql .= " GROUP BY c.idClient ORDER BY total_debt DESC LIMIT 50";

        $results = $this->db->query($sql)->result();

        $totalDebt = 0;
        $totalOver90 = 0;
        foreach ($results as $r) {
            $totalDebt += (float)$r->total_debt;
            $totalOver90 += (float)$r->debt_over_90;
        }

        $this->api_response->success(array(
            'clients' => $results,
            'summary' => array(
                'total_debt' => $totalDebt,
                'total_over_90' => $totalOver90,
                'pct_over_90' => $totalDebt > 0 ? round(($totalOver90 / $totalDebt) * 100, 1) : 0,
                'client_count' => count($results)
            )
        ));
    }

    /**
     * GET api/v1/liquidacion
     * Liquidacion del vendedor logueado
     */
    public function liquidacion()
    {
        $payload = $this->_authenticate();

        $vendorId = $payload->sub;

        // Ventas del mes actual
        $monthStart = date('Y-m-01');
        $salesRow = $this->db->query(
            "SELECT COALESCE(SUM(total - discount), 0) as total_sales,
                    COALESCE(SUM(payment), 0) as total_collected,
                    COUNT(*) as invoice_count
             FROM invoices
             WHERE deleted = 0 AND vendorId = ? AND date >= ?",
            array($vendorId, $monthStart)
        )->row();

        // Comision del vendedor
        $vendor = $this->db->where('idUser', $vendorId)->get('users')->row();
        $commissionPct = $vendor ? (float)($vendor->commission_perc ?: 10) : 10;
        $commission = $salesRow ? ($salesRow->total_sales * $commissionPct / 100) : 0;

        // Gastos/vales del vendedor este mes
        $expensesRow = $this->db->query(
            "SELECT COALESCE(SUM(value), 0) as total_expenses
             FROM expenses
             WHERE deleted = 0 AND vendorId = ? AND created_at >= ?",
            array($vendorId, $monthStart)
        )->row();
        $expenses = $expensesRow ? (float)$expensesRow->total_expenses : 0;

        // Vales del vendedor
        $vouchersRow = $this->db->query(
            "SELECT COALESCE(SUM(value), 0) as total_vouchers
             FROM vouchers
             WHERE deleted = 0 AND userId = ? AND date >= ?",
            array($vendorId, $monthStart)
        )->row();
        $vouchers = $vouchersRow ? (float)$vouchersRow->total_vouchers : 0;

        // Meta del mes
        $month = (int)date('m');
        $year = (int)date('Y');
        $goalRow = $this->db->query(
            "SELECT m{$month} as goal FROM sales_goal WHERE userId = ? AND year = ?",
            array($vendorId, $year)
        )->row();
        $goal = $goalRow ? (float)$goalRow->goal : 0;

        $netSettlement = $commission - $expenses - $vouchers;

        $this->api_response->success(array(
            'vendor' => $vendor ? $vendor->name : $vendorId,
            'month' => date('F Y'),
            'sales' => (float)$salesRow->total_sales,
            'collected' => (float)$salesRow->total_collected,
            'invoice_count' => (int)$salesRow->invoice_count,
            'collection_pct' => $salesRow->total_sales > 0 ? round(($salesRow->total_collected / $salesRow->total_sales) * 100, 1) : 0,
            'commission_pct' => $commissionPct,
            'commission' => $commission,
            'expenses' => $expenses,
            'vouchers' => $vouchers,
            'net_settlement' => $netSettlement,
            'goal' => $goal,
            'goal_pct' => $goal > 0 ? round(($salesRow->total_sales / $goal) * 100, 1) : 0
        ));
    }

    // ---------------------------------------------------------------
    // Notifications
    // ---------------------------------------------------------------

    /**
     * GET api/v1/notifications
     * Notificaciones del vendedor logueado.
     * Query params: unread_only (1/0, default 1)
     */
    public function notifications_list()
    {
        $payload = $this->_authenticate();
        $vendorId = $payload->sub;
        $unreadOnly = $this->input->get('unread_only') !== '0';

        $this->db->select('*')
            ->from('notifications')
            ->where('userId', $vendorId);

        if ($unreadOnly) {
            $this->db->where('read_at IS NULL');
        }

        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(50);
        $notifications = $this->db->get()->result();

        // Unread count
        $unreadCount = (int) $this->db->where('userId', $vendorId)
            ->where('read_at IS NULL')
            ->count_all_results('notifications');

        $this->api_response->success([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    /**
     * POST api/v1/notifications/read
     * Marcar notificaciones como leídas.
     * Body: { ids: [1,2,3] } o { all: true }
     */
    public function notifications_read()
    {
        $payload = $this->_authenticate();
        $vendorId = $payload->sub;

        $input = json_decode($this->input->raw_input_stream, true);

        if (!empty($input['all'])) {
            $this->db->where('userId', $vendorId)
                ->where('read_at IS NULL')
                ->update('notifications', ['read_at' => date('Y-m-d H:i:s')]);
        } elseif (!empty($input['ids']) && is_array($input['ids'])) {
            $this->db->where('userId', $vendorId)
                ->where_in('id', array_map('intval', $input['ids']))
                ->update('notifications', ['read_at' => date('Y-m-d H:i:s')]);
        }

        $this->api_response->success(['message' => 'OK']);
    }

    /**
     * GET api/v1/my-goal
     * Meta y progreso del vendedor para el mes actual.
     * Respuesta rápida para mostrar en el home de la PWA.
     */
    public function my_goal()
    {
        $payload = $this->_authenticate();
        $vendorId = $payload->sub;

        $month = (int) date('m');
        $year  = (int) date('Y');
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');

        // Ventas del mes
        $salesRow = $this->db->query("
            SELECT COALESCE(SUM(i.total), 0) as total_sales,
                   COUNT(*) as invoice_count,
                   COALESCE(SUM(i.payment), 0) as total_collected
            FROM invoices i
            WHERE i.vendorId = ?
              AND i.date BETWEEN ? AND ?
              AND i.deleted = 0
        ", [$vendorId, $monthStart, $monthEnd . ' 23:59:59'])->row();

        // Meta
        $goalRow = $this->db->query(
            "SELECT m{$month} as goal FROM sales_goal WHERE userId = ? AND year = ?",
            [$vendorId, $year]
        )->row();
        $goal = $goalRow ? (float)$goalRow->goal : 0;

        $sales = (float)$salesRow->total_sales;
        $goalPct = $goal > 0 ? round(($sales / $goal) * 100, 1) : 0;

        // Days progress
        $dayOfMonth = (int) date('j');
        $daysInMonth = (int) date('t');
        $daysPct = round(($dayOfMonth / $daysInMonth) * 100);

        // Emoji/status based on performance
        $status = 'normal';
        if ($goalPct >= 100) $status = 'elite';
        elseif ($goalPct >= $daysPct) $status = 'ahead';  // above pace
        elseif ($goalPct >= $daysPct * 0.7) $status = 'normal';
        else $status = 'behind';

        // Meta diaria (lo que falta / días hábiles restantes)
        $remaining = max(0, $goal - $sales);
        $workDaysLeft = $this->_workDaysLeft();
        $dailyGoal = $workDaysLeft > 0 ? round($remaining / $workDaysLeft) : 0;

        // Ventas de hoy
        $todayRow = $this->db->query("
            SELECT COALESCE(SUM(total), 0) as today_sales FROM invoices
            WHERE vendorId = ? AND DATE(date) = CURDATE() AND deleted = 0
        ", [$vendorId])->row();

        // Clientes inactivos (sin comprar hace 30+ días)
        $inactiveCount = $this->db->query("
            SELECT COUNT(*) as c FROM clients cl
            WHERE cl.vendor = ? AND cl.deleted = 0 AND cl.blacklisted = 0
            AND cl.idClient NOT IN (
                SELECT DISTINCT clientId FROM invoices WHERE vendorId = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND deleted = 0
            )
        ", [$vendorId, $vendorId])->row();

        // Ranking position
        $rankRows = $this->db->query("
            SELECT vendorId, SUM(total) as t FROM invoices
            WHERE MONTH(date) = ? AND YEAR(date) = ? AND deleted = 0 AND storeId IN (1,3,5)
            GROUP BY vendorId ORDER BY t DESC
        ", [$month, $year])->result();
        $position = 0;
        foreach ($rankRows as $idx => $rr) {
            if ($rr->vendorId == $vendorId) { $position = $idx + 1; break; }
        }

        $this->api_response->success([
            'sales'           => $sales,
            'goal'            => $goal,
            'goal_pct'        => $goalPct,
            'collected'       => (float)$salesRow->total_collected,
            'invoice_count'   => (int)$salesRow->invoice_count,
            'day_of_month'    => $dayOfMonth,
            'days_in_month'   => $daysInMonth,
            'days_pct'        => $daysPct,
            'status'          => $status,
            'daily_goal'      => $dailyGoal,
            'today_sales'     => (float)$todayRow->today_sales,
            'work_days_left'  => $workDaysLeft,
            'inactive_clients'=> (int)$inactiveCount->c,
            'ranking'         => $position,
            'total_vendors'   => count($rankRows),
        ]);
    }

    // ---------------------------------------------------------------
    // Client Messages (vendor side)
    // ---------------------------------------------------------------

    /**
     * GET api/v1/client-messages
     * Mensajes de clientes para este vendedor (no leídos primero).
     */
    public function client_messages()
    {
        $payload = $this->_authenticate();
        $vendorId = $payload->sub;

        // Get conversations grouped by client
        $conversations = $this->db->query("
            SELECT cm.clientId, c.name as clientName, c.cellphone, c.phone,
                   MAX(cm.created_at) as last_message_at,
                   SUM(CASE WHEN cm.sender = 'client' AND cm.read_at IS NULL THEN 1 ELSE 0 END) as unread
            FROM chat_messages cm
            INNER JOIN clients c ON c.idClient = cm.clientId
            WHERE cm.vendorId = ?
            GROUP BY cm.clientId
            ORDER BY unread DESC, last_message_at DESC
            LIMIT 30
        ", [$vendorId])->result();

        $this->api_response->success(['conversations' => $conversations]);
    }

    /**
     * GET api/v1/client-messages/chat?clientId=X
     * Historial de chat con un cliente específico.
     */
    public function client_messages_chat()
    {
        $payload = $this->_authenticate();
        $vendorId = $payload->sub;
        $clientId = (int)$this->input->get('clientId');

        if (!$clientId) {
            $this->api_response->error('Se requiere clientId', 400);
        }

        $messages = $this->db->select('*')
            ->from('chat_messages')
            ->where('clientId', $clientId)
            ->where('vendorId', $vendorId)
            ->order_by('created_at', 'ASC')
            ->limit(50)
            ->get()->result();

        // Mark client messages as read
        $this->db->where('clientId', $clientId)
            ->where('vendorId', $vendorId)
            ->where('sender', 'client')
            ->where('read_at IS NULL')
            ->update('chat_messages', ['read_at' => date('Y-m-d H:i:s')]);

        $client = $this->db->select('name, cellphone, phone')
            ->from('clients')
            ->where('idClient', $clientId)
            ->get()->row();

        $this->api_response->success([
            'messages' => $messages,
            'client'   => $client,
        ]);
    }

    /**
     * POST api/v1/client-messages/reply
     * Vendedor responde a un cliente.
     * Body: { clientId, message }
     */
    public function client_messages_reply()
    {
        $payload = $this->_authenticate();
        $vendorId = $payload->sub;

        $input = json_decode($this->input->raw_input_stream, true);
        $clientId = isset($input['clientId']) ? (int)$input['clientId'] : 0;
        $message  = isset($input['message']) ? trim($input['message']) : '';

        if (!$clientId || empty($message)) {
            $this->api_response->error('Se requiere clientId y message', 400);
        }

        $this->db->insert('chat_messages', [
            'clientId'   => $clientId,
            'vendorId'   => $vendorId,
            'sender'     => 'vendor',
            'message'    => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->api_response->success(['sent' => true]);
    }

    // ---------------------------------------------------------------
    // Helper Methods
    // ---------------------------------------------------------------

    /**
     * Extract and validate JWT token from Authorization header
     *
     * @return object Decoded JWT payload
     */
    private function _workDaysLeft()
    {
        $today = new DateTime();
        $endMonth = new DateTime(date('Y-m-t'));
        $days = 0;
        $current = clone $today;
        while ($current <= $endMonth) {
            if ($current->format('N') < 6) $days++;
            $current->modify('+1 day');
        }
        return max($days, 1);
    }

    private function _authenticate()
    {
        $headers = $this->input->request_headers();

        $token = null;

        // Look for Authorization header (case-insensitive)
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                if (preg_match('/Bearer\s+(.+)/i', $value, $matches)) {
                    $token = $matches[1];
                }
                break;
            }
        }

        if (empty($token)) {
            $this->api_response->error('Token de autenticacion requerido', 401);
        }

        $payload = $this->jwt_lib->validateToken($token);

        if ($payload === false) {
            $this->api_response->error('Token invalido o expirado', 401);
        }

        return $payload;
    }
}
