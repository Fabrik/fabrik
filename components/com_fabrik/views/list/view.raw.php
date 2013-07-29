<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once JPATH_SITE . '/components/com_fabrik/views/list/view.base.php';

/**
 * RAw Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikViewList extends FabrikViewListBase
{

	/**
	 * Display the template
	 *
	 * @param   sting  $tpl  template
	 *
	 * @return void
	 */

	public function display($tpl = null)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$model = $this->getModel();
		$model->setId($input->getInt('listid'));
		if (!parent::access($model))
		{
			exit;
		}
		$table = $model->getTable();
		$params = $model->getParams();
		$rowid = $input->getInt('rowid');
		list($this->headings, $groupHeadings, $this->headingClass, $this->cellClass) = $this->get('Headings');
		$data = $model->render();
		$this->emptyDataMessage = $this->get('EmptyDataMsg');
		$nav = $model->getPagination();
		$form = $model->getFormModel();
		$c = 0;
		foreach ($data as $groupk => $group)
		{
			foreach ($group as $i => $x)
			{
				$o = new stdClass;
				if (is_object($data[$groupk]))
				{
					$o->data = JArrayHelper::fromObject($data[$groupk]);
				}
				else
				{
					$o->data = $data[$groupk][$i];
				}
				$o->groupHeading = JArrayHelper::getValue($model->groupTemplates, $groupk, '') . ' ( ' . count($group) . ' )';
				$o->cursor = $i + $nav->limitstart;
				$o->total = $nav->total;
				$o->id = 'list_' . $model->getRenderContext() . '_row_' . @$o->data->__pk_val;
				$o->class = 'fabrik_row oddRow' . $c;
				if (is_object($data[$groupk]))
				{
					$data[$groupk] = $o;
				}
				else
				{
					$data[$groupk][$i] = $o;
				}
				$c = 1 - $c;
			}
		}

		$groups = $form->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$elementModel->setContext($groupModel, $form, $model);
				$elementModel->setRowClass($data);
			}
		}
		$d = array('id' => $table->id, 'listRef' => JRequest::getVar('listref'), 'rowid' => $rowid, 'model' => 'list', 'data' => $data,
		'headings' => $this->headings,
			'formid' => $model->getTable()->form_id,
			'lastInsertedRow' => JFactory::getSession()->get('lastInsertedRow', 'test'));
		$d['nav'] = $nav->getProperties();
		$tmpl = $input->get('tmpl', $this->getTmpl());
		$d['htmlnav'] = $params->get('show-table-nav', 1) ? $nav->getListFooter($model->getId(), $tmpl) : '';
		$d['calculations'] = $model->getCalculations();

		// $$$ hugh - see if we have a message to include, set by a list plugin
		$context = 'com_' . $package . '.list' . $model->getRenderContext() . '.msg';
		$session = JFactory::getSession();
		if ($session->has($context))
		{
			$d['msg'] = $session->get($context);
			$session->clear($context);
		}
		echo json_encode($d);
	}

	/**
	 * Get the view template name
	 *
	 * @return  string template name
	 */

	private function getTmpl()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$model = $this->getModel();
		$table = $model->getTable();
		$params = $model->getParams();
		if ($app->isAdmin())
		{
			$tmpl = $params->get('admin_template');
			if ($tmpl == -1 || $tmpl == '')
			{
				$tmpl = $input->get('layout', $table->template);
			}
		}
		else
		{
			$tmpl = $input->get('layout', $table->template);
		}
		return $tmpl;
	}
}
