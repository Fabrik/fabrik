<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.php
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 *  Add an action button to run PHP
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.copy
 * @since       3.0
 */

class plgFabrik_ListPhp extends plgFabrik_List
{

	protected $buttonPrefix = 'php';

	protected $msg = null;

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
	 * Get the button label
	 *
	 * @return  string
	 */

	protected function buttonLabel()
	{
		return $this->getParams()->get('table_php_button_label', parent::buttonLabel());
	}

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 */

	protected function getAclParam()
	{
		return 'table_php_access';
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
		$file = JFilterInput::clean($params->get('table_php_file'), 'CMD');
		if ($file == -1 || $file == '')
		{
			$code = $params->get('table_php_code');
			@eval($code);
		}
		else
		{
			require_once JPATH_ROOT . '/plugins/fabrik_list/php/scripts/' . $file;
		}
		if (isset($statusMsg) && !empty($statusMsg))
		{
			$this->msg = $statusMsg;
		}
		return true;
	}

	/**
	 * Get the message generated in process()
	 *
	 * @param   int  $c  plugin render order
	 *
	 * @return  string
	 */

	public function process_result($c)
	{
		if (isset($this->msg))
		{
			return $this->msg;
		}
		else
		{
			$params = $this->getParams();
			$msg = $params->get('table_php_msg', JText::_('PLG_LIST_PHP_CODE_RUN'));
			return $msg;
		}
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
		$opts->js_code = $params->get('table_php_js_code', '');
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListPHP($opts)";
		return true;
	}

}
