<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.colourpicker
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Plugin element to render colour picker
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.colourpicker
 */

class plgFabrik_ElementColourpicker extends plgFabrik_Element
{

	protected $fieldDesc = 'CHAR(%s)';

	protected $fieldSize = '10';

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
		$data = FabrikWorker::JSONtoData($data, true);
		$str = '';
		foreach ($data as $d)
		{
			$str .= '<div style="width:15px;height:15px;background-color:rgb(' . $d . ')"></div>';
		}
		return $str;
	}

	/**
	 * Manupulates posted form data for insertion into database
	 *
	 * @param   mixed  $val   this elements posted form data
	 * @param   array  $data  posted form data
	 *
	 * @return  mixed
	 */

	function storeDatabaseFormat($val, $data)
	{
		$val = parent::storeDatabaseFormat($val, $data);
		return $val;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  string
	 */

	function elementJavascript($repeatCounter)
	{
		if (!$this->_editable)
		{
			return;
		}
		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/colourpicker/images/', 'image', 'form', false);
		$params = $this->getParams();
		$element = $this->getElement();
		$id = $this->getHTMLId($repeatCounter);
		$data = $this->_form->_data;
		$value = $this->getValue($data, $repeatCounter);
		$vars = explode(",", $value);
		$vars = array_pad($vars, 3, 0);
		$opts = $this->getElementJSOptions($repeatCounter);
		$c = new stdClass();
		// 14/06/2011 changed over to color param object from ind colour settings
		$c->red = (int) $vars[0];
		$c->green = (int) $vars[1];
		$c->blue = (int) $vars[2];
		$opts->colour = $c;
		$swatch = $params->get('colourpicker-swatch', 'default.js');
		$swatchFile = JPATH_SITE . '/plugins/fabrik_element/colourpicker/swatches/' . $swatch;
		$opts->swatch = json_decode(JFile::read($swatchFile));

		$opts->closeImage = FabrikHelperHTML::image("close.gif", 'form', @$this->tmpl, array(), true);
		$opts->handleImage = FabrikHelperHTML::image("handle.gif", 'form', @$this->tmpl, array(), true);
		$opts->trackImage = FabrikHelperHTML::image("track.gif", 'form', @$this->tmpl, array(), true);

		$opts = json_encode($opts);
		return "new ColourPicker('$id', $opts)";
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to preopulate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */

	function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$value = $this->getValue($data, $repeatCounter);
		$str = array();
		$str[] = '<div class="fabrikSubElementContainer">';
		$str[] = '<input type="hidden" name="' . $name . '" id="' . $id
			. '" /><div class="colourpicker_bgoutput" style="float:left;width:20px;height:20px;border:1px solid #333333;background-color:rgb('
			. $value . ')"></div>';
		if ($this->_editable)
		{
			$str[] = '<div class="colourPickerBackground colourpicker-widget" style="color:#000;z-index:99999;left:200px;background-color:#EEEEEE;border:1px solid #333333;width:390px;padding:0 0 5px 0;"></div>';
		}
		$str[] = '</div>';
		return implode("\n", $str);
	}

	/**
	 * Get database field description
	 *
	 * @return  string  db field type
	 */
	function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe())
		{
			return 'BLOB';
		}
		return "VARCHAR(30)";
	}

}
