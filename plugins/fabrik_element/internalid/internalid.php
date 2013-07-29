<?php
/**
 * Plugin element to render internal id
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.internalid
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Plugin element to render internal id
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.internalid
 * @since       3.0
 */

class PlgFabrik_ElementInternalid extends PlgFabrik_Element
{

	/**
	 * If the element 'Include in search all' option is set to 'default' then this states if the
	 * element should be ignored from search all.
	 * @var bool  True, ignore in extended search all.
	 */
	protected $ignoreSearchAllDefault = true;

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
		$value = $this->getValue($data, $repeatCounter);
		if (!$this->isEditable())
		{
			return ($element->hidden == '1') ? "<!-- " . stripslashes($value) . " -->" : stripslashes($value);
		}
		$value = stripslashes($value);
		return $this->getHiddenField($name, $value, $id, 'inputbox hidden fabrikinput');
	}

	/**
	 * Get database field description
	 *
	 * @return  string  db field type
	 */

	public function getFieldDescription()
	{
		return "INT(11) NOT NULL AUTO_INCREMENT";
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
		return array('FbInternalId', $id, $opts);
	}

	/**
	 * Is the element hidden or not - if not set then return false
	 *
	 * @return  bool
	 */

	protected function isHidden()
	{
		return true;
	}

	/**
	 * Load a new set of default properites and params for the element
	 *
	 * @return  object	element (id = 0)
	 */

	public function getDefaultProperties()
	{
		$item = parent::getDefaultProperties();
		$item->primary_key = true;
		$item->width = 3;
		$item->hidden = 1;
		$item->auto_increment = 1;
		return $item;
	}

}
