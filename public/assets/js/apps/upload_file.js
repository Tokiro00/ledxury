
window.readURLAvatar = function(input) {
    $(".post-error").html(null);
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
        $(".avatar-image-preview").css("display","block");    
            $('#preview-avatar').attr('src', e.target.result);
        };
        $("#image_name").val(input.files[0].name);
        reader.readAsDataURL(input.files[0]);
    }
  };

