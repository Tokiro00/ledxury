<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Despachos MAM con guías Inter. Source of truth para auto-tagging
 * de items intercompañía (company='mam') en contrapago_invoice_items
 * y contrapago_payments.
 */
class Mamdispatches_model extends CI_Model
{
    /**
     * Upsert: si numero_guia existe, actualiza; si no, inserta.
     * Re-subir el mismo archivo NO duplica filas.
     *
     * @param array $rows  array of associative rows con las columnas
     * @return array  ['inserted' => N, 'updated' => N]
     */
    public function bulkUpsert(array $rows, $filename, $userId)
    {
        if (empty($rows)) return array('inserted' => 0, 'updated' => 0);

        $beforeCount = $this->db->count_all('mam_dispatches');
        $updated = 0;

        // Hago el upsert una fila a la vez para poder distinguir insert vs update.
        // Si fuera muy grande podría hacer batch INSERT ... ON DUPLICATE KEY UPDATE.
        foreach ($rows as $r) {
            $r['imported_filename'] = $filename;
            $r['imported_by']       = $userId;
            // Build SQL con ON DUPLICATE KEY UPDATE
            $cols = array_keys($r);
            $placeholders = array_map(function($c) { return '?'; }, $cols);
            $updates = array();
            foreach ($cols as $c) {
                if ($c === 'numero_guia') continue;  // no actualizar la key
                $updates[] = "`$c`=VALUES(`$c`)";
            }
            $sql = "INSERT INTO mam_dispatches (`" . implode('`,`', $cols) . "`) VALUES ("
                . implode(',', $placeholders) . ") ON DUPLICATE KEY UPDATE " . implode(',', $updates);
            $values = array_values($r);
            $this->db->query($sql, $values);
            // affected_rows: 1 = insert, 2 = update, 0 = no change
            $aff = $this->db->affected_rows();
            if ($aff == 2) $updated++;
        }

        $afterCount = $this->db->count_all('mam_dispatches');
        $inserted = $afterCount - $beforeCount;
        return array('inserted' => $inserted, 'updated' => $updated);
    }

    /**
     * Después de importar despachos MAM, marca como company='mam' los
     * items y payments existentes cuyas guías coincidan.
     *
     * @return array  ['items_updated' => N, 'payments_updated' => N]
     */
    public function autoTagExistingItems()
    {
        // contrapago_invoice_items
        $sqlItems = "UPDATE contrapago_invoice_items cii
                     JOIN mam_dispatches md ON CAST(cii.numero_guia AS UNSIGNED) = md.numero_guia
                     SET cii.company = 'mam'
                     WHERE COALESCE(cii.company, 'ledxury') <> 'mam'";
        $this->db->query($sqlItems);
        $itemsUpdated = $this->db->affected_rows();

        // contrapago_payments
        $sqlPay = "UPDATE contrapago_payments cp
                   JOIN mam_dispatches md ON CAST(cp.numeroGuia AS UNSIGNED) = md.numero_guia
                   SET cp.company = 'mam'
                   WHERE COALESCE(cp.company, 'ledxury') <> 'mam'";
        $this->db->query($sqlPay);
        $paymentsUpdated = $this->db->affected_rows();

        return array(
            'items_updated' => $itemsUpdated,
            'payments_updated' => $paymentsUpdated,
        );
    }

    /**
     * Marca como 'mam' los items de UNA factura específica que matcheen
     * con guías en mam_dispatches. Se llama desde uploadInvoice() después
     * de insertar los items, para auto-tagging on-the-fly.
     */
    public function autoTagInvoice($invoiceId)
    {
        $sql = "UPDATE contrapago_invoice_items cii
                JOIN mam_dispatches md ON CAST(cii.numero_guia AS UNSIGNED) = md.numero_guia
                SET cii.company = 'mam'
                WHERE cii.invoice_id = ? AND COALESCE(cii.company, 'ledxury') <> 'mam'";
        $this->db->query($sql, array($invoiceId));
        return $this->db->affected_rows();
    }

    /**
     * Marca como 'mam' los payments de UN batch específico que matcheen
     * con guías en mam_dispatches. Se llama desde upload() (contrapago batch).
     */
    public function autoTagBatch($batchId)
    {
        $sql = "UPDATE contrapago_payments cp
                JOIN mam_dispatches md ON CAST(cp.numeroGuia AS UNSIGNED) = md.numero_guia
                SET cp.company = 'mam'
                WHERE cp.batch_id = ? AND COALESCE(cp.company, 'ledxury') <> 'mam'";
        $this->db->query($sql, array($batchId));
        return $this->db->affected_rows();
    }

    public function getTotal($filters = array())
    {
        $this->db->from('mam_dispatches');
        if (!empty($filters['vendedor']))   $this->db->like('vendedor', $filters['vendedor']);
        if (!empty($filters['guia']))       $this->db->where('numero_guia', $filters['guia']);
        if (!empty($filters['from']))       $this->db->where('fecha_despacho >=', $filters['from'] . ' 00:00:00');
        if (!empty($filters['to']))         $this->db->where('fecha_despacho <=', $filters['to'] . ' 23:59:59');
        return $this->db->count_all_results();
    }

    public function getList($filters = array(), $page = 1, $limit = 50)
    {
        $this->db->from('mam_dispatches')->order_by('fecha_despacho', 'DESC')->limit($limit, ($page - 1) * $limit);
        if (!empty($filters['vendedor']))   $this->db->like('vendedor', $filters['vendedor']);
        if (!empty($filters['guia']))       $this->db->where('numero_guia', $filters['guia']);
        if (!empty($filters['from']))       $this->db->where('fecha_despacho >=', $filters['from'] . ' 00:00:00');
        if (!empty($filters['to']))         $this->db->where('fecha_despacho <=', $filters['to'] . ' 23:59:59');
        return $this->db->get()->result();
    }

    public function getStats()
    {
        $row = $this->db->select('COUNT(*) AS total, COALESCE(SUM(valor_factura),0) AS total_facturado, COALESCE(SUM(flete),0) AS total_flete, MAX(imported_at) AS last_import, COUNT(DISTINCT vendedor) AS vendedores, MIN(fecha_despacho) AS oldest, MAX(fecha_despacho) AS newest')
            ->from('mam_dispatches')
            ->get()->row();
        return $row;
    }

    /**
     * Cuántos items/payments en Ledxury coinciden con guías MAM (already tagged).
     */
    public function getMatchStats()
    {
        $r1 = $this->db->query("SELECT COUNT(*) AS n FROM contrapago_invoice_items WHERE company='mam'")->row();
        $r2 = $this->db->query("SELECT COUNT(*) AS n FROM contrapago_payments WHERE company='mam'")->row();
        return array(
            'items_mam' => $r1 ? (int)$r1->n : 0,
            'payments_mam' => $r2 ? (int)$r2->n : 0,
        );
    }
}
