<?php
/**
 * Fabrik Dropdown Element
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.dropdown
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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

		// Set elementlist params from drop-down params
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
		$params = $this->getParams();
		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		$endIs = $this->getSubOptionEnDis();
		$multiple = $params->get('multiple', 0);
		$multiSize = $params->get('dropdown_multisize', 3);
		$selected = (array) $this->getValue($data, $repeatCounter);

		$errorCSS = $this->elementError != '' ? " elementErrorHighlight" : '';
		$bootstrapClass = $params->get('bootstrap_class', '');
		$advancedClass = $this->getAdvancedSelectClass();

		$attributes = 'class="fabrikinput form-control inputbox input ' . $advancedClass . ' ' . $errorCSS . ' ' . $bootstrapClass . '"';

		if ($multiple == '1')
		{
			$attributes .= ' multiple="multiple" size="' . $multiSize . '" ';
		}

		$i = 0;
		$aRoValues = array();
		$opts = array();
		$optGroup = false;

		foreach ($values as $tmpVal)
		{
			if ($tmpVal === '<optgroup>')
			{
				$optGroup = true;
			}

			$tmpLabel = FArrayHelper::getValue($labels, $i);
			$disable = FArrayHelper::getValue($endIs, $i, false);

			// For values like '1"'
			$tmpVal = htmlspecialchars($tmpVal, ENT_QUOTES);
			$opt = JHTML::_('select.option', $tmpVal, $tmpLabel);
			$opt->disable = $disable;
			$opts[] = $opt;

			if (in_array($tmpVal, $selected))
			{
				$aRoValues[] = $this->getReadOnlyOutput($tmpVal, $tmpLabel);
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
					$opts[] = JHTML::_('select.option', htmlspecialchars($sel, ENT_QUOTES), $sel);
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
		$settings['list.attr'] = $attributes;
		$settings['group.items'] = null;

		if ($optGroup)
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

			// @todo JLayout list
			$str = JHTML::_('select.groupedlist', $groupedOpts, $name, $settings);
		}
		else
		{
			$layout = $this->getLayout('form');
			$displayData = new stdClass;
			$displayData->options = $opts;
			$displayData->name = $name;

			foreach ($selected as &$sel)
			{
				$sel = htmlspecialchars($sel, ENT_QUOTES);
			}

			$displayData->selected = $selected;
			$displayData->id = $id;
			$displayData->errorCSS = $errorCSS;
			$displayData->multiple = $multiple;
			$displayData->attribs = $attributes;
			$displayData->multisize = $multiple ? $multiSize : '';

			$str = $layout->render($displayData);
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
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$data = $this->getFormModel()->data;
		$arSelected = $this->getValue($data, $repeatCounter);
		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->allowadd = $params->get('allow_frontend_addtodropdown', false) ? true : false;
		$opts->value = $arSelected;
		$opts->defaultVal = $this->getDefaultValue($data);
		$opts->data = (empty($values) && empty($labels)) ? array() : array_combine($values, $labels);
		$opts->multiple = (bool) $params->get('multiple', '0') == '1';
		$opts->advanced = $this->getAdvancedSelectClass() != '';
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
	 * Used in rendering for adding fabrikEmpty class, etc
	 *
	 * @param   array  $data           data to test against
	 * @param   int    $repeatCounter  repeat group #
	 *
	 * @return  bool
	 */
	public function dataConsideredEmpty($data, $repeatCounter)
	{
		$data = $this->replaceLabelWithValue($data);

		if (is_array($data))
		{
			if (empty($data[0]) || $data[0] == "-1")
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
	 * Does the element consider the data to be empty
	 * Used during form submission, eg. for isEmpty validation rule
	 *
	 * @param   array  $data           data to test against
	 * @param   int    $repeatCounter  repeat group #
	 *
	 * @return  bool
	 */
	public function dataConsideredEmptyForValidation($data, $repeatCounter)
	{
		if (is_array($data))
		{
			if (empty($data[0]) || $data[0] == "-1")
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

		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		$return = array();
		$i = 0;

		foreach ($labels as $label)
		{
			if (in_array($label, $selected))
			{
				$return[] = $values[$i];
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
	 * Examples of where this would be overwritten include time date element with time field enabled
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

	/**
	 * Build the filter query for the given element.
	 * Can be overwritten in plugin - e.g. see checkbox element which checks for partial matches
	 *
	 * @param   string  $key            Element name in format `tablename`.`elementname`
	 * @param   string  $condition      =/like etc.
	 * @param   string  $value          Search string - already quoted if specified in filter array options
	 * @param   string  $originalValue  Original filter value without quotes or %'s applied
	 * @param   string  $type           Filter type advanced/normal/prefilter/search/querystring/searchall
	 * @param   string  $evalFilter     evaled
	 *
	 * @return  string	sql query part e,g, "key = value"
	 */
	public function getFilterQuery($key, $condition, $value, $originalValue, $type = 'normal', $evalFilter = '0')
	{
		$params = $this->getParams();
		$condition = JString::strtoupper($condition);
		$this->encryptFieldName($key);

		if ((bool) $params->get('multiple', false))
		{
			// Multiple select options need to be treated specially (regardless of filter type?)
			// see http://fabrikar.com/forums/index.php?threads/how-filter-a-dropdown-element-in-the-plug-fabrik-content.42089/
			$str = $this->filterQueryMultiValues($key, $condition, $originalValue, $evalFilter, $type);
		}
		else
		{
			$str = parent::getFilterQuery($key, $condition, $value, $originalValue, $type, $evalFilter);
		}

		return $str;
	}
}
