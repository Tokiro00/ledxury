
<!DOCTYPE html>
<html lang="en">
    <title>Catálogo</title>
    <meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet"/>
<link rel="stylesheet" href="<?php echo get_public_path('main'.''.'.css') ?>"> 
<!--script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script-->
<script type="text/javascript">
    var base_url = "<?php echo base_url(); ?>";
    function printDiv(title,id) {
        var data=document.getElementById(id).innerHTML;
        var myWindow = window.open('', title, 'height=2130,width=1600');
        myWindow.document.write('<html><head><title>'+title+'</title>');
        myWindow.document.write('<link rel="stylesheet" href="<?php echo get_public_path('main.css') ?>" type="text/css" />');
        myWindow.document.write('</head><body >');
        myWindow.document.write(data);
        myWindow.document.write('</body></html>');
        myWindow.document.close(); // necessary for IE >= 10

        

        myWindow.onload=function(){ // necessary if the div contain images
            console.log("Print");

            //alert("Print");
            if(navigator.userAgent.toLowerCase().indexOf('chrome') > -1){   // Chrome Browser Detected?
                myWindow.focus(); // necessary for IE >= 10
                myWindow.PPClose = false;                                     // Clear Close Flag
                myWindow.onbeforeunload = function(){                         // Before Window Close Event
                    if(myWindow.PPClose === false){                           // Close not OK?
                        return 'Leaving this page will block the parent window!\nPlease select "Stay on this Page option" and use the\nCancel button instead to close the Print Preview Window.\n';
                    }
                }                   
                myWindow.print();                                             // Print preview
                myWindow.PPClose = true;                                      // Set Close Flag to OK.
                //myWindow.close();    
            }else{
                myWindow.focus(); // necessary for IE >= 10
                myWindow.print();
                myWindow.close();    
            }

            
        };

        /*if(navigator.userAgent.toLowerCase().indexOf('chrome') > -1){   // Chrome Browser Detected?
            window.PPClose = false;                                     // Clear Close Flag
            window.onbeforeunload = function(){                         // Before Window Close Event
                if(window.PPClose === false){                           // Close not OK?
                    return 'Leaving this page will block the parent window!\nPlease select "Stay on this Page option" and use the\nCancel button instead to close the Print Preview Window.\n';
                }
            }                   
            window.print();                                             // Print preview
            window.PPClose = true;                                      // Set Close Flag to OK.
        }*/
    }
  </script>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen">

        <main class="h-full m-auto">
          <div class="px-6 mx-auto grid">
                    <h2 class="text-2xl font-semibold mt-2 text-center">
                        Catálogo <?php echo $store->name; ?>
                    </h2>
                    <div class="mb-4 font-semibold mt-2 text-center">
                        Productos sin imagen
                    </div>
                    <div class="mb-4 font-semibold mt-2 text-center">
                    <button onclick="printDiv('Productos Sin Imagen','no-images-prod-print')"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
            <span>Imprimir</span>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
          </button>
                    </div>
                        <div class="w-full overflow-hidden rounded-lg shadow-xs mb-4">
                          
          <div id="no-images-prod-print">
              <div class="w-full overflow-x-auto overflow-y-hidden">
                <table class="w-full whitespace-no-wrap border border-slate-400 table" style="border-collapse: collapse;">
                  <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left uppercase border-b">
                      <th class="px-4 py-3 table-cell">Código</th>
                      <!--th class="px-4 py-3 table-cell">Imagen</th-->
                      <th class="px-4 py-3 table-cell">Descripción</th>
                      <th class="px-4 py-3 table-cell">U. Disponibles</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y">
                    <?php if(!empty($products)):?>
                        <?php foreach($products as $product):

                            if((($product->picture_url == 'products/no_image.png') && !file_exists(('public/dist/images/products/'.$product->idProduct.'.jpg'))) || (($product->picture_url != 'products/no_image.png') && !file_exists(('public/dist/images/'.$product->picture_url)))){
                              
                            
                          ?>

                            <tr class="border border-slate-400 table-row" style="border-bottom:2pt solid #fff;">
                              <td class="px-4 py-3 table-cell border border-slate-400">
                                  <p class="font-semibold"><?php echo $product->idProduct;?></p>
                              </td>
                              <!--td class="px-4 py-3 border border-slate-400" style="text-align: center;">
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
                              </td-->
                              <td class="px-4 py-3 table-cell text-xs whitespace-normal border border-slate-400">
                                <?php echo $product->description;?>
                              </td>
                              <td class="px-4 py-3 table-cell text-sm text-center whitespace-normal border border-slate-400" style="text-align: center;">
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
                        <?php 
                          }
                        endforeach;?>
                    <?php endif;?>
                  </tbody>
                </table>
              </div>
            </div>

            
                
                  </div>
            </div>
            
          </main>
        
    </div>
  </body>
</html>