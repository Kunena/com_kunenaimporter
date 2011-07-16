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

jimport ( 'joomla.application.component.controller' );
jimport ( 'joomla.error.profiler' );

/**
 * Kunena importer Controller
 */
class KunenaImporterController extends JController {
	public function __construct() {
		$this->item_type = 'Default';
		$this->addModelPath ( JPATH_ADMINISTRATOR . '/components/com_kunenaimporter/models' );
		parent::__construct ();
		$this->registerTask ( 'start', 'start' );
		$this->registerTask ( 'truncatemap', 'truncatemap' );
		$this->registerTask ( 'mapusers', 'mapusers' );
		$this->registerTask ( 'stopmapping', 'stopmapping' );
		$this->registerTask ( 'select', 'selectuser' );
		$this->registerTask ( 'import', 'importforum' );
		$this->registerTask ( 'stopimport', 'stopimport' );
		$this->registerTask ( 'truncate', 'truncatetable' );
	}

	public function start() {
		$app = JFactory::getApplication ();
		if (! JRequest::checkToken (true)) {
			$app->enqueueMessage ( JText::_ ( 'COM_KUNENAIMPORTER_ERROR_TOKEN' ), 'error' );
			$this->redirectBack();
		}
		$params = getKunenaImporterParams();
		$forum = JRequest::getString ( 'select', '' );
		$success = false;
		if ($forum) {
			$exporter = $this->getModel ( 'export_' . $forum );
			if ($exporter) $success = $exporter->detectComponent ();
		}
		if (!$success) {
			$app->enqueueMessage ( JText::sprintf ( "Component '%s' was not detected!", $forum ), 'error' );
			$this->redirectBack();
		}

		if ($params->get('extforum') != $forum) {
			$table = JTable::getInstance ( 'component' );
			if (! $table->loadByOption ( 'com_kunenaimporter' )) {
				JError::raiseWarning ( 500, 'Not a valid component' );
				return false;
			}
			$post = array('params' => array('extforum'=>$forum, 'path'=>$exporter->getPath(), 'usermode'=>'joomla', 'useradd'=>'no'));
			$table->bind ( $post );
			if (!$table->save ( $post )) {
				$app->enqueueMessage ( JText::_ ( 'Saving configuration failed!' ), 'error' );
				$this->redirectBack();
			}
			$app->setUserState ( 'com_kunenaimporter.state', null );
			$app->setUserState ( 'com_kunenaimporter.Users', null );
			$importer = $this->getModel ( 'import' );
			$options = $importer->getImportOptions ();
			foreach ( $options as $option ) {
				$app->setUserState ( 'com_kunenaimporter.' . $option, -1 );
			}
		}

		$this->setredirect ( 'index.php?option=com_kunenaimporter' );
	}
	
	public function stopmapping() {
		$this->setredirect ( 'index.php?option=com_kunenaimporter&view=users' );
	}

	public function stopimport() {
		$this->setredirect ( 'index.php?option=com_kunenaimporter' );
	}

	public function truncatetable() {
		$limit = 1000;
		$timeout = false;

		$app = JFactory::getApplication ();

		$component = JComponentHelper::getComponent ( 'com_kunenaimporter' );
		$params = new JParameter ( $component->params );
		$newparams = JRequest::getVar ( 'params' );
		if (!empty($newparams)) {
			foreach ($newparams as $param=>$value) {
				if ($params->get ( $param ) != $value) {
					$this->save();
					break;
				}
			}
		}

		$importer = $this->getModel ( 'import' );
		$options = $importer->getImportOptions ();
		$state = $this->getParams ();
		$optlist = array ();
		foreach ( $options as $option ) {
			if (isset ( $state [$option] )) {
				$app->setUserState ( 'com_kunenaimporter.' . $option, -1 );
				$importer->truncateData ( $option );
				$optlist [] = $option;
			}
		}

		$app->enqueueMessage ( 'Deleted ' . implode ( ', ', $optlist ) );
		$this->setredirect ( 'index.php?option=com_kunenaimporter' );
	}

	public function truncatemap() {
		$importer = $this->getModel ( 'import' );
		$importer->truncateUsersMap ();
		$app = JFactory::getApplication ();
		$app->setUserState ( 'com_kunenaimporter.users', -1 );
		$app->setUserState ( 'com_kunenaimporter.mapusers', -1 );
		$app->setUserState ( 'com_kunenaimporter.createusers', -1 );
		$app->setUserState ( 'com_kunenaimporter.Users', 0 );
		$app->enqueueMessage ( 'Deleted user mapping' );
		$this->setredirect ( 'index.php?option=com_kunenaimporter&view=users' );
	}

