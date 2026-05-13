<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Accounting Library
 *
 * Biblioteca centralizada para generación de asientos contables automáticos
 * Soporta Plan Único de Cuentas (PUC) Colombia
 * Diseñado para multi-bodega
 *
 * FASE 1-2: Métodos mínimos para Caja y Bancos
 * FASE 3: Integración completa con compras, facturas, devoluciones
 *
 * @package    MAM ERP
 * @subpackage Libraries
 * @category   Accounting
 * @author     Claude Code
 * @version    1.0.0 - Fase 1-2
 */
class Accounting_lib {

    protected $CI;
    protected $_settingsCache = array();

    /**
     * Constructor
     * Carga modelos necesarios
     */
    public function __construct() {
        $this->CI =& get_instance();

        // Cargar modelos necesarios
        $this->CI->load->model('Entry_model');
        $this->CI->load->model('Subaccount_model');
        $this->CI->load->model('Auxsubaccount_model');
        $this->CI->load->model('Accountingperiods_model');
        $this->CI->load->model('Accountingsettings_model');
        $this->CI->load->model('logs_model');

        // Cargar helpers
        $this->CI->load->helper('mam_helper');

        // Timezone
        date_default_timezone_set("America/Bogota");
    }

    /**
     * Obtiene subaccount_id desde configuración contable con cache
     * @param string $key Clave del setting (ej: 'account_cash')
     * @param string $fallbackPuc Código PUC de fallback si no hay setting
     * @return int|null
     */
    protected function getConfiguredAccount($key, $fallbackPuc) {
        if (isset($this->_settingsCache[$key])) {
            return $this->_settingsCache[$key];
        }

        // Si el modelo de settings está cargado, intentar primero la config.
        // Si NO está cargado (CLI, controllers que no lo cargan en su
        // constructor), saltamos al fallback por PUC. Antes retornábamos
        // null acá, lo que rompía recordInvoice/recordRefund en CLI.
        if (!empty($this->CI->accountingsettings_model)) {
            $subId = $this->CI->accountingsettings_model->getSubaccountId($key);
            if ($subId) {
                $this->_settingsCache[$key] = $subId;
                return $subId;
            }
        }

        // Fallback: buscar por PUC code
        $result = $this->getAccountByPucCode($fallbackPuc);
        $this->_settingsCache[$key] = $result;
        return $result;
    }

    /**
     * Obtiene puc_code desde configuración contable
     * @param string $key Clave del setting
     * @param string $fallbackPuc Código PUC de fallback
     * @return string
     */
    protected function getConfiguredPucCode($key, $fallbackPuc) {
        if (empty($this->CI->accountingsettings_model)) {
            return $fallbackPuc;
        }
        try {
            $puc = $this->CI->accountingsettings_model->getPucCode($key);
            return $puc ?: $fallbackPuc;
        } catch (Exception $e) {
            return $fallbackPuc;
        }
    }

    /**
     * Verifica si un período está cerrado
     *
     * @param string $date Fecha a verificar (Y-m-d)
     * @param int $storeId ID de bodega (opcional)
     * @return bool TRUE si el período está cerrado
     */
    public function isPeriodClosed($date, $storeId = null) {
        $year = date('Y', strtotime($date));
        $month = date('n', strtotime($date));
        return $this->CI->Accountingperiods_model->isPeriodClosed($year, $month, $storeId);
    }

    // ========================================================================
    // MÉTODOS PRINCIPALES - FASE 2: CAJA Y BANCOS
    // ========================================================================

    /**
     * Registra asiento contable por movimiento de caja/banco
     *
     * Usado por el módulo de Caja y Bancos para registrar:
     * - Ingresos de efectivo
     * - Egresos de efectivo
     * - Transferencias entre cajas/bancos
     *
     * @param int    $movementId    ID del movimiento de caja (cash_movements.id)
     * @param string $type          Tipo: 'income', 'expense', 'transfer'
     * @param int    $accountId     ID de subcuenta origen (caja o banco)
     * @param float  $amount        Monto
     * @param int    $storeId       ID de bodega
     * @param string $description   Descripción del movimiento
     * @param int    $userId        ID del usuario que registra
     * @param int    $destinationAccountId ID de cuenta destino (solo para transferencias)
     * @return bool  TRUE si se creó el asiento, FALSE si falló
     */
    public function recordCashMovement($movementId, $type, $accountId, $amount, $storeId, $description, $userId, $destinationAccountId = null, $costCenterId = null) {

        // Validar parámetros
        if (!$movementId || !$type || !$accountId || !$amount || !$storeId || !$userId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordCashMovement - Parámetros faltantes");
            return false;
        }

        $this->CI->db->trans_start();

        try {
            switch ($type) {
                case 'income':
                    // Ingreso de efectivo
                    // Débito: Caja/Banco | Crédito: (se define desde el módulo, ej: Clientes, Ventas, Otros Ingresos)
                    // NOTA: Para ingresos simples, solo registramos el débito a caja
                    // El crédito se maneja desde recordPayment() o desde el módulo de Caja
                    break;

                case 'expense':
                    // Egreso de efectivo
                    // Débito: Gasto/Compra | Crédito: Caja/Banco
                    break;

                case 'transfer':
                    // Transferencia entre cajas/bancos
                    // Débito: Caja/Banco destino | Crédito: Caja/Banco origen
                    if (!$destinationAccountId) {
                        $this->CI->logs_model->logMessage("error", "Accounting_lib::recordCashMovement - Transferencia requiere cuenta destino");
                        $this->CI->db->trans_rollback();
                        return false;
                    }

                    $result = $this->createEntry(
                        $destinationAccountId,    // Débito: Cuenta destino
                        null,                      // Sin auxiliar débito
                        $accountId,                // Crédito: Cuenta origen
                        null,                      // Sin auxiliar crédito
                        $amount,
                        $description,
                        $userId,
                        $storeId,
                        'cash_movement',
                        $movementId,
                        null,
                        $costCenterId
                    );

                    if (!$result) {
                        $this->CI->db->trans_rollback();
                        return false;
                    }
                    break;

                default:
                    $this->CI->logs_model->logMessage("error", "Accounting_lib::recordCashMovement - Tipo no válido: $type");
                    $this->CI->db->trans_rollback();
                    return false;
            }

            $this->CI->db->trans_complete();
            return $this->CI->db->trans_status();

        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordCashMovement - Error: " . $e->getMessage());
            $this->CI->db->trans_rollback();
            return false;
        }
    }

