<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $partner = checkHasPartnerPrivileges();
?>
<div id="product-print">
	<hr class="my-6">
	<div class="w-full overflow-hidden rounded-lg shadow-xs">
      <div class="w-full overflow-x-auto">
        <table class="w-full whitespace-no-wrap">
          <thead>
            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
              <th class="px-4 py-3 hidden lg:table-cell">Código</th>
              <th class="px-4 py-3 hidden lg:table-cell">Descripción</th>
              <th class="px-4 py-3 hidden lg:table-cell">Stock</th>
              <?php if(in_array($role, [1])): ?>
              <th class="px-4 py-3 hidden lg:table-cell">Ficha T.</th>
              <!--th class="px-4 py-3 hidden lg:table-cell">Costo</th-->
              <?php if($partner): ?>
              <th class="px-4 py-3 hidden lg:table-cell">Costo Pesos</th>
              <th class="px-4 py-3 hidden lg:table-cell">Costo RMB</th>
              <?php endif; ?>
              <?php endif; ?>
              <th class="px-4 py-3 hidden lg:table-cell">Precio Base</th>
              <!--th class="px-4 py-3 hidden lg:table-cell">Precio Dist</th>                    
              <th class="px-4 py-3 hidden lg:table-cell">Precio Escala</th-->
              <th class="px-4 py-3 hidden lg:table-cell">Precio</th>
              <?php if(in_array($role, [1])): ?>
              <th class="px-4 py-3 hidden lg:table-cell">Cant. Min</th>
              <th class="px-4 py-3 hidden lg:table-cell">Proveedor</th>
              <th class="px-4 py-3 hidden lg:table-cell">Acciones</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody class="bg-white divide-y">
            <tr class="text-gray-700 flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0">
              <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Código</span>
                <div class="flex items-center text-sm">
                  <div class="relative w-8 h-8 mr-3 block">
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
              <td class="px-4 py-3 text-xs w-full lg:w-auto block lg:table-cell relative lg:static whitespace-normal">
                <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Descripción</span>
                <?php echo $product->description;?>
              </td>
              <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Stock</span>
                <?php echo $product->stock;?>
              </td>
              <?php if(in_array($role, [1])): ?>
              <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Ficha T.</span>
                <?php echo $product->datasheet_name;?>
              </td>
              <!--td class="px-4 py-3 text-sm">
                <?php echo $product->cost;?>
              </td-->
              <?php if($partner): ?>
              <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Costo Pesos</span>
                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->cost_cop)), 2);//$product->cost_cop;?>
              </td>
              <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Costo RMB</span>
                ¥ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->cost_rmb)), 2);// $product->cost_rmb;?>
              </td>
              <?php endif; ?>
              <?php endif; ?>
              <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio Base</span>
                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price_base)), 2);// $product->price_base;?>
              </td>
              <!--td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio Dist</span>
                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price_dist)), 2);// $product->price_dist;?>
              </td>
              <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio Escala</span>
                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price_scale)), 2);// $product->price_scale;?>
              </td-->
              <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio</span>
                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price)), 2);// $product->price;?>
              </td>
              <?php if(in_array($role, [1])): ?>
              <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cant. Min</span>
                <?php echo $product->min;?>
              </td>
              <td class="px-4 py-3 text-xs w-full lg:w-auto block lg:table-cell relative lg:static whitespace-normal">
                <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Proveedor</span>
                <?php echo $product->provider_name;?>
              </td>
              <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Acciones</span>
                  <div class="flex items-center space-x-4 text-sm">
                    <a href="<?php echo base_url()?>sisvent/business/products/edit/<?php echo $product->idProduct.$url_params;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                      <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Editar</span></p>
                    </a>
                    <a href="<?php echo base_url()?>sisvent/business/products/duplicate/<?php echo $product->idProduct;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Duplicate">
                      <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Duplicar</span></p>
                    </a>
                    <a href="<?php echo base_url()?>sisvent/commercial/invoices/searchbyproduct/<?php echo $product->idProduct;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Buscar Facturas">
                      <p class="tooltip"><svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Buscar Facturas</span></p>
                    </a>
                  </div>
                </td>
              <?php endif; ?>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
	
</div>
