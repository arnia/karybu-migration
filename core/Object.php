<?php
/**
 * wrapper for stdClass
 * Class Object
 * @author Arnia Software
 */
class Object extends stdClass{
    /**
     * overload the __get() method
     * @param $name
     * @return mixed
     */
    public function __get($name){
        if (isset($this->$name)) {
            return $this->$name;
        }
        return null;
    }

    /**
     * wrapper for db_type element
     * @return mixed
     */
    public function getDbType(){
        return $this->db_type;
    }

    /**
     * turn some_value_here into SomeValueHere
     * @param $string
     * @return mixed
     */
    public static function camelize($string){
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

    /**
     * convert stdClass to Object
     * @param $obj
     * @return Object
     */
    public static function convert($obj){
        if ($obj instanceof stdClass){
            $result = new Object();
            foreach ($obj as $k=>$v){
                $result->$k = $v;
            }
            return $result;
        }
        return $obj;
    }
}