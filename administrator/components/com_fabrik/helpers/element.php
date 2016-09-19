<?php
/**
 * JHtml element helper
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * JHtml element helper
 *
 * @package  Fabrik
 * @since    3.0
 */

class FabrikAdminElementHelper
{
	/**
	 * Get html id
	 *
	 * @param   object  $element       field
	 * @param   string  $control_name  control name
	 * @param   string  $name          name
	 *
	 * @return string
	 */

	public static function getId($element, $control_name, $name)
	{
		if (method_exists($element, 'getId'))
		{
			$id = $element->getId($control_name, $name);
		}
		else
		{
			$id = "$control_name.$name";
		}

		return $id;
	}

	/**
	 * Get full name
	 *
	 * @param   object  $element       field
	 * @param   string  $control_name  control name
	 * @param   string  $name          name
	 *
	 * @return string
	 */

	public static function getFullName($element, $control_name, $name)
	{
		if (method_exists($element, 'getFullName'))
		{
			$fullName = $element->getFullName($control_name, $name);
		}
		else
		{
			$fullName = $control_name . '[' . $name . ']';
		}

		return $fullName;
	}

	/**
	 * Get repeat counter
	 *
	 * @param   object  $element  Jhtml field
	 *
	 * @return mixed
	 */

	public static function getRepeatCounter($element)
	{
		if (method_exists($element, 'getRepeatCounter'))
		{
			$c = $element->getRepeatCounter();
		}
		else
		{
			$c = false;
		}

		return $c;
	}

	/**
	 * Get repeat
	 *
	 * @param   object  $element  Jhtml field
	 *
	 * @return mixed
	 */

	public static function getRepeat($element)
	{
		if (method_exists($element, 'getRepeat'))
		{
			$c = $element->getRepeat();
		}
		else
		{
			$c = 0;
		}

		return $c;
	}
}
