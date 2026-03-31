<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Executive API — Dashboard ejecutivo para dueños
 *
 * KPIs del negocio: ventas, cartera, inventario, vendedores, punto de equilibrio.
 * Auth: JWT (roles 1 y 2 únicamente).
 */
class Executive extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('api_response');
        $this->load->library('jwt_lib');
        $this->api_response->set_cors_headers();

        if ($this->input->method() === 'options') {
            $this->api_response->success(null, 'OK', 200);
        }
    }

    /**
     * GET api/exec/dashboard
     * Dashboard principal — todos los KPIs en una sola llamada.
     * Query params: period (today|week|month|year), store (0=todas)
     */
    public function dashboard()
    {
        $payload = $this->_auth();
        $period = $this->input->get('period') ?: 'month';
        $storeId = (int)$this->input->get('store') ?: 0;

        // Date ranges
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $yearStart = date('Y-01-01');
        $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
        $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));

        $storeWhere = $storeId > 0 ? "AND i.storeId = $storeId" : "";

        // ========== VENTAS ==========
        $ventas = $this->db->query("
            SELECT
                COALESCE(SUM(CASE WHEN i.date >= '$today' THEN i.total END), 0) as hoy,
                COALESCE(SUM(CASE WHEN i.date >= '$weekStart' THEN i.total END), 0) as semana,
                COALESCE(SUM(CASE WHEN i.date >= '$monthStart' THEN i.total END), 0) as mes,
                COALESCE(SUM(CASE WHEN i.date >= '$yearStart' THEN i.total END), 0) as ano,
                COUNT(CASE WHEN i.date >= '$monthStart' THEN 1 END) as facturas_mes,
                COUNT(CASE WHEN i.date >= '$today' THEN 1 END) as facturas_hoy
            FROM invoices i
            WHERE i.deleted = 0 $storeWhere
        ")->row();

        // Ventas mes pasado (para comparativo)
        $ventasMesPasado = $this->db->query("
            SELECT COALESCE(SUM(i.total), 0) as total
            FROM invoices i
            WHERE i.deleted = 0 AND i.date BETWEEN '$lastMonthStart' AND '$lastMonthEnd 23:59:59' $storeWhere
        ")->row()->total;

        $variacionMes = $ventasMesPasado > 0
            ? round((($ventas->mes - $ventasMesPasado) / $ventasMesPasado) * 100, 1)
            : 0;

        // ========== RECAUDO ==========
        $recaudo = $this->db->query("
            SELECT
                COALESCE(SUM(CASE WHEN i.date >= '$monthStart' THEN i.payment END), 0) as cobrado_mes,
                COALESCE(SUM(CASE WHEN i.date >= '$monthStart' THEN i.total END), 0) as vendido_mes
            FROM invoices i
            WHERE i.deleted = 0 $storeWhere
        ")->row();

        $pctRecaudo = $recaudo->vendido_mes > 0
            ? round(($recaudo->cobrado_mes / $recaudo->vendido_mes) * 100, 1)
            : 0;

        // ========== CARTERA ==========
        $cartera = $this->db->query("
            SELECT
                COALESCE(SUM(i.total - i.payment - i.discount), 0) as total_pendiente,
                COALESCE(SUM(CASE WHEN DATEDIFF(NOW(), i.date) > 90 THEN i.total - i.payment - i.discount ELSE 0 END), 0) as vencida_90,
                COALESCE(SUM(CASE WHEN DATEDIFF(NOW(), i.date) > 30 THEN i.total - i.payment - i.discount ELSE 0 END), 0) as vencida_30,
                COUNT(*) as facturas_pendientes
            FROM invoices i
            WHERE i.deleted = 0 AND (i.state = 0 OR i.state = 1)
              AND (i.total - i.payment - i.discount) > 0
              $storeWhere
        ")->row();

        // ========== INVENTARIO ==========
        $inventario = $this->db->query("
            SELECT
                COUNT(DISTINCT p.idProduct) as total_productos,
                COALESCE(SUM(inv.stock), 0) as total_unidades,
                COALESCE(SUM(inv.stock * p.cost_cop), 0) as valor_costo,
                COALESCE(SUM(inv.stock * p.price), 0) as valor_venta,
                SUM(CASE WHEN inv.stock <= 0 THEN 1 ELSE 0 END) as agotados,
                SUM(CASE WHEN inv.stock > 0 AND inv.stock <= p.min THEN 1 ELSE 0 END) as stock_bajo
            FROM products p
            LEFT JOIN (
                SELECT idProduct, SUM(stock) as stock FROM inventory
                " . ($storeId > 0 ? "WHERE idStore = $storeId" : "") . "
                GROUP BY idProduct
            ) inv ON inv.idProduct = p.idProduct
            WHERE p.deleted = 0
        ")->row();

        // ========== PUNTO DE EQUILIBRIO ==========
        // Gastos fijos mensuales (de expense_records pagados este mes)
        $gastosFijos = $this->db->query("
            SELECT COALESCE(SUM(er.amount), 0) as total
            FROM expense_records er
            WHERE er.status = 'pagado'
              AND er.expense_date BETWEEN '$monthStart' AND '$monthEnd'
        ")->row()->total;

        // Si no hay gastos registrados, estimamos con un % de las ventas
        if ($gastosFijos <= 0) {
            $gastosFijos = $ventas->mes * 0.15; // 15% como estimación
        }

        // Margen bruto promedio
        $margenData = $this->db->query("
            SELECT
                COALESCE(SUM(d.total), 0) as ingresos,
                COALESCE(SUM(d.quantity * p.cost_cop), 0) as costos
            FROM invoice_details d
            INNER JOIN invoices i ON i.idInvoice = d.invoiceId
            INNER JOIN products p ON p.idProduct = d.productId
            WHERE i.deleted = 0 AND i.date BETWEEN '$monthStart' AND '$monthEnd 23:59:59'
              " . ($storeId > 0 ? "AND i.storeId = $storeId" : "") . "
        ")->row();

        $margenBruto = $margenData->ingresos > 0
            ? round(($margenData->ingresos - $margenData->costos) / $margenData->ingresos * 100, 1)
            : 52.7; // default from CLAUDE.md

        $puntoEquilibrio = $margenBruto > 0
            ? round($gastosFijos / ($margenBruto / 100))
            : 0;

        $pctEquilibrio = $puntoEquilibrio > 0
            ? round(($ventas->mes / $puntoEquilibrio) * 100, 1)
            : 0;

        // ========== TOP VENDEDORES ==========
        $topVendedores = $this->db->query("
            SELECT u.idUser, u.name,
                   COALESCE(SUM(i.total), 0) as ventas,
                   COUNT(*) as facturas,
                   COALESCE(SUM(i.payment), 0) as cobrado,
                   sg.meta
            FROM invoices i
            INNER JOIN users u ON u.idUser = i.vendorId
            LEFT JOIN (
                SELECT userId, m" . date('n') . " as meta FROM sales_goal WHERE year = " . date('Y') . "
            ) sg ON sg.userId = i.vendorId
            WHERE i.deleted = 0 AND i.date BETWEEN '$monthStart' AND '$monthEnd 23:59:59'
              $storeWhere
            GROUP BY i.vendorId
            ORDER BY ventas DESC
            LIMIT 10
        ")->result();

        foreach ($topVendedores as &$v) {
            $v->meta = (float)($v->meta ?: 0);
            $v->pct_meta = $v->meta > 0 ? round(($v->ventas / $v->meta) * 100, 1) : 0;
            $v->pct_cobro = $v->ventas > 0 ? round(($v->cobrado / $v->ventas) * 100, 1) : 0;
        }
        unset($v);

        // ========== TOP PRODUCTOS ==========
        $topProductos = $this->db->query("
            SELECT d.productId, p.description, p.price,
                   SUM(d.quantity) as qty, SUM(d.total) as total_vendido
            FROM invoice_details d
            INNER JOIN invoices i ON i.idInvoice = d.invoiceId
            INNER JOIN products p ON p.idProduct = d.productId
            WHERE i.deleted = 0 AND i.date BETWEEN '$monthStart' AND '$monthEnd 23:59:59'
              " . ($storeId > 0 ? "AND i.storeId = $storeId" : "") . "
            GROUP BY d.productId
            ORDER BY total_vendido DESC
            LIMIT 10
        ")->result();

        // ========== VENTAS POR BODEGA ==========
        $ventasPorBodega = $this->db->query("
            SELECT s.idStore, s.name, COALESCE(SUM(i.total), 0) as ventas, COUNT(*) as facturas
            FROM invoices i
            INNER JOIN stores s ON s.idStore = i.storeId
            WHERE i.deleted = 0 AND i.date BETWEEN '$monthStart' AND '$monthEnd 23:59:59'
            GROUP BY i.storeId
            ORDER BY ventas DESC
        ")->result();

        // ========== STORES LIST ==========
        $stores = $this->db->select('idStore, name')->from('stores')->get()->result();

        $this->api_response->success([
            'ventas' => [
                'hoy'           => (float)$ventas->hoy,
                'semana'        => (float)$ventas->semana,
                'mes'           => (float)$ventas->mes,
                'ano'           => (float)$ventas->ano,
                'facturas_hoy'  => (int)$ventas->facturas_hoy,
                'facturas_mes'  => (int)$ventas->facturas_mes,
                'mes_pasado'    => (float)$ventasMesPasado,
                'variacion_mes' => $variacionMes,
            ],
            'recaudo' => [
                'cobrado_mes' => (float)$recaudo->cobrado_mes,
                'vendido_mes' => (float)$recaudo->vendido_mes,
                'pct'         => $pctRecaudo,
            ],
            'cartera' => [
                'total'      => (float)$cartera->total_pendiente,
                'vencida_30' => (float)$cartera->vencida_30,
                'vencida_90' => (float)$cartera->vencida_90,
                'facturas'   => (int)$cartera->facturas_pendientes,
            ],
            'inventario' => [
                'productos'    => (int)$inventario->total_productos,
                'unidades'     => (int)$inventario->total_unidades,
                'valor_costo'  => (float)$inventario->valor_costo,
                'valor_venta'  => (float)$inventario->valor_venta,
                'agotados'     => (int)$inventario->agotados,
                'stock_bajo'   => (int)$inventario->stock_bajo,
            ],
            'punto_equilibrio' => [
                'gastos_fijos'  => $gastosFijos,
                'margen_bruto'  => $margenBruto,
                'punto'         => $puntoEquilibrio,
                'pct_alcanzado' => $pctEquilibrio,
                'ventas_mes'    => (float)$ventas->mes,
            ],
            'top_vendedores'   => $topVendedores,
            'top_productos'    => $topProductos,
            'ventas_por_bodega' => $ventasPorBodega,
            'stores'           => $stores,
            'periodo'          => [
                'mes_actual'    => date('F Y'),
                'dia'           => (int)date('j'),
                'dias_mes'      => (int)date('t'),
            ],
        ]);
    }

    /**
     * GET api/exec/pendientes
     * Presupuestos pendientes de facturar, con días de retraso.
     * Query params: store, sort (dias|total|client)
     */
    public function pendientes()
    {
        $payload = $this->_auth();
        $storeId = (int)$this->input->get('store') ?: 0;
        $storeWhere = $storeId > 0 ? "AND b.storeId = $storeId" : "";

        $pendientes = $this->db->query("
            SELECT b.idBudget, b.total, b.date, b.budget_type, b.comments,
                   b.storeId, s.name as storeName,
                   c.idClient, c.name as clientName, c.city,
                   u.name as vendorName, u.idUser as vendorId,
                   DATEDIFF(NOW(), b.date) as dias_retraso
            FROM budgets b
            INNER JOIN clients c ON c.idClient = b.clientId
            LEFT JOIN stores s ON s.idStore = b.storeId
            LEFT JOIN users u ON u.idUser = b.vendorId
            WHERE b.deleted = 0 AND b.state IN (0, 1)
              $storeWhere
            ORDER BY dias_retraso DESC
            LIMIT 100
        ")->result();

        // Resumen
        $totalPendiente = 0;
        $porVendedor = [];
        foreach ($pendientes as $p) {
            $totalPendiente += (float)$p->total;
            $vid = $p->vendorId ?: 'sin_asignar';
            if (!isset($porVendedor[$vid])) $porVendedor[$vid] = ['name' => $p->vendorName ?: 'Sin asignar', 'count' => 0, 'total' => 0];
            $porVendedor[$vid]['count']++;
            $porVendedor[$vid]['total'] += (float)$p->total;
        }
        usort($porVendedor, function($a, $b) { return $b['total'] - $a['total']; });

        $this->api_response->success([
            'pendientes' => $pendientes,
            'resumen' => [
                'total_valor' => $totalPendiente,
                'total_count' => count($pendientes),
                'por_vendedor' => array_values($porVendedor),
            ]
        ]);
    }

    /**
     * GET api/exec/cartera-detalle
     * Cartera detallada por cliente — cuánto debe, días, facturas.
     * Query params: store, sort (deuda|dias|name)
     */
    public function cartera_detalle()
    {
        $payload = $this->_auth();
        $storeId = (int)$this->input->get('store') ?: 0;
        $storeWhere = $storeId > 0 ? "AND i.storeId = $storeId" : "";

        $clientes = $this->db->query("
            SELECT c.idClient, c.name as clientName, c.city, c.cellphone, c.phone,
                   u.name as vendorName, u.idUser as vendorId,
                   COUNT(i.idInvoice) as facturas,
                   SUM(i.total - i.payment - i.discount) as deuda_total,
                   SUM(CASE WHEN DATEDIFF(NOW(), i.date) > 90 THEN i.total - i.payment - i.discount ELSE 0 END) as deuda_90,
                   SUM(CASE WHEN DATEDIFF(NOW(), i.date) > 30 AND DATEDIFF(NOW(), i.date) <= 90 THEN i.total - i.payment - i.discount ELSE 0 END) as deuda_30_90,
                   SUM(CASE WHEN DATEDIFF(NOW(), i.date) <= 30 THEN i.total - i.payment - i.discount ELSE 0 END) as deuda_0_30,
                   MAX(DATEDIFF(NOW(), i.date)) as dias_mayor,
                   MIN(i.date) as factura_mas_vieja
            FROM invoices i
            INNER JOIN clients c ON c.idClient = i.clientId
            LEFT JOIN users u ON u.idUser = c.vendor
            WHERE i.deleted = 0 AND (i.state = 0 OR i.state = 1)
              AND (i.total - i.payment - i.discount) > 0
              $storeWhere
            GROUP BY c.idClient
            ORDER BY deuda_total DESC
            LIMIT 100
        ")->result();

        $this->api_response->success([
            'clientes' => $clientes,
            'total_clientes' => count($clientes),
        ]);
    }

    /**
     * GET api/exec/cliente-detalle?id=X
     * Detalle de un cliente: facturas, presupuestos, historial.
     */
    public function cliente_detalle()
    {
        $payload = $this->_auth();
        $clientId = (int)$this->input->get('id');
        if (!$clientId) $this->api_response->error('id requerido', 400);

        $client = $this->db->select('c.*, u.name as vendorName, u.phone as vendorPhone')
            ->from('clients c')
            ->join('users u', 'u.idUser = c.vendor', 'left')
            ->where('c.idClient', $clientId)->get()->row();

        if (!$client) $this->api_response->error('Cliente no encontrado', 404);

        // Facturas pendientes
        $facturas = $this->db->query("
            SELECT i.idInvoice, i.date, i.total, i.payment, i.discount,
                   (i.total - i.payment - i.discount) as saldo,
                   DATEDIFF(NOW(), i.date) as dias, i.state,
                   s.name as storeName
            FROM invoices i
            LEFT JOIN stores s ON s.idStore = i.storeId
            WHERE i.clientId = ? AND i.deleted = 0 AND (i.state = 0 OR i.state = 1)
              AND (i.total - i.payment - i.discount) > 0
            ORDER BY i.date ASC
        ", [$clientId])->result();

        // Add product details to each invoice
        foreach ($facturas as &$f) {
            $f->productos = $this->db->query("
                SELECT id.productId, p.description, id.quantity, id.unit, id.total
                FROM invoice_details id
                LEFT JOIN products p ON p.idProduct = id.productId
                WHERE id.invoiceId = ?
            ", [$f->idInvoice])->result();
        }
        unset($f);

        // Presupuestos pendientes con detalle de productos
        $presupuestos = $this->db->query("
            SELECT b.idBudget, b.date, b.total, b.state, b.budget_type, b.comments,
                   DATEDIFF(NOW(), b.date) as dias_retraso,
                   s.name as storeName, u.name as vendorName
            FROM budgets b
            LEFT JOIN stores s ON s.idStore = b.storeId
            LEFT JOIN users u ON u.idUser = b.vendorId
            WHERE b.clientId = ? AND b.deleted = 0 AND b.state IN (0, 1)
            ORDER BY b.date DESC
        ", [$clientId])->result();

        // Add product details to each budget
        foreach ($presupuestos as &$b) {
            $b->productos = $this->db->query("
                SELECT bd.productId, p.description, bd.quantity, bd.unit, bd.total
                FROM budget_detail bd
                LEFT JOIN products p ON p.idProduct = bd.productId
                WHERE bd.budgetId = ?
            ", [$b->idBudget])->result();
        }
        unset($b);

        // Resumen
        $stats = $this->db->query("
            SELECT COALESCE(SUM(i.total), 0) as total_comprado,
                   COALESCE(SUM(i.payment), 0) as total_pagado,
                   COUNT(*) as total_facturas,
                   MAX(i.date) as ultima_compra
            FROM invoices i WHERE i.clientId = ? AND i.deleted = 0
        ", [$clientId])->row();

        $this->api_response->success([
            'client'       => $client,
            'facturas'     => $facturas,
            'presupuestos' => $presupuestos,
            'stats'        => $stats,
        ]);
    }

    // ---------------------------------------------------------------
    private function _auth()
    {
        $headers = $this->input->request_headers();
        $token = null;
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                if (preg_match('/Bearer\s+(.+)/i', $value, $matches)) $token = $matches[1];
                break;
            }
        }
        if (empty($token)) $this->api_response->error('Token requerido', 401);

        $payload = $this->jwt_lib->validateToken($token);
        if ($payload === false) $this->api_response->error('Token invalido', 401);

        // Solo admin y gerente
        if (!in_array($payload->role, ['1', '2'])) {
            $this->api_response->error('Acceso no autorizado', 403);
        }

        return $payload;
    }
}
