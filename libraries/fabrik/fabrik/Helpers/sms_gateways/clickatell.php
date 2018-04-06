<?php
/**
 * Clickatell SMS gateway class
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Clickatell\Api\ClickatellRest;
use Fabrik\Helpers\ArrayHelper;

/**
 * Clickatell SMS gateway class
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @since       3.0
 */

class Clickatell extends JObject
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
		// Clickatell only uses token, no SID, use whichever param isn't empty
		$sid = ArrayHelper::getValue($opts, 'sms-username');
		$token = ArrayHelper::getValue($opts, 'sms-password');

		if (empty($token) && !empty($sid))
		{
			$token = $sid;
		}

		// no sms-from setting for Clickatell, just set up 'to' array
		$smsto = ArrayHelper::getValue($opts, 'sms-to');
		$smstos = empty($smsto) ? array() : explode(",", $smsto);

		// Clickatell is picky about numbers, no spaces or dashes
		foreach ($smstos as &$smsto)
		{
			$smsto = preg_replace("/[^0-9]/","", $smsto);
		}

		$client = new ClickatellRest($token);

		// Clickatell API doesn't throw exceptions, but something else might
		try {
			$response = $client->sendMessage(
				$smstos,
				$message
			);
		}
		catch (Exception $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return false;
		}

		// check the response array
		foreach ($response as $item)
		{
			if ($item->error !== false)
			{
				// @TODO add language for this
				JFactory::getApplication()->enqueueMessage('SMS failed with error code: ' . $item->errorCode, 'error');

				return false;
			}
		}

		return true;
	}
}
