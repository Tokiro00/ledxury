<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bot Import Controller
 *
 * Importa ventas desde Google Sheet generado por bot de IA
 * Crea automáticamente clientes y presupuestos
 */
class BotImport extends CI_Controller {

	// Configuración
	private $default_vendor = '1234567'; // GerMam
	private $default_store = '1'; // Medellín
	private $default_iva = 0; // Sin IVA
	private $default_delivery_type = 9; // Promoción con envío gratis
	private $apps_script_url = 'https://script.google.com/macros/s/AKfycbyRRP6pTIHF1fGZA0gL4ma6jpk2Yr36Nl9TVmvrXc8K4flnEK3bXHc16k9-DLeC9U7Uow/exec';

	// Mapeo de colores a letras de producto
	private $color_map = [
		'azul hielo' => 'I',
		'azul ice' => 'I',
		'ice' => 'I',
		'hielo' => 'I',
		'azul oscuro' => 'E',
		'azul' => 'E',
		'blue' => 'E',
		'rojo' => 'C',
		'red' => 'C',
		'verde' => 'F',
		'green' => 'F',
		'amarillo' => 'D',
		'yellow' => 'D',
		'blanco calido' => 'B',
		'blanco cálido' => 'B',
		'warm white' => 'B',
		'blanco' => 'A',
		'white' => 'A',
		'rosado' => 'G',
		'fucsia' => 'G',
		'pink' => 'G',
		'morado' => 'H',
		'purple' => 'H',
		'verde limon' => 'J',
		'verde limón' => 'J',
		'limon' => 'J',
		'limón' => 'J',
		'verde turquesa' => 'K',
		'turquesa' => 'K',
	];

	// Mapeo de productos especiales (no-LED) → código en BD
	private $product_map = [
		'aspiradora' => 'TP-012',
		'candado' => 'DISC-ALARM',
	];

	// Mapeo de vendedores del sheet → ID en BD
	private $vendor_map = [
		'germam medellin' => '1234567',
		'germam medellín' => '1234567',
		'germam julian' => '12345678',
		'julian germam' => '12345678',
		'germam bogota' => '12345678',
		'germam bogotá' => '12345678',
		'bogota' => '12345678',
		'bogotá' => '12345678',
		'germam barranquilla' => '1048937562',
		'germam maria barranquilla' => '1048937562',
		'germam maria' => '1048937562',
		'maria germam' => '1048937562',
		'barranquilla' => '1048937562',
		'maria' => '1048937562',
	];

	// Mapeo de textos de envío del sheet a IDs de delivery_type
	// ID 9 = Promoción con envío gratis
	// ID 5 = Envío Interrapidisimo (cliente paga)
	// ID 2 = Domicilio en Medellín
	private $delivery_map = [
		'gratis' => 9,
		'envio gratis' => 9,
		'envío gratis' => 9,
		'promocion' => 9,
		'promoción' => 9,
		'no gratis' => 5,
		'el cliente paga el envio' => 5,
		'el cliente paga el envío' => 5,
		'interrapidisimo' => 5,
		'domicilio en medellin' => 2,
		'domicilio en medellín' => 2,
		'domicilio medellin' => 2,
		'domicilio medellín' => 2,
	];

	public function __construct(){
		parent::__construct();
		$this->load->model("clients_model");
		$this->load->model("budgets_model");
		$this->load->model("products_model");
		$this->load->model("inventory_model");
		$this->load->model("dropshipping_model");
		$this->load->model("users_model");
		$this->load->helper('login');
	}

	/**
	 * GET: /sisvent/rest/botimport/getMyConfig
	 * Retorna la configuración del bot del usuario logueado
	 * Si es Super Admin (role 1), también retorna lista de todos los vendedores con bot
	 */
	public function getMyConfig()
	{
		// Verificar si está logueado
		if (!is_logged_in()) {
			return $this->json_response(401, [
				'ok' => false,
				'error' => 'Debes iniciar sesión para usar esta función'
			]);
		}

		$user_data = $this->session->userdata('user_data');
		$user_id = $user_data['uname'];
		$user_role = $user_data['role'];
		$is_super_admin = ($user_role == 1);

		// Obtener configuración del bot del usuario
		$user = $this->users_model->getAnyUser($user_id);

		if (!$user) {
			return $this->json_response(404, ['ok' => false, 'error' => 'Usuario no encontrado']);
		}

		$has_bot = !empty($user->bot_sheet_id);

		$response = [
			'ok' => true,
			'user_id' => $user_id,
			'user_name' => $user_data['name'],
			'role' => $user_role,
			'is_super_admin' => $is_super_admin,
			'has_bot' => $has_bot,
			'bot_config' => $has_bot ? [
				'sheet_id' => $user->bot_sheet_id,
				'script_url' => $user->bot_script_url,
				'gid' => $user->bot_gid ?? '0'
			] : null
		];

		// Si es Super Admin, incluir lista de todos los vendedores con bot configurado
		if ($is_super_admin) {
			$vendors = $this->db->select('idUser, name, bot_sheet_id, bot_script_url, bot_gid')
				->from('users')
				->where('bot_sheet_id IS NOT NULL')
				->where('bot_sheet_id !=', '')
				->where('deleted', 0)
				->order_by('name', 'ASC')
				->get()
				->result();

			$response['vendors'] = $vendors;
		}

		return $this->json_response(200, $response);
	}

