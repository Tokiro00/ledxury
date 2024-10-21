<div id="invoice-print">
<div class="grid grid-cols-12 mb-6">
	<!--div class="flex flex-col col-span-4 text-left">
		<img aria-hidden="true" class="object-contain w-32 h-32" src="<?php echo get_images_path('svg/logo.png') ?>" alt="Logo"/>
		<div class="text-xs"></div>
		<div class="text-xs"></div>
	</div>
	<div class="grid col-span-4"></div>
	<div class="grid col-span-4 text-right">
		<b>#<?php echo str_pad($invoice->idInvoice, 6, "0", STR_PAD_LEFT); ?></b><br>
	</div-->
	<div class="text-center">
		<b>#<?php echo str_pad($invoice->idInvoice, 6, "0", STR_PAD_LEFT); ?></b><br>
	</div>
</div> 
<hr class="my-6">
<div class="grid grid-cols-12">
	<div class="grid col-span-7">
		<p class="text-xl font-semibold"><?php echo $invoice->client_name;?></p>
		<p class="text-gray-600"><?php echo $invoice->client_idNum;?></p>
		<p class="text-gray-600"><?php echo $invoice->address;?></p>
		<?php if(isset($invoice->zone)): ?>
    <p class="text-gray-600"><?php echo $invoice->zone;?></p>
    <?php endif; ?> 
		<p class="text-gray-600"><?php echo $invoice->phone;?> - <?php echo $invoice->cellphone;?></p>
		<p class="text-gray-600"><?php echo $invoice->city;?> - <?php echo $invoice->client_state;?></p>
	</div>	
	<div class="grid col-span-5">
		<p><b>Fecha: </b> <?= $invoice->date; ?></p>
		<p><b>Vendedor:</b> <?php echo $invoice->vendor_name;?></p>
		<p><b>Almacén:</b> <?php echo $invoice->store_name;?></p>
	</div>	
</div>
<hr class="my-3">
<div class="grid text-xs">
	<!--div class="font-bold">Observaciones</div>
	<div class="grid col-span-7"><?= $invoice->comments; ?></div-->	
	
</div>
<div class="grid">
	<div class="">
		<b>Total Productos: <?php echo sizeof($details);?></b><br>
	</div>
</div> 
<div class="w-full overflow-hidden rounded-lg shadow-xs my-6">
   <div class="w-full overflow-x-auto">
     <table class="w-full whitespace-no-wrap">
			<thead>
	            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
	              <th class="px-4 py-3 text-xs">#</th>
	              <th class="px-4 py-3 text-xs">Código</th>
	              <th class="px-4 py-3 text-xs">Descripción</th>
	              <th class="px-4 py-3 text-xs text-right">Cantidad</th>
	              <th class="px-4 py-3 text-xs text-right">-</th>
	              <th class="px-4 py-3 text-xs text-right">V. Unitario</th>
	              <th class="px-4 py-3 text-xs text-right">Total</th>
	            </tr>
	          </thead>
	          <tbody id="tborders" class="bg-white divide-y">
	            <?php foreach($details as $key => $detail):?>
	                <tr class='text-gray-700 <?php echo $key%2 ? 'bg-gray-300 print:bg-gray-300' : 'bg-gray print:bg-gray' ?>'>
	                <td class='px-2 py-1 text-sm'><?php echo ($key + 1); ?></td>
	                <td class='px-2 py-1 text-xs whitespace-normal'><?php echo $detail->productId; ?></td>
	                <td class='px-2 py-1 text-xs whitespace-normal'><?php echo $detail->description; ?></td>
	                <td class='px-2 py-1 text-sm text-right'><?php echo $detail->quantity; ?></td>
	                <td class='px-2 py-1 text-sm text-right'>___</td>
	                <td class='px-2 py-1 text-sm text-right'><?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->unit)), 2);//$detail->unit; ?></td>
	                <td class='px-2 py-1 text-sm text-right'><?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->subtotal)), 2);//$detail->subtotal; ?></td>
	                </tr>
	            <?php endforeach;?>
	          </tbody>
			
		</table>
	</div>
