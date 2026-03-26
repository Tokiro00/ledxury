<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('users_model');
    }
    public function login($id, $password)
    {
        $query = $this->db->get_where('users', array('idUser' => $id));
        if($query->num_rows() == 1)
        {
            $row=$query->row();
            if(password_verify($password, $row->password))
            {
                $data=array('user_data'=>array(
                    'name'=>$row->name,
                    'uname'=>$row->idUser,
                    'store'=>$row->store,
                    'role'=>$row->role)
                );
                $this->session->set_userdata($data);
                $this->session->set_userdata('image', $row->picture_url );

                // Cargar permisos del rol en sesion
                if ($this->db->table_exists('role_permissions')) {
                    $this->load->model('roles_model');
                    $permissions = $this->roles_model->getRolePermissions($row->role);
                    $this->session->set_userdata('permissions', $permissions);
                } else {
                    $this->session->set_userdata('permissions', array());
                }

                return true;
            }
        }
        $this->session->unset_userdata('user_data');
        //$this->logs_model->logMessage("warning","Fallo inicio de sesión usuario ".$id);
        //$this->logs_model->logSesionFail("warning","Fallo inicio de sesión usuario ".$id);
        return false;
    }

    
}