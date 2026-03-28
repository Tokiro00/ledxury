export default {
  mamdata() {
    return {
      dark: false,
      toggleTheme() {
        this.dark = !this.dark
      },
      isSideMenuOpen: false,
      toggleSideMenu() {
        this.isSideMenuOpen = !this.isSideMenuOpen
      },
      closeSideMenu() {
        this.isSideMenuOpen = false
      },
      isNotificationsMenuOpen: false,
      toggleNotificationsMenu() {
        this.isNotificationsMenuOpen = !this.isNotificationsMenuOpen
      },
      closeNotificationsMenu() {
        this.isNotificationsMenuOpen = false
      },
      isProfileMenuOpen: false,
      toggleProfileMenu() {
        this.isProfileMenuOpen = !this.isProfileMenuOpen
      },
      closeProfileMenu() {
        this.isProfileMenuOpen = false
      },
      isPagesMenuOpen: false,
      togglePagesMenu() {
        this.isPagesMenuOpen = !this.isPagesMenuOpen
      },
      // Sidebar menus
      isVentasMenuOpen: false,
      toggleVentasMenu() {
        this.isVentasMenuOpen = !this.isVentasMenuOpen
      },
      isComprasMenuOpen: false,
      toggleComprasMenu() {
        this.isComprasMenuOpen = !this.isComprasMenuOpen
      },
      isInventarioMenuOpen: false,
      toggleInventarioMenu() {
        this.isInventarioMenuOpen = !this.isInventarioMenuOpen
      },
      isTesoreriaMenuOpen: false,
      toggleTesoreriaMenu() {
        this.isTesoreriaMenuOpen = !this.isTesoreriaMenuOpen
      },
      isCarteraMenuOpen: false,
      toggleCarteraMenu() {
        this.isCarteraMenuOpen = !this.isCarteraMenuOpen
      },
      isComercialMenuOpen: false,
      toggleComercialMenu() {
        this.isComercialMenuOpen = !this.isComercialMenuOpen
      },
      isAdminMenuOpen: false,
      toggleAdminMenu() {
        this.isAdminMenuOpen = !this.isAdminMenuOpen
      },
      isStoresMenuOpen: false,
      toggleStoresMenu() {
        this.isStoresMenuOpen = !this.isStoresMenuOpen
      },
      isBusinessMenuOpen: false,
      toggleBusinessMenu() {
        this.isBusinessMenuOpen = !this.isBusinessMenuOpen
      },
      isAccountingMenuOpen: false,
      toggleAccountingMenu() {
        this.isAccountingMenuOpen = !this.isAccountingMenuOpen
      },
      isReportesMenuOpen: false,
      toggleReportesMenu() {
        this.isReportesMenuOpen = !this.isReportesMenuOpen
      },
      isFinanzasMenuOpen: false,
      toggleFinanzasMenu() {
        this.isFinanzasMenuOpen = !this.isFinanzasMenuOpen
      },
      isConfigMenuOpen: false,
      toggleConfigMenu() {
        this.isConfigMenuOpen = !this.isConfigMenuOpen
      },
      isAiMenuOpen: false,
      toggleAiMenu() {
        this.isAiMenuOpen = !this.isAiMenuOpen
      },
      isEnviosMenuOpen: false,
      toggleEnviosMenu() {
        this.isEnviosMenuOpen = !this.isEnviosMenuOpen
      },
      // Modal
      isModalOpen: false,
      trapCleanup: null,
      openModal() {
        this.isModalOpen = true
        this.trapCleanup = focusTrap(document.querySelector('#modal'))
      },
      closeModal() {
        this.isModalOpen = false
        this.trapCleanup()
      },
    }
  }
}