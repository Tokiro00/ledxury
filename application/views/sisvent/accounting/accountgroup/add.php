<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Grupos</title>
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
                        Agregar Grupo
                    </h2>
                    
                    <?php if(!empty($classes)): ?>
                    <form action="<?php echo base_url();?>sisvent/accounting/accountgroup/store" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        
                         <label class="block text-sm mt-4 <?php echo !empty(form_error('group_id')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Id</span>
                          <input class="form-input" type="number" name="group_id" value="<?php echo set_value('group_id');?>" required/>
                          <?php echo form_error("group_id","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Clase
                          </span>
                          <select name="class_id" class="form-input form-select">
                            <?php foreach($classes as $class):?>
                                <option value="<?php echo $class->classID?>" <?php echo set_select("class_id",$class->classID);?>><?php echo $class->className;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Nombre</span>
                          <input class="form-input" type="text" name="name" value="<?php echo set_value('name');?>" required/>
                          <?php echo form_error("name","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('description')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Descripción</span>
                          <input class="form-input" type="text" name="description" value="<?php echo set_value('description');?>"/>
                          <?php echo form_error("description","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <div class="block text-sm mt-4">
                            <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" value="Guardar">
                        </div>
                      </div>
                    </form>
                    <?php else: ?>
                        <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Debes agregar una clase antes de agregar un grupo
                        </h2>
                       <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                              <a href="<?php echo base_url();?>sisvent/accounting/accountclass/add"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                                <span>Agregar Clase</span>
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                              </a>
                      </div>
                  <?php endif; ?>
    	 		    </div>
	        </main>
	      </div>
    </div>
  </body>
</html>