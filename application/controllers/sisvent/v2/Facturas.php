<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ledxury v2 · Pulso — Facturas (listado)
 *
 * Estados Ledxury facturas: 0=por_cobrar/abierta, 1=parcial, 2=pagada, 3=anulada
 * (Ver Invoices_model::getInvoices para confirmar mapping real).
 *
 * Read-only. Espejo en estilo Pulso de /sisvent/commercial/invoices.
 */
class Facturas extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control();
        $this->load->model('invoices_model');
        $this->load->model('clients_model');
        $this->load->model('users_model');
    }

    /**
     * GET params:
     *   - estado: todos | pagada | pendiente | vencida | anulada
     *   - p:      página
     */
    public function index()
    {
        $estado = $this->input->get('estado') ?: 'todos';
        $page   = max(1, (int) ($this->input->get('p') ?: 1));
        $limit  = 25;

        $user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']);
        $adminStore = !empty($user->admin_store) ? explode(',', $user->admin_store) : array();
        $role = (int)$this->session->userdata('user_data')['role'];
        $getOthers = !in_array($role, [3, 4]);
        $vendor = ($role === 3 && !empty($user->idUser)) ? $user->idUser : 'all';

        // state mapping para invoices_model
        $stateMap = array(
            'todos'      => 'all',
            'pagada'     => '2',
            'pendiente'  => '0',  // por cobrar
            'parcial'    => '1',
            'anulada'    => '3',
        );
        $stateFilter = $stateMap[$estado] ?? 'all';

        // Listado paginado
        $invoices = $this->invoices_model->getInvoices(
            $getOthers, 'all', $vendor, $stateFilter, 'all', 'all',
            $adminStore, $page, $limit, '', ''
        );

        // Enriquecer: cuando invoice.vendorId='00000' (Administrador) o originalVendorId
        // existe, usar el vendedor REAL (del presupuesto). Caso típico: vendedor
        // creó el presupuesto y el admin lo facturó.
        if (!empty($invoices)) {
            $budgetIds = array();
            foreach ($invoices as $inv) {
                if (!empty($inv->budgetId)) $budgetIds[] = (int)$inv->budgetId;
            }
            $budgetVendorMap = array();
            if (!empty($budgetIds)) {
                $rows = $this->db->select('b.idBudget, b.vendorId, u.name')
                    ->from('budgets b')
                    ->join('users u', 'u.idUser = b.vendorId', 'left')
                    ->where_in('b.idBudget', $budgetIds)
                    ->get()->result();
                foreach ($rows as $r) $budgetVendorMap[$r->idBudget] = $r;
            }
            foreach ($invoices as &$inv) {
                $inv->real_vendor_id   = $inv->vendorId;
                $inv->real_vendor_name = $inv->vendor_name ?? '—';
                $inv->facturado_por    = null;
                // Si invoice quedó con Administrador y existe budget con vendor real,
                // mostramos el vendor del budget como "real" y Admin como "facturador".
                if (in_array((string)$inv->vendorId, array('00000', ''), true)
                    && !empty($inv->budgetId)
                    && isset($budgetVendorMap[$inv->budgetId])
                    && $budgetVendorMap[$inv->budgetId]->vendorId !== '00000') {
                    $inv->real_vendor_id   = $budgetVendorMap[$inv->budgetId]->vendorId;
                    $inv->real_vendor_name = $budgetVendorMap[$inv->budgetId]->name ?: '—';
                    $inv->facturado_por    = 'Administrador';
                }
            }
            unset($inv);
        }

        $total = $this->invoices_model->getTotal('all', $vendor, $stateFilter, 'all', 'all', $adminStore);
        $lastPage = max(1, (int) ceil($total / $limit));

        // KPIs por estado — count per state
        $counts = array(
            'todos'     => (int)$this->invoices_model->getTotal('all', $vendor, 'all', 'all', 'all', $adminStore),
            'pendiente' => (int)$this->invoices_model->getTotal('all', $vendor, '0',   'all', 'all', $adminStore),
            'parcial'   => (int)$this->invoices_model->getTotal('all', $vendor, '1',   'all', 'all', $adminStore),
            'pagada'    => (int)$this->invoices_model->getTotal('all', $vendor, '2',   'all', 'all', $adminStore),
            'anulada'   => (int)$this->invoices_model->getTotal('all', $vendor, '3',   'all', 'all', $adminStore),
        );

        // Volumen total del mes corriente (cobrado)
        $vol = $this->db->select('COALESCE(SUM(total),0) AS v, COUNT(*) AS n')
            ->from('invoices')
            ->where('state', 2)
            ->where('total >', 0)
            ->group_start()->where('deleted IS NULL', null, false)->or_where('deleted', 0)->group_end()
            ->where('updated_at >=', date('Y-m-01 00:00:00'))
            ->get()->row();
        $volumenMes = (float)($vol->v ?? 0);
        $cobradasMes = (int)($vol->n ?? 0);

        // Cartera vencida
        $cart = $this->db->query("
            SELECT COALESCE(SUM(GREATEST(0, i.total - COALESCE(p.pagado,0))),0) AS cartera, COUNT(*) AS n_vencidas
            FROM invoices i
            LEFT JOIN (SELECT invoiceId, SUM(payment) AS pagado FROM payments WHERE deleted=0 GROUP BY invoiceId) p
              ON p.invoiceId = i.idInvoice
            WHERE i.state = 2 AND (i.deleted IS NULL OR i.deleted=0)
              AND (i.total - COALESCE(p.pagado,0)) > 0
              AND DATEDIFF(CURDATE(), i.date) > 30
        ")->row();
        $carteraVencida = (float)($cart->cartera ?? 0);
        $factsVencidas  = (int)($cart->n_vencidas ?? 0);

        // Sparkline cobros últimos 14 días
        $spark = $this->db->query("
            SELECT DATE(updated_at) AS d, COALESCE(SUM(total),0) AS v
            FROM invoices
            WHERE state=2 AND total>0 AND (deleted IS NULL OR deleted=0)
              AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
            GROUP BY DATE(updated_at) ORDER BY d ASC
        ")->result();
        $sparkVolumen = array();
        foreach ($spark as $r) $sparkVolumen[] = round((float)$r->v / 1000);
        if (count($sparkVolumen) < 2) $sparkVolumen = array(0, 0);

        $data = array(
            'pageTitle'     => 'Facturas',
            'activeRoute'   => 'facturas',
            'breadcrumbs'   => array('Operación', 'Ventas', 'Facturas'),
            'invoices'      => $invoices,
            'estado'        => $estado,
            'page'          => $page,
            'lastPage'      => $lastPage,
            'total'         => $total,
            'counts'        => $counts,
            'volumenMes'    => $volumenMes,
            'cobradasMes'   => $cobradasMes,
            'carteraVencida'=> $carteraVencida,
            'factsVencidas' => $factsVencidas,
            'sparkVolumen'  => $sparkVolumen,
        );
        $this->load->view('sisvent/v2/pulso/facturas/index', $data);
    }

    /**
     * Detalle de factura (estilo factura imprimible · Pulso).
     */
    public function view($id)
    {
        $id = (int) $id;
        if ($id <= 0) show_404();

        $invoice = $this->invoices_model->getInvoice($id);
        if (!$invoice) show_404();

        $details = $this->invoices_model->getDetails($id);
        $client  = $this->clients_model->getClient($invoice->clientId);

        // Pagos hechos
        $payments = $this->db->select('p.idPayment, p.payment, p.paymentMethod, p.originType, p.date, p.comments, pm.name AS methodName')
            ->from('payments p')
            ->join('paymentMethods pm', 'pm.idPaymentMethod = p.paymentMethod', 'left')
            ->where('p.invoiceId', $id)
            ->where('p.deleted', 0)
            ->order_by('p.date', 'ASC')
            ->get()->result();
        $totalPagado = 0;
        foreach ($payments as $p) $totalPagado += (float)$p->payment;
        $saldo = max(0, (float)$invoice->total - $totalPagado);

        // Guías shipping (si existen)
        $guias = $this->db->select('id, numeroPreenvio, status, carrierName, valorTotal, created_at')
            ->where('invoiceId', $id)
            ->order_by('id', 'DESC')
            ->get('shipping_guides')->result();

        // Vendedor real: si la factura tiene vendorId='00000' (Admin) y existe
        // un budget asociado con vendor diferente, ese es el vendedor "real".
        // Mostramos a Administrador como "facturada por" en cambio.
        $vendor = !empty($invoice->vendorId)
            ? $this->users_model->getAnyUser($invoice->vendorId)
            : null;
        $facturadoPor = null;
        if (in_array((string)$invoice->vendorId, array('00000', ''), true)
            && !empty($invoice->budgetId)) {
            $budgetVendor = $this->db->select('b.vendorId, u.name')
                ->from('budgets b')
                ->join('users u', 'u.idUser = b.vendorId', 'left')
                ->where('b.idBudget', (int)$invoice->budgetId)
                ->get()->row();
            if ($budgetVendor && !empty($budgetVendor->vendorId) && $budgetVendor->vendorId !== '00000') {
                $facturadoPor = $vendor ? $vendor->name : 'Administrador';
                // Re-asignar vendor al del presupuesto
                $vendor = $this->users_model->getAnyUser($budgetVendor->vendorId) ?: $vendor;
            }
        }

        // Productos detallados (descripción)
        $this->load->model('products_model');
        $productMap = array();
        if (!empty($details)) {
            $ids = array();
            foreach ($details as $d) $ids[] = $d->productId;
            $prods = $this->db->where_in('idProduct', $ids)->get('products')->result();
            foreach ($prods as $p) $productMap[$p->idProduct] = $p;
        }

        $data = array(
            'pageTitle'   => 'Factura #' . str_pad($invoice->idInvoice, 6, '0', STR_PAD_LEFT),
            'activeRoute' => 'facturas',
            'breadcrumbs' => array('Operación', 'Ventas', 'Facturas', '#' . $invoice->idInvoice),
            'invoice'     => $invoice,
            'details'     => $details,
            'client'      => $client,
            'payments'    => $payments,
            'totalPagado' => $totalPagado,
            'saldo'       => $saldo,
            'guias'       => $guias,
            'vendor'        => $vendor,
            'facturadoPor'  => $facturadoPor,
            'productMap'    => $productMap,
        );
        $this->load->view('sisvent/v2/pulso/facturas/show', $data);
    }
}
