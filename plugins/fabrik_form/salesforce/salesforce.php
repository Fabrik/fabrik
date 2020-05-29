<?php
/**
 * Submit or update data to Salesforce.com
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.salesforce
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Submit or update data to Salesforce.com
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.salesforce
 * @since       3.0
 */

class PlgFabrik_FormSalesforce extends PlgFabrik_Form
{
	/**
	 * Build the Salesforce.com API client
	 *
	 * @return  SforcePartnerClient
	 */

	private function client()
	{
		// Get the path to the Toolkit, set in the options on install.
		$toolkit_path = JPATH_SITE . '/components/com_fabrik/libs/salesforce';

		// Ok, now use SOAP to send the information to SalesForce
		require_once $toolkit_path . '/soapclient/SforcePartnerClient.php';
		require_once $toolkit_path . '/soapclient/SforceHeaderOptions.php';

		// Salesforce Login information
		$wsdl = $toolkit_path . '/soapclient/partner.wsdl.xml';

		// Process of logging on and getting a salesforce.com session
		$client = new SforcePartnerClient;
		$client->createConnection($wsdl);

		return $client;
	}

	/**
	 * Sets up HTML to be injected into the form's bottom
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function getBottomContent()
	{
		if (!class_exists('SoapClient'))
		{
			throw new Exception(FText::_('PLG_FORM_SALESFORCE_ERR_SOAP_NOT_INSTALLED'));
		}
	}

	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @return	bool
	 */
	public function onAfterProcess()
	{
		@ini_set("soap.wsdl_cache_enabled", "0");

		/** @var FabrikFEModelForm $formModel */
		$formModel = $this->getModel();
		$client = $this->client();
		$params = $this->getParams();
		$userName = $params->get('salesforce_username');
		$password = $params->get('salesforce_password');
		$token = $params->get('salesforce_token');

		if (empty($userName))
		{
			$config = JComponentHelper::getParams('com_fabrik');
			$userName = $config->get('fabrik_salesforce_username', '');
			$password = $config->get('fabrik_salesforce_password', '');
			$token = $config->get('fabrik_salesforce_token', '');

			if (empty($userName))
			{
				throw new Exception('No SalesForce credentials supplied!');
			}
		}

		$updateObject = $params->get('salesforce_updateobject', 'Lead');
		$loginResult = $client->login($userName, $password . $token);

		$givenObject = array($updateObject);
		$fields = $client->describeSObjects($givenObject)->fields;
		$submission = array();

		// Map the posted data into acceptable fields
		foreach ($fields as $f)
		{
			$name = $f->name;

			foreach ($formModel->fullFormData as $key => $val)
			{
				if (is_array($val))
				{
					$val = implode(';', $val);
				}

				$key = array_pop(explode('___', $key));

				if (JString::strtolower($key) == JString::strtolower($name) && JString::strtolower($name) != 'id')
				{
					$submission[$name] = $val;
				}
				else
				{
					// Check custom fields
					if (JString::strtolower($key . '__c') == JString::strtolower($name) && JString::strtolower($name) != 'id')
					{
						$submission[$name] = $val;
					}
				}
			}
		}

		$key = $formModel->getlistModel()->getPrimaryKey(true);
		$customKey = $params->get('salesforce_customid') . '__c';

		if ($params->get('salesforce_allowupsert', 0))
		{
			$submission[$customKey] = $formModel->fullFormData[$key];
		}

		$sObjects = array();
		$sObject = new sObject;

		// Salesforce Table or object that you will perform the upsert on
		$sObject->type = $updateObject;
		$sObject->fields = $submission;
		array_push($sObjects, $sObject);

		if ($params->get('salesforce_allowupsert', 0))
		{
			$result = $this->upsert($client, $sObjects, $customKey);
		}
		else
		{
			$result = $this->insert($client, $sObjects);
		}

		if ($result->success == 1)
		{
			if ($result->created == '' && $params->get('salesforce_allowupsert', 0))
			{
				$this->app->enqueueMessage(JText::sprintf(SALESFORCE_UPDATED, $updateObject));
			}
			else
			{
				$this->app->enqueueMessage(JText::sprintf(SALESFORCE_CREATED, $updateObject));
			}
		}
		else
		{
			if (isset($result->errors))
			{
				if (is_array($result->errors))
				{
					foreach ($result->errors as $error)
					{
						JError::raiseWarning(500, FText::_('SALESFORCE_ERR') . $error->message);
					}
				}
				else
				{
					JError::raiseWarning(500, FText::_('SALESFORCE_ERR') . $result->errors->message);
				}
			}
			else
			{
				JError::raiseWarning(500, JText::sprintf(SALESFORCE_NOCREATE, $updateObject));
			}
		}
	}

	/**
	 * Update or insert an object
	 *
	 * @param   object  $client    Salesforce client
	 * @param   array   $sObjects  array of sObjects
	 * @param   string  $key       External Id
	 *
	 * @return  mixed  UpsertResult or error
	 */

	protected function upsert($client, $sObjects, $key)
	{
		try
		{
			// The upsert process
			$results = $client->upsert($key, $sObjects);

			return $results;
		}
		catch (exception $e)
		{
			/* This is reached if there is a major problem in the data or with
			 * the salesforce.com connection. Normal data errors are caught by
			 * salesforce.com
			 */
			return $e;
		}
	}

	/**
	 * Insert an object
	 *
	 * @param   object  $client    Salesforce client
	 * @param   array   $sObjects  array of sObjects
	 *
	 * @return  mixed  UpsertResult or error
	 */

	protected function insert($client, $sObjects)
	{
		try
		{
			// The create process
			$results = $client->create($sObjects);

			return $results;
		}
		catch (exception $e)
		{
			/* This is reached if there is a major problem in the data or with
			 * the salesforce.com connection. Normal data errors are caught by
			 * salesforce.com
			 */
			return $e;
		}
	}
}
