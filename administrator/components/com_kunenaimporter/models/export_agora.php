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

require_once( JPATH_COMPONENT . '/models/export.php' );

/**
 * Agora Exporter Class
 *
 * Exports data from Agora Forum.
 * @todo Configuration import needs some work
 * @todo Forum ACL not exported (except for moderators)
 * @todo Uploaded avatars are not supported
 * @todo Ranks not exported
 * @todo Some emoticons may be missing (images/db are not exported)
 */
class KunenaimporterModelExport_Agora extends KunenaimporterModelExport {
	/**
	 * Extension name without prefix
	 * @var string
	 */
	public $extname = 'agora';
	/**
	 * Display name
	 * @var string
	 */
	public $exttitle = 'Agora';
	/**
	 * Minimum required version
	 * @var string or null
	 */
	protected $versionmin = '3.0.142';
	/**
	 * Maximum accepted version
	 * @var string or null
	 */
	protected $versionmax = null;

	/**
	 * Get configuration
	 */
	public function &getConfig() {
		if (empty($this->config)) {
			$query="SELECT conf_name, conf_value AS value FROM #__agora_config";
			$this->config = $this->getExportData($query, 0, 1000, 'conf_name');
		}
		return $this->config;
	}

	/**
	 * Get component version
	 */
	public function getVersion() {
		$config = $this->getConfig();
		return $config['o_cur_version']->value;
	}

	/**
	 * Convert BBCode to Kunena BBCode
	 *
	 * @param string $s String
	 */
	protected function parseBBCode(&$s) {
		$s = preg_replace ( '/\[s\]/', '[strike]', $s );
		$s = preg_replace ( '/\[\/s\]/', '[/strike]', $s );

		$s = preg_replace ( '/\[justify\]/', '', $s );
		$s = preg_replace ( '/\[\/justify\]/', '', $s );
		
		$s = preg_replace ( '/\[size=[1-9]\]/', '[size=1]', $s );
		$s = preg_replace ( '/\[size=(10|11)\]/', '[size=2]', $s );
		$s = preg_replace ( '/\[size=(12|13)\]/', '[size=3]', $s );
		$s = preg_replace ( '/\[size=(14|15|16|17):(.*?)\]/', '[size=4]', $s );
		$s = preg_replace ( '/\[size=(18|19|20|21|22|23)\]/', '[size=5]', $s );
		$s = preg_replace ( '/\[size=([2-9][0-9])\]/', '[size=6]', $s );
	}

	public function countConfig() {
		return 1;
	}

	public function &exportConfig($start=0, $limit=0) {
		$config = array();
		if ($start) return $config;

		$result = $this->getConfig();
		$config['id'] = 1;
		// $config['board_title'] = null;
		// $config['email'] = null;
		// $config['board_offline'] = null;
		// $config['board_ofset'] = null;
		// $config['offline_message'] = null;
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
		$result = array('1'=>$config);
		return $result;

	}

	public function countCategories() {
		$query="SELECT COUNT(*) FROM #__agora_categories";
		$count = $this->getCount($query);
		$query="SELECT COUNT(*) FROM #__agora_forums";
		return $count + $this->getCount($query);
	}

