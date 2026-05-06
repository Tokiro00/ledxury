<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * Estado de Cuenta — vista ejecutiva con KPIs + timeline + cronologico.
 *
 * Reescrito v1.30.31:
 *   - KPI strip: saldo inicial, facturado, cobrado, saldo periodo, saldo global
 *   - Timeline SVG: evolucion del saldo (running balance) en el periodo
 *   - Tabla cronologica con saldo corrido (igual que antes)
 *
 * Comparte el template `_statement.php` con ProviderStatement.
 */
class ClientStatement extends AbstractReport
{
    public function id(): string { return 'client_statement'; }
    public function title(): string { return 'Estado de Cuenta'; }
    public function description(): string
    {
        return 'Movimientos del cliente con saldo corrido + saldo inicial/final del período + evolución gráfica del saldo.';
    }

    public function requiredRoles(): array { return [2, 3, 5, 8]; }

    public function filterDefinitions(): array
    {
        return [
            ['name' => 'client_id', 'label' => 'Cliente', 'type' => 'client', 'required' => true],
            $this->storeFilterDefinition(),
            ['name' => 'date_range', 'type' => 'date_range'],
            ['name' => 'desde', 'label' => 'Desde', 'type' => 'date', 'default' => date('Y-01-01'), 'required' => true],
            ['name' => 'hasta', 'label' => 'Hasta', 'type' => 'date', 'default' => date('Y-m-d'),  'required' => true],
        ];
    }

    public function availableFormats(): array { return ['html', 'pdf', 'xlsx', 'csv']; }
    public function availableChannels(): array { return ['email', 'whatsapp', 'schedule']; }

