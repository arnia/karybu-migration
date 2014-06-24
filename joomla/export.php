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
    $prefix = $db_info->db_table_prefix;
    $q = "SELECT
                u.*
              FROM
                %s_users u
              ORDER BY u.id asc
              %s
            ";
    $query = sprintf($q, $prefix, $limit_query);
    $member_result = $oMigration->query($query);

    // Transform phpbb members into objects that represent XE member
    // Password is not imported because XE and phpBB use different hashing algorithms.
    while($member_info = $oMigration->fetch($member_result)) {

        $obj = new stdClass;
        $obj->user_id = $member_info->username;
        $obj->user_name = $member_info->name;
        $obj->nick_name = $member_info->username;
        $obj->email = $member_info->email;
        $obj->password = $member_info->password;
        $obj->birthday = $member_info->birthday;
        $obj->homepage = $member_info->homepage;
        //$obj->blog = $member_info->user_website;
        //$obj->allow_mailing = $member_info->user_notify!=0?'Y':'N';
        //$obj->allow_message = $member_info->user_notify_pm!=0?'Y':'N';
        $obj->regdate = date("YmdHis", strtotime($member_info->registerDate));
        //$obj->signature = $member_info->user_sig;

        // TODO Also import avatar pictures into profile images / image marks
        //$obj->image_nickname = sprintf("%s%d.gif", $image_nickname_path, $member_info->no);
        //if($member_info->icon) $obj->image_mark = sprintf("%s/%s", $path, $member_info->icon);
        //$obj->profile_image = '';

        $oMigration->printMemberItem($obj);
    }

}

