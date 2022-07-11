<?php
/**
 * Optional script for extending the Fabrik PayPal form plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.paypal
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/*
 * In the PayPal form plugin settings, you can select a PHP script to use for custom IPN processing.
 * You should copy this file, and use it as your starting point.  You must not change the class name.
 * During IPN processing, the PayPal plugin will create an instance of this class, and if there is a method
 * named after the 'payment_status' or 'txn_type' specified by PayPal, with payment_status_ or txn_type_ prepended
 * (like payment_type_Completed), the plugin will call your method, passing it a reference to the current Fabrik tableModel,
 * the request params, the 'set_list' and 'err_msg'.
 *
 * The $listModel allows you to access all the usual data about the table.  See the Fabrik code for details on
 * what you can do with this.
 *
 * The $request is just a copy of $_REQUEST, and will contain all the usual IPN keys/values PayPal send us,
 * like $request['pending_reason'] or $request['mc_shipping'] etc.
 *
 * The $set_list is an array, which contains the table updates the plugin is already going to make.  Array entries
 * will look like ...
 *    $set_list['your_status_field'] = 'Completed'
 * ... and you can change, add or remove as you see fit.  So if you have a custom field you want updated, just add
 * it to the arrays like ...
 *    $set_list['my_custom_field'] = "foo";
 * .... and the plugin will automatically add that to the UPDATE query for the row being processed.
 * (including Quote and quoteName of fields)
 *
 * The $err_msg is used if you wish to abort processing by returning a status of something other than 'ok',
 * and will be included in any error / debug reporting done by the plugin.
 *
 * Your method MUST return either 'ok' to continue processing, or anything other than 'ok' to abort.  We suggest
 * your error code be form.paypal.ipnfailure.your_code (replace your_code with some informative code).  So, to
 * return an error and abort processing:
 *    $err_msg = "Something is horribly wrong!";
 *    return "form.paypal.ipnfailure.horribly_wrong";
 *
 * We have included simple do-nothing methods for the most common paypal_status values, and some example code
 * for sending some emails on payment_status_Pending, but it is by no means
 * and exhaustive list.  To add a new one, for instance for Voided, just create a payment_status_Voided() method.
 *
 * IMPORTANT NOTE - during development of your script, you REALLY MUST use the PayPal developer sandbox!!
 */

class fabrikPayPalIPN
{

	/**
	 *
	 * In this example, we are assuming you have a form with a JUser and a PayPal plugin on it.,
	 * where you want people to pay for signing up.  Because the JUser plugin creates the user BEFORE
	 * the PayPal plugin runs, we don't know if the user ever hit "Pay" in PayPal.  So, we initially set
	 * the user to be blocked (inactive, see Juser plugin settings).  We then use this IPN 'completed' method
	 * to unblock the user when the payment confirmation IPN response arrive from PayPal.
	 *
	 * Obviously you will need to change the table and element names you are using for your registration form
	 * to suit your setup.
	 *
	 * You may also wish to check that the $amount_paid ('mc_gross' attribute from PayPal) is the correct amount,
	 * to prevent people from changing the amount they are paying from the PayPal redirect to (say) $1.
	 *
	 * @param unknown_type $listModel
	 * @param unknown_type $request
	 * @param unknown_type $set_list
	 * @param unknown_type $err_msg
	 * @return string
	 */
	function payment_status_Completed($listModel, $request, &$set_list, &$err_msg)
	{
		// Get the 'custom' values from the IPN call, which we provide by default
		// when redirecting the original form submission to PayPal.
		$custom = $request['custom'];
		list($formid, $rowid, $ipn_value) = explode(":", $custom);
		$amount_paid = $request['mc_gross'];
		$db = $listModel->getDb();
		// See if we can find the corresponding row from our registration table,
		// and fetch our userid element from it.  The PayPal plugin will have written
		// the newly created userid in to it during the original form submission.
		$db->setQuery("SELECT `userid` FROM `registration_individual` WHERE `id` = " . $db->quote($rowid));
		$userid = $db->loadResult();
		if (!empty($userid) && (int) $userid > 42)
		{
			// If we found the userid, and it is in the normal user range, set the 'block' field in J!'s
			// user table to 0.
			$db->setQuery("UPDATE `#__users` SET `block` = '0' WHERE `id` = " . $db->quote($userid));
			$db->execute();
			// Also set the block field in our registration table to 0.
			$db->setQuery("UPDATE `registration_individual` SET `block` = '0' WHERE `id` = " . $db->quote($rowid));
			$db->execute();
		}
		return 'ok';
	}

