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

class KunenaimporterModelExport_Discussions extends KunenaimporterModelExport {
	/**
	 * Extension name ([a-z0-9_], wihtout 'com_' prefix)
	 * @var string
	 */
	public $extname = 'discussions';
	/**
	 * Display name
	 * @var string
	 */
	public $exttitle = 'Discussions';
	/**
	 * Minimum required version
	 * @var string or null
	 */
	protected $versionmin = '1.3';
	/**
	 * Maximum accepted version
	 * @var string or null
	 */
	protected $versionmax = null;

	/**
	 * Get Discussions version
	 */
	public function getVersion() {
		$query = "SELECT version FROM `#__discussions_meta` WHERE id='1'";
		$this->ext_database->setQuery ( $query );
		return $this->ext_database->loadResult ();
	}

	public function countConfig() {
		return 1;
	}

	public function &exportConfig($start=0, $limit=0) {
		$config = array();
		if ($start) return $config;

		// FIX ME: the default config values are stored in JPATH_ADMINISTRATOR . "/components/com_{$this->extname}/config.xml";
		// but when you change these value i don't where the data are stored, there is no table in database for that !

		$config['id'] = 1;
		// $config['board_title'] = null; --> Sitename
		// $config['email'] = null; --> from
		// $config['board_offline'] = null;
		// $config['board_ofset'] = null;
		// $config['offline_message'] = null;
		// $config['enablerss'] = null; -->Use RSS Feeds
		// $config['enablepdf'] = null;
		// $config['threads_per_page'] = null; --> # of threads in category list
		// $config['messages_per_page'] = null; --> # of posts in thread list
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
		// $config['useredit'] = null; -->
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
		// $config['allowimageupload'] = null; --> Images allowed for
		// $config['allowimageregupload'] = null;
		// $config['imageheight'] = null;
		// $config['imagewidth'] = null;
		// $config['imagesize'] = null; --> Max Image Size
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
		$query="SELECT COUNT(*) FROM #__discussions_categories";
		$count = $this->getCount($query);
		return $count;
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
		$query = "SELECT
			id AS id,
			parent_id AS parent_id,
			name AS name,
			0 AS cat_emoticon,
			0 AS locked,
			moderated AS moderated,
			0 AS pub_access,
			1 AS pub_recurse,
			0 AS admin_access,
			1 AS admin_recurse,
			ordering AS ordering,
			published AS published,
			0 AS review,
			0 AS allow_anonymous,
			0 AS post_anonymous,
			0 AS hits,
			description AS description,
			'' AS headerdesc,
			'' AS class_sfx,
			0 AS allow_polls,
			last_entry_user_id AS id_last_msg,
			counter_posts AS numPosts,
			counter_threads AS numTopics,
			last_entry_date AS time_last_msg
		FROM #__discussions_categories
		ORDER BY id";
		$result = $this->getExportData ( $query, $start, $limit, 'id' );
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
		$query = "SELECT COUNT(*) FROM #__discussions_messages";
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
		$query = "SELECT
			id AS id,
			parent_id AS parent,
			thread AS thread,
			cat_id AS catid,
			account AS name,
			user_id AS userid,
			'' AS email,
			subject AS subject,
			UNIX_TIMESTAMP(date) AS time,
			ip AS ip,
			0 AS topic_emoticon,
			locked AS locked,
			0 AS hold,
			0 AS ordering,
			hits AS hits,
			0 AS moved,
			last_entry_user_id AS modified_by,
			UNIX_TIMESTAMP(last_entry_date) AS modified_time,
			'' AS modified_reason,
			message AS message
		FROM #__discussions_messages";
		$result = $this->getExportData ( $query, $start, $limit );
		foreach ( $result as $key => &$row ) {
			$this->parseText ( $row->name );
			$this->parseText ( $row->email );
			$this->parseText ( $row->subject );
			$this->parseText ( $row->modified_reason );
			$this->parseBBCode ( $row->message );
		}
		return $result;
	}

	/**
	 * Count total number of user profiles to be exported
	 */
	public function countUserProfile() {
		$query = "SELECT COUNT(*) FROM #__discussions_users";
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
			id AS userid,
			'flat' AS view,
			signature AS signature,
			moderator AS moderator,
			NULL AS banned,
			0 AS ordering,
			posts AS posts,
			'' AS avatar,
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
			twitter AS TWITTER,
			facebook AS FACEBOOK,
			NULL AS GTALK,
			NULL AS MYSPACE,
			NULL AS LINKEDIN,
			NULL AS DELICIOUS,
			NULL AS FRIENDFEED,
			NULL AS DIGG,
			NULL AS BLOGSPOT,
			flickr AS FLICKR,
			NULL AS BEBO,
			NULL AS websitename,
			website AS websiteurl,
			NULL AS rank,
			0 AS hideEmail,
			show_online_status AS showOnline
		FROM #__discussions_users
		ORDER BY userid";
		$result = $this->getExportData ( $query, $start, $limit, 'userid' );

		/*foreach ( $result as &$row ) {
			if ($row->avatar) {
				// Full path to the original file
				$row->copyfile = JPATH_ROOT . "/media/com_example/avatars/{$row->avatar}";
			}
			$this->parseBBCode ( $row->signature );
			$this->parseText ( $row->personalText );
			// Parse also all social data
		}*/
		return $result;
	}
}
