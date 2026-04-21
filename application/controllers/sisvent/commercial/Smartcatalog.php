<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Smart Catalog — Catálogo Inteligente + Dashboard Cliente + Fidelización
 *
 * Módulo integrado que combina:
 * - Catálogo visual con clasificación ABC
 * - Dashboard por cliente (facturas, historial, top productos)
 * - Clasificación automática de clientes A/B/C
 * - Recomendaciones inteligentes
 * - Compartir catálogos por WhatsApp
 * - Campañas de fidelización (clientes dormidos, huérfanos)
 */
class Smartcatalog extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->backend_lib->control();
        $this->load->model('Smartcatalog_model');
        $this->load->model('stores_model');
        $this->load->model('clients_model');
        $this->load->model('products_model');
    }

    /**
     * Catálogo inteligente con filtros
     * URL: /sisvent/commercial/smartcatalog
     */
    public function index() {
        $role    = $this->session->userdata('user_data')['role'];
        $storeId = $this->input->get('store') ? (int) $this->input->get('store') : 0;
        $familyId = $this->input->get('f') ? (int) $this->input->get('f') : 0;
        $search   = $this->input->get('q') ? trim($this->input->get('q')) : '';
        $onlyImg  = $this->input->get('img') === '1';
        $page     = $this->input->get('p') ? (int) $this->input->get('p') : 1;
        $limit    = 48;

        $total = $this->Smartcatalog_model->countSmartProducts($familyId, $search, $onlyImg);
        $last = ceil($total / $limit);
        if ($page > $last) $page = $last;
        if ($page <= 0) $page = 1;

        $data = [
            'products'  => $this->Smartcatalog_model->getSmartProducts($familyId, $search, $onlyImg, $storeId, $limit, ($page - 1) * $limit),
            'families'  => $this->Smartcatalog_model->getFamiliesWithCount(),
            'stores'    => $this->stores_model->getStores(),
            'stats'     => $this->Smartcatalog_model->getCatalogStats(),
            'familyId'  => $familyId,
            'search'    => $search,
            'onlyImg'   => $onlyImg,
            'storeId'   => $storeId,
            'total'     => $total,
            'page'      => $page,
            'limit'     => $limit,
            'role'      => $role,
        ];

        $this->load->view('sisvent/commercial/smartcatalog/index', $data);
    }

    /**
     * Detalle de producto (AJAX)
     * POST /sisvent/commercial/smartcatalog/product
     */
    public function product() {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $code = $this->input->post('code');
        $product = $this->db->select('p.*, f.name AS family_name')
            ->from('products p')
            ->join('product_families f', 'f.idFamily = p.family', 'left')
            ->where('p.idProduct', $code)
            ->get()->row();

        if ($product) {
            $product->stock = $this->Smartcatalog_model->getProductStock($code);
        }

        echo json_encode($product);
    }

    // ================================================================
    // DASHBOARD DE CLIENTES (clasificación ABC)
    // ================================================================

    /**
     * Vista de clientes clasificados A/B/C
     * URL: /sisvent/commercial/smartcatalog/clients
     */
    public function clients() {
        $role = $this->session->userdata('user_data')['role'];
        $vendorId = $this->session->userdata('user_data')['uname'];
        $filter = $this->input->get('abc') ?: '';
        $statusFilter = $this->input->get('status') ?: '';
        $search = $this->input->get('q') ?: '';

        $data = $this->Smartcatalog_model->getClientABCSummary();

        // Filtrar si es vendedor (solo sus clientes)
        if ($role == 3) {
            $data->clients = array_filter($data->clients, function($c) use ($vendorId) {
                return $c->vendor === $vendorId;
            });
            $data->clients = array_values($data->clients);
        }

        // Filtrar por ABC
        if ($filter) {
            $data->clients = array_filter($data->clients, function($c) use ($filter) {
                return $c->abc_class === strtoupper($filter);
            });
            $data->clients = array_values($data->clients);
        }

        // Filtrar por status
        if ($statusFilter) {
            $data->clients = array_filter($data->clients, function($c) use ($statusFilter) {
                return $c->status === $statusFilter;
            });
            $data->clients = array_values($data->clients);
        }

        // Buscar
        if ($search) {
            $q = strtolower($search);
            $data->clients = array_filter($data->clients, function($c) use ($q) {
                return strpos(strtolower($c->name), $q) !== false
                    || strpos(strtolower($c->commercial_name ?? ''), $q) !== false
                    || strpos(strtolower($c->city ?? ''), $q) !== false;
            });
            $data->clients = array_values($data->clients);
        }

        $viewData = [
            'data'         => $data,
            'filter'       => $filter,
            'statusFilter' => $statusFilter,
            'search'       => $search,
            'role'         => $role,
        ];

        $this->load->view('sisvent/commercial/smartcatalog/clients', $viewData);
    }

    /**
     * Dashboard individual de un cliente
     * URL: /sisvent/commercial/smartcatalog/clientview/123
     */
    public function clientview($clientId = 0) {
        $clientId = (int) $clientId;
        if (!$clientId) { show_404(); return; }

        $role = $this->session->userdata('user_data')['role'];
        $dashboard = $this->Smartcatalog_model->getClientDashboard($clientId);

        if (!$dashboard) { show_404(); return; }

        // Recomendaciones
        $dashboard->recommendations = $this->Smartcatalog_model->getRecommendations($clientId, 8);

        $data = [
            'd'    => $dashboard,
            'role' => $role,
        ];

        $this->load->view('sisvent/commercial/smartcatalog/clientview', $data);
    }

    // ================================================================
    // FIDELIZACIÓN
    // ================================================================

    /**
     * Panel de clientes dormidos y huérfanos
     * URL: /sisvent/commercial/smartcatalog/fidelizacion
     */
    public function fidelizacion() {
        $role = $this->session->userdata('user_data')['role'];
        if (!in_array($role, [1, 2])) { show_404(); return; }

        $data = [
            'dormidos'  => $this->Smartcatalog_model->getSleepingClients(),
            'huerfanos' => $this->Smartcatalog_model->getOrphanClients(),
            'ranking'   => $this->Smartcatalog_model->getVendorRanking(),
            'role'      => $role,
        ];

        $this->load->view('sisvent/commercial/smartcatalog/fidelizacion', $data);
    }

    /**
     * Generar catálogo personalizado para un cliente (compartir WhatsApp)
     * URL: /sisvent/commercial/smartcatalog/clientcatalog/123
     */
    public function clientcatalog($clientId = 0) {
        $clientId = (int) $clientId;
        if (!$clientId) { show_404(); return; }

        $client = $this->db->where('idClient', $clientId)->get('clients')->row();
        if (!$client) { show_404(); return; }

        $vendorId = $this->input->get('v') ?: $client->vendor;
        $vendor = $this->db->select('name, phone')->where('idUser', $vendorId)->get('users')->row();

        // Filtros del catálogo completo
        $familyId = $this->input->get('f') ? (int) $this->input->get('f') : 0;
        $search = $this->input->get('q') ? trim($this->input->get('q')) : '';
        $tab = $this->input->get('tab') ?: 'favoritos';

        $data = [
            'client'         => $client,
            'vendor'         => $vendor,
            'products'       => $this->Smartcatalog_model->getClientTopProducts($clientId, 30),
            'recommendations'=> $this->Smartcatalog_model->getRecommendations($clientId, 12),
            'allProducts'    => $this->Smartcatalog_model->getSmartProducts($familyId, $search, false, 0, 48, 0),
            'families'       => $this->Smartcatalog_model->getFamiliesWithCount(),
            'familyId'       => $familyId,
            'search'         => $search,
            'tab'            => $tab,
            'role'           => $this->session->userdata('user_data')['role'] ?? 0,
        ];

        $this->load->view('sisvent/commercial/smartcatalog/clientcatalog', $data);
    }

    // ================================================================
    // GESTIÓN DE CATÁLOGO DE OFERTAS (admin)
    // ================================================================

    /**
     * Panel de gestión de ofertas/remate
     * URL: /sisvent/commercial/smartcatalog/ofertas
     */
    public function ofertas() {
        $this->backend_lib->control([1, 2, 10]); // Solo admin y gerente
        $tab = $this->input->get('tab') ?: 'ofertas';
        $search = $this->input->get('q') ?: '';
        $familyId = (int)$this->input->get('f') ?: 0;

        // Productos en oferta (dormidos 3+ meses, stock >= 5)
        $ofertas = $this->db->query("
            SELECT p.idProduct, p.description, p.price, p.cost_cop, p.picture_url,
                   p.family, pf.name as familyName,
                   COALESCE(inv.total_stock, 0) as total_stock,
                   s.ultima_venta,
                   DATEDIFF(NOW(), s.ultima_venta) as dias_sin_venta,
                   co.id as override_id, co.tab as override_tab, co.price_override, co.discount_override, co.notes as override_notes
            FROM products p
            LEFT JOIN product_families pf ON pf.idFamily = p.family
            LEFT JOIN (SELECT idProduct, SUM(stock) as total_stock FROM inventory GROUP BY idProduct) inv ON inv.idProduct = p.idProduct
            LEFT JOIN (SELECT d.productId, MAX(i.date) as ultima_venta FROM invoice_details d INNER JOIN invoices i ON i.idInvoice = d.invoiceId GROUP BY d.productId) s ON s.productId = p.idProduct
            LEFT JOIN catalog_overrides co ON co.productId = p.idProduct AND co.active = 1
            WHERE p.deleted = 0
              AND COALESCE(inv.total_stock, 0) > 0
              AND (co.tab IS NOT NULL OR (s.ultima_venta IS NULL OR s.ultima_venta < DATE_SUB(NOW(), INTERVAL 3 MONTH)))
              AND (co.tab IS NULL OR co.tab != 'excluido')
            " . ($familyId ? "AND p.family = $familyId" : "") . "
            " . ($search ? "AND (p.description LIKE '%" . $this->db->escape_like_str($search) . "%' OR p.idProduct LIKE '%" . $this->db->escape_like_str($search) . "%')" : "") . "
            ORDER BY dias_sin_venta DESC
            LIMIT 200
        ")->result();

        // Calcular precio automático para cada producto
        foreach ($ofertas as &$p) {
            $dias = (int)$p->dias_sin_venta;
            $precioLista = (float)$p->price;
            $costo = (float)$p->cost_cop;
            $stock = (int)$p->total_stock;

            // Precio automático
            if ($stock < 5) {
                // Remate
                if ($stock <= 1) $autoPrice = $costo > 0 ? min($precioLista * 0.20, $costo) : $precioLista * 0.20;
                elseif ($stock <= 2) $autoPrice = $costo > 0 ? min($precioLista * 0.30, $costo * 0.90) : $precioLista * 0.30;
                else $autoPrice = $costo > 0 ? min($precioLista * 0.40, $costo * 0.95) : $precioLista * 0.40;
                $p->auto_tab = 'remate';
            } else {
                if ($dias > 730 && $costo > 0) { $autoPrice = $costo; $p->auto_tab = 'ofertas'; }
                elseif ($dias > 365) { $autoPrice = $precioLista * 0.30; $p->auto_tab = 'ofertas'; }
                elseif ($dias > 270) { $autoPrice = $precioLista * 0.45; $p->auto_tab = 'ofertas'; }
                elseif ($dias > 180) { $autoPrice = $precioLista * 0.60; $p->auto_tab = 'ofertas'; }
                else { $autoPrice = $precioLista * 0.75; $p->auto_tab = 'ofertas'; }
                if ($costo > 0 && $autoPrice < $costo && $dias <= 730) $autoPrice = $costo;
            }

            $p->auto_price = round($autoPrice);
            $p->auto_discount = $precioLista > 0 ? round((1 - $autoPrice / $precioLista) * 100) : 0;
            $p->final_price = $p->price_override ?: $p->auto_price;
            $p->final_tab = $p->override_tab ?: $p->auto_tab;
        }
        unset($p);

        // Productos excluidos
        $excluidos = $this->db->select('co.*, p.description, p.price, p.picture_url')
            ->from('catalog_overrides co')
            ->join('products p', 'p.idProduct = co.productId')
            ->where('co.tab', 'excluido')
            ->where('co.active', 1)
            ->get()->result();

        $data = [
            'ofertas'   => $ofertas,
            'excluidos' => $excluidos,
            'families'  => $this->Smartcatalog_model->getFamiliesWithCount(),
            'tab'       => $tab,
            'search'    => $search,
            'familyId'  => $familyId,
        ];

        $this->load->view('sisvent/commercial/smartcatalog/ofertas', $data);
    }

    /**
     * AJAX: Guardar override de un producto
     * POST /sisvent/commercial/smartcatalog/saveOverride
     */
    public function saveOverride() {
        $this->backend_lib->control([1, 2, 10]);
        if ($this->input->method() !== 'post') exit;

        $productId = $this->input->post('productId');
        $tab = $this->input->post('tab') ?: null;
        $priceOverride = $this->input->post('price_override') ? (float)$this->input->post('price_override') : null;
        $discountOverride = $this->input->post('discount_override') !== '' ? (int)$this->input->post('discount_override') : null;
        $notes = $this->input->post('notes') ?: null;
        $userId = $this->session->userdata('user_data')['uname'];

        if (empty($productId)) {
            echo json_encode(['success' => false, 'message' => 'ProductId requerido']);
            return;
        }

        // Upsert
        $existing = $this->db->where('productId', $productId)->get('catalog_overrides')->row();

        $data = [
            'productId'         => $productId,
            'tab'               => $tab,
            'price_override'    => $priceOverride,
            'discount_override' => $discountOverride,
            'notes'             => $notes,
            'active'            => 1,
            'updated_by'        => $userId,
        ];

        if ($existing) {
            $this->db->where('productId', $productId)->update('catalog_overrides', $data);
        } else {
            $this->db->insert('catalog_overrides', $data);
        }

        echo json_encode(['success' => true]);
    }

    /**
     * AJAX: Eliminar override (volver a automático)
     * POST /sisvent/commercial/smartcatalog/deleteOverride
     */
    public function deleteOverride() {
        $this->backend_lib->control([1, 2, 10]);
        if ($this->input->method() !== 'post') exit;

        $productId = $this->input->post('productId');
        $this->db->where('productId', $productId)->delete('catalog_overrides');
        echo json_encode(['success' => true]);
    }
}
