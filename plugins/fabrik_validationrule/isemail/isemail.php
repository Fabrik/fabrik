<?php
/**
 * Is Email Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isemail
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

/**
 * Is Email Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isemail
 * @since       3.0
 */

class PlgFabrik_ValidationruleIsEmail extends PlgFabrik_Validationrule
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'isemail';

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
		$email = $data;

		// Could be a dropdown with multivalues
		if (is_array($email))
		{
			$email = implode('', $email);
		}

		// Decode as it can be posted via ajax
		$email = urldecode($email);
		$params = $this->getParams();
		$allow_empty = $params->get('isemail-allow_empty');

		if ($allow_empty == '1' and empty($email))
		{
			return true;
		}

		// $$$ hugh - let's try using new helper func instead of rolling our own.
		return FabrikWorker::isEmail($email);
	}

	/**
	 * Does the validation allow empty value?
	 * Default is false, can be overridden on per-validation basis (such as isnumeric)
	 *
	 * @return  bool
	 */

	protected function allowEmpty()
	{
		$params = $this->getParams();
		$allow_empty = $params->get('isemail-allow_empty');

		return $allow_empty == '1';
	}
}
