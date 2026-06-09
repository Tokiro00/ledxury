<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ledxury v2 · Pulso — Clientes (listado)
 * Read-only view del Clients existente con paleta Pulso.
 */
class Clientes extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control();
        $this->load->model('clients_model');
        $this->load->model('users_model');
    }

    public function index()
    {
        $page  = max(1, (int) ($this->input->get('p') ?: 1));
        $limit = 30;
        $term  = trim((string)$this->input->get('q'));

        if ($term !== '') {
            $total   = (int) $this->clients_model->getTotalSearch($term);
            $clients = $this->clients_model->getClientsByWord($term, $page, $limit);
        } else {
            $total   = (int) $this->clients_model->clientCount(true);
            $clients = $this->clients_model->getClientsPag($page, $limit);
        }
        $lastPage = max(1, (int) ceil($total / $limit));

        // KPIs en vivo
        $kpis = $this->db->query("
            SELECT
                COUNT(*) AS total,
                COUNT(CASE WHEN created_at >= DATE_FORMAT(CURDATE(),'%Y-%m-01') THEN 1 END) AS nuevos_mes,
                COUNT(CASE WHEN blacklisted = 1 THEN 1 END) AS blacklisted,
                COUNT(CASE WHEN can_bill = 1 THEN 1 END) AS pueden_facturar
            FROM clients
            WHERE COALESCE(deleted,0) = 0
        ")->row();

        // Top 5 ciudades
        $topCities = $this->db->query("
            SELECT city, COUNT(*) AS n
            FROM clients
            WHERE COALESCE(deleted,0) = 0 AND city != ''
            GROUP BY city ORDER BY n DESC LIMIT 5
        ")->result();

        $data = array(
            'pageTitle'   => 'Clientes',
            'activeRoute' => 'clientes',
            'breadcrumbs' => array('Operación', 'Clientes'),
            'clients'     => $clients,
            'page'        => $page,
            'lastPage'    => $lastPage,
            'total'       => $total,
            'term'        => $term,
            'kpis'        => $kpis,
            'topCities'   => $topCities,
        );
        $this->load->view('sisvent/v2/pulso/clientes/index', $data);
    }
}
