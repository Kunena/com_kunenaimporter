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

class KunenaimporterModelExport_Ninjaboard extends KunenaimporterModelExport {
	/**
	 * Extension name ([a-z0-9_], wihtout 'com_' prefix)
	 * @var string
	 */
	public $name = 'ninjaboard';
	/**
	 * Display name
	 * @var string
	 */
	public $title = 'NinjaBoard';
	/**
	 * Minimum required version
	 * @var string or null
	 */
	protected $versionmin = '1.0';
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
		// Version can usually be found from <name>.xml file
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
		$importOps = array();
		$importOps ['config'] = array ('count' => 'countConfig', 'export' => 'exportConfig' );
		$importOps ['categories'] = array('count'=>'countCategories', 'export'=>'exportCategories');
		$importOps ['messages'] = array ('count' => 'countMessages', 'export' => 'exportMessages' );
		$importOps ['subscriptions'] = array ('count' => 'countSubscriptions', 'export' => 'exportSubscriptions' );
		$importOps ['userprofile'] = array ('count' => 'countUserprofile', 'export' => 'exportUserprofile' );
		$this->importOps = $importOps;
	}

	public function countConfig() {
		return 1;
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

	public function countCategories() {
		$query="SELECT COUNT(*) FROM #__ninjaboard_forums";
		$count = $this->getCount($query);
		return $count;
	}

	public function &exportCategories($start=0, $limit=0) {
		// Import the forums - Ninjaboard does not store separate categories
		$query="(SELECT
			enabled AS published,
			title AS name,
			description AS description,
			/* forum_mdesc AS headerdesc, TODO: extract description ouf of param */
			topics AS numTopics,
			posts AS numPosts,
			last_post_id AS id_last_msg,
			ninjaboard_forum_id AS id,
			parent_id AS parent
		FROM #__ninjaboard_forums)
		ORDER BY id";
		$result = $this->getExportData($query, $start, $limit);
		foreach ($result as $key=>&$row) {
			$row->name = prep($row->name);
			$row->description = prep($row->description);
		}
		return $result;
	}

	public function countMessages() {
		$query = "SELECT COUNT(*) FROM #__ninjaboard_messages";
		return $this->getCount ( $query );
	}

	public function &exportMessages($start = 0, $limit = 0) {
/* TODO: Build ninjaboard logic 
 		$query = "SELECT
			t.id AS id,
			t.poster AS name,
			IF(p.topic_id=t.id,0,p.topic_id) AS parent,
			t.sticky AS ordering,
			t.subject AS subject,
			t.num_views AS hits,
			t.closed AS locked,
			t.forum_id AS catid,
			u.jos_id AS userid,
			p.poster_ip AS ip,
			p.poster_email AS email,
			p.message AS message,
			p.posted AS time,
			p.topic_id AS thread
			p.edited AS modified_time,
			p.edited_by AS modified_by

			FROM `#__agora_topics` AS t
			LEFT JOIN `#__agora_posts` AS p ON p.topic_id = t.id
			LEFT JOIN `#__agora_users` AS u ON p.poster_id = u.id
			WHERE t.announcements='0'
			ORDER BY t.id";
*/
		$result = $this->getExportData ( $query, $start, $limit, 'id' );
		foreach ( $result as &$row ) {
			$row->subject = $this->prep ( $row->subject );
			$row->message = $this->prep ( $row->message );
		}
		return $result;
	}

	/* TODO: CHeck if smilies and ranks are available outside of db
	public function countSmilies() {
		return false;

		$query="SELECT COUNT(*) FROM #__agora_smilies";
		return $this->getCount($query);
	}

	public function &exportSmilies($start=0, $limit=0)
	{
		$query="SELECT image AS location, text FROM #__agora_smilies";
		$result = $this->getExportData($query, $start, $limit);
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
		return $result;
	}
*/
	public function countUserprofile() {
		$query="SELECT COUNT(*) FROM #__ninjaboard_people";
		return $this->getCount($query);
	}

	public function &exportUserprofile($start=0, $limit=0) {
		$query="SELECT
			ninjaboard_person_id AS user_id,
			signature,
			posts AS posts,
			avatar
		FROM #__ninjaboard_people";
		$result = $this->getExportData($query, $start, $limit);
		foreach ( $result as $key => &$row ) {
			//$row->copypath = JPATH_BASE . '/components/com_agora/img/pre_avatars/'. $row->id;
		}
	}

	public function countSubscriptions() {
		$query = "SELECT COUNT(*) FROM `#__ninjaboard_subscriptions`
					WHERE subscription_type=3";
		return $this->getCount ( $query );
	}

	public function &exportSubscriptions($start = 0, $limit = 0) {
		$query = "SELECT
			subscription_type_id AS thread,
			created_by AS userid
		FROM `#__ninjaboard_subscriptions`
		WHERE subscription_type=3";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}

	protected function prep($s) {
		return $s;
	}
}