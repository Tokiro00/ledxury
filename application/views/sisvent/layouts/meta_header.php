<?php 
$isProduction         = 'production' === ENVIRONMENT;
$prefix = $isProduction ? '.min' : '';
?>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet"/>
<link rel="stylesheet" href="<?php echo get_public_path('main'.$prefix.'.css') ?>"> 
<!--script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script-->
<?php if ($isProduction): ?>
<script src="https://cdn.jsdelivr.net/npm/vue" defer></script>
<?php else:?>
<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js" defer></script>
<?php endif;?>
<script src="<?php echo get_public_path('main'.$prefix.'.js') ?>"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
<script type="text/javascript">
    var base_url = "<?php echo base_url(); ?>";
    function printDiv(title,id) {
        var data=document.getElementById(id).innerHTML;
        var myWindow = window.open('', title, 'height=2130,width=1600');
        myWindow.document.write('<html><head><title>'+title+'</title>');
        myWindow.document.write('<link rel="stylesheet" href="<?php echo get_public_path('main'.$prefix.'.css') ?>" type="text/css" />');
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