<?php
/**
 * Kunena Importer component
 * @package Kunena.com_kunenaimporter
 *
 * @copyright (C) 2008 - 2012 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined('_JEXEC') or die();

jimport ( 'joomla.application.component.view' );

class KunenaImporterViewUsers extends JView {
	function display($tpl = null) {
		$app = JFactory::getApplication();
		$db = JFactory::getDBO ();
		$currentUser = JFactory::getUser ();
		$acl = JFactory::getACL ();

		$filter_order = $app->getUserStateFromRequest ( "com_kunenaimporter.filter_order", 'filter_order', 'a.username', 'cmd' );
		$filter_order_Dir = $app->getUserStateFromRequest ( "com_kunenaimporter.filter_order_Dir", 'filter_order_Dir', 'asc', 'word' );
		$filter_type = $app->getUserStateFromRequest ( "com_kunenaimporter.filter_type", 'filter_type', 'unmapped', 'string' );
		$search = $app->getUserStateFromRequest ( "com_kunenaimporter.search", 'search', '', 'string' );
		$search = JString::strtolower ( $search );

		$limit = $app->getUserStateFromRequest ( 'global.list.limit', 'limit', $app->getCfg ( 'list_limit' ), 'int' );
		$limitstart = $app->getUserStateFromRequest ( "com_kunenaimporter.limitstart", 'limitstart', 0, 'int' );

		$where = array ();
		if (isset ( $search ) && $search != '') {
			$searchEscaped = $db->Quote ( '%' . $db->getEscaped ( $search, true ) . '%', false );
			$where [] = 'a.username LIKE ' . $searchEscaped . ' OR a.email LIKE ' . $searchEscaped . ' OR a.name LIKE ' . $searchEscaped;
		}
		switch ($filter_type) {
			case 'unmapped':
				$where [] = " (a.id IS NULL) ";
				break;
			case 'mapped':
				$where [] = " a.id > 0 ";
				break;
			case 'ignored':
				$where [] = " (a.id = 0) ";
				break;
			case 'never':
				$where [] = " (a.lastvisitDate = 0 && a.id IS NULL) ";
				break;
			default:
		}
		$filter = '';

		$orderby = ' ORDER BY ' . $filter_order . ' ' . $filter_order_Dir;
		$where = (count ( $where ) ? ' WHERE (' . implode ( ') AND (', $where ) . ')' : '');

		$query = 'SELECT COUNT(*)' . ' FROM #__kunenaimporter_users AS a' . $filter . $where;
		$db->setQuery ( $query );
		$total = $db->loadResult ();

		jimport ( 'joomla.html.pagination' );
		$pagination = new JPagination ( $total, $limitstart, $limit );

		$query = 'SELECT a.* FROM #__kunenaimporter_users AS a ' . $filter . $where . $orderby;
		$db->setQuery ( $query, $pagination->limitstart, $pagination->limit );
		$rows = $db->loadObjectList ();

		$n = count ( $rows );

		// get list of Groups for dropdown filter
		$types [] = JHTML::_ ( 'select.option', '', 'All users' );
		$types [] = JHTML::_ ( 'select.option', 'unmapped', 'Unmapped users' );
		$types [] = JHTML::_ ( 'select.option', 'mapped', 'Mapped users' );
		$types [] = JHTML::_ ( 'select.option', 'ignored', 'Ignored users' );
		$types [] = JHTML::_ ( 'select.option', 'never', 'Never visited unmapped users' );
		$lists ['type'] = JHTML::_ ( 'select.genericlist', $types, 'filter_type', 'class="inputbox" size="1" onchange="document.adminForm.submit( );"', 'value', 'text', "$filter_type" );

		// get list of Log Status for dropdown filter
		$logged [] = JHTML::_ ( 'select.option', 0, '- ' . JText::_ ( 'Select Log Status' ) . ' -' );
		$logged [] = JHTML::_ ( 'select.option', 1, JText::_ ( 'Logged In' ) );

		// table ordering
		$lists ['order_Dir'] = $filter_order_Dir;
		$lists ['order'] = $filter_order;

		// search filter
		$lists ['search'] = $search;

		$this->assignRef ( 'user', JFactory::getUser () );
		$this->assignRef ( 'lists', $lists );
		$this->assignRef ( 'items', $rows );
		$this->assignRef ( 'pagination', $pagination );

		JToolBarHelper::title ( JText::_ ( 'Forum Importer: Migrate Users' ), 'kunenaimporter.png' );
		JToolBarHelper::custom ( 'mapusers', 'upload', 'upload', JText::_ ( 'Map Missing' ), false );
		//JToolBarHelper::deleteList();
		JToolBarHelper::divider();
		JToolBarHelper::custom ( 'truncatemap', 'delete', 'delete', JText::_ ( 'Delete All' ), false );
		/*JToolBarHelper::editListX();*/

		parent::display ( $tpl );
	}
}