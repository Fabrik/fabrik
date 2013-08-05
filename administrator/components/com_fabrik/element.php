<?php
/**
 * Parameter base class
 *
 * @package     Joomla.Platform
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Parameter base class
 *
 * The JElement is the base class for all JElement types
 *
 * @package     Joomla.Platform
 * @subpackage  Parameter
 * @since       11.1
 * @deprecated  Use JForm instead
 */
class JElement extends JObject
{
	/**
	 * Element name
	 *
	 * This has to be set in the final
	 * renderer classes.
	 *
	 * @var    string
	 */
	protected $_name = null;

	/**
	 * Reference to the object that instantiated the element
	 *
	 * @var    object
	 */
	protected $_parent = null;

	public $array_counter = null;

	/**
	 * Constructor
	 * @since   11.1
	 *
	 * @deprecated    12.1
	 */
	public function __construct($parent = null)
	{
		$this->_parent = $parent;
	}

	/**
	 * Get the element name
	 *
	 * @return  string  type of the parameter
	 * @since   11.1
	 *
	 * @deprecated    12.1
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 *
	 * @since   11.1
	 *
	 * @deprecated    12.1
	 */
	public function render(&$xmlElement, $value, $control_name = 'params')
	{
		$name = $xmlElement->attributes('name');
		$label = $xmlElement->attributes('label');
		$descr = $xmlElement->attributes('description');
		//make sure we have a valid label
		$label = $label ? $label : $name;
		$result[0] = $this->fetchTooltip($label, $descr, $xmlElement, $control_name, $name);
		$result[1] = $this->fetchElement($name, $value, $xmlElement, $control_name);
		$result[2] = $descr;
		$result[3] = $label;
		$result[4] = $value;
		$result[5] = $name;

		return $result;
	}

	/**
	 *
	 * @since   11.1
	 *
	 * @deprecated    12.1
	 */
	public function fetchTooltip($label, $description, &$xmlElement, $control_name = '', $name = '')
	{
		$output = '<label id="' . $control_name . $name . '-lbl" for="' . $control_name . $name . '"';
		if ($description)
		{
			$output .= ' class="hasTip" title="' . JText::_($label) . '::' . JText::_($description) . '">';
		}
		else
		{
			$output .= '>';
		}
		$output .= JText::_($label) . '</label>';

		return $output;
	}

	/**
	 *
	 * @since   11.1
	 *
	 * @deprecated    12.1
	 */
	public function fetchElement($name, $value, &$xmlElement, $control_name)
	{

	}

	//////
	// FABRIK SPECIFIC
	/////

	/**
	 * get the elements html name
* @param string $control_name
* @param string $name
	 * @return string html name
	 */
	function getFullName($control_name, $name)
	{
		if (strstr($name, "[]"))
		{
			$name = trim($name, "[]");
			$fullname = $control_name . '[' . $name . "][]";
		}
		else
		{
			$fullname = $control_name . '[' . $name . ']';
		}
		return $fullname;
	}

	/**
	 * get the elements id
* @param string $control_name
* @param string $name
	 * @return string element id
	 */
	function getId($control_name, $name)
	{
		$name = str_replace('[]', '', $name);

		$c = $this->getRepeatCounter();
		if ($c !== false)
		{
			$id = $control_name . $name . '-' . $c;
		}
		else
		{
			$id = $control_name . $name;
		}
		return $id;
	}

	/**
	 * get repeat group counter used for things like element's id
	 * @return int repeat group counter
	 */
	function getRepeatCounter()
	{
		if (isset($this->_parent->_counter_override) && $this->_parent->_counter_override != -1)
		{
			return $this->_parent->_counter_override;
		}
		else
		{
			if ($this->getRepeat())
			{
				if (isset($this->_array_counter))
				{
					//array counter might have been set when rendering viz params
					return $this->_array_counter;
				}
				else
				{
					// element in repeat but no repeat counter override or array counter set so default to 0
					return 0;
				}
			}
			return false;
		}
	}

	/**
	 * is the current element in a repeat group
	 * @return  bool
	 */
	function getRepeat()
	{
		if (isset($this->_parent->_group))
		{
			//funky custom fabrik params (components/com_fabrik/helpers/params)
			//  have had their _group option set
			// in render() method
			$group = $this->_parent->_group;
		}
		else
		{
			$group = $this->_parent->get('_group', '_default');
		}
		return $this->_parent->_xml[$group]->attributes('repeat');
	}
}
