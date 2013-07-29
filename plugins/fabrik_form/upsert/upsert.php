<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.upsert
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

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
	 * process the plugin, called afer form is submitted
	 *
	 * @param   object  $params      Plugin params
	 * @param   object  &$formModel  Form model
	 *
	 * @return  bool
	 */

	public function onAfterProcess($params, &$formModel)
	{
		$w = new FabrikWorker;
		$this->formModel = $formModel;
		$db = $this->getDb($params);
		$query = $db->getQuery(true);

		$this->data = array_merge($this->getEmailData(), $formModel->_formData);

		$table = $this->getTableName($params);
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

		$fields = $this->upsertData($params);
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
	 * @param   JRegisistry  $params  Plugin params
	 *
	 * @return JDatabaseDriver
	 */

	protected function getDb($params)
	{
		if (!isset($this->db))
		{
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
	 * @param   JRegistry  $params  Plugin params
	 *
	 * @return  array
	 */

	protected function upsertData($params)
	{
		$w = new FabrikWorker;
		$db = $this->getDb($params);
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
	 * @param   JRegistry  $params  Plugin params
	 *
	 * @return  string
	 */

	protected function getTableName($params)
	{
		$listid = $params->get('table');
		$listModel = JModel::getInstance('list', 'FabrikFEModel');
		$listModel->setId($listid);
		return $listModel->getTable()->db_table_name;
	}

}
