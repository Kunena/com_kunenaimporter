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

class KunenaimporterModelExport_Smf2 extends KunenaimporterModelExport {
	/**
	 * Extension name ([a-z0-9_], wihtout 'com_' prefix)
	 * @var string
	 */
	public $extname = 'smf2';
	/**
	 * Display name
	 * @var string
	 */
	public $exttitle = 'SMF2';
	/**
	 * External application
	 * @var bool
	 */
	public $external = true;
	/**
	 * Minimum required version
	 * @var string or null
	 */
	protected $versionmin = '2.0 RC3';
	/**
	 * Maximum accepted version
	 * @var string or null
	 */
	protected $versionmax = '2.0.999';

	protected $dbconfig = null;
	protected $config = null;

	/**
	 * Detect if component exists
	 *
	 * @return bool
	 */
	public function detectComponent($path = null) {
		if ($path === null) $path = $this->basepath;
		// Make sure that configuration file exist, but check also something else
		if (!JFile::exists("{$path}/Settings.php")
			|| !JFile::exists("{$path}/Sources/BoardIndex.php")) {
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
			$option ['host'] = $config['db_server'];
			$option ['user'] = $config['db_user'];
			$option ['password'] = $config['db_passwd'];
			$option ['database'] = $config['db_name'];
			$option ['prefix'] = $config['db_prefix'];
			$database = JDatabase::getInstance ( $option );
		}
		return $database;
	}

	/**
	 * Get component version
	 */
	public function getVersion() {
		$config = $this->getConfig();
		$version = isset($config['smfVersion']) ? $config['smfVersion']->value : '';
		return $version;
	}

	protected function &getDBConfig() {
		if (!$this->dbconfig) {
			require "{$this->basepath}/Settings.php";
			$this->dbconfig = get_defined_vars();
		}
		return $this->dbconfig;
	}

	public function &getConfig() {
		if (empty($this->config)) {
			$query = "SELECT variable, value FROM #__settings";
			$this->ext_database->setQuery ( $query );
			$this->config = $this->ext_database->loadObjectList ('variable');
		}
		return $this->config;
	}

	public function countConfig() {
		return 1;
	}

