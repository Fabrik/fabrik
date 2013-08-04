<?php
/**
 * Fabrik JS-Periodical - run JS every x ms
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.jsperiodical
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
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
 */

class PlgFabrik_ElementJSPeriodical extends PlgFabrik_Element
{
	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string  $data      elements data
	 * @param   object  &$thisRow  all the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData($data, &$thisRow)
	{
		$params = $this->getParams();
		$format = $params->get('text_format_string');
		if ($format != '')
		{
			$str = sprintf($format, $data);
			$data = eval($str);
		}
		return parent::renderListData($data, $thisRow);
	}

	/**
	 * Determines if the element can contain data used in sending receipts,
	 * e.g. fabrikfield returns true
	 *
	 * @deprecated - not used
	 *
	 * @return  bool
	 */

	public function isReceiptElement()
	{
		return true;
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
		$params = $this->getParams();
		$element = $this->getElement();
		$size = $element->width;
		$maxlength = $params->get('maxlength', 0);
		if ((int) $maxlength === 0)
		{
			$maxlength = $size;
		}

		$value = $this->getValue($data, $repeatCounter);
		$type = "text";
		if ($this->elementError != '')
		{
			$type .= " elementErrorHighlight";
		}
		if ($element->hidden == '1')
		{
			$type = "hidden";
		}
		$sizeInfo = " size=\"$size\" maxlength=\"$maxlength\"";
		if (!$this->isEditable())
		{
			$format = $params->get('text_format_string');
			if ($format != '')
			{
				$value = eval(sprintf($format, $value));
			}
			if ($element->hidden == '1')
			{
				return "<!--" . $value . "-->";
			}
			else
			{
				return $value;
			}
		}

		$str = "<input class=\"fabrikinput inputbox $type\" type=\"$type\" name=\"$name\" id=\"$id\" $sizeInfo value=\"$value\" />\n";
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