</div>
<div class="grid grid-cols-12 mb-6">
	<div class="grid col-span-8 text-xs">
		
	</div>	
	<div class="flex flex-col col-span-4">
		<hr>
		<?php if($invoice->discount > 0): ?>
		<div class="flex flex-col justify-between">
		<p class="flex flex-row justify-between"><span>Subtotal: </span><span><?php echo "$" . number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->total)), 2);?></span></p>
		<p class="flex flex-row justify-between"><span>Descuento: </span><span><?php echo "-$" . number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->discount)), 2);?></span></p>
		<p class="flex flex-row justify-between font-bold"><span>Total: </span><span><?php echo "$" . number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->total-$invoice->discount)), 2);?></span></p>
		</div>
		<?php else: ?>
		<div class="flex flex-row justify-between px-12 font-bold">
		<p>Total:</p>
		<p><?php echo "$" . number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->total)), 2);//$invoice->total;?></p>
		</div>
		<?php endif; ?>
	</div>	
</div>
<div class="grid text-xs mb-6">
	<div class="font-bold">Observaciones Generales</div>
	<?php $store = getStoreData($invoice->storeId); ?>
	<ul class="list-disc">
		<li><?php echo $store->invoice_account ?></li>
		<li><?php echo $store->invoice_support ?></li>
		<li>Si pagan en efectivo, solicite recibo de caja y reporte inmediatamente, o de lo contrario no nos hacemos responsables por el dinero</li>
		<li>Por favor revisar su pedido al momento de recibirlo y notificar si le llegó completo o no, ya que luego de 5 dias no nos hacemos responsables por algún faltante.</li>
	</ul>
</div>	
<div class="mb-6">
	<div class="flex items-center text-sm font-medium leading-5">
	<div class="flex items-center px-4 py-2 text-sm font-medium leading-5"><svg class="w-6 h-6" fill="#000000" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M20.445 5h-8.891A6.559 6.559 0 0 0 5 11.554v8.891A6.559 6.559 0 0 0 11.554 27h8.891a6.56 6.56 0 0 0 6.554-6.555v-8.891A6.557 6.557 0 0 0 20.445 5zm4.342 15.445a4.343 4.343 0 0 1-4.342 4.342h-8.891a4.341 4.341 0 0 1-4.341-4.342v-8.891a4.34 4.34 0 0 1 4.341-4.341h8.891a4.342 4.342 0 0 1 4.341 4.341l.001 8.891z"></path><path d="M16 10.312c-3.138 0-5.688 2.551-5.688 5.688s2.551 5.688 5.688 5.688 5.688-2.551 5.688-5.688-2.55-5.688-5.688-5.688zm0 9.163a3.475 3.475 0 1 1-.001-6.95 3.475 3.475 0 0 1 .001 6.95zM21.7 8.991a1.363 1.363 0 1 1-1.364 1.364c0-.752.51-1.364 1.364-1.364z"></path></g></svg><span class="ml-2">@mamlucesled</span></div>
	<div class="flex items-center px-4 py-2 text-sm font-medium leading-5"><svg class="w-6 h-6" fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M12 2.03998C6.5 2.03998 2 6.52998 2 12.06C2 17.06 5.66 21.21 10.44 21.96V14.96H7.9V12.06H10.44V9.84998C10.44 7.33998 11.93 5.95998 14.22 5.95998C15.31 5.95998 16.45 6.14998 16.45 6.14998V8.61998H15.19C13.95 8.61998 13.56 9.38998 13.56 10.18V12.06H16.34L15.89 14.96H13.56V21.96C15.9164 21.5878 18.0622 20.3855 19.6099 18.57C21.1576 16.7546 22.0054 14.4456 22 12.06C22 6.52998 17.5 2.03998 12 2.03998Z"></path> </g></svg><span class="ml-2">MAM Multi Accesorios Medellin</span></div>
</div>
<div class="flex items-center px-4 pb-2 text-sm font-medium leading-5"></div>
</div>
</div>
<button onclick="printDiv('No. <?= $invoice->idInvoice; ?>','invoice-print', 2, '<?= $invoice->idInvoice; ?>')"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
  <span>Imprimir</span>
  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
</button>
