<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Providers extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control([1]);
		$this->load->helper('file');
        $this->load->model("providers_model");
    }

	public function index()
	{
		$data  = array(
			'providers' => $this->providers_model->getProviders(), 
		);
		$this->load->view("sisvent/business/providers/list",$data);
		
	}

	public function add(){

		$this->load->view("sisvent/business/providers/add");
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$provider_id = $this->input->post("provider_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$phone = $this->input->post("phone");
		$address = $this->input->post("address");
		$vendor = $this->input->post("vendor");
		$rate = $this->input->post("rate");

		$this->form_validation->set_rules("provider_id","Cédula/NIT","required|is_unique[providers.idNum]");
		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("email","Email","valid_email");
		$this->form_validation->set_rules("phone","Teléfono","numeric");

		if ($this->form_validation->run()) {
			$data  = array(
				'idNum' => $provider_id, 
				'name' => $name,
				'email' => $email,
				'phone' => $phone,
				'address' => $address
			);

			if ($this->providers_model->save($data)) {
				redirect(base_url()."sisvent/business/providers");
			}
			else{
				$this->session->set_flashdata("error","No se pudo guardar la información");
				$this->add();
				//redirect(base_url()."sisvent/business/providers/add");
			}
		}
		else{
			$this->add();
		}
	}

	public function edit($provider_id){
		$data =array( 
			'provider' => $this->providers_model->getProvider($provider_id)
		);
		//print_r($data);
		$this->load->view("sisvent/business/providers/edit",$data);
	}

	public function update(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$id = $this->input->post("id");
		$provider_id = $this->input->post("provider_id");
		$name = $this->input->post("name");
		$email = $this->input->post("email");
		$phone = $this->input->post("phone");
		$address = $this->input->post("address");
		

		//$this->form_validation->set_rules("provider_id","Cédula/NIT","required|is_unique[providers.idNum]");
		$this->form_validation->set_rules("name","Nombre","required");
		$this->form_validation->set_rules("email","Email","valid_email");
		$this->form_validation->set_rules("phone","Teléfono","numeric");
		
		if ($this->form_validation->run()) {
			
			$data  = array(
				'idNum' => $provider_id, 
				'name' => $name,
				'email' => $email,
				'phone' => $phone,
				'address' => $address
			);

			if ($this->providers_model->update($id,$data)) {
				redirect(base_url()."sisvent/business/providers");
			}
			else{
				$this->session->set_flashdata("error","No se pudo actualizar la información");
				//redirect(base_url()."sisvent/business/providers/edit/".$provider_id);
				$this->edit($id);
			}
		}
		else{
			$this->edit($id);
		}
	}

	public function delete($provider_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$this->providers_model->remove($provider_id);
		//redirect(base_url()."sisvent/business/providers");
		echo base_url()."sisvent/business/providers";
	}

	public function load(){

		$this->load->view("sisvent/business/providers/loadproviders");
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
					$id = test_input($columns[0]);
					$name = test_input($columns[1]);
					$address = test_input($columns[3]);
					$phone = test_input($columns[4]);
					$email = test_input($columns[5]);
					//$query = "INSERT INTO `users`(`user_id`, `name`, `email`, `phone`) VALUES ('".$id."','".($name)."','".$email."','".($cellphone)."')";
					
					if(!empty($name))
					{
						$data  = array(
							'idNum' => $id, 
							'name' => $name,
							'email' => $email,
							'phone' => $phone,
							'address' => $address
						);

						if ($this->providers_model->save($data)) {
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
				$this->load->view('sisvent/business/providers/loadproviders', $error);
            }else{
                $error = array('error_msg' => 'Invalid file, please select only CSV file.:)','u_permissions' => $this->permissions);
				$this->load->view('sisvent/business/providers/loadproviders', $error);
            }
        }else{
            $error = array('error_msg' => 'Error on file upload, please try again.:)','u_permissions' => $this->permissions);
			$this->load->view('sisvent/business/providers/loadproviders', $error);
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