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

require_once (JPATH_COMPONENT . '/models/export.php');

/**
 * Example Exporter Class
 *
 * This class can be used as a base to export all data from your favorite forum software.
 * If you want to migrate something else than Joomla component, check also phpBB3 and SMF2 exporters.
 *
 * NOTE: Sor simplicity, please remove functions which you haven't modified!
 */
class KunenaimporterModelExport_example extends KunenaimporterModelExport {
	/**
	 * Extension name ([a-z0-9_], wihtout 'com_' prefix)
	 * @var string
	 */
	public $extname = 'example';
	/**
	 * Display name
	 * @var string
	 */
	public $exttitle = 'Example';
	/**
	 * External application (non-Joomla)
	 * @var bool
	 */
	public $external = false;
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
	 * Get external forum path from importer configuration
	 *
	 * You can usually remove this function if you are exporting Joomla component.
	 *
	 * @return string Relative path
	 */
	public function getPath($absolute = false) {
		if (!$this->external) return;
		return parent::getPath($absolute);
	}

	/**
	 * Detect if component exists
	 *
	 * You can usually remove this function if you are exporting Joomla component.
	 *
	 * @return bool
	 */
	public function detectComponent() {
		// use Joomla function to detect if component exists
		return parent::detectComponent();
	}

	/**
	 * Get database object
	 *
	 * You can usually remove this function if you are exporting Joomla component.
	 *
	 * @return JDatabase or JError or null
	 */
	public function getDatabase() {
		return JFactory::getDBO ();
	}

	/**
	 * Initialization needed by exporter
	 */
	public function initialize() {
	}

	/**
	 * Get configuration
	 *
	 * You can usually remove this function if you are exporting Joomla component.
	 * By default this function gets configuration from component parameters.
	 */
	public function &getConfig() {
		if (empty($this->config)) {
			$this->config = parent::getConfig();
		}
		return $this->config;
	}

	/**
	 * Full detection
	 *
	 * Make sure that everything is OK for full import.
	 * Use $this->addMessage($html) to add status messages.
	 * If you return false, remember also to fill $this->error
	 *
	 * @return bool
	 */
	public function detect() {
		// Initialize detection (calls $this->detectComponent() and $this->getVersion())
		if (!parent::detect()) return false;
		return true;
	}

	/**
	 * Get component version
	 *
	 * You can usually remove this function if you are exporting Joomla component.
	 */
	public function getVersion() {
		// Version can usually be found from <name>.xml file
		$xml = JPATH_ADMINISTRATOR . "/components/com_{$this->extname}/{$this->extname}.xml";
		if (!JFile::exists ( $xml )) {
			return false;
		}
		$parser = JFactory::getXMLParser ( 'Simple' );
		$parser->loadFile ( $xml );
		return $parser->document->getElementByPath ( 'version' )->data ();
	}

	/**
	 * Remove htmlentities, addslashes etc
	 *
	 * @param string $s String
	 */
	protected function parseText(&$s) {
	}

	/**
	 * Convert BBCode to Kunena BBCode
	 *
	 * @param string $s String
	 */
	protected function parseBBCode(&$s) {
	}

	/**
	 * Convert HTML to Kunena BBCode
	 *
	 * @param string $s String
	 */
	protected function parseHTML(&$s) {
		parent::parseHTML($s);
	}

	/**
	 * Map Joomla user to external user
	 *
	 * You can usually remove this function if you are exporting Joomla component.
	 *
	 * @param object $joomlauser StdClass(id, username, email)
	 * @return int External user ID
	 */
	public function mapJoomlaUser($joomlauser) {
		return $joomlauser->id;
	}

	/**
	 * Count total number of users to be exported (external applications only)
	 */
	public function countUsers() {
		return false;
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
		$result = array();
		return $result;

		$query = "SELECT
			NULL AS extid,
			'' AS extusername,
			'' AS name,
			'' AS username,
			'' AS email,
			'' AS password,
			'Registered' AS usertype,
			0 AS block,
			'0000-00-00 00:00:00' AS registerDate,
			'0000-00-00 00:00:00' AS lastvisitDate,
			NULL AS params,
		FROM #__users
		ORDER BY user_id";
		$result = $this->getExportData ( $query, $start, $limit, 'extid' );
		foreach ( $result as &$row ) {
			// Add prefix to password (for authentication plugin)
			$row->password = 'example::'.$row->password;
		}
		return $result;
	}

