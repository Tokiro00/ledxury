<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin: cola de ventas del bot + aliases de productos.
 * Permite ver fallidos, mapear texto del bot a codigo real y reintentar.
 */
class Botsqueue extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1, 2]);
        $this->backend_lib->controlBotsAccess();
        $this->load->model('products_model');
    }

    public function index()
    {
        date_default_timezone_set("America/Bogota");

        $status = $this->input->get('status') ?: 'failed';
        $search = trim((string)$this->input->get('q'));

        $this->db->from('bot_sales_queue');
        if ($status !== 'all') $this->db->where('status', $status);
        if ($search !== '') {
            $this->db->group_start()
                ->like('payload', $search)
                ->or_like('error_message', $search)
                ->group_end();
        }
        $this->db->order_by('created_at', 'DESC')->limit(200);
        $items = $this->db->get()->result();

        $counts = $this->db->query("SELECT status, COUNT(*) c FROM bot_sales_queue GROUP BY status")->result();
        $stats = ['pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0];
        foreach ($counts as $c) $stats[$c->status] = (int)$c->c;

        $aliases = $this->db->order_by('updated_at', 'DESC')->limit(500)->get('bot_product_aliases')->result();

        $data = [
            'items' => $items,
            'stats' => $stats,
            'status' => $status,
            'search' => $search,
            'aliases' => $aliases,
        ];
        $this->load->view('sisvent/admin/botsqueue/index', $data);
    }

    /**
     * AJAX: devuelve el payload decodificado de un item para el modal.
     */
    public function view_item()
    {
        header('Content-Type: application/json');
        $id = (int)$this->input->get('id');
        $item = $this->db->where('id', $id)->get('bot_sales_queue')->row();
        if (empty($item)) {
            echo json_encode(['ok' => false, 'error' => 'No encontrado']);
            return;
        }
        $payload = json_decode($item->payload, true);
        echo json_encode([
            'ok' => true,
            'item' => $item,
            'payload' => $payload,
        ]);
    }

    /**
     * AJAX: guarda o actualiza un alias texto -> codigo de producto.
     */
    public function save_alias()
    {
        header('Content-Type: application/json');

        $raw = trim((string)$this->input->post('alias_raw'));
        $code = strtoupper(trim((string)$this->input->post('product_code')));

        if ($raw === '' || $code === '') {
            echo json_encode(['ok' => false, 'error' => 'Faltan datos']);
            return;
        }

        $product = $this->products_model->getProduct($code);
        if (empty($product)) {
            echo json_encode(['ok' => false, 'error' => "Codigo {$code} no existe en products"]);
            return;
        }

        $norm = strtoupper(trim($raw));
        $norm = preg_replace('/\s+/', ' ', $norm);

        $uid = $this->session->userdata('user_data')['uname'] ?? null;
        $existing = $this->db->where('alias_norm', $norm)->get('bot_product_aliases')->row();

        if ($existing) {
            $this->db->where('id', $existing->id)->update('bot_product_aliases', [
                'alias_raw' => $raw,
                'product_code' => $code,
            ]);
            $msg = 'Alias actualizado';
        } else {
            $this->db->insert('bot_product_aliases', [
                'alias_norm' => $norm,
                'alias_raw' => $raw,
                'product_code' => $code,
                'created_by' => $uid,
            ]);
            $msg = 'Alias creado';
        }

        echo json_encode(['ok' => true, 'message' => $msg]);
    }

    /**
     * AJAX: elimina un alias.
     */
    public function delete_alias()
    {
        header('Content-Type: application/json');
        $id = (int)$this->input->post('id');
        $this->db->where('id', $id)->delete('bot_product_aliases');
        echo json_encode(['ok' => true]);
    }

    /**
     * AJAX: bulk import de aliases via CSV (texto). Formato por linea: texto||codigo
     */
    public function bulk_import()
    {
        header('Content-Type: application/json');
        $csv = (string)$this->input->post('csv');
        if (trim($csv) === '') {
            echo json_encode(['ok' => false, 'error' => 'CSV vacio']);
            return;
        }

        $uid = $this->session->userdata('user_data')['uname'] ?? null;
        $lines = preg_split('/\r\n|\r|\n/', $csv);
        $created = 0;
        $updated = 0;
        $skipped = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // Separadores aceptados: "||", tab, o coma (ultima coma)
            if (strpos($line, '||') !== false) {
                [$raw, $code] = array_map('trim', explode('||', $line, 2));
            } elseif (strpos($line, "\t") !== false) {
                [$raw, $code] = array_map('trim', explode("\t", $line, 2));
            } else {
                $pos = strrpos($line, ',');
                if ($pos === false) { $skipped[] = $line; continue; }
                $raw = trim(substr($line, 0, $pos));
                $code = trim(substr($line, $pos + 1));
            }

            $code = strtoupper($code);
            if ($raw === '' || $code === '') { $skipped[] = $line; continue; }

            $product = $this->products_model->getProduct($code);
            if (empty($product)) { $skipped[] = "{$line} (codigo inexistente)"; continue; }

            $norm = preg_replace('/\s+/', ' ', strtoupper(trim($raw)));
            $existing = $this->db->where('alias_norm', $norm)->get('bot_product_aliases')->row();

            if ($existing) {
                $this->db->where('id', $existing->id)->update('bot_product_aliases', [
                    'alias_raw' => $raw,
                    'product_code' => $code,
                ]);
                $updated++;
            } else {
                $this->db->insert('bot_product_aliases', [
                    'alias_norm' => $norm,
                    'alias_raw' => $raw,
                    'product_code' => $code,
                    'created_by' => $uid,
                ]);
                $created++;
            }
        }

        echo json_encode([
            'ok' => true,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);
    }

    /**
     * AJAX: reintenta un item fallido.
     */
    public function retry()
    {
        header('Content-Type: application/json');
        $id = (int)$this->input->post('id');
        $result = $this->_retry_one($id);
        echo json_encode($result);
    }

    /**
     * AJAX: reintenta todos los items en status=failed. Util despues de agregar aliases.
     */
    public function retry_all()
    {
        header('Content-Type: application/json');

        $ids = $this->db->select('id')->where('status', 'failed')->get('bot_sales_queue')->result();
        $summary = ['total' => count($ids), 'recovered' => 0, 'still_failed' => 0, 'details' => []];

        foreach ($ids as $row) {
            $r = $this->_retry_one($row->id);
            if (!empty($r['ok'])) {
                $summary['recovered']++;
            } else {
                $summary['still_failed']++;
            }
            $summary['details'][] = ['id' => $row->id, 'ok' => !empty($r['ok']), 'error' => $r['error'] ?? null];
        }

        echo json_encode($summary);
    }

    /**
     * Reintenta un item de cola. Reusa la logica de BotImport::process_webhook_sale()
     * llamandola internamente. Actualiza el estado del item.
     */
    private function _retry_one($id)
    {
        $item = $this->db->where('id', $id)->get('bot_sales_queue')->row();
        if (empty($item)) return ['ok' => false, 'error' => 'No encontrado'];

        $payload = json_decode($item->payload, true);
        if (empty($payload)) return ['ok' => false, 'error' => 'Payload invalido'];

        // Cargar BotImport para usar su logica de procesamiento
        require_once(APPPATH . 'controllers/sisvent/rest/BotImport.php');
        $bi = new BotImport();

        // process_webhook_sale es private; usamos reflection
        $ref = new ReflectionClass($bi);
        $method = $ref->getMethod('process_webhook_sale');
        $method->setAccessible(true);

        try {
            $result = $method->invoke($bi, $payload, $item->vendor_id);
        } catch (Exception $e) {
            $result = ['success' => false, 'error' => $e->getMessage()];
        }

        if (!empty($result['success'])) {
            $this->db->where('id', $id)->update('bot_sales_queue', [
                'status' => 'completed',
                'budget_id' => $result['budget_id'],
                'error_message' => null,
                'attempts' => (int)$item->attempts + 1,
                'processed_at' => date('Y-m-d H:i:s'),
            ]);
            return ['ok' => true, 'budget_id' => $result['budget_id']];
        } else {
            $this->db->where('id', $id)->update('bot_sales_queue', [
                'status' => 'failed',
                'error_message' => $result['error'] ?? 'Error desconocido',
                'attempts' => (int)$item->attempts + 1,
                'processed_at' => date('Y-m-d H:i:s'),
            ]);
            return ['ok' => false, 'error' => $result['error'] ?? 'Error desconocido'];
        }
    }

    /**
     * Auto-retry endpoint pensado para cron. Procesa items con status=failed y attempts < 5.
     * Seguridad via token simple compartido con cron-job.org / EventBridge.
     * GET /sisvent/admin/botsqueue/auto_retry?token=XXX
     */
    public function auto_retry()
    {
        header('Content-Type: application/json');

        $expected = $this->config->item('bot_retry_token');
        if (empty($expected)) $expected = 'ledxury-bot-retry-2026';
        $provided = $this->input->get('token');
        if ($provided !== $expected) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Token invalido']);
            return;
        }

        $ids = $this->db->select('id')
            ->where('status', 'failed')
            ->where('attempts <', 5)
            ->limit(50)
            ->get('bot_sales_queue')->result();

        $recovered = 0; $still = 0;
        foreach ($ids as $row) {
            $r = $this->_retry_one($row->id);
            if (!empty($r['ok'])) $recovered++; else $still++;
        }

        echo json_encode([
            'ok' => true,
            'processed' => count($ids),
            'recovered' => $recovered,
            'still_failed' => $still,
            'ts' => date('Y-m-d H:i:s'),
        ]);
    }
}
