<div class="py-4 text-gray-300">
  <a class="ml-6 text-lg font-bold text-white" href="#">
    M.A.M.
  </a>

  <!-- ================================================================ -->
  <!-- 1. DASHBOARD -->
  <!-- ================================================================ -->
  <ul class="mt-6">
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/dashboard'])): $dashboard_sel = 'text-white'; ?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <a class="inline-flex items-center w-full text-sm <?php echo isset($dashboard_sel) ? $dashboard_sel : '' ?> font-semibold transition-colors duration-150 hover:text-white" href="<?= base_url() ?>sisvent/dashboard">
        <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span class="ml-4">Dashboard</span>
      </a>
    </li>
  </ul>

  <ul>

  <!-- ================================================================ -->
  <!-- 2. VENTAS (Presupuestos, Facturas, Devoluciones, Clientes, Vendedores) -->
  <!-- ================================================================ -->
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/commercial/budgets/list','sisvent/commercial/budgets/add','sisvent/commercial/budgets/edit','sisvent/commercial/invoices/list','sisvent/commercial/invoices/add','sisvent/commercial/invoices/edit','sisvent/commercial/invoices/refunds','sisvent/commercial/invoices/viewrefund','sisvent/business/clients/list','sisvent/business/clients/add','sisvent/business/clients/edit','sisvent/business/vendors/list','sisvent/business/vendors/archived','sisvent/business/vendors/add','sisvent/business/vendors/edit'])): $ventas_sel = 'text-white'; ?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($ventas_sel) ? $ventas_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" @click="toggleVentasMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
          <span class="ml-4">Ventas</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isVentasMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)" aria-label="submenu">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/budgets">Presupuestos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/budgets/archived">Archivados</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/invoices">Facturas</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/daily">Ventas Diario</a>
          </li>
          <?php if(has_permission('notas_credito')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/creditnotes">Notas Credito</a>
          </li>
          <?php endif; ?>
          <?php if(has_permission('cobro_juridico')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/invoices/<?= $role == 3 ? 'legalcollection' : 'validate' ?>">Cobro Juridico</a>
          </li>
          <?php endif; ?>
          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Maestros</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/clients">Clientes</a>
          </li>
          <?php if(has_permission('vendedores')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/vendors">Vendedores</a>
          </li>
          <?php endif; ?>
        </ul>
      </transition>
    </li>

  <!-- ================================================================ -->
  <!-- 3. COMPRAS (Proveedores, Facturas Prov., Gastos) -->
  <!-- ================================================================ -->
    <?php if(has_permission('gastos')): ?>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/business/providers/list','sisvent/business/providers/add','sisvent/business/providers/edit','sisvent/admin/accountspayable/list','sisvent/admin/accountspayable/add','sisvent/admin/accountspayable/view','sisvent/admin/accountspayable/pay','sisvent/admin/providerstatement/index','sisvent/admin/providerstatement/show','sisvent/admin/expenses/list','sisvent/admin/expenses/add','sisvent/admin/expenses/edit','sisvent/admin/expenses/view','sisvent/admin/expensecategories/list','sisvent/admin/expensecategories/add','sisvent/admin/expensecategories/edit','sisvent/store/reorder/abc','sisvent/store/reorder/agent','sisvent/store/reorder/orders','sisvent/store/reorder/view','sisvent/store/reorder/receive'])): $compras_sel = 'text-white';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($compras_sel) ? $compras_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" @click="toggleComprasMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" /></svg>
          <span class="ml-4">Compras</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isComprasMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)" aria-label="submenu">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/accountspayable">Facturas Proveedor</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/providerstatement">Estado de Cuenta</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/expenses">Gastos Operacionales</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/expensecategories">Categorias de Gasto</a>
          </li>
          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Ordenes de Compra</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/reorder">Clasificacion ABC</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/reorder/agent">Generar Ordenes</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/reorder/orders">Ordenes a Proveedor</a>
          </li>
          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Maestros</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/providers">Proveedores</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/advertising/expenses">Gastos Publicidad</a>
          </li>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

  <!-- ================================================================ -->
  <!-- 4. INVENTARIO (Productos, Stock, Traspasos, Conteo, Catalogo) -->
  <!-- ================================================================ -->
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/business/products/list','sisvent/business/products/add','sisvent/business/products/edit','sisvent/store/inventory/index','sisvent/store/inventory/add','sisvent/store/inventory/edit','sisvent/store/transfers/index','sisvent/store/catalogue/index'])): $inventario_sel = 'text-white';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($inventario_sel) ? $inventario_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" @click="toggleInventarioMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
          <span class="ml-4">Inventario</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isInventarioMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)" aria-label="submenu">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/products">Productos</a>
          </li>
          <?php if(has_permission('inventario')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/inventory">Stock por Bodega</a>
          </li>
          <?php endif; ?>
          <?php if(has_permission('traspasos')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/transfers">Traspasos</a>
          </li>
          <?php endif; ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/count">Conteo Diario</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/catalogue">Catalogo</a>
          </li>
        </ul>
      </transition>
    </li>

  <!-- ================================================================ -->
  <!-- 4B. ENVÍOS -->
  <!-- ================================================================ -->
    <?php if(has_permission('envios') || has_permission('reporte_logistica')): ?>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/admin/envios/index','sisvent/admin/envios/view','sisvent/admin/envios/estado_cuenta','sisvent/admin/logistics/report'])): $envios_sel = 'text-white';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full text-sm <?php echo isset($envios_sel) ? $envios_sel : '' ?> font-semibold transition-colors duration-150 hover:text-white" @click="toggleEnviosMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
          <span class="ml-4">Envios</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <ul v-if="isEnviosMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)" aria-label="submenu">
          <?php if(has_permission('envios')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/envios">Dashboard Envios</a>
          </li>
          <?php endif; ?>
          <?php if(has_permission('reporte_logistica')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/logistics">Reporte Logistica</a>
          </li>
          <?php endif; ?>
      </ul>
    </li>
    <?php endif; ?>

  <!-- 5. TESORERIA (Cajas, Bancos, Movimientos, Abonos, Formas de Pago) -->
  <!-- ================================================================ -->
    <?php if(has_permission('caja_bancos')): ?>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/admin/financialdashboard/index','sisvent/admin/cashboxes/list','sisvent/admin/cashboxes/add','sisvent/admin/cashboxes/edit','sisvent/admin/cashboxes/view','sisvent/admin/cashmovements/list','sisvent/admin/cashmovements/add','sisvent/admin/cashmovements/transfer','sisvent/admin/cashmovements/view','sisvent/admin/bankaccounts/list','sisvent/admin/bankaccounts/add','sisvent/admin/bankaccounts/edit','sisvent/admin/bankaccounts/view','sisvent/admin/paymentmethods/list','sisvent/admin/paymentmethods/add','sisvent/admin/paymentmethods/edit','sisvent/admin/payments/list','sisvent/admin/payments/add','sisvent/admin/payments/edit','sisvent/admin/contrapagos/index'])): $tesoreria_sel = 'text-white';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($tesoreria_sel) ? $tesoreria_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" @click="toggleTesoreriaMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          <span class="ml-4">Tesoreria</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isTesoreriaMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)" aria-label="submenu">
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
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/paymentmethods">Formas de Pago</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/contrapagos">Pagos Interrapidisimo</a>
          </li>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

  <!-- ================================================================ -->
  <!-- 6. CARTERA (CxC, Estado de Cuenta, Liquidaciones, Vales) -->
  <!-- ================================================================ -->
    <?php if(has_permission('cartera')): ?>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/admin/accountsreceivable/list','sisvent/admin/accountsreceivable/by_client','sisvent/admin/accountsreceivable/client_detail','sisvent/admin/settlements/list','sisvent/admin/vouchers/add','sisvent/admin/vouchers/edit','sisvent/admin/vouchers/list','sisvent/admin/clientstatement/index','sisvent/admin/clientstatement/show'])): $cartera_sel = 'text-white';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($cartera_sel) ? $cartera_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" @click="toggleCarteraMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
          <span class="ml-4">Cartera</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isCarteraMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)" aria-label="submenu">
          <?php if(has_permission('cartera')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/accountsreceivable">Cuentas por Cobrar</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/accountsreceivable/byStore">Cartera por Tienda</a>
          </li>
          <?php endif; ?>
          <?php if(has_permission('estado_cuenta') || has_permission('reporte_cartera')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/clientStatement">Estado de Cuenta</a>
          </li>
          <?php endif; ?>
          <?php if(has_permission('liquidaciones')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/settlements">Liquidaciones</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/vouchers">Vales</a>
          </li>
          <?php endif; ?>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

  <!-- ================================================================ -->
  <!-- 7. CONTABILIDAD -->
  <!-- ================================================================ -->
    <?php if(has_permission('contabilidad')): ?>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/accounting/accountclass/index','sisvent/accounting/accounts/index','sisvent/accounting/accountclass/add','sisvent/accounting/accountclass/edit','sisvent/accounting/entries/list.php','sisvent/accounting/entries/add.php','sisvent/accounting/entries/view.php','sisvent/accounting/mayor/list','sisvent/accounting/cierre/list.php','sisvent/accounting/costcenters/list','sisvent/accounting/costcenters/add','sisvent/accounting/costcenters/edit','sisvent/accounting/plandecuentas/index','sisvent/admin/accountingsettings/index','sisvent/accounting/apertura/index'])): $accounting_sel = 'text-white';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($accounting_sel) ? $accounting_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" @click="toggleAccountingMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
          <span class="ml-4">Contabilidad</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isAccountingMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)" aria-label="submenu">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/plandecuentas">Plan de Cuentas</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/apertura">Apertura de Balance</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/costcenters">Centros de Costo</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/entries">Libro Diario</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/mayor">Libro Mayor</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/cierre">Cierre Contable</a>
          </li>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

  <!-- ================================================================ -->
  <!-- 8. REPORTES -->
  <!-- ================================================================ -->
    <?php if(has_permission('reportes_ventas') || has_permission('reportes_contables')): ?>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/accounting/reports/index','sisvent/accounting/reports/balance','sisvent/accounting/reports/resultados','sisvent/accounting/reports/comprobacion','sisvent/accounting/reports/inventario','sisvent/admin/reports','sisvent/admin/reports/index','sisvent/admin/reports/vendor_performance','sisvent/admin/reports/clients_abc','sisvent/admin/cashmovements/report','sisvent/admin/reports/product_profitability','sisvent/admin/reports/vendor_profitability','sisvent/admin/reports/cash_flow','sisvent/admin/reports/provider_statement','sisvent/admin/reports/inventory_valuation','sisvent/admin/reports/inventory_rotation','sisvent/admin/reports/sales_yoy','sisvent/admin/reports/top_products','sisvent/admin/reports/expenses_by_category','sisvent/admin/reports/vendor_commissions'])): $reportes_sel = 'text-white';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($reportes_sel) ? $reportes_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" @click="toggleReportesMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
          <span class="ml-4">Reportes</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isReportesMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)" aria-label="submenu">
          <?php if(has_permission('reportes_ventas')): ?>
          <li class="px-2 py-1 text-xs uppercase text-gray-500 font-bold">Ventas</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/daily">Ventas por Dia</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/vendorPerformance">Rendimiento Vendedores</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/topProducts">Top Productos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/salesYoY">Ventas Ano vs Ano</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/productProfitability">Rentabilidad Producto</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/vendorProfitability">Rentabilidad Vendedor</a>
          </li>
          <?php endif; ?>
          <?php if(has_permission('reporte_cartera')): ?>
          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Cartera</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/aging">Antiguedad de Saldos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/clientsABC">Analisis Clientes ABC</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/debtByCity">Cartera por Ciudad</a>
          </li>
          <?php endif; ?>
          <?php if(has_permission('reportes_contables')): ?>
          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Tesoreria</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/cashFlow">Flujo de Efectivo</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/cashmovements">Mov. Cajas y Bancos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/providerStatement">Cuentas por Pagar</a>
          </li>
          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Contable</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/expensesByCategory">Gastos por Categoria</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/vendorCommissions">Comisiones Vendedores</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/reports/comprobacion">Balance Comprobacion</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/reports/balance">Balance General</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/reports/resultados">Estado de Resultados</a>
          </li>
          <?php endif; ?>
          <?php if(has_permission('reportes_ventas')): ?>
          <li class="border-t border-gray-600 mt-2 pt-2 px-2 py-1 text-xs uppercase text-gray-500 font-bold">Inventario</li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/inventoryValuation">Inventario Valorizado</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/inventoryRotation">Rotacion Inventario</a>
          </li>
          <?php endif; ?>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

  <!-- ================================================================ -->
  <!-- 8b. DESEMPENO (Panel vendedores, Metas, Inactivos, Mi Desempeño, Tracking) -->
  <!-- ================================================================ -->
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/admin/salesboard/index','sisvent/admin/salesboard/metas','sisvent/admin/salesboard/inactivos','sisvent/admin/tracking/semanal','sisvent/admin/tracking/cierre','sisvent/admin/tracking/acumulado','sisvent/admin/tracking/mi_desempeno','sisvent/admin/departments/index'])): $tracking_sel = 'text-white'; ?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <?php if(in_array($role, [1, 2, 9])): ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($tracking_sel) ? $tracking_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" @click="toggleTrackingMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
          <span class="ml-4">Desempeno</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isTrackingMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)" aria-label="submenu">
          <?php if(has_permission('reporte_vendedores')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/salesboard">Panel de Vendedores</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/salesboard/metas">Configurar Metas</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/salesboard/inactivos">Clientes Inactivos</a>
          </li>
          <?php endif; ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white border-t border-gray-600 mt-2 pt-2">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/tracking/miDesempeno">Mi Desempeno</a>
          </li>
          <?php if(in_array($role, [1, 2])): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white border-t border-gray-600 mt-2 pt-2">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/tracking/semanal">Seguimiento Semanal</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/tracking/cierre">Cierre Mensual</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/tracking/acumulado">Acumulado Anual</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/departments">Departamentos y KPIs</a>
          </li>
          <?php endif; ?>
        </ul>
      </transition>
      <?php else: ?>
      <a class="inline-flex items-center w-full text-sm <?php echo isset($tracking_sel) ? $tracking_sel : '' ?> font-semibold transition-colors duration-150 hover:text-white" href="<?= base_url() ?>sisvent/admin/tracking/miDesempeno">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
        <span class="ml-4">Mi Desempeno</span>
      </a>
      <?php endif; ?>
    </li>

  <!-- ================================================================ -->
  <!-- 9. CONFIGURACION (Usuarios, Roles, Almacenes, Importar, Config Contable) -->
  <!-- ================================================================ -->
    <?php if(in_array($role, [1, 2])): ?>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/business/users/list','sisvent/business/users/add','sisvent/business/users/edit','sisvent/business/stores/list','sisvent/business/stores/add','sisvent/business/stores/edit','sisvent/business/roles/list','sisvent/business/roles/add','sisvent/business/roles/edit','sisvent/business/roles/permissions','sisvent/admin/import/index','sisvent/admin/accountingsettings/index','sisvent/admin/setup/wizard'])): $config_sel = 'text-white';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($config_sel) ? $config_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" @click="toggleConfigMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
          <span class="ml-4">Configuracion</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isConfigMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md" style="background:rgba(255,255,255,0.08)" aria-label="submenu">
          <?php if(has_permission('usuarios')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/users">Usuarios</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/roles">Permisos y Roles</a>
          </li>
          <?php endif; ?>
          <?php if(has_permission('tiendas')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/stores">Almacenes</a>
          </li>
          <?php endif; ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white font-semibold">
            <a class="w-full flex items-center gap-1" href="<?= base_url() ?>sisvent/admin/setup/wizard">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
              Asistente Nueva Empresa
            </a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white font-semibold">
            <a class="w-full flex items-center gap-1" href="<?= base_url() ?>sisvent/admin/import">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
              Importar Masivo
            </a>
          </li>
          <?php if(has_permission('config_contable')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/accountingsettings">Config. Contable</a>
          </li>
          <?php endif; ?>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

  <!-- ================================================================ -->
  <!-- 10. AUTOMATIZACION IA (Solo super admin) -->
  <!-- ================================================================ -->
    <?php if($role == 1): ?>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/admin/aiassistant/index','sisvent/admin/agents/collections','sisvent/admin/agents/summary','sisvent/admin/agents/whatsapp'])): $ai_sel = 'text-white'; ?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-green rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button id="btn-toggle-ai-menu" class="inline-flex items-center justify-between w-full <?php echo isset($ai_sel) ? $ai_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-white" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714a2.25 2.25 0 00.659 1.591L19 14.5M14.25 3.104c.251.023.501.05.75.082M19 14.5l-2.47 2.47a2.25 2.25 0 01-1.59.659H9.06a2.25 2.25 0 01-1.59-.659L5 14.5m14 0V17a2 2 0 01-2 2H7a2 2 0 01-2-2v-2.5" /></svg>
          <span class="ml-4">Automatizacion</span>
          <span class="ml-2 px-2 py-0.5 text-xs font-bold text-purple-100 bg-purple-600 rounded-full">AI</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <ul id="ai-submenu" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-400 rounded-md <?php echo isset($ai_sel) ? '' : 'hidden'; ?>" style="background:rgba(255,255,255,0.08)" aria-label="submenu">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/aiassistant">Asistente IA</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/agents/collections">Agente de Cobros</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/agents/dailySummary">Resumen Diario</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-white">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/agents/whatsapp">WhatsApp</a>
          </li>
        </ul>
    </li>
    <?php endif; ?>

  </ul>

  <!-- ================================================================ -->
  <!-- QUICK LINKS -->
  <!-- ================================================================ -->
  <div class="px-6 my-3">
    <a href="<?php echo base_url();?>sisvent/commercial/budgets/add" class="flex items-center justify-between w-full my-3 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-green border border-transparent rounded-lg hover:bg-mam-green-dark focus:outline-none">
      Nuevo Presupuesto <span class="ml-2" aria-hidden="true">+</span>
    </a>
    <?php if(has_permission('clientes')): ?>
    <a href="<?php echo base_url();?>sisvent/business/clients/add" class="flex items-center justify-between my-3 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg hover:bg-mam-blue focus:outline-none">
      <span>Agregar Cliente</span> <span>+</span>
    </a>
    <?php endif; ?>
    <?php if(has_permission('traspasos')): ?>
    <a href="<?php echo base_url();?>sisvent/store/dropshipping" class="flex items-center justify-between my-3 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg hover:bg-mam-blue focus:outline-none">
      <span>Dropshipping</span>
    </a>
    <?php endif; ?>
    <a href="<?php echo base_url();?>sisvent/store/dropshipping/promos" class="flex items-center justify-between my-3 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
      <span>Promos</span>
    </a>
    <a href="<?php echo base_url();?>sisvent/commercial/smartcatalog" class="flex items-center justify-between my-1 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 border border-transparent rounded-lg focus:outline-none" style="background:#FF6B00;">
      <span>Smart Catalog</span>
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
    </a>
    <a href="<?php echo base_url();?>sisvent/commercial/smartcatalog/ofertas" class="flex items-center justify-between my-1 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 border border-transparent rounded-lg focus:outline-none" style="background:#7C3AED;">
      <span>Gestionar Ofertas</span>
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
    </a>
  </div>
</div>
