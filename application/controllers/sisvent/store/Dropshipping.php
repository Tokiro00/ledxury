<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dropshipping extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
        //$this->load->library('form_validation');
        $this->load->model("products_model");
        $this->load->model("inventory_model");
        $this->load->model("vendors_model");
        $this->load->model("stores_model");
        $this->load->model("clients_model");
        $this->load->model("dropshipping_model");
        $this->load->model("budgets_model");
    }

	public function index()
	{
		$this->backend_lib->control();
		$page = $this->input->get('p');
		$limit = 50;
		if(!$page)
			$page = 1;

		$total = $this->dropshipping_model->getTotal();
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
			'promopacks' => $this->dropshipping_model->getPromopacks($page, $limit)
		);

		$this->load->view("sisvent/store/dropshipping/index",$data);
		
	}

	public function add(){
		
		$data  = array(
			'stores' => $this->stores_model->getStores(), 
		);
		$this->load->view("sisvent/store/dropshipping/add",$data);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
			date_default_timezone_set("America/Bogota");

		$page = $this->input->get('p');
		
		$limit = 50;
		if(!$page)
			$page = 1;
		

		$ptotal = $this->dropshipping_model->getTotal();
		$last       = ceil( $ptotal / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;


		$name = $this->input->post("name");
		//$store = $this->input->post("store");
		$comments = $this->input->post("comments");
		$total = $this->input->post("total");
		$promoquantity = $this->input->post("promoquantity");
        

		$products = $this->input->post("refs");
		$quantity = $this->input->post("quantity");
		$prices = $this->input->post("prices");
		$display_order = $this->input->post("display_order");
				
		if(isset($_FILES['imageAvatar']) && is_uploaded_file($_FILES['imageAvatar']['tmp_name'])) {
				
			$path = $_FILES['imageAvatar']['name'];
		    $ext = pathinfo($path, PATHINFO_EXTENSION);
		    $fname = pathinfo($path, PATHINFO_FILENAME);
		    $file = $_FILES['imageAvatar']['tmp_name'];

			$config['allowed_types']='jpg|jpeg|png';
			$config['upload_path']='./public/dist/images/promos';
			$config['file_name']= $fname;
			$config['overwrite']=true;

			$this->load->library('upload',$config);
			
			$image_data = $this->upload->data();

			//list($width, $height) = getimagesize($file);

			if (!is_dir('./public/dist/images/promos/')) {
				//print_r("<br> Creando directorio ".'./public/dist/images/products/'.'pf'.substr( $this->session->productdata('product_data')['product_name'], 0,2).$this->session->productdata('product_data')['product_uname']);
            	mkdir('./public/dist/images/promos/', 0777, true);
        	}

        	if($this->upload->do_upload('imageAvatar')){
	    	
		    	$picture_url = preg_replace('/\s+/', '_','promos/'.($image_data['file_name'].".".$ext));
				//$this->session->set_productdata('image', $data['picture_url']);
			    $error = "";
			
				$imgdata=exif_read_data($this->upload->upload_path.$this->upload->file_name, 'IFD0');
				
				$this->load->library('image_lib');

					
				// resizing image
				$config['image_library'] = 'gd2';
			    $config['source_image'] = $this->upload->data('full_path');//'./assets/avatarPictures/productPictures/'.$image_data['file_name'].".".$ext;//$image_data['full_path'].;
			    $config['maintain_ratio'] = TRUE;
			    $config['quality'] = "40%";
			    //$config['width']     = 300 * $height / $width;
			    //$config['height']   = 300;
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

				if(!empty($error)){
					$this->session->set_flashdata("error",$error);
				}
				if($products && count($products) > 0)
				{

					$data  = array(
						'name' => $name,
						'total' => $total,
						'quantity' => $promoquantity,
						'date' => date('Y-m-d H:i:s'),
						'comments' => $comments,
						'picture_url' => $picture_url, 
					);

					//print_r($data);

					if ($this->dropshipping_model->save($data)) {
						$idPromopack = $this->dropshipping_model->lastID();
						$this->_save_detail($products, $prices, $quantity, $display_order,$idPromopack);

						redirect(base_url()."sisvent/store/dropshipping".createFullParamsLinks($page));
					}
					else{
						$data  = array(
							'stores' => $this->stores_model->getStores(), 
						);
						$this->session->set_flashdata("error","No se pudo guardar la información");
						$this->load->view("sisvent/store/dropshipping/add",$data);
					}
					
				}
				else{

					$data  = array(
						'stores' => $this->stores_model->getStores(), 
					);
					$this->session->set_flashdata("error","Debe ingresar al menos un producto");
					$this->load->view("sisvent/store/dropshipping/add",$data);
					
				}
			}
			else {
				$promopack = $this->dropshipping_model->getPromopack($idPromopack);

				$products = $this->dropshipping_model->getDetails($idPromopack,-1);
				

				$data  = array(
					'promopack' => $promopack, 
					'details' => $products, 
					'stores' => $this->stores_model->getStores(), 
				);
				$this->session->set_flashdata("error",$this->upload->display_errors());
				$this->load->view("sisvent/store/dropshipping/edit",$data);
				//redirect(base_url().'sisvent/business/products/add');
			}		
			
		}else
		{
			if($products && count($products) > 0)
			{

				$data  = array(
					'name' => $name,
					//'storeId' => $store,
					'total' => $total,
					'quantity' => $promoquantity,
					//'has_discount' => $has_discount == "on",
					//'disc_mult' => $disc_mult,
					'date' => date('Y-m-d H:i:s'),
					'comments' => $comments,
				);

				//print_r($data);

				if ($this->dropshipping_model->save($data)) {
					$idPromopack = $this->dropshipping_model->lastID();
					$this->_save_detail($products, $prices, $quantity, $display_order,$idPromopack);

					redirect(base_url()."sisvent/store/dropshipping".createFullParamsLinks($page));
				}
				else{
					$data  = array(
						'stores' => $this->stores_model->getStores(), 
					);
					$this->session->set_flashdata("error","No se pudo guardar la información");
					$this->load->view("sisvent/store/dropshipping/add",$data);
				}
				
			}
			else{
				$data  = array(
					'stores' => $this->stores_model->getStores(), 
				);
				$this->session->set_flashdata("error","Debe ingresar al menos un producto");
				$this->load->view("sisvent/store/dropshipping/add",$data);
				//$this->add();
			}
		}
	}

	function _save_detail($products, $prices, $quantity, $display_order,$idPromopack){
		
		//echo "<script>console.log( 'per: ".empty($per_packages)." ' );</script>";
		for ($i=0; $i < count($products); $i++) { 
			//echo "<script>console.log( 'Debug Objects: ".$i." = ".$products[$i]." + " .implode(" -- ", $per_packages)." + " . (array_search($products[$i], $per_packages) === FALSE)." + " .array_search($products[$i], $per_packages). "' );</script>";

			$data  = array(
				'promopackId' =>$idPromopack,
				'quantity' =>$quantity[$i],				
				'promo_price' =>$prices[$i],				
				'display_order' =>$display_order[$i],				
				'productId' =>$products[$i]				
			);
			//echo "<pre>";
			//print_r($data);
			//echo "</pre>";
			$this->dropshipping_model->save_detail($data);
			//$this->updateProduct($products[$i],$quantities[$i]);
		}
	}


	public function edit($idPromopack){
		
		$promopack = $this->dropshipping_model->getPromopack($idPromopack);

		$products = $this->dropshipping_model->getDetails($idPromopack,-1);
		

		$data  = array(
			'promopack' => $promopack, 
			'details' => $products, 
			'stores' => $this->stores_model->getStores(), 
		);
		$this->load->view("sisvent/store/dropshipping/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
			date_default_timezone_set("America/Bogota");

		$idPromopack = $this->input->post("idPromopack");
		$name = $this->input->post("name");
		//$store = $this->input->post("store");
		$comments = $this->input->post("comments");
		$total = $this->input->post("total");
		$promoquantity = $this->input->post("promoquantity");
        
		$products = $this->input->post("refs");
		$quantity = $this->input->post("quantity");
		$prices = $this->input->post("prices");
		$display_order = $this->input->post("display_order");
				
		if(isset($_FILES['imageAvatar']) && is_uploaded_file($_FILES['imageAvatar']['tmp_name'])) {
				
			$path = $_FILES['imageAvatar']['name'];
		    $ext = pathinfo($path, PATHINFO_EXTENSION);
		    $fname = pathinfo($path, PATHINFO_FILENAME);
		    $file = $_FILES['imageAvatar']['tmp_name'];

			$config['allowed_types']='jpg|jpeg|png';
			$config['upload_path']='./public/dist/images/promos';
			$config['file_name']= $fname;
			$config['overwrite']=true;

			$this->load->library('upload',$config);
			
			$image_data = $this->upload->data();

			//list($width, $height) = getimagesize($file);

			if (!is_dir('./public/dist/images/promos/')) {
				//print_r("<br> Creando directorio ".'./public/dist/images/products/'.'pf'.substr( $this->session->productdata('product_data')['product_name'], 0,2).$this->session->productdata('product_data')['product_uname']);
            	mkdir('./public/dist/images/promos/', 0777, true);
        	}

        	if($this->upload->do_upload('imageAvatar')){
	    	
		    	$picture_url = preg_replace('/\s+/', '_','promos/'.($image_data['file_name'].".".$ext));
				//$this->session->set_productdata('image', $data['picture_url']);
			    $error = "";
			
				$imgdata=exif_read_data($this->upload->upload_path.$this->upload->file_name, 'IFD0');
				
				$this->load->library('image_lib');

					
				// resizing image
				$config['image_library'] = 'gd2';
			    $config['source_image'] = $this->upload->data('full_path');//'./assets/avatarPictures/productPictures/'.$image_data['file_name'].".".$ext;//$image_data['full_path'].;
			    $config['maintain_ratio'] = TRUE;
			    $config['quality'] = "40%";
			    //$config['width']     = 300 * $height / $width;
			    //$config['height']   = 300;
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

				if(!empty($error)){
					$this->session->set_flashdata("error",$error);
				}
				if($products && count($products) > 0)
				{

					$data  = array(
						'name' => $name,
						'total' => $total,
						'quantity' => $promoquantity,
						'comments' => $comments,
						'picture_url' => $picture_url, 
					);

					//print_r($data);

					if ($this->dropshipping_model->update($idPromopack,$data)) {
						$this->dropshipping_model->removeDetails($idPromopack);
						//if ($this->dropshipping_model->save($data)) {
						//$idPromopack = $this->dropshipping_model->lastID();
						$this->_save_detail($products, $prices, $quantity, $display_order,$idPromopack);

						redirect(base_url()."sisvent/store/dropshipping");
					}
					else{
						$promopack = $this->dropshipping_model->getPromopack($idPromopack);

						$products = $this->dropshipping_model->getDetails($idPromopack,-1);
						

						$data  = array(
							'promopack' => $promopack, 
							'details' => $products, 
							'stores' => $this->stores_model->getStores(), 
						);
						$this->session->set_flashdata("error","No se pudo guardar la información");
						$this->load->view("sisvent/store/dropshipping/edit",$data);

					}
					
				}
				else{

					$promopack = $this->dropshipping_model->getPromopack($idPromopack);

					$products = $this->dropshipping_model->getDetails($idPromopack,-1);
					

					$data  = array(
						'promopack' => $promopack, 
						'details' => $products, 
						'stores' => $this->stores_model->getStores(), 
					);
					$this->session->set_flashdata("error","Debe ingresar al menos un producto");
					$this->load->view("sisvent/store/dropshipping/edit",$data);
					
				}
			}
			else {
				$promopack = $this->dropshipping_model->getPromopack($idPromopack);

				$products = $this->dropshipping_model->getDetails($idPromopack,-1);
				

				$data  = array(
					'promopack' => $promopack, 
					'details' => $products, 
					'stores' => $this->stores_model->getStores(), 
				);
				$this->session->set_flashdata("error",$this->upload->display_errors());
				$this->load->view("sisvent/store/dropshipping/edit",$data);
				//redirect(base_url().'sisvent/business/products/add');
			}		
			
		}else
		{
			if($products && count($products) > 0)
			{

				$data  = array(
					'name' => $name,
					'total' => $total,
					'quantity' => $promoquantity,
					'comments' => $comments,
				);

				//print_r($data);

				if ($this->dropshipping_model->update($idPromopack,$data)) {
					$this->dropshipping_model->removeDetails($idPromopack);
					//if ($this->dropshipping_model->save($data)) {
					//$idPromopack = $this->dropshipping_model->lastID();
					$this->_save_detail($products, $prices, $quantity, $display_order,$idPromopack);

					redirect(base_url()."sisvent/store/dropshipping");
				}
				else{
					$promopack = $this->dropshipping_model->getPromopack($idPromopack);

					$products = $this->dropshipping_model->getDetails($idPromopack,-1);
					

					$data  = array(
						'promopack' => $promopack, 
						'details' => $products, 
						'stores' => $this->stores_model->getStores(), 
					);
					$this->session->set_flashdata("error","No se pudo guardar la información");
					$this->load->view("sisvent/store/dropshipping/edit",$data);

				}
				
			}
			else{

				$promopack = $this->dropshipping_model->getPromopack($idPromopack);

				$products = $this->dropshipping_model->getDetails($idPromopack,-1);
				

				$data  = array(
					'promopack' => $promopack, 
					'details' => $products, 
					'stores' => $this->stores_model->getStores(), 
				);
				$this->session->set_flashdata("error","Debe ingresar al menos un producto");
				$this->load->view("sisvent/store/dropshipping/edit",$data);
				
			}
		}
		
	}

	public function viewcat($idPromopack = -1)
	{
		
		
		$promopack = $this->dropshipping_model->getPromopack($idPromopack);

		if($idPromopack < 0 || empty($promopack)) {
			redirect(base_url()."sisvent/store/dropshipping");
		}else{


			$products = $this->dropshipping_model->getDetails($idPromopack,-1);
			
			$data  = array(
				'promopack' => $promopack, 
				'products' => $products,
			);
			$this->load->view("sisvent/store/dropshipping/viewcatalogue",$data);
		}
	}

	public function promos()
	{
		$page = $this->input->get('p');
		$limit = 50;
		if(!$page)
			$page = 1;

		$total = $this->dropshipping_model->getTotal();
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
			'promopacks' => $this->dropshipping_model->getPromopacks($page, $limit)
		);

		$this->load->view("sisvent/store/dropshipping/promos",$data);
	}

	public function delete($idPromopack){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$this->dropshipping_model->remove($idPromopack);
	    $this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." ha eliminado catálogo ".$idPromopack);
	//redirect(base_url()."sisvent/business/clients");
		echo base_url()."sisvent/store/dropshipping";
	}

	public function buy($idPromopack = -1)
	{
		
		
		$promopack = $this->dropshipping_model->getPromopack($idPromopack);

		if($idPromopack < 0 || empty($promopack)) {
			$this->promos();
		}else{


			$products = $this->dropshipping_model->getDetails($idPromopack,-1);
			
			$data  = array(
				'promopack' => $promopack, 
				'products' => $products,
			);
			$this->load->view("sisvent/store/dropshipping/buy",$data);
		}
	}

	public function search($store,$term)
	{
		$term = str_replace("%20", " ", $term);
	
		$page = $this->input->get('p');
		
		$limit = 50;
		if(!$page)
			$page = 1;
		
		$total = $this->inventory_model->getCurrentInventorySearchCount($store,$term);
		$last       = ceil( $total / $limit );

		$pag =  $page;
		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;
		$products = $this->inventory_model->getCurrentInventoryByWord($term, $store,$page,$limit);
		foreach ($products as $key => $product) {
			$product->datasheetvalues = $this->products_model->getProductsLabelsValues($product->idProduct,$product->datasheet);
		}
		$data  = array(
			'store' => $this->stores_model->getStore($store), 
			'total' => $total,
			'page' => $pag,
			'limit' => $limit,
			'products' => $products, 
			'ps' => $term,
		);
		$this->load->view("sisvent/store/dropshipping/viewdatasheet",$data);
		
	}

	public function getProducts(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$valor = $this->input->post("valor");

		$products = $this->inventory_model->getProducts($valor);
		
		echo json_encode($products);
	}

	public function getProduct(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$producto = $this->products_model->getProduct($this->input->post("ref"));
		
		echo json_encode($producto);
	}

	public function verifyClient(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$c = $this->clients_model->getClientByIdNum($this->input->post("ref"));
		
		if(empty($c)){
			$client  = array(
				'isnew' => true,
				'view' => $this->load->view("sisvent/store/dropshipping/newclient",null, TRUE),
			);
		}else{
			$data  = array(
				'client' => $c,
			);
			
			$client  = array(
				'isnew' => false,
				'view' => $this->load->view("sisvent/store/dropshipping/oldclient",$data, TRUE),
			);

		}
		echo json_encode($client);
	}

	public function completepurchase(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$isnew = $this->input->post("isnew");
		$idClient = $this->input->post("id");
		$client_id = $this->input->post("client_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$phone = $this->input->post("phone");
		$cellphone = $this->input->post("cellphone");
		$address = $this->input->post("address");
		$city = $this->input->post("city");
		$zone = $this->input->post("zone");
		$type = $this->input->post("type");
		$state = $this->input->post("state");
		$comments = $this->input->post("comments");
		$quantity = $this->input->post("quantity"); 
		$deliveryType = $this->input->post("delivery-type");

		$idPromopack = $this->input->post("promopack");

		if($isnew){
			$data  = array(
				'idNum' => $client_id, 
				'name' => $name,
				'commercial_name' => $name,
				'f_id' => $this->clients_model->getHighestClientFid()->next_fid+1,
				'email' => $email,
				'phone' => $phone,
				'cellphone' => $cellphone,
				'address' => $address,
				'retail' => 1,
				'blacklisted' => 0,
				'vendor' => "redes",
				'city' => $city,
				'zone' => $zone,
				'state' => $state,
				'is_new' => 1,
			);
			$this->clients_model->save($data);
			$idClient = $this->dropshipping_model->lastID();
		}else{
			$data  = array(
				'email' => $email,
				'phone' => $phone,
				'cellphone' => $cellphone,
				'address' => $address,
				'city' => $city,
				'zone' => $zone,
				'state' => $state,
			);
			$this->clients_model->update($idClient,$data);
		}

		$promopack = $this->dropshipping_model->getPromopack($idPromopack);

		$products = $this->dropshipping_model->getDetails($idPromopack,-1);
		$delivery = $this->dropshipping_model->getDelivery($deliveryType);
		
		$userId = $this->session->userdata('user_data')['uname'];
		//$user = $this->users_model->getAnyUser($userId);
		if(!isset($userId))
		{
			$vendor = '20326840';
			switch ($deliveryType) {
				case 1:
				case 6:
					$vendor = '1551512-0';
					break;
				case 3:
				case 8:
					$vendor = '1111793344';
					break;
				case 4:
					$vendor = '123456789';
					break;
				
				default:
					$vendor = '20326840';
					break;
			}
		}else{
			$vendor = $userId;
		}

		$data  = array(
			'clientId' => $idClient,
			'vendorId' => $vendor,
			'storeId' => "1",
			'total' => ($promopack->total * $quantity)+($deliveryType >= 5 ? 0 : 10000),
			'date' => date('Y-m-d H:i:s'),
			'state' => 0,
			'e_commerce' => 1,
			'list_price' => 0,
			'hasIva' => 0,
			'iva' => 8,
			'comments' => $promopack->name.". ".$delivery->name.', '.$name.', cc '.$client_id.', tel '.$phone.', cel '.$cellphone.', '.$address.', '.$zone.', '.$city.', '.$state.', '.$comments,
		);

		//print_r($data);

		$this->budgets_model->save($data);
		$idBudget = $this->budgets_model->lastID();
		foreach($products as $key => $product) { 
			
			$data  = array(
				'budgetId' => $idBudget,
				'productId' => $product->idProduct,
				'quantity' => $product->quantity * $quantity,
				'unit' => $product->promo_price,
				'base' => $product->price_base,
				'total' => $product->promo_price * $product->quantity * $quantity
			);
			//echo "<pre>";
			//print_r($data);
			//echo "</pre>";
			$res = $this->budgets_model->save_detail($data);
			
			$data  = array(
				'quantity' => $promopack->quantity-$quantity,
			);

			//print_r($data);

			$this->dropshipping_model->update($idPromopack,$data);
		}

		if($deliveryType < 5){
			$data  = array(
				'budgetId' => $idBudget,
				'productId' => 'FLETE',
				'quantity' => 1,
				'unit' => 10000,
				'base' => 9999,
				'total' => 10000
			);
			$this->budgets_model->save_detail($data);
		}

		$data  = array(
			'clientId' => $idClient,
			'vendorId' => $vendor,
			'deliverytypeId' => $deliveryType,
			'total' => $promopack->total * $quantity,
			'date' => date('Y-m-d H:i:s'),
			'state' => 0,
			'quantity' => $quantity,
			'idBudget' => $idBudget,
			'idPromopack' => $idPromopack,
			'comments' => $comments,
		);
		$this->dropshipping_model->savePromopurchase($data);

		sendEmail("cdga777@gmail.com,alex.alzate@gmail.com,lasolucionfinal88@gmail.com","Compra realizada ".date('Y-m-d H:i:s').": ".$promopack->name,"El cliente ".$name."(".$idClient.") con cedula ".$client_id." acaba de realizar la compra de ".$quantity." ".$promopack->name.'<br><b>presupuesto:</b> '.$idBudget.'<br><b>Datos del cliente:</b>'.' tel '.$phone.', cel '.$cellphone.', '.$address.', '.$zone.', '.$city.', '.$state.', '.$comments);
			
		$data  = array(
			'clientName' => $name, 
			'promopack' => $promopack,
		);
		$this->load->view("sisvent/store/dropshipping/purchasecompleted",$data);
	}

}