	/**
	 * GET: /sisvent/rest/botimport/getAllVendors
	 * Retorna todos los vendedores con bot configurado (solo para admin)
	 */
	public function getAllVendors()
	{
		// Verificar si está logueado
		if (!is_logged_in()) {
			return $this->json_response(401, ['ok' => false, 'error' => 'No autorizado']);
		}

		$user_data = $this->session->userdata('user_data');

		// Solo rol 1 (admin) puede ver todos los vendedores
		if ($user_data['role'] != 1) {
			return $this->json_response(403, ['ok' => false, 'error' => 'Solo administradores']);
		}

		// Obtener todos los usuarios con bot configurado
		$vendors = $this->db->select('idUser, name, bot_sheet_id, bot_script_url, bot_gid')
			->from('users')
			->where('bot_sheet_id IS NOT NULL')
			->where('bot_sheet_id !=', '')
			->get()
			->result();

		return $this->json_response(200, [
			'ok' => true,
			'vendors' => $vendors
		]);
	}

	/**
	 * GET: /sisvent/rest/botimport/getVendorsForConfig
	 * Retorna TODOS los vendedores (roles 2 y 3) para configuración
	 * Solo para Super Admin (role 1)
	 */
	public function getVendorsForConfig()
	{
		if (!is_logged_in()) {
			return $this->json_response(401, ['ok' => false, 'error' => 'No autorizado']);
		}

		$user_data = $this->session->userdata('user_data');
		if ($user_data['role'] != 1) {
			return $this->json_response(403, ['ok' => false, 'error' => 'Solo Super Admin']);
		}

		// Obtener todos los usuarios con roles 2 (Admin) y 3 (Vendedor)
		$vendors = $this->db->select('idUser, name, role, bot_sheet_id, bot_script_url, bot_gid')
			->from('users')
			->where_in('role', [2, 3])
			->where('deleted', 0)
			->order_by('name', 'ASC')
			->get()
			->result();

		return $this->json_response(200, [
			'ok' => true,
			'vendors' => $vendors
		]);
	}

	/**
	 * POST: /sisvent/rest/botimport/saveVendorConfig
	 * Guarda la configuración del bot para un vendedor
	 * Solo para Super Admin (role 1)
	 */
	public function saveVendorConfig()
	{
		if (!is_logged_in()) {
			return $this->json_response(401, ['ok' => false, 'error' => 'No autorizado']);
		}

		$user_data = $this->session->userdata('user_data');
		if ($user_data['role'] != 1) {
			return $this->json_response(403, ['ok' => false, 'error' => 'Solo Super Admin']);
		}

		$vendor_id = $this->input->post('vendor_id');
		$sheet_id = trim($this->input->post('sheet_id') ?? '');
		$script_url = trim($this->input->post('script_url') ?? '');
		$gid = trim($this->input->post('gid') ?? '0');

		if (empty($vendor_id)) {
			return $this->json_response(400, ['ok' => false, 'error' => 'vendor_id requerido']);
		}

		// Verificar que el vendedor existe
		$vendor = $this->users_model->getAnyUser($vendor_id);
		if (!$vendor) {
			return $this->json_response(404, ['ok' => false, 'error' => 'Vendedor no encontrado']);
		}

		// Actualizar configuración
		$update_data = [
			'bot_sheet_id' => $sheet_id ?: null,
			'bot_script_url' => $script_url ?: null,
			'bot_gid' => $gid ?: '0'
		];

		$this->users_model->update($vendor_id, $update_data);

		return $this->json_response(200, [
			'ok' => true,
			'message' => 'Configuración guardada para ' . $vendor->name,
			'vendor' => [
				'idUser' => $vendor_id,
				'name' => $vendor->name,
				'bot_sheet_id' => $sheet_id,
				'bot_script_url' => $script_url,
				'bot_gid' => $gid
			]
		]);
	}

