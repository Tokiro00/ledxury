<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Builderbot_model extends CI_Model {

    // ── Bot Configs ──────────────────────────────────────────

    public function getConfigs($active_only = true)
    {
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        return $this->db->order_by('name', 'ASC')->get('builderbot_configs')->result();
    }

    public function getConfig($id)
    {
        return $this->db->where('id', $id)->get('builderbot_configs')->row();
    }

    public function getConfigByBotId($bot_id)
    {
        return $this->db->where('bot_id', $bot_id)->get('builderbot_configs')->row();
    }

    public function saveConfig($data)
    {
        $this->db->insert('builderbot_configs', $data);
        return $this->db->insert_id();
    }

    public function updateConfig($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update('builderbot_configs', $data);
    }

    public function deleteConfig($id)
    {
        return $this->db->where('id', $id)->update('builderbot_configs', array('is_active' => 0));
    }

    // ── Messages ─────────────────────────────────────────────

    public function getMessages($bot_config_id, $limit = 50, $offset = 0)
    {
        return $this->db->where('bot_config_id', $bot_config_id)
            ->order_by('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get('builderbot_messages')
            ->result();
    }

    public function saveMessage($data)
    {
        $this->db->insert('builderbot_messages', $data);
        return $this->db->insert_id();
    }

    public function updateMessageStatus($id, $status, $api_response = null)
    {
        $update = array('status' => $status);
        if ($api_response !== null) {
            $update['api_response'] = is_string($api_response) ? $api_response : json_encode($api_response);
        }
        return $this->db->where('id', $id)->update('builderbot_messages', $update);
    }

    public function countMessages($bot_config_id)
    {
        return $this->db->where('bot_config_id', $bot_config_id)->count_all_results('builderbot_messages');
    }

    // ── Webhooks ─────────────────────────────────────────────

    public function saveWebhook($data)
    {
        $this->db->insert('builderbot_webhooks', $data);
        return $this->db->insert_id();
    }

    public function updateWebhook($id, $data)
    {
        return $this->db->where('id', $id)->update('builderbot_webhooks', $data);
    }

    public function getWebhooks($bot_config_id, $limit = 50, $offset = 0)
    {
        return $this->db->where('bot_config_id', $bot_config_id)
            ->order_by('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get('builderbot_webhooks')
            ->result();
    }

    // ── Dashboard Stats ──────────────────────────────────────

    public function getSalesStats($bot_config_id, $date_from = null, $date_to = null)
    {
        $this->db->select('COUNT(*) as total, SUM(CASE WHEN status="processed" THEN 1 ELSE 0 END) as exitosas, SUM(CASE WHEN status="failed" THEN 1 ELSE 0 END) as fallidas')
            ->from('builderbot_webhooks')
            ->where('bot_config_id', $bot_config_id)
            ->where('event_type', 'sale');

        if ($date_from) $this->db->where('created_at >=', $date_from);
        if ($date_to) $this->db->where('created_at <=', $date_to);

        return $this->db->get()->row();
    }

    /**
     * Ventas recientes (presupuestos) de un bot por su vendor_id
     */
    public function getRecentSales($bot_config_id, $limit = 20)
    {
        $config = $this->getConfig($bot_config_id);
        if (!$config) return array();

        return $this->db->select('b.idBudget, b.total as budget_total, b.date, b.comments, c.name as client_name, c.idNum, c.cellphone')
            ->from('budgets b')
            ->join('clients c', 'c.idClient = b.clientId', 'left')
            ->where('b.vendorId', $config->default_vendor_id)
            ->order_by('b.date', 'DESC')
            ->limit($limit)
            ->get()
            ->result();
    }

    /**
     * Ventas hoy (presupuestos)
     */
    public function getTodaySalesCount($bot_config_id)
    {
        $config = $this->getConfig($bot_config_id);
        if (!$config) return 0;

        return $this->db->where('vendorId', $config->default_vendor_id)
            ->where('DATE(date)', date('Y-m-d'))
            ->count_all_results('budgets');
    }

    /**
     * Ventas esta semana (presupuestos)
     */
    public function getWeekSalesCount($bot_config_id)
    {
        $config = $this->getConfig($bot_config_id);
        if (!$config) return 0;

        return $this->db->where('vendorId', $config->default_vendor_id)
            ->where('date >=', date('Y-m-d', strtotime('monday this week')))
            ->count_all_results('budgets');
    }

    /**
     * Mensajes enviados por bot
     */
    public function getMessagesSentCount($bot_config_id)
    {
        return $this->db->where('bot_config_id', $bot_config_id)
            ->where('direction', 'outgoing')
            ->where('status', 'sent')
            ->count_all_results('builderbot_messages');
    }

    // ── Reporte de Efectividad ───────────────────────────────

    public function getEffectivenessReport($vendor_id, $from, $to)
    {
        $report = array();

        // 1. Ventas = Facturas creadas (solo lo facturado cuenta)
        $r = $this->db->select('COUNT(DISTINCT i.idInvoice) as ventas, COALESCE(SUM(i.total),0) as total_ventas')
            ->from('invoices i')
            ->join('budgets b', 'b.idBudget = i.budgetId')
            ->where('b.vendorId', $vendor_id)
            ->where('i.deleted', 0)
            ->where('b.date >=', $from)
            ->where('b.date <=', $to . ' 23:59:59')
            ->get()->row();
        $report['ventas_bot'] = (int) $r->ventas;
        $report['total_ventas'] = (float) $r->total_ventas;

        // 2. Facturas pagadas (tienen al menos un pago)
        $r = $this->db->select('COUNT(DISTINCT i.idInvoice) as facturas_pagadas, COALESCE(SUM(p.payment),0) as total_recaudado')
            ->from('payments p')
            ->join('invoices i', 'i.idInvoice = p.invoiceId')
            ->join('budgets b', 'b.idBudget = i.budgetId')
            ->where('b.vendorId', $vendor_id)
            ->where('i.deleted', 0)
            ->where('b.date >=', $from)
            ->where('b.date <=', $to . ' 23:59:59')
            ->get()->row();
        $report['facturas'] = (int) $r->facturas_pagadas;
        $report['total_facturado'] = $report['total_ventas']; // total facturado = total ventas (son lo mismo ahora)
        $report['pagos'] = (int) $r->facturas_pagadas;
        $report['total_recaudado'] = (float) $r->total_recaudado;

        // 3. Envíos y costos
        $r = $this->db->select('COUNT(*) as envios, COALESCE(SUM(sg.valorFlete),0) as costo_flete, COALESCE(SUM(sg.valorTotal),0) as total_envio')
            ->from('shipping_guides sg')
            ->join('invoices i', 'i.idInvoice = sg.invoiceId')
            ->join('budgets b', 'b.idBudget = i.budgetId')
            ->where('b.vendorId', $vendor_id)
            ->where('i.deleted', 0)
            ->where('b.date >=', $from)
            ->where('b.date <=', $to . ' 23:59:59')
            ->get()->row();
        $report['envios'] = (int) $r->envios;
        $report['costo_flete'] = (float) $r->costo_flete;
        $report['total_envio'] = (float) $r->total_envio;

        // Cálculos
        $report['conversion'] = $report['ventas_bot'] > 0
            ? round(($report['facturas'] / $report['ventas_bot']) * 100, 1) : 0;
        $report['efectividad'] = $report['total_ventas'] > 0
            ? round(($report['total_recaudado'] / $report['total_ventas']) * 100, 1) : 0;
        $report['margen_neto'] = $report['total_recaudado'] - $report['costo_flete'];

        return $report;
    }

    /**
     * Reporte consolidado de todos los bots
     */
    public function getAllBotsReport($from, $to)
    {
        $configs = $this->getConfigs(true);
        $reports = array();

        foreach ($configs as $cfg) {
            $reports[] = array(
                'config' => $cfg,
                'data'   => $this->getEffectivenessReport($cfg->default_vendor_id, $from, $to),
            );
        }

        return $reports;
    }

    // =========================================================
    // CONVERSATIONS (WhatsApp Web)
    // =========================================================

    /**
     * Obtener o crear conversación por bot + teléfono
     */
    public function getOrCreateConversation($bot_config_id, $phone, $client_name = null) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $conv = $this->db->where('bot_config_id', $bot_config_id)
            ->where('phone', $phone)
            ->get('bot_conversations')->row();

        if ($conv) {
            if ($client_name && empty($conv->client_name)) {
                $this->db->where('id', $conv->id)->update('bot_conversations', array('client_name' => $client_name));
            }
            return $conv;
        }

        // Buscar cliente en BD por celular
        $CI =& get_instance();
        $CI->load->model('clients_model');
        $client = $CI->clients_model->getClientByPhone($phone);

        $data = array(
            'bot_config_id' => $bot_config_id,
            'phone' => $phone,
            'client_name' => $client_name ?: ($client ? $client->name : $phone),
            'client_id' => $client ? $client->idClient : null,
        );
        $this->db->insert('bot_conversations', $data);
        $data['id'] = $this->db->insert_id();
        return (object)$data;
    }

    /**
     * Guardar mensaje en conversación
     */
    public function saveConversationMessage($bot_config_id, $phone, $direction, $content, $media_url = null, $sent_by = null) {
        date_default_timezone_set("America/Bogota");
        $conv = $this->getOrCreateConversation($bot_config_id, $phone);
        $now = date('Y-m-d H:i:s');

        $this->db->insert('builderbot_messages', array(
            'bot_config_id' => $bot_config_id,
            'conversation_id' => $conv->id,
            'direction' => $direction,
            'phone_number' => $phone,
            'content' => $content,
            'media_url' => $media_url,
            'status' => ($direction === 'incoming') ? 'delivered' : 'sent',
            'sent_by' => $sent_by,
            'created_at' => $now,
        ));

        // Actualizar conversación
        $update = array(
            'last_message' => mb_substr($content, 0, 200),
            'last_message_at' => $now,
            'last_direction' => ($direction === 'incoming') ? 'in' : 'out',
        );
        if ($direction === 'incoming') {
            $current_unread = isset($conv->unread_count) ? (int)$conv->unread_count : 0;
            $update['unread_count'] = $current_unread + 1;
        }
        $this->db->where('id', $conv->id)->update('bot_conversations', $update);

        return $conv->id;
    }

    /**
     * Listar conversaciones de un bot (ordenadas por último mensaje)
     */
    public function getConversations($bot_config_id, $status = 'active', $search = '', $limit = 50, $tag_id = null) {
        $this->db->select('bot_conversations.*, bot_conversation_tags.name as tag_name, bot_conversation_tags.color as tag_color');
        $this->db->from('bot_conversations');
        $this->db->join('bot_conversation_tags', 'bot_conversation_tags.id = bot_conversations.tag_id', 'left');
        $this->db->where('bot_conversations.bot_config_id', $bot_config_id);
        if ($status !== 'all') $this->db->where('bot_conversations.status', $status);
        if ($tag_id && $tag_id !== 'all') $this->db->where('bot_conversations.tag_id', $tag_id);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('bot_conversations.phone', $search);
            $this->db->or_like('bot_conversations.client_name', $search);
            $this->db->group_end();
        }
        $this->db->order_by('bot_conversations.last_message_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    /**
     * Obtener todas las etiquetas disponibles
     */
    public function getTags() {
        return $this->db->order_by('sort_order', 'ASC')->get('bot_conversation_tags')->result();
    }

    /**
     * Contar conversaciones por etiqueta para un bot
     */
    public function getTagCounts($bot_config_id) {
        return $this->db->select('tag_id, COUNT(*) as total')
            ->from('bot_conversations')
            ->where('bot_config_id', $bot_config_id)
            ->where('status', 'active')
            ->group_by('tag_id')
            ->get()->result();
    }

    /**
     * Cambiar etiqueta de una conversación
     */
    public function setTag($conversation_id, $tag_id) {
        return $this->db->where('id', $conversation_id)->update('bot_conversations', array('tag_id' => $tag_id));
    }

    /**
     * Obtener mensajes de una conversación
     */
    public function getConversationMessages($conversation_id, $limit = 100, $before_id = null) {
        $this->db->from('builderbot_messages');
        $this->db->where('conversation_id', $conversation_id);
        if ($before_id) $this->db->where('id <', $before_id);
        $this->db->order_by('id', 'DESC');
        $this->db->limit($limit);
        $messages = $this->db->get()->result();
        return array_reverse($messages);
    }

    /**
     * Marcar conversación como leída
     */
    public function markConversationRead($conversation_id) {
        $this->db->where('id', $conversation_id)->update('bot_conversations', array('unread_count' => 0));
    }

    /**
     * Obtener conversación por ID
     */
    public function getConversation($id) {
        return $this->db->where('id', $id)->get('bot_conversations')->row();
    }

    /**
     * Conversaciones sin responder: el ÚLTIMO mensaje fue del cliente (last_direction='in')
     * y pasaron al menos $minutes minutos sin que nadie respondiera.
     *
     * @param int|null $bot_config_id  Si se pasa, filtra por bot. Si null, todos.
     * @param int      $minutes        Threshold en minutos
     * @param int      $limit          Máximo de conversaciones a devolver
     * @return array de objetos con campos de bot_conversations + minutos_sin_responder
     */
    public function getUnansweredConversations($bot_config_id = null, $minutes = 15, $limit = 100) {
        date_default_timezone_set('America/Bogota');
        $cutoff = date('Y-m-d H:i:s', time() - ($minutes * 60));

        $this->db->select('bc.*, bcfg.name AS bot_name,
            TIMESTAMPDIFF(MINUTE, bc.last_message_at, NOW()) AS minutos_sin_responder', false);
        $this->db->from('bot_conversations bc');
        $this->db->join('builderbot_configs bcfg', 'bcfg.id = bc.bot_config_id', 'left');
        $this->db->where('bc.last_direction', 'in');
        $this->db->where('bc.last_message_at <=', $cutoff);
        $this->db->where('bc.last_message_at IS NOT NULL', null, false);
        if ($bot_config_id) $this->db->where('bc.bot_config_id', (int)$bot_config_id);
        // Excluir conversaciones marcadas como spam (tag 9) o ventas confirmadas (tag 2)
        $this->db->where('(bc.tag_id IS NULL OR bc.tag_id NOT IN (2, 9))', null, false);
        $this->db->order_by('bc.last_message_at', 'ASC'); // las más viejas primero
        $this->db->limit((int)$limit);
        return $this->db->get()->result();
    }

    /**
     * Solo el conteo (para badge en navbar). Mucho más barato que listar.
     */
    public function getUnansweredCount($bot_config_id = null, $minutes = 15) {
        $cutoff = date('Y-m-d H:i:s', time() - ($minutes * 60));
        $this->db->from('bot_conversations');
        $this->db->where('last_direction', 'in');
        $this->db->where('last_message_at <=', $cutoff);
        $this->db->where('last_message_at IS NOT NULL', null, false);
        if ($bot_config_id) $this->db->where('bot_config_id', (int)$bot_config_id);
        $this->db->where('(tag_id IS NULL OR tag_id NOT IN (2, 9))', null, false);
        return (int) $this->db->count_all_results();
    }

    /**
     * Contar no leídos por bot
     */
    public function getUnreadCount($bot_config_id) {
        return $this->db->where('bot_config_id', $bot_config_id)
            ->where('unread_count >', 0)
            ->count_all_results('bot_conversations');
    }

    /**
     * Obtener mensajes nuevos desde un ID (para polling)
     */
    public function getNewMessages($conversation_id, $after_id) {
        return $this->db->from('builderbot_messages')
            ->where('conversation_id', $conversation_id)
            ->where('id >', $after_id)
            ->order_by('id', 'ASC')
            ->get()->result();
    }
}
