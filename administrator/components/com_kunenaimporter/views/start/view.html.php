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
jimport ( 'joomla.application.component.view' );
jimport('joomla.html.pane');

class KunenaimporterViewStart extends JView {

	function display($tpl = null) {
		$this->pane	= JPane::getInstance('sliders');

		if (version_compare(JVERSION, '1.6', '>')) {
			if (JFactory::getUser()->authorise('core.admin', 'com_kunenaimporter')) {
				JToolBarHelper::preferences('com_kunenaimporter');
			}
		}
		parent::display ( $tpl );
	}
}