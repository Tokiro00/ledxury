<?php 
$isProduction         = 'production' === ENVIRONMENT;
$prefix = $isProduction ? '' : '';
?>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="icon" type="image/jpeg" href="<?php echo base_url(); ?>public/images/logoLedxury.jpg" />
<link rel="shortcut icon" type="image/jpeg" href="<?php echo base_url(); ?>public/images/logoLedxury.jpg" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet"/>
<link rel="stylesheet" href="<?php echo get_public_path('main'.$prefix.'.css') ?>">
<link rel="stylesheet" href="<?php echo get_public_path('jquery.fancybox.min.css') ?>">
<style>
/* MAM design tokens (port desde Lumen _tokens.scss) — necesarios por
   el Panel de Vendedores y otras vistas que usan var(--mam-*). */
:root {
    --mam-blue-petroleo: #4487A0;
    --mam-blue-petroleo-light: #e8f4f8;
    --mam-blue-dark: #2B3164;
    --mam-blue-dark-hover: #1f2450;
    --mam-orange: #F7941D;
    --mam-green: #8AC045;
    --mam-green-program: #31AB20;
    --mam-green-dark: #6fa033;
    --mam-green-light: #f0f8e4;
    --mam-yellow: #FEAB2F;
    --mam-yellow-light: #FFEED4;
    --mam-purple: #5D41CC;
    --mam-purple-light: #EBE8F9;
    --mam-red: #ef0d0d;
    --mam-red-light: #FFD2D2;
    --mam-neutral-black: #2C2721;
    --mam-gray-dark: #575964;
    --mam-gray-medium: #AEAAA6;
    --mam-gray-default: #7F8392;
    --mam-gray-100: #FBFBFB;
    --mam-gray-150: #F8F8F8;
    --mam-gray-200: #F1F3F5;
    --mam-gray-300: #DDDFE8;
    --fg-1: var(--mam-neutral-black);
    --fg-2: var(--mam-gray-dark);
    --fg-3: var(--mam-gray-medium);
    --fg-4: var(--mam-gray-default);
    --fg-on-brand: #ffffff;
    --bg-page: var(--mam-gray-100);
    --bg-surface: #ffffff;
    --bg-subtle: var(--mam-gray-200);
    --bg-tinted-blue: var(--mam-blue-petroleo-light);
    --bg-tinted-green: var(--mam-green-light);
    --bg-tinted-yellow: var(--mam-yellow-light);
    --bg-tinted-red: var(--mam-red-light);
    --border-default: var(--mam-gray-300);
    --border-subtle: var(--mam-gray-200);
    --border-strong: var(--mam-gray-medium);
    --state-success: var(--mam-green-program);
    --state-warning: var(--mam-yellow);
    --state-danger:  var(--mam-red);
    --state-info:    var(--mam-blue-petroleo);
    --pill-success-bg: #D1FAE5; --pill-success-fg: #065F46;
    --pill-warning-bg: #FEF3C7; --pill-warning-fg: #92400E;
    --pill-danger-bg:  #FEE2E2; --pill-danger-fg:  #991B1B;
    --pill-gray-bg:    #F3F4F6; --pill-gray-fg:    #6B7280;
    --pill-info-bg:    var(--mam-blue-petroleo-light);
    --pill-info-fg:    var(--mam-blue-dark);
    --focus-ring: 0 0 0 3px rgba(68,135,160,0.40);
}
</style>
<!--script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script-->
<?php if ($isProduction): ?>
<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js" defer></script>
<?php else:?>
<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js" defer></script>
<?php endif;?>

<script src="<?php echo get_public_path('main'.$prefix.'.js') ?>"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
<script src="<?php echo get_public_path('jquery.fancybox.min.js') ?>"></script>
<script src="<?php echo get_public_path('FileSaver.min.js') ?>"></script>
<script src="<?php echo get_public_path('xlsx.core.min.js') ?>"></script>
<script src="<?php echo get_public_path('tableExport.min.js') ?>"></script>
<script src="<?php echo base_url() ?>public/assets/js/vendor/fix-webm-duration.js"></script>
<script type="text/javascript">
    var base_url = "<?php echo base_url(); ?>";
    window.base_url = base_url;
    function printDiv(title,id,type=0, eid) {
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

            switch(type){
                case 1:
                    $.ajax({
                        url: base_url+"sisvent/commercial/budgets/printed",
                        type:"POST",
                        dataType:"html",
                        data:{id: eid},
                        success:function(data){
                            console.log(data);
                            //showModal(data, "", "Cerrar", true);
                            //$("#modal-default .modal-body").html(data);
                        }
                    });
                    break;
                case 2:
                    $.ajax({
                        url: base_url+"sisvent/commercial/invoices/printed",
                        type:"POST",
                        dataType:"html",
                        data:{id: eid},
                        success:function(data){
                            console.log(data);
                            //showModal(data, "", "Cerrar", true);
                            //$("#modal-default .modal-body").html(data);
                        }
                    });
                    break;
                default:
                break;
            }

            

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