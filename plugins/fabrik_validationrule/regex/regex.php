<?php
/**
 * Regular Expression Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.regex
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use \Joomla\Registry\Registry;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

/**
 * Regular Expression Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.regex
 * @since       3.0
 */
class PlgFabrik_ValidationruleRegex extends PlgFabrik_Validationrule
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'regex';

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string  $data           To check
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */
	public function validate($data, $repeatCounter)
	{
		// For multi-select elements
		if (is_array($data))
		{
			$data = implode('', $data);
		}

		$params = $this->getParams();
		$doMatch = $params->get('regex-match');

		if ($doMatch)
		{
			$matches = array();
			$v = $params->get('regex-expression');
			$v = trim($v);
			$found = empty($v) ? true : preg_match($v, $data, $matches);

			return $found;
		}

		return true;
	}

	/**
	 * Checks if the validation should replace the submitted element data
	 * if so then the replaced data is returned otherwise original data returned
	 *
	 * @param   string  $data           Original data
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  string	original or replaced data
	 */
	public function replace($data, $repeatCounter)
	{
		$params = $this->getParams();
		$doMatch = $params->get('regex-match');

		if (!$doMatch)
		{
			$v = $params->get($this->pluginName . '-expression');
			$v = trim($v);
			$replace = $params->get('regex-replacestring');
			$return = empty($v) ? $data : preg_replace($v, $replace, $data);

			return $return;
		}

		return $data;
	}

	/**
	 * Get the base icon image as defined by the J Plugin options
	 *
	 * @since   3.1b2
	 *
	 * @return  string
	 */
	public function iconImage()
	{
		$plugin = JPluginHelper::getPlugin('fabrik_validationrule', $this->pluginName);
		$globalParams = new Registry($plugin->params);
		$default = $globalParams->get('icon', 'star');
		$params = $this->getParams();

		return $params->get('icon', $default);
	}
}
