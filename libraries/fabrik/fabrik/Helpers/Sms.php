<?php
/**
 * Send sms's
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Helpers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \JFactory;
use \RuntimeException;

/**
 * Send sms's
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @since       3.0
 */
class Sms
{
	/**
	 * Send sms
	 *
	 * @param   string  $method    post/get
	 * @param   string  $url       url to request
	 * @param   string  $vars      querystring vars to post
	 * @param   string  $auth      auth
	 * @param   string  $callback  method
	 *
	 * @return  mixed data or curl error
	 */

	public static function doRequest($method, $url, $vars, $auth = '', $callback = false)
	{
		$app = JFactory::getApplication();
		if (!function_exists('curl_init'))
		{
			throw new RuntimeException(Text::_('COM_FABRIK_ERR_CURL_NOT_INSTALLED'));
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $app->input->server->getString('HTTP_USER_AGENT'));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');

		if ($method == 'POST')
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
		}

		if (!empty($auth))
		{
			curl_setopt($ch, CURLOPT_USERPWD, $auth);
		}

		$data = curl_exec($ch);
		curl_close($ch);

		if ($data)
		{
			if ($callback)
			{
				return call_user_func($callback, $data);
			}
			else
			{
				return $data;
			}
		}
		else
		{
			return curl_error($ch);
		}
	}
}