	/**
	 * Endpoint principal: Lee Google Sheet e importa datos
	 * GET/POST: /sisvent/rest/botimport/processSheet?sheet_id=xxx&gid=0&vendor_id=xxx
	 * POST puede incluir archivo Excel de productos agotados (blocked_file)
	 *
	 * Super Admin (role 1) puede especificar vendor_id para importar por otro vendedor
	 */
	public function processSheet()
	{
		try {
			// Parámetros externos (opcionales)
			$sheetId = $this->input->get('sheet_id');
			$gid = $this->input->get('gid');
			$limit = (int)($this->input->get('limit') ?? 50);
			$vendor_override = $this->input->get('vendor');
			$script_url = $this->input->get('script_url');
			$vendor_id = $this->input->get('vendor_id'); // Para super admin

			// Verificar login
			if (!is_logged_in()) {
				return $this->json_response(401, ['ok' => false, 'error' => 'Debes iniciar sesión']);
			}

			$user_data = $this->session->userdata('user_data');
			$is_super_admin = ($user_data['role'] == 1);

			// Si no hay sheet_id, determinar qué configuración usar
			if (empty($sheetId)) {
				// Si es super admin y especificó vendor_id, usar la config de ese vendedor
				if ($is_super_admin && !empty($vendor_id)) {
					$target_vendor = $this->users_model->getAnyUser($vendor_id);

					if (!$target_vendor || empty($target_vendor->bot_sheet_id)) {
						return $this->json_response(400, ['ok' => false, 'error' => 'El vendedor seleccionado no tiene bot configurado.']);
					}

					$sheetId = $target_vendor->bot_sheet_id;
					$gid = $target_vendor->bot_gid ?? '0';
					$script_url = $target_vendor->bot_script_url;
					$vendor_override = $vendor_id; // Asignar al vendedor seleccionado
				} else {
					// Usar configuración del usuario logueado
					$user = $this->users_model->getAnyUser($user_data['uname']);

					if (!$user || empty($user->bot_sheet_id)) {
						return $this->json_response(400, ['ok' => false, 'error' => 'No tienes bot configurado. Contacta al administrador.']);
					}

					$sheetId = $user->bot_sheet_id;
					$gid = $user->bot_gid ?? '0';
					$script_url = $user->bot_script_url;
					$vendor_override = $user_data['uname']; // Forzar el vendedor al usuario logueado
				}
			}

			// Valores por defecto
			if (empty($gid)) $gid = '0';

			// Cargar productos agotados guardados en el servidor
			$blocked_products = $this->load_blocked_products();

			// URL del Apps Script para write-back
			if (!empty($script_url)) {
				$this->apps_script_url = $script_url;
			}

			// Leer CSV del Google Sheet
			$csvUrl = sprintf(
				'https://docs.google.com/spreadsheets/d/%s/export?format=csv&id=%s&gid=%s',
				urlencode($sheetId),
				urlencode($sheetId),
				urlencode($gid)
			);

			$csv = $this->http_get($csvUrl);
			if ($csv === false || $csv === '') {
				return $this->json_response(502, ['error' => 'No se pudo descargar el CSV. Verifica que el sheet sea público.']);
			}

			// Parsear CSV
			list($headers, $rows) = $this->parse_csv_assoc($csv, $limit);

			// Procesar filas
			$results = [
				'processed' => 0,
				'created' => 0,
				'errors' => 0,
				'skipped' => 0,
				'details' => []
			];

			foreach ($rows as $index => $row) {
				// Solo procesar filas que NO tengan ID ya asignado (id_factura, id_presupuesto) o marcadas como ENVIADO
				$tiene_id = !empty($row['id_factura']) || !empty($row['id_presupuesto']);
				if ($tiene_id || !empty($row['enviado'])) {
					$results['skipped']++;
					continue;
				}

				// Validar datos mínimos
				if (empty($row['nombre']) || empty($row['documento'])) {
					$results['errors']++;
					$results['details'][] = [
						'row' => $index + 2, // +2 por header y índice 0
						'error' => 'Falta nombre o documento',
						'data' => $row
					];
					continue;
				}

				// Procesar la venta
				$result = $this->process_sale($row, $vendor_override, $blocked_products);

				if ($result['success']) {
					$results['created']++;
					$results['details'][] = [
						'row' => $index + 2,
						'status' => 'success',
						'budget_id' => $result['budget_id'],
						'client_id' => $result['client_id'],
						'data' => $row
					];

					// Intentar escribir el budget_id de vuelta al sheet (opcional)
					$this->write_budget_to_sheet($sheetId, $gid, $index, $result['budget_id']);
				} else {
					$results['errors']++;
					$results['details'][] = [
						'row' => $index + 2,
						'status' => 'error',
						'error' => $result['error'],
						'data' => $row
					];
				}

				$results['processed']++;
			}

			return $this->json_response(200, [
				'ok' => true,
				'summary' => $results,
				'total_rows' => count($rows),
				'message' => "Procesados: {$results['processed']}, Creados: {$results['created']}, Errores: {$results['errors']}, Omitidos: {$results['skipped']}"
			]);

		} catch (Exception $e) {
			return $this->json_response(500, ['error' => $e->getMessage()]);
		}
	}

