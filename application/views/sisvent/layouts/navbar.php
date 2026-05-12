<header class="py-4 bg-white shadow-md" style="z-index:100; position:relative;">
          <div class="container flex items-center justify-between h-full px-6 mx-auto text-mam-blue-petroleo dark:text-mam-blue-petroleo">
            <!-- Mobile hamburger -->
            <button class="p-1 mr-5 -ml-1 rounded-md md:hidden focus:outline-none focus:shadow-outline-mam-blue-petroleo" @click="toggleSideMenu" aria-label="Menu">
              <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
              </svg>
            </button>
            <!-- Búsqueda Universal -->
            <div class="flex justify-center flex-1 lg:mr-32">
              <div class="relative w-full max-w-xl mr-6">
                <div class="absolute inset-y-0 flex items-center pl-3">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input id="navbar-universal-search" class="w-full py-3 pl-10 pr-4 text-sm text-gray-700 placeholder-gray-400 bg-gray-100 border-0 rounded-lg focus:placeholder-gray-500 focus:bg-white focus:ring-2 focus:ring-red-200 focus:outline-none form-input" type="text" placeholder="Buscar clientes, productos, facturas, usuarios..." autocomplete="off"/>
                <div id="navbarSearchResults" class="hidden absolute top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden z-50 max-h-80 overflow-y-auto"></div>
              </div>
            </div>
            <ul class="flex items-center flex-shrink-0 space-x-6">
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
});
</script>