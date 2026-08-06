<?php
/**
 * Ledxury · Sidemenu consolidado (6 secciones)
 *
 * Reorganización del 2026-05-22: de 13 secciones + ~70 items a:
 *   1. Inicio
 *   2. Operación        (Ventas + Envíos)
 *   3. Productos & Compras
 *   4. Finanzas         (Tesorería + Cartera + Liquidaciones)
 *   5. Reportes         (link al engine v2)
 *   6. Ledxury AI
 *   7. Configuración
 *
 * Backup del previo en sidemenu.php.bak-YYYYMMDD_HHMMSS.
 *
 * Vue toggles reusados:
 *   - isVentasMenuOpen      → Operación
 *   - isComprasMenuOpen     → Productos & Compras
 *   - isTesoreriaMenuOpen   → Finanzas
 *   - isAiMenuOpen          → Ledxury AI
 *   - isConfigMenuOpen      → Configuración
 */
$ud = $this->session->userdata('user_data');
$bots_access = !empty($ud['bots_access']) ? (int)$ud['bots_access'] : 0;
?>
<div class="py-4 text-gray-300">
  <a class="ml-6 text-lg font-bold text-white" href="#">Ledxury</a>

  <!-- ================================================================ -->
  <!-- 1. INICIO -->
  <!-- ================================================================ -->
  <ul class="mt-6">
    <li class="relative px-6 py-3">
      <?php if (in_array($thisFile, ['sisvent/dashboard'])): $dashboard_sel = 'text-white'; ?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg"></span>
      <?php endif; ?>
      <a class="inline-flex items-center w-full text-sm <?= isset($dashboard_sel) ? $dashboard_sel : '' ?> font-semibold transition-colors duration-150 hover:text-white" href="<?= base_url() ?>sisvent/dashboard">
        <svg class="w-5 h-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span class="ml-4">Inicio</span>
      </a>
    </li>
  </ul>

  <ul>

  <!-- ================================================================ -->
  <!-- 2. OPERACIÓN — Ventas + Envíos -->
  <!-- ================================================================ -->
    <li class="relative px-6 py-3">
      <?php
        $operacionPaths = [
          'sisvent/commercial/budgets/list','sisvent/commercial/budgets/add','sisvent/commercial/budgets/edit',
          'sisvent/commercial/invoices/list','sisvent/commercial/invoices/add','sisvent/commercial/invoices/edit',
          'sisvent/commercial/invoices/refunds','sisvent/commercial/invoices/viewrefund',
          'sisvent/commercial/creditnotes/list','sisvent/commercial/creditnotes/add','sisvent/commercial/creditnotes/view',
          'sisvent/business/clients/list','sisvent/business/clients/add','sisvent/business/clients/edit',
          'sisvent/business/vendors/list','sisvent/business/vendors/add','sisvent/business/vendors/edit',
          'sisvent/admin/envios/index','sisvent/admin/envios/view','sisvent/admin/envios/estado_cuenta',
          'sisvent/admin/logistics/report','sisvent/admin/devoluciones/list',
        ];
        if (in_array($thisFile, $operacionPaths)): $ventas_sel = 'text-white';
      ?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?= isset($ventas_sel) ? $ventas_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" @click="toggleVentasMenu">
        <span class="inline-flex items-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
          <span class="ml-4">Operación</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
      </button>
      <transition name="fade">
        <ul v-if="isVentasMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/budgets">Presupuestos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/budgets/archived">Archivados</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/invoices">Facturas</a>
          </li>
          <?php if (has_permission('notas_credito')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/creditnotes">Notas Crédito</a>
          </li>
          <?php endif; ?>

          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Maestros</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/clients">Clientes</a>
          </li>
          <?php if (has_permission('vendedores')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/vendors">Vendedores</a>
          </li>
          <?php endif; ?>

          <?php if (has_permission('envios') || has_permission('reporte_logistica')): ?>
          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Envíos</li>
          <?php if (has_permission('envios')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/envios">Dashboard Envíos</a>
          </li>
          <?php endif; ?>
          <?php if (has_permission('reporte_logistica')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/logistics">Reporte Logística</a>
          </li>
          <?php endif; ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/devoluciones">Devoluciones</a>
          </li>
          <?php endif; ?>
        </ul>
      </transition>
    </li>

  <!-- ================================================================ -->
  <!-- 3. PRODUCTOS & COMPRAS -->
  <!-- ================================================================ -->
    <?php if (
      has_permission('inventario') || has_permission('traspasos') ||
      has_permission('compras_reorden') || has_permission('cuentas_pagar')
    ): ?>
    <li class="relative px-6 py-3">
      <?php
        $productosPaths = [
          'sisvent/business/products/list','sisvent/business/products/add','sisvent/business/products/edit',
          'sisvent/store/inventory/index','sisvent/store/transfers/index','sisvent/store/count/index','sisvent/store/catalogue/index',
          'sisvent/store/reorder/abc','sisvent/store/reorder/agent','sisvent/store/reorder/orders',
          'sisvent/admin/purchaserules/list','sisvent/business/providers/list','sisvent/business/providers/edit',
          'sisvent/admin/accountspayable/list','sisvent/admin/accountspayable/add','sisvent/admin/accountspayable/view','sisvent/admin/accountspayable/pay',
        ];
        if (in_array($thisFile, $productosPaths)): $compras_sel = 'text-white';
      ?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?= isset($compras_sel) ? $compras_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" @click="toggleComprasMenu">
        <span class="inline-flex items-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          <span class="ml-4">Productos & Compras</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
      </button>
      <transition name="fade">
        <ul v-if="isComprasMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/products">Productos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/catalogue">Catálogo</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/count">Conteo Diario</a>
          </li>

          <?php if (has_permission('compras_reorden')): ?>
          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Órdenes de Compra</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/reorder/agent">Generar Órdenes</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/reorder/orders">Órdenes a Proveedor</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/purchaserules">Reglas Automáticas</a>
          </li>
          <?php endif; ?>

          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Proveedores</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/providers">Proveedores</a>
          </li>
          <?php if (has_permission('cuentas_pagar')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/accountspayable">Facturas Proveedor</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full text-mam-orange" href="<?= base_url() ?>sisvent/admin/accountspayable/closeCycleMam">⚡ Cierre Compra MAM</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" style="color:#A855F7;" href="<?= base_url() ?>sisvent/admin/accountspayable/returnToMam">📦 Devolución a MAM</a>
          </li>
          <?php endif; ?>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

  <!-- ================================================================ -->
  <!-- 4. FINANZAS — Tesorería + Cartera + Liquidaciones -->
  <!-- ================================================================ -->
    <?php if (has_permission('caja_bancos') || has_permission('cartera') || has_permission('liquidaciones') || has_permission('gastos')): ?>
    <li class="relative px-6 py-3">
      <?php
        $finanzasPaths = [
          'sisvent/admin/financialdashboard/index',
          'sisvent/admin/cashboxes/list','sisvent/admin/cashboxes/view',
          'sisvent/admin/bankaccounts/list','sisvent/admin/bankaccounts/view',
          'sisvent/admin/cashmovements/list','sisvent/admin/cashmovements/add','sisvent/admin/cashmovements/view',
          'sisvent/admin/payments/list','sisvent/admin/payments/add',
          'sisvent/admin/contrapagos/index','sisvent/admin/contrapagos/invoices','sisvent/admin/contrapagos/invoice_detail',
          'sisvent/admin/settlements/list','sisvent/admin/settlements/statement','sisvent/admin/settlements/detail',
          'sisvent/admin/expenses/list','sisvent/admin/expenses/add','sisvent/admin/expenses/edit','sisvent/admin/expenses/view',
          'sisvent/admin/expensecategories/list','sisvent/admin/expensecategories/add','sisvent/admin/expensecategories/edit',
        ];
        if (in_array($thisFile, $finanzasPaths)): $tesoreria_sel = 'text-white';
      ?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?= isset($tesoreria_sel) ? $tesoreria_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" @click="toggleTesoreriaMenu">
        <span class="inline-flex items-center">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span class="ml-4">Finanzas</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
      </button>
      <transition name="fade">
        <ul v-if="isTesoreriaMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)">
          <?php if (has_permission('caja_bancos')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white font-semibold">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/financialdashboard">Dashboard Financiero</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/cashboxes">Cajas</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/bankaccounts">Bancos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/cashmovements">Movimientos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/payments">Abonos</a>
          </li>
          <?php endif; ?>

          <?php if (has_permission('contrapagos') || has_permission('envios')): ?>
          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Contrapago</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/contrapagos">Pagos Interrapidísimo</a>
          </li>
          <?php endif; ?>

          <?php if (has_permission('gastos')): ?>
          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Gastos</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/expenses">Gastos Operacionales</a>
          </li>
          <?php if (has_permission('categorias_gastos')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/expensecategories">Categorías de Gasto</a>
          </li>
          <?php endif; ?>
          <?php endif; ?>

          <?php if (has_permission('liquidaciones')): ?>
          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Vendedores</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/settlements">Liquidaciones</a>
          </li>
          <?php endif; ?>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

  <!-- ================================================================ -->
  <!-- 5. REPORTES — engine v2 (link directo) -->
  <!-- ================================================================ -->
    <?php if (has_permission('reportes_ventas') || has_permission('reportes_contables')): ?>
    <li class="relative px-6 py-3">
      <?php if (strpos($thisFile, 'sisvent/admin/reports') === 0 || strpos($thisFile, 'sisvent/accounting/reports') === 0): $reportes_sel = 'text-white'; ?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg"></span>
      <?php endif; ?>
      <a class="inline-flex items-center w-full text-sm <?= isset($reportes_sel) ? $reportes_sel : '' ?> font-semibold transition-colors duration-150 hover:text-white" href="<?= base_url() ?>sisvent/admin/reports/v2">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        <span class="ml-4">Reportes</span>
      </a>
    </li>
    <?php endif; ?>

  <!-- ================================================================ -->
  <!-- 6. LEDXURY AI — Bots, WhatsApp, Asistente, Comisiones bots -->
  <!-- ================================================================ -->
    <?php if ($role == 1 || $role == 10 || $bots_access): ?>
    <li class="relative px-6 py-3">
      <?php
        $aiPaths = [
          'sisvent/admin/bots/index','sisvent/admin/bots/whatsapp','sisvent/admin/bots/ads',
          'sisvent/admin/bots/agotados','sisvent/admin/garantias/index',
          'sisvent/admin/botsqueue/index','sisvent/admin/aiassistant/index',
          'sisvent/admin/agents/dailySummary','sisvent/admin/comisiones/index',
          'sisvent/admin/comisiones/config',
        ];
        if (in_array($thisFile, $aiPaths)): $ai_sel = 'text-white';
      ?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?= isset($ai_sel) ? $ai_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" @click="toggleAiMenu">
        <span class="inline-flex items-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <span class="ml-4">Ledxury <span class="ml-2 px-1.5 py-0.5 text-xxs font-bold rounded bg-purple-500 text-white">AI</span></span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
      </button>
      <transition name="fade">
        <ul v-if="isAiMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)">
          <?php
            $__ud = $this->session->userdata('user_data');
            $__isLimitedBotOp = !empty($__ud['allowed_bot_ids']); // operador de un solo bot (ej. Axonia/Sebastian)
          ?>
          <?php if ($bots_access && $__isLimitedBotOp): ?>
          <!-- Operador limitado: solo su propio WhatsApp Web (allowed_bot_ids lo restringe a su bot) -->
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/bots/whatsapp">WhatsApp Web</a>
          </li>
          <?php elseif ($bots_access): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/bots">Bots WhatsApp</a>
          </li>
          <li class="px-2 py-1 pt-2 text-xxs uppercase text-gray-500 font-bold tracking-wide">WhatsApp Web</li>
          <li class="px-2 py-1 pl-6 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/bots/whatsapp?company=ledxury">WhatsApp Web Ledxury</a>
          </li>
          <li class="px-2 py-1 pl-6 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/bots/whatsapp?company=axonia">WhatsApp Web Axonia</a>
          </li>
          <?php if ($role == 1 || $role == 2 || $role == 10): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/bots/ads">Campañas Meta Ads</a>
          </li>
          <?php endif; ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/bots/agotados">Agotados</a>
          </li>
          <?php if ($role == 1 || $role == 10): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/garantias">Garantías</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/botsqueue">Cola Bots</a>
          </li>
          <?php endif; ?>
          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Comisiones</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/comisiones">Comisiones bots</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full text-xxs text-gray-500 pl-3" href="<?= base_url() ?>sisvent/admin/comisiones/config">⚙ Configurar</a>
          </li>
          <?php endif; ?>

          <?php if ($role == 1 || $role == 10): ?>
          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Asistente</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/aiassistant">Asistente IA</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/agents/dailySummary">Resumen Diario</a>
          </li>
          <?php endif; ?>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

  <!-- ================================================================ -->
  <!-- 7. CONFIGURACIÓN — Usuarios, Roles, Almacenes, Importar, Contable -->
  <!-- ================================================================ -->
    <?php if (in_array($role, [1, 2, 10])): ?>
    <li class="relative px-6 py-3">
      <?php
        $configPaths = [
          'sisvent/business/users/list','sisvent/business/users/add','sisvent/business/users/edit',
          'sisvent/business/stores/list','sisvent/business/stores/add','sisvent/business/stores/edit',
          'sisvent/business/roles/list','sisvent/business/roles/add','sisvent/business/roles/edit','sisvent/business/roles/permissions',
          'sisvent/admin/import/index','sisvent/admin/accountingsettings/index','sisvent/admin/setup/wizard',
          'sisvent/dashboard/userActivity',
        ];
        if (in_array($thisFile, $configPaths)): $config_sel = 'text-white';
      ?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?= isset($config_sel) ? $config_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" @click="toggleConfigMenu">
        <span class="inline-flex items-center">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <span class="ml-4">Configuración</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
      </button>
      <transition name="fade">
        <ul v-if="isConfigMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)">
          <?php if (!empty($this->session->userdata('user_data')['is_platform_admin'])): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full flex items-center gap-1" href="<?= base_url() ?>sisvent/admin/tenants">
              <span style="color:#FF5A36;">●</span> Tenants (Pulso)
            </a>
          </li>
          <?php endif; ?>
          <?php if (has_permission('usuarios')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/users">Usuarios</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/roles">Permisos y Roles</a>
          </li>
          <?php endif; ?>
          <?php if (has_permission('tiendas')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/stores">Almacenes</a>
          </li>
          <?php endif; ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full flex items-center gap-1" href="<?= base_url() ?>sisvent/admin/setup/wizard">⚡ Asistente nueva empresa</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/import">Importar masivo</a>
          </li>
          <?php if (has_permission('config_contable')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/accountingsettings">Config Contable</a>
          </li>
          <?php endif; ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/dashboard/userActivity">Actividad de usuarios</a>
          </li>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

    <?php
      // Ledxury v2 — switcher opcional al final. Si el archivo no existe, no
      // pasa nada. Para revertir, borrá estas 4 líneas.
      $sw = APPPATH . 'views/sisvent/v2/_v1_switcher.php';
      if (file_exists($sw)) include $sw;
    ?>

  </ul>

</div>
