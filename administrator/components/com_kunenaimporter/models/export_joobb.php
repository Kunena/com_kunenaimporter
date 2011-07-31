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

class KunenaimporterModelExport_JooBB extends KunenaimporterModelExport {
	/**
	 * Extension name ([a-z0-9_], wihtout 'com_' prefix)
	 * @var string
	 */
	public $name = 'joobb';
	/**
	 * Display name
	 * @var string
	 */
	public $title = 'Joo!BB';
	/**
	 * Minimum required version
	 * @var string or null
	 */
	protected $versionmin = '1.0.0';
	/**
	 * Maximum accepted version
	 * @var string or null
	 */
	protected $versionmax = null;

	public function countConfig() {
		return 1;
	}

	/**
	 * Get component version
	 */
	public function getVersion() {
		$query = "SELECT version FROM `#__joobb_updates`";
		$this->ext_database->setQuery ( $query );
		return substr ( $this->ext_database->loadResult (),0 ,5);
	}

	public function &exportConfig($start=0, $limit=0) {
		$config = array();
		if ($start) return $config;

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

	/**
	 * Count total number of categories to be exported
	 */
	public function countCategories() {
		$query = "SELECT COUNT(*) FROM #__joobb_categories";
		$count = $this->getCount ( $query );
		$query="SELECT COUNT(*) FROM #__joobb_forums";
		return $count + $this->getCount($query);
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
		$query = "SELECT MAX(id) FROM #__joobb_forums";
		$this->ext_database->setQuery ( $query );
		$maxboard = $this->ext_database->loadResult ();
		$query = "(SELECT
			id+{$maxboard} AS id,
			name AS name,
			0 AS parent,
			published,
			NULL AS description,
			0 AS locked,
			'' AS headerdesc,
			ordering AS ordering,
			0 AS pub_access,
			1 AS pub_recurse,
			0 AS admin_access,
			1 AS admin_recurse,
			0 AS numTopics,
			0 AS numPosts,
			0 AS id_last_msg
		FROM #__joobb_categories) UNION ALL
		(SELECT
			f.id AS id,
			f.name AS name,
			cat.id+{$maxboard} AS parent,
			f.status AS published,
			f.description AS description,
			f.locked AS locked,
			'' AS headerdesc,
			f.ordering AS ordering,
			0 AS pub_access,
			1 AS pub_recurse,
			0 AS admin_access,
			1 AS admin_recurse,
			f.topics AS numTopics,
			f.posts AS numPosts,
			f.id_last_post AS id_last_msg
		FROM #__joobb_forums AS f
		LEFT JOIN #__joobb_categories AS cat ON f.id_cat=cat.id)
		ORDER BY id";
		echo $maxboard;
		die();
		$result = $this->getExportData ( $query, $start, $limit );
		foreach ( $result as $key => &$row ) {
			$this->parseText ( $row->name );
			$this->parseBBCode ( $row->description );
			$this->parseBBCode ( $row->headerdesc );
			$this->parseText ( $row->class_sfx );
		}
		return $result;
	}

	/**
	 * Count total number of messages to be exported
	 */
	public function countMessages() {
		$query = "SELECT COUNT(*) FROM #__joobb_posts";
		$count = $this->getCount ( $query );
		return $count;
	}

	public function &exportMessages($start = 0, $limit = 0) {
		$query = "SELECT
			p.id AS id,
			u.username AS name,
			IF(t.id_first_post=p.id,0,t.id_first_post) AS parent,
			p.subject AS subject,
			t.views AS hits,
			p.id_forum AS catid,
			UNIX_TIMESTAMP(p.date_post) AS time,
			p.ip_poster AS ip,
			NULL AS email,
			p.text AS message,
			t.id_first_post AS thread,
			UNIX_TIMESTAMP(p.date_last_edit) AS modified_time,
			p.id_user_last_edit AS modified_by,
			p.id_user AS userid
		FROM `#__joobb_topics` AS t
		LEFT JOIN `#__joobb_posts` AS p ON t.id=p.id_topic
		LEFT JOIN `#__users` AS u ON u.id=p.id_user";

		$result = $this->getExportData ( $query, $start, $limit, 'id' );


		return $result;
	}

	/**
	 * Count total number of ranks to be exported
	 */
	public function countRanks() {
		$query = "SELECT COUNT(*) FROM #__joobb_ranks";
		$count = $this->getCount ( $query );
		return $count;
	}

	/**
	 * Export user ranks
	 *
	 * Returns list of rank objects containing database fields
	 * to #__kunena_ranks.
	 * NOTE: copies all files found in $row->copyfile (full path) to Kunena.
	 *
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportRanks($start = 0, $limit = 0) {
		$query = "SELECT
			id AS rank_id,
			name AS rank_title,
			min_posts AS rank_min,
			0 AS rank_special,
			rank_file AS rank_image
		FROM #__joobb_ranks
		ORDER BY rank_id";
		$result = $this->getExportData ( $query, $start, $limit );
		foreach ( $result as $rank ) {
			$this->parseText ( $row->rank_title );
			// Full path to the original file
			$rank->copyfile = JPATH_ROOT . "/components/components/com_joobb/assets/templates/joobb/themes/{$rank->rank_image}";
		}
		return $result;
	}

	/**
	 * Count total number of attachments to be exported
	 */
	public function countAttachments() {
		$query = "SELECT COUNT(*) FROM #__joobb_attachments";
		$count = $this->getCount ( $query );
		return $count;
	}

	/**
	 * Export attachments in messages
	 *
	 * Returns list of attachment objects containing database fields
	 * to #__kunena_attachments.
	 * NOTE: copies all files found in $row->copyfile (full path) to Kunena.
	 *
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportAttachments($start = 0, $limit = 0) {
		$query = "SELECT
			id AS id,
			id_post AS mesid,
			id_user AS userid,
			NULL AS hash,
			NULL AS size,
			NULL AS folder,
			NULL AS filetype,
			file_name AS filename
		FROM #__joobb_attachments
		ORDER BY id";
		$result = $this->getExportData ( $query, $start, $limit, 'id' );
		$copypath = JPATH_ROOT.'/media/joobb/attachments';
		foreach ( $result as &$row ) {
			// Full path to the original file
			$row->copyfile = "{$copypath}/{$row->filename}";
		}
		return $result;
	}

	/**
	 * Count total number of subscription items to be exported
	 */
	public function countSubscriptions() {
		$query = "SELECT COUNT(*) FROM #__joobb_topics_subscriptions";
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
		// future1 = notify status (1=message sent)
		$query = "SELECT
			id_topic AS thread,
			id_user AS userid,
			0 AS future1
		FROM #__joobb_topics_subscriptions";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}

	/**
	 * Count total number of user profiles to be exported
	 */
	public function countUserProfile() {
		$query = "SELECT COUNT(*) FROM #__joobb_users";
		$count = $this->getCount ( $query );
		return $count;
	}

	/**
	 * Export user profiles
	 *
	 * Returns list of user profile objects containing database fields
	 * to #__kunena_users.
	 * NOTE: copies all files found in $row->copyfile (full path) to Kunena.
	 *
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportUserProfile($start = 0, $limit = 0) {
		$query = "SELECT
			u.id AS userid,
			'flat' AS view,
			ucm.signature AS signature,
			0 AS moderator,
			NULL AS banned,
			0 AS ordering,
			u.posts,
			av.avatar_file AS avatar,
			0 AS karma,
			0 AS karma_time,
			ucm.views_count AS uhits,
			'' AS personalText,
			0 AS gender,
			NULL AS birthdate,
			p.p_town AS location,
			p.p_icq AS ICQ,
			p.p_aim AS AIM,
			p.p_yim AS YIM,
			p.p_msnm AS MSN,
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
			NULL AS websiteurl,
			NULL AS rank,
			ucm.show_email AS hideEmail,
			ucm.show_online_state AS showOnline
		FROM #__joobb_users AS u
		LEFT JOIN #__joocm_avatars AS av ON u.id=av.id_user
		LEFT JOIN #__joocm_profiles AS p ON p.id=u.id
		LEFT JOIN #__joocm_users AS ucm ON ucm.id=u.id
		ORDER BY userid";
		$result = $this->getExportData ( $query, $start, $limit, 'userid' );

		foreach ( $result as &$row ) {
			if ($row->avatar) {
				// Full path to the original file
				$row->copyfile = JPATH_ROOT . "/media/joocm/{$row->userid}/{$row->avatar}";
			}
			$this->parseBBCode ( $row->signature );
			$this->parseText ( $row->personalText );
			// Parse also all social data
		}
		return $result;
	}

}