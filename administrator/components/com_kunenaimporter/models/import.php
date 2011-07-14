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

// TODO: Better Error detection
// TODO: User Mapping


// Import Joomla! libraries
jimport ( 'joomla.application.component.model' );

// Everything else than user import can be found from here:
require_once (JPATH_COMPONENT . '/models/kunena.php');

class KunenaimporterModelImport extends JModel {
	protected $userfields = array(
		'messages'=>array('userid', 'modified_by'),
		'attachments'=>array('userid'),
		'subscriptions_categories'=>array('userid'),
		'subscriptions'=>array('userid'),
		'favorites'=>array('userid'),
		'userprofile'=>array('userid'),
		'sessions'=>array('userid'),
		//'categories'=>array('checked_out'),
		'moderation'=>array('userid'),
		'polls_users'=>array('userid'),
		'thankyou'=>array('userid', 'target_userid'),
		'users_banned'=>array('userid', 'created_by', 'modified_by'),
		'whoisonline'=>array('userid'),
	);

	public function __construct() {
		parent::__construct ();
		$this->db = JFactory::getDBO ();
		// $this->db->setDebug(0);
	}

	public function getImportOptions() {
		// version
		$options = array ('config', 'users', 'mapusers','userprofile', 'ranks', 'sessions', 'whoisonline', 'categories', 'moderation', 'messages', 'attachments', 'favorites', 'subscriptions', 'smilies', 'announcements', 'avatargalleries' );
		return $options;
	}

	protected function commitStart() {
		$query = "SET autocommit=0;";
		$this->db->setQuery ( $query );
		$result = $this->db->query () or die ( "<br />Disabling autocommit failed:<br />$query<br />" . $this->db->errorMsg () );
	}

	protected function commitEnd() {
		$query = "COMMIT;";
		$this->db->setQuery ( $query );
		$result = $this->db->query () or die ( "<br />Commit failed:<br />$query<br />" . $this->db->errorMsg () );
		$query = "SET autocommit=1;";
		$this->db->setQuery ( $query );
		$result = $this->db->query () or die ( "<br />Enabling autocommit failed:<br />$query<br />" . $this->db->errorMsg () );
	}

	public function disableKeys($table) {
		$query = "ALTER TABLE {$table} DISABLE KEYS";
		$this->db->setQuery ( $query );
		$result = $this->db->query () or die ( "<br />Disable keys failed:<br />$query<br />" . $this->db->errorMsg () );
	}

	public function enableKeys($table) {
		$query = "ALTER TABLE {$table} ENABLE KEYS";
		$this->db->setQuery ( $query );
		$result = $this->db->query () or die ( "<br />Enable keys failed:<br />$query<br />" . $this->db->errorMsg () );
	}

	public function setAuthMethod($auth_method) {
		$this->auth_method = $auth_method;
	}

	public function getUsername($name) {
		//if ($this->auth_method == 'joomla') return $name;
		return strtr ( $name, "<>\"'%;()&", '_________' );
	}

	public function findPotentialUsers($extuser, $all = false) {
		// Check if user exists in Joomla
		$query = "SELECT u.*
		FROM `#__users` AS u
		LEFT JOIN `#__kunenaimporter_users` AS e ON e.id=u.id
		WHERE (u.username LIKE {$this->db->quote($extuser->username)}
		OR u.email LIKE {$this->db->quote($extuser->email)})";
		if (!$all) {
			$query .= " AND e.id IS NULL";
		}
		else if ($extuser->id) {
			$query .= " OR u.id={$this->db->quote($extuser->id)}";
		}
		$this->db->setQuery ( $query );
		$userlist = $this->db->loadObjectList ( 'id' );

		$bestpoints = 0;
		$bestid = 0;
		$newlist = array ();
		foreach ( $userlist as $user ) {
			$points = 0;
			if (strtolower($extuser->username) == strtolower($user->username))
				$points += 2;
			if (strtolower($extuser->email) == strtolower($user->email))
				$points += 1;

			$user->points = $points;
			$newlist [$points] = $user;
		}
		krsort ( $newlist );
		return $newlist;
	}

