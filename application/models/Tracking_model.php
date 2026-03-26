<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tracking_model extends CI_Model {

    // ================================================================
    // WEEKLY TRACKING
    // ================================================================

    public function getWeeklyData($year, $month, $week)
    {
        $this->db->select('tracking_weekly.*, users.name as vendor_name');
        $this->db->from('tracking_weekly');
        $this->db->join('users', 'users.idUser = tracking_weekly.vendorId', 'left');
        $this->db->where('year', $year);
        $this->db->where('month', $month);
        $this->db->where('week', $week);
        return $this->db->get()->result();
    }

    public function getWeeklyExtras($year, $month, $week)
    {
        $this->db->from('tracking_weekly_extras');
        $this->db->where('year', $year);
        $this->db->where('month', $month);
        $this->db->where('week', $week);
        return $this->db->get()->row();
    }

    public function saveWeeklySnapshot($year, $month, $week, $vendorData, $extras)
    {
        // Save each vendor row
        foreach ($vendorData as $row) {
            $sql = "INSERT INTO tracking_weekly (year, month, week, vendorId, ventas, cobros)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE ventas = VALUES(ventas), cobros = VALUES(cobros)";
            $this->db->query($sql, array(
                $year, $month, $week,
                $row['vendorId'],
                $row['ventas'],
                $row['cobros']
            ));
        }

        // Save extras
        $sql = "INSERT INTO tracking_weekly_extras (year, month, week, cartera_total, inventario, gastos_semana, notas)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    cartera_total = VALUES(cartera_total),
                    inventario = VALUES(inventario),
                    gastos_semana = VALUES(gastos_semana),
                    notas = VALUES(notas)";
        $this->db->query($sql, array(
            $year, $month, $week,
            isset($extras['cartera_total']) ? $extras['cartera_total'] : 0,
            isset($extras['inventario']) ? $extras['inventario'] : 0,
            isset($extras['gastos_semana']) ? $extras['gastos_semana'] : 0,
            isset($extras['notas']) ? $extras['notas'] : ''
        ));

        return true;
    }

    // ================================================================
    // CIERRE MENSUAL
    // ================================================================

    public function getCierre($year, $month)
    {
        $this->db->from('cierre_mensual');
        $this->db->where('year', $year);
        $this->db->where('month', $month);
        return $this->db->get()->row();
    }

    public function saveCierre($data)
    {
        $existing = $this->getCierre($data['year'], $data['month']);
        if ($existing) {
            $this->db->where('year', $data['year']);
            $this->db->where('month', $data['month']);
            return $this->db->update('cierre_mensual', $data);
        } else {
            return $this->db->insert('cierre_mensual', $data);
        }
    }

    public function getCierresYear($year)
    {
        $this->db->from('cierre_mensual');
        $this->db->where('year', $year);
        $this->db->order_by('month', 'ASC');
        return $this->db->get()->result();
    }

    // ================================================================
    // LIVE QUERIES
    // ================================================================

    /**
     * Ventas por vendedor en un rango de fechas
     * Solo tiendas MDE: storeId IN (1,3,5)
     */
    public function getLiveVentasSemana($mondayDate, $sundayDate, $storeIds = array(1, 3, 5))
    {
        $this->db->select('invoices.vendorId, users.name as vendor_name, SUM(invoices.total) as total_ventas');
        $this->db->from('invoices');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->where_in('invoices.state', array(1, 2, 3));
        $this->db->where('invoices.deleted', 0);
        $this->db->where('invoices.date >=', $mondayDate);
        $this->db->where('invoices.date <', $sundayDate);
        $this->db->where_in('invoices.storeId', $storeIds);
        $this->db->group_by('invoices.vendorId');
        return $this->db->get()->result();
    }

    /**
     * Cobros por vendedor en un rango de fechas
     */
    public function getLiveCobrosSemana($mondayDate, $sundayDate)
    {
        $this->db->select('payments.vendorId, users.name as vendor_name, SUM(payments.payment) as total_cobros');
        $this->db->from('payments');
        $this->db->join('users', 'users.idUser = payments.vendorId');
        $this->db->where('payments.deleted', 0);
        $this->db->where('payments.date >=', $mondayDate);
        $this->db->where('payments.date <', $sundayDate);
        $this->db->group_by('payments.vendorId');
        return $this->db->get()->result();
    }

    /**
     * Ventas acumuladas del mes por vendedor
     */
    public function getLiveVentasMes($year, $month, $storeIds = array(1, 3, 5))
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        $to   = date('Y-m-d', strtotime($to . ' +1 day'));

        $this->db->select('invoices.vendorId, users.name as vendor_name, SUM(invoices.total) as total_ventas');
        $this->db->from('invoices');
        $this->db->join('users', 'users.idUser = invoices.vendorId');
        $this->db->where_in('invoices.state', array(1, 2, 3));
        $this->db->where('invoices.deleted', 0);
        $this->db->where('invoices.date >=', $from);
        $this->db->where('invoices.date <', $to);
        $this->db->where_in('invoices.storeId', $storeIds);
        $this->db->group_by('invoices.vendorId');
        return $this->db->get()->result();
    }

    /**
     * Cobros acumulados del mes por vendedor
     */
    public function getLiveCobrosMes($year, $month)
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        $to   = date('Y-m-d', strtotime($to . ' +1 day'));

        $this->db->select('payments.vendorId, users.name as vendor_name, SUM(payments.payment) as total_cobros');
        $this->db->from('payments');
        $this->db->join('users', 'users.idUser = payments.vendorId');
        $this->db->where('payments.deleted', 0);
        $this->db->where('payments.date >=', $from);
        $this->db->where('payments.date <', $to);
        $this->db->group_by('payments.vendorId');
        return $this->db->get()->result();
    }

    /**
     * Total ventas brutas del mes (todas las tiendas MDE)
     */
    public function getLiveVentasBrutasMes($year, $month, $storeIds = array(1, 3, 5))
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        $to   = date('Y-m-d', strtotime($to . ' +1 day'));

        $this->db->select('SUM(invoices.total) as total');
        $this->db->from('invoices');
        $this->db->where_in('invoices.state', array(1, 2, 3));
        $this->db->where('invoices.deleted', 0);
        $this->db->where('invoices.date >=', $from);
        $this->db->where('invoices.date <', $to);
        $this->db->where_in('invoices.storeId', $storeIds);
        $row = $this->db->get()->row();
        return $row ? (int) $row->total : 0;
    }

    /**
     * Total cobros del mes
     */
    public function getLiveCobrosBrutosMes($year, $month)
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        $to   = date('Y-m-d', strtotime($to . ' +1 day'));

        $this->db->select('SUM(payments.payment) as total');
        $this->db->from('payments');
        $this->db->where('payments.deleted', 0);
        $this->db->where('payments.date >=', $from);
        $this->db->where('payments.date <', $to);
        $row = $this->db->get()->row();
        return $row ? (int) $row->total : 0;
    }

    /**
     * Cartera pendiente total (facturas con saldo > 0)
     */
    public function getCarteraPendiente()
    {
        $this->db->select('SUM(invoices.total - (invoices.payment + invoices.discount)) as total');
        $this->db->from('invoices');
        $this->db->where('invoices.deleted', 0);
        $this->db->where("(invoices.state = '0' OR invoices.state = '1')");
        $this->db->where('(invoices.total - (invoices.payment + invoices.discount)) >', 0);
        $row = $this->db->get()->row();
        return $row ? (int) $row->total : 0;
    }

    /**
     * Inventario valorizado por tiendas
     */
    public function getInventarioValorizado($storeIds = array(1, 8))
    {
        $this->db->select('SUM(inventory.stock * products.cost_cop) as total');
        $this->db->from('inventory');
        $this->db->join('products', 'products.idProduct = inventory.idProduct');
        $this->db->where('inventory.stock >', 0);
        $this->db->where_in('inventory.idStore', $storeIds);
        $row = $this->db->get()->row();
        return $row ? (int) $row->total : 0;
    }

    /**
     * Gastos operacionales del mes
     */
    public function getLiveGastosMes($year, $month)
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        $to   = date('Y-m-d', strtotime($to . ' +1 day'));

        $this->db->select('SUM(expense_records.amount) as total');
        $this->db->from('expense_records');
        $this->db->where('expense_records.status', 'pagado');
        $this->db->where('expense_records.expense_date >=', $from);
        $this->db->where('expense_records.expense_date <', $to);
        $row = $this->db->get()->row();
        return $row ? (int) $row->total : 0;
    }

    /**
     * Gastos de la semana
     */
    public function getLiveGastosSemana($mondayDate, $sundayDate)
    {
        $this->db->select('SUM(expense_records.amount) as total');
        $this->db->from('expense_records');
        $this->db->where('expense_records.status', 'pagado');
        $this->db->where('expense_records.expense_date >=', $mondayDate);
        $this->db->where('expense_records.expense_date <', $sundayDate);
        $row = $this->db->get()->row();
        return $row ? (int) $row->total : 0;
    }

    /**
     * Snapshots existentes para un mes (para grafico de evolucion)
     */
    public function getWeeklySnapshots($year, $month)
    {
        $this->db->select('week, SUM(ventas) as total_ventas, SUM(cobros) as total_cobros');
        $this->db->from('tracking_weekly');
        $this->db->where('year', $year);
        $this->db->where('month', $month);
        $this->db->group_by('week');
        $this->db->order_by('week', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Ventas acumuladas de un vendedor en el mes
     */
    public function getLiveVentasVendorMes($vendorId, $year, $month, $storeIds = array(1, 3, 5))
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        $to   = date('Y-m-d', strtotime($to . ' +1 day'));

        $this->db->select('SUM(invoices.total) as total');
        $this->db->from('invoices');
        $this->db->where_in('invoices.state', array(1, 2, 3));
        $this->db->where('invoices.deleted', 0);
        $this->db->where('invoices.vendorId', $vendorId);
        $this->db->where('invoices.date >=', $from);
        $this->db->where('invoices.date <', $to);
        $this->db->where_in('invoices.storeId', $storeIds);
        $row = $this->db->get()->row();
        return $row ? (int) $row->total : 0;
    }

    /**
     * Ranking de vendedores (posicion) en ventas acumuladas del mes
     */
    public function getVendorRanking($year, $month, $storeIds = array(1, 3, 5))
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        $to   = date('Y-m-d', strtotime($to . ' +1 day'));

        $this->db->select('invoices.vendorId, SUM(invoices.total) as total_ventas');
        $this->db->from('invoices');
        $this->db->where_in('invoices.state', array(1, 2, 3));
        $this->db->where('invoices.deleted', 0);
        $this->db->where('invoices.date >=', $from);
        $this->db->where('invoices.date <', $to);
        $this->db->where_in('invoices.storeId', $storeIds);
        $this->db->group_by('invoices.vendorId');
        $this->db->order_by('total_ventas', 'DESC');
        return $this->db->get()->result();
    }
}
