<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'controllers/BaseController.php';

class User extends BaseController {

	function __construct() {

		parent::__construct();
		$this->load->model('UserModel', 'user', true);
	}

	public function index() {

		echo '{}';
		exit;
	}

	public function login() {

		 header("Access-Control-Allow-Origin: *");
		$this->load->library('form_validation');
		$this->form_validation->set_rules('email', 'Email', 'trim|required');
		$this->form_validation->set_rules('password', 'Password', 'trim|required|callback_check_database');

		if ($this->form_validation->run()) {
			$user = $this->session->userdata('logged_in');
			unset($user->password);

			$this->send_response(true, [
				'user' => $this->session->userdata('logged_in')
			]);
		} else {
			$this->send_response(false);
		}
	}

	function logout() {

		$this->session->unset_userdata('logged_in');
		session_destroy();
		$this->send_response();
	}

	function profile() {

		if($this->session->userdata('logged_in')) {
			$data['user'] = $this->session->userdata('logged_in');
			$data['usermodel'] = new $this->user($data['user']->id);
            $this->load->view('profile', $data);
        } else {
            redirect('login', 'refresh');
        }
	}

	function check_database($password) {

   		$username = $this->input->post('email');
   		$result = $this->user->login($username, $password);


   		if($result && is_array($result) && count($result) && is_object($result[0])) {
			/*
     		$sess_array = array();

			foreach($result as $row) {

				unset($row->password);
       			$this->session->set_userdata('logged_in', $row);
     		}
			*/
			$this->session->set_userdata('logged_in', $result[0]);

     		return true;
   		} else {
     		$this->form_validation->set_message('check_database', 'Invalid username or password');
     		return false;
   		}
 	}

	function forgot_pswd() {

		$this->load->view('forgot_pswd');
	}
}
