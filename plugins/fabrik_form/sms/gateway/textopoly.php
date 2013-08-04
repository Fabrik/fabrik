<?php
/**
 * Textopoly SMS gateway class
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Textopoly SMS gateway class
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @since       3.0
*/

class Textopoly extends JObject
{

	/**
	 * URL To Post SMS to
	 *
	 * @var string
	 */
	protected $url = 'http://sms.mxtelecom.com/SMSSend?user=%s&pass=%s&smsfrom=%s&smsto=%s&smsmsg=%s';

	/**
	 * Send SMS
	 *
	 * @param   string  $message  sms message
	 *
	 * @return  void
	 */

	public function process($message = '')
	{
		$params = $this->getParams();
		$username = $params->get('sms-username');
		$password = $params->get('sms-password');
		$smsto = $params->get('sms-to');
		$smsfrom  = $params->get('sms-from');
		$smstos = explode(",", $smsto);
		foreach ($smstos as $smsto)
		{
			$url = sprintf($this->url, $username, $password, $smsfrom, $smsto, $message);
			$response = FabrikSMS::doRequest('GET', $url, '');
		}
	}

	/**
	 * Get plugin params
	 *
	 * @return  object  params
	 */

	private function getParams()
	{
		return $this->params;
	}

}
