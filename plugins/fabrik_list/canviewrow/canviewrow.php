<?php
/**
 * Determines if a row is viewable
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.canviewrow
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 *  Determines if a row is viewable
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.canviewrow
 * @since       3.0
 */

class PlgFabrik_ListCanviewrow extends PlgFabrik_List
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
	 * Can the row be viewed
	 *
	 * @param   object  $row  Current row to test
	 *
	 * @return boolean
	 */

	public function onCanView($row)
	{
		$params = $this->getParams();
        $model = $this->getModel();
        $formModel = $model->getFormModel();

		// If $row is null, we were called from the list's canEdit() in a per-table rather than per-row context,
		// and we don't have an opinion on per-table view permissions, so just return true.
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

		/**
		 * If __pk_val is not set or empty, then we've probably been called from somewhere in form processing,
		 * and this is a new row.  In which case this plugin cannot offer any opinion!
		 */
		if (!isset($data->__pk_val) || empty($data->__pk_val))
		{
			$this->result = true;
			return true;
		}

		/**
		 * If we've got the results for this PK, return them.  Set result, so customProcessResult() gets it right
		 */
		if (array_key_exists($data->__pk_val, $this->acl))
		{
			$this->result = $this->acl[$data->__pk_val];
			return $this->acl[$data->__pk_val];
		}

		$field = str_replace('.', '___', $params->get('canviewrow_field'));

		// If they provided some PHP to eval, we ignore the other settings and just run their code
		$canviewrow_eval = $params->get('canviewrow_eval', '');

		// $$$ rob if no can view field selected in admin return true
		if (trim($field) == '' && trim($canviewrow_eval) == '')
		{
			$this->acl[$data->__pk_val] = true;
			$this->result = true;

			return true;
		}

		if (!empty($canviewrow_eval))
		{
			$w = new FabrikWorker;
			$data = ArrayHelper::fromObject($data);
			$canviewrow_eval = $w->parseMessageForPlaceHolder($canviewrow_eval, $data);
			FabrikWorker::clearEval();
			$canviewrow_eval = @eval($canviewrow_eval);
			FabrikWorker::logEval($canviewrow_eval, 'Caught exception on eval in can view row : %s');
			$this->acl[$data['__pk_val']] = $canviewrow_eval;
			$this->result = $canviewrow_eval;

			return $canviewrow_eval;
		}
		else
		{
			// No PHP given, so just do a simple match on the specified element and value settings.
			if ($params->get('canviewrow_useraw', '0') == '1')
			{
				$field .= '_raw';
			}

			$value = $params->get('canviewrow_value');
			$operator = $params->get('canviewrow_operator', '=');

			if (is_object($data->$field))
			{
				$data->$field = ArrayHelper::fromObject($data->$field);
			}

			switch ($operator)
			{
				case '=':
				default:
					$return = is_array($data->$field) ? in_array($value, $data->$field) : $data->$field == $value;
					break;
				case "!=":
					$return = is_array($data->$field) ? !in_array($value, $data->$field) : $data->$field != $value;
					break;
			}

			$this->acl[$data->__pk_val] = $return;
			$this->result = $return;

			return $return;
		}
	}

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 */

	protected function getAclParam()
	{
		return 'canviewrow_access';
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   array  $args  Array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 */

	public function onLoadJavascriptInstance($args)
	{
		parent::onLoadJavascriptInstance($args);
		$opts = $this->getElementJSOptions();
		$opts->acl = $this->acl;
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListCanviewrow($opts)";

		return true;
	}

	/**
	 * Load the AMD module class name
	 *
	 * @return string
	 */
	public function loadJavascriptClassName_result()
	{
		return 'FbListCanviewrow';
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
		 * If we didn't return false from onCanEdit(), the plugin manager will get the final result from this method,
		 * so we need to return whatever onCanEdit() set the result to.
		 */
		if ($method === 'onCanView')
		{
			return $this->result;
		}

		return true;
	}
}
