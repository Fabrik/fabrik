<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.email
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Send email upon form submission
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.email
 * @since       3.0
 */

class PlgFabrik_FormEmail extends PlgFabrik_Form
{
	/**
	 * Attachment files
	 *
	 * @var array
	 */
	protected $attachments = array();

	/**
	 * Posted form keys that we don't want to include in the message
	 * This is basically the fileupload elements
	 *
	 * @var array
	 */
	protected $dontEmailKeys = null;

	/**
	 * MOVED TO PLUGIN.PHP SHOULDPROCESS()
	 * determines if a condition has been set and decides if condition is matched
	 *
	 * @param object $params
	 * @return  bool true if you should send the email, false stops sending of email
	 */

	/*function shouldSend(&$params)
	{
	}*/

	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @return	bool
	 */

	public function onAfterProcess()
	{
		$params = $this->getParams();
		$app = JFactory::getApplication();
		$input = $app->input;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		jimport('joomla.mail.helper');
		$user = JFactory::getUser();
		$config = JFactory::getConfig();
		$db = JFactory::getDbo();
		$w = new FabrikWorker;
		$formModel = $this->getModel();
		$formParams = $formModel->getParams();
		$emailTemplate = JPath::clean(JPATH_SITE . '/plugins/fabrik_form/email/tmpl/' . $params->get('email_template', ''));

		$this->data = $this->getProcessData();

		/* $$$ hugh - moved this to here from above the previous line, 'cos it needs $this->data
		 * check if condition exists and is met
		 */
		if (!$this->shouldProcess('email_conditon', null))
		{
			return;
		}

		$contentTemplate = $params->get('email_template_content');
		$content = $contentTemplate != '' ? $this->_getConentTemplate($contentTemplate) : '';

		// Always send as html as even text email can contain html from wysiwyg editors
		$htmlEmail = true;

		$messageTemplate = '';

		if (JFile::exists($emailTemplate))
		{
			$messageTemplate = JFile::getExt($emailTemplate) == 'php' ? $this->_getPHPTemplateEmail($emailTemplate) : $this
				->_getTemplateEmail($emailTemplate);

			// $$$ hugh - added ability for PHP template to return false to abort, same as if 'condition' was was false
			if ($messageTemplate === false)
			{
				return;
			}

			$messageTemplate = str_replace('{content}', $content, $messageTemplate);
		}

		$messageText = $params->get('email_message_text', '');

		if (!empty($messageText))
		{
			$messageText = $w->parseMessageForPlaceholder($messageText, $this->data, false);
			$messageText = str_replace('{content}', $content, $messageText);
			$messageText = str_replace('{template}', $messageTemplate, $messageText);
		}

		$message = '';

		if (!empty($messageText))
		{
			$message = $messageText;
		}
		elseif (!empty($messageTemplate))
		{
			$message = $messageTemplate;
		}
		elseif (!empty($content))
		{
			$message = $content;
		}
		else
		{
			$message = $this->_getTextEmail();
		}

		$this->addAttachments();

		$cc = null;
		$bcc = null;

		// $$$ hugh - test stripslashes(), should be safe enough.
		$message = stripslashes($message);

		$editURL = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $package . '&amp;view=form&amp;fabrik=' . $formModel->get('id') . '&amp;rowid='
			. $input->get('rowid', '', 'string');
		$viewURL = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $package . '&amp;view=details&amp;fabrik=' . $formModel->get('id') . '&amp;rowid='
			. $input->get('rowid', '', 'string');
		$editlink = '<a href="' . $editURL . '">' . FText::_('EDIT') . '</a>';
		$viewlink = '<a href="' . $viewURL . '">' . FText::_('VIEW') . '</a>';
		$message = str_replace('{fabrik_editlink}', $editlink, $message);
		$message = str_replace('{fabrik_viewlink}', $viewlink, $message);
		$message = str_replace('{fabrik_editurl}', $editURL, $message);
		$message = str_replace('{fabrik_viewurl}', $viewURL, $message);

		// $$$ rob if email_to is not a valid email address check the raw value to see if that is
		$email_to = explode(',', $params->get('email_to'));

		foreach ($email_to as &$emailkey)
		{
			$emailkey = $w->parseMessageForPlaceholder($emailkey, $this->data, false);

			// Can be in repeat group in which case returns "email1,email2"
			$emailkey = explode(',', $emailkey);

			foreach ($emailkey as &$key)
			{
				// $$$ rob added strstr test as no point trying to add raw suffix if not placeholder in $emailkey
				if (!FabrikWorker::isEmail($key) && trim($key) !== '' && strstr($key, '}'))
				{
					$key = explode('}', $key);

					if (substr($key[0], -4) !== '_raw')
					{
						$key = $key[0] . '_raw}';
					}
					else
					{
						$key = $key[0] . '}';
					}

					$key = $w->parseMessageForPlaceholder($key, $this->data, false);
				}
			}
		}

		// Reduce back down to single dimension array
		foreach ($email_to as $i => $a)
		{
			foreach ($a as $v)
			{
				$email_to[] = $v;
			}

			unset($email_to[$i]);
		}

		$email_to_eval = $params->get('email_to_eval', '');

		if (!empty($email_to_eval))
		{
			$email_to_eval = $w->parseMessageForPlaceholder($email_to_eval, $this->data, false);
			$email_to_eval = @eval($email_to_eval);
			FabrikWorker::logEval($email_to_eval, 'Caught exception on eval in email emailto : %s');
			$email_to_eval = explode(',', $email_to_eval);
			$email_to = array_merge($email_to, $email_to_eval);
		}

		@list($email_from, $email_from_name) = explode(":", $w->parseMessageForPlaceholder($params->get('email_from'), $this->data, false), 2);

		if (empty($email_from))
		{
			$email_from = $config->get('mailfrom');
		}

		if (empty($email_from_name))
		{
			$email_from_name = $config->get('fromname', $email_from);
		}

		// Changes by JFQ
		@list($return_path, $return_path_name) = explode(":", $w->parseMessageForPlaceholder($params->get('return_path'), $this->data, false), 2);

		if (empty($return_path))
		{
			$return_path = null;
		}

		if (empty($return_path_name))
		{
			$return_path_name = null;
		}
		// End changes
		$subject = $params->get('email_subject');

		if ($subject == '')
		{
			$subject = $config->get('sitename') . " :: Email";
		}

		$subject = preg_replace_callback('/&#([0-9a-fx]+);/mi', array($this, 'replace_num_entity'), $subject);

		$attach_type = $params->get('email_attach_type', '');
		$config = JFactory::getConfig();
		$attach_fname = $config->get('tmp_path') . '/' . uniqid() . '.' . $attach_type;

		$query = $db->getQuery(true);
		$email_to = array_map('trim', $email_to);

		// Add any assigned groups to the to list
		$sendTo = (array) $params->get('to_group');
		$groupEmails = (array) $this->getUsersInGroups($sendTo, $field = 'email');
		$email_to = array_merge($email_to, $groupEmails);
		$email_to = array_unique($email_to);

		// Remove blank email addresses
		$email_to = array_filter($email_to);
		$dbEmailTo = array_map(array($db, 'quote'), $email_to);

		// Get an array of user ids from the email to array
		if (!empty($dbEmailTo))
		{
			$query->select('id, email')->from('#__users')->where('email IN (' . implode(',', $dbEmailTo) . ')');
			$db->setQuery($query);
			$userids = $db->loadObjectList('email');
		}
		else
		{
			$userids = array();
		}

		// Send email
		foreach ($email_to as $email)
		{
			$email = strip_tags($email);

			if (FabrikWorker::isEmail($email))
			{
				$thisAttachments = $this->attachments;
				$this->data['emailto'] = $email;

				$userid = array_key_exists($email, $userids) ? $userids[$email]->id : 0;
				$thisUser = JFactory::getUser($userid);

				$thisMessage = $w->parseMessageForPlaceholder($message, $this->data, true, false, $thisUser);
				$thisSubject = strip_tags($w->parseMessageForPlaceholder($subject, $this->data, true, false, $thisUser));

				if (!empty($attach_type))
				{
					if (JFile::write($attach_fname, $thisMessage))
					{
						$thisAttachments[] = $attach_fname;
					}
					else
					{
						$attach_fname = '';
					}
				}

				$this->pdfAttachement($thisAttachments, $this->data, $thisUser);

				// Get a JMail instance (have to get a new instance otherwise the recipients are appended to previously added recipients)
				$mail = JFactory::getMailer();
				$res = $mail->sendMail(
					$email_from, $email_from_name, $email, $thisSubject, $thisMessage,
					$htmlEmail, $cc, $bcc, $thisAttachments, $return_path, $return_path_name
				);

				/*
				 * $$$ hugh - added some error reporting, but not sure if 'invalid address' is the appropriate message,
				 * may need to add a generic "there was an error sending the email" message
				 */
				if ($res !== true)
				{
					$app->enqueueMessage(JText::sprintf('PLG_FORM_EMAIL_DID_NOT_SEND_EMAIL_INVALID_ADDRESS', $email), 'notice');
				}

				if (JFile::exists($attach_fname))
				{
					JFile::delete($attach_fname);
				}
			}
			else
			{
				$app->enqueueMessage(JText::sprintf('PLG_FORM_EMAIL_DID_NOT_SEND_EMAIL_INVALID_ADDRESS', $email), 'notice');
			}
		}

		return true;
	}

