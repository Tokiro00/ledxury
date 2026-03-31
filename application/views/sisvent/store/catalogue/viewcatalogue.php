

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$role = $this->session->userdata('user_data')['role'];
     $url_params = createFullParamsLinks($page);
     $last_fam = -1;
?>
<!DOCTYPE html>
<html lang="en">
    <title>Catálogo</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
    <style type="text/css">
      #productimg { min-height: 450px; height: 100%; }

      @media (max-width: 728px) {
          #productimg { min-height: 250px; height: 100%; }
      }
    </style>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <main class="container h-full m-auto">
          <div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-600 mt-2 text-center">
                        <?php echo $catalogue->cat_name; ?>
                    </h2>
                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        
                       <div class="flex-1"></div>
                      
                       <a href="<?php echo base_url();?>sisvent/store/catalogue/downloadcat/<?php echo $catalogue->idCatalogue ?>"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                          <span>Descargar</span>
                        </a>
                        
                        <a href="<?php echo base_url();?>sisvent/store/catalogue" class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                          <span>Volver</span>
                        </a>

                    </div>
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="container mx-auto px-1 md:px-6">
            <div class="grid gap-6 grid-cols-1 mt-6">
              <?php foreach($products as $key => $product):?>
                <?php if($last_fam != $product->family): 
                  $last_fam = $product->family; ?>
                  <div class="flex flex-col bg-black w-1/2">
                    <div class="px-6 pt-1 pb-1 m-auto font-medium text-3xl text-bold text-white whitespace-nowrap"><?php echo $product->family_name; ?></div>
                    <div class="w-10/12 bg-mam-red h-2 my-2"></div>
                  </div>
                <?php endif; ?>
                <div class="flex flex-col md:flex-row w-full mx-auto py-6 md:py-2 rounded-md shadow-md overflow-hidden">
                    <div id="productimg" class="w-full md:w-1/2 md:px-5 py-3 <?php if($key%2!=0) echo 'md:order-last'; ?>" >
                    <?php 
                      $imgurl = $product->picture_url;
                      if(($product->picture_url == 'products/no_image.png') && file_exists(('public/dist/images/products/'.$product->idProduct.'.jpg'))){
                        $imgurl = 'products/'.$product->idProduct.'.jpg';
                      }
                     ?>
                     <a href="<?php echo get_images_path($imgurl) ?>" data-fancybox data-caption="<?php echo $product->idProduct.' - '.$product->description;?>">
                      <div class="flex items-end justify-end h-full w-full bg-contain bg-no-repeat bg-center mt-2" style="background-image: url('<?php echo get_images_path($imgurl);  ?>')">
                      </div>
                    </a>
                    </div>
                    <div class="flex flex-col w-full md:w-1/2 md:px-5 py-3 content-center m-auto">

                        <div class="relative overflow-x-auto md:shadow-md sm:rounded-lg content-center">
                          <table class="w-full text-sm text-left text-gray-700 dark:text-gray-400">
                              <thead class="border-b bg-gray-800">
                                <tr class="bg-gray-50 p-4 text-center font-bold col-span-2" rowspan="2">
                                  <td  colspan="2" class="px-6 py-4 col-span-2 text-xl text-gray-400" >Ficha Técnica</td>
                                </tr>
                              </thead>
                              <tbody>
                                  <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                      <th scope="row" class="px-6 py-4 font-medium text-bold text-gray-900 dark:text-white whitespace-nowrap">
                                          Referencia
                                      </th>
                                      <td class="px-6 py-4">
                                          <?php echo $product->idProduct; ?>
                                      </td>
                                  </tr>
                                  <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                      <th scope="row" class="px-6 py-4 font-medium text-bold text-gray-900 dark:text-white whitespace-nowrap">
                                          Descripción
                                      </th>
                                      <td class="px-6 py-4">
                                          <?php echo $product->description; ?>
                                      </td>
                                  </tr>
                                  <?php foreach ($product->datasheetvalues as $key => $value): ?>
                                  <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                      <th scope="row" class="px-6 py-4 font-medium text-bold text-gray-900 dark:text-white whitespace-nowrap">
                                          <?php echo $value->label_name; ?>
                                      </th>
                                      <td class="px-6 py-4">
                                          <?php echo $value->value; ?>
                                      </td>
                                  </tr>
                                  <?php endforeach ?>
                                  <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                      <th scope="row" class="px-6 py-4 font-medium text-bold text-gray-900 dark:text-white whitespace-nowrap">
                                          Precio
                                      </th>
                                      <td class="px-6 py-4">
                                          <p class="font-bold text-green-900 mt-2">$<?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->cat_price != null ? ($catalogue->has_discount ? $catalogue->disc_mult*$product->cat_price  : $product->cat_price) : ($catalogue->has_discount ? $catalogue->disc_mult*$product->price_base : $product->price))), 2);?></p>
                                      </td>
                                  </tr>
                                  <tr class="bg-white dark:bg-gray-800">
                                      <th scope="row" class="px-6 py-4 font-medium text-bold text-gray-900 dark:text-white whitespace-nowrap">
                                          Cantidad
                                      </th>
                                      <td class="px-6 py-4">
                                          <p class="text-gray-500 mt-2 font-bold <?php echo ($product->stock >= 0 ? 'text-green-700' : 'text-orange-700') ?>">
                                          <?php 
                                          if($product->stock <= 0) 
                                            echo "Agotado"; 
                                          elseif ($product->stock == 1) 
                                            echo "1 Disponible";
                                          else 
                                            echo $product->stock." Disponibles";
                                             ?></p>
                                      </td>
                                  </tr>
                              </tbody>
                          </table>
                      </div>
                    </div>
                </div>
              <?php endforeach; ?>
            </div>
                
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