<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Contrapago_model extends CI_Model {

    public function saveBatch($data) {
        $this->db->insert('contrapago_batches', $data);
        return $this->db->insert_id();
    }

    public function savePayment($data) {
        return $this->db->insert('contrapago_payments', $data);
    }

    public function savePaymentsBatch($rows) {
        return $this->db->insert_batch('contrapago_payments', $rows);
    }

    public function getBatches() {
        return $this->db->order_by('id', 'DESC')->get('contrapago_batches')->result();
    }

    public function getBatch($id) {
        return $this->db->where('id', $id)->get('contrapago_batches')->row();
    }

    public function getPayments($batch_id) {
        return $this->db->where('batch_id', $batch_id)
            ->order_by('id', 'ASC')
            ->get('contrapago_payments')->result();
    }

    public function updateBatch($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('contrapago_batches', $data);
    }

    public function updatePayment($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('contrapago_payments', $data);
    }

    /**
     * Cruzar guías importadas con shipping_guides e invoices
     */
    public function matchGuides($batch_id) {
        $payments = $this->getPayments($batch_id);
        $matched = 0;
        $unmatched = 0;

        foreach ($payments as $p) {
            // Buscar en shipping_guides por número de guía
            $guide = $this->db->select('sg.id, sg.invoiceId, sg.status, i.clientId, i.vendorId, i.total as invoiceTotal')
                ->from('shipping_guides sg')
                ->join('invoices i', 'i.idInvoice = sg.invoiceId', 'left')
                ->where('sg.numeroPreenvio', $p->numeroGuia)
                ->get()->row();

            if ($guide) {
                $this->updatePayment($p->id, array(
                    'shipping_guide_id' => $guide->id,
                    'invoice_id' => $guide->invoiceId,
                    'status' => 'conciliado'
                ));

                // Escribir tracking_number en la factura si no lo tiene
                if ($guide->invoiceId) {
                    $invoice = $this->db->select('tracking_number')->where('idInvoice', $guide->invoiceId)->get('invoices')->row();
                    if ($invoice && empty($invoice->tracking_number)) {
                        $this->db->where('idInvoice', $guide->invoiceId)->update('invoices', array(
                            'tracking_number' => $p->numeroGuia,
                            'tracking_carrier' => 'interrapidisimo',
                        ));
                    }
                }

                $matched++;
            } else {
                $this->updatePayment($p->id, array('status' => 'sin_match'));
                $unmatched++;
            }
        }

        $this->updateBatch($batch_id, array(
            'matched' => $matched,
            'unmatched' => $unmatched,
            'status' => 'conciliado'
        ));

        return array('matched' => $matched, 'unmatched' => $unmatched);
    }
}
