<?php
/**
 * Subscription IPN return code
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.subscriptions
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

JTable::addIncludePath(JPATH_SITE . '/plugins/fabrik_form/subscriptions/tables');

/**
 * Subscription IPN return code
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.subscriptions
 * @since       3.0
*/

class FabrikSubscriptionsIPN
{
	/**
	 * Construct
	 */
	public function __construct()
	{
		// Include the JLog class.
		jimport('joomla.log.log');

		// Add the logger.
		JLog::addLogger(array('text_file' => 'fabrik.subs.log.php'));
	}

	/**
	 * Completed payment
	 *
	 * @param   object  $listModel  List model
	 * @param   array   $request    Request data
	 * @param   array   &$set_list  New invoice properties
	 * @param   array   &$err_msg   Error message
	 *
	 * @return bool
	 */

	public function payment_status_Completed($listModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass;
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.payment_status_Completed', $msg);
		return $this->activateSubscription($listModel, $request, $set_list, $err_msg, false);
	}

	/**
	 * Activate a subscription
	 *
	 * @param   object  $listModel  List model
	 * @param   array   $request    Request data
	 * @param   array   &$set_list  New invoice properties
	 * @param   array   &$err_msg   Error message
	 * @param   bool    $recurring  Is it a recurring subscription
	 *
	 * @return  bool
	 */
	protected function activateSubscription($listModel, $request, &$set_list, &$err_msg, $recurring = true)
	{
		$db = JFactory::getDbo();
		$mail = JFactory::getMailer();
		$app = JFactory::getApplication();

		$invoice = $this->checkInvoice($request);
		if ($invoice === false)
		{
			$this->reportError('Activate subscription: failed invoice check');
			return false;
		}

		// Update subscription details
		$inv = $this->getInvoice($invoice);
		if ($inv === false)
		{
			$this->reportError('Activate subscription: didn\'t load invoice id: ' . $invoice);
			return false;
		}

		$sub = $this->getSubscriptionFromInvoice($invoice);
		if ($sub === false)
		{
			$this->reportError('Activate subscription: didn\'t load sub for invoice id: ' . $invoice);
			return false;
		}
		$sub->activate();

		// Update invoice status
		$inv->update($request);

		// Set user to desired group
		$subUser = $this->setUserGroup($sub);
		if (!$subUser)
		{
			$this->reportError('Activate subscription: couldn\'t load or set groups on user');
			return false;
		}

		$subErrors = $subUser->getErrors();
		if (!empty($subErrors))
		{
			$msg = $subUser->getError() . "<br>" . $subUser->get('email') . " / userid = " . $subUser->get('id') . ' NOT set to ' . $sub;
			$this->reportError($msg);
		}
		else
		{
			$msg = $subUser->get('id') . ' set to ' . implode(', ', $subUser->groups) . "\n last error in user : " . $subUser->getError() . "\n " . $db->getErrorMsg();
			$this->log('fabrik.ipn.setusergid', $msg);
		}

		$mailFrom = $app->getCfg('mailfrom');
		$fromName = $app->getCfg('fromname');
		$siteName = $app->getCfg('sitename');

		$txn_id = $request['txn_id'];
		$payer_email = $request['payer_email'];
		$receiver_email = $request['receiver_email'];

		$subject = $recurring ? "%s - Subscription payment complete" : "%s - Payment complete";
		$subject = sprintf($subject, $siteName);
		$subject = html_entity_decode($subject, ENT_QUOTES);
		$type = $recurring ? 'subscription payment' : 'payment';
		$msgbuyer = 'Your ' . $type . ' on %s has successfully completed. (Paypal transaction ID: %s)<br /><br />%s';
		$msgbuyer = sprintf($msgbuyer, $siteName, $txn_id, $siteName);
		$msgbuyer = html_entity_decode($msgbuyer, ENT_QUOTES);

		JLog::add('fabrik.ipn.activateSubscription', JLog::INFO, $payer_email . ', ' . $msgbuyer);
		$mail->sendMail($mailFrom, $fromName, $payer_email, $subject, $msgbuyer, true);

		$msgseller = $type . ' success on %s. (Paypal transaction ID: %s)<br /><br />%s';
		$msgseller = sprintf($msgseller, $siteName, $txn_id, $siteName);
		$msgseller = html_entity_decode($msgseller, ENT_QUOTES);


		$mail->sendMail($mailFrom, $fromName, $receiver_email, $subject, $msgseller, true);
		$this->expireOldSubs($subUser->get('id'));
		return true;
	}

