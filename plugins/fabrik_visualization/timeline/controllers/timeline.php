<?php
/**
 * Fabrik Timeline Viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.timeline
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

/**
 * Fabrik Timeline Viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.timeline
 * @since       3.0
 */

class FabrikControllerVisualizationtimeline extends FabrikControllerVisualization
{

	/**
	 * Get a series of timeline events
	 *
	 * @return  void
	 */

	public function ajax_getEvents()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$viewName = 'timeline';
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model = $this->getModel($viewName);
		$id = $input->getInt('visualizationid', 0);
		$model->setId($id);
		$model->onAjax_getEvents();
	}
}
