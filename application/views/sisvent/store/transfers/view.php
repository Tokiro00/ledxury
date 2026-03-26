<div id="transfer-print">
<div class="grid mb-6">
	<div class="text-center">
		<b>Traspaso #<?php echo str_pad($transfer->idTransfer, 6, "0", STR_PAD_LEFT); ?></b><br>
	</div>
</div> 
<hr class="my-6">
<div class="grid grid-cols-12">
	<div class="grid col-span-7">
	</div>	
	<div class="grid col-span-5">
		<p><b>Fecha: </b> <?= $transfer->date; ?></p>
		<p><b>Vendedor:</b> <?php echo $transfer->user_name;?></p>
		<p><b>Origen:</b> <?php echo $transfer->origin_name;?></p>
		<p><b>Destino:</b> <?php echo $transfer->destination_name;?></p>
	</div>	
</div>
<hr class="my-6">
<div class="grid mb-6">
	<div class="">
		<b>Total Productos: <?php echo sizeof($details);?></b><br>
	</div>
</div> 
<div class="w-full overflow-hidden rounded-lg shadow-xs my-8">
   <div class="w-full overflow-x-auto">
     <table id="tborders" class="w-full whitespace-no-wrap">
			<thead>
	            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
	              <th class="px-4 py-3">#</th>
	              <th class="px-4 py-3">Código</th>
	              <th class="px-4 py-3">Descripción</th>
	              <th class="px-4 py-3 text-right">Cantidad</th>
	            </tr>
	          </thead>
	          <tbody id="tborders" class="bg-white divide-y">
	            <?php foreach($details as $key => $detail):?>
	                <tr class='text-gray-700'>
	                <td class='px-2 py-1'><?php echo ($key + 1); ?></td>
	                <td class='px-2 py-1 whitespace-normal'><?php echo $detail->idProduct; ?></td>
	                <td class='px-2 py-1 text-xs whitespace-normal'><?php echo $detail->description; ?></td>
	                <td class='px-2 py-1 text-right'><?php echo $detail->quantity; ?></td>
	                </tr>
	            <?php endforeach;?>
	          </tbody>
			
		</table>
	</div>
</div>
<div class="grid grid-cols-12 mb-8">
	<div class="grid col-span-7">
		
	</div>	
		
</div>
</div>
<button onclick="printDiv('Presupuesto <?= $transfer->idTransfer; ?>','transfer-print')"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
  <span>Imprimir</span>
  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
</button>
