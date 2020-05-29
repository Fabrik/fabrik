<?php
/**
 * Fusion chart viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.fusionchart
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

/**
 * Fusion chart viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.fusionchart
 * @since       3.0
 */

class FabrikControllerVisualizationfusionchart extends FabrikControllerVisualization
{
	/**
	 * Ajax chart update
	 *
	 * @param   string $tmpl Template
	 *
	 * @return  void
	 */
	public function ajax_getFusionchart($tmpl = 'default')
	{
		$viewName = 'fusionchart';
		$model    = $this->getModel($viewName);
		$id       = $this->input->getInt('visualizationid', 0);
		$model->setId($id);
		$model->onAjax_getFusionchart();
	}
}
