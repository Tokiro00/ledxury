<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DbQuery extends CI_Controller {

	public function __construct(){
		parent::__construct();
		// Sin autenticación para este endpoint temporal
		$this->load->model("users_model");
		$this->load->model("stores_model");
		$this->load->model("products_model");
	}

	public function info()
	{
		header('Content-Type: text/plain; charset=utf-8');

		echo "=== BUSCANDO VENDEDOR: germam o german ===\n\n";
		$query = $this->db->query("SELECT idUser, name, uname, email, role, store FROM users WHERE name LIKE '%german%' OR uname LIKE '%german%' OR name LIKE '%germam%' OR uname LIKE '%germam%'");
		$users = $query->result();

		if(count($users) > 0) {
			echo "ENCONTRADO:\n";
			foreach($users as $user) {
				echo "ID: {$user->idUser}, Name: {$user->name}, Username: {$user->uname}, Email: {$user->email}, Role: {$user->role}, Store: {$user->store}\n";
			}
		} else {
			echo "NO SE ENCONTRÓ 'germam' o 'german'\n\n";

			echo "=== TODOS LOS USUARIOS (primeros 30) ===\n";
			$query = $this->db->query("SELECT idUser, name, uname, email, role, store FROM users WHERE deleted = 0 ORDER BY name LIMIT 30");
			$users = $query->result();
			foreach($users as $user) {
				$roleText = $user->role == 1 ? 'Admin' : ($user->role == 2 ? 'Staff' : ($user->role == 3 ? 'Vendor' : 'Other'));
				echo "ID: {$user->idUser}, Username: {$user->uname}, Name: {$user->name}, Role: {$roleText}\n";
			}
		}

		echo "\n=== TIENDAS ===\n";
		$stores = $this->stores_model->getStores();
		foreach($stores as $store) {
			echo "ID: {$store->idStore}, Name: {$store->name}\n";
		}

		echo "\n\n=== PRODUCTOS CON 'MODULO' O 'LED' ===\n";
		$query = $this->db->query("SELECT idProduct, name, price, price_base FROM products WHERE (name LIKE '%modulo%' OR name LIKE '%led%' OR name LIKE '%módulo%') AND deleted = 0 ORDER BY name LIMIT 30");
		$products = $query->result();

		if(count($products) > 0) {
			foreach($products as $product) {
				echo "Código: {$product->idProduct}\n  Nombre: {$product->name}\n  Precio: \${$product->price}, Base: \${$product->price_base}\n\n";
			}
		} else {
			echo "No se encontraron productos con 'modulo' o 'led'\n\n";
			echo "=== PRIMEROS 30 PRODUCTOS ACTIVOS ===\n";
			$query = $this->db->query("SELECT idProduct, name, price FROM products WHERE deleted = 0 ORDER BY name LIMIT 30");
			$products = $query->result();
			foreach($products as $product) {
				echo "Código: {$product->idProduct}, Nombre: {$product->name}\n";
			}
		}

		echo "\n\n=== TIPOS DE ENVÍO (DELIVERY TYPES) ===\n";
		$query = $this->db->query("SELECT * FROM deliverytypes ORDER BY idDeliverytype");
		if($query && $query->num_rows() > 0) {
			$types = $query->result();
			foreach($types as $type) {
				echo "ID: {$type->idDeliverytype}, Nombre: {$type->name}\n";
			}
		} else {
			echo "No hay tabla deliverytypes o está vacía\n";
		}

		echo "\n\n=== BÚSQUEDA DE PRODUCTOS EN GOOGLE SHEET ===\n";
		echo "El bot envía descripciones como:\n";
		echo "- '40 módulos 6LED'\n";
		echo "- 'Módulos 6LED Azul'\n";
		echo "- Color: Azul / Voltaje: 12V\n\n";
		echo "Necesito saber cómo buscar estos productos en tu BD.\n";
		echo "¿Los productos tienen nombres exactos o códigos específicos?\n";
	}
}
