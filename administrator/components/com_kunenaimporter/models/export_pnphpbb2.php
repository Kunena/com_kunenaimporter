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

// Everything else than user import can be found from here:
require_once (JPATH_COMPONENT . '/models/export_phpbb2.php');

class KunenaimporterModelExport_PNphpBB2 extends KunenaimporterModelExport_phpBB2 {
	/**
	 * Extension name ([a-z0-9_], wihtout 'com_' prefix)
	 * @var string
	 */
	public $name = 'pnphpbb2';
	/**
	 * Display name
	 * @var string
	 */
	public $title = 'PNphpBB2';
	/**
	 * Minimum required version
	 * @var string or null
	 */
	protected $versionmin = '2.2i-p3';
	/**
	 * Maximum accepted version
	 * @var string or null
	 */
	protected $versionmax = null;

	/**
	 * Get component version
	 */
	public function getVersion() {
		$query = "SELECT config_value FROM #__config WHERE config_name='pnphpbb2_version'";
		$this->ext_database->setQuery ( $query );
		$version = $this->ext_database->loadResult ();
		return $version;
	}

	public function countUsers() {
		$prefix = $this->ext_database->_table_prefix;
		$prefix = substr ( $prefix, 0, strpos ( $prefix, '_phpbb_' ) );

		$query = "SELECT COUNT(*)
		FROM #__users AS f
		LEFT JOIN {$prefix}_users AS u ON u.pn_uid = user_id
		WHERE user_id > 0 && user_lastvisit>0";
		return $this->getCount ( $query );
	}

	public function &exportUsers($start = 0, $limit = 0) {
		$prefix = $this->ext_database->_table_prefix;
		$prefix = substr ( $prefix, 0, strpos ( $prefix, '_phpbb_' ) );

		// PostNuke
		$query = "SELECT
			u.pn_uid AS extuserid,
			u.pn_uname AS username,
			pn_email AS email,
			pn_pass AS password,
			pn_user_regdate,
			f.*,
			(b.ban_userid>0) AS blocked
		FROM #__users AS f
		LEFT JOIN {$prefix}_users AS u ON u.pn_uid = user_id
		LEFT OUTER JOIN #__banlist AS b ON u.pn_uid = b.ban_userid
		WHERE user_id > 0 && user_lastvisit>0
		ORDER BY u.pn_uid";

		$result = $this->getExportData ( $query, $start, $limit, 'extuserid' );

		foreach ( $result as &$row ) {
			$row->name = $row->username = $row->username;

			if ($row->user_regdate > $row->pn_user_regdate)
				$row->user_regdate = $row->pn_user_regdate;
				// Convert date for last visit and register date.
			$row->registerDate = date ( "Y-m-d H:i:s", $row->user_regdate );
			$row->lastvisitDate = date ( "Y-m-d H:i:s", $row->user_lastvisit );

			// Set user type and group id - 1=admin, 2=moderator
			if ($row->user_level == "1") {
				$row->usertype = "Administrator";
			} else {
				$row->usertype = "Registered";
			}

			// Convert bbcode in signature
			$row->user_sig = prep ( $row->user_sig );

			// No imported users will get mails from the admin
			$row->emailadmin = "0";

			unset ( $row->user_regdate, $row->user_lastvisit, $row->user_level );
		}
		return $result;
	}

}