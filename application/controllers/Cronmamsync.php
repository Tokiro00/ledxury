<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Puente accesoriosmam → Ledxury: importa las remisiones del canal MAM-Online
 * como facturas de proveedor MAM "en tránsito" (pendientes por recibir).
 *
 * Flujo completo:
 *   1. MAM crea una remisión de canal en accesoriosmam (channel_remisions).
 *   2. Este cron la trae vía /api/channelsync/remisions (solo lectura, con llave).
 *   3. Acá se convierte en supplier_invoice del proveedor MAM con sus detalles
 *      y su asiento (DR mercancía en tránsito / CR proveedores + aux MAM) —
 *      aparece en Cuentas por Pagar con estado "En Tránsito".
 *   4. Bodega la revisa y le da "Recibir Mercancía": entra el stock y el
 *      asiento pasa de tránsito a inventario (flujo receive() ya existente).
 *
 * Va en su propio controlador y NO en Cron.php a propósito: la versión de
 * Cron.php que corre en producción tiene trabajo que aún no está mezclado en
 * la rama (ver db/PENDIENTE_MERGE_PROD.md) y desplegarla lo revertiría.
 *
 * URL manual:  /cronmamsync/run?key=<cron_key>
 * Crontab:     cada 15 min (ver db/integracion_accesoriosmam/README.md)
 */
class Cronmamsync extends CI_Controller {

    private $cfg;

    public function __construct() {
        parent::__construct();
        $this->load->model('accountingsettings_model');
        $this->config->load('mamsync');
        $this->cfg = $this->config->item('mamsync');
        date_default_timezone_set('America/Bogota');
    }

    public function run() {
        header('Content-Type: text/plain; charset=utf-8');
        if ($this->input->get('key') !== $this->cfg['cron_key']) {
            http_response_code(403);
            echo "llave invalida\n";
            return;
        }

        // Desde dónde pedir: la última remisión de la que ya sabemos algo.
        $last = $this->db->select_max('remision_id')->get('mam_remision_sync')->row();
        $sinceId = (int)($last->remision_id ?? 0);

        $payload = $this->fetchRemote($sinceId);
        if ($payload === null) {
            echo "ERROR: no se pudo consultar accesoriosmam (ver logs)\n";
            return;
        }
        if (empty($payload['remisions'])) {
            echo "OK: sin remisiones nuevas (desde id $sinceId)\n";
            return;
        }

        $this->load->library('accounting_lib');
        $imported = 0; $skipped = 0; $errors = 0;

        foreach ($payload['remisions'] as $rem) {
            $remId = (int)$rem['id'];

            // Idempotencia dura: si ya está registrada, no se toca.
            $seen = $this->db->where('remision_id', $remId)->get('mam_remision_sync')->num_rows();
            if ($seen > 0) continue;

            // Lo anterior al saldo inicial no se factura: ya está dentro del
            // SALDO-INICIAL-MAMONLINE-20260801.
            if (substr($rem['created_at'], 0, 10) < $this->cfg['start_date']) {
                $this->db->insert('mam_remision_sync', array(
                    'remision_id' => $remId,
                    'total_ar'    => (float)$rem['total_ar'],
                    'items'       => count($rem['items']),
                    'status'      => 'omitida_saldo_inicial',
                ));
                $skipped++;
                continue;
            }

            if ($this->importRemision($rem)) {
                $imported++;
                echo "importada remision #" . $remId . " -> factura REM-MAM-" . str_pad($remId, 5, '0', STR_PAD_LEFT)
                   . " por $" . number_format((float)$rem['total_ar'], 0, ',', '.') . "\n";
            } else {
                $errors++;
                echo "ERROR en remision #" . $remId . " (ver logs)\n";
            }
        }

        echo "resumen: $imported importadas, $skipped omitidas (previas al saldo inicial), $errors con error\n";
    }

