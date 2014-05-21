<?php
/**
 * Plugin element to render folder list
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.folder
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render folder list
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.folder
 * @since       3.0
 */

class PlgFabrik_ElementFolder extends PlgFabrik_Element
{
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
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
		$params = $this->getParams();
		$allowAdd = $params->get('allow_frontend_addtodropdown', false);
		$selected = $this->getValue($data, $repeatCounter);
		$errorCSS = $this->elementError != '' ? " elementErrorHighlight" : '';
		$attribs = 'class="fabrikinput inputbox' . $errorCSS . "\"";
		$aRoValues = array();
		$path = JPATH_ROOT . '/' . $params->get('fbfolder_path');
		$opts = array();

		if ($params->get('folder_allownone', true))
		{
			$opts[] = JHTML::_('select.option', '', FText::_('NONE'));
		}

		if ($params->get('folder_listfolders', true))
		{
			$folders = JFolder::folders($path);

			foreach ($folders as $folder)
			{
				$opts[] = JHTML::_('select.option', $folder, $folder);

				if (is_array($selected) and in_array($folder, $selected))
				{
					$aRoValues[] = $folder;
				}
			}
		}

		if ($params->get('folder_listfiles', false))
		{
			$files = JFolder::files($path);

			foreach ($files as $file)
			{
				$opts[] = JHTML::_('select.option', $file, $file);

				if (is_array($selected) and in_array($file, $selected))
				{
					$aRoValues[] = $file;
				}
			}
		}

		$str = JHTML::_('select.genericlist', $opts, $name, $attribs, 'value', 'text', $selected, $id);

		if (!$this->isEditable())
		{
			return implode(', ', $aRoValues);
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
		$params = $this->getParams();
		$element = $this->getElement();
		$data = $this->getFormModel()->data;
		$arSelected = $this->getValue($data, $repeatCounter);
		$path = JPATH_ROOT . '/' . $params->get('fbfbfolder_path');
		$folders = JFolder::folders($path);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->defaultVal = $element->default;
		$opts->data = $folders;

		return array('FbFolder', $id, $opts);
	}
}
