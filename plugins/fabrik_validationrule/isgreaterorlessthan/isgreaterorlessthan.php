<?php
/**
 * Is Greater or Less than Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isgreatorlessthan
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

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
		$formdata = $elementModel->getForm()->formData;
		$cond = $params->get('isgreaterorlessthan-greaterthan');
		$cond = $cond[$pluginc];
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
		$otherElementModel = $this->getOtherElement($elementModel, $pluginc);
		$otherFullName = $otherElementModel->getFullName(true, false);
		$compare = $otherElementModel->getValue($formdata, $repeatCounter);
		if ($this->allowEmpty($elementModel, $pluginc) && ($data === '' || $compare === ''))
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
	 * @param   object  $elementModel  Element model
	 * @param   int     $pluginc       Validation render order
	 *
	 * @return	bool
	 */

	protected function allowEmpty($elementModel, $pluginc)
	{
		$params = $this->getParams();
		$allow_empty = $params->get('isgreaterorlessthan-allow_empty');
		$allow_empty = $allow_empty[$pluginc];
		return $allow_empty == '1';
	}

	/**
	 * Get the other element to compare this elements data against
	 *
	 * @param   object  $elementModel  Element model
	 * @param   int     $pluginc       Validation render order
	 *
	 * @return  object element model
	 */

	private function getOtherElement($elementModel, $pluginc)
	{
		$params = $this->getParams();
		$otherfield = (array) $params->get('isgreaterorlessthan-comparewith', array());
		$otherfield = $otherfield[$pluginc];
		return FabrikWorker::getPluginManager()->getElementPlugin($otherfield);
	}

}
