      <!--Modal-->
    <div class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center">
      <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
      <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg z-30 overflow-y-scroll" style="margin-top: -35%; max-height: 90%;">
        
        <div class="modal-close absolute top-0 right-0 cursor-pointer flex flex-col items-center mt-4 mr-4 text-white text-sm z-50">
          <svg class="fill-current text-white" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
            <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
          </svg>
          <span class="text-sm">(Esc)</span>
        </div>

        <!-- Add margin if you want to see some of the overlay behind the modal-->
        <div class="modal-content py-4 text-left px-6">
          <!--Title-->
          <div class="flex justify-between items-center pb-3">
            <p class="modal-title text-2xl font-bold">Advertencia</p>
            <div class="modal-close cursor-pointer z-50">
              <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
              </svg>
            </div>
          </div>

          <!--Body-->
          <div class="modal-body"></div>

          <!--Footer-->
          <div class="flex justify-end pt-2">
            <!--button class="px-4 bg-transparent p-3 rounded-lg text-indigo-500 hover:bg-gray-100 hover:text-indigo-400 mr-2">Action</button-->
            <button class="modal-close modal-close-btn px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue border border-transparent rounded-lg active:bg-mam-blue hover:bg-mam-blue focus:outline-none focus:shadow-outline-mam-blue">Aceptar</button>
          </div>
          
        </div>
      </div>
    </div>

<!-- Modal backdrop. This what you want to place close to the closing body tag -->
    <transition name="fade">
    <div id="mymodal" class="fixed inset-0 z-50 flex items-end bg-black bg-opacity-50 sm:items-center sm:justify-center hidden">
      <!-- Modal -->
      <div @click.away="closeModal"
        @keydown.escape="closeModal"
        class="w-full px-6 py-4 overflow-hidden bg-white rounded-t-lg dark:bg-gray-800 sm:rounded-lg sm:m-4 sm:max-w-xl"
        role="dialog"
        id="modal"
      >
        <!-- Remove header if you don't want a close icon. Use modal body to place modal tile. -->
        <header class="flex justify-end">
          <button
            class="inline-flex items-center justify-center w-6 h-6 text-gray-400 transition-colors duration-150 rounded dark:hover:text-gray-200 hover: hover:text-gray-700"
            aria-label="close"
            onclick="closeModal()"
          >
            <svg
              class="w-4 h-4"
              fill="currentColor"
              viewBox="0 0 20 20"
              role="img"
              aria-hidden="true"
            >
              <path
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
                fill-rule="evenodd"
              ></path>
            </svg>
          </button>
        </header>
        <!-- Modal body -->
        <div class="mt-4 mb-6">
          <!-- Modal title -->
          <p class="mb-2 text-lg font-semibold text-gray-700 dark:text-gray-300">
            Advertencia
          </p>
          <!-- Modal description -->
          <p class="m-body text-sm text-gray-700 dark:text-gray-400">
            ¿Está seguro que desea eliminar este elemento?
          </p>
        </div>
        <footer
          class="flex flex-col items-center justify-end px-6 py-3 -mx-6 -mb-4 space-y-4 sm:space-y-0 sm:space-x-6 sm:flex-row bg-gray-50 dark:bg-gray-800"
        >
          <button id="cancel_modal"
            onclick="closeModal()"
            class="w-full px-5 py-3 text-sm font-medium leading-5 text-white text-gray-700 transition-colors duration-150 border border-gray-300 rounded-lg dark:text-gray-400 sm:px-4 sm:py-2 sm:w-auto active:bg-transparent hover:border-gray-500 focus:border-gray-500 active:text-gray-500 focus:outline-none focus:shadow-outline-gray"
          >
            Cancelar
          </button>
          <button id="accept_modal" onclick="acceptModal()"
            class="w-full px-5 py-3 text-sm font-medium leading-5 text-white transition-colors duration-150 border border-transparent rounded-lg sm:w-auto sm:px-4 sm:py-2 focus:outline-none"
            style="background:#2E7D91;"
          >
            Aceptar
          </button>
        </footer>
      </div>
    </div>
  </transition>
    <!-- End of modal backdrop -->
<script>
// Patch: AI menu toggle
$(document).on('click', '#btn-toggle-ai-menu', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $('#ai-submenu').toggleClass('hidden');
});

// Profile dropdown toggle
$(document).on('click', '#btn-toggle-profile-menu', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $('#profile-dropdown').toggleClass('hidden');
    $('#notif-dropdown').addClass('hidden');
});

// Notifications dropdown toggle
$(document).on('click', '#btn-toggle-notif', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $('#notif-dropdown').toggleClass('hidden');
    $('#profile-dropdown').addClass('hidden');
    // Update chat count
    $.get(base_url + 'sisvent/dashboard/chatUnread', function(r) {
        if (r.count > 0) {
            $('#notif-chat-count').text(r.count).removeClass('hidden');
            $('#noti-badge').show();
        } else {
            $('#notif-chat-count').addClass('hidden');
        }
    }, 'json');
});