	/**
	 * Attach the details view as a PDF to the email
	 *
	 * @param   array  &$thisAttachments  Attachments
	 *
	 * @throws  RuntimeException
	 *
	 * @return  void
	 */

	protected function pdfAttachement(&$thisAttachments)
	{
		$params = $this->getParams();

		if ($params->get('attach_pdf', 0) == 0)
		{
			return;
		}

		$model = $this->getModel();
		$document = JFactory::getDocument();
		$config = JFactory::getConfig();
		$docType = $document->getType();
		$document->setType('pdf');
		$app = JFactory::getApplication();
		$input = $app->input;

		$orig['details'] = $input->get('view');
		$orig['format'] = $input->get('format');

		$input->set('view', 'details');
		$input->set('format', 'pdf');

		// Ensure the package is set to fabrik
		$prevUserState = $app->getUserState('com_fabrik.package');
		$app->setUserState('com_fabrik.package', 'fabrik');

		try
		{
			$model->getFormCss();
			// Require files and set up DOM pdf
			require_once JPATH_SITE . '/components/com_fabrik/helpers/pdf.php';
			require_once JPATH_SITE . '/components/com_fabrik/controllers/details.php';
			FabrikPDFHelper::iniDomPdf();
			$dompdf = new DOMPDF;
			$size = strtoupper($params->get('pdf_size', 'A4'));
			$orientation = $params->get('pdf_orientation', 'portrait');
			$dompdf->set_paper($size, $orientation);


			$controller = new FabrikControllerDetails;
			/**
			 * $$$ hugh - stuff our model in there, with already formatted data, so it doesn't get rendered
			 * all over again by the view, with unformatted data.  Should probably use a setModel() method
			 * here instead of poking in to the _model, but I don't think there is a setModel for controllers?
			 */
			$controller->_model = $model;
			$controller->_model->data = $this->getProcessData();

			// Store in output buffer
			ob_start();
			$controller->display();
			$html = ob_get_contents();
			ob_end_clean();

			// Load the HTML into DOMPdf and render it.
			$dompdf->load_html($html);
			$dompdf->render();

			// Store the file in the tmp folder so it can be attached
			$file = $config->get('tmp_path') . '/' . JStringNormalise::toDashSeparated($model->getForm()->label . '-' . $input->getString('rowid')) . '.pdf';
			$pdf = $dompdf->output();

			if (JFile::write($file, $pdf))
			{
				$thisAttachments[] = $file;
			}
			else
			{
				throw new RuntimeException('Could not write PDF file to tmp folder');
			}
		}
		catch (Exception $e)
		{
			$app->enqueueMessage($e->getMessage(), 'error');
		}

		// Set the package back to what it was before rendering the module
		$app->setUserState('com_fabrik.package', $prevUserState);

		// Reset input
		foreach ($orig as $key => $val)
		{
			$input->set($key, $val);
		}

		// Reset document type
		$document->setType($docType);
	}

