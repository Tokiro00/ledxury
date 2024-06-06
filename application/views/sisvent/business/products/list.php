<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $url_params = createFullParamsLinks($page);
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
    	 	<main class="h-full">
    	 		<div class="px-6 mx-auto grid">
            <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                Productos
            </h2>
            <?php if($this->session->flashdata("error")):?>
                <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <p><?php echo $this->session->flashdata("error"); ?></p>
                 </div>
            <?php endif;?>
            <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                <?php if(in_array($role, [1])): ?>
                    <a href="<?php echo base_url();?>sisvent/business/products/add" class="flex flex-grow-0 items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark m-0">
                        <span>Agregar Producto</span>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </a>
                    <a href="<?php echo base_url();?>sisvent/business/products/export"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                      <span>Excel</span>
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </a>
                    <div class="flex-1"></div>
                    <a href="<?php echo base_url();?>sisvent/business/products/viewfamilies"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue border border-transparent rounded-lg active:bg-mam-blue hover:bg-mam-blue focus:outline-none focus:shadow-outline-mam-blue">
                      <span>Ver Familias</span>
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </a>
                    <a href="<?php echo base_url();?>sisvent/business/products/addfamily"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue border border-transparent rounded-lg active:bg-mam-blue hover:bg-mam-blue focus:outline-none focus:shadow-outline-mam-blue">
                      <span>Agregar Familia</span>
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </a>
                    <?php if(strpos(uri_string(), 'search') !== false): ?>
                    <a href="<?php echo base_url();?>sisvent/business/products<?php echo $url_params ?>"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                      <span>Volver</span>
                    </a>
                    <?php endif; ?>
                    <label class="block my-4 text-sm">
                      <div class="relative text-gray-500 focus-within:text-purple-600">
                        <input class="form-input-lg inline w-1/2" data-params="<?php echo $url_params ?>" type="text" id="products-search" placeholder="Buscar producto"/>
                        <button id="btn-search-product" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="" onclick=""/>
                          <svg class="w-6 h-6 inline" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                          </svg>
                          <span class="inline pr-4">Buscar</span>
                        </button>
                      </div>
                    </label>
                <?php endif; ?>
            </div>
                <?php if(in_array($role, [1])): ?>
            <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                    
                    <div class="flex-1"></div>
                    <a href="<?php echo base_url();?>sisvent/business/products/viewdatasheets"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue border border-transparent rounded-lg active:bg-mam-blue hover:bg-mam-blue focus:outline-none focus:shadow-outline-mam-blue">
                      <span>Ver Fichas Técnicas</span>
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </a>
                    <a href="<?php echo base_url();?>sisvent/business/products/adddatasheet"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue border border-transparent rounded-lg active:bg-mam-blue hover:bg-mam-blue focus:outline-none focus:shadow-outline-mam-blue">
                      <span>Agregar Ficha Técnica</span>
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </a>
                    
            </div>
                <?php endif; ?>
            <div class="w-full overflow-hidden rounded-lg shadow-xs">
              <div class="w-full overflow-x-auto overflow-y-hidden">
                <table class="w-full whitespace-no-wrap">
                  <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                      <th class="px-4 py-3">Código</th>
                      <th class="px-4 py-3">Descripción</th>
                      <?php if(in_array($role, [1])): ?>
                      <th class="px-4 py-3">Ficha T.</th>
                      <!--th class="px-4 py-3">Costo</th-->
                      <th class="px-4 py-3">Fecha</th>
                      <?php if($partner): ?>
                      <th class="px-4 py-3">Costo Pesos</th>
                      <th class="px-4 py-3">Costo RMB</th>
                      <?php endif; ?>
                      <?php endif; ?>
                      <th class="px-4 py-3">Precio Base</th>
                      <!--th class="px-4 py-3">Precio Dist</th>                    
                      <th class="px-4 py-3">Precio Escala</th-->
                      <th class="px-4 py-3">Precio</th>
                      <?php if(in_array($role, [1])): ?>
                      <th class="px-4 py-3">Cant. Min</th>
                      <th class="px-4 py-3">Proveedor</th>
                      <th class="px-4 py-3">Acciones</th>
                      <?php endif; ?>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y">
                    <?php if(!empty($products)):?>
                        <?php foreach($products as $product):?>
                            <tr class="text-gray-700">
                              <td class="px-4 py-3">
                                <div class="flex items-center text-sm">
                                  <div class="relative hidden w-8 h-8 mr-3 md:block">
                                    <?php 
                                    $imgurl = $product->picture_url;
                                    if(($product->picture_url == 'products/no_image.png') && file_exists(('public/dist/images/products/'.$product->idProduct.'.jpg'))){
                                      $imgurl = 'products/'.$product->idProduct.'.jpg';
                                    }
                                     ?>
                                    <a href="<?php echo get_images_path($imgurl) ?>" data-fancybox data-caption="<?php echo $product->idProduct.' - '.$product->description;?>">
                                      <img class="object-cover w-full h-full" src="<?php echo get_images_path($imgurl) ?>" alt="" loading="lazy"/>
                                      <div class="absolute inset-0 shadow-inner" aria-hidden="true"></div>
                                    </a>
                                  </div>
                                    <div>
                                      <p class="font-semibold"><?php echo $product->idProduct;?></p>
                                      <p class="text-xs text-gray-600">
                                        <?php echo $product->family_name;?>
                                      </p>
                                    </div>
                                </div>
                              </td>
                              <td class="px-4 py-3 text-xs whitespace-normal">
                                <?php echo $product->description;?>
                              </td>
                              <?php if(in_array($role, [1])): ?>
                              <td class="px-4 py-3 text-sm">
                                <?php echo $product->datasheet_name;?>
                              </td>
                              <!--td class="px-4 py-3 text-sm">
                                <?php echo $product->cost;?>
                              </td-->
                              <td class="px-4 py-3 text-sm">
                                <?php echo date("d-m-Y H:m:s", strtotime($product->created_at));?>
                              </td>
                              <?php if($partner): ?>
                              <td class="px-4 py-3 text-sm">
                                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->cost_cop)), 2);//$product->cost_cop;?>
                              </td>
                              <td class="px-4 py-3 text-sm">
                                ¥ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->cost_rmb)), 2);// $product->cost_rmb;?>
                              </td>
                              <?php endif; ?>
                              <?php endif; ?>
                              <td class="px-4 py-3 text-sm">
                                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price_base)), 2);// $product->price_base;?>
                              </td>
                              <!--td class="px-4 py-3 text-sm">
                                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price_dist)), 2);// $product->price_dist;?>
                              </td>
                              <td class="px-4 py-3 text-sm">
                                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price_scale)), 2);// $product->price_scale;?>
                              </td-->
                              <td class="px-4 py-3 text-sm">
                                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price)), 2);// $product->price;?>
                              </td>
                              <?php if(in_array($role, [1])): ?>
                              <td class="px-4 py-3 text-sm">
                                <?php echo $product->min;?>
                              </td>
                              <td class="px-4 py-3 text-xs whitespace-normal">
                                <?php echo $product->provider_name;?>
                              </td>
                              <td class="px-4 py-3">
                                <div class="flex items-center space-x-4 text-sm">
                                  <a href="<?php echo base_url()?>sisvent/business/products/edit/<?php echo $product->idProduct.$url_params;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                    <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                      <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Editar</span></p>
                                  </a>
                                  <a href="<?php echo base_url()?>sisvent/business/products/duplicate/<?php echo $product->idProduct;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Duplicate">
                                    <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Duplicar</span></p>
                                  </a>
                                  <a href="<?php echo base_url()?>sisvent/business/products/delete/<?php echo $product->idProduct;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this)" aria-label="Delete">
                                    <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                      <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Eliminar</span></p>
                                  </a>
                                </div>
                              </td>
                              <?php endif; ?>
                            </tr>
                        <?php endforeach;?>
                    <?php endif;?>
                  </tbody>
                </table>
              </div>
              <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
                <span class="flex items-center col-span-3">
                  <?php  $last       = ceil( $total / $limit ); ?>
                  Mostrando <?php echo ((($page-1) * $limit)+1).'-'.(($last == $page) ? ($total) : ((($page-1) * $limit)+$limit)).' de '.($total) ?>
                </span>
                <span class="col-span-2"></span>
                <!-- Pagination -->
                <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
                  <nav aria-label="Table navigation">
                    <?php echo createLinks($page, $total, "", $limit) ?>
                  </nav>
                </span>
              </div>
            </div>
    	 		</div>
	        </main>
	      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>