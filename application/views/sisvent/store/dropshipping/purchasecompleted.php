

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    $role = $this->session->userdata('user_data')['role'];
     $url_params = createFullParamsLinks($page);
?>
<!DOCTYPE html>
<html lang="en">
    <title>Promociones</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <main class="h-full m-auto">
          <div class="px-6 mx-auto grid">
            <h1 class="mb-4 text-lg font-semibold text-gray-600 mt-2 text-center mx-auto">
                Compra registrada
            </h1>
                    
            <div class="w-full overflow-hidden rounded-lg shadow-xs">
                <div class="container mx-auto px-6">
                    <h4 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        <?php echo $clientName; ?> gracias por su compra, en poco tiempo alguno de nuestros vendedores se contactará con usted para ultimar detalles.
                    </h4>
                    <div class="grid gap-6 grid-cols-1 mt-6">
                 <div class="flex flex-col md:flex-row w-full mx-auto py-6 md:py-2 rounded-md shadow-md overflow-hidden">
                    <div id="productimg" class="w-full md:w-1/2 md:px-5 py-3" >
                    <?php 
                      $imgurl = $promopack->picture_url;
                      
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
                              </tbody>
                          </table>
                      </div>
                    </div>
                  </div>     
                </div> 
                        
                </div>
        
            </div>
          </div>
        </main>
        
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>