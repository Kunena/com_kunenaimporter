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

// Import Joomla! libraries
jimport ( 'joomla.application.component.model' );
jimport ( 'joomla.application.application' );

require_once (JPATH_COMPONENT . '/models/export.php');

class KunenaimporterModelExport_phpBB3 extends KunenaimporterModelExport {
	var $version;
	var $auth_method;
	var $rokbridge = null;
	protected $relpath = null;
	protected $basepath = null;

	public function __construct() {
		global $phpbb_root_path, $phpEx;

		// Get component parameters
		$this->params = getKunenaImporterParams();

		// Load rokBridge configuration (if exists)
		$this->rokbridge = JComponentHelper::getParams( 'com_rokbridge' );
		if (!$this->params->get('path')) {
			// Get phpBB3 path from rokBridge
			$this->params->set('path', $this->rokbridge->get('phpbb3_path'));
		}

		$this->relpath = $this->params->get('path');
		$this->basepath = JPATH_ROOT."/{$this->relpath}";

		if (JFile::exists("{$this->basepath}/config.php")) {
			//Include the phpBB3 configuration
			require "{$this->basepath}/config.php";
		}

		if (isset($dbms, $dbhost, $dbuser, $dbpasswd, $dbname, $table_prefix)) {
			// Initialize database object
			$options = array('driver' => $dbms, 'host' => $dbhost, 'user' => $dbuser, 'password' => $dbpasswd, 'database' => $dbname, 'prefix' => $table_prefix);
			$this->ext_database = JDatabase::getInstance($options);
		}

		if(!defined('IN_PHPBB')) {
			define('IN_PHPBB', true);
		}

		if(!defined('STRIP')) {
			define('STRIP', (get_magic_quotes_gpc()) ? true : false);
		}

		$phpbb_root_path = $this->basepath.'/';
		$phpEx = substr(strrchr(__FILE__, '.'), 1);

		parent::__construct ();
	}

	protected function getConfig() {
		if (empty($this->config)) {
			// Check if database settings are correct
			$query = "SELECT config_name, config_value AS value FROM #__config";
			$this->ext_database->setQuery ( $query );
			$this->config = $this->ext_database->loadObjectList ('config_name');
		}
		return $this->config;
	}

	public function checkConfig() {
		// Check Kunena compatibility
		parent::checkConfig ();
		if (JError::isError ( $this->ext_database ))
			return;

		// Check RokBridge
		if ($this->rokbridge->get('phpbb3_path')) {
			$this->addMessage ( '<div>RokBridge: <b style="color:green">detected</b></div>' );
		}

		if (is_dir($this->basepath)) {
			$this->addMessage ( '<div>phpBB path: <b style="color:green">' . $this->relpath . '</b></div>' );
		} else {
			$this->error = "phpBB3 not found from {$this->basepath}";
			$this->addMessage ( '<div>phpBB path: <b style="color:red">' . $this->relpath . '</b></div>' );
			$this->addMessage ( '<div><b>Error:</b> ' . $this->error . '</div>' );
			return false;
		}

		// Check if database settings are correct
		$config = $this->getConfig();
		if (empty($config['version'])) {
			$this->error = $this->ext_database->getErrorMsg ();
			if (! $this->error)
				$this->error = 'Configuration information missing: phpBB version not found';
		}
		if ($this->error) {
			$this->addMessage ( '<div>phpBB version: <b style="color:red">FAILED</b></div>' );
			return false;
		}

		// Check version number
		$this->version = $config['version']->value;
		if ($this->version [0] == '.')
			$this->version = '2' . $this->version;
		$version = explode ( '.', $this->version, 3 );
		if ($version [0] != 3 || $version [1] != 0)
			$this->error = "Unsupported forum: phpBB {$this->version}";
		if ($this->error) {
			$this->addMessage ( '<div>phpBB version: <b style="color:red">' . $this->version . '</b></div>' );
			$this->addMessage ( '<div><b>Error:</b> ' . $this->error . '</div>' );
			return false;
		}
		$this->addMessage ( '<div>phpBB version: <b style="color:green">' . $this->version . '</b></div>' );

		// Check authentication method
		$query = "SELECT config_value FROM #__config WHERE config_name='auth_method'";
		$this->ext_database->setQuery ( $query );
		$this->auth_method = $this->ext_database->loadResult () or die ( "<br />Invalid query:<br />$query<br />" . $this->ext_database->errorMsg () );
		$this->addMessage ( '<div>phpBB authentication method: <b style="color:green">' . $this->auth_method . '</b></div>' );

		// Find out which field is used as username
		$fields = $this->ext_database->getTableFields('#__users');
		$this->login_field = isset($fields['#__users']['login_name']);
	}

	public function getAuthMethod() {
		return $this->auth_method;
	}

	public function buildImportOps() {
		// query: (select, from, where, groupby), functions: (count, export)
		$importOps = array ();
		$importOps ['users'] = array ('count' => 'countUsers', 'export' => 'exportUsers' );
		$importOps ['mapusers'] = array ('count' => 'countMapUsers', 'export' => 'exportMapUsers' );
		$importOps ['categories'] = array ('count' => 'countCategories', 'export' => 'exportCategories' );
		$importOps ['config'] = array ('count' => 'countConfig', 'export' => 'exportConfig' );
		$importOps ['messages'] = array ('count' => 'countMessages', 'export' => 'exportMessages' );
		$importOps ['attachments'] = array ('count' => 'countAttachments', 'export' => 'exportAttachments' );
		$importOps ['sessions'] = array ('count' => 'countSessions', 'export' => 'exportSessions' );
		$importOps ['subscriptions'] = array ('count' => 'countSubscriptions', 'export' => 'exportSubscriptions' );
		$importOps ['userprofile'] = array ('count' => 'countUserProfile', 'export' => 'exportUserProfile' );
		$importOps ['avatargalleries'] = array ('count' => 'countAvatarGalleries', 'export' => 'exportAvatarGalleries' );
		$this->importOps = $importOps;
	}

	public function countCategories() {
		$query = "SELECT COUNT(*) FROM #__forums";
		return $this->getCount ( $query );
	}

	public function &exportCategories($start = 0, $limit = 0) {
		$query = "SELECT
			forum_id AS id,
			parent_id AS parent,
			forum_name AS name,
			0 AS cat_emoticon,
			(forum_status=1) AS locked,
			0 AS alert_admin,
			1 AS moderated,
			NULL AS moderators,
			0 AS pub_access,
			1 AS pub_recurse,
			0 AS admin_access,
			1 AS admin_recurse,
			left_id AS ordering,
			0 AS future2,
			1 AS published,
			0 AS checked_out,
			'0000-00-00 00:00:00' AS checked_out_time,
			0 AS review,
			0 AS allow_anonymous,
			0 as post_anonymous,
			0 AS hits,
			forum_desc AS description,
			'' AS headerdesc,
			'' AS class_sfx,
			0 AS allow_polls,
			forum_last_post_id AS id_last_msg,
			forum_posts AS numPosts,
			forum_topics AS numTopics,
			forum_last_post_time AS time_last_msg
		FROM #__forums ORDER BY id";
		$result = $this->getExportData ( $query, $start, $limit, 'id' );
		foreach ( $result as &$row ) {
			$row->name = $this->prep ( $row->name );
			$row->description = $this->prep ( $row->description );
		}
		return $result;
	}

	public function countConfig() {
		return 1;
	}

