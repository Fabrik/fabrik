<?php
/**
 * Update / insert a database record into any table
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.upsert
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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
class PlgFabrik_FormUpsert extends PlgFabrik_Form
{
	/**
	 * Database driver
	 *
	 * @var JDatabaseDriver
	 */
	protected $upsertDb = null;

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
		// @FIXME to use selected connection
		$upsertDb = $this->getDb();
		$query = $upsertDb->getQuery(true);
		$this->data = $this->getProcessData();

		if (!$this->shouldProcess('upsert_conditon', null, $params))
		{
			return;
		}

		$table = $this->getTableName();
		$pk = FabrikString::safeColName($params->get('primary_key'));

		$rowId = $params->get('row_value', '');

		// Used for updating previously added records. Need previous pk val to ensure new records are still created.
		$origData = $formModel->getOrigData();
		$origData = FArrayHelper::getValue($origData, 0, new stdClass);

		if (isset($origData->__pk_val))
		{
			$this->data['origid'] = $origData->__pk_val;
		}

		$rowId = $w->parseMessageForPlaceholder($rowId, $this->data, false);
		$upsertRowExists = $this->upsertRowExists($table, $pk, $rowId);

		/**
		 * If row exists and "insert only", or row doesn't exist and "update only", bail out
		 */
		if (
			($upsertRowExists && $params->get('upsert_insert_only', '0') === '1')
			||
			(!$upsertRowExists && $params->get('upsert_insert_only', '0') === '2')
		)
		{
			return true;
		}

		$fields = $this->upsertData($upsertRowExists);

		// make sure we have at least one field to upsert
		if (empty($fields))
		{
			return true;
		}

		$query->set($fields);

		if ($rowId === '')
		{
			$query->insert($table);
		}
		else
		{
			if ($upsertRowExists)
			{
				$query->update($table)->where($pk . ' = ' . $upsertDb->quote($rowId));
			}
			else
			{
				$query->insert($table);
			}
		}

		$upsertDb->setQuery($query);
		$upsertDb->execute();

		return true;
	}

	/**
	 * Get db
	 *
	 * @return JDatabaseDriver
	 */
	protected function getDb()
	{
		if (!isset($this->upsert_db))
		{
			$params = $this->getParams();
			$cid = $params->get('connection_id');
			$connectionModel = JModelLegacy::getInstance('connection', 'FabrikFEModel');
			$connectionModel->setId($cid);
			$this->upsert_db = $connectionModel->getDb();
		}

		return $this->upsert_db;
	}

	/**
	 * Get fields to update/insert
	 *
	 * @param   bool  $upsertRowExists
	 *
	 * @return  array
	 */
	protected function upsertData($upsertRowExists = false)
	{
		$params = $this->getParams();
		$w = new FabrikWorker;
		$upsertDb = $this->getDb();
		$upsert = json_decode($params->get('upsert_fields'));
		$fields = array();

		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->getModel();

		if ($formModel->isNewRecord() || !$upsertRowExists)
		{
			if ($params->get('upsert_pk_or_fk', 'pk') == 'fk')
			{
				$row_value = $params->get('row_value', '');
				if ($row_value == '{origid}')
				{
					$fk = FabrikString::safeColName($params->get('primary_key'));
					$rowId = $formModel->getInsertId();
					$fields[] = $fk . ' = ' . $upsertDb->q($rowId);
				}
			}
		}

		for ($i = 0; $i < count($upsert->upsert_key); $i++)
		{
			$k = FabrikString::shortColName($upsert->upsert_key[$i]);
			$k = $upsertDb->qn($k);
			$v = $upsert->upsert_value[$i];
			$v = $w->parseMessageForPlaceholder($v, $this->data);

			if ($upsert->upsert_eval_value[$i] === '1')
			{
				$res = FabrikHelperHTML::isDebug() ? eval($v) : @eval($v);
				FabrikWorker::logEval($res, 'Eval exception : upsert : ' . $v . ' : %s');

				// if the eval'ed code returned false, skip this
				if ($res === false)
				{
					continue;
				}

				$v = $res;
			}

			if ($v == '')
			{
				$v = $w->parseMessageForPlaceholder($upsert->upsert_default[$i], $this->data);
			}

			/*
			 * $$$ hugh - permit the use of expressions, by putting the value in parens, with option use
			 * of double :: to provide a default for new row (rowid is empty).  This default is seperate from
			 * the simple default used above, which is predicated on value being empty.  So simple usage
			 * might be ..
			 *
			 * (counter+1::0)
			 *
			 * ... if you want to increment a 'counter' field.  Or you might use a subquery, like ...
			 *
			 * ((SELECT foo FROM other_table WHERE fk_id = {rowid})::'foo default')
			 */

			if (!preg_match('#^\((.*)\)$#', $v))
			{
				$v = $upsertDb->q($v);
			}
			else
			{
				$matches = array();
				preg_match('#^\((.*)\)$#', $v, $matches);
				$v = $matches[1];
				$v = explode('::', $v);
				if (count($v) == 1)
				{
					$v = $v[0];
				}
				else
				{
					if ($formModel->isNewRecord())
					{
						$v = $v[1];
					}
					else
					{
						$v = $v[0];
					}
				}
			}

			$fields[] = $k . ' = ' . $v;
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
		$listId = $params->get('table');
		$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$listModel->setId($listId);

		return $listModel->getTable()->db_table_name;
	}

	/**
	 * See if row exists on upsert table
	 * @param   string  $table  Table name
	 * @param   string  $field
	 * @param   string  $value
	 *
	 * @return bool
	 */
	protected function upsertRowExists($table, $field, $value)
	{
		$params = $this->getParams();
		$cid = $params->get('connection_id');
		$connectionModel = JModelLegacy::getInstance('connection', 'FabrikFEModel');
		$connectionModel->setId($cid);
		$db = $connectionModel->getDb();
		$query = $db->getQuery(true);
		$query->select('COUNT(*) AS total')->from($table)->where($field . ' = ' . $db->q($value));
		$db->setQuery($query);
		return (int) $db->loadResult() > 0;
	}
}
