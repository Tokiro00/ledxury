<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Backend_model extends CI_Model {

	public function rowCount($table, $getOthers = null)
	{
		if($getOthers != null && !$getOthers)
		{
			$this->db->where("orders.user",$this->session->userdata('user_data')['user_id']);
		}
		$resultados = $this->db->get($table);
		return $resultados->num_rows();
	}

	public function rowUserCount($table, $userId)
	{
		$this->db->where("orders.user",$userId);
		$resultados = $this->db->get($table);
		return $resultados->num_rows();
	}
}