	public function &exportConfig($start = 0, $limit = 0) {
		$config = array ();
		if ($start)
			return $config;

		$dbconfig = $this->getDBConfig();
		$query = "SELECT variable, value FROM #__settings";
		$this->ext_database->setQuery ( $query );
		$result = $this->ext_database->loadObjectList ('variable');

		$config['id'] = 1;

		$config['board_title'] = $dbconfig['mbname'];
		$config['email'] = $dbconfig['webmaster_email'];
		$config['board_offline'] = (bool)$dbconfig['maintenance'];
		$config['board_ofset'] = $result['time_offset']->value; // + default_timezone
		$config['offline_message'] = "<h1>{$dbconfig['mmessage']}</h1><p>{$dbconfig['mmessage']}</p>";
		// $config['enablerss'] = null;
		// $config['enablepdf'] = null;
		// $config['threads_per_page'] = null;
		// $config['messages_per_page'] = null;
		// $config['messages_per_page_search'] = null;
		// $config['showhistory'] = null;
		// $config['historylimit'] = null;
		// $config['shownew'] = null;
		// $config['jmambot'] = null;
		// $config['disemoticons'] = null;
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
		// $config['allowsubscriptions'] = null;
		// $config['subscriptionschecked'] = null;
		// $config['allowfavorites'] = null;
		// $config['maxsubject'] = null;
		// $config['maxsig'] = null;
		// $config['regonly'] = null;
		// $config['changename'] = null;
		// $config['pubwrite'] = null;
		// $config['floodprotection'] = null;
		// $config['mailmod'] = null;
		// $config['mailadmin'] = null;
		// $config['captcha'] = null;
		// $config['mailfull'] = null;
		// $config['allowavatar'] = null;
		// $config['allowavatarupload'] = null;
		// $config['allowavatargallery'] = null;
		// $config['avatarquality'] = null;
		// $config['avatarsize'] = null;
		$config['allowimageupload'] = $result['attachmentEnable']->value;
		// $config['allowimageregupload'] = null;
		$config['imageheight'] = $result['max_image_height']->value;
		$config['imagewidth'] = $result['max_image_width']->value;
		$config['imagesize'] = $result['attachmentSizeLimit']->value;
		$config['allowfileupload'] = $result['attachmentEnable']->value;
		// $config['allowfileregupload'] = null;
		// $config['filetypes'] = null;
		$config['filesize'] = $result['attachmentSizeLimit']->value;
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
		$config['showwhoisonline'] = $result['who_enabled']->value;
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
		// $config['fbsessiontimeout'] = null;
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
		// $config['fbdefaultpage'] = null;
		// $config['default_sort'] = null;
		// $config['alphauserpointsnumchars'] = null;
		// $config['sef'] = null;
		// $config['sefcats'] = null;
		// $config['sefutf8'] = null;
		// $config['showimgforguest'] = null;
		// $config['showfileforguest'] = null;
		// $config['pollnboptions'] = null;
		// $config['pollallowvoteone'] = null;
		// $config['pollenabled'] = null;
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
		$config['thumbheight'] = $result['attachmentThumbHeight']->value;
		$config['thumbwidth'] = $result['attachmentThumbWidth']->value;
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

	public function countCategories() {
		static $count = false;
		if ($count === false) {
			$query = "SELECT COUNT(*) FROM #__categories";
			$count = $this->getCount ( $query );
			$query = "SELECT COUNT(*) FROM #__boards";
			$count += $this->getCount ( $query );
		}
		return $count;
	}

	public function &exportCategories($start = 0, $limit = 0) {
		$query = "SELECT MAX(id_board) FROM #__boards";
		$this->ext_database->setQuery ( $query );
		$maxboard = (int) $this->ext_database->loadResult ();
		$query = "(SELECT
			id_board AS id,
			IF(id_parent,id_parent,id_cat+{$maxboard}) AS parent,
			name AS name,
			0 AS cat_emoticon,
			0 AS locked,
			0 AS alert_admin,
			1 AS moderated,
			NULL AS moderators,
			0 AS pub_access,
			1 AS pub_recurse,
			0 AS admin_access,
			1 AS admin_recurse,
			board_order AS ordering,
			0 AS future2,
			1 AS published,
			0 AS checked_out,
			'0000-00-00 00:00:00' AS checked_out_time,
			0 AS review,
			0 AS allow_anonymous,
			0 as post_anonymous,
			0 AS hits,
			description AS description,
			'' AS headerdesc,
			'' AS class_sfx,
			0 AS allow_polls,
			id_last_msg AS id_last_msg,
			num_posts AS numPosts,
			num_topics AS numTopics,
			0 AS time_last_msg
		FROM #__boards ORDER BY id)
		UNION ALL
		(SELECT
			id_cat+{$maxboard} AS id,
			0 AS parent,
			name AS name,
			0 AS cat_emoticon,
			0 AS locked,
			0 AS alert_admin,
			1 AS moderated,
			NULL AS moderators,
			0 AS pub_access,
			1 AS pub_recurse,
			0 AS admin_access,
			1 AS admin_recurse,
			cat_order AS ordering,
			0 AS future2,
			1 AS published,
			0 AS checked_out,
			'0000-00-00 00:00:00' AS checked_out_time,
			0 AS review,
			0 AS allow_anonymous,
			0 as post_anonymous,
			0 AS hits,
			'' AS description,
			'' AS headerdesc,
			'' AS class_sfx,
			0 AS allow_polls,
			0 AS id_last_msg,
			0 AS numPosts,
			0 AS numTopics,
			0 AS time_last_msg
		FROM #__categories ORDER BY id)";
		$result = $this->getExportData ( $query, $start, $limit, 'id' );
		foreach ( $result as &$row ) {
			$row->name = $this->prep ( $row->name );
			$row->description = $this->prep ( $row->description );
		}
		return $result;
	}

	public function countMessages() {
		$query = "SELECT COUNT(*) FROM #__messages";
		return $this->getCount ( $query );
	}

	public function &exportMessages($start = 0, $limit = 0) {
		$query = "SELECT
			m.id_msg AS id,
			IF(m.id_msg=t.id_first_msg,0,t.id_first_msg) AS parent,
			t.id_first_msg AS thread,
			t.id_board AS catid,
			IF(m.poster_name, m.poster_name, u.member_name) AS name,
			m.id_member AS userid,
			m.poster_email AS email,
			m.subject AS subject,
			m.poster_time AS time,
			m.poster_ip AS ip,
			0 AS topic_emoticon,
			IF(m.id_msg=t.id_first_msg,locked,0) AS locked,
			IF(m.approved=1,0,1) AS hold,
			IF(m.id_msg=t.id_first_msg,t.is_sticky,0) AS ordering,
			t.num_views AS hits,
			0 AS moved,
			IF(m.modified_time>0,m.id_msg_modified,0) AS modified_by,
			m.modified_time AS modified_time,
			'' AS modified_reason,
			m.body AS message
		FROM #__messages AS m
		LEFT JOIN #__topics AS t ON m.id_topic = t.id_topic
		LEFT JOIN #__members AS u ON m.id_member = u.id_member
		ORDER BY m.id_msg";
		$result = $this->getExportData ( $query, $start, $limit, 'id' );
		foreach ( $result as &$row ) {
			$row->subject = $this->prep ( $row->subject );
			$row->message = $this->prep ( $row->message );
		}
		return $result;
	}

	public function countSessions() {
		$query = "SELECT COUNT(*) FROM #__members WHERE last_login>0";
		return $this->getCount ( $query );
	}
	public function &exportSessions($start = 0, $limit = 0) {
		$query = "SELECT
			id_member AS userid,
			NULL AS allowed,
			last_login AS lasttime,
			'' AS readtopics,
			last_login AS currvisit
		FROM #__members
		WHERE last_login>0";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}

	public function countSubscriptions() {
		$query = "SELECT COUNT(*) FROM #__log_notify";
		return $this->getCount ( $query );
	}
	public function &exportSubscriptions($start = 0, $limit = 0) {
		$query = "SELECT
			t.id_first_msg AS thread,
			s.id_member AS userid,
			0 AS future1
		FROM #__log_notify AS s
		INNER JOIN #__topics AS t ON s.id_topic=t.id_topic";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}

	public function countUserProfile() {
		$query = "SELECT COUNT(*) FROM #__members";
		return $this->getCount ( $query );
	}

	public function &exportUserProfile($start = 0, $limit = 0) {
		$query = "SELECT
			u.id_member AS userid,
			'flat' AS view,
			u.signature AS signature,
			0 AS moderator,
			NULL AS banned,
			0 AS ordering,
			u.posts AS posts,
			avatar AS avatar,
			(karma_good-karma_bad) AS karma,
			0 AS karma_time,
			1 AS group_id,
			0 AS uhits,
			u.personal_text AS personalText,
			u.gender AS gender,
			u.birthdate AS birthdate,
			u.location AS location,
			u.icq AS ICQ,
			u.aim AS AIM,
			u.yim AS YIM,
			u.msn AS MSN,
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
			u.website_title AS websitename,
			u.website_url AS websiteurl,
			0 AS rank,
			hide_email AS hideEmail,
			show_online AS showOnline
		FROM #__members AS u
		ORDER BY u.id_member";
		$result = $this->getExportData ( $query, $start, $limit, 'userid' );

		$config = $this->getConfig();
		if (!empty($config['custom_avatar_enabled']->value)) {
			$avatar_path = $config['custom_avatar_dir']->value;
		} elseif (!empty($config['currentAttachmentUploadDir']->value)) {
			$avatar_path = $config['custom_avatar_dir']->value;
		}
		foreach ( $result as &$row ) {
			if ($row->avatar) {
				if (stristr($row->avatar, 'http://')) {
					// URLs are not supported
					$row->avatar = '';
				} else {
					// Gallery
					$row->avatar = "gallery/{$row->avatar}";
				}
			}

			// Convert bbcode in signature
			$row->signature = $this->prep ( $row->signature );
			$row->location = $this->prep ( $row->location );
		}
		return $result;
	}

	public function countUsers() {
		$query = "SELECT COUNT(*) FROM #__members";
		return $this->getCount ( $query );
	}

	public function &exportUsers($start = 0, $limit = 0) {
		$query = "SELECT
			u.id_member AS extid,
			u.member_name AS extusername,
			u.real_name AS name,
			u.member_name AS username,
			u.email_address AS email,
			CONCAT('smf2::', u.password_salt,':',u.passwd) AS password,
			'Registered' AS usertype,
			IF(is_activated>0,0,1) AS block,
			FROM_UNIXTIME(u.date_registered) AS registerDate,
			IF(u.last_login>0, FROM_UNIXTIME(u.last_login), '0000-00-00 00:00:00') AS lastvisitDate,
			NULL AS params
		FROM #__members AS u
		ORDER BY u.id_member";
		$result = $this->getExportData ( $query, $start, $limit, 'extid' );
		foreach ( $result as &$row ) {
			$row->name = html_entity_decode ( $row->name );
			$row->username = html_entity_decode ( $row->username );
		}
		return $result;
	}

	/*
	public function &exportAttachments($start = 0, $limit = 0) {
		$query = "SELECT
			id_attach AS id,
			id_msg AS mesid,
			id_member AS userid,
			file_hash AS hash,
			size AS size,
			id_folder AS folder,
			IF(LENGTH(mime_type)>0,mime_type,fileext) AS filetype,
			filename AS filename
		FROM #__attachments
		WHERE attachment_type=0
		ORDER BY a.id_attach";
		$result = $this->getExportData ( $query, $start, $limit, 'id' );
		foreach ( $result as &$row ) {
			$row->folder = 'smf2/'.$row->folder;
			$row->copypath = $config->attachmentUploadDir;
		}
		return $result;
	}
	*/

	public function mapJoomlaUser($joomlauser) {
		$query = "SELECT id_member
			FROM #__members WHERE member_name={$this->ext_database->Quote($joomlauser->username)}";

		$this->ext_database->setQuery( $query );
		$result = intval($this->ext_database->loadResult());
		return $result;
	}

	protected function &getAvatarGalleries() {
		static $galleries = false;
		if ($galleries === false) {
			$config = $this->getConfig();
			$path = $config['avatar_directory']->value;
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
		$galleries = array_slice($this->getAvatarGalleries(), $start, $limit);
		return $galleries;
	}

	protected function prep($s) {
		$s = html_entity_decode($s, ENT_COMPAT, 'UTF-8');
		$s = preg_replace ( "/\r/", '', $s );
		$s = preg_replace ( "/\n/", '', $s );
		$s = preg_replace ( '/<br \/>/', "\n", $s );
		$s = preg_replace ( '/\[s\]/', "[strike]", $s );
		$s = preg_replace ( '/\[\/s\]/', "[/strike]", $s );
		return $s;
	}
}
