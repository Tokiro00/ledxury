<div id="inventory-print">
<div class="grid mb-6">
	<div class="text-center">
		<b>Inventario #<?php echo str_pad($inventory->idInventory, 6, "0", STR_PAD_LEFT); ?></b><br>
	</div>
</div> 
<hr class="my-6">
<div class="grid grid-cols-12">
	<div class="grid col-span-7">
		<p class="text-xl font-semibold"><?php echo $inventory->user_name;?></p>
	</div>	
	<div class="grid col-span-5">
		<p><b>Fecha: </b> <?= $inventory->date; ?></p>
		<p><b>Almacén:</b> <?php echo $inventory->store_name;?></p>
		<p><b>Conteo 1 por:</b> <?php echo $inventory->counted_count_1;?></p>
		<p><b>Conteo 1 ingresado por:</b> <?php echo $inventory->entry_count_1;?></p>
		<p><b>Conteo 2 por:</b> <?php echo $inventory->counted_count_2;?></p>
		<p><b>Conteo 2 ingresado por:</b> <?php echo $inventory->entry_count_2;?></p>
	</div>	
</div>
<hr class="my-6">
<div class="grid mb-6">
	<div class="">
		<b><?php echo $inventory->comments;?></b><br>
	</div>
</div> 
<div class="w-full overflow-hidden rounded-lg shadow-xs my-8">
   <div class="w-full overflow-x-auto">
     <table id="tborders" class="w-full whitespace-no-wrap">
			<thead>
	            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
	              <th class="px-4 py-3 text-xs">#</th>
	              <th class="px-4 py-3 text-xs">Código</th>
	              <th class="px-4 py-3 text-xs">Descripción</th>
	              <th class="px-4 py-3 text-xs text-right">Cantidad</th>
	            </tr>
	          </thead>
	          <tbody id="tborders" class="bg-white divide-y">
	            <?php foreach($details as $key => $detail):?>
	                <tr class='text-gray-700'>
	                <td class='px-2 py-1 text-sm'><?php echo ($key + 1); ?></td>
	                <td class='px-2 py-1 text-xs whitespace-normal'><?php echo $detail->idProduct; ?></td>
	                <td class='px-2 py-1 text-xs whitespace-normal'><?php echo $detail->description; ?></td>
	                <td class='px-2 py-1 text-sm text-right'><?php echo $detail->quantity; ?></td>
	                </tr>
	            <?php endforeach;?>
	          </tbody>
			
		</table>
	</div>
</div>
<div class="grid grid-cols-12 mb-8">
	<div class="grid col-span-7">
		
	</div>	
	<div class="flex flex-col col-span-5">

	</div>	
</div>
</div>
<button onclick="printDiv('Inventario <?= $inventory->idinventory; ?>','inventory-print')"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
  <span>Imprimir</span>
  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
</button>
