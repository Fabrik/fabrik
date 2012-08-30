<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.subscriptions
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';
JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');

/**
 * Redirects the browser to subscriptions to perform payment
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.subscriptions
 * @since       3.0
 */

class plgFabrik_FormSubscriptions extends plgFabrik_Form
{

	protected $gateway = null;

	protected $billingCycle = null;

	/**
	 * Get the buisiness email either based on the accountemail field or the value
	 * found in the selected accoutnemail_element
	 *
	 * @param   object  $params  plugin params
	 *
	 * @return  string  email
	 */

	protected function getBusinessEmail($params)
	{
		$w = $this->getWorker();
		$data = $this->getEmailData();
		$field = $params->get('subscriptions_testmode') == 1 ? 'subscriptions_sandbox_email' : 'subscriptions_accountemail';
		return $w->parseMessageForPlaceHolder($this->params->get($field), $data);
	}

	/**
	 * Get transaction amount based on the cost field or the value
	 * found in the selected cost_element
	 *
	 * @param   object  $params  plugin params
	 *
	 * @return  string  cost
	 */

	protected function getAmount($params)
	{
		$billingCycle = $this->getBillingCycle();
		return $billingCycle->cost;
	}

	/**
	 * Get the select billing cycles row
	 *
	 * @return  object  row
	 */

