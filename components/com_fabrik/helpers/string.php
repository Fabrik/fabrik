<?php
/**
 * String helpers
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * String helpers
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @since       3.0
 */

class FabrikString extends JString
{

	/**
	 * UTF-8 aware - replace the first word
	 *
	 * @param   string  $str         The string to be trimmed
	 * @param   string  $word        The word to trim
	 * @param   string  $whitespace  Ignore but preserve leading whitespace
	 *
	 * @return  string  the trimmed string
	 */

	public static function ltrimword($str, $word = false, $whitespace = false)
	{
		if ($word === false)
		{
			return $str;
		}
		if ($whitespace)
		{
			$word = preg_quote($word, '#');
			$str = preg_replace("#^(\s*)($word)(.*)#i", "$1$3", $str);
		}
		else
		{
			$pos = JString::strpos($str, $word);
			if ($pos === 0)
			{
				$str = JString::substr($str, JString::strlen($word));
			}
		}
		return $str;
	}

	/**
	 * Right trim a word from a string
	 *
	 * @param   string  &$str  The string to be trimmed
	 * @param   string  $word  The word to trim
	 *
	 * @return  string  the trimmed string
	 */

	public static function rtrimword(&$str, $word = false)
	{
		$l = JString::strlen($word);
		$end = JString::substr($str, -$l);
		if ($end === $word)
		{
			return JString::substr($str, 0, JString::strlen($str) - $l);
		}
		else
		{
			return $str;
		}
	}

	/**
	 * UTF-8 aware - remove the first word
	 * CASE INSENSETIVE
	 *
	 * @param   string  $str   The string to be trimmed
	 * @param   string  $word  The word to trim
	 *
	 * @return  string  the trimmed string
	 */

	public static function ltrimiword($str, $word = false)
	{
		$pos = stripos($str, $word);
		if ($pos === 0)
		{
			$str = JString::substr($str, JString::strlen($word));
		}
		return $str;
	}

	/**
	 * Formats a string to return a safe db col name - eg
	 * table.field is returned as `table`.field`
	 * table is return as `table`
	 *
	 * @param   string  $col  Col name to format
	 *
	 * @return string in `table`.field` format
	 */

	public static function safeColName($col)
	{
		$db = FabrikWorker::getDbo();
		$col = str_replace('`', '', $col);
		$splitter = '';
		if (strstr($col, '___'))
		{
			$splitter = '___';
		}
		if (strstr($col, '.'))
		{
			$splitter = '.';
		}
		if ($splitter == '')
		{
			return $db->quoteName($col);
		}
		if (strstr($col, $splitter))
		{
			$col = explode($splitter, $col);
			foreach ($col as &$c)
			{
				$c = $db->quoteName($c);
			}
			return implode('.', $col);
		}
		return $col;
	}

	/**
	 * Inverse of safeColName takes `table`.`field`
	 * and returns table___field
	 *
	 * @param   string  $col  In `table`.`field` format
	 *
	 * @return  string  in table___field format
	 */

	public static function safeColNameToArrayKey($col)
	{
		$col = str_replace(array("`.`", "."), '___', $col);
		$col = str_replace("`", "", $col);
		return $col;
	}

	/**
	 * Takes tablename.element or tablename___elementname
	 * (with or without quotes) and returns elementname
	 *
	 * @param   string  $col  Column name to shorten
	 *
	 * @return  string  element name
	 */

	public static function shortColName($col)
	{
		if (strstr($col, '.'))
		{
			$bits = explode('.', $col);
			$col = array_pop($bits);
		}
		elseif (strstr($col, '___'))
		{
			$bits = explode('___', $col);
			$col = array_pop($bits);
		}
		$col = str_replace("`", "", $col);
		return $col;
	}

	/**
	 * Get a shortened version of the element label - so that the admin pages
	 * don't get too stretched when we populate dropdowns with the label
	 *
	 * @param   string  $label  Complete element label
	 *
	 * @return  string  shortened element label
	 */

	public static function getShortDdLabel($label)
	{
		$label = strip_tags($label);
		preg_replace('/<[a-z][a-z0-9]*[^<>]*>/', '', $label);
		if (JString::strlen($label) > 50)
		{
			$label = JString::substr($label, 0, 47) . '...';
		}
		$label = trim($label);
		return $label;
	}

