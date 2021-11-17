<?php
/**
 * Determines if a row is deletable
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.candeleterow
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 *  Determines if a row is deletable
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.candeleterow
 * @since       3.0
 */
class PlgFabrik_ListCandeleterow extends PlgFabrik_List
{
	protected $acl = array();

	protected $result = null;

	/**
	 * Can the plug-in select list rows
	 *
	 * @return  bool
	 */
	public function canSelectRows()
	{
		return false;
	}

	/**
	 * Can the row be deleted
	 *
	 * @param   object  $row  Current row to test
	 *
	 * @return boolean
	 */
	public function onCanDelete($row)
	{
		$params = $this->getParams();

		// If $row is null, we were called from the table's canEdit() in a per-table rather than per-row context,
		// and we don't have an opinion on per-table delete permissions, so just return true.
		if (is_null($row) || is_null($row[0]))
		{
			$this->result = true;
			return true;
		}

		if (is_array($row[0]))
		{
			$data = ArrayHelper::toObject($row[0]);
		}
		else
		{
			$data = $row[0];
		}

		if (isset($data->__pk_val))
		{
			$pkVal = $data->__pk_val;
		}
		else
		{
			$dbPrimaryKey = $this->getModel()->getPrimaryKey(true);
			if (isset($data->$dbPrimaryKey))
			{
				$pkVal = $data->$dbPrimaryKey;
			}
			else
			{
				// probably a new form, so nope, no rowid, can't delete
				$this->result = false;
				return false;
			}
		}

		/**
		 * If we've got the results for this PK, return them.  Set result, so customProcessResult() gets it right
		 */
		if (array_key_exists($pkVal, $this->acl))
		{
			$this->result = $this->acl[$pkVal];
			return $this->acl[$pkVal];
		}

		$field = str_replace('.', '___', $params->get('candeleterow_field'));

		// If they provided some PHP to eval, we ignore the other settings and just run their code
		$canDeleteRowEval = $params->get('candeleterow_eval', '');

		// $$$ rob if no can delete field selected in admin return true
		if (trim($field) == '' && trim($canDeleteRowEval) == '')
		{
			$this->acl[$pkVal] = true;
			$this->result = true;

			return true;
		}

		if (!empty($canDeleteRowEval))
		{
			$w = new FabrikWorker;
			$data = ArrayHelper::fromObject($data);
			$canDeleteRowEval = $w->parseMessageForPlaceHolder($canDeleteRowEval, $data);
			FabrikWorker::clearEval();
			$canDeleteRowEval = @eval($canDeleteRowEval);
			FabrikWorker::logEval($canDeleteRowEval, 'Caught exception on eval in can delete row : %s');
			$this->acl[$pkVal] = $canDeleteRowEval;
		}
		else
		{
			// No PHP given, so just do a simple match on the specified element and value settings.
			if ($params->get('candeleterow_useraw', '0') == '1')
			{
				$field .= '_raw';
			}

			$value = $params->get('candeleterow_value');
			$operator = $params->get('operator', '=');

			if (!isset($data->$field))
			{
				$this->acl[$pkVal] = false;
			}
			else
			{
				switch ($operator)
				{
					case '=':
					default:
						$this->acl[$pkVal] = $data->$field == $value;
						break;
					case "!=":
						$this->acl[$pkVal] = $data->$field != $value;
						break;
				}
			}
		}

		$this->result = $this->acl[$pkVal];

		return $this->acl[$pkVal];
	}

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 */
	protected function getAclParam()
	{
		return 'candeleterow_access';
	}

	/**
	 * Custom process plugin result
	 *
	 * @param   string $method Method
	 *
	 * @return boolean
	 */
	public function customProcessResult($method)
	{
		/*
		 * If we didn't return false from onCanDelete(), the plugin manager will get the final result from this method,
		 * so we need to return whatever onCanDelete() set the result to.
		 */
		if ($method === 'onCanDelete')
		{
			return $this->result;
		}

		return true;
	}
}