	/**
	 * Set the users groups based on the subscription
	 *
	 * @param   JTable  $sub  Subscription table
	 *
	 * @return  JUser  Subscription user
	 */

	protected function setUserGroup($sub)
	{
		$subUser = JFactory::getUser($sub->userid);
		$this->log('fabrik.ipn.txn_type_subscr_payment sub userid', $subUser->get('id'));
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('usergroup')->from('#__fabrik_subs_plans')->where('id = ' . $sub->plan);
		$db->setQuery($query);
		$gid = $db->loadResult();
		$this->log('fabrik.ipn.txn_type_subscr_payment gid query', $db->getQuery());
		$groups = JUserHelper::getUserGroups($subUser->id);
		$subUser->groups = array_merge($groups, (array) $gid);
		$subUser->save();
		return $subUser;
	}

	/**
	 * Expire all but the most recent subs
	 *
	 * @param   int  $userid  User id
	 *
	 * @return  void
	 */
	protected function expireOldSubs($userid)
	{
		JLog::add('fabrik.ipn.expireOldSubs.start', JLog::INFO, 'expired old subs for ' . $userid);
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Don't load up active accounts with no eot_date!
		$query->select('id')->from('#__fabrik_subs_subscriptions')->where('userid = ' . (int) $userid)
		->where('status = "Active" AND date(format(eot_date, "%Y") = "000"')->order('lastpay_date DESC');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		if (count($rows) > 1)
		{
			// User can have up to one active subscription - if there's more we're going to expire the older ones
			for ($i = 1; $i < count($rows); $i++)
			{
				$sub = JTable::getInstance('Subscription', 'FabrikTable');
				$subid = (int) $rows[$i]->id;
				if ($subid !== 0)
				{
					$sub->load($subid);
					$sub->expire('Expire Old Subs');
				}
			}
		}
		$msg = new stdClass;
		$msg->subscriptionids = $rows;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.expireOldSubs.end', $msg);
	}

	/**
	 * Pending payment
	 *
	 * @param   object  $listModel  List model
	 * @param   array   $request    Request data
	 * @param   array   &$set_list  New invoice properties
	 * @param   array   &$err_msg   Error message
	 *
	 * @return bool
	 */

	public function payment_status_Pending($listModel, $request, &$set_list, &$err_msg)
	{
		$this->log('fabrik.ipn.payment_status_Pending', '');
		$app = JFactory::getApplication();
		$MailFrom = $app->getCfg('mailfrom');
		$FromName = $app->getCfg('fromname');
		$SiteName = $app->getCfg('sitename');

		$payer_email = $request['payer_email'];
		$receiver_email = $request['receiver_email'];

		$subject = "%s - Payment Pending";
		$subject = sprintf($subject, $SiteName);
		$subject = html_entity_decode($subject, ENT_QUOTES);

		$msgbuyer = 'Your payment on %s is pending. (Paypal transaction ID: %s)<br /><br />%s';
		$txn_id = $app->input->get('txn_id', 'n/a');
		$msgbuyer = sprintf($msgbuyer, $SiteName, $txn_id, $SiteName);
		$msgbuyer = html_entity_decode($msgbuyer, ENT_QUOTES);
		JFactory::getMailer()->sendMail($MailFrom, $FromName, $payer_email, $subject, $msgbuyer, true);

		$msgseller = 'Payment pending on %s. (Paypal transaction ID: %s)<br /><br />%s';
		$msgseller = sprintf($msgseller, $SiteName, $txn_id, $SiteName);
		$msgseller = html_entity_decode($msgseller, ENT_QUOTES);
		JFactory::getMailer()->sendMail($MailFrom, $FromName, $receiver_email, $subject, $msgseller, true);
		return true;
	}

	/**
	 * Reversed payment
	 *
	 * @param   object  $listModel  List model
	 * @param   array   $request    Request data
	 * @param   array   &$set_list  New invoice properties
	 * @param   array   &$err_msg   Error message
	 *
	 * @return bool
	 */

	public function payment_status_Reversed($listModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass;
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.payment_status_Reversed', $msg);
		return true;
	}

