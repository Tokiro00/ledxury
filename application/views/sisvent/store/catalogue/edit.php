<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Catálogos</title>
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
                        Editar Catálogo
                    </h2>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <?php //if(in_array($role, [1])): ?>
                            <a href="<?php echo base_url();?>sisvent/store/catalogue"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                              <span>Volver</span>
                            </a>
                        <?php //endif; ?>
                    </div>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4 text-center">
                        <!--h3 class="mx-auto text-gray-700"><span class="font-bold">Advertencia: </span>Si cambia el <span class="font-bold text-gray-600">Almacén</span>, los productos que haya seleccionado se eliminarán</h3-->
                    </div>
                    
                    <form id="new-catalogue-form" action="<?php echo base_url();?>sisvent/store/catalogue/update" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        <input class="form-input" type="hidden" name="idCatalogue" value="<?php echo $catalogue->idCatalogue;?>" readonly/>
                        
                        <label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Nombre del catálogo</span>
                          <input class="form-input" type="text" name="name" value="<?php echo set_value('name', $catalogue->cat_name);?>" required/>
                          <?php echo form_error("name","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <div class="grid grid-cols-12 gap-4">

                          <div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
                            <span class="text-gray-700">
                              Cliente
                            </span>
                            <input class="form-input" type="text" id="budget-client" value="<?php echo $catalogue->client_name;?>"/>
                            <input class="form-input" name="client" id="budget-client-id" type="hidden" id="budget-client" value="<?php echo $catalogue->clientId;?>" readonly/>
                          </div>

                          <!--div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
                            <span class="text-gray-700">
                             
                            </span>
                            <button id="change-price" class="flex items-center justify-between text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-r-lg focus:outline-none" type="button" value="Agregar" onclick=""/>
                              <span>Cambiar Precio</span>
                            </button>
                          </div-->
                        </div>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Vendedor
                          </span>
                          <select id="budget-vendor" name="vendor" class="form-input form-select" required>
                            <?php foreach($vendors as $vendor): ?>
                                <option value="<?php echo $vendor->idUser?>" <?php echo set_select("vendor",$vendor->idUser,$vendor->idUser==$catalogue->vendorId);?>><?php echo $vendor->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Almacén
                          </span>
                          <select name="store" class="form-input form-select">
                            <?php foreach($stores as $store):?>
                                <option value="<?php echo $store->idStore?>" <?php echo set_select("store",$store->idStore,$store->idStore==$catalogue->storeId);?>><?php echo $store->name;?></option>
                            <?php endforeach;?>
                          </select>
                          <!--input class="form-input" type="hidden" name="store" value="<?php echo $budget->storeId;?>" readonly/>
                          <input class="form-input" type="text" value="<?php echo $budget->store_name;?>" disabled/-->
                        </label>

                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input id="has_discount" type="checkbox" name="has_discount" class="text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" <?php echo $catalogue->has_discount ? 'checked':''; ?> />
                          <span class="ml-2">Multiplicar precio</span>
                        </label>

                        <label id="disc_mult" class="block text-sm mt-4 <?php echo $catalogue->has_discount ? '':'hidden';?>">
                          <span class="text-gray-700">Multiplicador</span>
                          <input class="form-input" type="number" name="disc_mult"  min="0" max="2" step="0.00001" value="<?php echo !empty(form_error('disc_mult')) ? set_value('disc_mult') : $catalogue->disc_mult;?>"/>
                          <?php echo form_error("disc_mult","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4">
                          <span class="text-gray-700">Observaciones</span>
                          <textarea id="invoice-payment-comment" class="form-input" name="comments"><?php echo set_value('comments',$catalogue->comments); ?></textarea>
                        </label>

                        <label class="block my-4 text-sm">
                          <span class="text-gray-700">Producto</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="form-input-lg inline w-1/2" type="text" id="catalogues-product"/>
                            <button id="btn-agregar-catalogues" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="" onclick=""/>
                              <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                              <span class="inline pr-4">Agregar</span>
                            </button>
                          </div>
                        </label>

                        <!--label class="block my-4 text-sm">
                          <span class="text-gray-700">Sección de familia</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="form-input-lg inline w-1/2" type="text" id="catalogues-section"/>
                            <button id="btn-agregar-catalogues-sect" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="" onclick=""/>
                              <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                              <span class="inline pr-4">Agregar</span>
                            </button>
                          </div>
                        </label-->

                        <label class="block my-4 text-sm">
                          <span class="text-gray-700">Familia</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <!--input class="form-input-lg inline w-1/2" type="text" id="catalogues-family"/-->
                            <select id="catalogues-family" name="family" class="form-input-lg inline w-1/2 form-select">
                              <?php foreach($families as $family):?>
                                  <option value="<?php echo $family->idFamily?>" <?php echo set_select("family",$family->idFamily);?>><?php echo $family->name;?></option>
                              <?php endforeach;?>
                            </select>
                            <button id="btn-agregar-catalogues-fam" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="" onclick=""/>
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
                                  <th class="px-4 py-3 hidden lg:table-cell">Stock</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Precio</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Orden</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Acciones</th>
                                </tr>
                              </thead>
                              <tbody id="tborders" class="bg-white divide-y">
                                <?php foreach($details as $key => $detail):?>
                                  <tr class='text-gray-700 flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0'>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-xs whitespace-normal'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>#</span><?php echo ($key + 1); ?></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Código</span><input type='hidden' name='refs[]' value='<?php echo $detail->productId; ?>'><?php echo $detail->productId; ?></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-xs whitespace-normal'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Descripción</span><input type='hidden' name='desc[]' value='<?php echo $detail->description; ?>'><?php echo $detail->description; ?></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Stock</span><input class='stock w-full' type='text' name='stock[]' value='<?php echo $detail->stock ?? 0; ?>' readonly></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Precio</span><input class='form-input prices' type='number' <?php if($role == 1) echo "min='1'"; else echo "min='".$detail->price_base."'"; ?> name='prices[]' value='<?php echo $detail->cat_price != null ? $detail->cat_price : $detail->price_base ?>'></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Orden</span><input class='form-input display_order' type='number' name='display_order[]' value='<?php echo $detail->display_order; ?>'></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Acciones</span>
                                    <button type='button' class='button-main btn-remove-budget-product'><p class='tooltip'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg><span class='tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded text-mam-blue-dark'>Eliminar</span></p></button>
                                    </td>
                                  </tr>
                                <?php endforeach;?>
                              </tbody>
                            </table>
                          </div>
                        </div>

                        <div class="block text-sm mt-4">
                            <input id="create-budget" type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark disabled:opacity-50" value="Guardar">
                            <button id="btn-unblock-budget" type='button' class='button-main' style="display: none;"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 015.905-.75 1 1 0 001.937-.5A5.002 5.002 0 0010 2z" /></svg></button>
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