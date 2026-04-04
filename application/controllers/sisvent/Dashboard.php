<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

	private $bot_vendors = array('1234567', '1048937562', '12345678');

	public function __construct(){
		parent::__construct();
		$this->backend_lib->control();
		$this->load->model("products_model");
		$this->load->model("expenses_model");
        $this->load->model("vouchers_model");
        $this->load->model("invoices_model");
        $this->load->model("payments_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
        $this->load->model("inventory_model");
        $this->load->model("users_model");
        $this->load->model("cashboxes_model");
        $this->load->model("cashmovements_model");
        $this->load->model("bankaccounts_model");
	}

	public function index()
	{
		$userId = $this->session->userdata('user_data')['uname'];
		$user = $this->users_model->getAnyUser($userId);

		$page = $this->input->get('p');
		$page2 = $this->input->get('p2');
		

		$limit = 50;
		if(!$page)
			$page = 1;
		
		$total = $this->inventory_model->getTotal($user->store);
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		if(!$page2)
			$page2 = 1;
		
		$total2 = $this->inventory_model->getTotalNoInve($user->store);
		$last2       = ceil( $total2 / $limit );

		if($page2 > $last2)
			$page2 = $last2;

		if($page2 <= 0)
			$page2 = 1;

		$data = array(
			'settlement' => getVendorSettlement($userId)->total,
			'settlementiva' => getVendorSettlement($userId)->totaliva,
			'settlementnoiva' => getVendorSettlement($userId)->totalnoiva,
			'numClients' =>  $this->clients_model->clientCount($this->session->userdata('user_data')['role'] != 3),
			'lostInvoices' =>  $this->invoices_model->getTotalVendorLegalColletionInvoices($userId),
			'salesByMonth' =>  $this->invoices_model->getVendorSalesByMonth($userId, date("Y")),
			//'numClientsquery' =>  $this->db->last_query(),
			'paidInvoices' =>  $this->invoices_model->paidInvoicesCount($this->session->userdata('user_data')['role'] != 3),
			'lowInventory' =>  $this->inventory_model->getLowInventoryProducts($user->store, $page, $limit),
			'noInventory' =>  $this->inventory_model->getNoInventoryProducts($user->store, $page2, $limit),
			//'paidInvoicesquery' =>  $this->db->last_query(),
			'nonPaidInvoices' =>  $this->invoices_model->nonPaidInvoicesCount($this->session->userdata('user_data')['role'] != 3),
			//'nonPaidInvoicesquery' =>  $this->db->last_query(),
			'page' => $page,
			'page2' => $page2,
			'total' => $total,
			'total2' => $total2,
			'limit' => $limit,
		);

		// Datos específicos por rol
		$role = $this->session->userdata('user_data')['role'];
		$data['role'] = $role;
		date_default_timezone_set("America/Bogota");
		$todayStart = date('Y-m-d') . ' 00:00:00';
		$todayEnd = date('Y-m-d') . ' 23:59:59';

		// Admin / Superadmin (1, 2)
		if (in_array($role, [1, 2])) {
			$openCashboxes = $this->cashboxes_model->getActiveCashboxes();
			foreach ($openCashboxes as $cb) {
				$totals = $this->cashmovements_model->getTotalsBySource('caja', $cb->idCashbox, $todayStart, $todayEnd);
				$cb->todayIngress = $totals->totalIngress ?: 0;
				$cb->todayEgress = $totals->totalEgress ?: 0;
			}
			$data['openCashboxes'] = $openCashboxes;

			$activeBanks = $this->bankaccounts_model->getActiveBankAccounts();
			foreach ($activeBanks as $bank) {
				$totals = $this->cashmovements_model->getTotalsBySource('banco', $bank->idBankAccount, $todayStart, $todayEnd);
				$bank->todayIngress = $totals->totalIngress ?: 0;
				$bank->todayEgress = $totals->totalEgress ?: 0;
			}
			$data['activeBanks'] = $activeBanks;

			// Facturas hoy
			$this->db->where('DATE(date)', date('Y-m-d'))->where('deleted', 0);
			$data['facturasHoy'] = $this->db->count_all_results('invoices');

			// Ventas hoy
			$this->db->select('COALESCE(SUM(total),0) as t')->where('DATE(date)', date('Y-m-d'))->where('deleted', 0);
			$data['ventasHoy'] = (float)$this->db->get('invoices')->row()->t;
		}

		// Almacenista (4) — pedidos asignados
		if ($role == 4) {
			$this->db->where('asignado_a', $userId)->where('state', 0)->where('embalado', 0)->where('deleted', 0);
			$data['pendientesEmbalar'] = $this->db->count_all_results('budgets');

			$this->db->where('embalado_by', $userId)->where('DATE(embalado_at)', date('Y-m-d'));
			$data['embaladosHoy'] = $this->db->count_all_results('budgets');

			$this->db->select('budgets.*, clients.name as client_name, users.name as vendor_name')
				->from('budgets')
				->join('clients', 'clients.idClient = budgets.clientId', 'left')
				->join('users', 'users.idUser = budgets.vendorId', 'left')
				->where('budgets.asignado_a', $userId)
				->where('budgets.state', 0)->where('budgets.embalado', 0)->where('budgets.deleted', 0)
				->order_by('budgets.created_at', 'ASC')->limit(20);
			$data['pedidosPorEmbalar'] = $this->db->get()->result();
		}

		// Jefe Logística (9) — pipeline completo
		if ($role == 9) {
			$this->db->where('state', 0)->where('deleted', 0)->where('archived', 0)->where('(asignado_a IS NULL OR asignado_a = "")');
			$data['sinAsignar'] = $this->db->count_all_results('budgets');

			$this->db->where('state', 0)->where('embalado', 0)->where('deleted', 0)->where('archived', 0)->where('asignado_a IS NOT NULL')->where('asignado_a !=', '');
			$data['porEmbalar'] = $this->db->count_all_results('budgets');

			$this->db->where('state', 0)->where('embalado', 1)->where('deleted', 0);
			$data['porFacturar'] = $this->db->count_all_results('budgets');

			$this->db->where('transportadora', 'sin_despacho')->where('deleted', 0)->where('DATE(date) >=', date('Y-m-d', strtotime('-7 days')));
			$data['sinDespachar'] = $this->db->count_all_results('invoices');

			$this->db->where('DATE(despachado_at)', date('Y-m-d'))->where('deleted', 0);
			$data['despachadosHoy'] = $this->db->count_all_results('invoices');

			// Facturas hoy
			$this->db->where('DATE(date)', date('Y-m-d'))->where('deleted', 0);
			$data['facturasHoy'] = $this->db->count_all_results('invoices');
		}

		// Cartera (8)
		if ($role == 8) {
			$this->db->select('COALESCE(SUM(total - payment - discount), 0) as t')->where('state IN (0,1)')->where('deleted', 0);
			$data['carteraTotal'] = (float)$this->db->get('invoices')->row()->t;

			$this->db->select('COALESCE(SUM(total - payment - discount), 0) as t')
				->where('state IN (0,1)')->where('deleted', 0)
				->where('date <', date('Y-m-d', strtotime('-30 days')));
			$data['carteraVencida30'] = (float)$this->db->get('invoices')->row()->t;

			$this->db->select('COALESCE(SUM(total - payment - discount), 0) as t')
				->where('state IN (0,1)')->where('deleted', 0)
				->where('date <', date('Y-m-d', strtotime('-60 days')));
			$data['carteraVencida60'] = (float)$this->db->get('invoices')->row()->t;

			$this->db->select('COALESCE(SUM(amount), 0) as t')
				->where('MONTH(date)', date('n'))->where('YEAR(date)', date('Y'));
			$data['recaudoMes'] = (float)$this->db->get('payments')->row()->t;
		}

		// KPIs de Bots (todos los vendedores bot)
		date_default_timezone_set("America/Bogota");
		$mesInicio = date('Y-m-01');
		$mesFin = date('Y-m-t') . ' 23:59:59';
		$anioInicio = date('Y-01-01');
		$anioFin = date('Y-12-31') . ' 23:59:59';

		// Ventas bot del mes (presupuestos)
		$r = $this->db->select('COUNT(*) as cnt, COALESCE(SUM(total),0) as total')
			->where_in('vendorId', $this->bot_vendors)
			->where('date >=', $mesInicio)->where('date <=', $mesFin)
			->get('budgets')->row();
		$data['bot_ventas_mes'] = (int)$r->cnt;
		$data['bot_total_mes'] = (float)$r->total;

		// Ventas bot del año
		$r = $this->db->select('COUNT(*) as cnt, COALESCE(SUM(total),0) as total')
			->where_in('vendorId', $this->bot_vendors)
			->where('date >=', $anioInicio)->where('date <=', $anioFin)
			->get('budgets')->row();
		$data['bot_ventas_anio'] = (int)$r->cnt;
		$data['bot_total_anio'] = (float)$r->total;

		// Ventas bot hoy
		$r = $this->db->select('COUNT(*) as cnt, COALESCE(SUM(total),0) as total')
			->where_in('vendorId', $this->bot_vendors)
			->where('DATE(date)', date('Y-m-d'))
			->get('budgets')->row();
		$data['bot_ventas_hoy'] = (int)$r->cnt;
		$data['bot_total_hoy'] = (float)$r->total;

		$this->load->view("sisvent/dashboard", $data);
		//$this->load->view("layouts/footer");

	}

	
	/**
	 * Perfil del usuario
	 */
	public function profile()
	{
		$userId = $this->session->userdata('user_data')['uname'];
		$user = $this->users_model->getAnyUser($userId);

		if (!$user) redirect(base_url() . 'sisvent/dashboard');

		$data = array(
			'user' => $user,
			'success' => $this->session->flashdata('profile_success'),
			'error' => $this->session->flashdata('profile_error'),
		);
		$this->load->view('sisvent/profile', $data);
	}

	/**
	 * Actualizar perfil (POST)
	 */
	public function updateProfile()
	{
		if ($this->input->method() !== 'post') redirect(base_url() . 'sisvent/dashboard/profile');

		$userId = $this->session->userdata('user_data')['uname'];
		$user = $this->users_model->getAnyUser($userId);
		if (!$user) redirect(base_url() . 'sisvent/dashboard');

		date_default_timezone_set("America/Bogota");
		$update = array(
			'name'       => trim($this->input->post('name')),
			'email'      => trim($this->input->post('email')),
			'phone'      => trim($this->input->post('phone')),
			'address'    => trim($this->input->post('address')),
			'gender'     => $this->input->post('gender') ?: null,
			'updated_at' => date('Y-m-d H:i:s'),
		);

		// Si seleccionó género y no tiene foto custom, asignar avatar por defecto
		if ($update['gender'] && ($user->picture_url === 'users/general_1.png' || empty($user->picture_url))) {
			$update['picture_url'] = $update['gender'] === 'F' ? 'users/avatar_female.svg' : 'users/avatar_male.svg';
		}

		// Cambio de contraseña
		$newPass = $this->input->post('new_password');
		$confirmPass = $this->input->post('confirm_password');
		if (!empty($newPass)) {
			if ($newPass !== $confirmPass) {
				$this->session->set_flashdata('profile_error', 'Las contraseñas no coinciden.');
				redirect(base_url() . 'sisvent/dashboard/profile');
				return;
			}
			if (strlen($newPass) < 6) {
				$this->session->set_flashdata('profile_error', 'La contraseña debe tener al menos 6 caracteres.');
				redirect(base_url() . 'sisvent/dashboard/profile');
				return;
			}
			$update['password'] = password_hash($newPass, PASSWORD_BCRYPT);
		}

		// Foto de perfil
		if (!empty($_FILES['photo']['name'])) {
			$uploadPath = './public/dist/images/users';
			$config = array(
				'upload_path'   => $uploadPath,
				'allowed_types' => 'jpg|jpeg|png',
				'max_size'      => 2048,
				'file_name'     => 'profile_' . $userId,
				'overwrite'     => true,
			);
			$this->load->library('upload', $config);

			if ($this->upload->do_upload('photo')) {
				$uploaded = $this->upload->data();

				// Crop cuadrado y resize a 300x300
				$this->load->library('image_lib');
				$imgW = $uploaded['image_width'];
				$imgH = $uploaded['image_height'];

				if ($imgW != $imgH) {
					$cropSize = min($imgW, $imgH);
					$cropConfig = array(
						'image_library' => 'gd2',
						'source_image'  => $uploaded['full_path'],
						'maintain_ratio' => false,
						'width'  => $cropSize,
						'height' => $cropSize,
						'x_axis' => ($imgW - $cropSize) / 2,
						'y_axis' => ($imgH - $cropSize) / 2,
					);
					$this->image_lib->initialize($cropConfig);
					$this->image_lib->crop();
					$this->image_lib->clear();
				}

				$resizeConfig = array(
					'image_library' => 'gd2',
					'source_image'  => $uploaded['full_path'],
					'maintain_ratio' => true,
					'width'  => 300,
					'height' => 300,
				);
				$this->image_lib->initialize($resizeConfig);
				$this->image_lib->resize();

				$update['picture_url'] = 'users/' . $uploaded['file_name'];
			}
		}

		$this->users_model->update($userId, $update);

		// Actualizar sesión
		$ud = $this->session->userdata('user_data');
		$ud['name'] = $update['name'];
		$this->session->set_userdata('user_data', $ud);
		if (isset($update['picture_url'])) {
			$this->session->set_userdata('image', $update['picture_url']);
		}

		$this->session->set_flashdata('profile_success', 'Perfil actualizado correctamente.');
		redirect(base_url() . 'sisvent/dashboard/profile');
	}

	public function getLowInventoryProducts($store)
	{
		/*$data = array(
			'low' =>  $this->inventory_model->getLowInventoryProducts($store),
			
			//'nonPaidInvoicesquery' =>  $this->db->last_query(),
		);*/
		
		//$this->load->view("layouts/footer");
		echo "<pre>";
		print_r($this->inventory_model->getLowInventoryProducts($store));
		echo "</pre>";
		echo "<br>";
		print_r($this->db->last_query());
	}

	/**
	 * Búsqueda universal AJAX
	 * GET /sisvent/dashboard/search?q=texto
	 */
	public function search()
	{
		header('Content-Type: application/json');
		$q = trim($this->input->get('q'));
		if (strlen($q) < 2) {
			echo json_encode(array('results' => array()));
			return;
		}

		$results = array();

		// Clientes
		$clients = $this->db->select('idClient, name, idNum, cellphone')
			->like('name', $q)->or_like('idNum', $q)->or_like('cellphone', $q)
			->limit(5)->get('clients')->result();
		foreach ($clients as $c) {
			$results[] = array('type' => 'Cliente', 'title' => $c->name, 'subtitle' => 'Doc: ' . $c->idNum, 'url' => base_url() . 'sisvent/business/clients/edit/' . $c->idClient, 'icon' => 'user');
		}

		// Productos
		$products = $this->db->select('idProduct, description')
			->like('idProduct', $q)->or_like('description', $q)
			->limit(5)->get('products')->result();
		foreach ($products as $p) {
			$results[] = array('type' => 'Producto', 'title' => $p->idProduct, 'subtitle' => $p->description, 'url' => base_url() . 'sisvent/business/products/edit/' . $p->idProduct, 'icon' => 'box');
		}

		// Facturas
		if (is_numeric($q)) {
			$invoices = $this->db->select('idInvoice, total, date')
				->where('idInvoice', $q)->or_where('budgetId', $q)
				->where('deleted', 0)
				->limit(5)->get('invoices')->result();
			foreach ($invoices as $i) {
				$results[] = array('type' => 'Factura', 'title' => '#' . $i->idInvoice, 'subtitle' => '$' . number_format($i->total, 0, ',', '.') . ' — ' . $i->date, 'url' => base_url() . 'sisvent/commercial/invoices/view/' . $i->idInvoice, 'icon' => 'doc');
			}
		}

		// Vendedores/Usuarios
		$users = $this->db->select('idUser, name, role')
			->like('name', $q)->or_like('idUser', $q)
			->limit(5)->get('users')->result();
		foreach ($users as $u) {
			$results[] = array('type' => 'Usuario', 'title' => $u->name, 'subtitle' => 'ID: ' . $u->idUser, 'url' => base_url() . 'sisvent/business/users/edit/' . $u->idUser, 'icon' => 'users');
		}

		echo json_encode(array('results' => array_slice($results, 0, 15)));
	}

	public function viewunattclients(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendor = $this->input->post("id");
		$data  = array(
			'clients' => $this->clients_model->getUnattendedClients($vendor, date( "Y-m-d H:i:s", strtotime('-3 months'))), 
			'neverclients' => $this->clients_model->getNeverAttendedClients($vendor),
			'vendor' => $this->vendors_model->getVendor($vendor),
		);
		$this->load->view("sisvent/business/clients/unattendedview",$data);
	}

	public function viewlostinvoices(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$vendor = $this->input->post("id");
		$data  = array(
			'lostInvoices' => $this->invoices_model->getVendorLegalColletionInvoices($vendor), 
			'lq' => $this->db->last_query(),
			'vendor' => $this->vendors_model->getVendor($vendor),
		);
		$this->load->view("sisvent/commercial/invoices/lostinvoices",$data);
	}

	public function getUnattendedClients()
	{
		/*$data = array(
			'low' =>  $this->inventory_model->getLowInventoryProducts($store),
			
			//'nonPaidInvoicesquery' =>  $this->db->last_query(),
		);*/
		
		//$this->load->view("layouts/footer");
		echo "<pre>";
		print_r($this->clients_model->getUnattendedClients($this->session->userdata('user_data')['uname'], date( "Y-m-d H:i:s", strtotime('-3 months'))));
		echo "</pre>";
		echo "<br>";
		print_r($this->db->last_query());
	}

	public function getNeverAttendedClients()
	{
		/*$data = array(
			'low' =>  $this->inventory_model->getLowInventoryProducts($store),
			
			//'nonPaidInvoicesquery' =>  $this->db->last_query(),
		);*/
		
		//$this->load->view("layouts/footer");
		echo "<pre>";
		print_r($this->clients_model->getNeverAttendedClients($this->session->userdata('user_data')['uname']));
		echo "</pre>";
		echo "<br>";
		print_r($this->db->last_query());
	}

	public function blacklisted($client_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$data  = array(
				'blacklisted' => 1,
			);

		$this->clients_model->update($client_id,$data);
		//redirect(base_url()."sisvent/business/clients");
		echo base_url()."sisvent/dashboard";
	}

	public function prodnofotos()
	{

		$data  = array(
			'products' => $this->products_model->getProducts(), 
		);
		$this->load->view("sisvent/prodnofotos",$data);
		
	}
}