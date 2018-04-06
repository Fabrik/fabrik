<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.email
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Pdf;
use Mailgun\Mailgun;
use Mailgun\Messages;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';
require_once JPATH_ROOT . '/plugins/fabrik_form/mailgun/vendor/autoload.php';

/**
 * Send email upon form submission
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.email
 * @since       3.0
 */
class PlgFabrik_FormMailgun extends PlgFabrik_Form
{
	/**
	 * Attachment files
	 *
	 * @var array
	 */
	protected $attachments = array();

	/**
	 * Attachment files to delete after use
	 *
	 * @var array
	 */
	protected $deleteAttachments = array();

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
		$profiler = JProfiler::getInstance('Application');
		JDEBUG ? $profiler->mark("email: start: onAfterProcess") : null;
		$params = $this->getParams();
		$input = $this->app->input;
		$w = new FabrikWorker;

		$mgClient = Mailgun::create($params->get('mailgun_api_key'));
		$mgDomain = $params->get('mailgun_domain');

		/** @var \FabrikFEModelForm $formModel */
		$formModel = $this->getModel();
		$emailTemplate = JPath::clean(JPATH_SITE . '/plugins/fabrik_form/mailgun/tmpl/' . $params->get('mailgun_template', ''));

		$this->data = $this->getProcessData();

		/* $$$ hugh - moved this to here from above the previous line, 'cos it needs $this->data
		 * check if condition exists and is met
		 */
		if ($this->alreadySent() || !$this->shouldProcess('mailgun_conditon', null, $params))
		{
			return true;
		}

		// set up some useful placeholders for links to form
		$this->data['fabrik_editurl'] = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $this->package . '&amp;view=form&amp;formid=' . $formModel->get('id') . '&amp;rowid='
			. $input->get('rowid', '', 'string');
		$this->data['fabrik_viewurl'] = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $this->package . '&amp;view=details&amp;formid=' . $formModel->get('id') . '&amp;rowid='
			. $input->get('rowid', '', 'string');
		$this->data['fabrik_editlink'] = '<a href="' . $this->data['fabrik_editurl'] . '">' . FText::_('COM_FABRIK_EDIT') . '</a>';
		$this->data['fabrik_viewlink'] = '<a href="' . $this->data['fabrik_viewurl'] . '">' . FText::_('COM_FABRIK_VIEW') . '</a>';

		/**
		 * Added option to run content plugins on message text.  Note that rather than run it one time at the
		 * end of the following code, after we have assembled all the various options in to a single $message,
		 * it needs to be run separately on each type of content.  This is because we do placeholder replacement
		 * in various places, which will strip all {text} which doesn't match element names.
		 */

		$runContentPlugins = $params->get('mailgun_run_content_plugins', '0') === '1';