	/**
	 * Cancelled reversed payment
	 *
	 * @param   object  $listModel  List model
	 * @param   array   $request    Request data
	 * @param   array   &$set_list  New invoice properties
	 * @param   array   &$err_msg   Error message
	 *
	 * @return bool
	 */

	public function payment_status_Cancelled_Reversal($listModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass;
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.payment_status_Cancelled_Reversal', $msg);
		return true;
	}

	/**
	 * Refunded payment and cancel subscription
	 *
	 * @param   object  $listModel  List model
	 * @param   array   $request    Request data
	 * @param   array   &$set_list  New invoice properties
	 * @param   array   &$err_msg   Error message
	 *
	 * @return bool
	 */

	public function payment_status_Refunded($listModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass;
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.payment_status_Refunded', $msg);
		$invoice = $this->checkInvoice($request);
		if (!$invoice)
		{
			return false;
		}
		$sub = $this->getSubscriptionFromInvoice($invoice);
		$sub->refund();
		$this->recalibrateUser($sub->userid);
		return true;
	}

	/**
	 * If user subs changed then this fn will work out and set the correct
	 * access levels for the user
	 *
	 * @param   int  $userId  User id
	 *
	 * @return  JUser
	 */

	public function recalibrateUser($userId)
	{
		$user = JFactory::getUser($userId);
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('usergroup')->from('#__fabrik_subs_subscriptions AS s')
		->join('LEFT', '#__fabrik_subs_plans AS p ON s.plan = p.id')
		->where('s.userid = ' . $user->id . ' AND s.status = "Active"');

		$db->setQuery($query);
		$groups = $db->loadColumn();

		// Get the base user group - normally 'registered' by may be set otherwise
		$config = JComponentHelper::getParams('com_users');
		$gid = $config->get('new_usertype');
		$groups[] = $gid;
		$groups = array_unique($groups);
		$this->log('fabrik.subs.recalibrateUser', $user->get('id') . ' groups: ' . implode(',', $groups));

		$user->groups = $groups;
		$user->save();
		return $user;

	}

	/**
	 * Transaction web accept
	 *
	 * @param   object  $listModel  List model
	 * @param   array   $request    Request data
	 * @param   array   &$set_list  New invoice properties
	 * @param   array   &$err_msg   Error message
	 *
	 * @return bool
	 */

	public function txn_type_web_accept($listModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass;
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.txn_type_web_accept', $msg);
		return true;
	}

	/**
	 * Occurs when someone first signs up for a subscription,
	 * you should get a subscr_payment about 3 seconds afterwards.
	 * So again I don't think we need to do anything here
	 *
	 * @param   object  $listModel  List model
	 * @param   array   $request    Request data
	 * @param   array   &$set_list  New invoice properties
	 * @param   array   &$err_msg   Error message
	 *
	 * @return bool
	 */

	public function txn_type_subscr_signup($listModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass;
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.txn_type_subscr_signup', $msg);
		return true;
	}

	/**
	 * The user has cancelled a subscription in Paypal,
	 *
	 * @param   object  $listModel  List model
	 * @param   array   $request    Request data
	 * @param   array   &$set_list  New invoice properties
	 * @param   array   &$err_msg   Error message
	 *
	 * @return bool
	 */

	public function txn_type_subscr_cancel($listModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass;
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.txn_type_subscr_cancel', $msg);
		$invoice = $this->checkInvoice($request);
		if (!$invoice)
		{
			$this->log('fabrik.ipn.txn_type_subscr_cancel invoice not found', $invoice . ' not found so didnt cancel a subscription');
			return false;
		}
		$sub = $this->getSubscriptionFromInvoice($invoice);
		if ($sub === false)
		{
			$this->log('fabrik.ipn.txn_type_subscr_cancel subscription not found', $invoice . ' not found so didnt cancel a subscription');
			return false;
		}
		$sub->cancel();
		$this->recalibrateUser($sub->userid);
		return true;
	}

	/**
	 * this occurs when a user upgrades their account in Paypal
	 * We don't allow this in fabrik so nothing should need to be done here
	 *
	 * @param   object  $listModel  List model
	 * @param   array   $request    Request data
	 * @param   array   &$set_list  New invoice properties
	 * @param   array   &$err_msg   Error message
	 *
	 * @return bool
	 */

	public function txn_type_subscr_modify($listModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass;
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.txn_type_subscr_modify', $msg);
		return true;
	}

