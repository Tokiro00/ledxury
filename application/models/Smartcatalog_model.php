<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Smart Catalog Model — Catálogo Inteligente MAM
 *
 * Queries inteligentes para:
 * - Catálogo con clasificación ABC por producto
 * - Top productos por cliente (lo que más compra)
 * - Recomendaciones basadas en historial
 * - Clasificación automática de clientes A/B/C por facturación
 * - Datos para campañas de fidelización
 * - Dashboard del cliente
 *
 * NO crea tablas nuevas. Usa las existentes.
 */
class Smartcatalog_model extends CI_Model {

    // ================================================================
    // CATÁLOGO INTELIGENTE
    // ================================================================

    /**
     * Productos con clasificación ABC y datos de venta
     */
    public function getSmartProducts($familyId = 0, $search = '', $onlyWithImg = false, $storeId = 0, $limit = 60, $offset = 0) {
        $this->db->select('p.idProduct, p.description, p.family, p.price, p.price_base, p.price_dist,
                           p.picture_url, p.is_national, p.location, p.abc_type,
                           f.name AS family_name,
                           COALESCE(inv.total_stock, 0) AS total_stock,
                           COALESCE(sales.total_vendido, 0) AS total_vendido,
                           COALESCE(sales.total_facturas, 0) AS total_facturas,
                           COALESCE(sales.revenue, 0) AS revenue');
        $this->db->from('products p');
        $this->db->join('product_families f', 'f.idFamily = p.family', 'left');
        // Stock total (o por tienda)
        if ($storeId > 0) {
            $this->db->join("(SELECT idProduct, stock AS total_stock FROM inventory WHERE idStore = {$storeId}) inv", 'inv.idProduct = p.idProduct', 'left');
        } else {
            $this->db->join('(SELECT idProduct, SUM(stock) AS total_stock FROM inventory GROUP BY idProduct) inv', 'inv.idProduct = p.idProduct', 'left');
        }
        // Ventas históricas
        $this->db->join('(SELECT d.productId, SUM(d.quantity) AS total_vendido, COUNT(DISTINCT d.invoiceId) AS total_facturas, SUM(d.total) AS revenue FROM invoice_details d GROUP BY d.productId) sales', 'sales.productId = p.idProduct', 'left');

        $this->db->where('p.deleted', 0);
        $this->db->where('p.price >', 0);

        if ($familyId > 0) $this->db->where('p.family', $familyId);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('p.idProduct', $search, 'both');
            $this->db->or_like('p.description', $search, 'both');
            $this->db->group_end();
        }

        if ($onlyWithImg) {
            $this->db->where('p.picture_url !=', 'products/no_image.png');
        }

        $this->db->order_by('CASE WHEN COALESCE(inv.total_stock, 0) > 0 THEN 0 ELSE 1 END', '', false);
        $this->db->order_by('sales.revenue', 'DESC');
        $this->db->limit($limit, $offset);

        $results = $this->db->get()->result();

        foreach ($results as &$r) {
            $r->hasImage = ($r->picture_url && $r->picture_url !== 'products/no_image.png');
        }

        return $results;
    }

    /**
     * Contar productos con filtros
     */
    public function countSmartProducts($familyId = 0, $search = '', $onlyWithImg = false) {
        $this->db->from('products p');
        $this->db->where('p.deleted', 0);
        $this->db->where('p.price >', 0);

        if ($familyId > 0) $this->db->where('p.family', $familyId);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('p.idProduct', $search, 'both');
            $this->db->or_like('p.description', $search, 'both');
            $this->db->group_end();
        }

        if ($onlyWithImg) {
            $this->db->where('p.picture_url !=', 'products/no_image.png');
        }

