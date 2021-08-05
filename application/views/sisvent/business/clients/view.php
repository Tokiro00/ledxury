<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<div id="product-print">
	<hr class="my-6">
	<div class="w-full overflow-hidden rounded-lg shadow-xs">
      <div class="w-full overflow-x-auto">
        <table class="w-full whitespace-no-wrap">
          <thead>
            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
              <th class="px-4 py-3">Id</th>
                <th class="px-4 py-3">Cliente</th>
                <th class="px-4 py-3">Id Factusol</th>
                <th class="px-4 py-3">Detal</th>
                <th class="px-4 py-3">Dirección</th>
                <th class="px-4 py-3">Teléfono</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Vendedor</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y">
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
                <td class="px-4 py-3 text-xs flex flex-row gap-2 whitespace-normal">
                  <div>
                    <?php if($client->is_new): ?>
                    <p class="tooltip">
                       <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Cliente nuevo</span></p>
                    <?php endif; ?>
                  </div>
                  <div><?php echo $client->f_id;?></div>
                </td>
                <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                  <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Detal</span>
                  <div class="flex flex-col items-center text-sm">
                    <p class="">
                      <?php if($client->retail): ?>
                      <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                      <?php else: ?>
                        <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                      <?php endif; ?></p>                    
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
          </tbody>
        </table>
      </div>
    </div>
	
</div>
