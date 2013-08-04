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

	protected $buttonPrefix = 'update_col';

	protected $_sent = 0;

	protected $_notsent = 0;

	protected $_row_count = 0;

	protected $msg = null;

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
		return $this->getParams()->get('button_label', parent::buttonLabel());
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
	 * @param   object       $model   List model
	 *
	 * @since   3.0.7
	 *
	 * @return  object|false
	 */

	protected function getUpdateCols($params, $model)
	{
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
	 * @param   object  $params  plugin parameters
	 * @param   object  &$model  List model
	 * @param   array   $opts    custom options
	 *
	 * @return  bool
	 */

	public function process($params, &$model, $opts = array())
	{
		$db = $model->getDb();
		$app = JFactory::getApplication();
		$input = $app->input;
		$user = JFactory::getUser();
		$update = $this->getUpdateCols($params, $model);

		if (!$update)
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
		$this->_row_count = count($ids);
		$ids = implode(',', $ids);
		$model->reset();
		$model->setPluginQueryWhere('update_col', $item->db_primary_key . ' IN ( ' . $ids . ')');
		$data = $model->getData();

		// $$$servantek reordered the update process in case the email routine wants to kill the updates
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
			if (!empty($emailColId))
			{
				$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($emailColID);
				$emailElement = $elementModel->getElement(true);
				$emailField = $elementModel->getFullName(true, false);
				$emailColumn = $elementModel->getFullName(false, false);
				$emailFieldRaw = $emailField . '_raw';
				$emailWhich = $emailElement->plugin == 'user' ? 'user' : 'field';
				$tbl = array_shift(explode('.', $emailColumn));
				$db = JFactory::getDBO();

				// If using a user element, build a lookup list of emails from #__users,
				// so we're only doing one query to grab all involved emails.
				if ($emailWhich == 'user')
				{
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
				}
			}
			else
			{
				$emailWhich = 'to';
			}
			foreach ($aids as $id)
			{
				$row = $model->getRow($id);
				if ($emailWhich == 'user')
				{
					$userid = (int) $row->$emailFieldRaw;
					$to = JArrayHelper::getValue($userids_emails, $userid);
				}
				elseif ($emailWhich == 'field')
				{
					$to = $row->$emailField;
				}
				else
				{
					$to = $emailTo;
				}
				if (JMailHelper::cleanAddress($to) && FabrikWorker::isEmail($to))
				{
					// $tofull = '"' . JMailHelper::cleanLine($toname) . '" <' . $to . '>';
					// $$$servantek added an eval option and rearranged placeholder call
					$thissubject = $w->parseMessageForPlaceholder($subject, $row);
					$thismessage = $w->parseMessageForPlaceholder($message, $row);
					if ($eval)
					{
						$thismessage = @eval($thismessage);
						FabrikWorker::logEval($thismessage, 'Caught exception on eval in updatecol::process() : %s');
					}
					$res = JUtility::sendMail($from, $fromname, $to, $thissubject, $thismessage, true);
					if ($res)
					{
						$this->_sent++;
					}
					else
					{
						$$this->_notsent++;
					}
				}
				else
				{
					$this->_notsent++;
				}
			}
		}
		//$$$servantek reordered the update process in case the email routine wants to kill the updates
		if (!empty($dateCol))
		{
			$date = JFactory::getDate();
			$this->_process($model, $dateCol, $date->toSql());
		}

		if (!empty($userCol))
		{
			$this->_process($model, $userCol, (int) $user->get('id'));
		}
		foreach ($update->coltoupdate as $i => $col)
		{
			$this->_process($model, $col, $update->update_value[$i]);
		}
		$this->msg = $params->get('update_message', '');

		if (empty($this->msg))
		{
			$this->msg = JText::sprintf('PLG_LIST_UPDATE_COL_UPDATE_MESSAGE', $this->_row_count, $this->_sent);
		}
		else
		{
			$this->msg = JText::sprintf($this->msg, $this->_row_count, $this->_sent);
		}

		// Clean the cache.
		$cache = JFactory::getCache($input->get('option'));
		$cache->clean();

		return true;
	}

	/**
	 * Get the message generated in process()
	 *
	 * @param   int  $c  plugin render order
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
	 * @param   object  &$model  list model
	 * @param   string  $col     update column
	 * @param   string  $val     update val
	 *
	 * @return  void
	 */

	private function _process(&$model, $col, $val)
	{
		$app = JFactory::getApplication();
		$ids = $app->input->get('ids', array(), 'array');
		$model->updateRows($ids, $col, $val);
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
		$params = $this->getParams();
		$opts = $this->getElementJSOptions($model);
		$opts->userSelect = (bool) $params->get('update_user_select', 0);
		$opts->form = $this->userSelectForm($model);
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListUpdateCol($opts)";
		return true;
	}

	/**
	 * Build the form which allows the user to select which elements to update
	 *
	 * @param   FabrikFEModelList  $model  List model
	 *
	 * @return  string  HTML Form
	 */

	protected function userSelectForm($model)
	{
		JText::script('PLG_LIST_UPDATE_COL_UPDATE');
		$html = array();
		$fieldNames = array();
		$options[] = '<option value="">' . JText::_('COM_FABRIK_PLEASE_SELECT') . '</option>';
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
				$options[] = '<option value="' . $elName . '" data-id="' . $element->id . '" data-plugin="' . $element->plugin . '">' . strip_tags($element->label) . '</option>';
			}
		}

		$listRef = $model->getRenderContext();
		$prefix = 'fabrik___update_col[list_' . $listRef . '][';
		$elements = '<select class="inputbox key" size="1" name="' . $prefix . 'key][]">' . implode("\n", $options) . '</select>';
		$add = '<a class="add button button-primary" href="#">
		' . FabrikHelperHTML::image('add.png', 'list', $model->getTmpl()) . '</a>';
		$del = '<a class="button delete" href="#">' . FabrikHelperHTML::image('del.png', 'list', $model->getTmpl()) . '</a>';
		$html[] = '<form id="update_col' . $listRef . '">';

		$html[] = '<table class="fabrikList table table-striped" style="width:100%">';
		$html[] = '<thead>';
		$html[] = '<tr><th>' . JText::_('COM_FABRIK_ELEMENT') . '</th><th>' . JText::_('COM_FABRIK_VALUE') . '</th><th>' . $add . '</th><tr>';
		$html[] = '</thead>';

		$html[] = '<tbody>';
		$html[] = '<tr><td>' . $elements . '</td><td class="update_col_value"></th><td>' . $add . $del . '</td></tr>';
		$html[] = '</tbody>';
		$html[] = '</table>';
		$html[] = '<input class="button btn button-primary" value="' . JText::_('COM_FABRIK_APPLY') . '" type="button">';
		$html[] = '</form>';
		return implode("\n", $html);
	}

	/**
	 * Get the name of the colum to update
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
