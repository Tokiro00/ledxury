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
              <?php endif; ?>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
	
</div>
