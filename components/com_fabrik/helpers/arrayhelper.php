<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
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
	 * @param   array   $array    to search
	 * @param   string  $key      search key, use key.dot.format to get nested value
	 * @param   string  $default  default value if key not found
	 *
	 *  @return  mixed
	 */

	public static function getNestedValue($array, $key, $default = null)
	{
		$keys = explode('.', $key);
		foreach ($keys as $key)
		{
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

	public function setValue(&$array, $key, $val)
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

	public function filter(&$array, $key, $value)
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

	public function extract($array, $key)
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

	public function firstKey($array)
	{
		reset($array);
		return key($array);
	}

	/**
	 * Iterates over the properties of an object (or array) and returns the sum of the integers, 
	 * lengths of string properties, and sizes of arrays/objects. 
	 *
	 * @param   object $obj an object
	 *
	 * @return array tables
	 */	

	private function getObjectNumber($obj){
		$num = 0;
		foreach($obj as $v){
			if(is_numeric($v)){
				$num += $v;
			}else if(is_string($v)){
				$num += strlen($v);
			}else if(is_array($v)){
				$num += sizeof($v);
			}else if(is_object($v)){
				$num += sizeof(get_object_vars($v));
			}
		}
		return $num;
	}

	/**
	 * Creates a key from the arguments passed to 'getHash', for the purposes of caching the result of that function.
	 * 	 
	 * @param   array $arrayOfObjects an array of objects
	 * @param   string $keyProp object property used as hash index.
	 * @param   string $valueProp object property used as hash value.
	 *
	 * @return array tables
	 */

	private function getHashKey($arrayOfObjects, $keyProp, $valueProp){
		$s1 = sizeof($arrayOfObjects);
		if($s1 == 0){ return ''; }
		
		$index = $s1 - 1;
		$object = $arrayOfObjects[$index];
		$arrayKeys = array_keys(get_object_vars($object));		
		$s2 = sizeof($arrayKeys);

		$hKey =  preg_replace("/[^a-zA-Z]/", "", $arrayKeys[0]) . preg_replace("/[^a-zA-Z]/", "", $arrayKeys[$s2 - 1]);
		$hKey .= ($keyProp === NULL ? '' : $keyProp) . ($valueProp === NULL ? '' : $valueProp);
		$hKey .= $s1 . $s2 . self::getObjectNumber($object);

		return $hKey;
	}

	/**
	 * Create a hash from an array of objects. 
	 * For example, from an array of fabrik connection objects (where each object has the properties 'id', 'host', 'user', etc) 
	 * you could get an associative array where the 'id' property is the index (key), and the 'database' property is the value.
	 *
	 * @param   array $arrayOfObjects an array of objects
	 * @param   string $keyProp object property to use as hash index. If null, returns an indexed array instead
	 * @param   string $valueProp object property to use as hash value. If null, returns a hash of the original objects in $arrayOfObjects, indexed by $keyProp.
	 * @param   boolean $refreshCache If true, the hash object will be re-created (rather than pulled from the cache)
	 *
	 * @return array tables
	 */

	public function getHash($arrayOfObjects, $keyProp = NULL, $valueProp = NULL, $refreshCache = false)
	{
		static $cache;
		$hKey = self::getHashKey($arrayOfObjects, $keyProp, $valueProp);

		if(!$cache[$hKey] || $refreshCache == true){

			if(is_array($arrayOfObjects[0])){
				foreach($arrayOfObjects AS &$arr){
					$arr = self::toObject($arr);
				}
			}			

			$hash = array();

			$s = sizeof($arrayOfObjects);	
			for($x = 0; $x < $s; $x++)
			{
				if($keyProp == NULL)
				{
					$key = $x;

				}else 
				{
					$key = $arrayOfObjects[$x]->$keyProp;
				}			
				
				if($valueProp !== NULL)
				{
					$value = $arrayOfObjects[$x]->$valueProp;

				}else
				{
					$value = $arrayOfObjects[$x];
				}
				
				$hash[$key] = $value;
			}

			$cache[$hKey] = $hash;
		}

		return $cache[$hKey];	
	}
}