	protected function mapUser($extuser) {
		if ($extuser->id !== null)
			return $extuser->id;

		$userlist = $this->findPotentialUsers ( $extuser );
		$best = array_shift ( $userlist );
		if (!$best)
			return 0;
		if (!empty($userlist))
			return -$best->id;
		return $best->id;
	}

	public function truncateUsersMap() {
		$query = "TRUNCATE TABLE `#__kunenaimporter_users`";
		$this->db->setQuery ( $query );
		$result = $this->db->query () or die ( "<br />Invalid query:<br />$query<br />" . $this->db->errorMsg () );
	}

	public function mapUsers($result, $limit) {
		if (!$result['total']) {
			$query = "SELECT COUNT(*) FROM `#__kunenaimporter_users` WHERE id IS NULL";
			$this->db->setQuery ( $query );
			$result['total'] = $this->db->loadResult ();
		}
		$query = "SELECT * FROM `#__kunenaimporter_users` WHERE id IS NULL AND extid > ".intval($result['start']);
		$this->db->setQuery ( $query, 0, $limit );
		$users = $this->db->loadAssocList ();

		$result['now'] = 0;
		foreach ( $users as $userdata ) {
			$result['start'] = $userdata['extid'];
			$result['now']++;
			$extuser = JTable::getInstance ( 'ExtUser', 'KunenaImporterTable' );
			$extuser->bind ( $userdata );
			unset($userdata);
			$extuser->exists ( true );
			$uid = $this->mapUser ( $extuser );
			$result['all']++;
			if (!$uid) {
				continue;
			}
			$userdata = array();
			if ($uid > 0) {
				$userdata['id'] = abs ( $uid );
				$result['new']++;
			} else {
				$userdata['conflict'] = abs ( $uid );
				$result['conflict']++;
			}
			if ($extuser->save ( $userdata ) === false) {
				$result['failed']++;
				echo "ERROR: Saving external {$extuser->username} failed: " . $extuser->getError () . "<br />";
			} elseif ($uid > 0) {
				$this->updateUserData(-$extuser->extid, $uid);
			}
		}
		unset($users);
		return $result;
	}

	public function createUsers(&$users) {
		foreach ( $users as $userdata ) {
		}
	}

	protected function UpdateCatStats() {
		// Update last message time from all categories.
		$query = "UPDATE `#__kunena_categories`, `#__kunena_messages` SET `#__kunena_categories`.time_last_msg=`#__kunena_messages`.time WHERE `#__kunena_categories`.id_last_msg=`#__kunena_messages`.id AND `#__kunena_categories`.id_last_msg>0";
		$this->db->setQuery ( $query );
		$result = $this->db->query () or die ( "<br />Invalid query:<br />$query<br />" . $this->db->errorMsg () );
		unset ( $query );
	}

	public function truncateData($option) {
		if ($option == 'config' || $option == 'avatargalleries')
			return;
		if ($option == 'mapusers')
			return;
		if ($option == 'users')
			$option = 'extuser';
		if ($option == 'messages')
			$this->truncateData ( $option . '_text' );
		$this->db = JFactory::getDBO ();
		$table = JTable::getInstance ( $option, 'KunenaImporterTable' );
		if (!$table) die ("<br />{$option}: Table doesn't exist!");
		$query = "TRUNCATE TABLE " . $this->db->nameQuote ( $table->getTableName () );
		$this->db->setQuery ( $query );
		$result = $this->db->query () or die ( "<br />{$option}: Invalid query:<br />$query<br />" . $this->db->errorMsg () );
	}

