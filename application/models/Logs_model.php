<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Logs_model extends CI_Model {

/*
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` int(11) DEFAULT NULL,
  `user` varchar(100) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `ip_address` varchar(50) COLLATE 'utf8_general_ci' NULL,
  `os` varchar(100) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `browser_version` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

*/

	public function logMessage($level, $event)
	{
		date_default_timezone_set("America/Bogota");
		switch (strtolower($level)) {
		    
		    case "emergency":
		        $data['level'] = 1;
		        break;
		    case "alert":
		        $data['level'] = 2;
		        break;
		    case "critical":
		        $data['level'] = 3;
		        break;
		    case "error":
		        $data['level'] = 4;
		        break;
		    case "warning":
		        $data['level'] = 5;
		        break;
		    case "notice":
		        $data['level'] = 6;
		        break;
		    case "info":
		        $data['level'] = 7;
		        break;
		    case "debug":
		        $data['level'] = 8;
		        break;
		    default:
		        $data['level'] = 7;
		        break;
		}
		$data['event'] = $event;
		$data['browser'] = $this->agent->browser();
		$data['user'] = $this->session->userdata('user_data')['uname'];
		$data['browser_version'] = $this->agent->version();
		$data['os'] = $this->agent->platform();
		$data['ip_address'] = $this->input->ip_address();
		$data['date'] = date("Y-m-d H:i:s");
		return $this->db->insert("logs",$data);
	}

	public function logSesionFail($level, $event)
	{
		date_default_timezone_set("America/Bogota");
		switch (strtolower($level)) {
		    
		    case "emergency":
		        $data['level'] = 1;
		        break;
		    case "alert":
		        $data['level'] = 2;
		        break;
		    case "critical":
		        $data['level'] = 3;
		        break;
		    case "error":
		        $data['level'] = 4;
		        break;
		    case "warning":
		        $data['level'] = 5;
		        break;
		    case "notice":
		        $data['level'] = 6;
		        break;
		    case "info":
		        $data['level'] = 7;
		        break;
		    case "debug":
		        $data['level'] = 8;
		        break;
		    default:
		        $data['level'] = 7;
		        break;
		}
		$data['event'] = $event;
		$data['browser'] = $this->agent->browser();
		$data['browser_version'] = $this->agent->version();
		$data['user'] = $this->session->userdata('user_data')['uname'];
		$data['os'] = $this->agent->platform();
		$data['ip_address'] = $this->input->ip_address();
		$data['date'] = date("Y-m-d H:i:s");
		return $this->db->insert("login_fails",$data);
	}

	public function getLogMessages($sid)
	{
		$this->db->select('logs.*,
                           logs_level.name as level_name');
        $this->db->from('logs')->join('logs_level', 'logs.level = logs_level.id');
        $this->db->where('user',$sid);
		$resultados = $this->db->get();
		return $resultados->result();
	}

	public function getFailSesion($sid)
	{
		$this->db->select('login_fails.*,
                           logs_level.name as level_name');
        $this->db->from('login_fails')->join('logs_level', 'login_fails.level = logs_level.id');
        $this->db->where('user',$sid);
		$resultados = $this->db->get();
		return $resultados->result();
	}
	
	/*public function getStats($campaign_id){
		$this->db->select('*,
                           campaigns.name as campaign_name');
        $this->db->from('stats')->join('campaigns', 'stats.campaign = campaigns.campaign_id');
		$this->db->where("campaign",$campaign_id);
		$resultado = $this->db->get();
		return $resultado->result();

	}

	public function getUsersOfCampaign($cid){
		$this->db->select('campaign,count(DISTINCT(user)) as total_users,
                           campaigns.name as campaign_name');
        $this->db->from('stats')->join('campaigns', 'stats.campaign = campaigns.campaign_id');
		$this->db->where("stats.campaign",$cid);
		$resultado = $this->db->get();
		return $resultado->row();

	}*/
}