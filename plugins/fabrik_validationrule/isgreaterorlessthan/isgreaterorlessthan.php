<?php
/**
 * Is Greater or Less than Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isgreaterorlessthan
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
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
 * @subpackage  Fabrik.validationrule.isgreaterorlessthan
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
	 * Form data
	 *
	 * @var object
	 */
	private $formData = null;

	/**
	 * Condition values
	 *
	 * @var object
	 */
	private $condValues = array('<','>','<=','>=','==');

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
		// Could be a drop-down with multi-values
		if (is_array($data))
		{
			$data = implode('', $data);
		}

		$c1 = $this->getOtherElement($repeatCounter);
		$c2 = $this->getOtherElement($repeatCounter, '2');
		$res = $this->elementModel->greaterOrLessThan($data, $c1['cond'], $c1['compare']);
		if ($c2['cond'] !== '')
		{
			$res &= $this->elementModel->greaterOrLessThan($data, $c2['cond'], $c2['compare']);
		}

		if ($res)
		{
			return true;
		}

		if ($this->allowEmpty())
		{
			if ($data === '' || $c1['compare'] === '')
			{
				return true;
			}
			if ($c2['cond'] !== "" && $c2['compare'] === '')
			{
				return true;
			}
		}

		$this->errorMsg = $this->getLabel();
		return $res;
	}

	/**
	 * Get the other element to compare this elements data against
	 *
	 * @return  object element model
	 */
	private function getOtherElement($repeatCounter, $suffix = '')
	{
		$params = $this->getParams();

		$cond = $params->get('isgreaterorlessthan-greaterthan' . $suffix, '');
		if ($cond == '')
		{
			return array('cond' => '', 'compare' => '', 'name' => '');
		}
		if (array_key_exists($cond, $this->condValues))
		{
			$cond = $this->condValues[$cond];
		} else {
			$cond = '==';
		}

		$compare = $params->get('compare_value' . $suffix, '');
		$name = "'" . $compare . "'";
		$fieldId = $params->get('isgreaterorlessthan-comparewith' . $suffix, '');

		if ((int) $fieldId !== 0 && $compare === '') {
			if (is_null($this->formData))
			{
				$this->formData = $this->elementModel->getForm()->formData;
			}
			$fieldElementModel = FabrikWorker::getPluginManager()->getElementPlugin($fieldId);
			$compare = $fieldElementModel->getValue($this->formData, $repeatCounter);
			$name = $fieldElementModel->getElement()->label;
		}

		return array('cond' => $cond, 'compare' => $compare, 'name' => $name);
	}

	/**
	 * Does the validation allow empty value?
	 * Default is false, can be overridden on per-validation basis (such as isnumeric)
	 *
	 * @return	bool
	 */
	protected function allowEmpty()
	{
		$params = $this->getParams();
		$allow_empty = $params->get('isgreaterorlessthan-allow_empty', '');

		return $allow_empty == '1';
	}

	/**
	 * Gets the hover/alt text that appears over the validation rule icon in the form
	 *
	 * @return    string    label
	 */
	protected function getLabel()
	{
		$params      = $this->getParams();
		$tipText     = $params->get('tip_text', '');

		$c1 = $this->getOtherElement(0);
		$c2 = $this->getOtherElement(0, '2');

		if (!empty($tipText) || $c1['name'] === '')
		{
			return parent::getLabel();
		}

		$text = $c1['cond'] . " " . $c1['name'];
		if ($c2['name'] !== '')
		{
			$text .= ' ' . strtolower(JText::_('COM_FABRIK_AND')) . " " . $c2['cond'] . " " . $c2['name'];
		}

		return JText::sprintf('PLG_VALIDATIONRULE_ISGREATERORLESSTHAN_ADDITIONAL_LABEL', $text);
	}
}
