<?php
    if ( ! defined('BASEPATH')) exit('No direct script access allowed');

    abstract class Data_Access_Layer extends CI_Model {

        private $_data = [];

        public function __construct($id = null, $recursive = false) {

            parent::__construct();

            if ($id) {
                $result = $this->getOne($id, $recursive);

                if ($result && is_object($result))
                    $this->_data = $result;
                else if ($result && is_array($result) && count($result) && is_object($result[0]))
                    $this->_data = $result[0];
                else
                    throw new Exception("No result for ID $id");
            }
        }

        public function create($data) {

            if ($this->_validate($data)) {
                $this->db->insert(static::TABLE_NAME, $data);
                return $this->getOne($this->db->insert_id());
            } else {
                echo '{"error": "Invalid data"}';
            }
        }

        public function delete($id) {

            $this->db->where(static::PRIMARY_KEY, $id);
            $this->db->delete(static::TABLE_NAME);
        }

        private function _validate($data = null) {

            if (!$data)
                $data = (array)$this->_data;

            $validations = $this->_validations;
            foreach($validations as $field => $type) {
                if (array_key_exists($field, $data)) {
                    $value = $data[$field];

                    switch($type) {
                        case 'int':
                            if (!is_int((int)$value))
                                return false;
                            break;
                        case 'string':
                            if (!is_string($value))
                                return false;
                            break;
                        case 'json':
                            json_decode($value);
                            if (json_last_error() != JSON_ERROR_NONE)
                                return false;
                            break;
                    }
                }
            }

            return true;
        }

        public function get($key) {

            if (is_object($this->_data) && property_exists($this->_data, $key))
                return $this->_data->{$key};
            return false;
        }

        public function set($key, $value) {

            if (property_exists($this->_data, $key)) {
                if ($this->_validate([$key => $value])) {
                    $this->_data->{$key} = $value;
                    $this->_update();
                }
            }
        }

        private function _update() {

            $pk = static::PRIMARY_KEY;
            $this->db->where($pk, $this->_data->{$pk});
            $this->db->update(static::TABLE_NAME, (array)$this->_data);
        }

        public function bulkUpdate($data) {

            if (is_array($data) && count($data)) {
                if ($this->_validate($data)) {
                    foreach($data as $key => $value) {
                        if (property_exists($this->_data, $key)) {
                            $this->_data->{$key} = $value;
                        }
                    }

                    $this->_update();
                }
            }
        }

        private function _parseJsonFields($fields) {

            foreach ($this->_jsonFields as $jField) {
                if (array_key_exists($jField, $fields)) {
                    $fields->{$jField} = json_decode($fields->{$jField});
                }
            }

            return $fields;
        }

        // recursive vs parsed bug
        public function getAll($recursive = false) {

            $this->db->select('*');
            $this->db->from(static::TABLE_NAME);
            $results = $this->db->get()->result();
            $parsedResults = [];
            foreach($results as $result) {
                $parsedResults[] = $this->_parseJsonFields($result);
            }
            $results = $parsedResults;


            if ($recursive) {
                $return = [static::TABLE_NAME => []];
                foreach($results as $result) {
                    $return[static::TABLE_NAME][] = $this->_getRelations([$result]);
                }
            } else { $return = false; }

            return $return ? $return : $results;
        }

        public function getWhere($field, $value, $recursive = false) {

            $this->db->select('*');
            $this->db->from(static::TABLE_NAME);
            $this->db->where($field, $value);
            $results = $this->db->get()->result();

            $parsedResults = [];
            foreach($results as $result) {
                $parsedResults[] = $this->_parseJsonFields($result);
            }

            if ($recursive) {
                $return = [static::TABLE_NAME => []];
                foreach($parsedResults as $result) {
                    $return[static::TABLE_NAME][] = $this->_getRelations([$result]);
                }
            } else { $return = false; }

            return $return ? $return : $parsedResults;
        }

        public function getOne($id, $recursive = false) {

            $this->db->select('*');
            $this->db->where(static::PRIMARY_KEY, $id);
            $this->db->from(static::TABLE_NAME);
            $result = $this->db->get()->result();
            $result = $this->_parseJsonFields($result[0]);

            /*
            if (method_exists($this, '_getOne')) {
                $result = $this->_getOne($result);
            }
            */

            if ($recursive) {
                $result = $this->_getRelations([$result]);
            }

            return $result;
        }

        public function getOneWhere($field, $value, $recursive = false) {

            $this->db->select('*');
            $this->db->where($field, $value);
            $this->db->from(static::TABLE_NAME);
            $this->db->limit(1);

            $result = $this->db->get()->result();
            //var_dump($result);
            if (is_array($result) && count($result))
                $result = $this->_parseJsonFields($result[0]);

            if ($recursive) {
                $result = $this->_getRelations([$result]);
            }

            return $result;
        }

        public function getSome($offset = 0, $limit = 25) {

            $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
            $limit  = $this->input->get('limit')  ? $this->input->get('limit')  : 25;

            $query = $this->db->get(static::TABLE_NAME, $limit, $offset);
            return $query->result();
        }

        public function getSomeWhere($key, $value, $offset = 0, $limit = 25, $recursive = false) {

            $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
            $limit  = $this->input->get('limit')  ? $this->input->get('limit')  : 25;

            $this->db->select('*');
            $this->db->from(static::TABLE_NAME);
            $this->db->where($key, $value);
            $this->db->offset($offset);
            $this->db->limit($limit);
            $query = $this->db->get();
            $result = $query->result();

            if ($recursive) {
                foreach($result as $index => $row) {
                    $row = $this->_getRelations([$row]);
                    $result[$index] = $row;
                }
            }

            return $result;
        }

        /*
        function getWhere($key, $value, $recursive = false) {

            $this->db->select('*');
            $this->db->from(static::TABLE_NAME);
            $this->db->where($key, $value);
            $query = $this->db->get();
            $result = $query->result();

            if ($recursive) {
                foreach($result as $index => $row) {
                    $row = $this->_getRelations([$row]);
                    $result[$index] = $row;
                }
            }

            return $result;
        }
        */

        public function getTotal() {

            return $this->db->count_all(static::TABLE_NAME);
        }

        public function getTotalWhere($key, $value) {

            $this->db->select('*');
            $this->db->from(static::TABLE_NAME);
            $this->db->where($key, $value);
            $q = $this->db->get();

            return $q->num_rows();
        }

        public function getByKey($key, $value, $recursive = false) {

            $this->db->select('*');
            $this->db->where($key, $value);
            $this->db->from(static::TABLE_NAME);
            $result = $this->db->get()->result();
            //
            //$result = $this->_parseJsonFields($result[0]);

            //if ($recursive) {
            //    $result = $this->_getRelations($result);
            //}

            return $result;
        }

        public function relate($relation, $id) {

            $table = $this->_identifyRelation($relation);
            //var_dump($id);
            //die();

            if ($table) {
                $this->db->insert($table['table'], [
                    $table['keys']['self']     => $this->get('id'),
                    $table['keys']['relation'] => $id
                ]);
            }
        }

        private function _identifyRelation($relation) {

            /*
            echo '<pre>';
            var_dump($this->_tableRelations);
            die();
            echo '</pre>';
            */

            foreach($this->_tableRelations as $type => $tableRelations) {
                foreach($tableRelations as $tableRelation) {
                    if($relation == $tableRelation['model']) {
                        switch($type) {
                            case 'oneToOne':
                                break;
                            case 'oneToMany':
                                break;
                            case 'manyToMany':
                                return [
                                    'table' => $tableRelation['through'],
                                    'keys'  => $tableRelation['keys']
                                ];
                                break;
                        }
                    }
                }
            }

            return false;
        }

        private function _getRelations($results) {

            $relations = $this->_tableRelations;

            if ($relations) {
                foreach($results as $index => $result) {
                    if (array_key_exists('oneToOne', $relations)) {
                        /*
                        echo '<pre>';
                        var_dump($relations);
                        echo '</pre>';
                        die();
                        */
                        foreach($relations['oneToOne'] as $relation) {
                            $result->{$relation['property']} = $this->{$relation['model']}->getOne($result->{$relation['key']}, true);
                            $results = $result;
                        }
                    }

                    if (array_key_exists('oneToMany', $relations)) {
                        foreach($relations['oneToMany'] as $relation) {
                            $result->{$relation['property']} = $this->{$relation['model']}->getByKey($relation['keys']['relation'], $result->{$relation['keys']['self']}, true);
                            $results = $result;
                        }
                    }

                    if (array_key_exists('belongsToMany', $relations)) {
                        foreach($relations['belongsToMany'] as $relation) {
                            $request->{$relation['property']} = $this->{$relation['model']}->getWhere($relation['keys']['relation'], $result->{$relation['keys']['pk']});
                            $results = $result;
                        }
                    }

                    if (array_key_exists('manyToMany', $relations)) {

                        foreach($relations['manyToMany'] as $relation) {

                            $this->db->select('*');
                            $this->db->from($relation['through']);
                            $this->db->where($relation['keys']['self'], $result->{$relation['keys']['pk']});
                            $relationResult = $this->db->get();

                            if ($relationResult) {

                                $relationResult = $relationResult->result();

                                $result->{$relation['property']} = [];

                                foreach($relationResult as $rResult) {
                                    $res = $this->{$relation['model']}->getOne($rResult->{$relation['keys']['relation']}, true);
                                    if(is_array($res) && count($res))
                                        $result->{$relation['property']}[] = $res[0];
                                    else
                                        $result->{$relation['property']}[] = $res;
                                }
                                //var_dump($results);
                                //$results[$index] = $result;
                            }
                        }
                    }
                }
            }

            return $results;
        }

        // DEPRECATAED
        /*
        public function createDom($records) {

            return XmlDomConstruct::toXml($records);
        }

        public function viewXml($dom) {

            header('Content-type: text/xml');
            $dom->formatOutput = true;
            echo $dom->saveXML();
        }

        public function viewJson($dom) {

            header('Content-type: text/json');
            $xml = $dom->saveXML();
            $xml = simplexml_load_string($xml);
            echo json_encode($xml);
        }
        */
    }
?>
