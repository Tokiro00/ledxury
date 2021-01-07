window.changeInventoryStore = function(base_url) {
    var store = $('#inv-store').children("option:selected").val();
    $.ajax({
        url: base_url+"sisvent/store/inventory/getStoreInventory",
        type:"POST",
        data:{store: store},
        success:function(data){
            $("#inventory-tbl").html(data);
        }
    });

    
 };

 window.editInventoryStore = function(base_url) {
    var store = $('#inv-store').children("option:selected").val();
    window.location.href = base_url+"sisvent/store/inventory/edit/"+store;
 };

