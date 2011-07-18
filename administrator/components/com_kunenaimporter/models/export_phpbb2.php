<?php
/**
 * @package com_kunenaimporter
 *
 * Imports forum data into Kunena
 *
 * @Copyright (C) 2009 - 2011 Kunena Team All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 *
 */
defined ( '_JEXEC' ) or die ();

require_once (JPATH_COMPONENT . '/models/export.php');

/**
 * phpBB2 Exporter Class
 * 
 * Exports almost all data from phpBB2.
 * @todo Configuration import needs some work
 * @todo Forum ACL not exported (except for moderators)
 * @todo URL avatars not exported
 * @todo Ranks not exported
 * @todo Private messages not exported
 * @todo Some emoticons may be missing (images/db are not exported)
 * @todo Password hashs should work in Joomla
 */
class KunenaimporterModelExport_phpBB2 extends KunenaimporterModelExport {
	/**
	 * Extension name
	 * @var string
	 */
	public $name = 'phpbb2';
	/**
	 * Display name
	 * @var string
	 */
	public $title = 'phpBB2';
	/**
	 * External application
	 * @var bool
	 */
	public $external = true;
	/**
	 * Minimum required version (adds MySQL 5 support)
	 * @var string or null
	 */
	protected $versionmin = '2.0.19';
	/**
	 * Maximum accepted version
	 * @var string or null
	 */
	protected $versionmax = '2.0.999';
	protected $dbconfig = null;

	/**
	 * Detect if component and config.php exists
	 * 
	 * @return bool
	 */
	public function detectComponent($path=null) {
		if ($path === null) $path = $this->basepath;
		// Make sure that configuration file exist, but check also something else
		if (!JFile::exists("{$path}/config.php")
			|| !JFile::exists("{$path}/admin/admin_board.php")
			|| !JFile::exists("{$path}/viewtopic.php")) {
			return false;
		}
		return true;
	}

	/**
	 * Get database object
	 */
	public function getDatabase() {
		$config = $this->getDBConfig();
		$database = null;
		if ($config) {
			$app = JFactory::getApplication ();
			$option ['driver'] = $app->getCfg ( 'dbtype' );
			$option ['host'] = $config['dbhost'];
			$option ['user'] = $config['dbuser'];
			$option ['password'] = $config['dbpasswd'];
			$option ['database'] = $config['dbname'];
			$option ['prefix'] = $config['table_prefix'];
			$database = JDatabase::getInstance ( $option );
		}
		return $database;
	}

	/**
	 * Get database settings
	 */
	protected function &getDBConfig() {
		if (!$this->dbconfig) {
			require "{$this->basepath}/config.php";
			$this->dbconfig = get_defined_vars();
		}
		return $this->dbconfig;
	}

	/**
	 * Initialization needed by exporter
	 */
	public function initialize() {
		global $phpbb_root_path, $phpEx;

		if(!defined('IN_PHPBB')) {
			define('IN_PHPBB', true);
		}

		if(!defined('STRIP')) {
			define('STRIP', (get_magic_quotes_gpc()) ? true : false);
		}

		$phpbb_root_path = $this->basepath.'/';
		$phpEx = substr(strrchr(__FILE__, '.'), 1);
	}

	/**
	 * Get configuration
	 */
	public function &getConfig() {
		if (empty($this->config)) {
			// Check if database settings are correct
			$query = "SELECT config_name, config_value AS value FROM #__config";
			$this->ext_database->setQuery ( $query );
			$this->config = $this->ext_database->loadObjectList ('config_name');
		}
		return $this->config;
	}

	/**
	 * Get component version
	 */
	public function getVersion() {
		$query = "SELECT config_value FROM #__config WHERE config_name='version'";
		$this->ext_database->setQuery ( $query );
		$version = $this->ext_database->loadResult ();
		if ($version [0] == '.')
			$version = '2' . $version;
		return $version;
	}

	/**
	 * Remove htmlentities, addslashes etc
	 * 
	 * @param string $s String
	 */
	protected function parseText(&$s) {
		$s = html_entity_decode ( $s );
	}
		
