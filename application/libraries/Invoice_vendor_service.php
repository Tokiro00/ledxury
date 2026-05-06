<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Invoice_vendor_service — queries y CRUD de invoices agrupadas por
 * vendedor. Incluye goal management (sales_goal table).
 *
 * Extraido del fat Invoices_model (audit post-v1.3.0). 12 metodos que
 * responden a "dame las facturas / totales / metas de un vendedor":
 *
 * Listados de invoices por vendedor:
 *   - getVendorInvoices($vendor)                       — todas sus facturas
 *   - getVendorInvoicesSince($vendor, $date)           — desde fecha
 *   - getVendorPaidInvoices($vendor)                   — solo state=2 (pagadas)
 *   - getVendorNonPaidInvoices($vendor)                — state 0 o 1
 *
 * Totales agregados:
 *   - getVendorTotalInvoicesSince($vendor, $date)      — SUM(total) desde
 *   - getVendorTotalPaidInvoicesSince($vendor, $date)  — SUM(payment) desde
 *
 * Series de tiempo:
 *   - getVendorSalesByMonth($vendor, $year)            — ventas x mes
 *   - getVendorSalesByDay($vendor, $from, $until)      — ventas x dia
 *
 * Sales goals:
 *   - getVendorSalesGoal($vendor)                      — todos los años
 *   - getVendorSalesYearGoal($vendor, $year)           — año especifico
 *   - saveVendorSalesGoal($data)                       — upsert por (user, year)
 *
 * Agregaciones cross-vendor (no se pueden omitir — quedan aca por
 * coherencia del dominio):
 *   - getStoreSalesByVendor($store, $year)             — ventas por vendedor en tienda
 *
 * Callsites al momento de la extraccion (27 total, 8 archivos):
 *   - settlement_helper.php (8)  — la mayoria de la logica de liquidacion
 *   - Salesboard.php (4)         — goals + year goals
 *   - Reports.php (5)            — store sales, vendor sales by month, year goals
 *   - Settlements.php (2)
 *   - Vendors.php (3)            — goals lookup + upsert
 *   - Departments.php (2)        — year goals para bonos de KPI
 *   - Dashboard.php (2)          — salesByMonth + yearGoal
 *   - sisvent/dashboard.php view (1) — yearGoal del usuario actual
 *
 * Nota sobre `getVendorPaidInvoices2` (en el modelo aparece comentado):
 * NO se extrae, queda en el modelo como bloque comentado historico.
 */
class Invoice_vendor_service
{
    /** @var CI_Controller */
    private $CI;

    public function __construct()
    {
        $this->CI = get_instance();
    }

