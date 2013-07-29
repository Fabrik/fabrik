<?php
/**
 * Fabrik Calendar Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * Fabrik Calendar Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @since       3.0
*/

class FabrikViewCalendar extends JView
{

	/**
	 * Display the view
	 *
	 * @param   string  $tmpl  Template
	 *
	 * @return  void
	 */

	public function display($tmpl = 'default')
	{
		$model = $this->getModel();
		$app = JFactory::getApplication();
		$input = $app->input;
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		echo $model->getEvents();
	}

}