	public function &exportConfig($start = 0, $limit = 0) {
		$config = array ();
		if ($start)
			return $config;

		$query = "SELECT config_name, config_value AS value FROM #__config";
		$result = $this->getExportData ( $query, 0, 1000, 'config_name' );

		if (! $result)
			return $config;

		$config ['id'] = 1; // $result['config_id']->value;
		$config ['board_title'] = $result ['sitename']->value;
		$config ['email'] = $result ['board_email']->value;
		$config ['board_offline'] = $result ['board_disable']->value;
		$config ['board_ofset'] = $result ['board_timezone']->value;
		// $config['offline_message'] = null;
		// $config['default_view'] = null;
		// $config['enablerss'] = null;
		// $config['enablepdf'] = null;
		$config ['threads_per_page'] = $result ['topics_per_page']->value;
		$config ['messages_per_page'] = $result ['posts_per_page']->value;
		// $config['messages_per_page_search'] = null;
		// $config['showhistory'] = null;
		// $config['historylimit'] = null;
		// $config['shownew'] = null;
		// $config['newchar'] = null;
		// $config['jmambot'] = null;
		$config ['disemoticons'] = $result ['allow_smilies']->value ^ 1;
		// $config['template'] = null;
		// $config['templateimagepath'] = null;
		// $config['joomlastyle'] = null;
		// $config['showannouncement'] = null;
		// $config['avataroncat'] = null;
		// $config['catimagepath'] = null;
		// $config['numchildcolumn'] = null;
		// $config['showchildcaticon'] = null;
		// $config['annmodid'] = null;
		// $config['rtewidth'] = null;
		// $config['rteheight'] = null;
		// $config['enablerulespage'] = null;
		// $config['enableforumjump'] = null;
		// $config['reportmsg'] = null;
		// $config['username'] = null;
		// $config['askemail'] = null;
		// $config['showemail'] = null;
		// $config['showuserstats'] = null;
		// $config['poststats'] = null;
		// $config['statscolor'] = null;
		// $config['showkarma'] = null;
		// $config['useredit'] = null;
		// $config['useredittime'] = null;
		// $config['useredittimegrace'] = null;
		// $config['editmarkup'] = null;
		$config ['allowsubscriptions'] = $result ['allow_topic_notify']->value;
		// $config['subscriptionschecked'] = null;
		// $config['allowfavorites'] = null;
		// $config['wrap'] = null;
		// $config['maxsubject'] = null;
		$config ['maxsig'] = $result ['allow_sig']->value ? $result ['max_sig_chars']->value : 0;
		// $config['regonly'] = null;
		$config ['changename'] = $result ['allow_namechange']->value;
		// $config['pubwrite'] = null;
		$config ['floodprotection'] = $result ['flood_interval']->value;
		// $config['mailmod'] = null;
		// $config['mailadmin'] = null;
		// $config['captcha'] = null;
		// $config['mailfull'] = null;
		$config ['allowavatar'] = $result ['allow_avatar_upload']->value || $result ['allow_avatar_local']->value;
		$config ['allowavatarupload'] = $result ['allow_avatar_upload']->value;
		$config ['allowavatargallery'] = $result ['allow_avatar_local']->value;
		// $config['imageprocessor'] = null;
		$config ['avatarsmallheight'] = $result ['avatar_max_height']->value > 50 ? 50 : $result ['avatar_max_height']->value;
		$config ['avatarsmallwidth'] = $result ['avatar_max_width']->value > 50 ? 50 : $result ['avatar_max_width']->value;
		$config ['avatarheight'] = $result ['avatar_max_height']->value > 100 ? 100 : $result ['avatar_max_height']->value;
		$config ['avatarwidth'] = $result ['avatar_max_width']->value > 100 ? 100 : $result ['avatar_max_width']->value;
		$config ['avatarlargeheight'] = $result ['avatar_max_height']->value;
		$config ['avatarlargewidth'] = $result ['avatar_max_width']->value;
		// $config['avatarquality'] = null;
		$config ['avatarsize'] = ( int ) ($result ['avatar_filesize']->value / 1000);
		// $config['allowimageupload'] = null;
		// $config['allowimageregupload'] = null;
		// $config['imageheight'] = null;
		// $config['imagewidth'] = null;
		// $config['imagesize'] = null;
		// $config['allowfileupload'] = null;
		// $config['allowfileregupload'] = null;
		// $config['filetypes'] = null;
		$config ['filesize'] = ( int ) ($result ['max_filesize']->value / 1000);
		// $config['showranking'] = null;
		// $config['rankimages'] = null;
		// $config['avatar_src'] = null;
		// $config['fb_profile'] = null;
		// $config['pm_component'] = null;
		// $config['discussbot'] = null;
		// $config['userlist_rows'] = null;
		// $config['userlist_online'] = null;
		// $config['userlist_avatar'] = null;
		// $config['userlist_name'] = null;
		// $config['userlist_username'] = null;
		// $config['userlist_group'] = null;
		// $config['userlist_posts'] = null;
		// $config['userlist_karma'] = null;
		// $config['userlist_email'] = null;
		// $config['userlist_usertype'] = null;
		// $config['userlist_joindate'] = null;
		// $config['userlist_lastvisitdate'] = null;
		// $config['userlist_userhits'] = null;
		// $config['showlatest'] = null;
		// $config['latestcount'] = null;
		// $config['latestcountperpage'] = null;
		// $config['latestcategory'] = null;
		// $config['latestsinglesubject'] = null;
		// $config['latestreplysubject'] = null;
		// $config['latestsubjectlength'] = null;
		// $config['latestshowdate'] = null;
		// $config['latestshowhits'] = null;
		// $config['latestshowauthor'] = null;
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
		// $config['rules_link'] = null;
		// $config['enablehelppage'] = null;
		// $config['help_infb'] = null;
		// $config['help_cid'] = null;
		// $config['help_link'] = null;
		// $config['showspoilertag'] = null;
		// $config['showvideotag'] = null;
		// $config['showebaytag'] = null;
		// $config['trimlongurls'] = null;
		// $config['trimlongurlsfront'] = null;
		// $config['trimlongurlsback'] = null;
		// $config['autoembedyoutube'] = null;
		// $config['autoembedebay'] = null;
		// $config['ebaylanguagecode'] = null;
		$config ['fbsessiontimeout'] = $result ['session_length']->value;
		// $config['highlightcode'] = null;
		// $config['rsstype'] = null;
		// $config['rsshistory'] = null;
		$config ['fbdefaultpage'] = 'categories';
		// $config['default_sort'] = null;
		$result = array ('1' => $config );
		return $result;
	}

	public function countMessages() {
		$query = "SELECT COUNT(*) FROM #__posts";
		return $this->getCount ( $query );
	}

	public function &exportMessages($start = 0, $limit = 0) {
		$query = "SELECT
			p.post_id AS id,
			IF(p.post_id=t.topic_first_post_id,0,t.topic_first_post_id) AS parent,
			t.topic_first_post_id AS thread,
			t.forum_id AS catid,
			IF(p.post_username, p.post_username, u.username) AS name,
			p.poster_id AS userid,
			u.user_email AS email,
			IF(p.post_subject, p.post_subject, t.topic_title) AS subject,
			p.post_time AS time,
			p.poster_ip AS ip,
			0 AS topic_emoticon,
			(t.topic_status=1 AND p.post_id=t.topic_first_post_id) AS locked,
			((p.post_approved+1)%2) AS hold,
			(t.topic_type>0 AND p.post_id=t.topic_first_post_id) AS ordering,
			t.topic_views AS hits,
			(t.topic_moved_id>0) AS moved,
			p.post_edit_user AS modified_by,
			p.post_edit_time AS modified_time,
			p.post_edit_reason AS modified_reason,
			p.post_text AS message
		FROM #__posts AS p
		LEFT JOIN #__topics AS t ON p.topic_id = t.topic_id
		LEFT JOIN #__users AS u ON p.poster_id = u.user_id
		ORDER BY p.post_id";
		$result = $this->getExportData ( $query, $start, $limit, 'id' );

		foreach ( $result as &$row ) {
			$row->name = $this->prep ( $row->name );
			$row->email = $this->prep ( $row->email );
			$row->subject = $this->prep ( $row->subject );
			if (! $row->modified_time)
				$row->modified_by = 0;
			$row->modified_reason = $this->prep ( $row->modified_reason );
			$row->message = $this->prep ( $row->message );
		}
		return $result;
	}

	public function countAttachments() {
		$query = "SELECT COUNT(*) FROM #__attachments";
		return $this->getCount ( $query );
	}
	public function &exportAttachments($start = 0, $limit = 0) {
		$query = "SELECT
			attach_id AS id,
			post_msg_id AS mesid,
			poster_id AS userid,
			NULL AS hash,
			filesize AS size,
			'phpbb3' AS folder,
			IF(LENGTH(mimetype)>0,mimetype,extension) AS filetype,
			real_filename AS filename,
			physical_filename AS location
		FROM `#__attachments`
		ORDER BY attach_id";
		$result = $this->getExportData ( $query, $start, $limit, 'id' );
		foreach ( $result as &$row ) {
			$row->location = "{$this->basepath}/files/{$row->location}";
		}
		return $result;
	}

	public function countSessions() {
		$query = "SELECT COUNT(*) FROM #__users AS u WHERE user_lastvisit>0";
		return $this->getCount ( $query );
	}
	public function &exportSessions($start = 0, $limit = 0) {
		$query = "SELECT
			user_id AS userid,
			NULL AS allowed,
			user_lastmark AS lasttime,
			'' AS readtopics,
			user_lastvisit AS currvisit
		FROM #__users
		WHERE user_lastvisit>0";
		$result = $this->getExportData ( $query, $start, $limit );

		foreach ( $result as $key => &$row ) {
			$row->lasttime = date ( "Y-m-d H:i:s", $row->lasttime );
			$row->currvisit = date ( "Y-m-d H:i:s", $row->currvisit );
		}
		return $result;
	}

