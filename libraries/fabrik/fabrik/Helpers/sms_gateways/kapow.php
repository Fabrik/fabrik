<?php
/**
 * Send an SMS via the kapow sms gateway
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
 * Kapow SMS gateway class
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @since       3.0
 */

class Kapow extends JObject
{
	/**
	 * URL To Post SMS to
	 *
	 * @var string
	 */
	protected $url = 'http://www.kapow.co.uk/scripts/sendsms.php?username=%s&password=%s&mobile=%s&sms=%s';

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
		$smstos = explode(',', $smsto);

		foreach ($smstos as $smsto)
		{
			$url = sprintf($this->url, $username, $password, $smsto, $message);
			Sms::doRequest('GET', $url, '');
		}
	}
}
