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
			if (!is_dir($uploadPath)) @mkdir($uploadPath, 0775, true);

			$config = array(
				'upload_path'   => $uploadPath,
				'allowed_types' => 'jpg|jpeg|png',
				'max_size'      => 2048,
				'file_name'     => 'profile_' . $userId,
				'overwrite'     => true,
			);
			$this->load->library('upload', $config);

			if (!$this->upload->do_upload('photo')) {
				$err = strip_tags($this->upload->display_errors('', ''));
				if (!is_writable($uploadPath)) $err .= ' (carpeta sin permisos de escritura)';
				$this->session->set_flashdata('profile_error', 'No se pudo guardar la foto: ' . $err);
				redirect(base_url() . 'sisvent/dashboard/profile');
				return;
			}

			{
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

	// ── CHAT INTERNO ──────────────────────────────────────────

	/**
	 * Lista de usuarios para el chat + conteo de no leídos
	 */
	public function chatUsers()
	{
		header('Content-Type: application/json');
		$myId = $this->session->userdata('user_data')['uname'];

		// Usuarios activos (no borrados, no archived)
		$users = $this->db->select('u.idUser, u.name, u.role, r.name as role_name, u.last_activity')
			->from('users u')
			->join('roles r', 'r.idRoles = u.role', 'left')
			->where('u.deleted', 0)
			->where('u.archived', 0)
			->where('u.idUser !=', $myId)
			->get()->result();

		$result = array();
		foreach ($users as $u) {
			// Contar mensajes no leídos de este usuario hacia mí
			$unread = $this->db->where('from_user', $u->idUser)
				->where('to_user', $myId)
				->where('is_read', 0)
				->count_all_results('internal_chat');

			// Online si actividad en últimos 5 min
			$isOnline = !empty($u->last_activity) && strtotime($u->last_activity) > strtotime('-5 minutes');

			$result[] = array(
				'idUser'    => $u->idUser,
				'name'      => $u->name,
				'role_name' => $u->role_name,
				'role'      => (int)$u->role,
				'unread'    => $unread,
				'is_online' => $isOnline,
			);
		}

		// Orden: 1) más mensajes sin leer primero
		//        2) en línea antes que offline
		//        3) jerarquía de rol (1=superadmin, 2=admin, 3=vendor, 4=storer, 8=cartera, 9=logistica, 10=superadminbots)
		//        4) nombre alfabético
		usort($result, function($a, $b) {
			if ($a['unread'] !== $b['unread']) return $b['unread'] - $a['unread'];
			if ($a['is_online'] !== $b['is_online']) return $b['is_online'] - $a['is_online'];
			// Prioridad por rol: 1 (super) > 10 (super bots) > 2 (admin) > resto
			$rank = function($r) {
				static $map = array(1 => 0, 10 => 1, 2 => 2, 8 => 3, 9 => 4, 4 => 5, 3 => 6);
				return isset($map[$r]) ? $map[$r] : 99;
			};
			$ra = $rank($a['role']); $rb = $rank($b['role']);
			if ($ra !== $rb) return $ra - $rb;
			return strcasecmp($a['name'], $b['name']);
		});

		// No leídos del chat general
		$unread_general = $this->db->where('to_user', null)
			->where('from_user !=', $myId)
			->where('is_read', 0)
			->where('created_at >', date('Y-m-d H:i:s', strtotime('-24 hours')))
			->count_all_results('internal_chat');

		echo json_encode(array('users' => $result, 'unread_general' => $unread_general));
	}

	/**
	 * Obtener mensajes de un chat
	 */
	public function chatMessages()
	{
		header('Content-Type: application/json');
		$myId = $this->session->userdata('user_data')['uname'];
		$chat = $this->input->get('chat');

		if ($chat === 'general') {
			// Chat general: últimos 50 mensajes donde to_user IS NULL
			$msgs = $this->db->select('cm.*, u.name as from_name')
				->from('internal_chat cm')
				->join('users u', 'u.idUser = cm.from_user', 'left')
				->where('cm.to_user IS NULL')
				->order_by('cm.created_at', 'ASC')
				->limit(50)
				->get()->result();
		} else {
			// Chat privado: mensajes entre yo y el otro usuario
			$msgs = $this->db->select('cm.*, u.name as from_name')
				->from('internal_chat cm')
				->join('users u', 'u.idUser = cm.from_user', 'left')
				->group_start()
					->group_start()
						->where('cm.from_user', $myId)
						->where('cm.to_user', $chat)
					->group_end()
					->or_group_start()
						->where('cm.from_user', $chat)
						->where('cm.to_user', $myId)
					->group_end()
				->group_end()
				->order_by('cm.created_at', 'ASC')
				->limit(50)
				->get()->result();

			// Marcar como leídos
			$this->db->where('from_user', $chat)
				->where('to_user', $myId)
				->where('is_read', 0)
				->update('internal_chat', array('is_read' => 1));
		}

		$result = array();
		foreach ($msgs as $m) {
			$result[] = array(
				'id' => $m->id,
				'from_user' => $m->from_user,
				'from_name' => $m->from_name ?: 'Usuario',
				'message' => htmlspecialchars($m->message, ENT_QUOTES, 'UTF-8'),
				'media_url' => !empty($m->media_url) ? base_url() . ltrim($m->media_url, '/') : null,
				'media_type' => $m->media_type ?? null,
				'media_name' => $m->media_name ?? null,
				'time' => date('H:i', strtotime($m->created_at)),
			);
		}

		echo json_encode(array('messages' => $result));
	}

	/**
	 * Enviar mensaje
	 */
	public function chatSend()
	{
		header('Content-Type: application/json');
		$myId = $this->session->userdata('user_data')['uname'];
		$to = $this->input->post('to');
		$message = trim((string)$this->input->post('message'));
		$media_url = trim((string)$this->input->post('media_url'));
		$media_type = $this->input->post('media_type');
		$media_name = $this->input->post('media_name');

		if (empty($message) && empty($media_url)) {
			echo json_encode(array('success' => false));
			return;
		}

		date_default_timezone_set("America/Bogota");
		$data = array(
			'from_user' => $myId,
			'to_user' => $to === 'general' ? null : $to,
			'message' => $message,
			'media_url' => $media_url ?: null,
			'media_type' => $media_type ?: null,
			'media_name' => $media_name ?: null,
			'created_at' => date('Y-m-d H:i:s'),
		);

		$this->db->insert('internal_chat', $data);
		echo json_encode(array('success' => true, 'id' => $this->db->insert_id()));
	}

	/**
	 * Contar mensajes no leídos (para badge)
	 */
	public function chatUnread()
	{
		header('Content-Type: application/json');
		$myId = $this->session->userdata('user_data')['uname'];

		$count = $this->db->where('to_user', $myId)
			->where('is_read', 0)
			->count_all_results('internal_chat');

		echo json_encode(array('count' => $count));
	}

	/**
	 * Reporte de actividad de usuarios (solo superadmin)
	 */
	public function userActivity()
	{
		$role = $this->session->userdata('user_data')['role'];
		if ($role != 1 && $role != 10) redirect(base_url() . 'sisvent/dashboard');

		date_default_timezone_set("America/Bogota");
		$date = $this->input->get('date') ?: date('Y-m-d');

		// Usuarios con su última actividad
		$users = $this->db->select('u.idUser, u.name, u.last_activity, r.name as role_name')
			->from('users u')
			->join('roles r', 'r.idRoles = u.role', 'left')
			->where('u.deleted', 0)
			->where('u.archived', 0)
			->order_by('u.last_activity', 'DESC')
			->get()->result();

		// Primer login y último logout del día por usuario
		$day_summary = array();
		$summary_rows = $this->db->select("user_id,
				MIN(CASE WHEN action = 'login' THEN created_at END) as first_login,
				MAX(CASE WHEN action = 'login' THEN created_at END) as last_login,
				MAX(CASE WHEN action = 'logout' THEN created_at END) as last_logout", false)
			->from('user_activity_log')
			->where('DATE(created_at)', $date)
			->group_by('user_id')
			->get()->result();
		foreach ($summary_rows as $sr) {
			$day_summary[$sr->user_id] = $sr;
		}

		// Log de actividad del día seleccionado
		$logs = $this->db->select('al.*, u.name as user_name')
			->from('user_activity_log al')
			->join('users u', 'u.idUser = al.user_id', 'left')
			->where('DATE(al.created_at)', $date)
			->order_by('al.created_at', 'DESC')
			->limit(200)
			->get()->result();

		$data = array(
			'users' => $users,
			'logs' => $logs,
			'date' => $date,
			'day_summary' => $day_summary,
		);
		$this->load->view('sisvent/admin/user_activity', $data);
	}

	/**
	 * AJAX: Buscar noticias via Google News RSS
	 * GET /sisvent/dashboard/news?q=inteligencia+artificial
	 */
	/**
	 * AJAX: Generar carta PDF y enviar por email
	 * POST /sisvent/dashboard/sendLetter
	 */
	public function sendLetter()
	{
		header('Content-Type: application/json');
		date_default_timezone_set("America/Bogota");

		$to_name = trim($this->input->post('to_name'));
		$to_email = trim($this->input->post('to_email'));
		$subject = trim($this->input->post('subject'));
		$body = trim($this->input->post('body'));
		$company = trim($this->input->post('company')) ?: '';

		if (empty($to_name) || empty($to_email) || empty($body)) {
			echo json_encode(array('success' => false, 'error' => 'Faltan datos: nombre, email y contenido son requeridos'));
			return;
		}

		if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
			echo json_encode(array('success' => false, 'error' => 'Email no valido: ' . $to_email));
			return;
		}

		$ud = $this->session->userdata('user_data');
		$from_name = isset($ud['name']) ? $ud['name'] : 'Ledxury';
		if (empty($subject)) $subject = 'Carta de Ledxury';

		// Generar HTML de la carta
		$fecha = date('d de F de Y');
		// Traducir mes
		$meses = array('January'=>'enero','February'=>'febrero','March'=>'marzo','April'=>'abril','May'=>'mayo','June'=>'junio','July'=>'julio','August'=>'agosto','September'=>'septiembre','October'=>'octubre','November'=>'noviembre','December'=>'diciembre');
		foreach ($meses as $en => $es) $fecha = str_replace($en, $es, $fecha);

		$bodyHtml = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));

		$html = '
		<div style="font-family: Arial, sans-serif; color: #333; padding: 40px;">
			<div style="text-align: center; margin-bottom: 30px; border-bottom: 3px solid #E63946; padding-bottom: 15px;">
				<h1 style="margin: 0; font-size: 28px; color: #1a1a2e; letter-spacing: 3px;">LEDXURY</h1>
				<p style="margin: 4px 0 0; font-size: 11px; color: #888; letter-spacing: 2px;">ILUMINACION LED DE ALTA TECNOLOGIA</p>
			</div>

			<p style="text-align: right; color: #666; font-size: 13px;">Medellin, ' . $fecha . '</p>

			<div style="margin-top: 30px;">
				<p style="font-size: 14px;"><strong>Senor(a):</strong><br>' . htmlspecialchars($to_name) . '</p>'
				. ($company ? '<p style="font-size: 14px; margin-top: -5px;"><strong>Empresa:</strong> ' . htmlspecialchars($company) . '</p>' : '') .
				'<p style="font-size: 14px; margin-top: -5px;"><strong>Email:</strong> ' . htmlspecialchars($to_email) . '</p>
			</div>

			<div style="margin-top: 20px; font-size: 14px; line-height: 1.7;">
				<p><strong>Asunto: ' . htmlspecialchars($subject) . '</strong></p>
				<p>Cordial saludo,</p>
				<p>' . $bodyHtml . '</p>
			</div>

			<div style="margin-top: 40px; font-size: 14px;">
				<p>Atentamente,</p>
				<br>
				<p style="margin: 0;"><strong>' . htmlspecialchars($from_name) . '</strong></p>
				<p style="margin: 0; color: #666;">Ledxury - Iluminacion LED</p>
				<p style="margin: 0; color: #666; font-size: 12px;">Medellin, Colombia</p>
			</div>

			<div style="margin-top: 40px; border-top: 2px solid #E63946; padding-top: 10px; text-align: center;">
				<p style="font-size: 10px; color: #999;">Este documento fue generado automaticamente por el sistema Ledxury.</p>
			</div>
		</div>';

		// Generar PDF con mPDF
		try {
			require_once FCPATH . 'vendor/autoload.php';
			$mpdf = new \Mpdf\Mpdf(array(
				'mode' => 'utf-8',
				'format' => 'Letter',
				'margin_left' => 15,
				'margin_right' => 15,
				'margin_top' => 15,
				'margin_bottom' => 15,
			));
			$mpdf->WriteHTML($html);

			$pdfPath = FCPATH . 'public/dist/cartas/';
			if (!is_dir($pdfPath)) mkdir($pdfPath, 0755, true);
			$fileName = 'carta_' . date('Ymd_His') . '_' . preg_replace('/[^a-z0-9]/', '', strtolower($to_name)) . '.pdf';
			$fullPath = $pdfPath . $fileName;
			$mpdf->Output($fullPath, 'F');

			// Enviar email con adjunto
			$emailHtml = '<div style="font-family:Arial,sans-serif;color:#333;">'
				. '<p>Estimado(a) ' . htmlspecialchars($to_name) . ',</p>'
				. '<p>' . $bodyHtml . '</p>'
				. '<p>Adjuntamos carta formal para su referencia.</p>'
				. '<br><p>Atentamente,<br><strong>' . htmlspecialchars($from_name) . '</strong><br>Ledxury - Iluminacion LED</p>'
				. '</div>';

			$this->email->clear(true);
			$config = array(
				'protocol' => 'smtp',
				'smtp_host' => 'ssl://smtp.gmail.com',
				'smtp_port' => 465,
				'smtp_user' => 'asistenciamam@gmail.com',
				'smtp_pass' => 'ssgdnzicymtfkhdc',
				'mailtype' => 'html',
				'charset' => 'utf-8',
			);
			$this->email->set_newline("\r\n");
			$this->email->initialize($config);
			$this->email->from('asistenciamam@gmail.com', 'Ledxury');
			$this->email->to($to_email);
			$this->email->subject($subject);
			$this->email->message($emailHtml);
			$this->email->attach($fullPath);

			$sent = $this->email->send();

			if ($sent) {
				echo json_encode(array('success' => true, 'message' => 'Carta enviada a ' . $to_email, 'pdf' => 'public/dist/cartas/' . $fileName));
			} else {
				echo json_encode(array('success' => false, 'error' => 'PDF generado pero error enviando email', 'pdf' => 'public/dist/cartas/' . $fileName));
			}

		} catch (Exception $e) {
			echo json_encode(array('success' => false, 'error' => 'Error generando carta: ' . $e->getMessage()));
		}
	}

	/**
	 * AJAX: Marcar producto agotado en presupuesto + notificar WhatsApp
	 * POST /sisvent/dashboard/markOutOfStock
	 */
	public function markOutOfStock()
	{
		header('Content-Type: application/json');
		date_default_timezone_set("America/Bogota");

		$role = $this->session->userdata('user_data')['role'];
		if (!in_array($role, [1, 9, 10])) {
			echo json_encode(array('success' => false, 'error' => 'Sin permisos'));
			return;
		}

		$budgetId = (int) $this->input->post('budget_id');
		$productId = trim($this->input->post('product_id'));

		if (!$budgetId || !$productId) {
			echo json_encode(array('success' => false, 'error' => 'Faltan datos: budget_id y product_id'));
			return;
		}

		// Verificar que existe el detalle
		$detail = $this->db->where('budgetId', $budgetId)->where('productId', $productId)
			->get('budget_detail')->row();
		if (!$detail) {
			echo json_encode(array('success' => false, 'error' => 'Producto ' . $productId . ' no encontrado en presupuesto ' . $budgetId));
			return;
		}

		// Obtener datos del cliente
		$budget = $this->db->select('b.*, c.name as client_name, c.cellphone, c.idNum')
			->from('budgets b')
			->join('clients c', 'c.idClient = b.clientId')
			->where('b.idBudget', $budgetId)
			->get()->row();

		if (!$budget) {
			echo json_encode(array('success' => false, 'error' => 'Presupuesto no encontrado'));
			return;
		}

		// Agregar comentario al presupuesto
		$comment = $budget->comments ?: '';
		$comment .= ' | AGOTADO: ' . $productId . ' (reportado por ' . $this->session->userdata('user_data')['name'] . ' ' . date('d/m H:i') . ')';
		$this->db->where('idBudget', $budgetId)->update('budgets', array('comments' => $comment));

		// Buscar colores alternativos con stock
		$codeParts = explode('-', $productId);
		$alternativas = '';
		if (count($codeParts) >= 3) {
			array_pop($codeParts);
			$baseCode = implode('-', $codeParts);
			$colorNames = array('A'=>'Blanco','B'=>'B.Calido','C'=>'Rojo','D'=>'Amarillo','E'=>'Azul','F'=>'Verde','G'=>'Rosado','H'=>'Morado','I'=>'Azul Ice','J'=>'Vde Limon','K'=>'Turquesa');

			$alts = $this->db->select('inv.idProduct, SUM(inv.stock) as total_stock')
				->from('inventory inv')
				->where('inv.idProduct LIKE', $baseCode . '-%')
				->where('inv.idProduct !=', $productId)
				->where('inv.idStore IN (1, 8)')
				->group_by('inv.idProduct')
				->having('total_stock >=', $detail->quantity)
				->get()->result();

			$opciones = array();
			foreach ($alts as $alt) {
				$altParts = explode('-', $alt->idProduct);
				$letter = end($altParts);
				$name = isset($colorNames[$letter]) ? $colorNames[$letter] : $letter;
				$opciones[] = $name . ' (' . $alt->total_stock . ' disp.)';
			}
			if (!empty($opciones)) $alternativas = implode(', ', $opciones);
		}

		// Enviar WhatsApp al cliente
		$whatsappSent = false;
		if (!empty($budget->cellphone)) {
			$phone = preg_replace('/\D/', '', $budget->cellphone);
			if (substr($phone, 0, 2) !== '57' && substr($phone, 0, 1) === '3') $phone = '57' . $phone;

			$nombre = explode(' ', trim($budget->client_name))[0];
			$mensaje = "Hola " . $nombre . "!\n\n"
				. "Te escribimos de Ledxury sobre tu pedido #" . $budgetId . ".\n\n"
				. "El producto " . $productId . " no esta disponible en este momento.\n";

			if ($alternativas) {
				$mensaje .= "\nColores disponibles: " . $alternativas . "\n"
					. "\nPor favor respondenos con el color que prefieras.\n";
			} else {
				$mensaje .= "\nEn este momento no tenemos alternativas disponibles. Te contactaremos cuando llegue stock.\n";
			}
			$mensaje .= "\nGracias por tu comprension!";

			$this->load->library('builderbot_lib');
			$this->load->model('builderbot_model');
			$configs = $this->builderbot_model->getConfigs();
			foreach ($configs as $cfg) {
				$result = $this->builderbot_lib->sendMessage($cfg, $phone, $mensaje);
				if ($result['success']) { $whatsappSent = true; break; }
			}
		}

		echo json_encode(array(
			'success' => true,
			'message' => 'Producto ' . $productId . ' marcado como agotado en pedido #' . $budgetId,
			'whatsapp_sent' => $whatsappSent,
			'alternativas' => $alternativas,
			'client_name' => $budget->client_name,
		));
	}

	/**
	 * AJAX: Modificar precio unitario de un producto en presupuesto
	 * POST /sisvent/dashboard/updateBudgetPrice
	 */
	public function updateBudgetPrice()
	{
		header('Content-Type: application/json');
		date_default_timezone_set("America/Bogota");

		$role = $this->session->userdata('user_data')['role'];
		if (!in_array($role, [1, 9, 10])) {
			echo json_encode(array('success' => false, 'error' => 'Sin permisos'));
			return;
		}

		$budgetId = (int) $this->input->post('budget_id');
		$productId = trim($this->input->post('product_id'));
		$newPrice = (int) $this->input->post('new_price');

		if (!$budgetId || !$productId || $newPrice <= 0) {
			echo json_encode(array('success' => false, 'error' => 'Faltan datos: budget_id, product_id y new_price'));
			return;
		}

		// Verificar que existe
		$detail = $this->db->where('budgetId', $budgetId)->where('productId', $productId)
			->get('budget_detail')->row();
		if (!$detail) {
			echo json_encode(array('success' => false, 'error' => 'Producto ' . $productId . ' no encontrado en presupuesto ' . $budgetId));
			return;
		}

		$oldPrice = $detail->unit;
		$newTotal = $newPrice * $detail->quantity;

		// Actualizar detalle
		$this->db->where('budgetId', $budgetId)->where('productId', $productId)
			->update('budget_detail', array(
				'unit' => $newPrice,
				'total' => $newTotal,
			));

		// Recalcular total del presupuesto
		$sumTotal = $this->db->select('COALESCE(SUM(total),0) as t')
			->where('budgetId', $budgetId)->get('budget_detail')->row()->t;
		$this->db->where('idBudget', $budgetId)->update('budgets', array('total' => $sumTotal));

		// Log en comentarios
		$budget = $this->db->where('idBudget', $budgetId)->get('budgets')->row();
		$comment = ($budget->comments ?: '') . ' | PRECIO: ' . $productId . ' $' . number_format($oldPrice,0,',','.') . ' -> $' . number_format($newPrice,0,',','.') . ' (' . $this->session->userdata('user_data')['name'] . ' ' . date('d/m H:i') . ')';
		$this->db->where('idBudget', $budgetId)->update('budgets', array('comments' => $comment));

		echo json_encode(array(
			'success' => true,
			'message' => 'Precio actualizado: ' . $productId . ' de $' . number_format($oldPrice,0,',','.') . ' a $' . number_format($newPrice,0,',','.'),
			'old_price' => $oldPrice,
			'new_price' => $newPrice,
			'new_total' => $sumTotal,
		));
	}

	/**
	 * AJAX: Aprobar presupuesto y crear factura (por voz)
	 * POST /sisvent/dashboard/approveBudget
	 */
	public function approveBudget()
	{
		header('Content-Type: application/json');
		date_default_timezone_set("America/Bogota");

		$role = $this->session->userdata('user_data')['role'];
		if (!in_array($role, [1, 2, 9, 10])) {
			echo json_encode(array('success' => false, 'error' => 'Sin permisos para aprobar'));
			return;
		}

		$budgetId = (int) $this->input->post('budget_id');
		if (!$budgetId) {
			echo json_encode(array('success' => false, 'error' => 'Falta budget_id'));
			return;
		}

		$this->load->model('budgets_model');
		$this->load->model('invoices_model');
		$this->load->model('clients_model');
		$this->load->model('inventory_model');

		$budget = $this->budgets_model->getBudget($budgetId);
		if (!$budget) {
			echo json_encode(array('success' => false, 'error' => 'Presupuesto ' . $budgetId . ' no encontrado'));
			return;
		}

		if ($budget->state == 1) {
			echo json_encode(array('success' => false, 'error' => 'El presupuesto ' . $budgetId . ' ya fue aprobado'));
			return;
		}

		$client = $this->clients_model->getClient($budget->clientId);
		$details = $this->budgets_model->getDetails($budgetId);

		if (empty($details)) {
			echo json_encode(array('success' => false, 'error' => 'El presupuesto no tiene productos'));
			return;
		}

		// Aprobar presupuesto
		$this->budgets_model->update($budgetId, array('state' => 1));

		// Crear factura
		$invoiceData = array(
			'budgetId' => $budget->idBudget,
			'clientId' => $budget->clientId,
			'vendorId' => $budget->vendorId,
			'storeId' => $budget->storeId,
			'total' => $budget->total,
			'date' => date('Y-m-d H:i:s'),
			'state' => 0,
			'e_commerce' => $budget->e_commerce,
			'list_price' => $budget->list_price,
			'hasIva' => $budget->hasIva,
			'iva' => $budget->iva,
			'payment' => 0,
			'comments' => $budget->comments,
		);

		$this->invoices_model->save($invoiceData);
		$invoiceId = $this->invoices_model->lastID();

		// Copiar detalles y descontar inventario
		foreach ($details as $d) {
			$this->invoices_model->save_detail(array(
				'invoiceId' => $invoiceId,
				'productId' => $d->productId,
				'quantity' => $d->quantity,
				'unit' => $d->unit,
				'base' => $d->base,
				'total' => $d->subtotal,
			));

			// Descontar inventario
			$this->db->set('stock', 'stock - ' . (int)$d->quantity, false)
				->where('idProduct', $d->productId)
				->where('idStore', $budget->storeId)
				->update('inventory');
		}

		// Log
		$userName = $this->session->userdata('user_data')['name'];
		$comment = ($budget->comments ?: '') . ' | APROBADO por ' . $userName . ' ' . date('d/m H:i') . ' → Factura #' . $invoiceId;
		$this->db->where('idBudget', $budgetId)->update('budgets', array('comments' => $comment));

		echo json_encode(array(
			'success' => true,
			'invoice_id' => $invoiceId,
			'budget_id' => $budgetId,
			'total' => $budget->total,
			'client_name' => $client ? $client->name : '',
			'message' => 'Presupuesto #' . $budgetId . ' aprobado. Factura #' . $invoiceId . ' creada.',
		));
	}

	public function news()
	{
		header('Content-Type: application/json');
		$query = trim($this->input->get('q'));
		if (empty($query)) {
			echo json_encode(array('success' => false, 'error' => 'Falta parametro q'));
			return;
		}

		$url = 'https://news.google.com/rss/search?q=' . urlencode($query) . '&hl=es-419&gl=CO&ceid=CO:es-419';

		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 15,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => true,
		));
		$xml = curl_exec($ch);
		curl_close($ch);

		if (empty($xml)) {
			echo json_encode(array('success' => false, 'error' => 'No se pudo conectar a Google News'));
			return;
		}

		$rss = @simplexml_load_string($xml);
		if (!$rss || !isset($rss->channel->item)) {
			echo json_encode(array('success' => false, 'error' => 'Error parseando RSS'));
			return;
		}

		$news = array();
		$count = 0;
		foreach ($rss->channel->item as $item) {
			if ($count >= 5) break;
			$title = (string) $item->title;
			// Separar título de la fuente (formato: "Titulo - Fuente")
			$parts = explode(' - ', $title);
			$source = count($parts) > 1 ? array_pop($parts) : '';
			$headline = implode(' - ', $parts);

			$news[] = array(
				'title' => $headline,
				'source' => $source,
				'date' => date('d/m H:i', strtotime((string) $item->pubDate)),
			);
			$count++;
		}

		echo json_encode(array('success' => true, 'news' => $news, 'query' => $query));
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