<?php
/**
 * Twilio SMS gateway class
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\ArrayHelper;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

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
		$sid = ArrayHelper::getValue($opts, 'sms-username');
		$token = ArrayHelper::getValue($opts, 'sms-password');
		$smsto = ArrayHelper::getValue($opts, 'sms-to');

		// From a valid Twilio number
		$smsfrom = ArrayHelper::getValue($opts, 'sms-from');
		$smstos = empty($smsto) ? array() : explode(",", $smsto);

		$client = new Twilio\Rest\Client($sid, $token);

		foreach ($smstos as $smsto)
		{
			try {
				$client->messages->create(
					trim($smsto),
					array(
						'from' => $smsfrom,
						'body' => $message
					)
				);
			}
			catch (TwilioException $e)
			{
				JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');

				return false;
			}
		}

		return true;
	}
}
