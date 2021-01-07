// Build CSS
import '../css/app.css'


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
                        html += "<td class='px-4 py-3 text-xs'>"+data.description+"</td>";
                        html += "<td class='px-4 py-3'><input class='form-input' type='text' id='quantity' name='quantity[]' value='1'></td>";
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
                                html += "<td class='px-4 py-3 text-xs'>"+data.description+"</td>";
                                html += "<td class='px-4 py-3'><input class='form-input' type='text' id='quantities' name='quantities[]' value='1'></td>";
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
    });
};