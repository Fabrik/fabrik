<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

/**
 * Media viz Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Contact
 * @since 1.5
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
?>