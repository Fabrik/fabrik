<?php
/**
 * Fabrik Calendar HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * Fabrik Calendar HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @since       3.0
 */

class FabrikViewCalendar extends JViewLegacy
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 */

	public function display($tpl = 'default')
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input = $app->input;
		$j3 = FabrikWorker::j3();
		$Itemid = FabrikWorker::itemId();
		$model = $this->getModel();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$id = $input->get('id', $usersConfig->get('visualizationid', $input->get('visualizationid', 0)));
		$model->setId($id);
		$this->row = $model->getVisualization();

		if (!$model->canView())
		{
			echo FText::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		$params = $model->getParams();
		$this->params = $params;
		$this->containerId = $model->getJSRenderContext();
		$this->filters = $this->get('Filters');
		$this->showFilters = $model->showFilters();
		$this->showTitle = $input->getInt('show-title', 1);
		$this->filterFormURL = $this->get('FilterFormURL');
		$calendar = $this->row;

		JHTML::stylesheet('media/com_fabrik/css/list.css');
		$this->canAdd = (bool) $params->get('calendar-read-only', 0) == 1 ? false : $model->getCanAdd();
		$this->requiredFiltersFound = $this->get('RequiredFiltersFound');

		if ($params->get('calendar_show_messages', '1') == '1' && $this->canAdd && $this->requiredFiltersFound)
		{
			$msg = FText::_('PLG_VISUALIZATION_CALENDAR_DOUBLE_CLICK_TO_ADD');
			$msg .= $model->getDateLimitsMsg();
			$app->enqueueMessage($msg);
		}

		JHTML::stylesheet('media/com_fabrik/css/list.css');

		// Get all list where statements - which are then included in the ajax call to ensure we get the correct data set loaded
		$urlfilters = new stdClass;
		$urlfilters->where = $model->buildQueryWhere();

		$urls = new stdClass;

		// Don't JRoute as its wont load with sef?
		$urls->del = 'index.php?option=com_' . $package
		. '&controller=visualization.calendar&view=visualization&task=deleteEvent&format=raw&Itemid=' . $Itemid . '&id=' . $id;
		$urls->add = 'index.php?option=com_' . $package . '&view=visualization&format=raw&Itemid=' . $Itemid . '&id=' . $id;
		$user = JFactory::getUser();
		$legend = $params->get('show_calendar_legend', 0) ? $model->getLegend() : '';
		$tpl = $j3 ? 'bootstrap' : 'default';
		$tpl = $params->get('calendar_layout', $j3);
		$options = new stdClass;
		$options->url = $urls;
		$options->dateLimits = $model->getDateLimits();

		$options->deleteables = $model->getDeleteAccess();
		$options->eventLists = $model->getEventLists();
		$options->calendarId = $calendar->id;
		$options->popwiny = $params->get('yoffset', 0);
		$options->urlfilters = $urlfilters;
		$options->canAdd = $this->canAdd;
		$options->showFullDetails = (bool) $params->get('show_full_details', false);

		$options->restFilterStart = FabrikWorker::getMenuOrRequestVar('resetfilters', 0, false, 'request');
		$options->tmpl = $tpl;

		$o = $model->getAddStandardEventFormInfo();

		if ($o != null)
		{
			$options->listid = $o->id;
		}

		// $$$rob @TODO not sure this is need - it isn't in the timeline viz
		$model->setRequestFilters();
		$options->filters = $model->filters;

		// End not sure
		$options->Itemid = $Itemid;
		$options->show_day = (bool) $params->get('show_day', true);
		$options->show_week = (bool) $params->get('show_week', true);
		$options->days = array(FText::_('SUNDAY'), FText::_('MONDAY'), FText::_('TUESDAY'), FText::_('WEDNESDAY'), FText::_('THURSDAY'),
			FText::_('FRIDAY'), FText::_('SATURDAY'));
		$options->shortDays = array(FText::_('SUN'), FText::_('MON'), FText::_('TUE'), FText::_('WED'), FText::_('THU'), FText::_('FRI'),
			FText::_('SAT'));
		$options->months = array(FText::_('JANUARY'), FText::_('FEBRUARY'), FText::_('MARCH'), FText::_('APRIL'), FText::_('MAY'), FText::_('JUNE'),
			FText::_('JULY'), FText::_('AUGUST'), FText::_('SEPTEMBER'), FText::_('OCTOBER'), FText::_('NOVEMBER'), FText::_('DECEMBER'));
		$options->shortMonths = array(FText::_('JANUARY_SHORT'), FText::_('FEBRUARY_SHORT'), FText::_('MARCH_SHORT'), FText::_('APRIL_SHORT'),
			FText::_('MAY_SHORT'), FText::_('JUNE_SHORT'), FText::_('JULY_SHORT'), FText::_('AUGUST_SHORT'), FText::_('SEPTEMBER_SHORT'),
			FText::_('OCTOBER_SHORT'), FText::_('NOVEMBER_SHORT'), FText::_('DECEMBER_SHORT'));
		$options->first_week_day = (int) $params->get('first_week_day', 0);

		$options->monthday = new stdClass;
		$options->monthday->width = (int) $params->get('calendar-monthday-width', 90);
		$options->monthday->height = (int) $params->get('calendar-monthday-height', 80);
		$options->greyscaledweekend = $params->get('greyscaled-week-end', 0) === '1';
		$options->viewType = $params->get('calendar_default_view', 'monthView');

		$options->weekday = new stdClass;
		$options->weekday->width = (int) $params->get('calendar-weekday-width', 90);
		$options->weekday->height = (int) $params->get('calendar-weekday-height', 10);
		$options->open = (int) $params->get('open-hour', 0);
		$options->close = (int) $params->get('close-hour', 24);
		$options->showweekends = (bool) $params->get('calendar-show-weekends', true);
		$options->readonly = (bool) $params->get('calendar-read-only', false);
		$options->timeFormat = $params->get('time_format', '%X');
		$options->readonlyMonth = (bool) $params->get('readonly_monthview', false);
		$options->j3 = FabrikWorker::j3();

		if (FabrikWorker::j3())
		{
			$options->buttons = new stdClass;
			$options->buttons->del = '<button class="btn popupDelete" data-task="deleteCalEvent">' . FabrikHelperHTML::icon('icon-delete') . '</button>';
			$options->buttons->edit = '<button class="btn popupEdit" data-task="editCalEvent">' . FabrikHelperHTML::icon('icon-edit') . '</button>';
			$options->buttons->view = '<button class="btn popupView" data-task="viewCalEvent">' . FabrikHelperHTML::icon('icon-eye') . '</button>';
		}
		else
		{
			$src = COM_FABRIK_LIVESITE . 'plugins/fabrik_visualization/calendar/views/calendar/tmpl/' . $tpl . '/images/minus-sign.png';
			$options->buttons = '<img src="' . $src . '"
				alt = "del" class="fabrikDeleteEvent" />' . FText::_('PLG_VISUALIZATION_CALENDAR_DELETE');
		}

		$json = json_encode($options);

		JText::script('PLG_VISUALIZATION_CALENDAR_NEXT');
		JText::script('PLG_VISUALIZATION_CALENDAR_PREVIOUS');
		JText::script('PLG_VISUALIZATION_CALENDAR_DAY');
		JText::script('PLG_VISUALIZATION_CALENDAR_WEEK');
		JText::script('PLG_VISUALIZATION_CALENDAR_MONTH');
		JText::script('PLG_VISUALIZATION_CALENDAR_KEY');
		JText::script('PLG_VISUALIZATION_CALENDAR_TODAY');
		JText::script('PLG_VISUALIZATION_CALENDAR_CONF_DELETE');
		JText::script('PLG_VISUALIZATION_CALENDAR_DELETE');
		JText::script('PLG_VISUALIZATION_CALENDAR_VIEW');
		JText::script('PLG_VISUALIZATION_CALENDAR_EDIT');
		JText::script('PLG_VISUALIZATION_CALENDAR_ADD_EDIT_EVENT');
		JText::script('COM_FABRIK_FORM_SAVED');
		JText::script('PLG_VISUALIZATION_CALENDAR_EVENT_START_END');
		JText::script('PLG_VISUALIZATION_CALENDAR_DATE_ADD_TOO_LATE');
		JText::script('PLG_VISUALIZATION_CALENDAR_DATE_ADD_TOO_EARLY');

		$ref = $model->getJSRenderContext();

		$js = array();
		$js[] = "\tvar $ref = new fabrikCalendar('$ref');";
		$js[] = "\t$ref.render($json);";
		$js[] = "\tFabrik.addBlock('" . $ref . "', $ref);";
		$js[] = "\t" . $legend . "";
		$js[] = "" . $model->getFilterJs();
		$js = implode("\n", $js);

		$srcs = FabrikHelperHTML::framework();
		$srcs['FbListFilter'] = 'media/com_fabrik/js/listfilter.js';
		$srcs['Calendar'] = 'plugins/fabrik_visualization/calendar/calendar.js';

		FabrikHelperHTML::iniRequireJs($model->getShim());
		FabrikHelperHTML::script($srcs, $js);

		$viewName = $this->getName();
		$this->params = $model->getParams();
		$tpl = $params->get('calendar_layout', $tpl);
		$tmplpath = JPATH_ROOT . '/plugins/fabrik_visualization/calendar/views/calendar/tmpl/' . $tpl;
		$this->_setPath('template', $tmplpath);
		FabrikHelperHTML::stylesheetFromPath('plugins/fabrik_visualization/calendar/views/calendar/tmpl/' . $tpl . '/template.css');

		// Adding custom.css, just for the heck of it
		FabrikHelperHTML::stylesheetFromPath('plugins/fabrik_visualization/calendar/views/calendar/tmpl/' . $tpl . '/custom.css');

		return parent::display();
	}

	/**
	 * Choose which list to add an event to
	 *
	 * @return  void
	 */

	public function chooseaddevent()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$this->setLayout('chooseaddevent');
		$model = $this->getModel();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$rows = $model->getEventLists();
		$o = $model->getAddStandardEventFormInfo();
		$calendar = $model->getVisualization();
		$options = array();
		$options[] = JHTML::_('select.option', '', FText::_('PLG_VISUALIZATION_CALENDAR_PLEASE_SELECT'));

		if ($o != null)
		{
			$listid = $o->id;
			$options[] = JHTML::_('select.option', $listid, FText::_('PLG_VISUALIZATION_CALENDAR_STANDARD_EVENT'));
		}

		$model->getEvents();
		$config = JFactory::getConfig();
		$prefix = $config->get('dbprefix');
		$attribs = 'class="inputbox" size="1" ';
		$options = array_merge($options, $rows);
		$this->_eventTypeDd = JHTML::_('select.genericlist', $options, 'event_type', $attribs, 'value', 'text', '', 'fabrik_event_type');

		/*
		 * Tried loading in iframe and as an ajax request directly - however
		 * in the end decided to set a call back to the main calendar object (via the package manager)
		 * to load up the new add event form
		 */
		$ref = $model->getJSRenderContext();
		$script = array();
		//$script[] = "window.addEvent('fabrik.loaded', function() {";
		$script[] = "document.id('fabrik_event_type').addEvent('change', function(e) {";
		$script[] = "var fid = e.target.get('value');";
		$script[] = "var o = ({'d':'','listid':fid,'rowid':0});";
		$script[] = "o.datefield = '{$prefix}fabrik_calendar_events___start_date';";
		$script[] = "o.datefield2 = '{$prefix}fabrik_calendar_events___end_date';";
		$script[] = "o.labelfield = '{$prefix}fabrik_calendar_events___label';";

		foreach ($model->events as $tid => $arr)
		{
			foreach ($arr as $ar)
			{
				$script[] = "if(" . $ar['formid'] . " == fid)	{";
				$script[] = "o.datefield = '" . $ar['startdate'] . "'";
				$script[] = "o.datefield2 = '" . $ar['enddate'] . "'";
				$script[] = "o.labelfield = '" . $ar['label'] . "'";
				$script[] = "}\n";
			}
		}

		$script[] = "Fabrik.blocks['" . $ref . "'].addEvForm(o);";
		$script[] = "Fabrik.Windows.chooseeventwin.close();";
		$script[] = "});";
		//$script[] = "});";

		echo '<h2>' . FText::_('PLG_VISUALIZATION_CALENDAR_PLEASE_CHOOSE_AN_EVENT_TYPE') . ':</h2>';
		echo $this->_eventTypeDd;
		FabrikHelperHTML::addScriptDeclaration(implode("\n", $script));
	}
}
