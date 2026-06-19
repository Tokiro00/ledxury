<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ledxury v2 · Pulso — Dashboard (Inicio)
 *
 * Reemplaza/extiende /sisvent/dashboard. Inspirado en reports/v2/daily_sales:
 *   1. Selector de período (Hoy, Ayer, Esta semana, Este mes, Mes pasado, YTD, custom)
 *   2. 4 KPIs comparativos vs período anterior equivalente (Total · # Facturas · Ticket · Anterior)
 *   3. Gráfico Ventas por día (período actual vs anterior — SVG inline)
 *   4. Indicadores BOTS: conversaciones, cierres, conversión, cobros vía bot
 *   5. Indicadores GUÍAS: generadas, en tránsito, entregadas, tasa entrega
 *   6. Top vendedores (barras)
 *   7. Feed "Lo que está pasando"
 *   8. Panel WhatsApp dark
 */
class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control();
    }

    public function index()
    {
        date_default_timezone_set("America/Bogota");

        // Aislamiento multi-tenant: este dashboard usa SQL crudo, así que inyectamos
        // tenant_id en cada query (las crudas con AND tenant_id=?, las builder con apply_tenant()).
        $tid = (int) current_tenant_id();

        $ud = $this->session->userdata('user_data') ?: array();
        $firstName = !empty($ud['name']) ? strtok($ud['name'], ' ') : ($ud['uname'] ?? 'Equipo');

        // -----------------------------------------------------------
        // Período: presets o custom
        // -----------------------------------------------------------
        $preset = $this->input->get('p') ?: 'mes';   // hoy|ayer|semana|mes|mes_pasado|ytd|custom
        $fromIn = $this->input->get('from');
        $toIn   = $this->input->get('to');
        list($from, $to, $label) = $this->_resolvePeriod($preset, $fromIn, $toIn);
        // Período anterior equivalente (mismos días, antes)
        $days = max(1, (int) ((strtotime($to) - strtotime($from)) / 86400) + 1);
        $prevTo   = date('Y-m-d', strtotime($from . ' -1 day'));
        $prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . ($days - 1) . ' day'));

        $fromDt = $from . ' 00:00:00';
        $toDt   = $to   . ' 23:59:59';
        $prevFromDt = $prevFrom . ' 00:00:00';
        $prevToDt   = $prevTo   . ' 23:59:59';

        // -----------------------------------------------------------
        // KPIs alineados con reporte v1 "Rendimiento Vendedores":
        //   - Ventas: TODAS las facturas emitidas en el período (cualquier state, deleted=0)
        //   - Recaudo: SUM(invoice.payment) — campo en invoices
        //   - Cartera pendiente: SUM(total - payment - discount)
        //   - Ticket promedio: ventas / # facturas
        // No filtrar state ni > 0 — espejo del reporte real.
        // -----------------------------------------------------------
        $cur = $this->db->query("
            SELECT
                COUNT(*) AS n_facts,
                COALESCE(SUM(total), 0) AS ventas,
                COALESCE(SUM(payment), 0) AS recaudo,
                COALESCE(SUM(total - payment - discount), 0) AS cartera
            FROM invoices
            WHERE deleted=0 AND tenant_id = ? AND DATE(date) BETWEEN ? AND ?
        ", array($tid, $from, $to))->row();
        $totalCur    = (float)($cur->ventas ?? 0);
        $factsCur    = (int)($cur->n_facts ?? 0);
        $recaudoCur  = (float)($cur->recaudo ?? 0);
        $carteraCur  = (float)($cur->cartera ?? 0);
        $ticketCur   = $factsCur > 0 ? $totalCur / $factsCur : 0;

        $prev = $this->db->query("
            SELECT
                COUNT(*) AS n_facts,
                COALESCE(SUM(total), 0) AS ventas,
                COALESCE(SUM(payment), 0) AS recaudo,
                COALESCE(SUM(total - payment - discount), 0) AS cartera
            FROM invoices
            WHERE deleted=0 AND tenant_id = ? AND DATE(date) BETWEEN ? AND ?
        ", array($tid, $prevFrom, $prevTo))->row();
        $totalPrev   = (float)($prev->ventas ?? 0);
        $factsPrev   = (int)($prev->n_facts ?? 0);
        $recaudoPrev = (float)($prev->recaudo ?? 0);
        $carteraPrev = (float)($prev->cartera ?? 0);
        $ticketPrev  = $factsPrev > 0 ? $totalPrev / $factsPrev : 0;

        // Deltas adicionales
        $recaudoDeltaPct = $recaudoPrev > 0 ? round((($recaudoCur - $recaudoPrev) / $recaudoPrev) * 100, 1) : 0;

        $deltaPct = $totalPrev > 0 ? round((($totalCur - $totalPrev) / $totalPrev) * 100, 1) : 0;
        $factsDeltaPct = $factsPrev > 0 ? round((($factsCur - $factsPrev) / $factsPrev) * 100, 1) : 0;
        $ticketDeltaPct = $ticketPrev > 0 ? round((($ticketCur - $ticketPrev) / $ticketPrev) * 100, 1) : 0;

        // -----------------------------------------------------------
        // Serie por día (actual + anterior alineado al día N)
        // -----------------------------------------------------------
        $seriesCur = $this->_dailySeries($fromDt, $toDt);
        $seriesPrev = $this->_dailySeries($prevFromDt, $prevToDt);
        // alinear índices a length de seriesCur
        $chartCur = array(); $chartPrev = array(); $chartLabels = array();
        $cursor = strtotime($from);
        $cursorPrev = strtotime($prevFrom);
        for ($i = 0; $i < $days; $i++) {
            $dCur = date('Y-m-d', $cursor);
            $dPrev = date('Y-m-d', $cursorPrev);
            $chartCur[]    = isset($seriesCur[$dCur])  ? (float)$seriesCur[$dCur]  : 0;
            $chartPrev[]   = isset($seriesPrev[$dPrev])? (float)$seriesPrev[$dPrev]: 0;
            $chartLabels[] = date('d/m', $cursor);
            $cursor += 86400;
            $cursorPrev += 86400;
        }

        // -----------------------------------------------------------
        // BOTS: conversaciones, cierres, conversión, cobros via bot (período)
        // -----------------------------------------------------------
        $bot = $this->db->query("
            SELECT
                COUNT(*) AS conversaciones,
                COUNT(CASE WHEN budget_id IS NOT NULL THEN 1 END) AS cerradas,
                COUNT(CASE WHEN unread_count = 0 AND last_direction = 'out' THEN 1 END) AS resueltas
            FROM bot_conversations
            WHERE tenant_id = ? AND created_at >= ? AND created_at <= ?
        ", array($tid, $fromDt, $toDt))->row();
        $convs       = (int)($bot->conversaciones ?? 0);
        $convCerr    = (int)($bot->cerradas ?? 0);
        $convResu    = (int)($bot->resueltas ?? 0);
        $tasaCierre  = $convs > 0 ? round($convCerr / $convs * 100, 1) : 0;
        $tasaResol   = $convs > 0 ? round($convResu / $convs * 100, 1) : 0;

        // Cobros con factura cuyo budget vino de un bot
        $cobrosBot = $this->db->query("
            SELECT COUNT(DISTINCT i.idInvoice) AS n, COALESCE(SUM(i.total),0) AS v
            FROM invoices i
            JOIN budgets b ON b.idBudget = i.budgetId
            JOIN bot_conversations bc ON bc.budget_id = b.idBudget
            WHERE i.state=2 AND i.total>0 AND (i.deleted IS NULL OR i.deleted=0)
              AND i.tenant_id = ? AND i.date >= ? AND i.date <= ?
        ", array($tid, $fromDt, $toDt))->row();
        $cobrosBotN = (int)($cobrosBot->n ?? 0);
        $cobrosBotV = (float)($cobrosBot->v ?? 0);

        // Conversaciones activas HOY (independiente del período)
        apply_tenant();
        $convsActivasHoy = $this->db->where('status', 'active')
            ->where('last_message_at >=', date('Y-m-d 00:00:00'))
            ->count_all_results('bot_conversations');

        // -----------------------------------------------------------
        // GUÍAS: generadas / en tránsito / entregadas / anuladas
        // -----------------------------------------------------------
        $guiasStats = $this->db->query("
            SELECT
                COUNT(*) AS total,
                COUNT(CASE WHEN status IN ('entregado','delivered') THEN 1 END) AS entregadas,
                COUNT(CASE WHEN status IN ('en_transito','in_transit','transito','enviado','despachado') THEN 1 END) AS transito,
                COUNT(CASE WHEN status IN ('anulado','cancelado','canceled') THEN 1 END) AS anuladas,
                COUNT(CASE WHEN status IN ('cotizado','generado','pendiente') THEN 1 END) AS pendientes
            FROM shipping_guides
            WHERE tenant_id = ? AND created_at >= ? AND created_at <= ?
        ", array($tid, $fromDt, $toDt))->row();
        $gTotal      = (int)($guiasStats->total ?? 0);
        $gEntregadas = (int)($guiasStats->entregadas ?? 0);
        $gTransito   = (int)($guiasStats->transito ?? 0);
        $gAnuladas   = (int)($guiasStats->anuladas ?? 0);
        $gPend       = (int)($guiasStats->pendientes ?? 0);
        $tasaEntrega = $gTotal > 0 ? round($gEntregadas / $gTotal * 100, 1) : 0;

        // -----------------------------------------------------------
        // Top 5 vendedores del período
        // CLAVE: agrupa por el vendedor REAL (del budget cuando la factura
        // quedó con vendor='00000' Administrador). Caso típico: vendedor crea
        // presupuesto y el admin lo factura.
        // -----------------------------------------------------------
        // Top vendedores — espejo del reporte v1 "Rendimiento Vendedores":
        // SUM(total), SUM(payment), num_invoices, num_clients, agrupando por
        // vendedor REAL (del budget cuando invoice quedó como Administrador).
        $topVendedores = $this->db->query("
            SELECT
                CASE
                    WHEN (i.vendorId IS NULL OR i.vendorId = '' OR i.vendorId = '00000')
                         AND b.vendorId IS NOT NULL AND b.vendorId != '00000'
                        THEN b.vendorId
                    ELSE i.vendorId
                END AS real_vendor_id,
                COUNT(DISTINCT i.idInvoice) AS n_facts,
                COUNT(DISTINCT i.clientId) AS n_clients,
                COALESCE(SUM(i.total), 0) AS volumen,
                COALESCE(SUM(i.payment), 0) AS recaudo,
                COALESCE(SUM(i.total - i.payment - i.discount), 0) AS cartera
            FROM invoices i
            LEFT JOIN budgets b ON b.idBudget = i.budgetId
            WHERE i.deleted=0 AND i.tenant_id = ? AND DATE(i.date) BETWEEN ? AND ?
            GROUP BY real_vendor_id
            ORDER BY volumen DESC
            LIMIT 5
        ", array($tid, $from, $to))->result();
        // Resolver nombres
        if (!empty($topVendedores)) {
            $ids = array();
            foreach ($topVendedores as $r) $ids[] = $r->real_vendor_id;
            $usersMap = array();
            if (!empty($ids)) {
                $usersRows = $this->db->select('idUser, name')->where_in('idUser', $ids)
                    ->get('users')->result();
                foreach ($usersRows as $u) $usersMap[$u->idUser] = $u->name;
            }
            foreach ($topVendedores as &$r) {
                $r->name = isset($usersMap[$r->real_vendor_id])
                    ? $usersMap[$r->real_vendor_id]
                    : ($r->real_vendor_id ?: 'Sin vendedor');
            }
            unset($r);
        }

        // -----------------------------------------------------------
        // Feed "Lo que está pasando" — últimos 6 eventos
        // -----------------------------------------------------------
        $feed = array();
        apply_tenant('cm');
        $cobros = $this->db->select('cm.idMovement, cm.amount, cm.concept, cm.movementDate, cm.sourceType')
            ->from('cash_movements cm')
            ->where('cm.movementType', 'ingreso')
            ->where('cm.deleted', 0)
            ->where('cm.referenceType', 'invoice')
            ->order_by('cm.idMovement', 'DESC')
            ->limit(8)
            ->get()->result();
        foreach ($cobros as $c) {
            $feed[] = array(
                'icon'  => 'card',
                'tone'  => 'mint',
                'title' => '$' . number_format((float)$c->amount, 0, ',', '.') . ' cobrado',
                'sub'   => $c->concept ?: 'Pago de factura',
                'when'  => $c->movementDate,
            );
        }
        apply_tenant();
        $guias = $this->db->select('id, numeroPreenvio, status, created_at, carrierName')
            ->from('shipping_guides')
            ->order_by('id', 'DESC')
            ->limit(8)
            ->get()->result();
        foreach ($guias as $g) {
            $feed[] = array(
                'icon'  => 'truck',
                'tone'  => in_array($g->status, ['entregado','delivered']) ? 'mint'
                       : (in_array($g->status, ['anulado','cancelado']) ? 'danger' : 'ink'),
                'title' => 'Guía ' . ($g->numeroPreenvio ?: '#' . $g->id) . ' · ' . ucfirst($g->status ?: '—'),
                'sub'   => ($g->carrierName ?: 'Interrapidísimo'),
                'when'  => $g->created_at,
            );
        }
        usort($feed, function($a, $b) { return strcmp($b['when'], $a['when']); });
        $feed = array_slice($feed, 0, 6);

        // -----------------------------------------------------------
        // Panel WhatsApp — chats activos
        // -----------------------------------------------------------
        apply_tenant();
        $activeConvs = $this->db->select('id, client_name, phone, last_message, last_direction, last_message_at')
            ->from('bot_conversations')
            ->where('status', 'active')
            ->where('last_message IS NOT NULL', null, false)
            ->order_by('last_message_at', 'DESC')
            ->limit(4)
            ->get()->result();

        // -----------------------------------------------------------
        // Ventas por mes · por bot integrado (últimos 6 meses, real)
        // Cada bot vende a través de invoices.vendorId = bot.default_vendor_id
        // -----------------------------------------------------------
        $sixMonthsAgo = date('Y-m-01', strtotime('-5 months')); // primer día hace 5 meses (incluye actual = 6 meses)
        apply_tenant();
        $bots = $this->db->select('id, name, default_vendor_id')
            ->where('is_active', 1)
            ->order_by('id', 'ASC')
            ->get('builderbot_configs')->result();
        // Generar lista de los últimos 6 meses Y-m
        $months = array();
        for ($i = 5; $i >= 0; $i--) {
            $months[] = date('Y-m', strtotime("-$i months"));
        }
        // Ventas por mes + bot (espejo lógica reporte v1, sin filter state)
        $botRows = $this->db->query("
            SELECT DATE_FORMAT(i.date, '%Y-%m') AS mes,
                   bc.id AS bot_id,
                   COUNT(*) AS facts,
                   COALESCE(SUM(i.total),0) AS volumen,
                   COALESCE(SUM(i.payment),0) AS recaudo
            FROM invoices i
            JOIN builderbot_configs bc ON bc.default_vendor_id = i.vendorId
            WHERE i.deleted=0
              AND i.tenant_id = ?
              AND i.date >= ?
              AND bc.is_active=1
            GROUP BY mes, bc.id
        ", array($tid, $sixMonthsAgo))->result();
        // Indexar: $botSales[bot_id][mes] = volumen
        $botSales = array();
        $botFacts = array();
        foreach ($botRows as $r) {
            $botSales[$r->bot_id][$r->mes] = (float)$r->volumen;
            $botFacts[$r->bot_id][$r->mes] = (int)$r->facts;
        }
        // Generar serie densa por bot
        $botsData = array();
        foreach ($bots as $b) {
            $serie = array();
            $totalVol = 0;
            $totalFacts = 0;
            foreach ($months as $m) {
                $v = $botSales[$b->id][$m] ?? 0;
                $f = $botFacts[$b->id][$m] ?? 0;
                $serie[] = $v;
                $totalVol += $v;
                $totalFacts += $f;
            }
            $botsData[] = array(
                'id'         => $b->id,
                'name'       => $b->name,
                'vendor_id'  => $b->default_vendor_id,
                'serie'      => $serie,
                'total'      => $totalVol,
                'facts'      => $totalFacts,
                'last_month' => end($serie),
                'prev_month' => count($serie) >= 2 ? $serie[count($serie) - 2] : 0,
            );
        }

        // -----------------------------------------------------------
        // Saldos cajas/bancos (independiente del período)
        // -----------------------------------------------------------
        apply_tenant();
        $cajasActivas = $this->db->select('idCashbox AS id, name, currentBalance')
            ->where('deleted', 0)->order_by('idCashbox', 'ASC')->limit(6)
            ->get('cashboxes')->result();
        apply_tenant();
        $bancosActivos = $this->db->select('idBankAccount AS id, bankName AS name, currentBalance')
            ->where('deleted', 0)->order_by('idBankAccount', 'ASC')->limit(6)
            ->get('bank_accounts')->result();

        $data = array(
            'pageTitle'   => 'Inicio',
            'activeRoute' => 'dashboard',
            'breadcrumbs' => array('Operación', 'Inicio'),
            'firstName'   => $firstName,

            'preset' => $preset,
            'from'   => $from,
            'to'     => $to,
            'prevFrom' => $prevFrom,
            'prevTo'   => $prevTo,
            'periodLabel' => $label,

            // KPIs comparativos
            'totalCur' => $totalCur,
            'totalPrev'=> $totalPrev,
            'deltaPct' => $deltaPct,
            'factsCur' => $factsCur,
            'factsPrev'=> $factsPrev,
            'factsDeltaPct' => $factsDeltaPct,
            'ticketCur'=> $ticketCur,
            'ticketPrev'=> $ticketPrev,
            'ticketDeltaPct'=> $ticketDeltaPct,
            'recaudoCur'=> $recaudoCur,
            'recaudoPrev'=> $recaudoPrev,
            'recaudoDeltaPct'=> $recaudoDeltaPct,
            'carteraCur'=> $carteraCur,
            'carteraPrev'=> $carteraPrev,

            // Chart series
            'chartCur'   => $chartCur,
            'chartPrev'  => $chartPrev,
            'chartLabels'=> $chartLabels,

            // Bots
            'convs'        => $convs,
            'convCerr'     => $convCerr,
            'convResu'     => $convResu,
            'tasaCierre'   => $tasaCierre,
            'tasaResol'    => $tasaResol,
            'cobrosBotN'   => $cobrosBotN,
            'cobrosBotV'   => $cobrosBotV,
            'convsActivasHoy' => $convsActivasHoy,

            // Guías
            'gTotal'       => $gTotal,
            'gEntregadas'  => $gEntregadas,
            'gTransito'    => $gTransito,
            'gAnuladas'    => $gAnuladas,
            'gPend'        => $gPend,
            'tasaEntrega'  => $tasaEntrega,

            // Listas
            'topVendedores' => $topVendedores,
            'feed'          => $feed,
            'activeConvs'   => $activeConvs,
            'cajasActivas'  => $cajasActivas,
            'bancosActivos' => $bancosActivos,

            // Ventas por mes · por bot (últimos 6 meses)
            'botsData'      => $botsData,
            'botMonths'     => $months,
        );

        $this->load->view('sisvent/v2/pulso/dashboard/index', $data);
    }

    /**
     * Resuelve preset de período a (from, to, label).
     */
    private function _resolvePeriod($preset, $fromIn, $toIn)
    {
        $today = date('Y-m-d');
        switch ($preset) {
            case 'hoy':
                return array($today, $today, 'Hoy ' . date('d/m'));
            case 'ayer':
                $y = date('Y-m-d', strtotime('-1 day'));
                return array($y, $y, 'Ayer');
            case 'semana':
                // Lunes a hoy
                $monday = date('Y-m-d', strtotime('monday this week'));
                return array($monday, $today, 'Esta semana');
            case 'mes_pasado':
                $f = date('Y-m-01', strtotime('first day of last month'));
                $t = date('Y-m-t', strtotime('last day of last month'));
                return array($f, $t, 'Mes pasado ' . date('M Y', strtotime($f)));
            case 'ytd':
                return array(date('Y-01-01'), $today, 'Año a la fecha');
            case 'custom':
                if ($fromIn && $toIn) {
                    return array($fromIn, $toIn, $fromIn . ' → ' . $toIn);
                }
                // fallback
            case 'mes':
            default:
                return array(date('Y-m-01'), date('Y-m-t'), 'Este mes · ' . date('M Y'));
        }
    }

    /**
     * Serie diaria de ventas (state=2) entre fechas. Retorna asoc Y-m-d => volumen.
     */
    private function _dailySeries($fromDt, $toDt)
    {
        // Ventas por día — TODAS las facturas emitidas (espejo reporte v1).
        $rows = $this->db->query("
            SELECT DATE(date) AS d, COALESCE(SUM(total),0) AS v
            FROM invoices
            WHERE deleted=0 AND tenant_id = ? AND DATE(date) BETWEEN DATE(?) AND DATE(?)
            GROUP BY DATE(date)
        ", array((int) current_tenant_id(), $fromDt, $toDt))->result();
        $out = array();
        foreach ($rows as $r) $out[$r->d] = (float)$r->v;
        return $out;
    }
}
