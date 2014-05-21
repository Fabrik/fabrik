<?php
/**
 * Fabrik Dropdown Element
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.dropdown
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Plugin element to render dropdown
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.dropdown
 * @since       3.0
 */

class PlgFabrik_ElementDropdown extends PlgFabrik_ElementList
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

		// Set elementlist params from dropdown params
		$params->set('allow_frontend_addto', (bool) $params->get('allow_frontend_addtodropdown', false));
		$params->set('allowadd-onlylabel', (bool) $params->get('dd-allowadd-onlylabel', true));
		$params->set('savenewadditions', (bool) $params->get('dd-savenewadditions', false));
		$params->set('options_populate', $params->get('dropdown_populate', ''));
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
		$params = $this->getParams();
		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		$multiple = $params->get('multiple', 0);
		$multisize = $params->get('dropdown_multisize', 3);
		$selected = (array) $this->getValue($data, $repeatCounter);
		$errorCSS = $this->elementError != '' ? " elementErrorHighlight" : '';
		$boostrapClass = $params->get('bootstrap_class', '');
		$attribs = 'class="fabrikinput inputbox input ' . $errorCSS . ' ' . $boostrapClass . '"';

		if ($multiple == "1")
		{
			$attribs .= ' multiple="multiple" size="' . $multisize . '" ';
		}

		$i = 0;
		$aRoValues = array();
		$opts = array();
		$optgroup = false;

		foreach ($values as $tmpval)
		{
			if ($tmpval === '<optgroup>')
			{
				$optgroup = true;
			}

			$tmpLabel = JArrayHelper::getValue($labels, $i);

			// For values like '1"'
			$tmpval = htmlspecialchars($tmpval, ENT_QUOTES);
			$opts[] = JHTML::_('select.option', $tmpval, $tmpLabel);

			if (in_array($tmpval, $selected))
			{
				$aRoValues[] = $this->getReadOnlyOutput($tmpval, $tmpLabel);
			}

			$i++;
		}
		/*
		 * If we have added an option that hasn't been saved to the database. Note you cant have
		 * it not saved to the database and asking the user to select a value and label
		 */
		if ($params->get('allow_frontend_addtodropdown', false) && !empty($selected))
		{
			foreach ($selected as $sel)
			{
				if (!in_array($sel, $values) && $sel !== '')
				{
					$opts[] = JHTML::_('select.option', $sel, $sel);
					$aRoValues[] = $this->getReadOnlyOutput($sel, $sel);
				}
			}
		}

		if (!$this->isEditable())
		{
			return implode(', ', $aRoValues);
		}

		$settings = array();
		$settings['list.select'] = $selected;
		$settings['option.id'] = $id;
		$settings['id'] = $id;
		$settings['list.attr'] = $attribs;
		$settings['group.items'] = null;


		if ($optgroup)
		{
			$groupedOpts = array();
			$groupOptLabel = '';

			foreach ($opts as $opt)
			{
				if ($opt->value === '&lt;optgroup&gt;')
				{
					$groupOptLabel = $opt->text;
					continue;
				}

				$groupedOpts[$groupOptLabel][] = $opt;
			}

			$str = JHTML::_('select.groupedlist', $groupedOpts, $name, $settings);
		}
		else
		{
			$str = JHTML::_('select.genericlist', $opts, $name, $settings);
		}

		$str .= $this->getAddOptionFields($repeatCounter);

		return $str;
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
		$id = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
		$data = $this->getFormModel()->data;
		$arSelected = $this->getValue($data, $repeatCounter);
		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		$params = $this->getParams();
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->allowadd = $params->get('allow_frontend_addtodropdown', false) ? true : false;
		$opts->value = $arSelected;
		$opts->defaultVal = $this->getDefaultValue($data);
		$opts->data = (empty($values) && empty($labels)) ? array() : array_combine($values, $labels);
		JText::script('PLG_ELEMENT_DROPDOWN_ENTER_VALUE_LABEL');

		return array('FbDropdown', $id, $opts);
	}

	/**
	 * This really does get just the default value (as defined in the element's settings)
	 *
	 * @param   array  $data  form data
	 *
	 * @return mixed
	 */

	public function getDefaultValue($data = array())
	{
		$params = $this->getParams();
		$element = $this->getElement();

		if (!isset($this->default))
		{
			if ($element->default != '')
			{
				$default = $element->default;
				/*
				 * Nasty hack to fix #504 (eval'd default value)
				* where _default not set on first getDefaultValue
				* and then its called again but the results have already been eval'd once and are hence in an array
				*/
				if (is_array($default))
				{
					$v = $default;
				}
				else
				{
					$w = new FabrikWorker;
					$default = $w->parseMessageForPlaceHolder($default, $data);

					if ($element->eval == "1")
					{
						$v = @eval((string) stripslashes($default));
						FabrikWorker::logEval($default, 'Caught exception on eval in ' . $element->name . '::getDefaultValue() : %s');
					}
					else
					{
						$v = $default;
					}
				}

				if (is_string($v))
				{
					$this->default = explode('|', $v);
				}
				else
				{
					$this->default = $v;
				}
			}
			else
			{
				$this->default = $this->getSubInitialSelection();
			}
		}

		return $this->default;
	}

	/**
	 * Does the element consider the data to be empty
	 * Used in isempty validation rule
	 *
	 * @param   array  $data           data to test against
	 * @param   int    $repeatCounter  repeat group #
	 *
	 * @return  bool
	 */

	public function dataConsideredEmpty($data, $repeatCounter)
	{
		// $$$ hugh - $data seems to be an array now?
		if (is_array($data))
		{
			if (empty($data[0]))
			{
				return true;
			}
		}
		else
		{
			if ($data == '' || $data == '-1')
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Replace a value with its label
	 *
	 * @param   string  $selected  value
	 *
	 * @return  string	label
	 */

	protected function replaceLabelWithValue($selected)
	{
		$selected = (array) $selected;

		foreach ($selected as &$s)
		{
			$s = str_replace("'", "", $s);
		}

		$element = $this->getElement();
		$vals = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		$return = array();
		$aRoValues = array();
		$opts = array();
		$i = 0;

		foreach ($labels as $label)
		{
			if (in_array($label, $selected))
			{
				$return[] = $vals[$i];
			}

			$i++;
		}

		return $return;
	}

	/**
	 * If the search value isn't what is stored in the database, but rather what the user
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
				return $values[$i];
			}
		}

		return $value;
	}

	/**
	 * Get an array of element html ids and their corresponding
	 * js events which trigger a validation.
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  array  html ids to watch for validation
	 */

	public function getValidationWatchElements($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$ar = array('id' => $id, 'triggerEvent' => 'change');

		return array($ar);
	}
}
