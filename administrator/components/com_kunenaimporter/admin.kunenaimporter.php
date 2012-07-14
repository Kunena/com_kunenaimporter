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

// Access check in Joomla! 2.5.
if (version_compare(JVERSION, '1.6', '>')) {
	if (!JFactory::getUser()->authorise('core.manage', 'com_kunenaimporter')) {
		return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
	}
}

// Initialize the controller.
require_once JPATH_COMPONENT . '/controller.php';
$controller = new KunenaImporterController ();

// Perform the Request task.
$controller->execute ( JRequest::getCmd ( 'task' ) );
$controller->redirect ();