	/**
	 * This function will send an email to both the payer and the payee emails, letting you both know that the payment
	 * is in a pending state.
	 *
	 * @param unknown_type $listModel
	 * @param unknown_type $request
	 * @param unknown_type $set_list
	 * @param unknown_type $err_msg
	 * @return string
	 */
	function payment_status_Pending($listModel, $request, &$set_list, &$err_msg)
	{
		$config = JFactory::getConfig();
		$MailFrom = $config->get('mailfrom');
		$FromName = $config->get('fromname');
		$SiteName = $config->get('sitename');

		$payer_email = $request['payer_email'];
		$receiver_email = $request['receiver_email'];

		$subject = "%s - Payment Pending";
		$subject = sprintf($subject, $SiteName);
		$subject = html_entity_decode($subject, ENT_QUOTES);

		$msgbuyer = 'Your payment on %s is pending. (Paypal transaction ID: %s)<br /><br />%s';
		$msgbuyer = sprintf($msgbuyer, $SiteName, $txn_id, $SiteName);
		$msgbuyer = html_entity_decode($msgbuyer, ENT_QUOTES);
		$mail = JFactory::getMailer();
		$res = $mail->sendMail($MailFrom, $FromName, $payer_email, $subject, $msgbuyer, true);

		$msgseller = 'Payment pending on %s. (Paypal transaction ID: %s)<br /><br />%s';
		$msgseller = sprintf($msgseller, $SiteName, $txn_id, $SiteName);
		$msgseller = html_entity_decode($msgseller, ENT_QUOTES);
		$mail = JFactory::getMailer();
		$res = $mail->sendMail($MailFrom, $FromName, $payer_email, $subject, $msgseller, true);
		return 'ok';
	}

	/**
	 *
	 * This code will block the user if they reverse their payment.
	 *
	 * @param unknown_type $listModel
	 * @param unknown_type $request
	 * @param unknown_type $set_list
	 * @param unknown_type $err_msg
	 * @return string
	 */
	function payment_status_Reversed($listModel, $request, &$set_list, &$err_msg)
	{
		$custom = $request['custom'];
		list($formid, $rowid, $ipn_value) = explode(":", $custom);
		$db = $listModel->getDb();
		$db->setQuery("SELECT `userid` FROM `registration_individual` WHERE `id` = " . $db->quote($rowid));
		$userid = $db->loadResult();
		if (!empty($userid) && (int) $userid > 42)
		{
			$db->setQuery("UPDATE `#__users` SET `block` = '1' WHERE `id` = " . $db->quote($userid));
			$db->execute();
			$db->setQuery("UPDATE `registration_individual` SET `block` = '1' WHERE `id` = " . $db->quote($rowid));
			$db->execute();
		}
		return 'ok';
	}

	function payment_status_Cancelled_Reversal($listModel, $request, &$set_list, &$err_msg)
	{
		return 'ok';
	}

	function payment_status_Refunded($listModel, $request, &$set_list, &$err_msg)
	{
		return 'ok';
	}

	function txn_type_web_accept($listModel, $request, &$set_list, &$err_msg)
	{
		return 'ok';
	}

	function txn_type_subscr_signup($listModel, $request, &$set_list, &$err_msg)
	{
		return 'ok';
	}

	function txn_type_subscr_cancel($listModel, $request, &$set_list, &$err_msg)
	{
		return 'ok';
	}

	function txn_type_subscr_modify($listModel, $request, &$set_list, &$err_msg)
	{
		return 'ok';
	}

	function txn_type_subscr_payment($listModel, $request, &$set_list, &$err_msg)
	{
		return 'ok';
	}

	function txn_type_subscr_failed($listModel, $request, &$set_list, &$err_msg)
	{
		return 'ok';
	}

	function txn_type_subscr_eot($listModel, $request, &$set_list, &$err_msg)
	{
		return 'ok';
	}

}
