<?php
/**
 * Plugin element to render fields
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.field
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

/**
 * Plugin element to render fields
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.field
 * @since       3.0
 */

class PlgFabrik_ElementField extends PlgFabrik_Element
{

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string  $data      Elements data
	 * @param   object  &$thisRow  All the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData($data, &$thisRow)
	{
		$params = $this->getParams();
		$data = FabrikWorker::JSONtoData($data, true);
		$format = $params->get('text_format_string');
		foreach ($data as &$d)
		{
			$d = $this->numberFormat($d);
			if ($format != '')
			{
				$d = sprintf($format, $d);
			}
			if ($params->get('password') == "1")
			{
				$d = str_pad('', JString::strlen($d), '*');
			}
			$this->_guessLinkType($d, $thisRow, 0);
		}
		return parent::renderListData($data, $thisRow);
	}

	/**
	 * Prepares the element data for CSV export
	 *
	 * @param   string  $data      Element data
	 * @param   object  &$thisRow  All the data in the lists current row
	 *
	 * @return  string	Formatted CSV export value
	 */

	public function renderListData_csv($data, &$thisRow)
	{
		$params = $this->getParams();
		$data = $this->numberFormat($data);
		$format = $params->get('text_format_string');
		if ($format != '')
		{
			$data = sprintf($format, $data);
		}
		return $data;
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
	 * @param   array  $data           To preopulate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$element = $this->getElement();
		$bits = $this->inputProperties($repeatCounter);
		/* $$$ rob - not sure why we are setting $data to the form's data
		 * but in table view when getting read only filter value from url filter this
		 * _form_data was not set to no readonly value was returned
		 * added little test to see if the data was actually an array before using it
		 */
		if (is_array($this->_form->_data))
		{
			$data = $this->_form->_data;
		}
		$value = $this->getValue($data, $repeatCounter);

		/* $$$ hugh - if the form just failed validation, number formatted fields will already
		 * be formatted, so we need to un-format them before formatting them!
		 * $$$ rob - well better actually check if we are coming from a failed validation then :)
		 */
		if (JRequest::getCmd('task') == 'form.process')
		{
			$value = $this->unNumberFormat($value);
		}
		$value = $this->numberFormat($value);
		if (!$this->isEditable())
		{
			$this->_guessLinkType($value, $data, $repeatCounter);
			$format = $params->get('text_format_string');
			if ($format != '')
			{
				$value = sprintf($format, $value);
			}
			if ($params->get('password') == "1")
			{
				$value = str_pad('', JString::strlen($value), '*');
			}
			return ($element->hidden == '1') ? "<!-- " . $value . " -->" : $value;
		}

		/* stop "'s from breaking the content out of the field.
		 * $$$ rob below now seemed to set text in field from "test's" to "test&#039;s" when failed validation
		 * so add false flag to ensure its encoded once only
		 * $$$ hugh - the 'double encode' arg was only added in 5.2.3, so this is blowing some sites up
		 */
		if (version_compare(phpversion(), '5.2.3', '<'))
		{
			$bits['value'] = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
		}
		else
		{
			$bits['value'] = htmlspecialchars($value, ENT_COMPAT, 'UTF-8', false);
		}
		$bits['class'] .= ' ' . $params->get('text_format');
		if ($params->get('speech', 0))
		{
			$bits['x-webkit-speech'] = "x-webkit-speech";
		}
		return $this->buildInput('input', $bits);
	}

	/**
	 * Format guess link type
	 *
	 * @param   string  &$value         Original field value
	 * @param   array   $data           Record data
	 * @param   int     $repeatCounter  Repeat counter
	 *
	 * @return  void
	 */

	protected function _guessLinkType(&$value, $data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$guessed = false;
		if ($params->get('guess_linktype') == '1')
		{
			jimport('joomla.mail.helper');
			$target = $this->guessLinkTarget();
			if (JMailHelper::isEmailAddress($value))
			{
				$value = JHTML::_('email.cloak', $value);
				$guessed = true;
			}
			// Changes JF Questiaux
			elseif (JString::stristr($value, 'http'))
			{
				$value = '<a href="' . $value . '"' . $target . '>' . $value . '</a>';
				$guessed = true;
			}
			else
			{
				if (JString::stristr($value, 'www.'))
				{
					$value = '<a href="http://' . $value . '"' . $target . '>' . $value . '</a>';
					$guessed = true;
				}
			}
		}
	}

	/**
	 * Get the guess type link target property
	 *
	 * @return  string
	 */

	protected function guessLinkTarget()
	{
		$params = $this->getParams();
		$target = $params->get('link_target_options', 'default');
		switch ($target)
		{
			default:
				$str = ' target="' . $target . '"';
				break;
			case 'default':
				$str = '';
				break;
			case 'lightbox':
				FabrikHelperHTML::slimbox();
				$str = ' rel="lightbox[]"';
				break;
		}
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
		$opts = $this->getElementJSOptions($repeatCounter);
		return array('FbField', $id, $opts);
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
				$objtype = "VARCHAR(" . $p->get('maxlength', 255) . ")";
				break;
			case 'integer':
				$objtype = "INT(" . $p->get('integer_length', 10) . ")";
				break;
			case 'decimal':
				$total = (int) $p->get('integer_length', 10) + (int) $p->get('decimal_length', 2);
				$objtype = "DECIMAL(" . $total . "," . $p->get('decimal_length', 2) . ")";
				break;
		}
		return $objtype;
	}

	/**
	 * Get Joomfish options
	 *
	 * @deprecated - not supporting joomfish
	 *
	 * @return  array	key=>value options
	 */

	public function getJoomfishOptions()
	{
		$params = $this->getParams();
		$return = array();
		$size = (int) $this->getElement()->width;
		if ($size !== 0)
		{
			$return['length'] = $size;
		}
		$maxlength = (int) $params->get('maxlength');
		if ($maxlength === 0)
		{
			$maxlength = $size;
		}
		if ($params->get('textarea-showmax') && $maxlength !== 0)
		{
			$return['maxlength'] = $maxlength;
		}
		return $return;
	}

	/**
	 * Can the element plugin encrypt data
	 *
	 * @return  bool
	 */

	public function canEncrypt()
	{
		return true;
	}

	/**
	 * Manupulates posted form data for insertion into database
	 *
	 * @param   mixed  $val   This elements posted form data
	 * @param   array  $data  Posted form data
	 *
	 * @return  mixed
	 */

	public function storeDatabaseFormat($val, $data)
	{
		if (is_array($val))
		{
			foreach ($val as $k => $v)
			{
				$val[$k] = $this->_indStoreDatabaseFormat($v);
			}
			$val = implode(GROUPSPLITTER, $val);
		}
		else
		{
			$val = $this->_indStoreDatabaseFormat($val);
		}
		return $val;
	}

	/**
	 * Manupulates individual values posted form data for insertion into database
	 *
	 * @param   string  $val  This elements posted form data
	 *
	 * @return  string
	 */

	protected function _indStoreDatabaseFormat($val)
	{
		return $this->unNumberFormat($val);
	}

	/**
	 * Get the element's cell class
	 *
	 * @since 3.0.4
	 *
	 * @return  string	css classes
	 */

	public function getCellClass()
	{
		$params = $this->getParams();
		$classes = parent::getCellClass();
		$format = $params->get('text_format');
		if ($format == 'decimal' || $format == 'integer')
		{
			$classes .= ' ' . $format;
		}
		return $classes;
	}
}
