

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    $role = $this->session->userdata('user_data')['role'];
     $url_params = createFullParamsLinks($page);
?>
<!DOCTYPE html>
<html lang="en">
    <title>Catálogo</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <main class="h-full m-auto">
          <div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-600 mt-2 text-center">
                        Catálogo <?php echo $store->name; ?>
                    </h2>
                       
                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                       <div class="flex-1"></div>
                        <a href="<?php echo base_url();?>sisvent/store/catalogue/download/<?php echo $store->idStore ?>"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                          <span>Descargar</span>
                        </a>
                        <a href="<?php echo base_url();?>sisvent/store/catalogue"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                          <span>Volver</span>
                        </a>
                    </div>
                        <div class="w-full overflow-hidden rounded-lg shadow-xs">
              <div class="w-full overflow-x-auto overflow-y-hidden">
                <table class="w-full whitespace-no-wrap">
                  <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                      <th class="px-4 py-3">Código</th>
                      <th class="px-4 py-3">Imagen</th>
                      <th class="px-4 py-3">Descripción</th>
                      <th class="px-4 py-3">Precio</th>
                      <th class="px-4 py-3">U. Disponibles</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y">
                    <?php if(!empty($products)):?>
                        <?php foreach($products as $product):?>
                            <tr class="text-gray-700">
                              <td class="px-4 py-3">
                                  <p class="font-semibold"><?php echo $product->idProduct;?></p>
                              </td>
                              <td class="px-4 py-3">
                                <div class="flex items-center text-sm">
                                  <?php 
                                    $imgurl = $product->picture_url;
                                    if(($product->picture_url == 'products/no_image.png') && file_exists(('public/dist/images/products/'.$product->idProduct.'.jpg'))){
                                      $imgurl = 'products/'.$product->idProduct.'.jpg';
                                    }
                                     ?>
                                    <a href="<?php echo get_images_path($imgurl) ?>" data-fancybox data-caption="<?php echo $product->idProduct.' - '.$product->description;?>">
                                  <div class="relative hidden w-full h-48 mr-3 md:block">
                                    

                                      <img class="object-contain w-full h-full" src="<?php echo get_images_path($imgurl) ?>" alt="" loading="lazy"/>
                                    <div class="absolute inset-0 shadow-inner" aria-hidden="true"></div>
                                  </div>
                                    </a>
                                </div>
                              </td>
                              <td class="px-4 py-3 text-xs whitespace-normal">
                                <?php echo $product->description;?>
                              </td>
                              <td class="font-semibold px-4 py-3 text-lg">
                                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price)), 2);// $product->price;?>
                              </td>
                              <td class="px-4 py-3 text-lg text-center whitespace-normal">
                                <p class="text-gray-500 mt-2 font-bold <?php echo ($product->stock >= 0 ? 'text-green-700' : 'text-orange-700') ?>">
                                <?php 
                                if($product->stock <= 0) 
                                  echo "Agotado"; 
                                elseif ($product->stock == 1) 
                                  echo "1";
                                else 
                                  echo $product->stock;
                                   ?></p>
                              </td>
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
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>