		$contentTemplate = $params->get('mailgun_template_content');
		$content = $contentTemplate != '' ? FabrikHelperHTML::getContentTemplate($contentTemplate, 'both', $runContentPlugins) : '';

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
				return true;
			}

			if ($runContentPlugins === true)
			{
				FabrikHelperHTML::runContentPlugins($messageTemplate, false);
			}

			$messageTemplate = str_replace('{content}', $content, $messageTemplate);
		}

		$messageText = $params->get('mailgun_message_text', '');

		if (!empty($messageText))
		{

			if ($runContentPlugins === true)
			{
				FabrikHelperHTML::runContentPlugins($messageText, false);
			}

			$messageText = str_replace('{content}', $content, $messageText);
			$messageText = str_replace('{template}', $messageTemplate, $messageText);
			$messageText = $w->parseMessageForPlaceholder($messageText, $this->data, false);
		}

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

		Pdf::fullPaths($message);


		// $$$ rob if email_to is not a valid email address check the raw value to see if that is
		$emailTo = explode(',', $params->get('mailgun_to'));

		foreach ($emailTo as &$emailKey)
		{
			$emailKey = $w->parseMessageForPlaceholder($emailKey, $this->data, false);

			// Can be in repeat group in which case returns "email1,email2"
			$emailKey = explode(',', $emailKey);

			foreach ($emailKey as &$key)
			{
				// $$$ rob added strstr test as no point trying to add raw suffix if not placeholder in $emailKey
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
		foreach ($emailTo as $i => $a)
		{
			foreach ($a as $v)
			{
				$emailTo[] = $v;
			}

			unset($emailTo[$i]);
		}

		$emailToEval = $params->get('mailgun_to_eval', '');

		if (!empty($emailToEval))
		{
			$emailToEval = $w->parseMessageForPlaceholder($emailToEval, $this->data, false);
			$emailToEval = @eval($emailToEval);
			FabrikWorker::logEval($emailToEval, 'Caught exception on eval in email emailto : %s');
			$emailToEval = explode(',', $emailToEval);
			$emailTo = array_merge($emailTo, $emailToEval);
		}

		@list($emailFrom, $emailFromName) = explode(":", $w->parseMessageForPlaceholder($params->get('mailgun_from'), $this->data, false), 2);

		if (empty($emailFrom))
		{
			$emailFrom = $this->config->get('mailfrom');
		}

		if (empty($emailFromName))
		{
			$emailFromName = $this->config->get('fromname', $emailFrom);
		}

		// Changes by JFQ
		@list($returnPath, $returnPathName) = explode(":", $w->parseMessageForPlaceholder($params->get('mailgun_return_path'), $this->data, false), 2);

		if (empty($returnPath))
		{
			$returnPath = null;
		}

		if (empty($returnPathName))
		{
			$returnPathName = null;
		}
		// End changes
		$subject = $params->get('mailgun_subject');

		if ($subject == '')
		{
			$subject = $this->config->get('sitename') . " :: Email";
		}

		$subject = preg_replace_callback('/&#([0-9a-fx]+);/mi', array($this, 'replace_num_entity'), $subject);

		$attachType = $params->get('mailgun_attach_type', '');
		$attachFileName = $this->config->get('tmp_path') . '/' . uniqid() . '.' . $attachType;

		$query = $this->_db->getQuery(true);
		$emailTo = array_map('trim', $emailTo);

		// Add any assigned groups to the to list
		$sendTo = (array) $params->get('mailgun_to_group');
		$groupEmails = (array) $this->getUsersInGroups($sendTo, $field = 'email');
		$emailTo = array_merge($emailTo, $groupEmails);
		$emailTo = array_unique($emailTo);

		// Remove blank email addresses
		$emailTo = array_filter($emailTo);
		$dbEmailTo = array_map(array($this->_db, 'quote'), $emailTo);

		// Get an array of user ids from the email to array
		if (!empty($dbEmailTo))
		{
			$query->select('id, email')->from('#__users')->where('email IN (' . implode(',', $dbEmailTo) . ')');
			$this->_db->setQuery($query);
			$userIds = $this->_db->loadObjectList('email');
		}
		else
		{
			$userIds = array();
		}

		$customHeadersEval = $params->get('mailgun_headers_eval', '');
		$customHeaders = array();

		if (!empty($customHeadersEval))
		{
			$customHeadersEval = $w->parseMessageForPlaceholder($customHeadersEval, $this->data, false);
			$customHeaders = @eval($customHeadersEval);
			FabrikWorker::logEval($customHeadersEval, 'Caught exception on eval in email custom headers : %s');
		}

		// Send email
		foreach ($emailTo as $email)
		{
			$email = strip_tags($email);

			if (FabrikWorker::isEmail($email))
			{
				$thisAttachments = $this->attachments;
				$this->data['emailto'] = $email;

				$userId = array_key_exists($email, $userIds) ? $userIds[$email]->id : 0;
				$thisUser = JFactory::getUser($userId);
				$thisMessage = $w->parseMessageForPlaceholder($message, $this->data, true, false, $thisUser);
				$thisSubject = strip_tags($w->parseMessageForPlaceholder($subject, $this->data, true, false, $thisUser));

				if (!empty($attachType))
				{
					if (JFile::write($attachFileName, $thisMessage))
					{
						$thisAttachments[] = $attachFileName;
					}
					else
					{
						$attachFileName = '';
					}
				}

				try
				{
					$this->pdfAttachment($thisAttachments);
				}
				catch (Exception $e)
				{
					$this->app->enqueueMessage($e->getMessage(), 'error');
				}

				/*
				 * Sanity check for attachment files existing.  Could have base folder paths for things
				 * like file upload elements with no file.  As of J! 3.5.1, the J! mailer tosses an exception
				 * if files don't exist.  We catch that in the sendMail helper, but remove non-files here anyway
				 */

				foreach ($thisAttachments as $aKey => $attachFile)
				{
					if (!JFile::exists($attachFile))
					{
						unset($thisAttachments[$aKey]);
					}
				}

				JDEBUG ? $profiler->mark("email: sendMail start: " . $email) : null;

				/*
				$res = FabrikWorker::sendMail(
					$emailFrom,
					$emailFromName,
					$email,
					$thisSubject,
					$thisMessage,
					$htmlEmail,
					$cc,
					$bcc,
					$thisAttachments,
					$returnPath,
					$returnPathName,
					$customHeaders
				);
				*/

				try
				{
					/*
					$mgRes = $mgClient->messages()->send($mgDomain, array(
						'from'    => $emailFrom,
						'to'      => $email,
						'subject' => $thisSubject,
						'html'    => $thisMessage,
						'v:my-custom-data' => json_encode(
							array(
								'formid' => $formModel->getId(),
								'rowid' => $this->data['rowid'],
								'listid' => $formModel->getListModel()->getId(),
								'userid' => JFactory::getUser()->get('id')
							)
						)
					));
					*/
					# Next, instantiate a Message Builder object from the SDK.
					$messageBldr = new Messages\MessageBuilder();
					$messageBldr->setFromAddress($emailFrom, array('full_name' => $emailFromName));
					$messageBldr->addToRecipient($email);
					$messageBldr->setSubject($thisSubject);
					$messageBldr->setHtmlBody($thisMessage);

					foreach ($customHeaders as $headerName => $headerValue)
					{
						$messageBldr->addCustomHeader($headerName, $headerValue);
					}

					foreach ($thisAttachments as $attachment)
					{
						$messageBldr->addAttachment($attachment);
					}

					$messageBldr->addCustomData(
						'fabrik-metadata',
						array(
							'formid' => $formModel->getId(),
							'rowid' => $this->data['rowid'],
							'listid' => $formModel->getListModel()->getId(),
							'userid' => JFactory::getUser()->get('id')
						)
					);

					$mgRes = $mgClient->messages()->send(
						$mgDomain,
						$messageBldr->getMessage(),
						$messageBldr->getFiles()
					);

					$res = true;
				}
				catch (Exception $e)
				{
					$res = false;
				}

				JDEBUG ? $profiler->mark("email: sendMail end: " . $email) : null;

				/*
				 * $$$ hugh - added some error reporting, but not sure if 'invalid address' is the appropriate message,
				 * may need to add a generic "there was an error sending the email" message
				 */
				if ($res !== true)
				{
					$this->app->enqueueMessage(JText::sprintf('PLG_FORM_EMAIL_DID_NOT_SEND_EMAIL', $email), 'notice');
				}

				if (JFile::exists($attachFileName))
				{
					JFile::delete($attachFileName);
				}
			}
			else
			{
				$this->app->enqueueMessage(JText::sprintf('PLG_FORM_EMAIL_DID_NOT_SEND_EMAIL_INVALID_ADDRESS', $email), 'notice');
			}
		}

		foreach($this->deleteAttachments as $attachment)
		{
			if (JFile::exists($attachment))
			{
				JFile::delete($attachment);
			}
		}

		$this->updateRow();

		JDEBUG ? $profiler->mark("email: end: onAfterProcess") : null;

		return true;
	}

	/**
	 * Check to see if there is an "update field" specified, and if it is already non-zero
	 *
	 * @return  bool
	 */
	protected function alreadySent()
	{
		$params      = $this->getParams();
		$updateField = $params->get('mailgun_update_field');
		if (!empty($updateField))
		{
			$updateField .= '_raw';
			$updateEl = FabrikString::safeColNameToArrayKey($updateField);
			$updateVal = FArrayHelper::getValue($this->data, $updateEl, '');
			$updateVal = is_array($updateVal) ? $updateVal[0] : $updateVal;
			return !empty($updateVal);
		}
		return false;
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
	protected function pdfAttachment(&$thisAttachments)
	{
		$params = $this->getParams();

		if ($params->get('mailgun_attach_pdf', 0) == 0)
		{
			return;
		}

		/** @var FabrikFEModelForm $model */
		$model = $this->getModel();
		$model->setRowId($this->data['rowid']);

		/*
		$document = JFactory::getDocument();
		$docType = $document->getType();
		$document->setType('pdf');
		*/

		/*
		 * We are going to swap out the raw document object with an HTML document
         * in order to work around some plugins that don't do proper environment
		 * checks before trying to use HTML document functions.
		 */
		$raw = clone JFactory::getDocument();
		$lang = JFactory::getLanguage();

		// Get the document properties.
		$attributes = array (
			'charset'   => 'utf-8',
			'lineend'   => 'unix',
			'tab'       => '  ',
			'language'  => $lang->getTag(),
			'direction' => $lang->isRtl() ? 'rtl' : 'ltr'
		);

		// Get the HTML document.
		$html = JDocument::getInstance('pdf', $attributes);

		// Todo: Why is this document fetched and immediately overwritten?
		$document = JFactory::getDocument();

		// Swap the documents.
		$document = $html;


		$input = $this->app->input;

		/*
		 *  * unset the template, to make sure view display picks up the PDF one
		 */
		$model->tmpl = null;

		$orig['view'] = $input->get('view');
		$orig['format'] = $input->get('format');

		$input->set('view', 'details');
		$input->set('format', 'pdf');

		// set editable false so things like getFormCss() pick up the detail, not form, CSS
		$model->setEditable(false);

		// Ensure the package is set to fabrik
		$prevUserState = $this->app->getUserState('com_fabrik.package');
		$this->app->setUserState('com_fabrik.package', 'fabrik');

		try
		{
			// Require files and set up DOM pdf
			//require_once JPATH_SITE . '/components/com_fabrik/helpers/pdf.php';
			require_once JPATH_SITE . '/components/com_fabrik/controllers/details.php';

			// if DOMPDF isn't installed, this will throw an exception which we should catch
			$domPdf = Pdf::iniDomPdf(true);

			$size = strtoupper($params->get('pdf_size', 'A4'));
			$orientation = $params->get('pdf_orientation', 'portrait');
			$domPdf->set_paper($size, $orientation);

			$controller = new FabrikControllerDetails;
			/**
			 * $$$ hugh - stuff our model in there, with already formatted data, so it doesn't get rendered
			 * all over again by the view, with unformatted data.  Should probably use a setModel() method
			 * here instead of poking in to the _model, but I don't think there is a setModel for controllers?
			 */
			$controller->_model = $model;

			/**
			 * Unfortunately, we need to reload the data, so it's in the right format.  Can't use the
			 * submitted data.  "One of these days" we need to have a serious look at normalizing the data formats,
			 * so submitted data is in the same format (once processed) as data read from the database.
			 */
			$model->data = null;
			$controller->_model->data = $model->getData();
			$controller->_model->tmpl = null;
			/*
			 * Allows us to bypass "view records" ACL settings for creating the details view
			 */
			$model->getListModel()->setLocalPdf();

			/*
			 * get the CSS in a kinda hacky way
			 * (moved to after setting up the model and controller, so things like tmpl have been reset)
			 */
			$model->getFormCss();

			foreach ($document->_styleSheets as $url => $ss)
			{
				if (!strstr($url, COM_FABRIK_LIVESITE))
				{
					$url = COM_FABRIK_LIVESITE_ROOT . $url;
				}

				$url = htmlspecialchars_decode($url);
				$formCss[] = file_get_contents($url);
			}

			// Store in output buffer
			ob_start();
			$controller->display();
			$html = ob_get_contents();
			ob_end_clean();

			if (!empty($formCss))
			{
				$html = "<style>\n" . implode("\n", $formCss) . "</style>\n" . $html;
			}

			// Load the HTML into DOMPdf and render it.
			// $$$trob: convert as in libraries\joomla\document\pdf\pdf.php
			$html = mb_convert_encoding($html,'HTML-ENTITIES','UTF-8');
			$domPdf->load_html($html);
			$domPdf->render();

			// Store the file in the tmp folder so it can be attached
			$layout                 = FabrikHelperHTML::getLayout('form.fabrik-pdf-title');
			$displayData         = new stdClass;
			$displayData->doc	= $document;
			$displayData->model	= $model;
			$fileName = $layout->render($displayData);
			$file = $this->config->get('tmp_path') . '/' . JStringNormalise::toDashSeparated($fileName) . '.pdf';

			$pdf = $domPdf->output();

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
			$this->app->enqueueMessage($e->getMessage(), 'error');
		}

		// set back to editable
		$model->setEditable(true);

		// Set the package back to what it was before rendering the module
		$this->app->setUserState('com_fabrik.package', $prevUserState);

		// Reset input
		foreach ($orig as $key => $val)
		{
			$input->set($key, $val);
		}

		// Reset document type
		//$document->setType($docType);

		// Swap the documents back.
		$document = $raw;
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
		$data = $this->getProcessData();

		/** @var FabrikFEModelForm $formModel */
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
						else
						{
							$val = array($val);
						}

						foreach ($val as $v)
						{
							$file = $elementModel->addEmailAttachement($v);

							if ($file !== false)
							{
								$this->attachments[] = $file;

								if ($elementModel->shouldDeleteEmailAttachment($v))
								{
									$this->deleteAttachments[] = $file;
								}
							}
						}
					}
				}
			}
		}
		// $$$ hugh - added an optional eval for adding attachments.
		// Eval'd code should just return an array of file paths which we merge with $this->attachments[]
		$w = new FabrikWorker;
		$emailAttachEval = $w->parseMessageForPlaceholder($params->get('mailgun_attach_eval', ''), $this->data, false);

		if (!empty($emailAttachEval))
		{
			$email_attach_array = @eval($emailAttachEval);
			FabrikWorker::logEval($email_attach_array, 'Caught exception on eval in email mailgun_attach_eval : %s');

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
	 * DEPRECATED use FabrikHelperHTML::getContentTemplate() instead
	 *
	 * @param   int  $contentTemplate  Joomla article ID to load
	 *
	 * @return  string  content item html (translated with Joomfish if installed)
	 */
	protected function _getContentTemplate($contentTemplate)
	{
		if ($this->app->isAdmin())
		{
			$query = $this->_db->getQuery(true);
			$query->select('introtext, ' . $this->_db->qn('fulltext'))->from('#__content')->where('id = ' . (int) $contentTemplate);
			$this->_db->setQuery($query);
			$res = $this->_db->loadObject();
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
		$ignore = $this->getDontEmailKeys();
		$message = '';

		/** @var FabrikFEModelForm $formModel */
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

					if (is_array(FArrayHelper::getValue($data, $key)))
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
						$val = FArrayHelper::getValue($data, $key);
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

		$message = FText::_('Email from') . ' ' . $this->config->get('sitename') . '<br />' . FText::_('Message') . ':'
			. "<br />===================================<br />" . "<br />" . stripslashes($message);

		return $message;
	}

	/**
	 * Update row
	 */
	private function updateRow()
	{
		$params      = $this->getParams();
		$updateField = $params->get('mailgun_update_field');
		$rowid = $this->data['rowid'];

		if (!empty($updateField) && !empty($rowid))
		{
			$this->getModel()->getListModel()->updateRow($rowid, $updateField, '1');
		}
	}


	/*
     * Process an inbound route
     *
	 * @param  FabrikFEModelForm  $formModel
	 * @param  FabrikFEModelList  $listModel
	 *
	 * @return  bool
     */
	private function doWebhook($formModel, $listModel)
	{
		$params = $this->getParams();
		$table = $listModel->getTable();
		$eventField = FabrikString::shortColName($params->get('mailgun_status_element'));

		if (empty($eventField))
		{
			return 406;
		}

		$metadata = $this->app->input->get('fabrik-metadata', '{}', 'json');
		$metadata = json_decode($metadata);

		if (!isset($metadata->formid) || !(isset($metadata->rowid)))
		{
			return 406;
		}

		$query = $this->_db->getQuery(true);
		$event = $this->app->input->get('event', '', 'raw');
		$query->update($this->_db->quoteName($table->db_table_name));
		$query->set($this->_db->quoteName($eventField) . ' = ' . $this->_db->quote($event));
		$query->where($table->db_primary_key . ' = ' . (int)$metadata->rowid);
		$this->_db->setQuery($query);

		try
		{
			$this->_db->execute();
		}
		catch (Exception $e)
		{
			$msgType      = 'fabrik.form.mailgun.webhook.event.dberr';
			$opts         = new stdClass;
			$opts->listid = $formModel->getListModel()->getId();
			$opts->formid = $formModel->getId();
			$opts->post   = $_POST;
			$opts->err    = $e->getMessage();
			$opts->query  = (string)$query;
			$msg          = new stdClass;
			$msg->opts    = $opts;
			$msg          = json_encode($msg);
			$this->doLog($msgType, $msg);

			// return a 403 to tell Mailgun to try again
			return 403;
		}

		$msgType      = 'fabrik.form.mailgun.webhook.event';
		$opts         = new stdClass;
		$opts->listid = $formModel->getListModel()->getId();
		$opts->formid = $formModel->getId();
		$opts->post   = $_POST;
		$msg          = new stdClass;
		$msg->opts    = $opts;
		$msg          = json_encode($msg);
		$this->doLog($msgType, $msg);

		return 200;

	}

	/*
	 * Process an inbound route
	 *
	 * @param  FabrikFEModelForm  $formModel
	 * @param  FabrikFEModelList  $listModel
	 *
	 * @return  bool
	 */
	private function doRoute($formModel, $listModel)
	{
		$params = $this->getParams();
		$check     = $params->get('mailgun_check_user', false);
		$query = $this->_db->getQuery(true);

		$recipient = $this->app->input->get('recipient', '', 'raw');
		$userid = 0;

		if ($check)
		{
			if ($check === '1')
			{
				$userparts = explode('@', $recipient);

				if (count($userparts) === 2)
				{
					$username = $userparts[0];
					$user   = JFactory::getUser($username);

					if (!empty($user))
					{
						$userid = $user->get('id');
					}
				}
			}
			else if ($check === '2')
			{
				$query->select('id')
					->from('#__users')
					->where('email = ' . $this->_db->quote($recipient));
				$this->_db->setQuery($query);
				$userid = (int) $this->_db->loadResult();
			}

			if (empty($userid))
			{
				$msgType      = 'fabrik.form.mailgun.webhook.route.nouser';
				$opts         = new stdClass;
				$opts->listid = $listModel->getId();
				$opts->formid = $formModel->getId();
				$opts->post   = $_POST;
				$msg          = new stdClass;
				$msg->opts    = $opts;
				$msg          = json_encode($msg);
				$this->doLog($msgType, $msg);

				// return a 406 to tell Mailgun to drop the message
				return 406;
			}
		}

		$fieldMap = array(
			'mailgun_user_element' => $userid,
			'mailgun_subject_element' => $this->app->input->get('subject', '', 'raw'),
			'mailgun_body_element' => $this->app->input->get('body-html', '', 'raw'),
			'mailgun_url_element' => $this->app->input->get('message-url', ''. 'raw'),
			'mailgun_metadata_element' => $this->app->input->get('X-Mailgun-Variables', '', 'raw'),
			'mailgun_sender_element' => $this->app->input->get('sender', '', 'raw'),
			'mailgun_msgid_element' => $this->app->input->get('Message-Id', '', 'raw'),
			'mailgun_date_element' => JFactory::getDate()->toSql()
		);

		$query->clear();
		$query->insert($this->_db->quoteName($listModel->getTable()->db_table_name));

		foreach ($fieldMap as $paramName => $value)
		{
			$field = FabrikString::shortColName($params->get($paramName));

			if (!empty($field))
			{
				if ($paramName === 'mailgun_msgid_element')
				{

				}

				$query->set($this->_db->quoteName($field) . ' = ' . $this->_db->quote($value));
			}
		}

		$this->_db->setQuery($query);

		try
		{
			$this->_db->execute();
		}
		catch (Exception $e)
		{
			$msgType      = 'fabrik.form.mailgun.webhook.route.dberr';
			$opts         = new stdClass;
			$opts->listid = $formModel->getListModel()->getId();
			$opts->formid = $formModel->getId();
			$opts->post   = $_POST;
			$opts->err    = $e->getMessage();
			$opts->query  = (string)$query;
			$msg          = new stdClass;
			$msg->opts    = $opts;
			$msg          = json_encode($msg);
			$this->doLog($msgType, $msg);

			// return a 403 to tell Mailgun to try again
			return 403;
		}

		$msgType      = 'fabrik.form.mailgun.webhook.route';
		$opts         = new stdClass;
		$opts->listid = $formModel->getListModel()->getId();
		$opts->formid = $formModel->getId();
		$opts->post   = $_POST;
		$msg          = new stdClass;
		$msg->opts    = $opts;
		$msg          = json_encode($msg);
		$this->doLog($msgType, $msg);

		return 200;
	}

	public function onWebhook()
	{
		$formId      = $this->app->input->get('formid', '', 'string');
		$renderOrder = $this->app->input->get('renderOrder', '', 'string');
		$formModel   = JModelLegacy::getInstance('Form', 'FabrikFEModel');
		$formModel->setId($formId);
		$listModel = $formModel->getListModel();
		$params    = $formModel->getParams();
		$params    = $this->setParams($params, $renderOrder);
		$timestamp = $this->app->input->get('timestamp');
		$token     = $this->app->input->get('token');
		$signature = $this->app->input->get('signature');
		$mailgun   = Mailgun::create($params->get('mailgun_api_key'));

		try
		{
			$valid = $mailgun->webhooks()->verifyWebhookSignature(
				$timestamp,
				$token,
				$signature
			);
		}
		catch (Exception $e)
		{
			$valid = false;
		}

		if (!$valid)
		{
			$msgType      = 'fabrik.form.mailgun.webhook.err';
			$opts         = new stdClass;
			$opts->listid = $listModel->getId();
			$opts->formid = $formModel->getId();
			$msg          = new stdClass;
			$msg->opts    = $opts;
			$msg->msg     = $e->getMessage();
			$msg          = json_encode($msg);
			$this->doLog($msgType, $msg);

			// 403 to tell Mailgun to try again
			http_response_code(403);
			jexit();
		}

		$event = $this->app->input->get('event', '');

		// if there's an event it's a webhook (tracking), if not it's a route (inbound email)
		if (!empty($event))
		{
			$response = $this->doWebhook($formModel, $listModel);
		}
		else
		{
			$response = $this->doRoute($formModel, $listModel);
		}

		http_response_code($response);
		jexit();
	}

}
