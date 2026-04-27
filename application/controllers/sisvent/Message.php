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
			'message' => isset($jsonDecode['message']) ? $jsonDecode['message'] : '',
			'media_url' => isset($jsonDecode['media_url']) ? $jsonDecode['media_url'] : null,
			'media_type' => isset($jsonDecode['media_type']) ? $jsonDecode['media_type'] : null,
			'media_name' => isset($jsonDecode['media_name']) ? $jsonDecode['media_name'] : null,
			'media_size' => isset($jsonDecode['media_size']) ? (int)$jsonDecode['media_size'] : null,
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

	/**
	 * Borrar un mensaje del chat (mobile, user_messages).
	 * Permitido al remitente o admin (role 1, 2, 10). Borra archivo media también.
	 * POST /sisvent/message/deleteMessage  body: id
	 */
	public function deleteMessage(){
		header('Content-Type: application/json');
		$myId = $this->session->userdata('user_data')['uname'] ?? null;
		$role = (int)($this->session->userdata('user_data')['role'] ?? 0);
		if (!$myId) { echo json_encode(['ok'=>false,'error'=>'No autenticado']); return; }
		$msgId = (int)$this->input->post('id');
		if ($msgId <= 0) { echo json_encode(['ok'=>false,'error'=>'ID inválido']); return; }

		$row = $this->db->where('id', $msgId)->get('user_messages')->row();
		if (!$row) { echo json_encode(['ok'=>false,'error'=>'Mensaje no encontrado']); return; }

		$isAdmin = in_array($role, [1, 2, 10], true);
		if ($row->sender_message_id !== $myId && !$isAdmin) {
			echo json_encode(['ok'=>false,'error'=>'No autorizado']); return;
		}

		if (!empty($row->media_url)) {
			$path = FCPATH . ltrim($row->media_url, '/');
			if (is_file($path)) @unlink($path);
		}
		$this->db->where('id', $msgId)->delete('user_messages');
		echo json_encode(['ok'=>true]);
	}

	/**
	 * Subir media de chat (imagen / audio / video / archivo).
	 * Acepta multipart/form-data con campo "file".
	 * Retorna JSON: { ok, url, type, name, size, error }
	 */
	public function uploadMedia(){
		header('Content-Type: application/json');
		$me = $this->session->userdata('user_data')['uname'] ?? null;
		if (!$me) { echo json_encode(['ok'=>false,'error'=>'No autenticado']); return; }
		if (empty($_FILES['file']['name'])) { echo json_encode(['ok'=>false,'error'=>'Sin archivo']); return; }

		$f = $_FILES['file'];
		if ($f['error'] !== UPLOAD_ERR_OK) { echo json_encode(['ok'=>false,'error'=>'Error de subida #'.$f['error']]); return; }
		if ($f['size'] > 15 * 1024 * 1024) { echo json_encode(['ok'=>false,'error'=>'Archivo excede 15MB']); return; }

		$mime = function_exists('mime_content_type') ? mime_content_type($f['tmp_name']) : ($f['type'] ?? '');
		$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));

		$type = 'file';
		if (strpos($mime,'image/') === 0 || in_array($ext,['jpg','jpeg','png','gif','webp'])) $type = 'image';
		elseif (strpos($mime,'audio/') === 0 || in_array($ext,['mp3','ogg','oga','wav','webm','m4a','aac'])) $type = 'audio';
		elseif (strpos($mime,'video/') === 0 || in_array($ext,['mp4','mov','webm'])) $type = 'video';

		// Whitelist final
		$allowed = ['jpg','jpeg','png','gif','webp','mp3','ogg','oga','wav','webm','m4a','aac','mp4','mov','pdf'];
		if (!in_array($ext, $allowed)) { echo json_encode(['ok'=>false,'error'=>'Tipo no permitido (.'.$ext.')']); return; }

		$dir = FCPATH . 'public/uploads/chat/' . preg_replace('/[^a-zA-Z0-9_-]/','', $me);
		if (!is_dir($dir)) @mkdir($dir, 0775, true);
		if (!is_writable($dir)) { echo json_encode(['ok'=>false,'error'=>'Carpeta sin permisos de escritura']); return; }

		$basename = $type . '_' . date('YmdHis') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
		$dest = $dir . '/' . $basename;
		if (!move_uploaded_file($f['tmp_name'], $dest)) { echo json_encode(['ok'=>false,'error'=>'No se pudo guardar el archivo']); return; }

		$relUrl = 'public/uploads/chat/' . preg_replace('/[^a-zA-Z0-9_-]/','', $me) . '/' . $basename;
		echo json_encode([
			'ok' => true,
			'url' => $relUrl,
			'type' => $type,
			'name' => $f['name'],
			'size' => (int)$f['size'],
		]);
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