	/**
	 * A subscription payment has been successfully made
	 *
	 * @param   object  $listModel  List model
	 * @param   array   $request    Request data
	 * @param   array   &$set_list  New invoice properties
	 * @param   array   &$err_msg   Error message
	 *
	 * @return bool
	 */

	public function txn_type_subscr_payment($listModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass;
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.txn_type_subscr_payment', $msg);
		return $this->activateSubscription($listModel, $request, $set_list, $err_msg, true);
	}

	/**
	 * this gets triggered when Paypal tries to charge the user for a recurring subscription
	 * but there are not enough funds in the account
	 * The user is emailed by Paypal regarding the issue
	 * If you have paypal's 'Re-attempt on Failure option' option turned on then Paypal will try to
	 * re-send the charge 3 days after the initial failed request.
	 * Once the max number of failures has occurred Paypal sends out a thx_type_subscr_cancel call
	 * Its there that we need to do something
	 *
	 * @param   object  $listModel  List model
	 * @param   array   $request    Request data
	 * @param   array   &$set_list  New invoice properties
	 * @param   array   &$err_msg   Error message
	 *
	 * @return bool
	 */

	public function txn_type_subscr_failed($listModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass;
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.txn_type_subscr_failed', $msg);
		return true;
	}

	/**
	 * Seems to get called when you do a silver paypal payment (not sub)
	 * but as it occurs before anything else (e.g. form.paypal.ipn.completed
	 * the expired invoice doesn't rest expired but shows as active
	 *
	 * @param   object  $listModel  List model
	 * @param   array   $request    Request data
	 * @param   array   &$set_list  New invoice properties
	 * @param   array   &$err_msg   Error message
	 *
	 * @return bool
	 */

	public function txn_type_subscr_eot($listModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass;
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.txn_type_subscr_eot', $msg);
		$invoice = $this->checkInvoice($request);
		if (!$invoice)
		{
			$this->log('fabrik.ipn.txn_type_subscr_eot', 'no invoice found for :' . json_encode($request));
			return false;
		}
		$sub = $this->getSubscriptionFromInvoice($invoice);
		if ($sub->recurring != 1)
		{
			$this->log('fabrik.ipn.txn_type_subscr_eot', 'not expiring as sub is not recurring (so eot is triggered on sub signup)');
			/*
			 * $$$ rob 09/06/2011 added cos I think if user has one sub non recurring
			* and that expires and they sign up for a new one (possibly before the
			 * end of the first subs term, both subs are expired if we don't return here
			 */
			return true;
		}
		$sub->expire('Expired');
		$this->recalibrateUser($sub->userid);
		return true;
	}

	/**
	 * Get subscription row from a given invoice number
	 *
	 * @param   string  $inv  Invoice id
	 *
	 * @return  JTable object
	 */

