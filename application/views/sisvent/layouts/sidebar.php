<!-- Desktop sidebar -->
  <aside class="z-20 hidden w-64 overflow-y-auto bg-white md:block flex-shrink-0">
    <?php $this->load->view('sisvent/layouts/sidemenu',array('thisFile' => $thisFile,'role' => $role)); ?>
  </aside>
  <!-- Mobile sidebar -->
  <!-- Backdrop -->
  <transition name="fade">
    <div v-if="isSideMenuOpen" class="fixed inset-0 z-10 flex items-end bg-black bg-opacity-50 sm:items-center sm:justify-center"></div>
  </transition>
  <transition name="fade-trans">
  <aside class="fixed inset-y-0 z-20 flex-shrink-0 w-64 mt-16 overflow-y-auto bg-white md:hidden" v-if="isSideMenuOpen" @keydown.escape="closeSideMenu">
    <?php $this->load->view('sisvent/layouts/sidemenu',array('thisFile' => $thisFile,'role' => $role)); ?>
  </aside>
  </transition>