<?php
/**
 * Plugin element to Sugar CRM id
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.sugarid
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to Sugar CRM id
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.sugarid
 * @since       3.0
 */

class PlgFabrik_ElementSugarid extends PlgFabrik_Element
{

	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'CHAR(%s)';

	/**
	 * Db table field size
	 *
	 * @var string
	 */
	protected $fieldSize = '36';

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To preopulate element with
	 * @param   int    $repeatCounter  Repeat group counter
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
		$type = "hidden";
		if ($this->elementError != '')
		{
			$type .= " elementErrorHighlight";
		}
		if (!$this->isEditable())
		{
			return "<!--" . stripslashes($value) . "-->";
		}
		$hidden = 'hidden';
		$guid = $this->create_guid();
		/* no need to eval here as its done before hand i think ! */
		if ($element->eval == "1" and !isset($data[$name]))
		{
			$str = "<input class=\"inputbox $type\" type=\"$hidden\" name=\"$name\" id=\"$id\" $sizeInfo value=\"$value\" />\n";
		}
		else
		{
			$value = stripslashes($value);
			$str = "<input class=\"inputbox fabrikinput $type\" type=\"$hidden\" name=\"$name\" id=\"$id\" value=\"$value\" />\n";
		}
		return $str;
	}

	/**
	 * Manupulates posted form data for insertion into database
	 *
	 * @param   mixed  $val   This elements posted form data
	 * @param   array  $data  Posted form data
	 *
	 * @return  mixed
	 */

	public function storeDatabaseFormat($val, $data)
	{
		if (trim($val) == '')
		{
			$val = $this->create_guid();
		}
		return $val;
	}

	/**
	 * A temporary method of generating GUIDs of the correct format for our DB.
	 *
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
	 * All Rights Reserved.
	 * Contributor(s): ______________________________________..
	 *
	 * @return String contianing a GUID in the format: aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee
	 */

	protected function create_guid()
	{
		$microTime = microtime();
		list($a_dec, $a_sec) = explode(' ', $microTime);

		$dec_hex = dechex($a_dec * 1000000);
		$sec_hex = dechex($a_sec);

		$this->ensure_length($dec_hex, 5);
		$this->ensure_length($sec_hex, 6);

		$guid = "";
		$guid .= $dec_hex;
		$guid .= $this->create_guid_section(3);
		$guid .= '-';
		$guid .= $this->create_guid_section(4);
		$guid .= '-';
		$guid .= $this->create_guid_section(4);
		$guid .= '-';
		$guid .= $this->create_guid_section(4);
		$guid .= '-';
		$guid .= $sec_hex;
		$guid .= $this->create_guid_section(6);
		return $guid;
	}

	/**
	 * Create guid section
	 *
	 * @param   string  $characters  String
	 *
	 * @return  string  guid section
	 */

	protected function create_guid_section($characters)
	{
		$return = "";
		for ($i = 0; $i < $characters; $i++)
		{
			$return .= dechex(mt_rand(0, 15));
		}
		return $return;
	}

	/**
	 * pad/substr string to specified length
	 *
	 * @param   string  &$string  String
	 * @param   int     $length   Size
	 *
	 * @return  void
	 */

	protected function ensure_length(&$string, $length)
	{
		$strlen = JString::strlen($string);
		if ($strlen < $length)
		{
			$string = str_pad($string, $length, "0");
		}
		elseif ($strlen > $length)
		{
			$string = JString::substr($string, 0, $length);
		}
	}
}
