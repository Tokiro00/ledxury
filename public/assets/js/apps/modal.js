window.closeModal = function() {
	console.log("closeModal");
	$("#accept_modal").val(null);
    $('#mymodal').toggleClass('hidden');
 };

 window.acceptModal = function() {
	var ruta = $("#accept_modal").val();
	console.log("acceptModal "+ruta);
    $.ajax({
        url: ruta,
        type:"POST",
        success:function(resp){
        	window.location.href = resp;
        }
    });
	
 };

 window.showSureModal = function(e,el) {
    e.preventDefault();
    var ruta = el.getAttribute("href");
    $("#accept_modal").val(ruta);
    $('#mymodal').toggleClass('hidden');
    //console.log(ruta);
 };