<?php
/**
 * Fabrik Calendar HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * Fabrik Calendar HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @since       3.0
 */

class FabrikViewCalendar extends JView
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
		$Itemid = FabrikWorker::itemId();
		$model = $this->getModel();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$id = $input->get('id', $usersConfig->get('visualizationid', $input->get('visualizationid', 0)));
		$model->setId($id);
		$this->row = $model->getVisualization();
		$params = $model->getParams();
		$this->params = $params;
		$this->containerId = $model->getJSRenderContext();
		$this->filters = $this->get('Filters');
		$this->showFilters = $input->getInt('showfilters', (int) $params->get('show_filters', 1)) === 1 ? 1 : 0;
		$this->showTitle = $input->getInt('show-title', 1);
		$this->filterFormURL = $this->get('FilterFormURL');

		$calendar = $model->_row;

		$fbConfig = JComponentHelper::getParams('com_fabrik');
		JHTML::stylesheet('media/com_fabrik/css/list.css');
		$params = $model->getParams();

		$canAdd = $params->get('calendar-read-only', 0) == 1 ? 0 : $this->get('CanAdd');

		$this->assign('requiredFiltersFound', $this->get('RequiredFiltersFound'));
		if ($canAdd && $this->requiredFiltersFound)
		{
			$app->enqueueMessage(JText::_('PLG_VISUALIZATION_CALENDAR_DOUBLE_CLICK_TO_ADD'));
		}
		$this->assign('canAdd', $canAdd);

		$fbConfig = JComponentHelper::getParams('com_fabrik');
		JHTML::stylesheet('media/com_fabrik/css/list.css');
		$params = $model->getParams();

		// Get the active menu item
		$urlfilters = JRequest::get('get');
		unset($urlfilters['option']);
		unset($urlfilters['view']);
		unset($urlfilters['controller']);
		unset($urlfilters['Itemid']);
		unset($urlfilters['visualizationid']);
		unset($urlfilters['format']);
		unset($urlfilters['id']);
		if (empty($urlfilters))
		{
			$urlfilters = new stdClass;
		}
		$urls = new stdClass;

		// Don't JRoute as its wont load with sef?
		$urls->del = 'index.php?option=com_' . $package . '&controller=visualization.calendar&view=visualization&task=deleteEvent&format=raw&Itemid=' . $Itemid
			. '&id=' . $id;
		$urls->add = 'index.php?option=com_' . $package . '&view=visualization&format=raw&Itemid=' . $Itemid . '&id=' . $id;
		$user = JFactory::getUser();
		$legend = $params->get('show_calendar_legend', 0) ? $model->getLegend() : '';
		$tpl = $params->get('calendar_layout', 'default');
		$options = new stdClass;
		$options->url = $urls;
		$options->deleteables = $this->get('DeleteAccess');
		$options->eventLists = $this->get('eventLists');
		$options->calendarId = $calendar->id;
		$options->popwiny = $params->get('yoffset', 0);
		$options->urlfilters = $urlfilters;
		$options->canAdd = $canAdd;

		$options->restFilterStart = FabrikWorker::getMenuOrRequestVar('resetfilters', 0, false, 'request');
		$options->tmpl = $tpl;

		$o = $model->getAddStandardEventFormInfo();

		if ($o != null)
		{
			$options->listid = $o->id;
		}

		// $$$rob @TODO not sure this is need - it isnt in the timeline viz
		$model->setRequestFilters();
		$options->filters = $model->filters;

		// End not sure
		$options->Itemid = $Itemid;
		$options->show_day = (bool) $params->get('show_day', true);
		$options->show_week = (bool) $params->get('show_week', true);
		$options->days = array(JText::_('SUNDAY'), JText::_('MONDAY'), JText::_('TUESDAY'), JText::_('WEDNESDAY'), JText::_('THURSDAY'),
			JText::_('FRIDAY'), JText::_('SATURDAY'));
		$options->shortDays = array(JText::_('SUN'), JText::_('MON'), JText::_('TUE'), JText::_('WED'), JText::_('THU'), JText::_('FRI'),
			JText::_('SAT'));
		$options->months = array(JText::_('JANUARY'), JText::_('FEBRUARY'), JText::_('MARCH'), JText::_('APRIL'), JText::_('MAY'), JText::_('JUNE'),
			JText::_('JULY'), JText::_('AUGUST'), JText::_('SEPTEMBER'), JText::_('OCTOBER'), JText::_('NOVEMBER'), JText::_('DECEMBER'));
		$options->shortMonths = array(JText::_('JANUARY_SHORT'), JText::_('FEBRUARY_SHORT'), JText::_('MARCH_SHORT'), JText::_('APRIL_SHORT'),
			JText::_('MAY_SHORT'), JText::_('JUNE_SHORT'), JText::_('JULY_SHORT'), JText::_('AUGUST_SHORT'), JText::_('SEPTEMBER_SHORT'),
			JText::_('OCTOBER_SHORT'), JText::_('NOVEMBER_SHORT'), JText::_('DECEMBER_SHORT'));
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

		$ref = $model->getJSRenderContext();

		// Hack until we replace head.js with require.js
		$js = array();
		$js[] = "Fabrik.liveSite = '" . COM_FABRIK_LIVESITE . "';";

		// Need var decalaration for IE8
		$js[] = "var $ref = new fabrikCalendar('$ref');";
		$js[] = " $ref.render($json);";
		$js[] = "  Fabrik.addBlock('" . $ref . "', $ref);";
		$js[] = $legend;
		$js[] = $model->getFilterJs();

		$srcs = FabrikHelperHTML::framework();
		$srcs[] = 'media/com_fabrik/js/listfilter.js';
		$srcs[] = 'plugins/fabrik_visualization/calendar/calendar.js';
		$js = implode("\n", $js);
		FabrikHelperHTML::script($srcs, $js);

		$viewName = $this->getName();
		$this->params = $model->getParams();
		$tpl = $params->get('calendar_layout', $tpl);
		$tmplpath = JPATH_ROOT . '/plugins/fabrik_visualization/calendar/views/calendar/tmpl/' . $tpl;
		$this->_setPath('template', $tmplpath);

		$ab_css_file = $tmplpath . '/template.css';

		if (JFile::exists($ab_css_file))
		{
			JHTML::stylesheet('plugins/fabrik_visualization/calendar/views/calendar/tmpl/' . $tpl . '/template.css');
		}

		// Adding custom.css, just for the heck of it
		$ab_css_file = $tmplpath . '/custom.css';
		if (JFile::exists($ab_css_file))
		{
			JHTML::stylesheet('plugins/fabrik_visualization/calendar/views/calendar/tmpl/' . $tpl . '/custom.css');
		}
		return parent::display();
	}

	/**
	 * Choose which list to add an event to
	 *
	 * @return  void
	 */

	function chooseaddevent()
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
		$options[] = JHTML::_('select.option', '', JText::_('PLG_VISUALIZATION_CALENDAR_PLEASE_SELECT'));
		if ($o != null)
		{
			$listid = $o->id;
			$options[] = JHTML::_('select.option', $listid, JText::_('PLG_VISUALIZATION_CALENDAR_STANDARD_EVENT'));
		}
		$model->getEvents();
		$config = JFactory::getConfig();
		$prefix = $config->get('dbprefix');
		$this->_eventTypeDd = JHTML::_('select.genericlist', array_merge($options, $rows), 'event_type', 'class="inputbox" size="1" ', 'value', 'text', '', 'fabrik_event_type');

		/*
		 * Tried loading in iframe and as an ajax request directly - however
		 * in the end decided to set a call back to the main calendar object (via the package manager)
		 * to load up the new add event form
		 */
		$ref = $model->getJSRenderContext();
		$script = array();
		$script[] = "head.ready(function() {";
		$script[] = "document.id('fabrik_event_type').addEvent('change', function(e) {";
		$script[] = "var fid = e.target.get('value');";
		$script[] = "var o = ({'d':'','listid':fid,'rowid':0});";
		$script[] = "o.datefield = '{$prefix}fabrik_calendar_events___start_date';";
		$script[] = "o.datefield2 = '{$prefix}fabrik_calendar_events___end_date';";
		$script[] = "o.labelfield = '{$prefix}fabrik_calendar_events___label';";

		foreach ($model->_events as $tid => $arr)
		{
			foreach ($arr as $ar)
			{
				$script[] = "if(".$ar['formid']." == fid)	{";
				$script[] = "o.datefield = '".$ar['startdate'] . "'";
				$script[] = "o.datefield2 = '".$ar['enddate'] . "'";
				$script[] = "o.labelfield = '".$ar['label'] . "'";
				$script[] = "}\n";
			}
		}
		$script[] = "Fabrik.blocks['" . $ref . "'].addEvForm(o);";
		$script[] = "Fabrik.Windows.chooseeventwin.close();";
		$script[] = "});";
		$script[] = "});";

		echo '<h2>' . JText::_('PLG_VISUALIZATION_CALENDAR_PLEASE_CHOOSE_AN_EVENT_TYPE') . ':</h2>';
		echo $this->_eventTypeDd;
		FabrikHelperHTML::addScriptDeclaration(implode("\n", $script));
	}
}
