<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->controlModule('usuarios');
        $this->load->model("users_model");
        $this->load->model("stores_model");
        $this->load->library('accounting_lib');
        $this->load->helper('mam'); // current_tenant_id(), is_platform_admin()
    }

    /** Tenants activos para el selector (solo lo usa el platform admin). */
    private function _tenantsForSelect() {
        return $this->db->where('active', 1)->order_by('name')->get('tenants')->result();
    }

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		$data  = array(
			'users' => $this->users_model->getUsers(false),
		);
		$this->load->view("sisvent/business/users/list",$data);
		
	}

	public function add(){

		$data =array(
			"stores" => $this->stores_model->getStores(),
			"roles" => $this->users_model->getRoles(),
			"tenants" => $this->_tenantsForSelect()
		);
		$this->load->view("sisvent/business/users/add", $data);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$user_id = $this->input->post("user_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$phone = $this->input->post("phone");
		$f_id = $this->input->post("f_id");
		$address = $this->input->post("address");
		$password = $this->input->post("password");
		$passconf = $this->input->post("passconf");
		$role = $this->input->post("role");
		$liststores = $this->input->post("admin_store") ?? [];
		$storesstr = implode(',', $liststores);

		$this->form_validation->set_rules("user_id","Identificación","required|is_unique[users.idUser]");
		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("email","Email","valid_email");
		$this->form_validation->set_rules("phone","Teléfono","numeric");
		$this->form_validation->set_rules('password', 'Contraseña', 'required|min_length[8]');
		//if(!empty($passconf))
		$this->form_validation->set_rules('passconf', 'Confirmar Contraseña', 'required|matches[password]');
		if($role == 1 && sizeof($liststores) == 0)
			$this->form_validation->set_rules('admin_store', 'Administrador de la bodega', 'required');

		if ($this->form_validation->run()) {
			$data  = array(
				'idUser' => $user_id, 
				'name' => $name,
				'email' => $email,
				'f_id' => $f_id,
				'store' => 1,
				'phone' => $phone,
				'address' => $address,
				'admin_store' => $storesstr,
				'password' => password_hash($password, PASSWORD_BCRYPT),
				'role' => $role
			);

			// Actúa también como vendedor (migración 061): habilita al usuario en
			// listas de vendedores y liquidaciones sin importar su rol.
			$data['is_vendor'] = $this->input->post('is_vendor') ? 1 : 0;

			// Tenant + platform admin: solo un platform admin puede asignar empresa
			// o marcar a alguien como platform admin. El resto crea usuarios dentro
			// de su propia empresa. Evita escalamiento entre tenants.
			$isPA = is_platform_admin();
			$data['tenant_id'] = $isPA
				? (int)($this->input->post('tenant_id') ?: current_tenant_id())
				: (int)current_tenant_id();
			$data['is_platform_admin'] = ($isPA && $this->input->post('is_platform_admin')) ? 1 : 0;

			if(isset($_FILES['imageAvatar']) && is_uploaded_file($_FILES['imageAvatar']['tmp_name'])) {
				
					$path = $_FILES['imageAvatar']['name'];
				    $ext = pathinfo($path, PATHINFO_EXTENSION);
				    $file = $_FILES['imageAvatar']['tmp_name'];

					$config['allowed_types']='jpg|png';
					$config['upload_path']='./public/dist/images/users';
					$config['file_name']= substr( $name, 0,2).$user_id;
					$config['overwrite']=true;

					$this->load->library('upload',$config);
					
					$image_data = $this->upload->data();

					list($width, $height) = getimagesize($file);

					if (!is_dir('./public/dist/images/users/')) {
						//print_r("<br> Creando directorio ".'./public/dist/images/users/'.'pf'.substr( $this->session->userdata('user_data')['user_name'], 0,2).$this->session->userdata('user_data')['user_uname']);
		            	mkdir('./public/dist/images/users/', 0777, true);
		        	}

		        	if($this->upload->do_upload('imageAvatar')){
			    	
				    	$data['picture_url']='users/'.($image_data['file_name'].".".$ext);
						//$this->session->set_userdata('image', $data['picture_url']);
					    $error = "";
					
						$imgdata=exif_read_data($this->upload->upload_path.$this->upload->file_name, 'IFD0');

						//Set config for img library
						$config['image_library'] = 'gd2';
						$config['source_image'] = $this->upload->data('full_path');//'./assets/avatarPictures/userPictures/'.$image_data['file_name'].".".$ext;
						$config['maintain_ratio'] = false;
						//Set cropping for y or x axis, depending on image orientation
						if ($width > $height) {
						    $config['width'] = $height;
						    $config['height'] = $height;
						    $config['x_axis'] = (($width / 2) - ($config['width'] / 2));
						}
						else {
						    $config['height'] = $width;
						    $config['width'] = $width;
						    $config['y_axis'] = (($height / 2) - ($config['height'] / 2));
						}

						//Load image library and crop
						$this->load->library('image_lib');
						$this->image_lib->initialize($config);
						if (!$this->image_lib->crop()) {
						    $error = "crop: ".$this->image_lib->display_errors();
							//print_r($error);
						}
						$this->image_lib->clear();
						unset($config);
							
						// resizing image
						$config['image_library'] = 'gd2';
					    $config['source_image'] = $this->upload->data('full_path');//'./assets/avatarPictures/userPictures/'.$image_data['file_name'].".".$ext;//$image_data['full_path'].;
					    $config['maintain_ratio'] = TRUE;
					    $config['width']     = 300;
					    $config['height']   = 300;
					    $config['x_axis'] = 0;
						$config['y_axis'] = 0;
						$this->image_lib->initialize($config); 
					    if(!$this->image_lib->resize()){
							//print_r("exito");
							//redirect(base_url().'wall/index');
					    //}else
					    //{
					    	$error .= " resize: ".$this->image_lib->display_errors();//array('error' => $this->image_lib->display_errors());
							//$this->session->set_flashdata("error",$error);
							//redirect(base_url().'wall/index');
					    }
					    //Clear image library settings so we can do some more image 
						//manipulations if we have to
					    $this->image_lib->clear();
						unset($config);

						if ($this->users_model->save($data)) {
							$this->accounting_lib->getOrCreateUserAuxAccount($user_id);
							redirect(base_url()."sisvent/business/users");
						}
						else{
							$this->session->set_flashdata("error","No se pudo guardar la información");
							$this->add();
							//redirect(base_url()."sisvent/business/users/add");
						}
					}
					else {
						$error = $this->upload->display_errors();//array('error' => $this->upload->display_errors());
						$this->session->set_flashdata("error",$error);
						$this->add();
						//redirect(base_url().'sisvent/business/users/add');
					}

			}else
			{
				if ($this->users_model->save($data)) {
					$this->accounting_lib->getOrCreateUserAuxAccount($user_id);
					redirect(base_url()."sisvent/business/users");
				}
				else{
					$this->session->set_flashdata("error","No se pudo guardar la información");
					$this->add();
					//redirect(base_url()."sisvent/business/users/add");
				}
				
			}
		}
		else{
			$this->add();
		}
	}

	public function edit($user_id){
		$user = $this->users_model->getAnyUser($user_id);
		$user->admin_store_arr = explode(',', $user->admin_store);

		$data =array(
			"stores" => $this->stores_model->getStores(),
			'user' => $user,
			'roles' => $this->users_model->getRoles(),
			'auxAccount' => $this->users_model->getUserAuxAccount($user_id),
			'tenants' => $this->_tenantsForSelect(),
			// Comisiones vigentes (bot_commission_config): una persona puede
			// tener varias a la vez (ej: 1% de pauta + 7% por su bot).
			'commissions' => $this->db->where('user_id', $user_id)
				->order_by('is_active', 'DESC')->order_by('id', 'ASC')
				->get('bot_commission_config')->result(),
		);
		$this->load->view("sisvent/business/users/edit",$data);
	}

	/**
	 * Guarda las comisiones enviadas desde la ficha del usuario en
	 * bot_commission_config (la tabla que realmente liquida). Misma validación
	 * que Comisiones::configSave. Una fila sin porcentaje válido se ignora si
	 * es nueva, o se pausa si ya existía.
	 */
	private function _saveUserCommissions($userId)
	{
		$types = $this->input->post('comm_type');
		if (!is_array($types) || empty($userId)) return;

		$ids     = $this->input->post('comm_id');
		$percs   = $this->input->post('comm_perc');
		$bases   = $this->input->post('comm_basis');
		$actives = $this->input->post('comm_active');
		$validTypes = array('admin_bots', 'operator', 'ads_manager');
		$validBasis = array('ventas', 'recaudo', 'margen');

		foreach ($types as $i => $type) {
			if (!in_array($type, $validTypes, true)) continue;
			$id    = isset($ids[$i]) ? (int)$ids[$i] : 0;
			$perc  = isset($percs[$i]) ? (float)str_replace(',', '.', (string)$percs[$i]) : 0;
			$basis = (isset($bases[$i]) && in_array($bases[$i], $validBasis, true)) ? $bases[$i] : 'recaudo';
			$act   = isset($actives[$i]) ? (int)$actives[$i] : 1;

			if ($perc <= 0 || $perc > 100) {
				// Fila existente que quedó sin porcentaje válido: se pausa, no se borra
				if ($id > 0) $this->db->where('id', $id)->where('user_id', $userId)
					->update('bot_commission_config', array('is_active' => 0));
				continue;
			}

			$payload = array(
				'user_id'         => $userId,
				'commission_type' => $type,
				'percentage'      => $perc,
				'basis'           => $basis,
				'is_active'       => $act ? 1 : 0,
			);
			if ($id > 0) {
				// El filtro por user_id evita editar la fila de otro usuario
				$this->db->where('id', $id)->where('user_id', $userId)
					->update('bot_commission_config', $payload);
			} else {
				$this->db->insert('bot_commission_config', $payload);
			}
		}
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$user_id = $this->input->post("user_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$phone = $this->input->post("phone");
		$f_id = $this->input->post("f_id");
		$address = $this->input->post("address");
		$password = $this->input->post("password");
		$passconf = $this->input->post("passconf");
		$role = $this->input->post("role");
		$liststores = $this->input->post("admin_store") ?? [];
		$storesstr = implode(',', $liststores);

		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("email","Email","valid_email");
		$this->form_validation->set_rules("phone","Teléfono","numeric");
		
		if($role == 1 && sizeof($liststores) == 0)
			$this->form_validation->set_rules('admin_store', 'Administrador de la bodega', 'required');

		if(!empty($password))
		{
			$this->form_validation->set_rules('password', 'Contraseña', 'min_length[8]');
			$this->form_validation->set_rules('passconf', 'Confirmar Contraseña', 'required|matches[password]');
		}
		if ($this->form_validation->run()) {
			if(!empty($password))
			{
				$data  = array(
					'name' => $name,
					'email' => $email,
					'f_id' => $f_id,
					'phone' => $phone,
					'address' => $address,
					'admin_store' => $storesstr,
					'password' => password_hash($password, PASSWORD_BCRYPT),
					'role' => $role
				);
			}
			else
			{
				$data  = array(
					'name' => $name,
					'email' => $email,
					'phone' => $phone,
					'f_id' => $f_id,
					'admin_store' => $storesstr,
					'address' => $address,
					'role' => $role
				);
			}

			// Actúa también como vendedor (migración 061)
			$data['is_vendor'] = $this->input->post('is_vendor') ? 1 : 0;

			// Tenant + platform admin: solo un platform admin puede cambiar a qué
			// empresa pertenece un usuario o marcarlo como platform admin.
			if (is_platform_admin()) {
				$data['tenant_id'] = (int)($this->input->post('tenant_id') ?: current_tenant_id());
				$data['is_platform_admin'] = $this->input->post('is_platform_admin') ? 1 : 0;
			}

			if(isset($_FILES['imageAvatar']) && is_uploaded_file($_FILES['imageAvatar']['tmp_name'])) {
				
					$path = $_FILES['imageAvatar']['name'];
				    $ext = pathinfo($path, PATHINFO_EXTENSION);
				    $file = $_FILES['imageAvatar']['tmp_name'];

					$config['allowed_types']='jpg|png';
					$config['upload_path']='./public/dist/images/users';
					$config['file_name']= substr( $name, 0,2).$user_id;
					$config['overwrite']=true;

					$this->load->library('upload',$config);
					
					$image_data = $this->upload->data();

					list($width, $height) = getimagesize($file);

					if (!is_dir('./public/dist/images/users/')) {
						//print_r("<br> Creando directorio ".'./public/dist/images/users/'.'pf'.substr( $this->session->userdata('user_data')['user_name'], 0,2).$this->session->userdata('user_data')['user_uname']);
		            	mkdir('./public/dist/images/users/', 0777, true);
		        	}

		        	if($this->upload->do_upload('imageAvatar')){
			    	
				    	$data['picture_url']='users/'.($image_data['file_name'].".".$ext);
						//$this->session->set_userdata('image', $data['picture_url']);
					    $error = "";
					
						$imgdata=exif_read_data($this->upload->upload_path.$this->upload->file_name, 'IFD0');

						//Set config for img library
						$config['image_library'] = 'gd2';
						$config['source_image'] = $this->upload->data('full_path');//'./assets/avatarPictures/userPictures/'.$image_data['file_name'].".".$ext;
						$config['maintain_ratio'] = false;
						//Set cropping for y or x axis, depending on image orientation
						if ($width > $height) {
						    $config['width'] = $height;
						    $config['height'] = $height;
						    $config['x_axis'] = (($width / 2) - ($config['width'] / 2));
						}
						else {
						    $config['height'] = $width;
						    $config['width'] = $width;
						    $config['y_axis'] = (($height / 2) - ($config['height'] / 2));
						}

						//Load image library and crop
						$this->load->library('image_lib');
						$this->image_lib->initialize($config);
						if (!$this->image_lib->crop()) {
						    $error = "crop: ".$this->image_lib->display_errors();
							//print_r($error);
						}
						$this->image_lib->clear();
						unset($config);
							
						// resizing image
						$config['image_library'] = 'gd2';
					    $config['source_image'] = $this->upload->data('full_path');//'./assets/avatarPictures/userPictures/'.$image_data['file_name'].".".$ext;//$image_data['full_path'].;
					    $config['maintain_ratio'] = TRUE;
					    $config['width']     = 300;
					    $config['height']   = 300;
					    $config['x_axis'] = 0;
						$config['y_axis'] = 0;
						$this->image_lib->initialize($config); 
					    if(!$this->image_lib->resize()){
							//print_r("exito");
							//redirect(base_url().'wall/index');
					    //}else
					    //{
					    	$error .= " resize: ".$this->image_lib->display_errors();//array('error' => $this->image_lib->display_errors());
							//$this->session->set_flashdata("error",$error);
							//redirect(base_url().'wall/index');
					    }
					    //Clear image library settings so we can do some more image 
						//manipulations if we have to
					    $this->image_lib->clear();
						unset($config);

						if ($this->users_model->update($user_id,$data)) {
							$this->_syncUserAuxAccount($user_id, $role);
							$this->_saveUserCommissions($user_id);
							redirect(base_url()."sisvent/business/users");
						}
						else{
							$this->session->set_flashdata("error","No se pudo actualizar la información");
							$this->edit($user_id);
							//redirect(base_url()."sisvent/business/users/edit/".$user_id);
						}
					}
					else {
						$error = $this->upload->display_errors();//array('error' => $this->upload->display_errors());
						$this->session->set_flashdata("error",$error);
						$this->edit($user_id);
						//redirect(base_url().'sisvent/business/users/add');
					}

			}else
			{
				if ($this->users_model->update($user_id,$data)) {
					$this->_syncUserAuxAccount($user_id, $role);
					$this->_saveUserCommissions($user_id);
					redirect(base_url()."sisvent/business/users");
				}
				else{
					$this->session->set_flashdata("error","No se pudo actualizar la información");
					//redirect(base_url()."sisvent/business/users/edit/".$user_id);
					$this->edit($user_id);
				}
			}
		}
		else{
			$this->edit($user_id);
		}
	}

	public function delete($user_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$this->users_model->remove($user_id);
		//redirect(base_url()."sisvent/business/users");
		echo base_url()."sisvent/business/users";
	}

	/**
	 * Crear cuenta contable auxiliar para un usuario desde la lista.
	 */
	public function createAccount($user_id) {
		$role = $this->session->userdata('user_data')['role'];
		if ($role != 1) {
			redirect(base_url()."sisvent/business/users");
		}

		$user = $this->users_model->getAnyUser($user_id);
		if ($user) {
			$this->accounting_lib->getOrCreateUserAuxAccount($user_id);
			$this->session->set_flashdata('success', 'Cuenta contable creada para ' . $user->name);
		}
		redirect(base_url()."sisvent/business/users");
	}

	/**
	 * Sincroniza la cuenta auxiliar del usuario cuando cambia de rol.
	 */
	private function _syncUserAuxAccount($userId, $newRole) {
		$pucCode = $this->users_model->getRolePucCode($newRole);
		$existing = $this->users_model->getUserAuxAccount($userId);

		if (!$pucCode) {
			// Nuevo rol no tiene PUC - soft-delete aux existente
			if ($existing) {
				$this->db->where('id', $existing->id);
				$this->db->update('auxiliary_subaccounts', array(
					'deleted' => 1,
					'deleted_at' => date('Y-m-d H:i:s')
				));
			}
			return;
		}

		$expectedType = ($pucCode == '231001') ? 'partner' : 'employee';

		if ($existing && $existing->accountType != $expectedType) {
			// Tipo cambio (employee<->partner) - soft-delete viejo
			$this->db->where('id', $existing->id);
			$this->db->update('auxiliary_subaccounts', array(
				'deleted' => 1,
				'deleted_at' => date('Y-m-d H:i:s')
			));
		}

		$this->accounting_lib->getOrCreateUserAuxAccount($userId);
	}
}