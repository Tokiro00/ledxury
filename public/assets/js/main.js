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

window.onload = function() {
  
	initVueComponent(bars, '#bars');
	/*if(document.querySelectorAll('#bars').length > 0) {
	    bars.el = '#bars';
	    vm = new Vue(bars);
		window.vm = vm;
	}*/
	initVueComponent(tables, '#myTable');

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
                        var html = "<tr class='text-gray-700'>";
                        html += "<td class='px-4 py-3'><input type='hidden' name='refs[]' value='"+data.idProduct+"'>"+data.idProduct+"</td>";
                        html += "<td class='px-4 py-3 text-xs whitespace-normal'>"+data.description+"</td>";
                        html += "<td class='px-4 py-3'><input class='form-input quantities' type='number' name='quantities[]' min='0' value='1'></td>";
                        html += "<td class='px-4 py-3'><button type='button' class='button-main btn-remove-inv-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg></button></td>";
                        html += "</tr>";
                        $("#tborders").prepend(html);
                        $('#btn-agregar').val(null);
                        $( "#producto" ).val(null);
                        
                    }else
                    {
                        alert("Este producto ya ha sido agregado");
                    }
                }
            });
      }else{
        alert("Por favor seleccione un producto");
      }
    });
    $(document).on("keydown", "#new-inventory-form", function(event) { 
        return event.key != "Enter";
    });
    $(document).on("keydown", '#producto', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
             var mdata = $('#btn-agregar').val();
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
                                var html = "<tr class='text-gray-700'>";
                                html += "<td class='px-4 py-3'><input type='hidden' name='refs[]' value='"+data.idProduct+"'>"+data.idProduct+"</td>";
                                html += "<td class='px-4 py-3 text-xs whitespace-normal'>"+data.description+"</td>";
                                html += "<td class='px-4 py-3'><input class='form-input quantities' type='number' name='quantities[]' min='0' value='1'></td>";
                                html += "<td class='px-4 py-3'><button type='button' class='button-main btn-remove-inv-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg></button></td>";
                                html += "</tr>";
                                $("#tborders").prepend(html);
                                $('#btn-agregar').val(null);
                                $( "#producto" ).val(null);
                                
                            }else
                            {
                                alert("Este producto ya ha sido agregado");
                            }
                        }
                    });
              }else{
                alert("Por favor seleccione un producto");
              }
        }
    });

    $(document).on("click",".btn-remove-inv-product", function(){
        $(this).closest("tr").remove();
    });

    $( "#inv-store" ).change(function() {
        var store = $('#inv-store').children("option:selected").val();
        $( "#edit-inventory" ).prop('disabled', store==-1);
        changeInventoryStore(window.base_url);
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
      if(mdata != '')
      {
        var origin_store = $('#origin-store').val();
        //console.log(origin_store);
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
                        html += "<td class='px-4 py-3'><button type='button' class='button-main btn-remove-inv-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg></button></td>";
                        html += "</tr>";
                        $("#tborders").prepend(html);
                        $('#btn-agregar-trfr').val(null);
                        $( "#transfer-product" ).val(null);
                        
                    }else
                    {
                        alert("Este producto ya ha sido agregado");
                    }
                }
            });
      }else{
        alert("Por favor seleccione un producto");
      }
    });
    $(document).on("keydown", "#new-transfers-form", function(event) { 
        return event.key != "Enter";
    });
    $(document).on("keydown", '#transfer-product', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
             var mdata = $('#btn-agregar-trfr').val();
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
                                html += "<td class='px-4 py-3'><button type='button' class='button-main btn-remove-inv-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg></button></td>";
                                html += "</tr>";
                                $("#tborders").prepend(html);
                                $('#btn-agregar-trfr').val(null);
                                $( "#transfer-product" ).val(null);
                                
                            }else
                            {
                                alert("Este producto ya ha sido agregado");
                            }
                        }
                    });
              }else{
                alert("Por favor seleccione un producto");
              }
        }
    });

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
    /******************* End Transfers *******************/

    /******************* Budgets *******************/
    $( "#budgets-product" ).autocomplete({
      source:function(request, response){
            var store = $('#budget-store').val();
            //console.log(request.term+" "+store);
            $.ajax({
                url: window.base_url+"/sisvent/commercial/budgets/getProducts",
                type:"POST",
                dataType:"json",
                data:{valor: request.term, orstr: store},
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
        }
    });

    $('#btn-agregar-budget').on('click',function(){
      var mdata = $(this).val();
      if(mdata && mdata != '')
      {
        var store = $('#budget-store').val();
        //console.log(origin_store);
        $.ajax({
                url: window.base_url+"sisvent/commercial/budgets/getProduct",
                type:"POST",
                dataType:"json",
                data:{ref: mdata, orstr: store},
                success:function(data){
                    if($('input[value="'+data.idProduct+'"]').length == 0)
                    {
                        let price = data.price;
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
                        var html = "<tr class='text-gray-700'>";
                        html += "<td class='px-4 py-3'><input type='hidden' name='refs[]' value='"+data.idProduct+"'>"+data.idProduct+"<input class='price' type='hidden' name='price[]' value='"+data.price+"' readonly><input class='price_base' type='hidden' name='price_base[]' value='"+data.price_base+"' readonly><input class='price_scale' type='hidden' name='price_scale[]' value='"+data.price_scale+"' readonly><input class='price_dist' type='hidden' name='price_dist[]' value='"+data.price_dist+"' readonly></td>";
                        html += "<td class='px-4 py-3 text-xs whitespace-normal'>"+data.description+"</td>";
                        html += "<td class='px-4 py-3'><input class='stock w-full' type='text' name='stock[]' value='"+data.stock+"' readonly></td>";
                        html += "<td class='px-4 py-3'><input class='form-input budget-rates' type='number' min='1' name='budget-rates[]' value='"+price+"'></td>";
                        html += "<td class='px-4 py-3'><input class='form-input budget-quantities' type='number' min='1' name='budget-quantities[]' value='1'></td>";
                        html += "<td class='px-4 py-3'><input class='form-input budget-subtotal' type='text' name='budget-subtotal[]' value='"+price+"' readonly></td>";
                        html += "<td class='px-4 py-3'><button type='button' class='button-main btn-base-price-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg></button>";
                        html += "<button type='button' class='button-main btn-remove-budget-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg></button>";
                        html += "</td>";
                        html += "</tr>";
                        $("#tborders").prepend(html);
                        $('#btn-agregar-budget').val(null);
                        $( "#budgets-product" ).val(null);
                        window.calcTotal();
                        if(Number(price) < Number(data.price_base))
                        {
                            showModal("El precio ingresado es menor que el precio base");
                            //document.querySelector('.modal-body').innerHTML = "El precio ingresado es menor que el precio base";
                            //toggleModal();
                        }
                    }else
                    {
                        showModal("Este producto ya ha sido agregado");
                        //alert("Este producto ya ha sido agregado");
                    }
                }
            });
      }else{
        showModal("Por favor seleccione un producto");
        //alert("Por favor seleccione un producto");
      }
    });
    $(document).on("keydown", "#new-budget-form", function(event) { 
        return event.key != "Enter";
    });

    $(document).on("keydown", '#budgets-product', function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
              var mdata = $('#btn-agregar-budget').val();
              if(mdata && mdata != '')
              {
                var store = $('#budget-store').val();
                //console.log(origin_store);
                $.ajax({
                        url: window.base_url+"sisvent/commercial/budgets/getProduct",
                        type:"POST",
                        dataType:"json",
                        data:{ref: mdata, orstr: store},
                        success:function(data){
                            if($('input[value="'+data.idProduct+'"]').length == 0)
                            {
                                let price = data.price;
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
                                var html = "<tr class='text-gray-700'>";
                                html += "<td class='px-4 py-3'><input type='hidden' name='refs[]' value='"+data.idProduct+"'>"+data.idProduct+"<input class='price' type='hidden' name='price[]' value='"+data.price+"' readonly><input class='price_base' type='hidden' name='price_base[]' value='"+data.price_base+"' readonly><input class='price_scale' type='hidden' name='price_scale[]' value='"+data.price_scale+"' readonly><input class='price_dist' type='hidden' name='price_dist[]' value='"+data.price_dist+"' readonly></td>";
                                html += "<td class='px-4 py-3 text-xs whitespace-normal'>"+data.description+"</td>";
                                html += "<td class='px-4 py-3'><input class='stock w-full' type='text' name='stock[]' value='"+data.stock+"' readonly></td>";
                                html += "<td class='px-4 py-3'><input class='form-input budget-rates' type='number' min='1' name='budget-rates[]' value='"+price+"'></td>";
                                html += "<td class='px-4 py-3'><input class='form-input budget-quantities' type='number' min='1' name='budget-quantities[]' value='1'></td>";
                                html += "<td class='px-4 py-3'><input class='form-input budget-subtotal' type='text' name='budget-subtotal[]' value='"+price+"' readonly></td>";
                                html += "<td class='px-4 py-3'><button type='button' class='button-main btn-base-price-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg></button>";
                                html += "<button type='button' class='button-main btn-remove-budget-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg></button>";
                                html += "</td>";
                                html += "</tr>";
                                $("#tborders").prepend(html);
                                $('#btn-agregar-budget').val(null);
                                $( "#budgets-product" ).val(null);
                                window.calcTotal();
                                if(Number(price) < Number(data.price_base))
                                {
                                    showModal("El precio ingresado es menor que el precio base");
                                    //document.querySelector('.modal-body').innerHTML = "El precio ingresado es menor que el precio base";
                                    //toggleModal();
                                }
                            }else
                            {
                                showModal("Este producto ya ha sido agregado");
                            }
                        }
                    });
              }else{
                showModal("Por favor seleccione un producto");
                //alert("Por favor seleccione un producto");
              }
        }
    });

    $(document).on("input","#tborders input.budget-quantities", function(){
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

      $(this).closest("tr").find(".budget-subtotal").val((Number($(this).val())*Number($(this).closest("tr").find(".budget-rates").val())));
      window.calcTotal();

    });

    $(document).on("focusout","#tborders input.budget-rates", function(){
    //$(".trfr-quantities").change(function() {
    //$('.trfr-quantities').on('input',function(e){
      /*if (/\D/g.test($(this).val()))
      {
        // Filter non-digits from input value.
        $(this).val($(this).val().replace(/\D/g, ''));
      }*/
      //console.log($(this).closest("tr").find(".price_base"));
      let price_base = $(this).closest("tr").find(".price_base").val();
      if(Number($(this).val()) < Number(price_base))
      {
          showModal("El precio ingresado es menor que el precio base");
          /*document.querySelector('.modal-title').innerHTML = "Advertencia";
          document.querySelector('.modal-body').innerHTML = "El precio ingresado es menor que el precio base";
          document.querySelector('.modal-close').innerHTML = "Aceptar";
          toggleModal();*/
      }

    });

    $(document).on("change","#tborders input.budget-rates", function(){

        $(this).closest("tr").find(".budget-subtotal").val((Number($(this).val())*Number($(this).closest("tr").find(".budget-quantities").val())));
        window.calcTotal();
    });

    $('#budget-vendor').change(function() {
        //document.querySelector('.modal-body').innerHTML = "Sisas por eso";
        //toggleModal();
        changeVendorClients($('#budget-vendor').val());
    });
    if($('#budget-vendor').length) changeVendorClients($('#budget-vendor').val());

    $("#budget-client").change(function() {
        //document.querySelector('.modal-body').innerHTML = "Sisas por eso";
        //toggleModal();
        //changeVendorClients($('#budget-vendor').val());
        changeClientRate($('#budget-client').val());
        
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
        window.calcTotal();
    });
    $('#budget-store').change(function() {
        $("#tborders").html('');
        window.calcTotal();
    });
     $('#budget-tax').change(function() {
        if($(this).is(':checked'))
        {
            $("#budget-tax-value").show();

        }else
        {
            $("#budget-tax-value").hide();

        }
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

     window.calcTotal();
    /******************* End Budgets ***************/

    /******************* Invoices ******************/
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
    /******************* End Invoices ***************/

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
};

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

function changeVendorClients(vendor){
      $.ajax({
        url: window.base_url+"sisvent/commercial/budgets/getVendorClients",
        type:"POST",
        dataType:"json",
        data:{vendor: vendor},
        success:function(data){
          var html = "";
          for (var i = 0; i < data.length; i++) {
            html += "<option value='"+data[i].idClient+"'>"+data[i].name+"</option>";
          }
          $("#budget-client").html(html); 
          changeClientRate($('#budget-client').val());
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
            $("#budget-rate").val(data.rate);
        }
    });
}

window.calcTotal = function ()
{
    var total = 0;
    $("#tborders > tr").each(function () {
          //alert($(this).find('td').eq(0).text() + " " + $(this).find('td').eq(1).text() );
          //let price = 0;
          total += Number($(this).closest("tr").find(".budget-subtotal").val());  
          console.log(total+"  "+$(this).closest("tr").find(".budget-subtotal").val());    
    });
    $("#budget-total-val").val(total);
    $("#budget-total").val(total.toLocaleString('en-US'));
}



/*function changePrices()
{
    $("#tborders > tr").each(function () {
        //alert($(this).find('td').eq(0).text() + " " + $(this).find('td').eq(1).text() );
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