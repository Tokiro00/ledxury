<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Conteo</title>
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
                        Agregar Conteo
                    </h2>
                    
                    <form id="new-inventory-form" action="<?php echo base_url();?>sisvent/store/count/storeCount" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        
                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Usuario
                          </span>
                          <select name="user" class="form-input form-select" required>
                            <?php foreach($users as $user): ?>
                                <option value="<?php echo $user->idUser?>" <?php echo set_select("user",$user->idUser,$user->idUser == $this->session->userdata('user_data')['uname']);?>><?php echo $user->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <label class="block mt-4 text-sm">
                        <input class="form-input" type="hidden" name="store" value="<?php echo $store;?>" readonly/>
                          <span class="text-gray-700">
                            Almacén
                          </span>
                          <select class="form-input form-select" disabled>
                            <?php foreach($stores as $str):?>
                                <option value="<?php echo $str->idStore?>" <?php echo set_select("store",$str->idStore, $str->idStore == $store );?>><?php echo $str->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <label class="block text-sm mt-4">
                          <span class="text-gray-700">Observaciones</span>
                          <textarea id="invoice-payment-comment" class="form-input" name="comments"><?php echo set_value('comments'); ?></textarea>
                        </label>

                        <div class="w-full overflow-hidden rounded-lg shadow-xs mt-4">
                          <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                              <thead>
                                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                  <th class="px-4 py-3">Código</th>
                                  <th class="px-4 py-3">Descripción</th>
                                  <!--th class="px-4 py-3">Stock</th-->
                                  <th class="px-4 py-3">Conteo</th>
                                </tr>
                              </thead>
                              <tbody id="tborders" class="bg-white divide-y">
                                <?php if(!empty($products)):?>
                                  <?php foreach($products as $product):?>
                                      <tr class='text-gray-700'>
                                      <td class='px-4 py-3'><input type='hidden' name='refs[]' value='<?php echo $product->idProduct;?>'><?php echo $product->idProduct;?></td>
                                      <td class='px-4 py-3 text-xs'><?php echo $product->description;?></td>
                                      <!--td class='px-4 py-3'>
                                        <input class="form-input" type="hidden" name="stock[]" value="<?php echo $product->stock;?>" readonly/>
                                        <?php echo $product->stock;?>
                                      </td-->
                                      <td class='px-4 py-3'><input class="form-input" type="hidden" name="stock[]" value="<?php echo $product->stock;?>" readonly/><input class='form-input' type='text' id='quantities' name='quantities[]' value='0'></td>
                                      
                                      </tr>
                                  <?php endforeach;?>
                              <?php endif;?>
                              </tbody>
                            </table>
                          </div>
                        </div>
                                           
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