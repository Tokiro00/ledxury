<div id="payment-print">

<div class="grid grid-cols-12">
	<div class="grid col-span-7">
		<p class="text-xl font-semibold"><?php echo $invoice->client_name;?></p>
		<p class="text-gray-600"><?php echo $invoice->client_idNum;?></p>
		<p class="text-gray-600"><?php echo $invoice->address;?></p>
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

<div class="grid text-center">
	PAGOS
</div> 
<div class="w-full overflow-hidden rounded-lg shadow-xs my-6">
   <div class="w-full overflow-x-auto">
     <table class="w-full whitespace-no-wrap">
		<thead>
            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
              <th class="px-4 py-3 text-xs">Id</th>
              <th class="px-4 py-3 text-xs">Método</th>
              <th class="px-4 py-3 text-xs">Valor</th>
              <th class="px-4 py-3 text-xs">Fecha</th>
              <th class="px-4 py-3 text-xs">Observaciones</th>
            </tr>
          </thead>
          <tbody id="tborders" class="bg-white divide-y">
            <?php foreach($payments as $key => $payment):?>
                <tr class='text-gray-700 <?php echo $key%2 ? 'bg-gray-300 print:bg-gray-300' : 'bg-gray print:bg-gray' ?>'>
                <td class='px-2 py-1 text-sm'><?php echo $payment->idPayment; ?></td>
                <td class='px-2 py-1 text-xs whitespace-normal'><?php echo $payment->method_name; ?></td>
                <td class='px-2 py-1 text-sm'>$ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $payment->payment)), 2); ?></td>
                <td class='px-2 py-1 text-sm'><?php echo date("d-m-Y", strtotime($payment->date)); ?></td>
                <td class='px-2 py-1 text-xs whitespace-normal'><?php echo $payment->comments; ?></td>
                </tr>
            <?php endforeach;?>
          </tbody>
			
		</table>
	</div>
</div>	
</div>