else {
    $q = sprintf("DESCRIBE %s_jtags_tags", $db_info->db_table_prefix);
    $result = $oMigration->query($q);
    if ($result){
        $hasTags = true;
    }
    else{
        $hasTags = false;
    }
    /**************************
     * Categories (document_categories)
     **************************/
    //show categories only on first batch

    if ($start == 0) {
        // Retrieve joomla categories
        $query = sprintf("select category.id as category_srl
                                , category.parent_id as parent_srl
                                , category.title as title
                                , category.description as description
                              from %s_categories as category", $db_info->db_table_prefix);

        $category_result = $oMigration->query($query);
        while($category_info= $oMigration->fetch($category_result)) {
            $obj = new stdClass;
            $obj->title = strip_tags($category_info->title);
            $obj->sequence = $category_info->category_srl;
            $obj->parent = $category_info->parent_srl;
            $category_list[$category_info->category_srl] = $obj;
            $category_titles[$obj->title] = $category_info->category_srl;
        }

        // Write categories to XML file
        $oMigration->printCategoryItem($category_list);

        //tags
        //check if tags exist
//        if ($hasTags){
//            $query = sprintf("select * from %s_jtags_tags", $db_info->db_table_prefix);
//            $tags_result = $oMigration->query($query);
//            $tags = array();
//            while($tag_info = $oMigration->fetch($tags_result)) {
//                $obj = new stdClass;
//                $obj->tag_id = $tag_info->tag_id;
//                $obj->tag = $tag_info->name;
//                $tags[$obj->tag_id] = $obj;
//            }
//            $oMigration->printTagItem($tags);
//        }
    }
    /**************************
     * Documents
     **************************/
    // Retrieve joomla articles
    $query = "
			select article.id as document_srl
				 , article.title as title
				 , COALESCE( NULLIF(  `fulltext` ,  '' ) , introtext ) AS content
				 , user.id as user_id
				 , user.username as user_name
				 , user.username as nick_name
				 , user.email as email_address
				 , created as regdate
				 , modified as last_update
				 , metakey as meta_keywords
				 , introtext as introtext
				 , alias
				 , hits as readed_count
			from {$db_info->db_table_prefix}_content as article
				inner join {$db_info->db_table_prefix}_users as user on user.id = article.created_by
			order by article.id asc
			{$limit_query}";
    $document_result = $oMigration->query($query);

    while($document_info = $oMigration->fetch($document_result)) {
        $obj = new stdClass;

        // Setup document common attributes
        //$obj->id = $document_info->document_srl;
        $obj->title = $document_info->title;
        $obj->user_id = $document_info->user_id;
        $obj->user_name = $document_info->user_name;
        $obj->nick_name = $document_info->nick_name;
        $obj->email = $document_info->email_address;
        $obj->regdate =  date('YmdHis', strtotime($document_info->regdate));
        $obj->last_update =  date('YmdHis', strtotime($document_info->last_update));
        $obj->meta_keywords = $document_info->meta_keywords;
        $obj->readed_count = $document_info->readed_count;
        if($document_info->content == $document_info->introtext) {
            $content = $document_info->content;
        } else {
            $content = $document_info->introtext.$document_info->content;
        }
        //images
        preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $content, $match, PREG_PATTERN_ORDER);
        $obj->images = $match[1];

        foreach ($obj->images as $key => $value){
            $new_val = './files/attach/' . $value;
            $content = str_replace($value, $new_val, $content);
            $obj->images[$key] = $new_val;
        }
        $obj->content = $content;
        $obj->meta_description = substr(strip_tags($obj->content),0,100) . '...';


        //alias
        $obj->alias = $document_info->document_srl.'-'.$document_info->alias;

        // Retrieve document categories
        $query = sprintf("select category.id as category_srl
									, category.title as title
								from %s_content article
								  inner join %s_categories category on category.id = article.catid
								where article.id = %d"
            ,$db_info->db_table_prefix,$db_info->db_table_prefix, $document_info->document_srl);

        $cat_result = $oMigration->query($query);
        $tags = array();
        while($cat_info = $oMigration->fetch($cat_result)) {
            $tags[] = $cat_info->title;
            if(!isset($obj->category)) $obj->category = $cat_info->title;
        }
        //retrieve document tags
        if ($hasTags){
            $itemTags = array();
            $query = sprintf("select tags.tag_id as tag_srl
									, tags.name as tag
								from %s_jtags_items item_tag
								  inner join %s_jtags_tags tags on item_tag.tag_id = tags.tag_id
								where item_tag.item_id = %d AND item_tag.component = 'com_content'"
                ,$db_info->db_table_prefix,$db_info->db_table_prefix, $document_info->document_srl);
            $tags_result = $oMigration->query($query);
            while($tag_info = $oMigration->fetch($tags_result)) {
                $itemTags[] = $tag_info->tag;
                if(!isset($obj->tags)) {
                    $obj->tags = array();
                }
                $obj->tags[] = $tag_info->tag;
            }
            //set tags on one line
            //$obj->tag_count = count($obj->tags);
            $obj->tags = implode(',', $obj->tags);
        }
        // Retrieve document comments
        $comments = array();
        $query = "
				select comment.id as comment_srl
				   , comment.parent as parent_srl
				   , comment.title as title
				   , comment.comment as content
				   , comment.isgood as voted_count
				   , comment.ispoor as blamed_count
				   , comment.subscribe as notify_message
				   , 0 as user_id
				   , comment.name as user_name
				   , comment.name as nick_name
				   , comment.email as email_address
				   , comment.homepage as homepage
				   , date as regdate
				   , date as last_update
				   , comment.ip as ipaddress
				from {$db_info->db_table_prefix}_content as article
				  inner join {$db_info->db_table_prefix}_jcomments as comment
					 on comment.object_id = article.id
				where article.id = {$document_info->document_srl}
				order by comment.id asc
			";

        $comment_result = $oMigration->query($query);
        while($comment_info = $oMigration->fetch($comment_result)) {
            $comment_obj = new stdClass;

            $comment_obj->sequence = $comment_info->comment_srl;
            $comment_obj->parent = $comment_info->parent_srl;
            $comment_obj->content = $comment_info->content;
            $comment_obj->voted_count = $comment_info->voted_count;
            $comment_obj->notify_message = $comment_info->notify_message;
            $comment_obj->user_id = $comment_info->user_id;
            $comment_obj->nick_name = $comment_info->nick_name;
            $comment_obj->user_name = $comment_info->user_name;
            $comment_obj->email = $comment_info->email_address;
            $comment_obj->homepage = $comment_info->homepage;
            $comment_obj->regdate = date("YmdHis",strtotime($comment_info->regdate));
            $comment_obj->last_update = date("YmdHis",strtotime($comment_info->last_update));
            $comment_obj->ipaddress = $comment_info->ipaddress;
            $comments[] = $comment_obj;
        }

        $obj->comments = $comments;
        //if ($obj->tags){
            $oMigration->printPostItem($document_info->document_srl, $obj, $exclude_attach);
        //}
    }

    /*$contentTable = $db_info->db_table_prefix . '_content';
    $usersTable = $db_info->db_table_prefix . '_users';
    $query = <<<endOfQuery
SELECT
    c.id AS document_srl,
    c.title AS title,
    COALESCE( NULLIF(  `fulltext` ,  '' ) , introtext ) AS content,
    c.alias AS alias,
    DATE_FORMAT(  `created` , '%Y%m%d%H%i%S' ) AS regdate,
    DATE_FORMAT(  `modified` ,  '%Y%m%d%H%i%S' ) AS last_update,
    c.created_by AS user_id,
    u.username AS user_name,
    u.name AS nick_name,
    u.email AS email_address
FROM $contentTable c
LEFT JOIN $usersTable u ON c.created_by = u.id
ORDER BY c.id ASC
$limit_query
endOfQuery;
    $documentsResult = $oMigration->query($query);
    while ($doc = $oMigration->fetch($documentsResult)) {
//        $obj = new stdClass;
        //TODO maybe get user info? get comments?
        $oMigration->printPostItem($doc->document_srl, $doc);
    }*/
}

$oMigration->printFooter();