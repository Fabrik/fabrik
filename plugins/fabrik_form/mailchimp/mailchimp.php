<?php
/**
 * Add a user to a mailchimp mailing list
 * @package Joomla
 * @subpackage Fabrik
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-form.php');
require_once('MCAPI.class.php');

class plgFabrik_FormMailchimp extends plgFabrik_Form 
{

	var $html = null;

	/**
	 * set up the html to be injected into the bottom of the form
	 *
	 * @param object $params (no repeat counter stuff needed here as the plugin manager
	 * which calls this function has already done the work for you
	 */

	function getBottomContent(&$params)
	{
			$this->html = "
			<label class=\"mailchimpsignup\"><input type=\"checkbox\" name=\"fabrik_mailchimp_signup\" class=\"fabrik_mailchimp_signup\" value=\"1\"  />
			 ".$params->get('mailchimp_signuplabel') . "</label>";
	}

	/**
	 * inject custom html into the bottom of the form
	 * @param int plugin counter
	 * @return string html
	 */

	function getBottomContent_result($c)
	{
		return $this->html;
	}

	/**
	 * process the plugin, called when form is submitted
	 *
	 * @param object $params
	 * @param object form
	 */

	function onAfterProcess($params, &$formModel)
	{
		$this->formModel = $formModel;
		$emailData = $this->getEmailData();
		$post = JRequest::get('post');
		if (!array_key_exists('fabrik_mailchimp_signup', $post)) {
			return;
		}
		$listId = $params->get('mailchimp_listid');
		$apiKey = $params->get('mailchimp_apikey');
		if ($apiKey == '') {
			JError::raiseNotice(500, 'Mailchimp: no api key specified');
			return;
		}
		if ($listId == '') {
			JError::raiseNotice(500, 'Mailchimp: no list id specified');
			return;
		}

		$api = new MCAPI($params->get('mailchimp_apikey'));

		$opts = array();

		$emailKey = $formModel->getElement($params->get('mailchimp_email'), true)->getFullName();
		$firstNameKey = $formModel->getElement($params->get('mailchimp_firstname'), true)->getFullName();
		if ($params->get('mailchimp_lastname') !== '') {
			$lastNameKey = $formModel->getElement($params->get('mailchimp_lastname'), true)->getFullName();
			$lname = $formModel->_formDataWithTableName[$lastNameKey];
			$opts['LNAME'] = $lname;
		}
		$email = $formModel->_formDataWithTableName[$emailKey];
		$fname = $formModel->_formDataWithTableName[$firstNameKey];

		$opts['FNAME'] = $fname;


		$w = new FabrikWorker();

		$groupOpts = json_decode($params->get('mailchimp_groupopts', "[]"));
		if (!empty($groupOpts)) {
			foreach ($groupOpts as $groupOpt) {
				$groups = array();
				if (isset($groupOpt->groups)) {
					$groupOpt->groups = $w->parseMessageForPlaceHolder($groupOpt->groups, $emailData);
		 			$groups[] = JArrayHelper::fromObject($groupOpt);//array('name'=>'Your Interests:', 'groups'=>'Bananas,Apples')
				}
			}
			$opts['GROUPINGS'] = $groups;
		}

		// By default this sends a confirmation email - you will not see new members
		// until the link contained in it is clicked!

		$emailType = $params->get('mailchimp_email_type', 'html');
		$doubleOptin = (bool)$params->get('mailchimp_double_optin', true);
		$updateExisting = (bool)$params->get('mailchimp_update_existing');
		$retval = $api->listSubscribe($listId, $email, $opts, $emailType, $doubleOptin, $updateExisting);
		if ($api->errorCode) {
			$formModel->_arErrors['mailchimp_error'] = true;
			JError::raiseNotice(500, $api->errorCode.':'.$api->errorMessage);
			return false;
		} else {
			return true;
		}

	}
}
?>