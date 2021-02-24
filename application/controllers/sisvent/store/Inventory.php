<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1,4]);
		$this->load->helper('file');
        $this->load->model("inventory_model");
        $this->load->model("stores_model");
        $this->load->model("users_model");
    }

	public function index()
	{
		$data  = array(
			'inventories' => $this->inventory_model->getInventories(), 
		);
		$this->load->view("sisvent/store/inventory/list",$data);
		
	}

	public function viewInventory()
	{
		$data  = array(
			'products' => $this->inventory_model->getCurrentInventory(-1), 
			'stores' => $this->stores_model->getStores()
		);
		$this->load->view("sisvent/store/inventory/index",$data);
		
	}

	public function addInventory(){
		$data  = array(
			'users' => $this->users_model->getUsers(), 
			'stores' => $this->stores_model->getStores(), 
		);
		$this->load->view("sisvent/store/inventory/addInventory",$data);
	}

	public function storeInventory(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$user = $this->input->post("user");
		$store = $this->input->post("store");
		$comment = $this->input->post("comments");
		$date = $this->input->post("date");
		
		$inve = $this->inventory_model->getStoreProduct($store,$products[$i]);

		$data  = array(
			'storeId' => $store, 
			'userId' => $user,
			'comments' => $comment,
			'date' => date('Y-m-d H:i:s',strtotime($date)),
		);
		$this->inventory_model->saveInventory($data);
				
		redirect(base_url()."sisvent/store/inventory");
		
	}

	public function count1($inventory){
		$data  = array(
			'inventory' => $this->inventory_model->getInventory($inventory), 
			'details' => $this->inventory_model->getCount1($inventory), 
		);
		$this->load->view("sisvent/store/inventory/count1",$data);
		//print_r($data);
	}

	public function count2($inventory){
		$data  = array(
			'inventory' => $this->inventory_model->getInventory($inventory), 
			'details' => $this->inventory_model->getCount2($inventory), 
		);
		$this->load->view("sisvent/store/inventory/count2",$data);
	}

	public function add(){
		$data  = array(
			'stores' => $this->stores_model->getStores(), 
		);
		$this->load->view("sisvent/store/inventory/add",$data);
	}

	public function getAllProducts(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$products = $this->inventory_model->getAllProducts($valor);
		echo json_encode($products);
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

		$producto = $this->inventory_model->getProduct($this->input->post("ref"));
		echo json_encode($producto);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$store = $this->input->post("store");
		$products = $this->input->post("refs");
		$quantities = $this->input->post("quantities");
		
		if($products && count($products) > 0)
		{
			for ($i=0; $i < count($products); $i++) { 
				$inve = $this->inventory_model->getStoreProduct($store,$products[$i]);

				if(empty($inve))
				{
					$data  = array(
						'idStore' => $store, 
						'idProduct' => $products[$i],
						'stock' => $quantities[$i]
					);
					$this->inventory_model->save($data);
				}else{
					$data  = array(
						'stock' => $inve->stock+$quantities[$i]
					);
					$this->inventory_model->update($store,$products[$i],$data);
				}

				/*$data  = array(
					'idStore' => $store, 
					'idProduct' => $products[$i],
					'stock' => $quantities[$i]
				);
				$this->inventory_model->save($data);*/
			}
			redirect(base_url()."sisvent/store/inventory");
		}
		else{
			$data  = array(
				'stores' => $this->stores_model->getStores(), 
			);
			$this->session->set_flashdata("error","Debe ingresar al menos un producto");
			$this->load->view("sisvent/store/inventory/add",$data);
			//$this->add();
		}
	}

	public function getStoreInventorys($store)
	{
		//$store = $this->input->post("store");
		$products = $this->inventory_model->getCurrentInventory($store);
		//echo json_encode($products);
		$html = '';
		foreach($products as $product){
			$html .= '<tr class="text-gray-700">'
	        .'  <td class="px-4 py-3">'
	        .'    <div class="flex items-center text-sm">'
	        .'      <div class="relative hidden w-8 h-8 mr-3 md:block">'
	        .'        <img class="object-cover w-full h-full" src="'.get_images_path($product->picture_url).'" alt="" loading="lazy"/>'
	        .'        <div class="absolute inset-0 shadow-inner" aria-hidden="true"></div>'
	        .'      </div>'
	        .'        <div>'
	        .'          <p class="font-semibold">'.$product->idProduct.'</p>'
	        .'        </div>'
	        .'    </div>'
	        .'  </td>'
	        .'  <td class="px-4 py-3 text-xs">'.$product->description.'</td>'
	        .'  <td class="px-4 py-3 text-sm">'.$product->stock.'</td>'
	        .'  <td class="px-4 py-3">'
	        .'    <!--div class="flex items-center space-x-4 text-sm">'
	        .'      <a href="'.base_url().'sisvent/store/inventory/edit/'.$product->idProduct.'" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">'
	        .'        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">'
	        .'          <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>'
	        .'        </svg>'
	        .'      </a>';
	        if($store != -1)
	        {
		        $html .= '      <a href="'.base_url().'sisvent/store/inventory/delete/'.$product->idStore.'/'.$product->idProduct.'" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this)" aria-label="Delete">'
		        .'        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">'
		        .'          <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>'
		        .'        </svg>'
		        .'      </a>'
		        .'    </div-->'
		        .'  </td>'
		        .'</tr>';
		    }
	    }

	    echo ($html);

	}

	public function getStoreInventory()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$store = $this->input->post("store");
		$products = $this->inventory_model->getCurrentInventory($store);
		//echo json_encode($products);
		$html = '';
		foreach($products as $product){
			$html .= '<tr class="text-gray-700">'
	        .'  <td class="px-4 py-3">'
	        .'    <div class="flex items-center text-sm">'
	        .'      <div class="relative hidden w-8 h-8 mr-3 md:block">'
	        .'        <img class="object-cover w-full h-full" src="'.get_images_path($product->picture_url).'" alt="" loading="lazy"/>'
	        .'        <div class="absolute inset-0 shadow-inner" aria-hidden="true"></div>'
	        .'      </div>'
	        .'        <div>'
	        .'          <p class="font-semibold">'.$product->idProduct.'</p>'
	        .'        </div>'
	        .'    </div>'
	        .'  </td>'
	        .'  <td class="px-4 py-3 text-xs">'.$product->description.'</td>'
	        .'  <td class="px-4 py-3 text-sm">'.$product->stock.'</td>'
	        .'  <td class="px-4 py-3">'
	        .'    <!--div class="flex items-center space-x-4 text-sm">'
	        .'      <a href="'.base_url().'sisvent/store/inventory/edit/'.$product->idProduct.'" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">'
	        .'        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">'
	        .'          <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>'
	        .'        </svg>'
	        .'      </a-->';
	        if($store != -1)
	        {
		        $html .= '      <a href="'.base_url().'sisvent/store/inventory/delete/'.$product->idStore.'/'.$product->idProduct.'" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this)" aria-label="Delete">'
		        .'        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">'
		        .'          <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>'
		        .'        </svg>'
		        .'      </a>'
		        .'    </div>'
		        .'  </td>'
		        .'</tr>';
	    	}
	    }

	    echo ($html);

	}

	public function edit($store_id){
		$data =array( 
			'products' => $this->inventory_model->getCurrentInventory($store_id),
			'store' => $this->stores_model->getStore($store_id)
		);
		//print_r($data);
		$this->load->view("sisvent/store/inventory/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$store = $this->input->post("store");
		$products = $this->input->post("refs");
		$quantities = $this->input->post("quantities");
		
		if($products && count($products) > 0)
		{
			for ($i=0; $i < count($products); $i++) { 
				
				$inve = $this->inventory_model->getStoreProduct($store,$products[$i]);

				if(empty($inve))
				{
					$data  = array(
						'idStore' => $store, 
						'idProduct' => $products[$i],
						'stock' => $quantities[$i]
					);
					$this->inventory_model->save($data);
				}else{
					$data  = array(
						'stock' => $quantities[$i]
					);
					$this->inventory_model->update($store,$products[$i],$data);
				}
			}
			redirect(base_url()."sisvent/store/inventory");
		}
		else{
			$data  = array(
				'products' => $this->inventory_model->getCurrentInventory($store),
				'store' => $this->stores_model->getStore($store)
			);
			$this->session->set_flashdata("error","Debe tener al menos un producto");
			$this->load->view("sisvent/store/inventory/edit",$data);
			//$this->add();
		}
		
	}

	public function delete($store_id,$product){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$this->inventory_model->remove($store_id,$product);
		//redirect(base_url()."sisvent/business/stores");
		echo base_url()."sisvent/store/inventory";
	}

	public function load()
	{
		$data  = array(
			'stores' => $this->stores_model->getStores(),
		);
		$this->load->view("sisvent/store/inventory/loadinventory",$data);
	}
	
	public function upload()
    {
    	$this->outh_model->CSRFVerify();
	
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

    	set_time_limit(0);
		$store = $this->input->post("store");
    	
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
					$product = test_input($columns[0]);
					$quantities = test_input($columns[1]);
					
					//$query = "INSERT INTO `users`(`user_id`, `name`, `email`, `phone`) VALUES ('".$id."','".($name)."','".$email."','".($cellphone)."')";
					
					$inve = $this->inventory_model->getStoreProduct($store,$product);

					$uc++;
					if(empty($inve))
					{
						$data  = array(
							'idStore' => $store, 
							'idProduct' => $product,
							'stock' => $quantities
						);
						$this->inventory_model->save($data);
					}else{
						$data  = array(
							'stock' => $quantities
						);
						$this->inventory_model->update($store,$product,$data);
						$nosaved .= $product.": ".$quantities." Sumado al inventario<br>";
					}

				}
				//print_r("Usuarios ")
				$error = array('stores' => $this->stores_model->getStores(),
								'success_msg' => 'Productos registrados: '.$uc.'/'.$size,
								'info_msg' => $nosaved);
				$this->load->view('sisvent/store/inventory/loadinventory', $error);
            }else{
                $error = array('stores' => $this->stores_model->getStores(),
                				'error_msg' => 'Invalid file, please select only CSV file.:)');
				$this->load->view('sisvent/store/inventory/loadinventory', $error);
            }
        }else{
            $error = array('stores' => $this->stores_model->getStores(),
            				'error_msg' => 'Error on file upload, please try again.:)');
			$this->load->view('sisvent/store/inventory/loadinventory', $error);
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
	
}