<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.radiolist
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Plugin element to a series of radio buttons
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.radiolist
 * @since       3.0
 */

class PlgFabrik_ElementRadiobutton extends PlgFabrik_ElementList
{

	/**
	 * Does the element have a label
	 * @var bool
	 */
	protected $hasLabel = false;

	/**
	* Method to set the element id
	*
	* @param   int  $id  element ID number
	*
	* @return  void
	*/

	public function setId($id)
	{
		parent::setId($id);
		$params = $this->getParams();

		// Set elementlist params from radio params
		$params->set('element_before_label', (bool) $params->get('radio_element_before_label', true));
		$params->set('allow_frontend_addto', (bool) $params->get('allow_frontend_addtoradio', false));
		$params->set('allowadd-onlylabel', (bool) $params->get('rad-allowadd-onlylabel', true));
		$params->set('savenewadditions', (bool) $params->get('rad-savenewadditions', false));
	}

	/**
	 * Turn form value into email formatted value
	 *
	 * @param   mixed  $value          element value
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  group repeat counter
	 *
	 * @return  string  email formatted value
	 */

	protected function _getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		if (empty($value))
		{
			return '';
		}
		$labels = $this->getSubOptionLabels();
		$values = $this->getSubOptionValues();
		$key = array_search($value[0], $values);
		$val = ($key === false) ? $value[0] : $labels[$key];
		return $val;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$data = $this->_form->_data;
		$arVals = $this->getSubOptionValues();
		$arTxt = $this->getSubOptionLabels();
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->value = $this->getValue($data, $repeatCounter);
		$opts->defaultVal = $this->getDefaultValue($data);
		$opts->data = empty($arVals) ? array() : array_combine($arVals, $arTxt);
		$opts->allowadd = $params->get('allow_frontend_addtoradio', false) ? true : false;
		JText::script('PLG_ELEMENT_RADIO_ENTER_VALUE_LABEL');
		return array('FbRadio', $id, $opts);
	}

	/**
	 * if the search value isnt what is stored in the database, but rather what the user
	 * sees then switch from the search string to the db value here
	 * overwritten in things like checkbox and radio plugins
	 *
	 * @param   string  $value  filterVal
	 *
	 * @return  string
	 */

	protected function prepareFilterVal($value)
	{
		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		for ($i = 0; $i < count($labels); $i++)
		{
			if (JString::strtolower($labels[$i]) == JString::strtolower($value))
			{
				$val = $values[$i];
				return $val;
			}
		}
		return $value;
	}

	/**
	 * If your element risks not to post anything in the form (e.g. check boxes with none checked)
	 * the this function will insert a default value into the database
	 *
	 * @param   array  &$data  form data
	 *
	 * @return  array  form data
	 */

	public function getEmptyDataValue(&$data)
	{
		$params = $this->getParams();
		$element = $this->getElement();
		if (!array_key_exists($element->name, $data))
		{
			$sel = $this->getSubInitialSelection();
			$sel = JArrayHelper::getValue($sel, 0, '');
			$arVals = $this->getSubOptionValues();
			$data[$element->name] = array(JArrayHelper::getValue($arVals, $sel, ''));
		}
	}

	/**
	 * Builds an array containing the filters value and condition
	 *
	 * @param   string  $value      initial value
	 * @param   string  $condition  intial $condition
	 * @param   string  $eval       how the value should be handled
	 *
	 * @return  array	(value condition)
	 */

	public function getFilterValue($value, $condition, $eval)
	{
		$value = $this->prepareFilterVal($value);
		$return = parent::getFilterValue($value, $condition, $eval);
		return $return;
	}

	/**
	 * Used by inline edit table plugin
	 * If returns yes then it means that there are only two possible options for the
	 * ajax edit, so we should simply toggle to the alternative value and show the
	 * element rendered with that new value (used for yes/no element)
	 *
	 * @deprecated - only called in a deprecated element method
	 *
	 * @return  bool
	 */

	protected function canToggleValue()
	{
		return count($this->getSubOptionValues()) < 3 ? true : false;
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           form data
	 * @param   int    $repeatCounter  when repeating joinded groups we need to know what part of the array to access
	 * @param   array  $opts           options
	 *
	 * @return  string	value
	 */

	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		$v = parent::getValue($data, $repeatCounter, $opts);

		// $$$ rob see http://fabrikar.com/forums/showthread.php?t=25965
		if (is_array($v) && count($v) == 1)
		{
			$v = $v[0];
		}
		return $v;
	}
}
