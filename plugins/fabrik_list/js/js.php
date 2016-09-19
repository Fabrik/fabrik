<?php
/**
 * Fabrik List Javascript
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.js
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 *  Add an action button to run PHP
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.js
 * @since       3.0
 */

class PlgFabrik_ListJs extends PlgFabrik_List
{
	/**
	 * Button prefix
	 * @var  string
	 */
	protected $buttonPrefix = 'js';

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
	 * Get button image
	 *
	 * @since   3.1b
	 *
	 * @return   string  image
	 */

	protected function getImageName()
	{
		$img = parent::getImageName();

		if (FabrikWorker::j3() && $img === 'php.png')
		{
			$img = 'lightning';
		}

		return $img;
	}

	/**
	 * Get the button label
	 *
	 * @return  string
	 */

	protected function buttonLabel()
	{
		return $this->getParams()->get('button_label', parent::buttonLabel());
	}

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 */

	protected function getAclParam()
	{
		return 'access';
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
	 * @param   array  $args  Array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 */

	public function onLoadJavascriptInstance($args)
	{
		parent::onLoadJavascriptInstance($args);
		$params = $this->getParams();
		$opts = $this->getElementJSOptions();
		$file = $params->get('js_file', '');

		if ($file !== '' && $file !== '-1')
		{
			$opts->js_code = file_get_contents(JPATH_ROOT . '/plugins/fabrik_list/js/scripts/' . $file);
		}
		else
		{
			$opts->js_code = $params->get('js_code', '');
		}

		$opts->statusMsg = $params->get('msg', '');
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListJs($opts)";

		return true;
	}

	/**
	 * Load the AMD module class name
	 *
	 * @return string
	 */
	public function loadJavascriptClassName_result()
	{
		return 'FbListJs';
	}
}