    /**
     * Registra asiento contable por pago recibido
     *
     * Reemplaza la lógica inline existente en Payments.php
     *
     * Asiento generado:
     * Débito: Caja/Banco (según método de pago)
     * Crédito: Clientes (cuenta por cobrar)
     *
     * @param int   $paymentId   ID del pago (payments.id)
     * @param int   $invoiceId   ID de la factura
     * @param int   $clientId    ID del cliente
     * @param float $amount      Monto del pago
     * @param int   $methodId    ID del método de pago (payment_methods.id)
     * @param int   $storeId     ID de bodega
     * @param int   $userId      ID del usuario que registra
     * @param int   $cashAccountId ID de la cuenta de caja/banco (opcional, para integración Fase 2)
     * @return bool TRUE si se creó el asiento, FALSE si falló
     */
    public function recordPayment($paymentId, $invoiceId, $clientId, $amount, $methodId, $storeId, $userId, $cashAccountId = null, $costCenterId = null) {

        // Validar parámetros
        if (!$paymentId || !$clientId || !$amount || !$storeId || !$userId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordPayment - Parámetros faltantes");
            return false;
        }

        $this->CI->db->trans_start();

        try {
            // 1. Obtener o determinar cuenta de caja/banco
            if ($cashAccountId) {
                // Fase 2: Se proporciona la cuenta desde el módulo de Caja y Bancos
                $debitAccountId = $cashAccountId;
            } else {
                // Fase 1: Determinar por método de pago (fallback)
                $debitAccountId = $this->getCashAccountByMethod($methodId, $storeId);
                if (!$debitAccountId) {
                    $this->CI->logs_model->logMessage("error", "Accounting_lib::recordPayment - No se encontró cuenta de caja/banco para método $methodId y bodega $storeId");
                    $this->CI->db->trans_rollback();
                    return false;
                }
            }

            // 2. Obtener cuenta de clientes (cuentas por cobrar)
            $creditAccountId = $this->getReceivableAccount($storeId);
            if (!$creditAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordPayment - No se encontró cuenta de clientes para bodega $storeId");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 3. Obtener o crear cuenta auxiliar del cliente
            $creditAuxAccountId = $this->getOrCreateClientAuxAccount($clientId, $storeId);
            if (!$creditAuxAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordPayment - No se pudo crear auxiliar para cliente $clientId");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 4. Crear asiento contable
            $description = "Pago recibido - Factura #$invoiceId";

            $result = $this->createEntry(
                $debitAccountId,        // Débito: Caja/Banco
                null,                   // Sin auxiliar débito
                $creditAccountId,       // Crédito: Clientes
                $creditAuxAccountId,    // Auxiliar: Cliente específico
                $amount,
                $description,
                $userId,
                $storeId,
                'payment',
                $paymentId,
                null,
                $costCenterId
            );

            if (!$result) {
                $this->CI->db->trans_rollback();
                return false;
            }

            $this->CI->db->trans_complete();
            return $this->CI->db->trans_status();

        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordPayment - Error: " . $e->getMessage());
            $this->CI->db->trans_rollback();
            return false;
        }
    }

    // ========================================================================
    // MÉTODOS AUXILIARES - OBTENCIÓN DE CUENTAS
    // ========================================================================

    /**
     * Obtiene cuenta de caja por bodega
     * Busca subcuenta con pucCode 110505 (Caja General)
     *
     * @param int $storeId ID de bodega
     * @return int|null ID de subcuenta o NULL si no existe
     */
    public function getCashAccount($storeId) {
        return $this->getConfiguredAccount('account_cash', '110505');
    }

    /**
     * Obtiene cuenta de banco por bodega
     * Busca subcuenta con pucCode 111005 (Bancos Moneda Nacional)
     *
     * @param int $storeId ID de bodega
     * @return int|null ID de subcuenta o NULL si no existe
     */
    public function getBankAccount($storeId) {
        return $this->getConfiguredAccount('account_bank', '111005');
    }

    /**
     * Determina cuenta de caja o banco según método de pago
     *
     * NOTA: Este es un método de fallback para Fase 1
     * En Fase 2, el módulo de Caja y Bancos proporciona el cashAccountId directamente
     *
     * @param int $methodId ID del método de pago
     * @param int $storeId  ID de bodega
     * @return int|null ID de subcuenta o NULL si no existe
     */
    public function getCashAccountByMethod($methodId, $storeId) {
        // Cargar modelo de métodos de pago
        $this->CI->load->model('payments_model');

        $method = $this->CI->payments_model->getPaymentMethod($methodId);

        if (!$method) {
            return null;
        }

        // Determinar si es efectivo o banco por nombre del método
        // IMPORTANTE: Ajustar según los nombres reales en payment_methods
        $methodName = strtolower($method->name);

        if (strpos($methodName, 'efectivo') !== false || strpos($methodName, 'cash') !== false) {
            return $this->getCashAccount($storeId);
        } else {
            // Por defecto, asumir banco
            return $this->getBankAccount($storeId);
        }
    }

    /**
     * Obtiene cuenta de clientes (cuentas por cobrar)
     * Busca subcuenta con pucCode 130505 (Clientes Nacionales)
     *
     * @param int $storeId ID de bodega
     * @return int|null ID de subcuenta o NULL si no existe
     */
    public function getReceivableAccount($storeId) {
        return $this->getConfiguredAccount('account_receivable', '130505');
    }

    /**
     * Obtiene o crea cuenta auxiliar para un cliente
     *
     * Busca auxiliar existente o crea uno nuevo
     * accountSide='1' (Débito) - Los clientes son activos
     * accountStatement='1' (Balance)
     * accountType='client'
     *
     * @param int $clientId ID del cliente
     * @param int $storeId  ID de bodega
     * @return int|null ID de cuenta auxiliar o NULL si falla
     */
    public function getOrCreateClientAuxAccount($clientId, $storeId) {

        // Cargar modelo de clientes
        $this->CI->load->model('clients_model');

        // Buscar auxiliar existente
        $query = $this->CI->db->select('id')
            ->from('auxiliary_subaccounts')
            ->where('accountAccount', $clientId) // Vinculado al cliente
            ->where('accountType', 'client')
            ->where('deleted', 0)
            ->get();

        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }

        // Si no existe, crear nuevo
        $client = $this->CI->clients_model->getClient($clientId);

        if (!$client) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::getOrCreateClientAuxAccount - Cliente $clientId no existe");
            return null;
        }

        $receivablePuc = $this->getConfiguredPucCode('account_receivable', '130505');

        $data = array(
            'accountID' => (int)$receivablePuc,
            'accountName' => $client->name,
            'accountAccount' => $clientId,
            'accountSide' => '1',  // Débito (VARCHAR)
            'accountStatement' => '1',  // Balance (VARCHAR)
            'accountType' => 'client',
            'accountBalance' => 0,
            'accountDebit' => 0,
            'accountCredit' => 0,
            'accountOrder' => 0,
            'accountStatus' => 1,
            'deleted' => 0,
            'created_at' => date('Y-m-d H:i:s')
        );

        $this->CI->db->insert('auxiliary_subaccounts', $data);

        if ($this->CI->db->affected_rows() > 0) {
            return $this->CI->db->insert_id();
        }

        return null;
    }

