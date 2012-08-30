<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.mailchimp
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';
require_once 'MCAPI.class.php';

/**
 * Add a user to a mailchimp mailing list
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.mailchimp
 * @since       3.0
 */

class plgFabrik_FormMailchimp extends plgFabrik_Form
{

	protected $html = null;

	/**
	 * Set up the html to be injected into the bottom of the form
	 *
	 * @param   object  $params     plugin params
	 * @param   object  $formModel  form model
	 *
	 * @return  void
	 */

	public function getBottomContent($params, $formModel)
	{
		if ($params->get('mailchimp_userconfirm', true))
		{
			$checked = JRequest::getVar('fabrik_mailchimp_signup', '') !== '' ? ' checked="checked"' : '';
			$this->html = '<label class="mailchimpsignup"><input type="checkbox" name="fabrik_mailchimp_signup" class="fabrik_mailchimp_signup" value="1" '
				. $checked . '/>' . $params->get('mailchimp_signuplabel') . '</label>';
		}
		else
		{
			$this->html = '';
		}
	}

	/**
	 * Inject custom html into the bottom of the form
	 *
	 * @param   int  $c  plugin counter
	 *
	 * @return  string  html
	 */

	public function getBottomContent_result($c)
	{
		return $this->html;
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

		$this->formModel = $formModel;
		$emailData = $this->getEmailData();
		$post = JRequest::get('post');
		if (!array_key_exists('fabrik_mailchimp_signup', $post) && (bool) $params->get('mailchimp_userconfirm', true) === true)
		{
			return;
		}
		$listId = $params->get('mailchimp_listid');
		$apiKey = $params->get('mailchimp_apikey');
		if ($apiKey == '')
		{
			JError::raiseNotice(500, 'Mailchimp: no api key specified');
			return false;
		}
		if ($listId == '')
		{
			JError::raiseNotice(500, 'Mailchimp: no list id specified');
			return false;
		}

		$api = new MCAPI($params->get('mailchimp_apikey'));

		$opts = array();

		$emailKey = $formModel->getElement($params->get('mailchimp_email'), true)->getFullName();
		$firstNameKey = $formModel->getElement($params->get('mailchimp_firstname'), true)->getFullName();
		$fname = $formModel->_formDataWithTableName[$firstNameKey];
		$opts['FNAME'] = $fname;
		$opts['NAME'] = $fname;

		if ($params->get('mailchimp_lastname', '') !== '')
		{
			$lastNameKey = $formModel->getElement($params->get('mailchimp_lastname'), true)->getFullName();
			$lname = $formModel->_formDataWithTableName[$lastNameKey];
			$opts['LNAME'] = $lname;
			$opts['NAME'] .= ' ' . $lname;
		}
		$email = $formModel->_formDataWithTableName[$emailKey];

		$w = new FabrikWorker;

		$groupOpts = json_decode($params->get('mailchimp_groupopts', "[]"));
		if (!empty($groupOpts))
		{
			foreach ($groupOpts as $groupOpt)
			{
				$groups = array();
				if (isset($groupOpt->groups))
				{
					$groupOpt->groups = $w->parseMessageForPlaceHolder($groupOpt->groups, $emailData);

					// An arry of additonal options: array('name'=>'Your Interests:', 'groups'=>'Bananas,Apples')
					$groups[] = JArrayHelper::fromObject($groupOpt);
				}
				else
				{
					foreach ($groupOpt as $k => $v)
					{
						// DOn't use emailData as that contains html markup which is not shown in the list view
						$opts[strtoupper($k)] = $w->parseMessageForPlaceHolder($v, $formModel->_formData);

						// But... labels for db joins etc are not availabel in _formData
						$opts[strtoupper($k)] = $w->parseMessageForPlaceHolder($v, $emailData);
					}
					$opts['GROUPINGS'] = $groups;
				}
			}
			$opts['GROUPINGS'] = $groups;
		}

		// By default this sends a confirmation email - you will not see new members until the link contained in it is clicked!
		$emailType = $params->get('mailchimp_email_type', 'html');
		$doubleOptin = (bool) $params->get('mailchimp_double_optin', true);
		$updateExisting = (bool) $params->get('mailchimp_update_existing');
		$retval = $api->listSubscribe($listId, $email, $opts, $emailType, $doubleOptin, $updateExisting);
		if ($api->errorCode)
		{
			$formModel->_arErrors['mailchimp_error'] = true;
			JError::raiseNotice(500, $api->errorCode . ':' . $api->errorMessage);
			return false;
		}
		else
		{
			return true;
		}

	}
}
