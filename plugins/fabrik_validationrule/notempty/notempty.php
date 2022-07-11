<?php
/**
 * Not Empty Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.notempty
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

/**
 * Not Empty Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.notempty
 * @since       3.0
 */
class PlgFabrik_ValidationruleNotempty extends PlgFabrik_Validationrule
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'notempty';

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
		if (method_exists($this->elementModel, 'dataConsideredEmptyForValidation'))
		{
			$ok = $this->elementModel->dataConsideredEmptyForValidation($data, $repeatCounter);
		}
		else
		{
			$ok = $this->elementModel->dataConsideredEmpty($data, $repeatCounter);
		}

		return !$ok;
	}
}
