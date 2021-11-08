<?php if(!empty($vouchers)):?>
      <?php foreach($vouchers as $key => $voucher):?>
          <tr class="text-gray-700 <?php echo $key%2 ? 'bg-gray-300' : 'bg-gray' ?>">
            <td class="px-4 py-3 text-sm">
              <?php echo $voucher->idVoucher;?>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center text-sm whitespace-normal">
                <div>
                  <p class="font-semibold whitespace-normal"><?php echo $voucher->vendor_name;?></p>
                  <p class="text-xs text-gray-600">
                    <?php echo $voucher->userId;?>
                  </p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $voucher->value)), 2);//$voucher->total;?>
            </td>
            <td class="px-4 py-3 text-xs whitespace-normal">
              <?php echo $voucher->method_name;?>
            </td>
            <td>
              <?php switch ($voucher->state) {
                 case 1:?>
                  <span class="px-2 py-1 font-semibold leading-tight text-orange-700 bg-orange-100 rounded-full dark:text-white dark:bg-orange-600">
                    Pagada
                  </span>
                 <?php break;
                 case 2:?>
                  <span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">
                    Liquidada
                  </span>
                 <?php break;
                
                default:?>
                  <span class="px-2 py-1 font-semibold leading-tight text-gray-700 bg-gray-100 rounded-full dark:text-gray-100 dark:bg-gray-700">
                    Expired
                  </span>
                 <?php break;
              } ?>
            </td>
            <td class="px-4 py-3 text-xs whitespace-normal">
              <?php echo date("d-m-Y", strtotime($voucher->date));?>
            </td>
            <td class="px-4 py-3 text-xs max-w-2xl whitespace-normal">
              <?php echo $voucher->description;?>
            </td>
            
          </tr>
      <?php endforeach;?>
  <?php endif; ?>