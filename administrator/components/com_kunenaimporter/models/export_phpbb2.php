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
	 * Minimum required version
	 * @var string or null
	 */
	protected $versionmin = '2.0.21';
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
	 * Count total number of users to be exported (external applications only)
	 */
	public function countUsers() {
		$query = "SELECT COUNT(*) FROM #__users AS f WHERE user_id > 0";
		return $this->getCount ( $query );
	}

	/**
	 * Export users (external applications only)
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

	public function countUserProfile() {
		$query = "SELECT COUNT(*) FROM #__users WHERE user_id > 0";
		return $this->getCount ( $query );
	}

	public function &exportUserProfile($start = 0, $limit = 0) {
		$query = "SELECT
			user_id AS userid,
			'' AS view,
			user_sig AS signature,
			0 AS moderator,
			0 AS ordering,
			user_posts AS posts,
			'' AS avatar,
			0 AS karma,
			0 AS karma_time,
			0 AS group_id,
			0 AS uhits,
			'' AS personalText,
			0 AS gender,
			'' AS birthdate,
			user_from AS location,
			user_icq AS ICQ,
			user_aim AS AIM,
			user_yim AS YIM,
			user_msnm AS MSN,
			'' AS SKYPE,
			'' AS GTALK,
			'' AS websitename,
			user_website AS websiteurl,
			0 AS rank,
			0 AS hideEmail,
			1 AS showOnline
		FROM #__users
		WHERE user_id > 0
		ORDER BY user_id";
		$result = $this->getExportData ( $query, $start, $limit );

		foreach ( $result as $key => &$row ) {
			// Convert bbcode in signature
			$row->signature = prep ( $row->signature );
			$row->location = prep ( $row->location );
		}
		return $result;
	}

	public function countCategories() {
		$query = "SELECT COUNT(*) FROM #__categories";
		$count = $this->getCount ( $query );
		$query = "SELECT COUNT(*) FROM #__forums";
		return $count + $this->getCount ( $query );
	}

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
			0 AS hits,
			'' AS description,
			'' AS headerdesc,
			'' AS class_sfx,
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
			0 AS hits,
			forum_desc AS description,
			'' AS headerdesc,
			'' AS class_sfx,
			forum_last_post_id AS id_last_msg,
			forum_posts AS numPosts,
			forum_topics AS numTopics,
			0 AS time_last_msg
		FROM #__forums) ORDER BY id";
		$result = $this->getExportData ( $query, $start, $limit );
		foreach ( $result as $key => &$row ) {
			$row->name = prep ( $row->name );
			$row->description = prep ( $row->description );
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

		$config['id'] = 1;
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

	public function countMessages() {
		$query = "SELECT count(*) FROM #__posts";
		return $this->getCount ( $query );
	}

	public function &exportMessages($start = 0, $limit = 0) {
		$query = "SELECT 
			p.post_id AS id, 
			IF(p.post_id=t.topic_first_post_id,0,t.topic_first_post_id) AS parent,
			t.topic_first_post_id AS thread, 
			t.forum_id+1 AS catid, 
			IF(p.post_username, p.post_username, u.username) AS name, 
			p.poster_id AS userid, 
			u.user_email AS email, 
			IF(x.post_subject, x.post_subject, t.topic_title) AS subject, 
			p.post_time AS time, 
			p.poster_ip AS ip, 
			0 AS topic_emoticon,
			(t.topic_status=1 AND p.post_id=t.topic_first_post_id) AS locked, 
			0 AS hold, 
			(t.topic_type>0 AND p.post_id=t.topic_first_post_id) AS ordering, 
			t.topic_views AS hits, 
			t.topic_moved_id AS moved, 
			IF(p.post_edit_time,u.username,'') AS modified_by, 
			p.post_edit_time AS modified_time, 
			'' AS modified_reason, 
			x.post_text AS message 
		FROM `#__posts` AS p 
		LEFT JOIN `#__posts_text` AS x ON p.post_id = x.post_id 
		LEFT JOIN `#__topics` AS t ON p.topic_id = t.topic_id 
		LEFT JOIN `#__users` AS u ON p.poster_id = u.user_id 
		ORDER BY p.post_id";
		$result = $this->getExportData ( $query, $start, $limit );
		// Iterate over all the posts and convert them to Kunena
		foreach ( $result as $key => &$row ) {
			$row->name = prep ( $row->name );
			$row->email = prep ( $row->email );
			$row->subject = prep ( $row->subject );
			$row->modified_reason = prep ( $row->modified_reason );
			$row->message = prep ( $row->message );
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
			'na' AS allowed,
			user_lastvisit AS lasttime,
			'' AS readtopics,
			user_session_time AS currvisit
		FROM #__users WHERE user_lastvisit>0";
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
			w.user_id AS userid
		FROM #__topics_watch AS w 
		LEFT JOIN #__topics AS t ON w.topic_id=t.topic_id";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}

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

}

//--- Function to prepare strings for MySQL storage ---/
function prep($s) {
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
	// #$s = preg_replace('/\[\/code:(.*?):(.*?)\]/', '[/code]', $s);


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

	$s = preg_replace ( '/\[\/url:(.*?)]/', '[/url]', $s );

	$s = preg_replace ( '/\<\/a>/', '', $s );

	# $s = preg_replace('/\\\\/', '', $s);


	$s = addslashes ( $s );

	return $s;
}