	/**
	 * Convert BBCode to Kunena BBCode
	 *
	 * @param string $s String
	 */
	protected function parseBBcode(&$s) {
		$s = preg_replace ( '/\[b:(.*?)\]/', '[b]', $s );
		$s = preg_replace ( '/\[\/b:(.*?)\]/', '[/b]', $s );
	
		$s = preg_replace ( '/\[i:(.*?)\]/', '[i]', $s );
		$s = preg_replace ( '/\[\/i:(.*?)\]/', '[/i]', $s );
	
		$s = preg_replace ( '/\[u:(.*?)\]/', '[u]', $s );
		$s = preg_replace ( '/\[\/u:(.*?)\]/', '[/u]', $s );
	
		$s = preg_replace ( '/\[quote:(.*?)\]/', '[quote]', $s );
		$s = preg_replace ( '/\[quote(:(.*?))?="(.*?)"\]/', '[b]\\3[/b]\n[quote]', $s );
		$s = preg_replace ( '/\[\/quote:(.*?)\]/', '[/quote]', $s );
	
		#$s = preg_replace('/\[img:(.*?)="(.*?)"\]/', '[img="\\2"]', $s);
		$s = preg_replace ( '/\[img:(.*?)\](.*?)\[\/img:(.*?)\]/si', '[img]\\2[/img]', $s );
	
		$s = preg_replace ( '/\[color=(.*?):(.*?)\]/', '[color=\\1]', $s );
		$s = preg_replace ( '/\[\/color:(.*?)\]/', '[/color]', $s );
	
		$s = preg_replace ( '/\[size=[1234567]:(.*?)\]/', '[size=1]', $s );
		$s = preg_replace ( '/\[size=(9|10|11):(.*?)\]/', '[size=2]', $s );
		$s = preg_replace ( '/\[size=1[23456]:(.*?)\]/', '[size=3]', $s );
		$s = preg_replace ( '/\[size=((1[789])|(2[012])):(.*?)\]/', '[size=4]', $s );
		$s = preg_replace ( '/\[size=2[345678]:(.*?)\]/', '[size=5]', $s );
		$s = preg_replace ( '/\[size=\d\d:(.*?)\]/', '[size=6]', $s );
		$s = preg_replace ( '/\[\/size:(.*?)\]/', '[/size]', $s );
	
		$s = preg_replace ( '/\[code:(.*?)]/', '[code]', $s );
		$s = preg_replace ( '/\[\/code:(.*?)]/', '[/code]', $s );
	
		$s = preg_replace ( '/\[list(:(.*?))?\]/', '[ul]', $s );
		$s = preg_replace ( '/\[list=([a1]):(.*?)\]/', '[ol]', $s );
		$s = preg_replace ( '/\[\/list:u:(.*?)\]/', '[/ul]', $s );
		$s = preg_replace ( '/\[\/list:o:(.*?)\]/', '[/ol]', $s );
	
		$s = preg_replace ( '/\[\*:(.*?)\]/', '[li]', $s );
		$s = preg_replace ( '/\[\/\*:(.*?)\]/', '[/li]', $s );
	
		$s = preg_replace ( '/<!-- s(.*?) --><img src=\"{SMILIES_PATH}.*?\/><!-- s.*? -->/', ' \\1 ', $s );
	
		$s = preg_replace ( '/\<!-- e(.*?) -->/', '', $s );
		$s = preg_replace ( '/\<!-- w(.*?) -->/', '', $s );
		$s = preg_replace ( '/\<!-- m(.*?) -->/', '', $s );
	
		$s = preg_replace ( '/\<a class=\"postlink\" href=\"(.*?)\">(.*?)<\/a>/', '[url=\\1]\\2[/url]', $s );
		$s = preg_replace ( '/\<a href=\"(.*?)\">(.*?)<\/a>/', '[url=\\1]\\2[/url]', $s );
		$s = preg_replace ( '/\<a href=.*?mailto:.*?>/', '', $s );
		$s = preg_replace ( '/\<\/a>/', '', $s );
		
		$s = preg_replace ( '/\[\/url:(.*?)]/', '[/url]', $s );
	}

	/**
	 * Convert HTML to Kunena BBCode
	 *
	 * @param string $s String
	 */
	protected function parseHTML(&$s) {
		$s = preg_replace ( '/\n/', "<br />", $s );
		parent::parseHTML ( $s );
	}

