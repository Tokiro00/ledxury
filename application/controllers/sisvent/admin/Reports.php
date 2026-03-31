<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
	$this->backend_lib->controlModule('reportes_ventas');
        $this->load->model("vouchers_model");
        $this->load->model("invoices_model");
        $this->load->model("budgets_model");
        $this->load->model("payments_model");
        $this->load->model("vendors_model");
        $this->load->model("stores_model");
        $this->load->model("users_model");
        $this->load->model("clients_model");
        $this->load->model("inventory_model");
        $this->load->model("cashmovements_model");
        $this->load->model("expenserecords_model");
        $this->load->model("supplierbills_model");
        $this->load->model("advertising_model");
    }

	public function index()
	{

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
	   
		$salesbyvendor = $this->invoices_model->getStoreSalesByVendor(-1, date("Y"));

	    	if($this->session->userdata('user_data')['role'] != 1){
	    		$stores = $this->stores_model->getStore($user->store);
	    		$salesbystore = array();
			$idStore = $stores->idStore;
			$storesales = array_filter($salesbyvendor, function($v) use ($idStore) {
	                    return $v->storeId == $idStore;
	                });

	                if(!empty($storesales)) {
	                	
	                	array_push($salesbystore, ["store" => $stores->idStore, "storename" => $stores->name, "salesbyvendor" => array_values($storesales)]);
			}
	    		
	    	
	    	}else{
	    		$stores = $this->stores_model->getStores();
			$salesbystore = array();
			foreach ($stores as $str) {
				$idStore = $str->idStore;
				$storesales = array_filter($salesbyvendor, function($v) use ($idStore) {
		                    return $v->storeId == $idStore;
		                });

		                if(!empty($storesales)) {
		                	
		                	array_push($salesbystore, ["store" => $str->idStore, "storename" => $str->name, "salesbyvendor" => array_values($storesales)]);
				}
			}
	    	}                


		$data  = array(
			'vendors' => $this->vendors_model->getVendors('',$this->session->userdata('user_data')['role'] == 1),
			//'salesbyvendor' => $this->invoices_model->getStoreSalesByVendor(-1, date("Y")),
			'salesbystore' => $salesbystore
		);
		$this->load->view("sisvent/admin/reports/index",$data);
		
	}

	function getUnivFunc($storeId){
	    return create_function('$a','return $a["storeId"] == "' . $storeId . '";');
	}

	public function getUSerData()
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$user_id = $this->input->post("user");
		$year = $this->input->post("year");

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 

		$salesByMonth =  $this->invoices_model->getVendorSalesByMonth($user_id, $year);
		$goal_sales = $this->invoices_model->getVendorSalesYearGoal($user_id, $year);//[30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 30000000, 80000000, 80000000];
	    	$month_names = ['Enero','Febrero','Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

	    	if(empty($goal_sales))
	        {
	        	$goal_sales["m1"] = 30000000;
	        	$goal_sales["m2"] = 31000000;
	        	$goal_sales["m3"] = 30000000;
	        	$goal_sales["m4"] = 30000000;
	        	$goal_sales["m5"] = 30000000;
	        	$goal_sales["m6"] = 30000000;
	        	$goal_sales["m7"] = 30000000;
	        	$goal_sales["m8"] = 30000000;
	        	$goal_sales["m9"] = 30000000;
	        	$goal_sales["m10"] = 30000000;
	        	$goal_sales["m11"] = 80000000;
	        	$goal_sales["m12"] = 80000000;
	        }

	        $month_row = '<td class="px-4 py-3 text-xs whitespace-normal">
	                        Mes
	                      </td>';
	        $month_goal = '<td class="px-4 py-3 text-xs whitespace-normal">
	                        Ventas Objetivo
	                      </td>';
	        $month_achieved = '<td class="px-4 py-3 text-xs whitespace-normal">
	                        Ventas Reales
	                      </td>';

		$graph_data_g = array();
		$arr = array();
		//array_push($arr, ["type" => 'string', "label" => 'Mes']);
		array_push($arr, 'Mes');
		//array_push($arr, ["type" => 'number', "label" => 'Ventas Objetivo']);
		array_push($arr, 'Ventas Objetivo');
		array_push($arr, 'Ventas Reales');
		array_push($graph_data_g,$arr);
		foreach ($salesByMonth as $key => $value) {
		      $arr = array();
		      array_push($arr, $month_names[$value->month-1]);
		      array_push($arr, (int)$goal_sales["m".$value->month]);
		      array_push($arr, (int)$value->total);
		      array_push($graph_data_g,$arr);
		      $month_row .= '<td class="px-4 py-3">'.$month_names[$value->month-1].'</td>';
		      $month_goal .= '<td class="px-4 py-3">$'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $goal_sales["m".$value->month])), 2).'</td>';
		      $month_achieved .= '<td class="px-4 py-3">$'.number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $value->total)), 2).'</td>';
	    	}

	    	$table = '<tr class="text-gray-700">'.$month_row.'</tr><tr class="text-gray-700">'.$month_goal.'</tr><tr class="text-gray-700">'.$month_achieved.'</tr>';

		$salesbyvendor = $this->invoices_model->getStoreSalesByVendor(-1, $year);


		if($this->session->userdata('user_data')['role'] != 1){
	    		$stores = $this->stores_model->getStore($user->store);
	    		$salesbystore = array();
			$idStore = $stores->idStore;
			$storesales = array_filter($salesbyvendor, function($v) use ($idStore) {
	                    return $v->storeId == $idStore;
	                });

	                if(!empty($storesales)) {
	                	
	                	array_push($salesbystore, ["store" => $stores->idStore, "storename" => $stores->name, "salesbyvendor" => array_values($storesales)]);
			}	    	
	    	}else{
	    		$stores = $this->stores_model->getStores();
			$salesbystore = array();
			foreach ($stores as $str) {
				$idStore = $str->idStore;
				$storesales = array_filter($salesbyvendor, function($v) use ($idStore) {
		                    return $v->storeId == $idStore;
		                });

		                if(!empty($storesales)) {
		                	
		                	array_push($salesbystore, ["store" => $str->idStore, "storename" => $str->name, "salesbyvendor" => array_values($storesales)]);
				}
			}
	    	}


	    	$data = array(
	    		'chart' => $graph_data_g,
	    		'table' => $table,
			'salesbystore' => $salesbystore
	    	);
	    	echo json_encode($data);
	}

	public function daily()
	{

		$user = $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']); 
	   
		//$salesbyvendor = $this->invoices_model->getStoreSalesByVendor(-1, date("Y"));
		//$salesByDay =  $this->invoices_model->getSalesByDay(-1, '2023-03-01'/*date('Y-m-d')*/, '2023-03-28 23:59:59');
		//$salesByDay =  $this->invoices_model->getSalesByDay(-1, '2023-03-01', '2023-03-28 23:59:59');
		//echo $this->db->last_query()."<br>";
		$salesByDay =  $this->invoices_model->getSalesByDay(-1, date('Y-m-d'));
		$totalSalesByDay =  $this->invoices_model->getTotalSalesByDay(-1, date('Y-m-d'));
		//$totalSalesByDay =  $this->invoices_model->getTotalSalesByDay(-1, '2023-03-01', '2023-03-28 23:59:59');
		$budgetsByDay =  $this->budgets_model->getBudgetsByDay(-1, date('Y-m-d'));
		$advExpensesByDay =  $this->advertising_model->getAdvertisingAmountSinceUntil(date('Y-m-d'));
		//$advExpensesByDay =  $this->advertising_model->getAdvertisingAmountSinceUntil('2023-07-01', '2023-07-04');

		//echo "<pre>";
		//print_r($advExpensesByDay);
		//echo "</pre>";

		//echo $this->db->last_query()."<br>";
		/*echo "salesByDay<br>";
	    echo "<pre>";
		print_r($salesByDay);
		echo "</pre>";*/
		/*echo "totalSalesByDay<br>";
	    echo "<pre>";
		print_r($totalSalesByDay);
		echo "</pre>";*/

		$stores = $this->stores_model->getStores();
		$salesbystore = array();
		$budgetsbystore = array();
		$salesstorebtday = array();
		$totalsalesstorebtday = array();
		$totalsalesbyvendor = array();
		$budgetstorebtday = array();
		foreach ($stores as $str) {
			$idStore = $str->idStore;
			$storesales = array_filter($salesByDay, function($v) use ($idStore) {
                return $v->storeId == $idStore;
            });
		            //echo "**********************<br>";

            if(!empty($storesales)) {

            	/*	$groupBy = Arrays::groupBy($storesales, Functions::extract()->date);

						echo "<pre>";
				print_r($groupBy);               
						echo "</pre>";*/

						/*echo "========********========<br>";
						echo "<pre>";
				print_r(array_values($storesales));               
						echo "</pre>";*/

				$vendor_ids = $this->_array_column_multi($storesales, 'vendorId', 'vendorId');
				$vendor_names = $this->_array_column_multi($storesales, 'vendorId', 'vendor_name');
				
				foreach ($vendor_ids as $vendor) {
					$storevendorsales = array_filter($storesales, function($v) use ($vendor) {
		                return $v->vendorId == $vendor;
		            });

		            

		            /*echo "======================= ".$vendor."<br>";
						echo "<pre>";
				print_r(array_values($storevendorsales));               
						echo "</pre>";*/

					foreach ($storevendorsales as $vendorsales) {

						if(isset($salesstorebtday[$idStore])){
							if(isset($salesstorebtday[$idStore]["sales"][$vendorsales->date])){
								if(isset($salesstorebtday[$idStore]["sales"][$vendorsales->date][$vendorsales->vendorId])){
									$salesstorebtday[$idStore]["sales"][$vendorsales->date][$vendorsales->vendorId]->total += $vendorsales->total; 
								}else{
									$salesstorebtday[$idStore]["sales"][$vendorsales->date][$vendorsales->vendorId] = $vendorsales;
								}
							}else{
								$salesstorebtday[$idStore]["sales"][$vendorsales->date][$vendorsales->vendorId] = $vendorsales;
							}
						}else{
							$salesstorebtday[$idStore]["sales"][$vendorsales->date][$vendorsales->vendorId] = $vendorsales;
							$salesstorebtday[$idStore]["storename"] = $str->name;
							$salesstorebtday[$idStore]["vendor_ids"] = $vendor_names;
						}

					}
				}
						

				/*$final = array();

				array_walk_recursive($storesales, function($item, $key) use (&$final){
				    $final[$key] = isset($final[$key]) ?  $item + $final[$key] : $item;
				});*/

				/*$groupBy = array();

				foreach ($storesales as $key => $item) {
					if(!isset($groupBy[$item->date])) $groupBy[$item->date] = array();

					/*echo "----------<br>";
						echo "<pre>";
				print_r($item);               
						echo "</pre>";* /
				   array_push($groupBy[$item->date], $item);
				}

				ksort($groupBy, SORT_NUMERIC);*/

				//$vendor_ids = array_column($storesales, 'vendorId');
				//$vendor_names = array_column($storesales, 'vendor_name');
			    /*echo "<pre>";
				print_r($vendor_names);
				echo "</pre>";*/

				/*echo "<pre>";
				print_r($this->_array_column_multi($storesales, 'vendorId', 'vendor_name'));
				echo "</pre>";*/

				/*echo "Group By<br>";
				echo "<pre>";
				print_r($groupBy);               
				echo "</pre>";*/
            	
            	/*array_push($salesbystore, ["store" => $str->idStore, "storename" => $str->name, "vendor_ids" => $vendor_names, "salesbyday" => ($groupBy)]);*/
			}

			$storetotalsales = array_filter($totalSalesByDay, function($v) use ($idStore) {
                return $v->storeId == $idStore;
            });

            if(!empty($storetotalsales)) {

				$vendor_ids = $this->_array_column_multi($storetotalsales, 'vendorId', 'vendorId');
				$vendor_names = $this->_array_column_multi($storetotalsales, 'vendorId', 'vendor_name');
				
				foreach ($vendor_ids as $vendor) {
					$storevendortotalsales = array_filter($storetotalsales, function($v) use ($vendor) {
		                return $v->vendorId == $vendor;
		            });

					foreach ($storevendortotalsales as $vendortotalsales) {

						/*if(isset($totalsalesstorebtday[$idStore])){
							if(isset($totalsalesstorebtday[$idStore]["sales"][$vendortotalsales->date])){
								if(isset($totalsalesstorebtday[$idStore]["sales"][$vendortotalsales->date][$vendortotalsales->vendorId])){
									$totalsalesstorebtday[$idStore]["sales"][$vendortotalsales->date][$vendortotalsales->vendorId]->total += $vendortotalsales->total; 
								}else{
									$totalsalesstorebtday[$idStore]["sales"][$vendortotalsales->date][$vendortotalsales->vendorId] = $vendortotalsales;
								}
							}else{
								$totalsalesstorebtday[$idStore]["sales"][$vendortotalsales->date][$vendortotalsales->vendorId] = $vendortotalsales;
							}
						}else{*/
							$totalsalesstorebtday[$idStore]["store"] = $idStore;
							$totalsalesstorebtday[$idStore]["storename"] = $str->name;
							$totalsalesstorebtday[$idStore]["vendor_ids"] = $vendor_names;
							$totalsalesstorebtday[$idStore]["sales"][$vendortotalsales->vendorId] = $vendortotalsales;
						//}
							if(isset($totalsalesbyvendor[$vendortotalsales->vendorId])){
								$totalsalesbyvendor[$vendortotalsales->vendorId] += $vendortotalsales->total;
							}else{
								$totalsalesbyvendor[$vendortotalsales->vendorId] = ($vendortotalsales->total != null) ? $vendortotalsales->total : 0;
							}

					}
				}
						
			}
		        
			$storebudgets = array_filter($budgetsByDay, function($v) use ($idStore) {
                return $v->storeId == $idStore;
            });


            if(!empty($storebudgets)) {

				/*$budgetsGroupBy = array();

				foreach ($storebudgets as $key => $item) {
					if(!isset($budgetsGroupBy[$item->date])) $budgetsGroupBy[$item->date] = array();

				   array_push($budgetsGroupBy[$item->date], $item);
				}

				ksort($budgetsGroupBy, SORT_NUMERIC);

				$vendor_ids = $this->_array_column_multi($storebudgets, 'vendorId', 'vendor_name');
            	
            	array_push($budgetsbystore, ["store" => $str->idStore, "storename" => $str->name, "vendor_ids" => $vendor_names, "budgetsbyday" => ($budgetsGroupBy)]);*/
            	$vendor_ids = $this->_array_column_multi($storebudgets, 'vendorId', 'vendorId');
				$vendor_names = $this->_array_column_multi($storebudgets, 'vendorId', 'vendor_name');
				
				foreach ($vendor_ids as $vendor) {
					$storevendorbudgets = array_filter($storebudgets, function($v) use ($vendor) {
		                return $v->vendorId == $vendor;
		            });

		            

		            /*echo "======================= ".$vendor."<br>";
						echo "<pre>";
				print_r(array_values($storevendorbudgets));               
						echo "</pre>";*/

					foreach ($storevendorbudgets as $vendorbudgets) {

						if(isset($budgetstorebtday[$idStore])){
							if(isset($budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date])){
								if(isset($budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date][$vendorbudgets->vendorId])){
									$budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date][$vendorbudgets->vendorId]->total += $vendorbudgets->total; 
								}else{
									$budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date][$vendorbudgets->vendorId] = $vendorbudgets;
								}
							}else{
								$budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date][$vendorbudgets->vendorId] = $vendorbudgets;
							}
						}else{
							$budgetstorebtday[$idStore]["store"] = $idStore;
							$budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date][$vendorbudgets->vendorId] = $vendorbudgets;
							$budgetstorebtday[$idStore]["storename"] = $str->name;
							$budgetstorebtday[$idStore]["vendor_ids"] = $vendor_names;
							//$budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date][$vendorbudgets->vendorId]->totalSales = $totalsalesbyvendor[$vendortotalsales->vendorId];
						}

					}
				}
						
			}
		}

		    /*echo "*-*-*-*-*-*-*-*-*-*-*-*<br>";

				echo "Sales By Store By Day<br>";
				echo "<pre>";
				print_r($salesstorebtday);               
				echo "</pre>";*/

				/*echo "totalSalesByDay<br>";
	    echo "<pre>";
		print_r($totalsalesstorebtday);
		echo "</pre>";*/

		foreach ($advExpensesByDay as $advExpense) {
			if(array_key_exists($advExpense->vendor, $totalsalesbyvendor)){
				$advExpense->totalSales = $totalsalesbyvendor[$advExpense->vendor];
			}else{
				$advExpense->totalSales = 0;
			}
		}
	    	 
		$data  = array(
			'vendors' => $this->vendors_model->getVendors('',$this->session->userdata('user_data')['role'] == 1),
			'salesbystore' => $salesstorebtday,
			'totalsalesbystore' => $totalsalesstorebtday,
			'advExpensesByDay' => $advExpensesByDay,
			'budgetsbystore' => $budgetstorebtday
		);
		/*echo "<pre>";
		//print_r(json_encode($salesbystore));
		print_r($salesbystore);
		echo "</pre>";*/
		$this->load->view("sisvent/admin/reports/daily",$data);
		
	}

	function _array_column_multi ($array, $column, $columnval) {
	    $types = array_unique(array_column($array, $column));

	    $return = [];
	    foreach ($types as $type) {
	        foreach ($array as $key => $value) {
	            if ($type === $value->$column) {
	                //unset($value->$column);
	                $return[$type] = $value->$columnval;
	                //unset($array[$key]);
	            }
	        }
	    }
	    return $return;
	}

	public function getDailyData(){

		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$since = $this->input->post("since");
		$until = $this->input->post("until");

		$salesByDay =  $this->invoices_model->getSalesByDay(-1, $since, $until.' 23:59:59');
		$lastq = $this->db->last_query();
		$totalSalesByDay =  $this->invoices_model->getTotalSalesByDay(-1, $since, $until.' 23:59:59');
		$budgetsByDay =  $this->budgets_model->getBudgetsByDay(-1, $since, $until.' 23:59:59');
		$advExpensesByDay =  $this->advertising_model->getAdvertisingAmountSinceUntil($since, $until);


		/*$stores = $this->stores_model->getStores();
		$salesbystore = array();
		$budgetsbystore = array();
		foreach ($stores as $str) {
			$idStore = $str->idStore;
			$storesales = array_filter($salesByDay, function($v) use ($idStore) {
                return $v->storeId == $idStore;
            });

            if(!empty($storesales)) {

				$groupBy = array();

				foreach ($storesales as $key => $item) {
					if(!isset($groupBy[$item->date])) $groupBy[$item->date] = array();

				   array_push($groupBy[$item->date], $item);
				}

				ksort($groupBy, SORT_NUMERIC);

				$vendor_ids = $this->_array_column_multi($storesales, 'vendorId', 'vendor_name');
            	
            	array_push($salesbystore, ["store" => $str->idStore, "storename" => $str->name, "vendor_ids" => $vendor_ids, "salesbyday" => ($groupBy)]);
			}

			$storebudgets = array_filter($budgetsByDay, function($v) use ($idStore) {
                return $v->storeId == $idStore;
            });

            
            if(!empty($storebudgets)) {

				$budgetsGroupBy = array();

				foreach ($storebudgets as $key => $item) {
					if(!isset($budgetsGroupBy[$item->date])) $budgetsGroupBy[$item->date] = array();

				   array_push($budgetsGroupBy[$item->date], $item);
				}

				ksort($budgetsGroupBy, SORT_NUMERIC);

				$vendor_ids = $this->_array_column_multi($storebudgets, 'vendorId', 'vendor_name');
            	
            	array_push($budgetsbystore, ["store" => $str->idStore, "storename" => $str->name, "vendor_ids" => $vendor_ids, "budgetsbyday" => ($budgetsGroupBy)]);
			}
		}*/

		$stores = $this->stores_model->getStores();
		//$salesbystore = array();
		//$budgetsbystore = array();
		$salesstorebtday = array();
		$totalsalesstorebtday = array();
		$totalsalesbyvendor = array();
		$budgetstorebtday = array();
		foreach ($stores as $str) {
			$idStore = $str->idStore;
			$storesales = array_filter($salesByDay, function($v) use ($idStore) {
                return $v->storeId == $idStore;
            });

            if(!empty($storesales)) {

				$vendor_ids = $this->_array_column_multi($storesales, 'vendorId', 'vendorId');
				$vendor_names = $this->_array_column_multi($storesales, 'vendorId', 'vendor_name');
				
				foreach ($vendor_ids as $vendor) {
					$storevendorsales = array_filter($storesales, function($v) use ($vendor) {
		                return $v->vendorId == $vendor;
		            });

					foreach ($storevendorsales as $vendorsales) {

						if(isset($salesstorebtday[$idStore])){
							if(isset($salesstorebtday[$idStore]["sales"][$vendorsales->date])){
								if(isset($salesstorebtday[$idStore]["sales"][$vendorsales->date][$vendorsales->vendorId])){
									$salesstorebtday[$idStore]["sales"][$vendorsales->date][$vendorsales->vendorId]->total += $vendorsales->total; 
								}else{
									$salesstorebtday[$idStore]["sales"][$vendorsales->date][$vendorsales->vendorId] = $vendorsales;
								}
							}else{
								$salesstorebtday[$idStore]["sales"][$vendorsales->date][$vendorsales->vendorId] = $vendorsales;
							}
						}else{
							$salesstorebtday[$idStore]["store"] = $idStore;
							$salesstorebtday[$idStore]["storename"] = $str->name;
							$salesstorebtday[$idStore]["vendor_ids"] = $vendor_names;
							$salesstorebtday[$idStore]["sales"][$vendorsales->date][$vendorsales->vendorId] = $vendorsales;
						}

					}
				}
						
			}

			$storetotalsales = array_filter($totalSalesByDay, function($v) use ($idStore) {
                return $v->storeId == $idStore;
            });

            if(!empty($storetotalsales)) {

				$vendor_ids = $this->_array_column_multi($storetotalsales, 'vendorId', 'vendorId');
				$vendor_names = $this->_array_column_multi($storetotalsales, 'vendorId', 'vendor_name');
				
				foreach ($vendor_ids as $vendor) {
					$storevendortotalsales = array_filter($storetotalsales, function($v) use ($vendor) {
		                return $v->vendorId == $vendor;
		            });

					foreach ($storevendortotalsales as $vendortotalsales) {

						/*if(isset($totalsalesstorebtday[$idStore])){
							if(isset($totalsalesstorebtday[$idStore]["sales"][$vendortotalsales->date])){
								if(isset($totalsalesstorebtday[$idStore]["sales"][$vendortotalsales->date][$vendortotalsales->vendorId])){
									$totalsalesstorebtday[$idStore]["sales"][$vendortotalsales->date][$vendortotalsales->vendorId]->total += $vendortotalsales->total; 
								}else{
									$totalsalesstorebtday[$idStore]["sales"][$vendortotalsales->date][$vendortotalsales->vendorId] = $vendortotalsales;
								}
							}else{
								$totalsalesstorebtday[$idStore]["sales"][$vendortotalsales->date][$vendortotalsales->vendorId] = $vendortotalsales;
							}
						}else{*/
							$totalsalesstorebtday[$idStore]["store"] = $idStore;
							$totalsalesstorebtday[$idStore]["storename"] = $str->name;
							$totalsalesstorebtday[$idStore]["vendor_ids"] = $vendor_names;
							$totalsalesstorebtday[$idStore]["sales"][$vendortotalsales->vendorId] = $vendortotalsales;
						//}
							if(isset($totalsalesbyvendor[$vendortotalsales->vendorId])){
								$totalsalesbyvendor[$vendortotalsales->vendorId] += $vendortotalsales->total;
							}else{
								$totalsalesbyvendor[$vendortotalsales->vendorId] = ($vendortotalsales->total != null) ? $vendortotalsales->total : 0;
							}
					}
				}
						
			}
		        
			$storebudgets = array_filter($budgetsByDay, function($v) use ($idStore) {
                return $v->storeId == $idStore;
            });


            if(!empty($storebudgets)) {				
            	
            	$vendor_ids = $this->_array_column_multi($storebudgets, 'vendorId', 'vendorId');
				$vendor_names = $this->_array_column_multi($storebudgets, 'vendorId', 'vendor_name');
				
				foreach ($vendor_ids as $vendor) {
					$storevendorbudgets = array_filter($storebudgets, function($v) use ($vendor) {
		                return $v->vendorId == $vendor;
		            });


					foreach ($storevendorbudgets as $vendorbudgets) {

						if(isset($budgetstorebtday[$idStore])){
							if(isset($budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date])){
								if(isset($budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date][$vendorbudgets->vendorId])){
									$budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date][$vendorbudgets->vendorId]->total += $vendorbudgets->total; 
								}else{
									$budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date][$vendorbudgets->vendorId] = $vendorbudgets;
								}
							}else{
								$budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date][$vendorbudgets->vendorId] = $vendorbudgets;
							}
						}else{
							$budgetstorebtday[$idStore]["store"] = $idStore;
							$budgetstorebtday[$idStore]["storename"] = $str->name;
							$budgetstorebtday[$idStore]["vendor_ids"] = $vendor_names;
							$budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date][$vendorbudgets->vendorId] = $vendorbudgets;
							//$budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date][$vendorbudgets->vendorId]->totalSales = $totalsalesbyvendor[$vendortotalsales->vendorId];
						}

					}
				}
						
			}
		}

		foreach ($advExpensesByDay as $advExpense) {
			//$advExpense->totalSales = $totalsalesbyvendor[$advExpense->vendor];
			if(array_key_exists($advExpense->vendor, $totalsalesbyvendor)){
				$advExpense->totalSales = $totalsalesbyvendor[$advExpense->vendor];
			}else{
				$advExpense->totalSales = 0;
			}
		}

		$data  = array(
			'vendors' => $this->vendors_model->getVendors('',$this->session->userdata('user_data')['role'] == 1),
			'lastq' => $lastq,
			'salesByDay' => $salesByDay,
			'budgetsByDay' => $budgetsByDay,
			'salesbystore' => $salesstorebtday,
			'totalsalesbystore' => $totalsalesstorebtday,
			'advExpensesByDay' => $advExpensesByDay,
			'budgetsbystore' => $budgetstorebtday
		);
		
	    echo json_encode($data);
	}

	public function reportscallcenter()
	{
		$this->load->view("sisvent/admin/reports/callcenter");
	}

	public function createCheckDeliveryCSVData() {

   		//$invoices = $this->invoices_model->getInvoicesToCheckDelivery();
		
		//echo "<h2>SIASS</h2>";
		//echo "<br>";
		//echo $this->db->last_query();
		//echo "<br>";
		//echo "<pre>";
        //echo (count($invoices));
        //echo "</pre><br>";

		//echo "<pre>";
        //echo print_r($invoices);
        //echo "</pre><br>";

		/*
		$line = "";
		foreach ($invoices as $val){
        	$line .=
        	$val->client_name." - ".
			$val->idInvoice." - ".
			$val->client_idNum." - ".
			$val->store_name." - ".
			$val->client_phone." - ".
			$val->client_cellphone."<br>";
        } 

        echo "<pre>";
        echo $line;
        echo "</pre>";*/
		
		$this->load->helper("file");
		
		$filename = 'clientes_check_recibido_'.date('Ymd').'.csv'; 
	   header("Content-Description: File Transfer"); 
	   header("Content-Disposition: attachment; filename=$filename"); 
	   header("Content-Type: application/csv; ");       

        $header = array(
		"Nombre",
		"Nit",
		"Telefono",
		"Celular");

		$file = fopen('php://output', 'w');
   		fputcsv($file, $header);

   		$invoices = $this->invoices_model->getInvoicesToCheckDelivery();
        $ids = array_column($invoices, 'idInvoice');
        //if(!empty($ids)) $this->invoices_model->updateInvoicesToCheckDelivery($ids);

        foreach ($invoices as $val){
        	$line = array(
        	$val->client_name,
			$val->client_idNum,
			$val->idInvoice,
			$val->client_phone,
			$val->client_cellphone);
			fputcsv($file,$line);

        } 

        if(!empty($ids)) $this->invoices_model->updateInvoicesToCheckDelivery($ids);

        fclose($file); 
		exit;
		//header("Content-Type: application/vnd.ms-excel");
        //redirect(base_url()."/public/".$fileName); 
    }    

    public function createCloseToExpireCSVData() {

   		/*//$invoices = $this->invoices_model->getInvoicesCloseToExpire();
   		$invoices = $this->invoices_model->getInvoicesToCheckDelivery();
		
		echo "<h2>SIASS</h2>";
		echo "<br>";
		echo $this->db->last_query();
		echo "<br>";
		echo "<pre>";
        echo (count($invoices));
        echo "</pre><br>";

		//echo "<pre>";
        //echo print_r($invoices);
        //echo "</pre><br>";

        $ids = array_column($invoices, 'idInvoice');

        if(!empty($ids)) $this->invoices_model->updateInvoicesToCheckDelivery($ids);

        echo "<pre>";
        echo print_r($ids);
        echo "</pre><br>";
		
		$line = "";
		foreach ($invoices as $val){
        	$line .=
        	$val->client_name." - ".
			$val->idInvoice." - ".
			$val->client_idNum." - ".
			$val->store_name." - ".
			$val->client_phone." - ".
			$val->client_cellphone."<br>";
        } 

        echo "<pre>";
        echo $line;
        echo "</pre>";*/
		
		$this->load->helper("file");
		
		$filename = 'clientes_fact_cerca_a_vencer_'.date('Ymd').'.csv'; 
	   header("Content-Description: File Transfer"); 
	   header("Content-Disposition: attachment; filename=$filename"); 
	   header("Content-Type: application/csv; ");       

        $header = array(
		"Nombre",
		"Nit",
		"Telefono",
		"Celular");

		$file = fopen('php://output', 'w');
   		fputcsv($file, $header);

   		$invoices = $this->invoices_model->getInvoicesCloseToExpire();
 		$ids = array_column($invoices, 'idInvoice');
        //if(!empty($ids)) $this->invoices_model->updateInvoicesCloseToExpire($ids);

        foreach ($invoices as $val){
        	$line = array(
        	$val->client_name,
			$val->client_idNum,
			$val->idInvoice,
			$val->client_phone,
			$val->client_cellphone);
			fputcsv($file,$line);

        } 

        fclose($file); 
		exit;
		//header("Content-Type: application/vnd.ms-excel");
        //redirect(base_url()."/public/".$fileName); 
    }    

     public function createExpiredCSVData() {

   		/*//$invoices = $this->invoices_model->getInvoicesCloseToExpire();
   		$invoices = $this->invoices_model->getInvoicesToCheckDelivery();
		
		echo "<h2>SIASS</h2>";
		echo "<br>";
		echo $this->db->last_query();
		echo "<br>";
		echo "<pre>";
        echo (count($invoices));
        echo "</pre><br>";

		//echo "<pre>";
        //echo print_r($invoices);
        //echo "</pre><br>";

        $ids = array_column($invoices, 'idInvoice');

        if(!empty($ids)) $this->invoices_model->updateInvoicesToCheckDelivery($ids);

        echo "<pre>";
        echo print_r($ids);
        echo "</pre><br>";
		
		$line = "";
		foreach ($invoices as $val){
        	$line .=
        	$val->client_name." - ".
			$val->idInvoice." - ".
			$val->client_idNum." - ".
			$val->store_name." - ".
			$val->client_phone." - ".
			$val->client_cellphone."<br>";
        } 

        echo "<pre>";
        echo $line;
        echo "</pre>";*/
		
		$this->load->helper("file");
		
		$filename = 'clientes_fact_vencidas_'.date('Ymd').'.csv'; 
	   header("Content-Description: File Transfer"); 
	   header("Content-Disposition: attachment; filename=$filename"); 
	   header("Content-Type: application/csv; ");       

        $header = array(
		"Nombre",
		"Nit",
		"Telefono",
		"Celular");

		$file = fopen('php://output', 'w');
   		fputcsv($file, $header);

   		$invoices = $this->invoices_model->getInvoicesExpired();
 		$ids = array_column($invoices, 'idInvoice');
        //if(!empty($ids)) $this->invoices_model->updateInvoicesExpired($ids);

        foreach ($invoices as $val){
        	$line = array(
        	$val->client_name,
			$val->client_idNum,
			$val->idInvoice,
			$val->client_phone,
			$val->client_cellphone);
			fputcsv($file,$line);

        } 

        fclose($file); 
		exit;
		//header("Content-Type: application/vnd.ms-excel");
        //redirect(base_url()."/public/".$fileName);
    }

    /**
     * Reporte: Rendimiento de Vendedores vs Meta (estilo Odoo/SAP)
     */
    public function vendorPerformance()
    {
        $year = $this->input->get('year') ? $this->input->get('year') : date('Y');
        $store = $this->input->get('store') ? $this->input->get('store') : -1;

        $salesByVendorMonth = $this->invoices_model->getAllVendorsSalesByMonth($year, $store);
        $allGoals = $this->invoices_model->getAllVendorsGoals($year);

        $goalsByVendor = array();
        foreach ($allGoals as $g) {
            $goalsByVendor[$g->userId] = $g;
        }

        $vendorData = array();
        foreach ($salesByVendorMonth as $row) {
            $vid = $row->vendorId;
            if (!isset($vendorData[$vid])) {
                $vendorData[$vid] = array(
                    'name' => $row->vendor_name,
                    'store' => $row->store_name,
                    'months' => array_fill(1, 12, array('sales' => 0, 'collected' => 0, 'invoices' => 0, 'goal' => 0)),
                    'total_sales' => 0,
                    'total_collected' => 0,
                    'total_invoices' => 0,
                    'total_goal' => 0
                );
            }
            $m = (int)$row->month;
            $vendorData[$vid]['months'][$m]['sales'] = (float)$row->total_sales;
            $vendorData[$vid]['months'][$m]['collected'] = (float)$row->total_collected;
            $vendorData[$vid]['months'][$m]['invoices'] = (int)$row->invoice_count;
            $vendorData[$vid]['total_sales'] += (float)$row->total_sales;
            $vendorData[$vid]['total_collected'] += (float)$row->total_collected;
            $vendorData[$vid]['total_invoices'] += (int)$row->invoice_count;
        }

        foreach ($vendorData as $vid => &$vd) {
            if (isset($goalsByVendor[$vid])) {
                $goal = $goalsByVendor[$vid];
                for ($m = 1; $m <= 12; $m++) {
                    $vd['months'][$m]['goal'] = isset($goal->{'m'.$m}) ? (float)$goal->{'m'.$m} : 0;
                    $vd['total_goal'] += $vd['months'][$m]['goal'];
                }
            }
        }

        uasort($vendorData, function($a, $b) {
            return $b['total_sales'] - $a['total_sales'];
        });

        $stores = $this->stores_model->getStores();
        $grandTotalSales = array_sum(array_column($vendorData, 'total_sales'));
        $grandTotalCollected = array_sum(array_column($vendorData, 'total_collected'));
        $grandTotalGoal = array_sum(array_column($vendorData, 'total_goal'));

        $data = array(
            'vendorData' => $vendorData,
            'year' => $year,
            'store' => $store,
            'stores' => $stores,
            'grandTotalSales' => $grandTotalSales,
            'grandTotalCollected' => $grandTotalCollected,
            'grandTotalGoal' => $grandTotalGoal,
            'month_names' => array(1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic')
        );
        $this->load->view("sisvent/admin/reports/vendor_performance", $data);
    }

    /**
     * Reporte: Analisis de Clientes ABC (estilo SAP/Odoo)
     */
    public function clientsABC()
    {
        $year = $this->input->get('year') ? $this->input->get('year') : date('Y');
        $store = $this->input->get('store') ? $this->input->get('store') : -1;

        $clients = $this->invoices_model->getClientSalesAnalysis($year, $store);

        $totalRevenue = 0;
        foreach ($clients as $c) {
            $totalRevenue += (float)$c->total_purchases;
        }

        $accumulated = 0;
        $classifiedClients = array();
        foreach ($clients as $c) {
            $accumulated += (float)$c->total_purchases;
            $pctAcc = $totalRevenue > 0 ? ($accumulated / $totalRevenue) * 100 : 0;
            $pctInd = $totalRevenue > 0 ? ((float)$c->total_purchases / $totalRevenue) * 100 : 0;

            if ($pctAcc <= 80) {
                $cls = 'A';
            } elseif ($pctAcc <= 95) {
                $cls = 'B';
            } else {
                $cls = 'C';
            }

            $obj = new stdClass();
            $obj->idClient = $c->idClient;
            $obj->client_name = $c->client_name;
            $obj->idNum = $c->idNum;
            $obj->city = $c->city;
            $obj->vendor_name = $c->vendor_name;
            $obj->invoice_count = $c->invoice_count;
            $obj->total_purchases = (float)$c->total_purchases;
            $obj->total_paid = (float)$c->total_paid;
            $obj->total_debt = (float)$c->total_debt;
            $obj->debt_over_90 = isset($c->debt_over_90) ? (float)$c->debt_over_90 : 0;
            $obj->debt_61_90 = isset($c->debt_61_90) ? (float)$c->debt_61_90 : 0;
            $obj->debt_31_60 = isset($c->debt_31_60) ? (float)$c->debt_31_60 : 0;
            $obj->debt_0_30 = isset($c->debt_0_30) ? (float)$c->debt_0_30 : 0;
            $obj->first_purchase = $c->first_purchase;
            $obj->last_purchase = $c->last_purchase;
            $obj->individual_pct = $pctInd;
            $obj->accumulated_pct = $pctAcc;
            $obj->classification = $cls;
            $obj->avg_ticket = $c->invoice_count > 0 ? (float)$c->total_purchases / $c->invoice_count : 0;

            $classifiedClients[] = $obj;
        }

        $countA = count(array_filter($classifiedClients, function($c) { return $c->classification == 'A'; }));
        $countB = count(array_filter($classifiedClients, function($c) { return $c->classification == 'B'; }));
        $countC = count(array_filter($classifiedClients, function($c) { return $c->classification == 'C'; }));
        $sumA = array_sum(array_map(function($c) { return $c->classification == 'A' ? $c->total_purchases : 0; }, $classifiedClients));
        $sumB = array_sum(array_map(function($c) { return $c->classification == 'B' ? $c->total_purchases : 0; }, $classifiedClients));
        $sumC = array_sum(array_map(function($c) { return $c->classification == 'C' ? $c->total_purchases : 0; }, $classifiedClients));

        $stores = $this->stores_model->getStores();

        $totalDebt = array_sum(array_map(function($c) { return $c->total_debt; }, $classifiedClients));
        $totalPaid = array_sum(array_map(function($c) { return $c->total_paid; }, $classifiedClients));
        $totalDebtOver90 = array_sum(array_map(function($c) { return $c->debt_over_90; }, $classifiedClients));
        $totalDebt6190 = array_sum(array_map(function($c) { return $c->debt_61_90; }, $classifiedClients));
        $totalDebt3160 = array_sum(array_map(function($c) { return $c->debt_31_60; }, $classifiedClients));
        $totalDebt030 = array_sum(array_map(function($c) { return $c->debt_0_30; }, $classifiedClients));

        $data = array(
            'clients' => $classifiedClients,
            'totalRevenue' => $totalRevenue,
            'totalPaid' => $totalPaid,
            'totalDebt' => $totalDebt,
            'totalDebtOver90' => $totalDebtOver90,
            'totalDebt6190' => $totalDebt6190,
            'totalDebt3160' => $totalDebt3160,
            'totalDebt030' => $totalDebt030,
            'totalClients' => count($classifiedClients),
            'countA' => $countA, 'countB' => $countB, 'countC' => $countC,
            'sumA' => $sumA, 'sumB' => $sumB, 'sumC' => $sumC,
            'year' => $year,
            'store' => $store,
            'stores' => $stores
        );
        $this->load->view("sisvent/admin/reports/clients_abc", $data);
    }

    /**
     * Reporte: Cartera por Ciudad y Vendedor (estilo Odoo/SAP)
     */
    public function debtByCity()
    {
        $store = $this->input->get('store') ?: -1;
        $vendor = $this->input->get('vendor') ?: null;
        $client = $this->input->get('client') ?: null;
        $groupBy = $this->input->get('group') ?: 'city'; // city, store, vendor
        $onlyOverdue = $this->input->get('overdue') ?: 0; // 1 = solo +90 dias
        $minAmount = $this->input->get('min') ?: 0;

        $rows = $this->invoices_model->getDebtByCityAndVendor(null, $store, $vendor, $client);

        // Filtrar por monto minimo
        if ($minAmount > 0) {
            $rows = array_filter($rows, function($r) use ($minAmount) {
                return (float)$r->total_debt >= $minAmount;
            });
        }

        // Filtrar solo +90 dias
        if ($onlyOverdue) {
            $rows = array_filter($rows, function($r) {
                return (float)$r->debt_over_90 > 0;
            });
        }

        // Agrupar segun seleccion
        $groups = array();
        $grandDebt = 0; $grandOver90 = 0; $grand6190 = 0; $grand3160 = 0; $grand030 = 0;
        $grandInvoiced = 0; $grandPaid = 0; $grandClients = 0; $grandInvoices = 0;

        foreach ($rows as $r) {
            switch($groupBy) {
                case 'store': $key = $r->store_name ?: 'Sin tienda'; break;
                case 'vendor': $key = $r->vendor_name ?: 'Sin vendedor'; break;
                default: $key = $r->city ?: 'Sin ciudad';
            }

            if (!isset($groups[$key])) {
                $groups[$key] = array(
                    'name' => $key,
                    'total_debt' => 0, 'debt_over_90' => 0, 'debt_61_90' => 0,
                    'debt_31_60' => 0, 'debt_0_30' => 0, 'total_invoiced' => 0,
                    'total_paid' => 0, 'client_count' => 0, 'invoice_count' => 0,
                    'details' => array()
                );
            }
            $groups[$key]['total_debt'] += (float)$r->total_debt;
            $groups[$key]['debt_over_90'] += (float)$r->debt_over_90;
            $groups[$key]['debt_61_90'] += (float)$r->debt_61_90;
            $groups[$key]['debt_31_60'] += (float)$r->debt_31_60;
            $groups[$key]['debt_0_30'] += (float)$r->debt_0_30;
            $groups[$key]['total_invoiced'] += (float)$r->total_invoiced;
            $groups[$key]['total_paid'] += (float)$r->total_paid;
            $groups[$key]['client_count'] += (int)$r->client_count;
            $groups[$key]['invoice_count'] += (int)$r->invoice_count;
            $groups[$key]['details'][] = $r;

            $grandDebt += (float)$r->total_debt;
            $grandOver90 += (float)$r->debt_over_90;
            $grand6190 += (float)$r->debt_61_90;
            $grand3160 += (float)$r->debt_31_60;
            $grand030 += (float)$r->debt_0_30;
            $grandInvoiced += (float)$r->total_invoiced;
            $grandPaid += (float)$r->total_paid;
            $grandClients += (int)$r->client_count;
            $grandInvoices += (int)$r->invoice_count;
        }

        uasort($groups, function($a, $b) { return $b['total_debt'] <=> $a['total_debt']; });

        // DSO (Days Sales Outstanding)
        $dso = $grandInvoiced > 0 ? round(($grandDebt / ($grandInvoiced / 365)), 0) : 0;

        $stores = $this->stores_model->getStores();
        $vendors = $this->users_model->getUsers(false);
        $clients = $this->clients_model->getClients();

        $data = array(
            'groups' => $groups,
            'groupBy' => $groupBy,
            'store' => $store,
            'vendorFilter' => $vendor,
            'clientFilter' => $client,
            'onlyOverdue' => $onlyOverdue,
            'minAmount' => $minAmount,
            'stores' => $stores,
            'vendors' => $vendors,
            'clients' => $clients,
            'dso' => $dso,
            'grandDebt' => $grandDebt,
            'grandOver90' => $grandOver90,
            'grand6190' => $grand6190,
            'grand3160' => $grand3160,
            'grand030' => $grand030,
            'grandInvoiced' => $grandInvoiced,
            'grandPaid' => $grandPaid,
            'grandClients' => $grandClients,
            'grandInvoices' => $grandInvoices
        );
        $this->load->view("sisvent/admin/reports/debt_by_city", $data);
    }

    // ========================================================================
    // REPORT 1: Rentabilidad por Producto
    // ========================================================================
    public function productProfitability()
    {
        $year = $this->input->get('year') ?: date('Y');
        $store = $this->input->get('store') ?: -1;
        $family = $this->input->get('family') ?: null;
        $sort = $this->input->get('sort') ?: 'revenue';

        $products = $this->invoices_model->getProductProfitability($year, $store, $family, $sort);
        $stores = $this->stores_model->getStores();
        $families = $this->inventory_model->getProductFamilies();

        $totalRevenue = 0; $totalCost = 0; $totalMargin = 0; $totalQty = 0;
        foreach ($products as $p) {
            $totalRevenue += (float)$p->revenue;
            $totalCost += (float)$p->total_cost;
            $totalMargin += (float)$p->margin;
            $totalQty += (int)$p->qty_sold;
        }
        $avgMarginPct = $totalRevenue > 0 ? ($totalMargin / $totalRevenue) * 100 : 0;

        $data = array(
            'products' => $products,
            'year' => $year, 'store' => $store, 'family' => $family, 'sort' => $sort,
            'stores' => $stores, 'families' => $families,
            'totalRevenue' => $totalRevenue, 'totalCost' => $totalCost,
            'totalMargin' => $totalMargin, 'totalQty' => $totalQty, 'avgMarginPct' => $avgMarginPct
        );
        $this->load->view("sisvent/admin/reports/product_profitability", $data);
    }

    // ========================================================================
    // REPORT 2: Rentabilidad por Vendedor
    // ========================================================================
    public function vendorProfitability()
    {
        $year = $this->input->get('year') ?: date('Y');
        $store = $this->input->get('store') ?: -1;

        $vendors = $this->invoices_model->getVendorProfitability($year, $store);
        $stores = $this->stores_model->getStores();

        $totalRevenue = 0; $totalCogs = 0; $totalMargin = 0; $totalCommission = 0;
        foreach ($vendors as $v) {
            $totalRevenue += (float)$v->revenue;
            $totalCogs += (float)$v->cogs;
            $totalMargin += (float)$v->gross_margin;
            $totalCommission += (float)$v->commission_earned;
        }
        $avgMarginPct = $totalRevenue > 0 ? ($totalMargin / $totalRevenue) * 100 : 0;

        $data = array(
            'vendors' => $vendors,
            'year' => $year, 'store' => $store,
            'stores' => $stores,
            'totalRevenue' => $totalRevenue, 'totalCogs' => $totalCogs,
            'totalMargin' => $totalMargin, 'totalCommission' => $totalCommission,
            'avgMarginPct' => $avgMarginPct
        );
        $this->load->view("sisvent/admin/reports/vendor_profitability", $data);
    }

    // ========================================================================
    // REPORT 3: Flujo de Efectivo
    // ========================================================================
    public function cashFlow()
    {
        $year = $this->input->get('year') ?: date('Y');
        $store = $this->input->get('store') ?: -1;

        $monthly = $this->cashmovements_model->getCashFlowMonthly($year, $store);
        $stores = $this->stores_model->getStores();

        $totalIn = 0; $totalOut = 0;
        foreach ($monthly as $m) {
            $totalIn += (float)$m->ingresos;
            $totalOut += (float)$m->egresos;
        }

        $data = array(
            'monthly' => $monthly,
            'year' => $year, 'store' => $store,
            'stores' => $stores,
            'totalIn' => $totalIn, 'totalOut' => $totalOut,
            'netFlow' => $totalIn - $totalOut,
            'month_names' => array(1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre')
        );
        $this->load->view("sisvent/admin/reports/cash_flow", $data);
    }

    // ========================================================================
    // REPORT 4: Estado de Cuenta por Proveedor
    // ========================================================================
    public function providerStatement()
    {
        $provider = $this->input->get('provider') ?: null;
        $store = $this->input->get('store') ?: -1;

        $providers = $this->supplierbills_model->getProviderStatement($provider, $store);
        $stores = $this->stores_model->getStores();

        // Get providers list for filter dropdown
        $providersList = $this->db->select('idProvider, name')->from('providers')->where('deleted', 0)->order_by('name', 'ASC')->get()->result();

        $totalInvoiced = 0; $totalPaid = 0; $totalPending = 0;
        $total030 = 0; $total3160 = 0; $total6190 = 0; $total90 = 0;
        foreach ($providers as $p) {
            $totalInvoiced += (float)$p->total_invoiced;
            $totalPaid += (float)$p->total_paid;
            $totalPending += (float)$p->total_pending;
            $total030 += (float)$p->aging_0_30;
            $total3160 += (float)$p->aging_31_60;
            $total6190 += (float)$p->aging_61_90;
            $total90 += (float)$p->aging_90_plus;
        }

        $data = array(
            'providers' => $providers,
            'providerFilter' => $provider, 'store' => $store,
            'stores' => $stores, 'providersList' => $providersList,
            'totalInvoiced' => $totalInvoiced, 'totalPaid' => $totalPaid,
            'totalPending' => $totalPending,
            'total030' => $total030, 'total3160' => $total3160,
            'total6190' => $total6190, 'total90' => $total90
        );
        $this->load->view("sisvent/admin/reports/provider_statement", $data);
    }

    // ========================================================================
    // REPORT 5: Inventario Valorizado por Bodega
    // ========================================================================
    public function inventoryValuation()
    {
        $store = $this->input->get('store') ?: -1;
        $family = $this->input->get('family') ?: null;

        $summary = $this->inventory_model->getValorizedInventorySummary($store, $family);
        $details = $this->inventory_model->getValorizedInventory($store, $family);
        $stores = $this->stores_model->getStores();
        $families = $this->inventory_model->getProductFamilies();

        $grandProducts = 0; $grandUnits = 0; $grandValue = 0;
        foreach ($summary as $s) {
            $grandProducts += (int)$s->product_count;
            $grandUnits += (int)$s->total_units;
            $grandValue += (float)$s->total_value;
        }

        $data = array(
            'summary' => $summary, 'details' => $details,
            'store' => $store, 'family' => $family,
            'stores' => $stores, 'families' => $families,
            'grandProducts' => $grandProducts, 'grandUnits' => $grandUnits, 'grandValue' => $grandValue
        );
        $this->load->view("sisvent/admin/reports/inventory_valuation", $data);
    }

    // ========================================================================
    // REPORT 6: Rotacion de Inventario
    // ========================================================================
    public function inventoryRotation()
    {
        $store = $this->input->get('store') ?: -1;
        $family = $this->input->get('family') ?: null;

        $products = $this->inventory_model->getInventoryRotation($store, $family);
        $stores = $this->stores_model->getStores();
        $families = $this->inventory_model->getProductFamilies();

        $alta = 0; $media = 0; $baja = 0; $sinMov = 0;
        foreach ($products as $p) {
            $idx = (float)$p->rotation_index;
            $qtySold = (int)$p->qty_sold;
            if ($qtySold == 0) { $sinMov++; }
            elseif ($idx >= 4) { $alta++; }
            elseif ($idx >= 1) { $media++; }
            else { $baja++; }
        }

        $data = array(
            'products' => $products,
            'store' => $store, 'family' => $family,
            'stores' => $stores, 'families' => $families,
            'alta' => $alta, 'media' => $media, 'baja' => $baja, 'sinMov' => $sinMov,
            'totalProducts' => count($products)
        );
        $this->load->view("sisvent/admin/reports/inventory_rotation", $data);
    }

    // ========================================================================
    // REPORT 7: Comparativo Ventas Ano vs Ano (YoY)
    // ========================================================================
    public function salesYoY()
    {
        $year = $this->input->get('year') ?: date('Y');
        $store = $this->input->get('store') ?: -1;
        $vendor = $this->input->get('vendor') ?: null;
        $yearPrev = $year - 1;

        $monthly = $this->invoices_model->getSalesYoY($year, $yearPrev, $store, $vendor);
        $stores = $this->stores_model->getStores();
        $vendorsList = $this->vendors_model->getVendors('', $this->session->userdata('user_data')['role'] == 1);

        $ytdCurrent = 0; $ytdPrevious = 0;
        foreach ($monthly as $m) {
            $ytdCurrent += (float)$m->current_total;
            $ytdPrevious += (float)$m->previous_total;
        }
        $ytdGrowth = $ytdPrevious > 0 ? (($ytdCurrent - $ytdPrevious) / $ytdPrevious) * 100 : ($ytdCurrent > 0 ? 100 : 0);

        $data = array(
            'monthly' => $monthly,
            'year' => $year, 'yearPrev' => $yearPrev,
            'store' => $store, 'vendor' => $vendor,
            'stores' => $stores, 'vendorsList' => $vendorsList,
            'ytdCurrent' => $ytdCurrent, 'ytdPrevious' => $ytdPrevious, 'ytdGrowth' => $ytdGrowth,
            'month_names' => array(1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre')
        );
        $this->load->view("sisvent/admin/reports/sales_yoy", $data);
    }

    // ========================================================================
    // REPORT 8: Top Productos mas Vendidos
    // ========================================================================
    public function topProducts()
    {
        $year = $this->input->get('year') ?: date('Y');
        $store = $this->input->get('store') ?: -1;
        $family = $this->input->get('family') ?: null;
        $topN = $this->input->get('topn') ?: 25;
        $orderBy = $this->input->get('orderby') ?: 'qty';

        $products = $this->invoices_model->getTopProducts($year, $store, $family, $topN, $orderBy);
        $stores = $this->stores_model->getStores();
        $families = $this->inventory_model->getProductFamilies();

        $totalQty = 0; $totalRevenue = 0;
        foreach ($products as $p) {
            $totalQty += (int)$p->qty_sold;
            $totalRevenue += (float)$p->revenue;
        }

        // Pareto data
        $accValue = 0;
        $paretoTotal = $orderBy === 'revenue' ? $totalRevenue : $totalQty;
        foreach ($products as &$p) {
            $accValue += $orderBy === 'revenue' ? (float)$p->revenue : (int)$p->qty_sold;
            $p->pareto_pct = $paretoTotal > 0 ? ($accValue / $paretoTotal) * 100 : 0;
        }

        $data = array(
            'products' => $products,
            'year' => $year, 'store' => $store, 'family' => $family, 'topN' => $topN,
            'orderBy' => $orderBy,
            'stores' => $stores, 'families' => $families,
            'totalQty' => $totalQty, 'totalRevenue' => $totalRevenue
        );
        $this->load->view("sisvent/admin/reports/top_products", $data);
    }

    // ========================================================================
    // REPORT 9: Gastos por Categoria
    // ========================================================================
    public function expensesByCategory()
    {
        $year = $this->input->get('year') ?: date('Y');
        $store = $this->input->get('store') ?: null;

        $byCategory = $this->expenserecords_model->getExpenseTotalsByCategory($year, $store);
        $monthlyData = $this->expenserecords_model->getExpensesByCategory($year, $store);
        $stores = $this->stores_model->getStores();

        $totalExpenses = 0;
        foreach ($byCategory as $c) {
            $totalExpenses += (float)$c->total;
        }

        // Build monthly grid per category
        $monthlyGrid = array();
        foreach ($monthlyData as $row) {
            $catId = $row->category_id ?: 'sin_cat';
            if (!isset($monthlyGrid[$catId])) {
                $monthlyGrid[$catId] = array(
                    'name' => $row->category_name ?: 'Sin categoria',
                    'code' => $row->category_code ?: '',
                    'months' => array_fill(1, 12, 0),
                    'total' => 0
                );
            }
            $monthlyGrid[$catId]['months'][(int)$row->mes] = (float)$row->total;
            $monthlyGrid[$catId]['total'] += (float)$row->total;
        }
        uasort($monthlyGrid, function($a, $b) { return $b['total'] <=> $a['total']; });

        $data = array(
            'byCategory' => $byCategory, 'monthlyGrid' => $monthlyGrid,
            'year' => $year, 'store' => $store,
            'stores' => $stores, 'totalExpenses' => $totalExpenses,
            'month_names' => array(1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic')
        );
        $this->load->view("sisvent/admin/reports/expenses_by_category", $data);
    }

    // ========================================================================
    // REPORT 10: Comisiones de Vendedores
    // ========================================================================
    public function vendorCommissions()
    {
        $year = $this->input->get('year') ?: date('Y');
        $month = $this->input->get('month') ?: null;
        $store = $this->input->get('store') ?: -1;

        $commissions = $this->invoices_model->getVendorCommissions($year, $month, $store);
        $settlements = $this->invoices_model->getVendorSettlements($year);
        $stores = $this->stores_model->getStores();

        // Index settlements by vendor
        $settledByVendor = array();
        foreach ($settlements as $s) {
            $settledByVendor[$s->vendorId] = (float)$s->total_settled;
        }

        // Build vendor summary
        $vendorSummary = array();
        foreach ($commissions as $c) {
            $vid = $c->vendorId;
            if (!isset($vendorSummary[$vid])) {
                $vendorSummary[$vid] = array(
                    'name' => $c->vendor_name,
                    'store' => $c->store_name,
                    'commission_perc' => (float)$c->commission_perc,
                    'months' => array_fill(1, 12, array('sales' => 0, 'commission' => 0)),
                    'total_sales' => 0,
                    'total_commission' => 0,
                    'total_settled' => isset($settledByVendor[$vid]) ? $settledByVendor[$vid] : 0
                );
            }
            $m = (int)$c->mes;
            $vendorSummary[$vid]['months'][$m]['sales'] = (float)$c->total_sales;
            $vendorSummary[$vid]['months'][$m]['commission'] = (float)$c->commission_amount;
            $vendorSummary[$vid]['total_sales'] += (float)$c->total_sales;
            $vendorSummary[$vid]['total_commission'] += (float)$c->commission_amount;
        }

        $grandSales = 0; $grandCommission = 0; $grandSettled = 0;
        foreach ($vendorSummary as $v) {
            $grandSales += $v['total_sales'];
            $grandCommission += $v['total_commission'];
            $grandSettled += $v['total_settled'];
        }

        $data = array(
            'vendorSummary' => $vendorSummary,
            'year' => $year, 'month' => $month, 'store' => $store,
            'stores' => $stores,
            'grandSales' => $grandSales, 'grandCommission' => $grandCommission,
            'grandSettled' => $grandSettled,
            'grandPending' => $grandCommission - $grandSettled,
            'month_names' => array(1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic')
        );
        $this->load->view("sisvent/admin/reports/vendor_commissions", $data);
    }

    /**
     * Estado de Cuenta de Cliente
     */
    public function clientStatement()
    {
        $clientId = $this->input->get('id');
        $q = $this->input->get('q') ?: '';
        $client = null;
        $results = array();
        $facturas = array();
        $pagos = array();
        $vendorName = '';
        $totals = (object) array('total_compras'=>0,'total_pagado'=>0,'saldo_pendiente'=>0,'num_facturas'=>0,'ultimo_pago'=>null);

        // Buscar clientes
        if ($q && !$clientId) {
            $this->db->select('idClient, name, idNum, city, cellphone, phone')
                ->from('clients')
                ->group_start()
                    ->like('name', $q)->or_like('idNum', $q)->or_like('cellphone', $q)->or_like('phone', $q)
                ->group_end()
                ->where('deleted', 0)->order_by('name')->limit(20);
            $results = $this->db->get()->result();
        }

        // Cargar datos del cliente
        if ($clientId) {
            $client = $this->db->get_where('clients', array('idClient' => $clientId))->row();
            if ($client) {
                // Vendedor
                $vendor = $this->db->select('name')->from('users')->where('idUser', $client->vendor)->get()->row();
                $vendorName = $vendor ? $vendor->name : $client->vendor;

                // Facturas
                $facturas = $this->db->select('idInvoice, date, total, payment, discount, state')
                    ->from('invoices')
                    ->where('clientId', $clientId)->where('deleted', 0)
                    ->order_by('date', 'DESC')
                    ->get()->result();

                // Pagos
                $pagos = $this->db->select('p.*, pm.name as method_name')
                    ->from('payments p')
                    ->join('payment_methods pm', 'pm.idPayment_method = p.paymentMethod', 'left')
                    ->where('p.clientId', $clientId)->where('p.deleted', 0)
                    ->order_by('p.date', 'DESC')
                    ->get()->result();

                // Totales
                foreach ($facturas as $f) {
                    $totals->total_compras += (float)$f->total;
                    $totals->total_pagado += (float)$f->payment + (float)$f->discount;
                    $totals->num_facturas++;
                }
                $totals->saldo_pendiente = $totals->total_compras - $totals->total_pagado;
                $totals->ultimo_pago = !empty($pagos) ? $pagos[0]->date : null;
            }
        }

        $data = array(
            'client' => $client,
            'results' => $results,
            'q' => $q,
            'facturas' => $facturas,
            'pagos' => $pagos,
            'vendorName' => $vendorName,
            'totals' => $totals
        );
        $this->load->view("sisvent/admin/reports/client_statement", $data);
    }

    /**
     * Antigüedad de Saldos — Aging Report
     */
    public function aging()
    {
        $vendor = $this->input->get('vendor') ?: 'all';
        $store = $this->input->get('store') ?: 'all';

        $where = "i.deleted = 0 AND i.state IN (0,1) AND (i.total - i.payment - i.discount) > 0";
        if ($vendor !== 'all') $where .= " AND i.vendorId = " . $this->db->escape($vendor);
        if ($store !== 'all') $where .= " AND i.storeId = " . $this->db->escape($store);

        $clientes = $this->db->query("
            SELECT c.idClient, c.name as client_name, u.name as vendor_name,
                SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) <= 0 THEN (i.total - i.payment - i.discount) ELSE 0 END) as corriente,
                SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) BETWEEN 1 AND 30 THEN (i.total - i.payment - i.discount) ELSE 0 END) as d1_30,
                SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) BETWEEN 31 AND 60 THEN (i.total - i.payment - i.discount) ELSE 0 END) as d31_60,
                SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) BETWEEN 61 AND 90 THEN (i.total - i.payment - i.discount) ELSE 0 END) as d61_90,
                SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) > 90 THEN (i.total - i.payment - i.discount) ELSE 0 END) as d90
            FROM invoices i
            JOIN clients c ON c.idClient = i.clientId
            LEFT JOIN users u ON u.idUser = i.vendorId
            WHERE {$where}
            GROUP BY c.idClient, c.name, u.name
            HAVING (SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) <= 0 THEN (i.total - i.payment - i.discount) ELSE 0 END) +
                    SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) BETWEEN 1 AND 30 THEN (i.total - i.payment - i.discount) ELSE 0 END) +
                    SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) BETWEEN 31 AND 60 THEN (i.total - i.payment - i.discount) ELSE 0 END) +
                    SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) BETWEEN 61 AND 90 THEN (i.total - i.payment - i.discount) ELSE 0 END) +
                    SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) > 90 THEN (i.total - i.payment - i.discount) ELSE 0 END)) > 0
            ORDER BY SUM(CASE WHEN DATEDIFF(CURDATE(), i.date) > 90 THEN (i.total - i.payment - i.discount) ELSE 0 END) DESC
        ")->result();

        $totals = (object) array('corriente'=>0,'d1_30'=>0,'d31_60'=>0,'d61_90'=>0,'d90'=>0,'total'=>0);
        foreach ($clientes as $c) {
            $totals->corriente += (float)$c->corriente;
            $totals->d1_30 += (float)$c->d1_30;
            $totals->d31_60 += (float)$c->d31_60;
            $totals->d61_90 += (float)$c->d61_90;
            $totals->d90 += (float)$c->d90;
        }
        $totals->total = $totals->corriente + $totals->d1_30 + $totals->d31_60 + $totals->d61_90 + $totals->d90;

        // Vendedores y tiendas para filtros
        $this->db->select('idUser, name')->from('users')->where('role', 3)->where('deleted', 0)->order_by('name');
        $vendedores = $this->db->get()->result();
        $this->db->select('idStore, name')->from('stores')->where('deleted', 0)->order_by('name');
        $tiendas = $this->db->get()->result();

        $data = array(
            'clientes' => $clientes,
            'totals' => $totals,
            'vendedores' => $vendedores,
            'tiendas' => $tiendas,
            'vendor' => $vendor,
            'store' => $store
        );
        $this->load->view("sisvent/admin/reports/aging", $data);
    }
}