<?php
/**
 * Kunena Importer component
 * @package Kunena.com_kunenaimporter
 *
 * @copyright (C) 2008 - 2012 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

require_once( JPATH_COMPONENT . '/models/export.php' );

class KunenaimporterModelExport_Ninjaboard extends KunenaimporterModelExport {
	/**
	 * Extension name ([a-z0-9_], wihtout 'com_' prefix)
	 * @var string
	 */
	public $extname = 'ninjaboard';
	/**
	 * Display name
	 * @var string
	 */
	public $exttitle = 'NinjaBoard';
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

	public function countConfig() {
		return 1;
	}

	/**
	 * Get NinjaBoard version
	 */
	public function getVersion() {
		// NinjaBoard version can be found from manifest.xml file
		$xml = JPATH_ADMINISTRATOR . "/components/com_{$this->extname}/manifest.xml";
		if (!JFile::exists ( $xml )) {
			return false;
		}
		$parser = JFactory::getXMLParser ( 'Simple' );
		$parser->loadFile ( $xml );
		return $parser->document->getElementByPath ( 'version' )->data ();
	}

	/**
	 * Get Ninkaboard configuration
	 */
	public function &getConfig() {
		if (empty($this->config)) {
			$query = "SELECT params FROM `#__ninjaboard_settings` WHERE enabled = '1'";
			$this->ext_database->setQuery ( $query );
			$config = $this->ext_database->loadResult ();

			$config = json_decode($config, true);

			$config_options = new stdclass();

			foreach($config as $value) {
				foreach($value as $key=>$opt) {
					$config_options->$key=$opt;
				}
			}

			if ( !empty($config_options) ) $this->config = $config_options;
			else $this->config = '';
		}
		return $this->config;
	}

	public function &exportConfig($start=0, $limit=0) {
		$config = array();
		if ($start) return $config;

		$NinjaBoardConfig = $this->getConfig ();

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
		$config['username'] = $NinjaBoardConfig->display_name=='username' ? 1 : 0;
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
		$config['allowimageupload'] = $NinjaBoardConfig->enable_attachments;
		// $config['allowimageregupload'] = null;
		// $config['imageheight'] = null;
		// $config['imagewidth'] = null;
		// $config['imagesize'] = null;
		// $config['allowfileupload'] = null;
		$config['allowfileregupload'] = $NinjaBoardConfig->enable_attachments;
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
		$config['usernamechange'] = $NinjaBoardConfig->change_display_name=='custom' ? 1 : 0;
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
			ninjaboard_forum_id AS id,
			enabled AS published,
			title AS name,
			description AS description,
			/* forum_mdesc AS headerdesc, TODO: extract description ouf of param */
			topics AS numTopics,
			ordering,
			posts AS numPosts,
			last_post_id AS id_last_msg,
			ninjaboard_forum_id AS id,
			path AS parent_id
		FROM #__ninjaboard_forums)
		ORDER BY id";
		$result = $this->getExportData($query, $start, $limit);
		foreach ($result as $key=>&$row) {
			if( $row->parent_id=='/' ) {
				$row->parent_id='0';
			} else {
				$row->parent_id=preg_replace('#/?#','',$row->parent_id);
			}
			$row->name = $this->prep($row->name);
			$row->description = $this->prep($row->description);
		}
		return $result;
	}

	public function countMessages() {
		$query = "SELECT COUNT(*) FROM #__ninjaboard_messages";
		return $this->getCount ( $query );
	}

	public function &exportMessages($start = 0, $limit = 0) {
		// TODO: in replies the subject is empty, need to find a way to fill it
 		$query = "SELECT
 			p.ninjaboard_post_id AS id,
			IF(p.ninjaboard_post_id=t.ninjaboard_topic_id,0,t.ninjaboard_topic_id) AS parent,
			t.first_post_id AS thread,
			t.forum_id AS catid,
			u.username AS name,
			p.subject,
			p.text AS message,
			UNIX_TIMESTAMP(p.created_time) AS time,
			p.created_user_id AS userid,
			p.modified_user_id AS modified_by,
			UNIX_TIMESTAMP(p.modified) AS modified_time,
			p.edit_reason AS modified_reason,
			p.user_ip AS ip,
			p.locked,
			p.ninjaboard_topic_id AS topic_emoticon
			FROM #__ninjaboard_posts AS p
			LEFT JOIN #__ninjaboard_topics AS t ON t.ninjaboard_topic_id=p.ninjaboard_topic_id
			LEFT JOIN #__users AS u ON u.id=p.created_user_id";
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
	}*/

	public function countRanks() {
		return false;

		$query="SELECT COUNT(*) FROM #__ninjaboard_ranks";
		return $this->getCount($query);
	}

	public function &exportRanks($start=0, $limit=0)
	{
		$query="SELECT
			title AS rank_title,
			min AS rank_min,
			rank_file AS rank_image
		FROM #__ninjaboard_ranks";
		$result = $this->getExportData($query, $start, $limit);
		foreach ( $result as $rank ) {
			$this->parseText ( $row->rank_title );
			// Full path to the original file
			$rank->copyfile = JPATH_ROOT . "/media/com_ninjaboard/images/rank/{$rank->rank_image}";
		}
		return $result;
	}

	public function countUserprofile() {
		$query="SELECT COUNT(*) FROM #__ninjaboard_people";
		return $this->getCount($query);
	}

	public function &exportUserprofile($start=0, $limit=0) {
		$query="SELECT
			ninjaboard_person_id AS userid,
			signature,
			posts AS posts,
			avatar,
			'flat' AS view,
			'' AS signature,
			0 AS moderator,
			NULL AS banned,
			0 AS ordering,
			0 AS karma,
			0 AS karma_time,
			0 AS uhits,
			'' AS personalText,
			0 AS gender,
			NULL AS birthdate,
			NULL AS location,
			NULL AS ICQ,
			NULL AS AIM,
			NULL AS YIM,
			NULL AS MSN,
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
			0 AS hideEmail,
			1 AS showOnline
		FROM #__ninjaboard_people";
		$result = $this->getExportData($query, $start, $limit);
		foreach ( $result as $key => &$row ) {
		  if ( !empty($row->avatar) ) $row->copypath = JPATH_ROOT . $row->avatar;
		}
		return $result;
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

	/**
	 * Count total number of attachments to be exported
	 */
	public function countAttachments() {
		$query = "SELECT COUNT(*) FROM #__ninjaboard_attachments";
		$count = $this->getCount ( $query );
		return $count;
	}

	/**
	 * Export attachments in messages
	 *
	 *
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportAttachments($start = 0, $limit = 0) {
		$query = "SELECT
			ninjaboard_attachment_id AS id,
			file AS filname,
			joomla_user_id AS userid,
			post_id AS mesid,
			NULL AS hash,
			NULL AS size,
			NULL AS folder,
			NULL AS filetype
		FROM #__ninjaboard_attachments
		ORDER BY id";
		$result = $this->getExportData ( $query, $start, $limit, 'id' );
		$copypath = JPATH_ROOT.'/media/com_ninjaboard/attachments';
		foreach ( $result as &$row ) {
			// Full path to the original file
			$row->copyfile = "{$copypath}/{$row->filename}";
		}
		return $result;
	}

	protected function prep($s) {
		return $s;
	}
}