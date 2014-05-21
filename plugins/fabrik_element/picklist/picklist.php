<?php
/**
 * Plugin element to two lists - one to select from the other to select into
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.picklist
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to two lists - one to select from the other to select into
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.picklist
 * @since       3.0
 */

class PlgFabrik_ElementPicklist extends PlgFabrik_ElementList
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

		// Set elementlist params from picklist params
		$params->set('allow_frontend_addto', (bool) $params->get('allowadd', false));
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To pre-populate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
		$params = $this->getParams();
		$arVals = $this->getSubOptionValues();
		$arTxt = $this->getSubOptionLabels();
		$arSelected = (array) $this->getValue($data, $repeatCounter);

		$errorCSS = $this->elementError != '' ? " elementErrorHighlight" : '';
		$attribs = 'class="span6 ' . $errorCSS . "\"";

		FabrikHelperHTML::stylesheet(COM_FABRIK_LIVESITE . 'plugins/fabrik_element/picklist/picklist.css');
		$i = 0;
		$aRoValues = array();
		$fromlist = array();
		$tolist = array();
		$fromlist[] = FText::_('PLG_FABRIK_PICKLIST_FROM') . ':<ul id="' . $id . '_fromlist" class="picklist well well-small fromList">';
		$tolist[] = FText::_('PLG_FABRIK_PICKLIST_TO') . ':<ul id="' . $id . '_tolist" class="picklist well well-small toList">';

		foreach ($arVals as $v)
		{
			if (!in_array($v, $arSelected))
			{
				$fromlist[] = '<li id="' . $id . '_value_' . $v . '" class="picklist">' . $arTxt[$i] . '</li>';
			}

			$i++;
		}

		$i = 0;
		$lookup = array_flip($arVals);

		foreach ($arSelected as $v)
		{
			if ($v == '' || $v == '-' || $v == '[""]')
			{
				continue;
			}

			$k = JArrayHelper::getValue($lookup, $v);
			$tmptxt = addslashes(htmlspecialchars(JArrayHelper::getValue($arTxt, $k)));
			$tolist[] = '<li id="' . $id . '_value_' . $v . '" class="' . $v . '">' . $tmptxt . '</li>';
			$aRoValues[] = $tmptxt;
			$i++;
		}

		$dragLbl = FText::_('PLG_ELEMENT_PICKLIST_DRAG_OPTIONS_HERE');
		$fromlist[] = '<li class="emptyplicklist" style="display:none"><i class="icon-move"></i> ' . $dragLbl . '</li>';
		$tolist[] = '<li class="emptyplicklist" style="display:none"><i class="icon-move"></i> ' . $dragLbl . '</li>';
		$fromlist[] = '</ul>';
		$tolist[] = '</ul>';
		$str = '<div ' . $attribs . '>' . implode("\n", $fromlist) . '</div>';
		$str .= '<div class="span6">' . implode("\n", $tolist) . '</div>';
		$str .= $this->getHiddenField($name, json_encode($arSelected), $id);

		if (!$this->isEditable())
		{
			return implode(', ', $aRoValues);
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
		$arVals = $this->getSubOptionValues();
		$arTxt = $this->getSubOptionLabels();
		$params = $this->getParams();
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->allowadd = (bool) $params->get('allowadd', false);
		$opts->defaultVal = $this->getValue($data, $repeatCounter);

		$opts->hovercolour = $params->get('picklist-hovercolour', '#AFFFFD');
		$opts->bghovercolour = $params->get('picklist-bghovercolour', '#FFFFDF');
		JText::script('PLG_ELEMENT_PICKLIST_ENTER_VALUE_LABEL');

		return array('FbPicklist', $id, $opts);
	}

	/**
	 * if the search value isn't what is stored in the database, but rather what the user
	 * sees then switch from the search string to the db value here
	 * overwritten in things like checkbox and radio plugins
	 *
	 * @param   string  $value  FilterVal
	 *
	 * @return  string
	 */

	protected function prepareFilterVal($value)
	{
		$arVals = $this->getSubOptionValues();
		$arTxt = $this->getSubOptionLabels();

		for ($i = 0; $i < count($arTxt); $i++)
		{
			if (JString::strtolower($arTxt[$i]) == JString::strtolower($val))
			{
				$val = $arVals[$i];

				return $val;
			}
		}

		return $val;
	}

	/**
	 * Builds an array containing the filters value and condition
	 *
	 * @param   string  $value      Initial value
	 * @param   string  $condition  Intial $condition
	 * @param   string  $eval       How the value should be handled
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
		$data = (array) $data;

		foreach ($data as $d)
		{
			if ($d != '' && $d != '[""]')
			{
				return false;
			}
		}

		return true;
	}
}
