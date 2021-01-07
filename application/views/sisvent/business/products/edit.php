<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
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
                        Editar Producto
                    </h2>
                    
                    <form action="<?php echo base_url();?>sisvent/business/products/update" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center justify-between p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        <input class="form-input" type="hidden" name="id" value="<?php echo $product->idproduct;?>" readonly/>
                        
                        <label class="block text-sm mt-4 <?php echo !empty(form_error('product_id')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Cédula/NIT</span>
                          <input class="form-input" type="text" name="product_id" value="<?php echo $product->idNum;?>"/>
                          <?php echo form_error("product_id","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Nombre</span>
                          <input class="form-input" type="text" name="name" value="<?php echo !empty(form_error('name')) ? set_value('name') : $product->name;?>" required/>
                          <?php echo form_error("name","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('address')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Dirección</span>
                          <input class="form-input" type="text" name="address" value="<?php echo !empty(form_error('address')) ? set_value('address') : $product->address;?>"/>
                          <?php echo form_error("address","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('phone')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Teléfono</span>
                          <input class="form-input" type="text" name="phone" value="<?php echo !empty(form_error('phone')) ? set_value('phone') : $product->phone;?>"/>
                          <?php echo form_error("phone","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('email')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Email</span>
                          <input class="form-input" type="email" value="<?php echo !empty(form_error('email')) ? set_value('email') : $product->email;?>" name="email"/>
                          <?php echo form_error("email","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Vendedor
                          </span>
                          <select name="vendor" class="form-input form-select">
                            <?php if(form_error("vendor")!=false || set_value("vendor") != false): ?>
                                <?php foreach ($vendors as $vendor) : ?>
                                    <option value="<?php echo $vendor->idUser?>" <?php echo set_select("vendor",$product->vendor);?> ><?php echo $vendor->name;?></option>
                                <?php endforeach;?>
                            <?php else: ?>
                                <?php foreach ($vendors as $vendor) : ?>
                                    <option value="<?php echo $vendor->idUser;?>" <?php echo $vendor->idUser == $product->vendor ? 'selected':'';?>><?php echo $vendor->name; ?></option>
                                <?php endforeach;?>
                            <?php endif;?>    
                          </select>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Tarifa
                          </span>
                          <select name="rate" class="form-input form-select">
                              <option value="1" <?php echo set_select("rate",1,1 == $product->rate);?>>Precio</option>
                              <option value="2" <?php echo set_select("rate",2,2 == $product->rate);?>>Precio Base</option>
                              <option value="3" <?php echo set_select("rate",3,3 == $product->rate);?>>Precio Escala</option>
                              <option value="4" <?php echo set_select("rate",4,4 == $product->rate);?>>Precio Distribución</option>
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