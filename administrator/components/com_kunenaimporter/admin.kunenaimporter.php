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

@set_time_limit ( 120 );

/*
 * Define constants for all pages
 */
define ( 'COM_KUNENAIMPORTER_BASEDIR', JPATH_COMPONENT_ADMINISTRATOR );
define ( 'COM_KUNENAIMPORTER_BASEURL', JURI::root () . 'administrator/index.php?option=com_kunenaimporter' );

// Access check.
if (version_compare(JVERSION, '1.6', '>')) {
	if (!JFactory::getUser()->authorise('core.manage', 'com_kunenaimporter')) {
		return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
	}
}

$document = JFactory::getDocument ();
$document->addStyleSheet ( 'components/com_kunenaimporter/assets/importer.css' );

// Require the base controller
require_once JPATH_COMPONENT . DS . 'controller.php';

$lang = JFactory::getLanguage ();
$lang->load ( 'com_kunenaimporter', COM_KUNENAIMPORTER_BASEDIR );

$document->setTitle ( JText::_ ( 'Kunena Forum Importer' ) );
JToolBarHelper::title ( JText::_ ( 'Forum Importer' ), 'kunenaimporter.png' );

// Initialize the controller
$controller = new KunenaImporterController ();

// Perform the Request task
$controller->execute ( JRequest::getCmd ( 'task' ) );
$controller->redirect ();

function getKunenaImporterParams($component = 'com_kunenaimporter') {
	static $instance = null;
	if ($instance === null) {
		$instance = JComponentHelper::getParams ( $component );
		$instance->loadSetupFile(JPATH_ADMINISTRATOR . "/components/{$component}/config.xml");
	}
	return $instance;
}
?>
