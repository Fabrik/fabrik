<?php
/**
 * Fabrik Notification Model
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

/**
 * The cron notification plugin model.
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @since       3.0
 */

class FabrikModelNotification extends JModel
{

	/**
	 * Get the current logged in users notifications
	 *
	 * @return array
	 */

	public function getUserNotifications()
	{
		$rows = $this->getRows();
		if (!$rows)
		{
			$this->makeDbTable();
			$rows = $this->getRows();
		}

		$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		foreach ($rows as &$row)
		{
			/*
			 * {observer_name, creator_name, event, record url
			 * dear %s, %s has %s on %s
			 */
			$event = JText::_($row->event);
			list($listid, $formid, $rowid) = explode('.', $row->reference);

			$listModel->setId($listid);
			$data = $listModel->getRow($rowid);
			$row->url = JRoute::_('index.php?option=com_fabrik&view=details&listid=' . $listid . '&formid=' . $formid . '&rowid=' . $rowid);
			$row->title = $row->url;
			foreach ($data as $key => $value)
			{
				$k = JString::strtolower(array_pop(explode('___', $key)));
				if ($k == 'title')
				{
					$row->title = $value;
				}
			}
		}
		return $rows;
	}

	/**
	 * Make notification db tables
	 *
	 * @return  void
	 */
	protected function makeDbTable()
	{
		parent::makeDbTable();
	}

	/**
	 * Get Rows
	 *
	 * @return  array
	 */

	protected function getRows()
	{
		$user = JFactory::getUser();
		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')->from('#__{package}_notification')->where('user_id = ' . (int) $user->get('id'));
		$db->setQuery($query);
		return $db->loadObjectList();
	}

	/**
	 * Delete a notification
	 *
	 * @return  void
	 */

	public function delete()
	{
		// Check for request forgeries
		JSessoin::checkToken() or die('Invalid Token');
		$app = JFactory::getApplication();
		$ids = $app->input->get('cid', array());
		JArrayHelper::toInteger($ids);
		if (empty($ids))
		{
			return;
		}
		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);
		$query->delete('#__{package}_notification')->where('id IN (' . implode(',', $ids) . ')');
		$db->setQuery($query);
		$db->execute();
	}

}
