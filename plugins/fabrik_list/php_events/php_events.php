<?php
/**
 * Execute PHP Code on any list event
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.phpevents
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Execute PHP Code on any list event
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.phpevents
 * @since       3.0
 */

class PlgFabrik_ListPhp_Events extends PlgFabrik_List
{
	/**
	 * onFiltersGot method - run after the list has created filters
	 *
	 * @return bool currently ignored
	 */

	public function onFiltersGot()
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_onfiltersgot'));
	}

	/**
	 * Called when the list HTML filters are loaded
	 *
	 * @return  void
	 */

	public function onMakeFilters()
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_onmakefilters'));
	}

	/**
	 * Do the plug-in action
	 *
	 * @param   array  $opts  Custom options
	 *
	 * @return  bool
	 */

	public function process($opts = array())
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_process'));
	}

	/**
	 * Run before the list loads its data
	 *
	 * @return bool currently ignored
	 */

	public function onPreLoadData()
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_onpreloaddata'));
	}

	/**
	 * onGetData method
	 *
	 * @param   &$args  Array  Additional options passed into the method when the plugin is called
	 *
	 * @return bool currently ignored
	 */

	public function onLoadData(&$args)
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_onloaddata'));
	}

	/**
	 * Called when the model deletes rows
	 *
	 * @return  bool  false if fail
	 */

	public function onDeleteRows()
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_ondeleterows'));
	}

	/**
	 * Called after the model has deleted rows
	 *
	 * @return  bool  false if fail
	 */

	public function onAfterDeleteRows()
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_onafterdeleterows'));
	}

	/**
	 * Prep the button if needed
	 *
	 * @param   array  &$args  Arguments
	 *
	 * @return  bool;
	 */

	public function button(&$args)
	{
		parent::button($args);

		return true;
	}

	/**
	 * Build the HTML for the plug-in button
	 *
	 * @return  string
	 */

	public function button_result()
	{
		return '';
	}

	/**
	 * Determine if we use the plugin or not
	 * both location and event criteria have to be match when form plug-in
	 *
	 * @param   string  $location  Location to trigger plugin on
	 * @param   string  $event     Event to trigger plugin on
	 *
	 * @return  bool  true if we should run the plugin otherwise false
	 */

	public function canUse($location = null, $event = null)
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
		return false;
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
		return true;
	}

	/**
	 * On build query where
	 *
	 * @return boolean
	 */
	public function onBuildQueryWhere()
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_onbuildquerywhere'));
	}

	/**
	 * Evaluate supplied PHP
	 *
	 * @param   string  $code  Php code
	 *
	 * @return bool
	 */

	protected function doEvaluate($code)
	{
		$model = $this->getModel();
		$w = new FabrikWorker;
		$code = $w->parseMessageForPlaceHolder($code);

		if ($code != '')
		{
			if (eval($code) === false)
			{
				return false;
			}
		}

		return true;
	}
}
