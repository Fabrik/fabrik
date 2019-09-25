<?php
/**
 * Generic tools that all models use
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Helpers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\FCipher;
use DateTime;
use Exception;
use FabTable;
use JAccess;
use JCache;
use JComponentHelper;
use JCrypt;
use JCryptCipherSimple;
use JCryptKey;
use JDatabaseDriver;
use JFactory;
use JFile;
use JFilterInput;
use JForm;
use JHtml;
use JLanguageHelper;
use JLanguageMultilang;
use JLog;
use JMail;
use JMailHelper;
use JModelLegacy;
use Joomla\CMS\Application\CMSApplication;
use JPath;
use JSession;
use JTable;
use JUri;
use JVersion;
use RuntimeException;

/**
 * Generic tools that all models use
 * This code used to be in models/parent.php
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @since       3.0
 */
class Worker
{
	/**
	 * Fabrik database objects
	 *
	 * @var  JDatabaseDriver[]
	 */
	public static $database = null;

	/**
	 * Fabrik db connections
	 *
	 * @var  array
	 */
	public static $connection = null;

	/**
	 * Plugin manager
	 *
	 * @var  object
	 */
	public static $pluginManager = null;

	/**
	 * Strtotime final date format
	 *
	 * @var  string
	 */
	static protected $finalFormat = null;

	/**
	 * Add slashes in parse message
	 *
	 * @var bool
	 */
	protected $parseAddSlashes = false;

	/**
	 * Search data to replace placeholders
	 *
	 * @var array
	 */
	protected $_searchData = array();

	/**
	 * Get array of valid view types
	 *
	 * @return  array
	 */
	public static function getViewTypes()
	{
		return array(
			'article',
			'cron',
			'csv',
			'details',
			'element',
			'form',
			'list',
			'package',
			'visualization'
		);
	}

	/**
	 * Returns true if $view is a valid view type
	 *
	 * @param   string $view View type
	 *
	 * @return    bool
	 */
	public static function isViewType($view)
	{
		$view      = strtolower(trim($view));
		$viewTypes = self::getViewTypes();

		return in_array($view, $viewTypes);
	}

	/**
	 * Returns true if $file has an image extension type
	 *
	 * @param   string $file Filename
	 *
	 * @return    bool
	 */
	public static function isImageExtension($file)
	{
		$path_parts = pathinfo($file);

		if (array_key_exists('extension', $path_parts))
		{
			$image_extensions_eregi = 'bmp|gif|jpg|jpeg|png';

			return preg_match('/' . $image_extensions_eregi . '/i', $path_parts['extension']) > 0;
		}

		return false;
	}

	/**
	 * Get audio mime type array, keyed by file extension
	 *
	 * @return array
	 */
	public static function getAudioMimeTypes()
	{
		return array(
			'mp3' => 'audio/x-mpeg',
			'm4a' => 'audio/x-m4a'
		);
	}

    /**
     * Get audio mime type array, keyed by file extension
     *
     * @return array
     */
    public static function getImageMimeTypes()
    {
        return array(
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'bmp'  => 'image/bmp',
            'webp' => 'image/webp'
        );
    }

	/**
	 * Get document mime type array, keyed by file extension
	 *
	 * @return array
	 */
	public static function getDocMimeTypes()
	{
		return array(
			'pdf' => 'application/pdf',
			'epub' => 'document/x-epub'
		);
	}

	/**
	 * Get video mime type array, keyed by file extension
	 *
	 * @return array
	 */
	public static function getVideoMimeTypes()
	{
		return array(
			'mp4' => 'video/mp4',
			'm4v' => 'video/x-m4v',
			'mov' => 'video/quicktime'
		);
	}

	/**
	 * Get Audio Mime type
	 *
	 * @param   string $file Filename
	 *
	 * @return  bool|string
	 */
	public static function getAudioMimeType($file)
	{
		$path_parts = pathinfo($file);
        $types = self::getAudioMimeTypes();

		return ArrayHelper::getValue(
		    $types,
            ArrayHelper::getValue($path_parts, 'extension', ''),
            false
        );
	}

    /**
     * Get Audio Mime type
     *
     * @param   string $file Filename
     *
     * @return  bool|string
     */
    public static function getImageMimeType($file)
    {
        $path_parts       = pathinfo($file);
        $types = self::getImageMimeTypes();

        return ArrayHelper::getValue(
            $types,
            ArrayHelper::getValue($path_parts, 'extension', ''),
            false
        );
    }

	/**
	 * Get Video Mime type
	 *
	 * @param   string $file Filename
	 *
	 * @return  bool|string
	 */
	public static function getVideoMimeType($file)
	{
		$path_parts       = pathinfo($file);
        $types = self::getVideoMimeTypes();

        return ArrayHelper::getValue(
            $types,
            ArrayHelper::getValue($path_parts, 'extension', ''),
            false
        );
	}

	/**
	 * Get Doc Mime type
	 *
	 * @param   string $file Filename
	 *
	 * @return  bool|string
	 */
	public static function getDocMimeType($file)
	{
		$path_parts     = pathinfo($file);
        $types = self::getDocMimeTypes();

        return ArrayHelper::getValue(
            $types,
            ArrayHelper::getValue($path_parts, 'extension', ''),
            false
        );
    }

	/**
	 * Get Podcast Mime type
	 *
	 * @param   string $file Filename
	 *
	 * @return  bool|string
	 */
	public static function getPodcastMimeType($file)
	{
		$mime_type        = false;

		if ($mime_type = self::getVideoMimeType($file))
		{
			return $mime_type;
		}
		elseif ($mime_type = self::getAudioMimeType($file))
		{
			return $mime_type;
		}
		elseif ($mime_type = self::getDocMimeType($file))
		{
			return $mime_type;
		}
        elseif ($mime_type = self::getImageMimeType($file))
        {
            return $mime_type;
        }

		return $mime_type;
	}

	/**
	 * Format a string to datetime
	 *
	 * http://fr.php.net/strftime
	 * (use as strftime)
	 *
	 * @param   string $date   String date to format
	 * @param   string $format Date format strftime format
	 *
	 * @return    array|void    date info
	 */
	public static function strToDateTime($date, $format)
	{
		$weekdays = array('Sun' => '0', 'Mon' => '1', 'Tue' => '2', 'Wed' => '3', 'Thu' => '4', 'Fri' => '5', 'Sat' => '6');

		if (!($date = self::str2Time($date, $format)))
		{
			return;
		}

		$shortMonths = array(Text::_('Jan'), Text::_('Feb'), Text::_('Mar'), Text::_('Apr'), Text::_('May'), Text::_('Jun'), Text::_('Jul'),
			Text::_('Aug'), Text::_('Sept'), Text::_('Oct'), Text::_('Nov'), Text::_('Dec'));

		/*$$ rob set day default to 1, so that if you have a date format string of %m-%Y the day is set to the first day of the month
		 * and not the last day of the previous month (which is what a 0 here would represent)
		 */
		$dateTime = array('sec' => 0, 'min' => 0, 'hour' => 0, 'day' => 1, 'mon' => 0, 'year' => 0, 'timestamp' => 0);

		foreach ($date as $key => $val)
		{
			switch ($key)
			{
				case 'd':
				case 'e':
				case 'j':
					$dateTime['day'] = intval($val);
					break;
				case 'D':
					$dateTime['day'] = intval($weekdays[$val]);
					break;
				case 'm':
				case 'n':
					$dateTime['mon'] = intval($val);
					break;
				case 'b':
					$dateTime['mon'] = $shortMonths[$val] + 1;
					break;
				case 'Y':
					$dateTime['year'] = intval($val);
					break;
				case 'y':
					$dateTime['year'] = intval($val) + 2000;
					break;
				case 'G':
				case 'g':
				case 'H':
				case 'h':
					$dateTime['hour'] = intval($val);
					break;
				case 'M':
					$dateTime['min'] = intval($val);
					break;
				case 'i':
					$dateTime['min'] = intval($val);
					break;
				case 's':
				case 'S':
					$dateTime['sec'] = intval($val);
					break;
			}
		}

		$dateTime['timestamp'] = mktime($dateTime['hour'], $dateTime['min'], $dateTime['sec'], $dateTime['mon'], $dateTime['day'], $dateTime['year']);

		return $dateTime;
	}

	/**
	 * Check for, and convert, any 'special' formats for strtotime, like 'yesterday', etc.
	 *
	 * @param   string $date Date to check
	 * @param   bool   $gmt  Set date to universal time?
	 *
	 * @return    string    date
	 */
	public static function specialStrToMySQL($date, $gmt = true)
	{
		/**
		 * $$$ hugh - if date is empty, just return today's date
		 */
		if (empty($date))
		{
			$d    = JFactory::getDate();
			$date = $d->toSql(!$gmt);

			return $date;
		}

		/**
		 * lets check if we have some special text as per :
		 * http://php.net/strtotime - this means we can use "+2 week" as a url filter
		 * do this before we urldecode the date otherwise the + is replaced with ' ';
		 */

		$matches  = array();
		$matches2 = array();
		$matches3 = array();

		// E.g. now
		preg_match("/(now|ago|midnight|yesterday|today)/i", $date, $matches);

		// E.g. +2 Week
		preg_match("/[+|-][0-9]* (week\b|year\b|day\b|month\b)/i", $date, $matches2);

		// E.g. next Wednesday
		preg_match("/[next|last]* (monday\b|tuesday\b|wednesday\b|thursday\b|friday\b|saturday\b|sunday\b)/i", $date, $matches3);
		$matches = array_merge($matches, $matches2, $matches3);

		if (!empty($matches))
		{
			$d    = JFactory::getDate($date);
			$date = $d->toSql(!$gmt);
		}

		return $date;
	}

