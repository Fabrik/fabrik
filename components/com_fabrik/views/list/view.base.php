<?php
/**
* @package     Joomla
* @subpackage  Fabrik
* @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
* @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * Base List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0.6
 */

class FabrikViewListBase extends JView
{

	public $isMambot = null;

	/**
	 * Get JS objects
	 *
	 * @param   array  $data  list data
	 *
	 * @return  void
	 */

	protected function getManagementJS($data = array())
	{
		$app = JFactory::getApplication();
		$menuItem = $app->getMenu('site')->getActive();
		$Itemid = is_object($menuItem) ? $menuItem->id : 0;
		$model = $this->getModel();
		$item = $model->getTable();
		$listref = $model->getRenderContext();
		$listid = $model->getId();
		$formModel = $model->getFormModel();
		$elementsNotInTable = $formModel->getElementsNotInTable();
		if ($model->requiresSlimbox())
		{
			FabrikHelperHTML::slimbox();
		}
		$frameworkJsFiles = FabrikHelperHTML::framework();
		$src = $model->getPluginJsClasses($frameworkJsFiles);
		array_unshift($src, 'media/com_fabrik/js/listfilter.js');
		array_unshift($src, 'media/com_fabrik/js/list.js');
		array_unshift($src, 'media/com_fabrik/js/advanced-search.js');

		$model->getCustomJsAction($src);
		$src[] = 'media/com_fabrik/js/encoder.js';

		$tmpl = $this->get('tmpl');
		$this->assign('tmpl', $tmpl);

		$this->get('ListCss');

		// Check for a custom js file and include it if it exists
		$aJsPath = JPATH_SITE . '/components/com_fabrik/views/list/tmpl/' . $tmpl . '/javascript.js';
		if (JFile::exists($aJsPath))
		{
			$src[] = 'components/com_fabrik/views/list/tmpl/' . $tmpl . '/javascript.js';
		}

		$origRows = $this->rows;
		$this->rows = array(array());

		$tmpItemid = !isset($Itemid) ? 0 : $Itemid;

		$this->_row = new stdClass;
		$script = array();
		$script[] = "head.ready(function() {";

		$params = $model->getParams();
		$opts = new stdClass;
		$opts->admin = $app->isAdmin();
		$opts->ajax = (int) $model->isAjax();
		$opts->ajax_links = (bool) $params->get('list_ajax_links', $opts->ajax);

		$opts->links = array('detail' => $params->get('detailurl', ''), 'edit' => $params->get('editurl', ''), 'add' => $params->get('addurl', ''));
		$opts->filterMethod = $this->filter_action;
		$opts->form = 'listform_' . $listref;
		$this->listref = $listref;
		$opts->headings = $model->_jsonHeadings();
		$labels = $this->headings;
		foreach ($labels as &$l)
		{
			$l = strip_tags($l);
		}
		$opts->labels = $labels;
		$opts->primaryKey = $item->db_primary_key;
		$opts->Itemid = $tmpItemid;
		$opts->listRef = $listref;
		$opts->formid = $model->getFormModel()->getId();
		$opts->canEdit = $model->canEdit() ? "1" : "0";
		$opts->canView = $model->canView() ? "1" : "0";
		$opts->page = JRoute::_('index.php');
		$opts->isGrouped = $this->isGrouped;

		$formEls = array();
		foreach ($elementsNotInTable as $tmpElement)
		{
			$oo = new stdClass;
			$oo->name = $tmpElement->name;
			$oo->label = $tmpElement->label;
			$formEls[] = $oo;

		}
		$opts->formels = $formEls;//$elementsNotInTable;
		$opts->actionMethod = $params->get('actionMethod', 'floating');
		$opts->floatPos = $params->get('floatPos');
		$opts->csvChoose = (bool) $params->get('csv_frontend_selection');
		$popUpWidth = $params->get('popup_width', '');
		if ($popUpWidth !== '')
		{
			$opts->popup_width = (int) $popUpWidth;
		}
		$popUpHeight = $params->get('popup_height', '');
		if ($popUpHeight !== '')
		{
			$opts->popup_height = (int) $popUpHeight;
		}
		$xOffset = $params->get('popup_offset_x', '');
		if ($xOffset !== '')
		{
			$opts->popup_offset_x = (int) $xOffset;
		}

		$yOffset = $params->get('popup_offset_y', '');
		if ($yOffset !== '')
		{
			$opts->popup_offset_y = (int) $yOffset;
		}
		$opts->popup_edit_label = $params->get('editlabel', JText::_('COM_FABRIK_EDIT'));
		$opts->popup_view_label = $params->get('detaillabel', JText::_('COM_FABRIK_VIEW'));
		$opts->popup_add_label = $params->get('addlabel', JText::_('COM_FABRIK_ADD'));
		$opts->limitLength = $model->limitLength;
		$opts->limitStart = $model->limitStart;
		$csvOpts = new stdClass;
		$csvOpts->excel = (int) $params->get('csv_format');
		$csvOpts->inctabledata = (int) $params->get('csv_include_data');
		$csvOpts->incraw = (int) $params->get('csv_include_raw_data');
		$csvOpts->inccalcs = (int) $params->get('csv_include_calculations');
		$opts->csvOpts = $csvOpts;

		$opts->csvFields = $this->get('CsvFields');
		$csvOpts->incfilters = (int) $params->get('incfilters');

		$opts->data = $data;

		// If table data starts as empty then we need the html from the row
		// template otherwise we can't add a row to the table
		ob_start();
		$this->_row = new stdClass;
		$this->_row->id = '';
		$this->_row->class = 'fabrik_row';
		echo $this->loadTemplate('row');
		$opts->rowtemplate = ob_get_contents();
		ob_end_clean();

		// $$$rob if you are loading a table in a window from a form db join select record option
		// then we want to know the id of the window so we can set its showSpinner() method
		$opts->winid = JRequest::getVar('winid', '');
		$opts = json_encode($opts);

		JText::script('COM_FABRIK_PREV');
		JText::script('COM_FABRIK_SELECT_ROWS_FOR_DELETION');
		JText::script('JYES');
		JText::script('JNO');
		JText::script('COM_FABRIK_SELECT_COLUMNS_TO_EXPORT');
		JText::script('COM_FABRIK_INCLUDE_FILTERS');
		JText::script('COM_FABRIK_INCLUDE_DATA');
		JText::script('COM_FABRIK_INCLUDE_RAW_DATA');
		JText::script('COM_FABRIK_INLCUDE_CALCULATIONS');
		JText::script('COM_FABRIK_EXPORT');
		JText::script('COM_FABRIK_START');
		JText::script('COM_FABRIK_NEXT');
		JText::script('COM_FABRIK_END');
		JText::script('COM_FABRIK_PAGE');
		JText::script('COM_FABRIK_OF');
		JText::script('COM_FABRIK_LOADING');
		JText::script('COM_FABRIK_RECORDS');
		JText::script('COM_FABRIK_SAVING_TO');
		JText::script('COM_FABRIK_CONFIRM_DROP');
		JText::script('COM_FABRIK_CONFIRM_DELETE');
		JText::script('COM_FABRIK_NO_RECORDS');
		JText::script('COM_FABRIK_CSV_COMPLETE');
		JText::script('COM_FABRIK_CSV_DOWNLOAD_HERE');
		JText::script('COM_FABRIK_CONFIRM_DELETE');
		JText::script('COM_FABRIK_CSV_DOWNLOADING');
		JText::script('COM_FABRIK_FILE_TYPE');
		JText::script('COM_FABRIK_ADVANCED_SEARCH');
		JText::script('COM_FABRIK_FORM_FIELDS');

		// Keyboard short cuts
		JText::script('COM_FABRIK_LIST_SHORTCUTS_ADD');
		JText::script('COM_FABRIK_LIST_SHORTCUTS_EDIT');
		JText::script('COM_FABRIK_LIST_SHORTCUTS_DELETE');
		JText::script('COM_FABRIK_LIST_SHORTCUTS_FILTER');

		$script[] = "var list = new FbList('$listid',";
		$script[] = $opts;
		$script[] = ");";
		$script[] = "Fabrik.addBlock('list_{$listref}', list);";

		// Add in plugin objects
		$params = $model->getParams();
		$pluginManager = FabrikWorker::getPluginManager();
		$c = 0;

		$pluginManager->runPlugins('onLoadJavascriptInstance', $model, 'list');
		$aObjs = $pluginManager->_data;

		if (!empty($aObjs))
		{
			$script[] = "list.addPlugins([\n";
			$script[] = "  " . implode(",\n  ", $aObjs);
			$script[] = "]);";
		}
		// @since 3.0 inserts content before the start of the list render (currently on f3 tmpl only)
		$pluginManager->runPlugins('onGetContentBeforeList', $model, 'list');
		$this->assign('pluginBeforeList', $pluginManager->_data);

		$script[] = $model->filterJs;
		$script[] = "});";
		$script = implode("\n", $script);

		FabrikHelperHTML::script($src);
		FabrikHelperHTML::addScriptDeclaration($script);
		$this->getElementJs();

		// Reset data back to original settings
		$this->rows = $origRows;
	}

