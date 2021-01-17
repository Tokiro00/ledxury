<div id="budget-print">
<div class="grid mb-6">
	<div class="text-center">
		<b>Presupuesto <?= $budget->idBudget; ?></b><br>
	</div>
</div> 
<hr class="my-6">
<div class="grid grid-cols-12">
	<div class="grid col-span-7">
		<p class="text-xl font-semibold"><?php echo $budget->client_name;?></p>
		<p class="text-gray-600"><?php echo $budget->client_idNum;?></p>
	</div>	
	<div class="grid col-span-5">
		<p><b>Fecha: </b> <?= $budget->date; ?></p>
		<p><b>Vendedor:</b> <?php echo $budget->vendor_name;?></p>
	</div>	
</div>
<hr class="my-6">
<div class="w-full overflow-hidden rounded-lg shadow-xs my-8">
   <div class="w-full overflow-x-auto">
     <table class="w-full whitespace-no-wrap">
			<thead>
	            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
	              <th class="px-4 py-3">Código</th>
	              <th class="px-4 py-3">Descripción</th>
	              <th class="px-4 py-3 text-right">Cantidad</th>
	              <th class="px-4 py-3 text-right">-</th>
	              <th class="px-4 py-3 text-right">V. Unitario</th>
	              <th class="px-4 py-3 text-right">Total</th>
	            </tr>
	          </thead>
	          <tbody id="tborders" class="bg-white divide-y">
	            <?php foreach($details as $key => $detail):?>
	                <tr class='text-gray-700 <?php echo $key%2 ? 'bg-gray-300 print:bg-gray-300' : 'bg-gray-100 print:bg-gray-100' ?>'>
	                <td class='px-2 py-1'><?php echo $detail->productId; ?></td>
	                <td class='px-2 py-1 text-xs'><?php echo $detail->description; ?></td>
	                <td class='px-2 py-1 text-right'><?php echo $detail->quantity; ?></td>
	                <td class='px-2 py-1'> </td>
	                <td class='px-2 py-1 text-right'><?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->unit)), 2);//$detail->unit; ?></td>
	                <td class='px-2 py-1 text-right'><?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->subtotal)), 2);//$detail->subtotal; ?></td>
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
		<hr>
		<div class="flex flex-row justify-between px-12 font-bold">
		<p>Total:</p>
		<p><?php echo "$" . number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $budget->total)), 2);//$budget->total;?></p>
		</div>
	</div>	
</div>
</div>
<button onclick="printDiv('Presupuesto <?= $budget->idBudget; ?>','budget-print')"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
  <span>Imprimir</span>
  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
</button>
