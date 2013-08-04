<?php
/**
 * Fabrik Google Map Viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
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
	 * @param   string  $tmpl  Template
	 *
	 * @return  void
	 */

	public function ajax_getMarkers($tmpl = 'default')
	{
		$viewName = 'googlemap';
		$app = JFactory::getApplication();
		$input = $app->input;
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model = $this->getModel($viewName);
		$id = $input->getInt('visualizationid', 0);
		$model->setId($id);
		$model->onAjax_getMarkers();
	}
}
