<?php
class Message extends CI_controller{

	public function __construct()
    {
        parent::__construct();
        $this->load->model("users_model");
        $this->load->model("message_model");
    }

	public function index(){
		$this->message_model->logoutUser('active','');
		$data  = array(
			'user' => $this->users_model->getAnyUser($this->session->userdata('user_data')['uname']),
			'users' => $this->users_model->getUsersButMe($this->session->userdata('user_data')['uname']), 
		);
		$this->load->view('sisvent/message',$data);
		
	}

	public function allUser(){
		$data['data'] = $this->users_model->getUsersButMe($this->session->userdata('user_data')['uname']);
		$data['last_msg'] = array();
		//$this->load->helper('url');
		if(!is_array($data['data'])){
			echo "<p class='text-center'>Usuario no disponible.</p>";
		}else{
			$count = count($data['data']);
			for($i = 0; $i < $count; $i++){
				$unique_id = $data['data'][$i]->idUser;
				$msg = $this->message_model->getLastMessage($unique_id);
				$data['data'][$i]->nrmsgs = $this->message_model->getUnreadMessagesCount($this->session->userdata('user_data')['uname'],$unique_id);
				for($j = 0; $j < count($msg); $j++){

					$time = explode(" ",$msg[0]['time']); //00:00:00.0000
					$time = explode(".", $time[1]);//00:00:00
					$time = explode(":",$time[0]);//00 00 00
					if((int)$time[0] == 12){
						$time = $time[0].":".$time[1]." PM";
					}
					elseif((int)$time[0] > 12){
						$time = ($time[0] - 12).":".$time[1]." PM";
					}else{
						$time = $time[0].":".$time[1]." AM";
					}

					array_push($data['last_msg'],array(
						'message' => $msg[0]['message'],
						'sender_id' => $msg[0]['sender_message_id'],
						'receiver_id' => $msg[0]['receiver_message_id'],
						'time' => $time //00:00
					));
				}
			}
			$this->load->view('sisvent/message/sampleDataShow',$data);
		}
		
	}

	public function getIndividual(){
		$returnVal = $this->message_model->getIndividual($_POST['data']);
		print_r(json_encode($returnVal,true));
	}

	public function setNoMessage(){
		$data['image'] = $_POST['image'];
		$data['name'] = $_POST['name'];
		$this->load->view('sisvent/message/notmessageyet',$data);
	}
	public function sendMessage(){
		if(isset($_POST['data']) && isset($this->session->userdata('user_data')['uname'])){
		$jsonDecode = json_decode($_POST['data'],true);
		$uniq = $this->session->userdata('user_data')['uname'];
		$arr = array(
			'time' => $jsonDecode['datetime'],
			'sender_message_id' => $uniq,
			'receiver_message_id' => $jsonDecode['uniq'],
			'message' => $jsonDecode['message'],
		);
			$this->message_model->sentMessage($arr);
		}
	}
	public function getMessage(){
		if(isset($_POST['data']) && isset($this->session->userdata('user_data')['uname'])){
			$data['data'] = $this->message_model->getmessage($_POST['data']);
			$data['image'] = $_POST['image'];
			$this->load->view('sisvent/message/sampleMessageShow',$data);
			if($_POST['clear']){
				$this->message_model->clearMessages($this->session->userdata('user_data')['uname'], $_POST['data']);
			}
		}
	}
	public function logout(){
		$date = $_POST['date'];
		$this->message_model->logoutUser('deactive',$date);
		echo base_url("sisvent/dashboard");
	}

	public function getNumUnreadMessages(){
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Don't allow anything but POST
		
		$data['data'] = $this->message_model->getUnreadMessagesCount($this->session->userdata('user_data')['uname']);
		echo json_encode($data);
	}
}