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
                        Agregar Vendedor
                    </h2>
                    
                    <form action="<?php echo base_url();?>sisvent/business/vendors/store" method="POST" enctype="multipart/form-data">
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

                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input id="e_commerce" type="checkbox" name="e_commerce" class="text-mam-blue-petroleo form-checkbox focus:border-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo"/>
                          <span class="ml-2">Venta por E-commerce</span>
                        </label>

                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input id="by_commission" type="checkbox" name="by_commission" class="text-mam-blue-petroleo form-checkbox focus:border-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo"/>
                          <span class="ml-2">Solo por comisión</span>
                        </label>

                        <label id="commission_perc" class="block text-sm mt-4 hidden">
                          <span class="text-gray-700">Porcentaje de comisión</span>
                          <input class="form-input" type="number" name="commission_perc"  min="1" max="100" value="10"/>
                        </label>
                        
                        <label class="flex items-center mt-4 dark:text-gray-400" title="Si está activo y el vendedor vende un producto bajo el precio mínimo, la comisión de esa factura cae al 5% (en vez del % configurado arriba). Si está inactivo, se paga siempre el % configurado.">
                          <input id="apply_underprice_penalty_5pct" type="checkbox" name="apply_underprice_penalty_5pct" class="text-mam-blue-petroleo form-checkbox focus:border-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo"/>
                          <span class="ml-2">Castigar venta bajo precio mínimo (baja comisión a 5%)</span>
                        </label>
                        
                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Almacén
                          </span>
                          <select name="store" class="form-input form-select">
                            <?php foreach($stores as $store):?>
                                <option value="<?php echo $store->idStore?>" <?php echo set_select("store",$store->idStore);?>><?php echo $store->name;?></option>
                            <?php endforeach;?>
                          </select>
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