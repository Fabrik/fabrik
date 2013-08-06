<?php
/**
 * Approval viz Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.approvals
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/visualization.php';

/**
 * Approval viz Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.approvals
 * @since       3.0
 */

class FabrikModelApprovals extends FabrikFEModelVisualization
{

	/**
	 * Get the rows of data to show in the viz
	 *
	 * @return   array
	 */

	public function getRows()
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$params = $this->getParams();
		$ids = (array) $params->get('approvals_table');
		$approveEls = (array) $params->get('approvals_approve_element');
		$titles = (array) $params->get('approvals_title_element');
		$users = (array) $params->get('approvals_user_element');
		$contents = (array) $params->get('approvals_content_element');

		$this->rows = array();
		for ($x = 0; $x < count($ids); $x++)
		{
			$asfields = array();
			$fields = array();
			$listModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
			$listModel->setId($ids[$x]);
			$item = $listModel->getTable();
			$formModel = $listModel->getFormModel();
			$formModel->getForm();
			$db = $listModel->getDb();
			$query = $db->getQuery(true);

			$approveEl = $formModel->getElement($approveEls[$x]);
			$approveEl->getAsField_html($asfields, $fields, array('alias' => 'approve'));

			$titleEl = $formModel->getElement($titles[$x]);
			$titleEl->getAsField_html($asfields, $fields, array('alias' => 'title'));

			$userEl = $formModel->getElement($users[$x]);
			$userEl->getAsField_html($asfields, $fields, array('alias' => 'user'));

			if (JArrayHelper::getValue($contents, $x, '') !== '')
			{
				$contentEl = $formModel->getElement($contents[$x]);
				$contentEl->getAsField_html($asfields, $fields, array('alias' => 'content'));
			}

			$query->select($db->quote($item->label) . " AS type, " . $item->db_primary_key . ' AS pk, ' . implode(',', $asfields))
				->from($db->quoteName($item->db_table_name));
			$query = $listModel->buildQueryJoin($query);
			$query->where(str_replace('___', '.', $approveEls[$x]) . ' = 0');
			$db->setQuery($query, 0, 5);
			$rows = $db->loadObjectList();
			foreach ($rows as &$row)
			{
				$row->view = 'index.php?option=com_' . $package . '&task=form.view&formid=' . $formModel->getId() . '&rowid=' . $row->pk;
				$row->rowid = $row->pk;
				$row->listid = $ids[$x];
			}
			$this->rows = array_merge($this->rows, $rows);
		}
		return $this->rows;
	}

	/**
	 * Disapprove a record
	 *
	 * @return  void
	 */

	public function disapprove()
	{
		$this->decide(0);
		echo FabrikHelperHTML::image('delete.png', 'list', '');
	}

	/**
	 * Approve a record
	 *
	 * @return  void
	 */

	public function approve()
	{
		$this->decide(1);
		echo FabrikHelperHTML::image('ok.png', 'list', '');
	}

	/**
	 * Decide if we should approve or not?
	 *
	 * @param   string  $v  update value
	 *
	 * @return  void
	 */

	protected function decide($v)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$params = $this->getParams();
		$ids = (array) $params->get('approvals_table');
		$approveEls = (array) $params->get('approvals_approve_element');
		foreach ($ids as $key => $listid)
		{
			if ($listid == $input->getInt('listid'))
			{
				$listModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
				$listModel->setId($input->getInt('listid'));
				$item = $listModel->getTable();
				$db = $listModel->getDbo();
				$query = $db->getQuery(true);
				$el = FabrikString::safeColName($approveEls[$key]);
				$query->update($db->quoteName($item->db_table_name))->set($el . ' = ' . $db->quote($v))
					->where($item->db_primary_key . ' = ' . $db->quote($input->get('rowid')));
				$db->setQuery($query);
				$db->execute();

			}
		}
	}

	/**
	 * Set an array of list id's whose data is used inside the visualaziation
	 *
	 * @return  void
	 */

	protected function setListIds()
	{
		if (!isset($this->listids))
		{
			$params = $this->getParams();
			$this->listids = (array) $params->get('approvals_table');
		}
	}
}