	/**
	 * Use a php template for advanced email templates, particularly for forms with repeat group data
	 *
	 * @param   string  $tmpl  Path to template
	 *
	 * @return string email message
	 */

	protected function _getPHPTemplateEmail($tmpl)
	{
		$emailData = $this->data;
		$formModel = $this->getModel();

		// Start capturing output into a buffer
		ob_start();
		$result = require $tmpl;
		$message = ob_get_contents();
		ob_end_clean();

		if ($result === false)
		{
			return false;
		}

		return $message;
	}

	/**
	 * Add attachments to the email
	 *
	 * @return  void
	 */

	protected function addAttachments()
	{
		$params = $this->getParams();
		$pluginManager = FabrikWorker::getPluginManager();
		$data = $this->getProcessData();
		$formModel = $this->getModel();
		$groups = $formModel->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$elName = $elementModel->getFullName(true, false);

				if (array_key_exists($elName, $this->data))
				{
					if (method_exists($elementModel, 'addEmailAttachement'))
					{
						if (array_key_exists($elName . '_raw', $data))
						{
							$val = $data[$elName . '_raw'];
						}
						else
						{
							$val = $data[$elName];
						}

						if (is_array($val))
						{
							if (is_array(current($val)))
							{
								// Can't implode multi dimensional arrays
								$val = json_encode($val);
								$val = FabrikWorker::JSONtoData($val, true);
							}
						}

						foreach ($val as $v)
						{
							$file = $elementModel->addEmailAttachement($v);

							if ($file !== false)
							{
								$this->attachments[] = $file;
							}
						}
					}
				}
			}
		}
		// $$$ hugh - added an optional eval for adding attachments.
		// Eval'd code should just return an array of file paths which we merge with $this->attachments[]
		$w = new FabrikWorker;
		$email_attach_eval = $w->parseMessageForPlaceholder($params->get('email_attach_eval', ''), $this->data, false);

		if (!empty($email_attach_eval))
		{
			$email_attach_array = @eval($email_attach_eval);
			FabrikWorker::logEval($email_attach_array, 'Caught exception on eval in email email_attach_eval : %s');

			if (!empty($email_attach_array))
			{
				$this->attachments = array_merge($this->attachments, $email_attach_array);
			}
		}
	}

	/**
	 * Get an array of keys we don't want to email to the user
	 *
	 * @return  array
	 */

	protected function getDontEmailKeys()
	{
		if (is_null($this->dontEmailKeys))
		{
			$this->dontEmailKeys = array();

			foreach ($_FILES as $key => $file)
			{
				$this->dontEmailKeys[] = $key;
			}
		}

		return $this->dontEmailKeys;
	}

	/**
	 * Template email handling routine, called if email template specified
	 *
	 * @param   string  $emailTemplate  path to template
	 *
	 * @return  string	email message
	 */

	protected function _getTemplateEmail($emailTemplate)
	{
		return file_get_contents($emailTemplate);
	}

	/**
	 * Get content item template
	 *
	 * @param   int  $contentTemplate  Joomla article ID to load
	 *
	 * @return  string  content item html (translated with Joomfish if installed)
	 */

	protected function _getConentTemplate($contentTemplate)
	{
		$app = JFactory::getApplication();

		if ($app->isAdmin())
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('introtext, ' . $db->quoteName('fulltext'))->from('#__content')->where('id = ' . (int) $contentTemplate);
			$db->setQuery($query);
			$res = $db->loadObject();
		}
		else
		{
			JModelLegacy::addIncludePath(COM_FABRIK_BASE . 'components/com_content/models');
			$articleModel = JModelLegacy::getInstance('Article', 'ContentModel');
			$res = $articleModel->getItem($contentTemplate);
		}

		return $res->introtext . ' ' . $res->fulltext;
	}

	/**
	 * Default email handling routine, called if no email template specified
	 *
	 * @return  string  email message
	 */

	protected function _getTextEmail()
	{
		$data = $this->getProcessData();
		$config = JFactory::getConfig();
		$ignore = $this->getDontEmailKeys();
		$message = "";
		$pluginManager = FabrikWorker::getPluginManager();
		$formModel = $this->getModel();
		$groupModels = $formModel->getGroupsHiarachy();

		foreach ($groupModels as &$groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as &$elementModel)
			{
				$element = $elementModel->getElement();

				// @TODO - how about adding a 'renderEmail()' method to element model, so specific element types  can render themselves?
				$key = (!array_key_exists($element->name, $data)) ? $elementModel->getFullName(true, false) : $element->name;

				if (!in_array($key, $ignore))
				{
					$val = '';

					if (is_array(JArrayHelper::getValue($data, $key)))
					{
						// Repeat group data
						foreach ($data[$key] as $k => $v)
						{
							if (is_array($v))
							{
								$val = implode(", ", $v);
							}

							$val .= count($data[$key]) == 1 ? ": $v<br />" : ($k++) . ": $v<br />";
						}
					}
					else
					{
						$val = JArrayHelper::getValue($data, $key);
					}

					$val = FabrikString::rtrimword($val, "<br />");
					$val = stripslashes($val);

					// Set $val to default value if empty
					if ($val == '')
					{
						$val = " - ";
					}
					// Don't add a second ":"
					$label = trim(strip_tags($element->label));
					$message .= $label;

					if (strlen($label) != 0 && JString::strpos($label, ':', JString::strlen($label) - 1) === false)
					{
						$message .= ':';
					}

					$message .= "<br />" . $val . "<br /><br />";
				}
			}
		}

		$message = FText::_('Email from') . ' ' . $config->get('sitename') . '<br />' . FText::_('Message') . ':'
			. "<br />===================================<br />" . "<br />" . stripslashes($message);

		return $message;
	}
}
