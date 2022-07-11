<?php
/**
 * Fabrik Google Map Viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

/**
 * Fabrik Google Map Viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @since       3.0
 */
class FabrikControllerVisualizationgooglemap extends FabrikControllerVisualization
{
	/**
	 * Ajax markers
	 *
	 * @param   string $tmpl Template
	 *
	 * @return  void
	 */
	public function ajax_getMarkers($tmpl = 'default')
	{
		$viewName = 'googlemap';
		$model    = $this->getModel($viewName);
		$id       = $this->input->getInt('visualizationid', 0);
		$model->setId($id);
		$model->onAjax_getMarkers();
	}
}
