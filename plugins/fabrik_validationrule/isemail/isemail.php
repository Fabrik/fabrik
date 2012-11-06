<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Pollen 8 Design Ltd. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

class PlgFabrik_ValidationruleIsEmail extends PlgFabrik_Validationrule
{

	protected $pluginName = 'isemail';

	/**
	 * If true uses icon of same name as validation, otherwise uses png icon specified by $icon
	 *
	 *  @var bool
	 */
	protected $icon = 'isemail';

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string  $data           to check
	 * @param   object  &$elementModel  element Model
	 * @param   int     $pluginc        plugin sequence ref
	 * @param   int     $repeatCounter  repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */

	public function validate($data, &$elementModel, $pluginc, $repeatCounter)
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
		$allow_empty = $allow_empty->$pluginc;
		if ($allow_empty == '1' and empty($email))
		{
			return true;
		}
		// $$$ hugh - let's try using new helper func instead of rolling our own.
		return FabrikWorker::isEmail($email);

		// $$$ keeping this code just in case, but shouldn't be reached.

		// First, we check that there's one symbol, and that the lengths are right
		if (!preg_match("/[^@]{1,64}@[^@]{1,255}/", $email))
		{
			// Email invalid because wrong number of characters in one section, or wrong number of symbols.
			return false;
		}

		// Split it into sections to make life easier
		$email_array = explode("@", $email);
		$local_array = explode(".", $email_array[0]);
		for ($i = 0; $i < count($local_array); $i++)
		{
			if (!preg_match("/^(([A-Za-z0-9!#$%&'*+\/=?^_`{|}~-][A-Za-z0-9!#$%&'*+\/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$/", $local_array[0]))
			{
				return false;
			}
		}
		// Check if domain is IP. If not, it should be valid domain name
		if (!preg_match("/^\[?[0-9\.]+\]?$/", $email_array[1]))
		{
			$domain_array = explode(".", $email_array[1]);
			if (count($domain_array) < 2)
			{
				// Not enough parts to domain
				return false;
			}
			for ($i = 0; $i < count($domain_array); $i++)
			{
				if (!preg_match("/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$/", $domain_array[$i]))
				{
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Does the validation allow empty value?
	 * Default is false, can be overrideen on per-validation basis (such as isnumeric)
	 *
	 * @param   object  $elementModel  element model
	 * @param   int     $pluginc       validation plugin order
	 *
	 * @return  bool
	 */

	protected function allowEmpty($elementModel, $pluginc)
	{
		$params = $this->getParams();
		$allow_empty = $params->get('isemail-allow_empty');
		$allow_empty = $allow_empty->$pluginc;
		return $allow_empty == '1';
	}

}
