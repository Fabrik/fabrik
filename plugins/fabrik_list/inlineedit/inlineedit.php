<?php
/**
* @package     Joomla.Plugin
* @subpackage  Fabrik.list.inlineedit
* @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
* @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

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
	 * Get the src(s) for the list plugin js class
	 *
	 * @return  mixed  string or array
	 */

	public function loadJavascriptClass_result()
	{
		$src = parent::loadJavascriptClass_result();
		return array($src, 'media/com_fabrik/js/element.js');
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
		FabrikHelperHTML::script('media/com_fabrik/js/element.js');
		$listModel = JModel::getInstance('list', 'FabrikFEModel');
		$listModel->setId(JRequest::getVar('listid'));

		$elements = $model->getElements('safecolname');

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
		if (!empty($test))
		{
			foreach ($use as $key => $fields)
			{
				if (array_key_exists($key, $elements))
				{
					$trigger = $elements[$key];
					$els[$key] = new stdClass;
					$els[$key]->elid = $trigger->_id;
					$els[$key]->plugins = array();
					foreach ($fields as $field)
					{
						$val = $elements[$field];

						// Load in all element js classes
						if (is_object($val))
						{
							$val->formJavascriptClass($srcs);
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
					$els[$key]->elid = $val->_id;
					$els[$key]->plugins = array();
					$els[$key]->plugins[$key] = $val->getElement()->id;
	
					// Load in all element js classes
					$val->formJavascriptClass($srcs);
				}
			}
		}
		FabrikHelperHTML::script($srcs);
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
