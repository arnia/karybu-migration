<?php
require_once 'AbstractDb.php';
/**
 * driver for Cubrid database
 * Class Cubrid
 */
class Cubrid extends AbstractDb {
    /**
     * connect to the db
     * @param Object $data
     * @return $this|mixed
     * @throws Exception
     */
    public function connect(Object $data) {
        $this->_connect = cubrid_connect($data->hostname, $data->port, $data->db_database, $data->userid, $data->password);
        if(!$this->_connect) {
            throw new Exception("Error connecting to cubrid DB");
        }
        return $this;
    }
    /**
     * run aquery
     * @param $query
     * @return bool|mixed|resource
     */
    public function query($query){
        return cubrid_execute($this->_connect, $query);
    }
    /**
     * fetch record from result
     * @param $result
     * @return mixed|Object
     */
    public function fetch($result) {
        $obj = cubrid_fetch($result, CUBRID_OBJECT);
        return Object::convert($obj);
    }
    /**
     * format the query limit
     * @param $start
     * @param $limit
     * @return string
     */
    public function getLimit($start, $limit){
        return sprintf(" for ordeby_num() between %d and %d ", $start, $limit);
    }
}