	/**
	 * Santize db fields names, can't just do regex on A-Z as languages like Chinese should be allowed
	 *
	 * @param   string  $str  Field name
	 *
	 * @since   3.0.7
	 *
	 * @return  string
	 */

	public static function dbFieldName($str)
	{
		$name = JFilterInput::getInstance()->clean($str, 'CMD');

		// Chinese characters?
		if ($name === '')
		{
			$name = str_replace(array(' ', '.', '-'), '', $str);
		}
		return $name;
	}

	/**
	 * Clean variable names for use as fabrik element names
	 * whitespace compressed and replaced with '_'
	 * replace all non-alphanumeric chars except _ and - with '_'
	 * 28/06/2011 replaces umlauts with eu
	 * 22/11/2011 added IGNORE to default enc otherwise iconv chops everything after first unconvertable char
	 * 05/02/2012 changed name to iclean, removed strtolower() and added clean() as wrapper that does strtolower
	 *
	 * @param   string  $str      To clean
	 * @param   string  $fromEnc  From encoding
	 * @param   string  $toEnc    To encoding
	 *
	 * @return  string  cleaned
	 */

	public static function iclean($str, $fromEnc = "UTF-8", $toEnc = "ASCII//IGNORE//TRANSLIT")
	{
		// Replace umlauts
		$out = '';
		for ($i = 0; $i < JString::strlen($str); $i++)
		{
			$ch = ord($str{$i});
			switch ($ch)
			{
				case 195:
					$out .= "";
					break;
				case 164:
					$out .= "ae";
					break;
				case 188:
					$out .= "ue";
					break;
				case 182:
					$out .= "oe";
					break;
				case 132:
					$out .= "Ae";
					break;
				case 156:
					$out .= "Ue";
					break;
				case 150:
					$out .= "Oe";
					break;

				// Fix for cleaning value of 1
				case 0:
					$out = '1';
					break;
				default:
					$out .= chr($ch);
			}
		}
		$str = $out;
		if (function_exists('iconv'))
		{
			/* $$$ rob added @ incase its farsi which creates a notice:
			 * https://github.com/Fabrik/fabrik/issues/72
			 */

			// Replace accented characters with ascii equivalent e.g. é => e
			$str1 = (@iconv($fromEnc, $toEnc, $str));
			if($str1) {
				$str = $str1;
			}
			$str = (str_replace("'", '', $str));
		}
		// Compress internal whitespace and replace with _
		$str = preg_replace('/\s+/', '_', $str);

		// Replace all non-alphanumeric chars except _ and - with '_'
		return preg_replace('/\W+/', '_', $str);
	}

	/**
	 * Wrapper for iclean(), that does strtolower on output of clean()
	 *
	 * @param   string  $str      To clean
	 * @param   string  $fromEnc  From encoding
	 * @param   string  $toEnc    To encoding
	 *
	 * @return  string  cleaned
	 */

	public static function clean($str, $fromEnc = "UTF-8", $toEnc = "ASCII//IGNORE//TRANSLIT")
	{
		return JString::strtolower(self::iclean($str, $fromEnc, $toEnc));
	}

	/**
	 * Truncate text possibly setting a tip to show all of the text
	 *
	 * @param   string  $text  text to truncate
	 * @param   array   $opts  optional options array
	 *
	 * @return  string
	 */

	public static function truncate($text, $opts = array())
	{
		$text = htmlspecialchars(strip_tags($text), ENT_QUOTES);
		$orig = $text;
		$wordCount = JArrayHelper::getValue($opts, 'wordcount', 10);
		$showTip = JArrayHelper::getValue($opts, 'tip', true);
		$title = JArrayHelper::getValue($opts, 'title', "");
		$text = explode(' ', $text);
		$summary = array_slice($text, 0, $wordCount);

		if (count($text) > $wordCount)
		{
			$summary[] = " ...";
		}
		$summary = implode(' ', $summary);
		if ($showTip && count($text) > $wordCount)
		{
			FabrikHelperHTML::tips();
			if ($title !== '')
			{
				$title .= "::";
			}
			$tip = htmlspecialchars('<div class="truncate_text">' . $title . $orig . '</div>');
			$jOpts = new stdClass;
			$jOpts->notice = true;
			$jOpts->position = JArrayHelper::getValue($opts, 'position', 'top');
			$jOpts = json_encode($jOpts);
			$summary = '<span class="fabrikTip" opts=\'' . $jOpts . '\' title="' . $tip . '">' . $summary . '</span>';
		}
		return $summary;
	}

