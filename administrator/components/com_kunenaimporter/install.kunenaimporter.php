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

function com_install() {
	// Update database
	$db = JFactory::getDBO ();
	$update_queries = array (
		"DROP TABLE IF EXISTS `#__knimporter_user`", 
		"DROP TABLE IF EXISTS `#__knimport_extuser`", 
		"DROP TABLE IF EXISTS `#__knimporter_extuser`",
		"ALTER TABLE `#__kunenaimporter_users` CHANGE `password` `password` VARCHAR( 255 ) NOT NULL DEFAULT ''");
	
	// Perform all queries - we don't care if they fail
	foreach ( $update_queries as $query ) {
		$db->setQuery ( $query );
		$db->query ();
		if ($db->getErrorNum ())
			die ( "<br />Invalid query:<br />$query<br />" . $db->getErrorMsg () );
	}
}
