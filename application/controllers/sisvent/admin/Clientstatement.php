<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clientstatement extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('estado_cuenta');
    }

    public function index()
    {
        redirect(base_url() . 'sisvent/admin/accountsreceivable/byClient');
    }

    public function show($clientId = null)
    {
        if ($clientId) {
            redirect(base_url() . 'sisvent/admin/accountsreceivable/clientDetail/' . $clientId);
        }
        redirect(base_url() . 'sisvent/admin/accountsreceivable/byClient');
    }
}
