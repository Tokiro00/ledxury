<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ClientPortal - API para clientes (acceso por token directo)
 *
 * Los clientes acceden via link con token único generado por su vendedor.
 * No requiere login — el token identifica al cliente.
 *
 * Endpoints:
 *   GET api/client/validate?t=TOKEN        - Valida token y devuelve info del cliente
 *   GET api/client/catalog?t=TOKEN         - Catálogo con precios del cliente
 *   GET api/client/lastunits?t=TOKEN       - Últimas unidades (stock bajo)
 *   GET api/client/favorites?t=TOKEN       - Productos favoritos del cliente
 *   GET api/client/orders?t=TOKEN          - Pedidos/presupuestos del cliente
 *   GET api/client/cartera?t=TOKEN         - Cartera pendiente del cliente
 *   POST api/client/order?t=TOKEN          - Crear pedido desde la PWA del cliente
 *   POST api/client/generate-token         - (JWT auth) Genera token para un cliente
 */
class ClientPortal extends CI_Controller {

    private $client = null;
    private $tokenData = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('api_response');
        $this->load->library('jwt_lib');
        $this->api_response->set_cors_headers();

        if ($this->input->method() === 'options') {
            $this->api_response->success(null, 'OK', 200);
        }

        $this->load->model('clients_model');
        $this->load->model('products_model');
        $this->load->model('smartcatalog_model');
        $this->load->model('budgets_model');
    }

    // ---------------------------------------------------------------
    // Token Validation
    // ---------------------------------------------------------------

    /**
     * Valida el token del query string y carga el cliente.
     * Llama api_response->error() si el token es inválido.
     */
    private function _authenticateClient()
    {
        $token = $this->input->get('t');

        if (empty($token) || strlen($token) < 16) {
            $this->api_response->error('Token requerido', 401);
        }

        $row = $this->db->select('ct.*, c.name as client_name, c.idNum, c.city, c.phone, c.cellphone, c.vendor, u.name as vendor_name, u.phone as vendor_phone')
            ->from('client_tokens ct')
            ->join('clients c', 'c.idClient = ct.clientId')
            ->join('users u', 'u.idUser = ct.vendorId', 'left')
            ->where('ct.token', $token)
            ->where('ct.active', 1)
            ->get()->row();

        if (!$row) {
            $this->api_response->error('Token invalido o expirado', 401);
        }

        // Check expiration if set
        if ($row->expires_at && strtotime($row->expires_at) < time()) {
            $this->api_response->error('Token expirado', 401);
        }

        // Update last used
        $this->db->where('id', $row->id)->update('client_tokens', ['last_used_at' => date('Y-m-d H:i:s')]);

        $this->tokenData = $row;
        $this->client = (object)[
            'idClient'     => $row->clientId,
            'name'         => $row->client_name,
            'idNum'        => $row->idNum,
            'city'         => $row->city,
            'phone'        => $row->phone,
            'cellphone'    => $row->cellphone,
            'vendor'       => $row->vendor,
            'vendor_name'  => $row->vendor_name,
            'vendor_phone' => $row->vendor_phone,
        ];

        return $this->client;
    }

    // ---------------------------------------------------------------
    // Public Client Endpoints (token auth)
    // ---------------------------------------------------------------

    /**
     * GET api/client/validate
     * Valida token y devuelve info básica del cliente + vendedor.
     */
    public function validate()
    {
        $client = $this->_authenticateClient();

        $this->api_response->success([
            'client' => [
                'id'           => $client->idClient,
                'name'         => $client->name,
                'idNum'        => $client->idNum,
                'city'         => $client->city,
            ],
            'vendor' => [
                'name'  => $client->vendor_name,
                'phone' => $client->vendor_phone,
            ]
        ]);
    }

    /**
     * GET api/client/catalog
     * Catálogo de productos con precios personalizados del cliente.
     * Query params: page, limit, family, q (búsqueda)
     */
    public function catalog()
    {
        $client = $this->_authenticateClient();

        $page   = (int) $this->input->get('page') ?: 1;
        $limit  = (int) $this->input->get('limit') ?: 20;
        $family = $this->input->get('family');
        $search = $this->input->get('q');
        $offset = ($page - 1) * $limit;

        $clientId = $client->idClient;

        // Smart order: client's most purchased products first, then the rest
        $familyWhere = !empty($family) ? "AND p.family = " . (int)$family : "";
        $searchWhere = "";
        if (!empty($search)) {
            $esc = $this->db->escape_like_str($search);
            $searchWhere = "AND (p.description LIKE '%{$esc}%' OR p.idProduct LIKE '%{$esc}%')";
        }

        $products = $this->db->query("
            SELECT p.idProduct, p.description, p.price, p.price_base, p.picture_url,
                   p.family, pf.name as familyName,
                   COALESCE(SUM(inv.stock), 0) as total_stock,
                   COALESCE(client_buys.qty_bought, 0) as qty_bought
            FROM products p
            LEFT JOIN inventory inv ON inv.idProduct = p.idProduct
            LEFT JOIN product_families pf ON pf.idFamily = p.family
            LEFT JOIN (
                SELECT d.productId, SUM(d.quantity) as qty_bought
                FROM invoice_details d
                INNER JOIN invoices i ON i.idInvoice = d.invoiceId
                WHERE i.clientId = ?
                GROUP BY d.productId
            ) client_buys ON client_buys.productId = p.idProduct
            WHERE p.deleted = 0
            {$familyWhere}
            {$searchWhere}
            GROUP BY p.idProduct
            HAVING total_stock > 0
            ORDER BY qty_bought DESC, p.description ASC
            LIMIT ? OFFSET ?
        ", [$clientId, $limit, $offset])->result();

        // Enrich with client's last negotiated price
        foreach ($products as &$p) {
            $lastPrice = $this->db->query("
                SELECT d.unit FROM invoice_details d
                INNER JOIN invoices i ON i.idInvoice = d.invoiceId
                WHERE i.clientId = ? AND d.productId = ?
                ORDER BY i.date DESC LIMIT 1
            ", [$clientId, $p->idProduct])->row();
            $p->client_price = $lastPrice ? (float)$lastPrice->unit : (float)$p->price;
        }
        unset($p);

        // Total count
        $totalQuery = $this->db->query("
            SELECT COUNT(*) as total FROM (
                SELECT p2.idProduct FROM products p2
                LEFT JOIN inventory inv2 ON inv2.idProduct = p2.idProduct
                WHERE p2.deleted = 0
                " . (!empty($family) ? "AND p2.family = " . (int)$family : "") . "
                " . (!empty($search) ? "AND (p2.description LIKE '%" . $this->db->escape_like_str($search) . "%' OR p2.idProduct LIKE '%" . $this->db->escape_like_str($search) . "%')" : "") . "
                GROUP BY p2.idProduct
                HAVING COALESCE(SUM(inv2.stock), 0) > 0
            ) sub
        ");
        $total = (int)$totalQuery->row()->total;

        // Families for filter
        $families = $this->db->select('pf.idFamily, pf.name')
            ->from('product_families pf')
            ->where('pf.deleted', 0)
            ->order_by('pf.name')
            ->get()->result();

        $this->api_response->success([
            'products' => $products,
            'families' => $families,
            'total'    => $total,
            'page'     => $page,
            'limit'    => $limit,
        ]);
    }

    /**
     * GET api/client/lastunits
     * Ofertas — productos reales que no se mueven hace 3+ meses.
     * Son productos con stock real > 0 cuya última venta fue hace más de 90 días.
     * Se ordenan por días sin venta (más dormidos primero).
     * Query params: page, limit
     */
    public function lastunits()
    {
        $client = $this->_authenticateClient();

        $page   = (int) $this->input->get('page') ?: 1;
        $limit  = (int) $this->input->get('limit') ?: 20;
        $offset = ($page - 1) * $limit;
        $clientId = $client->idClient;

        $products = $this->db->query("
            SELECT p.idProduct, p.description, p.price, p.price_base, p.picture_url,
                   p.family, pf.name as familyName, p.cost_cop,
                   COALESCE(inv_totals.total_stock, 0) as total_stock,
                   sales.ultima_venta,
                   DATEDIFF(NOW(), sales.ultima_venta) as dias_sin_venta
            FROM products p
            LEFT JOIN product_families pf ON pf.idFamily = p.family
            LEFT JOIN (
                SELECT idProduct, SUM(stock) as total_stock
                FROM inventory GROUP BY idProduct
            ) inv_totals ON inv_totals.idProduct = p.idProduct
            LEFT JOIN (
                SELECT d.productId, MAX(i.date) as ultima_venta
                FROM invoice_details d
                INNER JOIN invoices i ON i.idInvoice = d.invoiceId
                GROUP BY d.productId
            ) sales ON sales.productId = p.idProduct
            WHERE p.deleted = 0
              AND COALESCE(inv_totals.total_stock, 0) >= 5
              AND (sales.ultima_venta IS NULL OR sales.ultima_venta < DATE_SUB(NOW(), INTERVAL 3 MONTH))
            ORDER BY dias_sin_venta DESC, total_stock DESC
            LIMIT ? OFFSET ?
        ", [$limit, $offset])->result();

        // Add client's negotiated price + automatic discount (with manual overrides)
        foreach ($products as &$p) {
            // Check for manual override first
            $override = $this->db->where('productId', $p->idProduct)->where('active', 1)->get('catalog_overrides')->row();
            if ($override && $override->tab === 'excluido') { $p->_exclude = true; continue; }

            $lastPrice = $this->db->query("
                SELECT d.unit FROM invoice_details d
                INNER JOIN invoices i ON i.idInvoice = d.invoiceId
                WHERE i.clientId = ? AND d.productId = ?
                ORDER BY i.date DESC LIMIT 1
            ", [$clientId, $p->idProduct])->row();

            $precioLista = (float)$p->price;
            $costoCop = (float)$p->cost_cop;
            $dias = (int)$p->dias_sin_venta;

            // Estrategia agresiva de liquidación
            if ($dias > 730 && $costoCop > 0) {
                // +2 años: AL COSTO — solo recuperar capital
                $p->client_price = round($costoCop);
                $p->offer_tag = 'AL COSTO';
            } elseif ($dias > 365) {
                // +1 año: -70% — liberar bodega
                $p->client_price = round($precioLista * 0.30);
                $p->offer_tag = 'LIQUIDACION';
            } elseif ($dias > 270) {
                // 9-12 meses: -55%
                $p->client_price = round($precioLista * 0.45);
                $p->offer_tag = 'OFERTA';
            } elseif ($dias > 180) {
                // 6-9 meses: -40%
                $p->client_price = round($precioLista * 0.60);
                $p->offer_tag = 'OFERTA';
            } else {
                // 3-6 meses: -25%
                $p->client_price = round($precioLista * 0.75);
                $p->offer_tag = 'OPORTUNIDAD';
            }

            // Nunca vender por debajo del costo si lo conocemos
            if ($costoCop > 0 && $p->client_price < $costoCop && $dias <= 730) {
                $p->client_price = round($costoCop);
            }

            // Apply manual override if exists
            if ($override && $override->price_override) {
                $p->client_price = (float)$override->price_override;
            }

            $p->price_original = $precioLista;
            $p->discount_pct = $precioLista > 0 ? round((1 - $p->client_price / $precioLista) * 100) : 0;
        }
        unset($p);

        // Remove excluded products
        $products = array_values(array_filter($products, function($p) { return empty($p->_exclude); }));

        $totalQuery = $this->db->query("
            SELECT COUNT(*) as total FROM (
                SELECT p.idProduct
                FROM products p
                LEFT JOIN (SELECT idProduct, SUM(stock) as total_stock FROM inventory GROUP BY idProduct) inv ON inv.idProduct = p.idProduct
                LEFT JOIN (SELECT d.productId, MAX(i.date) as ultima_venta FROM invoice_details d INNER JOIN invoices i ON i.idInvoice = d.invoiceId GROUP BY d.productId) s ON s.productId = p.idProduct
                WHERE p.deleted = 0
                  AND COALESCE(inv.total_stock, 0) >= 5
                  AND (s.ultima_venta IS NULL OR s.ultima_venta < DATE_SUB(NOW(), INTERVAL 3 MONTH))
            ) sub
        ");
        $total = (int)$totalQuery->row()->total;

        $this->api_response->success([
            'products' => $products,
            'total'    => $total,
            'page'     => $page,
            'limit'    => $limit,
        ]);
    }

    /**
     * GET api/client/remate
     * Remate — últimas piezas (< 5 unidades), precios de regalo.
     * Productos que no vuelven. Descuentos extra agresivos.
     */
    public function remate()
    {
        $client = $this->_authenticateClient();

        $page   = (int) $this->input->get('page') ?: 1;
        $limit  = (int) $this->input->get('limit') ?: 20;
        $offset = ($page - 1) * $limit;
        $clientId = $client->idClient;

        $products = $this->db->query("
            SELECT p.idProduct, p.description, p.price, p.price_base, p.picture_url,
                   p.family, pf.name as familyName, p.cost_cop,
                   COALESCE(inv_totals.total_stock, 0) as total_stock,
                   sales.ultima_venta,
                   DATEDIFF(NOW(), sales.ultima_venta) as dias_sin_venta
            FROM products p
            LEFT JOIN product_families pf ON pf.idFamily = p.family
            LEFT JOIN (
                SELECT idProduct, SUM(stock) as total_stock
                FROM inventory GROUP BY idProduct
            ) inv_totals ON inv_totals.idProduct = p.idProduct
            LEFT JOIN (
                SELECT d.productId, MAX(i.date) as ultima_venta
                FROM invoice_details d
                INNER JOIN invoices i ON i.idInvoice = d.invoiceId
                GROUP BY d.productId
            ) sales ON sales.productId = p.idProduct
            WHERE p.deleted = 0
              AND COALESCE(inv_totals.total_stock, 0) > 0
              AND COALESCE(inv_totals.total_stock, 0) < 5
            ORDER BY total_stock ASC, dias_sin_venta DESC
            LIMIT ? OFFSET ?
        ", [$limit, $offset])->result();

        foreach ($products as &$p) {
            $precioLista = (float)$p->price;
            $costoCop = (float)$p->cost_cop;
            $stock = (int)$p->total_stock;

            // Remate: descuentos extra agresivos por ser últimas piezas
            if ($stock <= 1) {
                // Última unidad: -80% o al costo, lo que sea menor
                $p->client_price = $costoCop > 0 ? round(min($precioLista * 0.20, $costoCop)) : round($precioLista * 0.20);
            } elseif ($stock <= 2) {
                // 2 unidades: -70%
                $p->client_price = $costoCop > 0 ? round(min($precioLista * 0.30, $costoCop * 0.90)) : round($precioLista * 0.30);
            } else {
                // 3-4 unidades: -60%
                $p->client_price = $costoCop > 0 ? round(min($precioLista * 0.40, $costoCop * 0.95)) : round($precioLista * 0.40);
            }

            $p->price_original = $precioLista;
            $p->discount_pct = $precioLista > 0 ? round((1 - $p->client_price / $precioLista) * 100) : 0;
        }
        unset($p);

        $totalQuery = $this->db->query("
            SELECT COUNT(*) as total FROM (
                SELECT p.idProduct
                FROM products p
                LEFT JOIN (SELECT idProduct, SUM(stock) as total_stock FROM inventory GROUP BY idProduct) inv ON inv.idProduct = p.idProduct
                WHERE p.deleted = 0
                  AND COALESCE(inv.total_stock, 0) > 0
                  AND COALESCE(inv.total_stock, 0) < 5
            ) sub
        ");
        $total = (int)$totalQuery->row()->total;

        $this->api_response->success([
            'products' => $products,
            'total'    => $total,
            'page'     => $page,
            'limit'    => $limit,
        ]);
    }

    /**
     * GET api/client/hot
     * Mix por categoría — top 6 productos de cada familia, ordenados por ventas.
     * Devuelve secciones: [{family, familyName, products: [...]}]
     */
    public function hot()
    {
        $client = $this->_authenticateClient();
        $clientId = $client->idClient;

        // Get families with stock
        $families = $this->db->query("
            SELECT pf.idFamily, pf.name
            FROM product_families pf
            INNER JOIN products p ON p.family = pf.idFamily AND p.deleted = 0
            LEFT JOIN (SELECT idProduct, SUM(stock) as total_stock FROM inventory GROUP BY idProduct) inv ON inv.idProduct = p.idProduct
            WHERE pf.deleted = 0
            GROUP BY pf.idFamily
            HAVING SUM(COALESCE(inv.total_stock, 0)) > 0
            ORDER BY SUM(COALESCE(inv.total_stock, 0)) DESC
        ")->result();

        $sections = [];
        foreach ($families as $fam) {
            $products = $this->db->query("
                SELECT p.idProduct, p.description, p.price, p.price_base, p.picture_url,
                       p.family, ? as familyName,
                       COALESCE(inv_t.total_stock, 0) as total_stock,
                       COALESCE(sales.qty_sold, 0) as qty_sold
                FROM products p
                LEFT JOIN (SELECT idProduct, SUM(stock) as total_stock FROM inventory GROUP BY idProduct) inv_t ON inv_t.idProduct = p.idProduct
                LEFT JOIN (
                    SELECT d.productId, SUM(d.quantity) as qty_sold
                    FROM invoice_details d
                    INNER JOIN invoices i ON i.idInvoice = d.invoiceId
                    WHERE i.date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                    GROUP BY d.productId
                ) sales ON sales.productId = p.idProduct
                WHERE p.deleted = 0 AND p.family = ?
                  AND COALESCE(inv_t.total_stock, 0) > 0
                ORDER BY qty_sold DESC
                LIMIT 6
            ", [$fam->name, $fam->idFamily])->result();

            if (empty($products)) continue;

            // Add client price
            foreach ($products as &$p) {
                $lastPrice = $this->db->query("
                    SELECT d.unit FROM invoice_details d
                    INNER JOIN invoices i ON i.idInvoice = d.invoiceId
                    WHERE i.clientId = ? AND d.productId = ?
                    ORDER BY i.date DESC LIMIT 1
                ", [$clientId, $p->idProduct])->row();
                $p->client_price = $lastPrice ? (float)$lastPrice->unit : (float)$p->price;
            }
            unset($p);

            $sections[] = [
                'familyId'   => $fam->idFamily,
                'familyName' => $fam->name,
                'products'   => $products,
            ];
        }

        $this->api_response->success([
            'sections' => $sections,
        ]);
    }

    /**
     * GET api/client/favorites
     * Top productos comprados por este cliente.
     */
    public function favorites()
    {
        $client = $this->_authenticateClient();

        $products = $this->smartcatalog_model->getClientTopProducts($client->idClient, 30);

        // Add current stock
        foreach ($products as &$p) {
            $stockRow = $this->db->query(
                "SELECT COALESCE(SUM(stock), 0) as total_stock FROM inventory WHERE idProduct = ?",
                [$p->idProduct]
            )->row();
            $p->total_stock = (int)$stockRow->total_stock;
        }
        unset($p);

        $this->api_response->success(['products' => $products]);
    }

    /**
     * GET api/client/orders
     * Presupuestos/pedidos del cliente.
     * Query params: page, limit
     */
    public function orders()
    {
        $client = $this->_authenticateClient();

        $page   = (int) $this->input->get('page') ?: 1;
        $limit  = (int) $this->input->get('limit') ?: 20;
        $offset = ($page - 1) * $limit;

        $budgets = $this->db->select('b.idBudget, b.date, b.total, b.state, b.budget_type,
            s.name as storeName, u.name as vendorName')
            ->from('budgets b')
            ->join('stores s', 's.idStore = b.storeId', 'left')
            ->join('users u', 'u.idUser = b.vendorId', 'left')
            ->where('b.clientId', $client->idClient)
            ->where('b.deleted', 0)
            ->order_by('b.date', 'DESC')
            ->limit($limit, $offset)
            ->get()->result();

        $this->api_response->success([
            'orders' => $budgets,
            'page'   => $page,
            'limit'  => $limit,
        ]);
    }

    /**
     * GET api/client/cartera
     * Cartera pendiente del cliente — facturas sin pagar.
     */
    public function cartera()
    {
        $client = $this->_authenticateClient();

        $invoices = $this->db->query("
            SELECT i.idInvoice, i.if_id, i.date, i.total, i.payment, i.discount,
                   (i.total - i.payment - i.discount) as saldo,
                   DATEDIFF(NOW(), i.date) as dias,
                   s.name as storeName
            FROM invoices i
            LEFT JOIN stores s ON s.idStore = i.storeId
            WHERE i.clientId = ?
              AND (i.state = 0 OR i.state = 1)
              AND i.deleted = 0
              AND (i.total - i.payment - i.discount) > 0
            ORDER BY i.date ASC
        ", [$client->idClient])->result();

        $totalDebt = 0;
        $totalOver30 = 0;
        $totalOver90 = 0;
        foreach ($invoices as $inv) {
            $saldo = (float)$inv->saldo;
            $totalDebt += $saldo;
            if ((int)$inv->dias > 30) $totalOver30 += $saldo;
            if ((int)$inv->dias > 90) $totalOver90 += $saldo;
        }

        $this->api_response->success([
            'invoices' => $invoices,
            'summary'  => [
                'total_debt'   => $totalDebt,
                'total_over30' => $totalOver30,
                'total_over90' => $totalOver90,
                'invoice_count' => count($invoices),
            ]
        ]);
    }

    /**
     * POST api/client/order
     * Crear pedido/presupuesto desde la PWA del cliente.
     * Body: { items: [{productId, quantity, price}], notes }
     */
    public function order()
    {
        $client = $this->_authenticateClient();

        if ($this->input->method() !== 'post') {
            $this->api_response->error('Metodo no permitido', 405);
        }

        $input = json_decode($this->input->raw_input_stream, true);

        if (empty($input['items']) || !is_array($input['items'])) {
            $this->api_response->error('Se requieren productos', 400);
        }

        $items = $input['items'];
        $notes = isset($input['notes']) ? trim($input['notes']) : '';

        // Validate stock for each item
        foreach ($items as $item) {
            if (empty($item['productId']) || empty($item['quantity'])) {
                $this->api_response->error('Cada producto necesita productId y quantity', 400);
            }

            $stockRow = $this->db->query(
                "SELECT COALESCE(SUM(stock), 0) as total_stock FROM inventory WHERE idProduct = ?",
                [$item['productId']]
            )->row();

            if ((int)$stockRow->total_stock < (int)$item['quantity']) {
                $product = $this->products_model->getProduct($item['productId']);
                $name = $product ? $product->description : $item['productId'];
                $this->api_response->error("Stock insuficiente para: $name (disponible: {$stockRow->total_stock})", 400);
            }
        }

        // Calculate total
        $total = 0;
        foreach ($items as &$item) {
            if (empty($item['price'])) {
                $product = $this->products_model->getProduct($item['productId']);
                $item['price'] = $product ? (float)$product->price : 0;
            }
            $total += (float)$item['price'] * (int)$item['quantity'];
        }
        unset($item);

        // Determine vendor: client's assigned vendor, or token generator if not assigned
        $assignedVendor = $client->vendor;
        if (empty($assignedVendor) || $assignedVendor === '00000') {
            // Use the vendor who generated the catalog token
            $assignedVendor = $this->tokenData->vendorId;
        }

        // Determine store from the vendor
        $vendor = $this->db->select('store')->from('users')->where('idUser', $assignedVendor)->get()->row();
        $storeId = $vendor ? $vendor->store : 1;

        // Create budget
        $budgetData = [
            'clientId'    => $client->idClient,
            'storeId'     => $storeId,
            'vendorId'    => $assignedVendor,
            'total'       => $total,
            'state'       => 0, // pendiente
            'budget_type' => 'venta',
            'hasIva'      => 0,
            'comments'    => $notes ? "[Pedido web] $notes" : '[Pedido web]',
            'date'        => date('Y-m-d'),
            'created_at'  => date('Y-m-d H:i:s'),
        ];
        $this->db->insert('budgets', $budgetData);
        $budgetId = $this->db->insert_id();

        // Insert details
        foreach ($items as $item) {
            $this->db->insert('budget_detail', [
                'budgetId'  => $budgetId,
                'productId' => $item['productId'],
                'quantity'  => (int)$item['quantity'],
                'unit'      => (int)$item['price'],
                'base'      => (int)$item['price'],
                'total'     => (int)((float)$item['price'] * (int)$item['quantity']),
            ]);
        }

        // Notificar al vendedor asignado
        $this->db->insert('notifications', [
            'userId'     => $assignedVendor,
            'type'       => 'order',
            'title'      => 'Nuevo pedido web de ' . $client->name,
            'body'       => 'Pedido #' . $budgetId . ' por ' . number_format($total, 0, ',', '.') . ' COP' . ($notes ? " — $notes" : ''),
            'data'       => json_encode(['budgetId' => $budgetId, 'clientId' => $client->idClient, 'total' => $total]),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->api_response->success([
            'budgetId' => $budgetId,
            'total'    => $total,
            'message'  => 'Pedido creado exitosamente',
        ]);
    }

    // ---------------------------------------------------------------
    // AI Chat Assistant (Haiku 4.5)
    // ---------------------------------------------------------------

    /**
     * POST api/client/chat
     * Chat con asistente IA para clientes. Usa Haiku 4.5 para optimizar costos.
     * Body: { messages: [{role, content}] }
     */
    public function chat()
    {
        $client = $this->_authenticateClient();

        if ($this->input->method() !== 'post') {
            $this->api_response->error('Metodo no permitido', 405);
        }

        $input = json_decode($this->input->raw_input_stream, true);
        $messages = isset($input['messages']) ? $input['messages'] : [];

        if (empty($messages)) {
            $this->api_response->error('Se requieren mensajes', 400);
        }

        $api_key = $this->config->item('anthropic_api_key');
        if (empty($api_key)) {
            $this->api_response->success([
                'reply' => 'El asistente no esta disponible en este momento. Contacta a tu vendedor: ' . ($client->vendor_name ?: '') . ($client->vendor_phone ? ' (' . $client->vendor_phone . ')' : '')
            ]);
            return;
        }

        // Build rich context about this client
        $clientName = $client->name;
        $vendorName = $client->vendor_name;
        $vendorPhone = $client->vendor_phone;
        $clientId = $client->idClient;

        // Client's cartera (debt)
        $cartera = $this->db->query("
            SELECT COUNT(*) as facturas, COALESCE(SUM(total - payment - discount), 0) as saldo
            FROM invoices WHERE clientId = ? AND (state = 0 OR state = 1) AND deleted = 0 AND (total - payment - discount) > 0
        ", [$clientId])->row();
        $carteraInfo = $cartera->saldo > 0
            ? "Tiene {$cartera->facturas} facturas pendientes por $" . number_format($cartera->saldo, 0, ',', '.') . " COP."
            : "No tiene saldo pendiente.";

        // Recent orders
        $recentOrders = $this->db->query("
            SELECT idBudget, total, date, state FROM budgets
            WHERE clientId = ? AND deleted = 0 ORDER BY date DESC LIMIT 5
        ", [$clientId])->result();
        $ordersInfo = '';
        foreach ($recentOrders as $o) {
            $estado = $o->state == 0 ? 'pendiente' : ($o->state == 2 ? 'aprobado' : ($o->state == 4 ? 'facturado' : 'otro'));
            $ordersInfo .= "- Pedido #{$o->idBudget}: $" . number_format($o->total, 0, ',', '.') . " ({$estado}, " . substr($o->date, 0, 10) . ")\n";
        }

        // Client's top purchased products
        $topClient = $this->db->query("
            SELECT d.productId, p.description, p.price, SUM(d.quantity) as qty
            FROM invoice_details d
            INNER JOIN invoices i ON i.idInvoice = d.invoiceId
            INNER JOIN products p ON p.idProduct = d.productId
            WHERE i.clientId = ? GROUP BY d.productId ORDER BY qty DESC LIMIT 10
        ", [$clientId])->result();
        $favList = '';
        foreach ($topClient as $tp) {
            $favList .= "- {$tp->productId}: {$tp->description} (\${$tp->price}) — compro {$tp->qty} uds\n";
        }

        // Top products by sales (general)
        $topProducts = $this->db->query("
            SELECT p.idProduct, p.description, p.price, pf.name as family
            FROM products p
            LEFT JOIN product_families pf ON pf.idFamily = p.family
            LEFT JOIN (SELECT idProduct, SUM(stock) as s FROM inventory GROUP BY idProduct) inv ON inv.idProduct = p.idProduct
            WHERE p.deleted = 0 AND COALESCE(inv.s, 0) > 0
            ORDER BY COALESCE(inv.s, 0) DESC
            LIMIT 15
        ")->result();
        $productList = '';
        foreach ($topProducts as $tp) {
            $productList .= "- {$tp->idProduct}: {$tp->description} (\${$tp->price}) [{$tp->family}]\n";
        }

        // Product families
        $families = $this->db->query("SELECT name FROM product_families WHERE deleted = 0 ORDER BY name")->result();
        $familyNames = implode(', ', array_map(function($f) { return $f->name; }, $families));

        $system = "Eres el asistente de ventas de MAM, empresa colombiana de iluminacion LED para vehiculos.

CLIENTE: {$clientName}
VENDEDOR: {$vendorName}" . ($vendorPhone ? " (WhatsApp: {$vendorPhone})" : "") . "

CARTERA DEL CLIENTE:
{$carteraInfo}

ULTIMOS PEDIDOS:
" . ($ordersInfo ?: "Sin pedidos recientes.") . "

PRODUCTOS FAVORITOS DEL CLIENTE:
" . ($favList ?: "Cliente nuevo, sin historial.") . "

CATEGORIAS DISPONIBLES: {$familyNames}

CATALOGO (algunos productos con stock):
{$productList}

REGLAS:
1. SOLO hablas de productos MAM, pedidos, cartera y temas del negocio. Si preguntan otra cosa: 'Solo puedo ayudarte con productos y pedidos de MAM.'
2. Usa los datos reales de arriba (cartera, pedidos, favoritos) para responder.
3. Si pide estado de cuenta o cartera, dale los datos exactos.
4. Si pide info de un pedido, dale los datos exactos.
5. Si no tienes el dato, sugiere contactar al vendedor por WhatsApp.
6. Maximo 4 lineas. Conciso. Espanol colombiano informal, cercano.

PERSONALIDAD - VENDEDORA PROACTIVA:
- Eres como una vendedora estrella: amable, conocedora, y siempre buscando la venta.
- SIEMPRE sugiere productos. Si el cliente saluda, ya le estas recomendando algo: 'Hola! Vi que te gustan los M1-H4, te llegaron nuevas referencias...'
- Si pregunta por un producto, ofrece complementos: 'Ese bombillo queda brutal, y si le metes unas exploradoras el combo queda completo.'
- Usa urgencia real: 'De ese quedan pocas unidades' o 'Ese precio es especial, aprovecha.'
- Recuerda lo que el cliente compra y ofrece reposicion: 'Ya llevas tiempo sin pedir FW-12V, necesitas reabastecer?'
- Si el cliente tiene cartera pendiente, mencionalo con tacto solo si es relevante, nunca como regano.
- Cierra siempre con una pregunta o sugerencia: 'Te lo agrego al carrito?' o 'Que mas necesitas?'";

        // Call Haiku 4.5
        $data = [
            'model' => 'claude-haiku-4-5-20251001',
            'max_tokens' => 300,
            'system' => $system,
            'messages' => array_slice($messages, -10) // last 10 messages only
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
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $this->api_response->success(['reply' => 'Problemas de conexion. Intenta de nuevo o contacta a ' . $vendorName]);
            return;
        }

        $result = json_decode($response, true);

        if (isset($result['content'][0]['text'])) {
            $this->api_response->success(['reply' => $result['content'][0]['text']]);
        } else {
            $this->api_response->success(['reply' => 'No pude procesar tu mensaje. Intenta de nuevo.']);
        }
    }

    /**
     * POST api/client/send-message
     * Cliente envía mensaje al vendedor (escalación o directo).
     * Body: { message }
     */
    public function send_message()
    {
        $client = $this->_authenticateClient();

        if ($this->input->method() !== 'post') {
            $this->api_response->error('Metodo no permitido', 405);
        }

        $input = json_decode($this->input->raw_input_stream, true);
        $message = isset($input['message']) ? trim($input['message']) : '';

        if (empty($message)) {
            $this->api_response->error('Mensaje vacio', 400);
        }

        // Determine vendor
        $vendorId = $client->vendor;
        if (empty($vendorId) || $vendorId === '00000') {
            $vendorId = $this->tokenData->vendorId;
        }

        // Save message
        $this->db->insert('chat_messages', [
            'clientId'   => $client->idClient,
            'vendorId'   => $vendorId,
            'sender'     => 'client',
            'message'    => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Notify vendor
        $this->db->insert('notifications', [
            'userId'     => $vendorId,
            'type'       => 'message',
            'title'      => 'Mensaje de ' . $client->name,
            'body'       => mb_substr($message, 0, 100),
            'data'       => json_encode(['clientId' => $client->idClient, 'clientName' => $client->name]),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->api_response->success([
            'sent' => true,
            'vendor_name'  => $client->vendor_name,
            'vendor_phone' => $client->vendor_phone,
        ]);
    }

    /**
     * GET api/client/messages
     * Historial de mensajes entre este cliente y su vendedor.
     */
    public function messages()
    {
        $client = $this->_authenticateClient();

        $vendorId = $client->vendor;
        if (empty($vendorId) || $vendorId === '00000') {
            $vendorId = $this->tokenData->vendorId;
        }

        $messages = $this->db->select('*')
            ->from('chat_messages')
            ->where('clientId', $client->idClient)
            ->where('vendorId', $vendorId)
            ->order_by('created_at', 'ASC')
            ->limit(50)
            ->get()->result();

        // Mark vendor messages as read
        $this->db->where('clientId', $client->idClient)
            ->where('vendorId', $vendorId)
            ->where('sender', 'vendor')
            ->where('read_at IS NULL')
            ->update('chat_messages', ['read_at' => date('Y-m-d H:i:s')]);

        $this->api_response->success([
            'messages'     => $messages,
            'vendor_name'  => $client->vendor_name,
            'vendor_phone' => $client->vendor_phone,
        ]);
    }

    // ---------------------------------------------------------------
    // Token Generation (JWT auth — for vendors)
    // ---------------------------------------------------------------

    /**
     * POST api/client/generate-token
     * Genera un token de acceso para un cliente.
     * Body: { clientId, expiresInDays (optional, default 90) }
     * Requires JWT (vendedor auth).
     */
    public function generate_token()
    {
        if ($this->input->method() !== 'post') {
            $this->api_response->error('Metodo no permitido', 405);
        }

        // Authenticate vendor via JWT
        $payload = $this->_authenticateJWT();

        $input = json_decode($this->input->raw_input_stream, true);

        if (empty($input['clientId'])) {
            $this->api_response->error('Se requiere clientId', 400);
        }

        $clientId = (int)$input['clientId'];
        $expiresInDays = isset($input['expiresInDays']) ? (int)$input['expiresInDays'] : 90;

        // Verify client exists
        $client = $this->clients_model->getClient($clientId);
        if (!$client) {
            $this->api_response->error('Cliente no encontrado', 404);
        }

        // Generate unique token
        $token = bin2hex(random_bytes(32));

        $this->db->insert('client_tokens', [
            'token'      => $token,
            'clientId'   => $clientId,
            'vendorId'   => $payload->sub,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expiresInDays} days")),
            'active'     => 1,
        ]);

        // Build the client PWA URL
        $baseUrl = base_url('pwa/clientes/');
        $link = $baseUrl . '?t=' . $token;

        $this->api_response->success([
            'token'   => $token,
            'link'    => $link,
            'client'  => $client->name,
            'expires' => date('Y-m-d', strtotime("+{$expiresInDays} days")),
        ]);
    }

    /**
     * JWT auth helper (for vendor-facing endpoints like generate-token)
     */
    private function _authenticateJWT()
    {
        $headers = $this->input->request_headers();
        $token = null;

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
