<?php
/**
 * Is Greater or Less than Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isgreatorlessthan
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin classes
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

/**
 * Is Greater or Less than Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isgreatorlessthan
 * @since       3.0
 */

class PlgFabrik_ValidationruleIsgreaterorlessthan extends PlgFabrik_Validationrule
{

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'isgreaterorlessthan';

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
		// Could be a dropdown with multivalues
		if (is_array($data))
		{
			$data = implode('', $data);
		}
		$params = $this->getParams();
		$elementModel = $this->elementModel;
		$formdata = $elementModel->getForm()->formData;
		$cond = $params->get('isgreaterorlessthan-greaterthan');
		switch ($cond)
		{
			case '0':
				$cond = '<';
				break;
			case '1':
				$cond = '>';
				break;
			case '2':
				$cond = '<=';
				break;
			case '3':
				$cond = '>=';
				break;
			case '4':
			default:
				$cond = '==';
				break;
		}
		$otherElementModel = $this->getOtherElement();
		$otherFullName = $otherElementModel->getFullName(true, false);
		$compare = $otherElementModel->getValue($formdata, $repeatCounter);
		if ($this->allowEmpty() && ($data === '' || $compare === ''))
		{
			return true;
		}
		$res = $elementModel->greaterOrLessThan($data, $cond, $compare);
		return $res;
	}

	/**
	 * Does the validation allow empty value?
	 * Default is false, can be overrideen on per-validation basis (such as isnumeric)
	 *
	 * @return	bool
	 */

	protected function allowEmpty()
	{
		$params = $this->getParams();
		$allow_empty = $params->get('isgreaterorlessthan-allow_empty');
		return $allow_empty == '1';
	}

	/**
	 * Get the other element to compare this elements data against
	 *
	 * @return  object element model
	 */

	private function getOtherElement()
	{
		$params = $this->getParams();
		$otherfield = $params->get('isgreaterorlessthan-comparewith');
		return FabrikWorker::getPluginManager()->getElementPlugin($otherfield);
	}

}
