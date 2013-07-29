<?php
/**
 * Send an SMS
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

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
	 * @param   object  $params      plugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return	bool
	 */

	public function onAfterProcess($params, &$formModel)
	{
		return $this->process($params, $formModel);
	}

	/**
	* Send SMS
	*
	* @param   object  $params      plugin params
	* @param   object  &$formModel  form model
	*
	* @return	bool
	*/

	protected function process($params, &$formModel)
	{
		$this->formModel = $formModel;
		$message = $this->getMessage();
		$aData = $oForm->_formData;
		$gateway = $this->getInstance();
		return $gateway->process($message);
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
			$gateway = JFilterInput::clean($params->get('sms-gateway', 'kapow.php'), 'CMD');
			require_once JPATH_ROOT . '/plugins/fabrik_form/sms/gateway/' . JString::strtolower($gateway);
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
		$config = JFactory::getConfig();
		$data = $this->formModel->_formData;
		$arDontEmailThesKeys = array();
		/*remove raw file upload data from the email*/
		foreach ($_FILES as $key => $file)
		{
			$arDontEmailThesKeys[] = $key;
		}
		$message = "";
		$pluginManager = FabrikWorker::getPluginManager();
		$groups = $this->formModel->getGroupsHiarachy();
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
		$message = JText::_('PLG_FORM_SMS_FROM') . $config->getValue('sitename') . "\r \n \r \nMessage:\r \n" . stripslashes($message);
		return $message;
	}

}
