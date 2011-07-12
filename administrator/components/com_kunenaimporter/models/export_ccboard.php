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

class KunenaimporterModelExport_ccBoard extends KunenaimporterModelExport {
	/**
	 * Extension name ([a-z0-9_], wihtout 'com_' prefix)
	 * @var string
	 */
	public $name = 'ccboard';
	/**
	 * Display name
	 * @var string
	 */
	public $title = 'ccBoard';
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
	 * Full detection and initialization
	 * 
	 * Make sure that everything is ready for full import.
	 * Use $this->addMessage($html) to add status messages.
	 * If you return false, remember also to fill $this->error
	 * 
	 * @return bool
	 */
	public function detect() {
		// Initialize detection (also calls $this->detectComponent())
		if (!parent::detect()) return false;

		// Check if version is compatible with importer
		$this->version = $this->getVersion();
		if (!parent::isCompatible($this->version)) return false;
		return true;
	}

	/**
	 * Detect if component exists
	 * 
	 * By default this function uses Joomla function to detect components.
	 * 
	 * @param mixed $success Force detection to succeed/fail
	 * @return bool
	 */
	public function detectComponent($success=null) {
		// Set $success = true/false if you want to use custom detection
		return parent::detectComponent($success);
	}

	/**
	 * Get component version
	 */
	public function getVersion() {
		// ccBoard version can be found from ccboard.xml file
		$xml = JPATH_ADMINISTRATOR . "/components/com_{$this->name}/{$this->name}.xml";
		if (!JFile::exists ( $xml )) {
			return false;
		}
		$parser = JFactory::getXMLParser ( 'Simple' );
		$parser->loadFile ( $xml );
		return $parser->document->getElementByPath ( 'version' )->data ();
	}

	public function buildImportOps() {
		// query: (select, from, where, groupby), functions: (count, export)
		$importOps = array ();
		$importOps ['categories'] = array ('count' => 'countCategories', 'export' => 'exportCategories' );
		$importOps ['config'] = array ('count' => 'countConfig', 'export' => 'exportConfig' );
		$importOps ['attachments'] = array ('count' => 'countAttachments', 'export' => 'exportAttachments' );
		$importOps ['moderation'] = array ('count' => 'countModeration', 'export' => 'exportModeration' );
		$importOps ['messages'] = array ('count' => 'countMessages', 'export' => 'exportMessages' );
		$importOps ['ranks'] = array ('count' => 'countRanks', 'export' => 'exportRanks' );
		$importOps ['userprofile'] = array ('count' => 'countUserprofile', 'export' => 'exportUserprofile' );
		$this->importOps = $importOps;
	}

	public function countCategories() {
		$query = "SELECT COUNT(*) FROM #__ccb_category";
		$count = $this->getCount ( $query );
		$query = "SELECT COUNT(*) FROM #__ccb_forums";
		$count2 = $this->getCount ( $query );
		return $count + $count2;
	}

	public function &exportCategories($start = 0, $limit = 0) {
		$query = "SELECT MAX(id) FROM #__ccb_category";
		$this->ext_database->setQuery ( $query );
		$maxboard = $this->ext_database->loadResult ();
		// Import the categories
		$query = "(SELECT
			id AS id,
			forum_name AS name,
			forum_desc AS description,
			moderated,
			0 AS parent,
			topic_count AS numTopics,
			post_count AS numPosts,
			last_post_user,
			last_post_time AS time_last_msg,
			last_post_id AS id_last_msg,
			published,
			locked,
			ordering,
			moderated,
			review
		FROM #__ccb_forums) UNION ALL (SELECT
			cat_id+{$maxboard} AS id,
			cat.cat_name AS name,
			NULL AS description,
			0 AS moderated,
			0 AS numTopics,
			0 AS numPosts,
			0 AS last_post_user,
			0 AS time_last_msg,
			0 AS id_last_msg,
			IF(cat.id=f.cat_id,cat.id,0) AS parent,
			cat.ordering,
			1 AS published,
			0 AS locked,
			0 AS moderated,
			0 AS review
		FROM #__ccb_category AS cat
		LEFT JOIN #__ccb_forums AS f ON cat.id=f.cat_id)";
		$result = $this->getExportData ( $query, $start, $limit );
		foreach ( $result as $key => &$row ) {
			$row->name = $this->prep ( $row->name );
			$row->pub_access = 0;
			$row->description = $this->prep ( $row->description );
		}
		return $result;
	}

	public function countConfig() {
		return 1;
	}

