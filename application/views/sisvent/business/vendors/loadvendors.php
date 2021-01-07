<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Vendedores</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto">
    	 		<div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Cargar Vendedores
                    </h2>
                    
                    <form action="<?php echo base_url();?>sisvent/business/vendors/upload" method="POST" enctype="multipart/form-data">
                      <?php if(!empty($error_msg)):?>
                          <div class="flex items-center justify-between p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $error_msg; ?></p>
                           </div>
                      <?php endif;?>
                      <?php if(!empty($success_msg)){ ?>
                          <div class="flex items-center justify-between p-4 mb-8 text-sm font-semibold text-white bg-green-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $success_msg; ?></p>
                           </div>
                      <?php } ?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">CVS</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="" type="file" name="userfile" id="userfile"/>
                            <!--input class="form-input" type="text" name="image_name" id="image_name" readonly/>
                            <input class="absolute inset-y-0 right-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-r-lg focus:outline-none" type="button" value="Buscar..." onclick="document.getElementById('importSubmit').click();"/-->
                          </div>
                        </label>

                        <div class="block text-sm mt-4">
                            <input type="submit" name="importSubmit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" value="Cargar">
                        </div>
                      </div>
                      <?php if(!empty($info_msg)){ ?>
                          <div class="flex items-center justify-between p-4 mb-8 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $info_msg; ?></p>
                           </div>
                      <?php } ?>
                    </form>

    	 		    </div>
	        </main>
	      </div>
    </div>
  </body>
</html>