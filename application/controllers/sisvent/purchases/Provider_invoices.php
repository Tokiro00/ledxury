<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Provider_invoices — facturas de proveedor (CxP).
 * Portado de stockaccessories.co (20/08/2026) y adaptado a Ledxury:
 * moneda base COP, tesorería por cajas/bancos, CxP con auxiliar por proveedor.
 * Los pagos de costos de importación por caja quedan para fase 2.
 *
 * URLs:
 *   GET  /sisvent/purchases/provider_invoices[?provider_id=N&status=open]
 *   GET  /sisvent/purchases/provider_invoices/add
 *   POST /sisvent/purchases/provider_invoices/save
 *   GET  /sisvent/purchases/provider_invoices/view/<id>
 *   GET  /sisvent/purchases/provider_invoices/statement/<provider_id>
 */
class Provider_invoices extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control();
        $this->load->helper('compras');
        $this->load->model('cxp_model');
        $this->load->model('providers_model');
        $this->load->model('stores_model');
    }

    private function _guard(): int
    {
        $role = (int) $this->session->userdata('user_data')['role'];
        if (!in_array($role, array(1, 2, 4), true)) {
            show_error('No autorizado.', 403);
            exit;
        }
        return $role;
    }

    /** Cajas y bancos activos para los selects de pago. */
    private function _fuentesPago(): array
    {
        $bancos = $this->db->query("SELECT idBankAccount AS id, bankName AS name FROM bank_accounts WHERE COALESCE(deleted,0)=0 ORDER BY bankName")->result();
        $cajas  = $this->db->query("SELECT idCashbox AS id, name FROM cashboxes WHERE COALESCE(deleted,0)=0 ORDER BY name")->result();
        return array('bancos' => $bancos, 'cajas' => $cajas);
    }

    public function index(): void
    {
        $role = $this->_guard();
        $filters = array(
            'provider_id' => $this->input->get('provider_id') ?: null,
            'status'      => $this->input->get('status') ?: null,
            'open_only'   => $this->input->get('open_only') ? 1 : 0,
        );
        $invoices = $this->cxp_model->listInvoices($filters);
        $providers = $this->providers_model->getProviders();
        $selectedProvider = null;
        if (!empty($filters['provider_id'])) {
            foreach ($providers as $p) {
                if ((int) $p->idProvider === (int) $filters['provider_id']) { $selectedProvider = $p; break; }
            }
        }

        // Gastos de importación por pagar (consolidado). Respeta el filtro.
        $importPayables = array();
        $importPayTotal = 0.0;
        if ($this->db->table_exists('provider_invoice_import_costs')) {
            $provFilter = !empty($filters['provider_id']) ? ' AND pi.provider_id = ' . (int) $filters['provider_id'] : '';
            $importPayables = $this->db->query("
                SELECT ic.id, ic.concept, ic.description, ic.amount_base, ic.paid_amount,
                       ROUND(ic.amount_base - ic.paid_amount, 2) AS outstanding,
                       pi.id AS invoice_id, pi.inv_code, pi.received_at, pr.name AS provider_name
                FROM provider_invoice_import_costs ic
                JOIN provider_invoices pi ON pi.id = ic.provider_invoice_id
                LEFT JOIN providers pr ON pr.idProvider = pi.provider_id
                WHERE COALESCE(ic.deleted,0) = 0
                  AND ic.paid_source_id IS NULL
                  AND ROUND(ic.amount_base - ic.paid_amount, 2) > 0.005
                  AND COALESCE(pi.deleted,0) = 0 $provFilter
                ORDER BY pr.name, pi.inv_code, ic.id
            ")->result();
            foreach ($importPayables as $p) { $importPayTotal += (float) $p->outstanding; }
        }

        $fuentes = $this->_fuentesPago();
        $this->load->view('sisvent/purchases/provider_invoices/index', array(
            'invoices'          => $invoices,
            'providers'         => $providers,
            'filters'           => $filters,
            'selected_provider' => $selectedProvider,
            'import_payables'   => $importPayables,
            'import_pay_total'  => $importPayTotal,
            'bancos'            => $fuentes['bancos'],
            'cajas'             => $fuentes['cajas'],
            'role'              => $role,
        ));
    }

    public function add(): void
    {
        $role = $this->_guard();
        $this->load->view('sisvent/purchases/provider_invoices/add', array(
            'providers' => $this->providers_model->getProviders(),
            'role'      => $role,
            'preset_provider_id' => (int) $this->input->get('provider_id'),
        ));
    }

    /** Edición — SOLO si no fue recibida ni tiene pagos de caja. */
    public function edit($id): void
    {
        $role = $this->_guard();
        $id = (int) $id;
        $inv = $this->cxp_model->getInvoice($id);
        if (!$inv) {
            $this->session->set_flashdata('error', 'Factura no encontrada.');
            redirect('sisvent/purchases/provider_invoices');
            return;
        }
        if ((int) ($inv->cash_payments ?? 0) > 0) {
            $this->session->set_flashdata('error', 'No se puede editar: la factura tiene pagos desde caja/banco. Anula los pagos primero.');
            redirect('sisvent/purchases/provider_invoices/view/' . $id);
            return;
        }
        if (!empty($inv->received_at)) {
            $this->session->set_flashdata('error', 'No se puede editar: la factura ya fue recibida.');
            redirect('sisvent/purchases/provider_invoices/view/' . $id);
            return;
        }
        $items = $this->db->query("
            SELECT product_id, description, quantity, unit_cost
            FROM provider_invoice_items WHERE provider_invoice_id = ?
        ", array($id))->result();

        $this->load->view('sisvent/purchases/provider_invoices/add', array(
            'providers'          => $this->providers_model->getProviders(),
            'role'               => $role,
            'preset_provider_id' => (int) $inv->provider_id,
            'inv'                => $inv,
            'items_prefill'      => $items,
        ));
    }

    /** Búsqueda de productos para los ítems de la factura. */
    public function search_products(): void
    {
        $this->_guard();
        $q = trim((string) $this->input->get('q'));
        if ($q === '') {
            $this->output->set_content_type('application/json')->set_output(json_encode(array('rows' => array())));
            return;
        }
        $like = '%' . $q . '%';
        $rows = $this->db->query("
            SELECT idProduct, description, price, cost, cost_cop, cost_rmb, picture_url
            FROM products
            WHERE deleted = 0 AND (idProduct LIKE ? OR description LIKE ?)
            ORDER BY description
            LIMIT 25
        ", array($like, $like))->result();
        $this->output->set_content_type('application/json')->set_output(json_encode(array('rows' => $rows)));
    }

    /**
     * Estado de cuenta del proveedor en SU moneda (para conciliar 1:1 con el
     * estado que envía el proveedor — ej. las remisiones de MAM en COP, o un
     * proveedor de China en RMB) más el equivalente en pesos.
     */
    public function statement($providerId): void
    {
        $role = $this->_guard();
        $providerId = (int) $providerId;
        $provider = $this->providers_model->getProvider($providerId);
        if (!$provider) { show_404(); return; }

        $invoices = $this->db->query("
            SELECT id, inv_code, issue_date, due_date, currency, exchange_rate, total, paid, status,
                   (total - paid) AS balance
            FROM provider_invoices
            WHERE provider_id = ? AND COALESCE(deleted,0)=0
            ORDER BY issue_date ASC, id ASC
        ", array($providerId))->result();

        $payments = $this->db->query("
            SELECT pp.pay_code, pp.pay_date, pp.currency, pp.exchange_rate, pp.amount,
                   pp.amount_invoice_currency, pp.fx_diff, pi.inv_code
            FROM provider_payments pp
            JOIN provider_invoices pi ON pi.id = pp.invoice_id
            WHERE pi.provider_id = ? AND COALESCE(pp.deleted,0)=0
            ORDER BY pp.pay_date ASC, pp.id ASC
        ", array($providerId))->result();

        $curCount = array();
        foreach ($invoices as $i) { $curCount[$i->currency] = ($curCount[$i->currency] ?? 0) + 1; }
        arsort($curCount);
        $nativeCur = key($curCount) ?: 'COP';

        $totFact = 0; $totPag = 0; $balNativo = 0; $balBase = 0;
        foreach ($invoices as $i) {
            $totFact += (float) $i->total;
            $balNativo += (float) $i->balance;
            $balBase += (float) $i->balance * ($i->currency === 'COP' ? 1 : (float) $i->exchange_rate);
        }
        foreach ($payments as $p) { $totPag += (float) $p->amount_invoice_currency; }

        $this->load->view('sisvent/purchases/provider_invoices/statement', array(
            'role'      => $role,
            'provider'  => $provider,
            'invoices'  => $invoices,
            'payments'  => $payments,
            'nativeCur' => $nativeCur,
            'totFact'   => $totFact,
            'totPag'    => $totPag,
            'balNativo' => $balNativo,
            'balBase'   => $balBase,
        ));
    }

    /** Formulario para cargar un packing list (xlsx) de proveedor. */
    public function import(): void
    {
        $role = $this->_guard();
        $this->load->view('sisvent/purchases/provider_invoices/import', array(
            'providers'          => $this->providers_model->getProviders(),
            'role'               => $role,
            'preset_provider_id' => (int) $this->input->get('provider_id'),
        ));
    }

    /** POST — parsea el xlsx, matchea SKUs y muestra la revisión. */
    public function import_review(): void
    {
        $role = $this->_guard();
        $providerId = (int) $this->input->post('provider_id');
        // TRM = pesos por unidad de la moneda del proveedor (ej. COP por RMB)
        $trm = (float) str_replace(',', '.', (string) $this->input->post('trm')) ?: 1;
        if ($providerId <= 0 || empty($_FILES['packing']['tmp_name'])) {
            $this->session->set_flashdata('error', 'Faltan datos: proveedor o archivo.');
            redirect('sisvent/purchases/provider_invoices/import?provider_id=' . $providerId);
            return;
        }

        $tmp = $_FILES['packing']['tmp_name'];
        $parsed = $this->parsePackingList($tmp);
        if (isset($parsed['error']) || empty($parsed['items'])) {
            $this->session->set_flashdata('error', 'No se pudo leer el archivo: ' . ($parsed['error'] ?? 'sin líneas'));
            redirect('sisvent/purchases/provider_invoices/import?provider_id=' . $providerId);
            return;
        }

        $hdr = $this->parseInvoiceHeader($tmp);
        $finDays = 120; $provFinPct = 0;
        if ($this->db->field_exists('financing_days', 'providers')) {
            $prov = $this->db->query("SELECT financing_cost_pct, financing_days FROM providers WHERE idProvider = ? LIMIT 1", array($providerId))->row();
            if ($prov) {
                if ((int) $prov->financing_days > 0) $finDays = (int) $prov->financing_days;
                $provFinPct = (float) $prov->financing_cost_pct;
            }
        }
        $issue  = $hdr['issue_date'] ?: date('Y-m-d');
        $finPct = ($hdr['financing_pct'] !== null) ? (float) $hdr['financing_pct'] : $provFinPct;
        $noteParts = array();
        if (!empty($hdr['contract'])) $noteParts[] = 'Contenedor ' . $hdr['contract'];
        if (!empty($hdr['from']) || !empty($hdr['to'])) $noteParts[] = trim(($hdr['from'] ?? '') . ' → ' . ($hdr['to'] ?? ''), ' →');
        if (!empty($hdr['terms'])) $noteParts[] = 'Términos ' . $hdr['terms'];
        $header = array(
            'inv_code'      => $hdr['inv_code'] ?: '',
            'issue_date'    => $issue,
            'due_date'      => date('Y-m-d', strtotime($issue . ' +' . $finDays . ' days')),
            'trm'           => $trm,
            'financing_pct' => $finPct,
            'notes'         => implode(' · ', $noteParts),
        );

        $map = array();
        foreach ($this->db->query("SELECT provider_sku, product_id FROM provider_product_map WHERE provider_id = ?", array($providerId))->result() as $m) {
            $map[strtoupper($m->provider_sku)] = $m->product_id;
        }
        $skus = array_map(function ($it) { return $it['sku']; }, $parsed['items']);
        $existing = array();
        if ($skus) {
            $ph = implode(',', array_fill(0, count($skus), '?'));
            foreach ($this->db->query("SELECT idProduct, description FROM products WHERE idProduct IN ($ph) AND COALESCE(deleted,0)=0", $skus)->result() as $p) {
                $existing[strtoupper($p->idProduct)] = $p->description;
            }
        }
        foreach ($parsed['items'] as &$it) {
            $sku = strtoupper($it['sku']);
            if (isset($map[$sku])) { $it['match'] = 'mapeado'; $it['product_id'] = $map[$sku]; }
            elseif (isset($existing[$sku])) { $it['match'] = 'catálogo'; $it['product_id'] = $it['sku']; $it['erp_name'] = $existing[$sku]; }
            else { $it['match'] = 'nuevo'; $it['product_id'] = $it['sku']; $it['erp_name'] = $it['desc']; }
        }
        unset($it);

        $this->load->view('sisvent/purchases/provider_invoices/import_review', array(
            'role'       => $role,
            'providerId' => $providerId,
            'provider'   => $this->providers_model->getProvider($providerId),
            'header'     => $header,
            'items'      => $parsed['items'],
            'totals'     => $parsed['totals'],
        ));
    }

    /** POST — crea productos nuevos + mapeo SKU + factura EN TRÁNSITO (RMB). */
    public function import_save(): void
    {
        $role   = $this->_guard();
        $userId = $this->session->userdata('user_data')['uname'] ?? null;
        $providerId = (int) $this->input->post('provider_id');
        $invCode    = trim((string) $this->input->post('inv_code'));
        $issueDate  = $this->input->post('issue_date');
        $dueDate    = $this->input->post('due_date') ?: null;
        $trm        = (float) str_replace(',', '.', (string) $this->input->post('trm')) ?: 1; // pesos por RMB
        $notes      = trim((string) $this->input->post('notes')) ?: null;

        $skuArr  = (array) $this->input->post('sku');
        $refArr  = (array) $this->input->post('ref');
        $descArr = (array) $this->input->post('desc');
        $qtyArr  = (array) $this->input->post('qty');
        $rmbArr  = (array) $this->input->post('rmb_pc');
        $cbmArr  = (array) $this->input->post('cbm');
        $pidArr  = (array) $this->input->post('product_id');
        $nameArr = (array) $this->input->post('erp_name');
        $rows = array();
        foreach ($skuArr as $i => $s) {
            $rows[] = array(
                'sku'        => $s,
                'ref'        => $refArr[$i] ?? null,
                'desc'       => $descArr[$i] ?? '',
                'qty'        => $qtyArr[$i] ?? 0,
                'rmb_pc'     => $rmbArr[$i] ?? 0,
                'cbm'        => $cbmArr[$i] ?? 0,
                'product_id' => $pidArr[$i] ?? '',
                'erp_name'   => $nameArr[$i] ?? '',
            );
        }
        if ($providerId <= 0 || $invCode === '' || !$rows) {
            $this->session->set_flashdata('error', 'Datos incompletos para guardar.');
            redirect('sisvent/purchases/provider_invoices/import?provider_id=' . $providerId);
            return;
        }

        $this->db->trans_start();
        $items = array();
        foreach ($rows as $r) {
            $sku       = trim((string) ($r['sku'] ?? ''));
            $productId = trim((string) ($r['product_id'] ?? ''));
            $erpName   = trim((string) ($r['erp_name'] ?? $r['desc'] ?? ''));
            $qty       = (float) ($r['qty'] ?? 0);
            $rmb       = (float) ($r['rmb_pc'] ?? 0);
            if ($productId === '' || $qty <= 0) continue;

            $exists = $this->db->query("SELECT 1 FROM products WHERE idProduct = ? LIMIT 1", array($productId))->row();
            if (!$exists) {
                $costCop = round($rmb * $trm, 2);
                $this->db->insert('products', array(
                    'idProduct'   => $productId,
                    'description' => $erpName !== '' ? $erpName : $sku,
                    'cost'        => $costCop,
                    'cost_cop'    => $costCop,
                    'cost_rmb'    => $rmb,
                    'deleted'     => 0,
                    'created_at'  => date('Y-m-d H:i:s'),
                ));
            }
            if ($sku !== '') {
                $this->db->query("
                    INSERT INTO provider_product_map (provider_id, provider_sku, provider_ref, product_id, created_by, created_at)
                    VALUES (?,?,?,?,?,NOW())
                    ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), provider_ref = VALUES(provider_ref)
                ", array($providerId, $sku, ($r['ref'] ?? null), $productId, $userId));
            }
            $items[] = array(
                'product_id'  => $productId,
                'description' => $erpName !== '' ? $erpName : $sku,
                'quantity'    => $qty,
                'unit_cost'   => $rmb,
                'total'       => round($qty * $rmb, 2),
                'cbm'         => (float) ($r['cbm'] ?? 0),
            );
        }

        $finPct = $this->input->post('financing_pct');
        if ($finPct === null || trim((string) $finPct) === '') {
            $finPct = 0;
            if ($this->db->field_exists('financing_cost_pct', 'providers')) {
                $prov = $this->db->query("SELECT financing_cost_pct FROM providers WHERE idProvider = ? LIMIT 1", array($providerId))->row();
                $finPct = $prov ? (float) $prov->financing_cost_pct : 0;
            }
        } else {
            $finPct = (float) str_replace(',', '.', (string) $finPct);
        }

        // Factura EN TRÁNSITO — moneda CNY. exchange_rate = pesos por RMB (la TRM).
        $subtotal = 0; foreach ($items as $it) $subtotal += $it['total'];
        $total = round($subtotal * (1 + $finPct / 100), 2);
        $invId = $this->cxp_model->createTransitInvoice(array(
            'inv_code'      => $invCode,
            'provider_id'   => $providerId,
            'issue_date'    => $issueDate,
            'due_date'      => $dueDate,
            'currency'      => 'CNY',
            'exchange_rate' => $trm,
            'subtotal'      => round($subtotal, 2),
            'total'         => $total,
            'financing_pct' => $finPct,
            'notes'         => $notes,
            'created_by'    => $userId,
        ), $items);

        $this->db->trans_complete();
        if ($this->db->trans_status() === false || !$invId) {
            $this->session->set_flashdata('error', 'Error al guardar la factura en tránsito. Transacción revertida.');
            redirect('sisvent/purchases/provider_invoices/import?provider_id=' . $providerId);
            return;
        }
        $this->session->set_flashdata('success', 'Factura EN TRÁNSITO creada · ' . htmlspecialchars($invCode) . ' · ' . count($items) . ' ítem(s). Genera CxP al recibir.');
        redirect('sisvent/purchases/provider_invoices/view/' . $invId);
    }
    private function parsePackingList(string $file): array
    {
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $ss = $reader->load($file);
        } catch (\Throwable $t) {
            return ['error' => $t->getMessage()];
        }

        $amountsRows = null; $amountsHdr = -1; $amountsCol = null; $cbmMap = [];
        foreach ($ss->getAllSheets() as $sheet) {
            $rows = $sheet->toArray(null, true, true, false);
            $hdrIdx = -1;
            foreach ($rows as $i => $r) {
                foreach ($r as $c) { $n = mb_strtolower(trim((string)$c)); if ($n === 'sku' || $n === 'code') { $hdrIdx = $i; break 2; } }
            }
            if ($hdrIdx < 0) continue;
            $col = $this->mapPackingCols($rows[$hdrIdx], $hdrIdx > 0 ? $rows[$hdrIdx - 1] : []);
            // Mapa código→CBM (de cualquier hoja que lo traiga).
            if (isset($col['code']) && isset($col['cbm'])) {
                for ($i = $hdrIdx + 1; $i < count($rows); $i++) {
                    $code = trim((string)($rows[$i][$col['code']] ?? ''));
                    if ($code === '' || stripos($code, 'sku') !== false || stripos($code, 'code') !== false) continue;
                    $cbm = (float) $rows[$i][$col['cbm']];
                    if ($cbm > 0) $cbmMap[strtoupper($code)] = $cbm;
                }
            }
            // Hoja de montos = la que tenga precio o importe.
            if ($amountsRows === null && (isset($col['rmbpc']) || isset($col['amount']))) {
                $amountsRows = $rows; $amountsHdr = $hdrIdx; $amountsCol = $col;
            }
        }
        if ($amountsRows === null) return ['error' => 'No se encontró una hoja con precios (SKU/Code + RMB/Amount)'];

        $col = $amountsCol; $rows = $amountsRows;
        $items = []; $tCtns=0; $tQty=0; $tAmount=0; $tCbm=0;
        for ($i = $amountsHdr + 1; $i < count($rows); $i++) {
            $r = $rows[$i];
            $sku = trim((string)($r[$col['code']] ?? ''));
            if ($sku === '' || stripos($sku, 'sku') !== false || stripos($sku, 'code') !== false) continue;
            $qty = isset($col['qty']) ? (float)$r[$col['qty']] : 0;
            $rmb = isset($col['rmbpc']) ? (float)$r[$col['rmbpc']] : 0;
            $amt = isset($col['amount']) ? (float)$r[$col['amount']] : round($qty*$rmb, 2);
            if ($qty <= 0 && $amt <= 0) continue; // saltar filas de total / vacías
            $desc = isset($col['desc']) ? trim(preg_replace('/\s+/', ' ', (string)$r[$col['desc']])) : '';
            $ctns = isset($col['ctns']) ? (float)$r[$col['ctns']] : 0;
            $cbm  = isset($col['cbm']) ? (float)$r[$col['cbm']] : ($cbmMap[strtoupper($sku)] ?? 0);
            $items[] = [
                'sku' => $sku, 'ref' => isset($col['ref']) ? trim((string)$r[$col['ref']]) : '',
                'desc' => mb_substr($desc, 0, 120), 'ctns' => $ctns, 'qty' => $qty,
                'rmb_pc' => $rmb, 'amount' => $amt, 'cbm' => $cbm,
            ];
            $tCtns += $ctns; $tQty += $qty; $tAmount += $amt; $tCbm += $cbm;
        }
        return ['items' => $items, 'totals' => ['ctns'=>$tCtns,'qty'=>$tQty,'amount'=>round($tAmount,2),'cbm'=>round($tCbm,3)]];
    }

    /**
     * Extrae metadata del encabezado de la factura/proforma del proveedor:
     * Nº factura, fecha, contenedor/contrato, puertos (from/to), términos y
     * el % de financiación (línea "N%"). Escanea las primeras filas por
     * keywords, tolerando label:valor en la misma celda o en la siguiente.
     */
    private function parseInvoiceHeader(string $file): array
    {
        $out = ['inv_code'=>null,'issue_date'=>null,'contract'=>null,'from'=>null,'to'=>null,'terms'=>null,'financing_pct'=>null];
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $ss = $reader->load($file);
        } catch (\Throwable $t) {
            return $out;
        }
        foreach ($ss->getAllSheets() as $sheet) {
            $rows = $sheet->toArray(null, true, true, false);
            for ($i = 0; $i < min(28, count($rows)); $i++) {
                $r = $rows[$i];
                for ($j = 0; $j < count($r); $j++) {
                    $cell = trim((string)($r[$j] ?? ''));
                    if ($cell === '') continue;
                    $low   = mb_strtolower($cell);
                    $after = (strpos($cell, ':') !== false) ? trim(substr($cell, strpos($cell, ':') + 1)) : '';
                    $after = $after !== '' ? $after : null;
                    $next  = null; for ($k=$j+1;$k<count($r);$k++){ $v=trim((string)($r[$k]??'')); if($v!==''){ $next=$v; break; } }

                    if ($out['inv_code']===null && preg_match('/inv\.?\s*no/i',$low)) $out['inv_code'] = $after ?: $next;
                    if ($out['contract']===null && preg_match('/contract\s*no/i',$low)) $out['contract'] = $after ?: $next;
                    if ($out['terms']===null && preg_match('/terms\s*of\s*payment/i',$low)) $out['terms'] = $after ?: $next;
                    if ($out['issue_date']===null && strpos($low,'date')!==false) {
                        $dv = $after ?: $next;
                        if ($dv !== null && is_numeric($dv)) {
                            try { $out['issue_date'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$dv)->format('Y-m-d'); } catch (\Throwable $e) {}
                        }
                    }
                    if (($out['from']===null || $out['to']===null) && preg_match('/from:\s*(.+?)\s+to:\s*(.+)$/i',$cell,$m)) {
                        $out['from'] = trim($m[1]); $out['to'] = trim($m[2]);
                    }
                    if ($out['financing_pct']===null && preg_match('/^(\d{1,2}(?:[.,]\d+)?)\s*%/',$cell,$m)) {
                        $out['financing_pct'] = (float) str_replace(',', '.', $m[1]);
                    }
                }
            }
        }
        return $out;
    }

    /** Mapea columnas por keyword combinando la fila de encabezado y la de arriba (encabezados en 2 filas). */
    private function mapPackingCols(array $hdr, array $above): array
    {
        $col = [];
        $n = max(count($hdr), count($above));
        for ($idx = 0; $idx < $n; $idx++) {
            $lab = mb_strtolower(trim((string)($above[$idx] ?? '') . ' ' . (string)($hdr[$idx] ?? '')));
            if ($lab === '') continue;
            if (!isset($col['code'])   && (preg_match('/\bsku\b/', $lab) || preg_match('/\bcode\b/', $lab))) $col['code'] = $idx;
            elseif (!isset($col['ref'])    && (strpos($lab,'ref')!==false || strpos($lab,'fty')!==false)) $col['ref'] = $idx;
            elseif (!isset($col['desc'])   && (strpos($lab,'descrip')!==false || strpos($lab,'description')!==false)) $col['desc'] = $idx;
            elseif (!isset($col['ctns'])   && ($lab==='orden' || strpos($lab,'ctns')!==false)) $col['ctns'] = $idx;
            elseif (!isset($col['pcsctn']) && (strpos($lab,'pcs/ctn')!==false || strpos($lab,'pzas')!==false || strpos($lab,'pcs por')!==false)) $col['pcsctn'] = $idx;
            elseif (!isset($col['qty'])    && (strpos($lab,'cantidad')!==false || strpos($lab,'quantity')!==false || preg_match('/\bqty\b/',$lab) || preg_match('/\bpcs\b/',$lab))) $col['qty'] = $idx;
            elseif (!isset($col['rmbpc'])  && (strpos($lab,'rmb/pc')!==false || strpos($lab,'rmb/')!==false || strpos($lab,'rmb ')!==false)) $col['rmbpc'] = $idx;
            elseif (!isset($col['amount']) && ($lab==='monto' || strpos($lab,'amount')!==false || strpos($lab,'fob')!==false)) $col['amount'] = $idx;
            elseif (!isset($col['cbm'])    && (strpos($lab,'ttl cbm')!==false || strpos($lab,'tt cbm')!==false || strpos($lab,'total cbm')!==false)) $col['cbm'] = $idx;
        }
        return $col;
    }


    public function save(): void
    {
        $role = $this->_guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('sisvent/purchases/provider_invoices');
            return;
        }

        $userId = $this->session->userdata('user_data')['uname'] ?? null;

        $itemsRaw = (string) $this->input->post('items_json');
        $items = array();
        $itemsSubtotal = 0;
        if ($itemsRaw !== '') {
            $decoded = json_decode($itemsRaw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $it) {
                    $pid  = trim((string) ($it['product_id'] ?? ''));
                    $qty  = (float) ($it['quantity'] ?? 0);
                    $cost = (float) ($it['unit_cost'] ?? 0);
                    if ($pid === '' || $qty <= 0) continue;
                    $line = round($qty * $cost, 2);
                    $items[] = array(
                        'product_id'  => $pid,
                        'description' => (string) ($it['description'] ?? ''),
                        'quantity'    => $qty,
                        'unit_cost'   => $cost,
                        'total'       => $line,
                    );
                    $itemsSubtotal += $line;
                }
            }
        }

        $postedSubtotal = (float) str_replace(',', '.', (string) $this->input->post('subtotal'));
        $finalSubtotal  = !empty($items) ? round($itemsSubtotal, 2) : $postedSubtotal;

        $data = array(
            'inv_code'      => trim((string) $this->input->post('inv_code')),
            'provider_id'   => (int) $this->input->post('provider_id'),
            'issue_date'    => $this->input->post('issue_date'),
            'due_date'      => $this->input->post('due_date') ?: null,
            'currency'      => $this->input->post('currency') ?: 'COP',
            'exchange_rate' => (float) str_replace(',', '.', (string) $this->input->post('exchange_rate')) ?: 1,
            'subtotal'      => $finalSubtotal,
            'tax'           => (float) str_replace(',', '.', (string) $this->input->post('tax')),
            'withholding'   => (float) str_replace(',', '.', (string) $this->input->post('withholding')),
            'total'         => (float) str_replace(',', '.', (string) $this->input->post('total')),
            'notes'         => trim((string) $this->input->post('notes')) ?: null,
            'created_by'    => $userId,
        );

        $editId = (int) $this->input->post('id');

        if ($data['inv_code'] === '' || $data['provider_id'] <= 0 || $data['issue_date'] === '' || $data['total'] <= 0) {
            $this->session->set_flashdata('error', 'Faltan campos obligatorios: número, proveedor, fecha de emisión, total.');
            redirect('sisvent/purchases/provider_invoices/' . ($editId > 0 ? 'edit/' . $editId : 'add'));
            return;
        }

        // Crear EN TRÁNSITO (manual): DR Mercancía en Tránsito / CR CxP.
        if ($editId <= 0 && $this->input->post('in_transit')) {
            $finPct = $this->input->post('financing_pct');
            if ($finPct === null || trim((string) $finPct) === '') {
                $finPct = 0;
                if ($this->db->field_exists('financing_cost_pct', 'providers')) {
                    $prov = $this->db->query("SELECT financing_cost_pct FROM providers WHERE idProvider = ? LIMIT 1", array($data['provider_id']))->row();
                    $finPct = $prov ? (float) $prov->financing_cost_pct : 0;
                }
            } else {
                $finPct = (float) str_replace(',', '.', (string) $finPct);
            }
            $base = 0; foreach ($items as $it) $base += (float) $it['total'];
            if ($base <= 0) $base = (float) $data['subtotal'];
            $data['financing_pct'] = $finPct;
            $data['subtotal']      = round($base, 2);
            $data['total']         = round($base * (1 + $finPct / 100), 2);
            $id = $this->cxp_model->createTransitInvoice($data, $items);
            $this->session->set_flashdata('success', 'Factura EN TRÁNSITO creada · ' . htmlspecialchars($data['inv_code']) . ' · ' . count($items) . ' ítem(s). Entra a inventario al recibir.');
            redirect('sisvent/purchases/provider_invoices/view/' . $id);
            return;
        }

        if ($editId > 0) {
            $existing = $this->cxp_model->getInvoice($editId);
            if (!$existing) {
                $this->session->set_flashdata('error', 'Factura no encontrada.');
                redirect('sisvent/purchases/provider_invoices');
                return;
            }
            if ((int) ($existing->cash_payments ?? 0) > 0) {
                $this->session->set_flashdata('error', 'No se puede editar: la factura tiene pagos desde caja/banco. Anula los pagos primero.');
                redirect('sisvent/purchases/provider_invoices/view/' . $editId);
                return;
            }
            if (!empty($existing->received_at)) {
                $this->session->set_flashdata('error', 'No se puede editar: la factura ya fue recibida (los productos entraron al inventario).');
                redirect('sisvent/purchases/provider_invoices/view/' . $editId);
                return;
            }
            $this->cxp_model->updateInvoice($editId, $data);
            $this->db->where('provider_invoice_id', $editId)->delete('provider_invoice_items');
            $id = $editId;
        } else {
            $id = $this->cxp_model->createInvoice($data);
            // createInvoice ya insertó items si venían en $data['items'] — acá
            // los pasamos por separado para conservar el flujo del formulario.
        }

        // Persistir líneas + actualizar el costo del producto (en pesos)
        $costsUpdated = 0;
        if ($id && !empty($items)) {
            $cur  = strtoupper((string) $data['currency']);
            $rate = (float) $data['exchange_rate'] ?: 1;
            foreach ($items as $it) {
                $it['provider_invoice_id'] = $id;
                if ($editId > 0 || empty($this->input->post('in_transit'))) {
                    // en creación normal createInvoice no recibió items; insertarlos acá
                    $yaExiste = $this->db->query("SELECT 1 FROM provider_invoice_items WHERE provider_invoice_id = ? AND product_id = ? AND quantity = ? LIMIT 1",
                        array($id, $it['product_id'], $it['quantity']))->row();
                    if (!$yaExiste) $this->db->insert('provider_invoice_items', $it);
                }

                // Actualiza el costo del producto: cost_cop es la fuente real de
                // costos en Ledxury (COALESCE(cost_cop, cost)).
                if ((float) $it['unit_cost'] > 0) {
                    $costBase = $cur === 'COP' ? (float) $it['unit_cost'] : round((float) $it['unit_cost'] * $rate, 2);
                    if ($costBase > 0) {
                        $upd = array('cost' => $costBase, 'cost_cop' => $costBase, 'updated_at' => date('Y-m-d H:i:s'));
                        if ($cur === 'CNY') $upd['cost_rmb'] = (float) $it['unit_cost'];
                        $this->db->where('idProduct', $it['product_id']);
                        $this->db->update('products', $upd);
                        $costsUpdated++;
                    }
                }
            }
        }

        $msg = ($editId > 0 ? 'Factura actualizada · ' : 'Factura cargada · ') . htmlspecialchars($data['inv_code']);
        if (count($items)) $msg .= ' · ' . count($items) . ' ítem(s)';
        if ($costsUpdated)  $msg .= ' · costo actualizado en ' . $costsUpdated . ' producto(s)';
        $this->session->set_flashdata('success', $msg);
        redirect('sisvent/purchases/provider_invoices/view/' . $id);
    }

    /** Soft-delete. Bloquea si tiene pagos o si ya fue recibida. */
    public function delete($id): void
    {
        $this->_guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('sisvent/purchases/provider_invoices');
            return;
        }
        $id = (int) $id;
        $invoice = $this->cxp_model->getInvoice($id);
        if (!$invoice) {
            $this->session->set_flashdata('error', 'Factura no encontrada.');
            redirect('sisvent/purchases/provider_invoices');
            return;
        }
        if ((float) $invoice->paid > 0.01) {
            $this->session->set_flashdata('error', 'No se puede eliminar: la factura tiene pagos registrados. Anula primero los pagos.');
            redirect('sisvent/purchases/provider_invoices/view/' . $id);
            return;
        }
        if (!empty($invoice->received_at)) {
            $this->session->set_flashdata('error', 'No se puede eliminar: la factura ya fue recibida (los productos entraron al inventario). Usa "Revertir recepción" primero.');
            redirect('sisvent/purchases/provider_invoices/view/' . $id);
            return;
        }

        $this->db->trans_start();
        $this->db->where('id', $id)->update('provider_invoices', array(
            'deleted'    => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        // Reversar los asientos vivos de la factura (CxP/tránsito/recepción).
        $anulados = acc_void_entries($id, array(
            'provider_invoice', 'provider_invoice_transit', 'provider_invoice_receipt', 'import_cost',
        ), 'reverso por eliminación de factura proveedor');
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            $this->session->set_flashdata('error', 'Error al eliminar. Transacción revertida.');
            redirect('sisvent/purchases/provider_invoices/view/' . $id);
            return;
        }
        $this->session->set_flashdata('success', 'Factura eliminada · ' . htmlspecialchars($invoice->inv_code)
            . ($anulados > 0 ? ' · ' . $anulados . ' asiento(s) contable(s) reversado(s)' : ''));
        redirect('sisvent/purchases/provider_invoices');
    }

    /**
     * POST /receive/<id> (body: store_id, price[<item_id>] opcional)
     * Suma los ítems al inventario, capitaliza landed cost al costo del
     * producto, y reclasifica tránsito → inventario si aplica.
     */
    public function receive($id): void
    {
        $role = $this->_guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('sisvent/purchases/provider_invoices/view/' . (int) $id);
            return;
        }
        $id = (int) $id;
        $userId = $this->session->userdata('user_data')['uname'] ?? null;
        $storeId = (int) $this->input->post('store_id');

        $invoice = $this->cxp_model->getInvoice($id);
        if (!$invoice) {
            $this->session->set_flashdata('error', 'Factura no encontrada.');
            redirect('sisvent/purchases/provider_invoices');
            return;
        }
        if (!empty($invoice->received_at)) {
            $this->session->set_flashdata('error', 'Esta factura ya fue recibida el ' . $invoice->received_at . '.');
            redirect('sisvent/purchases/provider_invoices/view/' . $id);
            return;
        }
        if ($storeId <= 0) {
            $this->session->set_flashdata('error', 'Selecciona una bodega de destino antes de recibir.');
            redirect('sisvent/purchases/provider_invoices/view/' . $id);
            return;
        }

        $items = $this->db->query("
            SELECT pii.*, p.cost_cop AS cur_cost, p.price AS cur_price
            FROM provider_invoice_items pii
            LEFT JOIN products p ON p.idProduct = pii.product_id
            WHERE pii.provider_invoice_id = ?
        ", array($id))->result();

        if (empty($items)) {
            $this->session->set_flashdata('error', 'La factura no tiene artículos para recibir.');
            redirect('sisvent/purchases/provider_invoices/view/' . $id);
            return;
        }

        // Landed cost (gastos de importación ya registrados), en pesos.
        $b = $this->_importBuckets($id);
        $customs = $b['customs']; $freight = $b['freight'];
        $landedTotal = $customs + $freight;

        $prices = $this->input->post('price');
        if (!is_array($prices)) $prices = array();

        $cur  = strtoupper((string) $invoice->currency);
        $rate = (float) $invoice->exchange_rate ?: 1;
        $toBase = function ($v) use ($cur, $rate) { return $cur === 'COP' ? (float) $v : round((float) $v * $rate, 6); };

        $unitCosts = $this->_landedUnitCosts($invoice, $items, $customs, $freight);
        $isTransit = ($invoice->status === 'en_transito');

        $this->db->trans_start();

        $count = 0;
        foreach ($items as $it) {
            $qty = (float) $it->quantity;
            if ($qty <= 0) continue;

            $existing = $this->db->get_where('inventory', array('idStore' => $storeId, 'idProduct' => $it->product_id))->row();
            if ($existing) {
                $this->db->where('idStore', $storeId)->where('idProduct', $it->product_id)
                    ->update('inventory', array('stock' => (int) $existing->stock + (int) round($qty), 'updated_at' => date('Y-m-d H:i:s')));
            } else {
                $this->db->insert('inventory', array('idStore' => $storeId, 'idProduct' => $it->product_id, 'stock' => (int) round($qty), 'counted' => 0, 'updated_at' => date('Y-m-d H:i:s')));
            }

            // Snapshot para poder revertir la recepción.
            $this->db->where('id', $it->id)->update('provider_invoice_items', array(
                'prev_cost'  => isset($it->cur_cost)  ? (float) $it->cur_cost  : null,
                'prev_price' => isset($it->cur_price) ? (float) $it->cur_price : null,
            ));

            // Costo nacionalizado en pesos → cost_cop (la fuente real de costos).
            $costBase = round((float) ($unitCosts[(int) $it->id] ?? 0), 2);
            $upd = array('cost' => $costBase, 'cost_cop' => $costBase, 'updated_at' => date('Y-m-d H:i:s'));
            if ($cur === 'CNY') $upd['cost_rmb'] = (float) $it->unit_cost;
            $chosen = isset($prices[$it->id]) ? (float) str_replace(',', '.', (string) $prices[$it->id]) : 0;
            if ($chosen > 0) $upd['price'] = round($chosen, 2);
            $this->db->where('idProduct', $it->product_id)->update('products', $upd);
            $count++;
        }

        // Reclasificación contable tránsito → inventario. En Ledxury hoy ambas
        // llaves apuntan a la misma subcuenta (1435): si son iguales, el
        // asiento sería DR X / CR X y se omite sin perder nada.
        $merchBase   = $toBase((float) $invoice->total);
        $transitBase = round($merchBase + $landedTotal, 2);
        $invAcc = acc_setting('account_inventory');
        $trAcc  = acc_setting('account_inventory_transit') ?: $invAcc;
        if ($isTransit && $transitBase > 0 && $invAcc && $trAcc && $invAcc !== $trAcc) {
            $desc = 'Recepción factura ' . $invoice->inv_code . ' · ' . $invoice->provider_name . ' (de tránsito a inventario';
            if ($landedTotal > 0) $desc .= ', incl. costos de importación ' . money($landedTotal);
            $desc .= ')';
            acc_entry(array(
                'description'      => $desc,
                'date'             => date('Y-m-d'),
                'transaction_type' => 'provider_invoice_receipt',
                'transaction_id'   => $id,
                'debit'            => $invAcc,
                'credit'           => $trAcc,
                'amount'           => $transitBase,
                'user'             => $userId,
            ));
        }

        $updInv = array(
            'received_at' => date('Y-m-d H:i:s'), 'received_by' => $userId, 'received_store_id' => $storeId,
            'import_freight' => $freight, 'import_customs' => $customs, 'import_nationalization' => 0,
            'landed_total' => round($merchBase + $landedTotal, 2), 'updated_at' => date('Y-m-d H:i:s'),
        );
        if ($isTransit) $updInv['status'] = 'open';
        $this->db->where('id', $id)->update('provider_invoices', $updInv);

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            $this->session->set_flashdata('error', 'Error al recibir. Transacción revertida.');
        } else {
            $msg = $count . ' ítem(s) al inventario.';
            if ($landedTotal > 0) $msg .= ' Costos de importación ' . money($landedTotal) . ' prorrateados al costo.';
            $this->session->set_flashdata('success', $msg);
        }
        redirect('sisvent/purchases/provider_invoices/view/' . $id);
    }

    /**
     * Revierte una recepción: saca el stock, restaura costo/precio previos,
     * reversa el asiento de reclasificación y vuelve la factura a EN TRÁNSITO.
     * POST /unreceive/<id>
     */
    public function unreceive($id): void
    {
        $this->_guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('sisvent/purchases/provider_invoices/view/' . (int) $id);
            return;
        }
        $id = (int) $id;

        $invoice = $this->cxp_model->getInvoice($id);
        if (!$invoice) {
            $this->session->set_flashdata('error', 'Factura no encontrada.');
            redirect('sisvent/purchases/provider_invoices');
            return;
        }
        if (empty($invoice->received_at)) {
            $this->session->set_flashdata('error', 'Esta factura no está recibida.');
            redirect('sisvent/purchases/provider_invoices/view/' . $id);
            return;
        }

        $items   = $this->db->query("SELECT * FROM provider_invoice_items WHERE provider_invoice_id = ?", array($id))->result();
        $storeId = (int) $invoice->received_store_id;

        // ¿Era en tránsito? El asiento de tránsito vivo lo delata (la reclasificación
        // puede no existir si tránsito e inventario comparten subcuenta).
        $wasTransit = (int) $this->db->query("SELECT COUNT(*) AS n FROM entries WHERE entryTransactionType = 'provider_invoice_transit' AND entryTransactionId = ? AND COALESCE(deleted,0)=0", array($id))->row()->n > 0;

        $this->db->trans_start();

        foreach ($items as $it) {
            $qty = (int) round((float) $it->quantity);
            // Se PERMITE stock negativo: si ya se vendió parte, el negativo
            // hace visible el error para corregirlo.
            if ($storeId > 0 && $qty > 0) {
                $this->db->query("UPDATE inventory SET stock = stock - ?, updated_at = ? WHERE idStore = ? AND idProduct = ?",
                    array($qty, date('Y-m-d H:i:s'), $storeId, $it->product_id));
            }
            $upd = array();
            if (isset($it->prev_cost)  && $it->prev_cost  !== null) { $upd['cost'] = (float) $it->prev_cost; $upd['cost_cop'] = (float) $it->prev_cost; }
            if (isset($it->prev_price) && $it->prev_price !== null) { $upd['price'] = (float) $it->prev_price; }
            if (!empty($upd)) { $upd['updated_at'] = date('Y-m-d H:i:s'); $this->db->where('idProduct', $it->product_id)->update('products', $upd); }
        }

        acc_void_entries($id, array('provider_invoice_receipt'), 'reverso recepción');

        $updInv = array(
            'received_at' => null, 'received_by' => null, 'received_store_id' => null,
            'landed_total' => 0, 'import_customs' => 0, 'import_freight' => 0, 'import_nationalization' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        );
        if ($wasTransit) $updInv['status'] = 'en_transito';
        $this->db->where('id', $id)->update('provider_invoices', $updInv);

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            $this->session->set_flashdata('error', 'Error al revertir la recepción. Transacción revertida.');
        } else {
            $this->session->set_flashdata('success', 'Recepción revertida · stock retirado y costo/precio restaurados. Corrige y vuelve a recibir.');
        }
        redirect('sisvent/purchases/provider_invoices/view/' . $id);
    }

    /** Conceptos de gasto de importación y su base de prorrateo por defecto. */
    private function _importConcepts(): array
    {
        return array(
            'aduana'          => array('label' => 'Aduana',          'basis' => 'value'),
            'flete'           => array('label' => 'Flete',           'basis' => 'cbm'),
            'descargue'       => array('label' => 'Descargue',       'basis' => 'cbm'),
            'nacionalizacion' => array('label' => 'Nacionalización', 'basis' => 'value'),
            'otro'            => array('label' => 'Otro',            'basis' => 'value'),
        );
    }

    /** Suma de gastos de importación por base: 'value' → customs, 'cbm' → freight. */
    private function _importBuckets(int $invoiceId): array
    {
        $customs = 0.0; $freight = 0.0;
        if ($this->db->table_exists('provider_invoice_import_costs')) {
            $rows = $this->db->query("
                SELECT alloc_basis, SUM(amount_base) AS s
                FROM provider_invoice_import_costs
                WHERE provider_invoice_id = ? AND COALESCE(deleted,0)=0
                GROUP BY alloc_basis
            ", array($invoiceId))->result();
            foreach ($rows as $r) { if ($r->alloc_basis === 'cbm') $freight += (float) $r->s; else $customs += (float) $r->s; }
        }
        return array('customs' => $customs, 'freight' => $freight);
    }

    /**
     * Costo nacionalizado en pesos por unidad, por artículo:
     *   mercancía × (1 + financiación%) + landed/u.
     * Aduana se reparte por VALOR; flete/descargue por CBM (fallback valor).
     */
    private function _landedUnitCosts($invoice, array $items, float $customs, float $freight): array
    {
        $cur  = strtoupper((string) $invoice->currency);
        $rate = (float) $invoice->exchange_rate ?: 1;
        $toBase = function ($v) use ($cur, $rate) { return $cur === 'COP' ? (float) $v : round((float) $v * $rate, 6); };
        $finPct = (float) ($invoice->financing_pct ?? 0);

        $valueTotalBase = 0.0; $cbmTotal = 0.0;
        foreach ($items as $it) {
            $valueTotalBase += $toBase((float) $it->quantity * (float) $it->unit_cost);
            $cbmTotal       += (float) ($it->cbm ?? 0);
        }
        $out = array();
        foreach ($items as $it) {
            $qty = (float) $it->quantity;
            $itemValueBase = $toBase($qty * (float) $it->unit_cost);
            $itemCbm       = (float) ($it->cbm ?? 0);
            $customsAlloc = ($customs > 0 && $valueTotalBase > 0) ? $customs * ($itemValueBase / $valueTotalBase) : 0;
            $freightAlloc = ($freight > 0 && $cbmTotal > 0) ? $freight * ($itemCbm / $cbmTotal)
                          : (($freight > 0 && $valueTotalBase > 0) ? $freight * ($itemValueBase / $valueTotalBase) : 0);
            $landedUnit = $qty > 0 ? ($customsAlloc + $freightAlloc) / $qty : 0;
            $merchUnit  = $toBase((float) $it->unit_cost) * (1 + $finPct / 100);
            $out[(int) $it->id] = round($merchUnit + $landedUnit, 2);
        }
        return $out;
    }

    // ── Gastos de importación con pago desde caja/banco: FASE 2 ──────────────
    // El registro contable y el pago de aduana/flete/descargue por tesorería
    // se portan en la fase 2 del módulo. La estructura (tabla + prorrateo en
    // la recepción) ya queda lista.

    public function import_cost($id): void
    {
        $this->_guard();
        $this->session->set_flashdata('warning', 'Costos de importación: disponible en la fase 2 del módulo.');
        redirect('sisvent/purchases/provider_invoices/view/' . (int) $id);
    }

    public function edit_import_cost($costId): void
    {
        $this->_guard();
        $this->session->set_flashdata('warning', 'Costos de importación: disponible en la fase 2 del módulo.');
        redirect('sisvent/purchases/provider_invoices');
    }

    public function delete_import_cost($costId): void
    {
        $this->_guard();
        $this->session->set_flashdata('warning', 'Costos de importación: disponible en la fase 2 del módulo.');
        redirect('sisvent/purchases/provider_invoices');
    }

    public function pay_import_cost($costId): void
    {
        $this->_guard();
        $this->session->set_flashdata('warning', 'Costos de importación: disponible en la fase 2 del módulo.');
        redirect('sisvent/purchases/provider_invoices');
    }

    public function delete_import_cost_payment($payId): void
    {
        $this->_guard();
        $this->session->set_flashdata('warning', 'Costos de importación: disponible en la fase 2 del módulo.');
        redirect('sisvent/purchases/provider_invoices');
    }

    public function view($id): void
    {
        $role = $this->_guard();
        $invoice = $this->cxp_model->getInvoice((int) $id);
        if (!$invoice) {
            show_404();
            return;
        }
        $payments = $this->cxp_model->listPaymentsForInvoice((int) $id);
        $fuentes = $this->_fuentesPago();
        $items = $this->db->query("
            SELECT pii.*, p.description AS product_description,
                   p.price AS current_price, p.cost_cop AS current_cost
            FROM provider_invoice_items pii
            LEFT JOIN products p ON p.idProduct = pii.product_id
            WHERE pii.provider_invoice_id = ?
            ORDER BY pii.id ASC
        ", array((int) $id))->result();
        $stores = $this->stores_model->getStores();

        $advanceBalance = 0;
        if ($this->db->table_exists('provider_advances')) {
            $this->load->model('provider_advances_model');
            $advanceBalance = $this->provider_advances_model->getProviderBalance((int) $invoice->provider_id);
        }

        $importCosts = array();
        $importCostsTotal = 0.0;
        if ($this->db->table_exists('provider_invoice_import_costs')) {
            $importCosts = $this->db->query("
                SELECT ic.*
                FROM provider_invoice_import_costs ic
                WHERE ic.provider_invoice_id = ? AND COALESCE(ic.deleted,0) = 0
                ORDER BY ic.id ASC
            ", array((int) $id))->result();
            foreach ($importCosts as $c) {
                $importCostsTotal += (float) $c->amount_base;
                $c->payments    = array();
                $c->outstanding = round((float) $c->amount_base - (float) $c->paid_amount, 2);
                $c->account_name = null;
            }
        }

        if (empty($invoice->received_at) && !empty($items)) {
            $b = $this->_importBuckets((int) $id);
            $unitCosts = $this->_landedUnitCosts($invoice, $items, $b['customs'], $b['freight']);
            foreach ($items as $it) { $it->new_cost_base = (float) ($unitCosts[(int) $it->id] ?? 0); }
        }

        $this->load->view('sisvent/purchases/provider_invoices/view', array(
            'invoice'            => $invoice,
            'payments'           => $payments,
            'bancos'             => $fuentes['bancos'],
            'cajas'              => $fuentes['cajas'],
            'items'              => $items,
            'stores'             => $stores,
            'advance_balance'    => $advanceBalance,
            'import_costs'       => $importCosts,
            'import_costs_total' => $importCostsTotal,
            'price_factor'       => 2.1,
            'role'               => $role,
        ));
    }

    /** Aplica los anticipos abiertos del proveedor contra esta factura. */
    public function apply_advances($id): void
    {
        $this->_guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('sisvent/purchases/provider_invoices/view/' . $id); return; }
        if (!$this->db->table_exists('provider_advances')) { redirect('sisvent/purchases/provider_invoices/view/' . $id); return; }
        $this->load->model('provider_advances_model');
        $userId = $this->session->userdata('user_data')['uname'] ?? null;
        try {
            $applied = $this->provider_advances_model->applyToInvoice((int) $id, $userId);
            if ($applied > 0.01) {
                $this->session->set_flashdata('success', 'Anticipos aplicados · ' . money($applied));
            } else {
                $this->session->set_flashdata('warning', 'No había anticipos disponibles o la factura ya está saldada.');
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'No se pudieron aplicar los anticipos: ' . $e->getMessage());
        }
        redirect('sisvent/purchases/provider_invoices/view/' . $id);
    }
}
