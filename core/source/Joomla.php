<?php
require_once 'AbstractSource.php';
/**
 * Joomla export handler
 * Class Joomla
 */
class Joomla extends AbstractSource {
    /**
     * db config
     * @var mixed
     */
    protected $_dbConfig = null;

    /**
     * get joomla configuration values
     * @param $path
     * @return mixed|null|Object
     * @throws Exception
     */
    public function getDbConfig($path){
        if (is_null($this->_dbConfig)){
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
            $info = new Object();
            $info->db_type = $joomlaConfig->dbtype;
            $info->db_hostname = $joomlaConfig->host;
            $info->db_userid = $joomlaConfig->user;
            $info->db_password = $joomlaConfig->password;
            $info->db_database = $joomlaConfig->db;
            $info->db_table_prefix = preg_replace('/_$/','', $joomlaConfig->dbprefix);
            $this->_dbConfig = $info;
        }
        return $this->_dbConfig;
    }

    /**
     * get entities count for splitting into X files (hmmm...x-files)
     * @param $entity
     * @return int
     */
    public function getEntitiesCount($entity) {
        switch ($entity){
            case 'member':
                return $this->getMemberCount();
            break;
            case 'article':
                return $this->getArticleCount();
            break;
            default:
                return 0;
            break;
        }
    }

    /**
     * entities for export (as xml)
     * @param $entity
     * @param $start
     * @param $limit
     * @return array|string
     */
    public function getEntitiesForExport($entity, $start, $limit){
        switch ($entity) {
            case 'member' :
                return $this->getMembers($start, $limit);
            break;
            case 'article' :
                return $this->getArticles($start, $limit);
            break;
            default:
                return array();
            break;
        }
    }

    /**
     * get number of members
     * @return mixed
     */
    public function getMemberCount(){
        $dbInfo = $this->_dbConfig;
        $query = "select count(*) as count from {$dbInfo->db_table_prefix}_users";
        $result = $this->getMigration()->query($query);
        $data = $this->getMigration()->fetch($result);
        return $data->count;
    }

    /**
     * get total number of articles
     * @return mixed
     */
    public function getArticleCount(){
        $dbInfo = $this->_dbConfig;
        $query = "select count(*) as count from {$dbInfo->db_table_prefix}_content";
        $result = $this->getMigration()->query($query);
        $data = $this->getMigration()->fetch($result);
        return $data->count;
    }

    /**
     * get members for export
     * @param $start
     * @param $limit
     * @return string
     */
    public function getMembers($start, $limit){
        $dbInfo = $this->_dbConfig;
        $prefix = $dbInfo->db_table_prefix;
        $limit = $this->getMigration()->getLimit($start, $limit);
        //check if extended profile is enabled
        $checkQuery = "DESCRIBE {$prefix}_user_profiles";
        $checkResult = $this->getMigration()->query($checkQuery);
        //if extended user profile enabled, get additional info
        if ($checkResult) {
            $query = "SELECT
                    u.*,
                    REPLACE(upb.profile_value, '\"', '') as birthday,
                    REPLACE(upw.profile_value, '\"', '') as homepage
                  FROM
                    {$prefix}_users u
                  LEFT JOIN
                    {$prefix}_user_profiles upb ON u.id = upb.user_id AND upb.profile_key = 'profile.dob'
                  LEFT JOIN
                    {$prefix}_user_profiles upw ON u.id = upw.user_id AND upw.profile_key = 'profile.website'
                  ORDER BY u.id asc
                    {$limit}
                ";
        }
        //if there is no extended user profile just take data from the user table.
        else {
            $query = "SELECT
                    u.*
                  FROM
                    {$prefix}_users u
                  ORDER BY u.id asc
                    {$limit}
                ";
        }
        $result = $this->getMigration()->query($query);
        $members = array();
        //transform members to Karybu format
        while ($member = $this->getMigration()->fetch($result)) {
            $obj = new Object();
            //convert to object to avoid errors
            $obj->user_id   = $member->username;
            $obj->user_name = $member->name;
            $obj->nick_name = $member->username;
            $obj->email     = $member->email;
            $obj->password  = $member->password;
            $obj->birthday  = $member->birthday;
            $obj->homepage  = $member->homepage;
            $obj->regdate   = date("YmdHis", strtotime($member->registerDate));

            $members[]      = $obj;

        }
        //generate xml
        $xml = $this->getMigration()->printListXml($members, 'members', 'member');
        return $xml;
    }