	public function &exportConfig($start = 0, $limit = 0) {
		require_once (JPATH_ADMINISTRATOR . '/components/com_ccboard/ccboard-config.php');

		$ccBoardConfig = new ccboardConfig ();

		$config['id'] = 1;
		$config['board_title'] = $ccBoardConfig->boardname;
		// $config['email'] = null;
		$config['board_offline'] = $ccBoardConfig->boardlocked;
		$config['board_ofset'] = $ccBoardConfig->timeoffset;
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

	public function countAttachments() {
		$query = "SELECT COUNT(*) FROM #__ccb_attachments";
		$count = $this->getCount ( $query );
		return $count;
	}

	public function &exportAttachments($start = 0, $limit = 0) {
		$query = "SELECT
			post_id AS mesid,
			ccb_name AS userid,
			filesize AS size,
			real_name AS filename,
			mimetype AS filetype
		FROM #__ccb_attachments";
		$result = $this->getExportData ( $query, $start, $limit );
		foreach ( $result as $key => &$row ) {
			$row->copypath = JPATH_BASE.'/components/com_ccboard/assets/uploads/'.$row->ccb_name ;
		}
		return $result;
	}

	public function countModeration() {
		$query = "SELECT COUNT(*) FROM #__ccb_moderators";
		$count = $this->getCount ( $query );
		return $count;
	}

	public function &exportModeration($start = 0, $limit = 0) {
		$query = "SELECT user_id AS userid, forum_id AS catid FROM #__ccb_moderators";
		$result = $this->getExportData ( $query, $start, $limit );

		return $result;
	}

	public function countRanks() {
		$query = "SELECT COUNT(*) FROM #__ccb_ranks";
		$count = $this->getCount ( $query );
		return $count;
	}

	public function &exportRanks($start = 0, $limit = 0) {
		$query = "SELECT rank_title, rank_min, rank_special, rank_image FROM #__ccb_ranks";
		$result = $this->getExportData ( $query, $start, $limit );
		foreach ( $result as $rank ) {
			$rank->copypath = JPATH_BASE . '/components/com_ccboard/assets/ranks/' . $rank->rank_image;
		}

		return $result;
	}

	public function countMessages() {
		$query = "SELECT COUNT(*) FROM #__ccb_posts";
		$count = $this->getCount ( $query );
		return $count;
	}

	public function &exportMessages($start = 0, $limit = 0) {
		$query = "SELECT
			ccposts.id,
			ccposts.topic_id AS thread,
			ccposts.forum_id AS catid,
			ccposts.post_subject AS subject,
			ccposts.post_text AS message,
			ccposts.post_user AS userid,
			ccposts.post_time AS time,
			ccposts.ip,
			ccposts.hold,
			ccposts.modified_by,
			ccposts.modified_time,
			ccposts.modified_reason,
			ccposts.post_username AS name,
			cctopics.id,
			cctopics.forum_id,
			cctopics.post_subject,
			cctopics.reply_count,
			cctopics.hits,
			cctopics.post_time,
			cctopics.post_user,
			cctopics.last_post_time,
			cctopics.last_post_id,
			cctopics.last_post_user,
			cctopics.start_post_id,
			cctopics.topic_type,
			cctopics.locked,
			cctopics.topic_email,
			cctopics.hold,
			cctopics.topic_emoticon,
			cctopics.post_username,
			cctopics.last_post_username,
			cctopics.topic_favourite
		FROM #__ccb_posts AS ccposts
		LEFT JOIN #__ccb_topics AS cctopics ON ccposts.topic_id=cctopics.id";
		$result = $this->getExportData ( $query, $start, $limit );

		return $result;
	}

	public function countUserprofile() {
		$query = "SELECT COUNT(*) FROM #__ccb_users";
		$count = $this->getCount ( $query );
		return $count;
	}

	public function &exportUserprofile($start = 0, $limit = 0) {
		$query = "SELECT
			user_id AS userid,
			location,
			signature,
			avatar,
			rank,
			post_count AS posts,
			gender,
			www,icq AS ICQ,
			aol AS AOL,
			msn AS MSN,
			yahoo AS YAHOO,
			jabber AS GTALK,
			skype AS SKYPE,
			showemail AS hideEmail,
			moderator,
			karma,
			karma_time,
			hits AS uhits
		FROM #__ccb_users";
		$result = $this->getExportData ( $query, $start, $limit );
		foreach ( $result as $key => &$row ) {
			$row->copypath = JPATH_BASE . '/components/com_ccboard/assets/avatar/'. $row->avatar;
			$row->signature = $this->prep ( $row->signature );
			$row->gender = $row->gender == 'Male' ? '1' : '2';
		}
		return $result;
	}

	protected function prep($s) {
		return $s;
	}

}
