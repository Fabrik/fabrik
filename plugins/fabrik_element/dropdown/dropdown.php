<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.dropdown
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Plugin element to render dropdown
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.dropdown
 * @since       3.0
 */

class plgFabrik_ElementDropdown extends plgFabrik_ElementList
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
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to preopulate element with
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
		$allowAdd = $params->get('allow_frontend_addtodropdown', false);

		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		$multiple = $params->get('multiple', 0);
		$multisize = $params->get('dropdown_multisize', 3);
		$selected = (array) $this->getValue($data, $repeatCounter);
		$errorCSS = (isset($this->_elementError) && $this->_elementError != '') ? " elementErrorHighlight" : '';
		$attribs = 'class="fabrikinput inputbox' . $errorCSS . '"';

		if ($multiple == "1")
		{
			$attribs .= ' multiple="multiple" size="' . $multisize . '" ';
		}
		$i = 0;
		$aRoValues = array();
		$opts = array();
		foreach ($values as $tmpval)
		{
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
		 * If we have added an option that hasnt been saved to the database. Note you cant have
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
		$str = JHTML::_('select.genericlist', $opts, $name, $attribs, 'value', 'text', $selected, $id);
		if (!$this->_editable)
		{
			return implode(', ', $aRoValues);
		}
		$str .= $this->getAddOptionFields($repeatCounter);
		return $str;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  string
	 */

	public function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
		$data = $this->_form->_data;
		$arSelected = $this->getValue($data, $repeatCounter);
		$values = $this->getSubOptionValues();
		$labels = $this->getSubOptionLabels();
		$params = $this->getParams();

		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->allowadd = $params->get('allow_frontend_addtodropdown', false) ? true : false;
		$opts->value = $arSelected;
		$opts->defaultVal = $this->getDefaultValue($data);

		$opts->data = (empty($values) && empty($labels)) ? array() : array_combine($values, $labels);
		$opts = json_encode($opts);
		JText::script('PLG_ELEMENT_DROPDOWN_ENTER_VALUE_LABEL');
		return "new FbDropdown('$id', $opts)";
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

		if (!isset($this->_default))
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
						$v = @eval(stripslashes($default));
						FabrikWorker::logEval($default, 'Caught exception on eval in ' . $element->name . '::getDefaultValue() : %s');
					}else{
						$v = $default;
					}
				}
				if (is_string($v))
				{
					$this->_default = explode('|', $v);
				}
				else
				{
					$this->_default = $v;
				}
			}
			else
			{
				$this->_default = $this->getSubInitialSelection();
			}
		}
		return $this->_default;
	}

	/**
	 * Does the element conside the data to be empty
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
	 * Repalce a value with its label
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
	 * Build the filter query for the given element.
	 * Can be overwritten in plugin - e.g. see checkbox element which checks for partial matches
	 *
	 * @param   string  $key            element name in format `tablename`.`elementname`
	 * @param   string  $condition      =/like etc
	 * @param   string  $label          search string - already quoted if specified in filter array options
	 * @param   string  $originalValue  original filter value without quotes or %'s applied
	 * @param   string  $type           filter type advanced/normal/prefilter/search/querystring/searchall
	 *
	 * @return  string	sql query part e,g, "key = value"
	 */

	public function getFilterQuery($key, $condition, $label, $originalValue, $type = 'normal')
	{
		$value = $label;
		$db = JFactory::getDbo();
		if ($type == 'searchall')
		{
			// $$$ hugh - (sometimes?) $label is already quoted, which is causing havoc ...
			$values = $this->replaceLabelWithValue(trim($label, "'"));
			if (empty($values))
			{
				$value = '';
			}
			else
			{
				$value = $values[0];
			}
			if ($value == '')
			{
				$value = $label;
			}
			if (!preg_match('#^\'.*\'$#', $value))
			{
				$value = $db->quote($value);
			}
		}
		$this->encryptFieldName($key);
		$params = $this->getParams();
		if ($params->get('multiple'))
		{
			$originalValue = trim($value, "'");
			/*
			 * JSON stored values will back slash "/". So wwe need to add "\\\\"
			 * before it to escape it for the query.
			 */
			$originalValue = str_replace("/", "\\\\/", $originalValue);

			$where1 = ('["' . $originalValue . '",%');
			$where2 = ('%,"' . $originalValue . '",%');
			$where3 = ('%,"' . $originalValue . '"]');

			$str = "($key $condition $value " . " OR $key LIKE " . $db->quote('["' . $originalValue . '"%') . " OR $key LIKE "
				. $db->quote('%"' . $originalValue . '"%') . " OR $key LIKE " . $db->quote('%"' . $originalValue . '"]') . ")";
			return $str;
		}
		else
		{
			return parent::getFilterQuery($key, $condition, $value, $originalValue, $type);
		}
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
