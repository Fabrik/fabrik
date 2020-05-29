<?php
/**
 * Consent request plugin for Fabrik forms
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.acymailing
 * @copyright   Copyright (C) 2005-2020  Better Web - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
 
// No direct access
defined('_JEXEC') or die('Restricted access');

 use \Joomla\CMS\Date\Date;
 
// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Consent request plugin for Fabrik forms
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.gdpr
 * @since       3.8
 */

class PlgFabrik_FormConsent extends PlgFabrik_Form
{
	protected $html = null;

	/**
	 * Set up the html to be injected into the bottom of the form
	 *
	 * @return  void
	 */

	public function getBottomContent()
	{
		$params    = $this->getParams();
		$formModel = $this->getModel();

		if ($params->get('consent_terms', true))
		{
			$layout = $this->getLayout('form');
			$layoutData = new stdClass;

			$errors = $formModel->getErrors();

			if (array_key_exists('consent_required', $errors))
			{
				$layoutData->consentErrClass = '';
			}
			else
			{
				$layoutData->consentErrClass = 'fabrikHide';
			}

			if(array_key_exists('consent_remove', $errors))
			{
				$layoutData->removeErrClass = '';
			}
			else
			{
				$layoutData->removeErrClass = 'fabrikHide';
			}

			$layoutData->consentErrText  = FText::_('PLG_FORM_CONSENT_PLEASE_CONFIRM_CONSENT');
			$layoutData->removeErrText  = FText::_('PLG_FORM_CONSENT_REMOVE_CONSENT');
			$layoutData->useFieldset   = $params->get('consent_fieldset', '0') === '1';
			$layoutData->fieldsetClass = $params->get('consent_fieldset_class', '');
			$layoutData->legendClass   = $params->get('consent_legend_class', '');
			$layoutData->legendText    = FText::_($params->get('consent_legend', ''));
			$layoutData->showConsent   = $params->get('consent_terms', '0') === '1';
			$layoutData->consentIntro  = FText::_($params->get('consent_intro_terms'));
			$layoutData->consentText   = FText::_($params->get('consent_terms_text'));
			$this->html 			   = $layout->render($layoutData);
		}
		else
		{
			$this->html = '';
		}

		$opts = new \StdClass();
		$opts->renderOrder = $this->renderOrder;
		$opts->formid  = $formModel->getId();
		$opts = json_encode($opts);

		$this->formJavascriptClass($params, $formModel);
		$formModel->formPluginJS['Consent' . $this->renderOrder] = 'new Consent(' . $opts . ')';

	}

	/**
	 * Inject custom html into the bottom of the form
	 *
	 * @param   int  $c  Plugin counter
	 *
	 * @return  string  html
	 */

	public function getBottomContent_result($c)
	{
		return $this->html;
	}

	/**
	 * Run right before the form processing
	 * keeps the data to be processed or sent if consent is not given
	 *
	 * @return	bool
	 */
	
