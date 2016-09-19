<?php
/**
 * Determines if a row is deletable
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.candeleterow
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
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

		$field = str_replace('.', '___', $params->get('candeleterow_field'));

		// If they provided some PHP to eval, we ignore the other settings and just run their code
		$canDeleteRowEval = $params->get('candeleterow_eval', '');

		// $$$ rob if no can delete field selected in admin return true
		if (trim($field) == '' && trim($canDeleteRowEval) == '')
		{
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

			return $canDeleteRowEval;
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
				return false;
			}

			switch ($operator)
			{
				case '=':
				default:

					return $data->$field == $value;
					break;
				case "!=":

					return $data->$field != $value;
					break;
			}
		}
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
}
