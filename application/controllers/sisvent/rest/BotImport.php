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

			// Permitir llamadas desde cron con key válida (sin sesión)
			$cron_key = $this->input->get('cron_key');
			$is_cron = ($cron_key === 'sisvent_cron_2024_tracking');

			if (!$is_cron) {
				// Verificar login
				if (!is_logged_in()) {
					return $this->json_response(401, ['ok' => false, 'error' => 'Debes iniciar sesión']);
				}

				$user_data = $this->session->userdata('user_data');
				$is_super_admin = ($user_data['role'] == 1);
			} else {
				// Modo cron: tratar como super admin
				$user_data = ['uname' => 'cron', 'role' => 1];
				$is_super_admin = true;
			}

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

				// Fallback: si no hay documento usa celular sin prefijo 57
				$this->_resolveDocumento($row);

				// Validar datos mínimos (documento ya puede haber sido llenado desde celular)
				if (empty($row['nombre']) || empty($row['documento'])) {
					$results['errors']++;
					$results['details'][] = [
						'row' => $index + 2, // +2 por header y índice 0
						'error' => 'Falta nombre o documento/celular',
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
	 * Si el payload no trae documento usa el celular sin el prefijo "57" como fallback.
	 * Muta el array recibido y devuelve el documento resuelto (string, posiblemente vacio).
	 * Setea $data['_doc_is_fallback'] = true cuando el documento provino del celular,
	 * para que el resto del flujo trate ese idNum como reemplazable cuando llegue
	 * un documento real.
	 */
	private function _resolveDocumento(&$data)
	{
		$doc = isset($data['documento']) ? trim((string)$data['documento']) : '';
		$data['_doc_is_fallback'] = false;
		if ($doc === '' && !empty($data['celular'])) {
			$doc = Clients_model::normalizePhone($data['celular']);
			$data['documento'] = $doc;
			$data['_doc_is_fallback'] = true;
		}
		return $doc;
	}

	/**
	 * Devuelve true si el idNum guardado de un cliente parece ser un "fallback"
	 * (es decir, igual al celular sin prefijo 57, sin documento real). Lo usamos
	 * para decidir si reemplazar el idNum cuando llega un documento real.
	 */
	private function _isFallbackIdNum($client)
	{
		if (empty($client) || empty($client->idNum)) return false;
		$idnum_digits = preg_replace('/[^0-9]/', '', (string)$client->idNum);
		if ($idnum_digits === '') return false;
		$cell_norm = Clients_model::normalizePhone($client->cellphone ?? '');
		$phone_norm = Clients_model::normalizePhone($client->phone ?? '');
		return ($idnum_digits === $cell_norm || $idnum_digits === $phone_norm);
	}

	/**
	 * Normaliza un texto de alias (UPPER + trim + collapse whitespace) para lookup.
	 */
	private function _normalizeAlias($text)
	{
		$t = strtoupper(trim((string)$text));
		$t = preg_replace('/\s+/', ' ', $t);
		return $t;
	}

	/**
	 * Resuelve el codigo real de un producto.
	 * 1) Intenta con el codigo tal cual.
	 * 2) Si no existe, busca en bot_product_aliases por texto normalizado.
	 * Retorna el codigo real si encuentra, false si no.
	 */
	private function _resolveProductCode($input_code)
	{
		$codigo = strtoupper(trim((string)$input_code));
		if ($codigo === '') return false;

		$exact = $this->products_model->getProduct($codigo);
		if (!empty($exact)) return $codigo;

		$norm = $this->_normalizeAlias($input_code);
		if ($norm === '') return false;

		$alias = $this->db->where('alias_norm', $norm)->get('bot_product_aliases')->row();
		if (empty($alias)) return false;

		$real = $this->products_model->getProduct($alias->product_code);
		if (empty($real)) return false;

		$this->db->where('id', $alias->id)->set('hits', 'hits + 1', FALSE)->update('bot_product_aliases');

		return $alias->product_code;
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
			// Ledxury: el celular es la llave principal (todo viene por WhatsApp).
			// Buscamos primero por celular; si no aparece, fallback al documento.
			$celular_norm = Clients_model::normalizePhone($row['celular'] ?? '');
			$client = null;
			if ($celular_norm !== '') {
				$client = $this->clients_model->getClientByPhone($celular_norm);
			}
			if (empty($client) && !empty($row['documento'])) {
				$client = $this->clients_model->getClientByIdNum($row['documento']);
			}

			if (empty($client)) {
				// Crear nuevo cliente
				$client_data = [
					'idNum' => $row['documento'],
					'name' => $row['nombre'],
					'email' => $row['email'] ?? '',
					'phone' => $celular_norm,
					'cellphone' => $celular_norm,
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
					'cellphone' => $celular_norm ?: $client->cellphone,
					'address' => $address_parts['full_address'] ?: $client->address,
					'city' => $address_parts['city'] ?: $client->city,
					'state' => $address_parts['state'] ?: $client->state,
				];
				// Si el cliente existía sin documento y ahora el bot lo trae, lo guardamos
				if (empty($client->idNum) && !empty($row['documento'])) {
					$update_data['idNum'] = $row['documento'];
				}
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

			error_log("Agotados: Procesando archivo '{$filename}' (ext: {$ext}, size: " . filesize($file) . ")");

			$blocked = [];

			if ($ext === 'csv' || $ext === 'txt') {
				$fh = fopen($file, 'r');
				$header_skipped = false;
				while (($data = fgetcsv($fh)) !== false) {
					if (!$header_skipped) { $header_skipped = true; continue; }
					$code = strtoupper(trim($data[0] ?? ''));
					if (!empty($code)) $blocked[] = $code;
				}
				fclose($fh);
			} elseif ($ext === 'xlsx') {
				$blocked = $this->parse_xlsx_column_a($file);
			} elseif ($ext === 'xls') {
				// .xls (Excel 97-2003) no es compatible con ZipArchive
				// Intentar leerlo como CSV por si fue guardado con extensión incorrecta
				$fh = @fopen($file, 'r');
				if ($fh) {
					$firstBytes = fread($fh, 8);
					fclose($fh);
					// Verificar si es binario real de Excel (magic bytes: D0 CF 11 E0)
					if (substr($firstBytes, 0, 4) === "\xD0\xCF\x11\xE0") {
						error_log("Agotados: Archivo .xls en formato binario. Guardar como .xlsx");
						return [];
					}
				}
				// Si no es binario, intentar como CSV
				$fh = fopen($file, 'r');
				$header_skipped = false;
				while (($data = fgetcsv($fh)) !== false) {
					if (!$header_skipped) { $header_skipped = true; continue; }
					$code = strtoupper(trim($data[0] ?? ''));
					if (!empty($code)) $blocked[] = $code;
				}
				fclose($fh);
			}

			error_log("Agotados: Se extrajeron " . count($blocked) . " codigos");
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
		$result = $zip->open($file);
		if ($result !== true) {
			error_log("Agotados: ZipArchive no pudo abrir el archivo (error: {$result}). Puede que sea .xls y no .xlsx");
			return $codes;
		}

		// Leer strings compartidos
		$strings = [];
		$shared = $zip->getFromName('xl/sharedStrings.xml');
		if ($shared) {
			$xml = simplexml_load_string($shared);
			foreach ($xml->si as $si) {
				// Manejar texto simple: <si><t>valor</t></si>
				if (isset($si->t) && !isset($si->r)) {
					$strings[] = (string)$si->t;
				}
				// Manejar texto enriquecido: <si><r><t>parte1</t></r><r><t>parte2</t></r></si>
				elseif (isset($si->r)) {
					$text = '';
					foreach ($si->r as $run) {
						$text .= (string)$run->t;
					}
					$strings[] = $text;
				} else {
					$strings[] = '';
				}
			}
		}

		error_log("Agotados: SharedStrings cargados: " . count($strings));

		// Leer sheet1
		$sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
		$zip->close();
		if (!$sheet) {
			error_log("Agotados: No se encontro sheet1.xml");
			return $codes;
		}

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
					} elseif ($type === 'inlineStr') {
						// String inline
						$val = (string)$cell->is->t;
					} else {
						$val = (string)$cell->v;
					}
					$val = strtoupper(trim($val));
					if (!empty($val)) $codes[] = $val;
					break;
				}
			}
		}

		error_log("Agotados: Codigos extraidos de xlsx: " . count($codes) . " (filas: {$row_num})");
		return $codes;
	}

	// ══════════════════════════════════════════════════════════════════════
	// WEBHOOK: Recibir ventas directamente del bot via POST JSON
	// ══════════════════════════════════════════════════════════════════════

	/**
	 * POST: /sisvent/rest/botimport/receiveSale
	 *
	 * Recibe una venta del bot y crea el presupuesto automáticamente.
	 * Autenticación via header X-Api-Key.
	 *
	 * JSON esperado:
	 * {
	 *   "nombre": "Jhonatan Riascos",
	 *   "documento": "1087426350",
	 *   "celular": "3207820972",
	 *   "direccion": "Calle 5, Cali, Valle",
	 *   "tipoenvio": "envio gratis",
	 *   "productos": [
	 *     {"codigo": "6LED-12V-E", "cantidad": 20, "precio": 2500},
	 *     {"codigo": "6LED-12V-A", "cantidad": 20, "precio": 2500}
	 *   ]
	 * }
	 */
	public function receiveSale()
	{
		header('Content-Type: application/json');

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			return $this->json_response(405, ['ok' => false, 'error' => 'Método no permitido. Usa POST.']);
		}

		// 1. Autenticar por API key
		$api_key = $this->input->get_request_header('X-Api-Key', TRUE);
		if (empty($api_key)) {
			return $this->json_response(401, ['ok' => false, 'error' => 'Falta header X-Api-Key']);
		}

		$vendor = $this->db->select('idUser, name')
			->where('bot_api_key', $api_key)
			->where('deleted', 0)
			->get('users')->row();

		if (empty($vendor)) {
			return $this->json_response(401, ['ok' => false, 'error' => 'API key inválida']);
		}

		// 2. Leer JSON del body
		$payload = json_decode(file_get_contents('php://input'), true);
		if (empty($payload)) {
			return $this->json_response(400, ['ok' => false, 'error' => 'JSON inválido o vacío']);
		}

		// 3. Validar campos obligatorios
		// Fallback: si no hay documento usa celular sin prefijo 57
		$this->_resolveDocumento($payload);
		if (empty($payload['nombre']) || empty($payload['documento'])) {
			return $this->json_response(400, ['ok' => false, 'error' => 'Campos obligatorios: nombre y (documento o celular)']);
		}

		if (empty($payload['productos']) || !is_array($payload['productos'])) {
			return $this->json_response(400, ['ok' => false, 'error' => 'Campo productos debe ser un array con al menos un producto']);
		}

		// Validar cada producto
		foreach ($payload['productos'] as $i => $prod) {
			if (empty($prod['codigo'])) {
				return $this->json_response(400, ['ok' => false, 'error' => "Producto #" . ($i+1) . ": falta campo 'codigo'"]);
			}
			if (empty($prod['cantidad']) || intval($prod['cantidad']) <= 0) {
				return $this->json_response(400, ['ok' => false, 'error' => "Producto #" . ($i+1) . " ({$prod['codigo']}): cantidad debe ser mayor a 0"]);
			}
			if (!isset($prod['precio']) || floatval($prod['precio']) <= 0) {
				return $this->json_response(400, ['ok' => false, 'error' => "Producto #" . ($i+1) . " ({$prod['codigo']}): precio debe ser mayor a 0"]);
			}
		}

		// 4. Resolver vendedor
		$vendor_id = $vendor->idUser;
		if (!empty($payload['vendedor'])) {
			// Buscar vendedor por ID o por nombre en el vendor_map
			$vendedor_text = trim($payload['vendedor']);
			$vendedor_lower = strtolower($vendedor_text);

			// Primero buscar en el mapeo de nombres
			if (isset($this->vendor_map[$vendedor_lower])) {
				$vendor_id = $this->vendor_map[$vendedor_lower];
			} else {
				// Buscar directo por ID de usuario
				$found_vendor = $this->db->select('idUser')
					->where('idUser', $vendedor_text)
					->where('deleted', 0)
					->get('users')->row();
				if ($found_vendor) {
					$vendor_id = $found_vendor->idUser;
				}
			}
		}

		// 5. Guardar en cola
		$this->db->insert('bot_sales_queue', [
			'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
			'status' => 'processing',
			'vendor_id' => $vendor_id,
			'api_key' => $api_key,
		]);
		$queue_id = $this->db->insert_id();

		// 6. Procesar la venta
		$result = $this->process_webhook_sale($payload, $vendor_id);

		// 7. Actualizar cola con resultado
		if ($result['success']) {
			$this->db->where('id', $queue_id)->update('bot_sales_queue', [
				'status' => 'completed',
				'budget_id' => $result['budget_id'],
				'processed_at' => date('Y-m-d H:i:s'),
			]);

			return $this->json_response(200, [
				'ok' => true,
				'budget_id' => $result['budget_id'],
				'client_id' => $result['client_id'],
				'vendor_id' => $vendor_id,
				'productos' => $result['products'],
				'total' => $result['total'],
			]);
		} else {
			$this->db->where('id', $queue_id)->update('bot_sales_queue', [
				'status' => 'failed',
				'error_message' => $result['error'],
				'attempts' => 1,
				'processed_at' => date('Y-m-d H:i:s'),
			]);

			return $this->json_response(422, [
				'ok' => false,
				'queue_id' => $queue_id,
				'error' => $result['error'],
			]);
		}
	}

	/**
	 * Cron: reintenta ventas fallidas en bot_sales_queue.
	 * Llamar: /sisvent/rest/botimport/cronRetryFailed?cron_key=sisvent_cron_2024_tracking
	 */
	public function cronRetryFailed()
	{
		header('Content-Type: application/json');

		if ($this->input->get('cron_key') !== 'sisvent_cron_2024_tracking') {
			http_response_code(401);
			echo json_encode(['ok' => false, 'error' => 'Key inválida']);
			return;
		}

		date_default_timezone_set("America/Bogota");
		set_time_limit(300);

		$items = $this->db->select('id, payload, vendor_id, attempts')
			->where('status', 'failed')
			->where('attempts <', 5)
			->limit(50)
			->get('bot_sales_queue')->result();

		$recovered = 0; $still = 0;

		foreach ($items as $item) {
			$payload = json_decode($item->payload, true);
			if (empty($payload)) { $still++; continue; }

			// Si el payload original quedó con `nombre=''` (parser viejo no lo
			// extraía) pero tenemos el `raw` de la conversación y el `bot_id`,
			// re-procesamos vía _processPedidoConfirmado para aprovechar el
			// _smartExtractName actualizado. Cubre la deuda histórica de la cola.
			// cronMode=true → _processPedidoConfirmado no encola duplicados.
			if (empty($payload['nombre']) && !empty($payload['raw']) && !empty($payload['bot_id'])) {
				$this->load->model('builderbot_model');
				$botCfg = $this->builderbot_model->getConfig((int)$payload['bot_id']);
				if ($botCfg) {
					$phoneTry = $payload['phone'] ?? ($payload['celular'] ?? '');
					$threwHere = false;
					try {
						$bid = $this->_processPedidoConfirmado($payload['raw'], $phoneTry, $botCfg, true);
					} catch (Exception $e) {
						$bid = null; $threwHere = true;
					}
					if (!empty($bid)) {
						$this->db->where('id', $item->id)->update('bot_sales_queue', [
							'status'        => 'completed',
							'budget_id'     => $bid,
							'error_message' => null,
							'attempts'      => (int)$item->attempts + 1,
							'processed_at'  => date('Y-m-d H:i:s'),
						]);
						$recovered++;
						continue;
					}
					// Sin budget. Si _processPedidoConfirmado retornó null sin throw,
					// ya manejó el error; solo bumpear attempts y seguir al siguiente.
					if (!$threwHere) {
						$this->db->where('id', $item->id)->update('bot_sales_queue', [
							'attempts'     => (int)$item->attempts + 1,
							'processed_at' => date('Y-m-d H:i:s'),
						]);
						$still++;
						continue;
					}
					// Si threw, seguimos al flujo viejo abajo como último recurso.
				}
			}

			try {
				$result = $this->process_webhook_sale($payload, $item->vendor_id);
			} catch (Exception $e) {
				$result = ['success' => false, 'error' => $e->getMessage()];
			}

			if (!empty($result['success'])) {
				$this->db->where('id', $item->id)->update('bot_sales_queue', [
					'status' => 'completed',
					'budget_id' => $result['budget_id'],
					'error_message' => null,
					'attempts' => (int)$item->attempts + 1,
					'processed_at' => date('Y-m-d H:i:s'),
				]);
				$recovered++;
			} else {
				$this->db->where('id', $item->id)->update('bot_sales_queue', [
					'error_message' => $result['error'] ?? 'Error desconocido',
					'attempts' => (int)$item->attempts + 1,
					'processed_at' => date('Y-m-d H:i:s'),
				]);
				$still++;
			}
		}

		echo json_encode([
			'ok' => true,
			'processed' => count($items),
			'recovered' => $recovered,
			'still_failed' => $still,
			'timestamp' => date('Y-m-d H:i:s'),
		]);
	}

	/**
	 * Procesa una venta recibida por webhook.
	 * Los productos vienen con código directo de la BD (sin parseo de texto).
	 */
	private function process_webhook_sale($data, $vendor_id)
	{
		$gotLock = false; $lockKey = null;
		try {
			date_default_timezone_set("America/Bogota");

			// 1. Parsear dirección
			$address_parts = $this->parse_address($data['direccion'] ?? '');

			// 2. Buscar o crear cliente
			// Ledxury: celular = llave principal. Documento = fallback.
			$celular_norm = Clients_model::normalizePhone($data['celular'] ?? '');

			// === DEDUP GUARD: GET_LOCK por (vendor + celular + total) y check de
			// presupuesto duplicado en últimos 60 min. Evita race conditions cuando dos
			// webhooks llegan al mismo segundo, y duplicados cuando el bot reenvía la fila. ===
			$total_pre = (int) preg_replace('/[^0-9]/', '', (string)($data['total'] ?? ''));
			if ($total_pre <= 0 && !empty($data['productos']) && is_array($data['productos'])) {
				foreach ($data['productos'] as $p) {
					$total_pre += (int)($p['cantidad'] ?? 0) * (int)($p['precio'] ?? 0);
				}
			}
			if ($celular_norm !== '' && $total_pre > 0) {
				$lockKey = 'led_pws_' . md5($vendor_id . '|' . $celular_norm . '|' . $total_pre);
				$lockRow = $this->db->query("SELECT GET_LOCK(?, 8) AS got", array($lockKey))->row();
				$gotLock = $lockRow && (int)$lockRow->got === 1;

				$existing = $this->db->select('budgets.idBudget')
					->from('budgets')
					->join('clients', 'clients.idClient = budgets.clientId', 'left')
					->where('budgets.vendorId', $vendor_id)
					->where('budgets.total', $total_pre)
					->where('budgets.date >=', date('Y-m-d H:i:s', strtotime('-60 minutes')))
					->like('clients.cellphone', $celular_norm, 'both')
					->where('budgets.deleted', 0)
					->order_by('budgets.idBudget', 'DESC')
					->limit(1)
					->get()->row();
				if ($existing) {
					if ($gotLock) $this->db->query("SELECT RELEASE_LOCK(?)", array($lockKey));
					return ['success' => true, 'budget_id' => (int)$existing->idBudget, 'duplicate' => true];
				}
			}

			// Búsqueda de cliente — orden depende de si el documento es real o fallback:
			// - documento REAL (lo dijo el cliente): buscar por documento PRIMERO (más estable
			//   que el celular, que puede cambiar). Si no encuentra, fallback a celular.
			// - documento FALLBACK (= celular): buscar por celular primero (es lo mismo que
			//   por documento en este caso) para no confundirnos con clientes que casualmente
			//   tengan ese número como idNum real.
			$doc_is_fallback = !empty($data['_doc_is_fallback']);
			$client = null;
			if (!$doc_is_fallback && !empty($data['documento'])) {
				$client = $this->clients_model->getClientByIdNum($data['documento']);
			}
			if (empty($client) && $celular_norm !== '') {
				$client = $this->clients_model->getClientByPhone($celular_norm);
			}
			if (empty($client) && $doc_is_fallback && !empty($data['documento'])) {
				$client = $this->clients_model->getClientByIdNum($data['documento']);
			}

			if (empty($client)) {
				$client_data = [
					'idNum' => $data['documento'],
					'name' => $data['nombre'],
					'email' => $data['email'] ?? '',
					'phone' => $celular_norm,
					'cellphone' => $celular_norm,
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

				$update_data = [];
				if ($celular_norm !== '') $update_data['cellphone'] = $celular_norm;
				if (!empty($address_parts['full_address'])) $update_data['address'] = $address_parts['full_address'];
				if (!empty($address_parts['city'])) $update_data['city'] = $address_parts['city'];
				if (!empty($address_parts['state'])) $update_data['state'] = $address_parts['state'];

				// Reemplazar idNum guardado cuando: (a) está vacío, o (b) era un
				// fallback (= celular del propio cliente) y ahora llega documento real.
				if (!$doc_is_fallback && !empty($data['documento'])) {
					if (empty($client->idNum) || $this->_isFallbackIdNum($client)) {
						$update_data['idNum'] = $data['documento'];
					}
				}
				if (!empty($update_data)) {
					$this->clients_model->update($client_id, $update_data);
				}
			}

			// 3. Validar todos los productos existen en BD y verificar agotados.
			// IMPORTANTE: 'productos' DEBE ser un array no vacío. Si llega como string
			// (caso típico: payload de _enqueueFailedWebhookSale con productos extraídos
			// como texto), abortamos con error en vez de crear un budget vacío.
			if (!isset($data['productos']) || !is_array($data['productos']) || count($data['productos']) === 0) {
				if ($gotLock && $lockKey) $this->db->query("SELECT RELEASE_LOCK(?)", array($lockKey));
				return ['success' => false, 'error' => 'Payload sin productos estructurados — requiere reprocesamiento manual'];
			}
			$blocked_products = $this->load_blocked_products();
			$total = 0;
			$product_lines = [];
			// Acumulamos warnings de precio anómalo para alertar al vendedor en
			// los comments del presupuesto. No bloqueamos para no perder ventas;
			// el vendedor decide si aprueba o corrige antes de facturar.
			$price_warnings = [];

			foreach ($data['productos'] as $prod) {
				$input_code = isset($prod['codigo']) ? $prod['codigo'] : '';
				$cantidad = intval($prod['cantidad']);
				$precio = floatval($prod['precio']);

				// Resolver codigo: exacto -> alias en bot_product_aliases
				$codigo = $this->_resolveProductCode($input_code);
				if ($codigo === false) {
					if ($gotLock && $lockKey) $this->db->query("SELECT RELEASE_LOCK(?)", array($lockKey));
					return ['success' => false, 'error' => "Producto no encontrado: {$input_code}"];
				}

				// Verificar que no esté agotado
				if (in_array($codigo, $blocked_products)) {
					if ($gotLock && $lockKey) $this->db->query("SELECT RELEASE_LOCK(?)", array($lockKey));
					return ['success' => false, 'error' => "Producto agotado: {$codigo}"];
				}

				// Validación de precio anómalo: si el bot mandó un precio menor al
				// 50% del price_base del producto, casi siempre es un error de
				// extracción (ej. "1500" interpretado como "150"). Marcamos warning.
				$prod_row = $this->products_model->getProduct($codigo);
				$price_base = !empty($prod_row->price_base) ? floatval($prod_row->price_base) : 0;
				if ($price_base > 0 && $precio > 0 && $precio < ($price_base * 0.5)) {
					$price_warnings[] = sprintf(
						'%s @$%s (base $%s)',
						$codigo,
						number_format($precio),
						number_format($price_base)
					);
				}

				$line_total = $precio * $cantidad;
				$total += $line_total;

				$product_lines[] = [
					'codigo' => $codigo,
					'cantidad' => $cantidad,
					'precio' => $precio,
					'line_total' => $line_total,
				];
			}

			// 4. Tipo de envío
			$delivery_type_id = $this->default_delivery_type;
			if (!empty($data['tipoenvio'])) {
				$envio_text = strtolower(trim($data['tipoenvio']));
				foreach ($this->delivery_map as $keyword => $id) {
					if (strpos($envio_text, $keyword) !== false) {
						$delivery_type_id = $id;
						break;
					}
				}
			}
			$delivery = $this->dropshipping_model->getDelivery($delivery_type_id);
			$delivery_name = !empty($delivery) ? $delivery->name : 'Interrapidisimo';

			// 5. Construir comentarios
			$prod_desc = [];
			foreach ($product_lines as $p) {
				$prod_desc[] = $p['codigo'] . ' x' . $p['cantidad'] . ' @$' . number_format($p['precio']);
			}
			$comments = strtoupper($delivery_name) . ' | Productos: ' . implode(', ', $prod_desc);
			if (!empty($data['direccion'])) $comments .= ' | Dir: ' . $data['direccion'];
			if (!empty($data['celular'])) $comments .= ' | Tel: ' . $data['celular'];
			// Warning de precio anómalo va al inicio para que sea lo primero que vea
			// el vendedor al revisar el presupuesto.
			if (!empty($price_warnings)) {
				$comments = '⚠️ PRECIO BAJO: ' . implode(' | ', $price_warnings) . ' || ' . $comments;
			}
			$comments .= ' | [WEBHOOK]';

			// 6. Crear presupuesto
			$budget_data = [
				'clientId' => $client_id,
				'vendorId' => $vendor_id,
				'storeId' => $this->default_store,
				'total' => $total,
				'date' => date('Y-m-d H:i:s'),
				'state' => 0,
				'e_commerce' => 1,
				'list_price' => 0,
				'hasIva' => $this->default_iva,
				'iva' => 8,
				'comments' => $comments,
			];

			if (!$this->budgets_model->save($budget_data)) {
				return ['success' => false, 'error' => 'No se pudo crear el presupuesto'];
			}

			$budget_id = $this->budgets_model->lastID();

			// 7. Crear líneas de detalle
			$product_result = [];
			foreach ($product_lines as $p) {
				$detail_data = [
					'budgetId' => $budget_id,
					'productId' => $p['codigo'],
					'quantity' => $p['cantidad'],
					'unit' => $p['precio'],
					'base' => $p['precio'],
					'total' => $p['line_total'],
				];
				$this->budgets_model->save_detail($detail_data);
				$product_result[] = $p['codigo'] . ' x' . $p['cantidad'];
			}

			if ($gotLock && $lockKey) $this->db->query("SELECT RELEASE_LOCK(?)", array($lockKey));
			return [
				'success' => true,
				'budget_id' => $budget_id,
				'client_id' => $client_id,
				'products' => $product_result,
				'total' => $total,
			];

		} catch (Exception $e) {
			if ($gotLock && $lockKey) $this->db->query("SELECT RELEASE_LOCK(?)", array($lockKey));
			return ['success' => false, 'error' => $e->getMessage()];
		}
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
			$ext = strtolower(pathinfo($_FILES['blocked_file']['name'], PATHINFO_EXTENSION));
			$msg = 'No se pudieron extraer codigos del archivo.';
			if ($ext === 'xls') {
				$msg .= ' El formato .xls no es compatible. Guarda el archivo como .xlsx (Excel) o .csv';
			} else {
				$msg .= ' Verifica que la columna A tenga los codigos de producto.';
			}
			return $this->json_response(400, [
				'ok' => false,
				'error' => $msg
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
		// Fuente de verdad PRIMARIA: tabla blocked_products (la que edita el admin).
		$codes = array();
		try {
			$rows = $this->db->select('product_code')->get('blocked_products')->result();
			foreach ($rows as $r) $codes[] = strtoupper(trim((string)$r->product_code));
		} catch (\Throwable $e) { /* tabla aún no creada */ }

		// Fallback/legacy: JSON file (uploadBlocked aún escribe ahí, conservar compatibilidad).
		$file = $this->get_blocked_file_path();
		if (file_exists($file)) {
			$content = file_get_contents($file);
			$data = json_decode($content, true);
			if (is_array($data)) {
				foreach ($data as $c) $codes[] = strtoupper(trim((string)$c));
			}
		}

		return array_values(array_unique($codes));
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

	// ── BuilderBot Cloud Webhook ─────────────────────────────

	/**
	 * POST: /webhook/builderbot
	 * Recibe ventas desde BuilderBot Cloud via webhook.
	 * Auth: Header X-Webhook-Secret
	 *
	 * Flow:
	 *   1. Validar webhook secret
	 *   2. Identificar bot config
	 *   3. Log raw payload en builderbot_webhooks
	 *   4. Transformar al formato estándar
	 *   5. Procesar venta (reutiliza process_webhook_sale)
	 *   6. Escribir en Google Sheet (fire-and-forget)
	 *   7. Actualizar estado del webhook
	 */
	public function receiveBuilderbot()
	{
		$this->load->library('builderbot_lib');
		$this->load->model('builderbot_model');

		header('Content-Type: application/json');

		// 1. Leer payload
		$raw = file_get_contents('php://input');
		$payload = json_decode($raw, true);

		if (empty($payload)) {
			echo json_encode(['success' => false, 'error' => 'Payload vacío o JSON inválido']);
			return;
		}

		// 2. Identificar bot por bot_id en payload o query string
		$bot_id = isset($payload['bot_id']) ? $payload['bot_id'] : $this->input->get('bot_id');
		$botConfig = null;

		if ($bot_id) {
			$botConfig = $this->builderbot_model->getConfigByBotId($bot_id);
		}

		// Si no se identifica por bot_id, buscar por webhook_secret
		if (!$botConfig) {
			$configs = $this->builderbot_model->getConfigs(true);
			$secret = $this->input->get_request_header('X-Webhook-Secret', true)
					?: $this->input->get_request_header('X-Api-Key', true);
			foreach ($configs as $cfg) {
				if ($this->builderbot_lib->validateWebhook($secret, $cfg)) {
					$botConfig = $cfg;
					break;
				}
			}
		}

		// 3. Validar secret
		$secret = $this->input->get_request_header('X-Webhook-Secret', true)
				?: $this->input->get_request_header('X-Api-Key', true);

		if (!$this->builderbot_lib->validateWebhook($secret, $botConfig)) {
			http_response_code(401);
			echo json_encode(['success' => false, 'error' => 'Webhook secret inválido']);
			return;
		}

		// 4. Log raw webhook
		$webhook_id = $this->builderbot_model->saveWebhook([
			'bot_config_id' => $botConfig ? $botConfig->id : null,
			'event_type'    => 'sale',
			'raw_payload'   => $raw,
			'status'        => 'received',
		]);

		// 5. Transformar payload
		$transformed = $this->builderbot_lib->transformSalePayload($payload, $botConfig);

		if (empty($transformed['nombre']) || empty($transformed['productos'])) {
			$this->builderbot_model->updateWebhook($webhook_id, [
				'status' => 'failed',
				'error_message' => 'Payload incompleto: faltan nombre o productos',
			]);
			echo json_encode(['success' => false, 'error' => 'Payload incompleto']);
			return;
		}

		// 6. Insertar en bot_sales_queue
		$this->db->insert('bot_sales_queue', [
			'payload'   => json_encode($transformed),
			'status'    => 'processing',
			'vendor_id' => $botConfig->default_vendor_id,
			'api_key'   => 'builderbot:' . ($botConfig ? $botConfig->bot_id : 'unknown'),
		]);
		$queue_id = $this->db->insert_id();

		// 7. Procesar venta reutilizando la lógica existente
		$result = $this->process_webhook_sale($transformed, $botConfig->default_vendor_id);

		if (isset($result['success']) && $result['success']) {
			$this->db->where('id', $queue_id)->update('bot_sales_queue', [
				'status'       => 'completed',
				'budget_id'    => $result['budget_id'],
				'processed_at' => date('Y-m-d H:i:s'),
			]);

			$this->builderbot_model->updateWebhook($webhook_id, [
				'status'   => 'processed',
				'queue_id' => $queue_id,
			]);

			// 8. Escribir en Google Sheet (fire-and-forget)
			if ($botConfig) {
				$this->builderbot_lib->writeToGoogleSheet($botConfig, $transformed, $result['budget_id']);
			}

			echo json_encode([
				'success'   => true,
				'budget_id' => $result['budget_id'],
				'message'   => 'Venta procesada exitosamente',
			]);
		} else {
			$error_msg = isset($result['error']) ? $result['error'] : 'Error desconocido al procesar venta';

			$this->db->where('id', $queue_id)->update('bot_sales_queue', [
				'status'        => 'failed',
				'error_message' => $error_msg,
				'attempts'      => 1,
				'processed_at'  => date('Y-m-d H:i:s'),
			]);

			$this->builderbot_model->updateWebhook($webhook_id, [
				'status'        => 'failed',
				'queue_id'      => $queue_id,
				'error_message' => $error_msg,
			]);

			echo json_encode(['success' => false, 'error' => $error_msg]);
		}
	}

	/**
	 * POST: /webhook/builderbot-message
	 * Recibe mensajes entrantes de BuilderBot Cloud y los guarda en la BD.
	 * Esto alimenta la vista tipo WhatsApp Web.
	 */
	public function receiveMessage()
	{
		$this->load->library('builderbot_lib');
		$this->load->model('builderbot_model');

		header('Content-Type: application/json');

		$raw = file_get_contents('php://input');
		$payload = json_decode($raw, true);

		// Log para debug (temporal)
		file_put_contents(APPPATH . 'logs/webhook_debug.log', date('Y-m-d H:i:s') . " RAW: " . $raw . "\n", FILE_APPEND);

		if (empty($payload)) {
			echo json_encode(['success' => false, 'error' => 'Payload vacío']);
			return;
		}

		// Identificar bot: prioridad al projectId del payload (es el bot_id único)
		$projectId = isset($payload['projectId']) ? $payload['projectId'] : '';
		if (empty($projectId) && isset($payload['data']['projectId'])) {
			$projectId = $payload['data']['projectId'];
		}
		$botConfig = null;
		if ($projectId) {
			$botConfig = $this->builderbot_model->getConfigByBotId($projectId);
		}
		// Fallback: por bot_id en query o header secret
		if (!$botConfig) {
			$bot_id = isset($payload['bot_id']) ? $payload['bot_id'] : $this->input->get('bot_id');
			if ($bot_id) {
				$botConfig = $this->builderbot_model->getConfigByBotId($bot_id);
			}
		}
		if (!$botConfig) {
			$configs = $this->builderbot_model->getConfigs(true);
			$secret = $this->input->get_request_header('X-Webhook-Secret', true)
					?: $this->input->get_request_header('X-Api-Key', true);
			foreach ($configs as $cfg) {
				if ($this->builderbot_lib->validateWebhook($secret, $cfg)) {
					$botConfig = $cfg;
					break;
				}
			}
		}

		if (!$botConfig) {
			http_response_code(401);
			echo json_encode(['success' => false, 'error' => 'Bot no identificado']);
			return;
		}

		// BuilderBot format: { eventName: "message.incoming|outgoing", data: { from, body, name, pushName, projectId }, projectId }
		$eventName = isset($payload['eventName']) ? $payload['eventName'] : '';
		$data = isset($payload['data']) ? $payload['data'] : $payload;
		$projectId = isset($payload['projectId']) ? $payload['projectId'] : (isset($data['projectId']) ? $data['projectId'] : '');

		// Extraer datos del mensaje
		$from = isset($data['from']) ? preg_replace('/[^0-9]/', '', $data['from']) : '';
		$content = isset($data['body']) ? $data['body'] : (isset($data['answer']) ? $data['answer'] : '');
		$name = isset($data['pushName']) ? $data['pushName'] : (isset($data['name']) ? $data['name'] : '');

		// Capturar media URL (imágenes, audios, archivos)
		$media_url = null;
		$media_type = isset($data['type']) ? $data['type'] : 'text';

		// 1. Outgoing: media en options
		if (isset($data['options']['media']) && $data['options']['media']) {
			$media_url = $data['options']['media'];
		}
		// 2. Incoming: URL directa de WhatsApp/Facebook
		if (!$media_url && isset($data['url']) && $data['url']) {
			$media_url = $data['url'];
		}
		// 3. Incoming: fileData.url
		if (!$media_url && isset($data['fileData']['url']) && $data['fileData']['url']) {
			$media_url = $data['fileData']['url'];
		}
		// 4. Fallback: urlTempFile de BuilderBot
		if (!$media_url && isset($data['urlTempFile']) && $data['urlTempFile'] && strpos($data['urlTempFile'], 'ERROR') === false) {
			$media_url = $data['urlTempFile'];
		}

		// Descargar media al servidor (URLs de Facebook/WhatsApp son temporales)
		if ($media_url && in_array($media_type, ['image', 'video', 'document', 'audio', 'sticker'])) {
			$local_url = $this->_downloadMedia($media_url, $media_type, $phoneNum);
			if ($local_url) $media_url = $local_url;
		}

		// Si es imagen/audio/video con caption, usar el caption como contenido
		if (in_array($media_type, ['image', 'video', 'document', 'audio', 'sticker'])) {
			$caption = isset($data['caption']) ? $data['caption'] : '';
			if ($caption) {
				$content = $caption;
			} elseif (empty($content) || strpos($content, '_event_media_') === 0 || strpos($content, '_event_voice_') === 0) {
				$typeLabels = ['image' => 'Imagen', 'video' => 'Video', 'document' => 'Documento', 'audio' => 'Audio', 'sticker' => 'Sticker'];
				$content = '[' . ($typeLabels[$media_type] ?? 'Archivo') . ']';
			}
		}

		// Determinar dirección por eventName
		if ($eventName === 'message.incoming') {
			$direction = 'incoming';
		} elseif ($eventName === 'message.outgoing') {
			$direction = 'outgoing';
		} else {
			$direction = isset($payload['direction']) ? $payload['direction'] : 'incoming';
		}

		if (empty($from)) {
			echo json_encode(['success' => false, 'error' => 'No se encontró número de teléfono']);
			return;
		}

		if (empty($content)) {
			echo json_encode(['success' => true, 'skipped' => true, 'reason' => 'Mensaje vacío']);
			return;
		}

		$phoneNum = $from;

		// Guardar mensaje en conversación
		$conv_id = $this->builderbot_model->saveConversationMessage(
			$botConfig->id,
			$phoneNum,
			$direction,
			$content,
			$media_url,
			($direction === 'incoming') ? null : 'bot'
		);

		// Actualizar nombre si viene
		if ($name && $conv_id) {
			$conv = $this->builderbot_model->getOrCreateConversation($botConfig->id, $phoneNum, $name);
		}

		// === AUTO-CLASIFICAR CONVERSACIÓN ===
		$budget_id = null;
		$conv = $this->builderbot_model->getOrCreateConversation($botConfig->id, $phoneNum);

		// Trigger: PEDIDO_CONFIRMADO (preferido) O frases del cierre del bot
		// ("Tu pedido ha sido confirmado", "Gracias por tu compra"). Si el prompt del
		// Asistente IA no usa la keyword explícita, igual lo detectamos. Las defensas
		// contra empty budgets son: SKIP si nombre/total=0, validar productos como array
		// en process_webhook_sale, y el parser smart extrae datos de TODA la conversación.
		$hasConfirmado = (stripos($content, 'PEDIDO_CONFIRMADO') !== false
			|| stripos($content, 'pedido ha sido confirmado') !== false
			|| stripos($content, 'Gracias por tu compra') !== false);
		if ($direction === 'outgoing' && $hasConfirmado) {
			file_put_contents(APPPATH . 'logs/webhook_debug.log',
				date('Y-m-d H:i:s') . " TRIGGER DETECTADO: phone={$phoneNum} content_len=" . strlen($content) . "\n", FILE_APPEND);
		}

		if ($direction === 'outgoing' && $hasConfirmado) {
			// VENTA: agregar TODOS los mensajes recientes (entrantes Y salientes) de la conversación.
			// Los datos del cliente (nombre, cédula, dirección) suelen venir en sus mensajes ENTRANTES.
			// El total y descuentos suelen venir en mensajes SALIENTES del bot.
			// Combinar ambos da el contexto completo aunque el prompt no genere un resumen estructurado.
			$recentMsgs = $this->db->select('content, direction, created_at')
				->from('builderbot_messages')
				->where('conversation_id', $conv->id)
				->order_by('id', 'DESC')
				->limit(30)
				->get()->result();

			// Concatenar en orden cronológico (más viejo primero) para que el parsing tenga contexto natural
			$recentMsgs = array_reverse($recentMsgs);
			$allContent = '';
			foreach ($recentMsgs as $rm) {
				$prefix = ($rm->direction === 'incoming') ? '[CLIENTE]' : '[BOT]';
				$allContent .= "\n{$prefix} " . $rm->content;
			}
			$allContent .= "\n[BOT] " . $content; // mensaje actual al final

			// DETECTOR DE RECLAMACIONES: si el contexto tiene keywords de
			// post-venta/garantía/devolución, NO es venta nueva. El bot puede
			// decir "tu pedido ha sido confirmado" en respuesta a un cambio o
			// reposición (caso Edinson). Marcamos como Reclamo, no creamos budget.
			if ($this->_isReclamoContext($allContent)) {
				file_put_contents(APPPATH . 'logs/webhook_debug.log',
					date('Y-m-d H:i:s') . " TRIGGER IGNORADO (es reclamo): phone={$phoneNum}\n", FILE_APPEND);
				$this->db->where('id', $conv->id)->update('bot_conversations', array(
					'tag_id' => 7, // Reclamo
				));
			} else {
				$budget_id = $this->_processPedidoConfirmado($allContent, $phoneNum, $botConfig);
				// Solo marcamos tag=Venta si efectivamente se obtuvo budget_id.
				// Con el parser nuevo siempre vuelve no-null (gracias a _createReviewBudget),
				// pero esta defensa-en-profundidad protege contra excepciones inesperadas:
				// chat = Venta SOLO si hay budget asociado (regla operativa Ledxury).
				if ($budget_id) {
					$this->db->where('id', $conv->id)->update('bot_conversations', array(
						'tag_id'    => 2, // Venta
						'budget_id' => $budget_id,
					));
				} else {
					// Caso extremo: el parser falló. Dejamos tag=1 (Nuevo) para
					// que el caso quede visible en /errors y no se pierda.
					file_put_contents(APPPATH . 'logs/webhook_debug.log',
						date('Y-m-d H:i:s') . " WARN: budget_id null tras _processPedidoConfirmado para phone={$phoneNum} — chat NO se marca como Venta\n", FILE_APPEND);
				}
			}
		} elseif ($direction === 'outgoing' && (stripos($content, 'PRODUCTO AGOTADO') !== false || stripos($content, 'agotado') !== false)) {
			// AGOTADO
			if (!isset($conv->tag_id) || $conv->tag_id == 1) {
				$this->db->where('id', $conv->id)->update('bot_conversations', array('tag_id' => 3));
			}
		} elseif ($direction === 'incoming' && (stripos($content, 'guia') !== false || stripos($content, 'guía') !== false || stripos($content, 'pedido') !== false || stripos($content, 'envio') !== false || stripos($content, 'envío') !== false || stripos($content, 'llego') !== false || stripos($content, 'llegó') !== false)) {
			// NOVEDAD ENVÍO: cliente pregunta por su envío
			if (!isset($conv->tag_id) || $conv->tag_id == 1) {
				$this->db->where('id', $conv->id)->update('bot_conversations', array('tag_id' => 5));
			}
		} elseif ($direction === 'incoming' && (stripos($content, 'precio') !== false || stripos($content, 'cuanto') !== false || stripos($content, 'cuánto') !== false || stripos($content, 'cuesta') !== false || stripos($content, 'vale') !== false)) {
			// COTIZACIÓN: cliente pregunta precio
			if (!isset($conv->tag_id) || $conv->tag_id == 1) {
				$this->db->where('id', $conv->id)->update('bot_conversations', array('tag_id' => 4));
			}
		}

		echo json_encode(['success' => true, 'conversation_id' => $conv_id, 'budget_id' => $budget_id]);
	}

	/**
	 * Descargar archivo multimedia al servidor local
	 */
	private function _downloadMedia($url, $type, $phone)
	{
		try {
			$dir = FCPATH . 'uploads/whatsapp/';
			if (!is_dir($dir)) mkdir($dir, 0775, true);

			$ext = 'jpg';
			if ($type === 'audio') $ext = 'ogg';
			elseif ($type === 'video') $ext = 'mp4';
			elseif ($type === 'document') $ext = 'pdf';
			elseif ($type === 'sticker') $ext = 'webp';

			date_default_timezone_set("America/Bogota");
			$filename = $phone . '_' . date('YmdHis') . '_' . substr(md5($url . microtime()), 0, 8) . '.' . $ext;
			$filepath = $dir . $filename;

			// Intentar descarga directa primero
			$data = $this->_curlDownload($url);

			// Si falla, intentar vía WhatsApp Media API (necesita token de Meta)
			if (!$data) {
				$CI =& get_instance();
				$CI->config->load('secrets', true);
				$secrets = $CI->config->item('secrets');
				$meta_config = isset($secrets['meta_ads']) ? $secrets['meta_ads'] : ($CI->config->item('meta_ads') ?: []);
				if (empty($meta_config)) {
					// Cargar directo
					include(APPPATH . 'config/secrets.php');
					$meta_config = isset($config['meta_ads']) ? $config['meta_ads'] : [];
				}
				$token = isset($meta_config['access_token']) ? $meta_config['access_token'] : '';

				if ($token && preg_match('/mid=(\d+)/', $url, $m)) {
					$media_id = $m[1];
					// Paso 1: obtener URL de descarga
					$api_url = "https://graph.facebook.com/v19.0/{$media_id}";
					$ch = curl_init($api_url);
					curl_setopt_array($ch, array(
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $token),
						CURLOPT_TIMEOUT => 10,
						CURLOPT_SSL_VERIFYPEER => false,
					));
					$resp = curl_exec($ch);
					curl_close($ch);

					$json = json_decode($resp, true);
					if (isset($json['url'])) {
						// Paso 2: descargar con token
						$ch = curl_init($json['url']);
						curl_setopt_array($ch, array(
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $token),
							CURLOPT_FOLLOWLOCATION => true,
							CURLOPT_TIMEOUT => 15,
							CURLOPT_SSL_VERIFYPEER => false,
						));
						$data = curl_exec($ch);
						$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						curl_close($ch);
						if ($code < 200 || $code >= 300) $data = null;
					}
				}
			}

			if ($data && strlen($data) > 500) {
				file_put_contents($filepath, $data);
				return base_url() . 'uploads/whatsapp/' . $filename;
			}
		} catch (Exception $e) {
			// Silenciar errores
		}
		return null;
	}

	private function _curlDownload($url)
	{
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_TIMEOUT => 15,
			CURLOPT_SSL_VERIFYPEER => false,
		));
		$data = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return ($code >= 200 && $code < 300 && strlen($data) > 500) ? $data : null;
	}

	/**
	 * Procesar un mensaje con PEDIDO_CONFIRMADO:
	 * Parsea las variables y crea presupuesto en budgets.
	 */
	private function _processPedidoConfirmado($content, $phoneNum, $botConfig, $cronMode = false)
	{
		date_default_timezone_set("America/Bogota");

		// Parseo (fuera del try: no lanza excepciones).
		// Soporta tanto el formato viejo como el nuevo (con/sin tildes, campos adicionales).
		$nombre = $this->_smartExtractName($content);
		$documento = $this->_extractField($content, 'Cédula')
			?: $this->_extractField($content, 'Cedula')
			?: $this->_extractField($content, 'Documento');
		$direccion = $this->_extractField($content, 'Dirección')
			?: $this->_extractField($content, 'Direccion');
		$barrio = $this->_extractField($content, 'Barrio');
		$ciudad = $this->_extractField($content, 'Ciudad');
		$departamento = $this->_extractField($content, 'Departamento');
		$zona = $this->_extractField($content, 'Zona');
		$referencia = $this->_extractField($content, 'Referencia');
		$celular_explicit = $this->_extractField($content, 'Celular');
		$celular = preg_replace('/[^0-9]/', '', $phoneNum);
		$voltaje = $this->_extractField($content, 'Voltaje');
		$color = $this->_extractField($content, 'Color');
		$totalStr = $this->_extractField($content, 'Total');
		$pedidoStr = $this->_extractField($content, 'Pedido');
		$productosStr = $this->_extractField($content, 'Productos') ?: $this->_extractField($content, 'Producos');

		// === Fallbacks para conversaciones donde el bot NO genera el resumen estructurado ===
		// Si el campo no se encontró con "Campo:", buscarlo con "CC 12345" (sin dos puntos).
		if (empty($documento)) {
			if (preg_match('/\bC\.?C\.?\s*[#:]?\s*([0-9\.\-]{6,15})/i', $content, $m)) {
				$documento = trim($m[1]);
			} elseif (preg_match('/\b(?:cedula|cédula|documento|identificaci[oó]n|nit)\s+([0-9\.\-]{6,15})/i', $content, $m)) {
				$documento = trim($m[1]);
			}
		}
		if (empty($direccion)) {
			// Buscar "Dirección" sin colon: "Dirección Bello, Cabañas..."
			if (preg_match('/Direcci[oó]n\s+([^\n\[]{8,200})/iu', $content, $m)) {
				$direccion = trim($m[1]);
			}
		}
		// Nota: los fallbacks regex y heurísticas (saludo bot + nombre repetido,
		// mensaje del cliente con nombre completo) están consolidados en _smartExtractName.

		// === VALIDACIÓN + SANITIZACIÓN (A + B) ===
		// Normalizar celular acá para poder comparar contra documento.
		$celular_norm = $celular;
		if (strlen($celular_norm) > 10 && strpos($celular_norm, '57') === 0) $celular_norm = substr($celular_norm, 2);

		$warnings = array();

		// A) Si la dirección extraída parece pregunta del bot (ej. "completa con calle...",
		//    "para recibir el paquete", "¿zona urbana?"), descartarla. Mejor vacío que basura.
		if (!empty($direccion) && $this->_isLikelyBotQuestion($direccion)) {
			file_put_contents(APPPATH . 'logs/webhook_debug.log',
				date('Y-m-d H:i:s') . " DIR_REJECTED (bot question): '" . substr($direccion, 0, 100) . "'\n", FILE_APPEND);
			$direccion = '';
			$warnings[] = 'dirección descartada (era pregunta del bot)';
		}
		if (!empty($barrio) && $this->_isLikelyBotQuestion($barrio)) { $barrio = ''; }
		if (!empty($ciudad) && $this->_isLikelyBotQuestion($ciudad)) { $ciudad = ''; }
		if (!empty($referencia) && $this->_isLikelyBotQuestion($referencia)) { $referencia = ''; }

		// B) Si el documento extraído == celular, NO es cédula real. Vaciar para que el
		//    fallback más abajo lo trate como missing y use el celular sin engañarse.
		if (!empty($documento)) {
			$docNorm = preg_replace('/[^0-9]/', '', $documento);
			if ($docNorm === $celular_norm || $docNorm === $celular) {
				file_put_contents(APPPATH . 'logs/webhook_debug.log',
					date('Y-m-d H:i:s') . " DOC_REJECTED (== celular): doc={$documento} cel={$celular_norm}\n", FILE_APPEND);
				$documento = '';
				$warnings[] = 'cédula no validada (= celular)';
			}
		}

		// === D) FALLBACK CLIENT-ONLY: re-extraer SOLO de mensajes [CLIENTE] ===
		// Si dirección o documento quedaron vacíos por ser basura/bot, intentar
		// extraerlos del texto que el cliente realmente escribió.
		if (empty($direccion) || empty($documento)) {
			$clientText = $this->_extractClientOnly($content);
			if (empty($direccion)) {
				// Cualquier línea del cliente con calle/carrera/diagonal y número.
				if (preg_match('/\b(?:calle|cra|carrera|cr|diagonal|dg|transversal|tv|av|avenida)[^\n]{5,150}/iu', $clientText, $m)) {
					$cand = trim($m[0]);
					if (!$this->_isLikelyBotQuestion($cand)) $direccion = $cand;
				}
			}
			if (empty($documento)) {
				// Cédula en mensajes del cliente (CC, cedula, número de 6-15 dígitos no igual al celular).
				if (preg_match('/\b(?:c\.?c\.?|c[eé]dula|documento|identificaci[oó]n)\b\s*[#:]?\s*([0-9\.\-]{6,15})/iu', $clientText, $m)) {
					$cand = preg_replace('/[^0-9]/', '', $m[1]);
					if ($cand !== $celular_norm && strlen($cand) >= 6) $documento = $cand;
				}
			}
		}

		// === F) FALLBACK AI: si seguimos sin dirección o documento, reintentar con Groq ===
		// Llama 3.3 70B con prompt rígido + JSON mode. Timeout duro 6s. Si falla,
		// continuamos con lo que tengamos. No se ejecuta si ya tenemos todo.
		$aiUsed = false;
		$aiData = null;
		if (empty($direccion) || empty($documento) || empty($nombre)) {
			$aiData = $this->_aiExtractFallback($content, $celular_norm);
			if (is_array($aiData)) {
				$aiUsed = true;
				if (empty($nombre) && !empty($aiData['nombre'])) $nombre = trim($aiData['nombre']);
				if (empty($documento) && !empty($aiData['cedula'])) {
					$cand = preg_replace('/[^0-9]/', '', $aiData['cedula']);
					if ($cand !== $celular_norm && strlen($cand) >= 6) $documento = $cand;
				}
				if (empty($direccion) && !empty($aiData['direccion'])) {
					$cand = trim($aiData['direccion']);
					if (!$this->_isLikelyBotQuestion($cand)) $direccion = $cand;
				}
				if (empty($barrio) && !empty($aiData['barrio'])) $barrio = trim($aiData['barrio']);
				if (empty($ciudad) && !empty($aiData['ciudad'])) $ciudad = trim($aiData['ciudad']);
				if (empty($departamento) && !empty($aiData['departamento'])) $departamento = trim($aiData['departamento']);
				file_put_contents(APPPATH . 'logs/webhook_debug.log',
					date('Y-m-d H:i:s') . " AI_FALLBACK ok nombre='{$nombre}' doc='{$documento}' dir='" . substr($direccion, 0, 60) . "'\n", FILE_APPEND);
			}
		}

		// Nuevo formato: bloque "Productos:" multi-línea con "- Nx CODE | $subtotal"
		$productsFromBlock = $this->_parseProductsBlock($content);

		// Si no hay bloque ni Pedido, escanear toda la conversación en busca de menciones tipo
		// "40 modulos 6LED rojo 12 voltios" o "40 módulos 6LED en rojo".
		if (empty($productsFromBlock) && empty($pedidoStr) && empty($productosStr)) {
			$productsFromBlock = $this->_scanConversationForProducts($content);
		}

		// F.2) Si el parser local NO encontró productos pero el AI fallback sí, usar esos.
		// Cada producto AI viene como {qty, descripcion} — resolvemos a código real con _findProductCode.
		if (empty($productsFromBlock) && is_array($aiData) && !empty($aiData['productos']) && is_array($aiData['productos'])) {
			foreach ($aiData['productos'] as $aiProd) {
				$qty = isset($aiProd['qty']) ? (int)$aiProd['qty'] : 0;
				$desc = isset($aiProd['descripcion']) ? trim((string)$aiProd['descripcion']) : '';
				if ($qty <= 0 || $desc === '') continue;
				$code = $this->_findProductCode($desc, (string)$voltaje, (string)$color);
				$productsFromBlock[] = array(
					'qty' => $qty,
					'name' => $desc,
					'code' => $code ?: 'PENDIENTE',
					'subtotal' => 0,
				);
			}
			if (!empty($productsFromBlock)) {
				$warnings[] = 'productos extraídos por AI';
				file_put_contents(APPPATH . 'logs/webhook_debug.log',
					date('Y-m-d H:i:s') . " AI_FALLBACK products: " . count($productsFromBlock) . " items\n", FILE_APPEND);
			}
		}

		// Construir dirección completa para guardar en cliente: dirección + barrio + ciudad + departamento
		$direccionCompleta = $direccion;
		if (!empty($barrio) && stripos($direccion, $barrio) === false) $direccionCompleta .= ', Barrio ' . $barrio;
		if (!empty($ciudad) && stripos($direccion, $ciudad) === false) $direccionCompleta .= ', ' . $ciudad;
		if (!empty($departamento) && $departamento !== 'N/A' && stripos($direccionCompleta, $departamento) === false) $direccionCompleta .= ', ' . $departamento;
		if (!empty($direccionCompleta)) $direccion = $direccionCompleta;

		if (empty($documento) || strlen(preg_replace('/[^0-9]/', '', $documento)) < 6) {
			$documento = $celular_norm;
			$warnings[] = 'cédula = celular (cliente no la dio)';
		}

		$total = (int) preg_replace('/[^0-9]/', '', $totalStr ?: '0');

		// CAP DE SEGURIDAD: ningún pedido legítimo de Ledxury supera $10M COP.
		// Si el extractor tomó un valor ≥10M, casi seguro confundió un campo
		// (cédula, código de producto, teléfono, timestamp) como total. Lo
		// descartamos para que entre al fallback de regex con monto frecuente.
		// Casos vistos: parser tomó CC 1098804102 como total $80.000.406.
		if ($total >= 10000000) {
			file_put_contents(APPPATH . 'logs/webhook_debug.log',
				date('Y-m-d H:i:s') . " TOTAL_CAP descartado total inflado={$total} (totalStr='{$totalStr}')\n", FILE_APPEND);
			$total = 0;
		}

		// Si no se extrajo total con "Total:", buscar el mayor "$XX.XXX" o "$XXXXX" en mensajes del BOT.
		if ($total <= 0) {
			if (preg_match_all('/\$\s*([0-9]{1,3}(?:[\.,][0-9]{3})+|[0-9]{4,7})/u', $content, $tm)) {
				$amounts = array();
				foreach ($tm[1] as $amt) {
					$amt_int = (int) preg_replace('/[^0-9]/', '', $amt);
					if ($amt_int >= 30000 && $amt_int <= 5000000) $amounts[] = $amt_int;
				}
				if (!empty($amounts)) {
					// Elegir el monto más frecuente (suele ser el confirmado), no el máximo
					$counts = array_count_values($amounts);
					arsort($counts);
					$total = (int) array_key_first($counts);
				}
			}
		}

		$warnStr = empty($warnings) ? '' : ' WARN=' . implode('|', $warnings) . ($aiUsed ? ' AI=1' : '');
		file_put_contents(APPPATH . 'logs/webhook_debug.log',
			date('Y-m-d H:i:s') . " PARSED: nombre={$nombre} doc={$documento} total_str={$totalStr} total={$total} dir={$direccion} prod={$productosStr}{$warnStr}\n", FILE_APPEND);

		// REGLA LEDXURY: chat marcado como Venta SIEMPRE debe tener budget asociado.
		// Aunque falten nombre o total, creamos budget en state=0 con marker REVISAR
		// para que el vendedor entre al panel, lea la conversación, complete los
		// campos y apruebe (o cancele/marque agotado). Antes esto se encolaba en
		// bot_sales_queue y el chat quedaba huérfano: las ventas se perdían.
		if (empty($nombre) || $total <= 0) {
			file_put_contents(APPPATH . 'logs/webhook_debug.log',
				date('Y-m-d H:i:s') . " PEDIDO_CONFIRMADO REVIEW (datos incompletos): nombre={$nombre} total={$total} cron=" . ($cronMode ? '1' : '0') . "\n", FILE_APPEND);
			return $this->_createReviewBudget(
				$content, $phoneNum, $botConfig,
				$nombre, $celular_norm, $documento, $direccion, $total,
				"REVISAR — Bot detectó venta pero falta info clave. Datos extraídos: nombre='{$nombre}' total={$total}. Lee la conversación y completa el pedido."
			);
		}

		// DUPLICATE GUARD: lock por (vendor + cellphone + total) para evitar carrera entre webhooks paralelos.
		// Si dos requests idénticos llegan al mismo tiempo, el segundo espera y luego encuentra el primero.
		$lockKey = 'led_pdo_' . md5($botConfig->default_vendor_id . '|' . $celular_norm . '|' . $total);
		$lockRow = $this->db->query("SELECT GET_LOCK(?, 8) AS got", array($lockKey))->row();
		$gotLock = $lockRow && (int)$lockRow->got === 1;

		// DUPLICATE: ventana ampliada a 30 días — si existe presupuesto con mismo
		// cliente (cellphone) y mismo total, reusamos en vez de duplicar.
		// Antes la ventana era 30 min, lo que dejaba pasar duplicados cuando una
		// conversación se reprocesaba días después (ej. recovery histórico).
		$existing = $this->db->select('budgets.idBudget')
			->from('budgets')
			->join('clients', 'clients.idClient = budgets.clientId', 'left')
			->where('budgets.vendorId', $botConfig->default_vendor_id)
			->where('budgets.total', $total)
			->where('budgets.date >=', date('Y-m-d H:i:s', strtotime('-30 days')))
			->like('clients.cellphone', $celular_norm, 'both')
			->where('budgets.deleted', 0)
			->order_by('budgets.state', 'DESC')   // preferir el más procesado (state=1 facturado > 0 borrador)
			->order_by('budgets.idBudget', 'DESC')
			->limit(1)
			->get()->row();

		if ($existing) {
			if ($gotLock) $this->db->query("SELECT RELEASE_LOCK(?)", array($lockKey));
			file_put_contents(APPPATH . 'logs/webhook_debug.log',
				date('Y-m-d H:i:s') . " PEDIDO_CONFIRMADO DUPLICATE: {$celular_norm} total={$total} -> budget_id={$existing->idBudget} (window=30d)\n", FILE_APPEND);
			return (int)$existing->idBudget;
		}

		$budget_id = null;
		try {

			// Buscar o crear cliente
			$client = $this->clients_model->getClientByPhone($celular_norm);
			if (empty($client) && !empty($documento)) {
				$client = $this->clients_model->getClientByIdNum($documento);
			}

			if (empty($client)) {
				$address_parts = $this->parse_address($direccion ?: '');
				$client_data = array(
					'idNum' => $documento,
					'name' => $nombre,
					'email' => '',
					'phone' => $celular_norm,
					'cellphone' => $celular_norm,
					'address' => $address_parts['full_address'],
					'city' => $address_parts['city'],
					'state' => $address_parts['state'],
					'vendor' => $botConfig->default_vendor_id,
					'retail' => 1,
					'rate' => 0,
					'f_id' => $this->clients_model->getHighestClientFid()->next_fid + 1,
				);
				$this->clients_model->save($client_data);
				$client_id = $this->db->insert_id();
			} else {
				$client_id = $client->idClient;
				$update_data = array(
					'cellphone' => $celular_norm ?: $client->cellphone,
				);
				if (!empty($direccion)) {
					$address_parts = $this->parse_address($direccion);
					$update_data['address'] = $address_parts['full_address'] ?: $client->address;
					$update_data['city'] = $address_parts['city'] ?: $client->city;
					$update_data['state'] = $address_parts['state'] ?: $client->state;
				}
				if (empty($client->idNum) && !empty($documento)) {
					$update_data['idNum'] = $documento;
				}
				$this->clients_model->update($client_id, $update_data);
			}

			// Construir comentarios
			$comments = '';
			if (!empty($warnings)) {
				$comments .= 'REVISAR DATOS: ' . implode(', ', $warnings) . '. ';
			}
			if ($referencia) $comments .= "Ref: {$referencia}. ";
			if ($voltaje) $comments .= "Voltaje: {$voltaje}. ";
			if ($color) $comments .= "Color: {$color}. ";
			if ($pedidoStr) $comments .= "Pedido: {$pedidoStr}. ";
			$comments .= "Via: WhatsApp Bot.";

			// Crear presupuesto
			$budget_data = array(
				'clientId' => $client_id,
				'vendorId' => $botConfig->default_vendor_id,
				'storeId' => $botConfig->default_store_id,
				'total' => $total,
				'date' => date('Y-m-d H:i:s'),
				'state' => 0,
				'e_commerce' => 1,
				'list_price' => 0,
				'hasIva' => 0,
				'iva' => 8,
				'comments' => trim($comments),
			);

			$this->budgets_model->save($budget_data);
			$budget_id = $this->budgets_model->lastID();

			// Parsear productos del pedido e intentar crear detalle.
			// PRIORIDAD: 1) bloque "Productos:" multi-línea (formato nuevo, robusto)
			//            2) línea "Pedido:" formato viejo (fallback)
			// Si hay un productId inválido (FK fail en budget_detail), caemos a detalle PENDIENTE.
			$products = !empty($productsFromBlock)
				? $productsFromBlock
				: $this->_parsePedidoProducts($pedidoStr, $productosStr, $voltaje, $color);

			// === Cargar lista de AGOTADOS ===
			// Si TODOS los productos del pedido están agotados → no crear el budget.
			// Si ALGUNOS lo están → se crea el budget pero con alerta visible al vendedor.
			$blocked_codes = array_map('strtoupper', $this->load_blocked_products());
			$agotado_codes = array();
			if (!empty($products) && !empty($blocked_codes)) {
				$allAgotados = true;
				foreach ($products as $p) {
					if (!in_array(strtoupper((string)$p['code']), $blocked_codes, true)) {
						$allAgotados = false;
						break;
					}
				}
				if ($allAgotados) {
					// REGLA LEDXURY: aunque todos estén agotados, NO borrar el budget.
					// Lo dejamos en state=0 con prefijo TIENE AGOTADOS en el comentario;
					// el bodeguero entra al panel y le da click al botón "Agotado" que
					// dispara WhatsApp + archiva. Antes este caso borraba el budget y
					// dejaba el chat marcado como Venta sin presupuesto asociado.
					$pedidoCodes = implode(', ', array_map(function($p){ return $p['code']; }, $products));
					file_put_contents(APPPATH . 'logs/webhook_debug.log',
						date('Y-m-d H:i:s') . " PEDIDO_CONFIRMADO ALL_AGOTADOS (budget conservado): {$pedidoCodes} cron=" . ($cronMode ? '1' : '0') . "\n", FILE_APPEND);
					// No retornamos null: dejamos que continúe el flujo normal — los detalles
					// se insertarán abajo, y al final se prefija el comentario con la alerta
					// de agotados (lógica existente al armar $alerts).
					$agotado_codes = array_map(function($p){ return $p['code']; }, $products);
				}
			}

			// Intentar guardar cada línea de detalle individualmente. Si una falla por FK
			// (productId no existe en products), se guarda como PENDIENTE para
			// que el vendedor pueda corregirla, sin romper las demás líneas.
			$inserted = 0;
			$failed_codes = array();
			if (!empty($products)) {
				$sum = 0;
				$num = count($products);
				foreach ($products as $i => $p) {
					if ($i === $num - 1) {
						$line_total = $total - $sum;
						$line_unit = ($p['qty'] > 0) ? round($line_total / $p['qty']) : $line_total;
					} else {
						$line_unit = ($p['qty'] > 0) ? round($p['subtotal'] / $p['qty']) : $p['subtotal'];
						$line_total = $p['subtotal'];
					}
					$sum += $line_total;

					$code = (string) $p['code'];
					$code_upper = strtoupper($code);
					// Trackear agotados (se inserta igual con el código real para no perder info,
					// pero el vendedor verá la alerta en comentarios y debe contactar al cliente).
					if (in_array($code_upper, $blocked_codes, true)) {
						$agotado_codes[] = $code;
					}
					// Verificar que el código existe ANTES de insertar (evita FK exception)
					$exists = $this->db->where('idProduct', $code)->count_all_results('products');
					$useCode = $exists > 0 ? $code : 'PENDIENTE';
					if ($exists === 0) $failed_codes[] = $code;

					try {
						$this->budgets_model->save_detail(array(
							'budgetId' => $budget_id,
							'productId' => $useCode,
							'quantity' => $p['qty'],
							'unit' => $line_unit,
							'base' => $line_unit,
							'total' => $line_total,
						));
						$inserted++;
					} catch (\Throwable $rowErr) {
						file_put_contents(APPPATH . 'logs/webhook_debug.log',
							date('Y-m-d H:i:s') . " DETAIL ROW FAIL budget_id={$budget_id} code={$code}: " . $rowErr->getMessage() . "\n", FILE_APPEND);
					}
				}
			}

			// Si nada se insertó, garantizar al menos una línea PENDIENTE para que el budget tenga detalle
			if ($inserted === 0) {
				try {
					$this->budgets_model->save_detail(array(
						'budgetId' => $budget_id,
						'productId' => 'PENDIENTE',
						'quantity' => 1,
						'unit' => $total,
						'base' => $total,
						'total' => $total,
					));
					file_put_contents(APPPATH . 'logs/webhook_debug.log',
						date('Y-m-d H:i:s') . " DETAIL FALLBACK (PENDIENTE) budget_id={$budget_id} failed_codes=" . implode(',', $failed_codes) . "\n", FILE_APPEND);
				} catch (\Throwable $fbErr) {
					file_put_contents(APPPATH . 'logs/webhook_debug.log',
						date('Y-m-d H:i:s') . " DETAIL PENDIENTE FAIL budget_id={$budget_id}: " . $fbErr->getMessage() . "\n", FILE_APPEND);
				}
			}

			// Construir prefijos de alerta para los comentarios
			$alerts = array();
			if (!empty($agotado_codes)) {
				$alerts[] = '⚠️ TIENE AGOTADOS (' . implode(', ', array_unique($agotado_codes)) . ') — REVISAR ANTES DE DESPACHAR';
			}
			if (!empty($failed_codes)) {
				$alerts[] = 'Códigos sin resolver: ' . implode(', ', array_unique($failed_codes));
			}
			if (!empty($alerts)) {
				$current = $this->db->select('comments')->where('idBudget', $budget_id)->get('budgets')->row();
				$existing_comments = $current ? (string)$current->comments : '';
				$this->db->where('idBudget', $budget_id)->update('budgets', array(
					'comments' => trim(implode(' | ', $alerts) . ' | ' . $existing_comments, ' |'),
				));
			}

			file_put_contents(APPPATH . 'logs/webhook_debug.log',
				date('Y-m-d H:i:s') . " PEDIDO_CONFIRMADO OK: budget_id={$budget_id} cliente={$nombre} total={$total}\n", FILE_APPEND);

			if ($gotLock) $this->db->query("SELECT RELEASE_LOCK(?)", array($lockKey));
			return $budget_id;

		} catch (Exception $e) {
			file_put_contents(APPPATH . 'logs/webhook_debug.log',
				date('Y-m-d H:i:s') . " PEDIDO_CONFIRMADO ERROR: " . $e->getMessage() . " cron=" . ($cronMode ? '1' : '0') . "\n", FILE_APPEND);
			if ($gotLock) $this->db->query("SELECT RELEASE_LOCK(?)", array($lockKey));
			// Si el budget alcanzó a crearse, lo devolvemos para linkear con la conversación
			if ($budget_id) return $budget_id;
			// Si no, encolamos como failed (excepto en cronMode: duplicaría el item).
			if (!$cronMode) {
				$this->_enqueueFailedWebhookSale($content, $phoneNum, $botConfig, $e->getMessage());
			}
			return null;
		}
	}

	/**
	 * Registra un PEDIDO_CONFIRMADO fallido en bot_sales_queue para que
	 * aparezca en el panel /ventas/fallidos del vendedor y pueda reintentarse.
	 */
	private function _enqueueFailedWebhookSale($content, $phoneNum, $botConfig, $error)
	{
		$celular = preg_replace('/[^0-9]/', '', (string)$phoneNum);
		$celular_norm = $celular;
		if (strlen($celular_norm) > 10 && strpos($celular_norm, '57') === 0) $celular_norm = substr($celular_norm, 2);

		$payload = array(
			'source'    => 'whatsapp_webhook',
			'bot'       => $botConfig->name ?? null,
			'bot_id'    => $botConfig->id ?? null,
			'phone'     => $phoneNum,
			'celular'   => $celular_norm,
			'nombre'    => $this->_smartExtractName($content),
			'documento' => $this->_extractField($content, 'Cédula') ?: $this->_extractField($content, 'Documento'),
			'direccion' => $this->_extractField($content, 'Dirección') ?: $this->_extractField($content, 'Direccion'),
			'referencia'=> $this->_extractField($content, 'Referencia'),
			'voltaje'   => $this->_extractField($content, 'Voltaje'),
			'color'     => $this->_extractField($content, 'Color'),
			'total'     => $this->_extractField($content, 'Total'),
			'pedido'    => $this->_extractField($content, 'Pedido'),
			'productos' => $this->_extractField($content, 'Productos') ?: $this->_extractField($content, 'Producos'),
			'raw'       => mb_substr((string)$content, 0, 2000),
		);

		$this->db->insert('bot_sales_queue', array(
			'vendor_id'     => $botConfig->default_vendor_id,
			'payload'       => json_encode($payload, JSON_UNESCAPED_UNICODE),
			'status'        => 'failed',
			'error_message' => $error,
			'attempts'      => 1,
			'processed_at'  => date('Y-m-d H:i:s'),
			'created_at'    => date('Y-m-d H:i:s'),
		));
	}

	/**
	 * Extraer un campo "Clave: valor" de un mensaje multilínea
	 */
	/**
	 * Escanea toda la conversación buscando menciones tipo "40 modulos 6LED rojo 12 voltios"
	 * o "40 módulos 6LED en rojo". Útil cuando el prompt del bot NO genera resumen estructurado.
	 * Devuelve la línea más reciente que tenga producto resoluble.
	 */
	private function _scanConversationForProducts($content)
	{
		$products = array();
		// Buscar patrones "N modulos|X NLED..." o "N x NLED..."
		$rx = '/(\d+)\s*(?:m[oó]dulos?|x|unidad(?:es)?)\s+([^\n\[]{0,100}?\b\d+\s*led\b[^\n\[]{0,80})/iu';
		if (preg_match_all($rx, $content, $matches, PREG_SET_ORDER)) {
			// Tomar el último match (más reciente cronológicamente, ya que ordenamos así)
			$last = end($matches);
			$qty = (int) $last[1];
			$nameWithCtx = trim($last[2]);
			if ($qty > 0 && $qty <= 5000) {
				$code = $this->_findProductCode($nameWithCtx, '', '');
				$products[] = array(
					'qty' => $qty,
					'name' => $nameWithCtx,
					'code' => $code ?: 'PENDIENTE',
					'subtotal' => 0,
				);
			}
		}
		return $products;
	}

	/**
	 * Heurística para detectar si una conversación está en contexto de
	 * post-venta / garantía / devolución / reclamo, en lugar de una venta nueva.
	 *
	 * El bot puede decir "tu pedido ha sido confirmado" como respuesta a una
	 * reposición acordada con el cliente (ej. caso Edinson: recibió candado
	 * del color equivocado, se acuerda enviar el correcto, el bot dice
	 * "confirmado"). Sin este filtro, esos cierres falsos generaban budget
	 * fantasma y duplicaban registros.
	 *
	 * Devuelve true si en los últimos N mensajes aparecen patrones de reclamo
	 * con frecuencia significativa (≥ 3 hits sumando todas las palabras clave).
	 */
	private function _isReclamoContext($content)
	{
		// Patrones que delatan post-venta / reclamo / cambio
		$keywords = array(
			'color equivocado', 'producto equivocado', 'me llegó', 'me llego',
			'no funciona', 'defectuoso', 'dañado', 'rota', 'roto',
			'cambio', 'cambiar el', 'cambiarlo', 'reemplazo',
			'devolución', 'devolucion', 'devolver',
			'reclamo', 'reclamación', 'reclamacion', 'queja',
			'garantía', 'garantia',
			'no era', 'no es lo que pedí', 'no es lo que pedi',
			'envío equivocado', 'envio equivocado',
			'mal pedido', 'pedido equivocado', 'error en el pedido',
			'error de envío', 'error de envio',
		);

		$content_lower = mb_strtolower($content);
		$hits = 0;
		foreach ($keywords as $kw) {
			$hits += substr_count($content_lower, $kw);
			if ($hits >= 3) return true; // umbral: 3+ apariciones
		}

		// Patrón fuerte: una sola aparición de "color equivocado" o
		// "producto equivocado" ya es suficiente — son inequívocos.
		$strong = array('color equivocado', 'producto equivocado', 'envío equivocado', 'envio equivocado');
		foreach ($strong as $kw) {
			if (strpos($content_lower, $kw) !== false) return true;
		}

		return false;
	}

	/**
	 * Crea un budget mínimo en state=0 cuando el parser no logró extraer todos
	 * los datos. Garantiza la regla operativa de Ledxury: si el chat queda
	 * marcado como Venta, SIEMPRE debe existir budget asociado para que el
	 * vendedor lo revise desde el panel y lo complete (o lo cancele/marque
	 * agotado). Sin esto, las ventas se perdían silenciosamente.
	 *
	 * El budget queda con:
	 *   - state=0 (pendiente aprobar)
	 *   - e_commerce=1 (originado por bot)
	 *   - total = el que venga aunque sea 0
	 *   - clientId = del cliente encontrado/creado, o null si no hay datos
	 *   - 1 línea PENDIENTE en budget_detail (si no se pudo identificar producto)
	 *   - comments con prefijo REVISAR + razón de fallo del parser
	 *
	 * Siempre devuelve el budget_id; nunca null. Así el caller puede vincular
	 * la conversación con el budget recién creado.
	 */

	/**
	 * Devuelve solo las líneas marcadas con `[CLIENTE]` del content concatenado
	 * que arma receiveMessage(). Útil para extraer datos (cédula, dirección,
	 * productos) sin contaminarse con preguntas del bot.
	 *
	 * Si el content no tiene marcadores `[CLIENTE]`/`[BOT]` (caso legacy o
	 * single-message), devuelve el content original.
	 */
	private function _extractClientOnly($content)
	{
		if (strpos($content, '[CLIENTE]') === false && strpos($content, '[BOT]') === false) {
			return $content;
		}
		$lines = preg_split('/\r?\n/', $content);
		$out = array();
		$inClient = false;
		foreach ($lines as $line) {
			if (strpos($line, '[CLIENTE]') !== false) { $inClient = true; $line = str_replace('[CLIENTE]', '', $line); }
			elseif (strpos($line, '[BOT]') !== false) { $inClient = false; continue; }
			if ($inClient) $out[] = trim($line);
		}
		return implode("\n", $out);
	}

	/**
	 * Heurística: ¿este string parece la pregunta del bot, no la respuesta del cliente?
	 *
	 * El bot pide datos con frases tipo "completa con calle...", "para el envío",
	 * "por ejemplo: Calle 45...", "¿zona urbana?". Si el parser confundió eso con
	 * la respuesta, hay que descartarlo.
	 */
	private function _isLikelyBotQuestion($value)
	{
		$v = trim((string)$value);
		if ($v === '') return false;
		if (mb_strlen($v) > 250) return true; // las respuestas humanas suelen ser cortas
		// Empieza con una pregunta del bot
		$starts = array(
			'completa con', 'para recibir', 'para el envío', 'para el envio',
			'por favor', 'por ejemplo', 'es zona', 'de envío', 'de envio',
			'con barrio', 'con calle', 'con tu', 'con su', 'con el',
			'¿', 'me los', 'me la', 'me lo', 'cu[áa]l', 'qu[eé]',
		);
		foreach ($starts as $rx) {
			if (preg_match('/^\s*' . $rx . '/iu', $v)) return true;
		}
		// Contiene 2+ signos de interrogación → casi seguro es texto del bot
		if (substr_count($v, '?') + substr_count($v, '¿') >= 2) return true;
		// Contiene frases típicas del prompt del bot
		$prompts = array(
			'mensajero ubique', 'recibir el paquete', 'genera costos adicionales',
			'verificar con interrapidísimo', 'verificar con interrapidisimo',
			'¿cuál es', 'cuál es el barrio',
		);
		foreach ($prompts as $needle) {
			if (stripos($v, $needle) !== false) return true;
		}
		return false;
	}

	/**
	 * Re-extracción de datos vía Groq (Llama 3.3 70B) cuando el parser local
	 * deja campos clave vacíos o sospechosos.
	 *
	 * Usa solo el contenido `[CLIENTE]` para no confundirse con preguntas del bot.
	 * Tiene timeout duro de 6s — si Groq tarda más, retorna null y se sigue
	 * con lo que se tenga.
	 *
	 * Retorna ['nombre'=>..., 'documento'=>..., 'direccion'=>..., 'productos'=>[...]]
	 * o null si falla / sin api key / response inválido.
	 */
	private function _aiExtractFallback($content, $celular_norm)
	{
		$secretsFile = APPPATH . 'config/secrets.php';
		if (file_exists($secretsFile)) include($secretsFile);
		$api_key = isset($config['groq_api_key']) ? $config['groq_api_key'] : '';
		if (empty($api_key)) return null;

		$ai_cfg = $this->config->item('ai_models');
		$model = isset($ai_cfg['groq']['default']) ? $ai_cfg['groq']['default'] : 'llama-3.3-70b-versatile';

		$clientText = $this->_extractClientOnly($content);
		if (mb_strlen($clientText) > 6000) $clientText = mb_substr($clientText, -6000); // últimos 6k chars

		$system = "Eres un parser estricto de conversaciones de WhatsApp en español (Colombia). "
			. "Extrae los datos del cliente SOLO de lo que el cliente escribió. "
			. "El celular del cliente es {$celular_norm} — NUNCA lo uses como cédula. "
			. "Si un dato no aparece claramente, deja la cadena vacía. NO inventes. "
			. "Responde EXCLUSIVAMENTE un JSON válido con esta forma exacta, sin texto adicional, sin markdown:\n"
			. "{\"nombre\":\"\", \"cedula\":\"\", \"direccion\":\"\", \"barrio\":\"\", \"ciudad\":\"\", \"departamento\":\"\", \"productos\":[{\"qty\":0,\"descripcion\":\"\"}]}";

		$payload = array(
			'model' => $model,
			'messages' => array(
				array('role' => 'system', 'content' => $system),
				array('role' => 'user', 'content' => "Conversación (solo mensajes del CLIENTE):\n\n" . $clientText),
			),
			'max_tokens' => 800,
			'temperature' => 0,
			'response_format' => array('type' => 'json_object'),
		);

		$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($payload),
			CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Authorization: Bearer ' . $api_key),
			CURLOPT_TIMEOUT => 6,
			CURLOPT_CONNECTTIMEOUT => 3,
			CURLOPT_SSL_VERIFYPEER => false,
		));
		$resp = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($code !== 200 || !$resp) {
			file_put_contents(APPPATH . 'logs/webhook_debug.log',
				date('Y-m-d H:i:s') . " AI_FALLBACK failed http={$code} resp_len=" . strlen((string)$resp) . "\n", FILE_APPEND);
			return null;
		}
		$decoded = json_decode($resp, true);
		$jsonStr = isset($decoded['choices'][0]['message']['content']) ? $decoded['choices'][0]['message']['content'] : '';
		$data = json_decode($jsonStr, true);
		if (!is_array($data)) {
			file_put_contents(APPPATH . 'logs/webhook_debug.log',
				date('Y-m-d H:i:s') . " AI_FALLBACK invalid json: " . substr($jsonStr, 0, 200) . "\n", FILE_APPEND);
			return null;
		}

		// Defensa: nunca dejar que el modelo nos devuelva el celular como cédula
		$celNorm = preg_replace('/[^0-9]/', '', (string)$celular_norm);
		if (!empty($data['cedula'])) {
			$cedNorm = preg_replace('/[^0-9]/', '', (string)$data['cedula']);
			if ($cedNorm === $celNorm) $data['cedula'] = '';
		}

		return $data;
	}

	private function _createReviewBudget($content, $phoneNum, $botConfig, $nombre, $celular_norm, $documento, $direccion, $total, $reason)
	{
		date_default_timezone_set("America/Bogota");
		$now = date('Y-m-d H:i:s');

		// 1. Resolver cliente: por celular, doc, o crear con info parcial
		$client = null;
		if ($celular_norm) $client = $this->clients_model->getClientByPhone($celular_norm);
		if (!$client && !empty($documento)) $client = $this->clients_model->getClientByIdNum($documento);

		$client_id = null;
		if ($client) {
			$client_id = $client->idClient;
			// Update info parcial si vino algo nuevo
			$update = array();
			if ($nombre && empty($client->name))    $update['name']    = $nombre;
			if ($documento && empty($client->idNum)) $update['idNum']  = $documento;
			if ($direccion && empty($client->address)) $update['address'] = $direccion;
			if (!empty($update)) $this->clients_model->update($client_id, $update);
		} else {
			// Crear cliente con lo que haya. Si no hay nombre, ponemos un placeholder
			// claro para que el vendedor lo identifique en el panel.
			$placeholder_name = $nombre ?: ('REVISAR — cel ' . ($celular_norm ?: 'desconocido'));
			$client_data = array(
				'idNum'     => $documento ?: ($celular_norm ?: ''),
				'name'      => $placeholder_name,
				'phone'     => $celular_norm,
				'cellphone' => $celular_norm,
				'address'   => $direccion ?: '',
				'city'      => '',
				'state'     => '',
				'vendor'    => $botConfig->default_vendor_id,
				'retail'    => 1,
				'rate'      => 0,
				'f_id'      => $this->clients_model->getHighestClientFid()->next_fid + 1,
			);
			$this->clients_model->save($client_data);
			$client_id = $this->db->insert_id();
		}

		// 2. Crear budget mínimo
		$budget_data = array(
			'clientId'   => $client_id,
			'vendorId'   => $botConfig->default_vendor_id,
			'storeId'    => $botConfig->default_store_id,
			'total'      => max(0, (int)$total),
			'date'       => $now,
			'state'      => 0,
			'e_commerce' => 1,
			'list_price' => 0,
			'hasIva'     => 0,
			'iva'        => 8,
			'comments'   => $reason . ' | Via: WhatsApp Bot.',
		);
		$this->budgets_model->save($budget_data);
		$budget_id = $this->budgets_model->lastID();

		// 3. Línea PENDIENTE de respaldo para que el budget tenga detalle
		try {
			$this->budgets_model->save_detail(array(
				'budgetId'  => $budget_id,
				'productId' => 'PENDIENTE',
				'quantity'  => 1,
				'unit'      => max(0, (int)$total),
				'base'      => max(0, (int)$total),
				'total'     => max(0, (int)$total),
			));
		} catch (\Throwable $e) {
			file_put_contents(APPPATH . 'logs/webhook_debug.log',
				date('Y-m-d H:i:s') . " REVIEW_BUDGET detail PENDIENTE FAIL budget_id={$budget_id}: " . $e->getMessage() . "\n", FILE_APPEND);
		}

		file_put_contents(APPPATH . 'logs/webhook_debug.log',
			date('Y-m-d H:i:s') . " REVIEW_BUDGET creado: budget_id={$budget_id} client_id={$client_id} reason={$reason}\n", FILE_APPEND);

		return $budget_id;
	}

	/**
	 * Extracción robusta del nombre del cliente desde el `raw` de la conversación.
	 *
	 * Capas en orden de confiabilidad:
	 *  1. Campo estructurado "Nombre: X" (cuando el bot genera el resumen).
	 *  2. Variantes "Nombre completo: X" / "Nombre del cliente: X" / "Nombre Juan Pérez".
	 *  3. Saludos del bot tipo "Gracias, X" / "Perfecto X" / "Hola, X". El nombre
	 *     que más se repite tras un saludo es el del cliente. Esto cubre el caso
	 *     en que el LLM dejó de imprimir el resumen estructurado al cierre.
	 *  4. Mensaje del cliente que parezca un nombre completo (2–4 palabras
	 *     capitalizadas, sin números ni términos de dirección).
	 *
	 * Diseñado para tolerar la deriva del LLM y evitar mandar pedidos a queue
	 * con nombre vacío cuando la conversación claramente identifica al cliente.
	 */
	private function _smartExtractName($content)
	{
		// Capa 1: campo estructurado clásico
		$nombre = $this->_extractField($content, 'Nombre');
		if (!empty($nombre)) return $nombre;

		// Capa 2: variantes "Nombre completo:" / "Nombre del cliente:" / "Nombre y apellido:"
		if (preg_match('/Nombre\s+(?:completo|del cliente|y apellido)\s*:\s*([^\n\[]{3,80})/iu', $content, $m)) {
			$cand = trim($m[1]);
			if ($cand !== '') return $cand;
		}
		// "Nombre Juan Pérez" sin colon
		if (preg_match('/Nombre\s+([A-Za-zÁÉÍÓÚáéíóúñÑ]{2,30}(?:\s+[A-Za-zÁÉÍÓÚáéíóúñÑ]{2,30}){0,4})/u', $content, $m)) {
			$cand = trim($m[1]);
			if (!preg_match('/^(completo|del|y|de)\b/i', $cand)) return $cand;
		}

		// Capa 3: saludo del bot. Patrón: "[BOT] ... Saludo[,]? Nombre [Apellido...]"
		// El bot suele saludar varias veces con el nombre. El que más se repita gana.
		$blacklist = array(
			'hola','hi','buenas','buenos','cliente','señor','senor','señora','senora',
			'don','dona','doña','amigo','amiga','listo','perfecto','gracias','si','sí',
			'no','excelente','bienvenido','bienvenida','claro','vale','jefe','master',
			'mi','tu','su','para','por','bueno','genial','ok','okay','ay','ah',
		);
		$greetingRegex = '/\[BOT\][^\n]*?\b(?:Gracias|Perfecto|Hola|Listo|Bienvenido|Bienvenida|Excelente|Buenas|Buenos|Encantado|Saludos)\s*,?\s+([A-ZÁÉÍÓÚÑ][a-záéíóúñ\']{1,25}(?:\s+[A-ZÁÉÍÓÚÑa-záéíóúñ\']{1,25}){0,4})/u';
		if (preg_match_all($greetingRegex, $content, $gm) && !empty($gm[1])) {
			$counts = array();
			foreach ($gm[1] as $cand) {
				$cand = trim($cand);
				if ($cand === '') continue;
				$first = mb_strtolower(strtok($cand, ' '));
				strtok('', ''); // reset
				if (in_array($first, $blacklist, true)) continue;
				if (preg_match('/[0-9]/', $cand)) continue;
				// Quitar trailing punctuation
				$cand = rtrim($cand, ".,;:!?¿¡");
				if (mb_strlen($cand) < 3) continue;
				$counts[$cand] = isset($counts[$cand]) ? $counts[$cand] + 1 : 1;
			}
			if (!empty($counts)) {
				arsort($counts);
				$top = array_key_first($counts);
				// Si aparece 2+ veces, alta confianza
				if ($counts[$top] >= 2) return $top;
				// Si solo 1 vez, igual aceptamos si tiene 2+ palabras (más señal)
				if (substr_count($top, ' ') >= 1) return $top;
			}
		}

		// Capa 4: primer mensaje del cliente que parezca nombre completo.
		// Formato: "[CLIENTE] Juan Pérez García" en línea propia, sin números, sin términos de dirección.
		if (preg_match_all('/\[CLIENTE\]\s+([^\n]{3,80})/u', $content, $cm)) {
			foreach ($cm[1] as $line) {
				$line = trim($line);
				// Descartar líneas con números, signos comunes de dirección, preguntas
				if (preg_match('/[0-9?¿]/', $line)) continue;
				if (preg_match('/\b(?:carrera|calle|cra|cll|kr|av|avenida|barrio|ciudad|departamento|dpto|urbano|rural|si|no|hola|gracias|listo|claro|ok)\b/iu', $line)) continue;
				// Debe ser 2-5 palabras, todas con mayúscula inicial (acepta tildes)
				if (preg_match('/^([A-ZÁÉÍÓÚÑ][a-záéíóúñ\']{1,20}(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ\']{1,20}){1,4})$/u', $line, $nm)) {
					return trim($nm[1]);
				}
			}
		}

		return '';
	}

	private function _extractField($text, $fieldName)
	{
		// Limpiar asteriscos de formato WhatsApp
		$clean = preg_replace('/\*\s*\*([^*]+)\*/', '$1', $text);
		$clean = preg_replace('/\*([^*]+)\*/', '$1', $clean);
		$pattern = '/' . preg_quote($fieldName, '/') . '\s*:\s*(.+)/iu';
		// Buscar TODAS las coincidencias y tomar la última (más reciente)
		if (preg_match_all($pattern, $clean, $matches)) {
			$val = trim(end($matches[1]));
			$val = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $val);
			$val = preg_replace('/\(con envío gratis\)/i', '', $val);
			$val = preg_replace('/\(Premium\)/i', '', $val);
			return trim($val);
		}
		return '';
	}

	/**
	 * Parsear productos desde el bloque "Productos:" multi-línea (formato nuevo).
	 * Ejemplo:
	 *   Productos:
	 *   - 20x 12LED-12V-H | $65000
	 *   - 10x 12LED-12V-I | $32500
	 * Cada línea: "- {qty}x {CODIGO} | ${subtotal}". Pipe como separador para evitar
	 * confusión con guiones internos de los códigos (12LED-24V-H).
	 */
	private function _parseProductsBlock($content)
	{
		$products = array();
		// Limpiar formato WhatsApp
		$clean = preg_replace('/\*\s*\*([^*]+)\*/', '$1', $content);
		$clean = preg_replace('/\*([^*]+)\*/', '$1', $clean);

		// Extraer la sección que va después de "Productos:" hasta "Total:" (o fin del texto)
		if (!preg_match('/Productos\s*:\s*\n(.*?)(?=\n\s*Total\s*:|\Z)/usi', $clean, $m)) {
			return $products;
		}
		$block = $m[1];

		// === Formato A: pipe-delimited (preferido) ===
		// "- 20x 12LED-12V-H | $65000"  ó  "* 20x CODE | $65000"  ó  "20x CODE | $65000"
		$lineRegex = '/^\s*[-*•]?\s*(\d+)\s*x\s+([A-Z0-9][A-Z0-9\-\/]*)\s*\|\s*\$?\s*([\d.,]+)\s*$/im';
		if (preg_match_all($lineRegex, $block, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $row) {
				$qty = (int) $row[1];
				$code = trim(strtoupper($row[2]));
				$subtotal = (int) preg_replace('/[^0-9]/', '', $row[3]);
				if ($qty <= 0 || $code === '' || $subtotal <= 0) continue;
				$resolved = $this->_resolveProductCode($code);
				$products[] = array(
					'qty' => $qty,
					'name' => $code,
					'code' => $resolved !== false ? $resolved : $code,
					'subtotal' => $subtotal,
				);
			}
			if (!empty($products)) return $products;
		}

		// === Formato B: descriptivo multi-línea (fallback al prompt viejo) ===
		// Ejemplo:
		//   Módulos LED 3 Ideas estándar: 40 unidades
		//   Color: Morado
		//   Voltaje: 12V
		// Extraer color/voltaje del propio bloque y construir una sola línea.
		$blockColor = '';
		$blockVoltaje = '';
		if (preg_match('/Color\s*:\s*([^\n]+)/iu', $block, $cm)) $blockColor = trim($cm[1]);
		if (preg_match('/Voltaje\s*:\s*([^\n]+)/iu', $block, $vm)) $blockVoltaje = trim($vm[1]);

		// Buscar línea con cantidad: "PRODUCTO: 40 unidades", "40 unidades de PRODUCTO", "40 módulos PRODUCTO"
		$descriptiveRegexes = array(
			'/(.+?)\s*:\s*(\d+)\s*unidad(?:es)?/iu',           // "Módulos LED 3 Ideas: 40 unidades"
			'/(\d+)\s*unidad(?:es)?\s+(?:de\s+)?(.+)/iu',      // "40 unidades de Módulos LED"
			'/(\d+)\s*m[oó]dulos?\s+(.+)/iu',                  // "40 módulos LED"
			'/(\d+)\s*x\s+(.+)/iu',                            // "40x Módulos"
		);
		foreach (preg_split('/\n/', $block) as $line) {
			$line = trim(preg_replace('/^[\-\*\•\s]+/u', '', $line));
			if ($line === '' || stripos($line, 'color') === 0 || stripos($line, 'voltaje') === 0) continue;

			$qty = 0; $name = '';
			foreach ($descriptiveRegexes as $rx) {
				if (preg_match($rx, $line, $mm)) {
					if (ctype_digit(trim($mm[1]))) { $qty = (int)$mm[1]; $name = trim($mm[2]); }
					else { $qty = (int)$mm[2]; $name = trim($mm[1]); }
					break;
				}
			}
			if ($qty <= 0 || $name === '') continue;

			$code = $this->_findProductCode($name, $blockVoltaje, $blockColor);
			$products[] = array(
				'qty' => $qty,
				'name' => $name,
				'code' => $code ?: 'PENDIENTE',
				'subtotal' => 0, // se reparte el total al guardar
			);
			break; // formato descriptivo viene con UNA sola línea de producto + atributos
		}

		return $products;
	}

	/**
	 * Parsear productos desde la línea "Pedido:" del resumen (formato viejo, fallback).
	 * Formato: "10x MODULO 3LED - $55000, 5x CANDADO - $75000"
	 */
	private function _parsePedidoProducts($pedidoStr, $productosStr, $voltaje, $color)
	{
		$products = array();
		// Probar primero con pedidoStr, luego con productosStr (algunos prompts usan uno u otro)
		$source = trim((string)$pedidoStr) !== '' ? $pedidoStr : (string)$productosStr;
		if (trim($source) === '') return $products;

		// Patrones soportados, en orden:
		// A) "10x PRODUCTO - $55.000"
		// B) "10 x PRODUCTO ($55000)"
		// C) "10 unidades de PRODUCTO" (sin precio — subtotal=0, se calcula al final)
		// D) "PRODUCTO: 40 unidades"
		// E) Texto libre con un solo número y palabras LED → 1 sola línea
		$items = preg_split('/[,\n]+/', $source);

		foreach ($items as $item) {
			$item = trim($item);
			if (empty($item)) continue;
			$item = preg_replace('/^[\-\*\•]\s*/u', '', $item);

			$qty = 0; $productName = ''; $subtotal = 0;

			if (preg_match('/(\d+)\s*x\s+(.+?)\s*[-–]\s*\$?\s*([\d\.,]+)/iu', $item, $m)) {
				$qty = (int) $m[1];
				$productName = trim($m[2]);
				$subtotal = (int) preg_replace('/[^0-9]/', '', $m[3]);
			} elseif (preg_match('/(\d+)\s*x\s+(.+?)\s*\(\s*\$?\s*([\d\.,]+)\s*\)/iu', $item, $m)) {
				$qty = (int) $m[1]; $productName = trim($m[2]);
				$subtotal = (int) preg_replace('/[^0-9]/', '', $m[3]);
			} elseif (preg_match('/(\d+)\s*x\s+(.+)/iu', $item, $m)) {
				$qty = (int) $m[1]; $productName = trim($m[2]); $subtotal = 0;
			} elseif (preg_match('/(\d+)\s*unidad(?:es)?\s+(?:de\s+)?(.+)/iu', $item, $m)) {
				$qty = (int) $m[1]; $productName = trim($m[2]); $subtotal = 0;
			} elseif (preg_match('/(.+?)\s*[:=]\s*(\d+)\s*unidad/iu', $item, $m)) {
				$qty = (int) $m[2]; $productName = trim($m[1]); $subtotal = 0;
			} else {
				continue;
			}

			if ($qty <= 0 || $productName === '') continue;

			$code = $this->_findProductCode($productName, $voltaje, $color);

			$products[] = array(
				'qty' => $qty,
				'name' => $productName,
				'code' => $code ?: 'PENDIENTE',
				'subtotal' => $subtotal,
			);
		}

		return $products;
	}

	/**
	 * Buscar código de producto en la BD por nombre descriptivo
	 */
	private function _findProductCode($name, $voltaje, $color)
	{
		$haystack = mb_strtolower(trim((string)$name . ' ' . $voltaje . ' ' . $color));

		// 1) Si el texto YA contiene un código tipo "3LED-12V-B" o "MODULO-XXX", úsalo.
		if (preg_match('/\b([A-Z0-9]+(?:-[A-Z0-9]+){1,3})\b/i', (string)$name, $cm)) {
			$try = strtoupper($cm[1]);
			$product = $this->db->where('idProduct', $try)->where('deleted', 0)->get('products')->row();
			if ($product) return $product->idProduct;
		}

		// 2) Patrón LED: extraer número de leds, voltaje y color del haystack completo
		//    (incluso si BuilderBot lo manda en el nombre: "MODULO 6LED ROJO 12V")
		// Acepta "3LED", "3 LED", "LED 3", "3led", etc.
		$numLeds = '';
		if (preg_match('/(\d+)\s*led/i', $haystack, $m)) {
			$numLeds = $m[1];
		} elseif (preg_match('/\bled\s+(\d+)\b/i', $haystack, $m)) {
			$numLeds = $m[1];
		}
		if ($numLeds !== '') {

			// Voltaje (del campo o embebido en el nombre). Default 12V si no se especifica.
			$v = '12V';
			if (preg_match('/\b24\s*v(?:olt)?/i', $haystack)) $v = '24V';

			// Color — mapeo REAL de la tabla products de Ledxury:
			// A=BLANCO, B=BLANCO CALIDO, C=ROJO, D=AMARILLO, E=AZUL, F=VERDE,
			// G=ROSADO, H=MORADO, I=AZUL ICE, J=VERDE LIMON, K=TURQUESA
			// Nota: las claves más específicas van PRIMERO para que "blanco calido" no
			// caiga en "blanco". El break asegura que solo se aplique una.
			$colorMap = array(
				'blanco calido' => 'B', 'blanco cálido' => 'B', 'calido' => 'B', 'cálido' => 'B',
				'blanco frio' => 'A', 'blanco frío' => 'A', 'blanco' => 'A',
				'rojo' => 'C',
				'amarillo' => 'D',
				'azul ice' => 'I', 'ice' => 'I',
				'azul' => 'E',
				'verde limon' => 'J', 'verde limón' => 'J',
				'verde' => 'F',
				'rosado' => 'G', 'rosa' => 'G',
				'morado' => 'H',
				'turquesa' => 'K',
			);
			$c = '';
			foreach ($colorMap as $needle => $abbr) {
				if (stripos($haystack, $needle) !== false) { $c = $abbr; break; }
			}

			// Probar variantes: LED y LES (legacy), con/sin color
			foreach (array('LED', 'LES') as $suffix) {
				if ($c) {
					$try = $numLeds . $suffix . '-' . $v . '-' . $c;
					$product = $this->db->where('idProduct', $try)->where('deleted', 0)->get('products')->row();
					if ($product) return $product->idProduct;
				}
				// Sin color, solo voltaje (último recurso)
				$try2 = $numLeds . $suffix . '-' . $v;
				$product = $this->db->like('idProduct', $try2 . '-', 'after')
					->where('deleted', 0)->limit(1)->get('products')->row();
				if ($product) return $product->idProduct;
			}
		}

		// 3) LIKE por idProduct con la palabra suelta (ej. "3LED-12V-A" matchea "3LED")
		if (preg_match('/[A-Z0-9\-]{4,}/i', (string)$name, $cm2)) {
			$product = $this->db->like('idProduct', strtoupper($cm2[0]), 'both')
				->where('deleted', 0)
				->limit(1)
				->get('products')->row();
			if ($product) return $product->idProduct;
		}

		// 4) bot_product_aliases — texto del cliente normalizado al código real.
		// Busca primero match exacto, luego LIKE para tolerar variaciones.
		// Ej: "candado con alarma" → DISC-ALARM, "exploradora robot" → ACS-SD-38.
		$alias_norm = $this->_normalizeAlias((string)$name);
		if ($alias_norm !== '') {
			$alias = $this->db->where('alias_norm', $alias_norm)->get('bot_product_aliases')->row();
			if (!$alias) {
				// Match parcial: el nombre que vino contiene un alias conocido (o viceversa)
				$alias = $this->db->where("alias_norm LIKE", '%' . $this->db->escape_like_str($alias_norm) . '%', false)
					->or_where("? LIKE CONCAT('%', alias_norm, '%')", $alias_norm, false)
					->limit(1)
					->get('bot_product_aliases')->row();
			}
			if ($alias && !empty($alias->product_code)) {
				$exists = $this->db->where('idProduct', $alias->product_code)->where('deleted', 0)->count_all_results('products');
				if ($exists > 0) {
					$this->db->where('id', $alias->id)->set('hits', 'hits + 1', false)->update('bot_product_aliases');
					return $alias->product_code;
				}
			}
		}

		// 5) Último recurso: LIKE por description (la columna correcta, no "name")
		$product = $this->db->like('description', (string)$name, 'both')
			->where('deleted', 0)
			->limit(1)
			->get('products')->row();
		if ($product) return $product->idProduct;

		return null;
	}

	// ── Sheet Sync: Recibir filas del Google Sheet y crear presupuestos ──

	/**
	 * POST: /webhook/sheet-sync
	 * Recibe una fila del Google Sheet "Registros" y crea un presupuesto.
	 * Auth: X-Webhook-Secret header
	 *
	 * Payload esperado (columnas del Sheet):
	 * {
	 *   nombre, documento, direccion, productos, cantidad, voltaje, color,
	 *   celular, total, fecha, vendedor, tipoenvio, row_index
	 * }
	 */
	public function receiveSheetRow()
	{
		$this->load->library('builderbot_lib');
		$this->load->model('builderbot_model');

		header('Content-Type: application/json');

		$raw = file_get_contents('php://input');
		$data = json_decode($raw, true);

		if (empty($data)) {
			echo json_encode(['success' => false, 'error' => 'Payload vacío']);
			return;
		}

		// Validar secret
		$secret = $this->input->get_request_header('X-Webhook-Secret', true);
		if (!$this->builderbot_lib->validateWebhook($secret)) {
			http_response_code(401);
			echo json_encode(['success' => false, 'error' => 'Secret inválido']);
			return;
		}

		// Convertir columnas del Sheet a formato de process_webhook_sale
		$productos = $this->_sheetRowToProducts($data);

		if (empty($productos)) {
			echo json_encode(['success' => false, 'error' => 'No se pudieron resolver los productos']);
			return;
		}

		// Resolver vendedor
		$vendor_id = $this->parse_vendor($data);
		if (!$vendor_id) $vendor_id = $this->default_vendor;

		// Armar payload estándar
		$sale_data = [
			'nombre'    => $data['nombre'] ?? '',
			'documento' => $data['documento'] ?? '',
			'celular'   => $data['celular'] ?? '',
			'email'     => '',
			'direccion' => $data['direccion'] ?? '',
			'tipoenvio' => $data['tipoenvio'] ?? 'envio gratis',
			'productos' => $productos,
		];

		// Insertar en cola
		$this->db->insert('bot_sales_queue', [
			'payload'   => json_encode($sale_data),
			'status'    => 'processing',
			'vendor_id' => $vendor_id,
			'api_key'   => 'sheet-sync',
		]);
		$queue_id = $this->db->insert_id();

		// Procesar
		$result = $this->process_webhook_sale($sale_data, $vendor_id);

		if (isset($result['success']) && $result['success']) {
			$this->db->where('id', $queue_id)->update('bot_sales_queue', [
				'status'       => 'completed',
				'budget_id'    => $result['budget_id'],
				'processed_at' => date('Y-m-d H:i:s'),
			]);

			// Log en builderbot_webhooks para que aparezca en el dashboard
			$botConfig = $this->builderbot_model->getConfigs(true);
			$bot_id = !empty($botConfig) ? $botConfig[0]->id : null;
			$this->builderbot_model->saveWebhook([
				'bot_config_id' => $bot_id,
				'event_type'    => 'sale',
				'raw_payload'   => $raw,
				'status'        => 'processed',
				'queue_id'      => $queue_id,
			]);

			echo json_encode([
				'success'   => true,
				'budget_id' => $result['budget_id'],
				'row_index' => $data['row_index'] ?? null,
			]);
		} else {
			$error_msg = $result['error'] ?? 'Error desconocido';
			$this->db->where('id', $queue_id)->update('bot_sales_queue', [
				'status'        => 'failed',
				'error_message' => $error_msg,
				'attempts'      => 1,
				'processed_at'  => date('Y-m-d H:i:s'),
			]);

			echo json_encode(['success' => false, 'error' => $error_msg, 'row_index' => $data['row_index'] ?? null]);
		}
	}

	/**
	 * POST: /webhook/sheet-message
	 * Registra un mensaje de WhatsApp enviado desde el Apps Script
	 */
	public function receiveSheetMessage()
	{
		$this->load->library('builderbot_lib');
		$this->load->model('builderbot_model');

		header('Content-Type: application/json');

		$raw = file_get_contents('php://input');
		$data = json_decode($raw, true);

		if (empty($data)) {
			echo json_encode(['success' => false, 'error' => 'Payload vacío']);
			return;
		}

		$secret = $this->input->get_request_header('X-Webhook-Secret', true);
		if (!$this->builderbot_lib->validateWebhook($secret)) {
			http_response_code(401);
			echo json_encode(['success' => false, 'error' => 'Secret inválido']);
			return;
		}

		$botConfig = $this->builderbot_model->getConfigs(true);
		$bot_id = !empty($botConfig) ? $botConfig[0]->id : null;

		$this->builderbot_model->saveMessage([
			'bot_config_id' => $bot_id,
			'direction'     => 'outgoing',
			'phone_number'  => $data['phone'] ?? '',
			'content'       => $data['content'] ?? '',
			'media_url'     => null,
			'status'        => ($data['success'] ?? false) ? 'sent' : 'failed',
			'sent_by'       => 'apps-script',
		]);

		echo json_encode(['success' => true]);
	}

	/**
	 * Convierte columnas del Sheet (productos, cantidad, voltaje, color) a array de productos
	 * Ejemplo: productos="modulos 3 LED", cantidad="40 módulos", voltaje="24V", color="Azul hielo"
	 * → [{ codigo: "3LED-24V-I", cantidad: 40, precio: X }]
	 */
	private function _sheetRowToProducts($row)
	{
		$productos_text = strtolower(trim($row['productos'] ?? ''));
		$cantidad_text = trim($row['cantidad'] ?? '');
		$voltaje_text = strtolower(trim($row['voltaje'] ?? '12v'));
		$color_text = strtolower(trim($row['color'] ?? ''));
		$total = floatval($row['total'] ?? 0);

		// Extraer número de cantidad ("40 módulos" → 40)
		preg_match('/(\d+)/', $cantidad_text, $cant_match);
		$cantidad = isset($cant_match[1]) ? intval($cant_match[1]) : 1;

		// Extraer voltaje ("24V" → "24V", "24v" → "24V")
		preg_match('/(\d+)\s*v/i', $voltaje_text, $volt_match);
		$voltaje = isset($volt_match[1]) ? $volt_match[1] . 'V' : '12V';

		// Verificar si es un producto especial (no LED)
		foreach ($this->product_map as $keyword => $code) {
			if (strpos($productos_text, $keyword) !== false) {
				$precio = $cantidad > 0 ? round($total / $cantidad) : $total;
				return [['codigo' => $code, 'cantidad' => $cantidad, 'precio' => $precio]];
			}
		}

		// Extraer número de LEDs ("modulos 3 LED" → 3, "6 LED" → 6)
		preg_match('/(\d+)\s*led/i', $productos_text, $led_match);
		$num_leds = isset($led_match[1]) ? $led_match[1] : '';

		if (empty($num_leds)) {
			// Intentar "3 modulos" o "modulos 3"
			preg_match('/modulos?\s*(\d+)|(\d+)\s*modulos?/i', $productos_text, $mod_match);
			$num_leds = $mod_match[1] ?? $mod_match[2] ?? '';
		}

		if (empty($num_leds) || empty($color_text)) {
			return [];
		}

		// Mapear color a letra
		$color_letter = $this->map_color_to_letter($color_text, true);

		// Construir código: {num_leds}LED-{voltaje}-{color_letter}
		$codigo = $num_leds . 'LED-' . $voltaje . '-' . $color_letter;
		$precio = $cantidad > 0 ? round($total / $cantidad) : $total;

		return [['codigo' => $codigo, 'cantidad' => $cantidad, 'precio' => $precio]];
	}

	/**
	 * RECOVERY ONE-SHOT — recupera presupuestos huérfanos históricos.
	 *
	 * Busca todas las conversaciones con tag_id=2 (Venta) y budget_id NULL,
	 * reconstruye la conversación desde builderbot_messages y la pasa por el
	 * parser nuevo (_processPedidoConfirmado con cronMode=true). Gracias a
	 * _createReviewBudget, el parser SIEMPRE devuelve budget_id, así toda
	 * huérfana queda con presupuesto asociado.
	 *
	 * Uso:  GET /sisvent/rest/BotImport/recoverOrphanSales?cron_key=...
	 * Devuelve JSON con stats. Ejecuta hasta 100 huérfanas por invocación.
	 *
	 * Después de ejecutar y validar, este método se puede dejar o eliminar.
	 * Como protección extra usa un cron_key para no quedar abierto.
	 */
	public function recoverOrphanSales()
	{
		header('Content-Type: application/json');
		if ($this->input->get('cron_key') !== 'sisvent_cron_2024_tracking') {
			http_response_code(401);
			echo json_encode(['ok' => false, 'error' => 'unauthorized']);
			return;
		}
		set_time_limit(300);
		date_default_timezone_set("America/Bogota");

		$this->load->model('builderbot_model');
		$this->load->model('clients_model');

		// Filtros opcionales para hacerlo más selectivo
		$bot_filter = $this->input->get('bot');     // 'Bogot' / 'Medell' / 'Barranquilla' o vacío
		$max        = (int)($this->input->get('max') ?: 100);
		$max        = min(200, max(1, $max));

		$this->db->select('bc.id, bc.phone, bc.bot_config_id, bc.client_name')
			->from('bot_conversations bc')
			->join('builderbot_configs bot', 'bot.id = bc.bot_config_id', 'left')
			->where('bc.tag_id', 2)
			->where('bc.budget_id IS NULL', null, false);
		if (!empty($bot_filter)) {
			$this->db->like('bot.name', $bot_filter);
		}
		$orphans = $this->db->order_by('bc.last_message_at', 'ASC')
			->limit($max)
			->get()->result();

		$stats = ['total' => count($orphans), 'recovered' => 0, 'errors' => 0, 'details' => []];

		foreach ($orphans as $orphan) {
			$botConfig = $this->builderbot_model->getConfig($orphan->bot_config_id);
			if (!$botConfig) {
				$stats['errors']++;
				$stats['details'][] = ['conv_id' => (int)$orphan->id, 'status' => 'no_bot_config'];
				continue;
			}

			// Reconstruir la conversación desde builderbot_messages.
			$msgs = $this->db->select('content, direction')
				->from('builderbot_messages')
				->where('conversation_id', $orphan->id)
				->order_by('id', 'ASC')
				->get()->result();

			if (empty($msgs)) {
				$stats['errors']++;
				$stats['details'][] = ['conv_id' => (int)$orphan->id, 'status' => 'no_messages'];
				continue;
			}

			$content = '';
			foreach ($msgs as $m) {
				$prefix = ($m->direction === 'incoming') ? '[CLIENTE]' : '[BOT]';
				$content .= "\n{$prefix} " . $m->content;
			}

			try {
				// cronMode=true → no encolar en bot_sales_queue (evita duplicados).
				$bid = $this->_processPedidoConfirmado($content, $orphan->phone, $botConfig, true);
				if ($bid) {
					$this->db->where('id', $orphan->id)
						->update('bot_conversations', ['budget_id' => $bid]);
					$stats['recovered']++;
					$stats['details'][] = [
						'conv_id'   => (int)$orphan->id,
						'phone'     => $orphan->phone,
						'budget_id' => (int)$bid,
					];
				} else {
					$stats['errors']++;
					$stats['details'][] = ['conv_id' => (int)$orphan->id, 'status' => 'returned_null'];
				}
			} catch (Exception $e) {
				$stats['errors']++;
				$stats['details'][] = [
					'conv_id' => (int)$orphan->id,
					'status'  => 'exception',
					'msg'     => $e->getMessage(),
				];
			}
		}

		$stats['timestamp'] = date('Y-m-d H:i:s');
		echo json_encode($stats, JSON_UNESCAPED_UNICODE);
	}

	// ========================================================================
	// REEXTRACT AI — Endpoint para que el vendedor re-corra el AI sobre un
	// presupuesto ya creado (datos del cliente quedaron incompletos/raros).
	// ========================================================================

	/**
	 * GET /sisvent/rest/botimport/reextract_ai?budget_id=N
	 *
	 * Auth: sesión web (no API key). Acceso para admin (1, 10), gerente (2),
	 * o vendedor con bots_access. Vendedor de rol 3 solo sus propios pedidos.
	 *
	 * No aplica cambios — devuelve sugerencias para que el frontend las muestre
	 * y el vendedor decida campo por campo.
	 */
	public function reextract_ai()
	{
		header('Content-Type: application/json; charset=utf-8');

		$user_data = $this->session->userdata('user_data');
		if (empty($user_data)) {
			http_response_code(401);
			echo json_encode(['error' => 'No autenticado']);
			return;
		}
		$role = (int)($user_data['role'] ?? 0);
		$bots_access = !empty($user_data['bots_access']);
		if (!in_array($role, [1, 2, 3, 10], true) && !$bots_access) {
			http_response_code(403);
			echo json_encode(['error' => 'No autorizado']);
			return;
		}

		$budget_id = (int)$this->input->get('budget_id');
		if ($budget_id <= 0) {
			http_response_code(400);
			echo json_encode(['error' => 'budget_id inválido']);
			return;
		}

		$this->load->model('budgets_model');
		$this->load->model('clients_model');

		$budget = $this->budgets_model->getBudget($budget_id);
		if (empty($budget)) {
			http_response_code(404);
			echo json_encode(['error' => 'Presupuesto no encontrado']);
			return;
		}
		if (empty($budget->e_commerce)) {
			http_response_code(400);
			echo json_encode(['error' => 'No es presupuesto del bot']);
			return;
		}
		if ((int)$budget->state !== 0) {
			http_response_code(400);
			echo json_encode(['error' => 'Solo se puede re-extraer en presupuestos en borrador']);
			return;
		}
		// Vendedor solo sus propios presupuestos
		if ($role === 3 && (string)$budget->vendorId !== (string)$user_data['uname']) {
			http_response_code(403);
			echo json_encode(['error' => 'Solo puedes re-extraer tus propios presupuestos']);
			return;
		}

		$client = $this->clients_model->getClient($budget->clientId);
		$celular_norm = $client ? Clients_model::normalizePhone($client->cellphone ?? '') : '';

		// 1) Intentar conseguir el raw guardado en bot_sales_queue.payload
		$raw_text = '';
		$queue_row = $this->db->select('payload')
			->from('bot_sales_queue')
			->where('budget_id', $budget_id)
			->order_by('id', 'DESC')
			->limit(1)
			->get()->row();
		if ($queue_row && !empty($queue_row->payload)) {
			$payload_decoded = json_decode($queue_row->payload, true);
			if (is_array($payload_decoded) && !empty($payload_decoded['raw'])) {
				$raw_text = (string)$payload_decoded['raw'];
			}
		}

		// 2) Si no había raw en payload, reconstruir desde builderbot_messages
		// usando el celular del cliente (últimos 50 mensajes).
		if ($raw_text === '' && $celular_norm !== '') {
			$msgs = $this->db->select('content, direction')
				->from('builderbot_messages')
				->where('phone_number', $celular_norm)
				->order_by('created_at', 'DESC')
				->limit(50)
				->get()->result();
			if (!empty($msgs)) {
				$lines = [];
				foreach (array_reverse($msgs) as $m) {
					$prefix = ($m->direction === 'incoming') ? '[CLIENTE]' : '[BOT]';
					$lines[] = $prefix . ' ' . $m->content;
				}
				$raw_text = implode("\n", $lines);
			}
		}

		if ($raw_text === '') {
			http_response_code(400);
			echo json_encode(['error' => 'No hay raw para re-extraer (sin payload con conversación ni mensajes en builderbot_messages)']);
			return;
		}

		// 3) Llamar al AI
		$extracted = $this->_aiExtractFallback($raw_text, $celular_norm);
		if (empty($extracted)) {
			http_response_code(500);
			echo json_encode(['error' => 'AI no devolvió resultado válido (timeout/error)']);
			return;
		}

		// 4) Devolver sugerencias + valores actuales para que el frontend
		// muestre comparación lado a lado.
		echo json_encode([
			'success' => true,
			'extracted' => $extracted,
			'current' => [
				'name'    => $client->name ?? '',
				'idNum'   => $client->idNum ?? '',
				'address' => $client->address ?? '',
				'city'    => $client->city ?? '',
				'state'   => $client->state ?? '',
			],
			'budget_id' => $budget_id,
			'client_id' => (int)$budget->clientId,
		], JSON_UNESCAPED_UNICODE);
	}

	/**
	 * POST /sisvent/rest/botimport/apply_reextract
	 * Body: budget_id, client_id, fields (JSON con name/idNum/address/city/state)
	 *
	 * Aplica los campos seleccionados al cliente. Solo los campos enviados
	 * se actualizan (no toca campos vacíos del request).
	 * Misma autorización que reextract_ai.
	 */
	public function apply_reextract()
	{
		header('Content-Type: application/json; charset=utf-8');

		$user_data = $this->session->userdata('user_data');
		if (empty($user_data)) {
			http_response_code(401);
			echo json_encode(['error' => 'No autenticado']);
			return;
		}
		$role = (int)($user_data['role'] ?? 0);
		$bots_access = !empty($user_data['bots_access']);
		if (!in_array($role, [1, 2, 3, 10], true) && !$bots_access) {
			http_response_code(403);
			echo json_encode(['error' => 'No autorizado']);
			return;
		}

		$budget_id = (int)$this->input->post('budget_id');
		$client_id = (int)$this->input->post('client_id');
		$fields_raw = $this->input->post('fields');
		if ($budget_id <= 0 || $client_id <= 0 || empty($fields_raw)) {
			http_response_code(400);
			echo json_encode(['error' => 'Parámetros inválidos']);
			return;
		}
		$fields = is_array($fields_raw) ? $fields_raw : json_decode((string)$fields_raw, true);
		if (!is_array($fields)) {
			http_response_code(400);
			echo json_encode(['error' => 'fields debe ser objeto/JSON válido']);
			return;
		}

		$this->load->model('budgets_model');
		$this->load->model('clients_model');

		$budget = $this->budgets_model->getBudget($budget_id);
		if (empty($budget) || (int)$budget->clientId !== $client_id) {
			http_response_code(404);
			echo json_encode(['error' => 'Presupuesto/cliente no coincide']);
			return;
		}
		if ((int)$budget->state !== 0) {
			http_response_code(400);
			echo json_encode(['error' => 'No se puede modificar un presupuesto procesado']);
			return;
		}
		if ($role === 3 && (string)$budget->vendorId !== (string)$user_data['uname']) {
			http_response_code(403);
			echo json_encode(['error' => 'Solo puedes modificar tus propios presupuestos']);
			return;
		}

		// Whitelist de campos editables
		$allowed_keys = ['name', 'idNum', 'address', 'city', 'state'];
		$update = [];
		foreach ($allowed_keys as $k) {
			if (isset($fields[$k]) && trim((string)$fields[$k]) !== '') {
				$update[$k] = trim((string)$fields[$k]);
			}
		}
		if (empty($update)) {
			echo json_encode(['success' => true, 'updated' => 0, 'message' => 'Nada que aplicar']);
			return;
		}

		$ok = $this->clients_model->update($client_id, $update);
		if (!$ok) {
			http_response_code(500);
			echo json_encode(['error' => 'No se pudo actualizar el cliente']);
			return;
		}

		// Auditoría: log al final del comments del budget para no perder el rastro
		$audit_who = $user_data['uname'] ?? '?';
		$audit_when = date('Y-m-d H:i');
		$audit_what = implode(', ', array_keys($update));
		$prev_comments = (string)($budget->comments ?? '');
		$new_comments = $prev_comments . " | [AI-REEXTRACT {$audit_when} por {$audit_who}: {$audit_what}]";
		$this->db->where('idBudget', $budget_id)->update('budgets', ['comments' => $new_comments]);

		echo json_encode([
			'success' => true,
			'updated' => count($update),
			'fields' => array_keys($update),
		], JSON_UNESCAPED_UNICODE);
	}
}
