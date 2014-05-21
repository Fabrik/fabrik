<?php
/**
 * Plugin element to render internal id
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.internalid
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

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
	 * @param   array  $data           to pre-populate element with
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
		$value = stripslashes($value);

		if (!$this->isEditable())
		{
			return ($element->hidden == '1') ? "<!-- " . $value . " -->" : $value;
		}

		return $this->getHiddenField($name, $value, $id, 'fabrikinput inputbox hidden');
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

	public function isHidden()
	{
		return true;
	}

	/**
	 * Load a new set of default properties and params for the element
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
