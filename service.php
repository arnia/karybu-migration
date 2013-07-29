<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once 'core/Migration.php';
$response = array();
$sourceType = getRequest('source_type');
$path = getRequest('path');
if (isset($_REQUEST['action'])) {
    try {
        switch ($_REQUEST['action']) {
            case 'source' :
                $response = processSource($sourceType, $path);
                break;
            case 'export' :
                $filename = getRequest('filename');
                $start = getRequest('start');
                $limit = getRequest('limit');
                $type = getRequest('type');
                exportEntity($sourceType, $path, $type, $start, $limit, $filename);
                exit;
                break;
        }
    }
    catch (Exception $e) {
        $response['error'] = 1;
        $response['message'] = $e->getMessage();
    }
}
echo json_encode($response);

/**
 * filter request values
 * @param $key
 * @param string $default
 * @return string
 */
function getRequest($key, $default = ''){
    if (isset($_REQUEST[$key])){
        return $_REQUEST[$key];
    }
    return $default;
}

/**
 * instantiate the migration class
 * @param $sourceType
 * @param $path
 * @return Migration
 * @throws Exception
 */
function initMigrate($sourceType, $path){
    $migrate = new Migration();
    if (!$migrate->isValidSourceType($sourceType)){
        throw new Exception("Source type {$sourceType} is not supported");
    }
    $migrate->setSource($sourceType);
    $migrate->setSourcePath($path);
    return $migrate;
}

/**
 * process migration source selection
 * @param $sourceType
 * @param $path
 * @return array
 */
function processSource($sourceType, $path) {
    $response = array();
    $migrate = initMigrate($sourceType, $path);
    $migrate->getDbInfo();
    $response['error'] = 0;
    $entities = $migrate->getExportEntities();
    foreach ($entities as $key=>$entity){
        $count = $migrate->getEntitiesCount($key);
        $response['entities'][] = array('entity'=>$key, 'label'=>$entity, 'count'=>$count);
    }
    return $response;
}

/**
 * export an entity
 * @param $sourceType
 * @param $path
 * @param $type
 * @param $start
 * @param $limit
 * @param $filename
 */
function exportEntity($sourceType, $path, $type, $start, $limit, $filename){
    $migrate = initMigrate($sourceType, $path);
    $migrate->setExportEntity($type);
    $migrate->getDbInfo();
    $migrate->connectDb();
    $migrate->setExportFileName($filename);
    $migrate->sendHeader();
    echo $migrate->getEntitiesForExport($type, $start, $limit);
}
