<?php
/**
 * db drive factory
 * Class DbFactory
 * @author Arnia Software
 */
class SourceFactory{
    /**
     * get a db instance
     * @param string $type
     * @return mixed
     * @throws Exception
     */
    public static function getDbTypeInstance($type){
        $_type = Object::camelize(strtolower($type));
        try{
            require_once 'source/'.$_type.'.php';
            return new $_type();
        }
        catch (Exception $e){
            throw new Exception("Source type ".$type. "is not supported");
        }
    }
}