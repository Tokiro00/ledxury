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
                <td class="px-4 py-3 text-xs whitespace-normal">
                  <?php echo $client->f_id;?>
                </td>
                <td class="px-4 py-3 text-xs whitespace-normal">
                  <?php echo $client->address;?>
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
