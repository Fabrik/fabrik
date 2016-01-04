<?php
/**
 * Fabrik Calendar Plug-in Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

require_once COM_FABRIK_FRONTEND . '/helpers/params.php';

/**
 * Fabrik Calendar Plug-in Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @since       3.0
 */

class FabrikControllerVisualizationfullcalendar extends FabrikControllerVisualization
{
	/**
	 * Display the view
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached - NOTE does not actually control caching !!!
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  A JController object to support chaining.
	 *
	 * @since   11.1
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$document = JFactory::getDocument();
		$viewName = 'fullcalendar';
		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);
		$formModel = JModelLegacy::getInstance('Form', 'FabrikFEModel');
		parent::display();

		return $this;
	}

	/**
	 * Delete an event
	 *
	 * @return  void
	 */

	public function deleteEvent()
	{
		$model = $this->getModel('fullcalendar');
		$model->deleteEvent();
		$this->getEvents();
	}

	/**
	 * Get events
	 *
	 * @return  void
	 */
	public function getEvents()
	{
		$viewName = 'calendar';
		$app = JFactory::getApplication();
		$input = $app->input;
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model = &$this->getModel($viewName);
		$id = $input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0)), 'get');
		$model->setId($id);
		echo $model->getEvents();
	}

	/**
	 * Choose which list to add the event to
	 *
	 * @return  void
	 */
	public function chooseaddevent()
	{
		$document = JFactory::getDocument();
		$viewName = 'fullcalendar';

		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		$formModel = $this->getModel('Form', 'FabrikFEModel');
		$view->setModel($formModel);

		// Push a model into the view
		$model = $this->getModel($viewName);
		$view->setModel($model, true);
		$view->chooseaddevent();
	}

	/**
	 * Show the add event form
	 *
	 * @return  void
	 */

	public function addEvForm()
	{ 
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input = $app->input;
		$listid = $input->getInt('listid');
		$viewName = 'fullcalendar';
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model = $this->getModel($viewName);
		$id = $input->getInt('visualizationid', $usersConfig->get('visualizationid', 0));
		$model->setId($id);
		$model->setupEvents();
		$config = JFactory::getConfig();
		$prefix = $config->get('dbprefix');

		if (array_key_exists($listid, $model->events))
		{ 
			$startDateField = $model->events[$listid][0]['startdate'];
			$endDateField = $model->events[$listid][0]['enddate'];
		}
		else
		{
			$startDateField = $prefix . 'fabrik_calendar_events___start_date';
			$endDateField = $prefix . 'fabrik_calendar_events___end_date';
		}

		$startDateField = FabrikString::safeColNameToArrayKey($startDateField);
		$endDateField = FabrikString::safeColNameToArrayKey($endDateField);
		$rowid = $input->getString('rowid', '', 'string');
		$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$listModel->setId($listid);
		$table = $listModel->getTable();
		$input->set('view', 'form');
		$input->set('formid', $table->form_id);
		$input->set('tmpl', 'component');
		$input->set('ajax', '1');
		$nextView = $input->get('nextview', 'form');
		$link = 'index.php?option=com_' . $package . '&view=' . $nextView . '&formid=' . $table->form_id . '&rowid=' . $rowid . '&tmpl=component&ajax=1';
		$link .= '&fabrik_window_id=' . $input->get('fabrik_window_id');

		$start_date = $input->getString('start_date', '');
		$end_date = $input->getString('end_date', '');

		if (!empty($start_date))
		{
			$link .= "&$startDateField=" . $start_date;
		}
		if (!empty($end_date))
		{
			$link .= "&$endDateField=" . $end_date;
		}

		// $$$ rob have to add this to stop the calendar filtering itself after adding an new event?
		$link .= '&clearfilters=1';
		$this->setRedirect($link);
	}
}