// Close dropdowns on click outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('#btn-toggle-profile-menu, #profile-dropdown').length) {
        $('#profile-dropdown').addClass('hidden');
    }
    if (!$(e.target).closest('#btn-toggle-notif, #notif-dropdown').length) {
        $('#notif-dropdown').addClass('hidden');
    }
});

// toggleSubmenu is now defined in sidemenu.php (always loaded)

// Búsqueda Universal Navbar
(function() {
  var input = document.getElementById('navbar-universal-search');
  var results = document.getElementById('navbarSearchResults');
  if (!input || !results) return;

  var timer = null;
  var icons = {
    user: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>',
    box: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>',
    doc: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
    users: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m3 5.197V21"></path></svg>'
  };
  var colors = { Cliente: '#22c55e', Producto: '#3b82f6', Factura: '#f59e0b', Usuario: '#8b5cf6' };

  input.addEventListener('input', function() {
    clearTimeout(timer);
    var q = this.value.trim();
    if (q.length < 2) { results.classList.add('hidden'); return; }
    timer = setTimeout(function() {
      $.get(base_url + 'sisvent/dashboard/search', { q: q }, function(r) {
        if (!r.results || !r.results.length) {
          results.innerHTML = '<div class="p-4 text-sm text-gray-400 text-center">Sin resultados</div>';
          results.classList.remove('hidden');
          return;
        }
        var html = '';
        r.results.forEach(function(item) {
          var c = colors[item.type] || '#666';
          var ic = icons[item.icon] || icons.box;
          html += '<a href="' + item.url + '" class="flex items-center px-4 py-3 hover:bg-gray-50 border-b border-gray-100">'
            + '<div class="p-2 rounded-lg mr-3" style="background:' + c + '15;color:' + c + '">' + ic + '</div>'
            + '<div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-800 truncate">' + item.title + '</p><p class="text-xs text-gray-400 truncate">' + item.subtitle + '</p></div>'
            + '<span class="text-xs font-medium px-2 py-0.5 rounded-full ml-2" style="background:' + c + '15;color:' + c + '">' + item.type + '</span></a>';
        });
        results.innerHTML = html;
        results.classList.remove('hidden');
      }, 'json');
    }, 300);
  });

  $(document).on('click', function(e) {
    if (!$(e.target).closest('#navbar-universal-search, #navbarSearchResults').length) {
      results.classList.add('hidden');
    }
  });
})();
</script>

<?php $this->load->view('sisvent/layouts/voice_widget'); ?>
<?php $this->load->view('sisvent/layouts/chat_widget'); ?>
<?php $this->load->view('sisvent/layouts/screensaver'); ?>

<script>
// Drag floating buttons (voice + chat) — only drag from the toggle button itself
(function() {
  function makeDraggable(el, handleId) {
    if (!el) return;
    var handle = document.getElementById(handleId);
    if (!handle) return;
    var isDragging = false, wasDragged = false, startX, startY, origX, origY;
    var longPressTimer = null;
    var canDrag = false;

    // Long press (300ms) to start drag mode
    handle.addEventListener('pointerdown', function(e) {
      wasDragged = false;
      canDrag = false;
      startX = e.clientX; startY = e.clientY;
      origX = el.offsetLeft; origY = el.offsetTop;

      longPressTimer = setTimeout(function() {
        canDrag = true;
        isDragging = true;
        el.style.cursor = 'grabbing';
        el.setPointerCapture(e.pointerId);
      }, 300);
    });

    handle.addEventListener('pointermove', function(e) {
      if (!canDrag || !isDragging) return;
      var dx = e.clientX - startX, dy = e.clientY - startY;
      if (Math.abs(dx) > 5 || Math.abs(dy) > 5) wasDragged = true;
      if (!wasDragged) return;
      el.style.right = 'auto'; el.style.bottom = 'auto';
      el.style.left = Math.max(0, Math.min(window.innerWidth - 60, origX + dx)) + 'px';
      el.style.top = Math.max(0, Math.min(window.innerHeight - 60, origY + dy)) + 'px';
    });

    handle.addEventListener('pointerup', function(e) {
      clearTimeout(longPressTimer);
      isDragging = false;
      canDrag = false;
      el.style.cursor = 'grab';
      // Si fue drag, prevenir el click
      if (wasDragged) {
        e.stopPropagation();
        e.preventDefault();
        setTimeout(function() { wasDragged = false; }, 100);
      }
    });

    handle.addEventListener('pointercancel', function() {
      clearTimeout(longPressTimer);
      isDragging = false;
      canDrag = false;
      wasDragged = false;
    });

    handle.addEventListener('click', function(e) {
      if (wasDragged) { e.stopPropagation(); e.preventDefault(); wasDragged = false; }
    }, true);
  }

  makeDraggable(document.getElementById('voiceWidget'), 'voiceToggle');
  makeDraggable(document.getElementById('chatWidget'), 'chatToggle');
})();
</script>