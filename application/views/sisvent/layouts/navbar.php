<header class="py-4 bg-white shadow-md" style="z-index:100; position:relative;">
          <div class="container flex items-center justify-between h-full px-6 mx-auto text-mam-blue-petroleo dark:text-mam-blue-petroleo">
            <!-- Mobile hamburger -->
            <button class="p-1 mr-5 -ml-1 rounded-md md:hidden focus:outline-none focus:shadow-outline-mam-blue-petroleo" @click="toggleSideMenu" aria-label="Menu">
              <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
              </svg>
            </button>
            <!-- Espaciador (la búsqueda universal se retiró 2026-07-15: no cumplía función) -->
            <div class="flex-1"></div>
            <ul class="flex items-center flex-shrink-0 space-x-6">
              <!-- Tenant context indicator + switcher (solo platform admin) -->
              <?php
                $tenantSlug = $this->session->userdata('tenant_slug');
                $tenantName = $this->session->userdata('tenant_name');
                $tenantBrand = $this->session->userdata('tenant_brand') ?: '#FF5A36';
                $isPlatformAdmin = !empty($this->session->userdata('user_data')['is_platform_admin']);
              ?>
              <?php if ($tenantSlug): ?>
              <li class="relative" style="z-index:9999;">
                <?php if ($isPlatformAdmin):
                    $this->db->where('active', 1)->order_by('name', 'ASC');
                    $allTenants = $this->db->get('tenants')->result(); ?>
                <button id="btn-toggle-tenant" onclick="event.stopPropagation(); document.getElementById('tenant-dropdown').classList.toggle('hidden');"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-xs">
                  <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: <?= htmlspecialchars($tenantBrand) ?>;"></span>
                  <span class="font-bold text-gray-700"><?= htmlspecialchars($tenantName) ?></span>
                  <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M5.5 7l4.5 4.5L14.5 7H5.5z"/></svg>
                </button>
                <ul id="tenant-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-white border border-gray-100 rounded-md shadow-lg" style="z-index:99999;">
                  <li class="px-3 py-1 text-xxs font-bold text-gray-400 uppercase tracking-wider">Cambiar tenant</li>
                  <?php foreach ($allTenants as $tn): ?>
                  <li>
                    <a href="<?= base_url('sisvent/admin/tenants/switch_to/' . $tn->id) ?>"
                       class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 <?= $tn->slug === $tenantSlug ? 'bg-gray-50 font-bold' : '' ?>">
                      <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: <?= htmlspecialchars($tn->brand_primary) ?>;"></span>
                      <span class="flex-1 text-gray-700"><?= htmlspecialchars($tn->name) ?></span>
                      <?php if ($tn->slug === $tenantSlug): ?><svg class="w-3.5 h-3.5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.704 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.411 0z"/></svg><?php endif; ?>
                    </a>
                  </li>
                  <?php endforeach; ?>
                  <li class="border-t mt-1">
                    <a href="<?= base_url('sisvent/admin/tenants') ?>"
                       class="block px-3 py-2 text-xs text-gray-500 hover:bg-gray-50">⚙ Gestionar tenants</a>
                  </li>
                </ul>
                <?php else: ?>
                <span class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-50 text-xs">
                  <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: <?= htmlspecialchars($tenantBrand) ?>;"></span>
                  <span class="font-bold text-gray-700"><?= htmlspecialchars($tenantName) ?></span>
                </span>
                <?php endif; ?>
              </li>
              <?php endif; ?>
              <!-- Notifications (chat) -->
              <li class="relative">
                <button id="btn-toggle-notif" onclick="event.stopPropagation(); document.getElementById('notif-dropdown').classList.toggle('hidden'); document.getElementById('profile-dropdown').classList.add('hidden');" class="relative align-middle rounded-md focus:outline-none" aria-label="Notifications">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path>
                  </svg>
                  <span id="noti-badge" style="display:none;" class="absolute top-0 right-0 inline-block w-3 h-3 transform translate-x-1 -translate-y-1 bg-red-600 border-2 border-white rounded-full"></span>
                </button>
                <ul id="notif-dropdown" class="hidden absolute right-0 w-64 p-2 mt-2 space-y-1 bg-white border border-gray-100 rounded-md shadow-md" style="z-index:99999;">
                  <li class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase">Notificaciones</li>
                  <li>
                    <div onclick="document.getElementById('chatToggle').click(); document.getElementById('notif-dropdown').classList.add('hidden');" class="flex items-center justify-between w-full px-3 py-2 text-sm text-gray-600 rounded-md hover:bg-gray-100 cursor-pointer">
                      <span class="flex items-center"><svg class="w-4 h-4 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2v10z"/></svg> Chat interno</span>
                      <span id="notif-chat-count" class="hidden px-2 py-0.5 text-xs font-bold text-red-600 bg-red-100 rounded-full">0</span>
                    </div>
                  </li>
                  <li>
                    <div onclick="window.location.href='<?= base_url('sisvent/message') ?>'" class="flex items-center w-full px-3 py-2 text-sm text-gray-600 rounded-md hover:bg-gray-100 cursor-pointer">
                      <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                      Mensajes del sistema
                    </div>
                  </li>
                </ul>
              </li>
              <!-- Profile menu -->
              <li class="relative" style="z-index:9999;">
                <button id="btn-toggle-profile-menu" onclick="event.stopPropagation(); document.getElementById('profile-dropdown').classList.toggle('hidden'); document.getElementById('notif-dropdown').classList.add('hidden');" class="flex flex-row gap-4 align-middle rounded-full focus:shadow-outline-mam-blue-petroleo focus:outline-none" aria-label="Account" aria-haspopup="true"><span class="text-right leading-tight"><?php $ud = $this->session->userdata('user_data'); echo isset($ud['name']) ? $ud['name'] : ''; ?><br><span class="text-xs text-gray-400"><?php if(isset($ud['role'])){$this->db->select('name')->from('roles')->where('idRoles',$ud['role']);$r=$this->db->get()->row();echo $r?$r->name:'';}?></span></span>
                  <img class="object-cover w-8 h-8 rounded-full" src="<?php echo get_images_path($this->session->userdata('image')) ?>" alt="" aria-hidden="true"/>
                </button>
                  <ul id="profile-dropdown" class="hidden absolute right-0 w-56 p-2 mt-2 space-y-2 bg-white border border-gray-100 rounded-md shadow-md" style="z-index:99999;" aria-label="submenu">
                    <li class="flex">
                      <div onclick="window.location.href='<?= base_url() ?>sisvent/dashboard/profile'" class="inline-flex items-center w-full px-2 py-1 text-sm font-semibold text-gray-600 rounded-md hover:bg-gray-100 hover:text-gray-800 cursor-pointer">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span>Perfil</span>
                      </div>
                    </li>
                    <li class="flex">
                      <div onclick="window.location.href='<?= base_url() ?>sisvent/login/logout'" class="inline-flex items-center w-full px-2 py-1 text-sm font-semibold text-gray-600 rounded-md hover:bg-gray-100 hover:text-gray-800 cursor-pointer">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor"><path d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                        <span>Cerrar Sesion</span>
                      </div>
                    </li>
                  </ul>
              </li>
            </ul>
          </div>
        </header>

