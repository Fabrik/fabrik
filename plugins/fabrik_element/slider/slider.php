<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.slider
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render mootools slider
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.slider
 */

class plgFabrik_ElementSlider extends plgFabrik_Element
{

	protected $fieldDesc = 'INT(%s)';

	protected $fieldSize = '6';

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
		return parent::renderListData($data, $thisRow);
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
		FabrikHelperHTML::stylesheet(COM_FABRIK_LIVESITE . 'media/com_fabrik/css/slider.css');
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$params = $this->getParams();
		$width = (int) $params->get('slider_width', 250);
		$element = $this->getElement();
		$val = $this->getValue($data, $repeatCounter);
		if (!$this->_editable)
		{
			return $val;
		}
		$labels = array_filter(explode(',', $params->get('slider-labels')));
		$str = array();
		$str[] = '<div id="' . $id . '" class="fabrikSubElementContainer">';

		FabrikHelperHTML::addPath(COM_FABRIK_BASE . 'plugins/fabrik_element/slider/images/', 'image', 'form', false);
		$outsrc = FabrikHelperHTML::image('clear_rating_out.png', 'form', $this->tmpl, array(), true);
		if ($params->get('slider-shownone'))
		{
			$str[] = '<div class="clearslider_cont"><img src="' . $outsrc . '" style="cursor:pointer;padding:3px;" alt="'
				. JText::_('PLG_ELEMENT_SLIDER_CLEAR') . '" class="clearslider" /></div>';
		}
		$str[] = '<div class="slider_cont" style="width:' . $width . 'px;">';
		if (count($labels) > 0)
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
					case 1:
					default:
						$align = 'center';
						break;
					case count($labels) - 1:
						$align = 'right';
						break;
				}
				$str[] = '<li style="width:' . $spanwidth . 'px;text-align:' . $align . ';">' . $labels[$i] . '</li>';
			}
			$str[] = '</ul>';
		}
		$str[] = '<div class="fabrikslider-line" style="width:' . $width . 'px">';
		$str[] = '<div class="knob"></div>';
		$str[] = '</div>';
		$str[] = '<input type="hidden" class="fabrikinput" name="' . $name . '" value="' . $val . '" />';
		$str[] = '<div class="slider_output">' . $val . '</div>';
		$str[] = '</div>';
		$str[] = '</div>';
		return implode("\n", $str);
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @param	int		repeat counter
	 * @return string 	javascript to create instance
	 */

	function elementJavascript($repeatCounter)
	{
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->steps = (int) $params->get('slider-steps', 100);
		$data = $this->_form->_data;
		$opts->value = $this->getValue($data, $repeatCounter);
		$opts = json_encode($opts);
		return "new FbSlider('$id', $opts)";
	}

}
?>