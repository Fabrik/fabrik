<?php
/**
 * Fabrik Admin Upgrade Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once 'fabmodeladmin.php';

/**
 * Fabrik Admin Upgrade Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikModelUpgrade extends FabModelAdmin
{
	/**
	 * Construct
	 *
	 * @param   array $config options
	 */

	public function __construct($config = array())
	{
		$this->fundleMenus();

		if (!$this->shouldUpgrade())
		{
			return parent::__construct($config);
		}

		$this->upgrade();

		return parent::__construct($config);
	}

	/**
	 * Back up the fabrik db tables and make copies of the data tables they use
	 *
	 * @return  bool
	 */

	protected function backUp()
	{
		$db    = JFactory::getDbo(true);
		$app   = JFactory::getApplication();
		$query = $db->getQuery(true);
		$query->select('db_table_name, connection_id')->from('#__fabrik_tables');
		$db->setQuery($query);
		$tables    = $db->loadObjectList('db_table_name') + $this->getFabrikTables();
		$listModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
		$connModel = JModelLegacy::getInstance('Connection', 'FabrikFEModel');
		$cnnTables = array();

		foreach ($tables as $dbName => $item)
		{
			$connModel->setId($item->connection_id);
			$connModel->getConnection($item->connection_id);
			$cDb = $connModel->getDb();

			if (!array_key_exists($item->connection_id, $cnnTables))
			{
				$cnnTables[$item->connection_id] = $cDb->getTableList();
			}

			$listModel->set('connection', $connModel);
			$qTable     = $cDb->quoteName('bkup_' . $item->db_table_name);
			$qItemTable = $cDb->quoteName($item->db_table_name);

			// Drop the bkup table
			$cDb->dropTable($qTable);

			// Test table exists
			if (!in_array($item->db_table_name, $cnnTables[$item->connection_id]))
			{
				$app->enqueueMessage('backup: table not found: ' . $item->db_table_name, 'notice');
				continue;
			}
			// Create the bkup table (this method will also correctly copy table indexes
			$cDb->setQuery("CREATE TABLE IF NOT EXISTS " . $qTable . " LIKE " . $qItemTable);
			$cDb->execute();
			$cDb->setQuery("INSERT INTO " . $qTable . " SELECT * FROM " . $qItemTable);
			$cDb->execute();
		}

		return true;
	}

	/**
	 * Upgrade the database to fabrik3's structure.
	 *
	 * @return  void
	 */

	protected function upgrade()
	{
		if (!$this->shouldUpgrade())
		{
			JFactory::getApplication()->enqueueMessage('Already updated');

			return;
		}

		if (!$this->backUp())
		{
			return;
		}

		$db      = JFactory::getDbo(true);
		$updates = array('#__fabrik_elements', '#__fabrik_cron', '#__fabrik_forms', '#__fabrik_groups', '#__fabrik_joins', '#__fabrik_jsactions',
			'#__fabrik_tables', '#__fabrik_visualizations');

		foreach ($updates as $update)
		{
			$db->setQuery("SELECT * FROM $update");
			$rows = $db->loadObjectList();

			foreach ($rows as $row)
			{
				$json = json_decode($row->attribs);

				if ($json == false)
				{
					// Only do this if the attribs are not already in json format
					$p = $this->fromAttribsToObject($row->attribs);

					switch ($update)
					{
						case '#__fabrik_elements':
							// Elements had some fields moved into the attribs/params json object
							if ($row->state == 0)
							{
								$row->state = -2;
							}

							$p->can_order     = $row->can_order;
							$row->access      = isset($row->access) ? $this->mapACL($row->access) : 1;
							$p->view_access   = isset($p->view_access) ? $this->mapACL($p->view_access) : 1;
							$p->filter_access = isset($p->filter_access) ? $this->mapACL($p->filter_access) : 1;

							$p->sum_access    = isset($p->sum_access) ? $this->mapACL($p->sum_access) : 1;
							$p->avg_access    = isset($p->avg_access) ? $this->mapACL($p->avg_access) : 1;
							$p->median_access = isset($p->median_access) ? $this->mapACL($p->median_access) : 1;
							$p->count_access  = isset($p->count_access) ? $this->mapACL($p->count_access) : 1;

							$subOpts = new stdClass;

							$subOpts->sub_values            = explode('|', $row->sub_values);
							$subOpts->sub_labels            = explode('|', $row->sub_labels);
							$subOpts->sub_initial_selection = explode('|', $row->sub_intial_selection);
							$p->sub_options                = $subOpts;
							break;
						case '#__fabrik_tables':
							$row->access           = $this->mapACL($row->access);
							$p->allow_view_details = isset($p->allow_view_details) ? $this->mapACL($p->allow_view_details) : 1;
							$p->allow_edit_details = isset($p->allow_edit_details) ? $this->mapACL($p->allow_edit_details) : 1;
							$p->allow_add          = isset($p->allow_add) ? $this->mapACL($p->allow_add) : 1;
							$p->allow_drop         = isset($p->allow_drop) ? $this->mapACL($p->allow_drop) : 1;
							break;

						case '#__fabrik_visualizations':
							$row->access = isset($row->access) ? $this->mapACL($row->access) : 1;
							break;
					}

					$row->attribs = json_encode($p);
					$db->updateObject($update, $row, 'id');
				}
			}
		}
		// Get the upgrade script
		$sql    = file_get_contents(JPATH_SITE . '/administrator/components/com_fabrik/sql/2.x-3.0.sql');
		$prefix = JFactory::getApplication()->getCfg('dbprefix');
		$sql    = str_replace('#__', $prefix, $sql);
		$sql    = explode("\n", $sql);

		foreach ($sql as $q)
		{
			$db->setQuery($q);

			if (trim($q) !== '')
			{
				$db->execute();
			}
		}

		// Run fabrik ratings outside mysql script as it may not exist and error
		$db = JFactory::getDBO();

		// Check if #__fabrik_ratings table exists
		$fabrate = "SHOW TABLES LIKE '" . $prefix . "fabrik_ratings'";
		$db->setQuery($fabrate);
		$rateresult = $db->loadObjectList();

		if (count($rateresult))
		{
			$db->setQuery("ALTER TABLE " . $prefix . "fabrik_ratings CHANGE `tableid` `listid` INT( 6 ) NOT NULL");
			$db->execute();
		}

		JFactory::getApplication()->enqueueMessage('Upgraded OK!');

	}

	/**
	 * Hack #__menu table to update them for Fabrik3 url structure
	 *
	 * @return  void
	 */

	protected function fundleMenus()
	{
		$db = JFactory::getDbo();
		$db->setQuery('select extension_id FROM 	#__extensions WHERE type = "component" and element = "com_fabrik"');
		$cid = (int) $db->loadResult();
		$db->setQuery('UPDATE #__menu SET component_id = ' . $cid . ' WHERE link LIKE \'%com_fabrik%\'');
		$db->execute();

		$db->setQuery("UPDATE #__menu SET link = REPLACE(link, 'view=table', 'view=list') WHERE component_id = " . $cid);
		echo $db->getQuery() . "<br>";
		$db->execute();

		$db->setQuery("UPDATE #__menu SET link = REPLACE(link, 'tableid=', 'listid=') WHERE component_id = " . $cid);
		echo $db->getQuery() . "<br>";
		$db->execute();

		$db->setQuery("UPDATE #__menu SET link = REPLACE(link, 'fabrik=', 'formid=') WHERE component_id = " . $cid);
		echo $db->getQuery() . "<br>";
		$db->execute();
	}

	/**
	 * Convert old skool J1.5 attribs into json object
	 *
	 * @param   string $str f2 parameters string
	 *
	 * @return  object
	 */

	protected function fromAttribsToObject($str)
	{
		$o = new stdClass;
		$a = explode("\n", $str);

		foreach ($a as $line)
		{
			if (strstr($line, '='))
			{
				list($key, $val) = explode("=", $line, 2);

				if (strstr($val, '//..*..//'))
				{
					$val = explode('//..*..//', $val);
				}

				if ($key)
				{
					$o->$key = $val;
				}
			}
		}

		return $o;
	}

	/**
	 * Maps the fabrik2 user gid to a roughly corresponding J1.7 acl group
	 *
	 * @param   int $v gid
	 *
	 * @return int group id
	 */

	protected function mapACL($v)
	{
		switch ($v)
		{
			case 0:
			case 29:
				$group = 1;
				break;
			case 18:
				$group = 2;
				break;
			default:
				$group = 3;
				break;
		}

		return $group;
	}

	/**
	 * Get all the db tables which have _fabrik_ as part of their names
	 *
	 * @return array of objects each with db_table_name and connection_id property
	 */

	protected function getFabrikTables()
	{
		$db = JFactory::getDbo(true);
		$r  = array();
		$db->setQuery("SHOW TABLES");
		$rows = $db->loadColumn();

		foreach ($rows as $row)
		{
			if (strstr($row, '_fabrik_') && !strstr($row, 'bkup_'))
			{
				$o                = new stdClass;
				$o->db_table_name = $row;
				$o->connection_id = 1;
				$r[]              = $o;
			}
		}

		return $r;
	}

	/**
	 * Check for an existence of _fabrik_tables table if there is then we should upgrade
	 *
	 * @return  bool
	 */

	protected function shouldUpgrade()
	{
		$db = JFactory::getDbo(true);
		$db->setQuery("SHOW TABLES");
		$rows = $db->loadColumn();

		foreach ($rows as $row)
		{
			if (strstr($row, '_fabrik_tables') && !strstr($row, 'bkup_'))
			{
				return true;
			}
		}

		return false;
	}
}
