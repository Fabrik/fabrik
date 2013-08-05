<?php
/**
 * Raw Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE . '/components/com_fabrik/views/list/view.base.php';

/**
 * Raw Fabrik List view class
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
		$rowid = $input->getString('rowid', '', 'string');
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
				if (array_key_exists($groupk, $model->groupTemplates))
				{
					$o->groupHeading = $model->groupTemplates[$groupk] . ' ( ' . count($group) . ' )';
				}
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
		$d = array('id' => $table->id, 'listRef' => $input->get('listref'), 'rowid' => $rowid, 'model' => 'list', 'data' => $data,
			'headings' => $this->headings, 'formid' => $model->getTable()->form_id,
			'lastInsertedRow' => JFactory::getSession()->get('lastInsertedRow', 'test'));

		$d['nav'] = get_object_vars($nav);
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
