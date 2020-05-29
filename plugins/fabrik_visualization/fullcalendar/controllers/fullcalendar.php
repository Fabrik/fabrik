<?php
/**
 * Fabrik Calendar Plug-in Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

/**
 * Fabrik Calendar Plug-in Controller
 * Fabrik Calendar Plug-in Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @since       3.0
 */
class FabrikControllerVisualizationfullcalendar extends FabrikControllerVisualization
{
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
		$input  = $this->input;
		$config = JComponentHelper::getParams('com_fabrik');
		$model  = $this->getModel('fullcalendar');
		$id     = $input->getInt('visualizationid', $config->get('visualizationid', 0));
		$model->setId($id);
		echo $model->getEvents();
	}

	/**
	 * Choose which list to add the event to
	 *
	 * @return  void
	 */
	public function chooseAddEvent()
	{
		// Set the default view name
		$view      = $this->getView('fullcalendar', $this->doc->getType());
		//$view      = $this->getView('fullcalendar');
		$formModel = $this->getModel('Form', 'FabrikFEModel');
		$view->setModel($formModel);

		// Push a model into the view
		$model = $this->getModel('fullcalendar');
		$view->setModel($model, true);
		$view->chooseAddEvent();
	}

	/**
	 * Show the add event form
	 *
	 * @return  void
	 */
	public function addEvForm()
	{
		$package     = $this->package;
		$input       = $this->input;
		$listId      = $input->getInt('listid');
		$viewName    = 'fullcalendar';
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model       = $this->getModel($viewName);
		$id          = $input->getInt('visualizationid', $usersConfig->get('visualizationid', 0));
		$model->setId($id);
		$model->setupEvents();
		$prefix = $this->config->get('dbprefix');

		if (array_key_exists($listId, $model->events))
		{
			$events = array_shift($model->events[$listId]);
			$startDateField = $events['startdate'];
			$endDateField   = $events['enddate'];
		}
		else
		{
			$startDateField = $prefix . 'fabrik_calendar_events___start_date';
			$endDateField   = $prefix . 'fabrik_calendar_events___end_date';
		}

		$startDateField = FabrikString::safeColNameToArrayKey($startDateField);
		$endDateField   = FabrikString::safeColNameToArrayKey($endDateField);
		$rowId          = $input->getString('rowid', '');
		$Itemid         = $input->get('Itemid', '');

		/** @var FabrikFEModelList $listModel */
		$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$listModel->setId($listId);
		$table = $listModel->getTable();
		$input->set('view', 'form');
		$input->set('formid', $table->form_id);
		$input->set('tmpl', 'component');
		$input->set('ajax', '1');

		if (!empty($Itemid))
		{
			$input->set('Itemid', $Itemid);
		}

		$nextView = $input->get('nextview', 'form');

		$link = 'index.php?option=com_' . $package . '&view=' . $nextView . '&formid=' . $table->form_id;
		$link .= '&rowid=' . $rowId . '&tmpl=component&ajax=1';

		if (!empty($Itemid))
		{
			$link .= '&Itemid=' . $Itemid;
		}

		$link .= '&format=partial&fabrik_window_id=' . $input->get('fabrik_window_id');

		$startDate = $input->getString('start_date', '');
		$endDate   = $input->getString('end_date', '');

		if (!empty($startDate))
		{
			$link .= "&$startDateField=" . $startDate;
		}

		if (!empty($endDate))
		{
			$link .= "&$endDateField=" . $endDate;
		}

		// $$$ rob have to add this to stop the calendar filtering itself after adding an new event?
		$link .= '&clearfilters=1';
		$this->setRedirect($link);
	}
}
