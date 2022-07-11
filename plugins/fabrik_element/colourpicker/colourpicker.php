<?php
/**
 * Colour Picker Element
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.colourpicker
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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
	 * @param   string   $data     Elements data
	 * @param   stdClass &$thisRow All the data in the lists current row
	 * @param   array    $opts     Rendering options
	 *
	 * @return  string    formatted value
	 */
	public function renderListData($data, stdClass &$thisRow, $opts = array())
	{
        $profiler = JProfiler::getInstance('Application');
        JDEBUG ? $profiler->mark("renderListData: {$this->element->plugin}: start: {$this->element->name}") : null;

        $data              = FabrikWorker::JSONtoData($data, true);
		$layout            = $this->getLayout('list');
		$displayData       = new stdClass;
		$displayData->data = $data;

		return $layout->render($displayData);
	}

	/**
	 * Manipulates posted form data for insertion into database
	 *
	 * @param   mixed $val  This elements posted form data
	 * @param   array $data Posted form data
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
	 * @param   int $repeatCounter Repeat group counter
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
		$id     = $this->getHTMLId($repeatCounter);
		$data   = $this->getFormModel()->data;
		$value  = $this->getValue($data, $repeatCounter);

		if ($value == 'none')
		{
			$value = '';
		}

		$vars = explode(",", $value);
		$vars = array_pad($vars, 3, 0);
		$opts = $this->getElementJSOptions($repeatCounter);
		$c    = new stdClass;

		// 14/06/2011 changed over to color param object from ind colour settings
		$c->red                 = (int) $vars[0];
		$c->green               = (int) $vars[1];
		$c->blue                = (int) $vars[2];
		$opts->colour           = $c;
		$opts->value            = $vars;
		$opts->showPicker       = (bool) $params->get('show_picker', 1);
		$opts->swatchSizeWidth  = $params->get('swatch_size_width', '10px');
		$opts->swatchSizeHeight = $params->get('swatch_size_height', '10px');
		$opts->swatchWidth      = $params->get('swatch_width', '160px');

		$swatch       = $params->get('colourpicker-swatch', 'default.js');
		$swatchFile   = JPATH_SITE . '/plugins/fabrik_element/colourpicker/swatches/' . $swatch;
		$opts->swatch = json_decode(file_get_contents($swatchFile));

		return array('ColourPicker', $id, $opts);
	}

	/**
	 * Determines the value for the element in the form view. Ensure its set to be a r,g,b string
	 *
	 * @param   array $data          Form data
	 * @param   int   $repeatCounter When repeating joined groups we need to know what part of the array to access
	 * @param   array $opts          Options, 'raw' = 1/0 use raw value
	 *
	 * @return  string    value
	 */
	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		$value = parent::getValue($data, $repeatCounter, $opts);
		$value = strstr($value, '#') ? FabrikString::hex2rgb($value) : $value;

		return $value;
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
		$value  = $this->getValue($data, $repeatCounter);
		$params = $this->getParams();

		$layout          = $this->getLayout('form');
		$displayData     = new stdClass;
		$displayData->id = $this->getHTMLId($repeatCounter);;
		$displayData->name = $this->getHTMLName($repeatCounter);;
		$displayData->value      = $value;
		$displayData->editable   = $this->isEditable();
		$displayData->j3         = FabrikWorker::j3();
		$displayData->showPicker = (bool) $params->get('show_picker', 1);

		return $layout->render($displayData);
	}

	/**
	 * Get database field description
	 *
	 * @return  string  Bb field type
	 */
	public function getFieldDescription()
	{
		if ($this->encryptMe())
		{
			return 'BLOB';
		}

		return 'VARCHAR(30)';
	}
}
