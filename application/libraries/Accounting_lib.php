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
        $this->CI->load->model('logs_model');

        // Cargar helpers
        $this->CI->load->helper('mam_helper');

        // Timezone
        date_default_timezone_set("America/Bogota");
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
    public function recordCashMovement($movementId, $type, $accountId, $amount, $storeId, $description, $userId, $destinationAccountId = null) {

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
                        $movementId
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
    public function recordPayment($paymentId, $invoiceId, $clientId, $amount, $methodId, $storeId, $userId, $cashAccountId = null) {

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
                $paymentId
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
        $query = $this->CI->db->select('id')
            ->from('subaccounts')
            ->where('pucCode', '110505')
            ->where('deleted', 0)
            ->get();

        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }

        return null;
    }

    /**
     * Obtiene cuenta de banco por bodega
     * Busca subcuenta con pucCode 111005 (Bancos Moneda Nacional)
     *
     * @param int $storeId ID de bodega
     * @return int|null ID de subcuenta o NULL si no existe
     */
    public function getBankAccount($storeId) {
        $query = $this->CI->db->select('id')
            ->from('subaccounts')
            ->where('pucCode', '111005')
            ->where('deleted', 0)
            ->get();

        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }

        return null;
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
        $query = $this->CI->db->select('id')
            ->from('subaccounts')
            ->where('pucCode', '130505')
            ->where('deleted', 0)
            ->get();

        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }

        return null;
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

        $data = array(
            'accountID' => 130505,  // PUC Clientes
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
    private function createEntry($debitAccountId, $debitAuxAccountId, $creditAccountId, $creditAuxAccountId, $amount, $description, $userId, $storeId, $transactionType, $transactionId, $entryDate = null) {

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
    public function recordInvoice($invoiceId, $clientId, $storeId, $total, $userId) {

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
                $invoiceId
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
    public function recordRefund($refundId, $invoiceId, $clientId, $amount, $storeId, $userId) {

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
                $refundId
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
    public function recordRefundReversal($refundId, $invoiceId, $clientId, $amount, $storeId, $userId) {

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
                $refundId
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
     * Obtiene cuenta de ventas
     * Busca subcuenta con pucCode 413505 (Ventas de Mercancías)
     *
     * @param int $storeId ID de bodega
     * @return int|null ID de subcuenta o NULL si no existe
     */
    public function getRevenueAccount($storeId) {
        $query = $this->CI->db->select('id')
            ->from('subaccounts')
            ->where('pucCode', '413505')
            ->where('deleted', 0)
            ->get();

        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }

        return null;
    }

    /**
     * Obtiene cuenta de devoluciones en ventas
     * Busca subcuenta con pucCode 417505 (Devoluciones en Ventas)
     *
     * @param int $storeId ID de bodega
     * @return int|null ID de subcuenta o NULL si no existe
     */
    public function getRefundAccount($storeId) {
        $query = $this->CI->db->select('id')
            ->from('subaccounts')
            ->where('pucCode', '417505')
            ->where('deleted', 0)
            ->get();

        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }

        return null;
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
        $query = $this->CI->db->select('id')
            ->from('subaccounts')
            ->where('pucCode', '220505')
            ->where('deleted', 0)
            ->get();

        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }

        return null;
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

        $data = array(
            'accountID' => 220505,  // PUC Proveedores
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
     * Registra asiento contable por factura de proveedor
     *
     * Asiento generado:
     * Débito: Gastos/Compras (cuenta según categoría)
     * Crédito: Proveedores (220505) + Auxiliar de proveedor
     *
     * @param int    $billId      ID de la factura de proveedor
     * @param int    $providerId  ID del proveedor
     * @param int    $storeId     ID de bodega
     * @param float  $total       Total de la factura
     * @param int    $userId      ID del usuario que registra
     * @param string $expenseCode Código PUC del gasto (default: 519595 - Otros Gastos)
     * @return bool TRUE si se creó el asiento, FALSE si falló
     */
    public function recordSupplierBill($billId, $providerId, $storeId, $total, $userId, $expenseCode = '519595') {

        if (!$billId || !$providerId || !$total || !$storeId || !$userId) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierBill - Parámetros faltantes");
            return false;
        }

        $this->CI->db->trans_start();

        try {
            // 1. Obtener cuenta de gasto/compra
            $debitAccountId = $this->getAccountByPucCode($expenseCode);
            if (!$debitAccountId) {
                // Fallback a cuenta genérica de gastos
                $debitAccountId = $this->getAccountByPucCode('519595');
                if (!$debitAccountId) {
                    $this->CI->logs_model->logMessage("error", "Accounting_lib::recordSupplierBill - No se encontró cuenta de gastos");
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
                $debitAccountId,        // Débito: Gastos/Compras
                null,                   // Sin auxiliar débito
                $creditAccountId,       // Crédito: Proveedores
                $creditAuxAccountId,    // Auxiliar: Proveedor específico
                $total,
                $description,
                $userId,
                $storeId,
                'supplier_bill',
                $billId
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
    public function recordSupplierPayment($paymentId, $providerId, $amount, $storeId, $userId, $cashAccountId) {

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
                $paymentId
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
    public function recordExpense($expenseId, $amount, $debitSubaccountId, $storeId, $userId, $description, $cashAccountId, $entryDate = null) {

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
                $entryDate
            );

            return $result; // Retorna entryId o false

        } catch (Exception $e) {
            $this->CI->logs_model->logMessage("error", "Accounting_lib::recordExpense - Error: " . $e->getMessage());
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
    public function recordSettlement($expenseId, $vendorId, $amount, $storeId, $userId, $description = null) {

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
                    $expenseId
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
                    $expenseId
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
            'accountID' => 236505,  // PUC Cuentas por Pagar - Costos y Gastos
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
        // Buscar por código PUC 519505 (Comisiones)
        $accountId = $this->getAccountByPucCode('519505');

        if ($accountId) {
            return $accountId;
        }

        // Si no existe con código PUC, buscar por nombre
        $query = $this->CI->db->select('id')
            ->from('subaccounts')
            ->like('accountName', 'comision', 'both')
            ->where('deleted', 0)
            ->get();

        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }

        // Si no existe, intentar con 5195 (Gastos Diversos)
        return $this->getAccountByPucCode('5195');
    }

    /**
     * Obtiene cuenta de cuentas por pagar vendedores (PUC 236505)
     *
     * @param int $storeId ID de bodega (opcional)
     * @return int|null ID de subcuenta o NULL si no existe
     */
    public function getVendorPayableAccount($storeId = null) {
        // Buscar por código PUC 236505 (Costos y Gastos por Pagar)
        $accountId = $this->getAccountByPucCode('236505');

        if ($accountId) {
            return $accountId;
        }

        // Si no existe, intentar con 2365 (Cuentas por Pagar)
        $accountId = $this->getAccountByPucCode('2365');

        if ($accountId) {
            return $accountId;
        }

        // Si no existe, buscar por nombre
        $query = $this->CI->db->select('id')
            ->from('subaccounts')
            ->group_start()
                ->like('accountName', 'por pagar', 'both')
                ->or_like('accountName', 'costos por pagar', 'both')
            ->group_end()
            ->where('deleted', 0)
            ->get();

        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }

        // Fallback: usar cuenta de proveedores si no hay específica
        return $this->getPayableAccount($storeId);
    }
}
