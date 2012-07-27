<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.media
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

/**
 * Media viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.media
 */

class FabrikControllerVisualizationmedia extends FabrikControllerVisualization
{

	function getPlaylist()
	{
		$model= $this->getModel('media');
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$id = JRequest::getInt('id', $usersConfig->get('visualizationid', JRequest::getInt('visualizationid', 0) ), 'get');
		$model->setId($id);
		$model->getVisualization();
		echo $model->getPlaylist();
	}

}
