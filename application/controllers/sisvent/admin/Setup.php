<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;

class Setup extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1, 10]);
        $this->load->model('stores_model');
        $this->load->model('users_model');
        $this->load->model('products_model');
        $this->load->model('clients_model');
        $this->load->model('providers_model');
    }

    // ========================================================================
    // ASISTENTE DE CONFIGURACION
    // ========================================================================

    public function index()
    {
        redirect(base_url() . 'sisvent/admin/setup/wizard');
    }

    public function wizard()
    {
        $data = array();
        $data['stores'] = $this->stores_model->getStores();
        $data['users']  = $this->users_model->getUsers(false);
        $data['roles']  = $this->users_model->getRoles();
        $this->load->view('sisvent/admin/setup/wizard', $data);
    }

    // ========================================================================
    // AJAX: Guardar datos de la empresa (Step 1)
    // ========================================================================

    public function save_company()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(array('success' => false, 'message' => 'Metodo no permitido'));
            return;
        }

        $name             = $this->input->post('name', true);
        $nit              = $this->input->post('nit', true);
        $address          = $this->input->post('address', true);
        $phone            = $this->input->post('phone', true);
        $email            = $this->input->post('email', true);
        $invoice_account  = $this->input->post('invoice_account', true);
        $invoice_support  = $this->input->post('invoice_support', true);

        if (empty($name)) {
            echo json_encode(array('success' => false, 'message' => 'El nombre de la empresa es obligatorio'));
            return;
        }

        $updateData = array(
            'name'            => $name,
            'nit'             => $nit,
            'address'         => $address,
            'phone'           => $phone,
            'email'           => $email,
            'invoice_account' => $invoice_account,
            'invoice_support' => $invoice_support
        );

        // Update the primary store (id=1)
        $result = $this->stores_model->update(1, $updateData);

        if ($result) {
            echo json_encode(array('success' => true, 'message' => 'Datos de la empresa guardados correctamente'));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Error al guardar los datos'));
        }
    }

    // ========================================================================
    // AJAX: Crear bodega/almacen (Step 2)
    // ========================================================================

    public function save_store()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(array('success' => false, 'message' => 'Metodo no permitido'));
            return;
        }

        $name = $this->input->post('name', true);

        if (empty($name)) {
            echo json_encode(array('success' => false, 'message' => 'El nombre de la bodega es obligatorio'));
            return;
        }

        $data = array(
            'name' => $name
        );

        $result = $this->stores_model->save($data);

        if ($result) {
            $newId = $this->db->insert_id();
            echo json_encode(array(
                'success' => true,
                'message' => 'Bodega creada correctamente',
                'store'   => array('idStore' => $newId, 'name' => $name)
            ));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Error al crear la bodega'));
        }
    }

    // ========================================================================
    // AJAX: Eliminar bodega (Step 2)
    // ========================================================================

    public function delete_store()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(array('success' => false, 'message' => 'Metodo no permitido'));
            return;
        }

        $id = $this->input->post('id', true);

        if (empty($id) || $id == 1) {
            echo json_encode(array('success' => false, 'message' => 'No se puede eliminar la bodega principal'));
            return;
        }

        $result = $this->stores_model->remove($id);

        if ($result) {
            echo json_encode(array('success' => true, 'message' => 'Bodega eliminada correctamente'));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Error al eliminar la bodega'));
        }
    }

    // ========================================================================
    // AJAX: Crear usuario (Step 3)
    // ========================================================================

    public function save_user()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(array('success' => false, 'message' => 'Metodo no permitido'));
            return;
        }

        $idUser   = $this->input->post('idUser', true);
        $name     = $this->input->post('name', true);
        $password = $this->input->post('password');
        $role     = $this->input->post('role', true);
        $store    = $this->input->post('store', true);
        $email    = $this->input->post('email', true);

        if (empty($idUser) || empty($name) || empty($password) || empty($role)) {
            echo json_encode(array('success' => false, 'message' => 'Todos los campos obligatorios deben ser completados'));
            return;
        }

        // Check if user already exists
        $existing = $this->users_model->getAnyUser($idUser);
        if ($existing) {
            echo json_encode(array('success' => false, 'message' => 'Ya existe un usuario con ese ID'));
            return;
        }

        $hashedPassword = $this->outh_model->HashPassword($password);

        $data = array(
            'idUser'   => $idUser,
            'name'     => $name,
            'password' => $hashedPassword,
            'role'     => $role,
            'store'    => $store ? $store : 1,
            'email'    => $email
        );

        $result = $this->users_model->save($data);

        if ($result) {
            // Get the role name for the response
            $roles = $this->users_model->getRoles();
            $roleName = '';
            foreach ($roles as $r) {
                if ($r->idRoles == $role) {
                    $roleName = $r->description;
                    break;
                }
            }

            echo json_encode(array(
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'user'    => array(
                    'idUser'    => $idUser,
                    'name'      => $name,
                    'role'      => $role,
                    'role_name' => $roleName,
                    'store'     => $store,
                    'email'     => $email
                )
            ));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Error al crear el usuario'));
        }
    }

    // ========================================================================
    // AJAX: Eliminar usuario (Step 3)
    // ========================================================================

    public function delete_user()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(array('success' => false, 'message' => 'Metodo no permitido'));
            return;
        }

        $id = $this->input->post('id', true);

        if (empty($id)) {
            echo json_encode(array('success' => false, 'message' => 'ID de usuario requerido'));
            return;
        }

        // Don't allow deleting yourself
        $currentUser = $this->session->userdata('user_data')['uname'];
        if ($id === $currentUser) {
            echo json_encode(array('success' => false, 'message' => 'No puede eliminarse a si mismo'));
            return;
        }

        $result = $this->users_model->remove($id);

        if ($result) {
            echo json_encode(array('success' => true, 'message' => 'Usuario eliminado correctamente'));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Error al eliminar el usuario'));
        }
    }

    // ========================================================================
    // POST: Importar archivo SQL
    // ========================================================================

    public function import_sql()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(array('success' => false, 'message' => 'Metodo no permitido'));
            return;
        }

        $config['upload_path']   = FCPATH . 'uploads/imports/';
        $config['allowed_types'] = 'sql';
        $config['max_size']      = 51200; // 50MB
        $config['file_name']     = 'import_sql_' . date('YmdHis');

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0755, true);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('sql_file')) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Error al subir el archivo: ' . $this->upload->display_errors('', '')
            ));
            return;
        }

        $uploadData = $this->upload->data();
        $filePath   = $uploadData['full_path'];

        try {
            $sql = file_get_contents($filePath);
            if ($sql === false) {
                throw new Exception('No se pudo leer el archivo SQL');
            }

            // Remove comments
            $sql = preg_replace('/--.*$/m', '', $sql);
            $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

            // Split by semicolons (not inside quotes)
            $statements = $this->_splitSqlStatements($sql);

            $executed = 0;
            $errors   = 0;
            $errorMessages = array();

            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement)) continue;

                try {
                    $this->db->query($statement);
                    $executed++;
                } catch (Exception $e) {
                    $errors++;
                    if ($errors <= 5) {
                        $errorMessages[] = substr($statement, 0, 100) . '... : ' . $e->getMessage();
                    }
                }
            }

            // Clean up file
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            echo json_encode(array(
                'success'  => true,
                'message'  => "Importacion SQL completada: {$executed} sentencias ejecutadas, {$errors} errores.",
                'executed' => $executed,
                'errors'   => $errors,
                'errorMessages' => $errorMessages
            ));

        } catch (Exception $e) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            echo json_encode(array(
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage()
            ));
        }
    }

    // ========================================================================
    // POST: Importar archivo Excel
    // ========================================================================

    public function import_excel()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(array('success' => false, 'message' => 'Metodo no permitido'));
            return;
        }

        $type = $this->input->post('type', true);
        if (!in_array($type, array('products', 'clients', 'providers'))) {
            echo json_encode(array('success' => false, 'message' => 'Tipo de importacion no valido'));
            return;
        }

        $config['upload_path']   = FCPATH . 'uploads/imports/';
        $config['allowed_types'] = 'xlsx|xls|csv';
        $config['max_size']      = 10240; // 10MB
        $config['file_name']     = $type . '_' . date('YmdHis');

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0755, true);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('excel_file')) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Error al subir el archivo: ' . $this->upload->display_errors('', '')
            ));
            return;
        }

        $uploadData = $this->upload->data();
        $filePath   = $uploadData['full_path'];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet   = $spreadsheet->getActiveSheet();
            $rows        = $worksheet->toArray();

            $header    = array_shift($rows);
            $processed = count($rows);
            $success   = 0;
            $errors    = 0;

            foreach ($rows as $row) {
                try {
                    $data = array();
                    foreach ($header as $i => $col) {
                        if ($col !== null && isset($row[$i])) {
                            $key = strtolower(trim($col));
                            $key = str_replace(' ', '_', $key);
                            $data[$key] = trim($row[$i]);
                        }
                    }
                    if (empty($data)) continue;

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
                    $success++;
                } catch (Exception $e) {
                    $errors++;
                }
            }

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            echo json_encode(array(
                'success'   => true,
                'message'   => "Importacion completada: {$success} de {$processed} registros importados, {$errors} errores.",
                'processed' => $processed,
                'imported'  => $success,
                'errors'    => $errors
            ));

        } catch (Exception $e) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            echo json_encode(array(
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage()
            ));
        }
    }

    // ========================================================================
    // AJAX: Estado actual de la configuracion
    // ========================================================================

    public function check_status()
    {
        $stores    = $this->stores_model->getStores();
        $users     = $this->users_model->getUsers(false);
        $products  = $this->products_model->getTotal();
        $clients   = $this->clients_model->clientCount(true);
        $providers = count($this->providers_model->getProviders());

        // Accounting settings count
        $accountingCount = 0;
        $accountingTotal = 0;
        if ($this->db->table_exists('accounting_settings')) {
            $this->load->model('accountingsettings_model');
            $settings = $this->accountingsettings_model->getSettings();
            $accountingTotal = count($settings);
            foreach ($settings as $s) {
                if (!empty($s->subaccount_id)) {
                    $accountingCount++;
                }
            }
        }

        // Company data check (store id=1)
        $mainStore = $this->stores_model->getStore(1);
        $companyConfigured = false;
        if ($mainStore && !empty($mainStore->name) && $mainStore->name !== 'Principal') {
            $companyConfigured = true;
        }

        echo json_encode(array(
            'success'  => true,
            'status'   => array(
                'company_configured' => $companyConfigured,
                'company_name'       => $mainStore ? $mainStore->name : '',
                'stores'             => count($stores),
                'users'              => count($users),
                'products'           => $products,
                'clients'            => $clients,
                'providers'          => $providers,
                'accounting_configured' => $accountingCount,
                'accounting_total'      => $accountingTotal
            )
        ));
    }

    // ========================================================================
    // AJAX: Obtener roles disponibles
    // ========================================================================

    public function get_roles()
    {
        $roles = $this->users_model->getRoles();
        echo json_encode(array('success' => true, 'roles' => $roles));
    }

    // ========================================================================
    // AJAX: Obtener bodegas actuales
    // ========================================================================

    public function get_stores()
    {
        $stores = $this->stores_model->getStores();
        echo json_encode(array('success' => true, 'stores' => $stores));
    }

    // ========================================================================
    // AJAX: Obtener usuarios actuales
    // ========================================================================

    public function get_users()
    {
        $users = $this->users_model->getUsers(false);
        echo json_encode(array('success' => true, 'users' => $users));
    }

    // ========================================================================
    // METODOS PRIVADOS
    // ========================================================================

    /**
     * Split SQL content into individual statements, respecting quoted strings
     */
    private function _splitSqlStatements($sql)
    {
        $statements = array();
        $current    = '';
        $inString   = false;
        $stringChar = '';
        $len        = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];

            if ($inString) {
                $current .= $char;
                if ($char === $stringChar && ($i === 0 || $sql[$i - 1] !== '\\')) {
                    $inString = false;
                }
            } else {
                if ($char === '\'' || $char === '"') {
                    $inString   = true;
                    $stringChar = $char;
                    $current   .= $char;
                } elseif ($char === ';') {
                    $trimmed = trim($current);
                    if (!empty($trimmed)) {
                        $statements[] = $trimmed;
                    }
                    $current = '';
                } else {
                    $current .= $char;
                }
            }
        }

        $trimmed = trim($current);
        if (!empty($trimmed)) {
            $statements[] = $trimmed;
        }

        return $statements;
    }
}
