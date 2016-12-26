<?php
require_once APPPATH . 'models/data_access_layer.php';

Class RoleModel extends Data_Access_Layer {

    const TABLE_NAME = 'roles';
    const PRIMARY_KEY = 'id';

    protected $_jsonFields     = [];
    protected $_validations    = [];
    protected $_tableRelations = false;

    public function __construct() {

        parent::__construct();
    }


}
?>