	public function mapusers() {
		$limit = 100;
		$timeout = false;

		$app = JFactory::getApplication ();

		$component = JComponentHelper::getComponent ( 'com_kunenaimporter' );
		$params = new JParameter ( $component->params );
		$extforum = $params->get ( 'extforum' );
		$exporter = $this->getModel ( $extforum ? 'export_' . $extforum : 'export' );
		$success = $exporter->detect ();
		$errormsg = $exporter->getError ();
		$importer = $this->getModel ( 'import' );
		$importer->setAuthMethod ( $exporter->getAuthMethod () );

		if (!$success || $errormsg)
			return;

		$empty = array('start'=>0, 'all'=>0, 'new'=>0, 'conflict'=>0, 'failed'=>0, 'total'=>0);
		$result = $app->getUserState ( 'com_kunenaimporter.MapUsersRes', $empty );
		do {
			$result = $importer->mapUsers ( $result, $limit );
			$app->setUserState ( 'com_kunenaimporter.MapUsers', intval($result['all']) );
			$app->setUserState ( 'com_kunenaimporter.MapUsersRes', $result );
			$timeout = $this->checkTimeout ();
		} while ( $result['now'] && ! $timeout );

		if ($timeout) {
			$view = '&view=mapusers';
		} else {
			$view = '&view=users';
			$app->enqueueMessage ( "Mapped {$result['new']}/{$result['total']} unmapped users. Conflicts: {$result['conflict']}. Errors: {$result['failed']}." );
			$app->setUserState ( 'com_kunenaimporter.MapUsers', 0 );
			$app->setUserState ( 'com_kunenaimporter.MapUsersRes', $empty );
		}
		$this->setredirect ( 'index.php?option=com_kunenaimporter' . $view );
	}

	public function selectuser() {
		$extid = JRequest::getInt ( 'extid', 0 );
		$cid = JRequest::getVar ( 'cid', array (0), 'post', 'array' );
		$userdata ['id'] = array_shift ( $cid );
		if ($userdata ['id'] == 'NEW') {
			$userdata ['id'] = 0;
		} elseif (!intval($userdata ['id'])) {
			$this->setredirect ( 'index.php?option=com_kunenaimporter&view=users' );
			return;
		} elseif ($userdata ['id'] < 0) {
			$userdata ['id'] = JRequest::getInt ( 'userid', 0 );
		}
		$replace = JRequest::getInt ( 'replace', 0 );
		
		require_once (JPATH_COMPONENT . DS . 'models' . DS . 'kunena.php');
		$importer = $this->getModel ( 'import' );

		$extuser = JTable::getInstance ( 'ExtUser', 'KunenaImporterTable' );
		$extuser->load ( $extid );
		$success = true;
		$oldid = $extuser->id;
		if ($oldid > 0) $importer->updateUserData($oldid, -$extid);
		if ($userdata ['id'] == 0) {
			$userdata ['id'] = $importer->createUser($extuser);
			if (!is_numeric($userdata ['id'] )) {
				$this->setredirect ( 'index.php?option=com_kunenaimporter&view=users', $userdata ['id'], 'notice' );
				return;
			}
		}
		if ($userdata ['id'] > 0) $success = $importer->updateUserData(-$extid, $userdata ['id'], $replace);
		if ($success && $extuser->save ( $userdata ) === false) {
			die("ERROR: Saving external data for $userdata->username failed: " . $extuser->getError () . "<br />");
			$importer->updateUserData($userdata ['id'], $oldid);
		}
		if (!$success) {
			$importer->updateUserData(-$extid, $oldid);
		}

		$this->setredirect ( 'index.php?option=com_kunenaimporter&view=users' );
	}

