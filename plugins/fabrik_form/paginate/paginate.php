<?php
/**
 * Form record next/prev scroll plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.paginate
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Form record next/prev scroll plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.paginate
 * @since       3.0
 */

class PlgFabrik_FormPaginate extends PlgFabrik_Form
{
	/**
	 * Inject custom html into the bottom of the form
	 *
	 * @param   int  $c  Plugin counter
	 *
	 * @return  string  html
	 */

	public function getBottomContent_result($c)
	{
		return $this->data;
	}

	/**
	 * Sets up HTML to be injected into the form's bottom
	 *
	 * @return void
	 */

	public function getBottomContent()
	{
		$params = $this->getParams();
		$formModel = $this->getModel();

		if (!$this->show())
		{
			return;
		}

		$j3 = FabrikWorker::j3();
		$app = JFactory::getApplication();
		$input = $app->input;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$formId = $formModel->getForm()->id;
		$mode = JString::strtolower($input->get('view'));
		$this->ids = $this->getNavIds($formModel);
		$linkStartPrev = $this->ids->index == 0 ? ' disabled' : '';
		$linkNextEnd = $this->ids->index == $this->ids->lastKey ? ' disabled' : '';

		if ($app->isAdmin())
		{
			$url = 'index.php?option=com_fabrik&view=' . $mode . '&formid=' . $formId . '&rowid=';
		}
		else
		{
			$url = 'index.php?option=com_' . $package . '&view=' . $mode . '&formid=' . $formId . '&rowid=';
		}

		$links = array();
		$links['first'] = JRoute::_($url . $this->ids->first);
		$links['first-active'] = $linkStartPrev;
		$links['last-active'] = $linkNextEnd;
		$links['prev'] = JRoute::_($url . $this->ids->prev);
		$links['next'] = JRoute::_($url . $this->ids->next);
		$links['last'] = JRoute::_($url . $this->ids->last);

		if ($j3)
		{
			$layout = new JLayoutFile('plugins.fabrik_form.paginate.layouts.default_paginate', JPATH_BASE);
			$this->data = $layout->render($links);
		}
		else
		{
			$ajax = (bool) $params->get('paginate_ajax', true);
			$firstLink = ($linkStartPrev) ? '<span>&lt;&lt;</span>' . FText::_('COM_FABRIK_START')
				: '<a href="' . $links['first'] . '" class="pagenav paginateFirst ' . $linkStartPrev . '"><span>&lt;&lt;</span>'
					. FText::_('COM_FABRIK_START') . '</a>';
			$prevLink = ($linkStartPrev) ? '<span>&lt;</span>' . FText::_('COM_FABRIK_PREV')
				: '<a href="' . $lins['prev'] . '" class="pagenav paginatePrevious ' . $linkStartPrev . '"><span>&lt;</span>'
					. FText::_('COM_FABRIK_PREV') . '</a>';

			$nextLink = ($linkNextEnd) ? FText::_('COM_FABRIK_NEXT') . '<span>&gt;</span>'
				: '<a href="' . $links['next'] . '" class="pagenav paginateNext' . $linkNextEnd . '">' . FText::_('COM_FABRIK_NEXT')
					. '<span>&gt;</span></a>';
			$endLink = ($linkNextEnd) ? FText::_('COM_FABRIK_END') . '<span>&gt;&gt;</span>'
				: '<a href="' . $links['last'] . '" class="pagenav paginateLast' . $linkNextEnd . '">' . FText::_('COM_FABRIK_END')
					. '<span>&gt;&gt;</span></a>';
			$this->data = '<ul id="fabrik-from-pagination" class="pagination">
					<li>' . $firstLink . '</li>
					<li>' . $prevLink . '</li>
					<li>' . $nextLink . '</li>
					<li>' . $endLink . '</li>
			</ul>';
		}

		FabrikHelperHTML::stylesheet('plugins/fabrik_form/paginate/paginate.css');

		return true;
	}

