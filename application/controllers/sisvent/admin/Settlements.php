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
		$this->load->model("employeeadvances_model");
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

		// v2.2.0: la comisión "directa" por factura ya no existe. Toda
		// comisión se paga vía bots (operador 7%, admin 3%, coordinador 1%).
		// Esta pantalla muestra: comisión de bots pendiente − anticipos.
		// Solo aparecen personas con comisión de bots > 0 o anticipos != 0.
		$botCommissions = $this->_getPendingBotCommissionsByUser();

		// Empleados con anticipos pendientes (saldo > 0). Usa la misma
		// definición que Employeeadvances_model::getEmployeeBalance:
		// outstanding_balance > 0 con status='desembolsado' y deleted=0.
		$advRows = $this->db->select('employee_id, COALESCE(SUM(outstanding_balance), 0) AS balance')
			->from('employee_advances')
			->where('status', 'desembolsado')
			->where('deleted', 0)
			->group_by('employee_id')
			->having('balance >', 0.001)
			->get()->result();
		$advanceBalances = array();
		foreach ($advRows as $r) $advanceBalances[$r->employee_id] = (float)$r->balance;

		// Universo: union(bot_commissions.keys, advance_balances.keys)
		$userIds = array_unique(array_merge(array_keys($botCommissions), array_keys($advanceBalances)));

		$settlements = array();
		foreach ($userIds as $uid) {
			$u = $this->users_model->getAnyUser($uid);
			if (!$u) continue;
			$bot = isset($botCommissions[$uid]) ? (float)$botCommissions[$uid]['amount'] : 0;
			$adv = isset($advanceBalances[$uid]) ? $advanceBalances[$uid] : 0;
			$settlements[] = (object) array(
				'idUser'         => $uid,
				'name'           => $u->name,
				'bot_commission' => $bot,
				'bot_desc'       => isset($botCommissions[$uid]) ? $botCommissions[$uid]['desc'] : '',
				'advanceBalance' => $adv,
				'netoPagar'      => $bot - $adv,
			);
		}

		// Orden: saldo neto descendente (los que más cobran primero)
		usort($settlements, function ($a, $b) {
			return $b->netoPagar <=> $a->netoPagar;
		});

		$this->load->view("sisvent/admin/settlements/list", array(
			'settlements'   => $settlements,
			'cashboxes'     => $this->_loadCashboxesForCurrentStore(),
			'bank_accounts' => $this->_loadBankAccountsForCurrentStore(),
		));
	}

	/**
	 * Comisión PENDIENTE de bots por usuario, alcance año en curso.
	 * Mismo cálculo que /admin/comisiones (default año):
	 *   ganado_año     = cobros_año × % (por bot_commission_config)
	 *   liquidado_año  = SUM(bot_commission_details) de períodos status='liquidado'
	 *   pendiente_año  = ganado_año − liquidado_año
	 *
	 * Antes (v2.0.x) era solo el período en curso (21→20). Cambiado a año
	 * para que el saldo en /admin/settlements refleje TODOS los meses
	 * pendientes, no solo el actual — si hubo meses sin liquidar, ahora
	 * aparecen acumulados, evitando subestimar la deuda con cada persona.
	 */
	private function _getPendingBotCommissionsByUser()
	{
		date_default_timezone_set("America/Bogota");
		$year = (int)date('Y');
		$ps   = $year . '-01-01';
		$pe   = $year . '-12-31';

		// Cobros por bot del año (filtra por updated_at = cuando se cobró).
		// v2.2.1 — resta flete (consistente con Comisiones._getCobrosPerBot
		// y settlement_helper._getBotOperatorInvoiceRows). La base de
		// comisión es total facturado − flete, capado a 0.
		$sql = "SELECT bc.id AS bot_id, bc.name AS bot_name, bc.default_vendor_id,
				       COALESCE(SUM(i.total), 0) AS total_bruto,
				       COALESCE(SUM(sg.flete), 0) AS flete_total
				FROM builderbot_configs bc
				LEFT JOIN invoices i ON i.vendorId = bc.default_vendor_id
					AND i.state = 2 AND i.total > 0
					AND i.updated_at >= ? AND i.updated_at <= ?
					AND (i.deleted IS NULL OR i.deleted = 0)
				LEFT JOIN (
					SELECT invoiceId, SUM(valorTotal) AS flete
					FROM shipping_guides
					GROUP BY invoiceId
				) sg ON sg.invoiceId = i.idInvoice
				WHERE bc.is_active = 1
				GROUP BY bc.id";
		$cobrosRows = $this->db->query($sql, array($ps . ' 00:00:00', $pe . ' 23:59:59'))->result();
		$cobrosPerBot = array();
		$totalCobrado = 0;
		foreach ($cobrosRows as $r) {
			$neto = max(0, (float)$r->total_bruto - (float)$r->flete_total);
			$cobrosPerBot[$r->bot_id] = $neto;
			$totalCobrado += $neto;
		}

		// Liquidado del año por usuario (suma de detalles de períodos liquidados)
		$liquidatedRows = $this->db->select('d.user_id, COALESCE(SUM(d.commission_amount), 0) AS total')
			->from('bot_commission_details d')
			->join('bot_commission_periods p', 'p.id = d.period_id')
			->where('p.status', 'liquidado')
			->where('YEAR(p.period_end)', $year)
			->group_by('d.user_id')
			->get()->result();
		$liquidatedPerUser = array();
		foreach ($liquidatedRows as $r) $liquidatedPerUser[$r->user_id] = (float)$r->total;

		// Configs activas → calcular ganado del año por usuario
		$configs = $this->db->where('is_active', 1)->get('bot_commission_config')->result();
		$earned = array();
		foreach ($configs as $cfg) {
			if ($cfg->applies_to === 'all') {
				$base = $totalCobrado;
				$desc = 'Bots — todos';
			} else {
				$bot_id = (int)$cfg->applies_to;
				$base = isset($cobrosPerBot[$bot_id]) ? $cobrosPerBot[$bot_id] : 0;
				$desc = 'Bot #' . $bot_id;
			}
			$amount = round($base * ($cfg->percentage / 100));
			if (!isset($earned[$cfg->user_id])) $earned[$cfg->user_id] = array('amount' => 0, 'desc' => '');
			$earned[$cfg->user_id]['amount'] += $amount;
			$earned[$cfg->user_id]['desc']   .= ($earned[$cfg->user_id]['desc'] ? ' + ' : '') . $cfg->percentage . '% ' . $desc;
		}

		// Pendiente = ganado − liquidado, capado a 0 (defensivo)
		$out = array();
		foreach ($earned as $uid => $info) {
			$liq = isset($liquidatedPerUser[$uid]) ? $liquidatedPerUser[$uid] : 0;
			$pend = max(0, $info['amount'] - $liq);
			if ($pend <= 0) continue;
			$out[$uid] = array(
				'amount' => $pend,
				'desc'   => $info['desc'],
			);
		}
		return $out;
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

	/**
	 * Flujo legacy 1-paso: calcula y paga en la misma transacción.
	 * Se preserva por compatibilidad con scripts/llamadas viejas.
	 * El nuevo flujo recomendado es: calculate() → revisar → pay() o discard().
	 */
	public function approve($vendor){
		$this->backend_lib->controlModule('cartera');
		$this->outh_model->CSRFVerify();
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$settlementId = $this->_doCalculate($vendor);
		if ($settlementId) $this->_doPay($settlementId);

		echo $settlementId
			? base_url() . 'sisvent/admin/settlements/detail/' . $settlementId
			: base_url() . 'sisvent/admin/settlements';
	}

	/**
	 * Fase 3 — paso 1: calcula la liquidación SIN tocar facturas/vouchers/expense.
	 * Crea un snapshot en estado 'calculado' que se puede revisar y luego pagar
	 * (pay) o descartar (discard).
	 *
	 * Si ya existe un calculado para el mismo vendedor, redirige a ese sin
	 * crear uno nuevo (evita doble-cálculo).
	 */
	public function calculate($vendor){
		$this->backend_lib->controlModule('cartera');
		$this->outh_model->CSRFVerify();
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		// Si ya hay un calculado/aprobado pendiente, llevamos a ese.
		$pending = $this->db
			->where('vendor_id', $vendor)
			->where_in('status', array('calculado','aprobado'))
			->order_by('id', 'DESC')->limit(1)
			->get('vendor_settlements')->row();
		if ($pending) {
			echo base_url() . 'sisvent/admin/settlements/detail/' . $pending->id;
			return;
		}

		$settlementId = $this->_doCalculate($vendor);
		echo $settlementId
			? base_url() . 'sisvent/admin/settlements/detail/' . $settlementId
			: base_url() . 'sisvent/admin/settlements';
	}

	/**
	 * Fase 3 — paso 2: aplica los efectos secundarios de un settlement
	 * 'calculado' o 'aprobado': marca facturas state=3, vales state=2,
	 * crea expense + asiento contable + voucher de faltante/remanente.
	 */
	public function pay($id){
		$this->backend_lib->controlModule('cartera');
		$this->outh_model->CSRFVerify();
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$ok = $this->_doPay((int)$id);
		echo $ok
			? base_url() . 'sisvent/admin/settlements/detail/' . (int)$id
			: base_url() . 'sisvent/admin/settlements';
	}

	/**
	 * Descartar un settlement no pagado (calculado/aprobado): borra cabecera,
	 * items y vales asociados. Como no hubo side effects, no hay nada que
	 * revertir en facturas/vouchers/banco.
	 */
	public function discardSettlement($id){
		$this->backend_lib->controlModule('cartera');
		$this->outh_model->CSRFVerify();
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$s = $this->vendor_settlement_model->getSettlement((int)$id);
		if (!$s) { echo base_url() . 'sisvent/admin/settlements/history'; return; }
		if (!in_array($s->status, array('calculado','aprobado'))) {
			// Sólo se puede descartar lo no pagado. Para 'pagado' hay que reversar.
			echo base_url() . 'sisvent/admin/settlements/detail/' . (int)$id;
			return;
		}

		$this->db->where('settlement_id', (int)$id)->delete('vendor_settlement_items');
		$this->db->where('settlement_id', (int)$id)->delete('vendor_settlement_vouchers');
		$this->db->where('id', (int)$id)->delete('vendor_settlements');

		echo base_url() . 'sisvent/admin/settlements';
	}

	/**
	 * Avanzar manualmente de 'calculado' a 'aprobado' (paso opcional).
	 */
	public function approveSettlement($id){
		$this->backend_lib->controlModule('cartera');
		$this->outh_model->CSRFVerify();
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$s = $this->vendor_settlement_model->getSettlement((int)$id);
		if (!$s || $s->status !== 'calculado') {
			echo base_url() . 'sisvent/admin/settlements/detail/' . (int)$id;
			return;
		}
		$this->vendor_settlement_model->updateSettlement((int)$id, array(
			'status'      => 'aprobado',
			'approved_by' => $this->session->userdata('user_data')['uname'],
			'approved_at' => date('Y-m-d H:i:s'),
		));
		echo base_url() . 'sisvent/admin/settlements/detail/' . (int)$id;
	}

	/**
	 * Construye el snapshot de la liquidación de un vendedor y lo persiste
	 * en vendor_settlements + items + vales con status='calculado'.
	 *
	 * NO toca: invoices.state, vouchers.state, expenses, asiento contable.
	 *
	 * @return int|null  ID del settlement creado, o null si no había nada que liquidar.
	 */
	private function _doCalculate($vendor){
		$invoices = $this->invoices_model->getVendorPaidInvoices($vendor);
		$vend = $this->vendors_model->getVendor($vendor);

		$total = 0;
		$totalRecaudado = 0;
		$totalComisionPositiva = 0;
		$totalComisionNegativa = 0;

		// Strings que componen la descripción legible
		$inv = "Facturas:";   $desc = "Descuento:"; $ecom = "e-commerce:";
		$lc  = "CobroJuridico:"; $lp = "PrecioLista:"; $com = "Comisión:";
		$ivainv = "IVA:";     $vou = "Vales:";      $nal = "Nacionales:";

		$structuredItems = array();

		foreach ($invoices as $invoice) {
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

			$calc = $this->_computeInvoiceCommission($invoice, $vend, $details);
			$signed = $isSelf ? -$calc['amount'] : $calc['amount'];
			$total += $signed;
			if ($signed >= 0) $totalComisionPositiva += $signed;
			else              $totalComisionNegativa += abs($signed);

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

		// Vales del vendedor (NO se marcan como consumidos aún — eso pasa al pagar)
		$vouchers = $this->vouchers_model->getVendorPaidVouchers($vendor);
		$vtotal = 0;
		$structuredVouchers = array();
		foreach ($vouchers as $voucher) {
			$vtotal += (float)$voucher->value;
			$vou .= " (" . $voucher->idVoucher . ")";
			$structuredVouchers[] = array(
				'voucher_id'    => $voucher->idVoucher,
				'voucher_value' => (float)$voucher->value,
			);
		}
		$total -= $vtotal;

		// Si no hay nada que liquidar, no creamos un settlement vacío
		if (empty($structuredItems) && empty($structuredVouchers)) return null;

		$userId  = $this->session->userdata('user_data')['uname'];
		$storeId = isset($vend->storeId) ? $vend->storeId : 1;

		$settlementDescription = "Liquidación de " . (isset($vend->name) ? $vend->name : $vendor) . " " . $inv . " " . $ivainv . " " . $desc . " " . $ecom . " " . $vou . " " . $lc . " " . $lp . " " . $com . " " . $nal;

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
			'status'            => 'calculado',
			'description'       => $settlementDescription,
			'created_by'        => $userId,
		));

		foreach ($structuredItems as &$it) $it['settlement_id'] = $settlementId;
		unset($it);
		$this->vendor_settlement_model->saveItemsBatch($structuredItems);

		foreach ($structuredVouchers as &$sv) $sv['settlement_id'] = $settlementId;
		unset($sv);
		$this->vendor_settlement_model->saveVouchersBatch($structuredVouchers);

		return $settlementId;
	}

	/**
	 * Aplica los efectos secundarios de un settlement 'calculado' o 'aprobado':
	 * facturas state=3, vales state=2, expense + asiento + voucher final.
	 * No re-calcula: usa los datos snapshot del settlement.
	 *
	 * @return bool  true si se pagó, false si el settlement no era pagable.
	 */
	private function _doPay($settlementId){
		$settlement = $this->vendor_settlement_model->getSettlement($settlementId);
		if (!$settlement || !in_array($settlement->status, array('calculado','aprobado'))) {
			return false;
		}

		$items    = $this->vendor_settlement_model->getItems($settlementId);
		$vouchers = $this->vendor_settlement_model->getVouchers($settlementId);

		// Marcar facturas como liquidadas (state=3). Aplica también a las
		// blacklisted/national por consistencia con el flujo histórico.
		foreach ($items as $it) {
			$this->invoices_model->update($it->invoice_id, array('state' => 3));
		}
		// Marcar vales como consumidos
		foreach ($vouchers as $v) {
			$this->vouchers_model->update($v->voucher_id, array('state' => 2));
		}

		$total       = (float)$settlement->total_neto;
		$vendor      = $settlement->vendor_id;
		$userId      = $this->session->userdata('user_data')['uname'];
		$storeId     = $settlement->store_id ?: 1;
		$description = $settlement->description ?: ('Liquidación #' . $settlementId);

		// L.2 — Cruce FIFO de anticipos pendientes contra la comisión.
		// Si el vendedor tiene anticipos activos (status='desembolsado',
		// outstanding_balance>0), los descontamos del total a pagar antes
		// de hacer el gasto. Cada cruce queda en settlement_advance_payments
		// como audit trail y postea un asiento DR Vendor [aux] / CR Anticipos [aux].
		$totalAdvanceCross = 0;
		if ($total > 0) {
			$activeAdvances = $this->employeeadvances_model->getActiveAdvancesForEmployee($vendor);
			$remaining = $total;
			foreach ($activeAdvances as $adv) {
				if ($remaining <= 0.001) break;
				$advBalance = (float)$adv->outstanding_balance;
				$applied = min($advBalance, $remaining);
				if ($applied <= 0.001) continue;

				$this->employeeadvances_model->logSettlementCross($settlementId, $adv->id, $applied, $userId);
				$this->employeeadvances_model->applyToBalance($adv->id, $applied);

				$this->accounting_lib->recordAdvanceCross(
					$settlementId, $adv->id, $applied, $vendor, $storeId, $userId,
					'Cruce anticipo ' . $adv->code . ' con liquidación #' . $settlementId,
					date('Y-m-d')
				);

				$remaining -= $applied;
				$totalAdvanceCross += $applied;
			}
			$total -= $totalAdvanceCross;  // Lo que sale en efectivo
		}

		// Crear gasto + asiento contable (por el remanente que SÍ sale en efectivo)
		$this->expenses_model->save(array(
			'vendorId'      => $vendor,
			'value'         => $total,
			'description'   => $description . ($totalAdvanceCross > 0
				? ' | Anticipos cruzados: $' . number_format($totalAdvanceCross, 0, ',', '.')
				: ''),
			'settlement_id' => $settlementId,
		));
		$idExpenses = $this->db->insert_id();

		$this->accounting_lib->recordSettlement(
			$idExpenses, $vendor, $total, $storeId, $userId, $description
		);

		// Voucher de faltante (si total<0) o de remanente (si total>=0)
		$this->vouchers_model->save(array(
			'userId'        => $vendor,
			'value'         => $total < 0 ? abs($total) : -$total,
			'paymentMethod' => 4,
			'description'   => $total < 0
				? ('Faltante después de liquidación  - Liquidación ' . $idExpenses)
				: ('Liquidación ' . $idExpenses),
			'state'         => 1,
		));

		// Cerrar settlement, registrando los anticipos cruzados en notes
		$updateData = array(
			'status'     => 'pagado',
			'expense_id' => $idExpenses,
			'paid_by'    => $userId,
			'paid_at'    => date('Y-m-d H:i:s'),
		);
		if ($totalAdvanceCross > 0) {
			$prevNotes = $settlement->notes ? $settlement->notes . "\n" : '';
			$updateData['notes'] = $prevNotes . 'Anticipos cruzados: $' . number_format($totalAdvanceCross, 0, ',', '.');
		}
		$this->vendor_settlement_model->updateSettlement($settlementId, $updateData);

		return true;
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
	/**
	 * Estado de cuenta cronológico del vendedor (Fase L.6, portado de Lumen).
	 * UNION de 5 fuentes: liquidaciones, vales, anticipos, cruces y abonos.
	 * URL: /sisvent/admin/settlements/statement/{vendorId}?from=YYYY-MM-DD&to=YYYY-MM-DD
	 */
	public function statement($vendorId)
	{
		$this->backend_lib->controlModule('cartera');
		$this->load->helper('settlement');

		$vendor = $this->vendors_model->getVendor($vendorId);
		if (!$vendor) show_404();

		// Default: ciclo de comisiones 21 mes anterior → 20 mes actual.
		$defaultFrom = date('Y-m-21', strtotime('-1 month'));
		$defaultTo   = date('Y-m-20');
		$from = $this->input->get('from') ?: $defaultFrom;
		$to   = $this->input->get('to')   ?: $defaultTo;

		$rows  = getVendorStatement($vendorId, $from, $to);
		$kpis  = getVendorStatementKpis($vendorId, $from, $to, $rows);
		attachRunningBalance($rows, $kpis['previous_balance']);

		// KPIs del vendedor (saldo gerencial, no contable):
		//  - current_commission: comisión liquidable hoy. v2.2.1 — viene de
		//    _getPendingBotCommissionsByUser (misma fuente que la lista de
		//    /admin/settlements), no de getVendorSettlement (legacy, era la
		//    comisión directa per-factura eliminada en v2.2.0).
		//  - current_advances: anticipos pendientes hoy (employee_advances con saldo > 0)
		//  - current_balance: saldo neto = comisión - anticipos. Debe coincidir
		//    con la columna Saldo Neto del listado de Liquidaciones.
		$botPending = $this->_getPendingBotCommissionsByUser();
		$currentCommission = isset($botPending[$vendorId]) ? (float)$botPending[$vendorId]['amount'] : 0;
		$this->load->model('employeeadvances_model');
		$currentAdvances   = (float)$this->employeeadvances_model->getEmployeeBalance($vendorId);
		$currentBalance    = $currentCommission - $currentAdvances;

		// Anticipos activos (sección al pie)
		$activeAdvances = $this->employeeadvances_model->getActiveAdvancesForEmployee($vendorId);

		$data = array(
			'vendor'             => $vendor,
			'rows'               => $rows,
			'kpis'               => $kpis,
			'current_commission' => $currentCommission,
			'current_advances'   => $currentAdvances,
			'current_balance'    => $currentBalance,
			'active_advances'    => $activeAdvances,
			'from'               => $from,
			'to'                 => $to,
			'role'               => $this->session->userdata('user_data')['role'],
			'cashboxes'          => $this->_loadCashboxesForCurrentStore(),
			'bank_accounts'      => $this->_loadBankAccountsForCurrentStore(),
		);
		$this->load->view('sisvent/admin/settlements/statement', $data);
	}

	/**
	 * Carga cajas activas. Si admin tiene store específico filtra por bodega;
	 * super-admin ve todas (storeId=0 también, que aparece en todas las bodegas).
	 */
	private function _loadCashboxesForCurrentStore() {
		$this->load->model('cashboxes_model');
		$user = $this->session->userdata('user_data');
		$storeId = !empty($user['admin_store']) ? (int)explode(',', $user['admin_store'])[0] : null;
		if (!$storeId) {
			return $this->db->select('idCashbox AS id, name, storeId, currentBalance')
				->where('deleted', 0)
				->order_by('storeId', 'ASC')
				->get('cashboxes')->result();
		}
		return $this->db->select('idCashbox AS id, name, storeId, currentBalance')
			->where('deleted', 0)
			->group_start()
				->where('storeId', $storeId)
				->or_where('storeId', 0)
			->group_end()
			->order_by('name', 'ASC')
			->get('cashboxes')->result();
	}

	private function _loadBankAccountsForCurrentStore() {
		$user = $this->session->userdata('user_data');
		$storeId = !empty($user['admin_store']) ? (int)explode(',', $user['admin_store'])[0] : null;
		if (!$storeId) {
			return $this->db->select('idBankAccount AS id, bankName AS name, storeId, currentBalance')
				->where('deleted', 0)
				->order_by('storeId', 'ASC')
				->get('bank_accounts')->result();
		}
		return $this->db->select('idBankAccount AS id, bankName AS name, storeId, currentBalance')
			->where('deleted', 0)
			->group_start()
				->where('storeId', $storeId)
				->or_where('storeId', 0)
			->group_end()
			->order_by('bankName', 'ASC')
			->get('bank_accounts')->result();
	}

	/**
	 * Liquida todo el saldo pendiente de comisión bot de un operador.
	 * Cruza anticipos FIFO + paga remanente desde caja/banco.
	 *
	 * POST: vendor_id, source_type (caja|banco), source_id
	 * Retorna JSON con {success, commission_balance, crossed_total, cash_paid, advances_crossed}.
	 */
	public function payCommission()
	{
		header('Content-Type: application/json');
		$this->backend_lib->controlModule('cartera');

		$vendorId   = trim($this->input->post('vendor_id'));
		$sourceType = $this->input->post('source_type');
		$sourceId   = (int)$this->input->post('source_id');
		$amountRaw  = $this->input->post('amount'); // opcional — null/empty = todo
		$amount     = ($amountRaw === null || $amountRaw === '') ? null : (float)$amountRaw;
		$actor      = $this->session->userdata('user_data')['uname'];

		if (empty($vendorId) || !in_array($sourceType, array('caja','banco'), true) || !$sourceId) {
			echo json_encode(array('success' => false, 'message' => 'Parámetros inválidos'));
			return;
		}

		// Resolver subaccount contable + balance de caja/banco
		$user      = $this->users_model->getAnyUser($vendorId);
		$storeId   = ($sourceType === 'caja')
			? (int)$this->db->select('storeId')->where('idCashbox', $sourceId)->get('cashboxes')->row()->storeId
			: (int)$this->db->select('storeId')->where('idBankAccount', $sourceId)->get('bank_accounts')->row()->storeId;
		if ($storeId === 0) $storeId = 1; // storeId=0 (compartida) usa contabilidad de bodega 1

		$this->load->library('accounting_lib');
		$cashSubaccountId = ($sourceType === 'caja')
			? $this->accounting_lib->getCashAccount($storeId)
			: $this->accounting_lib->getBankAccount($storeId);
		if (!$cashSubaccountId) {
			echo json_encode(array('success' => false, 'message' => 'No se encontró cuenta contable de caja/banco para bodega ' . $storeId));
			return;
		}

		// Ejecutar (asientos + cruces de anticipo). amount=null → liquida todo.
		$result = $this->accounting_lib->payBotCommission($vendorId, $cashSubaccountId, $storeId, $actor, $amount);
		if (empty($result['success'])) {
			$reasonMsg = array(
				'no_balance'           => 'La persona no tiene saldo de comisión pendiente.',
				'cross_entry_failed'   => 'Falló asiento de cruce con anticipo.',
				'payment_entry_failed' => 'Falló asiento de pago en efectivo.',
				'transaction_failed'   => 'Falló la transacción contable.',
				'no_aux'               => 'No se pudo resolver auxiliar contable de la persona.',
				'missing_params'       => 'Parámetros faltantes.',
				'amount_invalid'       => 'Monto inválido.',
			);
			$msg = isset($reasonMsg[$result['reason']]) ? $reasonMsg[$result['reason']] : 'Error: ' . ($result['reason'] ?? 'desconocido');
			echo json_encode(array('success' => false, 'message' => $msg));
			return;
		}

		// Si hubo pago en efectivo: crear cash_movement + actualizar balance.
		// Si solo hubo cruces, no toca caja/banco.
		$cashPaid = (float)$result['cash_paid'];
		if ($cashPaid > 0) {
			$this->load->model('cashmovements_model');
			$this->cashmovements_model->save(array(
				'movementType'  => 'egreso',
				'sourceType'    => $sourceType,
				'sourceId'      => $sourceId,
				'amount'        => $cashPaid,
				'concept'       => 'Pago comisión bot — ' . ($user ? $user->name : $vendorId),
				'category'      => 'comision_bot',
				'referenceType' => 'bot_commission_payment',
				'referenceId'   => $vendorId,
				'executedBy'    => $actor,
				'movementDate'  => date('Y-m-d H:i:s'),
				'status'        => 'ejecutado',
			));
			if ($sourceType === 'caja') {
				$this->load->model('cashboxes_model');
				$this->cashboxes_model->updateBalance($sourceId, $cashPaid, 'subtract');
			} else {
				$this->load->model('bankaccounts_model');
				$this->bankaccounts_model->updateBalance($sourceId, $cashPaid, 'subtract');
			}
		}

		$liquidated = (float)($result['liquidated_total'] ?? $result['commission_balance']);
		$remaining  = (float)$result['commission_balance'] - $liquidated;
		$remainMsg  = $remaining > 0.001
			? sprintf(' · Queda pendiente: $%s', number_format($remaining, 0, ',', '.'))
			: '';
		echo json_encode(array(
			'success'            => true,
			'commission_balance' => $result['commission_balance'],
			'liquidated_total'   => $liquidated,
			'crossed_total'      => $result['crossed_total'],
			'cash_paid'          => $cashPaid,
			'advances_crossed'   => $result['advances_crossed'],
			'message'            => sprintf(
				'Liquidado: $%s. Cruzado: $%s · Efectivo: $%s%s',
				number_format($liquidated, 0, ',', '.'),
				number_format($result['crossed_total'], 0, ',', '.'),
				number_format($cashPaid, 0, ',', '.'),
				$remainMsg
			),
		));
	}

	public function detail($id)
	{
		$this->backend_lib->controlModule('cartera');

		$settlement = $this->vendor_settlement_model->getSettlement($id);
		if (!$settlement) show_404();

		$items = $this->vendor_settlement_model->getItems($id);
		$vouchers = $this->vendor_settlement_model->getVouchers($id);
		$summary = $this->vendor_settlement_model->getItemsSummaryByRule($id);

		// L.2 — anticipos cruzados con esta liquidación + balance proyectado
		$advanceCrosses = $this->employeeadvances_model->getCrossesForSettlement($id);
		$pendingAdvanceBalance = ($settlement->status !== 'pagado')
			? $this->employeeadvances_model->getEmployeeBalance($settlement->vendor_id)
			: 0;

		$data = array(
			'settlement'             => $settlement,
			'items'                  => $items,
			'vouchers'               => $vouchers,
			'summary'                => $summary,
			'advance_crosses'        => $advanceCrosses,
			'pending_advance_balance'=> $pendingAdvanceBalance,
			'role'                   => $this->session->userdata('user_data')['role'],
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
	/**
	 * v2.0.0: thin wrapper sobre Commissions_lib::compute(). Antes vivía
	 * inlineado aquí en ~110 líneas con las 7 reglas duplicadas. La lógica
	 * real ahora vive en application/libraries/Commissions_lib.php.
	 */
	private function _computeInvoiceCommission($invoice, $vend, $details)
	{
		$this->load->library('commissions_lib');
		$r = $this->commissions_lib->compute($invoice, $vend, $details);
		// Mapear al shape antiguo que esperan los callers de approve()/calculate()
		return array(
			'rule'            => $r['rule'],
			'base'            => $r['base'],
			'not_settle'      => $r['not_settle'],
			'percentage'      => $r['percentage'],
			'is_underpriced'  => $r['is_underpriced'],
			'amount'          => $r['amount'],
		);
	}
}