<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Devoluciones — workflow de devoluciones de transportadora.
 *
 * Cuando una guía pasa a status='returned' (o estadoGuia ∈ {13,14,15} en Inter),
 * se detecta automáticamente y entra al flujo de revisión:
 *
 *   detectada → en_camino → recibida → nota_credito_emitida
 *                                   ↘ reembarcada
 *                                   ↘ perdida (write-off)
 *
 * Cada acción genera auditoría (received_back_by/at). La nota crédito se delega
 * al módulo existente /commercial/creditnotes (ya tiene workflow approve →
 * restock con quality hold + asientos automáticos).
 */
class Devoluciones extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Permiso: admin (1,2) + logística (9). Si tenés otro rol, ajustar.
        $this->backend_lib->control([1, 2, 9]);
    }

    /**
     * Listado principal. Antes de mostrar, ejecuta el detector para crear
     * filas en shipping_returns por cada guía nueva con status='returned'.
     */
    public function index() {
        $detected = $this->_autoDetect();

        // Filtros desde GET
        $filterStatus = $this->input->get('status') ?: 'pendientes'; // pendientes | todas | <status_specific>
        $filterCarrier = $this->input->get('carrier') ?: 'all';
        $filterFrom = $this->input->get('from') ?: date('Y-m-01', strtotime('-2 month'));
        $filterTo   = $this->input->get('to')   ?: date('Y-m-d');

        $this->db->select("sr.*, sg.numeroPreenvio, sg.carrierName, sg.actualDelivery, sg.valorDeclarado,
                           sg.status as guide_status, sg.estadoNombre,
                           inv.idInvoice as factura_id, inv.total as factura_total, inv.date as factura_date,
                           cli.name as cliente_name, cli.cellphone as cliente_phone, cli.city as cliente_city,
                           u.name as vendor_name, st.name as store_name,
                           DATEDIFF(NOW(), sr.detected_at) as dias_desde_deteccion")
            ->from('shipping_returns sr')
            ->join('shipping_guides sg', 'sg.id = sr.shipping_guide_id', 'left')
            ->join('invoices inv', 'inv.idInvoice = sr.invoice_id', 'left')
            ->join('clients cli', 'cli.idClient = sr.client_id', 'left')
            ->join('users u', 'u.idUser = sr.vendor_id', 'left')
            ->join('stores st', 'st.idStore = sr.store_id', 'left')
            ->where('DATE(sr.detected_at) >=', $filterFrom)
            ->where('DATE(sr.detected_at) <=', $filterTo);

        if ($filterStatus === 'pendientes') {
            $this->db->where_in('sr.status', ['detectada', 'en_camino', 'recibida']);
        } elseif ($filterStatus !== 'todas') {
            $this->db->where('sr.status', $filterStatus);
        }
        if ($filterCarrier !== 'all') {
            $this->db->where('sg.carrierName', $filterCarrier);
        }

        $returns = $this->db->order_by('sr.detected_at', 'DESC')->get()->result();

        // KPIs sobre TODO el rango (no afectados por filtros de status/carrier)
        $kpiSql = "SELECT
                    SUM(CASE WHEN status='detectada' THEN 1 ELSE 0 END) AS detectadas,
                    SUM(CASE WHEN status='en_camino' THEN 1 ELSE 0 END) AS en_camino,
                    SUM(CASE WHEN status='recibida' THEN 1 ELSE 0 END) AS recibidas,
                    SUM(CASE WHEN status='nota_credito_emitida' THEN 1 ELSE 0 END) AS con_nc,
                    SUM(CASE WHEN status='reembarcada' THEN 1 ELSE 0 END) AS reembarcadas,
                    SUM(CASE WHEN status='perdida' THEN 1 ELSE 0 END) AS perdidas,
                    SUM(flete_perdido) AS total_flete_perdido,
                    COUNT(*) AS total
                  FROM shipping_returns
                  WHERE DATE(detected_at) BETWEEN ? AND ?";
        $kpis = $this->db->query($kpiSql, [$filterFrom, $filterTo])->row();

        $data = [
            'returns'        => $returns,
            'kpis'           => $kpis,
            'detected_now'   => $detected,
            'filter_status'  => $filterStatus,
            'filter_carrier' => $filterCarrier,
            'filter_from'    => $filterFrom,
            'filter_to'      => $filterTo,
            'role'           => $this->session->userdata('user_data')['role'],
        ];
        $this->load->view('sisvent/admin/devoluciones/list', $data);
    }

    /**
     * Detalle de una devolución con acciones disponibles según su status.
     */
    public function detail($id) {
        $return = $this->_getReturnEnriched((int)$id);
        if (!$return) show_404();

        // Items de la factura original (productos que volverán al stock)
        $invoiceItems = [];
        if ($return->invoice_id) {
            $invoiceItems = $this->db->select('id.*, p.description as product_description, p.cost_cop, p.price')
                ->from('invoice_details id')
                ->join('products p', 'p.idProduct = id.productId', 'left')
                ->where('id.invoiceId', $return->invoice_id)
                ->get()->result();
        }

        $data = [
            'return'       => $return,
            'invoice_items'=> $invoiceItems,
            'role'         => $this->session->userdata('user_data')['role'],
        ];
        $this->load->view('sisvent/admin/devoluciones/detail', $data);
    }

    /**
     * Acción: marcar paquete recibido en bodega + condition + restock toggle.
     */
    public function receive($id) {
        $this->outh_model->CSRFVerify();
        $return = $this->db->where('id', (int)$id)->get('shipping_returns')->row();
        if (!$return) show_404();

        $condition = $this->input->post('product_condition') ?: 'bueno';
        $restock   = $this->input->post('restock_inventory') ? 1 : 0;
        $notes     = trim((string)$this->input->post('notes'));

        $allowed = ['bueno', 'danado', 'incompleto', 'no_recibido'];
        if (!in_array($condition, $allowed, true)) $condition = 'bueno';

        $uname = $this->session->userdata('user_data')['uname'];
        $this->db->where('id', $return->id)->update('shipping_returns', [
            'status'            => 'recibida',
            'received_back_at'  => date('Y-m-d H:i:s'),
            'received_back_by'  => $uname,
            'product_condition' => $condition,
            'restock_inventory' => $restock,
            'notes'             => $notes ?: $return->notes,
        ]);

        $this->session->set_flashdata('devoluciones_msg', 'Devolución marcada como RECIBIDA.');
        redirect(base_url() . 'sisvent/admin/devoluciones/detail/' . $return->id);
    }

    /**
     * Acción: marcar como perdida (write-off). El paquete nunca volvió o
     * llegó destruido completo. Se asume el costo total (flete ida+vuelta+producto).
     */
    public function markLost($id) {
        $this->outh_model->CSRFVerify();
        $return = $this->db->where('id', (int)$id)->get('shipping_returns')->row();
        if (!$return) show_404();

        $notes = trim((string)$this->input->post('notes'));
        $uname = $this->session->userdata('user_data')['uname'];

        $this->db->where('id', $return->id)->update('shipping_returns', [
            'status'           => 'perdida',
            'received_back_at' => date('Y-m-d H:i:s'),
            'received_back_by' => $uname,
            'product_condition'=> 'no_recibido',
            'notes'            => $notes ?: 'Marcada como perdida',
        ]);

        $this->session->set_flashdata('devoluciones_msg', 'Devolución marcada como PERDIDA (write-off).');
        redirect(base_url() . 'sisvent/admin/devoluciones/detail/' . $return->id);
    }

    /**
     * Acción: reembarcar. Solo deja una nota; la nueva guía la crea el flujo
     * normal de envíos. Si después se le pega el id de la nueva guía vía
     * input post, se guarda en new_guide_id.
     */
    public function reship($id) {
        $this->outh_model->CSRFVerify();
        $return = $this->db->where('id', (int)$id)->get('shipping_returns')->row();
        if (!$return) show_404();

        $newGuideId = (int)$this->input->post('new_guide_id');
        $notes      = trim((string)$this->input->post('notes'));
        $uname      = $this->session->userdata('user_data')['uname'];

        $this->db->where('id', $return->id)->update('shipping_returns', [
            'status'         => 'reembarcada',
            'new_guide_id'   => $newGuideId ?: null,
            'notes'          => $notes ?: 'Reembarcada con nueva guía',
            'received_back_by'=> $uname,
        ]);

        $this->session->set_flashdata('devoluciones_msg', 'Devolución marcada como REEMBARCADA.');
        redirect(base_url() . 'sisvent/admin/devoluciones/detail/' . $return->id);
    }

    /**
     * Acción: generar nota crédito. Redirige al módulo existente con prefill
     * de invoiceId y type='devolucion'. El módulo /commercial/creditnotes
     * maneja el resto (approve workflow + restock con quality hold + asientos).
     * Cuando se aprueba, registramos su credit_note_id acá vía linkCreditNote().
     */
    public function generateCreditNote($id) {
        $return = $this->db->where('id', (int)$id)->get('shipping_returns')->row();
        if (!$return) show_404();
        if (!$return->invoice_id) {
            $this->session->set_flashdata('devoluciones_error', 'No hay factura asociada a esta devolución; no se puede generar nota crédito.');
            redirect(base_url() . 'sisvent/admin/devoluciones/detail/' . $return->id);
            return;
        }

        // Redirige al form de creditnotes con prefill via query string
        redirect(base_url() . 'sisvent/commercial/creditnotes/create?invoice_id=' . (int)$return->invoice_id . '&type=devolucion&return_id=' . (int)$return->id);
    }

    /**
     * Linkea una nota crédito ya creada. Se llamaría desde el módulo de
     * creditnotes después de aprobar, vía un POST con return_id + credit_note_id.
     * Por ahora puede usarse manualmente para conectar notas creadas antes.
     */
    public function linkCreditNote($id) {
        $this->outh_model->CSRFVerify();
        $return = $this->db->where('id', (int)$id)->get('shipping_returns')->row();
        if (!$return) show_404();

        $creditNoteId = (int)$this->input->post('credit_note_id');
        if (!$creditNoteId) {
            $this->session->set_flashdata('devoluciones_error', 'Falta credit_note_id');
            redirect(base_url() . 'sisvent/admin/devoluciones/detail/' . $return->id);
            return;
        }
        $this->db->where('id', $return->id)->update('shipping_returns', [
            'status'         => 'nota_credito_emitida',
            'credit_note_id' => $creditNoteId,
        ]);
        $this->session->set_flashdata('devoluciones_msg', 'Nota crédito #' . $creditNoteId . ' linkeada.');
        redirect(base_url() . 'sisvent/admin/devoluciones/detail/' . $return->id);
    }

    /**
     * Edición rápida de notas y status. POST con campo único 'notes' o 'status'.
     */
    public function update($id) {
        $this->outh_model->CSRFVerify();
        $return = $this->db->where('id', (int)$id)->get('shipping_returns')->row();
        if (!$return) show_404();

        $payload = [];
        $notes = $this->input->post('notes');
        if ($notes !== null) $payload['notes'] = trim((string)$notes);
        $newStatus = $this->input->post('status');
        $allowedStatus = ['detectada','en_camino','recibida','nota_credito_emitida','reembarcada','perdida'];
        if ($newStatus !== null && in_array($newStatus, $allowedStatus, true)) {
            $payload['status'] = $newStatus;
        }
        if (!empty($payload)) {
            $this->db->where('id', $return->id)->update('shipping_returns', $payload);
        }
        redirect(base_url() . 'sisvent/admin/devoluciones/detail/' . $return->id);
    }

    /**
     * Detector: busca shipping_guides con status='returned' o estadoGuia
     * en {13,14,15} (Inter) que no tengan fila en shipping_returns y las crea.
     * Llamado al cargar el listado (lazy detection). Idempotente por unique key.
     *
     * @return int cuántas devoluciones nuevas se detectaron
     */
    private function _autoDetect() {
        // Inter: estadoGuia 13=devuelta,14=anulada origen,15=anulada destino. Otros carriers usan status text.
        $sql = "SELECT sg.id AS sgid, sg.invoiceId, inv.clientId, inv.vendorId, inv.storeId,
                       sg.valorTotal AS flete_devolucion
                FROM shipping_guides sg
                LEFT JOIN invoices inv ON inv.idInvoice = sg.invoiceId
                LEFT JOIN shipping_returns sr ON sr.shipping_guide_id = sg.id
                WHERE sr.id IS NULL
                  AND (sg.status = 'returned'
                       OR (sg.carrierName = 'Interrapidisimo' AND sg.estadoGuia IN (13, 14, 15)))
                LIMIT 500";
        $rows = $this->db->query($sql)->result();

        $count = 0;
        foreach ($rows as $r) {
            $this->db->insert('shipping_returns', [
                'shipping_guide_id' => $r->sgid,
                'invoice_id'        => $r->invoiceId,
                'client_id'         => $r->clientId,
                'vendor_id'         => $r->vendorId,
                'store_id'          => $r->storeId,
                'status'            => 'detectada',
                'detected_at'       => date('Y-m-d H:i:s'),
                'flete_devolucion'  => (float)$r->flete_devolucion,
                'flete_perdido'     => (float)$r->flete_devolucion * 2, // ida + vuelta como estimado inicial
                'created_by'        => $this->session->userdata('user_data')['uname'] ?? 'auto-detect',
            ]);
            $count++;
        }
        return $count;
    }

    /**
     * Trae una devolución con todos los joins relevantes para el detalle.
     */
    private function _getReturnEnriched($id) {
        return $this->db->select("sr.*, sg.numeroPreenvio, sg.carrierName, sg.actualDelivery, sg.valorDeclarado,
                                  sg.status as guide_status, sg.estadoNombre, sg.recipientName, sg.recipientPhone, sg.ciudadDestinoNombre,
                                  inv.idInvoice as factura_id, inv.total as factura_total, inv.date as factura_date, inv.payment as factura_payment,
                                  cli.name as cliente_name, cli.cellphone as cliente_phone, cli.city as cliente_city, cli.idClient,
                                  u.name as vendor_name, st.name as store_name,
                                  cn.id as cn_exists, cn.status as cn_status, cn.total as cn_total")
            ->from('shipping_returns sr')
            ->join('shipping_guides sg', 'sg.id = sr.shipping_guide_id', 'left')
            ->join('invoices inv', 'inv.idInvoice = sr.invoice_id', 'left')
            ->join('clients cli', 'cli.idClient = sr.client_id', 'left')
            ->join('users u', 'u.idUser = sr.vendor_id', 'left')
            ->join('stores st', 'st.idStore = sr.store_id', 'left')
            ->join('credit_notes cn', 'cn.id = sr.credit_note_id', 'left')
            ->where('sr.id', $id)
            ->get()->row();
    }
}
