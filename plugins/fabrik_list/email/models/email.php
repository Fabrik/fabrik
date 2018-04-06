<?php
/**
 * Email list plugin model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Pdf;
use Fabrik\Helpers\StringHelper;
use Joomla\Utilities\ArrayHelper;

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
	 * SMS gateway instance
	 *
	 * @var object
	 */
	private $gateway = null;

	/**
	 * Mails sent
	 *
	 * @var int
	 */
	private $sent = 0;

	/**
	 * Mails not sent
	 *
	 * @var int
	 */
	private $notSent = 0;

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
	 * @param   array &$args Arguments
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
	 * @param   array $args Array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 */
	public function onLoadJavascriptInstance($args)
	{
		$params = $this->getParams();
		$w      = new FabrikWorker;
		FabrikHelperHTML::slimbox();
		parent::onLoadJavascriptInstance($args);
		$opts               = $this->getElementJSOptions();
		$opts->renderOrder  = $this->renderOrder;
		$opts->additionalQS = $w->parseMessageForPlaceHolder($params->get('list_email_additional_qs', ''));

		$url = 'index.php?option=com_fabrik';
		$url .= '&view=list';
		$url .= '&controller=list.email';
		//$url .= '&task=popupwin';
		$url .= '&tmpl=component';
		$url .= '&ajax=1';
		$url .= '&id=' . $this->getModel()->getId();
		$url .= '&renderOrder=' . $this->renderOrder;
		$url .= '&format=partial';

		if (!empty($opts->additionalQS))
		{
			$url .= '&' . $opts->additionalQS;
		}

		$opts->popupUrl = JRoute::_($url, false);
		$opts               = json_encode($opts);
		$this->jsInstance   = "new FbListEmail($opts)";

		return true;
	}

	public function _toType()
	{
		return $this->getParams()->get('emailtable_to_type');
	}

	private function _toHow()
	{
		return $this->getParams()->get('emailtable_to_how', 'single');
	}

	/**
	 * Get to field
	 *
	 * @return string
	 */
	public function getToField()
	{
		$input       = $this->app->input;
		$this->_type = 'table';
		$this->_id   = $input->getInt('id', 0);
		$params      = $this->getParams();
		$toType      = $this->_toType();

		if ($toType == 'field')
		{
			$to = $this->_emailTo();

			switch ($params->get('emailtable_email_to_field_how', 'readonly'))
			{
				case 'editable':
					$input = '<input type="text" name="list_email_to" id="list_email_to" value="' . $to . '" />';
					break;
				case 'hidden':
					$input = '<input name="list_email_to" id="list_email_to" value="' . $to . '" type="hidden" />';
					break;
				case 'readonly':
				default:
					$input = '<input type="text" name="list_email_to" id="list_email_to" value="' . $to . '" readonly="readonly" />';
					break;
			}

			return $input;
		}
		elseif ($toType == 'list')
		{
			return $this->formModel->getElementList('list_email_to');
		}
		elseif ($toType == 'table' || $toType == 'table_picklist')
		{
			$table      = $params->get('emailtable_to_table_table');
			$tableEmail = $params->get('emailtable_to_table_email');
			$tableName  = $params->get('emailtable_to_table_name');

			$toTableModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
			$toTableModel->setId($table);
			$toDb = $toTableModel->getDb();

			$tableName          = FabrikString::safeColName($tableName);
			$tableEmail         = FabrikString::safeColName($tableEmail);
			$emailTableTo_table = $toDb->qn($toTableModel->getTable()->db_table_name);

			$query = $toDb->getQuery(true);
			$query->select($tableEmail . ' AS email, ' . $tableName . ' AS name')
				->from($emailTableTo_table)->order('name ASC');
			$toDb->setQuery($query);
			$results = $toDb->loadObjectList();

			if (empty($results))
			{
				return FText::_('PLG_LIST_EMAIL_TO_TABLE_NO_DATA');
			}

			$empty   = new stdClass;
			$attribs = 'class="fabrikinput inputbox input-medium" multiple="multiple" size="5"';

			if ($toType == 'table_picklist')
			{
				$html = '<div class="pull-left" style="margin:0 20px 20px 0">';
				$html .= JHTML::_('select.genericlist', $results, 'email_to_selectfrom[]', $attribs, 'email', 'name', '', 'email_to_selectfrom');
				$html .= '<br /><a href="#" class="btn btn-small" id="email_add">' . FabrikHelperHTML::icon('icon-plus') . ' ' . FText::_('COM_FABRIK_ADD') . ' &gt;&gt;</a>';
				$html .= '</div>';
				$html .= '<div class="span6">';
				$html .= JHTML::_('select.genericlist', $empty, 'list_email_to[]', $attribs, 'email', 'name', '', 'list_email_to');
				$html .= '<br /><a href="#" class="btn btn-small" id="email_remove">&lt;&lt; '
					. FText::_('COM_FABRIK_DELETE') . ' ' . FabrikHelperHTML::icon('icon-delete') . '</a>';
				$html .= '</div>';
				$html .= '<div style="clear:both"></div>';
			}
			else
			{
				$attribs = 'class="fabrikinput inputbox input-large" multiple="multiple" size="5"';
				$html    = JHTML::_('select.genericlist', $results, 'list_email_to[]', $attribs, 'email', 'name', '', 'list_email_to');
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
		return $this->getParams()->get('emailtable_allow_attachment');
	}

	/**
	 * Get the show to field
	 *
	 * @return  string
	 */
	public function getShowToField()
	{
		$params = $this->getParams();
		$var    = $params->get('emailtable_email_to_field_how', 'readonly');
		$toType = $params->get('emailtable_to_type', 'list');

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
		return $this->getParams()->get('emailtable_hide_subject', '0') === '0';
	}

	/**
	 * Get the subject line
	 *
	 * @return  string
	 */
	public function getSubject()
	{
		return $this->getParams()->get('email_subject');
	}

	/**
	 * Get the email message
	 *
	 * @return  string
	 */
	public function getMessage()
	{
		return $this->getParams()->get('email_message');
	}

	/**
	 * Get the selected records
	 *
	 * @param   string $key     key
	 * @param   bool   $allData data
	 *
	 * @return    array    rows
	 */
	public function getRecords($key = 'ids', $allData = false)
	{
		$params = $this->getParams();
		$model  = $this->listModel;
		$input  = $this->app->input;

		$pk  = $model->getPrimaryKey();
		$pk2 = FabrikString::safeColNameToArrayKey($pk) . '_raw';

		/**
		 * If the 'checkall' param is set, and the checkAll checkbox was used, ignore pagination and selected
		 * ids, and just select all rows, subject to filtering.
		 *
		 * If not doing 'checkall', use the selected ids as usual.
		 */
		if ($input->get('checkAll', '0') == '1' && $params->get('checkall', '0') == '1')
		{
			$whereClause = '';
		}
		else
		{
			if ($key === 'recordids')
			{
				$ids = explode(',', $input->get($key, '', 'string'));
			}
			else
			{
				$ids = (array) $input->get($key, array(), 'array');
			}

			$ids = ArrayHelper::toInteger($ids);

			if (empty($ids))
			{
				throw new RuntimeException(FText::_('PLG_LIST_EMAIL_ERR_NO_RECORDS_SELECTED'), 400);
			}

			$whereClause = '(' . $pk . ' IN (' . implode(',', $ids) . '))';
		}

		$cond = $params->get('emailtable_condition');

		if (trim($cond) !== '')
		{
			if (!empty($whereClause))
			{
				$whereClause .= ' AND ';
			}
			$whereClause .= '(' . $cond . ')';
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
		$input = $this->app->input;
		JClientHelper::setCredentialsFromRequest('ftp');
		$files          = $input->files->get('attachment', array());
		$folder         = JPATH_ROOT . '/images/stories';
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
	 * @param   string $name Parameter name
	 *
	 * @return  string
	 */
	protected function updateVal($name)
	{
		$params    = $this->getParams();
		$input     = $this->app->input;
		$updateVal = $params->get($name);
		$search    = array();
		$replace   = array();

		if ($updateVal === 'now()' || $updateVal === '{now}')
		{
			$search[]  = 'now()';
			$replace[] = $this->date->toSql();
			$search[]  = '{now}';
			$replace[] = $this->date->toSql();
		}

		if ($updateVal === '{subject}')
		{
			$search[]  = '{subject}';
			$replace[] = $input->get('subject', '', 'string');
		}

		if ($updateVal === '{$my->id}')
		{
			$search[]  = '{$my->id}';
			$replace[] = $this->user->get('id', 0, 'int');
		}

		if ($updateVal === '{sent}')
		{
			$search[]  = '{sent}';
			$replace[] = $this->sent;
		}

		if ($updateVal === '{notsent}')
		{
			$search[]  = '{notsent}';
			$replace[] = $this->notSent;
		}

		return str_replace($search, $replace, $updateVal);
	}

	/**
	 * Do email
	 *
	 * @return boolean
	 */
	public function doEmail()
	{
		$params    = $this->getParams();
		$listModel = $this->listModel;
		$input     = $this->app->input;
		jimport('joomla.mail.helper');

		if (!$this->_upload())
		{
			return false;
		}


		$listModel->setId($input->getInt('id', 0));
		$w           = new FabrikWorker;
		$this->_type = 'table';
		$mergeEmails = $params->get('emailtable_mergemessages', 0);
		$sendSMS     = $params->get('emailtable_email_or_sms', 'email') == 'sms';
		$toHow       = $this->_toHow();
		$toType      = $this->_toType();
		$to          = $this->_to();
		$data        = $this->getRecords('recordids', true);
		$cc          = null;
		$bcc         = null;
		$sent        = 0;
		$notSent     = 0;
		$updated     = array();
		$mergedMsg   = '';
		$firstRow    = array();

		foreach ($data as $group)
		{
			foreach ($group as $row)
			{
				if ($mergeEmails)
				{
					if (empty($firstRow))
					{
						// Used for placeholders in subject when merging mail
						$firstRow = $row;
					}

					$thisMsg = $this->_thisMsg($row);

					$mergedMsg .= $thisMsg;
					$updated[] = $row->__pk_val;
				}
				else
				{
					if ($toType == 'list')
					{
						$process = isset($row->$to);
						$mailTo  = $row->$to;
					}
					else if ($toType == 'eval')
					{
						$process = true;
						$mailTo = $this->_evalTo($row);
					}
					else
					{
						$process = true;
						$mailTo  = $w->parseMessageForPlaceHolder($to, $row);
					}

					if ($process)
					{
						$res     = false;
						$mailTos = explode(',', $mailTo);

						if ($toHow == 'single')
						{
							list($mailTos, $notSent) = $this->_parseMailTos($mailTos, $row, $notSent);
							$sent = count($mailTos);

							if ($sent > 0)
							{
								$res = $this->_send($row, $mailTos);
							}
						}
						else
						{
							foreach ($mailTos as $mailTo)
							{
								$mailTo = trim($w->parseMessageForPlaceholder($mailTo, $row));

								if (FabrikWorker::isEmail($mailTo, $sendSMS))
								{
									$res = $this->_send($row, $mailTo);
									$res ? $sent++ : $notSent++;
								}
								else
								{
									$notSent++;
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
						$notSent++;
					}
				}
			}
		}

		if ($mergeEmails)
		{
			list($sent, $notSent) = $this->mailMerged($firstRow, $mergedMsg, $sent, $notSent);
		}

		$this->sent = $sent;
		$this->notSent = $notSent;
		$this->_updateRows($updated);

		// T3 blank tmpl doesn't seem to render messages when tmpl=component
		$this->app->enqueueMessage(JText::sprintf('%s emails sent', $sent));

		if ($notSent != 0)
		{
			$this->app->enqueueMessage(JText::sprintf('%s emails not sent', $notSent), 'notice');
		}

		return true;
	}

	/**
	 * @param $mailTos
	 * @param $row
	 * @param $notSent
	 *
	 * @return array
	 */
	private function _parseMailTos($mailTos, $row, $notSent)
	{
		$params  = $this->getParams();
		$sendSMS = $params->get('emailtable_email_or_sms', 'email') == 'sms';
		$w       = new FabrikWorker;

		foreach ($mailTos as $toKey => $thisTo)
		{
			$thisTo = $w->parseMessageForPlaceholder($thisTo, $row);

			if (!FabrikWorker::isEmail($thisTo, $sendSMS))
			{
				unset($mailTos[$toKey]);
				$notSent++;
			}
			else
			{
				$mailTos[$toKey] = $thisTo;
			}
		}

		if ($notSent > 0)
		{
			$mailTos = array_values($mailTos);
		}

		return array($mailTos, $notSent);
	}

	/**
	 * Are we using the old message style
	 *
	 * @return bool
	 */
	private function _oldStyle()
	{
		list($phpMsg, $message) = $this->_message();
		$oldStyle = false;

		if (empty($message) && !$phpMsg)
		{
			$oldStyle = true;
		}

		return $oldStyle;
	}

	/**
	 * Send the email
	 *
	 * @param $row
	 * @param $mailTo
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function _send($row, $mailTo)
	{
		$params        = $this->getParams();
		$sendSMS       = $params->get('emailtable_email_or_sms', 'email') == 'sms';
		$input         = $this->app->input;
		$coverMessage  = $input->get('message', '', 'raw');
		$coverMessage  = FabrikString::safeNl2br($coverMessage);
		$oldStyle      = $this->_oldStyle();
		$emailTemplate = $this->_emailTemplate();
		$w             = new FabrikWorker;
		$thisMsg       = $coverMessage;
		list($phpMsg, $message) = $this->_message();

		if (!$oldStyle)
		{
			if ($phpMsg)
			{
				$thisMsg .= FabrikHelperHTML::getPHPTemplate($emailTemplate, $row, $this->listModel);
			}
			else
			{
				$thisMsg .= $message;
			}
		}

		$thisMsg = $w->parseMessageForPlaceholder($thisMsg, $row);

		if (!$sendSMS && $params->get('wysiwyg', true))
		{
			Pdf::fullPaths($thisMsg);
		}

		if ($sendSMS)
		{
			return $this->sendSMS($mailTo, $thisMsg, $row);
		}
		else
		{
			$subject = $input->get('subject', '', 'string');
			$cc      = null;
			$bcc     = null;
			list($emailFrom, $fromName) = $this->_fromEmailName($row);
			list($replyEmail, $replyEmailName) = $this->_replyEmailName($row);
			$thisSubject = $w->parseMessageForPlaceholder($subject, $row);

			return FabrikWorker::sendMail($emailFrom, $fromName, $mailTo, $thisSubject, $thisMsg, 1, $cc, $bcc, $this->filepath,
				$replyEmail, $replyEmailName);
		}
	}

	/**
	 * Get a csv list of emails to send the email to.
	 *
	 * @return array|mixed|string
	 * @throws Exception
	 */
	private function _to()
	{
		$input  = $this->app->input;
		$toType = $this->_toType();

		if ($toType == 'table' || $toType == 'table_picklist')
		{
			$to = $input->get('list_email_to', array(), 'array');
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
					$emailTableTo = $this->_emailTo();

					if (!empty($emailTableTo))
					{
						if (!in_array($emailTableTo, $to))
						{
							$to[] = $emailTableTo;
						}
					}
				}

				$to = implode(',', $to);
			}
		}

		return $to;
	}

	/**
	 * Eval to address
	 *
	 * param  object  row  row data
	 * @return string
	 * @throws Exception
	 */
	private function _evalTo($row)
	{
		$toType = $this->_toType();
		$to = '';

		if ($toType == 'eval')
		{
			$params = $this->getParams();
			$php = $params->get('emailtable_to_eval');
			$to = FabrikHelperHTML::isDebug() ? eval($php) : @eval($php);
			FabrikWorker::logEval($to, 'Eval exception : listEmail::_evalTo() : %s');
		}

		return $to;
	}


	/**
	 * Get the email address(es) specified in the admin's 'Email to
	 * field
	 *
	 * @return string
	 */
	public function _emailTo()
	{
		$params  = $this->getParams();
		$emailTo = $params->get('emailtable_to', '');

		return $emailTo;
	}

	/**
	 * Get address book
	 * @return array
	 */
	public function addressBook()
	{
		$params = $this->getParams();
		$table      = $params->get('emailtable_to_table_table');

		if (empty($table))
		{
			return array();
		}

		$tableEmail = $params->get('emailtable_to_table_email');
		$tableName  = $params->get('emailtable_to_table_name');
		$tableWhere = $params->get('emailtable_to_table_where', '');

		$toTableModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$toTableModel->setId($table);
		$toDb = $toTableModel->getDb();

		$tableName          = FabrikString::safeColName($tableName);
		$tableEmail         = FabrikString::safeColName($tableEmail);
		$emailTableTo_table = $toDb->qn($toTableModel->getTable()->db_table_name);

		$query = $toDb->getQuery(true);
		$query->select($tableEmail . ' AS email, ' . $tableName . ' AS name')
			->from($emailTableTo_table)->order('name ASC');

		if (!empty($tableWhere)) {
			$w = new FabrikWorker;
			$tableWhere = $w->parseMessageForPlaceHolder($tableWhere);
			$query->where($tableWhere);
		}

		$toDb->setQuery($query);
		$results = $toDb->loadObjectList();

		return $results;
	}

	/**
	 * Get the message from the designated template files.
	 *
	 * @return array ($phpMsg (bool), $message (string))
	 */
	private function _message()
	{
		$phpMsg          = false;
		$params          = $this->getParams();
		$contentTemplate = $params->get('emailtable_template_content', '');
		$content         = empty($contentTemplate) ? '' : FabrikHelperHTML::getContentTemplate($contentTemplate);
		$emailTemplate   = $this->_emailTemplate();

		if (JFile::exists($emailTemplate))
		{
			if (JFile::getExt($emailTemplate) == 'php')
			{
				$message = '';
				$phpMsg  = true;
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

		return array($phpMsg, $message);
	}

	/**
	 * Get the email template
	 *
	 * @return mixed|string
	 */
	private function _emailTemplate()
	{
		$params        = $this->getParams();
		$emailTemplate = $params->get('emailtable_template', '');

		if ($emailTemplate != "-1" && !empty($emailTemplate))
		{
			$emailTemplate = JPath::clean(JPATH_SITE . '/plugins/fabrik_list/email/tmpl/' . $emailTemplate);
		}

		return $emailTemplate;
	}

	/**
	 * Build the message text
	 *
	 * @param $row
	 *
	 * @return mixed|string
	 * @throws Exception
	 */
	private function _thisMsg($row)
	{
		list($phpMsg, $message) = $this->_message();
		$w             = new FabrikWorker;
		$input         = $this->app->input;
		$coverMessage  = FabrikString::safeNl2br($input->get('message', '', 'html'));
		$emailTemplate = $this->_emailTemplate();
		$oldStyle      = $this->_oldStyle();

		if ($oldStyle)
		{
			$thisMsg = $w->parseMessageForPlaceHolder($coverMessage, $row);
		}
		else
		{
			if ($phpMsg)
			{
				$thisMsg = FabrikHelperHTML::getPHPTemplate($emailTemplate, $row, $this->listModel);
			}
			else
			{
				$thisMsg = $w->parseMessageForPlaceHolder($message, $row);
			}
		}

		return $thisMsg;
	}

	/**
	 * Sent mail merge
	 *
	 * @param $firstRow
	 * @param $mergedMsg
	 * @param $sent
	 * @param $notSent
	 *
	 * @return array
	 * @throws Exception
	 */
	private function mailMerged($firstRow, $mergedMsg, $sent, $notSent)
	{
		$params = $this->getParams();
		$input  = $this->app->input;
		list($replyEmail, $replyEmailName) = $this->_replyEmailName($firstRow);
		list($emailFrom, $fromName) = $this->_fromEmailName($firstRow);
		$w            = new FabrikWorker;
		$toType       = $this->_toType();
		$to           = $this->_to();
		$oldStyle     = $this->_oldStyle();
		$toHow        = $this->_toHow();
		$mailTo       = $toType == 'list' ? $firstRow->$to : $to;
		$subject      = $input->get('subject', '', 'string');
		$thisTos      = explode(',', $w->parseMessageForPlaceHolder($mailTo, $firstRow));
		$thisSubject  = $w->parseMessageForPlaceHolder($subject, $firstRow);
		$preamble     = $params->get('emailtable_message_preamble', '');
		$postamble    = $params->get('emailtable_message_postamble', '');
		$mergedMsg    = $preamble . $mergedMsg . $postamble;
		$coverMessage = FabrikString::safeNl2br($input->get('message', '', 'html'));
		$cc           = null;
		$bcc          = null;

		if (!$oldStyle)
		{
			$mergedMsg = $coverMessage . $mergedMsg;
		}

		if ($params->get('wysiwyg', true))
		{
			Pdf::fullPaths($coverMessage);
		}

		if ($toHow == 'single')
		{
			foreach ($thisTos as $toKey => $thisTo)
			{
				$thisTo = $w->parseMessageForPlaceholder($thisTo, $firstRow);

				if (!FabrikWorker::isEmail($thisTo))
				{
					unset($thisTos[$toKey]);
					$notSent++;
				}
				else
				{
					$mailTos[$toKey] = $thisTo;
				}
			}

			if ($notSent > 0)
			{
				$thisTos = array_values($thisTos);
			}

			$sent = count($thisTos);

			if ($sent > 0)
			{
				$res = FabrikWorker::sendMail($emailFrom, $fromName, $thisTos, $thisSubject, $mergedMsg, true, $cc, $bcc, $this->filepath,
					$replyEmail, $replyEmailName);
			}
		}
		else
		{
			foreach ($thisTos as $thisTo)
			{
				if (FabrikWorker::isEmail($thisTo))
				{
					$res = FabrikWorker::sendMail($emailFrom, $fromName, $thisTo, $thisSubject, $mergedMsg, true, $cc, $bcc, $this->filepath,
						$replyEmail, $replyEmailName);

					if ($res)
					{
						$sent++;
					}
					else
					{
						$notSent++;
					}
				}
				else
				{
					$notSent++;
				}
			}
		}

		return array($sent, $notSent);
	}

	/**
	 * Update a single row
	 *
	 * @param $updated
	 * @param $updateField
	 * @param $updateVal
	 */
	private function _updateSingleRow($updated, $updateField, $updateVal)
	{
		if (!empty($updateVal) && !empty($updated))
		{
			if (!empty($updateField))
			{
				$this->listModel->updateRows($updated, $updateField, $updateVal);
			}
			else
			{
				$this->listModel->updateRows($updated, '', '', $updateVal);
			}
		}
	}

	/**
	 * Update rows
	 *
	 * @param $updated
	 */
	private function _updateRows($updated)
	{
		$params      = $this->getParams();
		$updateField = $params->get('emailtable_update_field');
		$updateVal   = $this->updateVal('emailtable_update_value');

		$this->_updateSingleRow($updated, $updateField, $updateVal);

		// $$$ hugh - added second update field for Bea
		$updateField = $params->get('emailtable_update_field2');
		$updateVal   = $this->updateVal('emailtable_update_value2');

		$this->_updateSingleRow($updated, $updateField, $updateVal);
	}

	/**
	 * Get the reply email and name
	 *
	 * @param   array $data Data to use for placeholder replacement
	 *
	 * @since 3.3.2
	 *
	 * @return array ($replyEmail, $replyEmailName)
	 */
	private function _replyEmailName($data = array())
	{
		$w      = new FabrikWorker;
		$params = $this->getParams();
		$reply  = $w->parseMessageForPlaceholder($params->get('email_reply_to'), $data, false);
		@list($replyEmail, $replyEmailName) = explode(':', $reply, 2);

		if (empty($replyEmail))
		{
			$replyEmail = null;
		}

		if (empty($replyEmailName))
		{
			$replyEmailName = null;
		}

		return array($replyEmail, $replyEmailName);
	}

	/**
	 * Get the email to name and email address
	 *
	 * @param   array $data Placeholder replacement data
	 *
	 * @since 3.3.2
	 *
	 * @return array ($emailFrom, $fromName)
	 */
	private function _fromEmailName($data = array())
	{
		$w        = new FabrikWorker;
		$params   = $this->getParams();
		$fromUser = $params->get('emailtable_from_user');

		if ($fromUser)
		{
			$emailFrom = $this->user->get('email');
			$fromName  = $this->user->get('name');
		}
		else
		{
			$emailFrom = $params->get('email_from', $this->config->get('mailfrom'));
			@list($emailFrom, $fromName) = explode(':', $w->parseMessageForPlaceholder($emailFrom, $data, false), 2);
		}

		return array($emailFrom, $fromName);
	}

	/**
	 * Build the WYSIWYG editor
	 *
	 * @return string
	 */
	public function getEditor()
	{
		$params = $this->getParams();
		$msg    = $this->getMessage();

		if ($params->get('wysiwyg', true))
		{
			$editor = \JEditor::getInstance($this->config->get('editor'));

			return $editor->display('message', $msg, '100%', '200px', 75, 10, true, 'message');
		}
		else
		{
			return '<textarea name="message" style="width:100%" rows="10" cols="10">' . $msg . '</textarea>';
		}
	}

	/**
	 * Send SMS
	 *
	 * @return    bool
	 */

	protected function sendSMS($to, $message, $data)
	{
		$params               = $this->getParams();
		$w                    = new FabrikWorker;
		$opts                 = array();
		$userName             = $params->get('emailtable_sms_username');
		$password             = $params->get('emailtable_sms_password');
		$from                 = $params->get('emailtable_sms_from');
		$opts['sms-username'] = $w->parseMessageForPlaceHolder($userName, $data);
		$opts['sms-password'] = $w->parseMessageForPlaceHolder($password, $data);
		$opts['sms-from']     = $w->parseMessageForPlaceHolder($from, $data);
		$opts['sms-to']       = $w->parseMessageForPlaceHolder($to, $data);
		$gateway              = $this->getSMSInstance();

		return $gateway->process($message, $opts);
	}

	/**
	 * Get specific SMS gateway instance
	 *
	 * @return  object  gateway
	 */

	private function getSMSInstance()
	{
		if (!isset($this->gateway))
		{
			$params  = $this->getParams();
			$gateway = $params->get('emailtable_sms_gateway', 'kapow.php');
			$input   = new JFilterInput;
			$gateway = $input->clean($gateway, 'CMD');
            require_once JPATH_ROOT . '/libraries/fabrik/fabrik/Helpers/sms_gateways/' . StringHelper::strtolower($gateway);
			$gateway               = JFile::stripExt($gateway);
			$this->gateway         = new $gateway;
			$this->gateway->params = $params;
		}

		return $this->gateway;
	}

	/**
	 * Load the AMD module class name
	 *
	 * @return string
	 */
	public function loadJavascriptClassName_result()
	{
		return 'FbListEmail';
	}

}
