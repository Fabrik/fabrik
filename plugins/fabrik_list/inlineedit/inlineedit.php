<?php
/**
 * Fabrik InlineEdit
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.inlineedit
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
* Allows double-clicking in a cell to enable in-line editing
*
* @package     Joomla.Plugin
* @subpackage  Fabrik.list.inlineedit
* @since       3.0
*/

class PlgFabrik_ListInlineedit extends PlgFabrik_List
{

	/**
	 * Contains js file, elements to load and require js shim info
	 * @var array
	 */
	var $elementJs = array();

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 */

	protected function getAclParam()
	{
		return 'inline_access';
	}

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
	 * Get the shim require.js logic for loading the list class.
	 * -min suffix added elsewhere.
	 *
	 * @since   3.1b
	 *
	 * @return  object  shim
	 */

	public function requireJSShim_result()
	{
		$deps = new stdClass;
		$deps->deps = array('fab/list-plugin');
		$shim['list/' . $this->filterKey . '/' . $this->filterKey] = $deps;

		$params = $this->getParams();
		$shim = parent::requireJSShim_result();

		list($srcs, $els, $addShim) = $this->loadElementJS($params);

		foreach ($addShim as $key => $deps)
		{
			if (!array_key_exists($key, $shim))
			{
				$shim[$key] = $deps;
			}
			else
			{
				$shim[$key]['deps'] = array_merge($shim[$key]->deps, $shim->deps);
			}
		}
		return $shim;
	}

	/**
	 * Get the src(s) for the list plugin js class
	 *
	 * @return  mixed  string or array
	 */

	public function loadJavascriptClass_result()
	{
		$ext = FabrikHelperHTML::isDebug() ? '.js' : '-min.js';
		$src = parent::loadJavascriptClass_result();
		return array($src, 'media/com_fabrik/js/element' . $ext);
	}

	/**
	 * Helper function to decide which js files and shim files should be used
	 *
	 * @param   object  $params  Params
	 *
	 * @since   3.1b
	 *
	 * @return  array (element js files (not used), array of element names, require js shim setup files)
	 */

	protected function loadElementJS($params)
	{
		if (!empty($this->elementJs))
		{
			return $this->elementJs;
		}
		$app = JFactory::getApplication();
		$input = $app->input;
		$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$listModel->setId($input->getInt('listid'));
		$elements = $listModel->getElements('safecolname');

		$pels = $params->get('inline_editable_elements');
		$use = json_decode($pels);
		if (!is_object($use))
		{
			$aEls = trim($pels) == '' ? array() : explode(",", $pels);
			$use = new stdClass;
			foreach ($aEls as $e)
			{
				$use->$e = array($e);
			}
		}
		$els = array();
		$srcs = array();
		$test = (array) $use;
		$shim = array();
		if (!empty($test))
		{
			foreach ($use as $key => $fields)
			{
				if (array_key_exists($key, $elements))
				{
					$trigger = $elements[$key];
					$els[$key] = new stdClass;
					$els[$key]->elid = $trigger->getId();
					$els[$key]->plugins = array();
					foreach ($fields as $field)
					{
						$val = $elements[$field];

						// Load in all element js classes
						if (is_object($val))
						{
							$val->formJavascriptClass($srcs, '', $shim);
							$els[$key]->plugins[$field] = $val->getElement()->id;

						}
					}
				}
			}
		}
		else
		{
			foreach ($elements as $key => $val)
			{
				// Stop elements such as the password element from incorrectly updating themselves
				if ($val->recordInDatabase(array()))
				{
					$key = FabrikString::safeColNameToArrayKey($key);
					$els[$key] = new stdClass;
					$els[$key]->elid = $val->getId();
					$els[$key]->plugins = array();
					$els[$key]->plugins[$key] = $val->getElement()->id;

					// Load in all element js classes
				$val->formJavascriptClass($srcs, '', $shim);
				}
			}
		}
		$this->elementJs = array($srcs, $els, $shim);
		return $this->elementJs;
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
		$j3 = FabrikWorker::j3();
		list($srcs, $els, $shim) = $this->loadElementJS($params);
		$opts = $this->getElementJSOptions($model);
		$opts->elements = $els;
		$opts->formid = $model->getFormModel()->getId();
		$opts->focusClass = 'focusClass';
		$opts->editEvent = $params->get('inline_edit_event', 'dblclick');
		$opts->tabSave = $params->get('inline_tab_save', false);
		$opts->showCancel = $params->get('inline_show_cancel', true);
		$opts->showSave = (bool) $params->get('inline_show_save', true);
		$opts->loadFirst = (bool) $params->get('inline_load_first', false);
		$opts = json_encode($opts);
		$formid = 'list_' + $model->getFormModel()->getForm()->id;
		$this->jsInstance = "new FbListInlineEdit($opts)";
		return true;
	}

}
