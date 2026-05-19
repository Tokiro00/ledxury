<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CRUD de purchase_rules: reglas recurrentes que el cron ejecuta para
 * generar órdenes de compra borrador a proveedores.
 *
 * Frecuencias soportadas en la UI:
 *   - weekly: día de semana + hora (Bogotá)
 *   - monthly: día del mes (1–28) + hora (Bogotá)
 *
 * El cron `Cron::run_purchase_rules` toma rules con next_run_at <= NOW()
 * y las ejecuta. Después actualiza next_run_at sumando la frecuencia.
 */
class Purchaserules extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1]); // admin/superadmin
        $this->load->model('purchaserules_model');
        $this->load->model('providers_model');
        $this->load->model('stores_model');
        $this->load->model('supplierorders_model');
    }

    // ========================================================================
    // LISTADO
    // ========================================================================

    public function index()
    {
        $data = [
            'rules' => $this->purchaserules_model->getRules(false),
        ];
        $this->load->view('sisvent/admin/purchaserules/list', $data);
    }

    // ========================================================================
    // CREAR
    // ========================================================================

    public function add()
    {
        $data = [
            'rule'      => null,
            'providers' => $this->providers_model->getProviders(),
            'stores'    => $this->stores_model->getStores(),
        ];
        $this->load->view('sisvent/admin/purchaserules/edit', $data);
    }

    public function store()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        $payload = $this->_validateAndBuildPayload();
        if ($payload === null) {
            $this->add();
            return;
        }

        if ($this->purchaserules_model->nameExists($payload['name'])) {
            $this->session->set_flashdata('error', 'Ya existe una regla con ese nombre.');
            redirect(base_url() . 'sisvent/admin/purchaserules/add');
            return;
        }

        $payload['next_run_at'] = $this->_calcInitialNextRun($payload);

        $id = $this->purchaserules_model->save($payload);
        if ($id) {
            $this->session->set_flashdata('success', "Regla '{$payload['name']}' creada. Próxima ejecución: " . $this->_fmtNextRun($payload['next_run_at']));
            redirect(base_url() . 'sisvent/admin/purchaserules');
        } else {
            $this->session->set_flashdata('error', 'No se pudo crear la regla.');
            redirect(base_url() . 'sisvent/admin/purchaserules/add');
        }
    }

    // ========================================================================
    // EDITAR
    // ========================================================================

    public function edit($id)
    {
        $rule = $this->purchaserules_model->getRule($id);
        if (!$rule) {
            redirect(base_url() . 'sisvent/admin/purchaserules');
            return;
        }
        $data = [
            'rule'      => $rule,
            'providers' => $this->providers_model->getProviders(),
            'stores'    => $this->stores_model->getStores(),
        ];
        $this->load->view('sisvent/admin/purchaserules/edit', $data);
    }

    public function update()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        $id = (int)$this->input->post('id');
        $rule = $this->purchaserules_model->getRule($id);
        if (!$rule) redirect(base_url() . 'sisvent/admin/purchaserules');

        $payload = $this->_validateAndBuildPayload();
        if ($payload === null) {
            $this->edit($id);
            return;
        }

        if ($this->purchaserules_model->nameExists($payload['name'], $id)) {
            $this->session->set_flashdata('error', 'Ya existe otra regla con ese nombre.');
            redirect(base_url() . 'sisvent/admin/purchaserules/edit/' . $id);
            return;
        }

        // Si cambió la frecuencia, recalcular next_run_at
        $oldCfg = json_decode((string)$rule->frequency_config, true) ?: [];
        $newCfg = json_decode($payload['frequency_config'], true) ?: [];
        if ($rule->frequency_type !== $payload['frequency_type'] || $oldCfg !== $newCfg) {
            $payload['next_run_at'] = $this->_calcInitialNextRun($payload);
        }

        if ($this->purchaserules_model->update($id, $payload)) {
            $this->session->set_flashdata('success', 'Regla actualizada.');
            redirect(base_url() . 'sisvent/admin/purchaserules');
        } else {
            $this->session->set_flashdata('error', 'No se pudo actualizar.');
            redirect(base_url() . 'sisvent/admin/purchaserules/edit/' . $id);
        }
    }

    // ========================================================================
    // ACCIONES
    // ========================================================================

    public function delete($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        $this->purchaserules_model->remove($id);
        echo base_url() . 'sisvent/admin/purchaserules';
    }

    public function toggle($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        $this->purchaserules_model->toggleActive($id);
        echo base_url() . 'sisvent/admin/purchaserules';
    }

    /**
     * Forzar ejecución inmediata: pone next_run_at=NOW() y dispara el cron.
     * Útil para que el admin no tenga que esperar al horario programado.
     */
    public function runNow($id)
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        $rule = $this->purchaserules_model->getRule($id);
        if (!$rule) {
            echo json_encode(['ok' => false, 'error' => 'Regla no encontrada']);
            return;
        }
        if (!$rule->active) {
            echo json_encode(['ok' => false, 'error' => 'La regla está inactiva. Actívala primero.']);
            return;
        }

        // Forzar ejecución ahora: bajar next_run_at (hora local Bogotá)
        $this->purchaserules_model->update($id, ['next_run_at' => date('Y-m-d H:i:s')]);

        // Disparar el cron internamente (vía cURL local) para no esperar al :05
        $url = base_url() . 'cron/run_purchase_rules?key=sisvent_cron_2024_tracking';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($resp, true);
        if (!$decoded || empty($decoded['ok'])) {
            echo json_encode(['ok' => false, 'error' => 'El cron respondió: ' . $resp]);
            return;
        }

        // Buscar resultado de esta rule específica en details
        $myDetail = null;
        foreach (($decoded['details'] ?? []) as $d) {
            if ((int)$d['rule_id'] === (int)$id) { $myDetail = $d; break; }
        }
        echo json_encode([
            'ok'     => true,
            'cron'   => $decoded,
            'detail' => $myDetail,
        ]);
    }

    // ========================================================================
    // HELPERS
    // ========================================================================

    /**
     * Lee POST, valida y arma el payload listo para save/update.
     * Devuelve null si la validación falla (con flashdata).
     */
    private function _validateAndBuildPayload()
    {
        $name           = trim((string)$this->input->post('name'));
        $providerId     = (int)$this->input->post('providerId');
        $storeId        = (int)$this->input->post('storeId');
        $frequency_type = $this->input->post('frequency_type');
        $lookback_days  = max(1, (int)$this->input->post('lookback_days'));
        $product_filter = $this->input->post('product_filter');
        $product_list   = (string)$this->input->post('product_list');
        $exclude_blocked= $this->input->post('exclude_blocked') ? 1 : 0;
        $active         = $this->input->post('active') ? 1 : 0;

        $errors = [];
        if ($name === '')                           $errors[] = 'Nombre es obligatorio.';
        if ($providerId <= 0)                       $errors[] = 'Selecciona proveedor.';
        if ($storeId <= 0)                          $errors[] = 'Selecciona tienda.';
        if (!in_array($frequency_type, ['weekly','monthly','custom'], true)) $errors[] = 'Frecuencia inválida.';
        if (!in_array($product_filter, ['all_sold','specific_list','all_provider'], true)) $errors[] = 'Filtro de productos inválido.';
        if ($product_filter === 'specific_list' && trim($product_list) === '') $errors[] = 'Lista específica: pega los SKUs separados por coma.';

        // Construir frequency_config
        $cfg = ['hour' => max(0, min(23, (int)$this->input->post('hour')))];
        if ($frequency_type === 'weekly') {
            $cfg['day_of_week'] = max(1, min(7, (int)$this->input->post('day_of_week')));
        } elseif ($frequency_type === 'monthly') {
            $cfg['day_of_month'] = max(1, min(28, (int)$this->input->post('day_of_month')));
        } elseif ($frequency_type === 'custom') {
            $cfg['cron'] = trim((string)$this->input->post('cron_expr'));
        }

        // Lista específica → JSON array
        $product_list_json = null;
        if ($product_filter === 'specific_list') {
            $skus = array_filter(array_map('trim', preg_split('/[,\n;]+/', $product_list)));
            $product_list_json = json_encode(array_values(array_unique($skus)), JSON_UNESCAPED_UNICODE);
        }

        if (!empty($errors)) {
            $this->session->set_flashdata('error', implode(' ', $errors));
            return null;
        }

        return [
            'name'             => $name,
            'providerId'       => $providerId,
            'storeId'          => $storeId,
            'frequency_type'   => $frequency_type,
            'frequency_config' => json_encode($cfg, JSON_UNESCAPED_UNICODE),
            'lookback_days'    => $lookback_days,
            'product_filter'   => $product_filter,
            'product_list'     => $product_list_json,
            'exclude_blocked'  => $exclude_blocked,
            'active'           => $active,
        ];
    }

    /**
     * Calcula next_run_at en UTC para una rule recién creada/modificada,
     * a partir del frequency_type/config, en hora Bogotá.
     */
    private function _calcInitialNextRun($payload)
    {
        $cfg = json_decode($payload['frequency_config'], true) ?: [];
        $hour_bogota = max(0, min(23, (int)($cfg['hour'] ?? 6)));

        $tz_local = new DateTimeZone('America/Bogota');
        $tz_utc   = new DateTimeZone('UTC');
        $today_local = (new DateTime('now', $tz_local))->format('Y-m-d');

        switch ($payload['frequency_type']) {
            case 'monthly':
                $dom = max(1, min(28, (int)($cfg['day_of_month'] ?? 1)));
                $now = new DateTime('now', $tz_local);
                $candidate = (clone $now)->setDate((int)$now->format('Y'), (int)$now->format('n'), $dom)->setTime($hour_bogota, 0);
                if ($candidate <= $now) $candidate->modify('+1 month');
                $next = $candidate;
                break;

            case 'custom':
                // Sin parser de cron real: arranca mañana a esa hora
                $next = (new DateTime("$today_local +1 day", $tz_local))->setTime($hour_bogota, 0);
                break;

            case 'weekly':
            default:
                $dow = max(1, min(7, (int)($cfg['day_of_week'] ?? 1)));
                $today_dow = (int)(new DateTime('now', $tz_local))->format('N');
                $delta = ($dow - $today_dow + 7) % 7;
                if ($delta === 0) $delta = 7;
                $next = (new DateTime("$today_local +{$delta} days", $tz_local))->setTime($hour_bogota, 0);
                break;
        }

        // Server y MySQL están en America/Bogota desde el 1-may. Devolvemos
        // hora local sin convertir a UTC.
        return $next->format('Y-m-d H:i:s');
    }

    /**
     * Formatea un next_run_at (UTC) para mostrar al usuario en Bogotá.
     */
    private function _fmtNextRun($utcStr)
    {
        if (!$utcStr) return '-';
        $dt = new DateTime($utcStr, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('America/Bogota'));
        return $dt->format('Y-m-d H:i') . ' (Bogotá)';
    }
}
