<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BaseController extends CI_Controller {

	function __construct() {

		parent::__construct();
	}

    function send_response($success = true, $extraData = []) {

        $response = [
            'success' => $success
        ];

        foreach($extraData as $key => $value) {
            $response[$key] = $value;
        }

        echo json_encode($response);
        exit;
    }
}
