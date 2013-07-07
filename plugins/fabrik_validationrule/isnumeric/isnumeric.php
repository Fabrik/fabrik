<?php
/**
 * Is Numeric Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isnumeric
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin classes
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

/**
 * Is Numeric Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isnumeric
 * @since       3.0
 */

class PlgFabrik_ValidationruleIsNumeric extends PlgFabrik_Validationrule
{

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'isnumeric';

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
		// Could be a dropdown with multivalues
		if (is_array($data))
		{
			$data = implode('', $data);
		}
		$params = $this->getParams();
		$allow_empty = $params->get('isnumeric-allow_empty');
		$allow_empty = $allow_empty[$pluginc];
		if ($allow_empty == '1' and empty($data))
		{
			return true;
		}
		return is_numeric($elementModel->unNumberFormat($data));
	}

	/**
	 * Does the validation allow empty value?
	 * Default is false, can be overrideen on per-validation basis (such as isnumeric)
	 *
	 * @param   object  $elementModel  Element model
	 * @param   int     $pluginc       Validation render order
	 *
	 * @return bool
	 */

	protected function allowEmpty($elementModel, $pluginc)
	{
		$params = $this->getParams();
		$allow_empty = $params->get('isnumeric-allow_empty');
		$allow_empty = $allow_empty[$pluginc];
		return $allow_empty == '1';
	}
}