<!-- Dropdowns globales del navbar/sidemenu. Antes vivían en dashboard.php
     pero el dashboard ahora redirige a salesboard, lo que dejaba sin
     handlers al resto de páginas. Aquí se cargan siempre que se
     renderice la navbar. -->
<script>
$(document).on('click', '#btn-toggle-ai-menu', function(e) {
    e.preventDefault(); e.stopPropagation();
    $('#ai-submenu').toggleClass('hidden');
});
$(document).on('click', '#btn-toggle-profile-menu', function(e) {
    e.preventDefault(); e.stopPropagation();
    $('#profile-dropdown').toggleClass('hidden');
    $('#notif-dropdown').addClass('hidden');
});
$(document).on('click', '#btn-toggle-notif', function(e) {
    e.preventDefault(); e.stopPropagation();
    $('#notif-dropdown').toggleClass('hidden');
    $('#profile-dropdown').addClass('hidden');
    if (typeof base_url !== 'undefined') {
        $.get(base_url + 'sisvent/dashboard/chatUnread', function(r) {
            if (r && r.count > 0) { $('#notif-chat-count').text(r.count).removeClass('hidden'); $('#noti-badge').show(); }
            else { $('#notif-chat-count').addClass('hidden'); }
        }, 'json');
    }
});
$(document).on('click', function(e) {
    if (!$(e.target).closest('#btn-toggle-profile-menu, #profile-dropdown').length) $('#profile-dropdown').addClass('hidden');
    if (!$(e.target).closest('#btn-toggle-notif, #notif-dropdown').length) $('#notif-dropdown').addClass('hidden');
    if (!$(e.target).closest('#btn-toggle-tenant, #tenant-dropdown').length) $('#tenant-dropdown').addClass('hidden');
});
</script>