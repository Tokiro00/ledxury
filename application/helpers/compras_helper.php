<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helpers del módulo de compras/proveedores (portado de stockaccessories.co,
 * 20/08/2026). Formato de dinero y contabilidad simple de asientos.
 *
 * Diferencias con el fork de origen:
 *  - Moneda base COP (allá USD).
 *  - acc_entry / acc_void_entries también actualizan los saldos denormalizados
 *    (subaccounts / auxiliary_subaccounts) porque las vistas contables de
 *    Ledxury los leen.
 *  - acc_provider_aux: auxiliar por proveedor (Ledxury lleva la CxP con
 *    auxiliares; el fork de origen no los usaba).
 *
 * Cargar con $this->load->helper('compras') en los controladores del módulo.
 */

if (!function_exists('mam_table_exists')) {
    /** table_exists con caché por request (se consulta mucho en vistas). */
    function mam_table_exists(string $table): bool
    {
        static $cache = array();
        if (!array_key_exists($table, $cache)) {
            $CI =& get_instance();
            $cache[$table] = $CI->db->table_exists($table);
        }
        return $cache[$table];
    }
}

if (!function_exists('money')) {
    /** Formato moneda COP: $1.234.567 (sin decimales por defecto). */
    function money($value, int $decimals = 0): string
    {
        return '$' . number_format((float) $value, $decimals, ',', '.');
    }
}

if (!function_exists('num_fmt')) {
    function num_fmt($value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }
}

if (!function_exists('acc_setting')) {
    /** subaccount_id configurado en accounting_settings para una llave. */
    function acc_setting(string $key): ?int
    {
        static $cache = null;
        if (!mam_table_exists('accounting_settings')) return null;
        if ($cache === null) {
            $CI =& get_instance();
            $cache = array();
            foreach ($CI->db->get('accounting_settings')->result() as $r) {
                $cache[$r->setting_key] = (int) $r->subaccount_id;
            }
        }
        return isset($cache[$key]) ? $cache[$key] : null;
    }
}

