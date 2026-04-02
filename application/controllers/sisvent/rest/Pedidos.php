<?php
defined('BASEPATH') OR exit('No direct script access allowed');




class Pedidos extends CI_Controller {

	public function __construct(){
		parent::__construct();
		
		$this->load->model("invoices_model");
        $this->load->model("stores_model");
        $this->load->model("vendors_model");
        $this->load->model("clients_model");
        $this->load->model("inventory_model");
        $this->load->model("users_model");
	}

	
	
	public function saveOrder()
	{
		$order = json_decode($this->input->post("order"));

		$data  = array(
			'date' => $order->date, 
			'user' => $order->user,
			'client' => $order->client,
			'city' => $order->city
		);

		echo "<pre>";
		print_r($data);
		echo "</pre>";
		echo "<br>";
		
	}

	public function getOrder($idInvoice)
	{
		$data  = array(
			'invoice' => $this->invoices_model->getInvoice($idInvoice), 
			'details' => $this->invoices_model->getDetails($idInvoice),
		);
		$this->load->view("sisvent/commercial/invoices/view",$data);
		echo "<pre>";
		echo utf8_encode (json_encode($data));
		echo "</pre>";
		echo "<br>";
	}



//Para leer el sheet de google
public function sheet_read()
{
    // Parámetros GET
    $sheetId = $this->input->get('sheet_id');   // ej: 1sVhKNbjax3dSgl-81VsakE7Z8JHVXWxUkYDmizZH0Qk
    $gid     = $this->input->get('gid') ?? '0'; // pestaña por defecto
    $limit   = (int)($this->input->get('limit') ?? 20);

    if (!$sheetId) {
        return $this->json(400, ['error' => 'Falta sheet_id']);
    }

    // Construye URL CSV público
    $csvUrl = sprintf(
        'https://docs.google.com/spreadsheets/d/%s/export?format=csv&id=%s&gid=%s',
        urlencode($sheetId),
        urlencode($sheetId),
        urlencode($gid)
    );

    try {
        $csv = $this->http_get($csvUrl);
        if ($csv === false || $csv === '') {
            return $this->json(502, ['error' => 'No se pudo descargar el CSV (revisa que la hoja esté compartida como lector con enlace)']);
        }

        // Parseo CSV → headers y filas asociativas
        [$headers, $rows] = $this->parse_csv_assoc($csv, $limit);

        return $this->json(200, [
            'ok'      => true,
            'headers' => $headers,
            'rows'    => $rows,
            'count'   => count($rows),
            'note'    => 'Solo lectura para verificación. Si luego quitas el acceso público, usaremos Service Account o Apps Script.'
        ]);
    } catch (\Throwable $e) {
        return $this->json(500, ['error' => $e->getMessage()]);
    }
}

/**
 * Descarga por cURL (más robusto que file_get_contents).
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
        CURLOPT_USERAGENT      => 'MAM-Budgets-Reader/1.0'
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
 * Convierte CSV en [headers, rows] usando la primera fila como encabezados.
 * Normaliza headers a minúsculas, sin tildes y con guion_bajo.
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
            // descarta filas totalmente vacías
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
 * Normaliza encabezados: minúsculas, sin acentos, espacios→guion_bajo.
 */
private function normalize_header($h)
{
    $h = mb_strtolower(trim((string)$h), 'UTF-8');

    // reemplaza tildes
    $trans = [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
        'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u',
        'ñ'=>'n'
    ];
    $h = strtr($h, $trans);

    // espacios y símbolos → _
    $h = preg_replace('/[^a-z0-9]+/','_', $h);
    $h = trim($h, '_');

    return $h;
}

/**
 * Helper JSON (usa el tuyo si ya existe).
 */
private function json($status, $data) {
    return $this->output->set_status_header($status)
        ->set_content_type('application/json')
        ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
}


/**
 * Menú para enviar a tu API
 */
function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu('MAM Presupuestos')
    .addItem('Enviar filas a la API', 'enviarFilas')
    .addToUi();
}

function enviarFilas() {
  const hoja = SpreadsheetApp.getActive().getSheetByName('Hoja 1'); // ajusta nombre
  const datos = hoja.getDataRange().getValues(); // incluye encabezados
  const headers = datos[0].map(h => (h || '').toString().trim().toLowerCase());
  const rows = [];

  for (let i = 1; i < datos.length; i++) {
    const row = {};
    for (let j = 0; j < headers.length; j++) {
      row[headers[j]] = (datos[i][j] || '').toString().trim();
    }
    // Solo filas con nombre + documento + cantidad
    if (row.nombre && row.documento && row.cantidad) {
      rows.push({
        nombre: row.nombre,
        documento: row.documento,
        direccion: row.direccion,
        modulos: row.modulos,
        cantidad: row.cantidad,
        voltaje: row.voltaje,
        color: row.color,
        celular: row.celular,
        total: row.total,
        fecha: row.fecha
      });
    }
  }

  const payload = { rows };
  const url = 'https://TU-DOMINIO/rest/pedidos/import';
  const apiKey = 'TU-API-KEY-SEGURA'; // o usa PropertiesService

  const resp = UrlFetchApp.fetch(url, {
    method: 'post',
    contentType: 'application/json',
    muteHttpExceptions: true,
    payload: JSON.stringify(payload),
    headers: { 'X-API-KEY': apiKey }
  });

  const code = resp.getResponseCode();
  const body = resp.getContentText();
  SpreadsheetApp.getUi().alert(`API respondió ${code}:\n${body}`);
}

	
}