<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Contrapago_invoice_model extends CI_Model {

    public function saveInvoice($data) {
        $this->db->insert('contrapago_invoices', $data);
        return $this->db->insert_id();
    }

    public function saveItems($rows) {
        return $this->db->insert_batch('contrapago_invoice_items', $rows);
    }

    public function getInvoices($status = null) {
        if ($status) $this->db->where('status', $status);
        return $this->db->order_by('fecha_corte', 'DESC')->get('contrapago_invoices')->result();
    }

    public function getInvoice($id) {
        return $this->db->where('id', $id)->get('contrapago_invoices')->row();
    }

    public function getInvoiceByNumber($numero) {
        return $this->db->where('numero_factura', $numero)->get('contrapago_invoices')->row();
    }

    public function getItems($invoice_id) {
        return $this->db->where('invoice_id', $invoice_id)
            ->order_by('id', 'ASC')
            ->get('contrapago_invoice_items')->result();
    }

    public function updateInvoice($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('contrapago_invoices', $data);
    }

    public function deleteInvoice($id) {
        $this->db->where('invoice_id', $id)->delete('contrapago_invoice_items');
        return $this->db->where('id', $id)->delete('contrapago_invoices');
    }

    /**
     * Cruzar items con shipping_guides e invoices del sistema,
     * actualizar fletes reales en shipping_guides
     */
    public function matchItems($invoice_id) {
        $items = $this->getItems($invoice_id);
        $matched = 0;
        $fleteUpdated = 0;

        foreach ($items as $item) {
            $guide = $this->db->select('sg.id, sg.invoiceId, sg.valorTotal, sg.valorFlete, sg.valorSeguro')
                ->from('shipping_guides sg')
                ->where('sg.numeroPreenvio', $item->numero_guia)
                ->get()->row();

            if ($guide) {
                $updateItem = array(
                    'shipping_guide_id' => $guide->id,
                    'invoice_system_id' => $guide->invoiceId ?: null,
                    'company' => 'ledxury'
                );
                $this->db->where('id', $item->id)->update('contrapago_invoice_items', $updateItem);

                // Actualizar flete real en shipping_guides si es distinto
                $nuevoFlete = (float)$item->valor_transporte;
                $nuevoTotal = (float)$item->valor_total;
                $nuevoSeguro = (float)$item->valor_prima;
                if (abs((float)$guide->valorTotal - $nuevoTotal) > 0.01 || abs((float)$guide->valorFlete - $nuevoFlete) > 0.01) {
                    $this->db->where('id', $guide->id)->update('shipping_guides', array(
                        'valorFlete' => $nuevoFlete,
                        'valorSeguro' => $nuevoSeguro,
                        'valorTotal' => $nuevoTotal,
                        'updated_at' => date('Y-m-d H:i:s')
                    ));
                    $fleteUpdated++;
                }
                $matched++;
            }
        }

        return array('matched' => $matched, 'flete_updated' => $fleteUpdated);
    }

    /**
     * Buscar en las observaciones de contrapago_payments referencias a una factura
     * Ej: "Dcto Factura #208540 Por valor de $..."
     * Si la encuentra, marca la factura como descontada
     */
    public function linkDiscounts() {
        $invoices = $this->db->where('status', 'pendiente')->get('contrapago_invoices')->result();
        $linked = 0;
        foreach ($invoices as $inv) {
            $numero = $inv->numero_factura;
            $pay = $this->db->select('cp.*, b.id as batch_id')
                ->from('contrapago_payments cp')
                ->join('contrapago_batches b', 'b.id = cp.batch_id')
                ->like('cp.observacion', 'Factura #' . $numero)
                ->or_like('cp.observacion', 'Factura ' . $numero)
                ->get()->row();
            if ($pay) {
                $this->updateInvoice($inv->id, array(
                    'status' => 'descontada',
                    'descontada_en_batch_id' => $pay->batch_id,
                    'descuento_observacion' => $pay->observacion
                ));
                $linked++;
            }
        }
        return $linked;
    }
}