if (!function_exists('acc_provider_aux')) {
    /**
     * Auxiliar contable del proveedor (lo crea si no existe) — mismo criterio
     * que Accounting_lib::getOrCreateProviderAuxAccount.
     */
    function acc_provider_aux($providerId): ?int
    {
        $CI =& get_instance();
        $providerId = (int) $providerId;
        if ($providerId <= 0) return null;
        static $cache = array();
        if (isset($cache[$providerId])) return $cache[$providerId];

        $row = $CI->db->query("SELECT id FROM auxiliary_subaccounts
            WHERE accountAccount = ? AND accountType = 'provider' AND COALESCE(deleted,0) = 0 LIMIT 1", array($providerId))->row();
        if ($row) return $cache[$providerId] = (int) $row->id;

        $prov = $CI->db->query("SELECT name, puc_code FROM providers WHERE idProvider = ? LIMIT 1", array($providerId))->row();
        if (!$prov) return null;
        $puc = !empty($prov->puc_code) ? $prov->puc_code : '220501';
        $CI->db->insert('auxiliary_subaccounts', array(
            'accountID'      => (int) $puc,
            'accountName'    => $prov->name,
            'accountAccount' => $providerId,
            'accountType'    => 'provider',
            'accountSide'    => '2',
            'accountBalance' => 0,
            'accountDebit'   => 0,
            'accountCredit'  => 0,
            'deleted'        => 0,
        ));
        return $cache[$providerId] = (int) $CI->db->insert_id();
    }
}

if (!function_exists('acc_apply_balance')) {
    /** Actualiza saldos denormalizados de una subcuenta o un auxiliar. */
    function acc_apply_balance(string $table, int $id, float $amount, string $side): void
    {
        if ($id <= 0 || $amount == 0.0) return;
        $CI =& get_instance();
        $col = $side === 'debit' ? 'accountDebit' : 'accountCredit';
        $CI->db->query("
            UPDATE {$table} SET
                {$col} = {$col} + ?,
                accountBalance = accountBalance + IF(accountSide = '1', ?, ?)
            WHERE id = ?",
            array($amount, $side === 'debit' ? $amount : -$amount,
                  $side === 'credit' ? $amount : -$amount, $id));
    }
}

if (!function_exists('acc_entry')) {
    /**
     * Inserta un asiento simple (1 debe / 1 haber) y actualiza los saldos
     * denormalizados. Acepta debit_aux / credit_aux. Devuelve entryID o null
     * si faltan cuentas — nunca lanza: la contabilidad no debe tumbar la
     * operación que la origina.
     */
    function acc_entry(array $e): ?int
    {
        $CI =& get_instance();
        try {
            if (!mam_table_exists('entries')) return null;
            $dr = (int) (isset($e['debit']) ? $e['debit'] : 0);
            $cr = (int) (isset($e['credit']) ? $e['credit'] : 0);
            $amount = round((float) (isset($e['amount']) ? $e['amount'] : 0), 2);
            if ($dr <= 0 || $cr <= 0 || $amount <= 0) return null;
            $amountStr = number_format($amount, 2, '.', '');
            $sessUser = $CI->session->userdata('user_data');
            $user = isset($e['user']) ? $e['user'] : (isset($sessUser['uname']) ? $sessUser['uname'] : 'system');

            $CI->db->insert('entries', array(
                'userID'                => $user,
                'entryDescription'      => (string) (isset($e['description']) ? $e['description'] : ''),
                'entryDate'             => isset($e['date']) ? $e['date'] : date('Y-m-d'),
                'entryStoreId'          => isset($e['store_id']) ? $e['store_id'] : 1,
                'entryType'             => 1,
                'entryTransactionType'  => isset($e['transaction_type']) ? $e['transaction_type'] : null,
                'entryTransactionId'    => isset($e['transaction_id']) ? $e['transaction_id'] : null,
                'entryDebitAccount'     => $dr,
                'entryDebitAuxaccount'  => isset($e['debit_aux']) ? $e['debit_aux'] : null,
                'entryDebitBalance'     => $amountStr,
                'entryCreditAccount'    => $cr,
                'entryCreditAuxaccount' => isset($e['credit_aux']) ? $e['credit_aux'] : null,
                'entryCreditBalance'    => $amountStr,
                'entryStatus'           => 1,
                'created_by'            => $user,
                'entryCreateDate'       => date('Y-m-d H:i:s'),
                'deleted'               => 0,
            ));
            $entryId = (int) $CI->db->insert_id();

            acc_apply_balance('subaccounts', $dr, $amount, 'debit');
            acc_apply_balance('subaccounts', $cr, $amount, 'credit');
            if (!empty($e['debit_aux']))  acc_apply_balance('auxiliary_subaccounts', (int) $e['debit_aux'], $amount, 'debit');
            if (!empty($e['credit_aux'])) acc_apply_balance('auxiliary_subaccounts', (int) $e['credit_aux'], $amount, 'credit');

            return $entryId;
        } catch (Throwable $t) {
            log_message('error', 'acc_entry omitido: ' . $t->getMessage());
            return null;
        }
    }
}

if (!function_exists('acc_void_entries')) {
    /**
     * Anula (soft-delete) los asientos vivos de una transacción y REVIERTE su
     * efecto en los saldos denormalizados. Devuelve cuántos anuló.
     */
    function acc_void_entries($transactionId, array $types, string $reason): int
    {
        $CI =& get_instance();
        try {
            if (!mam_table_exists('entries') || (int) $transactionId <= 0 || empty($types)) return 0;
            $ph = implode(',', array_fill(0, count($types), '?'));
            $rows = $CI->db->query("
                SELECT entryID, entryDebitAccount, entryDebitAuxaccount, entryCreditAccount, entryCreditAuxaccount, entryDebitBalance
                FROM entries
                WHERE entryTransactionType IN ($ph) AND entryTransactionId = ? AND COALESCE(deleted,0) = 0",
                array_merge($types, array((int) $transactionId)))->result();
            foreach ($rows as $r) {
                $CI->db->query("UPDATE entries SET deleted = 1, deleted_at = NOW(),
                    entryStatusComment = CONCAT(COALESCE(entryStatusComment,''), ' [', ?, ']')
                    WHERE entryID = ?", array($reason, (int) $r->entryID));
                $amt = (float) $r->entryDebitBalance;
                acc_apply_balance('subaccounts', (int) $r->entryDebitAccount, -$amt, 'debit');
                acc_apply_balance('subaccounts', (int) $r->entryCreditAccount, -$amt, 'credit');
                if ($r->entryDebitAuxaccount)  acc_apply_balance('auxiliary_subaccounts', (int) $r->entryDebitAuxaccount, -$amt, 'debit');
                if ($r->entryCreditAuxaccount) acc_apply_balance('auxiliary_subaccounts', (int) $r->entryCreditAuxaccount, -$amt, 'credit');
            }
            return count($rows);
        } catch (Throwable $t) {
            log_message('error', 'acc_void_entries omitido: ' . $t->getMessage());
            return 0;
        }
    }
}
