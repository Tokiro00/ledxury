<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Catalogue extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
        //$this->load->library('form_validation');
        $this->load->model("products_model");
        $this->load->model("inventory_model");
        $this->load->model("vendors_model");
        $this->load->model("stores_model");
        $this->load->model("clients_model");
        $this->load->model("catalogues_model");
    }

	public function index()
	{
		$this->backend_lib->control();
		$page = $this->input->get('p');
		$limit = 50;
		if(!$page)
			$page = 1;

		$total = $this->catalogues_model->getTotal();
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$data  = array(
			
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
			'catalogues' => $this->catalogues_model->getCatalogues($page, $limit)
		);

		$this->load->view("sisvent/store/catalogue/index",$data);
		
	}

	public function add(){
		
		$data  = array(
			'stores' => $this->stores_model->getStores(), 
			'vendors' => $this->vendors_model->getVendors(), 
			'clients' => $this->clients_model->getClients(), 
			'families' => $this->products_model->getFamilies(),
		);
		$this->load->view("sisvent/store/catalogue/add",$data);
	}

	public function store(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
			date_default_timezone_set("America/Bogota");

		$page = $this->input->get('p');
		
		$limit = 50;
		if(!$page)
			$page = 1;
		

		$ptotal = $this->catalogues_model->getTotal();
		$last       = ceil( $ptotal / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;


		$name = $this->input->post("name");
		$vendor = $this->input->post("vendor");
		$client = $this->input->post("client");
		$store = $this->input->post("store");
		$comments = $this->input->post("comments");
        /*if(in_array($this->session->userdata('user_data')['role'], [1])):
			$iva = $this->input->post("iva");
        endif;*/

		$products = $this->input->post("refs");
				
		if($products && count($products) > 0)
		{

			$data  = array(
				'name' => $name,
				'clientId' => isset($client) ? $client : null,
				'vendorId' => $vendor,
				'storeId' => $store,
				'date' => date('Y-m-d H:i:s'),
				'comments' => $comments,
			);

			//print_r($data);

			if ($this->catalogues_model->save($data)) {
				$idCatalogue = $this->catalogues_model->lastID();
				$this->_save_detail($products,$idCatalogue);

				redirect(base_url()."sisvent/store/catalogue".createFullParamsLinks($page));
			}
			else{
				$data  = array(
					'stores' => $this->stores_model->getStores(), 
					'vendors' => $this->vendors_model->getVendors(), 
					'clients' => $this->clients_model->getClients(), 
					'families' => $this->products_model->getFamilies(),
				);
				$this->session->set_flashdata("error","No se pudo guardar la información");
				$this->load->view("sisvent/store/catalogue/add",$data);
			}
			
		}
		else{
			$data  = array(
				'stores' => $this->stores_model->getStores(), 
				'vendors' => $this->vendors_model->getVendors(), 
				'clients' => $this->clients_model->getClients(), 
				'families' => $this->products_model->getFamilies(),
			);
			$this->session->set_flashdata("error","Debe ingresar al menos un producto");
			$this->load->view("sisvent/store/catalogue/add",$data);
			//$this->add();
		}
		
	}

	function _save_detail($products,$idCatalogue){
		
		//echo "<script>console.log( 'per: ".empty($per_packages)." ' );</script>";
		for ($i=0; $i < count($products); $i++) { 
			//echo "<script>console.log( 'Debug Objects: ".$i." = ".$products[$i]." + " .implode(" -- ", $per_packages)." + " . (array_search($products[$i], $per_packages) === FALSE)." + " .array_search($products[$i], $per_packages). "' );</script>";

			$data  = array(
				'catalogueId' =>$idCatalogue,
				'productId' =>$products[$i]				
			);
			//echo "<pre>";
			//print_r($data);
			//echo "</pre>";
			$this->catalogues_model->save_detail($data);
			//$this->updateProduct($products[$i],$quantities[$i]);
		}
	}

	public function getFamilies(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$valor = $this->input->post("valor");
		//$products = $this->inventory_model->getStoreProducts($valor,$this->input->post("orstr"));
		$families = $this->products_model->getFamiliesByWord($valor);
		
		echo json_encode($families);
	}

	public function getFamilyProducts(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$productos = $this->inventory_model->getFamilyProducts($this->input->post("orstr"),$this->input->post("family"));
		
		echo json_encode($productos);
	}

	public function getSections(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$valor = $this->input->post("valor");
		//$products = $this->inventory_model->getStoreProducts($valor,$this->input->post("orstr"));
		$sections = $this->products_model->getSectionsByWord($valor);
		
		echo json_encode($sections);
	}

	public function getSectionProducts(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$productos = $this->inventory_model->getSectionProducts($this->input->post("orstr"),$this->input->post("section"));
		
		echo json_encode($productos);
	}

	public function viewcat($idCatalogue)
	{
		$page = $this->input->get('p');
		
		$limit = 48;
		if(!$page)
			$page = 1;
		
		$catalogue = $this->catalogues_model->getCatalogue($idCatalogue);

		$total = $this->catalogues_model->getDetailsCount($idCatalogue);
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$products = $this->catalogues_model->getDetails($idCatalogue,$page,$limit);
		foreach ($products as $key => $product) {
			$productoinv = $this->inventory_model->getStoreProduct($catalogue->storeId,$product->idProduct);
		
			$product->stock = empty($productoinv) ? 0 : $productoinv->stock;
			$product->datasheetvalues = $this->products_model->getProductsLabelsValues($product->idProduct,$product->datasheet);
		}
		$data  = array(
			'catalogue' => $catalogue, 
			'products' => $products,
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
			'ps' => '',
		);
		$this->load->view("sisvent/store/catalogue/viewcatalogue",$data);
	}

	public function view($store)
	{
		$page = $this->input->get('p');
		
		$limit = 48;
		if(!$page)
			$page = 1;
		
		$total = $this->inventory_model->getCurrentInventoryCount($store);
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		$products = $this->inventory_model->getCurrentInventory($store,/*$page,$limit*/);
		foreach ($products as $key => $product) {
			$product->datasheetvalues = $this->products_model->getProductsLabelsValues($product->idProduct,$product->datasheet);
		}
		$data  = array(
			'store' => $this->stores_model->getStore($store), 
			'products' => $products,
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
			'ps' => '',
		);
		$this->load->view("sisvent/store/catalogue/viewdatasheet",$data);
	}

	public function delete($idCatalogue){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST

		$this->catalogues_model->remove($idCatalogue);
	    $this->logs_model->logMessage("info","Usuario ".$this->session->userdata('user_data')['uname']." ha eliminado catálogo ".$idCatalogue);
	//redirect(base_url()."sisvent/business/clients");
		echo base_url()."sisvent/store/catalogue";
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
		$this->load->view("sisvent/store/catalogue/viewdatasheet",$data);
		
	}

	public function download($store){

		//https://www.pakainfo.com/codeigniter-3-pdf-generate-tutorial-example/
		//composer require mpdf/mpdf
		$live_mpdf = new \Mpdf\Mpdf();
		//$live_mpdf->showImageErrors = true;
		$live_mpdf->curlAllowUnsafeSslRequests = true;
		$total = $this->inventory_model->getCurrentInventoryCount($store);

		$products = $this->inventory_model->getCurrentInventory($store);
		$data_store = $this->stores_model->getStore($store);
		$data  = array(
			'store' => $data_store, 
			'products' => $products,
		);
		//$html_content = $this->load->view("sisvent/store/catalogue/view",$data, true);
	  	//$live_mpdf->WriteHTML($html_content);
	    ////$live_mpdf->Output(); // simple run and opens in browser
	    ////$live_mpdf->Output("catalogo_".strtolower(preg_replace('/\s*/', '_', iconv('UTF-8', 'US-ASCII//TRANSLIT',$data_store->name))).".pdf",'D'); // it //CodeIgniter downloads the file into the main dynamic system, with give your file name
	    //$live_mpdf->Output("catalogo.pdf",'D'); // it CodeIgniter downloads the file into the main dynamic system, with give your file name

	    $all_html = $this->load->view('sisvent/store/catalogue/pdfview',$data, true); //CodeIgniter view file name
        //print_r($all_html);
        $live_mpdf->WriteHTML($all_html);
        //$live_mpdf->Output(); // simple run and opens in browser
        $live_mpdf->Output("catalogo_".$store.".pdf",'D'); // it CodeIgniter downloads the file into the main dynamic system, with give your file name
   }

   public function viewpdf($store){

		//https://www.pakainfo.com/codeigniter-3-pdf-generate-tutorial-example/
		//composer require mpdf/mpdf
		$live_mpdf = new \Mpdf\Mpdf();
		$total = $this->inventory_model->getCurrentInventoryCount($store);

		$products = $this->inventory_model->getCurrentInventory($store);
		foreach ($products as $key => $product) {
			$product->datasheetvalues = $this->products_model->getProductsLabelsValues($product->idProduct,$product->datasheet);
		}

		$data_store = $this->stores_model->getStore($store);
		$data  = array(
			'store' => $data_store, 
			'products' => $products,
		);
		//$html_content = $this->load->view("sisvent/store/catalogue/view",$data, true);
	  	//$live_mpdf->WriteHTML($html_content);
	    ////$live_mpdf->Output(); // simple run and opens in browser
	    ////$live_mpdf->Output("catalogo_".strtolower(preg_replace('/\s*/', '_', iconv('UTF-8', 'US-ASCII//TRANSLIT',$data_store->name))).".pdf",'D'); // it //CodeIgniter downloads the file into the main dynamic system, with give your file name
	    //$live_mpdf->Output("catalogo.pdf",'D'); // it CodeIgniter downloads the file into the main dynamic system, with give your file name

	    //$this->load->view('sisvent/store/catalogue/pdfview',$data); //CodeIgniter view file name
	    $all_html = $this->load->view('sisvent/store/catalogue/pdfview',$data, true); //CodeIgniter view file name
        print_r($all_html);
        
    }

    public function viewdatasheet($store){

    	$page = $this->input->get('p');
		
		$limit = 48;
		if(!$page)
			$page = 1;
		
		$total = $this->inventory_model->getCurrentInventoryCount($store);
		$last       = ceil( $total / $limit );

		if($page > $last)
			$page = $last;

		if($page <= 0)
			$page = 1;

		//https://www.pakainfo.com/codeigniter-3-pdf-generate-tutorial-example/
		//composer require mpdf/mpdf
		$live_mpdf = new \Mpdf\Mpdf();
		$total = $this->inventory_model->getCurrentInventoryCount($store);

		$products = $this->inventory_model->getCurrentInventory($store);
		foreach ($products as $key => $product) {
			$product->datasheetvalues = $this->products_model->getProductsLabelsValues($product->idProduct,$product->datasheet);
		}
		$data_store = $this->stores_model->getStore($store);
		$data  = array(
			'store' => $data_store, 
			'products' => $products,
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
		);
		//$html_content = $this->load->view("sisvent/store/catalogue/view",$data, true);
	  	//$live_mpdf->WriteHTML($html_content);
	    ////$live_mpdf->Output(); // simple run and opens in browser
	    ////$live_mpdf->Output("catalogo_".strtolower(preg_replace('/\s*/', '_', iconv('UTF-8', 'US-ASCII//TRANSLIT',$data_store->name))).".pdf",'D'); // it //CodeIgniter downloads the file into the main dynamic system, with give your file name
	    //$live_mpdf->Output("catalogo.pdf",'D'); // it CodeIgniter downloads the file into the main dynamic system, with give your file name

	    //$this->load->view('sisvent/store/catalogue/pdfview',$data); //CodeIgniter view file name
	    $all_html = $this->load->view('sisvent/store/catalogue/viewdatasheet',$data, true); //CodeIgniter view file name
        print_r($all_html);
        
    }
}