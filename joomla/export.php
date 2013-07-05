<?php
// florin, 7/4/13, 3:57 PM

error_reporting(0);
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

if($target_module == 'member') {

    // Retrieve all members from the database
    // filter by user_type: Defines what type the user is. 0 is normal user, 1 is inactive and needs to activate their account through an activation link sent in an email, 2 is a pre-defined type to ignore user (i.e. bot), 3 is Founder.
    $query = sprintf("select * from %s_users order by id asc %s ",$db_info->db_table_prefix, $limit_query);
    $member_result = $oMigration->query($query);

    // Transform phpbb members into objects that represent XE member
    // Password is not imported because XE and phpBB use different hashing algorithms.
    while($member_info = $oMigration->fetch($member_result)) {
        $obj = new stdClass;
        $obj->user_id = $member_info->username;
        $obj->user_name = $member_info->name;
        $obj->nick_name = $member_info->username;
        $obj->email = $member_info->email;
        //$obj->homepage = $member_info->user_website;
        //$obj->blog = $member_info->user_website;
        //$obj->birthday = date("YmdHis", strtotime($member_info->user_birthday));
        //$obj->allow_mailing = $member_info->user_notify!=0?'Y':'N';
        //$obj->allow_message = $member_info->user_notify_pm!=0?'Y':'N';
        $obj->regdate = date("YmdHis", strtotime($member_info->registerDate));
        //$obj->signature = $member_info->user_sig;

        // TODO Also import avatar pictures into profile images / image marks
        //$obj->image_nickname = sprintf("%s%d.gif", $image_nickname_path, $member_info->no);
        //if($member_info->icon) $obj->image_mark = sprintf("%s/%s", $path, $member_info->icon);
        //$obj->profile_image = '';

        $obj->extra_vars = array(
            'icq' => $member_info->user_icq,
            'yim' => $member_info->user_yim,
            'msn' => $member_info->user_msnm,
            'job' => $member_info->user_occ,
            'hobby' => $member_info->user_interests,
            'address' => $member_info->user_from
        );

        $oMigration->printMemberItem($obj);
    }
}
