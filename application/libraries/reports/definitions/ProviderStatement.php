<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * Estado de Cuenta Proveedor — espejo de ClientStatement adaptado al pasivo.
 *
 * Reescrito v1.30.31:
 *   - KPI strip: saldo inicial, comprado, pagado, saldo periodo, saldo global
 *   - Timeline SVG: evolucion del saldo (deuda con proveedor)
 *   - Tabla cronologica con saldo corrido
 *
 * Comparte el template `_statement.php` con ClientStatement.
 */
class ProviderStatement extends AbstractReport
{
    public function id(): string { return 'provider_statement'; }
    public function title(): string { return 'Estado de Cuenta Proveedor'; }
    public function description(): string
    {
        return 'Movimientos del proveedor con saldo corrido + saldo inicial/final + evolución gráfica.';
    }

    public function requiredRoles(): array { return [2, 5, 4]; }

    public function filterDefinitions(): array
    {
        return [
            ['name' => 'provider_id', 'label' => 'Proveedor', 'type' => 'provider', 'required' => true],
            $this->storeFilterDefinition(),
            ['name' => 'date_range', 'type' => 'date_range'],
            ['name' => 'desde', 'label' => 'Desde', 'type' => 'date', 'default' => date('Y-01-01'), 'required' => true],
            ['name' => 'hasta', 'label' => 'Hasta', 'type' => 'date', 'default' => date('Y-m-d'),  'required' => true],
        ];
    }

    public function availableChannels(): array { return ['email', 'schedule']; }

