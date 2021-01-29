<?php 

function get_images_path($image = '') {
    return base_url() . 'public/dist/images/' . $image;
}

function get_public_path($asset = '') {
    return base_url() . 'public/dist/' . $asset;
}

function test_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}

	function getVendorSettlement($vendor)
	{
		$CI =& get_instance();

		$invoices = $CI->invoices_model->getVendorPaidInvoices($vendor);
		$total = 0;
		foreach ($invoices as $key => $invoice) {
			if($invoice->clientId == $vendor)
			{
				if($invoice->hasIva)
				{
					$total -= $invoice->total * ($invoice->iva/100);
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					foreach($details as $key => $detail){
						$total -= ($detail->subtotal - ($detail->quantity * $detail->price_base));
					}
				}
			}else
			{
				if($invoice->hasIva)
				{
					$total += $invoice->total * ($invoice->iva/100);
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					foreach($details as $key => $detail){
						$total += ($detail->subtotal - ($detail->quantity * $detail->price_base));
					}
				}
			}
		}

		$vouchersTotal = $CI->vouchers_model->getVendorPaidVouchersTotal($vendor);

		$total -= $vouchersTotal->total;

		//echo "  total:".$total."<br>";
		return $total;
	}

	function getVendorSettlementViewUgly($vendor)
	{
		$CI =& get_instance();
		
		$invoices = $CI->invoices_model->getVendorPaidInvoices($vendor);
		$total = 0;
		foreach ($invoices as $key => $invoice) {
			echo "Factura #".str_pad($invoice->idInvoice, 6, "0", STR_PAD_LEFT)." Cliente:".$invoice->client_name." total:".$invoice->total." Iva:".$invoice->hasIva."<br>";
			if($invoice->clientId == $vendor)
			{

				if($invoice->hasIva)
				{
					$total -= ($invoice->total * ($invoice->iva/100));
					echo "     -".(($invoice->total * ($invoice->iva/100)))."<br>";
					echo "  total:".$total."<br>";
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					foreach($details as $key => $detail){
						$total -= ($detail->subtotal - ($detail->quantity * $detail->price_base));
						echo "    ".$detail->productId." pre:".$detail->unit." subt:".$detail->subtotal." q:".$detail->quantity." base:".$detail->price_base."<br>";
						echo "      -".(($detail->subtotal - ($detail->quantity * $detail->price_base)))."<br>";
						echo "  total:".$total."<br>";
					}
				}
			}else
			{
				if($invoice->hasIva)
				{
					$total += $invoice->total * ($invoice->iva/100);
					echo "     +".(($invoice->total * ($invoice->iva/100)))."<br>";
					echo "  total:".$total."<br>";
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					foreach($details as $key => $detail){
						$total += ($detail->subtotal - ($detail->quantity * $detail->price_base));
						echo "    ".$detail->productId." pre:".$detail->unit." subt:".$detail->subtotal." q:".$detail->quantity." base:".$detail->price_base."<br>";
						echo "      +".(($detail->subtotal - ($detail->quantity * $detail->price_base)))."<br>";
						echo "  total:".$total."<br>";
					}
				}
			}
		}

		echo "--------------------------------<br>";

		$vouchers = $CI->vouchers_model->getVendorPaidVouchers($vendor);
		$vtotal = 0;

		foreach($vouchers as $key => $voucher){
			$vtotal += ($voucher->value);
			echo "    id:".$voucher->idVoucher." pre:".$voucher->value."<br>";
			echo "      -".(($voucher->value))."<br>";
		}
		echo "     vtotal:".$vtotal."<br>";
		$vouchersTotal = $CI->vouchers_model->getVendorPaidVouchersTotal($vendor);
		print_r($vouchersTotal);
		echo "<br>";

		$total -= $vouchersTotal->total;
		echo "  total:".$total."<br>";
	}

	function getVendorSettlementView($vendor)
	{
		$CI =& get_instance();

		$invoices = $CI->invoices_model->getVendorPaidInvoices($vendor);
		$html = "";
		$total = 0;
		foreach ($invoices as $key => $invoice) {
			$html .= "<p class='mx-auto text-gray-700'><span class='font-bold'>Factura #".str_pad($invoice->idInvoice, 6, "0", STR_PAD_LEFT)."</span></p> <p class='mx-auto text-gray-700'><span class='font-bold'>Cliente:</span> ".$invoice->client_name."</p> <p class='mx-auto text-gray-700'><span class='font-bold'>Total:</span> $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->total)), 2)."   ".($invoice->hasIva ? "<span class='font-bold'>Con IVA</span>" : '')."</p><br>";
			if($invoice->clientId == $vendor)
			{
				if($invoice->hasIva)
				{
					$total -= ($invoice->total * ($invoice->iva/100));
					$html .=  "<p class='mx-auto text-green-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($invoice->total * ($invoice->iva/100)))), 2)."</p><br>";
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					$html .= '<div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="w-full overflow-x-auto">
                        <table class="stripped-table w-full whitespace-no-wrap mt-8 lg:mt-0">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <td class="px-4 py-3 hidden sm:table-cell">Producto</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Valor</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Subtotal</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Cantidad</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Precio Base</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Diferencia</td>
                            </tr>
                          </thead>
                          <tbody id="tborders" class="bg-white divide-y">';
					foreach($details as $key => $detail){
						$total -= ($detail->subtotal - ($detail->quantity * $detail->price_base));
						$html .= '<tr class="text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 lg:mb-0">
                                  <td class="px-4 py-3 text-sm whitespace-normal w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Producto</span>
                                    '.$detail->productId.'
                                  </td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Valor</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->unit)), 2).'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->subtotal)), 2).'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cantidad</span>
                                    '.$detail->quantity.'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->price_base)), 2).'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto text-orange-700 block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    - $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($detail->subtotal - ($detail->quantity * $detail->price_base)))), 2).'
                                  </td>
                                </tr>';
						
					}
					$html .= '</tbody>
                        </table>
                      </div>
                    </div>';
				}
			}else
			{
				if($invoice->hasIva)
				{
					$total += $invoice->total * ($invoice->iva/100);
					$html .=  "<p class='mx-auto text-green-700'>    + $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($invoice->total * ($invoice->iva/100)))), 2)."</p><br>";
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					$html .= '<div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="w-full overflow-x-auto">
                        <table class="stripped-table w-full whitespace-no-wrap mt-8 lg:mt-0">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <td class="px-4 py-3 hidden sm:table-cell">Producto</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Valor</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Subtotal</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Cantidad</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Precio Base</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Diferencia</td>
                            </tr>
                          </thead>
                          <tbody id="tborders" class="bg-white divide-y">';
					foreach($details as $key => $detail){
						$total += ($detail->subtotal - ($detail->quantity * $detail->price_base));
						$html .= '<tr class="text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 lg:mb-0">
                                  <td class="px-4 py-3 text-sm whitespace-normal w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Producto</span>
                                    '.$detail->productId.'
                                  </td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Valor</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->unit)), 2).'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->subtotal)), 2).'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cantidad</span>
                                    '.$detail->quantity.'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->price_base)), 2).'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto text-green-700 block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    + $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($detail->subtotal - ($detail->quantity * $detail->price_base)))), 2).'
                                  </td>
                                </tr>';
					}
					$html .= '</tbody>
                        </table>
                      </div>
                    </div>';
				}
			}
		}

		$html .= "<br><br><p class='mx-auto text-gray-700'>Vales</p>";

		$vouchers = $CI->vouchers_model->getVendorPaidVouchers($vendor);
		$vtotal = 0;

		$html .= '<div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="w-full overflow-x-auto">
                        <table class="stripped-table w-full whitespace-no-wrap mt-8 lg:mt-0">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <td class="px-4 py-3 hidden sm:table-cell">Id</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Valor</td>
                            </tr>
                          </thead>
                          <tbody id="tborders" class="bg-white divide-y">';
		foreach($vouchers as $key => $voucher){
			$vtotal += ($voucher->value);
			$html .= '<tr class="text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 lg:mb-0">
                      <td class="px-4 py-3 text-sm whitespace-normal w-full sm:w-auto block sm:table-cell relative sm:static">
                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Id</span>
                        '.$voucher->idVoucher.'
                      </td>
                     <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Valor</span>
                        $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $voucher->value)), 2).'
                      </td>
                    </tr>';
		}
		$html .= '</tbody>
                        </table>
                      </div>
                    </div>';

		$html .= "<br><br><p class='mx-auto text-orange-700'>     Total Vales: $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $vtotal)), 2)."</p><br>";
		$vouchersTotal = $CI->vouchers_model->getVendorPaidVouchersTotal($vendor);
		$total -= $vouchersTotal->total;
		$html .= "<p class='mx-auto ".($total > 0 ? 'text-green-700' : 'text-orange-700')." font-bold'>  Total: $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$total)), 2)."</p><br>";
		return $html;
	}
