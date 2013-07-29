<?php
require_once 'AbstractDb.php';
/**
 * mysqli db driver
 * Class Mysqli
 */
class Mysqli extends AbstractDb {
    /**
     * connect to database
     * @param Object $data
     * @return $this|mixed|string
     */
    public function connect(Object $data) {
        $this->_connect = mysqli_connect($data->db_hostname, $data->db_userid, $data->db_password);
        if(!mysqli_connect_errno()) {
            mysqli_select_db($this->_connect, $data->db_database);
        }
        if(mysqli_connect_errno()) {
            return mysqli_connect_error();
        }
        $this->query("set names 'utf8'");
        return $this;
    }
    /**
     * run a query
     * @param $query
     * @return bool|mixed|mysqli_result
     */
    public function query($query) {
        return mysqli_query($this->_connect, $query);
    }
    /**
     * fetch record from result
     * @param $result
     * @return mixed|Object
     */
    public function fetch($result) {
        $obj = mysqli_fetch_object($result);
        return Object::convert($obj);
    }
}