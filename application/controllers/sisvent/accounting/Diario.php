<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Diario Controller
 *
 * Redirige a Entries controller para mantener compatibilidad con URLs existentes.
 * El módulo de Libro Diario ahora está unificado en Entries.php
 */
class Diario extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Redirige a Entries manteniendo los parámetros de consulta
     */
    public function index()
    {
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        $redirectUrl = base_url() . 'sisvent/accounting/entries';

        if (!empty($queryString)) {
            $redirectUrl .= '?' . $queryString;
        }

        redirect($redirectUrl);
    }
}