	protected function getBillingCycle()
	{
		if (!isset($this->billingCycle))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$data = $this->getEmailData();
			$cycleId = (int) $data['jos_fabrik_subs_users___billing_cycle_raw'][0];
			$query->select('*')->from('#__fabrik_subs_plan_billing_cycle')->where('id = ' . $cycleId);
			$db->setQuery($query);
			$this->billingCycle = $db->loadObject();
		}
		return $this->billingCycle;
	}

	/**
	 * Get the selected gateway (paypal single payment / subscription)
	 *
	 * @return  object  row
	 */

	protected function getGateway()
	{
		if (!isset($this->gateway))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$data = $this->getEmailData();
			$id = (int) $data['jos_fabrik_subs_users___gateway_raw'][0];
			$query->select('*')->from('#__fabrik_subs_payment_gateways')->where('id = ' . $id);
			$db->setQuery($query);
			$this->gateway = $db->loadObject();
		}
		return $this->gateway;
	}

	/**
	 * Get transaction item name based on the item field or the value
	 * found in the selected item_element
	 *
	 * @return  array  item name
	 */

	protected function getItemName()
	{
		$data = $this->getEmailData();

		// @TODO replace with look up of plan name and billing cycle
		return array($data['jos_fabrik_subs_users___plan_id_raw'], $data['jos_fabrik_subs_users___plan_id'][0]);
	}

	/**
	 * Append additional paypal values to the data to send to paypal
	 *
	 * @param   array  &$opts  paypal options
	 *
	 * @return  void
	 */

	protected function setSubscriptionValues(&$opts)
	{
		$w = $this->getWorker();
		$config = JFactory::getConfig();
		$data = $this->getEmailData();

		$gateWay = $this->getGateway();

		$item = $data['jos_fabrik_subs_users___billing_cycle'][0] . ' ' . $data['jos_fabrik_subs_users___gateway'][0];
		$item_raw = $data['jos_fabrik_subs_users___billing_cycle_raw'][0];

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('cost, label, plan_name, duration AS p3, period_unit AS t3, ' . $db->quote($item_raw) . ' AS item_number ')
			->from('#__fabrik_subs_plan_billing_cycle')->where('id = ' . $db->quote($item_raw));

		$db->setQuery($query);
		$sub = $db->loadObject();

		// @TODO test replace various placeholders
		$name = $config->get('sitename') . ' {plan_name}  User: {jos_fabrik_subs_users___name} ({jos_fabrik_subs_users___username})';
		$tmp = array_merge(JRequest::get('data'), JArrayHelper::fromObject($sub));

		// 'http://fabrikar.com/ '.$sub->item_name. ' - User: subtest26012010 (subtest26012010)';
		$opts['item_name'] = $w->parseMessageForPlaceHolder($name, $tmp);
		$opts['invoice'] = uniqid('', true);

		if ($gateWay->subscription == 1)
		{
			if (is_object($sub))
			{
				$opts['p3'] = $sub->p3;
				$opts['t3'] = $sub->t3;
				$opts['a3'] = $sub->cost;
				$opts['no_note'] = 1;
				$opts['custom'] = '';
				$opts['src'] = 1;
				unset($opts['amount']);
			}
			else
			{
				JError::raiseError(500, 'Could not determine subscription period, please check your settings');
			}
		}
	}

	/**
	 * Get FabrkWorker
	 *
	 * @return FabrikWorker
	 */

	protected function getWorker()
	{
		if (!isset($this->w))
		{
			$this->w = new FabrikWorker;
		}
		return $this->w;
	}

	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @param   object  $params      plugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return	bool
	 */

	public function onAfterProcess($params, &$formModel)
	{
		$this->params = $params;
		$this->formModel = $formModel;
		$app = JFactory::getApplication();
		$data = $formModel->_fullFormData;
		$this->data = $data;
		if (!$this->shouldProcess('subscriptions_conditon'))
		{
			return true;
		}
		$emailData = $this->getEmailData();
		$w = $this->getWorker();
		$ipn = $this->getIPNHandler();

		$testMode = $this->params->get('subscriptions_testmode', false);
		$url = $testMode == 1 ? 'https://www.sandbox.paypal.com/us/cgi-bin/webscr?' : 'https://www.paypal.com/cgi-bin/webscr?';
		$opts = array();
		$gateway = $this->getGateway();
		$opts['cmd'] = $gateway->subscription ? '_xclick-subscriptions' : '_xclick';
		$opts['business'] = $this->getBusinessEmail($params);
		$opts['amount'] = $this->getAmount($params);
		list($item_raw, $item) = $this->getItemName($params);
		$opts['item_name'] = $item;
		$this->setSubscriptionValues($opts);

		$opts['currency_code'] = $this->getCurrencyCode();
		$opts['notify_url'] = $this->getNotifyUrl();
		$opts['return'] = $this->getReturnUrl();
		$opts['custom'] = $this->getCustom();
		$qs = array();
		foreach ($opts as $k => $v)
		{
			$qs[] = $k . '=' . $v;
		}
		$url .= implode('&', $qs);

		// $$$ rob 04/02/2011 no longer doing redirect from ANY plugin EXCEPT the redirect plugin
		// - instead a session var is set as the preferred redirect url

		$session = JFactory::getSession();
		$context = $formModel->getRedirectContext();

		$surl = (array) $session->get($context . 'url', array());
		$surl[$this->renderOrder] = $url;
		$session->set($context . 'url', $surl);

		// @TODO use JLog instead of fabrik log
		// JLog::add($subject . ', ' . $body, JLog::NOTICE, 'com_fabrik');
		return true;
	}

	/**
	 * Get the currency code for the transaction e.g. USD
	 *
	 * @return  string  currency code
	 */

	protected function getCurrencyCode()
	{
		$cycle = $this->getBillingCycle();
		$data = $this->getEmailData();
		return $this->getWorker()->parseMessageForPlaceHolder($cycle->currency, $data);
	}

	/**
	 * Create the custom string value you can pass to Paypal
	 *
	 * @return  string
	 */

	protected function getCustom()
	{
		return $this->data['formid'] . ':' . $this->data['rowid'];
	}

	/**
	 * Get the url that payment notifications (IPN) are sent to
	 *
	 * @return  string  url
	 */

	protected function getNotifyUrl()
	{
		$testSite = $this->params->get('subscriptions_test_site', '');
		$testSiteQs = $this->params->get('subscriptions_test_site_qs', '');
		$testMode = $this->params->get('subscriptions_testmode', false);
		$ppurl = ($testMode == 1 && !empty($testSite)) ? $testSite : COM_FABRIK_LIVESITE;
		$ppurl .= '/index.php?option=com_fabrik&task=plugin.pluginAjax&formid=' . $this->formModel->get('id')
			. '&g=form&plugin=subscriptions&method=ipn';
		if ($testMode == 1 && !empty($testSiteQs))
		{
			$ppurl .= $testSiteQs;
		}
		$ppurl .= '&renderOrder=' . $this->renderOrder;
		return urlencode($ppurl);
	}

	/**
	 * Make the return url, this is the page you return to after paypal has component the transaction.
	 *
	 * @return  string  url.
	 */

	protected function getReturnUrl()
	{
		$url = '';
		$testSite = $this->params->get('subscriptions_test_site', '');
		$testSiteQs = $this->params->get('subscriptions_test_site_qs', '');
		$testMode = (bool) $this->params->get('subscriptions_testmode', false);

		$qs = '/index.php?option=com_fabrik&task=plugin.pluginAjax&formid=' . $this->formModel->get('id')
			. '&g=form&plugin=subscriptions&method=thanks&rowid=' . $this->data['rowid'] . '&renderOrder=' . $this->renderOrder;

		if ($testMode)
		{
			$url = !empty($testSite) ? $testSite . $qs : COM_FABRIK_LIVESITE . $qs;
			if (!empty($testSiteQs))
			{
				$url .= $testSiteQs;
			}
		}
		else
		{
			$url = COM_FABRIK_LIVESITE . $qs;
		}
		return urlencode($url);
	}

	/**
	 * Thanks message
	 *
	 * @return  void
	 */

	public function onThanks()
	{
		$formid = JRequest::getInt('formid');
		$rowid = JRequest::getInt('rowid');
		JModel::addIncludePath(COM_FABRIK_FRONTEND . '/models');
		$formModel = JModel::getInstance('Form', 'FabrikFEModel');
		$formModel->setId($formid);
		$params = $formModel->getParams();
		$ret_msg = (array) $params->get('subscriptions_return_msg');
		$ret_msg = $ret_msg[JRequest::getInt('renderOrder')];
		if ($ret_msg)
		{
			$w = $this->getWorker();
			$listModel = $formModel->getlistModel();
			$row = $listModel->getRow($rowid);
			$ret_msg = $w->parseMessageForPlaceHolder($ret_msg, $row);
			if (JString::stristr($ret_msg, '[show_all]'))
			{
				$all_data = array();
				foreach ($_REQUEST as $key => $val)
				{
					$all_data[] = "$key: $val";
				}
				JRequest::setVar('show_all', implode('<br />', $all_data));
			}
			$ret_msg = str_replace('[', '{', $ret_msg);
			$ret_msg = str_replace(']', '}', $ret_msg);
			$ret_msg = $w->parseMessageForPlaceHolder($ret_msg, $_REQUEST);
			echo $ret_msg;
		}
		else
		{
			echo JText::_("thanks");
		}
	}

	/**
	 * Called from subscriptions at the end of the transaction
	 *
	 * @return  void
	 */

	public function onIpn()
	{
		$config = JFactory::getConfig();
		$log = FabTable::getInstance('log', 'FabrikTable');
		$log->referring_url = $_SERVER['REQUEST_URI'];
		$log->message_type = 'fabrik.ipn.start';
		$log->message = json_encode($_REQUEST);
		$log->store();

		// Lets try to load in the custom returned value so we can load up the form and its parameters
		$custom = JRequest::getVar('custom');
		list($formid, $rowid) = explode(":", $custom);

		// Pretty sure they are added but double add
		JModel::addIncludePath(COM_FABRIK_FRONTEND . '/models');
		$formModel = JModel::getInstance('Form', 'FabrikFEModel');
		$formModel->setId($formid);
		$listModel = $formModel->getlistModel();
		$params = $formModel->getParams();
		$table = $listModel->getTable();
		$db = $listModel->getDb();

		$renderOrder = JRequest::getInt('renderOrder');
		$ipn_txn_field = 'pp_txn_id';
		$ipn_payment_field = 'amount';

		$ipn_status_field = 'pp_payment_status';

		$w = $this->getWorker();

		$email_from = $admin_email = $config->get('mailfrom');

		// Read the post from Subscriptions system and add 'cmd'
		$req = 'cmd=_notify-validate';
		foreach ($_POST as $key => $value)
		{
			$value = urlencode(stripslashes($value));
			$req .= '&' . $key . '=' . $value;
		}

		// Post back to Subscriptions system to validate
		$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
		$header .= "Host: www.paypal.com:443\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . JString::strlen($req) . "\r\n\r\n";

		/* $test = '{"option":"com_fabrik","task":"plugin.pluginAjax","formid":"22","g":"form","plugin":"subscriptions","method":"ipn","XDEBUG_SESSION_START":"shiny","renderOrder":"2","txn_type":"subscr_signup","subscr_id":"I-YU0M7L86HA4T","last_name":"User","residence_country":"GB","mc_currency":"EUR","item_name":"fabrikar.com Monthly Professional  User: professional-recurring-monthly (professional-recurring-monthly)","business":"seller_1229696802_biz@pollen-8.co.uk","recurring":"1","address_street":"1 Main Terrace","verify_sign":"A4ffosV9eZnI9PfOxrUT6ColxyFXA.HeejgAGPEcuVvbmovNY04R-Or-","payer_status":"unverified","test_ipn":"1","payer_email":"buyer_1229696752_per@pollen-8.co.uk","address_status":"confirmed","first_name":"Test","receiver_email":"seller_1229696802_biz@pollen-8.co.uk","address_country_code":"GB","payer_id":"TUCWSC3SURGAN","invoice":"503df6ec03b469.74349485","address_city":"Wolverhampton","reattempt":"1","address_state":"West Midlands","subscr_date":"04:03:28 Aug 29, 2012 PDT","address_zip":"W12 4LQ","custom":"22:6703","charset":"windows-1252","notify_version":"3.5","period3":"1 M","address_country":"United Kingdom","mc_amount3":"40.00","address_name":"Test User","ipn_track_id":"a20981d869e98","Itemid":"77","view":"plugin"}';
		$test = JArrayHelper::fromObject(json_decode($test));
		echo "<pre>";print_r($test);exit; */

		$subscriptionsurl = ($_POST['test_ipn'] == 1) ? 'ssl://www.sandbox.paypal.com' : 'ssl://www.paypal.com';

		// Assign posted variables to local variables
		$item_name = JRequest::getVar('item_name');
		$item_number = JRequest::getVar('item_number');
		$payment_status = JRequest::getVar('payment_status');
		$payment_amount = JRequest::getVar('mc_gross');
		$payment_currency = JRequest::getVar('mc_currency');
		$txn_id = JRequest::getVar('txn_id');
		$txn_type = JRequest::getVar('txn_type');
		$receiver_email = JRequest::getVar('receiver_email');
		$payer_email = JRequest::getVar('payer_email');

		$status = 'ok';
		$err_msg = '';
		if (empty($formid) || empty($rowid))
		{
			$status = 'form.subscriptions.ipnfailure.custom_error';
			$err_msg = "formid or rowid empty in custom: $custom";
		}
		else
		{
			// @TODO implement a curl alternative as fsockopen is not always available
			$fp = fsockopen($subscriptionsurl, 443, $errno, $errstr, 30);
			if (!$fp)
			{
				$status = 'form.subscriptions.ipnfailure.fsock_error';
				$err_msg = "fsock error: $errno;$errstr";
			}
			else
			{
				fputs($fp, $header . $req);
				while (!feof($fp))
				{
					$res = fgets($fp, 1024);
					/*subscriptions steps (from their docs):
					 * check the payment_status is Completed
					 * check that txn_id has not been previously processed
					 * check that receiver_email is your Primary Subscriptions email
					 * check that payment_amount/payment_currency are correct
					 * process payment
					 */
					if (JString::strcmp($res, "VERIFIED") == 0)
					{

						$query = $db->getQuery(true);
						$query->select($ipn_status_field)->from('#__fabrik_subs_invoices')
							->where($db->quoteName($ipn_txn_field) . ' = ' . $db->quote($txn_id));
						$db->setQuery($query);
						$txn_result = $db->loadResult();
						if (!empty($txn_result))
						{
							if ($txn_result == 'Completed')
							{
								if ($payment_status != 'Reversed' && $payment_status != 'Refunded')
								{
									$status = 'form.subscriptions.ipnfailure.txn_seen';
									$err_msg = "transaction id already seen as Completed, new payment status makes no sense: $txn_id, $payment_status";
								}
							}
							elseif ($txn_result == 'Reversed')
							{
								if ($payment_status != 'Canceled_Reversal')
								{
									$status = 'form.subscriptions.ipnfailure.txn_seen';
									$err_msg = "transaction id already seen as Reversed, new payment status makes no sense: $txn_id, $payment_status";
								}
							}
						}
						if ($status == 'ok')
						{
							$set_list = array();

							$set_list[$ipn_txn_field] = $txn_id;
							$set_list[$ipn_payment_field] = $payment_amount;
							$set_list[$ipn_status_field] = $payment_status;

							$ipn = $this->getIPNHandler($params, $renderOrder);

							if ($ipn !== false)
							{
								$request = $_REQUEST;
								$ipn_function = 'payment_status_' . $payment_status;
								if (method_exists($ipn, $ipn_function))
								{
									$status = $ipn->$ipn_function($listModel, $request, $set_list, $err_msg);
									if ($status != 'ok')
									{
										break;
									}
								}
								$txn_type_function = 'txn_type_' . $txn_type;
								if (method_exists($ipn, $txn_type_function))
								{
									$status = $ipn->$txn_type_function($listModel, $request, $set_list, $err_msg);
									if ($status != 'ok')
									{
										break;
									}
								}
							}

							if (!empty($set_list))
							{
								$set_array = array();
								foreach ($set_list as $set_field => $set_value)
								{
									$set_value = $db->quote($set_value);
									$set_field = $db->quoteName($set_field);
									$set_array[] = "$set_field = $set_value";
								}
								$query = $db->getQuery(true);
								$query->update('#__fabrik_subs_invoices')->set(implode(',', $set_array))->where('id = ' . $db->quote($rowid));
								$db->setQuery($query);
								if (!$db->query())
								{
									$status = 'form.subscriptions.ipnfailure.query_error';
									$err_msg = 'sql query error: ' . $db->getErrorMsg();
								}
							}
						}
					}
					elseif (JString::strcmp($res, "INVALID") == 0)
					{
						$status = 'form.subscriptions.ipnfailure.invalid';
						$err_msg = 'subscriptions postback failed with INVALID';
					}
				}
				fclose($fp);
			}
		}

		$receive_debug_emails = (array) $params->get('subscriptions_receive_debug_emails');
		$receive_debug_emails = $receive_debug_emails[$renderOrder];
		$send_default_email = (array) $params->get('subscriptions_send_default_email');
		$send_default_email = $send_default_email[$renderOrder];
		if ($status != 'ok')
		{
			foreach ($_POST as $key => $value)
			{
				$emailtext .= $key . " = " . $value . "\n\n";
			}

			if ($receive_debug_emails == '1')
			{
				$subject = $config->get('sitename') . ": Error with Fabrik Subscriptions IPN";
				JUtility::sendMail($email_from, $email_from, $admin_email, $subject, $emailtext, false);
			}
			$log->message_type = $status;
			$log->message = $emailtext . "\n//////////////\n" . $res . "\n//////////////\n" . $req . "\n//////////////\n" . $err_msg;
			if ($send_default_email == '1')
			{
				$payer_emailtext = "There was an error processing your Subscriptions payment.  The administrator of this site has been informed.";
				JUtility::sendMail($email_from, $email_from, $payer_email, $subject, $payer_emailtext, false);
			}
		}
		else
		{
			foreach ($_POST as $key => $value)
			{
				$emailtext .= $key . " = " . $value . "\n\n";
			}
			if ($receive_debug_emails == '1')
			{
				$subject = $config->get('sitename') . ': IPN ' . $payment_status;
				JUtility::sendMail($email_from, $email_from, $admin_email, $subject, $emailtext, false);
			}
			$log->message_type = 'form.subscriptions.ipn.' . $payment_status;
			$query = $db->getQuery();
			$log->message = $emailtext . "\n//////////////\n" . $res . "\n//////////////\n" . $req . "\n//////////////\n" . $query;

			if ($send_default_email == '1')
			{
				$payer_subject = "Subscriptions success";
				$payer_emailtext = "Your Subscriptions payment was succesfully processed.  The Subscriptions transaction id was $txn_id";
				JUtility::sendMail($email_from, $email_from, $payer_email, $payer_subject, $payer_emailtext, false);
			}
		}

		$log->message .= "\n IPN custom function = $ipn_function";
		$log->message .= "\n IPN custom transaction function = $txn_type_function";
		$log->store();
		jexit();
	}

	/* public function getBottomContent($params, $formModel)
	{
		$this->params = $params;
		$this->formModel = $formModel;
	}

	public function getBottomContent_result($c)
	{
		$this->renderOrder = $c;
		echo "notify url = " . $this->getNotifyUrl();;
		return $this->getNotifyUrl();
	} */

	/**
	 * Get the custom IPN class
	 *
	 * @return	object	ipn handler class
	 */

	protected function getIPNHandler()
	{
		require_once 'plugins/fabrik_form/subscriptions/scripts/ipn.php';
		return new fabrikSubscriptionsIPN;
	}
}
