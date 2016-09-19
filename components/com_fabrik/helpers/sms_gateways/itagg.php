<?php
/**
 * Itagg SMS gateway class
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Itagg SMS gateway class
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @since       3.0
 */

require_once COM_FABRIK_FRONTEND . '/helpers/sms.php';

class Itagg extends JObject
{
	/**
	 * URL To Post SMS to
	 *
	 * @var string
	 */
	protected $url = 'https://secure.itagg.com/smsg/sms.mes';

	/**
	 * Send SMS
	 *
	 * @param   string  $message  sms message
	 * @param   array   $opts     Options
	 *
	 * @return  void
	 */

	public function process($message, $opts)
	{
		$username = FArrayHelper::getValue($opts, 'sms-username');
		$password = FArrayHelper::getValue($opts, 'sms-password');
		$smsfrom = FArrayHelper::getValue($opts, 'sms-from');
		$smsto = FArrayHelper::getValue($opts, 'sms-to');
		$smstos = explode(",", $smsto);
		$message = urlencode($message);

		foreach ($smstos as $smsto)
		{
			if (substr($smsto, 0, 1) == '+' && JString::substr($smsto, 1, 2) != '44')
			{
				// Global sms
				$route = 8;
			}
			else
			{
				// UK (itagg)
				$route = 7;
			}

			$smsto = urlencode($smsto);
			$url = $this->url;
			$vars = 'usr=' . $username . '&pwd=' . $password . '&from=rob&to=' . $smsto . '&type=text&route=' . $route . '&txt=' . $message;

			$itaggapi = "https://secure.itagg.com/smsg/sms.mes";
			/* $params="usr=XXX&pwd=YYY&from=steve&to=07712345678,447912345678,3912345678&type=text&rout
			e=7&txt=hello+via+POST"; */
			$ch = curl_init();

			if (!$ch)
			{
				throw new RuntimeException("cant ini curl session", 500);
				exit;
			}

			curl_setopt($ch, CURLOPT_URL, $itaggapi);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);

			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

			$returned = curl_exec($ch);
			curl_close($ch);

			// This will be the OK / error message
			if ($returned === true)
			{
				echo "sent ok";
			}

			$res = FabrikSMS::doRequest('POST', $url, $vars);
		}
	}
}