    /**
     * get articles xml
     * @param $start
     * @param $limit
     * @return string
     */
    public function getArticles($start, $limit) {
        $dbInfo = $this->_dbConfig;
        $prefix = $dbInfo->db_table_prefix;
        $limit = $this->getMigration()->getLimit($start, $limit);
        //check if tags extension is enabled
        $checkQuery = "DESCRIBE {$prefix}_jtags_tags";
        $checkResult = $this->getMigration()->query($checkQuery);
        if ($checkResult){
            $hasTags = true;
        }
        else{
            $hasTags = false;
        }
        $categoriesXml = '';
        $categoryList = array();
        if ($start == 0) {
            //get all categories but only for the first xml
            $categoriesXml = '<categories>'.PHP_EOL;
            // Retrieve joomla categories
            $query = sprintf("select category.id as category_srl
                                , category.parent_id as parent_srl
                                , category.title as title
                                , category.description as description
                              from %s_categories as category", $prefix);

            $categoryResult = $this->getMigration()->query($query);
            $categoryTitles = array();
            while($categoryInfo = $this->getMigration()->fetch($categoryResult)) {
                $obj = new Object();
                $obj->title = strip_tags($categoryInfo->title);
                $obj->sequence = $categoryInfo->category_srl;
                $obj->parent = $categoryInfo->parent_srl;
                $categoryList[$categoryInfo->category_srl] = $obj;
                $categoryTitles[$obj->title] = $categoryInfo->category_srl;
            }
        }
        //posts
        $query = "
			select article.id as document_srl
				 , article.title as title
				 , COALESCE( NULLIF(  `fulltext` ,  '' ) , introtext ) AS content
				 , user.id as user_id
				 , user.username as user_name
				 , user.username as nick_name
				 , user.email as email_address
				 , date_format(from_unixtime(created),'%Y%m%d%H%i%S') as regdate
				 , date_format(from_unixtime(modified),'%Y%m%d%H%i%S') as last_update
				 , metakey as meta_keywords
				 , metadesc as meta_description
				 , alias
			from {$prefix}_content as article
				inner join {$prefix}_users as user on user.id = article.created_by
			order by article.id desc
			{$limit}";
        $documentResult = $this->getMigration()->query($query);
        $posts = array();
        while($documentInfo = $this->getMigration()->fetch($documentResult)) {
            $obj = new Object();
            // Setup document common attributes
            $obj->title             = $documentInfo->title;
            $obj->content           = $documentInfo->content;
            $obj->user_id           = $documentInfo->user_id;
            $obj->user_name         = $documentInfo->user_name;
            $obj->nick_name         = $documentInfo->nick_name;
            $obj->email             = $documentInfo->email_address;
            $obj->regdate           = $documentInfo->regdate;
            $obj->update            = $documentInfo->last_update;
            $obj->meta_description  = $documentInfo->meta_description;
            $obj->meta_keywords     = $documentInfo->meta_keywords;
            $obj->alias             = $documentInfo->document_srl.'-'.$documentInfo->alias;

            // Retrieve document categories
            $query = sprintf("select category.id as category_srl
									, category.title as title
								from %s_content article
								  inner join %s_categories category on category.id = article.catid
								where article.id = %d"
                ,$prefix, $prefix, $documentInfo->document_srl);

            $catResult = $this->getMigration()->query($query);
            $tags = array();
            while($catInfo = $this->getMigration()->fetch($catResult)) {
                $tags[] = $catInfo->title;
                if(!isset($obj->category)) {
                    $obj->category = $catInfo->title;
                }
            }
            //retrieve document tags - if extension is enabled
            if ($hasTags){
                $itemTags = array();
                $query = sprintf("select tags.tag_id as tag_srl
									, tags.name as tag
								from %s_jtags_items item_tag
								  inner join %s_jtags_tags tags on item_tag.tag_id = tags.tag_id
								where item_tag.item_id = %d AND item_tag.component = 'com_content'"
                          ,$prefix ,$prefix, $documentInfo->document_srl);
                $tagsResult = $this->getMigration()->query($query);
                $obj->tags = array();
                while($tagInfo = $this->getMigration()->fetch($tagsResult)) {
                    $itemTags[] = $tagInfo->tag;
                    $obj->tags[] = $tagInfo->tag;
                }
                //set tags on one line
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
				   , date_format(from_unixtime(date),'%Y%m%d%H%i%S') as regdate
				   , date_format(from_unixtime(date),'%Y%m%d%H%i%S') as last_update
				   , comment.ip as ipaddress
				from {$prefix}_content as article
				  inner join {$prefix}_jcomments as comment
					 on comment.object_id = article.id
				where article.id = {$documentInfo->document_srl}
				order by comment.id asc
			";

            $commentResult = $this->getMigration()->query($query);
            while($commentInfo = $this->getMigration()->fetch($commentResult)) {
                $commentObj = new Object();

                $commentObj->sequence          = $commentInfo->comment_srl;
                $commentObj->parent            = $commentInfo->parent_srl;
                $commentObj->content           = $commentInfo->content;
                $commentObj->voted_count       = $commentInfo->voted_count;
                $commentObj->notify_message    = $commentInfo->notify_message;
                $commentObj->user_id           = $commentInfo->user_id;
                $commentObj->nick_name         = $commentInfo->nick_name;
                $commentObj->user_name         = $commentInfo->user_name;
                $commentObj->email             = $commentInfo->email_address;
                $commentObj->homepage          = $commentInfo->homepage;
                $commentObj->regdate           = $commentInfo->regdate;
                $commentObj->update            = $commentInfo->last_update;
                $commentObj->ipaddress         = $commentInfo->ipaddress;

                $comments[] = $commentObj;
            }

            $obj->comment = $comments;
            $posts[] = $obj;
        }
        $xml ='<posts count="'.count($posts).'">'.PHP_EOL;
        if (count($categoryList) > 0) {
            $xml .= $this->getMigration()->printCategoriesXml($categoryList);
        }
        foreach ($posts as $post) {
            $xml .= $this->getMigration()->printPostXml($post);
        }
        $xml .='</posts>'.PHP_EOL;
        return $xml;
    }
}