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
	 * @param   int  $c  plugin counter
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
	 * @param   object  $params     params
	 * @param   object  $formModel  form model
	 *
	 * @return void
	 */

	public function getBottomContent($params, $formModel)
	{
		if (!$this->show($params, $formModel))
		{
			return;
		}
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
		$ajax = (bool) $params->get('paginate_ajax', true);
		$firstLink = ($linkStartPrev) ? '<span>&lt;&lt;</span>' . JText::_('COM_FABRIK_START')
			: '<a href="' . JRoute::_($url . $this->ids->first) . '" class="pagenav paginateFirst ' . $linkStartPrev . '"><span>&lt;&lt;</span>'
				. JText::_('COM_FABRIK_START') . '</a>';
		$prevLink = ($linkStartPrev) ? '<span>&lt;</span>' . JText::_('COM_FABRIK_PREV')
			: '<a href="' . JRoute::_($url . $this->ids->prev) . '" class="pagenav paginatePrevious ' . $linkStartPrev . '"><span>&lt;</span>'
				. JText::_('COM_FABRIK_PREV') . '</a>';

		$nextLink = ($linkNextEnd) ? JText::_('COM_FABRIK_NEXT') . '<span>&gt;</span>'
			: '<a href="' . JRoute::_($url . $this->ids->next) . '" class="pagenav paginateNext' . $linkNextEnd . '">' . JText::_('COM_FABRIK_NEXT')
				. '<span>&gt;</span></a>';
		$endLink = ($linkNextEnd) ? JText::_('COM_FABRIK_END') . '<span>&gt;&gt;</span>'
			: '<a href="' . JRoute::_($url . $this->ids->last) . '" class="pagenav paginateLast' . $linkNextEnd . '">' . JText::_('COM_FABRIK_END')
				. '<span>&gt;&gt;</span></a>';
		$this->data = '<ul id="fabrik-from-pagination" class="pagination">
				<li>' . $firstLink . '</li>
				<li>' . $prevLink . '</li>
				<li>' . $nextLink . '</li>
				<li>' . $endLink . '</li>
		</ul>';
		FabrikHelperHTML::stylesheet('plugins/fabrik_form/paginate/paginate.css');
		return true;
	}

	/**
	 * Get the first last, prev and next record ids
	 *
	 * @param   object  $formModel  form model
	 *
	 * @return  object
	 */

	protected function getNavIds($formModel)
	{
		$listModel = $formModel->getListModel();
		$table = $listModel->getTable();
		$db = $listModel->getDb();

		$join = $listModel->buildQueryJoin();
		$where = $listModel->buildQueryWhere();
		$order = $listModel->buildQueryOrder();

		// @ rob as we are selecting on primary key we can select all rows - 3000 records load in 0.014 seconds
		$query = "SELECT $table->db_primary_key FROM $table->db_table_name $join $where $order";

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
	 * @param   object  $params     plugin params
	 * @param   object  $formModel  form model
	 *
	 * @return  bool
	 */

	protected function show($params, $formModel)
	{
		/* Nobody except form model constuctor sets editable property yet -
		 * it sets in view.html.php only and after render() - too late I think
		 * so no pagination output for frontend details veiw for example.
		 * Let's set it here before use it
		 */
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
	 * @param   object  &$params     plugin params
	 * @param   object  &$formModel  form model
	 *
	 * @return  void
	 */

	public function onAfterJSLoad(&$params, &$formModel)
	{
		if (!$this->show($params, $formModel))
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
		$form = $formModel->getForm();
		$container = $formModel->isEditable() ? 'form' : 'details';
		$container .= '_' . $form->id;

		$this->formJavascriptClass($params, $formModel);
		$formModel->formPluginJS .= "\n" . "$container.addPlugin(new FabRecordSet($container, $opts));";
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
		$model->rowId = $rowid;
		$ids = $this->getNavIds($model);
		$url = COM_FABRIK_LIVESITE
			. 'index.php?option=com_' . $package . '&format=raw&controller=plugin&g=form&task=pluginAjax&plugin=paginate&method=xRecord&formid=' . $formid
			. '&rowid=' . $rowid;
		$url = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $package . '&c=form&view=' . $mode . '&fabrik=' . $formid . '&rowid=' . $rowid . '&format=raw';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);

		// Apend the ids to the json array
		$data = json_decode($data);
		$data->ids = $ids;
		echo json_encode($data);

	}

}
