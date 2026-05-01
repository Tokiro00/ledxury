<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Modelo de cuentas por cobrar/pagar entre Ledxury y MAM
 * Un saldo positivo en "mam_debe_ledxury" = MAM nos debe.
 * Un saldo positivo en "ledxury_debe_mam" = Nosotros le debemos a MAM.
 */
class Intercompany_model extends CI_Model {

    public function save($data) {
        $this->db->insert('intercompany_movements', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('intercompany_movements', $data);
    }

    public function get($id) {
        return $this->db->where('id', $id)
            ->get('intercompany_movements')->row();
    }

    public function softDelete($id, $user) {
        $this->db->where('id', $id);
        return $this->db->update('intercompany_movements', array(
            'status' => 'anulado',
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $user,
        ));
    }

    public function restore($id) {
        $this->db->where('id', $id);
        return $this->db->update('intercompany_movements', array(
            'status' => 'activo',
            'deleted_at' => null,
            'deleted_by' => null,
        ));
    }

    public function getMovements($filters = array()) {
        $this->db->from('intercompany_movements');
        if (isset($filters['status'])) $this->db->where('status', $filters['status']);
        else $this->db->where('status', 'activo');
        if (isset($filters['tipo'])) $this->db->where('tipo', $filters['tipo']);
        if (isset($filters['concepto'])) $this->db->where('concepto', $filters['concepto']);
        if (isset($filters['from'])) $this->db->where('fecha >=', $filters['from']);
        if (isset($filters['to'])) $this->db->where('fecha <=', $filters['to']);
        $this->db->order_by('fecha', 'DESC')->order_by('id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Saldo neto: positivo = MAM debe a Ledxury. Negativo = Ledxury debe a MAM.
     */
    public function getBalance() {
        $sql = "
            SELECT
              SUM(CASE
                WHEN tipo = 'cobro_pendiente' AND direccion = 'mam_debe_ledxury' THEN monto
                WHEN tipo = 'cobro_pendiente' AND direccion = 'ledxury_debe_mam' THEN -monto
                WHEN tipo = 'pago_recibido' AND direccion = 'mam_debe_ledxury' THEN -monto
                WHEN tipo = 'pago_recibido' AND direccion = 'ledxury_debe_mam' THEN monto
                WHEN tipo = 'ajuste' AND direccion = 'mam_debe_ledxury' THEN monto
                WHEN tipo = 'ajuste' AND direccion = 'ledxury_debe_mam' THEN -monto
                ELSE 0
              END) AS balance
            FROM intercompany_movements
            WHERE status = 'activo'
        ";
        $row = $this->db->query($sql)->row();
        return $row ? (float)$row->balance : 0;
    }

    public function getStats() {
        $r = $this->db->query("
            SELECT
              COALESCE(SUM(CASE WHEN tipo='cobro_pendiente' AND status='activo' THEN monto ELSE 0 END), 0) as total_cobros_pendientes,
              COALESCE(SUM(CASE WHEN tipo='pago_recibido' AND status='activo' THEN monto ELSE 0 END), 0) as total_pagos_recibidos,
              COUNT(CASE WHEN tipo='cobro_pendiente' AND status='activo' THEN 1 END) as count_cobros,
              COUNT(CASE WHEN tipo='pago_recibido' AND status='activo' THEN 1 END) as count_pagos
            FROM intercompany_movements
        ")->row();
        return $r;
    }

    /**
     * Genera (o actualiza) cuentas por cobrar a MAM al registrar un batch de contrapagos.
     * - Por cada guía marcada company='mam' en contrapago_payments:
     *   = Ledxury cobró el contrapago para MAM. Ledxury le debe ese dinero a MAM.
     */
    public function generateFromContrapagoBatch($batchId, $bankAccountId, $createdBy) {
        // Eliminar movimientos previos de este batch (si re-registran)
        $this->db->where('contrapago_batch_id', $batchId)
            ->where('concepto', 'contrapago_mam')
            ->update('intercompany_movements', array(
                'status' => 'anulado',
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => 'sistema',
            ));

        $batch = $this->db->where('id', $batchId)->get('contrapago_batches')->row();
        if (!$batch) return 0;

        $sum = $this->db->select('COALESCE(SUM(valorTotal),0) as total, COUNT(*) as cnt')
            ->from('contrapago_payments')
            ->where('batch_id', $batchId)
            ->where('company', 'mam')
            ->where('status', 'conciliado')
            ->get()->row();

        if (!$sum || (float)$sum->total <= 0) return 0;

        $this->save(array(
            'tipo' => 'cobro_pendiente',
            'concepto' => 'contrapago_mam',
            'direccion' => 'ledxury_debe_mam', // Ledxury cobró por MAM, le debe ese dinero
            'monto' => (float)$sum->total,
            'fecha' => $batch->fecha_pago ?: date('Y-m-d'),
            'descripcion' => 'Contrapagos cobrados de clientes MAM en lote #' . $batchId . ' (' . (int)$sum->cnt . ' guías)',
            'contrapago_batch_id' => $batchId,
            'bank_account_id' => $bankAccountId,
            'created_by' => $createdBy,
        ));
        return (float)$sum->total;
    }

    /**
     * Genera CxC a MAM al registrar una factura Inter:
     * - Por cada item company='mam' (flete que Ledxury pagó por una guía de MAM):
     *   = MAM le debe ese dinero a Ledxury.
     */
    public function generateFromInterInvoice($invoiceId, $createdBy) {
        $this->db->where('contrapago_invoice_id', $invoiceId)
            ->where('concepto', 'flete_mam')
            ->update('intercompany_movements', array(
                'status' => 'anulado',
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => 'sistema',
            ));

        $invoice = $this->db->where('id', $invoiceId)->get('contrapago_invoices')->row();
        if (!$invoice) return 0;

        $sum = $this->db->select('COALESCE(SUM(valor_total),0) as total, COUNT(*) as cnt')
            ->from('contrapago_invoice_items')
            ->where('invoice_id', $invoiceId)
            ->where('company', 'mam')
            ->get()->row();

        if (!$sum || (float)$sum->total <= 0) return 0;

        $this->save(array(
            'tipo' => 'cobro_pendiente',
            'concepto' => 'flete_mam',
            'direccion' => 'mam_debe_ledxury',
            'monto' => (float)$sum->total,
            'fecha' => $invoice->fecha_corte ?: date('Y-m-d'),
            'descripcion' => 'Fletes de guías MAM en factura Inter #' . $invoice->numero_factura . ' (' . (int)$sum->cnt . ' guías)',
            'contrapago_invoice_id' => $invoiceId,
            'created_by' => $createdBy,
        ));
        return (float)$sum->total;
    }
}
