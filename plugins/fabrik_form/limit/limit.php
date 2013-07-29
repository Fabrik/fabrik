<?php
/**
* @package     Joomla.Plugin
* @subpackage  Fabrik.form.limit
* @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
* @license     GNU General Public License version 2 or later; see LICENSE.txt
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
* Form limit submissions plugin
*
* @package     Joomla.Plugin
* @subpackage  Fabrik.form.limit
* @since       3.0
*/

class PlgFabrik_FormLimit extends plgFabrik_Form
{

	/**
	 * Process the plugin, called when form is loaded
	 *
	 * @param   object  $params      plugin parameters
	 * @param   JModel  &$formModel  Form model
	 *
	 * @return  void
	 */

	public function onLoad($params, &$formModel)
	{
		return $this->_process($params, $formModel);
	}

	/**
	 * Process the plugin
	 *
	 * @param   object  $params      Plugin params
	 * @param   JModel  &$formModel  Form model
	 *
	 * @return  bool
	 */
	private function _process($params, &$formModel)
	{
		if ($params->get('limit_allow_anonymous'))
		{
			return true;
		}
		if (JRequest::getCmd('view') === 'details' || $formModel->getRowId() > 0)
		{
			return true;
		}

		$limit = $this->limit();
		$c = $this->count($formModel);

		// Allow for unlimited
		if ($limit == -1)
		{
			return true;
		}
		if ($c >= $limit)
		{
			$msg = $params->get('limit_reached_message', JText::sprintf('PLG_FORM_LIMIT_LIMIT_REACHED', $limit));
			$msg = str_replace('{limit}', $limit, $msg);
			JError::raiseNotice(1, $msg);
			return false;
		}
		else
		{
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::sprintf('PLG_FORM_LIMIT_ENTRIES_LEFT_MESSAGE', $limit - $c, $limit));
		}
		return true;
	}

	/**
	 * Count the number of records the user has already submitted
	 *
	 * @param   JModel  $formModel  Form model
	 *
	 * @return  int
	 */
	protected function count($formModel)
	{
		$user = JFactory::getUser();
		$params = $this->getParams();
		$field = $params->get('limit_userfield');
		$listModel = $formModel->getlistModel();
		$list = $listModel->getTable();
		$db = $listModel->getDb();
		$query = $db->getQuery(true);
		$query->clear()->select(' COUNT(' . $field . ')')->from($list->db_table_name)->where($field . ' = ' . (int) $user->get('id'));
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
		$listid = (int) $params->get('limit_table');
		if ($listid === 0)
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
	 * looup done on user id OR user groups, max limit returned
	 *
	 * @return number
	 */
	protected function limitQuery()
	{
		$user = JFactory::getUser();
		$params = $this->getParams();
		$listid = (int) $params->get('limit_table');
		$listModel = JModel::getInstance('List', 'FabrikFEModel');
		$listModel->setId($listid);
		$dbTable = $listModel->getTable()->db_table_name;
		$db = $listModel->getDb();
		$query = $db->getQuery(true);
		$lookup = FabrikString::safeColName($params->get('limit_user'));
		$max = FabrikString::safeColName($params->get('limit_max'));
		$query->select('MAX(' . $max . ')')->from($dbTable);
		$type = $params->get('lookup_type', '');
		if ($type == 'user')
		{
			$query->where($lookup . ' = ' . (int) $user->get('id'));
		}
		else
		{
			$groups = $user->getAuthorisedGroups();
			$query->where($lookup . ' IN (' . implode(',', $groups) . ')');
		}
		$db->setQuery($query);
		$limit = (int) $db->loadResult();
		return $limit;
	}

}
