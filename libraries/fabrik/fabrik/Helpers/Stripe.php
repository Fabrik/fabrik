<?php
/**
 * PDF Set up helper
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Helpers;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Stripe set up helper
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @since       3.1rc3
 */

class Stripe
{
	/**
	 * Setup Stripe API
	 *
	 * @param  object  $puke  throw exception if not installed (true) or just return false
	 *
	 * @return  bool
	 */

	public static function setupStripe(\Joomla\Registry\Registry $params, $plugin)
	{
		$testMode = $params->get($plugin . '_test_mode', '0') === '1';

		if ($testMode)
		{
			$secretKey = trim($params->get($plugin . '_test_secret_key', ''));
		}
		else
		{
			$secretKey = trim($params->get($plugin . '_secret_key', ''));
		}

		if (empty($secretKey))
		{
			return false;
		}

		\Stripe\Stripe::setApiKey($secretKey);
		\Stripe\Stripe::setApiVersion('2018-01-23');
		\Stripe\Stripe::setAppInfo(
			"Joomla Fabrik " . $plugin,
			"3.8.0",
			"http://fabrikar.com"
		);

		/*
		try
		{
			$balance = \Stripe\Balance::retrieve();
		}
		catch (\Exception $e)
		{
			return false;
		}
		*/

		return true;
	}
}
