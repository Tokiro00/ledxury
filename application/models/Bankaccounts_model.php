<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bankaccounts_model extends MY_Model {

    // ========================================================================
    // CRUD BÁSICO
    // ========================================================================

    /** Expresión de saldo real (calculado desde movimientos) para esta tabla. */
    private function balanceSelect() {
        return $this->realBalanceExpr('banco', 'bank_accounts.idBankAccount', 'bank_accounts.initialBalance') . ' AS currentBalance';
    }

    public function getBankAccounts($storeId = null, $page = 1, $limit = 20) {
        $this->db->select('bank_accounts.*, stores.name as store_name, ' . $this->balanceSelect());
        $this->db->from('bank_accounts');
		$this->db->join('stores', 'stores.idStore = bank_accounts.storeId', 'left');
        $this->applyTenantFilter('bank_accounts');
        if ($storeId) {
            $this->db->group_start();
            $this->db->where('bank_accounts.storeId', $storeId);
            $this->db->or_where('bank_accounts.storeId', 0);
            $this->db->group_end();
        }
        $this->db->where('bank_accounts.deleted', 0);
        $this->db->order_by('bank_accounts.created_at', 'desc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getBankAccount($id) {
        $this->db->select('bank_accounts.*, stores.name as store_name, ' . $this->balanceSelect());
        $this->db->from('bank_accounts');
		$this->db->join('stores', 'stores.idStore = bank_accounts.storeId', 'left');
        $this->db->where('bank_accounts.idBankAccount', $id);
        $this->db->where('bank_accounts.deleted', 0);
        return $this->db->get()->row();
    }

    public function getBankAccountsByStore($storeId) {
        $this->db->select('bank_accounts.*, stores.name as store_name, ' . $this->balanceSelect());
        $this->db->from('bank_accounts');
		$this->db->join('stores', 'stores.idStore = bank_accounts.storeId', 'left');
        $this->applyTenantFilter('bank_accounts');
        $this->db->group_start();
        $this->db->where('bank_accounts.storeId', $storeId);
        $this->db->or_where('bank_accounts.storeId', 0);
        $this->db->group_end();
        $this->db->where('bank_accounts.deleted', 0);
        $this->db->order_by('bank_accounts.bankName', 'asc');
        return $this->db->get()->result();
    }

    public function getActiveBankAccounts($storeId = null) {
        $this->db->select('bank_accounts.*, stores.name as store_name, ' . $this->balanceSelect());
		$this->db->join('stores', 'stores.idStore = bank_accounts.storeId');
        $this->db->from('bank_accounts');
        $this->applyTenantFilter('bank_accounts');
        $this->db->where('bank_accounts.status', 'activa');
        if ($storeId) {
            $this->db->where('bank_accounts.storeId', $storeId);
        }
        $this->db->where('bank_accounts.deleted', 0);
        $this->db->order_by('bank_accounts.bankName', 'asc');
        return $this->db->get()->result();
    }

    public function save($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->tenantInsert('bank_accounts', $data);
    }

    public function update($id, $data) {
        date_default_timezone_set("America/Bogota");
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('idBankAccount', $id);
        return $this->db->update('bank_accounts', $data);
    }

    public function remove($id) {
        date_default_timezone_set("America/Bogota");
        $data = array(
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted' => 1
        );
        return $this->update($id, $data);
    }

    // ========================================================================
    // BÚSQUEDA
    // ========================================================================

    public function searchByWord($term, $storeId = null, $page = 1, $limit = 20) {
        $this->db->select('bank_accounts.*, ' . $this->balanceSelect());
        $this->db->from('bank_accounts');
        $this->db->group_start();
        $this->db->like('bank_accounts.bankName', $term);
        $this->db->or_like('bank_accounts.accountNumber', $term);
        $this->db->or_like('bank_accounts.ownerName', $term);
        $this->db->group_end();
        if ($storeId) {
            $this->db->where('bank_accounts.storeId', $storeId);
        }
        $this->db->where('bank_accounts.deleted', 0);
        $this->db->order_by('bank_accounts.created_at', 'desc');
        if ($page != -1)
            $this->db->limit($limit, (($page - 1) * $limit));
        return $this->db->get()->result();
    }

    public function getTotal($storeId = null) {
        $this->db->from('bank_accounts');
        $this->applyTenantFilter('bank_accounts');
        if ($storeId) {
            $this->db->where('bank_accounts.storeId', $storeId);
        }
        $this->db->where('bank_accounts.deleted', 0);
        return $this->db->count_all_results();
    }

    public function getTotalSearch($term, $storeId = null) {
        $this->db->from('bank_accounts');
        $this->applyTenantFilter('bank_accounts');
        $this->db->group_start();
        $this->db->like('bank_accounts.bankName', $term);
        $this->db->or_like('bank_accounts.accountNumber', $term);
        $this->db->or_like('bank_accounts.ownerName', $term);
        $this->db->group_end();
        if ($storeId) {
            $this->db->where('bank_accounts.storeId', $storeId);
        }
        $this->db->where('bank_accounts.deleted', 0);
        return $this->db->count_all_results();
    }

    // ========================================================================
    // SALDOS
    // ========================================================================

    /**
     * Sincroniza el campo currentBalance con la suma real de los movimientos.
     *
     * $amount y $operation se IGNORAN. Se conservan en la firma porque hay una
     * treintena de llamadas por todo el sistema y no vale la pena tocarlas: el
     * resultado es el mismo y esta versión no puede equivocarse.
     *
     * POR QUÉ SE DEJÓ DE SUMAR EL DELTA (22/08/2026)
     * Antes hacía `currentBalance ± amount` leyendo el saldo con
     * getBankAccount(). Pero ese getter devuelve currentBalance CALCULADO desde
     * los movimientos (balanceSelect() pone el alias encima de la columna), y el
     * módulo que llama ya insertó su movimiento antes de llamar aquí. Así que
     * tomaba un saldo que YA incluía el movimiento y le volvía a sumar el mismo
     * monto: el campo quedaba desfasado exactamente por el valor del último
     * movimiento, cada vez.
     *
     * Eso explica los desfases que fueron apareciendo uno por uno: $382.258,45
     * (el pago de Facebook del 16/08), $224.607 (el anticipo de JORGE),
     * $135.000 (el pago de la factura #4325 en caja) y $261.000 (el anticipo de
     * Christina). Cada uno era, al peso, el monto del último movimiento.
     *
     * Ahora el campo es un espejo de los movimientos y no puede divergir: dé lo
     * mismo cuántas veces se llame o en qué orden. Los saldos que ven las
     * vistas y las validaciones ya venían del cálculo, así que esto solo deja
     * de ensuciar la columna.
     */
    public function updateBalance($id, $amount = null, $operation = null) {
        date_default_timezone_set("America/Bogota");
        $real = $this->getCurrentBalance($id);
        $this->db->where('idBankAccount', $id);
        return $this->db->update('bank_accounts', array(
            'currentBalance' => $real,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function getCurrentBalance($id) {
        // Saldo REAL calculado desde movimientos (no el campo almacenado),
        // para que validaciones (ej. transferencias) usen el saldo verdadero.
        $this->db->select($this->realBalanceExpr('banco', 'bank_accounts.idBankAccount', 'bank_accounts.initialBalance') . ' AS bal');
        $this->db->from('bank_accounts');
        $this->db->where('idBankAccount', $id);
        $this->db->where('deleted', 0);
        $row = $this->db->get()->row();
        return $row ? (float)$row->bal : 0;
    }

    // ========================================================================
    // UTILITARIOS
    // ========================================================================

    public function lastID() {
        return $this->db->insert_id();
    }

    public function accountNumberExists($accountNumber, $excludeId = null) {
        $this->db->from('bank_accounts');
        $this->db->where('accountNumber', $accountNumber);
        if ($excludeId) {
            $this->db->where('idBankAccount !=', $excludeId);
        }
        return $this->db->count_all_results() > 0;
    }
}
