<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vendors extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
        $this->load->model("vendors_model");
        $this->load->model("stores_model");
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
			'vendors' => $this->vendors_model->getVendors(), 
		);
		$this->load->view("sisvent/business/vendors/list",$data);
		
	}

	public function add(){
		$data =array( 
			"stores" => $this->stores_model->getStores()
		);
		$this->load->view("sisvent/business/vendors/add",$data);
	}

	public function store(){
		$user_id = $this->input->post("user_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$phone = $this->input->post("phone");
		$address = $this->input->post("address");
		$password = $this->input->post("password");
		$passconf = $this->input->post("passconf");
		$store = $this->input->post("store");

		$this->form_validation->set_rules("user_id","Identificación","required|is_unique[users.idUser]");
		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("email","Email","valid_email");
		$this->form_validation->set_rules("phone","Teléfono","numeric");
		$this->form_validation->set_rules('password', 'Contraseña', 'required|min_length[8]');
		//if(!empty($passconf))
		$this->form_validation->set_rules('passconf', 'Confirmar Contraseña', 'required|matches[password]');


		if ($this->form_validation->run()) {
			$data  = array(
				'idUser' => $user_id, 
				'name' => $name,
				'email' => $email,
				'phone' => $phone,
				'address' => $address,
				'password' => password_hash($password, PASSWORD_BCRYPT),
				'store' => $store,
				'role' => 3
			);

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

						if ($this->vendors_model->save($data)) {
							redirect(base_url()."sisvent/business/vendors");
						}
						else{
							$this->session->set_flashdata("error","No se pudo guardar la información");
							$this->add();
							//redirect(base_url()."sisvent/business/vendors/add");
						}

		                /*$config['image_library'] = 'gd2';
		                $config['source_image'] = $this->upload->data('full_path');

		                switch($imgdata['Orientation']) {
		                    case 3:
		                        $config['rotation_angle']='180';
		                        break;
		                    case 6:
		                        $config['rotation_angle']='270';
		                        break;
		                    case 8:
		                        $config['rotation_angle']='90';
		                        break;
		                    default:
		                        $config['rotation_angle']='0';
		                        break;
		                }

		                $this->image_lib->initialize($config); 
		                if($this->image_lib->rotate()){
							print_r("exito");
							

							if ($this->vendors_model->save($data)) {
								redirect(base_url()."sisvent/business/vendors");
							}
							else{
								$this->session->set_flashdata("error","No se pudo guardar la información");
								$this->add();
								//redirect(base_url()."sisvent/business/vendors/add");
							}
					    }else
					    {
					    	$error .= " rotate: ".$this->image_lib->display_errors();//array('error' => $this->image_lib->display_errors());
							$this->session->set_flashdata("error",$error);
							$this->add();
							//redirect(base_url().'sisvent/business/vendors/add');
					    }*/
					}
					else {
						$error = $this->upload->display_errors();//array('error' => $this->upload->display_errors());
						$this->session->set_flashdata("error",$error);
						$this->add();
						//redirect(base_url().'sisvent/business/vendors/add');
					}		
				
				
			}else
			{
				if ($this->vendors_model->save($data)) {
					redirect(base_url()."sisvent/business/vendors");
				}
				else{
					$this->session->set_flashdata("error","No se pudo guardar la información");
					//redirect(base_url()."sisvent/business/vendors/add");
					$this->add();
				}
			}
			
		}
		else{
			$this->add();
		}
	}

	public function edit($user_id){
		$data =array( 
			'user' => $this->vendors_model->getVendor($user_id),
			'stores' => $this->stores_model->getStores()
		);
		//print_r($data);
		$this->load->view("sisvent/business/vendors/edit",$data);
	}

	public function update(){

		$user_id = $this->input->post("user_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$phone = $this->input->post("phone");
		$address = $this->input->post("address");
		$password = $this->input->post("password");
		$passconf = $this->input->post("passconf");
		$store = $this->input->post("store");

		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("email","Email","valid_email");
		$this->form_validation->set_rules("phone","Teléfono","numeric");
		
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
					'phone' => $phone,
					'address' => $address,
					'store' => $store,
					'password' => password_hash($password, PASSWORD_BCRYPT)
				);
			}
			else
			{
				$data  = array(
					'name' => $name,
					'email' => $email,
					'phone' => $phone,
					'store' => $store,
					'address' => $address
				);
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

						if ($this->vendors_model->update($user_id,$data)) {
							redirect(base_url()."sisvent/business/vendors");
						}
						else{
							$this->session->set_flashdata("error","No se pudo actualizar la información");
							$this->edit($user_id);
							//redirect(base_url()."sisvent/business/vendors/edit/".$user_id);
						}
					}
					else {
						$error = $this->upload->display_errors();//array('error' => $this->upload->display_errors());
						$this->session->set_flashdata("error",$error);
						$this->edit($user_id);
						//redirect(base_url().'sisvent/business/vendors/add');
					}		
				
				
			}else
			{
				if ($this->vendors_model->update($user_id,$data)) {
					redirect(base_url()."sisvent/business/vendors");
				}
				else{
					$this->session->set_flashdata("error","No se pudo actualizar la información");
					$this->edit($user_id);
					//redirect(base_url()."sisvent/business/vendors/edit/".$user_id);
				}
			}
		}
		else{
			$this->edit($user_id);
		}
	}

	public function delete($user_id){
		$this->vendors_model->remove($user_id);
		//redirect(base_url()."sisvent/business/vendors");
		//echo (base_url()."sisvent/business/vendors");
		echo base_url()."sisvent/business/vendors";
	}
	
}