	/**
	 * Map Joomla user to external user
	 *
	 * You can usually remove this function if you are exporting Joomla component.
	 * 
	 * @param object $joomlauser StdClass(id, username, email)
	 * @return int External user ID
	 */
	public function mapJoomlaUser($joomlauser) {
		$username = $joomlauser->username;
		$query = "SELECT user_id
			FROM #__users WHERE username={$this->ext_database->Quote($username)}";

		$this->ext_database->setQuery( $query );
		$result = intval($this->ext_database->loadResult());
		return $result;
	}	
	
	/**
	 * Count total number of users to be exported
	 */
	public function countUsers() {
		$query = "SELECT COUNT(*) FROM #__users AS f WHERE user_id > 0";
		return $this->getCount ( $query );
	}

	/**
	 * Export users
	 * 
	 * Returns list of user extuser objects containing database fields 
	 * to #__kunenaimporter_users.
	 * 
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportUsers($start = 0, $limit = 0) {
		$prefix = $this->ext_database->_table_prefix;
		$prefix = substr ( $prefix, 0, strpos ( $prefix, '_phpbb_' ) );

		$query = "SELECT
			user_id AS extid,
			username AS extusername,
			username AS name,
			username AS username,
			user_email AS email,
			user_password AS password,
			IF(user_level=1, 'Administrator', 'Registered') AS usertype,
			(user_active=0) AS block,
			FROM_UNIXTIME(user_regdate) AS registerDate,
			IF(user_lastvisit>0, FROM_UNIXTIME(user_lastvisit), '0000-00-00 00:00:00') AS lastvisitDate,
			NULL AS params
		FROM #__users
		WHERE user_id > 0
		ORDER BY user_id";
		$result = $this->getExportData ( $query, $start, $limit, 'extid' );
		foreach ( $result as &$row ) {
			$row->extusername = html_entity_decode ( $row->extusername );
			$row->name = html_entity_decode ( $row->name );
			$row->username = html_entity_decode ( $row->username );

			// Add prefix to password (for authentication plugin)
			$row->password = 'phpbb2::'.$row->password;
		}
		return $result;
	}

	/**
	 * Count total number of user profiles to be exported
	 */
	public function countUserProfile() {
		$query = "SELECT COUNT(*) FROM #__users WHERE user_id > 0";
		return $this->getCount ( $query );
	}

	/**
	 * Export user profiles
	 * 
	 * Returns list of user profile objects containing database fields 
	 * to #__kunena_users.
	 * 
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportUserProfile($start = 0, $limit = 0) {
		$query = "SELECT
			user_id AS userid,
			'' AS view,
			user_sig AS signature,
			(user_level=2) AS moderator,
			NULL AS banned,
			0 AS ordering,
			user_posts AS posts,
			user_avatar AS avatar,
			0 AS karma,
			0 AS karma_time,
			0 AS uhits,
			'' AS personalText,
			0 AS gender,
			NULL AS birthdate,
			user_from AS location,
			user_icq AS ICQ,
			user_aim AS AIM,
			user_yim AS YIM,
			user_msnm AS MSN,
			NULL AS SKYPE,
			NULL AS TWITTER,
			NULL AS FACEBOOK,
			NULL AS GTALK,
			NULL AS MYSPACE,
			NULL AS LINKEDIN,
			NULL AS DELICIOUS,
			NULL AS FRIENDFEED,
			NULL AS DIGG,
			NULL AS BLOGSPOT,
			NULL AS FLICKR,
			NULL AS BEBO,
			'' AS websitename,
			user_website AS websiteurl,
			0 AS rank,
			(user_viewemail=0) AS hideEmail,
			user_allow_viewonline AS showOnline,
			user_avatar_type AS avatartype,
			user_allowhtml,
			user_allowbbcode
		FROM #__users
		WHERE user_id > 0
		ORDER BY user_id";
		$result = $this->getExportData ( $query, $start, $limit );

		$path = $config['avatar_path']->value;
		foreach ( $result as $key => &$row ) {
			// Convert bbcode in signature
			if ($row->avatar) {
				switch ($row->avatartype) {
					case 1:
						// Uploaded
						$row->avatar = "users/{$row->avatar}";
						$row->copypath = "{$this->basepath}/{$path}/{$row->avatar}";
						break;
					case 2:
						// URL not supported
						$row->avatar = '';
						break;
					case 3:
						// Gallery
						$row->avatar = "gallery/{$row->avatar}";
						break;
					default:
						$row->avatar = '';
				}
			}
			// Convert bbcode in signature
			if ($row->user_allowbbcode) $this->parseBBcode ( $row->signature );
			if ($row->user_allowhtml) $this->parseHTML ( $row->signature );
			else $this->parseText ( $row->signature );
			$this->parseText ( $row->location );
			$this->parseText ( $row->ICQ );
			$this->parseText ( $row->AIM );
			$this->parseText ( $row->YIM );
			$this->parseText ( $row->MSN );
		}
		return $result;
	}

	/**
	 * Count total number of sessions to be exported
	 */
	public function countSessions() {
		$query = "SELECT COUNT(*) FROM #__users AS u WHERE user_lastvisit>0";
		return $this->getCount ( $query );
	}

