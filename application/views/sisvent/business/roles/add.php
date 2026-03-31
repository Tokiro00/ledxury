<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Nuevo Rol</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head></head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full">
    	 		<div class="px-4 py-4 w-full">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Nuevo Rol
                    </h2>

                    <form action="<?php echo base_url();?>sisvent/business/roles/store" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>

                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md max-w-2xl">

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Nombre del Rol</span>
                          <input class="form-input" type="text" name="name" value="<?php echo set_value('name');?>" required placeholder="Ej: Gerente, Contador, Vendedor"/>
                          <?php echo form_error("name","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('description')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Descripcion</span>
                          <input class="form-input" type="text" name="description" value="<?php echo set_value('description');?>" placeholder="Descripcion breve del rol"/>
                          <?php echo form_error("description","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('puc_code')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Codigo PUC (opcional)</span>
                          <input class="form-input" type="text" name="puc_code" value="<?php echo set_value('puc_code');?>" placeholder="Ej: 231001"/>
                          <span class="text-xs text-gray-500">Codigo de la cuenta contable PUC asociada a este rol</span>
                          <?php echo form_error("puc_code","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <div class="mt-6 flex items-center space-x-4">
                          <button type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 border border-transparent rounded-lg focus:outline-none" style="background:#2E7D91">
                            Guardar Rol
                          </button>
                          <a href="<?php echo base_url();?>sisvent/business/roles" class="px-4 py-2 text-sm font-medium leading-5 text-gray-700 transition-colors duration-150 border border-gray-300 rounded-lg hover:bg-gray-100 focus:outline-none">
                            Cancelar
                          </a>
                        </div>
                      </div>

                      <input type="hidden" name="csrf_token" value="<?php echo $this->session->userdata('csrf_token');?>" />
                    </form>
    	 		</div>
	        </main>
	      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>
