<?php if(!empty($vendors)):?>
      <?php foreach($vendors as $key => $vendor):?>
          <tr class="text-gray-700 <?php echo $key%2 ? 'bg-gray-300' : 'bg-gray' ?>">
            <td class="px-4 py-3 text-sm">
              <?php echo $vendor->idUser;?>
            </td>
            <td class="px-4 py-3">
              <?php echo $vendor->name;?>
            </td>
            <td class="px-4 py-3">
              $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $vendor->totalPaidMonthInvoices)), 2);?>
            </td>
                        
          </tr>
      <?php endforeach;?>
  <?php endif; ?>