	/**
	 * Procesa una venta individual
	 */
	private function process_sale($row, $vendor_override = null, $blocked_products = [])
	{
		try {
			date_default_timezone_set("America/Bogota");

			// 0. Resolver vendedor real del sheet
			$vendor_id = $vendor_override ?? $this->parse_vendor($row) ?? $this->default_vendor;

			// 1. Parsear dirección
			$address_parts = $this->parse_address($row['direccion'] ?? '');

			// 2. Buscar o crear cliente
			$client = $this->clients_model->getClientByIdNum($row['documento']);

			if (empty($client)) {
				// Crear nuevo cliente
				$client_data = [
					'idNum' => $row['documento'],
					'name' => $row['nombre'],
					'email' => $row['email'] ?? '',
					'phone' => $row['celular'] ?? '',
					'cellphone' => $row['celular'] ?? '',
					'address' => $address_parts['full_address'],
					'city' => $address_parts['city'],
					'state' => $address_parts['state'],
					'vendor' => $vendor_id,
					'retail' => 1,
					'rate' => 0,
					'f_id' => $this->clients_model->getHighestClientFid()->next_fid + 1,
				];

				if (!$this->clients_model->save($client_data)) {
					return ['success' => false, 'error' => 'No se pudo crear el cliente'];
				}

				$client_id = $this->db->insert_id();
			} else {
				$client_id = $client->idClient;

				// Actualizar datos del cliente si es necesario
				$update_data = [
					'cellphone' => $row['celular'] ?? $client->cellphone,
					'address' => $address_parts['full_address'] ?: $client->address,
					'city' => $address_parts['city'] ?: $client->city,
					'state' => $address_parts['state'] ?: $client->state,
				];
				$this->clients_model->update($client_id, $update_data);
			}

			// 3. Parsear producto(s)
			$product_info = $this->parse_product($row);

			if (!$product_info['found']) {
				$producto_text = $row['productos'] ?? $row['modulos'] ?? 'desconocido';
				$codigo_buscado = $product_info['searched_code'] ?? 'desconocido';
				return [
					'success' => false,
					'error' => "⚠️ Producto NO encontrado en BD: '{$producto_text}' (código buscado: {$codigo_buscado}). NO se creó presupuesto. El sistema NUNCA crea productos automáticamente."
				];
			}

			// 3b. Verificar productos agotados
			if (!empty($blocked_products)) {
				foreach ($product_info['products'] as $p) {
					$code_upper = strtoupper($p['product_code']);
					if (in_array($code_upper, $blocked_products)) {
						return [
							'success' => false,
							'error' => "⚠️ Producto AGOTADO: {$p['product_code']}. No se creó el presupuesto."
						];
					}
				}
			}

			// 4. Parsear tipo de envío
			$delivery_type_id = $this->parse_delivery_type($row);
			$delivery = $this->dropshipping_model->getDelivery($delivery_type_id);
			$delivery_name = !empty($delivery) ? $delivery->name : 'Interrapidisimo';

			// 5. Crear presupuesto
			$budget_data = [
				'clientId' => $client_id,
				'vendorId' => $vendor_id,
				'storeId' => $this->default_store,
				'total' => intval(preg_replace('/[^0-9]/', '', $row['total'] ?? '0')),
				'date' => date('Y-m-d H:i:s'),
				'state' => 0,
				'e_commerce' => 1,
				'list_price' => 0,
				'hasIva' => $this->default_iva,
				'iva' => 8,
				'comments' => $this->build_comments($row, $delivery_name, $product_info['products']),
			];

			if (!$this->budgets_model->save($budget_data)) {
				return ['success' => false, 'error' => 'No se pudo crear el presupuesto'];
			}

			$budget_id = $this->budgets_model->lastID();

			// 6. Crear detalle(s) - precio unitario calculado del total del sheet
			$sheet_total = $budget_data['total'];
			$total_qty = 0;
			foreach ($product_info['products'] as $p) {
				$total_qty += $p['quantity'];
			}
			$unit_price = ($total_qty > 0) ? floor($sheet_total / $total_qty) : $sheet_total;

			$product_codes = [];
			$sum_so_far = 0;
			$num_products = count($product_info['products']);

			foreach ($product_info['products'] as $i => $product) {
				// Último producto recibe el residuo para que siempre cuadre
				if ($i === $num_products - 1) {
					$line_total = $sheet_total - $sum_so_far;
					$line_unit = ($product['quantity'] > 0) ? round($line_total / $product['quantity']) : $line_total;
				} else {
					$line_unit = $unit_price;
					$line_total = $line_unit * $product['quantity'];
				}
				$sum_so_far += $line_total;

				$detail_data = [
					'budgetId' => $budget_id,
					'productId' => $product['product_code'],
					'quantity' => $product['quantity'],
					'unit' => $line_unit,
					'base' => $line_unit,
					'total' => $line_total
				];

				$this->budgets_model->save_detail($detail_data);
				$product_codes[] = $product['product_code'] . ' x' . $product['quantity'] . ' @$' . number_format($line_unit);
			}

			return [
				'success' => true,
				'budget_id' => $budget_id,
				'client_id' => $client_id,
				'products' => $product_codes
			];

		} catch (Exception $e) {
			return ['success' => false, 'error' => $e->getMessage()];
		}
	}

