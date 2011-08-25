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
 * ccBoard Exporter Class
 *
 * Exports almost all data from ccBoard.
 * @todo Some emoticons are missing
 * @todo Configuration import needs some work
 * @todo Are all topic icons mapped to right images?
 * @todo BBCodes: [LIST=1] and [SIZE=0/7]
 */
class KunenaimporterModelExport_ccBoard extends KunenaimporterModelExport {
	/**
	 * Extension name
	 * @var string
	 */
	public $extname = 'ccboard';
	/**
	 * Display name
	 * @var string
	 */
	public $exttitle = 'ccBoard';
	/**
	 * Minimum required version
	 * @var string or null
	 */
	protected $versionmin = '1.2-RC';
	/**
	 * Maximum accepted version
	 * @var string or null
	 */
	protected $versionmax = null;

	/**
	 * Get ccBoard version
	 */
	public function getVersion() {
		// ccBoard version can be found from ccboard.xml file
		$xml = JPATH_ADMINISTRATOR . "/components/com_{$this->extname}/{$this->extname}.xml";
		if (!JFile::exists ( $xml )) {
			return false;
		}
		$parser = JFactory::getXMLParser ( 'Simple' );
		$parser->loadFile ( $xml );
		return $parser->document->getElementByPath ( 'version' )->data ();
	}

	/**
	 * Get ccBoard configuration
	 */
	public function &getConfig() {
		if (empty($this->config)) {
			require_once (JPATH_ADMINISTRATOR . '/components/com_ccboard/ccboard-config.php');
			$this->config = new ccboardConfig ();
		}
		return $this->config;
	}

	/**
	 * Count total number of user profiles to be exported
	 */
	public function countUserProfile() {
		$query = "SELECT COUNT(*) FROM #__ccb_users";
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
			user_id AS userid,
			'flat' AS view,
			signature AS signature,
			moderator AS moderator,
			NULL AS banned,
			0 AS ordering,
			post_count AS posts,
			avatar AS avatar,
			karma AS karma,
			karma_time AS karma_time,
			1 AS group_id,
			hits AS uhits,
			'' AS personalText,
			gender AS gender,
			NULL AS birthdate,
			location AS location,
			icq AS ICQ,
			NULL AS AIM,
			NULL AS YIM,
			msn AS MSN,
			skype AS SKYPE,
			NULL AS TWITTER,
			NULL AS FACEBOOK,
			jabber AS GTALK,
			NULL AS MYSPACE,
			NULL AS LINKEDIN,
			NULL AS DELICIOUS,
			NULL AS FRIENDFEED,
			NULL AS DIGG,
			NULL AS BLOGSPOT,
			NULL AS FLICKR,
			NULL AS BEBO,
			www AS websitename,
			www AS websiteurl,
			rank AS rank,
			showemail^1 AS hideEmail,
			1 AS showOnline
		FROM #__ccb_users
		ORDER BY userid";
		$result = $this->getExportData ( $query, $start, $limit, 'userid' );

		foreach ( $result as &$row ) {
			if ($row->avatar) {
				$avatar = explode('/', $row->avatar);
				if ($avatar[0] == 'personal') {
					// Full path to the original file
					$row->copypath = JPATH_ROOT . '/components/com_ccboard/assets/avatar/'. $row->avatar;
					$row->avatar = 'users/'.$avatar[1];
				} else {
					$row->avatar = 'gallery/'.$row->avatar;
				}
			}
			$this->parseBBCode ( $row->signature );
			$this->parseText ( $row->personalText );
			$row->gender = $row->gender == 'Male' ? '1' : '2';
			// Parse also all social data
		}
		return $result;
	}

	/**
	 * Count total number of ranks to be exported
	 */
	public function countRanks() {
		$query = "SELECT COUNT(*) FROM #__ccb_ranks";
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
			rank_title AS rank_title,
			rank_min AS rank_min,
			rank_special AS rank_special,
			rank_image AS rank_image
		FROM #__ccb_ranks
		ORDER BY rank_id";
		$result = $this->getExportData ( $query, $start, $limit );
		foreach ( $result as $row ) {
			$this->parseText ( $row->rank_title );
			// Full path to the original file
			$row->copypath = JPATH_ROOT . '/components/com_ccboard/assets/ranks/' . $row->rank_image;
			if ($row->rank_special) {
				if ($row->rank_image == 'ccbadmin.png') $row->rank_image = 'rankadmin.png';
				if ($row->rank_image == 'ccbmoderator.png') $row->rank_image = 'rankmod.png';
			}
		}
		return $result;
	}