	public function importforum() {
		$limit = 1000;
		$timeout = false;

		$app = JFactory::getApplication ();

		$component = JComponentHelper::getComponent ( 'com_kunenaimporter' );
		$params = new JParameter ( $component->params );
		$newparams = JRequest::getVar ( 'params' );
		if (!empty($newparams)) {
			foreach ($newparams as $param=>$value) {
				if ($params->get ( $param ) != $value) {
					$this->save();
					$this->setredirect ( 'index.php?option=com_kunenaimporter', 'Configuration saved. Please try again.', 'notice' );
					return;
				}
			}
		}
		
		$extforum = $params->get ( 'extforum' );
		$exporter = $this->getModel ( $extforum ? 'export_' . $extforum : 'export' );
		$success = $exporter->detect ();
		$errormsg = $exporter->getError ();
		$importer = $this->getModel ( 'import' );
		$importer->setAuthMethod ( $exporter->getAuthMethod () );

		$options = $importer->getImportOptions ();
		$state = $this->getParams ();

		if (!$success || $errormsg)
			return;

		foreach ( $options as $option ) {
			$start = ( int ) $app->getUserState ( 'com_kunenaimporter.' . $option );
			if (isset ( $state [$option] )) {
				$count = 0;
				// Whether to truncate Joomla users table
				$truncatejoomla = $exporter->external && $params->get ('usermode') == 'external';
				do {
					if ($start < 0 || $state [$option]) {
						$importer->truncateData ( $option, $truncatejoomla );
						$state [$option] = $start = 0;
					}
					$data = $exporter->exportData ( $option, $start, $limit );
					$icount = $importer->importData ( $option, $data );
					$count = count ( $data );
					$start += $count;
					if ($option == 'createusers') {
						$app->setUserState ( 'com_kunenaimporter.mapusers', $app->getUserState ( 'com_kunenaimporter.mapusers' )+$icount );
					}
					$app->setUserState ( 'com_kunenaimporter.' . $option, $start );
					$timeout = $this->checkTimeout ();
					unset ( $data );
				} while ( $count && ! $timeout );
			}
			if ($timeout)
				break;
		}

		$message = null;
		$view = '';
		if ($timeout)
			$view = '&view=import';
		else {
			$message = "Import done!";
		}
		$app->setUserState ( 'com_kunenaimporter.state', $state );
		$this->setredirect ( 'index.php?option=com_kunenaimporter' . $view, $message );

	/*
		// Check errors
		$query = "SELECT * FROM `#__kunenaimporter_users` WHERE userid=0 OR conflict>0 OR error!=''";
		$db->setQuery($query);
		$userlist = $db->loadObjectList();
		if (count($userlist)) {
			echo "<ul>";
			foreach ($userlist as $user) {
				echo "<li>";
				if ($user->userid == 0) {
					$error = JText::_($user->error);
					echo "<b>SAVING USER FAILED:</b> $user->extname ($user->extuserid):  $error<br />";
				} else {
					echo "<b>USERNAME CONFLICT:</b> $user->extname ($user->extuserid): $user->userid == $user->conflict<br />";
				}
				echo "</li>";
			}
			echo "</ul>";
		}
*/
	}

	public function save() {
		$component = 'com_kunenaimporter';

		$table = JTable::getInstance ( 'component' );
		if (! $table->loadByOption ( $component )) {
			JError::raiseWarning ( 500, 'Not a valid component' );
			return false;
		}

		$post = JRequest::get ( 'post' );
		$post ['option'] = $component;
		$table->bind ( $post );

		if ($table->save ( $post )) {
			$msg = JText::_ ( 'Configuration Saved' );
			$type = 'message';
		} else {
			$msg = JText::_ ( 'Error Saving Configuration' );
			$type = 'notice';
		}

		// Check the table in so it can be edited.... we are done with it anyway
		$link = 'index.php?option=com_kunenaimporter';
		$this->setRedirect ( $link, $msg, $type );
	}

	public function display() {
		$params = getKunenaImporterParams();
		$forum = $params->get('extforum');
		if (!$forum) {
			$cmd = 'start';
		}

		$cmd = !empty($cmd) ? $cmd : JRequest::getCmd ( 'view', 'default' );
		$view = $this->getView ( $cmd, 'html' );
		$component = JComponentHelper::getComponent ( 'com_kunenaimporter' );
		$params = new JParameter ( $component->params );
		$view->setModel ( $this->getModel ( 'import' ), true );
		$extforum = $params->get ( 'extforum' );
		$view->setModel ( $this->getModel ( $extforum ? 'export_' . $extforum : 'export' ), false );

		if ($cmd != 'start') {
			JSubMenuHelper::addEntry ( JText::_ ( 'Choose Your Software' ), 'index.php?option=com_kunenaimporter&view=start', $cmd == 'default' );
			JSubMenuHelper::addEntry ( JText::_ ( 'Importer Configuration' ), 'index.php?option=com_kunenaimporter', $cmd == 'default' );
			JSubMenuHelper::addEntry ( JText::_ ( 'Migrate Users' ), 'index.php?option=com_kunenaimporter&view=users', $cmd == 'users' );
		}

		$view->display ();
	}

	protected function checkTimeout() {
		static $start = null;

		list ( $usec, $sec ) = explode ( ' ', microtime () );
		$time = (( float ) $usec + ( float ) $sec);

		if (empty ( $start ))
			$start = $time;

		if ($time - $start < 4)
			return false;
		return true;
	}

	protected function getParams() {
		$app = JFactory::getApplication ();
		$form = JRequest::getBool ( 'form' );

		if ($form) {
			$state = JRequest::getVar ( 'cid', array (), 'post', 'array' );
			$app->setUserState ( 'com_kunenaimporter.state', $state );
		} else {
			$state = $app->getUserState ( 'com_kunenaimporter.state' );
			if (! is_array ( $state ))
				$state = array ();
			JRequest::setVar ( 'cid', $state, 'post' );
		}
		return $state;
	}

	protected function redirectBack() {
		$httpReferer = JRequest::getVar ( 'HTTP_REFERER', JURI::base ( true ), 'server' );
		JFactory::getApplication ()->redirect ( $httpReferer );
	}
}