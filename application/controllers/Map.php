<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'controllers/BaseController.php';

class Map extends BaseController {

	function __construct() {

		parent::__construct();
		$this->load->model('MapModel');
	}

	function getNameAvailability() {

        header("Access-Control-Allow-Origin: *");
        echo json_encode(count($this->MapModel->getByKey('name', $this->input->post('name'))));
        exit;
    }
}
