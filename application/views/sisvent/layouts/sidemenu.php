<div class="py-4 text-gray-500 dark:text-gray-400">
  <a class="ml-6 text-lg font-bold text-gray-800 dark:text-gray-200" href="#">
    M.A.M.
  </a>
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
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/commercial/budgets/list','sisvent/commercial/budgets/add','sisvent/commercial/budgets/edit','sisvent/commercial/invoices/list','sisvent/commercial/invoices/add','sisvent/commercial/invoices/edit'])): $commercial_sel = 'text-gray-800'; ?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-blue-dark rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button class="inline-flex items-center justify-between w-full <?php echo isset($commercial_sel) ? $commercial_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-gray-800" @click="toggleComercialMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
          <span class="ml-4">Comercial</span>
        </span>
        <svg class="w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
      </button>
      <transition name="fade">
        <ul v-if="isComercialMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md shadow-inner bg-gray-50 dark:text-gray-400 dark:bg-gray-900" aria-label="submenu">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/budgets">Presupuestos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/budgets/archived">Archivados</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/invoices">Facturas</a>
          </li>
          <?php if(in_array($role, [1,4])): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/noinvoices">Facturas 2020</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/invoices/validate">Cobro Jurídico</a>
          </li>
          <?php endif; ?>
          <?php if(in_array($role, [3])): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/commercial/invoices/legalcollection">Cobro Jurídico</a>
          </li>
          <?php endif; ?>
        </ul>
    </li>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/admin/payment_methods/list','sisvent/admin/payment_methods/add','sisvent/admin/payment_methods/edit','sisvent/admin/payments/list','sisvent/admin/payments/add','sisvent/admin/payments/edit','sisvent/admin/vouchers/add','sisvent/admin/vouchers/edit','sisvent/admin/vouchers/list','sisvent/admin/settlements/list','sisvent/admin/reports','sisvent/admin/reports/index'])): $admin_sel = 'text-gray-800';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-blue-dark rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button   class="inline-flex items-center justify-between w-full <?php echo isset($admin_sel) ? $admin_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200" @click="toggleAdminMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
      <?php if(in_array($role, [1])): ?>
          <span class="ml-4">Administración</span>
        <?php else: ?>
          <span class="ml-4">Reportes</span>
      <?php endif; ?>
        </span>
        <svg class="w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
      </button>
      <transition name="fade">
        <ul v-if="isAdminMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md shadow-inner bg-gray-50 dark:text-gray-400 dark:bg-gray-900" aria-label="submenu">
      <?php if(in_array($role, [1])): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/paymentmethods">Formas de Pago</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/payments">Abonos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/nopayments">Abonos F. 2020</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/vouchers">Vales</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/settlements">Liquidaciones</a>
          </li>
    <?php endif; ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports">Reportes</a>
          </li>
      <?php if(in_array($role, [1])): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/daily">Ventas x Día</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/reports/reportscallcenter">Rep. Callcenter</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/admin/advertising/expenses">Gastos Publicidad</a>
          </li>
    <?php endif; ?>
        </ul>
    </li>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/store/transfers/index','sisvent/store/inventory/index','sisvent/store/inventory/add','sisvent/store/inventory/edit'])): $stores_sel = 'text-gray-800';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-blue-dark rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button   class="inline-flex items-center justify-between w-full <?php echo isset($stores_sel) ? $stores_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200" @click="toggleStoresMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
          <span class="ml-4">Almacén</span>
        </span>
        <svg class="w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
      </button>
      <transition name="fade">
        <ul v-if="isStoresMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md shadow-inner bg-gray-50 dark:text-gray-400 dark:bg-gray-900" aria-label="submenu">
    <?php if(in_array($role, [1,4])): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="#">Productos X Cliente</a>
          </li>
          <?php if(in_array($role, [1])): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/transfers">Traspasos</a>
          </li>
          <?php endif; ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/inventory">Inventario</a>
          </li>
    <?php endif; ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/store/count">Conteo diario</a>
          </li>
        </ul>
    </li>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/business/users/list','sisvent/business/users/add','sisvent/business/users/edit','sisvent/business/stores/list','sisvent/business/stores/add','sisvent/business/stores/edit','sisvent/business/vendors/list','sisvent/business/vendors/add','sisvent/business/vendors/edit','sisvent/business/clients/list','sisvent/business/clients/add','sisvent/business/clients/edit','sisvent/business/providers/list','sisvent/business/providers/add','sisvent/business/providers/edit','sisvent/business/products/list','sisvent/business/products/add','sisvent/business/products/edit','sisvent/business/stores/list','sisvent/business/stores/add','sisvent/business/stores/edit'])): $business_sel = 'text-gray-800';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-blue-dark rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button   class="inline-flex items-center justify-between w-full <?php echo isset($business_sel) ? $business_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200" @click="toggleBusinessMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>
          <span class="ml-4">Empresa</span>
        </span>
        <svg class="w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
      </button>
      <transition name="fade">
        <ul v-if="isBusinessMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md shadow-inner bg-gray-50 dark:text-gray-400 dark:bg-gray-900" aria-label="submenu">
          <?php if(in_array($role, [1])): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/users">Usuarios</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/vendors">Vendedores</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/providers">Proveedores</a>
          </li>
           <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/clients/unattclients">Clientes sin atender</a>
          </li>
          <?php endif; ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/clients">Clientes</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/products">Productos</a>
          </li>
          <?php if(in_array($role, [1])): ?>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/business/stores">Almacenes</a>
          </li>
          <?php endif; ?>
        </ul>
    </li>
    <?php if(in_array($role, [1,4])): ?>
    <li class="relative px-6 py-3">
      <?php if(in_array($thisFile, ['sisvent/accounting/accountclass/index','sisvent/accounting/accounts/index','sisvent/accounting/accountclass/add','sisvent/accounting/accountclass/edit'])): $accounting_sel = 'text-gray-800';?>
      <span class="absolute inset-y-0 left-0 w-1 bg-mam-blue-dark rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
      <?php endif; ?>
      <button   class="inline-flex items-center justify-between w-full <?php echo isset($accounting_sel) ? $accounting_sel : '' ?> text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200" @click="toggleAccountingMenu" aria-haspopup="true">
        <span class="inline-flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
          <span class="ml-4">Contabilidad</span>
        </span>
        <svg class="w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
      </button>
      <transition name="fade">
        <ul v-if="isAccountingMenuOpen" class="p-2 mt-2 space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md shadow-inner bg-gray-50 dark:text-gray-400 dark:bg-gray-900" aria-label="submenu">
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/accountclass">Clases</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/accountgroup">Grupos</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/accounts">Cuentas</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/subaccounts">Subcuentas</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/auxsubaccounts">Auxiliares</a>
          </li>
          <li class="px-2 py-1 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200">
            <a class="w-full" href="<?= base_url() ?>sisvent/accounting/entries">Entradas</a>
          </li>
        </ul>
    </li>
    <?php endif; ?>
  </ul>
  </transition>
  <div class="px-6 my-3">
    <a href="<?php echo base_url();?>sisvent/store/catalogue" class="flex items-center justify-between w-full my-3 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
      Catálogos <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
      </svg>
    </a>
    <a href="<?php echo base_url();?>sisvent/commercial/budgets/add" class="flex items-center justify-between w-full my-3 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
      Nuevo Presupuesto <span class="ml-2" aria-hidden="true">+</span>
    </a>
    <?php if(in_array($role, [1,2])): ?>
    <a href="<?php echo base_url();?>sisvent/business/clients/add" class="flex items-center justify-between my-3 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
      <span>Agregar Cliente</span>+</path></svg>
    </a>
    <?php endif; ?>
    <?php if(in_array($role, [1])): ?>
    <a href="<?php echo base_url();?>sisvent/store/dropshipping" class="flex items-center justify-between my-3 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
      <span>Dropshipping</span></path></svg>
    </a>
    <?php endif; ?>
    <a href="<?php echo base_url();?>sisvent/store/dropshipping/promos" class="flex items-center justify-between my-3 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
      <span>Promos</span></path></svg>
    </a>
  </div>
</div>