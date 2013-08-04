<?php
/**
 * Email list plugin model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Email list plugin model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @since       3.0
 */

class PlgFabrik_ListEmail extends PlgFabrik_List
{

	/**
	 * Button prefix
	 *
	 * @var string
	 */
	protected $buttonPrefix = 'email';

	/**
	 * Plugin name
	 * @var string
	 */
	var $name = "plgFabrik_ListEmail";

	/**
	 * pop up window
	 *
	 * @deprecated - not used
	 *
	 * @return  void
	 */

	public function onPopupwin()
	{
		echo ' hre lklfsd k popupwin';
	}

	/**
	 * Can the plug-in select list rows
	 *
	 * @return  bool
	 */

	public function canSelectRows()
	{
		return $this->canUse();
	}

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 */

	protected function getAclParam()
	{
		return 'emailtable_access';
	}

	/**
	 * Prep the button if needed
	 *
	 * @param   object  $params  plugin params
	 * @param   object  &$model  list model
	 * @param   array   &$args   arguements
	 *
	 * @return  bool;
	 */

	public function button($params, &$model, &$args)
	{
		parent::button($params, $model, $args);
		return true;
	}

	/**
	 * Get the button label
	 *
	 * @return  string
	 */

	protected function buttonLabel()
	{
		return $this->getParams()->get('email_button_label', parent::buttonLabel());
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   object  $params  plugin parameters
	 * @param   object  $model   list model
	 * @param   array   $args    array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 */

	public function onLoadJavascriptInstance($params, $model, $args)
	{
		FabrikHelperHTML::slimbox();
		parent::onLoadJavascriptInstance($params, $model, $args);
		$opts = $this->getElementJSOptions($model);
		$opts->renderOrder = $this->renderOrder;
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListEmail($opts)";
		return true;
	}

	/**
	 * Get the html to create the <to> list
	 *
	 * @return string
	 */

	public function orig_getToField()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$this->id = $input->getInt('id');
		$params = $this->getParams();
		$renderOrder = $input->getInt('renderOrder');
		$toType = $params->get('emailtable_to_type');
		$toType = is_array($toType) ? $toType[$renderOrder] : $toType;
		$email_to = '';
		if ($toType == 'field')
		{
			$to = $params->get('emailtable_to');
			$to = is_array($to) ? $to[$renderOrder] : $to;
			switch ($params->get('emailtable_email_to_field_how', 'readonly'))
			{
				case 'editable':
					$email_to = '<input name="list_email_to" id="list_email_to" value="' . $to . '" />';
					break;
				case 'hidden':
					$email_to = '<input name="list_email_to" id="list_email_to" value="' . $to . '" type="hidden" />';
					break;
				case 'readonly':
				default:
					$email_to = '<input name="list_email_to" id="list_email_to" value="' . $to . '" readonly="readonly" />';
					break;
			}

		}
		else
		{
			$email_to = $this->formModel->getElementList('list_email_to');
		}
		return $email_to;
	}

	/**
	 * Get to field
	 *
	 * @return string
	 */

