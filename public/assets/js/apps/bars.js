export default {
  data() {
    return {
      isSideMenuOpen: false,
      isNotificationsMenuOpen: false,
      isProfileMenuOpen: false,
      isPagesMenuOpen: false,
      isComercialMenuOpen: false,
      isAdminMenuOpen: false,
      isStoresMenuOpen: false,
      isBusinessMenuOpen: false,
      isModalOpen: false,
      trapCleanup: null,    }
  },
  created() {
  },
  destroyed() {
  },
  mounted() {
      console.log('Mounted');
    },
    methods: {
      toggleSideMenu() {
        console.log('toggleSideMenu');
        this.isSideMenuOpen = !this.isSideMenuOpen
      },
      closeSideMenu() {
        this.isSideMenuOpen = false
      },
      toggleNotificationsMenu() {
        this.isNotificationsMenuOpen = !this.isNotificationsMenuOpen
      },
      closeNotificationsMenu() {
        this.isNotificationsMenuOpen = false
      },
      toggleProfileMenu() {
        this.isProfileMenuOpen = !this.isProfileMenuOpen
      },
      closeProfileMenu() {
        this.isProfileMenuOpen = false
      },
      togglePagesMenu() {
        console.log('togglePagesMenu');
        this.isPagesMenuOpen = !this.isPagesMenuOpen
      },
      toggleComercialMenu() {
        this.isComercialMenuOpen = !this.isComercialMenuOpen
      },
      toggleAdminMenu() {
        this.isAdminMenuOpen = !this.isAdminMenuOpen
      },
      toggleStoresMenu() {
        this.isStoresMenuOpen = !this.isStoresMenuOpen
      },
      toggleBusinessMenu() {
        this.isBusinessMenuOpen = !this.isBusinessMenuOpen
      },
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