    /** GET al endpoint de solo lectura en accesoriosmam. */
    private function fetchRemote($sinceId) {
        $url = rtrim($this->cfg['remote_url'], '/') . '/remisions?key=' . urlencode($this->cfg['api_key'])
             . '&since_id=' . (int)$sinceId;
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
        ));
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $code !== 200) {
            log_message('error', "Cronmamsync: fallo HTTP $code al consultar accesoriosmam: $err");
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['remisions'])) {
            log_message('error', 'Cronmamsync: respuesta no es JSON valido: ' . substr($raw, 0, 200));
            return null;
        }
        return $data;
    }

    /** Crea la factura de proveedor + detalles + asiento para una remisión. */
    private function importRemision($rem) {
        $remId  = (int)$rem['id'];
        $number = 'REM-MAM-' . str_pad($remId, 5, '0', STR_PAD_LEFT);
        $total  = (float)$rem['total_ar'];
        $date   = substr($rem['created_at'], 0, 10);
        $items  = is_array($rem['items']) ? $rem['items'] : array();

        if ($total <= 0 || empty($items)) {
            $this->db->insert('mam_remision_sync', array(
                'remision_id' => $remId, 'total_ar' => $total, 'items' => count($items),
                'status' => 'error', 'error_msg' => 'remision vacia o total en 0',
            ));
            return false;
        }

        // Cuadre: la suma de items debe dar el total de la remisión.
        $sumItems = 0;
        foreach ($items as $it) $sumItems += (int)$it['qty'] * (float)$it['unit_price'];
        $cuadra = abs($sumItems - $total) < 1;

        // Productos que aún no existen en el catálogo de Ledxury (los códigos
        // son compartidos entre instancias). El detalle se importa igual —
        // inventory usa el código como llave, así que al crear el producto
        // después, el stock recibido queda conectado.
        $codes = array();
        foreach ($items as $it) $codes[] = (string)$it['product_id'];
        $inList = array();
        foreach ($codes as $cd) $inList[] = $this->db->escape($cd);
        $found = array();
        foreach ($this->db->query('SELECT idProduct FROM products WHERE idProduct IN (' . implode(',', $inList) . ')')->result() as $p) {
            $found[] = $p->idProduct;
        }
        $missing = array_values(array_diff($codes, $found));

        $notes = "Remisión #$remId del canal MAM-Online en accesoriosmam ($date)";
        if (!empty($rem['comments'])) $notes .= "\nNota de MAM: " . $rem['comments'];
        if (!$cuadra) $notes .= "\nOJO: la suma de los items ($" . number_format($sumItems, 0, ',', '.') . ") no cuadra con el total de la remisión.";
        if ($missing)  $notes .= "\nOJO - PRODUCTOS SIN CREAR EN LEDXURY: " . implode(', ', $missing) . " — créalos antes de dar Recibir para que el stock quede conectado.";

        $this->db->trans_start();

        $this->db->insert('supplier_invoices', array(
            'providerId'    => (string)$this->cfg['provider_id'],
            'invoiceNumber' => $number,
            'invoiceDate'   => $date,
            'dueDate'       => date('Y-m-d', strtotime($date . ' +30 days')),
            'total'         => $total,
            'subtotal'      => $total,
            'tax'           => 0,
            'paidAmount'    => 0,
            'balance'       => $total,
            'status'        => 'pendiente',
            'storeId'       => (int)$this->cfg['store_id'],
            'received'      => 0,     // queda "En Tránsito": bodega revisa y da Recibir
            'notes'         => $notes,
            'created_at'    => date('Y-m-d H:i:s'),
        ));
        $billId = $this->db->insert_id();

        foreach ($items as $it) {
            $this->db->insert('supplier_invoice_details', array(
                'supplierInvoiceId' => $billId,
                'productId'         => (string)$it['product_id'],
                'description'       => isset($it['description']) ? (string)$it['description'] : '',
                'quantity'          => (int)$it['qty'],
                'unitPrice'         => (float)$it['unit_price'],
                'total'             => (int)$it['qty'] * (float)$it['unit_price'],
            ));
        }

        // Asiento: DR mercancía en tránsito / CR proveedores + auxiliar MAM.
        // El mismo que crea el flujo manual de Cuentas por Pagar (store()).
        $entryOk = $this->accounting_lib->recordSupplierBill(
            $billId, $this->cfg['provider_id'], $this->cfg['store_id'], $total, 'mamsync'
        );

        $this->db->insert('mam_remision_sync', array(
            'remision_id'         => $remId,
            'supplier_invoice_id' => $billId,
            'total_ar'            => $total,
            'items'               => count($items),
            'missing_products'    => $missing ? implode(',', $missing) : null,
            'status'              => 'importada',
            'error_msg'           => $entryOk ? null : 'asiento contable fallo — revisar accounting_settings',
        ));

        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            log_message('error', "Cronmamsync: transaccion fallida para remision $remId");
            return false;
        }
        if (!$entryOk) {
            log_message('error', "Cronmamsync: factura $number creada pero el asiento contable fallo");
        }
        log_message('info', "mamsync importo remision #$remId como $number por $" . number_format($total, 0, ',', '.'));
        return true;
    }
}
