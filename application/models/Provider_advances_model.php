<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Provider_advances_model — Anticipos a proveedores (modelo "bolsa").
 * Portado de stockaccessories.co (20/08/2026) y adaptado a Ledxury:
 * moneda base COP, tesorería por cajas/bancos (source_type/source_id).
 *
 * Un anticipo es plata que sale ANTES de que exista la factura (típico import
 * China: 30% al ordenar, 70% antes de embarcar). Cuando llega la factura, los
 * anticipos abiertos del proveedor se aplican FIFO contra ella.
 *
 *   amount_base    → valor del anticipo en pesos
 *   applied_amount → cuánto ya se consumió contra facturas
 */
class Provider_advances_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('compras');
        $this->load->model('cxp_model');
    }

    public function nextCode(): string
    {
        $row = $this->db->query("
            SELECT adv_code FROM provider_advances
            WHERE adv_code LIKE 'ANP%' ORDER BY id DESC LIMIT 1
        ")->row();
        $n = 1;
        if ($row && preg_match('/(\d+)$/', $row->adv_code, $m)) $n = (int) $m[1] + 1;
        return 'ANP' . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Registra un anticipo. Si viene source_type/source_id, genera el egreso
     * de tesorería (cash_movement + saldo).
     */
    public function createAdvance(array $data): int
    {
        $providerId = (int) $data['provider_id'];
        if (!$providerId) throw new Exception('Proveedor requerido.');
        $amount = (float) $data['amount'];
        if ($amount <= 0) throw new Exception('El monto debe ser mayor a 0.');

        $sourceType = !empty($data['source_type']) ? $data['source_type'] : null;
        $sourceId   = !empty($data['source_id']) ? (int) $data['source_id'] : null;
        if ($sourceType && !in_array($sourceType, array('caja', 'banco'), true)) {
            throw new Exception('Origen de tesorería inválido.');
        }

        $currency = ($sourceType) ? 'COP' : ($data['currency'] ?? 'COP');
        $rate = (float) ($data['exchange_rate'] ?? 1) ?: 1;
        $amountBase = $this->cxp_model->toBase($amount, $currency, $rate);
        $payDate = $data['pay_date'] ?? date('Y-m-d');
        $code = $this->nextCode();

        $provider = $this->db->get_where('providers', array('idProvider' => $providerId))->row();
        $provName = $provider ? $provider->name : ('Proveedor ' . $providerId);

        $this->db->trans_start();

        $movId = null;
        if ($sourceType && $sourceId) {
            $this->db->insert('cash_movements', array(
                'sourceType'     => $sourceType,
                'sourceId'       => $sourceId,
                'movementType'   => 'egreso',
                'amount'         => $amount,
                'concept'        => 'Anticipo a ' . $provName . ' (' . $code . ')',
                'category'       => 'anticipo_proveedor',
                'documentNumber' => $code,
                'movementDate'   => $payDate . ' ' . date('H:i:s'),
                'status'         => 'ejecutado',
                'referenceType'  => 'provider_advance',
                'created_by'     => $data['created_by'] ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ));
            $movId = (int) $this->db->insert_id();
            if ($sourceType === 'banco') {
                $this->db->query("UPDATE bank_accounts SET currentBalance = currentBalance - ? WHERE idBankAccount = ?", array($amount, $sourceId));
            } else {
                $this->db->query("UPDATE cashboxes SET currentBalance = currentBalance - ? WHERE idCashbox = ?", array($amount, $sourceId));
            }
        }

        $this->db->insert('provider_advances', array(
            'adv_code'         => $code,
            'provider_id'      => $providerId,
            'po_id'            => !empty($data['po_id']) ? (int) $data['po_id'] : null,
            'pay_date'         => $payDate,
            'currency'         => $currency,
            'exchange_rate'    => $rate,
            'amount'           => $amount,
            'amount_base'      => $amountBase,
            'applied_amount'   => 0,
            'payment_method'   => $data['payment_method'] ?? null,
            'source_type'      => $sourceType,
            'source_id'        => $sourceId,
            'cash_movement_id' => $movId,
            'reference'        => $data['reference'] ?? null,
            'status'           => 'open',
            'notes'            => $data['notes'] ?? null,
            'created_by'       => $data['created_by'] ?? null,
            'created_at'       => date('Y-m-d H:i:s'),
        ));
        $id = (int) $this->db->insert_id();
        if ($movId) {
            $this->db->where('idMovement', $movId)->update('cash_movements', array('referenceId' => $id));
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            throw new Exception('No se pudo registrar el anticipo.');
        }
        return $id;
    }

    public function getAdvance(int $id): ?object
    {
        $row = $this->db->query("
            SELECT a.*, p.name AS provider_name,
                   (a.amount_base - a.applied_amount) AS saldo_base
            FROM provider_advances a
            LEFT JOIN providers p ON p.idProvider = a.provider_id
            WHERE a.id = ? AND COALESCE(a.deleted,0)=0
        ", array($id))->row();
        return $row ?: null;
    }

    public function listAdvances(array $filters = array()): array
    {
        $where = array("COALESCE(a.deleted,0)=0");
        $bind = array();
        if (!empty($filters['provider_id'])) { $where[] = "a.provider_id = ?"; $bind[] = (int) $filters['provider_id']; }
        if (!empty($filters['status']))      { $where[] = "a.status = ?"; $bind[] = $filters['status']; }
        if (!empty($filters['date_from']))   { $where[] = "a.pay_date >= ?"; $bind[] = $filters['date_from']; }
        if (!empty($filters['date_to']))     { $where[] = "a.pay_date <= ?"; $bind[] = $filters['date_to']; }
        $w = implode(' AND ', $where);
        return $this->db->query("
            SELECT a.*, p.name AS provider_name,
                   CASE WHEN a.source_type = 'banco' THEN (SELECT ba.bankName FROM bank_accounts ba WHERE ba.idBankAccount = a.source_id)
                        WHEN a.source_type = 'caja'  THEN (SELECT cb.name FROM cashboxes cb WHERE cb.idCashbox = a.source_id)
                        ELSE NULL END AS cash_account_name,
                   (a.amount_base - a.applied_amount) AS saldo_base
            FROM provider_advances a
            LEFT JOIN providers p ON p.idProvider = a.provider_id
            WHERE $w
            ORDER BY a.pay_date DESC, a.id DESC
            LIMIT 500
        ", $bind)->result();
    }

    /** Saldo de anticipos abiertos de un proveedor, en pesos. */
    public function getProviderBalance(int $providerId): float
    {
        $row = $this->db->query("
            SELECT COALESCE(SUM(amount_base - applied_amount), 0) AS saldo
            FROM provider_advances
            WHERE provider_id = ? AND status='open' AND COALESCE(deleted,0)=0
        ", array($providerId))->row();
        return $row ? (float) $row->saldo : 0;
    }

    public function balancesByProvider(): array
    {
        return $this->db->query("
            SELECT a.provider_id, p.name AS provider_name,
                   SUM(a.amount_base - a.applied_amount) AS saldo_base,
                   COUNT(*) AS num_anticipos
            FROM provider_advances a
            LEFT JOIN providers p ON p.idProvider = a.provider_id
            WHERE a.status='open' AND COALESCE(a.deleted,0)=0
            GROUP BY a.provider_id, p.name
            HAVING saldo_base > 0.01
            ORDER BY saldo_base DESC
        ")->result();
    }

    /**
     * Aplica los anticipos abiertos del proveedor (FIFO) contra una factura.
     * Crea provider_payments SIN movimiento de tesorería (la plata ya salió),
     * registra la aplicación y actualiza el anticipo. Devuelve el total
     * aplicado en pesos.
     */
    public function applyToInvoice(int $invoiceId, ?string $userId = null): float
    {
        $inv = $this->cxp_model->getInvoice($invoiceId);
        if (!$inv) throw new Exception('Factura no existe.');

        $pending = (float) $inv->total - (float) $inv->paid;
        if ($pending <= 0.01) return 0.0;

        $advances = $this->db->query("
            SELECT id, amount_base, applied_amount
            FROM provider_advances
            WHERE provider_id = ? AND status='open' AND COALESCE(deleted,0)=0
              AND (amount_base - applied_amount) > 0.01
            ORDER BY pay_date ASC, id ASC
        ", array((int) $inv->provider_id))->result();

        if (empty($advances)) return 0.0;

        $invRate = (float) $inv->exchange_rate ?: 1;
        $totalApplied = 0.0;
        $this->db->trans_start();

        foreach ($advances as $adv) {
            if ($pending <= 0.01) break;
            $advSaldo = (float) $adv->amount_base - (float) $adv->applied_amount;
            if ($advSaldo <= 0.01) continue;

            // pending está en moneda de la factura; el anticipo en pesos.
            $pendingBase = ($inv->currency === 'COP') ? $pending : round($pending * $invRate, 2);
            $takeBase = min($advSaldo, $pendingBase);
            if ($takeBase <= 0.01) continue;
            $takeInvCur = ($inv->currency === 'COP') ? $takeBase : round($takeBase / $invRate, 2);

            $this->db->insert('provider_payments', array(
                'pay_code'                => $this->cxp_model->nextPayCode(),
                'invoice_id'              => $invoiceId,
                'provider_id'             => (int) $inv->provider_id,
                'pay_date'                => date('Y-m-d'),
                'currency'                => 'COP',
                'exchange_rate'           => 1,
                'amount'                  => $takeBase,
                'amount_invoice_currency' => $takeInvCur,
                'payment_method'          => 'anticipo',
                'source_type'             => null,
                'source_id'               => null,
                'reference'               => 'Aplicación anticipo',
                'fx_diff'                 => 0,
                'notes'                   => 'Aplicación automática de anticipo a proveedor',
                'created_by'              => $userId,
                'created_at'              => date('Y-m-d H:i:s'),
                'deleted'                 => 0,
            ));

            $this->db->insert('provider_advance_applications', array(
                'advance_id'  => (int) $adv->id,
                'invoice_id'  => $invoiceId,
                'amount_base' => $takeBase,
                'applied_at'  => date('Y-m-d H:i:s'),
                'created_by'  => $userId,
            ));

            $newApplied = (float) $adv->applied_amount + $takeBase;
            $fullyUsed = ($newApplied >= (float) $adv->amount_base - 0.01);
            $this->db->where('id', $adv->id)->update('provider_advances', array(
                'applied_amount' => $newApplied,
                'status'         => $fullyUsed ? 'applied' : 'open',
                'updated_at'     => date('Y-m-d H:i:s'),
            ));

            $totalApplied += $takeBase;
            $pending -= $takeInvCur;
        }

        $this->cxp_model->recalcInvoice($invoiceId);

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            throw new Exception('Error aplicando anticipos.');
        }
        return $totalApplied;
    }

    public function getApplications(int $advanceId): array
    {
        return $this->db->query("
            SELECT ap.*, pi.inv_code
            FROM provider_advance_applications ap
            LEFT JOIN provider_invoices pi ON pi.id = ap.invoice_id
            WHERE ap.advance_id = ? AND COALESCE(ap.deleted,0)=0
            ORDER BY ap.applied_at ASC
        ", array($advanceId))->result();
    }
}
