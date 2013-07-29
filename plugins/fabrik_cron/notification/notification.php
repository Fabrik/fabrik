<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-cron.php';

/**
 * A cron task to email records to a give set of users
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @since       3.0
 */

class PlgFabrik_Cronnotification extends PlgFabrik_Cron
{

	/**
	 * Check if the user can use the active element
	 *
	 * @param   object  &$model    calling the plugin list/form
	 * @param   string  $location  to trigger plugin on
	 * @param   string  $event     to trigger plugin on
	 *
	 * @return  bool can use or not
	 */

	public function canUse(&$model = null, $location = null, $event = null)
	{
		return true;
	}

	/**
	 * Do the plugin action
	 *
	 * @param   array  &$data  Record data
	 *
	 * @return number of records updated
	 */

	public function process(&$data)
	{

		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);
		$query->select('n.*, e.event AS event, e.id AS event_id,
		n.user_id AS observer_id, observer_user.name AS observer_name, observer_user.email AS observer_email,
		e.user_id AS creator_id, creator_user.name AS creator_name, creator_user.email AS creator_email')
		->from('#__{package}_notification AS n')
		->join('LEFT', '#__{package}_notification_event AS e ON e.reference = n.reference')
		->join('LEFT', '#__{package}_notification_event_sent AS s ON s.notification_event_id = e.id')
		->join('INNER', '#__users AS observer_user ON observer_user.id = n.user_id')
		->join('INNER', '#__users AS creator_user ON creator_user.id = e.user_id')
		->where('(s.sent <> 1 OR s.sent IS NULL)  AND  n.user_id <> e.user_id')
		->order('n.reference');

		/* $sql = "SELECT n.*, e.event AS event, e.id AS event_id,
		n.user_id AS observer_id, observer_user.name AS observer_name, observer_user.email AS observer_email,
		e.user_id AS creator_id, creator_user.name AS creator_name, creator_user.email AS creator_email
		 FROM #__{package}_notification AS n" . "\n LEFT JOIN #__{package}_notification_event AS e ON e.reference = n.reference"
			. "\n LEFT JOIN #__{package}_notification_event_sent AS s ON s.notification_event_id = e.id"
			. "\n INNER JOIN #__users AS observer_user ON observer_user.id = n.user_id"
			. "\n INNER JOIN #__users AS creator_user ON creator_user.id = e.user_id" . "\n WHERE (s.sent <> 1 OR s.sent IS NULL)"
			. "\n AND  n.user_id <> e.user_id" . "\n ORDER BY n.reference"; //don't bother informing users about events that they've created themselves */
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		$config = JFactory::getConfig();
		$email_from = $config->get('mailfrom');
		$sitename = $config->get('sitename');
		$sent = array();
		$usermsgs = array();

		$successMails = array();
		$failedMails = array();
		foreach ($rows as $row)
		{
			/*
			 * {observer_name, creator_name, event, record url
			 * dear %s, %s has %s on %s
			 */
			$event = JText::_($row->event);
			list($listid, $formid, $rowid) = explode('.', $row->reference);

			$url = JRoute::_('index.php?option=com_fabrik&view=details&listid=' . $listid . '&formid=' . $formid . '&rowid=' . $rowid);
			$msg = JText::sprintf('FABRIK_NOTIFICATION_EMAIL_PART', $row->creator_name, $url, $event);
			if (!array_key_exists($row->observer_id, $usermsgs))
			{
				$usermsgs[$row->observer_email] = array();
			}
			$usermsgs[$row->observer_email][] = $msg;

			$query->clear();
			$query->insert('#__{package}_notification_event_sent')
			->set(array('notification_event_id = ' . $row->event_id, 'user_id = ' . $row->observer_id, 'sent = 1'));
			$sent[] = (string) $query;
			/* $sent[] = 'INSERT INTO #__{package}_notification_event_sent (`notification_event_id`, `user_id`, `sent`) VALUES (' . $row->event_id
				. ', ' . $row->observer_id . ', 1)'; */
		}
		$subject = $sitename . ": " . JText::_('FABRIK_NOTIFICATION_EMAIL_SUBJECT');
		foreach ($usermsgs as $email => $messages)
		{
			$msg = implode(' ', $messages);
			if (JUtility::sendMail($email_from, $email_from, $email, $subject, $msg, true))
			{
				$successMails[] = $email;
			}
			else
			{
				$failedMails[] = $email;
			}

		}
		if (!empty($sent))
		{
			$sent = implode(';', $sent);
			$db->setQuery($sent);
			$db->execute();
		}
		$this->log = count($sent) . ' notifications sent.<br />';
		$this->log .= 'Emailed users: <ul><li>' . implode('</li><li>', $successMails) . '</li></ul>';
		if (!empty($failedMails))
		{
			$this->log .= 'Failed emails: <ul><li>' . implode('</li><li>', $failedMails) . '</li></ul>';
		}
	}

}
