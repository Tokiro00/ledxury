<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users_model extends CI_Model {

	public function getUsers($excludeVendors = true){
        $this->db->select("users.*, roles.description as role_name, roles.puc_code as role_puc_code, aux.id as aux_account_id, aux.accountName as aux_account_name, aux.accountID as aux_puc_id");
        $this->db->from('users');
        $this->db->join('roles', 'roles.idRoles = users.role');
        $this->db->join('auxiliary_subaccounts aux', "aux.accountAccount = users.idUser AND aux.accountType IN ('employee','partner') AND aux.deleted = 0", 'left');
        if($excludeVendors)
        {
			$this->db->where("users.role !=",3);
		}
        $this->db->where("users.archived",0);
		$this->db->where("users.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getUsersButMe($id){
		/*$this->db->select('users.user_name,
                           users.name,
                           users.email,
						   users.role,
                           roles.name as role_name');*/
        $this->db->select('users.*,roles.description as role_name');
        $this->db->from('users')->join('roles', 'roles.idRoles = users.role');
		$this->db->where("users.idUser != ",$id);
        $this->db->where("users.archived",0);
		$this->db->where("users.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getUser($id){
		/*$this->db->select('users.user_name,
                           users.name,
                           users.email,
						   users.role,
                           roles.name as role_name');*/
        $this->db->select('users.*,roles.description as role_name');
        $this->db->from('users')->join('roles', 'roles.idRoles = users.role');
		$this->db->where("users.role !=",3);
		$this->db->where("users.idUser",$id);
        $this->db->where("users.archived",0);
		$this->db->where("users.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getAnyUser($id){
		/*$this->db->select('users.user_name,
                           users.name,
                           users.email,
						   users.role,
                           roles.name as role_name');*/
        $this->db->select('users.*,roles.description as role_name');
        $this->db->from('users')->join('roles', 'roles.idRoles = users.role');
		//$this->db->where("users.role !=",3);
		$this->db->where("users.idUser",$id);
        //$this->db->where("users.archived",0);
		$this->db->where("users.deleted",0);
		$resultados = $this->db->get();
		return $resultados->row();
	}

	public function getUsersByRole($roleid){
		/*$this->db->select('users.user_name,
                           users.name,
                           users.email,
						   users.role,
                           roles.name as role_name');*/
        $this->db->select('users.*,roles.description as role_name');
        $this->db->from('users')->join('roles', 'roles.idRoles = users.role');
		$this->db->where("users.role",$roleid);
        $this->db->where("users.archived",0);
		$this->db->where("users.deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getRoles(){
        $this->db->from('roles');
		$this->db->where("deleted",0);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert("users",$data);
	}

	public function update($id,$data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where("idUser",$id);
		return $this->db->update("users",$data);
	}
	public function remove($user_id){
		date_default_timezone_set("America/Bogota");
		$data  = array(
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted' => 1
				);
		return $this->update($user_id,$data);
		//$this->db->where("idUser",$user_id);
		//return $this->db->delete("users");
	}

	public function getRolePucCode($roleId){
		$this->db->select('puc_code');
		$this->db->from('roles');
		$this->db->where('idRoles', $roleId);
		$this->db->where('deleted', 0);
		$row = $this->db->get()->row();
		return $row ? $row->puc_code : null;
	}

	public function getUserAuxAccount($userId){
		$this->db->select('auxiliary_subaccounts.*');
		$this->db->from('auxiliary_subaccounts');
		$this->db->where('accountAccount', $userId);
		$this->db->where_in('accountType', array('employee', 'partner'));
		$this->db->where('deleted', 0);
		return $this->db->get()->row();
	}
}