	/**
	 * Removes a querystring key from a url/queyrstring
	 *
	 * @param   string  $url  Or querystring
	 * @param   string  $key  To remove
	 *
	 * @return  string  url/querystring
	 */

	public static function removeQSVar($url, $key)
	{
		$pair = explode('?', $url);
		if (count($pair) === 2)
		{
			$url = $pair[0];
			$bits = JArrayHelper::getValue($pair, 1);
		}
		else
		{
			$url = '';
			$bits = JArrayHelper::getValue($pair, 0);
		}
		$glue = strstr($bits, '&amp;') ? '&amp;' : '&';
		$bits = explode($glue, $bits);
		$a = array();
		foreach ($bits as $bit)
		{
			if (strstr($bit, '='))
			{
				list($thisKey, $val) = explode('=', $bit);
				if ($thisKey !== $key)
				{
					$a[] = $bit;
				}
			}
		}
		if (!empty($a))
		{
			$url .= '?' . implode($glue, $a);
		}
		return $url;
	}

	/**
	 * Takes a complete URL, and urlencodes any query string args
	 *
	 * @param   string  $url  To encode
	 *
	 * @return  encoded url
	 */

	public static function encodeurl($url)
	{
		if (strstr($url, '?'))
		{
			list($site, $qs) = explode('?', $url);
			if (!empty($qs))
			{
				$new_qs = array();
				foreach (explode('&', $qs) as $arg)
				{
					$bits = explode('=', $arg);
					$key = JArrayHelper::getValue($bits, 0, '');
					$val = JArrayHelper::getValue($bits, 1, '');
					$new_qs[] = $key . "=" . urlencode($val);
				}
				$url = $site . "?" . implode("&", $new_qs);
			}
		}
		if (strstr($url, '{'))
		{
			/* $$$ hugh special case for some Google URL's that use encoded JSON objects in the path part of the URL
			 * so we need to re-encode {, }, " and :.  Except of course for the : in http(s):.
			 */
			list($http, $rest) = explode(':', $url, 2);
			if (!empty($rest))
			{
				$patterns = array('#\{#', '#\}#', '#"#', '#\\\\#', '#:#');
				$replacements = array('%7B', '%7D', '%22', '%5C', '%3A');
				$rest = preg_replace($patterns, $replacements, $rest);
				$url = $http . ':' . $rest;
			}
		}
		return $url;
	}

	/**
	 * Prepare a string for presentation in html.
	 *
	 * @param   string  &$string  To convert for html
	 *
	 * @return  void
	 */

	public static function forHtml(&$string)
	{
		// Special chars such as <>
		$string = htmlspecialchars($string, ENT_QUOTES);

		// Show umlauts correctly in ajax error messages.
		$string = mb_convert_encoding($string, 'HTML-ENTITIES', "UTF-8");
	}

	/**
	 * See if it looks like a string uses {table___element} placeholders
	 * Doesn't do any sanity testing to see if it's actually a valid element
	 * name, just goes by pattern patching word___word
	 *
	 * @param   string  $str  String to test
	 *
	 * @return   bool
	 *
	 * @since   3.0.1
	 */

	public static function usesElementPlaceholders($str)
	{
		return preg_match("#\{\w+___\w+\}#", $str);
	}

	/**
	 * Convert standard Fabrik coords string into lat, long, zoom object.
	 * Copied from map element, as we end up needing this elsewhere.
	 *
	 * @param   string  $v          coordinates
	 * @param   int     $zoomlevel  default zoom level
	 *
	 * @return  object  coords array and zoomlevel int
	 */

	public static function mapStrToCoords($v, $zoomlevel = 0)
	{
		$o = new stdClass;
		$o->coords = array('', '');
		$o->zoomlevel = (int) $zoomlevel;
		if (strstr($v, ","))
		{
			$ar = explode(":", $v);
			$o->zoomlevel = count($ar) == 2 ? array_pop($ar) : 4;
			$v = self::ltrimword($ar[0], "(");
			$v = rtrim($v, ")");
			$v = str_replace(' ', '', $v);
			$o->coords = explode(",", $v);
		}
		else
		{
			$o->coords = array(0, 0);
		}
		// $$$ hugh - added these as I always think it's what they are!
		$o->lat = $o->coords[0];
		$o->long = $o->coords[1];
		$o->zoom = $o->zoomlevel;
		return $o;
	}

}
