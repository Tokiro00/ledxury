
<!DOCTYPE html>
<html lang="en">
    <title>Catálogo</title>
    <meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet"/>
<!--link rel="stylesheet" href="<?php echo get_public_path('main'.''.'.css') ?>"> 
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script-->
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen">

        <main class="h-full m-auto">
          <div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-2xl font-semibold mt-2 text-center">
                        Catálogo <?php echo $store->name; ?>
                    </h2>
                        <div class="w-full overflow-hidden rounded-lg shadow-xs">
              <div class="w-full overflow-x-auto overflow-y-hidden">
                <table class="w-full whitespace-no-wrap border border-slate-400" style="border-collapse: collapse;">
                  <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left uppercase border-b">
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
                            <tr class="border border-slate-400" style="border-bottom:2pt solid #ffffff;">
                              <td class="px-4 py-3 border border-slate-400">
                                  <p class="font-semibold"><?php echo $product->idProduct;?></p>
                              </td>
                              <td class="px-4 py-3 border border-slate-400" style="text-align: center;">
                                <div class="flex items-center text-sm">
                                  <div class="relative hidden h-64 mr-3 md:block">
                                    <?php 
                                    $imgurl = $product->picture_url;
                                    if(($product->picture_url == 'products/no_image.png') && file_exists(('public/dist/images/products/'.$product->idProduct.'.jpg'))){
                                      $imgurl = 'products/'.$product->idProduct.'.jpg';
                                    }
                                     ?>
                                    <img class="object-contain" style="max-height: 150px; min-height: 90px;" src="<?php echo get_images_path($imgurl) ?>" alt="" loading="lazy"/>
                                    <div class="absolute inset-0 shadow-inner" aria-hidden="true"></div>
                                  </div>
                                </div>
                              </td>
                              <td class="px-4 py-3 text-xs whitespace-normal border border-slate-400">
                                <?php echo $product->description;?>
                              </td>
                              <td class="font-semibold px-4 py-3 text-sm border border-slate-400" style="text-align: center;">
                                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price)), 2);// $product->price;?>
                              </td>
                              <td class="px-4 py-3 text-sm text-center whitespace-normal border border-slate-400" style="text-align: center;">
                                <p class=" mt-2 font-bold">
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
            </div>

            
                
                  </div>
          </main>
        
    </div>
  </body>
</html>