<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		$this->backend_lib->control();
        $this->load->model("vouchers_model");
        $this->load->model("invoices_model");
        $this->load->model("budgets_model");
        $this->load->model("payments_model");
        $this->load->model("vendors_model");
        $this->load->model("stores_model");
        $this->load->model("users_model");
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
							$budgetstorebtday[$idStore]["budgets"][$vendorbudgets->date][$vendorbudgets->vendorId] = $vendorbudgets;
							$budgetstorebtday[$idStore]["storename"] = $str->name;
							$budgetstorebtday[$idStore]["vendor_ids"] = $vendor_names;
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
						}

					}
				}
						
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

}