	public function getToField()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$this->_type = 'table';
		$this->_id = $input->getInt('id', 0);
		$params = $this->getParams();
		$renderOrder = $input->getInt('renderOrder');
		$toType = $params->get('emailtable_to_type');
		$toType = is_array($toType) ? JArrayHelper::getValue($toType, $renderOrder, 'list') : $toType;
		if ($toType == 'field')
		{
			$email_to = '';
			$to = $params->get('emailtable_to');
			$to = is_array($to) ? JArrayHelper::getValue($to, $renderOrder) : $to;
			switch ($params->get('emailtable_email_to_field_how', 'readonly'))
			{
				case 'editable':
					$email_to = '<input name="list_email_to" id="list_email_to" value="' . $to . '" />';
					break;
				case 'hidden':
					$email_to = '<input name="list_email_to" id="list_email_to" value="' . $to . '" type="hidden" />';
					break;
				case 'readonly':
				default:
					$email_to = '<input name="list_email_to" id="list_email_to" value="' . $to . '" readonly="readonly" />';
					break;
			}
			return $email_to;
		}
		elseif ($toType == 'list')
		{
			return $this->formModel->getElementList('list_email_to');
		}
		elseif ($toType == 'table' || $toType == 'table_picklist')
		{
			$emailtable_to_table_table = $params->get('emailtable_to_table_table');
			if (is_array($emailtable_to_table_table))
			{
				$emailtable_to_table_table = $emailtable_to_table_table[$renderOrder];
			}
			$emailtable_to_table_email = $params->get('emailtable_to_table_email');
			if (is_array($emailtable_to_table_email))
			{
				$emailtable_to_table_email = $emailtable_to_table_email[$renderOrder];
			}
			$emailtable_to_table_name = $params->get('emailtable_to_table_name');
			if (is_array($emailtable_to_table_name))
			{
				$emailtable_to_table_name = $emailtable_to_table_name[$renderOrder];
			}
			if (empty($emailtable_to_table_name))
			{
				$emailtable_to_table_name = $emailtable_to_table_email;
			}

			$toTableModel = JModel::getInstance('list', 'FabrikFEModel');
			$toTableModel->setId($emailtable_to_table_table);
			$toDb = $toTableModel->getDb();

			$emailtable_to_table_name = FabrikString::safeColName($emailtable_to_table_name);
			$emailtable_to_table_email = FabrikString::safeColName($emailtable_to_table_email);
			$emailtable_to_table = $toDb->nameQuote($toTableModel->getTable()->db_table_name);

			$query = $toDb->getQuery(true);
			$query->select($emailtable_to_table_email . ' AS email, ' . $emailtable_to_table_name . ' AS name')
			->from($emailtable_to_table)->order('name ASC');
			$toDb->setQuery($query);
			$results = $toDb->loadObjectList();
			if (empty($results))
			{
				return JText::_('PLG_LIST_EMAIL_TO_TABLE_NO_DATA');
			}
			$empty = new stdClass;

			if ($toType == 'table_picklist')
			{
				// $$$ hugh - yeah yeah, I'll move these into assets when I get a spare minute or three.
				$html = '
	<style type="text/css">
		.fabrik_email_holder	{ width:200px; float:left; }
		#email_add,#email_remove	{ display:block; width:150px; text-align:center; border:1px solid #ccc; background:#eee; }
		.fabrik_email_holder select	{ margin:0 0 10px 0; width:150px; padding:5px; height:200px; }
	</style>
	<script type="text/javascript">
		window.addEvent(\'domready\', function() {
			$(\'email_add\').addEvent(\'click\', function() {
				$(\'email_to_selectfrom\').getSelected().each(function(el) {
					el.inject($(\'list_email_to\'));
				});
			});
			$(\'email_remove\').addEvent(\'click\', function() {
				$(\'list_email_to\').getSelected().each(function(el) {
					el.inject($(\'email_to_selectfrom\'));
				});
			});
		});
	</script>
	';
				$html .= '<div class="fabrik_email_holder">';
				$html .= JHTML::_('select.genericlist', $results, 'email_to_selectfrom[]', 'class="fabrikinput inputbox" multiple="multiple" size="5"', 'email', 'name', '', 'email_to_selectfrom');
				$html .= '<a href="javascript:;" id="email_add">add &gt;&gt;</a>';
				$html .= '</div>';
				$html .= '<div class="fabrik_email_holder">';
				$html .= JHTML::_('select.genericlist', $empty, 'list_email_to[]', 'class="fabrikinput inputbox" multiple="multiple" size="5"', 'email', 'name', '', 'list_email_to');
				$html .= '<a href="javascript:;" id="email_remove">&lt;&lt; remove</a>';
				$html .= '</div>';
				$html .= '<div style="clear:both;"></div>';
			}
			else
			{
				$html = JHTML::_('select.genericlist', $results, 'list_email_to[]', 'class="fabrikinput inputbox" multiple="multiple" size="5"', 'email', 'name', '', 'list_email_to');
			}

