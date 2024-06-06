<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Promociones</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>
<script>
  //window.inBudgets = true;
</script>
</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full">
    	 		<div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Nueva Promoción
                    </h2>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <?php //if(in_array($role, [1])): ?>
                            <a href="<?php echo base_url();?>sisvent/store/dropshipping"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                              <span>Volver</span>
                            </a>
                        <?php //endif; ?>
                    </div>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4 text-center">
                        <!--h3 class="mx-auto text-gray-700"><span class="font-bold">Advertencia: </span>Si cambia el <span class="font-bold text-gray-600">Almacén</span>, los productos que haya seleccionado se eliminarán</h3-->
                    </div>
                    
                    <form id="new-catalogue-form" action="<?php echo base_url();?>sisvent/store/dropshipping/store" method="POST" enctype="multipart/form-data">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        
                        <label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Nombre de la Promoción</span>
                          <input class="form-input" type="text" name="name" value="<?php echo set_value('name');?>" required/>
                          <?php echo form_error("name","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4">
                          <span class="text-gray-700" style="width: 20%;">Cantidad:</span>
                          <input id="promopack-quantity" class="form-input" type="number" name="promoquantity" value="<?php echo set_value('promoquantity');?>"/>
                        </label>

                        <!--label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Almacén
                          </span>
                          <select id="budget-store" name="store" class="form-input form-select">
                            <?php foreach($stores as $store):?>
                                <option value="<?php echo $store->idStore?>" <?php echo set_select("store",$store->idStore);?>><?php echo $store->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label-->

                        <label class="block text-sm mt-4">
                          <span class="text-gray-700" style="width: 20%;">Valor Promoción: $</span>
                          <input id="promopack-total" class="form-input" type="number" name="total" value="<?php echo set_value('total');?>"/>
                        </label>

                        <label class="block text-sm mt-4">
                          <span class="text-gray-700">Observaciones</span>
                          <textarea id="invoice-payment-comment" class="form-input" name="comments"><?php echo set_value('comments'); ?></textarea>
                        </label>

                        <label class="block my-4 text-sm">
                          <span class="text-gray-700">Producto</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="form-input-lg inline w-1/2" type="text" id="promopacks-product"/>
                            <button id="btn-agregar-promopacks" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="" />
                              <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                              <span class="inline pr-4">Agregar</span>
                            </button>
                          </div>
                        </label>


                        <div class="w-full overflow-hidden rounded-lg shadow-xs">
                          <div class="w-full overflow-x-auto">
                            <table class="stripped-table w-full whitespace-no-wrap mt-8 lg:mt-0">
                              <thead>
                                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                  <th class="px-4 py-3 hidden lg:table-cell">#</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Código</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Descripción</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Cantidad</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Precio</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Orden</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Acciones</th>
                                </tr>
                              </thead>
                              <tbody id="tborders" class="bg-white divide-y">
                                
                              </tbody>
                            </table>
                          </div>
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
                            <input id="create-promopack" type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark disabled:opacity-50" value="Guardar">
                        </div>
                      </div>
                    </form>

    	 		    </div>
	        </main>
	      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>