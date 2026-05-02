<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Gestión de tickets de garantías y devoluciones que llegan por el canal
 * WhatsApp dedicado (+573330512998 vía Meta Cloud API directo).
 *
 * Las conversaciones del cliente se ven en /sisvent/admin/bots/messages
 * (chat existente, donde "Ledxury Garantías" aparece como un bot más).
 * Aquí se gestiona la capa de TICKET: estado, asignación, resolución,
 * vínculo con factura/budget original.
 */
class Garantias extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1]); // admin
        $this->load->model('garantias_model');
        $this->load->model('clients_model');
        $this->load->model('users_model');
        $this->load->model('builderbot_model');
    }

    // ========================================================================
    // LISTADO
    // ========================================================================

    public function index()
    {
        $filters = array(
            'status'     => $this->input->get('status') ?: 'all',
            'case_type'  => $this->input->get('type')   ?: 'all',
            'priority'   => $this->input->get('prio')   ?: 'all',
            'search'     => $this->input->get('q'),
        );
        $page = max(1, (int)$this->input->get('p'));

        $data = array(
            'tickets'   => $this->garantias_model->getTickets($filters, $page, 50),
            'counts'    => $this->garantias_model->getCounts(),
            'filters'   => $filters,
            'page'      => $page,
        );
        $this->load->view('sisvent/admin/garantias/list', $data);
    }

    // ========================================================================
    // CREAR (manual o desde una conversación)
    // ========================================================================

    public function add()
    {
        // Si viene ?conv=ID, pre-llenamos cliente y phone desde la conversación
        $convId = (int)$this->input->get('conv');
        $prefill = null;
        if ($convId) {
            $prefill = $this->builderbot_model->getConversation($convId);
        }
        $data = array(
            'prefill'  => $prefill,
            'agents'   => $this->users_model->getUsers(),
        );
        $this->load->view('sisvent/admin/garantias/edit', $data);
    }

    public function store()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        $payload = $this->_validatePayload();
        if ($payload === null) {
            $this->add();
            return;
        }
        // Resolver cliente si llegó por phone
        if (empty($payload['client_id']) && !empty($payload['client_phone'])) {
            $client = $this->clients_model->getClientByPhone($payload['client_phone']);
            if ($client) $payload['client_id'] = $client->idClient;
        }

        $id = $this->garantias_model->save($payload);
        if ($id) {
            $this->session->set_flashdata('success', "Ticket creado.");
            redirect(base_url() . 'sisvent/admin/garantias/view/' . $id);
        } else {
            $this->session->set_flashdata('error', 'No se pudo crear el ticket.');
            redirect(base_url() . 'sisvent/admin/garantias/add');
        }
    }

    // ========================================================================
    // VER / EDITAR
    // ========================================================================

    public function view($id)
    {
        $ticket = $this->garantias_model->getTicket($id);
        if (!$ticket) redirect(base_url() . 'sisvent/admin/garantias');

        // Cargar mensajes de la conversación si hay
        $messages = array();
        if ($ticket->conversation_id) {
            $messages = $this->builderbot_model->getConversationMessages($ticket->conversation_id, 50);
        }
        $data = array(
            'ticket'   => $ticket,
            'messages' => $messages,
            'agents'   => $this->users_model->getUsers(),
        );
        $this->load->view('sisvent/admin/garantias/view', $data);
    }

    public function update()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        $id = (int)$this->input->post('id');
        $ticket = $this->garantias_model->getTicket($id);
        if (!$ticket) redirect(base_url() . 'sisvent/admin/garantias');

        $payload = $this->_validatePayload();
        if ($payload === null) {
            redirect(base_url() . 'sisvent/admin/garantias/view/' . $id);
            return;
        }
        $this->garantias_model->update($id, $payload);
        $this->session->set_flashdata('success', 'Ticket actualizado.');
        redirect(base_url() . 'sisvent/admin/garantias/view/' . $id);
    }

    public function changeStatus($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        $newStatus = $this->input->post('status');
        $notes     = $this->input->post('notes');
        $valid = array('abierto','en_revision','resuelto','cerrado','cancelado');
        if (!in_array($newStatus, $valid, true)) {
            echo json_encode(array('ok' => false, 'error' => 'Estado inválido'));
            return;
        }
        $this->garantias_model->changeStatus($id, $newStatus, $notes);
        echo json_encode(array('ok' => true));
    }

    public function delete($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        $this->garantias_model->remove($id);
        echo base_url() . 'sisvent/admin/garantias';
    }

    // ========================================================================
    // HELPERS
    // ========================================================================

    private function _validatePayload()
    {
        $client_phone = preg_replace('/[^0-9]/', '', (string)$this->input->post('client_phone'));
        if (strlen($client_phone) === 12 && substr($client_phone, 0, 2) === '57') {
            $client_phone = substr($client_phone, 2);
        }
        $payload = array(
            'conversation_id'  => (int)$this->input->post('conversation_id') ?: null,
            'client_phone'     => $client_phone,
            'client_name'      => trim((string)$this->input->post('client_name')),
            'client_id'        => (int)$this->input->post('client_id') ?: null,
            'case_type'        => $this->input->post('case_type') ?: 'garantia',
            'description'      => trim((string)$this->input->post('description')),
            'invoice_id'       => (int)$this->input->post('invoice_id') ?: null,
            'budget_id'        => (int)$this->input->post('budget_id') ?: null,
            'product_id'       => trim((string)$this->input->post('product_id')) ?: null,
            'priority'         => $this->input->post('priority') ?: 'media',
            'assigned_to'      => $this->input->post('assigned_to') ?: null,
            'resolution_type'  => $this->input->post('resolution_type') ?: null,
            'resolution_notes' => trim((string)$this->input->post('resolution_notes')) ?: null,
        );

        $errors = array();
        if ($payload['client_phone'] === '') $errors[] = 'Teléfono del cliente es obligatorio.';
        if (!in_array($payload['case_type'], array('garantia','devolucion','reclamo','otro'), true)) {
            $errors[] = 'Tipo de caso inválido.';
        }
        if (!in_array($payload['priority'], array('baja','media','alta','urgente'), true)) {
            $errors[] = 'Prioridad inválida.';
        }

        if (!empty($errors)) {
            $this->session->set_flashdata('error', implode(' ', $errors));
            return null;
        }
        return $payload;
    }
}