	/*
	public function truncateJoomlaUsers() {
		// Leave only Super Administrators
		$this->db = JFactory::getDBO();
		$query="DELETE FROM #__users WHERE gid != 25";
		$this->db->setQuery($query);
		$result = $this->db->query() or die("<br />Invalid query:<br />$query<br />" . $this->db->errorMsg());
		$query="ALTER TABLE `#__users` AUTO_INCREMENT = 0";
		$this->db->setQuery($query);
		$result = $this->db->query() or die("<br />Invalid query:<br />$query<br />" . $this->db->errorMsg());
		$query="DELETE #__core_acl_aro AS a FROM #__core_acl_aro AS a LEFT JOIN #__users AS u ON a.value=u.id WHERE u.id IS NULL";
		$this->db->setQuery($query);
		$result = $this->db->query() or die("<br />Invalid query:<br />$query<br />" . $this->db->errorMsg());
		$query="DELETE FROM #__core_acl_groups_aro_map WHERE group_id != 25";
		$this->db->setQuery($query);
		$result = $this->db->query() or die("<br />Invalid query:<br />$query<br />" . $this->db->errorMsg());
		$query="ALTER TABLE `#__core_acl_aro` AUTO_INCREMENT = 0";
		$this->db->setQuery($query);
		$result = $this->db->query() or die("<br />Invalid query:<br />$query<br />" . $this->db->errorMsg());
	}
	*/

	public function importData($option, &$data) {
		if (empty($data)) return;

		// TODO: move timedelta out of session:
		$this->timedelta = intval(JFactory::getApplication ()->getUserState ( 'com_kunenaimporter.timedelta' ));
		switch ($option) {
			case 'config' :
				$newConfig = end ( $data );
				if (is_object ( $newConfig ))
					$newConfig = $newConfig->GetClassVars ();
				$newConfig['id'] = 1;
				$this->timedelta = intval(isset($newConfig['timedelta']) ? $newConfig['timedelta'] : 0);
				// TODO: move timedelta out of session:
				JFactory::getApplication ()->setUserState ( 'com_kunenaimporter.timedelta', $this->timedelta );
				$kunenaConfig = new KunenaImporterTableConfig ();
				$kunenaConfig->save ( $newConfig );
				break;
			case 'messages' :
				// time, modified_time
				$this->importMessages ( $data );
				break;
			case 'attachments':
				$this->importAttachments ( $data );
				break;
			case 'avatargalleries':
				$this->importAvatarGalleries ( $data );
				break;
			case 'mapusers':
				break;
			case 'users':
				$option = 'extuser';
			case 'userprofile':
				// karma_time, banned:datetime
			case 'ranks':
			case 'sessions':
				// lasttime, currvisit
			case 'whoisonline':
			case 'categories':
			case 'moderation':
			case 'smilies':
			case 'announcements':
				// created:datetime
			case 'subscriptions_categories':
			case 'subscriptions':
			case 'favorites':
			case 'polls':
			case 'polls_options':
			case 'polls_users':
				// lasttime:timestamp
			case 'thankyou':
			case 'usersbanned':
				// expiration:datetime, created_time:datetime, modified_time:datetime
			case 'whoisonline':
				$this->importDefault ( $option, $data );
		}
	}

	protected function importDefault($option, &$data) {
		// If table has userids in it, we need to convert them to Joomla userids
		$userids = !empty($this->userfields[$option]);
		if ($userids) {
			$extids = array();
			foreach ( $data as $item ) {
				foreach ( $this->userfields[$option] as $field) {
					$extids[$item->$field] = $item->$field;
				}
			}
			$this->loadUsers($extids);
		}

		$this->commitStart ();
		foreach ( $data as $item ) {
			if ($userids) {
				// Convert all userids in the table
				foreach ( $this->userfields[$option] as $field) {
					$item->userid = $this->getUser($item->userid)->userid;
				}
			}
			if (!empty($item->copypath) && file_exists($item->copypath)) {
				// There is attached file to be copied
				switch ($option) {
					case 'ranks':
						$destpath = JPATH_ROOT . "/components/com_kunena/template/default/images/ranks/{$item->rank_image}";
						break;
					case 'userprofile':
						$destpath = JPATH_ROOT . "/media/kunena/avatars/{$item->avatar}";
						break;
					default:
				}
				if (!empty($destpath)) {
					JFile::copy($item->copypath, $destpath);
				}
			}
			// Save row into table
			$table = JTable::getInstance ( $option, 'KunenaImporterTable' );
			if ($table->save ( $item ) === false)
				die ( "ERROR: " . $table->getError () );
		}
		$this->commitEnd ();
	}

