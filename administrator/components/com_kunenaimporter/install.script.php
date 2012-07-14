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

class Com_KunenaImporterInstallerScript {
	protected $versions = array(
		'PHP' => array (
			'5.2' => '5.2.4',
			'0' => '5.3.10' // Preferred version
		),
		'MySQL' => array (
			'5.0' => '5.0.4',
			'0' => '5.1' // Preferred version
		),
		'Joomla!' => array (
			'1.5' => '1.5.25',
			'1.6' => '2.5.3',
			'0' => '2.5.4' // Preferred version
		)
	);
	protected $extensions = array ('dom', 'gd', 'json', 'pcre', 'SimpleXML');

	public function preflight($type, $parent) {
		// Prevent installation if requirements are not met.
		if (!$this->checkRequirements()) return false;

		return true;
	}

	function postflight($type, $parent) {
		// Rename manifest file
		$path = $parent->getParent()->getPath('extension_root');
		$name = $parent->get('name');
		if (JFile::exists("{$path}/{$name}.j25.xml")) {
			if ( JFile::exists("{$path}/{$name}.xml")) JFile::delete("{$path}/{$name}.xml");
			JFile::move("{$path}/{$name}.j25.xml", "{$path}/{$name}.xml");
		}
	}

	public function checkRequirements() {
		$db = JFactory::getDbo();
		$pass  = $this->checkVersion('PHP', phpversion());
		$pass &= $this->checkVersion('Joomla!', JVERSION);
		$pass &= $this->checkVersion('MySQL', $db->getVersion ());
		$pass &= $this->checkDbo($db->name, array('mysql', 'mysqli'));
		$pass &= $this->checkExtensions($this->extensions);
		return $pass;
	}

	// Internal functions

	protected function checkVersion($name, $version) {
		$app = JFactory::getApplication();

		foreach ($this->versions[$name] as $major=>$minor) {
			if (!$major || version_compare ( $version, $major, "<" )) continue;
			if (version_compare ( $version, $minor, ">=" )) return true;
			break;
		}
		$recommended = end($this->versions[$name]);
		$app->enqueueMessage(sprintf("%s %s is not supported. Minimum required version is %s %s, but it is higly recommended to use %s %s or later.", $name, $version, $name, $minor, $name, $recommended), 'notice');
		return false;
	}

	protected function checkDbo($name, $types) {
		$app = JFactory::getApplication();

		if (in_array($name, $types)) {
			return true;
		}
		$app->enqueueMessage(sprintf("Database driver '%s' is not supported. Please use MySQL instead.", $name), 'notice');
		return false;
	}

	protected function checkExtensions($extensions) {
		$app = JFactory::getApplication();

		$pass = 1;
		foreach ($extensions as $name) {
			if (!extension_loaded($name)) {
				$pass = 0;
				$app->enqueueMessage(sprintf("Required PHP extension '%s' is missing. Please install it into your system.", $name), 'notice');
			}
		}
		return $pass;
	}
}
