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
}