	protected function importAttachments(&$data) {
		$extids = array();
		foreach ( $data as $item ) {
			if (!empty($item->userid)) $extids[$item->userid] = $item->userid;
		}
		$this->loadUsers($extids);

		$this->commitStart ();
		foreach ( $data as $item ) {
			$user = $this->getUser($item->userid);
			$item->userid = $user->userid;
			$item->folder = 'media/kunena/attachments/'.$item->folder;
			if (file_exists($item->copypath)) {
				$path = JPATH_ROOT."/{$item->folder}";

				// Create upload folder and index.html
				if (!JFolder::exists($path) && JFolder::create($path)) {
					JFile::write("{$path}/index.html",'<html><body></body></html>');
				}
				$item->hash = md5_file ( $item->copypath );
				JFile::copy($item->copypath, "{$path}/{$item->filename}");
			}
			$table = JTable::getInstance ( 'attachments', 'KunenaImporterTable' );
			if ($table->save ( $item ) === false)
				die ( "ERROR: " . $table->getError () );
		}
		$this->commitEnd ();
	}

	protected function importAvatarGalleries(&$data) {
		foreach ( $data as $item=>$path ) {
			if (is_dir($path)) {
				// Copy gallery
				JFolder::copy($path, JPATH_ROOT."/media/kunena/avatars/gallery/{$item}", '', true);
				// Create index.html
				JFile::write(JPATH_ROOT."/media/kunena/avatars/gallery/{$item}/index.html",'<html><body></body></html>');
			} elseif(is_file($path)) {
				JFile::copy($path, JPATH_ROOT."/media/kunena/avatars/gallery/{$item}", '', true);
			}
		}
	}

	protected function importMessages(&$messages) {
		$extids = array();
		foreach ( $messages as $message ) {
			if (!empty($message->userid)) $extids[$message->userid] = $message->userid;
			if (!empty($message->modified_by)) $extids[$message->modified_by] = $message->modified_by;
		}
		$this->loadUsers($extids);

		$this->commitStart ();
		foreach ( $messages as $message ) {
			if ($message->userid) {
				$user = $this->getUser($message->userid);
				if ($user->extid && $user->lastvisitDate < $message->time - 86400) {
					// user MUST have been in the forum in the past 24 hours, update last visit..
					$extuser = JTable::getInstance ( 'ExtUser', 'KunenaImporterTable' );
					$extuser->load($message->userid);
					$extuser->lastvisitDate = $idmap[$message->userid]->lastvisitDate =$message->time;
					$extuser->save();
				}
				$message->userid = $user->userid;
			}
			$message->time += $this->timedelta;
			if ($message->modified_time) {
				$message->modified_time += $this->timedelta;
			}
			if ($message->modified_by) {
				$user = $this->getUser($message->modified_by);
				$message->modified_by = $user->userid;
			}
			$message->mesid = $message->id;
			if ($message->userid > 0 && (empty ( $message->email ) || empty ( $message->name ))) {
				$user = JUser::getInstance ( $message->userid );
				if (empty ( $message->email ))
					$message->email = $user->email;
				if (empty ( $message->name ))
					$message->name = $user->username;
			}
			
			$msgtable = JTable::getInstance ( 'messages', 'KunenaImporterTable' );
			if ($msgtable->save ( $message ) === false)
				die ( "ERROR: " . $msgtable->getError () );
			$txttable = JTable::getInstance ( 'messages_text', 'KunenaImporterTable' );
			if ($txttable->save ( $message ) === false)
				die ( "ERROR: " . $txttable->getError () );
		}
		$this->commitEnd ();

		$this->updateCatStats ();
	}

	protected function loadUsers(&$extids) {
		$extuser = JTable::getInstance ( 'ExtUser', 'KunenaImporterTable' );
		$this->useridmap = $extuser->loadIdMap($extids);
	}

