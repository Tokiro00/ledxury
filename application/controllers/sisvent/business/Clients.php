<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clients extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control();
		$this->load->helper('file');
        $this->load->model("clients_model");
        $this->load->model("vendors_model");
    }

	public function index()
	{
		$page = $this->input->get('p');
		
		$limit = 50;
		if(!$page)
			$page = 1;
		
		$total = $this->clients_model->clientCount(true);
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
			'clients' => $this->clients_model->getClientsPag($page, $limit), 
		);
		$this->load->view("sisvent/business/clients/list",$data);
		
	}

	public function search($term)
	{
		$term = str_replace("%20", " ", $term);
	
		$page = $this->input->get('p');
		
		$limit = 50;
		if(!$page)
			$page = 1;
		
		$total = $this->clients_model->getTotalSearch($term);
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
			'clients' => $this->clients_model->getClientsByWord($term, $page, $limit), 
		);
		$this->load->view("sisvent/business/clients/list",$data);
		
	}

	public function add(){

		$data =array( 
			'vendors' => $this->vendors_model->getVendors()
		);
		$this->load->view("sisvent/business/clients/add", $data);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$client_id = $this->input->post("client_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$f_id = $this->input->post("f_id");
		$phone = $this->input->post("phone");
		$cellphone = $this->input->post("cellphone");
		$address = $this->input->post("address");
		$vendor = $this->input->post("vendor");
		$rate = $this->input->post("rate");
		$retail = $this->input->post("retail");
		$maximum_debt = $this->input->post("maximum_debt");

		if(!$maximum_debt)
			$maximum_debt = 10000000;

		$this->form_validation->set_rules("client_id","Cédula/NIT","required|is_unique[clients.idNum]");
		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("email","Email","valid_email");
		$this->form_validation->set_rules("phone","Teléfono","numeric");
		$this->form_validation->set_rules("cellphone","Celular","numeric");

		if ($this->form_validation->run()) {
			$data  = array(
				'idNum' => $client_id, 
				'name' => $name,
				'email' => $email,
				'f_id' => $f_id,
				'phone' => $phone,
				'cellphone' => $cellphone,
				'address' => $address,
				'retail' => $retail == "on",
				'vendor' => $vendor,
				'maximum_debt' => $maximum_debt,
				'rate' => $rate
			);

			if ($this->clients_model->save($data)) {
				redirect(base_url()."sisvent/business/clients");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				$this->add();
				//redirect(base_url()."sisvent/business/clients/add");
			}
		}
		else{
			$this->add();
		}
	}

	public function edit($client_id){
		$this->backend_lib->control([1]);

		$page = $this->input->get('p');
		
		if(!$page)
			$page = 1;

		$data =array( 
			'client' => $this->clients_model->getClient($client_id), 
			'vendors' => $this->vendors_model->getVendors(),
			'page' => $page,
		);
		//print_r($data);
		$this->load->view("sisvent/business/clients/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$id = $this->input->post("id");
		$client_id = $this->input->post("client_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$f_id = $this->input->post("f_id");
		$phone = $this->input->post("phone");
		$cellphone = $this->input->post("cellphone");
		$rate = $this->input->post("rate");
		$address = $this->input->post("address");
		$vendor = $this->input->post("vendor");
		$retail = $this->input->post("retail");
		$maximum_debt = $this->input->post("maximum_debt");

		if(!$maximum_debt)
			$maximum_debt = 10000000;

		$page = $this->input->get('p');
		
		if(!$page)
			$page = 1;

		//$this->form_validation->set_rules("client_id","Cédula/NIT","required|is_unique[clients.idNum]");
		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("email","Email","valid_email");
		$this->form_validation->set_rules("phone","Teléfono","numeric");
		$this->form_validation->set_rules("cellphone","Celular","numeric");
		
		if ($this->form_validation->run()) {
			
			$data  = array(
				'idNum' => $client_id, 
				'name' => $name,
				'email' => $email,
				'f_id' => $f_id,
				'phone' => $phone,
				'cellphone' => $cellphone,
				'address' => $address,
				'retail' => $retail == "on",
				'vendor' => $vendor,
				'maximum_debt' => $maximum_debt,
				'rate' => $rate
			);

			if ($this->clients_model->update($id,$data)) {
				redirect(base_url()."sisvent/business/clients".createFullParamsLinks($page));
			}
			else{
				$this->session->set_flashdata("error","No se pudo actualizar la información");
				//redirect(base_url()."sisvent/business/clients/edit/".$client_id);
				//$this->edit($id);
				$data =array( 
					'client' => $this->clients_model->getClient($client_id), 
					'vendors' => $this->vendors_model->getVendors(),
					'page' => $page,
				);
				//print_r($data);
				$this->load->view("sisvent/business/clients/edit",$data);
			}
		}
		else{
			//$this->edit($id);
			$data =array( 
				'client' => $this->clients_model->getClient($client_id), 
				'vendors' => $this->vendors_model->getVendors(),
				'page' => $page,
			);
			//print_r($data);
			$this->load->view("sisvent/business/clients/edit",$data);
		}
	}

	public function view(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$client_id = $this->input->post("id");
		$data  = array(
			'client' => $this->clients_model->getClient($client_id),
		);
		$this->load->view("sisvent/business/clients/view",$data);
		//echo "<h1>".$client_id."</h1>";
	}


	public function delete($client_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$this->clients_model->remove($client_id);
		//redirect(base_url()."sisvent/business/clients");
		echo base_url()."sisvent/business/clients";
	}

	public function load(){

		$this->load->view("sisvent/business/clients/loadclients");
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
					$vendor = test_input($columns[0]);
					$client_id = test_input($columns[1]);
					$name = test_input($columns[2]);
					$address = test_input($columns[3]);
					$phone = test_input($columns[4]);
					$cellphone = test_input($columns[5]);
					$email = test_input($columns[6]);
					//$query = "INSERT INTO `users`(`user_id`, `name`, `email`, `phone`) VALUES ('".$id."','".($name)."','".$email."','".($cellphone)."')";
					
					if(!empty($name))
					{
						$data  = array(
							'idNum' => $client_id, 
							'name' => $name,
							'email' => $email,
							'phone' => $phone,
							'cellphone' => $cellphone,
							'address' => $address,
							'vendor' => $vendor,
							'rate' => 0
						);

						if ($this->clients_model->save($data)) {
							$uc++;
						}else
						{
							$nosaved .= $id." No guardó<br>";
						}
					}else
					{
						$nosaved .= $id." Sin nombre<br>";
					}
				}
				//print_r("Usuarios ")
				$error = array('success_msg' => 'Usuarios registrados: '.$uc.'/'.$size,'u_permissions' => $this->permissions,
								'info_msg' => $nosaved);
				$this->load->view('sisvent/business/clients/loadclients', $error);
            }else{
                $error = array('error_msg' => 'Invalid file, please select only CSV file.:)','u_permissions' => $this->permissions);
				$this->load->view('sisvent/business/clients/loadclients', $error);
            }
        }else{
            $error = array('error_msg' => 'Error on file upload, please try again.:)','u_permissions' => $this->permissions);
			$this->load->view('sisvent/business/clients/loadclients', $error);
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