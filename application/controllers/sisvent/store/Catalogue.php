<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Catalogue extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
        //$this->load->library('form_validation');
        $this->load->model("products_model");
        $this->load->model("inventory_model");
        $this->load->model("stores_model");
    }

	public function index()
	{
		$this->load->view("sisvent/store/catalogue/index");
		
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

		$products = $this->inventory_model->getCurrentInventory($store,$page,$limit);
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