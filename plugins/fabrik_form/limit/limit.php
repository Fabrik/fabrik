<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.limit
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Form limit submissions plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.limit
 * @since       3.0
 */
class PlgFabrik_FormLimit extends PlgFabrik_Form
{
	/**
	 * Process the plugin, called when form is loaded
	 *
	 * @return  void
	 */
	public function onLoad()
	{
		return $this->_process();
	}

	/**
	 * Process the plugin
	 *
	 * @return  bool
	 */
	private function _process()
	{
		$params = $this->getParams();
		$formModel = $this->getModel();
		$this->data = $this->getProcessData();

		if (!$this->shouldProcess('limit_condition', null, $params))
		{
			return;
		}

		if ($params->get('limit_allow_anonymous'))
		{
			return true;
		}

		if ($this->app->input->get('view') === 'details' || $formModel->getRowId() !== '')
		{
			return true;
		}

		$limit = $this->limit();

		// Allow for unlimited
		if ($limit == -1)
		{
			return true;
		}

		$c = $this->count();

		if ($c === false)
		{
			$this->app->enqueueMessage(FText::_("PLG_FORM_LIMIT_NOT_SETUP"));

			return false;
		}

		if ($c >= $limit)
		{
			$msg = $params->get('limit_reached_message', JText::sprintf('PLG_FORM_LIMIT_LIMIT_REACHED', $limit));
			$msg = str_replace('{limit}', $limit, $msg);
			$this->app->enqueueMessage(FText::_($msg), 'notice');

			return false;
		}
		else
		{
			if ($params->get('show_limit_message', true))
			{
				$this->app->enqueueMessage(JText::sprintf('PLG_FORM_LIMIT_ENTRIES_LEFT_MESSAGE', $limit - $c, $limit));
			}
		}

		return true;
	}

	/**
	 * Count the number of records the user has already submitted
	 *
	 * @return  int
	 */
	protected function count()
	{
		$formModel = $this->getModel();
		$params = $this->getParams();
		$field = $params->get('limit_userfield');
		$fk = $params->get('limit_fk');
		$fkVal = '';

		/*
		if (empty($field))
		{
			return false;
		}
		*/

		if (!empty($fk))
		{
			$fkVal = FArrayHelper::getValue(
					$formModel->data,
					FabrikString::safeColNameToArrayKey($fk),
					FArrayHelper::getValue(
							$formModel->data,
							FabrikString::safeColNameToArrayKey($fk) . '_raw',
							''
					)
			);
		}

		$listModel = $formModel->getlistModel();
		$list = $listModel->getTable();
		$db = $listModel->getDb();
		$query = $db->getQuery(true);
		$query->clear()->select(' COUNT(*)')->from($list->db_table_name);

		if (!empty($field))
		{
			$query->where($field . ' = ' .
			(int) $this->user->get('id'));
		}

		if (!empty($fkVal))
		{
			$query->where($db->qn($fk) . ' = ' . $db->q($fkVal), 'AND');
		}

		$db->setQuery($query);

		return (int) $db->loadResult();
	}

	/**
	 * Work ok the max number of records the user can submit
	 *
	 * @return number
	 */
	protected function limit()
	{
		$params = $this->getParams();
		$listId = (int) $params->get('limit_table');

		if ($listId === 0)
		{
			// Use the limit setting supplied in the admin params
			$limit = (int) $params->get('limit_length');
		}
		else
		{
			// Query the db to get limits
			$limit = $this->limitQuery();
		}

		return $limit;
	}

	/**
	 * Look up the limit from the table spec'd in the admin params
	 * lookup done on user id OR user groups, max limit returned
	 *
	 * @return number
	 */
	protected function limitQuery()
	{
		$params = $this->getParams();
		$listId = (int) $params->get('limit_table');
		$listModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
		$listModel->setId($listId);
		$dbTable = $listModel->getTable()->db_table_name;
		$db = $listModel->getDb();
		$query = $db->getQuery(true);
		$lookup = FabrikString::safeColName($params->get('limit_user'));
		$max = FabrikString::safeColName($params->get('limit_max'));
		$query->select('MAX(' . $max . ')')->from($dbTable);
		$type = $params->get('lookup_type', '');

		if ($type == 'user')
		{
			$query->where($lookup . ' = ' . (int) $this->user->get('id'));
		}
		else
		{
			$groups = $this->user->getAuthorisedGroups();
			$query->where($lookup . ' IN (' . implode(',', $groups) . ')');
		}

		$db->setQuery($query);
		$limit = $db->loadResult();

		if (!isset($limit))
		{
			$addSql = $params->get('limit_add_sql', '');

			if (!empty($addSql))
			{
				$w = new FabrikWorker;
				$addSql = $w->parseMessageForPlaceHolder($addSql);
				$db->setQuery($addSql);
				$db->execute();
				$limit = (int) $params->get('limit_length', '0');

			}
			else
			{
				$limit = 0;
			}
		}
		else
		{
			$limit = (int) $limit;
		}

		return $limit;
	}
}
