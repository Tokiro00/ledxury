

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Promo</title>
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
                    
                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        
                       <div class="flex-1"></div>
                        
                        <a href="<?php echo base_url();?>sisvent/store/dropshipping" class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                          <span>Volver</span>
                        </a>

                    </div>
                    <h2 class="mb-4 text-2xl font-semibold text-gray-600 mt-2 text-center">
                        <?php echo $promopack->name; ?>
                    </h2>
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="container mx-auto px-1 md:px-6">
                       
                        <div class="relative overflow-x-auto md:shadow-md sm:rounded-lg content-center">
                          <table class="w-full text-sm text-left text-gray-700 dark:text-gray-400">
                              <thead class="border-b bg-gray-800">
                                <tr class="bg-gray-50 p-4 text-center font-bold col-span-2" rowspan="2">
                                  <td  colspan="2" class="px-6 py-4 col-span-2 text-xl text-gray-400" >Datos</td>
                                </tr>
                              </thead>
                              <tbody>
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
                                          Cantidad disponible
                                      </th>
                                      <td class="px-6 py-4">
                                          <?php echo $promopack->quantity; ?>
                                      </td>
                                  </tr>
                                  <tr class="bg-white dark:bg-gray-800">
                                      <th scope="row" class="px-6 py-4 font-medium text-bold text-gray-900 dark:text-white whitespace-nowrap">
                                          Valor
                                      </th>
                                      <td class="px-6 py-4">
                                          <p class="text-gray-900 mt-2 font-bold">
                                          $<?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $promopack->total)), 2);
                                             ?></p>
                                      </td>
                                  </tr>
                              </tbody>
                          </table>
                      </div>

            <div class="grid gap-6 grid-cols-1 mt-6">

              <div id="productimg" class="w-full md:w-1/2 md:px-5 py-3 mx-auto" >
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
               <h2 class="mb-4 text-2xl font-semibold text-gray-600 mt-2 text-center">
                        Productos
                      </h2>
              <?php foreach($products as $key => $product):?>
                
                <div class="flex flex-col md:flex-row w-full mx-auto py-6 md:py-2 rounded-md shadow-md overflow-hidden">
                    
                    <div class="flex flex-col w-full md:w-1/2 md:px-5 py-3 content-center m-auto">

                        <div class="relative overflow-x-auto md:shadow-md sm:rounded-lg content-center">
                          <table class="w-full text-sm text-left text-gray-700 dark:text-gray-400">
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
                                  <tr class="bg-white dark:bg-gray-800">
                                      <th scope="row" class="px-6 py-4 font-medium text-bold text-gray-900 dark:text-white whitespace-nowrap">
                                          Cantidad
                                      </th>
                                      <td class="px-6 py-4">
                                          <p class="text-gray-500 mt-2 font-bold">
                                          <?php echo $product->quantity;
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
        
          </div>
          </div>
          </main>
        
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>