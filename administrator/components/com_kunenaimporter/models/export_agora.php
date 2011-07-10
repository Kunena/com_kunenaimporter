<?php
/**
 * @version $Id$
 * Kunena Forum Importer Component
 * @package com_kunenaimporter
 *
 * Imports forum data into Kunena
 *
 * @Copyright (C) 2009 - 2010 Kunena Team All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.com
 *
 */
defined ( '_JEXEC' ) or die ();

// Import Joomla! libraries
jimport('joomla.application.component.model');
jimport('joomla.application.application');

require_once( JPATH_COMPONENT . '/models/export.php' );

class KunenaimporterModelExport_Agora extends KunenaimporterModelExport {
	public function checkConfig() {
		parent::checkConfig();

		$query = "SELECT conf_value FROM `#__agora_config` WHERE `conf_name` = 'o_cur_version'";
		$this->setQuery ( $query );
		$this->version = $this->ext_database->loadResult ();
		if (! $this->version) {
			$this->error = $this->getErrorMsg ();
			if (! $this->error)
				$this->error = 'Configuration information missing: Agora version not found';
		}
		if ($this->error) {
			$this->addMessage ( '<div>Agora version: <b style="color:red">FAILED</b></div>' );
			return false;
		}

		if (version_compare($this->version, '3.0.142', '<'))
			$this->error = "Unsupported forum: Agora $this->version";
		if ($this->error) {
			$this->addMessage ( '<div>Agora version: <b style="color:red">' . $this->version . '</b></div>' );
			$this->addMessage ( '<div><b>Error:</b> ' . $this->error . '</div>' );
			return false;
		}
		$this->addMessage ( '<div>Agora version: <b style="color:green">' . $this->version . '</b></div>' );
	}

	public function buildImportOps() {
		// query: (select, from, where, groupby), functions: (count, export)
		$importOps = array();
		$importOps ['config'] = array ('count' => 'countConfig', 'export' => 'exportConfig' );
		$importOps['categories'] = array('count'=>'countCategories', 'export'=>'exportCategories');
		$importOps ['messages'] = array ('count' => 'countMessages', 'export' => 'exportMessages' );
		$importOps ['subscriptions'] = array ('count' => 'countSubscriptions', 'export' => 'exportSubscriptions' );
		$importOps ['smilies'] = array ('count' => 'countSmilies', 'export' => 'exportSmilies' );
		$importOps ['ranks'] = array ('count' => 'countRanks', 'export' => 'exportRanks' );
		$importOps ['userprofile'] = array ('count' => 'countUserprofile', 'export' => 'exportUserprofile' );
		$this->importOps = $importOps;
	}

	public function countConfig() {
		return 1;
	}

