<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bots extends CI_Controller {

    private $is_owner = false;

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1]); // superadmin

        // Agotados (CRUD de productos bloqueados) lo puede gestionar cualquier admin,
        // sin requerir el flag bots_access. El resto del módulo Bots sí lo requiere.
        $agotados_methods = ['agotados', 'uploadAgotados', 'removeAgotado', 'removeAgotadoByCode', 'clearAgotados', 'addAgotado', 'syncAgotadosToBots'];
        if (!in_array($this->router->fetch_method(), $agotados_methods, true)) {
            $this->backend_lib->controlBotsAccess();
        }

        $this->load->model('builderbot_model');
        $this->load->library('builderbot_lib');

        $user_data = $this->session->userdata('user_data');
        $this->is_owner = !empty($user_data['bots_access']);
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

        // Inventario de módulos LED para mapa de stock
        $led_stock = $this->db->select('inv.idProduct, SUM(inv.stock) as total_stock')
            ->from('inventory inv')
            ->where('inv.idStore IN (1, 8)')
            ->group_start()
                ->like('inv.idProduct', 'LED-', 'both')
                ->or_like('inv.idProduct', '2835-', 'after')
            ->group_end()
            ->group_by('inv.idProduct')
            ->get()->result();

        // Organizar por familia-voltaje y color
        $color_map = array(
            'A' => array('name' => 'Blanco', 'hex' => '#FFFFFF', 'border' => '#ccc'),
            'B' => array('name' => 'B. Calido', 'hex' => '#FFF3E0', 'border' => '#e6c9a0'),
            'C' => array('name' => 'Rojo', 'hex' => '#EF4444', 'border' => '#dc2626'),
            'D' => array('name' => 'Amarillo', 'hex' => '#FBBF24', 'border' => '#d97706'),
            'E' => array('name' => 'Azul', 'hex' => '#3B82F6', 'border' => '#2563eb'),
            'F' => array('name' => 'Verde', 'hex' => '#22C55E', 'border' => '#16a34a'),
            'G' => array('name' => 'Rosado', 'hex' => '#F472B6', 'border' => '#db2777'),
            'H' => array('name' => 'Morado', 'hex' => '#A855F7', 'border' => '#7c3aed'),
            'I' => array('name' => 'Azul Ice', 'hex' => '#67E8F9', 'border' => '#06b6d4'),
            'J' => array('name' => 'Vde Limon', 'hex' => '#A3E635', 'border' => '#65a30d'),
            'K' => array('name' => 'Turquesa', 'hex' => '#2DD4BF', 'border' => '#0d9488'),
        );

        $families = array('3LED-12V', '3LED-24V', '6LED-12V', '6LED-24V', '12LED-12V', '12LED-24V', '2835-12V', '2835-24V');
        $stock_matrix = array();
        foreach ($families as $f) $stock_matrix[$f] = array();

        foreach ($led_stock as $row) {
            $parts = explode('-', $row->idProduct);
            if (count($parts) < 3) continue;
            $colorLetter = end($parts);
            if (!isset($color_map[$colorLetter])) continue;

            // Build family key
            array_pop($parts);
            $family = implode('-', $parts);
            if (!isset($stock_matrix[$family])) continue;

            $stock_matrix[$family][$colorLetter] = (int) $row->total_stock;
        }

        $data = array(
            'bots'         => $bots,
            'is_owner'     => $this->is_owner,
            'stock_matrix' => $stock_matrix,
            'color_map'    => $color_map,
            'families'     => $families,
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
            'answer_id'         => $this->input->post('answer_id') ?: null,
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
    /**
     * Reporte de efectividad
     * GET /sisvent/admin/bots/report/{bot_config_id}
     * Si bot_config_id = 0 o null, muestra todos los bots
     */
    public function report($bot_config_id = null)
    {
        $from = $this->input->get('from') ?: date('Y-m-d', strtotime('-30 days'));
        $to = $this->input->get('to') ?: date('Y-m-d');

        if ($bot_config_id && $bot_config_id != '0') {
            $config = $this->builderbot_model->getConfig($bot_config_id);
            if (!$config) redirect(base_url() . 'sisvent/admin/bots');
            $report = $this->builderbot_model->getEffectivenessReport($config->default_vendor_id, $from, $to);
            $bot_reports = array(array('config' => $config, 'data' => $report));
        } else {
            $bot_reports = $this->builderbot_model->getAllBotsReport($from, $to);
        }

        // Totales consolidados
        $totals = array(
            'ventas_bot' => 0, 'total_ventas' => 0, 'facturas' => 0, 'total_facturado' => 0,
            'pagos' => 0, 'total_recaudado' => 0, 'envios' => 0, 'costo_flete' => 0, 'margen_neto' => 0,
        );
        foreach ($bot_reports as $br) {
            foreach ($totals as $k => &$v) {
                if (isset($br['data'][$k])) $v += $br['data'][$k];
            }
        }
        $totals['conversion'] = $totals['ventas_bot'] > 0 ? round(($totals['facturas'] / $totals['ventas_bot']) * 100, 1) : 0;
        $totals['efectividad'] = $totals['total_facturado'] > 0 ? round(($totals['total_recaudado'] / $totals['total_facturado']) * 100, 1) : 0;

        // Tabla comparativa mensual (últimos 6 meses)
        $monthly = array();
        $vendor_id_for_monthly = null;
        if ($bot_config_id && $bot_config_id != '0' && isset($config)) {
            $vendor_id_for_monthly = $config->default_vendor_id;
        }

        for ($i = 5; $i >= 0; $i--) {
            $m_from = date('Y-m-01', strtotime("-{$i} months"));
            $m_to = date('Y-m-t', strtotime("-{$i} months"));
            $m_label = date('M Y', strtotime($m_from));

            if ($vendor_id_for_monthly) {
                $m_data = $this->builderbot_model->getEffectivenessReport($vendor_id_for_monthly, $m_from, $m_to);
            } else {
                // Consolidar todos los bots
                $all = $this->builderbot_model->getAllBotsReport($m_from, $m_to);
                $m_data = array('ventas_bot'=>0,'total_ventas'=>0,'facturas'=>0,'total_facturado'=>0,'pagos'=>0,'total_recaudado'=>0,'envios'=>0,'costo_flete'=>0,'margen_neto'=>0);
                foreach ($all as $a) {
                    foreach ($m_data as $k => &$v) {
                        if (isset($a['data'][$k])) $v += $a['data'][$k];
                    }
                }
                $m_data['conversion'] = $m_data['ventas_bot'] > 0 ? round(($m_data['facturas']/$m_data['ventas_bot'])*100,1) : 0;
                $m_data['efectividad'] = $m_data['total_facturado'] > 0 ? round(($m_data['total_recaudado']/$m_data['total_facturado'])*100,1) : 0;
            }

            $monthly[] = array('label' => $m_label, 'data' => $m_data);
        }

        $data = array(
            'bot_reports' => $bot_reports,
            'totals'      => $totals,
            'monthly'     => $monthly,
            'from'        => $from,
            'to'          => $to,
            'all_configs' => $this->builderbot_model->getConfigs(true),
            'selected_bot' => $bot_config_id ?: '0',
            'is_owner'    => $this->is_owner,
        );
        $this->load->view('sisvent/admin/bots/report', $data);
    }

    /**
     * Reporte de campañas Meta Ads
     * GET /sisvent/admin/bots/ads
     */
    public function ads()
    {
        $this->load->library('meta_ads_lib');

        $from = $this->input->get('from') ?: date('Y-m-d', strtotime('-30 days'));
        $to = $this->input->get('to') ?: date('Y-m-d');

        // Período anterior (mismo nº de días, justo antes del actual)
        $diffDays = max(1, (strtotime($to) - strtotime($from)) / 86400 + 1);
        $prevTo = date('Y-m-d', strtotime($from . ' -1 day'));
        $prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . ($diffDays - 1) . ' days'));

        // Obtener campañas
        $campaignsResult = $this->meta_ads_lib->getCampaigns();
        $campaigns = isset($campaignsResult['data']) ? $campaignsResult['data'] : array();

        // Obtener insights (período actual)
        $insightsResult = $this->meta_ads_lib->getCampaignInsights($from, $to);
        $insights = isset($insightsResult['data']) ? $insightsResult['data'] : array();

        // Insights del período anterior (para deltas)
        $prevInsightsResult = $this->meta_ads_lib->getCampaignInsights($prevFrom, $prevTo);
        $prevInsights = isset($prevInsightsResult['data']) ? $prevInsightsResult['data'] : array();
        $prevTotals = array('spend' => 0, 'conversations' => 0, 'impressions' => 0, 'clicks' => 0);
        foreach ($prevInsights as $pi) {
            $prevTotals['spend']        += (float)($pi['spend'] ?? 0);
            $prevTotals['impressions']  += (int)($pi['impressions'] ?? 0);
            $prevTotals['clicks']       += (int)($pi['clicks'] ?? 0);
            $prevTotals['conversations'] += $this->meta_ads_lib->extractConversations($pi['actions'] ?? array());
        }

        // Daily insights para gráfica de tendencia
        $dailyResult = $this->meta_ads_lib->getAccountDailyInsights($from, $to);
        $dailyRaw = isset($dailyResult['data']) ? $dailyResult['data'] : array();
        $daily = array();
        foreach ($dailyRaw as $d) {
            $daily[] = array(
                'date'         => $d['date_start'] ?? '',
                'spend'        => (float)($d['spend'] ?? 0),
                'impressions'  => (int)($d['impressions'] ?? 0),
                'clicks'       => (int)($d['clicks'] ?? 0),
                'conversations' => $this->meta_ads_lib->extractConversations($d['actions'] ?? array()),
            );
        }

        // Indexar insights por campaign_id
        $insightsBycamp = array();
        foreach ($insights as $ins) {
            $insightsBycamp[$ins['campaign_id']] = $ins;
        }

        // Combinar campañas con insights
        $report = array();
        $totals = array('impressions' => 0, 'clicks' => 0, 'spend' => 0, 'conversations' => 0);

        foreach ($campaigns as $c) {
            $ins = isset($insightsBycamp[$c['id']]) ? $insightsBycamp[$c['id']] : null;
            $conversations = $ins ? $this->meta_ads_lib->extractConversations(isset($ins['actions']) ? $ins['actions'] : array()) : 0;
            $costPerConv = $ins ? $this->meta_ads_lib->extractCostPerConversation(isset($ins['cost_per_action_type']) ? $ins['cost_per_action_type'] : array()) : 0;

            $impressions = $ins ? (int)$ins['impressions'] : 0;
            $clicks = $ins ? (int)$ins['clicks'] : 0;
            $spend = $ins ? (float)$ins['spend'] : 0;

            $report[] = array(
                'id'             => $c['id'],
                'name'           => $c['name'],
                'status'         => $c['status'],
                'objective'      => isset($c['objective']) ? $c['objective'] : '',
                'impressions'    => $impressions,
                'clicks'         => $clicks,
                'spend'          => $spend,
                'cpc'            => $ins ? (float)$ins['cpc'] : 0,
                'cpm'            => $ins ? (float)$ins['cpm'] : 0,
                'ctr'            => $ins ? (float)$ins['ctr'] : 0,
                'conversations'  => $conversations,
                'cost_per_conv'  => $costPerConv,
            );

            $totals['impressions'] += $impressions;
            $totals['clicks'] += $clicks;
            $totals['spend'] += $spend;
            $totals['conversations'] += $conversations;
        }

        $totals['ctr'] = $totals['impressions'] > 0 ? round(($totals['clicks'] / $totals['impressions']) * 100, 2) : 0;
        $totals['cost_per_conv'] = $totals['conversations'] > 0 ? round($totals['spend'] / $totals['conversations'], 0) : 0;

        // === VENTAS REALES: cruzar campañas con presupuestos por vendedor ===
        // Cargar bots dinámicamente (id, name, default_vendor_id) desde DB
        $bots = $this->db->select('id, name, default_vendor_id')
            ->from('builderbot_configs')
            ->get()->result();

        // Construir keywords → vendor_id desde nombre del bot.
        // Asume que el último token del nombre es la ciudad (Medellín, Barranquilla, Bogota...).
        // Adicional: alias 'julian' → bot Bogotá (legacy de antes de la unificación).
        $vendorMap = array();
        $vendorLabel = array();
        $defaultVendor = null;
        $defaultLabel = 'General';
        foreach ($bots as $b) {
            $tokens = preg_split('/\s+/', trim($b->name));
            $city = end($tokens);
            $cityLower = mb_strtolower(strtr($city, array('í'=>'i','ó'=>'o','á'=>'a','é'=>'e','ú'=>'u')));
            $vendorMap[$cityLower] = $b->default_vendor_id;
            $vendorLabel[$b->default_vendor_id] = $city;
            // El bot de Medellín se trata como default
            if (strpos($cityLower, 'medellin') !== false) {
                $defaultVendor = $b->default_vendor_id;
                $defaultLabel = $city;
            }
        }
        // Alias legacy: campañas con "julian" en el nombre van al bot Bogotá
        if (isset($vendorMap['bogota'])) $vendorMap['julian'] = $vendorMap['bogota'];
        // Si no encontramos Medellín, usar el primero como default
        if (!$defaultVendor && !empty($bots)) {
            $defaultVendor = $bots[0]->default_vendor_id;
            $defaultLabel = $bots[0]->name;
        }

        // Asignar vendedor a cada campaña por keyword en el nombre
        foreach ($report as &$r) {
            $nameL = mb_strtolower(strtr($r['name'], array('í'=>'i','ó'=>'o','á'=>'a','é'=>'e','ú'=>'u')));
            $r['vendor_id'] = $defaultVendor;
            $r['vendor_label'] = $defaultLabel;
            foreach ($vendorMap as $keyword => $vid) {
                if ($keyword === '') continue;
                if (strpos($nameL, $keyword) !== false) {
                    $r['vendor_id'] = $vid;
                    $r['vendor_label'] = $vendorLabel[$vid] ?? $keyword;
                    break;
                }
            }
        }
        unset($r);

        // Lista única de vendor IDs para queries
        $botVendors = array_values(array_unique(array_filter(array_column($bots, 'default_vendor_id'))));
        if (empty($botVendors)) $botVendors = array($defaultVendor);

        // Consultar PRESUPUESTOS por vendedor bot en el rango de fechas (cotizado)
        $this->load->model('budgets_model');
        $this->db->select('b.vendorId, COUNT(b.idBudget) as total_pedidos, COALESCE(SUM(b.total),0) as total_ventas');
        $this->db->from('budgets b');
        $this->db->where('b.deleted', 0);
        $this->db->where('b.date >=', $from . ' 00:00:00');
        $this->db->where('b.date <=', $to . ' 23:59:59');
        $this->db->where_in('b.vendorId', $botVendors);
        $this->db->group_by('b.vendorId');
        $salesRows = $this->db->get()->result();

        $salesByVendor = array();
        foreach ($salesRows as $s) {
            $salesByVendor[$s->vendorId] = array(
                'pedidos' => (int)$s->total_pedidos,
                'ventas'  => (int)$s->total_ventas,
            );
        }

        // Calcular gasto total por vendedor para prorratear ventas
        $spendByVendor = array();
        foreach ($report as $r) {
            if (!isset($spendByVendor[$r['vendor_id']])) $spendByVendor[$r['vendor_id']] = 0;
            $spendByVendor[$r['vendor_id']] += $r['spend'];
        }

        // Asignar ventas a cada campaña proporcionalmente al gasto
        $totals['ventas'] = 0;
        $totals['pedidos'] = 0;
        foreach ($report as &$r) {
            $vid = $r['vendor_id'];
            $vendorSales = isset($salesByVendor[$vid]) ? $salesByVendor[$vid] : array('pedidos' => 0, 'ventas' => 0);
            $vendorSpend = isset($spendByVendor[$vid]) ? $spendByVendor[$vid] : 0;

            if ($vendorSpend > 0 && $r['spend'] > 0) {
                $ratio = $r['spend'] / $vendorSpend;
                $r['ventas'] = round($vendorSales['ventas'] * $ratio);
                $r['pedidos'] = round($vendorSales['pedidos'] * $ratio);
            } else {
                $r['ventas'] = 0;
                $r['pedidos'] = 0;
            }
            // ROAS = Ventas / Inversión (retorno por cada peso invertido)
            $r['roas'] = $r['spend'] > 0 ? round($r['ventas'] / $r['spend'], 1) : 0;
            // ROI = (Ganancia bruta - Inversión) / Inversión × 100
            // Margen bruto = 52.7% → ganancia = ventas × 0.527
            $ganancia = $r['ventas'] * 0.527;
            $r['roi'] = $r['spend'] > 0 ? round((($ganancia - $r['spend']) / $r['spend']) * 100, 1) : 0;
            $totals['ventas'] += $r['ventas'];
            $totals['pedidos'] += $r['pedidos'];
        }
        unset($r);
        $totalGanancia = $totals['ventas'] * 0.527;
        $totals['roas'] = $totals['spend'] > 0 ? round($totals['ventas'] / $totals['spend'], 1) : 0;
        $totals['roi'] = $totals['spend'] > 0 ? round((($totalGanancia - $totals['spend']) / $totals['spend']) * 100, 1) : 0;
        $totals['cpc'] = $totals['clicks'] > 0 ? round($totals['spend'] / $totals['clicks'], 0) : 0;
        $totals['cpm'] = $totals['impressions'] > 0 ? round(($totals['spend'] / $totals['impressions']) * 1000, 0) : 0;

        // Funnel: tasas de conversión entre etapas
        $funnel = array(
            'impressions'  => $totals['impressions'],
            'clicks'       => $totals['clicks'],
            'conversations' => $totals['conversations'],
            'pedidos'      => $totals['pedidos'],
            'ventas'       => $totals['ventas'],
            'ctr'          => $totals['impressions'] > 0 ? round($totals['clicks'] / $totals['impressions'] * 100, 2) : 0,
            'click_to_conv' => $totals['clicks'] > 0 ? round($totals['conversations'] / $totals['clicks'] * 100, 1) : 0,
            'conv_to_order' => $totals['conversations'] > 0 ? round($totals['pedidos'] / $totals['conversations'] * 100, 1) : 0,
        );

        // Deltas vs período anterior (% cambio)
        $delta = function($cur, $prev) {
            if ($prev <= 0) return $cur > 0 ? 100 : 0;
            return round((($cur - $prev) / $prev) * 100, 1);
        };
        $compare = array(
            'spend'         => array('prev' => $prevTotals['spend'],         'delta' => $delta($totals['spend'], $prevTotals['spend'])),
            'conversations' => array('prev' => $prevTotals['conversations'], 'delta' => $delta($totals['conversations'], $prevTotals['conversations'])),
            'impressions'   => array('prev' => $prevTotals['impressions'],   'delta' => $delta($totals['impressions'], $prevTotals['impressions'])),
            'clicks'        => array('prev' => $prevTotals['clicks'],        'delta' => $delta($totals['clicks'], $prevTotals['clicks'])),
        );

        // Top y peor performer (por ROI; ignoran las que no tienen gasto)
        $withSpend = array_values(array_filter($report, function($r) { return $r['spend'] > 0; }));
        usort($withSpend, function($a, $b) { return $b['roi'] <=> $a['roi']; });
        $topPerformer = $withSpend[0] ?? null;
        $worstPerformer = !empty($withSpend) ? end($withSpend) : null;

        // === PER-BOT BREAKDOWN: cruzar inversión Meta vs FACTURAS reales ===
        // Inicializar struct por bot
        $perBot = array();
        foreach ($bots as $b) {
            $perBot[$b->default_vendor_id] = array(
                'bot_id'         => $b->id,
                'bot_name'       => $b->name,
                'vendor_id'      => $b->default_vendor_id,
                'spend'          => 0,
                'campaigns_count' => 0,
                'budgets_count'  => isset($salesByVendor[$b->default_vendor_id]) ? $salesByVendor[$b->default_vendor_id]['pedidos'] : 0,
                'budgets_total'  => isset($salesByVendor[$b->default_vendor_id]) ? $salesByVendor[$b->default_vendor_id]['ventas'] : 0,
                'invoices_count' => 0,
                'invoices_total' => 0,
            );
        }

        // Sumar inversión Meta por bot (a partir de las campañas asignadas)
        foreach ($report as $r) {
            if (isset($perBot[$r['vendor_id']])) {
                $perBot[$r['vendor_id']]['spend']           += $r['spend'];
                $perBot[$r['vendor_id']]['campaigns_count'] += 1;
            }
        }

        // Consultar FACTURAS por vendor en el rango (esto es la venta real)
        if (!empty($botVendors)) {
            $this->db->select('vendorId, COUNT(*) AS cnt, COALESCE(SUM(total),0) AS total_inv', false);
            $this->db->from('invoices');
            $this->db->where('deleted', 0);
            $this->db->where('date >=', $from . ' 00:00:00');
            $this->db->where('date <=', $to . ' 23:59:59');
            $this->db->where_in('vendorId', $botVendors);
            $this->db->group_by('vendorId');
            $invRows = $this->db->get()->result();
            foreach ($invRows as $iv) {
                if (isset($perBot[$iv->vendorId])) {
                    $perBot[$iv->vendorId]['invoices_count'] = (int)$iv->cnt;
                    $perBot[$iv->vendorId]['invoices_total'] = (int)$iv->total_inv;
                }
            }
        }

        // Métricas calculadas por bot
        foreach ($perBot as &$pb) {
            $ganancia = $pb['invoices_total'] * 0.527; // margen bruto
            $pb['roi_real']         = $pb['spend'] > 0 ? round((($ganancia - $pb['spend']) / $pb['spend']) * 100, 1) : 0;
            $pb['roas_real']        = $pb['spend'] > 0 ? round($pb['invoices_total'] / $pb['spend'], 2) : 0;
            $pb['conv_rate']        = $pb['budgets_count'] > 0 ? round($pb['invoices_count'] / $pb['budgets_count'] * 100, 1) : 0;
            $pb['cost_per_invoice'] = $pb['invoices_count'] > 0 ? round($pb['spend'] / $pb['invoices_count']) : 0;
        }
        unset($pb);
        // Ordenar por ROI real descendente para que el mejor aparezca primero
        usort($perBot, function($a, $b) { return $b['roi_real'] <=> $a['roi_real']; });

        // Totales facturados (suma de los perBot)
        $totals['facturado'] = 0;
        $totals['facturas']  = 0;
        foreach ($perBot as $pb) {
            $totals['facturado'] += $pb['invoices_total'];
            $totals['facturas']  += $pb['invoices_count'];
        }
        $gananciaTotalReal = $totals['facturado'] * 0.527;
        $totals['roi_real']  = $totals['spend'] > 0 ? round((($gananciaTotalReal - $totals['spend']) / $totals['spend']) * 100, 1) : 0;
        $totals['roas_real'] = $totals['spend'] > 0 ? round($totals['facturado'] / $totals['spend'], 2) : 0;

        // Error de API
        $apiError = '';
        if (isset($campaignsResult['error'])) {
            $apiError = $campaignsResult['error']['message'];
        } elseif (isset($insightsResult['error'])) {
            $apiError = $insightsResult['error']['message'];
        }

        $data = array(
            'report'         => $report,
            'totals'         => $totals,
            'funnel'         => $funnel,
            'compare'        => $compare,
            'daily'          => $daily,
            'top_performer'  => $topPerformer,
            'worst_performer' => $worstPerformer,
            'per_bot'        => $perBot,
            'prev_from'      => $prevFrom,
            'prev_to'        => $prevTo,
            'from'           => $from,
            'to'             => $to,
            'api_error'      => $apiError,
            'is_owner'       => $this->is_owner,
        );
        $this->load->view('sisvent/admin/bots/ads_report', $data);
    }

    /**
     * AJAX: Insights diarios de una campaña para gráficas
     * GET /sisvent/admin/bots/adsDaily/{campaign_id}
     */
    public function adsDaily($campaign_id = null)
    {
        header('Content-Type: application/json');
        if (!$campaign_id) {
            echo json_encode(array('error' => 'Falta campaign_id'));
            return;
        }

        $this->load->library('meta_ads_lib');
        $from = $this->input->get('from') ?: date('Y-m-d', strtotime('-30 days'));
        $to = $this->input->get('to') ?: date('Y-m-d');

        $result = $this->meta_ads_lib->getDailyInsights($campaign_id, $from, $to);
        $data = isset($result['data']) ? $result['data'] : array();

        $daily = array();
        foreach ($data as $d) {
            $daily[] = array(
                'date'          => $d['date_start'],
                'impressions'   => (int)$d['impressions'],
                'clicks'        => (int)$d['clicks'],
                'spend'         => (float)$d['spend'],
                'conversations' => $this->meta_ads_lib->extractConversations(isset($d['actions']) ? $d['actions'] : array()),
            );
        }

        echo json_encode(array('success' => true, 'data' => $daily));
    }

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
     * AJAX: Notificar guías pendientes via WhatsApp
     * POST /sisvent/admin/bots/notifyGuides
     * Busca guías con numeroPreenvio que no se han notificado y envía WhatsApp al cliente.
     */
    public function notifyGuides()
    {
        header('Content-Type: application/json');

        if (!$this->is_owner) {
            echo json_encode(array('error' => 'No autorizado'));
            return;
        }

        // Buscar guías pendientes de notificación
        $guides = $this->db->select('sg.*, c.name as client_name, c.cellphone as client_phone, i.storeId, i.vendorId')
            ->from('shipping_guides sg')
            ->join('invoices i', 'i.idInvoice = sg.invoiceId', 'left')
            ->join('clients c', 'c.idClient = i.clientId', 'left')
            ->where('sg.numeroPreenvio IS NOT NULL')
            ->where('sg.numeroPreenvio !=', '')
            ->where('sg.whatsapp_notified', 0)
            ->order_by('sg.created_at', 'DESC')
            ->get()->result();

        if (empty($guides)) {
            echo json_encode(array('success' => true, 'sent' => 0, 'message' => 'No hay guias pendientes de notificacion'));
            return;
        }

        // Cargar bots e indexar por store_id y vendor_id
        $configs = $this->builderbot_model->getConfigs(true);
        if (empty($configs)) {
            echo json_encode(array('error' => 'No hay bots configurados'));
            return;
        }

        $bot_by_store = array();
        $bot_by_vendor = array();
        foreach ($configs as $cfg) {
            $bot_by_store[$cfg->default_store_id] = $cfg;
            $bot_by_vendor[$cfg->default_vendor_id] = $cfg;
        }
        $default_bot = $configs[0]; // fallback

        $sent = 0;
        $errors = array();

        foreach ($guides as $g) {
            // Seleccionar bot: primero por vendedor de la factura, luego por tienda, luego default
            $bot = $default_bot;
            if (!empty($g->vendorId) && isset($bot_by_vendor[$g->vendorId])) {
                $bot = $bot_by_vendor[$g->vendorId];
            } elseif (!empty($g->storeId) && isset($bot_by_store[$g->storeId])) {
                $bot = $bot_by_store[$g->storeId];
            }

            // Obtener teléfono (preferir recipientPhone, luego client_phone)
            $phone = trim($g->recipientPhone ?: $g->client_phone);
            if (empty($phone)) {
                $errors[] = "Guia {$g->numeroPreenvio}: sin telefono";
                continue;
            }

            // Formatear teléfono
            $phone = preg_replace('/\D/', '', $phone);
            if (strlen($phone) === 10 && substr($phone, 0, 1) === '3') {
                $phone = '57' . $phone;
            }

            $nombre = $g->recipientName ?: $g->client_name ?: 'Cliente';

            // Construir mensaje
            $mensaje = "Hola {$nombre}! Tu pedido de *Ledxury* ya fue enviado por *Interrapidisimo*.\n\n";
            $mensaje .= "Numero de guia: *{$g->numeroPreenvio}*\n";

            if ($g->isContrapago && $g->valorTotal > 0) {
                $mensaje .= "Valor a pagar en destino: *$" . number_format($g->valorTotal, 0, ',', '.') . "*\n";
            }

            if ($g->ciudadDestinoNombre) {
                $mensaje .= "Ciudad destino: {$g->ciudadDestinoNombre}\n";
            }

            $mensaje .= "\nRastrea tu envio en: https://www.interrapidisimo.com/rastreo/\n\n";
            $mensaje .= "Gracias por tu compra!";

            // Enviar via BuilderBot
            $result = $this->builderbot_lib->sendMessage($default_bot, $phone, $mensaje);

            if ($result['success']) {
                // Marcar como notificado
                $this->db->where('id', $g->id)->update('shipping_guides', array(
                    'whatsapp_notified' => 1,
                    'whatsapp_notified_at' => date('Y-m-d H:i:s'),
                ));

                // Log en mensajes
                $this->builderbot_model->saveMessage(array(
                    'bot_config_id' => $bot->id,
                    'direction'     => 'outgoing',
                    'phone_number'  => $phone,
                    'content'       => $mensaje,
                    'status'        => 'sent',
                    'sent_by'       => 'auto-guia',
                ));

                $sent++;
            } else {
                $errors[] = "Guia {$g->numeroPreenvio} ({$nombre}): HTTP {$result['http_code']}";
            }

            // Esperar 2 segundos entre mensajes para no saturar
            if (count($guides) > 1) sleep(2);
        }

        echo json_encode(array(
            'success' => true,
            'sent'    => $sent,
            'pending' => count($guides),
            'errors'  => $errors,
            'message' => "{$sent} clientes notificados de {$g->numeroPreenvio}" . (count($errors) > 0 ? ", " . count($errors) . " errores" : ""),
        ));
    }

    /**
     * GET: Endpoint para cron externo
     * /sisvent/admin/bots/cronNotifyGuides?key=SECRET
     */
    public function cronNotifyGuides()
    {
        $key = $this->input->get('key');
        if ($key !== 'ledxury_cron_2026') {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        // Forzar owner para el cron
        $this->is_owner = true;
        $this->notifyGuides();
    }

    /**
     * AJAX: Recuperar ventas con producto agotado
     * Lee el Sheet, busca filas con "PRODUCTO AGOTADO" en col O,
     * sin "OK" en col U, y envía WhatsApp ofreciendo alternativas.
     * POST /sisvent/admin/bots/recoverOutOfStock
     */
    public function recoverOutOfStock()
    {
        header('Content-Type: application/json');

        if (!$this->is_owner) {
            echo json_encode(array('error' => 'No autorizado'));
            return;
        }

        $bot_config_id = (int) $this->input->post('bot_config_id');

        // Si bot_config_id = 0, recorrer todos los bots con sheet_id
        if ($bot_config_id === 0) {
            $all_configs = $this->builderbot_model->getConfigs(true);
            $total_sent = 0;
            $total_skipped = 0;
            $total_errors = array();

            foreach ($all_configs as $cfg) {
                if (empty($cfg->sheet_id)) continue;
                $result = $this->_processOutOfStockForBot($cfg);
                $total_sent += $result['sent'];
                $total_skipped += $result['skipped'];
                $total_errors = array_merge($total_errors, $result['errors']);
            }

            echo json_encode(array(
                'success' => true,
                'sent' => $total_sent,
                'skipped' => $total_skipped,
                'errors' => $total_errors,
                'message' => "{$total_sent} clientes notificados sobre productos agotados",
            ));
            return;
        }

        $config = $this->builderbot_model->getConfig($bot_config_id);

        if (!$config || empty($config->sheet_id)) {
            echo json_encode(array('success' => true, 'sent' => 0, 'skipped' => 0, 'errors' => array()));
            return;
        }

        $result = $this->_processOutOfStockForBot($config);
        echo json_encode(array_merge(array('success' => true), $result));
    }

    /**
     * Procesa productos agotados para un bot específico
     */
    private function _processOutOfStockForBot($config)
    {

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
        curl_close($ch);

        if (empty($csv)) {
            return array('sent' => 0, 'skipped' => 0, 'errors' => array('No se pudo descargar Sheet de ' . $config->name));
        }

        $lines = explode("\n", $csv);
        array_shift($lines); // quitar header

        // Cargar productos agotados (del archivo JSON)
        $blocked_file = APPPATH . '../blocked_products.json';
        $blocked = array();
        if (file_exists($blocked_file)) {
            $blocked = json_decode(file_get_contents($blocked_file), true) ?: array();
        }

        $sent = 0;
        $skipped = 0;
        $errors = array();

        foreach ($lines as $idx => $line) {
            if (empty(trim($line))) continue;

            $cols = str_getcsv($line);
            $col_o = trim($cols[14] ?? ''); // Seguimiento de envio
            $col_u = strtoupper(trim($cols[20] ?? '')); // Columna U

            // Solo procesar filas con AGOTADO y sin OK en U
            if (stripos($col_o, 'AGOTADO') === false || $col_u === 'OK') {
                continue;
            }

            $nombre = trim($cols[1] ?? '');
            $celular = trim($cols[8] ?? '');
            $productos_text = trim($cols[4] ?? '');

            if (empty($nombre) || empty($celular)) {
                $skipped++;
                continue;
            }

            // Anti-duplicado: verificar en BD si ya se envió este mensaje
            $hash = md5($celular . '|agotado|' . $productos_text);
            $already = $this->db->where('content LIKE', '%' . $celular . '%agotado%')
                ->where('phone_number', $celular)
                ->where('bot_config_id', $config->id)
                ->count_all_results('builderbot_messages');
            if ($already > 0) {
                $skipped++;
                continue;
            }

            // Extraer códigos de productos agotados del texto
            $agotados = array();
            preg_match_all('/([A-Z0-9]+-[0-9]+V-[A-Z])/i', $col_o, $matches_o);
            if (!empty($matches_o[1])) {
                $agotados = array_map('strtoupper', $matches_o[1]);
            } else {
                // Intentar del campo de productos
                preg_match_all('/\[([^\],]+)/', $productos_text, $matches_p);
                if (!empty($matches_p[1])) {
                    $agotados = array_map('strtoupper', array_map('trim', $matches_p[1]));
                }
            }

            // Buscar alternativas disponibles (mismo tipo de módulo, diferente color)
            $alternativas = array();
            foreach ($agotados as $code) {
                // Extraer prefijo: "6LED-12V" de "6LED-12V-H"
                $parts = explode('-', $code);
                if (count($parts) >= 3) {
                    $prefix = $parts[0] . '-' . $parts[1]; // ej: "6LED-12V"
                    $available = $this->db->select('idProduct, description')
                        ->from('products')
                        ->like('idProduct', $prefix . '-', 'after')
                        ->where('deleted', 0)
                        ->where('idProduct !=', $code)
                        ->get()->result();

                    foreach ($available as $p) {
                        // Excluir si está en la lista de bloqueados
                        if (!in_array($p->idProduct, $blocked)) {
                            // Extraer nombre del color de la descripción
                            $desc = $p->description;
                            $color = preg_replace('/.*DC\s*/i', '', $desc);
                            $color = trim($color);
                            if ($color) {
                                $alternativas[$prefix][] = $color;
                            }
                        }
                    }
                }
            }

            // Formatear teléfono
            $phone = preg_replace('/\D/', '', $celular);
            if (strlen($phone) === 10 && substr($phone, 0, 1) === '3') {
                $phone = '57' . $phone;
            }

            // Construir mensaje
            $productos_agotados_text = implode(', ', $agotados);
            $mensaje = "Hola {$nombre}! Te escribimos de *Ledxury*.\n\n";
            $mensaje .= "Lamentamos informarte que el producto *{$productos_agotados_text}* que solicitaste se encuentra agotado.\n\n";

            if (!empty($alternativas)) {
                $mensaje .= "Pero tenemos estos colores disponibles para ti:\n";
                foreach ($alternativas as $prefix => $colores) {
                    $colores_unicos = array_unique($colores);
                    $mensaje .= "*{$prefix}:* " . implode(', ', $colores_unicos) . "\n";
                }
                $mensaje .= "\nSi deseas cambiar tu pedido por alguno de estos colores, respondenos a este mensaje y con gusto te ayudamos.\n";
            } else {
                $mensaje .= "Estamos trabajando para reponer el inventario pronto. Te avisaremos cuando este disponible.\n";
            }

            $mensaje .= "\nGracias por tu preferencia!";

            // Enviar
            $result = $this->builderbot_lib->sendMessage($config, $phone, $mensaje);

            if ($result['success']) {
                // Log
                $this->builderbot_model->saveMessage(array(
                    'bot_config_id' => $config->id,
                    'direction'     => 'outgoing',
                    'phone_number'  => $phone,
                    'content'       => $mensaje,
                    'status'        => 'sent',
                    'api_response'  => json_encode($result['response']),
                    'sent_by'       => 'auto-agotado',
                ));
                $sent++;
            } else {
                $errors[] = "Fila " . ($idx + 2) . " ({$nombre}): HTTP {$result['http_code']}";
            }

            // Esperar entre mensajes
            if ($sent > 0) sleep(2);
        }

        return array(
            'sent'    => $sent,
            'skipped' => $skipped,
            'errors'  => $errors,
            'message' => "{$sent} clientes notificados sobre productos agotados",
        );
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
        // Medellín — debe ir antes de 'germam' solo
        'germam medellin' => '1234567', 'germam medellín' => '1234567',
        'jorge cano' => '1234567', 'ledxury medellin' => '1234567',
        'bot medellin' => '1234567',
        // Barranquilla — debe ir antes de 'maria' solo
        'germam barranquilla' => '1048937562', 'maria barranquilla' => '1048937562',
        'ledxury barranquilla' => '1048937562', 'bot barranquilla' => '1048937562',
        'barranquilla' => '1048937562', 'maria' => '1048937562',
        // Bogotá
        'germam bogota' => '12345678', 'germam bogotá' => '12345678',
        'julian bogota' => '12345678', 'julian bogotá' => '12345678',
        'bot julian' => '12345678', 'ledxury bogota' => '12345678',
        'bot bogota' => '12345678', 'bogota' => '12345678', 'bogotá' => '12345678',
        'julian' => '12345678',
        // Fallback genérico: 'germam' sin ciudad = Medellín
        'germam' => '1234567',
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

    /**
     * TEST: Simular escritura de guía en Sheet + WhatsApp
     * GET /sisvent/admin/bots/testGuide
     * ELIMINAR después de probar
     */
    public function testGuide()
    {
        header('Content-Type: application/json');

        $config = $this->builderbot_model->getConfig(1); // Bot Medellín

        $result = $this->builderbot_lib->writeGuideToSheet(
            $config->sheet_id,
            '1234567890',           // documento Jorge Cano
            'PRUEBA-GUIA-001',      // guía de prueba
            $config,
            array(
                'ciudad_destino' => 'Bello',
                'es_contrapago'  => true,
                'valor_cobrar'   => 66000,
            )
        );

        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Procesar productos agotados: leer Sheet, buscar alternativas en stock,
     * enviar WhatsApp al cliente y marcar columna U.
     * POST /sisvent/admin/bots/processAgotados
     */
    public function processAgotados()
    {
        header('Content-Type: application/json');

        if (!$this->is_owner) {
            echo json_encode(array('error' => 'No autorizado'));
            return;
        }

        $config = $this->builderbot_model->getConfig(1); // Bot Medellín
        if (!$config || empty($config->sheet_id)) {
            echo json_encode(array('error' => 'Bot sin Sheet configurado'));
            return;
        }

        try {
            $credPath = APPPATH . 'config/google_sheets_credentials.json';
            $client = new \Google\Client();
            $client->setAuthConfig($credPath);
            $client->addScope(\Google\Service\Sheets::SPREADSHEETS);
            $service = new \Google\Service\Sheets($client);

            // Leer columnas A-U desde fila 2
            $range = 'Registros!A2:U1000';
            $response = $service->spreadsheets_values->get($config->sheet_id, $range);
            $rows = $response->getValues();

            if (empty($rows)) {
                echo json_encode(array('success' => true, 'sent' => 0, 'message' => 'Sheet vacío'));
                return;
            }

            $this->load->model('products_model');

            // Color reverse map: letter -> nombre en español
            $color_names = array(
                'A' => 'Blanco', 'B' => 'Blanco Cálido', 'C' => 'Rojo',
                'D' => 'Amarillo', 'E' => 'Azul', 'F' => 'Verde',
                'G' => 'Rosado', 'H' => 'Morado', 'I' => 'Azul Hielo',
            );

            $sent = 0;
            $skipped = 0;
            $errors = array();
            $details = array();

            foreach ($rows as $i => $row) {
                $rowNum = $i + 2;

                // Solo procesar desde fila 288
                if ($rowNum < 288) continue;

                $colO = isset($row[14]) ? trim($row[14]) : '';
                $colU = isset($row[20]) ? trim($row[20]) : '';
                $celular = isset($row[8]) ? trim($row[8]) : '';
                $nombre = isset($row[1]) ? trim($row[1]) : '';
                $productos = isset($row[4]) ? trim($row[4]) : '';
                $color = isset($row[7]) ? trim($row[7]) : '';

                // Solo filas con PRODUCTO AGOTADO en col O y sin marcar en col U
                if (stripos($colO, 'AGOTADO') === false) continue;
                if (!empty($colU)) { $skipped++; continue; }
                if (empty($celular)) { $skipped++; continue; }



                // Parsear productos agotados del formato [CODE,QTY,PRICE]
                $agotados = array();
                preg_match_all('/\[([^\]]+)\]/', $productos, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $m) {
                        $parts = explode(',', $m);
                        if (count($parts) >= 1) {
                            $agotados[] = array(
                                'codigo' => trim($parts[0]),
                                'cantidad' => isset($parts[1]) ? (int)$parts[1] : 0,
                                'precio' => isset($parts[2]) ? (float)$parts[2] : 0,
                            );
                        }
                    }
                }

                if (empty($agotados)) { $skipped++; continue; }

                // Buscar alternativas en stock para cada producto agotado
                $alternativas_texto = array();
                foreach ($agotados as $prod) {
                    // Extraer base del código: ej 2835-12V de 2835-12V-H
                    $codeParts = explode('-', $prod['codigo']);
                    if (count($codeParts) < 3) continue;
                    $baseCode = $codeParts[0] . '-' . $codeParts[1]; // ej: 2835-12V
                    $colorLetter = end($codeParts);
                    $colorName = isset($color_names[$colorLetter]) ? $color_names[$colorLetter] : $colorLetter;

                    // Buscar otros colores con stock
                    $alts = $this->db->select('inv.idProduct, SUM(inv.stock) as total_stock')
                        ->from('inventory inv')
                        ->where('inv.idProduct LIKE', $baseCode . '-%')
                        ->where('inv.idProduct !=', $prod['codigo'])
                        ->where('inv.idStore IN (1, 8)')
                        ->group_by('inv.idProduct')
                        ->having('total_stock >=', $prod['cantidad'])
                        ->get()->result();

                    $opciones = array();
                    foreach ($alts as $alt) {
                        $altParts = explode('-', $alt->idProduct);
                        $altLetter = end($altParts);
                        $altName = isset($color_names[$altLetter]) ? $color_names[$altLetter] : $altLetter;
                        $opciones[] = $altName . ' (' . $alt->total_stock . ' disponibles)';
                    }

                    $alternativas_texto[] = array(
                        'producto' => $prod['codigo'],
                        'color_original' => $colorName,
                        'cantidad' => $prod['cantidad'],
                        'opciones' => $opciones,
                    );
                }

                // Construir mensaje WhatsApp
                $mensaje = "Hola " . $nombre . "!\n\n"
                    . "Te escribimos de *Ledxury* sobre tu pedido.\n\n"
                    . "Lamentablemente, el color que elegiste no esta disponible en este momento:\n\n";

                foreach ($alternativas_texto as $alt) {
                    $mensaje .= "- *" . $alt['color_original'] . "* x" . $alt['cantidad'] . "\n";
                    if (!empty($alt['opciones'])) {
                        $mensaje .= "  Colores disponibles:\n";
                        foreach ($alt['opciones'] as $op) {
                            $mensaje .= "  - " . $op . "\n";
                        }
                    } else {
                        $mensaje .= "  (Sin alternativas disponibles en este momento)\n";
                    }
                }

                $mensaje .= "\nPor favor respondenos con el color que prefieras y te actualizamos el pedido.\n\n"
                    . "Gracias por tu comprension!";

                // Formatear celular
                $phone = preg_replace('/\D/', '', $celular);
                if (substr($phone, 0, 2) !== '57' && substr($phone, 0, 1) === '3') {
                    $phone = '57' . $phone;
                }

                // Enviar WhatsApp
                $sendResult = $this->builderbot_lib->sendMessage($config, $phone, $mensaje);

                if ($sendResult['success']) {
                    // Marcar OK en columna U
                    $cellU = 'Registros!U' . $rowNum;
                    $bodyU = new \Google\Service\Sheets\ValueRange(['values' => [['OK']]]);
                    $service->spreadsheets_values->update($config->sheet_id, $cellU, $bodyU, ['valueInputOption' => 'RAW']);

                    $sent++;
                    $details[] = array(
                        'row' => $rowNum,
                        'nombre' => $nombre,
                        'celular' => $phone,
                        'alternativas' => count($alternativas_texto),
                        'success' => true,
                    );
                } else {
                    $errors[] = 'Fila ' . $rowNum . ' (' . $nombre . '): HTTP ' . $sendResult['http_code'];
                    $details[] = array(
                        'row' => $rowNum,
                        'nombre' => $nombre,
                        'success' => false,
                        'error' => 'HTTP ' . $sendResult['http_code'],
                    );
                }
            }

            echo json_encode(array(
                'success' => true,
                'sent' => $sent,
                'skipped' => $skipped,
                'errors' => $errors,
                'details' => $details,
                'message' => $sent . ' mensajes enviados, ' . $skipped . ' omitidos, ' . count($errors) . ' errores',
            ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode(array('error' => $e->getMessage()));
        }
    }

    // =========================================================
    // BOT CONTROL (Hub Central)
    // =========================================================

    public function control($bot_id)
    {
        $botConfig = $this->builderbot_model->getConfig($bot_id);
        if (!$botConfig) show_404();

        $data = array(
            'bot' => $botConfig,
            'is_owner' => $this->is_owner,
            'role' => $this->session->userdata('user_data')['role'],
        );
        $this->load->view('sisvent/admin/bots/control', $data);
    }

    public function botStatus($bot_id)
    {
        header('Content-Type: application/json');
        $bot = $this->builderbot_model->getConfig($bot_id);
        if (!$bot) { echo json_encode(array('error' => 'Bot not found')); return; }
        $result = $this->builderbot_lib->getBotStatus($bot);
        echo json_encode($result);
    }

    public function botStart($bot_id)
    {
        header('Content-Type: application/json');
        $bot = $this->builderbot_model->getConfig($bot_id);
        if (!$bot) { echo json_encode(array('error' => 'Bot not found')); return; }
        echo json_encode($this->builderbot_lib->startBot($bot));
    }

    public function botStop($bot_id)
    {
        header('Content-Type: application/json');
        $bot = $this->builderbot_model->getConfig($bot_id);
        if (!$bot) { echo json_encode(array('error' => 'Bot not found')); return; }
        echo json_encode($this->builderbot_lib->stopBot($bot));
    }

    public function botQR($bot_id)
    {
        header('Content-Type: application/json');
        $bot = $this->builderbot_model->getConfig($bot_id);
        if (!$bot) { echo json_encode(array('error' => 'Bot not found')); return; }
        echo json_encode($this->builderbot_lib->getBotQR($bot));
    }

    public function botRestart($bot_id)
    {
        header('Content-Type: application/json');
        $bot = $this->builderbot_model->getConfig($bot_id);
        if (!$bot) { echo json_encode(array('error' => 'Bot not found')); return; }
        echo json_encode($this->builderbot_lib->restartBot($bot));
    }

    public function botBlacklistAdd($bot_id)
    {
        header('Content-Type: application/json');
        $bot = $this->builderbot_model->getConfig($bot_id);
        if (!$bot) { echo json_encode(array('error' => 'Bot not found')); return; }
        $numbers = $this->input->post('numbers');
        echo json_encode($this->builderbot_lib->addToBlacklist($bot, $numbers));
    }

    public function botBlacklistRemove($bot_id)
    {
        header('Content-Type: application/json');
        $bot = $this->builderbot_model->getConfig($bot_id);
        if (!$bot) { echo json_encode(array('error' => 'Bot not found')); return; }
        $numbers = $this->input->post('numbers');
        echo json_encode($this->builderbot_lib->removeFromBlacklist($bot, $numbers));
    }

    public function botBlacklistList($bot_id)
    {
        header('Content-Type: application/json');
        $bot = $this->builderbot_model->getConfig($bot_id);
        if (!$bot) { echo json_encode(array('error' => 'Bot not found')); return; }
        echo json_encode($this->builderbot_lib->getBlacklist($bot));
    }

    // =========================================================
    // PRODUCTOS AGOTADOS
    // =========================================================

    /**
     * Vista de productos agotados
     */
    public function agotados()
    {
        // Set de códigos bloqueados para lookup O(1)
        $blockedRows = $this->db->get('blocked_products')->result();
        $blockedSet = array();
        foreach ($blockedRows as $b) $blockedSet[$b->product_code] = true;

        // Catálogo: traer productos LED/2835 desde products
        $products = $this->db->select('idProduct, description')
            ->from('products')
            ->where('deleted', 0)
            ->group_start()
                ->like('idProduct', '3LED-', 'after')
                ->or_like('idProduct', '6LED-', 'after')
                ->or_like('idProduct', '12LED-', 'after')
                ->or_like('idProduct', '2835-', 'after')
            ->group_end()
            ->order_by('idProduct', 'ASC')
            ->get()->result();

        $color_map = array(
            'A' => 'Blanco', 'B' => 'B. Calido', 'C' => 'Rojo', 'D' => 'Amarillo',
            'E' => 'Azul',   'F' => 'Verde',     'G' => 'Rosado','H' => 'Morado',
            'I' => 'Azul Ice','J' => 'Vde Limon','K' => 'Turquesa',
        );

        // Agrupar por familia (reference = "{tipo}-{voltaje}")
        $catalog = array();
        foreach ($products as $p) {
            $parts = explode('-', $p->idProduct);
            if (count($parts) < 3) {
                $family = 'Otros';
                $colorLetter = '';
            } else {
                $colorLetter = end($parts);
                array_pop($parts);
                $family = implode('-', $parts);
            }

            if (!isset($catalog[$family])) $catalog[$family] = array();
            $catalog[$family][] = (object) array(
                'idProduct'   => $p->idProduct,
                'description' => $p->description,
                'color'       => isset($color_map[$colorLetter]) ? $color_map[$colorLetter] : $colorLetter,
                'color_code'  => $colorLetter,
                'is_blocked'  => isset($blockedSet[$p->idProduct]),
            );
        }

        // Orden estable de familias
        $family_order = array('3LED-12V','3LED-24V','6LED-12V','6LED-24V','12LED-12V','12LED-24V','2835-12V','2835-24V');
        uksort($catalog, function($a, $b) use ($family_order) {
            $ia = array_search($a, $family_order); $ib = array_search($b, $family_order);
            if ($ia === false) $ia = 999; if ($ib === false) $ib = 999;
            return $ia <=> $ib ?: strcmp($a, $b);
        });

        $blocked_count = count($blockedRows);
        $catalog_count = 0;
        foreach ($catalog as $items) $catalog_count += count($items);

        $data = array(
            'catalog'       => $catalog,
            'blocked_count' => $blocked_count,
            'catalog_count' => $catalog_count,
            'is_owner'      => $this->is_owner,
            'role'          => $this->session->userdata('user_data')['role'],
        );
        $this->load->view('sisvent/admin/bots/agotados', $data);
    }

    /**
     * POST: Subir Excel de agotados (formato bodega: Referencia | Color1 | Color2 | ...)
     */
    public function uploadAgotados()
    {
        $this->outh_model->CSRFVerify();

        if (empty($_FILES['agotados_file']['name'])) {
            $this->session->set_flashdata('agotados_error', 'No se seleccionó archivo');
            redirect(base_url() . 'sisvent/admin/bots/agotados');
            return;
        }

        $file = $_FILES['agotados_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['agotados_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, array('xlsx', 'xls', 'csv'))) {
            $this->session->set_flashdata('agotados_error', 'Solo Excel (.xlsx) o CSV');
            redirect(base_url() . 'sisvent/admin/bots/agotados');
            return;
        }

        $color_map = array(
            'azul hielo' => 'I', 'azul ice' => 'I', 'ice' => 'I', 'hielo' => 'I',
            'azul' => 'E', 'blue' => 'E',
            'rojo' => 'C', 'red' => 'C',
            'verde' => 'F', 'green' => 'F',
            'amarillo' => 'D', 'yellow' => 'D',
            'blanco calido' => 'B', 'blanco cálido' => 'B',
            'blanco' => 'A', 'white' => 'A',
            'rosado' => 'G', 'fucsia' => 'G', 'pink' => 'G',
            'morado' => 'H', 'purple' => 'H',
            'verde limon' => 'J', 'verde limón' => 'J', 'limon' => 'J',
            'turquesa' => 'K',
        );

        // Mapeo de referencia de la imagen de bodega a prefijo del código en BD
        $ref_map = array(
            '6led-12v' => '6LED-12V',
            '6led-24v' => '6LED-24V',
            '3led-12v' => '3LED-12V',
            '3led-24v' => '3LED-24V',
            '12led-12v' => '12LED-12V',
            '12led-24v' => '12LED-24V',
            '12led' => '12LED-12V',
            'alta potencia 12v' => '6LES-12V',
            'alta potencia 24v' => '6LES-24V',
            'alta potencia' => '6LES-12V',
            'strover' => 'STROV-12V',
            'strov' => 'STROV-12V',
        );

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $highRow = $sheet->getHighestRow();
            $highCol = $sheet->getHighestDataColumn();

            $uid = $this->session->userdata('user_data')['uname'];
            $added = 0;

            // Si el usuario eligió "reemplazar", limpiar primero
            if ($this->input->post('replace') == '1') {
                $this->db->truncate('blocked_products');
            }

            for ($row = 2; $row <= $highRow; $row++) {
                $ref_raw = trim((string)$sheet->getCell('A' . $row)->getValue());
                if (empty($ref_raw)) continue;

                $ref_lower = mb_strtolower($ref_raw);
                $ref_prefix = null;
                foreach ($ref_map as $key => $prefix) {
                    if (strpos($ref_lower, $key) !== false) {
                        $ref_prefix = $prefix;
                        break;
                    }
                }
                if (!$ref_prefix) continue;

                // Leer colores de las columnas B en adelante
                for ($col = 'B'; $col <= $highCol; $col++) {
                    $color_raw = trim((string)$sheet->getCell($col . $row)->getValue());
                    if (empty($color_raw)) continue;

                    $color_lower = mb_strtolower($color_raw);
                    $color_letter = null;
                    foreach ($color_map as $cname => $cletter) {
                        if (strpos($color_lower, $cname) !== false) {
                            $color_letter = $cletter;
                            break;
                        }
                    }
                    if (!$color_letter) continue;

                    $product_code = $ref_prefix . '-' . $color_letter;

                    // Verificar que el producto existe en BD
                    $exists = $this->db->where('idProduct', $product_code)->get('products')->row();
                    if (!$exists) continue;

                    // Insertar (ignorar duplicados)
                    $this->db->query("INSERT IGNORE INTO blocked_products (product_code, reference, color, reason, added_by) VALUES (?, ?, ?, 'agotado', ?)",
                        array($product_code, $ref_prefix, $color_raw, $uid));
                    $added++;
                }
            }

            $total = $this->db->count_all_results('blocked_products');

            // Actualizar archivo JSON para compatibilidad con BotImport
            $all_codes = array_map(function($r) { return $r->product_code; }, $this->db->get('blocked_products')->result());
            $json_path = APPPATH . 'cache/blocked_products.json';
            file_put_contents($json_path, json_encode(array_values($all_codes)));

            $this->session->set_flashdata('agotados_success', "Se agregaron {$added} productos agotados. Total actual: {$total}");
        } catch (Exception $e) {
            $this->session->set_flashdata('agotados_error', 'Error: ' . $e->getMessage());
        }

        redirect(base_url() . 'sisvent/admin/bots/agotados');
    }

    /**
     * AJAX: Eliminar un producto de agotados
     */
    public function removeAgotado()
    {
        header('Content-Type: application/json');
        $id = $this->input->post('id');
        if (!$id) { echo json_encode(array('success' => false)); return; }

        $this->db->where('id', $id)->delete('blocked_products');

        // Sync JSON
        $all_codes = array_map(function($r) { return $r->product_code; }, $this->db->get('blocked_products')->result());
        file_put_contents(APPPATH . 'cache/blocked_products.json', json_encode(array_values($all_codes)));
        $this->_syncAgotadosToBotPrompts();

        echo json_encode(array('success' => true));
    }

    /**
     * AJAX: Quitar agotado por código (usado por el toggle del catálogo)
     */
    public function removeAgotadoByCode()
    {
        header('Content-Type: application/json');
        $code = strtoupper(trim($this->input->post('code')));
        if (empty($code)) { echo json_encode(array('success' => false, 'error' => 'Código requerido')); return; }

        $this->db->where('product_code', $code)->delete('blocked_products');

        $all_codes = array_map(function($r) { return $r->product_code; }, $this->db->get('blocked_products')->result());
        file_put_contents(APPPATH . 'cache/blocked_products.json', json_encode(array_values($all_codes)));
        $this->_syncAgotadosToBotPrompts();

        echo json_encode(array('success' => true, 'csrf_hash' => $this->security->get_csrf_hash()));
    }

    /**
     * AJAX: Limpiar todos los agotados
     */
    public function clearAgotados()
    {
        header('Content-Type: application/json');
        $this->db->truncate('blocked_products');
        file_put_contents(APPPATH . 'cache/blocked_products.json', '[]');
        $this->_syncAgotadosToBotPrompts();
        echo json_encode(array('success' => true));
    }

    /**
     * AJAX: Agregar producto manualmente
     */
    public function addAgotado()
    {
        header('Content-Type: application/json');
        $code = strtoupper(trim($this->input->post('code')));
        if (empty($code)) { echo json_encode(array('success' => false, 'error' => 'Código requerido')); return; }

        $product = $this->db->where('idProduct', $code)->get('products')->row();
        if (!$product) { echo json_encode(array('success' => false, 'error' => 'Producto no encontrado: ' . $code)); return; }

        // Extraer referencia y color (ej: 6LED-12V-E → reference="6LED-12V", color="E")
        $parts = explode('-', $code);
        $colorLetter = (count($parts) >= 3) ? array_pop($parts) : null;
        $reference = (count($parts) >= 2) ? implode('-', $parts) : null;

        $uid = $this->session->userdata('user_data')['uname'];
        $this->db->query(
            "INSERT INTO blocked_products (product_code, reference, color, reason, added_by) VALUES (?, ?, ?, 'agotado', ?)
             ON DUPLICATE KEY UPDATE reason='agotado', added_by=VALUES(added_by)",
            array($code, $reference, $colorLetter, $uid)
        );

        $all_codes = array_map(function($r) { return $r->product_code; }, $this->db->get('blocked_products')->result());
        file_put_contents(APPPATH . 'cache/blocked_products.json', json_encode(array_values($all_codes)));
        $this->_syncAgotadosToBotPrompts();

        echo json_encode(array(
            'success' => true,
            'description' => $product->description,
            'csrf_hash' => $this->security->get_csrf_hash(),
        ));
    }

    /**
     * GET: /sisvent/admin/bots/syncAgotadosToBots
     * Permite forzar el sync manualmente (botón en el panel de agotados).
     */
    public function syncAgotadosToBots()
    {
        header('Content-Type: application/json');
        $result = $this->_syncAgotadosToBotPrompts();
        echo json_encode($result);
    }

    /**
     * Sincroniza la lista actual de agotados al prompt del Asistente IA de cada bot activo.
     * Inserta o reemplaza un bloque entre marcadores [AGOTADOS_INICIO] / [AGOTADOS_FIN].
     * Si no hay agotados, elimina el bloque completo.
     * Ejecuta en best-effort: si un bot falla, sigue con los demás.
     */
    private function _syncAgotadosToBotPrompts()
    {
        $this->load->library('builderbot_lib');

        // 1. Construir el bloque de agotados actual
        $rows = $this->db->select('product_code, reference, color')
            ->from('blocked_products')
            ->order_by('reference, product_code', 'ASC')
            ->get()->result();

        $block = '';
        if (!empty($rows)) {
            $color_map = array(
                'A' => 'BLANCO', 'B' => 'BLANCO CALIDO', 'C' => 'ROJO', 'D' => 'AMARILLO',
                'E' => 'AZUL',   'F' => 'VERDE',         'G' => 'ROSADO', 'H' => 'MORADO',
                'I' => 'AZUL ICE', 'J' => 'VERDE LIMON', 'K' => 'TURQUESA',
            );
            $by_ref = array();
            foreach ($rows as $r) {
                $ref = $r->reference ?: 'OTROS';
                $colorName = isset($color_map[$r->color]) ? $color_map[$r->color] : ($r->color ?: '');
                $by_ref[$ref][] = $colorName ?: $r->product_code;
            }
            $lines = array();
            $lines[] = '[AGOTADOS_INICIO]';
            $lines[] = '🚫 IMPORTANTE — Productos AGOTADOS sin disponibilidad. NO los ofrezcas y NO permitas que el cliente los pida. Si el cliente menciona alguno, sugiere alternativas (otros colores u otra referencia).';
            $lines[] = '';
            foreach ($by_ref as $ref => $colores) {
                $lines[] = '• ' . $ref . ': ' . implode(', ', $colores);
            }
            $lines[] = '[AGOTADOS_FIN]';
            $block = implode("\n", $lines);
        }

        // 2. Para cada bot activo, leer prompt actual y reemplazar/insertar el bloque
        $bots = $this->db->where('is_active', 1)->where('answer_id IS NOT NULL', null, false)->get('builderbot_configs')->result();
        $updated = 0; $errors = array();

        foreach ($bots as $bot) {
            try {
                $current = $this->builderbot_lib->getAssistantInstructions($bot);
                if ($current === null) {
                    $errors[] = "bot_id={$bot->id}: no se pudo leer el prompt";
                    continue;
                }
                // Quitar bloque viejo si existe
                $clean = preg_replace('/\n*\[AGOTADOS_INICIO\][\s\S]*?\[AGOTADOS_FIN\]\n*/u', "\n", $current);
                $clean = rtrim((string)$clean);
                // Agregar bloque nuevo (si hay)
                $newPrompt = ($block === '')
                    ? $clean
                    : $clean . "\n\n" . $block;
                if ($newPrompt === $current) { $updated++; continue; }
                $r = $this->builderbot_lib->updateAssistantInstructions($bot, $newPrompt);
                if (!empty($r['success'])) $updated++;
                else $errors[] = "bot_id={$bot->id}: HTTP " . ($r['http_code'] ?? '?');
            } catch (\Throwable $e) {
                $errors[] = "bot_id={$bot->id}: " . $e->getMessage();
            }
        }

        return array(
            'success'  => empty($errors),
            'agotados' => count($rows),
            'bots'     => count($bots),
            'updated'  => $updated,
            'errors'   => $errors,
        );
    }

    // =========================================================
    // WHATSAPP WEB
    // =========================================================

    /**
     * Vista WhatsApp Web
     */
    public function whatsapp($bot_id = null)
    {
        $configs = $this->builderbot_model->getConfigs(true);
        $selectedBot = null;

        if ($bot_id) {
            foreach ($configs as $c) {
                if ($c->id == $bot_id) { $selectedBot = $c; break; }
            }
        }
        if (!$selectedBot && !empty($configs)) {
            $selectedBot = $configs[0];
        }

        $data = array(
            'bots' => $configs,
            'selectedBot' => $selectedBot,
            'tags' => $this->builderbot_model->getTags(),
            'is_owner' => $this->is_owner,
            'role' => $this->session->userdata('user_data')['role'],
        );
        $this->load->view('sisvent/admin/bots/whatsapp_web', $data);
    }

    /**
     * AJAX: Listar conversaciones de un bot
     */
    public function whatsappConversations($bot_config_id)
    {
        header('Content-Type: application/json');
        $search = $this->input->get('q') ?: '';
        $tag_id = $this->input->get('tag') ?: null;
        $conversations = $this->builderbot_model->getConversations($bot_config_id, 'active', $search, 100, $tag_id);
        $tag_counts = $this->builderbot_model->getTagCounts($bot_config_id);
        echo json_encode(array('conversations' => $conversations, 'tag_counts' => $tag_counts));
    }

    /**
     * AJAX: Cambiar etiqueta de una conversación
     */
    public function whatsappSetTag()
    {
        header('Content-Type: application/json');
        $conv_id = $this->input->post('conversation_id');
        $tag_id = $this->input->post('tag_id');
        if (!$conv_id || !$tag_id) {
            echo json_encode(array('success' => false));
            return;
        }
        $this->builderbot_model->setTag($conv_id, $tag_id);
        echo json_encode(array('success' => true));
    }

    /**
     * AJAX: Obtener mensajes de una conversación
     */
    public function whatsappMessages($conversation_id)
    {
        header('Content-Type: application/json');
        $messages = $this->builderbot_model->getConversationMessages($conversation_id, 200);
        $this->builderbot_model->markConversationRead($conversation_id);
        echo json_encode(array('messages' => $messages));
    }

    /**
     * AJAX: Enviar mensaje desde WhatsApp Web
     */
    public function whatsappSend()
    {
        header('Content-Type: application/json');

        $conversation_id = $this->input->post('conversation_id');
        $content = trim($this->input->post('content'));

        if (empty($conversation_id) || empty($content)) {
            echo json_encode(array('success' => false, 'error' => 'Conversación y mensaje requeridos'));
            return;
        }

        $conv = $this->builderbot_model->getConversation($conversation_id);
        if (!$conv) {
            echo json_encode(array('success' => false, 'error' => 'Conversación no encontrada'));
            return;
        }

        $botConfig = $this->builderbot_model->getConfig($conv->bot_config_id);
        if (!$botConfig) {
            echo json_encode(array('success' => false, 'error' => 'Bot no encontrado'));
            return;
        }

        // Enviar via BuilderBot
        $result = $this->builderbot_lib->sendMessage($botConfig, $conv->phone, $content);

        if ($result['success']) {
            $uid = $this->session->userdata('user_data')['uname'];
            $this->builderbot_model->saveConversationMessage(
                $conv->bot_config_id, $conv->phone, 'outgoing', $content, null, $uid
            );
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'error' => 'Error al enviar: HTTP ' . ($result['http_code'] ?? '')));
        }
    }

    /**
     * AJAX: Polling — mensajes nuevos desde un ID
     */
    public function whatsappPoll($conversation_id, $after_id)
    {
        header('Content-Type: application/json');
        $messages = $this->builderbot_model->getNewMessages($conversation_id, $after_id);
        $unread = $this->builderbot_model->getUnreadCount(
            $this->builderbot_model->getConversation($conversation_id)->bot_config_id ?? 0
        );
        echo json_encode(array('messages' => $messages, 'unread_total' => $unread));
    }

    /**
     * AJAX: Iniciar conversación nueva
     */
    public function whatsappNewChat()
    {
        header('Content-Type: application/json');

        $bot_config_id = $this->input->post('bot_config_id');
        $phone = preg_replace('/[^0-9]/', '', $this->input->post('phone'));
        $name = trim($this->input->post('name'));

        if (strlen($phone) === 10) $phone = '57' . $phone;
        if (strlen($phone) < 12) {
            echo json_encode(array('success' => false, 'error' => 'Número inválido'));
            return;
        }

        $conv = $this->builderbot_model->getOrCreateConversation($bot_config_id, $phone, $name);
        echo json_encode(array('success' => true, 'conversation_id' => $conv->id));
    }
}
