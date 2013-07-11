<?php
/**
 * Regular Expression Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.regex
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

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
	 * @param   object  &$elementModel  Element Model
	 * @param   int     $pluginc        Plugin sequence ref
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */

	public function validate($data, &$elementModel, $pluginc, $repeatCounter, $allData)
	{
		// For multiselect elements
		if (is_array($data))
		{
			$data = implode('', $data);
		}
		$params = $this->getParams();
		$domatch = $params->get('regex-match');
		$domatch = $domatch[$pluginc];
		if ($domatch)
		{
			$matches = array();
			$v = (array) $params->get('regex-expression');
			$v = JArrayHelper::getValue($v, $pluginc);
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
	 * @param   model   &$elementModel  Element model
	 * @param   int     $pluginc        Validation plugin counter
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  string	original or replaced data
	 */

	public function replace($data, &$elementModel, $pluginc, $repeatCounter, $allData)
	{
		$params = $this->getParams();
		$domatch = (array) $params->get('regex-match');
		$domatch = JArrayHelper::getValue($domatch, $pluginc);
		if (!$domatch)
		{
			$v = (array) $params->get($this->pluginName . '-expression');
			$v = JArrayHelper::getValue($v, $pluginc);
			$v = trim($v);
			$replace = (array) $params->get('regex-replacestring');
			$return = empty($v) ? $data : preg_replace($v, JArrayHelper::getValue($replace, $pluginc), $data);
			return $return;
		}
		return $data;
	}
}
