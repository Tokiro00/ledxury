<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Usuarios</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full">
    	 		<div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Agregar Usuario
                    </h2>
                    
                    <form action="<?php echo base_url();?>sisvent/business/users/store" method="POST" enctype="multipart/form-data">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        
                        <label class="block text-sm mt-4 <?php echo !empty(form_error('user_id')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Identificación</span>
                          <input class="form-input" type="text" name="user_id" value="<?php echo set_value('user_id');?>" required/>
                          <?php echo form_error("user_id","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('f_id')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Id Factusol</span>
                          <input class="form-input" type="number" name="f_id" value="<?php echo set_value('f_id');?>"/>
                          <?php echo form_error("f_id","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Nombre</span>
                          <input class="form-input" type="text" name="name" value="<?php echo set_value('name');?>" required/>
                          <?php echo form_error("name","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('address')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Dirección</span>
                          <input class="form-input" type="text" name="address" value="<?php echo set_value('address');?>" required/>
                          <?php echo form_error("address","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('phone')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Teléfono</span>
                          <input class="form-input" type="text" name="phone" value="<?php echo set_value('phone');?>" required/>
                          <?php echo form_error("phone","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('email')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Email</span>
                          <input class="form-input" type="email" value="<?php echo set_value('email');?>" name="email"/>
                          <?php echo form_error("email","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Rol
                          </span>
                          <select id="user-role" name="role" class="form-input form-select">
                            <?php foreach($roles as $role):?>
                                <option value="<?php echo $role->idRoles?>" data-puc="<?php echo $role->puc_code; ?>" <?php echo set_select("role",$role->idRoles,$role->idRoles==2);?>><?php echo $role->description;?></option>
                            <?php endforeach;?>
                          </select>
                          <p id="role-puc-info" class="text-xs text-gray-500 mt-1"></p>
                        </label>

                        <label id="admin-stores" class="block mt-4 text-sm <?php echo !empty(form_error('admin_store')) ? 'border-red-600':'';?>" style="<?php if(!in_array((int)set_value('role'), [1, 2, 4], true)): ?>display: none; <?php endif; ?>">
                          <span class="text-gray-700">
                            Administrador de la bodega
                          </span>
                          <div class="flex flex-wrap gap-10 py-4">
                            <?php foreach($stores as $store):?>
                              <div class="flex flex-row gap-2"><input type="checkbox" id="admin-store-<?php echo $store->idStore?>" name="admin_store[]" <?php echo set_checkbox('admin_store[]', $store->idStore); ?> value="<?php echo $store->idStore?>"><?php echo $store->name;?></div>
                            <?php endforeach;?>
                          </div>
                          <?php echo form_error("admin_store","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('password')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Contraseña</span>
                          <input class="form-input" type="password" name="password"value="<?php echo set_value('password');?>" required/>
                          <?php echo form_error("password","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('passconf')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Confirmar Contraseña</span>
                          <input class="form-input" type="password" name="passconf" value="<?php echo set_value('passconf');?>" required/>
                          <?php echo form_error("passconf","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">Foto</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="hidden" type="file" onchange="readURLAvatar(this);" name="imageAvatar" id="imageAvatar" accept="image/jpeg, image/png"/>
                            <input class="form-input" type="text" name="image_name" id="image_name" readonly/>
                            <input class="absolute inset-y-0 right-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-r-lg focus:outline-none" type="button" value="Buscar..." onclick="document.getElementById('imageAvatar').click();"/>
                          </div>
                          <span class="post-error text-xs text-red-600"></span>
                          <div class="avatar-image-preview" style="display: none"><img id="preview-avatar" src=""></div>
                        </label>

                        <div class="block text-sm mt-4">
                            <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo" value="Guardar">
                        </div>
                      </div>
                    </form>

    	 		    </div>
	        </main>
	      </div>
    </div>
  </body>
</html>