	/**
	 * Parsea la descripción del producto y encuentra el código correcto
	 * Ahora soporta múltiples productos en una misma fila
	 * Retorna array de productos con sus cantidades
	 */
	private function parse_product($row)
	{
		// Extraer información básica - SOPORTE NUEVA ESTRUCTURA DEL SHEET
		$productos_text = strtolower($row['productos'] ?? $row['modulos'] ?? '');
		$color_text = strtolower($row['color'] ?? '');
		$voltaje = trim($row['voltaje'] ?? '');
		if (empty($voltaje)) $voltaje = '12V';
		$cantidad_total = intval(preg_replace('/[^0-9]/', '', $row['cantidad'] ?? '1')) ?: 1;

		// ── PASO 1: Buscar producto especial (no-LED) ──
		foreach ($this->product_map as $keyword => $code) {
			if (strpos($productos_text, $keyword) !== false) {
				// Producto especial encontrado - buscar directamente en BD
				$product = $this->products_model->getProduct($code);
				if (empty($product)) {
					return ['found' => false, 'searched_code' => $code, 'products' => []];
				}
				return [
					'found' => true,
					'products' => [[
						'product_code' => $code,
						'quantity' => $cantidad_total,
						'price' => $product->price,
						'price_base' => $product->price_base,
						'color_name' => $productos_text
					]]
				];
			}
		}

		// ── PASO 2: Detectar tipo de módulo LED ──
		if (strpos($productos_text, 'alta potencia') !== false) {
			$num_leds = '2835';
		} else {
			preg_match('/(\d+)\s*led/i', $productos_text, $matches);
			$num_leds = $matches[1] ?? '6';
		}

		// Limpiar voltaje (ej: "12 Voltios" → "12V")
		$voltaje = preg_replace('/[^0-9]/', '', $voltaje) . 'V';

		// ── PASO 3: Detectar múltiples productos ──
		$products = [];

		// 3a: Productos completos separados por coma con voltaje propio
		// Ej: "40 módulos 6LED morados 12V, 40 módulos 6LED azul oscuro 24V"
		$segments = preg_split('/,/', $productos_text);
		if (count($segments) > 1) {
			foreach ($segments as $seg) {
				$seg = trim($seg);
				if (empty($seg)) continue;

				// Tipo LED del segmento
				$seg_num_leds = $num_leds;
				if (strpos($seg, 'alta potencia') !== false) {
					$seg_num_leds = '2835';
				} elseif (preg_match('/(\d+)\s*led/i', $seg, $m)) {
					$seg_num_leds = $m[1];
				}

				// Voltaje propio del segmento
				$seg_voltaje = $voltaje;
				if (preg_match('/(\d+)\s*v(?:oltios)?\b/i', $seg, $m)) {
					$seg_voltaje = $m[1] . 'V';
				}

				// Cantidad (primer número del segmento)
				$seg_qty = 1;
				if (preg_match('/^(\d+)/', $seg, $m)) {
					$seg_qty = intval($m[1]);
				}

				// Color
				$seg_color_letter = null;
				$seg_color_name = '';
				foreach ($this->color_map as $cname => $cletter) {
					if (strpos($seg, $cname) !== false) {
						$seg_color_letter = $cletter;
						$seg_color_name = $cname;
						break;
					}
				}

				if ($seg_color_letter) {
					$products[] = [
						'num_leds' => $seg_num_leds,
						'voltaje' => $seg_voltaje,
						'color_letter' => $seg_color_letter,
						'quantity' => $seg_qty,
						'color_name' => $seg_color_name
					];
				}
			}
		}

		// 3b: Si no hay multi-segmento, buscar patrones de colores
		if (count($products) === 0) {
			$products = $this->parse_multiple_colors($productos_text, $num_leds, $voltaje, $cantidad_total);
		}

		if (count($products) === 0) {
			$products = $this->parse_multiple_colors($color_text, $num_leds, $voltaje, $cantidad_total);
		}

		// Si no se detectaron múltiples productos, usar color simple
		if (count($products) === 0) {
			$color_letter = 'E'; // Default azul
			foreach ($this->color_map as $color_name => $letter) {
				if (strpos($color_text, $color_name) !== false) {
					$color_letter = $letter;
					break;
				}
			}

			$products[] = [
				'num_leds' => $num_leds,
				'voltaje' => $voltaje,
				'color_letter' => $color_letter,
				'quantity' => $cantidad_total,
				'color_name' => $color_text
			];
		}

		// Buscar cada producto en BD
		$result_products = [];
		foreach ($products as $prod) {
			// Construcción del código: 2835 NO lleva "LED", pero 6, 12, 3 sí
			if ($prod['num_leds'] === '2835') {
				$product_code = "{$prod['num_leds']}-{$prod['voltaje']}-{$prod['color_letter']}";
			} else {
				$product_code = "{$prod['num_leds']}LED-{$prod['voltaje']}-{$prod['color_letter']}";
			}

			$product = $this->products_model->getProduct($product_code);

			if (empty($product)) {
				return [
					'found' => false,
					'searched_code' => $product_code,
					'products' => []
				];
			}

			$result_products[] = [
				'product_code' => $product_code,
				'quantity' => $prod['quantity'],
				'price' => $product->price,
				'price_base' => $product->price_base,
				'color_name' => $prod['color_name']
			];
		}

		return [
			'found' => true,
			'products' => $result_products
		];
	}

