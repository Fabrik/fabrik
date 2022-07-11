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
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @since       3.0
 */
class FabrikControllerVisualizationcalendar extends FabrikControllerVisualization
{
	/**
	 * Delete an event
	 *
	 * @return  void
	 */
	public function deleteEvent()
	{
		$model = $this->getModel('calendar');
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
		$model  = $this->getModel('calendar');
		$id     = $input->getInt('id', $config->get('visualizationid', $input->getInt('visualizationid', 0)));
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
		// Set the default view name
		$view      = $this->getView('calendar', $this->doc->getType());
		$formModel = $this->getModel('Form', 'FabrikFEModel');
		$view->setModel($formModel);

		// Push a model into the view
		$model = $this->getModel('calendar');
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
		$package     = $this->package;
		$input       = $this->input;
		$listId      = $input->getInt('listid');
		$viewName    = 'calendar';
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model       = $this->getModel($viewName);
		$id          = $input->getInt('visualizationid', $usersConfig->get('visualizationid', 0));
		$model->setId($id);
		$model->setupEvents();
		$prefix = $this->config->get('dbprefix');

		if (array_key_exists($listId, $model->events))
		{
			$dateField = $model->events[$listId][0]['startdate'];
		}
		else
		{
			$dateField = $prefix . 'fabrik_calendar_events___start_date';
		}

		$dateField = FabrikString::safeColNameToArrayKey($dateField);
		$rowId     = $input->getString('rowid', '');
		$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$listModel->setId($listId);
		$table = $listModel->getTable();
		$input->set('view', 'form');
		$input->set('formid', $table->form_id);
		$input->set('tmpl', 'component');
		$input->set('ajax', '1');
		$nextView = $input->get('nextview', 'form');
		$link     = 'index.php?option=com_' . $package . '&view=' . $nextView . '&formid=' . $table->form_id . '&rowid=' . $rowId . '&tmpl=component&ajax=1';
		$link .= '&' . $prefix . 'fabrik_calendar_events___visualization_id=' . $input->getInt($prefix . 'fabrik_calendar_events___visualization_id');
		$link .= '&fabrik_window_id=' . $input->get('fabrik_window_id');

		$start_date = $input->getString('start_date', '');

		if (!empty($start_date))
		{
			$link .= "&$dateField=" . $start_date;
		}

		// $$$ rob have to add this to stop the calendar filtering itself after adding an new event?
		$link .= '&clearfilters=1';
		$this->setRedirect($link);
	}
}