	public function &exportConfig($start=0, $limit=0) {
		$config = array();
		if ($start) return $config;

		$query="SELECT conf_name, conf_value AS value FROM #__agora_config";
		$result = $this->getExportData($query, 0, 1000, 'conf_name');

		if (!$result) return $config;

		$config['id'] = 1;
		$config['board_title'] = $result['sitename']->value;
		$config['email'] = $result['board_email']->value;
		$config['board_offline'] = $result['board_disable']->value;
		$config['board_ofset'] = $result['board_timezone']->value;
// 		$config['offline_message'] = null;
// 		$config['default_view'] = null;
// 		$config['enablerss'] = null;
// 		$config['enablepdf'] = null;
		$config['threads_per_page'] = $result['topics_per_page']->value;
		$config['messages_per_page'] = $result['posts_per_page']->value;
// 		$config['messages_per_page_search'] = null;
// 		$config['showhistory'] = null;
// 		$config['historylimit'] = null;
// 		$config['shownew'] = null;
// 		$config['newchar'] = null;
// 		$config['jmambot'] = null;
		$config['disemoticons'] = $result['allow_smilies']->value ^ 1;
// 		$config['template'] = null;
// 		$config['templateimagepath'] = null;
// 		$config['joomlastyle'] = null;
// 		$config['showannouncement'] = null;
// 		$config['avataroncat'] = null;
// 		$config['catimagepath'] = null;
// 		$config['numchildcolumn'] = null;
// 		$config['showchildcaticon'] = null;
// 		$config['annmodid'] = null;
// 		$config['rtewidth'] = null;
// 		$config['rteheight'] = null;
// 		$config['enablerulespage'] = null;
// 		$config['enableforumjump'] = null;
// 		$config['reportmsg'] = null;
// 		$config['username'] = null;
// 		$config['askemail'] = null;
// 		$config['showemail'] = null;
// 		$config['showuserstats'] = null;
// 		$config['poststats'] = null;
// 		$config['statscolor'] = null;
// 		$config['showkarma'] = null;
// 		$config['useredit'] = null;
// 		$config['useredittime'] = null;
// 		$config['useredittimegrace'] = null;
// 		$config['editmarkup'] = null;
 		$config['allowsubscriptions'] = $result['o_auto_subscriptions'];
// 		$config['subscriptionschecked'] = null;
// 		$config['allowfavorites'] = null;
// 		$config['wrap'] = null;
// 		$config['maxsubject'] = null;
		$config['maxsig'] = $result['allow_sig']->value ? $result['max_sig_chars']->value : 0;
// 		$config['regonly'] = null;
		$config['changename'] = $result['allow_namechange']->value;
// 		$config['pubwrite'] = null;
		$config['floodprotection'] = $result['flood_interval']->value;
// 		$config['mailmod'] = null;
// 		$config['mailadmin'] = null;
// 		$config['captcha'] = null;
// 		$config['mailfull'] = null;
		$config['allowavatar'] = $result['allow_avatar_upload']->value || $result['allow_avatar_local']->value;
		$config['allowavatarupload'] = $result['allow_avatar_upload']->value;
		$config['allowavatargallery'] = $result['allow_avatar_local']->value;
// 		$config['imageprocessor'] = null;
		$config['avatarsmallheight'] = $result['avatar_max_height']->value > 50 ? 50 : $result['avatar_max_height']->value;
		$config['avatarsmallwidth'] = $result['avatar_max_width']->value > 50 ? 50 : $result['avatar_max_width']->value;
		$config['avatarheight'] = $result['o_avatars_dheight']->value;
		$config['avatarwidth'] = $result['o_avatars_dwidth']->value;
		$config['avatarlargeheight'] = $result['avatar_max_height']->value;
		$config['avatarlargewidth'] = $result['avatar_max_width']->value;
// 		$config['avatarquality'] = null;
		$config['avatarsize'] = (int)($result['avatar_filesize']->value / 1000);
// 		$config['allowimageupload'] = null;
// 		$config['allowimageregupload'] = null;
// 		$config['imageheight'] = null;
// 		$config['imagewidth'] = null;
// 		$config['imagesize'] = null;
// 		$config['allowfileupload'] = null;
// 		$config['allowfileregupload'] = null;
// 		$config['filetypes'] = null;
// 		$config['filesize'] = null;
// 		$config['showranking'] = null;
// 		$config['rankimages'] = null;
// 		$config['avatar_src'] = null;
// 		$config['fb_profile'] = null;
// 		$config['pm_component'] = null;
// 		$config['discussbot'] = null;
// 		$config['userlist_rows'] = null;
// 		$config['userlist_online'] = null;
// 		$config['userlist_avatar'] = null;
// 		$config['userlist_name'] = null;
// 		$config['userlist_username'] = null;
// 		$config['userlist_group'] = null;
// 		$config['userlist_posts'] = null;
// 		$config['userlist_karma'] = null;
// 		$config['userlist_email'] = null;
// 		$config['userlist_usertype'] = null;
// 		$config['userlist_joindate'] = null;
// 		$config['userlist_lastvisitdate'] = null;
// 		$config['userlist_userhits'] = null;
// 		$config['showlatest'] = null;
// 		$config['latestcount'] = null;
// 		$config['latestcountperpage'] = null;
// 		$config['latestcategory'] = null;
// 		$config['latestsinglesubject'] = null;
// 		$config['latestreplysubject'] = null;
// 		$config['latestsubjectlength'] = null;
// 		$config['latestshowdate'] = null;
// 		$config['latestshowhits'] = null;
// 		$config['latestshowauthor'] = null;
// 		$config['showstats'] = null;
// 		$config['showwhoisonline'] = null;
// 		$config['showgenstats'] = null;
// 		$config['showpopuserstats'] = null;
// 		$config['popusercount'] = null;
// 		$config['showpopsubjectstats'] = null;
// 		$config['popsubjectcount'] = null;
// 		$config['usernamechange'] = null;
// 		$config['rules_infb'] = null;
// 		$config['rules_cid'] = null;
// 		$config['rules_link'] = null;
// 		$config['enablehelppage'] = null;
// 		$config['help_infb'] = null;
// 		$config['help_cid'] = null;
// 		$config['help_link'] = null;
// 		$config['showspoilertag'] = null;
// 		$config['showvideotag'] = null;
// 		$config['showebaytag'] = null;
// 		$config['trimlongurls'] = null;
// 		$config['trimlongurlsfront'] = null;
// 		$config['trimlongurlsback'] = null;
// 		$config['autoembedyoutube'] = null;
// 		$config['autoembedebay'] = null;
// 		$config['ebaylanguagecode'] = null;
		$config['fbsessiontimeout'] = $result['session_length']->value;
// 		$config['highlightcode'] = null;
// 		$config['rsstype'] = null;
// 		$config['rsshistory'] = null;
		$config['fbdefaultpage'] = 'categories';
// 		$config['default_sort'] = null;
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
		// Import the categories
		$query="(SELECT
			cat_name AS name,
			disp_position AS ordering,
			enable AS published
		FROM #__agora_categories) UNION ALL
		(SELECT
			enable AS published,
			forum_name AS name,
			forum_desc AS description,
			forum_mdesc AS headerdesc,
			moderators,
			num_topics AS numTopics,
			num_posts AS numPosts,
			last_post_id AS id_last_msg,
			cat_id AS id,
			parent_forum_id AS parent
		FROM #__agora_forums)
		ORDER BY id";
		$result = $this->getExportData($query, $start, $limit);
		foreach ($result as $key=>&$row) {
			$row->name = prep($row->name);
			$row->description = prep($row->description);
		}
		return $result;
	}

	public function countMessages() {
		$query = "SELECT COUNT(*) FROM #__aogra_messages";
		return $this->getCount ( $query );
	}

	public function &exportMessages($start = 0, $limit = 0) {
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

			FROM `#__aogra_topics` AS t
			LEFT JOIN `#__agora_posts` AS p ON p.topic_id = t.id
			LEFT JOIN `#__agora_users` AS u ON p.poster_id = u.id
			WHERE t.announcements='0'
			ORDER BY t.id";
		$result = $this->getExportData ( $query, $start, $limit, 'id' );
		foreach ( $result as &$row ) {
			$row->subject = $this->prep ( $row->subject );
			$row->message = $this->prep ( $row->message );
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

	public function countUserprofile() {
		$query="SELECT COUNT(*) FROM #__agora_users";
		return $this->getCount($query);
	}

	public function &exportUserprofile($start=0, $limit=0) {
		$query="SELECT
			url AS websiteurl,
			icq AS ICQ,
			msn AS MSN,
			aim AS AIM,
			yahoo AS YAHOO,
			skype AS SKYPE,
			location,
			signature,
			gender,
			birthday AS birhtdate,
			aboutme AS personnalText,
			num_posts AS posts
		FROM #__agora_users";
		$result = $this->getExportData($query, $start, $limit);
		foreach ( $result as $key => &$row ) {
			//$row->avatarpath = JPATH_BASE . '/components/com_agora/img/pre_avatars/'. $row->id;
		}
	}

	public function countPolls() {
		$query="SELECT COUNT(*) FROM #__agora_polls";
		return $this->getCount($query);
	}

	public function &exportPolls($start=0, $limit=0) {
		$query="SELECT
			p.pollid AS id,
			p.options,
			p.voters,
			p.votes, 
			t.question AS title
		FROM #__agora_polls AS p
		LEFT JOIN #__agora_topics AS t ON p.pollid=t.id";
		$result = $this->getExportData($query, $start, $limit);
	}

	public function countSubscriptions() {
		$query = "SELECT COUNT(*) FROM `#__agora_subscriptions`";
		return $this->getCount ( $query );
	}

	public function &exportSubscriptions($start = 0, $limit = 0) {
		$query = "SELECT
			w.topic_id AS thread,
			w.user_id AS userid
		FROM `#__agora_subscriptions` AS w";
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

	protected function prep($s) {
		return $s;
	}
}