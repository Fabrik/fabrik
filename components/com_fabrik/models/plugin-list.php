<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

/**
 * Fabrik Plugin From Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class plgFabrik_List extends FabrikPlugin
{
	/** @var string button prefix*/
	protected $buttonPrefix = '';

	/** @var string js code to ini js object */
	protected $jsInstance = null;

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 */

	protected function getAclParam()
	{
		return '';
	}

	/**
	 * Determine if we use the plugin or not
	 * both location and event criteria have to be match when form plug-in
	 *
	 * @param   object  &$model    calling the plugin table/form
	 * @param   string  $location  location to trigger plugin on
	 * @param   string  $event     event to trigger plugin on
	 *
	 * @return  bool  true if we should run the plugin otherwise false
	 */

	public function canUse(&$model = null, $location = null, $event = null)
	{
		$aclParam = $this->getAclParam();
		if ($aclParam == '')
		{
			return true;
		}
		$params = $this->getParams();
		$groups = JFactory::getUser()->authorisedLevels();
		return in_array($params->get($aclParam), $groups);
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
	 * Get the button label
	 *
	 * @return  string
	 */

	protected function buttonLabel()
	{
		$s = JString::strtoupper($this->buttonPrefix);
		return JText::_('PLG_LIST_' . $s . '_' . $s);
	}

	/**
	 * Prep the button if needed
	 *
	 * @param   object  $params  plugin params
	 * @param   object  &$model  list model
	 * @param   array   &$args   arguements
	 *
	 * @since  3.0.6.2
	 *
	 * @return  bool;
	 */

	public function button($params, &$model, &$args)
	{
		$this->context = $model->getRenderContext();
		return false;
	}

	/**
	 * Build the HTML for the plug-in button
	 *
	 * @return  string
	 */

	public function button_result()
	{
		if ($this->canUse())
		{
			$p = $this->onGetFilterKey_result();
			FabrikHelperHTML::addPath('plugins/fabrik_list/' . $p . '/images/', 'image', 'list');
			$name = $this->_getButtonName();
			$label = $this->buttonLabel();
			$imageName = $this->getParams()->get('list_' . $this->buttonPrefix . '_image_name', $this->buttonPrefix . '.png');
			$img = FabrikHelperHTML::image($imageName, 'list', '', $label);
			return '<a href="#" data-list="' . $this->context . '"class="' . $name . ' listplugin" title="' . $label . '">' . $img . '<span>' . $label . '</span></a>';
		}
		return '';
	}

	/**
	 * Build an array of properties to ini the plugins JS objects
	 *
	 * @param   object  $model  list model
	 *
	 * @return  array
	 */

	public function getElementJSOptions($model)
	{
		$opts = new stdClass;
		$opts->ref = $model->getRenderContext();
		$opts->name = $this->_getButtonName();
		$opts->listid = $model->getId();
		return $opts;
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   object  $params  parameters
	 * @param   object  $model   list model
	 * @param   array   $args    [0] => string table's form id to contain plugin
	 *
	 * @return	bool
	 */

	public function onLoadJavascriptInstance($params, $model, $args)
	{
		JText::script('COM_FABRIK_PLEASE_SELECT_A_ROW');
		return true;
	}

	/**
	 * onGetData method
	 *
	 * @param   object  $params  list params
	 * @param   object  &$model  list model
	 *
	 * @return bol currently ignored
	 */

	public function onLoadData($params, &$model)
	{
		return true;
	}

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
		return true;
	}

	/**
	 * Provide some default text that most table plugins will need
	 * (this object will then be json encoded by the plugin and passed
	 * to it's js class
	 *
	 * @depreciated since 3.0
	 *
	 * @return  object  language
	 */

	protected function _getLang()
	{
		$lang = new stdClass;
		return $lang;
	}

	/**
	 * Get the html name for the button
	 *
	 * @return  string
	 */

	protected function _getButtonName()
	{
		return $this->buttonPrefix . '-' . $this->renderOrder;
	}

	/**
	 * Prefilght check to ensure that the list plugin should process
	 *
	 * @param   object  $params  params
	 * @param   object  &$model  list model
	 *
	 * @return	string|boolean
	 */

	public function process_preflightCheck($params, &$model)
	{
		if ($this->buttonPrefix == '')
		{
			return false;
		}
		$postedRenderOrder = JRequest::getInt('fabrik_listplugin_renderOrder', -1);
		return JRequest::getVar('fabrik_listplugin_name') == $this->buttonPrefix && $this->renderOrder == $postedRenderOrder;
	}

	/**
	 * Get a key name specific to the plugin class to use as the reference
	 * for the plugins filter data
	 * (Normal filter data is filtered on the element id, but here we use the plugin name)
	 *
	 * @return  string  key
	 */

	public function onGetFilterKey()
	{
		$this->filterKey = JString::strtolower(str_replace('plgFabrik_List', '', get_class($this)));
		return true;
	}

	/**
	 * Call onGetFilterKey() from plugin manager
	 *
	 * @return  string
	 */

	public function onGetFilterKey_result()
	{
		if (!isset($this->filterKey))
		{
			$this->onGetFilterKey();
		}
		return $this->filterKey;
	}

	/**
	 * Plugins should use their own name space for storing their sesssion data
	 * e.g radius search plugin stores its search values here
	 *
	 * @return  string
	 */

	protected function getSessionContext()
	{
		return 'com_fabrik.list' . $this->model->getRenderContext() . '.plugins.' . $this->onGetFilterKey() . '.';
	}

	/**
	 * Used to assign the js code created in onLoadJavascriptInstance()
	 * to the table view.
	 *
	 * @return  string  javascript to create instance. Instance name must be 'el'
	 */

	public function onLoadJavascriptInstance_result()
	{
		return $this->jsInstance;
	}

	/**
	 * Allows to to alter the table's select query
	 *
	 * @param   object  $params  plugin params
	 * @param   object  &$model  list model
	 * @param   array   &$args   arguements - first value is an object with a query
	 * property which contains the current query:
	 * $args[0]->query
	 *
	 * @return  void;
	 */

	public function onQueryBuilt($params, &$model, &$args)
	{

	}

	/**
	 * Load the javascript class that manages plugin interaction
	 * should only be called once
	 *
	 * @return  string  javascript class file
	 */

	public function loadJavascriptClass()
	{
		return true;
	}

	/**
	 * Get the src(s) for the list plugin js class
	 *
	 * @return  mixed  string or array
	 */

	public function loadJavascriptClass_result()
	{
		$this->onGetFilterKey();
		$p = $this->onGetFilterKey_result();
		return 'plugins/fabrik_list/' . $p . '/' . $p . '.js';
	}

}
