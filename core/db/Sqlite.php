<?php
require_once 'AbstractDb.php';
/**
 * sqllie db driver
 * Class Sqlite
 */
class Sqlite extends AbstractDb {
    /**
     * connect to database
     * @param Object $data
     * @return $this|mixed
     * @throws Exception
     */
    public function connect(Object $data) {
        if(substr($data->db_database,0,1)!='/') {
            $data->db_database = $data->path.'/'.$data->db_database;
        }
        if(!file_exists($data->db_database)) {
            throw new Exception("Database file not found");
        }
        $error = null;
        $this->_connect = sqlite_open($data->db_database, 0666, $error);
        if($error) {
            throw new Exception($error);
        }
        return $this;
    }

    /**
     * run query
     * @param $query
     * @return mixed|SQLiteResult
     */
    public function query($query) {
        return sqlite_query($query, $this->_connect);
    }

    /**
     * fetch record from result
     * @param $result
     * @return mixed|Object
     */
    public function fetch($result) {
        $tmp = sqlite_fetch_array($result, SQLITE_ASSOC);
        $obj = new stdClass();
        if($tmp) {
            foreach($tmp as $key => $val) {
                $pos = strpos($key, '.');
                if($pos) $key = substr($key, $pos+1);
                $obj->{$key} = $val;
            }
        }
        return Object::convert($obj);
    }
}