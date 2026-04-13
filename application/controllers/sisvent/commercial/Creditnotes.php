<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Creditnotes extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->backend_lib->controlModule('notas_credito');
        $this->load->model('creditnotes_model');
        $this->load->model('invoices_model');
        $this->load->model('clients_model');
        $this->load->model('inventory_model');
        $this->load->model('stores_model');
    }

    /**
     * Lista de notas crédito
     */
    public function index() {
        $status = $this->input->get('status') ?: 'all';
        $role = $this->session->userdata('user_data')['role'];
        $vendorId = null;

        // Vendedores solo ven las suyas
        if ($role == 3) {
            $vendorId = $this->session->userdata('user_data')['uname'];
        }

        $data = array(
            'notes' => $this->creditnotes_model->getAll($status, $vendorId),
            'status' => $status,
            'pendingCount' => $this->creditnotes_model->countByStatus('pendiente')
        );
        $this->load->view('sisvent/commercial/creditnotes/list', $data);
    }

    /**
     * Formulario de creación
     */
    public function create() {
        $data = array(
            'stores' => $this->stores_model->getStores()
        );
        $this->load->view('sisvent/commercial/creditnotes/create', $data);
    }

    /**
     * AJAX: Buscar facturas de un cliente
     */
    public function clientInvoices() {
        $clientId = $this->input->get('clientId');
        $this->db->select('idInvoice, date, total, payment, storeId')
            ->from('invoices')
            ->where('clientId', $clientId)
            ->where('deleted', 0)
            ->order_by('date', 'DESC')
            ->limit(10);
        $invoices = $this->db->get()->result();

        foreach ($invoices as &$inv) {
            $inv->details = $this->invoices_model->getDetails($inv->idInvoice);
        }

        header('Content-Type: application/json');
        echo json_encode($invoices);
    }

    /**
     * Guardar nota crédito
     */
    public function store() {
        $user = $this->session->userdata('user_data')['uname'];
        $invoiceId = $this->input->post('invoiceId') ?: null;
        $clientId = $this->input->post('clientId');
        $storeId = $this->input->post('storeId');
        $type = $this->input->post('type') ?: 'devolucion';
        $reason = $this->input->post('reason') ?: 'otro';
        $observations = $this->input->post('observations');
        $products = $this->input->post('productId');
        $quantities = $this->input->post('quantity');
        $prices = $this->input->post('price');
        $conditions = $this->input->post('condition');

        if (empty($clientId) || empty($products)) {
            $this->session->set_flashdata('error_cn', 'Datos incompletos.');
            redirect('sisvent/commercial/creditnotes/create');
            return;
        }

        $total = 0;
        $items = array();
        for ($i = 0; $i < count($products); $i++) {
            if (empty($products[$i]) || empty($quantities[$i])) continue;
            $subtotal = (float)$quantities[$i] * (float)$prices[$i];
            $total += $subtotal;
            $items[] = array(
                'productId' => $products[$i],
                'quantity' => (int)$quantities[$i],
                'price' => (float)$prices[$i],
                'subtotal' => $subtotal,
                'condition' => isset($conditions[$i]) ? $conditions[$i] : 'bueno'
            );
        }

        $noteId = $this->creditnotes_model->save(array(
            'invoiceId' => $invoiceId,
            'clientId' => $clientId,
            'vendorId' => $user,
            'storeId' => $storeId,
            'type' => $type,
            'reason' => $reason,
            'total' => $total,
            'status' => 'pendiente',
            'observations' => $observations,
            'created_by' => $user,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ));

        foreach ($items as $item) {
            $item['creditNoteId'] = $noteId;
            $this->creditnotes_model->saveDetail($item);
        }

        $this->session->set_flashdata('success_cn', 'Nota crédito #' . $noteId . ' creada. Pendiente de aprobación.');
        redirect('sisvent/commercial/creditnotes');
    }

    /**
     * Ver detalle
     */
    public function view($id) {
        $note = $this->creditnotes_model->get($id);
        if (!$note) show_404();

        $data = array(
            'note' => $note,
            'details' => $this->creditnotes_model->getDetails($id)
        );
        $this->load->view('sisvent/commercial/creditnotes/view', $data);
    }

    /**
     * Aprobar nota crédito
     */
    public function approve($id) {
        $this->backend_lib->controlModule('aprobar_notas_credito');

        $note = $this->creditnotes_model->get($id);
        if (!$note || $note->status !== 'pendiente') {
            $this->session->set_flashdata('error_cn', 'Esta nota no se puede aprobar.');
            redirect('sisvent/commercial/creditnotes');
            return;
        }

        $user = $this->session->userdata('user_data')['uname'];
        $details = $this->creditnotes_model->getDetails($id);

        // 1. Aprobar la nota
        $this->creditnotes_model->update($id, array(
            'status' => 'aprobada',
            'approved_by' => $user,
            'approved_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ));

        // 2. Reducir deuda en la factura de origen
        if ($note->invoiceId) {
            $invoice = $this->invoices_model->getInvoice($note->invoiceId);
            if ($invoice) {
                $newPayment = (float)$invoice->payment + (float)$note->total;
                $this->db->update('invoices', array(
                    'payment' => $newPayment,
                    'state' => ($newPayment >= ($invoice->total - $invoice->discount)) ? 2 : 1
                ), array('idInvoice' => $note->invoiceId));
            }
        }

        // 3. Devolver productos al inventario
        foreach ($details as $d) {
            $this->db->query("
                UPDATE inventory SET stock = stock + ?
                WHERE idProduct = ? AND idStore = ?
            ", array((int)$d->quantity, $d->productId, $note->storeId));
        }

        $this->session->set_flashdata('success_cn', 'Nota crédito #' . $id . ' aprobada. Cartera e inventario actualizados.');
        redirect('sisvent/commercial/creditnotes/view/' . $id);
    }

    /**
     * Rechazar nota crédito
     */
    public function reject($id) {
        $this->backend_lib->controlModule('aprobar_notas_credito');

        $reason = $this->input->post('rejection_reason') ?: '';
        $user = $this->session->userdata('user_data')['uname'];

        $this->creditnotes_model->update($id, array(
            'status' => 'rechazada',
            'rejection_reason' => $reason,
            'approved_by' => $user,
            'approved_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ));

        $this->session->set_flashdata('success_cn', 'Nota crédito #' . $id . ' rechazada.');
        redirect('sisvent/commercial/creditnotes');
    }
}
