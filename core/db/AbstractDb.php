<?php
/**
 * abstract class for DB handling
 * Class AbstractDb
 * @author Arnia Software
 */
abstract class AbstractDb{
    /**
     * database connection
     * @var moxed
     */
    protected $_connect = null;

    /**
     * method for connecting to the db
     * @param Object $data
     * @return mixed
     */
    abstract public function connect(Object $data);

    /**
     * run a query
     * @param $query
     * @return mixed
     */
    abstract public function query($query);

    /**
     * fetch results
     * @param $result
     * @return mixed
     */
    abstract public function fetch($result);

    /**
     * get the limit format
     * @param $start
     * @param $limit
     * @return string
     */
    public function getLimit($start, $limit) {
        return sprintf(" limit %d, %d ", $start, $limit);
    }
}