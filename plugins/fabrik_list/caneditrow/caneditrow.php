<?php
/**
 * Determines if a row is editable
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.caneditrow
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';
require_once COM_FABRIK_FRONTEND . '/helpers/html.php';

/**
 *  Determines if a row is editable
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.caneditrow
 * @since       3.0
 */

class PlgFabrik_ListCaneditrow extends PlgFabrik_List
{

	protected $acl = array();
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
	 * Can the row be edited
	 *
	 * @param   object  $params     plugin params
	 * @param   object  $listModel  list model
	 * @param   object  $row        current row to test
	 *
	 * @return boolean
	 */

	public function onCanEdit($params, $listModel, $row)
	{

		// If $row is null, we were called from the list's canEdit() in a per-table rather than per-row context,
		// and we don't have an opinion on per-table edit permissions, so just return true.
		if (is_null($row) || is_null($row[0]))
		{
			return true;
		}
		if (is_array($row[0]))
		{
			$data = JArrayHelper::toObject($row[0]);
		}
		else
		{
			$data = $row[0];
		}
		$field = str_replace('.', '___', $params->get('caneditrow_field'));

		// If they provided some PHP to eval, we ignore the other settings and just run their code
		$caneditrow_eval = $params->get('caneditrow_eval', '');

		// $$$ rob if no can edit field selected in admin return true
		if (trim($field) == '' && trim($caneditrow_eval) == '')
		{
			$this->acl[$data->__pk_val] = true;
			return true;
		}

		if (!empty($caneditrow_eval))
		{
			$w = new FabrikWorker;
			$data = JArrayHelper::fromObject($data);
			$caneditrow_eval = $w->parseMessageForPlaceHolder($caneditrow_eval, $data);
			$caneditrow_eval = @eval($caneditrow_eval);
			FabrikWorker::logEval($caneditrow_eval, 'Caught exception on eval in can edit row : %s');
			$this->acl[$data['__pk_val']] = $caneditrow_eval;
			return $caneditrow_eval;
		}
		else
		{
			// No PHP given, so just do a simple match on the specified element and value settings.
			if ($params->get('caneditrow_useraw', '0') == '1')
			{
				$field .= '_raw';
			}
			$value = $params->get('caneditrow_value');
			$operator = $params->get('operator', '=');
			switch ($operator)
			{
				case '=':
				default:
					$return = $data->$field == $value;
					$this->acl[$data->__pk_val] = $return;
					return $return;
					break;
				case "!=":
					$return = $data->$field != $value;
					$this->acl[$data->__pk_val] = $return;
					return $return;
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
		return 'caneditrow_access';
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   object  $params  plugin parameters
	 * @param   object  $model   list model
	 * @param   array   $args    array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 */

	public function onLoadJavascriptInstance($params, $model, $args)
	{
		parent::onLoadJavascriptInstance($params, $model, $args);
		$opts = $this->getElementJSOptions($model);
		$opts->acl = $this->acl;
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListCanEditRow($opts)";
		return true;
	}
}