	/**
	 * Count total number of user profiles to be exported
	 */
	public function countUserProfile() {
		$query = "SELECT COUNT(*) FROM #__example_users";
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
			0 AS userid,
			'flat' AS view,
			'' AS signature,
			0 AS moderator,
			NULL AS banned,
			0 AS ordering,
			0 AS posts,
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
		FROM #__example_users
		ORDER BY userid";
		$result = $this->getExportData ( $query, $start, $limit, 'userid' );

		foreach ( $result as &$row ) {
			if ($row->avatar) {
				// Full path to the original file
				$row->copyfile = JPATH_ROOT . "/media/com_example/avatars/{$row->avatar}";
			}
			$this->parseBBCode ( $row->signature );
			$this->parseText ( $row->personalText );
			// Parse also all social data
		}
		return $result;
	}

	/**
	 * Count total number of ranks to be exported
	 */
	public function countRanks() {
		$query = "SELECT COUNT(*) FROM #__example_ranks";
		$count = $this->getCount ( $query );
		return $count;
	}

	/**
	 * Export user ranks
	 *
	 * Returns list of rank objects containing database fields
	 * to #__kunena_ranks.
	 * NOTE: copies all files found in $row->copypath (full path) to Kunena.
	 *
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportRanks($start = 0, $limit = 0) {
		$query = "SELECT
			NULL AS rank_id,
			'' AS rank_title,
			0 AS rank_min,
			0 AS rank_special,
			'' AS rank_image
		FROM #__example_ranks
		ORDER BY rank_id";
		$result = $this->getExportData ( $query, $start, $limit );
		foreach ( $result as $rank ) {
			$this->parseText ( $row->rank_title );
			// Full path to the original file
			$rank->copypath = JPATH_ROOT . "/components/com_example/assets/ranks/{$rank->rank_image}";
		}
		return $result;
	}

	/**
	 * Count total number of sessions to be exported
	 */
	public function countSessions() {
		$query = "SELECT COUNT(*) FROM #__example_sessions";
		return $this->getCount ( $query );
	}