    /**
     * Crea cuentas auxiliares PUC (130505) para todos los clientes activos.
     * Es idempotente: no duplica cuentas existentes.
     *
     * @return array con 'created', 'existing', 'failed', 'total'
     */
    public function bulkCreateClientAuxAccounts() {
        $this->CI->load->model('clients_model');

        $clients = $this->CI->clients_model->getClients();

        $result = array(
            'created' => 0,
            'existing' => 0,
            'failed' => 0,
            'total' => count($clients)
        );

        foreach ($clients as $client) {
            // Verificar si ya tiene cuenta auxiliar
            $existing = $this->CI->db->select('id')
                ->from('auxiliary_subaccounts')
                ->where('accountAccount', $client->idClient)
                ->where('accountType', 'client')
                ->where('deleted', 0)
                ->get();

            if ($existing->num_rows() > 0) {
                $result['existing']++;
                continue;
            }

            // Crear nueva cuenta auxiliar
            $auxId = $this->getOrCreateClientAuxAccount($client->idClient, 0);

            if ($auxId) {
                $result['created']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * Estadísticas de clientes con/sin cuenta auxiliar contable
     *
     * @return array con 'with_account', 'without_account', 'total'
     */
    public function getClientAuxAccountStats() {
        $this->CI->load->model('clients_model');

        $totalClients = $this->CI->clients_model->clientCount(true);

        $withAccount = (int)$this->CI->db->select('COUNT(*) as count')
            ->from('auxiliary_subaccounts')
            ->where('accountType', 'client')
            ->where('deleted', 0)
            ->get()->row()->count;

        return array(
            'with_account' => $withAccount,
            'without_account' => $totalClients - $withAccount,
            'total' => $totalClients
        );
    }

    /**
     * Crea cuentas auxiliares PUC (220505) para todos los proveedores activos.
     * Es idempotente: no duplica cuentas existentes.
     *
     * @return array con 'created', 'existing', 'failed', 'total'
     */
    public function bulkCreateProviderAuxAccounts() {
        $this->CI->load->model('providers_model');

        $providers = $this->CI->providers_model->getProviders();

        $result = array(
            'created' => 0,
            'existing' => 0,
            'failed' => 0,
            'total' => count($providers)
        );

        foreach ($providers as $provider) {
            $existing = $this->CI->db->select('id')
                ->from('auxiliary_subaccounts')
                ->where('accountAccount', $provider->idProvider)
                ->where('accountType', 'provider')
                ->where('deleted', 0)
                ->get();

            if ($existing->num_rows() > 0) {
                $result['existing']++;
                continue;
            }

            $auxId = $this->getOrCreateProviderAuxAccount($provider->idProvider, 0);

            if ($auxId) {
                $result['created']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * Estadísticas de proveedores con/sin cuenta auxiliar contable
     *
     * @return array con 'with_account', 'without_account', 'total'
     */
    public function getProviderAuxAccountStats() {
        $this->CI->load->model('providers_model');

        $totalProviders = count($this->CI->providers_model->getProviders());

        $withAccount = (int)$this->CI->db->select('COUNT(*) as count')
            ->from('auxiliary_subaccounts')
            ->where('accountType', 'provider')
            ->where('deleted', 0)
            ->get()->row()->count;

        return array(
            'with_account' => $withAccount,
            'without_account' => $totalProviders - $withAccount,
            'total' => $totalProviders
        );
    }

    // ========================================================================
    // MÉTODOS PRIVADOS - CREACIÓN DE ASIENTOS
    // ========================================================================

    /**
     * Crea asiento contable de doble entrada
     *
     * IMPORTANTE:
     * - accountSide es VARCHAR: '1' = Débito, '2' = Crédito
     * - Actualiza saldos de subcuentas y auxiliares
     *
     * @param int    $debitAccountId    ID de subcuenta a debitar
     * @param int    $debitAuxAccountId ID de auxiliar a debitar (opcional)
     * @param int    $creditAccountId   ID de subcuenta a acreditar
     * @param int    $creditAuxAccountId ID de auxiliar a acreditar (opcional)
     * @param float  $amount             Monto
     * @param string $description        Descripción
     * @param int    $userId             ID del usuario
     * @param int    $storeId            ID de bodega
     * @param string $transactionType    Tipo de transacción (payment, cash_movement, etc.)
     * @param int    $transactionId      ID de la transacción origen
     * @return bool  TRUE si se creó exitosamente, FALSE si falló
     */
    private function createEntry($debitAccountId, $debitAuxAccountId, $creditAccountId, $creditAuxAccountId, $amount, $description, $userId, $storeId, $transactionType, $transactionId, $entryDate = null, $costCenterId = null) {

        try {
            // Usar fecha actual si no se proporciona
            $date = $entryDate ?: date('Y-m-d');

            // Validar que el período no esté cerrado (excepto para asientos de cierre)
            if ($transactionType !== 'closing' && $this->isPeriodClosed($date, $storeId)) {
                $this->CI->logs_model->logMessage("warning", "Accounting_lib::createEntry - Intento de crear asiento en período cerrado: $date");
                return false;
            }

            // Preparar datos del asiento
            $entryData = array(
                'userID' => $userId,
                'entryDescription' => $description,
                'entryDate' => $date,
                'entryStoreId' => $storeId,
                'entryType' => 1,  // Asiento estándar
                'entryTransactionType' => $transactionType,
                'entryTransactionId' => $transactionId,
                'entryDebitAccount' => $debitAccountId,
                'entryDebitAuxaccount' => $debitAuxAccountId,
                'entryDebitBalance' => $amount,
                'entryCreditAccount' => $creditAccountId,
                'entryCreditAuxaccount' => $creditAuxAccountId,
                'entryCreditBalance' => $amount,
                'cost_center_id' => $costCenterId,
                'entryStatus' => 1,  // Activo
                'created_by' => $userId,
                'entryCreateDate' => date('Y-m-d H:i:s'),
                'deleted' => 0
            );

            // Insertar asiento
            $result = $this->CI->Entry_model->save($entryData);

            if (!$result) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::createEntry - Fallo al insertar asiento");
                return false;
            }

            $entryId = $this->CI->db->insert_id();

            // Actualizar saldos de subcuentas
            $this->updateAccountBalance($debitAccountId, $amount, 'debit');
            $this->updateAccountBalance($creditAccountId, $amount, 'credit');

            // Actualizar saldos de auxiliares si existen
            if ($debitAuxAccountId) {
                $this->updateAuxAccountBalance($debitAuxAccountId, $amount, 'debit');
            }
            if ($creditAuxAccountId) {
                $this->updateAuxAccountBalance($creditAuxAccountId, $amount, 'credit');
            }

            return $entryId ? $entryId : true;

        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::createEntry - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza saldo de una subcuenta
     *
     * Considera accountSide para determinar si suma o resta:
     * - accountSide='1' (Débito): Débitos suman, Créditos restan
     * - accountSide='2' (Crédito): Créditos suman, Débitos restan
     *
     * @param int    $accountId ID de subcuenta
     * @param float  $amount    Monto
     * @param string $type      'debit' o 'credit'
     * @return bool  TRUE si se actualizó, FALSE si falló
     */
    private function updateAccountBalance($accountId, $amount, $type) {

        // Obtener cuenta
        $query = $this->CI->db->select('accountSide, accountBalance, accountDebit, accountCredit')
            ->from('subaccounts')
            ->where('id', $accountId)
            ->get();

        if ($query->num_rows() == 0) {
            return false;
        }

        $account = $query->row();
        $accountSide = $account->accountSide;  // VARCHAR '1' o '2'
        $currentBalance = $account->accountBalance;
        $currentDebit = $account->accountDebit;
        $currentCredit = $account->accountCredit;

        // Calcular nuevo saldo según naturaleza de la cuenta
        if ($accountSide == '1') {
            // Cuenta de naturaleza DÉBITO (Activos, Gastos, Costos)
            if ($type == 'debit') {
                $newBalance = $currentBalance + $amount;
                $newDebit = $currentDebit + $amount;
            } else {
                $newBalance = $currentBalance - $amount;
                $newCredit = $currentCredit + $amount;
            }
        } else {
            // Cuenta de naturaleza CRÉDITO (Pasivos, Patrimonio, Ingresos)
            if ($type == 'credit') {
                $newBalance = $currentBalance + $amount;
                $newCredit = $currentCredit + $amount;
            } else {
                $newBalance = $currentBalance - $amount;
                $newDebit = $currentDebit + $amount;
            }
        }

        // Actualizar cuenta
        $updateData = array(
            'accountBalance' => $newBalance,
            'updated_at' => date('Y-m-d H:i:s')
        );

        if ($type == 'debit') {
            $updateData['accountDebit'] = isset($newDebit) ? $newDebit : $currentDebit;
        } else {
            $updateData['accountCredit'] = isset($newCredit) ? $newCredit : $currentCredit;
        }

        $this->CI->db->where('id', $accountId);
        $this->CI->db->update('subaccounts', $updateData);

        return true;
    }

    /**
     * Actualiza saldo de una cuenta auxiliar
     *
     * Mismo comportamiento que updateAccountBalance pero para auxiliares
     *
     * @param int    $auxAccountId ID de cuenta auxiliar
     * @param float  $amount       Monto
     * @param string $type         'debit' o 'credit'
     * @return bool  TRUE si se actualizó, FALSE si falló
     */
    private function updateAuxAccountBalance($auxAccountId, $amount, $type) {

        // Obtener cuenta auxiliar
        $query = $this->CI->db->select('accountSide, accountBalance, accountDebit, accountCredit')
            ->from('auxiliary_subaccounts')
            ->where('id', $auxAccountId)
            ->get();

        if ($query->num_rows() == 0) {
            return false;
        }

        $account = $query->row();
        $accountSide = $account->accountSide;  // VARCHAR '1' o '2'
        $currentBalance = $account->accountBalance;
        $currentDebit = $account->accountDebit;
        $currentCredit = $account->accountCredit;

        // Calcular nuevo saldo según naturaleza de la cuenta
        if ($accountSide == '1') {
            // Cuenta de naturaleza DÉBITO
            if ($type == 'debit') {
                $newBalance = $currentBalance + $amount;
                $newDebit = $currentDebit + $amount;
            } else {
                $newBalance = $currentBalance - $amount;
                $newCredit = $currentCredit + $amount;
            }
        } else {
            // Cuenta de naturaleza CRÉDITO
            if ($type == 'credit') {
                $newBalance = $currentBalance + $amount;
                $newCredit = $currentCredit + $amount;
            } else {
                $newBalance = $currentBalance - $amount;
                $newDebit = $currentDebit + $amount;
            }
        }

        // Actualizar cuenta auxiliar
        $updateData = array(
            'accountBalance' => $newBalance,
            'updated_at' => date('Y-m-d H:i:s')
        );

        if ($type == 'debit') {
            $updateData['accountDebit'] = isset($newDebit) ? $newDebit : $currentDebit;
        } else {
            $updateData['accountCredit'] = isset($newCredit) ? $newCredit : $currentCredit;
        }

        $this->CI->db->where('id', $auxAccountId);
        $this->CI->db->update('auxiliary_subaccounts', $updateData);

        return true;
    }

    // ========================================================================
    // MÉTODOS - FASE 3: INTEGRACIÓN COMERCIAL
    // ========================================================================

    /**
     * Registra asiento contable por factura de venta
     *
     * Asiento generado:
     * Débito: Clientes (130505) + Auxiliar de cliente
     * Crédito: Ventas (413505)
     *
     * @param int   $invoiceId   ID de la factura
     * @param int   $clientId    ID del cliente
     * @param int   $storeId     ID de bodega
     * @param float $total       Total de la factura
     * @param int   $userId      ID del usuario que registra
     * @return bool TRUE si se creó el asiento, FALSE si falló
     */
    public function recordInvoice($invoiceId, $clientId, $storeId, $total, $userId, $costCenterId = null, $entryDate = null) {

        if (!$invoiceId || !$clientId || !$total || !$storeId || !$userId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordInvoice - Parámetros faltantes");
            return false;
        }

        $this->CI->db->trans_start();

        try {
            // 1. Obtener cuenta de clientes (cuentas por cobrar)
            $debitAccountId = $this->getReceivableAccount($storeId);
            if (!$debitAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordInvoice - No se encontró cuenta de clientes para bodega $storeId");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 2. Obtener o crear cuenta auxiliar del cliente
            $debitAuxAccountId = $this->getOrCreateClientAuxAccount($clientId, $storeId);
            if (!$debitAuxAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordInvoice - No se pudo crear auxiliar para cliente $clientId");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 3. Obtener cuenta de ventas
            $creditAccountId = $this->getRevenueAccount($storeId);
            if (!$creditAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordInvoice - No se encontró cuenta de ventas para bodega $storeId");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 4. Crear asiento contable
            $description = "Factura de Venta #" . str_pad($invoiceId, 6, "0", STR_PAD_LEFT);

            $result = $this->createEntry(
                $debitAccountId,        // Débito: Clientes
                $debitAuxAccountId,     // Auxiliar: Cliente específico
                $creditAccountId,       // Crédito: Ventas
                null,                   // Sin auxiliar crédito
                $total,
                $description,
                $userId,
                $storeId,
                'invoice',
                $invoiceId,
                $entryDate,             // null = hoy. En back-fill se pasa invoice.date.
                $costCenterId
            );

            if (!$result) {
                $this->CI->db->trans_rollback();
                return false;
            }

            $this->CI->db->trans_complete();
            return $this->CI->db->trans_status();

        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordInvoice - Error: " . $e->getMessage());
            $this->CI->db->trans_rollback();
            return false;
        }
    }

    /**
     * Registra el ASIENTO DE COSTO DE VENTAS al emitir una factura.
     *
     * Asiento generado:
     *   Débito:  Costo de mercancía vendida (613501)
     *   Crédito: Inventario mercancías     (143501)
     *
     * El costo se calcula desde invoice_details × products.cost_cop. Si el
     * caller no lo computa, este método lo hace por sí mismo desde la BD.
     *
     * Fase 3.3 de Contabilidad — sin esto la Utilidad Bruta sería = Ingresos
     * (margen 100% irreal). Con esto: U.Bruta = Ingresos − Costo de Ventas.
     *
     * @param int   $invoiceId  ID factura
     * @param int   $storeId    bodega
     * @param int   $userId     uname del usuario
     * @param float $totalCost  Si se pasa, se usa. Si null, se calcula.
     * @param string|null $entryDate  null = hoy; back-fill pasa fecha histórica
     */
    public function recordCostOfSales($invoiceId, $storeId, $userId, $totalCost = null, $entryDate = null)
    {
        if (!$invoiceId || !$storeId || !$userId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordCostOfSales - Parámetros faltantes");
            return false;
        }

        // Si no se pasa costo, computarlo desde invoice_details
        if ($totalCost === null) {
            $row = $this->CI->db->query("
                SELECT COALESCE(SUM(id.quantity * COALESCE(NULLIF(p.cost_cop, 0), p.cost, 0)), 0) AS total_cost
                FROM invoice_details id
                JOIN products p ON p.idProduct = id.productId
                WHERE id.invoiceId = ?
            ", [(int)$invoiceId])->row();
            $totalCost = $row ? (float)$row->total_cost : 0;
        }

        if ($totalCost <= 0) {
            // No hay costo conocido para esta factura — skip silenciosamente.
            // Razón típica: producto sin cost_cop en BD. NO es error.
            return false;
        }

        $this->CI->db->trans_start();
        try {
            $debitAccountId  = $this->getAccountByPucCode('613501', $storeId);
            $creditAccountId = $this->getAccountByPucCode('143501', $storeId);
            if (!$debitAccountId || !$creditAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordCostOfSales - PUC 613501 o 143501 no configurados (bodega $storeId)");
                $this->CI->db->trans_rollback();
                return false;
            }

            $description = "Costo de Ventas — Factura #" . str_pad($invoiceId, 6, "0", STR_PAD_LEFT);
            $result = $this->createEntry(
                $debitAccountId,  null,
                $creditAccountId, null,
                $totalCost,
                $description,
                $userId,
                $storeId,
                'cost_of_sales',
                $invoiceId,
                $entryDate,
                null
            );
            if (!$result) { $this->CI->db->trans_rollback(); return false; }
            $this->CI->db->trans_complete();
            return $this->CI->db->trans_status();
        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordCostOfSales - Error: " . $e->getMessage());
            $this->CI->db->trans_rollback();
            return false;
        }
    }

    /**
     * Registra asiento contable por devolución/nota crédito
     *
     * Asiento generado:
     * Débito: Devoluciones en Ventas (417505)
     * Crédito: Clientes (130505) + Auxiliar de cliente
     *
     * @param int   $refundId    ID de la devolución
     * @param int   $invoiceId   ID de la factura original
     * @param int   $clientId    ID del cliente
     * @param float $amount      Monto de la devolución
     * @param int   $storeId     ID de bodega
     * @param int   $userId      ID del usuario que registra
     * @return bool TRUE si se creó el asiento, FALSE si falló
     */
    public function recordRefund($refundId, $invoiceId, $clientId, $amount, $storeId, $userId, $costCenterId = null, $entryDate = null) {

        if (!$refundId || !$invoiceId || !$clientId || !$amount || !$storeId || !$userId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordRefund - Parámetros faltantes");
            return false;
        }

        $this->CI->db->trans_start();

        try {
            // 1. Obtener cuenta de devoluciones en ventas
            $debitAccountId = $this->getRefundAccount($storeId);
            if (!$debitAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordRefund - No se encontró cuenta de devoluciones para bodega $storeId");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 2. Obtener cuenta de clientes (cuentas por cobrar)
            $creditAccountId = $this->getReceivableAccount($storeId);
            if (!$creditAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordRefund - No se encontró cuenta de clientes para bodega $storeId");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 3. Obtener cuenta auxiliar del cliente
            $creditAuxAccountId = $this->getOrCreateClientAuxAccount($clientId, $storeId);
            if (!$creditAuxAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordRefund - No se pudo crear auxiliar para cliente $clientId");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 4. Crear asiento contable
            $description = "Devolución Factura #" . str_pad($invoiceId, 6, "0", STR_PAD_LEFT);

            $result = $this->createEntry(
                $debitAccountId,        // Débito: Devoluciones en Ventas
                null,                   // Sin auxiliar débito
                $creditAccountId,       // Crédito: Clientes
                $creditAuxAccountId,    // Auxiliar: Cliente específico
                $amount,
                $description,
                $userId,
                $storeId,
                'refund',
                $refundId,
                $entryDate,             // null = hoy; back-fill pasa fecha histórica
                $costCenterId
            );

            if (!$result) {
                $this->CI->db->trans_rollback();
                return false;
            }

            $this->CI->db->trans_complete();
            return $this->CI->db->trans_status();

        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordRefund - Error: " . $e->getMessage());
            $this->CI->db->trans_rollback();
            return false;
        }
    }

    /**
     * Registra asiento contable de reversión de devolución
     *
     * Usado cuando se deshace una devolución
     * Asiento generado (opuesto a recordRefund):
     * Débito: Clientes (130505) + Auxiliar de cliente
     * Crédito: Devoluciones en Ventas (417505)
     *
     * @param int   $refundId    ID de la devolución original
     * @param int   $invoiceId   ID de la factura original
     * @param int   $clientId    ID del cliente
     * @param float $amount      Monto de la devolución
     * @param int   $storeId     ID de bodega
     * @param int   $userId      ID del usuario que registra
     * @return bool TRUE si se creó el asiento, FALSE si falló
     */
    public function recordRefundReversal($refundId, $invoiceId, $clientId, $amount, $storeId, $userId, $costCenterId = null) {

        if (!$refundId || !$invoiceId || !$clientId || !$amount || !$storeId || !$userId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordRefundReversal - Parámetros faltantes");
            return false;
        }

        $this->CI->db->trans_start();

        try {
            // 1. Obtener cuenta de clientes (cuentas por cobrar)
            $debitAccountId = $this->getReceivableAccount($storeId);
            if (!$debitAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordRefundReversal - No se encontró cuenta de clientes para bodega $storeId");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 2. Obtener cuenta auxiliar del cliente
            $debitAuxAccountId = $this->getOrCreateClientAuxAccount($clientId, $storeId);
            if (!$debitAuxAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordRefundReversal - No se pudo obtener auxiliar para cliente $clientId");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 3. Obtener cuenta de devoluciones en ventas
            $creditAccountId = $this->getRefundAccount($storeId);
            if (!$creditAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordRefundReversal - No se encontró cuenta de devoluciones para bodega $storeId");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 4. Crear asiento contable de reversión
            $description = "Reversión Devolución #" . str_pad($refundId, 6, "0", STR_PAD_LEFT) . " - Factura #" . str_pad($invoiceId, 6, "0", STR_PAD_LEFT);

            $result = $this->createEntry(
                $debitAccountId,        // Débito: Clientes (restaura la cuenta por cobrar)
                $debitAuxAccountId,     // Auxiliar: Cliente específico
                $creditAccountId,       // Crédito: Devoluciones en Ventas (reduce la devolución)
                null,                   // Sin auxiliar crédito
                $amount,
                $description,
                $userId,
                $storeId,
                'refund_reversal',
                $refundId,
                null,
                $costCenterId
            );

            if (!$result) {
                $this->CI->db->trans_rollback();
                return false;
            }

            $this->CI->db->trans_complete();
            return $this->CI->db->trans_status();

        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordRefundReversal - Error: " . $e->getMessage());
            $this->CI->db->trans_rollback();
            return false;
        }
    }

    /**
     * Obtiene cuenta de ventas. PUC Colombia tiene varios códigos válidos
     * para "Comercio al por mayor y al por menor":
     *   - 413506: Comercio mercancías (estándar genérico)
     *   - 413505: Ventas, partes, piezas y accesorios (Ledxury usa este)
     *   - 413535: Otros
     *
     * Probamos en orden hasta encontrar uno que exista en la BD. Sin esto,
     * recordInvoice fallaba en Ledxury porque la subcuenta seedeada era
     * 413505 mientras el método buscaba 413506.
     */
    public function getRevenueAccount($storeId) {
        $configured = $this->getConfiguredAccount('account_revenue', '413506');
        if ($configured) return $configured;
        $fallback = $this->getAccountByPucCode('413505');
        if ($fallback) return $fallback;
        return $this->getAccountByPucCode('413535');
    }

    /**
     * Obtiene cuenta de devoluciones en ventas
     * Busca subcuenta con pucCode 417505 (Devoluciones en Ventas)
     *
     * @param int $storeId ID de bodega
     * @return int|null ID de subcuenta o NULL si no existe
     */
    public function getRefundAccount($storeId) {
        return $this->getConfiguredAccount('account_refund', '417505');
    }

    // ========================================================================
    // MÉTODOS - CUENTAS POR PAGAR (PROVEEDORES)
    // ========================================================================

    /**
     * Obtiene cuenta de proveedores (cuentas por pagar)
     * Busca subcuenta con pucCode 220505 (Proveedores Nacionales)
     *
     * @param int $storeId ID de bodega
     * @return int|null ID de subcuenta o NULL si no existe
     */
    public function getPayableAccount($storeId) {
        return $this->getConfiguredAccount('account_payable', '220501');
    }

    /**
     * Obtiene o crea cuenta auxiliar para un proveedor
     *
     * @param int $providerId ID del proveedor
     * @param int $storeId    ID de bodega
     * @return int|null ID de cuenta auxiliar o NULL si falla
     */
    public function getOrCreateProviderAuxAccount($providerId, $storeId) {

        $this->CI->load->model('providers_model');

        // Buscar auxiliar existente
        $query = $this->CI->db->select('id')
            ->from('auxiliary_subaccounts')
            ->where('accountAccount', $providerId)
            ->where('accountType', 'provider')
            ->where('deleted', 0)
            ->get();

        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }

        // Si no existe, crear nuevo
        $provider = $this->CI->providers_model->getProvider($providerId);

        if (!$provider) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::getOrCreateProviderAuxAccount - Proveedor $providerId no existe");
            return null;
        }

        // Usar la cuenta PUC específica del proveedor si está configurada
        $providerPuc = !empty($provider->puc_code) ? $provider->puc_code : null;
        $payablePuc = $providerPuc ?: $this->getConfiguredPucCode('account_payable', '220501');

        $data = array(
            'accountID' => (int)$payablePuc,
            'accountName' => $provider->name,
            'accountAccount' => $providerId,
            'accountSide' => '2',  // Crédito (pasivo)
            'accountStatement' => '1',  // Balance
            'accountType' => 'provider',
            'accountBalance' => 0,
            'accountDebit' => 0,
            'accountCredit' => 0,
            'accountOrder' => 0,
            'accountStatus' => 1,
            'deleted' => 0,
            'created_at' => date('Y-m-d H:i:s')
        );

        $this->CI->db->insert('auxiliary_subaccounts', $data);

        if ($this->CI->db->affected_rows() > 0) {
            return $this->CI->db->insert_id();
        }

        return null;
    }

    /**
     * Registra asiento contable por compra de mercancía (factura de proveedor)
     *
     * Asiento generado:
     * Débito: Mercancía en tránsito (143505)
     * Crédito: Proveedores (220501) + Auxiliar de proveedor
     *
     * @param int    $billId      ID de la factura de proveedor
     * @param int    $providerId  ID del proveedor
     * @param int    $storeId     ID de bodega
     * @param float  $total       Total de la factura
     * @param int    $userId      ID del usuario que registra
     * @return bool TRUE si se creó el asiento, FALSE si falló
     */
    public function recordSupplierBill($billId, $providerId, $storeId, $total, $userId, $costCenterId = null) {

        if (!$billId || !$providerId || !$total || !$storeId || !$userId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierBill - Parámetros faltantes");
            return false;
        }

        $this->CI->db->trans_start();

        try {
            // 1. Obtener cuenta de mercancía en tránsito (143505)
            $transitCode = $this->getConfiguredPucCode('account_inventory_transit', '143505');
            $debitAccountId = $this->getAccountByPucCode($transitCode);
            if (!$debitAccountId) {
                $debitAccountId = $this->getConfiguredAccount('account_inventory_transit', '143505');
                if (!$debitAccountId) {
                    $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierBill - No se encontró cuenta de mercancía en tránsito (143505)");
                    $this->CI->db->trans_rollback();
                    return false;
                }
            }

            // 2. Obtener cuenta de proveedores (cuentas por pagar)
            $creditAccountId = $this->getPayableAccount($storeId);
            if (!$creditAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierBill - No se encontró cuenta de proveedores");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 3. Obtener o crear cuenta auxiliar del proveedor
            $creditAuxAccountId = $this->getOrCreateProviderAuxAccount($providerId, $storeId);
            if (!$creditAuxAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierBill - No se pudo crear auxiliar para proveedor $providerId");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 4. Crear asiento contable
            $description = "Factura Proveedor #" . str_pad($billId, 6, "0", STR_PAD_LEFT);

            $result = $this->createEntry(
                $debitAccountId,        // Débito: Mercancía en tránsito (143505)
                null,                   // Sin auxiliar débito
                $creditAccountId,       // Crédito: Proveedores (220501)
                $creditAuxAccountId,    // Auxiliar: Proveedor específico
                $total,
                $description,
                $userId,
                $storeId,
                'supplier_bill',
                $billId,
                null,
                $costCenterId
            );

            if (!$result) {
                $this->CI->db->trans_rollback();
                return false;
            }

            $this->CI->db->trans_complete();
            return $this->CI->db->trans_status();

        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierBill - Error: " . $e->getMessage());
            $this->CI->db->trans_rollback();
            return false;
        }
    }

    /**
     * Registra asiento contable por recepción de mercancía
     *
     * Asiento generado:
     * Débito: Inventario (143501)
     * Crédito: Mercancía en tránsito (143505)
     *
     * @param int    $billId      ID de la factura de proveedor
     * @param float  $total       Total de la mercancía recibida
     * @param int    $storeId     ID de bodega
     * @param string $userId      Usuario que recibe
     * @return bool TRUE si se creó el asiento, FALSE si falló
     */
    public function recordSupplierReceive($billId, $total, $storeId, $userId) {

        if (!$billId || !$total || !$storeId || !$userId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierReceive - Parámetros faltantes");
            return false;
        }

        $this->CI->db->trans_start();

        try {
            // 1. Obtener cuenta de inventario (143501)
            $inventoryCode = $this->getConfiguredPucCode('account_inventory', '143501');
            $debitAccountId = $this->getAccountByPucCode($inventoryCode);
            if (!$debitAccountId) {
                $debitAccountId = $this->getConfiguredAccount('account_inventory', '143501');
                if (!$debitAccountId) {
                    $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierReceive - No se encontró cuenta de inventario (143501)");
                    $this->CI->db->trans_rollback();
                    return false;
                }
            }

            // 2. Obtener cuenta de mercancía en tránsito (143505)
            $transitCode = $this->getConfiguredPucCode('account_inventory_transit', '143505');
            $creditAccountId = $this->getAccountByPucCode($transitCode);
            if (!$creditAccountId) {
                $creditAccountId = $this->getConfiguredAccount('account_inventory_transit', '143505');
                if (!$creditAccountId) {
                    $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierReceive - No se encontró cuenta de mercancía en tránsito (143505)");
                    $this->CI->db->trans_rollback();
                    return false;
                }
            }

            // 3. Crear asiento contable
            $description = "Recepción Mercancía - Fact. Proveedor #" . str_pad($billId, 6, "0", STR_PAD_LEFT);

            $result = $this->createEntry(
                $debitAccountId,        // Débito: Inventario (143501)
                null,                   // Sin auxiliar débito
                $creditAccountId,       // Crédito: Mercancía en tránsito (143505)
                null,                   // Sin auxiliar crédito
                $total,
                $description,
                $userId,
                $storeId,
                'supplier_receive',
                $billId,
                null,
                null
            );

            if (!$result) {
                $this->CI->db->trans_rollback();
                return false;
            }

            $this->CI->db->trans_complete();
            return $this->CI->db->trans_status();

        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierReceive - Error: " . $e->getMessage());
            $this->CI->db->trans_rollback();
            return false;
        }
    }

    /**
     * Registra asiento contable por pago a proveedor
     *
     * Asiento generado:
     * Débito: Proveedores (220505) + Auxiliar de proveedor
     * Crédito: Caja/Banco
     *
     * @param int    $paymentId   ID del pago
     * @param int    $providerId  ID del proveedor
     * @param float  $amount      Monto del pago
     * @param int    $storeId     ID de bodega
     * @param int    $userId      ID del usuario que registra
     * @param int    $cashAccountId ID de la cuenta de caja/banco
     * @return bool TRUE si se creó el asiento, FALSE si falló
     */
    public function recordSupplierPayment($paymentId, $providerId, $amount, $storeId, $userId, $cashAccountId, $costCenterId = null) {

        if (!$paymentId || !$providerId || !$amount || !$storeId || !$userId || !$cashAccountId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierPayment - Parámetros faltantes");
            return false;
        }

        $this->CI->db->trans_start();

        try {
            // 1. Obtener cuenta de proveedores (cuentas por pagar)
            $debitAccountId = $this->getPayableAccount($storeId);
            if (!$debitAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierPayment - No se encontró cuenta de proveedores");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 2. Obtener cuenta auxiliar del proveedor
            $debitAuxAccountId = $this->getOrCreateProviderAuxAccount($providerId, $storeId);
            if (!$debitAuxAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierPayment - No se pudo crear auxiliar para proveedor $providerId");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 3. Crear asiento contable
            $description = "Pago a Proveedor - Pago #" . str_pad($paymentId, 6, "0", STR_PAD_LEFT);

            $result = $this->createEntry(
                $debitAccountId,        // Débito: Proveedores (reduce pasivo)
                $debitAuxAccountId,     // Auxiliar: Proveedor específico
                $cashAccountId,         // Crédito: Caja/Banco
                null,                   // Sin auxiliar crédito
                $amount,
                $description,
                $userId,
                $storeId,
                'supplier_payment',
                $paymentId,
                null,
                $costCenterId
            );

            if (!$result) {
                $this->CI->db->trans_rollback();
                return false;
            }

            $this->CI->db->trans_complete();
            return $this->CI->db->trans_status();

        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierPayment - Error: " . $e->getMessage());
            $this->CI->db->trans_rollback();
            return false;
        }
    }

    /**
     * Obtiene cuenta por código PUC
     *
     * @param string $pucCode Código PUC
     * @return int|null ID de subcuenta o NULL si no existe
     */
    public function getAccountByPucCode($pucCode) {
        $query = $this->CI->db->select('id')
            ->from('subaccounts')
            ->where('pucCode', $pucCode)
            ->where('deleted', 0)
            ->get();

        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }

        return null;
    }

    // ========================================================================
    // MÉTODOS - GASTOS OPERATIVOS
    // ========================================================================

    /**
     * Registra asiento contable por gasto operativo
     *
     * Asiento generado:
     * Débito: Subcuenta de gasto (según categoría, ej: 519505, 512010, etc.)
     * Crédito: Caja/Banco (110505 o 111005)
     *
     * @param int    $expenseId          ID del gasto (expense_records.id)
     * @param float  $amount             Monto del gasto
     * @param int    $debitSubaccountId  ID de subcuenta a debitar (de la categoría)
     * @param int    $storeId            ID de bodega
     * @param string $userId             ID del usuario que registra
     * @param string $description        Descripción del gasto
     * @param int    $cashAccountId      ID de subcuenta de caja/banco a acreditar
     * @param string $entryDate          Fecha del asiento (Y-m-d)
     * @return bool TRUE si se creó el asiento, FALSE si falló
     */
    public function recordExpense($expenseId, $amount, $debitSubaccountId, $storeId, $userId, $description, $cashAccountId, $entryDate = null, $costCenterId = null) {

        if (!$expenseId || !$amount || !$debitSubaccountId || !$storeId || !$userId || !$cashAccountId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordExpense - Parámetros faltantes");
            return false;
        }

        try {
            $result = $this->createEntry(
                $debitSubaccountId,     // Débito: Cuenta de gasto (según categoría)
                null,                    // Sin auxiliar débito
                $cashAccountId,          // Crédito: Caja o Banco
                null,                    // Sin auxiliar crédito
                $amount,
                $description,
                $userId,
                $storeId,
                'expense',
                $expenseId,
                $entryDate,
                $costCenterId
            );

            return $result; // Retorna entryId o false

        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordExpense - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Causación de un gasto contra cuenta por pagar de proveedor.
     *
     * Asiento:
     *   Débito:  Subcuenta de gasto (según categoría, ej. 513505, 519505)
     *   Crédito: 220505 Proveedores nacionales + auxiliar del proveedor
     *
     * Se usa cuando se registra un gasto pendiente de pago: se reconoce el
     * gasto y la deuda con el proveedor en el período de causación.
     *
     * @return int|false entryId o false si falla
     */
    public function recordExpenseAccrual($expenseId, $amount, $debitSubaccountId, $providerId, $storeId, $userId, $description, $entryDate = null, $costCenterId = null) {
        if (!$expenseId || !$amount || !$debitSubaccountId || !$providerId || !$storeId || !$userId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordExpenseAccrual - Parámetros faltantes");
            return false;
        }
        $payableAccountId = $this->getPayableAccount($storeId);
        if (!$payableAccountId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordExpenseAccrual - No hay cuenta de proveedores configurada");
            return false;
        }
        $providerAuxId = $this->getOrCreateProviderAuxAccount($providerId, $storeId);
        if (!$providerAuxId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordExpenseAccrual - No se pudo obtener auxiliar del proveedor $providerId");
            return false;
        }
        try {
            return $this->createEntry(
                $debitSubaccountId,    // DR: gasto
                null,
                $payableAccountId,     // CR: 220505 Proveedores
                $providerAuxId,        // aux: proveedor específico
                $amount,
                $description,
                $userId,
                $storeId,
                'expense_accrual',
                $expenseId,
                $entryDate,
                $costCenterId
            );
        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordExpenseAccrual - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Pago de un gasto previamente causado contra el proveedor.
     *
     * Asiento:
     *   Débito:  220505 Proveedores nacionales + auxiliar del proveedor
     *   Crédito: Caja o Banco (110505 / 111005)
     *
     * @return int|false entryId o false si falla
     */
    public function recordExpensePaymentToProvider($expenseId, $amount, $providerId, $cashAccountId, $storeId, $userId, $description, $entryDate = null, $costCenterId = null) {
        if (!$expenseId || !$amount || !$providerId || !$cashAccountId || !$storeId || !$userId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordExpensePaymentToProvider - Parámetros faltantes");
            return false;
        }
        $payableAccountId = $this->getPayableAccount($storeId);
        if (!$payableAccountId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordExpensePaymentToProvider - No hay cuenta de proveedores configurada");
            return false;
        }
        $providerAuxId = $this->getOrCreateProviderAuxAccount($providerId, $storeId);
        if (!$providerAuxId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordExpensePaymentToProvider - No se pudo obtener auxiliar del proveedor $providerId");
            return false;
        }
        try {
            return $this->createEntry(
                $payableAccountId,     // DR: 220505 Proveedores
                $providerAuxId,        // aux: proveedor
                $cashAccountId,        // CR: Caja o Banco
                null,
                $amount,
                $description,
                $userId,
                $storeId,
                'expense_payment',
                $expenseId,
                $entryDate,
                $costCenterId
            );
        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordExpensePaymentToProvider - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desembolso de un anticipo a un vendedor.
     *
     * Asiento:
     *   Débito:  136525 Anticipos a vendedores + auxiliar del empleado
     *   Crédito: Caja (110505) o Banco (111005)
     *
     * Se llama cuando el anticipo pasa a 'desembolsado' (sale el dinero).
     * El balance pendiente queda como cuenta por cobrar al empleado y se
     * cruza FIFO contra futuras liquidaciones.
     *
     * @return int|false entryId o false si falla
     */
    public function recordEmployeeAdvance($advanceId, $amount, $employeeId, $cashAccountId, $storeId, $userId, $description, $entryDate = null, $costCenterId = null) {
        if (!$advanceId || !$amount || !$employeeId || !$cashAccountId || !$storeId || !$userId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordEmployeeAdvance - Parámetros faltantes");
            return false;
        }
        $advanceAccountId = $this->getConfiguredAccount('account_employee_advance', '136525');
        if (!$advanceAccountId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordEmployeeAdvance - No hay cuenta de anticipos configurada");
            return false;
        }
        $employeeAuxId = $this->getOrCreateUserAuxAccount($employeeId);
        if (!$employeeAuxId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordEmployeeAdvance - No se pudo obtener auxiliar del empleado $employeeId");
            return false;
        }
        try {
            return $this->createEntry(
                $advanceAccountId,    // DR: 136525 Anticipos
                $employeeAuxId,        // aux: empleado específico
                $cashAccountId,        // CR: Caja o Banco
                null,
                $amount,
                $description,
                $userId,
                $storeId,
                'employee_advance',
                $advanceId,
                $entryDate,
                $costCenterId
            );
        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordEmployeeAdvance - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reversa de un anticipo cuando se anula antes de cruzar.
     * Asiento opuesto: DR Caja|Banco / CR 136525 [aux=empleado]
     */
    public function reverseEmployeeAdvance($advanceId, $amount, $employeeId, $cashAccountId, $storeId, $userId, $description, $entryDate = null, $costCenterId = null) {
        if (!$advanceId || !$amount || !$employeeId || !$cashAccountId || !$storeId || !$userId) return false;
        $advanceAccountId = $this->getConfiguredAccount('account_employee_advance', '136525');
        if (!$advanceAccountId) return false;
        $employeeAuxId = $this->getOrCreateUserAuxAccount($employeeId);
        if (!$employeeAuxId) return false;
        try {
            return $this->createEntry(
                $cashAccountId,        // DR: Caja|Banco (devuelve plata)
                null,
                $advanceAccountId,     // CR: 136525 (cancela el anticipo)
                $employeeAuxId,
                $amount,
                $description,
                $userId,
                $storeId,
                'employee_advance_reversal',
                $advanceId,
                $entryDate,
                $costCenterId
            );
        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::reverseEmployeeAdvance - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cruce de un anticipo contra una liquidación al pagar comisiones.
     *
     * Asiento:
     *   Débito:  236505 CxP Vendedores + aux del vendedor (cancela deuda)
     *   Crédito: 136525 Anticipos + aux del vendedor (cancela anticipo)
     *
     * Esto se postea por cada anticipo cruzado en la liquidación. El
     * remanente en efectivo se postea aparte vía recordSettlement / pago.
     *
     * @return int|false entryId
     */
    public function recordAdvanceCross($settlementId, $advanceId, $amount, $vendorId, $storeId, $userId, $description, $entryDate = null, $costCenterId = null) {
        if (!$settlementId || !$advanceId || !$amount || !$vendorId || !$storeId || !$userId) return false;
        $vendorPayableId = $this->getVendorPayableAccount($storeId);
        if (!$vendorPayableId) return false;
        $advanceAccountId = $this->getConfiguredAccount('account_employee_advance', '136525');
        if (!$advanceAccountId) return false;
        $vendorAuxId = $this->getOrCreateVendorAuxAccount($vendorId, $storeId);
        $employeeAuxId = $this->getOrCreateUserAuxAccount($vendorId);
        if (!$vendorAuxId || !$employeeAuxId) return false;
        try {
            return $this->createEntry(
                $vendorPayableId,    // DR: 236505 [aux=vendor] (cancela deuda)
                $vendorAuxId,
                $advanceAccountId,   // CR: 136525 [aux=empleado] (cancela anticipo)
                $employeeAuxId,
                $amount,
                $description,
                $userId,
                $storeId,
                'settlement_advance_cross',
                $settlementId,
                $entryDate,
                $costCenterId
            );
        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordAdvanceCross - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reversa la causación de un gasto pendiente cuando se anula.
     *
     * Asiento (opuesto al de recordExpenseAccrual):
     *   Débito:  220505 Proveedores + auxiliar del proveedor
     *   Crédito: Subcuenta de gasto original
     *
     * @return int|false entryId o false si falla
     */
    public function reverseExpenseAccrual($expenseId, $amount, $expenseSubaccountId, $providerId, $storeId, $userId, $description, $entryDate = null, $costCenterId = null) {
        if (!$expenseId || !$amount || !$expenseSubaccountId || !$providerId || !$storeId || !$userId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::reverseExpenseAccrual - Parámetros faltantes");
            return false;
        }
        $payableAccountId = $this->getPayableAccount($storeId);
        if (!$payableAccountId) return false;
        $providerAuxId = $this->getOrCreateProviderAuxAccount($providerId, $storeId);
        if (!$providerAuxId) return false;
        try {
            return $this->createEntry(
                $payableAccountId,     // DR: 220505 (reversa)
                $providerAuxId,
                $expenseSubaccountId,  // CR: gasto (reversa)
                null,
                $amount,
                $description,
                $userId,
                $storeId,
                'expense_reversal',
                $expenseId,
                $entryDate,
                $costCenterId
            );
        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::reverseExpenseAccrual - Error: " . $e->getMessage());
            return false;
        }
    }

    // ========================================================================
    // MÉTODOS PARA LIQUIDACIÓN DE VENDEDORES
    // ========================================================================

    /**
     * Registra asiento contable por liquidación de vendedor
     *
     * Genera asiento cuando se aprueba una liquidación:
     * - Débito: Gastos de Comisiones (PUC 519505)
     * - Crédito: Cuentas por Pagar Vendedores (PUC 236505) + Auxiliar vendedor
     *
     * @param int    $expenseId   ID del gasto/expense creado
     * @param int    $vendorId    ID del vendedor
     * @param float  $amount      Monto de la liquidación (positivo = a favor del vendedor)
     * @param int    $storeId     ID de bodega
     * @param int    $userId      ID del usuario que aprueba
     * @param string $description Descripción de la liquidación
     * @return bool TRUE si se creó el asiento, FALSE si falló
     */
    public function recordSettlement($expenseId, $vendorId, $amount, $storeId, $userId, $description = null, $costCenterId = null) {

        if (!$expenseId || !$vendorId || $amount == 0 || !$userId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSettlement - Parámetros faltantes");
            return false;
        }

        $this->CI->db->trans_start();

        try {
            // Determinar si es comisión a favor o en contra del vendedor
            $isPositive = $amount > 0;
            $absAmount = abs($amount);

            // 1. Obtener cuenta de gastos de comisiones (PUC 519505)
            $commissionAccountId = $this->getCommissionExpenseAccount($storeId);
            if (!$commissionAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSettlement - No se encontró cuenta de comisiones");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 2. Obtener cuenta de cuentas por pagar vendedores (PUC 236505)
            $vendorPayableAccountId = $this->getVendorPayableAccount($storeId);
            if (!$vendorPayableAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSettlement - No se encontró cuenta de cuentas por pagar vendedores");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 3. Obtener o crear cuenta auxiliar del vendedor
            $vendorAuxAccountId = $this->getOrCreateVendorAuxAccount($vendorId, $storeId);
            if (!$vendorAuxAccountId) {
                $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSettlement - No se pudo crear auxiliar para vendedor $vendorId");
                $this->CI->db->trans_rollback();
                return false;
            }

            // 4. Crear asiento contable
            $entryDescription = $description ?: "Liquidación Vendedor - Gasto #" . str_pad($expenseId, 6, "0", STR_PAD_LEFT);

            if ($isPositive) {
                // Vendedor ganó comisiones: aumenta gasto y aumenta pasivo
                // Débito: Gastos de Comisiones (aumenta gasto)
                // Crédito: Cuentas por Pagar Vendedor (aumenta pasivo)
                $result = $this->createEntry(
                    $commissionAccountId,       // Débito: Gastos de Comisiones
                    null,                       // Sin auxiliar débito
                    $vendorPayableAccountId,    // Crédito: Cuentas por Pagar
                    $vendorAuxAccountId,        // Auxiliar: Vendedor específico
                    $absAmount,
                    $entryDescription,
                    $userId,
                    $storeId,
                    'settlement',
                    $expenseId,
                    null,
                    $costCenterId
                );
            } else {
                // Vendedor debe dinero: reducir pasivo previo (si lo hay) o crear cuenta por cobrar
                // Débito: Cuentas por Pagar Vendedor (reduce pasivo)
                // Crédito: Gastos de Comisiones (ajuste/reverso)
                $result = $this->createEntry(
                    $vendorPayableAccountId,    // Débito: Cuentas por Pagar (reduce)
                    $vendorAuxAccountId,        // Auxiliar: Vendedor específico
                    $commissionAccountId,       // Crédito: Gastos de Comisiones
                    null,                       // Sin auxiliar crédito
                    $absAmount,
                    $entryDescription . " (Ajuste negativo)",
                    $userId,
                    $storeId,
                    'settlement',
                    $expenseId,
                    null,
                    $costCenterId
                );
            }

            if (!$result) {
                $this->CI->db->trans_rollback();
                return false;
            }

            $this->CI->db->trans_complete();

            $this->CI->logs_model->logMessage("info", "Accounting_lib::recordSettlement - Asiento creado para liquidación $expenseId, vendedor $vendorId, monto $amount");

            return $this->CI->db->trans_status();

        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSettlement - Error: " . $e->getMessage());
            $this->CI->db->trans_rollback();
            return false;
        }
    }

    /**
     * Obtiene o crea cuenta auxiliar para vendedor
     *
     * @param int $vendorId ID del vendedor (usuario)
     * @param int $storeId  ID de bodega
     * @return int|null ID de cuenta auxiliar o NULL si falla
     */
    public function getOrCreateVendorAuxAccount($vendorId, $storeId) {

        $this->CI->load->model('vendors_model');

        // Buscar auxiliar existente
        $query = $this->CI->db->select('id')
            ->from('auxiliary_subaccounts')
            ->where('accountAccount', $vendorId)
            ->where('accountType', 'vendor')
            ->where('deleted', 0)
            ->get();

        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }

        // Si no existe, crear nuevo
        $vendor = $this->CI->vendors_model->getVendor($vendorId);

        if (!$vendor) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::getOrCreateVendorAuxAccount - Vendedor $vendorId no existe");
            return null;
        }

        $data = array(
            'accountID' => (int)$this->getConfiguredPucCode('account_vendor_payable', '136595'),
            'accountName' => $vendor->name,
            'accountAccount' => $vendorId,
            'accountSide' => '2',  // Crédito (pasivo)
            'accountStatement' => '1',  // Balance
            'accountType' => 'vendor',
            'accountBalance' => 0,
            'accountDebit' => 0,
            'accountCredit' => 0,
            'accountOrder' => 0,
            'accountStatus' => 1,
            'deleted' => 0,
            'created_at' => date('Y-m-d H:i:s')
        );

        $this->CI->db->insert('auxiliary_subaccounts', $data);

        if ($this->CI->db->affected_rows() > 0) {
            return $this->CI->db->insert_id();
        }

        return null;
    }

    /**
     * Obtiene cuenta de gastos de comisiones (PUC 519505)
     *
     * @param int $storeId ID de bodega (opcional)
     * @return int|null ID de subcuenta o NULL si no existe
     */
    public function getCommissionExpenseAccount($storeId = null) {
        return $this->getConfiguredAccount('account_commission', '520518');
    }

    /**
     * Obtiene cuenta de cuentas por cobrar vendedores/empleados (PUC 136595)
     *
     * @param int $storeId ID de bodega (opcional)
     * @return int|null ID de subcuenta o NULL si no existe
     */
    public function getVendorPayableAccount($storeId = null) {
        return $this->getConfiguredAccount('account_vendor_payable', '136595');
    }

    /**
     * Obtiene o crea cuenta auxiliar para un usuario basado en su rol.
     * Roles con PUC 136595 (empleados): accountType='employee', accountSide='1' (Debito/Activo)
     * Roles con PUC 231001 (socios):    accountType='partner',  accountSide='2' (Credito/Pasivo)
     *
     * @param string $userId ID del usuario
     * @return int|null ID de cuenta auxiliar o NULL si falla o rol sin PUC
     */
    public function getOrCreateUserAuxAccount($userId) {

        $this->CI->load->model('users_model');

        $user = $this->CI->users_model->getAnyUser($userId);

        if (!$user) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::getOrCreateUserAuxAccount - Usuario $userId no existe");
            return null;
        }

        $pucCode = $this->CI->users_model->getRolePucCode($user->role);

        if (!$pucCode) {
            return null;
        }

        // Determinar tipo y naturaleza segun PUC
        if ($pucCode == '231001') {
            $accountType = 'partner';
            $accountSide = '2';  // Credito (pasivo)
        } else {
            $accountType = 'employee';
            $accountSide = '1';  // Debito (activo)
        }

        // Buscar auxiliar existente
        $query = $this->CI->db->select('id')
            ->from('auxiliary_subaccounts')
            ->where('accountAccount', $userId)
            ->where('accountType', $accountType)
            ->where('deleted', 0)
            ->get();

        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }

        // Crear nueva cuenta auxiliar
        $data = array(
            'accountID' => (int)$pucCode,
            'accountName' => $user->name,
            'accountAccount' => $userId,
            'accountSide' => $accountSide,
            'accountStatement' => '1',
            'accountType' => $accountType,
            'accountBalance' => 0,
            'accountDebit' => 0,
            'accountCredit' => 0,
            'accountOrder' => 0,
            'accountStatus' => 1,
            'deleted' => 0,
            'created_at' => date('Y-m-d H:i:s')
        );

        $this->CI->db->insert('auxiliary_subaccounts', $data);

        if ($this->CI->db->affected_rows() > 0) {
            return $this->CI->db->insert_id();
        }

        return null;
    }

    /**
     * Crea cuentas auxiliares para todos los usuarios con roles que tengan PUC.
     * Idempotente - no duplica cuentas existentes.
     *
     * @return array con 'created', 'existing', 'skipped', 'failed', 'total'
     */
    public function bulkCreateUserAuxAccounts() {
        $this->CI->load->model('users_model');

        $users = $this->CI->users_model->getUsers(false);

        $result = array('created' => 0, 'existing' => 0, 'skipped' => 0, 'failed' => 0, 'total' => count($users));

        foreach ($users as $user) {
            $pucCode = $this->CI->users_model->getRolePucCode($user->role);
            if (!$pucCode) {
                $result['skipped']++;
                continue;
            }

            $auxId = $this->getOrCreateUserAuxAccount($user->idUser);
            if ($auxId) {
                $result['created']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }
}