	/**
	 * Parsea múltiples colores/cantidades del campo color
	 * Ejemplos: "20 blancos y 20 azules", "30 azul, 10 blanco"
	 */
	private function parse_multiple_colors($color_text, $num_leds, $voltaje, $cantidad_total)
	{
		$products = [];

		// Patrón 1: "20 blancos y 20 azules"
		// Buscar patrón: número + color + "y" + número + color
		if (preg_match('/(\d+)\s+(\w+)\s+y\s+(\d+)\s+(\w+)/i', $color_text, $matches)) {
			$qty1 = intval($matches[1]);
			$color1 = strtolower($matches[2]);
			$qty2 = intval($matches[3]);
			$color2 = strtolower($matches[4]);

			$letter1 = $this->map_color_to_letter($color1);
			$letter2 = $this->map_color_to_letter($color2);

			// Solo agregar si ambos colores son válidos
			if ($letter1 !== null && $letter2 !== null) {
				$products[] = [
					'num_leds' => $num_leds,
					'voltaje' => $voltaje,
					'color_letter' => $letter1,
					'quantity' => $qty1,
					'color_name' => $color1
				];

				$products[] = [
					'num_leds' => $num_leds,
					'voltaje' => $voltaje,
					'color_letter' => $letter2,
					'quantity' => $qty2,
					'color_name' => $color2
				];

				return $products;
			}
		}

		// Patrón 2: "30x azul, 10x blanco" o "30 azul, 10 blanco"
		if (preg_match_all('/(\d+)\s*x?\s*(\w+)/i', $color_text, $matches, PREG_SET_ORDER)) {
			if (count($matches) > 1) {
				foreach ($matches as $match) {
					$qty = intval($match[1]);
					$color = strtolower($match[2]);
					$letter = $this->map_color_to_letter($color);

					// Solo agregar si el color es válido
					if ($letter !== null) {
						$products[] = [
							'num_leds' => $num_leds,
							'voltaje' => $voltaje,
							'color_letter' => $letter,
							'quantity' => $qty,
							'color_name' => $color
						];
					}
				}
				if (count($products) > 0) return $products;
			}
		}

		// Patrón 3: Saltos de línea o pipe
		$lines = preg_split('/[\n\r\|]/', $color_text);
		if (count($lines) > 1) {
			foreach ($lines as $line) {
				$line = trim($line);
				if (empty($line)) continue;

				// Buscar cantidad y color en cada línea
				if (preg_match('/(\d+)\s*x?\s*(\w+)/i', $line, $match)) {
					$qty = intval($match[1]);
					$color = strtolower($match[2]);
					$letter = $this->map_color_to_letter($color);

					// Solo agregar si el color es válido
					if ($letter !== null) {
						$products[] = [
							'num_leds' => $num_leds,
							'voltaje' => $voltaje,
							'color_letter' => $letter,
							'quantity' => $qty,
							'color_name' => $color
						];
					}
				}
			}
			if (count($products) > 0) return $products;
		}

		return $products;
	}

	/**
	 * Mapea un color a su letra correspondiente
	 * Retorna null si no encuentra coincidencia
	 */
	private function map_color_to_letter($color, $return_default = false)
	{
		foreach ($this->color_map as $color_name => $letter) {
			if (strpos($color, $color_name) !== false) {
				return $letter;
			}
		}
		return $return_default ? 'E' : null;
	}

	/**
	 * Resuelve el vendedor real del sheet
	 */
	private function parse_vendor($row)
	{
		$vendedor_text = strtolower(trim($row['vendedor'] ?? ''));
		if (empty($vendedor_text)) return null;

		// Normalizar: quitar tildes
		$trans = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n'];
		$vendedor_text = strtr($vendedor_text, $trans);

		foreach ($this->vendor_map as $key => $id) {
			if (strpos($vendedor_text, $key) !== false) {
				return $id;
			}
		}

		return null; // No encontrado, usará el default
	}

	/**
	 * Parsea el tipo de envío desde el row
	 */
	private function parse_delivery_type($row)
	{
		// Soporte para varias estructuras de columna: tipoenvio, tipo_envio, tipo_de_envio
		$tipo_envio = strtolower($row['tipoenvio'] ?? $row['tipo_envio'] ?? $row['tipo_de_envio'] ?? '');

		// Buscar en el mapeo
		foreach ($this->delivery_map as $key => $id) {
			if (strpos($tipo_envio, $key) !== false) {
				return $id;
			}
		}

		// Default: Envío gratis
		return $this->default_delivery_type;
	}

	/**
	 * Parsea la dirección para extraer ciudad y departamento
	 */
	private function parse_address($direccion)
	{
		// Formato esperado: "Barrio X, Ciudad, Departamento. Detalles adicionales"
		$parts = explode(',', $direccion);

		$city = '';
		$state = '';
		$full_address = trim($direccion);

		if (count($parts) >= 3) {
			// Última parte suele ser departamento
			$last_part = trim($parts[count($parts) - 1]);
			// Dividir por punto para quitar detalles adicionales
			$state_parts = explode('.', $last_part);
			$state = trim($state_parts[0]);

			// Penúltima parte es la ciudad
			$city = trim($parts[count($parts) - 2]);
		}elseif (count($parts) == 2) {
			$city = trim($parts[1]);
		}

		return [
			'full_address' => $full_address,
			'city' => $city,
			'state' => $state
		];
	}

