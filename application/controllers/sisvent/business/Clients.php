<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
			'vendors' => $this->vendors_model->getVendors(),
			'next_fid' => $this->clients_model->getHighestClientFid()->next_fid
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
		$city = $this->input->post("city");
		$state = $this->input->post("state");
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
				'city' => $city,
				'state' => $state,
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
		$blacklisted = $this->input->post("blacklisted");
		$city = $this->input->post("city");
		$state = $this->input->post("state");
		$is_new = $this->input->post("is_new");
		$can_bill = $this->input->post("can_bill");
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
				'blacklisted' => $blacklisted == "on",
				'vendor' => $vendor,
				'city' => $city,
				'state' => $state,
				'is_new' => $is_new == "on",
				'can_bill' => $can_bill == "on",
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

	public function duplicate($client_id){
		$this->backend_lib->control([1]);

		$limit = 50;

		$page = $this->input->get('p');

		if(!$page)
			$page = 1;

		$budget = $this->clients_model->getClient($client_id);
		
		$data =array( 
			'client' => $this->clients_model->getClient($client_id), 
			'vendors' => $this->vendors_model->getVendors(),
			'page' => $page,
			'next_fid' => $this->clients_model->getHighestClientFid()->next_fid
		);

			
			$this->load->view("sisvent/business/clients/duplicate",$data);
		
		
	}

	public function blacklisted($client_id){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$data  = array(
				'blacklisted' => 1,
			);

		$this->clients_model->update($client_id,$data);
		//redirect(base_url()."sisvent/business/clients");
		echo base_url()."sisvent/business/clients";
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

	public function createExcel($client_id) {

		$this->load->helper("file");
		
		//$client_id = $this->input->post("client");
		
		/*$client = $this->clients_model->getClient($client_id);

		echo ($from)."<br>";
		echo strtotime($from)."<br>";
		echo date('Y-m-d H:i:s',strtotime($from))."<br>";
		echo date('Y-m-d H:i:s',strtotime($until))."<br>";
		echo $this->db->last_query()."<br>";

		foreach ($invoices as $val){
       		echo $val->idInvoice."  ".$val->date."<br>";
        } */

		$dat = uniqid('MAMClients', true);

		$fileName = 'CLI-'.$client_id.'.xlsx';  
		//$employeeData = $this->EmployeeModel->employeeList();
		$client = $this->clients_model->getClient($client_id);

		$spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
       	$sheet->setCellValue('A1', 'Código'); 
		$sheet->setCellValue('B1', 'Código para contabilidad');
		$sheet->setCellValue('C1', 'NIF');
		$sheet->setCellValue('D1', 'Nombre fiscal');
		$sheet->setCellValue('E1', 'Nombre comercial');
		$sheet->setCellValue('F1', 'Domicilio');
		$sheet->setCellValue('G1', 'Población');
		$sheet->setCellValue('H1', 'Código postal');
		$sheet->setCellValue('I1', 'Provincia');
		$sheet->setCellValue('J1', 'País');
		$sheet->setCellValue('K1', 'Teléfono');
		$sheet->setCellValue('L1', 'Fax');
		$sheet->setCellValue('M1', 'Móvil');
		$sheet->setCellValue('N1', 'Persona de contacto');
		$sheet->setCellValue('O1', 'Agente comercial');
		$sheet->setCellValue('P1', 'Banco');
		$sheet->setCellValue('Q1', 'Entidad');
		$sheet->setCellValue('R1', 'Oficina');
		$sheet->setCellValue('S1', 'Dígito de control');
		$sheet->setCellValue('T1', 'Cuenta');
		$sheet->setCellValue('U1', 'Forma de pago');
		$sheet->setCellValue('V1', '% Financiación');
		$sheet->setCellValue('W1', '% Pronto pago');
		$sheet->setCellValue('X1', 'Tarifa');
		$sheet->setCellValue('Y1', 'Día de pago 1');
		$sheet->setCellValue('Z1', 'Día de pago 2');
		$sheet->setCellValue('AA1', 'Día de pago 3');
		$sheet->setCellValue('AB1', 'Tipo de cliente');
		$sheet->setCellValue('AC1', 'Descuento fijo 1');
		$sheet->setCellValue('AD1', 'Descuento fijo 2');
		$sheet->setCellValue('AE1', 'Descuento fijo 3');
		$sheet->setCellValue('AF1', 'Tarifa especial');
		$sheet->setCellValue('AG1', 'Código de proveedor');
		$sheet->setCellValue('AH1', 'Actividades');
		$sheet->setCellValue('AI1', 'Tipo de portes');
		$sheet->setCellValue('AJ1', 'Texto de portes');
		$sheet->setCellValue('AK1', 'Aplicarlo al cliente');
		$sheet->setCellValue('AL1', 'Tipo de IVA del cliente');
		$sheet->setCellValue('AM1', 'Recargo de equivalencia');
		$sheet->setCellValue('AN1', 'Fecha de alta');
		$sheet->setCellValue('AO1', 'Fecha de nacimiento');
		$sheet->setCellValue('AP1', 'E-mail');
		$sheet->setCellValue('AQ1', 'Dirección web 60 A');
		$sheet->setCellValue('AR1', 'Cuenta Skype 60 A');
		$sheet->setCellValue('AS1', 'Mensaje emergente 50 A');
		$sheet->setCellValue('AT1', 'Observaciones 255 A');
		$sheet->setCellValue('AU1', 'Horario 30 A');
		$sheet->setCellValue('AV1', 'Vacaciones desde 5 A');
		$sheet->setCellValue('AW1', 'Vacaciones hasta 5 A');
		$sheet->setCellValue('AX1', 'Crear recibos al facturar');
		$sheet->setCellValue('AY1', 'No vender');
		$sheet->setCellValue('AZ1', 'No facturar');
		$sheet->setCellValue('BA1', 'No imprimir');
		$sheet->setCellValue('BB1', 'Moneda de facturación');
		$sheet->setCellValue('BC1', 'Tipo de documento predeterminado');
		$sheet->setCellValue('BD1', 'Domicilio del banco');
		$sheet->setCellValue('BE1', 'Población del banco');
		$sheet->setCellValue('BF1', 'IBAN del banco');
		$sheet->setCellValue('BG1', 'SWIFT del banco');
		$sheet->setCellValue('BH1', 'Concepto de facturación 1');
		$sheet->setCellValue('BI1', 'Concepto de facturación 2');
		$sheet->setCellValue('BJ1', 'Concepto de facturación 3');
		$sheet->setCellValue('BK1', 'Concepto de facturación 4');
		$sheet->setCellValue('BL1', 'Concepto de facturación 5');
		$sheet->setCellValue('BM1', 'Importe concepto facturación 1');
		$sheet->setCellValue('BN1', 'Importe concepto facturación 2');
		$sheet->setCellValue('BO1', 'Importe concepto facturación 3');
		$sheet->setCellValue('BP1', 'Importe concepto facturación 4');
		$sheet->setCellValue('BQ1', 'Importe concepto facturación 5');
		$sheet->setCellValue('BR1', 'Ruta');
		$sheet->setCellValue('BS1', 'Teléfono de contacto');
		$sheet->setCellValue('BT1', 'Código usuario web');
		$sheet->setCellValue('BU1', 'Clave usuario web');
		$sheet->setCellValue('BV1', 'Subir a Internet');       
		$sheet->setCellValue('CZ1', 'Identificación Fiscal');  

        $rows = 2;

    	//echo $val->idInvoice."  ".$val->date." ".$val->clientFId." ".$val->client_name."<br>";
   		$sheet->setCellValue('A' . $rows, $client->f_id);
		$sheet->setCellValue('C' . $rows, $client->idNum);
		$sheet->setCellValue('D' . $rows, $client->name);
		$sheet->setCellValue('E' . $rows, $client->name);
		$sheet->setCellValue('F' . $rows, $client->address);
		$sheet->setCellValue('G' . $rows, $client->state);
		$sheet->setCellValue('I' . $rows, $client->city);
		$sheet->setCellValue('K' . $rows, $client->phone);
		$sheet->setCellValue('M' . $rows, $client->cellphone);
		$sheet->setCellValue('O' . $rows, $client->userFId);
		switch ($client->rate) {
        	case 1:
        		$sheet->setCellValue('X' . $rows, 4);
        		break;
        	case 2:
        		$sheet->setCellValue('X' . $rows, 1);
        		break;
        	case 3:
        		$sheet->setCellValue('X' . $rows, 3);
        		break;
        	case 4:
        		$sheet->setCellValue('X' . $rows, 2);
        		break;
        	default:
        		# code...
        		$sheet->setCellValue('X' . $rows, 4);
        		break;
        }
		$sheet->setCellValue('AP' . $rows, $client->email);    
		$sheet->setCellValue('CZ' . $rows, 1);
/*
		<option value="1" <?php echo set_select("rate",1);?>>Precio</option> 4
                              <option value="2" <?php echo set_select("rate",2);?>>Precio Base</option> 1
                              <option value="3" <?php echo set_select("rate",3);?>>Precio Escala</option> 3
                              <option value="4" <?php echo set_select("rate",4);?>>Precio Distribución</option> 2
*/

        if (!is_dir('./public/cli/')) {
			//print_r("<br> Creando directorio ".'./public/dist/images/products/'.'pf'.substr( $this->session->productdata('product_data')['product_name'], 0,2).$this->session->productdata('product_data')['product_uname']);
        	mkdir('./public/cli/', 0777, true);
    	}
    	
    	delete_files('./public/cli/');

        $writer = new Xlsx($spreadsheet);
		$writer->save("public/cli/".$fileName);

		//$data  = array(
		//		'cli' => "public/cli/".$fileName,
		//	);

		//echo json_encode($data);
		header("Content-Type: application/vnd.ms-excel");
        redirect(base_url()."/public/cli/".$fileName); 
    }
	
}