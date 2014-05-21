<?php
/**
 * Is Not Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isnot
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

/**
 * Is Not Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isnot
 * @since       3.0
 */

class PlgFabrik_ValidationruleIsNot extends PlgFabrik_Validationrule
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'isnot';

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
		if (is_array($data))
		{
			$data = implode('', $data);
		}

		$params = $this->getParams();
		$isnot = $params->get('isnot-isnot');
		$isnot = explode('|', $isnot);

		foreach ($isnot as $i)
		{
			if ((string) $data === (string) $i)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Gets the hover/alt text that appears over the validation rule icon in the form
	 *
	 * @return  string	label
	 */

	protected function getLabel()
	{
		$params = $this->getParams();
		$tipText = $params->get('tip_text', '');

		if ($tipText !== '')
		{
			return FText::_($tipText);
		}

		$isnot = $params->get('isnot-isnot');

		return JText::sprintf('PLG_VALIDATIONRULE_ISNOT_LABEL', $isnot);
	}
}
