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
      isAccountingMenuOpen: false,
      isCajaMenuOpen: false,
      isCarteraMenuOpen: false,
      isGastosMenuOpen: false,
      isReportesMenuOpen: false,
      isModalOpen: false,
      trapCleanup: null,
      HTMLcontent: null,
      total_msgs: 0,    }
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
        this.total_msgs = window.count_msgs;
        //window.$("#count-msgs").html(window.count_msgs);
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
      toggleAccountingMenu() {
        this.isAccountingMenuOpen = !this.isAccountingMenuOpen
      },
      toggleCajaMenu() {
        this.isCajaMenuOpen = !this.isCajaMenuOpen
      },
      toggleCarteraMenu() {
        this.isCarteraMenuOpen = !this.isCarteraMenuOpen
      },
      toggleGastosMenu() {
        this.isGastosMenuOpen = !this.isGastosMenuOpen
      },
      toggleReportesMenu() {
        this.isReportesMenuOpen = !this.isReportesMenuOpen
      },
      openModal() {
        this.isModalOpen = true
        this.trapCleanup = focusTrap(document.querySelector('#modal'))
      },
      openModales() {
        console.log("----**");
      },
      closeModal() {
        this.isModalOpen = false
        this.trapCleanup()
      },
      changePrices(){
         window.$("#tborders > tr").each(function () {
          //alert(window.$(this).find('td').eq(0).text() + " " + window.$(this).find('td').eq(1).text() );
          //let price = 0;
          switch(parseInt(window.$("#budget-rate").val()))
          {
              case 1:
                  console.log("1::"+window.$(this).closest("tr").find(".price").val());//budget-rates
                  //price = data.price;
                  window.$(this).closest("tr").find(".budget-rates").val(window.$(this).closest("tr").find(".price").val());
                  window.$(this).closest("tr").find(".budget-subtotal").val((Number(window.$(this).closest("tr").find(".budget-quantities").val())*Number(window.$(this).closest("tr").find(".price").val())));
              break;
              case 2:
                  console.log("2::"+window.$(this).closest("tr").find(".price_base").val());
                  //price = data.price_base;
                  window.$(this).closest("tr").find(".budget-rates").val(window.$(this).closest("tr").find(".price_base").val());
                  window.$(this).closest("tr").find(".budget-subtotal").val((Number(window.$(this).closest("tr").find(".budget-quantities").val())*Number(window.$(this).closest("tr").find(".price_base").val())));
              break;
              case 3:
                  console.log("3::"+window.$(this).closest("tr").find(".price_scale").val());
                  //price = data.price_scale;
                  window.$(this).closest("tr").find(".budget-rates").val(window.$(this).closest("tr").find(".price_scale").val());
                  window.$(this).closest("tr").find(".budget-subtotal").val((Number(window.$(this).closest("tr").find(".budget-quantities").val())*Number(window.$(this).closest("tr").find(".price_scale").val())));
              break;
              case 4:
                  console.log("4::"+window.$(this).closest("tr").find(".price_dist").val());
                  //price = data.price_dist;
                  window.$(this).closest("tr").find(".budget-rates").val(window.$(this).closest("tr").find(".price_dist").val());
                  window.$(this).closest("tr").find(".budget-subtotal").val((Number(window.$(this).closest("tr").find(".budget-quantities").val())*Number(window.$(this).closest("tr").find(".price_dist").val())));
              break;
              default:
                  console.log("default::"+window.$(this).closest("tr").find(".price").val());
                  //price = data.price;
                  window.$(this).closest("tr").find(".budget-rates").val(window.$(this).closest("tr").find(".price").val());
                  window.$(this).closest("tr").find(".budget-subtotal").val((Number(window.$(this).closest("tr").find(".budget-quantities").val())*Number(window.$(this).closest("tr").find(".price").val())));
              break;
          }
      });
      window.calcTotal();
    },
    multiplyPrices(){
        var multiplier = parseInt(window.$("#budget-rate-multiplier").val())/100;
        window.$("#tborders > tr").each(function () {
            //showModal($(this).find('td').eq(0).text() + " " + $(this).find('td').eq(1).text() );
            window.$(this).closest("tr").find(".price_base").val(window.$(this).closest("tr").find(".budget-rates").val()*multiplier);
            //window.$(this).closest("tr").find(".budget-subtotal").val((Number(window.$(this).closest("tr").find(".budget-quantities").val())*Number(window.$(this).closest("tr").find(".budget-rates").val())));
            //let price = 0;
            
        });
        window.calcTotal();
    }
  }
}