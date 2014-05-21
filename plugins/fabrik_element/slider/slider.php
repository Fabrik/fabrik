<?php
/**
 * Plugin element to render mootools slider
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.slider
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
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
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$width = (int) $params->get('slider_width', 250);
		$element = $this->getElement();
		$val = $this->getValue($data, $repeatCounter);

		if (!$this->isEditable())
		{
			return $val;
		}

		$labels = (explode(',', $params->get('slider-labels')));
		$str = array();
		$str[] = '<div id="' . $id . '" class="fabrikSubElementContainer">';
		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/slider/images/', 'image', 'form', false);

		if ($params->get('slider-shownone'))
		{
			if (FabrikWorker::j3())
			{
				$str[] = '<button class="btn btn-mini clearslider pull-left" style="margin-right:10px"><i class="icon-remove"></i></button>';
			}
			else
			{
				$outsrc = FabrikHelperHTML::image('clear_rating_out.png', 'form', $this->tmpl, array(), true);
				$str[] = '<div class="clearslider_cont"><img src="' . $outsrc . '" style="cursor:pointer;padding:3px;" alt="'
					. FText::_('PLG_ELEMENT_SLIDER_CLEAR') . '" class="clearslider" /></div>';
			}
		}

		$str[] = '<div class="slider_cont" style="width:' . $width . 'px;">';
		$str[] = '<div class="fabrikslider-line" style="width:' . $width . 'px">';
		$str[] = '<div class="knob"></div>';
		$str[] = '</div>';

		if (count($labels) > 0 && $labels[0] !== '')
		{
			$spanwidth = floor(($width - (2 * count($labels))) / count($labels));
			$str[] = '<ul class="slider-labels" style="width:' . $width . 'px;">';

			for ($i = 0; $i < count($labels); $i++)
			{
				if ($i == ceil(floor($labels) / 2))
				{
					$align = 'center';
				}

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

				$str[] = '<li style="width:' . $spanwidth . 'px;text-align:' . $align . ';">' . $labels[$i] . '</li>';
			}

			$str[] = '</ul>';
		}

		$str[] = '<input type="hidden" class="fabrikinput" name="' . $name . '" value="' . $val . '" />';
		$str[] = '</div>';
		$str[] = '<span class="slider_output badge badge-info">' . $val . '</span>';
		$str[] = '</div>';

		return implode("\n", $str);
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