	protected function getUser($extid) {
		if (isset($this->useridmap[$extid])) {
			$user = $this->useridmap[$extid];
			$user->userid = $user->id ? $user->id : -$userid->extid;
		} else {
			$user = new StdClass();
			$user->id = empty($this->useridmap) ? $extid : 0;
			$user->userid = empty($this->useridmap) ? $extid : -$extid;
			$user->extid = empty($this->useridmap) ? 0 : $extid;
			$user->lastvisitDate = JFactory::getDate()->toUnix();
		}
		return $user;
	}

	public function updateUserData($oldid, $newid, $replace = false) {
		if ($replace) {
			$this->db->setQuery ( "DELETE FROM `#__kunena_users` WHERE `userid` = {$this->db->quote($oldid)}" );
			$this->db->query ();
		}
		$queries[] = "UPDATE `#__kunena_users` SET `userid` = {$this->db->quote($newid)} WHERE `userid` = {$this->db->quote($oldid)}";
		$queries[] = "UPDATE `#__kunena_attachments` SET `userid` = {$this->db->quote($newid)} WHERE `userid` = {$this->db->quote($oldid)}";
		$queries[] = "UPDATE `#__kunena_favorites` SET `userid` = {$this->db->quote($newid)} WHERE `userid` = {$this->db->quote($oldid)}";
		$queries[] = "UPDATE `#__kunena_messages` SET `userid` = {$this->db->quote($newid)} WHERE `userid` = {$this->db->quote($oldid)}";
		$queries[] = "UPDATE `#__kunena_messages` SET `modified_by` = {$this->db->quote($newid)} WHERE `modified_by` = {$this->db->quote($oldid)}";
		$queries[] = "UPDATE `#__kunena_moderation` SET `userid` = {$this->db->quote($newid)} WHERE `userid` = {$this->db->quote($oldid)}";
		$queries[] = "UPDATE `#__kunena_polls_users` SET `userid` = {$this->db->quote($newid)} WHERE `userid` = {$this->db->quote($oldid)}";
		//$queries[] = "UPDATE `#__kunena_sessions` SET `userid` = {$this->db->quote($newid)} WHERE `userid` = {$this->db->quote($oldid)}";
		$queries[] = "UPDATE `#__kunena_subscriptions` SET `userid` = {$this->db->quote($newid)} WHERE `userid` = {$this->db->quote($oldid)}";
		$queries[] = "UPDATE `#__kunena_subscriptions_categories` SET `userid` = {$this->db->quote($newid)} WHERE `userid` = {$this->db->quote($oldid)}";
		$queries[] = "UPDATE `#__kunena_thankyou` SET `userid` = {$this->db->quote($newid)} WHERE `userid` = {$this->db->quote($oldid)}";
		$queries[] = "UPDATE `#__kunena_thankyou` SET `targetuserid` = {$this->db->quote($newid)} WHERE `targetuserid` = {$this->db->quote($oldid)}";
		$queries[] = "UPDATE `#__kunena_users_banned` SET `userid` = {$this->db->quote($newid)} WHERE `userid` = {$this->db->quote($oldid)}";
		$queries[] = "UPDATE `#__kunena_users_banned` SET `created_by` = {$this->db->quote($newid)} WHERE `created_by` = {$this->db->quote($oldid)}";
		$queries[] = "UPDATE `#__kunena_users_banned` SET `modified_by` = {$this->db->quote($newid)} WHERE `modified_by` = {$this->db->quote($oldid)}";
		$queries[] = "UPDATE `#__kunena_whoisonline` SET `userid` = {$this->db->quote($newid)} WHERE `userid` = {$this->db->quote($oldid)}";

		foreach ($queries as $query) {
			$this->db->setQuery ( $query );
			$this->db->query ();
			if ($this->db->getErrorNum()) {
				$app = JFactory::getApplication ();
				$app->enqueueMessage ( "WARNING: Userid {$newid} is already in use! Cannot map user to it." );
				return false;
			}
		}
		return true;
	}
}