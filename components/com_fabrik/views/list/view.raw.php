<?php
/**
 * Raw Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

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
		$input = $this->app->input;

		/** @var FabrikFEModelList $model */
		$model = $this->getModel();
		$model->setId($input->getInt('listid'));

		if (!parent::access($model))
		{
			exit;
		}

		$table = $model->getTable();
		$params = $model->getParams();
		$rowId = $input->getString('rowid', '', 'string');
		$data = $model->render();
		list($this->headings, $groupHeadings, $this->headingClass, $this->cellClass) = $this->get('Headings');
		$this->emptyDataMessage = $this->get('EmptyDataMsg');
		$nav = $model->getPagination();
		$form = $model->getFormModel();
		$c = 0;

		foreach ($data as $groupKey => $group)
		{
			foreach ($group as $i => $x)
			{
				$o = new stdClass;

				if (is_object($data[$groupKey]))
				{
					$o->data = ArrayHelper::fromObject($data[$groupKey]);
				}
				else
				{
					$o->data = $data[$groupKey][$i];
				}

				// should really stick this in a layout to match how it is built in list.fabrik-group-by-heading
				if (array_key_exists($groupKey, $model->groupTemplates))
				{
					$o->groupHeading = $model->groupTemplates[$groupKey];
					if ($params->get('group_by_show_count','1') == '1')
					{
						$o->groupHeading .= '<span class="groupCount">( ' . count($group) . ' )</span>';
					}
				}

				$o->cursor = $i + $nav->limitstart;
				$o->total = $nav->total;
				$o->id = 'list_' . $model->getRenderContext() . '_row_' . @$o->data->__pk_val;
				$o->class = 'fabrik_row oddRow' . $c;

				if (is_object($data[$groupKey]))
				{
					$data[$groupKey] = $o;
				}
				else
				{
					$data[$groupKey][$i] = $o;
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

		$d = array('id' => $table->id, 'listRef' => $input->get('listref'), 'rowid' => $rowId, 'model' => 'list', 'data' => $data,
			'headings' => $this->headings, 'formid' => $model->getTable()->form_id,
			'lastInsertedRow' => $this->session->get('lastInsertedRow', 'test'));

		$d['nav'] = get_object_vars($nav);
		$tmpl = $input->get('tmpl', $this->getTmpl());
		$d['htmlnav'] = $params->get('show-table-nav', 1) ? $nav->getListFooter($model->getId(), $tmpl) : '';
		$d['calculations'] = $model->getCalculations();
		$d['hasFilters'] = $model->gotOptionalFilters();
		$d['searchallvalue'] = $model->getFilterModel()->getSearchAllValue('html');

		// $$$ hugh - see if we have a message to include, set by a list plugin
		$context = 'com_' . $this->package . '.list' . $model->getRenderContext();

		if ($this->session->has($context . '.msg'))
		{
			$d['msg'] = $this->session->get($context . '.msg');

			if ($this->session->has($context . '.showmsg'))
			{
                $d['showmsg'] = $this->session->get($context . '.showmsg');
            }
			else
            {
                $d['showmsg'] = true;
            }

			$this->session->clear($context . '.msg');
            $this->session->clear($context . '.showmsg');
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
		$input = $this->app->input;
		$model = $this->getModel();
		$table = $model->getTable();
		$params = $model->getParams();

		if ($this->app->isAdmin())
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
