<?php
/**
 * db drive factory
 * Class DbFactory
 * @author Arnia Software
 */
class DbFactory{
    /**
     * get a db instance
     * @param string $type
     * @return mixed
     * @throws Exception
     */
    public static function getDbTypeInstance($type){
        $_type = Object::camelize(strtolower($type));
        try{
            require_once 'db/'.$_type.'.php';
            return new $_type();
        }
        catch (Exception $e){
            throw new Exception("No database driver support for type ".$type);
        }
    }
}