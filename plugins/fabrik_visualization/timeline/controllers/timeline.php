<?php
/**
 * Fabrik Timeline Viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.timeline
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

/**
 * Fabrik Time line Viz Controller
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
		$viewName = 'timeline';
		$model    = $this->getModel($viewName);
		$id       = $this->input->getInt('visualizationid', 0);
		$model->setId($id);
		$model->onAjax_getEvents();
	}
}
