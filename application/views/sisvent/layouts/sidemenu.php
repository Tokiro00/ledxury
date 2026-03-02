<div class="py-4 text-gray-500 dark:text-gray-400">
  <a class="ml-6 text-lg font-bold text-gray-800 dark:text-gray-200" href="#">
    M.A.M.
  </a>

  <!-- ================================================================ -->
  <!-- 1. DASHBOARD -->
  <!-- ================================================================ -->
  <ul class="mt-6">
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/dashboard'])): $dashboard_sel = 'text-gray-800'; ?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-blue-dark rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <a class="inline-flex items-center w-full text-sm <?php echo isset($dashboard_sel) ? $dashboard_sel : '' ?> font-semibold transition-colors duration-150 hover:text-gray-800" href="<?= base_url() ?>sisvent/dashboard">
        <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span class="ml-4">Dashboard</span>
      </a>
    </li>
  </ul>

  <ul>

  <!-- ================================================================ -->
  <!-- 2. COMERCIAL (todos) -->
  <!-- ================================================================ -->
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/commercial/budgets/list','sisvent/commercial/budgets/add','sisvent/commercial/budgets/edit','sisvent/commercial/invoices/list','sisvent/commercial/invoices/add','sisvent/commercial/invoices/edit','sisvent/commercial/invoices/refunds','sisvent/commercial/invoices/viewrefund'])): $commercial_sel = 'text-gray-800'; ?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-blue-dark rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($commercial_sel) ? $commercial_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-gray-800" @click="toggleComercialMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
          <span class="ml-4">Comercial</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isComercialMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md shadow-inner bg-gray-50" aria-label="submenu">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/budgets">Presupuestos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/budgets/archived">Archivados</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/invoices">Facturas</a>
          </li>
          <?php if(has_permission('devoluciones')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/invoices/refunds">Devoluciones</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/invoices/validate">Cobro Juridico</a>
          </li>
          <?php endif; ?>
          <?php if(in_array($role, [3])): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/invoices/legalcollection">Cobro Juridico</a>
          </li>
          <?php endif; ?>
        </ul>
      </transition>
    </li>

  <!-- ================================================================ -->
  <!-- 3. CAJA Y BANCOS -->
  <!-- ================================================================ -->
    <?php if(has_permission('caja_bancos')): ?>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/admin/financialdashboard/index','sisvent/admin/cashboxes/list','sisvent/admin/cashboxes/add','sisvent/admin/cashboxes/edit','sisvent/admin/cashboxes/view','sisvent/admin/cashmovements/list','sisvent/admin/cashmovements/add','sisvent/admin/cashmovements/transfer','sisvent/admin/cashmovements/view','sisvent/admin/bankaccounts/list','sisvent/admin/bankaccounts/add','sisvent/admin/bankaccounts/edit','sisvent/admin/bankaccounts/view','sisvent/admin/paymentmethods/list','sisvent/admin/paymentmethods/add','sisvent/admin/paymentmethods/edit','sisvent/admin/payments/list','sisvent/admin/payments/add','sisvent/admin/payments/edit'])): $caja_sel = 'text-gray-800';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-blue-dark rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($caja_sel) ? $caja_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-gray-800" @click="toggleCajaMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          <span class="ml-4">Caja y Bancos</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isCajaMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md shadow-inner bg-gray-50" aria-label="submenu">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 font-semibold">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/financialdashboard">Dashboard Financiero</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/cashboxes">Cajas</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/bankaccounts">Bancos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/cashmovements">Movimientos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/payments">Abonos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/paymentmethods">Formas de Pago</a>
          </li>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

  <!-- ================================================================ -->
  <!-- 4. CARTERA -->
  <!-- ================================================================ -->
    <?php if(has_permission('cartera')): ?>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/admin/accountsreceivable/list','sisvent/admin/accountsreceivable/by_client','sisvent/admin/accountsreceivable/client_detail','sisvent/admin/settlements/list','sisvent/admin/vouchers/add','sisvent/admin/vouchers/edit','sisvent/admin/vouchers/list','sisvent/admin/clientstatement/index','sisvent/admin/clientstatement/show'])): $cartera_sel = 'text-gray-800';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-blue-dark rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($cartera_sel) ? $cartera_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-gray-800" @click="toggleCarteraMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
          <span class="ml-4">Cartera</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isCarteraMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md shadow-inner bg-gray-50" aria-label="submenu">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/accountsreceivable">Cuentas por Cobrar</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/clientstatement">Estado de Cuenta</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/settlements">Liquidaciones</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/vouchers">Vales</a>
          </li>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

  <!-- ================================================================ -->
  <!-- 5. PROVEEDORES Y GASTOS -->
  <!-- ================================================================ -->
    <?php if(has_permission('gastos')): ?>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/admin/expenses/list','sisvent/admin/expenses/add','sisvent/admin/expenses/edit','sisvent/admin/expenses/view','sisvent/admin/expensecategories/list','sisvent/admin/expensecategories/add','sisvent/admin/expensecategories/edit','sisvent/admin/accountspayable/list','sisvent/admin/accountspayable/add','sisvent/admin/accountspayable/view','sisvent/admin/accountspayable/pay','sisvent/business/providers/list','sisvent/business/providers/add','sisvent/business/providers/edit','sisvent/admin/providerstatement/index','sisvent/admin/providerstatement/show'])): $gastos_sel = 'text-gray-800';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-blue-dark rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($gastos_sel) ? $gastos_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-gray-800" @click="toggleGastosMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
          <span class="ml-4">Proveedores</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isGastosMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md shadow-inner bg-gray-50" aria-label="submenu">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/providers">Proveedores</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/accountspayable">Facturas Proveedor</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/providerstatement">Estado de Cuenta</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/expenses">Gastos Operacionales</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/expensecategories">Categorias de Gasto</a>
          </li>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

  <!-- ================================================================ -->
  <!-- 6. ALMACEN -->
  <!-- ================================================================ -->
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/store/transfers/index','sisvent/store/inventory/index','sisvent/store/inventory/add','sisvent/store/inventory/edit'])): $stores_sel = 'text-gray-800';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-blue-dark rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($stores_sel) ? $stores_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-gray-800" @click="toggleStoresMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
          <span class="ml-4">Almacen</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isStoresMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md shadow-inner bg-gray-50" aria-label="submenu">
          <?php if(has_permission('inventario')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/inventory">Inventario</a>
          </li>
          <?php endif; ?>
          <?php if(has_permission('traspasos')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/transfers">Traspasos</a>
          </li>
          <?php endif; ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/count">Conteo diario</a>
          </li>
        </ul>
      </transition>
    </li>

  <!-- ================================================================ -->
  <!-- 7. EMPRESA -->
  <!-- ================================================================ -->
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/business/users/list','sisvent/business/users/add','sisvent/business/users/edit','sisvent/business/stores/list','sisvent/business/stores/add','sisvent/business/stores/edit','sisvent/business/vendors/list','sisvent/business/vendors/archived','sisvent/business/vendors/add','sisvent/business/vendors/edit','sisvent/business/clients/list','sisvent/business/clients/add','sisvent/business/clients/edit','sisvent/business/products/list','sisvent/business/products/add','sisvent/business/products/edit','sisvent/business/roles/index'])): $business_sel = 'text-gray-800';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-blue-dark rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($business_sel) ? $business_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-gray-800" @click="toggleBusinessMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>
          <span class="ml-4">Empresa</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isBusinessMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md shadow-inner bg-gray-50" aria-label="submenu">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/clients">Clientes</a>
          </li>
          <?php if(has_permission('vendedores')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/vendors">Vendedores</a>
          </li>
          <?php endif; ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/products">Productos</a>
          </li>
          <?php if(has_permission('tiendas')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/stores">Almacenes</a>
          </li>
          <?php endif; ?>
          <?php if(has_permission('usuarios')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/users">Usuarios</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/roles">Permisos</a>
          </li>
          <?php endif; ?>
        </ul>
      </transition>
    </li>

  <!-- ================================================================ -->
  <!-- 8. CONTABILIDAD -->
  <!-- ================================================================ -->
    <?php if(has_permission('contabilidad')): ?>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/accounting/accountclass/index','sisvent/accounting/accounts/index','sisvent/accounting/accountclass/add','sisvent/accounting/accountclass/edit','sisvent/accounting/entries/list.php','sisvent/accounting/entries/add.php','sisvent/accounting/entries/view.php','sisvent/accounting/mayor/list','sisvent/accounting/cierre/list.php','sisvent/accounting/costcenters/list','sisvent/accounting/costcenters/add','sisvent/accounting/costcenters/edit','sisvent/accounting/plandecuentas/index','sisvent/admin/accountingsettings/index','sisvent/accounting/apertura/index'])): $accounting_sel = 'text-gray-800';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-blue-dark rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($accounting_sel) ? $accounting_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-gray-800" @click="toggleAccountingMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
          <span class="ml-4">Contabilidad</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isAccountingMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md shadow-inner bg-gray-50" aria-label="submenu">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/plandecuentas">Plan de Cuentas</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/apertura">Apertura de Balance</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/costcenters">Centros de Costo</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/entries">Libro Diario</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/mayor">Libro Mayor</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/cierre">Cierre Contable</a>
          </li>
          <?php if(has_permission('config_contable')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/accountingsettings">Configuracion Contable</a>
          </li>
          <?php endif; ?>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

  <!-- ================================================================ -->
  <!-- 9. REPORTES -->
  <!-- ================================================================ -->
    <?php if(has_permission('reportes_ventas') || has_permission('reportes_contables')): ?>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/accounting/reports/index','sisvent/accounting/reports/balance','sisvent/accounting/reports/resultados','sisvent/accounting/reports/comprobacion','sisvent/admin/reports','sisvent/admin/reports/index','sisvent/admin/cashmovements/report'])): $reportes_sel = 'text-gray-800';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-blue-dark rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($reportes_sel) ? $reportes_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-gray-800" @click="toggleReportesMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
          <span class="ml-4">Reportes</span>
        </span>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
      <transition name="fade">
        <ul v-if="isReportesMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md shadow-inner bg-gray-50" aria-label="submenu">
          <?php if(has_permission('reportes_contables')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/reports/comprobacion">Balance de Comprobacion</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/reports/balance">Balance General</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/reports/resultados">Estado de Resultados</a>
          </li>
          <?php endif; ?>
          <?php if(has_permission('reportes_ventas')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports">Ventas</a>
          </li>
          <?php endif; ?>
          <?php if(has_permission('reportes_avanzados')): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/daily">Ventas x Dia</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/reportscallcenter">Callcenter</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/cashmovements/report">Mov. Cajas y Bancos</a>
          </li>
          <?php endif; ?>
        </ul>
      </transition>
    </li>
    <?php endif; ?>

  </ul>

  <!-- ================================================================ -->
  <!-- 10. QUICK LINKS -->
  <!-- ================================================================ -->
  <div class="px-6 my-3">
    <a href="<?php echo base_url();?>sisvent/store/catalogue" class="flex items-center justify-between w-full my-3 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none">
      Catalogos
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
    </a>
    <a href="<?php echo base_url();?>sisvent/commercial/budgets/add" class="flex items-center justify-between w-full my-3 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none">
      Nuevo Presupuesto <span class="ml-2" aria-hidden="true">+</span>
    </a>
    <?php if(has_permission('clientes')): ?>
    <a href="<?php echo base_url();?>sisvent/business/clients/add" class="flex items-center justify-between my-3 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none">
      <span>Agregar Cliente</span> <span>+</span>
    </a>
    <?php endif; ?>
    <?php if(has_permission('traspasos')): ?>
    <a href="<?php echo base_url();?>sisvent/store/dropshipping" class="flex items-center justify-between my-3 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none">
      <span>Dropshipping</span>
    </a>
    <?php endif; ?>
  </div>
</div>
