<?php
/**
 * Send an SMS via the kapow sms gateway
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once COM_FABRIK_FRONTEND . '/helpers/sms.php';

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
		$username = FArrayHelper::getValue($opts, 'sms-username');
		$password = FArrayHelper::getValue($opts, 'sms-password');
		$smsfrom = FArrayHelper::getValue($opts, 'sms-from');
		$smsto = FArrayHelper::getValue($opts, 'sms-to');
		$smstos = explode(',', $smsto);

		foreach ($smstos as $smsto)
		{
			$url = sprintf($this->url, $username, $password, $smsto, $message);
			FabrikSMS::doRequest('GET', $url, '');
		}
	}
}