    public function data(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $providerId = (int) ($params['provider_id'] ?? 0);
        $desde = $params['desde'];
        $hasta = $params['hasta'];

        $CI =& get_instance();
        if (!$providerId) {
            return ['columns' => $this->columns(), 'rows' => [], 'totals' => null, 'side' => 'provider'];
        }

        $storeId = (int) ($params['store_id'] ?? 0);
        $storeFilterFacturas = $storeId ? " AND storeId = " . $storeId : "";

        // Saldo global (lo que le debemos AHORA al proveedor) — ancla matemática.
        $globalRow = $CI->db->query("
            SELECT COALESCE(SUM(total - paidAmount), 0) AS saldo
            FROM supplier_invoices WHERE providerId = ? AND status NOT IN ('pagada','anulada')
        ", [$providerId])->row();
        $saldoGlobal = (float) ($globalRow->saldo ?? 0);

        // Facturas en rango
        $facturas = $CI->db->query("
            SELECT idSupplierInvoice, invoiceDate, total, paidAmount, status, invoiceNumber
            FROM supplier_invoices
            WHERE providerId = ? AND status != 'anulada'
              AND DATE(invoiceDate) BETWEEN ? AND ? $storeFilterFacturas
            ORDER BY invoiceDate ASC
        ", [$providerId, $desde, $hasta])->result();

        // Pagos en rango
        $pagos = $CI->db->query("
            SELECT idSupplierPayment AS idMovement, paymentDate AS movementDate,
                   amount, IFNULL(notes, reference) AS concept, sourceId
            FROM supplier_payments
            WHERE providerId = ? AND status != 'anulado'
              AND DATE(paymentDate) BETWEEN ? AND ?
            ORDER BY paymentDate ASC
        ", [$providerId, $desde, $hasta])->result();

        $movimientos = [];
        $totalCompras = 0.0;
        $totalPagado = 0.0;
        foreach ($facturas as $f) {
            $movimientos[] = [
                'fecha' => $f->invoiceDate,
                'tipo' => 'Factura compra',
                'referencia' => $f->invoiceNumber,
                'descripcion' => 'Factura del proveedor — ' . ucfirst($f->status),
                'debito' => 0.0,
                'credito' => (float) $f->total,
                'saldo' => 0.0,
            ];
            $totalCompras += (float) $f->total;
        }
        foreach ($pagos as $p) {
            $movimientos[] = [
                'fecha' => $p->movementDate,
                'tipo' => 'Pago',
                'referencia' => '#' . $p->idMovement,
                'descripcion' => $p->concept ?: 'Pago al proveedor',
                'debito' => (float) $p->amount,
                'credito' => 0.0,
                'saldo' => 0.0,
            ];
            $totalPagado += (float) $p->amount;
        }

        usort($movimientos, fn($a, $b) => strtotime($a['fecha']) - strtotime($b['fecha']));

        // Saldo final: si hasta >= hoy, == saldo_global. Si no, ajuste hacia adelante.
        $isHastaToday = strtotime($hasta) >= strtotime('today');
        if ($isHastaToday) {
            $closing = $saldoGlobal;
        } else {
            $futureRow = $CI->db->query("
                SELECT COALESCE((
                    SELECT COALESCE(SUM(total), 0) FROM supplier_invoices
                    WHERE providerId = ? AND status != 'anulada' AND DATE(invoiceDate) > ? $storeFilterFacturas
                ), 0) AS facturado_futuro,
                COALESCE((
                    SELECT COALESCE(SUM(amount), 0) FROM supplier_payments
                    WHERE providerId = ? AND status != 'anulado' AND DATE(paymentDate) > ?
                ), 0) AS pagado_futuro
            ", [$providerId, $hasta, $providerId, $hasta])->row();
            $closing = $saldoGlobal - (float)$futureRow->facturado_futuro + (float)$futureRow->pagado_futuro;
        }

        // Opening derivado: closing - compras + pagos (math always cuadra)
        $opening = $closing - $totalCompras + $totalPagado;

        // Saldo corrido en cada movimiento (lado pasivo: credito = deuda, debito = pago)
        $saldo = $opening;
        foreach ($movimientos as &$m) {
            $saldo += $m['credito'] - $m['debito'];
            $m['saldo'] = $saldo;
        }
        unset($m);

        // Timeline diario
        $timeline = $this->buildTimeline($movimientos, $opening, $desde, $hasta);

        $kpis = [
            'opening'      => $opening,
            'in_period'    => $totalCompras,
            'out_period'   => $totalPagado,
            'closing'      => $closing,
            'global'       => $saldoGlobal,
            'num_invoices' => count($facturas),
            'num_payments' => count($pagos),
        ];

        return [
            'kpis'     => $kpis,
            'timeline' => $timeline,
            'side'     => 'provider',
            'columns'  => $this->columns(),
            'rows'     => $movimientos,
            'totals'   => [
                'fecha' => 'Total periodo',
                'tipo' => '',
                'referencia' => '',
                'descripcion' => '',
                'debito' => $totalPagado,
                'credito' => $totalCompras,
                'saldo' => $closing,
            ],
        ];
    }

    public function meta(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $providerId = (int) ($params['provider_id'] ?? 0);

        $provider = null;
        if ($providerId) {
            $CI =& get_instance();
            $provider = $CI->db->get_where('providers', ['idProvider' => $providerId])->row();
        }
        $providerName = $provider->name ?? 'proveedor_' . $providerId;
        $hasta = $params['hasta'] ?? date('Y-m-d');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $providerName));
        $slug = trim($slug, '_');

        return [
            'filename' => 'estado_proveedor_' . $slug . '_' . $hasta,
            'email_subject' => 'Estado de cuenta proveedor — ' . $providerName,
            'email_body' => sprintf(
                "Estado de cuenta del proveedor %s al %s.\n\n— MAM ERP",
                $providerName, date('d/m/Y', strtotime($hasta))
            ),
            'pdf_orientation' => 'P',
            'html_template' => 'sisvent/reports/templates/_statement',
        ];
    }

    private function buildTimeline(array $movimientos, float $opening, string $desde, string $hasta): array
    {
        $cursor = strtotime($desde);
        $end = strtotime($hasta);

        $movsByDay = [];
        foreach ($movimientos as $m) {
            $key = date('Y-m-d', strtotime($m['fecha']));
            if (!isset($movsByDay[$key])) $movsByDay[$key] = ['in' => 0, 'out' => 0];
            $movsByDay[$key]['in']  += $m['credito']; // +credito = aumenta deuda
            $movsByDay[$key]['out'] += $m['debito'];  // +debito = pagamos = reduce deuda
        }

        $timeline = [];
        $saldo = $opening;
        while ($cursor <= $end) {
            $key = date('Y-m-d', $cursor);
            $delta = $movsByDay[$key] ?? ['in' => 0, 'out' => 0];
            $saldo += $delta['in'] - $delta['out'];
            $timeline[] = [
                'date'  => $key,
                'label' => date('d/m', $cursor),
                'saldo' => $saldo,
            ];
            $cursor = strtotime('+1 day', $cursor);
        }
        return $timeline;
    }

    private function columns(): array
    {
        return [
            ['key' => 'fecha',       'label' => 'Fecha',         'type' => 'date'],
            ['key' => 'tipo',        'label' => 'Tipo',          'type' => 'text'],
            ['key' => 'referencia',  'label' => 'Referencia',    'type' => 'text'],
            ['key' => 'descripcion', 'label' => 'Descripción',   'type' => 'text'],
            ['key' => 'debito',      'label' => 'Pagamos',       'type' => 'currency'],
            ['key' => 'credito',     'label' => 'Nos facturó',   'type' => 'currency'],
            ['key' => 'saldo',       'label' => 'Saldo a favor', 'type' => 'currency'],
        ];
    }
}
