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
	protected $buttonPrefix = 'envelope';

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
	 * @param   array  &$args  Arguments
	 *
	 * @return  bool;
	 */

	public function button(&$args)
	{
		parent::button($args);

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
	 * @param   array  $args  Array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 */

	public function onLoadJavascriptInstance($args)
	{
		FabrikHelperHTML::slimbox();
		parent::onLoadJavascriptInstance($args);
		$opts = $this->getElementJSOptions();
		$opts->renderOrder = $this->renderOrder;
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListEmail($opts)";

		return true;
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
					$email_to = '<input type="text" name="list_email_to" id="list_email_to" value="' . $to . '" />';
					break;
				case 'hidden':
					$email_to = '<input name="list_email_to" id="list_email_to" value="' . $to . '" type="hidden" />';
					break;
				case 'readonly':
				default:
					$email_to = '<input type="text" name="list_email_to" id="list_email_to" value="' . $to . '" readonly="readonly" />';
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

			$toTableModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
			$toTableModel->setId($emailtable_to_table_table);
			$toDb = $toTableModel->getDb();

			$emailtable_to_table_name = FabrikString::safeColName($emailtable_to_table_name);
			$emailtable_to_table_email = FabrikString::safeColName($emailtable_to_table_email);
			$emailtable_to_table = $toDb->quoteName($toTableModel->getTable()->db_table_name);

			$query = $toDb->getQuery(true);
			$query->select($emailtable_to_table_email . ' AS email, ' . $emailtable_to_table_name . ' AS name')
			->from($emailtable_to_table)->order('name ASC');
			$toDb->setQuery($query);
			$results = $toDb->loadObjectList();

			if (empty($results))
			{
				return FText::_('PLG_LIST_EMAIL_TO_TABLE_NO_DATA');
			}

			$empty = new stdClass;
			$attribs = 'class="fabrikinput inputbox input-medium" multiple="multiple" size="5"';

			if ($toType == 'table_picklist')
			{
				$html = '<div class="pull-left" style="margin:0 20px 20px 0">';
				$html .= JHTML::_('select.genericlist', $results, 'email_to_selectfrom[]', $attribs, 'email', 'name', '', 'email_to_selectfrom');
				$html .= '<br /><a href="#" class="btn btn-small" id="email_add"><i class="icon-plus"></i> ' . FText::_('COM_FABRIK_ADD') . ' &gt;&gt;</a>';
				$html .= '</div>';
				$html .= '<div class="span6">';
				$html .= JHTML::_('select.genericlist', $empty, 'list_email_to[]', $attribs, 'email', 'name', '', 'list_email_to');
				$html .= '<br /><a href="#" class="btn btn-small" id="email_remove">&lt;&lt; '
					. FText::_('COM_FABRIK_DELETE') . ' <i class="icon-delete"></i></a>';
				$html .= '</div>';
				$html .= '<div style="clear:both"></div>';
			}
			else
			{
				$attribs = 'class="fabrikinput inputbox input-large" multiple="multiple" size="5"';
				$html = JHTML::_('select.genericlist', $results, 'list_email_to[]', $attribs, 'email', 'name', '', 'list_email_to');
			}

			return $html;
		}
	}

	/**
	 * Are attachments allowed?
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
			throw new RuntimeException(FText::_('PLG_LIST_EMAIL_ERR_NO_RECORDS_SELECTED'), 400);
		}

		$renderOrder = $input->getInt('renderOrder');
		$params = $this->getParams();
		$model = $this->listModel;
		$pk = $model->getTable()->db_primary_key;
		$pk2 = FabrikString::safeColNameToArrayKey($pk) . '_raw';
		$whereClause = '(' . $pk . ' IN (' . implode(',', $ids) . '))';
		$cond = $params->get('emailtable_condition');

		if (trim($cond) !== '')
		{
			$whereClause .= ' AND (' . $cond . ')';
		}

		$model->setLimits(0, -1);
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
		$files = $input->files->get('attachment', array());
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
				JError::raiseWarning(100, FText::_('PLG_LIST_EMAIL_ERR_CANT_UPLOAD_FILE'));

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
	 * Get update value. Converts "now()" into current date
	 *
	 * @param   string  $name         Parameter name
	 * @param   int     $renderOrder  Plugin render order
	 *
	 * @return  string
	 */
	protected function updateVal($name, $renderOrder = 0)
	{
		$params = $this->getParams();
		$app = JFactory::getApplication();
		$input = $app->input;
		$updateVal = $params->get($name);
		$updateVal = is_array($updateVal) ? JArrayHelper::getValue($updateVal, $renderOrder, '') : $updateVal;

		if ($updateVal === 'now()')
		{
			$updateVal = JFactory::getDate()->toSql();
		}

		if ($updateVal === '{subject}')
		{
			$updateVal = $input->get('subject', '', 'string');
		}

		return $updateVal;
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
			$email_from = $config->get('mailfrom');
			$fromname = $config->get('fromname');
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
							$thismsg = FabrikHelperHTML::getPHPTemplate($emailTemplate, $row, $listModel);
							//$thismsg = $this->_getPHPTemplateEmail($emailTemplate, $row, $listModel);
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
										$thismsg .= FabrikHelperHTML::getPHPTemplate($emailTemplate, $row, $listModel);
										//$thismsg .= $this->_getPHPTemplateEmail($emailTemplate, $row, $listModel);
									}
									else
									{
										$thismsg .= $message;
									}
								}

								$thismsg = $w->parseMessageForPlaceholder($thismsg, $row);
								$mail = JFactory::getMailer();
								$res = $mail->sendMail($email_from, $fromname, $mailtos, $thissubject, $thismsg, 1, $cc, $bcc, $this->filepath);
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
									$mail = JFactory::getMailer();
									$res = $mail->sendMail($email_from, $fromname, $mailto, $thissubject, $thismsg, 1, $cc, $bcc, $this->filepath);

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
					$mail = JFactory::getMailer();
					$res = $mail->sendMail($email_from, $fromname, $thistos, $thissubject, $merged_msg, true, $cc, $bcc, $this->filepath);
				}
			}
			else
			{
				foreach ($thistos as $thisto)
				{
					if (FabrikWorker::isEmail($thisto))
					{
						$mail = JFactory::getMailer();
						$res = $mail->sendMail($email_from, $fromname, $thisto, $thissubject, $merged_msg, true, $cc, $bcc, $this->filepath);

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
		$updateVal = $this->updateVal('emailtable_update_value', $renderOrder);

		if (!empty($updateVal) && !empty($updated))
		{
			if (!empty($updateField))
			{
				$listModel->updateRows($updated, $updateField, $updateVal);
			}
			else
			{
				$listModel->updateRows($updated, '', '', $updateVal);
			}
		}

		// $$$ hugh - added second update field for Bea
		$updateField = $params->get('emailtable_update_field2');
		$updateField = is_array($updateField) ? JArrayHelper::getValue($updateField, $renderOrder, '') : $updateField;

		$updateVal = $this->updateVal('emailtable_update_value2', $renderOrder);

		if (!empty($updateVal) && !empty($updated))
		{
			if (!empty($updateField))
			{
				$listModel->updateRows($updated, $updateField, $updateVal);
			}
			else
			{
				$listModel->updateRows($updated, '', '', $updateVal);
			}
		}

		// T3 blank tmpl doesn't seem to render messages when tmpl=component
		$app->enqueueMessage(JText::sprintf('%s emails sent', $sent));

		if ($notsent != 0)
		{
			JError::raiseWarning(E_NOTICE, JText::sprintf('%s emails not sent', $notsent));
		}
	}

	public function getEditor()
	{
		$params = $this->getParams();
		$msg = $this->getMessage();

		if ($params->get('wysiwyg', true))
		{
			$editor = JFactory::getEditor();

			return $editor->display('message', $msg, '100%', '200px', 75, 10, true, 'message');
		}
		else
		{
			return '<textarea name="message" style="width:100%" rows="10" cols="10">' . $msg . '</textarea>';
		}
	}
}
