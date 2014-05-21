<?php
/**
 * Add an action button to the list to update selected columns to a given value
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.updatecol
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Add an action button to the list to update selected columns to a given value
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.updatecol
 * @since       3.0
 */

class PlgFabrik_ListUpdate_Col extends PlgFabrik_List
{
	/**
	 * Button prefix
	 *
	 * @var string
	 */
	protected $buttonPrefix = 'update_col';

	/**
	 * Number of send email notifications
	 *
	 * @var int
	 */
	protected $sent = 0;

	/**
	 * Number of NOT send email notifications
	 *
	 * @var int
	 */
	protected $notsent = 0;

	/**
	 * Number rows updated
	 *
	 * @var int
	 */
	protected $row_count = 0;

	/**
	 * Update message
	 *
	 * @var string
	 */
	protected $msg = null;

	/**
	 * Element containing email notification addresses
	 *
	 * @var  PlgFabrik_Element
	 */
	protected $emailElement = null;

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
		return FText::_($this->getParams()->get('button_label', parent::buttonLabel()));
	}

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 */

	protected function getAclParam()
	{
		return 'updatecol_access';
	}

	/**
	 * Can the plug-in select list rows
	 *
	 * @return  bool
	 */

	public function canSelectRows()
	{
		$access = $this->getParams()->get('updatecol_access');
		$name = $this->_getButtonName();

		return in_array($access, JFactory::getUser()->getAuthorisedViewLevels());
	}

	/**
	 * Get the values to update the list with.
	 * If user select the get them from the app's input else take from plug-in parameters
	 *
	 * @param   JParameters  $params  Plugin parameters
	 *
	 * @since   3.0.7
	 *
	 * @return  object|false
	 */

	protected function getUpdateCols($params)
	{
		$model = $this->getModel();

		if ($params->get('update_user_select', 0))
		{
			$formModel = $model->getFormModel();
			$app = JFactory::getApplication();
			$qs = $app->input->get('fabrik_update_col', '', 'string');
			parse_str($qs, $output);
			$key = 'list_' . $model->getRenderContext();

			$values = JArrayHelper::getValue($output, 'fabrik___filter', array());
			$values = JArrayHelper::getValue($values, $key, array());

			$update = new stdClass;
			$update->coltoupdate = array();
			$update->update_value = array();

			for ($i = 0; $i < count($values['elementid']); $i ++)
			{
				$id = $values['elementid'][$i];
				$elementModel = $formModel->getElement($id, true);
				$update->coltoupdate[] = $elementModel->getFullName(false, false);
				$update->update_value[] = $values['value'][$i];
			}

			// If no update input found return false to stop processing
			if (empty($update->coltoupdate) && empty($update->update_value))
			{
				return false;
			}
		}
		else
		{
			$update = json_decode($params->get('update_col_updates'));
		}

		return $update;
	}

	/**
	 * Do the plug-in action
	 *
	 * @param   array  $opts  Custom options
	 *
	 * @return  bool
	 */

	public function process($opts = array())
	{
		$params = $this->getParams();
		$model = $this->getModel();
		$db = $model->getDb();
		$app = JFactory::getApplication();
		$input = $app->input;
		$user = JFactory::getUser();
		$update = $this->getUpdateCols($params);
		$postEval = $params->get('update_post_eval', '');
		
		if (!$update && empty($postEval))
		{
			return false;
		}

		// $$$ rob moved here from bottom of func see http://fabrikar.com/forums/showthread.php?t=15920&page=7
		$dateCol = $params->get('update_date_element');
		$userCol = $params->get('update_user_element');

		$item = $model->getTable();

		// Array_unique for left joined table data
		$ids = array_unique($input->get('ids', array(), 'array'));
		JArrayHelper::toInteger($ids);
		$this->row_count = count($ids);
		$ids = implode(',', $ids);
		$model->reset();
		$model->setPluginQueryWhere('update_col', $item->db_primary_key . ' IN ( ' . $ids . ')');
		$data = $model->getData();
		
		// Needed to re-assign as getDate() messes the plugin params order
		$this->params = $params;

		if (!empty($dateCol))
		{
			$date = JFactory::getDate();
			$this->_process($model, $dateCol, $date->toSql(), false);
		}

		if (!empty($userCol))
		{
			$this->_process($model, $userCol, (int) $user->get('id'), false);
		}

		if (!empty($update))
		{
			foreach ($update->coltoupdate as $i => $col)
			{
				$this->_process($model, $col, $update->update_value[$i], $update->update_eval[$i]);
			}
		}

		$this->sendEmails($ids);

		$this->msg = $params->get('update_message', '');

		if (empty($this->msg))
		{
			$this->msg = JText::sprintf('PLG_LIST_UPDATE_COL_UPDATE_MESSAGE', $this->row_count, $this->sent);
		}
		else
		{
			$this->msg = JText::sprintf($this->msg, $this->row_count, $this->sent);
		}

		if (!empty($postEval))
		{
			$err = @eval($postEval);
			FabrikWorker::logEval($err, 'Caught exception on eval in updatecol::process() : %s');
		}
		
		// Clean the cache.
		$cache = JFactory::getCache($input->get('option'));
		$cache->clean();

		return true;
	}

	/**
	 * Send notification emails
	 *
	 * @param   string  $ids  csv list of row ids.
	 *
	 * @return  void
	 */
	protected function sendEmails($ids)
	{
		$params = $this->getParams();
		$model = $this->getModel();

		// Ensure that yesno exports text and not bootstrap icon.
		$model->setOutputFormat('csv');
		$emailColID = $params->get('update_email_element', '');
		$emailTo = $params->get('update_email_to', '');

		if (!empty($emailColID) || !empty($emailTo))
		{
			$w = new FabrikWorker;
			jimport('joomla.mail.helper');
			$aids = explode(',', $ids);
			$message = $params->get('update_email_msg');
			$subject = $params->get('update_email_subject');
			$eval = $params->get('eval', 0);
			$config = JFactory::getConfig();
			$from = $config->get('mailfrom');
			$fromname = $config->get('fromname');

			$emailWhich = $this->emailWhich();

			foreach ($aids as $id)
			{
				$row = $model->getRow($id, true);
				$to = $this->emailTo($row, $emailWhich);

				if (JMailHelper::cleanAddress($to) && FabrikWorker::isEmail($to))
				{
					$thissubject = $w->parseMessageForPlaceholder($subject, $row);
					$thismessage = $w->parseMessageForPlaceholder($message, $row);

					if ($eval)
					{
						$thismessage = @eval($thismessage);
						FabrikWorker::logEval($thismessage, 'Caught exception on eval in updatecol::process() : %s');
					}

					$mail = JFactory::getMailer();
					$res = $mail->sendMail($from, $fromname, $to, $thissubject, $thismessage, true);

					if ($res)
					{
						$this->sent++;
					}
					else
					{
						$this->notsent++;
					}
				}
				else
				{
					$this->notsent++;
				}
			}
		}
	}

	/**
	 * Get the email selection mode
	 *
	 * @return string
	 */
	private function emailWhich()
	{
		$params = $this->getParams();
		$emailColID = $params->get('update_email_element', '');

		if (!empty($emailColID))
		{
			$elementModel = $this->getEmailElement();
			$emailElement = $elementModel->getElement(true);
			$emailWhich = $emailElement->plugin == 'user' ? 'user' : 'field';
		}
		else
		{
			$emailWhich = 'to';
		}

		return $emailWhich;
	}

	/**
	 * Get list of user emails.
	 *
	 * @param   string  $ids  CSV list of ids
	 *
	 * @return  array
	 */
	private function getEmailUserIds($ids)
	{
		$elementModel = $this->getEmailElement();
		$emailColumn = $elementModel->getFullName(false, false);
		$tbl = array_shift(explode('.', $emailColumn));
		$db = JFactory::getDbo();
		$userids_emails = array();
		$query = $db->getQuery();
		$query->select('#__users.id AS id, #__users.email AS email')
		->from('#__users')->join('LEFT', $tbl . ' ON #__users.id = ' . $emailColumn)
		->where($item->db_primary_key . ' IN (' . $ids . ')');
		$db->setQuery($query);
		$results = $db->loadObjectList();

		foreach ($results as $result)
		{
			$userids_emails[(int) $result->id] = $result->email;
		}

		return $userids_emails;
	}

	/**
	 * Get Email Element
	 *
	 * @return PlgFabrik_Element
	 */
	private function getEmailElement()
	{
		if (isset($this->emailElement))
		{
			return $this->emailElement;
		}

		$params = $this->getParams();
		$emailColID = $params->get('update_email_element', '');

		return FabrikWorker::getPluginManager()->getElementPlugin($emailColID);
	}

	/**
	 * Get email address to send update notification to
	 *
	 * @param   object  $row         Current record row
	 * @param   string  $emailWhich  Mode for getting the user's email
	 *
	 * @return  string  Email address
	 */
	private function emailTo($row, $emailWhich)
	{
		$input = JFactory::getApplication()->input;
		$params = $this->getParams();
		$elementModel = $this->getEmailElement();
		$emailField = $elementModel->getFullName(true, false);

		if ($emailWhich == 'user')
		{
			$emailFieldRaw = $emailField . '_raw';
			$userid = (int) $row->$emailFieldRaw;
			$ids = array_unique($input->get('ids', array(), 'array'));
			JArrayHelper::toInteger($ids);
			$ids = implode(',', $ids);
			$userids_emails = $this->getEmailUserIds($ids);
			$to = JArrayHelper::getValue($userids_emails, $userid);
		}
		elseif ($emailWhich == 'field')
		{
			$to = $row->$emailField;
		}
		else
		{
			$to = $params->get('update_email_to', '');
		}

		return $to;
	}

	/**
	 * Get the message generated in process()
	 *
	 * @param   int  $c  Plugin render order
	 *
	 * @return  string
	 */

	public function process_result($c)
	{
		return $this->msg;
	}

	/**
	 * Process the update column
	 *
	 * @param   object  &$model  List model
	 * @param   string  $col     Update column
	 * @param   string  $val     Update val
	 *
	 * @return  void
	 */

	private function _process(&$model, $col, $val, $eval = false)
	{
		$app = JFactory::getApplication();
		$ids = $app->input->get('ids', array(), 'array');
		
		if ($eval)
		{
			$val = @eval($val);
			FabrikWorker::logEval($val, 'Caught exception on eval in updatecol::_process() : %s');
		}
		
		$model->updateRows($ids, $col, $val);
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
		$params = $this->getParams();
		$opts = $this->getElementJSOptions();
		$opts->userSelect = (bool) $params->get('update_user_select', 0);
		$opts->form = $this->userSelectForm();
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListUpdateCol($opts)";

		return true;
	}

	/**
	 * Build the form which allows the user to select which elements to update
	 *
	 * @return  string  HTML Form
	 */

	protected function userSelectForm()
	{
		$model = $this->getModel();
		JText::script('PLG_LIST_UPDATE_COL_UPDATE');
		$html = array();
		$fieldNames = array();
		$options[] = '<option value="">' . FText::_('COM_FABRIK_PLEASE_SELECT') . '</option>';
		$form = $model->getFormModel();
		$groups = $form->getGroupsHiarachy();
		$gkeys = array_keys($groups);
		$elementModels = $model->getElements(0, false, true);

		foreach ($elementModels as $elementModel)
		{
			$element = $elementModel->getElement();

			if ($elementModel->canUse($this, 'list') && $element->plugin !== 'internalid')
			{
				$elName = $elementModel->getFilterFullName();
				$options[] = '<option value="' . $elName . '" data-id="' . $element->id . '" data-plugin="' . $element->plugin . '">'
					. strip_tags($element->label) . '</option>';
			}
		}

		$listRef = $model->getRenderContext();
		$prefix = 'fabrik___update_col[list_' . $listRef . '][';
		$elements = '<select class="inputbox key" size="1" name="' . $prefix . 'key][]">' . implode("\n", $options) . '</select>';
		$j3 = FabrikWorker::j3();
		$addImg = $j3 ? 'plus.png' : 'add.png';
		$removeImg = $j3 ? 'remove.png' : 'del.png';
		$add = '<a class="btn add button btn-primary" href="#">
			' . FabrikHelperHTML::image($addImg, 'list', $model->getTmpl()) . '</a>';
		$del = '<a class="btn button delete" href="#">' . FabrikHelperHTML::image($removeImg, 'list', $model->getTmpl()) . '</a>';
		$html[] = '<form id="update_col' . $listRef . '">';

		$class = $j3 ? 'table table-striped' : 'fabrikList';
		$html[] = '<table class="' . $class . '" style="width:100%">';
		$html[] = '<thead>';
		$html[] = '<tr><th>' . FText::_('COM_FABRIK_ELEMENT') . '</th><th>' . FText::_('COM_FABRIK_VALUE') . '</th><th>' . $add . '</th><tr>';
		$html[] = '</thead>';

		$html[] = '<tbody>';
		$html[] = '<tr><td>' . $elements . '</td><td class="update_col_value"></th><td><div class="btn-group">' . $add . $del . '</div></td></tr>';
		$html[] = '</tbody>';
		$html[] = '</table>';
		$html[] = '<input class="button btn button-primary" value="' . FText::_('COM_FABRIK_APPLY') . '" type="button">';
		$html[] = '</form>';

		return implode("\n", $html);
	}

	/**
	 * Get the name of the column to update
	 *
	 * @return string
	 */

	protected function _getColName()
	{
		$params = $this->getParams();
		$col = $params->get('coltoupdate');

		return $col . '-' . $this->renderOrder;
	}
}
