<?php
require_once 'Object.php';
/**
 * core class used for migration
 * Class Migration
 * @author Arnia Software
 */
class Migration {
    /**
     * export file name
     * @var mixed
     */
    protected $_exportFileName  = null;
    /**
     * driver for db connections
     * @var mixed
     */
    protected $_dbDriver        = null;
    /**
     * source to be migrated
     * @var mixed
     */
    protected $_sourceInstance  = null;
    /**
     * database connection data
     * @var mixed
     */
    protected $_dbInfo          = null;
    /**
     * path to source root folder
     * @var null
     */
    protected $_sourcePath      = null;
    /**
     * file for Migration info
     * @var string
     */
    protected $_infoFile        = 'info.xml';
    /**
     * available source types
     * @var mixed
     */
    protected $_sourceTypes     = null;
    /**
     * module info
     * @var mixed
     */
    protected $_info            = null;

    /**
     * get database information
     * @return mixed|null
     * @throws Exception
     */
    public function getDbInfo() {
        if (is_null($this->_dbInfo)){
            if (is_null($this->getSourcePath())){
                throw new Exception("Set the path for import");
            }
            $this->_dbInfo = $this->getSourceInstance()->getDbConfig($this->getSourcePath());
        }
        return $this->_dbInfo;
    }

    /**
     * get the database driver
     * @return AbstractDb|mixed|null
     * @throws Exception
     */
    public function getDbDriver() {
        if (is_null($this->_dbDriver)){
            require_once 'DbFactory.php';
            $driver = DbFactory::getDbTypeInstance($this->getDbInfo()->getDbType());
            if ($driver instanceof AbstractDb) {
                $this->_dbDriver = $driver;
                if (!$this->_dbDriver->connect($this->getDbInfo())){
                    //TODO: make message more clear
                    throw new Exception("Could not connect to database");

                }
            }
            else {
                throw new Exception("The database driver must me an instance of 'AbstractDb'");
            }
        }
        return $this->_dbDriver;
    }

    /**
     * set source type for import (joomla, xe...)
     * @param $type
     * @return $this
     * @throws Exception
     */
    public function setSource($type) {
        require_once 'SourceFactory.php';
        $source = SourceFactory::getDbTypeInstance($type);
        if ($source instanceof AbstractSource) {
            $source->setMigration($this);
            $this->_sourceInstance = $source;
        }
        else {
            throw new Exception("The source instance must me an instance of 'AbstractSource'");
        }
        return $this;
    }

    /**
     * get the source model instance
     * @return mixed|null
     */
    public function getSourceInstance(){
        return $this->_sourceInstance;
    }

    /**
     * set path of the source to migrate
     * @param $path
     * @return $this
     */
    public function setSourcePath($path){
        $this->_sourcePath = $path;
        return $this;
    }

    /**
     * getter for source path to be migrated
     * @return mixed
     */
    public function getSourcePath(){
        return $this->_sourcePath;
    }

    /**
     * connect to database - wrapepr
     * @return mixed
     */
    public function connectDb() {
        return $this->getDbDriver()->connect($this->getDbInfo());
    }

    /**
     * run query - wrapper
     * @param $query
     * @return mixed
     */
    public function query($query) {
        return $this->getDbDriver()->query($query);
    }

    /**
     * fetch record from result - wrapper
     * @param $result
     * @return mixed
     */
    public function fetch($result) {
        return $this->getDbDriver()->fetch($result);
    }

    /**
     * encode value to Karybu format
     * @param $value
     * @return string
     */
    public function formatValue($value) {
        return base64_encode($value);
    }

    /**
     * set the name of the file to export
     * @param mixed $file
     * @return $this
     */
    public function setExportFileName($file){
        $this->_exportFileName = $file;
        return $this;
    }

    /**
     * get the name of the export file
     * @return mixed
     */
    public function getExportFileName(){
        return $this->_exportFileName;
    }

    /**
     * send xml header
     * @return $this
     * @throws Exception
     */
    public function sendHeader() {
        if (!$this->getExportFileName()){
            throw new Exception("Export filename is not set.");
        }
        header("Content-Type: application/octet-stream");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-Disposition: attachment; filename="'.$this->getExportFileName().'"');
        header("Content-Transfer-Encoding: binary");
        echo '<?xml version="1.0" encoding="utf-8" ?>'.PHP_EOL;
        return $this;
    }

