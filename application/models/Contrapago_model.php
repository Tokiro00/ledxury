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
     * Cruzar guías importadas con shipping_guides e invoices.
     * Si la guía no tiene flete, intenta recotizar con la API de Inter.
     */
    public function matchGuides($batch_id) {
        $payments = $this->getPayments($batch_id);
        $matched = 0;
        $unmatched = 0;
        $fletesUpdated = 0;

        foreach ($payments as $p) {
            $guide = $this->db->select('sg.id, sg.invoiceId, sg.status, sg.valorTotal, sg.ciudadDestinoId, sg.peso, sg.valorDeclarado, sg.isContrapago, i.clientId, i.vendorId, i.total as invoiceTotal')
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

                if ($guide->invoiceId) {
                    $invoice = $this->db->select('tracking_number')->where('idInvoice', $guide->invoiceId)->get('invoices')->row();
                    if ($invoice && empty($invoice->tracking_number)) {
                        $this->db->where('idInvoice', $guide->invoiceId)->update('invoices', array(
                            'tracking_number' => $p->numeroGuia,
                            'tracking_carrier' => 'interrapidisimo',
                        ));
                    }
                }

                // Si la guía no tiene flete, intentar recotizar
                if ((float)$guide->valorTotal <= 0) {
                    $fleteData = $this->_tryGetFlete($guide);
                    if ($fleteData) {
                        $this->db->where('id', $guide->id)->update('shipping_guides', $fleteData);
                        $fletesUpdated++;
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

        return array('matched' => $matched, 'unmatched' => $unmatched, 'fletes_updated' => $fletesUpdated);
    }

    /**
     * Intentar obtener el flete de una guía por recotización con la API
     */
    private function _tryGetFlete($guide) {
        $ciudadId = $guide->ciudadDestinoId;
        $peso = (float)$guide->peso;
        $valorDeclarado = (float)$guide->valorDeclarado;

        // Si no hay datos suficientes para cotizar, intentar con datos mínimos
        if (empty($ciudadId) || $valorDeclarado <= 0) {
            // Usar el total de la factura como valor declarado
            if ($guide->invoiceTotal > 0) $valorDeclarado = (float)$guide->invoiceTotal;
            else return false;
        }
        if ($peso <= 0) $peso = 2; // Peso mínimo por defecto
        if (empty($ciudadId)) return false;

        $CI =& get_instance();
        if (!isset($CI->interrapidisimo_lib)) {
            $CI->load->library('interrapidisimo_lib');
        }

        $esContrapago = (int)$guide->isContrapago;
        $resultado = $CI->interrapidisimo_lib->cotizar($ciudadId, $peso, $valorDeclarado, 1, $esContrapago);

        if (!$resultado) return false;

        // La API devuelve una lista de servicios, buscar el servicio 3 (mensajería)
        $flete = null;
        if (is_array($resultado)) {
            foreach ($resultado as $srv) {
                if (isset($srv->IdServicio) && $srv->IdServicio == 3) {
                    $flete = $srv;
                    break;
                }
            }
            if (!$flete && !empty($resultado)) $flete = $resultado[0];
        } elseif (is_object($resultado)) {
            $flete = $resultado;
        }

        if (!$flete) return false;

        $valorFlete = isset($flete->ValorFlete) ? (float)$flete->ValorFlete : 0;
        $valorSeguro = isset($flete->ValorSobreFlete) ? (float)$flete->ValorSobreFlete : 0;
        $valorTotal = $valorFlete + $valorSeguro;

        if ($valorTotal <= 0) return false;

        date_default_timezone_set("America/Bogota");
        return array(
            'valorFlete' => $valorFlete,
            'valorSeguro' => $valorSeguro,
            'valorTotal' => $valorTotal,
            'updated_at' => date('Y-m-d H:i:s')
        );
    }
}