	public function onBeforeProcess()
	{
		$formModel = $this->getModel();
		
		if(!array_key_exists('fabrik_contact_consent', $formModel->formData) && $formModel->isNewRecord())
		{
			$formModel->errors['consent_required'] = array(FText::_('PLG_FORM_CONSENT_PLEASE_CONFIRM_CONSENT'));
			$formModel->formErrorMsg = FText::_('PLG_FORM_CONSENT_PLEASE_CONFIRM_CONSENT');
			return false;
		}
		elseif(!array_key_exists('fabrik_contact_consent', $formModel->formData))
		{
			$formModel->errors['consent_remove'] = array(FText::_('PLG_FORM_CONSENT_REMOVE_CONSENT'));
			$formModel->formErrorMsg = FText::_('PLG_FORM_CONSENT_PLEASE_CONFIRM_CONSENT');
			return false;
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
		$params    = $this->getParams(); 
		$formModel = $this->getModel();
		$data 	   = $this->getProcessData();
		$filter    = JFilterInput::getInstance();
		$post      = $filter->clean($_POST, 'array');
		$contact   = array_key_exists('fabrik_contact_consent', $post);
		$rowid	   = $post['rowid'];
		$user 	   = JFactory::getUser();
		
		if($params->get('consent_juser', '0') === '1')
		{
			$userIdField = $this->getFieldName('consent_field_userid');
			$userId 	 = $data[$userIdField];
		}

		// If consent is missing for contact, do nothing
		if ($rowid && !$contact)
		{
			return;
		}
		
		// Record consent
		// To be valid a consent must record the date/time of the consent, the identity of the user and the consent message he agreed to.
		// If you edit a user's data, you must keep a record of the change
		if($rowid || $params->get('consent_juser', '0') === '1')
		{
			// Flag the record when user's data are updated
			if(!$rowid)
			{
				$update = '0';
			}
			else
			{
				$update = '1';
				// If an admin updates the user's data, the user has to be informed via email
				// No email is sent if the user updates his own data
				
				if($user->get('id') != $userId)
				{ 
					$userEmailField = $this->getFieldName('consent_field_email');
					$userEmail		= $data[$userEmailField];
					
					$this->sendUpdateWarning($userEmail);
				}
			}
		}
		else
		{
			$userId = 0;
			$update = 0;
		}
		
		$this->savePrivacy($data, $userId, $update);
		
		return;
	}
	
	/**
	 * Run from list model when deleting rows
	 *
	 * @param   array &$groups List data for deletion
	 *
	 * @return    bool
	 */
	public function onDeleteRowsForm(&$groups)
	{	
		$params    = $this->getParams();
		$formModel = $this->getModel();
		$listModel = $formModel->getListModel();
		
		//// Records log of deletion of a user's consent
		foreach ($groups as $group)
		{
			foreach ($group as $rows)
			{
				foreach ($rows as $row)
				{
					$userId = 0;
					if($params->get('consent_juser', '0') === '1')
					{
						$userIdField = $this->getFieldName('consent_field_userid');				
						$userId 	 = $row->$userIdField;
					}
					$data['listid'] = $listModel->getId();
					$data['formid'] = $formModel->getid();
					$data['rowid']  = $row->id;
					
					$this->savePrivacy($data, $userId, 2);
				}
			}
		 }
		
		return;
	}
	
	/**
	 * Insert record in privacy table
	 *
	 * @param	array	$data submitted data
	 * @param	int		$status status of consent : 0 = new, 1 = update, 2 = remove
	 *
	 * @return	bool
	 */
	protected function savePrivacy($data, $userId, $status)
	{
		$db 	   = FabrikWorker::getDbo();
		$params    = $this->getParams();
		$formModel = $this->getModel();
		
		$now 	   = new JDate('now');
		$listId	   = $data['listid'];
		$formId	   = $data['formid'];
		$rowId	   = $data['rowid'];
		
		$consentMessage = $params->get('consent_terms_text');
		
		// Optional record of the IP address
		$ip = '';
		if($params->get('consent_ip_record', '0') === '1')
		{
			$ip = $_SERVER['REMOTE_ADDR'];
		   
		}
		
		$query 	 = $db->getQuery( true );
		$columns = array('id', 'date_time', 'list_id', 'form_id', 'row_id', 'user_id', 'consent_message', 'update_record','ip', 'newsletter_engine', 'sublist_id', 'subid');
		$values  = array('NULL',
						 $db->quote($now->format('Y-m-d H:i:s')),
						 $db->quote($listId),
						 $db->quote($formId),
						 $db->quote($rowId),
						 $db->quote($userId),
						 $db->quote($consentMessage),
						 $db->quote($status),
						 $db->quote($ip),
						 'NULL',
						 0,
						 0
						 );
		$query->insert($db->quoteName('#__fabrik_privacy'))
			  ->columns($db->quoteName($columns))
			  ->values(implode(',', $values));
		$db->setQuery($query);
		$db->execute();
		
		return;
	}
	
	/**
	 * Send an email to the user when an admin has updated his data
	 *
	 * @param	string	$email	user's email
	 *
	 * @return	bool
	 */
	protected function sendUpdateWarning($email)
	{
		$params = $this->getParams();
		jimport('joomla.mail.helper');
		$w = new FabrikWorker;
		     
		$cc  	   	   	 = null;
		$bcc 	   	   	 = null;
		$emailFrom 	   	 = $returnPath 	   = $this->config->get('mailfrom');
		$emailFromName 	 = $returnPathName = $this->config->get('fromname', $emailFrom);
		$subject 	   	 = $params->get('consent_email_subject');
		$body   	 	 = $params->get('consent_message_body');
		$htmlEmail		 = true;

		if($body == '')
		{
			$this->app->enqueueMessage(JText::_('PLG_FORM_CONSENT_MESSAGE_TEXT_EMPTY'), 'warning');
			return;
		}
		$thisAttachments = array();
		$customHeaders   = array();
		
		if (FabrikWorker::isEmail($email))
		{
			$res = FabrikWorker::sendMail(
					$emailFrom,
					$emailFromName,
					$email,
					$subject,
					$body,
					$htmlEmail,
					$cc,
					$bcc,
					$thisAttachments,
					$returnPath,
					$returnPathName,
					$customHeaders
				);
			
			if ($res !== true)
			{
				$this->app->enqueueMessage(JText::sprintf('PLG_FORM_CONSENT_DID_NOT_SEND_EMAIL', $email), 'warning');
			}
		}
		else
		{
			$this->app->enqueueMessage(JText::sprintf('PLG_FORM_CONSENT_DID_NOT_SEND_EMAIL_INVALID_ADDRESS', $email), 'warning');
		}
		
		return;
	}
}
