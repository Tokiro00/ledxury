<div id="budget-print">
<div class="grid mb-6">
	<div class="text-center">
		<b>Clientes sin atender</b><br>
	</div>
</div> 
<hr class="my-6">
<div class="grid grid-cols-12">
	<div class="grid col-span-7">
		
	</div>	
	<div class="grid col-span-5">
		<p><b>Vendedor:</b> <?php echo $vendor->name;?></p>
	</div>	
</div>
<hr class="my-6">
<div class="w-full overflow-hidden rounded-lg shadow-xs my-8">
   <div class="w-full overflow-x-auto">
     <table class="w-full whitespace-no-wrap">
      <thead>
        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
          <th class="px-4 py-3">Id</th>
          <th class="px-4 py-3">Cliente</th>
          <th class="px-4 py-3">Dirección</th>
          <th class="px-4 py-3">Teléfono</th>
          <th class="px-4 py-3">Email</th>
          <th class="px-4 py-3">Vendedor</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y">
        <?php if(!empty($clients)):?>
            <?php foreach($clients as $client):?>
                <tr class="text-gray-700">
                  <td class="px-4 py-3 text-sm">
                    <?php echo $client->idClient;?>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center text-sm whitespace-normal">
                        <div>
                          <p class="font-semibold whitespace-normal"><?php echo $client->name;?></p>
                          <p class="text-xs text-gray-600">
                            <?php echo $client->idNum;?>
                          </p>
                        </div>
                    </div>
                  </td>
                  
                  
                  <td class="px-4 py-3 text-xs whitespace-normal">
                    <p><?php echo $client->address;?></p>
                    <p><?php echo $client->city." - ".$client->state;?></p>
                  </td>
                  <td class="flex items-center text-xs">
                    <div>
                      <p><?php echo $client->phone;?></p>
                      <p><?php echo $client->cellphone;?></p>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-xs">
                    <?php echo $client->email;?>
                  </td>
                  <td class="px-4 py-3 text-sm whitespace-normal">
                    <?php echo $client->vendor_name;?>
                  </td>
                 
                </tr>
            <?php endforeach;?>
        <?php endif;?>
      </tbody>
    </table>
	</div>
</div>
<hr class="my-6">
<div class="text-center">
		<b>Clientes nunca atendidos</b><br>
	</div>
<div class="w-full overflow-hidden rounded-lg shadow-xs my-8">
	
   <div class="w-full overflow-x-auto">
     <table class="w-full whitespace-no-wrap">
      <thead>
        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
          <th class="px-4 py-3">Id</th>
          <th class="px-4 py-3">Cliente</th>
          <th class="px-4 py-3">Dirección</th>
          <th class="px-4 py-3">Teléfono</th>
          <th class="px-4 py-3">Email</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y">
        <?php if(!empty($neverclients)):?>
            <?php foreach($neverclients as $client):?>
                <tr class="text-gray-700">
                  <td class="px-4 py-3 text-sm">
                    <?php echo $client->idClient;?>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center text-sm whitespace-normal">
                        <div>
                          <p class="font-semibold whitespace-normal"><?php echo $client->name;?></p>
                          <p class="text-xs text-gray-600">
                            <?php echo $client->idNum;?>
                          </p>
                        </div>
                    </div>
                  </td>
                  
                  
                  <td class="px-4 py-3 text-xs whitespace-normal">
                    <p><?php echo $client->address;?></p>
                    <p><?php echo $client->city." - ".$client->state;?></p>
                  </td>
                  <td class="flex items-center text-xs">
                    <div>
                      <p><?php echo $client->phone;?></p>
                      <p><?php echo $client->cellphone;?></p>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-xs">
                    <?php echo $client->email;?>
                  </td>
                 
                </tr>
            <?php endforeach;?>
        <?php endif;?>
      </tbody>
    </table>
	</div>
</div>
<div class="grid grid-cols-12 mb-8">
		
</div>
</div>
<button onclick="printDiv('Cientes sin atender <?= $vendor->name; ?>','budget-print')"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
  <span>Imprimir</span>
  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
</button>
