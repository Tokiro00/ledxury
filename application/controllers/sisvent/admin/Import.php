<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;

class Import extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('importar_datos');
        $this->load->model('products_model');
        $this->load->model('clients_model');
        $this->load->model('providers_model');
    }

    // ========================================================================
    // VISTA PRINCIPAL
    // ========================================================================

    public function index()
    {
        $data = array(
            'import_error' => $this->session->userdata('import_error') ?: null,
            'import_success' => $this->session->userdata('import_success') ?: null
        );
        $this->session->unset_userdata('import_error');
        $this->session->unset_userdata('import_success');
        $this->load->view('sisvent/admin/import/index', $data);
    }

    // ========================================================================
    // IMPORTAR PRODUCTOS
    // ========================================================================

    public function products()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $result = $this->_processUpload('products');

        $data = array('results' => $result);
        $this->load->view('sisvent/admin/import/index', $data);
    }

    // ========================================================================
    // IMPORTAR CLIENTES
    // ========================================================================

    public function clients()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $result = $this->_processUpload('clients');

        $data = array('results' => $result);
        $this->load->view('sisvent/admin/import/index', $data);
    }

    // ========================================================================
    // IMPORTAR PROVEEDORES
    // ========================================================================

    public function providers()
    {
        $this->outh_model->CSRFVerify();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

        $result = $this->_processUpload('providers');

        $data = array('results' => $result);
        $this->load->view('sisvent/admin/import/index', $data);
    }

    // ========================================================================
    // IMPORTAR FOTOS DESDE ZIP
    // ========================================================================

    public function photos()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            redirect(base_url() . 'sisvent/admin/import');
            return;
        }
        $this->outh_model->CSRFVerify();

        $uploadPath = FCPATH . 'uploads/imports/';
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

        $config['upload_path'] = $uploadPath;
        $config['allowed_types'] = 'zip';
        $config['max_size'] = 512000; // 500MB
        $config['file_name'] = 'photos_' . date('YmdHis');

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file')) {
            $this->session->set_userdata('import_error', $this->upload->display_errors('', ''));
            redirect(base_url() . 'sisvent/admin/import');
            return;
        }

        $uploadData = $this->upload->data();
        $zipPath = $uploadData['full_path'];

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== TRUE) {
            $this->session->set_userdata('import_error', 'No se pudo abrir el archivo ZIP.');
            redirect(base_url() . 'sisvent/admin/import');
            return;
        }

        $destPath = FCPATH . 'uploads/products/';
        if (!is_dir($destPath)) mkdir($destPath, 0755, true);

        $updated = 0;
        $notFound = 0;
        $errors = 0;
        $imageExts = array('jpg', 'jpeg', 'png', 'gif', 'webp');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileName = $zip->getNameIndex($i);

            // Skip directories and hidden files
            if (substr($fileName, -1) === '/' || strpos($fileName, '__MACOSX') !== false || strpos($fileName, '.') === 0) continue;

            // Get just the filename (in case it's inside a subfolder)
            $baseName = basename($fileName);
            $ext = strtolower(pathinfo($baseName, PATHINFO_EXTENSION));

            if (!in_array($ext, $imageExts)) continue;

            $productCode = pathinfo($baseName, PATHINFO_FILENAME);

            // Check if product exists
            $product = $this->products_model->getProduct($productCode);
            if (!$product) {
                $notFound++;
                continue;
            }

            // Extract and save the image
            $imageContent = $zip->getFromIndex($i);
            if ($imageContent === false) {
                $errors++;
                continue;
            }

            $newFileName = $productCode . '.' . $ext;
            $destFile = $destPath . $newFileName;

            // Overwrite if exists
            if (file_put_contents($destFile, $imageContent) !== false) {
                // Update product picture_url in database
                $picUrl = 'products/' . $newFileName;
                $this->db->where('idProduct', $productCode);
                $this->db->update('products', array('picture_url' => $picUrl, 'updated_at' => date('Y-m-d H:i:s')));
                $updated++;
            } else {
                $errors++;
            }
        }

        $zip->close();

        // Clean up zip file
        if (file_exists($zipPath)) unlink($zipPath);

        $total = $updated + $notFound + $errors;
        $this->session->set_userdata('import_success', "Fotos importadas: $updated actualizadas, $notFound productos no encontrados, $errors errores. Total procesadas: $total.");
        redirect(base_url() . 'sisvent/admin/import');
    }

    // ========================================================================
    // METODOS PRIVADOS
    // ========================================================================

    /**
     * Procesa la subida y lectura del archivo Excel/CSV
     */
    private function _processUpload($type)
    {
        $result = array('processed' => 0, 'success' => 0, 'errors' => 0);

        $config['upload_path'] = FCPATH . 'uploads/imports/';
        $config['allowed_types'] = 'xlsx|xls|csv';
        $config['max_size'] = 10240; // 10MB
        $config['file_name'] = $type . '_' . date('YmdHis');

        // Crear directorio si no existe
        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0755, true);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file')) {
            $this->session->set_userdata('import_error', $this->upload->display_errors('', ''));
            return $result;
        }

        $uploadData = $this->upload->data();
        $filePath = $uploadData['full_path'];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // La primera fila es el encabezado
            $header = array_shift($rows);
            $result['processed'] = count($rows);

            foreach ($rows as $row) {
                try {
                    $this->_importRow($type, $header, $row);
                    $result['success']++;
                } catch (Exception $e) {
                    $result['errors']++;
                }
            }

            $this->session->set_userdata('import_success', "Importacion completada: {$result['success']} registros importados, {$result['errors']} errores.");

        } catch (Exception $e) {
            $this->session->set_userdata('import_error', 'Error al procesar el archivo: ' . $e->getMessage());
        }

        // Limpiar archivo temporal
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        return $result;
    }

    /**
     * Importa una fila segun el tipo de datos
     */
    private function _importRow($type, $header, $row)
    {
        $data = array();
        foreach ($header as $i => $col) {
            if ($col !== null && isset($row[$i])) {
                $key = strtolower(trim($col));
                $key = str_replace(' ', '_', $key);
                $data[$key] = trim($row[$i]);
            }
        }

        if (empty($data)) return;

        switch ($type) {
            case 'products':
                $this->products_model->save($data);
                break;
            case 'clients':
                $this->clients_model->save($data);
                break;
            case 'providers':
                $this->providers_model->save($data);
                break;
        }
    }

    // ========================================================================
    // ASIGNAR PROVEEDOR POR PRODUCTO (carga masiva)
    // ========================================================================

    public function productProviders()
    {
        if ($this->input->method() !== 'post' || empty($_FILES['file']['name'])) {
            $this->session->set_userdata('import_error', 'Seleccione un archivo Excel.');
            redirect('sisvent/admin/import');
            return;
        }

        try {
            $spreadsheet = IOFactory::load($_FILES['file']['tmp_name']);
            $rows = $spreadsheet->getActiveSheet()->toArray();
        } catch (\Exception $e) {
            $this->session->set_userdata('import_error', 'Error al leer el archivo: ' . $e->getMessage());
            redirect('sisvent/admin/import');
            return;
        }

        if (empty($rows)) {
            $this->session->set_userdata('import_error', 'El archivo esta vacio.');
            redirect('sisvent/admin/import');
            return;
        }

        // Mapa de proveedores por nombre (case-insensitive) y por ID
        $providers = $this->providers_model->getProviders();
        $provByName = array();
        $provById = array();
        foreach ($providers as $p) {
            $provByName[mb_strtolower(trim($p->name))] = $p->idProvider;
            $provById[$p->idProvider] = $p->name;
        }

        $this->load->model('productproviders_model');

        $updated = 0;
        $skipped = 0;
        $notFound = 0;
        $provNotFound = 0;

        foreach ($rows as $i => $row) {
            $productId = trim($row[0] ?? '');
            $providerVal = trim($row[1] ?? '');

            // Saltar filas vacias o sin proveedor
            if (empty($productId) || empty($providerVal) || $providerVal === '0') {
                $skipped++;
                continue;
            }

            // Detectar si la primera fila es encabezado
            if ($i === 0 && preg_match('/^(codigo|producto|id|code)/i', $productId)) {
                continue;
            }

            // Verificar producto
            $product = $this->products_model->getProduct($productId);
            if (!$product) {
                $notFound++;
                continue;
            }

            // Resolver proveedor
            $providerId = null;
            if (is_numeric($providerVal) && isset($provById[(int)$providerVal])) {
                $providerId = (int) $providerVal;
            } else {
                $key = mb_strtolower($providerVal);
                if (isset($provByName[$key])) {
                    $providerId = $provByName[$key];
                }
            }

            if (!$providerId) {
                $provNotFound++;
                continue;
            }

            // Actualizar products.provider
            $this->db->where('idProduct', $productId);
            $this->db->update('products', array('provider' => $providerId));

            // Actualizar o crear en product_providers
            $existing = $this->db->where('productId', $productId)
                ->where('providerId', $providerId)
                ->get('product_providers')->row();

            if ($existing) {
                $this->productproviders_model->setDefault($productId, $providerId);
            } else {
                // Quitar default anterior
                $this->db->where('productId', $productId);
                $this->db->update('product_providers', array('isDefault' => 0));
                // Crear nuevo como default
                $this->productproviders_model->save(array(
                    'productId' => $productId,
                    'providerId' => $providerId,
                    'cost' => $product->cost_cop ?: 0,
                    'isDefault' => 1,
                    'priority' => 1
                ));
            }

            $updated++;
        }

        $msg = "{$updated} producto(s) actualizado(s).";
        if ($skipped > 0) $msg .= " {$skipped} sin proveedor (omitidos).";
        if ($notFound > 0) $msg .= " {$notFound} producto(s) no encontrado(s).";
        if ($provNotFound > 0) $msg .= " {$provNotFound} proveedor(es) no reconocido(s).";

        $this->session->set_userdata('import_success', $msg);
        redirect('sisvent/admin/import');
    }
}
