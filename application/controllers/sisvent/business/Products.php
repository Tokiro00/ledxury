<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->load->helper('file');
        $this->load->model("products_model");
        $this->load->model("vendors_model");
    }

	public function index()
	{
		$data  = array(
			'products' => $this->products_model->getProducts(), 
		);
		$this->load->view("sisvent/business/products/list",$data);
		
	}

	public function add(){

		$this->backend_lib->control([1]);
		$data =array( 
			"families" => $this->products_model->getFamilies(),
			"vendors" => $this->vendors_model->getVendors()
		);
		$this->load->view("sisvent/business/products/add", $data);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$product_id = $this->input->post("product_id");
		$description = $this->input->post("description");
		$price = $this->input->post("price");
		$price_base = $this->input->post("price_base");
		$price_scale = $this->input->post("price_scale");
		$price_dist = $this->input->post("price_dist");
		$cost = $this->input->post("cost");
		$cost_cop = $this->input->post("cost_cop");
		$cost_rmb = $this->input->post("cost_rmb");
		$family = $this->input->post("family");
		$provider = $this->input->post("provider");
		$min = $this->input->post("min");

		$this->form_validation->set_rules("product_id","Código","required|is_unique[products.idProduct]");
		$this->form_validation->set_rules("description","Descripción","required");
		

		if ($this->form_validation->run()) {
			$data  = array(
				'idProduct' => $product_id, 
				'description' => $description,
				'price' => $price,
				'price_base' => $price_base,
				'price_scale' => $price_scale,
				'price_dist' => $price_dist,
				'cost' => $cost,
				'cost_cop' => $cost_cop,
				'cost_rmb' => $cost_rmb,
				'family' => $family,
				'provider' => $provider,
				'min' => $min
			);

			if(isset($_FILES['imageAvatar']) && is_uploaded_file($_FILES['imageAvatar']['tmp_name'])) {
				
					$path = $_FILES['imageAvatar']['name'];
				    $ext = pathinfo($path, PATHINFO_EXTENSION);
				    $file = $_FILES['imageAvatar']['tmp_name'];

					$config['allowed_types']='jpg|png';
					$config['upload_path']='./public/dist/images/products';
					$config['file_name']= $product_id;
					$config['overwrite']=true;

					$this->load->library('upload',$config);
					
					$image_data = $this->upload->data();

					list($width, $height) = getimagesize($file);

					if (!is_dir('./public/dist/images/products/')) {
						//print_r("<br> Creando directorio ".'./public/dist/images/products/'.'pf'.substr( $this->session->productdata('product_data')['product_name'], 0,2).$this->session->productdata('product_data')['product_uname']);
		            	mkdir('./public/dist/images/products/', 0777, true);
		        	}

		        	if($this->upload->do_upload('imageAvatar')){
			    	
				    	$data['picture_url']='products/'.($image_data['file_name'].".".$ext);
						//$this->session->set_productdata('image', $data['picture_url']);
					    $error = "";
					
						$imgdata=exif_read_data($this->upload->upload_path.$this->upload->file_name, 'IFD0');

						//Set config for img library
						$config['image_library'] = 'gd2';
						$config['source_image'] = $this->upload->data('full_path');//'./assets/avatarPictures/productPictures/'.$image_data['file_name'].".".$ext;
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
					    $config['source_image'] = $this->upload->data('full_path');//'./assets/avatarPictures/productPictures/'.$image_data['file_name'].".".$ext;//$image_data['full_path'].;
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

						if ($this->products_model->save($data)) {
							redirect(base_url()."sisvent/business/products");
						}
						else{
							$this->session->set_flashdata("error","No se pudo guardar la información");
							$this->add();
							//redirect(base_url()."sisvent/business/products/add");
						}
					}
					else {
						$error = $this->upload->display_errors();//array('error' => $this->upload->display_errors());
						$this->session->set_flashdata("error",$error);
						$this->add();
						//redirect(base_url().'sisvent/business/products/add');
					}		
				
			}else
			{
				if ($this->products_model->save($data)) {
					redirect(base_url()."sisvent/business/products");
				}
				else{
					$this->session->set_flashdata("error","No se pudo guardar la información");
					$this->add();
					//redirect(base_url()."sisvent/business/products/add");
				}
			}
		}
		else{
			$this->add();
		}
	}

	public function edit($product_id){
		$this->backend_lib->control([1]);
		$data =array( 
			'product' => $this->products_model->getProduct($product_id), 
			"families" => $this->products_model->getFamilies(),
			"vendors" => $this->vendors_model->getVendors()
		);
		//print_r($data);
		$this->load->view("sisvent/business/products/edit",$data);
	}

	public function update(){

		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$product_id = $this->input->post("product_id");
		$description = $this->input->post("description");
		$price = $this->input->post("price");
		$price_base = $this->input->post("price_base");
		$price_scale = $this->input->post("price_scale");
		$price_dist = $this->input->post("price_dist");
		$cost = $this->input->post("cost");
		$cost_cop = $this->input->post("cost_cop");
		$cost_rmb = $this->input->post("cost_rmb");
		$family = $this->input->post("family");
		$provider = $this->input->post("provider");
		$min = $this->input->post("min");

		//$this->form_validation->set_rules("product_id","Código","required|is_unique[products.idProduct]");
		$this->form_validation->set_rules("description","Descripción","required");

		if ($this->form_validation->run()) {
			
			$data  = array(
				'description' => $description,
				'price' => $price,
				'price_base' => $price_base,
				'price_scale' => $price_scale,
				'price_dist' => $price_dist,
				'cost' => $cost,
				'cost_cop' => $cost_cop,
				'cost_rmb' => $cost_rmb,
				'family' => $family,
				'provider' => $provider,
				'min' => $min
			);
			

			if(isset($_FILES['imageAvatar']) && is_uploaded_file($_FILES['imageAvatar']['tmp_name'])) {
				
					$path = $_FILES['imageAvatar']['name'];
				    $ext = pathinfo($path, PATHINFO_EXTENSION);
				    $file = $_FILES['imageAvatar']['tmp_name'];

					$config['allowed_types']='jpg|png';
					$config['upload_path']='./public/dist/images/products';
					$config['file_name']= $product_id;
					$config['overwrite']=true;

					$this->load->library('upload',$config);
					
					$image_data = $this->upload->data();

					list($width, $height) = getimagesize($file);

					if (!is_dir('./public/dist/images/products/')) {
						//print_r("<br> Creando directorio ".'./public/dist/images/products/'.'pf'.substr( $this->session->productdata('product_data')['product_name'], 0,2).$this->session->productdata('product_data')['product_uname']);
		            	mkdir('./public/dist/images/products/', 0777, true);
		        	}

		        	if($this->upload->do_upload('imageAvatar')){
			    	
				    	$data['picture_url']='products/'.($image_data['file_name'].".".$ext);
						//$this->session->set_productdata('image', $data['picture_url']);
					    $error = "";
					
						$imgdata=exif_read_data($this->upload->upload_path.$this->upload->file_name, 'IFD0');

						//Set config for img library
						$config['image_library'] = 'gd2';
						$config['source_image'] = $this->upload->data('full_path');//'./assets/avatarPictures/productPictures/'.$image_data['file_name'].".".$ext;
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
					    $config['source_image'] = $this->upload->data('full_path');//'./assets/avatarPictures/productPictures/'.$image_data['file_name'].".".$ext;//$image_data['full_path'].;
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

						if ($this->products_model->update($product_id,$data)) {
							redirect(base_url()."sisvent/business/products");
						}
						else{
							$this->session->set_flashdata("error","No se pudo actualizar la información");
							$this->edit($product_id);
							//redirect(base_url()."sisvent/business/products/edit/".$product_id);
						}
					}
					else {
						$error = $this->upload->display_errors();//array('error' => $this->upload->display_errors());
						$this->session->set_flashdata("error",$error);
						$this->edit($product_id);
						//redirect(base_url().'sisvent/business/products/add');
					}		
				
			}else
			{
				if ($this->products_model->update($product_id,$data)) {
					redirect(base_url()."sisvent/business/products");
				}
				else{
					$this->session->set_flashdata("error","No se pudo actualizar la información");
					//redirect(base_url()."sisvent/business/products/edit/".$product_id);
					$this->edit($product_id);
				}
			}
		}
		else{
			$this->edit($product_id);
		}
	}

	public function delete($product_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$this->products_model->remove($product_id);
		//redirect(base_url()."sisvent/business/products");
		echo base_url()."sisvent/business/products";
	}

	public function load(){

		$this->load->view("sisvent/business/products/loadproducts");
	}
	
	public function upload()
    {
    	$this->outh_model->CSRFVerify();
	
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

    	set_time_limit(0);
    	//print_r($_FILES['userfile']);
    	// If import request is submitted
        if($this->input->post('importSubmit')){
            // Form field validation rules
            $this->form_validation->set_rules('userfile', 'CSV file', 'callback__file_check');
            // Validate submitted form data
            if($this->form_validation->run() == true){
            	$fp = fopen($_FILES['userfile']['tmp_name'],'r') or die("can't open file");
				$lines = $this->_readInputFromFile($fp);
				$size = count($lines);
				//echo $size."<br>";
				$uc = 0;
				$nosaved = "";
				for ($i = 0; $i < $size; $i++)
				{
					//echo "-------------------------------------<br>";
					//echo "i = ".$i."<br>";
				    
				    $columns = str_getcsv($lines[$i],",");
					$product_id = test_input($columns[0]);
					$description = test_input($columns[1]);
					$family = test_input($columns[2]);
					$price_base = test_input($columns[3]);
					$price_dist = test_input($columns[4]);
					$price_scale = test_input($columns[5]);
					$price = test_input($columns[6]);
					$cost_cop = test_input($columns[7]);
					$cost_rmb = test_input($columns[8]);
					//$query = "INSERT INTO `users`(`user_id`, `name`, `email`, `phone`) VALUES ('".$id."','".($name)."','".$email."','".($cellphone)."')";
					if(!empty($product_id))
					{
						$fam = $this->products_model->getFamilyByName($family);

						if(empty($fam))
						{	
							$datafam  = array(
								'name' => $family
							);
							$this->products_model->saveFamily($datafam);
							$fam_id = $this->db->insert_id();
						}
						else{
							$fam_id = $fam->idFamily;
						}
						
						$data  = array(
							'idProduct' => $product_id, 
							'description' => $description,
							'price' => $price,
							'price_base' => $price_base,
							'price_scale' => $price_scale,
							'price_dist' => $price_dist,
							'cost' => 0,
							'cost_cop' => $cost_cop,
							'cost_rmb' => $cost_rmb,
							'family' => $fam_id,
							'provider' => 1,
							'min' => 100
						);

						if ($this->products_model->save($data)) {
							$uc++;
						}else
						{
							$nosaved .= $id." No guardó<br>";
						}
					}else
					{
						$nosaved .= $id." Sin código<br>";
					}
				}
				//print_r("Usuarios ")
				$error = array('success_msg' => 'Usuarios registrados: '.$uc.'/'.$size,'u_permissions' => $this->permissions,
								'info_msg' => $nosaved);
				$this->load->view('sisvent/business/products/loadproducts', $error);
            }else{
                $error = array('error_msg' => 'Invalid file, please select only CSV file.:)','u_permissions' => $this->permissions);
				$this->load->view('sisvent/business/products/loadproducts', $error);
            }
        }else{
            $error = array('error_msg' => 'Error on file upload, please try again.:)','u_permissions' => $this->permissions);
			$this->load->view('sisvent/business/products/loadproducts', $error);
        }
            
    }

    /*
     * Callback function to check file value and type during validation
     */
    public function _file_check($str){
        
        $allowed_mime_types = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');
        if(isset($_FILES['userfile']['name']) && $_FILES['userfile']['name'] != ""){
            $mime = get_mime_by_extension($_FILES['userfile']['name']);
            $fileAr = explode('.', $_FILES['userfile']['name']);
            $ext = end($fileAr);
            if(($ext == 'csv') && in_array($mime, $allowed_mime_types)){
                return true;
            }else{
                //$this->form_validation->set_message('file_check', 'Please select only CSV file to upload.');
                //print_r('Please select only CSV file to upload.');
                return false;
            }
        }else{
            //$this->form_validation->set_message('file_check', 'Please select a CSV file to upload.');
            //print_r('Please select a CSV file to upload.');
            return false;
        }
    }

    function _readInputFromFile($fh)
	{
	   //$fh = fopen($file, 'r');
		if(isset($fh))
		{
		   while (!feof($fh))
		   {
		      $ln = fgets($fh);
		      $parts[] = $ln;
		   }

		   fclose($fh);

		   return $parts;
		}else
			return array();
	}

	public function viewfamilies(){
		$data =array( 
			'families' => $this->products_model->getFamilies()
		);
		$this->load->view("sisvent/business/product_families/list",$data);
	}

	public function addfamily(){

		$this->load->view("sisvent/business/product_families/add");
	}

	public function storefamily(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$name = $this->input->post("name");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			$data  = array(
				'name' => $name
			);

			if ($this->products_model->saveFamily($data)) {
				redirect(base_url()."sisvent/business/products/viewfamilies");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				redirect(base_url()."sisvent/business/products/addfamily");
			}
		}
		else{
			$this->addfamily();
		}
	}

	public function editfamily($store_id){
		$data =array( 
			'family' => $this->products_model->getFamily($store_id)
		);
		//print_r($data);
		$this->load->view("sisvent/business/product_families/edit",$data);
	}

	public function updatefamily(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$family_id = $this->input->post("family_id");
		$name = $this->input->post("name");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			
			$data  = array(
				'name' => $name
			);

			if ($this->products_model->updateFamily($family_id,$data)) {
				redirect(base_url()."sisvent/business/products/viewfamilies");
			}
			else{
				$this->session->set_flashdata("error","No se pudo actualizar la información");
				redirect(base_url()."sisvent/business/products/editfamily/".$family_id);
			}
		}
		else{
			$this->editfamily($family_id);
		}
	}

	public function deletefamily($family_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$this->products_model->removeFamily($family_id);
		//redirect(base_url()."sisvent/business/product_families");
		echo base_url()."sisvent/business/products/viewfamilies";
	}
	
}