	/**
	 * Construye el comentario del presupuesto
	 */
	private function build_comments($row, $delivery_name = '', $products = [])
	{
		$comments = [];

		// PRIMERO: Tipo de envío
		$tipo_envio_raw = strtolower(trim($row['tipoenvio'] ?? $row['tipo_envio'] ?? $row['tipo_de_envio'] ?? ''));
		if (strpos($tipo_envio_raw, 'gratis') !== false && strpos($tipo_envio_raw, 'no') === false) {
			$comments[] = "ENVIO GRATIS";
		} else {
			$comments[] = "EL CLIENTE PAGA EL ENVIO";
		}

		// Productos
		if (!empty($products) && count($products) > 1) {
			$comments[] = "Productos:";
			foreach ($products as $prod) {
				$comments[] = "  - {$prod['product_code']} x{$prod['quantity']} ({$prod['color_name']})";
			}
		} else {
			$producto = $row['productos'] ?? $row['modulos'] ?? '';
			if (!empty($producto)) {
				$comments[] = "Producto: {$producto}";
			}

			if (!empty($row['color'])) {
				$comments[] = "Color: {$row['color']}";
			}

			if (!empty($row['voltaje'])) {
				$comments[] = "Voltaje: {$row['voltaje']}";
			}
		}

		if (!empty($row['direccion'])) {
			$comments[] = "Dirección: {$row['direccion']}";
		}

		if (!empty($row['nombre'])) {
			$comments[] = "Cliente: {$row['nombre']}";
		}

		if (!empty($row['celular'])) {
			$comments[] = "Tel: {$row['celular']}";
		}

		return implode(' | ', $comments);
	}