	/**
	 * Export user session information
	 * 
	 * Returns list of attachment objects containing database fields 
	 * to #__kunena_sessions.
	 * 
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportSessions($start = 0, $limit = 0) {
		$query = "SELECT
			user_id AS userid,
			user_lastvisit AS lasttime,
			'' AS readtopics,
			user_session_time AS currvisit
		FROM #__users
		WHERE user_lastvisit>0";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}

	/**
	 * Count total number of categories to be exported
	 */
	public function countCategories() {
		$query = "SELECT COUNT(*) FROM #__categories";
		$count = $this->getCount ( $query );
		$query = "SELECT COUNT(*) FROM #__forums";
		return $count + $this->getCount ( $query );
	}

	/**
	 * Export sections and categories
	 * 
	 * Returns list of category objects containing database fields 
	 * to #__kunena_categories.
	 * All categories without parent are sections.
	 * 
	 * NOTE: it's very important to keep category IDs (containing topics) the same!
	 * If there are two tables for sections and categories, change IDs on sections..
	 * 
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportCategories($start = 0, $limit = 0) {
		$query = "SELECT MAX(forum_id) FROM #__forums";
		$this->ext_database->setQuery ( $query );
		$maxforum = $this->ext_database->loadResult ();
		// Import the categories
		$query = "(SELECT
			cat_id+{$maxforum} AS id,
			0 AS parent,
			cat_title AS name,
			0 AS cat_emoticon,
			0 AS locked,
			0 AS alert_admin,
			1 AS moderated,
			'' AS moderators,
			0 AS pub_access,
			1 AS pub_recurse,
			0 AS admin_access,
			1 AS admin_recurse,
			cat_order AS ordering,
			1 AS published,
			0 AS checked_out,
			0 AS checked_out_time,
			0 AS review,
			0 AS allow_anonymous,
			0 AS post_anonymous,
			0 AS hits,
			'' AS description,
			'' AS headerdesc,
			'' AS class_sfx,
			0 AS allow_polls,
			0 AS id_last_msg,
			0 AS numPosts,
			0 AS numTopics,
			0 AS time_last_msg
		FROM #__categories)
		UNION ALL
		(SELECT
			forum_id AS id,
			cat_id+{$maxforum} AS parent,
			forum_name AS name,
			0 AS cat_emoticon,
			(forum_status=1) AS locked,
			0 AS alert_admin,
			1 AS moderated,
			'' AS moderators,
			0 AS pub_access,
			1 AS pub_recurse,
			0 AS admin_access,
			1 AS admin_recurse,
			forum_order AS ordering,
			1 AS published,
			0 AS checked_out,
			0 AS checked_out_time,
			0 AS review,
			0 AS allow_anonymous,
			0 AS post_anonymous,
			0 AS hits,
			forum_desc AS description,
			'' AS headerdesc,
			'' AS class_sfx,
			1 AS allow_polls,
			forum_last_post_id AS id_last_msg,
			forum_posts AS numPosts,
			forum_topics AS numTopics,
			0 AS time_last_msg
		FROM #__forums) ORDER BY id";
		$result = $this->getExportData ( $query, $start, $limit );
		foreach ( $result as $key => &$row ) {
			$this->parseHTML ( $row->description );
		}
		return $result;
	}

	/**
	 * Count total number of moderator columns to be exported
	 */
	public function countModeration() {
		$query = "SELECT COUNT(DISTINCT (CONCAT(g.user_id, '-', a.forum_id)))
		FROM #__auth_access AS a
		INNER JOIN #__user_group AS g ON g.group_id = a.group_id
		WHERE a.auth_mod=1";
		$count = $this->getCount ( $query );
		return $count;
	}

