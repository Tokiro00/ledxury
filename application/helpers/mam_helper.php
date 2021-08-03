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

function sendEmail($to, $subject, $message)
{
	$CI =& get_instance();

	$config = array();
	$config['protocol'] = 'smtp';
	$config['smtp_host'] = 'ssl://smtp.googlemail.com';
	//$config['smtp_crypto'] = 'ssl';
	$config['smtp_port'] = 465;
	$config['smtp_user'] = 'asistenciamam@gmail.com';
	$config['smtp_pass'] = 'q49ZK34*M4M';
	$config['mailtype'] = 'html';
	//$config['charset'] = 'iso-8859-1';
	$config['charset'] = 'utf-8';
	//$config['wordwrap'] = 'TRUE';
	$config['newline'] = "\r\n";
	//$config['crlf'] = "\r\n";
	$CI->email->initialize($config);

	$CI->email->from('asistenciamam@gmail.com', 'Admin');
    $CI->email->to($to);
    $CI->email->subject($subject);
    $CI->email->message($message);
    $res = $CI->email->send();
    
}

	function getVendorSettlement($vendor)
	{
		$CI =& get_instance();

		$invoices = $CI->invoices_model->getVendorPaidInvoices($vendor);
		$total = 0;
		$totaldisc = 0;
		$totaliva = 0;
		$totalnoiva = 0;
		$alert = false;
		foreach ($invoices as $key => $invoice) {
			if($invoice->clientId == $vendor)
			{
				if($invoice->discount > 0)
				{
					$total -= ($invoice->total - $invoice->discount) * (0.1);
					$totaldisc -= ($invoice->total - $invoice->discount) * (0.1);
				}else
				if($invoice->e_commerce)
				{
					$total -= $invoice->total * (0.15);
					$totaliva -= $invoice->total * (0.15);
				}else
				if($invoice->hasIva)
				{
					$total -= $invoice->total * ($invoice->iva/100);
					$totaliva -= $invoice->total * ($invoice->iva/100);
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					foreach($details as $key => $detail){
						if(!$detail->reviewed && $detail->base >= $detail->unit)
						{
							$alert = true;
						}
						$total -= ($detail->subtotal - ($detail->quantity * $detail->base));
						$totalnoiva -= ($detail->subtotal - ($detail->quantity * $detail->base));
					}
				}
			}else
			{
				if($invoice->discount > 0)
				{
					$total += ($invoice->total - $invoice->discount) * (0.1);
					$totaldisc += ($invoice->total - $invoice->discount) * (0.1);
				}else
				if($invoice->e_commerce)
				{
					$total += $invoice->total * (0.15);
					$totaliva += $invoice->total * (0.15);
				}else
				if($invoice->hasIva)
				{
					$total += $invoice->total * ($invoice->iva/100);
					$totaliva += $invoice->total * ($invoice->iva/100);
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					foreach($details as $key => $detail){
						if(!$detail->reviewed && $detail->base >= $detail->unit)
						{
							$alert = true;
						}
						$total += ($detail->subtotal - ($detail->quantity * $detail->base));
						$totalnoiva += ($detail->subtotal - ($detail->quantity * $detail->base));
					}
				}
			}
		}

		$vouchersTotal = $CI->vouchers_model->getVendorPaidVouchersTotal($vendor);

		$total -= $vouchersTotal->total;

		$result = new stdClass();
		$result->total = $total;
		$result->totaldisc = $totaldisc;
		$result->totaliva = $totaliva;
		$result->totalnoiva = $totalnoiva;
		$result->alert = $alert;
		//echo "  total:".$total."<br>";
		return $result;
	}

	function getVendorPossibleSettlement($vendor)
	{
		$CI =& get_instance();

		$invoices = $CI->invoices_model->getVendorNonPaidInvoices($vendor);
		$total = 0;
		$totaldisc = 0;
		$totaliva = 0;
		$totalnoiva = 0;
		$alert = false;
		foreach ($invoices as $key => $invoice) {
			if($invoice->clientId == $vendor)
			{
				if($invoice->discount > 0)
				{
					$total -= ($invoice->total - $invoice->discount) * (0.1);
					$totaldisc -= ($invoice->total - $invoice->discount) * (0.1);
				}else
				if($invoice->e_commerce)
				{
					$total -= $invoice->total * (0.15);
					$totaliva -= $invoice->total * (0.15);
				}else
				if($invoice->hasIva)
				{
					$total -= $invoice->total * ($invoice->iva/100);
					$totaliva -= $invoice->total * ($invoice->iva/100);
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					foreach($details as $key => $detail){
						if(!$detail->reviewed && $detail->base >= $detail->unit)
						{
							$alert = true;
						}
						$total -= ($detail->subtotal - ($detail->quantity * $detail->base));
						$totalnoiva -= ($detail->subtotal - ($detail->quantity * $detail->base));
					}
				}
			}else
			{
				if($invoice->discount > 0)
				{
					$total += ($invoice->total - $invoice->discount) * (0.1);
					$totaldisc += ($invoice->total - $invoice->discount) * (0.1);
				}else
				if($invoice->e_commerce)
				{
					$total += $invoice->total * (0.15);
					$totaliva += $invoice->total * (0.15);
				}else
				if($invoice->hasIva)
				{
					$total += $invoice->total * ($invoice->iva/100);
					$totaliva += $invoice->total * ($invoice->iva/100);
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					foreach($details as $key => $detail){
						if(!$detail->reviewed && $detail->base >= $detail->unit)
						{
							$alert = true;
						}
						$total += ($detail->subtotal - ($detail->quantity * $detail->base));
						$totalnoiva += ($detail->subtotal - ($detail->quantity * $detail->base));
					}
				}
			}
		}

		$vouchersTotal = $CI->vouchers_model->getVendorPaidVouchersTotal($vendor);

		$total -= $vouchersTotal->total;

		$result = new stdClass();
		$result->total = $total;
		$result->totaldisc = $totaldisc;
		$result->totaliva = $totaliva;
		$result->totalnoiva = $totalnoiva;
		$result->alert = $alert;
		//echo "  total:".$total."<br>";
		return $result;
	}

	function getVendorTotalSettlement($vendor)
	{
		$CI =& get_instance();

		$invoices = $CI->invoices_model->getVendorInvoices($vendor);
		$total = 0;
		$totaldisc = 0;
		$totaliva = 0;
		$totalnoiva = 0;
		$alert = false;
		foreach ($invoices as $key => $invoice) {
			if($invoice->clientId == $vendor)
			{
				if($invoice->discount > 0)
				{
					$total -= ($invoice->total - $invoice->discount) * (0.1);
					$totaldisc -= ($invoice->total - $invoice->discount) * (0.1);
				}else
				if($invoice->e_commerce)
				{
					$total -= $invoice->total * (0.15);
					$totaliva -= $invoice->total * (0.15);
				}else
				if($invoice->hasIva)
				{
					$total -= $invoice->total * ($invoice->iva/100);
					$totaliva -= $invoice->total * ($invoice->iva/100);
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					foreach($details as $key => $detail){
						if(!$detail->reviewed && $detail->base >= $detail->unit)
						{
							$alert = true;
						}
						$total -= ($detail->subtotal - ($detail->quantity * $detail->base));
						$totalnoiva -= ($detail->subtotal - ($detail->quantity * $detail->base));
					}
				}
			}else
			{
				if($invoice->discount > 0)
				{
					$total += ($invoice->total - $invoice->discount) * (0.1);
					$totaldisc += ($invoice->total - $invoice->discount) * (0.1);
				}else
				if($invoice->e_commerce)
				{
					$total += $invoice->total * (0.15);
					$totaliva += $invoice->total * (0.15);
				}else
				if($invoice->hasIva)
				{
					$total += $invoice->total * ($invoice->iva/100);
					$totaliva += $invoice->total * ($invoice->iva/100);
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					foreach($details as $key => $detail){
						if(!$detail->reviewed && $detail->base >= $detail->unit)
						{
							$alert = true;
						}
						$total += ($detail->subtotal - ($detail->quantity * $detail->base));
						$totalnoiva += ($detail->subtotal - ($detail->quantity * $detail->base));
					}
				}
			}
		}

		$vouchersTotal = $CI->vouchers_model->getVendorPaidVouchersTotal($vendor);

		$total -= $vouchersTotal->total;

		$result = new stdClass();
		$result->total = $total;
		$result->totaldisc = $totaldisc;
		$result->totaliva = $totaliva;
		$result->totalnoiva = $totalnoiva;
		$result->alert = $alert;
		//echo "  total:".$total."<br>";
		return $result;
	}

	function getVendorSettlementViewUgly($vendor)
	{
		$CI =& get_instance();
		
		$invoices = $CI->invoices_model->getVendorPaidInvoices($vendor);
		$total = 0;
		$alert = false;
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
						$total -= ($detail->subtotal - ($detail->quantity * $detail->base));
						echo "    ".$detail->productId." pre:".$detail->unit." subt:".$detail->subtotal." q:".$detail->quantity." base:".$detail->base."<br>";
						echo "      -".(($detail->subtotal - ($detail->quantity * $detail->base)))."<br>";
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
						$total += ($detail->subtotal - ($detail->quantity * $detail->base));
						echo "    ".$detail->productId." pre:".$detail->unit." subt:".$detail->subtotal." q:".$detail->quantity." base:".$detail->base."<br>";
						echo "      +".(($detail->subtotal - ($detail->quantity * $detail->base)))."<br>";
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
		$totalfact = 0;
		$totaldisc = 0;
		$totaliva = 0;
		$totalec = 0;
		$totalnoiva = 0;
		foreach ($invoices as $key => $invoice) {
			if(!empty($html)) $html .= "<hr class='mt-6 mb-4 border-t-2 border-gray-500'>";
			$html .= "<p class='mx-auto text-gray-700'><span class='font-bold'>Factura #".str_pad($invoice->idInvoice, 6, "0", STR_PAD_LEFT)."</span></p><p class='mx-auto text-gray-700'><span class='font-bold'>Fecha Emisión:</span> ".$invoice->date."</p><p class='mx-auto text-gray-700'><span class='font-bold'>Fecha Pago:</span> ".$CI->invoices_model->getInvoicePaymentDate($invoice->idInvoice)->date."</p> <p class='mx-auto text-gray-700'><span class='font-bold'>Cliente:</span> ".$invoice->client_name."</p> <p class='mx-auto text-gray-700'><span class='font-bold'>Total:</span> $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->total)), 2)."   ".($invoice->e_commerce ? "<span class='font-bold'>Venta por E-commerce</span>" : ($invoice->hasIva ? "<span class='font-bold'>Con IVA</span>" : ''))."   ".($invoice->discount > 0 ? "<span class='font-bold'>Con Descuento $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->discount)), 2)."</span>" : '')."</p><br>";
			$totalfact += $invoice->total;
			if($invoice->clientId == $vendor)
			{
				if($invoice->discount > 0)
				{
					$total -= ($invoice->total - $invoice->discount) * (0.1);
					$totaldisc -= ($invoice->total - $invoice->discount) * (0.1);
					$html .=  "<p class='mx-auto font-bold text-green-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", (($invoice->total - $invoice->discount) * (0.1)))), 2)."</p><br>";
				}else
				if($invoice->e_commerce)
				{
					$total -= ($invoice->total * (0.15));
					$totalec -= ($invoice->total * (0.15));
					$html .=  "<p class='mx-auto font-bold text-green-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($invoice->total * (0.15)))), 2)."</p><br>";
				}else
				if($invoice->hasIva)
				{
					$total -= ($invoice->total * ($invoice->iva/100));
					$totaliva -= ($invoice->total * ($invoice->iva/100));
					$html .=  "<p class='mx-auto font-bold text-green-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($invoice->total * ($invoice->iva/100)))), 2)."</p><br>";
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					$html .= '<div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="w-full overflow-x-auto">
                        <table class="stripped-table w-full whitespace-no-wrap mt-8 lg:mt-0">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <td class="px-4 py-3 hidden sm:table-cell">Producto</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Precio Venta</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Cantidad</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Subtotal</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Precio Base</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Diferencia</td>
                            </tr>
                          </thead>
                          <tbody id="tborders" class="bg-white divide-y">';
                     $detailTotal = 0;
					foreach($details as $key => $detail){
						$alert = "";
						if(!$detail->reviewed && $detail->base >= $detail->unit)
						{
							$alert = '<a href="'.base_url().'sisvent/commercial/invoices/edit/'.$invoice->idInvoice.'" target="_blank"><svg class="w-6 h-6" fill="none" stroke="red" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></a>';
						}
						$total -= ($detail->subtotal - ($detail->quantity * $detail->base));
						$totalnoiva -= ($detail->subtotal - ($detail->quantity * $detail->base));
						$html .= '<tr class="text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 lg:mb-0">
                                  <td class="px-4 py-3 text-sm whitespace-normal w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Producto</span>
                                    '.$detail->productId.'
                                  </td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio Venta</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->unit)), 2).'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cantidad</span>
                                    '.$detail->quantity.'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->subtotal)), 2).'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->base)), 2).$alert.'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto text-orange-700 block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    - $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($detail->subtotal - ($detail->quantity * $detail->base)))), 2).'
                                  </td>
                                </tr>';

                            $detailTotal += ($detail->subtotal - ($detail->quantity * $detail->base));
						
					}
					$html .= '<tr class="text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 lg:mb-0">
                                 <td class="px-4 py-3 text-sm whitespace-normal w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                 <td class="px-4 py-3 w-full sm:w-auto font-bold text-orange-700 block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    - $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detailTotal)), 2).'
                                 </td>
                                </tr>';

					$html .= '</tbody>
                        </table>
                      </div>
                    </div>';
				}
			}else
			{
				if($invoice->discount > 0)
				{
					$total += ($invoice->total - $invoice->discount) * (0.1);
					$totaldisc += ($invoice->total - $invoice->discount) * (0.1);
					$html .=  "<p class='mx-auto font-bold text-green-700'>    + $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", (($invoice->total - $invoice->discount) * (0.1)))), 2)."</p><br>";
				}else
				if($invoice->e_commerce)
				{
					$total += $invoice->total * (0.15);
					$totalec += $invoice->total * (0.15);
					$html .=  "<p class='mx-auto font-bold text-green-700'>    + $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($invoice->total * (0.15)))), 2)."</p><br>";
				}else
				if($invoice->hasIva)
				{
					$total += $invoice->total * ($invoice->iva/100);
					$totaliva += $invoice->total * ($invoice->iva/100);
					$html .=  "<p class='mx-auto font-bold text-green-700'>    + $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($invoice->total * ($invoice->iva/100)))), 2)."</p><br>";
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					$html .= '<div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="w-full overflow-x-auto">
                        <table class="stripped-table w-full whitespace-no-wrap mt-8 lg:mt-0">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <td class="px-4 py-3 hidden sm:table-cell">Producto</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Precio Venta</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Cantidad</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Subtotal</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Precio Base</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Diferencia</td>
                            </tr>
                          </thead>
                          <tbody id="tborders" class="bg-white divide-y">';
                     $detailTotal = 0;
					foreach($details as $key => $detail){
						$alert = "";
						if(!$detail->reviewed && $detail->base >= $detail->unit)
						{
							$alert = '<a href="'.base_url().'sisvent/commercial/invoices/edit/'.$invoice->idInvoice.'" target="_blank"><svg class="w-6 h-6" fill="none" stroke="red" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></a>';
						}
						$total += ($detail->subtotal - ($detail->quantity * $detail->base));
						$totalnoiva += ($detail->subtotal - ($detail->quantity * $detail->base));
						$html .= '<tr class="text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 lg:mb-0">
                                  <td class="px-4 py-3 text-sm whitespace-normal w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Producto</span>
                                    '.$detail->productId.'
                                  </td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio Venta</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->unit)), 2).'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cantidad</span>
                                    '.$detail->quantity.'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->subtotal)), 2).'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->base)), 2).$alert.'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto '.(($detail->subtotal - ($detail->quantity * $detail->base)) >= 0 ? 'text-green-700' : 'text-orange-700').' block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    '.(($detail->subtotal - ($detail->quantity * $detail->base)) >= 0 ? '+' : '-').' $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($detail->subtotal - ($detail->quantity * $detail->base)))), 2).'
                                  </td>
                                </tr>';
                        $detailTotal += ($detail->subtotal - ($detail->quantity * $detail->base));

					}
					$html .= '<tr class="text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 lg:mb-0">
                                  <td class="px-4 py-3 text-sm whitespace-normal w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                  <td class="px-4 py-3 w-full sm:w-auto font-bold '.($detailTotal >= 0 ? 'text-green-700' : 'text-orange-700').' block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    '.($detailTotal >= 0 ? '+' : '-').' $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detailTotal)), 2).'
                                  </td>
                                </tr>';
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
                              <td class="px-4 py-3 hidden sm:table-cell">Fecha</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Observaciones</td>
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
                      <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Fecha</span>
                        '.$voucher->created_at.'
                      </td>
                      <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Observaciones</span>
                        '.$voucher->description.'
                      </td>
                    </tr>';
		}
		$html .= '</tbody>
                        </table>
                      </div>
                    </div>';

        $html .= "<br><br>";
        if($totaliva != 0) $html .= "<p class='mx-auto ".($totaliva >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total IVA: ".($totaliva >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totaliva)), 2)."</p>";
        if($totalec != 0) $html .= "<p class='mx-auto ".($totalec >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total E-commerce: ".($totalec >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totalec)), 2)."</p>";
		if($totalnoiva != 0) $html .= "<p class='mx-auto ".($totalnoiva >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total Remisiones: ".($totalnoiva >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totalnoiva)), 2)."</p>";
		if($totaldisc != 0) $html .= "<p class='mx-auto ".($totaldisc >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total Descuento: ".($totaldisc >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totaldisc)), 2)."</p>";
		$html .= "<p class='mx-auto text-green-700 font-bold'>     Total Comisiones: $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totaliva+$totalnoiva+$totalec+$totaldisc)), 2)." Correspondiente al ".number_format((($totaliva+$totalnoiva+$totalec+$totaldisc)/$totalfact*100), 2)."%</p>";
		$html .= "<p class='mx-auto text-orange-700'>     Total Vales: -$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $vtotal)), 2)."</p><br>";
		$vouchersTotal = $CI->vouchers_model->getVendorPaidVouchersTotal($vendor);
		$total -= $vouchersTotal->total;
		$html .= "<p class='mx-auto font-bold'>  Total Facturado: ".($totalfact >= 0 ? '' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$totalfact)), 2)."</p><br>";
		$html .= "<p class='mx-auto ".($total > 0 ? 'text-green-700' : 'text-orange-700')." font-bold'>  Total: ".($total >= 0 ? '' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$total)), 2)."</p><br>";
		return $html;
	}

	function getVendorSettlementTotalView($vendor)
	{
		$CI =& get_instance();

		$invoices = $CI->invoices_model->getVendorInvoices($vendor);
		$html = "";
		$total = 0;
		$totalfact = 0;
		$totaldisc = 0;
		$totaliva = 0;
		$totalec = 0;
		$totalnoiva = 0;
		foreach ($invoices as $key => $invoice) {
			if(!empty($html)) $html .= "<hr class='mt-6 mb-4 border-t-2 border-gray-500'>";
			$html .= "<p class='mx-auto text-gray-700'><span class='font-bold'>Factura #".str_pad($invoice->idInvoice, 6, "0", STR_PAD_LEFT)."</span></p><p class='mx-auto text-gray-700'><span class='font-bold'>Fecha:</span> ".$invoice->date."</p><p class='mx-auto text-gray-700'><span class='font-bold'>Fecha Pago:</span> ".$CI->invoices_model->getInvoicePaymentDate($invoice->idInvoice)->date."</p><p class='mx-auto text-gray-700'><span class='font-bold'>Cliente:</span> ".$invoice->client_name."</p> <p class='mx-auto text-gray-700'><span class='font-bold'>Total:</span> $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->total)), 2)."   ".($invoice->e_commerce ? "<span class='font-bold'>Venta con E-commerce</span>" : ($invoice->hasIva ? "<span class='font-bold'>Con IVA</span>" : ''))."   ".($invoice->discount > 0 ? "<span class='font-bold'>Con Descuento $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->discount)), 2)."</span>" : '')."</p><br>";
			$totalfact += $invoice->total;
			if($invoice->clientId == $vendor)
			{
				if($invoice->discount > 0)
				{
					$total -= ($invoice->total - $invoice->discount) * (0.1);
					$totaldisc -= ($invoice->total - $invoice->discount) * (0.1);
					$html .=  "<p class='mx-auto font-bold text-green-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", (($invoice->total - $invoice->discount) * (0.1)))), 2)."</p><br>";
				}else
				if($invoice->e_commerce)
				{
					$total -= ($invoice->total * (0.15));
					$totalec -= ($invoice->total * (0.15));
					$html .=  "<p class='mx-auto font-bold text-green-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($invoice->total * (0.15)))), 2)."</p><br>";
				}else
				if($invoice->hasIva)
				{
					$total -= ($invoice->total * ($invoice->iva/100));
					$totaliva -= ($invoice->total * ($invoice->iva/100));
					$html .=  "<p class='mx-auto font-bold text-green-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($invoice->total * ($invoice->iva/100)))), 2)."</p><br>";
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					$html .= '<div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="w-full overflow-x-auto">
                        <table class="stripped-table w-full whitespace-no-wrap mt-8 lg:mt-0">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <td class="px-4 py-3 hidden sm:table-cell">Producto</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Precio Venta</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Cantidad</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Subtotal</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Precio Base</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Diferencia</td>
                            </tr>
                          </thead>
                          <tbody id="tborders" class="bg-white divide-y">';
                     $detailTotal = 0;
					foreach($details as $key => $detail){
						$alert = "";
						if(!$detail->reviewed && $detail->base >= $detail->unit)
						{
							$alert = '<a href="'.base_url().'sisvent/commercial/invoices/edit/'.$invoice->idInvoice.'" target="_blank"><svg class="w-6 h-6" fill="none" stroke="red" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></a>';
						}
						$total -= ($detail->subtotal - ($detail->quantity * $detail->base));
						$totalnoiva -= ($detail->subtotal - ($detail->quantity * $detail->base));
						$html .= '<tr class="text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 lg:mb-0">
                                  <td class="px-4 py-3 text-sm whitespace-normal w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Producto</span>
                                    '.$detail->productId.'
                                  </td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio Venta</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->unit)), 2).'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cantidad</span>
                                    '.$detail->quantity.'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->subtotal)), 2).'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->base)), 2).$alert.'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto text-orange-700 block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    - $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($detail->subtotal - ($detail->quantity * $detail->base)))), 2).'
                                  </td>
                                </tr>';
						
                        $detailTotal += ($detail->subtotal - ($detail->quantity * $detail->base));
					}
					$html .= '<tr class="text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 lg:mb-0">
                                 <td class="px-4 py-3 text-sm whitespace-normal w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                 <td class="px-4 py-3 w-full sm:w-auto font-bold text-orange-700 block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    - $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detailTotal)), 2).'
                                 </td>
                                </tr>';
					$html .= '</tbody>
                        </table>
                      </div>
                    </div>';
				}
			}else
			{
				if($invoice->discount > 0)
				{
					$total += ($invoice->total - $invoice->discount) * (0.1);
					$totaldisc += ($invoice->total - $invoice->discount) * (0.1);
					$html .=  "<p class='mx-auto font-bold text-green-700'>    + $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", (($invoice->total - $invoice->discount) * (0.1)))), 2)."</p><br>";
				}else
				if($invoice->e_commerce)
				{
					$total += $invoice->total * (0.15);
					$totalec += $invoice->total * (0.15);
					$html .=  "<p class='mx-auto font-bold text-green-700'>    + $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($invoice->total * (0.15)))), 2)."</p><br>";
				}else
				if($invoice->hasIva)
				{
					$total += $invoice->total * ($invoice->iva/100);
					$totaliva += $invoice->total * ($invoice->iva/100);
					$html .=  "<p class='mx-auto font-bold text-green-700'>    + $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($invoice->total * ($invoice->iva/100)))), 2)."</p><br>";
				}else
				{
					$details = $CI->invoices_model->getDetails($invoice->idInvoice);
					$html .= '<div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="w-full overflow-x-auto">
                        <table class="stripped-table w-full whitespace-no-wrap mt-8 lg:mt-0">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <td class="px-4 py-3 hidden sm:table-cell">Producto</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Precio Venta</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Cantidad</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Subtotal</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Precio Base</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Diferencia</td>
                            </tr>
                          </thead>
                          <tbody id="tborders" class="bg-white divide-y">';
                     $detailTotal = 0;
					foreach($details as $key => $detail){
						$alert = "";
						if(!$detail->reviewed && $detail->base >= $detail->unit)
						{
							$alert = '<a href="'.base_url().'sisvent/commercial/invoices/edit/'.$invoice->idInvoice.'" target="_blank"><svg class="w-6 h-6" fill="none" stroke="red" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></a>';
						}
						$total += ($detail->subtotal - ($detail->quantity * $detail->base));
						$totalnoiva += ($detail->subtotal - ($detail->quantity * $detail->base));
						$html .= '<tr class="text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 lg:mb-0">
                                  <td class="px-4 py-3 text-sm whitespace-normal w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Producto</span>
                                    '.$detail->productId.'
                                  </td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio Venta</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->unit)), 2).'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cantidad</span>
                                    '.$detail->quantity.'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->subtotal)), 2).'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->base)), 2).$alert.'
                                  </td>
                                  <td class="px-4 py-3 w-full sm:w-auto '.(($detail->subtotal - ($detail->quantity * $detail->base)) >= 0 ? 'text-green-700' : 'text-orange-700').' block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    '.(($detail->subtotal - ($detail->quantity * $detail->base)) >= 0 ? '+' : '-').' $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($detail->subtotal - ($detail->quantity * $detail->base)))), 2).'
                                  </td>
                                </tr>';
                        $detailTotal += ($detail->subtotal - ($detail->quantity * $detail->base));
					}
					$html .= '<tr class="text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 lg:mb-0">
                                  <td class="px-4 py-3 text-sm whitespace-normal w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                  <td class="px-4 py-3 w-full sm:w-auto font-bold '.($detailTotal >= 0 ? 'text-green-700' : 'text-orange-700').' block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    '.($detailTotal >= 0 ? '+' : '-').' $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detailTotal)), 2).'
                                  </td>
                                </tr>';
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
                              <td class="px-4 py-3 hidden sm:table-cell">Fecha</td>
                              <td class="px-4 py-3 hidden sm:table-cell">Observaciones</td>
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
                      <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Fecha</span>
                        '.$voucher->created_at.'
                      </td>
                      <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static">
                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Observaciones</span>
                        '.$voucher->description.'
                      </td>
                    </tr>';
		}
		$html .= '</tbody>
                        </table>
                      </div>
                    </div>';

        $html .= "<br><br>";
        if($totaliva != 0) $html .= "<p class='mx-auto ".($totaliva >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total IVA: ".($totaliva >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totaliva)), 2)."</p>";
        if($totalec != 0) $html .= "<p class='mx-auto ".($totalec >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total E-commerce: ".($totalec >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totalec)), 2)."</p>";
		if($totalnoiva != 0) $html .= "<p class='mx-auto ".($totalnoiva >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total Remisiones: ".($totalnoiva >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totalnoiva)), 2)."</p>";
		if($totaldisc != 0) $html .= "<p class='mx-auto ".($totaldisc >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total Descuento: ".($totaldisc >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totaldisc)), 2)."</p>";
		$html .= "<p class='mx-auto text-green-700 font-bold'>     Total Comisiones: $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totaliva+$totalnoiva+$totalec+$totaldisc)), 2)." Correspondiente al ".number_format((($totaliva+$totalnoiva+$totalec+$totaldisc)/$totalfact*100), 2)."%</p>";
		$html .= "<p class='mx-auto text-orange-700'>     Total Vales: -$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $vtotal)), 2)."</p><br>";
		$vouchersTotal = $CI->vouchers_model->getVendorPaidVouchersTotal($vendor);
		$total -= $vouchersTotal->total;
		$html .= "<p class='mx-auto font-bold'>  Total Facturado: ".($totalfact >= 0 ? '' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$totalfact)), 2)."</p><br>";
		$html .= "<p class='mx-auto ".($total > 0 ? 'text-green-700' : 'text-orange-700')." font-bold'>  Total: ".($total >= 0 ? '' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$total)), 2)."</p><br>";
		return $html;
	}


	function createLinks($page, $total, $params, $limit = 20, $links = 2 ) {
	    	 
	    $last       = ceil( $total / $limit );
	 
	    $start      = ( ( $page - $links ) > 0 ) ? $page - $links : 1;
	    $end        = ( ( $page + $links ) < $last ) ? $page + $links : $last;
	 
	    $html       = '<ul class="inline-flex items-center">';
	 
	    //$class      = ( $page == 1 ) ? "disabled" : "";
	    $html       .= '<li><a class="px-3 py-1 rounded-lg rounded-l-lg"'. (( $page == 1 ) ? '' : 'href="?p=' . ( $page - 1 ) .$params. '"').'><svg aria-hidden="true" class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" fill-rule="evenodd"></path></svg></a></li>';
	 
	    if ( $start > 1 ) {
	        $html   .= '<li><a class="px-3 py-1 rounded-lg" href="?p=1">1</a></li>';
	        $html   .= '<li class="disabled"><span>...</span></li>';
	    }
	 
	    for ( $i = $start ; $i <= $end; $i++ ) {
	        $class  = ( $page == $i ) ? "text-white transition-colors duration-150 bg-mam-blue-dark" : "";
	        $html   .= '<li class=""><a class="px-3 py-1 ' . $class . ' rounded-lg" href="?p=' . $i .$params. '">' . $i . '</a></li>';
	    }
	 
	    if ( $end < $last ) {
	        $html   .= '<li class="disabled"><span>...</span></li>';
	        $html   .= '<li><a class="px-3 py-1 rounded-lg" href="?p=' . $last .$params. '">' . $last . '</a></li>';
	    }
	 
	    //$class      = ( $page == $last ) ? "disabled" : "";
	    $html       .= '<li><a class="px-3 py-1 rounded-lg rounded-l-lg"'. (( $page == $last ) ? '' : 'href="?p=' . ( $page + 1 ) .$params. '"').'><svg class="w-4 h-4 fill-current" aria-hidden="true" viewBox="0 0 20 20"><path d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" fill-rule="evenodd"></path></svg></a></li>';
	 
	    $html       .= '</ul>';
	 
	    return $html;
	}

	function createParamsLinks($store, $vendor, $state, $client, $iva = "all"  ) {
	    	 
	    $params = "";

	    if($store != "all")
        {
          //if (!empty($params))
          //  //$params = "?"
          ////else
          //  $params .= "&"
          $params .= "&str=".$store;
        }
        if($vendor != "all")
        {
          //if (!empty($params)) 
          //  //$params = "?"
          ////else
          //  $params .= "&"
          $params .= "&v=".$vendor;
        }
        if($state != "all")
        {
          //if (!empty($params)) 
          //  //$params = "?"
          ////else
          //  $params .= "&"
          $params .= "&ste=".$state;
        }
        if($client != "all")
        {
          //if (!empty($params)) 
          //  //$params = "?"
          ////else
          //  $params .= "&"
          $params .= "&c=".$client;
        }
	 	if($iva != "all")
        {
          //if (!empty($params)) 
          //  //$params = "?"
          ////else
          //  $params .= "&"
          $params .= "&i=".$iva;
        }
        
	    return $params;
	}

	function createFullParamsLinks($page, $store = "all", $vendor = "all", $state = "all", $client = "all", $iva = "all" ) {
	    	 
	    $params = "";

        $params .= "?p=".$page;

	    if($store != "all")
        {
          //if (!empty($params))
          //  //$params = "?"
          ////else
          //  $params .= "&"
          $params .= "&str=".$store;
        }
        if($vendor != "all")
        {
          //if (!empty($params)) 
          //  //$params = "?"
          ////else
          //  $params .= "&"
          $params .= "&v=".$vendor;
        }
        if($state != "all")
        {
          //if (!empty($params)) 
          //  //$params = "?"
          ////else
          //  $params .= "&"
          $params .= "&ste=".$state;
        }
        if($client != "all")
        {
          //if (!empty($params)) 
          //  //$params = "?"
          ////else
          //  $params .= "&"
          $params .= "&c=".$client;
        }
        if($iva != "all")
        {
          //if (!empty($params)) 
          //  //$params = "?"
          ////else
          //  $params .= "&"
          $params .= "&i=".$iva;
        }
	 
	    return $params;
	}