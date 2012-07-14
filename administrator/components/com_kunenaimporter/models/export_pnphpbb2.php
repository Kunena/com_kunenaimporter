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

// Everything else than user import can be found from here:
require_once (JPATH_COMPONENT . '/models/export_phpbb2.php');

class KunenaimporterModelExport_PNphpBB2 extends KunenaimporterModelExport_phpBB2 {
	/**
	 * Extension name
	 * @var string
	 */
	public $extname = 'pnphpbb2';
	/**
	 * Display name
	 * @var string
	 */
	public $exttitle = 'PNphpBB2';
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

		$query = "SELECT
			u.pn_uid AS extid,
			u.pn_uname AS extusername,
			u.pn_uname AS name,
			u.pn_uname AS username,
			u.pn_email AS email,
			CONCAT('postnuke::', u.pn_pass) AS password,
			IF(f.user_level=1, 'Administrator', 'Registered') AS usertype,
			IF(b.ban_userid > 0 OR f.user_active = 0, 1, 0) AS block,
			FROM_UNIXTIME(MIN(u.pn_user_regdate, f.user_regdate)) AS registerDate,
			IF(f.user_lastvisit>0, FROM_UNIXTIME(f.user_lastvisit), '0000-00-00 00:00:00') AS lastvisitDate,
			NULL AS params
		FROM #__users AS f
		LEFT JOIN {$prefix}_users AS u ON u.pn_uid = user_id
		LEFT OUTER JOIN #__banlist AS b ON u.pn_uid = b.ban_userid
		WHERE user_id > 0
		ORDER BY u.pn_uid";

		$result = $this->getExportData ( $query, $start, $limit, 'extuserid' );
		foreach ( $result as &$row ) {
			$this->parseText ( $row->extusername );
			$this->parseText ( $row->name );
			$this->parseText ( $row->username );
			$this->parseText ( $row->email );
		}
		return $result;
	}
}