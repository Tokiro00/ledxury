<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Products extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control();
		$this->load->helper('file');
        $this->load->model("products_model");
        $this->load->model("vendors_model");
        $this->load->model("providers_model");
    }

	public function index()
	{
		$page = $this->input->get('p');
		
		$limit = 50;
		if(!$page)
			$page = 1;
		
		$total = $this->products_model->getTotal();
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;


		$data  = array(
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
			'products' => $this->products_model->getProductsPag($page, $limit), 
		);
		$this->load->view("sisvent/business/products/list",$data);
		
	}

	public function search($term)
	{
		$term = str_replace("%20", " ", $term);
	
		$page = $this->input->get('p');
		
		$limit = 50;
		if(!$page)
			$page = 1;
		
		$total = $this->products_model->getTotalSearch($term);
		$last       = ceil( $total / $limit );

		$pag =  $page;
		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			'total' => $total,
			'page' => $pag,
			'limit' => $limit,
			'products' => $this->products_model->getProductsByWord($term, $page, $limit), 
		);
		$this->load->view("sisvent/business/products/list",$data);
		
	}

	public function add(){

		$this->backend_lib->control([1]);
		$data =array( 
			"families" => $this->products_model->getFamilies(),
			"providers" => $this->providers_model->getProviders(),
			"datasheets" => $this->products_model->getDatasheets()
		);
		$this->load->view("sisvent/business/products/add", $data);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$product_id = $this->input->post("product_id");
		$description = $this->input->post("description");
		$not_settle = $this->input->post("not_settle");
		$price = $this->input->post("price");
		$price_base = $this->input->post("price_base");
		$price_scale = $this->input->post("price_scale");
		$price_dist = $this->input->post("price_dist");
		$cost = 0;//$this->input->post("cost");
		$cost_cop = $this->input->post("cost_cop");
		$cost_rmb = $this->input->post("cost_rmb");
		$family = $this->input->post("family");
		$provider = $this->input->post("provider");
		$min = $this->input->post("min");
		$datasheet = $this->input->post("datasheet");

		$this->form_validation->set_rules("product_id","Código","required|is_unique[products.idProduct]");
		$this->form_validation->set_rules("description","Descripción","required");
		

		if ($this->form_validation->run()) {
			$data  = array(
				'idProduct' => $product_id, 
				'description' => $description,
				'not_settle' => $not_settle  == "on",
				'price' => $price,
				'price_base' => $price_base,
				'price_scale' => $price_scale,
				'price_dist' => $price_dist,
				'cost' => $cost,
				'cost_cop' => $cost_cop,
				'cost_rmb' => $cost_rmb,
				'family' => $family,
				'provider' => $provider,
				'min' => $min,
				'datasheet' => empty($datasheet) ? null : $datasheet
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
						
						$this->load->library('image_lib');

						//Set config for img library
						/*$config['image_library'] = 'gd2';
						$config['source_image'] = $this->upload->data('full_path');//'./assets/avatarPictures/productPictures/'.$image_data['file_name'].".".$ext;
						$config['maintain_ratio'] = true;
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
						$this->image_lib->initialize($config);
						if (!$this->image_lib->crop()) {
						    $error = "crop: ".$this->image_lib->display_errors();
							//print_r($error);
						}
						$this->image_lib->clear();
						unset($config);*/
							
						// resizing image
						$config['image_library'] = 'gd2';
					    $config['source_image'] = $this->upload->data('full_path');//'./assets/avatarPictures/productPictures/'.$image_data['file_name'].".".$ext;//$image_data['full_path'].;
					    $config['maintain_ratio'] = TRUE;
					    $config['width']     = 300 * $height / $width;
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

						if(!empty($error)){
							$this->session->set_flashdata("error",$error);
						}
						if ($this->products_model->save($data)) {

							$this->_save_product_datasheet_values($product_id,$datasheet);


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

					$this->_save_product_datasheet_values($product_id,$datasheet);
					$vendors = $this->vendors_model->getVendors();
			        $vendorsemails = "";
			        foreach($vendors as $vendor){
			        	if(!empty($vendor->email)){
			        		$vendorsemails .= empty($vendorsemails) ? $vendor->email : ",".$vendor->email ;
			        	}
			        }
					sendEmail("cdga777@gmail.com,".(!empty($vendorsemails) ? $vendorsemails : ""),"Nuevo Producto ".$product_id." - ".$description,"Hay un nuevo producto disponible: ".$product_id." - ".$description.", con precio base $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $price_base)), 2).".");

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

		$page = $this->input->get('p');
		
		if(!$page)
			$page = 1;

		$data =array( 
			'product' => $this->products_model->getProduct($product_id), 
			"families" => $this->products_model->getFamilies(),
			"providers" => $this->providers_model->getProviders(),
			'page' => $page,
			"datasheets" => $this->products_model->getDatasheets()
		);
		//print_r($data);
		$this->load->view("sisvent/business/products/edit",$data);
	}

	public function update(){

		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$product_id = $this->input->post("product_id");
		$description = $this->input->post("description");
		$not_settle = $this->input->post("not_settle");
		$price = $this->input->post("price");
		$price_base = $this->input->post("price_base");
		$price_scale = $this->input->post("price_scale");
		$price_dist = $this->input->post("price_dist");
		$cost = 0;//$this->input->post("cost");
		$cost_cop = $this->input->post("cost_cop");
		$cost_rmb = $this->input->post("cost_rmb");
		$family = $this->input->post("family");
		$provider = $this->input->post("provider");
		$min = $this->input->post("min");
		$datasheet = $this->input->post("datasheet");

		$page = $this->input->get('p');
		
		if(!$page)
			$page = 1;


		//$this->form_validation->set_rules("product_id","Código","required|is_unique[products.idProduct]");
		$this->form_validation->set_rules("description","Descripción","required");

		if ($this->form_validation->run()) {
			
			$product = $this->products_model->getProduct($product_id);

			if($price_base != $product->price_base)
			{
				$vendors = $this->vendors_model->getVendors();
		        $vendorsemails = "";
		        foreach($vendors as $vendor){
		        	if(!empty($vendor->email)){
		        		$vendorsemails .= empty($vendorsemails) ? $vendor->email : ",".$vendor->email ;
		        	}
		        }

				sendEmail("cdga777@gmail.com,"/*.(!empty($vendorsemails) ? $vendorsemails : "")*/,"Alerta de Cambio de precio base de ".$product_id." - ".$description,"Por favor tenga en cuenta que se ha cambiado el precio base de ".$product_id." - ".$description.", pasó de costar $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price_base)), 2)." a costar $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $price_base)), 2));
				//sendEmail("cdga777@gmail.com","Alerta de Cambio de precio base de ".$product_id." - ".$description,"Por favor tenga en cuenta que se ha cambiado el precio base de ".$product_id." - ".$description.", pasó de costar $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $product->price_base)), 2)." a costar $".number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $price_base)), 2)."<br> ".(!empty($vendorsemails) ? $vendorsemails : ""));

			}

			$data  = array(
				'description' => $description,
				'not_settle' => $not_settle == "on",
				'price' => $price,
				'price_base' => $price_base,
				'price_scale' => $price_scale,
				'price_dist' => $price_dist,
				'cost' => $cost,
				'cost_cop' => $cost_cop,
				'cost_rmb' => $cost_rmb,
				'family' => $family,
				'provider' => $provider,
				'min' => $min,
				'datasheet' => empty($datasheet) ? null : $datasheet
			);
			
//$this->_save_product_datasheet_values($product_id,$datasheet){
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
						
						$this->load->library('image_lib');

							
						// resizing image
						$config['image_library'] = 'gd2';
					    $config['source_image'] = $this->upload->data('full_path');//'./assets/avatarPictures/productPictures/'.$image_data['file_name'].".".$ext;//$image_data['full_path'].;
					    $config['maintain_ratio'] = TRUE;
					    $config['width']     =  300 * $height / $width;
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

						if(!empty($error)){
							$this->session->set_flashdata("error",$error);
						}
						if ($this->products_model->update($product_id,$data)) {
							$this->products_model->removeProductsLabelsValues($product_id,$datasheet);
							$this->_save_product_datasheet_values($product_id,$datasheet);
							redirect(base_url()."sisvent/business/products".createFullParamsLinks($page));
						}
						else{
							$this->session->set_flashdata("error","No se pudo actualizar la información ".$this->upload->display_errors());
							//$this->edit($product_id);
							//redirect(base_url()."sisvent/business/products/edit/".$product_id);
							$data =array( 
								'product' => $this->products_model->getProduct($product_id), 
								"families" => $this->products_model->getFamilies(),
								"providers" => $this->providers_model->getProviders(),
								'page' => $page,
								"datasheets" => $this->products_model->getDatasheets()
							);
							//print_r($data);
							$this->load->view("sisvent/business/products/edit",$data);
						}
					}
					else {
						$error = $this->upload->display_errors();//array('error' => $this->upload->display_errors());
						$this->session->set_flashdata("error",$error);
						//$this->edit($product_id);
						//redirect(base_url().'sisvent/business/products/add');
						$data =array( 
							'product' => $this->products_model->getProduct($product_id), 
							"families" => $this->products_model->getFamilies(),
							"providers" => $this->providers_model->getProviders(),
							'page' => $page,
							"datasheets" => $this->products_model->getDatasheets()
						);
						//print_r($data);
						$this->load->view("sisvent/business/products/edit",$data);
					}		
				
			}else
			{
				switch ($_FILES['imageAvatar']['error']) {
		            case UPLOAD_ERR_INI_SIZE:
		                $message = "El archivo excede el tamaño máximo permitido por el servidor";//"The uploaded file exceeds the upload_max_filesize directive in php.ini";
		                break;
		            case UPLOAD_ERR_FORM_SIZE:
		                $message = "El archivo excede el tamaño máximo permitido";//"The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
		                break;
		            case UPLOAD_ERR_PARTIAL:
		                $message = "El archivo fue subido parcialmente";//"The uploaded file was only partially uploaded";
		                break;
		            case UPLOAD_ERR_NO_FILE:
		                $message = "No se subió ningún archivo";//"No file was uploaded";
		                break;
		            case UPLOAD_ERR_NO_TMP_DIR:
		                $message = "Falta la carpeta temporal";//"Missing a temporary folder";
		                break;
		            case UPLOAD_ERR_CANT_WRITE:
		                $message = "Error al escibir en el disco";//Failed to write file to disk";
		                break;
		            case UPLOAD_ERR_EXTENSION:
		                $message = "Archivo parado por la extension";//File upload stopped by extension";
		                break;

		            default:
		                $message = "Error desconocido";//"Unknown upload error";
		                break;
		        } 
		        $this->session->set_flashdata("error",$message);
				if ($this->products_model->update($product_id,$data)) {
					$this->products_model->removeProductsLabelsValues($product_id,$datasheet);
							$this->_save_product_datasheet_values($product_id,$datasheet);
					redirect(base_url()."sisvent/business/products".createFullParamsLinks($page));
				}
				else{
					$this->session->set_flashdata("error","No se pudo actualizar la información");
					//redirect(base_url()."sisvent/business/products/edit/".$product_id);
					//$this->edit($product_id);
					$data =array( 
						'product' => $this->products_model->getProduct($product_id), 
						"families" => $this->products_model->getFamilies(),
						"providers" => $this->providers_model->getProviders(),
						'page' => $page,
						"datasheets" => $this->products_model->getDatasheets()
					);
					//print_r($data);
					$this->load->view("sisvent/business/products/edit",$data);
				}
			}
		}
		else{
			//$this->edit($product_id);
			$data =array( 
				'product' => $this->products_model->getProduct($product_id), 
				"families" => $this->products_model->getFamilies(),
				"providers" => $this->providers_model->getProviders(),
				'page' => $page,
				"datasheets" => $this->products_model->getDatasheets()
			);
			//print_r($data);
			$this->load->view("sisvent/business/products/edit",$data);
		}
	}

	public function duplicate($product_id){
		$this->backend_lib->control([1]);
		$data =array( 
			'product' => $this->products_model->getProduct($product_id), 
			"families" => $this->products_model->getFamilies(),
			"providers" => $this->providers_model->getProviders(),
			"datasheets" => $this->products_model->getDatasheets()
		);
		//print_r($data);
		$this->load->view("sisvent/business/products/duplicate",$data);
	}

	public function delete($product_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$this->products_model->remove($product_id);
		//redirect(base_url()."sisvent/business/products");
		echo base_url()."sisvent/business/products";
	}

	function _save_product_datasheet_values($product_id,$datasheet){
		
		$labels = $this->products_model->getDatasheetsLabels($datasheet);

		//echo "<script>console.log( 'per: ".empty($per_packages)." ' );</script>";
		foreach($labels as $label){
			//echo "<script>console.log( 'Debug Objects: ".$i." = ".$labels[$i]." + " .implode(" -- ", $per_packages)." + " . (array_search($labels[$i], $per_packages) === FALSE)." + " .array_search($labels[$i], $per_packages). "' );</script>";

			$data  = array(
				'idProduct' =>$product_id,
				'idDatasheet' =>$datasheet,
				'idLabel' =>$label->idLabel,
				'value' => $this->input->post("ds-".$datasheet."-".$label->idLabel)

			);
			//echo "<pre>";
			//print_r($data);
			//echo "</pre>";
			$this->products_model->saveProductsLabelsValues($data);
			//$this->updateProduct($products[$i],$quantities[$i]);
		}
	}

	public function export(){
		$this->load->view("sisvent/business/products/export");
	}

	public function createExcelProd() {

		$this->load->helper("file");
		
		$from = $this->input->post("from");
		$until = $this->input->post("until");

		$from = str_replace("%20", " ", $from);
		$until = str_replace("%20", " ", $until);
		
		/*$invoices = $this->invoices_model->getInvoices(true,  $store,  'all',  'all',  'all', -1, 50, $from, $until);

		echo ($from)."<br>";
		echo strtotime($from)."<br>";
		echo date('Y-m-d H:i:s',strtotime($from))."<br>";
		echo date('Y-m-d H:i:s',strtotime($until))."<br>";
		echo $this->db->last_query()."<br>";

		foreach ($invoices as $val){
       		echo $val->idInvoice."  ".$val->date."<br>";
        } */

		$dat = uniqid('MAMProds', true);

		$fileName = 'ART-'.$dat.'.xlsx';  
		$fileNamePrec = 'LTA-'.$dat.'.xlsx';  
		//$employeeData = $this->EmployeeModel->employeeList();
		$products = $this->products_model->getProducts($from, $until);
		$spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
       	$sheet->setCellValue('A1', 'Código');
		$sheet->setCellValue('B1', 'Código de barras');
		$sheet->setCellValue('C1', 'Código equivalente');
		$sheet->setCellValue('D1', 'Código corto');
		$sheet->setCellValue('E1', 'Familia');
		$sheet->setCellValue('F1', 'Descripción');
		$sheet->setCellValue('G1', 'Descrip. Etiquetas');
		$sheet->setCellValue('H1', 'Descrip. Ticket');
		$sheet->setCellValue('I1', 'Proveedor habitual');
		$sheet->setCellValue('J1', 'Tipo de IVA');
		$sheet->setCellValue('K1', 'Precio de costo');
		$sheet->setCellValue('L1', 'Descuento 1');
		$sheet->setCellValue('M1', 'Descuento 2D');

		$spreadsheetPrec = new Spreadsheet();
        $sheetPrec = $spreadsheetPrec->getActiveSheet();
       	$sheetPrec->setCellValue('A1', 'Código de tarifa');
		$sheetPrec->setCellValue('B1', 'Artículo');
		$sheetPrec->setCellValue('C1', 'Margen');
		$sheetPrec->setCellValue('D1', 'Precio');
		

              

        $rows = 2;
        $rowsDetails = 2;
        foreach ($products as $val){
        	$sheet->setCellValue('A' . $rows, $val->idProduct);
			//$sheet->setCellValue('B' . $rows, '1');
			//$sheet->setCellValue('C' . $rows, );
			//$sheet->setCellValue('D' . $rows, $val->description);
			$sheet->setCellValue('E' . $rows, 0);
			$sheet->setCellValue('F' . $rows, $val->description);
			//$sheet->setCellValue('G' . $rows, 'Código de barras');
			//$sheet->setCellValue('H' . $rows, 'No');
			//$sheet->setCellValue('I' . $rows, 'Descripción larga');
			$sheet->setCellValue('J' . $rows, '3');
			$sheet->setCellValue('K' . $rows, $val->cost_cop);
			//$sheet->setCellValue('L' . $rows, 'Gravado');
			//$sheet->setCellValue('M' . $rows, 'Código impuesto retención');
			//$sheet->setCellValue('N' . $rows, 'Valor impoconsumo');
			//$sheet->setCellValue('O' . $rows, $val->price); 

	        	//echo "   ".$i;
        		//echo "      ". $det->productId."  ".$det->quantity." ".$det->unit." ".$det->subtotal."<br>";
	            $sheetPrec->setCellValue('A' . $rowsDetails, 1);
	            $sheetPrec->setCellValue('B' . $rowsDetails, $val->idProduct);
	            //$sheetPrec->setCellValue('C' . $rowsDetails, $rd-1);
		    	$sheetPrec->setCellValue('D' . $rowsDetails, $val->price_base);
	            $rowsDetails++;

	            $sheetPrec->setCellValue('A' . $rowsDetails, 2);
	            $sheetPrec->setCellValue('B' . $rowsDetails, $val->idProduct);
	            //$sheetPrec->setCellValue('C' . $rowsDetails, $rd-1);
		    	$sheetPrec->setCellValue('D' . $rowsDetails, $val->price_dist);
	            $rowsDetails++;

	            $sheetPrec->setCellValue('A' . $rowsDetails, 3);
	            $sheetPrec->setCellValue('B' . $rowsDetails, $val->idProduct);
	            //$sheetPrec->setCellValue('C' . $rowsDetails, $rd-1);
		    	$sheetPrec->setCellValue('D' . $rowsDetails, $val->price_scale);
	            $rowsDetails++;
				
				$sheetPrec->setCellValue('A' . $rowsDetails, 4);
	            $sheetPrec->setCellValue('B' . $rowsDetails, $val->idProduct);
	            //$sheetPrec->setCellValue('C' . $rowsDetails, $rd-1);
		    	$sheetPrec->setCellValue('D' . $rowsDetails, $val->price);
	            $rowsDetails++;
	       
            $rows++;
        } 

        if (!is_dir('./public/prod/')) {
			//print_r("<br> Creando directorio ".'./public/dist/images/products/'.'pf'.substr( $this->session->productdata('product_data')['product_name'], 0,2).$this->session->productdata('product_data')['product_uname']);
        	mkdir('./public/prod/', 0777, true);
    	}
    	
    	delete_files('./public/prod/');

        $writer = new Xlsx($spreadsheet);
		$writer->save("public/prod/".$fileName);

		$writerPrec = new Xlsx($spreadsheetPrec);
		$writerPrec->save("public/prod/".$fileNamePrec);

		$data  = array(
				'prod' => "public/prod/".$fileName,
				'prodPrec' => "public/prod/".$fileNamePrec,
			);

		echo json_encode($data);
		//header("Content-Type: application/vnd.ms-excel");
        //redirect(base_url()."/public/".$fileName); 
    }    

    public function createShopifyCSVProd() {

		$this->load->helper("file");
		
		$filename = 'products_'.date('Ymd').'.csv'; 
	   header("Content-Description: File Transfer"); 
	   header("Content-Disposition: attachment; filename=$filename"); 
	   header("Content-Type: application/csv; ");

		/*$invoices = $this->invoices_model->getInvoices(true,  $store,  'all',  'all',  'all', -1, 50, $from, $until);

		echo ($from)."<br>";
		echo strtotime($from)."<br>";
		echo date('Y-m-d H:i:s',strtotime($from))."<br>";
		echo date('Y-m-d H:i:s',strtotime($until))."<br>";
		echo $this->db->last_query()."<br>";

		foreach ($invoices as $val){
       		echo $val->idInvoice."  ".$val->date."<br>";
        } */        

        $header = array(
		"Handle",
		"Title",
		"Body (HTML)",
		"Vendor",
		"Type",
		"Tags",
		"Published",
		"Option1 Name",
		"Option1 Value",
		"Option2 Name",
		"Option2 Value",
		"Option3 Name",
		"Option3 Value",
		"Variant SKU",
		"Variant Grams",
		"Variant Inventory Tracker",
		"Variant Inventory Qty",
		"Variant Inventory Policy",
		"Variant Fulfillment Service",
		"Variant Price",
		"Variant Compare At Price",
		"Variant Requires Shipping",
		"Variant Taxable",
		"Variant Barcode",
		"Image Src",
		"Image Position",
		"Image Alt Text",
		"Gift Card",
		"SEO Title",
		"SEO Description",
		"Google Shopping / Google Product Category",
		"Google Shopping / Gender",
		"Google Shopping / Age Group",
		"Google Shopping / MPN",
		"Google Shopping / AdWords Grouping",
		"Google Shopping / AdWords Labels",
		"Google Shopping / Condition",
		"Google Shopping / Custom Product",
		"Google Shopping / Custom Label 0",
		"Google Shopping / Custom Label 1",
		"Google Shopping / Custom Label 2",
		"Google Shopping / Custom Label 3",
		"Google Shopping / Custom Label 4",
		"Variant Image",
		"Variant Weight Unit",
		"Variant Tax Code",
		"Cost per item",
		"Status");

		$file = fopen('php://output', 'w');
   		fputcsv($file, $header);

		$products = $this->products_model->getProducts();
        $rows = 2;
        $rowsDetails = 2;
        foreach ($products as $val){
        	$line = array(
        	$val->idProduct,
			$val->description,
			"",
			"",
			$val->family_name,
			"",
			"TRUE",
			"Title",
			"Default Title",
			"",
			"",
			"",
			"",
			$val->idProduct,
			0,
			"",
			0,
			"deny",
			"manual",
			$val->price,
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			$val->description,
			$val->description,
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			$val->cost_cop,
			"active");
			fputcsv($file,$line);

        } 

        fclose($file); 
		exit;
		//header("Content-Type: application/vnd.ms-excel");
        //redirect(base_url()."/public/".$fileName); 
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
				$ua = 0;
				$nosaved = "";
				for ($i = 0; $i < $size; $i++)
				{
					//echo "-------------------------------------<br>";
					//echo "i = ".$i."<br>";
				    
				    $columns = str_getcsv($lines[$i],";");
					$product_id = test_input($columns[0]);
					$description = test_input($columns[1]);
					$family = test_input($columns[2]);
					$price_base = test_input($columns[3]);
					$price_dist = test_input($columns[4]);
					$price_scale = test_input($columns[5]);
					$price = test_input($columns[6]);
					//$cost_cop = test_input($columns[7]);
					//$cost_rmb = test_input($columns[8]);

					/*$columns = str_getcsv($lines[$i],",");
					$product_id = test_input($columns[0]);
					$description = test_input($columns[1]);
					$family = test_input($columns[2]);
					$price_base = test_input($columns[3]);
					$price_dist = test_input($columns[4]);
					$price_scale = test_input($columns[5]);
					$price = test_input($columns[6]);
					$cost_cop = test_input($columns[7]);
					$cost_rmb = test_input($columns[8]);
					*/
					//$query = "INSERT INTO `users`(`product_id`, `price_base`, `cost_cop`, `cost_rmb`) VALUES ('".$product_id."','".($price_base)."','".$cost_cop."','".str_replace(".", ",",$cost_rmb)."')";
					//echo $query."<br>";
					//echo $product_id."<br>";
					if(!empty($product_id))
					{
						$prod = $this->products_model->getProduct($product_id);

						if(!empty($prod))
						{
							//$fam_id = 1;
							//echo $product_id." Ya existe<br>";
							$data  = array(
								'price' => str_replace(".","",$price),
								'price_base' => str_replace(".","",$price_base),
								'price_scale' => str_replace(".","",$price_scale),
								'price_dist' => str_replace(".","",$price_dist)
								//'cost_rmb' => floatval($cost_rmb)//str_replace(".", ",",$cost_rmb ),
							);

							if ($this->products_model->update($product_id,$data)){
								$ua++;
							}else
							{
								$nosaved .= $product_id." Error actualizando<br>";
							}

						}/*else
						{
							//echo $product_id." No existe<br>";
							$fam = $this->products_model->getFamilyByName($family);

							if(empty($family))
							{
								$fam_id = 1;
							}else
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
								'cost_rmb' => floatval($cost_rmb),//str_replace(".", ",",$cost_rmb ),
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
						}*/

						
					}else
					{
						$nosaved .= $product_id." Sin código<br>";
					}
				}
				//print_r("Usuarios ")
				$error = array('success_msg' => 'Usuarios registrados: '.$ua.' - '.$uc.'/'.$size,'u_permissions' => $this->permissions,
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

	public function changefamilies(){

		$this->load->view("sisvent/business/product_families/update");
	}
	
	public function changeuploadedfamilies()
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
				$ua = 0;
				$nosaved = "";
				for ($i = 0; $i < $size; $i++)
				{
					//echo "-------------------------------------<br>";
					//echo "i = ".$i."<br>";
				    
				    $columns = str_getcsv($lines[$i],";");
					$family_id = test_input($columns[0]);
					$description = test_input($columns[1]);
					
					//$query = "INSERT INTO `users`(`product_id`, `price_base`, `cost_cop`, `cost_rmb`) VALUES ('".$product_id."','".($price_base)."','".$cost_cop."','".str_replace(".", ",",$cost_rmb)."')";
					//echo $query."<br>";
					
							//echo $product_id." No existe<br>";
					$fam = $this->products_model->getFamily($family_id);

					
					if(empty($fam))
					{	
						$datafam  = array(
							'idFamily' => $family_id,
							'name' => $fam
						);
								$ua++;
						//echo "Guardando Familia: ".$family_id." - ".$description."<br>";
						$this->products_model->saveFamily($datafam);
						//$fam_id = $this->db->insert_id();
					}
					else{
						//$fam_id = $fam->idFamily;
						$datafam  = array(
							'name' => $description
						);
								$uc++;
						//echo "Actualizando Familia: ".$family_id." from ".$fam->name." to ".$description."<br>";
						$this->products_model->updateFamily($family_id,$datafam);
					}
					
					/*$data  = array(
						'idProduct' => $product_id, 
						'description' => $description,
						'price' => $price,
						'price_base' => $price_base,
						'price_scale' => $price_scale,
						'price_dist' => $price_dist,
						'cost' => 0,
						'cost_cop' => $cost_cop,
						'cost_rmb' => floatval($cost_rmb),//str_replace(".", ",",$cost_rmb ),
						'family' => $fam_id,
						'provider' => 1,
						'min' => 100
					);

					if ($this->products_model->save($data)) {
						$uc++;
					}else
					{
						$nosaved .= $id." No guardó<br>";
					}*/
						
				}
				//print_r("Usuarios ")
				$error = array('success_msg' => 'Familias registradas: '.$ua.' - '.$uc.'/'.$size,'u_permissions' => $this->permissions, 'info_msg' => $nosaved);
				$this->load->view('sisvent/business/product_families/update', $error);
            }else{
                $error = array('error_msg' => 'Invalid file, please select only CSV file.:)','u_permissions' => $this->permissions);
				$this->load->view('sisvent/business/product_families/update', $error);
            }
        }else{
            $error = array('error_msg' => 'Error on file upload, please try again.:)','u_permissions' => $this->permissions);
			$this->load->view('sisvent/business/product_families/update', $error);
        }
            
    }

	public function loadfamily(){

		$this->load->view("sisvent/business/product_families/load");
	}
	
	public function uploadfamilies()
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
				$ua = 0;
				$nosaved = "";
				for ($i = 0; $i < $size; $i++)
				{
					//echo "-------------------------------------<br>";
					//echo "i = ".$i."<br>";
				    
				    $columns = str_getcsv($lines[$i],";");
					$family = test_input($columns[0]);
					$product_id = test_input($columns[1]);
					$description = test_input($columns[2]);
					
					//$query = "INSERT INTO `users`(`product_id`, `price_base`, `cost_cop`, `cost_rmb`) VALUES ('".$product_id."','".($price_base)."','".$cost_cop."','".str_replace(".", ",",$cost_rmb)."')";
					//echo $query."<br>";
					if(!empty($product_id))
					{
						$prod = $this->products_model->getProduct($product_id);

						if(!empty($prod))
						{
							//$fam_id = 1;
							//echo $product_id." Ya existe<br>";
							//echo "Actualizando Familia de producto: ".$product_id."  to ".$family." - ".$description."<br>";
							$data  = array(
								'family' => $family
							);

							if ($this->products_model->update($product_id,$data)){
								$ua++;
							}else
							{
								$nosaved .= $product_id." Error actualizando<br>";
							}

						}else
						{
							$nosaved .= $product_id." No existe<br>";
						}

						
					}else
					{
						$nosaved .= $product_id." Sin código<br>";
					}
				}
				//print_r("Usuarios ")
				$error = array('success_msg' => 'Familias actualizadas: '.$ua.' - '.$uc.'/'.$size,'u_permissions' => $this->permissions, 'info_msg' => $nosaved);
				$this->load->view('sisvent/business/product_families/load', $error);
            }else{
                $error = array('error_msg' => 'Invalid file, please select only CSV file.:)','u_permissions' => $this->permissions);
				$this->load->view('sisvent/business/product_families/load', $error);
            }
        }else{
            $error = array('error_msg' => 'Error on file upload, please try again.:)','u_permissions' => $this->permissions);
			$this->load->view('sisvent/business/product_families/load', $error);
        }
            
    }

	public function viewdatasheets(){
		$data =array( 
			'datasheets' => $this->products_model->getDatasheets()
		);
		$this->load->view("sisvent/business/product_datasheets/list",$data);
	}

	public function adddatasheet(){

		$this->load->view("sisvent/business/product_datasheets/add");
	}

	public function storedatasheet(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$name = $this->input->post("name");
		$labels = $this->input->post("label");
		$defvals = $this->input->post("defval");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			$data  = array(
				'name' => $name
			);

			if ($this->products_model->saveDatasheet($data)) {
				$idDatasheet = $this->products_model->lastID();
				$this->_save_labels($labels,$defvals,$idDatasheet);

				redirect(base_url()."sisvent/business/products/viewdatasheets");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				redirect(base_url()."sisvent/business/products/adddatasheet");
			}
		}
		else{
			$this->adddatasheet();
		}
	}

	function _save_labels($labels,$defvals,$idDatasheet){
		
		//echo "<script>console.log( 'per: ".empty($per_packages)." ' );</script>";
		for ($i=0; $i < count($labels); $i++) { 
			//echo "<script>console.log( 'Debug Objects: ".$i." = ".$labels[$i]." + " .implode(" -- ", $per_packages)." + " . (array_search($labels[$i], $per_packages) === FALSE)." + " .array_search($labels[$i], $per_packages). "' );</script>";

			$data  = array(
				'idDatasheet' =>$idDatasheet,
				'label' =>$labels[$i],
				'default_value' =>$defvals[$i]
			);
			//echo "<pre>";
			//print_r($data);
			//echo "</pre>";
			$this->products_model->saveDatasheetsLabels($data);
			//$this->updateProduct($products[$i],$quantities[$i]);
		}
	}
	public function editdatasheet($datasheet_id){
		$data =array( 
			'datasheet' => $this->products_model->getDatasheet($datasheet_id),
			'labels' => $this->products_model->getDatasheetsLabels($datasheet_id)
		);
		//print_r($data);
		$this->load->view("sisvent/business/product_datasheets/edit",$data);
	}

	public function updatedatasheet(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$datasheet_id = $this->input->post("datasheet_id");
		$name = $this->input->post("name");
		$labels = $this->input->post("label");
		$idLabels = $this->input->post("label_id");
		$defvals = $this->input->post("defval");
		
		$this->form_validation->set_rules("name","Nombre","required");
		
		if ($this->form_validation->run()) {
			
			$data  = array(
				'name' => $name
			);

			if ($this->products_model->updateDatasheet($datasheet_id,$data)) {

				//$this->products_model->removeDatasheetsLabels($datasheet_id);
				$this->_update_labels($idLabels,$labels,$defvals,$datasheet_id);

				redirect(base_url()."sisvent/business/products/viewdatasheets");
			}
			else{
				$this->session->set_flashdata("error","No se pudo actualizar la información");
				redirect(base_url()."sisvent/business/products/editdatasheet/".$datasheet_id);
			}
		}
		else{
			$this->editdatasheet($datasheet_id);
		}
	}
	function _update_labels($idLabels,$labels,$defvals,$idDatasheet){
		
		$currentlabels = $this->products_model->getDatasheetsLabels($idDatasheet);
	
		$idCurrentLabels = array_column($currentlabels, 'idLabel');

		//$deleted = array();
		for ($i=0; $i < count($idCurrentLabels); $i++) { 
			if(!in_array($idCurrentLabels[$i], $idLabels)){
				//array_push($deleted, $idCurrentLabels[$i]);
				$this->products_model->removeLabel($idCurrentLabels[$i]);
			}
		}
		
		for ($i=0; $i < count($labels); $i++) { 

			if(strpos($idLabels[$i], "new_") === FALSE)
			{
				$data  = array(
					//'idDatasheet' =>$idDatasheet,
					'label' =>$labels[$i],
					'default_value' =>$defvals[$i]
				);
				$this->products_model->updateDatasheetLabels($idLabels[$i],$data);
			}else{
				$data  = array(
					'idDatasheet' =>$idDatasheet,
					'label' =>$labels[$i],
					'default_value' =>$defvals[$i]
				);
				$this->products_model->saveDatasheetsLabels($data);
			}
			//echo "<pre>";
			//print_r($data);
			//echo "</pre>";
		}
	}
	public function deletedatasheet($datasheet_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$this->products_model->removeDatasheet($datasheet_id);
		echo base_url()."sisvent/business/products/viewdatasheets";
	}

	public function getDatasheetsLabels(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$datasheet = $this->input->post("datasheet");

		$labels = $this->products_model->getDatasheetsLabels($datasheet);
		
		echo json_encode($labels);
	}

	public function getDatasheetsValues(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$product_id = $this->input->post("product_id");
		$datasheet = $this->input->post("datasheet");

		$values = $this->products_model->getProductsLabelsValues($product_id,$datasheet);
		
		echo json_encode($values);
	}

	public function loaddatasheets(){

		$this->load->view("sisvent/business/product_datasheets/load");
	}
	
	public function uploaddatasheets()
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
				$ua = 0;
				$nosaved = "";
				for ($i = 0; $i < $size; $i++)
				{
					//echo "-------------------------------------<br>";
					//echo "i = ".$i."<br>";
				    
				    $columns = str_getcsv($lines[$i],";");
					$datasheet = test_input($columns[0]);
					$product_id = test_input($columns[1]);
					$description = test_input($columns[2]);
					
					//$query = "INSERT INTO `users`(`product_id`, `price_base`, `cost_cop`, `cost_rmb`) VALUES ('".$product_id."','".($price_base)."','".$cost_cop."','".str_replace(".", ",",$cost_rmb)."')";
					//echo $query."<br>";
					if(!empty($product_id))
					{
						$prod = $this->products_model->getProduct($product_id);

						if(!empty($prod))
						{
							//$fam_id = 1;
							//echo $product_id." Ya existe<br>";
							//echo "Actualizando Ficha Técnica de producto: ".$product_id."  to ".$datasheet." - ".$description."<br>";
							$data  = array(
								'datasheet' => $datasheet
							);

							if ($this->products_model->update($product_id,$data)){
								$ua++;
							}else
							{
								$nosaved .= $product_id." Error actualizando<br>";
							}

						}else
						{
							//echo "producto: ".$product_id."  No existe<br>";
							$nosaved .= $product_id." No existe<br>";
						}

						
					}else
					{
						$nosaved .= $product_id." Sin código<br>";
					}
				}
				//print_r("Usuarios ")
				$error = array('success_msg' => 'Datasheets actualizados: '.$ua.' - '.$uc.'/'.$size,'u_permissions' => $this->permissions, 'info_msg' => $nosaved);
				$this->load->view('sisvent/business/product_datasheets/load', $error);
            }else{
                $error = array('error_msg' => 'Invalid file, please select only CSV file.:)','u_permissions' => $this->permissions);
				$this->load->view('sisvent/business/product_datasheets/load', $error);
            }
        }else{
            $error = array('error_msg' => 'Error on file upload, please try again.:)','u_permissions' => $this->permissions);
			$this->load->view('sisvent/business/product_datasheets/load', $error);
        }
            
    }

	public function createExcel() {
		
		/*$invoices = $this->invoices_model->getInvoices(true,  $store,  'all',  'all',  'all', -1, 50, $from, $until);

		echo ($from)."<br>";
		echo strtotime($from)."<br>";
		echo date('Y-m-d H:i:s',strtotime($from))."<br>";
		echo date('Y-m-d H:i:s',strtotime($until))."<br>";
		echo $this->db->last_query()."<br>";

		foreach ($invoices as $val){
       		echo $val->idInvoice."  ".$val->date."<br>";
        } */

		
		$fileName = 'Productos.xlsx';  
		//$employeeData = $this->EmployeeModel->employeeList();
		$products = $this->products_model->getProducts();
		$spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
       	$sheet->setCellValue('A1', 'Tipo de producto');
		$sheet->setCellValue('B1', 'Código clasificacion de inventario y servicio');
		$sheet->setCellValue('C1', 'Código del producto');
		$sheet->setCellValue('D1', 'Nombre del producto / Servicio');
		$sheet->setCellValue('E1', 'Referencia de fábrica');
		$sheet->setCellValue('F1', 'Unidad de medida impresión factura');
		$sheet->setCellValue('G1', 'Código de barras');
		$sheet->setCellValue('H1', 'Inventariable ');
		$sheet->setCellValue('I1', 'Descripción larga');
		$sheet->setCellValue('J1', 'Código impuesto cargo');
		$sheet->setCellValue('K1', 'Precio con impuestos');
		$sheet->setCellValue('L1', 'Clasificación tributaria');
		$sheet->setCellValue('M1', 'Código impuesto retención');
		$sheet->setCellValue('N1', 'Valor impoconsumo');
		$sheet->setCellValue('O1', 'Lista de precio 1');
		$sheet->setCellValue('P1', 'Lista de precio 2');
		$sheet->setCellValue('Q1', 'Lista de precio 3');
		$sheet->setCellValue('R1', 'Lista de precio 4');
		$sheet->setCellValue('S1', 'Lista de precio 5');
		$sheet->setCellValue('T1', 'Lista de precio 6');
		$sheet->setCellValue('U1', 'Lista de precio 7');
		$sheet->setCellValue('V1', 'Lista de precio 8');
		$sheet->setCellValue('W1', 'Lista de precio 9');
		$sheet->setCellValue('X1', 'Lista de precio 10');
		$sheet->setCellValue('Y1', 'Lista de precio 11');
		$sheet->setCellValue('Z1', 'Lista de precio 12');
		$sheet->setCellValue('AA1', 'Código arancelario');
		$sheet->setCellValue('AB1', 'Marca');
		$sheet->setCellValue('AC1', 'Modelo');
		$sheet->setCellValue('AD1', 'Unidad de medida factura electrónica');       

        $rows = 2;
        foreach ($products as $val){
        	echo $val->idProduct."  ".$val->description." ".$val->price."<br>";
       		$rd = 2;

       		$sheet->setCellValue('A' . $rows, 'P');
			$sheet->setCellValue('B' . $rows, '1');
			$sheet->setCellValue('C' . $rows, $val->idProduct);
			$sheet->setCellValue('D' . $rows, $val->description);
			//$sheet->setCellValue('E' . $rows, 'Referencia de fábrica');
			//$sheet->setCellValue('F' . $rows, 'Unidad de medida impresión factura');
			//$sheet->setCellValue('G' . $rows, 'Código de barras');
			$sheet->setCellValue('H' . $rows, 'No');
			//$sheet->setCellValue('I' . $rows, 'Descripción larga');
			$sheet->setCellValue('J' . $rows, '1');
			$sheet->setCellValue('K' . $rows, 'Si');
			$sheet->setCellValue('L' . $rows, 'Gravado');
			//$sheet->setCellValue('M' . $rows, 'Código impuesto retención');
			//$sheet->setCellValue('N' . $rows, 'Valor impoconsumo');
			$sheet->setCellValue('O' . $rows, $val->price);
			//$sheet->setCellValue('P' . $rows, 'Lista de precio 2');
			//$sheet->setCellValue('Q' . $rows, 'Lista de precio 3');
			//$sheet->setCellValue('R' . $rows, 'Lista de precio 4');
			//$sheet->setCellValue('S' . $rows, 'Lista de precio 5');
			//$sheet->setCellValue('T' . $rows, 'Lista de precio 6');
			//$sheet->setCellValue('U' . $rows, 'Lista de precio 7');
			//$sheet->setCellValue('V' . $rows, 'Lista de precio 8');
			//$sheet->setCellValue('W' . $rows, 'Lista de precio 9');
			//$sheet->setCellValue('X' . $rows, 'Lista de precio 10');
			//$sheet->setCellValue('Y' . $rows, 'Lista de precio 11');
			//$sheet->setCellValue('Z' . $rows, 'Lista de precio 12');
			//$sheet->setCellValue('AA' . $rows, 'Código arancelario');
			//$sheet->setCellValue('AB' . $rows, 'Marca');
			//$sheet->setCellValue('AC' . $rows, 'Modelo');
			//$sheet->setCellValue('AD' . $rows, 'Unidad de medida factura electrónica');


            $rows++;
        } 
        $writer = new Xlsx($spreadsheet);
		$writer->save("public/".$fileName);

		//header("Content-Type: application/vnd.ms-excel");
        //redirect(base_url()."/public/".$fileName); 
    }    
	
}