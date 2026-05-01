<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settlements extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
        $this->load->model("expenses_model");
        $this->load->model("vouchers_model");
        $this->load->model("invoices_model");
        $this->load->model("payments_model");
        $this->load->model("users_model");
		$this->load->model("vendors_model");
		$this->load->model("products_model");
		$this->load->model("stores_model");
		$this->load->model("clients_model");
		$this->load->model("vendor_settlement_model");
		$this->load->library("accounting_lib");
    }

	public function index()
	{

		$this->backend_lib->controlModule('cartera');

		$user = $this->users_model->getUser($this->session->userdata('user_data')['uname']);
		if (!$user) {
			redirect(base_url() . 'sisvent/dashboard');
			return;
		}
		// array_filter quita el string vacío que produce explode('',',') cuando
		// admin_store es '' o null. Así, admin_store vacío => sin filtro de tienda
		// (consistente con getVendors() que solo filtra cuando el array es no-vacío).
		$user->admin_store_arr = array_filter(explode(',', $user->admin_store ?? ''));

		$vendors = $this->vendors_model->getVendors($user->admin_store_arr);
		foreach ($vendors as $vendor){
			$s_temp = getVendorSettlement($vendor->idUser);
			$st_temp = getVendorTotalSettlement($vendor->idUser);
			$vendor->settlement = $s_temp->total;
			$vendor->alert = $s_temp->alert;
			$vendor->totalSettlement = $st_temp->total;
			$vendor->totalalert = $st_temp->alert;
			$vendor->possibleSettlement = getVendorPossibleSettlement($vendor->idUser)->total;
		}

		$data  = array(
			'settlements' => $vendors, 
		);
		$this->load->view("sisvent/admin/settlements/list",$data);
		
	}
	
	public function view(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendor = $this->input->post("id");
		$data  = array(
			'html' => getVendorSettlementView($vendor), 
			'vendor' => $this->vendors_model->getVendor($vendor),
		);
		$this->load->view("sisvent/admin/settlements/view",$data);
	}

	public function view2($vendor){
		$this->outh_model->CSRFVerify();

		//$vendor = $this->input->post("id");
		/*$data  = array(
			'vendor' => $this->vendors_model->getVendor($vendor),
			//'html' => $this->invoices_model->getVendorPaidInvoices2($vendor), 
		);
		$totalMonthInvoices = $this->invoices_model->getVendorTotalInvoicesSince($vendor,date('Y-m-01 00:00:00'));
		echo "<pre>";
		print_r($totalMonthInvoices);
		echo "</pre>";
		echo "<br>";
		print_r($this->db->last_query());
		echo "<br>";
		$totalPaidMonthInvoices = $this->payments_model->getVendorTotalPaymentsSince($vendor,date('Y-m-01 00:00:00'));
		echo "<pre>";
		print_r($totalPaidMonthInvoices);
		echo "</pre>";
		print_r($this->db->last_query());
		echo "<br>";*/

		//$this->load->view("sisvent/admin/settlements/view",$data);
		echo "<pre>";
		print_r(getVendorSettlementView($vendor));
		echo "</pre>";
		echo "<br>";
		//print_r($this->db->last_query());
	}

	public function viewtotal(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendor = $this->input->post("id");
		$data  = array(
			'html' => getVendorSettlementTotalView($vendor), 
			'vendor' => $this->vendors_model->getVendor($vendor),
		);
		$this->load->view("sisvent/admin/settlements/view",$data);
	}
	
	public function viewlostinvoices(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendor = $this->input->post("id");
		$data  = array(
			'html' => getUserLostInvoices($vendor), 
			'vendor' => $this->vendors_model->getVendor($vendor),
		);
		$this->load->view("sisvent/admin/settlements/view",$data);
	}
	
	public function marksettled($invoice_id){
		$this->backend_lib->controlModule('cartera');

		$data  = array(
			'settled' => 1,
		);
		$this->invoices_model->update($invoice_id,$data);

		$this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." marcó liquidada la factura ".$invoice_id);

		redirect(base_url()."sisvent/admin/settlements");

	}

	public function approve($vendor){
		$this->backend_lib->controlModule('cartera');
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$invoices = $this->invoices_model->getVendorPaidInvoices($vendor);
		$vend = $this->vendors_model->getVendor($vendor);

		// Acumuladores de la liquidación
		$total = 0;
		$totalRecaudado = 0;
		$totalComisionPositiva = 0;
		$totalComisionNegativa = 0;

		// Strings de descripción para preservar el formato histórico de
		// expenses.description ("Liquidación de Juan Facturas: (123) ...")
		$inv = "Facturas:";   $desc = "Descuento:"; $ecom = "e-commerce:";
		$lc  = "CobroJuridico:"; $lp = "PrecioLista:"; $com = "Comisión:";
		$ivainv = "IVA:";     $vou = "Vales:";      $nal = "Nacionales:";

		// Detalle estructurado (Fase 1)
		$structuredItems = array();

		foreach ($invoices as $invoice) {
			// Marcar la factura como liquidada (también blacklisted/national)
			$this->invoices_model->update($invoice->idInvoice, array('state' => 3));
			$totalRecaudado += (float)$invoice->total;

			$client = $this->clients_model->getClient($invoice->clientId);
			$itemBase = array(
				'invoice_id'    => $invoice->idInvoice,
				'invoice_date'  => isset($invoice->date) ? $invoice->date : null,
				'invoice_total' => (float)$invoice->total,
				'client_id'     => $invoice->clientId,
				'client_name'   => $client ? $client->name : null,
			);

			if ($invoice->blacklisted) {
				$structuredItems[] = array_merge($itemBase, array(
					'rule_applied'      => 'blacklisted_skipped',
					'is_self_invoice'   => 0, 'is_underpriced' => 0,
					'not_settle_amount' => 0, 'base_amount' => 0,
					'percentage' => 0, 'commission_amount' => 0,
					'notes' => 'Cliente blacklisted',
				));
				continue;
			}

			$details = $this->invoices_model->getDetails($invoice->idInvoice);
			$isSelf = ($invoice->clientId == $vendor);

			// Si NO es factura propia y la factura tiene productos nacionales,
			// se omite del cálculo (regla histórica). Solo se anota en $nal.
			if (!$isSelf && !empty($this->invoices_model->getIfDetailsHasNational($invoice->idInvoice))) {
				$nal .= " (" . $invoice->idInvoice . ")";
				$structuredItems[] = array_merge($itemBase, array(
					'rule_applied'      => 'national_skipped',
					'is_self_invoice'   => 0, 'is_underpriced' => 0,
					'not_settle_amount' => 0, 'base_amount' => 0,
					'percentage' => 0, 'commission_amount' => 0,
					'notes' => 'Tiene productos nacionales',
				));
				continue;
			}

			// Calcular la comisión via helper (mismas 7 reglas que el flujo original)
			$calc = $this->_computeInvoiceCommission($invoice, $vend, $details);
			$signed = $isSelf ? -$calc['amount'] : $calc['amount'];
			$total += $signed;

			if ($signed >= 0) $totalComisionPositiva += $signed;
			else              $totalComisionNegativa += abs($signed);

			// Construir el sufijo "(123)" o "(-123)" o "(-123*)" para la
			// descripción legible que se guarda en expenses.description.
			$tag = $isSelf ? "(-" . $invoice->idInvoice : "(" . $invoice->idInvoice;
			switch ($calc['rule']) {
				case 'legal_collection': $lc     .= " " . $tag . ")"; break;
				case 'by_commission':    $com    .= " " . $tag . ($calc['is_underpriced'] ? "*" : "") . ")"; break;
				case 'list_price':       $lp     .= " " . $tag . ")"; break;
				case 'invoice_discount': $desc   .= " " . $tag . ")"; break;
				case 'e_commerce':       $ecom   .= " " . $tag . ")"; break;
				case 'iva':              $ivainv .= " " . $tag . ")"; break;
				case 'default':          $inv    .= " " . $tag . ")"; break;
			}

			$structuredItems[] = array_merge($itemBase, array(
				'rule_applied'      => $calc['rule'],
				'is_self_invoice'   => $isSelf ? 1 : 0,
				'is_underpriced'    => $calc['is_underpriced'],
				'not_settle_amount' => $calc['not_settle'],
				'base_amount'       => $calc['base'],
				'percentage'        => $calc['percentage'],
				'commission_amount' => $signed,
			));
		}

		// Vales: descuentan del neto y se anotan en $vou (se cargan a paymentMethod=4)
		$vouchers = $this->vouchers_model->getVendorPaidVouchers($vendor);
		$vtotal = 0;
		$structuredVouchers = array();

		foreach ($vouchers as $voucher) {
			$this->vouchers_model->update($voucher->idVoucher, array('state' => 2));
			$vtotal += (float)$voucher->value;
			$vou .= " (" . $voucher->idVoucher . ")";
			$structuredVouchers[] = array(
				'voucher_id'    => $voucher->idVoucher,
				'voucher_value' => (float)$voucher->value,
			);
		}
		$total -= $vtotal;

		$userId  = $this->session->userdata('user_data')['uname'];
		$storeId = isset($vend->storeId) ? $vend->storeId : 1;

		// Persistir cabecera + items + vales (Fase 1)
		$settlementId = $this->vendor_settlement_model->createSettlement(array(
			'vendor_id'         => $vendor,
			'vendor_name'       => isset($vend->name) ? $vend->name : null,
			'store_id'          => $storeId,
			'invoice_count'     => count($invoices),
			'voucher_count'     => count($vouchers),
			'total_recaudado'   => $totalRecaudado,
			'total_comision'    => $totalComisionPositiva,
			'total_descuentos'  => $totalComisionNegativa,
			'total_vouchers'    => $vtotal,
			'total_neto'        => $total,
			'status'            => 'pagado',
			'created_by'        => $userId,
			'paid_by'           => $userId,
			'paid_at'           => date('Y-m-d H:i:s'),
		));

		foreach ($structuredItems as &$it) $it['settlement_id'] = $settlementId;
		unset($it);
		$this->vendor_settlement_model->saveItemsBatch($structuredItems);

		foreach ($structuredVouchers as &$sv) $sv['settlement_id'] = $settlementId;
		unset($sv);
		$this->vendor_settlement_model->saveVouchersBatch($structuredVouchers);

		if($total < 0)
		{
			$user = $this->vendors_model->getVendor($vendor);
			$settlementDescription = "Liquidación de ".$user->name." ".$inv." ".$ivainv." ".$desc." ".$ecom." ".$vou." ".$lc." ".$lp." ".$com." ".$nal;
			$data  = array(
				'vendorId' => $vendor,
				'value' => $total,
				'description' => $settlementDescription,
				'settlement_id' => $settlementId,
			);

			$this->expenses_model->save($data);

			$idExpenses = $this->db->insert_id();
			$this->vendor_settlement_model->updateSettlement($settlementId, array('expense_id' => $idExpenses));

			// Registrar asiento contable de la liquidación
			$this->accounting_lib->recordSettlement(
				$idExpenses,
				$vendor,
				$total,
				$storeId,
				$userId,
				$settlementDescription
			);

			$data  = array(
				'userId' => $vendor,
				'value' => abs($total),
				'paymentMethod' => 4,
				'description' => "Faltante después de liquidación  - Liquidación ".$idExpenses,
				'state' => 1,
			);

			$this->vouchers_model->save($data);
		}else
		{
			$user = $this->vendors_model->getVendor($vendor);
			$settlementDescription = "Liquidación de ".$user->name." ".$inv." ".$ivainv." ".$desc." ".$ecom." ".$vou." ".$lc." ".$lp." ".$com." ".$nal;
			$data  = array(
				'vendorId' => $vendor,
				'value' => $total,
				'description' => $settlementDescription,
				'settlement_id' => $settlementId,
			);

			$this->expenses_model->save($data);

			$idExpenses = $this->db->insert_id();
			$this->vendor_settlement_model->updateSettlement($settlementId, array('expense_id' => $idExpenses));

			// Registrar asiento contable de la liquidación
			$this->accounting_lib->recordSettlement(
				$idExpenses,
				$vendor,
				$total,
				$storeId,
				$userId,
				$settlementDescription
			);

			$data  = array(
				'userId' => $vendor,
				'value' => -$total,
				'paymentMethod' => 4,
				'description' => "Liquidación ".$idExpenses,
				'state' => 1,
			);

			$this->vouchers_model->save($data);
		}
		//print_r($data);

		// Redirige al detalle estructurado de esta liquidación recién creada
		// (Fase 4: vista detalle). Si por alguna razón no se generó, cae al
		// listado clásico.
		echo $settlementId
			? base_url() . 'sisvent/admin/settlements/detail/' . $settlementId
			: base_url() . 'sisvent/admin/settlements';

	}

	public function totalpaidindate()
	{

		$this->backend_lib->controlModule('cartera');

		
		$this->load->view("sisvent/admin/settlements/totalpaidindate");
		
	}

	public function getTotalPaidInDate()
	{

		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		//$user = $this->users_model->getUser($this->session->userdata('user_data')['uname']); 
		//$user->admin_store_arr = explode(',', $user->admin_store);
		$since = $this->input->post("since");
		$until = $this->input->post("until");

		$vendors = $this->vendors_model->getVendors();
		foreach ($vendors as $vendor){
			$vendor->totalPaidMonthInvoices = $this->payments_model->getVendorTotalPaymentsSinceUntil($vendor->idUser,$since, $until)->payment;
		}

		$data  = array(
			'vendors' => $vendors, 
		);
		$this->load->view("sisvent/admin/settlements/totalpaid",$data);
		
	}

	public function getTotalPaidInDate2($since, $until)
	{

		
		//$since = $this->input->post("since");
		//$until = $this->input->post("until");

		$vendors = $this->vendors_model->getVendors();
		print_r(date('Y-m-d H:i:s',strtotime($since)));
		print_r(date('Y-m-d H:i:s',strtotime($until)));

		foreach ($vendors as $vendor){
			
			$vendor->totalPaidMonthInvoices = $this->payments_model->getVendorTotalPaymentsSinceUntil($vendor->idUser,$since, $until)->payment;
		}

		$data  = array(
			'vendors' => $vendors, 
		);
		$this->load->view("sisvent/admin/settlements/totalpaid",$data);

	}

	/**
	 * Listado de liquidaciones realizadas (cabeceras de vendor_settlements).
	 * Acepta filtros por vendor (?vendor=ID) y rango de fechas (?from=&to=).
	 */
	public function history()
	{
		$this->backend_lib->controlModule('cartera');

		$vendorId = $this->input->get('vendor');
		$from = $this->input->get('from');
		$to = $this->input->get('to');

		$this->db->select('vs.*, u.name AS vendor_full_name, e.value AS expense_value')
			->from('vendor_settlements vs')
			->join('users u', 'u.idUser = vs.vendor_id', 'left')
			->join('expenses e', 'e.idExpense = vs.expense_id', 'left')
			->order_by('vs.created_at', 'DESC');

		if ($vendorId) $this->db->where('vs.vendor_id', $vendorId);
		if ($from) $this->db->where('vs.created_at >=', $from . ' 00:00:00');
		if ($to)   $this->db->where('vs.created_at <=', $to   . ' 23:59:59');

		$rows = $this->db->get()->result();

		$totals = array('count' => count($rows), 'recaudado' => 0, 'comision' => 0, 'descuentos' => 0, 'neto' => 0);
		foreach ($rows as $r) {
			$totals['recaudado']  += (float)$r->total_recaudado;
			$totals['comision']   += (float)$r->total_comision;
			$totals['descuentos'] += (float)$r->total_descuentos;
			$totals['neto']       += (float)$r->total_neto;
		}

		$data = array(
			'settlements'  => $rows,
			'totals'       => $totals,
			'filter_vendor'=> $vendorId,
			'filter_from'  => $from,
			'filter_to'    => $to,
			'role'         => $this->session->userdata('user_data')['role'],
		);
		$this->load->view('sisvent/admin/settlements/history', $data);
	}

	/**
	 * Detalle de una liquidación: cabecera + items por factura agrupados
	 * por regla aplicada + vales consumidos.
	 */
	public function detail($id)
	{
		$this->backend_lib->controlModule('cartera');

		$settlement = $this->vendor_settlement_model->getSettlement($id);
		if (!$settlement) show_404();

		$items = $this->vendor_settlement_model->getItems($id);
		$vouchers = $this->vendor_settlement_model->getVouchers($id);
		$summary = $this->vendor_settlement_model->getItemsSummaryByRule($id);

		$data = array(
			'settlement' => $settlement,
			'items'      => $items,
			'vouchers'   => $vouchers,
			'summary'    => $summary,
			'role'       => $this->session->userdata('user_data')['role'],
		);
		$this->load->view('sisvent/admin/settlements/detail', $data);
	}

	/**
	 * Calcula la comisión de UNA factura usando las mismas 7 reglas del bloque
	 * if/elseif de approve(). Retorna un array estructurado para guardar en
	 * vendor_settlement_items. La magnitud retornada es siempre positiva; el
	 * caller aplica el signo (+ para factura de cliente, − cuando vendedor==cliente).
	 *
	 * Reglas en orden de precedencia (idéntico al original):
	 *   legal_collection > by_commission > list_price > invoice_discount >
	 *   e_commerce > iva > default
	 *
	 * @param object $invoice  Factura recaudada
	 * @param object $vend     Vendedor
	 * @param array  $details  Detalles de la factura (invoice_details)
	 * @return array { rule, base, not_settle, percentage, is_underpriced, amount }
	 */
	private function _computeInvoiceCommission($invoice, $vend, $details)
	{
		$not_settle = 0;
		foreach ($details as $d) {
			if ($d->not_settle) $not_settle += (float)$d->subtotal;
		}
		$invTotal = (float)$invoice->total;

		if ($invoice->legal_collection) {
			$base = $invTotal - $not_settle;
			return array(
				'rule' => 'legal_collection',
				'base' => $base, 'not_settle' => $not_settle,
				'percentage' => 2.00, 'is_underpriced' => 0,
				'amount' => $base * 0.02,
			);
		}

		if ($vend->by_commission) {
			$pct = ((int)$vend->commission_perc) / 100;
			$is_underpriced = 0;
			if ($vend->new_settlement_method) {
				foreach ($details as $d) {
					$product = $this->products_model->getProduct($d->productId);
					if ($product && $d->unit < $product->price) {
						$pct = 0.05;
						$is_underpriced = 1;
					}
				}
			}
			$base = $invTotal - $not_settle;
			return array(
				'rule' => 'by_commission',
				'base' => $base, 'not_settle' => $not_settle,
				'percentage' => $pct * 100, 'is_underpriced' => $is_underpriced,
				'amount' => $base * $pct,
			);
		}

		if ($invoice->list_price) {
			$base = ($invTotal * 0.7) - $not_settle;
			return array(
				'rule' => 'list_price',
				'base' => $base, 'not_settle' => $not_settle,
				'percentage' => 5.00, 'is_underpriced' => 0,
				'amount' => $base * 0.05,
			);
		}

		if ($invoice->discount > 0) {
			$base = $invTotal - $not_settle - (float)$invoice->discount;
			return array(
				'rule' => 'invoice_discount',
				'base' => $base, 'not_settle' => $not_settle,
				'percentage' => (float)$invoice->discount_perc, 'is_underpriced' => 0,
				'amount' => $base * ((float)$invoice->discount_perc / 100),
			);
		}

		if ($invoice->e_commerce) {
			$base = $invTotal - $not_settle;
			return array(
				'rule' => 'e_commerce',
				'base' => $base, 'not_settle' => $not_settle,
				'percentage' => 15.00, 'is_underpriced' => 0,
				'amount' => $base * 0.15,
			);
		}

		if ($invoice->hasIva) {
			$base = $invTotal - $not_settle;
			return array(
				'rule' => 'iva',
				'base' => $base, 'not_settle' => $not_settle,
				'percentage' => (float)$invoice->iva, 'is_underpriced' => 0,
				'amount' => $base * ((float)$invoice->iva / 100),
			);
		}

		// Default: margen por línea = subtotal − (cantidad × base), excluyendo not_settle
		$amount = 0;
		foreach ($details as $d) {
			if ($d->not_settle) continue;
			$amount += (float)$d->subtotal - ((float)$d->quantity * (float)$d->base);
		}
		return array(
			'rule' => 'default',
			'base' => $invTotal - $not_settle, 'not_settle' => $not_settle,
			'percentage' => 0, 'is_underpriced' => 0,
			'amount' => $amount,
		);
	}
}