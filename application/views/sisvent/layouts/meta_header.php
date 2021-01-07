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