<?php 
    $now = date("d-m-Y");
 ?>
<div id="lostinvoice-print">

<div class="grid text-xs">
	<!--div class="font-bold">Observaciones</div>
	<div class="grid col-span-7"><?= $invoice->comments; ?></div-->	
	
</div>
<div class="grid">
	<div class="">
		<b>Total Facturas perdidas: <?php echo sizeof($lostInvoices);?></b><br>
	</div>
</div> 
<div class="w-full overflow-hidden rounded-lg shadow-xs my-6">
   <div class="w-full overflow-x-auto">
     <table class="w-full whitespace-no-wrap mt-8 lg:mt-0">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <th class="px-4 py-3 hidden lg:table-cell">Id</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Cliente</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Almacén</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Valor</th>
                              <th class="px-4 py-3 hidden lg:table-cell">IVA</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Fecha</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Días V.</th>
                            </tr>
                          </thead>
                          <tbody id="tborders" class="bg-white divide-y">
                            <?php if(!empty($lostInvoices)):?>
                                <?php foreach($lostInvoices as $key => $invoice):?>
                                    <tr class="text-gray-700 <?php echo ($key%2 ? 'bg-gray-300' : 'bg-gray') ?> flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0">
                                      <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Id</span>
                                        <?php echo $invoice->idInvoice;?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cliente</span>
                                        <div class="flex items-center text-sm whitespace-normal">
                                          <div>
                                            <p class="font-semibold whitespace-normal"><?php echo $invoice->client_name;?></p>
                                            <p class="text-xs text-gray-600">
                                              <?php echo $invoice->client_idNum;?>
                                            </p>
                                          </div>
                                          
                                        </div>
                                      </td>
                                      <td class="px-4 py-3 text-xs whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Almacén</span>
                                        <?php echo $invoice->store_name;?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Valor</span>
                                        $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->total)), 2);//$invoice->total;?>
                                        <?php if($invoice->discount > 0): ?>
                                        <p class="text-xs w-full text-center text-orange-600">
                                          -$ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->discount)), 2);//$invoice->payment;?>
                                        </p>
                                        <?php endif; ?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">IVA</span>
                                        <div class="flex flex-col items-center text-sm">
                                          <div>
                                            <p class="">
                                              <?php if($invoice->hasIva): ?>
                                              <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                              <?php else: ?>
                                                <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                              <?php endif; ?></p>
                                          </div>
                                          <div>
                                              <?php if($invoice->e_commerce): ?>
                                            <p class="tooltip">
                                             <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Venta por E-commerce</span></p>
                                              <?php endif; ?>
                                          </div>
                                        </div>
                                      </td>
                                      <td class="px-4 py-3 text-xs whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Fecha</span>
                                        <?php echo date("d-m-Y H:m:s", strtotime($invoice->date));// $invoice->date;?>
                                      </td>
                                      <td class="px-4 py-3 text-xs whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Días de vencida</span>
                                        <?php //echo $now."   ".date("d-m-Y", strtotime($invoice->date)); ?>
                                        <?php echo (date_diff(date_create($now), date_create(date("d-m-Y", strtotime($invoice->date))))->format('%a') - 90); ?>
                                        <?php //echo (($now - date("d-m-Y", strtotime($invoice->date)))/ (60 * 60 * 24));// $invoice->date;?>
                                      </td>
                                      
                                    </tr>
                                <?php endforeach;?>
                            <?php endif;?>
                          </tbody>
                        </table>
	</div>
</div>

</div>