	public function countSubscriptions() {
		$query = "SELECT COUNT(*) FROM #__topics_watch";
		return $this->getCount ( $query );
	}
	public function &exportSubscriptions($start = 0, $limit = 0) {
		$query = "SELECT
			t.topic_first_post_id AS thread,
			w.user_id AS userid,
			0 AS future1
		FROM #__topics_watch AS w
		LEFT JOIN #__topics AS t ON w.topic_id=t.topic_id";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}

	public function countUserProfile() {
		$query = "SELECT COUNT(*) FROM #__users AS u WHERE user_id > 0 AND u.user_type != 2";
		return $this->getCount ( $query );
	}

	public function &exportUserProfile($start = 0, $limit = 0) {
		$query = "SELECT
			u.user_id AS userid,
			'flat' AS view,
			u.user_sig AS signature,
			0 AS moderator,
			NULL AS banned,
			0 AS ordering,
			u.user_posts AS posts,
			user_avatar AS avatar,
			0 AS karma,
			0 AS karma_time,
			1 AS group_id,
			0 AS uhits,
			NULL AS personalText,
			0 AS gender,
			u.user_birthday AS birthdate,
			u.user_from AS location,
			u.user_icq AS ICQ,
			u.user_aim AS AIM,
			u.user_yim AS YIM,
			u.user_msnm AS MSN,
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
			NULL AS websitename,
			u.user_website AS websiteurl,
			0 AS rank,
			1 AS hideEmail,
			1 AS showOnline,
			user_avatar_type AS avatartype
		FROM #__users AS u
		WHERE u.user_id > 0 AND u.user_type != 2
		ORDER BY u.user_id";
		$result = $this->getExportData ( $query, $start, $limit, 'userid' );

		$path = $this->config['avatar_path']->value;
		$salt = $this->config['avatar_salt']->value;
		foreach ( $result as &$row ) {
			// Convert bbcode in signature
			if ($row->avatar) {
				switch ($row->avatartype) {
					case 1:
						// Uploaded
						$filename = (int) $row->avatar;
						$ext = substr(strrchr($row->avatar, '.'), 1);
						$row->avatar = "users/{$row->avatar}";
						$row->avatarpath = "{$this->basepath}/{$path}/{$salt}_{$filename}.{$ext}";
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
			$row->signature = $this->prep ( $row->signature );
			$row->location = $this->prep ( $row->location );
		}
		return $result;
	}

	public function countUsers() {
		$query = "SELECT COUNT(*) FROM #__users AS u WHERE user_id > 0 AND u.user_type != 2";
		return $this->getCount ( $query );
	}

	public function &exportUsers($start = 0, $limit = 0) {
		$username = $this->login_field ? 'login_name' : 'username';
		$query = "SELECT
			u.user_id AS extid,
			u.{$username} AS extusername,
			u.username AS name,
			u.{$username} AS username,
			u.user_email AS email,
			u.user_password AS password,
			IF(u.user_type=3, 'Administrator', 'Registered') AS usertype,
			IF(b.ban_userid, 1, 0) AS block,
			0 AS gid,
			FROM_UNIXTIME(u.user_regdate) AS registerDate,
			IF(u.user_lastvisit>0, FROM_UNIXTIME(u.user_lastvisit), '0000-00-00 00:00:00') AS lastvisitDate,
			NULL AS params,
			u.user_pass_convert AS password_phpbb2
		FROM #__users AS u
		LEFT JOIN #__banlist AS b ON u.user_id = b.ban_userid
		WHERE user_id > 0 AND u.user_type != 2
		GROUP BY u.user_id
		ORDER BY u.user_id";
		$result = $this->getExportData ( $query, $start, $limit, 'extid' );
		foreach ( $result as &$row ) {
			$row->name = html_entity_decode ( $row->name );
			$row->username = html_entity_decode ( $row->username );
			// Password hash check is described in phpBB3/includes/functions.php: phpbb_check_hash(),
			// _hash_crypt_private() and _hash_encode64() if we want to add plugin for phpBB3 authentication.
			// It works for all phpBB3 passwords, but phpBB2 passwords may need some extra work, which is
			// described in phpBB3/includes/auth/auth_db.php. Basically phpBB2 passwords are encoded by using
			// md5(utf8_to_cp1252(addslashes($password))).
			if ($row->password_phpbb2) {
				$row->password = 'phpbb2::'.$row->password;
			} else {
				$row->password = 'phpbb3::'.$row->password;
			}
		}
		return $result;
	}

	protected function &getAvatarGalleries() {
		static $galleries = false;
		if ($galleries === false) {
			$path = "{$this->basepath}/{$this->config['avatar_gallery_path']->value}";
			$galleries = array();
			$folders = JFolder::folders($path);
			foreach ($folders as $folder) {
				$galleries[$folder] = "{$path}/{$folder}";
			}
		}
		return $galleries;
	}
	public function countAvatarGalleries() {
		return count($this->getAvatarGalleries());
	}
	public function &exportAvatarGalleries($start = 0, $limit = 0) {
		$galleries = $this->getAvatarGalleries();
		return array_slice($galleries, $start, $limit);
	}

	public function mapJoomlaUser($joomlauser) {
		if ($this->login_field) {
			// Use login_name created by SMF to phpBB3 convertor
			$field = 'login_name';
			$username = $joomlauser->username;
		} else {
			$field = 'username_clean';
			$username = utf8_clean_string($joomlauser->username);
		}
		$query = "SELECT user_id
			FROM #__users WHERE {$field}={$this->ext_database->Quote($username)}";

		$this->ext_database->setQuery( $query );
		$result = intval($this->ext_database->loadResult());
		return $result;
	}

	//--- Function to prepare strings for MySQL storage ---/
	protected function prep($s) {
		// Parse out the $uid things that fuck up bbcode

		$s = preg_replace ( '/\&lt;/', '<', $s );
		$s = preg_replace ( '/\&gt;/', '>', $s );
		$s = preg_replace ( '/\&quot;/', '"', $s );
		$s = preg_replace ( '/\&amp;/', '&', $s );
		$s = preg_replace ( '/\&nbsp;/', ' ', $s );

		$s = preg_replace ( '/\&#39;/', "'", $s );
		$s = preg_replace ( '/\&#40;/', '(', $s );
		$s = preg_replace ( '/\&#41;/', ')', $s );
		$s = preg_replace ( '/\&#46;/', '.', $s );
		$s = preg_replace ( '/\&#58;/', ':', $s );
		$s = preg_replace ( '/\&#123;/', '{', $s );
		$s = preg_replace ( '/\&#125;/', '}', $s );

		// <strong> </strong>
		$s = preg_replace ( '/\[b:(.*?)\]/', '[b]', $s );
		$s = preg_replace ( '/\[\/b:(.*?)\]/', '[/b]', $s );

		// <em> </em>
		$s = preg_replace ( '/\[i:(.*?)\]/', '[i]', $s );
		$s = preg_replace ( '/\[\/i:(.*?)\]/', '[/i]', $s );

		// <u> </u>
		$s = preg_replace ( '/\[u:(.*?)\]/', '[u]', $s );
		$s = preg_replace ( '/\[\/u:(.*?)\]/', '[/u]', $s );

		// quote
		$s = preg_replace ( '/\[quote:(.*?)\]/', '[quote]', $s );
		$s = preg_replace ( '/\[quote(:(.*?))?="(.*?)"\]/', '[b]\\3[/b]\n[quote]', $s );
		$s = preg_replace ( '/\[\/quote:(.*?)\]/', '[/quote]', $s );

		// image
		#$s = preg_replace('/\[img:(.*?)="(.*?)"\]/', '[img="\\2"]', $s);
		$s = preg_replace ( '/\[img:(.*?)\](.*?)\[\/img:(.*?)\]/si', '[img]\\2[/img]', $s );

		// color
		$s = preg_replace ( '/\[color=(.*?):(.*?)\]/', '[color=\\1]', $s );
		$s = preg_replace ( '/\[\/color:(.*?)\]/', '[/color]', $s );

		// size
		$s = preg_replace ( '/\[size=\d:(.*?)\]/', '[size=1]', $s );
		$s = preg_replace ( '/\[size=1[0123]:(.*?)\]/', '[size=2]', $s );
		$s = preg_replace ( '/\[size=1[4567]:(.*?)\]/', '[size=3]', $s );
		$s = preg_replace ( '/\[size=((1[89])|(2[01])):(.*?)\]/', '[size=4]', $s );
		$s = preg_replace ( '/\[size=2[234567]:(.*?)\]/', '[size=5]', $s );
		$s = preg_replace ( '/\[size=((2[89])|(3[01])):(.*?)\]/', '[size=6]', $s );
		$s = preg_replace ( '/\[size=3[2-9]:(.*?)\]/', '[size=7]', $s );
		$s = preg_replace ( '/\[\/size:(.*?)\]/', '[/size]', $s );

		// code
		// $s = preg_replace('/\[code:(.*?):(.*?)\]/',    '[code:\\1]', $s);
		// $s = preg_replace('/\[\/code:(.*?):(.*?)\]/', '[/code:\\1]', $s);


		// $s = preg_replace('/\[code:(.*?):(.*?)\]/',    '[code]', $s);
		// $s = preg_replace('/\[\/code:(.*?):(.*?)\]/', '[/code]', $s);


		$s = preg_replace ( '/\[code:(.*?)]/', '[code]', $s );
		$s = preg_replace ( '/\[\/code:(.*?)]/', '[/code]', $s );

		// lists
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

		$s = preg_replace ( '/\[url:(.*?)]/', '[url]', $s );
		$s = preg_replace ( '/\[\/url:(.*?)]/', '[/url]', $s );

		$s = preg_replace ( '/\<\/a>/', '', $s );

		// $s = preg_replace('/\\\\/', '', $s);

		return $s;
	}
}

if (!function_exists('utf8_clean_string')) {
	/**
	* This function is used to generate a "clean" version of a string.
	* Clean means that it is a case insensitive form (case folding) and that it is normalized (NFC).
	* Additionally a homographs of one character are transformed into one specific character (preferably ASCII
	* if it is an ASCII character).
	*
	* Please be aware that if you change something within this function or within
	* functions used here you need to rebuild/update the username_clean column in the users table. And all other
	* columns that store a clean string otherwise you will break this functionality.
	*
	* @param	string	$text	An unclean string, mabye user input (has to be valid UTF-8!)
	* @return	string			Cleaned up version of the input string
	*/
	function utf8_clean_string($text)
	{
		global $phpbb_root_path, $phpEx;

		static $homographs = array();
		if (empty($homographs)) {
			$homographs = include($phpbb_root_path . 'includes/utf/data/confusables.' . $phpEx);
		}

		$text = utf8_case_fold_nfkc($text);
		$text = strtr($text, $homographs);
		// Other control characters
		$text = preg_replace('#(?:[\x00-\x1F\x7F]+|(?:\xC2[\x80-\x9F])+)#', '', $text);

		// we can use trim here as all the other space characters should have been turned
		// into normal ASCII spaces by now
		return trim($text);
	}
}


if (!function_exists('utf8_case_fold_nfkc')) {
	/**
	* Takes the input and does a "special" case fold. It does minor normalization
	* and returns NFKC compatable text
	*
	* @param	string	$text	text to be case folded
	* @param	string	$option	determines how we will fold the cases
	* @return	string			case folded text
	*/
	function utf8_case_fold_nfkc($text, $option = 'full')
	{
		static $fc_nfkc_closure = array(
			"\xCD\xBA"	=> "\x20\xCE\xB9",
			"\xCF\x92"	=> "\xCF\x85",
			"\xCF\x93"	=> "\xCF\x8D",
			"\xCF\x94"	=> "\xCF\x8B",
			"\xCF\xB2"	=> "\xCF\x83",
			"\xCF\xB9"	=> "\xCF\x83",
			"\xE1\xB4\xAC"	=> "\x61",
			"\xE1\xB4\xAD"	=> "\xC3\xA6",
			"\xE1\xB4\xAE"	=> "\x62",
			"\xE1\xB4\xB0"	=> "\x64",
			"\xE1\xB4\xB1"	=> "\x65",
			"\xE1\xB4\xB2"	=> "\xC7\x9D",
			"\xE1\xB4\xB3"	=> "\x67",
			"\xE1\xB4\xB4"	=> "\x68",
			"\xE1\xB4\xB5"	=> "\x69",
			"\xE1\xB4\xB6"	=> "\x6A",
			"\xE1\xB4\xB7"	=> "\x6B",
			"\xE1\xB4\xB8"	=> "\x6C",
			"\xE1\xB4\xB9"	=> "\x6D",
			"\xE1\xB4\xBA"	=> "\x6E",
			"\xE1\xB4\xBC"	=> "\x6F",
			"\xE1\xB4\xBD"	=> "\xC8\xA3",
			"\xE1\xB4\xBE"	=> "\x70",
			"\xE1\xB4\xBF"	=> "\x72",
			"\xE1\xB5\x80"	=> "\x74",
			"\xE1\xB5\x81"	=> "\x75",
			"\xE1\xB5\x82"	=> "\x77",
			"\xE2\x82\xA8"	=> "\x72\x73",
			"\xE2\x84\x82"	=> "\x63",
			"\xE2\x84\x83"	=> "\xC2\xB0\x63",
			"\xE2\x84\x87"	=> "\xC9\x9B",
			"\xE2\x84\x89"	=> "\xC2\xB0\x66",
			"\xE2\x84\x8B"	=> "\x68",
			"\xE2\x84\x8C"	=> "\x68",
			"\xE2\x84\x8D"	=> "\x68",
			"\xE2\x84\x90"	=> "\x69",
			"\xE2\x84\x91"	=> "\x69",
			"\xE2\x84\x92"	=> "\x6C",
			"\xE2\x84\x95"	=> "\x6E",
			"\xE2\x84\x96"	=> "\x6E\x6F",
			"\xE2\x84\x99"	=> "\x70",
			"\xE2\x84\x9A"	=> "\x71",
			"\xE2\x84\x9B"	=> "\x72",
			"\xE2\x84\x9C"	=> "\x72",
			"\xE2\x84\x9D"	=> "\x72",
			"\xE2\x84\xA0"	=> "\x73\x6D",
			"\xE2\x84\xA1"	=> "\x74\x65\x6C",
			"\xE2\x84\xA2"	=> "\x74\x6D",
			"\xE2\x84\xA4"	=> "\x7A",
			"\xE2\x84\xA8"	=> "\x7A",
			"\xE2\x84\xAC"	=> "\x62",
			"\xE2\x84\xAD"	=> "\x63",
			"\xE2\x84\xB0"	=> "\x65",
			"\xE2\x84\xB1"	=> "\x66",
			"\xE2\x84\xB3"	=> "\x6D",
			"\xE2\x84\xBB"	=> "\x66\x61\x78",
			"\xE2\x84\xBE"	=> "\xCE\xB3",
			"\xE2\x84\xBF"	=> "\xCF\x80",
			"\xE2\x85\x85"	=> "\x64",
			"\xE3\x89\x90"	=> "\x70\x74\x65",
			"\xE3\x8B\x8C"	=> "\x68\x67",
			"\xE3\x8B\x8E"	=> "\x65\x76",
			"\xE3\x8B\x8F"	=> "\x6C\x74\x64",
			"\xE3\x8D\xB1"	=> "\x68\x70\x61",
			"\xE3\x8D\xB3"	=> "\x61\x75",
			"\xE3\x8D\xB5"	=> "\x6F\x76",
			"\xE3\x8D\xBA"	=> "\x69\x75",
			"\xE3\x8E\x80"	=> "\x70\x61",
			"\xE3\x8E\x81"	=> "\x6E\x61",
			"\xE3\x8E\x82"	=> "\xCE\xBC\x61",
			"\xE3\x8E\x83"	=> "\x6D\x61",
			"\xE3\x8E\x84"	=> "\x6B\x61",
			"\xE3\x8E\x85"	=> "\x6B\x62",
			"\xE3\x8E\x86"	=> "\x6D\x62",
			"\xE3\x8E\x87"	=> "\x67\x62",
			"\xE3\x8E\x8A"	=> "\x70\x66",
			"\xE3\x8E\x8B"	=> "\x6E\x66",
			"\xE3\x8E\x8C"	=> "\xCE\xBC\x66",
			"\xE3\x8E\x90"	=> "\x68\x7A",
			"\xE3\x8E\x91"	=> "\x6B\x68\x7A",
			"\xE3\x8E\x92"	=> "\x6D\x68\x7A",
			"\xE3\x8E\x93"	=> "\x67\x68\x7A",
			"\xE3\x8E\x94"	=> "\x74\x68\x7A",
			"\xE3\x8E\xA9"	=> "\x70\x61",
			"\xE3\x8E\xAA"	=> "\x6B\x70\x61",
			"\xE3\x8E\xAB"	=> "\x6D\x70\x61",
			"\xE3\x8E\xAC"	=> "\x67\x70\x61",
			"\xE3\x8E\xB4"	=> "\x70\x76",
			"\xE3\x8E\xB5"	=> "\x6E\x76",
			"\xE3\x8E\xB6"	=> "\xCE\xBC\x76",
			"\xE3\x8E\xB7"	=> "\x6D\x76",
			"\xE3\x8E\xB8"	=> "\x6B\x76",
			"\xE3\x8E\xB9"	=> "\x6D\x76",
			"\xE3\x8E\xBA"	=> "\x70\x77",
			"\xE3\x8E\xBB"	=> "\x6E\x77",
			"\xE3\x8E\xBC"	=> "\xCE\xBC\x77",
			"\xE3\x8E\xBD"	=> "\x6D\x77",
			"\xE3\x8E\xBE"	=> "\x6B\x77",
			"\xE3\x8E\xBF"	=> "\x6D\x77",
			"\xE3\x8F\x80"	=> "\x6B\xCF\x89",
			"\xE3\x8F\x81"	=> "\x6D\xCF\x89",
			"\xE3\x8F\x83"	=> "\x62\x71",
			"\xE3\x8F\x86"	=> "\x63\xE2\x88\x95\x6B\x67",
			"\xE3\x8F\x87"	=> "\x63\x6F\x2E",
			"\xE3\x8F\x88"	=> "\x64\x62",
			"\xE3\x8F\x89"	=> "\x67\x79",
			"\xE3\x8F\x8B"	=> "\x68\x70",
			"\xE3\x8F\x8D"	=> "\x6B\x6B",
			"\xE3\x8F\x8E"	=> "\x6B\x6D",
			"\xE3\x8F\x97"	=> "\x70\x68",
			"\xE3\x8F\x99"	=> "\x70\x70\x6D",
			"\xE3\x8F\x9A"	=> "\x70\x72",
			"\xE3\x8F\x9C"	=> "\x73\x76",
			"\xE3\x8F\x9D"	=> "\x77\x62",
			"\xE3\x8F\x9E"	=> "\x76\xE2\x88\x95\x6D",
			"\xE3\x8F\x9F"	=> "\x61\xE2\x88\x95\x6D",
			"\xF0\x9D\x90\x80"	=> "\x61",
			"\xF0\x9D\x90\x81"	=> "\x62",
			"\xF0\x9D\x90\x82"	=> "\x63",
			"\xF0\x9D\x90\x83"	=> "\x64",
			"\xF0\x9D\x90\x84"	=> "\x65",
			"\xF0\x9D\x90\x85"	=> "\x66",
			"\xF0\x9D\x90\x86"	=> "\x67",
			"\xF0\x9D\x90\x87"	=> "\x68",
			"\xF0\x9D\x90\x88"	=> "\x69",
			"\xF0\x9D\x90\x89"	=> "\x6A",
			"\xF0\x9D\x90\x8A"	=> "\x6B",
			"\xF0\x9D\x90\x8B"	=> "\x6C",
			"\xF0\x9D\x90\x8C"	=> "\x6D",
			"\xF0\x9D\x90\x8D"	=> "\x6E",
			"\xF0\x9D\x90\x8E"	=> "\x6F",
			"\xF0\x9D\x90\x8F"	=> "\x70",
			"\xF0\x9D\x90\x90"	=> "\x71",
			"\xF0\x9D\x90\x91"	=> "\x72",
			"\xF0\x9D\x90\x92"	=> "\x73",
			"\xF0\x9D\x90\x93"	=> "\x74",
			"\xF0\x9D\x90\x94"	=> "\x75",
			"\xF0\x9D\x90\x95"	=> "\x76",
			"\xF0\x9D\x90\x96"	=> "\x77",
			"\xF0\x9D\x90\x97"	=> "\x78",
			"\xF0\x9D\x90\x98"	=> "\x79",
			"\xF0\x9D\x90\x99"	=> "\x7A",
			"\xF0\x9D\x90\xB4"	=> "\x61",
			"\xF0\x9D\x90\xB5"	=> "\x62",
			"\xF0\x9D\x90\xB6"	=> "\x63",
			"\xF0\x9D\x90\xB7"	=> "\x64",
			"\xF0\x9D\x90\xB8"	=> "\x65",
			"\xF0\x9D\x90\xB9"	=> "\x66",
			"\xF0\x9D\x90\xBA"	=> "\x67",
			"\xF0\x9D\x90\xBB"	=> "\x68",
			"\xF0\x9D\x90\xBC"	=> "\x69",
			"\xF0\x9D\x90\xBD"	=> "\x6A",
			"\xF0\x9D\x90\xBE"	=> "\x6B",
			"\xF0\x9D\x90\xBF"	=> "\x6C",
			"\xF0\x9D\x91\x80"	=> "\x6D",
			"\xF0\x9D\x91\x81"	=> "\x6E",
			"\xF0\x9D\x91\x82"	=> "\x6F",
			"\xF0\x9D\x91\x83"	=> "\x70",
			"\xF0\x9D\x91\x84"	=> "\x71",
			"\xF0\x9D\x91\x85"	=> "\x72",
			"\xF0\x9D\x91\x86"	=> "\x73",
			"\xF0\x9D\x91\x87"	=> "\x74",
			"\xF0\x9D\x91\x88"	=> "\x75",
			"\xF0\x9D\x91\x89"	=> "\x76",
			"\xF0\x9D\x91\x8A"	=> "\x77",
			"\xF0\x9D\x91\x8B"	=> "\x78",
			"\xF0\x9D\x91\x8C"	=> "\x79",
			"\xF0\x9D\x91\x8D"	=> "\x7A",
			"\xF0\x9D\x91\xA8"	=> "\x61",
			"\xF0\x9D\x91\xA9"	=> "\x62",
			"\xF0\x9D\x91\xAA"	=> "\x63",
			"\xF0\x9D\x91\xAB"	=> "\x64",
			"\xF0\x9D\x91\xAC"	=> "\x65",
			"\xF0\x9D\x91\xAD"	=> "\x66",
			"\xF0\x9D\x91\xAE"	=> "\x67",
			"\xF0\x9D\x91\xAF"	=> "\x68",
			"\xF0\x9D\x91\xB0"	=> "\x69",
			"\xF0\x9D\x91\xB1"	=> "\x6A",
			"\xF0\x9D\x91\xB2"	=> "\x6B",
			"\xF0\x9D\x91\xB3"	=> "\x6C",
			"\xF0\x9D\x91\xB4"	=> "\x6D",
			"\xF0\x9D\x91\xB5"	=> "\x6E",
			"\xF0\x9D\x91\xB6"	=> "\x6F",
			"\xF0\x9D\x91\xB7"	=> "\x70",
			"\xF0\x9D\x91\xB8"	=> "\x71",
			"\xF0\x9D\x91\xB9"	=> "\x72",
			"\xF0\x9D\x91\xBA"	=> "\x73",
			"\xF0\x9D\x91\xBB"	=> "\x74",
			"\xF0\x9D\x91\xBC"	=> "\x75",
			"\xF0\x9D\x91\xBD"	=> "\x76",
			"\xF0\x9D\x91\xBE"	=> "\x77",
			"\xF0\x9D\x91\xBF"	=> "\x78",
			"\xF0\x9D\x92\x80"	=> "\x79",
			"\xF0\x9D\x92\x81"	=> "\x7A",
			"\xF0\x9D\x92\x9C"	=> "\x61",
			"\xF0\x9D\x92\x9E"	=> "\x63",
			"\xF0\x9D\x92\x9F"	=> "\x64",
			"\xF0\x9D\x92\xA2"	=> "\x67",
			"\xF0\x9D\x92\xA5"	=> "\x6A",
			"\xF0\x9D\x92\xA6"	=> "\x6B",
			"\xF0\x9D\x92\xA9"	=> "\x6E",
			"\xF0\x9D\x92\xAA"	=> "\x6F",
			"\xF0\x9D\x92\xAB"	=> "\x70",
			"\xF0\x9D\x92\xAC"	=> "\x71",
			"\xF0\x9D\x92\xAE"	=> "\x73",
			"\xF0\x9D\x92\xAF"	=> "\x74",
			"\xF0\x9D\x92\xB0"	=> "\x75",
			"\xF0\x9D\x92\xB1"	=> "\x76",
			"\xF0\x9D\x92\xB2"	=> "\x77",
			"\xF0\x9D\x92\xB3"	=> "\x78",
			"\xF0\x9D\x92\xB4"	=> "\x79",
			"\xF0\x9D\x92\xB5"	=> "\x7A",
			"\xF0\x9D\x93\x90"	=> "\x61",
			"\xF0\x9D\x93\x91"	=> "\x62",
			"\xF0\x9D\x93\x92"	=> "\x63",
			"\xF0\x9D\x93\x93"	=> "\x64",
			"\xF0\x9D\x93\x94"	=> "\x65",
			"\xF0\x9D\x93\x95"	=> "\x66",
			"\xF0\x9D\x93\x96"	=> "\x67",
			"\xF0\x9D\x93\x97"	=> "\x68",
			"\xF0\x9D\x93\x98"	=> "\x69",
			"\xF0\x9D\x93\x99"	=> "\x6A",
			"\xF0\x9D\x93\x9A"	=> "\x6B",
			"\xF0\x9D\x93\x9B"	=> "\x6C",
			"\xF0\x9D\x93\x9C"	=> "\x6D",
			"\xF0\x9D\x93\x9D"	=> "\x6E",
			"\xF0\x9D\x93\x9E"	=> "\x6F",
			"\xF0\x9D\x93\x9F"	=> "\x70",
			"\xF0\x9D\x93\xA0"	=> "\x71",
			"\xF0\x9D\x93\xA1"	=> "\x72",
			"\xF0\x9D\x93\xA2"	=> "\x73",
			"\xF0\x9D\x93\xA3"	=> "\x74",
			"\xF0\x9D\x93\xA4"	=> "\x75",
			"\xF0\x9D\x93\xA5"	=> "\x76",
			"\xF0\x9D\x93\xA6"	=> "\x77",
			"\xF0\x9D\x93\xA7"	=> "\x78",
			"\xF0\x9D\x93\xA8"	=> "\x79",
			"\xF0\x9D\x93\xA9"	=> "\x7A",
			"\xF0\x9D\x94\x84"	=> "\x61",
			"\xF0\x9D\x94\x85"	=> "\x62",
			"\xF0\x9D\x94\x87"	=> "\x64",
			"\xF0\x9D\x94\x88"	=> "\x65",
			"\xF0\x9D\x94\x89"	=> "\x66",
			"\xF0\x9D\x94\x8A"	=> "\x67",
			"\xF0\x9D\x94\x8D"	=> "\x6A",
			"\xF0\x9D\x94\x8E"	=> "\x6B",
			"\xF0\x9D\x94\x8F"	=> "\x6C",
			"\xF0\x9D\x94\x90"	=> "\x6D",
			"\xF0\x9D\x94\x91"	=> "\x6E",
			"\xF0\x9D\x94\x92"	=> "\x6F",
			"\xF0\x9D\x94\x93"	=> "\x70",
			"\xF0\x9D\x94\x94"	=> "\x71",
			"\xF0\x9D\x94\x96"	=> "\x73",
			"\xF0\x9D\x94\x97"	=> "\x74",
			"\xF0\x9D\x94\x98"	=> "\x75",
			"\xF0\x9D\x94\x99"	=> "\x76",
			"\xF0\x9D\x94\x9A"	=> "\x77",
			"\xF0\x9D\x94\x9B"	=> "\x78",
			"\xF0\x9D\x94\x9C"	=> "\x79",
			"\xF0\x9D\x94\xB8"	=> "\x61",
			"\xF0\x9D\x94\xB9"	=> "\x62",
			"\xF0\x9D\x94\xBB"	=> "\x64",
			"\xF0\x9D\x94\xBC"	=> "\x65",
			"\xF0\x9D\x94\xBD"	=> "\x66",
			"\xF0\x9D\x94\xBE"	=> "\x67",
			"\xF0\x9D\x95\x80"	=> "\x69",
			"\xF0\x9D\x95\x81"	=> "\x6A",
			"\xF0\x9D\x95\x82"	=> "\x6B",
			"\xF0\x9D\x95\x83"	=> "\x6C",
			"\xF0\x9D\x95\x84"	=> "\x6D",
			"\xF0\x9D\x95\x86"	=> "\x6F",
			"\xF0\x9D\x95\x8A"	=> "\x73",
			"\xF0\x9D\x95\x8B"	=> "\x74",
			"\xF0\x9D\x95\x8C"	=> "\x75",
			"\xF0\x9D\x95\x8D"	=> "\x76",
			"\xF0\x9D\x95\x8E"	=> "\x77",
			"\xF0\x9D\x95\x8F"	=> "\x78",
			"\xF0\x9D\x95\x90"	=> "\x79",
			"\xF0\x9D\x95\xAC"	=> "\x61",
			"\xF0\x9D\x95\xAD"	=> "\x62",
			"\xF0\x9D\x95\xAE"	=> "\x63",
			"\xF0\x9D\x95\xAF"	=> "\x64",
			"\xF0\x9D\x95\xB0"	=> "\x65",
			"\xF0\x9D\x95\xB1"	=> "\x66",
			"\xF0\x9D\x95\xB2"	=> "\x67",
			"\xF0\x9D\x95\xB3"	=> "\x68",
			"\xF0\x9D\x95\xB4"	=> "\x69",
			"\xF0\x9D\x95\xB5"	=> "\x6A",
			"\xF0\x9D\x95\xB6"	=> "\x6B",
			"\xF0\x9D\x95\xB7"	=> "\x6C",
			"\xF0\x9D\x95\xB8"	=> "\x6D",
			"\xF0\x9D\x95\xB9"	=> "\x6E",
			"\xF0\x9D\x95\xBA"	=> "\x6F",
			"\xF0\x9D\x95\xBB"	=> "\x70",
			"\xF0\x9D\x95\xBC"	=> "\x71",
			"\xF0\x9D\x95\xBD"	=> "\x72",
			"\xF0\x9D\x95\xBE"	=> "\x73",
			"\xF0\x9D\x95\xBF"	=> "\x74",
			"\xF0\x9D\x96\x80"	=> "\x75",
			"\xF0\x9D\x96\x81"	=> "\x76",
			"\xF0\x9D\x96\x82"	=> "\x77",
			"\xF0\x9D\x96\x83"	=> "\x78",
			"\xF0\x9D\x96\x84"	=> "\x79",
			"\xF0\x9D\x96\x85"	=> "\x7A",
			"\xF0\x9D\x96\xA0"	=> "\x61",
			"\xF0\x9D\x96\xA1"	=> "\x62",
			"\xF0\x9D\x96\xA2"	=> "\x63",
			"\xF0\x9D\x96\xA3"	=> "\x64",
			"\xF0\x9D\x96\xA4"	=> "\x65",
			"\xF0\x9D\x96\xA5"	=> "\x66",
			"\xF0\x9D\x96\xA6"	=> "\x67",
			"\xF0\x9D\x96\xA7"	=> "\x68",
			"\xF0\x9D\x96\xA8"	=> "\x69",
			"\xF0\x9D\x96\xA9"	=> "\x6A",
			"\xF0\x9D\x96\xAA"	=> "\x6B",
			"\xF0\x9D\x96\xAB"	=> "\x6C",
			"\xF0\x9D\x96\xAC"	=> "\x6D",
			"\xF0\x9D\x96\xAD"	=> "\x6E",
			"\xF0\x9D\x96\xAE"	=> "\x6F",
			"\xF0\x9D\x96\xAF"	=> "\x70",
			"\xF0\x9D\x96\xB0"	=> "\x71",
			"\xF0\x9D\x96\xB1"	=> "\x72",
			"\xF0\x9D\x96\xB2"	=> "\x73",
			"\xF0\x9D\x96\xB3"	=> "\x74",
			"\xF0\x9D\x96\xB4"	=> "\x75",
			"\xF0\x9D\x96\xB5"	=> "\x76",
			"\xF0\x9D\x96\xB6"	=> "\x77",
			"\xF0\x9D\x96\xB7"	=> "\x78",
			"\xF0\x9D\x96\xB8"	=> "\x79",
			"\xF0\x9D\x96\xB9"	=> "\x7A",
			"\xF0\x9D\x97\x94"	=> "\x61",
			"\xF0\x9D\x97\x95"	=> "\x62",
			"\xF0\x9D\x97\x96"	=> "\x63",
			"\xF0\x9D\x97\x97"	=> "\x64",
			"\xF0\x9D\x97\x98"	=> "\x65",
			"\xF0\x9D\x97\x99"	=> "\x66",
			"\xF0\x9D\x97\x9A"	=> "\x67",
			"\xF0\x9D\x97\x9B"	=> "\x68",
			"\xF0\x9D\x97\x9C"	=> "\x69",
			"\xF0\x9D\x97\x9D"	=> "\x6A",
			"\xF0\x9D\x97\x9E"	=> "\x6B",
			"\xF0\x9D\x97\x9F"	=> "\x6C",
			"\xF0\x9D\x97\xA0"	=> "\x6D",
			"\xF0\x9D\x97\xA1"	=> "\x6E",
			"\xF0\x9D\x97\xA2"	=> "\x6F",
			"\xF0\x9D\x97\xA3"	=> "\x70",
			"\xF0\x9D\x97\xA4"	=> "\x71",
			"\xF0\x9D\x97\xA5"	=> "\x72",
			"\xF0\x9D\x97\xA6"	=> "\x73",
			"\xF0\x9D\x97\xA7"	=> "\x74",
			"\xF0\x9D\x97\xA8"	=> "\x75",
			"\xF0\x9D\x97\xA9"	=> "\x76",
			"\xF0\x9D\x97\xAA"	=> "\x77",
			"\xF0\x9D\x97\xAB"	=> "\x78",
			"\xF0\x9D\x97\xAC"	=> "\x79",
			"\xF0\x9D\x97\xAD"	=> "\x7A",
			"\xF0\x9D\x98\x88"	=> "\x61",
			"\xF0\x9D\x98\x89"	=> "\x62",
			"\xF0\x9D\x98\x8A"	=> "\x63",
			"\xF0\x9D\x98\x8B"	=> "\x64",
			"\xF0\x9D\x98\x8C"	=> "\x65",
			"\xF0\x9D\x98\x8D"	=> "\x66",
			"\xF0\x9D\x98\x8E"	=> "\x67",
			"\xF0\x9D\x98\x8F"	=> "\x68",
			"\xF0\x9D\x98\x90"	=> "\x69",
			"\xF0\x9D\x98\x91"	=> "\x6A",
			"\xF0\x9D\x98\x92"	=> "\x6B",
			"\xF0\x9D\x98\x93"	=> "\x6C",
			"\xF0\x9D\x98\x94"	=> "\x6D",
			"\xF0\x9D\x98\x95"	=> "\x6E",
			"\xF0\x9D\x98\x96"	=> "\x6F",
			"\xF0\x9D\x98\x97"	=> "\x70",
			"\xF0\x9D\x98\x98"	=> "\x71",
			"\xF0\x9D\x98\x99"	=> "\x72",
			"\xF0\x9D\x98\x9A"	=> "\x73",
			"\xF0\x9D\x98\x9B"	=> "\x74",
			"\xF0\x9D\x98\x9C"	=> "\x75",
			"\xF0\x9D\x98\x9D"	=> "\x76",
			"\xF0\x9D\x98\x9E"	=> "\x77",
			"\xF0\x9D\x98\x9F"	=> "\x78",
			"\xF0\x9D\x98\xA0"	=> "\x79",
			"\xF0\x9D\x98\xA1"	=> "\x7A",
			"\xF0\x9D\x98\xBC"	=> "\x61",
			"\xF0\x9D\x98\xBD"	=> "\x62",
			"\xF0\x9D\x98\xBE"	=> "\x63",
			"\xF0\x9D\x98\xBF"	=> "\x64",
			"\xF0\x9D\x99\x80"	=> "\x65",
			"\xF0\x9D\x99\x81"	=> "\x66",
			"\xF0\x9D\x99\x82"	=> "\x67",
			"\xF0\x9D\x99\x83"	=> "\x68",
			"\xF0\x9D\x99\x84"	=> "\x69",
			"\xF0\x9D\x99\x85"	=> "\x6A",
			"\xF0\x9D\x99\x86"	=> "\x6B",
			"\xF0\x9D\x99\x87"	=> "\x6C",
			"\xF0\x9D\x99\x88"	=> "\x6D",
			"\xF0\x9D\x99\x89"	=> "\x6E",
			"\xF0\x9D\x99\x8A"	=> "\x6F",
			"\xF0\x9D\x99\x8B"	=> "\x70",
			"\xF0\x9D\x99\x8C"	=> "\x71",
			"\xF0\x9D\x99\x8D"	=> "\x72",
			"\xF0\x9D\x99\x8E"	=> "\x73",
			"\xF0\x9D\x99\x8F"	=> "\x74",
			"\xF0\x9D\x99\x90"	=> "\x75",
			"\xF0\x9D\x99\x91"	=> "\x76",
			"\xF0\x9D\x99\x92"	=> "\x77",
			"\xF0\x9D\x99\x93"	=> "\x78",
			"\xF0\x9D\x99\x94"	=> "\x79",
			"\xF0\x9D\x99\x95"	=> "\x7A",
			"\xF0\x9D\x99\xB0"	=> "\x61",
			"\xF0\x9D\x99\xB1"	=> "\x62",
			"\xF0\x9D\x99\xB2"	=> "\x63",
			"\xF0\x9D\x99\xB3"	=> "\x64",
			"\xF0\x9D\x99\xB4"	=> "\x65",
			"\xF0\x9D\x99\xB5"	=> "\x66",
			"\xF0\x9D\x99\xB6"	=> "\x67",
			"\xF0\x9D\x99\xB7"	=> "\x68",
			"\xF0\x9D\x99\xB8"	=> "\x69",
			"\xF0\x9D\x99\xB9"	=> "\x6A",
			"\xF0\x9D\x99\xBA"	=> "\x6B",
			"\xF0\x9D\x99\xBB"	=> "\x6C",
			"\xF0\x9D\x99\xBC"	=> "\x6D",
			"\xF0\x9D\x99\xBD"	=> "\x6E",
			"\xF0\x9D\x99\xBE"	=> "\x6F",
			"\xF0\x9D\x99\xBF"	=> "\x70",
			"\xF0\x9D\x9A\x80"	=> "\x71",
			"\xF0\x9D\x9A\x81"	=> "\x72",
			"\xF0\x9D\x9A\x82"	=> "\x73",
			"\xF0\x9D\x9A\x83"	=> "\x74",
			"\xF0\x9D\x9A\x84"	=> "\x75",
			"\xF0\x9D\x9A\x85"	=> "\x76",
			"\xF0\x9D\x9A\x86"	=> "\x77",
			"\xF0\x9D\x9A\x87"	=> "\x78",
			"\xF0\x9D\x9A\x88"	=> "\x79",
			"\xF0\x9D\x9A\x89"	=> "\x7A",
			"\xF0\x9D\x9A\xA8"	=> "\xCE\xB1",
			"\xF0\x9D\x9A\xA9"	=> "\xCE\xB2",
			"\xF0\x9D\x9A\xAA"	=> "\xCE\xB3",
			"\xF0\x9D\x9A\xAB"	=> "\xCE\xB4",
			"\xF0\x9D\x9A\xAC"	=> "\xCE\xB5",
			"\xF0\x9D\x9A\xAD"	=> "\xCE\xB6",
			"\xF0\x9D\x9A\xAE"	=> "\xCE\xB7",
			"\xF0\x9D\x9A\xAF"	=> "\xCE\xB8",
			"\xF0\x9D\x9A\xB0"	=> "\xCE\xB9",
			"\xF0\x9D\x9A\xB1"	=> "\xCE\xBA",
			"\xF0\x9D\x9A\xB2"	=> "\xCE\xBB",
			"\xF0\x9D\x9A\xB3"	=> "\xCE\xBC",
			"\xF0\x9D\x9A\xB4"	=> "\xCE\xBD",
			"\xF0\x9D\x9A\xB5"	=> "\xCE\xBE",
			"\xF0\x9D\x9A\xB6"	=> "\xCE\xBF",
			"\xF0\x9D\x9A\xB7"	=> "\xCF\x80",
			"\xF0\x9D\x9A\xB8"	=> "\xCF\x81",
			"\xF0\x9D\x9A\xB9"	=> "\xCE\xB8",
			"\xF0\x9D\x9A\xBA"	=> "\xCF\x83",
			"\xF0\x9D\x9A\xBB"	=> "\xCF\x84",
			"\xF0\x9D\x9A\xBC"	=> "\xCF\x85",
			"\xF0\x9D\x9A\xBD"	=> "\xCF\x86",
			"\xF0\x9D\x9A\xBE"	=> "\xCF\x87",
			"\xF0\x9D\x9A\xBF"	=> "\xCF\x88",
			"\xF0\x9D\x9B\x80"	=> "\xCF\x89",
			"\xF0\x9D\x9B\x93"	=> "\xCF\x83",
			"\xF0\x9D\x9B\xA2"	=> "\xCE\xB1",
			"\xF0\x9D\x9B\xA3"	=> "\xCE\xB2",
			"\xF0\x9D\x9B\xA4"	=> "\xCE\xB3",
			"\xF0\x9D\x9B\xA5"	=> "\xCE\xB4",
			"\xF0\x9D\x9B\xA6"	=> "\xCE\xB5",
			"\xF0\x9D\x9B\xA7"	=> "\xCE\xB6",
			"\xF0\x9D\x9B\xA8"	=> "\xCE\xB7",
			"\xF0\x9D\x9B\xA9"	=> "\xCE\xB8",
			"\xF0\x9D\x9B\xAA"	=> "\xCE\xB9",
			"\xF0\x9D\x9B\xAB"	=> "\xCE\xBA",
			"\xF0\x9D\x9B\xAC"	=> "\xCE\xBB",
			"\xF0\x9D\x9B\xAD"	=> "\xCE\xBC",
			"\xF0\x9D\x9B\xAE"	=> "\xCE\xBD",
			"\xF0\x9D\x9B\xAF"	=> "\xCE\xBE",
			"\xF0\x9D\x9B\xB0"	=> "\xCE\xBF",
			"\xF0\x9D\x9B\xB1"	=> "\xCF\x80",
			"\xF0\x9D\x9B\xB2"	=> "\xCF\x81",
			"\xF0\x9D\x9B\xB3"	=> "\xCE\xB8",
			"\xF0\x9D\x9B\xB4"	=> "\xCF\x83",
			"\xF0\x9D\x9B\xB5"	=> "\xCF\x84",
			"\xF0\x9D\x9B\xB6"	=> "\xCF\x85",
			"\xF0\x9D\x9B\xB7"	=> "\xCF\x86",
			"\xF0\x9D\x9B\xB8"	=> "\xCF\x87",
			"\xF0\x9D\x9B\xB9"	=> "\xCF\x88",
			"\xF0\x9D\x9B\xBA"	=> "\xCF\x89",
			"\xF0\x9D\x9C\x8D"	=> "\xCF\x83",
			"\xF0\x9D\x9C\x9C"	=> "\xCE\xB1",
			"\xF0\x9D\x9C\x9D"	=> "\xCE\xB2",
			"\xF0\x9D\x9C\x9E"	=> "\xCE\xB3",
			"\xF0\x9D\x9C\x9F"	=> "\xCE\xB4",
			"\xF0\x9D\x9C\xA0"	=> "\xCE\xB5",
			"\xF0\x9D\x9C\xA1"	=> "\xCE\xB6",
			"\xF0\x9D\x9C\xA2"	=> "\xCE\xB7",
			"\xF0\x9D\x9C\xA3"	=> "\xCE\xB8",
			"\xF0\x9D\x9C\xA4"	=> "\xCE\xB9",
			"\xF0\x9D\x9C\xA5"	=> "\xCE\xBA",
			"\xF0\x9D\x9C\xA6"	=> "\xCE\xBB",
			"\xF0\x9D\x9C\xA7"	=> "\xCE\xBC",
			"\xF0\x9D\x9C\xA8"	=> "\xCE\xBD",
			"\xF0\x9D\x9C\xA9"	=> "\xCE\xBE",
			"\xF0\x9D\x9C\xAA"	=> "\xCE\xBF",
			"\xF0\x9D\x9C\xAB"	=> "\xCF\x80",
			"\xF0\x9D\x9C\xAC"	=> "\xCF\x81",
			"\xF0\x9D\x9C\xAD"	=> "\xCE\xB8",
			"\xF0\x9D\x9C\xAE"	=> "\xCF\x83",
			"\xF0\x9D\x9C\xAF"	=> "\xCF\x84",
			"\xF0\x9D\x9C\xB0"	=> "\xCF\x85",
			"\xF0\x9D\x9C\xB1"	=> "\xCF\x86",
			"\xF0\x9D\x9C\xB2"	=> "\xCF\x87",
			"\xF0\x9D\x9C\xB3"	=> "\xCF\x88",
			"\xF0\x9D\x9C\xB4"	=> "\xCF\x89",
			"\xF0\x9D\x9D\x87"	=> "\xCF\x83",
			"\xF0\x9D\x9D\x96"	=> "\xCE\xB1",
			"\xF0\x9D\x9D\x97"	=> "\xCE\xB2",
			"\xF0\x9D\x9D\x98"	=> "\xCE\xB3",
			"\xF0\x9D\x9D\x99"	=> "\xCE\xB4",
			"\xF0\x9D\x9D\x9A"	=> "\xCE\xB5",
			"\xF0\x9D\x9D\x9B"	=> "\xCE\xB6",
			"\xF0\x9D\x9D\x9C"	=> "\xCE\xB7",
			"\xF0\x9D\x9D\x9D"	=> "\xCE\xB8",
			"\xF0\x9D\x9D\x9E"	=> "\xCE\xB9",
			"\xF0\x9D\x9D\x9F"	=> "\xCE\xBA",
			"\xF0\x9D\x9D\xA0"	=> "\xCE\xBB",
			"\xF0\x9D\x9D\xA1"	=> "\xCE\xBC",
			"\xF0\x9D\x9D\xA2"	=> "\xCE\xBD",
			"\xF0\x9D\x9D\xA3"	=> "\xCE\xBE",
			"\xF0\x9D\x9D\xA4"	=> "\xCE\xBF",
			"\xF0\x9D\x9D\xA5"	=> "\xCF\x80",
			"\xF0\x9D\x9D\xA6"	=> "\xCF\x81",
			"\xF0\x9D\x9D\xA7"	=> "\xCE\xB8",
			"\xF0\x9D\x9D\xA8"	=> "\xCF\x83",
			"\xF0\x9D\x9D\xA9"	=> "\xCF\x84",
			"\xF0\x9D\x9D\xAA"	=> "\xCF\x85",
			"\xF0\x9D\x9D\xAB"	=> "\xCF\x86",
			"\xF0\x9D\x9D\xAC"	=> "\xCF\x87",
			"\xF0\x9D\x9D\xAD"	=> "\xCF\x88",
			"\xF0\x9D\x9D\xAE"	=> "\xCF\x89",
			"\xF0\x9D\x9E\x81"	=> "\xCF\x83",
			"\xF0\x9D\x9E\x90"	=> "\xCE\xB1",
			"\xF0\x9D\x9E\x91"	=> "\xCE\xB2",
			"\xF0\x9D\x9E\x92"	=> "\xCE\xB3",
			"\xF0\x9D\x9E\x93"	=> "\xCE\xB4",
			"\xF0\x9D\x9E\x94"	=> "\xCE\xB5",
			"\xF0\x9D\x9E\x95"	=> "\xCE\xB6",
			"\xF0\x9D\x9E\x96"	=> "\xCE\xB7",
			"\xF0\x9D\x9E\x97"	=> "\xCE\xB8",
			"\xF0\x9D\x9E\x98"	=> "\xCE\xB9",
			"\xF0\x9D\x9E\x99"	=> "\xCE\xBA",
			"\xF0\x9D\x9E\x9A"	=> "\xCE\xBB",
			"\xF0\x9D\x9E\x9B"	=> "\xCE\xBC",
			"\xF0\x9D\x9E\x9C"	=> "\xCE\xBD",
			"\xF0\x9D\x9E\x9D"	=> "\xCE\xBE",
			"\xF0\x9D\x9E\x9E"	=> "\xCE\xBF",
			"\xF0\x9D\x9E\x9F"	=> "\xCF\x80",
			"\xF0\x9D\x9E\xA0"	=> "\xCF\x81",
			"\xF0\x9D\x9E\xA1"	=> "\xCE\xB8",
			"\xF0\x9D\x9E\xA2"	=> "\xCF\x83",
			"\xF0\x9D\x9E\xA3"	=> "\xCF\x84",
			"\xF0\x9D\x9E\xA4"	=> "\xCF\x85",
			"\xF0\x9D\x9E\xA5"	=> "\xCF\x86",
			"\xF0\x9D\x9E\xA6"	=> "\xCF\x87",
			"\xF0\x9D\x9E\xA7"	=> "\xCF\x88",
			"\xF0\x9D\x9E\xA8"	=> "\xCF\x89",
			"\xF0\x9D\x9E\xBB"	=> "\xCF\x83",
			"\xF0\x9D\x9F\x8A"	=> "\xCF\x9D",
		);
		global $phpbb_root_path, $phpEx;

		// do the case fold
		$text = utf8_case_fold($text, $option);

		if (!class_exists('utf_normalizer')) {
			global $phpbb_root_path, $phpEx;
			include($phpbb_root_path . 'includes/utf/utf_normalizer.' . $phpEx);
		}

		// convert to NFKC
		utf_normalizer::nfkc($text);

		// FC_NFKC_Closure, http://www.unicode.org/Public/5.0.0/ucd/DerivedNormalizationProps.txt
		$text = strtr($text, $fc_nfkc_closure);

		return $text;
	}
}

if (!function_exists('utf8_case_fold')) {
	/**
	* Case folds a unicode string as per Unicode 5.0, section 3.13
	*
	* @param	string	$text	text to be case folded
	* @param	string	$option	determines how we will fold the cases
	* @return	string			case folded text
	*/
	function utf8_case_fold($text, $option = 'full')
	{
		static $uniarray = array();
		global $phpbb_root_path, $phpEx;

		// common is always set
		if (!isset($uniarray['c'])) {
			$uniarray['c'] = include($phpbb_root_path . 'includes/utf/data/case_fold_c.' . $phpEx);
		}

		// only set full if we need to
		if ($option === 'full' && !isset($uniarray['f'])) {
			$uniarray['f'] = include($phpbb_root_path . 'includes/utf/data/case_fold_f.' . $phpEx);
		}

		// only set simple if we need to
		if ($option !== 'full' && !isset($uniarray['s'])) {
			$uniarray['s'] = include($phpbb_root_path . 'includes/utf/data/case_fold_s.' . $phpEx);
		}

		// common is always replaced
		$text = strtr($text, $uniarray['c']);

		if ($option === 'full') {
			// full replaces a character with multiple characters
			$text = strtr($text, $uniarray['f']);
		} else {
			// simple replaces a character with another character
			$text = strtr($text, $uniarray['s']);
		}

		return $text;
	}
}