	/**
	 * String to time
	 *
	 * @param   string $date   Date representation
	 * @param   string $format Date format
	 *
	 * @return    array    date bits keyed on date representations e.g.  m/d/Y
	 */
	public static function str2Time($date, $format)
	{
		/**
		 * lets check if we have some special text as per :
		 * http://php.net/strtotime - this means we can use "+2 week" as a url filter
		 * do this before we urldecode the date otherwise the + is replaced with ' ';
		 */
		$matches  = array();
		$matches2 = array();
		$matches3 = array();

		// E.g. now
		preg_match("/[now|ago|midnight|yesterday|today]/i", $date, $matches);

		// E.g. +2 Week
		preg_match("/[+|-][0-9]* (week\b|year\b|day\b|month\b)/i", $date, $matches2);

		// E.g. next Wednesday
		preg_match("/[next|last]* (monday\b|tuesday\b|wednesday\b|thursday\b|friday\b|saturday\b|sunday\b)/i", $date, $matches3);
		$matches = array_merge($matches, $matches2, $matches3);

		if (!empty($matches))
		{
			$d    = JFactory::getDate($date);
			$date = $d->format($format);
		}

		/* $$$ - hugh : urldecode (useful when ajax calls, may need better fix)
		 * as per http://fabrikar.com/forums/showthread.php?p=43314#post43314
		 */
		$date = urldecode($date);

		// Strip any textual date representations from the string
		$days = array('%A', '%a');

		foreach ($days as $day)
		{
			if (strstr($format, $day))
			{
				$format = str_replace($day, '', $format);
				$date   = self::stripDay($date, $day == '%a' ? true : false);
			}
		}

		$months = array('%B', '%b', '%h');

		foreach ($months as $month)
		{
			if (strstr($format, $month))
			{
				$format = str_replace($month, '%m', $format);
				$date   = self::monthToInt($date, $month == '%B' ? false : true);
			}
		}
		// @TODO: some of these aren't right for strftime
		self::$finalFormat = $format;
		$search            = array('%d', '%e', '%D', '%j', '%m', '%b', '%Y', '%y', '%g', '%H', '%h', '%i', '%s', '%S', '%M');

		$replace = array('(\d{2})', '(\d{1,2})', '(\w{3})', '(\d{1,2})', '(\d{2})', '(\w{3})', '(\d{4})', '(\d{2})', '(\d{1,2})', '(\d{2})',
			'(\d{2})', '(\d{2})', '(\d{2})', '(\d{2})', '(\d{2})');

		$pattern = str_replace($search, $replace, $format);

		if (!preg_match("#$pattern#", $date, $matches))
		{
			// Lets allow for partial date formats - e.g. just the date and ignore the time
			$format = explode('%', $format);

			if (empty($format))
			{
				// No format left to test so return false
				return false;
			}

			array_pop($format);
			$format            = trim(implode('%', $format));
			self::$finalFormat = $format;

			return self::str2Time($date, $format);
		}

		$dp = $matches;

		if (!preg_match_all('#%(\w)#', $format, $matches))
		{
			return false;
		}

		$id = $matches['1'];

		if (count($dp) != count($id) + 1)
		{
			return false;
		}

		$ret = array();

		for ($i = 0, $j = count($id); $i < $j; $i++)
		{
			$ret[$id[$i]] = $dp[$i + 1];
		}

		return $ret;
	}

	/**
	 * Removed day of week name from string
	 *
	 * @param   string $date The string date
	 * @param   bool   $abrv Abbreviated day?
	 *
	 * @return    string    date
	 */
	public static function stripDay($date, $abrv = false)
	{
		if ($abrv)
		{
			$date = str_replace(Text::_('SUN'), '', $date);
			$date = str_replace(Text::_('MON'), '', $date);
			$date = str_replace(Text::_('TUE'), '', $date);
			$date = str_replace(Text::_('WED'), '', $date);
			$date = str_replace(Text::_('THU'), '', $date);
			$date = str_replace(Text::_('FRI'), '', $date);
			$date = str_replace(Text::_('SAT'), '', $date);
		}
		else
		{
			$date = str_replace(Text::_('SUNDAY'), '', $date);
			$date = str_replace(Text::_('MONDAY'), '', $date);
			$date = str_replace(Text::_('TUESDAY'), '', $date);
			$date = str_replace(Text::_('WEDNESDAY'), '', $date);
			$date = str_replace(Text::_('THURSDAY'), '', $date);
			$date = str_replace(Text::_('FRIDAY'), '', $date);
			$date = str_replace(Text::_('SATURDAY'), '', $date);
		}

		return $date;
	}

	/**
	 * Convert a month (could be in any language) into the month number (1 = jan)
	 *
	 * @param   string $date Data to convert
	 * @param   bool   $abrv Is the month is a short or full name version
	 *
	 * @return  string
	 */
	public static function monthToInt($date, $abrv = false)
	{
		if ($abrv)
		{
			$date = str_replace(Text::_('JANUARY_SHORT'), '01', $date);
			$date = str_replace(Text::_('FEBRUARY_SHORT'), '02', $date);
			$date = str_replace(Text::_('MARCH_SHORT'), '03', $date);
			$date = str_replace(Text::_('APRIL_SHORT'), '04', $date);
			$date = str_replace(Text::_('MAY_SHORT'), '05', $date);
			$date = str_replace(Text::_('JUNE_SHORT'), '06', $date);
			$date = str_replace(Text::_('JULY_SHORT'), '07', $date);
			$date = str_replace(Text::_('AUGUST_SHORT'), '08', $date);
			$date = str_replace(Text::_('SEPTEMBER_SHORT'), '09', $date);
			$date = str_replace(Text::_('OCTOBER_SHORT'), 10, $date);
			$date = str_replace(Text::_('NOVEMBER_SHORT'), 11, $date);
			$date = str_replace(Text::_('DECEMBER_SHORT'), 12, $date);
		}
		else
		{
			$date = str_replace(Text::_('JANUARY'), '01', $date);
			$date = str_replace(Text::_('FEBRUARY'), '02', $date);
			$date = str_replace(Text::_('MARCH'), '03', $date);
			$date = str_replace(Text::_('APRIL'), '04', $date);
			$date = str_replace(Text::_('MAY'), '05', $date);
			$date = str_replace(Text::_('JUNE'), '06', $date);
			$date = str_replace(Text::_('JULY'), '07', $date);
			$date = str_replace(Text::_('AUGUST'), '08', $date);
			$date = str_replace(Text::_('SEPTEMBER'), '09', $date);
			$date = str_replace(Text::_('OCTOBER'), 10, $date);
			$date = str_replace(Text::_('NOVEMBER'), 11, $date);
			$date = str_replace(Text::_('DECEMBER'), 12, $date);
		}

		return $date;
	}

	/**
	 * Check a string is not reserved by Fabrik
	 *
	 * @param   string $str    To check
	 * @param   bool   $strict Include things like rowid, listid in the reserved words, defaults to true
	 *
	 * @return bool
	 */
	public static function isReserved($str, $strict = true)
	{
		$reservedWords = array("task", "view", "layout", "option", "formid", "submit", "ul_max_file_size"
		, "ul_file_types", "ul_directory", 'adddropdownvalue', 'adddropdownlabel', 'ul_end_dir');
		/*
		 * $$$ hugh - a little arbitrary, but need to be able to exclude these so people can create lists from things like
		 * log files, which include field names like rowid and itemid.  So when saving an element, we now set strict mode
		 * to false if it's not a new element.
		 */
		$strictWords = array("listid", 'rowid', 'itemid');

		if ($strict)
		{
			$reservedWords = array_merge($reservedWords, $strictWords);
		}

		if (in_array(StringHelper::strtolower($str), $reservedWords))
		{
			return true;
		}

		return false;
	}

