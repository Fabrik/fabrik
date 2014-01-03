<?php
/**
 * Update / insert a database record into any table
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.upsert
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Update / insert a database record into any table
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.upsert
 * @since       3.0.7
 */

class PlgFabrik_FormUpsert extends plgFabrik_Form
{
	/**
	 * Database driver
	 *
	 * @var JDatabaseDriver
	 */
	protected $db = null;

	/**
	 * process the plugin, called after form is submitted
	 *
	 * @return  bool
	 */

	public function onAfterProcess()
	{
		$params = $this->getParams();
		$w = new FabrikWorker;
		$formModel = $this->getModel();
		$db = $this->getDb();
		$query = $db->getQuery(true);
		$this->data = $this->getProcessData();

		$table = $this->getTableName();
		$pk = FabrikString::safeColName($params->get('primary_key'));

		$rowid = $params->get('row_value', '');

		// Used for updating previously added records. Need previous pk val to ensure new records are still created.
		$origData = $formModel->getOrigData();
		$origData = JArrayHelper::getValue($origData, 0, new stdClass);

		if (isset($origData->__pk_val))
		{
			$this->data['origid'] = $origData->__pk_val;
		}

		$rowid = $w->parseMessageForPlaceholder($rowid, $this->data, false);
		$fields = $this->upsertData();
		$query->set($fields);

		if ($rowid === '')
		{
			$query->insert($table);
		}
		else
		{
			$query->update($table)->where($pk . ' = ' . $rowid);
		}

		$db->setQuery($query);
		$db->execute();

		return true;
	}

	/**
	 * Get db
	 *
	 * @return JDatabaseDriver
	 */

	protected function getDb()
	{
		if (!isset($this->db))
		{
			$params = $this->getParams();
			$cid = $params->get('connection_id');
			$connectionModel = JModelLegacy::getInstance('connection', 'FabrikFEModel');
			$connectionModel->setId($cid);
			$this->db = $connectionModel->getDb();
		}

		return $this->db;
	}

	/**
	 * Get fields to update/insert
	 *
	 * @return  array
	 */

	protected function upsertData()
	{
		$params = $this->getParams();
		$w = new FabrikWorker;
		$db = $this->getDb();
		$upsert = json_decode($params->get('upsert_fields'));
		$fields = array();

		for ($i = 0; $i < count($upsert->upsert_key); $i++)
		{
			$k = FabrikString::shortColName($upsert->upsert_key[$i]);
			$k = $db->quoteName($k);
			$v = $upsert->upsert_value[$i];
			$v = $w->parseMessageForPlaceholder($v, $this->data);

			if ($v == '')
			{
				$v = $w->parseMessageForPlaceholder($upsert->upsert_default[$i], $this->data);
			}

			$fields[] = $k . ' = ' . $db->quote($v);
		}

		return $fields;
	}

	/**
	 * Get the table name to insert / update to
	 *
	 * @return  string
	 */

	protected function getTableName()
	{
		$params = $this->getParams();
		$listid = $params->get('table');
		$listModel = JModel::getInstance('list', 'FabrikFEModel');
		$listModel->setId($listid);

		return $listModel->getTable()->db_table_name;
	}
}
