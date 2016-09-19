<?php
/**
 * Twilio SMS gateway class
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Twilio SMS gateway class
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @since       3.0
 */

class Twilio extends JObject
{
	/**
	 * Send SMS
	 *
	 * @param   string  $message  sms message
	 * @param   array   $opts     options
	 *
	 * @return  void
	 */

	public function process($message = '', $opts)
	{
		jimport('vendor.twilio.sdk.Services.Twilio');
		$username = FArrayHelper::getValue($opts, 'sms-username');
		$token = FArrayHelper::getValue($opts, 'sms-password');
		$smsto = FArrayHelper::getValue($opts, 'sms-to');

		// From a valid Twilio number
		$smsfrom = FArrayHelper::getValue($opts, 'sms-from');
		$smstos = explode(",", $smsto);

		$client = new Services_Twilio($username, $token);

		foreach ($smstos as $smsto)
		{
			try {
				$call = $client->account->messages->create(
					array(
						'From' => $smsfrom,
						'To' => $smsto,
						'Body' => $message
					)
				);
			}
			catch (Services_Twilio_RestException $e)
			{
				//echo $e->getMessage();

				return false;
			}
		}

		return true;
	}
}
