<?php
/**
 * Method for retrieving database info from a Joomla 2.5 application
 **/
function getDBInfo($path) {
    if(substr($path,-1)=='/') $path = substr($path, 0, strlen($path)-1);
    $config_file = $path . '/configuration.php';
    if(!file_exists($config_file)) {
        throw new Exception('No config file in ' . $path);
    }
    require_once($config_file);
    if (!class_exists('JConfig')) {
        throw new Exception('Weird configuration file for Joomla 2.5, can\'t use it');
    }
    $joomlaConfig = new JConfig;
    $info = new stdClass;
    $info->db_type = $joomlaConfig->dbtype;
    //$info->db_port = $dbport;
    $info->db_hostname = $joomlaConfig->host;
    $info->db_userid = $joomlaConfig->user;
    $info->db_password = $joomlaConfig->password;
    $info->db_database = $joomlaConfig->db;
    $info->db_table_prefix = preg_replace('/_$/','', $joomlaConfig->dbprefix);
    return $info;
}
