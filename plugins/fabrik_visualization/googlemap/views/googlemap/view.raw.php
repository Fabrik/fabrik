<?php
/**
 * Fabrik Google Map Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * Fabrik Google Map Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @since       3.0
 */

class FabrikViewGooglemap extends JView
{

	/**
	 * Display the view
	 *
	 * @param   string  $tmpl  template
	 *
	 * @return void
	 */

	public function display($tmpl = 'default')
	{
		$document = JFactory::getDocument();
		$app = JFactory::getApplication();
		$input = $app->input;
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model = $this->getModel();
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$this->row = $model->getVisualization();
		echo $model->getJSIcons();
	}
}
