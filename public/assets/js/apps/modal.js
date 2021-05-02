window.closeModal = function() {
	console.log("closeModal");
	$("#accept_modal").val(null);
    $('#mymodal').toggleClass('hidden');
 };

 window.acceptModal = function() {
	var ruta = $("#accept_modal").val();
	console.log("acceptModal "+ruta);
    $("#accept_modal").prop('disabled', true);
    $.ajax({
        url: ruta,
        type:"POST",
        success:function(resp){
            $("#accept_modal").prop('disabled', false);
        	window.location.href = resp;
        }
    });
	
 };

 window.showSureModal = function(e,el,msg ="¿Está seguro que desea eliminar este elemento?") {
    e.preventDefault();
    var ruta = el.getAttribute("href");
    $('.m-body').html(msg);
    $("#accept_modal").val(ruta);
    $('#mymodal').toggleClass('hidden');
    //console.log(ruta);
 };
