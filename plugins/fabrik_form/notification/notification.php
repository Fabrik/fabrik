<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.notification
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Allows users to subscribe to updates to a given row and receive emails
 * of those updates. Used in conjunction with the cron notification plug-in
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.notification
 * @since       3.0
 */

class PlgFabrik_FormNotification extends PlgFabrik_Form
{

	/**
	 * Inject custom html into the bottom of the form
	 *
	 * @param   int  $c  plugin counter
	 *
	 * @return  string  html
	 */

	public function getBottomContent_result($c)
	{
		return $this->html;
	}

	/**
	 * Sets up HTML to be injected into the form's bottom
	 *
	 * @param   object  $params     Params
	 * @param   object  $formModel  Form model
	 *
	 * @return void
	 */

	public function getBottomContent($params, $formModel)
	{
		$user = JFactory::getUser();
		$app = JFactory::getApplication();
		$input = $app->input;
		if ($user->get('id') == 0)
		{
			$this->html = JText::_('PLG_CRON_NOTIFICATION_SIGN_IN_TO_RECEIVE_NOTIFICATIONS');
			return;
		}
		if ($params->get('send_mode') == '1')
		{
			return;
		}
		$opts = new stdClass;
		$opts->listid = $formModel->getListModel()->getId();
		$opts->formid = $formModel->getId();
		$opts->rowid = $formModel->getRowId();
		$opts->senderBlock = $input->get('view') == 'form' ? 'form_' : 'details_';
		$opts->senderBlock .= $formModel->getId();
		$opts = json_encode($opts);
		$id = uniqid('fabrik_notification');
		if ($params->get('notification_ajax', 0) == 1)
		{
			FabrikHelperHTML::script('components/com_fabrik/plugins/form/notification/notify.js');
			$script = "window.addEvent('fabrik.loaded', function() {
				var notify = new Notify('$id', $opts);
 			});";

