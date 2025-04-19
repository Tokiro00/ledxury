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

function checkHasPartnerPrivileges(){
	$CI =& get_instance();
	$isSuperAdmin = $CI->session->userdata('user_data')['uname'] == "00000" 
    || $CI->session->userdata('user_data')['uname'] == '6542543'//Alex
    || $CI->session->userdata('user_data')['uname'] == '71339095'//Alex
    || $CI->session->userdata('user_data')['uname'] == '98696877'//Elkin
    || $CI->session->userdata('user_data')['uname'] == '98697054'//Daniel
    || $CI->session->userdata('user_data')['uname'] == '98707053';//Julian
	return $isSuperAdmin;
}

function sendEmail($to, $subject, $message)
{
	$CI =& get_instance();

	$config = array();
	$config['protocol'] = 'smtp';
	$config['smtp_host'] = 'ssl://smtp.gmail.com';
	//$config['smtp_crypto'] = 'ssl';
	$config['smtp_port'] = 465;//25;//587;//
	$config['smtp_user'] = 'asistenciamam@gmail.com';
	$config['smtp_pass'] = 'ssgdnzicymtfkhdc';//'wokkamsemenlsmnu';//
	$config['mailtype'] = 'html';
	//$config['charset'] = 'iso-8859-1';
	$config['charset'] = 'utf-8';
	//$config['wordwrap'] = 'TRUE';
	//$config['newline'] = "\r\n";
	//$config['crlf'] = "\r\n";
	$CI->email->set_newline("\r\n");
	$CI->email->initialize($config);

	$CI->email->from('asistenciamam@gmail.com', 'Admin');
    $CI->email->to($to);
    $CI->email->subject($subject);
    $CI->email->message($message);
    $res = $CI->email->send();
    return $res;
}

	function getVendorSettlement($vendor)
	{
		$CI =& get_instance();

		$invoices = $CI->invoices_model->getVendorPaidInvoices($vendor);
		
		return calculateSettlementValues($invoices, $vendor);
	}

	function getVendorPossibleSettlement($vendor)
	{
		$CI =& get_instance();

		$invoices = $CI->invoices_model->getVendorNonPaidInvoices($vendor);
		return calculateSettlementValues($invoices, $vendor);
	}

	function getVendorTotalSettlement($vendor)
	{
		$CI =& get_instance();

		$invoices = $CI->invoices_model->getVendorInvoices($vendor);
		return calculateSettlementValues($invoices, $vendor);
	}

	function getStoreData($store)
	{
		$CI =& get_instance();
		return $CI->stores_model->getStore($store);
	}

	function calculateSettlementValues($invoices, $vendor){
		$CI =& get_instance();
		$total = 0;
		$totaldisc = 0;
		$totaliva = 0;
		$totallc = 0;
		$totallp = 0;
		$totalcom = 0;
		$totalnoiva = 0;
		$totalec = 0;
		$alert = false;
		$vend = $CI->vendors_model->getVendor($vendor);
		foreach ($invoices as $key => $invoice) {
			if(!$invoice->blacklisted)
			{
				
	    		//echo "Liquidar";
	    		$details = $CI->invoices_model->getDetails($invoice->idInvoice);
				if($invoice->clientId == $vendor)
				{
					if($invoice->legal_collection)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$total -= ($invoice->total - $not_settle_total) * (0.02);
						$totallc -= ($invoice->total - $not_settle_total) * (0.02);
					}else
					if($vend->by_commission)
					{
						if($vend->new_settlement_method)
						{
							$percentage = $vend->commission_perc/100;
							$not_settle_total = 0;
							foreach($details as $key => $detail){
								$product = $CI->products_model->getProduct($detail->productId);
								if($detail->not_settle)
								{
									$not_settle_total += $detail->subtotal;
								}
								if($detail->unit < $product->price){
									$percentage = 0.05;
								}
							}
							$total -= ($invoice->total - $not_settle_total) * ($percentage);
							$totalcom -= ($invoice->total - $not_settle_total) * ($percentage);
						}else
						{
							$not_settle_total = 0;
							foreach($details as $key => $detail){
								if($detail->not_settle)
								{
									$not_settle_total += $detail->subtotal;
								}
							}
							$total -= ($invoice->total - $not_settle_total) * ($vend->commission_perc/100);
							$totalcom -= ($invoice->total - $not_settle_total) * ($vend->commission_perc/100);
						}
					}else
					if($invoice->list_price)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$total -= (($invoice->total * 0.7) - $not_settle_total) * (0.05);
						$totallp -= (($invoice->total * 0.7) - $not_settle_total) * (0.05);
					}else
					if($invoice->discount > 0)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$total -= ($invoice->total - $not_settle_total - $invoice->discount) * ($invoice->discount_perc/100);
						$totaldisc -= ($invoice->total - $not_settle_total - $invoice->discount) * ($invoice->discount_perc/100);
					}else
					if($invoice->e_commerce)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$total -= ($invoice->total - $not_settle_total) * (0.15);
						$totalec -= ($invoice->total - $not_settle_total) * (0.15);
					}else
					if($invoice->hasIva)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$total -= ($invoice->total - $not_settle_total) * ($invoice->iva/100);
						$totaliva -= ($invoice->total - $not_settle_total) * ($invoice->iva/100);
					}else
					{
						
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								continue;
							}
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
					$detailsnat = $CI->invoices_model->getIfDetailsHasNational($invoice->idInvoice);

					if(!empty($detailsnat)){
						//echo "No Liquidar!!";

			    	}else{
						if($invoice->legal_collection)
						{
							$not_settle_total = 0;
							foreach($details as $key => $detail){
								if($detail->not_settle)
								{
									$not_settle_total += $detail->subtotal;
								}
							}
							$total += ($invoice->total - $not_settle_total) * (0.02);
							$totallc += ($invoice->total - $not_settle_total) * (0.02);
						}else
						if($vend->by_commission)
						{
							if($vend->new_settlement_method)
							{
								$percentage = $vend->commission_perc/100;
								$not_settle_total = 0;
								foreach($details as $key => $detail){
									$product = $CI->products_model->getProduct($detail->productId);
									if($detail->not_settle)
									{
										$not_settle_total += $detail->subtotal;
									}
									if($detail->unit < $product->price){
										$percentage = 0.05;
									}
								}
								$total += ($invoice->total - $not_settle_total) * ($percentage);
								$totalcom += ($invoice->total - $not_settle_total) * ($percentage);
							}else
							{
								$not_settle_total = 0;
								foreach($details as $key => $detail){
									if($detail->not_settle)
									{
										$not_settle_total += $detail->subtotal;
									}
								}
								$total += ($invoice->total - $not_settle_total) * ($vend->commission_perc/100);
								$totalcom += ($invoice->total - $not_settle_total) * ($vend->commission_perc/100);
							}
						}else
						if($invoice->list_price)
						{
							$not_settle_total = 0;
							foreach($details as $key => $detail){
								if($detail->not_settle)
								{
									$not_settle_total += $detail->subtotal;
								}
							}
							$total += (($invoice->total * 0.7) - $not_settle_total) * (0.05);
							$totallp += (($invoice->total * 0.7) - $not_settle_total) * (0.05);
						}else
						if($invoice->discount > 0)
						{
							$not_settle_total = 0;
							foreach($details as $key => $detail){
								if($detail->not_settle)
								{
									$not_settle_total += $detail->subtotal;
								}
							}
							$total += ($invoice->total - $not_settle_total - $invoice->discount) * ($invoice->discount_perc/100);
							$totaldisc += ($invoice->total - $not_settle_total - $invoice->discount) * ($invoice->discount_perc/100);
						}else
						if($invoice->e_commerce)
						{
							$not_settle_total = 0;
							foreach($details as $key => $detail){
								if($detail->not_settle)
								{
									$not_settle_total += $detail->subtotal;
								}
							}
							$total += ($invoice->total - $not_settle_total) * (0.15);
							$totalec += ($invoice->total - $not_settle_total) * (0.15);
						}else
						if($invoice->hasIva)
						{
							$not_settle_total = 0;
							foreach($details as $key => $detail){
								if($detail->not_settle)
								{
									$not_settle_total += $detail->subtotal;
								}
							}
							$total += ($invoice->total - $not_settle_total) * ($invoice->iva/100);
							$totaliva += ($invoice->total - $not_settle_total) * ($invoice->iva/100);
						}else
						{
							//$details = $CI->invoices_model->getDetails($invoice->idInvoice);
							foreach($details as $key => $detail){
								if($detail->not_settle)
								{
									continue;
								}
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
			}
		}

		$vouchersTotal = $CI->vouchers_model->getVendorPaidVouchersTotal($vendor);

		$total -= $vouchersTotal->total;

		$result = new stdClass();
		$result->total = $total;
		$result->totaldisc = $totaldisc;
		$result->totalec = $totalec;
		$result->totaliva = $totaliva;
		$result->totallc = $totallc;
		$result->totallp = $totallp;
		$result->totalcom = $totalcom;
		$result->totalnoiva = $totalnoiva;
		$result->alert = $alert;
		//echo "  total:".$total."<br>";
		return $result;
	}
	

	/*function getVendorSettlementViewUgly($vendor)
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
	}*/

	function getVendorSettlementView($vendor)
	{
		$CI =& get_instance();

		$invoices = $CI->invoices_model->getVendorPaidInvoices($vendor);

		return getSettlementHtml($invoices, $vendor);
	}

	
	function getVendorSettlementTotalView($vendor)
	{
		$CI =& get_instance();

		$invoices = $CI->invoices_model->getVendorInvoices($vendor);

		return getSettlementHtml($invoices, $vendor);
	}

	function getSettlementHtml($invoices, $vendor){
		$CI =& get_instance();
		$html = "";
		$total = 0;
		$totalfact = 0;
		$totaldisc = 0;
		$totaliva = 0;
		$totalec = 0;
		$totallc = 0;
		$totallp = 0;
		$totalcom = 0;
		$totalnoiva = 0;
		$vend = $CI->vendors_model->getVendor($vendor);
		foreach ($invoices as $key => $invoice) {
			if(!empty($html)) $html .= "<hr class='mt-6 mb-4 border-t-2 border-gray-500'>";
			$html .= "<p class='mx-auto text-gray-700'><span class='font-bold'>Factura #".str_pad($invoice->idInvoice, 6, "0", STR_PAD_LEFT)."</span></p><p class='mx-auto text-gray-700'><span class='font-bold'>Fecha Emisión:</span> ".$invoice->date."</p><p class='mx-auto text-gray-700'><span class='font-bold'>Fecha Pago:</span> ".$CI->invoices_model->getInvoicePaymentDate($invoice->idInvoice)->date."</p> <p class='mx-auto text-gray-700'><span class='font-bold'>Cliente:</span> ".$invoice->client_name."</p> <p class='mx-auto text-gray-700'><span class='font-bold'>Total:</span> $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->total)), 2)."   ".($invoice->e_commerce ? "<span class='font-bold'>Venta por E-commerce</span>" : '')."   ".($invoice->legal_collection ? "<span class='font-bold'>Cobro Jurídico 2%</span>" : ($vend->by_commission ? "<span class='font-bold'>Comisión ".$vend->commission_perc."%</span>" : ''))."   ".($invoice->list_price ? "<span class='font-bold'>Precio de lista</span>" : '')."   ".($invoice->hasIva ? "<span class='font-bold'>Con IVA</span>" : '')."   ".($invoice->discount > 0 ? "<span class='font-bold'>Con Descuento $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->discount)), 2)." factura al ".$invoice->discount_perc."%</span>" : '')."</p><br>";
			$totalfact += $invoice->total - $invoice->discount;


			
		    		
			$details = $CI->invoices_model->getDetails($invoice->idInvoice);
			if($invoice->clientId == $vendor)
			{
				if($invoice->legal_collection)
				{
					$not_settle_total = 0;
					foreach($details as $key => $detail){
						if($detail->not_settle)
						{
							$not_settle_total += $detail->subtotal;
						}
					}
					$inv_total = ($invoice->total - $not_settle_total) * (0.02);
					$total -= $inv_total;
					$totallc -= $inv_total;
					$html .=  "<p class='mx-auto font-bold text-green-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
				}else
				if($vend->by_commission)
				{
					if($vend->new_settlement_method){
						$percentage = $vend->commission_perc/100;
						$not_settle_total = 0;
						$underpricelist = false;
						foreach($details as $key => $detail){
							$product = $CI->products_model->getProduct($detail->productId);
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
							if($detail->unit < $product->price){
								$percentage = 0.05;
								$underpricelist = true;
							}
						}
						$inv_total = ($invoice->total - $not_settle_total) * ($percentage);
						$total -= $inv_total;
						$totalcom -= $inv_total;
						$html .=  "<p class='mx-auto font-bold text-green-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
						if($underpricelist) $html .=  "<p class='mx-auto font-bold text-orange-700'>    Tiene productos con precio por debajo del precio de lista, se calculará con 5% de comisión</p><br>";
					}
					else{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$inv_total = ($invoice->total - $not_settle_total) * ($vend->commission_perc/100);
						$total -= $inv_total;
						$totalcom -= $inv_total;
						$html .=  "<p class='mx-auto font-bold text-green-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
					}
				}else
				if($invoice->list_price)
				{
					$not_settle_total = 0;
					foreach($details as $key => $detail){
						if($detail->not_settle)
						{
							$not_settle_total += $detail->subtotal;
						}
					}
					$inv_total = (($invoice->total * 0.7) - $not_settle_total) * (0.05);
					$total -= $inv_total;
					$totallp -= $inv_total;
					$html .=  "<p class='mx-auto font-bold text-green-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
				}else
				if($invoice->discount > 0)
				{
					$not_settle_total = 0;
					foreach($details as $key => $detail){
						if($detail->not_settle)
						{
							$not_settle_total += $detail->subtotal;
						}
					}
					$inv_total = ($invoice->total - $not_settle_total - $invoice->discount) * ($invoice->discount_perc/100);
					$total -= $inv_total;
					$totaldisc -= $inv_total;
					$html .=  "<p class='mx-auto font-bold text-green-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
				}else
				if($invoice->e_commerce)
				{
					$not_settle_total = 0;
					foreach($details as $key => $detail){
						if($detail->not_settle)
						{
							$not_settle_total += $detail->subtotal;
						}
					}
					$inv_total = (($invoice->total - $not_settle_total) * (0.15));
					$total -= $inv_total;
					$totalec -= $inv_total;
					$html .=  "<p class='mx-auto font-bold text-green-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
				}else
				if($invoice->hasIva)
				{
					$not_settle_total = 0;
					foreach($details as $key => $detail){
						if($detail->not_settle)
						{
							$not_settle_total += $detail->subtotal;
						}
					}
					$inv_total = (($invoice->total - $not_settle_total) * ($invoice->iva/100));
					$total -= $inv_total;
					$totaliva -= $inv_total;
					$html .=  "<p class='mx-auto font-bold text-green-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
				}else
				{
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
						if($detail->not_settle)
						{
							continue;
						}
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
				$detailsnat = $CI->invoices_model->getIfDetailsHasNational($invoice->idInvoice);
				
				if(!empty($detailsnat)){
					//echo "No Liquidar!!";
					$html .= "<br><br><p class='mx-auto text-orange-700'>No se liquida por tener productos nacionales</p>";
		    	}else{
		    		//echo "Liquidar";
					if($invoice->legal_collection)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$inv_total = ($invoice->total - $not_settle_total) * (0.02);
						$total += $inv_total;
						$totallc += $inv_total;
						$html .=  "<p class='mx-auto font-bold text-green-700'>    + $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
					}else
					if($vend->by_commission)
					{
						if($vend->new_settlement_method){
							$percentage = $vend->commission_perc/100;
							$not_settle_total = 0;
							$underpricelist = false;
							foreach($details as $key => $detail){
								$product = $CI->products_model->getProduct($detail->productId);
								if($detail->not_settle)
								{
									$not_settle_total += $detail->subtotal;
								}
								if($detail->unit < $product->price){
									$percentage = 0.05;
									$underpricelist = true;
								}
							}
							$inv_total = ($invoice->total - $not_settle_total) * ($percentage);
							$total += $inv_total;
							$totalcom += $inv_total;
							$html .=  "<p class='mx-auto font-bold text-green-700'>     + $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
							if($underpricelist) $html .=  "<p class='mx-auto font-bold text-orange-700'>    Tiene productos con precio por debajo del precio de lista, se calculará con 5% de comisión</p><br>";
						}
						else{
							$not_settle_total = 0;
							foreach($details as $key => $detail){
								if($detail->not_settle)
								{
									$not_settle_total += $detail->subtotal;
								}
							}
							$inv_total = ($invoice->total - $not_settle_total) * ($vend->commission_perc/100);
							$total += $inv_total;
							$totalcom += $inv_total;
							$html .=  "<p class='mx-auto font-bold text-green-700'>     + $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
						}
					}else
					if($invoice->list_price)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$inv_total = (($invoice->total * 0.7) - $not_settle_total) * (0.05);
						$total += $inv_total;
						$totallp += $inv_total;
						$html .=  "<p class='mx-auto font-bold text-green-700'>    + $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
					}else
					if($invoice->discount > 0)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$inv_total = ($invoice->total - $not_settle_total - $invoice->discount) * ($invoice->discount_perc/100);
						$total += $inv_total;
						$totaldisc += $inv_total;
						$html .=  "<p class='mx-auto font-bold text-green-700'>    + $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
					}else
					if($invoice->e_commerce)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$inv_total = ($invoice->total - $not_settle_total) * (0.15);
						$total += $inv_total;
						$totalec += $inv_total;
						$html .=  "<p class='mx-auto font-bold text-green-700'>    + $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
					}else
					if($invoice->hasIva)
					{
						$not_settle_total = 0;
						foreach($details as $key => $detail){
							if($detail->not_settle)
							{
								$not_settle_total += $detail->subtotal;
							}
						}
						$inv_total = ($invoice->total - $not_settle_total) * ($invoice->iva/100);
						$total += $inv_total;
						$totaliva += $inv_total;
						$html .=  "<p class='mx-auto font-bold text-green-700'>    + $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
					}else
					{
						//$details = $CI->invoices_model->getDetails($invoice->idInvoice);
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
							if($detail->not_settle)
							{
								continue;
							}
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
                     <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static '.($voucher->value >= 0 ? '' : 'text-orange-700').'">
                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Valor</span>
                         '.($voucher->value >= 0 ? '' : '-').' $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $voucher->value)), 2).'
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

		$lostInvoices = $CI->invoices_model->getVendorLegalColletionInvoices($vendor);
		$totalLostInvoices = 0;
		$totalLostComission = 0;
		foreach($lostInvoices as $key => $invoice){
			$details = $CI->invoices_model->getDetails($invoice->idInvoice);
			if($vend->by_commission)
			{
				if($vend->new_settlement_method)
				{
					$percentage = $vend->commission_perc/100;
					$not_settle_total = 0;
					foreach($details as $key => $detail){
						$product = $CI->products_model->getProduct($detail->productId);
						if($detail->not_settle)
						{
							$not_settle_total += $detail->subtotal;
						}
						if($detail->unit < $product->price){
							$percentage = 0.05;
						}
					}
					$totalLostInvoices += ($invoice->total - $not_settle_total);
					$totalLostComission += ($invoice->total - $not_settle_total) * ($percentage);
				}else{
					$not_settle_total = 0;
					foreach($details as $key => $detail){
						if($detail->not_settle)
						{
							$not_settle_total += $detail->subtotal;
						}
					}
					$totalLostInvoices += ($invoice->total - $not_settle_total);
					$totalLostComission += ($invoice->total - $not_settle_total) * ($vend->commission_perc/100);
				}
			}else
			if($invoice->list_price)
			{
				$not_settle_total = 0;
				foreach($details as $key => $detail){
					if($detail->not_settle)
					{
						$not_settle_total += $detail->subtotal;
					}
				}
				$totalLostInvoices += (($invoice->total * 0.7) - $not_settle_total);
				$totalLostComission += (($invoice->total * 0.7) - $not_settle_total) * (0.05);
			}else
			if($invoice->discount > 0)
			{
				$not_settle_total = 0;
				foreach($details as $key => $detail){
					if($detail->not_settle)
					{
						$not_settle_total += $detail->subtotal;
					}
				}
				$totalLostInvoices += ($invoice->total - $not_settle_total - $invoice->discount);
				$totalLostComission += ($invoice->total - $not_settle_total - $invoice->discount) * ($invoice->discount_perc/100);
			}else
			if($invoice->e_commerce)
			{
				$not_settle_total = 0;
				foreach($details as $key => $detail){
					if($detail->not_settle)
					{
						$not_settle_total += $detail->subtotal;
					}
				}
				$totalLostInvoices += ($invoice->total - $not_settle_total);
				$totalLostComission += ($invoice->total - $not_settle_total) * (0.15);
			}else
			if($invoice->hasIva)
			{
				$not_settle_total = 0;
				foreach($details as $key => $detail){
					if($detail->not_settle)
					{
						$not_settle_total += $detail->subtotal;
					}
				}
				$totalLostInvoices += ($invoice->total - $not_settle_total);
				$totalLostComission += ($invoice->total - $not_settle_total) * ($invoice->iva/100);
			}else
			{
				//$details = $CI->invoices_model->getDetails($invoice->idInvoice);
				foreach($details as $key => $detail){
					if($detail->not_settle)
					{
						continue;
					}
					if(!$detail->reviewed && $detail->base >= $detail->unit)
					{
						$alert = true;
					}
					$totalLostInvoices += ($detail->subtotal);
					$totalLostComission += ($detail->subtotal - ($detail->quantity * $detail->base));
				}
			}
		}

        if($totaliva != 0) $html .= "<p class='mx-auto ".($totaliva >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total IVA: ".($totaliva >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totaliva)), 2)."</p>";
        if($totalec != 0) $html .= "<p class='mx-auto ".($totalec >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total E-commerce: ".($totalec >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totalec)), 2)."</p>";
        if($totallc != 0) $html .= "<p class='mx-auto ".($totallc >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total Cobro Jurídico: ".($totallc >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totallc)), 2)."</p>";
        if($totallp != 0) $html .= "<p class='mx-auto ".($totallp >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total Precio de lista: ".($totallp >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totallp)), 2)."</p>";
		if($totalnoiva != 0) $html .= "<p class='mx-auto ".($totalnoiva >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total Remisiones: ".($totalnoiva >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totalnoiva)), 2)."</p>";
		if($totaldisc != 0) $html .= "<p class='mx-auto ".($totaldisc >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total Descuento: ".($totaldisc >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totaldisc)), 2)."</p>";
		if($totalcom != 0) $html .= "<p class='mx-auto ".($totalcom >= 0 ? 'text-green-700' : 'text-orange-700')."'>     Total x comisión: ".($totalcom >= 0 ? '+' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totalcom)), 2)."</p>";
		$html .= "<p class='mx-auto text-green-700 font-bold'>     Total Comisiones: $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totaliva+$totalnoiva+$totalec+$totallp+$totalcom+$totaldisc)), 2)." Correspondiente al ".number_format($totalfact!=0 ?(($totaliva+$totalnoiva+$totalec+$totallp+$totalcom+$totaldisc)/$totalfact*100) : 0, 2)."%</p>";
		$html .= "<p class='mx-auto ".($vtotal >= 0 ? 'text-orange-700' : 'text-green-700')."'>     Total Vales: ".($vtotal >= 0 ? '-' : '+')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $vtotal)), 2)."</p><br>";
		$vouchersTotal = $CI->vouchers_model->getVendorPaidVouchersTotal($vendor);
		$totalMonthInvoices = $CI->invoices_model->getVendorTotalInvoicesSince($vendor,date('Y-m-01 00:00:00'));
		$totalPaidMonthInvoices = $CI->payments_model->getVendorTotalPaymentsSince($vendor,date('Y-m-01 00:00:00'));

		$total -= $vouchersTotal->total;
		$html .= "<p class='mx-auto font-bold'>  Total facturas cobradas para comisión: ".($totalfact >= 0 ? '' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$totalfact)), 2)."</p><br>";

		$html .= "<p class='mx-auto font-bold'>  Total Facturado este Mes: ".($totalMonthInvoices->total >= 0 ? '' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$totalMonthInvoices->total)), 2)."</p><br>";
		
		$html .= "<p class='mx-auto font-bold'>  Total Cobrado este Mes: ".($totalPaidMonthInvoices->payment >= 0 ? '' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$totalPaidMonthInvoices->payment)), 2)."</p><br>";
        
        if($totalLostInvoices != 0) $html .= "<p class='mx-auto  font-bold text-orange-700'>     Total Facturación perdida por Cobro Jurídico: "."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totalLostInvoices)), 2).", total comisión perdida: ".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totalLostComission)), 2)."</p><br>";

		$html .= "<p class='mx-auto ".($total > 0 ? 'text-green-700' : 'text-orange-700')." font-bold'>  Total: ".($total >= 0 ? '' : '-')."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$total)), 2)."</p><br>";
       
		
		return $html;
	}

	function getUserLostInvoices($vendor){
		$CI =& get_instance();
		$html = "";
		$lostInvoices = $CI->invoices_model->getVendorLegalColletionInvoices($vendor);
		$totalLostInvoices = 0;
		$totalLostComission = 0;
    $now = date("d-m-Y");
		$vend = $CI->vendors_model->getVendor($vendor);
		foreach($lostInvoices as $key => $invoice){
			if(!empty($html)) $html .= "<hr class='mt-6 mb-4 border-t-2 border-gray-500'>";
			$html .= "<p class='mx-auto text-gray-700'><div class='flex flex-row'><span class='font-bold'>Factura #".str_pad($invoice->idInvoice, 6, "0", STR_PAD_LEFT)."</span><a href='".base_url()."sisvent/admin/settlements/marksettled/".$invoice->idInvoice."' class='flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray' aria-label='Approve'><p class='tooltip'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4'></path></svg><span class='tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded'>Marcar como liquidada</span></div></p></a></p><p class='mx-auto text-gray-700'><span class='font-bold'>Fecha Emisión:</span> ".$invoice->date."</p><p class='mx-auto text-gray-700'><span class='font-bold'>Fecha Pago:</span> ".$CI->invoices_model->getInvoicePaymentDate($invoice->idInvoice)->date."</p> <p class='mx-auto text-gray-700'><span class='font-bold'>Cliente:</span> ".$invoice->client_name."</p> <p class='mx-auto text-gray-700'><span class='font-bold'>Total:</span> $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->total)), 2)."   ".($invoice->e_commerce ? "<span class='font-bold'>Venta por E-commerce</span>" : '')."   ".($invoice->list_price ? "<span class='font-bold'>Precio de lista</span>" : '')."   ".($invoice->hasIva ? "<span class='font-bold'>Con IVA</span>" : '')."   ".($invoice->discount > 0 ? "<span class='font-bold'>Con Descuento $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->discount)), 2)." factura al ".$invoice->discount_perc."%</span>" : '')."</p>";
			$html .= "<p class='mx-auto text-orange-700'><span class='font-bold'>Días de vencida:</span> ".(date_diff(date_create($now), date_create(date("d-m-Y", strtotime($invoice->date))))->format('%a') - 90)."</p>";
			$html .= "<p class='mx-auto text-gray-700'><div class='flex flex-row'><span class='font-bold'>Estado:</span> <div class='flex flex-col'>";
            switch ($invoice->state) {
              case 0:
                $html .= "<span class='px-2 py-1 font-semibold leading-tight text-red-700 bg-red-100 rounded-full dark:text-red-100 dark:bg-red-700'>Pendiente</span>";
               break;
               case 1:
                $html .= "<button value='echo $invoice->idInvoice;' class='btn-view-invoice-payment px-2 py-1 font-semibold leading-tight text-orange-700 bg-orange-100 rounded-full dark:text-white dark:bg-orange-600'>Parcial</button>
                <p class='text-xs w-full text-center text-gray-600'>
                  $ ".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->payment)), 2)."</p>";
               break;
               case 2:
                $html .= "<span class='px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100'>
                  Pagada
                </span>";
               break;
               case 3:
                $html .= "<span class='px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100'>
                  Liquidada
                </span>";
               break;
              
              default:
                $html .= "<span class='px-2 py-1 font-semibold leading-tight text-gray-700 bg-gray-100 rounded-full dark:text-gray-100 dark:bg-gray-700'>
                  Desconocido
                </span>";
               break;
            }

            $html .= "</div></div><br>";
			$details = $CI->invoices_model->getDetails($invoice->idInvoice);
			if($vend->by_commission)
			{
				if($vend->new_settlement_method)
				{
					$percentage = $vend->commission_perc/100;
					$not_settle_total = 0;
					$underpricelist = false;
					foreach($details as $key => $detail){
						$product = $CI->products_model->getProduct($detail->productId);
						if($detail->not_settle)
						{
							$not_settle_total += $detail->subtotal;
						}
						if($detail->unit < $product->price){
							$percentage = 0.05;
							$underpricelist = true;
						}
					}
					$inv_total = ($invoice->total - $not_settle_total) * ($percentage);
					$totalLostInvoices += ($invoice->total - $not_settle_total);
					$totalLostComission += $inv_total;
					$html .=  "<p class='mx-auto font-bold text-orange-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
					if($underpricelist) $html .=  "<p class='mx-auto font-bold text-orange-700'>    Tiene productos con precio por debajo del precio de lista, se calculó con 5% de comisión</p><br>";

				}else{
					$not_settle_total = 0;
					foreach($details as $key => $detail){
						if($detail->not_settle)
						{
							$not_settle_total += $detail->subtotal;
						}
					}
					$inv_total = ($invoice->total - $not_settle_total) * ($vend->commission_perc/100);
					$totalLostInvoices += ($invoice->total - $not_settle_total);
					$totalLostComission += $inv_total;
					$html .=  "<p class='mx-auto font-bold text-orange-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
				}
			}else
			if($invoice->list_price)
			{
				$not_settle_total = 0;
				foreach($details as $key => $detail){
					if($detail->not_settle)
					{
						$not_settle_total += $detail->subtotal;
					}
				}
				$inv_total = (($invoice->total * 0.7) - $not_settle_total) * (0.05);
				$totalLostInvoices += (($invoice->total * 0.7) - $not_settle_total);
				$totalLostComission += $inv_total;
				$html .=  "<p class='mx-auto font-bold text-orange-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
			}else
			if($invoice->discount > 0)
			{
				$not_settle_total = 0;
				foreach($details as $key => $detail){
					if($detail->not_settle)
					{
						$not_settle_total += $detail->subtotal;
					}
				}
				$inv_total = ($invoice->total - $not_settle_total - $invoice->discount) * ($invoice->discount_perc/100);
				$totalLostInvoices += ($invoice->total - $not_settle_total - $invoice->discount);
				$totalLostComission += $inv_total;
				$html .=  "<p class='mx-auto font-bold text-orange-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
			}else
			if($invoice->e_commerce)
			{
				$not_settle_total = 0;
				foreach($details as $key => $detail){
					if($detail->not_settle)
					{
						$not_settle_total += $detail->subtotal;
					}
				}
				$inv_total = ($invoice->total - $not_settle_total) * (0.15);
				$totalLostInvoices += ($invoice->total - $not_settle_total);
				$totalLostComission += $inv_total;
				$html .=  "<p class='mx-auto font-bold text-orange-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
			}else
			if($invoice->hasIva)
			{
				$not_settle_total = 0;
				foreach($details as $key => $detail){
					if($detail->not_settle)
					{
						$not_settle_total += $detail->subtotal;
					}
				}
				$inv_total = ($invoice->total - $not_settle_total) * ($invoice->iva/100);
				$totalLostInvoices += ($invoice->total - $not_settle_total);
				$totalLostComission += $inv_total;
				$html .=  "<p class='mx-auto font-bold text-orange-700'>     - $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $inv_total)), 2)."</p><br>";
			}else
			{
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
				//$details = $CI->invoices_model->getDetails($invoice->idInvoice);
                          $detailTotal = 0;
				foreach($details as $key => $detail){
					if($detail->not_settle)
					{
						continue;
					}
					if(!$detail->reviewed && $detail->base >= $detail->unit)
					{
						$alert = true;
					}
					$inv_total = ($detail->subtotal - ($detail->quantity * $detail->base));
					$totalLostInvoices += ($detail->subtotal);
					$totalLostComission += $inv_total;
					$detailTotal += $inv_total;
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
                                  <td class="px-4 py-3 w-full sm:w-auto '.('text-orange-700').' block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    '.('-').' $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", ($inv_total))), 2).'
                                  </td>
                                </tr>';
					
				}
				$html .= '<tr class="text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 lg:mb-0">
                                  <td class="px-4 py-3 text-sm whitespace-normal w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                 <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                  <td class="px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static"></td>
                                  <td class="px-4 py-3 w-full sm:w-auto font-bold '.('text-orange-700').' block sm:table-cell relative sm:static">
                                    <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span>
                                    '.('-').' $'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detailTotal)), 2).'
                                  </td>
                                </tr>';
					$html .= '</tbody>
                        </table>
                      </div>
                    </div>';
			}

		}

		$html .= "<br><br>";
		
		 if($totalLostInvoices != 0) $html .= "<p class='mx-auto  font-bold text-orange-700'>     Total Facturación perdida por Cobro Jurídico: "."$".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totalLostInvoices)), 2).", total comisión perdida: ".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $totalLostComission)), 2)."</p><br>";

		return $html;

	}


	function createLinks($page, $total, $params, $limit = 20, $links = 2, $pn='') {
	    	 
	    $last       = ceil( $total / $limit );
	 
	    $start      = ( ( $page - $links ) > 0 ) ? $page - $links : 1;
	    $end        = ( ( $page + $links ) < $last ) ? $page + $links : $last;
	 
	    $html       = '<ul class="inline-flex items-center">';
	 
	    //$class      = ( $page == 1 ) ? "disabled" : "";
	    $html       .= '<li><a class="px-3 py-1 rounded-lg rounded-l-lg"'. (( $page == 1 ) ? '' : 'href="?p'.$pn.'=' . ( $page - 1 ) .$params. '"').'><svg aria-hidden="true" class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" fill-rule="evenodd"></path></svg></a></li>';
	 
	    if ( $start > 1 ) {
	        $html   .= '<li><a class="px-3 py-1 rounded-lg" href="?p'.$pn.'=1">1</a></li>';
	        $html   .= '<li class="disabled"><span>...</span></li>';
	    }
	 
	    for ( $i = $start ; $i <= $end; $i++ ) {
	        $class  = ( $page == $i ) ? "text-white transition-colors duration-150 bg-mam-blue-dark" : "";
	        $html   .= '<li class=""><a class="px-3 py-1 ' . $class . ' rounded-lg" href="?p'.$pn.'=' . $i .$params. '">' . $i . '</a></li>';
	    }
	 
	    if ( $end < $last ) {
	        $html   .= '<li class="disabled"><span>...</span></li>';
	        $html   .= '<li><a class="px-3 py-1 rounded-lg" href="?p'.$pn.'=' . $last .$params. '">' . $last . '</a></li>';
	    }
	 
	    //$class      = ( $page == $last ) ? "disabled" : "";
	    $html       .= '<li><a class="px-3 py-1 rounded-lg rounded-l-lg"'. (( $page == $last ) ? '' : 'href="?p'.$pn.'=' . ( $page + 1 ) .$params. '"').'><svg class="w-4 h-4 fill-current" aria-hidden="true" viewBox="0 0 20 20"><path d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" fill-rule="evenodd"></path></svg></a></li>';
	 
	    $html       .= '</ul>';
	 
	    return $html;
	}

	function createParamsLinks($store, $vendor, $state, $client, $iva = "all" , $s = '' ) {
	    	 
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
        if($s != "")
        {
          //if (!empty($params)) 
          //  //$params = "?"
          ////else
          //  $params .= "&"
          $params .= "&s=".$s;
        }
        
	    return $params;
	}

	function createFullParamsLinks($page, $store = "all", $vendor = "all", $state = "all", $client = "all", $iva = "all", $s = '' ) {
	    	 
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
        if($s != "")
        {
          //if (!empty($params)) 
          //  //$params = "?"
          ////else
          //  $params .= "&"
          $params .= "&s=".$s;
        }
	 
	    return $params;
	}