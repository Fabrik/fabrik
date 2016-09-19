<?php
/**
 * Fabrik Plugin From Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

/**
 * Fabrik Plugin From Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class PlgFabrik_List extends FabrikPlugin
{
	/**
	 * Button prefix
	 *
	 * @var string
	 */
	protected $buttonPrefix = '';

	/**
	 * JavaScript code to ini js object
	 *
	 * @var string
	 */
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
	 * @param   string $location Location to trigger plugin on
	 * @param   string $event    Event to trigger plugin on
	 *
	 * @return  bool  true if we should run the plugin otherwise false
	 */
	public function canUse($location = null, $event = null)
	{
		$aclParam = $this->getAclParam();

		if ($aclParam == '')
		{
			return true;
		}

		$params = $this->getParams();
		$groups = $this->user->getAuthorisedViewLevels();

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
	 * Can the plug-in use AJAX
	 *
	 * @return  bool
	 */
	public function canAJAX()
	{
		return true;
	}

	/**
	 * Get the button label
	 *
	 * @return  string
	 */
	protected function buttonLabel()
	{
		$s = JString::strtoupper($this->buttonPrefix);

		return FText::_('PLG_LIST_' . $s . '_' . $s);
	}

	/**
	 * Prep the button if needed
	 *
	 * @param   array &$args Arguments
	 *
	 * @since  3.0.6.2
	 *
	 * @return  bool;
	 */
	public function button(&$args)
	{
		$model              = $this->getModel();
		$this->buttonAction = $model->actionMethod();
		$this->context      = $model->getRenderContext();

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
			$p  = $this->onGetFilterKey_result();
			$j3 = FabrikWorker::j3();
			FabrikHelperHTML::addPath('plugins/fabrik_list/' . $p . '/images/', 'image', 'list');
			$name = $this->_getButtonName();
			$label = $this->buttonLabel();
			$imageName = $this->getImageName();
			$tmpl = $this->getModel()->getTmpl();
			$properties = array();
			$opts       = array(
				'forceImage' => false
			);

			if (FabrikWorker::isImageExtension($imageName))
			{
				$opts['forceImage'] = true;
			}


			$img = FabrikHelperHTML::image($imageName, 'list', $tmpl, $properties, false, $opts);
			$text = $this->buttonAction == 'dropdown' ? $label : '<span class="hidden">' . $label . '</span>';

			if ($j3 && $this->buttonAction != 'dropdown')
			{
				$layout = FabrikHelperHTML::getLayout('fabrik-button');
				$layoutData = (object) array(
					'tag' => 'a',
					'attributes' => 'data-list="' . $this->context . '" title="' . $label . '"',
					'class' => $name . ' listplugin btn-default',
					'label' => $img . ' ' . $text
				);

				return $layout->render($layoutData);
			}
			else
			{
				$a = '<a href="#" data-list="' . $this->context . '" class="' . $name . ' listplugin" title="' . $label . '">';

				return $a . $img . ' ' . $text . '</a>';
			}
		}

		return '';
	}

	/**
	 * Get button image
	 *
	 * @since   3.1b
	 *
	 * @return   string  image
	 */
	protected function getImageName()
	{
		return $this->getParams()->get('list_' . $this->buttonPrefix . '_image_name', $this->buttonPrefix . '.png');
	}

	/**
	 * Build an array of properties to ini the plugins JS objects
	 *
	 * @return  stdClass
	 */
	public function getElementJSOptions()
	{
		$opts          = new stdClass;
		$model         = $this->getModel();
		$opts->ref     = $model->getRenderContext();
		$opts->name    = $this->_getButtonName();
		$opts->listid  = $model->getId();
		$opts->canAJAX = $this->canAJAX();

		return $opts;
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   array $args [0] => string table's form id to contain plugin
	 *
	 * @return    bool
	 */
	public function onLoadJavascriptInstance($args)
	{
		JText::script('COM_FABRIK_PLEASE_SELECT_A_ROW');

		return true;
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
		return true;
	}

	/**
	 * onFiltersGot method - run after the list has created filters
	 *
	 * @return bool currently ignored
	 */
	public function onFiltersGot()
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
	 * Preflight check to ensure that the list plugin should process
	 *
	 * @return    string|boolean
	 */
	public function process_preflightCheck()
	{
		if ($this->buttonPrefix == '')
		{
			return false;
		}

		$input             = $this->app->input;
		$postedRenderOrder = $input->getInt('fabrik_listplugin_renderOrder', -1);

		return $input->get('fabrik_listplugin_name') == $this->buttonPrefix && $this->renderOrder == $postedRenderOrder;
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
		$this->filterKey = JString::strtolower(str_ireplace('PlgFabrik_List', '', get_class($this)));

		return $this->filterKey;
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
	 * Plugins should use their own name space for storing their session data
	 * e.g radius search plugin stores its search values here
	 *
	 * @return  string
	 */
	protected function getSessionContext()
	{
		return 'com_' . $this->package . '.list' . $this->model->getRenderContext() . '.plugins.' . $this->onGetFilterKey() . '.';
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
	 * @param   array &$args Arguments - first value is an object with a JQuery object
	 *                       contains the current query:
	 *                       $args[0]->query
	 *
	 * @return  void;
	 */
	public function onQueryBuilt(&$args)
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
	 * @return  mixed   array or null. If array then key is class name and value
	 * is relative path to either compressed or uncompress js file.
	 */
	public function loadJavascriptClass_result()
	{
		$this->onGetFilterKey();
		$p = $this->onGetFilterKey_result();
		$ext = FabrikHelperHTML::isDebug() ? '.js' : '-min.js';
		$file = 'plugins/fabrik_list/' . $p . '/' . $p . $ext;

		if (JFile::exists(JPATH_SITE . '/' . $file))
		{
			return array('FbList' . ucfirst(($p)) => $file);
		}
		else
		{
			return null;
		}
	}

	public function loadJavascriptClassName()
	{
		return true;
	}

	public function loadJavascriptClassName_result()
	{
		return '';
	}

	/**
	 * Shouldn't do anything here - but needed for the result return
	 *
	 * @since   3.1b
	 *
	 * @return  void
	 */
	public function requireJSShim()
	{
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
		$deps                                                      = new stdClass;
		$deps->deps                                                = array('fab/list-plugin');
		$shim['list/' . $this->filterKey . '/' . $this->filterKey] = $deps;

		return $shim;
	}

	/**
	 * Overridden by plugins if necessary.
	 * If the plugin is a filter plugin, return true if it needs the 'form submit'
	 * method, i.e. the Go button.  Implemented specifically for radius search plugin.
	 *
	 * @return  null
	 */
	public function requireFilterSubmit()
	{
	}

	/**
	 * Overridden by plugins if necessary.
	 * If the plugin is a filter plugin, return true if it needs the 'form submit'
	 * method, i.e. the Go button.  Implemented specifically for radius search plugin.
	 *
	 * @return  bool
	 */
	public function requireFilterSubmit_result()
	{
		return false;
	}

	/**
	 * Get the element's JLayout file
	 * Its actually an instance of FabrikLayoutFile which inverses the ordering added include paths.
	 * In FabrikLayoutFile the addedPath takes precedence over the default paths, which makes more sense!
	 *
	 * @param   string $type form/details/list
	 *
	 * @return FabrikLayoutFile
	 */
	public function getLayout($type)
	{
		$name     = get_class($this);
		$name     = strtolower(JString::str_ireplace('PlgFabrik_List', '', $name));
		$basePath = COM_FABRIK_BASE . '/plugins/fabrik_list/' . $name . '/layouts';
		$layout   = new FabrikLayoutFile('fabrik-list-' . $name . '-' . $type, $basePath, array('debug' => false, 'component' => 'com_fabrik', 'client' => 'site'));
		$layout->addIncludePaths(JPATH_THEMES . '/' . $this->app->getTemplate() . '/html/layouts');

		return $layout;
	}
}
