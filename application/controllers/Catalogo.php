<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Módulo Catálogo Digital MAM
 *
 * Controller para catálogo B2B con:
 * - Catálogo visual con fotos, filtros, búsqueda
 * - Presupuestos vinculados a budgets
 * - Compartir por WhatsApp (catálogos dinámicos por filtro)
 * - Vinculado a vendedor y cliente
 * - Soporte dropshipping
 *
 * Instalación: Copiar este archivo en application/controllers/
 *
 * @author Claude Code
 * @version 1.0
 * @date 2026-03-25
 */
class Catalogo extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Catalogo_model');
        $this->load->library('session');
    }

    /**
     * Página principal del catálogo
     * URL: /catalogo
     * Params: ?f=familia&q=busqueda&img=1&v=vendorId&c=clientId
     */
    public function index() {
        $data = [];

        // Parámetros de filtro
        $familyId      = $this->input->get('f') ? (int) $this->input->get('f') : 0;
        $search        = $this->input->get('q') ? trim($this->input->get('q')) : '';
        $onlyWithImg   = $this->input->get('img') === '1';
        $vendorId      = $this->input->get('v') ? trim($this->input->get('v')) : '';
        $clientId      = $this->input->get('c') ? (int) $this->input->get('c') : 0;
        $storeId       = $this->input->get('store') ? (int) $this->input->get('store') : 0;
        $onlyAvailable = $this->input->get('disp') === '1';

        // Obtener datos
        $this->load->model('stores_model');
        $data['families']   = $this->Catalogo_model->get_families();
        $data['products']   = $this->Catalogo_model->get_products($familyId, $search, $onlyWithImg, 80, 0, $storeId, $onlyAvailable);
        $data['stats']      = $this->Catalogo_model->get_stats();
        $data['stores']     = $this->stores_model->getStores();

        // Filtros activos
        $data['familyId']      = $familyId;
        $data['search']        = $search;
        $data['onlyWithImg']   = $onlyWithImg;
        $data['vendorId']      = $vendorId;
        $data['clientId']      = $clientId;
        $data['storeId']       = $storeId;
        $data['onlyAvailable'] = $onlyAvailable;

        // Info del vendedor (si viene por URL)
        if ($vendorId) {
            $data['vendor'] = $this->Catalogo_model->get_vendor($vendorId);
        }

        // Info del cliente (si viene por URL)
        if ($clientId) {
            $data['client'] = $this->Catalogo_model->get_client($clientId);
        }

        // Nombre de familia activa
        $data['familyName'] = $familyId > 0
            ? $this->Catalogo_model->get_family_name($familyId)
            : 'Catálogo completo';

        $this->load->view('catalogo/index', $data);
    }

    /**
     * API: Buscar productos (AJAX)
     * URL: /catalogo/buscar
     */
    public function buscar() {
        $familyId    = $this->input->get('f') ? (int) $this->input->get('f') : 0;
        $search      = $this->input->get('q') ? trim($this->input->get('q')) : '';
        $onlyWithImg = $this->input->get('img') === '1';
        $page        = $this->input->get('page') ? (int) $this->input->get('page') : 1;
        $perPage     = 60;

        $products = $this->Catalogo_model->get_products($familyId, $search, $onlyWithImg, $perPage, ($page - 1) * $perPage);
        $total    = $this->Catalogo_model->count_products($familyId, $search, $onlyWithImg);

        header('Content-Type: application/json');
        echo json_encode([
            'products'  => $products,
            'total'     => $total,
            'page'      => $page,
            'pages'     => ceil($total / $perPage),
        ]);
    }

    /**
     * API: Detalle de un producto
     * URL: /catalogo/producto/CODIGO
     */
    public function producto($code = '') {
        if (empty($code)) {
            show_404();
            return;
        }

        $product = $this->Catalogo_model->get_product($code);

        if (!$product) {
            show_404();
            return;
        }

        // Stock por tienda
        $product->stock = $this->Catalogo_model->get_stock($code);

        header('Content-Type: application/json');
        echo json_encode($product);
    }

    /**
     * Crear presupuesto desde el catálogo
     * POST /catalogo/crear_presupuesto
     *
     * Body JSON: {
     *   clientId: 123,
     *   vendorId: "98765432",
     *   storeId: 7,
     *   items: [{productId: "M1-H4", quantity: 10}, ...]
     * }
     */
    public function crear_presupuesto() {
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }

        $json = json_decode(file_get_contents('php://input'), true);

        if (!$json || empty($json['items'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Datos incompletos']);
            return;
        }

        $result = $this->Catalogo_model->create_budget(
            $json['clientId'] ?? 0,
            $json['vendorId'] ?? '',
            $json['storeId'] ?? 7, // Default: Barranquilla
            $json['items']
        );

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Vista compartible por WhatsApp (catálogo filtrado)
     * URL: /catalogo/compartir/HASH
     * El hash codifica: familia + búsqueda + vendedor
     */
    public function compartir($hash = '') {
        $params = $this->_decode_share_hash($hash);

        $data = [];
        $data['families']  = $this->Catalogo_model->get_families();
        $data['products']  = $this->Catalogo_model->get_products(
            $params['f'] ?? 0,
            $params['q'] ?? '',
            true // Solo con imagen para catálogos compartidos
        );
        $data['familyId']   = $params['f'] ?? 0;
        $data['search']     = $params['q'] ?? '';
        $data['vendorId']   = $params['v'] ?? '';
        $data['isShared']   = true;
        $data['familyName'] = ($params['f'] ?? 0) > 0
            ? $this->Catalogo_model->get_family_name($params['f'])
            : 'Catálogo';

        if (!empty($params['v'])) {
            $data['vendor'] = $this->Catalogo_model->get_vendor($params['v']);
        }

        $this->load->view('catalogo/shared', $data);
    }

    /**
     * Generar hash para compartir
     */
    private function _encode_share_hash($params) {
        return base64_encode(json_encode($params));
    }

    private function _decode_share_hash($hash) {
        if (empty($hash)) return [];
        $decoded = base64_decode($hash);
        return json_decode($decoded, true) ?: [];
    }
}
