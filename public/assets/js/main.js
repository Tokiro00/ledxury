// Build CSS
import '../css/app.css'
window.$ = window.jQuery = require('jquery');

function initVueComponent(component, el) {
	//console.log(el);
  if(document.querySelectorAll(el).length > 0) {
    component.el = el;
    new Vue(component);
  }
}

//import alpine_init from './alpine/init-alpine'
import inventory from './apps/inventory'
import upload_file from './apps/upload_file'
import modal from './apps/modal'
import bars from './apps/bars'
import tables from './apps/tables'

//var vm;
var inter2;

window.onload = function() {

if(window.inBudgets)
{
  var budget = localStorage.getItem("budget");
  console.log(budget);
  console.log(budget == 'null');
  console.log(budget == null);
  if(budget != null && budget != 'null'){
    $("#reload-budget").show();
    $("#clear-budget").show();
      showModal("Existen datos sin guardar de una visita previa, si desea cargar los datos presione el botón \"Recargar Info\"");
      console.log('budget exists');
      /*console.log(budget);
      var budgetjson = JSON.parse(budget);
      console.log(budget.budget_vendor);
      console.log(budgetjson.budget_vendor);*/
  }else{
      console.log('budget is not found');
  }

   changeVendor($('#budget-vendor').val());
}

if(!window.inMessages)
{
  console.log("no message page!!");
  getUserMessages(); //Calling the root function without interval
  inter2 = setInterval(getUserMessages, 5000); //Calling the root function with interval
}
  //console.log(getAllUrlParams().p);
  //console.log(location.protocol + '//' + location.host + location.pathname);
  
  initVueComponent(bars, '#bars');
	/*if(document.querySelectorAll('#bars').length > 0) {
	    bars.el = '#bars';
	    vm = new Vue(bars);
		window.vm = vm;
	}*/
    initVueComponent(tables, '#myTable');

    //$(document).on("change","#user-role", function(){
      $("#user-role").change(function() {

        var role = $('#user-role').children("option:selected").val();
        console.log("---------- - oe "+role);
        if(role == 1)
        {
          $( "#admin-stores" ).show();
        }else{
          $( "#admin-stores" ).hide();
        }        
    });
 
    $(document).on("click","#export-btn", function(){
        var mdata = $('#exportfrom').val();
        var muntildata = $('#exportuntil').val();
        var store = $('#exportstore').val();
        //console.log(mdata+" "+muntildata+" "+store);
        if(mdata && mdata != '')
        {
          $.ajax({
                url: window.base_url+"sisvent/commercial/invoices/createExcelFac",
                type:"POST",
                dataType:"json",
                data:{from: mdata, until: muntildata, store: store },
                success:function(data){

                  $('#export-btn-container').empty();

                    var aTag = document.createElement('a');
                  aTag.setAttribute('href',base_url+data.fac);
                  aTag.innerText = "FAC";
                  $('#export-btn-container').append(aTag);

                  aTag.classList.add("flex","items-center","justify-between","px-2","py-2","text-sm","font-medium","leading-5","text-mam-blue-dark","rounded-lg","focus:outline-none","focus:shadow-outline-gray");

                 var aTag2 = document.createElement('a');
                  aTag2.setAttribute('href',base_url+data.facdet);
                  aTag2.innerText = "LFA";
                  $('#export-btn-container').append(aTag2);
                  
                  aTag2.classList.add("flex","items-center","justify-between","px-2","py-2","text-sm","font-medium","leading-5","text-mam-blue-dark","rounded-lg","focus:outline-none","focus:shadow-outline-gray");
                }
            });
          //window.location.href = window.base_url+"/sisvent/business/clients/search/"+mdata+params;
        }else{
            showModal("El campo Desde no puede estar vacío");
        }
    });

    $(document).on("click","#export-prod-btn", function(){
        var mdata = $('#datepicker').val();
        var muntildata = $('#datepicker2').val();
        //console.log(mdata+" "+muntildata+" "+store);
        if(mdata && mdata != '')
        {
          $.ajax({
                url: window.base_url+"sisvent/business/products/createExcelProd",
                type:"POST",
                dataType:"json",
                data:{from: mdata, until: muntildata },
                success:function(data){

                  $('#export-btn-container').empty();

                  //console.log(data);
                  var aTag = document.createElement('a');
                  aTag.setAttribute('href',base_url+data.prod);
                  aTag.innerText = "ART";
                  $('#export-btn-container').append(aTag);

                  aTag.classList.add("flex","items-center","justify-between","px-2","py-2","text-sm","font-medium","leading-5","text-mam-blue-dark","rounded-lg","focus:outline-none","focus:shadow-outline-gray");

                  var aTag2 = document.createElement('a');
                  aTag2.setAttribute('href',base_url+data.prodPrec);
                  aTag2.innerText = "LTA";
                  $('#export-btn-container').append(aTag2);
                  
                  aTag2.classList.add("flex","items-center","justify-between","px-2","py-2","text-sm","font-medium","leading-5","text-mam-blue-dark","rounded-lg","focus:outline-none","focus:shadow-outline-gray");
                }
            });
          //window.location.href = window.base_url+"/sisvent/business/clients/search/"+mdata+params;
        }else{
            showModal("El campo Desde no puede estar vacío");
        }
    });
  
  $(document).on("click","#btn-search-client", function(){
        var mdata = $('#clients-search').val();
        var params = $('#clients-search').data("params");
        if(mdata && mdata != '')
        {
          window.location.href = window.base_url+"/sisvent/business/clients/search/"+mdata+params;
        }else{
            showModal("El campo no puede estar vacío");
        }
    });
  

     $(document).on("keydown", '#clients-search', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            var mdata = $('#clients-search').val();
            var params = $('#clients-search').data("params");
            if(mdata && mdata != '')
            {
              window.location.href = window.base_url+"/sisvent/business/clients/search/"+mdata+params;
            }else{
                showModal("El campo no puede estar vacío");
            }
        }
    });

    $(document).on("click","#btn-search-product", function(){
        var mdata = $('#products-search').val();
        var params = $('#products-search').data("params");
        if(mdata && mdata != '')
        {
          window.location.href = window.base_url+"/sisvent/business/products/search/"+mdata+params;
        }else{
            showModal("El campo no puede estar vacío");
        }
    });

     $(document).on("keydown", '#products-search', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            var mdata = $('#products-search').val();
            var params = $('#products-search').data("params");
            if(mdata && mdata != '')
            {
              window.location.href = window.base_url+"/sisvent/business/products/search/"+mdata+params;
            }else{
                showModal("El campo no puede estar vacío");
            }
        }
    });

    $(document).on("click","#btn-search-catalogue-product", function(){
        var mdata = $('#products-search-catalogue').val();
        var params = $('#products-search-catalogue').data("params");
        var store = $('#store_id').val();
        if(mdata && mdata != '')
        {
          window.location.href = window.base_url+"/sisvent/store/catalogue/search/"+store+"/"+mdata+params;
        }else{
            showModal("El campo no puede estar vacío");
        }
    });
  
     $(document).on("keydown", '#products-search-catalogue', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            var mdata = $('#products-search-catalogue').val();
            var params = $('#products-search-catalogue').data("params");
            var store = $('#store_id').val();
            if(mdata && mdata != '')
            {
              window.location.href = window.base_url+"/sisvent/store/catalogue/search/"+store+"/"+mdata+params;
            }else{
                showModal("El campo no puede estar vacío");
            }
        }
    });

     $(document).on("click","#btn-search-payment", function(){
        var mdata = $('#payment-search').val();
        var params = $('#payment-search').data("params");
        if(mdata && mdata != '')
        {
          window.location.href = window.base_url+"/sisvent/admin/payments/search/"+mdata+params;
        }else{
            showModal("El campo no puede estar vacío");
        }
    });

     $(document).on("keydown", '#payment-search', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            var mdata = $('#payment-search').val();
            var params = $('#payment-search').data("params");
            if(mdata && mdata != '')
            {
              window.location.href = window.base_url+"/sisvent/admin/payments/search/"+mdata+params;
            }else{
                showModal("El campo no puede estar vacío");
            }
        }
    });

     $(document).on("click","#btn-search-nopayment", function(){
        var mdata = $('#nopayment-search').val();
        var params = $('#nopayment-search').data("params");
        if(mdata && mdata != '')
        {
          window.location.href = window.base_url+"/sisvent/admin/nopayments/search/"+mdata+params;
        }else{
            showModal("El campo no puede estar vacío");
        }
    });

     $(document).on("keydown", '#nopayment-search', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            var mdata = $('#nopayment-search').val();
            var params = $('#nopayment-search').data("params");
            if(mdata && mdata != '')
            {
              window.location.href = window.base_url+"/sisvent/admin/nopayments/search/"+mdata+params;
            }else{
                showModal("El campo no puede estar vacío");
            }
        }
    });

     $(document).on("click","#btn-search-vouchers", function(){
        var mdata = $('#vouchers-search').val();
        var params = $('#vouchers-search').data("params");
        if(mdata && mdata != '')
        {
          window.location.href = window.base_url+"/sisvent/admin/vouchers/search/"+mdata+params;
        }else{
            showModal("El campo no puede estar vacío");
        }
    });

     $(document).on("keydown", '#vouchers-search', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            var mdata = $('#vouchers-search').val();
            var params = $('#vouchers-search').data("params");
            if(mdata && mdata != '')
            {
              window.location.href = window.base_url+"/sisvent/admin/vouchers/search/"+mdata+params;
            }else{
                showModal("El campo no puede estar vacío");
            }
        }
    });

  $( "#navbar-search-input" ).autocomplete({
      source:function(request, response){
            $.ajax({
                url: window.base_url+"/sisvent/store/inventory/searchProducts",
                type:"POST",
                dataType:"json",
                data:{valor: request.term},
                success:function(data){
                    response(data);
                }
            });
        },
        minLength:1,
        select:function(event, ui){
            //data=ui.item.ref;
            //$('#btn-agregar').val(ui.item.idProduct);
            //console.log(ui.item);
             event.preventDefault();
            $( "#navbar-search-input" ).val(null);
            showModal(ui.item.view, "", "Cerrar", true);
        }
    });

	//console.log(window.base_url+"/sisvent/store/inventory/getProducts");
	$( "#producto" ).autocomplete({
      source:function(request, response){
            $.ajax({
                url: window.base_url+"/sisvent/store/inventory/getProducts",
                type:"POST",
                dataType:"json",
                data:{valor: request.term},
                success:function(data){
                    response(data);
                }
            });
        },
        minLength:1,
        select:function(event, ui){
            //data=ui.item.ref;
            $('#btn-agregar').val(ui.item.idProduct);
        }
    });

    $('#btn-agregar').on('click',function(){
      var mdata = $(this).val();
      addInventoryProduct(mdata);
    });

    $('#btn-all-inventory').on('click',function(e){
      console.log("Add All "+e.target.id);
      addAllInventoryProduct();
    });

    function addAllInventoryProduct()
    {
      $.ajax({
              url: window.base_url+"sisvent/store/inventory/getAllProducts",
              type:"POST",
              dataType:"json",
              success:function(data){

                  console.log(data);
                  var html = "";
                  var index = $("#tborders").find('tr').length+1;
                  for (var i =  0; i < data.length; i++) {
                      if($('input[value="'+data[i].idProduct+'"]').length == 0)
                      {
                          let quant = 0;
                          html += "<tr class='text-gray-700'>";
                          html += "<td class='px-4 py-3 print:py-0 text-sm'>"+(index)+"</td>";
                          html += "<td class='px-4 py-3 print:py-0 text-xs whitespace-normal'><input type='hidden' name='refs[]' value='"+data[i].idProduct+"'>"+data[i].idProduct+"</td>";
                          html += "<td class='px-4 py-3 print:py-0 text-xs whitespace-normal'>"+data[i].description+"</td>";
                          html += "<td class='px-4 py-3 print:py-0 text-xs'><input class='form-input quantities' type='number' name='quantities[]' min='0' value='"+quant+"'></td>";
                          html += "<td class='px-4 py-3 print:py-0 '><button type='button' class='button-main btn-remove-inv-product print:hidden'><p class='tooltip'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg><span class='tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded text-mam-blue-dark'>Eliminar</span></p></button></td>";
                          html += "</tr>";
                          index++;
                      }else
                      {
                          /*showModal("Este producto ya ha sido agregado");
                          $('#btn-agregar').val(null);
                          $( "#producto" ).val(null);
                          $("#inv-quantities-ele").val(null);
                          $( "#producto" ).focus();*/
                      }
                  }

                  $("#tborders").prepend(html);
                  $('#btn-agregar').val(null);
                  $( "#producto" ).val(null);
                  $("#inv-quantities-ele").val(null);
                  $( "#producto" ).focus();  
              }
          });
        
    }

    $(document).on("keydown", "#new-inventory-form", function(event) { 
        return event.key != "Enter";
    });
    $(document).on("keydown", '#producto', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            $("#inv-quantities-ele").val(null);
            $("#inv-quantities-ele").focus();
            //var mdata = $('#btn-agregar').val();
            //addInventoryProduct(mdata);
        }
    });

    $(document).on("keydown", '#inv-quantities-ele', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            var mdata = $('#btn-agregar').val();
            addInventoryProduct(mdata);
        }
    });

    function addInventoryProduct(mdata)
    {
        if(mdata != '')
        {
          $.ajax({
                  url: window.base_url+"sisvent/store/inventory/getProduct",
                  type:"POST",
                  dataType:"json",
                  data:{ref: mdata},
                  success:function(data){
                      if($('input[value="'+data.idProduct+'"]').length == 0)
                      {
                          let quant = 1;
                          if($('#inv-quantities-ele').val() != null && $('#inv-quantities-ele').val() != ''){
                              quant = $('#inv-quantities-ele').val();
                          }
                          var html = "<tr class='text-gray-700'>";
                          html += "<td class='px-4 py-3'><input type='hidden' name='refs[]' value='"+data.idProduct+"'>"+data.idProduct+"</td>";
                          html += "<td class='px-4 py-3 text-xs whitespace-normal'>"+data.description+"</td>";
                          html += "<td class='px-4 py-3'><input class='form-input quantities' type='number' name='quantities[]' min='0' value='"+quant+"'></td>";
                          html += "<td class='px-4 py-3'><button type='button' class='button-main btn-remove-inv-product'><p class='tooltip'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg><span class='tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded text-mam-blue-dark'>Eliminar</span></p></button></td>";
                          html += "</tr>";
                          $("#tborders").prepend(html);
                          $('#btn-agregar').val(null);
                          $( "#producto" ).val(null);
                          $("#inv-quantities-ele").val(null);
                          $( "#producto" ).focus();

                      }else
                      {
                          showModal("Este producto ya ha sido agregado");
                          $('#btn-agregar').val(null);
                          $( "#producto" ).val(null);
                          $("#inv-quantities-ele").val(null);
                          $( "#producto" ).focus();
                      }
                  }
              });
        }else{
          showModal("Por favor seleccione un producto");
        }
    }

    $(document).on("click",".btn-remove-inv-product", function(){
        $(this).closest("tr").remove();
    });

    $( "#inv-store" ).change(function() {
        var store = $('#inv-store').children("option:selected").val();
        $( "#edit-inventory" ).prop('disabled', store==-1);
        changeInventoryStore(window.base_url);
    });

    $( "#vendor-voucher" ).change(function() {
        showVouchers();
    });
     $(document).on("click","#update-user-voucher", function(){
        showVouchers();
    });

    $( "#filter-store" ).change(function() {
        filterOrders();
    });

    $( "#filter-vendor" ).change(function() {
        filterOrders();
    });

    $( "#filter-state" ).change(function() {
        filterOrders();
    });

    $( "#filter-client" ).change(function() {
        filterOrders();
    });

     $( "#filter-iva" ).change(function() {
        filterOrders();
    });

    function filterOrders()
    {   
        let nurl = "";
        if(getAllUrlParams().p !== undefined)
        {
          nurl = "?p="+getAllUrlParams().p;
        }
        var store = $('#filter-store').children("option:selected").val();
        if(store != "Todos")
        {
          if (nurl === "") 
            nurl = "?"
          else
            nurl += "&"
          nurl += "str="+store;
        }
        var vendor = $('#filter-vendor').children("option:selected").val();
        if(vendor != "Todos" && vendor != undefined)
        {
          if (nurl === "") 
            nurl = "?"
          else
            nurl += "&"
          nurl += "v="+vendor;
        }
        var state = $('#filter-state').children("option:selected").val();
        if(state != "Todos" && state != undefined)
        {
          if (nurl === "") 
            nurl = "?"
          else
            nurl += "&"
          nurl += "ste="+state;
        }
        var client = $('#filter-client').children("option:selected").val();
        if(client != "Todos" && client != undefined)
        {
          if (nurl === "") 
            nurl = "?"
          else
            nurl += "&"
          nurl += "c="+client;
        }
        var iva = $('#filter-iva').children("option:selected").val();
        if(iva != "Todos" && iva != undefined)
        {
          if (nurl === "") 
            nurl = "?"
          else
            nurl += "&"
          nurl += "i="+iva;
        }

        //console.log(nurl);
        window.location.href = location.protocol + '//' + location.host + location.pathname+nurl;
        /*$("#tborders").find('tr').each(function () {
            /// *if(store == "Todos" && vendor == "Todos" && state == "Todos" && client == "Todos")
            //{
            //    $(this).show();    
            //}else * /
            if((store == "Todos" || (store != "Todos" && $(this).find('td').eq(3).html().indexOf(store) > -1)) && 
             (vendor == "Todos" || (vendor != "Todos" && $(this).find('td').eq(2).html().indexOf(vendor) > -1)) && 
             (state == "Todos" || (state != "Todos" && $(this).find('td').eq(5).html().indexOf(state) > -1)) && 
             (client == "Todos" || (client != "Todos" && $(this).find('td').eq(1).html().indexOf(client) > -1))
             )
            {
               $(this).show();   
            }else
            {
               $(this).hide();
            }
        });*/
    }

     $("#new-inventory-form").on('submit', function(e){
         //e.preventDefault();
         console.log($("#tborders").find('tr').length);
         if($("#tborders").find('tr').length == 0){
             showModal("Debe ingresar por lo menos un producto");
            //document.querySelector('.modal-body').innerHTML = "Debe ingresar por lo menos un producto";
            //toggleModal();
            return false;
         }
         return true;
    });

     $(document).on("click",".btn-view-inventory", function(){
        var valor_id = $(this).val();
        console.log(valor_id);
        $.ajax({
                url: base_url+"sisvent/store/inventory/view",
                type:"POST",
                dataType:"html",
                data:{id: valor_id},
                success:function(data){
                    //console.log(data);
                    showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });


    /******************* Transfers *******************/
    $( "#transfer-product" ).autocomplete({
      source:function(request, response){
            var origin_store = $('#origin-store').val();
            //console.log(request.term+" "+origin_store);
            $.ajax({
                url: window.base_url+"/sisvent/store/transfers/getProducts",
                type:"POST",
                dataType:"json",
                data:{valor: request.term, orstr: origin_store},
                success:function(data){
                    //console.log(data);
                    response(data);
                }
            });
        },
        minLength:1,
        select:function(event, ui){
            //data=ui.item.ref;
            $('#btn-agregar-trfr').val(ui.item.idProduct);
        }
    });

    $('#origin-store').change(function() {
        $("#tborders").html('');
    });

    $('#btn-agregar-trfr').on('click',function(){
      var mdata = $(this).val();
      addTransferProduct(mdata);
    });
    $(document).on("keydown", "#new-transfers-form", function(event) { 
        return event.key != "Enter";
    });
    $(document).on("keydown", '#transfer-product', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
             var mdata = $('#btn-agregar-trfr').val();
              addTransferProduct(mdata);
        }
    });

    function addTransferProduct(mdata)
    {
        if(mdata != '')
              {
                var origin_store = $('#origin-store').val();
                $.ajax({
                        url: window.base_url+"sisvent/store/transfers/getProduct",
                        type:"POST",
                        dataType:"json",
                        data:{ref: mdata, orstr: origin_store},
                        success:function(data){
                            if($('input[value="'+data.idProduct+'"]').length == 0)
                            {
                                var html = "<tr class='text-gray-700'>";
                                html += "<td class='px-4 py-3'><input type='hidden' name='refs[]' value='"+data.idProduct+"'>"+data.idProduct+"</td>";
                                html += "<td class='px-4 py-3 text-xs whitespace-normal'>"+data.description+"</td>";
                                html += "<td class='px-4 py-3'><input class='stock' type='text' name='stock[]' value='"+data.stock+"' readonly></td>";
                                html += "<td class='px-4 py-3'><input class='form-input trfr-quantities' type='number' min='1' name='trfr-quantities[]' value='1'></td>";
                                html += "<td class='px-4 py-3'><button type='button' class='button-main btn-remove-inv-product'><p class='tooltip'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg><span class='tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded text-mam-blue-dark'>Eliminar</span></p></button></td>";
                                html += "</tr>";
                                $("#tborders").prepend(html);
                                $('#btn-agregar-trfr').val(null);
                                $( "#transfer-product" ).val(null);
                                
                            }else
                            {
                                showModal("Este producto ya ha sido agregado");
                                $('#btn-agregar-trfr').val(null);
                                $( "#transfer-product" ).val(null);
                            }
                        }
                    });
              }else{
                showModal("Por favor seleccione un producto");
              }
    }

    $("#new-transfers-form").on('submit', function(e){
         //e.preventDefault();
         var origin_store = $('#origin-store').val();
         var destination_store = $('#destination-store').val();
         //console.log(origin_store+" "+destination_store);
         if(origin_store == destination_store)
         {
             showModal("El almacen de origen y el de destino deben ser diferentes");
             //document.querySelector('.modal-body').innerHTML = "El almacen de origen y el de destino deben ser diferentes";
             //toggleModal();
             return false;
         }
         //console.log($("#tborders").find('tr').length);
         if($("#tborders").find('tr').length == 0){
             showModal("Debe ingresar por lo menos un producto");
            //document.querySelector('.modal-body').innerHTML = "Debe ingresar por lo menos un producto";
            //toggleModal();
            return false;
         }
         return true;
    });

    $(document).on("input","#tborders input.trfr-quantities", function(){
    //$(".trfr-quantities").change(function() {
    //$('.trfr-quantities').on('input',function(e){
      /*if (/\D/g.test($(this).val()))
      {
        // Filter non-digits from input value.
        $(this).val($(this).val().replace(/\D/g, ''));
      }*/
      let maxStock = $(this).closest("tr").find(".stock").val();
      if(Number($(this).val()) > Number(maxStock))
      {
        $(this).val(maxStock);
      }

    });

    $(document).on("click",".btn-view-transfer", function(){
        var valor_id = $(this).val();
        $.ajax({
                url: base_url+"sisvent/store/transfers/view",
                type:"POST",
                dataType:"html",
                data:{id: valor_id},
                success:function(data){
                    //console.log(data);
                    showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });

    /******************* End Transfers *******************/

    /******************* Budgets *******************/
    $(document).on("click","#btn-search-budget", function(){
        var mdata = $('#budgets-search').val();
        var params = $('#budgets-search').data("params");
        if(mdata && mdata != '')
        {
          window.location.href = window.base_url+"/sisvent/commercial/budgets/search/"+mdata+params;
        }else{
            showModal("El campo no puede estar vacío");
        }
    });
     $(document).on("keydown", '#budgets-search', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            var mdata = $('#budgets-search').val();
            var params = $('#budgets-search').data("params");
            if(mdata && mdata != '')
            {
              window.location.href = window.base_url+"/sisvent/commercial/budgets/search/"+mdata+params;
            }else{
                showModal("El campo no puede estar vacío");
            }
        }
    });

    $( "#budget-client" ).autocomplete({
      source:function(request, response){

          //console.log("*-----");
          //console.log(request.term.length);
            if(request.term.length <= 2)
            {
                $('#budget-client-id').val(null);
            }

            $.ajax({
                url: window.base_url+"/sisvent/commercial/budgets/getClients",
                type:"POST",
                dataType:"json",
                data:{valor: request.term},
                success:function(data){
                    //console.log(data);
                    response(data);
                }
            });
        },
        minLength:1,
        select:function(event, ui){
            //data=ui.item.ref;
            $('#budget-client').val(ui.item.name);
            $('#budget-client-id').val(ui.item.idClient);
            changeClientRate($('#budget-client-id').val());
        }
    });

    $( "#budgets-product" ).autocomplete({
      source:function(request, response){
            var store = $('#budget-store').val();
            var vendor = $('#budget-vendor').val();
            var client = $('#budget-client-id').val();
            if(request.term.length <= 2)
            {
                $("#budget-quantities-ele").val(null);
                $( "#budget-price-ele" ).val(null);
            }
            //console.log(request.term+" "+store);
            $.ajax({
                url: window.base_url+"/sisvent/commercial/budgets/getProducts",
                type:"POST",
                dataType:"json",
                data:{valor: request.term, orstr: store, vendor: vendor, client: client},
                success:function(data){
                    //console.log(data);
                    response(data);
                }
            });
        },
        minLength:1,
        select:function(event, ui){
            //data=ui.item.ref;
            $('#btn-agregar-budget').val(ui.item.idProduct);
            //console.log(ui.item.idProduct);
            //console.log(ui.item.last_price);
            let price = ui.item.price;
            if(ui.item.last_price)
            {
                price = ui.item.last_price;
                console.log("Ya se ha vendido antes en: $"+ui.item.last_price);
                if(ui.item.last_price < ui.item.price_base){
                  price = ui.item.price;
                }
            }else
            {
                console.log("Primera vez que se vende");
                switch(parseInt($("#budget-rate").val()))
                {
                    case 1:
                        //console.log("1::"+ui.item.price);
                        price = ui.item.price;
                    break;
                    case 2:
                        //console.log("2::"+ui.item.price_base);
                        price = ui.item.price_base;
                    break;
                    case 3:
                        //console.log("3::"+ui.item.price_scale);
                        price = ui.item.price_scale;
                    break;
                    case 4:
                        //console.log("4::"+ui.item.price_dist);
                        price = ui.item.price_dist;
                    break;
                    default:
                        //console.log("default::"+ui.item.price);
                        price = ui.item.price;
                    break;
                }  
            }
            //console.log("  --->  "+ui.item.last_query);
            //console.log(price);
            $( "#budget-price-ele" ).val(price);
            $( "#budget-quantities-ele" ).val(null);
            $("#budget-quantities-ele").focus();
        }
    });

    $('#btn-agregar-budget').on('click',function(){
      var mdata = $(this).val();
      addProduct(mdata);
    });
    $(document).on("keydown", "#new-budget-form", function(event) { 
        return event.key != "Enter";
    });
    $(document).on("keydown", "#new-noinvoice-form", function(event) { 
        return event.key != "Enter";
    });

    $(document).on("keydown", '#budgets-product', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            $("#budget-quantities-ele").val(null);
            $("#budget-quantities-ele").focus();
            //var mdata = $('#btn-agregar-budget').val();
            //addProduct(mdata);
        }
    });

    $(document).on("keydown", '#budget-quantities-ele', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            //console.log("focus");
            $("#budget-price-ele").select();
            $("#budget-price-ele").focus();
            //$("#budget-quantities-ele").val(null);
            //$("#budget-quantities-ele").focus();
            //var mdata = $('#btn-agregar-budget').val();
            //addProduct(mdata);
        }
    });

    $(document).on("keydown", '#budget-price-ele', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            //$("#budget-quantities-ele").val(null);
            //$("#budget-quantities-ele").focus();
            var mdata = $('#btn-agregar-budget').val();
            addProduct(mdata);
        }
    });

    function addProduct(mdata)
    {
        if(mdata && mdata != '')
        {
            var store = $('#budget-store').val();
            var vendor = $('#budget-vendor').val();
            var client = $('#budget-client-id').val();
            //console.log(origin_store);
            $.ajax({
                url: window.base_url+"sisvent/commercial/budgets/getProduct",
                type:"POST",
                dataType:"json",
                data:{ref: mdata, orstr: store, vendor: vendor, client: client},
                success:function(data){
                    if($('input[name="refs[]"][value="'+data.idProduct+'"]').length == 0)
                    {
                        if(data.last_price)
                        {
                            console.log("Ya se ha vendido antes en: $"+data.last_price);
                        }else
                        {
                            console.log("Primera vez que se vende");
                        }
                        //console.log("  --->  "+data.last_query);

                        let price = data.price;
                        if($('#budget-price-ele').val() != null && $('#budget-price-ele').val() != ''){
                            price = $('#budget-price-ele').val();
                        }else
                        {
                            switch(parseInt($("#budget-rate").val()))
                            {
                                case 1:
                                    //console.log("1::"+data.price);
                                    price = data.price;
                                break;
                                case 2:
                                    //console.log("2::"+data.price_base);
                                    price = data.price_base;
                                break;
                                case 3:
                                    //console.log("3::"+data.price_scale);
                                    price = data.price_scale;
                                break;
                                case 4:
                                    //console.log("4::"+data.price_dist);
                                    price = data.price_dist;
                                break;
                                default:
                                    //console.log("default::"+data.price);
                                    price = data.price;
                                break;
                            }
                        }   
                        let quant = 1;
                        if($('#budget-quantities-ele').val() != null && $('#budget-quantities-ele').val() != ''){
                            quant = $('#budget-quantities-ele').val();
                        }
                        var html = "<tr class='text-gray-700 flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0'>";
                        if(!window.inEditInvoice) html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-xs whitespace-normal'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>#</span>"+($("#tborders").find('tr').length+1)+"</td>";
                        if(!window.inEditInvoice) html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Código</span><input type='hidden' name='refs[]' value='"+data.idProduct+"'>"+data.idProduct+"<input class='price' type='hidden' name='price[]' value='"+data.price+"' readonly><input class='price_base' type='hidden' name='price_base[]' value='"+data.price_base+"' readonly><input class='price_scale' type='hidden' name='price_scale[]' value='"+data.price_scale+"' readonly><input class='price_dist' type='hidden' name='price_dist[]' value='"+data.price_dist+"' readonly></td>";
                        if(window.inEditInvoice) html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Código</span><input type='hidden' name='refs[]' value='"+data.idProduct+"'>"+data.idProduct+"</td>";
                        html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-xs whitespace-normal'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Descripción</span><input type='hidden' name='desc[]' value='"+data.description+"'>"+data.description+"</td>";
                        if(!window.inEditInvoice) html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Stock</span><input class='stock w-full' type='text' name='stock[]' value='"+(data.stock ? data.stock : 0)+"' readonly></td>";
                        if(!window.inEditInvoice) html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Cantidad</span><input class='form-input budget-quantities' type='number' min='1' name='budget-quantities[]' value='"+quant+"'></td>";
                        if(window.inEditInvoice) html += "<td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class='sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Cantidad</span><div class='flex flex-row items-center gap-2'><p class='tooltip'><svg class='alarm-sim w-6 h-6 hidden' fill='none' stroke='red' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'></path></svg><span class='tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded'>Precios de venta por debajo del Precio Base</span></p><input class='form-input budget-quantities' type='text' min='1' name='budget-quantities[]' value='"+quant+"'></div></td>";
                        if(window.inEditInvoice) html += "<td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class='sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Precio Base</span><input class='price_base form-input flex-1' type='number' min='1' name='price_base[]' value='"+data.price_base+"'></td>";
                        html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Precio</span><input class='form-input budget-rates' type='number' min='1' name='budget-rates[]' value='"+price+"'></td>";
                        html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Subtotal</span><input class='form-input budget-subtotal' type='text' name='budget-subtotal[]' value='"+(quant*price)+"' readonly></td>";
                        if(window.inEditInvoice) html += "<td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class='sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Revisado</span><input type='checkbox' name='reviewed[]' value='"+($("#tborders").find('tr').length)+"' class='reviewed-cb text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark' /></td>";
                        html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Acciones</span>";
                        if(!window.inEditInvoice) html += "<button type='button' class='button-main btn-base-price-product'><p class='tooltip'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg><span class='tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded text-mam-blue-dark'>Cambiar Precio</span></p></button>";
                        html += "<button type='button' class='button-main btn-remove-budget-product'><p class='tooltip'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg><span class='tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded text-mam-blue-dark'>Eliminar</span></p></button>";
                        html += "</td>";
                        html += "</tr>";
                        if(window.inEditInvoice) 
                          $("#tborders").append(html);
                        else  
                          $("#tborders").prepend(html);
                        $('#btn-agregar-budget').val(null);
                        $( "#budgets-product" ).val(null);
                        $("#budget-quantities-ele").val(null);
                        $( "#budget-price-ele" ).val(null);
                        $( "#budget-total-products" ).val($("#tborders").find('tr').length);
                        $( "#budgets-product" ).focus();
                        if(!window.inEditInvoice) changeListIndex();
                        window.calcTotal();
                        if(Number(price) < Number(data.price_base))
                        {
                            showModal("El precio ingresado es menor que el precio base");
                            //document.querySelector('.modal-body').innerHTML = "El precio ingresado es menor que el precio base";
                            //toggleModal();
                        }
                        if(Number(price) == Number(data.price_base))
                        {
                            showModal("El precio ingresado es igual que el precio base");
                            //document.querySelector('.modal-body').innerHTML = "El precio ingresado es menor que el precio base";
                            //toggleModal();
                        }
                        if(window.inBudgets){
                          window.saveBudget();
                        } 
                    }else
                    {
                        showModal("Este producto ya ha sido agregado");
                        $('#btn-agregar-budget').val(null);
                        $( "#budgets-product" ).val(null);
                        $("#budget-quantities-ele").val(null);
                        $( "#budget-price-ele" ).val(null);
                        $( "#budgets-product" ).focus();
                    }
                }
            });
        }else{
            showModal("Por favor seleccione un producto");
            //showModal("Por favor seleccione un producto");
        }
    }

    $(document).on("input","#tborders input.budget-quantities", function(){
    //$(".trfr-quantities").change(function() {
    //$('.trfr-quantities').on('input',function(e){
      /*if (/\D/g.test($(this).val()))
      {
        // Filter non-digits from input value.
        $(this).val($(this).val().replace(/\D/g, ''));
      }*/
      /*let maxStock = $(this).closest("tr").find(".stock").val();
      if(Number($(this).val()) > Number(maxStock))
      {
        //$(this).val(maxStock);
          showModal("La cantidad ingresada es menor al stock disponible");
      }*/

      $(this).closest("tr").find(".budget-subtotal").val((Number($(this).val())*Number($(this).closest("tr").find(".budget-rates").val())));
      window.calcTotal();

    });

    $(document).on("focusout","#tborders input.budget-quantities", function(){
    //$(".trfr-quantities").change(function() {
    //$('.trfr-quantities').on('input',function(e){
      /*if (/\D/g.test($(this).val()))
      {
        // Filter non-digits from input value.
        $(this).val($(this).val().replace(/\D/g, ''));
      }*/
      /*let maxStock = $(this).closest("tr").find(".stock").val();
      if(Number($(this).val()) > Number(maxStock))
      {
        //$(this).val(maxStock);
        showModal("La cantidad ingresada es menor al stock disponible");
      }*/

      //$(this).closest("tr").find(".budget-subtotal").val((Number($(this).val())*Number($(this).closest("tr").find(".budget-rates").val())));
      //window.calcTotal();

    });

    $(document).on("focusout","#tborders input.budget-rates", function(){
    //$(".trfr-quantities").change(function() {
    //$('.trfr-quantities').on('input',function(e){
      /*if (/\D/g.test($(this).val()))
      {
        // Filter non-digits from input value.
        $(this).val($(this).val().replace(/\D/g, ''));
      }*/
      console.log($(this).closest("tr").find(".price_base").val());
      let price_base = $(this).closest("tr").find(".price_base").val();
      if(Number($(this).val()) < Number(price_base))
      {
          showModal("El precio ingresado es menor que el precio base");
          if($(this).closest("tr").find(".reviewed-cb") && !$(this).closest("tr").find(".reviewed-cb").is(':checked') ) $(this).closest("tr").find(".alarm-sim").show();
          /*document.querySelector('.modal-title').innerHTML = "Advertencia";
          document.querySelector('.modal-body').innerHTML = "El precio ingresado es menor que el precio base";
          document.querySelector('.modal-close').innerHTML = "Aceptar";
          toggleModal();*/
      }else
      {
        $(this).closest("tr").find(".alarm-sim").hide();
      }
      if(Number($(this).val()) == Number(price_base))
      {
          showModal("El precio ingresado es igual que el precio base");
          if($(this).closest("tr").find(".reviewed-cb") && !$(this).closest("tr").find(".reviewed-cb").is(':checked') ) $(this).closest("tr").find(".alarm-sim").show();
          /*document.querySelector('.modal-title').innerHTML = "Advertencia";
          document.querySelector('.modal-body').innerHTML = "El precio ingresado es menor que el precio base";
          document.querySelector('.modal-close').innerHTML = "Aceptar";
          toggleModal();*/
      }else
      {
        $(this).closest("tr").find(".alarm-sim").hide();
      }

    });

    $(document).on("change","#tborders input.budget-rates", function(){

        $(this).closest("tr").find(".budget-subtotal").val((Number($(this).val())*Number($(this).closest("tr").find(".budget-quantities").val())));
        
        let price_base = $(this).closest("tr").find(".price_base").val();
        if($(this).closest("tr").find(".reviewed-cb") && !$(this).closest("tr").find(".reviewed-cb").is(':checked') ){
          if(Number($(this).val()) <= Number(price_base))
          {
               $(this).closest("tr").find(".alarm-sim").show();
              
          }else
          {
            $(this).closest("tr").find(".alarm-sim").hide();
          }
        }
        window.calcTotal();
    });

    $(document).on("change","#tborders input.price_base", function(){
        
        let price_base = $(this).val();
        if($(this).closest("tr").find(".reviewed-cb") && !$(this).closest("tr").find(".reviewed-cb").is(':checked') ){
          if(Number($(this).closest("tr").find(".budget-rates").val()) <= Number(price_base))
          {
              $(this).closest("tr").find(".alarm-sim").show();
          }else
          {
            $(this).closest("tr").find(".alarm-sim").hide();
          }
        }
    });

    $(document).on("click","#tborders input.reviewed-cb", function(){
        
        let price_base = $(this).closest("tr").find(".price_base").val();
        if(!$(this).is(':checked') ){
          if(Number($(this).closest("tr").find(".budget-rates").val()) <= Number(price_base))
          {
              $(this).closest("tr").find(".alarm-sim").show();
          }else
          {
            $(this).closest("tr").find(".alarm-sim").hide();
          }
        }else
        {
          $(this).closest("tr").find(".alarm-sim").hide();
        }
    });

    $('#budget-vendor').change(function() {
        //document.querySelector('.modal-body').innerHTML = "Sisas por eso";
        //toggleModal();
        changeVendor($('#budget-vendor').val());
    });
    //if($('#budget-vendor').length) changeVendorClients($('#budget-vendor').val());

    /*$("#budget-client").change(function() {
        //document.querySelector('.modal-body').innerHTML = "Sisas por eso";
        //toggleModal();
        //changeVendorClients($('#budget-vendor').val());
        //changeClientRate($('#budget-client').val());
        console.log($('#budget-client').val());
        if($('#budget-client').val() == "")
        {
            $('#budget-client-id').val(null);
        }
        
    });*/
    //if($('#budget-client').length) changeClientRate($('#budget-client').val());

     $("#new-budget-form").on('submit', function(e){
         //e.preventDefault();
         //console.log($("#tborders").find('tr').length);
         

         if($('#budget-client-id').val() == null || $('#budget-client-id').val() == ''){
             showModal("Debe seleccionar un cliente");
            //document.querySelector('.modal-body').innerHTML = "Debe ingresar por lo menos un producto";
            //toggleModal();
            return false;
         }

         if($('#hasiva-field').val() == null || $('#hasiva-field').val() == ''){
             showModal("Debe seleccionar si es Factura con IVA o Remisión");
            //document.querySelector('.modal-body').innerHTML = "Debe ingresar por lo menos un producto";
            //toggleModal();
            return false;
         }

         if($("#tborders").find('tr').length == 0){
             showModal("Debe ingresar por lo menos un producto");
            //document.querySelector('.modal-body').innerHTML = "Debe ingresar por lo menos un producto";
            //toggleModal();
            return false;
         }

         if(!checkPrices()){
           return false;
         }

         if(window.inBudgets){
          window.saveBudget();
         } 
         
         return true;
    });

     $("#new-noinvoice-form").on('submit', function(e){
         //e.preventDefault();
         console.log($("#budget-client-id").val());
         if($('#budget-client-id').val() == null || $('#budget-client-id').val() == ''){
             showModal("Debe seleccionar un cliente");
            //document.querySelector('.modal-body').innerHTML = "Debe ingresar por lo menos un producto";
            //toggleModal();
            return false;
         }

         
         return true;
    });

    $(document).on("click",".btn-base-price-product", function(){
        switch(parseInt(window.$("#budget-rate").val()))
        {
          case 1:
              //console.log("1::"+window.$(this).closest("tr").find(".price").val());//budget-rates
              //price = data.price;
              window.$(this).closest("tr").find(".budget-rates").val(window.$(this).closest("tr").find(".price").val());
              window.$(this).closest("tr").find(".budget-subtotal").val((Number(window.$(this).closest("tr").find(".budget-quantities").val())*Number(window.$(this).closest("tr").find(".price").val())));
          break;
          case 2:
              //console.log("2::"+window.$(this).closest("tr").find(".price_base").val());
              //price = data.price_base;
              window.$(this).closest("tr").find(".budget-rates").val(window.$(this).closest("tr").find(".price_base").val());
              window.$(this).closest("tr").find(".budget-subtotal").val((Number(window.$(this).closest("tr").find(".budget-quantities").val())*Number(window.$(this).closest("tr").find(".price_base").val())));
          break;
          case 3:
              //console.log("3::"+window.$(this).closest("tr").find(".price_scale").val());
              //price = data.price_scale;
              window.$(this).closest("tr").find(".budget-rates").val(window.$(this).closest("tr").find(".price_scale").val());
              window.$(this).closest("tr").find(".budget-subtotal").val((Number(window.$(this).closest("tr").find(".budget-quantities").val())*Number(window.$(this).closest("tr").find(".price_scale").val())));
          break;
          case 4:
              //console.log("4::"+window.$(this).closest("tr").find(".price_dist").val());
              //price = data.price_dist;
              window.$(this).closest("tr").find(".budget-rates").val(window.$(this).closest("tr").find(".price_dist").val());
              window.$(this).closest("tr").find(".budget-subtotal").val((Number(window.$(this).closest("tr").find(".budget-quantities").val())*Number(window.$(this).closest("tr").find(".price_dist").val())));
          break;
          default:
              //console.log("default::"+window.$(this).closest("tr").find(".price").val());
              //price = data.price;
              window.$(this).closest("tr").find(".budget-rates").val(window.$(this).closest("tr").find(".price").val());
              window.$(this).closest("tr").find(".budget-subtotal").val((Number(window.$(this).closest("tr").find(".budget-quantities").val())*Number(window.$(this).closest("tr").find(".price").val())));
          break;
        }
        //$(this).closest("tr").find(".budget-rates").val($(this).closest("tr").find(".price_base").val());
        //$(this).closest("tr").find(".budget-subtotal").val((Number($(this).closest("tr").find(".budget-rates").val())*Number($(this).closest("tr").find(".budget-quantities").val())));
        window.calcTotal();
    });

    $(document).on("click",".btn-remove-budget-product", function(){
        $(this).closest("tr").remove();
        $( "#budget-total-products" ).val($("#tborders").find('tr').length);
        if(!window.inEditInvoice) changeListIndex();
        window.calcTotal();
    });
    /*$('#budget-store').change(function() {
        $("#tborders").html('');
        $( "#budget-total-products" ).val($("#tborders").find('tr').length);
        changeListIndex();
        window.calcTotal();
    });*/
     $('#budget-tax').change(function() {
        if($(this).is(':checked'))
        {
            $("#budget-tax-value").show();

        }else
        {
            $("#budget-tax-value").hide();

        }
    });

     $('#list_price').change(function() {
        
      window.calcTotal();
    });


    $(document).on("click",".btn-view-budget", function(){
        var valor_id = $(this).val();
        $.ajax({
                url: base_url+"sisvent/commercial/budgets/view",
                type:"POST",
                dataType:"html",
                data:{id: valor_id},
                success:function(data){
                    //console.log(data);
                    showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });

    $(document).on("click","#btn-unblock-budget", function(){
        var client_id = $('#budget-client-id').val();
        $.ajax({
                url: base_url+"sisvent/commercial/budgets/unblock",
                type:"POST",
                dataType:"json",
                data:{id: client_id},
                success:function(data){
                    if(data.can_bill != 1)
                    {
                      $("#create-budget").prop('disabled', true);
                      $("#btn-unblock-budget").show();

                    }else{
                      $("#create-budget").prop('disabled', false);
                      $("#btn-unblock-budget").hide();
                    }
                }
            });
    });

     window.calcTotal();
    /******************* End Budgets ***************/

    $(document).on("click",".btn-view-client", function(){
        var valor_id = $(this).val();
        $.ajax({
                url: base_url+"sisvent/business/clients/view",
                type:"POST",
                dataType:"html",
                data:{id: valor_id},
                success:function(data){
                    //console.log(data);
                    showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });

    /******************* Invoices ******************/
    $(document).on("click","#btn-search-invoice", function(){
        var mdata = $('#invoices-search').val();
        var params = $('#invoices-search').data("params");
        if(mdata && mdata != '')
        {
          window.location.href = window.base_url+"/sisvent/commercial/invoices/search/"+mdata+params;
        }else{
            showModal("El campo no puede estar vacío");
        }
    });
     $(document).on("keydown", '#invoices-search', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            var mdata = $('#invoices-search').val();
            var params = $('#invoices-search').data("params");
            if(mdata && mdata != '')
            {
              window.location.href = window.base_url+"/sisvent/commercial/invoices/search/"+mdata+params;
            }else{
                showModal("El campo no puede estar vacío");
            }
        }
    });

      $(document).on("click","#btn-search-noinvoice", function(){
        var mdata = $('#noinvoices-search').val();
        var params = $('#noinvoices-search').data("params");
        if(mdata && mdata != '')
        {
          window.location.href = window.base_url+"/sisvent/commercial/noinvoices/search/"+mdata+params;
        }else{
            showModal("El campo no puede estar vacío");
        }
    });
     $(document).on("keydown", '#noinvoices-search', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            var mdata = $('#noinvoices-search').val();
            var params = $('#noinvoices-search').data("params");
            if(mdata && mdata != '')
            {
              window.location.href = window.base_url+"/sisvent/commercial/noinvoices/search/"+mdata+params;
            }else{
                showModal("El campo no puede estar vacío");
            }
        }
    });

     $(document).on("click","#btn-search-invoice-bp", function(){
        var mdata = $('#invoices-search-bp').val();
        var params = $('#invoices-search-bp').data("params");
        if(mdata && mdata != '')
        {
          window.location.href = window.base_url+"/sisvent/commercial/invoices/searchbyproduct/"+mdata+params;
        }else{
            showModal("El campo no puede estar vacío");
        }
    });
     $(document).on("keydown", '#invoices-search-bp', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            var mdata = $('#invoices-search-bp').val();
            var params = $('#invoices-search-bp').data("params");
            if(mdata && mdata != '')
            {
              window.location.href = window.base_url+"/sisvent/commercial/invoices/searchbyproduct/"+mdata+params;
            }else{
                showModal("El campo no puede estar vacío");
            }
        }
    });

     $(document).on("click",".btn-view-invoice", function(){
        var valor_id = $(this).val();
        $.ajax({
                url: base_url+"sisvent/commercial/invoices/view",
                type:"POST",
                dataType:"html",
                data:{id: valor_id},
                success:function(data){
                    //console.log(data);
                    showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });

     $(document).on("click",".btn-view-invoice-payment", function(){
        var valor_id = $(this).val();
        $.ajax({
                url: base_url+"sisvent/commercial/invoices/viewpayment",
                type:"POST",
                dataType:"html",
                data:{id: valor_id},
                success:function(data){
                    //console.log(data);
                    showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });

    $(document).on("click",".btn-payment-invoice", function(){
        var valor_id = $(this).val();
        var params = $('.btn-payment-invoice').data("params");
        $.ajax({
                url: base_url+"sisvent/commercial/invoices/payment",
                type:"POST",
                dataType:"html",
                data:{id: valor_id, params:params},
                success:function(data){
                    //console.log(data);
                    showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });

    $(document).on("click",".btn-payment-noinvoice", function(){
        var valor_id = $(this).val();
        var params = $('.btn-payment-noinvoice').data("params");
        $.ajax({
                url: base_url+"sisvent/commercial/noinvoices/payment",
                type:"POST",
                dataType:"html",
                data:{id: valor_id, params:params},
                success:function(data){
                    //console.log(data);
                    showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });

    $(document).on("click",".invoice-do-payment-btn", function(){
        var invoice_id = $(this).val();
        var method = $('#invoice-payment-method').val();
        var payment = $('#invoice-payment-val').val();
        var comment = $('#invoice-payment-comment').val();
        var params = $('.invoice-do-payment-btn').data("params");
        $.ajax({
                url: base_url+"sisvent/commercial/invoices/makepayment",
                type:"POST",
                dataType:"html",
                data:{id: invoice_id, method: method, payment: payment, comment: comment, params:params},
                success:function(data){
                    window.location.href = data;
                    //console.log(data);
                    //showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });
    $(document).on("click",".noinvoice-do-payment-btn", function(){
        var invoice_id = $(this).val();
        var method = $('#invoice-payment-method').val();
        var payment = $('#invoice-payment-val').val();
        var comment = $('#invoice-payment-comment').val();
        var params = $('.noinvoice-do-payment-btn').data("params");
        $.ajax({
                url: base_url+"sisvent/commercial/noinvoices/makepayment",
                type:"POST",
                dataType:"html",
                data:{id: invoice_id, method: method, payment: payment, comment: comment, params:params},
                success:function(data){
                    window.location.href = data;
                    //console.log(data);
                    //showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });
    //invoice-id
    $('#invoice-id').change(function() {
        var inv = $('#invoice-id').val();
        $.ajax({
            url: window.base_url+"sisvent/admin/payments/getInvoice",
            type:"POST",
            dataType:"json",
            data:{inv: inv},
            success:function(data){
              $("#vendor").val(data.vendor_name);
              $("#client").val(data.client_name);
              $("#invoice-total").val(data.total);
              $("#invoice-payment").val(data.payment);
              $("#invoice-payment-val").val(data.total-data.payment);
            }
        });
    });

    $('#noinvoice-id').change(function() {
        var inv = $('#noinvoice-id').val();
        $.ajax({
            url: window.base_url+"sisvent/admin/nopayments/getInvoice",
            type:"POST",
            dataType:"json",
            data:{inv: inv},
            success:function(data){
              $("#vendor").val(data.vendor_name);
              $("#client").val(data.client_name);
              $("#invoice-total").val(data.total);
              $("#invoice-payment").val(data.payment);
              $("#invoice-payment-val").val(data.total-data.payment);
            }
        });
    });
    /******************* End Invoices ***************/

    /******************* Settlements ***************/
    $(document).on("click",".btn-view-settlement", function(){
        var valor_id = $(this).val();
        $.ajax({
                url: base_url+"sisvent/admin/settlements/view",
                type:"POST",
                dataType:"html",
                data:{id: valor_id},
                success:function(data){
                    //console.log(data);
                    showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });

    $(document).on("click",".btn-view-unattenclients", function(){
        var valor_id = $(this).val();
        $.ajax({
                url: base_url+"sisvent/dashboard/viewunattclients",
                type:"POST",
                dataType:"html",
                data:{id: valor_id},
                success:function(data){
                    //console.log(data);
                    showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });

    $(document).on("click",".btn-view-total-settlement", function(){
        var valor_id = $(this).val();
        $.ajax({
                url: base_url+"sisvent/admin/settlements/viewtotal",
                type:"POST",
                dataType:"html",
                data:{id: valor_id},
                success:function(data){
                    //console.log(data);
                    showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });

    $(document).on("click",".btn-view-userlostinvoices", function(){
        var valor_id = $(this).val();
        $.ajax({
                url: base_url+"sisvent/admin/settlements/viewlostinvoices",
                type:"POST",
                dataType:"html",
                data:{id: valor_id},
                success:function(data){
                    //console.log(data);
                    showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });

    $(document).on("click",".btn-view-lostinvoices", function(){
        var valor_id = $(this).val();
        $.ajax({
                url: base_url+"sisvent/dashboard/viewlostinvoices",
                type:"POST",
                dataType:"html",
                data:{id: valor_id},
                success:function(data){
                    //console.log(data);
                    showModal(data, "", "Cerrar", true);
                    //$("#modal-default .modal-body").html(data);
                }
            });
    });
    /******************* End Invoices ***************/

    //$( "#datepicker" ).datepicker();
    $('#datepicker').datepicker({ dateFormat: 'dd-mm-yy' });

    if($( "#datepicker" ).val() == null || $('#datepicker').val() == '')
    {
        $( "#datepicker" ).datepicker('setDate', 'today');
    }else
    {
        $( "#datepicker" ).datepicker('setDate', $( "#datepicker" ).val());
    }

    $('body').on('focus',"#datepicker", function(){
        $('#datepicker').datepicker({ dateFormat: 'dd-mm-yy' });
        if($( "#datepicker" ).val() == null || $('#datepicker').val() == '')
        {
            $( "#datepicker" ).datepicker('setDate', 'today');
        }else
        {
            $( "#datepicker" ).datepicker('setDate', $( "#datepicker" ).val());
        }
    });

    $('#datepicker2').datepicker({ dateFormat: 'dd-mm-yy' });

    if($( "#datepicker2" ).val() == null || $('#datepicker2').val() == '')
    {
        $( "#datepicker2" ).datepicker('setDate', 'today');
    }else
    {
        $( "#datepicker2" ).datepicker('setDate', $( "#datepicker2" ).val());
    }

    $('body').on('focus',"#datepicker2", function(){
        $('#datepicker2').datepicker({ dateFormat: 'dd-mm-yy' });
        if($( "#datepicker2" ).val() == null || $('#datepicker2').val() == '')
        {
            $( "#datepicker2" ).datepicker('setDate', 'today');
        }else
        {
            $( "#datepicker2" ).datepicker('setDate', $( "#datepicker2" ).val());
        }
    });


    $('#datepicker-since').datepicker({ dateFormat: 'dd-mm-yy' });

    if($( "#datepicker-since" ).val() == null || $('#datepicker-since').val() == '')
    {
        $( "#datepicker-since" ).datepicker('setDate', '-1M');
    }else
    {
        $( "#datepicker-since" ).datepicker('setDate', $( "#datepicker-since" ).val());
    }

    $('#datepicker-until').datepicker({ dateFormat: 'dd-mm-yy' });

    if($( "#datepicker-until" ).val() == null || $('#datepicker-until').val() == '')
    {
        $( "#datepicker-until" ).datepicker('setDate', 'today');
    }else
    {
        $( "#datepicker-until" ).datepicker('setDate', $( "#datepicker-until" ).val());
    }

     $(document).on("click","#export2excel", function(){
      $('.table2excel').tableExport({fileName: "Vales-"+(new Date().toISOString().replace(/[\-\:\.]/g, "")),type:'excel'});
      });
    /***************** MODAL *****************/
    var openmodal = document.querySelectorAll('.modal-open')
    for (var i = 0; i < openmodal.length; i++) {
      openmodal[i].addEventListener('click', function(event){
      event.preventDefault()
      toggleModal()
      })
    }
    
    const overlay = document.querySelector('.modal-overlay')
    if(overlay) overlay.addEventListener('click', toggleModal)
    
    var closemodal = document.querySelectorAll('.modal-close')
    for (var i = 0; i < closemodal.length; i++) {
      closemodal[i].addEventListener('click', toggleModal)
    }
    
    document.onkeydown = function(evt) {
      evt = evt || window.event
      var isEscape = false
      if ("key" in evt) {
      isEscape = (evt.key === "Escape" || evt.key === "Esc")
      } else {
      isEscape = (evt.keyCode === 27)
      }
      if (isEscape && document.body.classList.contains('modal-active')) {
          toggleModal()
      }
    };
    
    
   
    /***************** END MODAL *****************/

    changeDatasheetLabels($('#product-datasheet').val());


    $('#product-datasheet').change(function() {
        var inv = $('#product-datasheet').val();
         changeDatasheetLabels($('#product-datasheet').val());
    });
};

 window.saveBudget = function() {
      var inputs = new Object();
      inputs.refs = document.querySelectorAll("#tborders input[name='refs[]']");
      inputs.desc = document.querySelectorAll("#tborders input[name='desc[]']");
      inputs.stock = document.querySelectorAll("#tborders input[name='stock[]']");
      inputs.price_base = document.querySelectorAll("#tborders input[name='price_base[]']");
      inputs.budget_rates = document.querySelectorAll("#tborders input[name='budget-rates[]']");
      inputs.budget_quantities = document.querySelectorAll("#tborders input[name='budget-quantities[]']");
      inputs.budget_subtotal = document.querySelectorAll("#tborders input[name='budget-subtotal[]']");

      inputs.price = document.querySelectorAll("#tborders input[name='price[]']");
      inputs.price_scale = document.querySelectorAll("#tborders input[name='price_scale[]']");
      inputs.price_dist = document.querySelectorAll("#tborders input[name='price_dist[]']");

      var inputsf = new Object();

      inputsf.budget_vendor = $("#budget-vendor").val();
      inputsf.budget_store = $("#budget-store").val();
      inputsf.hasiva_field = $("#hasiva-field").val();
      inputsf.budget_client = $("#budget-client").val();
      inputsf.budget_client_id = $("#budget-client-id").val();
      inputsf.e_commerce = $("#e_commerce").is(":checked");
      inputsf.comment = $("#invoice-payment-comment").val();
      inputsf.refs = new Object();
      inputsf.desc = new Object();
      inputsf.stock = new Object();
      inputsf.price_base = new Object();
      inputsf.budget_rates = new Object();
      inputsf.budget_quantities = new Object();
      inputsf.budget_subtotal = new Object();

      inputsf.price = new Object();
      inputsf.price_scale = new Object();
      inputsf.price_dist = new Object();

      //var inputs = document.querySelectorAll("#tborders input[name='name[]']");
      //console.log(inputs.refs);
      for (var i = 0; i < inputs.refs.length; i++) {
        //console.log("array[" + i + "].value= "+ inputs.refs[i].value + " ");
        inputsf.refs[i] = inputs.refs[i].value;
        inputsf.desc[i] = inputs.desc[i].value;
        inputsf.stock[i] = inputs.stock[i].value;
        inputsf.price_base[i] = inputs.price_base[i].value;
        inputsf.budget_rates[i] = inputs.budget_rates[i].value;
        inputsf.budget_quantities[i] = inputs.budget_quantities[i].value;
        inputsf.budget_subtotal[i] = inputs.budget_subtotal[i].value;
        inputsf.price[i] = inputs.price[i].value;
        inputsf.price_scale[i] = inputs.price_scale[i].value;
        inputsf.price_dist[i] = inputs.price_dist[i].value;
      }
      console.log(JSON.stringify(inputsf));
      //localStorage.budget = JSON.stringify(inputsf);
      localStorage.setItem("budget", JSON.stringify(inputsf));
    }

    window.loadBudget = function() {

      var bud = localStorage.getItem("budget");

      var budjson =JSON.parse(bud);

      var inputsf = new Object();

      
      console.log(budjson);
      console.log(budjson.refs);
      console.log(Object.keys(budjson.refs).length);
      //localStorage.budget = JSON.stringify(inputsf);

      $("#budget-vendor").val(budjson.budget_vendor);
      $("#budget-store").val(budjson.budget_store);
      $("#hasiva-field").val(budjson.hasiva_field);
      $("#budget-client").val(budjson.budget_client);
      $("#budget-client-id").val(budjson.budget_client_id);
      $("#e_commerce").prop('checked', budjson.e_commerce);
      $("#invoice-payment-comment").val(budjson.comment);

      for (var i = 0; i < Object.keys(budjson.refs).length; i++) {
        console.log("array[" + i + "].value= "+ budjson.refs[i] + " ");
       

        var html = "<tr class='text-gray-700 flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0'>";
        html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-xs whitespace-normal'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>#</span>"+($("#tborders").find('tr').length+1)+"</td>";
        html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Código</span><input type='hidden' name='refs[]' value='"+budjson.refs[i]+"'>"+budjson.refs[i]+"<input class='price' type='hidden' name='price[]' value='"+budjson.price[i]+"' readonly><input class='price_base' type='hidden' name='price_base[]' value='"+budjson.price_base[i]+"' readonly><input class='price_scale' type='hidden' name='price_scale[]' value='"+budjson.price_scale[i]+"' readonly><input class='price_dist' type='hidden' name='price_dist[]' value='"+budjson.price_dist[i]+"' readonly></td>";
        html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-xs whitespace-normal'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Descripción</span><input type='hidden' name='desc[]' value='"+budjson.desc[i]+"'>"+budjson.desc[i]+"</td>";
        html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Stock</span><input class='stock w-full' type='text' name='stock[]' value='"+(budjson.stock[i] ? budjson.stock[i] : 0)+"' readonly></td>";
        html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Cantidad</span><input class='form-input budget-quantities' type='number' min='1' name='budget-quantities[]' value='"+budjson.budget_quantities[i]+"'></td>";
        html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Precio</span><input class='form-input budget-rates' type='number' min='1' name='budget-rates[]' value='"+budjson.budget_rates[i]+"'></td>";
        html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Subtotal</span><input class='form-input budget-subtotal' type='text' name='budget-subtotal[]' value='"+(budjson.budget_quantities[i]*budjson.budget_rates[i])+"' readonly></td>";
        html += "<td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Acciones</span><button type='button' class='button-main btn-base-price-product'><p class='tooltip'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg><span class='tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded text-mam-blue-dark'>Cambiar Precio</span></p></button>";
        html += "<button type='button' class='button-main btn-remove-budget-product'><p class='tooltip'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg><span class='tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded text-mam-blue-dark'>Eliminar</span></p></button>";
        html += "</td>";
        html += "</tr>";
        $("#tborders").prepend(html);
      }
      
      $( "#budget-total-products" ).val($("#tborders").find('tr').length);
      $( "#budgets-product" ).focus();
      changeListIndex();
      window.calcTotal();

    }

    window.clearBudget = function() {
      console.log('clear budget');
       localStorage.setItem("budget", null);
    }


 function toggleModal () {
      const body = document.querySelector('body')
      const modal = document.querySelector('.modal')
      modal.classList.toggle('opacity-0')
      modal.classList.toggle('pointer-events-none')
      body.classList.toggle('modal-active')
    }

function showModal(body, title = "Advertencia", button = "Aceptar", big = false)
{
      document.querySelector('.modal-title').innerHTML = title;
      document.querySelector('.modal-body').innerHTML = body;
      document.querySelector('.modal-close-btn').innerHTML = button;
      if(big){
          $('.modal-container').css('margin-top', '0');
          $('.modal-container').css('max-width', '80%');
      }else
      {
          $('.modal-container').css('margin-top', '-35%');
          $('.modal-container').css('max-width', '28rem');
      }
      toggleModal();
}

function changeDatasheetLabels(datasheet){
      $.ajax({
        url: window.base_url+"sisvent/business/products/getDatasheetsLabels",
        type:"POST",
        dataType:"json",
        data:{datasheet: datasheet},
        success:function(data){
          console.log(data);

          var html = "";
          for (var i = 0; i < data.length; i++) {
           html += '<label class="block text-sm mt-4">'
            + '  <span class="text-gray-700">'+(data[i].label)+'</span>'
            + '  <input id="ds-'+(data[i].idDatasheet)+'-'+(data[i].idLabel)+'" class="form-input" type="text" name="ds-'+(data[i].idDatasheet)+'-'+(data[i].idLabel)+'" value="'+(data[i].default_value)+'" required/>'
            + '</label>';
          }
          $("#datasheets-elemets").html(html); 

          var pid = $('#edit-product-id').val();
          
          if(pid && pid != '')
          {
              updateDatasheetsValues(pid, datasheet);
          }
          /*var html = "";
          for (var i = 0; i < data.length; i++) {
            html += "<option value='"+data[i].idClient+"'>"+data[i].name+"</option>";
          }
          $("#budget-client").html(html); */
          //$("#e_commerce").prop('checked', data.e_commerce == 1);
          //changeClientRate($('#budget-client').val());
        }
    });
}

function updateDatasheetsValues(product_id,datasheet){
      $.ajax({
        url: window.base_url+"sisvent/business/products/getDatasheetsValues",
        type:"POST",
        dataType:"json",
        data:{product_id:product_id,datasheet: datasheet},
        success:function(data){
          console.log(data);
          for (var i = 0; i < data.length; i++) {
              $('#ds-'+(data[i].idDatasheet)+'-'+(data[i].idLabel)).val(data[i].value);
          }
        }
    });
}

function getUserMessages(){
      $.ajax({
        url: window.base_url+"sisvent/message/getNumUnreadMessages",
        type:"POST",
        dataType:"json",
        success:function(data){
          //console.log(data);
          //$('#count-msgs').html(data.data);
          window.count_msgs = data.data;
          if(data.data > 0){
            $('#noti-badge').show();
          }else
          {
            $('#noti-badge').hide();
          }
        }
    });
}

function changeVendorClients(vendor){
      $.ajax({
        url: window.base_url+"sisvent/commercial/budgets/getVendorClients",
        type:"POST",
        dataType:"json",
        data:{vendor: vendor},
        success:function(data){
          /*var html = "";
          for (var i = 0; i < data.length; i++) {
            html += "<option value='"+data[i].idClient+"'>"+data[i].name+"</option>";
          }
          $("#budget-client").html(html); */
          $("#e_commerce").prop('checked', data[i].e_commerce == 1);
          //changeClientRate($('#budget-client').val());
        }
    });
}

function changeVendor(vendor){
      $.ajax({
        url: window.base_url+"sisvent/commercial/budgets/getVendor",
        type:"POST",
        dataType:"json",
        data:{vendor: vendor},
        success:function(data){
          /*var html = "";
          for (var i = 0; i < data.length; i++) {
            html += "<option value='"+data[i].idClient+"'>"+data[i].name+"</option>";
          }
          $("#budget-client").html(html); */
          $("#e_commerce").prop('checked', data.e_commerce == 1);
          //changeClientRate($('#budget-client').val());
        }
    });
}

function changeClientRate(client){
  $.ajax({
        url: window.base_url+"sisvent/commercial/budgets/getClient",
        type:"POST",
        dataType:"json",
        data:{client: client},
        success:function(data){
            console.log("vendor rate:"+data.rate);
            $("#budget-rate").val(data.rate != 0 ? data.rate : 1);
            //console.log("vendor:"+data.vendor);
            $('#budget-vendor').val(data.vendor);
            $('#budget-store').val(data.store);
            //console.log("deuda:"+data.debt+" "+data.maximum_debt);
            //console.log("Moroso:"+data.defaulter+" "+data.oldestInvioce);
            if(parseInt(data.debt) > parseInt(data.maximum_debt))
            {
              showModal("Este cliente está moroso, debe $"+data.debt);
            }else if(data.defaulter)
            {
              showModal("Este cliente no ha pagado facturas vencidas, debe una de "+data.oldestInvioce);
              if(data.can_bill != 1)
              {
                $("#create-budget").prop('disabled', true);
                $("#btn-unblock-budget").show();
              }else{
                $("#create-budget").prop('disabled', false);
                $("#btn-unblock-budget").hide();
              }
            }else{
                $("#create-budget").prop('disabled', false);
                $("#btn-unblock-budget").hide();
            }
        }
    });
}

function changeListIndex(){
  $("#tborders").find('tr').each(function () {
       $(this).find('td').eq(0).text($(this).index()+1);
    });
}

function showVouchers()
{
  var user = $('#vendor-voucher').children("option:selected").val();
    var since = $('#datepicker-since').val();
    var until = $('#datepicker-until').val();
    $.ajax({
            url: base_url+"sisvent/admin/vouchers/getDetail",
            type:"POST",
            dataType:"html",
            data:{user: user, since: since, until: until},
            success:function(data){
                $("#user-vouchers").html(data);
            }
        }); 
}


window.calcTotal = function ()
{
    var total = 0;
    $("#tborders > tr").each(function () {
          //showModal($(this).find('td').eq(0).text() + " " + $(this).find('td').eq(1).text() );
          //let price = 0;
          total += Number($(this).closest("tr").find(".budget-subtotal").val());  
          //console.log(total+"  "+$(this).closest("tr").find(".budget-subtotal").val());    
    });

    var lp = "";
    if($("#list_price").is(':checked')){
      lp = " - 30% = "+(total*0.7).toLocaleString('en-US');
    }
    $("#budget-total-val").val(total);
    $("#budget-total").val(total.toLocaleString('en-US')+lp);
}


function getAllUrlParams(url) {

  // get query string from url (optional) or window
  var queryString = url ? url.split('?')[1] : window.location.search.slice(1);

  // we'll store the parameters here
  var obj = {};

  // if query string exists
  if (queryString) {

    // stuff after # is not part of query string, so get rid of it
    queryString = queryString.split('#')[0];

    // split our query string into its component parts
    var arr = queryString.split('&');

    for (var i = 0; i < arr.length; i++) {
      // separate the keys and the values
      var a = arr[i].split('=');

      // set parameter name and value (use 'true' if empty)
      var paramName = a[0];
      var paramValue = typeof (a[1]) === 'undefined' ? true : a[1];

      // (optional) keep case consistent
      paramName = paramName.toLowerCase();
      if (typeof paramValue === 'string') paramValue = paramValue.toLowerCase();

      // if the paramName ends with square brackets, e.g. colors[] or colors[2]
      if (paramName.match(/\[(\d+)?\]$/)) {

        // create key if it doesn't exist
        var key = paramName.replace(/\[(\d+)?\]/, '');
        if (!obj[key]) obj[key] = [];

        // if it's an indexed array e.g. colors[2]
        if (paramName.match(/\[\d+\]$/)) {
          // get the index value and add the entry at the appropriate position
          var index = /\[(\d+)\]/.exec(paramName)[1];
          obj[key][index] = paramValue;
        } else {
          // otherwise add the value to the end of the array
          obj[key].push(paramValue);
        }
      } else {
        // we're dealing with a string
        if (!obj[paramName]) {
          // if it doesn't exist, create property
          obj[paramName] = paramValue;
        } else if (obj[paramName] && typeof obj[paramName] === 'string'){
          // if property does exist and it's a string, convert it to an array
          obj[paramName] = [obj[paramName]];
          obj[paramName].push(paramValue);
        } else {
          // otherwise add the property
          obj[paramName].push(paramValue);
        }
      }
    }
  }

  return obj;
}

function checkPrices()
{
    var good = true;
    var prod = "";
    $("#tborders > tr").each(function () {
        if(Number($(this).closest("tr").find(".budget-rates").val()) < Number($(this).closest("tr").find(".price_base").val())){
          good = false;
          prod = $(this).closest("tr").find("td:eq(1)").text();
          return false;
        }
    });

    var skip = false;
    if(window.isadusr){
      skip = true;
    }

    if(!skip && !good)
    {
      showModal("El precio ingresado para "+prod+" es menor que el precio base.");
      return false;
    }else{
      return true;
    }
}

/*function changePrices()
{
    $("#tborders > tr").each(function () {
        //showModal($(this).find('td').eq(0).text() + " " + $(this).find('td').eq(1).text() );
        //let price = 0;
        switch(parseInt($("#budget-rate").val()))
        {
            case 1:
                console.log("1::"+$(this).closest("tr").find(".price").val());//budget-rates
                //price = data.price;
                $(this).closest("tr").find(".budget-rates").val($(this).closest("tr").find(".price").val());
            break;
            case 2:
                console.log("2::"+$(this).closest("tr").find(".price_base").val());
                //price = data.price_base;
                $(this).closest("tr").find(".budget-rates").val($(this).closest("tr").find(".price_base").val());
            break;
            case 3:
                console.log("3::"+$(this).closest("tr").find(".price_scale").val());
                //price = data.price_scale;
                $(this).closest("tr").find(".budget-rates").val($(this).closest("tr").find(".price_scale").val());
            break;
            case 4:
                console.log("4::"+$(this).closest("tr").find(".price_dist").val());
                //price = data.price_dist;
                $(this).closest("tr").find(".budget-rates").val($(this).closest("tr").find(".price_dist").val());
            break;
            default:
                console.log("default::"+$(this).closest("tr").find(".price").val());
                //price = data.price;
                $(this).closest("tr").find(".budget-rates").val($(this).closest("tr").find(".price").val());
            break;
        }
    });
}*/