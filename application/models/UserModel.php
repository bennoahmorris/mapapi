<?php
require_once APPPATH . 'models/data_access_layer.php';

Class UserModel extends Data_Access_Layer {

    const TABLE_NAME = 'users';
    const PRIMARY_KEY = 'id';

    protected $_jsonFields     = [];
    protected $_validations    = [];
    protected $_tableRelations = [
        'manyToMany' => [
            [
                'model' => 'RoleModel',
                'property' => 'roles',
                'through' => 'user_roles',
                'keys' => [
                    'pk' => 'id',
                    'self' => 'user_id',
                    'relation' => 'role_id'
                ]
            ]
        ]
    ];

    public function __construct($id = false) {

        parent::__construct($id, true);
        $this->load->model('RoleModel');
    }

    function login($username, $password) {

        $this->db->select('*');
        $this->db->from('users');
        $this->db->where('email', $username);
        $this->db->where('password', MD5($password));
        $this->db->limit(1);

        $query = $this->db->get();

        if($query->num_rows() == 1)
            return $this->getOne($query->result()[0]->id, true);

        return false;
    }

    function hasRole($name) {

        $roles = $this->get('roles');

        foreach($roles as $role) {
            if ($role->name == $name) {
                return true;
            }
        }

        return false;
    }
}
?>
