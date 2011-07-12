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

// Import Joomla! libraries
jimport ( 'joomla.application.component.model' );
jimport ( 'joomla.application.application' );
jimport ( 'joomla.filesystem.file' );
jimport ( 'joomla.filesystem.folder' );

// Kunena wide defines
$kunena_defines = JPATH_ROOT . '/components/com_kunena/lib/kunena.defines.php';
if (file_exists ( $kunena_defines ))
	require_once ($kunena_defines);

class KunenaimporterModelExport extends JModel {
	/**
	 * Extension name ([a-z0-9_], wihtout 'com_' prefix)
	 * @var string
	 */
	public $name = null;
	/**
	 * Display name
	 * @var string
	 */
	public $title = null;
	/**
	 * External application
	 * @var bool
	 */
	public $external = false;
	/**
	 * Minimum required version
	 * @var string or null
	 */
	protected $versionmin = null;
	/**
	 * Maximum accepted version
	 * @var string or null
	 */
	protected $versionmax = null;
	/**
	 * Current version
	 * @var string
	 */
	protected $version = null;
	/**
	 * Error message
	 * @var string or null
	 */
	protected $error = null;

	public $params = null;
	protected $relpath = null;
	protected $basepath = null;
	var $ext_database = null;
	var $ext_same = false;
	var $messages = array ();
	var $importOps = array ();
	var $auth_method;

	public function __construct() {
		parent::__construct ();

		// Get component parameters
		$this->params = getKunenaImporterParams();

		$this->getPath();		
		if (!$this->detectComponent()) return;

		$this->ext_database = $this->getDatabase();
		$this->buildImportOps ();
		$this->initialize();
	}

	/**
	 * Get forum path from importer configuration
	 * 
	 * @return string Relative path
	 */
	public function getPath($absolute = false) {
		if (!$this->external) return;
		// No path set, try auto detecting forum
		if (!$this->params->get('path')) {
			$folders = JFolder::folders(JPATH_ROOT);
			foreach ($folders as $folder) {
				if ($this->detectComponent(JPATH_ROOT."/{$folder}")) {
					$path = $folder;
					break;
				}
			}
		}

		$this->relpath = isset($path) ? $path : $this->params->get('path');
		$this->basepath = JPATH_ROOT."/{$this->relpath}";
		return $absolute ? $this->basepath : $this->relpath;
	}
	
	public function detectComponent() {
		if ($this->external || !JComponentHelper::getComponent ( "com_{$this->name}", true )->enabled) {
			return false;
		}
		return true;
	}

	public function getDatabase() {
		return JFactory::getDBO ();
	}
	
	public function initialize() {
	}

	public function getExportOptions($importer) {
		$app = JFactory::getApplication ();

		$options = $importer->getImportOptions ();
		$exportOpt = array ();
		foreach ( $options as $option ) {
			$count = $this->countData ( $option );
			if ($count !== false)
				$exportOpt [] = array (
				'name' => $option,
				'task' => 'COM_KUNENAIMPORTER_TASK_' . $option,
				'desc' => 'COM_KUNENAIMPORTER_DESCRIPTION_' . $option,
				'status' => ( int ) $app->getUserState ( 'com_kunenaimporter.' . $option ),
				'total' => $count );
		}
		return $exportOpt;
	}

	public function buildImportOps() {
		$this->importOps = array();
	}
	
	public function isCompatible($version) {
		if ((!empty($this->versionmin) && version_compare($version, $this->versionmin, '<')) ||
			(!empty($this->versionmax) && version_compare($version, $this->versionmax, '>'))) {
			return false;
		}
		return true;
	}

