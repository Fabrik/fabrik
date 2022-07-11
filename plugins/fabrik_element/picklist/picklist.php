<?php
/**
 * Plugin element to two lists - one to select from the other to select into
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.picklist
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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
	 * Does the element have sub elements
	 *
	 * @var bool
	 */
	public $hasSubElements = false;

	/**
	 * Method to set the element id
	 *
	 * @param   int $id element ID number
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
	 * @param   array $data          To pre-populate element with
	 * @param   int   $repeatCounter Repeat group counter
	 *
	 * @return  string    elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$values   = $this->getSubOptionValues();
		$labels   = $this->getSubOptionLabels();
		$selected = (array) $this->getValue($data, $repeatCounter);
		$i        = 0;
		$to       = array();
		$from     = array();

		foreach ($values as $v)
		{
			if (!in_array($v, $selected))
			{
				$from[$v] = $labels[$i];
			}

			$i++;
		}

		$i      = 0;
		$lookup = array_flip($values);

		foreach ($selected as $v)
		{
			if ($v == '' || $v == '-' || $v == '[""]')
			{
				continue;
			}

			$k      = FArrayHelper::getValue($lookup, $v);
			$tmpTxt = addslashes(htmlspecialchars(FArrayHelper::getValue($labels, $k)));
			$to[$v] = $tmpTxt;
			$i++;
		}

		if (!$this->isEditable())
		{
			return implode(', ', $to);
		}

		FabrikHelperHTML::stylesheet(COM_FABRIK_LIVESITE . 'plugins/fabrik_element/picklist/picklist.css');

		$layout                   = $this->getLayout('form');
		$layoutData               = new stdClass;
		$layoutData->id           = $this->getHTMLId($repeatCounter);
		$layoutData->errorCSS     = $this->elementError != '' ? ' elementErrorHighlight' : '';
		$layoutData->from         = $from;
		$layoutData->to           = $to;
		$layoutData->name         = $this->getHTMLName($repeatCounter);
		$layoutData->value        = json_encode($selected);
		$layoutData->addOptionsUi = $this->getAddOptionFields($repeatCounter);

		return $layout->render($layoutData);
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int $repeatCounter Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$id               = $this->getHTMLId($repeatCounter);
		$data             = $this->getFormModel()->data;
		$params           = $this->getParams();
		$opts             = $this->getElementJSOptions($repeatCounter);
		$opts->allowadd   = (bool) $params->get('allowadd', false);
		$opts->defaultVal = $this->getValue($data, $repeatCounter);

		$opts->hovercolour   = $params->get('picklist-hovercolour', '#AFFFFD');
		$opts->bghovercolour = $params->get('picklist-bghovercolour', '#FFFFDF');
		JText::script('PLG_ELEMENT_PICKLIST_ENTER_VALUE_LABEL');

		return array('FbPicklist', $id, $opts);
	}

	/**
	 * if the search value isn't what is stored in the database, but rather what the user
	 * sees then switch from the search string to the db value here
	 * overwritten in things like checkbox and radio plugins
	 *
	 * @param   string $value FilterVal
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
	 * Builds an array containing the filters value and condition
	 *
	 * @param   string $value     Initial value
	 * @param   string $condition Intial $condition
	 * @param   string $eval      How the value should be handled
	 *
	 * @return  array    (value condition)
	 */

	public function getFilterValue($value, $condition, $eval)
	{
		$value  = $this->prepareFilterVal($value);
		$return = parent::getFilterValue($value, $condition, $eval);

		return $return;
	}

	/**
	 * Does the element consider the data to be empty
	 * Used in isempty validation rule
	 *
	 * @param   array $data          data to test against
	 * @param   int   $repeatCounter repeat group #
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
