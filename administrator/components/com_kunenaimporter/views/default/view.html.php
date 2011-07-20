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

class KunenaimporterViewDefault extends JView {

	function display($tpl = null) {
		$app = JFactory::getApplication ();
		$params = getKunenaImporterParams ();

		$importer = $this->getModel ( 'import' );
		$extforum = $params->get ( 'extforum' );
		$exporter = $this->getModel ( $extforum ? 'export_' . $extforum : 'export' );

		$this->pane	= JPane::getInstance('sliders');
		$this->assign ( 'title', $exporter->title );
		if ($exporter->external) {
			$this->assign ( 'params', $params );
		}
		$this->options = '';
		if (is_object ( $exporter )) {

			$success = $exporter->detect ();
			$errormsg = $exporter->getError ();
			if ($success && ! $errormsg) {
				$options = $exporter->getExportOptions ( $importer );
				$this->assign ( 'options', $options );
			}
			$messages = $exporter->getMessages ();
			$this->assign ( 'messages', $messages );
		} else {
			$errormsg = 'Exporter not found!';
		}
		$this->assign ( 'errormsg', $errormsg );
		if (! $errormsg) {
			JToolBarHelper::custom ( 'import', 'upload', 'upload', JText::_ ( 'Import' ), false );
			JToolBarHelper::custom ( 'truncate', 'delete', 'delete', JText::_ ( 'Truncate' ), false );
			JToolBarHelper::divider ();
		}
		if ($exporter->external) {
			JToolBarHelper::save ( 'save', JText::_ ( 'Save Settings' ) );
		}

		parent::display ( $tpl );
	}
}