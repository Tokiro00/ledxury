<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $partner = checkHasPartnerPrivileges();
?>
<!DOCTYPE html>
<html lang="en">
    <title>Productos</title>
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
                        Agregar Producto
                    </h2>
                    
                    <form action="<?php echo base_url();?>sisvent/business/products/store" method="POST" enctype="multipart/form-data">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        
                        <label class="block text-sm mt-4 <?php echo !empty(form_error('product_id')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Código</span>
                          <input class="form-input" type="text" name="product_id" maxlength="13" value="<?php echo set_value('product_id');?>" required/>
                          <?php echo form_error("product_id","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('description')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Descripción</span>
                          <input class="form-input" type="text" name="description" value="<?php echo set_value('description');?>" required/>
                          <?php echo form_error("description","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <?php if(in_array($role, [1])): ?>
                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input type="checkbox" name="not_settle" class="text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" />
                          <span class="ml-2"><b>No</b> liquidar producto</span>
                        </label>

                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input type="checkbox" name="is_national" class="text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" />
                          <span class="ml-2">Producto nacional</span>
                        </label>
                        <?php endif; ?>

                        <hr class="mt-8">
                         <!--label class="block text-sm mt-4 <?php echo !empty(form_error('cost')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Costo</span>
                          <input class="form-input" type="number" value="<?php echo set_value('cost',0);?>" name="cost"/>
                          <?php echo form_error("cost","<span class='text-xs text-red-600'>","</span>");?>
                        </label-->
                      <?php if($partner): ?>
                        <h4 class="mt-2 text-lg text-center mx-auto font-semibold text-gray-600">
                          Costos
                        </h4>

                        <label class="block text-sm mt-8 <?php echo !empty(form_error('cost_cop')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Costo Pesos</span>
                          <input class="form-input" type="number" step="0.01" value="<?php echo set_value('cost_cop',0);?>" name="cost_cop"/>
                          <?php echo form_error("cost_cop","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('cost_rmb')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Costo RMB</span>
                          <input class="form-input" type="number" step=".01" value="<?php echo set_value('cost_rmb',0);?>" name="cost_rmb"/>
                          <?php echo form_error("cost_rmb","<span class='text-xs text-red-600'>","</span>");?>
                        </label>
                      <?php endif; ?>
                        
                        <hr class="mt-8">
                        <h4 class="mt-2 text-center mx-auto text-lg font-semibold text-gray-600">
                          Precios
                        </h4>
                        
                        <label class="block text-sm mt-8 <?php echo !empty(form_error('price_base')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Precio Base</span>
                          <input class="form-input" type="number" step="0.01" name="price_base" value="<?php echo set_value('price_base',0);?>" />
                          <?php echo form_error("price_base","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <!--label class="block text-sm mt-4 <?php echo !empty(form_error('price_dist')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Precio Distribución</span>
                          <input class="form-input" type="number" value="<?php echo set_value('price_dist',0);?>" name="price_dist"/>
                          <?php echo form_error("price_dist","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('price_scale')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Precio Escala</span>
                          <input class="form-input" type="number" value="<?php echo set_value('price_scale',0);?>" name="price_scale"/>
                          <?php echo form_error("price_scale","<span class='text-xs text-red-600'>","</span>");?>
                        </label-->
                        
                        <label class="block text-sm mt-4 <?php echo !empty(form_error('price')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Precio</span>
                          <input class="form-input" type="number" step="0.01" name="price" value="<?php echo set_value('price',0);?>" />
                          <?php echo form_error("price","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <hr class="mt-8">
                        <label class="block text-sm mt-8 <?php echo !empty(form_error('min')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Cant. Mínima</span>
                          <input class="form-input" type="number" value="<?php echo set_value('min',0);?>" name="min"/>
                          <?php echo form_error("min","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Familia
                          </span>
                          <select name="family" class="form-input form-select">
                            <?php foreach($families as $family):?>
                                <option value="<?php echo $family->idFamily?>" <?php echo set_select("family",$family->idFamily);?>><?php echo $family->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Proveedor
                          </span>
                          <select name="provider" class="form-input form-select">
                              <?php foreach($providers as $provider):?>
                                <option value="<?php echo $provider->idProvider?>" <?php echo set_select("provider",$provider->idProvider);?>><?php echo $provider->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Ficha Técnica
                          </span>
                          <select id="product-datasheet" name="datasheet" class="form-input form-select">
                              <option value="" >Ninguna</option>
                            <?php foreach($datasheets as $datasheet):?>
                                <option value="<?php echo $datasheet->idDatasheet?>" <?php echo set_select("datasheet",$datasheet->idDatasheet);?>><?php echo $datasheet->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                         <div id="datasheets-elemets" class="block text-sm mt-4">
                          
                      </div>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">Foto</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="hidden" type="file" onchange="readURLAvatar(this);" name="imageAvatar" id="imageAvatar" accept="image/jpeg, image/png"/>
                            <input class="form-input" type="text" name="image_name" id="image_name" readonly/>
                            <input class="absolute inset-y-0 right-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-r-lg focus:outline-none" type="button" value="Buscar..." onclick="document.getElementById('imageAvatar').click();"/>
                          </div>
                          <span class="post-error text-xs text-red-600"></span>
                          <div class="avatar-image-preview" style="display: none"><img id="preview-avatar" src=""></div>
                        </label>
                        
                        <div class="block text-sm mt-4">
                            <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" value="Guardar">
                        </div>
                      </div>
                    </form>

    	 		    </div>
	        </main>
	      </div>
    </div>
  </body>
</html>