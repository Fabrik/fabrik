<?php
/**
 * Plugin element to a series of radio buttons
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.radiolist
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

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

	protected function getIndEmailValue($value, $data = array(), $repeatCounter = 0)
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
		$data = $this->getFormModel()->data;
		$arVals = $this->getSubOptionValues();
		$arTxt = $this->getSubOptionLabels();
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->value = $this->getValue($data, $repeatCounter);
		$opts->defaultVal = $this->getDefaultValue($data);
		$opts->data = empty($arVals) ? array() : array_combine($arVals, $arTxt);
		$opts->allowadd = $params->get('allow_frontend_addtoradio', false) ? true : false;
		$opts->changeEvent = $this->getChangeEvent();
		$opts->btnGroup = $this->buttonGroup();
		JText::script('PLG_ELEMENT_RADIO_ENTER_VALUE_LABEL');

		return array('FbRadio', $id, $opts);
	}

	/**
	 * if the search value isn't what is stored in the database, but rather what the user
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
			if (is_string($value))
			{
				if (JString::strtolower($labels[$i]) == JString::strtolower($value))
				{
					$val = $values[$i];

					return $val;
				}
			}
			else
			{
				if (in_array(JString::strtolower($labels[$i]), $value))
				{
					foreach ($value as &$v)
					{
						if (JString::strtolower($labels[$i]) == JString::strtolower($v))
						{
							$v = $values[$i];
						}
					}
				}
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
			$sel = FArrayHelper::getValue($sel, 0, '');
			$arVals = $this->getSubOptionValues();
			$data[$element->name] = array(FArrayHelper::getValue($arVals, $sel, ''));
		}
	}

	/**
	 * Builds an array containing the filters value and condition
	 *
	 * @param   string  $value      initial value
	 * @param   string  $condition  initial $condition
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
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  When repeating joined groups we need to know what part of the array to access
	 * @param   array  $opts           Options
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

	/**
	 * Return JS event required to trigger a 'change', this is overriding default element model.
	 * When in BS mode with button-grp, needs to be 'click'.
	 *
	 * @return  string
	 */

	public function getChangeEvent()
	{
		return $this->buttonGroup() ? 'click' : 'change';
	}

	/**
	 * Get classes to assign to the grid
	 * An array of arrays of class names, keyed as 'container', 'label' or 'input',
	 *
	 * @return  array
	 */
	protected function gridClasses()
	{
		if ($this->buttonGroup())
		{
			return array(
				'label' => array('btn-default'),
				'container' => array('btn-radio')
			);
		}
		else
		{
			return array();
		}
	}

	/**
	 * Get data attributes to assign to the container
	 *
	 * @return  array
	 */
	protected function dataAttributes()
	{
		if ($this->buttonGroup())
		{
			return array('data-toggle="buttons"');
		}
		else
		{
			return array();
		}
	}


}
