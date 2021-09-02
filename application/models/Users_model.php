<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users_model extends CI_Model {

	public function getUsers($excludeVendors = true){
		/*$this->db->select('users.user_name,
                           users.name,
                           users.email,
						   users.role,
                           roles.name as role_name');*/
        $this->db->select('users.*,roles.description as role_name');
        $this->db->from('users')->join('roles', 'roles.idRoles = users.role');
        if($excludeVendors)
        {
			$this->db->where("users.role !=",3);
		}
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
}