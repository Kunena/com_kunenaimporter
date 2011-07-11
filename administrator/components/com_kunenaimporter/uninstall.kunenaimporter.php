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

// Set a simple message
$application = JFactory::getApplication ();
$application->enqueueMessage ( JText::_ ( 'NOTE: Database tables were NOT removed to allow for upgrades' ), 'notice' );