	/**
	 * Export moderator columns
	 * 
	 * Returns list of moderator objects containing database fields 
	 * to #__kunena_moderation.
	 * NOTE: Global moderator doesn't have columns in this table!
	 * 
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportModeration($start = 0, $limit = 0) {
		$query = "SELECT g.user_id AS userid, 
			a.forum_id AS catid
		FROM #__auth_access AS a
		INNER JOIN #__user_group AS g ON g.group_id = a.group_id
		WHERE a.auth_mod=1
		GROUP BY g.user_id, a.forum_id
		ORDER BY `g`.`user_id` ASC";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}
	
	/**
	 * Count total number of messages to be exported
	 */
	public function countMessages() {
		$query = "SELECT count(*) FROM #__posts";
		return $this->getCount ( $query );
	}

	/**
	 * Export messages
	 * 
	 * Returns list of message objects containing database fields 
	 * to #__kunena_messages (and #__kunena_messages_text.message).
	 * 
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportMessages($start = 0, $limit = 0) {
		$query = "SELECT 
			p.post_id AS id, 
			IF(p.post_id=t.topic_first_post_id,0,t.topic_first_post_id) AS parent,
			t.topic_first_post_id AS thread, 
			t.forum_id AS catid, 
			IF(p.post_username, p.post_username, u.username) AS name, 
			IF(p.poster_id<0,0,p.poster_id) AS userid, 
			u.user_email AS email, 
			IF(x.post_subject, x.post_subject, t.topic_title) AS subject, 
			p.post_time AS time, 
			p.poster_ip AS ip, 
			0 AS topic_emoticon,
			(t.topic_status=1 AND p.post_id=t.topic_first_post_id) AS locked, 
			0 AS hold, 
			(t.topic_type>0 AND p.post_id=t.topic_first_post_id) AS ordering, 
			IF(p.post_id=t.topic_first_post_id,0,t.topic_views) AS hits, 
			t.topic_moved_id AS moved, 
			0 AS modified_by, 
			p.post_edit_time AS modified_time, 
			'' AS modified_reason, 
			x.post_text AS message,
			enable_bbcode,
			enable_html
		FROM #__posts AS p 
		LEFT JOIN #__posts_text AS x ON p.post_id = x.post_id 
		LEFT JOIN #__topics AS t ON p.topic_id = t.topic_id 
		LEFT JOIN #__users AS u ON p.poster_id = u.user_id
		ORDER BY p.post_id";
		$result = $this->getExportData ( $query, $start, $limit );
		// Iterate over all the posts and convert them to Kunena
		foreach ( $result as $key => &$row ) {
			$this->parseText ( $row->name );
			$this->parseText ( $row->email );
			$this->parseText ( $row->subject );
			$row->ip = long2ip(hexdec($row->ip));
			if ($row->moved) {
				// TODO: support moved messages (no txt)
				$row->message = "id={$row->moved}";
				$row->moved = 1;
			} else {
				if ($row->enable_bbcode) $this->parseBBcode ( $row->message );
				if ($row->enable_html) $this->parseHTML ( $row->message );
				else $this->parseText ( $row->message );
			}
		}
		return $result;
	}

	/**
	 * Count total polls to be exported
	 */
	public function countPolls() {
		$query="SELECT COUNT(*) FROM #__vote_desc";
		return $this->getCount($query);
	}

	/**
	 * Export polls
	 * 
	 * Returns list of poll objects containing database fields 
	 * to #__kunena_polls.
	 * 
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportPolls($start=0, $limit=0) {
		$query="SELECT
			v.vote_id AS id,
			v.vote_text AS title,
			t.topic_first_post_id AS threadid,
			IF(v.vote_length>0,FROM_UNIXTIME(v.vote_start+v.vote_length),'0000-00-00 00:00:00') AS polltimetolive
		FROM #__vote_desc AS v
		INNER JOIN #__topics AS t ON v.topic_id=t.topic_id
		ORDER BY id";
		$result = $this->getExportData($query, $start, $limit, 'id');
		return $result;
	}

	/**
	 * Count total poll options to be exported
	 */
	public function countPollsOptions() {
		$query="SELECT COUNT(*) FROM #__vote_results";
		return $this->getCount($query);
	}

