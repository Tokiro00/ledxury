<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bots extends CI_Controller {

    private $is_owner = false;
    private $owner_id = '71211970';

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1, 10]); // superadmin + adminbots
        $this->load->model('builderbot_model');
        $this->load->library('builderbot_lib');

        $user_data = $this->session->userdata('user_data');
        // Owner: user 71211970 O cualquier usuario con rol adminbots (10)
        $permissions = $this->session->userdata('permissions') ?: [];
        $this->is_owner = ($user_data['uname'] === $this->owner_id || in_array('admin_bots', $permissions));
    }

    /**
     * Dashboard: listado de bots + stats
     * GET /sisvent/admin/bots
     */
    public function index()
    {
        $configs = $this->builderbot_model->getConfigs();

        $bots = array();
        foreach ($configs as $cfg) {
            $bots[] = array(
                'config'       => $cfg,
                'ventas_hoy'   => $this->builderbot_model->getTodaySalesCount($cfg->id),
                'ventas_semana' => $this->builderbot_model->getWeekSalesCount($cfg->id),
                'mensajes'     => $this->builderbot_model->getMessagesSentCount($cfg->id),
                'recientes'    => $this->builderbot_model->getRecentSales($cfg->id, 10),
            );
        }

        $data = array(
            'bots'     => $bots,
            'is_owner' => $this->is_owner,
        );
        $this->load->view('sisvent/admin/bots/dashboard', $data);
    }

    /**
     * Configuración de un bot (SOLO OWNER)
     * GET /sisvent/admin/bots/config/{id}
     */
    public function config($id = null)
    {
        $this->_requireOwner();

        $this->load->model('vendors_model');
        $this->load->model('stores_model');

        $config = $id ? $this->builderbot_model->getConfig($id) : null;

        // Cargar instrucciones del asistente si el bot tiene answer_id
        $instructions = null;
        if ($config && !empty($config->answer_id)) {
            $instructions = $this->builderbot_lib->getAssistantInstructions($config);
        }

        $data = array(
            'bot_config'   => $config,
            'vendors'      => $this->vendors_model->getVendors(),
            'stores'       => $this->stores_model->getStores(),
            'is_owner'     => true,
            'webhook_url'  => base_url() . 'webhook/builderbot',
            'instructions' => $instructions,
        );
        $this->load->view('sisvent/admin/bots/config', $data);
    }

    /**
     * Guardar configuración (SOLO OWNER)
     * POST /sisvent/admin/bots/saveConfig
     */
    public function saveConfig()
    {
        $this->_requireOwner();
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        $id = $this->input->post('id');

        $config_data = array(
            'name'              => $this->input->post('name'),
            'bot_id'            => $this->input->post('bot_id'),
            'api_key'           => $this->input->post('api_key'),
            'base_url'          => $this->input->post('base_url') ?: 'https://app.builderbot.cloud',
            'webhook_secret'    => $this->input->post('webhook_secret'),
            'default_vendor_id' => $this->input->post('default_vendor_id'),
            'default_store_id'  => $this->input->post('default_store_id') ?: 1,
            'sheet_id'          => $this->input->post('sheet_id'),
            'sheet_gid'         => $this->input->post('sheet_gid') ?: '0',
            'script_url'        => $this->input->post('script_url'),
        );

        if ($id) {
            $this->builderbot_model->updateConfig($id, $config_data);
            $this->session->set_flashdata('success', 'Bot actualizado correctamente.');
        } else {
            $config_data['created_by'] = $this->session->userdata('user_data')['uname'];
            $this->builderbot_model->saveConfig($config_data);
            $this->session->set_flashdata('success', 'Bot creado correctamente.');
        }

        redirect(base_url() . 'sisvent/admin/bots');
    }

    /**
     * Log de ventas de un bot
     * GET /sisvent/admin/bots/sales/{bot_config_id}
     */
    public function sales($bot_config_id = null)
    {
        if (!$bot_config_id) redirect(base_url() . 'sisvent/admin/bots');

        $config = $this->builderbot_model->getConfig($bot_config_id);
        if (!$config) redirect(base_url() . 'sisvent/admin/bots');

        $data = array(
            'bot_config' => $config,
            'sales'      => $this->builderbot_model->getRecentSales($bot_config_id, 100),
            'stats'      => $this->builderbot_model->getSalesStats($bot_config_id),
            'is_owner'   => $this->is_owner,
        );
        $this->load->view('sisvent/admin/bots/sales', $data);
    }

    /**
     * Historial de mensajes
     * GET /sisvent/admin/bots/messages/{bot_config_id}
     */
    public function messages($bot_config_id = null)
    {
        if (!$bot_config_id) redirect(base_url() . 'sisvent/admin/bots');

        $config = $this->builderbot_model->getConfig($bot_config_id);
        if (!$config) redirect(base_url() . 'sisvent/admin/bots');

        $data = array(
            'bot_config' => $config,
            'messages'   => $this->builderbot_model->getMessages($bot_config_id, 100),
            'is_owner'   => $this->is_owner,
        );
        $this->load->view('sisvent/admin/bots/messages', $data);
    }

    /**
     * AJAX: Enviar mensaje (SOLO OWNER)
     * POST /sisvent/admin/bots/sendMessage
     */
    public function sendMessage()
    {
        header('Content-Type: application/json');

        if (!$this->is_owner) {
            echo json_encode(array('error' => 'No autorizado'));
            return;
        }

        $bot_config_id = (int) $this->input->post('bot_config_id');
        $phone   = $this->input->post('phone');
        $content = $this->input->post('content');
        $mediaUrl = $this->input->post('media_url');

        if (!$bot_config_id || !$phone || !$content) {
            echo json_encode(array('error' => 'Faltan campos requeridos'));
            return;
        }

        $config = $this->builderbot_model->getConfig($bot_config_id);
        if (!$config) {
            echo json_encode(array('error' => 'Bot no encontrado'));
            return;
        }

        // Guardar en log
        $msg_id = $this->builderbot_model->saveMessage(array(
            'bot_config_id' => $bot_config_id,
            'direction'     => 'outgoing',
            'phone_number'  => $phone,
            'content'       => $content,
            'media_url'     => $mediaUrl ?: null,
            'status'        => 'queued',
            'sent_by'       => $this->session->userdata('user_data')['uname'],
        ));

        // Enviar via API
        $result = $this->builderbot_lib->sendMessage($config, $phone, $content, $mediaUrl ?: null);

        // Actualizar status
        $status = $result['success'] ? 'sent' : 'failed';
        $this->builderbot_model->updateMessageStatus($msg_id, $status, $result['response']);

        echo json_encode(array(
            'success' => $result['success'],
            'message' => $result['success'] ? 'Mensaje enviado' : 'Error al enviar: HTTP ' . $result['http_code'],
        ));
    }

    /**
     * AJAX: Eliminar/desactivar bot (SOLO OWNER)
     */
    public function deleteConfig()
    {
        header('Content-Type: application/json');

        if (!$this->is_owner) {
            echo json_encode(array('error' => 'No autorizado'));
            return;
        }

        $id = (int) $this->input->post('id');
        $this->builderbot_model->deleteConfig($id);
        echo json_encode(array('success' => true));
    }

    /**
     * Editor del prompt del Asistente IA del bot
     * GET /sisvent/admin/bots/prompt/{bot_config_id}
     */
    public function prompt($bot_config_id = null)
    {
        $this->_requireOwner();

        if (!$bot_config_id) redirect(base_url() . 'sisvent/admin/bots');
        $config = $this->builderbot_model->getConfig($bot_config_id);
        if (!$config) redirect(base_url() . 'sisvent/admin/bots');

        $instructions = $this->builderbot_lib->getAssistantInstructions($config);

        $data = array(
            'bot_config'   => $config,
            'instructions' => $instructions,
            'is_owner'     => true,
        );
        $this->load->view('sisvent/admin/bots/prompt', $data);
    }

    /**
     * AJAX: Generar bloque de datos de productos desde la BD
     * GET /sisvent/admin/bots/getProductData
     */
    public function getProductData()
    {
        header('Content-Type: application/json');

        if (!$this->is_owner) {
            echo json_encode(array('error' => 'No autorizado'));
            return;
        }

        $this->load->model('products_model');
        date_default_timezone_set("America/Bogota");

        // Obtener productos activos
        $products = $this->db->select('idProduct, description, price, price_base')
            ->from('products')
            ->where('deleted', 0)
            ->order_by('idProduct', 'ASC')
            ->get()->result();

        $now = date('Y-m-d H:i');
        $text = "--- DATOS ACTUALIZADOS DESDE MAM ({$now}) ---\n";
        $text .= "IMPORTANTE: Estos datos son reales y actualizados del sistema. Usalos como referencia de precios y productos disponibles.\n\n";

        // Agrupar por tipo
        $modulos_3led = array();
        $modulos_6led = array();
        $modulos_12led = array();
        $modulos_alta = array();
        $strover = array();
        $candados = array();
        $exploradoras = array();
        $otros = array();

        foreach ($products as $p) {
            $id = $p->idProduct;
            $precio = number_format($p->price, 0, '', '.');

            if (strpos($id, '2835-') === 0) {
                $modulos_alta[] = "{$id}: {$p->description} - \${$precio}/ud";
            } elseif (strpos($id, '3LED-') === 0) {
                $modulos_3led[] = "{$id}: {$p->description} - \${$precio}/ud";
            } elseif (strpos($id, '6LED-') === 0) {
                $modulos_6led[] = "{$id}: {$p->description} - \${$precio}/ud";
            } elseif (strpos($id, '12LED-') === 0) {
                $modulos_12led[] = "{$id}: {$p->description} - \${$precio}/ud";
            } elseif (strpos($id, 'JS-COB') === 0) {
                $strover[] = "{$id}: {$p->description} - \${$precio}/ud";
            } elseif ($id === 'DISC-ALARM' || $id === 'MOTO-LOCK') {
                $candados[] = "{$id}: {$p->description} - \${$precio}";
            } elseif (strpos($id, 'ACS-') === 0) {
                $exploradoras[] = "{$id}: {$p->description} - \${$precio}";
            } elseif ($id === 'TP-012') {
                $otros[] = "{$id}: {$p->description} - \${$precio}";
            }
        }

        if (!empty($modulos_3led)) {
            $text .= "MODULOS 3LED (Disponibles):\n" . implode("\n", $modulos_3led) . "\n\n";
        }
        if (!empty($modulos_alta)) {
            $text .= "MODULOS 3LED ALTA POTENCIA (Disponibles):\n" . implode("\n", $modulos_alta) . "\n\n";
        }
        if (!empty($modulos_6led)) {
            $text .= "MODULOS 6LED (Disponibles):\n" . implode("\n", $modulos_6led) . "\n\n";
        }
        if (!empty($modulos_12led)) {
            $text .= "MODULOS 12LED (Disponibles):\n" . implode("\n", $modulos_12led) . "\n\n";
        }
        if (!empty($strover)) {
            $text .= "MODULOS STROVER (Disponibles):\n" . implode("\n", $strover) . "\n\n";
        }
        if (!empty($candados)) {
            $text .= "CANDADOS Y SEGURIDAD:\n" . implode("\n", $candados) . "\n\n";
        }
        if (!empty($otros)) {
            $text .= "OTROS PRODUCTOS:\n" . implode("\n", $otros) . "\n\n";
        }
        if (!empty($exploradoras)) {
            $text .= "EXPLORADORAS Y ACCESORIOS LED (" . count($exploradoras) . " productos):\n" . implode("\n", $exploradoras) . "\n\n";
        }

        // Productos agotados (del archivo JSON de BotImport)
        $blocked_file = APPPATH . '../blocked_products.json';
        if (file_exists($blocked_file)) {
            $blocked = json_decode(file_get_contents($blocked_file), true);
            if (!empty($blocked)) {
                $text .= "PRODUCTOS AGOTADOS (NO VENDER):\n";
                foreach ($blocked as $code) {
                    $text .= "- {$code}\n";
                }
                $text .= "\n";
            }
        }

        $text .= "--- FIN DATOS MAM ---";

        $total_products = count($modulos_3led) + count($modulos_alta) + count($modulos_6led)
                        + count($modulos_12led) + count($strover) + count($candados)
                        + count($exploradoras) + count($otros);

        echo json_encode(array(
            'success'       => true,
            'data'          => $text,
            'product_count' => $total_products,
        ));
    }

    /**
     * AJAX: Guardar prompt del asistente IA
     * POST /sisvent/admin/bots/savePrompt
     */
    public function savePrompt()
    {
        header('Content-Type: application/json');

        if (!$this->is_owner) {
            echo json_encode(array('error' => 'No autorizado'));
            return;
        }

        $bot_config_id = (int) $this->input->post('bot_config_id');
        $instructions = $this->input->post('instructions');

        $config = $this->builderbot_model->getConfig($bot_config_id);
        if (!$config) {
            echo json_encode(array('error' => 'Bot no encontrado'));
            return;
        }

        $result = $this->builderbot_lib->updateAssistantInstructions($config, $instructions);

        echo json_encode(array(
            'success' => $result['success'],
            'message' => $result['success'] ? 'Prompt actualizado en BuilderBot' : 'Error: HTTP ' . $result['http_code'],
        ));
    }

    /**
     * AJAX: Sincronizar ventas desde Google Sheet
     * POST /sisvent/admin/bots/syncSheet
     */
    public function syncSheet()
    {
        header('Content-Type: application/json');

        if (!$this->is_owner) {
            echo json_encode(array('error' => 'No autorizado'));
            return;
        }

        $bot_config_id = (int) $this->input->post('bot_config_id');
        $config = $this->builderbot_model->getConfig($bot_config_id);

        if (!$config || empty($config->sheet_id)) {
            echo json_encode(array('error' => 'Bot sin Sheet ID configurado'));
            return;
        }

        // Descargar CSV del Sheet
        $csv_url = 'https://docs.google.com/spreadsheets/d/' . $config->sheet_id
                 . '/export?format=csv&gid=' . ($config->sheet_gid ?: '0');

        $ch = curl_init($csv_url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ));
        $csv = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($csv)) {
            echo json_encode(array('error' => 'No se pudo descargar el Sheet. HTTP ' . $httpCode));
            return;
        }

        // Parsear CSV
        $lines = explode("\n", $csv);
        if (count($lines) < 2) {
            echo json_encode(array('error' => 'Sheet vacío'));
            return;
        }

        // Header: A=ID Factura, B=nombre, C=documento, D=direccion, E=productos,
        //         F=cantidad, G=voltaje, H=color, I=celular, J=total, K=fecha,
        //         L=vendedor, M=guia, N=TipoEnvio, ...Q=MensajeGuia, R=MySQL
        $header = str_getcsv(array_shift($lines));

        // Cargar modelos necesarios para process_webhook_sale (via BotImport)
        $this->load->model('clients_model');
        $this->load->model('budgets_model');
        $this->load->model('products_model');
        $this->load->model('inventory_model');
        $this->load->model('dropshipping_model');

        $synced = 0;
        $errors = array();
        $skipped = 0;

        $cutoff_date = date('Y-m-d', strtotime('-3 days'));

        foreach ($lines as $idx => $line) {
            if (empty(trim($line))) continue;

            $cols = str_getcsv($line);
            if (count($cols) < 10) continue;

            $nombre    = isset($cols[1]) ? trim($cols[1]) : '';
            $documento = isset($cols[2]) ? trim($cols[2]) : '';
            $fecha_raw = isset($cols[10]) ? trim($cols[10]) : '';

            // Saltar si no tiene datos mínimos
            if (empty($nombre) || empty($documento)) {
                $skipped++;
                continue;
            }

            // Normalizar fecha (soporta: "2026-04-02 18:22:25", "3/4/2026", "Saturday,", etc.)
            $fecha = '';
            if (!empty($fecha_raw)) {
                $ts = strtotime($fecha_raw);
                if ($ts !== false) {
                    $fecha = date('Y-m-d', $ts);
                }
            }

            // Solo importar ventas recientes (últimos 2 días)
            if (empty($fecha) || $fecha < $cutoff_date) {
                $skipped++;
                continue;
            }

            // Limpiar documento (quitar "CC", "cc", espacios)
            $documento = preg_replace('/^(cc|CC|ce|CE)\s*/i', '', $documento);
            $documento = trim($documento);

            // Anti-duplicado: hash único por documento+fecha+total
            $total_val = isset($cols[9]) ? trim($cols[9]) : '0';
            $sync_hash = md5($documento . '|' . $fecha . '|' . $total_val);

            $already_synced = $this->db->where('raw_payload LIKE', '%"sync_hash":"' . $sync_hash . '"%')
                ->where('bot_config_id', $bot_config_id)
                ->count_all_results('builderbot_webhooks');
            if ($already_synced > 0) {
                $skipped++;
                continue;
            }

            // Construir datos de la fila
            $row_data = array(
                'nombre'    => $nombre,
                'documento' => $documento,
                'direccion' => isset($cols[3]) ? trim($cols[3]) : '',
                'productos' => isset($cols[4]) ? trim($cols[4]) : '',
                'cantidad'  => isset($cols[5]) ? trim($cols[5]) : '',
                'voltaje'   => isset($cols[6]) ? trim($cols[6]) : '12V',
                'color'     => isset($cols[7]) ? trim($cols[7]) : '',
                'celular'   => isset($cols[8]) ? trim($cols[8]) : '',
                'total'     => isset($cols[9]) ? floatval($cols[9]) : 0,
                'fecha'     => $fecha,
                'vendedor'  => isset($cols[11]) ? trim($cols[11]) : '',
                'tipoenvio' => isset($cols[13]) ? trim($cols[13]) : 'Gratis',
                'row_index' => $idx + 2,
                'sync_hash' => $sync_hash,
            );

            // Enviar al endpoint de sincronización
            $result = $this->_processSheetRow($row_data, $config);

            if ($result['success']) {
                $synced++;
            } else {
                $errors[] = 'Fila ' . ($idx + 2) . ': ' . $result['error'];
            }
        }

        echo json_encode(array(
            'success' => true,
            'synced'  => $synced,
            'skipped' => $skipped,
            'errors'  => $errors,
            'message' => $synced . ' ventas sincronizadas, ' . $skipped . ' ya existían, ' . count($errors) . ' errores',
        ));
    }

    /**
     * Procesa una fila del Sheet internamente
     */
    private function _processSheetRow($row_data, $botConfig)
    {
        // Convertir productos del sheet a código
        $productos = $this->_sheetRowToProducts($row_data);

        if (empty($productos)) {
            return array('success' => false, 'error' => 'No se pudo resolver producto: ' . $row_data['productos'] . ' / ' . $row_data['color']);
        }

        // Resolver vendedor
        $vendor_id = $this->_resolveVendor($row_data['vendedor']);

        $sale_data = array(
            'nombre'    => $row_data['nombre'],
            'documento' => $row_data['documento'],
            'celular'   => $row_data['celular'],
            'email'     => '',
            'direccion' => $row_data['direccion'],
            'tipoenvio' => $row_data['tipoenvio'],
            'productos' => $productos,
        );

        // Insertar en cola
        $this->db->insert('bot_sales_queue', array(
            'payload'   => json_encode($sale_data),
            'status'    => 'processing',
            'vendor_id' => $vendor_id,
            'api_key'   => 'sheet-sync',
        ));
        $queue_id = $this->db->insert_id();

        // Crear presupuesto usando la lógica de BotImport
        $this->load->model('clients_model');
        $this->load->model('budgets_model');
        $this->load->model('products_model');
        $this->load->model('dropshipping_model');

        // Procesar la venta directamente
        $result = $this->_createBudgetFromSheet($sale_data, $vendor_id);

        if ($result['success']) {
            $this->db->where('id', $queue_id)->update('bot_sales_queue', array(
                'status'       => 'completed',
                'budget_id'    => $result['budget_id'],
                'processed_at' => date('Y-m-d H:i:s'),
            ));

            // Log en webhooks para dashboard
            $this->builderbot_model->saveWebhook(array(
                'bot_config_id' => $botConfig->id,
                'event_type'    => 'sale',
                'raw_payload'   => json_encode($row_data),
                'status'        => 'processed',
                'queue_id'      => $queue_id,
            ));

            return array('success' => true, 'budget_id' => $result['budget_id']);
        } else {
            $this->db->where('id', $queue_id)->update('bot_sales_queue', array(
                'status'        => 'failed',
                'error_message' => $result['error'],
                'attempts'      => 1,
                'processed_at'  => date('Y-m-d H:i:s'),
            ));

            return $result;
        }
    }

    /**
     * Crea un presupuesto a partir de datos del Sheet
     */
    private function _createBudgetFromSheet($data, $vendor_id)
    {
        try {
            date_default_timezone_set("America/Bogota");

            // Parsear dirección
            $parts = explode(',', $data['direccion']);
            $city = count($parts) >= 2 ? trim($parts[count($parts) - 2]) : '';
            $state = count($parts) >= 3 ? trim(end($parts)) : '';
            $full_address = $data['direccion'];

            // Buscar o crear cliente
            $client = $this->clients_model->getClientByIdNum($data['documento']);

            if (empty($client)) {
                $client_data = array(
                    'idNum' => $data['documento'],
                    'name' => $data['nombre'],
                    'email' => '',
                    'phone' => $data['celular'],
                    'cellphone' => $data['celular'],
                    'address' => $full_address,
                    'city' => $city,
                    'state' => $state,
                    'vendor' => $vendor_id,
                    'retail' => 1,
                    'rate' => 0,
                    'f_id' => $this->clients_model->getHighestClientFid()->next_fid + 1,
                );
                $this->clients_model->save($client_data);
                $client_id = $this->db->insert_id();
            } else {
                $client_id = $client->idClient;
                $update = array();
                if (!empty($data['celular'])) $update['cellphone'] = $data['celular'];
                if (!empty($full_address)) $update['address'] = $full_address;
                if (!empty($update)) $this->clients_model->update($client_id, $update);
            }

            // Calcular totales
            $total = 0;
            $product_lines = array();
            foreach ($data['productos'] as $prod) {
                $codigo = strtoupper(trim($prod['codigo']));
                $cantidad = intval($prod['cantidad']);
                $precio = floatval($prod['precio']);

                $db_product = $this->products_model->getProduct($codigo);
                if (empty($db_product)) {
                    return array('success' => false, 'error' => "Producto no encontrado: {$codigo}");
                }

                $line_total = $precio * $cantidad;
                $total += $line_total;
                $product_lines[] = array(
                    'codigo' => $codigo,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'line_total' => $line_total,
                );
            }

            // Comentarios
            $prod_desc = array();
            foreach ($product_lines as $p) {
                $prod_desc[] = $p['codigo'] . ' x' . $p['cantidad'];
            }
            $comments = 'SHEET SYNC | ' . implode(', ', $prod_desc);
            if (!empty($data['direccion'])) $comments .= ' | Dir: ' . $data['direccion'];
            if (!empty($data['celular'])) $comments .= ' | Tel: ' . $data['celular'];

            // Crear presupuesto (usar fecha del Sheet si existe, si no la actual en zona Bogotá)
            date_default_timezone_set("America/Bogota");
            $budget_date = !empty($data['fecha']) ? $data['fecha'] : date('Y-m-d H:i:s');
            // Normalizar fecha
            $ts = strtotime($budget_date);
            $budget_date = $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');

            $budget_data = array(
                'clientId' => $client_id,
                'vendorId' => $vendor_id,
                'storeId' => 1,
                'total' => $total,
                'date' => $budget_date,
                'state' => 0,
                'e_commerce' => 1,
                'list_price' => 0,
                'hasIva' => 0,
                'iva' => 8,
                'comments' => $comments,
            );

            $this->budgets_model->save($budget_data);
            $budget_id = $this->budgets_model->lastID();

            // Detalle
            foreach ($product_lines as $p) {
                $this->budgets_model->save_detail(array(
                    'budgetId' => $budget_id,
                    'productId' => $p['codigo'],
                    'quantity' => $p['cantidad'],
                    'unit' => $p['precio'],
                    'base' => $p['precio'],
                    'total' => $p['line_total'],
                ));
            }

            return array('success' => true, 'budget_id' => $budget_id);

        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    // Color map (mismo que BotImport)
    private $color_map = array(
        'azul hielo' => 'I', 'azul ice' => 'I', 'ice' => 'I', 'hielo' => 'I',
        'azul oscuro' => 'E', 'azul' => 'E', 'blue' => 'E',
        'rojo' => 'C', 'red' => 'C',
        'verde' => 'F', 'green' => 'F',
        'amarillo' => 'D', 'yellow' => 'D',
        'blanco calido' => 'B', 'warm white' => 'B',
        'blanco' => 'A', 'white' => 'A',
        'rosado' => 'G', 'fucsia' => 'G', 'pink' => 'G',
        'morado' => 'H', 'purple' => 'H',
        'verde limon' => 'J', 'limon' => 'J',
        'verde turquesa' => 'K', 'turquesa' => 'K',
    );

    private $vendor_map = array(
        'germam medellin' => '1234567', 'germam medellín' => '1234567',
        'germam bogota' => '12345678', 'bogota' => '12345678',
        'germam barranquilla' => '1048937562', 'barranquilla' => '1048937562',
    );

    private $product_map = array(
        'aspiradora' => 'TP-012',
        'candado' => 'DISC-ALARM',
    );

    /**
     * Convierte columnas del Sheet a array de productos.
     * Soporta dos formatos:
     *   1. Directo: "[6LED-24V-D,40,80000]" o "[DISC-ALARM,1,55000]" o múltiples "[código,cant,precio],[código,cant,precio]"
     *   2. Columnas separadas: productos="modulos 3 LED", cantidad="40", voltaje="24V", color="Azul hielo"
     */
    private function _sheetRowToProducts($row)
    {
        $productos_text = trim($row['productos']);
        $total = floatval($row['total']);

        // ── Formato 1: [código,cantidad,precio] ──
        // Ejemplo: "[6LED-24V-D,40,80000]" o "[DISC-ALARM,1,55000],[JS-COB-4-E,10,45000]"
        if (preg_match('/\[/', $productos_text)) {
            $products = array();
            // Extraer todos los bloques [código,cantidad,precio]
            preg_match_all('/\[([^\]]+)\]/', $productos_text, $matches);

            if (!empty($matches[1])) {
                foreach ($matches[1] as $block) {
                    $parts = explode(',', $block);
                    if (count($parts) >= 2) {
                        $codigo = strtoupper(trim($parts[0]));
                        $cantidad = intval(trim($parts[1]));
                        $precio = isset($parts[2]) ? floatval(trim($parts[2])) : 0;

                        if ($cantidad > 0 && $precio > 0) {
                            // El precio en el sheet es el total de la línea, calcular unitario
                            $precio_unit = round($precio / $cantidad);
                            $products[] = array('codigo' => $codigo, 'cantidad' => $cantidad, 'precio' => $precio_unit);
                        }
                    }
                }
            }

            if (!empty($products)) return $products;
        }

        // ── Formato 2: Columnas separadas ──
        $productos_lower = strtolower($productos_text);
        $cantidad_text = trim($row['cantidad']);
        $voltaje_text = strtolower(trim($row['voltaje'] ?: '12v'));
        $color_text = strtolower(trim($row['color']));

        preg_match('/(\d+)/', $cantidad_text, $cant_match);
        $cantidad = isset($cant_match[1]) ? intval($cant_match[1]) : 1;

        preg_match('/(\d+)\s*v/i', $voltaje_text, $volt_match);
        $voltaje = isset($volt_match[1]) ? $volt_match[1] . 'V' : '12V';

        // Productos especiales
        foreach ($this->product_map as $keyword => $code) {
            if (strpos($productos_lower, $keyword) !== false) {
                $precio = $cantidad > 0 ? round($total / $cantidad) : $total;
                return array(array('codigo' => $code, 'cantidad' => $cantidad, 'precio' => $precio));
            }
        }

        // Extraer LEDs
        preg_match('/(\d+)\s*led/i', $productos_lower, $led_match);
        $num_leds = isset($led_match[1]) ? $led_match[1] : '';

        if (empty($num_leds)) {
            preg_match('/modulos?\s*(\d+)|(\d+)\s*modulos?/i', $productos_lower, $mod_match);
            $num_leds = isset($mod_match[1]) && $mod_match[1] ? $mod_match[1] : (isset($mod_match[2]) ? $mod_match[2] : '');
        }

        if (empty($num_leds) || empty($color_text)) return array();

        $color_letter = 'E';
        foreach ($this->color_map as $name => $letter) {
            if (strpos($color_text, $name) !== false) {
                $color_letter = $letter;
                break;
            }
        }

        $codigo = $num_leds . 'LED-' . $voltaje . '-' . $color_letter;
        $precio = $cantidad > 0 ? round($total / $cantidad) : $total;

        return array(array('codigo' => $codigo, 'cantidad' => $cantidad, 'precio' => $precio));
    }

    private function _resolveVendor($vendedor_text)
    {
        $text = strtolower(trim($vendedor_text));
        $trans = array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n');
        $text = strtr($text, $trans);

        foreach ($this->vendor_map as $key => $id) {
            if (strpos($text, $key) !== false) return $id;
        }
        return '1234567'; // default GerMAM Medellín
    }

    private function _requireOwner()
    {
        if (!$this->is_owner) {
            $this->session->set_flashdata('bots_error', 'Solo el propietario puede acceder a esta sección.');
            redirect(base_url() . 'sisvent/admin/bots');
        }
    }
}