	/**
	 * Count total number of categories to be exported
	 */
	public function countCategories() {
		$query = "SELECT COUNT(*) FROM #__ccb_category";
		$count = $this->getCount ( $query );
		$query = "SELECT COUNT(*) FROM #__ccb_forums";
		$count2 = $this->getCount ( $query );
		return $count + $count2;
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
		$query = "SELECT MAX(id) FROM #__ccb_forums";
		$this->ext_database->setQuery ( $query );
		$maxforum = (int) $this->ext_database->loadResult ();

		// Import the categories
		$query = "(SELECT
			id AS id,
			cat_id+{$maxforum} AS parent,
			forum_name AS name,
			0 AS cat_emoticon,
			locked AS locked,
			moderated AS moderated,
			IF(view_for=0 AND post_for=18,0,post_for) AS pub_access,
			1 AS pub_recurse,
			0 AS admin_access,
			1 AS admin_recurse,
			ordering AS ordering,
			published AS published,
			review AS review,
			0 AS allow_anonymous,
			0 AS post_anonymous,
			0 AS hits,
			forum_desc AS description,
			'' AS headerdesc,
			'' AS class_sfx,
			0 AS allow_polls

		FROM #__ccb_forums)
		UNION ALL
		(SELECT
			id+{$maxforum} AS id,
			0 AS parent,
			cat_name AS name,
			0 AS cat_emoticon,
			0 AS locked,
			1 AS moderated,
			0 AS pub_access,
			1 AS pub_recurse,
			0 AS admin_access,
			1 AS admin_recurse,
			ordering AS ordering,
			1 AS published,
			0 AS review,
			0 AS allow_anonymous,
			0 AS post_anonymous,
			0 AS hits,
			'' AS description,
			'' AS headerdesc,
			'' AS class_sfx,
			0 AS allow_polls
		FROM #__ccb_category)
		ORDER BY id";
		$result = $this->getExportData ( $query, $start, $limit, 'id' );
		foreach ( $result as $key => &$row ) {
			$this->parseText ( $row->name );
			$this->parseText ( $row->description );
			$this->parseText ( $row->headerdesc );
			$this->parseText ( $row->class_sfx );
		}
		return $result;
	}

	/**
	 * Count total number of moderator columns to be exported
	 */
	public function countModeration() {
		$query = "SELECT COUNT(*) FROM #__ccb_moderators";
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
		$query = "SELECT
			user_id AS userid,
			forum_id AS catid
		FROM #__ccb_moderators";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}

	/**
	 * Count total number of messages to be exported
	 */
	public function countMessages() {
		$query = "SELECT COUNT(*) FROM #__ccb_posts";
		$count = $this->getCount ( $query );
		return $count;
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
		$config = $this->getConfig();
		$query = "SELECT
			p.id AS id,
			IF(t.start_post_id=p.id,0,t.start_post_id) AS parent,
			t.start_post_id AS thread,
			t.forum_id AS catid,
			p.post_username AS name,
			p.post_user AS userid,
			'' AS email,
			p.post_subject AS subject,
			p.post_time AS time,
			p.ip AS ip,
			t.topic_emoticon AS topic_emoticon,
			IF(t.start_post_id=p.id,t.locked,0) AS locked,
			(t.hold OR p.hold) AS hold,
			0 AS ordering,
			IF(t.start_post_id=p.id,t.hits,0) AS hits,
			0 AS moved,
			p.modified_by AS modified_by,
			p.modified_time AS modified_time,
			p.modified_reason AS modified_reason,
			p.post_text AS message
		FROM #__ccb_posts AS p
		INNER JOIN #__ccb_topics AS t ON p.topic_id=t.id
		ORDER BY p.id";
		$result = $this->getExportData ( $query, $start, $limit );
		foreach ( $result as $key => &$row ) {
			$this->parseText ( $row->name );
			$this->parseText ( $row->email );
			$this->parseText ( $row->subject );
			$this->parseText ( $row->modified_reason );
			if ($config->ccbeditor == 'ccboard') {
				$this->parseBBCode ( $row->message );
			} else {
				$this->parseHTML ( $row->message );
			}
		}
		return $result;
	}

	/**
	 * Count total number of attachments to be exported
	 */
	public function countAttachments() {
		$query = "SELECT COUNT(*) FROM #__ccb_attachments";
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
			a.id AS id,
			a.post_id AS mesid,
			p.post_user AS userid,
			NULL AS hash,
			a.filesize AS size,
			'ccboard' AS folder,
			a.mimetype AS filetype,
			a.real_name AS filename,
			a.ccb_name AS realfile
		FROM #__ccb_attachments AS a
		INNER JOIN #__ccb_posts AS p ON a.post_id=p.id
		ORDER BY a.id";
		$result = $this->getExportData ( $query, $start, $limit, 'id' );
		foreach ( $result as $key => &$row ) {
			$row->copypath = JPATH_ROOT . '/components/com_ccboard/assets/uploads/'.$row->realfile;
		}
		return $result;
	}

	/**
	 * Count global configurations to be exported
	 * @return 1
	 */
	public function countConfig() {
		return 1;
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
			$copypath = JPATH_ROOT.'/components/com_ccboard/assets/avatar';
			$galleries = array();
			$files = JFolder::files($copypath, '\.(?i)(gif|jpg|jpeg|png)$');
			foreach ($files as $file) {
				$galleries[$file] = "{$copypath}/{$file}";
			}
		}
		return $galleries;
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

		$ccBoardConfig = $this->getConfig ();

		// Save HTML/BBCode setting
		$ccBoardConfig->ccbeditor;

		// time delta in seconds from UTC (=JFactory::getDate()->toUnix())
		$config['timedelta'] = JFactory::getDate()->toUnix() - time() - $ccBoardConfig->timeoffset*60*60;

		$config['board_title'] = $ccBoardConfig->boardname;
		// $config['email'] = null;
		$config['board_offline'] = $ccBoardConfig->boardlocked;
		$config['offline_message'] = $ccBoardConfig->lockedmsg;
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
		$config['rtewidth'] = $ccBoardConfig->editorwidth;
		$config['rteheight'] = $ccBoardConfig->editorheight;
		// $config['enableforumjump'] = null;
		// $config['reportmsg'] = null;
		$config['username'] = $ccBoardConfig->showrealname;
		// $config['askemail'] = null;
		// $config['showemail'] = null;
		// $config['showuserstats'] = null;
		$config['showkarma'] = $ccBoardConfig->showkarma;
		// $config['useredit'] = null;
		// $config['useredittime'] = null;
		$config['useredittimegrace'] = $ccBoardConfig->editgracetime;
		$config['editmarkup'] = $ccBoardConfig->showeditmarkup;
		$config['allowsubscriptions'] = $ccBoardConfig->emailsub;
		// $config['subscriptionschecked'] = null;
		$config['allowfavorites'] = $ccBoardConfig->showfavourites;
		$config['maxsubject'] = $ccBoardConfig->subjwidth;
		$config['maxsig'] = $ccBoardConfig->sigmax;
		// $config['regonly'] = null;
		// $config['changename'] = null;
		// $config['pubwrite'] = null;
		// $config['floodprotection'] = null;
		// $config['mailmod'] = null;
		// $config['mailadmin'] = null;
		$config['captcha'] = $ccBoardConfig->showcaptcha > 0 ? 1 : 0;
		// $config['mailfull'] = null;
		// $config['allowavatar'] = null;
		$config['allowavatarupload'] = $ccBoardConfig->avatarupload;
		// $config['allowavatargallery'] = null;
		// $config['avatarquality'] = null;
		$config['avatarsize'] = $ccBoardConfig->avataruploadsize;
		// $config['allowimageupload'] = null;
		// $config['allowimageregupload'] = null;
		// $config['imageheight'] = null;
		// $config['imagewidth'] = null;
		$config['imagesize'] = $ccBoardConfig->fileuploadsize;
		// $config['allowfileupload'] = null;
		// $config['allowfileregupload'] = null;
		// $config['filetypes'] = null;
		$config['filesize'] = $ccBoardConfig->fileuploadsize;
		$config['showranking'] = $ccBoardConfig->showrank;
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
		$config['default_sort'] = $ccBoardConfig->postlistorder;
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
}
