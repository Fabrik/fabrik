<?php
/**
 * Emailform view
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * Fabrik Email Form View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikViewEmailform extends JViewLegacy
{
	public $rowId = null;

	public $params = null;

	public $isMambot = null;

	public $id = null;

	/**
	 * Display
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		FabrikHelperHTML::framework();
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = $this->getModel('form');
		$filter = JFilterInput::getInstance();
		$post = $filter->clean($_POST, 'array');

		if (!array_key_exists('youremail', $post))
		{
			FabrikHelperHTML::emailForm($model);
		}
		else
		{
			$to = $input->getString('email', '');

			if ($this->sendMail($to))
			{
				$app->enqueueMessage(FText::_('COM_FABRIK_THIS_ITEM_HAS_BEEN_SENT_TO') . ' ' . $to, 'success');
			}

			FabrikHelperHTML::emailSent();
		}
	}

	/**
	 * Send email
	 *
	 * @param   string  $email  Email
	 *
	 * @throws RuntimeException
	 *
	 * @return  bool
	 */
	public function sendMail($email)
	{
		JSession::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$input = $app->input;

		/*
		 * First, make sure the form was posted from a browser.
		 * For basic web-forms, we don't care about anything
		 * other than requests from a browser:
		 */
		if (!isset($_SERVER['HTTP_USER_AGENT']))
		{
			throw new RuntimeException(FText::_('JERROR_ALERTNOAUTHOR'), 500);
		}

		// Make sure the form was indeed POST'ed:
		//  (requires your html form to use: action="post")
		if (!$_SERVER['REQUEST_METHOD'] == 'POST')
		{
			throw new RuntimeException(FText::_('JERROR_ALERTNOAUTHOR'), 500);
		}

		// Attempt to defend against header injections:
		$badStrings = array('Content-Type:', 'MIME-Version:', 'Content-Transfer-Encoding:', 'bcc:', 'cc:');

		// Loop through each POST'ed value and test if it contains
		// one of the $badStrings:
		foreach ($_POST as $k => $v)
		{
			foreach ($badStrings as $v2)
			{
				if (JString::strpos($v, $v2) !== false)
				{
					throw new RuntimeException(FText::_('JERROR_ALERTNOAUTHOR'), 500);
				}
			}
		}

		// Made it past spammer test, free up some memory
		// and continue rest of script:
		unset($k, $v, $v2, $badStrings);
		$email = $input->getString('email', '');
		$yourname = $input->getString('yourname', '');
		$youremail = $input->getString('youremail', '');
		$subject_default = JText::sprintf('Email from', $yourname);
		$subject = $input->getString('subject', $subject_default);
		jimport('joomla.mail.helper');

		if (!$email || !$youremail || (FabrikWorker::isEmail($email) == false) || (FabrikWorker::isEmail($youremail) == false))
		{
			$app->enqueueMessage(FText::_('PHPMAILER_INVALID_ADDRESS'));
		}

		$config = JFactory::getConfig();
		$sitename = $config->get('sitename');

		// Link sent in email
		$link = $input->get('referrer', '', 'string');

		// Message text
		$msg = JText::sprintf('COM_FABRIK_EMAIL_MSG', $sitename, $yourname, $youremail, $link);

		// Mail function
		$mail = JFactory::getMailer();

		return $mail->sendMail($youremail, $yourname, $email, $subject, $msg);
	}
}
