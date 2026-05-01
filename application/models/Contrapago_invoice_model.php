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
            } else {
                // Sin match en shipping_guides → presumir que es de MAM
                $this->db->where('id', $item->id)->update('contrapago_invoice_items', array(
                    'company' => 'mam'
                ));
            }
        }

        return array('matched' => $matched, 'flete_updated' => $fleteUpdated);
    }

    /**
     * Parser multi-formato de observaciones de pago. Detecta:
     *   - "Dcto Factura #208540 Por valor de $ 452.430"
     *   - "Fra. 208426 $17.779"
     *   - "Fra. 208426 $17.779 / Fra. 208540 $2.537.221"
     *   - "Factura 208540 por 2.537.221"
     * Retorna array de [['factura' => '208540', 'monto' => 452430.0], ...]
     */
    public function parseInvoiceReferences($obs) {
        if (empty($obs)) return array();
        $resultado = array();

        // Patrones a probar (en orden de especificidad)
        // 1) "Fra. NNNN $A.AAA" o "Fra NNNN $A.AAA"
        // 2) "Factura #NNNN ... $A.AAA" o "Factura NNNN ... $A.AAA"
        // 3) "Dcto Factura #NNNN ... $A.AAA"

        // Primero capturamos pares (factura, monto) en cada segmento separado por '/' o ';'
        $segmentos = preg_split('/[\/;]/', $obs);
        foreach ($segmentos as $seg) {
            $seg = trim($seg);
            if (empty($seg)) continue;

            // Buscar número de factura
            $factura = null;
            if (preg_match('/Fra\.?\s*#?\s*(\d{3,})/i', $seg, $m)) {
                $factura = $m[1];
            } elseif (preg_match('/Factura\s*#?\s*(\d{3,})/i', $seg, $m)) {
                $factura = $m[1];
            }

            // Buscar monto: $X.XXX o $X,XXX o $XXXX
            $monto = 0;
            if (preg_match('/\$\s*([\d][\d\.,]*)/', $seg, $m)) {
                $rawMonto = $m[1];
                // Normalizar: quitar separadores de miles (puntos en formato es-CO)
                // Si tiene punto y luego más de 2 dígitos al final → punto es separador miles
                // Convertir punto a vacío, y comas decimales no se usan en pesos colombianos
                $monto = (float) str_replace(array('.', ','), array('', '.'), $rawMonto);
                // Hack: si terminó con .XX (decimales), revertir
                if (preg_match('/[\.,]\d{2}$/', $rawMonto)) {
                    // tenía decimales, ajuste no necesario porque str_replace ya quitó separadores
                }
            }

            if ($factura && $monto > 0) {
                $resultado[] = array(
                    'factura' => $factura,
                    'monto' => $monto,
                    'segmento' => $seg,
                );
            }
        }

        return $resultado;
    }

    /**
     * Vincular un pago contrapago con todas las facturas Inter que aparecen en su observación.
     * - Detecta "Fra. X $Y", "Factura #X $Y", "Dcto Factura #X $Y" (también múltiples por '/')
     * - Crea/actualiza filas en contrapago_invoice_payments
     * - Recalcula status de cada factura Inter (pendiente / parcial / descontada)
     * Retorna array con info de los vínculos creados.
     */
    public function linkBatchToInterInvoices($batchId, $createdBy = 'sistema') {
        // Tomar UNA observación distinta del batch (todas las filas tienen la misma observación
        // a nivel del lote, pero por seguridad iteramos todos los pagos y deduplicamos)
        $payments = $this->db->where('batch_id', $batchId)->get('contrapago_payments')->result();
        $observacionesProcesadas = array();
        $vinculos = array();

        foreach ($payments as $p) {
            $obs = trim((string)$p->observacion);
            if (empty($obs) || isset($observacionesProcesadas[$obs])) continue;
            $observacionesProcesadas[$obs] = true;

            $refs = $this->parseInvoiceReferences($obs);
            foreach ($refs as $ref) {
                // Buscar la factura Inter en BD
                $invoice = $this->getInvoiceByNumber($ref['factura']);
                if (!$invoice) {
                    // Factura aún no importada — guardar el vínculo con invoice_id null pendiente
                    $vinculos[] = array(
                        'factura' => $ref['factura'],
                        'monto' => $ref['monto'],
                        'invoice_id' => null,
                        'status' => 'factura_no_importada',
                    );
                    continue;
                }

                // Upsert en contrapago_invoice_payments (UK por invoice_id+batch_id)
                $existing = $this->db->where('invoice_id', $invoice->id)
                    ->where('batch_id', $batchId)
                    ->get('contrapago_invoice_payments')->row();

                if ($existing) {
                    $this->db->where('id', $existing->id)->update('contrapago_invoice_payments', array(
                        'monto_cobrado' => $ref['monto'],
                        'texto_observacion' => $obs,
                    ));
                    $invoicePayId = $existing->id;
                } else {
                    $this->db->insert('contrapago_invoice_payments', array(
                        'invoice_id' => $invoice->id,
                        'batch_id' => $batchId,
                        'monto_cobrado' => $ref['monto'],
                        'texto_observacion' => $obs,
                        'created_by' => $createdBy,
                    ));
                    $invoicePayId = $this->db->insert_id();
                }

                // Recalcular status de la factura
                $totalCobrado = (float)$this->db->select('COALESCE(SUM(monto_cobrado),0) as t')
                    ->where('invoice_id', $invoice->id)
                    ->get('contrapago_invoice_payments')
                    ->row()->t;

                $valorTotal = (float)$invoice->valor_total;
                $newStatus = 'pendiente';
                if ($totalCobrado >= $valorTotal - 0.01) $newStatus = 'descontada';
                elseif ($totalCobrado > 0) $newStatus = 'parcial';

                $this->updateInvoice($invoice->id, array(
                    'status' => $newStatus,
                    'descontada_en_batch_id' => ($newStatus === 'descontada' ? $batchId : $invoice->descontada_en_batch_id),
                ));

                $vinculos[] = array(
                    'factura' => $ref['factura'],
                    'invoice_id' => $invoice->id,
                    'monto' => $ref['monto'],
                    'total_cobrado_acumulado' => $totalCobrado,
                    'valor_total' => $valorTotal,
                    'status' => $newStatus,
                );
            }
        }

        return $vinculos;
    }

    /**
     * Calcular hash único de una hoja de Excel para detectar duplicados.
     * Hash basado en nombre, total, fecha y primera guía.
     */
    public function calcSheetHash($sheetName, $totalValor, $fechaPago, $primeraGuia) {
        return md5(($sheetName ?: '') . '|' . round($totalValor, 0) . '|' . ($fechaPago ?: '') . '|' . $primeraGuia);
    }

    /**
     * @deprecated Usar linkBatchToInterInvoices()
     */
    public function linkDiscounts() {
        $batches = $this->db->where('status', 'conciliado')->or_where('status', 'registrado')->get('contrapago_batches')->result();
        $totalLinked = 0;
        foreach ($batches as $b) {
            $vinc = $this->linkBatchToInterInvoices($b->id);
            $totalLinked += count($vinc);
        }
        return $totalLinked;
    }
}
