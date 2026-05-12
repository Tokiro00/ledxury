<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/ReportInterface.php';
require_once __DIR__ . '/AbstractReport.php';

/**
 * ReportRegistry — catálogo de todos los reportes registrados.
 *
 * Punto único donde se declaran todos los reportes del engine. El
 * controller `Reports.php` lo consulta para resolver `?id={report_id}` →
 * instancia de la definición.
 *
 * Para agregar un reporte nuevo:
 *   1. Crear application/libraries/reports/definitions/MyReport.php
 *   2. Agregar entrada en register() acá
 *   3. (opcional) Declararlo como visible en el index del módulo
 *
 * Auto-discovery NO se hace en CI3 (no hay PSR-4); se mantiene una lista
 * explícita para evitar magia + dejar trazable qué hay registrado.
 */
class ReportRegistry
{
    /** @var array<string, ReportInterface> */
    private $reports = [];

    /** @var ReportRegistry|null */
    private static $instance = null;

    public static function getInstance(): ReportRegistry
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->register();
        }
        return self::$instance;
    }

    /**
     * Registra todos los reportes disponibles. Editar esta función para
     * agregar nuevos.
     */
    private function register(): void
    {
        // Cartera + estados de cuenta
        $this->add('client_statement',     'ClientStatement.php',     'ClientStatement');
        $this->add('provider_statement',   'ProviderStatement.php',   'ProviderStatement');
        $this->add('aging',                'Aging.php',               'Aging');
        $this->add('pending_invoices',     'PendingInvoices.php',     'PendingInvoices');
        // Ventas
        $this->add('daily_sales',          'DailySales.php',          'DailySales');
        $this->add('vendor_performance',   'VendorPerformance.php',   'VendorPerformance');
        $this->add('top_products',         'TopProducts.php',         'TopProducts');
        // Tesorería
        $this->add('cash_flow',            'CashFlow.php',            'CashFlow');
        // v1.32.2: Contabilidad — estados financieros principales
        $this->add('income_statement',     'IncomeStatement.php',     'IncomeStatement');
        $this->add('balance_sheet',        'BalanceSheet.php',        'BalanceSheet');
        $this->add('trial_balance',        'TrialBalance.php',        'TrialBalance');
        // Inventario
        $this->add('inventory_valuation',  'InventoryValuation.php',  'InventoryValuation');
        $this->add('inventory_movements',  'InventoryMovements.php',  'InventoryMovements');
        // v1.30.43: comisiones + analisis ABC
        $this->add('vendor_commissions',   'VendorCommissions.php',   'VendorCommissions');
        $this->add('clients_abc',          'ClientsABC.php',          'ClientsABC');
        $this->add('products_abc',         'ProductsABC.php',         'ProductsABC');
        // v1.31.35: logistica
        $this->add('dispatches',           'Dispatches.php',          'Dispatches');
        // v1.32.0: recaudo transportadora (cartera con guias)
        $this->add('carrier_collections',  'CarrierCollections.php',  'CarrierCollections');
        // v1.32.1: análisis de devoluciones (tasa por dimensión + clientes problemáticos)
        $this->add('returns_analytics',    'ReturnsAnalytics.php',    'ReturnsAnalytics');
        // Pendientes de migrar a v2: financial_dashboard, expenses_by_category,
        // inventory_rotation, accounting_balance, accounting_results, sales_yoy,
        // vendor_profitability, debt_by_city, etc.
    }

    /**
     * Carga lazy: solo requiere el archivo si alguien pide ese reporte.
     */
    private function add(string $id, string $filename, string $className): void
    {
        $path = __DIR__ . '/definitions/' . $filename;
        if (!file_exists($path)) {
            // Definición todavía no existe (release futuro). Skip silenciosamente.
            return;
        }
        require_once $path;
        if (!class_exists($className)) {
            log_message('error', "ReportRegistry: clase $className no existe en $filename");
            return;
        }
        $instance = new $className();
        if (!($instance instanceof ReportInterface)) {
            log_message('error', "ReportRegistry: $className no implementa ReportInterface");
            return;
        }
        if ($instance->id() !== $id) {
            log_message('error', "ReportRegistry: id() de $className es '{$instance->id()}' pero se registró como '$id'");
            return;
        }
        $this->reports[$id] = $instance;
    }

    /**
     * Resuelve un id a su definición. Lanza NotFoundException si no existe.
     */
    public function get(string $id): ReportInterface
    {
        if (!isset($this->reports[$id])) {
            throw new NotFoundException("Reporte '$id' no existe", 'REPORT_NOT_FOUND');
        }
        return $this->reports[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->reports[$id]);
    }

    /**
     * Lista todos los reportes que puede ver un usuario con role $role.
     *
     * @return ReportInterface[]
     */
    public function listAccessible(int $role): array
    {
        // Override de matriz: el permiso 'reportes_v2' habilita TODOS los reports v2
        // independiente del requiredRoles() de cada uno (util para logistica,
        // supervisores transversales, etc.). userCanAccess() de AbstractReport ya
        // lo honra; lo replicamos aca para reports legacy que no extienden Abstract.
        $hasOverride = (function_exists('has_permission') && has_permission('reportes_v2'));
        $accessible = [];
        foreach ($this->reports as $report) {
            if ($report instanceof AbstractReport) {
                if ($report->userCanAccess($role)) $accessible[] = $report;
            } elseif ($role === 1 || $hasOverride || in_array($role, $report->requiredRoles(), true)) {
                $accessible[] = $report;
            }
        }
        return $accessible;
    }

    /**
     * Lista TODOS los reportes (sin filtrar por rol). Para admin UI.
     *
     * @return ReportInterface[]
     */
    public function all(): array
    {
        return array_values($this->reports);
    }
}
