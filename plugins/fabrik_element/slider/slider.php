<?php
/**
 * Plugin element to render mootools slider
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.slider
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render mootools slider
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.slider
 * @since       3.0
 */

class PlgFabrik_ElementSlider extends PlgFabrik_Element
{
	/**
	 * If the element 'Include in search all' option is set to 'default' then this states if the
	 * element should be ignored from search all.
	 * @var bool  True, ignore in extended search all.
	 */
	protected $ignoreSearchAllDefault = true;

	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'INT(%s)';

	/**
	 * Db table field size
	 *
	 * @var string
	 */
	protected $fieldSize = '6';

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
		FabrikHelperHTML::stylesheet(COM_FABRIK_LIVESITE . 'media/com_fabrik/css/slider.css');
		$params = $this->getParams();
		$width = (int) $params->get('slider_width', 250);
		$val = $this->getValue($data, $repeatCounter);

		if (!$this->isEditable())
		{
			return $val;
		}

		$labels = (explode(',', $params->get('slider-labels')));
		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/slider/images/', 'image', 'form', false);

		$layout = $this->getLayout('form');
		$layoutData = new stdClass;
		$layoutData->id = $this->getHTMLId($repeatCounter);;
		$layoutData->name = $this->getHTMLName($repeatCounter);;
		$layoutData->value = $val;
		$layoutData->width = $width;
		$layoutData->j3 = FabrikWorker::j3();
		$layoutData->showNone = $params->get('slider-shownone');
		$layoutData->outSrc = FabrikHelperHTML::image('clear_rating_out.png', 'form', $this->tmpl, array(), true);
		$layoutData->labels = $labels;
		$layoutData->spanWidth = floor(($width - (2 * count($labels))) / count($labels));

		$layoutData->align = array();

		for ($i = 0; $i < count($labels); $i++)
		{
			switch ($i)
			{
				case 0:
					$align = 'left';
					break;
				case count($labels) - 1:
					$align = 'right';
					break;
				case 1:
				default:
					$align = 'center';
					break;
			}

			$layoutData->align[] = $align;
		}

		return $layout->render($layoutData);
	}

	/**
	 * Manipulates posted form data for insertion into database
	 *
	 * @param   mixed  $val   This elements posted form data
	 * @param   array  $data  Posted form data
	 *
	 * @return  mixed
	 */

	public function storeDatabaseFormat($val, $data)
	{
		// If clear button pressed then store as null.
		if ($val == '')
		{
			$val = null;
		}

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
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->steps = (int) $params->get('slider-steps', 100);
		$data = $this->getFormModel()->data;
		$opts->value = $this->getValue($data, $repeatCounter);

		return array('FbSlider', $id, $opts);
	}
}
