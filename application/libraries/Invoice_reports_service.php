<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Invoice_reports_service — queries de analytics / reportes sobre
 * invoices e invoice_details.
 *
 * Extraido del fat Invoices_model (audit post-v1.3.0). Agrupa los 10
 * metodos de agregacion y reporte que alimentan principalmente
 * Reports.php + algunas llamadas desde Dashboard / Tracking / Salesboard
 * (basicamente getAllVendorsGoals que es usado en varios lugares para
 * lookup de metas anuales).
 *
 * Dominio: agregaciones por periodo + vendedor / tienda / cliente /
 * producto con buckets de antiguedad, rentabilidad (margen sobre
 * cost_cop), comisiones, YoY, top products.
 *
 * Callsites al momento de la extraccion (14 total):
 *   - Reports.php (10)
 *   - Salesboard.php (2 — getAllVendorsGoals)
 *   - Dashboard.php (1 — getAllVendorsGoals)
 *   - Tracking.php (1 — getAllVendorsGoals)
 */
class Invoice_reports_service
{
    /** @var CI_Controller */
    private $CI;

    public function __construct()
    {
        $this->CI = get_instance();
    }

    /**
     * Ventas agregadas por vendedor y mes del año dado (x12 meses).
     * Incluye invoice_count y total_collected (payment).
     */
    public function getAllVendorsSalesByMonth($year, $store = -1)
    {
        $this->CI->db->select('SUM(invoices.total - invoices.discount) as total_sales,
            SUM(invoices.payment) as total_collected,
            COUNT(invoices.idInvoice) as invoice_count,
            invoices.vendorId,
            invoices.storeId,
            users.name as vendor_name,
            stores.name as store_name,
            MONTH(invoices.date) as month');
        $this->CI->db->join('users', 'users.idUser = invoices.vendorId');
        $this->CI->db->join('stores', 'stores.idStore = invoices.storeId');
        $this->CI->db->from('invoices');
        $this->CI->db->where('invoices.date >=', $year . '-01-01');
        $this->CI->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        $this->CI->db->where('invoices.deleted', 0);
        if ($store != -1) {
            $this->CI->db->where('invoices.storeId', $store);
        }
        $this->CI->db->group_by(['invoices.vendorId', 'month']);
        $this->CI->db->order_by('total_sales', 'DESC');
        return $this->CI->db->get()->result();
    }

    /**
     * Metas mensuales de todos los vendedores para el año dado
     * (tabla sales_goal). Usado por Reports, Salesboard, Dashboard,
     * Tracking para lookup de goals.
     */
    public function getAllVendorsGoals($year)
    {
        $this->CI->db->select('*');
        $this->CI->db->from('sales_goal');
        $this->CI->db->where('year', $year);
        return $this->CI->db->get()->result();
    }

    /**
     * Analisis ABC de clientes con cartera por antiguedad. Agrega compras
     * totales, pagadas, deuda actual + buckets (0-30 / 31-60 / 61-90 / 90+).
     */
    public function getClientSalesAnalysis($year = null, $store = -1)
    {
        $this->CI->db->select("clients.idClient, clients.name as client_name, clients.idNum,
            clients.city, clients.vendor,
            users.name as vendor_name,
            COUNT(DISTINCT invoices.idInvoice) as invoice_count,
            SUM(invoices.total - invoices.discount) as total_purchases,
            SUM(invoices.payment) as total_paid,
            SUM(invoices.total - invoices.discount - invoices.payment) as total_debt,
            MIN(invoices.date) as first_purchase,
            MAX(invoices.date) as last_purchase,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) > 90 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_over_90,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) BETWEEN 61 AND 90 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_61_90,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) BETWEEN 31 AND 60 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_31_60,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) <= 30 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_0_30", FALSE);
        $this->CI->db->join('invoices', 'invoices.clientId = clients.idClient AND invoices.deleted = 0');
        $this->CI->db->join('users', 'users.idUser = clients.vendor', 'left');
        $this->CI->db->from('clients');
        $this->CI->db->where('clients.deleted', 0);
        if ($year) {
            $this->CI->db->where('invoices.date >=', $year . '-01-01');
            $this->CI->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        }
        if ($store != -1) {
            $this->CI->db->where('invoices.storeId', $store);
        }
        $this->CI->db->group_by('clients.idClient');
        $this->CI->db->order_by('total_purchases', 'DESC');
        return $this->CI->db->get()->result();
    }

    /**
     * Cartera agrupada por ciudad + vendedor con buckets de antiguedad.
     */
    public function getDebtByCityAndVendor($year = null, $store = -1, $vendorId = null, $clientId = null)
    {
        $this->CI->db->select("
            clients.city,
            invoices.vendorId,
            invoices.storeId,
            users.name as vendor_name,
            stores.name as store_name,
            COUNT(DISTINCT clients.idClient) as client_count,
            COUNT(DISTINCT invoices.idInvoice) as invoice_count,
            SUM(invoices.total - invoices.discount) as total_invoiced,
            SUM(invoices.payment) as total_paid,
            SUM(invoices.total - invoices.discount - invoices.payment) as total_debt,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) > 90 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_over_90,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) BETWEEN 61 AND 90 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_61_90,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) BETWEEN 31 AND 60 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_31_60,
            SUM(CASE WHEN (invoices.total - invoices.discount - invoices.payment) > 0 AND DATEDIFF(CURDATE(), invoices.date) <= 30 THEN (invoices.total - invoices.discount - invoices.payment) ELSE 0 END) as debt_0_30
        ", FALSE);
        $this->CI->db->from('invoices');
        $this->CI->db->join('clients', 'clients.idClient = invoices.clientId');
        $this->CI->db->join('users', 'users.idUser = invoices.vendorId');
        $this->CI->db->join('stores', 'stores.idStore = invoices.storeId');
        $this->CI->db->where('invoices.deleted', 0);
        $this->CI->db->where('(invoices.total - invoices.discount - invoices.payment) >', 0);
        if ($year) {
            $this->CI->db->where('invoices.date >=', $year . '-01-01');
            $this->CI->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        }
        if ($store != -1) {
            $this->CI->db->where('invoices.storeId', $store);
        }
        if ($vendorId) {
            $this->CI->db->where('invoices.vendorId', $vendorId);
        }
        if ($clientId) {
            $this->CI->db->where('invoices.clientId', $clientId);
        }
        $this->CI->db->group_by(['clients.city', 'invoices.vendorId']);
        $this->CI->db->order_by('total_debt', 'DESC');
        return $this->CI->db->get()->result();
    }

    /**
     * Rentabilidad por producto — qty_sold, revenue, cost, margin, margin_pct.
     * Orden: 'margin' o 'revenue' (default).
     */
    public function getProductProfitability($year, $store = -1, $family = null, $sort = 'revenue')
    {
        $this->CI->db->select("
            products.idProduct,
            products.description,
            pf.name as family_name,
            SUM(invoice_details.quantity) as qty_sold,
            SUM(invoice_details.total) as revenue,
            SUM(invoice_details.quantity * products.cost_cop) as total_cost,
            SUM(invoice_details.total) - SUM(invoice_details.quantity * products.cost_cop) as margin,
            CASE WHEN SUM(invoice_details.total) > 0
                THEN ((SUM(invoice_details.total) - SUM(invoice_details.quantity * products.cost_cop)) / SUM(invoice_details.total)) * 100
                ELSE 0 END as margin_pct
        ", FALSE);
        $this->CI->db->from('invoice_details');
        $this->CI->db->join('invoices', 'invoices.idInvoice = invoice_details.invoiceId');
        $this->CI->db->join('products', 'products.idProduct = invoice_details.productId');
        $this->CI->db->join('product_families pf', 'pf.idFamily = products.family', 'left');
        $this->CI->db->where('invoices.deleted', 0);
        $this->CI->db->where('invoices.date >=', $year . '-01-01');
        $this->CI->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        if ($store != -1) $this->CI->db->where('invoices.storeId', $store);
        if ($family) $this->CI->db->where('products.family', $family);
        $this->CI->db->group_by('products.idProduct');
        if ($sort == 'margin') {
            $this->CI->db->order_by('margin', 'DESC');
        } else {
            $this->CI->db->order_by('revenue', 'DESC');
        }
        return $this->CI->db->get()->result();
    }

    /**
     * Rentabilidad por vendedor — revenue, cogs (via subquery sobre
     * invoice_details x products.cost_cop), margin, margin_pct, commission.
     */
    public function getVendorProfitability($year, $store = -1)
    {
        $this->CI->db->select("
            invoices.vendorId,
            users.name as vendor_name,
            stores.name as store_name,
            users.commission_perc,
            COUNT(DISTINCT invoices.idInvoice) as invoice_count,
            SUM(invoices.total - invoices.discount) as revenue,
            SUM(sub.total_cost) as cogs,
            SUM(invoices.total - invoices.discount) - SUM(sub.total_cost) as gross_margin,
            CASE WHEN SUM(invoices.total - invoices.discount) > 0
                THEN ((SUM(invoices.total - invoices.discount) - SUM(sub.total_cost)) / SUM(invoices.total - invoices.discount)) * 100
                ELSE 0 END as margin_pct,
            SUM(invoices.total - invoices.discount) * COALESCE(users.commission_perc, 0) / 100 as commission_earned
        ", FALSE);
        $this->CI->db->from('invoices');
        $this->CI->db->join('users', 'users.idUser = invoices.vendorId');
        $this->CI->db->join('stores', 'stores.idStore = invoices.storeId');
        $this->CI->db->join("(SELECT invoiceId, SUM(quantity * p.cost_cop) as total_cost FROM invoice_details JOIN products p ON p.idProduct = invoice_details.productId GROUP BY invoiceId) sub", 'sub.invoiceId = invoices.idInvoice', 'left');
        $this->CI->db->where('invoices.deleted', 0);
        $this->CI->db->where('invoices.date >=', $year . '-01-01');
        $this->CI->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        if ($store != -1) $this->CI->db->where('invoices.storeId', $store);
        $this->CI->db->group_by('invoices.vendorId');
        $this->CI->db->order_by('revenue', 'DESC');
        return $this->CI->db->get()->result();
    }

    /**
     * Comparativo de ventas Año vs Año (12 meses). Usa raw SQL con
     * subqueries por cada año + JOIN con generador de meses 1-12.
     */
    public function getSalesYoY($yearCurrent, $yearPrevious, $store = -1, $vendor = null)
    {
        $sql = "SELECT
            m.month_num,
            COALESCE(curr.total, 0) as current_total,
            COALESCE(curr.invoice_count, 0) as current_count,
            COALESCE(prev.total, 0) as previous_total,
            COALESCE(prev.invoice_count, 0) as previous_count
        FROM (SELECT 1 as month_num UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) m
        LEFT JOIN (
            SELECT MONTH(date) as mes, SUM(total - discount) as total, COUNT(*) as invoice_count
            FROM invoices WHERE deleted = 0 AND YEAR(date) = " . $this->CI->db->escape($yearCurrent);
        if ($store != -1) $sql .= " AND storeId = " . $this->CI->db->escape($store);
        if ($vendor) $sql .= " AND vendorId = " . $this->CI->db->escape($vendor);
        $sql .= " GROUP BY MONTH(date)
        ) curr ON curr.mes = m.month_num
        LEFT JOIN (
            SELECT MONTH(date) as mes, SUM(total - discount) as total, COUNT(*) as invoice_count
            FROM invoices WHERE deleted = 0 AND YEAR(date) = " . $this->CI->db->escape($yearPrevious);
        if ($store != -1) $sql .= " AND storeId = " . $this->CI->db->escape($store);
        if ($vendor) $sql .= " AND vendorId = " . $this->CI->db->escape($vendor);
        $sql .= " GROUP BY MONTH(date)
        ) prev ON prev.mes = m.month_num
        ORDER BY m.month_num";
        return $this->CI->db->query($sql)->result();
    }

    /**
     * Top N productos mas vendidos, orden por 'qty' (default) o 'revenue'.
     */
    public function getTopProducts($year, $store = -1, $family = null, $topN = 25, $orderBy = 'qty')
    {
        $this->CI->db->select("
            products.idProduct,
            products.description,
            pf.name as family_name,
            SUM(invoice_details.quantity) as qty_sold,
            SUM(invoice_details.total) as revenue,
            SUM(invoice_details.total) / SUM(invoice_details.quantity) as avg_price,
            SUM(invoice_details.quantity * products.cost_cop) as total_cost,
            CASE WHEN SUM(invoice_details.total) > 0
                THEN ((SUM(invoice_details.total) - SUM(invoice_details.quantity * products.cost_cop)) / SUM(invoice_details.total)) * 100
                ELSE 0 END as margin_pct
        ", FALSE);
        $this->CI->db->from('invoice_details');
        $this->CI->db->join('invoices', 'invoices.idInvoice = invoice_details.invoiceId');
        $this->CI->db->join('products', 'products.idProduct = invoice_details.productId');
        $this->CI->db->join('product_families pf', 'pf.idFamily = products.family', 'left');
        $this->CI->db->where('invoices.deleted', 0);
        $this->CI->db->where('invoices.date >=', $year . '-01-01');
        $this->CI->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        if ($store != -1) $this->CI->db->where('invoices.storeId', $store);
        if ($family) $this->CI->db->where('products.family', $family);
        $this->CI->db->group_by('products.idProduct');
        $this->CI->db->order_by($orderBy === 'revenue' ? 'revenue' : 'qty_sold', 'DESC');
        $this->CI->db->limit((int)$topN);
        return $this->CI->db->get()->result();
    }

    /**
     * Comisiones agregadas por vendedor + mes. Filtro opcional por month
     * (dia = primer dia, rango hasta primer dia del mes siguiente).
     */
    public function getVendorCommissions($year, $month = null, $store = -1)
    {
        $this->CI->db->select("
            invoices.vendorId,
            users.name as vendor_name,
            stores.name as store_name,
            users.commission_perc,
            MONTH(invoices.date) as mes,
            SUM(invoices.total - invoices.discount) as total_sales,
            SUM(invoices.total - invoices.discount) * COALESCE(users.commission_perc, 0) / 100 as commission_amount
        ", FALSE);
        $this->CI->db->from('invoices');
        $this->CI->db->join('users', 'users.idUser = invoices.vendorId');
        $this->CI->db->join('stores', 'stores.idStore = invoices.storeId');
        $this->CI->db->where('invoices.deleted', 0);
        $this->CI->db->where('invoices.date >=', $year . '-01-01');
        $this->CI->db->where('invoices.date <', ((int)$year + 1) . '-01-01');
        if ($month) {
            $this->CI->db->where('invoices.date >=', $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01');
            $this->CI->db->where('invoices.date <', ($month == 12 ? ((int)$year + 1) . '-01-01' : $year . '-' . str_pad((int)$month + 1, 2, '0', STR_PAD_LEFT) . '-01'));
        }
        if ($store != -1) $this->CI->db->where('invoices.storeId', $store);
        $this->CI->db->group_by(['invoices.vendorId', 'MONTH(invoices.date)']);
        $this->CI->db->order_by('vendor_name', 'ASC');
        $this->CI->db->order_by('mes', 'ASC');
        return $this->CI->db->get()->result();
    }

    /**
     * Settlements (expenses) pagadas a vendedores por año. Agregado por
     * vendorId: total_settled. Nota: lee tabla expenses, no invoices.
     */
    public function getVendorSettlements($year, $vendorId = null)
    {
        $this->CI->db->select('vendorId, SUM(value) as total_settled');
        $this->CI->db->from('expenses');
        $this->CI->db->where('YEAR(created_at)', $year);
        if ($vendorId) $this->CI->db->where('vendorId', $vendorId);
        $this->CI->db->group_by('vendorId');
        return $this->CI->db->get()->result();
    }
}