    public function data(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $clientId = (int) ($params['client_id'] ?? 0);
        $desde = $params['desde'];
        $hasta = $params['hasta'];

        $CI =& get_instance();
        if (!$clientId) {
            return ['columns' => $this->columns(), 'rows' => [], 'totals' => null, 'side' => 'client'];
        }

        $storeId = (int) ($params['store_id'] ?? 0);
        $storeFilter = $storeId ? " AND idStore = " . $storeId : "";
        $pagosFilter = $storeId ? " AND p.invoiceId IN (SELECT idInvoice FROM invoices WHERE idStore = " . $storeId . ")" : "";
        $cnStoreFilter = $storeId ? " AND storeId = " . $storeId : "";

        // Saldo global (current outstanding) — ancla matemática del reporte.
        // Mismo calculo que Pending Invoices y Aging usan: SUM(total-payment-discount)
        // para invoices en state 0 (sin pagar) o 1 (parcial). Garantiza que el
        // "saldo final" del estado de cuenta coincida con lo que se ve en otros
        // reportes — sin drift de tabla payments vs invoice.payment column.
        $globalRow = $CI->db->query("
            SELECT COALESCE(SUM(total - payment - discount), 0) AS saldo
            FROM invoices WHERE clientId = ? AND deleted = 0 AND state IN (0,1)
        ", [$clientId])->row();
        $saldoGlobal = (float) ($globalRow->saldo ?? 0);

        // Facturas en rango (incluye state 2/3 para no perder facturas pagadas)
        $facturas = $CI->db->query("
            SELECT idInvoice, date, total, payment, discount, state
            FROM invoices WHERE clientId = ? AND deleted = 0
              AND DATE(date) BETWEEN ? AND ? $storeFilter
            ORDER BY date ASC
        ", [$clientId, $desde, $hasta])->result();

        // Pagos en rango
        $pagos = $CI->db->query("
            SELECT p.idPayment, p.invoiceId, p.payment, p.date, p.comments
            FROM payments p WHERE p.clientId = ? AND p.deleted = 0
              AND DATE(p.date) BETWEEN ? AND ? $pagosFilter
            ORDER BY p.date ASC
        ", [$clientId, $desde, $hasta])->result();

        // Notas de credito aprobadas en rango. La aprobacion suma al invoice.payment
        // del invoice de origen, asi que reduce el saldo igual que un pago. Antes este
        // movimiento no se mostraba — el usuario veia la factura sin el credito que la
        // cancelaba.
        $creditNotes = $CI->db->query("
            SELECT id, invoiceId, total, type, reason, observations, approved_at
            FROM credit_notes
            WHERE clientId = ? AND deleted = 0 AND status = 'aprobada'
              AND DATE(approved_at) BETWEEN ? AND ? $cnStoreFilter
            ORDER BY approved_at ASC
        ", [$clientId, $desde, $hasta])->result();

        // Cobrado/facturado del periodo (con discount restado en facturas)
        $movimientos = [];
        $totalFacturado = 0.0; // neto: total - discount (alineado con saldo_global)
        $totalPagado = 0.0;
        foreach ($facturas as $f) {
            $netoFactura = (float) $f->total - (float) $f->discount;
            $movimientos[] = [
                'fecha' => $f->date,
                'tipo' => 'Factura',
                'referencia' => '#' . $f->idInvoice,
                'descripcion' => 'Factura de venta' . ((float)$f->discount > 0 ? ' (con descuento)' : ''),
                'debito' => $netoFactura,
                'credito' => 0.0,
                'saldo' => 0.0,
            ];
            $totalFacturado += $netoFactura;
        }
        foreach ($pagos as $p) {
            $movimientos[] = [
                'fecha' => $p->date,
                'tipo' => 'Pago',
                'referencia' => 'Fact #' . $p->invoiceId,
                'descripcion' => $p->comments ?: 'Abono a factura',
                'debito' => 0.0,
                'credito' => (float) $p->payment,
                'saldo' => 0.0,
            ];
            $totalPagado += (float) $p->payment;
        }
        $totalCreditNotes = 0.0;
        foreach ($creditNotes as $cn) {
            $cnTypeLabels = ['devolucion' => 'Devolución', 'garantia' => 'Garantía'];
            $cnReasonLabels = [
                'defecto' => 'producto defectuoso',
                'dano' => 'producto dañado',
                'inconformidad' => 'inconformidad cliente',
                'garantia' => 'garantía fabricante',
                'error_facturacion' => 'error de facturación',
                'otro' => 'otro',
            ];
            $typeLabel = $cnTypeLabels[$cn->type] ?? $cn->type;
            $reasonLabel = $cnReasonLabels[$cn->reason] ?? $cn->reason;
            $desc = 'Nota crédito (' . $typeLabel . ': ' . $reasonLabel . ')';
            if (!empty($cn->observations)) $desc .= ' — ' . $cn->observations;
            $movimientos[] = [
                'fecha' => $cn->approved_at,
                'tipo' => 'Nota Crédito',
                'referencia' => '#NC-' . $cn->id . ($cn->invoiceId ? ' / Fact #' . $cn->invoiceId : ''),
                'descripcion' => $desc,
                'debito' => 0.0,
                'credito' => (float) $cn->total,
                'saldo' => 0.0,
            ];
            $totalCreditNotes += (float) $cn->total;
        }

        usort($movimientos, fn($a, $b) => strtotime($a['fecha']) - strtotime($b['fecha']));

        // Si hasta >= hoy, el saldo final = saldo_global. Si hasta es pasado, usamos
        // ajuste hacia adelante: saldo_at_hasta = saldo_global − futuro_neto.
        $isHastaToday = strtotime($hasta) >= strtotime('today');
        if ($isHastaToday) {
            $closing = $saldoGlobal;
        } else {
            // Movimientos despues de $hasta: revertimos para llegar a saldo_at_hasta.
            // Incluye CN aprobadas futuras (cuentan como credito al saldo igual que pagos).
            $futureRow = $CI->db->query("
                SELECT COALESCE((
                    SELECT COALESCE(SUM(total - discount), 0) FROM invoices
                    WHERE clientId = ? AND deleted = 0 AND DATE(date) > ? $storeFilter
                ), 0) AS facturado_futuro,
                COALESCE((
                    SELECT COALESCE(SUM(p.payment), 0) FROM payments p
                    WHERE p.clientId = ? AND p.deleted = 0 AND DATE(p.date) > ? $pagosFilter
                ), 0) AS cobrado_futuro,
                COALESCE((
                    SELECT COALESCE(SUM(total), 0) FROM credit_notes
                    WHERE clientId = ? AND deleted = 0 AND status = 'aprobada' AND DATE(approved_at) > ? $cnStoreFilter
                ), 0) AS cn_futuro
            ", [$clientId, $hasta, $clientId, $hasta, $clientId, $hasta])->row();
            $closing = $saldoGlobal
                     - (float)$futureRow->facturado_futuro
                     + (float)$futureRow->cobrado_futuro
                     + (float)$futureRow->cn_futuro;
        }

        // Saldo inicial derivado matematicamente. opening + facturado − (cobrado + CN) = closing
        $opening = $closing - $totalFacturado + $totalPagado + $totalCreditNotes;

        // Saldo corrido en cada movimiento (arranca en opening)
        $saldo = $opening;
        foreach ($movimientos as &$m) {
            $saldo += $m['debito'] - $m['credito'];
            $m['saldo'] = $saldo;
        }
        unset($m);

        // Timeline diario: saldo al cierre de cada día
        $timeline = $this->buildTimeline($movimientos, $opening, $desde, $hasta);

        $kpis = [
            'opening'           => $opening,
            'in_period'         => $totalFacturado,
            // out_period suma pagos + notas credito aprobadas — todo lo que reduce saldo
            'out_period'        => $totalPagado + $totalCreditNotes,
            'closing'           => $closing,
            'global'            => $saldoGlobal,
            'num_invoices'      => count($facturas),
            'num_payments'      => count($pagos),
            'num_credit_notes'  => count($creditNotes),
        ];

        return [
            'kpis'     => $kpis,
            'timeline' => $timeline,
            'side'     => 'client', // afecta colores y labels
            'columns'  => $this->columns(),
            'rows'     => $movimientos,
            'totals'   => [
                'fecha' => 'Total periodo',
                'tipo' => '',
                'referencia' => '',
                'descripcion' => '',
                'debito' => $totalFacturado,
                'credito' => $totalPagado + $totalCreditNotes,
                'saldo' => $closing,
            ],
        ];
    }

    public function meta(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $clientId = (int) ($params['client_id'] ?? 0);

        $client = null;
        if ($clientId) {
            $CI =& get_instance();
            $client = $CI->db->get_where('clients', ['idClient' => $clientId])->row();
        }

        $clientName = $client->name ?? 'cliente_' . $clientId;
        $hasta = $params['hasta'] ?? date('Y-m-d');

        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $clientName));
        $slug = trim($slug, '_');

        return [
            'filename' => 'estado_cuenta_' . $slug . '_' . $hasta,
            'email_subject' => 'Estado de cuenta — ' . $clientName,
            'email_body' => sprintf(
                "Hola %s,\n\nTe enviamos adjunto tu estado de cuenta actualizado al %s.\n\nSi tenés alguna duda, respondé este correo.\n\n— MAM ERP",
                $clientName, date('d/m/Y', strtotime($hasta))
            ),
            'whatsapp_report_label' => 'estado de cuenta',
            'client_id' => $clientId ?: null,
            'client_name' => $clientName,
            'pdf_orientation' => 'P',
            'html_template' => 'sisvent/reports/templates/_statement', // shared con ProviderStatement
        ];
    }

    /**
     * Genera puntos para el chart de evolucion del saldo.
     * Una entrada por dia entre desde y hasta con el saldo al final de ese dia.
     */
    private function buildTimeline(array $movimientos, float $opening, string $desde, string $hasta): array
    {
        $cursor = strtotime($desde);
        $end = strtotime($hasta);

        // Index de movimientos por fecha
        $movsByDay = [];
        foreach ($movimientos as $m) {
            $key = date('Y-m-d', strtotime($m['fecha']));
            if (!isset($movsByDay[$key])) $movsByDay[$key] = ['in' => 0, 'out' => 0];
            $movsByDay[$key]['in']  += $m['debito'];
            $movsByDay[$key]['out'] += $m['credito'];
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
            ['key' => 'fecha',       'label' => 'Fecha',       'type' => 'date'],
            ['key' => 'tipo',        'label' => 'Tipo',        'type' => 'text'],
            ['key' => 'referencia',  'label' => 'Referencia',  'type' => 'text'],
            ['key' => 'descripcion', 'label' => 'Descripción', 'type' => 'text'],
            ['key' => 'debito',      'label' => 'Débito',      'type' => 'currency'],
            ['key' => 'credito',     'label' => 'Crédito',     'type' => 'currency'],
            ['key' => 'saldo',       'label' => 'Saldo',       'type' => 'currency'],
        ];
    }
}
