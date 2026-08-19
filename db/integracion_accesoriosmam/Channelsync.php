<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Endpoint de SOLO LECTURA para el puente accesoriosmam → Ledxury.
 *
 * Expone las remisiones del canal MAM-Online (cliente 3377, que representa a
 * Ledxury) para que el importador de Ledxury (Cronmamsync) las convierta en
 * facturas de proveedor pendientes por recibir. No escribe nada en esta base.
 *
 * Vive en el repo de LEDXURY (db/integracion_accesoriosmam/Channelsync.php) y
 * se despliega como archivo único a:
 *   accesoriosmam.com:/var/www/html/application/controllers/api/Channelsync.php
 *
 * Prueba:  https://accesoriosmam.com/api/channelsync/ping?key=...
 */
class Channelsync extends CI_Controller {

    // Debe coincidir con application/config/mamsync.php en Ledxury.
    const API_KEY = '__MAMSYNC_KEY__';

    // Cliente del canal que representa a Ledxury en esta instancia.
    const CHANNEL_CLIENT_ID = 3377;

    private function auth() {
        if ($this->input->get('key') !== self::API_KEY) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(array('error' => 'llave invalida'));
            return false;
        }
        return true;
    }

    public function ping() {
        if (!$this->auth()) return;
        header('Content-Type: application/json');
        $tot = $this->db->query(
            'SELECT COUNT(*) n, COALESCE(MAX(id),0) max_id FROM channel_remisions
             WHERE deleted = 0 AND channel_client_id = ' . self::CHANNEL_CLIENT_ID
        )->row();
        echo json_encode(array('ok' => true, 'remisiones' => (int)$tot->n, 'max_id' => (int)$tot->max_id));
    }

    /**
     * Remisiones del canal con sus items, a partir de since_id (exclusivo).
     * GET /api/channelsync/remisions?key=...&since_id=45
     */
    public function remisions() {
        if (!$this->auth()) return;
        header('Content-Type: application/json');

        $sinceId = (int)$this->input->get('since_id');
        $limit = 50; // por tanda; el importador vuelve a pedir desde el último id

        $rems = $this->db->query(
            'SELECT id, total_cost, margin_pct, total_ar, comments, created_by, created_at
             FROM channel_remisions
             WHERE deleted = 0 AND channel_client_id = ' . self::CHANNEL_CLIENT_ID . '
               AND id > ' . $sinceId . '
             ORDER BY id ASC LIMIT ' . $limit
        )->result_array();

        foreach ($rems as &$r) {
            $r['items'] = $this->db->query(
                'SELECT cri.product_id, cri.qty, cri.unit_cost, cri.unit_price,
                        COALESCE(p.description, "") AS description
                 FROM channel_remision_items cri
                 LEFT JOIN products p ON p.idProduct = cri.product_id
                 WHERE cri.remision_id = ' . (int)$r['id'] . '
                 ORDER BY cri.id'
            )->result_array();
        }
        unset($r);

        echo json_encode(array('remisions' => $rems, 'since_id' => $sinceId), JSON_UNESCAPED_UNICODE);
    }
}
