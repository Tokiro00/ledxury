<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Departments_model extends CI_Model {

    public function getDepartments($storeId = null) {
        $this->db->select('departments.*, users.name as manager_name, stores.name as store_name');
        $this->db->from('departments');
        $this->db->join('users', 'users.idUser = departments.leader_user_id', 'left');
        $this->db->join('stores', 'stores.idStore = departments.store_id', 'left');
        $this->db->where('departments.active', 1);
        if ($storeId !== null && $storeId !== '') {
            $this->db->where('departments.store_id', $storeId);
        }
        $this->db->order_by('departments.sort_order', 'asc');
        return $this->db->get()->result();
    }

    public function getDepartment($id) {
        $this->db->select('departments.*, users.name as manager_name, stores.name as store_name');
        $this->db->from('departments');
        $this->db->join('users', 'users.idUser = departments.leader_user_id', 'left');
        $this->db->join('stores', 'stores.idStore = departments.store_id', 'left');
        $this->db->where('departments.id', $id);
        return $this->db->get()->row();
    }

    public function save($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('departments', $data);
    }

    public function update($id, $data) {
        date_default_timezone_set("America/Bogota");
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('departments', $data);
    }

    public function remove($id) {
        return $this->update($id, array('active' => 0));
    }

    // ========================================================================
    // KPIs
    // ========================================================================

    public function getKpisByDepartment($departmentId) {
        $this->db->from('department_kpis');
        $this->db->where('department_id', $departmentId);
        $this->db->where('active', 1);
        $this->db->order_by('sort_order', 'asc');
        return $this->db->get()->result();
    }

    public function getKpi($id) {
        $this->db->from('department_kpis');
        $this->db->where('id', $id);
        return $this->db->get()->row();
    }

    public function saveKpi($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('department_kpis', $data);
    }

    public function updateKpi($id, $data) {
        date_default_timezone_set("America/Bogota");
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('department_kpis', $data);
    }

    public function removeKpi($id) {
        $this->db->where('id', $id);
        return $this->db->delete('department_kpis');
    }

    // ========================================================================
    // Bonus Calculations
    // ========================================================================

    public function getBonusesByDepartment($departmentId) {
        $this->db->select('bonus_calculations.*, users.name as calculated_by_name');
        $this->db->from('bonus_calculations');
        $this->db->join('users', 'users.idUser = bonus_calculations.calculated_by', 'left');
        $this->db->where('bonus_calculations.department_id', $departmentId);
        $this->db->order_by('bonus_calculations.year', 'desc');
        $this->db->order_by('bonus_calculations.quarter', 'desc');
        return $this->db->get()->result();
    }

    public function saveBonus($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('bonus_calculations', $data);
    }

    // ========================================================================
    // KPI Calculation Queries
    // ========================================================================

    /**
     * Ventas del mes actual: SUM(total - discount) de facturas no eliminadas
     */
    public function getSalesCurrentMonth($storeId = null) {
        $this->db->select('COALESCE(SUM(invoices.total - invoices.discount), 0) as total_sales');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('MONTH(invoices.date)', date('m'));
        $this->db->where('YEAR(invoices.date)', date('Y'));
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $row = $this->db->get()->row();
        return $row ? (float)$row->total_sales : 0;
    }

    /**
     * Porcentaje de recaudo: SUM(payment) / SUM(total - discount) * 100
     */
    public function getCollectionRateCurrentMonth($storeId = null) {
        $this->db->select('COALESCE(SUM(invoices.payment), 0) as total_payment, COALESCE(SUM(invoices.total - invoices.discount), 0) as total_sales');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('MONTH(invoices.date)', date('m'));
        $this->db->where('YEAR(invoices.date)', date('Y'));
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $row = $this->db->get()->row();
        if ($row && $row->total_sales > 0) {
            return round(($row->total_payment / $row->total_sales) * 100, 2);
        }
        return 0;
    }

    /**
     * Porcentaje de cartera mayor a 90 dias
     */
    public function getReceivablesOver90Days($storeId = null) {
        // Total de cartera pendiente
        $this->db->select('COALESCE(SUM(invoices.total - invoices.discount - invoices.payment), 0) as total_receivable');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('invoices.total - invoices.discount - invoices.payment >', 0);
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $totalRow = $this->db->get()->row();
        $totalReceivable = $totalRow ? (float)$totalRow->total_receivable : 0;

        if ($totalReceivable <= 0) return 0;

        // Cartera mayor a 90 dias
        $this->db->select('COALESCE(SUM(invoices.total - invoices.discount - invoices.payment), 0) as over90');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('invoices.total - invoices.discount - invoices.payment >', 0);
        $this->db->where('DATEDIFF(NOW(), invoices.date) >', 90);
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $over90Row = $this->db->get()->row();
        $over90 = $over90Row ? (float)$over90Row->over90 : 0;

        return round(($over90 / $totalReceivable) * 100, 2);
    }

    /**
     * Valor total de inventario: SUM(stock * cost)
     */
    public function getInventoryValue($storeId = null) {
        $this->db->select('COALESCE(SUM(inventory.stock * products.cost), 0) as inventory_value');
        $this->db->from('inventory');
        $this->db->join('products', 'products.idProduct = inventory.productId', 'inner');
        if ($storeId) {
            $this->db->where('inventory.storeId', $storeId);
        }
        $row = $this->db->get()->row();
        return $row ? (float)$row->inventory_value : 0;
    }

    /**
     * Total gastos del mes actual
     */
    public function getExpensesCurrentMonth($storeId = null) {
        $this->db->select('COALESCE(SUM(expense_records.amount), 0) as total_expenses');
        $this->db->from('expense_records');
        $this->db->where('MONTH(expense_records.expense_date)', date('m'));
        $this->db->where('YEAR(expense_records.expense_date)', date('Y'));
        $this->db->where('expense_records.status !=', 'anulado');
        if ($storeId) {
            $this->db->where('expense_records.store_id', $storeId);
        }
        $row = $this->db->get()->row();
        return $row ? (float)$row->total_expenses : 0;
    }

    /**
     * Clientes activos del mes actual (con facturas)
     */
    public function getActiveClientsCurrentMonth($storeId = null) {
        $this->db->select('COUNT(DISTINCT invoices.clientId) as total_clients');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('MONTH(invoices.date)', date('m'));
        $this->db->where('YEAR(invoices.date)', date('Y'));
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $row = $this->db->get()->row();
        return $row ? (int)$row->total_clients : 0;
    }

    // ========================================================================
    // KPI Calculations - Extended
    // ========================================================================

    /**
     * Descuento promedio / ventas: SUM(discount) / SUM(total - discount) * 100
     */
    public function getDiscountRateCurrentMonth($storeId = null) {
        $this->db->select('COALESCE(SUM(invoices.discount), 0) as total_discount, COALESCE(SUM(invoices.total - invoices.discount), 0) as net_sales');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('MONTH(invoices.date)', date('m'));
        $this->db->where('YEAR(invoices.date)', date('Y'));
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $row = $this->db->get()->row();
        if ($row && $row->net_sales > 0) {
            return round(($row->total_discount / $row->net_sales) * 100, 2);
        }
        return 0;
    }

    /**
     * Clientes dormidos >60d reactivados: clientes sin compra en 60+ dias que compraron este mes
     */
    public function getReactivatedClientsCurrentMonth($storeId = null) {
        $currentMonth = date('Y-m-01');
        $currentEnd   = date('Y-m-t');
        $cutoffDate   = date('Y-m-d', strtotime('-60 days', strtotime($currentMonth)));

        $sql = "SELECT COUNT(DISTINCT this_month.clientId) as reactivated
                FROM invoices this_month
                WHERE this_month.deleted = 0
                  AND this_month.date BETWEEN '{$currentMonth}' AND '{$currentEnd}'
                  AND this_month.clientId IN (
                      SELECT prev.clientId FROM invoices prev
                      WHERE prev.deleted = 0 AND prev.date < '{$cutoffDate}'
                  )
                  AND this_month.clientId NOT IN (
                      SELECT recent.clientId FROM invoices recent
                      WHERE recent.deleted = 0 AND recent.date >= '{$cutoffDate}' AND recent.date < '{$currentMonth}'
                  )";
        if ($storeId) {
            $sql .= " AND this_month.storeId = " . (int)$storeId;
        }
        $result = $this->db->query($sql)->row();
        return $result ? (int)$result->reactivated : 0;
    }

    /**
     * Nuevos clientes activos del mes: clientes que compraron este mes y nunca antes
     */
    public function getNewActiveClientsCurrentMonth($storeId = null) {
        $currentMonth = date('Y-m-01');
        $currentEnd   = date('Y-m-t');

        $sql = "SELECT COUNT(DISTINCT this_month.clientId) as new_clients
                FROM invoices this_month
                WHERE this_month.deleted = 0
                  AND this_month.date BETWEEN '{$currentMonth}' AND '{$currentEnd}'
                  AND this_month.clientId NOT IN (
                      SELECT prev.clientId FROM invoices prev
                      WHERE prev.deleted = 0 AND prev.date < '{$currentMonth}'
                  )";
        if ($storeId) {
            $sql .= " AND this_month.storeId = " . (int)$storeId;
        }
        $result = $this->db->query($sql)->row();
        return $result ? (int)$result->new_clients : 0;
    }

    /**
     * DSO consolidado: (cartera total / ventas ultimos 30 dias) * 30
     */
    public function getDSOConsolidado($storeId = null) {
        // Total cartera pendiente
        $this->db->select('COALESCE(SUM(invoices.total - invoices.discount - invoices.payment), 0) as total_receivable');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('invoices.total - invoices.discount - invoices.payment >', 0);
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $recRow = $this->db->get()->row();
        $totalReceivable = $recRow ? (float)$recRow->total_receivable : 0;

        // Ventas ultimos 30 dias
        $this->db->select('COALESCE(SUM(invoices.total - invoices.discount), 0) as sales_30d');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('invoices.date >=', date('Y-m-d', strtotime('-30 days')));
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $salesRow = $this->db->get()->row();
        $sales30d = $salesRow ? (float)$salesRow->sales_30d : 0;

        if ($sales30d <= 0) return 0;
        return round(($totalReceivable / $sales30d) * 30, 1);
    }

    /**
     * Recuperacion cartera >180d: pagos recibidos este mes de facturas con >180 dias
     */
    public function getRecoveryOver180Days($storeId = null) {
        $sql = "SELECT COALESCE(SUM(p.amount), 0) as recovered
                FROM payments p
                INNER JOIN invoices i ON i.idInvoice = p.invoiceId
                WHERE MONTH(p.date) = " . date('m') . "
                  AND YEAR(p.date) = " . date('Y') . "
                  AND DATEDIFF(p.date, i.date) > 180
                  AND i.deleted = 0";
        if ($storeId) {
            $sql .= " AND i.storeId = " . (int)$storeId;
        }
        $result = $this->db->query($sql)->row();
        return $result ? (float)$result->recovered : 0;
    }

    /**
     * Margen bruto %: (revenue - cost) / revenue * 100
     */
    public function getGrossMarginCurrentMonth($storeId = null) {
        $this->db->select('COALESCE(SUM(invoices.total - invoices.discount), 0) as revenue, COALESCE(SUM(invoices.cost), 0) as cost');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('MONTH(invoices.date)', date('m'));
        $this->db->where('YEAR(invoices.date)', date('Y'));
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $row = $this->db->get()->row();
        if ($row && $row->revenue > 0) {
            return round((($row->revenue - $row->cost) / $row->revenue) * 100, 2);
        }
        return 0;
    }

    /**
     * Margen neto: (revenue - cost - expenses) / revenue * 100
     */
    public function getNetMarginCurrentMonth($storeId = null) {
        $revenue = $this->getSalesCurrentMonth($storeId);
        $cost = 0;

        $this->db->select('COALESCE(SUM(invoices.cost), 0) as cost');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('MONTH(invoices.date)', date('m'));
        $this->db->where('YEAR(invoices.date)', date('Y'));
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $costRow = $this->db->get()->row();
        $cost = $costRow ? (float)$costRow->cost : 0;

        $expenses = $this->getExpensesCurrentMonth($storeId);

        if ($revenue <= 0) return 0;
        return round((($revenue - $cost - $expenses) / $revenue) * 100, 2);
    }

    /**
     * Utilidad neta trimestral: revenue - cost - expenses del trimestre actual
     */
    public function getNetProfitCurrentQuarter($storeId = null) {
        $quarter = ceil(date('n') / 3);
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;
        $year = date('Y');

        // Revenue y cost del trimestre
        $this->db->select('COALESCE(SUM(invoices.total - invoices.discount), 0) as revenue, COALESCE(SUM(invoices.cost), 0) as cost');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('MONTH(invoices.date) >=', $startMonth);
        $this->db->where('MONTH(invoices.date) <=', $endMonth);
        $this->db->where('YEAR(invoices.date)', $year);
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $row = $this->db->get()->row();
        $revenue = $row ? (float)$row->revenue : 0;
        $cost = $row ? (float)$row->cost : 0;

        // Gastos del trimestre
        $this->db->select('COALESCE(SUM(expense_records.amount), 0) as total_expenses');
        $this->db->from('expense_records');
        $this->db->where('MONTH(expense_records.expense_date) >=', $startMonth);
        $this->db->where('MONTH(expense_records.expense_date) <=', $endMonth);
        $this->db->where('YEAR(expense_records.expense_date)', $year);
        $this->db->where('expense_records.status !=', 'anulado');
        if ($storeId) {
            $this->db->where('expense_records.store_id', $storeId);
        }
        $expRow = $this->db->get()->row();
        $expenses = $expRow ? (float)$expRow->total_expenses : 0;

        return $revenue - $cost - $expenses;
    }

    /**
     * Ventas del trimestre actual
     */
    public function getSalesCurrentQuarter($storeId = null) {
        $quarter = ceil(date('n') / 3);
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;

        $this->db->select('COALESCE(SUM(invoices.total - invoices.discount), 0) as total_sales');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('MONTH(invoices.date) >=', $startMonth);
        $this->db->where('MONTH(invoices.date) <=', $endMonth);
        $this->db->where('YEAR(invoices.date)', date('Y'));
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $row = $this->db->get()->row();
        return $row ? (float)$row->total_sales : 0;
    }

    /**
     * Crecimiento vs ano anterior: ((ventas_actual - ventas_anterior) / ventas_anterior) * 100
     */
    public function getGrowthVsPreviousYear($storeId = null) {
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('m');

        // Ventas acumuladas este ano hasta el mes actual
        $this->db->select('COALESCE(SUM(invoices.total - invoices.discount), 0) as current_sales');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('YEAR(invoices.date)', $currentYear);
        $this->db->where('MONTH(invoices.date) <=', $currentMonth);
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $curRow = $this->db->get()->row();
        $currentSales = $curRow ? (float)$curRow->current_sales : 0;

        // Ventas mismo periodo ano anterior
        $this->db->select('COALESCE(SUM(invoices.total - invoices.discount), 0) as prev_sales');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('YEAR(invoices.date)', $currentYear - 1);
        $this->db->where('MONTH(invoices.date) <=', $currentMonth);
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $prevRow = $this->db->get()->row();
        $prevSales = $prevRow ? (float)$prevRow->prev_sales : 0;

        if ($prevSales <= 0) return 0;
        return round((($currentSales - $prevSales) / $prevSales) * 100, 2);
    }

    /**
     * Dias inventario: (valor inventario / COGS ultimos 30 dias) * 30
     */
    public function getInventoryDays($storeId = null) {
        $inventoryValue = $this->getInventoryValue($storeId);

        // COGS ultimos 30 dias
        $this->db->select('COALESCE(SUM(invoices.cost), 0) as cogs_30d');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where('invoices.date >=', date('Y-m-d', strtotime('-30 days')));
        if ($storeId) {
            $this->db->where('invoices.storeId', $storeId);
        }
        $row = $this->db->get()->row();
        $cogs30d = $row ? (float)$row->cogs_30d : 0;

        if ($cogs30d <= 0) return 0;
        return round(($inventoryValue / $cogs30d) * 30, 0);
    }

    /**
     * Quiebres de stock: % productos con stock <= 0
     */
    public function getStockBreakageRate($storeId = null) {
        // Total productos en inventario
        $this->db->select('COUNT(*) as total_products');
        $this->db->from('inventory');
        if ($storeId) {
            $this->db->where('inventory.storeId', $storeId);
        }
        $totalRow = $this->db->get()->row();
        $totalProducts = $totalRow ? (int)$totalRow->total_products : 0;

        if ($totalProducts <= 0) return 0;

        // Productos agotados
        $this->db->select('COUNT(*) as out_of_stock');
        $this->db->from('inventory');
        $this->db->where('inventory.stock <=', 0);
        if ($storeId) {
            $this->db->where('inventory.storeId', $storeId);
        }
        $outRow = $this->db->get()->row();
        $outOfStock = $outRow ? (int)$outRow->out_of_stock : 0;

        return round(($outOfStock / $totalProducts) * 100, 2);
    }

    /**
     * Faltante inventario / ventas: diferencia del ultimo conteo vs ventas
     */
    public function getInventoryShortageRate($storeId = null) {
        // Buscar diferencias negativas del ultimo conteo de inventario
        $sql = "SELECT COALESCE(SUM(ABS(ci.diff) * p.cost), 0) as shortage_value
                FROM count_items ci
                INNER JOIN products p ON p.idProduct = ci.productId
                INNER JOIN counts c ON c.idCount = ci.countId
                WHERE ci.diff < 0";
        if ($storeId) {
            $sql .= " AND c.storeId = " . (int)$storeId;
        }
        $sql .= " AND c.date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = $this->db->query($sql)->row();
        $shortageValue = $result ? (float)$result->shortage_value : 0;

        $totalSales = $this->getSalesCurrentMonth($storeId);
        if ($totalSales <= 0) return 0;

        return round(($shortageValue / $totalSales) * 100, 2);
    }

    /**
     * Gastos admin / ventas: gastos admin del mes / ventas del mes * 100
     */
    public function getAdminExpenseRateCurrentMonth($storeId = null) {
        $expenses = $this->getExpensesCurrentMonth($storeId);
        $sales = $this->getSalesCurrentMonth($storeId);

        if ($sales <= 0) return 0;
        return round(($expenses / $sales) * 100, 2);
    }

    // ========================================================================
    // Vendor Monthly Sales
    // ========================================================================

    /**
     * Obtiene las ventas mensuales de un vendedor para un año dado
     * Retorna array asociativo [mes => total_ventas]
     */
    public function getVendorMonthlySales($vendorId, $year) {
        $this->db->select('MONTH(invoices.date) as month, COALESCE(SUM(invoices.total - invoices.discount), 0) as total_sales');
        $this->db->from('invoices');
        $this->db->where('invoices.vendorId', $vendorId);
        $this->db->where('YEAR(invoices.date)', $year);
        $this->db->where('invoices.deleted', 0);
        $this->db->group_by('MONTH(invoices.date)');
        $results = $this->db->get()->result();

        $monthlySales = array();
        for ($i = 1; $i <= 12; $i++) {
            $monthlySales[$i] = 0;
        }
        foreach ($results as $row) {
            $monthlySales[(int)$row->month] = (float)$row->total_sales;
        }
        return $monthlySales;
    }

    public function lastID() {
        return $this->db->insert_id();
    }
}