	private function getSubscriptionFromInvoice($inv)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('subscr_id')->from('#__fabrik_subs_invoices')->where('id = ' . $db->quote($inv));
		$db->setQuery($query);
		$subid = (int) $db->loadResult();
		if ($subid === 0)
		{
			return false;
		}
		$sub = JTable::getInstance('Subscription', 'FabrikTable');
		$sub->load($subid);
		return $sub;
	}

	/**
	 * Get an invoice JTable object from its invoice number
	 *
	 * @param   string  $inv  Invoice number
	 *
	 * @return JTable
	 */

	private function getInvoice($inv)
	{
		$row = JTable::getInstance('Invoice', 'FabrikTable');
		$row->load($inv);
		return $row;
	}

	/**
	 *Report an error by email and to fabrik.subs.log.php
	 *
	 * @param   string  $msg   Message
	 * @param   string  $to    To
	 * @param   array   $data  Data to log
	 *
	 * @return unknown_type
	 */

	private function reportError($msg, $to = '', $data = array())
	{
		$app = JFactory::getApplication();
		$MailFrom = $app->getCfg('mailfrom');
		$FromName = $app->getCfg('fromname');
		if ($to === '')
		{
			$to = $FromName;
		}
		$body = $msg . "\n\n\\";
		foreach ($data as $k => $v)
		{
			$body .= "$k = $v \n";
		}
		$subject = 'fabrik.ipn.fabrikar_subs error';
		JFactory::getMailer()->sendMail($MailFrom, $FromName, $to, $subject, $body);

		// Start logging...
		JLog::add($body, JLog::ERROR, $subject);
	}

	/**
	 * Log something
	 *
	 * @param   string  $subject  Log subject
	 * @param   string  $body     Log message
	 *
	 * @return  void
	 */

	private function log($subject, $body)
	{
		// Start logging...
		JLog::add($body, JLog::NOTICE, $subject);
	}

	/**
	 * Ensures that an invoice num was found in the request data.
	 *
	 * @param   array  $request  Data to check
	 *
	 * @return  mixed	false if not found, otherwise returns invoice id
	 */

	private function checkInvoice($request)
	{
		$invoice = $request['invoiceid'];
		$receiver_email = $request['receiver_email'];

		// Eekk no invoice number found in returned data - inform the sys admin
		if ($invoice === '')
		{
			$this->reportError('missing invoice', $receiver_email, $request);
			return false;
		}
		return $invoice;
	}

	/**
	 * If the plan has a fall back plan -
	 * e.g. original subscription was bronze, updates to silver and silver expires - should fall back to bronze
	 * we want to change the user type
	 *
	 * @param   object  $sub  Subscription
	 *
	 * @deprecated - should use recalibrateUser();
	 *
	 * @return  void
	 */

	public function fallbackPlan($sub)
	{
		// @TODO need to do this with the respect to the plan billing cycles

		return;
		$mail = JFactory::getMailer();
		$plan = JTable::getInstance('Plan', 'FabrikTable');
		$newPlan = JTable::getInstance('Plan', 'FabrikTable');
		$plan->load((int) $sub->plan);
		$this->log('fabrik.ipn. fallback', ' getting fallback sub plan :  ' . (int) $sub->plan . ' = ' . (int) $plan->fall_back_plan);
		$fallback = false;
		if ($plan->fall_back_plan != 0)
		{
			$fallback = true;
			$newPlan->load((int) $plan->fall_back_plan);
			$gid = (int) $newPlan->usergroup;
			if ($gid < 18)
			{
				$gid = 18;
			}
		}
		else
		{
			$gid = 18;
		}
		$subUser = JFactory::getUser($sub->userid);
		$subUser->gid = $gid;
		$this->log('fabrik.ipn. fallback', $subUser->get('id') . ' gid set to ' . $gid);
		$subUser->save();

		if ($fallback)
		{
			// Create new subscription for fall back plan

			// Get the expiration date (length of new plan - that of previous plan)
			$newLength = $this->charToPeriod($newPlan->period_unit);
			$oldLength = $this->charToPeriod($plan->period_unit);
			$expDate = strtotime("+{$newPlan->duration} $newLength	");

			$minus = strtotime("-{$plan->duration} $oldLength");

			$this->log('fabrik.ipn. fallback', 'expiration date = strtotime(+' . $newPlan->duration . ' ' . $newLength . ")\n minus = strtotime(-" . $plan->duration . ' ' . $oldLength . ") \n =: $expDate - $minus");
			$expDate = JFactory::getDate()->toUnix() - ( $expDate - $minus);

			$sub = JTable::getInstance('Subscriptions', 'Table');
			$sub->userid = $subUser->get('id');

			// Paypal payment - no recurring
			$sub->type = 1;
			$sub->status = 'Active';
			$sub->signup_date = JFactory::getDate()->toSQL();
			$sub->plan = $newPlan->id;
			$sub->recurring = 0;
			$sub->lifetime = 0;
			$sub->expiration = JFactory::getDate($expDate)->toSql();
			$this->log('fabrik.ipn. fallback', 'new sub expiration set to ' . $sub->expiration);
			$sub->store();
			$msg = "<h3>new sub expiration set to $sub->expiration</h3>";
			foreach ($sub as $k => $v)
			{
				$msg .= "$k = $v <br>";
			}
			$mail->sendMail('rob@pollen-8.co.uk', 'fabrikar subs', 'rob@pollen-8.co.uk', 'fabrikar sub: fall back plan created', $msg, true);
		}
	}

	/**
	 * Turn a time period character into its full name
	 *
	 *  @param   string  $p  Period character
	 *
	 *  @return  string  name
	 */

	private function charToPeriod($p)
	{

		switch ($p)
		{
			case 'D':
				$newLength = 'day';
				break;
			case 'M':
				$newLength = 'month';
				break;
			case 'Y':
				$newLength = 'year';
				break;
			default:
				$newLength = '';
				break;
		}
		return $newLength;
	}

}
