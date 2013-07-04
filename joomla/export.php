<?php
// florin, 7/4/13, 3:57 PM

@set_time_limit(0);
// zMigration class require
require_once('./lib.inc.php');
require_once('./zMigration.class.php');
$oMigration = new zMigration();

// Retrieve request data
$path = $_GET['path'];
if(substr($path,-1)=='/') $path = substr($path,0,strlen($path)-1);
$start = $_GET['start'];
$limit_count = $_GET['limit_count'];
$exclude_attach = $_GET['exclude_attach']=='Y'?'Y':'N';
$filename = $_GET['filename'];
$target_module = $_GET['target_module'];

// Get phpBB database info
$db_info = getDBInfo($path);
if(!$db_info) {
    header("HTTP/1.0 404 Not Found");
    exit();
}

$module_id = 'joomla';

// set DB info in zMigration class
$oMigration->setDBInfo($db_info);

// setup target module info
$oMigration->setModuleType($target_module, $module_id);

// make sure charset used is UTF-8
$oMigration->setCharset('UTF-8', 'UTF-8');

// init filename
$oMigration->setFilename($filename);

// init path
$oMigration->setPath($path);

// attempt to connect to database
if($oMigration->dbConnect()) {
    header("HTTP/1.0 404 Not Found");
    exit();
}


// get the limit part of the query
$limit_query = $oMigration->getLimitQuery($start, $limit_count);

/**
 * Start export
 **/
// Print XML file header
$oMigration->setItemCount($limit_count);
$oMigration->printHeader();

