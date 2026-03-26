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
            class="w-full px-5 py-3 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg sm:w-auto sm:px-4 sm:py-2 focus:outline-none"
          >
            Aceptar
          </button>
        </footer>
      </div>
    </div>
  </transition>
    <!-- End of modal backdrop -->
<script>
// Patch: AI menu toggle (until webpack is rebuilt)
$(document).on('click', '#btn-toggle-ai-menu', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $('#ai-submenu').toggleClass('hidden');
});
</script>