	public function &exportCategories($start=0, $limit=0) {
		$query = "SELECT MAX(id) FROM #__agora_forums";
		$this->ext_database->setQuery ( $query );
		$maxboard = $this->ext_database->loadResult ();
		// Import the categories
		$query="(SELECT
			cat_name AS name,
			disp_position AS ordering,
			0 AS parent,
			enable AS published,
			NULL AS description,
			NULL AS headerdesc,
			0 AS pub_access,
			1 AS pub_recurse,
			0 AS admin_access,
			1 AS admin_recurse,
			0 AS numTopics,
			0 AS numPosts,
			0 AS id_last_msg,
			id+{$maxboard} AS id
		FROM #__agora_categories) UNION ALL
		(SELECT
			f.forum_name AS name,
			f.sort_by AS ordering,
			cat.id+{$maxboard} AS parent,
			f.enable AS published,
			f.forum_desc AS description,
			f.forum_mdesc AS headerdesc,
			0 AS pub_access,
			1 AS pub_recurse,
			0 AS admin_access,
			1 AS admin_recurse,
			f.num_topics AS numTopics,
			f.num_posts AS numPosts,
			f.last_post_id AS id_last_msg,
			f.id AS id
		FROM #__agora_forums AS f
		LEFT JOIN #__agora_categories AS cat ON f.cat_id=cat.id)
		ORDER BY id";
		$result = $this->getExportData($query, $start, $limit);
		return $result;
	}

	public function countMessages() {
		$query="SELECT COUNT(*) FROM #__agora_posts";
		return $this->getCount ( $query );
	}

	public function &exportMessages($start = 0, $limit = 0) {
		$query = "SELECT
			p.id AS id,
			t.poster AS name,
			IF(t.posted=p.posted,0,p.topic_id) AS parent,
			t.sticky AS ordering,
			t.subject AS subject,
			t.num_views AS hits,
			t.closed AS locked,
			t.forum_id AS catid,
			p.posted AS time,
			p.poster_ip AS ip,
			p.poster_email AS email,
			p.message AS message,
			t.id AS thread,
			p.edited AS modified_time,
			p.edited_by AS modified_by,
			u.jos_id AS userid
		FROM `#__agora_topics` AS t
		LEFT JOIN `#__agora_posts` AS p ON t.id=p.topic_id
		LEFT JOIN `#__agora_users` AS u ON p.poster_id = u.id
		WHERE t.announcements='0'	";
		$result = $this->getExportData ( $query, $start, $limit, 'id' );

		foreach ( $result as &$row ) {
			$this->parseBBCode( $row->message );
		}
		return $result;
	}

	public function countSmilies() {
		return false;

		$query="SELECT COUNT(*) FROM #__agora_smilies";
		return $this->getCount($query);
	}

	public function &exportSmilies($start=0, $limit=0)
	{
		$query="SELECT image AS location, text FROM #__agora_smilies";
		$result = $this->getExportData($query, $start, $limit);
		foreach ( $result as $smiley ) {
			// Full path to the original file
			$rank->copyfile = JPATH_ROOT . "/components/components/com_agora/img/smilies/{$smiley->image}";
		}
		return $result;
	}

	public function countRanks() {
		return false;

		$query="SELECT COUNT(*) FROM #__agora_ranks";
		return $this->getCount($query);
	}

	public function &exportRanks($start=0, $limit=0)
	{
		$query="SELECT
			rank AS rank_title,
			min_posts AS rank_min,
			image AS rank_image,
			user_type AS rank_special
		FROM #__agora_ranks";
		$result = $this->getExportData($query, $start, $limit);
		foreach ( $result as $rank ) {
			$this->parseText ( $row->rank_title );
			// Full path to the original file
			$rank->copyfile = JPATH_ROOT . "/components/components/com_agora/img/ranks/{$rank->rank_image}";
		}
		return $result;
	}

	public function countUserprofile() {
		$query="SELECT COUNT(*) FROM #__agora_users";
		return $this->getCount($query);
	}

	public function &exportUserprofile($start=0, $limit=0) {
		$query="SELECT
			id,
			jos_id AS userid,
			group_id,
			url AS websiteurl,
			icq AS ICQ,
			msn AS MSN,
			aim AS AIM,
			yahoo AS YAHOO,
			skype AS SKYPE,
			location,
			signature,
			url AS websiteurl,
			gender,
			IF(birthday,FROM_UNIXTIME(birthday, '%Y-%m-%d'),'0001-01-01' ) AS birthdate,
			aboutme AS personnalText,
			num_posts AS posts
		FROM #__agora_users";
		$result = $this->getExportData($query, $start, $limit);
		foreach ( $result as $profile ) {
			if ( JFile::exists(JPATH_ROOT . '/components/com_agora/img/pre_avatars/'. $profile->id.'.png') ) {
				$avatar_path = JPATH_ROOT . '/components/com_agora/img/pre_avatars/'. $profile->id.'.png';
				$avatar = $profile->id.'.png';
			} elseif ( JFile::exists(JPATH_ROOT . '/components/com_agora/img/pre_avatars/'. $profile->id.'.jpg') ) {
				$avatar_pathr = JPATH_ROOT . '/components/com_agora/img/pre_avatars/'. $profile->id.'.jpg';
				$avatar = $profile->id.'.jpg';
			} elseif ( JFile::exists(JPATH_ROOT . '/components/com_agora/img/pre_avatars/'. $profile->id.'.gif') ) {
				$avatar_path = JPATH_ROOT . '/components/com_agora/img/pre_avatars/'. $profile->id.'.gif';
				$avatar = $profile->id.'.gif';
			} else {
				$avatar_path = '';
				$avatar = '';
			}
			$profile->avatar = $avatar;
			$profile->copypath =  $avatar_path;
		}
		return $result;
	}

	public function countPolls() {
		$query="SELECT COUNT(*) FROM #__agora_polls";
		return $this->getCount($query);
	}

	public function &exportPolls($start=0, $limit=0) {
		$query="SELECT
			p.pollid AS id,
			t.id AS threadid,
			t.question AS title
		FROM #__agora_polls AS p
		LEFT JOIN #__agora_topics AS t ON p.pollid=t.id";
		$result = $this->getExportData($query, $start, $limit);

		return $result;
	}

	/**
	 * Count total poll options to be exported
	 */
	public function countPollsOptions() {
		$query="SELECT COUNT(*) FROM #__agora_polls";
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
			p.id,
			t.id AS pollid,
			p.votes,
			p.options
		FROM #__agora_polls AS p
		LEFT JOIN #__agora_topics AS t ON p.pollid=t.id";
		$tlp = $this->getExportData($query, $start, $limit);

		$array = array();
		$y = 0;

		foreach ($tlp as $item) {
			$array[$y]['pollid'] = $item->pollid;
			$array[$y]['options'] = unserialize($item->options);
			$array[$y]['votes'] = unserialize($item->votes);

			$y++;
		}

		$result = array();

		foreach($array as $it) {
			$tmp = array();
			foreach($it['votes'] as $key=>$value) {
				$tmp[$key] = $value;
			}

			foreach($it['options'] as $key=>$value) {
				$opt = new stdClass();
				$opt->pollid = $it['pollid'];
				$opt->text  = $value;
				if (isset($tmp[$key])) $opt->votes = $tmp[$key];
				$result[] = $opt;
			}
		}

		$i =1;
		foreach($result as $item) {
			$item->id = $i;
			$i++;
		}

		return $result;
	}

	public function countSubscriptions() {
		$query = "SELECT COUNT(*) FROM #__agora_subscriptions";
		return $this->getCount ( $query );
	}

	public function &exportSubscriptions($start = 0, $limit = 0) {
		$query = "SELECT
			w.topic_id AS thread,
			u.jos_id AS userid,
			w.emailed AS future1
		FROM #__agora_subscriptions AS w
		INNER JOIN #__agora_users AS u ON w.user_id=u.id";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}

	public function countBans() {
		$query = "SELECT COUNT(*) FROM `#__agora_bans`";
		return $this->getCount ( $query );
	}

	public function &exportBans($start = 0, $limit = 0) {
		$query = "SELECT
			ban.id AS id,
			ban.ip AS ip,
			u.jos_id AS userid,
			ban.message AS comments,
			ban.expire AS expiration
		FROM `#__agora_bans` AS ban
		LEFT JOIN `#__agora_users` AS u ON ban.username=u.username";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}

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
		$galleries = array_slice($this->getAvatarGalleries(), $start, $limit);
		return $galleries;
	}

	/**
	 * Internal function to fetch all avatar galleries
	 *
	 * @return array (folder=>full path, ...)
	 */
	protected function &getAvatarGalleries() {
		static $galleries = false;
		if ($galleries === false) {
			$path = JPATH_ROOT.'/components/com_agora/img/pre_avatars';
			$galleries = array();
			$folders = JFolder::folders($path);
			foreach ($folders as $folder) {
				$galleries[$folder] = "{$path}/{$folder}";
			}
		}
		return $galleries;
	}
}