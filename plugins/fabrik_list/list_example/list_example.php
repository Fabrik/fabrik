<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.example
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Example plugin showing some of the main list plugin methods
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.example
 * @since       3.0
 */

class plgFabrik_ListList_Example extends plgFabrik_List
{

	/**
	 * onFiltersGot method - run after the list has created filters
	 *
	 * @param   object  $params  plugin params
	 * @param   object  &$model  list
	 *
	 * @return bol currently ignored
	 */

	public function onFiltersGot($params, &$model)
	{
	}

	/**
	 * Called when the list HTML filters are loaded
	 *
	 * @param   object  $params  plugin params
	 * @param   object  &$model  list model
	 *
	 * @return  void
	 */

	public function onMakeFilters($params, &$model)
	{
	}

	/**
	 * Do the plug-in action
	 *
	 * @param   object  $params  plugin parameters
	 * @param   object  &$model  list model
	 * @param   array   $opts    custom options
	 *
	 * @return  bool
	 */

	public function process($params, &$model, $opts = array())
	{
	}

	/**
	 * Run before the list loads its data
	 *
	 * @param   object  $params  plugin params
	 * @param   object  &$model  list model
	 *
	 * @return  void
	 */

	public function onPreLoadData($params, &$model)
	{
	}

	/**
	 * onGetData method
	 *
	 * @param   object  $params  calling the plugin table/form
	 * @param   object  &$model  list model
	 *
	 * @return bol currently ignored
	 */

	public function onLoadData($params, &$model)
	{
	}

	/**
	 * Prep the button if needed
	 *
	 * @param   object  $params  plugin params
	 * @param   object  &$model  list model
	 * @param   array   &$args   arguements
	 *
	 * @return  bool;
	 */

	public function button($params, &$model, &$args)
	{
		parent::button($params, $model, $args);
		return true;
	}

	/**
	 * Check if the user can use the plugin
	 *
	 * @param   object  &$model    calling the plugin list/form
	 * @param   string  $location  to trigger plugin on
	 * @param   string  $event     to trigger plugin on
	 *
	 * @return  bool can use or not
	 */

	public function canUse(&$model = null, $location = null, $event = null)
	{
		return true;
	}

	/**
	 * Can the plug-in select list rows
	 *
	 * @return  bool
	 */

	public function canSelectRows()
	{
		return true;
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
		$opts = json_encode($opts);
		return "new FbListExample('$formid', $opts)";
	}

}