	public function detect() {
		$this->addMessage ( '<h2>Importer Status</h2>' );

		// Kunena detection and version check
		$minKunenaVersion = '1.6.4';
		if (! class_exists ( 'Kunena' ) || version_compare(Kunena::version(), $minKunenaVersion, '<')) {
			$this->addMessage ( '<div>Kunena version: <b style="color:red">FAILED</b></div>' );
			$this->addMessage ( '<br /><div><b>You need to install Kunena '.$minKunenaVersion.'!</b></div>' );
			$this->error = 'Kunena not detected!';
			return false;
		}
		$this->addMessage ( '<div>Kunena version: <b style="color:green">' . Kunena::version() . '</b></div>' );

		if (get_class($this) == __CLASS__) {
			$this->addMessage ( '<br /><div><b>Please select forum software!</b></div>' );
			$this->error = 'Forum not selected!';
			return false;
		}
		
		if ($this->external) {
			if (is_dir($this->basepath)) {
				$this->addMessage ( '<div>Using relative path: <b style="color:green">./' . $this->relpath . '</b></div>' );
			} else {
				$this->error = $this->title." not found from {$this->basepath}";
				$this->addMessage ( '<div>Using relative path: <b style="color:red">./' . $this->relpath . '</b></div>' );
				$this->addMessage ( '<div><b>Error:</b> ' . $this->error . '</div>' );
				return false;
			}
		}

		if (!$this->detectComponent()) {
			$this->error = $this->title.' has not been installed into your system!';
			$this->addMessage ( '<div>Detecting '.$this->title.': <b style="color:red">FAILED</b></div>' );
			$this->addMessage ( '<br /><div><b>Error:</b> ' . $this->error . '</div>' );
			return false;
		}
		$this->addMessage ( '<div>Detecting '.$this->title.': <b style="color:green">OK</b></div>' );

		if (JError::isError ( $this->ext_database ))
			$this->error = $this->ext_database->toString ();
		elseif (!$this->ext_database) {
			$this->error = 'Database not configured.';
		}
		if ($this->error) {
			$this->addMessage ( '<div>Database connection: <b style="color:red">FAILED</b></div>' );
			$this->addMessage ( '<br /><div><b>Please check that your external database settings are correct!</b></div><div><b>Error:</b> ' . $this->error . '</div>' );
			return false;
		}
		$this->addMessage ( '<div>Database connection: <b style="color:green">OK</b></div>' );

		// Check if version is compatible with importer
		$this->version = $this->getVersion();
		if (!$this->isCompatible($this->version)) {
			$this->error = "Unsupported forum: {$this->title} {$this->version}";
			$this->addMessage ( '<div>'.$this->title.' version: <b style="color:red">' . $this->version . '</b></div>' );
			$this->addMessage ( '<div><b>Error:</b> ' . $this->error . '</div>' );
			return false;
		}
		$this->addMessage ( '<div>'.$this->title.' version: <b style="color:green">' . $this->version . '</b></div>' );
		
		return true;
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

	public function getAuthMethod() {
		return $this->auth_method;
	}

	protected function addMessage($msg) {
		$this->messages [] = $msg;
	}

	public function getMessages() {
		return implode ( '', $this->messages );
	}

	public function getError() {
		return $this->error;
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
	}
	
	/**
	 * Map Joomla user to external user
	 *
	 * @param object $joomlauser StdClass(id, username, email)
	 * @return int External user ID
	 */
	public function mapJoomlaUser($joomlauser) {
		return $joomlauser->id;
	}

	public function getCount($query) {
		$this->ext_database->setQuery ( $query );
		$result = $this->ext_database->loadResult ();
		if ($this->ext_database->getErrorNum ()) {
			$this->error = $this->ext_database->getErrorMsg ();
			$this->addMessage ( '<div><b>Error:</b> ' . $this->error . '</div>' );
		}
		return $result;
	}

	public function &getExportData($query, $start = 0, $limit = 0, $key = null) {
		$this->ext_database->setQuery ( $query, $start, $limit );
		$result = $this->ext_database->loadObjectList ( $key );
		if ($this->ext_database->getErrorNum ()) {
			$this->error = $this->ext_database->getErrorMsg ();
			$this->addMessage ( '<div><b>Error:</b> ' . $this->error . '</div>' );
		}
		return $result;
	}

	public function countData($operation) {
		$result = 0;
		if (empty ( $this->importOps [$operation] )) {
			$func = "count{$operation}";
			if (method_exists($this, $func)) {
				return $this->$func ();
			}
			return false;
		}
		$info = $this->importOps [$operation];
		if (! empty ( $info ['from'] )) {
			$query = "SELECT COUNT(*) FROM " . $info ['from'];
			if (! empty ( $info ['where'] ))
				$query .= ' WHERE ' . $info ['where'];
			if (! empty ( $info ['orderby'] ))
				$query .= ' ORDER BY ' . $info ['orderby'];
			$result = $this->getCount ( $query );
		} else if (! empty ( $info ['count'] ))
			$result = $this->$info ['count'] ();
		return $result;
	}

	public function &exportData($operation, $start = 0, $limit = 0) {
		$result = array ();
		if (empty ( $this->importOps [$operation] )) {
			$func = "export{$operation}";
			if (method_exists($this, $func)) {
				$result = $this->$func ( $start, $limit );
			}
			return $result;
		}
		$info = $this->importOps [$operation];
		if (! empty ( $info ['select'] ) && ! empty ( $info ['from'] )) {
			$query = "SELECT " . $info ['select'] . " FROM " . $info ['from'];
			if (! empty ( $info ['where'] ))
				$query .= ' WHERE ' . $info ['where'];
			if (! empty ( $info ['orderby'] ))
				$query .= ' ORDER BY ' . $info ['orderby'];
			$result = $this->getExportData ( $query, $start, $limit );
		} else if (! empty ( $info ['export'] ))
			$result = $this->$info ['export'] ( $start, $limit );
		return $result;
	}

	public function countMapUsers() {
		$db = JFactory::getDBO();
		$query = "SELECT COUNT(*) FROM #__users";
		$db->setQuery ($query);
		return $db->loadResult ();
	}

	public function &exportMapUsers($start = 0, $limit = 0) {
		$db = JFactory::getDBO();
		$query = "SELECT id, username, email FROM #__users";
		$db->setQuery ( $query, $start, $limit );
		$users = $db->loadObjectList ( 'id' );
		$count = 0;
		foreach ($users as $user) {
			$count++;
			$extid = $this->mapJoomlaUser($user);
			if ($extid) {
				$extuser = JTable::getInstance ( 'ExtUser', 'KunenaImporterTable' );
				$extuser->load ( $extid );
				if ($extuser->exists() && !$extuser->id) {
					$extuser->id = $user->id;
					if ($extuser->store () === false) {
						die("ERROR: Saving external data for $user->username failed: " . $extuser->getError () . "<br />");
					}
				}
			}
		}
		return $users;
	}

	public function &exportJoomlaUsers($start = 0, $limit = 0) {

	}
}