	/**
	 * Export poll options
	 * 
	 * Returns list of poll options objects containing database fields 
	 * to #__kunena_polls_options.
	 * 
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportPollsOptions($start=0, $limit=0) {
		// WARNING: from unknown reason pollid = threadid!!!
		$query="SELECT
			0 AS id,
			t.topic_first_post_id AS pollid,
			r.vote_option_text AS text,
			r.vote_result AS votes
		FROM #__vote_results AS r
		INNER JOIN #__vote_desc AS v ON v.vote_id=r.vote_id
		INNER JOIN #__topics AS t ON v.topic_id=t.topic_id
		ORDER BY pollid, vote_option_id";
		$result = $this->getExportData($query, $start, $limit);
		return $result;
	}

	/**
	 * Count total poll users to be exported
	 */
	public function countPollsUsers() {
		$query="SELECT COUNT(*) FROM #__vote_voters";
		return $this->getCount($query);
	}

	/**
	 * Export poll users
	 * 
	 * Returns list of poll users objects containing database fields 
	 * to #__kunena_polls_users.
	 * 
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportPollsUsers($start=0, $limit=0) {
		// WARNING: from unknown reason pollid = threadid!!!
		$query="SELECT
			t.topic_first_post_id AS pollid,
			u.vote_user_id AS userid,
			1 AS votes,
			'0000-00-00 00:00:00' AS lasttime,
			0 AS lastvote
		FROM #__vote_voters AS u
		INNER JOIN #__vote_desc AS v ON v.vote_id=u.vote_id
		INNER JOIN #__topics AS t ON v.topic_id=t.topic_id";
		$result = $this->getExportData($query, $start, $limit);
		return $result;
	}

	/**
	 * There's no attachments support in phpBB2
	 */
	public function countAttachments() {
		return false;
	}

	/**
	 * Count total number of subscription items to be exported
	 */
	public function countSubscriptions() {
		$query = "SELECT COUNT(*) FROM #__topics_watch AS w INNER JOIN #__topics AS t ON w.topic_id=t.topic_id";
		return $this->getCount ( $query );
	}

	/**
	 * Export topic subscriptions
	 * 
	 * Returns list of subscription objects containing database fields 
	 * to #__kunena_subscriptions.
	 * 
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportSubscriptions($start = 0, $limit = 0) {
		$query = "SELECT
			t.topic_first_post_id AS thread, 
			w.user_id AS userid,
			w.notify_status AS future1
		FROM #__topics_watch AS w 
		INNER JOIN #__topics AS t ON w.topic_id=t.topic_id";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}

	/*
	public function countSmilies() {
		return false;

		$query = "SELECT COUNT(*) FROM #__smilies";
		return $this->getCount ( $query );
	}

	public function &exportSmilies($start = 0, $limit = 0) {
		$query = "SELECT
			smiley_id AS id,
			code AS code,
			smiley_url AS location,
			smiley_url AS greylocation,
			1 AS emoticonbar FROM #__smilies";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}
	*/

	/**
	 * Count total number of avatar galleries to be exported
	 */
	public function countAvatarGalleries() {
		return count($this->getAvatarGalleries());
	}

	/**
	 * Export avatar galleries
	 * 
	 * Returns list of folder=>fullpath to be copied, where fullpath points
	 * to the directory in the filesystem.
	 * 
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportAvatarGalleries($start = 0, $limit = 0) {
		$galleries = $this->getAvatarGalleries();
		return array_slice($galleries, $start, $limit);
	}

	/**
	 * Internal function to fetch all avatar galleries
	 * 
	 * @return array (folder=>full path, ...)
	 */
	protected function &getAvatarGalleries() {
		$config = $this->getConfig();
		static $galleries = false;
		if ($galleries === false) {
			$galleries = array();
			if (isset($config['avatar_gallery_path'])) {
				$path = "{$this->basepath}/{$config['avatar_gallery_path']->value}";
				$folders = JFolder::folders($path);
				foreach ($folders as $folder) {
					$galleries[$folder] = "{$path}/{$folder}";
				}
			}
		}
		return $galleries;
	}