			FabrikHelperHTML::addScriptDeclaration($script);
		}
		// See if the checkbox should be checked
		$db = FabrikWorker::getDbo();
		$ref = $this->getRef($formModel->getListModel()->getId());
		$query = $db->getQuery(true);
		$query->select('COUNT(id)')->from('#__{package}_notification')->where('user_id = ' . (int) $user->get('id') . ' AND reference = ' . $ref);
		$db->setQuery($query);
		$found = $db->loadResult();
		$checked = $found ? 'checked="checked"' : '';
		$this->html = '
		<label><input id="' . $id . '" ' . $checked . ' type="checkbox" name="fabrik_notification" class="input" value="1"  />
		 ' . JText::_('PLG_CRON_NOTIFICATION_NOTIFY_ME') . '</label>';
	}

	/**
	 * Toggle notification
	 *
	 * @return  void
	 */

	public function toggleNotification()
	{
		// $$$ rob yes this looks odd but its right - as the js mouseup event is fired before the checkbox checked value changes
		$app = JFactory::getApplication();
		$notify = $app->input->get('notify') == 'true' ? false : true;
		$params = $this->getParams();
		$this->process($notify, 'observer', $params);
	}

	/**
	 * Get notification reference
	 *
	 * @param   int  $listid  Default list id
	 *
	 * @return string
	 */

	protected function getRef($listid = 0)
	{
		$db = FabrikWorker::getDbo();
		$app = JFactory::getApplication();
		$input = $app->input;
		return $db->quote($input->getInt('listid', $listid) . '.' . $input->getInt('formid', 0) . '.' . $input->get('rowid', '', 'string'));
	}

	/**
	 * Process the plugin
	 *
	 * @param   bool       $add     Add or remove notification
	 * @param   string     $why     Reason for notification
	 * @param   JRegistry  $params  Params
	 *
	 * @return  void
	 */

	protected function process($add, $why, $params)
	{
		$db = FabrikWorker::getDbo();
		$user = JFactory::getUser();
		$userid = (int) $user->get('id');
		$ref = $this->getRef();
		$query = $db->getQuery(true);
		$fields = array('reference = ' . $ref);

		if ($params->get('send_mode', 0) == '0')
		{
			$fields[] = 'user_id = ' . $userid;

			// Was using ON DUPLICATE KEY but that is mySQL specific and I couldn't see how an update could have occurred
			if ($add)
			{
				echo JText::_('PLG_CRON_NOTIFICATION_ADDED');
				$fields[] = 'reason = ' . $db->quote($why);
				$query->insert('#__{package}_notification')->set($fields);
				$db->setQuery($query);
				$db->execute();

			}
			else
			{
				$query->delete('#__{package}_notification')->where($fields);
				echo JText::_('PLG_CRON_NOTIFICATION_REMOVED');
				$db->setQuery($query);
				$db->execute();
			}
		}
		else
		{
			$sendTo = (array) $params->get('sendto');
			$userids = $this->getUsersInGroups($sendTo);

			foreach ($userids as $userid)
			{
				$query->clear('set');
				$fields2 = array_merge($fields, array('user_id = ' . $userid));
				$query->insert('#__{package}_notification')->set($fields2);
				$db->setQuery($query);
				$db->execute();
			}

		}
	}

	/**
	 * Test if the notifications should be fired
	 *
	 * @param   object     $formModel  Form model
	 * @param   JRegistry  $params     Params
	 *
	 * @return  bool
	 */

	protected function triggered($formModel, $params)
	{
		if ($params->get('send_mode', 0) == 0)
		{
			$user = JFactory::getUser();
			return $user->get('id') == 0 ? false : true;
		}
		else
		{
			$triggerEl = $formModel->getElement($params->get('trigger'), true);
			$trigger = $triggerEl->getFullName();
			$data = $formModel->getData();
			return JArrayHelper::getValue($data, $trigger) == $params->get('trigger_value') ? true : false;
		}
	}

	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @param   object  $params      Plugin params
	 * @param   object  &$formModel  Form model
	 *
	 * @return	bool
	 */

	public function onAfterProcess($params, &$formModel)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		if ($params->get('notification_ajax', 0) == 1)
		{
			return;
		}
		$user = JFactory::getUser();
		$userid = $user->get('id');
		$notify = $input->getInt('fabrik_notification', 0);

		if (!$this->triggered($formModel, $params))
		{
			return;
		}
		$rowId = $input->getString('rowid', '', 'string');
		$why = $rowId == '' ? 'author' : 'editor';
		$this->process($notify, $why, $params);

		// Add entry indicating the form has been updated this record will then be used by the cron plugin to
		// see which new events have been generated and notify subscribers of said events.
		$db = FabrikWorker::getDbo();
		$event = $rowId == '' ? $db->quote(JText::_('RECORD_ADDED')) : $db->quote(JText::_('RECORD_UPDATED'));
		$date = JFactory::getDate();
		$date = $db->quote($date->toSql());
		$ref = $this->getRef();
		$msg = $notify ? JText::_('PLG_CRON_NOTIFICATION_ADDED') : JText::_('PLG_CRON_NOTIFICATION_REMOVED');
		$app = JFactory::getApplication();
		$app->enqueueMessage($msg);
		$query = $db->getQuery(true);
		$fields = array('reference = ' . $ref, 'event = ' . $event, 'date_time = ' . $date);
		if ($params->get('send_mode') == '0')
		{
			$fields = array('user_id = ' . $userid);
			$query->insert('#__{package}_notification_event')->set($fields);
			$db->setQuery($query);
			$db->execute();
		}
		else
		{
			$sendTo = (array) $params->get('sendto');
			$userids = $this->getUsersInGroups($sendTo);
			$query->clear();
			foreach ($userids as $userid)
			{
				$query->clear('set');
				$fields2 = array_merge($fields, array('user_id = ' . $userid));
				$query->insert('#__{package}_notification_event')->set($fields2);
				$db->setQuery($query);
				$db->execute();
			}
		}
	}

}