    public function getVendorInvoices($vendor)
    {
        $this->CI->db->select('invoices.*,
            users.name as vendor_name,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name');
        $this->CI->db->join('users', 'users.idUser = invoices.vendorId');
        $this->CI->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->CI->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->CI->db->from('invoices');
        $this->CI->db->where('invoices.vendorId', $vendor);
        $this->CI->db->where('invoices.deleted', 0);
        $this->CI->db->order_by('invoices.updated_at', 'desc');
        return $this->CI->db->get()->result();
    }

    public function getVendorTotalInvoicesSince($vendor, $date)
    {
        $this->CI->db->select('SUM(invoices.total) as total');
        $this->CI->db->from('invoices');
        $this->CI->db->where('invoices.vendorId', $vendor);
        $this->CI->db->where('invoices.date >=', date('Y-m-d H:i:s', strtotime($date)));
        $this->CI->db->where('invoices.deleted', 0);
        return $this->CI->db->get()->row();
    }

    public function getVendorTotalPaidInvoicesSince($vendor, $date)
    {
        $this->CI->db->select('SUM(invoices.payment) as payment');
        $this->CI->db->from('invoices');
        $this->CI->db->where('invoices.vendorId', $vendor);
        $this->CI->db->where('invoices.date >=', date('Y-m-d H:i:s', strtotime($date)));
        $this->CI->db->where('invoices.deleted', 0);
        return $this->CI->db->get()->row();
    }

    public function getVendorInvoicesSince($vendor, $date)
    {
        $this->CI->db->select('invoices.*,
            users.name as vendor_name,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name');
        $this->CI->db->join('users', 'users.idUser = invoices.vendorId');
        $this->CI->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->CI->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->CI->db->from('invoices');
        $this->CI->db->where('invoices.vendorId', $vendor);
        $this->CI->db->where('invoices.date >=', date('Y-m-d H:i:s', strtotime($date)));
        $this->CI->db->where('invoices.deleted', 0);
        $this->CI->db->order_by('invoices.updated_at', 'desc');
        return $this->CI->db->get()->result();
    }

    public function getVendorPaidInvoices($vendor)
    {
        $this->CI->db->select('invoices.*,
            users.name as vendor_name,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name');
        $this->CI->db->join('users', 'users.idUser = invoices.vendorId');
        $this->CI->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->CI->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->CI->db->from('invoices');
        $this->CI->db->where('invoices.vendorId', $vendor);
        $this->CI->db->where('invoices.state', 2);
        $this->CI->db->where('invoices.deleted', 0);
        $this->CI->db->order_by('invoices.updated_at', 'desc');
        return $this->CI->db->get()->result();
    }

    public function getVendorNonPaidInvoices($vendor)
    {
        $this->CI->db->select('invoices.*,
            users.name as vendor_name,
            stores.name as store_name,
            clients.idNum as client_idNum,
            clients.name as client_name');
        $this->CI->db->join('users', 'users.idUser = invoices.vendorId');
        $this->CI->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->CI->db->join('stores', 'invoices.storeId = stores.idStore');
        $this->CI->db->from('invoices');
        $this->CI->db->where('invoices.vendorId', $vendor);
        $this->CI->db->where("(invoices.state = '0' OR invoices.state = '1')");
        $this->CI->db->where('invoices.deleted', 0);
        $this->CI->db->order_by('invoices.updated_at', 'desc');
        return $this->CI->db->get()->result();
    }

    public function getVendorSalesByMonth($vendor, $year)
    {
        $this->CI->db->select('SUM(invoices.total - invoices.discount) as total,
            invoices.storeId as storeId,
            invoices.vendorId as vendorId,
            users.name as vendor_name,
            MONTH(invoices.date) as month');
        $this->CI->db->join('users', 'users.idUser = invoices.vendorId');
        $this->CI->db->from('invoices');
        $this->CI->db->where('invoices.vendorId', $vendor);
        $this->CI->db->where('invoices.date >=', $year . '-01-01');
        $this->CI->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        $this->CI->db->where('invoices.deleted', 0);
        $this->CI->db->group_by('month');
        $this->CI->db->order_by('month', 'asc');
        return $this->CI->db->get()->result();
    }

    public function getVendorSalesByDay($vendor, $from = '', $until = '')
    {
        $this->CI->db->select('SUM(invoices.total - invoices.discount) as total,
            invoices.storeId as storeId,
            invoices.vendorId as vendorId,
            date(invoices.date) as date,
            users.name as vendor_name,
            DAY(invoices.date) as day');
        $this->CI->db->join('users', 'users.idUser = invoices.vendorId');
        $this->CI->db->from('invoices');
        $this->CI->db->where('invoices.vendorId', $vendor);
        if (!empty($from)) {
            $this->CI->db->where('invoices.date >=', date('Y-m-d H:i:s', strtotime($from)));
        }
        if (!empty($until)) {
            $this->CI->db->where('invoices.date <=', date('Y-m-d H:i:s', strtotime($until)));
        }
        $this->CI->db->where('invoices.deleted', 0);
        $this->CI->db->group_by('day');
        $this->CI->db->order_by('day', 'asc');
        return $this->CI->db->get()->result();
    }

    public function getVendorSalesGoal($vendor)
    {
        $this->CI->db->select('*');
        $this->CI->db->from('sales_goal');
        $this->CI->db->where('sales_goal.userId', $vendor);
        return $this->CI->db->get()->result_array();
    }

    public function getVendorSalesYearGoal($vendor, $year)
    {
        $this->CI->db->select('*');
        $this->CI->db->from('sales_goal');
        $this->CI->db->where('sales_goal.userId', $vendor);
        $this->CI->db->where('sales_goal.year', $year);
        return $this->CI->db->get()->row_array();
    }

    /**
     * Upsert de meta anual por (userId, year). Si existe, update; else insert.
     */
    public function saveVendorSalesGoal($data)
    {
        $goal = $this->getVendorSalesYearGoal($data['userId'], $data['year']);
        if (empty($goal)) {
            return $this->CI->db->insert('sales_goal', $data);
        }
        $this->CI->db->where('userId', $data['userId']);
        $this->CI->db->where('year', $data['year']);
        return $this->CI->db->update('sales_goal', $data);
    }

    public function getStoreSalesByVendor($store, $year)
    {
        $this->CI->db->select('SUM(invoices.total - invoices.discount) as total, invoices.storeId,
            users.name as vendor_name');
        $this->CI->db->join('users', 'users.idUser = invoices.vendorId');
        $this->CI->db->from('invoices');
        if ($store != -1) {
            $this->CI->db->where('invoices.storeId', $store);
        }
        $this->CI->db->where('invoices.date >=', $year . '-01-01');
        $this->CI->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        $this->CI->db->where('invoices.deleted', 0);
        $this->CI->db->group_by('vendorId');
        $this->CI->db->order_by('invoices.storeId', 'asc');
        return $this->CI->db->get()->result();
    }
}