	/**
	 * Count global configurations to be exported
	 * @return 1
	 */
	public function countConfig() {
		return 1;
	}

	/**
	 * Export global configuration
	 * 
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array (1=>(array(option=>value, ...)))
	 */
	public function &exportConfig($start = 0, $limit = 0) {
		$config = array ();
		if ($start)
			return $config;

		$result = $this->getConfig();
			
		// Time delta in seconds from UTC (=JFactory::getDate()->toUnix())
		$config['timedelta'] = JFactory::getDate()->toUnix() - time();

		$config['board_title'] = $result ['sitename']->value;
		$config['email'] = $result ['board_email']->value;
		$config['board_offline'] = $result ['board_disable']->value;
		$config['board_ofset'] = $result ['board_timezone']->value;
		// $config['offline_message'] = null;
		// $config['enablerss'] = null;
		// $config['enablepdf'] = null;
		$config['threads_per_page'] = $result ['topics_per_page']->value;
		$config['messages_per_page'] = $result ['posts_per_page']->value;
		// $config['messages_per_page_search'] = null;
		// $config['showhistory'] = null;
		// $config['historylimit'] = null;
		// $config['shownew'] = null;
		// $config['jmambot'] = null;
		$config['disemoticons'] = $result ['allow_smilies']->value ^ 1;
		// $config['template'] = null;
		// $config['showannouncement'] = null;
		// $config['avataroncat'] = null;
		// $config['catimagepath'] = null;
		// $config['showchildcaticon'] = null;
		// $config['annmodid'] = null;
		// $config['rtewidth'] = null;
		// $config['rteheight'] = null;
		// $config['enableforumjump'] = null;
		// $config['reportmsg'] = null;
		// $config['username'] = null;
		// $config['askemail'] = null;
		// $config['showemail'] = null;
		// $config['showuserstats'] = null;
		// $config['showkarma'] = null;
		// $config['useredit'] = null;
		// $config['useredittime'] = null;
		// $config['useredittimegrace'] = null;
		// $config['editmarkup'] = null;
		$config['allowsubscriptions'] = 1;
		// $config['subscriptionschecked'] = null;
		// $config['allowfavorites'] = null;
		// $config['maxsubject'] = null;
		$config['maxsig'] = $result ['allow_sig']->value ? $result ['max_sig_chars']->value : 0;
		// $config['regonly'] = null;
		$config['changename'] = $result ['allow_namechange']->value;
		// $config['pubwrite'] = null;
		$config['floodprotection'] = $result ['flood_interval']->value;
		// $config['mailmod'] = null;
		// $config['mailadmin'] = null;
		// $config['captcha'] = null;
		// $config['mailfull'] = null;
		$config['allowavatar'] = $result ['allow_avatar_upload']->value || $result ['allow_avatar_local']->value;
		$config['allowavatarupload'] = $result ['allow_avatar_upload']->value;
		$config['allowavatargallery'] = $result ['allow_avatar_local']->value;
		// $config['avatarquality'] = null;
		$config['avatarsize'] = ( int ) ($result ['avatar_filesize']->value / 1000);
		// $config['allowimageupload'] = null;
		// $config['allowimageregupload'] = null;
		// $config['imageheight'] = null;
		// $config['imagewidth'] = null;
		// $config['imagesize'] = null;
		// $config['allowfileupload'] = null;
		// $config['allowfileregupload'] = null;
		// $config['filetypes'] = null;
		// $config['filesize'] = null;
		// $config['showranking'] = null;
		// $config['rankimages'] = null;
		// $config['avatar_src'] = null;
		// $config['pm_component'] = null;
		// $config['discussbot'] = null;
		// $config['userlist_rows'] = null;
		// $config['userlist_online'] = null;
		// $config['userlist_avatar'] = null;
		// $config['userlist_name'] = null;
		// $config['userlist_username'] = null;
		// $config['userlist_posts'] = null;
		// $config['userlist_karma'] = null;
		// $config['userlist_email'] = null;
		// $config['userlist_usertype'] = null;
		// $config['userlist_joindate'] = null;
		// $config['userlist_lastvisitdate'] = null;
		// $config['userlist_userhits'] = null;
		// $config['latestcategory'] = null;
		// $config['showstats'] = null;
		// $config['showwhoisonline'] = null;
		// $config['showgenstats'] = null;
		// $config['showpopuserstats'] = null;
		// $config['popusercount'] = null;
		// $config['showpopsubjectstats'] = null;
		// $config['popsubjectcount'] = null;
		// $config['usernamechange'] = null;
		// $config['rules_infb'] = null;
		// $config['rules_cid'] = null;
		// $config['help_infb'] = null;
		// $config['help_cid'] = null;
		// $config['showspoilertag'] = null;
		// $config['showvideotag'] = null;
		// $config['showebaytag'] = null;
		// $config['trimlongurls'] = null;
		// $config['trimlongurlsfront'] = null;
		// $config['trimlongurlsback'] = null;
		// $config['autoembedyoutube'] = null;
		// $config['autoembedebay'] = null;
		// $config['ebaylanguagecode'] = null;
		$config['fbsessiontimeout'] = $result ['session_length']->value;
		// $config['highlightcode'] = null;
		// $config['rss_type'] = null;
		// $config['rss_timelimit'] = null;
		// $config['rss_limit'] = null;
		// $config['rss_included_categories'] = null;
		// $config['rss_excluded_categories'] = null;
		// $config['rss_specification'] = null;
		// $config['rss_allow_html'] = null;
		// $config['rss_author_format'] = null;
		// $config['rss_author_in_title'] = null;
		// $config['rss_word_count'] = null;
		// $config['rss_old_titles'] = null;
		// $config['rss_cache'] = null;
		$config['fbdefaultpage'] = 'categories';
		// $config['default_sort'] = null;
		// $config['alphauserpointsnumchars'] = null;
		// $config['sef'] = null;
		// $config['sefcats'] = null;
		// $config['sefutf8'] = null;
		// $config['showimgforguest'] = null;
		// $config['showfileforguest'] = null;
		// $config['pollnboptions'] = null;
		// $config['pollallowvoteone'] = null;
		$config['pollenabled'] = 1;
		// $config['poppollscount'] = null;
		// $config['showpoppollstats'] = null;
		// $config['polltimebtvotes'] = null;
		// $config['pollnbvotesbyuser'] = null;
		// $config['pollresultsuserslist'] = null;
		// $config['maxpersotext'] = null;
		// $config['ordering_system'] = null;
		// $config['post_dateformat'] = null;
		// $config['post_dateformat_hover'] = null;
		// $config['hide_ip'] = null;
		// $config['js_actstr_integration'] = null;
		// $config['imagetypes'] = null;
		// $config['checkmimetypes'] = null;
		// $config['imagemimetypes'] = null;
		// $config['imagequality'] = null;
		// $config['thumbheight'] = null;
		// $config['thumbwidth'] = null;
		// $config['hideuserprofileinfo'] = null;
		// $config['integration_access'] = null;
		// $config['integration_login'] = null;
		// $config['integration_avatar'] = null;
		// $config['integration_profile'] = null;
		// $config['integration_private'] = null;
		// $config['integration_activity'] = null;
		// $config['boxghostmessage'] = null;
		// $config['userdeletetmessage'] = null;
		// $config['latestcategory_in'] = null;
		// $config['topicicons'] = null;
		// $config['onlineusers'] = null;
		// $config['debug'] = null;
		// $config['catsautosubscribed'] = null;
		// $config['showbannedreason'] = null;
		// $config['version_check'] = null;
		// $config['showthankyou'] = null;
		// $config['showpopthankyoustats'] = null;
		// $config['popthankscount'] = null;
		// $config['mod_see_deleted'] = null;
		// $config['bbcode_img_secure'] = null;
		// $config['listcat_show_moderators'] = null;
		// $config['lightbox'] = null;
		// $config['activity_limit'] = null;
		// $config['show_list_time'] = null;
		// $config['show_session_type'] = null;
		// $config['show_session_starttime'] = null;
		// $config['userlist_allowed'] = null;
		// $config['userlist_count_users'] = null;
		// $config['enable_threaded_layouts'] = null;
		// $config['category_subscriptions'] = null;
		// $config['topic_subscriptions'] = null;
		// $config['pubprofile'] = null;
		// $config['thankyou_max'] = null;
		$result = array ('1' => $config );
		return $result;
	}
}