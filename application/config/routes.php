<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// API v1 routes
$route['api/v1/login'] = 'api/V1/login';
$route['api/v1/refresh'] = 'api/V1/refresh';
$route['api/v1/clients'] = 'api/V1/clients_list';
$route['api/v1/clients/search'] = 'api/V1/clients_search';
$route['api/v1/clients/detail'] = 'api/V1/clients_detail';
$route['api/v1/products/search'] = 'api/V1/products_search';
$route['api/v1/products/detail'] = 'api/V1/products_detail';
$route['api/v1/products/catalog'] = 'api/V1/products_catalog';
$route['api/v1/stores'] = 'api/V1/stores_list';
$route['api/v1/budgets'] = 'api/V1/budgets_list';
$route['api/v1/budgets/store'] = 'api/V1/budgets_store';
$route['api/v1/budgets/detail'] = 'api/V1/budgets_detail';
$route['api/v1/budgets/sync'] = 'api/V1/budgets_sync';
$route['api/v1/refunds'] = 'api/V1/refunds_list';
$route['api/v1/refunds/create'] = 'api/V1/refunds_create';
$route['api/v1/refunds/invoice'] = 'api/V1/refunds_invoice_products';
$route['api/v1/clients/by-phone'] = 'api/V1/clients_by_phone';
$route['api/v1/promotions'] = 'api/V1/promotions_list';
$route['api/v1/cartera'] = 'api/V1/cartera';
$route['api/v1/liquidacion'] = 'api/V1/liquidacion';
