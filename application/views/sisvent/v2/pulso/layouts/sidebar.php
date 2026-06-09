<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pulso sidebar — refleja TODO el sidemenu v1 activo (no archivados).
 * Cada link va a su URL canónica v1; las pantallas migradas a Pulso
 * (Dashboard/Presupuestos/Facturas/Clientes/Productos) viven en
 * /sisvent/v2/* hasta que unifiquemos URLs.
 *
 * Espera $activeRoute (string id).
 */
$ud = $this->session->userdata('user_data') ?: array();
$userName = !empty($ud['name']) ? $ud['name'] : ($ud['uname'] ?? 'Usuario');
$userRole = isset($ud['role']) ? (int)$ud['role'] : 1;
$roleLabel = array(1=>'Administrador',2=>'Gerente',3=>'Vendedor',4=>'Contador',9=>'Operador',10=>'Super admin')[$userRole] ?? 'Usuario';
$initials = strtoupper(mb_substr($userName, 0, 1) . mb_substr(explode(' ', $userName)[1] ?? '', 0, 1));
$activeRoute = $activeRoute ?? '';

$isActive = function($id) use ($activeRoute) { return $activeRoute === $id ? 'is-active' : ''; };

// Definimos qué grupos abren si alguno de sus hijos coincide
$groupChildren = array(
    'ventas'      => array('presupuestos','archivados','facturas','notas-credito','clientes','vendedores'),
    'inventario'  => array('productos','conteo','catalogo'),
    'envios'      => array('envios','logistica','devoluciones'),
    'tesoreria'   => array('dashboard-fin','cajas','bancos','movimientos','abonos','interrapidisimo'),
    'compras'     => array('fact-prov','gastos','categorias-gasto','generar-ordenes','ordenes','reglas-compra','proveedores'),
    'reportes'    => array('rep-ventas','rep-vendedores','rep-top-prod','rep-yoy','rep-rent-prod','rep-rent-vendor','rep-aging','rep-abc','rep-cartera-ciudad','rep-cashflow','rep-mov-cajas','rep-cxp','rep-gastos','rep-comisiones','rep-comprobacion','rep-balance','rep-resultados','rep-inv-val','rep-inv-rot','rep-todos'),
    'ledxury-ai'  => array('asistente-ia','resumen-diario','bots','wa-web','agotados','garantias','cola-bots','comisiones-bot'),
);
$isOpen = function($groupId) use ($activeRoute, $groupChildren) {
    return (isset($groupChildren[$groupId]) && in_array($activeRoute, $groupChildren[$groupId])) ? 'is-open' : '';
};
?>
<aside class="pulso-sidebar">

    <!-- Wordmark + workspace switcher -->
    <div style="padding: 18px 14px 12px; display:flex; align-items:center; gap:10px;">
        <svg width="32" height="32" viewBox="0 0 48 48" fill="none">
            <rect width="48" height="48" rx="14" fill="var(--pulso-accent)"/>
            <path d="M8 24 H14 L17 17 L22 31 L26 14 L31 28 L34 24 H40" stroke="var(--pulso-bg)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        </svg>
        <span style="font-family: var(--pulso-font-ui); font-size: 22px; font-weight: 800; letter-spacing: -0.04em; color: var(--pulso-ink); line-height: 1;">
            pulso<span style="color: var(--pulso-accent);">.</span>
        </span>
    </div>
    <a href="<?= base_url() ?>sisvent/dashboard" class="pulso-ws-switch" title="Cambiar de empresa/bodega" style="margin-top:0;">
        <span class="pulso-ws-logo">L</span>
        <span class="pulso-ws-meta">
            <strong>MAM Ledxury</strong>
            <span>Almacén 1 · Medellín</span>
        </span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="color:var(--pulso-ink3);flex:0 0 auto;">
            <path d="m7 15 5 5 5-5 M7 9l5-5 5 5"/>
        </svg>
    </a>

    <!-- Search trigger -->
    <button type="button" class="pulso-search-trigger" onclick="document.dispatchEvent(new CustomEvent('pulso:openSearch'))">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
        </svg>
        <span style="flex:1;text-align:left;">Buscar…</span>
        <span class="pulso-kbd">⌘K</span>
    </button>

    <!-- Navigation -->
    <nav class="pulso-nav">

        <!-- INICIO -->
        <div class="pulso-nav-group">
            <a href="<?= base_url() ?>sisvent/dashboard" class="pulso-nav-item <?= $isActive('dashboard') ?>">
                <span class="pulso-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3v18h18 M7 16l4-4 4 4 6-6"/>
                    </svg>
                </span>
                <span class="pulso-nav-label">Inicio</span>
            </a>
        </div>

        <!-- OPERACIÓN -->
        <div class="pulso-nav-group">
            <div class="pulso-nav-group-title">Operación</div>

            <a href="#" class="pulso-nav-item" data-pulso-toggle="ventas">
                <span class="pulso-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-6l-2 3h-4l-2-3H2 M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"/>
                    </svg>
                </span>
                <span class="pulso-nav-label">Ventas</span>
                <span class="pulso-nav-icon" style="transform:rotate(-90deg);transition:transform .15s;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </a>
            <div class="pulso-nav-children <?= $isOpen('ventas') ?>" data-pulso-children="ventas">
                <a href="<?= base_url() ?>sisvent/commercial/budgets"      class="pulso-nav-child <?= $isActive('presupuestos') ?>"><span style="flex:1;">Presupuestos</span></a>
                <a href="<?= base_url() ?>sisvent/commercial/budgets/archived" class="pulso-nav-child <?= $isActive('archivados') ?>"><span style="flex:1;">Archivados</span></a>
                <a href="<?= base_url() ?>sisvent/commercial/invoices"          class="pulso-nav-child <?= $isActive('facturas') ?>"><span style="flex:1;">Facturas</span></a>
                <?php if (function_exists('has_permission') && has_permission('notas_credito')): ?>
                <a href="<?= base_url() ?>sisvent/commercial/creditnotes" class="pulso-nav-child <?= $isActive('notas-credito') ?>"><span style="flex:1;">Notas crédito</span></a>
                <?php endif; ?>
                <a href="<?= base_url() ?>sisvent/business/clients"          class="pulso-nav-child <?= $isActive('clientes') ?>"><span style="flex:1;">Clientes</span></a>
                <?php if (function_exists('has_permission') && has_permission('vendedores')): ?>
                <a href="<?= base_url() ?>sisvent/business/vendors"     class="pulso-nav-child <?= $isActive('vendedores') ?>"><span style="flex:1;">Vendedores</span></a>
                <?php endif; ?>
            </div>

            <a href="#" class="pulso-nav-item" data-pulso-toggle="envios">
                <span class="pulso-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/>
                        <path d="M15 18H9 M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/>
                        <circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>
                    </svg>
                </span>
                <span class="pulso-nav-label">Envíos</span>
                <span class="pulso-nav-icon" style="transform:rotate(-90deg);transition:transform .15s;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </a>
            <div class="pulso-nav-children <?= $isOpen('envios') ?>" data-pulso-children="envios">
                <a href="<?= base_url() ?>sisvent/admin/envios"      class="pulso-nav-child <?= $isActive('envios') ?>"><span style="flex:1;">Dashboard envíos</span></a>
                <a href="<?= base_url() ?>sisvent/admin/logistics"   class="pulso-nav-child <?= $isActive('logistica') ?>"><span style="flex:1;">Reporte logística</span></a>
                <a href="<?= base_url() ?>sisvent/admin/devoluciones" class="pulso-nav-child <?= $isActive('devoluciones') ?>"><span style="flex:1;">Devoluciones</span></a>
            </div>

            <a href="<?= base_url() ?>sisvent/business/clients" class="pulso-nav-item <?= $isActive('clientes') ?>">
                <span class="pulso-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </span>
                <span class="pulso-nav-label">Clientes</span>
            </a>

            <a href="#" class="pulso-nav-item" data-pulso-toggle="inventario">
                <span class="pulso-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z M3.27 6.96 12 12.01l8.73-5.05 M12 22.08V12"/>
                    </svg>
                </span>
                <span class="pulso-nav-label">Inventario</span>
                <span class="pulso-nav-icon" style="transform:rotate(-90deg);transition:transform .15s;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </a>
            <div class="pulso-nav-children <?= $isOpen('inventario') ?>" data-pulso-children="inventario">
                <a href="<?= base_url() ?>sisvent/business/products"  class="pulso-nav-child <?= $isActive('productos') ?>"><span style="flex:1;">Productos</span></a>
                <a href="<?= base_url() ?>sisvent/store/count"   class="pulso-nav-child <?= $isActive('conteo') ?>"><span style="flex:1;">Conteo diario</span></a>
                <a href="<?= base_url() ?>sisvent/store/catalogue" class="pulso-nav-child <?= $isActive('catalogo') ?>"><span style="flex:1;">Catálogo</span></a>
            </div>
        </div>

        <!-- FINANZAS -->
        <div class="pulso-nav-group">
            <div class="pulso-nav-group-title">Finanzas</div>

            <a href="<?= base_url() ?>sisvent/admin/settlements" class="pulso-nav-item <?= $isActive('liquidaciones') ?>">
                <span class="pulso-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                        <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                    </svg>
                </span>
                <span class="pulso-nav-label">Liquidaciones</span>
            </a>

            <a href="#" class="pulso-nav-item" data-pulso-toggle="tesoreria">
                <span class="pulso-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <span class="pulso-nav-label">Tesorería</span>
                <span class="pulso-nav-icon" style="transform:rotate(-90deg);transition:transform .15s;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </a>
            <div class="pulso-nav-children <?= $isOpen('tesoreria') ?>" data-pulso-children="tesoreria">
                <a href="<?= base_url() ?>sisvent/admin/financialdashboard" class="pulso-nav-child <?= $isActive('dashboard-fin') ?>"><span style="flex:1;">Dashboard financiero</span></a>
                <a href="<?= base_url() ?>sisvent/admin/cashboxes"          class="pulso-nav-child <?= $isActive('cajas') ?>"><span style="flex:1;">Cajas</span></a>
                <a href="<?= base_url() ?>sisvent/admin/bankaccounts"       class="pulso-nav-child <?= $isActive('bancos') ?>"><span style="flex:1;">Bancos</span></a>
                <a href="<?= base_url() ?>sisvent/admin/cashmovements"      class="pulso-nav-child <?= $isActive('movimientos') ?>"><span style="flex:1;">Movimientos</span></a>
                <a href="<?= base_url() ?>sisvent/admin/payments"           class="pulso-nav-child <?= $isActive('abonos') ?>"><span style="flex:1;">Abonos</span></a>
                <a href="<?= base_url() ?>sisvent/admin/contrapagos"        class="pulso-nav-child <?= $isActive('interrapidisimo') ?>"><span style="flex:1;">Interrapidísimo</span></a>
            </div>

            <a href="#" class="pulso-nav-item" data-pulso-toggle="compras">
                <span class="pulso-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                    </svg>
                </span>
                <span class="pulso-nav-label">Compras</span>
                <span class="pulso-nav-icon" style="transform:rotate(-90deg);transition:transform .15s;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </a>
            <div class="pulso-nav-children <?= $isOpen('compras') ?>" data-pulso-children="compras">
                <a href="<?= base_url() ?>sisvent/admin/accountspayable"   class="pulso-nav-child <?= $isActive('fact-prov') ?>"><span style="flex:1;">Facturas proveedor</span></a>
                <a href="<?= base_url() ?>sisvent/admin/expenses"          class="pulso-nav-child <?= $isActive('gastos') ?>"><span style="flex:1;">Gastos operacionales</span></a>
                <a href="<?= base_url() ?>sisvent/admin/expensecategories" class="pulso-nav-child <?= $isActive('categorias-gasto') ?>"><span style="flex:1;">Categorías de gasto</span></a>
                <a href="<?= base_url() ?>sisvent/store/reorder/agent"     class="pulso-nav-child <?= $isActive('generar-ordenes') ?>"><span style="flex:1;">Generar órdenes</span></a>
                <a href="<?= base_url() ?>sisvent/store/reorder/orders"    class="pulso-nav-child <?= $isActive('ordenes') ?>"><span style="flex:1;">Órdenes a proveedor</span></a>
                <a href="<?= base_url() ?>sisvent/admin/purchaserules"     class="pulso-nav-child <?= $isActive('reglas-compra') ?>"><span style="flex:1;">Reglas automáticas</span></a>
                <a href="<?= base_url() ?>sisvent/business/providers"      class="pulso-nav-child <?= $isActive('proveedores') ?>"><span style="flex:1;">Proveedores</span></a>
            </div>
        </div>

        <!-- CRECIMIENTO -->
        <div class="pulso-nav-group">
            <div class="pulso-nav-group-title">Crecimiento</div>

            <a href="#" class="pulso-nav-item" data-pulso-toggle="reportes">
                <span class="pulso-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3v18h18 M7 16l4-4 4 4 6-6"/>
                    </svg>
                </span>
                <span class="pulso-nav-label">Reportes</span>
                <span class="pulso-nav-icon" style="transform:rotate(-90deg);transition:transform .15s;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </a>
            <div class="pulso-nav-children <?= $isOpen('reportes') ?>" data-pulso-children="reportes">
                <a href="<?= base_url() ?>sisvent/admin/reports/v2" class="pulso-nav-child <?= $isActive('rep-todos') ?>" style="font-weight:600;color:var(--pulso-accent);"><span style="flex:1;">Todos los reportes →</span></a>
                <div style="font-size:10px; padding:8px 10px 4px; color:var(--pulso-ink3); text-transform:uppercase; letter-spacing:0.06em; font-weight:700;">Ventas</div>
                <a href="<?= base_url() ?>sisvent/admin/reports/daily"               class="pulso-nav-child <?= $isActive('rep-ventas') ?>"><span style="flex:1;">Ventas por día</span></a>
                <a href="<?= base_url() ?>sisvent/admin/reports/vendorPerformance"   class="pulso-nav-child <?= $isActive('rep-vendedores') ?>"><span style="flex:1;">Rendimiento vendedores</span></a>
                <a href="<?= base_url() ?>sisvent/admin/reports/topProducts"         class="pulso-nav-child <?= $isActive('rep-top-prod') ?>"><span style="flex:1;">Top productos</span></a>
                <a href="<?= base_url() ?>sisvent/admin/reports/salesYoY"            class="pulso-nav-child <?= $isActive('rep-yoy') ?>"><span style="flex:1;">Ventas año vs año</span></a>
                <a href="<?= base_url() ?>sisvent/admin/reports/productProfitability" class="pulso-nav-child <?= $isActive('rep-rent-prod') ?>"><span style="flex:1;">Rentabilidad producto</span></a>
                <a href="<?= base_url() ?>sisvent/admin/reports/vendorProfitability"  class="pulso-nav-child <?= $isActive('rep-rent-vendor') ?>"><span style="flex:1;">Rentabilidad vendedor</span></a>
                <div style="font-size:10px; padding:8px 10px 4px; color:var(--pulso-ink3); text-transform:uppercase; letter-spacing:0.06em; font-weight:700;">Cartera</div>
                <a href="<?= base_url() ?>sisvent/admin/reports/aging"               class="pulso-nav-child <?= $isActive('rep-aging') ?>"><span style="flex:1;">Antigüedad saldos</span></a>
                <a href="<?= base_url() ?>sisvent/admin/reports/clientsABC"          class="pulso-nav-child <?= $isActive('rep-abc') ?>"><span style="flex:1;">Análisis clientes ABC</span></a>
                <a href="<?= base_url() ?>sisvent/admin/reports/debtByCity"          class="pulso-nav-child <?= $isActive('rep-cartera-ciudad') ?>"><span style="flex:1;">Cartera por ciudad</span></a>
                <div style="font-size:10px; padding:8px 10px 4px; color:var(--pulso-ink3); text-transform:uppercase; letter-spacing:0.06em; font-weight:700;">Contable</div>
                <a href="<?= base_url() ?>sisvent/admin/reports/cashFlow"            class="pulso-nav-child <?= $isActive('rep-cashflow') ?>"><span style="flex:1;">Flujo de efectivo</span></a>
                <a href="<?= base_url() ?>sisvent/admin/cashmovements"               class="pulso-nav-child <?= $isActive('rep-mov-cajas') ?>"><span style="flex:1;">Mov. cajas y bancos</span></a>
                <a href="<?= base_url() ?>sisvent/admin/reports/providerStatement"   class="pulso-nav-child <?= $isActive('rep-cxp') ?>"><span style="flex:1;">Cuentas por pagar</span></a>
                <a href="<?= base_url() ?>sisvent/admin/reports/expensesByCategory"  class="pulso-nav-child <?= $isActive('rep-gastos') ?>"><span style="flex:1;">Gastos por categoría</span></a>
                <a href="<?= base_url() ?>sisvent/admin/reports/vendorCommissions"   class="pulso-nav-child <?= $isActive('rep-comisiones') ?>"><span style="flex:1;">Comisiones vendedores</span></a>
                <a href="<?= base_url() ?>sisvent/accounting/reports/comprobacion"   class="pulso-nav-child <?= $isActive('rep-comprobacion') ?>"><span style="flex:1;">Balance comprobación</span></a>
                <a href="<?= base_url() ?>sisvent/accounting/reports/balance"        class="pulso-nav-child <?= $isActive('rep-balance') ?>"><span style="flex:1;">Balance general</span></a>
                <a href="<?= base_url() ?>sisvent/accounting/reports/resultados"     class="pulso-nav-child <?= $isActive('rep-resultados') ?>"><span style="flex:1;">Estado de resultados</span></a>
                <div style="font-size:10px; padding:8px 10px 4px; color:var(--pulso-ink3); text-transform:uppercase; letter-spacing:0.06em; font-weight:700;">Inventario</div>
                <a href="<?= base_url() ?>sisvent/admin/reports/inventoryValuation"  class="pulso-nav-child <?= $isActive('rep-inv-val') ?>"><span style="flex:1;">Inventario valorizado</span></a>
                <a href="<?= base_url() ?>sisvent/admin/reports/inventoryRotation"   class="pulso-nav-child <?= $isActive('rep-inv-rot') ?>"><span style="flex:1;">Rotación inventario</span></a>
            </div>

            <a href="#" class="pulso-nav-item" data-pulso-toggle="ledxury-ai">
                <span class="pulso-nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="10" rx="2"/>
                        <circle cx="12" cy="5" r="2"/>
                        <path d="M12 7v4 M8 16h.01 M16 16h.01"/>
                    </svg>
                </span>
                <span class="pulso-nav-label">Ledxury AI</span>
                <span class="pulso-tag">IA</span>
                <span class="pulso-nav-icon" style="transform:rotate(-90deg);transition:transform .15s;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </a>
            <div class="pulso-nav-children <?= $isOpen('ledxury-ai') ?>" data-pulso-children="ledxury-ai">
                <a href="<?= base_url() ?>sisvent/admin/aiassistant"          class="pulso-nav-child <?= $isActive('asistente-ia') ?>"><span style="flex:1;">Asistente IA</span></a>
                <a href="<?= base_url() ?>sisvent/admin/agents/dailySummary"  class="pulso-nav-child <?= $isActive('resumen-diario') ?>"><span style="flex:1;">Resumen diario</span></a>
                <a href="<?= base_url() ?>sisvent/admin/bots"                 class="pulso-nav-child <?= $isActive('bots') ?>"><span style="flex:1;">Bots WhatsApp</span></a>
                <a href="<?= base_url() ?>sisvent/admin/bots/whatsapp"        class="pulso-nav-child <?= $isActive('wa-web') ?>"><span style="flex:1;">WhatsApp Web</span></a>
                <a href="<?= base_url() ?>sisvent/admin/bots/agotados"        class="pulso-nav-child <?= $isActive('agotados') ?>"><span style="flex:1;">Agotados</span></a>
                <a href="<?= base_url() ?>sisvent/admin/garantias"            class="pulso-nav-child <?= $isActive('garantias') ?>"><span style="flex:1;">Garantías</span></a>
                <a href="<?= base_url() ?>sisvent/admin/botsqueue"            class="pulso-nav-child <?= $isActive('cola-bots') ?>"><span style="flex:1;">Cola de bots</span></a>
                <a href="<?= base_url() ?>sisvent/admin/comisiones"           class="pulso-nav-child <?= $isActive('comisiones-bot') ?>"><span style="flex:1;">Comisiones bot</span></a>
            </div>
        </div>

    </nav>

    <!-- Footer: user + settings (con menú config completo) -->
    <div class="pulso-sidebar-footer">
        <a href="<?= base_url() ?>sisvent/profile" class="pulso-user-trigger">
            <span class="pulso-avatar"><?= htmlspecialchars($initials) ?></span>
            <span class="pulso-user-meta">
                <span class="pulso-user-name"><?= htmlspecialchars($userName) ?></span>
                <span class="pulso-user-role"><?= htmlspecialchars($roleLabel) ?></span>
            </span>
        </a>
        <a href="<?= base_url() ?>sisvent/business/users" class="pulso-icon-btn" title="Configuración: Usuarios, Roles, Almacenes, Importar, Config. Contable">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </a>
    </div>
</aside>

<script>
// Submenu toggle (vanilla)
document.addEventListener('click', function(e) {
    var t = e.target.closest('[data-pulso-toggle]');
    if (!t) return;
    e.preventDefault();
    var id = t.getAttribute('data-pulso-toggle');
    var children = document.querySelector('[data-pulso-children="' + id + '"]');
    if (children) children.classList.toggle('is-open');
    var chevron = t.querySelector('.pulso-nav-icon:last-child');
    if (chevron) {
        var isOpen = children && children.classList.contains('is-open');
        chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(-90deg)';
    }
});
</script>
