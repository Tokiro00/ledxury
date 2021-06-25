<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<div id="product-print">
	<hr class="my-6">
	<div class="w-full overflow-hidden rounded-lg shadow-xs">
      <div class="w-full overflow-x-auto">
        <table class="w-full whitespace-no-wrap">
          <thead>
            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
              <th class="px-4 py-3">Código</th>
              <th class="px-4 py-3">Descripción</th>
              <th class="px-4 py-3">Stock</th>
              <?php if(in_array($role, [1])): ?>
              <th class="px-4 py-3">Cant. Min</th>
              <!--th class="px-4 py-3">Costo</th-->
              <th class="px-4 py-3">Costo Pesos</th>
              <th class="px-4 py-3">Costo RMB</th>
              <?php endif; ?>
              <th class="px-4 py-3">Precio Base</th>
              <th class="px-4 py-3">Precio Dist</th>                    
              <th class="px-4 py-3">Precio Escala</th>
              <th class="px-4 py-3">Precio</th>
              <?php if(in_array($role, [1])): ?>
              <th class="px-4 py-3">Proveedor</th>
              <th class="px-4 py-3">Acciones</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody class="bg-white divide-y">
            <tr class="text-gray-700">
              <td class="px-4 py-3">
                <div class="flex items-center text-sm">
                  <div class="relative hidden w-8 h-8 mr-3 md:block">
                    <img class="object-cover w-full h-full" src="<?php echo get_images_path($product->picture_url) ?>" alt="" loading="lazy"/>
                    <div class="absolute inset-0 shadow-inner" aria-hidden="true"></div>
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
              <td class="px-4 py-3 text-sm">
                <?php echo $product->stock;?>
              </td>
              <?php if(in_array($role, [1])): ?>
              <td class="px-4 py-3 text-sm">
                <?php echo $product->min;?>
              </td>
              <!--td class="px-4 py-3 text-sm">
                <?php echo $product->cost;?>
              </td-->
              <td class="px-4 py-3 text-sm">
                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->cost_cop)), 2);//$product->cost_cop;?>
              </td>
              <td class="px-4 py-3 text-sm">
                ¥ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->cost_rmb)), 2);// $product->cost_rmb;?>
              </td>
              <?php endif; ?>
              <td class="px-4 py-3 text-sm">
                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price_base)), 2);// $product->price_base;?>
              </td>
              <td class="px-4 py-3 text-sm">
                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price_dist)), 2);// $product->price_dist;?>
              </td>
              <td class="px-4 py-3 text-sm">
                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price_scale)), 2);// $product->price_scale;?>
              </td>
              <td class="px-4 py-3 text-sm">
                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price)), 2);// $product->price;?>
              </td>
              <?php if(in_array($role, [1])): ?>
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
                  </div>
                </td>
              <?php endif; ?>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
	
</div>
