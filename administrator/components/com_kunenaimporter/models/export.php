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
	public $extname = null;
	/**
	 * Display name
	 * @var string
	 */
	public $exttitle = null;
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

	public function __construct() {
		parent::__construct ();

		// Get component parameters
		$this->params = getKunenaImporterParams();

		$this->getPath();
		if (!$this->detectComponent()) return;

		$this->ext_database = $this->getDatabase();
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
			// Find forum from Joomla root
			if ($this->detectComponent(JPATH_ROOT)) {
				$path = '.';
				break;
			}
			// Count parent directories to www root
			$parents = trim(JURI::root(true), '/');
			$parents = $parents ? count(explode('/', $parents)) : 0;
			// Create subdirectory lists
			while ($parents >= 0) {
				$ppath = trim(str_repeat('/..', $parents), '/');
				$folders[$ppath] = JFolder::folders(JPATH_ROOT."/{$ppath}");
				$parents--;
			}
			// Find forum from all subdirectories
			foreach ($folders as $ppath=>$parent) {
				foreach ($parent as $folder) {
					if ($this->detectComponent(JPATH_ROOT."/{$ppath}/{$folder}")) {
						$path = trim("{$ppath}/{$folder}", '/');;
						break;
					}
				}
			}
		}

		$this->relpath = isset($path) ? $path : $this->params->get('path');
		$this->basepath = JPATH_ROOT."/{$this->relpath}";
		$this->params->set('path', $this->relpath);
		return $absolute ? $this->basepath : $this->relpath;
	}

	public function detectComponent() {
		if (!$this->extname || $this->external || !JComponentHelper::getComponent ( "com_{$this->extname}", true )->enabled) {
			return false;
		}
		return true;
	}

	public function getDatabase() {
		return JFactory::getDBO ();
	}

	public function initialize() {
	}

	public function &getConfig() {
		if (empty($this->config)) {
			$this->config = JComponentHelper::getParams( "com_{$this->extname}" );
		}
		return $this->config;
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

	public function isCompatible($version) {
		if ((!empty($this->versionmin) && version_compare($version, $this->versionmin, '<')) ||
			(!empty($this->versionmax) && version_compare($version, $this->versionmax, '>'))) {
			return false;
		}
		return true;
	}

	public function detect() {
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
				$this->relpath = JPath::clean($this->relpath);
				$this->addMessage ( '<div>Using relative path: <b style="color:green">' . $this->relpath . '</b></div>' );
			} else {
				$this->error = $this->exttitle." not found from {$this->basepath}";
				$this->addMessage ( '<div>Using relative path: <b style="color:red">' . $this->relpath . '</b></div>' );
				$this->addMessage ( '<div><b>Error:</b> ' . $this->error . '</div>' );
				return false;
			}
		}

		if (!$this->detectComponent()) {
			$this->error = $this->exttitle.' has not been installed into your system!';
			$this->addMessage ( '<div>Detecting '.$this->exttitle.': <b style="color:red">FAILED</b></div>' );
			$this->addMessage ( '<br /><div><b>Error:</b> ' . $this->error . '</div>' );
			return false;
		}
		$this->addMessage ( '<div>Detecting '.$this->exttitle.': <b style="color:green">OK</b></div>' );

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
			$this->error = "Unsupported forum: {$this->exttitle} {$this->version}";
			$this->addMessage ( '<div>'.$this->exttitle.' version: <b style="color:red">' . $this->version . '</b></div>' );
			$this->addMessage ( '<div><b>Error:</b> ' . $this->error . '</div>' );
			return false;
		}
		$this->addMessage ( '<div>'.$this->exttitle.' version: <b style="color:green">' . $this->version . '</b></div>' );

		return true;
	}

	/**
	 * Get component version
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
	protected function parseHTML(&$html) {
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->preserveWhiteSpace = false;
		$doc->loadHTML('<?xml encoding="UTF-8"><html><body><div>' . preg_replace('|[\r\n]|', ' ', $html) . '</div></body></html>');
		$nodes = $doc->getElementsByTagName('p');
		foreach ($nodes as $node) {
			$node->insertBefore(new DOMText("\n"));
		}
		$nodes = $doc->getElementsByTagName('div');
		foreach ($nodes as $node) {
			$node->insertBefore(new DOMText("\n"));
		}
		$node = $doc->getElementsByTagName('div')->item(0);
		$html = trim($this->parseHtmlChildren($node));
		libxml_clear_errors();
	}

	protected function parseHtmlChildren(DomNode $node) {
		$output = '';
		if ($node->hasChildNodes ()) {
			$children = $node->childNodes;
			foreach ( $children as $child ) {
				switch ($child->nodeType) {
					CASE XML_ELEMENT_NODE:
						$output .= $this->parseHtmlChildren ( $child );
						break;
					case XML_TEXT_NODE:
						$output .= preg_replace('/[\r\n ]+/', ' ', $child->data);
						break;
					case XML_CDATA_SECTION_NODE:
						$output .= $child->data;
						break;
				}
			}
		}
		return $this->parseHtmlNode ( $node, $output );
	}

	protected function parseHtmlNode(DomNode $node, $output) {
		$tag = $node->tagName;
		switch ($tag) {
			case 'br':
				return "\n";
			case 'b':
			case 'strong':
				return "[b]{$output}[/b]";
			case 'i':
			case 'em':
				return "[i]{$output}[/i]";
			case 'u':
				return "[u]{$output}[/u]";
			case 'span':
				$style = $node->getAttribute('style');
				if ($style == 'text-decoration: underline;') $output = "[u]{$output}[/u]";
				return $output;
			case 'a':
				return "[url={$node->getAttribute('href')}]{$output}[/url]";
			case 'img':
				return "[img]{$node->getAttribute('src')}[/img]";
			case 'address':
				return "{$output}";
			case 'h1':
				return "\n[size=6]{$output}[/size]";
			case 'h2':
				return "\n[size=5]{$output}[/size]";
			case 'h3':
				return "\n[size=4]{$output}[/size]";
			case 'h4':
				return "\n[size=3]{$output}[/size]";
			case 'h5':
				return "\n[size=2]{$output}[/size]";
			case 'h6':
				return "\n[size=1]{$output}[/size]";
			case 'pre':
			case 'li':
			case 'ul':
			case 'ol':
				return "\n[{$tag}]{$output}[/{$tag}]";
			case 'hr':
				return "\n[hr]";
			case 'p':
				$output = trim($output);
				if (!$output) return;
				$style = $node->getAttribute('style');
				if ($node->getAttribute('class' == 'caption')) $output = "[center]{$output}[/center]";
				elseif ($style == 'text-align: left;') $output = "[left]{$output}[/left]";
				elseif ($style == 'text-align: center;') $output = "[center]{$output}[/center]";
				elseif ($style == 'text-align: right;') $output = "[right]{$output}[/right]";
				return "\n{$output}";
			case 'div':
			default:
				return "{$output}";

		}
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
			JFactory::getApplication()->enqueueMessage( '<b>Error:</b> ' . $this->error, 'error' );
		}
		return $result;
	}

	public function countData($operation) {
		$result = 0;
		$func = "count{$operation}";
		if (method_exists($this, $func)) {
			return $this->$func ();
		}
		return false;
	}

	public function &exportData($operation, $start = 0, $limit = 0) {
		$result = array ();
		$func = "export{$operation}";
		if (method_exists($this, $func)) {
			$result = $this->$func ( $start, $limit );
		}
		return $result;
	}

	public function countMapUsers() {
		if (!$this->external) return false;
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

	public function countCreateUsers() {
		if (!$this->external || $this->params->get('useradd') != 'yes') return false;
		$db = JFactory::getDBO();
		$query = "SELECT COUNT(*) FROM #__kunenaimporter_users";
		$db->setQuery ( $query );
		return $db->loadResult();
	}

	public function &exportCreateUsers($start = 0, $limit = 0) {
		$db = JFactory::getDBO();
		$query = "SELECT * FROM #__kunenaimporter_users ORDER BY extid";
		$db->setQuery ( $query, $start, $limit );
		$extusers = $db->loadObjectList ( 'extid' );
		$users = array();
		foreach ($extusers as $user) {
			$extuser = JTable::getInstance ( 'ExtUser', 'KunenaImporterTable' );
			$extuser->bind ( get_object_vars($user) );
			$extuser->exists ( true );
			$users[] = $extuser;
		}
		return $users;
	}
}