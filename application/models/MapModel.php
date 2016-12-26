<?php
require_once APPPATH . 'models/data_access_layer.php';

Class MapModel extends Data_Access_Layer {

    const TABLE_NAME = 'maps';
    const PRIMARY_KEY = 'id';

    protected $_jsonFields     = [];
    protected $_validations    = [];
    protected $_tableRelations = [
        'oneToOne' => [
            [
                'model' => 'UserModel',
                'property' => 'user',
                'key' => 'user_id'
            ]
        ]
    ];

    public function __construct($id = false) {

        parent::__construct($id, true);
    }
}
?>
