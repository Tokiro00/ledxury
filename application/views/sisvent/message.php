<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->budgetdata('budget_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));

?>
<!DOCTYPE html>
<html lang="en">
    <title>Mensajes</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>
  <script>
  window.inMessages = true;
</script>
</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php //$this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php //$this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full">
    	 		<div class="mx-auto grid">
           <section id="main" class="bg-dark">
    <div id="chat_user_list">
      <div id="owner_profile_details">
        <div id="owner_avtar" style="background-image: url('<?php echo get_images_path($user->picture_url);?>'); background-size: 100% 100%">
          <div>
            <div id="online"></div>
          </div>
        </div>
        <div id="owner_profile_text" class="">
          <h6 id="owner_profile_name" class="m-0 p-0"><?php echo $user->name;?></h6>
          <div id="bio">
            <p id="owner_profile_bio" class="m-0 p-0"></p>
            <i class="fas fa-edit" id="edit_icon"></i>
          </div>
          <a class="text-decoration-none" href="<?= base_url('sisvent/dashboard') ?>" style="color:#4487A0;"><i class="fa fa-arrow-left"></i> Volver</a>
        </div>
      </div>
      <div id="search_box_container" class="py-3">
        <input type="text" name="txt_search" class="form-control" autocomplete="off" placeholder="Buscar Usuario" id="search">
      </div>
      <hr/>
      <div id="user_list" class="py-3">
      </div>
    </div>
    <div id="chatbox">
      <div id="data_container" class="">
        <div id="bg_image"></div>
        <h2 class="mt-0"></h2>
        <h2>Mensajes</h2>
        <p class="text-center my-2">Conecta tu dispositivo a Internet. Recuerda que <br> debes teber una Conexión a Internet estable<br>.</p>
      </div>
      <div class="chatting_section" id="chat_area" style="display: none">
        <div id="header" class="py-2">
          <div id="name_details" class="pt-2">
            <div id="chat_profile_image" class="mx-2" style="background-size: 100% 100%">
              <div id="online"></div>
            </div>
            <div id="name_last_seen">
              <h6 class="m-0 pt-2"></h6>
              <p class="m-0 py-1"></p>
            </div>
          </div>
          <div id="icons" class="px-4 pt-2">
            <div id="send_mail">
              <a href="" id="mail_link"><i class="fas fa-envelope text-dark"></i></a>
            </div>
            <div id="details_btn" class="ml-3">
              <i class="fas fa-info-circle text-dark"></i>
            </div>
          </div>
        </div>
        <div id="chat_message_area">

        </div>
        <div id="messageBar" class="py-4 px-4">
          <div id="textBox_attachment_emoji_container">
            <div id="text_box_message">
              <input type="text" maxlength = "200" name="txt_message" id="messageText" class="form-control" placeholder="Escribe tu mensaje">
            </div>
            <div id="text_counter">
              <p id="count_text" class="m-0 p-0"></p>
            </div>
          </div>
          <div id="sendButtonContainer">
            <button class="btn" id="send_message">
              <span class="material-icons">Enviar</span>
            </button>
          </div>
        </div>
      </div>
    </div>
    
  </section>         
                    
                    
    	 		</div>
	        </main>
	      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.6.1/gsap.min.js"></script>
    <script type="text/javascript" src="<?php echo get_public_path('message.js') ?>"></script>
  </body>
</html>