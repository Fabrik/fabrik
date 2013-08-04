<?php
/**
 * Colour Picker Element
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.colourpicker
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Plugin element to render colour picker
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.colourpicker
 * @since       3.0
 */

class PlgFabrik_ElementColourpicker extends PlgFabrik_Element
{

	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'CHAR(%s)';

	/**
	 * Db table field size
	 *
	 * @var string
	 */
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

	public function storeDatabaseFormat($val, $data)
	{
		$val = parent::storeDatabaseFormat($val, $data);
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
		if (!$this->isEditable())
		{
			return array();
		}
		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/colourpicker/images/', 'image', 'form', false);
		$params = $this->getParams();
		$element = $this->getElement();
		$id = $this->getHTMLId($repeatCounter);
		$data = $this->getFormModel()->data;
		$value = $this->getValue($data, $repeatCounter);
		$vars = explode(",", $value);
		$vars = array_pad($vars, 3, 0);
		$opts = $this->getElementJSOptions($repeatCounter);
		$c = new stdClass;

		// 14/06/2011 changed over to color param object from ind colour settings
		$c->red = (int) $vars[0];
		$c->green = (int) $vars[1];
		$c->blue = (int) $vars[2];
		$opts->colour = $c;
		$opts->value = $vars;
		$swatch = $params->get('colourpicker-swatch', 'default.js');
		$swatchFile = JPATH_SITE . '/plugins/fabrik_element/colourpicker/swatches/' . $swatch;
		$opts->swatch = json_decode(file_get_contents($swatchFile));

		return array('ColourPicker', $id, $opts);
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
		$value = $this->getValue($data, $repeatCounter);
		$str = array();
		$str[] = '<div class="fabrikSubElementContainer">';
		$str[] = '<input type="hidden" name="' . $name . '" id="' . $id
			. '" /><div class="colourpicker_bgoutput img-rounded " style="float:left;width:25px;height:25px;background-color:rgb('
			. $value . ')"></div>';
		if ($this->isEditable())
		{
			$str[] = '<div class="colourPickerBackground colourpicker-widget fabrikWindow" style="display:none;min-width:350px;min-height:250px;">';
			$str[] = '<div class="draggable modal-header">';
			$str[] = '<div class="colourpicker_output img-rounded" style="width:15px;height:15px;float:left;margin-right:10px;"></div> ';
			$str[] = JText::_('PLG_FABRIK_COLOURPICKER_COLOUR');

			if (FabrikWorker::j3())
			{
				$str[] = '<a class="pull-right" href="#"><i class="icon-cancel "></i></a>';
			}
			else
			{
				$str[] = FabrikHelperHTML::image("close.gif", 'form', @$this->tmpl, array());
			}
			$str[] = '</div>';

			$str[] = '<div class="itemContentPadder">';
			$str[] = '<div class="row-fluid">';
			$str[] = '  <div class="span7">';
			$str[] = '    <ul class="nav nav-tabs">';
			$str[] = '      <li class="active"><a href="#' . $name . '-picker" data-toggle="tab">' . JText::_('PLG_FABRIK_COLOURPICKER_PICKER') . '</a></li>';
			$str[] = '      <li><a href="#' . $name . '-swatch" data-toggle="tab">' . JText::_('PLG_FABRIK_COLOURPICKER_SWATCH') . '</a></li>';
			$str[] = '    </ul>';
			$str[] = '    <div class="tab-content">';
			$str[] = '      <div class="tab-pane active" id="' . $name . '-picker"></div>';
			$str[] = '      <div class="tab-pane" id="' . $name . '-swatch"></div>';
			$str[] = '    </div>';
			$str[] = '  </div>';
			$str[] = '  <div class="span5 sliders" style="margin-top:50px">';
			$str[] = '  </div>';

			$str[] = '</div>';
			$str[] = '</div>';
			$str[] = '</div>';
		}
		$str[] = '</div>';
		return implode("\n", $str);
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
		return "VARCHAR(30)";
	}

}
