<?php
/**
 * Render an embedded spotify player
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.spotify
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Render a spotify player in an iframe
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.spotify
 * @since       3.0
 */
class PlgFabrik_ElementSpotify extends PlgFabrik_Element
{
	protected $pluginName = 'spotify';

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

        return $this->constructPlayer($data, 'list');
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
		$input = $this->app->input;
		$params = $this->getParams();
		$element = $this->getElement();
		$data = $this->getFormModel()->data;
		$value = $this->getValue($data, $repeatCounter);

		if ($input->get('view') != 'details')
		{
			$name = $this->getHTMLName($repeatCounter);
			$id = $this->getHTMLId($repeatCounter);
			$size = $params->get('width');
			$maxLength = 255;
			$bits = array();
			$type = "text";

			if ($this->elementError != '')
			{
				$type .= " elementErrorHighlight";
			}

			if (!$this->isEditable())
			{
				return ($element->hidden == '1') ? '<!-- ' . $value . ' -->' : $value;
			}

			$bits['class'] = "fabrikinput inputbox $type";
			$bits['type'] = $type;
			$bits['name'] = $name;
			$bits['id'] = $id;

			// Stop "'s from breaking the content out of the field.
			$bits['value'] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
			$bits['size'] = $size;
			$bits['maxlength'] = $maxLength;

			$layout = $this->getLayout('form');
			$layoutData = new stdClass;
			$layoutData->attributes = $bits;

			return $layout->render($layoutData);
		}
		else
		{
			return $this->constructPlayer($value);
		}
	}

	/**
	 * Make player
	 *
	 * @param   string  $value  Value
	 * @param   string  $mode   Mode form/list
	 *
	 * @return string
	 */
	private function constructPlayer($value, $mode = 'form')
	{
		$params = $this->getParams();
		$width = (int) $params->get('width');
		$width = min($width, 640);
		$width = max($width, 250);

		$height = (int) $params->get('height');
		$height = min($height, 720);
		$height = max($height, 80);

		$opts = array();
		$src = $value;
		$src .= '&theme=' . $params->get('theme', 'black');
		$src .= '&view=' . $params->get('view', 'list');

		$opts[] = 'src="https://embed.spotify.com/?uri=' . $src . '"';
		$opts[] = 'width="' . $width . '"';
		$opts[] = 'height="' . $height . '"';

		$layout = $this->getLayout('player');
		$layoutData = new stdClass;
		$layoutData->attributes = $opts;

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
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);

		return array('FbSpotify', $id, $opts);
	}
}
