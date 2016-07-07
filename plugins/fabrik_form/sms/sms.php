<?php
/**
 * Send an SMS
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

require_once COM_FABRIK_FRONTEND . '/helpers/sms.php';

/**
 * Send an SMS
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @since       3.0
 */
class PlgFabrik_FormSMS extends PlgFabrik_Form
{
	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @return	bool
	 */
	public function onAfterProcess()
	{
		return $this->process();
	}

	/**
	 * Send SMS
	 *
	 * @return	bool
	 */
	protected function process()
	{
		$formModel = $this->getModel();
		$params = $this->getParams();
		$data = $formModel->formData;
		$w = new FabrikWorker;
		$opts = array();
		$userName = $params->get('sms-username');
		$password = $params->get('sms-password');
		$from = $params->get('sms-from');
		$to = $params->get('sms-to');
		$opts['sms-username'] = $w->parseMessageForPlaceHolder($userName, $data);
		$opts['sms-password'] = $w->parseMessageForPlaceHolder($password, $data);
		$opts['sms-from'] = $w->parseMessageForPlaceHolder($from, $data);
		$opts['sms-to'] = $w->parseMessageForPlaceHolder($to, $data);
		$message = $this->getMessage();
		$gateway = $this->getInstance();

		if (!$this->shouldProcess('sms_conditon', null, $params))
		{
			return;
		} else {
      			return $gateway->process($message, $opts);
      }
	}

	/**
	 * Get specific SMS gateway instance
	 *
	 * @return  object  gateway
	 */
	private function getInstance()
	{
		if (!isset($this->gateway))
		{
			$params = $this->getParams();
			$gateway = $params->get('sms-gateway', 'kapow.php');
			$input = new JFilterInput;
			$gateway = $input->clean($gateway, 'CMD');
			require_once JPATH_ROOT . '/components/com_fabrik/helpers/sms_gateways/' . JString::strtolower($gateway);
			$gateway = JFile::stripExt($gateway);
			$this->gateway = new $gateway;
			$this->gateway->params = $params;
		}

		return $this->gateway;
	}

	/**
	 * Default email handling routine, called if no email template specified
	 *
	 * @return	string	email message
	 */
	protected function getMessage()
	{
		$params = $this->getParams();
		$msg    = $params->get('sms_message', '');
		$formModel = $this->getModel();
		$data = $formModel->formData;

		if ($msg !== '')
		{
			$w = new FabrikWorker;
			return $w->parseMessageForPlaceHolder($msg, $data);
		}
		else
		{
			return $this->defaultMessage();
		}
	}

	/**
	 * @return string
	 */
	protected function defaultMessage()
	{
		$formModel = $this->getModel();
		$data = $formModel->formData;
		$arDontEmailThesKeys = array();

		// Remove raw file upload data from the email
		foreach ($_FILES as $key => $file)
		{
			$arDontEmailThesKeys[] = $key;
		}

		$message = '';
		$groups = $formModel->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$element = $elementModel->getElement();
				$element->label = strip_tags($element->label);

				if (!array_key_exists($element->name, $data))
				{
					$elName = $element->getFullName();
				}
				else
				{
					$elName = $element->name;
				}

				$key = $elName;

				if (!in_array($key, $arDontEmailThesKeys))
				{
					if (array_key_exists($elName, $data))
					{
						$val = stripslashes($data[$elName]);
						$params = $elementModel->getParams();

						if (method_exists($elementModel, 'getEmailValue'))
						{
							$val = $elementModel->getEmailValue($val);
						}
						else
						{
							if (is_array($val))
							{
								$val = implode("\n", $val);
							}
						}

						$val = FabrikString::rtrimword($val, '<br />');
						$message .= $element->label . ': ' . $val . "\r\n";
					}
				}
			}
		}

		$message = FText::_('PLG_FORM_SMS_FROM') . $this->config->get('sitename') . "\r \n \r \nMessage:\r \n" . stripslashes($message);

		return $message;
	}
}
