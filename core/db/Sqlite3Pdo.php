<?php
require_once 'AbstractDb.php';
/**
 * Sqllite3pdo db driver
 * Class Sqlite3Pdo
 */
class Sqlite3Pdo extends AbstractDb {
    /**
     * connection handler
     * @var mixed
     */
    protected $_handler = null;

    /**
     * connect to database
     * @param Object $data
     * @return $this|mixed
     * @throws Exception
     */
    public function connect(Object $data){
        if(substr($data->db_database,0,1)!='/') {
            $data->db_database = $data->path.'/'.$data->db_database;
        }
        if(!file_exists($data->db_database)) {
            throw new Exception("Sqlite3Pdo database not found");
        }
        $this->_handler = new PDO('sqlite:'.$data->db_database);
        if(!file_exists($data->db_database)) {
            throw new Exception("permission denied to access database");
        }
        return $this;
    }

    /**
     * run query
     * @param $query
     * @return mixed
     */
    public function query($query) {
        $stmt = $this->_handler->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * fetch record from result
     * @param $result
     * @return mixed|Object
     */
    public function fetch($result) {
        $tmp = $result->fetch(2);
        $obj = new stdClass();
        if ($tmp) {
            foreach($tmp as $key => $val) {
                $pos = strpos($key, '.');
                if($pos) {
                    $key = substr($key, $pos+1);
                }
                $obj->{$key} = str_replace("''","'",$val);
            }
        }
        return Object::convert($obj);
    }
}