	/**
	 * Check a string is valid to use as an element name
	 *
	 * @param   string $str    To check
	 * @param   bool   $strict Include things like rowid, listid in the reserved words, defaults to true
	 *
	 * @return bool
	 */
	public static function validElementName($str, $strict = true)
	{
		// check if it's a Fabrik reserved word
		if (self::isReserved($str, $strict))
		{
			return false;
		}

		// check valid MySQL - start with letter or _, then only alphanumeric or underscore
		if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $str))
		{
			return false;
		}

		// check for various other gotchas, like ending in _raw, starting with more than one _, etc.
		if (preg_match('/^submit|^__|_raw$/', $str))
		{
			return false;
		}

		return true;
	}

	/**
	 * Get the crypt object
	 *
	 * @param  string  type  type of encryption (aes, crypt or simple)
	 *
	 * @since  3.1
	 *
	 * @return  Fabrik\Helpers\FCipher
	 */
	public static function getCrypt($type = 'simple')
	{
		return new FCipher($type);
	}

	/**
	 * Special case placeholder handling for repeat data. When something (usually an element plugin) is doing
	 * replacements for elements which are in the "same" repeat group, almost always they will want
	 * the value for the same repeat instance, not a comma seperated list of all the values.  So (say)
	 * the upload element is creating a file path, for an upload element in a repeat group, of ...
	 * '/uploads/{repeat_table___userid}/', and there are 4 repeat instance, it doesn't want a path of ...
	 * '/uploads/34,45,94,103/', it just wants the one value from the same repeat count as the upload
	 * element.  Or a calc element doing "return '{repeat_table___first_name} {repeat_table___last_name}';".  Etc.
	 *
	 * Rather than make this a part of parseMessageForPlaceHolder, for now I'm making it a sperate function,
	 * which just handles this one very specific data replacement.  Will look at merging it in with the main
	 * parsing once we have a better understanding of where / when / how to do it.
	 *
	 * @param  string $msg           Text to parse
	 * @param  array  $searchData    Data to search for placeholders
	 * @param  object $el            Element model of the element which is doing the replacing
	 * @param  int    $repeatCounter Repeat instance
	 *
	 * @return  string  parsed message
	 */
	public function parseMessageForRepeats($msg, $searchData, $el, $repeatCounter)
	{
		if (strstr($msg, '{') && !empty($searchData))
		{
			$groupModel = $el->getGroupModel();
			if ($groupModel->canRepeat())
			{
				$elementModels = $groupModel->getPublishedElements();
				$formModel     = $el->getFormModel();

				foreach ($elementModels as $elementModel)
				{
					$repeatElName = $elementModel->getFullName(true, false);
					foreach (array($repeatElName, $repeatElName . '_raw') as $tmpElName)
					{
						if (strstr($msg, '{' . $tmpElName . '}'))
						{
							if (array_key_exists($tmpElName, $searchData) && is_array($searchData[$tmpElName]) && array_key_exists($repeatCounter, $searchData[$tmpElName]))
							{
								$tmpVal = $searchData[$tmpElName][$repeatCounter];

								if (is_array($tmpVal))
								{
									$tmpVal = implode(',', $tmpVal);
								}

								$msg    = str_replace('{' . $tmpElName . '}', $tmpVal, $msg);
							}
						}
					}
				}
			}
		}

		return $msg;
	}

	/**
	 * Iterates through string to replace every
	 * {placeholder} with posted data
	 *
	 * @param   mixed  $msg              Text|Array to parse
	 * @param   array  $searchData       Data to search for placeholders (default $_REQUEST)
	 * @param   bool   $keepPlaceholders If no data found for the place holder do we keep the {...} string in the
	 *                                   message
	 * @param   bool   $addSlashes       Add slashed to the text?
	 * @param   object $theirUser        User to use in replaceWithUserData (defaults to logged in user)
	 * @param   bool   $unsafe           If true (default) will not replace certain placeholders like $jConfig_secret
	 *                                   must not be shown to users
	 *
	 * @return  string  parsed message
	 */
	public function parseMessageForPlaceHolder($msg, $searchData = null, $keepPlaceholders = true, $addSlashes = false, $theirUser = null, $unsafe = true)
	{
		$returnType = is_array($msg) ? 'array' : 'string';
		$messages   = (array) $msg;

		foreach ($messages as &$msg)
		{
			$this->parseAddSlashes = $addSlashes;

			if (!($msg == '' || is_array($msg) || StringHelper::strpos($msg, '{') === false))
			{
				$msg = str_replace(array('%7B', '%7D'), array('{', '}'), $msg);

				if (is_object($searchData))
				{
					$searchData = ArrayHelper::fromObject($searchData);
				}
				// Merge in request and specified search data
				$f                 = JFilterInput::getInstance();
				$post              = $f->clean($_REQUEST, 'array');
				$this->_searchData = is_null($searchData) ? $post : array_merge($post, $searchData);

				// Enable users to use placeholder to insert session token
				$this->_searchData['JSession::getFormToken'] = JSession::getFormToken();

				// Replace with the user's data
				$msg = self::replaceWithUserData($msg);

				if (!is_null($theirUser))
				{
					// Replace with a specified user's data
					$msg = self::replaceWithUserData($msg, $theirUser, 'your');
				}

				$msg = self::replaceWithGlobals($msg);

				if (!$unsafe)
				{
					$msg = self::replaceWithUnsafe($msg);
					$msg = self::replaceWithSession($msg);
				}

				$msg = preg_replace("/{}/", "", $msg);

				// Replace {element name} with form data
				$msg = preg_replace_callback("/{([^}\s]+(\|\|[\w|\s]+|<\?php.*\?>)*)}/i", array($this, 'replaceWithFormData'), $msg);

				if (!$keepPlaceholders)
				{
					$msg = preg_replace("/{[^}\s]+}/i", '', $msg);
				}
			}
		}

		return $returnType === 'array' ? $messages : ArrayHelper::getValue($messages, 0, '');
	}

	/**
	 * Replace {varname} with request data (called from J content plugin)
	 *
	 * @param   string &$msg String to parse
	 *
	 * @return  void
	 */
	public function replaceRequest(&$msg)
	{
		static $request;

		if (!is_array($request))
		{
			$request = array();
			$f       = JFilterInput::getInstance();

			foreach ($_REQUEST as $k => $v)
			{
				if (is_string($v))
				{
					$request[$k] = $f->clean($v, 'CMD');
				}
			}
		}


		foreach ($request as $key => $val)
		{
			if (is_string($val))
			{
				// $$$ hugh - escape the key so preg_replace won't puke if key contains /
				$key = str_replace('/', '\/', $key);
				$msg = preg_replace("/\{$key\}/", $val, $msg);
			}
		}
	}

	/**
	 * Called from parseMessageForPlaceHolder to iterate through string to replace
	 * {placeholder} with user ($my) data
	 * AND
	 * {$their->var->email} placeholders
	 *
	 * @param   string $msg    Message to parse
	 * @param   object $user   Joomla user object
	 * @param   string $prefix Search string to look for e.g. 'my' to look for {$my->id}
	 *
	 * @return    string    parsed message
	 */
	public static function replaceWithUserData($msg, $user = null, $prefix = 'my')
	{
		$app = JFactory::getApplication();

		if (is_null($user))
		{
			$user = JFactory::getUser();
		}

		$user->levels = $user->getAuthorisedViewLevels();

		if (is_object($user))
		{
			foreach ($user as $key => $val)
			{
				if (substr($key, 0, 1) != '_')
				{
					if (!is_object($val) && !is_array($val))
					{
						$msg = str_replace('{$' . $prefix . '->' . $key . '}', $val, $msg);
						$msg = str_replace('{$' . $prefix . '-&gt;' . $key . '}', $val, $msg);
					}
					elseif (is_array($val))
					{
						$msg = str_replace('{$' . $prefix . '->' . $key . '}', implode(',', $val), $msg);
						$msg = str_replace('{$' . $prefix . '-&gt;' . $key . '}', implode(',', $val), $msg);
					}
				}
			}
		}
		/*
		 *  $$$rob parse another users data into the string:
		 *  format: is {$their->var->email} where var is the $app->input var to search for
		 *  e.g url - index.php?owner=62 with placeholder {$their->owner->id}
		 *  var should be an integer corresponding to the user id to load
		 */
		$matches = array();
		preg_match('/{\$their-\>(.*?)}/', $msg, $matches);

		foreach ($matches as $match)
		{
			$bits   = explode('->', str_replace(array('{', '}'), '', $match));

			if (count($bits) !== 3)
			{
				continue;
			}

			$userId = $app->input->getInt(ArrayHelper::getValue($bits, 1));

			// things like user elements might be single entry arrays
			if (is_array($userId))
			{
				$userId = array_pop($userId);
			}

			if (!empty($userId))
			{
				$user = JFactory::getUser($userId);
				$val  = $user->get(ArrayHelper::getValue($bits, 2));
				$msg  = str_replace($match, $val, $msg);
			}
		}

		return $msg;
	}

	/**
	 * Called from parseMessageForPlaceHolder to iterate through string to replace
	 * {placeholder} with global data
	 *
	 * @param   string $msg Message to parse
	 *
	 * @return    string    parsed message
	 */
	public static function replaceWithGlobals($msg)
	{
		$replacements = self::globalReplacements();

		foreach ($replacements as $key => $value)
		{
			$msg = str_replace($key, $value, $msg);
		}

		return $msg;
	}

	/**
	 * Utility function for replacing language tags.
	 * {lang} - Joomla code for user's selected language, like en-GB
	 * {langtag} - as {lang} with with _ instead of -
	 * {shortlang} - first two letters of {lang}, like en
	 * {multilang} - multilang URL code
	 *
	 * @param   string $msg Message to parse
	 *
	 * @return    string    parsed message
	 */
	public static function replaceWithLanguageTags($msg)
	{
		$replacements = self::langReplacements();

		foreach ($replacements as $key => $value)
		{
			$msg = str_replace($key, $value, $msg);
		}

		return $msg;
	}

	/**
	 * Called from parseMessageForPlaceHolder to iterate through string to replace
	 * {placeholder} with unsafe data
	 *
	 * @param   string $msg Message to parse
	 *
	 * @return    string    parsed message
	 */
	public static function replaceWithUnsafe($msg)
	{
		$replacements = self::unsafeReplacements();

		foreach ($replacements as $key => $value)
		{
			$msg = str_replace($key, $value, $msg);
		}

		return $msg;
	}

	/**
	 * Called from parseMessageForPlaceHolder to iterate through string to replace
	 * {placeholder} with session data
	 *
	 * @param   string $msg Message to parse
	 *
	 * @return    string    parsed message
	 */
	public static function replaceWithSession($msg)
	{
		if (strstr($msg, '{$session->'))
		{
			$session   = JFactory::getSession();
			$sessionData = array(
				'id' => $session->getId(),
				'token' => $session->get('session.token'),
				'formtoken' => JSession::getFormToken()
			);

			foreach ($sessionData as $key => $value)
			{
				$msg = str_replace('{$session->' . $key . '}', $value, $msg);
			}

			$msg = preg_replace_callback(
				'/{\$session-\>(.*?)}/',
				function($matches) use ($session) {
					$bits       = explode(':', $matches[1]);

					if (count($bits) > 1)
					{
						$sessionKey = $bits[1];
						$nameSpace  = $bits[0];
					}
					else
					{
						$sessionKey = $bits[0];
						$nameSpace  = 'default';
					}

					$val        = $session->get($sessionKey, '', $nameSpace);

					if (is_string($val))
					{
						return $val;
					}
					else if (is_numeric($val))
					{
						return (string) $val;
					}

					return '';
				},
				$msg
			);
		}

		return $msg;
	}

	/**
	 * Get an associative array of replacements for 'unsafe' value, like $jConfig_secret, which we
	 * only want to use for stricty internal use that won't ever get shown to the user
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function unsafeReplacements()
	{
		$config = JFactory::getConfig();

		$replacements = array(
			'{$jConfig_absolute_path}' => JPATH_SITE,
			'{$jConfig_secret}' => $config->get('secret')
		);

		return $replacements;
	}

	/**
	 * Get an associative array of replacements strings and values
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function globalReplacements()
	{
		$app       = JFactory::getApplication();
		$itemId    = self::itemId();
		$config    = JFactory::getConfig();
		$session   = JFactory::getSession();
		$token     = $session->get('session.token');

		$replacements = array(
			'{$jConfig_live_site}' => COM_FABRIK_LIVESITE,
			'{$jConfig_offset}' => $config->get('offset'),
			'{$Itemid}' => $itemId,
			'{$jConfig_sitename}' => $config->get('sitename'),
			'{$jConfig_mailfrom}' => $config->get('mailfrom'),
			'{where_i_came_from}' => $app->input->server->get('HTTP_REFERER', '', 'string'),
			'{date}' => date('Ymd'),
			'{year}' => date('Y'),
			'{mysql_date}' => date('Y-m-d H:i:s'),
			'{session.token}' => $token
		);

		foreach ($_SERVER as $key => $val)
		{
			if (!is_object($val) && !is_array($val))
			{
				$replacements['{$_SERVER->' . $key . '}']    = $val;
				$replacements['{$_SERVER-&gt;' . $key . '}'] = $val;
			}
		}

		if ($app->isClient('administrator'))
		{
			$replacements['{formview}'] = 'task=form.view';
			$replacements['{listview}'] = 'task=list.view';
			$replacements['{detailsview}'] = 'task=details.view';
		}
		else
		{
			$replacements['{formview}'] = 'view=form';
			$replacements['{listview}'] = 'view=list';
			$replacements['{detailsview}'] = 'view=details';
		}

		return array_merge($replacements, self::langReplacements());
	}

	/**
	 * Returns array of language tag replacements
	 *
	 * @return array
	 */
	public static function langReplacements()
	{
		$langtag   = JFactory::getLanguage()->getTag();
		$lang      = str_replace('-', '_', $langtag);
		$shortlang = explode('_', $lang);
		$shortlang = $shortlang[0];
		$multilang = Worker::getMultiLangURLCode();

		$replacements = array(
			'{lang}' => $lang,
			'{langtag}' => $langtag,
			'{multilang}' => $multilang,
			'{shortlang}' => $shortlang,
		);

		return $replacements;
	}

	/**
	 * Called from parseMessageForPlaceHolder to iterate through string to replace
	 * {placeholder} with posted data
	 *
	 * @param   string $matches Placeholder e.g. {placeholder}
	 *
	 * @return    string    posted data that corresponds with placeholder
	 */
	protected function replaceWithFormData($matches)
	{
		// Merge any join data key val pairs down into the main data array
		$joins = ArrayHelper::getValue($this->_searchData, 'join', array());

		foreach ($joins as $k => $data)
		{
			foreach ($data as $k => $v)
			{
				/*
				 * Only replace if we haven't explicitly set the key in _searchData.
				 * Otherwise, calc element in repeat group uses all repeating groups values rather than the
				 * current one that the plugin sets when it fire its Ajax request.
				 */
				if (!array_key_exists($k, $this->_searchData))
				{
					$this->_searchData[$k] = $v;
				}
			}
		}

		$match = $matches[0];
		$orig  = $match;

		// Strip the {}
		$match = StringHelper::substr($match, 1, StringHelper::strlen($match) - 2);

		/* $$$ hugh - added dbprefix substitution
		 * Not 100% if we should do this on $match before copying to $orig, but for now doing it
		 * after, so we don't potentially disclose dbprefix if no substitution found.
		 */
		$config = JFactory::getConfig();
		$prefix = $config->get('dbprefix');
		$match  = str_replace('#__', $prefix, $match);

		// $$$ rob test this format searchvalue||defaultsearchvalue
		$bits = explode('||', $match);

		if (count($bits) == 2)
		{
			$match = self::parseMessageForPlaceHolder('{' . $bits[0] . '}', $this->_searchData, false);

			if (in_array($match, array('', '<ul></ul>', '<ul><li></li></ul>')))
			{
			    // experiment with eval'ed code in defaults
                if (strstr($bits[1], '<?php'))
                {
                    $code = preg_replace('/^<\?php(.*)(\?>)$/s', '$1', $bits[1]);
                    $bits[1] = @eval($code);
                }

				return $bits[1] !== '' ? $bits[1] : $orig;
			}
			else
			{
				return $match !== '' ? $match : $orig;
			}
		}

		$match = preg_replace("/ /", "_", $match);

		if (!strstr($match, '.'))
		{
			// For some reason array_key_exists wasn't working for nested arrays??
			$aKeys = array_keys($this->_searchData);

			// Remove the table prefix from the post key
			$aPrefixFields = array();

			for ($i = 0; $i < count($aKeys); $i++)
			{
				$aKeyParts = explode('___', $aKeys[$i]);

				if (count($aKeyParts) == 2)
				{
					$tablePrefix           = array_shift($aKeyParts);
					$field                 = array_pop($aKeyParts);
					$aPrefixFields[$field] = $tablePrefix;
				}
			}

			if (array_key_exists($match, $aPrefixFields))
			{
				$match = $aPrefixFields[$match] . '___' . $match;
			}

			// Test to see if the made match is in the post key arrays
			$found = in_array($match, $aKeys, true);

			if ($found)
			{
				// Get the post data
				$match = $this->_searchData[$match];

				if (is_array($match))
				{
					$newMatch = '';

					// Deal with radio boxes etc. inside repeat groups
					foreach ($match as $m)
					{
						if (is_array($m))
						{
							$newMatch .= ',' . implode(',', $m);
						}
						else
						{
							$newMatch .= ',' . $m;
						}
					}

					$match = StringHelper::ltrim($newMatch, ',');
				}
			}
			else
			{
				$match = '';
			}
		}
		else
		{
			// Could be looking for URL field type e.g. for $_POST[url][link] the match text will be url.link
			$aMatch = explode('.', $match);
			$aPost  = $this->_searchData;

			foreach ($aMatch as $sPossibleArrayKey)
			{
				if (is_array($aPost))
				{
					if (!isset($aPost[$sPossibleArrayKey]))
					{
						return $orig;
					}
					else
					{
						$aPost = $aPost[$sPossibleArrayKey];
					}
				}
			}

			$match = $aPost;
			$found = true;
		}

		if ($this->parseAddSlashes)
		{
			$match = htmlspecialchars($match, ENT_QUOTES, 'UTF-8');
		}

		return $found ? $match : $orig;
	}

	/**
	 * Internal function to recursive scan directories
	 *
	 * @param   string $imagePath     Image path
	 * @param   string $folderPath    Path to scan
	 * @param   string &$folders      Root path of this folder
	 * @param   array  &$images       Value array of all existing folders
	 * @param   array  $aFolderFilter Value array of all existing images
	 * @param   bool   $makeOptions   Make options out for the results
	 *
	 * @return  void
	 */
	public static function readImages($imagePath, $folderPath, &$folders, &$images, $aFolderFilter, $makeOptions = true)
	{
		$imgFiles = self::fabrikReadDirectory($imagePath, '.', false, false, $aFolderFilter);

		foreach ($imgFiles as $file)
		{
			$ff_ = $folderPath . $file . '/';
			$ff  = $folderPath . $file;
			$i_f = $imagePath . '/' . $file;

			if (is_dir($i_f) && $file != 'CVS' && $file != '.svn')
			{
				if (!in_array($file, $aFolderFilter))
				{
					$folders[] = JHTML::_('select.option', $ff_);
					self::readImages($i_f, $ff_, $folders, $images, $aFolderFilter);
				}
			}
			elseif (preg_match('/bmp|gif|jpg|png/i', $file) && is_file($i_f))
			{
				// Leading / we don't need
				$imageFile             = StringHelper::substr($ff, 1);
				$images[$folderPath][] = $makeOptions ? JHTML::_('select.option', $imageFile, $file) : $file;
			}
		}
	}

	/**
	 * Utility function to read the files in a directory
	 *
	 * @param   string $path          The file system path
	 * @param   string $filter        A filter for the names
	 * @param   bool   $recurse       Recurse search into sub-directories
	 * @param   bool   $fullPath      True if to prepend the full path to the file name
	 * @param   array  $aFolderFilter Folder names not to recurse into
	 * @param   bool   $foldersOnly   Return a list of folders only (true)
	 *
	 * @return    array    of file/folder names
	 */
	public static function fabrikReadDirectory($path, $filter = '.', $recurse = false, $fullPath = false, $aFolderFilter = array(),
		$foldersOnly = false)
	{
		$arr = array();

		if (!@is_dir($path))
		{
			return $arr;
		}

		$handle = opendir($path);

		while ($file = readdir($handle))
		{
			$dir   = JPath::clean($path . '/' . $file);
			$isDir = is_dir($dir);

			if ($file != "." && $file != "..")
			{
				if (preg_match("/$filter/", $file))
				{
					if (($isDir && $foldersOnly) || !$foldersOnly)
					{
						if ($fullPath)
						{
							$arr[] = trim(JPath::clean($path . '/' . $file));
						}
						else
						{
							$arr[] = trim($file);
						}
					}
				}

				$goDown = true;

				if ($recurse && $isDir)
				{
					foreach ($aFolderFilter as $sFolderFilter)
					{
						if (strstr($dir, $sFolderFilter))
						{
							$goDown = false;
						}
					}

					if ($goDown)
					{
						$arr2    = self::fabrikReadDirectory($dir, $filter, $recurse, $fullPath, $aFolderFilter, $foldersOnly);
						$arrDiff = array_diff($arr, $arr2);
						$arr     = array_merge($arrDiff);
					}
				}
			}
		}

		closedir($handle);
		asort($arr);

		return $arr;
	}

	/**
	 * Joomfish translations don't seem to work when you do an ajax call
	 * it seems to load the geographical location language rather than the selected lang
	 * so for ajax calls that need to use jf translated text we need to get the current lang and
	 * send it to the js code which will then append the lang=XX to the ajax querystring
	 *
	 * Renamed to getShortLang as we don't support Joomfish any more
	 *
	 * @since 2.0.5
	 *
	 * @return    string    first two letters of lang code - e.g. nl from 'nl-NL'
	 */
	public static function getShortLang()
	{
		$lang = JFactory::getLanguage();
		$lang = explode('-', $lang->getTag());

		return array_shift($lang);
	}

	/**
	 * If J! multiple languages is enabled, return the URL language code for the currently selected language, which is
	 * set by the admin in the 'content languages'.  If not multi lang, return false;
	 *
	 * @return boolean || string
	 */
	public static function getMultiLangURLCode()
	{
		$multiLang = false;

		if (JLanguageMultilang::isEnabled())
		{
			$lang      = JFactory::getLanguage()->getTag();
			$languages = JLanguageHelper::getLanguages();
			foreach ($languages as $language)
			{
				if ($language->lang_code === $lang)
				{
					$multiLang = $language->sef;
					break;
				}
			}
		}

		return $multiLang;
	}

	/**
	 * Get the content filter used both in form and admin pages for content filter
	 * takes values from J content filtering options
	 *
	 * @return   array  (bool should the filter be used, object the filter to use)
	 */
	public static function getContentFilter()
	{
		$filter = false;

		// Filter settings
		jimport('joomla.application.component.helper');

		// Get Config and Filters in Joomla 2.5
		$config  = JComponentHelper::getParams('com_config');
		$filters = $config->get('filters');

		// If no filter data found, get from com_content (Joomla 1.6/1.7 sites)
		if (empty($filters))
		{
			$contentParams = JComponentHelper::getParams('com_content');
			$filters       = $contentParams->get('filters');
		}

		$user       = JFactory::getUser();
		$userGroups = JAccess::getGroupsByUser($user->get('id'));

		$blackListTags       = array();
		$blackListAttributes = array();

		$whiteListTags       = array();
		$whiteListAttributes = array();

		$whiteList  = false;
		$blackList  = false;
		$unfiltered = false;

		// Cycle through each of the user groups the user is in.
		// Remember they are include in the Public group as well.
		foreach ($userGroups AS $groupId)
		{
			// May have added a group by not saved the filters.
			if (!isset($filters->$groupId))
			{
				continue;
			}

			// Each group the user is in could have different filtering properties.
			$filterData = $filters->$groupId;
			$filterType = StringHelper::strtoupper($filterData->filter_type);

			if ($filterType == 'NH')
			{
				// Maximum HTML filtering.
			}
			elseif ($filterType == 'NONE')
			{
				// No HTML filtering.
				$unfiltered = true;
			}
			else
			{
				// Black or white list.
				// Pre-process the tags and attributes.
				$tags           = explode(',', $filterData->filter_tags);
				$attributes     = explode(',', $filterData->filter_attributes);
				$tempTags       = array();
				$tempAttributes = array();

				foreach ($tags as $tag)
				{
					$tag = trim($tag);

					if ($tag)
					{
						$tempTags[] = $tag;
					}
				}

				foreach ($attributes as $attribute)
				{
					$attribute = trim($attribute);

					if ($attribute)
					{
						$tempAttributes[] = $attribute;
					}
				}

				// Collect the black or white list tags and attributes.
				// Each list is cumulative.
				if ($filterType == 'BL')
				{
					$blackList           = true;
					$blackListTags       = array_merge($blackListTags, $tempTags);
					$blackListAttributes = array_merge($blackListAttributes, $tempAttributes);
				}
				elseif ($filterType == 'WL')
				{
					$whiteList           = true;
					$whiteListTags       = array_merge($whiteListTags, $tempTags);
					$whiteListAttributes = array_merge($whiteListAttributes, $tempAttributes);
				}
			}
		}

		// Remove duplicates before processing (because the black list uses both sets of arrays).
		$blackListTags       = array_unique($blackListTags);
		$blackListAttributes = array_unique($blackListAttributes);
		$whiteListTags       = array_unique($whiteListTags);
		$whiteListAttributes = array_unique($whiteListAttributes);

		// Unfiltered assumes first priority.
		if ($unfiltered)
		{
			$doFilter = false;

			// Don't apply filtering.
		}
		else
		{
			$doFilter = true;

			// Black lists take second precedence.
			if ($blackList)
			{
				// Remove the white-listed attributes from the black-list.
				$tags   = array_diff($blackListTags, $whiteListTags);
				$filter = JFilterInput::getInstance($tags, array_diff($blackListAttributes, $whiteListAttributes), 1, 1);
			}
			// White lists take third precedence.
			elseif ($whiteList)
			{
				// Turn off xss auto clean
				$filter = JFilterInput::getInstance($whiteListTags, $whiteListAttributes, 0, 0, 0);
			}
			// No HTML takes last place.
			else
			{
				$filter = JFilterInput::getInstance();
			}
		}

		return array($doFilter, $filter);
	}

	/**
	 * Clear PHP errors prior to running eval'd code
	 *
	 * @return  void
	 */
	public static function clearEval()
	{
		/**
		 * "Clear" PHP's errors.  NOTE that error_get_last() will still return non-null after this
		 * if there were any errors, but $error['message'] will be empty.  See comment in logEval()
		 * below for details.
		 */
		if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
			error_clear_last();
		}
		else
		{
			@trigger_error("");
		}
	}

	/**
	 * Raise a J Error notice if the eval'd result is false and there is a error
	 *
	 * @param   mixed  $val Evaluated result
	 * @param   string $msg Error message, should contain %s as we sprintf in the error_get_last()'s message property
	 *
	 * @return  void
	 */
	public static function logEval($val, $msg)
	{
		/*
		if ($val !== false)
		{
			return;
		}
		*/

		$error = error_get_last();
		/**
		 * $$$ hugh - added check for 'message' being empty, so we can do ..
		 * @trigger_error('');
		 * ... prior to eval'ing code if we want to "clear" anything pitched prior
		 * to the eval.  For instance, in the PHP validation plugin.  If we don't "clear"
		 * the errors before running the eval'd validation code, we end up reporting any
		 * warnings or notices pitched in our code prior to the validation running, which
		 * can be REALLY confusing.  After a trigger_error(), error_get_last() won't return null,
		 * but 'message' will be empty.
		 */
		if (is_null($error) || empty($error['message']))
		{
			// No error set (eval could have actually returned false as a correct value)
			return;
		}

		$enqMsgType = 'error';
		$indentHTML = '<br/>&nbsp;&nbsp;&nbsp;&nbsp;Debug:&nbsp;';
		$errString  = Text::_('COM_FABRIK_EVAL_ERROR_USER_WARNING');

		// Give a technical error message to the developer
		if (version_compare(phpversion(), '5.2.0', '>=') && $error && is_array($error))
		{
			$errString .= $indentHTML . sprintf($msg, $error['message']);
		}
		else
		{
			$errString .= $indentHTML . sprintf($msg, "unknown error - php version < 5.2.0");
		}

		self::logError($errString, $enqMsgType);
	}

	/**
	 * Raise a J Error notice if in dev mode or log a J error otherwise
	 *
	 * @param   string $errString Message to display / log
	 * @param   string $msgType   Joomla enqueueMessage message type e.g. 'error', 'warning' etc.
	 *
	 * @return  void
	 */
	public static function logError($errString, $msgType)
	{
		if (Html::isDebug())
		{
			$app = JFactory::getApplication();
			$app->enqueueMessage($errString, $msgType);
		}
		else
		{
			switch ($msgType)
			{
				case 'message':
					$priority = JLog::INFO;
					break;
				case 'warning':
					$priority = JLog::WARNING;
					break;
				case 'error':
				default:
					$priority = JLog::ERROR;
					break;
			}

			JLog::add($errString, $priority, 'com_fabrik');
		}
	}

	/**
	 * Log  to table jos_fabrik_logs
	 *
	 * @param   string $type       E.g. 'fabrik.fileupload.download'
	 * @param   mixed  $msg        Array/object/string
	 * @param   bool   $jsonEncode Should we json encode the message?
	 *
	 * @return  void
	 */
	public static function log($type, $msg, $jsonEncode = true)
	{
		if ($jsonEncode)
		{
			$msg = json_encode($msg);
		}

		$log               = FabTable::getInstance('log', 'FabrikTable');
		$log->message_type = $type;
		$log->message      = $msg;
		$log->store();
	}

	/**
	 * Get a database object
	 *
	 * Returns the global {@link JDatabase} object, only creating it
	 * if it doesn't already exist.
	 *
	 * @param   bool  $loadJoomlaDb Force (if true) the loading of the main J database,
	 *                              needed in admin to connect to J db whilst still using fab db drivers "{package}"
	 *                              replacement text
	 *
	 * @param   mixed $cnnId        If null then loads the fabrik default connection, if an int then loads the
	 *                              specified connection by its id
	 *
	 * @return  JDatabaseDriver object
	 */
	public static function getDbo($loadJoomlaDb = false, $cnnId = null)
	{
		$sig = (int) $loadJoomlaDb . '.' . $cnnId;

		if (!self::$database)
		{
			self::$database = array();
			self::$database = array();
		}

		if (!array_key_exists($sig, self::$database))
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
			$conf = JFactory::getConfig();

			if (!$loadJoomlaDb)
			{
				$cnModel  = JModelLegacy::getInstance('Connection', 'FabrikFEModel');
				$cn       = $cnModel->getConnection($cnnId);
				$host     = $cn->host;
				$user     = $cn->user;
				$password = $cn->password;
				$database = $cn->database;
			}
			else
			{
				$host     = $conf->get('host');
				$user     = $conf->get('user');
				$password = $conf->get('password');
				$database = $conf->get('db');
			}

			$dbPrefix = $conf->get('dbprefix');
			$driver   = $conf->get('dbtype');

			// Test for swapping db table names
			$driver .= '_fab';
			$options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database,
				'prefix' => $dbPrefix);

			$version              = new JVersion;
			self::$database[$sig] = $version->RELEASE > 2.5 ? JDatabaseDriver::getInstance($options) : JDatabase::getInstance($options);

			Worker::bigSelects(self::$database[$sig]);

		}

		return self::$database[$sig];
	}

	/**
	 *  $$$ hugh - testing doing bigSelects stuff here
	 *  Reason being, some folk on shared hosting plans with very restrictive MySQL
	 *  setups are hitting the 'big selects' problem on Fabrik internal queries, not
	 *  just on their List specific queries.  So we need to apply 'big selects' to our
	 *  default connection as well, essentially enabling it for ALL queries we do.
	 *
	 * @param  JDatabaseDriver $fabrikDb
	 *
	 * @return void
	 */
	public static function bigSelects($fabrikDb)
	{
		$fbConfig = JComponentHelper::getParams('com_fabrik');

		if ($fbConfig->get('enable_big_selects', 0) == '1')
		{
			/**
			 * Use of OPTION in SET deprecated from MySQL 5.1. onward
			 * http://www.fabrikar.com/forums/index.php?threads/enable-big-selects-error.39463/#post-198293
			 * NOTE - technically, using verison_compare on MySQL version could fail, if it's a "gamma"
			 * release, which PHP desn't grok!
			 */

			if (version_compare($fabrikDb->getVersion(), '5.1.0', '>='))
			{
				$fabrikDb->setQuery("SET SQL_BIG_SELECTS=1, GROUP_CONCAT_MAX_LEN=10240");
			}
			else
			{
				$fabrikDb->setQuery("SET OPTION SQL_BIG_SELECTS=1, GROUP_CONCAT_MAX_LEN=10240");
			}

			try
			{
				$fabrikDb->execute();
			} catch (\Exception $e)
			{
				// Fail silently
			}
		}
	}

	/**
	 * Helper function get get a connection
	 *
	 * @param   mixed $item A list table or connection id
	 *
	 * @since 3.0b
	 *
	 * @return FabrikFEModelConnection  connection
	 */
	public static function getConnection($item = null)
	{
		$app   = JFactory::getApplication();
		$input = $app->input;
		$jForm = $input->get('jform', array(), 'array');

		if (is_object($item))
		{
			$item = is_null($item->connection_id) ? ArrayHelper::getValue($jForm, 'connection_id', -1) : $item->connection_id;
		}

		$connId = (int) $item;

		if (!self::$connection)
		{
			self::$connection = array();
		}

		if (!array_key_exists($connId, self::$connection))
		{
			$connectionModel = JModelLegacy::getInstance('connection', 'FabrikFEModel');
			$connectionModel->setId($connId);

			if ($connId === -1)
			{
				// -1 for creating new table
				$connectionModel->loadDefaultConnection();
				$connectionModel->setId($connectionModel->getConnection()->id);
			}

			$connectionModel->getConnection();
			self::$connection[$connId] = $connectionModel;
		}

		return self::$connection[$connId];
	}

	/**
	 * Get the plugin manager
	 *
	 * @since    3.0b
	 *
	 * @return    FabrikFEModelPluginmanager    Plugin manager
	 */
	public static function getPluginManager()
	{
		if (!self::$pluginManager)
		{
			self::$pluginManager = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');
		}

		return self::$pluginManager;
	}

	/**
	 * Takes a string which may or may not be json and returns either string/array/object
	 * will also turn valGROUPSPLITTERval2 to array
	 *
	 * @param   string $data     Json encoded string
	 * @param   bool   $toArray  Force data to be an array
	 * @param   bool   $emptyish Set to false to return an empty array if $data is an empty string, instead of an
	 *                           emptyish (one empty string entry) array
	 *
	 * @return  mixed data
	 */
	public static function JSONtoData($data, $toArray = false, $emptyish = true)
	{
		if (is_string($data))
		{
			if (!strstr($data, '{'))
			{
				// Was messing up date rendering @ http://www.podion.eu/dev2/index.php/2011-12-19-10-33-59/actueel
				// return $toArray ? (array) $data : $data;
			}

			// Repeat elements are concatenated with the GROUPSPLITTER - convert to json string  before continuing.
			if (strstr($data, GROUPSPLITTER))
			{
				$data = json_encode(explode(GROUPSPLITTER, $data));
			}
			/* half hearted attempt to see if string is actually json or not.
			 * issue was that if you try to decode '000123' its turned into '123'
			 */
			if (strstr($data, '{') || strstr($data, '['))
			{
				$json = json_decode($data);

				// Only works in PHP5.3
				// $data = (json_last_error() == JSON_ERROR_NONE) ? $json : $data;
				if (is_null($json))
				{
					/*
					 * if coming back from a failed validation - the json string may have been htmlspecialchars_encoded in
					 * the form model getGroupView method
					 */
					$json = json_decode(stripslashes(htmlspecialchars_decode($data, ENT_QUOTES)));
				}

				$data = is_null($json) ? $data : $json;
			}

			// If $data was an empty string and "emptyish" is not set, we want an empty array, not an array with one empty string
			if ($toArray && !$emptyish && $data === '')
			{
				$data = array();
			}
		}

		$data = $toArray ? (array) $data : $data;

		return $data;
	}

	/**
	 * Test if a string is a compatible date
	 *
	 * @param   string $d Date to test
	 * @param   bool   $notNull  don't allow null / empty dates
	 *
	 * @return    bool
	 */
	public static function isNullDate($d)
	{
		$db         = self::getDbo(true);
		$aNullDates = array('0000-00-000000-00-00', '0000-00-00 00:00:00', '0000-00-00', '', $db->getNullDate());

		return in_array($d, $aNullDates);
	}

	/**
	 * Test if a string is a compatible date
	 *
	 * @param   string $d Date to test
     * @param   bool   $notNull  don't allow null / empty dates
	 *
	 * @return    bool
	 */
	public static function isDate($d, $notNull = true)
	{
		// Catch for ','
		if (strlen($d) < 2)
		{
			return false;
		}

		if ($notNull && self::isNullDate($d))
		{
			return false;
		}

		$cerl = error_reporting ();
		error_reporting (0);

		try
		{
			$dt = new DateTime($d);
		} catch (\Exception $e)
		{
			error_reporting ($cerl);
			self::clearEval();
			return false;
		}

		error_reporting ($cerl);
		return true;
	}


	public static function addMonthsInterval($months, DateTime $date)
	{
		$next = new DateTime($date->format('d-m-Y H:i:s'));
		$next->modify('last day of +' . $months . ' month');

		if ($date->format('d') > $next->format('d'))
		{
			return $date->diff($next);
		}
		else
		{
			return new DateInterval('P' . $months . 'M');
		}
	}

	public static function addMonths($months, DateTime $date)
	{
		return $date->add(self::addMonthsInterval($months, $date));
	}

	/**
	 * Get a user's TZ offset in MySql format, suitable for CONVERT_TZ
	 *
	 * @param  int  userId  userid or null (use logged on user if null)
	 *
	 * @return  string  symbolic timezone name (America/Chicago)
	 */
	public static function getUserTzOffsetMySql($userId = null)
	{
		$tz = self::getUserTzName($userId);
		$tz = new \DateTimeZone($tz);
		$date = new \DateTime("now", $tz);
		$offset = $tz->getOffset($date) . ' seconds';
		$dateOffset = clone $date;
		$dateOffset->sub(\DateInterval::createFromDateString($offset));
		$interval = $dateOffset->diff($date);
		return $interval->format('%R%H:%I');
	}

	/**
	 * Get a user's TZ offset in seconds
	 *
	 * @param  int  userId  userid or null (use logged on user if null)
	 *
	 * @return  int  seconds offset
	 */
	public static function getUserTzOffset($userId = null)
	{
		$tz = self::getUserTzName($userId);
		$tz = new \DateTimeZone($tz);
		$date = new \DateTime("now", $tz);
		return $tz->getOffset($date);
	}

	/**
	 * Get a user's TZ name
	 *
	 * @param  int  userId  userid or null (use logged on user if null)
	 *
	 * @return  string  symbolic timezone name (America/Chicago)
	 */
	public static function getUserTzName($userId = null)
	{
		if (empty($userId))
		{
			$user = JFactory::getUser();
		}
		else
		{
			$user = JFactory::getUser($userId);
		}
		$config = JFactory::getConfig();
		$tz = $user->getParam('timezone', $config->get('offset'));

		return $tz;
	}

	/**
	 * See if data is JSON or not.
	 *
	 * @param   mixed $data Date to test
	 * @params  bool  $quotedString  should we treat a single quoted string as JSON
	 * @since    3.0.6
	 *
	 * @return bool
	 */
	public static function isJSON($data, $quotedString = true)
	{
		if (!is_string($data))
		{
			return false;
		}

		if (is_numeric($data))
		{
			return false;
		}

		if (!$quotedString)
		{
			$data = trim($data, '"');
		}

		return json_decode($data) !== null;
	}

	/**
	 * Is the email really an email (more strict than JMailHelper::isEmailAddress())
	 *
	 * @param   string $email Email address
	 * @param   bool   $sms   test for SMS phone number instead of email, default false
	 *
	 * @since 3.0.4
	 *
	 * @return bool
	 */
	public static function isEmail($email, $sms = false)
	{
		if ($sms)
		{
			return self::isSMS($email);
		}

		$conf   = JFactory::getConfig();
		$mailer = $conf->get('mailer');

		if ($mailer === 'mail')
		{
			// Sendmail and Joomla isEmailAddress don't use the same conditions
			return (JMailHelper::isEmailAddress($email) && JMail::ValidateAddress($email));
		}

		return JMailHelper::isEmailAddress($email);
	}

	/**
	 * Is valid SMS number format
	 * This is just a stub which return true for now!
	 *
	 * @param   string $sms SMS number
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	public static function isSMS($sms)
	{
		return true;
	}

	/**
	 * Function to send an email
	 *
	 * @param   string   $from         From email address
	 * @param   string   $fromName     From name
	 * @param   mixed    $recipient    Recipient email address(es)
	 * @param   string   $subject      email subject
	 * @param   string   $body         Message body
	 * @param   boolean  $mode         false = plain text, true = HTML
	 * @param   mixed    $cc           CC email address(es)
	 * @param   mixed    $bcc          BCC email address(es)
	 * @param   mixed    $attachment   Attachment file name(s)
	 * @param   mixed    $replyTo      Reply to email address(es)
	 * @param   mixed    $replyToName  Reply to name(s)
	 * @param   array    $headers      Optional custom headers, assoc array keyed by header name
	 *
	 * @return  boolean  True on success
	 *
	 * @since   11.1
	 */
	public static function sendMail($from, $fromName, $recipient, $subject, $body, $mode = false,
		$cc = null, $bcc = null, $attachment = null, $replyTo = null, $replyToName = null, $headers = array())
	{
		// do a couple of tweaks to improve spam scores

		// Get a JMail instance
		$mailer = JFactory::getMailer();

		// If html, make sure there's an <html> tag
		if ($mode)
		{
			if (!stristr($body, '<html>'))
			{
				$body = '<html>' . $body . '</html>';
			}
		}

		/**
		 * if simple single email recipient with no name part, fake out name part to avoid TO_NO_BKRT hit in spam filters
		 * (don't do it for sendmail, as sendmail only groks simple emails in To header!)
		 */
		$recipientName = '';
		if ($mailer->Mailer !== 'sendmail' && is_string($recipient) && !strstr($recipient, '<'))
		{
			$recipientName = $recipient;
		}

		$mailer->setSubject($subject);
		$mailer->setBody($body);
		$mailer->Encoding = 'base64';

		// Are we sending the email as HTML?
		$mailer->isHtml($mode);

		try
		{
			$mailer->addRecipient($recipient, $recipientName);
		}
		catch (\Exception $e)
		{
            self::log('fabrik.helper.sendmail.error', 'Exception in addRecipient: ' . $e->getMessage(), false);

            return false;
		}

		try
		{
			$mailer->addCc($cc);
		}
		catch (\Exception $e)
		{
            self::log('fabrik.helper.sendmail.warning', 'Exception in addCc: ' . $e->getMessage());

			// not sure if we should bail if Cc is bad, for now just soldier on
		}

		try
		{
			$mailer->addBcc($bcc);
		}
		catch (\Exception $e)
		{
            self::log('fabrik.helper.sendmail.warning', 'Exception in addBcc: ' . $e->getMessage(), false);

            // not sure if we should bail if Bcc is bad, for now just soldier on
		}

		if (!empty($attachment))
		{
			try
			{
				$mailer->addAttachment($attachment);
			}
			catch (\Exception $e)
			{
                self::log('fabrik.helper.sendmail.warning', 'Exception in addAttachment: ' . $e->getMessage(), false);

                // most likely file didn't exist, ignore
			}
		}

		$autoReplyTo = false;

		// Take care of reply email addresses
		if (is_array($replyTo))
		{
			$numReplyTo = count($replyTo);

			for ($i = 0; $i < $numReplyTo; $i++)
			{
				try
				{
					$mailer->addReplyTo($replyTo[$i], $replyToName[$i]);
				}
				catch (\Exception $e)
				{
                    self::log('fabrik.helper.sendmail.warning', 'Exception in addReplyTo: ' . $e->getMessage(), false);

                    // carry on
				}
			}
		}
		elseif (isset($replyTo))
		{
			try
			{
				$mailer->addReplyTo($replyTo, $replyToName);
			}
			catch (\Exception $e)
			{
                self::log('fabrik.helper.sendmail.warning', 'Exception in addReplyTo: ' . $e->getMessage(), false);

                // carry on
			}
		}
		else
		{
			$autoReplyTo = true;
		}

		try
		{
			$mailer->setSender(array($from, $fromName, $autoReplyTo));
		}
		catch (\Exception $e)
		{
            self::log('fabrik.helper.sendmail.error', 'Exception in setSender: ' . $e->getMessage(), false);

            return false;
		}

		/**
		 * Set the plain text AltBody, which forces the PHP mailer class to make this
		 * a multipart MIME type, with an alt body for plain text.  If we don't do this,
		 * the default behavior is to send it as just text/html, which causes spam filters
		 * to downgrade it.
		 * @@@trob: insert \n before  <br to keep newlines(strip_tag may then strip <br> or <br /> etc, decode html
		 */
		if ($mode)
		{
			$body = str_ireplace(array("<br />","<br>","<br/>"), "\n<br />", $body);
			$body = html_entity_decode($body);
			$mailer->AltBody = JMailHelper::cleanText(strip_tags($body));
		}

		foreach ($headers as $headerName => $headerValue) {
			$mailer->addCustomHeader($headerName, $headerValue);
		}

		$config = JComponentHelper::getParams('com_fabrik');

		if ($config->get('verify_peer', '1') === '0')
		{
			$mailer->SMTPOptions = array(
				'ssl' =>
					array(
						'verify_peer' => false,
						'verify_peer_name' => false,
						'allow_self_signed' => true
					)
			);
		}

		try
		{
			$ret = $mailer->Send();
		}
		catch (\Exception $e)
		{
            self::log('fabrik.helper.sendmail.error', 'Exception in Send: ' . $e->getMessage(), false);

            return false;
		}

        // if ACY mailing is installed, it returns an exception (!) rather than false
        if (get_parent_class($ret) === 'Exception')
        {
            self::log('fabrik.helper.sendmail.error', 'Exception in Send: ' . $ret->getMessage(), false);

            $ret = false;
        }

        return $ret;
	}

	/**
	 * Get a JS go back action e.g 'onclick="history.back()"
	 *
	 * @return string
	 */
	public static function goBackAction()
	{
		jimport('joomla.environment.browser');
		$uri = JUri::getInstance();

		$url = filter_var(ArrayHelper::getValue($_SERVER, 'HTTP_REFERER'), FILTER_SANITIZE_URL);

		if ($uri->getScheme() === 'https')
		{
			$goBackAction = 'onclick="parent.location=\'' . $url . '\'"';
		}
		else
		{
			$goBackAction = 'onclick="parent.location=\'' . $url . '\'"';
		}

		return $goBackAction;
	}

	/**
	 * Attempt to find the active menu item id - Only for front end
	 *
	 *  - First checked $listId for menu items
	 *  - Then checks if itemId in $input
	 *  - Finally checked active menu item
	 *
	 * @param   int $listId List id to attempt to get the menu item id for the list.
	 *
	 * @return mixed NULL if nothing found, int if menu item found
	 */
	public static function itemId($listId = null)
	{
		static $listIds = array();

		$app = JFactory::getApplication();

		if (!$app->isAdmin())
		{
			// Attempt to get Itemid from possible list menu item.
			if (!is_null($listId))
			{
				if (!array_key_exists($listId, $listIds))
				{
					$db         = JFactory::getDbo();
					$myLanguage = JFactory::getLanguage();
					$myTag      = $myLanguage->getTag();
					$qLanguage  = !empty($myTag) ? ' AND ' . $db->q($myTag) . ' = ' . $db->qn('m.language') : '';
					$query      = $db->getQuery(true);
					$query->select('m.id AS itemId')->from('#__extensions AS e')
						->leftJoin('#__menu AS m ON m.component_id = e.extension_id')
						->where('e.name = "com_fabrik" and e.type = "component" and m.link LIKE "%listid=' . $listId . '"' . $qLanguage);
					$db->setQuery($query);

					if ($itemId = $db->loadResult())
					{
						$listIds[$listId] = $itemId;
					}
					else{
						$listIds[$listId] = false;
					}
				}
				else{
					if ($listIds[$listId] !== false)
					{
						return $listIds[$listId];
					}
				}
			}

			$itemId = (int) $app->input->getInt('itemId');

			if ($itemId !== 0)
			{
				return $itemId;
			}

			$menus = $app->getMenu();
			$menu  = $menus->getActive();

			if (is_object($menu))
			{
				return $menu->id;
			}
		}

		return null;
	}

	/**
	 * Attempt to get a variable first from the menu params (if they exists) if not from request
	 *
	 * @param   string $name                         Param name
	 * @param   mixed  $val                          Default
	 * @param   bool   $mambot                       If set to true menu params ignored
	 * @param   string $priority                     Defaults that menu priorities override request - set to 'request'
	 *                                               to inverse this priority
	 * @param   array  $opts                         Options 'listid' -> if priority = menu then the menu list id must
	 *                                               match this value to use the menu param.
	 *
	 * @return  string
	 */
    public static function getMenuOrRequestVar($name, $val = '', $mambot = false, $priority = 'menu', $opts = array())
    {
        $app   = JFactory::getApplication();
        $input = $app->input;

        if ($priority === 'menu')
        {

            $val = $input->get($name, $val, 'string');

            if (!$app->isAdmin())
            {
                if (!$mambot)
                {
                    $menus = $app->getMenu();
                    $menu  = $menus->getActive();

                    if (is_object($menu))
                    {
                        $match = true;

                        if (array_key_exists('listid', $opts)) {
                            $menuListId = ArrayHelper::getValue($menu->query, 'listid', '');
                            $checkListId = ArrayHelper::getValue($opts, 'listid', $menuListId);
                            $match = (int) $menuListId === (int) $checkListId;
                        }
                        else if (array_key_exists('formid', $opts)) {
                            $menuFormId  = ArrayHelper::getValue($menu->query, 'formid', '');
                            $checkFormId = ArrayHelper::getValue($opts, 'formid', $menuFormId);
                            $match = (int) $menuFormId === (int) $checkFormId;
                        }

                        if ($match)
                        {
                            $val = $menu->params->get($name, $val);
                        }
                    }
                }
            }
        }
        else
        {
            if (!$app->isAdmin())
            {
                $menus = $app->getMenu();
                $menu  = $menus->getActive();

                // If there is a menu item available AND the view is not rendered in a content plugin
                if (is_object($menu) && !$mambot)
                {
                    $match = true;

                    if (array_key_exists('listid', $opts)) {
                        $menuListId = ArrayHelper::getValue($menu->query, 'listid', '');
                        $checkListId = ArrayHelper::getValue($opts, 'listid', $menuListId);
                        $match = (int) $menuListId === (int) $checkListId;
                    }
                    else if (array_key_exists('formid', $opts)) {
                        $menuFormId  = ArrayHelper::getValue($menu->query, 'formid', '');
                        $checkFormId = ArrayHelper::getValue($opts, 'formid', $menuFormId);
                        $match = (int) $menuFormId === (int) $checkFormId;
                    }

                    if ($match)
                    {
                        $val = $menu->params->get($name, $val);
                    }
                }
            }

            $val = $input->get($name, $val, 'string');
        }

        return $val;
    }

	/**
	 * Access control function for determining if the user can perform
	 * a designated function on a specific row
	 *
	 * @param   object $params Item params to test
	 * @param   object $row    Data
	 * @param   string $col    Access control setting to compare against
	 *
	 * @return    mixed    - if ACL setting defined here return bool, otherwise return -1 to continue with default acl
	 *                     setting
	 */
	public static function canUserDo($params, $row, $col)
	{
		if (!is_null($row))
		{
			$app     = JFactory::getApplication();
			$input   = $app->input;
			$user    = JFactory::getUser();
			$userCol = $params->get($col, '');

			if ($userCol != '')
			{
				$userCol = StringHelper::safeColNameToArrayKey($userCol);

				if (!array_key_exists($userCol, $row))
				{
					return false;
				}
				else
				{
					if (array_key_exists($userCol . '_raw', $row))
					{
						$userCol .= '_raw';
					}

					$myId = $user->get('id');

					// -1 for menu items that link to their own records
					$userColVal = is_array($row) ? $row[$userCol] : $row->$userCol;

					// User element stores as object
					if (is_object($userColVal))
					{
						$userColVal = ArrayHelper::fromObject($userColVal);
					}

					// Could be coming back from a failed validation in which case val might be an array
					if (is_array($userColVal))
					{
						$userColVal = array_shift($userColVal);
					}

					if (empty($userColVal) && empty($myId))
					{
						return false;
					}

					if (intVal($userColVal) === intVal($myId) || $input->get('rowid') == -1)
					{
						return true;
					}
				}
			}
		}

		return -1;
	}

	/**
	 * Can Fabrik render PDF - required the DOMPDF library to be installed in Joomla libraries folder
	 *
	 * @param  bool  $puke  throw an exception if can't
	 *
	 * @throws RuntimeException
	 *
	 * @return bool
	 */
	public static function canPdf($puke = true)
	{
		$config = \JComponentHelper::getParams('com_fabrik');

		if ($config->get('fabrik_pdf_lib', 'dompdf') === 'dompdf')
		{
			$file = COM_FABRIK_LIBRARY . '/vendor/dompdf/dompdf/composer.json';
		}
		else
		{
			$file = COM_FABRIK_LIBRARY . '/vendor/mpdf/mpdf/composer.json';
		}

		if (!JFile::exists($file))
		{
			if ($puke)
			{
				throw new RuntimeException(Text::_('COM_FABRIK_NOTICE_DOMPDF_NOT_FOUND'));
			}
			else
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Get a cache handler
	 * $$$ hugh - added $listModel arg, needed so we can see if they have set "Disable Caching" on the List
	 *
	 * @param   object  $listModel  List Model
	 * @param   string  $group  group name (will default to package)
	 * @param   int  $ttl  time to live in minutes, defaults to J! config
	 *
	 * @since   3.0.7
	 *
	 * @return  JCache
	 */
	public static function getCache($listModel = null, $group = null, $ttl = null)
	{
		$config  = JFactory::getConfig();
		$app     = JFactory::getApplication();
		$package = isset($group) ? $group : $app->getUserState('com_fabrik.package', 'fabrik');
		$time    = isset($ttl) ? (int) $ttl : (int) $config->get('cachetime');
		$base    = JPATH_BASE . '/cache/';
		$opts    = array('defaultgroup' => 'com_' . $package, 'cachebase' => $base, 'lifetime' => $time, 'language' => 'en-GB', 'storage' => 'file');
		$cache   = JCache::getInstance('callback', $opts);
		$doCache = $config->get('caching', 0) > 0 ? true : false;

		if ($doCache && $listModel !== null)
		{
			$doCache = $listModel->getParams()->get('list_disable_caching', '0') == '0';
		}

		$cache->setCaching($doCache);

		return $cache;
	}

	/**
	 * Is caching enabled
	 *
	 * @param   object  $listModel  List Model
	 * @param   bool    $noGuest    disable caching for guests
	 *
	 * @since   3.8
	 *
	 * @return  JCache
	 */
	public static function useCache($listModel = null, $noGuest = true, $excludedFormats = null)
	{
		$config  = JFactory::getConfig();
		$app = JFactory::getApplication();

		if (!isset($excludedFormats))
		{
			$excludedFormats =  array('raw', 'csv', 'pdf', 'json', 'fabrikfeed', 'feed');
		}

		// check global J! system cache setting
		$doCache = $config->get('caching', 0) > 0 ? true : false;

		// if enabled, see if any other settingg disables it
		if ($doCache)
		{
			// Check the Fabrik global option
			$fabrikConfig = JComponentHelper::getParams('com_fabrik');

			if ($fabrikConfig->get('disable_caching', '0') === '1')
			{
				return false;
			}

			// If a list model has been specified, see if caching is disabled for this list
			if ($listModel !== null)
			{
				if ($listModel->getParams()->get('list_disable_caching', '0') === '1')
				{
					return false;
				}
			}

			// Check if caching is disabled for guests
			if ($noGuest)
			{
				if (JFactory::getUser()->get('id') === '0')
				{
					return false;
				}
			}

			if (in_array($app->input->get('format'), $excludedFormats))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the default values for a given JForm
	 *
	 * @param   string $form Form name e.g. list, form etc.
	 *
	 * @since   3.0.7
	 *
	 * @return  array  key field name, value default value
	 */
	public static function formDefaults($form)
	{
		JForm::addFormPath(JPATH_COMPONENT . '/models/forms');
		JForm::addFieldPath(JPATH_COMPONENT . '/models/fields');
		$form = JForm::getInstance('com_fabrik.' . $form, $form, array('control' => '', 'load_data' => true));
		$fs   = $form->getFieldset();
		$json = array('params' => array());

		foreach ($fs as $name => $field)
		{
			if (substr($name, 0, 7) === 'params_')
			{
				$name                  = str_replace('params_', '', $name);
				$json['params'][$name] = $field->value;
			}
			else
			{
				$json[$name] = $field->value;
			}
		}

		return $json;
	}

	/**
	 * Are we in J3 or using a bootstrap tmpl
	 *
	 * @since   3.1
	 *
	 * @return  bool
	 */
	public static function j3()
	{
		$app     = JFactory::getApplication();
		$version = new JVersion;

		// Only use template test for testing in 2.5 with my temp J bootstrap template.
		$tpl = $app->getTemplate();

		return ($tpl === 'bootstrap' || $tpl === 'fabrik4' || $version->RELEASE > 2.5);
	}

	/**
	 * Are we in a form process task
	 *
	 * @since 3.2
	 *
	 * @return bool
	 */
	public static function inFormProcess()
	{
		$app = JFactory::getApplication();

		return $app->input->get('task') == 'form.process' || ($app->isAdmin() && $app->input->get('task') == 'process');
	}

	/**
	 * Are we in an AJAX validation process task
	 *
	 * @since 3.9.2
	 *
	 * @return bool
	 */
	public static function inAJAXValidation()
	{
		$app = JFactory::getApplication();

		return $app->input->get('task', '') === 'form.ajax_validate';
	}


	/**
	 * Remove messages from JApplicationCMS
	 *
	 * @param   CMSApplication $app  Application to kill messages from
	 * @param   string          $type Message type e.g. 'warning', 'error'
	 *
	 * @return  array  Remaining messages.
	 */
	public static function killMessage(CMSApplication $app, $type)
	{
		$appReflection = new \ReflectionClass(get_class($app));
		$_messageQueue = $appReflection->getProperty('_messageQueue');
		$_messageQueue->setAccessible(true);
		$messages = $_messageQueue->getValue($app);

		foreach ($messages as $key => $message)
		{
			if ($message['type'] == $type)
			{
				unset($messages[$key]);
			}
		}

		$_messageQueue->setValue($app, $messages);

		return $messages;
	}

	/**
	 * Loose casing to boolean
	 *
	 * @param   mixed   $var     Var to test
	 * @param   boolean $default if neither a truish or falsy match are found
	 *
	 * @return bool - Set to false if false is found.
	 */
	public static function toBoolean($var, $default)
	{
		if ($var === 'false' || $var === 0 || $var === false)
		{
			return false;
		}

		if ($var === 'true' || $var === 1 || $var === true)
		{
			return true;
		}

		return $default;
	}

	/**
	 * Get a getID3 instance - check if library installed, if not, toss an exception
	 *
	 * @return  object|bool  - getid3 object or false if lib not installed
	 */
	public static function getID3Instance()
	{
		$getID3 = false;

		if (JFile::exists(COM_FABRIK_LIBRARY . '/libs/getid3/getid3/getid3.php'))
		{
			ini_set('display_errors', true);
			require_once COM_FABRIK_LIBRARY . '/libs/getid3/getid3/getid3.php';
			require_once COM_FABRIK_LIBRARY . '/libs/getid3/getid3/getid3.lib.php';

			\getid3_lib::IncludeDependency(COM_FABRIK_LIBRARY . '/libs/getid3/getid3/extension.cache.mysqli.php', __FILE__, true);
			$config   = JFactory::getConfig();
			$host     = $config->get('host');
			$database = $config->get('db');
			$username = $config->get('user');
			$password = $config->get('password');
			$getID3   = new \getID3_cached_mysqli($host, $database, $username, $password);
		}

		return $getID3;
	}

	public static function getMemoryLimit($symbolic = false)
	{
		$memory    = trim(ini_get('memory_limit'));
		$memory    = trim($memory);

		if ($symbolic)
		{
			return $memory;
		}

		$last = strtolower($memory[strlen($memory)-1]);
		$val  = substr($memory, 0, -1);

		switch($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}
}