	/**
	 * Export user session information
	 *
	 * Returns list of attachment objects containing database fields
	 * to #__kunena_sessions.
	 *
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportSessions($start = 0, $limit = 0) {
		$query = "SELECT
			0 AS userid,
			0 AS lasttime,
			'' AS readtopics,
			lasttime AS currvisit
		FROM #__example_sessions";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}

	/**
	 * Count total number of categories to be exported
	 */
	public function countCategories() {
		$query = "SELECT COUNT(*) FROM #__example_categories";
		$count = $this->getCount ( $query );
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
			0 AS id,
			0 AS parent_id,
			'' AS name,
			0 AS cat_emoticon,
			0 AS locked,
			1 AS moderated,
			0 AS pub_access,
			1 AS pub_recurse,
			0 AS admin_access,
			1 AS admin_recurse,
			0 AS ordering,
			1 AS published,
			0 AS review,
			0 AS allow_anonymous,
			0 AS post_anonymous,
			0 AS hits,
			'' AS description,
			'' AS headerdesc,
			'' AS class_sfx,
			0 AS allow_polls,
			0 AS id_last_msg,
			0 AS numPosts,
			0 AS numTopics,
			0 AS time_last_msg
		FROM #__example_categories
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
	 * Count total number of moderator columns to be exported
	 */
	public function countModeration() {
		$query = "SELECT COUNT(*) FROM #__example_moderators";
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
			0 AS userid,
			0 AS catid
		FROM #__example_moderators";
		$result = $this->getExportData ( $query, $start, $limit );
		return $result;
	}

	/**
	 * Count total number of topics to be exported
	 */
	public function countTopics() {
		$query = "SELECT COUNT(*) FROM #__example_topics";
		$count = $this->getCount ( $query );
		return $count;
	}

	/**
	 * Export topics
	 *
	 * Returns list of message objects containing database fields
	 * to #__kunena_topics.
	 *
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportTopics($start = 0, $limit = 0) {
		$query = "SELECT
		0 AS id,
		0 AS category_id,
		'' AS subject,
		0 AS icon_id,
		0 AS locked,
		0 AS hold,
		0 AS ordering,
		0 AS posts,
		0 AS hits,
		0 AS attachments,
		0 AS poll_id,
		0 AS moved_id,
		0 AS first_post_id,
		0 AS first_post_time
		0 AS first_post_userid
		'' AS first_post_message
		'' AS first_post_guest_name
		0 AS last_post_id
		0 AS last_post_time
		0 AS last_post_userid
		'' AS last_post_message
		'' AS last_post_guest_name
		'' AS params
		FROM #__example_topics";
		$result = $this->getExportData ( $query, $start, $limit );
		foreach ( $result as $key => &$row ) {
			$this->parseText ( $row->subject );
			$this->parseText ( $row->first_post_guest_name );
			$this->parseText ( $row->last_post_guest_name );
			$this->parseBBCode ( $row->first_post_message );
			$this->parseBBCode ( $row->last_post_message );
		}
		return $result;
	}

	/**
	 * Count total number of messages to be exported
	 */
	public function countMessages() {
		$query = "SELECT COUNT(*) FROM #__example_messages";
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
			0 AS id,
			0 AS parent,
			0 AS thread,
			0 AS catid,
			'' AS name,
			0 AS userid,
			'' AS email,
			'' AS subject,
			0 AS time,
			'' AS ip,
			0 AS topic_emoticon,
			0 AS locked,
			0 AS hold,
			0 AS ordering,
			0 AS hits,
			0 AS moved,
			0 AS modified_by,
			0 AS modified_time,
			'' AS modified_reason,
			'' AS message
		FROM #__example_messages";
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
	 * Count total polls to be exported
	 */
	public function countPolls() {
		$query="SELECT COUNT(*) FROM #__example_polls";
		return $this->getCount($query);
	}

	/**
	 * Export polls
	 *
	 * Returns list of poll objects containing database fields
	 * to #__kunena_polls.
	 *
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportPolls($start=0, $limit=0) {
		$query="SELECT
			0 AS id,
			'' AS title,
			0 AS threadid,
			'0000-00-00 00:00:00' AS polltimetolive
		FROM #__example_polls";
		$result = $this->getExportData($query, $start, $limit);
		return $result;
	}

	/**
	 * Count total poll options to be exported
	 */
	public function countPollsOptions() {
		$query="SELECT COUNT(*) FROM #__example_polls_options";
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
			0 AS id,
			0 AS pollid,
			'' AS text,
			0 AS votes
		FROM #__example_polls_options";
		$result = $this->getExportData($query, $start, $limit);
		return $result;
	}

	/**
	 * Count total poll users to be exported
	 */
	public function countPollsUsers() {
		$query="SELECT COUNT(*) FROM #__example_polls_users";
		return $this->getCount($query);
	}

	/**
	 * Export poll users
	 *
	 * Returns list of poll users objects containing database fields
	 * to #__kunena_polls_users.
	 *
	 * @param int $start Pagination start
	 * @param int $limit Pagination limit
	 * @return array
	 */
	public function &exportPollsUsers($start=0, $limit=0) {
		// WARNING: from unknown reason pollid = threadid!!!
		$query="SELECT
			0 AS pollid,
			0 AS userid,
			0 AS votes,
			'0000-00-00 00:00:00' AS lasttime,
			0 AS lastvote
		FROM #__example_polls_users";
		$result = $this->getExportData($query, $start, $limit);
		return $result;
	}

	/**
	 * Count total number of attachments to be exported
	 */
	public function countAttachments() {
		$query = "SELECT COUNT(*) FROM #__example_attachments";
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
			0 AS id,
			0 AS mesid,
			0 AS userid,
			NULL AS hash,
			NULL AS size,
			NULL AS folder,
			NULL AS filetype,
			NULL AS filename
		FROM #__attachments
		ORDER BY id";
		$result = $this->getExportData ( $query, $start, $limit, 'id' );
		$copypath = JPATH_ROOT.'/media/example/attachments';
		foreach ( $result as &$row ) {
			// Folder is relative path like: "example/folder" or "example"
			$row->folder = 'example'. ($row->folder ? '/'.$row->folder : '');
			// Full path to the original file
			$row->copyfile = "{$copypath}/{$row->folder}/{$row->filename}";
		}
		return $result;
	}

	/**
	 * Count total number of subscription items to be exported
	 */
	public function countSubscriptions() {
		$query = "SELECT COUNT(*) FROM #__example_subscriptions";
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
			0 AS thread,
			0 AS userid,
			0 AS future1
		FROM #__example_subscriptions";
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
			$copypath = JPATH_ROOT.'/media/example/avatars/gallery';
			$galleries = array();
			$folders = JFolder::folders($copypath);
			foreach ($folders as $folder) {
				$galleries[$folder] = "{$copypath}/{$folder}";
			}
		}
		return $galleries;
	}

	/**
	 * Count global configurations to be exported
	 * @return 1
	 */
	public function countConfig() {
		return 1;
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

		// Time delta in seconds from UTC (=JFactory::getDate()->toUnix())
		// $config['timedelta'] = JFactory::getDate()->toUnix() - time() - $offsetinseconds;

		// Get configuration and fill any values from below:

		// $config['board_title'] = null;
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
		$result = array ('1' => $config );
		return $result;
	}
}