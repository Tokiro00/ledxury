<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboard de Devoluciones
 *
 * Muestra tasa de devolución global y desglosada por SKU, ciudad, vendor
 * y día. Usa shipping_guides.estadoNombre = "Devuelto" como criterio (es lo
 * que Interrapidísimo reporta vía API cuando una guía vuelve a origen).
 *
 * Objetivo: detectar patrones (qué producto, qué zona, qué vendedor genera
 * más devoluciones) para atacar la causa raíz y bajar del 15% que tenemos hoy.
 */
class ReturnsDashboard extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1, 10]); // solo admin
    }

    public function index()
    {
        // Filtros de rango — por defecto últimos 30 días
        $desde = $this->input->get('desde') ?: date('Y-m-d', strtotime('-30 days'));
        $hasta = $this->input->get('hasta') ?: date('Y-m-d');
        $store = $this->input->get('store') ?: 'all';

        // Validación básica
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = date('Y-m-d', strtotime('-30 days'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = date('Y-m-d');

        $where_store = '';
        $params_store = array();
        if ($store !== 'all' && is_numeric($store)) {
            $where_store = " AND i.storeId = ? ";
            $params_store = array((int)$store);
        }

        // ── KPIs principales ──────────────────────────────────────────
        $kpi_sql = "SELECT
            COUNT(*) AS total_guias,
            SUM(CASE WHEN sg.estadoNombre = 'Devuelto' THEN 1 ELSE 0 END) AS devueltas,
            SUM(CASE WHEN sg.estadoNombre IN ('Conciliado','Archivada') THEN 1 ELSE 0 END) AS entregadas,
            SUM(CASE WHEN sg.estadoNombre IN ('Reparto','Transito nacional','Transito regional','Admitida','Centro acopio','Creado','Digitalizada') THEN 1 ELSE 0 END) AS en_curso,
            SUM(CASE WHEN sg.estadoNombre IN ('Anulada') THEN 1 ELSE 0 END) AS anuladas,
            SUM(CASE WHEN sg.estadoNombre = 'Devuelto' THEN COALESCE(sg.contrapagoCost, 0) ELSE 0 END) AS valor_devuelto
            FROM shipping_guides sg
            JOIN invoices i ON i.idInvoice = sg.invoiceId
            WHERE DATE(sg.created_at) BETWEEN ? AND ? $where_store";
        $params = array_merge(array($desde, $hasta), $params_store);
        $kpis = $this->db->query($kpi_sql, $params)->row();

        $kpis->tasa_pct = ($kpis->total_guias > 0)
            ? round(100 * $kpis->devueltas / $kpis->total_guias, 1)
            : 0;
        $kpis->tasa_entregadas_pct = ($kpis->total_guias > 0)
            ? round(100 * $kpis->entregadas / $kpis->total_guias, 1)
            : 0;

        // ── Top SKUs devueltos ────────────────────────────────────────
        $sku_sql = "SELECT bd.productId, p.description,
                COUNT(DISTINCT sg.id) AS dev_guias,
                SUM(bd.quantity) AS dev_unidades,
                (SELECT COUNT(DISTINCT sg2.id)
                 FROM shipping_guides sg2
                 JOIN invoices i2 ON i2.idInvoice = sg2.invoiceId
                 JOIN invoice_details bd2 ON bd2.invoiceId = i2.idInvoice
                 WHERE bd2.productId = bd.productId
                   AND DATE(sg2.created_at) BETWEEN ? AND ?
                ) AS total_envios_sku
            FROM shipping_guides sg
            JOIN invoices i ON i.idInvoice = sg.invoiceId
            JOIN invoice_details bd ON bd.invoiceId = i.idInvoice
            LEFT JOIN products p ON p.idProduct = bd.productId
            WHERE sg.estadoNombre = 'Devuelto'
              AND DATE(sg.created_at) BETWEEN ? AND ? $where_store
            GROUP BY bd.productId, p.description
            ORDER BY dev_guias DESC LIMIT 15";
        $sku_params = array_merge(array($desde, $hasta, $desde, $hasta), $params_store);
        $top_skus = $this->db->query($sku_sql, $sku_params)->result();
        foreach ($top_skus as $r) {
            $r->tasa_pct = ($r->total_envios_sku > 0)
                ? round(100 * $r->dev_guias / $r->total_envios_sku, 1)
                : 0;
        }

        // ── Top ciudades devueltas ────────────────────────────────────
        $city_sql = "SELECT sg.ciudadDestinoNombre, COUNT(*) AS dev_guias,
                (SELECT COUNT(*) FROM shipping_guides sg2
                 JOIN invoices i2 ON i2.idInvoice = sg2.invoiceId
                 WHERE sg2.ciudadDestinoNombre = sg.ciudadDestinoNombre
                   AND DATE(sg2.created_at) BETWEEN ? AND ?
                ) AS total_envios_ciudad
            FROM shipping_guides sg
            JOIN invoices i ON i.idInvoice = sg.invoiceId
            WHERE sg.estadoNombre = 'Devuelto'
              AND DATE(sg.created_at) BETWEEN ? AND ? $where_store
            GROUP BY sg.ciudadDestinoNombre
            ORDER BY dev_guias DESC LIMIT 15";
        $city_params = array_merge(array($desde, $hasta, $desde, $hasta), $params_store);
        $top_ciudades = $this->db->query($city_sql, $city_params)->result();
        foreach ($top_ciudades as $r) {
            $r->tasa_pct = ($r->total_envios_ciudad > 0)
                ? round(100 * $r->dev_guias / $r->total_envios_ciudad, 1)
                : 0;
        }

        // ── Vendor / vendedor ─────────────────────────────────────────
        $vendor_sql = "SELECT u.name AS vendor_name,
                COUNT(*) AS total_guias,
                SUM(CASE WHEN sg.estadoNombre = 'Devuelto' THEN 1 ELSE 0 END) AS dev_guias
            FROM shipping_guides sg
            JOIN invoices i ON i.idInvoice = sg.invoiceId
            LEFT JOIN users u ON u.idUser = i.vendorId
            WHERE DATE(sg.created_at) BETWEEN ? AND ? $where_store
            GROUP BY u.name HAVING total_guias >= 3
            ORDER BY dev_guias DESC LIMIT 15";
        $top_vendors = $this->db->query($vendor_sql, $params)->result();
        foreach ($top_vendors as $r) {
            $r->tasa_pct = ($r->total_guias > 0)
                ? round(100 * $r->dev_guias / $r->total_guias, 1)
                : 0;
        }

        // ── Tendencia diaria ──────────────────────────────────────────
        $trend_sql = "SELECT DATE(sg.created_at) AS dia,
                COUNT(*) AS total,
                SUM(CASE WHEN sg.estadoNombre = 'Devuelto' THEN 1 ELSE 0 END) AS dev
            FROM shipping_guides sg
            JOIN invoices i ON i.idInvoice = sg.invoiceId
            WHERE DATE(sg.created_at) BETWEEN ? AND ? $where_store
            GROUP BY DATE(sg.created_at)
            ORDER BY dia ASC";
        $tendencia = $this->db->query($trend_sql, $params)->result();

        // ── Lista detallada de devoluciones (con motivo si existe) ────
        $detail_sql = "SELECT sg.id, sg.numeroPreenvio, sg.created_at, sg.estadoNombre,
                sg.observations, sg.ciudadDestinoNombre, sg.contrapagoCost,
                c.name AS client_name, c.cellphone,
                u.name AS vendor_name, i.idInvoice
            FROM shipping_guides sg
            JOIN invoices i ON i.idInvoice = sg.invoiceId
            JOIN clients c ON c.idClient = i.clientId
            LEFT JOIN users u ON u.idUser = i.vendorId
            WHERE sg.estadoNombre = 'Devuelto'
              AND DATE(sg.created_at) BETWEEN ? AND ? $where_store
            ORDER BY sg.created_at DESC LIMIT 50";
        $detalle = $this->db->query($detail_sql, $params)->result();

        $this->load->model('stores_model');

        $data = array(
            'thisFile'     => 'sisvent/admin/returnsdashboard/index',
            'desde'        => $desde,
            'hasta'        => $hasta,
            'store'        => $store,
            'stores'       => $this->stores_model->getStores(),
            'kpis'         => $kpis,
            'top_skus'     => $top_skus,
            'top_ciudades' => $top_ciudades,
            'top_vendors'  => $top_vendors,
            'tendencia'    => $tendencia,
            'detalle'      => $detalle,
        );
        $this->load->view('sisvent/admin/returnsdashboard/index', $data);
    }
}
