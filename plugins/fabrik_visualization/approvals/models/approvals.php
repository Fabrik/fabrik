<?php
/**
 * Approval viz Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.approvals
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
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

			$this->asField($formModel, $approveEls[$x], $asfields, array('alias' => 'approve'));
			$this->asField($formModel, $titles[$x], $asfields, array('alias' => 'title'));
			$this->asField($formModel, $users[$x], $asfields, array('alias' => 'user'));
			$this->asField($formModel, $contents[$x], $asfields, array('alias' => 'content'));

			$query->select($db->quote($item->label) . " AS type, " . $item->db_primary_key . ' AS pk, ' . implode(',', $asfields))
				->from($db->quoteName($item->db_table_name));
			$query = $listModel->buildQueryJoin($query);
			$query->where(str_replace('___', '.', $approveEls[$x]) . ' = 0');
			$db->setQuery($query, 0, 5);
			$rows = $db->loadObjectList();

			foreach ($rows as &$row)
			{
				$row->view = 'index.php?option=com_' . $this->package . '&task=form.view&formid=' . $formModel->getId() . '&rowid=' . $row->pk;
				$row->rowid = $row->pk;
				$row->listid = $ids[$x];
			}

			$this->rows = array_merge($this->rows, $rows);
		}

		return $this->rows;
	}

	/**
	 * Load up a field 'select as' statement
	 *
	 * @param   JModel  $formModel  Form model
	 * @param   string  $fieldName  Element full name
	 * @param   array   &$asfields  As fields to append as statement to
	 * @param   array   $opts       Options
	 *
	 * @throws RuntimeException
	 *
	 * @return  void
	 */
	private function asField($formModel, $fieldName, &$asfields, $opts)
	{
		$elementModel = $formModel->getElement($fieldName);
		$fields = array();

		if ($elementModel)
		{
			if ($elementModel->getElement()->published <> 1)
			{
				throw new RuntimeException('Approval ' . $fieldName . ' element must be published', 500);
			}

			$elementModel->getAsField_html($asfields, $fields, $opts);
		}
	}

	/**
	 * Disapprove a record
	 *
	 * @return  void
	 */
	public function disapprove()
	{
		$this->decide(0);
		echo FabrikWorker::j3() ? FabrikHelperHTML::icon('icon-remove') : FabrikHelperHTML::image('delete.png', 'list', '');
	}

	/**
	 * Approve a record
	 *
	 * @return  void
	 */
	public function approve()
	{
		$this->decide(1);
		echo FabrikWorker::j3() ? FabrikHelperHTML::icon('icon-ok') : FabrikHelperHTML::image('ok.png', 'list', '');
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
		$input = $this->app->input;
		$params = $this->getParams();
		$ids = (array) $params->get('approvals_table');
		$approveEls = (array) $params->get('approvals_approve_element');

		foreach ($ids as $key => $listId)
		{
			if ($listId == $input->getInt('listid'))
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
	 * Set an array of list id's whose data is used inside the visualization
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