    /**
     * get al supported migration sources from configuration xml
     * @return array|mixed|null
     */
    public function getSupportedSources(){
        if (is_null($this->_sourceTypes)){
            $this->loadInfo();
            $types = $this->_info->xpath('source_types/type');
            $this->_sourceTypes = array();
            if (is_array($types)) {
                foreach ($types as $type) {
                    $this->_sourceTypes[(string)$type->value] = (string)$type->label;
                }
            }
        }
        return $this->_sourceTypes;
    }

    /**
     * check if a source type is valid
     * @param $source
     * @return bool
     */
    public function isValidSourceType($source) {
        $sourceTypes = $this->getSupportedSources();
        return isset($sourceTypes[$source]);
    }

    /**
     * load the information from info.xml
     * @return mixed|null|SimpleXMLElement
     */
    public function loadInfo(){
        if (is_null($this->_info)){
            $contents = file_get_contents($this->_infoFile);
            $this->_info = simplexml_load_string($contents);
        }
        return $this->_info;
    }

    /**
     * get entities for export - wrapper
     * @return mixed
     */
    public function getExportEntities() {
        return $this->getSourceInstance()->getExportEntities();
    }

    /**
     * get the number of entities for export - wrapper
     * @param $entity
     * @return mixed
     */
    public function getEntitiesCount($entity) {
        return $this->getSourceInstance()->getEntitiesCount($entity);
    }

    /**
     * set the entity to be exported - wrapper
     * @param $entity
     * @return $this
     */
    public function setExportEntity($entity) {
        $this->getSourceInstance()->setExportEntity($entity);
        return $this;
    }

    /**
     * get the exported entities - wrapper
     * @param $entity
     * @param $start
     * @param $limit
     * @return mixed
     */
    public function getEntitiesForExport($entity, $start, $limit) {
        return $this->getSourceInstance()->getEntitiesForExport($entity, $start, $limit);
    }

    /**
     * get the limit for exported entities
     * @param $start
     * @param $limit
     * @return mixed
     */
    public function getLimit($start, $limit) {
        return $this->getDbDriver()->getLimit($start, $limit);
    }

    /**
     * print xml the categories
     * @param $categoryList
     * @param bool $withEmpty
     * @return string
     */
    public function printCategoriesXml($categoryList, $withEmpty = false){
        $xml = '<categories>'.PHP_EOL;
        foreach ($categoryList as $obj) {
            $xml .= $this->printCategoryXml($obj, $withEmpty);
        }
        $xml .= '</categories>'.PHP_EOL;
        return $xml;
    }

    /**
     * print a category object
     * @param $obj
     * @param bool $withEmpty
     * @return string
     */
    public function printCategoryXml($obj, $withEmpty = false) {
        if (empty($obj->title) && !$withEmpty) {
            return '';
        }
        $xml = '<category sequence="'.$obj->sequence.'" parent="'.$obj->parent.'">';
        $xml .= $this->formatValue($obj->title);
        $xml .= '</category>'.PHP_EOL;
        return $xml;
    }

    /**
     * make an article xml
     * @param $obj
     * @param bool $withEmpty
     * @return string
     */
    public function printPostXml($obj, $withEmpty = false) {
        return $this->printObjectXml($obj, 'post', $withEmpty);
    }

    /**
     * general method for turning an object to xml
     * @param $obj
     * @param $mainTag
     * @param bool $withEmpty
     * @param bool $addCount
     * @return string
     */
    public function printObjectXml($obj, $mainTag, $withEmpty = false, $addCount = false){
        $xml = '<'.$mainTag.'>'.PHP_EOL;
        foreach ($obj as $k=>$v) {
            if(!$v && !$withEmpty) {
                continue;
            }
            if (is_array($v)) {
                $xml .= $this->printListXml($v, $k.'s', $k, $withEmpty);
            }
            else{
                $xml .= '<'.$k.'>'.$this->formatValue($v).'</'.$k.'>'.PHP_EOL;
            }
        }
        $xml .= '</'.$mainTag.'>'.PHP_EOL;
        return $xml;
    }

    /**
     * make a list of objects as xml
     * @param $list
     * @param $groupTag
     * @param $mainTag
     * @param bool $withEmpty
     * @return string
     */
    public function printListXml($list, $groupTag, $mainTag, $withEmpty = false){
        if (!is_array($list) || (count($list) == 0 && !$withEmpty)){
            return '';
        }
        $xml = '<'.$groupTag.' count="'.count($list).'">'.PHP_EOL;
        foreach ($list as $obj) {
            $xml .= $this->printObjectXml($obj, $mainTag, $withEmpty);
        }
        $xml .= '</'.$groupTag.'>'.PHP_EOL;
        return $xml;
    }
}