			return $html;
		}
	}
	/**
	 * Are attachements allowed?
	 *
	 * @return bool
	 */

	public function getAllowAttachment()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$renderOrder = $input->getInt('renderOrder');
		$params = $this->getParams();
		$allow = $params->get('emailtable_allow_attachment');
		return is_array($allow) ? JArrayHelper::getValue($allow, $renderOrder, false) : $allow;
	}

	/**
	* Get the show to field
	*
	* @return  string
	*/

	public function getShowToField()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$renderOrder = $input->getInt('renderOrder');
		$params = $this->getParams();
		$var = $params->get('emailtable_email_to_field_how', 'readonly');
		$var = is_array($var) ? JArrayHelper::getValue($var, $renderOrder, 'readonly') : $var;
		$toType = $params->get('emailtable_to_type', 'list');
		$toType = is_array($toType) ? JArrayHelper::getValue($toType, $renderOrder, 'single') : $toType;

		// Can only hide To if it's the simple field type, as all others require user input
		return !($var == 'hidden' && $toType == 'field');
	}

	/**
	* Get the show subject line
	*
	* @return  string
	*/

	public function getShowSubject()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$renderOrder = $input->getInt('renderOrder');
		$params = $this->getParams();
		$var = $params->get('emailtable_hide_subject');
		return (is_array($var) ? JArrayHelper::getValue($var, $renderOrder, '') : $var) == '0';
	}

	/**
	 * Get the subject line
	 *
	 * @return  string
	 */

	public function getSubject()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$renderOrder = $input->getInt('renderOrder');
		$params = $this->getParams();
		$var = $params->get('email_subject');
		return is_array($var) ? JArrayHelper::getValue($var, $renderOrder, '') : $var;
	}

	/**
	 * Get the email message
	 *
	 * @return  string
	 */

	public function getMessage()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$renderOrder = $input->getInt('renderOrder');
		$params = $this->getParams();
		$var = $params->get('email_message');
		return is_array($var) ? JArrayHelper::getValue($var, $renderOrder, '') : $var;
	}

	/**
	 * Get the selected records
	 *
	 * @param   string  $key      key
	 * @param   bool    $allData  data
	 *
	 * @return	array	rows
	 */

	public function getRecords($key = 'ids', $allData = false)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		if ($key === 'recordids')
		{
			$ids = explode(',', $input->get($key, '', 'string'));
		}
		else
		{
			$ids = (array) $input->get($key, array(), 'array');
		}

		JArrayHelper::toInteger($ids);
		if (empty($ids))
		{
			throw new RuntimeException(JText::_('PLG_LIST_EMAIL_ERR_NO_RECORDS_SELECTED'), 400);
		}
		$renderOrder = $input->getInt('renderOrder');
		$params = $this->getParams();
		$model = $this->listModel;
		$pk = $model->getTable()->db_primary_key;
		$pk2 = FabrikString::safeColNameToArrayKey($pk) . '_raw';
		$whereClause = "($pk IN (" . implode(",", $ids) . "))";
		$cond = $params->get('emailtable_condition');
		if (trim($cond) !== '')
		{
			$whereClause .= ' AND (' . $cond . ')';
		}
		$model->setPluginQueryWhere($this->buttonPrefix, $whereClause);
		$data = $model->getData();
		if ($allData)
		{
			return $data;
		}
		$return = array();
		foreach ($data as $gdata)
		{
			foreach ($gdata as $row)
			{
				$return[] = $row->$pk2;
			}
		}
		return $return;
	}

	/**
	 * Upload the attachments to the server
	 *
	 * @return  bool success/fail
	 */

	private function _upload()
	{
		jimport('joomla.filesystem.file');
		jimport('joomla.client.helper');
		$app = JFactory::getApplication();
		$input = $app->input;
		JClientHelper::setCredentialsFromRequest('ftp');
		$files = $input->files->get('attachement', array());
		$folder = JPATH_ROOT . '/images/stories';
		$this->filepath = array();
		foreach ($files as $file)
		{
			$name = $file['name'];
			if ($name == '')
			{
				continue;
			}
			$path = $folder . '/' . strtolower($name);
			if (!JFile::upload($file['tmp_name'], $path))
			{
				JError::raiseWarning(100, JText::_('PLG_LIST_EMAIL_ERR_CANT_UPLOAD_FILE'));
				return false;
			}
			else
			{
				$this->filepath[] = $path;
			}
		}
		return true;
	}

	/**
	 * Send the email
	 *
	 * @return boolean
	 */

	public function orig_doEmail()
	{
		$listModel = $this->listModel;
		$app = JFactory::getApplication();
		$input = $app->input;
		jimport('joomla.mail.helper');
		if (!$this->_upload())
		{
			return false;
		}
		$listId = $input->getInt('id', 0);
		$this->setId($listId);
		$listModel->setId($listId);
		$w = new FabrikWorker;
		$config = JFactory::getConfig();
		$params = $this->getParams();
		$to = $input->get('list_email_to');
		$renderOrder = $input->getInt('renderOrder');
		$toType = $params->get('emailtable_to_type', 'list');
		$fromUser = $params->get('emailtable_from_user');
		if ($toType == 'list')
		{
			$to = str_replace('.', '___', $to);
		}

		if ($toType == 'list' && $to == '')
		{
			throw new RuntimeException(JText::_('PLG_LIST_EMAIL_ERR_NO_TO_ELEMENT_SELECTED'), 500);
		}
		$subject = $input->get('subject', '', 'string');
		$message = $input->get('message', '', 'html');
		$message = nl2br($message);
		$data = $this->getRecords('recordids', true);
		if ($fromUser)
		{
			$my = JFactory::getUser();
			$from = $my->get('email');
			$fromname = $my->get('name');
		}
		else
		{
			$from = $config->get('mailfrom');
			$fromname = $config->get('fromname');
		}

		$email_from = $config->get('mailfrom');
		$cc = null;
		$bcc = null;
		$sent = 0;
		$notsent = 0;
		$updated = array();
		foreach ($data as $group)
		{

			foreach ($group as $row)
			{
				if ($toType == 'list')
				{
					$process = isset($row->$to);
					$mailto = $row->$to;
				}
				else
				{
					$process = true;
					$mailto = $to;
				}
				if ($process)
				{
					$mailtos = explode(',', $mailto);
					foreach ($mailtos as $mailto)
					{
						$thisMailto = $w->parseMessageForPlaceholder($mailto, $row);
						if (FabrikWorker::isEmail($thisMailto))
						{
							$thissubject = $w->parseMessageForPlaceholder($subject, $row);
							$thismessage = $w->parseMessageForPlaceholder($message, $row);

							// Get a JMail instance (have to get a new instnace otherwise the receipients are appended to previously added recipients)
							$mail = JFactory::getMailer();
							$res = $mail->sendMail($email_from, $email_from, $thisMailto, $thissubject, $thismessage, true, $cc, $bcc, $this->filepath);
							if ($res)
							{
								$sent++;
							}
							else
							{
								$notsent++;
							}
						}
						else
						{
							$notsent++;
						}
					}
					if ($res)
					{
						$updated[] = $row->__pk_val;
					}
				}
				else
				{
					$notsent++;
				}
			}
		}
		if (!empty($updated))
		{
			$updateField = $params->get('emailtable_update_field');
			$updateVal = $params->get('emailtable_update_value');
			$listModel->updateRows($updated, $updateField, $updateVal);
		}
		$app->enqueueMessage(JText::sprintf('PLG_LIST_EMAIL_N_SENT', $sent));
		if ($notsent != 0)
		{
			JError::raiseWarning(E_NOTICE, JText::sprintf('PLG_LIST_EMAIL_N_NOT_SENT', $notsent));
		}
	}

	/**
	 * Do email
	 *
	 * @return boolean
	 */

	public function doEmail()
	{
		$listModel = $this->listModel;
		$app = JFactory::getApplication();
		$input = $app->input;
		jimport('joomla.mail.helper');
		if (!$this->_upload())
		{
			return false;
		}

		$listModel->setId($input->getInt('id', 0));
		$w = new FabrikWorker;
		$config = JFactory::getConfig();
		$this->_type = 'table';
		$params = $this->getParams();
		$renderOrder = $input->getInt('renderOrder');

		$merge_emails = $params->get('emailtable_mergemessages', 0);
		if (is_array($merge_emails))
		{
			$merge_emails = (int) JArrayHelper::getValue($merge_emails, $renderOrder, 0);
		}

		$toHow = $params->get('emailtable_to_how', 'single');
		if (is_array($toHow))
		{
			$toHow = JArrayHelper::getValue($toHow, $renderOrder, 'single');
		}

		$toType = $params->get('emailtable_to_type', 'list');
		if (is_array($toType))
		{
			$toType = JArrayHelper::getValue($toType, $renderOrder, 'list');
		}
		if ($toType == 'table' || $toType == 'table_picklist')
		{
			$to = $input->get('list_email_to', '', 'array');
		}
		else
		{
			$to = $input->get('list_email_to', '', 'string');
		}
		if ($toType == 'list')
		{
			$to = str_replace('.', '___', $to);
		}
		else
		{
			if (is_array($to))
			{
				// $$$ hugh - if using a table selection type, allow specifying a default in
				// the "emailtable_to" field.
				if ($toType != 'field')
				{
					$emailtable_to = $params->get('emailtable_to', '');
					if (is_array($emailtable_to))
					{
						$emailtable_to  = JArrayHelper::getValue($emailtable_to, $renderOrder, '');
					}
					if (!empty($emailtable_to))
					{
						if (!in_array($emailtable_to, $to))
						{
							$to[] = $emailtable_to;
						}
					}
				}
				$to = implode(',', $to);
			}
		}

		$fromUser = $params->get('emailtable_from_user');
		if (is_array($fromUser))
		{
			$fromUser = JArrayHelper::getValue($fromUser, $renderOrder, '');
		}

		$emailTemplate = $params->get('emailtable_template', '');
		if (is_array($emailTemplate))
		{
			$emailTemplate = JArrayHelper::getValue($emailTemplate, $renderOrder, '');
		}
		if ($emailTemplate != "-1" && !empty($emailTemplate))
		{
			$emailTemplate = JPath::clean(JPATH_SITE . '/plugins/fabrik_list/email/tmpl/' . $emailTemplate);
		}

		$contentTemplate = $params->get('emailtable_template_content', '');
		if (is_array($contentTemplate))
		{
			$contentTemplate = JArrayHelper::getValue($contentTemplate, $renderOrder, '');
		}
		$content = empty($contentTemplate) ? '' : FabrikHelperHTML::getContentTemplate($contentTemplate);

		$php_msg = false;
		if (JFile::exists($emailTemplate))
		{
			if (JFile::getExt($emailTemplate) == 'php')
			{
				// $message = $this->_getPHPTemplateEmail($emailTemplate);
				$message = '';
				$php_msg = true;
			}
			else
			{
				$message = FabrikHelperHTML::getTemplateFile($emailTemplate);
			}
			$message = str_replace('{content}', $content, $message);
		}
		else
		{
			$message = $contentTemplate != '' ? $content : '';
		}

		$subject = $input->get('subject', '', 'string');

		// $$$ hugh - may need to allow html
		$cover_message = nl2br($input->get('message', '', 'html'));
		$old_style = false;
		if (empty($message) && !$php_msg)
		{
			$old_style = true;

			// $message = $cover_message;
		}
		$data = $this->getRecords('recordids', true);
		if ($fromUser)
		{
			$my = JFactory::getUser();
			$email_from = $my->get('email');
			$fromname = $my->get('name');
		}
		else
		{
			$config = JFactory::getConfig();
			$email_from = $config->getValue('mailfrom');
			$fromname = $config->getValue('fromname');
		}

		$cc = null;
		$bcc = null;
		$sent = 0;
		$notsent = 0;
		$updated = array();
		$merged_msg = '';
		$first_row = array();
		foreach ($data as $group)
		{
			foreach ($group as $row)
			{
				if ($merge_emails)
				{
					if (empty($first_row))
					{
						// Used for placeholders in subject when merging mail
						$first_row = $row;
					}
					$thismsg = '';
					if ($old_style)
					{
						$thismsg = $w->parseMessageForPlaceHolder($cover_message, $row);
					}
					else
					{
						if ($php_msg)
						{
							$thismsg = $this->_getPHPTemplateEmail($emailTemplate, $row, $listModel);
						}
						else
						{
							$thismsg = $w->parseMessageForPlaceHolder($message, $row);
						}
					}
					$merged_msg .= $thismsg;
					$updated[] = $row->__pk_val;
				}
				else
				{

					if ($toType == 'list')
					{
						$process = isset($row->$to);
						$mailto = $row->$to;
					}
					else
					{
						$process = true;
						$mailto = $w->parseMessageForPlaceHolder($to, $row);
					}
					if ($process)
					{
						$res = false;
						$mailtos = explode(',', $mailto);
						if ($toHow == 'single')
						{
							foreach ($mailtos as $tokey => $thisto)
							{
								$thisto = $w->parseMessageForPlaceholder($thisto, $row);
								if (!FabrikWorker::isEmail($thisto))
								{
									unset($mailtos[$tokey]);
									$notsent++;
								}
								else
								{
									$mailtos[$tokey] = $thisto;
								}
							}
							if ($notsent > 0)
							{
								$mailtos = array_values($mailtos);
							}
							$sent = count($mailtos);
							if ($sent > 0)
							{
								$thissubject = $w->parseMessageForPlaceholder($subject, $row);
								$thismsg = '';
								$thismsg = $cover_message;
								if (!$old_style)
								{
									if ($php_msg)
									{
										$thismsg .= $this->_getPHPTemplateEmail($emailTemplate, $row, $listModel);
									}
									else
									{
										$thismsg .= $message;
									}
								}
								$thismsg = $w->parseMessageForPlaceholder($thismsg, $row);
								$res = JUtility::sendMail($email_from, $fromname, $mailtos, $thissubject, $thismsg, 1, $cc, $bcc, $this->filepath);
							}
						}
						else
						{
							foreach ($mailtos as $mailto)
							{
								$mailto = $w->parseMessageForPlaceholder($mailto, $row);
								if (FabrikWorker::isEmail($mailto))
								{
									$thissubject = $w->parseMessageForPlaceholder($subject, $row);
									$thismsg = '';
									$thismsg = $cover_message;
									if (!$old_style)
									{
										if ($php_msg)
										{
											$thismsg .= FabrikHelperHTML::getPHPTemplate($emailTemplate, $row, $listModel);
										}
										else
										{
											$thismsg .= $message;
										}
									}
									$thismsg = $w->parseMessageForPlaceholder($thismsg, $row);
									$res = JUtility::sendMail($email_from, $fromname, $mailto, $thissubject, $thismsg, 1, $cc, $bcc, $this->filepath);
									if ($res)
									{
										$sent ++;
									}
									else
									{
										$notsent ++;
									}
								}
								else
								{
									$notsent ++;
								}
							}
						}
						if ($res)
						{
							$updated[] = $row->__pk_val;
						}
					}
					else
					{
						$notsent ++;
					}
				}
			}
		}
		if ($merge_emails)
		{
			// Arbitrarily use first row for placeholders
			if ($toType == 'list')
			{
				$mailto = $first_row->$to;
			}
			else
			{
				$mailto = $to;
			}
			$thistos = explode(',', $w->parseMessageForPlaceHolder($mailto, $first_row));
			$thissubject = $w->parseMessageForPlaceHolder($subject, $first_row);
			$preamble = $params->get('emailtable_message_preamble', '');
			$preamble = is_array($preamble) ? JArrayHelper::getValue($preamble, $renderOrder, '') : $preamble;
			$postamble = $params->get('emailtable_message_postamble', '');
			$postamble = is_array($postamble) ? JArrayHelper::getValue($postamble, $renderOrder, '') : $postamble;
			$merged_msg = $preamble . $merged_msg . $postamble;
			if (!$old_style)
			{
				$merged_msg = $cover_message . $merged_msg;
			}

			if ($toHow == 'single')
			{
				foreach ($thistos as $tokey => $thisto)
				{
					$thisto = $w->parseMessageForPlaceholder($thisto, $first_row);
					if (!FabrikWorker::isEmail($thisto))
					{
						unset($thistos[$tokey]);
						$notsent++;
					}
					else
					{
						$mailtos[$tokey] = $thisto;
					}
				}
				if ($notsent > 0)
				{
					$thistos = array_values($thistos);
				}
				$sent = count($thistos);
				if ($sent > 0)
				{
					$res = JUTility::sendMail($email_from, $fromname, $thistos, $thissubject, $merged_msg, true, $cc, $bcc, $this->filepath);
				}
			}
			else
			{
				foreach ($thistos as $thisto)
				{
					if (FabrikWorker::isEmail($thisto))
					{
						$res = JUTility::sendMail($email_from, $fromname, $thisto, $thissubject, $merged_msg, true, $cc, $bcc, $this->filepath);
						if ($res)
						{
							$sent ++;
						}
						else
						{
							$notsent ++;
						}
					}
					else
					{
						$notsent++;
					}
				}
			}
		}
		$updateField = $params->get('emailtable_update_field');
		$updateField = is_array($updateField) ? JArrayHelper::getValue($updateField, $renderOrder, '') : $updateField;
		if (!empty($updateField) && !empty($updated))
		{
			$updateVal = $params->get('emailtable_update_value');
			$updateVal = is_array($updateVal) ? JArrayHelper::getValue($updateVal, $renderOrder, '') : $updateVal;
			$listModel->updateRows($updated, $updateField, $updateVal);
		}

		// $$$ hugh - added second update field for Bea
		$updateField = $params->get('emailtable_update_field2');
		$updateField = is_array($updateField) ? JArrayHelper::getValue($updateField, $renderOrder, '') : $updateField;
		if (!empty($updateField) && !empty($updated))
		{
			$updateVal = $params->get('emailtable_update_value2');
			$updateVal = is_array($updateVal) ? JArrayHelper::getValue($updateVal, $renderOrder, '') : $updateVal;
			$listModel->updateRows($updated, $updateField, $updateVal);
		}
		$app->enqueueMessage(JText::sprintf('%s emails sent', $sent));
		if ($notsent != 0)
		{
			JError::raiseWarning(E_NOTICE, JText::sprintf('%s emails not sent', $notsent));
		}
	}

}
