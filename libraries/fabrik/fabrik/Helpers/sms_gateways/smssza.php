<?php
/**
 * Send an SMS via the SMSS (ZA) gateway
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\ArrayHelper;
use Fabrik\Helpers\Sms;

/**
 * SMSS (ZA) SMS gateway class
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @since       3.0
 */
class Smssza extends JObject
{
	/**
	 * URL To Post SMS to
	 *
	 * @var string
	 */
	protected $url = 'http://148.251.196.36/app/smsapi/index.php?key=%s&type=text&contacts=%s&senderid=%s&msg=%s&time=';

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
		$username = ArrayHelper::getValue($opts, 'sms-username');
		$password = ArrayHelper::getValue($opts, 'sms-password');
		$smsfrom = ArrayHelper::getValue($opts, 'sms-from');
		$smsto = ArrayHelper::getValue($opts, 'sms-to');

		$url = sprintf($this->url, $username, $smsto, $smsfrom, urlencode($message));
		$response = Sms::doRequest('GET', $url, '');
		return strstr($response, 'api_') !== false;
	}
}