	/**
	 * Descarga CSV por cURL
	 */
	private function http_get($url, $timeout = 20)
	{
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => $timeout,
			CURLOPT_TIMEOUT        => $timeout,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_USERAGENT      => 'Dropshipping-Bot-Importer/1.0'
		]);
		$body = curl_exec($ch);
		if ($body === false) {
			$err = curl_error($ch);
			curl_close($ch);
			throw new Exception('cURL error: '.$err);
		}
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($code >= 400) {
			throw new Exception('HTTP '.$code.' descargando CSV');
		}
		return $body;
	}

	/**
	 * Parsea CSV a array asociativo
	 */
	private function parse_csv_assoc($csv, $limit = 0)
	{
		$fh = fopen('php://temp', 'w+');
		fwrite($fh, $csv);
		rewind($fh);

		$headers = [];
		$rows    = [];
		$line    = 0;

		while (($data = fgetcsv($fh, 0, ',')) !== false) {
			// Limpia BOM en primera celda si existe
			if ($line === 0 && isset($data[0])) {
				$data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
			}

			if ($line === 0) {
				foreach ($data as $h) {
					$headers[] = $this->normalize_header($h);
				}
			} else {
				$row = [];
				foreach ($headers as $i => $h) {
					$row[$h] = isset($data[$i]) ? trim((string)$data[$i]) : '';
				}
				// Descarta filas totalmente vacías
				$allEmpty = true;
				foreach ($row as $v) { if ($v !== '') { $allEmpty = false; break; } }
				if (!$allEmpty) {
					$rows[] = $row;
				}
			}

			$line++;
			if ($limit > 0 && count($rows) >= $limit) break;
		}

		fclose($fh);
		return [$headers, $rows];
	}

	/**
	 * Normaliza encabezados de CSV
	 */
	private function normalize_header($h)
	{
		$h = mb_strtolower(trim((string)$h), 'UTF-8');

		// Reemplaza tildes
		$trans = [
			'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
			'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u',
			'ñ'=>'n'
		];
		$h = strtr($h, $trans);

		// Espacios y símbolos → _
		$h = preg_replace('/[^a-z0-9]+/','_', $h);
		$h = trim($h, '_');

		return $h;
	}

	/**
	 * Escribe el budget_id de vuelta al Google Sheet vía Apps Script
	 * Simple POST a la URL del Apps Script deployado
	 */
	private function write_budget_to_sheet($sheetId, $gid, $row_index, $budget_id)
	{
		try {
			if (empty($this->apps_script_url)) return false;

			$sheet_row = $row_index + 2; // +2 por header + índice base 0

			$body = json_encode([
				'updates' => [
					['row' => $sheet_row, 'budget_id' => $budget_id]
				]
			]);

			$ch = curl_init($this->apps_script_url);
			curl_setopt_array($ch, [
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $body,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
				CURLOPT_TIMEOUT => 15,
			]);

			$response = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			return $httpCode === 200;

		} catch (Exception $e) {
			error_log('Error escribiendo al Google Sheet: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Parsea archivo Excel de productos agotados subido por POST
	 * Retorna array de códigos de producto en mayúsculas
	 * Soporta .csv y .xlsx (sin dependencia de PhpSpreadsheet)
	 */
	private function parse_blocked_file()
	{
		if (empty($_FILES['blocked_file']['tmp_name'])) return [];

		try {
			$file = $_FILES['blocked_file']['tmp_name'];
			$filename = $_FILES['blocked_file']['name'];
			$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

			$blocked = [];

			if ($ext === 'csv') {
				$fh = fopen($file, 'r');
				$header_skipped = false;
				while (($data = fgetcsv($fh)) !== false) {
					if (!$header_skipped) { $header_skipped = true; continue; }
					$code = strtoupper(trim($data[0] ?? ''));
					if (!empty($code)) $blocked[] = $code;
				}
				fclose($fh);
			} elseif ($ext === 'xlsx' || $ext === 'xls') {
				$blocked = $this->parse_xlsx_column_a($file);
			}

			return $blocked;
		} catch (Exception $e) {
			error_log('Error parseando archivo de agotados: ' . $e->getMessage());
			return [];
		}
	}

	/**
	 * Lee columna A de un archivo .xlsx usando ZipArchive + SimpleXML
	 * No requiere PhpSpreadsheet
	 */
	private function parse_xlsx_column_a($file)
	{
		$codes = [];

		$zip = new ZipArchive();
		if ($zip->open($file) !== true) return $codes;

		// Leer strings compartidos
		$strings = [];
		$shared = $zip->getFromName('xl/sharedStrings.xml');
		if ($shared) {
			$xml = simplexml_load_string($shared);
			foreach ($xml->si as $si) {
				$strings[] = (string)$si->t;
			}
		}

		// Leer sheet1
		$sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
		$zip->close();
		if (!$sheet) return $codes;

		$xml = simplexml_load_string($sheet);
		$row_num = 0;

		foreach ($xml->sheetData->row as $row) {
			$row_num++;
			if ($row_num === 1) continue; // Skip header

			// Buscar celda A de esta fila
			foreach ($row->c as $cell) {
				$ref = (string)$cell['r'];
				// Solo columna A (A2, A3, A4...)
				if (preg_match('/^A\d+$/', $ref)) {
					$type = (string)$cell['t'];
					if ($type === 's') {
						// String compartido
						$idx = intval((string)$cell->v);
						$val = isset($strings[$idx]) ? $strings[$idx] : '';
					} else {
						$val = (string)$cell->v;
					}
					$val = strtoupper(trim($val));
					if (!empty($val)) $codes[] = $val;
					break;
				}
			}
		}

		return $codes;
	}

	/**
	 * Respuesta JSON
	 */
	private function json_response($status, $data) {
		return $this->output->set_status_header($status)
			->set_content_type('application/json')
			->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	}

	// ══════════════════════════════════════════════════════════════════════
	// ENDPOINTS PARA GESTIONAR PRODUCTOS AGOTADOS PERSISTENTES
	// ══════════════════════════════════════════════════════════════════════

	/**
	 * Ruta al archivo JSON donde se guardan los productos agotados
	 */
	private function get_blocked_file_path()
	{
		return APPPATH . 'cache/blocked_products.json';
	}

	/**
	 * GET: /sisvent/rest/botimport/getBlocked
	 * Retorna la lista de productos agotados guardados
	 */
	public function getBlocked()
	{
		$blocked = $this->load_blocked_products();
		return $this->json_response(200, [
			'ok' => true,
			'blocked' => $blocked,
			'count' => count($blocked)
		]);
	}

	/**
	 * POST: /sisvent/rest/botimport/uploadBlocked
	 * Sube un archivo Excel/CSV y guarda los codigos en el servidor
	 */
	public function uploadBlocked()
	{
		$new_codes = $this->parse_blocked_file();

		if (empty($new_codes) && !empty($_FILES['blocked_file']['name'])) {
			return $this->json_response(400, [
				'ok' => false,
				'error' => 'No se pudieron extraer codigos del archivo'
			]);
		}

		if (empty($new_codes)) {
			return $this->json_response(400, [
				'ok' => false,
				'error' => 'No se recibio archivo'
			]);
		}

		// Cargar existentes y agregar nuevos (sin duplicados)
		$existing = $this->load_blocked_products();
		$merged = array_unique(array_merge($existing, $new_codes));
		sort($merged);

		$this->save_blocked_products($merged);

		return $this->json_response(200, [
			'ok' => true,
			'message' => 'Productos agotados actualizados',
			'added' => count($new_codes),
			'total' => count($merged),
			'blocked' => $merged
		]);
	}

	/**
	 * POST: /sisvent/rest/botimport/clearBlocked
	 * Limpia todos los productos agotados
	 */
	public function clearBlocked()
	{
		$this->save_blocked_products([]);
		return $this->json_response(200, [
			'ok' => true,
			'message' => 'Lista de productos agotados limpiada'
		]);
	}

	/**
	 * POST: /sisvent/rest/botimport/removeBlocked
	 * Elimina un codigo especifico de la lista de agotados
	 * Parametro: code (codigo a eliminar)
	 */
	public function removeBlocked()
	{
		$code = strtoupper(trim($this->input->post('code') ?? ''));
		if (empty($code)) {
			return $this->json_response(400, ['ok' => false, 'error' => 'Codigo requerido']);
		}

		$blocked = $this->load_blocked_products();
		$blocked = array_values(array_filter($blocked, function($c) use ($code) {
			return $c !== $code;
		}));
		$this->save_blocked_products($blocked);

		return $this->json_response(200, [
			'ok' => true,
			'message' => "Codigo {$code} eliminado",
			'blocked' => $blocked
		]);
	}

	/**
	 * Carga productos agotados del archivo JSON
	 */
	private function load_blocked_products()
	{
		$file = $this->get_blocked_file_path();
		if (!file_exists($file)) return [];

		$content = file_get_contents($file);
		$data = json_decode($content, true);
		return is_array($data) ? $data : [];
	}

	/**
	 * Guarda productos agotados en archivo JSON
	 */
	private function save_blocked_products($codes)
	{
		$file = $this->get_blocked_file_path();
		$dir = dirname($file);
		if (!is_dir($dir)) mkdir($dir, 0755, true);

		file_put_contents($file, json_encode(array_values($codes), JSON_PRETTY_PRINT));
	}
}
