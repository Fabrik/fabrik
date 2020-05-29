<?php
/**
 * Array helper class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Helpers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \stdClass;

/**
 * Array helper class
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @since       3.0
 */

class ArrayHelper
{
	/**
	 * Get a value from a nested array
	 *
	 * @param   array   $array         Data to search
	 * @param   string  $key           Search key, use key.dot.format to get nested value
	 * @param   string  $default       Default value if key not found
	 * @param   bool    $allowObjects  Should objects found in $array be converted into arrays
	 *
	 * @return  mixed
	 */

	public static function getNestedValue($array, $key, $default = null, $allowObjects = false)
	{
		$keys = explode('.', $key);

		foreach ($keys as $key)
		{
			if (is_object($array) && $allowObjects)
			{
				$array = self::fromObject($array);
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
				// avoid PHP error if property name is empty
				if ($k !== '')
				{
					if (is_array($v) && $recurse)
					{
						$obj->$k = self::toObject($v, $class);
					}
					else
					{
						$obj->$k = $v;
					}
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

	/**
	 * Array is empty, or only has one entry which itself is an empty string
	 *
	 * @param   array  $array            The array to test
	 * @param   bool   $even_emptierish  If true, use empty() to test single key, if false only count empty string or null as empty
	 *
	 * @since 3.0.8
	 *
	 * @return  bool  is array empty(ish)
	 */

	public static function emptyIsh($array, $even_emptierish = false)
	{
		if (empty($array))
		{
			return true;
		}

		if (count($array) > 1)
		{
			return false;
		}

		$val = self::getValue($array, self::firstKey($array), '');

		return  $even_emptierish ? empty($val) : $val === '' || !isset($val);
	}

	/**
	 * Workaround for J! 3.4 change in FArrayHelper::getValue(), which now forces $array to be, well, an array.
	 * We've been a bit naughty and using it for things like SimpleXMLElement.  So for J! 3.4 release, 2/25/2015,
	 * globally replaced all use of JArrayHelper::getValue() with FArrayHelper::getValue().  This code is just a
	 * copy of the J! code, it just doesn't specify "array $array".
	 *
	 * @param   array   &$array   A named array
	 * @param   string  $name     The key to search for
	 * @param   mixed   $default  The default value to give if no key found
	 * @param   string  $type     Return type for the variable (INT, FLOAT, STRING, WORD, BOOLEAN, ARRAY)
	 *
	 * @return  mixed  The value from the source array
	 */

	public static function getValue(&$array, $name, $default = null, $type = '')
	{
		if (is_object($array))
		{
			$array = self::fromObject($array);
		}

		$result = null;

		if (!is_scalar($name))
		{
			return $default;
		}

		if (isset($array[$name]))
		{
			$result = $array[$name];
		}

		// Handle the default case
		if (is_null($result))
		{
			$result = $default;
		}

		// Handle the type constraint
		switch (strtoupper($type))
		{
			case 'INT':
			case 'INTEGER':
				// Only use the first integer value
				@preg_match('/-?[0-9]+/', $result, $matches);
				$result = @(int) $matches[0];
				break;

			case 'FLOAT':
			case 'DOUBLE':
				// Only use the first floating point value
				@preg_match('/-?[0-9]+(\.[0-9]+)?/', $result, $matches);
				$result = @(float) $matches[0];
				break;

			case 'BOOL':
			case 'BOOLEAN':
				$result = (bool) $result;
				break;

			case 'ARRAY':
				if (!is_array($result))
				{
					$result = array($result);
				}
				break;

			case 'STRING':
				$result = (string) $result;
				break;

			case 'WORD':
				$result = (string) preg_replace('#\W#', '', $result);
				break;

			case 'NONE':
			default:
				// No casting necessary
				break;
		}

		return $result;
	}

	/**
	 *
	 * Wrapper for srray_fill, 'cos PHP <5.6 tosses a warning if $num is not positive,
	 * and we often call it with 0 length
	 *
	 * @param   int    $start_index
	 * @param   int    $num
	 * @param   mixed  $value
	 *
	 * @return  array
	 */
	public static function array_fill($start_index, $num, $value)
	{
		if ($num > 0)
		{
			return array_fill($start_index, $num, $value);
		}
		else
		{
			return array();
		}
	}

    /**
     * Utility function to map an object to an array
     *
     * @param   object   $p_obj    The source object
     * @param   boolean  $recurse  True to recurse through multi-level objects
     * @param   string   $regex    An optional regular expression to match on field names
     *
     * @return  array
     *
     * @since   1.0
     */
    public static function fromObject($p_obj, $recurse = true, $regex = null)
    {
        if (is_object($p_obj) || is_array($p_obj))
        {
            return self::arrayFromObject($p_obj, $recurse, $regex);
        }

        return array();
    }

    /**
     * Utility function to map an object or array to an array
     *
     * @param   mixed    $item     The source object or array
     * @param   boolean  $recurse  True to recurse through multi-level objects
     * @param   string   $regex    An optional regular expression to match on field names
     *
     * @return  array
     *
     * @since   1.0
     */
    private static function arrayFromObject($item, $recurse, $regex)
    {
        if (is_object($item))
        {
            $result = array();

            foreach (get_object_vars($item) as $k => $v)
            {
                if (!$regex || preg_match($regex, $k))
                {
                    if ($recurse)
                    {
                        $result[$k] = self::arrayFromObject($v, $recurse, $regex);
                    }
                    else
                    {
                        $result[$k] = $v;
                    }
                }
            }

            return $result;
        }

        if (is_array($item))
        {
            $result = array();

            foreach ($item as $k => $v)
            {
                $result[$k] = self::arrayFromObject($v, $recurse, $regex);
            }

            return $result;
        }

        return $item;
    }

	/**
	 * Wrapper for standard array_chunk that allows for flipping a grid.  So without flipping, chunking ...
	 *
	 * 1, 2, 3, 4, 5
	 *
	 * ... into a chunksize of 2 becomes ...
	 *
	 * 1, 2
	 * 3, 4
	 * 5
	 *
	 * With flipping, it becomes ...
	 *
	 * 1, 4
	 * 2, 5
	 * 3
	 *
	 * This is useful for building Bootstrap style grids from unchunked arrays.
	 *
	 * @param      $array
	 * @param      $cols
	 * @param bool $flip
	 *
	 * @return array
	 *
	 * @since 3.8
	 */
    public static function chunk($array, $cols, $flip = false)
    {
    	$chunked = array_chunk($array, $cols);

    	if ($flip)
	    {
		    $rows = count($chunked);
		    $ridx = 0;
		    $cidx = 0;
		    $flipped = array();

		    foreach($chunked as $row)
		    {
			    foreach($row as $val)
			    {
				    $flipped[$ridx][$cidx] = $val;
				    $ridx++;

				    if($ridx >= $rows)
				    {
					    $cidx++;
					    $ridx = 0;
				    }
			    }
		    }

		    return $flipped;
	    }

	    return $chunked;
    }

}
