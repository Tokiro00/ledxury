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

    public function getRecentSales($bot_config_id, $limit = 20)
    {
        return $this->db->select('w.*, q.budget_id, q.status as queue_status, q.vendor_id, q.payload as sale_payload, b.total as budget_total, c.name as client_name')
            ->from('builderbot_webhooks w')
            ->join('bot_sales_queue q', 'q.id = w.queue_id', 'left')
            ->join('budgets b', 'b.idBudget = q.budget_id', 'left')
            ->join('clients c', 'c.idClient = b.clientId', 'left')
            ->where('w.bot_config_id', $bot_config_id)
            ->where('w.event_type', 'sale')
            ->order_by('w.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result();
    }

    public function getDailySalesCount($bot_config_id, $days = 30)
    {
        return $this->db->select('DATE(created_at) as fecha, COUNT(*) as total')
            ->from('builderbot_webhooks')
            ->where('bot_config_id', $bot_config_id)
            ->where('event_type', 'sale')
            ->where('created_at >=', date('Y-m-d', strtotime("-{$days} days")))
            ->group_by('DATE(created_at)')
            ->order_by('fecha', 'ASC')
            ->get()
            ->result();
    }

    public function getTodaySalesCount($bot_config_id)
    {
        return $this->db->where('bot_config_id', $bot_config_id)
            ->where('event_type', 'sale')
            ->where('DATE(created_at)', date('Y-m-d'))
            ->count_all_results('builderbot_webhooks');
    }

    public function getWeekSalesCount($bot_config_id)
    {
        return $this->db->where('bot_config_id', $bot_config_id)
            ->where('event_type', 'sale')
            ->where('created_at >=', date('Y-m-d', strtotime('monday this week')))
            ->count_all_results('builderbot_webhooks');
    }

    public function getMessagesSentCount($bot_config_id)
    {
        return $this->db->where('bot_config_id', $bot_config_id)
            ->where('direction', 'outgoing')
            ->where('status', 'sent')
            ->count_all_results('builderbot_messages');
    }
}