	/**
	 * Get the first last, prev and next record ids
	 *
	 * @return  object
	 */

	protected function getNavIds()
	{
		$formModel = $this->getModel();
		$listModel = $formModel->getListModel();
		$table = $listModel->getTable();
		$db = $listModel->getDb();
		$query = $db->getQuery(true);

		// As we are selecting on primary key we can select all rows - 3000 records load in 0.014 seconds
		$query->select($table->db_primary_key)->from($table->db_table_name);
		$query = $listModel->buildQueryJoin($query);
		$query = $listModel->buildQueryWhere(true, $query);
		$query = $listModel->buildQueryOrder($query);
		$db->setQuery($query);
		$rows = $db->loadColumn();
		$keys = array_flip($rows);
		$o = new stdClass;
		$o->index = JArrayHelper::getValue($keys, $formModel->getRowId(), 0);
		$o->first = $rows[0];
		$o->lastKey = count($rows) - 1;
		$o->last = $rows[$o->lastKey];
		$o->next = $o->index + 1 > $o->lastKey ? $o->lastKey : $rows[$o->index + 1];
		$o->prev = $o->index - 1 < 0 ? 0 : $rows[$o->index - 1];

		return $o;
	}

	/**
	 * Show we show the pagination
	 *
	 * @return  bool
	 */

	protected function show()
	{
		/* Nobody except form model constructor sets editable property yet -
		 * it sets in view.html.php only and after render() - too late I think
		 * so no pagination output for frontend details view for example.
		 * Let's set it here before use it
		 */
		$params = $this->getParams();
		$formModel = $this->getModel();
		$formModel->checkAccessFromListSettings();
		$where = $params->get('paginate_where');

		switch ($where)
		{
			case 'both':
				return true;
				break;
			case 'form':
				return (bool) $formModel->isEditable() == 1;
				break;
			case 'details':
				return (bool) $formModel->isEditable() == 0;
				break;
		}
	}

	/**
	 * Need to do this rather than on onLoad as otherwise in chrome form.js addevents is fired
	 * before autocomplete class ini'd so then the autocomplete class never sets itself up
	 *
	 * @return  void
	 */

	public function onAfterJSLoad()
	{
		$formModel = $this->getModel();
		$params = $this->getParams();

		if (!$this->show())
		{
			return;
		}

		if ($params->get('paginate_ajax') == 0)
		{
			return;
		}

		$app = JFactory::getApplication();
		$input = $app->input;
		$opts = new stdClass;
		$opts->liveSite = COM_FABRIK_LIVESITE;
		$opts->view = $input->get('view');
		$opts->ids = $this->ids;
		$opts->pkey = FabrikString::safeColNameToArrayKey($formModel->getTableModel()->getTable()->db_primary_key);
		$opts = json_encode($opts);
		$container = $formModel->jsKey();
		$this->formJavascriptClass($params, $formModel);
		$formModel->formPluginJS .= "\n var " . $container . "_paginate = new FabRecordSet($container, $opts);";
	}

	/**
	 * Called from plugins ajax call
	 *
	 * @return  void
	 */

	public function onXRecord()
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input = $app->input;
		$formid = $input->getInt('formid');
		$rowid = $input->get('rowid', '', 'string');
		$mode = $input->get('mode', 'details');
		$model = JModelLegacy::getInstance('Form', 'FabrikFEModel');
		$model->setId($formid);
		$this->setModel($model);
		$model->rowId = $rowid;
		$ids = $this->getNavIds();
		$url = COM_FABRIK_LIVESITE
			. 'index.php?option=com_' . $package . '&format=raw&controller=plugin&g=form&task=pluginAjax&plugin=paginate&method=xRecord&formid=' . $formid
			. '&rowid=' . $rowid;
		$url = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $package . '&view=' . $mode . '&formid=' . $formid . '&rowid=' . $rowid . '&format=raw';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);

		// Append the ids to the json array
		$data = json_decode($data);
		$data->ids = $ids;
		echo json_encode($data);
	}
}