	/**
	 * Get element js
	 *
	 * @return  void
	 */

	protected function getElementJs()
	{
		$model = $this->getModel();
		$model->getElementJs();
	}

	/**
	 * Display the template
	 *
	 * @param   sting  $tpl  template
	 *
	 * @return void
	 */

	public function display($tpl = null)
	{
		if ($this->getLayout() == '_advancedsearch')
		{
			$this->advancedSearch($tpl);
			return;
		}
		$fbConfig = JComponentHelper::getParams('com_fabrik');
		$profiler = JProfiler::getInstance('Application');
		$app = JFactory::getApplication();

		// Force front end templates
		$tmpl = $this->get('tmpl');
		$this->_basePath = COM_FABRIK_FRONTEND . '/views';
		$this->addTemplatePath($this->_basePath . '/' . $this->_name . '/tmpl/' . $tmpl);

		$app = JFactory::getApplication();
		$root = $app->isAdmin() ? JPATH_ADMINISTRATOR : JPATH_SITE;
		$this->addTemplatePath($root . '/templates/' . $app->getTemplate() . '/html/com_fabrik/list/' . $tmpl);

		require_once COM_FABRIK_FRONTEND . '/views/modifiers.php';
		$user = JFactory::getUser();
		$model = $this->getModel();
		$document = JFactory::getDocument();
		$item = $model->getTable();
		$data = $model->render();
		$w = new FabrikWorker;

		// Add in some styling short cuts
		$c = 0;
		$form = $model->getFormModel();
		$nav = $this->get('Pagination');
		foreach ($data as $groupk => $group)
		{
			$last_pk = '';
			$last_i = 0;
			$num_rows = 1;
			foreach (array_keys($group) as $i)
			{
				$o = new stdClass;

				// $$$ rob moved merge wip code to FabrikModelTable::formatForJoins() - should contain fix for pagination
				$o->data = $data[$groupk][$i];
				$o->cursor = $num_rows + $nav->limitstart;
				$o->total = $nav->total;
				$o->id = 'list_' . $model->getRenderContext() . '_row_' . @$o->data->__pk_val;
				$o->class = 'fabrik_row oddRow' . $c;
				$data[$groupk][$i] = $o;
				$c = 1 - $c;
				$num_rows++;
			}
		}
		$groups = $form->getGroupsHiarachy();
		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel)
			{
				$elementModel->setContext($groupModel, $form, $model);
				$rowclass = $elementModel->setRowClass($data);
			}
		}
		$this->rows = $data;
		reset($this->rows);

		// Cant use numeric key '0' as group by uses groupd name as key
		$firstRow = current($this->rows);
		$this->assign('requiredFiltersFound', $this->get('RequiredFiltersFound'));
		$this->assign('advancedSearch', $this->get('AdvancedSearchLink'));
		$this->nodata = (empty($this->rows) || (count($this->rows) == 1 && empty($firstRow)) || !$this->requiredFiltersFound) ? true : false;
		$this->tableStyle = $this->nodata ? 'display:none' : '';
		$this->emptyStyle = $this->nodata ? '' : 'display:none';
		$params = $model->getParams();

		if (!$model->canPublish())
		{
			echo JText::_('COM_FABRIK_LIST_NOT_PUBLISHED');
			return false;
		}
		if (!$model->canView())
		{
			echo JText::_('JERROR_ALERTNOAUTHOR');
			return false;
		}

		if (!class_exists('JSite'))
		{
			require_once JPATH_ROOT . '/includes/application.php';
		}
		$app = JFactory::getApplication();
		$this->setTitle($w, $params, $model);
		/** depreciated (keep incase ppl use them in old tmpls**/
		$this->table = new stdClass;
		$this->table->label = $w->parseMessageForPlaceHolder($item->label, $_REQUEST);
		$this->table->intro = $w->parseMessageForPlaceHolder($item->introduction);
		$this->table->outro = $w->parseMessageForPlaceHolder($params->get('outro'));
		$this->table->id = $item->id;
		$this->table->renderid = $this->get('RenderContext');
		$this->table->db_table_name = $item->db_table_name;
		/** end **/
		$this->assign('list', $this->table);
		$this->group_by = $item->group_by;
		$this->form = new stdClass;
		$this->form->id = $item->form_id;
		$this->assign('renderContext', $this->get('RenderContext'));
		$this->formid = 'listform_' . $this->renderContext;
		$form = $model->getFormModel();
		$this->table->action = $this->get('TableAction');
		$this->showCSV = $model->canCSVExport();
		$this->showCSVImport = $model->canCSVImport();
		$this->canGroupBy = $model->canGroupBy();
		$this->assignRef('navigation', $nav);
		$this->nav = JRequest::getInt('fabrik_show_nav', $params->get('show-table-nav', 1))
			? $nav->getListFooter($this->renderContext, $this->get('tmpl')) : '';
		$this->nav = '<div class="fabrikNav">' . $this->nav . '</div>';
		$this->fabrik_userid = $user->get('id');
		$this->canDelete = $model->deletePossible() ? true : false;

		// 3.0 observed in list.js & html moved into fabrik_actions rollover
		$canPdf = FabrikWorker::canPdf();
		$this->showPDF = $params->get('pdf', $fbConfig->get('list_pdf', false));

		if (!$canPdf && $this->showPDF)
		{
			JError::raiseNotice(500, JText::_('COM_FABRIK_NOTICE_DOMPDF_NOT_FOUND'));
		}
		$this->emptyLink = $model->canEmpty() ? '#' : '';
		$this->csvImportLink = $this->showCSVImport ? JRoute::_("index.php?option=com_fabrik&view=import&filetype=csv&listid=" . $item->id) : '';
		$this->showAdd = $model->canAdd();
		if ($this->showAdd)
		{
			if ($params->get('show-table-add', 1))
			{
				$this->assign('addRecordLink', $this->get('AddRecordLink'));
			}
			else
			{
				$this->showAdd = false;
			}
		}
		$this->assign('addLabel', $params->get('addlabel', JText::_('COM_FABRIK_ADD')));
		$this->showRSS = $params->get('rss', 0) == 0 ? 0 : 1;
		if ($this->showRSS)
		{
			$this->rssLink = $model->getRSSFeedLink();
			if ($this->rssLink != '')
			{
				$attribs = array('type' => 'application/rss+xml', 'title' => 'RSS 2.0');
				$document->addHeadLink($this->rssLink, 'alternate', 'rel', $attribs);
			}
		}
		$this->pdfLink = JRoute::_('index.php?option=com_fabrik&view=list&format=pdf&listid=' . $item->id);
		list($this->headings, $groupHeadings, $this->headingClass, $this->cellClass) = $this->get('Headings');
		$this->assign('groupByHeadings', $this->get('GroupByHeadings'));
		$this->filter_action = $this->get('FilterAction');
		JDEBUG ? $profiler->mark('fabrik getfilters start') : null;
		$this->filters = $model->getFilters('listform_' . $this->renderContext);
		$this->assign('clearFliterLink', $this->get('clearButton'));
		JDEBUG ? $profiler->mark('fabrik getfilters end') : null;
		$this->assign('filterMode', (int) $params->get('show-table-filters'));
		$this->assign('toggleFilters', ($this->filterMode == 2 || $this->filterMode == 4));
		$this->assign('showFilters', $this->get('showFilters'));
		$this->showClearFilters = ($this->showFilters || $params->get('advanced-filter')) ? true : false;

		$this->assign('emptyDataMessage', $this->get('EmptyDataMsg'));
		$this->assignRef('groupheadings', $groupHeadings);
		$this->calculations = $this->_getCalculations($this->headings, $params->get('actionMethod', 'floating'));
		$this->assign('isGrouped', !($this->get('groupBy') == ''));
		$this->assign('colCount', count($this->headings));

		$this->assign('hasButtons', $this->get('hasButtons'));
		$this->assignRef('grouptemplates', $model->grouptemplates);

		$this->assignRef('params', $params);

		$this->loadTemplateBottom();

		$this->getManagementJS($this->rows);

		// Get dropdown list of other tables for quick nav in admin
		$this->tablePicker = $app->isAdmin() ? FabrikHelperHTML::tableList($this->table->id) : '';

		$this->buttons();

		$this->assign('pluginTopButtons', $this->get('PluginTopButtons'));
	}

	/**
	 * Set page title
	 *
	 * @param   object  $w        Fabrikworker
	 * @param   object  &$params  list params
	 * @param   object  $model    list model
	 *
	 * @return  void
	 */

	protected function setTitle($w, &$params, $model)
	{
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();
		$menus = $app->getMenu();
		$menu = $menus->getActive();
		/** Because the application sets a default page title, we need to get it
		 * right from the menu item itself
		 *if there is a menu item available AND the form is not rendered in a content plugin or module
		 */
		if (is_object($menu) && !$this->isMambot)
		{
			$menu_params = new JRegistry($menu->params);
			$params->set('page_title', $menu_params->get('page_title', $menu->title));
			$params->set('show_page_title', $menu_params->get('show_page_title', 0));
		}
		else
		{
			$params->set('show_page_title', JRequest::getInt('show_page_title', 0));
			$params->set('page_title', JRequest::getVar('title', ''));
			$params->set('show-title', JRequest::getInt('show-title', $params->get('show-title')));
		}

		$title = $params->get('page_title');
		if (empty($title))
		{
			$title = $app->getCfg('sitename');
		}
		if (!$this->isMambot)
		{
			$document->setTitle($w->parseMessageForPlaceHolder($title, $_REQUEST, false));
		}
	}

	/**
	 * Actually load the template and echo the view html
	 * will process jplugins if required.
	 *
	 * @return  void
	 */

	protected function output()
	{
		$profiler = JProfiler::getInstance('Application');
		$text = $this->loadTemplate();
		$model = $this->getModel();
		$params = $model->getParams();
		if ($params->get('process-jplugins'))
		{
			$opt = JRequest::getVar('option');
			JRequest::setVar('option', 'com_content');
			jimport('joomla.html.html.content');
			$text .= '{emailcloak=off}';
			$text = JHTML::_('content.prepare', $text);
			$text = preg_replace('/\{emailcloak\=off\}/', '', $text);
			JRequest::setVar('option', $opt);
		}
		JDEBUG ? $profiler->mark('end fabrik display') : null;

		// $$$ rob 09/06/2011 no need for isMambot test? should use ob_start() in module / plugin to capture the output
		echo $text;
	}

	/**
	 * Build an object with the button icons based on the current tmpl
	 *
	 * @return  void
	 */

	protected function buttons()
	{
		$model = $this->getModel();
		$params = $model->getParams();
		$this->buttons = new stdClass;
		$buttonProperties = array('class' => 'fabrikTip', 'opts' => "{notice:true}",
			'title' => '<span>' . JText::_('COM_FABRIK_EXPORT_TO_CSV') . '</span>');
		$buttonProperties['alt'] = JText::_('COM_FABRIK_EXPORT_TO_CSV');
		$this->buttons->csvexport = FabrikHelperHTML::image('csv-export.png', 'list', $this->tmpl, $buttonProperties);
		$buttonProperties['title'] = '<span>' . JText::_('COM_FABRIK_IMPORT_FROM_CSV') . '</span>';
		$buttonProperties['alt'] = JText::_('COM_FABRIK_IMPORT_TO_CSV');
		$this->buttons->csvimport = FabrikHelperHTML::image('csv-import.png', 'list', $this->tmpl, $buttonProperties);
		$buttonProperties['title'] = '<span>' . JText::_('COM_FABRIK_SUBSCRIBE_RSS') . '</span>';
		$buttonProperties['alt'] = JText::_('COM_FABRIK_SUBSCIBE_RSS');
		$this->buttons->feed = FabrikHelperHTML::image('feed.png', 'list', $this->tmpl, $buttonProperties);
		$buttonProperties['title'] = '<span>' . JText::_('COM_FABRIK_EMPTY') . '</span>';
		$buttonProperties['alt'] = JText::_('COM_FABRIK_EMPTY');
		$this->buttons->empty = FabrikHelperHTML::image('trash.png', 'list', $this->tmpl, $buttonProperties);

		$buttonProperties['title'] = '<span>' . JText::_('COM_FABRIK_GROUP_BY') . '</span>';
		$buttonProperties['alt'] = JText::_('COM_FABRIK_GROUP_BY');
		$this->buttons->groupby = FabrikHelperHTML::image('group_by.png', 'list', $this->tmpl, $buttonProperties);

		$buttonProperties['title'] = '<span>' . JText::_('COM_FABRIK_FILTER') . '</span>';
		$buttonProperties['alt'] = JText::_('COM_FABRIK_FILTER');
		$this->buttons->filter = FabrikHelperHTML::image('filter.png', 'list', $this->tmpl, $buttonProperties);

		$buttonProperties['title'] = '<span>' . $params->get('addlabel', JText::_('COM_FABRIK_ADD')) . '</span>';
		$buttonProperties['alt'] = $params->get('addlabel', JText::_('COM_FABRIK_ADD'));
		$this->buttons->add = FabrikHelperHTML::image('add.png', 'list', $this->tmpl, $buttonProperties);

		$buttonProperties['title'] = '<span>' . JText::_('COM_FABRIK_PDF') . '</span>';
		$buttonProperties['alt'] = JText::_('COM_FABRIK_PDF');
		$this->buttons->pdf = FabrikHelperHTML::image('pdf.png', 'list', $this->tmpl, $buttonProperties);
	}

	/**
	 * Get the table calculations
	 *
	 * @param   array   $aCols   columns
	 * @param   string  $method  tip style?
	 *
	 * @return  array
	 */

	protected function _getCalculations($aCols, $method)
	{
		$aData = array();
		$found = false;
		$model = $this->getModel();
		$modelCals = $model->getCalculations();
		foreach ($aCols as $key => $val)
		{
			if ($key == 'fabrik_actions' && $method == 'floating')
			{
				continue;
			}
			$calc = '';
			$res = '';
			$oCalcs = new stdClass;
			$oCalcs->grouped = array();

			if (array_key_exists($key, $modelCals['sums']))
			{
				$found = true;
				$res = $modelCals['sums'][$key];
				$calc .= $res;
				$tmpKey = str_replace('.', '___', $key) . '_calc_sum';
				$oCalcs->$tmpKey = $res;
			}
			if (array_key_exists($key . '_obj', $modelCals['sums']))
			{
				$found = true;
				$res = $modelCals['sums'][$key . '_obj'];
				foreach ($res as $k => $v)
				{
					if ($k != 'calc')
					{
						@$oCalcs->grouped[$k] .= '<span class="calclabel">' . $v->calLabel . ':</span> ' . $v->value . '<br />';
					}
				}
			}

			if (array_key_exists($key, $modelCals['avgs']))
			{
				$found = true;
				$res = $modelCals['avgs'][$key];
				$calc .= $res;
				$tmpKey = str_replace('.', '___', $key) . '_calc_average';
				$oCalcs->$tmpKey = $res;
			}

			if (array_key_exists($key . '_obj', $modelCals['avgs']))
			{
				$found = true;
				$res = $modelCals['avgs'][$key . '_obj'];
				foreach ($res as $k => $v)
				{
					if ($k != 'calc')
					{
						@$oCalcs->grouped[$k] .= '<span class="calclabel">' . $v->calLabel . ':</span> ' . $v->value . '<br />';
					}
				}
			}

			if (array_key_exists($key . '_obj', $modelCals['medians']))
			{
				$found = true;
				$res = $modelCals['medians'][$key . '_obj'];
				foreach ($res as $k => $v)
				{
					if ($k != 'calc')
					{
						@$oCalcs->grouped[$k] .= '<span class="calclabel">' . $v->calLabel . ':</span> ' . $v->value . '<br />';
					}
				}
			}

			if (array_key_exists($key, $modelCals['medians']))
			{
				$found = true;
				$res = $modelCals['medians'][$key];
				$calc .= $res;
				$tmpKey = str_replace('.', '___', $key) . "_calc_median";
				$oCalcs->$tmpKey = $res;
			}

			if (array_key_exists($key . '_obj', $modelCals['count']))
			{
				$found = true;
				$res = $modelCals['count'][$key . '_obj'];
				foreach ($res as $k => $v)
				{
					if ($k != 'calc')
					{
						@$oCalcs->grouped[$k] .= '<span class="calclabel">' . $v->calLabel . ':</span> ' . $v->value . '<br />';
					}
				}
			}

			if (array_key_exists($key, $modelCals['count']))
			{
				$res = $modelCals['count'][$key];
				$calc .= $res;
				$tmpKey = str_replace('.', '___', $key) . "_calc_count";
				$oCalcs->$tmpKey = $res;
				$found = true;
			}

			if (array_key_exists($key . '_obj', $modelCals['custom_calc']))
			{
				$found = true;
				$res = $modelCals['custom_calc'][$key . '_obj'];
				foreach ($res as $k => $v)
				{
					if ($k != 'calc')
					{
						@$oCalcs->grouped[$k] .= '<span class="calclabel">' . $v->calLabel . ':</span> ' . $v->value . '<br />';
					}
				}
			}

			if (array_key_exists($key, $modelCals['custom_calc']))
			{
				$res = $modelCals['custom_calc'][$key];
				$calc .= $res;
				$tmpKey = str_replace('.', '___', $key) . "_calc_custom_calc";
				$oCalcs->$tmpKey = $res;
				$found = true;
			}

			$key = str_replace('.', '___', $key);
			$oCalcs->calc = $calc;
			$aData[$key] = $oCalcs;
		}
		$this->assign('hasCalculations', $found);
		return $aData;
	}

	/**
	 * Get the table's forms hidden fields
	 *
	 * @return  string  hidden fields
	 */

	protected function loadTemplateBottom()
	{
		$app = JFactory::getApplication();
		$menuItem = $app->getMenu('site')->getActive();
		$Itemid = is_object($menuItem) ? $menuItem->id : 0;
		$model = $this->getModel();
		$item = $model->getTable();

		$reffer = str_replace('&', '&amp;', JRequest::getVar('REQUEST_URI', '', 'server'));
		$reffer = FabrikString::removeQSVar($reffer, 'fabrik_incsessionfilters');
		$this->hiddenFields = array();

		// $$$ rob 15/12/2011 - if in com_content then doing this means you cant delete rows
		$this->hiddenFields[] = '<input type="hidden" name="option" value="' . JRequest::getCmd('option', 'com_fabrik') . '" />';

		// $$$ rob 28/12/2011 but when using com_content as a value you cant filter!
		// $this->hiddenFields[] = '<input type="hidden" name="option" value="com_fabrik" />';
		$this->hiddenFields[] = '<input type="hidden" name="orderdir" value="" />';
		$this->hiddenFields[] = '<input type="hidden" name="orderby" value="" />';

		// $$$ rob if the content plugin has temporarily set the view to list then get view from origview var, if that doesn't exist
		// revert to view var. Used when showing table in article/blog layouts
		$view = JRequest::getVar('origview', JRequest::getVar('view', 'list'));
		$this->hiddenFields[] = '<input type="hidden" name="view" value="' . $view . '" />';

		$this->hiddenFields[] = '<input type="hidden" name="listid" value="' . $item->id . '"/>';
		$this->hiddenFields[] = '<input type="hidden" name="listref" value="' . $this->renderContext . '"/>';
		$this->hiddenFields[] = '<input type="hidden" name="Itemid" value="' . $Itemid . '"/>';

		// Removed in favour of using list_{id}_limit dorop down box
		$this->hiddenFields[] = '<input type="hidden" name="fabrik_referrer" value="' . $reffer . '" />';
		$this->hiddenFields[] = JHTML::_('form.token');

		$this->hiddenFields[] = '<input type="hidden" name="format" value="html" />';

		// $packageId = JRequest::getInt('_packageId', 0);
		// $$$ rob testing for ajax table in module
		$packageId = $model->packageId;
		$this->hiddenFields[] = '<input type="hidden" name="_packageId" value="' . $packageId . '" />';
		if ($app->isAdmin())
		{
			$this->hiddenFields[] = '<input type="hidden" name="task" value="list.view" />';
		}
		else
		{
			$this->hiddenFields[] = '<input type="hidden" name="task" value="" />';
		}
		$this->hiddenFields[] = '<input type="hidden" name="fabrik_listplugin_name" value="" />';
		$this->hiddenFields[] = '<input type="hidden" name="fabrik_listplugin_renderOrder" value="" />';

		// $$$ hugh - added this so plugins have somewhere to stuff any random data they need during submit
		$this->hiddenFields[] = '<input type="hidden" name="fabrik_listplugin_options" value="" />';

		$this->hiddenFields[] = '<input type="hidden" name="incfilters" value="1" />';

		// $$$ hugh - testing social profile hash stuff
		if (JRequest::getVar('fabrik_social_profile_hash', '') != '')
		{
			$this->hiddenFields[] = '<input type="hidden" name="fabrik_social_profile_hash" value="' . JRequest::getVar('fabrik_social_profile_hash')
				. '" />';
		}
		$this->hiddenFields = implode("\n", $this->hiddenFields);
	}

	/**
	 * Set the advanced search template
	 *
	 * @param   string  $tpl  template
	 *
	 * @return  void
	 */
	protected function advancedSearch($tpl)
	{
		$model = $this->getModel();
		$id = $model->getState('list.id');
		$this->assign('tmpl', $this->get('tmpl'));
		$model->setRenderContext($id);
		$this->listref = $model->getRenderContext();

		// Advanced search script loaded in list view - avoids timing issues with ie loading the ajax content and script
		$this->assignRef('rows', $this->get('advancedSearchRows'));
		$action = JRequest::getVar('HTTP_REFERER', 'index.php?option=com_fabrik', 'server');
		$this->assign('action', $action);
		$this->assign('listid', $id);
	}
}
