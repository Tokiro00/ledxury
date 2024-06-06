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
                        Editar Promoción
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
                    
                    <form id="new-catalogue-form" action="<?php echo base_url();?>sisvent/store/dropshipping/update" method="POST" enctype="multipart/form-data">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        <input class="form-input" type="hidden" name="idPromopack" value="<?php echo $promopack->idPromopack;?>" readonly/>
                        
                        <label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Nombre del catálogo</span>
                          <input class="form-input" type="text" name="name" value="<?php echo set_value('name', $promopack->name);?>" required/>
                          <?php echo form_error("name","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <!--label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Almacén
                          </span>
                          <select name="store" class="form-input form-select">
                            <?php foreach($stores as $store):?>
                                <option value="<?php echo $store->idStore?>" <?php echo set_select("store",$store->idStore,$store->idStore==$promopack->storeId);?>><?php echo $store->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label-->

                        <label id="promoquantity" class="block text-sm mt-4">
                          <span class="text-gray-700">Cantidad:</span>
                          <input class="form-input" type="number" name="promoquantity"  min="1" value="<?php echo !empty(form_error('promoquantity')) ? set_value('promoquantity') : $promopack->quantity;?>"/>
                          <?php echo form_error("promoquantity","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label id="total" class="block text-sm mt-4">
                          <span class="text-gray-700">Valor Promoción: $</span>
                          <input class="form-input" type="number" name="total"  min="1" value="<?php echo !empty(form_error('total')) ? set_value('total') : $promopack->total;?>"/>
                          <?php echo form_error("total","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4">
                          <span class="text-gray-700">Observaciones</span>
                          <textarea id="invoice-payment-comment" class="form-input" name="comments"><?php echo set_value('comments',$promopack->comments); ?></textarea>
                        </label>

                        <label class="block my-4 text-sm">
                          <span class="text-gray-700">Producto</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="form-input-lg inline w-1/2" type="text" id="promopacks-product"/>
                            <button id="btn-agregar-promopacks" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="" onclick=""/>
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
                                <?php foreach($details as $key => $detail):?>
                                  <tr class='text-gray-700 flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0'>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-xs whitespace-normal'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>#</span><?php echo ($key + 1); ?></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Código</span><input type='hidden' name='refs[]' value='<?php echo $detail->productId; ?>'><?php echo $detail->productId; ?></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-xs whitespace-normal'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Descripción</span><input type='hidden' name='desc[]' value='<?php echo $detail->description; ?>'><?php echo $detail->description; ?></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Cantidad</span><input class='quantity w-full' type='text' name='quantity[]' value='<?php echo $detail->quantity ?? 0; ?>' readonly></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Precio</span><input class='form-input prices' type='number' <?php if($role == 1) echo "min='1'"; else echo "min='".$detail->price_base."'"; ?> name='prices[]' value='<?php echo $detail->promo_price != null ? $detail->cat_price : $detail->price ?>'></td>
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

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">Foto</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="hidden" type="file" onchange="readURLAvatar(this);" name="imageAvatar" id="imageAvatar" accept="image/jpeg, image/png"/>
                            <input class="form-input" type="text" name="image_name" id="image_name" readonly/>
                            <input class="absolute inset-y-0 right-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-r-lg focus:outline-none" type="button" value="Buscar..." onclick="document.getElementById('imageAvatar').click();"/>
                          </div>
                          <span class="post-error text-xs text-red-600"></span>
                          <div class="avatar-image-preview"><img id="preview-avatar" src="<?php echo get_images_path($promopack->picture_url);  ?>"></div>
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