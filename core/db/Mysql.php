<?php
require_once 'AbstractDb.php';
/**
 * Mysql db driver
 * Class Mysql
 */
class Mysql extends AbstractDb{
    /**
     * connect to database
     * @param Object $data
     * @return $this|mixed|string
     */
    public function connect(Object $data){
        $this->_connect = mysql_connect($data->db_hostname, $data->db_userid, $data->db_password);
        if(!mysql_error()) {
            mysql_select_db($data->db_database, $this->_connect);
        }
        if(mysql_error()) {
            return mysql_error();
        }
        $this->query("set names 'utf8'");
        return $this;
    }
    /**
     * run a query
     * @param $query
     * @return mixed|resource
     */
    public function query($query) {
        return mysql_query($query);
    }
    /**
     * fetch record from result
     * @param $result
     * @return mixed|Object
     */
    public function fetch($result) {
        $obj = mysql_fetch_object($result);
        return Object::convert($obj);
    }
}