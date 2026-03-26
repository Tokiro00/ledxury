<?php
defined('BASEPATH') OR exit('No direct script access allowed');

?>
<!DOCTYPE html>
<html lang="en">
    <title>Catálogo</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <main class="container h-full m-auto">
          <div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-600 mt-2 text-center">
                        Promociones
                    </h2>
                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="container mx-auto px-1 md:px-6">
            <div class="grid gap-6 grid-cols-1 mt-6">
              <?php foreach($promopacks as $key => $promopack):?>
                
                <div class="flex flex-col md:flex-row w-full mx-auto py-6 md:py-2 rounded-md shadow-md overflow-hidden">
                    <div id="productimg" class="w-full md:w-1/2 min-h-64 md:min-h-full md:px-5 py-3 <?php if($key%2!=0) echo 'md:order-last'; ?>" >
                    <?php 
                      $imgurl = $promopack->picture_url;
                      if(($promopack->picture_url == 'products/no_image.png') && file_exists(('public/dist/images/products/'.$promopack->idPromopack.'.jpg'))){
                        $imgurl = 'products/'.$promopack->idPromopack.'.jpg';
                      }
                     ?>
                     <a href="<?php echo get_images_path($imgurl) ?>" data-fancybox data-caption="<?php echo $promopack->idPromopack.' - '.$promopack->comments;?>">
                      <div class="flex items-end justify-end h-full w-full bg-contain bg-no-repeat bg-center mt-2" style="background-image: url('<?php echo get_images_path($imgurl);  ?>')">
                      </div>
                    </a>
                    </div>
                    <div class="flex flex-col w-full md:w-1/2 md:px-5 py-3 content-center m-auto">

                        <div class="relative overflow-x-auto md:shadow-md sm:rounded-lg content-center">
                          <table class="w-full text-sm text-left text-gray-700 dark:text-gray-400">
                              <thead class="border-b bg-gray-800">
                                <tr class="bg-gray-50 p-4 text-center font-bold col-span-2" rowspan="2">
                                  <td  colspan="2" class="px-6 py-4 col-span-2 text-xl text-gray-400" ><?php echo $promopack->name; ?></td>
                                </tr>
                              </thead>
                              <tbody>
                                  <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                      <th scope="row" class="px-6 py-4 font-medium text-bold text-gray-900 dark:text-white whitespace-nowrap">
                                          Id
                                      </th>
                                      <td class="px-6 py-4">
                                          <?php echo $promopack->idPromopack; ?>
                                      </td>
                                  </tr>
                                  <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                      <th scope="row" class="px-6 py-4 font-medium text-bold text-gray-900 dark:text-white whitespace-nowrap">
                                          Descripción
                                      </th>
                                      <td class="px-6 py-4">
                                          <?php echo $promopack->comments; ?>
                                      </td>
                                  </tr>
                                  <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                      <th scope="row" class="px-6 py-4 font-medium text-bold text-gray-900 dark:text-white whitespace-nowrap">
                                          Precio
                                      </th>
                                      <td class="px-6 py-4">
                                          <p class="font-bold text-green-900 mt-2">$<?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $promopack->total)), 2);?></p>
                                      </td>
                                  </tr>
                                  <tr class="bg-white border-b dark:bg-gray-800">
                                      <th scope="row" class="px-6 py-4 font-medium text-bold text-gray-900 dark:text-white whitespace-nowrap">
                                          Cantidad
                                      </th>
                                      <td class="px-6 py-4">
                                          <p class="text-gray-500 mt-2 font-bold <?php echo ($promopack->quantity >= 0 ? 'text-green-700' : 'text-orange-700') ?>">
                                          <?php 
                                          if($promopack->quantity <= 0) 
                                            echo "Agotado"; 
                                          elseif ($promopack->quantity == 1) 
                                            echo "1 Disponible";
                                          else 
                                            echo $promopack->quantity." Disponibles";
                                             ?></p>
                                      </td>
                                  </tr>
                                  <tr>
                                    <td colspan="2" class="px-6 py-4 font-medium text-bold text-gray-900 dark:text-white whitespace-nowrap">
                                      <?php     if($promopack->quantity > 0):  ?>

                                      <a href="<?php echo base_url();?>sisvent/store/dropshipping/buy/<?php echo $promopack->idPromopack; ?>" class="flex items-center justify-between w-64 px-4 py-2 text-sm font-medium leading-5 text-center text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo mx-auto">
                                      <span class="mx-auto text-center">Comprar</span>
                                    </a>
                                    <?php endif; ?>
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