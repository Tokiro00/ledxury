<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $isSuperAdmin = $this->session->userdata('user_data')['uname'] == "00000";
?>
<!DOCTYPE html>
<html lang="en">
    <title>Clientes</title>
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
                        Agregar Cliente
                    </h2>
                    
                    <form action="<?php echo base_url();?>sisvent/business/clients/store" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        
                        <label class="block text-sm mt-4 <?php echo !empty(form_error('client_id')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Cédula/NIT</span>
                          <input class="form-input" type="text" name="client_id" value="<?php echo set_value('client_id');?>" required/>
                          <?php echo form_error("client_id","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('f_id')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Id Facutsol</span>
                          <input class="form-input" type="number" name="f_id" value="<?php echo set_value('f_id', $next_fid+1);?>"  <?php echo (in_array($role, [1]) && $isSuperAdmin) ? '' : 'readonly' ?>/>
                          <?php echo form_error("f_id","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Nombre</span>
                          <input class="form-input" type="text" name="name" value="<?php echo set_value('name');?>" required/>
                          <?php echo form_error("name","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('address')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Dirección</span>
                          <input class="form-input" type="text" minlength="15" name="address" value="<?php echo set_value('address');?>" required/>
                          <?php echo form_error("address","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('zone')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Zona</span>
                          <input class="form-input" type="text" name="zone" value="<?php echo set_value('zone');?>"/>
                          <?php echo form_error("zone","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('city')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Ciudad</span>
                          <input class="form-input" type="text" name="city" value="<?php echo set_value('city');?>" required/>
                          <?php echo form_error("city","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('state')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Departamento</span>
                          <input class="form-input" type="text" name="state" value="<?php echo set_value('state');?>" required/>
                          <?php echo form_error("state","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('phone')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Teléfono</span>
                          <input class="form-input" type="text" name="phone" value="<?php echo set_value('phone');?>" required/>
                          <?php echo form_error("phone","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('cellphone')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Celular</span>
                          <input class="form-input" type="text" name="cellphone" value="<?php echo set_value('cellphone');?>" />
                          <?php echo form_error("cellphone","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('email')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Email</span>
                          <input class="form-input" type="email" value="<?php echo set_value('email');?>" name="email" required/>
                          <?php echo form_error("email","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Tipo de Cliente
                          </span>
                          <select name="type" class="form-input form-select">
                              <option value="-" <?php echo set_select("type","-");?>>-</option>
                              <option value="A" <?php echo set_select("type","A");?>>A</option>
                              <option value="B" <?php echo set_select("type","B");?>>B</option>
                              <option value="C" <?php echo set_select("type","C");?>>C</option>
                              <option value="D" <?php echo set_select("type","D");?>>D</option>
                          </select>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Vendedor
                          </span>
                          <select name="vendor" class="form-input form-select">
                            <?php foreach($vendors as $vendor):?>
                                <option value="<?php echo $vendor->idUser?>" <?php echo set_select("vendor",$vendor->idUser,$vendor->idUser==$this->session->userdata('user_data')['uname']);?>><?php echo $vendor->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <?php if(in_array($role, [1])): ?>
                        <label class="block text-sm mt-4 <?php echo !empty(form_error('maximum_debt')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Deuda máxima</span>
                          <input class="form-input" type="number" name="maximum_debt" value="<?php echo set_value('maximum_debt');?>" />
                          <?php echo form_error("maximum_debt","<span class='text-xs text-red-600'>","</span>");?>
                        </label>
                        <?php endif; ?>

                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input type="checkbox" name="retail" class="text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark"/>
                          <span class="ml-2">Cliente al Detal</span>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Tarifa
                          </span>
                          <select name="rate" class="form-input form-select">
                              <option value="1" <?php echo set_select("rate",1);?>>Precio</option>
                              <option value="2" <?php echo set_select("rate",2);?>>Precio Base</option>
                              <option value="3" <?php echo set_select("rate",3);?>>Precio Escala</option>
                              <option value="4" <?php echo set_select("rate",4);?>>Precio Distribución</option>
                          </select>
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