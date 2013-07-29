<?php
/**
 * Array helper class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Array helper class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @since       3.0
 */

class FArrayHelper extends JArrayHelper
{

	/**
	 * Get a value from a nested array
	 *
	 * @param   array   $array         Data to search
	 * @param   string  $key           Search key, use key.dot.format to get nested value
	 * @param   string  $default       Default value if key not found
	 * @param   bool    $allowObjects  Should objects found in $array be converted into arrays
	 *
	 *  @return  mixed
	 */

	public static function getNestedValue($array, $key, $default = null, $allowObjects = false)
	{
		$keys = explode('.', $key);
		foreach ($keys as $key)
		{
			if (is_object($array) && $allowObjects)
			{
				$array = JArrayHelper::fromObject($array);
			}
			if (!is_array($array))
			{
				return $default;
			}
			if (array_key_exists($key, $array))
			{
				$array = $array[$key];
			}
			else
			{
				return $default;
			}
		}
		return $array;
	}

	/**
	 * update the data that gets posted via the form and stored by the form
	 * model. Used in elements to modify posted data see fabrikfileupload
	 *
	 * @param   array   &$array  array to set value for
	 * @param   string  $key     (in key.dot.format) to set a recursive array
	 * @param   string  $val     value to set key to
	 *
	 * @return  null
	 */

	public static function setValue(&$array, $key, $val)
	{

		if (strstr($key, '.'))
		{

			$nodes = explode('.', $key);
			$count = count($nodes);
			$pathNodes = $count - 1;
			if ($pathNodes < 0)
			{
				$pathNodes = 0;
			}
			$ns = $array;
			for ($i = 0; $i <= $pathNodes; $i++)
			{
				/**
				 * If any node along the registry path does not exist, create it
				 * if (!isset($this->formData[$nodes[$i]])) { //this messed up for joined data
				 */
				if (!isset($ns[$nodes[$i]]))
				{
					$ns[$nodes[$i]] = array();
				}
				$ns = $ns[$nodes[$i]];
			}
			$ns = $val;

			$ns = $array;
			for ($i = 0; $i <= $pathNodes; $i++)
			{
				/**
				 * If any node along the registry path does not exist, create it
				 * if (!isset($this->formData[$nodes[$i]])) { //this messed up for joined data
				 */
				if (!isset($ns[$nodes[$i]]))
				{
					$ns[$nodes[$i]] = array();
				}
				$ns = $ns[$nodes[$i]];
			}
			$ns = $val;
		}
		else
		{
			$array[$key] = $val;
		}
	}

	/**
	 * Utility function to map an array to a stdClass object.
	 *
	 * @param   array   &$array   The array to map.
	 * @param   string  $class    Name of the class to create
	 * @param   bool    $recurse  into each value and set any arrays to objects
	 *
	 * @return  object	The object mapped from the given array
	 *
	 * @since	1.5
	 */

	public static function toObject(&$array, $class = 'stdClass', $recurse = true)
	{
		$obj = null;
		if (is_array($array))
		{
			$obj = new $class;
			foreach ($array as $k => $v)
			{
				if (is_array($v) && $recurse)
				{
					$obj->$k = JArrayHelper::toObject($v, $class);
				}
				else
				{
					$obj->$k = $v;
				}
			}
		}
		return $obj;
	}

	/**
	 * returns copy of array $ar1 with those entries removed
	 * whose keys appear as keys in any of the other function args
	 *
	 * @param   array  $ar1  first array
	 * @param   array  $ar2  second array
	 *
	 * @return  array
	 */

	public function array_key_diff($ar1, $ar2)
	{
		/**
		 *  , $ar3, $ar4, ...
		 *
		 */
		$aSubtrahends = array_slice(func_get_args(), 1);
		foreach ($ar1 as $key => $val)
		{
			foreach ($aSubtrahends as $aSubtrahend)
			{
				if (array_key_exists($key, $aSubtrahend))
				{
					unset($ar1[$key]);
				}
			}
		}
		return $ar1;
	}

	/**
	 * filters array of objects removing those when key does not match
	 * the value
	 *
	 * @param   array   &$array  of objects - passed by ref
	 * @param   string  $key     to search on
	 * @param   string  $value   of key to keep from array
	 *
	 * @return unknown_type
	 */

	public static function filter(&$array, $key, $value)
	{
		for ($i = count($array) - 1; $i >= 0; $i--)
		{
			if ($array[$i]->$key !== $value)
			{
				unset($array[$i]);
			}
		}
	}

	/**
	 * get the first object in an array whose key = value
	 *
	 * @param   array   $array  of objects
	 * @param   string  $key    to search on
	 * @param   string  $value  to search on
	 *
	 * @return  mixed  value or false
	 */

	public function get($array, $key, $value)
	{
		for ($i = count($array) - 1; $i >= 0; $i--)
		{
			if ($array[$i]->$key == $value)
			{
				return $array[$i];
			}
		}
		return false;
	}

	/**
	 * Extract an array of single property values from an array of objects
	 *
	 * @param   array   $array  the array of objects to search
	 * @param   string  $key    the key to extract the values on.
	 *
	 * @return  array of single key values
	 */

	public static function extract($array, $key)
	{
		$return = array();
		foreach ($array as $object)
		{
			$return[] = $object->$key;
		}
		return $return;
	}

	/**
	 * Returns first key in an array, used if we aren't sure if array is assoc or
	 * not, and just want the first row.
	 *
	 * @param   array  $array  the array to get the first key for
	 *
	 * @since	3.0.6
	 *
	 * @return  string  the first array key.
	 */

	public static function firstKey($array)
	{
		reset($array);
		return key($array);
	}

}