        return $this->db->count_all_results();
    }

    /**
     * Familias con conteo
     */
    public function getFamiliesWithCount() {
        $sql = "SELECT f.idFamily, f.name,
                       COUNT(p.idProduct) AS total_products,
                       SUM(CASE WHEN p.picture_url != 'products/no_image.png' THEN 1 ELSE 0 END) AS with_image
                FROM product_families f
                LEFT JOIN products p ON p.family = f.idFamily AND p.deleted = 0 AND p.price > 0
                GROUP BY f.idFamily
                ORDER BY total_products DESC";
        return $this->db->query($sql)->result();
    }

    /**
     * Stats generales del catálogo
     */
    public function getCatalogStats() {
        $total = $this->db->where('deleted', 0)->where('price >', 0)->count_all_results('products');
        $withImg = $this->db->where('deleted', 0)->where('price >', 0)->where('picture_url !=', 'products/no_image.png')->count_all_results('products');
        $totalStock = $this->db->select_sum('stock')->get('inventory')->row()->stock ?: 0;

        return (object)[
            'total' => $total,
            'withImg' => $withImg,
            'noImg' => $total - $withImg,
            'totalStock' => $totalStock,
        ];
    }

    // ================================================================
    // CLASIFICACIÓN ABC DE CLIENTES (automática)
    // ================================================================

    /**
     * Clasificar TODOS los clientes como A, B, C basado en facturación
     * A = Top 20% (80% del ingreso) — Clientes estrella
     * B = Siguiente 30% — Clientes importantes
     * C = Restante 50% — Clientes pequeños
     */
    public function classifyClients() {
        $sql = "SELECT c.idClient, c.name, c.commercial_name, c.city, c.type,
                       c.vendor, u.name AS vendor_name,
                       COUNT(DISTINCT i.idInvoice) AS total_facturas,
                       COALESCE(SUM(i.total), 0) AS total_comprado,
                       MAX(i.date) AS ultima_compra,
                       DATEDIFF(NOW(), MAX(i.date)) AS dias_sin_compra
                FROM clients c
                LEFT JOIN invoices i ON i.clientId = c.idClient AND i.state >= 0
                LEFT JOIN users u ON u.idUser = c.vendor
                WHERE c.deleted = 0
                GROUP BY c.idClient
                ORDER BY total_comprado DESC";

        $clients = $this->db->query($sql)->result();

        if (empty($clients)) return [];

        // Calcular total general
        $totalRevenue = array_sum(array_column($clients, 'total_comprado'));
        if ($totalRevenue == 0) return $clients;

        // Clasificación ABC por Pareto
        $cumulative = 0;
        foreach ($clients as &$c) {
            $cumulative += $c->total_comprado;
            $pct = ($cumulative / $totalRevenue) * 100;

            if ($pct <= 80) {
                $c->abc_class = 'A';
            } elseif ($pct <= 95) {
                $c->abc_class = 'B';
            } else {
                $c->abc_class = 'C';
            }

            $c->pct_revenue = round(($c->total_comprado / $totalRevenue) * 100, 2);

            // Estado de actividad
            if ($c->dias_sin_compra === null || $c->total_facturas == 0) {
                $c->status = 'inactivo';
            } elseif ($c->dias_sin_compra > 90) {
                $c->status = 'dormido';
            } elseif ($c->dias_sin_compra > 30) {
                $c->status = 'alerta';
            } else {
                $c->status = 'activo';
            }
        }

        return $clients;
    }

    /**
     * Resumen ABC de clientes
     */
    public function getClientABCSummary() {
        $clients = $this->classifyClients();

        $summary = [
            'A' => ['count' => 0, 'revenue' => 0, 'label' => 'Estrella', 'color' => 'yellow'],
            'B' => ['count' => 0, 'revenue' => 0, 'label' => 'Importante', 'color' => 'blue'],
            'C' => ['count' => 0, 'revenue' => 0, 'label' => 'Pequeño', 'color' => 'gray'],
        ];

        $statusSummary = [
            'activo' => 0, 'alerta' => 0, 'dormido' => 0, 'inactivo' => 0
        ];

        foreach ($clients as $c) {
            $summary[$c->abc_class]['count']++;
            $summary[$c->abc_class]['revenue'] += $c->total_comprado;
            $statusSummary[$c->status]++;
        }

        return (object)[
            'total' => count($clients),
            'abc' => $summary,
            'status' => $statusSummary,
            'clients' => $clients,
        ];
    }

    // ================================================================
    // PRODUCTOS TOP POR CLIENTE
    // ================================================================

    /**
     * Los productos que más compra un cliente específico
     */
    public function getClientTopProducts($clientId, $limit = 20) {
        // Primero obtenemos los productos top
        $sql = "SELECT d.productId AS idProduct, p.description, p.price, p.picture_url,
                       p.family, f.name AS family_name,
                       SUM(d.quantity) AS total_comprado,
                       COUNT(DISTINCT d.invoiceId) AS veces,
                       MAX(i.date) AS ultima_vez
                FROM invoice_details d
                INNER JOIN invoices i ON i.idInvoice = d.invoiceId
                INNER JOIN products p ON p.idProduct = d.productId AND p.deleted = 0
                LEFT JOIN product_families f ON f.idFamily = p.family
                WHERE i.clientId = ?
                GROUP BY d.productId
                ORDER BY total_comprado DESC
                LIMIT ?";

        $results = $this->db->query($sql, [$clientId, $limit])->result();

        // Luego para cada producto buscamos el precio de la última compra
        foreach ($results as &$r) {
            $lastPrice = $this->db->query("
                SELECT d2.unit FROM invoice_details d2
                INNER JOIN invoices i2 ON i2.idInvoice = d2.invoiceId
                WHERE i2.clientId = ? AND d2.productId = ?
                ORDER BY i2.date DESC LIMIT 1
            ", [$clientId, $r->idProduct])->row();
            $r->precio_promedio = $lastPrice ? $lastPrice->unit : $r->price;
        }
        foreach ($results as &$r) {
            $r->hasImage = ($r->picture_url && $r->picture_url !== 'products/no_image.png');
        }
        return $results;
    }

    /**
     * Historial de compras de un cliente por mes
     */
    public function getClientHistory($clientId, $months = 12) {
        $sql = "SELECT DATE_FORMAT(i.date, '%Y-%m') AS mes,
                       DATE_FORMAT(i.date, '%b %Y') AS mes_label,
                       COUNT(*) AS facturas,
                       SUM(i.total) AS total
                FROM invoices i
                WHERE i.clientId = ? AND i.state >= 0
                GROUP BY mes
                ORDER BY mes DESC
                LIMIT ?";
        return $this->db->query($sql, [$clientId, $months])->result();
    }

    /**
     * Resumen completo de un cliente
     */
    public function getClientDashboard($clientId) {
        // Info básica
        $client = $this->db->select('c.*, u.name AS vendor_name, u.phone AS vendor_phone')
            ->from('clients c')
            ->join('users u', 'u.idUser = c.vendor', 'left')
            ->where('c.idClient', $clientId)
            ->get()->row();

        if (!$client) return null;

        // Stats de compras
        $stats = $this->db->query("
            SELECT COUNT(DISTINCT i.idInvoice) AS total_facturas,
                   COALESCE(SUM(i.total), 0) AS total_comprado,
                   COALESCE(SUM(i.payment), 0) AS total_pagado,
                   MAX(i.date) AS ultima_compra,
                   MIN(i.date) AS primera_compra,
                   SUM(CASE WHEN i.state = 0 THEN i.total - i.payment ELSE 0 END) AS saldo_pendiente,
                   SUM(CASE WHEN i.is_expired = 1 THEN i.total - i.payment ELSE 0 END) AS saldo_vencido
            FROM invoices i WHERE i.clientId = ?
        ", [$clientId])->row();

        // Facturas recientes
        $invoices = $this->db->select('i.idInvoice, i.if_id, i.total, i.payment, i.date, i.state, i.is_expired, i.settled')
            ->from('invoices i')
            ->where('i.clientId', $clientId)
            ->order_by('i.date', 'DESC')
            ->limit(20)
            ->get()->result();

        // Presupuestos recientes
        $budgets = $this->db->select('b.idBudget, b.total, b.date, b.state, b.archived')
            ->from('budgets b')
            ->where('b.clientId', $clientId)
            ->where('b.archived', 0)
            ->order_by('b.date', 'DESC')
            ->limit(10)
            ->get()->result();

        // Top productos
        $topProducts = $this->getClientTopProducts($clientId, 10);

        // Historial mensual
        $history = $this->getClientHistory($clientId);

        return (object)[
            'client'      => $client,
            'stats'       => $stats,
            'invoices'    => $invoices,
            'budgets'     => $budgets,
            'topProducts' => $topProducts,
            'history'     => $history,
        ];
    }

    // ================================================================
    // RECOMENDACIONES
    // ================================================================

    /**
     * Productos que compran clientes similares pero este NO compra
     * "Clientes que compran X también compran Y"
     */
    public function getRecommendations($clientId, $limit = 10) {
        // Productos que este cliente YA compra
        $clientProducts = $this->db->query("
            SELECT DISTINCT d.productId
            FROM invoice_details d
            INNER JOIN invoices i ON i.idInvoice = d.invoiceId
            WHERE i.clientId = ?
        ", [$clientId])->result_array();

        $existingCodes = array_column($clientProducts, 'productId');
        if (empty($existingCodes)) return [];

        // Encontrar clientes que compran productos similares
        $placeholders = implode(',', array_fill(0, count($existingCodes), '?'));
        $sql = "SELECT d2.productId, p.description, p.price, p.picture_url, f.name AS family_name,
                       COUNT(DISTINCT i2.clientId) AS clientes_que_compran,
                       SUM(d2.quantity) AS total_vendido
                FROM invoice_details d2
                INNER JOIN invoices i2 ON i2.idInvoice = d2.invoiceId
                INNER JOIN products p ON p.idProduct = d2.productId AND p.deleted = 0
                LEFT JOIN product_families f ON f.idFamily = p.family
                WHERE i2.clientId IN (
                    SELECT DISTINCT i3.clientId
                    FROM invoice_details d3
                    INNER JOIN invoices i3 ON i3.idInvoice = d3.invoiceId
                    WHERE d3.productId IN ({$placeholders})
                    AND i3.clientId != ?
                )
                AND d2.productId NOT IN ({$placeholders})
                GROUP BY d2.productId
                ORDER BY clientes_que_compran DESC, total_vendido DESC
                LIMIT ?";

        $params = array_merge($existingCodes, [$clientId], $existingCodes, [$limit]);
        $results = $this->db->query($sql, $params)->result();

        foreach ($results as &$r) {
            $r->hasImage = ($r->picture_url && $r->picture_url !== 'products/no_image.png');
        }
        return $results;
    }

    // ================================================================
    // CAMPAÑAS Y FIDELIZACIÓN
    // ================================================================

    /**
     * Clientes dormidos (>30 días sin compra) con alto potencial
     */
    public function getSleepingClients($vendorId = '', $minRevenue = 1000000) {
        $sql = "SELECT c.idClient, c.name, c.commercial_name, c.city, c.type, c.vendor,
                       u.name AS vendor_name,
                       COUNT(DISTINCT i.idInvoice) AS total_facturas,
                       COALESCE(SUM(i.total), 0) AS total_comprado,
                       MAX(i.date) AS ultima_compra,
                       DATEDIFF(NOW(), MAX(i.date)) AS dias_sin_compra
                FROM clients c
                LEFT JOIN invoices i ON i.clientId = c.idClient AND i.state >= 0
                LEFT JOIN users u ON u.idUser = c.vendor
                WHERE c.deleted = 0
                GROUP BY c.idClient
                HAVING total_comprado >= ?
                AND dias_sin_compra > 30
                ORDER BY total_comprado DESC";

        $params = [$minRevenue];

        $results = $this->db->query($sql, $params)->result();

        if ($vendorId) {
            $results = array_filter($results, function($c) use ($vendorId) {
                return $c->vendor === $vendorId;
            });
        }

        return array_values($results);
    }

    /**
     * Clientes huérfanos (vendedor inactivo/vetado)
     */
    public function getOrphanClients() {
        $sql = "SELECT c.idClient, c.name, c.commercial_name, c.city, c.type, c.vendor,
                       u.name AS vendor_name, u.user_status AS vendor_status,
                       COUNT(DISTINCT i.idInvoice) AS total_facturas,
                       COALESCE(SUM(i.total), 0) AS total_comprado,
                       MAX(i.date) AS ultima_compra
                FROM clients c
                INNER JOIN users u ON u.idUser = c.vendor
                LEFT JOIN invoices i ON i.clientId = c.idClient
                WHERE c.deleted = 0
                AND (u.user_status = 'deactive' OR u.idUser LIKE '%vetado%' OR u.idUser LIKE '%veta%')
                GROUP BY c.idClient
                ORDER BY total_comprado DESC";
        return $this->db->query($sql)->result();
    }

    // ================================================================
    // VENDEDORES: RANKING Y METAS
    // ================================================================

    /**
     * Ranking de vendedores del mes actual
     */
    public function getVendorRanking($month = null, $year = null) {
        if (!$month) $month = date('n');
        if (!$year) $year = date('Y');

        $sql = "SELECT u.idUser, u.name, u.phone, u.store,
                       s.name AS store_name,
                       COUNT(DISTINCT i.idInvoice) AS facturas_mes,
                       COALESCE(SUM(i.total), 0) AS venta_mes,
                       COUNT(DISTINCT i.clientId) AS clientes_atendidos,
                       sg.m{$month} AS meta_mes
                FROM users u
                LEFT JOIN invoices i ON i.vendorId = u.idUser
                    AND MONTH(i.date) = ? AND YEAR(i.date) = ?
                LEFT JOIN stores s ON s.idStore = u.store
                LEFT JOIN sales_goal sg ON sg.userId = u.idUser AND sg.year = ?
                WHERE u.user_status = 'active'
                AND u.archived = 0
                AND u.idUser NOT LIKE '%vetado%'
                AND u.idUser NOT LIKE '%veta%'
                AND (u.role = 3 OR u.by_commission = 1)
                GROUP BY u.idUser
                ORDER BY venta_mes DESC";

        $results = $this->db->query($sql, [$month, $year, $year])->result();

        foreach ($results as &$r) {
            $r->meta_mes = $r->meta_mes ?: 30000000; // Default $30M si no tiene meta
            $r->cumplimiento = $r->meta_mes > 0 ? round(($r->venta_mes / $r->meta_mes) * 100, 1) : 0;
        }

        return $results;
    }

    // ================================================================
    // STOCK POR TIENDA
    // ================================================================

    /**
     * Stock de un producto por tienda
     */
    public function getProductStock($productId) {
        return $this->db->select('i.stock, s.name AS store_name, s.idStore')
            ->from('inventory i')
            ->join('stores s', 's.idStore = i.idStore')
            ->where('i.idProduct', $productId)
            ->where('i.stock >', 0)
            ->get()->result();
    }
}
