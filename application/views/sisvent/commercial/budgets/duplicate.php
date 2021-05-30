<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    $url_params = $params;
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Presupuestos</title>
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
                        Nuevo Presupuesto
                    </h2>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <?php if(in_array($role, [1])): ?>
                            <a href="<?php echo base_url();?>sisvent/commercial/budgets"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                              <span>Volver</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4 text-center">
                        <!--h3 class="mx-auto text-gray-700"><span class="font-bold">Advertencia: </span>Si cambia el <span class="font-bold text-gray-600">Almacén</span>, los productos que haya seleccionado se eliminarán</h3-->
                    </div>
                    
                    <form id="new-budget-form" action="<?php echo base_url();?>sisvent/commercial/budgets/store<?php echo $url_params; ?>" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        

                         <div class="flex flex-row gap-4">
                          <div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
                            <span class="text-gray-700">
                              Cliente
                            </span>
                            <input class="form-input" type="text" id="budget-client" value="<?php echo $budget->client_name;?>"/>
                            <input class="form-input" name="client" id="budget-client-id" type="hidden" id="budget-client" value="<?php echo $budget->clientId;?>" readonly/>
                        </div>

                          <div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
                            <span class="text-gray-700">
                              Tarifa
                            </span>
                            <div class="flex flex-row gap-4">
                              <select id="budget-rate" name="rate" class="form-input form-select">
                                  <option value="1" <?php echo set_select("rate",1);?>>Precio</option>
                                  <option value="2" <?php echo set_select("rate",2);?>>Precio Base</option>
                                  <option value="3" <?php echo set_select("rate",3);?>>Precio Escala</option>
                                  <option value="4" <?php echo set_select("rate",4);?>>Precio Distribución</option>
                              </select>
                              <button id="change-price" class="flex items-center justify-between text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="Agregar" @click="changePrices()"/>
                                <span>Cambiar Tarifa</span>
                              </button>
                            </div>
                          </div>

                        </div>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Vendedor
                          </span>
                          <select id="budget-vendor" name="vendor" class="form-input form-select" required>
                            <?php foreach($vendors as $vendor): ?>
                                <option value="<?php echo $vendor->idUser?>" <?php echo set_select("vendor",$vendor->idUser,$vendor->idUser==$budget->vendorId);?>><?php echo $vendor->name;?></option>
                            <?php endforeach;?>
                          </select>
                          <!--input id="budget-vendor" class="form-input" type="hidden" name="vendor" value="<?php echo $budget->vendorId;?>" readonly/>
                          <input class="form-input" type="text" value="<?php echo $budget->vendor_name;?>" disabled/-->
                        </label>

                        
                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Almacén
                          </span>
                          <select name="store" class="form-input form-select">
                            <?php foreach($stores as $store):?>
                                <option value="<?php echo $store->idStore?>" <?php echo set_select("store",$store->idStore,$store->idStore==$budget->storeId);?>><?php echo $store->name;?></option>
                            <?php endforeach;?>
                          </select>
                          <!--input class="form-input" type="hidden" name="store" value="<?php echo $budget->storeId;?>" readonly/>
                          <input class="form-input" type="text" value="<?php echo $budget->store_name;?>" disabled/-->
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            IVA
                          </span>
                          <select id="hasiva-field" name="hasIva" class="form-input form-select" required>
                            <option value="0" <?php echo !$budget->hasIva ? 'selected' : '';?>>Remisión</option>
                            <option value="1" <?php echo $budget->hasIva ? 'selected' : '';?>>IVA</option>
                          </select>
                        </label>

                        <label class="block text-sm mt-4">
                          <span class="text-gray-700">Observaciones</span>
                          <textarea class="form-input" name="comments"><?php echo set_value('comments',$budget->comments); ?></textarea>
                        </label>

                        <label class="flex flex-row text-xl mt-4">
                          <span class="form-input nb font-bold w-16">Total $</span>
                          <input id="budget-total-val" class="form-input nb font-bold" type="hidden" name="total" value="<?php echo set_value('total');?>" readonly/>
                          <input id="budget-total" class="form-input nb font-bold" type="text" value="<?php echo set_value('total');?>" disabled/>
                        </label>

                        <label class="flex flex-row text-xl mt-4">
                          <span class="form-input nb font-bold w-36">Total Productos:</span>
                          <input id="budget-total-products" class="form-input nb font-bold" type="text" value="<?php echo sizeof($details);?>" disabled/>
                        </label>

                        <label class="block my-4 text-sm">
                          <span class="text-gray-700">Producto</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="form-input-lg inline w-1/2" type="text" id="budgets-product"/>
                            <input id="budget-quantities-ele" class='form-input-lg inline' type='number' placeholder="Cantidad" min='1' value='1'>
                            <input id="budget-price-ele" class='form-input-lg inline' type='number' placeholder="Precio" min='1' value=''>
                            <button id="btn-agregar-budget" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="" onclick=""/>
                              <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                              <span class="inline pr-4">Agregar</span>
                            </button>
                          </div>
                        </label>

                        <div class="w-full overflow-hidden rounded-lg shadow-xs">
                          <div class="w-full overflow-x-auto">
                            <table class="stripped-table w-full whitespace-no-wrap">
                              <thead>
                                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                  <th class="px-4 py-3 hidden sm:table-cell">#</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Código</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Descripción</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Stock</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Cantidad</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Precio</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Subtotal</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Acciones</th>
                                </tr>
                              </thead>
                              <tbody id="tborders" class="bg-white divide-y">
                                <?php foreach($details as $key => $detail):?>
                                    <tr class='text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 sm:mb-0'>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static text-xs'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">#</span><?php echo ($key + 1); ?></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Código</span><input type='hidden' name='refs[]' value='<?php echo $detail->productId; ?>'><?php echo $detail->productId; ?><input class='price' type='hidden' name='price[]' value='<?php echo $detail->price; ?>' readonly><input class='price_base' type='hidden' name='price_base[]' value='<?php echo $detail->price_base; ?>' readonly><input class='price_scale' type='hidden' name='price_scale[]' value='<?php echo $detail->price_scale; ?>' readonly><input class='price_dist' type='hidden' name='price_dist[]' value='<?php echo $detail->price_dist; ?>' readonly></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static text-xs'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Descripción</span><?php echo $detail->description; ?></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Stock</span><input class='stock w-full' type='text' name='stock[]' value='<?php echo $detail->stock ?? 0; ?>' readonly></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cantidad</span><input class='form-input budget-quantities' type='number' min='1' name='budget-quantities[]' value='<?php echo $detail->quantity; ?>'></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio</span><input class='form-input budget-rates' type='number' min='1' name='budget-rates[]' value='<?php echo $detail->unit; ?>'></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span><input class='form-input budget-subtotal' type='text' name='budget-subtotal[]' value='<?php echo $detail->subtotal; ?>' readonly></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Acciones</span><button type='button' class='button-main btn-base-price-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg></button><button type='button' class='button-main btn-remove-budget-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg></button></td>
                                    </tr>
                                <?php endforeach;?>
                              </tbody>
                            </table>
                          </div>
                        </div>

                        <div class="block text-sm mt-4">
                            <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" value="Guardar">
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