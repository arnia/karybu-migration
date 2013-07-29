<?php
/**
 * general export source. all apps should extend this class
 * Class AbstractSource
 */
abstract class AbstractSource {
    /**
     * path to application to be migrated
     * @var mixed
     */
    protected $_sourcePath = null;
    /**
     * reference to the Migration instance
     * @var mixed
     */
    protected $_migration = null;
    /**
     * entity to export
     * @var mixed
     */
    protected $_exportEntity = null;

    /**
     * get database config
     * @param $path
     * @return mixed
     */
    abstract public function getDbConfig($path);

    /**
     * setter for source path
     * @param $path
     * @return $this
     */
    public function setSourcePath($path){
        $this->_sourcePath = $path;
        return $this;
    }

    /**
     * getter for source path
     * @return mixed|null
     */
    public function getSourcePath(){
        return $this->_sourcePath;
    }

    /**
     * set reference to Migration instance
     * @param Migration $migration
     * @return $this
     */
    public function setMigration(Migration $migration){
        $this->_migration = $migration;
        return $this;
    }

    /**
     * get reference to Migration instance
     * @return mixed|null
     */
    public function getMigration(){
        return $this->_migration;
    }

    /**
     * get entities that can be exported
     * should be overwritten in child classes if more entities can be exported
     * @return array
     */
    public function getExportEntities() {
        return array(
            'member'=>'Members',
            'article'=>'Articles'
        );
    }

    /**
     * set entity code for export
     * @param $entity
     * @return $this
     * @throws Exception
     */
    public function setExportEntity($entity){
        if (!$this->isValidEntity($entity)){
            throw new Exception("Entity {$entity} is not valid");
        }
        $this->_exportEntity = $entity;
        return $this;
    }

    /**
     * check if entity can be exported
     * @param $entity
     * @return bool
     */
    public function isValidEntity($entity){
        $exportEntities = $this->getExportEntities();
        return isset($exportEntities[$entity]);
    }
}