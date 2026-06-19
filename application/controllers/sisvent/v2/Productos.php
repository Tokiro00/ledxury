<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ledxury v2 · Pulso — Productos (listado)
 */
class Productos extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control();
        $this->load->model('products_model');
    }

    public function index()
    {
        $page  = max(1, (int) ($this->input->get('p') ?: 1));
        $limit = 30;
        $term  = trim((string)$this->input->get('q'));

        if ($term !== '') {
            $total    = (int) $this->products_model->getTotalSearch($term);
            $products = $this->products_model->getProductsByWord($term, $page, $limit);
        } else {
            $total    = (int) $this->products_model->getTotal();
            $products = $this->products_model->getProductsPag($page, $limit);
        }
        $lastPage = max(1, (int) ceil($total / $limit));

        // KPIs: total, sin stock, stock bajo, valor de inventario
        $tid = (int) current_tenant_id();
        $kpis = $this->db->query("
            SELECT
                COUNT(*) AS total_skus,
                COALESCE(AVG(p.price), 0) AS precio_promedio
            FROM products p
            WHERE COALESCE(p.deleted, 0) = 0 AND p.tenant_id = ?
        ", array($tid))->row();

        $stock = $this->db->query("
            SELECT
                COUNT(DISTINCT i.idProduct) AS con_stock,
                COALESCE(SUM(i.stock), 0) AS unidades,
                COALESCE(SUM(i.stock * p.cost), 0) AS valor_inv
            FROM inventory i
            LEFT JOIN products p ON p.idProduct = i.idProduct
            WHERE i.stock > 0 AND i.tenant_id = ?
        ", array($tid))->row();

        // Sin stock = products no presentes en inventory con stock > 0
        $sinStock = $this->db->query("
            SELECT COUNT(*) AS n
            FROM products p
            WHERE COALESCE(p.deleted, 0) = 0 AND p.tenant_id = ?
              AND NOT EXISTS (
                SELECT 1 FROM inventory i WHERE i.idProduct = p.idProduct AND i.stock > 0
              )
        ", array($tid))->row();

        $data = array(
            'pageTitle'   => 'Productos',
            'activeRoute' => 'productos',
            'breadcrumbs' => array('Operación', 'Productos'),
            'products'    => $products,
            'page'        => $page,
            'lastPage'    => $lastPage,
            'total'       => $total,
            'term'        => $term,
            'kpiTotal'    => (int)($kpis->total_skus ?? 0),
            'kpiPrecio'   => (float)($kpis->precio_promedio ?? 0),
            'kpiConStock' => (int)($stock->con_stock ?? 0),
            'kpiUnidades' => (int)($stock->unidades ?? 0),
            'kpiValorInv' => (float)($stock->valor_inv ?? 0),
            'kpiSinStock' => (int)($sinStock->n ?? 0),
        );
        $this->load->view('sisvent/v2/pulso/productos/index', $data);
    }
}
