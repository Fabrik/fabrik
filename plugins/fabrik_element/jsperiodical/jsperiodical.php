<?php
/**
 * Fabrik JS-Periodical - run JS every x ms
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.jsperiodical
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element: js periodical will fire a JavaScript function at a definable interval
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.jsperiodical
 * @since       3.0
 */

class PlgFabrik_ElementJSPeriodical extends PlgFabrik_Element
{
	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string    $data      Elements data
	 * @param   stdClass  &$thisRow  All the data in the lists current row
	 * @param   array     $opts      Rendering options
	 *
	 * @return  string	formatted value
	 */
	public function renderListData($data, stdClass &$thisRow, $opts = array())
	{
        $profiler = JProfiler::getInstance('Application');
        JDEBUG ? $profiler->mark("renderListData: {$this->element->plugin}: start: {$this->element->name}") : null;

        $params = $this->getParams();
		$format = $params->get('text_format_string');
		$format_blank = $params->get('field_format_string_blank', true);

		if ($format != '' && ($format_blank || $d != ''))
		{
			$str = sprintf($format, $data);
			// ToDo - No idea why eval is here but not in similar code in field.php (Sophist)
			$data = eval($str);
		}

		return parent::renderListData($data, $thisRow, $opts);
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
		$layout = $this->getLayout('form');
		$layoutData = new stdClass;
		$layoutData->attributes = $this->inputProperties($repeatCounter);;
		$layoutData->value = $this->getValue($data, $repeatCounter);;
		$layoutData->isEditable = $this->isEditable();
		$layoutData->hidden = $this->getElement()->hidden  == '1';

		return $layout->render($layoutData);
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
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->code = $params->get('jsperiod_code');
		$opts->period = $params->get('jsperiod_period');

		return array('FbJSPeriodical', $id, $opts);
	}

	/**
	 * Get database field description
	 *
	 * @return  string  db field type
	 */

	public function getFieldDescription()
	{
		$p = $this->getParams();

		if ($this->encryptMe())
		{
			return 'BLOB';
		}

		switch ($p->get('text_format'))
		{
			case 'text':
			default:
				$objtype = "VARCHAR(255)";
				break;
			case 'integer':
				$objtype = "INT(" . $p->get('integer_length', 10) . ")";
				break;
			case 'decimal':
				$objtype = "DECIMAL(" . $p->get('integer_length', 10) . "," . $p->get('decimal_length', 2) . ")";
				break;
		}

		return $objtype;
	}
}
