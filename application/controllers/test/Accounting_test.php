<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Accounting Library - Test Controller
 *
 * Script de testing para verificar funcionalidad de Accounting_lib
 * Ejecutar desde: /test/accounting_test/run
 *
 * IMPORTANTE: Solo para desarrollo - ELIMINAR en producción
 *
 * @package    MAM ERP
 * @subpackage Controllers/Test
 * @category   Testing
 */
class Accounting_test extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('accounting_lib');
        $this->load->model('logs_model');

        // SEGURIDAD: Solo permitir en entorno desarrollo
        if (ENVIRONMENT !== 'development') {
            show_404();
        }
    }

    /**
     * Página principal de tests
     */
    public function index() {
        echo "<h1>Accounting_lib - Tests</h1>";
        echo "<ul>";
        echo "<li><a href='" . base_url('test/accounting_test/test_get_accounts') . "'>Test 1: Obtener Cuentas</a></li>";
        echo "<li><a href='" . base_url('test/accounting_test/test_create_client_aux') . "'>Test 2: Crear Auxiliar de Cliente</a></li>";
        echo "<li><a href='" . base_url('test/accounting_test/test_record_payment') . "'>Test 3: Registrar Pago (SIMULA)</a></li>";
        echo "<li><a href='" . base_url('test/accounting_test/test_cash_transfer') . "'>Test 4: Transferencia entre Cajas (SIMULA)</a></li>";
        echo "</ul>";
    }

    /**
     * Test 1: Verificar obtención de cuentas
     */
    public function test_get_accounts() {
        echo "<h2>Test 1: Obtención de Cuentas</h2>";

        $storeId = 3;  // Bogotá

        echo "<h3>Resultados:</h3>";
        echo "<ul>";

        // Test getCashAccount
        $cashId = $this->accounting_lib->getCashAccount($storeId);
        echo "<li><strong>Cuenta de Caja:</strong> " . ($cashId ? "ID $cashId ✓" : "No encontrada ✗") . "</li>";

        // Test getBankAccount
        $bankId = $this->accounting_lib->getBankAccount($storeId);
        echo "<li><strong>Cuenta de Banco:</strong> " . ($bankId ? "ID $bankId ✓" : "No encontrada ✗") . "</li>";

        // Test getReceivableAccount
        $receivableId = $this->accounting_lib->getReceivableAccount($storeId);
        echo "<li><strong>Cuenta de Clientes:</strong> " . ($receivableId ? "ID $receivableId ✓" : "No encontrada ✗") . "</li>";

        echo "</ul>";

        // Mostrar detalles de las cuentas
        if ($cashId) {
            $this->showAccountDetails($cashId, 'Caja');
        }
        if ($bankId) {
            $this->showAccountDetails($bankId, 'Banco');
        }
        if ($receivableId) {
            $this->showAccountDetails($receivableId, 'Clientes');
        }

        echo "<p><a href='" . base_url('test/accounting_test') . "'>← Volver</a></p>";
    }

    /**
     * Test 2: Crear auxiliar de cliente
     */
    public function test_create_client_aux() {
        echo "<h2>Test 2: Crear/Obtener Auxiliar de Cliente</h2>";

        $clientId = 1;  // Usar primer cliente
        $storeId = 3;

        echo "<h3>Parametros:</h3>";
        echo "<ul>";
        echo "<li>Cliente ID: $clientId</li>";
        echo "<li>Bodega ID: $storeId</li>";
        echo "</ul>";

        $auxId = $this->accounting_lib->getOrCreateClientAuxAccount($clientId, $storeId);

        echo "<h3>Resultado:</h3>";
        if ($auxId) {
            echo "<p class='success'>✓ Cuenta auxiliar creada/encontrada con ID: $auxId</p>";
            $this->showAuxAccountDetails($auxId);
        } else {
            echo "<p class='error'>✗ Error al crear cuenta auxiliar</p>";
        }

        echo "<p><a href='" . base_url('test/accounting_test') . "'>← Volver</a></p>";
    }

    /**
     * Test 3: Simular registro de pago
     * NOTA: Solo simula, no crea registros reales
     */
    public function test_record_payment() {
        echo "<h2>Test 3: Registrar Pago (SIMULACIÓN)</h2>";

        echo "<p><strong>IMPORTANTE:</strong> Esta es una simulación. No se crean registros reales en la BD.</p>";

        $paymentId = 99999;
        $invoiceId = 99999;
        $clientId = 1;
        $amount = 100000;
        $methodId = 1;
        $storeId = 3;
        $userId = 1;

        echo "<h3>Parámetros:</h3>";
        echo "<ul>";
        echo "<li>Payment ID: $paymentId (ficticio)</li>";
        echo "<li>Invoice ID: $invoiceId (ficticio)</li>";
        echo "<li>Cliente ID: $clientId</li>";
        echo "<li>Monto: $" . number_format($amount, 2) . "</li>";
        echo "<li>Método de Pago: $methodId</li>";
        echo "<li>Bodega: $storeId</li>";
        echo "<li>Usuario: $userId</li>";
        echo "</ul>";

        echo "<h3>Cuentas que se utilizarían:</h3>";

        // Determinar cuenta de caja/banco
        $cashAccountId = $this->accounting_lib->getCashAccountByMethod($methodId, $storeId);
        echo "<p><strong>Débito (Caja/Banco):</strong> " . ($cashAccountId ? "ID $cashAccountId" : "No encontrada") . "</p>";

        // Cuenta de clientes
        $receivableId = $this->accounting_lib->getReceivableAccount($storeId);
        echo "<p><strong>Crédito (Clientes):</strong> " . ($receivableId ? "ID $receivableId" : "No encontrada") . "</p>";

        // Auxiliar de cliente
        $auxId = $this->accounting_lib->getOrCreateClientAuxAccount($clientId, $storeId);
        echo "<p><strong>Auxiliar de Cliente:</strong> " . ($auxId ? "ID $auxId" : "No creado") . "</p>";

        echo "<h3>Asiento que se generaría:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Cuenta</th><th>Débito</th><th>Crédito</th></tr>";
        echo "<tr><td>Caja/Banco (ID $cashAccountId)</td><td>$" . number_format($amount, 2) . "</td><td>-</td></tr>";
        echo "<tr><td>Clientes (ID $receivableId) + Aux (ID $auxId)</td><td>-</td><td>$" . number_format($amount, 2) . "</td></tr>";
        echo "<tr><th>TOTAL</th><th>$" . number_format($amount, 2) . "</th><th>$" . number_format($amount, 2) . "</th></tr>";
        echo "</table>";

        echo "<p><em>Para crear asiento real, descomentar la llamada a recordPayment() en el código</em></p>";

        // DESCOMENTADO para testing real (usar con precaución):
        // $result = $this->accounting_lib->recordPayment($paymentId, $invoiceId, $clientId, $amount, $methodId, $storeId, $userId);
        // echo "<p>Resultado: " . ($result ? "✓ Asiento creado" : "✗ Error") . "</p>";

        echo "<p><a href='" . base_url('test/accounting_test') . "'>← Volver</a></p>";
    }

    /**
     * Test 4: Simular transferencia entre cajas
     */
    public function test_cash_transfer() {
        echo "<h2>Test 4: Transferencia entre Cajas (SIMULACIÓN)</h2>";

        echo "<p><strong>IMPORTANTE:</strong> Esta es una simulación. No se crean registros reales en la BD.</p>";

        $movementId = 99999;
        $sourceAccountId = $this->accounting_lib->getCashAccount(3);  // Caja Bogotá
        $destinationAccountId = $this->accounting_lib->getBankAccount(3);  // Banco Bogotá
        $amount = 500000;
        $storeId = 3;
        $description = "Transferencia de Caja a Banco";
        $userId = 1;

        echo "<h3>Parámetros:</h3>";
        echo "<ul>";
        echo "<li>Movimiento ID: $movementId (ficticio)</li>";
        echo "<li>Cuenta Origen (Caja): $sourceAccountId</li>";
        echo "<li>Cuenta Destino (Banco): $destinationAccountId</li>";
        echo "<li>Monto: $" . number_format($amount, 2) . "</li>";
        echo "<li>Descripción: $description</li>";
        echo "</ul>";

        echo "<h3>Asiento que se generaría:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Cuenta</th><th>Débito</th><th>Crédito</th></tr>";
        echo "<tr><td>Banco (Destino ID $destinationAccountId)</td><td>$" . number_format($amount, 2) . "</td><td>-</td></tr>";
        echo "<tr><td>Caja (Origen ID $sourceAccountId)</td><td>-</td><td>$" . number_format($amount, 2) . "</td></tr>";
        echo "<tr><th>TOTAL</th><th>$" . number_format($amount, 2) . "</th><th>$" . number_format($amount, 2) . "</th></tr>";
        echo "</table>";

        echo "<p><em>Para crear asiento real, descomentar la llamada a recordCashMovement() en el código</em></p>";

        // DESCOMENTADO para testing real (usar con precaución):
        // $result = $this->accounting_lib->recordCashMovement($movementId, 'transfer', $sourceAccountId, $amount, $storeId, $description, $userId, $destinationAccountId);
        // echo "<p>Resultado: " . ($result ? "✓ Asiento creado" : "✗ Error") . "</p>";

        echo "<p><a href='" . base_url('test/accounting_test') . "'>← Volver</a></p>";
    }

    // ========================================================================
    // MÉTODOS AUXILIARES
    // ========================================================================

    /**
     * Muestra detalles de una subcuenta
     */
    private function showAccountDetails($accountId, $label) {
        $query = $this->db->select('*')
            ->from('subaccounts')
            ->where('id', $accountId)
            ->get();

        if ($query->num_rows() > 0) {
            $account = $query->row();
            echo "<h4>Detalles: $label (ID $accountId)</h4>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            echo "<tr><td>Nombre</td><td>{$account->accountName}</td></tr>";
            echo "<tr><td>PUC Code</td><td>{$account->pucCode}</td></tr>";
            echo "<tr><td>Tipo</td><td>{$account->accountType}</td></tr>";
            echo "<tr><td>Lado</td><td>" . ($account->accountSide == '1' ? 'Débito' : 'Crédito') . "</td></tr>";
            echo "<tr><td>Balance</td><td>$" . number_format($account->accountBalance, 2) . "</td></tr>";
            echo "<tr><td>Débitos Acum</td><td>$" . number_format($account->accountDebit, 2) . "</td></tr>";
            echo "<tr><td>Créditos Acum</td><td>$" . number_format($account->accountCredit, 2) . "</td></tr>";
            echo "</table>";
        }
    }

    /**
     * Muestra detalles de una cuenta auxiliar
     */
    private function showAuxAccountDetails($auxId) {
        $query = $this->db->select('*')
            ->from('auxiliary_subaccounts')
            ->where('id', $auxId)
            ->get();

        if ($query->num_rows() > 0) {
            $account = $query->row();
            echo "<h4>Detalles: Cuenta Auxiliar (ID $auxId)</h4>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            echo "<tr><td>Nombre</td><td>{$account->accountName}</td></tr>";
            echo "<tr><td>Tipo</td><td>{$account->accountType}</td></tr>";
            echo "<tr><td>Lado</td><td>" . ($account->accountSide == '1' ? 'Débito' : 'Crédito') . "</td></tr>";
            echo "<tr><td>Balance</td><td>$" . number_format($account->accountBalance, 2) . "</td></tr>";
            echo "<tr><td>Vinculado a</td><td>Cliente/Proveedor ID {$account->accountAccount}</td></tr>";
            echo "</table>";
        }
    }
}
