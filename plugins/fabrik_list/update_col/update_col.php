<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.updatecol
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Add an action button to the list to update selected columns to a given value
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.updatecol
 * @since       3.0
 */

class plgFabrik_ListUpdate_col extends plgFabrik_List
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
		return in_array($access, JFactory::getUser()->authorisedLevels());
	}

	/**
	 * Do the plug-in action
	 *
	 * @param   object  $params  plugin parameters
	 * @param   object  &$model  list model
	 * @param   array   $opts    custom options
	 *
	 * @return  bool
	 */

	public function process($params, &$model, $opts = array())
	{
		$db = $model->getDb();
		$user = JFactory::getUser();
		$update = json_decode($params->get('update_col_updates'));
		if (!$update)
		{
			return false;
		}

		// $$$ rob moved here from bottom of func see http://fabrikar.com/forums/showthread.php?t=15920&page=7

		$dateCol = $params->get('update_date_element');
		$userCol = $params->get('update_user_element');

		$item = $model->getTable();

		// Array_unique for left joined table data
		$ids = array_unique(JRequest::getVar('ids', array(), 'method', 'array'));
		JArrayHelper::toInteger($ids);
		$this->_row_count = count($ids);
		$ids = implode(',', $ids);
		$model->reset();
		$model->_pluginQueryWhere[] = $item->db_primary_key . ' IN ( ' . $ids . ')';
		$data = $model->getData();

		// $$$servantek reordered the update process in case the email routine wants to kill the updates
		$emailColID = $params->get('update_email_element', '');
		if (!empty($emailColID))
		{
			$w = new FabrikWorker;
			jimport('joomla.mail.helper');
			$message = $params->get('update_email_msg');
			$subject = $params->get('update_email_subject');
			$eval = $params->get('eval', 0);
			$config = JFactory::getConfig();
			$from = $config->getValue('mailfrom');
			$fromname = $config->getValue('fromname');
			$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($emailColID);
			$emailElement = $elementModel->getElement(true);
			$emailField = $elementModel->getFullName(false, true, false);
			$emailColumn = $elementModel->getFullName(false, false, false);
			$emailFieldRaw = $emailField . '_raw';
			$emailWhich = $emailElement->plugin == 'user' ? 'user' : 'field';
			$tbl = array_shift(explode('.', $emailColumn));
			$db = JFactory::getDBO();
			$aids = explode(',', $ids);

			// If using a user element, build a lookup list of emails from #__users,
			// so we're only doing one query to grab all involved emails.
			if ($emailWhich == 'user')
			{
				$userids_emails = array();
				$query = $db->getQuery();
				$query->select('#__users.id AS id, #__users.email AS email')
				->from('#__users')->join('LEFT', $tbl . ' ON #__users.id = ' . $emailColumn)
				->where(_primary_key . ' IN (' . $ids . ')');
				$db->setQuery($query);
				$results = $db->loadObjectList();
				foreach ($results as $result)
				{
					$userids_emails[(int) $result->id] = $result->email;
				}
			}
			foreach ($aids as $id)
			{
				$row = $model->getRow($id);
				if ($emailWhich == 'user')
				{
					$userid = (int) $row->$emailFieldRaw;
					$to = JArrayHelper::getValue($userids_emails, $userid);
				}
				else
				{
					$to = $row->$emailField;
				}

				if (JMailHelper::cleanAddress($to) && JMailHelper::isEmailAddress($to))
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
		// $$$servantek reordered the update process in case the email routine wants to kill the updates
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
		$cache = JFactory::getCache(JRequest::getCmd('option'));
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
		$ids = JRequest::getVar('ids', array(), 'method', 'array');
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
		$opts = $this->getElementJSOptions($model);
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListUpdateCol($opts)";
		return true;
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
