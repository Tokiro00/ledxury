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
                
                <a href="<?php echo base_url();?>sisvent/store/dropshipping/promos" class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                  <span>Volver</span>
                </a>

            </div>
            <h2 class="mb-4 text-2xl font-semibold text-gray-600 mt-2 text-center">
                Comprar <?php echo $promopack->name; ?>
            </h2>
            <div class="w-full overflow-hidden rounded-lg shadow-xs">
              <div class="container mx-auto px-1 md:px-6">
                <div class="grid gap-6 grid-cols-1 mt-6">
                 <div class="flex flex-col md:flex-row w-full mx-auto py-6 md:py-2 rounded-md shadow-md overflow-hidden">
                    <div id="productimg" class="w-full md:w-1/2 min-h-64 md:min-h-full md:px-5 py-3" >
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
                                  <tr class="bg-white border-b dark:bg-gray-800">
                                      <th scope="row" class="px-6 py-4 font-medium text-bold text-gray-900 dark:text-white whitespace-nowrap">
                                          Disponibilidad
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
                              </tbody>
                          </table>
                      </div>
                    </div>
                  </div>     
                </div>     

                <h2 class="mb-4 text-2xl font-semibold text-gray-600 mt-2 text-center">
                  Datos del comprador
                </h2>
                <form id="new-purchase-form" action="<?php echo base_url();?>sisvent/store/dropshipping/completepurchase" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div id="promo-client-data" class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        <input class="form-input" type="hidden" name="promopack" value="<?php echo $promopack->idPromopack; ?>" readonly/>
                        
                        <label class="block text-sm mt-4 <?php echo !empty(form_error('client_id')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Cédula/NIT</span>
                          <input id="promopack-client" class="form-input" type="text" name="client_id" value="<?php echo set_value('client_id');?>" required/>
                          <?php echo form_error("client_id","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('quantity')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Cantidad</span>
                          <input class="form-input" type="number" name="quantity" min="1" max="<?php echo $promopack->quantity; ?>" value="<?php echo set_value('quantity', 1);?>"/>
                          <?php echo form_error("quantity","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <div id="verify-client-container" class="flex flex-col flex-wrap mb-8 space-y-1 md:flex-row md:items-end md:space-x-4">
                          <div class="flex-1"></div>
                          <div class="block text-sm mt-1">
                              <button id="btn-verify-client" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="" />
                                <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                <span class="inline pr-4">Verificar Cliente</span>
                              </button>
                          </div>
                        </div>

                      </div>
                    </form>
                
              </div>
        
            </div>
          </div>
          </main>
        
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>